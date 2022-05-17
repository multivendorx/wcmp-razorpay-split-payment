<?php

class MVX_Razorpay_Checkout_Gateway_Admin {

    public function __construct() {
        add_filter( 'automatic_payment_method', array( $this, 'admin_razorpay_payment_mode'), 20);
        add_filter( 'mvx_vendor_payment_mode', array( $this, 'vendor_razorpay_payment_mode' ), 20);
        add_filter('mvx_vendor_user_fields', array( $this, 'mvx_vendor_user_fields_for_razorpay' ), 10, 2 );
        add_action('mvx_after_vendor_billing', array($this, 'mvx_after_vendor_billing_for_razorpay'));
        // mvx settings
        add_filter('mvx_multi_tab_array_list', array($this, 'mvx_multi_tab_array_list_for_razorpay'));
        add_filter('mvx_settings_fields_details', array($this, 'mvx_settings_fields_details_for_razorpay'));
        add_filter('mvx_payment_method_disbursement_options', array($this, 'mvx_payment_method_disbursement_options_for_razorpay'));
        
    }

    public function mvx_after_vendor_billing_for_razorpay() {
        global $MVX;
        $user_array = $MVX->user->get_vendor_fields( get_current_user_id() );
        ?>
        <div class="payment-gateway payment-gateway-razorpay <?php echo apply_filters('mvx_vendor_paypal_email_container_class', ''); ?>">
            <div class="form-group">
                <label for="vendor_razorpay_account_id" class="control-label col-sm-3 col-md-3"><?php esc_html_e('Razorpay Account Id', 'mvx-razorpay-checkout-gateway'); ?></label>
                <div class="col-md-6 col-sm-9">
                    <input id="vendor_razorpay_account_id" class="form-control" type="text" name="vendor_razorpay_account_id" value="<?php echo isset($user_array['vendor_razorpay_account_id']['value']) ? $user_array['vendor_razorpay_account_id']['value'] : ''; ?>"  placeholder="<?php esc_attr_e('Razorpay Account Id', 'mvx-razorpay-checkout-gateway'); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    public function mvx_vendor_user_fields_for_razorpay($fields, $vendor_id) {
        $vendor = get_mvx_vendor($vendor_id);
        $fields["vendor_razorpay_account_id"] = array(
            'label' => __('Razorpay Route Account Id', 'mvx-razorpay-checkout-gateway'),
            'type' => 'text',
            'value' => $vendor->razorpay_account_id,
            'class' => "user-profile-fields regular-text"
        );
        return $fields;
    }

    public function admin_razorpay_payment_mode( $arg ) {
        unset($arg['razorpay_block']);
        $admin_payment_mode_select = array_merge( $arg, array( 'razorpay' => __('Razorpay', 'mvx-razorpay-checkout-gateway') ) );
        return $admin_payment_mode_select;
    }

    public function vendor_razorpay_payment_mode($payment_mode) {
        $payment_admin_settings = get_option('mvx_payment_settings_name');

        if (isset($payment_admin_settings['payment_method_razorpay']) && $payment_admin_settings['payment_method_razorpay'] = 'Enable') {
            $payment_mode['razorpay'] = __('Razorpay', 'mvx-razorpay-checkout-gateway');
        }
        return $payment_mode;
    }

    // mvx work
    public function mvx_multi_tab_array_list_for_razorpay($tab_link) {
        if (mvx_is_module_active('razorpay')) {
            $tab_link['marketplace-payments'][] = array(
                'tablabel'      =>  __('Razorpay', 'mvx-razorpay-checkout-gateway'),
                'apiurl'        =>  'mvx_module/v1/save_dashpages',
                'description'   =>  __('Razorpay makes it easy for you to pay multiple sellers at the sametime', 'mvx-razorpay-checkout-gateway'),
                'icon'          =>  'module-razorpay',
                'submenu'       =>  'payment',
                'modulename'    =>  'payment-razorpay'
            );
        }
        return $tab_link;
    }
    
    public function mvx_settings_fields_details_for_razorpay($settings_fileds) {
        $settings_fileds['payment-razorpay'] = [
            [
                'key'       => 'key_id',
                'type'      => 'text',
                'label'     => __( 'Key ID', 'mvx-razorpay-checkout-gateway' ),
                'database_value' => '',
            ],
            [
                'key'       => 'key_secret',
                'type'      => 'text',
                'label'     => __( 'Key Secret', 'mvx-razorpay-checkout-gateway' ),
                'database_value' => '',
            ],
            [
                'key'    => 'is_split',
                'label'   => __( 'Enable Split Payment', 'mvx-razorpay-checkout-gateway' ),
                'type'    => 'checkbox',
                'class'     => 'mvx-toggle-checkbox',
                'options' => array(
                    array(
                        'key'=> "is_split",
                        'label'=> __('', 'mvx-razorpay-checkout-gateway'),
                        'value'=> "is_split"
                    )
                ),
                'database_value' => array(),
            ],
        ];
        return $settings_fileds;
    }

    public function mvx_payment_method_disbursement_options_for_razorpay($disburse_methods) {
        if (mvx_is_module_active('razorpay')) {
            $disburse_methods[] = array(
                'key'=> "razorpay_connect",
                'label'=> __('Razorpay', 'dc-woocommerce-multi-vendor'),
                'value'=> "razorpay_connect"
            );
        }
        return $disburse_methods;
    }
}