<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'ES_Phpmail_Mailer' ) ) {
	/**
	 * Class ES_Phpmail_Mailer
	 *
	 * @since 4.3.2
	 */
	class ES_Phpmail_Mailer extends ES_Base_Mailer {

		/**
		 * Mailer name
		 *
		 * @since 4.8.5
		 * @var
		 */
		public $name = 'PHP mail';

		/**
		 * Mailer Slug
		 *
		 * @since 4.8.5
		 * @var
		 */
		public $slug = 'php_mail';

		/**
		 * ES_Phpmail_Mailer constructor.
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
		 * @return bool|WP_Error
		 *
		 * @since 4.3.2
		 */
		public function send( ES_Message $message ) {

			global $wp_version;

			ES()->logger->info( 'Start Sending Email Using PHP Mail', $this->logger_context );

			if ( version_compare( $wp_version, '5.5', '<' ) ) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
			} else {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

				// Check if PHPMailer class already exists before creating an alias for it.
				if ( ! class_exists( 'PHPMailer' ) ) {
					class_alias( PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer' );
				}

				// Check if phpmailerException class already exists before creating an alias for it.
				if ( ! class_exists( 'phpmailerException' ) ) {
					class_alias( PHPMailer\PHPMailer\Exception::class, 'phpmailerException' );
				}
			}

			$phpmailer           = new PHPMailer( true );
			$phpmailer->From     = $message->from;
			$phpmailer->FromName = $message->from_name;
			$phpmailer->CharSet  = $message->charset;
			$phpmailer->ClearAllRecipients();
			$phpmailer->clearAttachments();
			$phpmailer->clearCustomHeaders();
			$phpmailer->clearReplyTos();

			$phpmailer->addAddress( $message->to );
			$phpmailer->addReplyTo( $message->from, $message->from_name );

			$phpmailer->WordWrap = 50;
			$phpmailer->isHTML( true );

			$list_unsubscribe_header = ES()->mailer->get_list_unsubscribe_header( $message->to );
			if ( ! empty( $list_unsubscribe_header ) ) {
				$phpmailer->addCustomHeader( 'List-Unsubscribe', $list_unsubscribe_header );
				$phpmailer->addCustomHeader( 'List-Unsubscribe-Post', 'List-Unsubscribe=One-Click' );
			}

			apply_filters( 'ig_es_php_mailer_email_headers', $phpmailer );

			$phpmailer->Subject = $message->subject;
			$phpmailer->Body    = $message->body;
			$phpmailer->AltBody = $message->body_text; // Text Email Body for non html email client

			if ( ! empty( $message->attachments ) ) {
				$attachments = $message->attachments;
				foreach ( $attachments as $attachment ) {
					try {
						$phpmailer->addAttachment( $attachment );
					} catch ( phpmailerException $e ) {
						continue;
					}
				}
			}

			try {
				if ( ! $phpmailer->send() ) {
					ES()->logger->error( '[Error in Email Sending] : ' . $message->to . ' Error: ' . $phpmailer->ErrorInfo, $this->logger_context );

					return $this->do_response( 'error', $phpmailer->ErrorInfo );
				}
			} catch ( Exception $e ) {
				ES()->logger->error( '[Error in Email Sending] : ' . $message->to . ' Error: ' . $e->getMessage(), $this->logger_context );

				return $this->do_response( 'error', $e->getMessage() );
			}

			ES()->logger->info( 'Email Sent Successfully Using PHP Mail', $this->logger_context );

			return $this->do_response( 'success' );

		}

	}

}
