<?php

/**
 * Class to handle single workflow options
 * 
 * @class Workflow
 *
 * @credit: Inspired by AutomateWoo
 */
class ES_Workflow {

	/**
	 * Workflow id
	 *
	 * @since 4.4.1
	 * @var int
	 */
	public $id;

	/**
	 * Workflow title
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $title;

	/**
	 * Workflow name(slug)
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $name;

	/**
	 * Workflow trigger object
	 *
	 * @since 4.4.1
	 * @var ES_Workflow_Trigger
	 */
	private $trigger;

	/**
	 * Workflow actions
	 *
	 * @since 4.4.1
	 * @var ES_Workflow_Actions[]
	 */
	private $actions;

	/**
	 * Workflow data absctraction class object
	 *
	 * @since 4.4.1
	 * @var ES_Workflow_Data_Layer
	 */
	private $data_layer;

	/**
	 * Workflow status
	 *
	 * @since 4.4.1
	 * @var integer
	 */
	public $status = 0;

	/**
	 * Workflow trigger name
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $trigger_name;

	/**
	 * Workflow trigger options
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $trigger_options;

	/**
	 * Workflow rules
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $rules;

	/**
	 * Workflow meta data
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $meta;

	/**
	 * Workflow priority
	 *
	 * @since 4.4.1
	 * @var integer
	 */
	public $priority = 0;

	/**
	 * Workflow creation date/time
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $created_at;

	/**
	 * Workflow last update date/time
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $updated_at;

	/**
	 * Flag to check whether valid workflow object or not
	 *
	 * @since 4.4.1
	 * @var bool
	 */
	public $exists = false;

	/**
	 * Added Logger Context
	 *
	 * @since 4.2.0
	 * @var array
	 */
	public $logger_context = array(
		'source' => 'ig_es_workflow',
	);

	/**
	 * Class constructor
	 * 
	 * @param $post mixed (object or post ID)
	 */
	public function __construct( $workflow = null ) {

		if ( is_numeric( $workflow ) ) {
			// Get from id
			$workflow = self::get_instance( $workflow );
		}

		if ( is_object( $workflow ) ) {
			$this->exists          = true;
			$this->id              = $workflow->id;
			$this->name            = $workflow->name;
			$this->title           = $workflow->title;
			$this->trigger_name    = $workflow->trigger_name;
			$this->trigger_options = maybe_unserialize( $workflow->trigger_options );
			$this->rules           = maybe_unserialize( $workflow->rules );
			$this->actions         = maybe_unserialize( $workflow->actions );
			$this->meta            = maybe_unserialize( $workflow->meta );
			$this->status          = $workflow->status;
			$this->priority        = $workflow->priority;
			$this->created_at      = $workflow->created_at;
			$this->updated_at      = $workflow->updated_at;
		}
	}

	/**
	 * Retrieve ES_Workflow instance.
	 *
	 * @since 3.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $workflow_id Worfklow ID.
	 * @return ES_Workflow|false Worfklow object, false otherwise.
	 */
	public static function get_instance( $workflow_id = 0 ) {
		global $wpdb;

		$workflow_id = ES_Clean::id( $workflow_id );
		if ( ! $workflow_id ) {
			return false;
		}

		$_workflow = wp_cache_get( $workflow_id, 'ig_es_workflows' );

		if ( ! $_workflow ) {
			$_workflow = ES()->workflows_db->get_workflow( $workflow_id, 'object' );
			if ( ! $_workflow ) {
				return false;
			}

			wp_cache_add( $_workflow->id, $_workflow, 'ig_es_workflows' );
		}

		return new ES_Workflow( $_workflow );
	}


	/**
	 * Get workflow id
	 * 
	 * @return int
	 */
	public function get_id() {
		return $this->id ? ES_Clean::id( $this->id ) : 0;
	}


