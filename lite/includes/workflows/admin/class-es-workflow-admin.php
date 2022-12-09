<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workflow admin
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Helper class for workflow admin functionality
 *
 * @class ES_Workflow_Admin
 *
 * @since 4.4.1
 */
class ES_Workflow_Admin {

	/**
	 * Workflow admin init
	 *
	 * @since 4.4.1
	 */
	public static function init() {

		ES_Workflow_Admin_Ajax::init();
		ES_Workflow_Admin_Edit::init();
		ES_Workflow_Gallery::init();
	}

	/**
	 * Method to load workflow admin views
	 *
	 * @since 4.4.1
	 *
	 * @param string $view View name.
	 * @param array  $imported_variables Passed variables.
	 * @param mixed  $path Path to view file.
	 */
	public static function get_view( $view, $imported_variables = array(), $path = false ) {

		if ( $imported_variables && is_array( $imported_variables ) ) {
			extract( $imported_variables ); // phpcs:ignore
		}

		if ( ! $path ) {
			$path = ES_PLUGIN_DIR . 'lite/includes/workflows/admin/views/';
		}

		include $path . $view . '.php';
	}
}
