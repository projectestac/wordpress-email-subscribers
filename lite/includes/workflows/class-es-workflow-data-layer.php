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
	 * Gets the customer email based on the data layer.
	 *
	 * @return string
	 */
	public function get_customer_email() {

		$customer_email = '';
		$customer       = $this->get_customer();

		if ( $customer ) {
			// If the customer has an account always use the account email over a order billing email
			// The reason for this is that a customer could change their account email and their
			// orders will not be updated.
			$customer_email = $customer->get_email();
		}

		return $customer_email;
	}

	/**
	 * Gets the customer billing address 1.
	 *
	 * @return string
	 */
	public function get_customer_address_1() {
		
		$prop     = '';
		$customer = $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_address_1();
		}

		return $prop;
	}

	/**
	 * Gets the customer billing address 2.
	 *
	 * @return string
	 */
	public function get_customer_address_2() {
		$prop     = '';
		$customer = $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_address_2();
		}

		return $prop;
	}

	/**
	 * Gets the customer first name based on the data layer.
	 *
	 * @return string
	 */
	public function get_customer_first_name() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_first_name();
		}

		return $prop;
	}


	/**
	 * Gets the customer last name based on the data layer.
	 *
	 * @return string
	 */
	public function get_customer_last_name() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_last_name();
		}

		return $prop;
	}


	/**
	 * Gets the customer full name based on the data layer.
	 *
	 * @return string
	 */
	public function get_customer_full_name() {
		/* translators: 1. Customer first name 2. Customer last name */
		return trim( sprintf( _x( '%1$s %2$s', 'full name', 'email-subscribers' ), $this->get_customer_first_name(), $this->get_customer_last_name() ) );
	}


	/**
	 * Gets the customer billing phone.
	 * Doesn't parse or format.
	 *
	 * @return string
	 */
	public function get_customer_phone() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_phone();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing company.
	 *
	 * @return string
	 */
	public function get_customer_company() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_company();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing country code.
	 *
	 * @return string
	 */
	public function get_customer_country() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_country();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing state.
	 *
	 * @return string
	 */
	public function get_customer_state() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_state();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing city.
	 *
	 * @return string
	 */
	public function get_customer_city() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_city();
		}

		return $prop;
	}

	/**
	 * Gets the customer billing postcode.
	 *
	 * @return string
	 */
	public function get_customer_postcode() {
		$prop     = '';
		$customer =  $this->get_customer();

		if ( $customer ) {
			$prop = $customer->get_billing_postcode();
		}

		return $prop;
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
