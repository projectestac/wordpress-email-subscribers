<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Handle_Subscription {

	public $email;

	public $name;

	public $first_name;

	public $last_name;

	public $es_optin_type;

	public $list_ids;

	public $es_nonce;

	public $status;

	public $is_double_optin = false;

	public $guid;

	public $db_id;

	public $form_id;

	private $from_rainmaker = false;


	public function __construct( $from_rainmaker = false ) {
		if ( defined( 'DOING_AJAX' ) && ( true === DOING_AJAX ) ) {
			add_action( 'wp_ajax_es_add_subscriber', array( $this, 'process_request' ), 10 );
			add_action( 'wp_ajax_nopriv_es_add_subscriber', array( $this, 'process_request' ), 10 );
		}

		$this->from_rainmaker = $from_rainmaker;
	}

	public function process_request() {

		$response = array( 'status' => 'ERROR', 'message' => '' );

		if ( ( isset( $_POST['es'] ) ) && ( 'subscribe' === $_POST['es'] ) && ! empty( $_POST['esfpx_es-subscribe'] ) ) {

			// Restrict too many requests
			$timeout = ES_Subscription_Throttaling::throttle();
			if ( $timeout > 0 ) {
				$response['message'] = 'es_rate_limit_notice';
				$this->do_response( $response );
				exit;
			}

			$form_data         = wp_unslash( $_POST );
			$validate_response = $this->validate_data( $form_data );
			if ( $validate_response['status'] === 'ERROR' ) {

				// We want to pretend as "SUCCESS" for blocked emails.
				// So, we are setting as "SUCCESS" even if this email is blocked
				if ( $validate_response['message'] === 'es_email_address_blocked' ) {
					$validate_response['status']  = 'SUCCESS';
					$validate_response['message'] = 'es_single_optin_success_message';
				}

				$this->do_response( $validate_response );
				exit;
			}

			$first_name = $last_name = '';
			if ( ! empty( $form_data['esfpx_name'] ) ) {
				$name = trim( $form_data['esfpx_name'] );
				// Get First Name and Last Name from Name
				$name_parts = ES_Common::prepare_first_name_last_name( $name );
				$first_name = $name_parts['first_name'];
				$last_name  = $name_parts['last_name'];
			} else {
				$email      = trim( $form_data['esfpx_email'] );
				$first_name = ES_Common::get_name_from_email( $email );
			}

			$this->name          = $first_name;
			$this->first_name    = $first_name;
			$this->last_name     = $last_name;
			$this->email         = isset( $form_data['esfpx_email'] ) ? trim( $form_data['esfpx_email'] ) : '';
			$this->list_ids      = isset( $form_data['esfpx_lists'] ) ? $form_data['esfpx_lists'] : array();
			$this->es_nonce      = isset( $form_data['esfpx_es-subscribe'] ) ? trim( $form_data['esfpx_es-subscribe'] ) : '';
			$this->form_id       = isset( $form_data['esfpx_form_id'] ) ? trim( $form_data['esfpx_form_id'] ) : 0;
			$this->es_optin_type = get_option( 'ig_es_optin_type' );
			$this->guid          = ES_Common::generate_guid();

			if ( in_array( $this->es_optin_type, array( 'double_opt_in', 'double_optin' ) ) ) { // Backward Compatibility
				$this->is_double_optin = true;
				$this->status          = "unconfirmed";
			} else {
				$this->status = "subscribed";
			}

			/**
			 * Check if subscribers exists?
			 *  - If yes, get id and save lists into ig_lists_contacts table
			 *  - If not, create contact and then save list
			 */

			$contact_id = ES_DB_Contacts::get_contact_id_by_email( $this->email );
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

				$data = apply_filters( 'es_add_subscriber_data', $data );
				if ( 'ERROR' === $data['status'] ) {
					$this->do_response( $validate_response );
					exit;
				}

				$contact_id = ES_DB_Contacts::add_subscriber( $data );
			}

			if ( count( $this->list_ids ) > 0 ) {
				$contact_lists = ES_DB_Lists_Contacts::get_list_ids_by_contact( $contact_id );
				if ( $contact_lists == $this->list_ids ) {
					$response['message'] = 'es_email_exists_notice';
					$this->do_response( $response );
					exit;
				}
				$optin_type        = $this->is_double_optin ? IG_DOUBLE_OPTIN : IG_SINGLE_OPTIN;
				$list_contact_data = array(
					'list_id'       => $this->list_ids,
					'contact_id'    => $contact_id,
					'status'        => $this->status,
					'subscribed_at' => ( $this->status === 'subscribed' ) ? ig_get_current_date_time() : '',
					'optin_type'    => $optin_type,
					'subscribed_ip' => ES_Subscription_Throttaling::getUserIP()
				);
				ES_DB_Lists_Contacts::delete_list_contacts( $contact_id, $this->list_ids );
				ES_DB_Lists_Contacts::add_lists_contacts( $list_contact_data );

				if ( $contact_id ) {
					$this->db_id = $contact_id;
					if ( $this->is_double_optin ) {
						$this->send_double_optin_notification();
						$response['message'] = 'es_double_optin_success_message';
					} else {
						$enable_welcome_email = get_option( 'ig_es_enable_welcome_email', 'no' );
						if ( 'yes' === $enable_welcome_email ) {
							$this->send_welcome_notification();
						}

						$response['message'] = 'es_single_optin_success_message';
					}

					$ig_es_notifyadmin = get_option( 'ig_es_notify_admin' );
					if ( 'yes' === $ig_es_notifyadmin ) {
						$this->send_admin_signup_notification();
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

	public function do_response( $response ) {

		$message                  = isset( $response['message'] ) ? $response['message'] : '';
		$response['message_text'] = '';
		if ( ! empty( $message ) ) {
			$response['message_text'] = $this->get_messages( $message );
		}

		echo json_encode( $response );
		exit;

	}

	public function send_welcome_notification() {

		$list_name = '';
		if ( count( $this->list_ids ) > 0 ) {
			$lists_id_name_map = ES_DB_Lists::get_list_id_name_map();
			$lists_name        = array();
			foreach ( $this->list_ids as $list_id ) {
				if ( ! empty( $lists_id_name_map[ $list_id ] ) ) {
					$lists_name[] = $lists_id_name_map[ $list_id ];
				}
			}

			$list_name = implode( ', ', $lists_name );
		}

		$template_data = array(
			'email'     => $this->email,
			'db_id'     => $this->db_id,
			'name'      => $this->first_name,
			'guid'      => $this->guid,
			'list_name' => $list_name
		);

		$subject = ES_Mailer::prepare_welcome_email_subject( $template_data );

		$content = ES_Mailer::prepare_welcome_email( $template_data );

		$response = ES_Mailer::send( $this->email, $subject, $content );

		if ( $response ) {
			return true;
		}

		return false;

	}

	public function send_double_optin_notification() {

		$template_data = array(
			'email' => $this->email,
			'db_id' => $this->db_id,
			'name'  => $this->first_name,
			'guid'  => $this->guid
		);

		$subject = get_option( 'ig_es_confirmation_mail_subject', true );
		$content = ES_Mailer::prepare_double_optin_email( $template_data );

		$response = ES_Mailer::send( $this->email, $subject, $content );

		if ( $response ) {
			return true;
		}

		return false;
	}

	public function send_admin_signup_notification() {

		$admin_email_addresses = get_option( 'ig_es_admin_emails', '' );

		if ( ! empty( $admin_email_addresses ) ) {

			$admin_emails = explode( ',', $admin_email_addresses );

			$list_id_name_map = ES_DB_Lists::get_list_id_name_map();

			$list_name = '';
			if ( count( $this->list_ids ) > 0 ) {
				foreach ( $this->list_ids as $list_id ) {
					$list_name .= $list_id_name_map[ $list_id ] . ',';
				}

				$list_name = rtrim( $list_name, ',' );
			}

			$template_data = array(
				'name'      => $this->first_name,
				'email'     => $this->email,
				'list_name' => $list_name
			);

			if ( count( $admin_emails ) > 0 ) {
				$send = ES_Common::send_signup_notification_to_admins( $template_data );

				return $send;
			}
		}

		return false;

	}

	public function validate_data( $data ) {

		$es_response = array( 'status' => 'ERROR', 'message' => '' );

		if ( ! $this->from_rainmaker ) {
			//honey-pot validation
			$hp_key = "esfpx_es_hp_" . wp_create_nonce( 'es_hp' );
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
			$subscriber_ip = ES_Subscription_Throttaling::getUserIP();
			$data['email'] = $email;
			$data['ip']    = $subscriber_ip;
			ES_DB_Blocked_Emails::insert( $data );

			$es_response['status']  = 'ERROR';
			$es_response['message'] = 'es_email_address_blocked';

			return $es_response;
		}

		$data = apply_filters( 'es_validate_subscribers_data', $data );

		if ( ! empty( $data['status'] ) && 'ERROR' === $data['status'] ) {
			$es_response = $data;

			return $es_response;
		}

		$es_response['status'] = 'SUCCESS';

		return $es_response;
	}

	public function is_domain_blocked( $email ) {
		$domains = get_option( 'ig_es_blocked_domains' );

		$domains = explode( PHP_EOL, $domains );
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

	public function get_messages( $message ) {

		$messages = array(
			'es_empty_email_notice'           => __( 'Please enter email address', 'email-subscribers' ),
			'es_rate_limit_notice'            => __( 'You need to wait for sometime before subscribing again', 'email-subscribers' ),
			'es_single_optin_success_message' => __( 'Successfully Subscribed.', 'email-subscribers' ),
			'es_double_optin_success_message' => __( 'Your subscription was successful! Kindly check your mailbox and confirm your subscription. If you don\'t see the email within a few minutes, check the spam/junk folder.', 'email-subscribers' ),
			'es_email_exists_notice'          => __( 'Email Address already exists!', 'email-subscribers' ),
			'es_unexpected_error_notice'      => __( 'Oops.. Unexpected error occurred.', 'email-subscribers' ),
			'es_invalid_email_notice'         => __( 'Invalid email address', 'email-subscribers' ),
			'es_invalid_name_notice'          => __( 'Invalid name', 'email-subscribers' ),
			'es_try_later_notice'             => __( 'Please try after some time', 'email-subscribers' ),
			'es_db_error_notice'              => __( 'Oops...unable to add subscriber', 'email-subscribers' ),
			'es_permission_denied_notice'     => __( 'You do not have permission to add subscriber', 'email-subscribers' ),
			'es_no_list_selected'             => __( 'Please select the list', 'email-subscribers' ),
			'es_invalid_captcha'              => __( 'Invalid Captcha', 'email-subscribers' )
		);

		if ( ! empty( $messages ) ) {
			return isset( $messages[ $message ] ) ? $messages[ $message ] : '';
		}

		return $messages;
	}

}


add_action( 'init', function () {
	new ES_Handle_Subscription();
} );