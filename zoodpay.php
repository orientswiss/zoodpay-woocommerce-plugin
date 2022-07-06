<?php
/**
 * Plugin Name: Zoodpay
 * Description: Plugin Used ZoodPay payment gateway integration for Woocommerce.
 * Author: Zoodpay
 * Author URI: https://api.zoodpay.com
 * Version: 1.0.5
 * Requires at least: 4.0
 * Tested up to: 6
 * WC requires at least: 3.0
 * WC tested up to: 6.6.1
 * Text Domain: zoodpay
 * Domain Path: /languages
 * License: GPLv2 or later
 *
 */



if ( ! function_exists( 'add_action' ) ) {
    _e( 'Hi there!  I\'m just a plugin, not much I can do when called directly.', 'zoodpay' );
    exit;
}
defined( 'ABSPATH' ) || exit;


define( 'WC_ZOODPAY_MIN_WC_VER', '3.0' );



/* Make sure WooCommerce is active */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    if ( ! function_exists( 'ZDP_admin_notice' ) ) {
        function ZDP_admin_notice() { ?>
            <div class='notice notice-warning is-dismissible '>
                <p><?php _e( 'Woocommerce plugin is deactivate, please activate woocommerce plugin first to use zoodpay paymets .', 'zoodpay' ); ?>    </p>
            </div> <?php
        }

        add_action( 'admin_notices', 'ZDP_admin_notice' );
    }

    return;
}



function Zoodpay_wc_not_supported() {

    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Zoodpay requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'zoodpay' ), WC_ZOODPAY_MIN_WC_VER, WC_VERSION ) . '</strong></p></div>';
}


function ZoodPay_add_custom_pages() {
    $_zoodpay_success = array(
        'post_title'   => wp_strip_all_tags( 'Zoodpay Success' ),
        'post_content' => __( 'zoodpay default payment success page.', 'zoodpay' ),
        'post_status'  => 'publish',
        'post_author'  => 1,
        'post_type'    => 'page',
    );
    $success_page_id  = wp_insert_post( $_zoodpay_success );
    update_option( '_zoodpay_success', $success_page_id, true );
    $_zoodpay_failure = array(
        'post_title'   => wp_strip_all_tags( 'Zoodpay Failure' ),
        'post_content' => __( 'zoodpay default payment fail page.', 'zoodpay' ),
        'post_status'  => 'publish',
        'post_author'  => 1,
        'post_type'    => 'page',
    );
    $failure_page_id  = wp_insert_post( $_zoodpay_failure );
    update_option( '_zoodpay_failure', $failure_page_id, true );
}

register_activation_hook( __FILE__, 'ZoodPay_add_custom_pages' );
function ZoodPay_delete_custom_pages() {
    $success_page_ID = get_option( '_zoodpay_success', true );
    wp_delete_post( $success_page_ID, true );
    $failure_page_ID = get_option( '_zoodpay_failure', true );
    wp_delete_post( $failure_page_ID, true );
}

register_deactivation_hook( __FILE__, 'ZoodPay_delete_custom_pages' );
function Zoodpay_remove_plugin_data() {
    delete_option( 'woocommerce_zoodpay_settings' );
}

register_uninstall_hook( __FILE__, 'Zoodpay_remove_plugin_data' );

add_action( 'plugins_loaded', 'Zoodpay_paymets_init', 0 );

function Zoodpay_paymets_init() {
    if ( version_compare( WC_VERSION, WC_ZOODPAY_MIN_WC_VER, '<' ) ) {
        add_action( 'admin_notices', 'Zoodpay_wc_not_supported' );

        return;
    }

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    include_once( 'ZD_add_setting_page.php' );

    add_filter( 'woocommerce_payment_gateways', 'Zoodpay_add_gateway_class' );

    function Zoodpay_add_gateway_class( $gateways ) {
        $gateways[] = 'WC_Zoodpay_Gateway';

        return $gateways;
    }
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ZoodPay_payment_action_links' );
function ZoodPay_payment_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zoodpay' ) ) . '">' . __( 'Settings', 'zoodpay' ) . '</a>',
    );

    return array_merge( $plugin_links, $links );
}

