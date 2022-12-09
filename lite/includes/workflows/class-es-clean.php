<?php
/**
 * Container class for sanitizer functions.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Sanitizer class
 *
 * @class ES_Clean
 * @since 4.4.1
 */
class ES_Clean {

	/**
	 * Sanitizes a text string.
	 *
	 * @since 4.4.1
	 *
	 * @param string $string input string.
	 *
	 * @return string
	 */
	public static function string( $string ) {
		return sanitize_text_field( $string );
	}


	/**
	 * Sanitizes a email string.
	 *
	 * @since 4.4.1
	 *
	 * @param string $email input email.
	 *
	 * @return string
	 */
	public static function email( $email ) {
		return strtolower( sanitize_email( $email ) );
	}


	/**
	 * Sanitize a multi-line string. Will strip HTML tags.
	 *
	 * @since 4.4.1
	 *
	 * @param string $text input text string.
	 *
	 * @return string
	 */
	public static function textarea( $text ) {
		return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $text ) ) );
	}

	/**
	 * Sanitize array of
	 *
	 * @since 4.4.1
	 *
	 * @param array $var input array.
	 *
	 * @return array
	 */
	public static function ids( $var ) {
		if ( is_array( $var ) ) {
			return array_filter( array_map( 'absint', $var ) );
		} elseif ( is_numeric( $var ) ) {
			return array( absint( $var ) );
		}
		return array();
	}


	/**
	 * Sanitize a numeric string into an integer
	 *
	 * @since 4.4.1
	 *
	 * @param string|int $id ID.
	 *
	 * @return int
	 */
	public static function id( $id ) {
		return absint( $id );
	}


	/**
	 * Sanitize an array of input text
	 *
	 * @since 4.4.1
	 *
	 * @param mixed $var input array/text.
	 *
	 * @return array|string
	 */
	public static function recursive( $var ) {
		if ( is_array( $var ) ) {
			return array_map( array( 'ES_Clean', 'recursive' ), $var );
		} else {
			return is_scalar( $var ) ? self::string( $var ) : $var;
		}
	}

	/**
	 * HTML encodes emoji's in string or array.
	 *
	 * @since 4.4.1
	 *
	 * @param string|array $data input.
	 *
	 * @return string|array
	 */
	public static function encode_emoji( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as &$field ) {
				if ( is_array( $field ) || is_string( $field ) ) {
					$field = self::encode_emoji( $field );
				}
			}
		} elseif ( is_string( $data ) ) {
			$data = wp_encode_emoji( $data );
		}
		return $data;
	}

	/**
	 * Performs a basic sanitize for editor content permitting all HTML.
	 *
	 * @param string $content
	 *
	 * @return string $content
	 *
	 * @since 4.5.3
	 */
	public static function editor_content( $content ) {
		$content = wp_check_invalid_utf8( stripslashes( (string) $content ) );
		return $content;
	}

}