	/**
	 * Get workflow title
	 * 
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}


	/**
	 * Get creation date/time of workflow.
	 * 
	 * @return string
	 */
	public function get_date_created() {
		return $this->created_at;
	}

	/**
	 * Get workflow data layer object.
	 * 
	 * @return ES_Workflow_Data_Layer
	 */
	public function data_layer() {
		if ( ! isset( $this->data_layer ) ) {
			$this->data_layer = new ES_Workflow_Data_Layer();
		}

		return $this->data_layer;
	}


	/**
	 * Get workflow trigger object
	 * 
	 * @return ES_Workflow_Trigger|false
	 */
	public function get_trigger() {
		if ( ! isset( $this->trigger ) ) {

			$this->trigger = false;
			$trigger_name  = $this->get_trigger_name();

			if ( $trigger_name && ES_Workflow_Triggers::get( $trigger_name ) ) {
				// @todo clone triggers just to retrieve options now seems a little confusing and inefficient
				$this->trigger = clone ES_Workflow_Triggers::get( $trigger_name );
			}
		}

		return $this->trigger;
	}


	/**
	 * Get all actions in current workflow.
	 * 
	 * @return ES_Workflow_Action[]
	 */
	public function get_actions() {

		$workflow_actions = array();

		if ( ! empty( $this->actions ) ) {

			$actions_data = $this->get_actions_data();

			if ( ! empty( $actions_data ) && is_array( $actions_data ) ) {
				$n = 1;
				foreach ( $actions_data as $action ) {
					try {
						$action_obj = clone $this->get_action_from_action_fields( $action );
						$action_obj->set_options( $action );
						$action_obj->trigger    = $this->get_trigger();
						$workflow_actions[ $n ] = $action_obj;
						$n++;
					} catch ( Exception $e ) {
						continue;
					}
				}
			}
		}

		return $workflow_actions;
	}


	/**
	 * Returns the saved actions with their data
	 *
	 * @param $number
	 * @return Action|false
	 */
	public function get_action( $number ) {

		$actions = $this->get_actions();

		if ( ! isset( $actions[ $number ] ) ) {
			return false;
		}

		return $actions[ $number ];
	}

	/**
	 * Method to schedule workflow
	 *
	 * @param ES_Workflow_Data_Layer|array $data_layer
	 * @param bool                         $skip_validation
	 */
	public function schedule( $data_layer = array(), $skip_validation = false ) {

		// setup language and data before validation occurs
		$this->setup( $data_layer );

		if ( $this->is_missing_required_data() ) {
			return;
		}

		if ( $skip_validation || $this->validate_workflow() ) {
			$queue = $this->queue( true );
			if ( $queue instanceof ES_Workflow_Queue ) {
					$queue_id = $queue->get_id();
				if ( ! empty( $queue_id ) ) {
					$queue_scheduled_at = $queue->get_scheduled_at();
					if ( $queue_scheduled_at ) {
						$action_args = array(
							'queue_id' => $queue_id,
						);
						if ( function_exists( 'as_schedule_single_action' ) ) {
							as_schedule_single_action( $queue_scheduled_at, 'ig_es_process_workflow_queue', array( $action_args ), 'email-subscribers' );
						}
					}
				}
			}
		}

		$this->cleanup();
	}

