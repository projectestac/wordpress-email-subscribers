<?php
/**
 * Icegram Express' WP Editor field
 *
 * @since       4.5.3
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for WP Editor field
 *
 * @class ES_WP_Editor
 * 
 * @since 4.5.3
 */
class ES_WP_Editor extends ES_Field {

	protected $name = 'ig_es_wp_editor';

	protected $type = 'textarea';

	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'WP Editor', 'email-subscribers' ) );
	}

	/**
	 * Render wp editor field
	 * 
	 * @param string $value
	 * 
	 * @since 4.5.3
	 */
	public function render( $value ) {

		$id    = $this->get_id();
		$value = ES_Clean::editor_content( $value );

		// If it is an ajax request then load wp editor using js library.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			?>
			<textarea name="<?php echo esc_attr( $this->get_full_name() ); ?>" id="<?php echo esc_attr( $id ); ?>">
			</textarea>
			<?php
			$this->ajax_init( $id );
		} else {
			// If it is not an ajax request then load wp editor using WordPress wp_editor PHP function.
			wp_editor( $value, $id, array(
				'textarea_name' => $this->get_full_name(),
				'tinymce'       => true, // default to visual
				'quicktags'     => true,
			));
		}
	}

	/**
	 * Initialize ajax loading of wp editor field
	 * 
	 * @param int $id ID of the field.
	 * 
	 * @since 4.5.3
	 */
	public function ajax_init( $id ) {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('#<?php echo esc_js( $id ); ?>').wp_js_editor();
			});
		</script>
		<?php
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * @param string $value
	 *
	 * @return string
	 * 
	 * @since 4.5.3
	 */
	public function sanitize_value( $value ) {
		return ES_Clean::editor_content( $value );
	}
}
