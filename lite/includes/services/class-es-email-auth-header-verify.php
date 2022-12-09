<?php

class ES_Service_Auth_Header_Check extends ES_Services {

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @sinc 5.x
	 */
	public $cmd = '/email/auth/:mailbox';

	/**
	 * ES_Email_Auth_Headers 
	 *
	 * @since 4.6.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get Email Authentication Headers
	 *
	 * @return mixed
	 *
	 * @since 4.6.0
	 */
	public function get_email_authentication_headers() {

		$mailbox   = ES_Common::get_email_verify_test_email();
		$this->cmd = str_replace( ':mailbox', $mailbox, $this->cmd );

		$request_data     = array();
		$request_method   = 'GET';
		$validate_request = true;

		$response = $this->send_request( $request_data, $request_method, $validate_request );

		$res = array();
		if ( is_wp_error( $response ) ) {
			$res['status'] = 'error';
		} else {
			if ( 'success' === $response['status']) {
				$res['status'] = 'success';
				$res['data']   = $response['data'];
			} else {
				$res['additional_message'] = __( ' The test email did not reach our test server. Did you get any test emails on your email? This could be a temporary problem, but it can also mean that emails are getting stuck on your server, or getting rejected by recipients.', 'email-subscribers' );
				$res['status']             = 'error';
			}
		}

		return $res;
	}

	public static function get_verification_score() {
		$headers = get_option('ig_es_email_auth_headers', []);
		$points  = 0;
		$remark  = '<div class="text-gray-600 font-bold">Not verified</div>';
		if ( ! empty($headers) ) {
			$remark = '<div class="text-red-500 font-bold">Needs Improvement</div>';

			foreach ( $headers as $header ) {
				if ( strpos( $header['test'], 'PASS') !== -1 ) {
					$points -= 0.1;
				} elseif ( strpos( $header['test'], 'FAIL') !== -1 ) {
					$points += 0.1;
				}
			}

			if ($points < -0.2) {
				$remark = '<div class="text-green-400 font-bold">Excellent</div>';
			} elseif ($points < 0) {
				$remark = '<div class="text-green-400 font-bold">Good</div>';
			}
		}

		$result = array(
			'points' => $points,
			'remark' => $remark
		); 

		return( $result );

	}

	

}
