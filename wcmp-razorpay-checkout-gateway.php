<?php
/**
 * Plugin Name: WCMp Razorpay Checkout Gateway
 * Plugin URI: https://wc-marketplace.com/
 * Description: WCMp Razorpay Checkout Gateway is a payment gateway for pay with woocommerce as well as split payment with multivendor marketplace.
 * Author: WC Marketplace
 * Version: 1.0.0
 * Author URI: https://wc-marketplace.com/
 *
 * Text Domain: wcmp-razorpay-checkout-gateway
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

if (!class_exists('WCMP_Razorpay_Checkout_Gateway_Dependencies')) {
    require_once 'classes/class-wcmp-razorpay-checkout-gateway-dependencies.php';
}
require_once 'includes/wcmp-razorpay-checkout-gateway-core-functions.php';
require_once 'wcmp-razorpay-checkout-gateway-config.php';

if (!defined('WCMP_RAZORPAY_CHECKOUT_GATEWAY_PLUGIN_TOKEN')) {
    exit;
}
if (!defined('WCMP_RAZORPAY_CHECKOUT_GATEWAY_TEXT_DOMAIN')) {
    exit;
}

if(!WCMP_Razorpay_Checkout_Gateway_Dependencies::woocommerce_active_check()){
    add_action('admin_notices', 'woocommerce_inactive_notice');
}
if (!class_exists('WCMP_Razorpay_Checkout_Gateway') && WCMP_Razorpay_Checkout_Gateway_Dependencies::woocommerce_active_check()) {
    require_once( 'classes/class-wcmp-razorpay-checkout-gateway.php' );
    global $WCMP_Razorpay_Checkout_Gateway;
    $WCMP_Razorpay_Checkout_Gateway = new WCMP_Razorpay_Checkout_Gateway(__FILE__);
    $GLOBALS['WCMP_Razorpay_Checkout_Gateway'] = $WCMP_Razorpay_Checkout_Gateway;
}