<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle workflow queue
 * 
 * @class ES_Workflow_Queue
 *
 * @property array $data_items (legacy)
 */
class ES_Workflow_Queue extends ES_DB_Workflows_Queue {

	/**
	 * Raw data received from workflow trigger
	 *
	 * @since 4.4.1
	 * @var bool
	 */
	private $uncompressed_data_layer;


	// error messages
	const F_WORKFLOW_INACTIVE = 100;
	const F_MISSING_DATA      = 101;
	const F_FATAL_ERROR       = 102;

	/**
	 * Workflow queue id
	 *
	 * @since 4.4.1
	 * @var integer queue id 
	 */
	public $id = 0;

	/**
	 * Flag to check whether workflow queue is valid
	 *
	 * @since 4.4.1
	 * @var bool
	 */
	public $exists = false;

	/**
	 * Workflow queue data
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $data = array();

	/**
	 * Variable to store changed field in workflow queue data
	 *
	 * @since 4.4.1
	 * @var array
	 */
	public $changed_fields = array();

	/**
	 * Added Logger Context
	 *
	 * @since 4.2.0
	 * @var array
	 *
	 */
	public $logger_context = array(
		'source' => 'ig_es_workflows_queue'
	);

	/**
	 * Class constructor
	 * 
	 * @param bool|int $id
	 */
	public function __construct( $id = false ) {
		parent::__construct();
		if ( is_numeric( $id ) ) {
			$queue_data = $this->get_by( 'id', $id );
			if ( ! empty( $queue_data ) && is_array( $queue_data ) ) {
				$this->id           = $id;
				$queue_data['meta'] = ! empty( $queue_data['meta'] ) ? maybe_unserialize( $queue_data['meta'] ) : array();
				$this->data         = $queue_data;
				$this->exists       = true;
			}
		} 
	}

	/**
	 * Get workflow queue id
	 * 
	 * @return int
	 */
	public function get_id() {
		return $this->id ? (int) $this->id : 0;
	}


	/**
	 * Set workflow queue id
	 * 
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Set workflow id
	 * 
	 * @param int $id
	 */
	public function set_workflow_id( $id ) {
		$this->set_prop( 'workflow_id', ES_Clean::id( $id ) );
	}


	/**
	 * Get workflow id
	 * 
	 * @return int
	 */
	public function get_workflow_id() {
		return ES_Clean::id( $this->get_prop( 'workflow_id' ) );
	}

	/**
	 * Change workflow queue status as failed.
	 * 
	 * @param bool $failed
	 */
	public function set_failed( $failed = true ) {
		$this->set_prop( 'failed', ig_es_bool_int( $failed ) );
	}


	/**
	 * Check if workflow queue has failed
	 * 
	 * @return bool
	 */
	public function is_failed() {
		return (bool) $this->get_prop( 'failed' );
	}


	/**
	 * Set workflow queue failure code
	 * 
	 * @param int $failure_code
	 */
	public function set_failure_code( $failure_code ) {
		$this->set_prop( 'failure_code', absint( $failure_code ) );
	}


	/**
	 * Get workflow queue failure code
	 * 
	 * @return int
	 */
	public function get_failure_code() {
		return absint( $this->get_prop( 'failure_code' ) );
	}


	/**
	 * Set workflow queue creation date/time
	 * 
	 * @param DateTime $date
	 */
	public function set_created_at( $date ) {
		$this->set_date_column( 'created_at', $date );
	}


	/**
	 * Get workflow queue creation date/time
	 * 
	 * @return bool|DateTime
	 */
	public function get_created_at() {
		return $this->get_date_column( 'created_at' );
	}


	/**
	 * Set schedule time to run the workflow queue
	 * 
	 * @param DateTime $date
	 */
	public function set_scheduled_at( $date ) {
		$this->set_date_column( 'scheduled_at', $date );
	}


	/**
	 * Get schedule time to run the workflow queue
	 * 
	 * @return bool|DateTime
	 */
	public function get_scheduled_at() {
		return $this->get_date_column( 'scheduled_at' );
	}


	/**
	 * Store workflow data in the queue
	 * 
	 * @param ES_Workflow_Data_Layer $data_layer
	 */
	public function store_data_layer( $data_layer ) {

		$this->uncompressed_data_layer = $data_layer->get_raw_data();

		$items_data = array();
		foreach ( $this->uncompressed_data_layer as $data_type_id => $data_item ) {
			$item_data = $this->get_item_data( $data_type_id, $data_item );
			if ( ! empty( $item_data ) ) {
				$items_data = array_merge( $items_data, $item_data );
			}
		}

		if ( ! empty( $items_data ) ) {
			$queue_id = $this->get_id();
			$this->update_meta( $queue_id, $items_data );
		}
	}


