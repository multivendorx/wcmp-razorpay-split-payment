<?php
/**
 * Plugin Name: MVX Razorpay Split Payment
 * Plugin URI: https://wc-marketplace.com/addons/
 * Description: MVX Razorpay Split Checkout Gateway is a payment gateway for pay with woocommerce as well as split payment with MVX multivendor marketplace.
 * Author: WC Marketplace
 * Version: 1.0.1
 * Author URI: https://wc-marketplace.com/
 * Text Domain: mvx-razorpay-checkout-gateway
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

if (!class_exists('MVX_Razorpay_Checkout_Gateway_Dependencies')) {
    require_once 'classes/class-mvx-razorpay-checkout-gateway-dependencies.php';
}
require_once 'includes/mvx-razorpay-checkout-gateway-core-functions.php';
require_once 'mvx-razorpay-checkout-gateway-config.php';

if (!defined('MVX_RAZORPAY_CHECKOUT_GATEWAY_PLUGIN_TOKEN')) {
    exit;
}
if (!defined('MVX_RAZORPAY_CHECKOUT_GATEWAY_TEXT_DOMAIN')) {
    exit;
}

if(!MVX_Razorpay_Checkout_Gateway_Dependencies::woocommerce_active_check()){
    add_action('admin_notices', 'woocommerce_inactive_notice');
}

if(MVX_Razorpay_Checkout_Gateway_Dependencies::others_razorpay_plugin_active_check()){
    add_action('admin_notices', 'others_razorpay_plugin_inactive_notice');
}

if (!class_exists('MVX_Razorpay_Checkout_Gateway') && MVX_Razorpay_Checkout_Gateway_Dependencies::woocommerce_active_check() && !MVX_Razorpay_Checkout_Gateway_Dependencies::others_razorpay_plugin_active_check()) {
    require_once( 'classes/class-mvx-razorpay-checkout-gateway.php' );
    global $MVX_Razorpay_Checkout_Gateway;
    $MVX_Razorpay_Checkout_Gateway = new MVX_Razorpay_Checkout_Gateway(__FILE__);
    $GLOBALS['MVX_Razorpay_Checkout_Gateway'] = $MVX_Razorpay_Checkout_Gateway;
}
