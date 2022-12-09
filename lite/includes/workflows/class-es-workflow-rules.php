<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to load workflow rules
 *
 * @class ES_Workflow_Rules
 * @since 5.5.0
 */
class ES_Workflow_Rules extends ES_Workflow_Registry {

	/**
	 * Registered include classes
	 *
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
	 * Implement this method in sub classes
	 *
	 * @return array
	 */
	public static function load_includes() {

		$includes = array();

		return apply_filters( 'ig_es_workflow_rules', $includes );
	}


	/**
	 * Get object of specific rule class.
	 *
	 * @param $name string
	 *
	 * @return ES_Workflow_Rule|false
	 *
	 */
	public static function get( $name ) {
		static::load( $name );

		if ( ! isset( static::$loaded[ $name ] ) ) {
			return false;
		}

		return static::$loaded[ $name ];
	}

	/**
	 * Load rule class object by rule name
	 *
	 * @param $name
	 */
	public static function load( $name ) {
		if ( static::is_loaded( $name ) ) {
			return;
		}

		$rule   = false;
		$includes = static::get_includes();

		if ( ! empty( $includes[ $name ] ) ) {
			/**
			 * Registered include classes
			 *
			 * @var ES_Workflow_Rule $rule
			 */
			$rule_class = $includes[ $name ];
			if ( class_exists( $rule_class ) ) {
				$rule = new $rule_class();
			}
		}

		static::$loaded[ $name ] = $rule;
	}

	/**
	 * Get all registered workflow rules
	 *
	 * @return ES_Workflow_Rule[]
	 *
	 */
	public static function get_all() {
		foreach ( static::get_includes() as $name => $path ) {
			static::load( $name );
		}

		return static::$loaded;
	}

}
