<?php
/**
 * Functions for updating data, used by the background updater.
 */

defined( 'ABSPATH' ) || exit;


/* --------------------- ES 3.2.0 (Start)--------------------------- */
/**
 * To update sync email option to remove Commented user & it's group - ig_es_sync_wp_users
 * ES version 3.2 onwards
 */
function ig_es_update_320_add_sync_option() {

	$sync_subscribers = get_option( 'ig_es_sync_wp_users' );

	$es_unserialized_data = maybe_unserialize( $sync_subscribers );
	unset( $es_unserialized_data['es_commented'] );
	unset( $es_unserialized_data['es_commented_group'] );

	$es_serialized_data = serialize( $es_unserialized_data );
	update_option( 'ig_es_sync_wp_users', $es_serialized_data );
}

function ig_es_update_320_db_version() {
	ES_Install::update_db_version( '3.2.0' );
}

/* --------------------- ES 3.2.0 (End)--------------------------- */

/* --------------------- ES 3.2.7 (Start)--------------------------- */
/**
 * To rename a few terms in compose & reports menu
 * ES version 3.2.7 onwards
 */
function ig_es_update_327_change_email_type() {

	global $wpdb;

	// Compose table
	$template_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_templatetable'" );
	if ( $template_table_exists > 0 ) {
		$wpdb->query(
			"UPDATE {$wpdb->prefix}es_templatetable
						   SET es_email_type =
						   ( CASE
								WHEN es_email_type = 'Static Template' THEN 'Newsletter'
								WHEN es_email_type = 'Dynamic Template' THEN 'Post Notification'
								ELSE es_email_type
							 END ) "
		);
	}

	// Sent Details table
	$wpdb->query(
		"UPDATE {$wpdb->prefix}es_sentdetails
					   SET es_sent_type =
					   ( CASE
							WHEN es_sent_type = 'Instant Mail' THEN 'Immediately'
							WHEN es_sent_type = 'Cron Mail' THEN 'Cron'
							ELSE es_sent_type
						 END ),
						   es_sent_source =
					   ( CASE
							WHEN es_sent_source = 'manual' THEN 'Newsletter'
							WHEN es_sent_source = 'notification' THEN 'Post Notification'
							ELSE es_sent_source
					   END ) "
	);

	// Delivery Reports table
	$wpdb->query(
		"UPDATE {$wpdb->prefix}es_deliverreport
					   SET es_deliver_senttype =
					   ( CASE
							WHEN es_deliver_senttype = 'Instant Mail' THEN 'Immediately'
							WHEN es_deliver_senttype = 'Cron Mail' THEN 'Cron'
							ELSE es_deliver_senttype
						 END ) "
	);

}

function ig_es_update_327_db_version() {
	ES_Install::update_db_version( '3.2.7' );
}

/* --------------------- ES 3.2.7 (End)--------------------------- */

/* --------------------- ES 3.3.6 (Start)--------------------------- */
/**
 * To migrate Email Settings data from custom pluginconfig table to WordPress options table and to update user roles
 * ES version 3.3 onwards
 */
function ig_es_update_330_import_options() {
	global $wpdb;

	$settings_to_rename = array(
		'es_c_fromname'         => 'ig_es_fromname',
		'es_c_fromemail'        => 'ig_es_fromemail',
		'es_c_mailtype'         => 'ig_es_emailtype',
		'es_c_adminmailoption'  => 'ig_es_notifyadmin',
		'es_c_adminemail'       => 'ig_es_adminemail',
		'es_c_adminmailsubject' => 'ig_es_admin_new_sub_subject',
		'es_c_adminmailcontant' => 'ig_es_admin_new_sub_content',
		'es_c_usermailoption'   => 'ig_es_welcomeemail',
		'es_c_usermailsubject'  => 'ig_es_welcomesubject',
		'es_c_usermailcontant'  => 'ig_es_welcomecontent',
		'es_c_optinoption'      => 'ig_es_optintype',
		'es_c_optinsubject'     => 'ig_es_confirmsubject',
		'es_c_optincontent'     => 'ig_es_confirmcontent',
		'es_c_optinlink'        => 'ig_es_optinlink',
		'es_c_unsublink'        => 'ig_es_unsublink',
		'es_c_unsubtext'        => 'ig_es_unsubcontent',
		'es_c_unsubhtml'        => 'ig_es_unsubtext',
		'es_c_subhtml'          => 'ig_es_successmsg',
		'es_c_message1'         => 'ig_es_suberror',
		'es_c_message2'         => 'ig_es_unsuberror',
	);

	$options_to_rename = array(
		'es_c_post_image_size'      => 'ig_es_post_image_size',
		'es_c_sentreport'           => 'ig_es_sentreport',
		'es_c_sentreport_subject'   => 'ig_es_sentreport_subject',
		'es_c_rolesandcapabilities' => 'ig_es_rolesandcapabilities',
		'es_c_cronurl'              => 'ig_es_cronurl',
		'es_cron_mailcount'         => 'ig_es_cron_mailcount',
		'es_cron_adminmail'         => 'ig_es_cron_adminmail',
		'es_c_emailsubscribers'     => 'ig_es_sync_wp_users',
	);

	// Rename options that were previously stored
	foreach ( $options_to_rename as $old_option_name => $new_option_name ) {
		$option_value = get_option( $old_option_name );
		if ( $option_value ) {
			update_option( $new_option_name, $option_value );
			delete_option( $old_option_name );
		}
	}

	// Do not pull data for new users as there is no pluginconfig table created on activation
	$table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_pluginconfig'" );

	if ( $table_exists > 0 ) {
		global $wpdb;
		// Pull out ES settings data of existing users and move them to options table
		$settings_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}es_pluginconfig WHERE es_c_id = %d", 1 ), ARRAY_A );

		if ( ! empty( $settings_data ) ) {
			foreach ( $settings_data as $name => $value ) {
				if ( array_key_exists( $name, $settings_to_rename ) ) {
					update_option( $settings_to_rename[ $name ], $value );
				}
			}
		}
	}

	// Update User Roles Settings
	$es_c_rolesandcapabilities = get_option( 'ig_es_rolesandcapabilities', 'norecord' );

	if ( 'norecord' != $es_c_rolesandcapabilities ) {
		$remove_roles = array( 'es_roles_setting', 'es_roles_help' );
		foreach ( $es_c_rolesandcapabilities as $role_name => $role_value ) {
			if ( in_array( $role_name, $remove_roles ) ) {
				unset( $es_c_rolesandcapabilities[ $role_name ] );
			}
		}
		update_option( 'ig_es_rolesandcapabilities', $es_c_rolesandcapabilities );
	}

}

function ig_es_update_330_db_version() {
	ES_Install::update_db_version( '3.3.0' );
}

/* --------------------- ES 3.3.0 (End)--------------------------- */

/* --------------------- ES 3.3.6 (Start)--------------------------- */
/**
 * To alter templatable for extra slug column - to support new template designs
 * ES version 3.3.6 onwards
 */
function ig_es_update_336_add_template_slug() {

	global $wpdb;

	$template_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_templatetable'" );
	if ( $template_table_exists > 0 ) {

		// To check if column es_templ_slug exists or not
		$results_template_col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}es_templatetable LIKE %s", 'es_templ_slug' ), 'ARRAY_A' );
		$template_num_rows    = $wpdb->num_rows;

		// If column doesn't exists, then insert it
		if ( '1' != $template_num_rows ) {
			// Template table
			$wpdb->query(
				"ALTER TABLE {$wpdb->prefix}es_templatetable
								ADD COLUMN es_templ_slug VARCHAR(255) NULL
								AFTER es_email_type"
			);
		}
	}
}

