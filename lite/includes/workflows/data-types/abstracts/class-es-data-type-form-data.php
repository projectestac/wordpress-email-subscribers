<?php
/**
 * Workflow data type form data
 *
 * @since       4.4.6
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class to handle form data
 *
 * @class ES_Data_Type_Form_Data
 *
 * @since 4.4.6
 */
abstract class ES_Data_Type_Form_Data extends ES_Workflow_Data_Type {

	/**
	 * Validate given data item
	 *
	 * @since 4.4.6
	 *
	 * @param array $item Data item to validate.
	 *
	 * @return bool
	 */
	public function validate( $item ) {
		// Check if we have an array with email field not being empty.
		if ( ! is_array( $item ) || empty( $item['email'] ) ) {
			return false;
		}
		return true;
	}


	/**
	 * Returns passed form data. Only validated $items should be passed to this method
	 *
	 * @param array $item Passed data item.
	 *
	 * @return mixed
	 */
	public function compress( $item ) {
		// Return the same $item as submitted contact form aren't saved in DB for later user.
		return $item;
	}

	/**
	 * Return data item object from given data.
	 *
	 * @param array $compressed_item Data item.
	 * @param array $compressed_data_layer Data layer.
	 *
	 * @return Array|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return $compressed_item;
	}
}
