<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCMP_Vendor_Razorpay_License' ) ) {
    class WCMP_Vendor_Razorpay_License {

        /**
         * Class args
         *
         * @var string
         */
        public $api_url          = '';
        public $data_key         = '';
        public $file             = '';
        public $plugin_name      = '';
        public $plugin_path      = '';
        public $plugin_or_theme  = '';
        public $product_id       = '';
        public $slug             = '';
        public $software_title   = '';
        public $software_version = '';
        public $text_domain      = ''; // For language translation.
        public $tabs_list        = array();

        /**
         * Class properties.
         *
         * @var string
         */
        public $data                              = array();
        public $wc_am_activated_key               = '';
        public $wc_am_activation_tab_key          = '';
        public $wc_am_api_key_key                 = '';
        public $wc_am_api_key_product_id          = '';
        public $wc_am_deactivate_checkbox_key     = '';
        public $wc_am_deactivation_tab_key        = '';
        public $wc_am_domain                      = '';
        public $wc_am_instance_id                 = '';
        public $wc_am_instance_key                = '';
        public $wc_am_licence_menu_slug           = '';
        public $wc_am_menu_tab_activation_title   = '';
        public $wc_am_menu_tab_deactivation_title = '';
        public $wc_am_plugin_name                 = '';
        public $wc_am_renew_license_url           = '';
        public $wc_am_settings_menu_title         = '';
        public $wc_am_settings_title              = '';
        public $wc_am_software_version            = '';
        
        public function __construct( $file, $plugin_path, $product_id, $software_version, $plugin_or_theme, $api_url, $software_title = '', $text_domain = '' ) {
            /**
             * Preserve the value of $product_id to use for API requests. Pre 2.0 product_id is a string, and >= 2.0 is an integer.
             */
            if ( is_int( $product_id ) ) {
                $this->product_id = absint( $product_id );
            } else {
                $this->product_id = esc_attr( $product_id );
            }

            $this->file             = $file;
            $this->plugin_path      = $plugin_path;
            $this->software_title   = esc_attr( $software_title );
            $this->software_version = esc_attr( $software_version );
            $this->plugin_or_theme  = esc_attr( $plugin_or_theme );
            $this->api_url          = esc_url( $api_url );
            $this->text_domain      = esc_attr( $text_domain );
            /**
             * If the product_id is a pre 2.0 string, format it to be used as an option key, otherwise it will be an integer if >= 2.0.
             */
            $this->data_key            = 'wc_am_client_' . strtolower( str_ireplace( array( ' ', '_', '&', '?' ), '_', $this->product_id ) );
            $this->wc_am_activated_key = $this->data_key . '_activated';

            if ( is_admin() ) {
                if ( ! empty( $this->plugin_or_theme ) && $this->plugin_or_theme == 'theme' ) {
                    add_action( 'admin_init', array( $this, 'activation' ) );
                }

                if ( ! empty( $this->plugin_or_theme ) && $this->plugin_or_theme == 'plugin' ) {
                    register_activation_hook( $this->file, array( $this, 'activation' ) );
                    register_activation_hook( $this->file, 'flush_rewrite_rules' );
                    update_option('wcmp_vendor_razorpay_installed', 1);
                }

                add_action( 'admin_init', array( $this, 'load_settings' ) );
                // Check for external connection blocking
                add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );
                
                add_filter( 'wcmp_enable_admin_script_screen_ids', array( $this, 'add_css_for_licence' ) );
                add_filter( 'wcmp_license_tabs_list', array( $this, 'add_tab_item_for_licence' ) );
                
                /**
                 * Set all data defaults here
                 */
                $this->wc_am_api_key_key  = $this->data_key . '_api_key';
                $this->wc_am_api_key_product_id  = $this->data_key . '_product_id';
                $this->wc_am_instance_key = $this->data_key . '_instance';

                /**
                 * Set all admin menu data
                 */
                $this->wc_am_deactivate_checkbox_key     = $this->data_key . '_deactivate_checkbox';
                $this->wc_am_activation_tab_key          = $this->data_key . '_dashboard';
                $this->wc_am_deactivation_tab_key        = $this->data_key . '_deactivation';
                $this->wc_am_licence_activation_slug     = $this->data_key . '_activation';
                $this->wc_am_licence_deactivation_slug   = $this->data_key . '_deactivation';
                $this->wc_am_settings_menu_title         = $this->software_title . esc_html__( ' Activation', 'wcmp-razorpay-checkout-gateway' );
                $this->wc_am_settings_title              = $this->software_title . esc_html__( ' API Key Activation', 'wcmp-razorpay-checkout-gateway' );
                $this->wc_am_licence_menu_slug           = 'wcmp-license-admin';
                $this->wc_am_menu_tab_activation_title   = esc_html__( 'Activation', 'wcmp-razorpay-checkout-gateway' );
                $this->wc_am_menu_tab_deactivation_title = esc_html__( 'Deactivation', 'wcmp-razorpay-checkout-gateway' );

                /**
                 * Set all software update data here
                 */
                $this->data                    = get_option( $this->data_key );
                if(isset($this->data[ $this->wc_am_api_key_product_id ])) $this->product_id = $this->data[ $this->wc_am_api_key_product_id ];
                $this->wc_am_plugin_name       = $this->plugin_or_theme == 'plugin' ? untrailingslashit( plugin_basename( $this->file ) ) : get_stylesheet(); // same as plugin slug. if a theme use a theme name like 'twentyeleven'
                $this->wc_am_renew_license_url = $this->api_url . 'my-account'; // URL to renew an API Key. Trailing slash in the upgrade_url is required.
                $this->wc_am_instance_id       = get_option( $this->wc_am_instance_key ); // Instance ID (unique to each blog activation)
                /**
                 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
                 * so only the host portion of the URL can be sent. For example the host portion might be
                 * www.example.com or example.com. http://www.example.com includes the scheme http,
                 * and the host www.example.com.
                 * Sending only the host also eliminates issues when a client site changes from http to https,
                 * but their activation still uses the original scheme.
                 * To send only the host, use a line like the one below:
                 *
                 * $this->wc_am_domain = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
                 */
                $this->wc_am_domain           = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
                $this->wc_am_software_version = $this->software_version; // The software version

                // Check if data has been migrated from pre-2.0.
                $this->migrate_pre_2_0_data( $this->product_id, $software_title );

                /**
                 * Check for software updates
                 */
                $this->check_for_update();

                // Load WCMp Licence Admin Class
                if(!class_exists('WCMp_License_Admin')) {
                    require_once ( $this->plugin_path . 'classes/class-wcmp-license-admin.php' );
                    new WCMp_License_Admin( $file, $product_id, $software_version, $plugin_or_theme, $api_url, $software_title = '', $text_domain = '' );
                }

                if ( ! empty( $this->wc_am_activated_key ) && get_option( $this->wc_am_activated_key ) != 'Activated' ) {
                    add_action( 'admin_notices', array( $this, 'inactive_notice' ) );
                }
            }

            /**
             * Deletes all data if plugin deactivated
             */
            if ( $this->plugin_or_theme == 'plugin' ) {
                register_deactivation_hook( $this->file, array( $this, 'uninstall' ) );
                delete_option('wcmp_vendor_razorpay_installed');
            }

            if ( $this->plugin_or_theme == 'theme' ) {
                add_action( 'switch_theme', array( $this, 'uninstall' ) );
            }
        }

        /**
         * Migrates pre 2.0 data to prevent breaking old software activations.
         *
         * @since 2.0
         *
         * @param int    $product_id
         * @param string $software_title
         */
        public function migrate_pre_2_0_data( $product_id, $software_title ) {
            $upraded_postfix = strtolower( str_ireplace( array(
                                                             ' ',
                                                             '_',
                                                             '&',
                                                             '?'
                                                         ), '_', $this->product_id ) );
            $upraded         = get_option( 'wc_client_20_ugrade_attempt_' . $upraded_postfix );

            if ( $upraded != 'yes' ) {
                $title        = is_int( $product_id ) ? strtolower( $software_title ) : strtolower( $product_id );
                $title        = str_ireplace( array( ' ', '_', '&', '?' ), '_', $title );
                $old_data_key = $title . '_data';
                $data         = get_option( $old_data_key );
                $instance     = get_option( $title . '_instance' );

                if ( ! empty( $data ) && ! empty( $instance ) ) {
                    $api_key = array(
                        $this->wc_am_api_key_key => $data[ 'api_key' ],
                    );

                    update_option( $this->data_key, $api_key );
                    update_option( $this->wc_am_instance_key, $instance );
                    ! empty( $instance ) ? update_option( $this->wc_am_deactivate_checkbox_key, 'off' ) : update_option( $this->wc_am_deactivate_checkbox_key, 'on' );
                    ! empty( $instance ) ? update_option( $this->wc_am_activated_key, 'Activated' ) : update_option( $this->wc_am_activated_key, 'Deactivated' );
                    // Success!
                    update_option( 'wc_client_20_ugrade_attempt_' . $upraded_postfix, 'yes' );
                } else {
                    if ( empty( $this->wc_am_instance_id ) ) {
                        // Failed migration. :( Cue the violins to play a sad song.
                        add_action( 'admin_notices', array( $this, 'migrate_error_notice' ) );
                    }
                }
            }
        }

        /**
         * Provides one-time instructions for customer to reactivate the API Key if the migration fails.
         *
         * @since 2.0
         */
        public function migrate_error_notice() { ?>
            <div class="notice notice-error">
                <p>
                    <?php esc_html_e( 'Attempt to migrate data failed. Deactivate then reactive this plugin or theme, then enter your API Key on the settings screen to receive software updates. Contact support if assistance is required.', 'wcmp-razorpay-checkout-gateway' ); ?>
                </p>
            </div>
            <?php
        }

        /**
         * Generate the default data arrays
         */
        public function activation() {
            if ( get_option( $this->data_key ) === false || get_option( $this->wc_am_instance_key ) === false ) {
                //$api_key = array(
                //  $this->wc_am_api_key_key => '',
                //);
                //
                //update_option( $this->data_key, $api_key );
                update_option( $this->wc_am_instance_key, wp_generate_password( 12, false ) );
                update_option( $this->wc_am_deactivate_checkbox_key, 'on' );
                update_option( $this->wc_am_activated_key, 'Deactivated' );
            }
            do_action('wc_am_after_plugin_activation');
        }

        /**
         * Deletes all data if plugin deactivated
         */
        public function uninstall() {
            global $blog_id;

            $this->license_key_deactivation();

            // Remove options pre API Manager 2.0
            if ( is_multisite() ) {
                switch_to_blog( $blog_id );

                foreach (
                    array(
                        //$this->data_key,
                        $this->wc_am_instance_key,
                        $this->wc_am_deactivate_checkbox_key,
                        $this->wc_am_activated_key,
                    ) as $option
                ) {

                    delete_option( $option );
                }

                restore_current_blog();
            } else {
                foreach (
                    array(
                        //$this->data_key,
                        $this->wc_am_instance_key,
                        $this->wc_am_deactivate_checkbox_key,
                        $this->wc_am_activated_key
                    ) as $option
                ) {

                    delete_option( $option );
                }
            }
            
            do_action('wc_am_after_plugin_deactivation');
        }

        /**
         * Deactivates the license on the API server
         */
        public function license_key_deactivation() {
            $activation_status = get_option( $this->wc_am_activated_key );
            $api_key           = $this->data[ $this->wc_am_api_key_key ];

            $args = array(
                'api_key' => $api_key,
            );

            if ( $activation_status == 'Activated' && $api_key != '' ) {
                $this->deactivate( $args ); // reset API Key activation
            }
        }

        /**
         * Displays an inactive notice when the software is inactive.
         */
        public function inactive_notice() { ?>
            <?php if ( ! current_user_can( 'manage_options' ) ) {
                return;
            } ?>
            <?php if ( isset( $_GET[ 'page' ] ) && $this->wc_am_activation_tab_key == $_GET[ 'page' ] ) {
                return;
            } ?>
            <div class="notice notice-error">
                <p><?php printf( __( 'The <strong>%s</strong> API Key has not been activated, so the %s is inactive! %sClick here%s to activate <strong>%s</strong>.', 'wcmp-razorpay-checkout-gateway' ), esc_attr( $this->software_title ), esc_attr( $this->plugin_or_theme ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . $this->wc_am_licence_menu_slug . '&tab=' . $this->wc_am_activation_tab_key ) ) . '">', '</a>', esc_attr( $this->software_title ) ); ?></p>
            </div>
            <?php
        }

        /**
         * Check for external blocking contstant.
         */
        public function check_external_blocking() {
            // show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant
            if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {
                // check if our API endpoint is in the allowed hosts
                $host = parse_url( $this->api_url, PHP_URL_HOST );

                if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
                    ?>
                    <div class="notice notice-error">
                        <p><?php printf( __( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %s updates. Please add %s to %s.', 'wcmp-razorpay-checkout-gateway' ), $this->software_title, '<strong>' . $host . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>' ); ?></p>
                    </div>
                    <?php
                }
            }
        }
        
        public function add_css_for_licence( $screen_ids ) {
            $screen_ids[] = 'wcmp_page_wcmp-license-admin';
            
            return $screen_ids;
        }
        
        public function add_tab_item_for_licence( $tabs_list ) {
            $tabs_list[$this->wc_am_activation_tab_key] = $this->software_title;
            return $tabs_list;
        }

        // Register settings
        public function load_settings() {
            register_setting( $this->data_key, $this->data_key, array( $this, 'validate_options' ) );
            // API Key
            add_settings_section( $this->wc_am_api_key_key, esc_html__( 'API Key Activation', 'wcmp-razorpay-checkout-gateway' ), array(
                $this,
                'wc_am_api_key_text'
            ), $this->wc_am_activation_tab_key );
            add_settings_field( $this->wc_am_api_key_key, esc_html__( 'API Key', 'wcmp-razorpay-checkout-gateway' ), array(
                $this,
                'wc_am_api_key_field'
            ), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );
            add_settings_field( 'product_id', esc_html__( 'Product ID', 'wcmp-razorpay-checkout-gateway' ), array(
                $this,
                'wc_am_api_key_product_id'
            ), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );
            add_settings_field( 'status', esc_html__( 'API Key Status', 'wcmp-razorpay-checkout-gateway' ), array(
                $this,
                'wc_am_api_key_status'
            ), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );
            // Activation settings
            register_setting( $this->wc_am_deactivate_checkbox_key, $this->wc_am_deactivate_checkbox_key, array(
                $this,
                'wc_am_license_key_deactivation'
            ) );
            add_settings_section( 'deactivate_button', esc_html__( 'API Key Deactivation', 'wcmp-razorpay-checkout-gateway' ), array(
                $this,
                'wc_am_deactivate_text'
            ), $this->wc_am_deactivation_tab_key );
            add_settings_field( 'deactivate_button', esc_html__( 'Deactivate API Key', 'wcmp-razorpay-checkout-gateway' ), array(
                $this,
                'wc_am_deactivate_textarea'
            ), $this->wc_am_deactivation_tab_key, 'deactivate_button' );
        }

        // Provides text for api key section
        public function wc_am_api_key_text() { }

        // Returns the API Key status from the WooCommerce API Manager on the server
        public function wc_am_api_key_status() {
            $license_status       = $this->license_key_status();
            $license_status_check = ( ! empty( $license_status[ 'status_check' ] ) && $license_status[ 'status_check' ] == 'active' ) ? esc_html__( 'Activated', 'wcmp-razorpay-checkout-gateway' ) : esc_html__( 'Deactivated', 'wcmp-razorpay-checkout-gateway' );
            if ( ! empty( $license_status_check ) ) {
                if ( $license_status == 'Activated' ) {
                    update_option( $this->wc_am_activated_key, 'Activated' );
                    update_option( $this->wc_am_deactivate_checkbox_key, 'off' );
                }

                echo esc_attr( $license_status_check );
            }
        }

        // Returns true if the API Key status is Activated in the database.
        public function get_api_key_status() {
            return get_option( $this->wc_am_activated_key ) == 'Activated';
        }

        // Returns API Key text field
        public function wc_am_api_key_field() {
            if ( ! empty( $this->data[ $this->wc_am_api_key_key ] ) ) {
                echo "<input id='api_key' name='" . esc_attr( $this->data_key ) . "[" . esc_attr( $this->wc_am_api_key_key ) . "]' size='25' type='text' value='" . esc_attr( $this->data[ $this->wc_am_api_key_key ] ) . "' />";
                //if ( $this->data[ $this->wc_am_api_key_key ] ) {
                //  echo "<span class='dashicons dashicons-yes' style='color: #66ab03;'></span>";
                //} else {
                //  echo "<span class='dashicons dashicons-no' style='color: #ca336c;'></span>";
                //}
            } else {
                echo "<input id='api_key' name='" . esc_attr( $this->data_key ) . "[" . esc_attr( $this->wc_am_api_key_key ) . "]' size='25' type='text' value='' />";
            }
        }
        
        // Returns product ID text field
        public function wc_am_api_key_product_id() {
            if ( ! empty( $this->data[ $this->wc_am_api_key_product_id ] ) ) {
                echo "<input id='product_id' name='" . esc_attr( $this->data_key ) . "[" . esc_attr( $this->wc_am_api_key_product_id ) . "]' size='25' type='text' value='" . esc_attr( $this->data[ $this->wc_am_api_key_product_id ] ) . "' />";
                //if ( $this->data[ $this->wc_am_api_key_key ] ) {
                //  echo "<span class='dashicons dashicons-yes' style='color: #66ab03;'></span>";
                //} else {
                //  echo "<span class='dashicons dashicons-no' style='color: #ca336c;'></span>";
                //}
            } else {
                echo "<input id='product_id' name='" . esc_attr( $this->data_key ) . "[" . esc_attr( $this->wc_am_api_key_product_id ) . "]' size='25' type='text' value='' />";
            }
        }
        
        /**
         * Sanitizes and validates all input and output for Dashboard
         *
         * @since 2.0
         *
         * @param $input
         *
         * @return mixed|string
         */
        public function validate_options( $input ) {
            // Load existing options, validate, and update with changes from input before returning
            $options                             = $this->data;
            $options[ $this->wc_am_api_key_key ] = trim( $input[ $this->wc_am_api_key_key ] );
            $options[ $this->wc_am_api_key_product_id ] = trim( $input[ $this->wc_am_api_key_product_id ] );
            $api_key                             = trim( $input[ $this->wc_am_api_key_key ] );
            $activation_status                   = get_option( $this->wc_am_activated_key );
            $checkbox_status                     = get_option( $this->wc_am_deactivate_checkbox_key );
            $current_api_key                     = $this->data[ $this->wc_am_api_key_key ];

            // Should match the settings_fields() value
            if ( $_REQUEST[ 'option_page' ] != $this->wc_am_deactivate_checkbox_key ) {
                if ( $activation_status == 'Deactivated' || $activation_status == '' || $api_key == '' || $checkbox_status == 'on' || $current_api_key != $api_key ) {
                    /**
                     * If this is a new key, and an existing key already exists in the database,
                     * deactivate the existing key before activating the new key.
                     */
                    if ( $current_api_key != $api_key ) {
                        $this->replace_license_key( $current_api_key );
                    }

                    $args = array(
                        'api_key' => $api_key,
                    );

                    $activate_results = json_decode( $this->activate( $args ), true );

                    if ( $activate_results[ 'success' ] === true && $activate_results[ 'activated' ] === true ) {
                        add_settings_error( 'activate_text', 'activate_msg', sprintf( __( '%s activated. ', 'wcmp-razorpay-checkout-gateway' ), esc_attr( $this->software_title ) ) . esc_attr( "{$activate_results['message']}." ), 'updated' );
                        update_option( $this->wc_am_activated_key, 'Activated' );
                        update_option( $this->wc_am_deactivate_checkbox_key, 'off' );
                    }

                    if ( $activate_results == false && ! empty( $this->data ) && ! empty( $this->wc_am_activated_key ) ) {
                        add_settings_error( 'api_key_check_text', 'api_key_check_error', esc_html__( 'Connection failed to the License Key API server. Try again later. There may be a problem on your server preventing outgoing requests, or the store is blocking your request to activate the plugin/theme.', 'wcmp-razorpay-checkout-gateway' ), 'error' );
                        update_option( $this->data[ $this->wc_am_activated_key ], 'Deactivated' );
                    }

                    if ( isset( $activate_results[ 'data' ][ 'error_code' ] ) && ! empty( $this->data ) && ! empty( $this->wc_am_activated_key ) ) {
                        add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$activate_results['data']['error']}" ), 'error' );
                        update_option( $this->data[ $this->wc_am_activated_key ], 'Deactivated' );
                    }
                } // End Plugin Activation
            }

            return $options;
        }

        // Deactivates the API Key to allow key to be used on another blog
        public function wc_am_license_key_deactivation( $input ) {
            $activation_status = get_option( $this->wc_am_activated_key );
            $options           = ( $input == 'on' ? 'on' : 'off' );

            $args = array(
                'api_key' => $this->data[ $this->wc_am_api_key_key ],
            );

            if ( $options == 'on' && $activation_status == 'Activated' && $this->data[ $this->wc_am_api_key_key ] != '' ) {
                // deactivates API Key key activation
                $activate_results = json_decode( $this->deactivate( $args ), true );

                if ( $activate_results[ 'success' ] === true && $activate_results[ 'deactivated' ] === true ) {
                    //$update = array(
                    //  $this->wc_am_api_key_key => '',
                    //);
                    //
                    //$merge_options = array_merge( $this->data, $update );

                    if ( ! empty( $this->wc_am_activated_key ) ) {
                        //update_option( $this->data_key, $merge_options );
                        update_option( $this->wc_am_activated_key, 'Deactivated' );
                        add_settings_error( 'wc_am_deactivate_text', 'deactivate_msg', esc_html__( 'API Key deactivated. ', 'wcmp-razorpay-checkout-gateway' ) . esc_attr( "{$activate_results['activations_remaining']}." ), 'updated' );
                    }

                    return $options;
                }

                if ( isset( $activate_results[ 'data' ][ 'error_code' ] ) && ! empty( $this->data ) && ! empty( $this->wc_am_activated_key ) ) {
                    add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$activate_results['data']['error']}" ), 'error' );
                    update_option( $this->data[ $this->wc_am_activated_key ], 'Deactivated' );
                }
            } else {

                return $options;
            }

            return false;
        }

        /**
         * Returns the API Key status from the WooCommerce API Manager on the server.
         *
         * @return array|mixed|object
         */
        public function license_key_status() {
            $args = array();

            if ( ! empty( $this->data[ $this->wc_am_api_key_key ] ) ) {
                $args = array(
                    'api_key' => $this->data[ $this->wc_am_api_key_key ],
                );
            }

            return json_decode( $this->status( $args ), true );
        }

        /**
         * Deactivate the current API Key before activating the new API Key
         *
         * @param string $current_api_key
         *
         * @return bool
         */
        public function replace_license_key( $current_api_key ) {
            $args = array(
                'api_key' => $current_api_key,
            );

            $reset = $this->deactivate( $args ); // reset API Key activation

            if ( $reset == true ) {
                return true;
            }

            add_settings_error( 'not_deactivated_text', 'not_deactivated_error', esc_html__( 'The API Key could not be deactivated. Use the API Key Deactivation tab to manually deactivate the API Key before activating a new API Key. If all else fails, go to Plugins, then deactivate and reactivate this plugin, or if a theme change themes, then change back to this theme, then go to the Settings for this plugin/theme and enter the API Key information again to activate it. Also check the My Account dashboard to see if the API Key for this site was still active before the error message was displayed.', 'wcmp-razorpay-checkout-gateway' ), 'updated' );

            return false;
        }

        public function wc_am_deactivate_text() { }

        public function wc_am_deactivate_textarea() {
            echo '<input type="checkbox" id="' . esc_attr( $this->wc_am_deactivate_checkbox_key ) . '" name="' . esc_attr( $this->wc_am_deactivate_checkbox_key ) . '" value="on"';
            echo checked( get_option( $this->wc_am_deactivate_checkbox_key ), 'on' );
            echo '/>';
            ?><span class="description"><?php esc_html_e( 'Deactivates an API Key so it can be used on another blog.', 'wcmp-razorpay-checkout-gateway' ); ?></span>
            <?php
        }

        /**
         * Builds the URL containing the API query string for activation, deactivation, and status requests.
         *
         * @param array $args
         *
         * @return string
         */
        public function create_software_api_url( $args ) {
            return add_query_arg( 'wc-api', 'wc-am-api', $this->api_url ) . '&' . http_build_query( $args );
        }

        /**
         * Sends the request to activate to the API Manager.
         *
         * @param array $args
         *
         * @return bool|string
         */
        public function activate( $args ) {
            $defaults = array(
                'request'          => 'activate',
                'product_id'       => $this->product_id,
                'instance'         => $this->wc_am_instance_id,
                'object'           => $this->wc_am_domain,
                'software_version' => $this->wc_am_software_version
            );

            $args       = wp_parse_args( $defaults, $args );
            $target_url = esc_url_raw( $this->create_software_api_url( $args ) );
            $request    = wp_safe_remote_post( $target_url );

            if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                // Request failed
                return false;
            }

            $response = wp_remote_retrieve_body( $request );

            return $response;
        }

        /**
         * Sends the request to deactivate to the API Manager.
         *
         * @param array $args
         *
         * @return bool|string
         */
        public function deactivate( $args ) {
            $defaults = array(
                'request'    => 'deactivate',
                'product_id' => $this->product_id,
                'instance'   => $this->wc_am_instance_id,
                'object'     => $this->wc_am_domain
            );

            $args       = wp_parse_args( $defaults, $args );
            $target_url = esc_url_raw( $this->create_software_api_url( $args ) );
            $request    = wp_safe_remote_post( $target_url );

            if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                // Request failed
                return false;
            }

            $response = wp_remote_retrieve_body( $request );

            return $response;
        }

        /**
         * Sends the status check request to the API Manager.
         *
         * @param array $args
         *
         * @return bool|string
         */
        public function status( $args ) {
            $defaults = array(
                'request'    => 'status',
                'product_id' => $this->product_id,
                'instance'   => $this->wc_am_instance_id,
                'object'     => $this->wc_am_domain
            );

            $args       = wp_parse_args( $defaults, $args );
            $target_url = esc_url_raw( $this->create_software_api_url( $args ) );
            $request    = wp_safe_remote_post( $target_url );

            if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                // Request failed
                return false;
            }

            $response = wp_remote_retrieve_body( $request );

            return $response;
        }

        /**
         * Check for software updates.
         */
        public function check_for_update() {
            $this->plugin_name = $this->wc_am_plugin_name;

            // Slug should be the same as the plugin/theme directory name
            if ( strpos( $this->plugin_name, '.php' ) !== 0 ) {
                $this->slug = dirname( $this->plugin_name );
            } else {
                $this->slug = $this->plugin_name;
            }

            /*********************************************************************
             * The plugin and theme filters should not be active at the same time
             *********************************************************************/
            /**
             * More info:
             * function set_site_transient moved from wp-includes/functions.php
             * to wp-includes/option.php in WordPress 3.4
             *
             * set_site_transient() contains the pre_set_site_transient_{$transient} filter
             * {$transient} is either update_plugins or update_themes
             *
             * Transient data for plugins and themes exist in the Options table:
             * _site_transient_update_themes
             * _site_transient_update_plugins
             */

            // uses the flag above to determine if this is a plugin or a theme update request
            if ( $this->plugin_or_theme == 'plugin' ) {
                /**
                 * Plugin Updates
                 */
                add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
                // Check For Plugin Information to display on the update details page
                add_filter( 'plugins_api', array( $this, 'information_request' ), 10, 3 );
            } else if ( $this->plugin_or_theme == 'theme' ) {
                /**
                 * Theme Updates
                 */
                add_filter( 'pre_set_site_transient_update_themes', array( $this, 'update_check' ) );

                // Check For Theme Information to display on the update details page
                //add_filter( 'themes_api', array( $this, 'information_request' ), 10, 3 );

            }
        }

        /**
         * Sends and receives data to and from the server API
         *
         * @since  2.0
         *
         * @param array $args
         *
         * @return bool|string $response
         */
        public function send_query( $args ) {
            $target_url = esc_url_raw( add_query_arg( 'wc-api', 'wc-am-api', $this->api_url ) . '&' . http_build_query( $args ) );
            $request    = wp_safe_remote_post( $target_url );

            if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                return false;
            }

            $response = wp_remote_retrieve_body( $request );

            return ! empty( $response ) ? $response : false;
        }

        /**
         * Check for updates against the remote server.
         *
         * @since  2.0
         *
         * @param  object $transient
         *
         * @return object $transient
         */
        public function update_check( $transient ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            $args = array(
                'request'     => 'update',
                'slug'        => $this->slug,
                'plugin_name' => $this->plugin_name,
                'version'     => $this->wc_am_software_version,
                'product_id'  => $this->product_id,
                'api_key'     => $this->wc_am_api_key_key ? $this->data[ $this->wc_am_api_key_key ] : '',
                'instance'    => $this->wc_am_instance_id,
            );

            // Check for a plugin update
            $response = json_decode( $this->send_query( $args ), true );
            // Displays an admin error message in the WordPress dashboard
            //$this->check_response_for_errors( $response );

            if ( isset( $response[ 'data' ][ 'error_code' ] ) ) {
                add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', "{$response['data']['error']}", 'error' );
            }

            if ( $response !== false && $response[ 'success' ] === true ) {
                // New plugin version from the API
                $new_ver = (string) $response[ 'data' ][ 'package' ][ 'new_version' ];
                // Current installed plugin version
                $curr_ver = (string) $this->wc_am_software_version;

                $package = array(
                    'id'             => $response[ 'data' ][ 'package' ][ 'id' ],
                    'slug'           => $response[ 'data' ][ 'package' ][ 'slug' ],
                    'plugin'         => $response[ 'data' ][ 'package' ][ 'plugin' ],
                    'new_version'    => $response[ 'data' ][ 'package' ][ 'new_version' ],
                    'url'            => $response[ 'data' ][ 'package' ][ 'url' ],
                    'tested'         => $response[ 'data' ][ 'package' ][ 'tested' ],
                    'package'        => $response[ 'data' ][ 'package' ][ 'package' ],
                    'upgrade_notice' => $response[ 'data' ][ 'package' ][ 'upgrade_notice' ],
                );

                if ( isset( $new_ver ) && isset( $curr_ver ) ) {
                    if ( $response !== false && version_compare( $new_ver, $curr_ver, '>' ) ) {
                        if ( $this->plugin_or_theme == 'plugin' ) {
                            $transient->response[ $this->plugin_name ] = (object) $package;
                            unset( $transient->no_update[ $this->plugin_name ] );
                        } else if ( $this->plugin_or_theme == 'theme' ) {
                            $transient->response[ $this->plugin_name ][ 'new_version' ] = $response[ 'data' ][ 'package' ][ 'new_version' ];
                            $transient->response[ $this->plugin_name ][ 'url' ]         = $response[ 'data' ][ 'package' ][ 'url' ];
                            $transient->response[ $this->plugin_name ][ 'package' ]     = $response[ 'data' ][ 'package' ][ 'package' ];
                        }
                    }
                }
            }

            return $transient;
        }

        /**
         * API request for informatin.
         *
         * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
         * If `$action` is 'hot_tags` or 'hot_categories', an array should be passed.
         *
         * @param false|object|array $result The result object or array. Default false.
         * @param string             $action The type of information being requested from the Plugin Install API.
         * @param object             $args
         *
         * @return object
         */
        public function information_request( $result, $action, $args ) {
            // Check if this plugins API is about this plugin
            if ( isset( $args->slug ) ) {
                if ( $args->slug != $this->slug ) {
                    return $result;
                }
            } else {
                return $result;
            }

            $args = array(
                'request'     => 'plugininformation',
                'plugin_name' => $this->plugin_name,
                'version'     => $this->wc_am_software_version,
                'product_id'  => $this->product_id,
                'api_key'     => $this->data[ $this->wc_am_api_key_key ],
                'instance'    => $this->wc_am_instance_id,
                'object'      => $this->wc_am_domain,
            );

            $response = unserialize( base64_decode( $this->send_query( $args ) ) );

            if ( isset( $response ) && is_object( $response ) && $response !== false ) {
                return $response;
            }

            return $result;
        }
    }
}