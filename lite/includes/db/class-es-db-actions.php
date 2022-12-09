<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Actions extends ES_DB {
	/**
	 * Table Name
	 *
	 * @since 4.2.1
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Version
	 *
	 * @since 4.2.1
	 * @var $version
	 */
	public $version;

	/**
	 * Primary Key
	 *
	 * @since 4.2.1
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists constructor.
	 *
	 * @since 4.2.1
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'ig_actions';

		$this->version = '1.0';

	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 */
	public function get_columns() {
		return array(
			'contact_id'   => '%d',
			'message_id'   => '%d',
			'campaign_id'  => '%d',
			'type'         => '%d',
			'count'        => '%d',
			'link_id'      => '%d',
			'list_id'      => '%d',
			'ip'           => '%s',
			'country'      => '%s',
			'device'       => '%s',
			'browser'      => '%s',
			'email_client' => '%s',
			'os'           => '%s',
			'created_at'   => '%d',
			'updated_at'   => '%d',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.2.1
	 */
	public function get_column_defaults() {
		return array(
			'contact_id'   => null,
			'message_id'   => null,
			'campaign_id'  => null,
			'type'         => 0,
			'count'        => 0,
			'link_id'      => 0,
			'list_id'      => 0,
			'ip'           => '',
			'country'      => '',
			'device'       => '',
			'browser'      => '',
			'email_client' => '',
			'os'           => '',
			'created_at'   => ig_es_get_current_gmt_timestamp(),
			'updated_at'   => ig_es_get_current_gmt_timestamp(),
		);
	}

	/**
	 * Track action
	 *
	 * @param $args
	 * @param bool $explicit
	 *
	 * @return bool
	 *
	 * @since 4.2.4
	 */
	public function add( $args, $explicit = true ) {

		global $wpbd;

		$ig_actions_table = IG_ACTIONS_TABLE;

		$args_keys = array_keys( $args );

		$args_keys_str = implode( ', ', $args_keys );

		$sql = "INSERT INTO $ig_actions_table ($args_keys_str)";

		$args_values_array = array();
		if ( is_array( $args['contact_id'] ) ) {
			$contact_ids = $args['contact_id'];
			foreach ( $contact_ids as $contact_id ) {
				$args['contact_id'] = $contact_id;

				$args_values = array_values( $args );
				$args_values = esc_sql( $args_values );

				$args_values_array[] = $this->prepare_for_in_query( $args_values );
			}
		} else {
			$args_values = array_values( $args );
			$args_values = esc_sql( $args_values );

			$args_values_array[] = $this->prepare_for_in_query( $args_values );
		}

		$sql .= ' VALUES ( ' . implode( '), (', $args_values_array ) . ' )';
		$sql .= ' ON DUPLICATE KEY UPDATE';
		$sql .= ( $explicit ) ? $wpbd->prepare( ' created_at = created_at, count = count+1, updated_at = %d, ip = %s, country = %s, browser = %s, device = %s, os = %s, email_client = %s', ig_es_get_current_gmt_timestamp(), $args['ip'], $args['country'], $args['browser'], $args['device'], $args['os'], $args['email_client'] ) : ' count = values(count)';

		$result = $wpbd->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false !== $result ) {
			return true;
		}

		return false;
	}

	/**
	 * Get total contacts who have clicked links in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.3.2
	 */
	public function get_total_contacts_clicks_links( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_LINK_CLICK,
		);

		$total_contacts_clicked = 0;
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days                   = esc_sql( $days );
				$args[]                 = $days;
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days                   = esc_sql( $days );
				$args[]                 = $days;
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}

		return $total_contacts_clicked;
	}

	/**
	 * Get total contacts who have unsubscribed in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 */
	public function get_total_contact_lost( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_CONTACT_UNSUBSCRIBE,
		);

		$total_emails_unsubscribed = 0;
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days                      = esc_sql( $days );
				$args[]                    = $days;
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days                      = esc_sql( $days );
				$args[]                    = $days;
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}

		return $total_emails_unsubscribed;
	}



	/**
	 * Get total contacts who have opened message in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.4.0
	 */
	public function get_total_contacts_opened_message( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_MESSAGE_OPEN,
		);

		$total_emails_opened = 0;
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days                = esc_sql( $days );
				$args[]              = $days;
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days                = esc_sql( $days );
				$args[]              = $days;
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}

		return $total_emails_opened;
	}

	/**
	 * Get total emails sent in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.4.0
	 */
	public function get_total_emails_sent( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_MESSAGE_SENT,
		);

		$total_emails_sent = 0;
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days              = esc_sql( $days );
				$args[]            = $days;
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days              = esc_sql( $days );
				$args[]            = $days;
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}

		return $total_emails_sent;
	}

	/**
	 * Get contact count based on campaign_id and type
	 *
	 * @return string|null
	 *
	 * @since 4.5.2
	 */
	public function get_count_based_on_id_type( $campaign_id, $message_id, $type, $distinct = true ) {
		global $wpbd;

		$args = array();

		$args[] = $campaign_id;
		$args[] = $message_id;
		$args[] = $type;

		$count = 0;
		if ( $distinct ) {
			$query = $wpbd->prepare(
				"SELECT COUNT(DISTINCT(`contact_id`)) as count FROM {$wpbd->prefix}ig_actions WHERE `campaign_id`= %d AND `message_id`= %d AND `type` = %d",
				$args
			);
		} else {
			$query = $wpbd->prepare(
				"SELECT  COUNT(`contact_id`) as count FROM {$wpbd->prefix}ig_actions WHERE `campaign_id`= %d  AND `message_id`= %d AND `type` = %d",
				$args
			);
		}

		$cache_key       = ES_Cache::generate_key( $query );
		$exists_in_cache = ES_Cache::is_exists( $cache_key, 'query' );
		if ( ! $exists_in_cache ) {
			$count = $wpbd->get_var(
				$query
			);
			ES_Cache::set( $cache_key, $count, 'query' );
		} else {
			$count = ES_Cache::get( $cache_key, 'query' );
		}

		return $count;
	}

	/**
	 * Get Last opened at based on contact_ids
	 *
	 * @param array $contact_ids
	 *
	 * @return array
	 *
	 * @since 4.6.5
	 */
	public function get_last_opened_of_contact_ids( $contact_ids = '', $filter = false ) {

		global $wpbd;

		if ( empty( $contact_ids ) ) {
			return array();
		}

		$contact_ids_str = implode( ',', $contact_ids );

		$result = $wpbd->get_results( $wpbd->prepare( "SELECT contact_id, MAX(created_at) as last_opened_at FROM {$wpbd->prefix}ig_actions WHERE contact_id IN ({$contact_ids_str}) AND type = %d  GROUP BY contact_id", IG_MESSAGE_OPEN ), ARRAY_A );

		if ( $filter ) {
			$last_opened_at = array_column( $result, 'last_opened_at', 'contact_id' );
			foreach ( $last_opened_at as $contact_id => $timestamp ) {
				$convert_date_format           = get_option( 'date_format' );
				$convert_time_format           = get_option( 'time_format' );
				$last_opened_at[ $contact_id ] = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), $convert_date_format . ' ' . $convert_time_format );
			}
			return $last_opened_at;
		}
		return $result;
	}
}
