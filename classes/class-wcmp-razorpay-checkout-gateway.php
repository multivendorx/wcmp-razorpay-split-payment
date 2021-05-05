<?php

class WCMP_Razorpay_Checkout_Gateway {
    public $plugin_url;
    public $plugin_path;
    public $version;
    public $token;
    public $text_domain;
    private $file;
    public $license;

    public function __construct($file) {
        $this->file = $file;
        $this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
        $this->plugin_path = trailingslashit(dirname($file));
        $this->token = WCMP_RAZORPAY_CHECKOUT_GATEWAY_PLUGIN_TOKEN;
        $this->text_domain = WCMP_RAZORPAY_CHECKOUT_GATEWAY_TEXT_DOMAIN;
        $this->version = WCMP_RAZORPAY_CHECKOUT_GATEWAY_PLUGIN_VERSION;

        require_once $this->plugin_path . 'classes/class-wcmp-vendor-razorpay-license.php';        
        $this->license =  new WCMP_Vendor_Razorpay_License( $this->file, $this->plugin_path, WCMP_VENDOR_RAZORPAY_PLUGIN_PRODUCT_ID, $this->version, 'plugin', WCMP_VENDOR_RAZORPAY_PLUGIN_SERVER_URL, WCMP_VENDOR_RAZORPAY_PLUGIN_SOFTWARE_TITLE, $this->text_domain  );
        require_once $this->plugin_path . 'classes/class-wcmp-razorpay-checkout-payment.php';        
        add_action('init', array(&$this, 'init'), 0);
    }

    /**
     * initilize plugin on WP init
     */
    function init() {
        // Init Text Domain
        $this->load_plugin_textdomain();
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
        $locale = apply_filters('plugin_locale', $locale, 'wcmp-razorpay-checkout-gateway');
        load_textdomain('wcmp-razorpay-checkout-gateway', WP_LANG_DIR . '/wcmp-razorpay-checkout-gateway/wcmp-razorpay-checkout-gateway-' . $locale . '.mo');
        load_plugin_textdomain('wcmp-razorpay-checkout-gateway', false, plugin_basename(dirname(dirname(__FILE__))) . '/languages');
    }
}
