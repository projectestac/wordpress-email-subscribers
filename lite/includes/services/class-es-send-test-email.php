<?php

/**
 * Class ES_Send_Test_Email
 */
class ES_Send_Test_Email {
	/**
	 * ES_Send_Test_Email constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {

	}

	/**
	 * Send Test Email
	 *
	 * @since 4.6.0
	 */
	public function send_test_email( $params = array() ) {

		if ( !empty( $params['email'] )) {
			$email = $params['email'];
		} else {
			$email = ES_Common::get_test_email();
		}
		$response = array( 'status' => 'ERROR' );

		if ( ! empty( $email ) ) {
			$subject = ES()->mailer->get_test_email_subject( $email );

			$content = ES()->mailer->get_test_email_content();

			$response = ES()->mailer->send_test_email( $email, $subject, $content );
		}

		return $response;
	}
}
