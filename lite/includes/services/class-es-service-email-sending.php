<?php

class ES_Service_Email_Sending extends ES_Services {

	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
	protected static $instance = null;

	/**
	 * Added Logger Context
	 *
	 * @since 4.6.0
	 * @var array
	 */
	protected static $logger_context = array(
		'source' => 'ig_es_ess_onboarding',
	);

	/**
	 * API URL
	 *
	 * @since 4.6.0
	 * @var string
	 */
	public $api_url = 'https://api.igeml.com/';

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @since 4.6.1
	 */
	public $cmd = 'accounts/register';

	/**
	 * Variable to hold all onboarding tasks list.
	 * 
	 * UPDATE : Added ess cron scheduling in 5.6.11
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $all_onboarding_tasks = array(
		'configuration_tasks' => array(
			'create_ess_account',
			'set_sending_service_consent',
			'schedule_ess_cron',			
		),
		'email_delivery_check_tasks' => array(
			'dispatch_emails_from_server',
			'check_test_email_on_server',
		),
		'completion_tasks' => array(
			'complete_ess_onboarding',
		),
	);

	/**
	 * Option name for current task name.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_current_task_option = 'ig_es_ess_onboarding_current_task';

	/**
	 * Option name which holds common data between tasks.
	 *
	 * E.g. created subscription form id from create_default_subscription_form function so we can use it in add_widget_to_sidebar
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_data_option = 'ig_es_ess_onboarding_tasks_data';

	/**
	 * Option name which holds tasks which are done.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_done_option = 'ig_es_ess_onboarding_tasks_done';

	/**
	 * Option name which holds tasks which are failed.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_failed_option = 'ig_es_ess_onboarding_tasks_failed';

	/**
	 * Option name which holds tasks which are skipped due to dependency on other tasks.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_skipped_option = 'ig_es_ess_onboarding_tasks_skipped';

	/**
	 * Option name which store the step which has been completed.
	 *
	 * @since 4.6.0
	 * @var string
	 */
	private static $onboarding_step_option = 'ig_es_ess_onboarding_step';

	/**
	 * ES_Service_Email_Sending constructor.
	 *
	 * @since 4.6.1
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_ig_es_setup_email_sending_service', array( $this, 'setup_email_sending_service' ) );
		add_action( 'ig_es_ess_update_account', array( $this, 'send_plan_data_to_ess') );
		add_action( 'ig_es_message_sent', array( $this, 'update_sending_service_status' ) );
		// We are marking sending service status as failed only when we can't send a campaign after trying 3 times.
		// This will be helpful in avoiding temporary failure errors due to network calls/site load on ESS end.
		add_action( 'ig_es_campaign_failed', array( $this, 'update_sending_service_status' ) );
	}

	/**
	 * Register the JavaScript for ES gallery.
	 */
	public function enqueue_scripts() {

		$current_page = ig_es_get_request_data( 'page' );

		if ( in_array( $current_page, array( 'es_dashboard' ), true ) ) {
			wp_register_script( 'ig-es-sending-service-js', ES_PLUGIN_URL . 'lite/admin/js/sending-service.js', array( 'jquery' ), ES_PLUGIN_VERSION, true );
			wp_enqueue_script( 'ig-es-sending-service-js' );
			$onboarding_data                  = $this->get_onboarding_data();
			$onboarding_data['next_task']     = $this->get_next_onboarding_task();
			$onboarding_data['error_message'] = __( 'An error occured. Please try again later.', 'email-subscribers' );
			wp_localize_script( 'ig-es-sending-service-js', 'ig_es_ess_onboarding_data', $onboarding_data );
		}
	}

	/**
	 * Method to perform configuration and list, ES form, campaigns creation related operations in the onboarding
	 *
	 * @since 4.6.0
	 */
	public function ajax_perform_configuration_tasks() {

		$step = 2;
		$this->update_onboarding_step( $step );
		return $this->perform_onboarding_tasks( 'configuration_tasks' );
	}

	

	public function setup_email_sending_service() {
		$response = array(
			'status' => 'error',
		);

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$request = ig_es_get_request_data( 'request' );

		if ( ! empty( $request ) ) {
			$callback = 'ajax_' . $request;
			if ( is_callable( array( $this, $callback ) ) ) {
				$response = call_user_func( array( $this, $callback ) );
			}
		}

		wp_send_json( $response );
	}

