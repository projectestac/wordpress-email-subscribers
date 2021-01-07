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
			
			// Action scheduler action to add subscribers to sending_queue table in background. Called through Action Scheduler library.
			add_action( 'ig_es_add_subscribers_to_sending_queue', array( &$this, 'add_subscribers_to_sending_queue' ) );
			
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
									$list_id = explode( ',', $list_id );

									// Do we have active subscribers?
									$contacts       = ES()->contacts_db->get_active_contacts_by_list_id( $list_id );
									$total_contacts = count( $contacts );

									if ( $total_contacts > 0 ) {

										// Create a new mailing queue using this campaign
										$result = $this->add_campaign_to_queue( $campaign, $total_contacts );

										if ( is_array( $result ) ) {

											$mailing_queue_id = $result['id'];
											
											if ( ! empty( $mailing_queue_id ) ) {
												$action_args = array(
													'mailing_queue_id' => $mailing_queue_id,
													'list_ids'         => $list_id,
												);
												IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_add_subscribers_to_sending_queue', $action_args );
											}
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
			global $wpbd;
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

				// ES()->logger->info( 'Rules: ' . print_r( $rules, true ) );

				if ( ! empty( $rules ) ) {

					$action = ! empty( $rules['action'] ) ? $rules['action'] : '';

					if ( 'ig_es_contact_insert' != $action ) {
						continue;
					}

					// We are considering contacts for sequences which are last added in a week.
					$grace_period  = 1 * DAY_IN_SECONDS;
					$queue_upfront = 3600;

					$offset = (int) $rules['amount'] . ' ' . strtoupper( $rules['unit'] );

					$list_ids = $campaign['list_ids'];

					$ig_actions_table        = IG_ACTIONS_TABLE;
					$ig_lists_contacts_table = IG_LISTS_CONTACTS_TABLE;
					$ig_queue_table          = IG_QUEUE_TABLE;
					$ig_campaign_sent        = IG_MESSAGE_SENT;

					$query_args = array(
						'select'   => "SELECT lists_contacts.contact_id, UNIX_TIMESTAMP ( lists_contacts.subscribed_at + INTERVAL $offset ) AS timestamp",
						'from'     => "FROM $ig_lists_contacts_table AS lists_contacts",
						'join1'    => "LEFT JOIN $ig_actions_table AS actions_sent_message ON lists_contacts.contact_id = actions_sent_message.contact_id AND actions_sent_message.type = $ig_campaign_sent AND actions_sent_message.campaign_id IN ($campaign_id)",
						'join2'    => "LEFT JOIN $ig_queue_table AS queue ON lists_contacts.contact_id = queue.contact_id AND queue.campaign_id IN ($campaign_id)",
						'where'    => "WHERE 1=1 AND lists_contacts.list_id IN ($list_ids) AND lists_contacts.status = 'subscribed' AND actions_sent_message.contact_id IS NULL AND queue.contact_id IS NULL",
						'group_by' => 'GROUP BY lists_contacts.contact_id',
						'having'   => 'HAVING timestamp <= ' . ( $now + $queue_upfront ) . ' AND timestamp >= ' . ( $now - $grace_period ),
						'order_by' => 'ORDER BY timestamp ASC',
					);

					$query = implode( ' ', $query_args );

					// ES()->logger->info( '----------------------------Query Args (ig_es_contact_insert) ----------------------------' );
					// ES()->logger->info( $query );
					// ES()->logger->info( '----------------------------Query Args Complete (ig_es_contact_insert) ----------------------------' );

					$results = $wpbd->get_results( $query, ARRAY_A );

					// ES()->logger->info( 'Results: ' . print_r( $results, true ) );

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
		public function add_campaign_to_queue( $campaign, $total_contacts ) {

			$campaign_id = $campaign['id'];
			$template_id = $campaign['base_template_id'];
			$template    = get_post( $template_id );
			$queue_id    = 0;
			if ( $template instanceof WP_Post && $total_contacts > 0 ) {

				$subject = ! empty( $template->post_title ) ? $template->post_title : '';
				$content = ! empty( $template->post_content ) ? $template->post_content : '';
				$content = ES_Common::es_process_template_body( $content, $template_id );

				$guid = ES_Common::generate_guid( 6 );

				$data = array(
					'hash'        => $guid,
					'campaign_id' => $campaign_id,
					'subject'     => $subject,
					'body'        => $content,
					'count'       => 0,
					'status'      => 'Queueing',
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

			return $queue_id;
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

			if ( $tags ) {
				$tags = maybe_serialize( $tags );
			}
			if ( $options ) {
				$options = maybe_serialize( $options );
			}

			if ( empty( $subscribers ) ) {
				return true;
			}

			$inserts = array();

			foreach ( $subscribers as $i => $subscriber_id ) {
				$inserts[] = "($subscriber_id,$campaign_id,$now," . $timestamps[ $i ] . ",$priority,1,'$ignore_status','$options','$tags')";
			}

			$chunks = array_chunk( $inserts, 1000 );

			$success = true;

			foreach ( $chunks as $insert ) {

				$sql = "INSERT INTO {$wpdb->prefix}ig_queue (contact_id, campaign_id, added, timestamp, priority, count, ignore_status, options, tags) VALUES";

				$sql .= ' ' . implode( ',', $insert );

				$sql .= ' ON DUPLICATE KEY UPDATE timestamp = values(timestamp), ignore_status = values(ignore_status)';
				if ( $reset ) {
					$sql .= ', sent = 0';
				}
				if ( $options ) {
					$sql .= sprintf( ", options = '%s'", $options );
				}
				if ( $tags ) {
					$sql .= sprintf( ", tags = '%s'", $tags );
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

			$micro_time = microtime( true );

			$ig_queue_table     = IG_QUEUE_TABLE;
			$ig_campaigns_table = IG_CAMPAIGNS_TABLE;

			$sql  = 'SELECT queue.campaign_id, queue.contact_id, queue.count AS _count, queue.requeued AS _requeued, queue.options AS _options, queue.tags AS _tags, queue.priority AS _priority';
			$sql .= " FROM $ig_queue_table AS queue";
			$sql .= " LEFT JOIN $ig_campaigns_table AS campaigns ON campaigns.id = queue.campaign_id";
			$sql .= ' WHERE queue.timestamp <= ' . (int) $micro_time . ' AND queue.sent_at = 0';
			$sql .= ' AND (campaigns.status = 1)';
			$sql .= ' ORDER BY queue.priority DESC';

			// ES()->logger->info( 'Process Queue:' );
			// ES()->logger->info( 'SQL: ' . $sql );

			$notifications = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT queue.campaign_id, queue.contact_id, queue.count AS _count, queue.requeued AS _requeued, queue.options AS _options, queue.tags AS _tags, queue.priority AS _priority
					 FROM {$wpdb->prefix}ig_queue AS queue
					 LEFT JOIN {$wpdb->prefix}ig_campaigns AS campaigns ON campaigns.id = queue.campaign_id
					 WHERE queue.timestamp <= %d AND queue.sent_at = 0
					 AND (campaigns.status = 1)
					 ORDER BY queue.priority DESC",
					 (int) $micro_time
				),
				ARRAY_A
			);

			if ( is_array( $notifications ) && count( $notifications ) > 0 ) {
				$campaigns_notifications = array(); 
				$contact_ids 			 = array();
				foreach ( $notifications as $notification ) {
					$campaigns_notifications[ $notification['campaign_id'] ][] = $notification;

					$contact_ids[] = $notification['contact_id'];
				}

				// We need unique ids
				$contact_ids = array_unique( $contact_ids );

				$contacts = ES()->contacts_db->get_details_by_ids( $contact_ids );

				foreach ( $campaigns_notifications as $campaign_id => $notifications ) {

					$campaign = ES()->campaigns_db->get( $campaign_id );

					if ( ! empty( $campaign ) ) {

						$content = $campaign['body'];
						$subject = $campaign['subject'];

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

								$result = ES()->mailer->send( $subject, $content, $email, $merge_tags );

								do_action( 'ig_es_message_sent', $contact_id, $campaign_id, 0 );

								// Email Sent now delete from queue now.
								$this->db->delete_from_queue( $campaign_id, $contact_id );
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

							
		
							ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sending' );
		
							// Get subscribers from the sending_queue table based on fetched guid
							$emails_data  = ES_DB_Sending_Queue::get_emails_to_be_sent_by_hash( $notification_guid, $es_c_croncount );
							$total_emails = count( $emails_data );
							// Found Subscribers to send notification?
							if ( $total_emails > 0 ) {
								$ids 	= array();
								$emails = array();
								foreach ( $emails_data as $email ) {
									$ids[]    = $email['id'];
									$emails[] = $email['email'];
								}
		
								$merge_tags = array(
									'guid'        => $notification_guid,
									'message_id'  => $message_id,
									'campaign_id' => $campaign_id,
								);
		
								$subject = $notification['subject'];
								$content = $notification['body'];
		
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
		
									// Send Cron Email to admins
									ES()->mailer->send_cron_admin_email( $notification_guid );
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
			ES_Common::update_total_email_sent_count();
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
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, 'Sending' );
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
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, 'Sent' );
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
				$this->update_email_sent_status( $contact_id, $campaign_id, $message_id, 'In Queue' );
			}
		}
		
		/**
		 * Method to add subscribers to the sending queue in background. Gets called through the Action Scheduler library.
		 *
		 * @param array $args action arguements.
		 * 
		 * @since 4.6.3
		 */
		public function add_subscribers_to_sending_queue( $args = array() ) {

			if ( empty( $args['mailing_queue_id'] ) || ! is_numeric( $args['mailing_queue_id'] ) || empty( $args['list_ids'] ) ) {
				return false;
			}

			$batch_start_time = time();

			/** 
			 * By subtracting the waiting time from $batch_start_time now, 
			 * We are allowing timeout to happen in the background process loop 3 seconds earlier.
			 * This earlier timeout will ensure we get engough time to make another asynchrounous request 
			 * since we need to wait for sometime before making the asynchronous request.
			 **/ 
			$batch_start_time = $batch_start_time - IG_ES_Background_Process_Helper::get_wait_seconds();

			$mailing_queue_id = $args['mailing_queue_id'];
			$list_ids         = $args['list_ids'];

			$mailing_queue      = ES_DB_Mailing_Queue::get_email_by_id( $mailing_queue_id );

			// Check if mailing queue exists. May have been deleted manually.
			if ( empty( $mailing_queue ) ) {
				return false;
			}
			
			$mailing_queue_hash = $mailing_queue['hash'];
			$campaign_id        = $mailing_queue['campaign_id'];
			
			$active_subscribers = ES()->contacts_db->get_active_contacts_by_list_and_mailing_queue_id( $list_ids, $mailing_queue_id );
			
			if ( ! empty( $active_subscribers ) ) {
				$subscribers_batch_size = 5000; 
	
				// Create batches of subscribers each containing maximum subscribers equal to $subscribers_batch_size.
				$subscribers_batches = array_chunk( $active_subscribers, $subscribers_batch_size );
	
				foreach ( $subscribers_batches as $key => $subscribers ) {
					
					$delivery_data                     = array();
					$delivery_data['hash']             = $mailing_queue_hash;
					$delivery_data['subscribers']      = $subscribers;
					$delivery_data['campaign_id']      = $campaign_id;
					$delivery_data['mailing_queue_id'] = $mailing_queue_id;
					
					ES_DB_Sending_Queue::do_batch_insert( $delivery_data );
					
					// Remove the processed batch.
					unset( $subscribers_batches[ $key ] );
	
					// Check if time limit or memory limit has been reached.
					if ( IG_ES_Background_Process_Helper::time_exceeded( $batch_start_time ) || IG_ES_Background_Process_Helper::memory_exceeded() ) {
						break;
					}
				}
				
				$total_contacts_added =  ES_DB_Sending_Queue::get_total_email_count_by_hash( $mailing_queue_hash );
				ES_DB_Mailing_Queue::update_subscribers_count( $mailing_queue_hash, $total_contacts_added );
				
				// Check if there are no batches to process.
				if ( empty( $subscribers_batches ) ) {
	
					$mailing_queue_status = 'In Queue';
					// Update status to 'In Queue' so that cron(ES Cron/WP Cron) can pick it up.
					ES_DB_Mailing_Queue::update_sent_status( $mailing_queue_hash, $mailing_queue_status );

					$campaign_type = '';
					if ( ! empty( $campaign_id ) ) {
						$campaign_type = ES()->campaigns_db->get_campaign_type_by_id( $campaign_id );
					}

					// If campaign_type is newsletter i.e. broadcast, then trigger email sending if its email sending time has come.
					if ( 'newsletter' === $campaign_type ) {
						$queue_start_at = $mailing_queue['start_at'];
			
						$current_timestamp = time();
						$sending_timestamp = strtotime( $queue_start_at );
			
						// Check if campaign sending time has come.
						if ( $sending_timestamp <= $current_timestamp ) {
							$request_args = array(
								'action'        => 'ig_es_trigger_mailing_queue_sending',
								'campaign_hash' => $mailing_queue_hash,
							);
							// Send an asynchronous request to trigger sending of campaign emails.
							IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
						}
					}

				} else {
					/**
					 * If all subscribers batches are not processed(i.e. there are still emails to be added in the sending_queue table)
					 * Create another action scheduler task to process remaining batches.
					 **/
					$action_args = array(
						'mailing_queue_id' => $mailing_queue_id,
						'list_ids'         => $list_ids,
					);
					IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_add_subscribers_to_sending_queue', $action_args, true, true );
				}
			} else {
				$total_contacts_added =  ES_DB_Sending_Queue::get_total_email_count_by_hash( $mailing_queue_hash );
				// Check if there are not any queued email for this mailing queue id. If yes, then delete the mailing queue also since there is no meaning in processing an empty mailing queue.
				if ( empty( $total_contacts_added ) ) {
					ES_DB_Mailing_Queue::delete_notifications( array( $mailing_queue_id ) );
				}
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
		 * @param array $cron_job_data Cron job data
		 * 
		 * @since 4.6.4
		 */
		public function lock_cron_job( $cron_job = '', $cron_job_data = array() ) {

			$es_cron_jobs = ES()->cron->get_cron_jobs_list();

			$locking_status = '';

			if ( in_array( $cron_job, $es_cron_jobs, true  ) ) {

				$locked_cron_job_data = get_option( $cron_job . '_locked_data', false );
				if ( ! empty( $locked_cron_job_data ) ) {
					$locking_status = 'already_locked';
				} else {
					$locked_cron_job_data = array(
						'locked_at' => time(),
						'data'      => $cron_job_data
					);
					$job_locked = update_option( $cron_job . '_locked_data', $locked_cron_job_data , false );
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

			if ( in_array( $cron_job, $es_cron_jobs, true  ) ) {

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

			if ( in_array( $cron_job, $es_cron_jobs, true  ) ) {
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
		 * @param bool $force Should unlock the cron job forcefully.
		 *
		 * @return bool $should_unlock Should unlock the cron.
		 *
		 * @since 4.6.4
		 */
		public function should_unlock_cron_job( $cron_job = '', $force = false ) {

			$should_unlock = false;

			$es_cron_jobs = ES()->cron->get_cron_jobs_list();

			if ( in_array( $cron_job, $es_cron_jobs, true  ) ) {

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



