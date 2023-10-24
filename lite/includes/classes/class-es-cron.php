<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Cron {
	/**
	 * ES_Cron constructor.
	 *
	 * @since 4.0.0
	 * @since 4.3.5 Added ig_es_after_settings_save action
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( &$this, 'init' ), 1 );
		add_filter( 'cron_schedules', array( &$this, 'cron_schedules' ) );
		add_action( 'ig_es_plugin_deactivate', array( &$this, 'clear' ) );
		add_action( 'ig_es_after_settings_save', array( &$this, 'reschedule' ) );
	}

	/**
	 * Initialize Cron
	 *
	 * @since 4.3.1
	 */
	public function init() {
		add_action( 'ig_es_cron', array( &$this, 'hourly' ) );
		add_action( 'ig_es_cron_worker', array( &$this, 'handler' ), - 1 );

		if ( ! wp_next_scheduled( 'ig_es_cron' ) ) {
			$this->update( true );
		}

		$this->handle_cron_request();
		$this->handle_data_request();
	}

	/**
	 * Schedule/ Clear Cronjob
	 *
	 * @param bool $hourly_only
	 *
	 * @return bool
	 *
	 * @since 4.3.1
	 */
	public function update( $hourly_only = false ) {

		// Schedule Main Cron
		if ( ! wp_next_scheduled( 'ig_es_cron' ) ) {
			wp_schedule_event( strtotime( 'midnight' ) - 300, 'hourly', 'ig_es_cron' );

			return true;
		} elseif ( $hourly_only ) {
			return false;
		}

		// Don't want to use WP_CRON?
		if ( ! $this->is_wp_cron_enable() ) {
			$this->clear();

			return true;
		}

		$this->schedule();

		return false;
	}

	/**
	 * Is WP Cron enable?
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 */
	public function is_wp_cron_enable() {
		$ig_es_disable_wp_cron = get_option( 'ig_es_disable_wp_cron', 'no' );

		// Don't want to use WP_CRON?
		if ( 'yes' === $ig_es_disable_wp_cron ) {
			return false;
		}

		return true;
	}

	/**
	 * Reschedule Crons
	 *
	 * @since 4.3.5
	 */
	public function reschedule() {

		$this->clear();

		if ( $this->is_wp_cron_enable() ) {
			$this->schedule();
		}
	}

	/**
	 * Update Crons every hour
	 *
	 * @since 4.3.1
	 */
	public function hourly() {
		$this->update();
	}

	/**
	 * Schedule Events if it's not already scheduled
	 *
	 * @since 4.3.1
	 */
	public function schedule() {

		global $ig_es_tracker;
		$sending_service = new ES_Service_Email_Sending();

		// Add worker only once
		if ( ! wp_next_scheduled( 'ig_es_cron_auto_responder' ) ) {
			wp_schedule_event( floor( time() / 300 ) * 300 - 120, 'ig_es_cron_interval', 'ig_es_cron_auto_responder' );
		}

		if ( ! wp_next_scheduled( 'ig_es_cron_worker' ) ) {
			wp_schedule_event( floor( time() / 300 ) * 300, 'ig_es_cron_interval', 'ig_es_cron_worker' );
		}

		if ( ES_Service_Email_Sending::opted_for_sending_service() ) {
			$sending_service->schedule_ess_cron();
		}
		
		if ( ES()->is_pro() ) {

			if ( ! wp_next_scheduled('ig_es_calculate_engagement_score')) {
				$local_time = 'midnight';
				$timestamp  = strtotime( $local_time ) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
				wp_schedule_event( $timestamp , 'daily', 'ig_es_calculate_engagement_score');
			}

			if ( 'yes' === get_option( 'ig_es_enable_bounce_handling_feature', 'yes' ) ) {
				if ( ! wp_next_scheduled( 'ig_es_bounce_handler_cron_action' ) ) {
					wp_schedule_event( time(), 'daily', 'ig_es_bounce_handler_cron_action', array() );
				}
			}

			$is_woocommerce_active = $ig_es_tracker::is_plugin_activated( 'woocommerce/woocommerce.php' );
			if ( $is_woocommerce_active ) {

				if ( IG_ES_Abandoned_Cart_Options::is_cart_tracking_enabled() ) {

					if ( ! wp_next_scheduled( 'ig_es_wc_abandoned_cart_worker' ) ) {
						wp_schedule_event( floor( time() / 300 ) * 300, 'ig_es_two_minutes', 'ig_es_wc_abandoned_cart_worker' );
					}
				}

				// Cron job to detect WooCommerce products which are on sale.
				if ( ! wp_next_scheduled( 'ig_es_wc_products_on_sale_worker' ) ) {
					wp_schedule_event( floor( time() / 300 ) * 300, 'ig_es_fifteen_minutes', 'ig_es_wc_products_on_sale_worker' );
				}
			}

			$es_services = ES()->get_es_services();
			if ( ! empty( $es_services ) && in_array( 'list_cleanup', $es_services, true ) ) {
				$list_cleanup_cron_scheduled = wp_next_scheduled( 'ig_es_list_cleanup_worker' );
				if ( ! $list_cleanup_cron_scheduled ) {
					wp_schedule_event( floor( time() / 300 ) * 300, 'ig_es_monthly_interval', 'ig_es_list_cleanup_worker' );
				}
			}
		}

	}

	/**
	 * Clear all ES Cronjob
	 *
	 * @since 4.3.1
	 */
	public function clear() {
		wp_clear_scheduled_hook( 'ig_es_cron' );
		wp_clear_scheduled_hook( 'ig_es_cron_worker' );
		wp_clear_scheduled_hook( 'ig_es_cron_auto_responder' );

		$cron_url = $this->url();
		if ( ! empty( $cron_url ) ) {
			parse_str( $cron_url, $output );
			$guid = $output['guid'];
			wp_clear_scheduled_hook( 'ig_es_cron_fifteen_mins', array( 'cron', $guid ) );
		}
	}

	/**
	 * Lock Cron to avoid multiple execution of a cron
	 *
	 * @param int $key
	 *
	 * @return bool
	 *
	 * @since 4.3.1
	 */
	public function lock( $key = 0 ) {

		$process_id = get_option( 'ig_es_cron_lock_' . $key, false );

		if ( $process_id && $this->is_locked( $key ) ) {
			return $process_id;
		}

		// On some hosts getmypid is disabled due to security reasons.
		if ( function_exists( 'getmypid' ) ) {
			$process_id = @getmypid();
		} else {
			$process_id = wp_rand();
		}

		update_option( 'ig_es_cron_lock_' . $key, $process_id, false );

		return true;
	}

	/**
	 * Unlock Cron
	 *
	 * @param int $key
	 *
	 * @since 4.3.1
	 */
	public function unlock( $key = 0 ) {
		update_option( 'ig_es_cron_lock_' . $key, false, false );
	}

	/**
	 * Should Unlock Cron?
	 *
	 * @param bool $force
	 *
	 * @return bool
	 *
	 * @since 4.3.3
	 */
	public function should_unlock( $force = false ) {

		if ( $force ) {
			return true;
		}

		$cron_last_hit = $this->get_last_hit();

		// Initially we don't have timetamp data. So, set as 900 to unlock cron lock
		$time_lapsed = isset( $cron_last_hit['timestamp'] ) ? ( round( time() - $cron_last_hit['timestamp'] ) ) : 900;

		return $time_lapsed > ( 10 * MINUTE_IN_SECONDS );
	}


	/**
	 * Check If Cron Locked
	 *
	 * @param $key
	 *
	 * @return bool
	 *
	 * @since 4.3.1
	 */
	public function is_locked( $key = 0 ) {
		global $wpdb;

		$lock = 'ig_es_cron_lock_' . $key . '%';

		$res = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}options WHERE option_name LIKE %s AND option_value != ''", $lock ) );

		return ! ! $res;
	}

	/**
	 * Set interval for Email Subscribers Cronjob
	 *
	 * @return mixed
	 *
	 * @since 4.3.1
	 * @since 4.3.2 Changed name from filter_cron_schedules to cron_schedules
	 */
	public function cron_schedules( $schedules = array() ) {
		$es_schedules = array(
			'ig_es_cron_interval'   => array(
				'interval' => $this->get_cron_interval(),
				'display'  => __( 'Icegram Express Cronjob Interval', 'email-subscribers' ),
			),
			'ig_es_two_minutes'     => array(
				'interval' => 2 * MINUTE_IN_SECONDS,
				'display'  => __( 'Two minutes', 'email-subscribers' ),
			),
			'ig_es_fifteen_minutes' => array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => __( 'Fifteen minutes', 'email-subscribers' ),
			),
			'ig_es_monthly_interval' => array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Monthly', 'email-subscribers' ),
			),
		);

		$schedules = array_merge( $schedules, $es_schedules );

		return $schedules;
	}

	/**
	 * Get Cron Interval
	 *
	 * @return int
	 *
	 * @since 4.3.5
	 */
	public function get_cron_interval() {
		$cron_interval = (int) get_option( 'ig_es_cron_interval', IG_ES_CRON_INTERVAL );

		if ( $cron_interval <= 0 ) {
			$cron_interval = IG_ES_CRON_INTERVAL;
		}

		return $cron_interval;
	}

	/**
	 * Get available cron intervals
	 *
	 * @return array
	 *
	 * @since 4.3.5
	 */
	public function cron_intervals() {

		return array(
			600  => __( '10 minutes', 'email-subscribers' ),
			900  => __( '15 minutes', 'email-subscribers' ),
			1200 => __( '20 minutes', 'email-subscribers' ),
			1500 => __( '25 minutes', 'email-subscribers' ),
			1800 => __( '30 minutes', 'email-subscribers' ),
		);

	}

	/**
	 * Get Cron URL
	 *
	 * @param bool   $self
	 * @param bool   $pro
	 * @param string $campaign_hash
	 *
	 * @return mixed|string|void
	 *
	 * @since 4.3.1
	 */
	public function url( $self = false, $pro = false, $campaign_hash = '' ) {

		$cron_url = get_option( 'ig_es_cronurl', '' );

		$result = array();
		if ( ! empty( $cron_url ) ) {
			parse_str( $cron_url, $result );
		}

		// Adding site url with a trailing slash
		$site_url = trailingslashit( site_url() );

		$cron_url = add_query_arg( 'es', 'cron', $site_url );
		if ( empty( $result['guid'] ) ) {
			$guid = ES_Common::generate_guid();
		} else {
			$guid = $result['guid'];
		}

		$cron_url = add_query_arg( 'guid', $guid, $cron_url );
		update_option( 'ig_es_cronurl', $cron_url );

		if ( ! empty( $campaign_hash ) ) {
			$cron_url = add_query_arg( 'campaign_hash', $campaign_hash, $cron_url );
		}

		if ( $self ) {
			$cron_url = add_query_arg( 'self', true, $cron_url );
			$nonce    = wp_create_nonce( 'ig_es_self_cron' );
			$cron_url = add_query_arg( '_wpnonce', $nonce, $cron_url );
		}

		if ( $pro ) {
			$cron_url = add_query_arg( 'es_pro', true, $cron_url );
		}

		return $cron_url;
	}

	/**
	 * Set Cron Last Hit data
	 *
	 * @return bool
	 *
	 * @since 4.3.3
	 *
	 * @modify 5.4.5
	 */
	public function set_last_hit() {

		$last_hit = get_option( 'ig_es_cron_last_hit', array() );
		$last_hit['timestamp'] = time();

		if ( isset( $_SERVER['HTTP_X_ES_EMAIL_SENDING_LIMIT'] ) ) {
			$last_hit['icegram_timestamp'] = time();
		}

		return update_option( 'ig_es_cron_last_hit', $last_hit );
	}

	/**
	 * Get Cron Last Hit data
	 *
	 * @return mixed|void
	 *
	 * @since 4.3.3
	 */
	public function get_last_hit() {
		return get_option( 'ig_es_cron_last_hit', array() );
	}

	/**
	 * Handler
	 *
	 * @return bool
	 *
	 * @since 4.3.1
	 */
	public function handler() {

		if ( defined( 'IG_ES_DOING_CRON' ) || defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || defined( 'WP_INSTALLING' ) || defined( 'MAILSTER_DO_UPDATE' ) ) {
			return false;
		}

		define( 'IG_ES_DOING_CRON', microtime( true ) );

		register_shutdown_function( array( &$this, 'shutdown' ) );
	}

	/**
	 * Handle Shutdown event
	 *
	 * @since 4.3.1
	 */
	public function shutdown() {

		if ( ! defined( 'IG_ES_DOING_CRON' ) ) {
			return;
		}

		// Unlock Cron Lock
		$this->unlock();
	}

	/**
	 * Handle Cron Request
	 *
	 * @since 4.0.0
	 *
	 * @modify 5.4.5.
	 */
	public function handle_cron_request() {

		$execution_start_time = microtime( true );

		$es_request = ig_es_get_request_data( 'es' );
		$guid       = ig_es_get_request_data( 'guid' );

		// It's not a cron request . Say Goodbye!
		if ( 'cron' !== $es_request ) {
			return;
		}

		$self = ig_es_get_request_data( 'self', 0 );

		$verified_self = false;
		if ( 1 == $self && wp_verify_nonce( ig_es_get_request_data( '_wpnonce' ), 'ig_es_self_cron' ) ) {
			$verified_self = true;
		}

		if ( 0 == $self || $verified_self ) {

			if ( ! empty( $guid ) ) {

				$response = array(
					'status'                   => 'SUCCESS',
					'es_remaining_email_count' => 100,
				);

				$es_process_request = true;

				// filter request
				$es_process_request = apply_filters( 'ig_es_email_sending_limit', $es_process_request );

				if ( true === $es_process_request ) {
					$security1             = strlen( $guid );
					$es_c_cronguid_noslash = str_replace( '-', '', $guid );
					$security2             = strlen( $es_c_cronguid_noslash );
					if ( 34 == $security1 && 30 == $security2 ) {
						if ( ! preg_match( '/[^a-z]/', $es_c_cronguid_noslash ) ) {
							$cron_url = ES()->cron->url();

							parse_str( $cron_url, $output );

							// Now, all check pass.
							if ( $guid === $output['guid'] ) {

								// Should I unlock cron?
								if ( $this->should_unlock( $verified_self ) ) {
									$this->unlock();
								}

								if ( ! $this->is_locked() ) {

									// Set Last Hit time.
									$this->set_last_hit();

									// Release WP_CRON if it should
									if ( wp_next_scheduled( 'ig_es_cron' ) - $execution_start_time < 0 ) {
										spawn_cron();
									}

									// Lock Cron to avoid duplicate
									$this->lock();

									// Queue Auto Responder
									do_action( 'ig_es_cron_auto_responder' );

									// Worker
									do_action( 'ig_es_cron_worker' );

									$response['total_emails_sent']        = get_transient( 'ig_es_total_emails_sent' );
									$response['es_remaining_email_count'] = get_transient( 'ig_es_remaining_email_count' );
									$response['message']                  = 'EMAILS_SENT';
									$response['status']                   = 'SUCCESS';

									// Unlock it.
									$this->unlock();
								} else {
									$response['status']  = 'ERROR';
									$response['message'] = 'CRON_LOCK_ENABLED';
								}
							} else {
								$self                = false;
								$response['status']  = 'ERROR';
								$response['message'] = 'CRON_GUID_DOES_NOT_MATCH';
							}
						} else {
							$self                = false;
							$response['status']  = 'ERROR';
							$response['message'] = 'CRON_GUID_PATTERN_DOES_NOT_MATCH';
						}
					} else {
						$self                = false;
						$response['status']  = 'ERROR';
						$response['message'] = 'INVALID_CRON_GUID';
					}
				} else {
					$self                = false;
					$response['status']  = 'ERROR';
					$response['message'] = 'DO_NOT_PROCESS_REQUEST';
				}
			} else {
				$self                = false;
				$response['status']  = 'ERROR';
				$response['message'] = 'EMPTY_CRON_GUID';
			}
		} else {
			$response['es_remaining_email_count'] = 0;
			$response['message']                  = 'PLEASE_TRY_AGAIN_LATER';
			$response['status']                   = 'ERROR';
		}

		if ( $self ) {
			$total_emails_sent       = ! empty( $response['total_emails_sent'] ) ? $response['total_emails_sent'] : 0;
			$status                  = ! empty( $response['status'] ) ? $response['status'] : 'ERROR';
			$total_emails_to_be_sent = ! empty( $response['es_remaining_email_count'] ) ? $response['es_remaining_email_count'] : 0;
			$cron_url                = ES()->cron->url( true );

			if ( 'SUCCESS' === $status ) {
				$message = __( sprintf( 'Email(s) sent successfully!' ), 'email-subscribers' );
			} else {
				$message = $this->get_status_messages( $response['message'] );
			}

			include ES_PLUGIN_DIR . 'lite/public/partials/cron-message.php';
			die();
		} else {
			echo json_encode( $response );
			die();
		}
	}

	/**
	 * Handle Data Request
	 *
	 * @since 4.6.6
	 */
	public function handle_data_request() {
		$es_request = ig_es_get_request_data( 'es' );

		// It's not a cron request . Say Goodbye!
		if ('get_info' !== $es_request ) {
			return;
		}

		$guid = ig_es_get_request_data( 'guid' );

		$is_valid_request = $this->is_valid_request( $guid );

		$response = array();
		if ( $is_valid_request ) {

			$allow_tracking = apply_filters( 'ig_es_allow_tracking', '' );

			if ( 'yes' === $allow_tracking ) {

				/*
				 * Trial start date
				 * Total # Contacts they have
				 * Total # lists they have
				 * Some of ES settings like enable track opens, track clicks etc
				 * Uninstall Date
				 */

				$response = apply_filters( 'ig_es_tracking_data', array() );
			}
		}

		echo json_encode( $response );
		die();
	}

	/**
	 * Get Status Message
	 *
	 * @param string $message
	 *
	 * @return mixed|string
	 *
	 * @since 4.0.0
	 */
	public function get_status_messages( $message = '' ) {

		if ( empty( $message ) ) {
			return '';
		}

		$status_messages = array(
			'EMAILS_SENT'                      => __( 'Emails sent successfully!', 'email-subscribers' ),
			'EMAILS_NOT_FOUND'                 => __( 'Emails not found.', 'email-subscribers' ),
			'NOTIFICATION_NOT_FOUND'           => __( 'No notifications found to send.', 'email-subscribers' ),
			'CRON_GUID_DOES_NOT_MATCH'         => __( 'Invalid GUID.', 'email-subscribers' ),
			'CRON_GUID_PATTERN_DOES_NOT_MATCH' => __( 'Invalid GUID.', 'email-subscribers' ),
			'INVALID_CRON_GUID'                => __( 'Invalid GUID.', 'email-subscribers' ),
			'DO_NOT_PROCESS_REQUEST'           => __( 'Not allowed to process request.', 'email-subscribers' ),
			'EMPTY_CRON_GUID'                  => __( 'GUID is empty.', 'email-subscribers' ),
			'PLEASE_TRY_AGAIN_LATER'           => __( 'Please try after sometime.', 'email-subscribers' ),
			'EMAIL_SENDING_LIMIT_EXCEEDED'     => __( 'You have hit your hourly email sending limit. Please try after sometime.', 'email-subscribers' ),
			'CRON_LOCK_ENABLED'                => __( 'Cron lock enabled. Please try after sometime.', 'email-subscribers' ),
		);

		$message_text = ! empty( $status_messages[ $message ] ) ? $status_messages[ $message ] : '';

		return $message_text;

	}

	/**
	 * Is valid request
	 *
	 * @param string $guid
	 *
	 * @return bool
	 *
	 * @since 4.6.6
	 */
	public function is_valid_request( $guid = '' ) {

		$security1             = strlen( $guid );
		$es_c_cronguid_noslash = str_replace( '-', '', $guid );
		$security2             = strlen( $es_c_cronguid_noslash );
		if ( 34 == $security1 && 30 == $security2 ) {
			if ( ! preg_match( '/[^a-z]/', $es_c_cronguid_noslash ) ) {
				$cron_guid = $this->get_cron_guid();
				// Now, all check pass.
				if ( ! empty( $cron_guid ) && $guid === $cron_guid ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get cron guid
	 *
	 * @return string $guid
	 *
	 * @since 4.7.7
	 */
	public function get_cron_guid() {

		$cron_url = ES()->cron->url();
		parse_str( $cron_url, $result );
		$guid = ! empty( $result['guid'] ) ? $result['guid'] : '';

		return $guid;
	}

	/**
	 * Method to get list of cron jobs being used in the plugin
	 *
	 * @return array $es_cron_jobs List of cron jobs used in the plugin
	 *
	 * @since 4.6.4
	 */
	public function get_cron_jobs_list() {

		$es_cron_jobs = array(
			'ig_es_cron',
			'ig_es_cron_worker',
			'ig_es_cron_auto_responder',
		);

		return $es_cron_jobs;
	}
}
