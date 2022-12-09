<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The trial-specific functionality of the plugin.
 */
class IG_ES_Trial {

	/**
	 * Class instance.
	 *
	 * @var IG_ES_Trial $instance
	 */
	public static $instance;

	/**
	 * Initialize the class.
	 *
	 * @since 4.6.2
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'show_trial_notices' ) );
		add_action( 'wp_ajax_ig_es_trial_optin', array( $this, 'handle_trial_optin' ) );
	}

	/**
	 * Get class instance.
	 *
	 * @since 4.6.2
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Method to get if user has opted for trial or not.
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
	public function is_trial() {
		$is_trial = get_option( 'ig_es_is_trial', '' );
		if ( 'yes' === $is_trial ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get trial start date
	 *
	 * @return false|mixed|void
	 *
	 * @since 4.6.6
	 */
	public function get_trial_start_date() {
		return get_option( 'ig_es_trial_started_at', '' );
	}

	/**
	 * Method to get if trial has expired or not.
	 *
	 * @return bool
	 *
	 * @since 4.6.1
	 */
	public function is_trial_expired() {
		$is_trial_expired = false;
		$is_trial         = get_option( 'ig_es_is_trial', '' );

		if ( 'yes' === $is_trial ) {
			$trial_started_at = get_option( 'ig_es_trial_started_at' );
			if ( ! empty( $trial_started_at ) ) {

				// Get current timestamp.
				$current_time = time();

				// Get the timestamp when trial will expire.
				$trial_expires_at = $trial_started_at + ES()->trial->get_trial_period();

				// Check if current time is greater than expiry time.
				if ( $current_time > $trial_expires_at ) {
					$is_trial_expired = true;
				}
			}
		}

		return $is_trial_expired;
	}

	/**
	 * Method to check if trial is valid.
	 *
	 * @return bool $is_trial_valid Is trial valid
	 *
	 * @since 4.6.1
	 */
	public function is_trial_valid() {

		// Check if user has opted for trial and it has not yet expired.
		return $this->is_trial() && ! $this->is_trial_expired();
	}

	/**
	 * Method to get trial period
	 *
	 * @param string $period_in Period unit.
	 *
	 * @return int Trial period in second.
	 *
	 * @since 4.6.2
	 */
	public function get_trial_period( $period_in = 'in_seconds' ) {

		$trial_period = 0;

		switch ( $period_in ) {
			case 'in_days':
				$trial_period = IG_ES_TRIAL_PERIOD_IN_DAYS;
				break;
			case 'in_seconds':
			default:
				$trial_period = IG_ES_TRIAL_PERIOD_IN_DAYS * DAY_IN_SECONDS;
		}

		return $trial_period;
	}

	/**
	 * Method to add trial related data.
	 *
	 * @param string $is_trial.
	 *
	 * @return int $trial_started_at
	 *
	 * @since 4.6.1
	 */
	public function add_trial_data( $is_trial = '', $trial_started_at = 0 ) {

		$is_trial = ! empty( $is_trial ) ? $is_trial : 'yes';
		update_option( 'ig_es_is_trial', $is_trial, false );

		if ( 'yes' === $is_trial ) {
			$trial_started_at = ! empty( $trial_started_at ) ? $trial_started_at : time();
			update_option( 'ig_es_trial_started_at', $trial_started_at, false );
		}
	}

	/**
	 * Method to get total days since trial start date/time
	 *
	 * @return int $trial_time_in_days Trial period passed in days.
	 *
	 * @since 4.6.2
	 */
	public function get_days_since_trial_started() {

		$current_time       = time();
		$trial_time_in_days = 0;
		$trial_started_at   = get_option( 'ig_es_trial_started_at' );

		if ( ! empty( $trial_started_at ) ) {
			$trial_time_in_seconds = $current_time - $trial_started_at;
			$trial_time_in_days    = floor( $trial_time_in_seconds / DAY_IN_SECONDS );
		}

		return $trial_time_in_days;
	}

