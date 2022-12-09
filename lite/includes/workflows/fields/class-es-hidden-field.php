<?php
/**
 * Email Subscribers' text field
 *
 * @since       5.0.2
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for text field
 *
 * @class ES_Hidden_Field
 */
class ES_Hidden_Field extends ES_Field {

	/**
	 * Input name
	 *
	 * @since 5.0.2
	 *
	 * @var string
	 */
	protected $name = 'hidden_input';

	/**
	 * Input type
	 *
	 * @since 5.0.2
	 *
	 * @var string
	 */
	protected $type = 'hidden';

	/**
	 * Is multiple
	 *
	 * @since 5.0.2
	 *
	 * @var boolean
	 */
	public $multiple = false;

	/**
	 * Define whether HTML entities should be decoded before the field is rendered.
	 *
	 * @since 5.0.2
	 *
	 * @var bool
	 */
	public $decode_html_entities_before_render = true;


	/**
	 * Constructor
	 *
	 * @since 5.0.2
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * Set multiple
	 *
	 * @since 5.0.2
	 *
	 * @param bool $multi Flag for multiple field.
	 *
	 * @return $this
	 */
	public function set_multiple( $multi = true ) {
		$this->multiple = $multi;
		return $this;
	}

	/**
	 * Output the field HTML.
	 *
	 * @since 5.0.2
	 *
	 * @param string $value Field value.
	 */
	public function render( $value ) {
		if ( $this->decode_html_entities_before_render ) {
			$value = html_entity_decode( $value );
		}
		?>
		<input type="<?php echo esc_attr( $this->get_type() ); ?>"
			name="<?php echo esc_attr( $this->get_full_name() ); ?><?php echo $this->multiple ? '[]' : ''; ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="<?php echo esc_attr( $this->get_classes() ); ?>"
			placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>"
			<?php $this->output_extra_attrs(); ?>
			<?php echo ( $this->get_required() ? 'required' : '' ); ?>
			>
		<?php
	}
}
