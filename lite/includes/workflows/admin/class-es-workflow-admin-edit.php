<?php
/**
 * Workflow admin edit
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle workflow save/edit
 *
 * @class ES_Admin_Workflow_Edit
 *
 * @since 4.4.1
 */
class ES_Workflow_Admin_Edit {

	/**
	 * Class instance.
	 *
	 * @var ES_Workflow_Admin_Edit $instance
	 */
	public static $instance;

	/**
	 * ES_Workflow object
	 *
	 * @since 4.4.1
	 *
	 * @var ES_Workflow|object
	 */
	public static $workflow;

	/**
	 * Get class instance.
	 *
	 * @since 5.0.6
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook in methods
	 *
	 * @since 5.0.6
	 */
	public static function init() {
		add_action( 'ig_es_workflow_inserted', array( __CLASS__, 'update_campaign_data_in_workflow' ), 10, 2 );
		add_action( 'ig_es_workflow_updated', array( __CLASS__, 'update_campaign_data_in_workflow' ), 10, 2 );
		add_action( 'ig_es_workflow_updated', array( __CLASS__, 'delete_unmapped_child_tracking_campaigns' ), 10, 2 );

		add_action( 'ig_es_workflow_inserted', array( __CLASS__, 'update_optin_email_wp_option' ), 10, 2 );
		add_action( 'ig_es_workflow_updated', array( __CLASS__, 'update_optin_email_wp_option' ), 10, 2 );

		add_action( 'ig_es_after_campaign_status_updated', array( __CLASS__, 'update_campaign_workflow_status' ), 10, 2 );

		add_action( 'wp_ajax_ig_es_get_workflow_email_preview', array( __CLASS__, 'get_workflow_email_preview' ) );
		add_action( 'wp_ajax_ig_es_send_workflow_action_test_email', array( __CLASS__, 'send_workflow_action_test_email' ) );

		add_action( 'admin_notices', array( __CLASS__, 'show_membership_integration_notice' ) );
	}

	/**
	 * Handle send test email for send email action
	 *
	 * @since 5.3.6
	 */
	public static function send_workflow_action_test_email() {
		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$email    = sanitize_email( ig_es_get_request_data( 'es_test_email' ) );
		$subject  = ig_es_get_request_data( 'subject', '' );
		$content  = ig_es_get_request_data( 'content', '', false );
		$trigger  = ig_es_get_request_data( 'trigger', '' );
		$template = ig_es_get_request_data( 'template', '' );
		$heading  = ig_es_get_request_data( 'heading', '' );

		$content = ES_Workflow_Action_Preview::get_preview( $trigger, array(
			'action_name'                => 'ig_es_send_email',
			'ig-es-send-to'              => '',
			'ig-es-email-subject'        => $subject,
			'ig-es-email-template'       => $template,
			'ig-es-email-heading'        => $heading,
			'ig-es-email-content'        => $content,
			'ig-es-tracking-campaign-id' => ''
		) );

		$response = ES()->mailer->send_test_email( $email, $subject, $content, array() );

		if ( $response && 'SUCCESS' === $response['status'] ) {
			$response['message'] = __( 'Email has been sent. Please check your inbox', 'email-subscribers' );
		}
		wp_send_json( array( 'status' => 'SUCCESS' ) );
	}

	/**
	 * Get the workflow email preview
	 *
	 * @since 5.3.6
	 */
	public static function get_workflow_email_preview() {
		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array();
		$trigger  = ig_es_get_request_data( 'trigger' );
		$content  = ig_es_get_request_data( 'content', '', false );
		$subject  = ig_es_get_request_data( 'subject', '' );
		$template = ig_es_get_request_data( 'template', '' );
		$heading  = ig_es_get_request_data( 'heading', '' );

		$response['preview_html'] = ES_Workflow_Action_Preview::get_preview( $trigger, array(
			'action_name'                => 'ig_es_send_email',
			'ig-es-send-to'              => '',
			'ig-es-email-subject'        => $subject,
			'ig-es-email-template'       => $template,
			'ig-es-email-heading'        => $heading,
			'ig-es-email-content'        => $content,
			'ig-es-tracking-campaign-id' => ''
		) );

		$response[ 'subject' ] = $subject;


		if ( ! empty( $response ) ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error();
		}


	}