	/**
	 * Method to get total remaining days in trial expiration.
	 *
	 * @return int $remaining_trial_days Remaining trial days.
	 *
	 * @since 4.6.2
	 */
	public function get_remaining_trial_days() {

		$total_days_since_trial = $this->get_days_since_trial_started();
		$trial_period_in_days   = $this->get_trial_period( 'in_days' );
		$remaining_trial_days   = $trial_period_in_days - $total_days_since_trial;

		return $remaining_trial_days;
	}

	/**
	 * Method to get trial expiry date
	 *
	 * @param string $date_format Date formate
	 *
	 * @return string $trial_expriy_date Trial expiry date.
	 *
	 * @since 4.6.2
	 */
	public function get_trial_expiry_date( $date_format = 'Y-m-d H:i:s' ) {

		$trial_expriy_date = '';
		$trial_started_at  = $this->get_trial_started_at();

		if ( ! empty( $trial_started_at ) ) {
			$trial_expires_at  = $trial_started_at + $this->get_trial_period();
			$trial_expriy_date = gmdate( $date_format, $trial_expires_at );
		}

		return $trial_expriy_date;
	}

	/**
	 * Method to get trial started at timestamp.
	 *
	 * @return int $trial_started_at Trial started at timestamp.
	 *
	 * @since 4.6.2
	 */
	public function get_trial_started_at() {
		$trial_started_at = get_option( 'ig_es_trial_started_at', 0 );
		return $trial_started_at;
	}

	/**
	 * Method to show trial related notices to user.
	 *
	 * @since 4.6.2
	 */
	public function show_trial_notices() {

		// Don't show trial notices untill onboarding is completed.
		if ( ! IG_ES_Onboarding::is_onboarding_completed() ) {
			return;
		}

		$is_trial             = ES()->trial->is_trial();
		$is_premium           = ES()->is_premium();
		$is_premium_installed = ES()->is_premium_installed();
		$current_page         = ig_es_get_request_data( 'page' );

		$show_offer_notice = false;

		// Add upgrade to premium nudging notice if currently isn't any offer going on, user has opted for trial and is not a premium user and premium plugin is not installed on site and is not dashboard page.
		if ( ! ES()->is_offer_period() && $is_trial && ! $is_premium && ! $is_premium_installed && 'es_dashboard' !== $current_page ) {

			// Start nudging the user on following days before trial expiration.
			$nudging_days    = array( 1, 3, 5 );
			$min_nudging_day = min( $nudging_days );
			$max_nudging_day = max( $nudging_days );

			// Current day's number from start of trial.
			$remaining_trial_days = ES()->trial->get_remaining_trial_days();

			// User is in nudging period if remaining trial days are between minmum and maximum nudging days.
			$is_in_nudging_period = $remaining_trial_days >= $min_nudging_day && $remaining_trial_days <= $max_nudging_day ? true : false;

			// Start nudging the user if peried fall into nudging period.
			if ( $is_in_nudging_period ) {
				$current_nudging_day = 0;

				foreach ( $nudging_days as $day ) {
					if ( $remaining_trial_days <= $day ) {
						// Get current nudging day i.e. 1 or 3 or 5
						$current_nudging_day = $day;
						break;
					}
				}

				// Check if we have a nudging day.
				if ( ! empty( $current_nudging_day ) ) {
					$notice_last_dismiss_date = get_option( 'ig_es_trial_to_premium_notice_date' );
					// Always show notice if not already dismissed before.
					if ( empty( $notice_last_dismiss_date ) ) {
						$show_offer_notice = true;
					} else {
						$trial_expiry_date    = ES()->trial->get_trial_expiry_date();
						$date_diff_in_seconds = strtotime( $trial_expiry_date ) - strtotime( $notice_last_dismiss_date );

						// Ceil function is used to round off to nearest upper limit integer, 4.1 would be 5.
						$date_diff_in_days = ceil( $date_diff_in_seconds / DAY_IN_SECONDS );

						// Check if current nudging day is after last dismissed date.
						if ( $current_nudging_day < $date_diff_in_days ) {
							$show_offer_notice = true;
						}
					}
				}
			}
		}

		if ( $show_offer_notice ) {
			$notice_args = array(
				'include' => ES_PLUGIN_DIR . 'lite/includes/notices/views/trial-to-premium-offer.php',
			);
			ES_Admin_Notices::add_custom_notice( 'trial_to_premium', $notice_args );
		} else {
			ES_Admin_Notices::remove_notice( 'trial_to_premium' );
		}
	}