function ig_es_update_336_db_version() {
	ES_Install::update_db_version( '3.3.6' );
}

/* --------------------- ES 3.3.6 (End)--------------------------- */

/* --------------------- ES 3.4.0 (Start)--------------------------- */
/**
 * To convert Compose to Custom Post Type (to support new template designs) AND Converting keywords structure
 * ES version 3.4.0 onwards
 */
function ig_es_update_340_migrate_templates_to_cpt() {

	global $wpdb, $wpbd;

	// MIGRATION OF TEMPLATE TABLE TO CTP
	$es_template_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_templatetable'" );
	if ( $es_template_table_exists > 0 ) {

		$es_migration_success = get_option( 'es_template_migration_done', 'nodata' );
		if ( 'yes' == $es_migration_success ) {
			return;
		}

		$arrRes = $wpdb->get_results(
			"SELECT es_tt.*,
					IFNULL(es_not.es_note_id, '') as es_note_id
   					FROM {$wpdb->prefix}es_templatetable AS es_tt
   					LEFT JOIN {$wpdb->prefix}es_notification AS es_not
	   				ON(es_not.es_note_templ = es_tt.es_templ_id)",
			ARRAY_A
		);

		if ( ! empty( $arrRes ) ) {

			$es_note_ids = array();

			foreach ( $arrRes as $tmpl ) {
				// Create post object
				$es_post = array(
					'post_title'   => wp_strip_all_tags( $tmpl['es_templ_heading'] ),
					'post_content' => $tmpl['es_templ_body'],
					'post_status'  => 'publish',
					'post_type'    => 'es_template',
					'meta_input'   => array(
						'es_template_type' => $tmpl['es_email_type'],
					),
				);
				// Insert the post into the database
				$last_inserted_id = wp_insert_post( $es_post );

				if ( 'Post Notification' == $tmpl['es_email_type'] && ! empty( $tmpl['es_note_id'] ) ) {
					$es_note_ids[] = 'WHEN es_note_id = ' . $tmpl['es_note_id'] . ' THEN ' . $last_inserted_id;
				}
			}

			if ( ! empty( $es_note_ids ) ) {
				// To update the 'es_note_templ' ids
				$sSql = "UPDATE {$wpdb->prefix}es_notification SET es_note_templ = (CASE " . implode( ' ', $es_note_ids ) . ' END)';
				$wpbd->query( $sSql );
			}
		}

		update_option( 'es_template_migration_done', 'yes' );
	}
}

function ig_es_update_340_migrate_keywords() {

	global $wpdb, $wpbd;
	// Keywords in Compose table
	$keywords_to_rename_in_compose = array(
		'###NAME###'               => '{{NAME}}',
		'###EMAIL###'              => '{{EMAIL}}',
		'###DATE###'               => '{{DATE}}',
		'###POSTTITLE###'          => '{{POSTTITLE}}',
		'###POSTIMAGE###'          => '{{POSTIMAGE}}',
		'###POSTDESC###'           => '{{POSTDESC}}',
		'###POSTFULL###'           => '{{POSTFULL}}',
		'###POSTAUTHOR###'         => '{{POSTAUTHOR}}',
		'###POSTLINK###'           => '{{POSTLINK}}',
		'###POSTLINK-ONLY###'      => '{{POSTLINK-ONLY}}',
		'###POSTLINK-WITHTITLE###' => '{{POSTLINK-WITHTITLE}}',
	);

	// Keywords in Settings
	$keywords_in_settings_to_rename = array(
		'###NAME###'      => '{{NAME}}',
		'###EMAIL###'     => '{{EMAIL}}',
		'###GROUP###'     => '{{GROUP}}',
		'###COUNT###'     => '{{COUNT}}',
		'###UNIQUE###'    => '{{UNIQUE}}',
		'###STARTTIME###' => '{{STARTTIME}}',
		'###ENDTIME###'   => '{{ENDTIME}}',
		'###LINK###'      => '{{LINK}}',
		'###DATE###'      => '{{DATE}}',
		'###SUBJECT###'   => '{{SUBJECT}}',
		'###DBID###'      => '{{DBID}}',
		'###GUID###'      => '{{GUID}}',
	);

	// Updating keywords in post_title column where `post_type` = 'es_template'
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}posts SET post_title = REPLACE(post_title,'###POSTTITLE###','{{POSTTITLE}}') WHERE post_type = %s", 'es_template' ) );

	// Updating keywords in post_content column where `post_type` = 'es_template'
	$compose_keywords = array();
	foreach ( $keywords_to_rename_in_compose as $key => $value ) {
		$compose_keywords[] = "post_content = REPLACE(post_content,'" . $key . "','" . $value . "')";
	}

	$es_post_content_query = "UPDATE {$wpdb->prefix}posts SET " . implode( ', ', $compose_keywords ) . " WHERE post_type = 'es_template'";
	$wpbd->query( $es_post_content_query );

	// Updating keywords in options
	$es_admin_new_sub_content = get_option( 'ig_es_admin_new_sub_content', 'nodata' );
	$es_sent_report_content   = get_option( 'ig_es_sentreport', 'nodata' );
	$es_confirm_content       = get_option( 'ig_es_confirmcontent', 'nodata' );
	$es_welcome_content       = get_option( 'ig_es_welcomecontent', 'nodata' );
	$es_unsub_content         = get_option( 'ig_es_unsubcontent', 'nodata' );
	$es_cron_admin_mail       = get_option( 'ig_es_cron_adminmail', 'nodata' );
	$es_optin_link            = get_option( 'ig_es_optinlink', 'nodata' );
	$es_unsub_link            = get_option( 'ig_es_unsublink', 'nodata' );

	foreach ( $keywords_in_settings_to_rename as $key => $value ) {
		if ( 'nodata' != $es_admin_new_sub_content ) {
			$es_admin_new_sub_content = str_replace( $key, $value, $es_admin_new_sub_content );
			update_option( 'ig_es_admin_new_sub_content', $es_admin_new_sub_content );
		}

		if ( 'nodata' != $es_sent_report_content ) {
			$es_sent_report_content = str_replace( $key, $value, $es_sent_report_content );
			update_option( 'ig_es_sentreport', $es_sent_report_content );
		}

		if ( 'nodata' != $es_confirm_content ) {
			$es_confirm_content = str_replace( $key, $value, $es_confirm_content );
			update_option( 'ig_es_confirmcontent', $es_confirm_content );
		}

		if ( 'nodata' != $es_welcome_content ) {
			$es_welcome_content = str_replace( $key, $value, $es_welcome_content );
			update_option( 'ig_es_welcomecontent', $es_welcome_content );
		}

		if ( 'nodata' != $es_unsub_content ) {
			$es_unsub_content = str_replace( $key, $value, $es_unsub_content );
			update_option( 'ig_es_unsubcontent', $es_unsub_content );
		}

		if ( 'nodata' != $es_cron_admin_mail ) {
			$es_cron_admin_mail = str_replace( $key, $value, $es_cron_admin_mail );
			update_option( 'ig_es_cron_adminmail', $es_cron_admin_mail );
		}

		if ( 'nodata' != $es_optin_link ) {
			$es_optin_link = str_replace( $key, $value, $es_optin_link );
			update_option( 'ig_es_optinlink', $es_optin_link );
		}

		if ( 'nodata' != $es_unsub_link ) {
			$es_unsub_link = str_replace( $key, $value, $es_unsub_link );
			update_option( 'ig_es_unsublink', $es_unsub_link );
		}
	}

}

