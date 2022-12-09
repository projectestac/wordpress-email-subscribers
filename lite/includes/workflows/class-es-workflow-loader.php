<?php
/**
 * Loads functionalities required for the workflows.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/**
 *
 * Class to loads functionalities required for the workflows.
 *
 * @class   ES_Workflow_Loader
 */
class ES_Workflow_Loader {

	/**
	 * Instance of singleton.
	 *
	 * @var ES_Workflow_Loader
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( &$this, 'load' ), 20 );
	}

	/**
	 * Load workflows
	 */
	public function load() {

		// Init all triggers.
		// Actions don't load until required by admin interface or when a workflow runs.
		ES_Workflow_Triggers::init();

		if ( is_admin() ) {
			$this->admin = new ES_Workflow_Admin();
			ES_Workflow_Admin::init();

			if ( ES()->is_pro() ) {
				ES_Pro_Workflow_Admin::init();
			}
		}
	}

	/**
	 * Return the singleton instance.
	 *
	 * @return ES_Workflow_Loader
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}

ES_Workflow_Loader::instance();
