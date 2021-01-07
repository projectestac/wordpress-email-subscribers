<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The onboarding-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.6.0
 *
 * @package    Email_Subscribers
 */


if ( ! class_exists( 'IG_ES_Onboarding' ) ) {
	
	/**
	 * The onboarding-specific functionality of the plugin.
	 *
	 */
	class IG_ES_Onboarding {

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
		 *
		 */
		protected static $logger_context = array(
			'source' => 'ig_es_onboarding'
		);
		
		/**
		 * Variable to hold all onboarding tasks list
		 *
		 * @since 4.6.0
		 * @var array
		 *
		 */
		private static $all_onboarding_tasks = array(
			'configuration_tasks' => array(
				'set_settings',
				'create_default_lists',
				'create_contacts_and_add_to_list',
				'add_workflow_for_user_registration',
				'create_default_newsletter_broadcast',
				'create_default_post_notification',
				'create_default_subscription_form',
				'add_widget_to_sidebar',
			),
			'email_delivery_check_tasks' => array(
				'queue_default_broadcast_newsletter',
				'dispatch_emails_from_server',
				'check_test_email_on_server',
				'evaluate_email_delivery',
			),
			'completion_tasks' => array(
				'subscribe_to_klawoo',
				'save_final_configuration',
			)
		);

		/**
		 * Option name for current task name.
		 * 
		 * @since 4.6.0
		 * @var array
		 *
		 */
		private static $onboarding_current_task_option = 'ig_es_onboarding_current_task';

		/**
		 * Option name which holds common data between tasks.
		 * 
		 * E.g. created subscription form id from create_default_subscription_form function so we can use it in add_widget_to_sidebar
		 *
		 * @since 4.6.0
		 * @var array
		 *
		 */
		private static $onboarding_tasks_data_option = 'ig_es_onboarding_tasks_data';

		/**
		 * Option name which holds tasks which are done.
		 * 
		 * @since 4.6.0
		 * @var array
		 *
		 */
		private static $onboarding_tasks_done_option = 'ig_es_onboarding_tasks_done';
		
		/**
		 * Option name which holds tasks which are failed.
		 * 
		 * @since 4.6.0
		 * @var array
		 *
		 */
		private static $onboarding_tasks_failed_option = 'ig_es_onboarding_tasks_failed';

		/**
		 * Option name which holds tasks which are skipped due to dependency on other tasks.
		 * 
		 * @since 4.6.0
		 * @var array
		 *
		 */
		private static $onboarding_tasks_skipped_option = 'ig_es_onboarding_tasks_skipped';

		/**
		 * Option name which store the step which has been completed.
		 * 
		 * @since 4.6.0
		 * @var string
		 *
		 */
		private static $onboarding_step_option = 'ig_es_onboarding_step';
		
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 4.6.0
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_ig_es_handle_request', array( $this, 'handle_request' ) );
		}
	
		/**
		 * Get class instance.
		 * 
		 * @since 4.6.0
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		
		/**
		 * Register the JavaScript for the onboarding process.
		 * 
		 * @since 4.6.0
		 */
		public function enqueue_scripts() {
	
			if ( ! ES()->is_es_admin_screen() ) {
				return;
			}

			$current_page = ig_es_get_request_data( 'page' );

			if ( 'es_dashboard' === $current_page ) {

				if ( ! self::is_onboarding_completed() ) {
					wp_enqueue_script( 'ig-es-onboarding', plugin_dir_url( __FILE__ ) . 'js/es-onboarding.js', array( 'jquery' ), ES_PLUGIN_VERSION, false );
					$onboarding_data                  = $this->get_onboarding_data();
					$onboarding_data['next_task']     = $this->get_next_onboarding_task();
					$onboarding_data['error_message'] = __( 'An error occured. Please try again later.', 'email-subscribers' );
					wp_localize_script( 'ig-es-onboarding', 'ig_es_onboarding_data', $onboarding_data );
				}
			}
		}
		
		/**
		 * Method to perform configuration and list, ES form, campaigns creation related operations in the onboarding
		 * 
		 * @since 4.6.0
		 */
		public function handle_request() {
	
			$response = array();

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

			$request = ig_es_get_request_data( 'request' );

			if ( ! empty( $request ) ) {
				$ajax_action = 'ajax_' . $request;
				if ( is_callable( array( $this, $ajax_action ) ) ) {
					$response = call_user_func( array( $this, $ajax_action ) );
				}
			}

			wp_send_json( $response );
		}
		
		/**
		 * Method to perform configuration and list, ES form, campaigns creation related operations in the onboarding
		 * 
		 * @since 4.6.0
		 */
		public function ajax_perform_configuration_tasks() {

			update_option( self::$onboarding_step_option, 2 );
			return $this->perform_onboarding_tasks( 'configuration_tasks' );
		}

		/**
		 * Method to perform email delivery tasks.
		 * 
		 * @since 4.6.0
		 */
		public function ajax_queue_default_broadcast_newsletter() {
			return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'queue_default_broadcast_newsletter' );
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
		 * Method to perform email delivery tasks.
		 * 
		 * @since 4.6.0
		 */
		public function ajax_checking_spam_score_delivery_metrics() {
			return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'checking_spam_score_delivery_metrics' );
		}

		/**
		 * Method to perform email delivery tasks.
		 * 
		 * @since 4.6.0
		 */
		public function ajax_evaluate_email_delivery() {
			return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'evaluate_email_delivery' );
		}
		
		/**
		 * Method to perform email delivery tasks.
		 * 
		 * @since 4.6.0
		 */
		public function ajax_finishing_onboarding() {

			$response = $this->perform_onboarding_tasks( 'completion_tasks' );

			// Delete the onboarding data used during onboarding process.
			$this->delete_onboarding_data();

			$settings_url             = admin_url( 'admin.php?page=es_settings' );
			$response['redirect_url'] = $settings_url;
			
			return $response;
		}

		/**
		 * Method to updatee the onboarding step
		 * 
		 * @return bool 
		 * 
		 * @since 4.6.0
		 */
		public static function ajax_update_onboarding_step() {

			$response = array(
				'status' => 'error',
			);

			$step = ig_es_get_request_data( 'step' );
			if ( ! empty( $step ) ) {
				self::update_onboarding_step( $step );
				$response['status'] = 'success';
			}

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
			$current_tasks_done    = ! empty( $onboarding_tasks_done[$task_group] ) ? $onboarding_tasks_done[$task_group] : array();
			
			$onboarding_tasks_failed = get_option( self::$onboarding_tasks_failed_option, array() );
			$current_tasks_failed    = ! empty( $onboarding_tasks_failed[$task_group] ) ? $onboarding_tasks_failed[$task_group] : array();

			$onboarding_tasks_skipped = get_option( self::$onboarding_tasks_skipped_option, array() );
			$current_tasks_skipped    = ! empty( $onboarding_tasks_skipped[$task_group] ) ? $onboarding_tasks_skipped[$task_group] : array();

			$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
			if ( ! empty( $current_tasks ) ) {
				foreach ( $current_tasks as $current_task ) {
					if ( ! in_array( $current_task, $current_tasks_done, true ) ) {

						if ( is_callable( array( $this, $current_task ) ) ) {
							$logger->info( 'Doing Task:' . $current_task, self::$logger_context );

							// Call callback function.
							$task_response = call_user_func( array( $this, $current_task ) );
							if ( 'success' === $task_response['status'] ) {
								if ( ! empty( $task_response['tasks_data'] ) ) {
									if ( ! isset( $onboarding_tasks_data[$current_task] ) ) {
										$onboarding_tasks_data[$current_task] = array();
									}
									$onboarding_tasks_data[$current_task] = array_merge( $onboarding_tasks_data[$current_task], $task_response['tasks_data'] );
								}
								$logger->info( 'Task Done:' . $current_task, self::$logger_context );
								// Set success status only if not already set else it can override error/skipped statuses set previously from other tasks.
								if ( empty( $response['status'] ) ) {
									$response['status'] = 'success';
								}
								$current_tasks_done[] = $current_task;
							} else if ( 'skipped' === $task_response['status'] ) {
								$response['status'] = 'skipped';
								$current_tasks_skipped[] = $current_task;
							} else {
								$logger->info( 'Task Failed:' . $current_task, self::$logger_context );
								$response['status'] = 'error';
								$current_tasks_failed[] = $current_task;
							}
							
							$response['tasks'][$current_task] = $task_response;

							$onboarding_tasks_done[$task_group]    = $current_tasks_done;
							$onboarding_tasks_failed[$task_group]  = $current_tasks_failed;
							$onboarding_tasks_skipped[$task_group] = $current_tasks_skipped;

							update_option( self::$onboarding_tasks_done_option, $onboarding_tasks_done );
							update_option( self::$onboarding_tasks_failed_option, $onboarding_tasks_failed );
							update_option( self::$onboarding_tasks_skipped_option, $onboarding_tasks_skipped );
							update_option( self::$onboarding_tasks_data_option, $onboarding_tasks_data );
							update_option( self::$onboarding_current_task_option, $current_task );
						} else {
							$logger->info( 'Missing Task:' . $current_task, self::$logger_context );
						}
					} else {
						$response['tasks'][$current_task] = array(
							'status' => 'success',
						);
						$logger->info( 'Task already done:' . $current_task, self::$logger_context );
					}
				}
			}

			return $response;
		}

		/**
		 * Set settings required for ES settings.
		 * 
		 * @since 4.6.0
		 */
		public function set_settings() {

			$response = array(
				'status' => 'error',
			);

			$from_name           = ig_es_get_request_data( 'es_from_name', '' );
			$from_email          = ig_es_get_request_data( 'es_from_email', '' );
			$enable_double_optin = ig_es_get_request_data( 'enable_double_optin', 'yes' );
			$optin_type          = 'yes' === $enable_double_optin ? 'double_opt_in': 'single_opt_in';
			$is_trial            = ig_es_get_request_data( 'is_trial', '' );

			if ( ! empty( $is_trial ) ) {
				// Add trial preferences.
				ES()->add_trial_data( $is_trial, time() );
			}

			update_option( 'ig_es_optin_type', $optin_type );

			if ( ! empty( $from_name ) && ! empty( $from_email ) ) {
				update_option( 'ig_es_from_name', $from_name );
				update_option( 'ig_es_from_email', $from_email );
				$response['status'] = 'success';
			} else {
				$response['status'] = 'error';
			}

			return $response;
		}

		/**
		 * Create default list contact
		 *
		 * @since 4.0.0
		 */
		public function create_default_lists() {
	
			$response = array(
				'status' => 'error',
			);
	
			$default_list   = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );
			// Check if list already not exists.
			if ( empty( $default_list['id'] ) ) {
				// Add default list.
				$default_list_id = ES()->lists_db->add_list( IG_DEFAULT_LIST );
			} else {
				$default_list_id = $default_list['id'];
			}
			
			$main_list = ES()->lists_db->get_list_by_name( IG_MAIN_LIST );
			// Check if list already not exists.
			if ( empty( $main_list['id'] ) ) {
				// Add main list.
				$main_list_id = ES()->lists_db->add_list( IG_MAIN_LIST );
			} else {
				$main_list_id = $main_list['id'];
			}

			// Check if lists created successfully.
			if ( $default_list_id && $main_list_id ) {
				$response['status'] = 'success';
			} else {
				/* translators: 1. Main list name. 2. Default list name. */
				$response['message'] = sprintf( __( 'Unable to create %1$s and %2$s list.', 'email-subscribers' ), IG_MAIN_LIST, IG_DEFAULT_LIST );
				$response['status'] = 'error';
			}

			return $response;
		}
		
		public function create_contacts_and_add_to_list() {

			// Get default list data.
			$default_list_id = 0;
			$default_list    = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );

			if ( ! empty( $default_list['id'] ) ) {
				$default_list_id = $default_list['id'];
			}

			// Get main list data.
			$main_list_id = 0;
			$main_list    = ES()->lists_db->get_list_by_name( IG_MAIN_LIST );

			if ( ! empty( $main_list['id'] ) ) {
				$main_list_id = $main_list['id'];
			}

			// Get contact data for admin
			$admin_email = get_option( 'admin_email' );
			$admin_name  = get_option( 'admin_email' );
			$user        = get_user_by( 'email', $admin_email );
			$wp_user_id  = 0;
			$ip_address	 = ig_es_get_ip();
			if ( $user instanceof WP_User ) {
				$wp_user_id = $user->ID;
			}

			// Prepare admin contact data.
			$data = array(
				'wp_user_id'   => $wp_user_id,
				'first_name'   => $admin_name,
				'last_name'    => '',
				'email'        => $admin_email,
				'source'       => 'admin',
				'ip_address'   => $ip_address,
				'form_id'      => 0,
				'status'       => 'verified',
				'unsubscribed' => 0,
				'hash'         => ES_Common::generate_guid(),
				'created_at'   => ig_get_current_date_time()
			);

			$contact_id = ES()->contacts_db->insert( $data );
			if ( $contact_id ) {
				
				// Prepare admin contact list data.
				$data = array(
					'contact_id'    => $contact_id,
					'status'        => 'subscribed',
					'optin_type'    => IG_SINGLE_OPTIN,
					'subscribed_at' => ig_get_current_date_time(),
					'subscribed_ip' => $ip_address,
				);

				if ( ! empty( $default_list_id ) ) {
					$contacts_added = ES()->lists_contacts_db->add_contact_to_lists( $data, $default_list_id );
					if ( $contacts_added ) {
						$response['status'] = 'success';
					}
				}

				if ( ! empty( $main_list_id ) ) {
					$contacts_added = ES()->lists_contacts_db->add_contact_to_lists( $data, $main_list_id );
					if ( $contacts_added ) {
						$response['status'] = 'success';
					}
				}
			}

			// Get details of other emails.
			$emails = ig_es_get_request_data( 'emails', array() );
			if ( is_array( $emails ) && count( $emails ) > 0 ) {
				$default_list = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );
				// Add to the default list.
				foreach ( $emails as $email ) {
					$data       = array(
						'first_name'   => ES_Common::get_name_from_email( $email ),
						'email'        => $email,
						'source'       => 'admin',
						'form_id'      => 0,
						'status'       => 'verified',
						'unsubscribed' => 0,
						'hash'         => ES_Common::generate_guid(),
						'created_at'   => ig_get_current_date_time()
					);
					$contact_id = ES()->contacts_db->insert( $data );
					if ( $contact_id ) {
						$data = array(
							'contact_id'    => $contact_id,
							'status'        => 'subscribed',
							'optin_type'    => IG_SINGLE_OPTIN,
							'subscribed_at' => ig_get_current_date_time(),
							'subscribed_ip' => null,
						);
	
						$contacts_added = ES()->lists_contacts_db->add_contact_to_lists( $data, $default_list_id );
						if ( $contacts_added ) {
							$response['status'] = 'success';
						}
					}
				}
			}

			return $response;
		}

		/**
		 * Add user registration workflow
		 * 
		 * @return $response
		 * 
		 * @since 4.6.0
		 */
		public function add_workflow_for_user_registration() {

			$response = array(
				'status' => 'error',
			);

			$workflow_query_args = array(
				'trigger_name' => 'ig_es_user_registered',
			);
	
			$workflows = ES()->workflows_db->get_workflows( $workflow_query_args );

			// Add workflow only if there is no workflow for user registration already present.
			if ( empty( $workflows ) ) {
				$main_list = ES()->lists_db->get_list_by_name( IG_MAIN_LIST );

				// Check if Main list exists.
				if ( ! empty( $main_list ) ) {
					$workflow_title               = __( 'User Registered', 'email-subscribers' );
					$workflow_name                = sanitize_title( $workflow_title );
					$trigger_name                 = 'ig_es_user_registered';
					$main_list_id                 = $main_list['id'];
					$workflow_meta                = array();
					$workflow_meta['when_to_run'] = 'immediately';
					$workflow_status              = 0;
					
					$workflow_actions = array(
						array(
							'action_name' => 'ig_es_add_to_list',
							'ig-es-list'  => ES_Clean::id( $main_list_id ),
						),
					);

					$workflow_data = array(
						'name'         => $workflow_name,
						'title'        => $workflow_title,
						'trigger_name' => $trigger_name,
						'actions'      => maybe_serialize( $workflow_actions ),
						'meta'         => maybe_serialize( $workflow_meta ),
						'priority'     => 0,
						'status'       => $workflow_status,
					);

					$workflow_id = ES()->workflows_db->insert_workflow( $workflow_data );
					if ( $workflow_id ) {
						$response['status'] = 'success';
					}
				}
			}

			return $response;
		}
		
		/**
		 * Create default form
		 *
		 * @since 4.6.0
		 */
		public function create_default_subscription_form() {

			$response = array(
				'status' => 'error',
			);

			$form_data    = array();
			$default_list = ES()->lists_db->get_list_by_name( IG_MAIN_LIST );
			$list_id      = $default_list['id'];

			$body         = array(
				array(
					'type'   => 'text',
					'name'   => 'Name',
					'id'     => 'name',
					'params' => array(
						'label'    => 'Name',
						'show'     => true,
						'required' => true
					),

					'position' => 1
				),

				array(
					'type'   => 'text',
					'name'   => 'Email',
					'id'     => 'email',
					'params' => array(
						'label'    => 'Email',
						'show'     => true,
						'required' => true
					),

					'position' => 2
				),

				array(
					'type'   => 'checkbox',
					'name'   => 'Lists',
					'id'     => 'lists',
					'params' => array(
						'label'    => 'Lists',
						'show'     => false,
						'required' => true,
						'values'   => array( $list_id )
					),

					'position' => 3
				),

				array(
					'type'   => 'submit',
					'name'   => 'submit',
					'id'     => 'submit',
					'params' => array(
						'label' => 'Subscribe',
						'show'  => true
					),

					'position' => 4
				),

			);

			$settings = array(
				'lists' => array( $list_id ),
				'desc'  => ''
			);
			
			$add_gdpr_consent = ig_es_get_request_data( 'add_gdpr_consent', '' );

			// Add GDPR setting if admin has opted for.
			if ( 'yes' === $add_gdpr_consent ) {
				$settings['gdpr']['consent']      = 'yes';
				$settings['gdpr']['consent_text'] = __( 'Please accept terms & condition', 'email-subscribers' );
			}

			$form_data['name']       = 'First Form';
			$form_data['body']       = maybe_serialize( $body );
			$form_data['settings']   = maybe_serialize( $settings );
			$form_data['styles']     = '';
			$form_data['created_at'] = ig_get_current_date_time();
			$form_data['updated_at'] = null;
			$form_data['deleted_at'] = null;
			$form_data['af_id']      = 0;

			// Add Form.
			$form_id = ES()->forms_db->add_form( $form_data );
			if ( ! empty( $form_id ) ) {
				$response['status'] = 'success';
				$response['tasks_data'] = array(
					'form_id' => $form_id
				);
			} else {
				$response['status'] = 'error';
			}

			return $response;
		}
		
		/**
		 * Add ES widget to active sidebar
		 * 
		 * @since 4.6.0
		 */
		public function add_widget_to_sidebar() {

			global $wp_registered_sidebars;

			$response = array(
				'status' => 'error',
			);

			if ( ! empty( $wp_registered_sidebars ) ) {
				foreach ( $wp_registered_sidebars as $sidebar ) {
					$sidebar_id = $sidebar['id'];
					if ( is_active_sidebar( $sidebar_id ) ) {
						$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
						// Get created form id from create_default_subscription_form task's data.
						if ( ! empty( $onboarding_tasks_data['create_default_subscription_form']['form_id'] ) ) {
							$form_id     = $onboarding_tasks_data['create_default_subscription_form']['form_id'];
							$widget_data = array(
								'form_id' => $form_id,
							);
							$widget_added = ig_es_insert_widget_in_sidebar( 'email-subscribers-form', $widget_data, $sidebar_id );
							if ( $widget_added ) {
								$response['status']     = 'success';
								$response['tasks_data'] = array(
									'sidebar_id' => $sidebar_id,
									'sidebar_name' => $sidebar['name'],
								);
								/* translators: Active sidebar name. */
								$response['message'] = sprintf( __( 'Adding the form to "%s" sidebar, so you can show it on the site', 'email-subscribers' ), $sidebar['name'] );
							} else {
								/* translators: Active sidebar name. */
								$response['message'] = sprintf( __( 'Unable to add form widget to "%s" sidebar. Widget may already exists.', 'email-subscribers' ), $sidebar['name'] );
							}
							break;
						} else {
							/* translators: Active sidebar name. */
							$response['message'] = sprintf( __( 'Unable to add form widget to "%s" sidebar. No subscription form found.', 'email-subscribers' ), $sidebar['name'] );
							break;
						}
					}
				}
			}
			
			return $response;
		}
		
		/**
		 * Create and send default broadcast while onboarding
		 *
		 * @return array|mixed|void
		 *
		 * @since 4.0.0
		 */
		public function create_default_newsletter_broadcast() {

			$response = array(
				'status' => 'error',
			);

			/**
			 * - Create Default Template
			 * - Create Broadcast Campaign
			 */
			$from_name  = ES_Common::get_ig_option( 'from_name' );
			$from_email = ES_Common::get_ig_option( 'from_email' );

			// First Create Default Template.
			// Start-IG-Code.
			$sample  = '<strong style="color: #990000">What can you achieve using Email Subscribers?</strong><p>Add subscription forms on website, send HTML newsletters & automatically notify subscribers about new blog posts once it is published.';
			// End-IG-Code.
			// Start-Woo-Code.
			$sample  = '<strong style="color: #990000">What can you achieve using Email Subscribers?</strong><p>Add subscription forms on website, send HTML newsletters.';
			// End-Woo-Code.
			$sample .= ' You can also Import or Export subscribers from any list to Email Subscribers.</p>';
			$sample .= ' <strong style="color: #990000">Plugin Features</strong><ol>';
			// Start-IG-Code.
			$sample .= ' <li>Send notification emails to subscribers when new blog posts are published.</li>';
			// End-IG-Code.
			$sample .= ' <li>Subscribe form available with 3 options to setup.</li>';
			$sample .= ' <li>Double Opt-In and Single Opt-In support.</li>';
			$sample .= ' <li>Email notification to admin when a new user signs up (Optional).</li>';
			$sample .= ' <li>Automatic welcome email to subscriber.</li>';
			$sample .= ' <li>Auto add unsubscribe link in the email.</li>';
			$sample .= ' <li>Import/Export subscriber emails to migrate to any lists.</li>';
			$sample .= ' <li>Default WordPress editor to create emails.</li>';
			$sample .= ' </ol>';
			$sample .= ' <strong>Thanks & Regards,</strong><br/>Admin<br/>';

			$title   = esc_html__( 'Welcome To Email Subscribers', 'email-subscribers' );
			$subject = esc_html__( 'Welcome To Email Subscribers', 'email-subscribers' );

			$es_post = array(
				'post_title'   => $title,
				'post_content' => $sample,
				'post_status'  => 'publish',
				'post_type'    => 'es_template',
				'meta_input'   => array(
					'es_template_type' => 'newsletter'
				)
			);

			// Insert the post into the database
			$post_id = wp_insert_post( $es_post );

			// Create Broadcast Campaign

			$default_list = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );

			if ( ! empty( $default_list ) ) {
				$list_id = $default_list['id'];

				if ( ! empty( $post_id ) ) {

					$data['slug']             = sanitize_title( $title );
					$data['name']             = $title;
					$data['subject']          = $subject;
					$data['type']             = 'newsletter';
					$data['from_email']       = $from_email;
					$data['reply_to_email']	  = $from_email;
					$data['from_name']        = $from_name;
					$data['reply_to_name'] 	  = $from_name;
					$data['list_ids']         = $list_id;
					$data['base_template_id'] = $post_id;
					$data['body']             = $sample;
					$data['status']           = 1;

					$broadcast_id = ES()->campaigns_db->save_campaign( $data );

					if ( $broadcast_id ) {
						$response['status']     = 'success';
						$response['tasks_data'] = array(
							'broadcast_id' => $broadcast_id,
						);
					}

				}
			}

			return $response;
		}

		/**
		 * Method to queue created default newsletter broadcast.
		 * 
		 * @since 4.6.0
		 */ 
		public function queue_default_broadcast_newsletter() {

			$response = array(
				'status' => 'error',
			);

			$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
			$campaign_id           = ! empty( $onboarding_tasks_data['create_default_newsletter_broadcast']['broadcast_id'] ) ? $onboarding_tasks_data['create_default_newsletter_broadcast']['broadcast_id']: 0;
			
			if ( ! empty( $campaign_id ) ) {
				$default_list = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );
				if ( ! empty( $default_list ) ) {
					$list_id     = $default_list['id'];
					$subscribers = ES()->contacts_db->get_active_contacts_by_list_id( $list_id );
					if ( ! empty( $subscribers ) && count( $subscribers ) > 0 ) {
						$content_body = ES()->campaigns_db->get_column( 'body', $campaign_id );
						$title		  = ES()->campaigns_db->get_column( 'name', $campaign_id );
						$guid         = ES_Common::generate_guid( 6 );
						$now          = ig_get_current_date_time();

						$data = array(
							'hash'        => $guid,
							'campaign_id' => $campaign_id,
							'subject'     => $title,
							'body'        => $content_body,
							'count'       => count( $subscribers ),
							'status'      => 'In Queue',
							'start_at'    => $now,
							'finish_at'   => $now,
							'created_at'  => $now,
							'updated_at'  => $now,
						);
	
						$report_id = ES_DB_Mailing_Queue::add_notification( $data );
	
						$delivery_data                     = array();
						$delivery_data['hash']             = $guid;
						$delivery_data['subscribers']      = $subscribers;
						$delivery_data['campaign_id']      = $campaign_id;
						$delivery_data['mailing_queue_id'] = $report_id;
						$delivery_data['status']           = 'In Queue';
						ES_DB_Sending_Queue::do_batch_insert( $delivery_data );

						$response['status']     = 'success';
						$response['tasks_data'] = array(
							'report_id'         => $report_id,
							'notification_guid' => $guid,
						);
					}
				}
			}

			return $response;
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

			// Send test email to Icegram API only if trial is valid or user is premium user.
			if ( ES()->is_trial_valid() || ES()->is_premium() ) {
				
				$service = new ES_Send_Test_Email();
				$res     = $service->send_test_email();
			}

			$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
			$campaign_id           = ! empty( $onboarding_tasks_data['create_default_newsletter_broadcast']['broadcast_id'] ) ? $onboarding_tasks_data['create_default_newsletter_broadcast']['broadcast_id']: 0;
			$report_id             = ! empty( $onboarding_tasks_data['queue_default_broadcast_newsletter']['report_id'] ) ? $onboarding_tasks_data['queue_default_broadcast_newsletter']['report_id'] : 0;
	
			if ( ! empty( $campaign_id ) && ! empty( $report_id ) ) {
	
				$content_body      = ES()->campaigns_db->get_column( 'body', $campaign_id );
				$title             = ES()->campaigns_db->get_column( 'name', $campaign_id );
				$email_created     = time();
				$from_name         = ES_Common::get_ig_option( 'from_name' );
				$from_email        = ES_Common::get_ig_option( 'from_email' );
				$notification_guid = ! empty( $onboarding_tasks_data['queue_default_broadcast_newsletter']['notification_guid'] ) ? $onboarding_tasks_data['queue_default_broadcast_newsletter']['notification_guid'] : 0;

				// Newsletter Send.
				$email_template = ES_Common::convert_es_templates( $content_body, $from_name, $from_email, $email_created );

				$merge_tags = array(
					'message_id'  => $report_id,
					'campaign_id' => $campaign_id,
				);

				$send_limit = ES()->mailer->get_total_emails_send_now();

				$emails_data = ES_DB_Sending_Queue::get_emails_to_be_sent_by_hash( $notification_guid, $send_limit );
				$emails      = array();
				foreach ( $emails_data as $email ) {
					$emails[] = $email['email'];
				}

				ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sending' );

				$res = ES()->mailer->send( $title, $email_template, $emails, $merge_tags );

				ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sent' );

				if ( $res && is_array( $res ) && ! empty( $res['status'] ) ) {
					if ( 'SUCCESS' === $res['status'] ) {
						$response['status'] = 'success';
						update_option( 'ig_es_onboarding_test_campaign_success', 'yes' );
					} else {
						$response['status']             = 'error';
						$response['message']            = ! empty( $res['message'] ) ? $res['message']: '';
						$response['additional_message'] = __( 'Seems like your server is not setup correctly to send emails. Please confirm if you\'re getting any other emails from within WordPress', 'email-subscribers' );
						update_option( 'ig_es_onboarding_test_campaign_error', 'yes' );
					}
				}
			}

			return $response;
		}

		/**
		 * Method to check test email on Icegram servers.
		 * 
		 * @since 4.6.0
		 */
		public function check_test_email_on_server() {

			$response = array(
				'status' => 'error',
			);

			// Check test email only if user has valid trial or is a premium user.
			if ( ES()->is_trial_valid() || ES()->is_premium() ) {
				$onboarding_tasks_failed           = get_option( self::$onboarding_tasks_failed_option, array() );
				$email_delivery_check_tasks_failed = ! empty( $onboarding_tasks_failed['email_delivery_check_tasks'] ) ? $onboarding_tasks_failed['email_delivery_check_tasks']: array();
				
				// Peform test email checking if dispatch_emails_from_server task hasn't failed.
				if ( ! in_array( 'dispatch_emails_from_server', $email_delivery_check_tasks_failed, true ) ) {
					$service = new ES_Email_Delivery_Check();
					return $service->test_email_delivery();
				} else {
					$response['status'] = 'skipped';
				}
			}

			return $response;
		}

		/**
		 * Method to check spam score of test email recieved on Icegram servers.
		 * 
		 * @since 4.6.0
		 */
		public function checking_spam_score_delivery_metrics() {

			$response = array(
				'status' => 'error',
			);

			// Check spam score only if user has valid trial or is a premium user.
			if ( ES()->is_trial_valid() || ES()->is_premium() ) {
				$onboarding_tasks_failed           = get_option( self::$onboarding_tasks_failed_option, array() );
				$email_delivery_check_tasks_failed = ! empty( $onboarding_tasks_failed['email_delivery_check_tasks'] ) ? $onboarding_tasks_failed['email_delivery_check_tasks']: array();

				$onboarding_tasks_skipped           = get_option( self::$onboarding_tasks_skipped_option, array() );
				$email_delivery_check_tasks_skipped = ! empty( $onboarding_tasks_skipped['email_delivery_check_tasks'] ) ? $onboarding_tasks_skipped['email_delivery_check_tasks']: array();

				// Peform test email spam score only if check_test_email_on_server task hasn't failed or skipped.
				if ( ! in_array( 'check_test_email_on_server', $email_delivery_check_tasks_failed, true ) && ! in_array( 'check_test_email_on_server', $email_delivery_check_tasks_skipped, true ) ) {
					$response['status'] = 'success';
				} else {
					$response['status'] = 'skipped';
				}
			}

			return $response;
		}

		/**
		 * Method to check test email on Icegram servers.
		 * 
		 * @since 4.6.0
		 */
		public function evaluate_email_delivery() {

			$response = array(
				'status' => 'error',
			);

			// Evaluate email delivery only if user has valid trial or is a premium user.
			if ( ES()->is_trial_valid() || ES()->is_premium() ) {
				$onboarding_tasks_failed           = get_option( self::$onboarding_tasks_failed_option, array() );
				$email_delivery_check_tasks_failed = ! empty( $onboarding_tasks_failed['email_delivery_check_tasks'] ) ? $onboarding_tasks_failed['email_delivery_check_tasks']: array();

				$onboarding_tasks_skipped           = get_option( self::$onboarding_tasks_skipped_option, array() );
				$email_delivery_check_tasks_skipped = ! empty( $onboarding_tasks_skipped['email_delivery_check_tasks'] ) ? $onboarding_tasks_skipped['email_delivery_check_tasks']: array();

				// Peform email delivery evaulation only if check_test_email_on_server task hasn't failed or skipped.
				if ( ! in_array( 'check_test_email_on_server', $email_delivery_check_tasks_failed, true ) && ! in_array( 'check_test_email_on_server', $email_delivery_check_tasks_skipped, true ) ) {
					$response['status'] = 'success';
				} else {
					$response['status'] = 'skipped';
				}
			}
			return $response;
		}
	
		/**
		 * Create default Post notification while on boarding
		 *
		 * @return array|int|mixed|void|WP_Error
		 *
		 * @since 4.6.0
		 */
		public function create_default_post_notification() {
			
			$response = array(
				'status' => 'error',
			);
			$create_post_notification = ig_es_get_request_data( 'create_post_notification', '' );
			if ( 'no' === $create_post_notification ) {
				$response['status'] = 'skipped';
				return $response;
			}

			$from_name  = ES_Common::get_ig_option( 'from_name' );
			$from_email = ES_Common::get_ig_option( 'from_email' );

			$content  = "Hello {{NAME}},\r\n\r\n";
			$content .= "We have published a new blog article on our website : {{POSTTITLE}}\r\n";
			$content .= "{{POSTIMAGE}}\r\n\r\n";
			$content .= 'You can view it from this link : ';
			$content .= "{{POSTLINK}}\r\n\r\n";
			$content .= "Thanks & Regards,\r\n";
			$content .= "Admin\r\n\r\n";
			$content .= 'You received this email because in the past you have provided us your email address : {{EMAIL}} to receive notifications when new updates are posted.';

			$title = esc_html__( 'New Post Published - {{POSTTITLE}}', 'email-subscribers' );
			// Create Post Notification object
			$post = array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'es_template',
				'meta_input'   => array(
					'es_template_type' => 'post_notification'
				)
			);
			// Insert the post into the database
			$post_id = wp_insert_post( $post );

			$default_list = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );

			if ( ! empty( $post_id ) ) {
				$list_id = $default_list['id'];

				$categories_objects = get_terms( array(
					'taxonomy'   => 'category',
					'hide_empty' => false,
				) );

				$categories = array();
				if ( count( $categories_objects ) > 0 ) {
					foreach ( $categories_objects as $category ) {
						if ( $category instanceof WP_Term ) {
							$categories[] = $category->term_id;
						}
					}
				}

				$categories_str = ES_Common::convert_categories_array_to_string( $categories );

				$data['slug']             = sanitize_title( $title );
				$data['name']             = $title;
				$data['type']             = 'post_notification';
				$data['from_email']       = $from_name;
				$data['reply_to_email']   = $from_name;
				$data['from_name']        = $from_email;
				$data['reply_to_name']    = $from_email;
				$data['categories']       = $categories_str;
				$data['list_ids']         = $list_id;
				$data['base_template_id'] = $post_id;
				$data['status']           = 0;

				$post_notification_id = ES()->campaigns_db->save_campaign( $data );
				if ( $post_notification_id ) {
					$response['status'] = 'success';
					$response['tasks_data'] = array(
						'post_notification_id' => $post_notification_id
					);
				}
			}

			return $response;
		}

		/**
		 * Method to subscribe to klawoo in the onboarding process.
		 * 
		 * @since 4.6.0
		 */ 
		public function subscribe_to_klawoo() {

			$response = Email_Subscribers_Admin::klawoo_subscribe( true );

			return $response;
		}

		/**
		 * Method to subscribe to klawoo in the onboarding process.
		 * 
		 * @since 4.6.0
		 */ 
		public function save_final_configuration() {

			$response = array();

			$is_trial = ig_es_get_request_data( 'is_trial', '' );

			if ( ! empty( $is_trial ) ) {
				// Add trial preferences.
				ES()->add_trial_data( $is_trial, time() );
			}

			// Set flag for onboarding completion.
			update_option( 'ig_es_onboarding_complete', 'yes' );

			$response['status']       = 'success';

			return $response;
		}

		/**
		 * Method to delete the all onboarding data used in onboarding process.
		 */
		public function delete_onboarding_data() {

			$response = array(
				'status' => 'error',
			);

			$onboarding_options = $this->get_onboarding_data_options();
			
			foreach ( $onboarding_options as $option ) {
				$deleted = delete_option( $option );
			}

			$response['status'] = 'success';

			return $response;
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
				$option_data              = get_option( $option );
				$onboarding_data[$option] = $option_data;
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
		public static function is_onboarding_completed() {
			
			$onboarding_complete = get_option( 'ig_es_onboarding_complete', 'no' );

			if ( 'yes' === $onboarding_complete ) {
				return true;
			}

			return false;
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
		 * Method to get lists of required tasks for this task.
		 * 
		 * @return array $required_tasks List of required tasks.
		 */
		public function get_required_tasks( $task_name = '' ) {

			if ( empty( $task_name ) ) {
				return array();
			}

			$required_tasks_mapping = array(
				'check_test_email_on_server' => array(
					'dispatch_emails_from_server',
				),
				'evaluate_email_delivery' => array(
					'check_test_email_on_server'
				),
			);

			$required_tasks = ! empty( $required_tasks_mapping[ $task_name ] ) ? $required_tasks_mapping[ $task_name ] : array();

			return $required_tasks;
		}
	}
	
	IG_ES_Onboarding::instance();
}