function ig_es_update_340_db_version() {
	ES_Install::update_db_version( '3.4.0' );

}

/* --------------------- ES 3.4.0 (End)--------------------------- */

/* --------------------- ES 3.5.16(Start)--------------------------- */
/**
 * Add es_subscriber_ips table to handle rate limit.
 * ES version 3.5.16 onwards
 */
function ig_es_update_3516_create_subscribers_ips_table() {

	global $wpdb;

	$charset_collate         = $wpdb->get_charset_collate();
	$es_subscriber_ips_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}es_subscriber_ips (
									ip varchar(45) NOT NULL, 
									created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
									PRIMARY KEY  (created_at, ip),
									KEY ip (ip)
							  ) $charset_collate";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $es_subscriber_ips_table );
}

function ig_es_update_3516_db_version() {
	ES_Install::update_db_version( '3.5.16' );
}

/* --------------------- ES 3.5.16 (End)--------------------------- */


/* --------------------- ES 4.0.0 (Start)--------------------------- */
function ig_es_update_400_delete_tables() {
	global $wpdb, $wpbd;

	$tables_to_delete = array(
		$wpdb->prefix . 'ig_blocked_emails',
		$wpdb->prefix . 'ig_campaigns',
		// $wpdb->prefix . 'ig_contacts',
		$wpdb->prefix . 'ig_contacts_ips',
		$wpdb->prefix . 'ig_forms',
		$wpdb->prefix . 'ig_lists',
		$wpdb->prefix . 'ig_lists_contacts',
		$wpdb->prefix . 'ig_mailing_queue',
		$wpdb->prefix . 'ig_sending_queue',
		$wpdb->prefix . 'ig_queue',
		$wpdb->prefix . 'ig_actions',
		$wpdb->prefix . 'ig_links',
		$wpdb->prefix . 'ig_workflows',
		$wpdb->prefix . 'ig_workflows_queue',
	);

	foreach ( $tables_to_delete as $table ) {
		$query = "DROP TABLE IF EXISTS {$table}";
		$wpbd->query( $query );
	}
}

function ig_es_update_400_create_tables() {
	ES_Install::create_tables( '4.0.0' );
}

function ig_es_update_400_import_options() {

	$options = ES_Install::get_options();
	foreach ( $options as $option => $data ) {
		if ( ! empty( $data['old_option'] ) ) {
			$value = get_option( $data['old_option'], '--NOT-FOUND--' );

			// Don't find the value? Get default.
			if ( '--NOT-FOUND--' === $value ) {
				$value = $data['default'];
			}

			if ( ! empty( $data['action'] ) && 'convert_space_to_underscore' === $data['action'] ) {
				$value = strtolower( ig_es_convert_space_to_underscore( $value ) );
			}

			update_option( $option, $value );
		}
	}
}

function ig_es_update_400_migrate_lists() {
	/**
	 *  Get Unique groups from es_emaillist table and import it into ig_lists table
	 */
	global $wpdb;

	// Collect list name from Email list
	$lists = $wpdb->get_col( "SELECT distinct(es_email_group) FROM {$wpdb->prefix}es_emaillist" );

	// Collect list name from notification table
	$ps_lists = $wpdb->get_col( "SELECT distinct(es_note_group) FROM {$wpdb->prefix}es_notification" );

	if ( count( $lists ) > 0 || count( $ps_lists ) > 0 ) {
		$all_lists = array_unique( array_merge( $lists, $ps_lists ) );
		ES()->lists_db->add_lists( $all_lists );
	}

}

// Import contacts from es_emaillist table to ig_contacts and ig_lists_contacts table
function ig_es_update_400_migrate_subscribers() {
	ES()->contacts_db->migrate_subscribers_from_older_version();
}

function ig_es_update_400_migrate_post_notifications() {
	ES()->campaigns_db->migrate_post_notifications();
}


function ig_es_update_400_migrate_notifications() {
	/**
	 * - Migrate notifications from es_notification to ig_es_mailing_queue table
	 * es_notification => ig_es_mailing_queue
	 */

	ES_DB_Mailing_Queue::migrate_notifications();
}

function ig_es_update_400_migrate_reports_data() {

	/**
	 * - Migrate individual notification data from es_deliverreport to ig_es_sending_queue table
	 * es_deliverreport => ig_es_sending_queue
	 */
	// @ini_set( 'max_execution_time', 0 );
	// ES_DB_Sending_Queue::migrate_reports_data();
}

function ig_es_update_400_migrate_group_selectors_forms() {
	ES()->forms_db->migrate_advanced_forms();
}

function ig_es_update_400_db_version() {
	ES_Install::update_db_version( '4.0.0' );
}

/* --------------------- ES 4.0.0 (End)--------------------------- */

function ig_es_update_401_migrate_newsletters() {
	// Migrate newsletters from es_sentdetails table
	ES()->campaigns_db->migrate_newsletters();
}

function ig_es_update_401_db_version() {
	ES_Install::update_db_version( '4.0.1' );
}

/* --------------------- ES 4.0.1 (End)--------------------------- */

/**
 * Change es_template_type from "Post Notification" to "post_notification"
 */
function ig_es_update_402_migrate_post_notification_es_template_type() {
	ES_DB_Notifications::migrate_post_notification_es_template_type();
}

function ig_es_update_402_db_version() {
	ES_Install::update_db_version( '4.0.2' );
}

/* --------------------- ES 4.0.2(End)--------------------------- */

function ig_es_update_403_alter_campaigns_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `name` varchar(255) DEFAULT NULL" );

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `slug` varchar(255) DEFAULT NULL" );
}

function ig_es_update_403_alter_mailing_queue_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_mailing_queue MODIFY `subject` text DEFAULT ''" );
}

function ig_es_update_403_db_version() {
	ES_Install::update_db_version( '4.0.3' );
}

/* --------------------- ES 4.0.3(End)--------------------------- */

function ig_es_update_405_alter_forms_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_forms MODIFY `name` varchar(255) DEFAULT NULL" );
}

function ig_es_update_405_alter_lists_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_lists MODIFY `name` varchar(255) DEFAULT NULL" );

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_lists MODIFY `slug` varchar(255) DEFAULT NULL" );
}

function ig_es_update_405_migrate_widgets() {
	ES_Common::migrate_widgets();
}


function ig_es_update_405_db_version() {
	ES_Install::update_db_version( '4.0.5' );
}

/* --------------------- ES 4.0.5(End)--------------------------- */
function ig_es_update_4010_update_sending_status() {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}ig_sending_queue SET status = %s WHERE status = %s", array( 'Sent', 'Nodata' ) ) );
}

function ig_es_update_4010_db_version() {
	ES_Install::update_db_version( '4.0.10' );
}

/* --------------------- ES 4.0.10(End)--------------------------- */

function ig_es_update_4011_migrate_newsletter_es_template_type() {
	ES_DB_Notifications::migrate_newsletter_es_template_type();
}

function ig_es_update_4011_update_campaign_id_in_mailing_queue() {
	ES()->campaigns_db->update_campaign_id_in_mailing_queue();
}

function ig_es_update_4011_db_version() {
	ES_Install::update_db_version( '4.0.11' );
}

/* --------------------- ES 4.0.11(End)--------------------------- */

function ig_es_update_4015_alter_blocked_emails_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_blocked_emails MODIFY `id` int(10) NOT NULL AUTO_INCREMENT" );
}

function ig_es_update_4015_db_version() {
	ES_Install::update_db_version( '4.0.15' );
}

