<?php
/**
 * Workflow Queue DB
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_DB_Workflows_Queue class
 *
 * @since 4.4.1
 */
class ES_DB_Workflows_Queue extends ES_DB {

	/**
	 * Workflow queue table name
	 *
	 * @since 4.4.1
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Workflow queue table version
	 *
	 * @since 4.4.1
	 * @var $version
	 */
	public $version;

	/**
	 * Workflow queue table primary key
	 *
	 * @since 4.4.1
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Workflows_Queue constructor.
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name  = $wpdb->prefix . 'ig_workflows_queue';
		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Returns workflow queue table's columns
	 *
	 * @since 4.4.1
	 *
	 * @return array workflow queue table columns
	 */
	public function get_columns() {
		return array(
			'id'           => '%d',
			'workflow_id'  => '%d',
			'scheduled_at' => '%s',
			'created_at'   => '%s',
			'meta'         => '%s',
			'failed'       => '%d',
			'failure_code' => '%d',
		);
	}

	/**
	 * Returns default values for workflow columns
	 *
	 * @since 4.4.1
	 *
	 * @return array default values for workflow columns
	 */
	public function get_column_defaults() {
		return array(
			'workflow_id'  => 0,
			'scheduled_at' => '',
			'created_at'   => '',
			'meta'         => '',
			'failed'       => 0,
			'failure_code' => 0,
		);
	}

	/**
	 * Update meta value
	 *
	 * @since 4.4.1
	 *
	 * @param int   $queue_id Queue ID.
	 * @param array $meta_data Meta data to update.
	 *
	 * @return bool|false|int
	 */
	public function update_meta( $queue_id = 0, $meta_data = array() ) {

		$update = false;
		if ( ! empty( $queue_id ) && ! empty( $meta_data ) ) {
			$queue = $this->get( $queue_id );

			if ( ! empty( $queue ) ) {

				if ( isset( $queue['meta'] ) ) {
					$meta = maybe_unserialize( $queue['meta'] );

					// If $meta is an empty or isn't an array, then convert it to an array before adding data to it.
					if ( empty( $meta ) || ! is_array( $meta ) ) {
						$meta = array();
					}

					foreach ( $meta_data as $meta_key => $meta_value ) {
						$meta[ $meta_key ] = $meta_value;
					}

					$queue['meta'] = maybe_serialize( $meta );

					$update = $this->update( $queue_id, $queue );

				}
			}
		}

		return $update;

	}
}
