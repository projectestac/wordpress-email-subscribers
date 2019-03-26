<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Info {

	static $instance;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
	}

	public function plugin_menu() {
		add_submenu_page( 'es_dashboard', 'Help & Info', 'Help & Info', 'edit_posts', 'es_general_information', array( $this, 'es_information_callback' ) );
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		if ( ! ( in_array( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) || array_key_exists( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) ) ) {
			add_submenu_page( 'es_dashboard', 'Go Pro', 'Go Pro', 'edit_posts', 'es_pricing', array( $this, 'es_pricing_callback' ) );
		}
	}

	public function es_information_callback() {
		include_once( EMAIL_SUBSCRIBERS_DIR . '/admin/partials/help.php' );
	}

	public static function es_pricing_callback() {
		require_once( EMAIL_SUBSCRIBERS_DIR . '/admin/partials/pricing.php' );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

add_action( 'plugins_loaded', function () {
	ES_Info::get_instance();
} );