add_filter( 'woocommerce_checkout_fields', 'ZoodPay_shipping_phone_checkout' );
add_filter( 'woocommerce_available_payment_gateways', 'Zoodpay_unset_gateway_by_detail' );
function Zoodpay_unset_gateway_by_detail( $available_gateways ) {
    global $woocommerce;
    if ( is_admin() ) {
        return $available_gateways;
    }
    if ( ! is_checkout() ) {
        return $available_gateways;
    }
    $unset         = false;
    $order_total   = WC()->cart->total;
    $ZPI_min_value = get_option( '_ZPI_min_value_', true );
    $ZPI_max_value = get_option( '_ZPI_max_value_', true );
    $PAD_min_value = get_option( '_PAD_min_value_', true );
    $PAD_max_value = get_option( '_PAD_max_value_', true );
    $config_status = get_option( '_Zoodpay_config_status_', true );
    if ( $config_status == '' || $config_status == 'false' ) {
        unset( $available_gateways['zoodpay'] );
    }
    if ( $ZPI_min_value <= floatval( $order_total ) && $ZPI_max_value > floatval( $order_total ) ) {
        $unset = true;
    } elseif ( $PAD_min_value <= floatval( $order_total ) && $PAD_max_value > floatval( $order_total ) ) {
        $unset = true;
    } else {
        $unset = false;
    }
    if ( $unset == false && $unset != 1 ) {
        unset( $available_gateways['zoodpay'] );
    }

    return $available_gateways;
}

function ZoodPay_shipping_phone_checkout( $fields ) {
    $fields['shipping']['shipping_phone'] = array(
        'label'    => 'Phone',
        'required' => false,
        'class'    => array(
            'form-row-wide'
        ),
        'priority' => 25,
    );

    return $fields;
}

add_action( 'wp_ajax_get_configration', 'ZoodPay_get_configration' );
add_action( 'wp_ajax_nopriv_get_configration', 'ZoodPay_get_configration' );
function ZoodPay_get_configration() {
    check_ajax_referer( 'chek', 'security' );
    $zoodpay    = WC()->payment_gateways->payment_gateways() ['zoodpay'];
    $APIURL     = $zoodpay->get_option( 'environment' );
    $payload    = json_encode( array(
        "market_code" => sanitize_text_field( $_REQUEST['market_code'] )
    ) );
    $m_key      = $zoodpay->get_option( 'zoodpay_merchant_key' );
    $m_S_key    = base64_decode( $zoodpay->get_option( 'zoodpay_merchant_secret_key' ) );
    $config_url = $APIURL . 'configuration';
    $args       = array(
        'method'    => 'POST',
        'sslverify' => false,
        'headers'   => array(
            'Accept'         => 'application/json',
            'Content-Length' => strlen( $payload ),
            'Authorization'  => 'Basic ' . base64_encode( $m_key . ':' . $m_S_key ),
            'Content-Type'   => 'application/json',
        ),
        'body'      => $payload,
    );
    $return     = wp_remote_retrieve_body( wp_remote_post( $config_url, $args ) );

    if ( is_wp_error( $return ) || wp_remote_retrieve_response_code( $return ) != 200 ) {
        error_log( print_r( $return, true ) );
    }

    $result = json_decode( $return, true );

    delete_option( '_ZPI_min_value_', true );
    delete_option( '_ZPI_max_value_', true );
    delete_option( '_PAD_min_value_', true );
    delete_option( '_PAD_max_value_', true );
    delete_option( '_Zoodpay_config_status_', true );
    delete_option( '_Zoodpay_Market_code_', true );
    if ( $result['configuration'] ) {
        $countconfig = count( $result['configuration'] );
        update_option( '_Zoodpay_configuration', $return, true );
        for ( $i = 0; $i <= ( $countconfig - 1 ); $i ++ ) {
            update_option( '_' . $result['configuration'][ $i ]['service_code'] . '_min_value_', $result['configuration'][ $i ]['min_limit'], true );
            update_option( '_' . $result['configuration'][ $i ]['service_code'] . '_max_value_', $result['configuration'][ $i ]['max_limit'], true );
        }
        update_option( '_Zoodpay_config_status_', 'true', true );
        update_option( '_Zoodpay_Market_code_', sanitize_text_field( $_REQUEST['market_code'] ), true );

		$i=0;
		$availableServiceResult = __('Available to Operate', 'zoodpay') . "\r\n";
		$availableServiceResult .=  "================================ \r\n";
		$availableServiceResult .= __('Available Services','zoodpay') . ": \r\n";
		$availableServiceResult .= "\r\n";
		foreach ($result['configuration'] as $iValue) {
			$availableServiceResult .= $i. ". " .$iValue['service_code'] . " ".__('LIMIT','zoodpay')." -> " .$iValue['min_limit']  ." ". get_option('woocommerce_currency'). " - ". $iValue['max_limit']." ". get_option('woocommerce_currency') . "\r\n";
			$i++;
		}

		single_update_option_serialized('services',$availableServiceResult,'woocommerce_zoodpay_settings');

        esc_html_e( "success" );

    } else {
		single_update_option_serialized('services', __('No Service is Available'),'woocommerce_zoodpay_settings');
		update_option( '_Zoodpay_config_status_', 'false', true );

		_e( $return );
        exit;
    }
    exit;
}

