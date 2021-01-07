<?php
/**
 * Factory class for ES_Workflow_Queue object
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Factory class for ES_Workflow_Queue object
 *
 * @since 4.4.1
 */
class ES_Workflow_Queue_Factory {

	/**
	 * Method to get ES_Workflow_Queue based on queue ID
	 *
	 * @param int $id Workflow Queue ID.
	 *
	 * @return bool|ES_Workflow_Queue
	 */
	public static function get( $id ) {
		$id = ES_Clean::id( $id );

		if ( ! $id ) {
			return false;
		}

		$queued_event = new ES_Workflow_Queue( $id );

		if ( ! $queued_event->exists ) {
			return false;
		}

		return $queued_event;
	}

}
