<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_DB_Custom_Fields class
 *
 *@since 4.8.4
 */
class ES_DB_Custom_Fields extends ES_DB {
	
	/**
	 * Table name
	 * 
	 * @since 4.8.4
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table DB version
	 * 
	 * @since 4.8.4
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Table primary key column name
	 * 
	 * @since 4.8.4
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * ES_DB_Custom_Fields constructor.
	 *
	 * @since 4.8.4
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_custom_fields';

		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.8.4
	 */
	public function get_columns() {
		return array(
			'id'         => '%d',
			'slug'       => '%s',
			'label'      => '%s',
			'type'   	 => '%s',
			'meta'    	 => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since 4.8.4
	 */
	public function get_column_defaults() {
		return array(
			'id'         => null,
			'slug'       => null,
			'label'      => null,
			'type'   	 => null,
			'meta'    	 => null,
		);
	}

	/**
	 * Get all custom fields
	 *
	 * @return array|object|null
	 *
	 * @since 4.8.4
	 */
	public function get_custom_fields() {
		return $this->get_all();
	}

	public function get_custom_date_fields() {
		$where_condition = "type = 'date'";
		return ES()->custom_fields_db->get_by_conditions( $where_condition );
	}

	/**
	 * Add Custom Field
	 *
	 * @param $data
	 *
	 * @return int
	 *
	 * @since 4.8.4
	 */
	public function add_custom_field( $data ) {
		return $this->insert( $data );
	}

	/**
	 * Update Custom Field
	 *
	 * @param int $row_id
	 * @param array $data
	 *
	 * @return bool|void
	 *
	 * @since 4.8.4
	 */
	public function update_custom_field( $row_id, $data = array() ) {

		if ( empty( $row_id ) ) {
			return;
		}

		$data = array(
			'label'  => $data['label'],
			'meta'   => $data['meta'],
		);

		return $this->update( $row_id, $data );
	}

	/**
	 * Get Custom field By ID
	 *
	 * @param $id
	 *
	 * @return array|mixed
	 *
	 * @since 4.8.4
	 */
	public function get_custom_field_by_id( $id ) {

		if ( empty( $id ) ) {
			return array();
		}

		return $this->get( $id );
	}

	/**
	 * Get Custom field By Slug
	 *
	 * @param $slug_name
	 *
	 * @return array|mixed
	 *
	 * @since 4.8.4
	 */
	public function get_custom_field_meta_by_slug( $slug ) {

		if ( empty( $slug ) ) {
			return array();
		}

		$meta = $this->get_column_by( 'meta', 'slug', $slug );
		if ( $meta ) {
			$meta = maybe_unserialize( $meta );
		}

		return $meta;
	}

	/**
	 * Check if custom field already exists
	 *
	 * @param $name
	 *
	 * @return bool
	 *
	 * @since 4.8.4
	 */
	public function is_custom_field_exists( $slug ) {
		$col = $this->get_by( 'slug', $slug );
		if ( is_null( $col ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete Custom fields
	 *
	 * @param $ids
	 *
	 * @since 4.8.4
	 */
	public function delete_custom_fields( $ids ) {

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}
		
		$field_deleted = 0;
		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				$field_deleted = $this->delete( absint( $id ) );

				/**
				 * Take necessary cleanup steps using this hook
				 *
				 * @since 4.8.4
				 */
				do_action( 'ig_es_custom_field_deleted', $id );
			}
			return $field_deleted;
		}
		return false;

	}

	/**
	 * Delete Custom fields
	 *
	 * @param $ids
	 *
	 * @since 4.8.4
	 */
	public function get_custom_field_slug_list_by_ids( $ids ) {

		global $wpbd;
		if ( ! is_array( $ids ) && ! count( $ids ) > 0 ) {
			return array();
		}

		$ids_str = implode( ',', $ids );
		$result = $wpbd->get_col( "SELECT slug FROM {$wpbd->prefix}ig_custom_fields WHERE id IN ({$ids_str})" );
		return $result;
	}
}