add_action( 'wp_ajax_API_healtcheck', 'ZoodPay_API_healtcheck' );
add_action( 'wp_ajax_nopriv_API_healtcheck', 'ZoodPay_API_healtcheck' );
function ZoodPay_API_healtcheck() {
    check_ajax_referer( 'healtchek', 'security' );
    $config_url = 'https://sandbox-api.zoodpay.com/healthcheck';
    $args       = array(
        'method'    => 'GET',
        'sslverify' => false,
        'headers'   => array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ),

    );
    $return     = wp_remote_retrieve_body( wp_remote_post( $config_url, $args ) );

    if ( is_wp_error( $return ) || wp_remote_retrieve_response_code( $return ) != 200 ) {
        error_log( print_r( $return, true ) );
    }

    $result = json_decode( $return, true );
    if ( $result == "OK 0.0" ) {
        esc_html_e( "success" );
    } else {
        _e( $return );
        exit;
    }
    //
    exit;
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'ZoodPay_shipping_phone_checkout_display' );
function ZoodPay_shipping_phone_checkout_display( $order ) {
    echo wp_kses_post( '<p><b>Shipping Phone:</b> ' . get_post_meta( $order->get_id(), '_shipping_phone', true ) . '</p>' );
}

add_filter( 'woocommerce_billing_fields', 'ZoodPay_add_birth_date_billing_field', 20, 1 );
function ZoodPay_add_birth_date_billing_field( $billing_fields ) {
    $billing_fields['billing_birth_date'] = array(
        'type'     => 'date',
        'label'    => __( 'Birth date', 'zoodpay' ),
        'class'    => array(
            'form-row-wide'
        ),
        'priority' => 25,
        'required' => true,
        'clear'    => true,
    );

    return $billing_fields;
}

add_action( 'template_redirect', 'Zoodpay_redirect_depending_on_gateway' );
function Zoodpay_redirect_depending_on_gateway() {
    if ( isset( $_POST['merchant_order_reference'] ) && $_POST['merchant_order_reference'] != '' ) {
        global $wp;
        $data['merchant_order_reference'] = sanitize_text_field( $_POST['merchant_order_reference'] );
        $data['status']                   = sanitize_text_field( $_POST['status'] );
        $data['transaction_id']           = sanitize_text_field( $_POST['transaction_id'] );
        $data['signature']                = sanitize_text_field( $_POST['signature'] );
        $order                            = new WC_Order( $data['merchant_order_reference'] );
        $total                            = number_format( $order->get_total(), 2, '.', '' );
        $currency                         = get_woocommerce_currency();
        $order_id                         = trim( $order->get_order_number() );
        $get_trn_ID                       = get_post_meta( $data['merchant_order_reference'], '_transaction_id', true );
        $date                             = date( 'm/d/Y h:i:s a', time() );
        //Added Code
        $zoodpay      = WC()->payment_gateways->payment_gateways() ['zoodpay'];
        $marchantKey  = $zoodpay->get_option( 'zoodpay_merchant_key' );
        $saltKey      = base64_decode( $zoodpay->get_option( 'zoodpay_salt' ) );
        $marketCode   = get_option( '_Zoodpay_Market_code_', true );
        $order_status = $order->get_status();
        $sign         = $marketCode . '|' . $currency . '|' . $total . '|' . $order_id . '|' . $marchantKey . '|' . $get_trn_ID . '|' . htmlspecialchars_decode( $saltKey );
        $signature    = hash( 'sha512', $sign );

        if ( $data['signature'] == $signature ) {
            if ( $data['status'] == "Paid" ) {
                update_post_meta( $order->get_id(), '_transaction_id', $data['transaction_id'], true );
                $order->payment_complete();
            } else {
                update_post_meta( $order->get_id(), '_transaction_id', $data['transaction_id'], true );
                update_post_meta( $order->get_id(), 'zoodpay_failed_status', 'Failed completed' );
                $order->update_status( 'failed' );
            }
        }
    }
}

