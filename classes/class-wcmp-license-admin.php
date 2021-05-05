<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCMp_License_Admin' ) ) {
    class WCMp_License_Admin {
    	public $data_key                          = '';
    	public $product_id       = '';
        public $wc_am_activation_tab_key          = '';
    	public $wc_am_licence_menu_title		  = '';
        public $wc_am_licence_menu_slug	          = '';
        public $wc_am_menu_tab_activation_title   = '';
        public $wc_am_menu_tab_deactivation_title = '';
        public $text_domain      = ''; // For language translation.
        public $tabs_list 		 = array();
        
    	public function __construct( $file, $product_id, $software_version, $plugin_or_theme, $api_url, $software_title = '', $text_domain = '' ) {
    		add_action( 'admin_init', array( $this, 'license_page_init' ) );
    		
    		if ( is_admin() ) {
    			if ( is_int( $product_id ) ) {
					$this->product_id = absint( $product_id );
				} else {
					$this->product_id = esc_attr( $product_id );
				}
				
				$this->data_key                          = 'wc_am_client_' . strtolower( str_ireplace( array( ' ', '_', '&', '?' ), '_', $this->product_id ) );
                $this->wc_am_activation_tab_key          = $this->data_key . '_dashboard';
    			$this->wc_am_licence_menu_slug			 = 'wcmp-license-admin';
    			$this->text_domain                       = esc_attr( $text_domain );
                $this->wc_am_licence_menu_title			 = esc_html__( 'License', 'wcmp-razorpay-checkout-gateway' );
                $this->wc_am_menu_tab_activation_title   = esc_html__( 'Activation', 'wcmp-razorpay-checkout-gateway' );
                $this->wc_am_menu_tab_deactivation_title = esc_html__( 'Deactivation', 'wcmp-razorpay-checkout-gateway' );

    			add_action( 'admin_menu', array( $this, 'register_menu' ), 101  );
            }
    	}
    		
		/**
         * Register submenu specific to this product.
         */
        public function register_menu() {
            add_submenu_page( 'wcmp', __( $this->wc_am_licence_menu_title, 'wcmp-razorpay-checkout-gateway' ), __( $this->wc_am_licence_menu_title, 'wcmp-razorpay-checkout-gateway' ), 'manage_woocommerce', $this->wc_am_licence_menu_slug, array( $this, 'load_settings_panel' ) );
        }
        
        // Draw option page
        public function load_settings_panel() {
            global $WCMp;
			?>
			<div class="wrap blank-wrap"><h2></h2></div>
			<div class="wrap wcmp-settings-wrap">
				<?php $this->wc_am_licence_tabs();
				$tab = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : current( array_keys( $this->tabs_list ) );
				foreach ( $this->tabs_list as $tab_id => $_tab ) {
					if ( $tab_id != $tab ) {
						continue;
					}
					$tab_section = isset( $_GET['tab_section'] ) && ! empty( $_GET['tab_section'] ) ? $_GET['tab_section'] : current( array_keys( $this->get_licence_subtabs( $tab_id ) ) );
				}
				?>
				<form class='wcmp_vendors_settings wcmp_subtab_content wcmp_<?php echo $tab; ?>_<?php echo $tab_section; ?>_settings_group' method="post" action="options.php">
					<?php
					if ( ! empty( $tab_desc[$tab] ) ) {
						echo '<h4 class="wcmp-tab-description">' . $tab_desc[$tab] . '</h4>';
					}
					?>
					<?php
					// This prints out all hidden setting fields
					$tab_selected = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : current( array_keys( $this->tabs_list ) );
					$get_product_id = str_replace("_dashboard", "", str_replace("wc_am_client_" , "", $tab_selected));
					
					if( isset( $_GET['tab_section'] ) && $_GET['tab_section'] == 'wc_am_client_' . $get_product_id . '_deactivation' ) { // Deactivation block
						settings_fields( 'wc_am_client_' . $get_product_id . '_deactivate_checkbox' );
						do_settings_sections( 'wc_am_client_' . $get_product_id . '_deactivation' );
						submit_button();
					} else {
						settings_fields( 'wc_am_client_' . $get_product_id );
						do_settings_sections( 'wc_am_client_' . $get_product_id . '_dashboard' );
						submit_button();
					}
					?>
				</form>
				</div>
			</div>
			<?php
			do_action( 'dualcube_admin_footer' );
        }
        
        public function wc_am_licence_tabs() {
			$current = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : $this->wc_am_activation_tab_key;
			$sublinks = array();
			foreach ( $this->tabs_list as $tab_id => $tab ) {
				if ( $current != $tab_id ) {
					continue;
				}
				$current_section = isset( $_GET['tab_section'] ) && ! empty( $_GET['tab_section'] ) ? $_GET['tab_section'] : current( array_keys( $this->get_licence_subtabs( $tab_id ) ) );
	
				foreach ( $this->get_licence_subtabs( $tab_id ) as $subtab_id => $subtab ) {
					$sublink = '';
					if ( is_array( $subtab ) ) {
						$icon = isset( $subtab['icon'] ) && ! empty( $subtab['icon'] ) ? '<span class="dashicons ' . $subtab['icon'] . '"></span> ' : '';
						$sublink = $icon . '<label>' . $subtab['title'] . '</label>';
					} else {
						$sublink = '<label>' . $subtab . '</label>';
					}
	
					if ( $subtab_id === $current_section ) {
						$sublinks[] = "<li><a class='current wcmp_sub_sction' href='?page=" . $this->wc_am_licence_menu_slug . "&tab=$tab_id&tab_section=$subtab_id'>$sublink</a></li>";
					} else {
						$sublinks[] = "<li><a class='wcmp_sub_sction' href='?page=" . $this->wc_am_licence_menu_slug . "&tab=$tab_id&tab_section=$subtab_id'>$sublink</a></li>";
					}
				}
			}
	
			$links = array();
			foreach ( $this->tabs_list as $tab => $name ) :
				if ( $tab == $current ) :
					$links[] = "<a class='nav-tab nav-tab-active' href='?page=" . $this->wc_am_licence_menu_slug . "&tab=$tab'>$name</a>";
				else :
					$links[] = "<a class='nav-tab' href='?page=" . $this->wc_am_licence_menu_slug . "&tab=$tab'>$name</a>";
				endif;
			endforeach;
	
	
			echo '<h2 class="nav-tab-wrapper">';
			foreach ( $links as $link ) {
				echo $link;
			}
			echo '</h2>';
	
			
			echo '<div class="wcmp_subtab_container">';
			echo '<ul class="subsubsub wcmpsubtabadmin">';
			foreach ( $sublinks as $sublink ) {
				echo $sublink;
			}
			echo '</ul>';
		}
		
		/**
		* Register and add settings
		*/
		public function license_page_init() { 
			$this->tabs_list        = $this->get_tabs_list();
		}
		
		public function get_tabs_list() {
			return apply_filters('wcmp_license_tabs_list', array());
		}
		
		public function get_licence_subtabs() {
			$tab_selected = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : current( array_keys( $this->tabs_list ) );
			$get_product_id = str_replace("_dashboard", "", str_replace("wc_am_client_" , "", $tab_selected));

			$tabs_list = array(
				'wc_am_client_' . $get_product_id . '_activation' => $this->wc_am_menu_tab_activation_title,
				'wc_am_client_' . $get_product_id . '_deactivation' => $this->wc_am_menu_tab_deactivation_title,
			);
			return $tabs_list;
		}
    }
}
