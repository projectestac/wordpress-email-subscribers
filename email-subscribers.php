<?php
/**
 * Plugin Name: Email Subscribers & Newsletters
 * Plugin URI: https://www.icegram.com/
 * Description: Add subscription forms on website, send HTML newsletters & automatically notify subscribers about new blog posts once it is published.
 * Version: 4.6.5
 * Author: Icegram
 * Author URI: https://www.icegram.com/
 * Requires at least: 3.9
 * Tested up to: 5.6
 * WC requires at least: 3.6.0
 * WC tested up to: 4.6.2
 * Requires PHP: 5.6
 * Text Domain: email-subscribers
 * Domain Path: /lite/languages/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright (c) 2016-2020 Icegram
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Minimum PHP version required for Email Subscribers
 *
 * @since 4.4.1
 * 
 * @since 4.4.3 Added if not already defined() check.
 */
if ( ! defined( 'IG_ES_MIN_PHP_VER' ) ) {
	define( 'IG_ES_MIN_PHP_VER', '5.6' );
}

if ( ! function_exists( 'ig_es_fail_php_version_notice' ) ) {

	/**
	 * Email Subscribers admin notice for minimum PHP version.
	 *
	 * Warning when the site doesn't have the minimum required PHP version.
	 *
	 * @return void
	 * @since 4.4.1
	 *
	 */
	function ig_es_fail_php_version_notice() {
		/* translators: %s: PHP version */
		$message      = sprintf( esc_html__( 'Email Subscribers requires PHP version %s+, plugin is currently NOT RUNNING.', 'email-subscribers' ), IG_ES_MIN_PHP_VER );
		$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
		echo wp_kses_post( $html_message );
	}
}

if ( ! version_compare( PHP_VERSION, IG_ES_MIN_PHP_VER, '>=' ) ) {
	add_action( 'admin_notices', 'ig_es_fail_php_version_notice' );
	return;
}

/**
 * Earlier we were using IG_ES_FEEDBACK_VERSION constant
 * We have made some changes into 1.0.11 which we want to use.
 * So, avoid conflicts, we have define new constant
 * Now onward, we will use this constant
 *
 * @since 4.3.0
 */
if ( ! defined( 'IG_ES_FEEDBACK_TRACKER_VERSION' ) ) {
	define( 'IG_ES_FEEDBACK_TRACKER_VERSION', '1.2.4' );
}


global $ig_es_tracker;

/**
 * WordPress version
 *
 * @since 4.4.2
 */
global $wp_version;


/* ***************************** Initial Compatibility Work (Start) ******************* */

/* =========== Do not edit this code unless you know what you are doing ========= */

/*
 * Note: We are not using ES_PLUGIN_DIR constant at this moment because there are chances
 * It might be defined from older version of ES
 */
require plugin_dir_path( __FILE__ ) . 'lite/includes/feedback/class-ig-tracker.php';
$ig_es_tracker = 'IG_Tracker_V_' . str_replace( '.', '_', IG_ES_FEEDBACK_TRACKER_VERSION );

