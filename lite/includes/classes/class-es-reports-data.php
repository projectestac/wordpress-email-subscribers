<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Reports_Data' ) ) {
	/**
	 * Get Reports Data
	 * Class ES_Reports_Data
	 *
	 * @since 4.3.2
	 */
	class ES_Reports_Data {

		/**
		 * Get total Contacts
		 *
		 * @since 4.3.2
		 */
		public static function get_total_contacts() {
			return ES()->contacts_db->get_total_contacts();
		}

		/**
		 * Get total subscribed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 */
		public static function get_total_subscribed_contacts( $days = 0 ) {
			return ES_DB_Lists_Contacts::get_total_subscribed_contacts( $days );
		}

		/**
		 * Get total unsubscribed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 */
		public static function get_total_unsubscribed_contacts( $days = 0 ) {
			return ES_DB_Lists_Contacts::get_total_unsubscribed_contacts( $days );
		}

		/**
		 * Get total contacts have opened emails in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 */
		public static function get_total_contacts_opened_emails( $days = 60 ) {
			return ES_DB_Sending_Queue::get_total_contacts_opened_emails( $days );
		}

		/**
		 * Get total contacts have clicked on links in emails in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 */
		public static function get_total_contacts_clicks_links( $days = 60 ) {
			return ES()->actions_db->get_total_contacts_clicks_links( $days );
		}


	}
}
