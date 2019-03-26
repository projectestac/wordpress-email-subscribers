<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      4.0
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 * @author     Your Name <email@example.com>
 */
class Email_Subscribers {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0
	 * @access   protected
	 * @var      Email_Subscribers_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    4.0
	 * @access   protected
	 * @var      string $email_subscribers The string used to uniquely identify this plugin.
	 */
	protected $email_subscribers;

	/**
	 * The current version of the plugin.
	 *
	 * @since    4.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    4.0
	 */
	public function __construct() {

		require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-activator.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-deactivator.php';
		add_action( 'admin_notices', array( $this, 'add_version_notice' ) );
		add_action( 'admin_init', array( &$this, 'es_dismiss_admin_notice' ) );
		if ( ! post_type_exists( 'es_template' ) ) {
			add_action( 'init', array( 'Email_Subscribers_Activator', 'register_email_templates' ) );
		}

		$this->email_subscribers = 'email-subscribers';

		$this->define_constants();
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_action( 'widgets_init', array( $this, 'register_es_widget' ) );
		add_filter( 'cron_schedules', array( $this, 'es_add_cron_interval' ));
	}

	public function add_version_notice() {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		if ( is_admin() && ( in_array( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) || array_key_exists( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) ) ) {
			$es_pro_plugin_meta_data = get_plugin_data( WP_PLUGIN_DIR . '/email-subscribers-premium/email-subscribers-premium.php' );
			$es_pro_plugin_version   = $es_pro_plugin_meta_data['Version'];

			if ( is_admin() && ! empty( $es_pro_plugin_version ) && version_compare( $es_pro_plugin_version, 4.0, '<' ) ) {
				$url = admin_url( "plugins.php?plugin_status=upgrade" );
				?>
                <div class="notice notice-error">
                    <p><?php echo sprintf( __( '<strong>Email Subscribers Pro</strong> plugin is activated but it won\'t work because it needs plugin to be updated. Please update %s plugin first.', 'email-subscribers-premium' ),
							'<a href="' . $url . '" target="_blank">' . __( 'Email Subscribers Pro', 'email-subscribers-premium' ) . '</a>' ); ?></p>
                </div>
				<?php
				return;
			}
		}

		$notice_option = get_option( 'ig_es_wp_cron_notice', 'yes' );
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON && $notice_option != 'no') {
				$es_cron_url = 'https://www.icegram.com/documentation/how-to-enable-the-wordpress-cron/?utm_source=es&utm_medium=in_app&utm_campaign=view_admin_notice';
				$cpanel_url = 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_admin_notice';
				$es_pro_url = 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_admin_notice';
				$disable_wp_cron_notice = sprintf(__('WordPress Cron is disable on your site. Email notifications from Email Subscribers plugin will not be sent automatically. <a href="%s" target="_blank" >Here\'s how you can enable it.</a>', 'email-subscribers'), $es_cron_url );
				$disable_wp_cron_notice .= '<br/>'. sprintf(__('Or schedule Cron in <a href="%s" target="_blank">cPanel</a>', 'email-subscribers'), $cpanel_url);
				$disable_wp_cron_notice .= '<br/>'. sprintf(__('Or use <strong><a href="%s" target="_blank">Email Subscribners Pro</a></strong> for automatic Cron support', 'email-subscribers'), $es_pro_url);
				echo '<div class="notice notice-warning" style="background-color: #FFF;"><p style="letter-spacing: 0.6px;">'.$disable_wp_cron_notice.'<a style="float:right" class="es-admin-btn es-admin-btn-secondary " href="' . admin_url() . '?es_dismiss_admin_notice=1&option_name=wp_cron_notice">' . __('OK, I Got it!' , 'email-subscribers') . '</a></p></div>';
		}
	}

	public function es_dismiss_admin_notice(){
		if(isset($_GET['es_dismiss_admin_notice']) && $_GET['es_dismiss_admin_notice'] == '1' && isset($_GET['option_name'])) {
			$option_name = sanitize_text_field($_GET['option_name']);
			update_option( 'ig_es_' . $option_name, 'no' );
			$referer = wp_get_referer();
			wp_safe_redirect( $referer );
			exit();
		}
	}

	public function define_constants() {

		global $wpdb;

		$upload_dir = wp_upload_dir( null, false );

		$constants = array(

			'EMAIL_SUBSCRIBERS_SLUG'               => 'email-subscribers',
			'IG_LOG_DIR'                           => $upload_dir['basedir'] . '/ig-logs/',
			'EMAIL_SUBSCRIBERS_INCLUDES_DIR'       => __DIR__ . '/includes',
			'EMAIL_SUBSCRIBERS_DIR'                => WP_PLUGIN_DIR . '/email-subscribers',
			'EMAIL_SUBSCRIBERS_URL'                => WP_PLUGIN_URL . '/email-subscribers',
			//TAbles
			'ES_EMAILLIST_TABLE'                   => $wpdb->prefix . 'es_emaillist',
			'EMAIL_LIST_TABLE'                     => $wpdb->prefix . 'es_lists',
			'EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE' => $wpdb->prefix . 'es_notification',
			'EMAIL_SUBSCRIBERS_STATS_TABLE'        => $wpdb->prefix . 'es_deliverreport',
			'EMAIL_SUBSCRIBERS_SENT_TABLE'         => $wpdb->prefix . 'es_sentdetails',
			'EMAIL_TEMPLATES_TABLE'                => $wpdb->prefix . 'es_templates',
			'EMAIL_SUBSCRIBERS_ADVANCED_FORM'      => $wpdb->prefix . 'es_advanced_form',
			// Constants
			'EMAIL_SUBSCRIBERS_LIST_MAX'           => 40,
			'EMAIL_SUBSCRIBERS_CRON_INTERVAL'      => 300,
			// Tables
			'IG_CAMPAIGNS_TABLE'                   => $wpdb->prefix . 'ig_campaigns',
			'IG_CONTACTS_TABLE'                    => $wpdb->prefix . 'ig_contacts',
			'IG_CONTACTS_IPS_TABLE'                => $wpdb->prefix . 'ig_contacts_ips',
			'IG_FORMS_TABLE'                       => $wpdb->prefix . 'ig_forms',
			'IG_LISTS_TABLE'                       => $wpdb->prefix . 'ig_lists',
			'IG_LISTS_CONTACTS_TABLE'              => $wpdb->prefix . 'ig_lists_contacts',
			'IG_MAILING_QUEUE_TABLE'               => $wpdb->prefix . 'ig_mailing_queue',
			'IG_SENDING_QUEUE_TABLE'               => $wpdb->prefix . 'ig_sending_queue',
			'IG_BLOCKED_EMAILS_TABLE'              => $wpdb->prefix . 'ig_blocked_emails',
			//Statuses
			'IG_EMAIL_STATUS_IN_QUEUE'             => 'in_queue',
			'IG_EMAIL_STATUS_SENDING'              => 'sending',
			'IG_EMAIL_STATUS_SENT'                 => 'sent',
			// Optin Types
			'IG_SINGLE_OPTIN'                      => 1,
			'IG_DOUBLE_OPTIN'                      => 2,

			'IG_CAMPAIGN_TYPE_POST_NOTIFICATION' => 'post_notification',
			'IG_CAMPAIGN_TYPE_NEWSLETTER'        => 'newsletter',
			'IG_DEFAULT_BATCH_SIZE'              => 100,
			'IG_MAX_MEMORY_LIMIT'                => '-1',
			'IG_SET_TIME_LIMIT'                  => 0,

			'IG_DEFAULT_LIST' => 'Test'

		);

		foreach ( $constants as $constant => $value ) {
			if ( ! defined( $constant ) ) {
				define( $constant, $value );
			}
		}

	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Email_Subscribers_Loader. Orchestrates the hooks of the plugin.
	 * - Email_Subscribers_Admin. Defines all hooks for the admin area.
	 * - Email_Subscribers_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    4.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		$required_files = array(

			// Loader
			'includes/class-email-subscribers-loader.php',
			//Logger
			'includes/logs/class-ig-logger-interface.php',
			'includes/logs/class-ig-log-handler-interface.php',
			'includes/logs/class-ig-log-handler.php',
			'includes/logs/log-handlers/class-ig-log-handler-file.php',
			'includes/logs/class-ig-log-levels.php',
			'includes/class-ig-logger.php',
			//Notices
			'includes/notices/class-es-admin-notices.php',
			// Database Classes
			'includes/db/class-es-db.php',
			'includes/db/class-es-db-mailing-queue.php',
			'includes/db/class-es-db-lists.php',
			'includes/db/class-es-db-contacts.php',
			'includes/db/class-es-db-lists-contacts.php',
			'includes/db/class-es-db-sending-queue.php',
			'includes/db/class-es-db-notifications.php',
			'includes/db/class-es-db-campaigns.php',
			'includes/db/class-es-db-forms.php',
			'includes/db/class-es-db-blocked-emails.php',
			// Mailer Class
			'includes/class-es-mailer.php',
			// Common Class
			'includes/class-es-common.php',
			// Admin Classes
			'includes/admin/class-es-lists-table.php',
			'includes/admin/class-es-subscribers-table.php',
			'includes/admin/class-es-post-notifications.php',
			'includes/admin/class-es-templates-table.php',
			'includes/admin/class-es-campaigns-table.php',
			'includes/admin/class-es-reports-table.php',
			'includes/admin/class-es-forms-table.php',
			'includes/admin/class-es-cron.php',
			'includes/admin/class-es-newsletters.php',
			'includes/admin/class-es-tools.php',
			'includes/admin/class-es-admin-settings.php',
			'includes/admin/class-es-widget.php',
			'includes/admin/class-es-old-widget.php',
			'includes/admin/class-es-form-widget.php',
			'includes/admin/class-es-export-subscribers.php',
			'includes/admin/class-es-import-subscribers.php',
			'includes/admin/class-es-info.php',
			'includes/admin/class-es-handle-post-notification.php',
			'includes/admin/class-es-handle-subscription.php',
			'includes/admin/class-es-handle-sync-wp-user.php',
			'includes/admin/class-es-subscription-throttaling.php',
			//includes
			'includes/upgrade/es-update-functions.php',
			'includes/es-core-functions.php',
			'includes/class-es-install.php',
			// Background Process
			'includes/upgrade/class-es-background-process.php',
			'includes/upgrade/class-es-background-updater.php',
			// Main public class
			'public/class-email-subscribers-public.php',
			// Partials
			'admin/partials/admin-header.php',
			'public/partials/class-es-shortcode.php',
			// Backward Compatibility with Rainmaker
			'includes/es-backward.php',
			// Main Admin Class
			'admin/class-email-subscribers-admin.php',

		);

		foreach ( $required_files as $file ) {
			$file_path = plugin_dir_path( dirname( __FILE__ ) ) . $file;
			require_once $file_path;
		}

		add_shortcode( 'email-subscribers', array( 'ES_Shortcode', 'render_es_subscription_shortcode' ) );
		add_shortcode( 'email-subscribers-advanced-form', array( 'ES_Shortcode', 'render_es_advanced_form' ) );
		add_shortcode( 'email-subscribers-form', array( 'ES_Shortcode', 'render_es_form' ) );
		$this->loader = new Email_Subscribers_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    4.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Email_Subscribers_Admin( $this->get_email_subscribers(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		//$this->loader->add_action( 'in_admin_header', $plugin_admin, 'ig_es_admin_header',99 );

		//$this->loader->add_action( 'edit_form_after_editor', $plugin_admin, 'es_edit_form_after_editor_fn' );
		//$this->loader->add_filter( 'cron_schedules',$plugin_admin,'es_cron_schedules');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Email_Subscribers_Public( $this->get_email_subscribers(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'es_email_subscribe_init' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    4.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     4.0
	 * @return    string    The name of the plugin.
	 */
	public function get_email_subscribers() {
		return $this->email_subscribers;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     4.0
	 * @return    Email_Subscribers_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     4.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public static function get_redirect( $url ) {
		header( "Location: " . $url );
		exit;
	}

	public function register_es_widget() {
		//register_widget( 'ES_Old_Widget' );
		//register_widget( 'ES_Widget' );
		register_widget( 'ES_Form_Widget' );
	}

	public static function get_request( $request ) {
		if ( isset( $_REQUEST[ $request ] ) ) {
			return wp_unslash( $_REQUEST[ $request ] );
		}

		return '';
	}

	public static function insert_widget_in_sidebar( $widget_id, $widget_data, $sidebar ) {
		// Retrieve sidebars, widgets and their instances
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
		$widget_instances = get_option( 'widget_' . $widget_id, array() );
		// Retrieve the key of the next widget instance
		$numeric_keys = array_filter( array_keys( $widget_instances ), 'is_int' );
		$next_key     = $numeric_keys ? max( $numeric_keys ) + 1 : 2;
		// Add this widget to the sidebar
		if ( ! isset( $sidebars_widgets[ $sidebar ] ) ) {
			$sidebars_widgets[ $sidebar ] = array();
		}
		$sidebars_widgets[ $sidebar ][] = $widget_id . '-' . $next_key;
		// Add the new widget instance
		$widget_instances[ $next_key ] = $widget_data;
		// Store updated sidebars, widgets and their instances
		update_option( 'sidebars_widgets', $sidebars_widgets );
		update_option( 'widget_' . $widget_id, $widget_instances );
	}

	function es_add_cron_interval( $schedules ) {
	    $schedules['ig_es_fifteen_mins_interval'] = array(
	        'interval' => 900,
	        'display'  => esc_html__( 'Every Fifteen Minutes' ),
	    );
	    return $schedules;
	}

}
