<?php
/**
 * Guest Queue DB
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
 * IG_ES_DB_WC_Guest class
 *
 * @since 4.6.5
 */
class IG_ES_DB_WC_Guest extends ES_DB {

	/**
	 * Guest queue table name
	 *
	 * @since 4.6.5
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Guest queue table version
	 *
	 * @since 4.6.5
	 * @var $version
	 */
	public $version;

	/**
	 * Guest queue table primary key
	 *
	 * @since 4.6.5
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * IG_ES_DB_WC_Guest constructor.
	 *
	 * @since 4.6.5
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name  = $wpdb->prefix . 'ig_wc_guests';
		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Returns Guest queue table's columns
	 *
	 * @since 4.6.5
	 *
	 * @return array Guest queue table columns
	 */
	public function get_columns() {
		return array(
			'id'                => '%d',
			'email'             => '%s',
			'tracking_key'      => '%s',
			'created'           => '%s',
			'last_active'       => '%s',
			'language'          => '%s',
			'most_recent_order' => '%d',
			'version'           => '%d',
			'meta'              => '%s',
		);
	}

	/**
	 * Returns default values for Guest columns
	 *
	 * @since 4.6.5
	 *
	 * @return array default values for Guest columns
	 */
	public function get_column_defaults() {
		return array(
			'id'                => 0,
			'email'             => '',
			'tracking_key'      => '',
			'created'           => '',
			'last_active'       => '',
			'language'          => '',
			'most_recent_order' => 0,
			'version'           => 0,
			'meta'              => '',
		);
	}

	/**
	 * Get Guests based on arguements
	 *
	 * @param  array   $query_args    Query arguements.
	 * @param  string  $output        Output format.
	 * @param  boolean $do_count_only Count only flag.
	 *
	 * @return mixed $result Query result
	 *
	 * @since 4.6.5
	 */
	public function get_carts( $query_args = array(), $output = ARRAY_A, $do_count_only = false ) {

		global $wpdb, $wpbd;
		if ( $do_count_only ) {
			$sql = 'SELECT count(*) as total FROM ' . $wpdb->prefix . 'ig_wc_cart';
		} else {
			$sql = 'SELECT ';
			if ( ! empty( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
				$sql .= implode( ' ,', $query_args['fields'] );
			} else {
				$sql .= '*';
			}

			$sql .= ' FROM ' . $wpdb->prefix . 'ig_wc_cart';
		}

		$args  = array();
		$query = array();

		if ( ! empty( $query_args['ids'] ) ) {
			$ids_count        = count( $query_args['ids'] );
			$ids_placeholders = array_fill( 0, $ids_count, '%d' );
			$query[]          = ' id IN( ' . implode( ',', $ids_placeholders ) . ' )';
			$args             = array_merge( $args, $query_args['ids'] );
		}

		if ( isset( $query_args['status'] ) ) {
			$query[] = ' status = %s ';
			$args[]  = $query_args['status'];
		}

		if ( isset( $query_args['last_modified'] ) ) {
			$query[] = ' last_modified <= %s ';
			$args[]  = $query_args['last_modified'];
		}

		$query = apply_filters( 'ig_es_wc_cart_where_caluse', $query );

		if ( count( $query ) > 0 ) {
			$sql .= ' WHERE ';

			$sql .= implode( ' AND ', $query );

			if ( count( $args ) > 0 ) {
				$sql = $wpbd->prepare( $sql, $args ); // phpcs:ignore
			}
		}

		if ( ! $do_count_only ) {

			$order                 = ! empty( $query_args['order'] ) ? strtolower( $query_args['order'] ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values, true ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created' );

			$expected_order_by_values = array( 'created' );
			if ( empty( $query_args['order_by'] ) || ! in_array( $query_args['order_by'], $expected_order_by_values, true ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $query_args['order_by'] );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;

			if ( ! empty( $query_args['per_page'] ) ) {
				$sql .= ' LIMIT ' . $query_args['per_page'];
				if ( ! empty( $query_args['page_number'] ) ) {
					$sql .= ' OFFSET ' . ( $query_args['page_number'] - 1 ) * $query_args['per_page'];
				}
			}

			$result = $wpbd->get_results( $sql, $output ); // phpcs:ignore
		} else {
			$result = $wpbd->get_var( $sql ); // phpcs:ignore
		}

		return $result;

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
