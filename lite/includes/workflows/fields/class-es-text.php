<?php
/**
 * Email Subscribers' text field
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for text field
 *
 * @class ES_Text
 */
class ES_Text extends ES_Field {

	/**
	 * Input name
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $name = 'text_input';

	/**
	 * Input type
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $type = 'text';

	/**
	 * Is multiple
	 *
	 * @since 4.4.1
	 *
	 * @var boolean
	 */
	public $multiple = false;

	/**
	 * Define whether HTML entities should be decoded before the field is rendered.
	 *
	 * @since 4.4.1
	 *
	 * @var bool
	 */
	public $decode_html_entities_before_render = true;


	/**
	 * Constructor
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Text Input', 'email-subscribers' );
	}


	/**
	 * Set multiple
	 *
	 * @since 4.4.1
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
	 * @since 4.4.1
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
