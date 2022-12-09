<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_ig_es_db_version' ) ) {
	/**
	 * Get current db version
	 *
	 * @since 4.0.0
	 */
	function get_ig_es_db_version() {

		$option = get_option( 'ig_es_db_version', null );

		if ( ! is_null( $option ) ) {
			return $option;
		}

		$option = get_option( 'current_sa_email_subscribers_db_version', null );

		if ( ! is_null( $option ) ) {
			return $option;
		}

		// Prior to ES 3.2, 'email-subscribers' option was being used to decide db version.
		$option = get_option( 'email-subscribers', null );

		return $option;

	}
}

if ( ! function_exists( 'ig_es_maybe_define_constant' ) ) {
	/**
	 * Define constant
	 *
	 * @param $name
	 * @param $value
	 *
	 * @since 4.0.0
	 */
	function ig_es_maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}

if ( ! function_exists( 'get_ig_logger' ) ) {
	/**
	 * Get IG Logger
	 *
	 * @return IG_Logger|string|null
	 */
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
	/**
	 * Get current date time
	 *
	 * @return false|string
	 */
	function ig_get_current_date_time() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'ig_es_get_current_gmt_timestamp' ) ) {
	/**
	 * Get current date time
	 *
	 * @return false|string
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_current_gmt_timestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s' ) );
	}
}

if ( ! function_exists( 'ig_es_get_current_date' ) ) {
	/**
	 * Get current date
	 *
	 * @return false|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_current_date() {
		return gmdate( 'Y-m-d' );
	}
}

if ( ! function_exists( 'ig_es_get_current_hour' ) ) {
	/**
	 * Get current hour
	 *
	 * @return false|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_current_hour() {
		return gmdate( 'H' );
	}
}

if ( ! function_exists( 'ig_es_format_date_time' ) ) {
	/**
	 * Format date time
	 *
	 * @param $date
	 *
	 * @return string
	 */
	function ig_es_format_date_time( $date ) {

		$local_timestamp = ( '0000-00-00 00:00:00' !== $date && ! empty( $date ) ) ? ES_Common::convert_date_to_wp_date( get_date_from_gmt( $date ) ) : '<i class="dashicons dashicons-es dashicons-minus"></i>';

		return $local_timestamp;
	}
}

if ( ! function_exists( 'ig_es_convert_space_to_underscore' ) ) {
	/**
	 * Convert Space to underscore
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	function ig_es_convert_space_to_underscore( $string ) {
		return str_replace( ' ', '_', $string );
	}
}

if ( ! function_exists( 'ig_es_clean' ) ) {
	/**
	 * Clean String or array using sanitize_text_field
	 *
	 * @param $variable Data to sanitize
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'ig_es_clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
}

if ( ! function_exists( 'ig_es_check_invalid_utf8' ) ) {
	/**
	 * Function ig_check_invalid_utf8 with recursive array support.
	 *
	 * @param string|array $var Data to sanitize.
	 *
	 * @return string|array
	 *
	 * @since 4.1.15
	 */
	function ig_es_check_invalid_utf8( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'ig_es_check_invalid_utf8', $var );
		} else {
			return wp_check_invalid_utf8( $var );
		}
	}
}


if ( ! function_exists( 'ig_es_get_data' ) ) {
	/**
	 * Get data from array
	 *
	 * @param array  $array
	 * @param string $var
	 * @param string $default
	 * @param bool   $clean
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_data( $array = array(), $var = '', $default = '', $clean = false ) {

		if ( ! empty( $var ) ) {
			$value = isset( $array[ $var ] ) ? wp_unslash( $array[ $var ] ) : $default;
		} else {
			$value = wp_unslash( $array );
		}

		if ( $clean ) {
			$value = ig_es_clean( $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'ig_es_get_request_data' ) ) {
	/**
	 * Get POST | GET data from $_REQUEST
	 *
	 * @param $var
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_request_data( $var = '', $default = '', $clean = true ) {
		return ig_es_get_data( $_REQUEST, $var, $default, $clean );
	}
}

if ( ! function_exists( 'ig_es_get_post_data' ) ) {
	/**
	 * Get POST data from $_POST
	 *
	 * @param $var
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */

	function ig_es_get_post_data( $var = '', $default = '', $clean = true ) {

		$nonce = ! empty( $_POST['es-nonce'] ) ? sanitize_text_field( $_POST['es-nonce'] ) : '';

		if ( wp_verify_nonce( $nonce, 'es-nonce' ) ) {
			// TODO: Verify Nonce
			$nonce_verified = true;
		}

		return ig_es_get_data( $_POST, $var, $default, $clean );
	}
}

