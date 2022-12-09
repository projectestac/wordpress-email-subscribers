<?php
/**
 * Workflow data type subscriber
 *
 * @since       4.7.2
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle subscriber data item
 *
 * @class ES_Data_Type_Subscriber
 *
 * @since 4.7.2
 */
class ES_Data_Type_Subscriber extends ES_Workflow_Data_Type {

	/**
	 * Validate given data item
	 *
	 * @since 4.7.2
	 *
	 * @param WP_User $item Data item object.
	 *
	 * @return bool
	 */
	public function validate( $item ) {

		if ( empty( $item ) || ! is_email( $item['email'] ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Returns id of given data item object. Only validated $items should be passed to this method
	 *
	 * @since 4.7.2
	 *
	 * @param WP_User $item Data item object.
	 *
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item;
	}


	/**
	 * Return data item object from given id.
	 *
	 * @since 4.7.2
	 *
	 * @param string $compressed_item Data item object ID.
	 * @param array  $compressed_data_layer Data layer.
	 *
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {

		if ( ! $compressed_item ) {
			return false;
		}

		return $compressed_item;
	}

	/**
	 * Abstract required data from data item object
	 *
	 * @since 4.7.2
	 *
	 * @param array $item Data item object.
	 * @return array
	 */
	public function get_data( $item ) {

		$data = array();

		if ( ! empty( $item['email'] ) ) {
			$data 		    = $item;
			$data['source'] = 'es';
		}

		return $data;
	}
}
