<?php
/**
 * Email Subscribers' field abstract class
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Abstract class Email Subsribers' fields.
 *
 * @class ES_Field
 *
 * @since 4.4.1
 */
abstract class ES_Field {

	/**
	 * Field title
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Field id
	 *
	 * @since 5.0.8
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Field name
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Field type
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Field description
	 *
	 * @since 4.4.6
	 * 
	 * @var string
	 */
	protected $description;

	/**
	 * Field name base
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $name_base;

	/**
	 * Is field required
	 *
	 * @since 4.4.1
	 *
	 * @var bool
	 */
	protected $required = false;

	/**
	 * Field classes
	 *
	 * @since 4.4.1
	 *
	 * @var array
	 */
	protected $classes = array();

	/**
	 * Container element classes
	 *
	 * @since 5.3.9
	 *
	 * @var array
	 */
	protected $container_classes = array();

	/**
	 * Extra attributes that will appended to the HTML field element.
	 *
	 * @since 4.4.1
	 *
	 * @var array
	 */
	protected $extra_attrs = array();

	/**
	 * Field placeholder
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $placeholder = '';

	/**
	 * Output the field HTML.
	 *
	 * @since 4.4.1
	 *
	 * @param mixed $value Field value.
	 */
	abstract public function render( $value );

	/**
	 * Field constructor.
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		$this->classes[] = 'ig-es-field';
		$this->classes[] = 'ig-es-field--type-' . $this->type;
	}

	/**
	 * Set field id
	 *
	 * @since 5.0.8
	 *
	 * @param string $name Field name.
	 *
	 * @return $this
	 */
	public function set_id( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Set field name
	 *
	 * @since 4.4.1
	 *
	 * @param string $name Field name.
	 *
	 * @return $this
	 */
	public function set_name( $name ) {
		$this->name = $name;
		return $this;
	}


	/**
	 * Set field title
	 *
	 * @since 4.4.1
	 *
	 * @param string $title Field title.
	 *
	 * @return $this
	 */
	public function set_title( $title ) {
		$this->title = $title;
		return $this;
	}


	/**
	 *
	 * Get field title
	 *
	 * @since 4.4.1
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title ? $this->title : '';
	}

	/**
	 *
	 * Get field id
	 *
	 * @since 5.0.8
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id ? $this->id : '';
	}

	/**
	 *
	 * Get field name
	 *
	 * @since 4.4.1
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name ? $this->name : '';
	}


	/**
	 * Get field type
	 *
	 * @since 4.4.1
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Set field description
	 * 
	 * @since 4.4.6
	 * 
	 * @param $description
	 * 
	 * @return $this
	 */
	public function set_description( $description ) {
		$this->description = $description;
		return $this;
	}


	/**
	 * Get field description
	 * 
	 * @since 4.4.6
	 * 
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 *
	 * Set field placeholder
	 *
	 * @since 4.4.1
	 *
	 * @param string $placeholder Field placeholder.
	 *
	 * @return $this
	 */
	public function set_placeholder( $placeholder ) {
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 *
	 * Get field placeholder
	 *
	 * @since 4.4.1
	 *
	 * @return string
	 */
	public function get_placeholder() {
		return $this->placeholder;
	}


	/**
	 *
	 * Add field classes
	 *
	 * @since 4.4.1
	 *
	 * @param string $classes field classes.
	 *
	 * @return $this
	 */
	public function add_classes( $classes ) {
		$this->classes = array_merge( $this->classes, explode( ' ', $classes ) );
		return $this;
	}


	/**
	 * Get field classes
	 *
	 * @since 4.4.1
	 *
	 * @param bool $implode Should implode.
	 *
	 * @return array|string
	 */
	public function get_classes( $implode = true ) {
		if ( $implode ) {
			return implode( ' ', $this->classes );
		}
		return $this->classes;
	}

	/**
	 *
	 * Add container field classes
	 *
	 * @since 5.3.9
	 *
	 * @param string $container_classes container field classes.
	 *
	 * @return $this
	 */
	public function add_container_classes( $container_classes ) {
		$this->container_classes = array_merge( $this->container_classes, explode( ' ', $container_classes ) );
		return $this;
	}


	/**
	 * Get container field classes
	 *
	 * @since 5.3.9
	 *
	 * @param bool $implode Should implode.
	 *
	 * @return array|string
	 */
	public function get_container_classes( $implode = true ) {
		if ( $implode ) {
			return implode( ' ', $this->container_classes );
		}
		return $this->container_classes;
	}

	/**
	 * Get extra attributes for field.
	 *
	 * @since 4.4.1
	 *
	 * @param string $name Field name.
	 * @param string $value Field value.
	 *
	 * @return $this
	 */
	public function add_extra_attr( $name, $value = null ) {
		$this->extra_attrs[ $name ] = $value;
		return $this;
	}

	/**
	 * Add data attribute to field
	 *
	 * @since 4.4.1
	 *
	 * @param string $name Field name.
	 * @param string $value Field value.
	 *
	 * @return $this
	 */
	public function add_data_attr( $name, $value = null ) {
		$this->add_extra_attr( 'data-' . $name, $value );
		return $this;
	}


	/**
	 * Outputs the extra field attrs in HTML attribute format.
	 *
	 * @since 4.4.1
	 * 
	 * @modified 4.5.4 Removed echo to allow escaping of attribute.
	 */
	public function output_extra_attrs() {

		foreach ( $this->extra_attrs as $name => $value ) {
			if ( is_null( $value ) ) {
				echo esc_attr( $name ) . ' ';
			} else {
				echo esc_attr( $name ) . '="' . esc_attr( $value ) . '" ';
			}
		}
	}


	/**
	 * Set field to be required
	 *
	 * @since 4.4.1
	 *
	 * @param bool $required Should be required.
	 *
	 * @return $this
	 */
	public function set_required( $required = true ) {
		$this->required = $required;
		return $this;
	}


	/**
	 * Check if field is required
	 *
	 * @since 4.4.1
	 *
	 * @return bool
	 */
	public function get_required() {
		return $this->required;
	}

	/**
	 * Set field name attribute
	 *
	 * @since 4.4.1
	 *
	 * @param string $name_base Field base name.
	 *
	 * @return $this
	 */
	public function set_name_base( $name_base ) {
		$this->name_base = $name_base;
		return $this;
	}

	/**
	 * Get field base name value
	 *
	 * @since 4.4.1
	 *
	 * @return bool
	 */
	public function get_name_base() {
		return $this->name_base;
	}

	/**
	 * Get field full name including base name and field name.
	 *
	 * @since 4.4.1
	 *
	 * @return string
	 */
	public function get_full_name() {
		return ( $this->get_name_base() ? $this->get_name_base() . '[' . $this->get_name() . ']' : $this->get_name() );
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * This method runs before WRITING a value to the DB but doesn't run before READING.
	 *
	 * Defaults to sanitize as a single line string. Override this method for fields that should be sanitized differently.
	 *
	 * @since 4.4.1
	 *
	 * @param string $value Field value.
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		return ES_Clean::string( $value );
	}
}