if ( ! function_exists( 'ig_es_get_ip' ) ) {
	/**
	 * Get Contact IP
	 *
	 * @return mixed|string|void
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_ip() {

		// Get real visitor IP behind CloudFlare network
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_REAL_IP'] );
		} elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_FORWARDED'] );
		} else {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'UNKNOWN';
		}

		return $ip;
	}
}

if ( ! function_exists( 'ig_es_encode_request_data' ) ) {
	/**
	 * Encode request data
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	function ig_es_encode_request_data( $data ) {
		return rtrim( base64_encode( json_encode( $data ) ), '=' );
	}
}

if ( ! function_exists( 'ig_es_decode_request_data' ) ) {
	/**
	 * Decode request data
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	function ig_es_decode_request_data( $data ) {
		$data = json_decode( base64_decode( $data ), true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		return $data;
	}
}

if ( ! function_exists( 'ig_es_get_gmt_offset' ) ) {
	/**
	 * Get GMT Offset
	 *
	 * @param bool $in_seconds
	 * @param null $timestamp
	 *
	 * @return float|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_gmt_offset( $in_seconds = false, $timestamp = null ) {

		$offset = get_option( 'gmt_offset', 0 );

		// check if timestamp has DST
		if ( ! is_null( $timestamp ) ) {
			$l = localtime( $timestamp, true );
			if ( $l['tm_isdst'] ) {
				$offset ++;
			}
		}

		return $in_seconds ? $offset * 3600 : (int) $offset;
	}
}

if ( ! function_exists( 'ig_es_get_upcoming_daily_datetime' ) ) {
	/**
	 * Get next daily run time
	 *
	 * @param $time
	 *
	 * @return false|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_upcoming_daily_datetime( $time ) {

		$offset = ig_es_get_gmt_offset( true );
		$now    = time() + $offset;

		$year    = (int) gmdate( 'Y', $now );
		$month   = (int) gmdate( 'm', $now );
		$day     = (int) gmdate( 'd', $now );
		$hour    = (int) gmdate( 'H', $now );
		$minutes = (int) gmdate( 'i', $now );
		$seconds = (int) gmdate( 's', $now );

		$timestamp = ( $hour * 3600 ) + ( $minutes * 60 ) + $seconds;

		if ( $time < $timestamp ) {
			$day++;
		}

		$t = mktime( 0, 0, 0, $month, $day, $year ) + $time;

		return $t;
	}
}

if ( ! function_exists( 'ig_es_get_upcoming_weekly_datetime' ) ) {
	/**
	 * Get next weekly time
	 *
	 * @param $days_of_week
	 *
	 * @return false|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_upcoming_weekly_datetime( $frequency_interval, $time ) {

		$week_days_map = array(
			0 => 'sunday',
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday',
		);

		// w is used since it returns day number considering Sunday as 0.
		$current_day = (int) current_time( 'w' );

		// If campaign day is same as the current day then check campaign time also with current time since campaign time may have not already been passed.
		if ( $current_day === (int) $frequency_interval ) {
			// Get curret time.
			$current_hours   = (int) current_time( 'H' );
			$current_minutes = (int) current_time( 'i' );
			$current_seconds = (int) current_time( 's' );

			$current_time = $current_hours * HOUR_IN_SECONDS + $current_minutes * MINUTE_IN_SECONDS + $current_seconds;

			// Check if campaign time has not yet passed then we can use today's date/time else use date/time when campaign day comes next time.
			if ( $current_time < $time ) {
				$week_day_str = 'today';
			} else {
				$week_day_str = 'next ' . $week_days_map[ $frequency_interval ];
			}
		} else {
			$week_day_str = 'next ' . $week_days_map[ $frequency_interval ];
		}

		$timestamp = strtotime( $week_day_str ) + $time;

		return $timestamp;

	}
}

if ( ! function_exists( 'ig_es_get_upcoming_monthly_datetime' ) ) {
	/**
	 * Get next monthly time
	 *
	 * @param $day_of_month
	 *
	 * @return false|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_upcoming_monthly_datetime( $day, $time ) {

		$month = (int) gmdate( 'm', time() );
		$year  = (int) gmdate( 'Y', time() );

		$expected_time = strtotime( gmdate( 'Y-m-d' ) ) + $time;

		if ( $expected_time < time() ) {

			$month++;

			$expected_time = mktime( 0, 0, 0, $month, $day, $year ) + $time;

		}

		return $expected_time;

	}
}

if ( ! function_exists( 'ig_es_get_next_future_schedule_date' ) ) {
	/**
	 * Get future schedule date
	 *
	 * @param $utc_start
	 * @param $interval
	 * @param $time_frame
	 * @param array      $weekdays
	 * @param bool       $in_future
	 *
	 * @return false|float|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_next_future_schedule_date( $data ) {

		$weekdays_array = array( '0', '1', '2', '3', '4', '5', '6' );

		$utc_start   = ! empty( $data['utc_start'] ) ? $data['utc_start'] : 0;
		$interval    = isset( $data['interval'] ) ? $data['interval'] : 1;
		$time_frame  = ! empty( $data['time_frame'] ) ? $data['time_frame'] : 'week';
		$weekdays    = ! empty( $data['weekdays'] ) ? $data['weekdays'] : $weekdays_array;
		$force       = ! empty( $data['force'] ) ? $data['force'] : false;
		$in_future   = ! empty( $data['in_future'] ) ? $data['in_future'] : true;
		$time_of_day = isset( $data['time_of_day'] ) ? $data['time_of_day'] : 32400;

		$offset     = ig_es_get_gmt_offset( true );
		$now        = time() + $offset;
		$utc_start += $offset;
		$times      = 1;

		$next_date        = '';
		$change_next_date = true;
		/**
		 * Start time should be in past
		 */
		if ( ( $in_future && $utc_start - $now < 0 ) || $force ) {
			// get how many $time_frame are in the time between now and the starttime
			switch ( $time_frame ) {
				case 'year':
					$count = gmdate( 'Y', $now ) - gmdate( 'Y', $utc_start );
					break;
				case 'month':
					$count = ( gmdate( 'Y', $now ) - gmdate( 'Y', $utc_start ) ) * 12 + ( gmdate( 'm', $now ) - gmdate( 'm', $utc_start ) );
					break;
				case 'week':
					$count = floor( ( ( $now - $utc_start ) / 86400 ) / 7 );
					break;
				case 'day':
					$count = floor( ( $now - $utc_start ) / 86400 );
					break;
				case 'hour':
					$count = floor( ( $now - $utc_start ) / 3600 );
					break;
				case 'immediately':
					$time_frame       = 'day';
					$next_date        = $now;
					$interval         = 1;
					$count            = 1;
					$change_next_date = false;
					break;
				case 'daily':
					$time_frame       = 'day';
					$next_date        = ig_es_get_upcoming_daily_datetime( $time_of_day );
					$interval         = 1;
					$count            = 1;
					$change_next_date = false;
					break;
				case 'weekly':
					$time_frame       = 'day';
					$next_date        = ig_es_get_upcoming_weekly_datetime( $interval, $time_of_day );
					$interval         = 1;
					$count            = 1;
					$change_next_date = false;
					break;
				case 'monthly':
					$time_frame       = 'day';
					$next_date        = ig_es_get_upcoming_monthly_datetime( $interval, $time_of_day );
					$interval         = 1;
					$count            = 1;
					$change_next_date = false;
					break;
				default:
					$count = $interval;
					break;
			}

			$times = $interval ? ceil( $count / $interval ) : 0;
		}

		// We have already got the next date for weekly & monthly
		if ( empty( $next_date ) ) {
			$next_date = strtotime( gmdate( 'Y-m-d H:i:s', $utc_start ) . ' +' . ( $interval * $times ) . " {$time_frame}" );
		}

		// add a single entity if date is still in the past or just now
		if ( $in_future && ( $next_date - $now < 0 || $next_date == $utc_start ) && $change_next_date ) {
			$next_date = strtotime( gmdate( 'Y-m-d H:i:s', $utc_start ) . ' +' . ( $interval * $times + $interval ) . " {$time_frame}" );
		}

		if ( ! empty( $weekdays ) && count( $weekdays ) < 7 ) {

			$day_of_week = gmdate( 'w', $next_date );

			$i = 0;
			if ( ! $interval ) {
				$interval = 1;
			}

			/**
			 * If we can't send email to the specific weekday, schedule for next possible day.
			 */
			while ( ! in_array( $day_of_week, $weekdays ) ) {

				if ( 'week' == $time_frame ) {
					$next_date = strtotime( '+1 day', $next_date );
				} else {
					$next_date = strtotime( "+{$interval} {$time_frame}", $next_date );
				}

				$day_of_week = gmdate( 'w', $next_date );

				// Force break
				if ( $i > 500 ) {
					break;
				}

				$i ++;
			}
		}

		// return as UTC
		return $next_date - $offset;

	}
}

