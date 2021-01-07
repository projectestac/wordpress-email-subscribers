<?php
/**
 * Helper class for Workflow date time options
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for Workflow date time options
 *
 * @class ES_Workflow_DateTime
 * 
 * @since 4.4.1
 */
class ES_Workflow_DateTime extends DateTime {

	/**
	 * Same as parent but forces UTC timezone if no timezone is supplied instead of using the PHP default.
	 *
	 * @param string               $time time-based string.
	 * @param \DateTimeZone|string $timezone DateTimeZone object
	 *
	 * @throws \Exception Emits Exception in case of an error.
	 */
	public function __construct( $time = 'now', $timezone = null ) {
		if ( ! $timezone ) {
			$timezone = new DateTimeZone( 'UTC' );
		}

		parent::__construct( $time, $timezone instanceof DateTimeZone ? $timezone : null );
	}


	/**
	 * Convert ES_Workflow_DateTime from site timezone to UTC.
	 *
	 * Note this doesn't actually set the timezone property, it directly modifies the date.
	 *
	 * @return $this
	 */
	public function convert_to_utc_time() {
		ES_Workflow_Time_Helper::convert_to_gmt( $this );
		return $this;
	}


	/**
	 * Convert ES_Workflow_DateTime from UTC to the site timezone.
	 *
	 * Note this doesn't actually set the timezone property, it directly modifies the date.
	 *
	 * @return $this
	 */
	public function convert_to_site_time() {
		ES_Workflow_Time_Helper::convert_from_gmt( $this );
		return $this;
	}


	/**
	 * Convert to mysql date time string
	 * 
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function to_mysql_string() {
		return $this->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Set time to the day end in the current timezone.
	 *
	 * @return $this
	 *
	 * @since 4.4.0
	 */
	public function set_time_to_day_start() {
		$this->setTime( 0, 0, 0 );
		return $this;
	}

	/**
	 * Set time to the day start in the current timezone.
	 *
	 * @return $this
	 *
	 * @since 4.4.0
	 */
	public function set_time_to_day_end() {
		$this->setTime( 23, 59, 59 );
		return $this;
	}

}
