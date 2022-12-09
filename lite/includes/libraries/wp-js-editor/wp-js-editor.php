<?php

/**
 * Function to load required html/js for creating WP Editor dynamically.
 *
 * @since 4.4.1
 */
function ig_es_wp_js_editor_load_scripts() {

	if ( ! class_exists( '_WP_Editors' ) ) {
		require ABSPATH . WPINC . '/class-wp-editor.php';
	}

	$editor_args = array(
		'textarea_rows' => 40,
		'editor_class'  => 'wp-editor-content',
		'media_buttons' => true,
		'tinymce'       => true,
		'quicktags'     => true,
	);
	?>
	<script id="_wp-mce-editor-tpl" type="text/html">
		<?php wp_editor( '', '__wp_mce_editor__', $editor_args ); ?>
	</script>
	<?php
	wp_enqueue_script( 'ig_es_wp_js_editor', plugin_dir_url( __FILE__ ) . 'wp-js-editor.js', array( 'jquery' ), '1.0.0', true );
}

/**
 * Load html/js in admin area.
 *
 * @since 4.4.1
 */
function ig_es_wp_js_editor_admin_scripts() {
	if ( is_admin() ) {
		add_action( 'admin_footer', 'ig_es_wp_js_editor_load_scripts', -1 );
	}
}

/**
 * Load html/js in frontend area.
 *
 * @since 4.4.1
 */
function ig_es_wp_js_editor_frontend_scripts() {
	if ( ! is_admin() ) {
		add_action( 'wp_footer', 'ig_es_wp_js_editor_load_scripts', -1 );
	}
}



