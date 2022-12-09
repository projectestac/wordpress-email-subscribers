<?php


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */
class ES_GB_Subscription_Form_Block {
	
	// class instance
	public static $instance;

	// class constructor
	public function __construct() {
		$this->init();
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	public function init() {
		add_action( 'init', array( $this, 'register_gb_subscription_form_block' ) );
		add_action( 'enqueue_block_editor_assets', array($this,'enqueue_assets') );
	}

	public function enqueue_assets() {
	   
		wp_register_style( 'ig-es-gb-subscription-form-block-css', plugin_dir_url( __FILE__ ) . 'css/gb-subscription-form-block.css', array('wp-edit-blocks'), ES_PLUGIN_VERSION, 'all' );

		wp_register_script( 'ig-es-gb-subscription-form-block-js', plugin_dir_url( __FILE__ ) . 'js/gb-subscription-form-block.js', array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ), ES_PLUGIN_VERSION, true );

		$es_forms = ES()->forms_db->get_all();
		wp_localize_script( 'ig-es-gb-subscription-form-block-js', 'es_forms', $es_forms );
	}

	public function register_gb_subscription_form_block() {
		
		if ( ! function_exists( 'register_block_type' ) ) {
			// Block editor is not available.
			return;
		}

		register_block_type(
		  'email-subscribers/subscription-form-block',
		  array(
			 'editor_style'    => 'ig-es-gb-subscription-form-block-css',
			 'editor_script'   => 'ig-es-gb-subscription-form-block-js',
			 'api_version'     => 2,
			 'render_callback' => array( $this, 'render_form' ),
			 'attributes'      => array(
				'formID'       => array(
					'type' => 'number'
				)
			 ),
		  )
		);     
	}

	public function render_form( $attributes ) {
		ob_start();
		$formID = isset( $attributes['formID'] ) ? $attributes['formID'] : '';
		if ( ! empty( $formID ) ) {
			echo do_shortcode( '[email-subscribers-form id="' . $formID . '"]' );
		}
		$search_form_html = ob_get_clean();
		return $search_form_html;
	}
}

ES_GB_Subscription_Form_Block::get_instance();
