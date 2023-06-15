<?php

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Integration_Base;

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Es_Form_Action extends Integration_Base {
	/**
	 * Get the name for form action
	 *
	 * @return string
	 */
	public function get_name() {
		return 'email_subscribers';
	}

	/**
	 * Get the label of the action
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Icegram Express', 'email-subscribers' );
	}

	/**
	 * Register Email subscribers related settings
	 *
	 * @param \ElementorPro\Modules\Forms\Widgets\Form $form
	 */
	public function register_settings_section( $form ) {
		if (method_exists($form, 'start_controls_section')&& method_exists($form, 'end_controls_section')&& method_exists($form, 'add_control')) {
			$form->start_controls_section(
				'section_email_subscribers',
				array(
					'label'     => __( 'Icegram Express', 'email-subscribers' ),
					'condition' => array(
						'submit_actions' => $this->get_name(),
					),
				)
			);
			$lists = ES()->lists_db->get_list_id_name_map();

			$form->add_control(
				'email_subscribers_lists',
				array(
					'label'       => __( 'List', 'email-subscribers' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'options'     => $lists,
					'render_type' => 'none',
				)
			);

			$this->register_fields_map_control( $form );

			$form->end_controls_section();
		}
	}

	public function on_export( $element ) {
		unset( $element['email_subscribers_lists'] );

		return $element;
	}

	/**
	 * Handle form submission
	 *
	 * @throws Exception
	 */
	public function run( $record, $ajax_handler ) {
		$settings          = $record->get( 'form_settings' );
		$subscriber        = $this->map_fields( $record );
		$ip_address        = ig_es_get_request_data( 'ip_address' );
		$subscription_list = isset( $settings['email_subscribers_lists'] ) && ! empty( $settings['email_subscribers_lists'] ) ? $settings['email_subscribers_lists'] : '';
		if ( is_numeric( $subscription_list ) ) {
			$lists = ES()->lists_db->get_lists_by_id( $subscription_list );
			if ( ! empty( $lists ) ) {
				$list_hash = ! empty( $lists[0]['hash'] ) ? $lists[0]['hash'] : '';
				if ( ! empty( $list_hash ) ) {
					$subscriber['esfpx_lists'] = array( $list_hash );
				}
			}
		}
		$subscriber['esfpx_ip_address']  = $ip_address;
		$subscriber['form_type']         = 'external';
		$subscriber['esfpx_es_hp_email'] = '';
		// This constant is defined to return the response form process_request method
		// Because process_request using wp_send_json function to return response,
		defined( 'IG_ES_RETURN_HANDLE_RESPONSE' ) || define( 'IG_ES_RETURN_HANDLE_RESPONSE', true );
		$es       = new ES_Handle_Subscription();
		$response = $es->process_request( $subscriber );
		if ( 'ERROR' === $response['status'] ) {
			// If there any error just return response
			wp_send_json_error( array( 'message' => $response['message_text'] ) );
		}
		// No need to process success response
	}

	/**
	 * Map Elemntor's form fields with Email subscriber's fields on frontend form submission
	 *
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record $record
	 *
	 * @return array
	 */
	private function map_fields( $record ) {
		$settings = $record->get( 'form_settings' );
		$fields   = $record->get( 'fields' );

		$subscriber = array();

		foreach ( $settings['email_subscribers_fields_map'] as $map_item ) {
			if ( empty( $fields[ $map_item['local_id'] ]['value'] ) ) {
				continue;
			}

			$subscriber[ $map_item['remote_id'] ] = $fields[ $map_item['local_id'] ]['value'];
		}

		return $subscriber;
	}

	/**
	 * Icegram Express fields that need to map with Elementor form fields
	 *
	 * @return array
	 */
	protected function get_fields_map_control_options() {
		$email_subscribers_fields = array(
			array(
				'remote_id'    => 'esfpx_name',
				'remote_label' => __( 'Name', 'email-subscribers' ),
				'remote_type'  => 'text',
			),
			array(
				'remote_id'       => 'esfpx_email',
				'remote_label'    => __( 'Email', 'email-subscribers' ),
				'remote_type'     => 'email',
				'remote_required' => true,
			),
		);

		apply_filters( 'ig_es_compatability_elementor_get_fields_map_control_options', $email_subscribers_fields );

		return array(
			'default'   => $email_subscribers_fields,
			'condition' => array(
				'email_subscribers_lists!' => '',
			),
		);
	}
}