	/**
	 * Check if workflow is missing some required data.
	 *
	 * This must be run after the setup() method.
	 *
	 * @since 4.6
	 *
	 * @return bool
	 */
	public function is_missing_required_data() {
		if ( ! $this->exists ) {
			return true;
		}

		if ( ! $this->get_trigger() ) {
			return true;
		}

		if ( $this->data_layer()->is_missing_data() ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate workflow based on received data from the workflow trigger object.
	 * 
	 * @return bool
	 */
	public function validate_workflow() {

		if ( ! $this->is_active() ) {
			return false;
		}

		$trigger = $this->get_trigger();
		if ( ! $trigger ) {
			return false;
		}

		if ( ! $trigger->validate_workflow( $this ) ) {
			return false;
		}
		
		// Allow third party developers to hook their validation logic.
		if ( ! apply_filters( 'ig_es_custom_validate_workflow', true, $this ) ) {
			return false;
		}
		
		// Validate a workflow based on the trigger being used in it.
		if ( ! apply_filters( 'ig_es_validate_workflow_' . $trigger->name, true, $this ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Execute workflow actions.
	 * 
	 * @param  int <parameter_name> { parameter_description }
	 * @return bool
	 */
	public function run() {

		do_action( 'ig_es_before_workflow_run', $this );

		$actions = $this->get_actions();

		foreach ( $actions as $action_index => $action ) {

			$action->workflow = $this;

			try {
				do_action( 'ig_es_before_action_run', $action, $this );
				$action->run();
				do_action( 'ig_es_after_action_run', $action, $this );
			} catch ( \Exception $e ) {
				// Log exceptions as errors
				ES()->logger->error( $action->get_title() . ':' . $e->getMessage(), $this->logger_context );
			}
		}

		do_action( 'ig_es_after_workflow_run', $this );

		return true;
	}

	/**
	 * Reset the workflow object
	 * Clears any data that is related to the last run
	 * The trigger and actions don't need to be reset because their data flows from the workflow options not the workflow data layer
	 */
	public function reset_data() {
		$this->data_layer()->clear();
	}


	/**
	 * Create queued event from workflow
	 *
	 * @return ES_Workflow_Queue|false
	 */
	public function queue() {

		$date  = false;
		$queue = new ES_Workflow_Queue();
		$queue->set_workflow_id( $this->get_id() );
		$timing_type = $this->get_timing_type();

		switch ( $timing_type ) {

			case 'immediately':
				$date = new DateTime();
				$date->setTimestamp( time() );
				break;

			case 'delayed':
				$date = new DateTime();
				$date->setTimestamp( time() + $this->get_timing_delay() );
				break;

			case 'scheduled':
				$date = $this->calculate_scheduled_datetime();
				break;

			case 'fixed':
				$date = $this->get_fixed_time();
				break;

			case 'datetime':
				$date = $this->get_variable_time();
				break;

		}

		$date = apply_filters( 'ig_es_workflow_queue_date', $date, $this );

		if ( ! $date ) {
			return false;
		}

		$queue->set_scheduled_at( $date );
		$queue->save();

		$queue->store_data_layer( $this->data_layer() ); // add meta data after saved

		return $queue;
	}

	/**
	 * Setup the state of the workflow before it is validated or checked
	 *
	 * @param array|Data_Layer|bool $data
	 */
	public function setup( $data = false ) {

		// the only time data is false is in preview mode
		if ( $data ) {
			$this->set_data_layer( $data, true );
		}

		$this->is_setup = true;
	}

	/**
	 * Clean up after workflow run
	 */
	public function cleanup() {

		$this->is_setup = false;
	}


	/**
	 * Get workflow option data.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function get_option( $name ) {

		$options = $this->meta;

		if ( ! is_array( $options ) || ! isset( $options[ $name ] ) ) {
			return false;
		}

		return apply_filters( 'ig_es_workflow_option', $options[ $name ], $name, $this );
	}


	/**
	 * Returns options are immediately, delayed, scheduled, datetime
	 *
	 * @since 4.4.0
	 * @return string
	 */
	public function get_timing_type() {
		$when        = ES_Clean::string( $this->get_option( 'when_to_run' ) );
		$workflow_id = $this->get_id();
		if ( ! $when ) {
			$when = 'immediately';
		}
		return $when;
	}


	/**
	 * Return the delay period in seconds
	 *
	 * @since 4.4.0
	 * @return integer
	 */
	public function get_timing_delay() {

		$timing_type = $this->get_timing_type();

		if ( ! in_array( $timing_type, array( 'delayed', 'scheduled' ) ) ) {
			return 0;
		}

		$number = $this->get_timing_delay_number();
		$unit   = $this->get_timing_delay_unit();

		$units = array(
			'm' => MINUTE_IN_SECONDS,
			'h' => HOUR_IN_SECONDS,
			'd' => DAY_IN_SECONDS,
			'w' => WEEK_IN_SECONDS,
		);

		if ( ! $number || ! isset( $units[ $unit ] ) ) {
			return 0;
		}

		return $number * $units[ $unit ];
	}


	/**
	 * Get set delay in workflow execution from scheduled date/time.
	 * 
	 * @return int
	 */
	public function get_timing_delay_number() {
		return (float) $this->get_option( 'run_delay_value' );
	}


	/**
	 * Get unit of the delay settings.
	 * 
	 * @return string
	 */
	public function get_timing_delay_unit() {
		return ES_Clean::string( $this->get_option( 'run_delay_unit' ) );
	}


	/**
	 * Calculate the next point in time that matches the workflow scheduling options
	 *
	 * @param bool|integer $current_timestamp - optional, not GMT
	 * @return bool|DateTime
	 */
	public function calculate_scheduled_datetime( $current_timestamp = false ) {

		if ( $this->get_timing_type() !== 'scheduled' ) {
			return false;
		}

		if ( ! $current_timestamp ) {
			$current_timestamp = current_time( 'timestamp' ); // calculate based on the local timezone
		}

		// scheduled day and time are in the sites specified timezone
		$scheduled_time                        = $this->get_scheduled_time();
		$scheduled_days                        = $this->get_scheduled_days();
		$scheduled_time_seconds_from_day_start = ES_Workflow_Time_Helper::calculate_seconds_from_day_start( $scheduled_time );

		// get minimum datetime before scheduling can happen, if no delay is set then this will be now
		$min_wait_datetime = new ES_Workflow_DateTime();
		$min_wait_datetime->setTimestamp( $current_timestamp + $this->get_timing_delay() );
		$min_wait_time_seconds_from_day_start = ES_Workflow_Time_Helper::calculate_seconds_from_day_start( $min_wait_datetime );

		// check to see if the scheduled time of day is later than the min wait time
		$is_scheduled_time_later_than_min_wait_time = $min_wait_time_seconds_from_day_start < $scheduled_time_seconds_from_day_start;

		// if the scheduled time comes before the current min wait time we can not schedule on the same day as the min wait
		// therefore update the min wait datetime so that is its midnight of the next day
		if ( ! $is_scheduled_time_later_than_min_wait_time ) {
			$min_wait_datetime->modify( '+1 day' );
		}

		$min_wait_datetime->set_time_to_day_start(); // set time to midnight, time will be added on later

		// check if scheduled day matches the min wait day
		if ( $scheduled_days && ! in_array( $min_wait_datetime->format( 'N' ), $scheduled_days ) ) {

			// advance time until a matching day is found
			while ( ! in_array( $min_wait_datetime->format( 'N' ), $scheduled_days ) ) {
				$min_wait_datetime->modify( '+1 day' );
			}
		}

		$scheduled_time = new ES_Workflow_DateTime();
		$scheduled_time->setTimestamp( $min_wait_datetime->getTimestamp() );
		$scheduled_time->modify( "+$scheduled_time_seconds_from_day_start seconds" );
		$scheduled_time->convert_to_utc_time();

		return $scheduled_time;
	}


	/**
	 * Get scheduled time to run workflow.
	 * 
	 * @return string
	 */
	public function get_scheduled_time() {
		return ES_Clean::string( $this->get_option( 'scheduled_time' ) );
	}


	/**
	 * Returns empty if set to any day, 1 (for Monday) through 7 (for Sunday)
	 *
	 * @return array
	 */
	public function get_scheduled_days() {
		return ES_Clean::ids( $this->get_option( 'scheduled_day' ) );
	}


	/**
	 * Get the fixed time to run the workflow
	 * 
	 * @return DateTime|bool
	 */
	public function get_fixed_time() {

		$date = ES_Clean::string( $this->get_option( 'fixed_date' ) );
		$time = array_map( 'absint', (array) $this->get_option( 'fixed_time' ) );

		if ( ! $date ) {
			return false;
		}

		$datetime = new ES_Workflow_DateTime( $date );
		$datetime->setTime( isset( $time[0] ) ? $time[0] : 0, isset( $time[1] ) ? $time[1] : 0, 0 );
		$datetime->convert_to_utc_time();

		return $datetime;
	}


	/**
	 * Get scheduled date as set by variable timing option
	 *
	 * @return DateTime|bool
	 */
	public function get_variable_time() {
		$datetime = $this->get_option( 'queue_datetime', true );

		if ( ! $datetime ) {
			return false;
		}

		$timestamp = strtotime( $datetime, current_time( 'timestamp' ) );

		$date = new DateTime();
		$date->setTimestamp( $timestamp );
		$date->convert_to_utc_time();

		return $date;
	}

	/**
	 * Get the name of the workflow's trigger.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_trigger_name() {
		return ES_Clean::string( $this->trigger_name );
	}

	/**
	 * Set the trigger for the workflow.
	 *
	 * @since 4.4.0
	 *
	 * @param $trigger_name
	 */
	public function set_trigger_name( $trigger_name ) {
		$this->update_meta( 'trigger_name', ES_Clean::string( $trigger_name ) );
		unset( $this->trigger );
	}

	/**
	 * Get the workflow trigger options.
	 * Values will be sanitized as per the fields set on the trigger object.
	 *
	 * @return array
	 */
	public function get_trigger_options() {
		$options = $this->trigger_options;
		return is_array( $options ) ? $options : array();
	}

	/**
	 * Get's the value of workflow trigger option.
	 *
	 * @param string $name
	 * @param bool|string $default used when value is not set, this should only be if the option was added workflow was created
	 *
	 * @return mixed Will vary depending on the field type specified in the trigger's fields.
	 * 
	 * @since 4.4.6
	 */
	public function get_trigger_option( $name, $default = false ) {
		$options = $this->get_trigger_options();

		if ( isset( $options[$name] ) ) {
			$value = $options[$name];
		} else {
			$value = $default;
		}

		return $value;
	}

	/**
	 * Get actions data for the workflow.
	 *
	 * Values will be formatted as per the fields set on the action object.
	 *
	 * @since 4.4.0
	 * @since 4.8.0 Added formatting to the fields.
	 *
	 * @return array
	 */
	public function get_actions_data() {
		$actions_data = $this->actions;
		return is_array( $actions_data ) ? array_map( array( $this, 'format_action_fields' ), $actions_data ) : array();
	}

	/**
	 * Set the workflow actions data.
	 *
	 * Values will be sanitized as per the fields set on the action object. Data is
	 * only sanitized before write, not before read.
	 *
	 * @since 4.4.0
	 *
	 * @param array $raw_actions_data
	 */
	public function set_actions_data( $raw_actions_data ) {
		$actions_data = array_map( array( $this, 'sanitize_action_fields' ), $raw_actions_data );
		// remove empty values from actions array
		$actions_data = array_filter( $actions_data );
		$this->update_meta( 'actions', $actions_data );
		unset( $this->actions );
	}

	/**
	 * Sanitizes a array of action fields for a single action.
	 *
	 * @since 4.4.0
	 *
	 * @param array $action_fields
	 *
	 * @return array
	 */
	public function sanitize_action_fields( $action_fields ) {
		try {
			$action = $this->get_action_from_action_fields( $action_fields );
		} catch ( Exception $e ) {
			return array();
		}

		$sanitized = array(
			'action_name' => $action->get_name(),
		);

		foreach ( $action_fields as $name => $value ) {
			$name      = ES_Clean::string( $name );
			$field_obj = $action->get_field( $name );

			if ( $field_obj ) {
				$field_value = $field_obj->sanitize_value( $value );
				// encode emojis to avoid emoji serialization issues
				$field_value        = ES_Clean::encode_emoji( $field_value );
				$sanitized[ $name ] = $field_value;
			}
		}

		return $sanitized;
	}

	/**
	 * Format action fields according to the field type.
	 *
	 * @since 4.8.0
	 *
	 * @param array $action_fields
	 *
	 * @return array
	 */
	public function format_action_fields( $action_fields ) {
		try {
			$action = $this->get_action_from_action_fields( $action_fields );
		} catch ( Exception $e ) {
			return array();
		}

		$formatted = array(
			'action_name' => $action->get_name(),
		);

		foreach ( $action_fields as $name => $value ) {
			$name      = ES_Clean::string( $name );
			$field_obj = $action->get_field( $name );

			$formatted[ $name ] = $value;
		}

		return $formatted;
	}

	/**
	 * Set data item in workflow data layer.
	 * 
	 * @param $name
	 * @param $item
	 */
	public function set_data_item( $name, $item ) {
		$this->data_layer()->set_item( $name, $item );
	}


	/**
	 * Set workflow data layer.
	 * 
	 * @param array|data_layer $data_layer
	 * @param bool             $reset_workflow_data
	 */
	public function set_data_layer( $data_layer, $reset_workflow_data ) {

		if ( ! is_a( $data_layer, 'ES_Workflow_Data_Layer' ) ) {
			$data_layer = new ES_Workflow_Data_Layer( $data_layer );
		}

		if ( $reset_workflow_data ) {
			$this->reset_data();
		}

		$this->data_layer = $data_layer;
	}


	/**
	 * Retrieve and validate a data item
	 *
	 * @param $name string
	 * @return mixed
	 */
	public function get_data_item( $name ) {
		return $this->data_layer()->get_item( $name );
	}


	/**
	 * Is workflow active.
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( ! $this->exists ) {
			return false;
		}

		return $this->get_status() === 'active';
	}

	/**
	 * Get workflow status.
	 *
	 * Possible statuses are active|inactive|trash
	 *
	 * @since 4.6
	 *
	 * @return string
	 */
	public function get_status() {
		$status = $this->status;
		if ( 1 == $status ) {
			$status = 'active';
		} elseif ( 0 == $status ) {
			$status = 'inactive';
		}

		return $status;
	}


	/**
	 * Update worflow status.
	 * 
	 * @param string $status active|inactive i.e 1|0
	 */
	public function update_status( $status ) {

		if ( 'active' === $status ) {
			$workflow_status = 1;
		} elseif ( 'inactive' === $status ) {
			$workflow_status = 0;
		} else {
			$workflow_status = $status;
		}

		$workflow_id = $this->get_id();

		$status_updated = ES()->workflows_db->update_status( $workflow_id, $workflow_status );

		return $status_updated;
	}

	/**
	 * Get workflow meta data from meta key.
	 * 
	 * @param $key
	 * @param bool $single
	 * @return mixed
	 */
	public function get_meta( $key, $single = true ) {
		return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
	}

	/**
	 * Get an action based on field data.
	 *
	 * @since 4.8.0
	 *
	 * @param array $field_data
	 *
	 * @return Action The action object for the data.
	 * @throws Exception When the Action could not be resolved to an object.
	 */
	public function get_action_from_action_fields( $field_data ) {
		if ( ! is_array( $field_data ) || ! isset( $field_data['action_name'] ) ) {
			throw new Exception( __( 'Missing action_name key in array.', 'email-subscribers' ) );
		}

		$action_name = ES_Clean::string( $field_data['action_name'] );
		$action      = ES_Workflow_Actions::get( $action_name );
		if ( ! $action ) {
			throw new Exception( __( 'Could not retrieve the action.', 'email-subscribers' ) );
		}

		return $action;
	}
}
