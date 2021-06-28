<?php
/**
 * Plugin Name: WOO Daily Stock Limit
 * Plugin URI: https://github.com/amirition/woo-daily-stock-limit
 * Description: Set a daily stock limit for your products
 * Version: 0.1.0
 * Author: Amirition
 * Author URI: https://github.com/amirition/
 * Text Domain: wdsl
 * Domain Path: /lang
 */


/**
 * Add metabox for setting the daily limit by user
 */
function wdsl_add_custom_box() {
    $screens = [ 'product' ];
    foreach ( $screens as $screen ) {
        add_meta_box(
            'wdsl_custom_mb',
            __( 'Extra Information' ),
            'wdsl_custom_mb_html',
            $screen
        );
    }
}
add_action( 'add_meta_boxes', 'wdsl_add_custom_box' );

/**
 * The HTML construct for metabox
 * This metabox gets the value of daily limit
 * @param $post  Object     The current product object
 */
function wdsl_custom_mb_html( $post ) {
    $current_value = get_post_meta( $post->ID, 'order_per_day', true );
    ?>
    <label for="order-per-day"><?= __( 'The number of orders per day', 'wdsl' ) ?>></label>
    <input type="number" id="order-per-day" name="order_per_day" value="<?= $current_value ?>">
    <?php
}

/**
 * Save the value of custom metabox
 * This metabox contains the number of orders per day
 * @param $post_id  int     The if of the current product that's being edited
 */
function wdsl_save_custom_mb( $post_id ) {
    if ( array_key_exists( 'order_per_day', $_POST ) ) {
        update_post_meta(
            $post_id,
            'order_per_day',
            $_POST['order_per_day']
        );
    }
}
add_action( 'save_post_product', 'wdsl_save_custom_mb' );

/**
 * Set up the main cron job in the first midnight time
 * And after that it will run on a daily basis
 */
function wdsl_cron_setup() {
    if( !wp_next_scheduled( 'wdsl_daily_stock_hook' ) ) {
        wp_schedule_event( wdsl_get_midnight(), 'daily', 'wdsl_daily_stock_hook' );
    }
    add_action( 'wdsl_daily_stock_hook', 'wdsl_cron_exec' );
}
add_action( 'init', 'wdsl_cron_setup' );


/**
 * Get the products with the value being set
 * Then turn on stock management
 * And set the stock to the order per day limit
 */
function wdsl_cron_exec() {
    $args = array(
        'post_type'         =>  'product',
        'post_status'       =>  'publish',
        'posts_per_page'    =>  -1,
        'meta_value'        =>  0,
        'meta_key'          =>  'order_per_day',
        'meta_type'         =>  'numeric',
        'meta_compare'      =>  '>=',
    );

    $products = new WP_Query( $args );

    if( $products->have_posts() ) {
        while( $products->have_posts() ) {
            $products->the_post();
            update_post_meta( get_the_ID(), '_stock', get_post_meta( get_the_ID(), 'order_per_day', true ) );
            update_post_meta( get_the_ID(), '_manage_stock', 'yes' );
        }
        wp_reset_postdata();
    }
}

/**
 * Get the first midnight timestamp
 */
function wdsl_get_midnight() {
    return mktime(24, 0, 0);
}
