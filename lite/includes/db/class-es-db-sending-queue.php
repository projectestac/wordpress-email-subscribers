<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Sending_Queue {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	public static function get_columns() {
		return array(
			'id'                 => '%d',
			'mailing_queue_id'   => '%d',
			'mailing_queue_hash' => '%s',
			'campaign_id'        => '%d',
			'contact_id'         => '%d',
			'contact_hash'       => '%s',
			'email'              => '%s',
			'status'             => '%s',
			'links'              => '%s',
			'opened'             => '%d',
			'sent_at'            => '%s',
			'opened_at'          => '%s',
		);
	}

	public static function get_column_defaults() {
		return array(
			'mailing_queue_id'   => 0,
			'mailing_queue_hash' => '',
			'campaign_id'        => 0,
			'contact_id'         => 0,
			'contact_hash'       => '',
			'email'              => '',
			'status'             => '',
			'links'              => '',
			'opened'             => 0,
			'sent_at'            => null,
			'opened_at'          => null,
		);
	}

	public static function get_emails_to_be_sent_by_hash( $guid, $limit ) {
		global $wpdb;

		$subscribers = $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT * FROM {$wpdb->prefix}ig_sending_queue WHERE status = %s AND mailing_queue_hash = %s ORDER BY id LIMIT 0, %d",
				array(
					'In Queue',
					$guid,
					$limit
				)
			),
			ARRAY_A
		);

		return $subscribers;
	}

	public static function update_sent_status( $contact_ids, $message_id = 0, $status = 'Sent' ) {
		global $wpbd;

		$updated = false;
		if ( 0 == $message_id ) {
			return $updated;
		}

		$id_str = '';
		if ( is_array( $contact_ids ) && count( $contact_ids ) > 0 ) {
			$id_str = implode( ',', $contact_ids );
		} elseif ( is_string( $contact_ids ) ) {
			$id_str = $contact_ids;
		}

		if ( ! empty( $id_str ) ) {
			if ( 'Sent' === $status ) {
				$current_time = ig_get_current_date_time();
				$updated = $wpbd->query(
					$wpbd->prepare(
						"UPDATE {$wpbd->prefix}ig_sending_queue SET status = %s, sent_at = %s WHERE mailing_queue_id = %d AND contact_id IN($id_str)",
						$status,
						$current_time,
						$message_id
					)
				);
			} else {
				$updated = $wpbd->query(
					$wpbd->prepare(
						"UPDATE {$wpbd->prefix}ig_sending_queue SET status = %s WHERE mailing_queue_id = %d AND contact_id IN($id_str)",
						$status,
						$message_id
					)
				);
			}
		}

		return $updated;

	}

	/* count cron email */
	public static function get_total_emails_to_be_sent_by_hash( $notification_hash = '' ) {

		global $wpdb;

		$result = 0;
		if ( ! empty( $notification_hash ) ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) AS count FROM {$wpdb->prefix}ig_sending_queue WHERE mailing_queue_hash = %s AND status = %s",
					array( $notification_hash, 'In Queue' )
				)
			);
		}

		return $result;

	}

	public static function get_total_emails_to_be_sent() {

		global $wpdb;

		$result = $wpdb->get_var( 
			$wpdb->prepare(
				"SELECT COUNT(*) AS count FROM {$wpdb->prefix}ig_sending_queue WHERE status = %s",
				array( 'In Queue' )
			)
		 );

		return $result;

	}

	public static function get_total_emails_sent_by_hash( $notification_hash ) {

		global $wpdb;

		$result = 0;
		if ( '' != $notification_hash ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) AS count FROM {$wpdb->prefix}ig_sending_queue WHERE mailing_queue_hash = %s AND status = %s",
					array( $notification_hash, 'Sent' )
				)
			);
		}

		return $result;

	}

	public static function get_emails_by_hash( $notification_hash ) {
		global $wpdb;

		$emails = array();
		if ( '' != $notification_hash ) {
			$emails = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ig_sending_queue WHERE mailing_queue_hash = %s",
					array( $notification_hash )
				),
				ARRAY_A
			);

			// We are not migrating reports data because it caused lots of migration issues
			// in the past. So, we are fetching reports data from older table if we don't get
			// the data from the new table.
			// This is generally fetch the data for older campaigns
			if ( count( $emails ) == 0 ) {
				$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s ', $wpdb->prefix . 'es_deliverreport' ) );
				if ( $result === $wpdb->prefix . 'es_deliverreport' ) {
					$emails = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}es_deliverreport WHERE es_deliver_sentguid = %s", 
							array( $notification_hash ) 
						),
						ARRAY_A
					);
				}
			}
		}

		return $emails;
	}

	public static function do_batch_insert( $delivery_data ) {

		$status = ! empty( $delivery_data['status'] ) ? $delivery_data['status'] : 'In Queue';

		$data['mailing_queue_id']   = $delivery_data['mailing_queue_id'];
		$data['mailing_queue_hash'] = $delivery_data['hash'];
		$data['campaign_id']        = $delivery_data['campaign_id'];
		$data['status']             = $status;

		$columns = self::get_columns();
		unset( $columns['id'] );
		$fields = array_keys( $columns );

		$batches = array_chunk( $delivery_data['subscribers'], 50 );

		$emails = array();
		foreach ( $batches as $key => $batch ) {
			$place_holders = array();
			$values        = array();
			foreach ( $batch as $subscriber ) {

				$email      = ! empty( $subscriber['email'] ) ? $subscriber['email'] : '';
				$contact_id = ! empty( $subscriber['id'] ) ? $subscriber['id'] : 0;

				if ( ! empty( $email ) && ! in_array( $email, $emails ) ) {

					$emails[] = $email;

					$data['contact_id']   = $contact_id;
					$data['email']        = $email;
					$data['contact_hash'] = $subscriber['hash'];
					$data                 = wp_parse_args( $data, self::get_column_defaults() );
					$formats              = array();
					foreach ( $columns as $column => $format ) {
						$values[]  = $data[ $column ];
						$formats[] = $format;
					}

					$place_holders[] = '( ' . implode( ', ', $formats ) . ' )';
				}
			}

			ES_DB::do_insert( IG_SENDING_QUEUE_TABLE, $fields, $place_holders, $values );
		}

		return true;

	}

	public static function do_insert( $place_holders, $values ) {
		global $wpbd;

		$delivery_reports_table = IG_SENDING_QUEUE_TABLE;

		$query  = "INSERT INTO $delivery_reports_table (`mailing_queue_id`, `mailing_queue_hash`, `campaign_id`, `contact_id`, `contact_hash`, `email`, `status`, `links`, `opened`, `sent_at`, `opened_at`) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql    = $wpbd->prepare( "$query ", $values );

		if ( $wpbd->query( $sql ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Method to insert sending queue data from contact table.
	 * 
	 * @param int $mailing_queue_id Mailing queue ID.
	 * @param string $mailing_queue_hash Mailing Hash.
	 * @param int $campaign_id Campaign ID.
	 * @param array|string $list_ids List IDs seperated by commas if string i.e. '1,2,3' or array( 1, 2, 3 ) if array.
	 * 
	 * @return bool $is_inserted Is contacts inserted into sending_queue table.
	 * 
	 * @since 4.6.4
	 */
	public static function do_insert_from_contacts_table( $mailing_queue_id = 0, $mailing_queue_hash = '', $campaign_id = 0, $list_ids = array() ) {

		global $wpbd;
	
		$is_inserted = false;
	
		if ( empty( $mailing_queue_id ) || empty( $mailing_queue_hash ) || empty( $campaign_id ) ) {
			return $is_inserted;
		}
	
		$column_defaults = self::get_column_defaults();
	
		$queue_status    = 'In Queue';
		$queue_links     = isset( $column_defaults['links'] ) ? $column_defaults['links']    : '';
		$queue_opened    = isset( $column_defaults['opened'] ) ? $column_defaults['opened']  : 0;
		$queue_sent_at   = isset( $column_defaults['sent_at'] ) ? $column_defaults['sent_at']: null;
		$queue_opened_at = isset( $column_defaults['opened_at'] ) ? $column_defaults['opened_at'] : null;
	
		$campaign = ES()->campaigns_db->get( $campaign_id );
		$args = array(
			'select'     => array( 'subscribers.id' ),
			'lists'      => $list_ids,
			'return_sql' => true, // This flag will return the required sql query
			'orderby'    => array( 'id'),
		);
		if ( ! empty( $campaign ) && ! empty( $campaign['meta'] ) ) {
			$campaign_meta = maybe_unserialize( $campaign['meta'] );
			if ( ! empty( $campaign_meta['list_conditions'] ) ) {
				$args['conditions'] = $campaign_meta['list_conditions'];
			}
		}
		$query     = new IG_ES_Subscribers_Query();
		$sql_query = $query->run( $args );

		$query_args = array(
			$mailing_queue_id,
			$mailing_queue_hash,
			$campaign_id,
			$queue_status,
			$queue_links,
			$queue_opened,
			$queue_sent_at,
			$queue_opened_at,
		);
	
		$total_contacts_added = $wpbd->query(
			$wpbd->prepare(
				"INSERT INTO `{$wpbd->prefix}ig_sending_queue` 
				(
					`mailing_queue_id`,
					`mailing_queue_hash`,
					`campaign_id`,
					`contact_id`,
					`contact_hash`,
					`email`,
					`status`,
					`links`,
					`opened`,
					`sent_at`,
					`opened_at`
				)
				SELECT 
					%d AS `mailing_queue_id`,
					%s AS `mailing_queue_hash`,
					%d AS `campaign_id`,
					MAX(`ig_contacts`.`id`) AS `contact_id`,
					MAX(`ig_contacts`.`hash`) AS `contact_hash`,
					`ig_contacts`.`email` AS `email`,
					%s AS `status`,
					%s AS `links`,
					%d AS `opened`,
					%s AS `sent_at`,
					%s AS `opened_at`
				FROM `{$wpbd->prefix}ig_contacts` AS `ig_contacts` 
				WHERE id IN ( " . $sql_query . ')
				GROUP BY `ig_contacts`.`email`',
				$query_args
			)
		);
	
		// Check if contacts added.
		if ( ! empty( $total_contacts_added ) ) {
			$is_inserted = true;
			ES_DB_Mailing_Queue::update_subscribers_count( $mailing_queue_hash, $total_contacts_added );
		} else {
	
			// If some how above sql query fails then queue emails using old approach.
			// i.e. Preparing data for insert query in PHP and then doing insert.
			
			// Converto to an array if already not an array.
			if ( ! is_array( $list_ids ) ) {
				$list_ids = explode( ',', $list_ids );
			}
	
			$subscribers       = ES()->contacts_db->get_active_contacts_by_list_id( $list_ids );
			$subscribers_count = count( $subscribers );
			if ( $subscribers_count > 0 ) {
				// Add entry into sending queue table
				$delivery_data                     = array();
				$delivery_data['hash']             = $mailing_queue_hash;
				$delivery_data['status']           = $queue_status;
				$delivery_data['subscribers']      = $subscribers;
				$delivery_data['campaign_id']      = $campaign_id;
				$delivery_data['mailing_queue_id'] = $mailing_queue_id;
				$is_inserted = self::do_batch_insert( $delivery_data );
				if ( $is_inserted ) {
					ES_DB_Mailing_Queue::update_subscribers_count( $mailing_queue_hash, $subscribers_count );
				}
			}
		}
	
		return $is_inserted;
	}

	public static function update_viewed_status( $guid = '', $email = '', $message_id = 0 ) {
		global $wpdb;

		$current_date = ig_get_current_date_time();

		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}ig_sending_queue SET opened_at = %s, opened = %d WHERE (mailing_queue_id = %d OR mailing_queue_hash = %s ) AND email = %s",
				$current_date,
				1,
				$message_id,
				$guid,
				$email
			)
		);
	}

	/*
	 * Commenting for now as we might need this function in the future. 
	public static function migrate_reports_data() {
		global $wpdb;

		$mailing_queue_details = ES_DB_Mailing_Queue::get_id_details_map();
		$email_details         = ES()->contacts_db->get_email_details_map();

		$query = 'SELECT count(*) as total FROM ' . EMAIL_SUBSCRIBERS_STATS_TABLE;

		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {
			$columns = self::get_columns();
			unset( $columns['id'] );
			$fields     = array_keys( $columns );
			$batch_size = IG_DEFAULT_BATCH_SIZE;

			$total_bataches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;

			$last_sending_queue_batch_run = get_transient( 'ig_es_last_sending_queue_batch_run' . false );

			if ( false === $last_sending_queue_batch_run ) {
				$batch_start_from = 0;
			} else {
				$batch_start_from = $last_sending_queue_batch_run + 1;
			}

			$logger = get_ig_logger();
			$logger->info( 'Sending Queue Start From: ' . $batch_start_from, array( 'source' => 'es_update' ) );

			for ( $i = $batch_start_from; $i < $total_bataches; $i ++ ) {
				if ( false === get_transient( 'ig_es_running_migration_for_' . $i ) ) {

					set_transient( 'ig_es_running_migration_for_' . $i, true, 300 );
					$batch_start = $i * $batch_size;

					$query   = 'SELECT * FROM ' . EMAIL_SUBSCRIBERS_STATS_TABLE . " LIMIT {$batch_start}, {$batch_size}";
					$results = $wpdb->get_results( $query, ARRAY_A );

					$values        = array();
					$data          = array();
					$place_holders = array();

					foreach ( $results as $key => $result ) {
						$email     = $result['es_deliver_emailmail'];
						$is_opened = ( '0000-00-00 00:00:00' != $result['es_deliver_viewdate'] ) ? 1 : 0;

						$contact_id = 0;
						$hash       = '';
						if ( isset( $email_details[ $email ] ) ) {
							$contact_id = $email_details[ $email ]['id'];
							$hash       = $email_details[ $email ]['hash'];
						}

						$mailing_queue_id           = ! empty( $mailing_queue_details[ $result['es_deliver_sentguid'] ] ) ? $mailing_queue_details[ $result['es_deliver_sentguid'] ]['id'] : 0;
						$start_at                   = ! empty( $mailing_queue_details[ $result['es_deliver_sentguid'] ] ) ? $mailing_queue_details[ $result['es_deliver_sentguid'] ]['start_at'] : '0000-00-00 00:00:00';
						$data['mailing_queue_id']   = $mailing_queue_id;
						$data['mailing_queue_hash'] = $result['es_deliver_sentguid'];
						$data['contact_id']         = $contact_id;
						$data['contact_hash']       = $hash;
						$data['email']              = $email;
						$data['status']             = $result['es_deliver_sentstatus'];
						$data['opened']             = $is_opened;
						$data['sent_at']            = $start_at;
						$data['opened_at']          = $result['es_deliver_viewdate'];

						$data = wp_parse_args( $data, self::get_column_defaults() );

						$formats = array();
						foreach ( $columns as $column => $format ) {
							$values[]  = $data[ $column ];
							$formats[] = $format;
						}

						$place_holders[] = '( ' . implode( ', ', $formats ) . ' )';
					}

					$logger->info( '------------------[Running.....]: ' . $i, array( 'source' => 'es_update' ) );
					ES_DB::do_insert( IG_SENDING_QUEUE_TABLE, $fields, $place_holders, $values );

					delete_transient( 'ig_es_running_migration_for_' . $i );

					$logger->info( '------------------[last_sending_queue_batch_run]: ' . $i, array( 'source' => 'es_update' ) );
					set_transient( 'ig_es_last_sending_queue_batch_run', $i, MINUTE_IN_SECONDS * 100 );
				}

			}
		}
	}
	*/

	/*
	public static function migrate_reports_data() {
		global $wpdb;

		$mailing_queue_details = ES_DB_Mailing_Queue::get_id_details_map();
		$email_details         = ES()->contacts_db->get_email_details_map();

		$query = "SELECT count(*) as total FROM " . EMAIL_SUBSCRIBERS_STATS_TABLE;

		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {
			$columns = self::get_columns();
			unset( $columns['id'] );
			$fields     = array_keys( $columns );
			$batch_size = IG_DEFAULT_BATCH_SIZE;

			$total_bataches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;

			$logger = get_ig_logger();

			for ( $i = 0; $i < $total_bataches; $i ++ ) {

				if(false === get_transient('running_reports_migration_for')) {

					set_transient( 'running_reports_migration_for', true, 300 );

					$batch_start = 0;

					$query           = "SELECT * FROM " . EMAIL_SUBSCRIBERS_STATS_TABLE . " LIMIT {$batch_start}, {$batch_size}";
					$results         = $wpdb->get_results( $query, ARRAY_A );
					$values          = $data = $place_holders = array();
					$es_delivery_ids = array();
					foreach ( $results as $key => $result ) {

						$es_delivery_ids[] = $result['es_deliver_id'];

						$email     = $result['es_deliver_emailmail'];
						$is_opened = ( $result['es_deliver_viewdate'] != '0000-00-00 00:00:00' ) ? 1 : 0;

						$contact_id = 0;
						$hash       = '';
						if ( isset( $email_details[ $email ] ) ) {
							$contact_id = $email_details[ $email ]['id'];
							$hash       = $email_details[ $email ]['hash'];
						}

						$mailing_queue_id           = ! empty( $mailing_queue_details[ $result['es_deliver_sentguid'] ] ) ? $mailing_queue_details[ $result['es_deliver_sentguid'] ]['id'] : 0;
						$start_at                   = ! empty( $mailing_queue_details[ $result['es_deliver_sentguid'] ] ) ? $mailing_queue_details[ $result['es_deliver_sentguid'] ]['start_at'] : '0000-00-00 00:00:00';
						$data['mailing_queue_id']   = $mailing_queue_id;
						$data['mailing_queue_hash'] = $result['es_deliver_sentguid'];
						$data['contact_id']         = $contact_id;
						$data['contact_hash']       = $hash;
						$data['email']              = $email;
						$data['status']             = $result['es_deliver_sentstatus'];
						$data['opened']             = $is_opened;
						$data['sent_at']            = $start_at;
						$data['opened_at']          = $result['es_deliver_viewdate'];

						$data = wp_parse_args( $data, self::get_column_defaults() );

						$formats = array();
						foreach ( $columns as $column => $format ) {
							$values[]  = $data[ $column ];
							$formats[] = $format;
						}

						$place_holders[] = "( " . implode( ', ', $formats ) . " )";
					}

					$logger->info( '------------------[Running.....]: ' . $i, array( 'source' => 'es_update' ) );
					ES_DB::do_insert( IG_SENDING_QUEUE_TABLE, $fields, $place_holders, $values );

					$logger->info( '------------------[Deleting Records]: ', array( 'source' => 'es_update' ) );
					self::delete_records_from_delivereport( $es_delivery_ids );
					$logger->info( '------------------[Deleted]: ' . print_r($es_delivery_ids, true), array( 'source' => 'es_update' ) );
					delete_transient( 'running_reports_migration_for' );
				}

			}
		}
	}
	*/
	
	public static function delete_records_from_delivereport( $ids ) {
		global $wpbd;

		$delivereport_ids = implode( ',', array_map( 'absint', $ids ) );

		$query = 'DELETE FROM ' . EMAIL_SUBSCRIBERS_STATS_TABLE . " WHERE es_deliver_id IN ($delivereport_ids)";

		$wpbd->query( $query );
	}

	public static function delete_sending_queue_by_mailing_id( $mailing_queue_ids ) {
		global $wpbd;
		
		$mailing_queue_ids = esc_sql( $mailing_queue_ids );
		$mailing_queue_ids = implode( ',', array_map( 'absint', $mailing_queue_ids ) );

		$wpbd->query(
				"DELETE FROM {$wpbd->prefix}ig_sending_queue WHERE mailing_queue_id IN ($mailing_queue_ids)"
		);
	}

	// Query to get total viewed emails per report
	public static function get_viewed_count_by_hash( $hash = '' ) {

		global $wpdb;

		$result = 0;

		if ( '' != $hash ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) AS count FROM {$wpdb->prefix}ig_sending_queue WHERE opened = 1 AND mailing_queue_hash = %s",
					array( $hash )
				)
			);

			if ( 0 == $result ) {
				$table_name = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'es_deliverreport' ) );
				if ( $table_name === $wpdb->prefix . 'es_deliverreport' ) {
					$result = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) AS count FROM {$wpdb->prefix}es_deliverreport WHERE es_deliver_status = 'Viewed' AND  es_deliver_sentguid = %s",
							array( $hash )
						)
					);
				}
			}

		}

		return $result;

	}

	public static function get_total_email_count_by_hash( $hash = '' ) {

		global $wpdb;

		$result = 0;

		if ( '' != $hash ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) AS count FROM {$wpdb->prefix}ig_sending_queue WHERE mailing_queue_hash = %s", 
					array( $hash )
				)
			);
			if ( 0 == $result ) {
				$es_deliver_report_table = EMAIL_SUBSCRIBERS_STATS_TABLE;
				$table_name              = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'es_deliverreport' ) );
				if ( $table_name === $es_deliver_report_table ) {
					$result = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) AS count FROM {$wpdb->prefix}es_deliverreport WHERE es_deliver_sentguid = %s",
							array( $hash )
						)
					);
				}
			}
		}

		return $result;

	}

	/**
	 * Get Total Opened emails count based on $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.3.2
	 */
	public static function get_total_contacts_opened_emails( $days = 0 ) {

		global $wpbd;

		$ig_sending_queue_table = IG_SENDING_QUEUE_TABLE;

		$query = "SELECT COUNT(DISTINCT(`contact_id`)) FROM $ig_sending_queue_table WHERE `opened` = %d";

		$args[] = 1;

		if ( 0 != $days ) {
			$days   = esc_sql( $days );
			$where  = ' AND opened_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
			$query .= $where;
			$args[] = $days;
		}

		return $wpbd->get_var( $wpbd->prepare( $query, $args ) );
	}

	
	/**
	 * Get Email => ID map based on Sending Queue table
	 * 
	 * @param int $campaign_id
	 * @param array $emails
	 *
	 * @return array
	 *
	 * @since 4.3.7
	 */
	public static function get_emails_id_map_by_campaign( $campaign_id = 0, $emails = array() ) {
		global $wpbd;

		$emails      = esc_sql( $emails );
		$campaign_id = esc_sql( absint( $campaign_id ) );

		if ( 0 === $campaign_id || empty( $emails ) ) {
			return array();
		}

		$emails_str = "'" . implode( "','", $emails ) . "'";

		$results = $wpbd->get_results( 
			$wpbd->prepare(
				"SELECT contact_id, email FROM {$wpbd->prefix}ig_sending_queue WHERE campaign_id = %d AND email IN($emails_str)", 
				$campaign_id
			), 
			ARRAY_A
		);

		$emails_id_map = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$emails_id_map[ $result['email'] ] = $result['contact_id'];
			}
		}

		return $emails_id_map;
	}


	/**
	 * Get Queue data
	 *
	 * @return array
	 *
	 * @since 4.6.12
	 */
	public static function get_queue_data( $campaign_id = '', $message_id = '' ) {
		global $wpdb;

		$emails = array();
		if ( ! empty( $message_id ) && ! empty( $campaign_id ) ) {
			$emails = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ig_sending_queue WHERE campaign_id = %d AND mailing_queue_id = %d AND contact_id NOT IN ( SELECT contact_id FROM {$wpdb->prefix}ig_actions WHERE campaign_id = %d AND message_id = %d )",
					array( $campaign_id, $message_id, $campaign_id, $message_id )
				),
				ARRAY_A
			);
		}

		return $emails;
	}


}
