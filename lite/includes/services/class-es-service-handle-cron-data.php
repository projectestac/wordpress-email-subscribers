<?php

class ES_Service_Handle_Cron_Data extends ES_Services {

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @sinc 4.6.1
	 */
	public $cmd = '/store/cron/';

	/**
	 * ES_Service_Handle_Cron_Data constructor.
	 *
	 * @since 4.6.1
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Trigger cron save data
	 *
	 * @since 4.6.1
	 */
	public function handle_es_cron_data( $options = array() ) {

		if ( ES()->validate_service_request( array( 'es_cron' ) ) ) {

			$ig_es_set_cron_data = get_option( 'ig_es_set_cron_data', 'no' );
			$ig_es_set_cron_data = ( ! empty( $options ) && 'email_subscribers_settings' === $options['option_page'] ) ? 'no' : $ig_es_set_cron_data;
			if ( 'yes' === $ig_es_set_cron_data ) {
				return;
			}

			// send url and limit to server
			$es_cron_url_data                         = array();
			$es_cron_url_data['es_cronurl']           = get_option( 'ig_es_cronurl' );
			$es_cron_url_data['es_croncount']         = get_option( 'ig_es_hourly_email_send_limit', 50 );
			$es_cron_url_data['es_enable_background'] = true;

			$this->send_cron_data( $es_cron_url_data );

			update_option( 'ig_es_set_cron_data', 'yes' );
			delete_option( 'ig_es_cron_data_deleted' );
		} else {

			if ( ES()->trial->is_trial() ) {
				$ig_es_cron_data_deleted = get_option( 'ig_es_cron_data_deleted', 'no' );

				// Check if we have already deleted the cron data.
				if ( 'yes' === $ig_es_cron_data_deleted ) {
					return;
				}

				$request_response = $this->delete_cron_data();

				if ( ! $request_response instanceof WP_Error && ! empty( $request_response['status'] ) && 'SUCCESS' === $request_response['status'] ) {
					update_option( 'ig_es_cron_data_deleted', 'yes' );
				}
			}
		}
	}

	/**
	 * Delete cron data.
	 *
	 * @since 4.6.1
	 */
	public function delete_cron_data() {
		delete_option( 'ig_es_set_cron_data' );

		$es_cron_url_data                         = array();
		$es_cron_url_data['es_cronurl']           = ES()->cron->url();
		$es_cron_url_data['es_enable_background'] = false;

		return $this->send_cron_data( $es_cron_url_data );
	}

	/**
	 * Send cron data to our server
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 * @since 4.6.1
	 */
	public function send_cron_data( $data = array() ) {

		$response = array(
			'status' => 'error',
		);

		if ( ! empty( $data ) ) {
			$data['tasks'][] = 'store-cron';
			$options         = array(
				'timeout'   => 15,
				'method'    => 'POST',
				'body'      => $data,
				'sslverify' => false,
			);
			$response        = $this->send_request( $options );
		}

		return $response;
	}
}
