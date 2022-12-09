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

if ( ! class_exists( 'ES_Action_Update_Contact' ) ) {
	/**
	 * Class to add contact to the selected list
	 *
	 * @class ES_Action_Update_Contact
	 *
	 * @since 4.4.1
	 */
	class ES_Action_Update_Contact extends ES_Workflow_Action {

		/**
		 * Load action admin details.
		 *
		 * @since 4.4.1
		 */
		public function load_admin_details() {
			$this->title = __( 'Update Contact', 'email-subscribers' );
			$this->group = __( 'Contact', 'email-subscribers' );
		}

		/**
		 * Called when an action should be run
		 *
		 * @since 4.4.1
		 */
		public function run() {

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

					$user_id = ! empty( $data['wp_user_id'] ) ? $data['wp_user_id'] : 0;

					if ( ! empty( $user_id ) ) {

						$user = get_user_by( 'ID', $user_id );

						if ( $user instanceof WP_User ) {
							// Check if user exist with this email.
							$es_contact_id = ES()->contacts_db->get_contact_id_by_wp_user_id( $user_id );
							if ( ! $es_contact_id ) {
								$es_contact_id = ES()->contacts_db->get_contact_id_by_email( $user->user_email );
							}

							if ( ! empty( $es_contact_id ) ) {

								$first_name = get_user_meta( $user_id, 'first_name', true );
								$last_name  = get_user_meta( $user_id, 'last_name', true );

								if ( empty( $first_name ) && empty( $last_name ) ) {
									$first_name = $user->display_name;
								}

								$contact = array(
									'email'      => $user->user_email,
									'first_name' => $first_name,
									'last_name'  => $last_name,
									'wp_user_id' => $user->ID,
								);

								ES()->contacts_db->update_contact( $es_contact_id, $contact );
							}
						}
					} else {

						$email         = ! empty( $data['email'] ) ? $data['email'] : '';
						$es_contact_id = ES()->contacts_db->get_contact_id_by_email( $email );
						if ( ! empty( $es_contact_id ) ) {
							$first_name = ! empty( $data['first_name'] ) ? $data['first_name'] : '';
							$last_name  = ! empty( $data['last_name'] ) ? $data['last_name'] : '';

							// Check if we are getting the name field.
							if ( empty( $first_name ) && empty( $last_name ) && ! empty( $data['name'] ) ) {
								$name       = explode( ' ', $data['name'] );
								$first_name = $name[0];
								if ( isset( $name[1] ) ) {
									$last_name = $name[1];
								}
							}
							$contact = array(
								'email'      => $email,
								'first_name' => $first_name,
								'last_name'  => $last_name,
							);

							ES()->contacts_db->update_contact( $es_contact_id, $contact );
						}
					}
				}
			}

		}

	}
}
