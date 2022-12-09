<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_DB_Queue class
 *
 * @since 4.2.1
 */
class ES_DB_Queue extends ES_DB {
	/**
	 * Table name
	 *
	 * @since 4.2.1
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Table DB version
	 *
	 * @since 4.2.1
	 * @var $version
	 */
	public $version;

	/**
	 * Table primary key column name
	 *
	 * @since 4.2.1
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_queue';

		$this->version = '1.0';
	}

	/**
	 * Delete from queue based on campaign_id & contact_id
	 *
	 * @param $campaign_id
	 * @param $contact_id
	 *
	 * @return bool|int
	 *
	 * @since 4.2.1
	 */
	public function delete_from_queue( $campaign_id, $contact_id ) {
		global $wpdb;

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ig_queue WHERE campaign_id = %d AND contact_id = %d",
				$campaign_id,
				$contact_id
			)
		);
	}

}