if ( ! function_exists( 'Zoodpay_get_private_order_notes' ) ) {

    function Zoodpay_get_private_order_notes( $order_id ) {
        global $wpdb;

        $table_perfixed = $wpdb->prefix . 'comments';
        $results        = $wpdb->get_results( "
        SELECT *
        FROM $table_perfixed
        WHERE  `comment_post_ID` = $order_id
        AND  `comment_type` LIKE  'order_note' AND  comment_approved = 1  ORDER by  comment_ID DESC LIMIT 1
    " );

        foreach ( $results as $note ) {
            $order_note[] = array(
                'note_id'      => $note->comment_ID,
                'note_date'    => $note->comment_date,
                'note_author'  => $note->comment_author,
                'note_content' => $note->comment_content,
            );
        }

        return $order_note;
    }
}
function Zoodpay_woocommerce_order_status_changed( $order_id ) {
    global $wpdb;
    $zoodpay     = WC()->payment_gateways->payment_gateways() ['zoodpay'];
    $order       = new WC_Order( $order_id );
    $orderstatus = $order->status;
    $order_notes = Zoodpay_get_private_order_notes( $order_id );

    $get_results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'posts WHERE post_type = "shop_order_refund" AND post_parent = "' . $order_id . '" ORDER BY ID DESC LIMIT 0,1' );
    $stat_refund = get_post_meta( $get_results[0]->ID, 'zoodpay_refund_status', true );
    $stat_failed = get_post_meta( $order_id, 'zoodpay_failed_status', true );
    //get_post_meta('zoodpay_refund_status',true);
    if ( $orderstatus == 'refunded' && $order->payment_method == 'zoodpay' && $stat_refund != "Refund completed" ) {

        $wpdb->query( "UPDATE " . $wpdb->prefix . "comments SET comment_approved='0' WHERE comment_ID=" . $order_notes[0]['note_id'] . "" );
        $order->update_status( 'processing', 'Zoodpay' );
        $order_note = Zoodpay_get_private_order_notes( $order_id );
        $wpdb->query( "UPDATE " . $wpdb->prefix . "comments SET comment_approved='0' WHERE comment_ID=" . $order_note[0]['note_id'] . "" );

        return;
        exit;
    }

    if ( $orderstatus == 'failed' && $order->payment_method == 'zoodpay' && $stat_failed != 'Failed completed' ) {

        $wpdb->query( "UPDATE " . $wpdb->prefix . "comments SET comment_approved='0' WHERE comment_ID=" . $order_notes[0]['note_id'] . "" );
        $order->update_status( 'cancelled', 'Zoodpay' );
        $order_note = Zoodpay_get_private_order_notes( $order_id );
        $wpdb->query( "UPDATE " . $wpdb->prefix . "comments SET comment_approved='0' WHERE comment_ID=" . $order_note[0]['note_id'] . "" );

        return;
        exit;
    }

    if ( $orderstatus != 'completed' ) {

        return;
        exit;
    }
    $marchentKey         = $zoodpay->get_option( 'zoodpay_merchant_key' );
    $marchent_Secret_Key = base64_decode( $zoodpay->get_option( 'zoodpay_merchant_secret_key' ) );
    $APIURL              = $zoodpay->get_option( 'environment' );
    $saltKey             = base64_decode( $zoodpay->get_option( 'zoodpay_salt' ) );
    $date                = date( 'Y-m-d\TH:i:s.000' );
    $total               = $order->get_total();
    $deldata             = json_encode( array(
        "delivered_at"         => $date,
        "final_capture_amount" => $total
    ) );
    $get_t_ID            = get_post_meta( $order->get_id(), '_transaction_id', true );
    $delevery_url        = $APIURL . 'transactions/' . $get_t_ID . '/delivery';
    $args                = array(
        'method'    => 'PUT',
        'timeout'   => 45,
        'sslverify' => false,
        'headers'   => array(
            'Accept'         => 'application/json',
            'Content-Length' => strlen( $deldata ),
            'Authorization'  => 'Basic ' . base64_encode( $marchentKey . ':' . $marchent_Secret_Key ),
            'Content-Type'   => 'application/json',
        ),
        'body'      => $deldata,
    );
    $return              = wp_remote_retrieve_body( wp_remote_post( $delevery_url, $args ) );

    if ( is_wp_error( $return ) || wp_remote_retrieve_response_code( $return ) != 200 ) {
        error_log( print_r( $return, true ) );
    }
    $del_data = json_decode( $return );
    //echo "<pre>";print_r($return);die;
}

