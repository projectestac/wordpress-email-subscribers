<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IG_ES_WC_Cookies
 *
 * @since 4.6.5
 */
class IG_ES_WC_Cookies {


	/**
	 * Sets a cookie and also updates the $_COOKIE array.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int    $expire timestamp
	 *
	 * @return bool
	 */
	public static function set( $name, $value, $expire = 0 ) {
		wc_setcookie( $name, $value, $expire );
		$_COOKIE[ $name ] = $value;
		return true;
	}


	/**
	 * Gets a cookie
	 *
	 * @param $name
	 * @return mixed
	 */
	public static function get( $name ) {
		return isset( $_COOKIE[ $name ] ) ? sanitize_title( sanitize_text_field( $_COOKIE[ $name ] ) ) : false;
	}


	/**
	 * Clear a cookie and also updates the $_COOKIE array.
	 *
	 * @param $name
	 */
	public static function clear( $name ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			wc_setcookie( $name, '', time() - HOUR_IN_SECONDS );
			unset( $_COOKIE[ $name ] );
		}
	}

}