/* --------------------- ES 4.0.15(End)--------------------------- */
function ig_es_update_411_alter_contacts_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_contacts MODIFY `source` varchar(50) DEFAULT NULL" );
}

function ig_es_update_411_db_version() {
	ES_Install::update_db_version( '4.1.1' );
}

/* --------------------- ES 4.1.1(End)--------------------------- */
function ig_es_update_417_alter_campaigns_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_campaigns" );
	if ( ! in_array( 'meta', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN meta longtext NULL AFTER `status`" );
	}
}

function ig_es_update_417_alter_mailing_queue_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_mailing_queue" );
	if ( ! in_array( 'meta', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_mailing_queue ADD COLUMN meta longtext NULL AFTER `finish_at`" );
	}
}

function ig_es_update_417_db_version() {
	ES_Install::update_db_version( '4.1.7' );
}

/* --------------------- ES 4.1.7(End)--------------------------- */

/**
 * Migrate categories names into category ids
 *
 * @since 4.1.13
 */
function ig_es_update_4113_migrate_categories_in_campaigns_table() {
	global $wpdb, $wpbd;
	$campaign_category_map = $wpdb->get_results( $wpdb->prepare( "SELECT id, categories FROM {$wpdb->prefix}ig_campaigns where type = %s", 'post_notification' ), ARRAY_A );

	if ( ! empty( $campaign_category_map ) ) {
		foreach ( $campaign_category_map as $value ) {
			$categories_str = ES_Common::prepare_categories_migration_string( $value['categories'] );
			// remove category with 0
			$categories_str = str_replace( '##0', '', $categories_str );
			$categories[]   = " WHEN categories = '" . esc_sql( $value['categories'] ) . "' THEN '" . $categories_str . "'";
		}

		$update_query = "UPDATE {$wpdb->prefix}ig_campaigns SET categories = (CASE " . implode( ' ', $categories ) . ' ELSE categories END)';
		$wpbd->query( $update_query );
	}
}

/**
 * Create different files.
 *
 * @since 4.1.13
 */
function ig_es_update_4113_create_files() {
	ES_Install::create_files();
}

/**
 * Add DB update time
 *
 * @since 4.1.13
 */
function ig_es_update_4113_db_version() {
	ES_Install::update_db_version( '4.1.13' );
}

/* --------------------- ES 4.1.13(End)--------------------------- */

/**
 * Migrate db_updated_at options into new structure
 *
 * @since 4.1.15
 */
function ig_es_update_4115_migrate_db_update_history() {

	$db_update_history_option = 'db_update_history';
	$db_update_history_data   = ES_Common::get_ig_option( $db_update_history_option, array() );

	/**
	 * We have already created these many options to store date and time of individual
	 * version installation. Now, we are merging into single option
	 */
	$db_update_at_options = array(
		'4.0.0'  => '400_db_updated_at',
		'4.0.1'  => '401_db_updated_at',
		'4.0.2'  => '402_db_updated_at',
		'4.0.3'  => '403_db_updated_at',
		'4.0.5'  => '405_db_updated_at',
		'4.0.10' => '4010_db_updated_at',
		'4.0.11' => '4011_db_updated_at',
		'4.0.15' => '4015_db_updated_at',
		'4.1.1'  => '411_db_updated_at',
		'4.1.7'  => '417_db_updated_at',
		'4.1.13' => '4113_db_updated_at',
	);

	foreach ( $db_update_at_options as $version => $option ) {
		$value = ES_Common::get_ig_option( $option, false );
		if ( $value ) {
			$db_update_history_data[ $version ] = $value;

			// As we are migrating to new structure
			// Delete existing option
			ES_Common::delete_ig_option( $option );
		}
	}

	ES_Common::set_ig_option( $db_update_history_option, $db_update_history_data );

}

/**
 * Add form success message according to opt-in type
 *
 * @since 4.1.15
 */
function ig_es_update_4115_add_form_submission_option() {
	$ig_es_option_type = get_option( 'ig_es_optin_type' );
	$message           = ( 'double_opt_in' == $ig_es_option_type ) ? __( 'Your subscription was successful! Kindly check your mailbox and confirm your subscription. If you don\'t see the email within a few minutes, check the spam/junk folder.', 'email-subscribers' ) : __( 'Successfully Subscribed.', 'email-subscribers' );
	update_option( 'ig_es_form_submission_success_message', $message );
}

/**
 * Update DB Update history
 *
 * @since 4.1.15
 */
function ig_es_update_4115_db_version() {
	ES_Install::update_db_version( '4.1.15' );
}

/* --------------------- ES 4.1.15(End)--------------------------- */
/**
 * Alter campaigns table
 *
 * @since 4.2.0
 */
