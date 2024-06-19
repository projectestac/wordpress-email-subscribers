<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Mailer' ) ) {
	/**
	 * Class ES_Mailer_New
	 *
	 * @since 4.0.0
	 * @since 4.3.2 Modified structure. Made it OOP based
	 */
	class ES_Mailer {
		/**
		 * Store Link data
		 *
		 * @since 4.3.2
		 * @var array
		 */
		public $link_data = array();

		/**
		 * Is limits set?
		 *
		 * @since 4.3.2
		 * @var bool
		 */
		public $limits_set = false;

		/**
		 * Max execution time
		 *
		 * @since 4.3.2
		 * @var int
		 */
		public $time_limit = 0;

		/**
		 * Start time of email sending
		 *
		 * @since 4.3.2
		 * @var int
		 */
		public $time_start = 0;

		/**
		 * Maximum email send count
		 *
		 * @since 4.3.2
		 * @var int
		 */
		public $email_limit = 0;

		/**
		 * Keep map of email => id data
		 *
		 * @since 4.3.2
		 * @var array
		 */
		public $email_id_map = array();

		/**
		 * Need to add unsubscribe link ?
		 *
		 * @since 4.3.2
		 * @var bool
		 */
		public $add_unsubscribe_link = true;

		/**
		 * Need to add tracking pixel ?
		 *
		 * @since 4.3.2
		 * @var bool
		 */
		public $can_track_open_clicks = true;

		/**
		 * Added Logger Context
		 *
		 * @since 4.3.2
		 * @var array
		 */
		public $logger_context = array(
			'source' => 'ig_es_mailer',
		);

		/**
		 * Mailer setting
		 *
		 * @since 4.3.2
		 * @var object|ES_Base_Mailer
		 */
		public $mailer;
		
		/**
		 * Default mailer to be used When ESS limit is reached
		 *
		 * @since 4.3.2
		 * @var object|ES_Base_Mailer
		 */
		public $default_mailer;

		/**
		 * ES_Mailer constructor.
		 *
		 * @since 4.3.2
		 */
		public function __construct() {
			$this->set_mailer();
		}

		/**
		 * Check whether max execution time reaches to it's maximum or
		 * reached email quota
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function limits_exceeded() {

			if ( ! $this->limits_set ) {

				$cron_interval = ES()->cron->get_cron_interval();
				@set_time_limit( $cron_interval );

				// Set 95% of max_execution_time as a max limit. We can reduce it as well
				$max_time = (int) ( @ini_get( 'max_execution_time' ) * 0.95 );
				if ( 0 == $max_time || $max_time > $cron_interval ) {
					$max_time = (int) ( $cron_interval * 0.95 );
				}

				$this->time_limit = $this->time_start + $max_time;

				$this->email_limit = $this->get_total_emails_send_now();

				// We are doing heavy lifting..allocate more memory
				if ( function_exists( 'memory_get_usage' ) && ( (int) @ini_get( 'memory_limit' ) < 128 ) ) {

					// Add filter to increase memory limit
					add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

					wp_raise_memory_limit( 'ig_es' );

					// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
					remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
				}

				$this->limits_set = true;
			}

			if ( time() > $this->time_limit ) {
				return true;
			}

			/**
			 * Check if memory limit is exceeded
			 *
			 * For mailers supporting batch APIs, since we need to prepare and store subscriber's email data before dispatching it
			 * memory limit can be reached in that case.
			*/
			if ( IG_ES_Background_Process_Helper::memory_exceeded() ) {
				return true;
			}

			if ( $this->email_limit <= 0 ) {
				return true;
			}

			return false;
		}

		/**
		 * Send Sign up Notifications to admins
		 *
		 * @param $data
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function send_add_new_contact_notification_to_admins( $data ) {

			if ( ! $this->can_send_add_new_contact_notification() ) {
				return false;
			}

			$admin_emails = $this->get_admin_emails();

			if ( ! empty( $admin_emails ) && is_array( $admin_emails ) && count( $admin_emails ) > 0 ) {

				$subject = $this->get_admin_new_contact_email_subject();
				$subject = $this->replace_admin_notification_merge_tags( $data, $subject );

				$content = $this->get_admin_new_contact_email_content();
				$content = $this->replace_admin_notification_merge_tags( $data, $content );

				$this->add_unsubscribe_link = false;
				$this->can_track_open_clicks   = false;
				$this->send( $subject, $content, $admin_emails, $data );

				return true;
			}

			return false;
		}


		/**
		 * Get new contact email subject
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_admin_new_contact_email_subject() {
			return stripslashes( get_option( 'ig_es_admin_new_contact_email_subject', '' ) );
		}

		/**
		 * Get new contact email content
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_admin_new_contact_email_content() {
			return wpautop( stripslashes( get_option( 'ig_es_admin_new_contact_email_content', '' ) ) );
		}

		/**
		 * Can send add new contact to admin?
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function can_send_add_new_contact_notification() {
			$ig_es_notify_admin = get_option( 'ig_es_notify_admin', 'no' );

			if ( 'yes' === $ig_es_notify_admin ) {
				return true;
			}

			return false;
		}

		/**
		 * Send Double Optin Email
		 *
		 * @param $emails
		 * @param array  $merge_tags
		 *
		 * @since 4.3.2
		 */
		public function send_double_optin_email( $emails, $merge_tags = array() ) {

			$subject = $this->get_confirmation_email_subject();
			$content = $this->get_confirmation_email_content();

			if ( empty( $subject ) || empty( $content ) ) {
				return false;
			}

			$content = str_replace( '{{LINK}}', '{{SUBSCRIBE-LINK}}', $content );

			$this->add_unsubscribe_link = false;
			$this->can_track_open_clicks   = false;

			return $this->send( $subject, $content, $emails, $merge_tags );
		}

		/**
		 * Get Confirmation Email Content
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_confirmation_email_content() {
			return wpautop( stripslashes( get_option( 'ig_es_confirmation_mail_content', '' ) ) );
		}

		/**
		 * Get Confirmation Email Subject
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 *
		 * @modify 4.3.12
		 */
		public function get_confirmation_email_subject() {
			$subject = stripslashes( get_option( 'ig_es_confirmation_mail_subject', '' ) );

			if ( empty( $subject ) ) {
				$subject = __( 'Thanks!', 'email-subscribers' );
			}

			return $subject;
		}

		/**
		 * Send Cron Admin Emails
		 *
		 * @param string $notification_guid
		 *
		 * @since 4.3.2
		 */
		public function send_cron_admin_email( $notification_guid = '' ) {

			if ( ! $this->can_send_cron_admin_email() ) {
				return;
			}

			$admin_emails = $this->get_admin_emails();

			if ( ! empty( $admin_emails ) && ! empty( $notification_guid ) && is_array( $admin_emails ) && count( $admin_emails ) > 0 ) {

				$notification = ES_DB_Mailing_Queue::get_notification_by_hash( $notification_guid );

				$subject = $this->get_cron_admin_email_subject();
				$content = $this->get_cron_admin_email_content();

				if ( ! empty( $content ) && isset( $notification['subject'] ) ) {

					$subject = str_replace( '{{SUBJECT}}', $notification['subject'], $subject );

					$email_count     = $notification['count'];
					$post_subject    = $notification['subject'];
					$cron_date       = gmdate( 'Y-m-d H:i:s' );
					$cron_local_date = get_date_from_gmt( $cron_date ); // Convert from GMT to local date/time based on WordPress time zone setting.
					$cron_date       = ES_Common::convert_date_to_wp_date( $cron_local_date ); // Get formatted date from WordPress date/time settings.

					$content = str_replace( '{{DATE}}', $cron_date, $content );
					$content = str_replace( '{{COUNT}}', $email_count, $content );
					$content = str_replace( '{{SUBJECT}}', $post_subject, $content );

					$this->add_unsubscribe_link = false;
					$this->can_track_open_clicks   = false;

					$this->send( $subject, $content, $admin_emails );
				}
			}
		}

		/**
		 * Get cron admin email subject
		 *
		 * @return mixed|void
		 *
		 * @since 4.3.2
		 */
		public function get_cron_admin_email_subject() {
			return get_option( 'ig_es_cron_admin_email_subject', __( 'Campaign Sent!', 'email-subscribers' ) );
		}

		/**
		 * Get cron admin email content
		 *
		 * @return mixed|void
		 *
		 * @since 4.3.2
		 */
		public function get_cron_admin_email_content() {
			return wpautop( get_option( 'ig_es_cron_admin_email', '' ) );
		}

		/**
		 * Can send cron admin email?
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function can_send_cron_admin_email() {

			$notify_admin = get_option( 'ig_es_enable_cron_admin_email', 'yes' );

			if ( 'yes' === $notify_admin ) {
				return true;
			}

			return false;
		}

		/**
		 * Get admin emails
		 *
		 * @return array
		 *
		 * @since 4.3.2
		 */
		public function get_admin_emails() {

			$admin_email_addresses = get_option( 'ig_es_admin_emails', '' );

			return explode( ',', $admin_email_addresses );
		}

		/**
		 * Send Welcome email after subscription
		 *
		 * @param $email
		 * @param $data
		 *
		 * @since 4.1.13
		 */
		public function send_welcome_email( $email, $data = array() ) {

			// Prepare Welcome Email Subject
			$subject = $this->get_welcome_email_subject();

			// Prepare Welcome Email Content
			$content = $this->get_welcome_email_content();

			// Backward Compatibility...Earlier we used to use {{LINK}} for Unsubscribe link
			$content = str_replace( '{{LINK}}', '{{UNSUBSCRIBE-LINK}}', $content );

			// Don't add Unsubscribe link. It should be there in content
			$this->add_unsubscribe_link = false;
			$this->can_track_open_clicks   = false;
			// Send Email
			$this->send( $subject, $content, $email, $data );

		}

		/**
		 * Get Welcome Email Subject
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_welcome_email_subject() {
			return stripslashes( get_option( 'ig_es_welcome_email_subject', '' ) );
		}

		/**
		 * Get Welcome Email Message
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_welcome_email_content() {
			return wpautop( stripslashes( get_option( 'ig_es_welcome_email_content', '' ) ) );
		}

		/**
		 * Enable Welcome Email?
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function can_send_welcome_email() {
			// Enable Welcome Email?
			$enable_welcome_email = get_option( 'ig_es_enable_welcome_email', 'no' );

			if ( 'yes' === $enable_welcome_email ) {
				return true;
			}

			return false;
		}

		/**
		 * Send Test Email
		 *
		 * @param string $email
		 * @param array  $merge_tags
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function send_test_email( $email = '', $subject = '', $content = '', $merge_tags = array() ) {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

			if ( empty( $email ) ) {
				return false;
			}

			if ( empty( $subject ) ) {
				$subject = $this->get_test_email_subject( $email );
			}

			if ( empty( $content ) ) {
				$content = $this->get_test_email_content();
			}

			// Disable unsubsribe link if it is not a campaign email.
			if ( empty( $merge_tags['campaign_id'] ) ) {
				$this->add_unsubscribe_link = false;
			}

			$this->can_track_open_clicks = false;
			return $this->send( $subject, $content, $email, $merge_tags );
		}

		/**
		 * Get Test Email Subject
		 *
		 * @param string $email
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_test_email_subject( $email = '' ) {
			/* translators: %s: Email address */
			return 'Icegram Express: ' . sprintf( esc_html__( 'Test email to %s', 'email-subscribers' ), $email );
		}

		/**
		 * Get test email content
		 *
		 * @return false|string
		 *
		 * @since 4.3.2
		 */
		public function get_test_email_content() {
			ob_start();
			$review_url = 'https://wordpress.org/support/plugin/email-subscribers/reviews/?filter=5';
			?>
			<html>
			<head></head>
			<body>
			<p><?php echo esc_html__( 'Congrats, test email was sent successfully!', 'email-subscribers' ); ?></p>
			<p><?php echo esc_html__( 'Thank you for trying out Icegram Express. We are on a mission to make the best Email Marketing Automation plugin for WordPress.', 'email-subscribers' ); ?></p>
			<!-- Start-IG-Code -->
			<p>
			<?php
				/* translators: 1: <a> 2: </a> */
				echo sprintf( esc_html__( 'If you find this plugin useful, please consider giving us %1$s5 stars review%2$s on WordPress!', 'email-subscribers' ), '<a href="' . esc_url( $review_url ) . '">', '</a>' );
			?>
			</p>
			<p>Nirav Mehta</p>
			<p>Founder, <a href="https://www.icegram.com/">Icegram</a></p>
			<!-- End-IG-Code -->
			</body>
			</html>

			<?php
			$content = ob_get_clean();

			return $content;
		}

		/**
		 * Send Email
		 *
		 * @param $subject
		 * @param $content
		 * @param array   $emails
		 * @param array   $merge_tags
		 * @param bool    $nl2br
		 *
		 * @return mixed
		 *
		 * @since 4.3.2
		 * 
		 * @modify 5.6.4
		 */
		public function send( $subject, $content, $emails = array(), $merge_tags = array(), $nl2br = false ) {

			ignore_user_abort( true );

			$this->time_start = time();

			if ( ES_Service_Email_Sending::using_icegram_mailer() ) {
				$remaining_limit = ES_Service_Email_Sending::get_remaining_limit();
				if ( $remaining_limit > 0 ) {
					$this->mailer->remaining_limit = $remaining_limit;
				} else {
					$this->switch_to_default_mailer();
				}
			}
			$message_id       = ! empty( $merge_tags['message_id'] ) ? $merge_tags['message_id'] : 0;
			$campaign_id      = ! empty( $merge_tags['campaign_id'] ) ? $merge_tags['campaign_id'] : 0;
			$attachments      = ! empty( $merge_tags['attachments'] ) ? $merge_tags['attachments'] : array();
			
			$sender_data   = array();
			$campaign_type = '';
			if ( ! empty( $campaign_id ) ) {
				$campaign = ES()->campaigns_db->get( $campaign_id );
				if ( ! empty( $campaign ) ) {
					$campaign_type = $campaign['type'];
					if ( 'newsletter' === $campaign_type ) {
						$from_name                     = ! empty( $campaign['from_name'] ) ? $campaign['from_name'] : '';
						$from_email                    = ! empty( $campaign['from_email'] ) ? $campaign['from_email'] : '';
						$reply_to_email                = ! empty( $campaign['reply_to_email'] ) ? $campaign['reply_to_email'] : '';
						$sender_data['from_name']      = $from_name;
						$sender_data['from_email']     = $from_email;
						$sender_data['reply_to_email'] = $reply_to_email;
					}

					$campaign_meta = maybe_unserialize( $campaign['meta'] );
					
					if ( ! empty( $campaign_meta['preheader'] ) ) {
						$content = '<span class="preheader" style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0;">' . $campaign_meta['preheader'] . '</span>' . $content;
					} elseif ( ! empty( $merge_tags['preheader'] ) ) {
						$content = '<span class="preheader" style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0;">' . $merge_tags['preheader'] . '</span>' . $content;
					}

					if ( ! empty( $campaign_meta['attachments'] ) ) {
						$sender_data['attachments'] = array();
						$attachments                = $campaign_meta['attachments'];
					}
				}
			}

			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment_id ) {
					if ( ! $attachment_id ) {
						continue;
					}
					$attached_file = get_attached_file( $attachment_id );
					if ( ! is_file( $attached_file ) ) {
						continue;
					}
					$sender_data['attachments'][ basename( $attached_file ) ] = $attached_file;
				}
			}

			// If unsubscribe link placeholder already present in the email then don't add it from our end.
			if ( false !== strpos( $content, '{{UNSUBSCRIBE-LINK}}' ) ) {
				$this->add_unsubscribe_link = false;
			}

			$subject = $this->prepare_subject( $subject );

			$content = $this->prepare_content( $content, $merge_tags, $nl2br );

			$response = array();

			if ( ! is_array( $emails ) ) {
				$emails = array( $emails );
			}

			// When email in not sent through a campaign e.g. Test emails.
			if ( '' === $campaign_type ) {
				$this->email_id_map = ES()->contacts_db->get_email_id_map( $emails );
			} else {
				/**
				 * In case of sequence message campaign, fetch contact-email mapping from contacts table, since sending_queue table isn't used to store sequence campaign data.
				 * TODO: Please check need for using sending_queue table for other campaigns type. If it is not required, then we can remove it for other campaigns types as well.
				 */
				if ( in_array( $campaign_type, array( 'sequence_message', 'workflow_email' ), true ) ) {
					$this->email_id_map = ES()->contacts_db->get_email_id_map( $emails );
				} else {
					// If the campaign isn't a sequence message, then we can fetch contact-email mapping data from sending_queue table
					$this->email_id_map = ES_DB_Sending_Queue::get_emails_id_map_by_campaign( $campaign_id, $message_id, $emails );
				}
			}

			$total_recipients = count( $emails );

			$can_use_batch_api = $total_recipients > 1 && $this->mailer->support_batch_sending;
			$can_use_batch_api = apply_filters( 'ig_es_can_use_batch_api_' . $this->mailer->slug, $can_use_batch_api, $total_recipients, $sender_data );

			// In case mailser supporting batch APIs, we are setting API credentials, sender data before running the email loop
			// For normal mailers, we are doing this inside the loop
			if ( $can_use_batch_api ) {

				$mailer_data_set = $this->mailer->set_mailer_data();

				// Error setting up mailer?
				if ( is_wp_error( $mailer_data_set ) ) {
					$response['status']  = 'ERROR';
					$response['message'] = $mailer_data_set->get_error_messages();

					return $response;
				}

				if ( 'multiple' === $this->mailer->batch_sending_mode ) {

					$this->link_data = array(
						'message_id'  => $message_id,
						'campaign_id' => $campaign_id,
					);

					// If sender name is not passed then fetch it from ES settings.
					if ( ! empty( $sender_data['from_name'] ) ) {
						$sender_name = $sender_data['from_name'];
					} else {
						$sender_name = $this->get_from_name();
					}

					// If sender email is not passed then fetch it from ES settings.
					if ( ! empty( $sender_data['from_email'] ) ) {
						$sender_email = $sender_data['from_email'];
					} else {
						$sender_email = $this->get_from_email();
					}

					// If reply to email is not passed then fetch it from ES settings.
					if ( ! empty( $sender_data['reply_to_email'] ) ) {
						$reply_to_email = $sender_data['reply_to_email'];
					} elseif ( empty( $reply_to_email ) ) {
						$reply_to_email = $this->get_from_email();
					}

					$charset = get_bloginfo( 'charset' );
					$subject = html_entity_decode( $subject, ENT_QUOTES, $charset );
					$content = preg_replace( '/data-json=".*?"/is', '', $content );
					$content = preg_replace( '/  +/s', ' ', $content );

					if ( $this->add_unsubscribe_link ) {
						$unsubscribe_message = get_option( 'ig_es_unsubscribe_link_content', '' );
						$unsubscribe_message = stripslashes( $unsubscribe_message );
						if ( false === strpos( $content, '</html>' ) ) {
							$content = $content . $unsubscribe_message;
						} else {
							if ( strpos( $content, '</body>' ) > 0 ) {
								// Insert unsubscribe message inside body tag.
								$content = str_replace( '</body>', $unsubscribe_message . '</body>', $content );
							} else {
								// Insert unsubscribe message inside html tag.
								$content = str_replace( '</html>', $unsubscribe_message . '</html>', $content );
							}
						}
					}

					$subject = $this->replace_global_tags( $subject );
					$subject = $this->mailer->convert_es_tags_to_mailer_tags( $subject );

					$content         = $this->replace_global_tags( $content );
					$content         = $this->mailer->convert_es_tags_to_mailer_tags( $content );
					$variable_string = $this->mailer->get_variable_string( 'contact_link_hash' );
					$content         = $this->add_links_variables( $content, $campaign_id, $message_id, $variable_string );

					if ( $this->can_track_open() ) {
						$tracking_pixel_variable_name = $this->mailer->get_variable_prefix() . $this->mailer->get_variable_string( 'tracking_pixel_url' ) . $this->mailer->get_variable_suffix();
						$tracking_image               = '<img src="' . $tracking_pixel_variable_name . '" width="1" height="1" alt=""/>';
						if ( false === strpos( $content, '</html>' ) ) {
							$content = $content . $tracking_image;
						} else {
							// Insert tracking pixel inside body tag.
							if ( strpos( $content, '</body>' ) > 0 ) {
								$content = str_replace( '</body>', $tracking_image . '</body>', $content );
							} else {
								// Insert tracking pixel inside html tag.
								$content = str_replace( '</html>', $tracking_image . '</html>', $content );
							}
						}
					}

					if ( $this->unsubscribe_headers_enabled() && is_callable( array( $this->mailer, 'set_list_unsubscribe_header' ) ) ) {
						$this->mailer->set_list_unsubscribe_header();
					}

					$email_data = array(
						'sender_email'   => $sender_email,
						'sender_name'    => $sender_name,
						'reply_to_email' => $reply_to_email,
						'subject'        => $subject,
						'content'        => $content,
					);
					$this->mailer->set_email_data( $email_data );
				}
			}

			foreach ( $emails as $email_counter => $email ) {
				
				// Break if we have reached Icegram mailer limit set by ESS
				if ( ES_Service_Email_Sending::using_icegram_mailer()  && 0 === $this->mailer->remaining_limit ) {
					break;
				}

				// Clean it.
				$email = trim( $email );

				$response['status'] = 'SUCCESS';

				// Don't find contact_id?
				$contact_id = ! empty( $this->email_id_map[ $email ] ) ? $this->email_id_map[ $email ] : 0;

				$merge_tags['contact_id'] = $contact_id;

				$merge_tags = array_merge( $merge_tags, $this->get_contact_merge_tags( $contact_id ) );

				$this->link_data = array(
					'message_id'  => $message_id,
					'campaign_id' => $campaign_id,
					'contact_id'  => $contact_id,
					'email'       => $email,
					'guid'        => ig_es_get_data( $merge_tags, 'hash', '' ),
				);

				if ( $can_use_batch_api ) {

					if ( ! $this->mailer->is_batch_limit_reached() ) {
						if ( 'single' === $this->mailer->batch_sending_mode ) {
							$message = $this->build_message( $subject, $content, $email, $merge_tags, $nl2br, $sender_data );
							$this->mailer->add_into_batch( $email, $merge_tags, $message );
						} else {
							$this->mailer->add_into_batch( $email, $merge_tags );
						}
					}

					if ( ( $email_counter + 1 ) >= $total_recipients || $this->mailer->is_batch_limit_reached() ) {
						$contact_ids = array_column( $this->mailer->batch_data, 'contact_id' );
						if ( ! empty( $contact_ids ) ) {
							do_action( 'ig_es_before_message_send', $contact_ids, $campaign_id, $message_id );
						}
						if ( 'multiple' === $this->mailer->batch_sending_mode ) {
							if ( ! empty( $sender_data['attachments'] ) ) {
								$this->mailer->set_attachments( $sender_data['attachments'] );
							}
						}

						$send_response = $this->mailer->send_batch();

						$send_status = ! is_wp_error( $send_response ) ? 'sent' : 'failed';
						
						if ( ! empty( $contact_ids ) ) {
							do_action( 'ig_es_message_' . $send_status, $contact_ids, $campaign_id, $message_id );
						}

						$this->email_limit -= $this->mailer->current_batch_size;
						$this->mailer->clear_batch();
						$this->mailer->handle_throttling();

						// Error Sending Email?
						if ( 'failed' === $send_status ) {
							$response['status']  = 'ERROR';
							$response['message'] = $send_response->get_error_messages();
							// TODO: Log somewhere
						}
					}
				} else {
					do_action( 'ig_es_before_message_send', $contact_id, $campaign_id, $message_id );

					$message = $this->build_message( $subject, $content, $email, $merge_tags, $nl2br, $sender_data );

					// object | WP_Error
					$send_response = $this->mailer->send( $message );
					$send_status   = ! is_wp_error( $send_response ) ? 'sent' : 'failed';

					do_action( 'ig_es_message_' . $send_status, $contact_id, $campaign_id, $message_id );

					// Error Sending Email?
					if ( is_wp_error( $send_response ) ) {
						$response['status']  = 'ERROR';
						$response['message'] = $send_response->get_error_messages();
					}

					// Reduce Email Sending Limit for this hour
					$this->email_limit --;

				}

				if ( $this->limits_exceeded() ) {

					if ( $this->mailer->support_batch_sending && ! empty( $this->mailer->batch_data ) ) {
						if ( 'multiple' === $this->mailer->batch_sending_mode ) {
							if ( ! empty( $sender_data['attachments'] ) ) {
								$this->mailer->set_attachments( $sender_data['attachments'] );
							}
						}

						$send_response = $this->mailer->send_batch();
						$send_status   = ! is_wp_error( $send_response ) ? 'sent' : 'failed';
						
						if ( ! empty( $contact_ids ) ) {
							do_action( 'ig_es_message_' . $send_status, $contact_ids, $campaign_id, $message_id );
						}

						$this->email_limit -= $this->mailer->current_batch_size;
						$this->mailer->clear_batch();
						$this->mailer->handle_throttling();

						// Error Sending Email?
						if ( is_wp_error( $send_response ) ) {
							$response['status']  = 'ERROR';
							$response['message'] = $send_response->get_error_messages();
							// TODO: Log somewhere
						}
					}
					break;
				}
			}

			if ( $can_use_batch_api ) {
				$this->mailer->clear_email_data();
			}

			return $response;

		}

		/**
		 * Prepare ES_Message object
		 *
		 * @param $subject
		 * @param $body
		 * @param $email
		 * @param array   $merge_tags
		 * @param array   $sender_data
		 *
		 * @return ES_Message
		 *
		 * @since 4.3.2
		 *
		 * @since 4.4.7 Added sender data.
		 */
		public function build_message( $subject, $body, $email, $merge_tags = array(), $nl2br = false, $sender_data = array() ) {

			$message = new ES_Message();

			$sender_name    = '';
			$sender_email   = '';
			$reply_to_email = '';
			$attachments    = array();
			// If sender data is passed .i.g. set in the campaign then use it.
			if ( ! empty( $sender_data ) ) {
				$sender_name    = ! empty( $sender_data['from_name'] ) ? $sender_data['from_name'] : '';
				$sender_email   = ! empty( $sender_data['from_email'] ) ? $sender_data['from_email'] : '';
				$reply_to_email = ! empty( $sender_data['reply_to_email'] ) ? $sender_data['reply_to_email'] : '';
				$attachments    = ! empty( $sender_data['attachments'] ) ? $sender_data['attachments'] : array();
			}

			// If sender name is not passed then fetch it from ES settings.
			if ( empty( $sender_name ) ) {
				$sender_name = $this->get_from_name();
			}

			// If sender email is not passed then fetch it from ES settings.
			if ( empty( $sender_email ) ) {
				$sender_email = $this->get_from_email();
			}

			// If reply to email is not passed then fetch it from ES settings.
			if ( empty( $reply_to_email ) ) {
				$reply_to_email = $this->get_from_email();
			}

			$charset = get_bloginfo( 'charset' );

			$subject = html_entity_decode( $subject, ENT_QUOTES, $charset );

			$message->from           = $sender_email;
			$message->from_name      = $sender_name;
			$message->to             = $email;
			$message->subject        = $subject;
			$message->body           = $body;
			$message->attachments    = $attachments;
			$message->reply_to_email = $reply_to_email;
			$message->charset        = $charset;

			$headers = array(
				"From: \"$message->from_name\" <$message->from>",
				'Return-Path: <' . $message->from . '>',
				'Reply-To: <' . $message->reply_to_email . '>',
				'Content-Type: text/html; charset="' . $message->charset . '"',
			);

			$list_unsub_header = $this->get_list_unsubscribe_header( $email );

			if ( ! empty( $list_unsub_header ) ) {
				$headers[] = 'List-Unsubscribe: ' . $list_unsub_header;
				$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
			}

			// $email       = ig_es_get_data( $merge_tags, 'email', '' );
			$hash        = ig_es_get_data( $merge_tags, 'hash', '' );
			$campaign_id = ig_es_get_data( $merge_tags, 'campaign_id', 0 );
			$contact_id  = ig_es_get_data( $merge_tags, 'contact_id', 0 );
			$message_id  = ig_es_get_data( $merge_tags, 'message_id', 0 );

			$link_data = array(
				'message_id'  => $message_id,
				'campaign_id' => $campaign_id,
				'contact_id'  => $contact_id,
				'email'       => $email,
				'guid'        => $hash,
			);
			//Apply_filter is created in version 4.8.3 for bounce handling
			$message->headers = apply_filters( 'ig_es_mailer_email_headers', $headers, $link_data );

			$message->body = preg_replace( '/data-json=".*?"/is', '', $message->body );
			$message->body = preg_replace( '/  +/s', ' ', $message->body );

			$message->subject = $this->replace_merge_tags( $message->subject, $merge_tags );

			// Can Track Clicks? Replace Links
			$message->body = $this->replace_links( $message->body, $link_data );

			// Unsubscribe Text
			$unsubscribe_message = $this->get_unsubscribe_text();

			// Can Track Email Open? Add pixel.
			$email_tracking_image = $this->get_tracking_pixel();


			if ( false === strpos( $message->body, '<html' ) ) {
				$message->body = $message->body . $unsubscribe_message . $email_tracking_image;
			} else {
				// If content is HTML then we need to place unsubscribe message and tracking image inside body tag.
				$message->body = str_replace( '</body>', $unsubscribe_message . $email_tracking_image . '</body>', $message->body );
			}


			if ( $nl2br ) {
				$message->body = nl2br( $message->body );
			}

			$message->body = $this->replace_merge_tags( $message->body, $merge_tags );

			$message->body_text = $this->convert_to_text( $message->body );

			/*
			 * TODO: Enable after Fixing preheader issue.
			$campaign_id = ! empty( $merge_tags['campaign_id'] ) ? $merge_tags['campaign_id'] : 0;
			$message->body = $this->set_pre_header_text( $message->body, $campaign_id );
			*/

			return $message;
		}

		/**
		 * Set Pre header text
		 *
		 * @param $content
		 * @param int     $campaign_id
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function set_pre_header_text( $content, $campaign_id = 0 ) {

			if ( ! empty( $campaign_id ) ) {

				$meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );

				$pre_header_text = ! empty( $meta['pre_header'] ) ? $meta['pre_header'] : '';

				if ( ! empty( $pre_header_text ) ) {
					$content = '<span class="es_preheader" style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0;">' . $pre_header_text . '</span>' . $content;
				}
			}

			return $content;
		}

		/**
		 * Prepare Subject
		 *
		 * @param $subject
		 *
		 * @return mixed
		 *
		 * @since 4.3.2
		 */
		public function prepare_subject( $subject ) {
			return $subject;
		}

		/**
		 * Prepare Content
		 *
		 * @param $content
		 *
		 * @return string|string[]|null
		 *
		 * @since 4.3.2
		 */
		public function prepare_content( $content, $merge_tags = array(), $nl2br = false ) {
			// Convert text equivalent of smilies to images.
			$content = convert_smilies( wptexturize( $content ) );

			$content = ES_Common::handle_oembed_content( $content );

			// Replaces double line-breaks with paragraph elements.
			// $content = wpautop( $content );

			// Have shortcode? Execute it.
			$content = do_shortcode( shortcode_unautop( $content ) );

			// Format Templates.
			$data['content']     = $content;
			$campaign_id         = ! empty( $merge_tags['campaign_id'] ) ? $merge_tags['campaign_id'] : 0;
			$message_id          = ! empty( $merge_tags['message_id'] ) ? $merge_tags['message_id'] : 0;
			$data['tmpl_id']     = ! empty( $campaign_id ) ? ES()->campaigns_db->get_template_id_by_campaign( $campaign_id ) : 0;
			$data['campaign_id'] = $campaign_id;
			$data['message_id']  = $message_id;

			$data = apply_filters( 'es_after_process_template_body', $data );

			$content = $data['content'];

			return $content;
		}

		/**
		 * Get contact merge tags
		 *
		 * @param int $contact_id
		 *
		 * @return array
		 *
		 * @since 4.3.2
		 */
		public function get_contact_merge_tags( $contact_id = 0 ) {
			$merge_tags = array();

			if ( 0 != $contact_id ) {
				$contact_details = ES()->contacts_db->get_details_by_ids( array( $contact_id ) );
				if ( is_array( $contact_details ) ) {

					$merge_tags         = array_shift( $contact_details );
					$merge_tags['name'] = ES_Common::prepare_name_from_first_name_last_name( $merge_tags['first_name'], $merge_tags['last_name'] );
				}
			}

			return $merge_tags;
		}

		/**
		 * Replace Merge Tags
		 *
		 * @param string $content
		 * @param array  $merge_tags
		 *
		 * @return mixed|string
		 *
		 * @since 4.3.2
		 */
		public function replace_merge_tags( $content = '', $merge_tags = array() ) {

			$blog_name      = get_option( 'blogname' );
			$total_contacts = ES()->contacts_db->get_total_contacts();
			$site_url       = home_url( '/' );

			$name          = ig_es_get_data( $merge_tags, 'name', '' );
			$first_name    = ig_es_get_data( $merge_tags, 'first_name', '' );
			$last_name     = ig_es_get_data( $merge_tags, 'last_name', '' );
			$list_name     = ig_es_get_data( $merge_tags, 'list_name', '' );
			$hash          = ig_es_get_data( $merge_tags, 'hash', '' );
			$email         = ig_es_get_data( $merge_tags, 'email', '' );
			$contact_id    = ig_es_get_data( $merge_tags, 'contact_id', 0 );
			$campaign_id   = ig_es_get_data( $merge_tags, 'campaign_id', 0 );
			$message_id    = ig_es_get_data( $merge_tags, 'message_id', 0 );
			$list_ids      = ig_es_get_data( $merge_tags, 'list_ids', '' );
			
			$link_data = array(
				'message_id'  => $message_id,
				'campaign_id' => $campaign_id,
				'contact_id'  => $contact_id,
				'email'       => $email,
				'guid'        => $hash,
				'list_ids'    => $list_ids,
			);

			$this->link_data = $link_data;

			$subscribe_link   = $this->get_subscribe_link( $link_data );
			$unsubscribe_link = $this->get_unsubscribe_link( $link_data );

			$content = ES_Common::replace_keywords_with_fallback( $content, array(
				'FIRSTNAME' => $first_name,
				'NAME'      => $name,
				'LASTNAME'  => $last_name,
				'EMAIL'     => $email
			) );

			$custom_field_values = array();
			foreach ( $merge_tags as $merge_tag_key => $merge_tag_value ) {
				if ( false !== strpos( $merge_tag_key, 'cf_' ) ) {
					$merge_tag_key_parts = explode( '_', $merge_tag_key );
					$merge_tag_key       = $merge_tag_key_parts[2];
					if ( is_null( $merge_tag_value ) ) {
						$merge_tag_value = '';
					}
					$custom_field_values[ 'subscriber.' . $merge_tag_key ] = $merge_tag_value;
				}
			}

			$subscriber_tags_values = array(
				'subscriber.first_name' => $first_name,
				'subscriber.name'       => $name,
				'subscriber.last_name'  => $last_name,
				'subscriber.email'      => $email
			);

			$subscriber_tags_values = array_merge( $subscriber_tags_values, $custom_field_values );

			$content = ES_Common::replace_keywords_with_fallback( $content, $subscriber_tags_values );

			// TODO: This is a quick workaround to handle <a href="{{LINK}}?utm_source=abc" >
			// TODO: Implement some good solution

			$content = str_replace( '{{LINK}}?', '{{LINK}}&', $content );
			$content = str_replace( '{{LINK}}', $subscribe_link, $content );

			$content = str_replace( '{{SUBSCRIBE-LINK}}?', '{{SUBSCRIBE-LINK}}&', $content );
			$content = str_replace( '{{SUBSCRIBE-LINK}}', $subscribe_link, $content );

			$content = str_replace( '{{UNSUBSCRIBE-LINK}}?', '{{UNSUBSCRIBE-LINK}}&', $content );
			$content = str_replace( '{{UNSUBSCRIBE-LINK}}', $unsubscribe_link, $content );

			$content = str_replace( '{{TOTAL-CONTACTS}}', $total_contacts, $content );
			$content = str_replace( '{{site.total_contacts}}', $total_contacts, $content );
			$content = str_replace( '{{GROUP}}', $list_name, $content );
			$content = str_replace( '{{LIST}}', $list_name, $content );
			$content = str_replace( '{{SITENAME}}', $blog_name, $content );
			$content = str_replace( '{{SITEURL}}', $site_url, $content );
			$content = str_replace( '{{site.name}}', $blog_name, $content );
			$content = str_replace( '{{site.url}}', $site_url, $content );

			return apply_filters( 'ig_es_message_content', $content, $merge_tags );
		}

		/**
		 * Replace global merge tags
		 *
		 * @param string $content
		 * @param array  $merge_tags
		 *
		 * @return mixed|string
		 *
		 * @since 4.7.0
		 */
		public function replace_global_tags( $content = '', $merge_tags = array() ) {

			$blog_name      = get_option( 'blogname' );
			$total_contacts = ES()->contacts_db->get_total_contacts();
			$site_url       = home_url( '/' );
			$list_name      = ig_es_get_data( $merge_tags, 'list_name', '' );

			$content = str_replace( '{{LINK}}?', '{{LINK}}&', $content );
			$content = str_replace( '{{LINK}}', '{{UNSUBSCRIBE-LINK}}', $content );
			$content = str_replace( '{{SUBSCRIBE-LINK}}?', '{{SUBSCRIBE-LINK}}&', $content );
			$content = str_replace( '{{UNSUBSCRIBE-LINK}}?', '{{UNSUBSCRIBE-LINK}}&', $content );
			$content = str_replace( '{{TOTAL-CONTACTS}}', $total_contacts, $content );
			$content = str_replace( '{{GROUP}}', $list_name, $content );
			$content = str_replace( '{{LIST}}', $list_name, $content );
			$content = str_replace( '{{SITENAME}}', $blog_name, $content );
			$content = str_replace( '{{SITEURL}}', $site_url, $content );
			$content = str_replace( '{{site.name}}', $blog_name, $content );
			$content = str_replace( '{{site.url}}', $site_url, $content );

			return $content;
		}

		/**
		 * Convert Html to text
		 *
		 * @param $html
		 * @param bool $links_only
		 *
		 * @return mixed|string|string[]|null
		 *
		 * @since 4.0.0
		 * @since 4.3.2
		 */
		public function convert_to_text( $html, $links_only = false ) {

			if ( $links_only ) {
				$links = '/< *a[^>]*href *= *"([^#]*)"[^>]*>(.*)< *\/ *a *>/Uis';
				$text  = preg_replace( $links, '${2} [${1}]', $html );
				$text  = str_replace( array( ' ', '&nbsp;' ), ' ', strip_tags( $text ) );
				$text  = @html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

				return trim( $text );

			} else {
				require_once ES_PLUGIN_DIR . 'lite/includes/libraries/class-es-html2text.php';
				$htmlconverter = new ES_Html2Text(
					$html,
					array(
						'width'    => 200,
						'do_links' => 'table',
					)
				);

				$text = trim( $htmlconverter->get_text() );
				$text = preg_replace( '/\s*$^\s*/mu', "\n\n", $text );
				$text = preg_replace( '/[ \t]+/u', ' ', $text );

				return $text;

			}
		}

		/**
		 * Replace links with tracking link
		 *
		 * @param $content
		 * @param $data
		 *
		 * @return string|string[]|null
		 *
		 * @since 4.2.4
		 * @since 4.3.2
		 */
		public function replace_links( $content = '', $link_data = array() ) {

			if ( $this->can_track_clicks() ) {

				if ( empty( $link_data ) ) {
					$link_data = $this->link_data;
				}

				$link_data['action'] = 'click';

				// get all links from the basecontent
				preg_match_all( '#<a\s+(?:[^>]*?\s+)?href=(\'|")?(https?[^\'"]+)(\'|")?#', $content, $links );

				$links = $links[2];

				if ( empty( $links ) ) {
					return $content;
				}

				$inserted_links = array();

				$campaign_id = ! empty( $link_data['campaign_id'] ) ? $link_data['campaign_id'] : 0;
				$message_id  = ! empty( $link_data['message_id'] ) ? $link_data['message_id'] : 0;
				$contact_id  = ! empty( $link_data['contact_id'] ) ? $link_data['contact_id'] : 0;

				foreach ( $links as $link ) {

					if ( ! isset( $inserted_links[ $link ] ) ) {
						$index = 0;
					} else {
						$index = $inserted_links[ $link ] + 1;
					}

					$inserted_links[ $link ] = $index;

					$result = ES()->links_db->get_link_by_campaign_id( $link, $campaign_id, $message_id, $index );

					if ( is_array( $result ) && count( $result ) > 0 ) {
						$hash = $result[0]['hash'];
					} else {

						$hash = ES_Common::generate_hash( 12 );

						$link_data = array(
							'link'        => $link,
							'message_id'  => $message_id,
							'campaign_id' => $campaign_id,
							'hash'        => $hash,
							'i'           => $index,
						);

						ES()->links_db->insert( $link_data );
					}

					$data = array(
						'action'     => 'click',
						'link_hash'  => $hash,
						'contact_id' => $contact_id,
					);

					$new_link = $this->prepare_link( $data );

					$link     = ' href="' . $link . '"';
					$new_link = ' href="' . $new_link . '"';
					$pos      = strpos( $content, $link );
					if ( false != $pos ) {
						$content = preg_replace( '/' . preg_quote( $link, '/' ) . '/', $new_link, $content, 1 );
					}
				}
			}

			return $content;
		}

		/**
		 * Replace links with variable string for given mailer
		 *
		 * @param $content
		 * @param $campaign_id
		 * @param $message_id
		 * @param $variable_string
		 *
		 * @return string|string[]|null
		 *
		 * @since 4.7.0
		 */
		public function add_links_variables( $content, $campaign_id, $message_id, $variable_string = '' ) {
			$this->mailer->links = array();
			if ( $this->can_track_clicks() ) {

				// get all links from the basecontent
				preg_match_all( '#<a\s+(?:[^>]*?\s+)?href=(\'|")?(https?[^\'"]+)(\'|")?#', $content, $links );

				$links = $links[2];

				if ( empty( $links ) ) {
					return $content;
				}

				$inserted_links  = array();
				$variable_string = $this->mailer->get_variable_prefix() . $variable_string . $this->mailer->get_variable_suffix();

				foreach ( $links as $link_index => $link ) {

					if ( ! isset( $inserted_links[ $link ] ) ) {
						$index = 0;
					} else {
						$index = $inserted_links[ $link ] + 1;
					}

					$inserted_links[ $link ] = $index;

					$result = ES()->links_db->get_link_by_campaign_id( $link, $campaign_id, $message_id, $index );

					if ( is_array( $result ) && count( $result ) > 0 ) {
						$hash = $result[0]['hash'];
					} else {

						$hash = ES_Common::generate_hash( 12 );

						$link_data = array(
							'link'        => $link,
							'message_id'  => $message_id,
							'campaign_id' => $campaign_id,
							'hash'        => $hash,
							'i'           => $index,
						);

						ES()->links_db->insert( $link_data );
					}

					if ( ! empty( $hash ) ) {

						$hash_data      = array(
							'action'     => 'click',
							'link_hash'  => $hash,
							'contact_id' => '0',
						);
						$new_link       = $this->prepare_link( $hash_data );
						$json_string    = '"contact_id":"0"}';
						$encoded_string = rtrim( base64_encode( $json_string ), '=' );
						// Here we are replacing base64 encoded string with variable string in new link i.e. '"contact_id":"0"}' with contact_link_hash
						$new_link = str_replace( $encoded_string, $variable_string, $new_link );

						$old_link = ' href="' . $link . '"';
						$new_link = ' href="' . $new_link . '"';
						$pos      = strpos( $content, $old_link );
						if ( false !== $pos ) {
							$content = preg_replace( '/' . preg_quote( $old_link, '/' ) . '/', $new_link, $content, 1 );
						}
					}
				}
			}

			return $content;
		}

		/**
		 * Get mailer specific link variables
		 *
		 * @param $contact_id
		 *
		 * @return array $link_variables
		 *
		 * @since 4.7.0
		 *
		 * @since 4.7.7 Returning only contact specific data
		 */
		public function get_link_variable( $contact_id ) {

			$link_variables = array();
			$json_string    = '"contact_id":"' . $contact_id . '"}';

			// Here we are providing value for contact_hash_link variable
			$new_link = rtrim( base64_encode( $json_string ), '=' );

			$link_variables['contact_link_hash'] = $new_link;

			return $link_variables;
		}

		/**
		 * Get tracking url
		 *
		 * @param $link_data
		 *
		 * @return string $tracking_pixel_url
		 *
		 * @since 4.7.0
		 */
		public function get_tracking_pixel_url( $link_data = array() ) {

			$tracking_pixel_url = '';

			if ( ! empty( $link_data ) && $this->can_track_open() ) {

				$link_data['action'] = 'open';

				$tracking_pixel_url = $this->prepare_link( $link_data );
			}

			return $tracking_pixel_url;
		}

		/**
		 * Get Sender Name
		 *
		 * @return mixed|string|void
		 *
		 * @since 4.3.2
		 */
		public function get_from_name() {

			$site_title = get_bloginfo();

			$from_name = get_option( 'ig_es_from_name', '' );

			$from_name = ! empty( $from_name ) ? $from_name : $site_title;

			return $from_name;
		}

		/**
		 * Get Sender Email
		 *
		 * @return mixed|void
		 *
		 * @since 4.3.2
		 */
		public function get_from_email() {

			$admin_email = get_option( 'admin_email', '' );

			$from_email = get_option( 'ig_es_from_email', '' );

			$from_email = ! empty( $from_email ) ? $from_email : $admin_email;

			return $from_email;
		}

		/**
		 * Allow click tracking?
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 *
		 * @return mixed|void
		 *
		 * @since 4.3.2
		 */
		public function can_track_clicks( $contact_id = 0, $campaign_id = 0 ) {
			if ( $this->can_track_open_clicks ) {
				$is_track_clicks = false;
				return apply_filters( 'ig_es_track_clicks', $is_track_clicks, $contact_id, $campaign_id, $this->link_data );
			}
			return false;
		}

		/**
		 * Allow utm tracking?
		 *
		 * @param array $data
		 *
		 * @return bool $
		 *
		 * @since 4.3.2
		 */
		public function can_track_utm( $data = array() ) {
			$is_track_utm = apply_filters( 'ig_es_track_utm', false, $data );

			return $is_track_utm;
		}

		/**
		 * Allow tracking email open?
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 *
		 * @return bool
		 *
		 * @since 4.3.2
		 */
		public function can_track_open( $contact_id = 0, $campaign_id = 0 ) {

			if ( $this->can_track_open_clicks ) {

				$is_track_email_opens = get_option( 'ig_es_track_email_opens', 'yes' );
				$is_track_email_opens = apply_filters( 'ig_es_track_open', $is_track_email_opens, $contact_id, $campaign_id, $this->link_data );

				if ( 'yes' === $is_track_email_opens ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get Tracking pixel
		 *
		 * @param array $data
		 *
		 * @return string
		 *
		 * @since 4.2.0
		 */
		public function get_tracking_pixel( $link_data = array() ) {

			$tracking_image = '';

			if ( $this->can_track_open() ) {

				if ( empty( $link_data ) ) {
					$link_data = $this->link_data;
				}

				$link_data['action'] = 'open';

				$url = $this->prepare_link( $link_data );

				$tracking_image = "<img src='{$url}' width='1' height='1' alt=''/>";
			}

			return $tracking_image;
		}

		/**
		 * Get link
		 *
		 * @param array $data
		 *
		 * @return string
		 *
		 * @since 4.0.0
		 * @since 4.2.0
		 * @since 4.3.2
		 */
		public function prepare_link( $data = array() ) {

			/**
			 * We are getting different data like action, message_id, campaign_id, contact_id, guid, email etc in $data
			 */
			$action = ! empty( $data['action'] ) ? $data['action'] : '';

			if ( 'subscribe' === $action ) {
				$action = 'optin';
			}

			$link = add_query_arg( 'es', $action, site_url( '/' ) );

			$data = ig_es_encode_request_data( $data );

			$link = add_query_arg( 'hash', $data, $link );

			return $link;
		}

		/**
		 * Get Unsubscribe text
		 *
		 * @param $link_data
		 *
		 * @return mixed|string|void
		 *
		 * @since 4.3.2
		 */
		public function get_unsubscribe_text( $link_data = array() ) {

			$text = '';

			if ( $this->add_unsubscribe_link ) {

				if ( empty( $link_data ) ) {
					$link_data = $this->link_data;
				}

				$unsubscribe_link = $this->get_unsubscribe_link( $link_data );

				$text = get_option( 'ig_es_unsubscribe_link_content', '' );

				$text = stripslashes( $text );
				$text = str_replace( '{{LINK}}', '{{UNSUBSCRIBE-LINK}}', $text );
				$text = str_replace( '{{UNSUBSCRIBE-LINK}}', $unsubscribe_link, $text );
			}

			return $text;
		}

		/**
		 * Get Unsubscribe link
		 *
		 * @param $link_data
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_unsubscribe_link( $link_data = array() ) {
			$link_data['action'] = 'unsubscribe';

			return $this->prepare_link( $link_data );
		}

		/**
		 * Get Subscribe link
		 *
		 * @param $link_data
		 *
		 * @return string
		 *
		 * @since 4.3.2
		 */
		public function get_subscribe_link( $link_data ) {
			$link_data['action'] = 'subscribe';

			return $this->prepare_link( $link_data );
		}

		/**
		 * How many emails we can send now?
		 *
		 * @since 4.3.5
		 */
		public function get_total_emails_send_now( $max_send = 100000 ) {
			
			// Get total emails sent in this hour
			$total_emails_sent = ES_Common::count_sent_emails();

			// Get hourly limit
			$can_total_emails_send_in_hour = ES_Common::get_ig_option( 'hourly_email_send_limit', 300 );

			// Is limit exceed?
			if ( $total_emails_sent >= $can_total_emails_send_in_hour ) {
				return 0;
			}

			// Still, you can send these many emails.
			$total_emails_can_send_now = $can_total_emails_send_in_hour - $total_emails_sent;

			// We can send more emails but if we get the count, send only those
			if ( ( $max_send > 0 ) && ( $max_send < $total_emails_can_send_now ) ) {
				$total_emails_can_send_now = $max_send;
			}

			// Do we have max email sending limit at once set?
			$can_total_emails_send_at_once = $this->get_max_email_send_at_once_count();

			if ( $can_total_emails_send_at_once < $total_emails_can_send_now ) {
				$total_emails_can_send_now = $can_total_emails_send_at_once;
			}

			return $total_emails_can_send_now;
		}

		/**
		 * Get max email send at once count
		 *
		 * @return int
		 *
		 * @since 4.3.5
		 */
		public function get_max_email_send_at_once_count() {
			$max_count = (int) ES_Common::get_ig_option( 'max_email_send_at_once', IG_ES_MAX_EMAIL_SEND_AT_ONCE );

			if ( $max_count <= 0 ) {
				$max_count = IG_ES_MAX_EMAIL_SEND_AT_ONCE;
			}

			return $max_count;
		}

		/**
		 * Replace keywords for new subscriber admin notification
		 *
		 * @return array
		 *
		 * @since 4.7.0
		 */
		public function replace_admin_notification_merge_tags( $data = array(), $message = '' ) {

			$name  = ! empty( $data['name'] ) ? $data['name'] : '';
			$email = ! empty( $data['email'] ) ? $data['email'] : '';
			$list  = ! empty( $data['list_name'] ) ? $data['list_name'] : '';

			$message = str_replace( '{{NAME}}', $name, $message );
			$message = str_replace( '{{EMAIL}}', $email, $message );
			$message = str_replace( '{{GROUP}}', '{{LIST}}', $message );
			$message = str_replace( '{{LIST}}', $list, $message );

			return $message;

		}

		/**
		 * Get list unsubscribe header string
		 *
		 * @return string $list_unsub_header
		 *
		 * @since 4.7.2
		 */
		public function get_list_unsubscribe_header( $email ) {

			$list_unsub_header = '';

			// Check if it is an campaign email and unsubscribe headers are enabled on the site.
			if ( $this->unsubscribe_headers_enabled() ) {
				$unsubscribe_link = $this->get_unsubscribe_link( $this->link_data );
				$contact_id       = ! empty( $this->link_data['contact_id'] )  ? $this->link_data['contact_id']  : 0;
				$campaign_id      = ! empty( $this->link_data['campaign_id'] ) ? $this->link_data['campaign_id'] : 0;
				$message_id       = ! empty( $this->link_data['message_id'] )  ? $this->link_data['message_id']  : 0;

				/* translators: 1. Subscriber email 2. Blog name */
				$mail_to_subject = sprintf( __( 'Unsubscribe %1$s from %2$s', 'email-subscribers' ), $email, get_bloginfo( 'name' ) );
				$mail_to_body    = "Contact-ID:$contact_id,Campaign-ID:$campaign_id";
				if ( ! empty( $message_id ) ) {
					$mail_to_body .= ",Message-ID:$message_id";
				}
				$list_unsub_header = sprintf(
					/* translators: 1. Unsubscribe link 2. Blog admin email */
					'<%1$s>,<mailto:%2$s?subject=%3$s&body=%4$s>',
					$unsubscribe_link,
					ES_Common::get_admin_email(),
					$mail_to_subject,
					$mail_to_body
				);
			}

			return $list_unsub_header;
		}

		/**
		 * Check if List-Unsubcribe headers are enabled
		 *
		 * @return boolean $enabled
		 *
		 * @since 4.7.2
		 */
		public function unsubscribe_headers_enabled() {
			$enabled = false;
			if ( ! empty( $this->link_data['campaign_id'] ) && apply_filters( 'ig_es_enable_list_unsubscribe_header', true ) ) {
				$enabled = true;
			}
			return $enabled;
		}

		/**
		 * Get default phpmailer
		 *
		 * @return PHPMailer
		 *
		 * @since 4.7.7
		 */
		public function get_phpmailer() {

			global $wp_version;

			if ( version_compare( $wp_version, '5.5', '<' ) ) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
				require_once ABSPATH . WPINC . '/class-smtp.php';
			} else {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

				// Check if PHPMailer class already exists before creating an alias for it.
				if ( ! class_exists( 'PHPMailer' ) ) {
					class_alias( PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer' );
				}

				// Check if phpmailerException class already exists before creating an alias for it.
				if ( ! class_exists( 'phpmailerException' ) ) {
					class_alias( PHPMailer\PHPMailer\Exception::class, 'phpmailerException' );
				}

				// Check if SMTP class already exists before creating an alias for it.
				if ( ! class_exists( 'SMTP' ) ) {
					class_alias( PHPMailer\PHPMailer\SMTP::class, 'SMTP' );
				}
			}

			$phpmailer          = new PHPMailer( true );
			$phpmailer->CharSet = 'UTF-8';

			$phpmailer::$validator = static function ( $email ) {
				return (bool) is_email( $email );
			};

			return $phpmailer;
		}

		/**
		 * Get current mailer slug
		 *
		 * @return string $mailer
		 * 
		 * @since 5.5.7
		 */
		public function get_current_mailer_slug() {
			$mailer_settings     = get_option( 'ig_es_mailer_settings', '');
			$current_mailer_slug = ( !empty( $mailer_settings['mailer'] ) ) ? $mailer_settings['mailer'] : 'wpmail';
			return $current_mailer_slug;
		}

		public function get_current_mailer_class() {
			$malier_slug          = $this->get_current_mailer_slug();
			$current_mailer_class = 'ES_' . ucfirst( $malier_slug ) . '_Mailer';
			// If we don't found mailer class, fallback to WP Mail.
			if ( ! class_exists( $current_mailer_class ) ) {
				$current_mailer_class = 'ES_Wpmail_Mailer';
			}
			return $current_mailer_class;
		}

		/**
		 * Get current mailer name
		 *
		 * @return string Mailer name
		 * 
		 * @since 5.6.0
		 */
		public function get_current_mailer_name() {
			$current_mailer_class = $this->get_current_mailer_class();
			$current_mailer       = new $current_mailer_class();
			return $current_mailer->get_name();
		}

		/**
		 * Set mailer to be used while sending emails.
		 * 
		 * @since 5.6.0
		 */
		public function set_mailer() {
			$mailer_class = $this->get_current_mailer_class();
			 $mailer_obj = new $mailer_class();
			if (ES_Service_Email_Sending::use_icegram_mailer()) {
				$this->default_mailer = $mailer_obj;
				$mailer_obj = new ES_Icegram_Mailer();
			}
			$this->mailer =$mailer_obj;
		}

		public function switch_to_default_mailer() {
			$this->mailer = $this->default_mailer;
		}

		public function get_current_mailer_account_url() {
			$current_mailer_class = $this->get_current_mailer_class();
			$current_mailer       = new $current_mailer_class();
			return $current_mailer->get_account_url();
		}
	}
}