	public function create_ess_account() {

		global $ig_es_tracker;

		$response = array(
			'status' => 'error',
		);

		if ( $ig_es_tracker::is_dev_environment() ) {
			$response['message'] = __( 'Email sending service is not supported on local or dev environments.', 'email-subscribers' );
			return $response;
		}

		$plan = $this->get_plan();
		$from_email = get_option( 'ig_es_from_email' );
		$home_url   = home_url();
		$parsed_url = parse_url( $home_url );
		$domain     = ! empty( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		if ( empty( $domain ) ) {
			$response['message'] = __( 'Site url is not valid. Please check your site url.', 'email-subscribers' );
			return $response;
		}

		$email = get_option( 'admin_email' );
		$limit = 100;

		if ( ES()->is_premium() ) {
			$account_data = get_option( '_icegram_connector_data' );
			$email        = ! empty( $account_data['email'] ) ? $account_data['email'] : $email;
			$limit        = ! empty( $account_data['sending_limit'] ) ? $account_data['sending_limit'] : $limit;
		}

		$from_name = ES()->mailer->get_from_name();

		$data = array(
			'limit'      => $limit,
			'domain'     => $domain,
			'email'      => $email,
			'from_email' => $from_email,
			'from_name'  => $from_name,
			'plan'		 => $plan,
		);

		$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => $data,
		);

		$request_response = $this->send_request( $options, 'POST', false );
		if ( ! empty( $request_response['account_id'] ) ) {
			$account_id      = $request_response['account_id'];
			$api_key         = $request_response['api_key'];
			$allocated_limit = $request_response['allocated_limit'];
			$internval       = $request_response['interval'];
			$from_email      = $request_response['from_email'];
			$plan			 = $request_response['plan'];

			$ess_data = array(
				'account_id'      => $account_id,
				'allocated_limit' => $allocated_limit,
				'interval'        => $internval,
				'api_key'         => $api_key,
				'from_email'      => $from_email,
				'plan'			  => $plan,
			);

			update_option( 'ig_es_ess_data', $ess_data );
			$response['status'] = 'success';
		} else {
			$response['message'] = ! empty( $request_response['message'] ) ? $request_response['message'] : __( 'An error has occured while creating your account. Please try again later', 'email-subscribers' );
		}

		return $response;
	}

	public function set_sending_service_consent() {

		$response = array(
			'status' => 'error',
		);

		update_option( 'ig_es_ess_opted_for_sending_service', 'yes', 'no' );
		update_option( 'ig_es_ess_status', 'success' );

		$response['status'] = 'success';
		
		return $response;
	}