	/**
	 * Get workflow data item's data
	 * 
	 * @param $data_type_id
	 * @param $data_item
	 * 
	 * @return $item_data
	 */
	private function get_item_data( $data_type_id, $data_item ) {

		$data_type = ES_Workflow_Data_Types::get( $data_type_id );

		if ( ! $data_type || ! $data_type->validate( $data_item ) ) {
			return array();
		}

		$storage_key   = $data_type_id;
		$storage_value = $data_type->compress( $data_item );
		$item_data     = array();
		if ( $storage_key ) {
			$item_data = array(
				$storage_key => $storage_value
			);
		}
		
		return $item_data;
	}


	/**
	 * Get workflow data layer
	 * 
	 * @return ES_Workflow_Data_Layer
	 */
	public function get_data_layer() {

		if ( ! isset( $this->uncompressed_data_layer ) ) {

			$uncompressed_data_layer = array();
			$compressed_data_layer = $this->get_compressed_data_layer();

			if ( $compressed_data_layer ) {
				foreach ( $compressed_data_layer as $data_type_id => $compressed_item ) {
					$data_type = ES_Workflow_Data_Types::get( $data_type_id );
					if ( $data_type ) {
						$uncompressed_data_layer[$data_type_id] = $data_type->decompress( $compressed_item, $compressed_data_layer );
					}
				}
			}

			$this->uncompressed_data_layer = new ES_Workflow_Data_Layer( $uncompressed_data_layer );
		}

		return $this->uncompressed_data_layer;
	}


	/**
	 * Fetches the data layer from queue meta, but does not decompress
	 * Uses the the supplied_data_items field on the workflows trigger
	 *
	 * @return array|false
	 */
	public function get_compressed_data_layer() {

		$workflow = $this->get_workflow();
		if ( ! $workflow ) {
			return false; // workflow must be set
		}

		if ( ! $this->exists ) {
			return false; // queue must be saved
		}

		$trigger = $workflow->get_trigger();
		if ( ! $trigger ) {
			return false; // need a trigger
		}

		$data_layer = array();

		$supplied_items = $trigger->get_supplied_data_items();

		foreach ( $supplied_items as $data_type_id ) {

			$data_item_value = $this->get_compressed_data_item( $data_type_id, $supplied_items );

			if ( false !== $data_item_value ) {
				$data_layer[ $data_type_id ] = $data_item_value;
			}
		}

		return $data_layer;
	}


	/**
	 * Get data item id from stored queue data
	 * 
	 * @param $data_type_id
	 * @param array $supplied_data_items
	 * @return string|false
	 */
	private function get_compressed_data_item( $data_type_id, $supplied_data_items ) {

		$storage_key = $data_type_id;

		if ( ! $storage_key ) {
			return false;
		}

		return ES_Clean::recursive( $this->get_meta( $storage_key ) );
	}


	/**
	 * Get workflow object
	 * 
	 * Returns the workflow without a data layer
	 *
	 * @return ES_Workflow|false
	 */
	public function get_workflow() {
		return ES_Workflow_Factory::get( $this->get_workflow_id() );
	}


	/**
	 * Run workflow in the queue
	 * 
	 * @return bool
	 */
	public function run() {

		if ( ! $this->exists ) {
			return false;
		}
		
		// mark as failed and then delete if complete, so fatal error will not cause it to run repeatedly
		$this->mark_as_failed( self::F_FATAL_ERROR );
		$this->save();
		$success = false;
		
		$workflow   = $this->get_workflow();
		$data_layer = $this->get_data_layer();
		$workflow->setup( $data_layer );
		
		$failure = $this->do_failure_check( $workflow );
		
		if ( $failure ) {
			// queued event failed
			$this->mark_as_failed( $failure );
		} else {
			$success = true;
			
			// passed fail check so validate workflow and then delete
			if ( $this->validate_workflow( $workflow ) ) {
				$workflow->run();
				$this->delete_queue();
			}
		}

		// important to always clean up
		$workflow->cleanup();
		return $success;
	}