function ig_es_update_420_alter_campaigns_table() {
	global $wpdb;

	$wpdb->hide_errors();

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_campaigns" );

	// Add `parent_id`
	if ( ! in_array( 'parent_id', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `parent_id` int(10) unsigned DEFAULT NULL AFTER `type`" );
	}

	// Add `parent_type`
	if ( ! in_array( 'parent_type', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `parent_type` varchar(50) DEFAULT NULL AFTER `parent_id`" );
	}

	// Add `subject`
	if ( ! in_array( 'subject', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `subject` varchar(255) DEFAULT NULL AFTER `parent_type`" );
	}

	// Add `body`
	if ( ! in_array( 'body', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `body` longtext DEFAULT NULL AFTER `subject`" );
	}

	// Drop `sequence_ids`
	if ( in_array( 'sequence_ids', $cols ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns DROP COLUMN `sequence_ids`" );
	}
}

/**
 * Create New Tables
 *
 * @since 4.2.0
 */
function ig_es_update_420_create_tables() {
	ES_Install::create_tables( '4.2.0' );
}

/**
 * Migrate mailer and SMTP options
 *
 * @since 4.2.0
 */
function ig_es_update_420_migrate_mailer_options() {
	$es_email_type                   = get_option( 'ig_es_email_type' );
	$default_mailer                  = ( 'php_html_mail' === $es_email_type || 'php_plaintext_mail' === $es_email_type ) ? 'phpmail' : 'wpmail';
	$ig_es_mailer_settings['mailer'] = $default_mailer;
	// smtp settings default option
	$enable_smtp                     = get_option( 'ig_es_enable_smtp', 'no' );
	$ig_es_mailer_settings['mailer'] = ( 'yes' === $enable_smtp ) ? 'smtp' : $default_mailer;
	$smtp_host                       = get_option( 'ig_es_smtp_host', '' );
	$smtp_port                       = get_option( 'ig_es_smtp_port', 25 );
	$smtp_encryption                 = get_option( 'ig_es_smtp_encryption', 'tls' );
	$smtp_auth                       = get_option( 'ig_es_smtp_authentication', 'yes' );
	$smtp_username                   = get_option( 'ig_es_smtp_username', '' );
	$smtp_password                   = get_option( 'ig_es_smtp_password', '' );

	$ig_es_mailer_settings['smtp']['enable_smtp']         = $enable_smtp;
	$ig_es_mailer_settings['smtp']['smtp_host']           = $smtp_host;
	$ig_es_mailer_settings['smtp']['smtp_port']           = $smtp_port;
	$ig_es_mailer_settings['smtp']['smtp_encryption']     = $smtp_encryption;
	$ig_es_mailer_settings['smtp']['smtp_authentication'] = $smtp_auth;
	$ig_es_mailer_settings['smtp']['smtp_username']       = $smtp_username;
	$ig_es_mailer_settings['smtp']['smtp_password']       = $smtp_password;
	update_option( 'ig_es_mailer_settings', $ig_es_mailer_settings );

}


/**
 * Update DB Update history
 *
 * @since 4.2.0
 */
function ig_es_update_420_db_version() {
	ES_Install::update_db_version( '4.2.0' );
}

/* --------------------- ES 4.2.0(End)--------------------------- */

function ig_es_update_421_drop_tables() {
	global $wpdb, $wpbd;

	/**
	 * Note: Still we are not using ig_contact_meta table.
	 * So, it's ok to drop ig_contact_meta table now
	 * Because we want to use WordPress' metadata api and for that we need ig_contactmeta table
	 * Which we are going to create in 'ig_es_update_421_create_table' function.
	 */
	$tables_to_drop = array(
		$wpdb->prefix . 'ig_contact_meta',
		$wpdb->prefix . 'ig_actions',
	);

	foreach ( $tables_to_drop as $table ) {
		$query = "DROP TABLE IF EXISTS {$table}";
		$wpbd->query( $query );
	}
}

/**
 * Create New Table
 *
 * @since 4.2.1
 */
function ig_es_update_421_create_tables() {
	ES_Install::create_tables( '4.2.1' );
}

/**
 * Update DB Update history
 *
 * @since 4.2.1
 */
function ig_es_update_421_db_version() {
	ES_Install::update_db_version( '4.2.1' );
}

/* --------------------- ES 4.2.1(End)--------------------------- */

/**
 * Drop ig_links table
 *
 * @since 4.2.4
 */
function ig_es_update_424_drop_tables() {

	global $wpdb, $wpbd;

	/**
	 * Note: Still we are not using ig_links table.
	 * So, it's ok to drop ig_links table now as we want to modify structure
	 * Which we are going to create in 'ig_es_update_424_create_table' function.
	 */
	$tables_to_drop = array(
		$wpdb->prefix . 'ig_links',
	);

	foreach ( $tables_to_drop as $table ) {
		$query = "DROP TABLE IF EXISTS {$table}";
		$wpbd->query( $query );
	}
}

/**
 * Create ig_links table
 *
 * @since 4.2.4
 */
function ig_es_update_424_create_tables() {
	ES_Install::create_tables( '4.2.4' );
}

/**
 * Update DB Update history
 *
 * @since 4.2.4
 */
function ig_es_update_424_db_version() {
	ES_Install::update_db_version( '4.2.4' );
}

/* --------------------- ES 4.2.4(End)--------------------------- */

function ig_es_update_430_alter_campaigns_table() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `from_name` varchar(250) DEFAULT NULL" );

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `from_email` varchar(150) DEFAULT NULL" );

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `reply_to_name` varchar(250) DEFAULT NULL" );

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `reply_to_email` varchar(150) DEFAULT NULL" );
}

/**
 * Update DB Update history
 *
 * @since 4.3.0
 */
function ig_es_update_430_db_version() {
	ES_Install::update_db_version( '4.3.0' );
}

/* --------------------- ES 4.3.0(End)--------------------------- */

/**
 * Set default permissions
 *
 * @since 4.3.1
 */
function ig_es_update_431_set_default_permissions() {
	if ( get_option( 'ig_es_user_roles' ) === false ) {
		update_option( 'ig_es_user_roles', ES_Install::get_default_permissions() );
	}
}

/**
 * Delete Lists Permanently
 *
 * @since 4.3.1
 */
function ig_es_update_431_permanently_delete_lists() {
	global $wpdb;

	$wpdb->query( "DELETE FROM {$wpdb->prefix}ig_lists WHERE deleted_at IS NOT NULL" );
}

/**
 * Delete Forms Permanently
 *
 * @since 4.3.1
 */
function ig_es_update_431_permanently_delete_forms() {
	global $wpdb;

	$wpdb->query( "DELETE FROM {$wpdb->prefix}ig_forms WHERE deleted_at IS NOT NULL" );
}

/**
 * Delete unwanted options
 *
 * @since 4.3.1
 */
function ig_es_update_431_delete_options() {
	global $wpbd;

	$options_to_delete = array(
		'ig_es_smtp_host',
		'ig_es_smtp_port',
		'ig_es_smtp_username',
		'ig_es_smtp_password',
		'ig_es_smtp_authentication',
		'ig_es_smtp_encryption',
		'ig_es_unsublink',
		'ig_es_unsuberror',
		'ig_es_unsubcontent',
	);

	$options_str = "'" . implode( "','", $options_to_delete ) . "'";

	$wpbd->query(
		"DELETE FROM {$wpbd->prefix}options WHERE option_name IN($options_str, %s)"
	);
}

/**
 * Disable autoload for all ES options
 *
 * @since 4.3.1
 */
function ig_es_update_431_disable_autoload_options() {
	global $wpdb;

	$wpdb->query( "UPDATE {$wpdb->prefix}options SET `autoload` = 'no' WHERE option_name LIKE 'ig_es_%'" );
}

/**
 * Update DB Update history
 *
 * @since 4.3.1
 */
function ig_es_update_431_db_version() {
	ES_Install::update_db_version( '4.3.1' );
}

/* --------------------- ES 4.3.1(End)--------------------------- */

/**
 * Import Email Templates
 *
 * @sicne 4.3.2
 */
function ig_es_update_432_import_bfcm_templates() {
	// ES_Install::load_templates();
}

/**
 * Update DB Update history
 *
 * @since 4.3.2
 */
function ig_es_update_432_db_version() {
	ES_Install::update_db_version( '4.3.2' );
}

/* --------------------- ES 4.3.2(End)--------------------------- */
/**
 * Delete Campaigns Permanently
 *
 * @since 4.3.4
 * @since 4.3.4.1 Added and condition
 */
function ig_es_update_434_permanently_delete_campaigns() {

	global $wpdb;

	$wpdb->query( "DELETE FROM {$wpdb->prefix}ig_campaigns WHERE deleted_at IS NOT NULL AND deleted_at != '0000-00-00 00:00:00' " );
}

/**
 * Update DB Update history
 *
 * @since 4.3.4
 */
function ig_es_update_434_db_version() {
	ES_Install::update_db_version( '4.3.4' );
}

/* --------------------- ES 4.3.4(End)--------------------------- */


/* --------------------- ES 4.4.1(Start)--------------------------- */
/**
 * Create Workflows Tables.
 *
 * @since 4.4.1
 */
function ig_es_update_441_create_tables() {
	ES_Install::create_tables( '4.4.1' );
}

/**
 * Migrate audience sync setting to related workflows/admin settings.
 *
 * @since 4.4.1
 */
function ig_es_update_441_migrate_audience_sync_settings() {
	ES()->workflows_db->migrate_audience_sync_settings_to_workflows();
	ES()->workflows_db->migrate_audience_sync_settings_to_admin_settings();
}

/**
 * Update DB version
 *
 * @since 4.4.1
 */
function ig_es_update_441_db_version() {
	ES_Install::update_db_version( '4.4.1' );
}

/* --------------------- ES 4.4.1(End)--------------------------- */


/* --------------------- ES 4.4.2(Start)--------------------------- */

/**
 * Adding workflows user role permissions.
 *
 * @since 4.4.2
 */
function ig_es_update_442_set_workflows_default_permission() {
	$user_role_permissions = get_option( 'ig_es_user_roles', false );
	if ( false === $user_role_permissions ) {
		update_option( 'ig_es_user_roles', ES_Install::get_default_permissions() );
	} elseif ( ! empty( $user_role_permissions ) && is_array( $user_role_permissions ) ) {
		$user_role_permissions['workflows'] = array(
			'administrator' => 'yes',
		);
		update_option( 'ig_es_user_roles', $user_role_permissions );
	}
}

/**
 * Update DB version
 *
 * @since 4.4.2
 */
function ig_es_update_442_db_version() {
	ES_Install::update_db_version( '4.4.2' );
}

/* --------------------- ES 4.4.2(End)--------------------------- */

/* --------------------- ES 4.4.9(Start)--------------------------- */

/*
 * Verify table structure. If table is not created, this will create table
 */
function ig_es_update_449_create_tables() {
	ES_Install::create_tables();
}

/**
 * Update DB version
 *
 * @since 4.4.2
 */
function ig_es_update_449_db_version() {
	ES_Install::update_db_version( '4.4.9' );
}
/* --------------------- ES 4.4.9(End)--------------------------- */

/* --------------------- ES 4.4.10(Start)--------------------------- */

/**
 * Load templates
 *
 * @since 4.4.10
 */
function ig_es_update_4410_load_templates() {
	// ES_Install::load_templates( true );
}

/**
 * Update DB version
 *
 * @since 4.4.10
 */
function ig_es_update_4410_db_version() {
	ES_Install::update_db_version( '4.4.10' );
}

/* --------------------- ES 4.4.10(End)--------------------------- */


/* --------------------- ES 4.5.0(Start)--------------------------- */
function ig_es_update_450_alter_actions_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_actions" );

	if ( ! in_array( 'ip', $cols ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_actions 
			ADD COLUMN ip varchar(50) NULL AFTER `list_id`,
			ADD COLUMN country varchar(50) NULL AFTER `ip`,
			ADD COLUMN device varchar(50) NULL AFTER `country`,
			ADD COLUMN browser varchar(50) NULL AFTER `device`,
			ADD COLUMN email_client varchar(50) NULL AFTER `browser`,
			ADD COLUMN os varchar(50) NULL AFTER `email_client`"
		);
	}
}

/**
 * Update DB version
 *
 * @since 4.5.0
 */
function ig_es_update_450_db_version() {
	ES_Install::update_db_version( '4.5.0' );
}
/* --------------------- ES 4.5.0(End)--------------------------- */

/* --------------------- ES 4.5.7(Start)--------------------------- */
function ig_es_update_457_alter_list_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_lists" );

	if ( ! in_array( 'hash', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_lists 
			ADD COLUMN `hash` varchar(12) NULL AFTER `name`"
		);
	}
}

/**
 * Add hash string in existing list items.
 *
 * @since 4.5.7
 */
function ig_es_update_457_add_list_hash() {
	global $wpdb;

	$lists = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ig_lists", ARRAY_A );

	if ( ! empty( $lists ) ) {
		foreach ( $lists as $list ) {
			$list_id = $list['id'];
			if ( ! empty( $list_id ) ) {
				$list_hash = ES_Common::generate_hash( 12 );
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}ig_lists SET hash = %s WHERE id = %d AND ( hash = '' OR hash IS NULL )", array( $list_hash, $list_id ) ) );
			}
		}
	}
}

/**
 * Update DB version
 *
 * @since 4.5.7
 */
function ig_es_update_457_db_version() {
	ES_Install::update_db_version( '4.5.7' );
}
/* --------------------- ES 4.5.7(End)--------------------------- */


/* --------------------- ES 4.6.3(Start)--------------------------- */

/**
 * Add Ip column in contacts table
 * Migrate existing Ip address from lists_contacts table to contacts table
 *
 * @since 4.6.3
 */
function ig_es_update_463_alter_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'ip_address', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts
			ADD COLUMN `ip_address` varchar(50) NULL AFTER `source`"
		);

	}
}

