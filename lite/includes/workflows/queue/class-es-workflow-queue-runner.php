<?php
/**
 * Workflow Queue Runner
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/**
 * ES_Workflow_Queue_Runner plugin singleton.
 *
 * @class   ES_Workflow_Queue_Runner
 */
class ES_Workflow_Queue_Runner {

	/**
	 * Instance of singleton.
	 *
	 * @var ES_Workflow_Queue_Runner
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Init queue runner to process workflow queue
	 */
	public static function init() {
		add_action( 'wp_ajax_ig_es_trigger_workflow_queue_processing', array( __CLASS__, 'init_queue_runner' ) );
		add_action( 'wp_ajax_nopriv_ig_es_trigger_workflow_queue_processing', array( __CLASS__, 'init_queue_runner' ) );
	}

	/**
	 * Return the singleton instance.
	 *
	 * @return ES_Workflow_Queue_Runner
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Furnction to initiate action scheduler queue runner when a trigger is fired. It gets called after queueing workflows in the queue table.
	 */
	public static function init_queue_runner() {
		if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
			$queue_runner = ActionScheduler_QueueRunner::instance();
			$queue_runner->run();
		}
	}
}

ES_Workflow_Queue_Runner::instance();