if ( ! function_exists( 'ig_es_array_insert_after' ) ) {
	/**
	 * Insert $new in $array after $key
	 *
	 * @param $array
	 * @param $key
	 * @param $new
	 *
	 * @return array
	 *
	 * @since 4.3.6
	 */
	function ig_es_array_insert_after( $array, $key, $new ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys );
		$pos   = false === $index ? count( $array ) : $index + 1;

		return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
	}
}

if ( ! function_exists( 'ig_es_get_raw_human_interval' ) ) {
	/**
	 * Gets interval split by days, hours, minutes and seconds
	 *
	 * @param $interval_in_seconds
	 *
	 * @return array
	 *
	 * @since 4.4.9
	 */
	function ig_es_get_raw_human_interval( $interval_in_seconds = 0 ) {

		$interval = array();

		$seconds_in_minute = 60;
		$seconds_in__hour  = 60 * $seconds_in_minute;
		$seconds_in_day    = 24 * $seconds_in__hour;

		// extract days
		$interval['days'] = floor( $interval_in_seconds / $seconds_in_day );

		// extract hours
		$hour_seconds      = $interval_in_seconds % $seconds_in_day;
		$interval['hours'] = floor( $hour_seconds / $seconds_in__hour );

		// extract minutes
		$minute_seconds      = $hour_seconds % $seconds_in__hour;
		$interval['minutes'] = floor( $minute_seconds / $seconds_in_minute );

		// extract the remaining seconds
		$remaining_seconds   = $minute_seconds % $seconds_in_minute;
		$interval['seconds'] = ceil( $remaining_seconds );

		return $interval;

	}
}

