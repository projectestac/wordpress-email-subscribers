<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ES_Install' ) ) {

	/**
	 * ES_Install Class.
	 */
	class ES_Install {

		/**
		 * Background update class.
		 *
		 * @var object
		 */
		public static $logger;

		/**
		 * Added Logger Context
		 *
		 * @since 4.2.0
		 * @var array
		 *
		 */
		public static $logger_context = array(
			'source' => 'ig_es_db_updates'
		);

		/**
		 * DB updates and callbacks that need to be run per version.
		 *
		 * @since 4.0.0
		 * @var array
		 *
		 */
		private static $db_updates = array(

			'3.2.0' => array(
				'ig_es_update_320_add_sync_option',
				'ig_es_update_320_db_version'
			),

			'3.2.7' => array(
				'ig_es_update_327_change_email_type',
				'ig_es_update_327_db_version'
			),

			'3.3.0' => array(
				'ig_es_update_330_import_options',
				'ig_es_update_330_db_version'
			),

			'3.3.6' => array(
				'ig_es_update_336_add_template_slug',
				'ig_es_update_336_db_version'
			),

			'3.4.0' => array(
				'ig_es_update_340_migrate_templates_to_cpt',
				'ig_es_update_340_migrate_keywords',
				'ig_es_update_340_db_version'
			),

			'3.5.16' => array(
				'ig_es_update_3516_create_subscribers_ips_table',
				'ig_es_update_3516_db_version'
			),


			'4.0.0' => array(
				/**
				 * - Create Tables
				 * - Import Options
				 * - Get unique Lists/ Groups from es_emaillist table
				 *  - Create new lists into ig_lists table
				 *  - Get subscribers from emaillist table in batch and import it into ig_contacts table
				 *  - Add list entry into ig_lists_contacts table
				 *  - Get all Post Notifications from es_notification table and get newsletters from es_sentdetails table and import into campaigns and ig_mailing_queue table
				 *  - Get all data from es_deliverreport and import into ig_sending_queue table
				 *  - Import all data from es_subscriber_ips to ig_contacts_ips
				 */
				'ig_es_update_400_delete_tables',
				'ig_es_update_400_create_tables',
				'ig_es_update_400_import_options',
				'ig_es_update_400_migrate_lists',
				'ig_es_update_400_migrate_subscribers',
				'ig_es_update_400_migrate_post_notifications',
				'ig_es_update_400_migrate_notifications',
				'ig_es_update_400_migrate_group_selectors_forms',
				'ig_es_update_400_db_version'
			),

			'4.0.1' => array(
				'ig_es_update_401_migrate_newsletters',
				'ig_es_update_401_db_version'
			),

			'4.0.2' => array(
				'ig_es_update_402_migrate_post_notification_es_template_type',
				'ig_es_update_402_db_version'
			),

			'4.0.3' => array(
				'ig_es_update_403_alter_campaigns_table',
				'ig_es_update_403_alter_mailing_queue_table',
				'ig_es_update_403_db_version'
			),

			'4.0.5' => array(
				'ig_es_update_405_alter_forms_table',
				'ig_es_update_405_alter_lists_table',
				'ig_es_update_405_migrate_widgets',
				'ig_es_update_405_db_version'
			),

			'4.0.10' => array(
				'ig_es_update_4010_db_version'
			),

			'4.0.11' => array(
				'ig_es_update_4011_migrate_newsletter_es_template_type',
				'ig_es_update_4011_update_campaign_id_in_mailing_queue',
				'ig_es_update_4011_db_version'
			),

			'4.0.15' => array(
				'ig_es_update_4015_alter_blocked_emails_table',
				'ig_es_update_4015_db_version',
			),

			'4.1.1' => array(
				'ig_es_update_411_alter_contacts_table',
				'ig_es_update_411_db_version'
			),

			'4.1.7' => array(
				'ig_es_update_417_alter_campaigns_table',
				'ig_es_update_417_alter_mailing_queue_table',
				'ig_es_update_417_db_version'
			),

			'4.1.13' => array(
				'ig_es_update_4113_migrate_categories_in_campaigns_table',
				'ig_es_update_4113_create_files',
				'ig_es_update_4113_db_version'
			),
			'4.1.15' => array(
				'ig_es_update_4115_add_form_submission_option',
				'ig_es_update_4115_migrate_db_update_history',
				'ig_es_update_4115_db_version'
			),

			'4.2.0' => array(
				'ig_es_update_420_alter_campaigns_table',
				'ig_es_update_420_create_tables',
				'ig_es_update_420_migrate_mailer_options',
				'ig_es_update_420_db_version'
			),

			'4.2.1' => array(
				'ig_es_update_421_drop_tables',
				'ig_es_update_421_create_tables',
				'ig_es_update_421_db_version'
			),

			'4.2.4' => array(
				'ig_es_update_424_drop_tables',
				'ig_es_update_424_create_tables',
				'ig_es_update_424_db_version'
			),

			'4.3.0' => array(
				'ig_es_update_430_alter_campaigns_table',
				'ig_es_update_430_db_version'
			),

			'4.3.1' => array(
				'ig_es_update_431_set_default_permissions',
				'ig_es_update_431_permanently_delete_lists',
				'ig_es_update_431_permanently_delete_forms',
				'ig_es_update_431_disable_autoload_options',
				'ig_es_update_431_db_version'
			),

			'4.3.2' => array(
				'ig_es_update_432_import_bfcm_templates',
				'ig_es_update_432_db_version'
			),

			'4.3.4' => array(
				'ig_es_update_434_permanently_delete_campaigns',
				'ig_es_update_434_db_version'
			),

			'4.4.1' => array(
				'ig_es_update_441_create_tables',
				'ig_es_update_441_migrate_audience_sync_settings',
				'ig_es_update_441_db_version'
			),

			'4.4.2' => array(
				'ig_es_update_442_set_workflows_default_permission',
				'ig_es_update_442_db_version'
			),

			'4.4.9' => array(
				'ig_es_update_449_create_tables',
				'ig_es_update_449_db_version'
			),

			'4.4.10' => array(
				'ig_es_update_4410_load_templates',
				'ig_es_update_4410_db_version'
			),

			'4.5.0' => array(
				'ig_es_update_450_alter_actions_table',
				'ig_es_update_450_db_version'
			),

			'4.5.7' => array(
				'ig_es_update_457_alter_list_table',
				'ig_es_update_457_add_list_hash',
				'ig_es_update_457_db_version',
			),

			'4.6.3' => array(
				'ig_es_update_463_alter_contacts_table',
				'ig_es_migrate_ip_from_list_contacts_to_contacts_table',
				'ig_es_update_463_db_version',
			),

			'4.6.5' => array(
				'ig_es_update_465_create_tables',
				'ig_es_update_465_db_version',
			),
			'4.6.6' => array(
				'ig_es_update_466_create_temp_import_table',
				'ig_es_update_466_db_version',
			),
			'4.6.7' => array(
				'ig_es_update_467_alter_contacts_table',
				'ig_es_add_country_code_to_contacts_table',
				'ig_es_update_467_db_version',
			),
			'4.6.8' => array(
				'ig_es_update_468_create_unsubscribe_feedback_table',
				'ig_es_update_468_db_version',
			),
			'4.6.9' => array(
				'ig_es_update_469_alter_wc_guests_table',
				'ig_es_update_469_db_version',
			),
			'4.6.13' => array(
				'ig_es_migrate_4613_sequence_list_settings_into_campaign_rules',
				'ig_es_update_4613_db_version',
			)

		);

		/**
		 * Init Install/ Update Process
		 *
		 * @since 4.0.0
		 */
		public static function init() {

			if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

				self::$logger = get_ig_logger();

				add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
				add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
			}
		}

		/**
		 * Install if required
		 *
		 * @since 4.0.0
		 */
		public static function check_version() {

			$current_db_version = get_option( 'ig_es_db_version', '1.0.0' );

			// Get latest available DB update version
			$latest_db_version_to_update = self::get_latest_db_version_to_update();

			if ( version_compare( $current_db_version, $latest_db_version_to_update, '<' ) ) {

				self::$logger->info( 'Require to run updater..Start Installation/ Update process', self::$logger_context );

				self::install();
			}
			// Do we need to load templates?
			self::load_templates();
		}

		/**
		 * Update
		 *
		 * @since 4.0.0
		 */
		public static function install_actions() {
			if ( ! empty( $_GET['do_update_ig_es'] ) ) {
				check_admin_referer( 'ig_es_db_update', 'ig_es_db_update_nonce' );
				$from_db_version = ! empty( $_GET['from_db_version'] ) ? sanitize_text_field( $_GET['from_db_version'] ) : '';

				self::delete_update_transient();

				if ( ! empty( $from_db_version ) ) {
					self::$logger->info( sprintf( 'Forcefully update database from: %s', $from_db_version ), self::$logger_context );

					self::update_db_version( $from_db_version );
				}

				self::update( true );

				//ES_Admin_Notices::add_notice( 'update' );
			}

			if ( ! empty( $_GET['force_update_ig_es'] ) ) {
				check_admin_referer( 'ig_es_force_db_update', 'ig_es_force_db_update_nonce' );
				self::update();
				//ES_Admin_Notices::add_notice( 'update' );
				wp_safe_redirect( admin_url( 'admin.php?page=es_settings' ) );
				exit;
			}
		}

		/**
		 * Begin Installation
		 *
		 * @since 4.0.0
		 */
		public static function install() {


			if ( ! is_blog_installed() ) {
				self::$logger->error( 'Blog is not installed.', self::$logger_context );

				return;
			}

			// Check if we are not already running this routine.
			if ( 'yes' === get_transient( 'ig_es_installing' ) ) {
				self::$logger->error( 'Installation process is running..', self::$logger_context );

				return;
			}

			if ( self::is_new_install() ) {

				self::$logger->info( 'It seems new Email Subscribers. Start Installation process.', self::$logger_context );

				// If we made it till here nothing is running yet, lets set the transient now.
				set_transient( 'ig_es_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				ig_es_maybe_define_constant( 'IG_ES_INSTALLING', true );

				// Create Files
				self::create_files();

				self::$logger->info( 'Create Files.', self::$logger_context );

				// Create Tables
				self::create_tables();

				self::$logger->info( 'Create Tables.', self::$logger_context );

				// Create Default Option
				self::create_options();

				self::$logger->info( 'Create Options.', self::$logger_context );

				self::load_templates();

				self::$logger->info( 'Load readymade template', self::$logger_context );

				self::$logger->info( 'Installation Complete.', self::$logger_context );
			}
			self::maybe_update_db_version();
			delete_transient( 'ig_es_installing' );

		}

		/**
		 * Delete Update Transient
		 *
		 * @since 4.0.0
		 */
		public static function delete_update_transient() {
			global $wpdb;

			delete_option( 'ig_es_update_processed_tasks' );
			delete_option( 'ig_es_update_tasks_to_process' );

			$transient_like               = $wpdb->esc_like( '_transient_ig_es_update_' ) . '%';
			$updating_like                = $wpdb->esc_like( '_transient_ig_es_updating' ) . '%';
			$last_sent_queue_like         = '%' . $wpdb->esc_like( '_last_sending_queue_batch_run' ) . '%';
			$running_migration_queue_like = '%' . $wpdb->esc_like( '_running_migration_for_' ) . '%';
			$db_migration_queue_like      = '%' . $wpdb->esc_like( 'ig_es_updater_batch_' ) . '%';

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", $transient_like, $updating_like, $last_sent_queue_like, $running_migration_queue_like, $db_migration_queue_like ) );

		}

		/**
		 * Is this new Installation?
		 *
		 * @return bool
		 *
		 * @since 4.0.0
		 */
		public static function is_new_install() {
			/**
			 * We are storing ig_es_db_version if it's new installation.
			 *
			 * If we found 'current_sa_email_subscribers_db_version' option, which means it's a
			 * migration from ES 3.5.x
			 *
			 */
			return is_null( get_option( 'ig_es_db_version', null ) ) && is_null( get_option( 'current_sa_email_subscribers_db_version', null ) ) && is_null( get_option( 'email-subscribers', null ) );
		}

		/**
		 * Get latest db version based on available updates.
		 *
		 * @return mixed
		 *
		 * @since 4.0.0
		 */
		public static function get_latest_db_version_to_update() {
			$updates         = self::get_db_update_callbacks();
			$update_versions = array_keys( $updates );
			usort( $update_versions, 'version_compare' );

			return end( $update_versions );
		}

		/**
		 * Require DB updates?
		 *
		 * @return bool
		 *
		 * @since 4.0.0
		 */
		private static function needs_db_update() {
			self::$logger->info( 'Needs DB Update?', self::$logger_context );

			$current_db_version = get_ig_es_db_version();

			$latest_db_version_to_update = self::get_latest_db_version_to_update();

			self::$logger->info( sprintf( 'Current DB Version: %s', $current_db_version ), self::$logger_context );

			return ! is_null( $current_db_version ) && version_compare( $current_db_version, $latest_db_version_to_update, '<' );
		}

		/**
		 * Check whether database update require? If require do update.
		 *
		 * @since 4.0.0
		 */
		private static function maybe_update_db_version() {
			if ( self::needs_db_update() ) {
				if ( apply_filters( 'ig_es_enable_auto_update_db', true ) ) {
					self::$logger->info( 'Database update require. Start updating database', self::$logger_context );
					self::update();
				} else {
					self::$logger->info( 'Show update notice.', self::$logger_context );
					//ES_Admin_Notices::add_notice( 'update' );
				}
			} else {
				self::$logger->info( 'Database is upto date' );
			}
		}

		/**
		 * Get all database updates
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public static function get_db_update_callbacks() {
			return self::$db_updates;
		}

		/**
		 * Do database update.
		 *
		 * @param bool $force
		 *
		 * @since 4.0.0
		 */
		private static function update( $force = false ) {

			self::$logger->info( 'Do Update....', self::$logger_context );

			// Check if we are not already running this routine.
			if ( ! $force && 'yes' === get_transient( 'ig_es_updating' ) ) {
				self::$logger->info( '********* Update is already running..... ****** ', self::$logger_context );

				return;
			}

			set_transient( 'ig_es_updating', 'yes', MINUTE_IN_SECONDS * 5 );

			$current_db_version = get_ig_es_db_version();

			$tasks_to_process = get_option( 'ig_es_update_tasks_to_process', array() );

			// Get all tasks processed
			$processed_tasks = get_option( 'ig_es_update_processed_tasks', array() );

			self::$logger->info( sprintf( 'Current IG ES DB Version: %s', $current_db_version ), self::$logger_context );

			// Get al tasks to process
			$tasks = self::get_db_update_callbacks();

			if ( count( $tasks ) > 0 ) {

				foreach ( $tasks as $version => $update_callbacks ) {

					if ( version_compare( $current_db_version, $version, '<' ) ) {
						foreach ( $update_callbacks as $update_callback ) {
							if ( ! in_array( $update_callback, $tasks_to_process ) && ! in_array( $update_callback, $processed_tasks ) ) {
								self::$logger->info( sprintf( '[Queue] %s', $update_callback ), self::$logger_context );
								$tasks_to_process[] = $update_callback;
							} else {
								self::$logger->info( sprintf( 'Task "%s" is already processed or is in queue', $update_callback ), self::$logger_context );
							}
						}
					}
				}
			}

			if ( count( $tasks_to_process ) > 0 ) {

				self::$logger->info( 'Yes, we have tasks to process', self::$logger_context );

				update_option( 'ig_es_update_tasks_to_process', $tasks_to_process );

				self::dispatch();

			} else {
				self::$logger->info( 'Sorry, we do not have any tasks to process', self::$logger_context );
				delete_transient( 'ig_es_updating' );
			}

		}

		/**
		 * Dispatch database updates.
		 *
		 * @since 4.0.0
		 */
		public static function dispatch() {

			$logger = get_ig_logger();

			$batch = get_option( 'ig_es_update_tasks_to_process', array() );

			$logger->info( '--------------------- Started To Run Task Again---------------------', self::$logger_context );

			if ( count( $batch ) > 0 ) {

				// We may require lots of memory
				// Add filter to increase memory limit
				add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

				wp_raise_memory_limit( 'ig_es' );

				// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
				remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

				// It may take long time to process database update.
				// So, increase execution time
				@set_time_limit( 360 );

				foreach ( $batch as $key => $value ) {

					$is_value_exists = true;
					//$task_transient = $value . '_processed';
					$ig_es_update_processed_tasks = get_option( 'ig_es_update_processed_tasks', array() );
					$task                         = false; // By default it's set to false

					// Check whether the tasks is already processed? If not, process it.
					if ( ! in_array( $value, $ig_es_update_processed_tasks ) ) {
						$is_value_exists = false;
						$logger->info( sprintf( '[Processing] %s', $value ), self::$logger_context );
						$task = (bool) self::task( $value );
						$logger->info( sprintf( '[Processed] %s', $value ), self::$logger_context );
					} else {
						$logger->info( sprintf( "Task '%s' is already processed. Remove it from list", $value ), self::$logger_context );
						unset( $batch[ $key ] );
					}

					if ( false === $task ) {

						if ( ! $is_value_exists ) {
							$ig_es_update_processed_tasks[] = $value;
							update_option( 'ig_es_update_processed_tasks', $ig_es_update_processed_tasks );
						}

						unset( $batch[ $key ] );
					}

				}

				update_option( 'ig_es_update_tasks_to_process', $batch );
			}

			//Delete update transient
			delete_transient( 'ig_es_updating' );
		}

		/**
		 * Run individual database update.
		 *
		 * @param $callback
		 *
		 * @return bool|callable
		 *
		 * @since 4.0.0
		 */
		public static function task( $callback ) {

			$logger = get_ig_logger();

			include_once dirname( __FILE__ ) . '/upgrade/es-update-functions.php';

			$result = false;

			if ( is_callable( $callback ) ) {
				$logger->info( sprintf( '--- Running Task - %s', $callback ), self::$logger_context );

				$result = (bool) call_user_func( $callback );

				if ( $result ) {
					$logger->info( sprintf( '%s callback needs to run again', $callback ), self::$logger_context );
				} else {
					$logger->info( sprintf( '--- Finished Task - %s ', $callback ), self::$logger_context );
				}
			} else {
				$logger->notice( sprintf( '--- Could not find %s callback', $callback ), self::$logger_context );
			}

			return $result ? $callback : false;
		}

		/**
		 * Update DB Version & DB Update history
		 *
		 * @param null $version
		 *
		 * @since 4.0.0
		 */
		public static function update_db_version( $version = null ) {

			$latest_db_version_to_update = self::get_latest_db_version_to_update();

			update_option( 'ig_es_db_version', is_null( $version ) ? $latest_db_version_to_update : $version );

			if ( ! is_null( $version ) ) {
				$db_update_history_option = 'db_update_history';

				$ig_es_db_update_history_data = ES_Common::get_ig_option( $db_update_history_option, array() );

				$ig_es_db_update_history_data[ $version ] = ig_get_current_date_time();

				ES_Common::set_ig_option( $db_update_history_option, $ig_es_db_update_history_data );
			}
		}

		/**
		 * Create default options while installing
		 *
		 * @since 4.0.0
		 */
		private static function create_options() {
			$options = self::get_options();
			foreach ( $options as $option => $values ) {
				add_option( $option, $values['default'], '', false );
			}
		}

		/**
		 * Get sender details to set in from email and name
		 *
		 * @return array
		 *
		 * @since 4.3.6
		 */
		public static function get_sender_details() {
			global $ig_es_tracker;
			$active_plugins = $ig_es_tracker::get_active_plugins();
			$sender_details = array();
			$admin_email    = get_option( 'admin_email', '' );
			$blog_name      = get_option( 'blogname', '' );

			if ( '' == $admin_email ) {
				$admin_email = 'support@icegram.com';
			}

			$sender_details['name']  = $blog_name;
			$sender_details['email'] = $admin_email;

			//check if installed WP Mail SMTP
			if ( in_array( 'wp-mail-smtp/wp_mail_smtp.php', $active_plugins ) ) {
				$wp_mail_smtp_settings = get_option( 'wp_mail_smtp', array() );

				$mail_settings = ig_es_get_data( $wp_mail_smtp_settings, 'mail', array() );

				if ( ! empty( $mail_settings ) ) {
					$sender_details['name']  = ! empty( $mail_settings['from_name'] ) ? $mail_settings['from_name'] : $sender_details['name'];
					$sender_details['email'] = ! empty( $mail_settings['from_email'] ) ? $mail_settings['from_email'] : $sender_details['email'];
				}
			}

			return $sender_details;
		}

		/**
		 * Get default options
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public static function get_options() {

			$admin_email = get_option( 'admin_email' );
			$blogname    = get_option( 'blogname' );

			// We are setting latest_db_version as a ig_es_db_version option while installation
			// So, we don't need to run the upgrade process again.
			$latest_db_version = self::get_latest_db_version_to_update();

			$ig_es_db_update_history = array(
				$latest_db_version => ig_get_current_date_time()
			);

			$sender_details = self::get_sender_details();

			$home_url  = home_url( '/' );
			$optinlink = $home_url . '?es=optin&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}';
			$unsublink = $home_url . '?es=unsubscribe&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}';

			$guid    = ES_Common::generate_guid( 6 );
			$cronurl = $home_url . '?es=cron&guid=' . $guid;

			$report = '';
			$report .= "Hi Admin,\n\n";
			$report .= "Email has been sent successfully to {{COUNT}} email(s). Please find the details below:\n\n";
			$report .= "Unique ID: {{UNIQUE}}\n";
			$report .= "Start Time: {{STARTTIME}}\n";
			$report .= "End Time: {{ENDTIME}}\n";
			$report .= "For more information, login to your dashboard and go to Reports menu in Email Subscribers.\n\n";
			$report .= 'Thank You.';


			$new_contact_email_subject = 'One more contact joins our tribe!';
			$new_contact_email_content = "Hi,\r\n\r\nYour friendly Email Subscribers notification bot here!\r\n\r\n{{NAME}} ({{EMAIL}}) joined our tribe just now.\r\n\r\nWhich list/s? {{LIST}}\r\n\r\nIf you know this person, or if they are an influencer, you may want to reach out to them personally!\r\n\r\nLater...";

			$confirmation_email_subject = 'Thanks!';
			$confirmation_email_content = "Hi {{NAME}},\r\n\r\nJust one more step before we share the awesomeness from {{SITENAME}}!\r\n\r\nPlease confirm your subscription by clicking on <a href='{{SUBSCRIBE-LINK}}'>this link</a>\r\n\r\nThanks!";

			$welcome_email_subject = 'Welcome to {{SITENAME}}';
			$welcome_email_content = "Hi {{NAME}},\r\n\r\nJust wanted to send you a quick note...\r\n\r\nThank you for joining the awesome {{SITENAME}} tribe.\r\n\r\nOnly valuable emails from me, promise!\r\n\r\nThanks!";

			$cron_admin_email         = "Hi Admin,\r\n\r\nCron URL has been triggered successfully on {{DATE}} for the email '{{SUBJECT}}'. And it sent email to {{COUNT}} recipient(s).\r\n\r\nBest,\r\n" . $blogname;
			$unsubscribe_link_content = "I'd be sad to see you go. But if you want to, you can unsubscribe from <a href='{{UNSUBSCRIBE-LINK}}'>here</a>";

			$unsubscribe_message        = '<p>You will no longer hear from us. ☹️ Sorry to see you go!</p>';
			$subscription_error_message = "Hmm.. Something's amiss..\r\n\r\nCould not complete your request. That email address  is probably already subscribed. Or worse blocked!!\r\n\r\nPlease try again after some time - or contact us if the problem persists.\r\n\r\n";

			$unsubscribe_error_message = "Urrgh.. Something's wrong..\r\n\r\nAre you sure that email address is on our file? There was some problem in completing your request.\r\n\r\nPlease try again after some time - or contact us if the problem persists.\r\n\r\n";

			$options = array(
				'ig_es_from_name'                       => array( 'default' => $sender_details['name'], 'old_option' => 'ig_es_fromname' ),
				'ig_es_from_email'                      => array( 'default' => $sender_details['email'], 'old_option' => 'ig_es_fromemail' ),
				'ig_es_admin_new_contact_email_subject' => array( 'default' => $new_contact_email_subject, 'old_option' => 'ig_es_admin_new_sub_subject' ),
				'ig_es_admin_new_contact_email_content' => array( 'default' => $new_contact_email_content, 'old_option' => 'ig_es_admin_new_sub_content' ),
				'ig_es_admin_emails'                    => array( 'default' => $admin_email, 'old_option' => 'ig_es_adminemail' ),
				'ig_es_confirmation_mail_subject'       => array( 'default' => $confirmation_email_subject, 'old_option' => 'ig_es_confirmsubject' ),
				'ig_es_confirmation_mail_content'       => array( 'default' => $confirmation_email_content, 'old_option' => 'ig_es_confirmcontent' ),
				'ig_es_enable_welcome_email'            => array( 'default' => 'yes', 'old_option' => 'ig_es_welcomeemail', 'action' => 'convert_space_to_underscore' ),
				'ig_es_welcome_email_subject'           => array( 'default' => $welcome_email_subject, 'old_option' => 'ig_es_welcomesubject' ),
				'ig_es_welcome_email_content'           => array( 'default' => $welcome_email_content, 'old_option' => 'ig_es_welcomecontent' ),
				'ig_es_enable_cron_admin_email'         => array( 'default' => 'yes', 'old_option' => 'ig_es_enable_cron_adminmail' ),
				'ig_es_cron_admin_email'                => array( 'default' => $cron_admin_email, 'old_option' => 'ig_es_cron_adminmail' ),
				'ig_es_cronurl'                         => array( 'default' => $cronurl, 'old_option' => 'ig_es_cronurl' ),
				'ig_es_hourly_email_send_limit'         => array( 'default' => 300, 'old_option' => 'ig_es_cron_mailcount' ),
				'ig_es_sent_report_subject'             => array( 'default' => 'Your email has been sent', 'old_option' => 'ig_es_sentreport_subject' ),
				'ig_es_sent_report_content'             => array( 'default' => $report, 'old_option' => 'ig_es_sentreport' ),
				'ig_es_unsubscribe_link'                => array( 'default' => $unsublink, 'old_option' => 'ig_es_unsublink' ),
				'ig_es_optin_link'                      => array( 'default' => $optinlink, 'old_option' => 'ig_es_optinlink' ),
				'ig_es_unsubscribe_link_content'        => array( 'default' => $unsubscribe_link_content, 'old_option' => 'ig_es_unsubcontent' ),
				'ig_es_email_type'                      => array( 'default' => 'wp_html_mail', 'old_option' => 'ig_es_emailtype', 'action' => 'convert_space_to_underscore' ),
				'ig_es_notify_admin'                    => array( 'default' => 'yes', 'old_option' => 'ig_es_notifyadmin', 'action' => 'convert_space_to_underscore' ),
				'ig_es_optin_type'                      => array( 'default' => 'double_opt_in', 'old_option' => 'ig_es_optintype', 'action' => 'convert_space_to_underscore' ),
				'ig_es_subscription_error_messsage'     => array( 'default' => $subscription_error_message, 'old_option' => 'ig_es_suberror' ),
				'ig_es_subscription_success_message'    => array( 'default' => 'You have been successfully subscribed.', 'old_option' => 'ig_es_successmsg' ),
				'ig_es_unsubscribe_error_message'       => array( 'default' => $unsubscribe_error_message, 'old_option' => 'ig_es_unsuberror' ),
				'ig_es_unsubscribe_success_message'     => array( 'default' => $unsubscribe_message, 'old_option' => 'ig_es_unsubtext' ),
				'ig_es_post_image_size'                 => array( 'default' => 'thumbnail', 'old_option' => 'ig_es_post_image_size' ),
				'ig_es_db_version'                      => array( 'default' => $latest_db_version, 'old_option' => 'current_sa_email_subscribers_db_version' ),
				'ig_es_current_version_date_details'    => array( 'default' => '', 'old_option' => '' ),
				'ig_es_enable_captcha'                  => array( 'default' => '', 'old_option' => '' ),
				'ig_es_roles_and_capabilities'          => array( 'default' => '', 'old_option' => 'ig_es_rolesandcapabilities' ),
				'ig_es_sample_data_imported'            => array( 'default' => 'no', 'old_option' => '' ),
				'ig_es_default_subscriber_imported'     => array( 'default' => 'no', 'old_option' => '' ),
				'ig_es_set_widget'                      => array( 'default' => '', 'old_option' => '' ),
				'ig_es_sync_wp_users'                   => array( 'default' => array(), 'old_option' => '' ),
				'ig_es_blocked_domains'                 => array( 'default' => 'mail.ru' ),
				'ig_es_disable_wp_cron'                 => array( 'default' => 'no' ),
				'ig_es_track_email_opens'               => array( 'default' => 'yes' ),
				'ig_es_show_opt_in_consent'             => array( 'default' => 'yes' ),
				'ig_es_opt_in_consent_text'             => array( 'default' => '' ),
				'ig_es_installed_on'                    => array( 'default' => ig_get_current_date_time(), 'old_option' => '' ),
				'ig_es_form_submission_success_message' => array( 'default' => __( 'Your subscription was successful! Kindly check your mailbox and confirm your subscription. If you don\'t see the email within a few minutes, check the spam/junk folder.', 'email-subscribers' ), 'old_option' => '' ),
				'ig_es_db_update_history'               => array( 'default' => $ig_es_db_update_history ),
				'ig_es_email_sent_data'                 => array( 'default' => array() ),
				'ig_es_mailer_settings'                 => array( 'default' => array( 'mailer' => 'wpmail' ), 'old_option' => '' ),
				'ig_es_user_roles'                      => array( 'default' => self::get_default_permissions(), 'old_option' => '' ),
				'ig_es_cron_interval'                   => array( 'default' => IG_ES_CRON_INTERVAL, 'old_option' => '' ),
				'ig_es_max_email_send_at_once'          => array( 'default' => IG_ES_MAX_EMAIL_SEND_AT_ONCE, 'old_option' => '' ),
				'ig_es_test_mailbox_user'               => array( 'default' => ES_Common::generate_test_mailbox_user(), 'old_option' => '' ),
			);

			return $options;
		}

		/**
		 * Create tables
		 *
		 * @param null $version
		 *
		 * @since 4.0.0
		 *
		 * @modify 4.4.9
		 */
		public static function create_tables( $version = null ) {

			global $wpdb;

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			if ( is_null( $version ) ) {
				$schema_fn = 'get_schema';
			} else {
				$v         = str_replace( '.', '', $version );
				$schema_fn = 'get_ig_es_' . $v . '_schema';
			}

			$wpdb->hide_errors();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( self::$schema_fn( $collate ) );
		}

		public static function get_ig_es_400_schema( $collate = '' ) {

			global $wpdb;

			$tables = "
            CREATE TABLE `{$wpdb->prefix}ig_campaigns` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) DEFAULT NULL,
				`name` varchar(255) DEFAULT NULL,
				`type` varchar(50) DEFAULT NULL,
				`parent_id` int(10) unsigned DEFAULT NULL,
				`parent_type` varchar(50) DEFAULT NULL,
				`subject` varchar(255) DEFAULT NULL,
				`body` longtext DEFAULT NULL,
				`from_name` varchar(250) DEFAULT NULL,
				`from_email` varchar(150) DEFAULT NULL,
				`reply_to_name` varchar(250) DEFAULT NULL,
				`reply_to_email` varchar(150) DEFAULT NULL,
				`categories` text,
				`list_ids` text NOT NULL,
				`base_template_id` int(10) NOT NULL,
				`status` tinyint(4) NOT NULL,
				`meta` longtext DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`deleted_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY `type` (type),
                KEY `status` (status),
                KEY `base_template_id` (base_template_id)
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_contacts` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`wp_user_id` int(10) NOT NULL DEFAULT '0',
				`first_name` varchar(50) DEFAULT NULL,
				`last_name` varchar(50) DEFAULT NULL,
				`email` varchar(50) NOT NULL,
				`source` varchar(50) DEFAULT NULL,
				`ip_address` varchar(50) DEFAULT NULL,
				`country_code` varchar(50) DEFAULT NULL,
				`form_id` int(10) NOT NULL DEFAULT '0',
				`status` varchar(10) DEFAULT NULL,
				`unsubscribed` tinyint(1) NOT NULL DEFAULT '0',
				`hash` varchar(50) DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`is_verified` tinyint(1) DEFAULT '0',
				`is_disposable` tinyint(1) DEFAULT '0',
				`is_rolebased` tinyint(1) DEFAULT '0',
				`is_webmail` tinyint(1) DEFAULT '0',
				`is_deliverable` tinyint(1) DEFAULT '0',
				`is_sendsafely` tinyint(1) DEFAULT '0',
				`meta` longtext CHARACTER SET utf8,
                PRIMARY KEY  (id),
                KEY `wp_user_id` (wp_user_id),
                KEY `email` (email),
                KEY `status` (status),
                KEY `form_id` (form_id)
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_contacts_ips` (
				ip varchar(45) NOT NULL,
				created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (created_on, ip),
				KEY ip (ip)
            ) $collate;

			CREATE TABLE `{$wpdb->prefix}ig_blocked_emails` (
  				id int(10) NOT NULL AUTO_INCREMENT,
  				email varchar(50) DEFAULT NULL,
  				ip varchar(45) DEFAULT NULL,
  				created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id)
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_forms` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				`body` longtext,
				`settings` longtext,
				`styles` longtext,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`deleted_at` datetime DEFAULT NULL,
				`af_id` int(10) NOT NULL DEFAULT '0',
                PRIMARY KEY  (id)
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_lists` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) NOT NULL,
				`name` varchar(255) NOT NULL,
				`hash` varchar(12) NOT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`deleted_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id)

            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_lists_contacts` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`list_id` int(10) NOT NULL,
				`contact_id` int(10) NOT NULL,
				`status` varchar(50) NOT NULL,
				`optin_type` tinyint(4) NOT NULL,
				`subscribed_at` datetime DEFAULT NULL,
				`subscribed_ip` varchar(45) DEFAULT NULL,
				`unsubscribed_at` datetime DEFAULT NULL,
				`unsubscribed_ip` varchar(45) DEFAULT NULL,
				 PRIMARY KEY  (id)
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_mailing_queue` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`hash` varchar(50) NOT NULL,
				`campaign_id` int(10) NOT NULL DEFAULT '0',
				`subject` text DEFAULT '',
				`body` longtext,
				`count` int(10) UNSIGNED NOT NULL DEFAULT '0',
				`status` varchar(10) NOT NULL,
				`start_at` datetime DEFAULT NULL,
				`finish_at` datetime DEFAULT NULL,
				`meta` longtext DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY `campaign_id` (campaign_id)
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_sending_queue` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`mailing_queue_id` int(10) NOT NULL DEFAULT '0',
				`mailing_queue_hash` varchar(50) DEFAULT NULL,
				`campaign_id` int(10) NOT NULL DEFAULT '0',
				`contact_id` int(10) NOT NULL DEFAULT '0',
				`contact_hash` varchar(255) DEFAULT NULL,
				`email` varchar(50) DEFAULT NULL,
				`status` varchar(50) DEFAULT NULL,
				`links` longtext,
				`opened` int(1) DEFAULT NULL,
				`sent_at` datetime DEFAULT NULL,
				`opened_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id)
            ) $collate;
        ";

			return $tables;
		}

		/**
		 * Create Contact Meta table
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.0
		 */
		public static function get_ig_es_420_schema( $collate = '' ) {
			global $wpdb;

			$tables = "CREATE TABLE `{$wpdb->prefix}ig_contact_meta` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`contact_id` bigint(10) unsigned NOT NULL,
				`meta_key` varchar(255) DEFAULT NULL,
				`meta_value` longtext,
                PRIMARY KEY  (id),
                KEY `contact_id` (contact_id),
                KEY `meta_ley` (meta_key)
            ) $collate;
         ";

			return $tables;
		}

		/**
		 * Add new table
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.1
		 */
		public static function get_ig_es_421_schema( $collate = '' ) {

			global $wpdb;

			$tables = "CREATE TABLE `{$wpdb->prefix}ig_contactmeta` (
				`meta_id` bigint(20) NOT NULL AUTO_INCREMENT,
				`contact_id` bigint(20) unsigned NOT NULL,
				`meta_key` varchar(255) DEFAULT NULL,
				`meta_value` longtext DEFAULT NULL,
                PRIMARY KEY  (meta_id),
                KEY `contact_id` (contact_id),
                KEY `meta_ley` (meta_key)
            ) $collate;

			CREATE TABLE {$wpdb->prefix}ig_queue (
                `contact_id` bigint(20) unsigned NOT NULL DEFAULT 0,
                `campaign_id` bigint(20) unsigned NOT NULL DEFAULT 0,
                `requeued` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `added` int(11) unsigned NOT NULL DEFAULT 0,
                `timestamp` int(11) unsigned NOT NULL DEFAULT 0,
                `sent_at` int(11) unsigned NOT NULL DEFAULT 0,
                `priority` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `count` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `error` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `ignore_status` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `options` varchar(191) NOT NULL DEFAULT '',
                `tags` longtext NOT NULL,
                UNIQUE KEY `id` (`contact_id`,`campaign_id`,`requeued`,`options`),
                KEY `contact_id` (`contact_id`),
                KEY `campaign_id` (`campaign_id`),
                KEY `requeued` (`requeued`),
                KEY `timestamp` (`timestamp`),
                KEY `priority` (`priority`),
                KEY `count` (`count`),
                KEY `error` (`error`),
                KEY `ignore_status` (`ignore_status`)
            ) $collate;

			CREATE TABLE `{$wpdb->prefix}ig_actions` (
			  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
			  `message_id` bigint(20) UNSIGNED DEFAULT NULL,
			  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
			  `type` tinyint(1) NOT NULL DEFAULT 0,
			  `count` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `link_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			  `list_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `ip` varchar(50) DEFAULT NULL,
			  `country` varchar(50) DEFAULT NULL,
			  `device` varchar(50) DEFAULT NULL,
			  `browser` varchar(50) DEFAULT NULL,
			  `email_client` varchar(50) DEFAULT NULL,
			  `os` varchar(50) DEFAULT NULL,
			  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  UNIQUE KEY `id` (`contact_id`,`message_id`, `campaign_id`,`type`,`link_id`, `list_id`),
                KEY `contact_id` (`contact_id`),
                KEY `message_id` (`message_id`),
                KEY `campaign_id` (`campaign_id`),
                KEY `type` (`type`)
			) $collate;
		";

			return $tables;
		}

		/**
		 * Create Links Table
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @sinc 4.2.4
		 */
		public static function get_ig_es_424_schema( $collate = '' ) {

			global $wpdb;

			$tables = "CREATE TABLE `{$wpdb->prefix}ig_links` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`message_id` int(10) unsigned NOT NULL,
				`campaign_id` int(10) unsigned NOT NULL,
				`link` varchar(2083) NOT NULL,
				`hash` varchar(20) NOT NULL,
				`i` tinyint(1) unsigned NOT NULL,
				`created_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
      			KEY `campaign_id` (campaign_id),
      			KEY `message_id` (message_id),
      			KEY `link` (link(100))
            ) $collate;
		";

			return $tables;
		}

		/**
		 * Create Links Table
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @sinc 4.4.1
		 */
		public static function get_ig_es_441_schema( $collate = '' ) {
			global $wpdb;

			$tables = "CREATE TABLE `{$wpdb->prefix}ig_workflows` (
					`id` int(10) NOT NULL AUTO_INCREMENT,
					`name` varchar(255) DEFAULT NULL,
					`title` varchar(255) DEFAULT NULL,
					`trigger_name` varchar(250) NOT NULL,
					`trigger_options` longtext NOT NULL,
					`rules` longtext NOT NULL,
					`actions` longtext NOT NULL,
					`status` tinyint(4) NOT NULL,
					`type` tinyint(4) NOT NULL,
					`priority` int(11) DEFAULT 0,
					`meta` longtext NOT NULL,
					`created_at` datetime DEFAULT NULL,
					`updated_at` datetime DEFAULT NULL,
					PRIMARY KEY (id)
	            ) $collate;

				CREATE TABLE {$wpdb->prefix}ig_workflows_queue (
	                `id` bigint(20) NOT NULL AUTO_INCREMENT,
					`workflow_id` bigint(20) DEFAULT NULL,
					`failed` int(1) NOT NULL DEFAULT 0,
					`failure_code` int(3) NOT NULL DEFAULT 0,
					`meta` longtext NOT NULL,
					`scheduled_at` datetime DEFAULT NULL,
					`created_at` datetime DEFAULT NULL,
					PRIMARY KEY (id)
	            ) $collate;
			";

			return $tables;
		}

		/**
		 * Create WooCommerce cart and guest tables
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @sinc 4.6.5
		 */
		public static function get_ig_es_465_schema( $collate = '' ) {
			global $wpdb;

			$tables = "CREATE TABLE `{$wpdb->prefix}ig_wc_cart` (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`status` varchar(100) NOT NULL default '',
					`user_id` bigint(20) NOT NULL default 0,
					`guest_id` bigint(20) NOT NULL default 0,
					`last_modified` datetime NULL,
					`created` datetime NULL,
					`items` longtext NOT NULL default '',
					`coupons` longtext NOT NULL default '',
					`fees` longtext NOT NULL default '',
					`shipping_tax_total` double DEFAULT 0 NOT NULL,
					`shipping_total` double DEFAULT 0 NOT NULL,
					`total` double DEFAULT 0 NOT NULL,
					`token` varchar(32) NOT NULL default '',
					`currency` varchar(8) NOT NULL default '',
					PRIMARY KEY  (id),
					KEY `status` (`status`),
					KEY `user_id` (`user_id`),
					KEY `guest_id` (`guest_id`),
					KEY `last_modified` (`last_modified`),
					KEY `created` (`created`)
				) $collate;

				CREATE TABLE `{$wpdb->prefix}ig_wc_guests` (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				email varchar(255) NOT NULL default '',
				tracking_key varchar(32) NOT NULL default '',
				created datetime NULL,
				last_active datetime NULL,
				meta longtext NOT NULL,
				language varchar(10) NOT NULL default '',
				most_recent_order bigint(20) NOT NULL DEFAULT 0,
				version bigint(20) NOT NULL default 0,
				PRIMARY KEY  (id),
				KEY tracking_key (tracking_key),
				KEY email (email(191)),
				KEY most_recent_order (most_recent_order),
				KEY version (version)
				) $collate;
			";
			
			return $tables;
		}

		/**
		 * Create table for storing subscribers import CSV data temporarily
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.6.6
		 */
		public static function get_ig_es_466_schema( $collate = '' ) {
			global $wpdb;

			$tables = "CREATE TABLE {$wpdb->prefix}ig_temp_import (
				`ID` bigint(20) NOT NULL AUTO_INCREMENT,
				`data` longtext NOT NULL,
				`identifier` char(13) NOT NULL,
				PRIMARY KEY (ID)
			) $collate;";
			
			return $tables;
		}

		/**
		 * Create table unsubscribe feedback
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.6.8
		 */
		public static function get_ig_es_468_schema( $collate = '' ) {
			global $wpdb;

			$tables = "CREATE TABLE {$wpdb->prefix}ig_unsubscribe_feedback (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`contact_id` int(10) unsigned NOT NULL,
				`list_id` int(10) unsigned NOT NULL,
				`campaign_id` int(10) unsigned NOT NULL,
				`mailing_queue_id` int(10) unsigned NOT NULL,
				`feedback_slug` varchar(50) NOT NULL,
				`feedback_text` varchar(500) NOT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`meta` longtext DEFAULT NULL,
				PRIMARY KEY (id)
			) $collate;";
			
			return $tables;
		}

		/**
		 * Collect multiple version schema
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.0
		 */
		private static function get_schema( $collate = '' ) {

			$tables = self::get_ig_es_400_schema( $collate );
			$tables .= self::get_ig_es_420_schema( $collate );
			$tables .= self::get_ig_es_421_schema( $collate );
			$tables .= self::get_ig_es_424_schema( $collate );
			$tables .= self::get_ig_es_441_schema( $collate );
			$tables .= self::get_ig_es_465_schema( $collate );
			$tables .= self::get_ig_es_466_schema( $collate );
			$tables .= self::get_ig_es_468_schema( $collate );

			return $tables;
		}

		/**
		 * Load readymade templates
		 *
		 * @since 4.3.2
		 */
		public static function load_templates( $force = false ) {
			//TODO :: Add template with custom post type
			global $wpdb;

			$plan = 'lite';
			if ( ES()->is_pro() ) {
				$plan = 'pro';
			} elseif ( ES()->is_starter() ) {
				$plan = 'starter';
			}

			$templates_loaded_for = get_option( 'ig_es_templates_loaded_for', '' );

			if ( $force || ( $plan !== $templates_loaded_for ) ) {

				set_time_limit( 0 );

				$templates = array();
				$templates = apply_filters( 'ig_es_email_templates', $templates );
				$post_type = 'es_template';

				$imported_templ = $wpdb->get_col( $wpdb->prepare( "SELECT post_name FROM {$wpdb->prefix}posts where post_type  = %s", $post_type ) );

				if ( is_array( $templates ) && count( $templates ) > 0 ) {

					foreach ( $templates as $slug => $template ) {
						if ( in_array( $slug, $imported_templ ) ) {
							continue;
						}

						// Start-Woo-Code.
						if ( 'woo' === IG_ES_PLUGIN_PLAN ) {
							$template_type = ! empty( $template['es_email_type'] ) ? $template['es_email_type'] : '';
							// Don't add post notification and post digest templates in the Woo plugin.
							if ( in_array( $template_type, array( 'post_notification', 'post_digest' ), true ) ) {
								continue;
							}
						}
						// End-Woo-Code.
						$es_post = array(
							'post_title'   => wp_strip_all_tags( $template['es_templ_heading'] ),
							'post_content' => $template['es_templ_body'],
							'post_status'  => 'publish',
							'post_type'    => 'es_template',
							'post_name'    => $slug,
							'meta_input'   => array(
								'es_template_type' => $template['es_email_type'],
								'es_custom_css'    => $template['es_custom_css']
							)
						);
						// Insert the post into the database.
						$last_inserted_id = wp_insert_post( $es_post );

						// Generate Featured Image.
						self::es_generate_featured_image( $template['es_thumbnail'], $last_inserted_id );

					}
				}

				update_option( 'ig_es_templates_loaded_for', $plan );
			}
		}

		/**
		 * Generate Featured Image
		 *
		 * @param $image_url
		 * @param $post_id
		 *
		 * @since 4.3.2
		 */
		public static function es_generate_featured_image( $image_url, $post_id ) {
			$upload_dir = wp_upload_dir();
			$image_data = file_get_contents( $image_url );
			$filename   = basename( $image_url );
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}

			file_put_contents( $file, $image_data );

			$wp_filetype = wp_check_filetype( $filename, null );
			$attachment  = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_file_name( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			$res1        = wp_update_attachment_metadata( $attach_id, $attach_data );
			$res2        = set_post_thumbnail( $post_id, $attach_id );
		}


		/**
		 * Create files/ directory
		 *
		 * @since 4.1.13
		 */
		public static function create_files() {

			// Want to bypass creation of files?
			if ( apply_filters( 'ig_es_install_skip_create_files', false ) ) {
				return;
			}

			$files = array(
				array(
					'base'    => IG_LOG_DIR,
					'file'    => '.htaccess',
					'content' => 'deny from all',
				),
				array(
					'base'    => IG_LOG_DIR,
					'file'    => 'index.html',
					'content' => '',
				),
			);

			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
					if ( $file_handle ) {
						fwrite( $file_handle, $file['content'] );
						fclose( $file_handle );
					}
				}
			}
		}

		/**
		 * Get default permissions
		 *
		 * @return mixed
		 *
		 * @since 4.3.1
		 */
		public static function get_default_permissions() {

			$campaigns_permission = array(
				'administrator' => 'yes'
			);

			$reports_permission = array(
				'administrator' => 'yes'
			);

			$sequence_permission = array(
				'administrator' => 'yes'
			);

			$audience_permission = array(
				'administrator' => 'yes'
			);

			$forms_permission = array(
				'administrator' => 'yes'
			);

			$workflows_permission = array(
				'administrator' => 'yes'
			);

			$es_roles_default_permission['campaigns'] = $campaigns_permission;
			$es_roles_default_permission['reports']   = $reports_permission;
			$es_roles_default_permission['sequences'] = $sequence_permission;
			$es_roles_default_permission['audience']  = $audience_permission;
			$es_roles_default_permission['forms']     = $forms_permission;
			$es_roles_default_permission['workflows'] = $workflows_permission;

			return $es_roles_default_permission;
		}
	}

	ES_Install::init();
}
