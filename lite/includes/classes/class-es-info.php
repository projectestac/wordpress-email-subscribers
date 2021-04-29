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

		// Start-IG-Code.
		$help_title = __( 'Help & Info', 'email-subscribers' );
		add_submenu_page( 'es_dashboard', $help_title, $help_title, 'edit_posts', 'es_general_information', array( $this, 'es_information_callback' ) );

		$pro_title = __( '<span class="es-fire-sale"> 🔥 </span> Go Pro', 'email-subscribers' );
		if ( ! ES()->is_pro() ) {
			add_submenu_page( 'es_dashboard', $pro_title, $pro_title, 'edit_posts', 'es_pricing', array( $this, 'es_pricing_callback' ) );
		}
		// End-IG-Code.
	}

	public function es_information_callback() {

		$is_option_exists     = get_option( 'current_sa_email_subscribers_db_version', false );
		$enable_manual_update = false;
		if ( $is_option_exists ) {
			$enable_manual_update = true;
		}

		$update_url = add_query_arg( 'do_update_ig_es', 'true', admin_url( 'admin.php?page=es_general_information' ) );
		$update_url = add_query_arg( 'from_db_version', '3.5.18', $update_url );
		$update_url = wp_nonce_url( $update_url, 'ig_es_db_update', 'ig_es_db_update_nonce' );

		include_once ES_PLUGIN_DIR . '/lite/admin/partials/help.php';
	}

	public static function es_pricing_callback() {
		// remove because of warning
		// $url = 'https://www.icegram.com/email-subscribers-pricing/';
		// header('Location: ' . $url );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