if ( ! function_exists( 'ig_es_show_upgrade_pro_notice' ) ) {
	/**
	 * Show ES Premium Upgrade Notice
	 *
	 * @since 4.3.0
	 */
	function ig_es_show_upgrade_pro_notice() {
		$url = admin_url( 'plugins.php?plugin_status=upgrade' );
		?>
		<div class="notice notice-error">
			<p>
			<?php 
			/* translators: %s: Link to Email Subscribers Premium upgrade */
			echo wp_kses_post( sprintf( __( 'You are using older version of <strong>Email Subscribers Premium</strong> plugin. It won\'t work because it needs plugin to be updated. Please update %s plugin.', 'email-subscribers' ),
					'<a href="' . esc_url( $url ) . '" target="_blank">' . __( 'Email Subscribers Premium', 'email-subscribers' ) . '</a>' ) );
			?>
					</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'deactivate_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$ig_es_plan = 'lite';
if ( 'email-subscribers-premium.php' === basename( __FILE__ ) ) {
	$ig_es_plan = 'premium';
} elseif ( 'icegram-marketing-automation.php' === basename( __FILE__ ) ) {
	$ig_es_plan = 'woo';
}

$current_active_plugins = $ig_es_tracker::get_active_plugins();

if ( 'premium' === $ig_es_plan ) {
	// We don't need ES Lite version As we are already running ES Premium 4.3.0+
	// which includes ES Lite code.
	// So, deactivate it
	deactivate_plugins( 'email-subscribers/email-subscribers.php', true );
} elseif ( 'woo' === $ig_es_plan ) {
	$plugins_to_deactivate = array(
		'email-subscribers/email-subscribers.php',
		'email-subscribers-premium/email-subscribers-premium.php',
	);
	foreach ( $plugins_to_deactivate as $plugin_slug ) {
		if ( in_array( $plugin_slug, $current_active_plugins, true ) ) {
			deactivate_plugins( $plugin_slug, true );
		}
	}
} else {
	/**
	 * Steps:
	 * - Check Whether ES Premium Installed
	 * - If It's installed & It's < 4.3.0 => Show Upgrade Notice
	 * - If It's installed & It's >= 4.3.0 => return
	 */

	//- If It's installed & It's < 4.3.0 => Show Upgrade Notice
	$all_plugins = $ig_es_tracker::get_plugins( 'all', true );

	$es_pro_plugin         = 'email-subscribers-premium/email-subscribers-premium.php';
	$es_pro_plugin_version = ! empty( $all_plugins[ $es_pro_plugin ] ) ? $all_plugins[ $es_pro_plugin ]['version'] : '';

	if ( ! empty( $es_pro_plugin_version ) ) {

		// Is Pro active?
		$is_pro_active = $all_plugins[ $es_pro_plugin ]['is_active'];

		// Free >= 4.3.0 && Pro < 4.3.0
		if ( version_compare( $es_pro_plugin_version, 4.3, '<' ) ) {

			// Show Upgrade Notice if It's Admin Screen.
			if ( is_admin() ) {
				add_action( 'admin_head', 'ig_es_show_upgrade_pro_notice', PHP_INT_MAX );
			}

		} elseif ( $is_pro_active && version_compare( $es_pro_plugin_version, 4.3, '>=' ) ) {
			return;
		}
	}
}
/* ***************************** Initial Compatibility Work (End) ******************* */

if ( ! defined( 'ES_PLUGIN_VERSION' ) ) {
	define( 'ES_PLUGIN_VERSION', '4.6.5' );
}

// Plugin Folder Path.
if ( ! defined( 'ES_PLUGIN_DIR' ) ) {
	define( 'ES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ES_PLUGIN_BASE_NAME' ) ) {
	define( 'ES_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'ES_PLUGIN_FILE' ) ) {
	define( 'ES_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ES_PLUGIN_URL' ) ) {
	define( 'ES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Start-IG-Code.
if ( ! defined( 'IG_ES_PLUGIN_PLAN' ) ) {
	define( 'IG_ES_PLUGIN_PLAN', 'lite' );
}
// End-IG-Code.
// Start-Woo-Code.
if ( ! defined( 'IG_ES_PLUGIN_PLAN' ) ) {
	define( 'IG_ES_PLUGIN_PLAN', 'woo' );
}

if ( ! function_exists( 'ig_es_woocommerce_inactive_notice' ) ) {

	/**
	 * Email Subscribers admin notice when WooCommerce is in inactive.
	 *
	 * @return void
	 * @since 4.6.1
	 */
	function ig_es_woocommerce_inactive_notice() {
		$message      = esc_html__( 'Email Subscribers requires WooCommerce to be installed and active, plugin is currently NOT RUNNING.', 'email-subscribers' );
		$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
		echo wp_kses_post( $html_message );
	}
}

/*
 * Check if WooCommerce is active. Show notice if not active.
 */

if ( 'woo' === IG_ES_PLUGIN_PLAN && ! in_array( 'woocommerce/woocommerce.php', $current_active_plugins, true ) ) {
	add_action( 'admin_notices', 'ig_es_woocommerce_inactive_notice' );
	// Disable all other functionality.
	return;
}
// End-Woo-Code.
if ( ! function_exists( 'activate_email_subscribers' ) ) {
	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-email-subscribers-activator.php
	 * 
	 * @param bool $network_wide Is plugin being activated on a network.
	 */
	function activate_email_subscribers( $network_wide ) {

		global $wpdb;
		
		require_once ES_PLUGIN_DIR . 'lite/includes/class-email-subscribers-activator.php';

		if ( is_multisite() && $network_wide ) {
			
			// Get all active blogs in the network and activate plugin on each one
			$blog_ids = $wpdb->get_col( sprintf( "SELECT blog_id FROM $wpdb->blogs WHERE deleted = %d", 0 ) );
			foreach ( $blog_ids as $blog_id ) {
				ig_es_activate_on_blog( $blog_id );
			}
		} else {
			Email_Subscribers_Activator::activate();
			add_option( 'email_subscribers_do_activation_redirect', true );
		}
	}
}

if ( ! function_exists( 'deactivate_email_subscribers' ) ) {
	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-email-subscribers-deactivator.php
	 * 
	 * @param bool $network_wide Is plugin being activated on a network.
	 * 
	 */
	function deactivate_email_subscribers( $network_wide ) {

		require_once ES_PLUGIN_DIR . 'lite/includes/class-email-subscribers-deactivator.php';

		if ( is_multisite() && $network_wide ) {
			
			global $wpdb;
			
			// Get all active blogs in the network.
			$blog_ids = $wpdb->get_col( sprintf( "SELECT blog_id FROM $wpdb->blogs WHERE deleted = %d", 0 ) );
			foreach ( $blog_ids as $blog_id ) {
				// Run deactivation code on each one
				ig_es_trigger_deactivation_in_multisite( $blog_id );
			}
		} else {
			Email_Subscribers_Deactivator::deactivate();
		}
	}
}

register_activation_hook( __FILE__, 'activate_email_subscribers' );
register_deactivation_hook( __FILE__, 'deactivate_email_subscribers' );

if ( ! function_exists( 'ig_es_may_activate_on_blog' ) ) {

	/**
	 * Function to handle new blog(site) creation/activation in WP Multisite
	 * 
	 * @param  int $blog_id Blog ID of new site.
	 * 
	 * @since  4.4.2
	 */
	function ig_es_may_activate_on_blog( $blog_id ) {

		// In WP > WP 5.1.0 WP_Site object is passed instead of blog_id.
		if ( $blog_id instanceof WP_Site ) {
			$blog_id = (int) $blog_id->blog_id;
		}

		if ( empty( $blog_id ) || ! is_numeric( $blog_id ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active_for_network( 'email-subscribers/email-subscribers.php' ) ) {
			ig_es_activate_on_blog( $blog_id );
		}
	}
}

if ( ! function_exists( 'ig_es_activate_on_blog' ) ) {

	/**
	 * Function to trigger Email Subscribers' activation code for individual site/blog in a network.
	 * 
	 * @param  int $blog_id Blog ID of newly created site/blog.
	 * 
	 * @since  4.4.2
	 */
	function ig_es_activate_on_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		Email_Subscribers_Activator::activate();
		add_option( 'email_subscribers_do_activation_redirect', true );
		restore_current_blog();
	}
}

if ( ! function_exists( 'ig_es_trigger_deactivation_in_multisite' ) ) {

	/**
	 * Function to trigger Email Subscribers' deactivation code for individual site in a network.
	 * 
	 * @param  int $blog_id Blog ID of newly created site/blog.
	 * 
	 * @since  4.4.2
	 */
	function ig_es_trigger_deactivation_in_multisite( $blog_id ) {
		switch_to_blog( $blog_id );
		Email_Subscribers_Deactivator::deactivate();
		restore_current_blog();
	}
}

if ( version_compare( $wp_version, '5.1.0', '>' ) ) {
	/**
	 * New action when a new site/blog created in WP Multisite > 5.1.0. Priority is lower to allow other options of site to be set before we initiate our activation process.
	 */
	add_action( 'wp_initialize_site', 'ig_es_may_activate_on_blog', 99 );
} else {
	/**
	 * Deprecated action when  a new site/blog created in WP Multisite <= 5.1.0
	 */
	add_action( 'wpmu_new_blog', 'ig_es_may_activate_on_blog' );
}

// This action gets triggered when super admin activates a single site/blog.
add_action( 'activate_blog', 'ig_es_may_activate_on_blog' );

add_action( 'admin_init', 'email_subscribers_redirect' );

if ( ! function_exists( 'email_subscribers_redirect' ) ) {
	function email_subscribers_redirect() {
		
		// Check if it is multisite and the current user is in the network administrative interface. e.g. `/wp-admin/network/`
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		if ( get_option( 'email_subscribers_do_activation_redirect', false ) ) {
			delete_option( 'email_subscribers_do_activation_redirect' );
			wp_redirect( 'admin.php?page=es_dashboard' );
		}
	}
}

if ( ! function_exists( 'es_subbox' ) ) {
	/**
	 * Show subscription form
	 *
	 * @param null $namefield
	 * @param null $desc
	 * @param null $group
	 */
	function es_subbox( $namefield = null, $desc = null, $group = null ) {

		$allowedtags 			= ig_es_allowed_html_tags_in_esc();
		$atts 					= array(
			'namefield' => $namefield,
			'desc'      => $desc,
			'group'     => $group
		);
		$subscription_shortcode = ES_Shortcode::render_es_subscription_shortcode( $atts );
		echo wp_kses( $subscription_shortcode , $allowedtags ); 
	}
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ES_PLUGIN_DIR . 'lite/includes/class-email-subscribers.php';

if ( ! function_exists( 'ES' ) ) {
	
	/**
	 * Email Subscribers instance
	 *
	 * @return Email_Subscribers
	 *
	 * @since 4.2.1
	 */
	function ES() {
		return Email_Subscribers::instance();
	}
}

// Start ES
ES()->run();
