<?php
/**
 * Load workflows based on ID
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Class to load workflows based on ID
 *
 * @class ES_Workflow_Factory
 *
 * @since 4.4.1
 */
class ES_Workflow_Factory {

	/**
	 * Method to get workflow from workflow ID
	 *
	 * @param int $id Workflow ID.
	 *
	 * @return ES_Workflow|false
	 *
	 * @since 4.4.1
	 */
	public static function get( $id ) {
		$id = ES_Clean::id( $id );

		if ( ! $id ) {
			return false;
		}

		$workflow = new ES_Workflow( $id );

		if ( ! $workflow->exists ) {
			return false;
		}

		return $workflow;
	}

}