	/**
	 * Method to perform give onboarding tasks types.
	 *
	 * @param string $task_group Tasks group
	 * @param string $task_name Specific task
	 *
	 * @since 4.6.0
	 */
	public function perform_onboarding_tasks( $task_group = '', $task_name = '' ) {

		$response = array(
			'status' => '',
			'tasks'  => array(),
		);

		$logger     = get_ig_logger();
		$task_group = ! empty( $task_group ) ? $task_group : 'configuration_tasks';

		$all_onboarding_tasks = self::$all_onboarding_tasks;

		$current_tasks = array();
		if ( ! empty( $all_onboarding_tasks[ $task_group ] ) ) {
			// Get specific task else all tasks in a group.
			if ( ! empty( $task_name ) ) {
				$task_index = array_search( $task_name, $all_onboarding_tasks[ $task_group ], true );
				if ( false !== $task_index ) {
					$current_tasks = array( $task_name );
				}
			} else {
				$current_tasks = $all_onboarding_tasks[ $task_group ];
			}
		}

		$onboarding_tasks_done = get_option( self::$onboarding_tasks_done_option, array() );
		$current_tasks_done    = ! empty( $onboarding_tasks_done[ $task_group ] ) ? $onboarding_tasks_done[ $task_group ] : array();

		$onboarding_tasks_failed = get_option( self::$onboarding_tasks_failed_option, array() );
		$current_tasks_failed    = ! empty( $onboarding_tasks_failed[ $task_group ] ) ? $onboarding_tasks_failed[ $task_group ] : array();

		$onboarding_tasks_skipped = get_option( self::$onboarding_tasks_skipped_option, array() );
		$current_tasks_skipped    = ! empty( $onboarding_tasks_skipped[ $task_group ] ) ? $onboarding_tasks_skipped[ $task_group ] : array();

		$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
		if ( ! empty( $current_tasks ) ) {
			foreach ( $current_tasks as $current_task ) {
				if ( ! in_array( $current_task, $current_tasks_done, true ) ) {

					if ( $this->is_required_tasks_completed( $current_task ) ) {
						if ( is_callable( array( $this, $current_task ) ) ) {
							$logger->info( 'Doing Task:' . $current_task, self::$logger_context );
	
							// Call callback function.
							$task_response = call_user_func( array( $this, $current_task ) );
							if ( 'success' === $task_response['status'] ) {
								if ( ! empty( $task_response['tasks_data'] ) ) {
									if ( ! isset( $onboarding_tasks_data[ $current_task ] ) ) {
										$onboarding_tasks_data[ $current_task ] = array();
									}
									$onboarding_tasks_data[ $current_task ] = array_merge( $onboarding_tasks_data[ $current_task ], $task_response['tasks_data'] );
								}
								$logger->info( 'Task Done:' . $current_task, self::$logger_context );
								// Set success status only if not already set else it can override error/skipped statuses set previously from other tasks.
								if ( empty( $response['status'] ) ) {
									$response['status'] = 'success';
								}
								$current_tasks_done[] = $current_task;
							} elseif ( 'skipped' === $task_response['status'] ) {
								$response['status']      = 'skipped';
								$current_tasks_skipped[] = $current_task;
							} else {
								$logger->info( 'Task Failed:' . $current_task, self::$logger_context );
								$response['status']     = 'error';
								$current_tasks_failed[] = $current_task;
							}
	
							$response['tasks'][ $current_task ] = $task_response;
	
							$onboarding_tasks_done[ $task_group ]    = $current_tasks_done;
							$onboarding_tasks_failed[ $task_group ]  = $current_tasks_failed;
							$onboarding_tasks_skipped[ $task_group ] = $current_tasks_skipped;
	
							update_option( self::$onboarding_tasks_done_option, $onboarding_tasks_done );
							update_option( self::$onboarding_tasks_failed_option, $onboarding_tasks_failed );
							update_option( self::$onboarding_tasks_skipped_option, $onboarding_tasks_skipped );
							update_option( self::$onboarding_tasks_data_option, $onboarding_tasks_data );
							update_option( self::$onboarding_current_task_option, $current_task );
						} else {
							$logger->info( 'Missing Task:' . $current_task, self::$logger_context );
						}
					} else {
						$response['status']      = 'skipped';
						$current_tasks_skipped[] = $current_task;
					}
				} else {
					$response['tasks'][ $current_task ] = array(
						'status' => 'success',
					);
					$logger->info( 'Task already done:' . $current_task, self::$logger_context );
				}
			}
		}

		return $response;
	}

	/**
	 * Method to get next task for onboarding.
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public function get_next_onboarding_task() {
		$all_onboarding_tasks = self::$all_onboarding_tasks;
		$current_task         = get_option( self::$onboarding_current_task_option, '' );

		// Variable to hold tasks list without any grouping.
		$onboarding_tasks = array();
		foreach ( $all_onboarding_tasks as $task_group => $grouped_tasks ) {
			foreach ( $grouped_tasks as $task ) {
				$onboarding_tasks[] = $task;
			}
		}

		$next_task = '';
		if ( ! empty( $current_task ) ) {
			$current_task_index = array_search( $current_task, $onboarding_tasks, true );
			if ( ! empty( $current_task_index ) ) {

				$next_task_index = $current_task_index + 1;
				$next_task       = ! empty( $onboarding_tasks[ $next_task_index ] ) ? $onboarding_tasks[ $next_task_index ] : '';

				// Check if previous required tasks are completed then only return next task else return blank task.
				if ( ! $this->is_required_tasks_completed( $next_task ) ) {
					$next_task = '';
				}
			}
		}

		return $next_task;
	}

	/**
	 * Method to get the onboarding data options used in onboarding process.
	 *
	 * @since 4.6.0
	 */
	public function get_onboarding_data_options() {

		$onboarding_options = array(
			self::$onboarding_tasks_done_option,
			self::$onboarding_tasks_failed_option,
			self::$onboarding_tasks_data_option,
			self::$onboarding_tasks_skipped_option,
			self::$onboarding_step_option,
			self::$onboarding_current_task_option,
		);

		return $onboarding_options;
	}