if ( ! function_exists( 'ig_es_get_human_interval' ) ) {
	/**
	 * Gets interval in human readable format
	 *
	 * @param $interval_in_seconds
	 *
	 * @return string
	 *
	 * @since 4.4.9
	 */
	function ig_es_get_human_interval( $interval_in_seconds = 0 ) {

		$interval = ig_es_get_raw_human_interval( $interval_in_seconds );

		$human_time = '';

		if ( $interval['days'] > 0 ) {
			$human_time .= $interval['days'] . 'd ';
		}

		if ( $interval['hours'] > 0 ) {
			$human_time .= $interval['hours'] . 'h ';
		}

		if ( $interval['minutes'] > 0 ) {
			$human_time .= $interval['minutes'] . 'm ';
		}

		if ( $interval['seconds'] > 0 ) {
			$human_time .= $interval['seconds'] . 's ';
		}

		if ( empty( $human_time ) ) {
			$human_time = '0s';
		}

		return trim( $human_time );

	}
}

if ( ! function_exists( 'ig_es_allowed_html_tags_in_esc' ) ) {
	/**
	 * Allow Html tags in WP Kses
	 *
	 * @since 4.5.4
	 */
	function ig_es_allowed_html_tags_in_esc() {
		$context_allowed_tags = wp_kses_allowed_html( 'post' );
		$custom_allowed_tags  = array(
			'div'      => array(
				'x-data' => true,
				'x-show' => true,
			),
			'select'   => array(
				'class'    => true,
				'name'     => true,
				'id'       => true,
				'style'    => true,
				'title'    => true,
				'role'     => true,
				'data-*'   => true,
				'tab-*'    => true,
				'multiple' => true,
				'aria-*'   => true,
				'disabled' => true,
				'required' => 'required',
			),
			'optgroup' => array(
				'label' => true,
			),
			'option'   => array(
				'class'    => true,
				'value'    => true,
				'selected' => true,
				'name'     => true,
				'id'       => true,
				'style'    => true,
				'title'    => true,
				'data-*'   => true,
			),
			'input'    => array(
				'class'          => true,
				'name'           => true,
				'type'           => true,
				'value'          => true,
				'id'             => true,
				'checked'        => true,
				'disabled'       => true,
				'selected'       => true,
				'style'          => true,
				'required'       => 'required',
				'min'            => true,
				'max'            => true,
				'maxlength'      => true,
				'size'           => true,
				'placeholder'    => true,
				'autocomplete'   => true,
				'autocapitalize' => true,
				'autocorrect'    => true,
				'tabindex'       => true,
				'role'           => true,
				'aria-*'         => true,
				'data-*'         => true,
			),
			'label'    => array(
				'class' => true,
				'name'  => true,
				'type'  => true,
				'value' => true,
				'id'    => true,
				'for'   => true,
				'style' => true,
			),
			'form'     => array(
				'class'  => true,
				'name'   => true,
				'value'  => true,
				'id'     => true,
				'style'  => true,
				'action' => true,
				'method' => true,
				'data-*' => true,
			),
			'svg'      => array(
				'width'    => true,
				'height'   => true,
				'viewbox'  => true,
				'xmlns'    => true,
				'class'    => true,
				'stroke-*' => true,
				'fill'     => true,
				'stroke'   => true,
			),
			'path'     => array(
				'd'               => true,
				'fill'            => true,
				'class'           => true,
				'fill-*'          => true,
				'clip-*'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'fill-rule'       => true,
			),

			'main'     => array(
				'align'    => true,
				'dir'      => true,
				'lang'     => true,
				'xml:lang' => true,
				'aria-*'   => true,
				'class'    => true,
				'id'       => true,
				'style'    => true,
				'title'    => true,
				'role'     => true,
				'data-*'   => true,
			),
			'textarea' => array(
				'autocomplete' => true,
				'required'	   => 'required',
				'placeholder'  => true,
			),
			'style'    => array(),
			'link'     => array(
				'rel'   => true,
				'id'    => true,
				'href'  => true,
				'media' => true,
			),
			'a'        => array(
				'x-on:click' => true,
			),
			'polygon'  => array(
				'class'  => true,
				'points' => true,
			),
		);

		$allowedtags = array_merge_recursive( $context_allowed_tags, $custom_allowed_tags );

		return $allowedtags;
	}
}

