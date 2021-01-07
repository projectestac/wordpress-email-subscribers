<?php
/**
 * Workflow Queue Handler
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background processor for the queue
 */
class ES_Workflow_Queue_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'ig_es_process_workflow_queue', array( &$this, 'process_workflow_queue' ) );
	}

	/**
	 * Method to process workflows queue.
	 *
	 * @param array $args action arguements.
	 */
	public function process_workflow_queue( $args = array() ) {

		if ( empty( $args['queue_id'] ) || ! is_numeric( $args['queue_id'] ) ) {
			return false;
		}

		$queue = ES_Workflow_Queue_Factory::get( $args['queue_id'] );
		if ( ! $queue ) {
			return false;
		}

		/** IMPORTANT - since we are running this in background, check if the queue is failed.
			This ensures the queue has not already begun to run in a different process
			since we are preemptively marking events as failed when they begin to run */
		if ( $queue->is_failed() ) {
			return false;
		}

		$queue->run();

		return false;
	}

}

return new ES_Workflow_Queue_Handler();