	/**
	 * Method to get saved onboarding data.
	 *
	 * @since 4.6.0
	 */
	public function get_onboarding_data() {

		$onboarding_data = array();

		$onboarding_options = $this->get_onboarding_data_options();

		foreach ( $onboarding_options as $option ) {
			$option_data                = get_option( $option );
			$onboarding_data[ $option ] = $option_data;
		}

		return $onboarding_data;
	}

	/**
	 * Method to get the current onboarding step
	 *
	 * @return int $onboarding_step Current onboarding step.
	 *
	 * @since 4.6.0
	 */
	public static function get_onboarding_step() {
		$onboarding_step = (int) get_option( self::$onboarding_step_option, 1 );
		return $onboarding_step;
	}

	/**
	 * Method to updatee the onboarding step
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
	public static function update_onboarding_step( $step = 1 ) {
		if ( ! empty( $step ) ) {
			update_option( self::$onboarding_step_option, $step );
			return true;
		}

		return false;
	}

	/**
	 * Method to check if onboarding is completed
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public static function ajax_complete_ess_onboarding() {
		$response       = array();
		$option_updated = update_option( 'ig_es_ess_onboarding_complete', 'yes', false );
		if ( $option_updated ) {
			$response['html']   = self::get_account_overview_html();
			$response['status'] = 'success';
		}
		return $response;
	}

	public static function get_account_overview_html() {
		$current_date        = ig_es_get_current_date();
		$service_status      = self::get_sending_service_status();
		$ess_data            = get_option( 'ig_es_ess_data', array() );
		$used_limit          = isset( $ess_data['used_limit'][$current_date] ) ? $ess_data['used_limit'][$current_date]: 0;
		$allocated_limit     = isset( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit']                    : 0;
		$interval            = isset( $ess_data['interval'] ) ? $ess_data['interval']                                  : '';
		$current_mailer_name = ES()->mailer->get_current_mailer_name();
		$settings_url        = admin_url( 'admin.php?page=es_settings' );

		ob_start();
		ES_Admin::get_view(
			'dashboard/ess-account-overview',
			array(
				'service_status'      => $service_status,
				'allocated_limit'     => $allocated_limit,
				'used_limit'          => $used_limit,
				'interval'            => $interval,
				'current_mailer_name' => $current_mailer_name,
				'settings_url'        => $settings_url,
			)
		);
		$account_overview_html = ob_get_clean();
		return $account_overview_html;
	}

	/**
	 * Method to check if onboarding is completed
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public static function is_onboarding_completed() {

		$onboarding_complete = get_option( 'ig_es_ess_onboarding_complete', 'no' );

		if ( 'yes' === $onboarding_complete ) {
			return true;
		}

		return false;
	}

	/**
	 * Method to check if all required task has been completed.
	 *
	 * @param string $task_name Task name.
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
	public function is_required_tasks_completed( $task_name = '' ) {

		if ( empty( $task_name ) ) {
			return false;
		}

		$required_tasks = $this->get_required_tasks( $task_name );

		// If there are not any required tasks which means this task can run without any dependency.
		if ( empty( $required_tasks ) ) {
			return true;
		}

		$done_tasks = get_option( self::$onboarding_tasks_done_option, array() );

		// Variable to hold list of all done tasks without any grouping.
		$all_done_tasks         = array();
		$is_required_tasks_done = false;
		if ( ! empty( $done_tasks ) ) {
			foreach ( $done_tasks as $task_group => $grouped_tasks ) {
				foreach ( $grouped_tasks as $task ) {
					$all_done_tasks[] = $task;
				}
			}
		}

		$remaining_required_tasks = array_diff( $required_tasks, $all_done_tasks );

		// Check if there are not any required tasks remaining.
		if ( empty( $remaining_required_tasks ) ) {
			$is_required_tasks_done = true;
		}

		return $is_required_tasks_done;
	}

	/**
	 * Method to get lists of required tasks which should be completed successfully for this task.
	 *
	 * @return array $required_tasks List of required tasks.
	 */
	public function get_required_tasks( $task_name = '' ) {

		if ( empty( $task_name ) ) {
			return array();
		}

		$required_tasks_mapping = array(
			'set_sending_service_consent' => array(
				'create_ess_account',
			),
			'schedule_ess_cron' => array(
				'create_ess_account',
			),
			'dispatch_emails_from_server' => array(
				'set_sending_service_consent',
			),
			'check_test_email_on_server' => array(
				'dispatch_emails_from_server',
			),
		);

		$required_tasks = ! empty( $required_tasks_mapping[ $task_name ] ) ? $required_tasks_mapping[ $task_name ] : array();

		return $required_tasks;
	}

