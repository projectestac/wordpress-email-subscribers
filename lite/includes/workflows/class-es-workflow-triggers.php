<?php
/**
 * Includes all workflows triggers.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to include registered workflow triggers
 *
 * @class ES_Workflow_Triggers
 *
 * @since 4.4.1
 */
class ES_Workflow_Triggers extends ES_Workflow_Registry {

	/**
	 * Registered workflow trigger list
	 *
	 * @var array
	 */
	public static $includes;

	/**
	 * Loaded workflow triggers
	 *
	 * @var array
	 */
	public static $loaded = array();

	/**
	 * Get workflow trigger list.
	 *
	 * @return array
	 */
	public static function load_includes() {

		$includes = array(
			'ig_es_user_registered'   => 'ES_Trigger_User_Registered',
			'ig_es_user_deleted'      => 'ES_Trigger_User_Deleted',
			'ig_es_user_updated'      => 'ES_Trigger_User_Updated',
			'ig_es_user_subscribed'   => 'ES_Trigger_User_Subscribed',
			'ig_es_user_unconfirmed'  => 'ES_Trigger_User_Unconfirmed',
			'ig_es_user_unsubscribed' => 'ES_Trigger_User_Unsubscribed',
			'ig_es_campaign_sent'     => 'ES_Trigger_Campaign_Sent',
			'ig_es_campaign_failed'   => 'ES_Trigger_Campaign_Failed',
		);

		return apply_filters( 'ig_es_workflow_triggers', $includes );
	}


	/**
	 * Get trigger object based on its name
	 *
	 * @param string $trigger_name Trigger name.
	 *
	 * @return ES_Workflow_Trigger|false
	 */
	public static function get( $trigger_name ) {
		static::init();

		if ( ! isset( static::$loaded[ $trigger_name ] ) ) {
			return false;
		}

		return static::$loaded[ $trigger_name ];
	}


	/**
	 * Get all available triggers.
	 *
	 * @return ES_Workflow_Trigger[]
	 */
	public static function get_all() {
		static::init();

		return static::$loaded;
	}

	/**
	 * Load and init all triggers
	 */
	public static function init() {
		foreach ( static::get_includes() as $name => $path ) {
			static::load( $name );
		}

		if ( ! did_action( 'ig_es_init_workflow_triggers' ) ) {
			do_action( 'ig_es_init_workflow_triggers' );
		}
	}


	/**
	 * Load trigger class based on its name.
	 *
	 * @param string $trigger_name Trigger name.
	 */
	public static function load( $trigger_name ) {
		if ( static::is_loaded( $trigger_name ) ) {
			return;
		}

		$trigger  = false;
		$includes = static::get_includes();

		if ( ! empty( $includes[ $trigger_name ] ) ) {
			/**
			 * Workflow Trigger object
			 *
			 * @var ES_Workflow_Trigger
			 */
			$trigger_class = $includes[ $trigger_name ];
			if ( class_exists( $trigger_class ) ) {
				$trigger = new $trigger_class();
				$trigger->set_name( $trigger_name );
			}
		}

		static::$loaded[ $trigger_name ] = $trigger;
	}

}
