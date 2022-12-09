<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to load required data types for workflows
 * 
 * @class ES_Data_Types
 * @since 4.4.1
 */
class ES_Workflow_Data_Types extends ES_Workflow_Registry {

	/**
	 * Registered include classes
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public static $includes;

	/**
	 * Loaded registered class objects
	 *
	 * @var array
	 */
	public static $loaded = array();

	/**
	 *
	 * Get registered data type
	 *
	 * @since 4.4.1
	 * @return array
	 */
	public static function load_includes() {
		return apply_filters(
			'ig_es_data_types_includes',
			array(
				'user'       => 'ES_Data_Type_User',
				'subscriber' => 'ES_Data_Type_Subscriber',
				'campaign'   => 'ES_Data_Type_Campaign',
			)
		);
	}

	/**
	 * Get data item from data type
	 * 
	 * @param $data_type_id
	 * @return Data_Type|false
	 */
	public static function get( $data_type_id ) {
		return parent::get( $data_type_id );
	}

	/**
	 * Set data type in data item
	 * 
	 * @param string    $data_type_id
	 * @param Data_Type $data_type
	 */
	public static function after_loaded( $data_type_id, $data_type ) {
		$data_type->set_id( $data_type_id );
	}

	/**
	 * Get non supported data types
	 * 
	 * @return array
	 */
	public static function get_non_stored_data_types() {
		return array( 'shop', 'coupon' );
	}
}
