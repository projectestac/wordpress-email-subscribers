<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IG_ES_DB_Unsubscribe_Feedback extends ES_DB {
	/**
	 * Table Name
	 *
	 * @since 4.6.8
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Version
	 *
	 * @since 4.6.8
	 * @var $version
	 */
	public $version;

	/**
	 * Primary Key
	 *
	 * @since 4.6.8
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * IG_ES_DB_Unsubscribe_Feedback constructor.
	 *
	 * @since 4.6.8
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_unsubscribe_feedback';

		$this->primary_key = 'id';

		$this->version = '1.0';

	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.6.8
	 */
	public function get_columns() {
		return array(
			'id'               => '%d',
			'contact_id'       => '%d',
			'list_id'          => '%d',
			'campaign_id'      => '%d',
			'mailing_queue_id' => '%d',
			'feedback_slug'    => '%s',
			'feedback_text'    => '%s',
			'created_at'       => '%s',
			'updated_at'       => '%s',
			'meta'             => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @return array Default column values
	 *
	 * @since  4.6.8
	 */
	public function get_column_defaults() {
		return array(
			'contact_id'       => 0,
			'list_id'          => 0,
			'campaign_id'      => 0,
			'mailing_queue_id' => 0,
			'feedback_slug'    => '',
			'feedback_text'    => '',
			'created_at'       => ig_get_current_date_time(),
			'updated_at'       => ig_get_current_date_time(),
			'meta'             => '',
		);
	}

	/**
	 * Add feedback into database
	 *
	 * @param array $feedback_data Feedback data.
	 *
	 * @return int
	 */
	public function insert_feedback( $feedback_data = array() ) {

		if ( empty( $feedback_data ) || ! is_array( $feedback_data ) ) {
			return 0;
		}

		return $this->insert( $feedback_data );
	}

	/**
	 * Update feedback
	 *
	 * @param int   $feedback_id   Feedback ID.
	 * @param array $feedback_data Feedback data.
	 *
	 * @return bool|void
	 */
	public function update_feedback( $feedback_id = 0, $feedback_data = array() ) {

		if ( empty( $feedback_id ) || empty( $feedback_data ) || ! is_array( $feedback_data ) ) {
			return;
		}

		return $this->update( $feedback_id, $feedback_data );
	}

	/**
	 * Get existing feedback for given contact and list id
	 *
	 * @param int $contact_id Contact id
	 * @param int $list_id List id
	 *
	 * @return int $existing_feedback_id Existing feedback ID
	 */
	public function get_existing_feedback_id( $contact_id = 0, $list_id = 0 ) {

		global $wpdb;

		$existing_feedback_id = 0;
		if ( empty( $contact_id ) || empty( $list_id ) ) {
			return $existing_feedback_id;
		}

		$existing_feedback_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}ig_unsubscribe_feedback WHERE `contact_id`= %d AND `list_id` = %d ORDER BY updated_at DESC LIMIT 1",
				$contact_id,
				$list_id
			)
		);

		return $existing_feedback_id;
	}


	/**
	 * Get feedback count for given number of days
	 *
	 * @param int $number_of_days
	 *
	 * @return int $feedback_counts
	 */
	public static function get_feedback_counts( $number_of_days ) {
		global $wpdb;
		$feedback_counts = $wpdb->get_results(
			$wpdb->prepare(
			"SELECT feedback_slug, COUNT(feedback_slug) AS feedback_count FROM `{$wpdb->prefix}ig_unsubscribe_feedback` WHERE `updated_at` >= DATE_SUB(NOW(), INTERVAL %d DAY) GROUP BY `feedback_slug` ORDER BY feedback_count DESC"
			, $number_of_days
			),
			ARRAY_A
		);

		return $feedback_counts;
	}
}
