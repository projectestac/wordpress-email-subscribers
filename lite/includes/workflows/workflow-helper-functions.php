<?php
/**
 * Workflow helper functions
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Function to validate workflow data item
 *
 * @param string                $type Data item type.
 * @param ES_Workflow_Data_Type $item Data item.
 *
 * @return mixed item of false
 *
 * @since 4.4.1
 */
function ig_es_validate_data_item( $type, $item ) {

	if ( ! $type || ! $item ) {
		return false;
	}

	$valid = false;

	// Validate with the data type classes.
	$data_type = ES_Workflow_Data_Types::get( $type );
	if ( $data_type ) {
		$valid = $data_type->validate( $item );
	}

	/**
	 * Filter to override data item validation
	 *
	 * @since 4.4.1
	 */
	$valid = apply_filters( 'ig_es_validate_data_item', $valid, $type, $item );

	if ( $valid ) {
		return $item;
	}

	return false;
}

/**
 * Function to convert bool values to int values.
 *
 * @param mixed $val Mixed values.
 * @return int
 *
 * @since 4.4.1
 */
function ig_es_bool_int( $val ) {
	return intval( (bool) $val );
}

/**
 * Generate tracking key
 * 
 * @param $length int
 * @param bool $case_sensitive When false only lowercase letters will be included
 * @param bool $more_numbers
 * @return string
 */
function ig_es_generate_key( $length = 25, $case_sensitive = true, $more_numbers = false ) {

	$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

	if ( $case_sensitive ) {
		$chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}

	if ( $more_numbers ) {
		$chars .= '01234567890123456789';
	}

	$password = '';
	$chars_length = strlen( $chars );

	for ( $i = 0; $i < $length; $i++ ) {
		$password .= substr($chars, wp_rand( 0, $chars_length - 1), 1);
	}

	return $password;
}

