<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'ES_Wpmail_Mailer' ) ) {

	class ES_Wpmail_Mailer extends ES_Base_Mailer {

		/**
		 * Mailer name
		 *
		 * @since 4.8.5
		 * @var
		 */
		public $name = 'WP Mail';

		/**
		 * Mailer Slug
		 *
		 * @since 4.8.5
		 * @var
		 */
		public $slug = 'wp_mail';

		/**
		 * ES_Wpmail_Mailer constructor.
		 *
		 * @since 4.3.2
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Send Email
		 *
		 * @param ES_Message $message
		 *
		 * @return boolean|WP_Error
		 *
		 * @since 4.3.2
		 */
		public function send( ES_Message $message ) {

			ES()->logger->info( 'Start Sending Email Using WP Mail', $this->logger_context );

			$send_mail = wp_mail( $message->to, $message->subject, $message->body, $message->headers, $message->attachments );

			if ( ! $send_mail ) {
				global $phpmailer;

				if ( is_object( $phpmailer ) && $phpmailer->ErrorInfo ) {
					$message = wp_strip_all_tags( $phpmailer->ErrorInfo );
				} else {
					$message = __( 'WP Mail Error: Unknown', 'email-subscribers' );
				}

				return $this->do_response( 'error', $message );
			}

			ES()->logger->info( 'Email Sent Successfully Using WP Mail', $this->logger_context );

			return $this->do_response( 'success' );
		}

	}

}
