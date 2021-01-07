<?php
/**
 * Helper class for getting/setting workflow time options
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Class for workflow timing options.
 *
 * @class ES_Workflow_Time_Helper
 *
 * @since 4.4.1
 */
class ES_Workflow_Time_Helper {

	/**
	 * Method to calculate total seconds from start of the day till time given.
	 *
	 * @param string|DateTime $time - string must be in format 00:00.
	 *
	 * @return int
	 */
	public static function calculate_seconds_from_day_start( $time ) {

		if ( is_a( $time, 'DateTime' ) ) {
			$time = $time->format( 'G:i' );
		}

		$parts = explode( ':', $time );

		if ( count( $parts ) !== 2 ) {
			return 0;
		}

		return ( absint( $parts[0] ) * HOUR_IN_SECONDS + absint( $parts[1] ) * MINUTE_IN_SECONDS );
	}


	/**
	 * Convert local time to GMT time.
	 *
	 * @param \DateTime|ES_Workflow_DateTime $datetime DateTime object.
	 */
	public static function convert_to_gmt( $datetime ) {
		$datetime->modify( '-' . self::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );
	}


	/**
	 * Convert GMT time to local time
	 *
	 * @param \DateTime|DateTime $datetime DateTime object.
	 */
	public static function convert_from_gmt( $datetime ) {
		$datetime->modify( '+' . self::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );
	}


	/**
	 * Get timezone offset
	 *
	 * @return float|int
	 */
	public static function get_timezone_offset() {
		$timezone = get_option( 'timezone_string' );
		if ( $timezone ) {
			$timezone_object = new DateTimeZone( $timezone );
			return $timezone_object->getOffset( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) ) / HOUR_IN_SECONDS;
		} else {
			return floatval( get_option( 'gmt_offset', 0 ) );
		}
	}
}
