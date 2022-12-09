<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Contacts extends ES_DB {

	/**
	 * Table name
	 *
	 * @since 4.2.4
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Table DB version
	 *
	 * @since 4.2.4
	 * @var $version
	 */
	public $version;

	/**
	 * Table primary key column name
	 *
	 * @since 4.2.4
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Contacts constructor.
	 *
	 * @since 4.2.4
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_contacts';

		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Get columns
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_columns() {
		$columns = array(
			'id'               => '%d',
			'wp_user_id'       => '%d',
			'first_name'       => '%s',
			'last_name'        => '%s',
			'email'            => '%s',
			'source'           => '%s',
			'ip_address'       => '%s',
			'country_code'     => '%s',
			'form_id'          => '%d',
			'status'           => '%s',
			'reference_site'   => '%s',
			'unsubscribed'     => '%d',
			'hash'             => '%s',
			'engagement_score' => '%f',
			'created_at'       => '%s',
			'updated_at'       => '%s',
			'is_verified'      => '%d',
			'is_disposable'    => '%d',
			'is_rolebased'     => '%d',
			'is_webmail'       => '%d',
			'is_deliverable'   => '%d',
			'is_sendsafely'    => '%d',
			'timezone'         => '%s',
			'meta'             => '%s',
		);

		$custom_field_data = ES()->custom_fields_db->get_custom_fields();
		$custom_field_cols = array();
		if ( count( $custom_field_data ) > 0 ) {
			foreach ($custom_field_data as $key => $data) {
				$type = '%s';
				if ( isset( $data[ 'type' ] ) && 'number' === $data[ 'type' ] ) {
					$type = '%d';
				}
				$custom_field_cols[$data['slug']] = $type;
			}
		}

		$columns = array_merge( $columns, $custom_field_cols);
		return $columns;
	}

	/**
	 * Get default column values
	 *
	 * @since   4.0.0
	 */
	public function get_column_defaults() {
		$default_col_values = array(
			'wp_user_id'     	=> 0,
			'first_name'     	=> '',
			'last_name'      	=> '',
			'email'          	=> '',
			'source'         	=> '',
			'ip_address'	 	=> '',
			'country_code'	 	=> '',
			'form_id'        	=> 0,
			'status'         	=> 'verified',
			'reference_site'    => '',
			'unsubscribed'   	=> 0,
			'hash'           	=> '',
			'engagement_score' 	=> 4,
			'created_at'     	=> ig_get_current_date_time(),
			'updated_at'     	=> '',
			'is_verified'    	=> 1,
			'is_disposable'  	=> 0,
			'is_rolebased'   	=> 0,
			'is_webmail'     	=> 0,
			'is_deliverable' 	=> 1,
			'is_sendsafely'  	=> 1,
			'timezone'			=> '',
			'meta'           	=> '',
		);

		$custom_field_data = ES()->custom_fields_db->get_custom_fields();
		$custom_field_cols = array();
		if ( count( $custom_field_data ) > 0 ) {
			foreach ($custom_field_data as $key => $data) {
				$custom_field_cols[$data['slug']] = null;
			}
		}

		$columns = array_merge( $default_col_values, $custom_field_cols);
		return $columns;
	}

	/**
	 * Get by id
	 *
	 * @param $id
	 *
	 * @return array|object|void|null
	 *
	 * @since 4.0.0
	 */
	public function get_by_id( $id ) {
		return $this->get( $id );
	}

	/**
	 * Get contact email name map
	 *
	 * @param array $emails
	 *
	 * @return array
	 *
	 * @since 4.2.2
	 */
	public function get_contacts_email_name_map( $emails = array() ) {

		global $wpbd;

		$subscriber_email_name_map = array();
		if ( count( $emails ) > 0 ) {

			$emails_str = "'" . implode( "','", $emails ) . "'";

			$subscribers = $wpbd->get_results(
				"SELECT email, first_name, last_name FROM {$wpbd->prefix}ig_contacts WHERE email IN({$emails_str})",
				ARRAY_A
			);

			if ( count( $subscribers ) > 0 ) {
				foreach ( $subscribers as $subscriber ) {
					$name = ES_Common::prepare_name_from_first_name_last_name( $subscriber['first_name'], $subscriber['last_name'] );

					$subscriber_email_name_map[ $subscriber['email'] ] = array(
						'name'       => $name,
						'first_name' => $subscriber['first_name'],
						'last_name'  => $subscriber['last_name'],
					);
				}
			}
		}

		return $subscriber_email_name_map;
	}

	/**
	 * Update contact
	 *
	 * @param int $id
	 *
	 * @return void
	 *
	 * @since 4.3.2
	 */
	public function update_contact( $contact_id = 0, $data = array() ) {

		if ( ! empty( $contact_id ) ) {

			$email = ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
			if ( ! empty( $email ) ) {

				$first_name = ! empty( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
				$last_name  = ! empty( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';

				$data_to_update = array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'email'      => $email,
					'updated_at' => ig_get_current_date_time(),
				);

				foreach ( $data as $key => $value ) {
					if ( strpos( $key, 'cf_') !== false ) {
						$data_to_update[$key] = sanitize_text_field( $value );
					}
				}


				$this->update( $contact_id, $data_to_update );
			}
		}

	}

	/**
	 * Get contact hash by contact id
	 *
	 * @param $id
	 *
	 * @return array|string|null
	 *
	 * @since 4.0.0
	 */
	public function get_contact_hash_by_id( $id ) {

		if ( ! empty( $id ) ) {
			return $this->get_column( 'hash', $id );
		}

		return '';

	}

	/**
	 * Is contacts exists based on id & email?
	 *
	 * @param string $id
	 * @param string $email
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.4
	 */
	public function is_contact_exists( $id = '', $email = '' ) {
		global $wpdb;

		if ( ! empty( $id ) && ! empty( $email ) ) {

			$where = $wpdb->prepare( 'id = %d AND email = %s', $id, $email );
			$count = $this->count( $where );

			if ( $count ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get active contacts by list_id
	 *
	 * @param $list_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.2.4
	 */
	public function get_active_contacts_by_list_id( $list_id ) {

		if ( empty( $list_id ) ) {
			return array();
		}

		global $wpdb;

		// Check if we have got array of list ids.
		if ( is_array( $list_id ) ) {
			$list_ids_str = implode( ',', $list_id );
		} else {
			$list_ids_str = $list_id;
		}

		$where = "id IN (SELECT contact_id FROM {$wpdb->prefix}ig_lists_contacts WHERE list_id IN({$list_ids_str}) AND status IN ('subscribed', 'confirmed'))";

		return $this->get_by_conditions( $where );

	}

	/**
	 * Get active contacts by list_id and excluding contacts from sending queue having given mailing_queue_id
	 *
	 * @param $list_id
	 * @param $mailing_queue_id
	 *
	 * @return array|object|null
	 *
	 * @since 4.6.3
	 */
	public function get_active_contacts_by_list_and_mailing_queue_id( $list_id, $mailing_queue_id = 0 ) {

		if ( empty( $list_id ) ) {
			return array();
		}

		global $wpbd;

		// Check if we have got array of list ids.
		if ( is_array( $list_id ) ) {
			$ids_count        = count( $list_id );
			$ids_placeholders = array_fill( 0, $ids_count, '%d' );
			$query_args       = $list_id;
			$query_args[]     = $mailing_queue_id;
			$where            = $wpbd->prepare(
				"id IN (SELECT contact_id FROM {$wpbd->prefix}ig_lists_contacts WHERE list_id IN( " . implode( ',', $ids_placeholders ) . " ) AND status IN ('subscribed', 'confirmed')) AND id NOT IN(SELECT contact_id FROM {$wpbd->prefix}ig_sending_queue WHERE mailing_queue_id = %d )",
				$query_args
			);
		} else {
			$where = $wpbd->prepare( "id IN (SELECT contact_id FROM {$wpbd->prefix}ig_lists_contacts WHERE list_id = %d AND status IN ('subscribed', 'confirmed')) AND id NOT IN(SELECT contact_id FROM {$wpbd->prefix}ig_sending_queue WHERE mailing_queue_id = %d )", $list_id, $mailing_queue_id );
		}

		return $this->get_by_conditions( $where );
	}


	/**
	 * Get contacts by ids
	 *
	 * @param $ids
	 *
	 * @return array|object|null
	 *
	 * @since 4.2.1
	 *
	 * @modify 4.2.4
	 */
	public function get_contacts_by_ids( $ids ) {

		if ( ! is_array( $ids ) && ! count( $ids ) > 0 ) {
			return array();
		}

		$ids_str = $this->prepare_for_in_query( $ids );

		$where = "id IN ($ids_str)";

		return $this->get_by_conditions( $where );
	}

	/**
	 * Count Active Contacts by list id
	 *
	 * @param string $list_id
	 *
	 * @return string|null
	 *
	 * @since 4.2.4
	 */
	public function count_active_contacts_by_list_id( $list_id = '' ) {

		global $wpdb;

		if ( ! empty( $list_id ) ) {
			$subscribers = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT count(distinct(contact_id)) as total_subscribers FROM {$wpdb->prefix}ig_lists_contacts WHERE status = %s AND list_id = %d",
					'subscribed',
					$list_id
				)
			);
		} else {
			$subscribers = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT count(distinct(contact_id)) as total_subscribers FROM {$wpdb->prefix}ig_lists_contacts WHERE status = %s",
					'subscribed'
				)
			);
		}

		return $subscribers;

	}



		// Get all contact ids
	public function get_all_contact_ids() {
		global $wpbd;

		$query = "SELECT id FROM $this->table_name";
		return $wpbd->get_results( $query );
	}


	/**
	 * Get Total Contacts
	 *
	 * @since 4.3.2
	 */
	public function get_total_contacts() {
		return $this->count();
	}

	/**
	 * Delete Contacts by ids
	 *
	 * @param $ids
	 *
	 * @return bool|int
	 *
	 * @since 4.2.4
	 *
	 * @modify 4.3.12
	 */
	public function delete_contacts_by_ids( $ids = array() ) {

		$ids = $this->prepare_for_in_query( $ids );

		$where = "id IN ($ids)";

		$delete = $this->delete_by_condition( $where );

		if ( $delete ) {
			$where = "contact_id IN ($ids)";

			return ES()->lists_contacts_db->delete_by_condition( $where );
		}

		return false;

	}

	/**
	 * Edit global status of contact
	 *
	 * @param $ids
	 * @param $unsubscribed
	 *
	 * @return bool|int
	 *
	 * @since 4.2.4
	 * @since 4.3.4 Use prepare_for_in_query instead of array_to_str
	 */
	public function edit_contact_global_status( $ids = array(), $unsubscribed = 0 ) {
		global $wpbd;

		$ids_str = implode( ',', $ids );

		return $wpbd->query(
			$wpbd->prepare(
				"UPDATE {$wpbd->prefix}ig_contacts SET unsubscribed = %d WHERE id IN({$ids_str})",
				$unsubscribed
			)
		);
	}

	/**
	 * Is Contact exists in list?
	 *
	 * @param $email
	 * @param $list_id
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 * @since 4.3.4 Used prepare_for_in_query instead of array_to_str
	 */
	public function is_contact_exist_in_list( $email, $list_id ) {
		global $wpbd;

		// Flush cache to ensure we have latest results.
		ES_Cache::flush();

		$contact_id = $this->get_column_by( 'id', 'email', $email );

		$data = array();
		if ( ! empty( $contact_id ) ) {
			$data['contact_id'] = $contact_id;

			if ( ! is_array( $list_id ) ) {
				$list_id = array( $list_id );
			}

			$list_ids_str = implode( ',', $list_id );

			$list_contact_count = $wpbd->get_var(
				$wpbd->prepare(
					"SELECT count(*) as count FROM {$wpbd->prefix}ig_lists_contacts WHERE list_id IN ($list_ids_str) AND contact_id = %d",
					$contact_id
				)
			);

			if ( ! empty( $list_contact_count ) ) {
				$data['list_id'] = true;
			}

			return $data;
		}

		return $data;
	}

	/**
	 * Get Email Details Map
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_email_details_map() {
		global $wpdb;

		$contacts = $wpdb->get_results(
			"SELECT id, email, hash FROM {$wpdb->prefix}ig_contacts",
			ARRAY_A
		);
		$details  = array();
		if ( count( $contacts ) > 0 ) {
			foreach ( $contacts as $contact ) {
				$details[ $contact['email'] ]['id']   = $contact['id'];
				$details[ $contact['email'] ]['hash'] = $contact['hash'];
			}
		}

		return $details;

	}

	/**
	 * Get contacts id details map
	 *
	 * @param array $contact_ids
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 * @since 4.2.4
	 */
	public function get_details_by_ids( $contact_ids = array() ) {

		$contacts = $this->get_contacts_by_ids( $contact_ids );

		$results = array();
		if ( ! empty( $contacts ) && count( $contacts ) > 0 ) {

			foreach ( $contacts as $contact ) {
				$results[ $contact['id'] ] = $contact;
			}
		}

		return $results;
	}

	/**
	 * Get contact ids by emails
	 *
	 * @param array $emails
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 * @since 4.3.4 Used prepare_for_in_query instead of array_to_str
	 */
	public function get_contact_ids_created_at_date_by_emails( $emails = array() ) {
		global $wpbd;

		if ( count( $emails ) > 0 ) {
			$ids_count        = count( $emails );
			$ids_placeholders = array_fill( 0, $ids_count, '%s' );
			$results          = $wpbd->get_results(
				$wpbd->prepare(
					"SELECT id, created_at FROM {$wpbd->prefix}ig_contacts WHERE email IN( " . implode( ',', $ids_placeholders ) . ' )',
					$emails
				),
				ARRAY_A
			);
		} else {
			$results = $wpbd->get_results( "SELECT id , created_at FROM {$wpbd->prefix}ig_contacts" );
		}

		$map = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$map[ $result['id'] ] = $result['created_at'];
			}
		}

		return $map;
	}

	/**
	 * Get contacts Email => id map
	 *
	 * @param array $emails
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 * @since 4.3.4 Used prepare_for_in_query instead of array_to_str
	 */
	public function get_email_id_map( $emails = array() ) {
		global $wpbd;

		if ( count( $emails ) > 0 ) {
			$email_count  = count( $emails );
			$placeholders = array_fill( 0, $email_count, '%s' );
			$results      = $wpbd->get_results(
				$wpbd->prepare(
					"SELECT id, email FROM {$wpbd->prefix}ig_contacts WHERE email IN( " . implode( ',', $placeholders ) . ' )',
					$emails
				),
				ARRAY_A
			);
		} else {
			$results = $wpbd->get_results( "SELECT id, email FROM {$wpbd->prefix}ig_contacts", ARRAY_A );
		}

		$map = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$map[ $result['email'] ] = $result['id'];
			}
		}

		return $map;

	}

	/**
	 * Get contact id by email
	 *
	 * @param $email
	 *
	 * @return string|null
	 */
	public function get_contact_id_by_email( $email ) {

		if ( empty( $email ) ) {
			return null;
		}

		return $this->get_column_by( 'id', 'email', $email );
	}

	/**
	 * Migrate all subscribers from 3.5.x to contacts table
	 *
	 * @since 4.0.0
	 */
	public function migrate_subscribers_from_older_version() {
		global $wpdb;

		// Get Total count of subscribers
		$total = $wpdb->get_var( "SELECT count(*) as total FROM {$wpdb->prefix}es_emaillist" );

		// If we have subscribers?
		if ( $total > 0 ) {

			// Get all existing Contacts
			$emails = $this->get_column( 'email' );
			if ( ! is_array( $emails ) ) {
				$emails = array();
			}

			// Import subscribers into batch of 100
			$batch_size     = IG_DEFAULT_BATCH_SIZE;
			$total_batches  = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;
			$lists_contacts = array();
			// $exclude_status = array( 'Unsubscribed', 'Unconfirmed' );
			$j = 0;
			for ( $i = 0; $i < $total_batches; $i ++ ) {
				$batch_start = $i * $batch_size;
				$results     = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}es_emaillist LIMIT %d, %d ",
						$batch_start,
						$batch_size
					),
					ARRAY_A
				);
				if ( count( $results ) > 0 ) {
					$contacts = array();
					foreach ( $results as $key => $result ) {
						$email = $result['es_email_mail'];
						if ( ! in_array( $email, $emails ) ) {

							$contacts[ $key ] = $result;

							$names = array(
								'first_name' => '',
								'last_name'  => '',
							);

							if ( ! empty( $result['es_email_name'] ) ) {
								$names = ES_Common::prepare_first_name_last_name( $result['es_email_name'] );
							} else {
								$name = ES_Common::get_name_from_email( $email );

								$names['first_name'] = $name;
							}

							$contacts[ $key ]['first_name']   = $names['first_name'];
							$contacts[ $key ]['last_name']    = $names['last_name'];
							$contacts[ $key ]['email']        = $email;
							$contacts[ $key ]['source']       = 'Migrated';
							$contacts[ $key ]['status']       = ( 'spam' === strtolower( $result['es_email_status'] ) ) ? 'spam' : 'verified';
							$contacts[ $key ]['unsubscribed'] = ( 'Unsubscribed' === $result['es_email_status'] ) ? 1 : 0;
							$contacts[ $key ]['hash']         = $result['es_email_guid'];
							$contacts[ $key ]['created_at']   = $result['es_email_created'];
							$contacts[ $key ]['updated_at']   = ig_get_current_date_time();

							$emails[] = $email;
						}

						// Collect all contacts based on Lists
						// if ( ! in_array( $result['es_email_status'], $exclude_status ) ) {
						$lists_contacts[ $result['es_email_group'] ][ $j ]['email']         = $email;
						$lists_contacts[ $result['es_email_group'] ][ $j ]['status']        = $result['es_email_status'];
						$lists_contacts[ $result['es_email_group'] ][ $j ]['subscribed_at'] = $result['es_email_created'];
						$lists_contacts[ $result['es_email_group'] ][ $j ]['subscribed_ip'] = null;
						$j ++;
						// }
					}

					$this->bulk_insert( $contacts );
				}
			}

			// Do import Lists Contacts
			if ( count( $lists_contacts ) > 0 ) {
				$list_name_id_map = ES()->lists_db->get_list_id_name_map( '', true );
				foreach ( $lists_contacts as $list_name => $contacts ) {
					if ( ! empty( $list_name_id_map[ $list_name ] ) ) {
						ES()->lists_contacts_db->import_contacts_into_lists( $list_name_id_map[ $list_name ], $contacts );
					}
				}
			}
		}
	}

	/**
	 * Edit List Contact Status
	 *
	 * @param $contact_ids
	 * @param $list_ids
	 * @param $status
	 *
	 * @return bool|int
	 *
	 * @since 4.2.0
	 * @since 4.3.4 Used prepare_for_in_query instead of array_to_str
	 */
	public function edit_list_contact_status( $contact_ids, $list_ids, $status ) {
		global $wpbd;

		$contact_ids  = implode( ',', $contact_ids );
		$list_ids     = implode( ',', $list_ids );
		$current_date = ig_get_current_date_time();

		$query_result = array();
		if ( 'subscribed' === $status ) {
			$query_result = $wpbd->query(
				$wpbd->prepare(
					"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, subscribed_at = %s WHERE contact_id IN( {$contact_ids} ) AND list_id IN( {$list_ids} )",
					array(
						$status,
						$current_date,
					)
				)
			);
		} elseif ( 'unsubscribed' === $status ) {
			$query_result = $wpbd->query(
				$wpbd->prepare(
					"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, unsubscribed_at = %s WHERE contact_id IN( {$contact_ids} ) AND list_id IN( {$list_ids} )",
					array(
						$status,
						$current_date,
					)
				)
			);
		} elseif ( 'unconfirmed' === $status ) {
			$query_result = $wpbd->query(
				$wpbd->prepare(
					"UPDATE {$wpbd->prefix}ig_lists_contacts SET status = %s, optin_type = %d, subscribed_at = NULL, unsubscribed_at = NULL WHERE contact_id IN( {$contact_ids} ) AND list_id IN( {$list_ids} )",
					array(
						$status,
						IG_DOUBLE_OPTIN,
					)
				)
			);
		}

		return $query_result;
	}

	/**
	 * Get total contacts by date
	 *
	 * @param string $status
	 * @param int    $days
	 *
	 * @return array
	 *
	 * @since 4.4.0
	 */
	public function get_total_contacts_by_date( $status = 'subscribed', $days = 60 ) {

		if ( 'subscribed' === $status ) {
			$results = $this->get_total_subscribed_contacts_by_date( $days );
		}

		return $results;
	}

	/**
	 * Get contact id by email
	 *
	 * @param $email
	 *
	 * @return string|null
	 *
	 * @since 4.4.1
	 */
	public function get_contact_id_by_wp_user_id( $user_id ) {

		if ( empty( $user_id ) ) {
			return null;
		}

		return $this->get_column_by( 'id', 'wp_user_id', $user_id );
	}

	/**
	 * Get total subscribed contacts by date
	 *
	 * @param string $status
	 * @param int    $days
	 *
	 * @return array
	 *
	 * @since 4.4.2
	 */
	public function get_total_subscribed_contacts_by_date( $days = 60 ) {
		global $wpbd;

		$columns = array( 'DATE(created_at) as date', 'count(DISTINCT(id)) as total' );
		$where   = 'unsubscribed = %d';
		$args[]  = 0;

		if ( 0 != $days ) {
			$days   = esc_sql( $days );
			$where .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
			$args[] = $days;
		}

		$group_by = ' GROUP BY DATE(created_at)';

		$where .= $group_by;

		$where = $wpbd->prepare( $where, $args );

		$results = $this->get_columns_by_condition( $columns, $where );

		$contacts = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$contacts[ $result['date'] ] = $result['total'];
			}
		}

		return $contacts;
	}

	/**
	 * Get total subscribed contacts before $days
	 *
	 * @param int $days
	 *
	 * @return array
	 *
	 * @since 4.4.2
	 */
	public function get_total_subscribed_contacts_before_days( $days = 60 ) {
		global $wpbd;

		$columns = array( 'count(DISTINCT(id)) as total' );
		$where   = 'unsubscribed = %d';
		$args[]  = 0;

		if ( 0 != $days ) {
			$days   = esc_sql( $days );
			$where .= ' AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)';
			$args[] = $days;
		}

		$where = $wpbd->prepare( $where, $args );

		$results = $this->get_columns_by_condition( $columns, $where );

		$results = array_shift( $results );

		return $results['total'];
	}


	/**
	 * Get total subscribed contacts between $days
	 *
	 * @param int $days
	 *
	 * @return array
	 *
	 * @since 4.4.2
	 */
	public function get_total_subscribed_contacts_between_days( $days = 60 ) {
		global $wpbd;

		$columns = array( 'count(DISTINCT(id)) as total' );
		$where   = 'unsubscribed = %d';
		$args[]  = 0;

		if ( 0 != $days ) {
			$days   = esc_sql( $days );
			$where .= ' AND created_at > DATE_SUB(NOW(), INTERVAL %d DAY) AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY) ';
			$args[] = $days * 2;
			$args[] = $days;
		}

		$where = $wpbd->prepare( $where, $args );

		$results = $this->get_columns_by_condition( $columns, $where );

		$results = array_shift( $results );

		return $results['total'];
	}


	/**
	 * Count contacts by Form id
	 *
	 * @param string $form_id
	 *
	 * @return string|null
	 *
	 * @since 4.6.3
	 */
	public function get_total_contacts_by_form_id( $form_id = '', $status = 0 ) {

		global $wpdb;

		$total_subscribers = '';

		if ( ! empty( $form_id ) ) {
			$total_subscribers = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT count(distinct(id)) as total_active_subscribers FROM {$wpdb->prefix}ig_contacts where form_id = %d AND unsubscribed = %d",
					$form_id,
					$status
				)
			);
		}

		return $total_subscribers;

	}

	/**
	 * Migrate Ip address of subscribers from lists_contacts to contacts table
	 *
	 * @since 4.6.3
	 */
	public function migrate_ip_from_list_contacts_to_contacts_table() {
		global $wpdb;

		// Get Total count of subscribers
		$total = $wpdb->get_var( "SELECT count(*) as total FROM {$wpdb->prefix}ig_contacts" );

		// If we have subscribers?
		if ( $total > 0 ) {

			$wpdb->query(
				"UPDATE {$wpdb->prefix}ig_contacts AS contact_data
				LEFT JOIN {$wpdb->prefix}ig_lists_contacts AS list_data
				ON contact_data.id = list_data.contact_id
				SET contact_data.ip_address = list_data.subscribed_ip
				WHERE contact_data.id = list_data.contact_id
				AND list_data.subscribed_ip IS NOT NULL
				AND list_data.subscribed_ip <> ''"
			);
		}
	}

	/**
	 * Add custom fields column
	 *
	 * @param $col_name
	 * @param string $type
	 *
	 * @return array
	 *
	 * @since 4.8.4
	 */
	public function add_custom_field_col_in_contacts_table( $slug_name, $custom_field_type = 'text' ) {
		global $wpbd;

		$col_added = 0;
		if ( ! empty( $slug_name ) ) {
			// To check if column exists or not
			$custom_field_col = $wpbd->get_results( $wpbd->prepare( "SHOW COLUMNS FROM {$wpbd->prefix}ig_contacts LIKE %s", $slug_name ) , 'ARRAY_A' );
			$custom_field_num_rows    = $wpbd->num_rows;

			// If column doesn't exists, then insert it
			if ( '1' != $custom_field_num_rows ) {

				$col_data_type = ES_Common::get_custom_field_col_datatype( $custom_field_type );
				// Template table
				$col_added = $wpbd->query( "ALTER TABLE {$wpbd->prefix}ig_contacts
									ADD COLUMN {$slug_name} {$col_data_type} DEFAULT NULL" );
			}
		}
		return $col_added;
	}

	/**
	 * Delete Custom fields columns
	 *
	 * @param $ids
	 *
	 * @since 4.8.4
	 */
	public function delete_col_by_custom_field_id( $cf_ids ) {

		global $wpbd;
		if ( ! is_array( $cf_ids ) ) {
			$ids = array( $cf_ids );
		}

		$col_deleted = 0;
		$slug_name_list = ES()->custom_fields_db->get_custom_field_slug_list_by_ids( $cf_ids );

		if ( is_array( $slug_name_list ) && count( $slug_name_list ) > 0 ) {

			foreach ( $slug_name_list as $col_name ) {
				$col_deleted = $wpbd->query( "ALTER TABLE {$wpbd->prefix}ig_contacts
									DROP COLUMN {$col_name}" );
			}
		}
		return $col_deleted;

	}

	/**
	 * Insert IP along with subscriber data
	 *
	 * @param $data
	 * @param string $type
	 *
	 * @return int
	 *
	 * @since 4.6.3
	 */
	public function insert( $data, $type = '' ) {
		$source = array( 'admin', 'import' );

		if ( ! ES()->is_pro() ) {
			$data['ip_address']   = '';
			$data['country_code'] = '';
		} else {

			if ( empty( $data['ip_address'] ) && ! in_array( $data['source'], $source, true ) ) {
					$data = apply_filters( 'ig_es_get_subscriber_ip', $data, 'ip_address' );
			}

			if ( ! empty( $data['ip_address'] ) ) {
				$data = apply_filters( 'ig_es_get_country_based_on_ip', $data );
			}
		}
		$contact_id = parent::insert( $data, $type );
		do_action( 'ig_es_new_contact_inserted', $contact_id );
		return $contact_id;
	}

	public function get_last_contact_id() {
		global $wpdb;
		$last_contact_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}ig_contacts ORDER BY id DESC LIMIT 0, 1" );
		return $last_contact_id;
	}
}
