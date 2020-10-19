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
      return; //We don't want to get stuck in a loop of constant backorders.
    }
    $order = new WC_Order( $order_id );
    if( $order->get_status()!='processing' ){
      return; //We only change orders to the backordered status is the payment is successful.
    }
    $items = $order->get_items();
    foreach ( $items as $item ) {
      $product = wc_get_product(($item['variation_id']!=0?$item['variation_id']:$item['product_id'])); //Check if the product is a variation or not.
      if( $product->get_manage_stock() ){ //Check if the product manages stock.
        if ( $product->get_backorders()!='no' && $product->get_stock_quantity() < 0) {
          $order->update_status('backordered'); //If the product allows for backorders, and the available stock is less than 0, set order to backordered.
          return;
        }
      }
      else if( $product->get_stock_status() == 'onbackorder' ){ //Check if the non stock managed product has a stock status of 'onbackorder'.
          $order->update_status('backordered');
          return;
      }
    }
}

add_action('woocommerce_order_status_changed', 'gk_if_backordered', 10, 3);