add_filter( 'ig_es_escape_allowed_tags', 'ig_es_allowed_html_tags_in_esc' );

if ( ! function_exists( 'ig_es_get_strip_excluded_tags' ) ) {
	/**
	 * Get HTML tags which should be excleded from stripping when calling strip_tags function.
	 *
	 * @since 5.3.8
	 */
	function ig_es_get_strip_excluded_tags() {
		$excluded_tags = array( '<style>', '<p>', '<em>', '<span>', '<b>', '<strong>', '<i>', '<a>', '<ul>', '<ol>', '<li>', '<br>', '<br/>', '<blockquote>', '<header>', '<footer>' );
		return apply_filters( 'ig_es_strip_excluded_tags', $excluded_tags ) ;
	}
}

if ( ! function_exists( 'ig_es_allowed_css_style' ) ) {
	/**
	 * Allow CSS style in WP Kses
	 *
	 * @since 4.5.4
	 *
	 * @since 4.7.3 Returns empty array to whitelist all CSS properties.
	 */
	function ig_es_allowed_css_style( $default_allowed_attr ) {
		return array(); // Return empty array to whitelist all CSS properties.
	}
}

if ( ! function_exists( 'ig_es_increase_memory_limit' ) ) {

	/**
	 * Return memory limit required for ES heavy operations
	 *
	 * @return string
	 *
	 * @since 4.5.4
	 */
	function ig_es_increase_memory_limit() {

		return '512M';
	}
}

if ( ! function_exists( 'ig_es_remove_utf8_bom' ) ) {

	/**
	 * Remove UTF-8 BOM signature.
	 *
	 * @param string $string String to handle.
	 *
	 * @return string
	 *
	 * @since 4.5.4
	 */
	function ig_es_remove_utf8_bom( $string = '' ) {

		// Check if string contains BOM characters.
		if ( ! empty( $string ) && 'efbbbf' === substr( bin2hex( $string ), 0, 6 ) ) {
			// Remove BOM characters by extracting substring from the original string after the BOM characters.
			$string = substr( $string, 3 );
		}

		return $string;
	}
}

