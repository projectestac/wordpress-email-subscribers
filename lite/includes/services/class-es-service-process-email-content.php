<?php

class ES_Service_Process_Email_Content extends ES_Services {

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @sinc 4.6.1
	 */
	public $cmd = '/email/process/';

	/**
	 * ES_Service_Process_Email_Content constructor.
	 *
	 * @since 4.6.1
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get inline CSS
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 * @since 4.6.1
	 */
	public function process_email_content( $data = array() ) {

		$data = apply_filters( 'ig_es_util_data', $data );

		// Check if we have content to process and task to be performed.
		if ( ! empty( $data['content'] ) && ! empty( $data['tasks'] ) ) {
			$options  = array(
				'timeout' => 15,
				'method'  => 'POST',
				'body'    => $data,
			);
			$response = $this->send_request( $options );

			// Change data only if we have got a valid response from the service.
			if ( ! $response instanceof WP_Error ) {
				$data = $response;
			}
		}

		return $data;
	}
}