	/**
	 * Returns false if no failure occurred
	 *
	 * @param Workflow $workflow
	 * @return bool|int
	 */
	public function do_failure_check( $workflow ) {

		if ( ! $workflow || ! $workflow->is_active() ) {
			return self::F_WORKFLOW_INACTIVE;
		}

		if ( $this->get_data_layer()->is_missing_data() ) {
			return self::F_MISSING_DATA;
		}

		return false;
	}


	/**
	 * Validate the workflow before running it from the queue.
	 * This validation is different from the initial trigger validation.
	 *
	 * @param $workflow Workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$trigger = $workflow->get_trigger();
		if ( ! $trigger ) {
			return false;
		}

		return true;
	}

	/**
	 * Inserts or updates the model
	 * Only updates modified fields
	 *
	 * @return bool True on success, false on error.
	 */
	public function save() {

		if ( $this->exists ) {
			// update changed fields
			$changed_data = array_intersect_key( $this->data, array_flip( $this->changed_fields ) );

			// serialize
			$changed_data = array_map( 'maybe_serialize', $changed_data );

			if ( empty( $changed_data ) ) {
				return true;
			}

			$queue_id = $this->get_id();

			$updated = $this->update( $queue_id, $changed_data );

			if ( false === $updated ) {
				// Return here to prevent cache updates on error
				return false;
			}

			do_action( 'ig_es_object_update', $this ); // cleans object cache
		} else {
			$this->set_created_at( new DateTime() );
			$this->data = array_map( 'maybe_serialize', $this->data );
			
			// insert row
			$queue_id = $this->insert( $this->data );

			if ( $queue_id ) {
				$this->exists = true;
				$this->id = $queue_id;
			} else {
				/* translators: %s: Table name */
				ES()->logger->error( sprintf( __( 'Could not insert into \'%1$s\' table. \'%1$s\' may not be present in the database.', 'email-subscribers' ), $this->table_name ), $this->logger_context );

				// Return here to prevent cache updates on error
				return false;
			}
		}

		// reset changed data
		// important to reset after cache hooks
		$this->changed_fields = array();
		$this->original_data = $this->data;

		return true;
	}

	/**
	 * Delete workflow queue item.
	 */
	public function delete_queue() {
		$queue_id = $this->get_id();
		parent::delete($queue_id);
	}



	/**
	 * Mark workflow queue item as failed
	 * 
	 * @param int $code
	 */
	public function mark_as_failed( $code ) {
		$this->set_failed();
		$this->set_failure_code( $code );
		$this->save();
	}

	/**
	 * Set workflow queue data option
	 * 
	 * @param $key
	 * @param $value
	 */
	public function set_prop( $key, $value ) {

		if ( is_array( $value ) && ! $value ) {
			$value = ''; // convert empty arrays to blank
		}

		$this->data[$key] = $value;
		$this->changed_fields[] = $key;
	}

	/**
	 * Get workflow queue data option
	 * 
	 * @param $key
	 * @return mixed
	 */
	public function get_prop( $key ) {
		if ( ! isset( $this->data[$key] ) ) {
			return false;
		}

		$value = $this->data[$key];
		$value = maybe_unserialize( $value );

		return $value;
	}

	/**
	 * Sets the value of a date column from  a mixed input.
	 *
	 * $value can be an instance of WC_DateTime the timezone will be ignored.
	 * If $value is a string it must be MYSQL formatted.
	 *
	 * @param string                                 $column
	 * @param DateTime|\DateTime|string $value
	 */
	protected function set_date_column( $column, $value ) {
		if ( is_a( $value, 'DateTime' ) ) {
			// convert to UTC time
			$utc_date = new DateTime();
			$utc_date->setTimestamp( $value->getTimestamp() );
			$this->set_prop( $column, $utc_date->format( 'Y-m-d H:i:s' ) );
		} elseif ( $value ) {
			$this->set_prop( $column, ES_Clean::string( $value ) );
		}
	}

	/**
	 * Get datetime of workflow queue
	 * 
	 * @param $column
	 * @return bool|DateTime
	 */
	protected function get_date_column( $column ) {
		$prop = $this->get_prop( $column );
		if ( $column && $prop ) {
			return new DateTime( $prop );
		}

		return false;
	}

	/**
	 * Get a single meta value by key.
	 *
	 * Returns an empty string if field is empty or doesn't exist.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get_meta( $meta_key = '' ) {
		return ! empty( $this->data['meta'][ $meta_key ] ) ? $this->data['meta'][ $meta_key ] : array();
	}

}
