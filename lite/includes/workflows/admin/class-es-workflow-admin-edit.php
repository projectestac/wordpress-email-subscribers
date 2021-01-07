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
	 * ES_Workflow object
	 *
	 * @since 4.4.1
	 *
	 * @var ES_Workflow|object
	 */
	public static $workflow;

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

		$action      = ig_es_get_request_data( 'action' );
		if ( 'new' === $action ) {
			$title        = __( ' Add New Workflow', 'email-subscribers' );
		} else {
			$title        = __( ' Edit Workflow', 'email-subscribers' );
		}
		?>
		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="md:flex md:items-center md:justify-between justify-center">
					<div class="flex-1 min-w-0">
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
								<a class="hover:underline" href="<?php echo esc_url( $workflows_page_url ); ?>"><?php esc_html_e('Workflows', 'email-subscribers'); ?></a>
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
		
		$meta_box_title_for_trigger			= __( 'Trigger', 'email-subscribers' );
		$meta_box_title_for_actions			= __( 'Actions', 'email-subscribers' );
		$meta_box_title_for_save    	    = __( 'Save', 'email-subscribers' );
		$meta_box_title_for_options 	    = __( 'Options', 'email-subscribers' );
		// $meta_box_title_for_timing  = __( 'Timing', 'email-subscribers' );

		add_meta_box( 'ig_es_workflow_trigger', $meta_box_title_for_trigger, array( __CLASS__, 'trigger_metabox' ), $page_prefix . '_page_es_workflows', 'normal', 'default' );
		add_meta_box( 'ig_es_workflow_actions', $meta_box_title_for_actions, array( __CLASS__, 'actions_metabox' ), $page_prefix . '_page_es_workflows', 'normal', 'default' );
		add_meta_box( 'ig_es_workflow_save', $meta_box_title_for_save, array( __CLASS__, 'save_metabox' ), $page_prefix . '_page_es_workflows', 'side', 'default' );
		if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {
			do_action( 'ig_es_workflows_integration', $page_prefix ); 
		}
		// add_meta_box( 'ig_es_workflow_options', $meta_box_title_for_options, array( __CLASS__, 'options_metabox' ), $page_prefix . '_page_es_workflows', 'side', 'default' ); // phpcs:ignore
		// add_meta_box( 'ig_es_workflow_timing', $meta_box_title_for_timing, array( __CLASS__, 'timing_metabox' ), $page_prefix . '_page_es_workflows', 'side', 'default' ); // phpcs:ignore
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
				'workflow_actions'         => self::$workflow ? self::$workflow->get_actions(): false,
				'action_select_box_values' => $action_select_box_values,
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
	 * Timing meta box
	 *
	 * @since 4.4.1
	 */
	public static function timing_metabox() {
		ES_Workflow_Admin::get_view(
			'meta-box-timing',
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
	 * Method to save workflow
	 *
	 * @since 4.4.1
	 * @param int $workflow_id Workflow ID.
	 * @return mixed $workflow_id/false workflow id on success otherwise false
	 * 
	 * @modified 4.5.3 Removed sanitization for $posted being performed through ig_es_get_request_data function. Instead added individual sanitization based on workflow field.
	 */
	public static function save( $workflow_id = 0 ) {

		$posted = ig_es_get_request_data( 'ig_es_workflow_data', array(), false );

		if ( ! is_array( $posted ) ) {
			return false;
		}

		$workflow_title  = isset( $posted['title'] ) ? ig_es_clean( $posted['title'] ) : '';
		$workflow_name   = ! empty( $workflow_title ) ? sanitize_title( ES_Clean::string( $workflow_title ) ) : '';
		$trigger_name    = isset( $posted['trigger_name'] ) ? ig_es_clean( $posted['trigger_name'] ): '';
		$trigger_options = isset( $posted['trigger_options'] ) ? ig_es_clean( $posted['trigger_options'] ): array();
		$rules           = isset( $posted['rules'] ) ? ig_es_clean( $posted['rules'] ) : array();
		$actions         = isset( $posted['actions'] ) ? $posted['actions'] : array(); // We can't sanitize actions data since some actions like Send email allows html in its field.
		$status          = isset( $posted['status'] ) ? ig_es_clean( $posted['status'] ): 0;
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
			$workflow_updated = ES()->workflows_db->update_workflow( $workflow_id, $workflow_data );
			if ( ! $workflow_updated ) {
				// Return false if update failed.
				return false;
			}
		}

		if ( $workflow_id ) {
			do_action( 'ig_es_workflow_updated', $workflow_id, $workflow_data );
		}

		return $workflow_id;
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
	 * Method to get edit url of a workflow
	 *
	 * @since 4.4.1
	 *
	 * @param  integer $workflow_id Workflow ID.
	 * @return string  $edit_url Workflow edit URL
	 */
	public static function get_edit_url( $workflow_id = 0 ) {

		if ( empty( $workflow_id ) ) {
			return '';
		}

		$edit_url = admin_url( 'admin.php?page=es_workflows' );

		$edit_url = add_query_arg(
			array(
				'action' => 'edit',
				'id'     => $workflow_id,
			),
			$edit_url
		);

		return $edit_url;
	}

}
