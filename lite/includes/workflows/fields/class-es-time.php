<?php
/**
 * Icegram Express' time field
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for Time field
 *
 * @class ES_Time
 *
 * @since 4.4.1
 */
class ES_Time extends ES_Field {

	/**
	 * Field name
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $name = 'time';

	/**
	 * Field type
	 *
	 * @var string
	 */
	protected $type = 'text';

	/**
	 * Flag to show 24 hours note
	 *
	 * @since 4.4.1
	 *
	 * @var boolean
	 */
	protected $show_24hr_note = true;

	/**
	 * Set the maximum value for the hours field.
	 *
	 * @since 4.4.1
	 *
	 * @var int
	 */
	public $max_hours = 23;

	/**
	 * Constructor
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Time', 'email-subscribers' );
	}

	/**
	 * Set 24 Hours note
	 *
	 * @since 4.4.1
	 *
	 * @param boolean $show Flag to show 24 hours notice.
	 *
	 * @return $this
	 */
	public function set_show_24hr_note( $show ) {
		$this->show_24hr_note = $show;
		return $this;
	}


	/**
	 * Render field
	 *
	 * @since 4.4.1
	 *
	 * @param array $value  Field value.
	 */
	public function render( $value ) {
		if ( $value ) {
			$value = ES_Clean::recursive( (array) $value );
		} else {
			$value = array( '', '' );
		}

		?>
		<div class="ig-es-time-field-group">
			<div class="ig-es-time-field-group__fields">
		<?php
		$field = new ES_Number();
		$field
			->set_name_base( $this->get_name_base() )
			->set_name( $this->get_name() )
			->set_min( 0 )
			->set_max( $this->max_hours )
			->set_multiple()
			->set_placeholder( _x( 'HH', 'time field', 'email-subscribers' ) )
			->render( $value[0] );

		echo '<div class="ig-es-time-field-group__sep">:</div>';

		$field = new ES_Number();
		$field
			->set_name_base( $this->get_name_base() )
			->set_name( $this->get_name() )
			->set_min( 0 )
			->set_max( 59 )
			->set_multiple()
			->set_placeholder( _x( 'MM', 'time field', 'email-subscribers' ) )
			->render( $value[1] );

		?>
		</div>

		<?php if ( $this->show_24hr_note ) : ?>
			<span class="ig-es-time-field-group__24hr-note"><?php esc_html_e( '(24 hour time)', 'email-subscribers' ); ?></span>
		<?php endif; ?>

		</div>

		<?php
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.1
	 *
	 * @param array $value Field value.
	 *
	 * @return array
	 */
	public function sanitize_value( $value ) {
		$value = ES_Clean::recursive( $value );

		$value[0] = min( $this->max_hours, $value[0] );

		return $value;
	}

}
