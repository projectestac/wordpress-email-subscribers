<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles workflow admin ajax functionality
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Class to handle workflow admin ajax functionality
 *
 * @class ES_Workflow_Admin_Ajax
 *
 * @since 4.4.1
 */
class ES_Workflow_Admin_Ajax {

	/**
	 * Hook in methods
	 *
	 * @since 4.4.1
	 */
	public static function init() {
		$ajax_events = array(
			'fill_trigger_fields',
			'fill_action_fields',
			'toggle_workflow_status',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_ig_es_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}


	/**
	 * Get trigger fields
	 */
	public static function fill_trigger_fields() {

		check_ajax_referer( 'ig-es-workflow-nonce', 'security' );

		$trigger_name = ig_es_get_request_data( 'trigger_name' );
		$workflow_id  = ig_es_get_request_data( 'workflow_id' );

		$trigger = ES_Workflow_Triggers::get( $trigger_name );

		if ( ! $trigger ) {
			die;
		}

		$workflow = null;
		if ( ! empty( $workflow_id ) ) {
			$workflow = new ES_Workflow( $workflow_id );
		}

		ob_start();

		ES_Workflow_Admin::get_view(
			'trigger-fields',
			array(
				'trigger'  => $trigger,
				'workflow' => $workflow,
			)
		);

		$fields = ob_get_clean();

		wp_send_json_success(
			array(
				'fields'  => $fields,
				'trigger' => ES_Workflow_Admin_Edit::get_trigger_data( $trigger ),
			)
		);
	}

	/**
	 * Method to get workflow action related fields in ajax request
	 */
	public static function fill_action_fields() {

		check_ajax_referer( 'ig-es-workflow-nonce', 'security' );

		$action_name   = ig_es_get_request_data( 'action_name' );
		$action_number = ig_es_get_request_data( 'action_number' );
		$trigger_name  = ig_es_get_request_data( 'trigger_name' );

		$action  = ES_Workflow_Actions::get( $action_name );
		$trigger = ES_Workflow_Triggers::get( $trigger_name );

		if ( ! empty( $trigger ) ) {
			$action->trigger = $trigger;
		}

		ob_start();

		ES_Workflow_Admin::get_view(
			'action-fields',
			array(
				'workflow_action' => $action,
				'action_number'   => $action_number,
			)
		);

		$fields = ob_get_clean();

		wp_send_json_success(
			array(
				'fields'      => $fields,
				'title'       => ( $action instanceof ES_Workflow_Action ) ? $action->get_title( true ) : '',
				'description' => ( $action instanceof ES_Workflow_Action ) ? $action->get_description_html() : '',
			)
		);
	}

	/**
	 * Method to toggle workflow status
	 */
	public static function toggle_workflow_status() {

		check_ajax_referer( 'ig-es-workflow-nonce', 'security' );

		$workflow  = ES_Workflow_Factory::get( ig_es_get_request_data( 'workflow_id' ) );
		$new_state = ig_es_get_request_data( 'new_state' );

		if ( ! $workflow || ! $new_state ) {
			die;
		}

		$status_updated = $workflow->update_status( $new_state );

		if ( $status_updated ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

}
