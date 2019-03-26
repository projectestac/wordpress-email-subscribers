<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Subscription_Throttaling {

	static function throttle() {

		global $wpdb;

		if ( ! ( is_user_logged_in() && is_super_admin() ) ) {
			$subscriber_ip = self::getUserIP();
			if ( ! empty( $subscriber_ip ) ) {
				$query       = "SELECT count(*) as count from " . IG_CONTACTS_IPS_TABLE . " WHERE ip = %s AND ( `created_on` >= NOW() - INTERVAL %s SECOND )";
				$subscribers = $wpdb->get_var( $wpdb->prepare( $query, $subscriber_ip, DAY_IN_SECONDS ) );

				if ( $subscribers > 0 ) {
					$timeout = MINUTE_IN_SECONDS * pow( 2, $subscribers - 1 );

					$query       = "SELECT count(*) as count from " . IG_CONTACTS_IPS_TABLE . " WHERE ip = %s AND ( `created_on` >= NOW() - INTERVAL %s SECOND ) LIMIT 1";
					$subscribers = $wpdb->get_var( $wpdb->prepare( $query, $subscriber_ip, $timeout ) );

					if ( $subscribers > 0 ) {
						return $timeout;
					}
				}

				// Add IP Address.
				$query = "INSERT INTO " . IG_CONTACTS_IPS_TABLE . " (`ip`) VALUES ( %s )";
				$wpdb->query( $wpdb->prepare( $query, $subscriber_ip ) );

				// Delete older entries
				$query = "DELETE FROM " . IG_CONTACTS_IPS_TABLE . " WHERE (`created_on` < NOW() - INTERVAL %s SECOND )";
				$wpdb->query( $wpdb->prepare( $query, DAY_IN_SECONDS ) );
			}
		}

		return false;
	}

	static function getUserIP() {

		// Get real visitor IP behind CloudFlare network
		if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
			$_SERVER['REMOTE_ADDR']    = $_SERVER["HTTP_CF_CONNECTING_IP"];
			$_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		}

		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
			$ip = $client;
		} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
			$ip = $forward;
		} else {
			$ip = $remote;
		}

		return $ip;
	}

}