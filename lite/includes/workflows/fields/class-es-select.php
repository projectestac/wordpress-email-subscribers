<?php
/**
 * Icegram Express' select field
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for select field
 *
 * @class Select
 */
class ES_Select extends ES_Field {

	/**
	 * Field name
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $name = 'select';

	/**
	 * Field type
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $type = 'select';

	/**
	 * Field options
	 *
	 * @since 4.4.1
	 *
	 * @var string
	 */
	protected $default_option;

	/**
	 * Allow multiple choices
	 *
	 * @since 4.4.1
	 *
	 * @var boolean
	 */
	public $multiple = false;

	/**
	 * Select field options
	 *
	 * @since 4.4.1
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Construct
	 *
	 * @since 4.4.1
	 *
	 * @param bool $show_placeholder Should show placeholder.
	 */
	public function __construct( $show_placeholder = true ) {
		parent::__construct();

		$this->set_title( __( 'Select', 'email-subscribers' ) );

		if ( $show_placeholder ) {
			$this->set_placeholder( __( '[Select]', 'email-subscribers' ) );
		}
	}


	/**
	 * Set select options
	 *
	 * @since 4.4.1
	 *
	 * @param array $options Select options.
	 *
	 * @return $this
	 */
	public function set_options( $options ) {
		$this->options = $options;
		return $this;
	}


	/**
	 * Get select options
	 *
	 * @since 4.4.1
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}


	/**
	 * Set select default option
	 *
	 * @since 4.4.1
	 *
	 * @param string $option default option.
	 *
	 * @return $this
	 */
	public function set_default( $option ) {
		$this->default_option = $option;
		return $this;
	}


	/**
	 * Set multiple flag
	 *
	 * @since 4.4.1
	 *
	 * @param bool $multi Is multiple.
	 *
	 * @return $this
	 */
	public function set_multiple( $multi = true ) {
		$this->multiple = $multi;
		return $this;
	}

	/**
	 * Render field
	 *
	 * @since 4.4.1
	 *
	 * @param string $value field value.
	 *
	 * @return void
	 */
	public function render( $value = false ) {

		$value = ES_Clean::recursive( $value );

		if ( $this->multiple ) {
			if ( ! $value ) {
				$value = $this->default_option ? $this->default_option : array();
			}

			$this->render_multiple( (array) $value );
		} else {
			if ( empty( $value ) && $this->default_option ) {
				$value = $this->default_option;
			}

			$this->render_single( (string) $value );
		}

	}


	/**
	 * Render a single select box.
	 *
	 * @since 4.4.1
	 *
	 * @param string $value field value.
	 */
	protected function render_single( $value ) {
		?>

		<select name="<?php echo esc_attr( $this->get_full_name() ); ?>"
				data-name="<?php echo esc_attr( $this->get_name() ); ?>"
				class="<?php echo esc_attr( $this->get_classes() ); ?>"
				<?php $this->output_extra_attrs(); ?>
				<?php echo ( $this->get_required() ? 'required' : '' ); ?>
		>

			<?php if ( $this->get_placeholder() ) : ?>
				<option value=""><?php echo esc_html( $this->get_placeholder() ); ?></option>
			<?php endif; ?>

			<?php foreach ( $this->get_options() as $opt_name => $opt_value ) : ?>
				<?php if ( is_array( $opt_value ) ) : ?>
					<optgroup label="<?php echo esc_attr( $opt_name ); ?>">
						<?php foreach ( $opt_value as $opt_sub_name => $opt_sub_value ) : ?>
							<option value="<?php echo esc_attr( $opt_sub_name ); ?>" <?php selected( $value, $opt_sub_name ); ?>><?php echo esc_html( $opt_sub_value ); ?></option>
						<?php endforeach ?>
					</optgroup>
				<?php else : ?>
					<option value="<?php echo esc_attr( $opt_name ); ?>" <?php selected( $value, $opt_name ); ?>><?php echo esc_html( $opt_value ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>

		</select>

		<?php
	}


	/**
	 * Render a multi-select box.
	 *
	 * @since 4.4.1
	 *
	 * @param array $values field value.
	 */
	protected function render_multiple( $values ) {
		?>
		<select name="<?php echo esc_attr( $this->get_full_name() ); ?>[]"
				data-name="<?php echo esc_attr( $this->get_name() ); ?>"
				class="<?php echo esc_attr( $this->get_classes() ); ?> wc-enhanced-select"
				multiple="multiple"
				data-placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>"
			<?php $this->output_extra_attrs(); ?>
		>

			<?php foreach ( $this->get_options() as $opt_name => $opt_value ) : ?>
				<option value="<?php echo esc_attr( $opt_name ); ?>"
					<?php echo in_array( (string) $opt_name, $values, true ) ? 'selected="selected"' : ''; ?>
					><?php echo esc_html( $opt_value ); ?></option>
			<?php endforeach; ?>

		</select>

		<script type="text/javascript">
			jQuery(document).ready(function(){
				if( 'function' === typeof jQuery.fn.ig_es_select2 ) {
					jQuery('.ig-es-form-multiselect[data-name="<?php echo esc_attr( $this->get_name() ); ?>"]').ig_es_select2();
				}
			});
		</script>

		<?php
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.1
	 *
	 * @param array|string $value Field value.
	 *
	 * @return array|string
	 */
	public function sanitize_value( $value ) {
		if ( $this->multiple ) {
			return ES_Clean::recursive( $value );
		} else {
			return ES_Clean::string( $value );
		}
	}

}
