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

if ( ! class_exists( 'ES_Action_Add_To_List' ) ) {
/**
 * Class to add contact to the selected list
 *
 * @class ES_Action_Add_To_List
 *
 * @since 4.4.1
 */
	class ES_Action_Add_To_List extends ES_Workflow_Action {

		/**
		 * Load action admin details.
		 *
		 * @since 4.4.1
		 */
		public function load_admin_details() {
			$this->title = __( 'Add to list', 'email-subscribers' );
			$this->group = __( 'List', 'email-subscribers' );
		}

		/**
		 * Load action fields
		 *
		 * @since 4.4.1
		 */
		public function load_fields() {

			$lists = ES()->lists_db->get_list_id_name_map();

			$list_field = new ES_Select();
			$list_field->set_name( 'ig-es-list' );
			$list_field->set_title( __( 'Select List', 'email-subscribers' ) );
			$list_field->set_options( $lists );
			$list_field->set_required();
			$this->add_field( $list_field );

		}

		/**
		 * Called when an action should be run
		 *
		 * @since 4.4.1
		 */
		public function run() {

			$list_id = $this->get_option( 'ig-es-list' );

			$user_list_status = $this->get_option( 'ig-es-user-list-status' );

			$user_list_status = empty( $user_list_status ) ? 'subscribed' : $user_list_status;

			if ( ! $list_id ) {
				return;
			}

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
					if ( ! empty( $data['email'] ) ) {
						$this->add_contact( $list_id, $data, $user_list_status );
					}
				}
			}

		}

		/**
		 * Add contact data to given list
		 *
		 * @param int   $list_id List id to add the contact's data.
		 * @param array $data Contact's data.
		 */
		public function add_contact( $list_id = 0, $data = array(), $user_list_status = '' ) {

			// Don't know where to add contact? please find it first.
			if ( empty( $list_id ) ) {
				return;
			}

			// Email not found? Say good bye.
			if ( empty( $data['email'] ) || ! filter_var( $data['email'], FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			// Source not set? Say bye.
			if ( empty( $data['source'] ) ) {
				return;
			}

			$email      = trim( $data['email'] );
			$source     = trim( $data['source'] );
			$status     = ! empty( $data['status'] ) ? trim( $data['status'] ) : 'verified';
			$wp_user_id = ! empty( $data['wp_user_id'] ) ? trim( $data['wp_user_id'] ) : 0;

			// If first name is set, get the first name and last name from $data.
			// Else prepare the first name and last name from $data['name'] field or $data['email'] field.
			if ( ! empty( $data['first_name'] ) ) {
				$first_name = $data['first_name'];
				$last_name  = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
			} else {
				$name = ! empty( $data['name'] ) ? trim( $data['name'] ) : '';

				$first_name = '';
				$last_name  = '';
				if ( ! empty( $name ) ) {
					$name_parts = ES_Common::prepare_first_name_last_name( $name );
					$first_name = $name_parts['first_name'];
					$last_name  = $name_parts['last_name'];
				}
			}

			$guid = ES_Common::generate_guid();

			$contact_data = array(
				'first_name' 			 => $first_name,
				'last_name'  			 => $last_name,
				'email'      			 => $email,
				'source'     			 => $source,
				'status'     			 => $status,
				'user_list_status' => $user_list_status,
				'hash'       			 => $guid,
				'created_at' 			 => ig_get_current_date_time(),
				'wp_user_id' 			 => $wp_user_id,
			);

			do_action( 'ig_es_add_contact', $contact_data, $list_id );
		}





		/**
		 * Create contact list from product
		 *
		 * @param  WC_Product $product Product object.
		 *
		 * @return int $list_id List ID.
		 *
		 * @since 4.4.3
		 */
		public function create_list_from_product( $product ) {

			$list_id = 0;

			if ( ! ( $product instanceof WC_Product ) ) {
				return $list_id;
			}

			$product_name = $product->get_name();
			$product_sku  = $product->get_sku();

			$list_name = $product_name;

			if ( empty( $product_sku ) ) {
				$list_slug = $product_name;
			} else {
				$list_slug = $product_sku;
			}

			$list = ES()->lists_db->get_list_by_slug( $list_slug );
			if ( ! empty( $list ) ) {
				$list_id = $list['id'];
			} else {
				$list_id = ES()->lists_db->add_list( $list_name, $list_slug );
			}

			return $list_id;
		}

	}

}
