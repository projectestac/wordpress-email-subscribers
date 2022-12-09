<?php
/**
 * Abstract class for triggers.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Triggers abstract class. All workflow triggers should extend this class.
 *
 * @class ES_Workflow_Trigger
 *
 * @since 4.4.1
 */
abstract class ES_Workflow_Trigger {

	/**
	 * Trigger title
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $title;

	/**
	 * Trigger name
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $name;

	/**
	 * Trigger description
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $description;

	/**
	 * Trigger group
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $group;

	/**
	 * Supplied data by the trigger class
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $supplied_data_items = array();

	/**
	 * Trigger fields
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $fields = array();

	/**
	 * Trigger options
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $options;

	/**
	 * Trigger rules
	 *
	 * @since 4.4.1
	 * @var array
	 */
	protected $rules;

	/**
	 * Flag for trigger fields
	 *
	 * @since 4.4.1
	 * @var boolean
	 */
	protected $has_loaded_fields = false;

	/**
	 * Flag for trigger admin fields
	 *
	 * @since 4.4.1
	 * @var boolean
	 */
	public $has_loaded_admin_details = false;


	/**
	 * Method to register event(user registerd, comment added) to trigger class
	 */
	abstract public function register_hooks();


	/**
	 * Construct
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		$this->init();

		$this->supplied_data_items = array_unique( $this->supplied_data_items );

		add_action( 'ig_es_init_workflow_triggers', array( $this, 'register_hooks' ) );
	}


	/**
	 * Init
	 *
	 * @since 4.4.1
	 */
	public function init() {}


	/**
	 * Method to set title, group, description and other admin props
	 *
	 * @since 4.4.1
	 */
	public function load_admin_details() {}


	/**
	 * Registers any fields used on for a trigger
	 *
	 * @since 4.4.1
	 */
	public function load_fields() {}


	/**
	 * Admin info loader
	 *
	 * @since 4.4.1
	 */
	public function maybe_load_admin_details() {
		if ( ! $this->has_loaded_admin_details ) {
			$this->load_admin_details();
			$this->has_loaded_admin_details = true;
		}
	}

	/**
	 * Field loader
	 *
	 * @since 4.4.1
	 *
	 * @modified 4.5.3 Added new action trigger_name_load_extra_fields to allow loading of extra fields for given trigger.
	 */
	public function maybe_load_fields() {
		if ( ! $this->has_loaded_fields ) {
			// Load fields defined in trigger.
			$this->load_fields();

			// Load extra fields for given trigger.
			do_action( $this->name . '_load_extra_fields', $this );

			$this->has_loaded_fields = true;
		}
	}


	/**
	 * Validate a workflow against trigger
	 *
	 * @since 4.4.1
	 * @param ES_Workflow $workflow workflow object.
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		return true;
	}

	/**
	 * Add trigger option field
	 *
	 * @param object $option Option object.
	 *
	 * @since 4.4.6
	 */
	public function add_field( $option ) {
		$option->set_name_base( 'ig_es_workflow_data[trigger_options]' );
		$this->fields[ $option->get_name() ] = $option;
	}

	/**
	 * Get supplied data item from trigger
	 *
	 * @since 4.4.1
	 * @return array
	 */
	public function get_supplied_data_items() {
		return $this->supplied_data_items;
	}

	/**
	 * Method to get trigger option field
	 *
	 * @param string $name Field name.
	 *
	 * @return ES_Field|false
	 *
	 * @since 4.4.6
	 */
	public function get_field( $name ) {
		$this->maybe_load_fields();

		if ( ! isset( $this->fields[ $name ] ) ) {
			return false;
		}

		return $this->fields[ $name ];
	}


	/**
	 * Method to get trigger option fields
	 *
	 * @return ES_Field[]
	 */
	public function get_fields() {
		$this->maybe_load_fields();
		return $this->fields;
	}

	/**
	 * Check if there are active workflow for this trigger
	 *
	 * @return bool
	 *
	 * @since 4.6.10
	 */
	public function has_workflows() {

		$workflow_query = new ES_Workflow_Query();
		$workflow_query->set_triggers( $this->get_name() );

		$workflows = $workflow_query->get_results();
		if ( ! empty( $workflows ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get workflow ids registered to use this trigger
	 *
	 * @since 4.4.1
	 * @return array
	 */
	public function get_workflow_ids() {

		// Get all workflows that using this trigger.
		$query = new ES_Workflow_Query();
		$query->set_return( 'ids' );
		$query->set_trigger( $this->get_name() );
		$workflows = $query->get_results();

		$workflow_ids = array();
		if ( ! empty( $workflows ) ) {
			foreach ( $workflows as $workflow ) {
				$workflow_ids[] = $workflow['id'];
			}
		}
		return $workflow_ids;
	}

	/**
	 * Get workflow registered to use this trigger
	 *
	 * @since 4.4.1
	 * @return ES_Workflow[]
	 */
	public function get_workflows() {
		$workflows = array();

		foreach ( $this->get_workflow_ids() as $workflow_id ) {
			$workflow = ES_Workflow_Factory::get( $workflow_id );
			if ( $workflow ) {
				$workflows[] = $workflow;
			}
		}

		return apply_filters( 'ig_es_trigger_workflows', $workflows, $this );
	}


	/**
	 * Every data item registered with the trigger should be supplied to this method in its object form.
	 * E.g. a 'user' should be passed as a WP_User object, and an 'order' should be passed as a WC_Order object
	 *
	 * @since 4.4.1
	 * @param ES_Workflow_Data_Layer|array $data_layer Workflow data layer.
	 */
	public function maybe_run( $data_layer = array() ) {

		// Get all workflows that are registered to use this trigger.
		$workflows = $this->get_workflows();
		if ( ! $workflows ) {
			return;
		}

		// Flag to check if we should start the workflow processing immediately.
		$process_immediately = false;

		foreach ( $workflows as $workflow ) {
			// First we need to schedule all the workflows.
			$workflow->schedule( $data_layer );
			$timing_type = $workflow->get_timing_type();

			// Check if there are any workflows which needs to run immediately.
			if ( 'immediately' === $timing_type ) {
				$process_immediately = true;
			}
		}

		if ( $process_immediately ) {

			$request_args = array(
				'action' => 'ig_es_trigger_workflow_queue_processing',
			);

			IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args );
		}
	}


	/**
	 * Get trigger name
	 *
	 * @since 4.4.1
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Set trigger name
	 *
	 * @since 4.4.1
	 * @param string $name trigger name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}


	/**
	 * Get trigger title
	 *
	 * @since 4.4.1
	 * @return string
	 */
	public function get_title() {
		$this->maybe_load_admin_details();
		return $this->title;
	}


	/**
	 * Get trigger group
	 *
	 * @since 4.4.1
	 * @return string
	 */
	public function get_group() {
		$this->maybe_load_admin_details();
		return $this->group ? $this->group : __( 'Other', 'email-subscribers' );
	}


	/**
	 * Get trigger description
	 *
	 * @since 4.4.1
	 * @return string|null
	 */
	public function get_description() {
		$this->maybe_load_admin_details();
		return $this->description;
	}

	/**
	 * Get trigger description html
	 *
	 * @since 4.4.1
	 * @return string
	 */
	public function get_description_html() {

		if ( ! $this->get_description() ) {
			return '';
		}

		return '<p class="ig-es-field-description">' . $this->get_description() . '</p>';
	}

}
