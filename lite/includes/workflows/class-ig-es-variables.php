<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to include registered workflow variables
 * 
 * @class IG_ES_Variables
 */
class IG_ES_Variables {

	/** 
	 * List of already loaded variables in variable name => variable class object format
	 * This helps in removing the need of recreating the class object 
	 * 
	 * @var array
	 */
	private static $loaded_variables = array();

	/**
	 * List of registered workflow variables in [variable_data_type][variable_field] => path to variable file format
	 * 
	 * @var array
	 */
	private static $variables_list;

	/**
	 * List of registered workflow variables in [variable_data_type] => [variable_field] format
	 * 
	 * @var array
	 */
	private static $included_variables = array();


	/**
	 * Get registered workflow variables list in [variable_data_type][variable_field] => path to variable file format
	 * 
	 * @return array
	 */
	public static function get_list() {
		// cache the list after first generation
		if ( isset( self::$variables_list ) ) {
			return self::$variables_list;
		}

		$variables = array();
		$included_variables = self::$included_variables;

		if ( ! empty( $included_variables ) ) {
			// generate paths to included variables
			foreach ( $included_variables as $data_type => $fields ) {
				foreach ( $fields as $field ) {
					$filename = str_replace( '_', '-', $data_type ) . '-' . str_replace( '_', '-', $field ) . '.php';
					$variables[$data_type][$field] = ES_PLUGIN_DIR . 'lite/includes/workflows/variables/' . $filename;
				}
			}
		}

		self::$variables_list = apply_filters( 'ig_es_workflow_variables', $variables );
		return self::$variables_list;
	}


	/**
	 * Get path to file which handles variable
	 * 
	 * @param $data_type
	 * @param $data_field
	 * @return false|string
	 */
	public static function get_path_to_variable( $data_type, $data_field ) {

		$list = self::get_list();

		if ( isset( $list[$data_type][$data_field] ) ) {
			return $list[$data_type][$data_field];
		}

		return false;
	}


	/**
	 * Get variable object based on variable name
	 * e.g. returns IG_ES_Variable_WC_Order_ID class object if variable name is wc_order.id
	 * 
	 * @param $variable_name string
	 * @return IG_ES_Variable|false
	 */
	public static function get_variable( $variable_name ) {

		if ( isset( self::$loaded_variables[$variable_name] ) ) {
			return self::$loaded_variables[$variable_name];
		}

		list( $data_type, $data_field ) = explode( '.', $variable_name );

		$path = self::get_path_to_variable( $data_type, $data_field );

		if ( ! file_exists( $path ) ) {

			if ( ! file_exists( $path ) ) {
				return false;
			}
		}

		/** 
		 * Variable class object
		 *
		 * @var IG_ES_Variable $variable_object
		 */
		$variable_object = require_once $path;

		if ( ! $variable_object ) {
			return false;
		}

		$variable_object->setup( $variable_name );

		self::$loaded_variables[$variable_name] = $variable_object;

		return $variable_object;
	}
}
