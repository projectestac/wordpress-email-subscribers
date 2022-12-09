<?php
/**
 * Workflow data type campaign
 *
 * @since       5.0.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle campaign data item
 *
 * @class ES_Data_Type_Campaign
 *
 * @since 5.0.1
 */
class ES_Data_Type_Campaign extends ES_Workflow_Data_Type {

	/**
	 * Validate given data item
	 *
	 * @since 5.0.1
	 *
	 * @param WP_User $item Data item object.
	 *
	 * @return bool
	 */
	public function validate( $item ) {

		if ( empty( $item['notification_guid'] ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Returns id of given data item object. Only validated $items should be passed to this method
	 *
	 * @since 5.0.1
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
	 * @since 5.0.1
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
	 * @since 5.0.1
	 *
	 * @param array $item Data item object.
	 * @return array
	 */
	public function get_data( $item ) {

		$data = array();

		if ( ! empty( $item ) ) {
			$data = $item;
		}

		return $data;
	}
}
