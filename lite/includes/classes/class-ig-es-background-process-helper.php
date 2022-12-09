<?php
/**
 * Helper class for background processing
 *
 * @since       4.6.3
 * @version     1.0.0
 *
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'IG_ES_Background_Process_Helper' ) ) {

	/**
	 * IG_ES_Background_Process_Helper Class.
	 */
	class IG_ES_Background_Process_Helper {

		/**
		 * Identifier
		 *
		 * @var mixed
		 */
		protected static $identifier;

		/**
		 * Variable to hold instance of IG_ES_Background_Process_Helper
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Contructor
		 *
		 * @since 4.6.3
		 */
		private function __construct() {

			self::$identifier = 'ig_es_background_process';
		}

		/**
		 * Get single instance of IG_ES_Background_Process_Helper
		 *
		 * @return IG_ES_Background_Process_Helper Singleton object of IG_ES_Background_Process_Helper
		 *
		 * @since 4.6.3
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Memory exceeded
		 *
		 * Ensures the batch process never exceeds 90%
		 * of the maximum WordPress memory.
		 *
		 * @return bool
		 *
		 * @since 4.6.3
		 */
		public static function memory_exceeded() {
			$memory_limit   = self::get_memory_limit() * 0.9; // 90% of max memory
			$current_memory = memory_get_usage( true );

			if ( $current_memory >= $memory_limit ) {
				return true;
			}

			return false;
		}

		/**
		 * Get memory limit.
		 *
		 * @return int
		 *
		 * @since 4.6.3
		 */
		public static function get_memory_limit() {
			if ( function_exists( 'ini_get' ) ) {
				$memory_limit = ini_get( 'memory_limit' );
			} else {
				// Sensible default.
				$memory_limit = '128M';
			}

			if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
				// Unlimited, set to 32GB.
				$memory_limit = '32G';
			}

			return wp_convert_hr_to_bytes( $memory_limit );
		}

		/**
		 * Time exceeded.
		 *
		 * Ensures the batch never exceeds a sensible time limit.
		 * A timeout limit of 30s is common on shared hosting.
		 *
		 * @param string $start_time start timestamp.
		 * @param float  $fraction Time fraction.
		 *
		 * @return bool
		 *
		 * @since 4.6.3
		 *
		 * @since Added $fraction parameter
		 */
		public static function time_exceeded( $start_time = 0, $fraction = 0.6 ) {

			$finish = $start_time + ( self::get_time_limit() * $fraction );
			$return = false;

			if ( time() >= $finish ) {
				$return = true;
			}

			return apply_filters( self::$identifier . '_time_exceeded', $return );
		}

		/**
		 * Method to get Server time limit
		 *
		 * @return int $time_limit Server time limit.
		 *
		 * @since  4.6.3
		 */
		public static function get_time_limit() {
			if ( function_exists( 'ini_get' ) ) {
				$time_limit = ini_get( 'max_execution_time' );
			} else {
				// Sensible default.
				$time_limit = apply_filters( self::$identifier . '_default_time_limit', 20 );
			}

			$time_limit = (int) $time_limit;
			return $time_limit;
		}

		/**
		 * Method to add new action scheduler task. Task added by this function are called by Action Scheduler library when execution time comes.
		 *
		 * @param string $action Action name.
		 * @param array  $action_args Action arguements.
		 * @param bool   $process_asynchronously Should process action asynchronously.
		 * @param bool   $should_wait Should wait before making asynchronous request to process the action.
		 *
		 * @return int|bool $action_id Action ID on success or false on failure.
		 *
		 * @since 4.6.3
		 */
		public static function add_action_scheduler_task( $action = '', $action_args = array(), $process_asynchronously = true, $should_wait = false, $time = 0 ) {

			if ( empty( $action ) ) {
				return false;
			}

			if ( function_exists( 'as_schedule_single_action' ) ) {
				$time      = ! empty( $time ) ? $time : time();
				$action_id = as_schedule_single_action( $time, $action, array( $action_args ), 'email-subscribers' );

				if ( ! empty( $action_id ) ) {
					if ( $process_asynchronously ) {
						$request_args = array(
							'action'    => 'ig_es_run_action_scheduler_task',
							'action_id' => $action_id,
						);
						self::send_async_ajax_request( $request_args, $should_wait );
					}
					return $action_id;
				}
			}

			return false;
		}

		/**
		 * Method to trigger immediate processing of action scheduler task.
		 *
		 * @since 4.6.3
		 */
		public static function run_action_scheduler_task() {

			$action_id = ig_es_get_request_data( 'action_id' );

			if ( ! empty( $action_id ) ) {
				if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
					$queue_runner = ActionScheduler_QueueRunner::instance();
					$queue_runner->process_action( $action_id, 'email-subscribers' );
				}
			}
		}

		/**
		 * Method to get required waiting time in seconds before making async request.
		 * Chaining async requests can crash MySQL. A brief waiting period in PHP helps in preventing that.
		 *
		 * @return int Waiting time in seconds.
		 *
		 * @since 4.6.3
		 */
		public static function get_wait_seconds() {
			return apply_filters( 'ig_es_async_request_wait_seconds', 3 );
		}

		/**
		 * Method to send asynchronous background request to admin-ajax.
		 *
		 * @param array $request_args Async request's arguments.
		 * @param bool  $should_wait Should wait before making this async request.
		 *
		 * @return array $response Async request's response.
		 *
		 * @since 4.6.3
		 */
		public static function send_async_ajax_request( $request_args = array(), $should_wait = false ) {

			$response = array();

			if ( empty( $request_args ) ) {
				return $response;
			}

			// Should wait before making async request.
			if ( $should_wait ) {
				$wait_seconds = self::get_wait_seconds();

				if ( $wait_seconds ) {

					// Sleep to prevent crashing of MYSQL due to chaining of async request.
					sleep( $wait_seconds );
				}
			}

			$admin_ajax_url = admin_url( 'admin-ajax.php' );
			$admin_ajax_url = add_query_arg( $request_args, $admin_ajax_url );
			$args           = array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'cookies'   => $_COOKIE,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			);

			$args = apply_filters( 'ig_es_async_request_args', $args );

			// Make a asynchronous request.
			$response = wp_remote_get( esc_url_raw( $admin_ajax_url ), $args );

			return $response;
		}
	}
}