add_action( 'woocommerce_order_status_changed', 'Zoodpay_woocommerce_order_status_changed', 10, 3 );
function Zoodpay_add_admin_scripts( $hook ) {
    global $post;
    if ( isset( $post->ID ) ) {
        $type = get_post_meta( $post->ID, "_transaction_type", true );
        if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
            if ( 'shop_order' === $post->post_type && ($type == "ZPI" || $type == "PAD") ) {
                wp_enqueue_script( 'plugin-script', plugin_dir_url( __FILE__ ) . 'assest/js/custom.js' );
            }
        }
    }

}

add_action( 'admin_enqueue_scripts', 'Zoodpay_add_admin_scripts', 10, 1 );
function Zoodpay_refund_process_order_status() {
    register_post_status( 'wc-zoodpay-refund', array(
        'label'                     => __( 'Refund Initiated', 'zoodpay' ),
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'Refund Initiated <span class="count">(%s)</span>', 'Refund Initiated <span class="count">(%s)</span>' )
    ) );
}

//add_action( 'init', 'Zoodpay_refund_process_order_status' );
function Zoodpay_process_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-refunded' === $key ) {
            $new_order_statuses['wc-zoodpay-refund'] = __( 'Refund Initiated', 'zoodpay' );
        }
    }

    return $new_order_statuses;
}

//add_filter( 'wc_order_statuses', 'Zoodpay_process_to_order_statuses' );
function Zoodpay_update_refund_status() {
    global $wpdb;

    if ( sanitize_text_field( isset( $_REQUEST['zoodpay_action'] ) ) && sanitize_text_field( $_REQUEST['zoodpay_action'] ) == "refund" ) {
        if ( $json = json_decode( file_get_contents( "php://input" ), true ) ) {

            $data['merchant_refund_reference'] = sanitize_text_field( $json['refund']['merchant_refund_reference'] );
            $data['refund_amount']             = sanitize_text_field( $json['refund']['refund_amount'] );
            $data['refund_id']                 = sanitize_text_field( $json['refund']['refund_id'] );
            $data['status']                    = sanitize_text_field( $json['refund']['status'] );
            $data['request_id']                = sanitize_text_field( $json['refund']['request_id'] );
            $data['declined_reason']           = sanitize_text_field( $json['refund']['declined_reason'] );
            $data['signature']                 = sanitize_text_field( $json['signature'] );
        } else {

            $data['merchant_refund_reference'] = sanitize_text_field( $_POST['refund']['merchant_refund_reference'] );
            $data['refund_amount']             = sanitize_text_field( $_POST['refund']['refund_amount'] );
            $data['refund_id']                 = sanitize_text_field( $_POST['refund']['refund_id'] );
            $data['status']                    = sanitize_text_field( $_POST['refund']['status'] );
            $data['request_id']                = sanitize_text_field( $_POST['refund']['request_id'] );
            $data['declined_reason']           = sanitize_text_field( $_POST['refund']['declined_reason'] );
            $data['signature']                 = sanitize_text_field( $_POST['signature'] );
        }

        $zoodpay     = WC()->payment_gateways->payment_gateways() ['zoodpay'];
        $marchantKey = $zoodpay->get_option( 'zoodpay_merchant_key' );
        $saltKey     = base64_decode( $zoodpay->get_option( 'zoodpay_salt' ) );

        $sign      = $data['merchant_refund_reference'] . '|' . floatval( $data['refund_amount'] ) . '|' . $data['status'] . '|' . $marchantKey . '|' . $data['refund_id'] . '|' . htmlspecialchars_decode( $saltKey );
        $signature = hash( 'sha512', $sign );


        if ( $data['signature'] == $signature ) {

            $meta = $wpdb->get_results( "SELECT * FROM `" . $wpdb->postmeta . "` WHERE meta_key='_request_id' AND meta_value='" . $data['request_id'] . "'" );
            update_post_meta( $meta[0]->post_id, '_refund_status', $data['status'] );
            update_post_meta( $meta[0]->post_id, 'declined_reason', $data['declined_reason'] );

            if ( $data['status'] == 'Declined' ) {
                update_post_meta( $meta[0]->post_id, '_refund_amount', 0 );
                update_post_meta( $meta[0]->post_id, '_order_total', '-0' );
                update_post_meta( $meta[0]->post_id, '_declined_amount', $data['refund_amount'] );

            }

            $order_id          = $data['merchant_refund_reference'];
            $refund_order      = new WC_Order( $order_id );
            $order_data        = $refund_order->get_data(); // The Order data
            $order_total_final = $order_data['total'];

            $get_results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'posts WHERE post_type = "shop_order_refund" AND post_parent = "' . $order_id . '" ORDER BY ID DESC ' );

            $_refund_amount_tot = "";

            foreach ( $get_results as $get_result ) {

                $_refund_amount = get_post_meta( $get_result->ID, '_refund_amount', true );

                $_refund_amount_tot += $_refund_amount;

            }

            if ( $order_total_final == $_refund_amount_tot ) {

                update_post_meta( $get_results[0]->ID, 'zoodpay_refund_status', 'Refund completed' );
                $refund_order->update_status( 'refunded', '' );
            } else {

                update_post_meta( $get_results[0]->ID, 'zoodpay_refund_status', 'initial refund' );
            }

        }


    }
}