	/**
	 * Method to perform email delivery tasks.
	 *
	 * @since 4.6.0
	 */
	public function ajax_dispatch_emails_from_server() {
		return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'dispatch_emails_from_server' );
	}

	/**
	 * Method to perform email delivery tasks.
	 *
	 * @since 4.6.0
	 */
	public function ajax_check_test_email_on_server() {

		return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'check_test_email_on_server' );
	}

	/**
	 * Method to send default broadcast campaign.
	 *
	 * @since 4.6.0
	 */
	public function dispatch_emails_from_server() {

		$response = array(
			'status' => 'error',
		);

		$service = new ES_Send_Test_Email();
		$result  = $service->send_test_email();
		if ( ! empty( $result['status'] ) && 'SUCCESS' === $result['status'] ) {
			$response['status'] = 'success';
		}
		
		return $response;
	}

	/**
	 * Method to check if test email is received on Icegram servers.
	 *
	 * @since 4.6.0
	 */
	public function check_test_email_on_server() {

		$response = array(
			'status' => 'error',
		);

		$onboarding_tasks_failed           = get_option( self::$onboarding_tasks_failed_option, array() );
		$email_delivery_check_tasks_failed = ! empty( $onboarding_tasks_failed['email_delivery_check_tasks'] ) ? $onboarding_tasks_failed['email_delivery_check_tasks'] : array();

		$task_failed = in_array( 'dispatch_emails_from_server', $email_delivery_check_tasks_failed, true );

		// Peform test email checking if dispatch_emails_from_server task hasn't failed.
		if ( ! $task_failed ) {
			$service  = new ES_Email_Delivery_Check();
			$response = $service->test_email_delivery();
		} else {
			$response['status'] = 'failed';
		}

		return $response;
	}

	public function schedule_ess_cron() {
		$response = array(
			'status' => 'error',
		);

		if ( ! wp_next_scheduled( 'ig_es_ess_update_account') ) {
			wp_schedule_event( time(), 'daily', 'ig_es_ess_update_account' );
		}

		$response['status'] = 'success';
		return $response;
	}

	public function clear_ess_cron() {
		wp_clear_scheduled_hook('ig_es_ess_update_account');
	}

	public function send_plan_data_to_ess() {

		$response = array(
			'status' => 'error',
		);

		if ( !$this->opted_for_sending_service() ) {
			$this -> clear_ess_cron();
			return;
		}

		$ess_data = get_option( 'ig_es_ess_data', array() );
		$api_key  = $ess_data['api_key'];
		$current_plan = $this -> get_plan();
		
		// Update account if plan is not registered or it has changed
		if ( !empty( $ess_data['plan'] ) && $ess_data['plan'] == $current_plan ) {
			return;
		}

		$data = array(
			'plan'   => $current_plan,
		);

		$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => json_encode($data),
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,// Keep it like bearer when we send email
				'Content-Type'  => 'application/json',
			),
		);

		$api_url = 'https://api.igeml.com/accounts/update/';

		$response = wp_remote_post( $api_url, $options );

		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = ( array ) json_decode( $response_body );
			if ( 'success' === $response_data['status'] ) {
				$ess_data['plan'] = $this->get_plan();
				update_option( 'ig_es_ess_data', $ess_data );
			}
		}
	}

	public static function update_used_limit( $sent_count = 0 ) {
		$ess_data     = get_option( 'ig_es_ess_data', array() );
		$current_date = ig_es_get_current_date();
		$used_limit   = ! empty( $ess_data['used_limit'][$current_date] ) ? $ess_data['used_limit'][$current_date] : 0;
		$used_limit  += $sent_count;
		
		$ess_data['used_limit'][$current_date] = $used_limit;
		update_option( 'ig_es_ess_data', $ess_data );
	}

	public static function get_remaining_limit() {
		$ess_data        = get_option( 'ig_es_ess_data', array() );
		$current_date    = ig_es_get_current_date();
		$allocated_limit = ! empty( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit'] : 0;
		$used_limit      = ! empty( $ess_data['used_limit'][$current_date] ) ? $ess_data['used_limit'][$current_date] : 0;
		$remaining_limit = $allocated_limit - $used_limit;
		return $remaining_limit;
	}

	public static function use_icegram_mailer() {
		$use_icegram_mailer = false;
		if ( self::opted_for_sending_service() ) {
			$remaining_limit = self::get_remaining_limit();
			if ( $remaining_limit > 0 ) {
				$use_icegram_mailer = true;
			}
		}
		return $use_icegram_mailer;
	}

	public static function opted_for_sending_service() {
		$opted_for_sending_service = get_option( 'ig_es_ess_opted_for_sending_service', 'no' );
		return 'yes' === $opted_for_sending_service;
	}

	public static function using_icegram_mailer() {
		return 'icegram' === ES()->mailer->mailer->slug;
	}

	public static function get_ess_from_email() {
		$ess_data       = get_option( 'ig_es_ess_data', array() );
		$ess_from_email = ! empty( $ess_data['from_email'] ) ? $ess_data['from_email'] : '';
		return $ess_from_email;
	}

	public static function can_show_ess_optin() {

		global $ig_es_tracker;

		if ( $ig_es_tracker::is_dev_environment() ) {
			return false;
		}

		return true;
	}

	public static function is_installed_on_same_month_day() {
		$installation_date = ES_Common::get_plugin_installation_date();
		if ( ! empty( $installation_date ) ) {
			$installation_day = gmdate( 'd', strtotime( $installation_date ) );
			$current_day      = gmdate( 'd', time() );
			if ( $current_day === $installation_day ) {
				return true;
			}
		}
		return false;
	}

	public static function is_shown_previously() {
		$ess_optin_shown = get_option( 'ig_es_ess_optin_shown', 'no' );
		return 'yes' === $ess_optin_shown;
	}

	public static function set_ess_optin_shown_flag() {
		update_option( 'ig_es_ess_optin_shown', 'yes', false );
	}

	public function update_sending_service_status() {
		if ( self::using_icegram_mailer() ) {
			$status = 'ig_es_message_sent' === current_action() ? 'success' : 'error';
			update_option( 'ig_es_ess_status', $status, false );
		}
	}

	public static function get_sending_service_status() {
		$service_status = get_option( 'ig_es_ess_status' );
		return $service_status;
	}

	public static function get_plan() {
		
		$es_services  = ES()->get_es_services();
		$service_plan = 'lite';
		if ( empty( $es_services ) ) {
			return $service_plan;
		}

		if ( in_array( 'bounce_handling', $es_services, true ) ) {
			$service_plan = 'max';
		} else {
			$service_plan = 'pro';
		}

		return $service_plan;
	}

	public static function is_ess_branding_enabled() {
		$ess_branding_enabled = get_option( 'ig_es_ess_branding_enabled', 'yes' );
		return 'yes' === $ess_branding_enabled; 
	}

	public static function can_promote_ess() {
		if ( ! self::opted_for_sending_service() && ! self::is_ess_promotion_disabled() ) {
			return true;
		}
		return false;
	}

	public static function is_ess_promotion_disabled() {
		$is_ess_promotion_disabled = 'yes' === get_option( 'ig_es_promotion_disabled', 'no' );
		return $is_ess_promotion_disabled;
	}

	public static function get_ess_promotion_message_html() {
		ob_start();
		$optin_url      = admin_url( '?page=es_dashboard&ess_optin=yes' );
		$learn_more_url = 'https://www.icegram.com/email-sending-service-in-icegram-express/';
		?>
		<div id="ig_es_ess_promotion_message" class="text-gray-700 not-italic">
			<p>
				<?php echo esc_html__( 'Please fix above sending error to continue sending emails', 'email-subscribers' ); ?>
			</p>
			<p>
				<?php echo esc_html__( 'OR', 'email-subscribers' ); ?>
			</p>
			<p>
				<?php echo esc_html__( 'Use our Icegram email sending service for a hassle-free email sending experience.', 'email-subscribers' ); ?>
			</p>
			<a href="<?php echo esc_url( $optin_url ); ?>" target="_blank" id="ig-es-ess-optin-promo" class="ig-es-primary-button px-3 py-1 mt-2 align-middle">
				<?php
					echo esc_html__( 'Signup to ESS', 'email-subscribers' );
				?>
			</a>
			<a href="<?php echo esc_url( $learn_more_url ); ?>" target="_blank" class="ig-es-title-button px-3 py-1 mt-2 ml-2 align-middle">
				<?php
					echo esc_html__( 'Learn more', 'email-subscribers' );
				?>
			</a>
		</div>
		<?php
		$message_html = ob_get_clean();
		return $message_html;
	}
}

new ES_Service_Email_Sending();