if ( ! function_exists( 'ig_es_covert_to_utf8_encoding' ) ) {

	/**
	 * Function to convert existing string to its UTF-8 equivalent string.
	 *
	 * @param string $data String to handle.
	 * @param bool   $use_mb Flag to determine whether we should use mb_* functions while detecting and converting the encoding.
	 *
	 * @return string $data
	 *
	 * @since 4.5.6
	 */
	function ig_es_covert_to_utf8_encoding( $data = '', $use_mb = false ) {

		// Check if we can use mb_* functions.
		if ( $use_mb ) {
			// Detect character encoding. detecting order is 1.UTF-8 2. ISO-8859-1.
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			// Convert to UTF-8 encoding from detected encoding.
			if ( $encoding ) {
				$data = mb_convert_encoding( $data, 'UTF-8', $encoding );
			} else {
				// If we can't detect the encoding then also make sure we have a valid UTF-8 encoded string.
				$data = mb_convert_encoding( $data, 'UTF-8', 'UTF-8' );
			}
		} else {
			// Remove invalid UTF-8 characters.
			$data = wp_check_invalid_utf8( $data, true );
		}

		return $data;
	}
}

if ( ! function_exists( 'ig_es_insert_widget_in_sidebar' ) ) {
	/**
	 * Insert a widget in a sidebar.
	 *
	 * @param string $widget_id   ID of the widget (search, recent-posts, etc.)
	 * @param array  $widget_data  Widget settings.
	 * @param string $sidebar     ID of the sidebar.
	 * @param string $allow_duplicate Flag to check whether we should add widget even if added to the sidebar already.
	 *
	 * @return boolean
	 *
	 * @since 4.6.0
	 */
	function ig_es_insert_widget_in_sidebar( $widget_id, $widget_data, $sidebar, $allow_duplicate = false ) {
		// Retrieve sidebars, widgets and their instances
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
		$widget_instances = get_option( 'widget_' . $widget_id, array() );

		// Retrieve the key of the next widget instance
		$numeric_keys = array_filter( array_keys( $widget_instances ), 'is_int' );

		if ( ! isset( $sidebars_widgets[ $sidebar ] ) ) {
			$sidebars_widgets[ $sidebar ] = array();
		}

		$widget_already_added = false;
		if ( ! empty( $numeric_keys ) ) {
			foreach ( $numeric_keys as $numeric_key ) {
				$widget_index = array_search( $widget_id . '-' . $numeric_key, $sidebars_widgets[ $sidebar ] );
				// Check if this sidebar has this widget in it.
				if ( false !== $widget_index ) {
					$widget_already_added = true;
					break;
				}
			}
		}

		// Add new widget only if it already not added in the sidebar or duplicate widget is allowed.
		if ( ! $widget_already_added || $allow_duplicate ) {
			$next_key = $numeric_keys ? max( $numeric_keys ) + 1 : 1;

			// Add this widget to the sidebar
			$sidebars_widgets[ $sidebar ][] = $widget_id . '-' . $next_key;

			// Add the new widget instance
			$widget_instances[ $next_key ] = $widget_data;

			// Store updated sidebars, widgets and their instances
			update_option( 'sidebars_widgets', $sidebars_widgets );
			update_option( 'widget_' . $widget_id, $widget_instances );

			return true;
		}

		return false;
	}
}


if ( ! function_exists( 'ig_es_get_values_in_range' ) ) {

	/**
	 * Get the values in range
	 *
	 * @param $values
	 * @param $start
	 * @param $end
	 *
	 * @return array
	 */
	function ig_es_get_values_in_range( $values, $start, $end ) {
		$in_range = array();
		foreach ( $values as $val ) {
			if ( in_array( $val, range( $start, $end ) ) ) {
				array_push( $in_range, $val );
			}
		}
		return $in_range;
	}
}


if ( ! function_exists( 'ig_es_is_arrays_are_equal' ) ) {

	/**
	 * Check the given two arrays are equal
	 *
	 * @param $array1
	 * @param $array2
	 *
	 * @return bool
	 */
	function ig_es_is_arrays_are_equal( $array1, $array2) {
		// If the objects are not arrays or differ in their size, they cannot be equal
		if ( !is_array($array1) || !is_array($array2) || count($array1) !== count($array2)) {
			return false;
		}
		// If the arrays of keys are not strictly equal (after sorting),
		// the original arrays are not strictly equal either
		$array1_keys = array_keys($array1);
		$array2_keys = array_keys($array2);
		array_multisort($array1_keys);
		array_multisort($array2_keys);
		if ($array1_keys !== $array2_keys) {
			return false;
		}
		// Comparing values
		foreach ($array1_keys as $key) {
			$array1_value = $array1[$key];
			$array2_value = $array2[$key];
			// Either the objects are strictly equal or they are arrays
			// which are equal according to our definition. Otherwise they
			// are different.
			if ($array1_value !== $array2_value) {
				return false;
			}
		}
		return true;
	}
}
