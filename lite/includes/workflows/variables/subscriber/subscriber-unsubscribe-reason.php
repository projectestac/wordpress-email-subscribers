<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable class to get value for {{subscriber.unsubscriber_reason}} placeholder
 */
class IG_ES_Variable_Subscriber_Unsubscribe_Reason extends IG_ES_Workflow_Variable {

	/**
	 * Method to set description and other admin props
	 *
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays unsubscriber feedback.', 'email-subscribers' );
	}


	/**
	 * Get unsubscriber feedback
	 * 
	 * @param $parameters array
	 * @return string
	 */
	public function get_value( $subscriber, $parameters ) {
		
		return $subscriber['unsubscribe_feedback_text'];
	}
}

return new IG_ES_Variable_Subscriber_Unsubscribe_Reason();
