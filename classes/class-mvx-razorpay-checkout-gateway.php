<?php

class MVX_Razorpay_Checkout_Gateway {
    public $plugin_url;
    public $plugin_path;
    public $version;
    public $token;
    public $text_domain;
    private $file;
    public $license;
    public $connect_razorpay;
    public $razorpay_admin;

    public function __construct($file) {
        $this->file = $file;
        $this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
        $this->plugin_path = trailingslashit(dirname($file));
        $this->token = MVX_RAZORPAY_CHECKOUT_GATEWAY_PLUGIN_TOKEN;
        $this->text_domain = MVX_RAZORPAY_CHECKOUT_GATEWAY_TEXT_DOMAIN;
        $this->version = MVX_RAZORPAY_CHECKOUT_GATEWAY_PLUGIN_VERSION;

        require_once $this->plugin_path . 'classes/class-mvx-razorpay-checkout-payment.php';        
        add_action('init', array(&$this, 'init'), 0);
        add_filter('mvx_multi_tab_array_list', array($this, 'mvx_multi_tab_array_list_for_razorpay'));
        add_filter('mvx_settings_fields_details', array($this, 'mvx_settings_fields_details_for_razorpay'));
    }

    public function mvx_multi_tab_array_list_for_razorpay($tab_link) {
        $tab_link['marketplace-payments'][] = array(
                'tablabel'      =>  __('Razorpay', 'mvx-razorpay-checkout-gateway'),
                'apiurl'        =>  'mvx_module/v1/save_dashpages',
                'description'   =>  __('Connect to vendors stripe account and make hassle-free transfers as scheduled.', 'mvx-razorpay-checkout-gateway'),
                'icon'          =>  'icon-tab-stripe-connect',
                'submenu'       =>  'payment',
                'modulename'     =>  'payment-razorpay'
            );
        return $tab_link;
    }

    public function mvx_settings_fields_details_for_razorpay($settings_fileds) {
        $settings_fileds_report = [
            [
                'key'       => 'key_id',
                'type'      => 'text',
                'label'     => __('Key ID', 'mvx-pro'),
                'placeholder'   => __('Key ID', 'mvx-pro'),
                'database_value' => '',
            ],
            [
                'key'       => 'key_secret',
                'type'      => 'text',
                'label'     => __('Key Secret', 'mvx-pro'),
                'placeholder'   => __('Key Secret', 'mvx-pro'),
                'database_value' => '',
            ],
            [
                'key'    => 'is_split',
                'label'   => __( "Enable Split Payment", 'mvx-pro' ),
                'class'     => 'mvx-toggle-checkbox',
                'type'    => 'checkbox',
                'options' => array(
                    array(
                        'key'=> "is_split",
                        'label'=> '',
                        'value'=> "is_split"
                    ),
                ),
                'database_value' => array(),
            ],
        ];
        $settings_fileds['payment-razorpay'] = $settings_fileds_report;
        return $settings_fileds;
    }

    /**
     * initilize plugin on WP init
     */
    function init() {
        // Init Text Domain
        $this->load_plugin_textdomain();

        if (class_exists('MVX')) {
            require_once $this->plugin_path . 'classes/class-mvx-gateway-razor-pay.php';
            $this->connect_razorpay = new MVX_Gateway_RazorPay();

            require_once $this->plugin_path . 'classes/class-mvx-razorpay-checkout-gateway-admin.php';
            $this->razorpay_admin = new MVX_Razorpay_Checkout_Gateway_Admin();

            add_filter('mvx_payment_gateways', array(&$this, 'add_mvx_razorpay_payment_gateway'));
        }
    }

    public function add_mvx_razorpay_payment_gateway($load_gateways) {
        $load_gateways[] = 'MVX_Gateway_RazorPay';
        return $load_gateways;
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present
     *
     * @access public
     * @return void
     */
    public function load_plugin_textdomain() {
        $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
        $locale = apply_filters('plugin_locale', $locale, 'mvx-razorpay-checkout-gateway');
        load_textdomain('mvx-razorpay-checkout-gateway', WP_LANG_DIR . '/mvx-razorpay-checkout-gateway/mvx-razorpay-checkout-gateway-' . $locale . '.mo');
        load_plugin_textdomain('mvx-razorpay-checkout-gateway', false, plugin_basename(dirname(dirname(__FILE__))) . '/languages');
    }
}