/**
 * Migrate existing Ip address from lists_contacts table to contacts table
 *
 * @since 4.6.3
 */
function ig_es_migrate_ip_from_list_contacts_to_contacts_table() {
	ES()->contacts_db->migrate_ip_from_list_contacts_to_contacts_table();
}

/**
 * Update DB version
 *
 * @since 4.6.3
 */
function ig_es_update_463_db_version() {
	ES_Install::update_db_version( '4.6.3' );
}
/* --------------------- ES 4.6.3(End)--------------------------- */

/* --------------------- ES 4.6.5(Start)--------------------------- */
/**
 * Create Abandoned Carts Tables.
 *
 * @since 4.6.5
 */
function ig_es_update_465_create_tables() {
	ES_Install::create_tables( '4.6.5' );
}

/**
 * Update DB version
 *
 * @since 4.6.5
 */
function ig_es_update_465_db_version() {
	ES_Install::update_db_version( '4.6.5' );
}
/* --------------------- ES 4.6.5(End)--------------------------- */

/* --------------------- ES 4.6.6(Start)--------------------------- */

/**
 * Create table for storing subscribers import CSV data temporarily
 *
 * @since 4.6.6
 */
function ig_es_update_466_create_temp_import_table() {
	ES_Install::create_tables( '4.6.6' );
}

/**
 * Update DB version
 *
 * @since 4.6.6
 */
function ig_es_update_466_db_version() {
	ES_Install::update_db_version( '4.6.6' );
}
/* --------------------- ES 4.6.6(End)--------------------------- */

/* --------------------- ES 4.6.7(Start)--------------------------- */

/**
 * Add Country column in contacts table
 *
 * @since 4.6.7
 */
function ig_es_update_467_alter_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'country_code', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts
			ADD COLUMN `country_code` varchar(50) NULL AFTER `ip_address`"
		);
	}
}

/**
 * Add country code based on the contacts ip_address
 *
 * @since 4.6.7
 */
function ig_es_add_country_code_to_contacts_table() {
	IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_add_country_code_to_audience' );
}

/**
 * Update DB version
 *
 * @since 4.6.7
 */
function ig_es_update_467_db_version() {
	ES_Install::update_db_version( '4.6.7' );
}

/* --------------------- ES 4.6.7(End)--------------------------- */

/* --------------------- ES 4.6.8(Start)--------------------------- */

/**
 * Create table for storing subscribers import CSV data temporarily
 *
 * @since 4.6.8
 */
function ig_es_update_468_create_unsubscribe_feedback_table() {
	ES_Install::create_tables( '4.6.8' );
}

/**
 * Update DB version
 *
 * @since 4.6.8
 */
function ig_es_update_468_db_version() {
	ES_Install::update_db_version( '4.6.8' );
}
/* --------------------- ES 4.6.8(End)--------------------------- */

/* --------------------- ES 4.6.9(Start)--------------------------- */

/**
 * Add meta column in wc_guests table
 *
 * @since 4.6.9
 */
function ig_es_update_469_alter_wc_guests_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_wc_guests" );

	if ( ! in_array( 'meta', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_wc_guests
			ADD COLUMN `meta` longtext NULL AFTER `last_active`"
		);
	}
}

/**
 * Update DB version
 *
 * @since 4.6.9
 */
function ig_es_update_469_db_version() {
	ES_Install::update_db_version( '4.6.9' );
}

/* --------------------- ES 4.6.9(End)--------------------------- */

/* --------------------- ES 4.6.13(Start)--------------------------- */

/**
 * Migrate sequence list settings into campaign rules
 *
 * @since 4.6.13
 */
