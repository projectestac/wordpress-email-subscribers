<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Icegram_Mailer' ) ) {
	/**
	 * Class ES_Icegram_Mailer
	 *
	 * @since 5.6.0
	 */
	class ES_Icegram_Mailer extends ES_Base_Mailer {

		/**
		 * Mailer name
		 *
		 * @since 5.6.0
		 * @var
		 */
		public $name = 'Icegram';

		/**
		 * Mailer Slug
		 *
		 * @since 5.6.0
		 * @var
		 */
		public $slug = 'icegram';

		/**
		 * Which response code from HTTP provider is considered to be successful?
		 *
		 * @var int
		 */
		public $email_sent_code = 200;

		/**
		 * Icegram API Url
		 *
		 * @var string
		 *
		 */
		public $api_url = 'https://api.igeml.com/accounts/mail/send/';

		/**
		 * Private API Key
		 *
		 * @var string
		 *
		 */
		public $api_key = '';

		/**
		 * Domain Name
		 *
		 * @var string
		 *
		 */
		public $domain_name = '';

		/**
		 * Region
		 *
		 * @var string
		 *
		 */
		public $region = 'us';

		/**
		 * Flag to determine whether this mailer support batch sending or not
		 * 
		 * @var boolean
		 * 
		 * @since 5.6.0
		 */
		public $support_batch_sending = true;

		/**
		 * Stores batch sending mode
		 * 
		 * @var boolean
		 * 
		 * @since 5.6.0
		 */
		public $batch_sending_mode = 'multiple';
		
		/**
		 * Batch limit
		 * 
		 * @var boolean
		 * 
		 * @since 5.6.0
		 */
		public $batch_limit = 30;

		/**
		 * ES_Icegram_Mailer constructor.
		 *
		 * @since 5.6.0
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Set mailer data
		 * 
		 * @since 5.6.0
		 */
		public function set_mailer_data() {

			ES()->logger->info( 'Start Sending Email Using Icegram', $this->logger_context );

			$ig_es_ess_data = get_option( 'ig_es_ess_data', array() );

			if ( ES()->is_const_defined( 'icegram', 'api_key' ) ) {
				$this->api_key = ES()->get_const_value( 'icegram', 'api_key' );
			} else {
				$this->api_key = ! empty( $ig_es_ess_data['api_key'] ) ? $ig_es_ess_data['api_key'] : '';
			}

			if ( empty( $this->api_key ) ) {
				return $this->do_response( 'error', __( 'API key is empty.', 'email-subscribers' ) );
			}

			// Reset body and headers.
			$this->reset_mailer_data();

			// We don't need to encode the api key using base64 since it now not required in Icegram api
			$this->set_header( 'Authorization', 'Bearer ' . $this->api_key  );
			$this->set_header( 'Content-Type', 'application/json' );

			$this->set_tracking_options();
			$this->enable_sandbox_mode();
			$this->set_ess_metadata();
		}

		/**
		 * Set email data
		 * e.g. Sender email, name
		 * 
		 * @since 5.6.0
		 */
		public function set_email_data( $email_data = array() ) {

			$sender_email   = ! empty( $email_data['sender_email'] ) ? $email_data['sender_email']    : '';
			$sender_name    = ! empty( $email_data['sender_name'] ) ? $email_data['sender_name']      : '';
			$reply_to_email = ! empty( $email_data['reply_to_email'] ) ? $email_data['reply_to_email']: '';
			$subject        = ! empty( $email_data['subject'] ) ? $email_data['subject']              : '';
			$content        = ! empty( $email_data['content'] ) ? $email_data['content']              : '';

			$this->set_from( $sender_email, $sender_name );
			$this->set_reply_to( $reply_to_email );
			$this->set_subject( $subject );
			$this->set_content( array(
				'html' => $content
			) );
		}

		/**
		 * Add into batch
		 * 
		 * @param string $email
		 * @param array $merge_tags
		 * 
		 * @since 5.6.0
		 */
		public function add_into_batch( $email, $merge_tags = array(), $message = null ) {
			
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
				'list_ids'	  => $list_ids,
			);
			
			$subscribe_link     = ES()->mailer->get_subscribe_link( $link_data );
			$unsubscribe_link   = ES()->mailer->get_unsubscribe_link( $link_data );
			$link_variables     = ES()->mailer->get_link_variable( $contact_id );
			$tracking_pixel_url = ES()->mailer->get_tracking_pixel_url( $link_data );

			$contact_data = array(
				'name'             => $name,
				'first_name'       => $first_name,
				'last_name'        => $last_name,
				'list_name'        => $list_name,
				'hash'             => $hash,
				'email'            => $email,
				'contact_id'       => $contact_id,
				'campaign_id'      => $campaign_id,
				'message_id'       => $message_id,
				'list_ids'         => $list_ids,
				'subscribe_link'   => $subscribe_link,
				'unsubscribe_link' => $unsubscribe_link,
			);

			if ( ! empty( $link_variables ) ) {
				$contact_data = array_merge( $contact_data, $link_variables );
			}

			if ( ! empty( $tracking_pixel_url )) {
				$contact_data['tracking_pixel_url'] = $tracking_pixel_url;
			}

			// Check if it is an campaign email and headers are enabled on the site.
			$list_unsubscribe_header = ES()->mailer->get_list_unsubscribe_header( $email );
			if ( $list_unsubscribe_header ) {
				$contact_data['list_unsubscribe_header'] = $list_unsubscribe_header;
			}

			$contact_data = apply_filters( 'ig_es_contact_mail_data', $contact_data, $merge_tags );

			$recipient_variables = array();
			$variable_prefix     = $this->get_variable_prefix();
			$variable_suffix     = $this->get_variable_suffix();

			foreach ( $contact_data as $data_key => $data_value ) {
				$recipient_variables[ $variable_prefix . $data_key . $variable_suffix ] = $data_value;
			}

			$this->set_recipients(
				array(
					'to'            => array( $email ),
					'substitutions' => $recipient_variables
				)
			);

			$this->batch_data[] = $contact_data;
			
			$this->current_batch_size++;
		}

		/**
		 * Convert ES tags to mailer tags
		 * 
		 * @param string $string
		 * 
		 * @return string $string
		 * 
		 * @since 5.6.0
		 */
		public function convert_es_tags_to_mailer_tags( $string = '' ) {

			$mailer_tags_mapping = array(
				'subscriber.name'             => '-name-',
				'subscriber.first_name'        => '-first_name-',
				'subscriber.last_name'         => '-last_name-',
				'subscriber.email'            => '-email-',
				'subscriber.unsubscribe_link' => '-unsubscribe_link-',
				'subscriber.subscribe_link'   => '-subscribe_link-',
			);

			$mailer_tags_mapping = apply_filters( 'ig_es_mailer_tags_mapping', $mailer_tags_mapping );
			
			$string = ES_Common::replace_keywords_with_fallback( $string, $mailer_tags_mapping );

			return ES_Common::replace_keywords_with_fallback( $string, array(
				'NAME'             => '-name-',
				'FIRSTNAME'        => '-first_name-',
				'LASTNAME'         => '-last_name-',
				'EMAIL'            => '-email-',
				'UNSUBSCRIBE-LINK' => '-unsubscribe_link-',
				'SUBSCRIBE-LINK'   => '-subscribe_link-',
			) );
		}

		/**
		 * Get variable prefix
		 * 
		 * @return string
		 * 
		 * @since 5.6.0
		 */
		public function get_variable_prefix() {
			return '-';
		}

		/**
		 * Get variable suffix
		 * 
		 * @return string
		 * 
		 * @since 5.6.0
		 */
		public function get_variable_suffix() {
			return '-';
		}

		/**
		 * Send Email
		 *
		 * @param ES_Message $message
		 *
		 * @return bool|WP_Error
		 */
		public function send( ES_Message $message ) {

			ES()->logger->info( 'Start Sending Email Using Icegram', $this->logger_context );

			$ig_es_ess_data = get_option( 'ig_es_ess_data', array() );

			if ( ES()->is_const_defined( 'icegram', 'api_key' ) ) {
				$this->api_key = ES()->get_const_value( 'icegram', 'api_key' );
			} else {
				$this->api_key = ! empty( $ig_es_ess_data['api_key'] ) ? $ig_es_ess_data['api_key'] : '';
			}

			if ( empty( $this->api_key ) ) {
				return $this->do_response( 'error', __( 'API key is empty.', 'email-subscribers' ) );
			}

			// Reset body and headers.
			$this->reset_mailer_data();

			// We don't need to encode the api key using base64 since it now not required in Icegram api
			$this->set_header( 'Authorization', 'Bearer ' . $this->api_key  );
			$this->set_header( 'Content-Type', 'application/json' );
			$this->set_from( $message->from, $message->from_name );
			$this->set_reply_to( $message->reply_to_email );
			$this->set_list_unsubscribe_header( $message->to );
			$this->set_tracking_options();
			$this->enable_sandbox_mode();
			$this->set_ess_metadata();
			
			$this->set_recipients(
				array(
					'to'  => array( $message->to ),
				)
			);
			$this->set_subject( $message->subject );
			$this->set_content(
				array(
					'html' => $message->body,
				)
			);

			/*
			* In some cases we will need to modify the internal structure
			* of the body content, if attachments are present.
			* So lets make this call the last one.
			*/
			if ( $message->attachments ) {
				$this->set_attachments( $message->attachments );
			}

			$params = array_merge_recursive( $this->get_default_params(), array(
				'headers' => $this->get_headers(),
				'body'    => $this->get_body(),
			));
			
			$response = wp_remote_post( $this->api_url, $params );
			if ( ! is_wp_error( $response ) ) {
				$body = ! empty( $response['body'] ) && ES_Common::is_valid_json( $response['body'] ) ? json_decode( $response['body'], true ) : '';
				if ( ! empty( $body ) ) {
					$status = ! empty( $body['status'] ) ? $body['status'] : 'error';
					if ( 'success' === $status ) {
						ES()->logger->info( 'Email Sent Successfully Using Icegram', $this->logger_context );
						return $this->do_response( 'success' );
					} else {
						if ( ! empty( $body['message'] ) ) {
							$error_message = $body['message'];
						} else {
							$error_message = __( 'An unknown error has occured. Please try again later.', 'email-subscribers' );
						}
					}
					return $this->do_response( 'error', $error_message );
				} else {
					return $this->do_response( 'error', wp_remote_retrieve_response_message( $response ) );
				}
			} else {
				$error_message = $response->get_error_message();
				return $this->do_response( 'error', $error_message );
			}

			ES()->logger->info( 'Email Sent Successfully Using Icegram', $this->logger_context );

			return $this->do_response( 'success' );
		}

		/**
		 * Clear batch
		 * 
		 * @since 5.6.0
		 */
		public function clear_batch() {
			$this->body['personalizations'] = array();
			$this->batch_data               = array();
			$this->current_batch_size       = 0;
		}

		/**
		 * Redefine the way email body is returned.
		 * By default we are sending an array of data.
		 * Icegram requires a JSON, so we encode the body.
		 *
		 */
		public function get_body() {

			$body = parent::get_body();

			return wp_json_encode( $body );
		}

		/**
		 * Set from
		 */
		public function set_from( $email, $name = '' ) {

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			$from['email'] = $email;

			if ( ! empty( $name ) ) {
				$from['name'] = $name;
			}

			$this->set_body_param(
				array(
					'from' => $from,
				)
			);
		}

		/**
		 * Set reply to
		 * 
		 * @param string $email
		 * 
		 * @since 4.6.7
		 */
		public function set_reply_to( $email ) {

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			$this->set_body_param(
				array(
					'reply_to' => array(
						'email' => $email,
					),
				)
			);
		}

		/**
		 * Set list unsubscribe header
		 * 
		 * @param string $email
		 * 
		 * @since 4.7.2
		 */
		public function set_list_unsubscribe_header( $email = '' ) {

			if ( empty( $email ) ) {
				if ( ES()->mailer->unsubscribe_headers_enabled() ) {
					$this->set_body_param(
						array(
							'headers' => array(
								'List-Unsubscribe'      => '-list_unsubscribe_header-',
								'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
							),
						)
					);
				}
			} else {
				$list_unsubscribe_header = ES()->mailer->get_list_unsubscribe_header( $email );
				if ( ! empty( $list_unsubscribe_header ) ) {
					$this->set_body_param(
						array(
							'headers' => array(
								'List-Unsubscribe'      => $list_unsubscribe_header,
								'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
							),
						)
					);
				}
			}
		}

		/**
		 * Set recipients
		 */
		public function set_recipients( $recipients ) {

			if ( empty( $recipients ) ) {
				return;
			}

			$data = array();

			foreach ( $recipients as $type => $recipient_data ) {

				$data[ $type ] = array();

				if ( in_array( $type, array( 'to' ), true) ) {

					$emails = $recipient_data;
					if (
						empty( $emails ) ||
						! is_array( $emails )
					) {
						continue;
					}
	
					// Iterate over all emails for each type.
					// There might be multiple cc/to/bcc emails.
					foreach ( $emails as $email ) {
						$holder = array();
	
						if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
							continue;
						}
	
						$holder['email'] = $email;
	
						array_push( $data[ $type ], $holder );
					}
				} elseif ( 'substitutions' === $type ) {
					$data[ $type ] = $recipient_data;
				}
			}

			if ( ! empty( $data ) ) {
				$this->set_body_param(
					array(
						'personalizations' => array( $data ),
					)
				);
			}
		}

		/**
		 * Set email content
		 */
		public function set_content( $content ) {

			if ( empty( $content ) ) {
				return;
			}

			if ( is_array( $content ) ) {

				$default = array( 'html' );
				$data    = array();

				foreach ( $content as $type => $body ) {
					if (
						! in_array( $type, $default, true ) ||
						empty( $body )
					) {
						continue;
					}

					$content_value = $body;
					$content_type  = 'text/html';

					$data[] = array(
						'type'  => $content_type,
						'value' => $content_value,
					);
				}

				$this->set_body_param(
					array(
						'content' => $data,
					)
				);
			} else {
				$data['type']  = 'text/html';
				$data['value'] = $content;
				$this->set_body_param(
					array(
						'content' => array( $data ),
					)
				);
			}
		}
		
		/**
		 * This mailer supports email-related custom headers inside a body of the message.
		 *
		 * @param string $name
		 * @param string $value
		 */
		public function set_body_header( $name, $value ) {

			$name = sanitize_text_field( $name );
			if ( empty( $name ) ) {
				return;
			}

			$headers = isset( $this->body['headers'] ) ? (array) $this->body['headers'] : array();

			$headers[ $name ] = sanitize_text_field( $value );

			$this->set_body_param(
				array(
					'headers' => $headers,
				)
			);
		}

		/**
		 * Icegram accepts an array of files content in body, so we will include all files and send.
		 * Doesn't handle exceeding the limits etc, as this is done and reported by Icegram API.
		 *
		 * @param array $attachments
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
					'content'     => base64_encode( $file ), // string, 1 character.
					'type'        => $filetype, // string, no ;, no CRLF.
					'filename'    => empty( $attachment_name ) ? 'file-' . wp_hash( microtime() ) . '.' . $filetype : trim( $attachment_name ), // required string, no CRLF.
					'disposition' => 'attachment', // either inline or attachment.
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
		 * @since 5.6.0
		 */
		public function set_tracking_options() {
			
			$tracking_settings = array(
				'open_tracking' => array(
					'enable' => false,
				),
				'click_tracking' => array(
					'enable'      => false,
					'enable_text' => false // Disable tracking for plain/text links
				),
			);
			
			$this->set_body_param(
				array(
					'tracking_settings' => $tracking_settings,
				)
			);
		}

		public function enable_sandbox_mode() {
			$this->set_body_param(
				array(
					'mail_settings' => array(
						'sandbox_mode' => array(
							'enable' => false,
						)
					),
				)
			);
		}

		public function set_ess_metadata() {

			$plan                 = ES_Service_Email_Sending::get_plan();
			$premium_plans        = array( 'pro', 'max' );
			$is_premium_plan      = in_array( $plan, $premium_plans, true );
			$ess_metadata         = array();
			$add_ess_footer_image = true;
			$footer_image_url     = trailingslashit( ES_IMG_URL ) . 'ess-footer-image.png';

			if ( $is_premium_plan && ! ES_Service_Email_Sending::is_ess_branding_enabled() ) {
				$add_ess_footer_image = false;
			}
			
			if ( $add_ess_footer_image ) {
				$ess_metadata['footer'] = array(
					'image_url' => $footer_image_url,
				);
			}

			if ( ! empty( $ess_metadata ) ) {
				$this->set_body_param(
					array(
						'ess_metadata' => $ess_metadata
					)
				);
			}

		}

		/**
		 * Send email using Icegram API
		 * 
		 * @since 5.6.0
		 */
		public function send_email() {

			ES()->logger->info( 'Start Sending Email Using Icegram', $this->logger_context );
			
			$params = array_merge_recursive( $this->get_default_params(), array(
				'headers' => $this->get_headers(),
				'body'    => $this->get_body(),
			));
			
			$response = wp_remote_post( $this->api_url, $params );
			if ( ! is_wp_error( $response ) ) {
				$body = ! empty( $response['body'] ) && ES_Common::is_valid_json( $response['body'] ) ? json_decode( $response['body'], true ) : '';
				if ( ! empty( $body ) ) {
					$status = ! empty( $body['status'] ) ? $body['status'] : 'error';
					if ( 'success' === $status ) {
						ES()->logger->info( 'Email Sent Successfully Using Icegram', $this->logger_context );
						return $this->do_response( 'success' );
					} else {
						if ( ! empty( $body['message'] ) ) {
							$error_message = $body['message'];
						} else {
							$error_message = __( 'An unknown error has occured. Please try again later.', 'email-subscribers' );
						}
					}
					return $this->do_response( 'error', $error_message );
				} else {
					return $this->do_response( 'error', wp_remote_retrieve_response_message( $response ) );
				}
			} else {
				$error_message = $response->get_error_message();
				return $this->do_response( 'error', $error_message );
			}

			ES()->logger->info( 'Email Sent Successfully Using Icegram', $this->logger_context );

			return $this->do_response( 'success' );
		}

		/**
		 * Check if the batch limit has been reached or not
		 *
		 * @return boolean
		 *
		 * @since 5.6.0
		 */
		public function is_batch_limit_reached() {
			return $this->current_batch_size >= $this->batch_limit;
		}
	}

}
