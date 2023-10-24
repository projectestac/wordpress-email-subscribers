<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable class to get value for  {{subscriber.email}} placeholder
 */
class IG_ES_Variable_Subscriber_Email extends IG_ES_Workflow_Variable {

	/**
	 * Method to set description and other admin props
	 *
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays subscriber email.', 'email-subscribers' );
	}


	/**
	 * Get subscriber email from order
	 * 
	 * @param $parameters array
	 * @return string
	 */
	public function get_value( $subscriber, $parameters ) {
		return $subscriber['email'];
	}
}

return new IG_ES_Variable_Subscriber_Email();
