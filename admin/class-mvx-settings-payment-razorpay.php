<?php

class MVX_Settings_Payment_Razorpay {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;
    private $subsection;
    private $key_id = '';
    private $key_secret = '';

    /**
     * Start up
     */
    public function __construct($tab, $subsection) {
        $this->tab = $tab;
        $this->subsection = $subsection;
        $this->options = get_option("mvx_{$this->tab}_{$this->subsection}_settings_name");
        $this->settings_page_init();
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
        global $MVX;
        $settings_tab_options = array("tab" => "{$this->tab}",
            "ref" => &$this,
            "subsection" => "{$this->subsection}",
            "sections" => array(
                "mvx_payment_razorpay_payout_settings_section" => array("title" => __('Razorpay setting', 'mvx-razorpay-checkout-gateway'),
                    "fields" => array(
                        "key_id" => array(
                            'title' => __('Key ID', 'mvx-razorpay-checkout-gateway'),
                            'type' => 'text',
                            'id' => 'key_id',
                            'label_for' => 'key_id',
                            'name' => 'key_id',
                            'dfvalue' => $this->key_id
                        ),
                        "key_secret" => array(
                            'title' => __('Key Secret', 'mvx-razorpay-checkout-gateway'),
                            'type' => 'text',
                            'id' => 'key_secret',
                            'label_for' => 'key_secret',
                            'name' => 'key_secret',
                            'dfvalue' => $this->key_secret
                        ),
                        "is_split" => array('title' => __('Enable Split Payment', 'mvx-razorpay-checkout-gateway'), 'type' => 'checkbox', 'id' => 'is_split', 'label_for' => 'is_split', 'name' => 'is_split', 'value' => 'Enable'), // Checkbox
                    ),
                )
            ),
        );

        $MVX->admin->settings->settings_field_withsubtab_init(
            apply_filters("settings_{$this->tab}_{$this->subsection}_tab_options", $settings_tab_options)
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function mvx_payment_razorpay_payout_settings_sanitize($input) {
        $new_input = array();
        $hasError = false;
        if (isset($input['key_id'])) {
            $new_input['key_id'] = sanitize_text_field($input['key_id']);
        }
        if (isset($input['key_secret'])) {
            $new_input['key_secret'] = sanitize_text_field($input['key_secret']);
        }
        if (isset($input['is_split'])) {
            $new_input['is_split'] = sanitize_text_field($input['is_split']);
        }
        if (!$hasError) {
            add_settings_error(
                    "mvx_{$this->tab}_{$this->subsection}_settings_name",
                    esc_attr("mvx_{$this->tab}_{$this->subsection}_settings_admin_updated"),
                    __('Razorpay Payout Settings Updated', 'mvx-razorpay-checkout-gateway'),
                    'updated'
            );
        }
        return apply_filters("settings_{$this->tab}_{$this->subsection}_tab_new_input", $new_input, $input);
    }
}