	/**
	 * Method to get trigger data
	 *
	 * @since 4.4.1
	 *
	 * @param ES_Workflow_Trigger $trigger Workflow trigger.
	 *
	 * @return array|false
	 */
	public static function get_trigger_data( $trigger ) {
		$data = array();

		if ( ! $trigger ) {
			return false;
		}

		$data['title']               = $trigger->get_title();
		$data['name']                = $trigger->get_name();
		$data['description']         = $trigger->get_description();
		$data['supplied_data_items'] = array_values( $trigger->get_supplied_data_items() );

		return $data;
	}

	/**
	 * Method to get trigger data
	 *
	 * @since 4.4.1
	 *
	 * @param ES_Workflow_Trigger $trigger Workflow trigger.
	 *
	 * @return array|false
	 */
	public static function get_workflow_data() {

		$data = array(
			'is_new' => true,
		);

		$workflow_id = ig_es_get_request_data( 'id' );
		if ( ! empty( $workflow_id ) && is_numeric( $workflow_id ) ) {
			$workflow = ES_Workflow_Factory::get( $workflow_id );
			if ( $workflow instanceof ES_Workflow ) {
				$data['is_new']  = false;
				$data['trigger'] = $workflow->get_trigger();
			}
		}

		return $data;
	}

	/**
	 * Register Workflow meta boxes
	 *
	 * @since 4.4.1
	 */
	public static function register_meta_boxes() {

		$action = ig_es_get_request_data( 'action' );

		if ( 'new' !== $action && 'edit' !== $action ) {
			// Don't load metaboxes if it isn't not a add/edit workflow screen.
			return;
		}

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metaboxes' ) );
		add_filter( 'screen_options_show_screen', array( __CLASS__, 'remove_screen_options' ) );
		add_filter( 'hidden_meta_boxes', array( __CLASS__, 'show_hidden_workflow_metaboxes' ), 10, 3 );

		/* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
		do_action( 'add_meta_boxes_es_workflows', null );
		do_action( 'add_meta_boxes', 'es_workflows', null );

		/* Enqueue WordPress' script for handling the meta boxes */
		wp_enqueue_script( 'postbox' );

		/* Add screen option: user can choose between 1 or 2 columns (default 2) */
		add_screen_option(
			'layout_columns',
			array(
				'max'     => 2,
				'default' => 2,
			)
		);
	}

	/**
	 * Prints script in footer. This 'initialises' the meta boxes
	 *
	 * @since 4.4.1
	 */
	public static function print_script_in_footer() {
		$action = ig_es_get_request_data( 'action' );

		if ( 'new' !== $action && 'edit' !== $action ) {
			// Don't trigger metabox toggle handler if it isn't not a add/edit workflow screen since there isn't a postbox script enqueued.
			return;
		}
		?>
		<script>
			jQuery(document).ready(function(){
				postboxes.add_postbox_toggles(pagenow);
			});
		</script>
		<?php
	}

	/**
	 * Render Workflows table
	 *
	 * @param int $workflow_id Workflow ID.
	 * @since 4.4.1
	 */
	public static function load_workflow( $workflow_id = null ) {

		if ( ! empty( $workflow_id ) ) {
			self::$workflow = ES_Workflow_Factory::get( $workflow_id );
		}

		self::prepare_workflow_settings_form();
	}

