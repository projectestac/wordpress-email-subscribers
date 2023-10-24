<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable class to get value for  {{subscriber.last_name}} placeholder
 */
class IG_ES_Variable_Subscriber_Last_Name extends IG_ES_Workflow_Variable {

	/**
	 * Method to set description and other admin props
	 *
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays subscriber name.', 'email-subscribers' );
	}


	/**
	 * Get subscriber name from order
	 * 
	 * @param $parameters array
	 * @return string
	 */
	public function get_value( $subscriber, $parameters ) {
		return $subscriber['last_name'];
	}
}

return new IG_ES_Variable_Subscriber_Last_Name();
