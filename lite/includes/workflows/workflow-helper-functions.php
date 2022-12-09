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

/**
 *  Does str_replace but limited to one replacement
 *
 * @param string$subject
 * @param string$find
 * @param string $replace
 * @return string
 */
function ig_es_str_replace_first_match( $subject, $find, $replace = '' ) {
	$pos = strpos($subject, $find);
	if ( false !== $pos ) {
		return substr_replace($subject, $replace, $pos, strlen($find));
	}
	return $subject;
}

/**
 * Get country name from country code
 * 
 * @param string $country_code
 * @return string|bool
 * 
 * @since 4.6.9
 */
function ig_es_get_country_name( $country_code ) {
	$countries = WC()->countries->get_countries();
	return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : false;
}

/**
  * Get state name from country and state code
 * 
 * @param string $country_code
 * @param string $state_code
 * @return string|bool
 * 
 * @since 4.6.9
 */
function ig_es_get_state_name( $country_code, $state_code ) {
	$states = WC()->countries->get_states( $country_code );
	return isset( $states[ $state_code ] ) ? $states[ $state_code ] : false;
}

/**
 * Get product image
 * 
 * @param WC_Product $product
 * @param string $size
 * @return array|false|string
 * 
* @since 4.6.9
 */
function ig_es_get_wc_product_image_url( $product, $size = 'shop_catalog' ) {

	$image_id = $product->get_image_id();
	if ( $image_id ) {
		$image_url = wp_get_attachment_image_url( $image_id, $size );
		return apply_filters( 'ig_es_email_product_image_src', $image_url, $size, $product );
	} else {
		$image_url = wc_placeholder_img_src( $size );
		return apply_filters( 'ig_es_email_product_placeholder_image_src', $image_url, $size, $product );
	}
}

function ig_es_create_list_from_product( $product ) {

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
