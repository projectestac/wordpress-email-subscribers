<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Queue' ) ) {
	/**
	 * Class ES_Queue
	 *
	 * Manage Mailing Queue
	 *
	 * Actions
	 * ig_es_time_based_campaign - Immediately, Daily, Weekly, Monthly
	 * ig_es_contact_insert - Time after contact subscribe
	 * ig_es_campaign_open - Time after specific campaign open
	 *
	 * @since 4.2.0
	 */
	class ES_Queue {
		/**
		 * ES_DB_Queue object
		 *
		 * @since 4.2.1
		 * @var $db
		 */
		protected $db;

		/**
		 * ES_Queue constructor.
		 *
		 * @since 4.2.0
		 */
		public function __construct() {

			$this->db = new ES_DB_Queue();

			add_action( 'plugins_loaded', array( &$this, 'init' ), 1 );

			add_action( 'ig_es_before_message_send', array( &$this, 'set_sending_status' ), 10, 3 );
			// add_action( 'ig_es_email_sending_error', array( &$this, 'set_status_in_queue' ), 10, 4 );
			add_action( 'ig_es_message_sent', array( &$this, 'set_sent_status' ), 10, 3 );
			add_action( 'ig_es_message_sent', array( &$this, 'update_email_sent_count' ), 10, 3 );
			add_action( 'ig_es_message_failed', array( &$this, 'set_failed_status' ), 10, 3 );
			add_action( 'ig_es_contact_unsubscribe', array( &$this, 'delete_contact_queued_emails' ), 10, 4 );
			add_action( 'ig_es_admin_contact_unsubscribe', array( &$this, 'delete_contact_queued_emails' ), 10, 4 );

			// Ajax handler for running action scheduler task.
			add_action( 'wp_ajax_ig_es_run_action_scheduler_task', array( 'IG_ES_Background_Process_Helper', 'run_action_scheduler_task' ) );
			add_action( 'wp_ajax_nopriv_ig_es_run_action_scheduler_task', array( 'IG_ES_Background_Process_Helper', 'run_action_scheduler_task' ) );

			// Ajax handler for triggering email queue sending.
			add_action( 'wp_ajax_ig_es_trigger_mailing_queue_sending', array( $this, 'trigger_mailing_queue_sending' ) );
			add_action( 'wp_ajax_nopriv_ig_es_trigger_mailing_queue_sending', array( $this, 'trigger_mailing_queue_sending' ) );
		}

		/**
		 * Initialize Queue
		 *
		 * @since 4.2.0
		 */
		public function init() {
			add_action( 'ig_es_cron_auto_responder', array( &$this, 'queue_time_based_campaigns' ), 30 );
			add_action( 'ig_es_cron_auto_responder', array( &$this, 'queue_sequences' ), 30 );

			add_action( 'ig_es_cron_worker', array( &$this, 'process_campaigns' ), 10 );
			add_action( 'ig_es_cron_worker', array( &$this, 'process_queue' ), 30 );
			// Background request to process ig_queue.
			add_action( 'wp_ajax_ig_es_process_queue', array( &$this, 'process_queue' ) );
		}

		/**
		 * Queue valid time based campaigns
		 *
		 * @since 4.2.0
		 */
		public function queue_time_based_campaigns( $campaign_id = 0, $force = false ) {

			/**
			 * Steps
			 *  1. Fetch all active campaigns
			 *  2. Loop over through and based on matched condition put campaign into mailing_queue table
			 *  3. And also insert subscribers for respective campaign into sending_queue table
			 *  4. Call es cron to send emails from queue
			 */
			static $campaigns_to_process;

			if ( ! isset( $campaigns_to_process ) ) {
				$campaigns_to_process = array();
			}

			if ( $campaign_id ) {
				$campaign  = ES()->campaigns_db->get_campaign_by_id( $campaign_id );
				$campaigns = array( $campaign );
			} else {
				$campaigns = ES()->campaigns_db->get_active_campaigns( IG_CAMPAIGN_TYPE_POST_DIGEST );
			}

			if ( empty( $campaigns ) ) {
				return;
			}

			$now = time();

			foreach ( $campaigns as $campaign ) {

				if ( in_array( $campaign['id'], $campaigns_to_process ) && ! $force ) {
					continue;
				}

				$campaign_id = $campaign['id'];

				$campaigns_to_process[] = $campaign_id;

				$meta = maybe_unserialize( $campaign['meta'] );

				$rules = ! empty( $meta['rules'] ) ? $meta['rules'] : array();

				if ( ! empty( $rules ) ) {

					$action = ! empty( $rules['action'] ) ? $rules['action'] : '';

					if ( 'ig_es_time_based_campaign' != $action ) {
						continue;
					}

					$start_time = ! empty( $meta['next_run'] ) ? $meta['next_run'] : 0;

					if ( ! empty( $start_time ) ) {

						$meta_data = array();
						$scheduled = ! empty( $meta['scheduled'] ) ? $meta['scheduled'] : 0;
						$delay     = $start_time - $now;

						// seconds the campaign should created before the actual send time.
						$time_created_before = 3600;

						// Is it a good time to do now?
						$do_it = $delay <= $time_created_before;

						// If current time is within an hour range or has already passed the scheduled time(negative value fot $delay) e.g. for 11 A.M. post digest, do it if current time is between 10 A.M - 11 A.M. or it is after 11 A.M.
						if ( $do_it ) {

							// By default do not schedule
							if ( ! $scheduled ) {

								$campaign['start_at'] = gmdate( 'Y-m-d H:i:s', $start_time );

								$post_ids = array();
								if ( class_exists( 'ES_Post_Digest' ) ) {
									$post_ids = ES_Post_Digest::get_post_id_for_post_digest( $campaign_id );
								}

								// Proceed only if we have posts for digest.
								if ( ! empty( $post_ids ) ) {
									$list_id = $campaign['list_ids'];
									if ( ! empty( $list_id ) ) {
										$list_id = explode( ',', $list_id );
									}

									// Do we have active subscribers?
									$contacts       = ES()->contacts_db->get_active_contacts_by_list_id( $list_id );
									$total_contacts = count( $contacts );

									// Create a new mailing queue using this campaign
									$result = $this->add_campaign_to_queue( $campaign, $total_contacts );

									if ( ! empty( $result['id'] ) ) {

										$mailing_queue_id = $result['id'];

										if ( ! empty( $mailing_queue_id ) ) {
											$mailing_queue_hash = $result['hash'];
											ES_DB_Sending_Queue::do_insert_from_contacts_table( $mailing_queue_id, $mailing_queue_hash, $campaign_id, $list_id );
										}
									}
								}
							}

							$time_frame = ! empty( $rules['time_frame'] ) ? $rules['time_frame'] : '';

							if ( 'immediately' !== $time_frame ) {

								$data = array(
									'utc_start'   => $start_time,
									'interval'    => $rules['interval'],
									'time_frame'  => $time_frame,
									'time_of_day' => $rules['time_of_day'],
									'weekdays'    => $rules['weekdays'],
									'force'       => true,
								);

								// Get the next run time.
								$next_run = ig_es_get_next_future_schedule_date( $data );

								$meta_data['next_run'] = $next_run;
								if ( $next_run == $start_time ) {
									$meta_data['scheduled'] = 1;
								} else {
									$meta_data['scheduled'] = 0;
								}
							} else {
								$meta_data['scheduled'] = 1;
							}
						} else {
							// If current time is not within an hour range, then mark it as unschedule so that when current time comes in an hour range before next scheduled time, reports can be queued and campaign can be set as scheduled.
							$meta_data['scheduled'] = 0;
						}

						ES()->campaigns_db->update_campaign_meta( $campaign_id, $meta_data );
					}
				}
			}

		}

		/**
		 * Queue Valid Sequence messages
		 *
		 * @since 4.2.1
		 */
		public function queue_sequences( $campaign_id = 0, $force = false ) {
			/**
			 * Steps
			 *  1. Fetch all active Sequence Message
			 *  2. Loop over through and based on matched condition put campaign into mailing_queue table if not already exists
			 *  3. And also insert subscribers for respective campaign into snding_queue_table
			 */
			static $campaigns_to_process;

			if ( ! isset( $campaigns_to_process ) ) {
				$campaigns_to_process = array();
			}

			if ( $campaign_id ) {
				$campaign  = ES()->campaigns_db->get_campaign_by_id( $campaign_id );
				$campaigns = array( $campaign );
			} else {
				$campaigns = ES()->campaigns_db->get_active_campaigns( IG_CAMPAIGN_TYPE_SEQUENCE_MESSAGE );
			}

			if ( empty( $campaigns ) ) {
				return;
			}

			$now = time();
		
			foreach ( $campaigns as $campaign ) {

				if ( in_array( $campaign['id'], $campaigns_to_process ) && ! $force ) {
					continue;
				}

				$campaign_id = $campaign['id'];

				$campaigns_to_process[] = $campaign_id;

				$meta = maybe_unserialize( $campaign['meta'] );

				$rules = ! empty( $meta['rules'] ) ? $meta['rules'] : array();

				if ( ! empty( $rules ) ) {

					$action = ! empty( $rules['action'] ) ? $rules['action'] : '';

					if ( 'ig_es_contact_insert' != $action ) {
						continue;
					}
					
					$delay_unit   = $rules['unit'];
					$delay_amount = $rules['amount'];

					if ( 'immediately' === $delay_unit ) {
						$offset       = '0 HOUR';
						$grace_period = 15 * MINUTE_IN_SECONDS;
					} else {
						$offset = (int) $delay_amount . ' ' . strtoupper( $delay_unit );
						if ( 'hour' === $delay_unit ) {
							$grace_period = 15 * MINUTE_IN_SECONDS;
						} elseif ( 'day' === $delay_unit ) {
							$grace_period = 1 * HOUR_IN_SECONDS;
						} elseif ( 'week' === $delay_unit ) {
							$grace_period = 1 * HOUR_IN_SECONDS;
						}
					}

					$list_ids = $campaign['list_ids'];

					$campaign = ES()->campaigns_db->get( $campaign_id );

					$conditions = array();
					if ( ! empty( $campaign ) && ! empty( $campaign['meta'] ) ) {
						$campaign_meta = maybe_unserialize( $campaign['meta'] );
						if ( ! empty( $campaign_meta['list_conditions'] ) ) {
							$conditions = $campaign_meta['list_conditions'];
						}
					}

					$conditions = ! empty( $meta['list_conditions'] ) ? $meta['list_conditions'] : array();
					$end_time   = gmdate( 'Y-m-d H:i:s', $now );
					$query_args = array(
						'select'        => array(
							'lists_subscribers.contact_id AS contact_id',
							// Since UNIX_TIMESTAMP expect date to be in session time zone and subscribed_at is already in UTC, we are first converting subscribed_at date from UTC time to session time and then passing it to .
							"UNIX_TIMESTAMP ( CONVERT_TZ( lists_subscribers.subscribed_at + INTERVAL $offset, '+0:00', @@session.time_zone ) ) AS timestamp",
						),
						'sent__not_in'  => array( $campaign_id ),
						'queue__not_in' => array( $campaign_id ),
						'lists'         => $list_ids,
						'conditions'    => $conditions,
						'having'        => array( "timestamp <= UNIX_TIMESTAMP ( CONVERT_TZ( '$end_time', '+0:00', @@session.time_zone ) )" ),
						'orderby'       => array( 'timestamp' ),
						'groupby'       => 'lists_subscribers.contact_id',
						'status'		=> 'subscribed',
						'subscriber_status'		=> array( 'verified' ),
					);

					if ( $grace_period ) {
						$start_time             = gmdate( 'Y-m-d H:i:s', $now - $grace_period );
						$query_args['having'][] = "timestamp >= UNIX_TIMESTAMP ( CONVERT_TZ( '$start_time', '+0:00', @@session.time_zone ) )";
					}

					$query   = new IG_ES_Subscribers_Query();
					$results = $query->run( $query_args );

					if ( ! empty( $results ) ) {

						$contact_ids = wp_list_pluck( $results, 'contact_id' );
						$timestamps  = wp_list_pluck( $results, 'timestamp' );

						/**
						 * Check whether campaign is already exists in mailing_queue table with $campaign_id
						 * If Exists, Get the mailing_queue_id & hash
						 * If Not, create new and get the mailing_queue_id & hash
						 */
						$total_contacts = count( $contact_ids );
						if ( $total_contacts > 0 ) {

							$this->bulk_add( $campaign_id, $contact_ids, $timestamps, 15 );

							$timestamp = min( $timestamps );

							// handle instant delivery
							if ( $timestamp - time() <= 0 ) {
								wp_schedule_single_event( $timestamp, 'ig_es_cron_worker', array( $campaign_id ) );
							}
						}
					}
				}
			}

		}

		/**
		 * Add campaign to queue
		 *
		 * @param $campaign
		 *
		 * @return int | array
		 *
		 * @since 4.2.0
		 */
		public function add_campaign_to_queue( $campaign ) {

			$campaign_id = $campaign['id'];
			$template_id = $campaign['base_template_id'];
			$template    = get_post( $template_id );
			$subject     = $campaign['subject'];
			$content     = $campaign['body'];
			$content     = ES_Common::es_process_template_body( $content, $template_id );

			$guid = ES_Common::generate_guid( 6 );

			$data = array(
				'hash'        => $guid,
				'campaign_id' => $campaign_id,
				'subject'     => $subject,
				'body'        => $content,
				'count'       => 0,
				'status'      => '',
				'start_at'    => ! empty( $campaign['start_at'] ) ? $campaign['start_at'] : '',
				'finish_at'   => '',
				'created_at'  => ig_get_current_date_time(),
				'updated_at'  => ig_get_current_date_time(),
				'meta'        => maybe_serialize( array( 'type' => $campaign['type'] ) ),
			);

			$queue_id = ES_DB_Mailing_Queue::add_notification( $data );

			return array(
				'hash' => $guid,
				'id'   => $queue_id,
			);
		}

		/**
		 * Add contacts into sending_queue table
		 *
		 * @param $campaign_id
		 * @param $guid
		 * @param $queue_id
		 * @param $contacts
		 *
		 * @since 4.2.1
		 */
		public function add_contacts_to_queue( $campaign_id, $guid, $queue_id, $contacts ) {

			$delivery_data                     = array();
			$delivery_data['hash']             = $guid;
			$delivery_data['subscribers']      = $contacts;
			$delivery_data['campaign_id']      = $campaign_id;
			$delivery_data['mailing_queue_id'] = $queue_id;

			ES_DB_Sending_Queue::do_batch_insert( $delivery_data );
		}

		/**
		 * Bulk Add contacts into queue
		 *
		 * @param $campaign_id
		 * @param $subscribers
		 * @param null        $timestamp
		 * @param int         $priority
		 * @param bool        $clear
		 * @param bool        $ignore_status
		 * @param bool        $reset
		 * @param bool        $options
		 * @param bool        $tags
		 *
		 * @return bool|void
		 *
		 * @since 4.2.1
		 */
		public function bulk_add( $campaign_id, $subscribers, $timestamp = null, $priority = 10, $clear = false, $ignore_status = false, $reset = false, $options = false, $tags = false ) {

			global $wpdb, $wpbd;

			if ( $clear ) {
				$this->clear( $campaign_id, $subscribers );
			}

			if ( empty( $subscribers ) ) {
				return;
			}

			if ( is_null( $timestamp ) ) {
				$timestamp = time();
			}

			$timestamps = ! is_array( $timestamp )
				? array_fill( 0, count( $subscribers ), $timestamp )
				: $timestamp;

			$now = time();

			$campaign_id = (int) $campaign_id;
			$subscribers = array_filter( $subscribers, 'is_numeric' );

			if ( empty( $subscribers ) ) {
				return true;
			}

			$inserts = array();

			foreach ( $subscribers as $i => $subscriber_id ) {
				if ( isset( $tags[ $subscriber_id ] ) ) {
					$subscriber_tags = maybe_serialize( $tags[ $subscriber_id ] );
				} else {
					$subscriber_tags = '';
				}

				if ( isset( $options[ $subscriber_id ] ) ) {
					$subscriber_options = maybe_serialize( $options[ $subscriber_id ] );
				} else {
					$subscriber_options = '';
				}
				$inserts[] = "($subscriber_id,$campaign_id,$now," . $timestamps[ $i ] . ",$priority,1,'$ignore_status','$subscriber_options','$subscriber_tags')";
			}

			$chunks = array_chunk( $inserts, 1000 );

			$success = true;

			foreach ( $chunks as $insert ) {

				$sql = "INSERT INTO {$wpdb->prefix}ig_queue (contact_id, campaign_id, added, timestamp, priority, count, ignore_status, options, tags) VALUES";

				$sql .= ' ' . implode( ',', $insert );

				$sql .= ' ON DUPLICATE KEY UPDATE timestamp = values(timestamp), ignore_status = values(ignore_status), options = values(options), tags = values(tags)';
				if ( $reset ) {
					$sql .= ', sent_at = 0';
				}

				// ES()->logger->info( 'Adding Bulk SQL: ' . $sql );

				$success = $success && false !== $wpbd->query( $sql );

			}

			return $success;

		}

		/**
		 * Clear queue which are not assigned to any campaign
		 *
		 * @param null  $campaign_id
		 * @param array $subscribers
		 *
		 * @return bool
		 *
		 * @since 4.2.1
		 */
		public function clear( $campaign_id = null, $subscribers = array() ) {

			global $wpdb, $wpbd;

			$campaign_id = (int) $campaign_id;
			$subscribers = array_filter( $subscribers, 'is_numeric' );

			if ( empty( $subscribers ) ) {
				$subscribers = array( - 1 );
			}

			$sql = "DELETE queue FROM {$wpdb->prefix}ig_queue AS queue WHERE queue.sent = 0 AND queue.contact_id NOT IN (" . implode( ',', $subscribers ) . ')';
			if ( ! is_null( $campaign_id ) ) {
				$sql .= $wpdb->prepare( ' AND queue.campaign_id = %d', $campaign_id );
			}

			return false !== $wpbd->query( $sql );

		}

		/**
		 * Process Queue
		 *
		 * @since 4.2.1
		 */
		public function process_queue() {

			global $wpdb;

			if ( ES()->cron->should_unlock() ) {
				ES()->cron->unlock();
			}

			ES()->cron->set_last_hit();

			$email_sending_limit = ES()->mailer->get_total_emails_send_now();

			if ( $email_sending_limit > 0 ) {

				$micro_time = microtime( true );

				// ES()->logger->info( 'Process Queue:' );
				// ES()->logger->info( 'SQL: ' . $sql );

				$notifications = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT queue.campaign_id, queue.contact_id, queue.count AS _count, queue.requeued AS _requeued, queue.options AS _options, queue.tags AS _tags, queue.priority AS _priority
						 FROM {$wpdb->prefix}ig_queue AS queue
						 LEFT JOIN {$wpdb->prefix}ig_campaigns AS campaigns ON campaigns.id = queue.campaign_id
						 WHERE queue.timestamp <= %d AND queue.sent_at = 0
						 AND (campaigns.status = 1 OR queue.ignore_status = 1)
						 ORDER BY queue.priority DESC",
						(int) $micro_time
					),
					ARRAY_A
				);

				$batch_start_time = time();

				if ( is_array( $notifications ) && count( $notifications ) > 0 ) {
					$campaigns_notifications = array();
					$contact_ids             = array();
					foreach ( $notifications as $notification ) {
						$campaigns_notifications[ $notification['campaign_id'] ][] = $notification;

						$contact_ids[] = $notification['contact_id'];
					}

					// We need unique ids
					$contact_ids = array_unique( $contact_ids );

					$contacts = ES()->contacts_db->get_details_by_ids( $contact_ids );

					foreach ( $campaigns_notifications as $campaign_id => $notifications ) {

						if ( ! empty( $campaign_id ) ) {
							$campaign = ES()->campaigns_db->get( $campaign_id );

							if ( ! empty( $campaign ) ) {

								$content = $campaign['body'];

								$subject = $campaign['subject'];
							}
						}

						foreach ( $notifications as $notification ) {

							$contact_id = $notification['contact_id'];

							if ( ! empty( $contacts[ $contact_id ] ) ) {

								$first_name = $contacts[ $contact_id ]['first_name'];
								$last_name  = $contacts[ $contact_id ]['last_name'];
								$hash       = $contacts[ $contact_id ]['hash'];
								$email      = $contacts[ $contact_id ]['email'];
								$name       = ES_Common::prepare_name_from_first_name_last_name( $first_name, $last_name );

								$merge_tags = array(
									'name'        => $name,
									'first_name'  => $first_name,
									'last_name'   => $last_name,
									'email'       => $email,
									'guid'        => $hash,
									'dbid'        => $contact_id,
									'message_id'  => 0,
									'campaign_id' => $campaign_id,
								);

								$notification_options = maybe_unserialize( $notification['_options'] );
								$notification_type    = ! empty( $notification_options['type'] ) ? $notification_options['type'] : '';
								if ( 'optin_confirmation' === $notification_type ) {
									$merge_tags['contact_id'] = $contact_id;
									ES()->mailer->send_double_optin_email( $email, $merge_tags );
								} elseif ( 'optin_welcome_email' === $notification_type ) {
									$merge_tags['contact_id'] = $contact_id;
									ES()->mailer->send_welcome_email( $email, $merge_tags );
								} else {
									// Enable unsubscribe link and tracking pixel
									ES()->mailer->add_unsubscribe_link = true;
									ES()->mailer->add_tracking_pixel   = true;
									ES()->mailer->send( $subject, $content, $email, $merge_tags );
								}

								do_action( 'ig_es_message_sent', $contact_id, $campaign_id, 0 );

								$email_sending_limit--;

								// Email Sent now delete from queue now.
								$this->db->delete_from_queue( $campaign_id, $contact_id );
							}

							// Check if email sending limit or time limit or memory limit has been reached.
							if ( $email_sending_limit <= 0 || IG_ES_Background_Process_Helper::time_exceeded( $batch_start_time, 0.8 ) || IG_ES_Background_Process_Helper::memory_exceeded() ) {
								break 2; // Break inner and outer loop
							}
						}
					}
				}
			}

		}

		/**
		 * Process Campaigns and send notifications
		 *
		 * @since 4.3.1
		 */
		public function process_campaigns() {

			if ( ES()->cron->should_unlock() ) {
				ES()->cron->unlock();
			}

			ES()->cron->set_last_hit();

			/**
			 * - Get GUID from ig_es_mailing_queue table which are in queue
			 * - Get contacts from the ig_es_sending_queue table based on fetched guid
			 * - Prepare email content
			 * - Send emails based on fetched contacts
			 * - Update status in ig_es_mailing_queue table
			 * - Update status in ig_es_sending_queue table
			 */
			$es_c_croncount = ES()->mailer->get_total_emails_send_now();

			if ( $es_c_croncount > 0 ) {

				// Get GUID from sentdetails report which are in queue
				$campaign_hash = ig_es_get_request_data( 'campaign_hash' );

				$notification      = ES_DB_Mailing_Queue::get_notification_to_be_sent( $campaign_hash );
				$notification_guid = isset( $notification['hash'] ) ? $notification['hash'] : null;
				$message_id        = isset( $notification['id'] ) ? $notification['id'] : 0;
				$campaign_id       = isset( $notification['campaign_id'] ) ? $notification['campaign_id'] : 0;

				if ( ! is_null( $notification_guid ) ) {

					$cron_job = 'ig_es_cron_worker';

					$cron_job_data = array(
						'campaign_id' => $campaign_id,
					);

					// Check if admin has forcefully triggered the email sending.
					$triggered_by_admin = ig_es_get_request_data( 'self', 0 );

					// If admin has forcefully triggered the email sending, then unlock the cron job.
					$force_unlock = '1' === $triggered_by_admin ? true : false;

					if ( $this->should_unlock_cron_job( $cron_job, $force_unlock ) ) {
						$this->unlock_cron_job( $cron_job );
					}

					// Check if cron job is not already locked before sending campaign emails
					if ( ! $this->is_cron_job_locked( $cron_job ) ) {

						// Try to lock cron job.
						$locking_status = $this->lock_cron_job( $cron_job, $cron_job_data );
						if ( 'locked' === $locking_status ) {

							register_shutdown_function( array( $this, 'unlock_cron_job' ), $cron_job );

							$campaign_type = '';
							if ( ! empty( $campaign_id ) ) {
								$campaign_type = ES()->campaigns_db->get_campaign_type_by_id( $campaign_id );
							}

							if ( 'newsletter' === $campaign_type ) {
								ES()->campaigns_db->update_status( $campaign_id, IG_ES_CAMPAIGN_STATUS_QUEUED );
							}

							// Set status to Sending only if it in the queued status currently.
							if ( 'In Queue' === $notification['status'] ) {
								ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sending' );
							}

							// Sync mailing queue content with the related campaign.
							$notification = ES_DB_Mailing_Queue::sync_content( $notification );

							// Get subscribers from the sending_queue table based on fetched guid
							$emails_data  = ES_DB_Sending_Queue::get_emails_to_be_sent_by_hash( $notification_guid, $es_c_croncount );
							$total_emails = count( $emails_data );
							// Found Subscribers to send notification?
							if ( $total_emails > 0 ) {
								$emails = array_column( $emails_data, 'email' );

								$merge_tags = array(
									'guid'        => $notification_guid,
									'message_id'  => $message_id,
									'campaign_id' => $campaign_id,
								);

								$subject = $notification['subject'];
								$content = $notification['body'];

								// $content = utf8_encode( $content );
								ES()->mailer->send( $subject, $content, $emails, $merge_tags );

								$total_remaining_emails      = ES_DB_Sending_Queue::get_total_emails_to_be_sent_by_hash( $notification_guid );
								$remaining_emails_to_be_sent = ES_DB_Sending_Queue::get_total_emails_to_be_sent();

								// No emails left for the $notification_guid??? Send admin notification for the
								// Completion of a job
								if ( 0 == $total_remaining_emails ) {
									ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sent' );

									if ( 'newsletter' === $campaign_type ) {
										ES()->campaigns_db->update_status( $campaign_id, IG_ES_CAMPAIGN_STATUS_FINISHED );
									} elseif ( 'post_digest' === $campaign_type ) {
										$campaign_meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
										if ( ! empty( $campaign_meta['post_ids'] ) ) {
											// Empty the post ids since they have already been sent in this campaign notification.
											$campaign_meta['post_ids'] = array();
											ES()->campaigns_db->update_campaign_meta( $campaign_id, $campaign_meta );
										}
									}

									do_action( 'ig_es_campaign_sent', $notification_guid );
								}

								
								$campaign_failed = did_action( 'ig_es_message_failed' );
								if ( $campaign_failed ) {
									$pending_statuses = array( 
										IG_ES_SENDING_QUEUE_STATUS_QUEUED,
										IG_ES_SENDING_QUEUE_STATUS_SENDING 
									);
									$pending_emails = ES_DB_Sending_Queue::get_total_emails_to_be_sent_by_hash( $notification_guid, $pending_statuses );	
									if ( empty( $pending_emails ) ) {
										$notification_meta = maybe_unserialize( $notification['meta'] );
										$failed_count      = isset( $notification_meta['failed_count'] ) ? $notification_meta['failed_count'] : 0;
										$failed_count++;
										$notification_meta['failed_count'] = $failed_count;
										$notification_data = array(
											'meta'    => maybe_serialize( $notification_meta ),
										);
										if ( $failed_count >= 3 ) {
											$notification_data['status'] = IG_ES_MAILING_QUEUE_STATUS_FAILED;
											do_action( 'ig_es_campaign_failed', $notification_guid );
										}
										ES_DB_Mailing_Queue::update_mailing_queue( $message_id, $notification_data );
									}									
								} elseif ( $triggered_by_admin ) {
									$notification_status = $notification['status'];
									if ( IG_ES_MAILING_QUEUE_STATUS_FAILED === $notification_status ) {
										$notification_meta = maybe_unserialize( $notification['meta'] );
										unset( $notification_meta['failed_count'] );
										$notification_data = array(
											'meta'    => maybe_serialize( $notification_meta ),
										);
										$notification_data['status'] = IG_ES_MAILING_QUEUE_STATUS_SENDING;
										ES_DB_Mailing_Queue::update_mailing_queue( $message_id, $notification_data );
									}
								}

								// TODO: Implement better solution
								set_transient( 'ig_es_total_emails_sent', $total_emails, MINUTE_IN_SECONDS );
								set_transient( 'ig_es_remaining_email_count', $remaining_emails_to_be_sent, MINUTE_IN_SECONDS );

								$response['total_emails_sent']        = $total_emails;
								$response['es_remaining_email_count'] = $remaining_emails_to_be_sent;
								$response['message']                  = 'EMAILS_SENT';
								$response['status']                   = 'SUCCESS';
								// update last cron run time
								update_option( 'ig_es_last_cron_run', time() );

							} else {
								$response['es_remaining_email_count'] = 0;
								$response['message']                  = 'EMAILS_NOT_FOUND';
								$response['status']                   = 'SUCCESS';
								ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sent' );
							}

							$this->unlock_cron_job( $cron_job );
						}
					} else {
						$response['status']  = 'ERROR';
						$response['message'] = 'CRON_LOCK_ENABLED';
					}
				} else {
					$response['es_remaining_email_count'] = 0;
					$response['message']                  = 'NOTIFICATION_NOT_FOUND';
					$response['status']                   = 'SUCCESS';
				}
			} else {
				$self                = false;
				$response['status']  = 'ERROR';
				$response['message'] = 'EMAIL_SENDING_LIMIT_EXCEEDED';
			}

		}

		/**
		 * Update Email Sent Count
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 * @param int $message_id
		 *
		 * @since 4.3.2
		 */
		public function update_email_sent_count( $contact_id = 0, $campaign_id = 0, $message_id = 0 ) {
			$sent_count = 0;
			if ( is_array( $contact_id ) ) {
				$sent_count = count( $contact_id );
			} else {
				$sent_count = 1;
			}
			if ( ! empty( $sent_count ) ) {
				ES_Common::update_total_email_sent_count( $sent_count );
			}
		}

		/**
		 * Delete contact queued emails
		 *
		 * @param int   $contact_id
		 * @param int   $message_id
		 * @param int   $campaign_id
		 * @param array $list_ids
		 *
		 * @since 4.7.6
		 */
		public function delete_contact_queued_emails( $contact_ids, $message_id = 0, $campaign_id = 0, $list_ids = array() ) {
			global $wpdb;

			// Convert to array of contact ids if not already.
			if ( ! is_array( $contact_ids ) ) {
				$contact_ids = array( $contact_ids );
			}

			if ( ! empty( $contact_ids ) ) {
				foreach ( $contact_ids as $contact_id ) {

					// Queued emails from sending_queue table.
					$sending_queue_emails = ES_DB_Sending_Queue::get_queued_emails( $contact_id );

					// Queued emails from queue table.
					$where         = $wpdb->prepare( 'contact_id = %d AND campaign_id <> 0', $contact_id );
					$queued_emails = ES()->queue_db->get_by_conditions( $where );

					// Merge sending queue table emails and queue table emails.
					$queued_emails = array_merge( $sending_queue_emails, $queued_emails );

					if ( ! empty( $queued_emails ) ) {
						foreach ( $queued_emails as $queued_email ) {
							$should_delete      = false;
							$campaign_id        = $queued_email['campaign_id'];
							$campaign_list_ids  = ES()->campaigns_db->get_list_ids( $campaign_id );
							$remaining_list_ids = array_diff( $campaign_list_ids, $list_ids );

							// True when the contact has unsubscribed from all the lists in the campaign.
							if ( empty( $remaining_list_ids ) ) {
								$should_delete = true;
							} else {
								$subscribed_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $contact_id, 'subscribed' );
								$common_lists     = array_intersect( $remaining_list_ids, $subscribed_lists );
								// True when contact isn't subscribed to the remaining campaign lists.
								if ( empty( $common_lists ) ) {
									$should_delete = true;
								}
							}

							if ( $should_delete ) {
								// If mailing_queue_id exists then email is from the sending queue table.
								if ( ! empty( $queued_email['mailing_queue_id'] ) ) {
									$mailing_queue_id = $queued_email['mailing_queue_id'];
									// Delete the contact from the sending queue and update the subscribers count in the mailing queue.
									ES_DB_Sending_Queue::delete_contacts( array( $contact_id ), $mailing_queue_id );
									ES_DB_Mailing_Queue::decrease_subscribers_count( $mailing_queue_id, 1 );
								} else {
									ES()->queue_db->delete_from_queue( $campaign_id, $contact_id );
								}
							}
						}
					}
				}
			}

		}

		/**
		 * Update email sent status in a queue
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 * @param int $message_id
		 *
		 * @since 4.3.2
		 */
		public function update_email_sent_status( $contact_id = 0, $campaign_id = 0, $message_id = 0, $status = 'Sent' ) {
			ES_DB_Sending_Queue::update_sent_status( $contact_id, $message_id, $status );
		}

		/**
		 * Set "Sending" status
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 * @param int $message_id
		 *
		 * @since 4.3.2
		 */
		public function set_sending_status( $contact_id = 0, $campaign_id = 0, $message_id = 0 ) {

			if ( 0 != $contact_id && 0 != $message_id ) {
				$sending_status = IG_ES_SENDING_QUEUE_STATUS_SENDING;
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, $sending_status );
			}
		}

		/**
		 * Set "Sent" status
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 * @param int $message_id
		 *
		 * @since 4.3.2
		 */
		public function set_sent_status( $contact_id = 0, $campaign_id = 0, $message_id = 0 ) {

			if ( 0 != $contact_id && 0 != $message_id ) {
				$sent_status = IG_ES_SENDING_QUEUE_STATUS_SENT;
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, $sent_status );
			}
		}

		/**
		 * Set "Sent" status
		 *
		 * @param int $contact_id
		 * @param int $campaign_id
		 * @param int $message_id
		 *
		 * @since 4.3.2
		 */
		public function set_failed_status( $contact_id = 0, $campaign_id = 0, $message_id = 0 ) {

			if ( 0 != $contact_id && 0 != $message_id ) {
				$failed_status = IG_ES_SENDING_QUEUE_STATUS_FAILED;
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, $failed_status );
			}
		}

		/**
		 * Set status in queue
		 *
		 * @param int   $contact_id
		 * @param int   $campaign_id
		 * @param int   $message_id
		 * @param array $response
		 *
		 * @since 4.3.3
		 */
		public function set_status_in_queue( $contact_id = 0, $campaign_id = 0, $message_id = 0, $response = array() ) {

			if ( 0 != $contact_id && 0 != $message_id ) {
				$in_queue_status = IG_ES_SENDING_QUEUE_STATUS_QUEUED;
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, $in_queue_status );
			}
		}

		/**
		 * Method to trigger email sending through 'ig_es_cron_worker' cron worker.
		 *
		 * @since 4.6.4
		 */
		public function trigger_mailing_queue_sending() {

			// Call cron action only when it is not locked.
			if ( ! ES()->cron->is_locked() ) {

				// Start processing of campaigns which are scheduled for current date time.
				do_action( 'ig_es_cron_worker' );
			}
		}

		/**
		 * Method to set locking options for current cron job
		 *
		 * @param string $cron_job Job name
		 * @param array  $cron_job_data Cron job data
		 *
		 * @since 4.6.4
		 */
		public function lock_cron_job( $cron_job = '', $cron_job_data = array() ) {

			$es_cron_jobs = ES()->cron->get_cron_jobs_list();

			$locking_status = '';

			if ( in_array( $cron_job, $es_cron_jobs, true ) ) {

				$locked_cron_job_data = get_option( $cron_job . '_locked_data', false );
				if ( ! empty( $locked_cron_job_data ) ) {
					$locking_status = 'already_locked';
				} else {
					$locked_cron_job_data = array(
						'locked_at' => time(),
						'data'      => $cron_job_data,
					);
					$job_locked           = update_option( $cron_job . '_locked_data', $locked_cron_job_data, false );
					if ( $job_locked ) {
						$locking_status = 'locked';
					} else {
						$locking_status = 'failed';
					}
				}
			}

			return $locking_status;
		}

		/**
		 * Method to set locking options for current cron job
		 *
		 * @param string $cron_job Cron Job name.
		 *
		 * @return bool $cron_job_locked Is cron job locked.
		 *
		 * @since 4.6.4
		 */
		public function is_cron_job_locked( $cron_job = '' ) {

			$es_cron_jobs = ES()->cron->get_cron_jobs_list();

			$cron_job_locked = false;

			if ( in_array( $cron_job, $es_cron_jobs, true ) ) {

				$locked_cron_job_data = get_option( $cron_job . '_locked_data', false );
				if ( ! empty( $locked_cron_job_data ) ) {
					$cron_job_locked = true;
				}
			}

			return $cron_job_locked;
		}

		/**
		 * Method to delete locking options for current cron job
		 *
		 * @param string $cron_job Job name

		 * @return string $unlocking_status Cron job unlocking status
		 *
		 * @since 4.6.4
		 */
		public function unlock_cron_job( $cron_job = '' ) {

			$es_cron_jobs = ES()->cron->get_cron_jobs_list();

			$unlocking_status = '';

			if ( in_array( $cron_job, $es_cron_jobs, true ) ) {
				$job_unlocked = delete_option( $cron_job . '_locked_data' );
				if ( $job_unlocked ) {
					$unlocking_status = 'unlocked';
				} else {
					$unlocking_status = 'failed';
				}
			}

			return $unlocking_status;
		}

		/**
		 * Should Unlock Cron?
		 *
		 * @param string $cron_job Cron job name
		 * @param bool   $force Should unlock the cron job forcefully.
		 *
		 * @return bool $should_unlock Should unlock the cron.
		 *
		 * @since 4.6.4
		 */
		public function should_unlock_cron_job( $cron_job = '', $force = false ) {

			$should_unlock = false;

			$es_cron_jobs = ES()->cron->get_cron_jobs_list();

			if ( in_array( $cron_job, $es_cron_jobs, true ) ) {

				if ( $force ) {
					$should_unlock = true;
				} else {
					$locked_cron_job_data = get_option( $cron_job . '_locked_data', false );
					if ( ! empty( $locked_cron_job_data ) ) {
						$locked_at = $locked_cron_job_data['locked_at'];

						$time_lapsed = time() - $locked_at;

						// Since maximum allowed cron execution duration is always equal to the set cron interval, check if time lapsed is more than the cron interval.
						$should_unlock = $time_lapsed > ES()->cron->get_cron_interval();
					}
				}
			}

			return $should_unlock;
		}
	}
}
