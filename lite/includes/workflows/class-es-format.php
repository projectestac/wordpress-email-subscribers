<?php

/**
 * Class to format data according to workflows
 * 
 * @class ES_Format
 * @since 4.4.0
 */
class ES_Format {

	const MYSQL = 'Y-m-d H:i:s';


	/**
	 * Get date/time in human readable format from passed date time
	 * 
	 * @param int|string|DateTime| $date
	 * @param bool|int                         $max_diff Set to 0 to disable diff format
	 * @param bool                             $convert_from_gmt If its gmt convert it to local time
	 * @param bool                             $shorten_month
	 *
	 * @since 4.4.1 $shorten_month param added
	 *
	 * @return string|false
	 */
	public static function datetime( $date, $max_diff = false, $convert_from_gmt = true, $shorten_month = false ) {
		$timestamp = self::mixed_date_to_timestamp( $date );
		if ( ! $timestamp ) {
			return false; // convert to timestamp ensures WC_DateTime objects are in UTC
		}

		if ( $convert_from_gmt ) {
			$timestamp = strtotime( get_date_from_gmt( gmdate( self::MYSQL, $timestamp ), self::MYSQL ) );
		}

		$now = current_time( 'timestamp' );

		if ( false === $max_diff ) {
			$max_diff = DAY_IN_SECONDS; // set default
		}

		$diff = $timestamp - $now;

		if ( abs( $diff ) >= $max_diff ) {
			$time_format     = get_option( 'time_format' );
			$date_to_display = date_i18n( self::get_date_format( $shorten_month ) . ' ' . $time_format, $timestamp );
			return $date_to_display;
		}

		return self::human_time_diff( $timestamp );
	}


	/**
	 * Get human readable date from passed date
	 * 
	 * @param int|string|DateTime| $date
	 * @param bool|int                         $max_diff
	 * @param bool                             $convert_from_gmt If its gmt convert it to local time
	 * @param bool                             $shorten_month
	 *
	 * @since 4.1.1 $shorten_month param added
	 *
	 * @return string|false
	 */
	public static function date( $date, $max_diff = false, $convert_from_gmt = true, $shorten_month = false ) {
		$timestamp = self::mixed_date_to_timestamp( $date );
		if ( ! $timestamp ) {
			return false; // convert to timestamp ensures WC_DateTime objects are in UTC
		}

		if ( $convert_from_gmt ) {
			$timestamp = strtotime( get_date_from_gmt( gmdate( self::MYSQL, $timestamp ), self::MYSQL ) );
		}

		$now = current_time( 'timestamp' );

		if ( false === $max_diff ) {
			$max_diff = WEEK_IN_SECONDS; // set default
		}

		$diff = $timestamp - $now;

		if ( abs( $diff ) >= $max_diff ) {
			$date_to_display = date_i18n( self::get_date_format( $shorten_month ), $timestamp );
			return $date_to_display;
		}

		return self::human_time_diff( $timestamp );
	}


	/**
	 * Get date format from site settings
	 * 
	 * @since 4.4.1
	 * @param bool $shorten_month
	 * @return string
	 */
	public static function get_date_format( $shorten_month = false ) {
		$format = get_option( 'date_format' );

		if ( $shorten_month ) {
			$format = str_replace( 'F', 'M', $format );
		}

		return $format;
	}


	/**
	 * Get human readable date from passed timestamp
	 * 
	 * @param integer $timestamp
	 * @return string
	 */
	private static function human_time_diff( $timestamp ) {
		$now = current_time( 'timestamp' );

		$diff = $timestamp - $now;

		if ( $diff < 55 && $diff > -55 ) {
			/* translators: %d: time difference in second %d: time difference in seconds */
			$diff_string = sprintf( _n( '%d second', '%d seconds', abs( $diff ), 'email-subscribers' ), abs( $diff ) );
		} else {
			$diff_string = human_time_diff( $now, $timestamp );
		}

		if ( $diff > 0 ) {
			/* translators: %s: time difference */
			return sprintf( __( '%s from now', 'email-subscribers' ), $diff_string );
		} else {
			/* translators: %s: time difference */
			return sprintf( __( '%s ago', 'email-subscribers' ), $diff_string );
		}
	}


	/**
	 * Convert date object/string to timestamp
	 * 
	 * @param int|string|DateTime $date
	 * @return int|bool
	 */
	public static function mixed_date_to_timestamp( $date ) {
		if ( ! $date ) {
			return false;
		}

		$timestamp = 0;

		if ( is_numeric( $date ) ) {
			$timestamp = $date;
		} else {
			if ( is_a( $date, 'DateTime' ) ) { // maintain support for \DateTime
				$timestamp = $date->getTimestamp();
			} elseif ( is_string( $date ) ) {
				$timestamp = strtotime( $date );
			}
		}

		if ( $timestamp < 0 ) {
			return false;
		}

		return $timestamp;
	}


	/**
	 * Get weekday number from day.
	 * 
	 * @param integer $day - 1 (for Monday) through 7 (for Sunday)
	 * @return string|false
	 */
	public static function weekday( $day ) {

		global $wp_locale;

		$days = array(
			1 => $wp_locale->get_weekday( 1 ),
			2 => $wp_locale->get_weekday( 2 ),
			3 => $wp_locale->get_weekday( 3 ),
			4 => $wp_locale->get_weekday( 4 ),
			5 => $wp_locale->get_weekday( 5 ),
			6 => $wp_locale->get_weekday( 6 ),
			7 => $wp_locale->get_weekday( 0 ),
		);

		if ( ! isset( $days[ $day ] ) ) {
			return false;
		}

		return $days[ $day ];
	}

	/**
	 * Format a price decimal value.
	 *
	 * Does NOT localize the decimal.
	 *
	 * @param float|string $number
	 * @param int          $places
	 * @param bool         $trim_zeros
	 *
	 * @return string
	 */
	public static function decimal( $number, $places = null, $trim_zeros = false ) {
		if ( null === $places ) {
			$places = wc_get_price_decimals();
		}

		return wc_format_decimal( $number, $places, $trim_zeros );
	}
}
