<?php

class WCMP_Razorpay_Checkout_Gateway_Admin {

    public function __construct() {
        add_filter( 'automatic_payment_method', array( $this, 'admin_razorpay_payment_mode'), 20);
        add_filter( 'wcmp_vendor_payment_mode', array( $this, 'vendor_razorpay_payment_mode' ), 20);
        add_filter("settings_vendors_payment_tab_options", array( $this, 'wcmp_setting_razorpay_account_id' ), 90, 2 );
        add_action( 'settings_page_payment_razorpay_tab_init', array( &$this, 'payment_razorpay_init' ), 10, 2 );
        add_filter('wcmp_tabsection_payment', array( $this, 'wcmp_tabsection_payment_razorpay' ) );
        add_filter('wcmp_vendor_user_fields', array( $this, 'wcmp_vendor_user_fields_for_razorpay' ), 10, 2 );
        add_action('wcmp_after_vendor_billing', array($this, 'wcmp_after_vendor_billing_for_razorpay'));
    }

    public function wcmp_after_vendor_billing_for_razorpay() {
        global $WCMp;
        $user_array = $WCMp->user->get_vendor_fields( get_current_user_id() );
        ?>
        <div class="payment-gateway payment-gateway-razorpay <?php echo apply_filters('wcmp_vendor_paypal_email_container_class', ''); ?>">
            <div class="form-group">
                <label for="vendor_razorpay_account_id" class="control-label col-sm-3 col-md-3"><?php esc_html_e('Razorpay Account Id', 'wcmp-razorpay-checkout-gateway'); ?></label>
                <div class="col-md-6 col-sm-9">
                    <input id="vendor_razorpay_account_id" class="form-control" type="text" name="vendor_razorpay_account_id" value="<?php echo isset($user_array['vendor_razorpay_account_id']['value']) ? $user_array['vendor_razorpay_account_id']['value'] : ''; ?>"  placeholder="<?php esc_attr_e('Razorpay Account Id', 'wcmp-razorpay-checkout-gateway'); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    public function wcmp_vendor_user_fields_for_razorpay($fields, $vendor_id) {
        $vendor = get_wcmp_vendor($vendor_id);
        $fields["vendor_razorpay_account_id"] = array(
            'label' => __('Razorpay Route Account Id', 'wcmp-razorpay-checkout-gateway'),
            'type' => 'text',
            'value' => $vendor->razorpay_account_id,
            'class' => "user-profile-fields regular-text"
        );
        return $fields;
    }

    public function admin_razorpay_payment_mode( $arg ) {
        unset($arg['razorpay_block']);
        $admin_payment_mode_select = array_merge( $arg, array( 'razorpay' => __('Razorpay', 'wcmp-razorpay-checkout-gateway') ) );
        return $admin_payment_mode_select;
    }

    public function vendor_razorpay_payment_mode($payment_mode) {
        $payment_admin_settings = get_option('wcmp_payment_settings_name');

        if (isset($payment_admin_settings['payment_method_razorpay']) && $payment_admin_settings['payment_method_razorpay'] = 'Enable') {
            $payment_mode['razorpay'] = __('Razorpay', 'wcmp-razorpay-checkout-gateway');
        }
        return $payment_mode;
    }

    public function wcmp_setting_razorpay_account_id( $payment_tab_options, $vendor_obj ) {
        $payment_tab_options['vendor_razorpay_account_id'] = array('label' => __('Account Number', 'wcmp-razorpay-checkout-gateway'), 'type' => 'text', 'id' => 'vendor_razorpay_account_id', 'label_for' => 'vendor_razorpay_account_id', 'name' => 'vendor_razorpay_account_id', 'value' => $vendor_obj->razorpay_account_id, 'wrapper_class' => 'payment-gateway-razorpay payment-gateway');
        return $payment_tab_options;
    }

    public function payment_razorpay_init( $tab, $subsection ) {
        global $WCMP_Razorpay_Checkout_Gateway;
        require_once $WCMP_Razorpay_Checkout_Gateway->plugin_path . 'admin/class-wcmp-settings-payment-razorpay.php';
        new WCMp_Settings_Payment_Razorpay( $tab, $subsection );
    }

    public function wcmp_tabsection_payment_razorpay($tabsection_payment) {
        if ( 'Enable' === get_wcmp_vendor_settings( 'payment_method_razorpay', 'payment' ) ) {
            $tabsection_payment['razorpay'] = array( 'title' => __( 'Razorpay', 'wcmp-razorpay-checkout-gateway' ), 'icon' => 'dashicons-admin-settings' );
        }
        return $tabsection_payment;
    }
}