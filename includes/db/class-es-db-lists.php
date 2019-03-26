<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Lists {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	public static function get_lists( $status = 'all' ) {
		global $wpdb;


		$query = "SELECT * FROM " . IG_LISTS_TABLE . " WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' ";
		// if ( $status === 'enable' || $status === 'disable' ) {
		// 	$query .= $wpdb->prepare(" WHERE status = %s", $status);
		// }

		$lists = $wpdb->get_results( $query, ARRAY_A );

		return $lists;
	}


	public static function get_list_id_name_map( $list_id = '', $flip = false ) {
		global $wpdb;

		$lists_map = array();

		$sSql = "SELECT id, name FROM " . IG_LISTS_TABLE . " WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' ";
		$res  = $wpdb->get_results( $sSql, ARRAY_A );
		foreach ( $res as $list ) {
			$lists_map[ $list['id'] ] = $list['name'];
		}

		if ( ! empty( $list_id ) ) {
			$list_name = ! empty( $lists_map[ $list_id ] ) ? $lists_map[ $list_id ] : '';

			return $list_name;
		}

		if ( $flip ) {
			$lists_map = array_flip( $lists_map );
		}

		return $lists_map;
	}

	public static function get_list_by_name( $name ) {
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
	}

	public static function get_all_lists_name_by_contact( $id ) {
		global $wpdb;

		$sSql = $wpdb->prepare( "SELECT name FROM " . IG_LISTS_TABLE . " where id IN ( SELECT list_id from " . IG_LISTS_CONTACTS_TABLE . " where contact_id = %d ) AND ( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' ) ", $id );
		$res  = $wpdb->get_col( $sSql );

		return $res;
	}

	public static function add_lists( $lists ) {
		global $wpdb;

		if ( is_string( $lists ) ) {
			$lists = array( $lists );
		}

		$query          = "SELECT LOWER(name) FROM " . IG_LISTS_TABLE;
		$existing_lists = $wpdb->get_col( $query );
		foreach ( $lists as $key => $list ) {
			// Insert only if list is not exists.
			$lower_list = strtolower( $list );
			if ( ! in_array( $lower_list, $existing_lists ) ) {
				$sql   = "INSERT INTO " . IG_LISTS_TABLE . " (`slug`, `name`, `created_at`) VALUES (%s, %s, %s)";
				$query = $wpdb->prepare( $sql, sanitize_title( $list ), $list, ig_get_current_date_time() );
				$wpdb->query( $query );
				$existing_lists[] = $list;
			}
		}
	}

	public static function add_list( $list ) {
		global $wpdb;

		$query          = "SELECT LOWER(name) FROM " . IG_LISTS_TABLE;
		$existing_lists = $wpdb->get_col( $query );
		$lower_list     = strtolower( $list );

		if ( ! in_array( $lower_list, $existing_lists ) ) {
			$data               = array();
			$data['slug']       = sanitize_title( $list );
			$data['name']       = $list;
			$data['created_at'] = ig_get_current_date_time();
			$insert             = $wpdb->insert( IG_LISTS_TABLE, $data );

			if ( $insert ) {
				return $wpdb->insert_id;
			}

		}

		return 0;

	}


}
