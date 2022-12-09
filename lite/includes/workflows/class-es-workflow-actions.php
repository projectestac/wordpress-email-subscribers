<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to load workflow actions
 *
 * @class ES_Workflow_Actions
 * @since 4.4.1
 */
class ES_Workflow_Actions extends ES_Workflow_Registry {

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
	 * Implement this method in sub classes
	 *
	 * @since 4.4.1
	 * @return array
	 */
	public static function load_includes() {

		$includes = array(
			'ig_es_add_to_list'    				=> 'ES_Action_Add_To_List',
			'ig_es_delete_contact' 				=> 'ES_Action_Delete_Contact',
			'ig_es_update_contact' 				=> 'ES_Action_Update_Contact',
			'ig_es_send_email' 	   				=> 'ES_Action_Send_Email',
			'ig_es_update_contact_custom_field' => 'ES_Action_Update_Contact_Custom_Field',
		);

		return apply_filters( 'ig_es_workflow_actions', $includes );
	}


	/**
	 * Get object of specific action class.
	 *
	 * @param $action_name string
	 *
	 * @return ES_Workflow_Action|false
	 *
	 * @since 4.4.1
	 */
	public static function get( $action_name ) {
		static::load( $action_name );

		if ( ! isset( static::$loaded[ $action_name ] ) ) {
			return false;
		}

		return static::$loaded[ $action_name ];
	}


	/**
	 * Get all registered workflow actions
	 *
	 * @return ES_Workflow_Action[]
	 *
	 * @since 4.4.1
	 */
	public static function get_all() {
		foreach ( static::get_includes() as $name => $path ) {
			static::load( $name );
		}

		return static::$loaded;
	}


	/**
	 * Load action class object by action name
	 *
	 * @param $action_name
	 *
	 * @since 4.4.1
	 */
	public static function load( $action_name ) {
		if ( static::is_loaded( $action_name ) ) {
			return;
		}

		$action   = false;
		$includes = static::get_includes();

		if ( ! empty( $includes[ $action_name ] ) ) {

			/**
			* Registered include classes
			*
			* @since 4.4.1
			* @var ES_Workflow_Action $action
			*/
			$action_class = $includes[ $action_name ];
			if ( class_exists( $action_class ) ) {
				$action = new $action_class();
				$action->set_name( $action_name );
			}
		}

		static::$loaded[ $action_name ] = $action;
	}

}
