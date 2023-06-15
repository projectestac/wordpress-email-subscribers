<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Lists_Contacts extends ES_DB {

	/**
	 * Table name
	 *
	 * @since 4.3.5
	 *
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Table DB version
	 *
	 * @since 4.3.5
	 *
	 * @var $version
	 */
	public $version;

	/**
	 * Table primary key column name
	 *
	 * @since 4.3.5
	 *
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists_Contacts constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_lists_contacts';

		$this->primary_key = 'id';

		$this->version = '1.0';

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initializes class
	 *
	 * @since 5.5.7
	 */
	public function init() {
		add_action( 'ig_es_list_deleted', array( $this, 'delete_contacts_from_list' ) );
	}

	/**
	 * Get columns
	 *
	 * @return array
	 *
	 * @since 4.3.5
	 */
	public function get_columns() {
		return array(
			'id'              => '%d',
			'list_id'         => '%d',
			'contact_id'      => '%d',
			'status'          => '%s',
			'optin_type'      => '%d',
			'subscribed_at'   => '%s',
			'subscribed_ip'   => '%s',
			'unsubscribed_at' => '%s',
			'unsubscribed_ip' => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since 4.3.5
	 */
	public function get_column_defaults() {
		return array(
			'list_id'         => 0,
			'contact_id'      => 0,
			'status'          => 0,
			'optin_type'      => 1,
			'subscribed_at'   => null,
			'subscribed_ip'   => null,
			'unsubscribed_at' => null,
			'unsubscribed_ip' => null,
		);
	}

	/**
	 * Add single contacts to multiple lists
	 *
	 * @param array $contact_data
	 * @param array $list_ids
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 */
	public function add_contact_to_lists( $contact_data = array(), $list_ids = array() ) {

		if ( ! is_array( $list_ids ) ) {
			$list_ids = array( $list_ids );
		}

		if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {

			$contact_data = apply_filters( 'ig_es_get_subscriber_ip', $contact_data, 'subscribed_ip' );

			// Remove entry if it's already there in a list
			$contact_id = ! empty( $contact_data['contact_id'] ) ? $contact_data['contact_id'] : 0;
			$this->remove_contacts_from_lists( $contact_id, $list_ids );

			foreach ( $list_ids as $list_id ) {
				$contact_data['list_id'] = $list_id;
				if ( ! $this->insert( $contact_data, 'list_contact' ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Prepare contact_data
	 *
	 * @param array   $contact_ids
	 * @param $list_id
	 *
	 * @return array|bool
	 *
	 * @since 4.3.5
	 */
	public function prepare_contact_data( $contact_ids, $list_id ) {

		if ( empty( $contact_ids ) || empty( $list_id ) ) {
			return array();
		}

		$list_id = esc_sql( absint( $list_id ) );

		$contact_data = array();
		if ( 0 != $list_id ) {

			$optin_type_option = get_option( 'ig_es_optin_type', true );

			$optin_type = 1;
			if ( in_array( $optin_type_option, array( 'double_opt_in', 'double_optin' ) ) ) {
				$optin_type = 2;
			}

			$data = array(
				'list_id'       => $list_id,
				'status'        => 'subscribed',
				'optin_type'    => $optin_type,
				'subscribed_at' => ig_get_current_date_time(),
			);

			foreach ( $contact_ids as $contact_id ) {
				$data['contact_id'] = $contact_id;
				$contact_data[]     = $data;
			}
		}

		return $contact_data;
	}

	/**
	 * Add contacts to list
	 *
	 * @param array $contact_ids
	 * @param int   $list_id
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 */
	public function add_contacts_to_list( $contact_ids = array(), $list_id = 0 ) {

		if ( empty( $list_id ) ) {
			return false;
		}

		if ( ! is_array( $contact_ids ) ) {
			$contact_ids = array( absint( $contact_ids ) );
		}

		$list_id = esc_sql( absint( $list_id ) );

		if ( 0 != $list_id ) {
			$this->remove_contacts_from_lists( $contact_ids, $list_id );

			$contact_data = $this->prepare_contact_data( $contact_ids, $list_id );

			return $this->bulk_insert( $contact_data );
		}

		return false;
	}

	/**
	 * Move multiple contacts to specific list
	 *
	 * @param array $contact_ids
	 * @param int   $list_id
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 */
	public function move_contacts_to_list( $contact_ids = array(), $list_id = 0 ) {

		if ( empty( $contact_ids ) ) {
			return false;
		}

		$list_id = esc_sql( absint( $list_id ) );

		if ( is_array( $contact_ids ) && count( $contact_ids ) > 0 ) {

			$this->remove_contacts_from_lists( $contact_ids );

			$contact_data = $this->prepare_contact_data( $contact_ids, $list_id );
			
			return $this->bulk_insert( $contact_data );
		}

		return false;
	}

	/**
	 * Get list ids by contact id
	 *
	 * @param $id
	 * @param string $status
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 * @since 4.3.5 Removed Static Method
	 */
	public function get_list_ids_by_contact( $contact_id = 0, $status = '' ) {
		global $wpdb;

		if ( empty( $contact_id ) ) {
			return array();
		}

		$where = $wpdb->prepare( 'contact_id = %d', esc_sql( $contact_id ) );

		if ( ! empty( $status ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', esc_sql( $status ) );
		}

		return $this->get_column_by_condition( 'list_id', $where );
	}

	/**
	 * Get mapping of contact status with list
	 *
	 * @param int $contact_id
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 * @since 4.3.5 Removed Static method. Call get_columns_map method
	 */
	public function get_list_contact_status_map( $contact_id = 0 ) {

		global $wpdb;

		if ( 0 == $contact_id ) {
			return array();
		}

		$where = $wpdb->prepare( 'contact_id = %d', $contact_id );

		return $this->get_columns_map( 'list_id', 'status', $where );
	}

	/**
	 * Update lists of contact
	 *
	 * @param int   $contact_id
	 * @param array $list_ids
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 * @since 4.3.6 Modified lists saving
	 */
	public function update_contact_lists( $contact_id = 0, $lists = array() ) {

		if ( empty( $contact_id ) || empty( $lists ) ) {
			return false;
		}

		$contact_id = esc_sql( $contact_id );
		$lists      = esc_sql( $lists );

		if ( ! empty( $lists ) ) {

			$optin_type_option = get_option( 'ig_es_optin_type', true );

			$optin_type = 1;
			if ( in_array( $optin_type_option, array( 'double_opt_in', 'double_optin' ) ) ) {
				$optin_type = 2;
			}

			// Remove from all lists
			$this->remove_contacts_from_lists( $contact_id );

			$data = array();
			$key  = 0;
			foreach ( $lists as $list_id => $status ) {
				if ( ! empty( $status ) ) {
					$data[ $key ]['list_id']       = $list_id;
					$data[ $key ]['contact_id']    = $contact_id;
					$data[ $key ]['status']        = $status;
					$data[ $key ]['optin_type']    = $optin_type;
					$data[ $key ]['subscribed_at'] = ig_get_current_date_time();

					$key ++;
				}
			}

			return ES()->lists_contacts_db->bulk_insert( $data );
		}

		return false;

	}

	/**
	 * Remove Contacts from lists
	 *
	 * @param array $contact_id
	 * @param array $list_ids
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 */
	public function remove_contacts_from_lists( $contact_ids = array(), $list_ids = array() ) {

		if ( ! is_array( $contact_ids ) ) {
			$contact_ids = array( absint( $contact_ids ) );
		}

		if ( ! is_array( $list_ids ) ) {
			$list_ids = array( absint( $list_ids ) );
		}

		$where = '';
		if ( is_array( $contact_ids ) && count( $contact_ids ) > 0 ) {

			$contact_ids_str = $this->prepare_for_in_query( $contact_ids );

			$where = "contact_id IN ($contact_ids_str)";
		}

		if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {

			$list_ids_str = $this->prepare_for_in_query( $list_ids );

			if ( ! empty( $where ) ) {
				$where .= ' AND ';
			}

			$where .= "list_id IN ($list_ids_str)";
		}

		return $this->delete_by_condition( $where );
	}

	/**
	 * Remove all contacts from specific list
	 *
	 * @param int   $list_id
	 * @param array $contact_ids
	 *
	 * @return bool
	 *
	 * @since 4.3.5
	 */
	public function remove_all_contacts_from_list( $list_id = 0 ) {
		return $this->remove_contacts_from_lists( array(), $list_id );
	}

	/**
	 * Import contacts into lists
	 *
	 * @param int   $list_id
	 * @param array $contacts
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 * @since 4.3.5
	 */
	public function import_contacts_into_lists( $list_id = 0, $contacts = array() ) {

		if ( count( $contacts ) > 0 ) {

			$emails = array();
			foreach ( $contacts as $contact ) {
				$emails[] = $contact['email'];
			}

			$contacts_str = $this->prepare_for_in_query( $emails );

			$where = "email IN ($contacts_str)";

			$email_id_map = ES()->contacts_db->get_columns_map( 'email', 'id', $where );

			foreach ( $contacts as $key => $contact ) {

				if ( empty( $email_id_map[ $contact['email'] ] ) ) {
					continue;
				}

				$contacts[ $key ]['contact_id'] = $email_id_map[ $contact['email'] ];
				$status                         = 'subscribed';
				$optin_type                     = IG_SINGLE_OPTIN;
				if ( 'Single Opt In' === $contact['status'] ) {
					$optin_type = IG_SINGLE_OPTIN;
				} elseif ( 'Confirmed' === $contact['status'] ) {
					$optin_type = IG_DOUBLE_OPTIN;
				} elseif ( 'Unconfirmed' === $contact['status'] ) {
					$optin_type = IG_DOUBLE_OPTIN;
					$status     = 'Unconfirmed';
				} elseif ( 'Unsubscribed' === $contact['status'] ) {
					$optin_type = IG_DOUBLE_OPTIN;
					$status     = 'unsubscribed';
				}

				$contacts[ $key ]['list_id']       = $list_id;
				$contacts[ $key ]['contact_id']    = $email_id_map[ $contact['email'] ];
				$contacts[ $key ]['status']        = $status;
				$contacts[ $key ]['optin_type']    = $optin_type;
				$contacts[ $key ]['subscribed_at'] = $contact['subscribed_at'];
			}

			return $this->bulk_insert( $contacts );
		}

		return true;
	}

	/**
	 * Add contacts into lists_contacts table
	 *
	 * @param array  $list_ids
	 * @param array  $contacts
	 * @param string $status
	 * @param int    $optin_type
	 * @param null   $subscribed_at
	 * @param null   $subscribed_ip
	 * @param null   $unsubscribed_at
	 * @param null   $unsubscribed_ip
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 * @since 4.3.5 Used bulk_insert method
	 * @since 4.6.4 Added support for multiple lists.
	 */
	public function do_import_contacts_into_list( $list_ids = array(), $contacts = array(), $status = 'subscribed', $optin_type = 1, $subscribed_at = null, $subscribed_ip = null, $unsubscribed_at = null, $unsubscribed_ip = null ) {
		if ( count( $contacts ) > 0 ) {
			$values = array();

			if ( ! is_array( $list_ids ) ) {
				$list_ids = array( absint( $list_ids ) );
			}

			$key = 0;
			foreach ( $contacts as $contact_id => $created_at ) {
				foreach ( $list_ids as $list_id ) {

					$values[ $key ]['contact_id']      = $contact_id;
					$values[ $key ]['list_id']         = $list_id;
					$values[ $key ]['status']          = $status;
					$values[ $key ]['optin_type']      = $optin_type;
					$values[ $key ]['subscribed_at']   = $created_at;
					$values[ $key ]['subscribed_ip']   = $subscribed_ip;
					$values[ $key ]['unsubscribed_at'] = $unsubscribed_at;
					$values[ $key ]['unsubscribed_ip'] = $unsubscribed_ip;

					$key ++;
				}
			}

			return $this->bulk_insert( $values );
		}

		return false;
	}

	/**
	 * Get total contacts based on list & status
	 *
	 * @param int    $list_id
	 * @param string $status
	 *
	 * @return string|null
	 *
	 * @since 4.0.0
	 * @since 4.3.5 Removed static call
	 */
	public function get_total_count_by_list( $list_id = 0, $status = 'subscribed' ) {

		// Convert to integer only if it a numberic value and not an array.
		if ( is_numeric( $list_id ) ) {
			$list_id = absint( $list_id );
		}

		if ( empty( $list_id ) ) {
			return 0;
		}

		return $this->get_contacts( $status, $list_id, 0, true, true );
	}


	/**
	 * Get total distinct contacts by condition
	 *
	 * @param string $where
	 *
	 * @return string|null
	 *
	 * @since 4.3.5
	 * @since 4.3.6 Added $distinct
	 */
	public function get_total_contacts( $where = '', $distinct = true ) {
		global $wpbd;

		if ( $distinct ) {
			$query = "SELECT count(DISTINCT(contact_id)) FROM $this->table_name";
		} else {
			$query = "SELECT count(contact_id) FROM $this->table_name";
		}

		if ( ! empty( $where ) ) {
			$query .= " WHERE $where";
		}

		return $wpbd->get_var( $query );
	}

	/**
	 * Get List => Status map by contact_ids
	 *
	 * @param array $contact_ids
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 * @since 4.3.5 Used prepare_for_in_query & get_columns_by_condition method
	 */
	public function get_list_status_by_contact_ids( $contact_ids = array() ) {

		if ( empty( $contact_ids ) ) {
			return array();
		}

		if ( ! is_array( $contact_ids ) ) {
			$contact_ids = array( $contact_ids );
		}

		$contact_ids_str = $this->prepare_for_in_query( $contact_ids );

		$where = "contact_id IN ($contact_ids_str)";

		$columns = array( 'contact_id', 'list_id', 'status' );

		$results = $this->get_columns_by_condition( $columns, $where );

		$map = array();
		if ( count( $results ) > 0 ) {

			foreach ( $results as $result ) {
				$map[ $result['contact_id'] ][ $result['list_id'] ] = $result['status'];
			}
		}

		return $map;
	}

	/**
	 * Update list wise subscription status based on contact ids
	 *
	 * @param $ids array
	 * @param $status string
	 *
	 * @return bool|int
	 *
	 * @since 4.1.14
	 * @since 4.3.5 Removed static call
	 */
	public function edit_subscriber_status( $ids = array(), $status = '', $list_ids = array() ) {
		global $wpbd;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}
		if ( ! empty( $list_ids ) && ! is_array( $list_ids ) ) {
			$list_ids = array( $list_ids );
		}

		$ids    = array_map( 'absint', $ids );
		$status = esc_sql( $status );

		$ids_str = implode( ',', $ids );

		$current_date = ig_get_current_date_time();

		$query = '';
		if ( 'subscribed' === $status ) {
			if ( ! empty( $list_ids ) ) {

				$list_ids_str = implode( ',', $list_ids );
				$result       = $wpbd->query(
					$wpbd->prepare(
						"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, subscribed_at = %s WHERE contact_id IN( {$ids_str} ) AND list_id IN( {$list_ids_str} )",
						array(
							$status,
							$current_date,
						)
					)
				);
			} else {
				$result = $wpbd->query(
					$wpbd->prepare(
						"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, subscribed_at = %s WHERE contact_id IN( {$ids_str} )",
						array(
							$status,
							$current_date,
						)
					)
				);
			}
			return $result;
		} elseif ( 'unsubscribed' === $status ) {
			if ( ! empty( $list_ids ) ) {

				$list_ids_str = implode( ',', $list_ids );

				return $wpbd->query(
					$wpbd->prepare(
						"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, unsubscribed_at = %s WHERE contact_id IN( {$ids_str} ) AND list_id IN( {$list_ids_str} )",
						array(
							$status,
							$current_date,
						)
					)
				);
			} else {
				return $wpbd->query(
					$wpbd->prepare(
						"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, unsubscribed_at = %s WHERE contact_id IN( {$ids_str} )",
						array(
							$status,
							$current_date,
						)
					)
				);
			}
		} elseif ( 'unconfirmed' === $status ) {
			return $wpbd->query(
				$wpbd->prepare(
					"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, optin_type = %d, subscribed_at = NULL, unsubscribed_at = NULL WHERE contact_id IN( {$ids_str} )",
					array(
						$status,
						IG_DOUBLE_OPTIN,
					)
				)
			);
		}

		return false;

	}

	/**
	 * Check whether status update required
	 *
	 * @param $contact_ids
	 * @param $status
	 *
	 * @return bool
	 *
	 * @since 4.1.14
	 * @since 4.3.5 Removed static call
	 */
	public function is_status_update_required( $ids = array(), $status = '' ) {
		global $wpbd;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids     = array_map( 'absint', $ids );
		$ids_str = implode( ',', $ids );

		$where = $wpbd->prepare( "contact_id IN($ids_str) && status != %s", $status );

		if ( $this->count( $where ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get Total Subscribers count based on timeline
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.3.2
	 * @since 4.3.5 Removed static call
	 * @since 4.3.6 Modified method name from get_total_subscribed_contacts to get_subscribed_contacts_count
	 */
	public function get_subscribed_contacts_count( $days = 0, $distinct = true ) {
		return $this->get_contacts( 'subscribed', 0, $days, true, $distinct );
	}

	/**
	 * Get Total Subscribers count based on timeline
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.3.2
	 * @since 4.3.5 Removed static call
	 * @since 4.3.6 Changed Method name from get_total_unsubscribed_contacts to get_unsubscribed_contacts_count
	 */
	public function get_unsubscribed_contacts_count( $days = 0, $distinct = true ) {
		return $this->get_contacts( 'unsubscribed', 0, $days, true, $distinct );
	}

	/**
	 * Get all confirmed contacts
	 *
	 * @param int  $days
	 * @param bool $distinct
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_confirmed_contacts_count( $days = 0, $distinct = true ) {
		return $this->get_contacts( 'confirmed', 0, $days, true, $distinct );
	}

	/**
	 * Get all unconfirmed contacts
	 *
	 * @param int  $days
	 * @param bool $distinct
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_unconfirmed_contacts_count( $days = 0, $distinct = true ) {
		return $this->get_contacts( 'unconfirmed', 0, $days, true, $distinct );
	}

	/**
	 * Get all contacts count
	 *
	 * @param int  $days
	 * @param bool $distinct
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_contacts_count( $days = 0, $distinct = true ) {
		return $this->get_contacts( 'all', 0, $days, true, $distinct );
	}

	/**
	 * Get all contacts based on status, list, days, count
	 *
	 * @param string $status
	 * @param int    $list_id
	 * @param int    $days
	 * @param bool   $count_only
	 * @param bool   $distinct
	 *
	 * @return array|object|string|null
	 *
	 * @since 4.3.6
	 */
	public function get_contacts( $status = 'all', $list_id = 0, $days = 0, $count_only = false, $distinct = true ) {
		global $wpdb, $wpbd;

		$expected_statuses = array( 'subscribed', 'unsubscribed', 'unconfirmed', 'confirmed', 'all' );

		if ( ! in_array( $status, $expected_statuses ) ) {
			return array();
		}

		$status = esc_sql( $status );
		if ( is_array( $list_id ) ) {
			$list_id = array_map( 'esc_sql', $list_id );
		} else {
			$list_id = esc_sql( $list_id );
		}

		$where[] = '1 = %d';
		$args[]  = 1;
		if ( ! empty( $status ) ) {
			switch ( $status ) {
				case 'subscribed':
				case 'unsubscribed':
				case 'unconfirmed':
					$where[] = 'status = %s';
					$args[]  = $status;
					break;
				case 'confirmed':
					$where[] = 'status = %s AND optin_type = %d';
					$args[]  = $status;
					$args[]  = IG_DOUBLE_OPTIN;
					break;
				default:
					$where[] = '1 = 1';
					break;
			}
		}

		if ( is_array( $list_id ) ) {
			$ids_count        = count( $list_id );
			$ids_placeholders = array_fill( 0, $ids_count, '%d' );
			$ids_query        = ' list_id IN( ' . implode( ',', $ids_placeholders ) . ' )';
			$where[]          = $ids_query;
			$args             = array_merge( $args, $list_id );
		} else {
			$list_id = absint( $list_id );
			if ( ! empty( $list_id ) ) {
				$list_id = esc_sql( $list_id );
				$where[] = 'list_id = %d';
				$args[]  = $list_id;
			}
		}

		$days = absint( $days );
		if ( $days > 0 ) {
			$days = esc_sql( $days );

			if ( 'unsubscribed' === $status ) {
				$where[] = 'unsubscribed_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
				$args[]  = $days;
			} elseif ( 'subscribed' === $status ) {
				$where[] = 'subscribed_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
				$args[]  = $days;
			} elseif ( 'unconfirmed' === $status ) {
				$where[] = "contact_id IN( SELECT id FROM `{$wpdb->prefix}ig_contacts` WHERE status = 'verified' && created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) )";
				$args[]  = $days;
			}
		}

		if ( count( $where ) > 0 ) {
			$where = implode( ' AND ', $where );
			$where = $wpbd->prepare( $where, $args );
		}

		if ( $count_only ) {
			return $this->get_total_contacts( $where, $distinct );
		} else {
			return $this->get_by_conditions( $where );
		}
	}

	/**
	 * Get contacts from list based on status
	 *
	 * @param string $status
	 * @param int    $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_contacts_from_list( $status = 'all', $list_id = 0 ) {
		$list_id = absint( $list_id );
		if ( empty( $list_id ) ) {
			return array();
		}

		return $this->get_contacts( $status, $list_id );
	}

	/**
	 * Get Subscribed contacts from list
	 *
	 * @param int $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_subscribed_contacts_from_list( $list_id = 0 ) {
		return $this->get_contacts_from_list( 'subscribed', $list_id );
	}

	/**
	 * Get Unsubscribed contacts from list
	 *
	 * @param int $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_unsubscribed_contacts_from_list( $list_id = 0 ) {
		return $this->get_contacts_from_list( 'unsubscribed', $list_id );
	}

	/**
	 * Get Confirmed contacts from list
	 *
	 * @param int $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_confirmed_contacts_from_list( $list_id = 0 ) {
		return $this->get_contacts_from_list( 'confirmed', $list_id );
	}

	/**
	 * Get Unconfirmed contacts from list
	 *
	 * @param int $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_unconfirmed_contacts_from_list( $list_id = 0 ) {
		return $this->get_contacts_from_list( 'unconfirmed', $list_id );
	}

	/**
	 * Get All Contacts from list
	 *
	 * @param int $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_contacts_from_list( $list_id = 0 ) {
		return $this->get_contacts_from_list( 'all', $list_id );
	}

	/**
	 * Get All Subscribed contacts
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_subscribed_contacts() {
		return $this->get_contacts( 'subscribed' );
	}

	/**
	 * Get All Unsubscribed contacts
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_unsubscribed_contacts() {
		return $this->get_contacts( 'unsubscribed' );
	}

	/**
	 * Get All Confirmed contacts from list
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_confirmed_contacts() {
		return $this->get_contacts( 'confirmed' );
	}

	/**
	 * Get All Unconfirmed contacts
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_unconfirmed_contacts() {
		return $this->get_contacts( 'unconfirmed' );
	}

	/**
	 * Get All Contacts
	 *
	 * @return array|object|null
	 *
	 * @since 4.3.6
	 */
	public function get_all_contacts() {
		return $this->get_contacts( 'all' );
	}

	/**
	 * Remove contacts from list
	 *
	 * @param $list_id
	 * 
	 * @return bool
	 *
	 * @since 5.5.7
	 */
	public function delete_contacts_from_list( $list_id = 0 ) {

		if ( empty( $list_id ) ) {
			return;
		}

		return $this->remove_all_contacts_from_list( $list_id );
	}
}
