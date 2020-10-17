<?php
/**
 * Plugin Name: Backordered Status Plugin
 * Plugin URI: https://github.com/greenknightca/gk-wc-backordered-status
 * Description: Sets all new orders with backordered items to a custom "Backordered" Status
 * Author: Green Knight
 * Author URI: https://greenknight.ca
 * Version: 1.0.1
 * Requires at least: 5.5
 * Tested up to: 5.5
 * WC Requires at least: 4.3
 * WC tested up to: 4.6
 *
 */
defined( 'ABSPATH' ) || exit;

function gk_register_backordered_order_status() {
    register_post_status( 'wc-backordered', array(
        'label'                     => 'Backordered',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'Backordered <span class="count">(%s)</span>', 'Backordered <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'gk_register_backordered_order_status' );

function gk_add_backordered_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-backordered'] = 'Backordered';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'gk_add_backordered_to_order_statuses' );

function gk_if_backordered( $order_id, $old_status, $new_status) {
    if( $old_status == 'backordered' && $new_status == 'processing' ){
      return;
    }
    $order = new WC_Order( $order_id );
    if( $order->get_status()!='processing' ){
      return;
    }
    $items = $order->get_items();
    foreach ( $items as $item ) {
      $product = wc_get_product($item['product_id']);

        if ( $product->get_backorders()!='no' ) {
          $order->update_status('backordered');
          return;
        }
    }
}

add_action('woocommerce_order_status_changed', 'gk_if_backordered', 10, 3);
