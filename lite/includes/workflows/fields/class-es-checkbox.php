<?php
/**
 * Email Subscribers' checkbox field
 *
 * @since       4.4.3
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for workflow checkbox field
 * 
 * @class ES_Checkbox
 */
class ES_Checkbox extends ES_Field {

	protected $name = 'checkbox';

	protected $type = 'checkbox';

	public $default_to_checked = false;

	/**
	 * Constructor
	 *
	 * @since 4.4.3
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Checkbox', 'email-subscribers' ) );
	}

	/**
	 * Render checkbox field
	 * 
	 * @param $value
	 * 
	 * @since 4.4.3
	 */
	public function render( $value ) {

		if ( null === $value || '' === $value ) {
			$value = $this->default_to_checked;
		}

		?>
		<label>
		<input type="checkbox"
			 name="<?php echo esc_attr( $this->get_full_name() ); ?>"
			 value="1"
			 <?php echo ( $value ? 'checked' : '' ); ?>
			 class="<?php echo esc_attr( $this->get_classes() ); ?>"
			<?php $this->output_extra_attrs(); ?>
			>
			<?php
				echo esc_html( $this->get_title() );
			?>
		</label>
		<?php
	}
}
