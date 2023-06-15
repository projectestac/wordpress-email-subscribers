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
		 */
		public $email;

		/**
		 * Subscriber Name
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $name;

		/**
		 * Subscriber First Name
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $first_name;

		/**
		 * Subscriber Last Name
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $last_name;

		/**
		 * Optin type
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $es_optin_type;

		/**
		 * List Id
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $list_ids;

		/**
		 * List Hashes
		 *
		 * @since 4.6.12
		 * @var
		 */
		public $list_hashes;

		/**
		 * Nonce value
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $es_nonce;

		/**
		 * Subscriber Status
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $status;

		/**
		 * To check if double-optin or not
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $is_double_optin = false;

		/**
		 * Guid
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $guid;

		/**
		 * Database Id
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $db_id;

		/**
		 * Form id
		 *
		 * @since 4.0.0
		 * @var
		 */
		public $form_id;

		/**
		 * IP Address
		 *
		 * @since 4.7.3
		 * @var
		 */
		public $ip_address;

		/**
		 * Reference Site
		 *
		 * @since 5.4.0
		 * @var
		 */
		public $reference_site;

		/**
		 * If the user is subscribed from Rainmaker
		 *
		 * @since 4.0.0
		 * @var
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
				add_action( 'wp_ajax_es_add_subscriber', array( $this, 'process_ajax_request' ), 10 );
				add_action( 'wp_ajax_nopriv_es_add_subscriber', array( $this, 'process_ajax_request' ), 10 );
			}

			$this->from_rainmaker = $from_rainmaker;

			$this->handle_subscription();
		}

		/**
		 * Process form submission via ajax call
		 */
		public function process_ajax_request() {
			$es_subscribe = ! empty( $_POST['esfpx_es-subscribe'] ) ? sanitize_text_field( wp_unslash( $_POST['esfpx_es-subscribe'] ) ) : '';

			if ( ! empty( $es_subscribe ) && wp_verify_nonce( $es_subscribe, 'es-subscribe' ) ) {
				$nonce_verified = true;
			}

			if ( ! empty( $es_subscribe ) ) {
				defined( 'IG_ES_RETURN_HANDLE_RESPONSE' ) || define( 'IG_ES_RETURN_HANDLE_RESPONSE', true );
				$response = $this->process_request( wp_unslash( $_POST ) );
			} else {
				$response = array( 'status' => 'ERROR', 'message' => 'es_unexpected_error_notice', );
			}
			$response = $this->do_response( $response );
			wp_send_json( $response );
		}

		/**
		 * Process request
		 *
		 * @param array $external_form_data data from external form/APIs.
		 *
		 * @since 4.0.0
		 *
		 * @modified 4.5.7 Added $external_form_data parameter.
		 * @modified 4.8.2 Added IG_ES_RETURN_HANDLE_RESPONSE const.
		 *
		 * @return void | array
		 */
		public function process_request( $external_form_data = array() ) {

			$response = array(
				'status'  => 'ERROR',
				'message' => '',
			);

			$es           = ! empty( $_POST['es'] ) ? sanitize_text_field( wp_unslash( $_POST['es'] ) ) : '';
			$es_form_id   = ! empty( $_POST['esfpx_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['esfpx_form_id'] ) ) : '';
			$es_subscribe = ! empty( $_POST['esfpx_es-subscribe'] ) ? sanitize_text_field( wp_unslash( $_POST['esfpx_es-subscribe'] ) ) : '';
			
			if ( ! empty( $es_subscribe ) && wp_verify_nonce( $es_subscribe, 'es-subscribe' ) ) {
				$nonce_verified = true;
			}

			$doing_ajax      = defined( 'DOING_AJAX' ) && DOING_AJAX;
			$return_response = defined( 'IG_ES_RETURN_HANDLE_RESPONSE' ) && IG_ES_RETURN_HANDLE_RESPONSE;

			// Verify nonce only if it is submitted through Icegram Express' subscription form else check if we have form data in $external_form_data.
			if ( ( 'subscribe' === $es ) || ! empty( $external_form_data ) ) {

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

					$response = $this->do_response( $validate_response );
					if ( $return_response ) {
						return $response;
					} elseif ( $doing_ajax ) {
						wp_send_json( $response );
					} else {
						return $response;
					}
				}

				$email      = ! empty( $form_data['esfpx_email'] ) ? sanitize_email( $form_data['esfpx_email'] ) : '';
				$name       = ! empty( $form_data['esfpx_name'] ) ? sanitize_text_field( $form_data['esfpx_name'] ) : '';
				$ip_address = ! empty( $form_data['esfpx_ip_address'] ) ? sanitize_text_field( $form_data['esfpx_ip_address'] ) : '';

				$first_name = '';
				$last_name  = '';
				if ( ! empty( $name ) ) {
					// Get First Name and Last Name from Name.
					$name_parts = ES_Common::prepare_first_name_last_name( $name );
					$first_name = $name_parts['first_name'];
					$last_name  = $name_parts['last_name'];
				}

				$this->name           = $first_name;
				$this->first_name     = $first_name;
				$this->last_name      = $last_name;
				$this->email          = $email;
				$this->ip_address     = $ip_address;
				$this->list_hashes    = isset( $form_data['esfpx_lists'] ) ? $form_data['esfpx_lists'] : array();
				$this->es_nonce       = isset( $form_data['esfpx_es-subscribe'] ) ? trim( $form_data['esfpx_es-subscribe'] ) : '';
				$this->form_id        = isset( $form_data['esfpx_form_id'] ) ? trim( $form_data['esfpx_form_id'] ) : 0;
				$this->reference_site = isset( $form_data['esfpx_reference_site'] ) ? esc_url_raw( $form_data['esfpx_reference_site'] ) : null;
				$this->es_optin_type  = get_option( 'ig_es_optin_type' );
				$this->guid           = ES_Common::generate_guid();

				if ( in_array( $this->es_optin_type, array( 'double_opt_in', 'double_optin' ) ) ) { // Backward Compatibility
					$this->is_double_optin = true;
					$this->status          = 'unconfirmed';
				} else {
					$this->status = 'subscribed';
				}

				if ( ! empty( $this->list_hashes ) ) {

					$list_hash_str  = ES()->lists_db->prepare_for_in_query( $this->list_hashes );
					$where          = "hash IN ($list_hash_str)";
					$this->list_ids = ES()->lists_db->get_column_by_condition( 'id', $where );

					if ( ! empty( $this->list_ids ) ) {

						$is_new = true;
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
							$data['ip_address'] = $this->ip_address;
							$data['status']     = 'verified';
							$data['hash']       = $this->guid;
							$data['created_at'] = ig_get_current_date_time();
							$data['updated_at'] = null;
							$data['meta']       = null;

							if ( ! is_null( $this->reference_site ) ) {
								$data['reference_site'] = $this->reference_site;
							}

							$data = apply_filters( 'ig_es_add_subscriber_data', $data );

							$data = apply_filters( 'ig_es_add_custom_field_data' , $data, $form_data );
							if ( 'ERROR' === $data['status'] ) {
								$response = $this->do_response( $validate_response );
								if ( $return_response ) {
									return $response;
								} elseif ( $doing_ajax ) {
									wp_send_json( $response );
								} else {
									return $response;
								}
							}

							$contact_id = ES()->contacts_db->insert( $data );

							// do_action( 'ig_es_contact_added', $data);

						} else {
							$is_new = false;
						}

						$contact_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $contact_id, 'subscribed' );
						if ( empty( array_diff( $this->list_ids, $contact_lists ) ) ) {
							$response['message'] = 'es_email_exists_notice';
							$response            = $this->do_response( $response );
							if ( $return_response ) {
								return $response;
							} elseif ( $doing_ajax ) {
								wp_send_json( $response );
							} else {
								return $response;
							}
						}

						// If contact already exists then update the contact data.
						if ( ! $is_new ) {
							$data = array();

							// Update first name and last name when both are provided
							if ( ! empty( $this->first_name ) && ! empty( $this->last_name ) ) {
								$data['first_name'] = $this->first_name;
								$data['last_name']  = $this->last_name;
							}

							if ( ! empty( $this->ip_address ) ) {
								$data['ip_address'] = $this->ip_address;
							}

							if ( ! empty( $data ) ) {
								$data['updated_at'] = ig_get_current_date_time();
								ES()->contacts_db->update( $contact_id, $data );
							}
						}

						$optin_type        = $this->is_double_optin ? IG_DOUBLE_OPTIN : IG_SINGLE_OPTIN;
						$list_contact_data = array(
							'contact_id'    => $contact_id,
							'status'        => $this->status,
							'subscribed_at' => ( 'subscribed' === $this->status ) ? ig_get_current_date_time() : '',
							'optin_type'    => $optin_type,
							'subscribed_ip' => '',
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
								'list_ids'   => $this->list_ids,
							);

							if ( $this->is_double_optin ) {
								$response['message'] = 'es_optin_success_message';

								do_action( 'ig_es_contact_unconfirmed', $merge_tags );
							} else {

								do_action( 'ig_es_contact_subscribed', $merge_tags );

								// Send Notifications to admins
								//ES()->mailer->send_add_new_contact_notification_to_admins( $merge_tags );

								$response['message'] = 'es_optin_success_message';
							}

							$response['status']  = 'SUCCESS';
							$form_settings       = ES()->forms_db->get_form_settings( $es_form_id );
							$action_after_submit = ! empty( $form_settings['action_after_submit'] ) ? $form_settings['action_after_submit'] : '';

							if ( 'redirect_to_url' === $action_after_submit ) {
								$redirection_url             = ! empty( $form_settings['redirection_url'] ) ? $form_settings['redirection_url'] : '';
								$is_hash_not_added 		 	 = false === strpos( $redirection_url, '#' );
								$response['redirection_url'] = $is_hash_not_added ? $redirection_url . '#' : $redirection_url;
							}
						} else {

							$response['message'] = 'es_db_error_notice';
						}
					} else {
						$response['status']  = 'SUCCESS';
						$response['message'] = 'es_optin_success_message';							
						
						$response = $this->do_response( $response );
						if ( $return_response ) {
							return $response;
						} elseif ( $doing_ajax ) {
							wp_send_json( $response );
						} else {
							return $response;
						}
					}
				} else {
					$response['message'] = 'es_no_list_selected';
					$response            = $this->do_response( $response );
					if ( $return_response ) {
						return $response;
					} elseif ( $doing_ajax ) {
						wp_send_json( $response );
					} else {
						return $response;
					}
				}
			} else {
				$response['message'] = 'es_permission_denied_notice';				
			}

			$response = $this->do_response( $response );

			if ( $return_response ) {
				return $response;
			} elseif ( $doing_ajax ) {
				wp_send_json( $response );
			} else {
				return $response;
			}
		}

		/**
		 * Send Response
		 *
		 * @param $response
		 *
		 * @since 4.0.0
		 * 
		 * @modify 5.6.1
		 */
		public function do_response( $response ) {

			$message                  = isset( $response['message'] ) ? $response['message'] : '';
			$response['message_text'] = '';
			if ( ! empty( $message ) ) {
				$response['message_text'] = $this->get_messages( $message );
			}

			return $response;
		}

		/**
		 * Validate subscribers data
		 *
		 * @param $data
		 *
		 * @return array|mixed|void
		 *
		 * @since 4.0.0
		 * 
		 * @modify 5.6.7
		 */
		public function validate_data( $data ) {

			$es_response = array(
				'status'  => 'ERROR',
				'message' => '',
			);

			if ( ! $this->from_rainmaker ) {

				// Honeypot validation
				// $hp_key = "esfpx_es_hp" . wp_create_nonce( 'es_hp' );
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

			$is_domain_blocked = ES_Common::is_domain_blocked( $email );

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
		 * Get Message description based on message
		 *
		 * @param $message
		 *
		 * @return array|mixed|string|void
		 *
		 * @since 4.0.0
		 * 
		 * @modify 5.6.1
		 */
		public function get_messages( $message ) {
			$ig_es_form_submission_success_message = get_option( 'ig_es_form_submission_success_message' );
			$form_data 							   = ES()->forms_db->get_form_by_id( $this->form_id );
			$settings  							   = ig_es_get_data( $form_data, 'settings', array() );

			if ( ! empty( $settings ) ) {
				$settings        = maybe_unserialize( $settings );
				$success_message = ! empty( $settings['success_message'] ) ? $settings['success_message'] : '';
			}

			$messages                              = array(
				'es_empty_email_notice'       => __( 'Please enter email address', 'email-subscribers' ),
				'es_rate_limit_notice'        => __( 'You need to wait for some time before subscribing again', 'email-subscribers' ),
				'es_optin_success_message'    => ! empty( $success_message ) ? $success_message : $ig_es_form_submission_success_message,
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
		 * 
		 * @modify 5.6.2
		 **/
		public function handle_subscription() {

			$external_action = ig_es_get_request_data( 'ig_es_external_action' );
			if ( ! empty( $external_action ) && 'subscribe' === $external_action ) {
				$subscription_api_enabled = 'yes' === get_option( 'ig_es_allow_api', 'yes' );
				if ( ! $subscription_api_enabled ) {
					return;
				}
				$list_hash  = ig_es_get_request_data( 'list' );
				$lists_hash = ig_es_get_request_data( 'lists' );
				if ( ! empty( $list_hash ) ) {
					$list  = ES()->lists_db->get_by( 'hash', $list_hash );
					$lists = array( $list );
					$lists_hash = array( $list_hash );
				} elseif ( ! empty( $lists_hash ) ) {
					$lists = ES()->lists_db->get_lists_by_hash( $lists_hash );
				}

				if ( ! empty( $lists ) ) {
					$name       = ig_es_get_request_data( 'name' );
					$email      = ig_es_get_request_data( 'email' );
					$hp_email   = ig_es_get_request_data( 'es_hp_email' );
					$ip_address = ig_es_get_request_data( 'ip_address' );

					$form_data = array(
						'esfpx_name'        => $name,
						'esfpx_email'       => $email,
						'esfpx_es_hp_email' => $hp_email,
						'esfpx_ip_address'  => $ip_address,
						'esfpx_lists'       => $lists_hash,
						'form_type'         => 'external',
					);

					$response = $this->process_request( $form_data );
					wp_send_json( $response );
				}
			}

			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
			// Run only when it is normal form submission and not ajax form submission.
			if ( ! $doing_ajax ) {
				$es_action = ig_es_get_post_data( 'es' );				
				if ( ! empty( $es_action ) && 'subscribe' === $es_action ) {
					// Store the response, so that it can be shown while outputting the subscription form HTML.
					$response = $this->process_request();
					if ( ! empty ( $response['redirection_url'] ) ) {
						wp_redirect( $response['redirection_url'] );
						exit;
					} 

					ES_Shortcode::$response = $response;
				}
			}
		}
	}
}
