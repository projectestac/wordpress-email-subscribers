<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Base_Mailer' ) ) {
	/**
	 * Class ES_Base_Mailer
	 *
	 * @since 4.3.2
	 */
	class ES_Base_Mailer {
		/**
		 * Mailer name
		 *
		 * @since 4.3.2
		 * @var
		 *
		 */
		public $name;

		/**
		 * Mailer Slug
		 *
		 * @since 4.3.2
		 * @var
		 *
		 */
		public $slug;

		/**
		 * Mailer Version
		 *
		 * @since 4.3.2
		 * @var string
		 *
		 */
		public $version = '1.0';

		/**
		 * Added Logger Context
		 *
		 * @since 4.2.0
		 * @var array
		 *
		 */
		public $logger_context = array(
			'source' => 'ig_es_email_sending'
		);

		/**
		 * ES_Base_Mailer constructor.
		 *
		 * @since 4.3.2
		 */
		public function __construct() {

		}

		/**
		 * Send Method
		 *
		 * @since 4.3.2
		 */
		public function send( ES_Message $message ) {
			return new WP_Error( 'ig_es_email_sending_failed', 'Send Method Not Implemented' );
		}

		/**
		 * Method will be called before email send
		 *
		 * @since 4.3.2
		 */
		public function pre_send( ES_Message $message ) {

		}

		/**
		 * Method will be called after email send
		 *
		 * @since 4.3.2
		 */
		public function post_send( ES_Message $message ) {

		}

		/**
		 * Prepare Response
		 *
		 * @param string $status
		 * @param string $message
		 *
		 * @return bool|WP_Error
		 *
		 * @since 4.3.2
		 */
		public function do_response( $status = 'success', $message = '' ) {

			if ( 'success' !== $status ) {
				return new WP_Error( 'ig_es_email_sending_failed', $message );
			}

			return true;
		}

	}
}
