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
	 *
	 * @since 4.6.9 Added modal_variable_info ajax event.
	 */
	public static function init() {
		$ajax_events = array(
			'fill_trigger_fields',
			'fill_action_fields',
			'toggle_workflow_status',
			'modal_variable_info',
			'create_workflow_from_gallery_item',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_ig_es_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}


	/**
	 * Get trigger fields
	 */
	public static function fill_trigger_fields() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

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

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

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

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

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

	/**
	 * Show vairable info modal
	 *
	 * @since 4.6.9
	 */
	public static function modal_variable_info() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$variable = IG_ES_Variables::get_variable( ES_Clean::string( ig_es_get_request_data( 'variable' ) ) );

		if ( $variable ) {
			ES_Workflow_Admin::get_view(
				'modal-variable-info',
				array(
					'variable' => $variable,
				)
			);
			die;
		}

		wp_die( esc_html__( 'Variable not found.', 'email-subscribers' ) );
	}

	/**
	 * Method to toggle workflow status
	 */
	public static function create_workflow_from_gallery_item() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$item_name = ig_es_get_request_data( 'item_name' );

		if ( ! $item_name ) {
			die;
		}

		$workflow_id = 0;

		$workflow_gallery = ES_Workflow_Gallery::get_workflow_gallery_items();
		if ( ! empty( $workflow_gallery[ $item_name ] ) ) {
			$item_data = $workflow_gallery[ $item_name ];

			$workflow_title  = isset( $item_data['title'] ) ? ig_es_clean( $item_data['title'] ) : '';
			$workflow_name   = ! empty( $workflow_title ) ? sanitize_title( ES_Clean::string( $workflow_title ) ) : '';
			$trigger_name    = isset( $item_data['trigger_name'] ) ? ig_es_clean( $item_data['trigger_name'] ) : '';
			$trigger_options = isset( $item_data['trigger_options'] ) ? ig_es_clean( $item_data['trigger_options'] ) : array();
			$rules           = isset( $item_data['rules'] ) ? ig_es_clean( $item_data['rules'] ) : array();
			$actions         = isset( $item_data['actions'] ) ? $item_data['actions'] : array(); // We can't sanitize actions data since some actions like Send email allows html in its field.
			$status          = isset( $item_data['status'] ) ? ig_es_clean( $item_data['status'] ) : 0;
			$type            = isset( $item_data['type'] ) ? ig_es_clean( $item_data['type'] ) : 0;
			$priority        = isset( $item_data['priority'] ) ? ig_es_clean( $item_data['priority'] ) : 0;
			$meta            = isset( $item_data['meta'] ) ? ig_es_clean( $item_data['meta'] ) : 0;

			$workflow_data = array(
				'name'            => $workflow_name,
				'title'           => $workflow_title,
				'trigger_name'    => $trigger_name,
				'trigger_options' => maybe_serialize( $trigger_options ),
				'rules'           => maybe_serialize( $rules ),
				'actions'         => maybe_serialize( $actions ),
				'meta'            => maybe_serialize( $meta ),
				'status'          => 0,
				'type'            => 0,
				'priority'        => 0,
			);

			$workflow_id = ES()->workflows_db->insert_workflow( $workflow_data );
		}

		if ( $workflow_id ) {
			$redirect_url = ES_Workflow_Admin_Edit::get_admin_edit_url( $workflow_id );
			wp_send_json_success(
				array(
					'workflow_id'  => $workflow_id,
					'redirect_url' => $redirect_url,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'error_message' => __( 'Workflow could not be created. Please try again.', 'email-subscribers' ),
				)
			);
		}
	}
}
