<?php

class ES_Services {

	/**
	 * API URL
	 *
	 * @since 4.6.0
	 * @var string
	 */
	public $api_url = 'https://api.icegram.com';

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @sinc 4.6.0
	 */
	public $cmd = '';

	/**
	 * ES_Services constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {

	}

	/**
	 * Send Request
	 *
	 * @param array  $options
	 * @param string $method
	 *
	 * @since 4.6.0
	 */
	public function send_request( $options = array(), $method = 'POST', $validate_request = true ) {

		$response = array();

		if ( empty( $this->cmd ) ) {
			return new WP_Error( '404', 'Command Not Found' );
		}

		if ( $validate_request ) {
			// Request is valid if trial is valid or user is on a premium plan.
			$is_request_valid = ES()->trial->is_trial_valid() || ES()->is_premium();
	
			// Allow custom validation logic for some services e.g. sending es cron delete request even after trial is inactive.
			$is_request_valid = apply_filters( 'ig_es_service_request_custom_validation', $is_request_valid, $options );
	
			// Don't process request if request is not valid.
			if ( ! $is_request_valid ) {
				return $options;
			}
		}


		$url = $this->api_url . $this->cmd;

		$options = apply_filters( 'ig_es_service_request_data', $options );

		if ( ! empty( $options ) && is_array( $options ) ) {
			if ( 'POST' === $method ) {
				$response = wp_remote_post( $url, $options );
			} else {
				$response = wp_remote_get( $url, $options );
			}

			if ( ! is_wp_error( $response ) ) {

				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

					$response_data = $response['body'];

					if ( 'error' != $response_data ) {

						$response_data = json_decode( $response_data, true );

						do_action( 'ig_es_service_response_received', $response_data, $options );

						return $response_data;
					}
				}
			}
		}

		return $response;

	}
}
