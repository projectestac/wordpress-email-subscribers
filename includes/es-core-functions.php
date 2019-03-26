<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_ig_es_db_version() {

	$option = get_option( 'ig_es_db_version', null );

	if ( ! is_null( $option ) ) {
		return $option;
	}

	$option = get_option( 'current_sa_email_subscribers_db_version', null );

	return $option;

}

function ig_es_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

if ( ! function_exists( 'get_ig_logger' ) ) {

	function get_ig_logger() {

		static $logger = null;

		$class = 'IG_Logger';

		if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
			return $logger;
		}

		$implements = class_implements( $class );

		if ( is_array( $implements ) && in_array( 'IG_Logger_Interface', $implements, true ) ) {
			$logger = is_object( $class ) ? $class : new $class();
		} else {
			$logger = is_a( $logger, 'IG_Logger' ) ? $logger : new IG_Logger();
		}

		return $logger;
	}
}

if ( ! function_exists( 'ig_get_current_date_time' ) ) {
	function ig_get_current_date_time() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'ig_es_format_date_time' ) ) {
	function ig_es_format_date_time( $date ) {
		$local_timestamp = ( $date !== '0000-00-00 00:00:00' ) ? get_date_from_gmt( $date) : $date;
		return $local_timestamp;
	}
}

function ig_es_convert_space_to_underscore( $string ) {
	return str_replace( ' ', '_', $string );
}