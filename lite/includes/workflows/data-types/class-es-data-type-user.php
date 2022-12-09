<?php
/**
 * Workflow data type user
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle WP_User data item
 *
 * @class Data_Type_User
 *
 * @since 4.4.1
 */
class ES_Data_Type_User extends ES_Workflow_Data_Type {

	/**
	 * Validate given data item
	 *
	 * @since 4.4.1
	 *
	 * @param WP_User $item Data item object.
	 *
	 * @return bool
	 */
	public function validate( $item ) {

		if ( ! ( $item instanceof WP_User ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Returns id of given data item object. Only validated $items should be passed to this method
	 *
	 * @since 4.4.1
	 *
	 * @param WP_User $item Data item object.
	 *
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->ID;
	}


	/**
	 * Return data item object from given id.
	 *
	 * @since 4.4.1
	 *
	 * @param string $compressed_item Data item object ID.
	 * @param array  $compressed_data_layer Data layer.
	 *
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {

		if ( $compressed_item ) {
			return get_user_by( 'id', absint( $compressed_item ) );
		}

		return false;
	}

	/**
	 * Abstract required data from data item object
	 *
	 * @since 4.4.1
	 *
	 * @param WP_User $item Data item object.
	 * @return array
	 */
	public function get_data( $item ) {

		$data = array();

		if ( $item instanceof WP_User ) {

			$name  = $item->display_name;
			$email = $item->user_email;

			// prepare data.
			$data = array(
				'name' 		 => $name,
				'email'      => $email,
				'source'     => 'wp',
				'status'     => 'verified',
				'hash'       => ES_Common::generate_guid(),
				'created_at' => ig_get_current_date_time(),
				'wp_user_id' => $item->ID,
			);
		}

		return $data;
	}
}
