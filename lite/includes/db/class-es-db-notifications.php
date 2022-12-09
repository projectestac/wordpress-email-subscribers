<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Notifications {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	/**
	 * Migrate Post Notification Email Template Type
	 *
	 * @return bool|int
	 *
	 * @since 4.0.0
	 */
	public static function migrate_post_notification_es_template_type() {
		global $wpdb;

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}postmeta SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
				array( 'post_notification', 'es_template_type', 'Post Notification' )
			)
		);

		return $update;
	}

	/**
	 * Migrate Newsletter Email template type
	 *
	 * @return bool|int
	 *
	 * @since 4.0.0
	 */
	public static function migrate_newsletter_es_template_type() {
		global $wpdb;

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}postmeta SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
				array( 'newsletter', 'es_template_type', 'Newsletter' )
			)
		);

		return $update;
	}

}