	/**
	 * Method to trigger workflow save when user submits workflow form.
	 *
	 * @since 4.4.1
	 */
	public static function maybe_save() {

		$save_workflow = ig_es_get_request_data( 'save_workflow' );

		if ( ! empty( $save_workflow ) ) {
			$workflow_id    = ig_es_get_request_data( 'workflow_id' );
			$workflow_nonce = ig_es_get_request_data( 'ig-es-workflow-nonce' );
			$action_status  = '';
			if ( ! wp_verify_nonce( $workflow_nonce, 'ig-es-workflow' ) ) {
				$action_status = 'not_allowed';
			} elseif ( ! empty( $workflow_id ) ) {
				$workflow_id = self::save( $workflow_id );
				if ( ! empty( $workflow_id ) ) {
					$action_status = 'updated';
				} else {
					$action_status = 'error';
				}
			} else {
				$workflow_id = self::save();
				if ( ! empty( $workflow_id ) ) {
					$action_status = 'added';
				} else {
					$action_status = 'error';
				}
			}

			if ( in_array( $action_status, array( 'added', 'updated' ), true ) ) {
				$run_workflow = ig_es_get_request_data( 'run_workflow', 'no' );
				if ( 'yes' === $run_workflow ) {
					set_transient( 'ig_es_run_workflow', 'yes', 3 );
				}
			}

			$redirect_url = menu_page_url( 'es_workflows', false );
			$redirect_url = add_query_arg(
				array(
					'id'            => $workflow_id,
					'action_status' => $action_status,
				),
				$redirect_url
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Render Workflow settings form
	 *
	 * @since 4.4.1
	 */
	public static function prepare_workflow_settings_form() {
		$workflow_id        = self::$workflow ? self::$workflow->get_id() : '';
		$workflow_title     = self::$workflow ? self::$workflow->get_title() : '';
		$workflows_page_url = menu_page_url( 'es_workflows', false );

		$action = ig_es_get_request_data( 'action' );
		if ( 'new' === $action ) {
			$title = __( ' Add New Workflow', 'email-subscribers' );
		} else {
			$title = __( ' Edit Workflow', 'email-subscribers' );
		}
		?>
		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="md:flex md:items-center md:justify-between justify-center">
					<div class="flex-1 min-w-0">
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
								<a class="hover:underline" href="<?php echo esc_url( $workflows_page_url ); ?>"><?php esc_html_e( 'Workflows', 'email-subscribers' ); ?></a>
								<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
								</li>
							</ol>
						</nav>
						<h2 class="-mt-1 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate">
							<?php echo esc_html( $title ); ?>
						</h2>
					</div>
				</div>
			</header>
			<form class="mt-5" method="post" action="#">
				<input type="hidden" id="workflow_id" name="workflow_id" value="<?php echo ! empty( $workflow_id ) ? esc_attr( $workflow_id ) : ''; ?>">
				<?php
					// Workflow nonce.
					wp_nonce_field( 'ig-es-workflow', 'ig-es-workflow-nonce', false );

					// Used to save closed metaboxes and their order.
					wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
					wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				?>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div id="titlediv">
								<div id="titlewrap">
									<input type="text" name="ig_es_workflow_data[title]" size="30" value="<?php echo esc_attr( $workflow_title ); ?>" id="title" spellcheck="true"
										autocomplete="off" placeholder="<?php echo esc_attr__( 'Add title', 'email-subscribers' ); ?>" required>
								</div>
							</div>
						</div>
						<div id="postbox-container-1" class="postbox-container">
							<?php
								do_meta_boxes( '', 'side', null );
							?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php
								do_meta_boxes( '', 'normal', null );
								do_meta_boxes( '', 'advanced', null );
							?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Add metaboxes to workflow
	 *
	 * @since 4.4.1
	 */
	public static function add_metaboxes() {

		$page_prefix = ES()->get_admin_page_prefix();

		add_meta_box( 'ig_es_workflow_trigger', __( 'Trigger', 'email-subscribers' ), array( __CLASS__, 'trigger_metabox' ), $page_prefix . '_page_es_workflows', 'normal', 'high' );
		add_meta_box( 'ig_es_workflow_rules', __( 'Rules', 'email-subscribers' ), array( __CLASS__, 'rules_metabox' ), $page_prefix . '_page_es_workflows', 'normal', 'core' );
		add_meta_box( 'ig_es_workflow_actions', __( 'Actions', 'email-subscribers' ), array( __CLASS__, 'actions_metabox' ), $page_prefix . '_page_es_workflows', 'normal', 'low' );
		add_meta_box( 'ig_es_workflow_save', __( 'Save', 'email-subscribers' ), array( __CLASS__, 'save_metabox' ), $page_prefix . '_page_es_workflows', 'side', 'high' );
		add_meta_box( 'ig_es_workflow_variables', __( 'Placeholders', 'email-subscribers' ), array( __CLASS__, 'variables_metabox' ), $page_prefix . '_page_es_workflows', 'side', 'default' );

		if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {
			do_action( 'ig_es_workflows_integration', $page_prefix );
		}
	}

	/**
	 * Method to remove screen options tab on workflow edit page.
	 *
	 * @param bool $show_screen_options
	 *
	 * @return bool $show_screen_options
	 *
	 * @since 4.6.1
	 */
	public static function remove_screen_options( $show_screen_options ) {

		$show_screen_options = false;
		return $show_screen_options;
	}

	/**
	 * Method to forcefully show ES workflow metaboxes if user has hidden them from screen options.
	 *
	 * @param array     $hidden       An array of IDs of hidden meta boxes.
	 * @param WP_Screen $screen       WP_Screen object of the current screen.
	 * @param bool      $use_defaults Whether to show the default meta boxes.
	 *                                Default true.
	 *
	 * @return array $hidden
	 *
	 * @since 4.6.1
	 */
	public static function show_hidden_workflow_metaboxes( $hidden, $screen, $use_defaults ) {

		$es_workflow_metaboxes = array(
			'ig_es_workflow_trigger',
			'ig_es_workflow_actions',
			'ig_es_workflow_save',
		);

		$es_workflow_metaboxes = apply_filters( 'ig_es_display_hidden_workflow_metabox', $es_workflow_metaboxes );

		// Check if user has hidden metaboxes from the screen options.
		if ( ! empty( $hidden ) ) {
			// Remove ES workflows metaboxes from the user's hidden metabox list.
			$hidden = array_diff( $hidden, $es_workflow_metaboxes );
		}

		return $hidden;
	}

	/**
	 * Triggers meta box
	 *
	 * @since 4.4.1
	 */
	public static function trigger_metabox() {
		ES_Workflow_Admin::get_view(
			'meta-box-trigger',
			array(
				'workflow'        => self::$workflow,
				'current_trigger' => self::$workflow ? self::$workflow->get_trigger() : false,
			)
		);
	}

	/**
	 * Actions meta box
	 *
	 * @since 4.4.1
	 */
	public static function actions_metabox() {

		$action_select_box_values = array();

		foreach ( ES_Workflow_Actions::get_all() as $action ) {
			if ( $action instanceof ES_Workflow_Action ) {
				$action_select_box_values[ $action->get_group() ][ $action->get_name() ] = $action->get_title();
			}
		}

		ES_Workflow_Admin::get_view(
			'meta-box-actions',
			array(
				'workflow'                 => self::$workflow,
				'workflow_actions'         => self::$workflow ? self::$workflow->get_actions() : false,
				'action_select_box_values' => $action_select_box_values,
			)
		);
	}

	/**
	 * Get all rules in the workflow
	 *
	 * @return array
	 *
	 * @since 5.5.0
	 */
	public static function get_rules_data() {
		$data = [];

		foreach ( Es_Workflow_Rules::get_all() as $rule ) {
			$rule_data = (array) $rule;
			if ( is_callable( [ $rule, 'get_search_ajax_action' ] ) ) {
				$rule_data['ajax_action'] = $rule->get_search_ajax_action();
			}

			if ( is_callable( [ $rule, 'get_select_choices' ] ) ) {
				$rule_data['select_choices'] = $rule->get_select_choices();
			}
			
			$data[ $rule->name ] = $rule_data;
		}

		return $data;
	}

	/**
	 * Get the workflow rules to edit
	 *
	 * @return array
	 */
	public static function get_workflow_rules() {
		Es_Workflow_Rules::get_all(); // load all the rules into memory so the order is preserved

		if ( self::$workflow ) {
			$rule_options = self::$workflow->get_rule_data();
			foreach ( $rule_options as &$rule_group ) {
				foreach ( $rule_group as &$rule ) {
					if ( ! isset( $rule['name'] ) ) {
						continue;
					}

					$rule_object = Es_Workflow_Rules::get( $rule['name'] );
					if ( ! $rule_object ) {
						continue;
					}

					if ( 'object' === $rule_object->type ) {
						/**
						 * Searchable search rule value field
						 *
						 * @var Es_Rule_Searchable_Select_Abstract $rule_object
						 */
						// If rule has multiple values get the display value for all keys
						if ( $rule_object->is_multi ) {
							foreach ( (array) $rule['value'] as $item ) {
								$rule['selected'][] = $rule_object->get_object_display_value( $item );
							}
						} else {
							$rule['selected'] = $rule_object->get_object_display_value( $rule['value'] );
						}
					} else {
						// Format the rule value
						$rule['value'] = $rule_object->format_value( $rule['value'] );
					}
				}
			}
		} else {
			$rule_options = [];
		}

		return $rule_options;
	}

	/**
	 * Rules meta box
	 *
	 * @since 5.5.0
	 */
	public static function rules_metabox() {
		ES_Workflow_Admin::get_view(
			'meta-box-rules',
			array(
				'workflow'         => self::$workflow,
				'workflow_rules'   => self::get_workflow_rules(),
				'selected_trigger' => self::$workflow ? self::$workflow->get_trigger() : false,
				'all_rules'        => self::get_rules_data(),
			)
		);
	}

	/**
	 * Save workflow meta box
	 *
	 * @since 4.4.1
	 */
	public static function save_metabox() {
		ES_Workflow_Admin::get_view(
			'meta-box-save',
			array(
				'workflow' => self::$workflow,
			)
		);
	}
	
	/**
	 * Options meta box
	 *
	 * @since 4.4.1
	 */
	public static function options_metabox() {
		ES_Workflow_Admin::get_view(
			'meta-box-options',
			array(
				'workflow' => self::$workflow,
			)
		);
	}

	/**
	 * Variables workflow meta box
	 *
	 * @since 4.6.9
	 */
	public static function variables_metabox() {
		ES_Workflow_Admin::get_view(
			'meta-box-variables',
			array(
				'workflow' => self::$workflow,
			)
		);
	}

	/**
	 * Method to save workflow
	 *
	 * @since 4.4.1
	 * @param int $workflow_id Workflow ID.
	 * @return mixed $workflow_id/false workflow id on success otherwise false
	 *
	 * @since 4.5.3 Removed sanitization for $posted being performed through ig_es_get_request_data function. Instead added individual sanitization based on workflow field.
	 */
	public static function save( $workflow_id = 0 ) {

		$posted = ig_es_get_request_data( 'ig_es_workflow_data', array(), false );

		if ( ! is_array( $posted ) ) {
			return false;
		}

		$workflow_title  = isset( $posted['title'] ) ? ig_es_clean( $posted['title'] ) : '';
		$workflow_name   = ! empty( $workflow_title ) ? sanitize_title( ES_Clean::string( $workflow_title ) ) : '';
		$trigger_name    = isset( $posted['trigger_name'] ) ? ig_es_clean( $posted['trigger_name'] ) : '';
		$trigger_options = isset( $posted['trigger_options'] ) ? ig_es_clean( $posted['trigger_options'] ) : array();
		$rules           = isset( $posted['rules'] ) ? self::filter_valid_rules_to_save( ig_es_clean( $posted['rules'] ) ) : array();
		$actions         = isset( $posted['actions'] ) ? $posted['actions'] : array(); // We can't sanitize actions data since some actions like Send email allows html in its field.
		$status          = isset( $posted['status'] ) ? ig_es_clean( $posted['status'] ) : 0;
		$type            = isset( $posted['type'] ) ? ig_es_clean( $posted['type'] ) : 0;
		$priority        = isset( $posted['priority'] ) ? ig_es_clean( $posted['priority'] ) : 0;

		$workflow_meta                = array();
		$workflow_meta['when_to_run'] = self::extract_string_option_value( 'when_to_run', $posted, 'immediately' );

		switch ( $workflow_meta['when_to_run'] ) {

			case 'delayed':
				$workflow_meta['run_delay_value'] = self::extract_string_option_value( 'run_delay_value', $posted );
				$workflow_meta['run_delay_unit']  = self::extract_string_option_value( 'run_delay_unit', $posted );
				break;

			case 'scheduled':
				$workflow_meta['run_delay_value'] = self::extract_string_option_value( 'run_delay_value', $posted );
				$workflow_meta['run_delay_unit']  = self::extract_string_option_value( 'run_delay_unit', $posted );
				$workflow_meta['scheduled_time']  = self::extract_string_option_value( 'scheduled_time', $posted );
				$workflow_meta['scheduled_day']   = self::extract_array_option_value( 'scheduled_day', $posted );
				break;

			case 'fixed':
				$workflow_meta['fixed_date'] = self::extract_string_option_value( 'fixed_date', $posted );
				$workflow_meta['fixed_time'] = self::extract_array_option_value( 'fixed_time', $posted );
				break;

			case 'datetime':
				$workflow_meta['queue_datetime'] = self::extract_string_option_value( 'queue_datetime', $posted );
				break;
		}

		if ( ! empty( $workflow_id ) ) {
			$run_workflow = ig_es_get_request_data( 'run_workflow', 'no' );
			if ( 'no' === $run_workflow ) {
				$existing_meta = ES()->workflows_db->get_column( 'meta', $workflow_id );
				$existing_meta = maybe_unserialize( $existing_meta );
				if ( ! empty( $existing_meta['last_ran_at'] ) ) {
					// Don't update the workflow last run time unless admin check the run workflow option.
					$workflow_meta['last_ran_at'] = $existing_meta['last_ran_at'];
				}
			}
		}

		$workflow_data = array(
			'name'            => $workflow_name,
			'title'           => $workflow_title,
			'trigger_name'    => $trigger_name,
			'trigger_options' => maybe_serialize( $trigger_options ),
			'rules'           => maybe_serialize( $rules ),
			'actions'         => maybe_serialize( $actions ),
			'meta'            => maybe_serialize( $workflow_meta ),
			'status'          => $status,
			'type'            => $type,
			'priority'        => $priority,
		);

		if ( empty( $workflow_id ) ) {
			$workflow_id = ES()->workflows_db->insert_workflow( $workflow_data );
		} else {
			$workflow = new ES_Workflow( $workflow_id );
			if ( $workflow->exists ) {
				$workflow_updated = ES()->workflows_db->update_workflow( $workflow_id, $workflow_data );
				if ( ! $workflow_updated ) {
					// Return false if update failed.
					return false;
				}
			}
		}

		return $workflow_id;
	}

	/**
	 * Filter the rules before saving it into DB
	 *
	 * @param $rules
	 *
	 * @return array
	 */
	public static function filter_valid_rules_to_save( $rules ) {
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return array();
		}
		$valid_rules = array();

		foreach ( $rules as $rule_group ) {
			if ( empty( $rule_group ) || ! is_array( $rule_group ) ) {
				continue;
			}
			$valid_rule_group = array();
			foreach ( $rule_group as $rule ) {
				if ( empty( $rule['name'] ) || empty( $rule['compare'] ) || empty( $rule['value'] ) ) {
					continue;
				}
				array_push( $valid_rule_group, $rule );
			}

			if ( ! empty( $valid_rule_group ) ) {
				array_push( $valid_rules, $valid_rule_group );
			}
		}

		return $valid_rules;
	}

	/**
	 * Update campaign data in workflow
	 *
	 * @param int $workflow_id
	 * @param array $workflow_data
	 * @return void
	 *
	 * @since 5.0.6
	 */
	public static function update_campaign_data_in_workflow( $workflow_id, $workflow_data = array() ) {

		if ( ! empty( $workflow_data['actions'] ) ) {
			$workflow_actions     = maybe_unserialize( $workflow_data['actions'] );
			$actions_data_updated = false;
			if ( ! empty( $workflow_actions ) ) {
				$parent_campaign_id           = ES()->workflows_db->get_workflow_parent_campaign_id( $workflow_id );
				$has_parent_workflow_campaign = ! empty( $parent_campaign_id );
				foreach ( $workflow_actions as $action_index => $action ) {
					$action_name = $action['action_name'];
					if ( 'ig_es_send_email' === $action_name ) {

						if ( empty( $parent_campaign_id ) ) {
							$parent_campaign_id = ES()->workflows_db->create_parent_workflow_campaign( $workflow_id, $workflow_data );
						}

						$tracking_campaign_id = ! empty ( $action['ig-es-tracking-campaign-id'] ) ? $action['ig-es-tracking-campaign-id'] : 0;
						if ( ! empty( $tracking_campaign_id ) ) {
							ES()->workflows_db->update_child_tracking_campaign( $tracking_campaign_id, $action );
						} else {
							$tracking_campaign_id = ES()->workflows_db->create_child_tracking_campaign( $parent_campaign_id, $action );

							$workflow_actions[$action_index]['ig-es-tracking-campaign-id'] = $tracking_campaign_id;
							$actions_data_updated = true;
						}
					}
				}

				if ( $has_parent_workflow_campaign ) {
					ES()->workflows_db->update_parent_workflow_campaign( $parent_campaign_id, $workflow_data );
				}

				$workflow_data['actions'] = maybe_serialize( $workflow_actions );
			}

			if ( $actions_data_updated ) {
				ES()->workflows_db->update_workflow( $workflow_id, $workflow_data );
			}
		}

	}

	/**
	 * Delete unmapped tracking campaigns
	 *
	 * @param int $workflow_id
	 * @param array $workflow_data
	 * @return void
	 *
	 * @since 5.0.6
	 */
	public static function delete_unmapped_child_tracking_campaigns( $workflow_id, $workflow_data = array() ) {

		$unmapped_child_tracking_campaigns_ids = self::get_unmapped_child_tracking_campaigns_ids( $workflow_id );
		if ( ! empty( $unmapped_child_tracking_campaigns_ids ) ) {
			ES()->campaigns_db->delete_campaigns( $unmapped_child_tracking_campaigns_ids );
		}

	}

	/**
	 * Get ids of unmapped child tracking campaigns(campaigns who don't have any associated send email action in workflow)
	 *
	 * @param int $workflow_id
	 * @return array $unmapped_child_tracking_campaigns_ids
	 *
	 * @since 5.0.6
	 */
	public static function get_unmapped_child_tracking_campaigns_ids( $workflow_id ) {

		$mapped_child_tracking_campaign_ids = self::get_mapped_child_tracking_campaign_ids( $workflow_id );
		$all_child_tracking_campaign_ids    = ES()->workflows_db->get_all_child_tracking_campaign_ids( $workflow_id );

		$unmapped_child_tracking_campaigns_ids = array_diff( $all_child_tracking_campaign_ids, $mapped_child_tracking_campaign_ids );

		return $unmapped_child_tracking_campaigns_ids;
	}

	/**
	 * Get ids of mapped child tracking campaigns ids(campaigns who have any associated send email action in workflow)
	 *
	 * @param int $workflow_id
	 * @return array $mapped_child_tracking_campaign_ids
	 *
	 * @since 5.0.6
	 */
	public static function get_mapped_child_tracking_campaign_ids( $workflow_id ) {

		$mapped_child_tracking_campaign_ids = array();

		$workflow = new ES_Workflow( $workflow_id );
		if ( $workflow->exists ) {
			$workflow_actions = $workflow->get_actions();
			if ( ! empty( $workflow_actions ) ) {
				foreach ( $workflow_actions as $workflow_action ) {
					$action_name = $workflow_action->get_name();
					if ( 'ig_es_send_email' === $action_name ) {
						$tracking_campaign_id = $workflow_action->get_option( 'ig-es-tracking-campaign-id', false );
						if ( ! empty( $tracking_campaign_id ) ) {
							$mapped_child_tracking_campaign_ids[] = $tracking_campaign_id;
						}
					}
				}
			}
		}

		return $mapped_child_tracking_campaign_ids;
	}

	/**
	 * Update optin email option in Options
	 *
	 * @param int $workflow_id
	 * @param array $workflow_data
	 * @return void
	 *
	 * @since 5.3.4
	 */
	public static function update_optin_email_wp_option( $workflow_id, $workflow_data = array() ) {

		$trigger_name 		  = isset( $workflow_data['trigger_name'] ) ? $workflow_data['trigger_name'] : '';
		$optin_email_triggers = array(
			'ig_es_user_subscribed',
			'ig_es_user_unconfirmed',
		);

		$is_optin_email_trigger = in_array( $trigger_name, $optin_email_triggers, true );

		if ( $is_optin_email_trigger ) {
			$workflow_has_actions = ! empty( $workflow_data['actions'] );
			if ( $workflow_has_actions ) {
				$workflow_actions = maybe_unserialize( $workflow_data['actions'] );
				foreach ( $workflow_actions as $action_index => $action ) {
					$action_name = ! empty( $action['action_name'] ) ? $action['action_name'] : '';
					if ( 'ig_es_send_email' === $action_name ) {
						$email_subject = ! empty( $action['ig-es-email-subject'] ) ? $action['ig-es-email-subject'] : '';
						$email_content = ! empty( $action['ig-es-email-content'] ) ? $action['ig-es-email-content'] : '';
						if ( 'ig_es_user_subscribed' === $trigger_name ) {
							$email_subject_wp_option = 'ig_es_welcome_email_subject';
							$email_content_wp_option = 'ig_es_welcome_email_content';
						} else {
							$email_subject_wp_option = 'ig_es_confirmation_mail_subject';
							$email_content_wp_option = 'ig_es_confirmation_mail_content';
						}
						update_option( $email_subject_wp_option, $email_subject );
						update_option( $email_content_wp_option, $email_content );
					}
				}
			}

		}
	}

	/**
	 * Update workflow linked to campaign
	 *
	 * @param int $campaign_ids
	 * @param array $new_status
	 * @return void
	 *
	 * @since 5.3.4
	 */
	public static function update_campaign_workflow_status( $campaign_ids, $new_status = 0 ) {

		if ( empty( $campaign_ids ) ) {
			return;
		}

		$campaign_ids = is_array( $campaign_ids ) ? $campaign_ids : array( $campaign_ids );

		$linked_workflow_ids = array();
		foreach ( $campaign_ids as $campaign_id ) {
			$campaign = ES()->campaigns_db->get( $campaign_id );
			if ( ! empty( $campaign ) ) {
				$campaign_type        = $campaign['type'];
				$is_workflow_campaign = IG_CAMPAIGN_TYPE_WORKFLOW === $campaign_type;
				if ( $is_workflow_campaign ) {
					$workflow_id = $campaign['parent_id'];
					if ( ! empty( $workflow_id ) ) {
						$workflow = new ES_Workflow( $workflow_id );
						if ( $workflow->exists ) {
							$linked_workflow_ids[] = $workflow_id;
						}
					}
				}
			}
		}

		if ( ! empty( $linked_workflow_ids ) ) {
			ES()->workflows_db->update_status( $linked_workflow_ids, $new_status );
		}
	}

	/**
	 * Returns option value from workflow option data string
	 *
	 * @since 4.4.1
	 *
	 * @param string $option Option name.
	 * @param array  $posted Posted data.
	 * @param string $default Default value.
	 *
	 * @return string
	 */
	public static function extract_string_option_value( $option, $posted, $default = '' ) {
		return isset( $posted['workflow_options'][ $option ] ) ? ES_Clean::string( $posted['workflow_options'][ $option ] ) : $default;
	}

	/**
	 * Returns option value array from workflow option data array
	 *
	 * @since 4.4.1
	 *
	 * @param string $option Option name.
	 * @param array  $posted Posted data.
	 * @param string $default Default value.
	 *
	 * @return array
	 */
	public static function extract_array_option_value( $option, $posted, $default = array() ) {
		return isset( $posted['workflow_options'][ $option ] ) ? ES_Clean::recursive( $posted['workflow_options'][ $option ] ) : $default;
	}

	/**
	 * Method to get admin edit url of a workflow
	 *
	 * @param int $workflow_id
	 * @return string  $edit_url Workflow edit URL
	 *
	 * @since 5.3.8
	 */
	public static function get_admin_edit_url( $workflow_id ) {

		$edit_url = admin_url( 'admin.php?page=es_workflows' );

		$edit_url = add_query_arg(
			array(
				'id'     => $workflow_id,
				'action' => 'edit',
			),
			$edit_url
		);

		return $edit_url;
	}

	public static function show_membership_integration_notice() {

		$notice_pages = array( 'es_workflows' );
		$current_page = ig_es_get_request_data( 'page' );
		$is_notice_page = in_array( $current_page, $notice_pages, true );
		if ( ! $is_notice_page || ! ES()->is_pro() ) {
			return;
		}

		global $ig_es_tracker;

		$supported_membership_plugins = array(
			'sfwd-lms/sfwd_lms.php' => 'LearnDash',
			'ultimate-member/ultimate-member.php' => 'Ultimate Member',
			'paid-memberships-pro/paid-memberships-pro.php' => 'Paid Memberships Pro',
			'memberpress/memberpress.php' => 'MemberPress',
			'woocommerce-memberships/woocommerce-memberships.php' => 'WooCommerce Memberships',
		);

		$active_plugins = $ig_es_tracker::get_active_plugins();
		$supported_active_plugins = array();

		foreach ( $supported_membership_plugins as $plugin_slug => $plugin_name ) {
			if ( in_array( $plugin_slug, $active_plugins, true ) ) {
				$supported_active_plugins[] = $plugin_slug;
			}
		}

		if ( empty( $supported_active_plugins ) ) {
			return;
		}
		
		$workflow_gallery_url  = admin_url( 'admin.php?page=es_workflows&tab=gallery');
		$workflow_gallery_url .= '&integration-plugins=' . implode( ',', $supported_active_plugins );
		$supported_plugin_slug = $supported_active_plugins[0]; // We are showing only first plugin name from supported and active plugins.
		$supported_plugin_name = $supported_membership_plugins[ $supported_plugin_slug ];

		$membership_integration_notice_shown = get_option( 'ig_es_membership_integration_notice_shown', 'no' );
		if ( 'no' === $membership_integration_notice_shown ) {
			?>
		<div class="notice notice-success is-dismissible p-2">
			<h2 class="ig-es-workflow-gallery-item-title font-medium text-gray-600 tracking-wide text-base mb-2">
			<?php
				/* translators: 1. Email Subscriber name 3. Supported plugin name */
				echo sprintf( esc_html__( 'Connect %1$s and %2$s', 'email-subscribers' ), '<strong>Icegram Express</strong>)', '<strong>' . esc_html( $supported_plugin_name ) . '</strong>' );
			?>
			</h2>
			<p>
				<?php
					/* translators: 1. Plugin name */
					echo sprintf( esc_html__( 'Automatically sync your %1$s users/members into %2$s\'s audience list through our workflow integrations.', 'email-subscribers' ), '<strong>' . esc_html( $supported_plugin_name ) . '</strong>', '<strong>Icegram Express</strong>)', '<strong>' );
				?>
				<br/>
				<a href="<?php echo esc_url( $workflow_gallery_url ); ?>" class="ig-es-primary-button px-3 py-1 mt-2 align-middle">
					<?php
						echo esc_html__( 'Browse workflows', 'email-subscribers' );
					?>
				</a>
			</p>
		</div>
		<?php
			update_option( 'ig_es_membership_integration_notice_shown', 'yes', false );
		}
	}
}