add_action( 'init', 'Zoodpay_update_refund_status' );
function Zoodpay_update_order_status() {
    if ( sanitize_text_field( isset( $_REQUEST['zoodpay_action'] ) ) && sanitize_text_field( $_REQUEST['zoodpay_action'] ) == "ipn" ) {
        if ( $json = json_decode( file_get_contents( "php://input" ), true ) ) {
            $data['merchant_order_reference'] = sanitize_text_field( $json['merchant_order_reference'] );
            $data['transaction_id']           = sanitize_text_field( $json['transaction_id'] );
            $data['status']                   = sanitize_text_field( $json['status'] );
            $data['signature']                = sanitize_text_field( $json['signature'] );
        } else {
            $data['merchant_order_reference'] = sanitize_text_field( $_POST['merchant_order_reference'] );
            $data['transaction_id']           = sanitize_text_field( $_POST['transaction_id'] );
            $data['status']                   = sanitize_text_field( $_POST['status'] );
            $data['signature']                = sanitize_text_field( $_POST['signature'] );
        }
        $update_order = new WC_Order( $data['merchant_order_reference'] );
        $total        = number_format( $update_order->get_total(), 2, '.', '' );
        $currency     = get_woocommerce_currency();
        $order_id     = trim( $update_order->get_order_number() );
        $get_trn_ID   = get_post_meta( $data['merchant_order_reference'], '_transaction_id', true );
        $date         = date( 'm/d/Y h:i:s a', time() );

        //Added Code
        $zoodpay      = WC()->payment_gateways->payment_gateways() ['zoodpay'];
        $marchantKey  = $zoodpay->get_option( 'zoodpay_merchant_key' );
        $saltKey      = base64_decode( $zoodpay->get_option( 'zoodpay_salt' ) );
        $marketCode   = get_option( '_Zoodpay_Market_code_', true );
        $order_status = $update_order->get_status();
        $sign         = $marketCode . '|' . $currency . '|' . $total . '|' . $order_id . '|' . $marchantKey . '|' . $get_trn_ID . '|' . htmlspecialchars_decode( $saltKey );
        $signature    = hash( 'sha512', $sign );


        if ( $update_order->has_status( 'refunded' ) ) {

            return;
        }


        if ( $data['status'] == "Paid" && $data['transaction_id'] == $get_trn_ID && $data['signature'] == $signature ) {
            $update_order->update_status( 'processing', 'IPN Update: ' );

        } elseif ( $data['status'] == "Inactive" && $data['transaction_id'] == $get_trn_ID && $data['signature'] == $signature ) {
            update_post_meta( $order_id, 'zoodpay_failed_status', '' );
            $update_order->update_status( 'cancelled', 'IPN Update: ' );
        } elseif ( $data['status'] == "Failed" && $data['transaction_id'] == $get_trn_ID && $data['signature'] == $signature ) {
            update_post_meta( $order_id, 'zoodpay_failed_status', 'Failed completed' );
            $update_order->update_status( 'failed', 'IPN Update: ' );


        } elseif ( $data['status'] == "Cancelled" && $data['transaction_id'] == $get_trn_ID && $data['signature'] == $signature ) {

            $update_order->update_status( 'cancelled', 'IPN Update: ' );
        }
    }
}