function ig_es_migrate_4613_sequence_list_settings_into_campaign_rules() {

	$args = array(
		'include_types' => array(
			'sequence_message',
		),
	);

	$sequence_campaigns = ES()->campaigns_db->get_all_campaigns( $args );
	if ( ! empty( $sequence_campaigns ) ) {
		foreach ( $sequence_campaigns as $campaign ) {
			$campaign_id = $campaign['id'];
			$list_ids    = $campaign['list_ids'];
			if ( ! empty( $campaign_id ) && ! empty( $list_ids ) ) {
				$list_ids      = explode( ',', $list_ids );
				$campaign_meta = ! empty( $campaign['meta'] ) ? maybe_unserialize( $campaign['meta'] ) : array();
				if ( empty( $campaign_meta['list_conditions'] ) ) {
					$list_conditions      = array();
					$list_conditions_data = array(
						'field'    => '_lists__in',
						'operator' => 'is',
						'value'    => array(),
					);
					foreach ( $list_ids as $index => $list_id ) {
						$list_conditions_data['value'][] = $list_id;
					}
					$list_conditions[][]              = $list_conditions_data;
					$campaign_meta['list_conditions'] = $list_conditions;
					ES()->campaigns_db->update_campaign_meta( $campaign_id, $campaign_meta );
				}
			}
		}
	}
}

/**
 * Update DB version
 *
 * @since 4.6.13
 */
function ig_es_update_4613_db_version() {
	ES_Install::update_db_version( '4.6.13' );
}

/* --------------------- ES 4.6.13(End)--------------------------- */

/* --------------------- ES 4.7.8(Start)--------------------------- */

/**
 * Add index to contact id column in the ig_list_contacts table
 *
 * We are adding index to improve the response time of the select query
 * e.g. Imrpoves performance of select query in ES_DB_Sending_Queue::queue_emails() to get contact ids
 *
 * @since 4.7.8
 */
function ig_es_add_index_to_list_contacts_table() {
	global $wpdb;

	$index_exists = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->prefix}ig_lists_contacts WHERE column_name = 'contact_id' AND key_name = 'contact_id'" );

	if ( is_null( $index_exists ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_lists_contacts 
		ADD INDEX contact_id( contact_id );"
		);
	}
}

/**
 * Update DB version
 *
 * @since 4.7.8
 */
function ig_es_update_478_db_version() {
	ES_Install::update_db_version( '4.7.8' );
}

/* --------------------- ES 4.7.8(End)--------------------------- */

/* --------------------- ES 4.7.9(Start)--------------------------- */

/**
 * Add primary key column to actions table
 *
 * On some web hosts which uses db optimization services like Percona,
 * we can't insert data into a table which don't have primay key column
 *
 * @since 4.7.9
 */
function ig_es_add_primay_key_to_actions_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_actions" );

	if ( ! in_array( 'id', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_actions 
			ADD COLUMN id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;"
		);
	}
}

/**
 * Update DB version
 *
 * @since 4.7.9
 */
function ig_es_update_479_db_version() {
	ES_Install::update_db_version( '4.7.9' );
}

/* --------------------- ES 4.7.9(End)--------------------------- */

/* --------------------- ES 4.8.3(Start)--------------------------- */

/**
 * Add engagement_score column to contacts table
 *
 * @since 4.8.3
 */
function ig_es_add_engagement_score_to_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'engagement_score', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts
			ADD COLUMN `engagement_score` FLOAT NULL AFTER `hash`"
		);
	}
}

/**
 * Calculate engagement score of existing subscribers
 *
 * @since 4.8.3
 */
function ig_es_calculate_existing_subscribers_engagement_score() {

	global $wpbd;

	// First update existing unsubscribed contact score to 0.
	$wpbd->query(
		"UPDATE {$wpbd->prefix}ig_contacts SET `engagement_score` = 0 WHERE `unsubscribed` = 1"
	);

	IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_calculate_existing_subscribers_engagement_score', array(), false );
}

/**
 * Update DB version
 *
 * @since 4.8.3
 */
function ig_es_update_483_db_version() {
	ES_Install::update_db_version( '4.8.3' );
}

/* --------------------- ES 4.8.3(End)--------------------------- */

/* --------------------- ES 4.8.4(Start)--------------------------- */

/**
 * Create New custom field table
 *
 * @since 4.8.4
 */
function ig_es_update_484_create_custom_field_table() {
	ES_Install::create_tables( '4.8.4' );
}

function ig_es_update_484_db_version() {
	ES_Install::update_db_version( '4.8.4' );
}

/* --------------------- ES 4.8.4(End)--------------------------- */


/* --------------------- ES 4.9.0(Start)--------------------------- */

/**
 * Add bounce_status column in contacts table
 *
 * @since 4.9.0
 */
function ig_es_update_490_alter_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'bounce_status', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts ADD COLUMN `bounce_status` enum('0','1','2') NOT NULL DEFAULT '0' AFTER `ip_address`"
		);

	}
}

/**
 * Update DB version
 *
 * @since 4.9.0
 */
function ig_es_update_490_db_version() {
	ES_Install::update_db_version( '4.9.0' );
}
/* --------------------- ES 4.9.0(End)--------------------------- */


/* --------------------- ES 5.0.1(Start)--------------------------- */

/**
 * Migrate notifications into workflows.
 *
 * @since 5.0.1
 */
function ig_es_update_501_migrate_notifications_into_workflows() {
	ES()->workflows_db->migrate_notifications_to_workflows();
}

/**
 * Update DB version
 *
 * @since 5.0.1
 */
function ig_es_update_501_db_version() {
	ES_Install::update_db_version( '5.0.1' );
}

/* --------------------- ES 5.0.1(End)--------------------------- */

/* --------------------- ES 5.0.3(Start)--------------------------- */

/**
 * Add send_at column in sending queue table
 *
 * @since 5.0.3
 */
function ig_es_update_503_alter_sending_queue_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_sending_queue" );

	if ( ! in_array( 'send_at', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_sending_queue ADD COLUMN `send_at` DATETIME NULL DEFAULT NULL AFTER `opened`"
		);
	}
}

/**
 * Add timezone column in contacts table
 *
 * @since 5.0.3
 */
function ig_es_update_503_alter_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'timezone', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts ADD COLUMN `timezone` VARCHAR(255) NULL DEFAULT NULL AFTER `bounce_status`"
		);
	}
}

/**
 * Add timezone based on the contacts ip_address
 *
 * @since 5.0.3
 */
function ig_es_add_timezone_to_contacts_table() {
	IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_add_country_code_to_audience' );
}

/**
 * Update DB version
 *
 * @since 5.0.3
 */
function ig_es_update_503_db_version() {
	ES_Install::update_db_version( '5.0.3' );
}
/* --------------------- ES 5.0.3(End)--------------------------- */

/* --------------------- ES (Start)--------------------------- */

/**
 * Add description column in Lists table
 *
 * @since 5.0.4
 */
function ig_es_update_504_alter_lists_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_lists" );
	if ( ! in_array( 'description', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_lists 
			ADD COLUMN `description` varchar(255) DEFAULT NULL AFTER `name`"
		);
	}
}

/**
 * Update DB version
 *
 * @since 5.0.4
 */
function ig_es_update_504_db_version() {
	ES_Install::update_db_version( '5.0.4' );
}

/* --------------------- ES 5.0.4(End)--------------------------- */

/* --------------------- ES 4.6.13(Start)--------------------------- */

/**
 * Migrate sequence list settings into campaign rules
 *
 * @since 4.6.13
 */
