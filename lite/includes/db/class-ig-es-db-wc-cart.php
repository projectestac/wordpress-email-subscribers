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
 * IG_ES_DB_WC_Cart class
 *
 * @since 4.6.5
 */
class IG_ES_DB_WC_Cart extends ES_DB {

	/**
	 * Workflow queue table name
	 *
	 * @since 4.6.5
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Workflow queue table version
	 *
	 * @since 4.6.5
	 * @var $version
	 */
	public $version;

	/**
	 * Workflow queue table primary key
	 *
	 * @since 4.6.5
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * IG_ES_DB_WC_Cart constructor.
	 *
	 * @since 4.6.5
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name  = $wpdb->prefix . 'ig_wc_cart';
		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Returns workflow queue table's columns
	 *
	 * @since 4.6.5
	 *
	 * @return array workflow queue table columns
	 */
	public function get_columns() {
		return array(
			'id'                 => '%d',
			'status'             => '%s',
			'user_id'            => '%d',
			'guest_id'           => '%d',
			'last_modified'      => '%s',
			'created'            => '%s',
			'items'              => '%s',
			'coupons'            => '%s',
			'fees'               => '%s',
			'shipping_tax_total' => '%f',
			'shipping_total'     => '%f',
			'total'              => '%f',
			'token'              => '%s',
			'currency'           => '%s',
		);
	}

	/**
	 * Returns default values for workflow columns
	 *
	 * @since 4.6.5
	 *
	 * @return array default values for workflow columns
	 */
	public function get_column_defaults() {
		return array(
			'id'                 => 0,
			'status'             => '',
			'user_id'            => 0,
			'guest_id'           => 0,
			'last_modified'      => '',
			'created'            => '',
			'items'              => '',
			'coupons'            => '',
			'fees'               => '',
			'shipping_tax_total' => 0,
			'shipping_total'     => 0,
			'total'              => 0,
			'token'              => '',
			'currency'           => '',
		);
	}

	/**
	 * Get workflows based on arguements
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
}
