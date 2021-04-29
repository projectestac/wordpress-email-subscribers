<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Email_Subscribers' ) ) {

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
	 */
	class Email_Subscribers {
		/**
		 * ES instance
		 *
		 * @since 4.2.1
		 *
		 * @var Email_Subscribers The one true Email_Subscribers
		 *
		 */
		private static $instance;

		/**
		 * ES_Queue object
		 *
		 * @since 4.2.1
		 * @var object|ES_Queue
		 *
		 */
		public $queue;

		/**
		 * ES_DB_Queue object
		 *
		 * @since 4.2.1
		 * @var object|ES_DB_Queue
		 *
		 */
		public $queue_db;
		/**
		 * ES_Actions object
		 *
		 * @since 4.2.1
		 * @var object|ES_Actions
		 *
		 */
		public $actions;

		/**
		 * ES_Cron object
		 *
		 * @since 4.3.1
		 * @var object|ES_Cron
		 *
		 */
		public $cron;

		/**
		 * ES_Compatibility object
		 *
		 * @since 4.3.9
		 * @var object|ES_Compatibility
		 *
		 */
		public $compatibiloty;

		/**
		 * ES_DB_Actions object
		 *
		 * @since 4.2.1
		 * @var object|ES_DB_Actions
		 *
		 */
		public $actions_db;

		/**
		 * Feedback object
		 *
		 * @since 4.2.1
		 *
		 * @var $feedback
		 *
		 */
		public $feedback;

		/**
		 * Tracker Object
		 *
		 * @since 4.2.1
		 *
		 * @var $tracker
		 *
		 */
		public $tracker;

		/**
		 * Campaigns Object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_Campaigns_Table
		 */
		public $campaigns;

		/**
		 * ES_DB_Campaigns object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_DB_Campaigns
		 *
		 */
		public $campaigns_db;

		/**
		 * Lists Object
		 *
		 * @since 4.2.1
		 * @var object|ES_Lists_Table
		 *
		 */
		public $lists;

		/**
		 * Lists DB Object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_DB_Lists
		 *
		 */
		public $lists_db;

		/**
		 * Forms Object
		 *
		 * @since 4.2.1
		 * @var object|ES_Forms_Table
		 *
		 */
		public $forms;

		/**
		 * Forms DB Object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_DB_Forms
		 */
		public $forms_db;

		/**
		 * Contacts Object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_Contacts_Table
		 */
		public $contacts;

		/**
		 * Contacts DB Object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_DB_Contacts
		 */
		public $contacts_db;

		/**
		 * ES_DB_Blocked_Emails object
		 *
		 * @since 4.2.2
		 *
		 * @var object|ES_DB_Blocked_Emails
		 */
		public $blocked_emails_db;

		/**
		 * ES_DB_Links object
		 *
		 * @since 4.2.4
		 *
		 * @var object|ES_DB_Links
		 */
		public $links_db;

		/**
		 * ES_DB_Lists_Contacts object
		 *
		 * @since 4.3.5
		 *
		 * @var object|ES_DB_Lists_Contacts
		 */
		public $lists_contacts_db;

		/**
		 * ES_Integrations object
		 *
		 * @since 4.2.1
		 *
		 * @var object|ES_Integrations
		 *
		 */
		public $integrations;

		/**
		 * IG_Logger object
		 *
		 * @since 4.2.1
		 *
		 * @var object|IG_Logger
		 *
		 */
		public $logger;

		/**
		 * ES_Mailer object
		 *
		 * @since 4.3.1
		 *
		 * @var object|ES_Mailer
		 */
		public $mailer;

		/**
		 * IG_ES_Trail object
		 *
		 * @var object|IG_ES_Trial
		 *
		 * @since 4.6.6
		 */
		public $trial;

		/**
		 * IG_ES_DB_WC_Cart object
		 *
		 * @var object|IG_ES_DB_WC_Cart
		 *
		 * @since 4.6.6
		 */
		public $carts_db;

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    4.0
		 *
		 * @var      Email_Subscribers_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    4.0
		 *
		 * @var      string $email_subscribers The string used to uniquely identify this plugin.
		 */
		protected $email_subscribers;

		/**
		 * The current version of the plugin.
		 *
		 * @since    4.0
		 *
		 * @var      string $version The current version of the plugin.
		 */
		protected $version;

		/**
		 * ES_DB_Workflows object
		 *
		 * @since 4.4.0
		 *
		 * @var object|ES_DB_Workflows
		 */
		public $workflows_db;

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
			$this->version = ES_PLUGIN_VERSION;
		}

		/**
		 * Add Admin Notices
		 *
		 * @since 4.0.0
		 */
		public function add_admin_notice() {
			///Halloween offer
			$show_offer = false;
			$current_page = ig_es_get_request_data( 'page' );
			
			if ( $this->can_upsell_features( array( 'lite', 'trial', 'starter' ) ) && IG_ES_Onboarding::is_onboarding_completed() ) { 
				if ( 'es_reports' === $current_page ) {
					$report_insight = ig_es_get_request_data( 'insight' );
					if ( ! $report_insight ) {
						$show_offer = true;
					}
				} else {
					$show_offer = true;
				}	
			}

			if ( $show_offer ) {
				$args['url']     = 'https://www.icegram.com/';
				$args['include'] = ES_PLUGIN_DIR . 'lite/includes/notices/views/ig-es-bfcm-offer.php';
				ES_Admin_Notices::add_custom_notice( 'bfcm_offer_2020', $args );
			} else {
				ES_Admin_Notices::remove_notice( 'bfcm_offer_2020' );
			}

			$screen_id = $this->get_current_screen_id();
			// Don't show admin notices on Dashboard if onboarding is not yet completed.
			$is_onboarding_complete = get_option( 'ig_es_onboarding_complete', false );

			// We don't have ig_es_onboarding_complete option if somebody is migrating from older version
			if ( ( 'toplevel_page_es_dashboard' === $screen_id ) && ( ! $is_onboarding_complete || 'no' == $is_onboarding_complete ) ) {
				return;
			}

			//cron notice
			$notice_option = get_option( 'ig_es_wp_cron_notice' );

			$show_notice = true;
			$show_notice = apply_filters( 'ig_es_show_wp_cron_notice', $show_notice );

			// If DISABLE_WP_CRON constant is defined and set to true, then we can say that wp cron is disabled.
			$wp_cron_disabled = ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? true : false;

			// Add notice only if wp cron is disabled and user have not acknowledged the notice by clicking on the acknowledgement button in the notice.
			if ( $wp_cron_disabled && 'yes' != $notice_option && $show_notice ) {
				$es_cron_url = 'https://www.icegram.com/documentation/how-to-enable-the-wordpress-cron/?utm_medium=enable_wordpress_cron&utm_source=in_app&utm_campaign=view_admin_notice';
				$cpanel_url  = 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=schedule_cron_in_cpanel&utm_medium=in_app&utm_campaign=view_admin_notice';
				$es_pro_url  = 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=schedule_cron_in_cpanel&utm_medium=in_app&utm_campaign=view_admin_notice';
				/* translators: %s: Cron URL */
				$disable_wp_cron_notice = sprintf( __( 'WordPress Cron is disabled on your site. Email notifications from Email Subscribers plugin will not be sent automatically. <a href="%s" target="_blank" >Here\'s how you can enable it.</a>', 'email-subscribers' ), $es_cron_url );
				/* translators: %s: Link to Cpanel URL */
				$disable_wp_cron_notice .= '<br/>' . sprintf( __( 'Or schedule Cron in <a href="%s" target="_blank">cPanel</a>', 'email-subscribers' ), $cpanel_url );
				/* translators: %s: ES Pro URL */
				$disable_wp_cron_notice .= '<br/>' . sprintf( __( 'Or use <strong><a href="%s" target="_blank">Email Subscribers Pro</a></strong> for automatic Cron support', 'email-subscribers' ), $es_pro_url );
				$html                    = '<div class="notice notice-warning" style="background-color: #FFF;"><p style="letter-spacing: 0.6px;">' . $disable_wp_cron_notice . '<a style="float:right" class="es-admin-btn es-admin-btn-secondary " href="' . admin_url() . '?es_dismiss_admin_notice=1&option_name=wp_cron_notice">' . __( 'OK, I Got it!',
						'email-subscribers' ) . '</a></p></div>';
				$args['html']            = $html;
				ES_Admin_Notices::add_custom_notice( 'show_wp_cron', $args );
			} else {
				// Remove the notice if user hasn't disabled the WP CRON or renabled the WP CRON.
				ES_Admin_Notices::remove_notice( 'show_wp_cron' );
			}

		}

		/**
		 * Define Contstants
		 *
		 * @since 4.0.0
		 */
		public function define_constants() {

			global $wpdb;

			$upload_dir = wp_upload_dir( null, false );

			if ( ! defined( 'EMAIL_SUBSCRIBERS_SLUG' ) ) {
				define( 'EMAIL_SUBSCRIBERS_SLUG', 'email-subscribers' );
			}

			if ( ! defined( 'IG_LOG_DIR' ) ) {
				define( 'IG_LOG_DIR', $upload_dir['basedir'] . '/ig-logs/' );
			}

			if ( ! defined( 'EMAIL_SUBSCRIBERS_INCLUDES_DIR' ) ) {
				define( 'EMAIL_SUBSCRIBERS_INCLUDES_DIR', __DIR__ . '/includes' );
			}

			if ( ! defined( 'EMAIL_SUBSCRIBERS_DIR' ) ) {
				define( 'EMAIL_SUBSCRIBERS_DIR', WP_PLUGIN_DIR . '/email-subscribers' );
			}

			if ( ! defined( 'ES_EMAILLIST_TABLE' ) ) {
				define( 'ES_EMAILLIST_TABLE', $wpdb->prefix . 'es_emaillist' );
			}

			if ( ! defined( 'EMAIL_LIST_TABLE' ) ) {
				define( 'EMAIL_LIST_TABLE', $wpdb->prefix . 'es_lists' );
			}
			if ( ! defined( 'EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE' ) ) {
				define( 'EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE', $wpdb->prefix . 'es_notification' );
			}

			if ( ! defined( 'EMAIL_SUBSCRIBERS_STATS_TABLE' ) ) {
				define( 'EMAIL_SUBSCRIBERS_STATS_TABLE', $wpdb->prefix . 'es_deliverreport' );
			}
			if ( ! defined( 'EMAIL_SUBSCRIBERS_SENT_TABLE' ) ) {
				define( 'EMAIL_SUBSCRIBERS_SENT_TABLE', $wpdb->prefix . 'es_sentdetails' );
			}

			if ( ! defined( 'EMAIL_TEMPLATES_TABLE' ) ) {
				define( 'EMAIL_TEMPLATES_TABLE', $wpdb->prefix . 'es_templates' );
			}
			if ( ! defined( 'EMAIL_SUBSCRIBERS_ADVANCED_FORM' ) ) {
				define( 'EMAIL_SUBSCRIBERS_ADVANCED_FORM', $wpdb->prefix . 'es_advanced_form' );
			}
			if ( ! defined( 'EMAIL_SUBSCRIBERS_LIST_MAX' ) ) {
				define( 'EMAIL_SUBSCRIBERS_LIST_MAX', 40 );
			}
			if ( ! defined( 'EMAIL_SUBSCRIBERS_CRON_INTERVAL' ) ) {
				define( 'EMAIL_SUBSCRIBERS_CRON_INTERVAL', 300 );
			}
			if ( ! defined( 'IG_CAMPAIGNS_TABLE' ) ) {
				define( 'IG_CAMPAIGNS_TABLE', $wpdb->prefix . 'ig_campaigns' );
			}
			if ( ! defined( 'IG_WORKFLOWS_TABLE' ) ) {
				define( 'IG_WORKFLOWS_TABLE', $wpdb->prefix . 'ig_workflows' );
			}
			if ( ! defined( 'IG_CONTACTS_TABLE' ) ) {
				define( 'IG_CONTACTS_TABLE', $wpdb->prefix . 'ig_contacts' );
			}
			if ( ! defined( 'IG_CONTACTS_IPS_TABLE' ) ) {
				define( 'IG_CONTACTS_IPS_TABLE', $wpdb->prefix . 'ig_contacts_ips' );
			}
			if ( ! defined( 'IG_FORMS_TABLE' ) ) {
				define( 'IG_FORMS_TABLE', $wpdb->prefix . 'ig_forms' );
			}
			if ( ! defined( 'IG_LISTS_TABLE' ) ) {
				define( 'IG_LISTS_TABLE', $wpdb->prefix . 'ig_lists' );
			}
			if ( ! defined( 'IG_LISTS_CONTACTS_TABLE' ) ) {
				define( 'IG_LISTS_CONTACTS_TABLE', $wpdb->prefix . 'ig_lists_contacts' );
			}
			if ( ! defined( 'IG_MAILING_QUEUE_TABLE' ) ) {
				define( 'IG_MAILING_QUEUE_TABLE', $wpdb->prefix . 'ig_mailing_queue' );
			}
			if ( ! defined( 'IG_SENDING_QUEUE_TABLE' ) ) {
				define( 'IG_SENDING_QUEUE_TABLE', $wpdb->prefix . 'ig_sending_queue' );
			}
			if ( ! defined( 'IG_BLOCKED_EMAILS_TABLE' ) ) {
				define( 'IG_BLOCKED_EMAILS_TABLE', $wpdb->prefix . 'ig_blocked_emails' );
			}
			if ( ! defined( 'IG_ACTIONS_TABLE' ) ) {
				define( 'IG_ACTIONS_TABLE', $wpdb->prefix . 'ig_actions' );
			}
			if ( ! defined( 'IG_LINKS_TABLE' ) ) {
				define( 'IG_LINKS_TABLE', $wpdb->prefix . 'ig_links' );
			}

			if ( ! defined( 'IG_CONTACT_META_TABLE' ) ) {
				define( 'IG_CONTACT_META_TABLE', $wpdb->prefix . 'ig_contactmeta' );
			}

			if ( ! defined( 'IG_QUEUE_TABLE' ) ) {
				define( 'IG_QUEUE_TABLE', $wpdb->prefix . 'ig_queue' );
			}

			if ( ! defined( 'IG_EMAIL_STATUS_IN_QUEUE' ) ) {
				define( 'IG_EMAIL_STATUS_IN_QUEUE', 'in_queue' );
			}
			if ( ! defined( 'IG_EMAIL_STATUS_SENDING' ) ) {
				define( 'IG_EMAIL_STATUS_SENDING', 'sending' );
			}
			if ( ! defined( 'IG_EMAIL_STATUS_SENT' ) ) {
				define( 'IG_EMAIL_STATUS_SENT', 'sent' );
			}
			if ( ! defined( 'IG_SINGLE_OPTIN' ) ) {
				define( 'IG_SINGLE_OPTIN', 1 );
			}
			if ( ! defined( 'IG_DOUBLE_OPTIN' ) ) {
				define( 'IG_DOUBLE_OPTIN', 2 );
			}
			if ( ! defined( 'IG_CAMPAIGN_TYPE_POST_NOTIFICATION' ) ) {
				define( 'IG_CAMPAIGN_TYPE_POST_NOTIFICATION', 'post_notification' );
			}
			if ( ! defined( 'IG_CAMPAIGN_TYPE_NEWSLETTER' ) ) {
				define( 'IG_CAMPAIGN_TYPE_NEWSLETTER', 'newsletter' );
			}
			if ( ! defined( 'IG_CAMPAIGN_TYPE_POST_DIGEST' ) ) {
				define( 'IG_CAMPAIGN_TYPE_POST_DIGEST', 'post_digest' );
			}
			if ( ! defined( 'IG_CAMPAIGN_TYPE_SEQUENCE' ) ) {
				define( 'IG_CAMPAIGN_TYPE_SEQUENCE', 'sequence' );
			}

			if ( ! defined( 'IG_CAMPAIGN_TYPE_SEQUENCE_MESSAGE' ) ) {
				define( 'IG_CAMPAIGN_TYPE_SEQUENCE_MESSAGE', 'sequence_message' );
			}

			if ( ! defined( 'IG_DEFAULT_BATCH_SIZE' ) ) {
				define( 'IG_DEFAULT_BATCH_SIZE', 100 );
			}
			if ( ! defined( 'IG_MAX_MEMORY_LIMIT' ) ) {
				define( 'IG_MAX_MEMORY_LIMIT', '-1' );
			}
			if ( ! defined( 'IG_SET_TIME_LIMIT' ) ) {
				define( 'IG_SET_TIME_LIMIT', 0 );
			}
			if ( ! defined( 'IG_DEFAULT_LIST' ) ) {
				define( 'IG_DEFAULT_LIST', 'Test' );
			}
			if ( ! defined( 'IG_MAIN_LIST' ) ) {
				define( 'IG_MAIN_LIST', 'Main' );
			}
			if ( ! defined( 'IG_CONTACT_SUBSCRIBE' ) ) {
				define( 'IG_CONTACT_SUBSCRIBE', 1 );
			}
			if ( ! defined( 'IG_MESSAGE_SENT' ) ) {
				define( 'IG_MESSAGE_SENT', 2 );
			}
			if ( ! defined( 'IG_MESSAGE_OPEN' ) ) {
				define( 'IG_MESSAGE_OPEN', 3 );
			}
			if ( ! defined( 'IG_LINK_CLICK' ) ) {
				define( 'IG_LINK_CLICK', 4 );
			}
			if ( ! defined( 'IG_CONTACT_UNSUBSCRIBE' ) ) {
				define( 'IG_CONTACT_UNSUBSCRIBE', 5 );
			}
			if ( ! defined( 'IG_MESSAGE_SOFT_BOUNCE' ) ) {
				define( 'IG_MESSAGE_SOFT_BOUNCE', 6 );
			}
			if ( ! defined( 'IG_MESSAGE_HARD_BOUNCE' ) ) {
				define( 'IG_MESSAGE_HARD_BOUNCE', 7 );
			}
			if ( ! defined( 'IG_MESSAGE_ERROR' ) ) {
				define( 'IG_MESSAGE_ERROR', 8 );
			}
			if ( ! defined( 'IG_ES_CRON_INTERVAL' ) ) {
				define( 'IG_ES_CRON_INTERVAL', 15 * MINUTE_IN_SECONDS );
			}
			if ( ! defined( 'IG_ES_MAX_EMAIL_SEND_AT_ONCE' ) ) {
				define( 'IG_ES_MAX_EMAIL_SEND_AT_ONCE', 30 );
			}


			if ( ! defined( 'IG_ES_CAMPAIGN_STATUS_IN_ACTIVE' ) ) {
				define( 'IG_ES_CAMPAIGN_STATUS_IN_ACTIVE', 0 );
			}

			if ( ! defined( 'IG_ES_CAMPAIGN_STATUS_ACTIVE' ) ) {
				define( 'IG_ES_CAMPAIGN_STATUS_ACTIVE', 1 );
			}

			if ( ! defined( 'IG_ES_CAMPAIGN_STATUS_SCHEDULED' ) ) {
				define( 'IG_ES_CAMPAIGN_STATUS_SCHEDULED', 2 );
			}

			if ( ! defined( 'IG_ES_CAMPAIGN_STATUS_QUEUED' ) ) {
				define( 'IG_ES_CAMPAIGN_STATUS_QUEUED', 3 );
			}

			if ( ! defined( 'IG_ES_CAMPAIGN_STATUS_PAUSED' ) ) {
				define( 'IG_ES_CAMPAIGN_STATUS_PAUSED', 4 );
			}

			if ( ! defined( 'IG_ES_CAMPAIGN_STATUS_FINISHED' ) ) {
				define( 'IG_ES_CAMPAIGN_STATUS_FINISHED', 5 );
			}


			if ( ! defined( 'IG_ES_WORKFLOW_STATUS_IN_ACTIVE' ) ) {
				define( 'IG_ES_WORKFLOW_STATUS_IN_ACTIVE', 0 );
			}

			if ( ! defined( 'IG_ES_WORKFLOW_STATUS_ACTIVE' ) ) {
				define( 'IG_ES_WORKFLOW_STATUS_ACTIVE', 1 );
			}

			if ( ! defined( 'IG_ES_TRIAL_PERIOD_IN_DAYS' ) ) {
				define( 'IG_ES_TRIAL_PERIOD_IN_DAYS', 14 );
			}
		}

		/**
		 * Define Constant
		 *
		 * @param $constant
		 * @param $value
		 *
		 * @since 4.2.0
		 */
		public function define( $constant, $value ) {
			if ( ! defined( $constant ) ) {
				define( $constant, $value );
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
		 * 
		 */
		private function load_dependencies() {

			$files_to_load = array(
				'lite/includes/class-email-subscribers-loader.php',
				'lite/includes/class-email-subscribers-i18n.php',

				'lite/includes/classes/class-es-list-table.php',

				// Logs
				'lite/includes/logs/class-ig-logger-interface.php',
				'lite/includes/logs/class-ig-log-handler-interface.php',
				'lite/includes/logs/class-ig-log-handler.php',
				'lite/includes/logs/log-handlers/class-ig-log-handler-file.php',
				'lite/includes/logs/class-ig-log-levels.php',
				'lite/includes/class-ig-logger.php',

				// Admin Notices
				'lite/includes/notices/class-es-admin-notices.php',


				// Database class files
				'lite/includes/db/class-es-db.php',
				'lite/includes/db/class-es-db-queue.php',
				'lite/includes/db/class-es-db-mailing-queue.php',
				'lite/includes/db/class-es-db-lists.php',
				'lite/includes/db/class-es-db-links.php',
				'lite/includes/db/class-es-db-contacts.php',
				'lite/includes/db/class-es-db-lists-contacts.php',
				'lite/includes/db/class-es-db-sending-queue.php',
				'lite/includes/db/class-es-db-notifications.php',
				'lite/includes/db/class-es-db-campaigns.php',
				'lite/includes/db/class-es-db-forms.php',
				'lite/includes/db/class-es-db-blocked-emails.php',
				'lite/includes/db/class-es-db-actions.php',
				'lite/includes/db/class-ig-es-db-unsubscribe-feedback.php',
				'lite/includes/db/class-ig-es-db-wc-cart.php',
				'lite/includes/db/class-ig-es-db-wc-guest.php',

				// Mailers
				'lite/includes/mailers/class-es-base-mailer.php',
				'lite/includes/mailers/class-es-pepipost-mailer.php',
				'lite/includes/mailers/class-es-phpmail-mailer.php',
				'lite/includes/mailers/class-es-wpmail-mailer.php',

				// Common Class
				'lite/includes/class-es-common.php',

				// Services
				'lite/includes/services/class-es-services.php',
				'lite/includes/services/class-es-email-delivery-check.php',
				'lite/includes/services/class-es-send-test-email.php',
				'lite/includes/services/class-es-service-spam-score-check.php',
				'lite/includes/services/class-es-service-handle-cron-data.php',
				'lite/includes/services/class-es-service-process-email-content.php',

				// Classes
				'lite/includes/classes/class-es-list-table.php',
				'lite/includes/classes/class-es-cache.php',
				'lite/includes/classes/class-es-mailer.php',
				'lite/includes/classes/class-es-message.php',
				'lite/includes/classes/class-es-lists-table.php',
				'lite/includes/classes/class-es-contacts-table.php',
				'lite/includes/classes/class-es-post-notifications.php',
				'lite/includes/classes/class-es-templates-table.php',
				'lite/includes/classes/class-es-campaigns-table.php',
				'lite/includes/classes/class-es-reports-table.php',
				'lite/includes/classes/class-es-reports-data.php',
				'lite/includes/classes/class-es-forms-table.php',
				'lite/includes/classes/class-es-queue.php',
				'lite/includes/classes/class-es-cron.php',
				'lite/includes/classes/class-es-newsletters.php',
				'lite/includes/classes/class-es-tools.php',
				'lite/includes/classes/class-es-admin-settings.php',
				'lite/includes/classes/class-es-widget.php',
				'lite/includes/classes/class-es-old-widget.php',
				'lite/includes/classes/class-es-form-widget.php',
				'lite/includes/classes/class-es-export-subscribers.php',
				'lite/includes/classes/class-es-import-subscribers.php',
				'lite/includes/classes/class-es-campaign-report.php',
				// Start-IG-Code.
				'lite/includes/classes/class-es-info.php',
				// End-IG-Code.
				'lite/includes/classes/class-es-handle-post-notification.php',
				'lite/includes/classes/class-es-handle-subscription.php',
				'lite/includes/classes/class-es-handle-sync-wp-user.php',
				'lite/includes/classes/class-es-subscription-throttling.php',
				'lite/includes/classes/class-es-actions.php',
				'lite/includes/classes/class-es-tracking.php',
				'lite/includes/classes/class-es-compatibility.php',
				'lite/includes/classes/class-es-ig-redirect.php',
				'lite/includes/classes/class-es-geolocation.php',
				'lite/includes/classes/class-es-browser.php',
				'lite/includes/classes/class-ig-es-trial.php',
				'lite/includes/classes/class-es-mailchimp-api.php',

				// Core Functions
				'lite/includes/es-core-functions.php',

				// Install/ Update
				'lite/includes/upgrade/es-update-functions.php',
				'lite/includes/class-es-install.php',
				
				// Onboarding process handler class.
				'lite/admin/class-ig-es-onboarding.php',

				// Public Classes
				'lite/public/class-email-subscribers-public.php',
				'lite/admin/partials/admin-header.php',
				'lite/public/partials/class-es-shortcode.php',

				// Start-IG-Code.
				// Backward Compatibility.
				'lite/includes/es-backward.php',
				// End-IG-Code.
				'lite/admin/class-email-subscribers-admin.php',

				// Start-IG-Code.
				// Pro Feature
				'lite/includes/pro-features.php',
				// End-IG-Code.
				
				// Feedback Class
				'lite/includes/feedback/class-ig-tracker.php',
				// Start-IG-Code.
				'lite/includes/feedback/class-ig-feedback.php',
				'lite/includes/feedback.php',
				// End-IG-Code.
				
				// WC session tracking
				'lite/includes/classes/class-ig-es-wc-session-tracker.php',
				'lite/includes/classes/ig-es-wc-cookies.php',
				
				// Workflows
				'lite/includes/workflows/db/class-es-db-workflows.php',
				'lite/includes/workflows/db/class-es-db-workflows-queue.php',
				'lite/includes/workflows/class-es-workflows-table.php',
				// Workflow Abstracts
				'lite/includes/workflows/abstracts/class-es-workflow-registry.php',
				'lite/includes/workflows/abstracts/class-es-workflow-trigger.php',
				'lite/includes/workflows/abstracts/class-es-workflow-action.php',
				'lite/includes/workflows/abstracts/class-es-workflow-data-type.php',
				'lite/includes/workflows/abstracts/class-ig-es-workflow-variable.php',
				
				// Workflow Utility
				'lite/includes/workflows/class-es-clean.php',
				'lite/includes/workflows/class-es-format.php',
				'lite/includes/workflows/class-es-workflow-time-helper.php',
				'lite/includes/workflows/class-es-workflow-datetime.php',
				'lite/includes/workflows/class-ig-es-variables-processor.php',
				'lite/includes/workflows/class-ig-es-workflow-variable-parser.php',
				'lite/includes/workflows/class-ig-es-variables.php',
				'lite/includes/workflows/class-ig-es-replace-helper.php',
				'lite/includes/workflows/workflow-helper-functions.php',

				// Workflow
				'lite/includes/workflows/class-es-workflow.php',
				'lite/includes/workflows/class-es-workflow-factory.php',
				
				// Data Types
				'lite/includes/workflows/data-types/abstracts/class-es-data-type-form-data.php',
				'lite/includes/workflows/data-types/class-es-data-type-user.php',
				'lite/includes/workflows/class-es-workflow-data-types.php',
				
				'lite/includes/workflows/variables/class-es-workflow-data-types.php',
				
				// Data Layer
				'lite/includes/workflows/class-es-workflow-data-layer.php',
				
				// Workflow Fields
				'lite/includes/workflows/fields/class-es-field.php',
				'lite/includes/workflows/fields/class-es-text.php',
				'lite/includes/workflows/fields/class-es-date.php',
				'lite/includes/workflows/fields/class-es-number.php',
				'lite/includes/workflows/fields/class-es-time.php',
				'lite/includes/workflows/fields/class-es-select.php',
				'lite/includes/workflows/fields/class-es-checkbox.php',
				'lite/includes/workflows/fields/class-es-wp-editor.php',
				
				// Workflow Admin
				'lite/includes/workflows/admin/class-es-workflow-admin.php',
				'lite/includes/workflows/admin/class-es-workflow-admin-edit.php',
				'lite/includes/workflows/admin/class-es-workflow-admin-ajax.php',
				
				// Workflow Triggers.
				'lite/includes/workflows/triggers/abstracts/class-es-trigger-form-submitted.php',
				'lite/includes/workflows/triggers/class-es-trigger-user-registered.php',
				'lite/includes/workflows/triggers/class-es-trigger-user-deleted.php',
				'lite/includes/workflows/triggers/class-es-trigger-user-updated.php',
				'lite/includes/workflows/class-es-workflow-triggers.php',
				
				// Abstracts workflow actions
				'lite/includes/workflows/actions/abstracts/class-ig-es-action-send-email-abstract.php',
				
				// Workflow Actions.
				'lite/includes/workflows/actions/class-es-action-add-to-list.php',
				'lite/includes/workflows/actions/class-es-action-move-contact.php',
				'lite/includes/workflows/actions/class-es-action-remove-contact.php',
				'lite/includes/workflows/actions/class-es-action-delete-contact.php',
				'lite/includes/workflows/actions/class-es-action-update-contact.php',
				'lite/includes/workflows/class-es-workflow-actions.php',
				
				// Workflow Query
				'lite/includes/workflows/class-es-workflow-query.php',
				
				// Workflow Queue			
				'lite/includes/workflows/queue/class-es-workflow-queue.php',
				'lite/includes/workflows/queue/class-es-workflow-queue-factory.php',
				'lite/includes/workflows/queue/class-es-workflow-queue-handler.php',
				'lite/includes/workflows/queue/class-es-workflow-queue-runner.php',

				// Workflow Loader
				'lite/includes/workflows/class-es-workflow-loader.php',
				
				// Premium services ui components.
				'lite/includes/premium-services-ui/class-ig-es-premium-services-ui.php',
				
				// Background Process Helper
				'lite/includes/classes/class-ig-es-background-process-helper.php',
				
				// Subscribers Query
				'lite/includes/classes/class-ig-es-subscriber-query.php',
				
				// Campaign Rules
				'lite/admin/class-ig-es-campaign-rules.php',

				'starter/starter-class-email-subscribers.php',
				'pro/pro-class-email-subscribers.php',
			);
			
			foreach ( $files_to_load as $file ) {
				if ( is_file( ES_PLUGIN_DIR . $file ) ) {
					require_once ES_PLUGIN_DIR . $file;
				}
			}

			add_shortcode( 'email-subscribers', array( 'ES_Shortcode', 'render_es_subscription_shortcode' ) );
			add_shortcode( 'email-subscribers-advanced-form', array( 'ES_Shortcode', 'render_es_advanced_form' ) );
			add_shortcode( 'email-subscribers-form', array( 'ES_Shortcode', 'render_es_form' ) );

			$this->loader = new Email_Subscribers_Loader();

		}

		/**
		 * Set Localization.
		 *
		 * @since   1.0.0
		 */
		private function set_locale() {

			$plugin_i18n = new Email_Subscribers_I18n();

			$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    4.0
		 * 
		 */
		private function define_admin_hooks() {

			$plugin_admin = new Email_Subscribers_Admin( $this->get_email_subscribers(), $this->get_version() );

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
			$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'plugins_loaded' );

			$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'save_screen_options', 20, 3 );
			$this->loader->add_action( 'wp_ajax_count_contacts_by_list', $plugin_admin, 'count_contacts_by_list' );
			$this->loader->add_action( 'wp_ajax_get_template_content', $plugin_admin, 'get_template_content' );
			$this->loader->add_action( 'admin_print_scripts', $plugin_admin, 'remove_other_admin_notices' );

			// Start-IG-Code.
			$this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'update_admin_footer_text' );
			// End-IG-Code.
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    4.0
		 * 
		 */
		private function define_public_hooks() {

			$plugin_public = new Email_Subscribers_Public( $this->get_email_subscribers(), $this->get_version() );

			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
			$this->loader->add_action( 'init', $plugin_public, 'es_email_subscribe_init' );
			$this->loader->add_action( 'ig_es_add_contact', $plugin_public, 'add_contact', 10, 2 );
			$this->loader->add_action( 'ig_es_confirm_unsubscription', $plugin_public, 'confirm_unsubscription', 10, 2 );

			$this->loader->add_filter( 'es_template_type', $plugin_public, 'add_template_type' );
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
		 * @return    string    The name of the plugin.
		 * @since     4.0
		 */
		public function get_email_subscribers() {
			return $this->email_subscribers;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @return    Email_Subscribers_Loader    Orchestrates the hooks of the plugin.
		 * @since     4.0
		 */
		public function get_loader() {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @return    string    The version number of the plugin.
		 * @since     4.0
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Method to get if user has opted for trial or not.
		 * 
		 * @return bool
		 * 
		 * @since 4.6.0
		 */
		public function is_trial() {
			$is_trial = get_option( 'ig_es_is_trial', '' );
			if ( 'yes' === $is_trial ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get trial start date
		 *
		 * @return false|mixed|void
		 *
		 * @since 4.6.6
		 */
		public function get_trial_start_date() {
			return get_option( 'ig_es_trial_started_at', '' );
		}

		/**
		 * Method to get if trial has expired or not.
		 * 
		 * @return bool
		 * 
		 * @since 4.6.1
		 */
		public function is_trial_expired() {
			$is_trial_expired = false;
			$is_trial         = get_option( 'ig_es_is_trial', '' );

			if ( 'yes' === $is_trial ) {
				$trial_started_at = get_option( 'ig_es_trial_started_at' );
				if ( ! empty( $trial_started_at ) ) {
					
					// Get current timestamp.
					$current_time = time();
					
					// Get the timestamp when trial will expire.
					$trial_expires_at = $trial_started_at + ES()->trial->get_trial_period();
					
					// Check if current time is greater than expiry time.
					if ( $current_time > $trial_expires_at ) {
						$is_trial_expired = true;
					}
				}
			}

			return $is_trial_expired;
		}

		/**
		 * Method to check if trial is valid.
		 * 
		 * @return bool $is_trial_valid Is trial valid
		 * 
		 * @since 4.6.1
		 */
		public function is_trial_valid() {

			// Check if user has opted for trial and it has not yet expired.
			return $this->is_trial() && ! $this->is_trial_expired();
		}

		/**
		 * Method to validate a premium service request
		 * 
		 * @param array $service Request
		 * 
		 * @return bool
		 * 
		 * @since 4.6.1
		 */
		public function validate_service_request( $services = array() ) {
			$is_request_valid = false;
			
			// Check if trial is still valid.
			if ( $this->is_trial_valid() ) {
				$is_request_valid = true;
			} else if ( $this->is_premium() ) {
				$es_services = apply_filters( 'ig_es_services', array() );
				if ( ! empty( $es_services ) ) {
					// Check if there is not any invalid service in $services array which is not present in the $es_services.
					$invalid_services = array_diff( $services, $es_services );
					if ( empty( $invalid_services ) ) {
						$is_request_valid = true;
					}
				}
			}

			return $is_request_valid;
		}

		/**
		 * Is ES PRO?
		 *
		 * @return bool
		 *
		 * @since 4.3.0
		 */
		public function is_pro() {
			return file_exists( ES_PLUGIN_DIR . 'pro/pro-class-email-subscribers.php' );
		}

		/**
		 * Is ES Starter?
		 *
		 * @return bool
		 *
		 * @since 4.3.0
		 */
		public function is_starter() {
			return file_exists( ES_PLUGIN_DIR . 'starter/starter-class-email-subscribers.php' );
		}

		/**
		 * Is ES Premium?
		 *
		 * @return bool
		 *
		 * @since 4.4.4
		 */
		public function is_premium() {
			return ES()->is_starter() || ES()->is_pro();
		}

		/**
		 * Check whether ES premium activated
		 *
		 * @return mixed
		 *
		 * @since 4.4.8
		 */
		public function is_premium_activated() {
			global $ig_es_tracker;

			$plugin = 'email-subscribers-premium/email-subscribers-premium.php';

			return $ig_es_tracker::is_plugin_activated( $plugin );
		}

		/**
		 * Check whether ES Premium Installed
		 *
		 * @return mixed
		 *
		 * @since 4.4.8
		 */
		public function is_premium_installed() {
			global $ig_es_tracker;

			$plugin = 'email-subscribers-premium/email-subscribers-premium.php';

			return $ig_es_tracker::is_plugin_installed( $plugin );
		}

		/**
		 * Check whether ES Pro features can be upselled or not
		 *
		 * @return bool
		 *
		 * @since 4.6.1
		 */
		public function can_upsell_features( $show_for_plans = array() ) {
			$es_current_plan = $this->get_plan();
			if ( in_array( $es_current_plan, $show_for_plans ) ) { 
				return true;
			}
			return false;
		}

		/**
		 * Get all ES admin screens
		 *
		 * @return array|mixed|void
		 *
		 * @since 4.3.8
		 */
		public function get_es_admin_screens() {

			// TODO: Can be updated with a version check when https://core.trac.wordpress.org/ticket/18857 is fixed
			$prefix = $this->get_admin_page_prefix();

			$screens = array(
				'es_template',
				'edit-es_template',
				'toplevel_page_es_dashboard',
				'admin_page_go_to_icegram',
				"{$prefix}_page_es_subscribers",
				"{$prefix}_page_es_lists",
				"{$prefix}_page_es_forms",
				"{$prefix}_page_es_campaigns",
				"{$prefix}_page_es_workflows",
				"{$prefix}_page_es_newsletters",
				"{$prefix}_page_es_notifications",
				"{$prefix}_page_es_reports",
				"{$prefix}_page_es_settings",
				"{$prefix}_page_es_tools",
				"{$prefix}_page_es_general_information",
				"{$prefix}_page_es_pricing",
				"{$prefix}_page_es_sequence",


			);

			$screens = apply_filters( 'ig_es_admin_screens', $screens );

			return $screens;
		}

		/**
		 * Is es admin screen?
		 *
		 * @param string $screen_id Admin screen id
		 *
		 * @return bool
		 *
		 * @since 4.3.8
		 */
		public function is_es_admin_screen( $screen_id = '' ) {

			$current_screen_id = $this->get_current_screen_id();
			// Check for specific admin screen id if passed.
			if ( ! empty( $screen_id ) ) {
				if ( $current_screen_id === $screen_id ) {
					return true;
				} else {
					return false;
				}
			}

			$es_admin_screens = $this->get_es_admin_screens();
			if ( in_array( $current_screen_id, $es_admin_screens ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get Current Screen Id
		 *
		 * @return string
		 *
		 * @since 4.3.8
		 */
		public function get_current_screen_id() {

			$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

			if ( ! $current_screen instanceof WP_Screen ) {
				return '';
			}

			$current_screen = get_current_screen();

			return ( $current_screen ? $current_screen->id : '' );
		}

		/**
		 * Check if the current user is admin
		 *
		 * @return bool
		 *
		 * @since 4.4.2
		 */
		public function is_current_user_administrator() {
			return current_user_can( 'administrator' );
		}

		/**
		 * Register Widget Class
		 *
		 * @since 4.0.0
		 */
		public function register_es_widget() {
			register_widget( 'ES_Form_Widget' );
		}

		/**
		 * Log Fatal Errors on Shutdown
		 *
		 * @since 4.3.1
		 */
		public function log_errors() {
			$error = error_get_last();
			if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
				$logger = get_ig_logger();
				$logger->critical(
					/* translators: 1: Error message 2: File name  3: Line number */
					sprintf( esc_html__( '%1$s in %2$s on line %3$s', 'email-subscribers' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
					array(
						'source' => 'fatal-errors',
					)
				);

				do_action( 'ig_es_shutdown_error', $error );
			}
		}

		/**
		 * Return a true instance of a class
		 *
		 * @return Email_Subscribers
		 *
		 * @since 4.2.1
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Email_Subscribers ) ) {
				global $wpdb, $ig_es_feedback, $wpbd;

				$wpbd = $wpdb;

				self::$instance = new Email_Subscribers();

				require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-activator.php';
				require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-deactivator.php';

				// Start-IG-Code.
				require_once plugin_dir_path( __FILE__ ) . 'libraries/action-scheduler/action-scheduler.php';
				// End-IG-Code.

				self::$instance->email_subscribers = 'email-subscribers';

				self::$instance->define_constants();
				self::$instance->load_dependencies();
				self::$instance->set_locale();
				self::$instance->define_admin_hooks();
				self::$instance->define_public_hooks();

				self::$instance->logger = get_ig_logger();

				self::$instance->mailer = new ES_Mailer();

				add_action( 'widgets_init', array( self::$instance, 'register_es_widget' ) );

				self::$instance->queue_db          = new ES_DB_Queue();
				self::$instance->actions_db        = new ES_DB_Actions();
				self::$instance->campaigns_db      = new ES_DB_Campaigns();
				self::$instance->lists_db          = new ES_DB_Lists();
				self::$instance->forms_db          = new ES_DB_Forms();
				self::$instance->contacts_db       = new ES_DB_Contacts();
				self::$instance->lists_contacts_db = new ES_DB_Lists_Contacts();
				self::$instance->blocked_emails_db = new ES_DB_Blocked_Emails();
				self::$instance->links_db          = new ES_DB_Links();
				self::$instance->queue             = new ES_Queue();
				self::$instance->actions           = new ES_Actions();
				self::$instance->cron              = new ES_Cron();
				self::$instance->compatibiloty     = new ES_Compatibility();
				self::$instance->workflows_db      = new ES_DB_Workflows();
				self::$instance->carts_db      	   = new IG_ES_DB_WC_Cart();
				self::$instance->trial             = new IG_ES_Trial();

				// Start-IG-Code.
				if ( is_admin() ) {
					$ig_es_feedback_class = 'IG_Feedback_V_' . str_replace( '.', '_', IG_ES_FEEDBACK_TRACKER_VERSION );

					$name         = 'Email Subscribers';
					$plugin       = 'email-subscribers';
					$plugin_abbr  = 'ig_es';
					$event_prefix = 'esfree.';
					if ( self::$instance->is_pro() ) {
						$name         = 'Email Subscribers PRO';
						$plugin       = 'email-subscribers-newsletters-pro';
						$event_prefix = 'espro.';
					} elseif ( self::$instance->is_starter() ) {
						$name         = 'Email Subscribers Starter';
						$plugin       = 'email-subscribers-newsletters-starter';
						$event_prefix = 'esstarter.';
					}

					$ig_es_feedback = new $ig_es_feedback_class( $name, $plugin, $plugin_abbr, $event_prefix, false );

					$ig_es_feedback->render_deactivate_feedback();
				}
				// End-IG-Code.

				add_action( 'admin_init', array( self::$instance, 'add_admin_notice' ) );
				add_action( 'admin_init', array( self::$instance, 'check_trial_optin_consent' ) );
				add_filter( 'ig_es_service_request_data', array( self::$instance, 'add_service_authentication_data' ) );
				add_filter( 'ig_es_plan', array( self::$instance, 'add_trial_plan' ) );

				if ( ! post_type_exists( 'es_template' ) ) {
					add_action( 'init', array( 'Email_Subscribers_Activator', 'register_email_templates' ) );
				}

			}

			return self::$instance;
		}

		/**
		 * Method to get plugin plan
		 * 
		 * @return string $plan
		 * 
		 * @since 4.6.1
		 */
		public function get_plan() {

			$plan = apply_filters( 'ig_es_plan', 'lite' );
			
			return $plan;
		}

		/**
		 * Method to add trial plan
		 * 
		 * @param string $plan
		 * 
		 * @return string $plan
		 * 
		 * @since 4.6.1
		 */
		public function add_trial_plan( $plan = '' ) {

			if ( $this->is_trial_valid() ) {
				$plan = 'trial';
			}

			return $plan;
		}

		/**
		 * Method to add ES service authentication data.
		 * 
		 * @param array $request_data Service request data.
		 * 
		 * @return array $request_data
		 * 
		 * @since 4.6.1
		 */
		public function add_service_authentication_data( $request_data = array() ) {

			$es_plan = $this->get_plan();
			
			if ( ! empty( $es_plan ) ) {
				$request_data['plan'] = $es_plan;
			}
			
			if ( $this->is_trial() ) {

				$trial_started_at = get_option( 'ig_es_trial_started_at' );
				$site_url	      = site_url();

				$request_data['trial_started_at'] = $trial_started_at;
				$request_data['site_url']         = $site_url;
			}

			return $request_data;
		}

		/**
		 * Method to check if user has given optin consent.
		 * 
		 * @since 4.6.1
		 */
		public function check_trial_optin_consent() {

			// Check optin consent only if not already trial or premium. 
			if ( ! ( $this->is_trial() || $this->is_premium() ) ) {
				$trial_consent = ig_es_get_request_data( 'ig_es_trial_consent', '' );
				if ( ! empty( $trial_consent ) ) {
					check_admin_referer( 'ig_es_trial_consent' );
					$this->add_trial_data( $trial_consent );
					update_option( 'ig_es_trial_consent', $trial_consent, false );
					ES_Admin_Notices::remove_notice( 'trial_consent' );
					$referer = wp_get_referer();
					wp_safe_redirect( $referer );
				}
			}
		}
		
		/**
		 * Method to add trial related data.
		 * 
		 * @param string $is_trial.
		 * 
		 * @return int $trial_started_at
		 * 
		 * @since 4.6.1
		 */
		public function add_trial_data( $is_trial = '', $trial_started_at = 0 ) {

			$is_trial = ! empty( $is_trial ) ? $is_trial : 'yes';
			update_option( 'ig_es_is_trial', $is_trial, false );
			
			if ( 'yes' === $is_trial ) {
				$trial_started_at = ! empty( $trial_started_at ) ? $trial_started_at : time();
				update_option( 'ig_es_trial_started_at', $trial_started_at, false );
			}
		}

		/**
		 * Method to get admin menu title.
		 * 
		 * @return string $menu_title Admin menu title
		 * 
		 * @since 4.6.3
		 */ 
		public function get_admin_menu_title() {
			
			global $ig_es_tracker;
			
			$menu_title = __( 'Email Subscribers', 'email-subscribers' );

			if ( 'woo' === IG_ES_PLUGIN_PLAN ) {
				$menu_title = __( 'Icegram', 'email-subscribers' );

				$icegram_lite_plugin_slug = 'icegram/icegram.php';
				$icegram_premium_plugin_slug = 'icegram-engage/icegram-engage.php';

				$icegram_lite_installed    = $ig_es_tracker::is_plugin_installed( $icegram_lite_plugin_slug );
				$icegram_premium_installed = $ig_es_tracker::is_plugin_installed( $icegram_premium_plugin_slug );

				// Change Woo Plugin's menu name if Icegram or Icegram premium plugin is installed on the site.
				if ( $icegram_lite_installed || $icegram_premium_installed ) {
					$menu_title = __( 'Icegram WC', 'email-subscribers' );
				}
			}

			return $menu_title;
		}

		/**
		 * Method to get admin menu page prefix.
		 * 
		 * @return string $page_prefix Admin menu page prefix.
		 * 
		 * @since 4.6.3
		 */
		public function get_admin_page_prefix() {
			
			$menu_title  = $this->get_admin_menu_title();
			$page_prefix = sanitize_title( $menu_title );
			
			return $page_prefix;
		}

		/**
		 * Check whether constant definition is enabled or not.
		 *
		 * @return bool
		 * 
		 * @since 4.7.0
		 */
		public function is_const_enabled() {

			$const_enabled = defined( 'IG_ES_CONSTANT_ENABLED' ) && IG_ES_CONSTANT_ENABLED === true;

			return $const_enabled;
		}

		/**
		 * Check if mailer setting is defined through constant
		 *
		 * @param string $group
		 * @param string $key
		 *
		 * @return bool
		 *
		 * @since 4.7.0
		 */
		public function is_const_defined( $group, $key ) {

			if ( ! $this->is_const_enabled() ) {
				return false;
			}

			$return = false;

			switch ( $group ) {
				case 'pepipost':
					switch ( $key ) {
						case 'api_key':
							$return = defined( 'IG_ES_PEPIPOST_API_KEY' ) && IG_ES_PEPIPOST_API_KEY;
							break;
					}

					break;
				case 'smtp':
					switch ( $key ) {
						case 'host':
							$return = defined( 'IG_ES_SMTP_HOST' ) && IG_ES_SMTP_HOST;
							break;
						case 'encryption':
							$return = defined( 'IG_ES_SMTP_ENCRYPTION' ) && IG_ES_SMTP_ENCRYPTION;
							break;
						case 'port':
							$return = defined( 'IG_ES_SMTP_PORT' ) && IG_ES_SMTP_PORT;
							break;
						case 'authentication':
							$return = defined( 'IG_ES_SMTP_AUTHENTICATION' ) && IG_ES_SMTP_AUTHENTICATION;
							break;
						case 'username':
							$return = defined( 'IG_ES_SMTP_USERNAME' ) && IG_ES_SMTP_USERNAME;
							break;
						case 'password':
							$return = defined( 'IG_ES_SMTP_PASSWORD' ) && IG_ES_SMTP_PASSWORD;
							break;
					}

					break;

				case 'Amazon_SES':
					switch ( $key ) {
						case 'access_key_id':
							$return = defined( 'IG_ES_AMAZONSES_ACCESS_KEY_ID' ) && IG_ES_AMAZONSES_ACCESS_KEY_ID;
							break;
						case 'secret_access_key':
							$return = defined( 'IG_ES_AMAZONSES_SECRET_ACCESS_KEY' ) && IG_ES_AMAZONSES_SECRET_ACCESS_KEY;
							break;
						case 'region':
							$return = defined( 'IG_ES_AMAZONSES_REGION' ) && IG_ES_AMAZONSES_REGION;
							break;
					}

					break;

				case 'mailgun':
					switch ( $key ) {
						case 'private_api_key':
							$return = defined( 'IG_ES_MAILGUN_PRIVATE_API_KEY' ) && IG_ES_MAILGUN_PRIVATE_API_KEY;
							break;
						case 'domain_name':
							$return = defined( 'IG_ES_MAILGUN_DOMAIN_NAME' ) && IG_ES_MAILGUN_DOMAIN_NAME;
							break;
						case 'region':
							$return = defined( 'IG_ES_MAILGUN_REGION' ) && IG_ES_MAILGUN_REGION;
							break;
					}

					break;
				
				case 'sparkpost':
					switch ( $key ) {
						case 'api_key':
							$return = defined( 'IG_ES_SPARKPOST_API_KEY' ) && IG_ES_SPARKPOST_API_KEY;
							break;
						case 'region':
							$return = defined( 'IG_ES_SPARKPOST_REGION' ) && IG_ES_SPARKPOST_REGION;
							break;
					}

					break;

				case 'sendgrid':
					switch ( $key ) {
						case 'api_key':
							$return = defined( 'IG_ES_SENDGRID_API_KEY' ) && IG_ES_SENDGRID_API_KEY;
							break;
					}

					break;

				
			}

			return $return;
		}

		/**
		 * Process the options values through the constants check.
		 * If we have defined associated constant - use it instead of a DB value.
		 *
		 * @param string $group
		 * @param string $key
		 * @param mixed $value
		 *
		 * @since 4.7.0
		 *
		 * @return mixed
		 */
		public function get_const_value( $group, $key, $value = '' ) {

			if ( ! $this->is_const_enabled() ) {
				return $value;
			}

			$return = null;

			switch ( $group ) {

				case 'smtp':
					switch ( $key ) {
						case 'host':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SMTP_HOST : $value;
							break;
						case 'encryption':
							$return = $this->is_const_defined( $group, $key ) ? ( IG_ES_SMTP_ENCRYPTION === '' ? 'none' : IG_ES_SMTP_ENCRYPTION ) : $value;
							break;
						case 'port':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SMTP_PORT : $value;
							break;
						case 'authentication':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SMTP_AUTHENTICATION : $value;
							break;
						case 'username':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SMTP_USERNAME : $value;
							break;
						case 'password':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SMTP_PASSWORD : $value;
							break;
					}

					break;

				case 'Amazon_SES':
					switch ( $key ) {
						case 'access_key_id':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_AMAZONSES_ACCESS_KEY_ID : $value;
							break;
						case 'secret_access_key':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_AMAZONSES_SECRET_ACCESS_KEY : $value;
							break;
						case 'region':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_AMAZONSES_REGION : $value;
							break;
					}

					break;

				case 'mailgun':
					switch ( $key ) {
						case 'private_api_key':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_MAILGUN_PRIVATE_API_KEY : $value;
							break;
						case 'domain_name':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_MAILGUN_DOMAIN_NAME : $value;
							break;
						case 'region':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_MAILGUN_REGION : $value;
							break;
					}

					break;

				case 'sendgrid':
					switch ( $key ) {
						case 'api_key':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SENDGRID_API_KEY : $value;
							break;
					}

					break;

				case 'sparkpost':
					switch ( $key ) {
						case 'api_key':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SPARKPOST_API_KEY : $value;
							break;
						case 'region':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_SPARKPOST_REGION : $value;
							break;
					}

					break;

				case 'pepipost':
					switch ( $key ) {
						case 'api_key':
							$return = $this->is_const_defined( $group, $key ) ? IG_ES_PEPIPOST_API_KEY : $value;
							break;
					}

					break;

				default:
					// Always return the default value if nothing from above matches the request.
					$return = $value;
			}

			return $return;
		}

		/**
		 * Get related constant name for given key/group pair
		 *
		 * @param string $group
		 * @param string $key
		 *
		 * @since 4.7.0
		 *
		 * @return mixed
		 */
		public function get_const_name( $group, $key ) {

			$return = '';
			
			if ( $this->is_const_enabled() ) {
				switch ( $group ) {
	
					case 'smtp':
						switch ( $key ) {
							case 'host':
								$return = 'IG_ES_SMTP_HOST';
								break;
							case 'port':
								$return = 'IG_ES_SMTP_PORT';
								break;
							case 'encryption':
								$return = 'IG_ES_SMTP_ENCRYPTION';
								break;
							case 'authentication':
								$return = 'IG_ES_SMTP_AUTHENTICATION';
								break;
							case 'username':
								$return = 'IG_ES_SMTP_USERNAME';
								break;
							case 'password':
								$return = 'IG_ES_SMTP_PASSWORD';
								break;
						}
	
						break;
	
					case 'Amazon_SES':
						switch ( $key ) {
							case 'access_key_id':
								$return = 'IG_ES_AMAZONSES_ACCESS_KEY_ID';
								break;
							case 'secret_access_key':
								$return = 'IG_ES_AMAZONSES_SECRET_ACCESS_KEY';
								break;
							case 'region':
								$return = 'IG_ES_AMAZONSES_REGION';
								break;
						}
	
						break;
	
					case 'mailgun':
						switch ( $key ) {
							case 'private_api_key':
								$return = 'IG_ES_MAILGUN_PRIVATE_API_KEY';
								break;
							case 'domain_name':
								$return = 'IG_ES_MAILGUN_DOMAIN_NAME';
								break;
							case 'region':
								$return = 'IG_ES_MAILGUN_REGION';
								break;
						}
	
						break;
	
					case 'sendgrid':
						switch ( $key ) {
							case 'api_key':
								$return = 'IG_ES_SENDGRID_API_KEY';
								break;
						}
	
						break;
	
					case 'sparkpost':
						switch ( $key ) {
							case 'api_key':
								$return = 'IG_ES_SPARKPOST_API_KEY';
								break;
							case 'region':
								$return = 'IG_ES_SPARKPOST_REGION';
								break;
						}
	
						break;
	
					case 'pepipost':
						switch ( $key ) {
							case 'api_key':
								$return = 'IG_ES_PEPIPOST_API_KEY';
								break;
						}
	
						break;
	
					default:
						$return = '';
				}
			}

			return $return;
		}

		/**
		 * Display a message of a constant that was set.
		 *
		 * @param string $group Group name.
		 * @param string $key Key name.
		 *
		 * @return $message
		 * 
		 * @since 4.7.0
		 */
		public function get_const_set_message( $group, $key ) {
			$constant = ES()->get_const_name( $group, $key );
			ob_start();
			?>
			<?php
			printf( /* translators: %1$s - constant that was used */
				esc_html__( 'Value was set using constant %1$s', 'email-subscribers' ),
				'<code>' . esc_attr( $constant ) . '</code>'
			);
			?>
			<br/>
			<?php
			$message = ob_get_clean();
			return $message;
		}
	}
}
