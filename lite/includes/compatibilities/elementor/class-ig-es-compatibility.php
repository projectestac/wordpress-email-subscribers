<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * Main Elementor Form  compatability Class
 */
final class ES_Compatibility_Elementor {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize the custom block feature.
		add_action( 'elementor_pro/forms/register_action', array( $this, 'register_for_action' ) );
	}

	/**
	 * Register form actions on Elementor_Pro/Forms/Register_Action
	 *
	 * @param $forms_module \ElementorPro\Modules\Forms\Module
	 */
	public function register_for_action( $forms_module ) {
		if (method_exists($forms_module, 'add_form_action')) {
			require_once 'actions/class-es-ig-form-action.php';
			$forms_module->add_form_action( 'email_subscribers', new Es_Form_Action() );
		}
	}

}

new ES_Compatibility_Elementor();