function ig_es_migrate_post_campaigns_list_settings_into_campaign_rules() {

	$args = array(
		'include_types' => array(
			IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
			IG_CAMPAIGN_TYPE_POST_DIGEST
		),
	);

	$post_campaigns = ES()->campaigns_db->get_all_campaigns( $args );
	if ( ! empty( $post_campaigns ) ) {
		foreach ( $post_campaigns as $campaign ) {
			
			$campaign_id = $campaign['id'];
			
			if ( ! empty( $campaign_id ) ) {

				$data_to_update = array();
				
				if ( ! empty( $campaign['base_template_id'] ) ) {
					$template_id = $campaign['base_template_id'];
					$template    = get_post( $template_id );
					if ( is_object( $template ) ) {
						$campaign_name = $template->post_title;
						$campaign_body = $template->post_content;

						$data_to_update['name']    = $campaign_name;
						$data_to_update['subject'] = $campaign_name;
						$data_to_update['body']    = $campaign_body;
					}
				}

				if ( ! empty( $campaign['list_ids'] ) ) {
					$list_ids      = $campaign['list_ids'];
					$list_ids      = explode( ',', $list_ids );
					$campaign_meta = ! empty( $campaign['meta'] ) ? maybe_unserialize( $campaign['meta'] ) : array();
					if ( empty( $campaign_meta['list_conditions'] ) ) {
						$list_conditions      = array();
						$list_conditions_data = array(
							'field'    => '_lists__in',
							'operator' => 'is',
							'value'    => array(),
						);
						foreach ( $list_ids as $index => $list_id ) {
							$list_conditions_data['value'][] = $list_id;
						}
						$list_conditions[][]              = $list_conditions_data;
						$campaign_meta['list_conditions'] = $list_conditions;
						$data_to_update['meta']           = maybe_serialize( $campaign_meta );
					}
				}

				if ( ! empty( $data_to_update ) ) {
					ES()->campaigns_db->update( $campaign_id, $data_to_update );
				}
			} 
		}
	}
}

/**
 * Update DB version
 *
 * @since 5.1.0
 */
function ig_es_update_510_db_version() {
	ES_Install::update_db_version( '5.1.0' );
}

/* --------------------- ES 5.1.0(End)--------------------------- */

/* --------------------- ES 5.3.8(Start)--------------------------- */


function ig_es_mark_system_workflows() {
	$workflows = ES()->workflows_db->get_workflows();
	if ( ! empty( $workflows ) ) {

		$system_triggers = array(
			'ig_es_user_subscribed',
			'ig_es_user_unconfirmed',
			'ig_es_campaign_sent',
		);

		foreach ( $workflows as $workflow ) {
			$workflow_id       = $workflow['id'];
			$trigger_name      = $workflow['trigger_name'];
			$is_system_trigger = in_array( $trigger_name, $system_triggers, true );
			if ( $is_system_trigger ) {
				$data_to_update         = array();
				$data_to_update['type'] = IG_ES_WORKFLOW_TYPE_SYSTEM;
				ES()->workflows_db->update( $workflow_id, $data_to_update );
			}
		}
	}
}

/**
 * Update DB version
 *
 * @since 5.3.8
 */
function ig_es_update_538_db_version() {
	ES_Install::update_db_version( '5.3.8' );
}

/* --------------------- ES 5.3.8(End)--------------------------- */

/* --------------------- ES 5.4.0(Start)--------------------------- */

/**
 * Add reference_site column in contacts table
 *
 * @since 5.4.0
 */
function ig_es_update_540_alter_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'reference_site', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts ADD `reference_site` VARCHAR(255) NULL DEFAULT NULL AFTER `ip_address`"
		);

	}
}

/**
 * Update DB version
 *
 * @since 5.4.0
 */
function ig_es_update_540_db_version() {
	ES_Install::update_db_version( '5.4.0' );
}

/* --------------------- ES 5.4.0(End)--------------------------- */

/* --------------------- ES 5.5.0(Start)--------------------------- */

/**
 * Migrate Existing Workflow trigger conditions to rules section
 *
 * @since 5.5.0
 */
function ig_es_migrate_workflow_trigger_conditions_to_rules() {
	$workflows = ES()->workflows_db->get_workflows();
	if ( ! empty( $workflows ) ) {
		foreach ( $workflows as $workflow ) {
			$workflow_id     = $workflow['id'];
			$trigger_name    = $workflow['trigger_name'];
			$trigger_options = maybe_unserialize( $workflow['trigger_options'] );
			$data_to_update  = array(
				'rules'           => maybe_serialize( array() ),
				'trigger_options' => maybe_serialize( array() ),
			);
			$do_migration    = true;
			$new_rule        = array( array() );

			switch ( $trigger_name ) {
				case 'ig_es_user_registered':
					$rule_value = ! empty( $trigger_options['ig-es-allowed-user-roles'] ) ? $trigger_options['ig-es-allowed-user-roles'] : array();
					if ( ! empty( $rule_value ) ) {
						$new_rule[0][] = array(
							'name'    => 'ig_es_user_role',
							'compare' => 'matches_any',
							'value'   => $rule_value
						);
						$data_to_update['rules'] = maybe_serialize( $new_rule);
					} else {
						$do_migration = false;
					}
					break;
				case 'ig_es_user_unconfirmed':
				case 'ig_es_user_subscribed':
					$rule_value = ! empty( $trigger_options['ig-es-list'] ) ? $trigger_options['ig-es-list'] : array();
					if ( ! empty( $rule_value ) ) {
						$new_rule[0][] = array(
							'name'    => 'ig_es_subscriber_list',
							'compare' => 'matches_any',
							'value'   => $rule_value
						);
						$data_to_update['rules'] = maybe_serialize( $new_rule);
					} else {
						$do_migration = false;
					}
					break;
				default:
					$do_migration = false;
					break;
			}
			if ( $do_migration ) {
				ES()->workflows_db->update( $workflow_id, $data_to_update );
			}
		}
	}
}

/**
 * Update DB version
 *
 * @since 5.5.0
 */
function ig_es_update_550_db_version() {
	ES_Install::update_db_version( '5.5.0' );
}

/* --------------------- ES 5.5.0(End)--------------------------- */

/* --------------------- ES 5.6.3(Start)--------------------------- */

/**
 * Enable newsletter summary automation
 *
 * @since 5.6.3
 */
function ig_es_update_563_enable_newsletter_summary_automation() {
	if ( ! ES()->is_pro() ) {
		do_action( 'ig_es_enable_newsletter_summary_automation' );
	}
}

/**
 * Update DB version
 *
 * @since 5.6.3
 */
function ig_es_update_563_db_version() {
	ES_Install::update_db_version( '5.6.3' );
}

/* --------------------- ES 5.6.3(End)--------------------------- */

/* --------------------- ES 5.6.4(Start)--------------------------- */

/**
 * Add engagement_score column to contacts table
 *
 * @since 5.6.6
 */
function ig_es_add_average_opened_at_to_contacts_table() {
	global $wpdb;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ig_contacts" );

	if ( ! in_array( 'average_opened_at', $cols, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}ig_contacts
			ADD COLUMN `average_opened_at` TIME NULL AFTER `engagement_score`"
		);
	}
}

/**
 * Migrate customer timezone settings to send time optimzer setting
 *
 * @since 5.6.6
 */
function ig_es_migrate_customer_timezone_settings() {
	$ig_es_enable_sending_mails_in_customer_timezone = get_option( 'ig_es_enable_sending_mails_in_customer_timezone', 'no' );
	if ( 'yes' === $ig_es_enable_sending_mails_in_customer_timezone ) {
		update_option( 'ig_es_send_time_optimizer_enabled', 'yes', false );
		update_option( 'ig_es_send_time_optimization_method', 'subscriber_timezone', false );
	}
}

/**
 * Update DB version
 *
 * @since 5.6.6
 */
function ig_es_update_566_db_version() {
	ES_Install::update_db_version( '5.6.6' );
}

/* --------------------- ES 5.6.3(End)--------------------------- */
