<?php
/**
 * Email Subscribers' date field
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for date field
 *
 * @class Date
 *
 * @since 4.4.1
 */
class ES_Date extends ES_Text {

	/**
	 * Constructor
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		parent::__construct();

		$this->title = __( 'Date', 'email-subscribers' );
		$this->name  = 'date';
		$this->set_placeholder( 'YYYY-MM-DD' );
		$this->add_extra_attr( 'autocomplete', 'off' );

		$this->add_classes( 'ig-es-date-picker date-picker' );
	}
}
