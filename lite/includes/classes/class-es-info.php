<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Info {

	public static $instance;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
	}

	public function plugin_menu() {

		$update_text = ES()->is_starter() ? esc_html__( ' Get MAX', 'email-subscribers' ) : esc_html__( ' Get PRO', 'email-subscribers' );

		// Start-IG-Code.
		$help_title = __( 'Help & Info', 'email-subscribers' );
		add_submenu_page( 'es_dashboard', $help_title, $help_title, 'edit_posts', 'es_general_information', array( $this, 'es_information_callback' ) );

		$pro_title = $update_text . ' <span class="premium-icon-rocket"></span>';
		if ( ! ES()->is_pro() ) {
			add_submenu_page( 'es_dashboard', $pro_title, $pro_title, 'edit_posts', 'es_pricing', array( $this, 'es_pricing_callback' ) );
		}
		// End-IG-Code.
	}

	public function es_information_callback() {

		include_once ES_PLUGIN_DIR . '/lite/admin/partials/help.php';
	}

	public static function es_pricing_callback() {

		// Email_Subscribers_Pricing::sm_show_pricing();
		Email_Subscribers_Pricing::es_show_pricing();

	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
