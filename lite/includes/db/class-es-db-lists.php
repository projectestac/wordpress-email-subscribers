<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Lists extends ES_DB {
	
	/**
	 * Table name
	 * 
	 * @since 4.0.0
	 * @var $table_name
	 *
	 */
	public $table_name;
	
	/**
	 * Table DB version
	 * 
	 * @since 4.0.0
	 * @var $version
	 *
	 */
	public $version;
	
	/**
	 * Table primary key column name
	 * 
	 * @since 4.0.0
	 * @var $primary_key
	 *
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_lists';

		$this->primary_key = 'id';

		$this->version = '1.0';


	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 */
	public function get_columns() {
		return array(
			'id'         => '%d',
			'slug'       => '%s',
			'name'       => '%s',
			'hash'       => '%s',
			'created_at' => '%s',
			'updated_at' => '%s',
			'deleted_at' => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.2.1
	 */
	public function get_column_defaults() {
		return array(
			'slug'       => null,
			'name'       => null,
			'hash'       => null,
			'created_at' => ig_get_current_date_time(),
			'updated_at' => null,
			'deleted_at' => null,
		);
	}

	/**
	 * Get Lists
	 *
	 * @return array|object|null
	 *
	 * @since 4.0.0
	 */
	public function get_lists() {
		return $this->get_all();
	}

	/**
	 * Get list id name map
	 *
	 * @param string $list_id
	 * @param bool $flip
	 *
	 * @return array|mixed|string
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function get_list_id_name_map( $list_id = '', $flip = false ) {

		$lists_map = array();

		$lists = $this->get_lists();

		if ( count( $lists ) > 0 ) {

			foreach ( $lists as $list ) {
				$lists_map[ $list['id'] ] = $list['name'];
			}

			if ( ! empty( $list_id ) ) {
				$list_name = ! empty( $lists_map[ $list_id ] ) ? $lists_map[ $list_id ] : '';

				return $list_name;
			}

			if ( $flip ) {
				$lists_map = array_flip( $lists_map );
			}
		}

		return $lists_map;
	}

	/**
	 * Get list by name
	 *
	 * @param $name
	 *
	 * @return array|mixed
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function get_list_by_name( $name ) {
		$list = $this->get_by( 'name', $name );
		if ( is_null( $list ) ) {
			$list = array();
		}

		return $list;

		/* TODO: Keep for sometime. Remove it after complete verification/ testing
		global $wpdb;

		$lists = array();
		if ( ! empty( $name ) ) {

			$query = "SELECT * FROM " . IG_LISTS_TABLE . " WHERE `name` = %s LIMIT 0, 1";
			$sql   = $wpdb->prepare( $query, $name );
			$lists = $wpdb->get_results( $sql, ARRAY_A );
		}

		$list = array();
		if ( count( $lists ) > 0 ) {
			$list = array_shift( $lists );
		}

		return $list;
		*/
	}

	/**
	 * Get list by slug
	 *
	 * @param string $slug List slug.
	 *
	 * @return bool/array $list Returns list array if list exists else false.
	 *
	 * @since 4.4.3
	 */
	public function get_list_by_slug( $slug ) {
		$list = $this->get_by( 'slug', $slug );

		if ( is_null( $list ) ) {
			return false;
		}

		return $list;
	}

	/**
	 * Get all lists name by contact_id
	 *
	 * @param $id
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.0
	 */
	public function get_all_lists_name_by_contact( $id ) {
		global $wpdb;

		$res  = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `name` FROM {$wpdb->prefix}ig_lists WHERE id IN ( SELECT list_id FROM {$wpdb->prefix}ig_lists_contacts WHERE contact_id = %d )",
				$id
			)			
		);

		return $res;
	}

	/**
	 * Add lists
	 *
	 * @param $lists
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function add_lists( $lists ) {

		if ( ! is_array( $lists ) ) {
			$lists = array( $lists );
		}

		if ( count( $lists ) > 0 ) {
			foreach ( $lists as $key => $list ) {
				$this->add_list( $list );
			}
		}

		/**
		 * $query          = "SELECT LOWER(name) FROM " . IG_LISTS_TABLE;
		 * $existing_lists = $wpdb->get_col( $query );
		 * foreach ( $lists as $key => $list ) {
		 * // Insert only if list is not exists.
		 * $lower_list = strtolower( $list );
		 * if ( ! in_array( $lower_list, $existing_lists ) ) {
		 * $sql   = "INSERT INTO " . IG_LISTS_TABLE . " (`slug`, `name`, `created_at`) VALUES (%s, %s, %s)";
		 * $query = $wpdb->prepare( $sql, sanitize_title( $list ), $list, ig_get_current_date_time() );
		 * $wpdb->query( $query );
		 * $existing_lists[] = $list;
		 * }
		 * }
		 */
	}

	/**
	 * Add List into database
	 *
	 * @param string $list List name.
	 *
	 * @param string $slug List slug.
	 *
	 * @return int
	 *
	 * @since 4.0.0
	 * 
	 * @modified 4.4.3 Added $slug parameter.
	 */
	public function add_list( $list = '', $slug = '' ) {

		if ( empty( $list ) || ! is_scalar( $list ) ) {
			return 0;
		}

		$lower_list = strtolower( $list );

		$is_list_exists = $this->is_list_exists( $lower_list );

		if ( $is_list_exists ) {
			return 0;
		}

		$data = array(
			'slug' => ! empty( $slug ) ? $slug : sanitize_title( $list ),
			'name' => $list,
			'hash' => ES_Common::generate_hash( 12 ),
		);

		return $this->insert( $data );

		/*
		$list_table = IG_LISTS_TABLE;

		$query          = "SELECT LOWER(name) FROM {$list_table}";
		$existing_lists = $wpdb->get_col( $query );

		$lower_list = strtolower( $list );

		if ( ! in_array( $lower_list, $existing_lists ) ) {
			$data               = array();
			$data['slug']       = sanitize_title( $list );
			$data['name']       = $list;
			$data['created_at'] = ig_get_current_date_time();

			$insert = $wpdb->insert( $list_table, $data );

			if ( $insert ) {
				return $wpdb->insert_id;
			}

		}

		return 0;
		*/

	}

	/**
	 * Update List
	 *
	 * @param int $row_id
	 * @param array $data
	 *
	 * @return bool|void
	 *
	 * @since 4.2.1
	 */
	public function update_list( $row_id = 0, $name ) {

		if ( empty( $row_id ) ) {
			return;
		}

		$data = array(
			'name'       => $name,
			'updated_at' => ig_get_current_date_time()
		);

		return $this->update( $row_id, $data );
	}

	/**
	 * Check if list is already exists
	 *
	 * @param $name
	 *
	 * @return bool
	 *
	 * @since 4.2.1
	 */
	public function is_list_exists( $name ) {
		$col = $this->get_by( 'name', $name );

		if ( is_null( $col ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get total count of lists
	 *
	 * @return string|null
	 *
	 * @since 4.2.0
	 */
	public function count_lists() {
		return $this->count();
	}

	/**
	 * Get List Name By Id
	 *
	 * @param $id
	 *
	 * @return string|null
	 *
	 * @since 4.2.0
	 */
	public function get_list_name_by_id( $id ) {
		return $this->get_column_by( 'name', 'id', $id );
	}

	/**
	 * Delete lists
	 *
	 * @param $ids
	 *
	 * @since 4.2.1
	 */
	public function delete_lists( $ids ) {

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				$this->delete( absint($id) );

				/**
				 * Take necessary cleanup steps using this hook
				 *
				 * @since 4.3.1
				 */
				do_action( 'ig_es_list_deleted', $id );
			}
		}

	}


}