add_action( 'init', 'Zoodpay_update_order_status' );

function Zoodpay_global_notice_meta_box() {
    $screens = array( 'shop_order' );
    foreach ( $screens as $screen ) {
        add_meta_box(
            'global-notice',
            __( 'ZoodPay Refund Details', 'zoodpay' ),
            'Zoodpay_global_notice_meta_box_callback',
            $screen
        );
    }
}

add_action( 'add_meta_boxes', 'Zoodpay_global_notice_meta_box' );
function Zoodpay_global_notice_meta_box_callback( $post ) {
    global $wpdb;
    wp_reset_query();
    $get_results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'posts WHERE post_type = "shop_order_refund" AND post_parent = "' . $post->ID . '"' );
    echo '<table id="customers">
              <tr>
                <th>Refund Date</th>
                <th>Amount Requested</th>
				<th>Amount Approved</th>
                <th>Status</th>
              </tr>';
    foreach ( $get_results as $get_result ) {
        $get_order_meta          = get_post_meta( $get_result->ID, '_refund_amount', true );
        $get_order_refund_status = get_post_meta( $get_result->ID, '_refund_status', true );
        $declined_reason         = get_post_meta( $get_result->ID, 'declined_reason', true );
        $amout_req               = get_post_meta( $get_result->ID, '_declined_amount', true );
        if ( $amout_req > 0 ) {
            $amout_req = $amout_req;
        } else {
            $amout_req = $get_order_meta;
        }

        if ( $get_order_refund_status == 'Initiated' ) {
            $get_order_meta = 0;
        } else {
            $get_order_meta = $get_order_meta;
        }
        if ( $get_order_refund_status != '' ) {
            echo '<tr>
                <td>' . str_replace( 'Order', 'Refund', $get_result->post_title ) . '</td>
				<td>' . $amout_req . '</td>
                <td>' . $get_order_meta . '</td>';
            if ( $declined_reason != '' ) {
                echo '<td>' . $get_order_refund_status . ' ( ' . $declined_reason . ' )</td>';
            } else {
                echo '<td>' . $get_order_refund_status . '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</table>';
    echo "<style>#customers {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
    }
    #customers td, #customers th {
    border: 1px solid #ddd;
    padding: 8px;
    }
#customers tr:hover {background-color: #ddd;}
#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
    background-color: #000;
  color: white;
}</style>";
}

function Zoodpay_admin_scripts() {

    wp_enqueue_script( 'main-script', plugin_dir_url( __FILE__ ) . 'assest/js/main.js' );
    wp_enqueue_style( 'main-stylesheet', plugin_dir_url( __FILE__ ) . 'assest/css/custom.css' );

}

add_action( 'wp_enqueue_scripts', 'Zoodpay_admin_scripts' );
add_action( 'wp_footer', 'Zoodpay_email_popup' );
if ( ! function_exists( 'Zoodpay_email_popup' ) ) {
    function Zoodpay_email_popup() {
        echo
        '<div id="myModal" class="modal">
  <!-- Modal content -->
  <div class="modal-content">
    <span class="closeX">&times;</span>
    <p  id="main-id">Some text in the Modal..</p>
  </div>
</div>';


    }
}
add_action( 'template_redirect', 'Zoodpay_default_payment_gateway' );
function Zoodpay_default_payment_gateway() {
    if ( is_checkout() && ! is_wc_endpoint_url() ) {

        $default_payment_id = 'zoodpay';
        WC()->session->set( 'chosen_payment_method', $default_payment_id );
    }
}

function single_update_option_serialized($opt_key,$opt_val,$opt_group){
	// get options-data as it exists before update
	$options = get_option($opt_group);

	// update it
	$options[$opt_key] = $opt_val;

	// store updated data
	update_option($opt_group,$options);

}

// Localize our plugin.
add_action( 'init', 'load_plugin_translations' );

/**
 * Load plugin translation file
 */
function load_plugin_translations() {

	load_plugin_textdomain(
			'zoodpay',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
