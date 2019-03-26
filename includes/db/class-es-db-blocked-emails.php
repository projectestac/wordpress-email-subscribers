<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Blocked_Emails {


	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	/**
	 * Get columns and formats
	 *
	 * @since   2.1
	 */
	public static function get_columns() {
		return array(
			'id'         => '%d',
			'email'      => '%s',
			'ip'         => '%s',
			'created_on' => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   2.1
	 */
	public static function get_column_defaults() {
		return array(
			'email'      => null,
			'ip'         => null,
			'created_on' => ig_get_current_date_time(),
		);
	}


	public static function insert( $data ) {
		global $wpdb;

		$column_formats  = self::get_columns();
		$column_defaults = self::get_column_defaults();
		$insert          = true;
		$prepared_data   = ES_DB::prepare_data( $data, $column_formats, $column_defaults, $insert );

		$campaigns_data = $prepared_data['data'];
		$column_formats = $prepared_data['column_formats'];

		if ( $insert ) {
			$result = $wpdb->insert( IG_BLOCKED_EMAILS_TABLE, $campaigns_data, $column_formats );
			if ( $result ) {
				return $wpdb->insert_id;
			}
		}

		return $result;
	}


}
