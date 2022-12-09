<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'ES_Pepipost_Mailer' ) ) {
	/**
	 * Class ES_Pepipost_Mailer
	 *
	 * @since 4.2.1
	 * @since 4.3.2 Modified response
	 */
	class ES_Pepipost_Mailer extends ES_Base_Mailer {

		/**
		 * Mailer name
		 *
		 * @since 4.8.5
		 * @var
		 */
		public $name = 'Pepipost';

		/**
		 * Mailer Slug
		 *
		 * @since 4.8.5
		 * @var
		 */
		public $slug = 'pepipost';

		/**
		 * Pepipost API Url
		 *
		 * @since 4.3.2
		 * @var string
		 */
		public $api_url = 'https://api.pepipost.com/v2/sendEmail';
		/**
		 * API Key
		 *
		 * @since 4.3.2
		 * @var string
		 */
		public $api_key = '';

		/**
		 * Flag to determine whether this mailer support batch sending or not
		 *
		 * @var boolean
		 *
		 * @since 4.7.5
		 */
		public $support_batch_sending = true;

		/**
		 * Stores batch sending mode
		 *
		 * @var boolean
		 *
		 * @since 4.7.5
		 */
		public $batch_sending_mode = 'multiple';

		/**
		 * Batch limit
		 *
		 * @var boolean
		 *
		 * @since 4.7.5
		 */
		public $batch_limit = 1000;

		/**
		 * ES_Pepipost_Mailer constructor.
		 *
		 * @since 4.3.2
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Set mailer data
		 *
		 * @since 4.7.5
		 */
		public function set_mailer_data() {

			$ig_es_mailer_settings = get_option( 'ig_es_mailer_settings', array() );

			if ( ES()->is_const_defined( 'pepipost', 'api_key' ) ) {
				$this->api_key = ES()->get_const_value( 'pepipost', 'api_key' );
			} else {
				$this->api_key = ! empty( $ig_es_mailer_settings['pepipost']['api_key'] ) ? $ig_es_mailer_settings['pepipost']['api_key'] : '';
			}

			if ( empty( $this->api_key ) ) {
				return $this->do_response( 'error', 'API Key is empty' );
			}

			// Reset body and headers.
			$this->reset_mailer_data();

			$this->set_header( 'Accept', 'application/json' );
			$this->set_header( 'content-type', 'application/json; charset=utf-8' );
			$this->set_header( 'user-agent', 'APIMATIC 2.0' );
			$this->set_header( 'api_key', $this->api_key );

			$this->set_tracking_options();
		}

		/**
		 * Set email data
		 * e.g. Sender email, name
		 *
		 * @since 4.7.5
		 */
		public function set_email_data( $email_data = array() ) {

			$sender_email   = ! empty( $email_data['sender_email'] ) ? $email_data['sender_email'] : '';
			$sender_name    = ! empty( $email_data['sender_name'] ) ? $email_data['sender_name'] : '';
			$reply_to_email = ! empty( $email_data['reply_to_email'] ) ? $email_data['reply_to_email'] : '';
			$subject        = ! empty( $email_data['subject'] ) ? $email_data['subject'] : '';
			$content        = ! empty( $email_data['content'] ) ? $email_data['content'] : '';

			$this->set_from( $sender_email, $sender_name );
			$this->set_reply_to( $reply_to_email );
			$this->set_subject( $subject );
			$this->set_content( $content );
		}

		/**
		 * Add into batch
		 *
		 * @param string $email
		 * @param array  $merge_tags
		 *
		 * @since 4.7.5
		 */
		public function add_into_batch( $email, $merge_tags = array() ) {

			$name        = ig_es_get_data( $merge_tags, 'name', '' );
			$first_name  = ig_es_get_data( $merge_tags, 'first_name', '' );
			$last_name   = ig_es_get_data( $merge_tags, 'last_name', '' );
			$list_name   = ig_es_get_data( $merge_tags, 'list_name', '' );
			$hash        = ig_es_get_data( $merge_tags, 'hash', '' );
			$contact_id  = ig_es_get_data( $merge_tags, 'contact_id', 0 );
			$campaign_id = ig_es_get_data( $merge_tags, 'campaign_id', 0 );
			$message_id  = ig_es_get_data( $merge_tags, 'message_id', 0 );
			$list_ids    = ig_es_get_data( $merge_tags, 'list_ids', '' );

			$link_data = array(
				'message_id'  => $message_id,
				'campaign_id' => $campaign_id,
				'contact_id'  => $contact_id,
				'email'       => $email,
				'guid'        => $hash,
				'list_ids'    => $list_ids,
			);

			$subscribe_link     = ES()->mailer->get_subscribe_link( $link_data );
			$unsubscribe_link   = ES()->mailer->get_unsubscribe_link( $link_data );
			$link_variables     = ES()->mailer->get_link_variable( $contact_id );
			$tracking_pixel_url = ES()->mailer->get_tracking_pixel_url( $link_data );

			$recipient_variables = array(
				'NAME'             => $name,
				'FIRSTNAME'        => $first_name,
				'LASTNAME'         => $last_name,
				'LIST'             => $list_name,
				'HASH'             => $hash,
				'EMAIL'            => $email,
				'contact_id'       => $contact_id,
				'CAMPAIGN_ID'      => $campaign_id,
				'MESSAGE_ID'       => $message_id,
				'LIST_IDS'         => $list_ids,
				'SUBSCRIBE_LINK'   => $subscribe_link,
				'UNSUBSCRIBE_LINK' => $unsubscribe_link,
			);

			if ( ! empty( $link_variables ) ) {
				$recipient_variables = array_merge( $recipient_variables, $link_variables );
			}

			if ( ! empty( $tracking_pixel_url ) ) {
				$recipient_variables['tracking_pixel_url'] = $tracking_pixel_url;
			}

			$this->set_recipients(
				array(
					'personalizations' => array(
						'recipient'  => $email,
						'attributes' => $recipient_variables,
					),
				)
			);

			$this->batch_data[] = $recipient_variables;

			$this->current_batch_size++;
		}

		/**
		 * Convert ES tags to mailer tags
		 *
		 * @param string $string
		 *
		 * @return string $string
		 *
		 * @since 4.7.5
		 */
		public function convert_es_tags_to_mailer_tags( $string = '' ) {
			$string = ES_Common::replace_keywords_with_fallback( $string, array(
				'subscriber.name'             => '[%name%]',
				'subscriber.first_name'        => '[%first_name%]',
				'subscriber.last_name'         => '[%last_name%]',
				'subscriber.email'            => '[%email%]',
				'subscriber.unsubscribe_link' => '[%unsubscribe_link%]',
				'subscriber.subscribe_link'   => '[%subscribe_link%]',
			));

			return ES_Common::replace_keywords_with_fallback( $string, array(
				'NAME'             => '[%NAME%]',
				'FIRSTNAME'        => '[%FIRSTNAME%]',
				'LASTNAME'         => '[%LASTNAME%]',
				'EMAIL'            => '[%EMAIL%]',
				'UNSUBSCRIBE-LINK' => '[%UNSUBSCRIBE_LINK%]',
				'SUBSCRIBE-LINK'   => '[%SUBSCRIBE_LINK%]',
			) );
		}

		/**
		 * Get variable prefix
		 *
		 * @return string
		 *
		 * @since 4.7.5
		 */
		public function get_variable_prefix() {
			return '[%';
		}

		/**
		 * Get variable suffix
		 *
		 * @return string
		 *
		 * @since 4.7.5
		 */
		public function get_variable_suffix() {
			return '%]';
		}

		/**
		 * Redefine the way email body is returned.
		 * By default we are sending an array of data.
		 * Pepipost requires a JSON, so we encode the body.
		 *
		 * @return string json encoded body data
		 *
		 * @since 4.7.5
		 */
		public function get_body() {

			$body = parent::get_body();
			return wp_json_encode( $body );
		}

		/**
		 * Set email subject.
		 *
		 * @param string $subject
		 *
		 * @since 4.7.5
		 */
		public function set_subject( $subject ) {

			$this->set_body_param(
				array(
					'subject' => $subject,
				)
			);
		}

		/**
		 * Set email content
		 *
		 * @param string content
		 *
		 * @since 4.7.5
		 */
		public function set_content( $content ) {
			
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$content = mb_convert_encoding( $content, 'UTF-8', mb_detect_encoding( $content, 'UTF-8, ISO-8859-1', true ) );
			}

			$this->set_body_param(
				array(
					'content' => $content,
				)
			);
		}

		/**
		 * Set from
		 *
		 * @param string $email
		 * @param string $name
		 *
		 * @since 4.7.5
		 */
		public function set_from( $email, $name = '' ) {

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			if ( ! empty( $name ) ) {
				$this->set_body_param(
					array(
						'from' => array(
							'fromEmail' => $email,
							'fromName'  => $name,
						),
					)
				);
			} else {
				$this->set_body_param(
					array(
						'from' => array(
							'fromEmail' => $email,
						),
					)
				);
			}
		}

		/**
		 * Set reply to
		 *
		 * @param string $email
		 *
		 * @since 4.7.5
		 */
		public function set_reply_to( $email ) {

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			$this->set_body_param(
				array(
					'replyToId' => $email,
				)
			);
		}

		/**
		 * Set recipients
		 *
		 * @param array $recipients
		 *
		 * @since 4.7.5
		 */
		public function set_recipients( $recipients ) {

			if ( empty( $recipients ) ) {
				return;
			}

			$default = array( 'personalizations' );

			foreach ( $recipients as $kind => $recipient_data ) {
				if (
					! in_array( $kind, $default, true ) ||
					empty( $recipient_data ) ||
					! is_array( $recipient_data )
				) {
					continue;
				}

				$this->set_body_param(
					array(
						$kind => array( $recipient_data ),
					)
				);
			}
		}

		/**
		 * Pepipost accepts an array of files content in body, so we will include all files and send.
		 *
		 * @param array $attachments
		 *
		 * @since 4.7.5
		 */
		public function set_attachments( $attachments ) {

			if ( empty( $attachments ) ) {
				return;
			}

			$data = array();

			foreach ( $attachments as $attachment_name => $attachment_path ) {
				$file = false;

				try {
					if ( is_file( $attachment_path ) && is_readable( $attachment_path ) ) {
						$file = file_get_contents( $attachment_path );
					}
				} catch ( Exception $e ) {
					$file = false;
				}

				if ( false === $file ) {
					continue;
				}

				$filetype = mime_content_type( $attachment_path );

				$data[] = array(
					'fileName'    => empty( $attachment_name ) ? 'file-' . wp_hash( microtime() ) . '.' . $filetype : trim( $attachment_name ), // required string, no CRLF.
					'fileContent' => base64_encode( $file ), // string, 1 character.
				);
			}

			if ( ! empty( $data ) ) {
				$this->set_body_param(
					array(
						'attachments' => $data,
					)
				);
			}
		}

		/**
		 * Set open/click tracking options
		 *
		 * @since 4.7.5
		 */
		public function set_tracking_options() {

			$tracking_settings = array(
				'opentrack'  => ES()->mailer->can_track_open() ? 1 : 0,
				'clicktrack' => ES()->mailer->can_track_clicks() ? 1 : 0,
			);

			$this->set_body_param(
				array(
					'settings' => $tracking_settings,
				)
			);
		}

		/**
		 * Clear batch
		 *
		 * @since 4.7.5
		 */
		public function clear_batch() {
			$this->body['personalizations'] = array();
			$this->batch_data               = array();
			$this->current_batch_size       = 0;
		}

		/**
		 * Send Email
		 *
		 * @param ES_Message $message
		 *
		 * @return bool|WP_Error
		 *
		 * @since 4.2.1
		 * @since 4.3.2 Modified Response
		 * @since 4.7.5 Used common functions to set email data both when sending bulk emails and single email
		 */
		public function send( ES_Message $message ) {

			$response = $this->set_mailer_data();

			// Error setting up mailer?
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$this->set_from( $message->from, $message->from_name );
			$this->set_reply_to( $message->reply_to_email );
			$this->set_subject( $message->subject );
			$this->set_content( $message->body );
			$this->set_recipients(
				array(
					'personalizations' => array(
						'recipient' => $message->to,
					),
				)
			);

			if ( $message->attachments ) {
				$this->set_attachments( $message->attachments );
			}

			$response = $this->send_email();

			return $response;
		}

		/**
		 * Send email using Pepipost API
		 *
		 * @since 4.7.5
		 */
		public function send_email() {

			ES()->logger->info( 'Start Sending Email Using Pepipost', $this->logger_context );

			$params = array_merge_recursive(
				$this->get_default_params(),
				array(
					'headers' => $this->get_headers(),
					'body'    => $this->get_body(),
				)
			);

			$response = wp_remote_post( $this->api_url, $params );
			if ( ! is_wp_error( $response ) ) {
				$body = ! empty( $response['body'] ) ? json_decode( $response['body'], true ) : '';

				if ( ! empty( $body ) ) {
					if ( 'Success' === $body['message'] ) {
						return $this->do_response( 'success' );
					} elseif ( ! empty( $body['error_info'] ) ) {
						return $this->do_response( 'error', $body['error_info']['error_message'] );
					}
				} else {
					$this->do_response( 'error', wp_remote_retrieve_response_message( $response ) );
				}
			} else {
				$error_message = $response->get_error_message();
				return $this->do_response( 'error', $error_message );
			}

			ES()->logger->info( 'Email Sent Successfully Using Pepipost', $this->logger_context );

			return $this->do_response( 'success' );
		}

	}

}
