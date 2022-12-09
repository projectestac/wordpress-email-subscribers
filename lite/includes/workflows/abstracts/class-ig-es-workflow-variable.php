<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for workflow placeholder variables
 */
abstract class IG_ES_Workflow_Variable {

	/**
	 * Variable name
	 * wc_order.id, cart.link, user.first_name etc
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Variable description
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Stores parameter field objects.
	 *
	 * @var ES_Field[]
	 */
	protected $parameter_fields = array();

	/**
	 * Variable data type
	 * e.g. wc_order in wc_order.id, cart in cart.link, user in user.first_name etc
	 *
	 * @var string
	 */
	protected $data_type;

	/**
	 * Variable data field
	 * e.g. id in wc_order.id, link in cart.link, first_name in user.first_name etc
	 *
	 * @var string
	 */
	protected $data_field;

	/**
	 * Does variable support fallback value in case no value is found
	 *
	 * @var bool
	 */
	public $use_fallback = true;

	/**
	 * Knows if admin details have been loaded.
	 *
	 * @var bool
	 */
	public $has_loaded_admin_details = false;


	/**
	 * Optional method
	 */
	public function init() {}


	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {}


	public function maybe_load_admin_details() {
		if ( ! $this->has_loaded_admin_details ) {
			$this->load_admin_details();
			$this->has_loaded_admin_details = true;
		}
	}


	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}


	/**
	 * Sets the name, data_type and data_field props
	 *
	 * @param $name
	 */
	public function setup( $name ) {
		$this->name                                 = $name;
		list( $this->data_type, $this->data_field ) = explode( '.', $this->name );
	}


	/**
	 * Get variable description
	 *
	 * @return string
	 */
	public function get_description() {
		$this->maybe_load_admin_details();
		return $this->description;
	}


	/**
	 * Get the parameter fields for the variable.
	 *
	 * @return ES_Field[]
	 */
	public function get_parameter_fields() {
		$this->maybe_load_admin_details();
		return $this->parameter_fields;
	}


	/**
	 * Get variable name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Get variable data type
	 *
	 * @return string
	 */
	public function get_data_type() {
		return $this->data_type;
	}


	/**
	 * Get variable data field
	 *
	 * @return string
	 */
	public function get_data_field() {
		return $this->data_field;
	}

	/**
	 * Add a parameter field to the variable.
	 *
	 * @param ES_Field $field
	 */
	protected function add_parameter_field( ES_Field $field ) {
		$this->parameter_fields[ $field->get_name() ] = $field;
	}

	/**
	 * Add a text parameter field to the variable.
	 *
	 * @param string $name
	 * @param string $description
	 * @param bool   $required
	 * @param string $placeholder
	 * @param array  $extra
	 */
	protected function add_parameter_text_field( $name, $description, $required = false, $placeholder = '', $extra = array() ) {
		$field = new ES_Text();
		$field->set_name( $name );
		$field->set_description( $description );
		$field->set_required( $required );
		$field->set_placeholder( $placeholder );
		$field->meta = $extra;
		if ( $required ) {
			$field->add_extra_attr( 'data-required', 'yes' );
		}

		$this->add_parameter_field( $field );
	}

	/**
	 * Add a select parameter field to the variable.
	 *
	 * @param string $name
	 * @param string $description
	 * @param array  $options
	 * @param bool   $required
	 * @param array  $extra
	 */
	protected function add_parameter_select_field( $name, $description, $options = array(), $required = false, $extra = array() ) {
		$field = new ES_Select( false );
		$field->set_name( $name );
		$field->set_description( $description );
		$field->set_required( $required );
		$field->set_options( $options );
		$field->meta = $extra;
		if ( $required ) {
			$field->add_extra_attr( 'data-required', 'yes' );
		}

		$this->add_parameter_field( $field );
	}

	/**
	 * Add a number parameter field to the variable.
	 *
	 * @param string $name
	 * @param string $description
	 * @param bool   $required
	 * @param string $placeholder
	 * @param array  $extra
	 *
	 * @since 5.3.4
	 */
	public function add_parameter_number_field( $name, $description, $required = false, $placeholder = '', $extra = array()) {
		$field = new ES_Number();
		$field->set_name( $name );
		$field->set_description( $description );
		$field->set_required( $required );
		$field->set_placeholder( $placeholder );
		$field->meta = $extra;
		if ( $required ) {
			$field->add_extra_attr( 'data-required', 'yes' );
		}

		$this->add_parameter_field( $field );
	}

}
