<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable class to get value for  {{subscriber.subscribe_link}} placeholder
 * 
 * @since 5.7.13
 */
class IG_ES_Variable_Subscriber_Subscribe_Link extends IG_ES_Workflow_Variable {

	/**
	 * Method to set description and other admin props
	 *
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays subscribe link.', 'email-subscribers' );
	}


	/**
	 * Get subscriber email from order
	 * 
	 * @param $parameters array
	 * @return string
	 */
	public function get_value( $subscriber, $parameters ) {
		$subscribe_link = $this->get_subscribe_link( $subscriber );
		return $subscribe_link;
	}

	/**
	 * Get Subscribe link
	 *
	 * @param $link_data
	 *
	 * @return string
	 */
	public function get_subscribe_link( $link_data ) {
		$link_data['action'] = 'subscribe';

		return $this->prepare_link( $link_data );
	}

	/**
	 * Get link
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function prepare_link( $data = array() ) {

		/**
		 * We are getting different data like action, message_id, campaign_id, contact_id, guid, email etc in $data
		 */
		$action = ! empty( $data['action'] ) ? $data['action'] : '';

		if ( 'subscribe' === $action ) {
			$action = 'optin';
		}

		$link = add_query_arg( 'es', $action, site_url( '/' ) );

		$data = ig_es_encode_request_data( $data );

		$link = add_query_arg( 'hash', $data, $link );

		return $link;
	}
}

return new IG_ES_Variable_Subscriber_Subscribe_Link();
