<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Subscription_Throttling {

	public static function throttle() {

		global $wpdb;

		if ( ! ( is_user_logged_in() && is_super_admin() ) ) {
			$subscriber_ip = ig_es_get_ip();

			$whitelist_ips = array();
			$whitelist_ips = apply_filters( 'ig_es_whitelist_ips', $whitelist_ips );

			$blacklist_ips = array();
			$blacklist_ips = apply_filters( 'ig_es_blacklist_ips', $blacklist_ips );

			if ( ! ( empty( $subscriber_ip ) || ( is_array( $whitelist_ips ) && count( $whitelist_ips ) > 0 && in_array( $subscriber_ip, $whitelist_ips ) ) ) ) {

				if ( is_array( $blacklist_ips ) && count( $blacklist_ips ) > 0 && in_array( $subscriber_ip, $blacklist_ips ) ) {
					return MINUTE_IN_SECONDS * 10;
				}

				$subscribers = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT count(*) as count from {$wpdb->prefix}ig_contacts_ips WHERE ip = %s AND ( `created_on` >= NOW() - INTERVAL %s SECOND )",
						$subscriber_ip,
						DAY_IN_SECONDS
					)
				);

				if ( $subscribers > 0 ) {
					$timeout = MINUTE_IN_SECONDS * pow( 2, $subscribers - 1 );

					$subscribers = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT count(*) as count from {$wpdb->prefix}ig_contacts_ips WHERE ip = %s AND ( `created_on` >= NOW() - INTERVAL %s SECOND ) LIMIT 1",
							$subscriber_ip,
							$timeout
						)
					);

					if ( $subscribers > 0 ) {
						return $timeout;
					}
				}

				// Add IP Address.
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$wpdb->prefix}ig_contacts_ips (`ip`) VALUES ( %s )",
						$subscriber_ip
					)
				);

				// Delete older entries
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}ig_contacts_ips WHERE (`created_on` < NOW() - INTERVAL %s SECOND )",
						DAY_IN_SECONDS
					)
				);
			}
		}

		return false;
	}

}
