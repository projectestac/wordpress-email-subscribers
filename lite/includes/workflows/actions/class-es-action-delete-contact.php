<?php
/**
 * Action to add contact to the selected list
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Action_Delete_Contact' ) ) {
/**
 * Class to add contact to the selected list
 *
 * @class ES_Action_Delete_Contact
 *
 * @since 4.4.1
 */
	class ES_Action_Delete_Contact extends ES_Workflow_Action {

		/**
		 * Load action admin details.
		 *
		 * @since 4.4.1
		 */
		public function load_admin_details() {
			$this->title = __( 'Delete Contact', 'email-subscribers' );
			$this->group = __( 'Contact', 'email-subscribers' );
		}

		/**
		 * Called when an action should be run
		 *
		 * @since 4.4.1
		 */
		public function run() {

			global $wpdb;

			$raw_data = $this->workflow->data_layer()->get_raw_data();
			if ( ! empty( $raw_data ) ) {
				foreach ( $raw_data as $data_type_id => $data_item ) {
					$data_type = ES_Workflow_Data_Types::get( $data_type_id );
					if ( ! $data_type || ! $data_type->validate( $data_item ) ) {
						continue;
					}

					$data = array();
					if ( is_callable( array( $data_type, 'get_data' ) ) ) {
						$data = $data_type->get_data( $data_item );
					}

					$email = ! empty( $data['email'] ) ? $data['email'] : '';

					if ( ! empty( $email ) ) {
						$where      = $wpdb->prepare( 'email = %s', $email );
						$contact_id = ES()->contacts_db->get_column_by_condition( 'id', $where );

						if ( $contact_id ) {
							ES()->contacts_db->delete_contacts_by_ids( $contact_id );
						}
					}
				}
			}
		}

	}
}
