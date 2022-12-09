<?php

class ES_Service_Spam_Score_Check extends ES_Services {

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @since 4.6.1
	 */
	public $cmd = '/email/process/';

	/**
	 * ES_Service_Spam_Score_Check constructor.
	 *
	 * @since 4.6.1
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get spam score data
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 * @since 4.6.1
	 */
	public function get_spam_score( $data = array() ) {

		$response = array(
			'status' => 'error',
		);

		if ( ES()->validate_service_request( array( 'spam_score_check' ) ) ) {
			if ( ! empty( $data ) ) {
				$data['options']    = 'full';
				$options            = array(
					'timeout' => 50,
					'method'  => 'POST',
					'body'    => $data,
				);
				$response_data      = $this->send_request( $options );
				$response['data']   = $response_data;
				$response['status'] = 'success';
			}
		}

		return $response;
	}

}
