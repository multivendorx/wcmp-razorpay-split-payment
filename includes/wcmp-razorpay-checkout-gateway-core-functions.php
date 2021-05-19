<?php

if (!function_exists('woocommerce_inactive_notice')) {

    function woocommerce_inactive_notice() {
        ?>
        <div id="message" class="error">
            <p><?php printf(__('%sWCMp Razorpay Checkout Gateway is inactive.%s The %sWooCommerce plugin%s must be active for the WCMp Razorpay Checkout Gateway to work. Please %sinstall & activate WooCommerce%s', 'wcmp-razorpay-checkout-gateway'), '<strong>', '</strong>', '<a target="_blank" href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url('plugins.php') . '">', '&nbsp;&raquo;</a>'); ?></p>
        </div>
        <?php
    }
}

if (!function_exists('others_razorpay_plugin_inactive_notice')) {
	function others_razorpay_plugin_inactive_notice() {
        ?>
        <div id="message" class="error">
            <p><?php printf(__('%sWCMp Razorpay Checkout Gateway is inactive. %s Please deactivate others razorpay plugin to bypass conflict', 'wcmp-razorpay-checkout-gateway'), '<strong>', '</strong>'); ?></p>
        </div>
        <?php
    }
}