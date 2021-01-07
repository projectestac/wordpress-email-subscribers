<?php
/**
 * Abstract trigger for form related triggers
 *
 * @since       4.4.6
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_Form_Submitted class.
 *
 * @since 4.4.6
 */
abstract class ES_Trigger_Form_Submitted extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'form_data' );

	/**
	 * Validate a workflow.
	 *
	 * @param ES_Workflow $workflow Workglow object.
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$form_data = $workflow->data_layer()->get_item( 'form_data' );

		if ( ! is_array( $form_data ) || empty( $form_data['email'] ) ) {
			return false;
		}

		return true;
	}

}