	/**
	 * Method to get ES trial list hash
	 *
	 * @return string $trial_list_hash Get hash for Trial list
	 *
	 * @since 5.3.12
	 */
	public function get_es_trial_list_hash() {
		$trial_list_hash = 'f114244b3819';
		return $trial_list_hash;
	}

	/**
	 * Method to handle trial optin on dashboard page.
	 */
	public function handle_trial_optin() {
		check_ajax_referer( 'ig-es-trial-optin-nonce', 'security' );
		
		$name            = ig_es_get_request_data( 'name', '' );
		$email           = ig_es_get_request_data( 'email', '' );
		$trial_list_hash = $this->get_es_trial_list_hash();

		$sign_up_data = array(
			'name'  => $name,
			'email' => $email,
			'list'  => $trial_list_hash,
		);

		$response              = $this->send_ig_sign_up_request( $sign_up_data );
		$ig_signup_successfull = 'success' === $response['status'];
		if ( $ig_signup_successfull ) {
			$is_trial         = 'yes';
			$trial_started_at = time();
			$this->add_trial_data( $is_trial, $trial_started_at );
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}

	/**
	 * Send a sign up request to ES installed on IG site.
	 * 
	 * @since 5.3.12
	 * 
	 * @param array $request_data
	 */
	public function send_ig_sign_up_request( $request_data = array() ) {

		$response = array(
			'status' => 'error',
		);
		
		$name  = ! empty( $request_data['name'] ) ? $request_data['name']  : '';
		$email = ! empty( $request_data['email'] ) ? $request_data['email']: '';
		$lists = ! empty( $request_data['lists'] ) ? $request_data['lists']: array();
		$list  = ! empty( $request_data['list'] ) ? $request_data['list']  : '';

		if ( is_email( $email ) ) {

			$url_params = array(
				'ig_es_external_action' => 'subscribe',
				'name'                  => $name,
				'email'                 => $email,
			);

			if ( ! empty( $lists ) ) {
				$url_params['lists'] = $lists;
			}

			if ( ! empty( $list ) ) {
				$url_params['list'] = $list;
			}

			$ip_address = ig_es_get_ip();
			if ( ! empty( $ip_address ) && 'UNKNOWN' !== $ip_address ) {
				$url_params['ip_address'] = $ip_address;
			}

			$ig_es_url = 'https://www.icegram.com/';
			$ig_es_url = add_query_arg( $url_params, $ig_es_url );

			// Make a get request.
			$api_response = wp_remote_get( $ig_es_url );
			if ( ! is_wp_error( $api_response ) ) {
				$body = ! empty( $api_response['body'] ) && ES_Common::is_valid_json( $api_response['body'] ) ? json_decode( $api_response['body'], true ) : '';
				if ( ! empty( $body ) ) {
					// If we have received an id in response then email is successfully queued at mailgun server.
					if ( ! empty( $body['status'] ) && 'SUCCESS' === $body['status'] ) {
						$response['status'] = 'success';
					} elseif ( ! empty( $body['status'] ) && 'ERROR' === $body['status'] ) {
						$response['status']       = 'error';
						$response['message']      = $body['message'];
						$response['message_text'] = $body['message_text'];
					}
				} else {
					$response['status'] = 'success';
				}
			} else {
				$response['status'] = 'error';
			}
		}

		return $response;
	}
}
