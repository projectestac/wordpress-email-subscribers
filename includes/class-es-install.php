<?php

defined( 'ABSPATH' ) || exit;

/**
 * ES_Install Class.
 */
class ES_Install {

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
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
			 * - Improt Options
			 * - Get unique Lists/ Groups from es_emaillist table
			 *  - Create new lists into ig_lists table
			 *  - Get subscribers from emaillist table in batch and import it into ig_contacts table
			 *  - Add list entry into ig_lists_contacts table
			 *  - Get all Post Notifications from es_notification table and get newsletters from es_sentdetails table and import into campaigns and ig_mailing_queue table
			 *  - Get all data from es_deliverreport and import into ig_sending_queue table
			 *  - Import all data from es_subscriber_ips to ig_contacts_ips
			 */
			'ig_es_update_400_create_tables',
			'ig_es_update_400_import_options',
			'ig_es_update_400_migrate_lists',
			'ig_es_update_400_migrate_subscribers',
			'ig_es_update_400_migrate_post_notifications',
			'ig_es_update_400_migrate_notifications',
			'ig_es_update_400_migrate_reports_data',
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
		)
	);

	/**
	 * Background update class.
	 *
	 * @var object
	 */
	private static $background_updater;

	public static $logger;

	/**
	 * Hook in tabs.
	 */
	public static function init() {

		self::$logger = get_ig_logger();

		self::$logger->info( '--- Init Installation Process..' );
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
	}

	public static function check_version() {
		self::$logger->info( 'Checking Version..' . get_option( 'ig_es_db_version', '1.0.0' ) );
		if ( version_compare( get_option( 'ig_es_db_version', '1.0.0' ), ES_PLUGIN_VERSION, '<' ) ) {
			self::$logger->info( 'Seems need to run updater..' );
			self::install();
		}
	}

	public static function init_background_updater() {
		self::$logger->info( 'Init Background Updater..' );
		include_once dirname( __FILE__ ) . '/upgrade/class-es-background-updater.php';
		self::$background_updater = new ES_Background_Updater();
	}

	public static function install_actions() {
		self::$logger->info( 'Installing Actions......' );
		if ( ! empty( $_GET['do_update_ig_es'] ) ) { // WPCS: input var ok.
			check_admin_referer( 'ig_es_db_update', 'ig_es_db_update_nonce' );
			self::$logger->info( 'Performing Installing Actions......' );
			self::update();
			ES_Admin_Notices::add_notice( 'update' );
		}

		if ( ! empty( $_GET['force_update_ig_es'] ) ) { // WPCS: input var ok.
			self::$logger->info( 'Force IG ES Update Before check......' );

			check_admin_referer( 'ig_es_force_db_update', 'ig_es_force_db_update_nonce' );

			self::$logger->info( 'Force IG ES Update......' );
			self::update();
			ES_Admin_Notices::add_notice( 'update' );
			wp_safe_redirect( admin_url( 'admin.php?page=es_settings' ) );
			exit;
		}
	}

	public static function install() {

		self::$logger->info( 'Installation Step 1......' );
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'ig_es_installing' ) ) {
			return;
		}

		if ( self::is_new_install() ) {
			self::$logger->info( 'New Install??? Yes...Begin installing......' );

			// If we made it till here nothing is running yet, lets set the transient now.
			set_transient( 'ig_es_installing', 'yes', MINUTE_IN_SECONDS * 10 );
			ig_es_maybe_define_constant( 'IG_ES_INSTALLING', true );


			// Create Tables
			self::create_tables();

			// Create Default Optiom
			self::create_options();

			// Create Default List and contact
			self::create_default_list_contact();

			// Create and send default broadcast
			self::create_and_send_default_broadcast();

			// Create and send Post Notification
			self::create_and_send_default_post_notification();

			//Create Default form
			self::create_default_form();

			self::$logger->info( 'Installation Complete......' );

		}

		self::maybe_update_db_version();

		delete_transient( 'ig_es_installing' );

	}

	private static function is_new_install() {
		return is_null( get_option( 'ig_es_db_version', null ) ) && is_null( get_option( 'current_sa_email_subscribers_db_version', null ) );
	}

	private static function needs_db_update() {

		self::$logger->info( 'Needs DB Update?......' );
		$current_db_version = get_option( 'ig_es_db_version', '1.0.0' );
		$updates            = self::get_db_update_callbacks();

		self::$logger->info( 'Current DB VERSION-------------------' . $current_db_version );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'ig_es_enable_auto_update_db', true ) ) {
				self::$logger->info( 'Do Update DB ................. >>>>>>>>>>>>>>>>>>>>>>>' );
				self::init_background_updater();
				self::update();
			} else {
				self::$logger->info( 'Show Update Notice..' );
				ES_Admin_Notices::add_notice( 'update' );
			}
		} else {
			self::$logger->info( 'No need to update db...' );
			self::update_db_version();
		}
	}

	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	private static function update() {
		self::$logger->info( 'Do Update....' );

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'ig_es_updating' ) ) {
			return;
		}

		$current_db_version = get_ig_es_db_version();
		$update_queued      = false;

		self::$logger->info( 'Current IG ES DB Version: ' . $current_db_version );
		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					self::$logger->info(
						sprintf( 'Queuing %s - %s', $version, $update_callback ),
						array( 'source' => 'ig_es_db_updates' )
					);

					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			set_transient( 'ig_es_updating', 'yes', MINUTE_IN_SECONDS * 10 );
			self::$background_updater->save()->dispatch();
		}


	}

	public static function update_db_version( $version = null ) {
		delete_option( 'ig_es_db_version' );
		add_option( 'ig_es_db_version', is_null( $version ) ? ES_PLUGIN_VERSION : $version );
	}

	private static function create_options() {
		$options = self::get_options();
		foreach ( $options as $option => $values ) {
			add_option( $option, $values['default'], '', false );
		}
	}

	public static function get_options() {

		$admin_email = get_option( 'admin_email' );
		$blogname    = get_option( 'blogname' );

		if ( $admin_email == "" ) {
			$admin_email = "support@icegram.com";
		}

		$home_url  = home_url( '/' );
		$optinlink = $home_url . "?es=optin&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}";
		$unsublink = $home_url . "?es=unsubscribe&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}";

		$guid    = ES_Common::generate_guid( 6 );
		$cronurl = $home_url . "?es=cron&guid=" . $guid;

		$report = "";
		$report .= "Hi Admin,\n\n";
		$report .= "Email has been sent successfully to {{COUNT}} email(s). Please find the details below:\n\n";
		$report .= "Unique ID: {{UNIQUE}}\n";
		$report .= "Start Time: {{STARTTIME}}\n";
		$report .= "End Time: {{ENDTIME}}\n";
		$report .= "For more information, login to your dashboard and go to Reports menu in Email Subscribers.\n\n";
		$report .= "Thank You.";

		$new_contact_email_subject = "One more contact joins our tribe!";
		$new_contact_email_content = "Hi,\r\n\r\nYour friendly Email Subscribers notification bot here!\r\n\r\n{{NAME}} ({{EMAIL}}) joined our tribe just now.\r\n\r\nWhich list/s? {{LIST}}\r\n\r\nIf you know this person, or if they are an influencer, you may want to reach out to them personally!\r\n\r\nLater...";

		$confirmation_email_subject = "Thanks!";
		$confirmation_email_content = "Hi {{NAME}},\r\n\r\nJust one more step before we share the awesomeness from {{SITENAME}}!\r\n\r\nPlease confirm your subscription by clicking on this link:\r\n\r\n{{LINK}}\r\n\r\nThanks!";

		$welcome_email_subject = "Welcome to {{SITENAME}}";
		$welcome_email_content = "Hi {{NAME}},\r\n\r\nJust wanted to send you a quick note...\r\n\r\nThank you for joining the awesome {{SITENAME}} tribe.\r\n\r\nOnly valuable emails from me, promise!\r\n\r\nThanks!";

		$cron_admin_email         = "Hi Admin,\r\n\r\nCron URL has been triggered successfully on {{DATE}} for the email '{{SUBJECT}}'. And it sent email to {{COUNT}} recipient(s).\r\n\r\nBest,\r\n" . $blogname;
		$unsubscribe_link_content = "I'd be sad to see you go. But if you want to, you can unsubscribe from here: {{UNSUBSCRIBE-LINK}}";

		$unsubscribe_message         = "<h2>Unsubscribed.</h2><p>You will no longer hear from us. ☹️ Sorry to see you go!</p>";
		$subscription_error_messsage = "Hmm.. Something's amiss..\r\n\r\nCould not complete your request. That email address  is probably already subscribed. Or worse blocked!!\r\n\r\nPlease try again after some time - or contact us if the problem persists.\r\n\r\n";

		$unsubscribe_error_message = "Urrgh.. Something's wrong..\r\n\r\nAre you sure that email address is on our file? There was some problem in completing your request.\r\n\r\nPlease try again after some time - or contact us if the problem persists.\r\n\r\n";

		$options = array(
			'ig_es_from_name'                       => array( 'default' => $blogname, 'old_option' => 'ig_es_fromname' ),
			'ig_es_from_email'                      => array( 'default' => $admin_email, 'old_option' => 'ig_es_fromemail' ),
			'ig_es_admin_new_contact_email_subject' => array( 'default' => $new_contact_email_subject, 'old_option' => 'ig_es_admin_new_sub_subject' ),
			'ig_es_admin_new_contact_email_content' => array( 'default' => $new_contact_email_content, 'old_option' => 'ig_es_admin_new_sub_content' ),
			'ig_es_admin_emails'                    => array( 'default' => $admin_email, 'old_option' => 'ig_es_adminemail' ),
			'ig_es_confirmation_mail_subject'       => array( 'default' => $confirmation_email_subject, 'old_option' => 'ig_es_confirmsubject' ),
			'ig_es_confirmation_mail_content'       => array( 'default' => $confirmation_email_content, 'old_option' => 'ig_es_confirmcontent' ),
			'ig_es_enable_welcome_email'            => array( 'default' => 'yes', 'old_option' => 'ig_es_welcomeemail', 'action' => 'convert_space_to_underscore' ),
			'ig_es_welcome_email_subject'           => array( 'default' => $welcome_email_subject, 'old_option' => 'ig_es_welcomesubject' ),
			'ig_es_welcome_email_content'           => array( 'default' => $welcome_email_content, 'old_option' => 'ig_es_welcomecontent' ),
			'ig_es_enable_cron_admin_email'         => array( 'default' => $cron_admin_email, 'old_option' => 'ig_es_enable_cron_adminmail' ),
			'ig_es_cron_admin_email'                => array( 'default' => $cron_admin_email, 'old_option' => 'ig_es_cron_adminmail' ),
			'ig_es_cronurl'                         => array( 'default' => $cronurl, 'old_option' => 'ig_es_cronurl' ),
			'ig_es_hourly_email_send_limit'         => array( 'default' => 300, 'old_option' => 'ig_es_cron_mailcount' ),
			'ig_es_sent_report_subject'             => array( 'default' => "Your email has been sent", 'old_option' => 'ig_es_sentreport_subject' ),
			'ig_es_sent_report_content'             => array( 'default' => $report, 'old_option' => 'ig_es_sentreport' ),
			'ig_es_unsubscribe_link'                => array( 'default' => $unsublink, 'old_option' => 'ig_es_unsublink' ),
			'ig_es_optin_link'                      => array( 'default' => $optinlink, 'old_option' => 'ig_es_optinlink' ),
			'ig_es_unsubscribe_link_content'        => array( 'default' => $unsubscribe_link_content, 'old_option' => 'ig_es_unsubcontent' ),
			'ig_es_email_type'                      => array( 'default' => 'wp_mail_html', 'old_option' => 'ig_es_emailtype', 'action' => 'convert_space_to_underscore' ),
			'ig_es_notify_admin'                    => array( 'default' => 'no', 'old_option' => 'ig_es_notifyadmin', 'action' => 'convert_space_to_underscore' ),
			'ig_es_optin_type'                      => array( 'default' => 'double_opt_in', 'old_option' => 'ig_es_optintype', 'action' => 'convert_space_to_underscore' ),
			'ig_es_subscription_error_messsage'     => array( 'default' => $subscription_error_messsage, 'old_option' => 'ig_es_suberror' ),
			'ig_es_subscription_success_message'    => array( 'default' => "You have been successfully subscribed.", 'old_option' => 'ig_es_successmsg' ),
			'ig_es_unsubscribe_error_message'       => array( 'default' => $unsubscribe_error_message, 'old_option' => 'ig_es_unsuberror' ),
			'ig_es_unsubscribe_success_message'     => array( 'default' => $unsubscribe_message, 'old_option' => 'ig_es_unsubtext' ),
			'ig_es_post_image_size'                 => array( 'default' => 'thumbnail', 'old_option' => 'ig_es_post_image_size' ),
			'ig_es_db_version'                      => array( 'default' => ES_PLUGIN_VERSION, 'old_option' => 'current_sa_email_subscribers_db_version' ),
			'ig_es_current_version_date_details'    => array( 'default' => '', 'old_option' => 'ig_es_current_version_date_details' ),
			'ig_es_enable_captcha'                  => array( 'default' => '', 'old_option' => '' ),
			'ig_es_roles_and_capabilities'          => array( 'default' => '', 'old_option' => 'ig_es_rolesandcapabilities' ),
			'ig_es_sample_data_imported'            => array( 'default' => 'no', 'old_option' => 'ig_es_sample_data_imported' ),
			'ig_es_default_subscriber_imported'     => array( 'default' => 'no', 'old_option' => 'ig_es_default_subscriber_imported' ),
			'ig_es_set_widget'                      => array( 'default' => '', 'old_option' => '' ),
			'ig_es_sync_wp_users'                   => array( 'default' => '', 'old_option' => 'ig_es_sync_wp_users' ),
			'ig_es_blocked_domains'                 => array( 'default' => 'mail.ru' ),
		);


		return $options;
	}

	// Create Tables
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::get_schema() );
	}

	public static function get_ig_es_400_schema() {

		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
            CREATE TABLE `{$wpdb->prefix}ig_campaigns` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) DEFAULT NULL,
				`name` varchar(255) DEFAULT NULL,
				`type` varchar(50) DEFAULT NULL,
				`from_name` varchar(50) DEFAULT NULL,
				`from_email` varchar(50) DEFAULT NULL,
				`reply_to_name` varchar(50) DEFAULT NULL,
				`reply_to_email` varchar(50) DEFAULT NULL,
				`sequence_ids` text,
				`categories` text,
				`list_ids` text NOT NULL,
				`base_template_id` int(10) NOT NULL,
				`status` tinyint(4) NOT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`deleted_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id)
            ) $collate;
            
            CREATE TABLE `{$wpdb->prefix}ig_contacts` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`wp_user_id` int(10) NOT NULL DEFAULT '0',
				`first_name` varchar(50) DEFAULT NULL,
				`last_name` varchar(50) DEFAULT NULL,
				`email` varchar(50) NOT NULL,
				`source` varchar(10) DEFAULT NULL,
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
                PRIMARY KEY  (id)
                                                         
            ) $collate;

            CREATE TABLE `{$wpdb->prefix}ig_contacts_ips` (
				ip varchar(45) NOT NULL, 
				created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
				PRIMARY KEY  (created_on, ip),
				KEY ip (ip)
            ) $collate;

			CREATE TABLE `{$wpdb->prefix}ig_blocked_emails` (
  				id int(10) NOT NULL,
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
				`created_at` datetime DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id)
                                                              
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

	private static function get_schema() {

		$tables = self::get_ig_es_400_schema();

		return $tables;
	}

	private static function create_default_list_contact() {

		$list_name = IG_DEFAULT_LIST;

		$list_id = ES_DB_Lists::add_list( $list_name );

		if ( $list_id ) {

			$admin_email = $admin_name = get_option( 'admin_email' );

			$user = get_user_by( 'email', $admin_email );

			$wp_user_id = 0;
			if ( $user instanceof WP_User ) {
				$wp_user_id = $user->ID;
			}
			$data = array(
				'wp_user_id'   => $wp_user_id,
				'first_name'   => $admin_name,
				'last_name'    => '',
				'email'        => $admin_email,
				'source'       => 'admin',
				'form_id'      => 0,
				'status'       => 'verified',
				'unsubscribed' => 0,
				'hash'         => ES_Common::generate_guid(),
				'created_at'   => ig_get_current_date_time()
			);

			$contact_id = ES_DB_Contacts::add_subscriber( $data );

			if ( $contact_id ) {
				$data = array(
					'list_id'       => array( $list_id ),
					'contact_id'    => $contact_id,
					'status'        => 'subscribed',
					'optin_type'    => IG_SINGLE_OPTIN,
					'subscribed_at' => ig_get_current_date_time(),
					'subscribed_ip' => null
				);

				ES_DB_Lists_Contacts::add_lists_contacts( $data );
			}

		}
	}

	private static function create_and_send_default_broadcast() {
		/**
		 * - Create Default Template
		 * - Create Broadcast Campaign
		 * - Send Email.
		 */

		$admin_name  = ES_Common::get_ig_option( 'admin_name' );
		$admin_email = ES_Common::get_ig_option( 'admin_email' );

		// Create Default Template
		$sample = '<strong style="color: #990000">What can you achieve using Email Subscribers?</strong><p>Add subscription forms on website, send HTML newsletters & automatically notify subscribers about new blog posts once it is published.';
		$sample .= ' You can also Import or Export subscribers from any list to Email Subscribers.</p>';
		$sample .= ' <strong style="color: #990000">Plugin Features</strong><ol>';
		$sample .= ' <li>Send notification emails to subscribers when new blog posts are published.</li>';
		$sample .= ' <li>Subscribe form available with 3 options to setup.</li>';
		$sample .= ' <li>Double Opt-In and Single Opt-In support.</li>';
		$sample .= ' <li>Email notification to admin when a new user signs up (Optional).</li>';
		$sample .= ' <li>Automatic welcome email to subscriber.</li>';
		$sample .= ' <li>Auto add unsubscribe link in the email.</li>';
		$sample .= ' <li>Import/Export subscriber emails to migrate to any lists.</li>';
		$sample .= ' <li>Default WordPress editor to create emails.</li>';
		$sample .= ' </ol>';
		$sample .= ' <strong>Thanks & Regards,</strong><br>Admin';

		$title   = 'Welcome To Email Subscribers';
		$es_post = array(
			'post_title'   => $title,
			'post_content' => $sample,
			'post_status'  => 'publish',
			'post_type'    => 'es_template',
			'meta_input'   => array(
				'es_template_type' => 'newsletter'
			)
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $es_post );

		// Create Broadcast Campaign

		$default_list = ES_DB_Lists::get_list_by_name( IG_DEFAULT_LIST );

		if ( ! empty( $default_list ) ) {
			$list_id = $default_list['id'];

			if ( ! empty( $post_id ) ) {

				$data['slug']             = sanitize_title( $title );
				$data['name']             = $title;
				$data['type']             = 'newsletter';
				$data['from_email']       = $data['reply_to_email'] = $admin_email;
				$data['from_name']        = $data['reply_to_name'] = $admin_name;
				$data['list_ids']         = $list_id;
				$data['base_template_id'] = $post_id;
				$data['status']           = 1;

				$campaign_id = ES_DB_Campaigns::save_campaign( $data );

				$subscribers = ES_DB_Contacts::get_active_subscribers_by_list_id( $list_id );
				if ( ! empty( $subscribers ) && count( $subscribers ) > 0 ) {
					$guid = ES_Common::generate_guid( 6 );
					$now  = ig_get_current_date_time();
					$data = array(
						'hash'        => $guid,
						'campaign_id' => $campaign_id,
						'subject'     => $title,
						'body'        => $sample,
						'count'       => count( $subscribers ),
						'status'      => 'Sent',
						'start_at'    => $now,
						'finish_at'   => $now,
						'created_at'  => $now,
						'updated_at'  => $now
					);

					$last_report_id = ES_DB_Mailing_Queue::add_notification( $data );

					$delivery_data                     = array();
					$delivery_data['hash']             = $guid;
					$delivery_data['subscribers']      = $subscribers;
					$delivery_data['campaign_id']      = $campaign_id;
					$delivery_data['mailing_queue_id'] = $last_report_id;
					$delivery_data['status']           = 'Sent';
					ES_DB_Sending_Queue::do_batch_insert( $delivery_data );

					$email_created = time();

					// Newsletter Send

					$email_template = ES_Common::convert_es_templates( $sample, $admin_name, $admin_email, $email_created );
					ES_Mailer::send( $admin_email, $title, $email_template );
				}

			}
		}

	}

	private static function create_and_send_default_post_notification() {

		$admin_name  = ES_Common::get_ig_option( 'admin_name' );
		$admin_email = ES_Common::get_ig_option( 'admin_email' );

		$content = "Hello {{NAME}},\r\n\r\n";
		$content .= "We have published a new blog article on our website : {{POSTTITLE}}\r\n";
		$content .= "{{POSTIMAGE}}\r\n\r\n";
		$content .= "You can view it from this link : ";
		$content .= "{{POSTLINK}}\r\n\r\n";
		$content .= "Thanks & Regards,\r\n";
		$content .= "Admin\r\n\r\n";
		$content .= "You received this email because in the past you have provided us your email address : {{EMAIL}} to receive notifications when new updates are posted.";

		$title = 'New Post Published - {{POSTTITLE}}';
		// Create Post Notification object
		$post = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'es_template',
			'meta_input'   => array(
				'es_template_type' => 'post_notification'
			)
		);
		// Insert the post into the database
		$post_id = wp_insert_post( $post );

		$default_list = ES_DB_Lists::get_list_by_name( IG_DEFAULT_LIST );

		if ( ! empty( $post_id ) ) {
			$list_id = $default_list['id'];

			$categories_objects = get_terms( array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
			) );

			$categories = array();
			if ( count( $categories_objects ) > 0 ) {
				foreach ( $categories_objects as $category ) {
					if ( $category instanceof WP_Term ) {
						$categories[] = $category->name;
					}
				}
			}

			$categories_str = ES_Common::convert_categories_array_to_string( $categories );

			$data['slug']             = sanitize_title( $title );
			$data['name']             = $title;
			$data['type']             = 'post_notification';
			$data['from_email']       = $data['reply_to_email'] = $admin_email;
			$data['from_name']        = $data['reply_to_name'] = $admin_name;
			$data['categories']       = $categories_str;
			$data['list_ids']         = $list_id;
			$data['base_template_id'] = $post_id;
			$data['status']           = 1;

			$campaign_id = ES_DB_Campaigns::save_campaign( $data );

			$subscribers = ES_DB_Contacts::get_active_subscribers_by_list_id( $list_id );
			if ( ! empty( $subscribers ) && count( $subscribers ) > 0 ) {

				$args  = array( 'posts_per_page' => 1 );
				$posts = get_posts( $args );

				if ( count( $posts ) > 0 ) {
					$recent_post = array_shift( $posts );

					$template = get_post( $post_id );
					$content  = ES_Handle_Post_Notification::prepare_body( $content, $recent_post->ID, $post_id );
					$subject  = ES_Handle_Post_Notification::prepare_subject( $recent_post, $template );
					$guid     = ES_Common::generate_guid( 6 );
					$now      = ig_get_current_date_time();
					$data     = array(
						'hash'        => $guid,
						'campaign_id' => $campaign_id,
						'subject'     => $subject,
						'body'        => $content,
						'count'       => count( $subscribers ),
						'status'      => 'Sent',
						'start_at'    => $now,
						'finish_at'   => $now,
						'created_at'  => $now,
						'updated_at'  => $now
					);

					$last_report_id = ES_DB_Mailing_Queue::add_notification( $data );

					$delivery_data                     = array();
					$delivery_data['hash']             = $guid;
					$delivery_data['subscribers']      = $subscribers;
					$delivery_data['campaign_id']      = $campaign_id;
					$delivery_data['mailing_queue_id'] = $last_report_id;
					$delivery_data['status']           = 'Sent';
					ES_DB_Sending_Queue::do_batch_insert( $delivery_data );

					$email_created = time();

					// Post Notification Send Send
					$email_template = ES_Common::convert_es_templates( $content, $admin_name, $admin_email, $email_created );
					ES_Mailer::send( $admin_email, $subject, $email_template );
				}
			}

		}

		return $post_id;
	}

	private static function create_default_form() {
		$form_data    = array();
		$default_list = ES_DB_Lists::get_list_by_name( IG_DEFAULT_LIST );
		$list_id      = $default_list['id'];
		$body         = array(
			array(
				'type'   => 'text',
				'name'   => 'Name',
				'id'     => 'name',
				'params' => array(
					'label'    => 'Name',
					'show'     => true,
					'required' => true
				),

				'position' => 1
			),

			array(
				'type'   => 'text',
				'name'   => 'Email',
				'id'     => 'email',
				'params' => array(
					'label'    => 'Email',
					'show'     => true,
					'required' => true
				),

				'position' => 2
			),

			array(
				'type'   => 'checkbox',
				'name'   => 'Lists',
				'id'     => 'lists',
				'params' => array(
					'label'    => 'Lists',
					'show'     => false,
					'required' => false,
					'values'   => $list_id
				),

				'position' => 3
			),

			array(
				'type'   => 'submit',
				'name'   => 'submit',
				'id'     => 'submit',
				'params' => array(
					'label' => 'Submit',
					'show'  => true
				),

				'position' => 4
			),

		);

		$settings = array(
			'lists' => $list_id,
			'desc'  => ''
		);

		$form_data['name']       = 'First Form';
		$form_data['body']       = maybe_serialize( $body );
		$form_data['settings']   = maybe_serialize( $settings );
		$form_data['styles']     = '';
		$form_data['created_at'] = ig_get_current_date_time();
		$form_data['updated_at'] = null;
		$form_data['deleted_at'] = null;
		$form_data['af_id']      = 0;
		$form_id                 = ES_DB_Forms::add_form( $form_data );
	}
}

ES_Install::init();
