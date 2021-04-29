<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Handle_Subscription' ) ) {
	/**
	 * Handle subscription
	 *
	 * Class ES_Handle_Subscription
	 *
	 * @since 4.0.0
	 */
	class ES_Handle_Subscription {
		/**
		 * Subscriber Email
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $email;
		/**
		 * Subscriber Name
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $name;
		/**
		 * Subscriber First Name
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $first_name;
		/**
		 * Subscriber Last Name
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $last_name;
		/**
		 * Optin type
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $es_optin_type;
		/**
		 * List Id 
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $list_ids;
		/**
		 * Nonce value
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $es_nonce;
		/**
		 * Subscriber Status
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $status;
		/**
		 * To check if double-optin or not
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $is_double_optin = false;
		/**
		 * Guid
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $guid;
		/**
		 * Database Id
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $db_id;
		/**
		 * Form id
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		public $form_id;
		/**
		 * If the user is subscribed from Rainmaker
		 *
		 * @since 4.0.0
		 * @var
		 *
		 */
		private $from_rainmaker = false;

		/**
		 * ES_Handle_Subscription constructor.
		 *
		 * @param bool $from_rainmaker
		 *
		 * @since 4.0.0
		 */
		public function __construct( $from_rainmaker = false ) {
			if ( defined( 'DOING_AJAX' ) && ( true === DOING_AJAX ) ) {
				add_action( 'wp_ajax_es_add_subscriber', array( $this, 'process_request' ), 10 );
				add_action( 'wp_ajax_nopriv_es_add_subscriber', array( $this, 'process_request' ), 10 );
			}

			$this->from_rainmaker = $from_rainmaker;

			$this->handle_external_subscription();
		}

		/**
		 * Process request
		 *
		 * @param array $external_form_data data from external form/APIs.
		 *
		 * @since 4.0.0
		 * 
		 * @modified 4.5.7 Added $external_form_data parameter.
		 */
		public function process_request( $external_form_data = array() ) {

			$response = array(
				'status'  => 'ERROR',
				'message' => '',
			);

			$es           = ! empty( $_POST['es'] ) ? sanitize_text_field( wp_unslash( $_POST['es'] ) ) : '';
			$es_subscribe = ! empty( $_POST['esfpx_es-subscribe'] ) ? sanitize_text_field( wp_unslash( $_POST['esfpx_es-subscribe'] ) ) : '';

			// Verify nonce only if it is submitted through Email Subscribers' subscription form else check if we have form data in $external_form_data.
			if ( ( 'subscribe' === $es && ! empty( $es_subscribe ) && wp_verify_nonce( $es_subscribe, 'es-subscribe' ) ) || ! empty( $external_form_data ) ) {

				// Get form data from external source if passed.
				if ( ! empty( $external_form_data ) ) {
					$form_data = $external_form_data;
				} else {
					// If external form data is not passed then get form data from $_POST.
					$form_data = wp_unslash( $_POST );
				}
				$validate_response = $this->validate_data( $form_data );
				if ( 'ERROR' === $validate_response['status'] ) {

					// We want to pretend as "SUCCESS" for blocked emails.
					// So, we are setting as "SUCCESS" even if this email is blocked
					if ( 'es_email_address_blocked' === $validate_response['message'] ) {
						$validate_response['status']  = 'SUCCESS';
						$validate_response['message'] = 'es_optin_success_message';
					}

					$this->do_response( $validate_response );
					exit;
				}

				$email = ! empty( $form_data['esfpx_email'] ) ? sanitize_email( $form_data['esfpx_email'] ) : '';
				$name  = ! empty( $form_data['esfpx_name'] ) ? sanitize_text_field( $form_data['esfpx_name'] ) : '';

				$first_name = '';
				$last_name 	= '';
				if ( ! empty( $name ) ) {
					// Get First Name and Last Name from Name.
					$name_parts = ES_Common::prepare_first_name_last_name( $name );
					$first_name = $name_parts['first_name'];
					$last_name  = $name_parts['last_name'];
				} else {
					$first_name = ES_Common::get_name_from_email( $email );
				}

				$this->name          = $first_name;
				$this->first_name    = $first_name;
				$this->last_name     = $last_name;
				$this->email         = $email;
				$this->list_ids      = isset( $form_data['esfpx_lists'] ) ? $form_data['esfpx_lists'] : array();
				$this->es_nonce      = isset( $form_data['esfpx_es-subscribe'] ) ? trim( $form_data['esfpx_es-subscribe'] ) : '';
				$this->form_id       = isset( $form_data['esfpx_form_id'] ) ? trim( $form_data['esfpx_form_id'] ) : 0;
				$this->es_optin_type = get_option( 'ig_es_optin_type' );
				$this->guid          = ES_Common::generate_guid();

				if ( in_array( $this->es_optin_type, array( 'double_opt_in', 'double_optin' ) ) ) { // Backward Compatibility
					$this->is_double_optin = true;
					$this->status          = 'unconfirmed';
				} else {
					$this->status = 'subscribed';
				}

				if ( count( $this->list_ids ) > 0 ) {
					/**
					 * Check if subscribers exists?
					 *  - If yes, get id and save lists into ig_lists_contacts table
					 *  - If not, create contact and then save list
					 */
					$contact_id = ES()->contacts_db->get_contact_id_by_email( $this->email );
					if ( ! $contact_id ) {
						$data               = array();
						$data['first_name'] = $this->first_name;
						$data['last_name']  = $this->last_name;
						$data['source']     = 'form';
						$data['form_id']    = $this->form_id;
						$data['email']      = $this->email;
						$data['hash']       = $this->guid;
						$data['status']     = 'verified';
						$data['hash']       = $this->guid;
						$data['created_at'] = ig_get_current_date_time();
						$data['updated_at'] = null;
						$data['meta']       = null;

						$data = apply_filters( 'ig_es_add_subscriber_data', $data );
						if ( 'ERROR' === $data['status'] ) {
							$this->do_response( $validate_response );
							exit;
						}

						$contact_id = ES()->contacts_db->insert( $data );

						// do_action( 'ig_es_contact_added', $data);

					}

					$contact_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $contact_id, 'subscribed' );
					if ( empty( array_diff( $this->list_ids, $contact_lists ) ) ) {
						$response['message'] = 'es_email_exists_notice';
						$this->do_response( $response );
						exit;
					}
					$optin_type        = $this->is_double_optin ? IG_DOUBLE_OPTIN : IG_SINGLE_OPTIN;
					$list_contact_data = array(
						'contact_id'    => $contact_id,
						'status'        => $this->status,
						'subscribed_at' => ( 'subscribed' === $this->status ) ? ig_get_current_date_time() : '',
						'optin_type'    => $optin_type,
						'subscribed_ip' => ig_es_get_ip(),
					);

					ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $this->list_ids );

					if ( $contact_id ) {

						do_action( 'ig_es_contact_subscribe', $contact_id, $this->list_ids );

						$this->db_id = $contact_id;

						// Get comma(,) separated lists name based on ids.
						$list_name = ES_Common::prepare_list_name_by_ids( $this->list_ids );

						$merge_tags = array(
							'email'      => $this->email,
							'contact_id' => $contact_id,
							'name'       => ES_Common::prepare_name_from_first_name_last_name( $this->first_name, $this->last_name ),
							'first_name' => $this->first_name,
							'last_name'  => $this->last_name,
							'guid'       => $this->guid,
							'list_name'  => $list_name,
							'list_ids'	 => $this->list_ids,
						);

						if ( $this->is_double_optin ) {
							$response            = ES()->mailer->send_double_optin_email( $this->email, $merge_tags );
							$response['message'] = 'es_optin_success_message';
						} else {
							// Send Welcome Email
							ES()->mailer->send_welcome_email( $this->email, $merge_tags );

							// Send Notifications to admins
							ES()->mailer->send_add_new_contact_notification_to_admins( $merge_tags );

							$response['message'] = 'es_optin_success_message';
						}

						$response['status'] = 'SUCCESS';
					} else {

						$response['message'] = 'es_db_error_notice';
					}
				} else {
					$response['message'] = 'es_no_list_selected';
					$this->do_response( $response );
					exit;
				}
			} else {
				$response['message'] = 'es_permission_denied_notice';
			}

			$this->do_response( $response );
			exit;
		}

		/**
		 * Send Response
		 *
		 * @param $response
		 *
		 * @since 4.0.0
		 */
		public function do_response( $response ) {

			$message                  = isset( $response['message'] ) ? $response['message'] : '';
			$response['message_text'] = '';
			if ( ! empty( $message ) ) {
				$response['message_text'] = $this->get_messages( $message );
			}

			echo json_encode( $response );
			exit;

		}

		/**
		 * Validate subscribers data
		 *
		 * @param $data
		 *
		 * @return array|mixed|void
		 *
		 * @since 4.0.0
		 */
		public function validate_data( $data ) {

			$es_response = array(
				'status'  => 'ERROR',
				'message' => '',
			);

			if ( ! $this->from_rainmaker ) {

				// Honeypot validation
				//$hp_key = "esfpx_es_hp" . wp_create_nonce( 'es_hp' );
				$hp_key = 'esfpx_es_hp_email';
				if ( ! isset( $data[ $hp_key ] ) || ! empty( $data[ $hp_key ] ) ) {
					$es_response['message'] = 'es_unexpected_error_notice';

					return $es_response;
				}
			}

			$name = isset( $data['esfpx_name'] ) ? $data['esfpx_name'] : '';
			if ( strlen( $name ) > 50 ) {
				$es_response['message'] = 'es_invalid_name_notice';

				return $es_response;
			}

			$email = isset( $data['esfpx_email'] ) ? $data['esfpx_email'] : '';

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$es_response['message'] = 'es_invalid_email_notice';

				return $es_response;
			}

			$is_domain_blocked = $this->is_domain_blocked( $email );

			// Store it blocked emails
			if ( $is_domain_blocked ) {
				$data = array(
					'email' => $email,
					'ip'    => ig_es_get_ip(),
				);

				ES()->blocked_emails_db->insert( $data );

				$es_response['status']  = 'ERROR';
				$es_response['message'] = 'es_email_address_blocked';

				return $es_response;
			}

			$data = apply_filters( 'ig_es_validate_subscribers_data', $data );

			if ( ! empty( $data['status'] ) && 'ERROR' === $data['status'] ) {
				$es_response = $data;

				return $es_response;
			}

			$timeout = ES_Subscription_Throttling::throttle();
			if ( $timeout > 0 ) {
				$es_response['message'] = 'es_rate_limit_notice';

				return $es_response;
			}

			$es_response['status'] = 'SUCCESS';

			return $es_response;
		}

		/**
		 * Check if the domain is blocked based on email
		 *
		 * @param $email
		 *
		 * @return bool
		 *
		 * @since 4.1.0
		 */
		public function is_domain_blocked( $email ) {

			if ( empty( $email ) ) {
				return true;
			}

			$domains = trim( get_option( 'ig_es_blocked_domains', '' ) );

			// No domains to block? Return
			if ( empty( $domains ) ) {
				return false;
			}

			$domains = explode( PHP_EOL, $domains );

			$domains = apply_filters( 'ig_es_blocked_domains', $domains );

			if ( empty( $domains ) ) {
				return false;
			}

			$rev_email = strrev( $email );
			foreach ( $domains as $item ) {
				$item = trim( $item );
				if ( strpos( $rev_email, strrev( $item ) ) === 0 ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get Message description based on message
		 *
		 * @param $message
		 *
		 * @return array|mixed|string|void
		 *
		 * @since 4.0.0
		 */
		public function get_messages( $message ) {
			$ig_es_form_submission_success_message = get_option( 'ig_es_form_submission_success_message' );
			$messages                              = array(
				'es_empty_email_notice'       => __( 'Please enter email address', 'email-subscribers' ),
				'es_rate_limit_notice'        => __( 'You need to wait for sometime before subscribing again', 'email-subscribers' ),
				'es_optin_success_message'    => ! empty( $ig_es_form_submission_success_message ) ? $ig_es_form_submission_success_message : __( 'Successfully Subscribed.', 'email-subscribers' ),
				'es_email_exists_notice'      => __( 'Email Address already exists!', 'email-subscribers' ),
				'es_unexpected_error_notice'  => __( 'Oops.. Unexpected error occurred.', 'email-subscribers' ),
				'es_invalid_email_notice'     => __( 'Invalid email address', 'email-subscribers' ),
				'es_invalid_name_notice'      => __( 'Invalid name', 'email-subscribers' ),
				'es_try_later_notice'         => __( 'Please try after some time', 'email-subscribers' ),
				'es_db_error_notice'          => __( 'Oops...unable to add subscriber', 'email-subscribers' ),
				'es_permission_denied_notice' => __( 'You do not have permission to add subscriber', 'email-subscribers' ),
				'es_no_list_selected'         => __( 'Please select the list', 'email-subscribers' ),
				'es_invalid_captcha'          => __( 'Invalid Captcha', 'email-subscribers' ),
			);

			$messages = apply_filters( 'ig_es_subscription_messages', $messages );

			if ( ! empty( $messages ) ) {
				return isset( $messages[ $message ] ) ? $messages[ $message ] : '';
			}

			return $messages;
		}

		/**
		 * Method to handle external subscriptions.
		 *
		 * @since 4.4.7
		 **/
		public function handle_external_subscription() {

			$external_action = ig_es_get_request_data( 'ig_es_external_action' );
			if ( ! empty( $external_action ) && 'subscribe' === $external_action ) {
				$list_hash = ig_es_get_request_data( 'list' );
				$list      = ES()->lists_db->get_by( 'hash', $list_hash );
				if ( ! empty( $list ) ) {
					$list_id  = $list['id'];
					$name     = ig_es_get_request_data( 'name' );
					$email    = ig_es_get_request_data( 'email' );
					$hp_email = ig_es_get_request_data( 'es_hp_email' );

					$form_data = array(
						'esfpx_name'        => $name,
						'esfpx_email'       => $email,
						'esfpx_es_hp_email' => $hp_email,
						'esfpx_lists'       => array(
							$list_id,
						),
						'form_type'         => 'external',
					);

					$this->process_request( $form_data );
				}
			}
		}
	}
}
