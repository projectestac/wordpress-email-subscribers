<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Message' ) ) {
	/**
	 * Class ES_Message
	 *
	 * @since 4.3.2
	 */
	class ES_Message {
		/**
		 * To email
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $to = '';

		/**
		 * To name
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $to_name = '';

		/**
		 * Message headers
		 *
		 * @var array
		 *
		 * @since 4.3.2
		 */
		public $headers = array();

		/**
		 * Message errors
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $error = '';

		/**
		 * Message subject
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $subject = '';

		/**
		 * Message body
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $body = '';

		/**
		 * Message text
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $body_text = '';

		/**
		 * Message From
		 *
		 * @var
		 *
		 * @sinc 4.3.2
		 */
		public $from;

		/**
		 * Message from name
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $from_name = '';


		public function __construct() {

		}

	}
}


