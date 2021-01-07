<?php
/**
 * Email Subscribers' number field
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for number field
 *
 * @class ES_Number
 *
 * @since 4.4.1
 */
class ES_Number extends ES_Text {

	/**
	 * Field type
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $name = 'number_input';

	/**
	 * Field type
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $type = 'number';

	/**
	 * Constructor
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Number', 'email-subscribers' );
	}


	/**
	 * Set minimumu allowed value
	 *
	 * @since 4.4.1
	 *
	 * @param string $min minimum value.
	 *
	 * @return $this
	 */
	public function set_min( $min ) {
		$this->add_extra_attr( 'min', $min );
		return $this;
	}


	/**
	 * Set maimum allowed value
	 *
	 * @since 4.4.1
	 *
	 * @param string $max maximum value.
	 *
	 * @return $this
	 */
	public function set_max( $max ) {
		$this->add_extra_attr( 'max', $max );
		return $this;
	}

	/**
	 * Sanitizes the value of a number field.
	 *
	 * If the field is not required, the field can be left blank.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value Field value.
	 *
	 * @return string|float
	 */
	public function sanitize_value( $value ) {
		$value = trim( $value );

		if ( ! $this->get_required() ) {
			// preserve empty string values, don't cast to float.
			if ( '' === $value ) {
				return '';
			}
		}

		return (float) $value;
	}

}
