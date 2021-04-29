<?php

class ES_DB_Lists_Contacts {

	public function __construct() {

	}

	public static function add_lists_contacts( $data ) {
		global $wpdb;
		$values = array();
		foreach ( $data['list_id'] as $list_id ) {
			array_push( $values, $list_id, $data['contact_id'], $data['status'], $data['optin_type'], $data['subscribed_at'], $data['subscribed_ip'] );
			$place_holders[] = "( %d, %d, %s, %s, %s, %s )"; /* In my case, i know they will always be integers */
		}
		$query = "INSERT INTO " . IG_LISTS_CONTACTS_TABLE . " (`list_id`, `contact_id`, `status`, `optin_type`, `subscribed_at`, `subscribed_ip` ) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( "$query ", $values );
		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_list_ids_by_contact( $id ) {
		global $wpdb;
		$query = "SELECT list_id FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id = $id";
		$res   = $wpdb->get_col( $query );

		return $res;
	}

	public static function get_list_details_by_contact( $id ) {
		global $wpdb;
		$query = "SELECT * FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id = $id";
		$res   = $wpdb->get_results( $query, ARRAY_A );

		return $res;
	}

	public static function get_list_contact_status_map( $id ){
		global $wpdb;
		$query = "SELECT list_id, status FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id = $id";
		$res   = $wpdb->get_results( $query, ARRAY_A );
		foreach ( $res as $list ) {
			$lists_contact_status_map[ $list['list_id'] ] = $list['status'];
		}
		return $lists_contact_status_map;
	}

	public static function update_list_contacts( $contact_id, $list_ids ) {
		global $wpdb;
		$query      = "DELETE FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id = $contact_id";
		$res        = $wpdb->get_results( $query );
		$optin_type_option = get_option( 'ig_es_optin_type', true );

		$optin_type = 1;
		if(in_array($optin_type_option, array('double_opt_in', 'double_optin'))) {
			$optin_type = 2;
		}

		if ( ! empty( $list_ids ) ) {
			$data['list_id']       = $list_ids;
			$data['contact_id']    = $contact_id;
			$data['status']        = 'subscribed';
			$data['optin_type']    = $optin_type;
			$data['subscribed_at'] = ig_get_current_date_time();
			$data['subscribed_ip'] = '';
			$result                = ES_DB_Lists_Contacts::add_lists_contacts( $data );
		}

		return $res;
	}

	public static function delete_list_contacts( $contact_id, $list_ids ) {
		global $wpdb;
		$list_ids = implode( ',', $list_ids );
		$query    = "DELETE FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id = $contact_id AND list_id IN ($list_ids)";
		$res      = $wpdb->get_results( $query );

		return $res;
	}

	public static function import_contacts_into_lists( $list_id, $contacts ) {
		global $wpdb;


		if ( count( $contacts ) > 0 ) {

			$emails = array();
			foreach ( $contacts as $contact ) {
				$emails[] = $contact['email'];
			}

			$contacts_str = "'" . implode( "', '", $emails ) . "'";
			$query        = "SELECT id, email FROM " . IG_CONTACTS_TABLE . " WHERE email IN ( {$contacts_str} )";
			$results      = $wpdb->get_results( $query, ARRAY_A );
			$email_id_map = array();
			foreach ( $results as $result ) {
				$email_id_map[ $result['email'] ] = $result['id'];
			}

			$values = array();
			foreach ( $contacts as $contact ) {
				$status     = 'subscribed';
				$optin_type = IG_SINGLE_OPTIN;
				if ( $contact['status'] === 'Single Opt In' ) {
					$optin_type = IG_SINGLE_OPTIN;
				} elseif ( $contact['status'] === 'Confirmed' ) {
					$optin_type = IG_DOUBLE_OPTIN;
				} elseif ( $contact['status'] === 'Unconfirmed' ) {
					$optin_type = IG_DOUBLE_OPTIN;
					$status     = 'Unconfirmed';
				} elseif($contact['status'] === 'Unsubscribed') {
					$optin_type = IG_DOUBLE_OPTIN;
					$status     = 'unsubscribed';
				}

				array_push( $values, $list_id, $email_id_map[ $contact['email'] ], $status, $optin_type, $contact['subscribed_at'] );
				$place_holders[] = "( %d, %d, %s, %s, %s )"; /* In my case, i know they will always be integers */
			}

			$query = "INSERT INTO " . IG_LISTS_CONTACTS_TABLE . " (`list_id`, `contact_id`, `status`, `optin_type`, `subscribed_at` ) VALUES ";
			$query .= implode( ', ', $place_holders );
			$sql   = $wpdb->prepare( $query, $values );

			if ( $wpdb->query( $sql ) ) {
				return true;
			} else {
				return false;
			}

		}

		return true;
	}

	public static function do_import_contacts_into_list( $list_id, $contacts, $status = 'subscribed', $optin_type = 1, $subscribed_at = null, $subscribed_ip = null, $unsubscribed_at = null, $unsubscribed_ip = null ) {

		global $wpdb;

		if ( count( $contacts ) > 0 ) {

			$values = array();
			foreach ( $contacts as $contact_id ) {

				array_push( $values, $list_id, $contact_id, $status, $optin_type, $subscribed_at, $subscribed_ip, $unsubscribed_at, $unsubscribed_ip );
				$place_holders[] = "( %d, %d, %s, %d, %s, %s, %s, %s )"; /* In my case, i know they will always be integers */
			}

			$query = "INSERT INTO " . IG_LISTS_CONTACTS_TABLE . " (`list_id`, `contact_id`, `status`, `optin_type`, `subscribed_at`, `subscribed_ip`, `unsubscribed_at`, `unsubscribed_ip` ) VALUES ";
			$query .= implode( ', ', $place_holders );
			$sql   = $wpdb->prepare( $query, $values );

			if ( $wpdb->query( $sql ) ) {
				return true;
			} else {
				return false;
			}

		}

		return true;

	}

	public static function get_total_count_by_list( $list_id, $status = 'active' ) {
		global $wpdb;

		if ( 'active' === $status ) {
			$sql = "SELECT count(*) FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE list_id = %d AND status = 'subscribed'";
		} else {
			$sql = "SELECT count(*) FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE list_id = %d";
		}

		$total_count = $wpdb->get_var( $wpdb->prepare( $sql, $list_id ) );

		return $total_count;

	}


}