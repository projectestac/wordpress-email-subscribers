<?php

/**
 * Class to handle data absctraction from worflow trigger
 * 
 * @class ES_Workflow_Data_Layer
 */
class ES_Workflow_Data_Layer {

	private $data = array();

	/**
	 * Constructor
	 * 
	 * @param array $data
	 */
	public function __construct( $data = array() ) {

		if ( is_array( $data ) ) {
			$this->data = $data;
		}

		$this->init();
	}


	/**
	 * Initiate the data layer
	 */
	public function init() {
		do_action( 'ig_es_data_layer_init' );
	}


	public function clear() {
		$this->data = array();
	}


	/**
	 * Returns unvalidated data layer
	 *
	 * @return array
	 */
	public function get_raw_data() {
		return $this->data;
	}


	/**
	 * Set data item
	 * 
	 * @param $type
	 * @param $item
	 */
	public function set_item( $type, $item ) {
		$this->data[ $type ] = $item;
	}


	/**
	 * Get data item
	 * 
	 * @param string $type
	 * @return mixed
	 */
	public function get_item( $type ) {

		if ( ! isset( $this->data[ $type ] ) ) {
			return false;
		}

		return ig_es_validate_data_item( $type, $this->data[ $type ] );
	}

	/**
	 * Is the data layer missing data?
	 *
	 * Data can be missing if it has been deleted e.g. if an order has been trashed.
	 *
	 * @since 4.6
	 *
	 * @return bool
	 */
	public function is_missing_data() {
		$is_missing = false;

		foreach ( $this->get_raw_data() as $data_item ) {

			if ( ! $data_item ) {
				$is_missing = true;
			}
		}

		return $is_missing;
	}

	/**
	 * Get customer object from data layer
	 * 
	 * @return IG_ES_Customer|false
	 */
	public function get_customer() {
		return $this->get_item( 'customer' );
	}


	/**
	 * Get cart object from data layer
	 * 
	 * @return IG_ES_Cart|false
	 */
	public function get_cart() {
		return $this->get_item( 'cart' );
	}

	/**
	 * Get cart object from data layer
	 * 
	 * @return IG_ES_Guest|false
	 */
	public function get_guest() {
		return $this->get_item( 'guest' );
	}

}
