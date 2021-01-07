<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Mailing_Queue {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

		global $wpdb;

		$this->table_name  = IG_MAILING_QUEUE_TABLE;
		$this->primary_key = 'id';
		$this->version     = '1.0';

	}

	/**
	 * Get columns and formats
	 *
	 * @since   2.1
	 */
	public static function get_columns() {
		return array(
			'id'          => '%d',
			'hash'        => '%s',
			'campaign_id' => '%d',
			'subject'     => '%s',
			'body'        => '%s',
			'count'       => '%d',
			'status'      => '%s',
			'start_at'    => '%s',
			'finish_at'   => '%s',
			'created_at'  => '%s',
			'updated_at'  => '%s',
			'meta'        => '%s'
		);
	}

	public static function get_column_defaults() {
		return array(
			'hash'        => null,
			'campaign_id' => 0,
			'subject'     => '',
			'body'        => '',
			'count'       => 0,
			'status'      => 'In Queue',
			'start_at'    => null,
			'finish_at'   => null,
			'created_at'  => ig_get_current_date_time(),
			'updated_at'  => null,
			'meta'        => null
		);
	}

	public static function get_notification_hash_to_be_sent() {
		global $wpdb;

		$hash = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT hash FROM {$wpdb->prefix}ig_mailing_queue WHERE status = %s ORDER BY id LIMIT 0, 1",
				'In Queue'
			)
		);

		//TODO :: update start date

		return $hash;

	}

	public static function get_notification_to_be_sent( $campaign_hash = '' ) {
		global $wpdb, $wpbd;

		$notification = array();

		$ig_mailing_queue_table = IG_MAILING_QUEUE_TABLE;

		$results = array();
		if ( ! empty( $campaign_hash ) ) {
			$results = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ig_mailing_queue WHERE hash = %s",
					array( $campaign_hash )
				),
				ARRAY_A
			);
		} else {
			$current_time = ig_get_current_date_time();
			$results 	  = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ig_mailing_queue WHERE status IN ('Sending', 'In Queue') AND start_at <= %s ORDER BY start_at, id LIMIT 0, 1",
					$current_time
				),
				ARRAY_A
			);
		}

		if ( count( $results ) > 0 ) {
			$notification = array_shift( $results );
			// refresh content
			$meta = maybe_unserialize( $notification['meta'] );

			if ( ! empty( $meta ) ) {
				$filter  = 'ig_es_refresh_' . $meta['type'] . '_content';
				$post_id = ! empty( $meta['post_id'] ) ? $meta['post_id'] : 0;
				$content = array();
				$content = apply_filters( $filter, $content, array( 'campaign_id' => $notification['campaign_id'], 'post_id' => $post_id ) );
				if ( ! empty( $content ) ) {
					$notification['subject'] = ! empty( $content['subject'] ) ? $content['subject'] : $notification['subject'];
					$notification['body']    = ! empty( $content['body'] ) ? $content['body'] : $notification['body'];
					$query_sub_str           = " , subject = '" . esc_sql( $notification['subject'] ) . "', body = '" . esc_sql( $notification['body'] ) . "' ";
				}
			}
			//update sent date
			$currentdate = ig_get_current_date_time();
			$query_str   = "UPDATE {$ig_mailing_queue_table} SET start_at = %s ";
			$where       = ' WHERE hash = %s AND finish_at = %s';
			$query_str   = ! empty( $query_sub_str ) ? $query_str . $query_sub_str . $where : $query_str . $where;
			$query       = $wpbd->prepare( $query_str, array( $currentdate, $notification['hash'], '0000-00-00 00:00:00' ) );
			$return_id   = $wpbd->query( $query );
		}

		return $notification;

	}

	// Query to insert sent emails (cron) records in table: es_sentdetails
	public static function update_sent_status( $hash = '', $status = 'In Queue' ) {

		global $wpdb;
		
		// If status is sent then add finish_at time as well.
		if ( 'Sent' === $status ) {
			$current_date_time = ig_get_current_date_time();
			$return_id = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}ig_mailing_queue SET status = %s, finish_at = %s WHERE hash = %s",
					$status,
					$current_date_time,
					$hash
				)
			);
		} else {
			$return_id = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}ig_mailing_queue SET status = %s WHERE hash = %s",
					$status,
					$hash
				)
			);
		}

		return $return_id;
	}

	/* Get sent email count */
	public static function get_sent_email_count( $notification_hash ) {
		global $wpdb;
		$email_count = $wpdb->get_col(
			$wpdb->prepare( 
				"SELECT count FROM {$wpdb->prefix}ig_mailing_queue WHERE hash = %s ",
				array( $notification_hash )
			)
		);
		$email_count = array_shift( $email_count );

		return $email_count;
	}

	public static function get_notification_by_hash( $notification_hash ) {
		global $wpdb;

		$notification = array();
		$results      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ig_mailing_queue WHERE hash = %s",
				$notification_hash
			),
			ARRAY_A
		);

		if ( count( $results ) > 0 ) {
			$notification = array_shift( $results );
		}

		return $notification;
	}

	public static function get_notification_by_campaign_id( $campaign_id ) {
		global $wpdb;

		$notification = array();
		$results      = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ig_mailing_queue WHERE campaign_id = %d", $campaign_id ),
			ARRAY_A
		);

		if ( count( $results ) > 0 ) {
			$notification = array_shift( $results );
		}

		return $notification;
	}

	public static function get_notifications( $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		if ( ! empty( $per_page ) && ! empty( $page_number ) ) {
			$start_limit = ( $page_number - 1 ) * $per_page;
			$result      = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ig_mailing_queue ORDER BY created_at DESC LIMIT %d, %d",
					$start_limit,
					$per_page
				),
				ARRAY_A
			);
		} else {
			$result = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ig_mailing_queue ORDER BY created_at DESC "
				),
				ARRAY_A
			);
		}

		return $result;
	}

	public static function get_notifications_count() {
		global $wpdb;

		$result = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT count(*) as total_notifications FROM {$wpdb->prefix}ig_mailing_queue WHERE %d",
				1
			)
		);

		return $result[0];
	}

	public static function delete_notifications( $ids ) {
		global $wpdb;

		$ids = esc_sql( $ids );

		$ids = implode( ',', array_map( 'absint', $ids ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ig_mailing_queue WHERE FIND_IN_SET(id, %s)",
				$ids
			)
		);
	}

	public static function add_notification( $data ) {
		global $wpdb;

		$column_formats  = self::get_columns();
		$column_defaults = self::get_column_defaults();
		$prepared_data   = ES_DB::prepare_data( $data, $column_formats, $column_defaults, true );

		$data           = $prepared_data['data'];
		$column_formats = $prepared_data['column_formats'];

		$inserted = $wpdb->insert( IG_MAILING_QUEUE_TABLE, $data, $column_formats );

		$last_report_id = 0;
		if ( $inserted ) {
			$last_report_id = $wpdb->insert_id;
		}

		return $last_report_id;
	}

	public static function update_notification( $notification_id, $data ) {
		global $wpdb;

		$column_formats  = self::get_columns();
		$column_defaults = self::get_column_defaults();
		$prepared_data   = ES_DB::prepare_data( $data, $column_formats, $column_defaults, true );

		$data           = $prepared_data['data'];
		$column_formats = $prepared_data['column_formats'];

		$wpdb->update( IG_MAILING_QUEUE_TABLE, $data, array( 'id' => $notification_id ), $column_formats );

	}

	public static function get_id_details_map() {
		global $wpdb;

		$query   = 'SELECT id, start_at, hash FROM ' . IG_MAILING_QUEUE_TABLE;
		$results = $wpdb->get_results( 
			$wpdb->prepare(
				"SELECT id, start_at, hash FROM {$wpdb->prefix}ig_mailing_queue WHERE %d",
				1
			),
			ARRAY_A
		);
		$details = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$details[ $result['hash'] ]['id']       = $result['id'];
				$details[ $result['hash'] ]['start_at'] = $result['start_at'];
			}
		}

		return $details;
	}

	public static function get_email_by_id( $mailing_queue_id ) {
		global $wpdb;

		$report  = array();
		$results = $wpdb->get_results( 
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ig_mailing_queue WHERE id = %s",
				$mailing_queue_id
			),
			ARRAY_A
		);

		if ( count( $results ) > 0 ) {
			$report = array_shift( $results );
		}

		return $report;
	}

	/**
	 * Get recent campaigns data
	 * 
	 * @param int $count
	 *
	 * @return array|object|null
	 *
	 * @since 4.4.0
	 */
	public static function get_recent_campaigns( $count = 5 ) {
		global $wpdb;

		if ( ! is_numeric( $count ) ) {
			$count = 5;
		}

		return $wpdb->get_results( 
			$wpdb->prepare(
				"SELECT id, hash, campaign_id, subject, start_at, status, finish_at FROM {$wpdb->prefix}ig_mailing_queue order by created_at DESC LIMIT 0, %d",
				$count
			),
			ARRAY_A
		);
	}

	public static function do_insert( $place_holders, $values ) {
		global $wpdb, $wpbd;

		$query  = "INSERT INTO {$wpdb->prefix}ig_mailing_queue (`hash`, `campaign_id`, `subject`, `body`, `count`, `status`, `start_at`, `finish_at`, `created_at`, `updated_at`) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql    = $wpbd->prepare( $query, $values );

		$logger = get_ig_logger();
		$logger->info( 'Query....<<<<<' . $sql );

		if ( $wpbd->query( $sql ) ) {
			return true;
		} else {
			return false;
		}

	}

	public static function migrate_notifications() {
		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(*) as total FROM {$wpdb->prefix}es_notification WHERE %d",
				1
			)
		);

		if ( $total > 0 ) {
			$columns = self::get_columns();
			unset( $columns['id'] );
			$fields = array_keys( $columns );

			$batch_size     = IG_DEFAULT_BATCH_SIZE;
			$total_bataches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;

			for ( $i = 0; $i < $total_bataches; $i ++ ) {
				$batch_start = $i * $batch_size;

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}es_sentdetails LIMIT %d, %d",
						$batch_start,
						$batch_size
					),
					ARRAY_A
				);

				$values        = array();
				$place_holders = array();
				foreach ( $results as $key => $result ) {
					$queue_data['hash']        = $result['es_sent_guid'];
					$queue_data['campaign_id'] = 0;
					$queue_data['subject']     = $result['es_sent_subject'];
					$queue_data['body']        = $result['es_sent_preview'];
					$queue_data['count']       = $result['es_sent_count'];
					$queue_data['status']      = $result['es_sent_status'];
					$queue_data['start_at']    = $result['es_sent_starttime'];
					$queue_data['finish_at']   = $result['es_sent_endtime'];
					$queue_data['created_at']  = $result['es_sent_starttime'];

					$queue_data = wp_parse_args( $queue_data, self::get_column_defaults() );

					$formats = array();
					foreach ( $columns as $column => $format ) {
						$values[]  = $queue_data[ $column ];
						$formats[] = $format;
					}

					$place_holders[] = '( ' . implode( ', ', $formats ) . ' )';
				}

				ES_DB::do_insert( IG_MAILING_QUEUE_TABLE, $fields, $place_holders, $values );
			}
		}
	}

	/**
	 * Method to update subscribers count in mailing queue table.
	 * 
	 * @param string $hash Mailing queue hash.
	 * @param int $count Subscribers count.
	 * 
	 * @since 4.6.3
	 */ 
	public static function update_subscribers_count( $hash = '', $count = 0 ) {

		global $wpdb;
		
		if ( empty( $hash ) ) {
			return;
		}
		
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}ig_mailing_queue SET count = %d WHERE hash = %s",
				$count,
				$hash
			)
		);
	}
}
