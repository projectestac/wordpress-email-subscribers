<?php

if ( ! class_exists( 'ES_Campaign_Controller' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Campaign_Controller
	 */
	class ES_Campaign_Controller {

		// class instance
		public static $instance;

		// class constructor
		public function __construct() {
			$this->init();
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function init() {
			$this->register_hooks();
		}

		public function register_hooks() {
			add_action( 'ig_es_' . IG_CAMPAIGN_TYPE_POST_NOTIFICATION . '_data', array( __CLASS__, 'add_post_notification_data' ) );
			add_action( 'ig_es_' . IG_CAMPAIGN_TYPE_POST_DIGEST . '_data', array( __CLASS__, 'add_post_notification_data' ) );

			if ( ! ES()->is_pro() ) {
				// Add newsletter scheduler data
				add_filter( 'ig_es_' . IG_CAMPAIGN_TYPE_NEWSLETTER . '_data', array( __CLASS__, 'add_broadcast_scheduler_data' ) );
			}

			add_filter( 'ig_es_campaign_data', array( __CLASS__, 'add_tracking_fields_data' ) );

			// Check campaign wise open tracking is enabled.
			add_filter( 'ig_es_track_open', array( __CLASS__, 'is_open_tracking_enabled' ), 10, 4 );
		}

		public static function validate_campaign_data( $campaign_data, $action) {
			$messages = [
				'subject' => __('Please add subject before', 'email-subscribers'),
				'body' => __('Please add content before', 'email-subscribers'),
				'list_conditions' => __('Please add recipients before', 'email-subscribers')
			];
			foreach ($messages as $key => $message) {
				if ('list_conditions' === $key ) {
					if (empty($campaign_data['meta']['list_conditions'])) {
						return $message . ' ' . $action . '.';
					}
				} else {
					if (empty($campaign_data[$key])) {
						return $message . ' ' . $action . '.';
					}
				}
			}
			return '';
		}

		public static function save( $campaign_data ) {
			$response          = array();
			$campaign_status   = ! empty( $campaign_data['status'] ) ? (int) $campaign_data['status'] : 0;
			if ( IG_ES_CAMPAIGN_STATUS_ACTIVE === $campaign_status || IG_ES_CAMPAIGN_STATUS_SCHEDULED === $campaign_status ) {
				$meta     = ! empty( $campaign_data['meta'] ) ? $campaign_data['meta'] : array();
				if ( ! empty( $meta['list_conditions'] ) ) {
					$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
				}

				$validation_message = self::validate_campaign_data($campaign_data, 'activating');
				if ($validation_message) {
					$response['success'] = false;
					$response['message'] = $validation_message;
					wp_send_json($response);
				}
			}
			
			$campaign_data     = self::prepare_campaign_data( $campaign_data );
			$saved_campaign_id = self::save_campaign( $campaign_data );
			if ( $saved_campaign_id ) {
				$response['campaign_id'] = $saved_campaign_id;
				
				$campaign_type = $campaign_data['type'];
				if ( self::is_post_campaign( $campaign_type ) && IG_ES_CAMPAIGN_STATUS_ACTIVE == $campaign_data['status']) {
						
					$meta = maybe_unserialize($campaign_data['meta']);
					if ( ! empty( $meta['send_posts_now'] ) && 'yes' === $meta['send_posts_now']) {
					
					  $mailing_queue_id = self::generateReport($saved_campaign_id);
						if (!empty($mailing_queue_id)) {

						  $mailing_queue = ES_DB_Mailing_Queue::get_mailing_queue_by_id($mailing_queue_id);
							if (!empty($mailing_queue)) {
								$mailing_queue_hash = $mailing_queue['hash'];
								$request_args = array(
								'action'        => 'ig_es_trigger_mailing_queue_sending',
								'campaign_hash' => $mailing_queue_hash,
								);
								// Send an asynchronous request to trigger sending of campaign emails.
								IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
							}
						}
					}
				}
			}

			return $response;
		}

		public static function generateReport( $campaign_id = 0 ) {
			$post_ids = array();
			$meta_data = array();
			$mailing_queue_id=0;
			$has_matching_post = false;
			if ( $campaign_id ) {
				$campaign  = ES()->campaigns_db->get_campaign_by_id( $campaign_id );
				$meta=maybe_unserialize($campaign['meta']);
				//$campaigns = array( $campaign );
			}
			
				$campaign_body          = $campaign['body'];
				$ignore_stored_post_ids = true; // Set it to true so that we don't get same post ids which were set in the last run.
			if ( ES_Common::contains_posts_block( $campaign_body ) ) {
				$post_ids = self::get_post_block_matching_post_ids( $campaign_id, $ignore_stored_post_ids );
				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $block_index => $block_post_ids ) {
						if ( ! empty( $block_post_ids ) ) {
							// Set flag to true if we found matching posts for atleast one block.
							$has_matching_post = true;
							break;
						}
					}
				}
			} else {
				$post_ids = self::get_matching_post_ids( $campaign_id, $ignore_stored_post_ids );
				if ( ! empty( $post_ids ) ) {
					$has_matching_post = true;
				}
			}


					// Proceed only if we have posts for digest.
			if ( ! empty( $has_matching_post ) ) {
				$list_id = $meta['list_conditions'][0][0]['value'];
						
				// Do we have active subscribers?
				$contacts       = ES()->contacts_db->get_active_contacts_by_list_id( $list_id );
				$total_contacts = count( $contacts );
				// Create a new mailing queue using this campaign
				$result = self::add_campaign_to_queue( $campaign, $post_ids );

				if ( ! empty( $result['id'] ) ) {

					$mailing_queue_id = $result['id'];

					if ( ! empty( $mailing_queue_id ) ) {
						$mailing_queue_hash = $result['hash'];
						$emails_queued      = ES_DB_Sending_Queue::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id, $list_id );
						if ( $emails_queued ) {
							$meta_data['post_ids'] = $post_ids;
							$meta_data['last_run'] = strtotime( ig_get_current_date_time() );
						}
					}
				}
			}

					//$time_frame = ! empty( $rules['time_frame'] ) ? $rules['time_frame'] : '';

					ES()->campaigns_db->update_campaign_meta( $campaign_id, $meta_data );

			return $mailing_queue_id;


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
		public static function add_campaign_to_queue( $campaign, $post_ids ) {

			$campaign_id = $campaign['id'];
			$template_id = $campaign['base_template_id'];
			$template    = get_post( $template_id );
			$subject     = $campaign['subject'];
			$content     = $campaign['body'];
			$content     = ES_Common::es_process_template_body( $content, $template_id );

			$guid       = ES_Common::generate_guid( 6 );
			$meta_array = array( 'type' => $campaign['type'] ) ;

			if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign['type']) {
			   $meta_array['post_id'] = !empty($post_ids[0][0]) ? $post_ids[0][0] : 0;
			}

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
				'meta'        => maybe_serialize( $meta_array ),
			);




			$queue_id = ES_DB_Mailing_Queue::add_notification( $data );

			return array(
				'hash' => $guid,
				'id'   => $queue_id,
			);
		}

		public static function save_and_schedule( $campaign_data ) {
			$response = array();
			$meta     = ! empty( $campaign_data['meta'] ) ? $campaign_data['meta'] : array();
			if ( ! empty( $meta['list_conditions'] ) ) {
				$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
			}
			$validation_message = self::validate_campaign_data($campaign_data, 'scheduling');
			if ($validation_message) {
				$response['success'] = false;
				$response['message'] = $validation_message;
				wp_send_json($response);
			}

			$saved_campaign_id = self::save( $campaign_data );
			if ( $saved_campaign_id ) {
				$response = self::schedule( $campaign_data );
			}
			return $response;
		}

		public static function schedule( $campaign_data ) {
			$response          = array(
				'success' => false,
			);
			$scheduling_status = '';
			if ( ! empty( $campaign_data['id'] ) ) {
				$campaign_id           = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
				$campaign_meta         = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
				$notification          = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
				$base_template_id      = ! empty( $campaign_data['base_template_id'] ) ? $campaign_data['base_template_id'] : 0;
				$campaign_data['body'] = ES_Common::es_process_template_body( $campaign_data['body'], $base_template_id, $campaign_id );

				$guid = ES_Common::generate_guid( 6 );

				$meta                = apply_filters( 'ig_es_before_save_campaign_notification_meta', array( 'type' => 'newsletter' ), $campaign_meta );
				$data                = array(
					'hash'        => $guid,
					'campaign_id' => $campaign_id,
					'subject'     => $campaign_data['subject'],
					'body'        => $campaign_data['body'],
					'status'      => '',
					'start_at'    => ! empty( $campaign_meta['date'] ) ? $campaign_meta['date'] : '',
					'finish_at'   => '',
					'created_at'  => ig_get_current_date_time(),
					'updated_at'  => ig_get_current_date_time(),
					'meta'        => maybe_serialize( $meta ),
				);
				$should_queue_emails = false;
				$mailing_queue_id    = 0;
				// Add notification to mailing queue if not already added.
				if ( empty( $notification ) ) {
					$data['count']       = 0;
					$mailing_queue_id    = ES_DB_Mailing_Queue::add_notification( $data );
					$mailing_queue_hash  = $guid;
					$should_queue_emails = true;
				} else {
					$mailing_queue_id    = $notification['id'];
					$mailing_queue_hash  = $notification['hash'];
					$notification_status = $notification['status'];
					// Check if notification is not sending or already sent then only update the notification.
					if ( ! in_array( $notification_status, array( 'Sending', 'Sent' ), true ) ) {
						// Don't update this data.
						$data['hash']        = $notification['hash'];
						$data['campaign_id'] = $notification['campaign_id'];
						$data['created_at']  = $notification['created_at'];

						// Check if list has been updated, if yes then we need to delete emails from existing lists and requeue the emails from the updated lists.
						$should_queue_emails    = true;
						$data['count'] = 0;

						$notification = ES_DB_Mailing_Queue::update_notification( $mailing_queue_id, $data );
					}
				}

				if ( ! empty( $mailing_queue_id ) ) {
					if ( $should_queue_emails ) {
						$email_queued = self::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id );
						if ( $email_queued ) {
							$response['success']              = true;
							$response['data']['redirect_url'] = admin_url( 'admin.php?page=es_campaigns&id=' . $campaign_id . '&action=campaign_scheduled' );
						}
					}

					self::maybe_send_mailing_queue( $mailing_queue_id, $mailing_queue_hash );
				}
			}

			return $response;
		}

		public static function prepare_campaign_data( $campaign_data ) {
			
			$list_id     = ! empty( $campaign_data['list_ids'] ) ? $campaign_data['list_ids']      : '';
			$template_id = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id']: '';
			$meta        = ! empty( $campaign_data['meta']    ) ? $campaign_data['meta']           : array();
			
			$campaign_data['subject']          = ! empty( $campaign_data['subject'] ) ? wp_strip_all_tags( $campaign_data['subject'] ) : '';
			$campaign_data['base_template_id'] = $template_id;
			$campaign_data['list_ids']         = $list_id;
			$meta                              = ! empty( $campaign_data['meta'] ) ? $campaign_data['meta'] : array();
			 $meta['scheduling_option']         = ! empty( $campaign_data['scheduling_option'] ) ? $campaign_data['scheduling_option'] : 'schedule_now';

			$meta['es_schedule_date']          = ! empty( $campaign_data['es_schedule_date'] ) ? $campaign_data['es_schedule_date'] : '';
			$meta['es_schedule_time']          = ! empty( $campaign_data['es_schedule_time'] ) ? $campaign_data['es_schedule_time'] : '';

			if ( ! empty( $meta['list_conditions'] ) ) {
				$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
			}

			$campaign_type = $campaign_data['type'];
			if ( self::is_post_campaign( $campaign_type ) ) {
				$campaign_body = $campaign_data['body'];
				if ( ! ES_Common::contains_posts_block( $campaign_body ) ) {
					$campaign_body         = ES_Common::wrap_post_keywords_between_campaign_posts_keyword( $campaign_body );
					$campaign_data['body'] = $campaign_body;
				}
			}
			
			$meta = apply_filters( 'ig_es_before_save_campaign_meta', $meta, $campaign_data );

			$campaign_data['meta'] = maybe_serialize( $meta );
			
			return $campaign_data;
		}

		public static function save_campaign( $campaign_data ) {
			$campaign_id   = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
			$campaign_type = ! empty( $campaign_data['type'] ) ? $campaign_data['type'] : IG_ES_DRAG_AND_DROP_EDITOR;


			$campaign_data['name'] = !empty($campaign_data['name']) ? $campaign_data['name'] : $campaign_data['subject'];
			$campaign_data['slug'] = sanitize_title( sanitize_text_field( $campaign_data['name'] ) );

			$campaign_data = apply_filters( 'ig_es_campaign_data', $campaign_data );
			$campaign_data = apply_filters( 'ig_es_' . $campaign_type . '_data', $campaign_data );
			if ( ! empty( $campaign_id ) ) {
				$campaign_saved = ES()->campaigns_db->save_campaign( $campaign_data, $campaign_id );
				if ( $campaign_saved ) {
					return $campaign_id;
				}
			} else {
				$campaign_saved = ES()->campaigns_db->save_campaign( $campaign_data );
				if ( $campaign_saved ) {
					$new_campaign_id         = $campaign_saved;
					$new_flow_campaign_ids   = get_option( 'ig_es_new_category_format_campaign_ids', array() );
					$new_flow_campaign_ids[] = $new_campaign_id;
					update_option( 'ig_es_new_category_format_campaign_ids', $new_flow_campaign_ids, false );
					return $new_campaign_id;
				}
			}

			return false;
		}

		public static function add_post_notification_data( $campaign_data ) {

			$new_flow_campaign_ids = get_option( 'ig_es_new_category_format_campaign_ids', array() );
			if ( empty( $campaign_data['id'] ) || in_array( (int) $campaign_data['id'], $new_flow_campaign_ids, true ) ) {
				$categories         = ! empty( $campaign_data['es_note_cat'] ) ? $campaign_data['es_note_cat'] : array();
				$es_note_cat_parent = ! empty( $campaign_data['es_note_cat_parent'] ) ? $campaign_data['es_note_cat_parent'] : array();
				$category_array     = array();
				if ( '{a}None{a}' === $es_note_cat_parent ) {
					$category_array[] = 'post:none';
				} elseif ( '{a}All{a}' === $es_note_cat_parent ) {
					$category_array[] = 'post:all';
				} else {
					$category_array[] = 'post:' . implode( ',', $categories );
				}
	
				$cpt_terms = ! empty( $campaign_data['cpt_terms'] ) ? $campaign_data['cpt_terms'] : array();
				
				// Check if custom post types are selected.
				if ( ! empty( $campaign_data['es_note_cpt'] ) ) {
					foreach ( $campaign_data['es_note_cpt'] as $cpt ) {
						$cpt = str_replace( '{T}', '', $cpt );
						if ( ! empty( $cpt_terms[ $cpt ] ) ) {
							$term_ids = array();
							foreach ( $cpt_terms[ $cpt ] as $cpt_slug => $cpt_term_ids ) {
								$term_ids = array_merge( $term_ids, $cpt_term_ids );
							}
							if ( ! empty( $term_ids ) ) {
								$category_array[] = $cpt . ':' . implode( ',', $term_ids );
							} else {
								$category_array[] = $cpt . ':all';
							}
						} else {
							$category_array[] = $cpt . ':all';
						}
					}
				}
				// Merge categories and selected custom post types.
				$categories = '##' . implode( '|', $category_array ) . '##';
				//$campaign_data['categories'] = $categories;
			} else {
				$categories         = ! empty( $campaign_data['es_note_cat'] ) ? $campaign_data['es_note_cat'] : array();
				$es_note_cat_parent = $campaign_data['es_note_cat_parent'];
				$categories         = ( ! empty( $es_note_cat_parent ) && in_array( $es_note_cat_parent, array( '{a}All{a}', '{a}None{a}' ), true ) ) ? array( $es_note_cat_parent ) : $categories;
	
				// Check if custom post types are selected.
				if ( ! empty( $campaign_data['es_note_cpt'] ) ) {
					// Merge categories and selected custom post types.
					$categories = array_merge( $categories, $campaign_data['es_note_cpt'] );
				}
	
	
				$campaign_data['categories'] = ES_Common::convert_categories_array_to_string( $categories );
			}


			return $campaign_data;
		}

		/**
		 * Add required broadcast schedule date/time data
		 *
		 * @param array $data
		 *
		 * @return array $data
		 *
		 * @since 4.4.7
		 */
		public static function add_broadcast_scheduler_data( $data ) {

			$scheduling_option = ! empty( $data['scheduling_option'] ) ? sanitize_text_field($data['scheduling_option']) : 'schedule_now';
			$schedule_str      = '';

			if ( 'schedule_now' === $scheduling_option ) {
				// Get time without GMT offset, as we are adding later on.
				$schedule_str = current_time( 'timestamp', false );
			}

			if ( ! empty( $schedule_str ) ) {
				$gmt_offset_option = get_option( 'gmt_offset' );
				$gmt_offset        = ( ! empty( $gmt_offset_option ) ) ? $gmt_offset_option : 0;
				$schedule_date     = gmdate( 'Y-m-d H:i:s', $schedule_str - ( $gmt_offset * HOUR_IN_SECONDS ) );

				$data['start_at'] = $schedule_date;
				$meta             = ! empty( $data['meta'] ) ? ig_es_maybe_unserialize( $data['meta'] ) : array();
				$meta['type']     = 'one_time';
				$meta['date']     = $schedule_date;
				$data['meta']     = maybe_serialize($meta);
			}

			return $data;
		}

		/**
		 * Function to add values of checkbox fields incase they are not checked.
		 *
		 * @param array $campaign_data
		 *
		 * @return array $campaign_data
		 *
		 * @since 4.4.7
		 */
		public static function add_tracking_fields_data( $campaign_data = array() ) {
			
			$campaign_meta = ! empty( $campaign_data['meta'] ) ? ig_es_maybe_unserialize( $campaign_data['meta'] ) : array();
			
			if ( is_array( $campaign_meta ) ) {
				$enable_open_tracking = isset( $campaign_meta['enable_open_tracking'] ) ? sanitize_text_field( $campaign_meta['enable_open_tracking'] ) : 'no';	
				$campaign_meta['enable_open_tracking'] = $enable_open_tracking;
				$campaign_data['meta'] = maybe_serialize( $campaign_meta );
			}
			return $campaign_data;
		}
		

		/**
		 * Method to check if open tracking is enabled campaign wise.
		 *
		 * @param bool  $is_track_email_opens Is open tracking enabled.
		 * @param int   $contact_id Contact ID.
		 * @param int   $campaign_id Campaign ID.
		 * @param array $link_data Link data.
		 *
		 * @return bool $is_track_email_opens Is open tracking enabled.
		 *
		 * @since 4.4.7
		 */
		public static function is_open_tracking_enabled( $is_track_email_opens, $contact_id, $campaign_id, $link_data ) {
			if ( ! empty( $link_data ) ) {
				$campaign_id = ! empty( $link_data['campaign_id'] ) ? $link_data['campaign_id'] : 0;
				if ( ! empty( $campaign_id ) ) {
					$campaign = ES()->campaigns_db->get( $campaign_id );
					if ( ! empty( $campaign ) ) {
						$campaign_type = $campaign['type'];

						$supported_campaign_types = array(
							IG_CAMPAIGN_TYPE_NEWSLETTER,
							IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
							IG_CAMPAIGN_TYPE_POST_DIGEST,
							IG_CAMPAIGN_TYPE_WORKFLOW_EMAIL
						);

						$is_supported_type = in_array( $campaign_type, $supported_campaign_types, true );
						if ( $is_supported_type ) {
							$campaign_meta        = maybe_unserialize( $campaign['meta'] );
							$is_track_email_opens = ! empty( $campaign_meta['enable_open_tracking'] ) ? $campaign_meta['enable_open_tracking'] : $is_track_email_opens;
						}
					}
				}
			}

			return $is_track_email_opens;
		}

		public static function queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id ) {
			$list_ids = '';
			// Delete existing sending queue if any already present.
			ES_DB_Sending_Queue::delete_by_mailing_queue_id( array( $mailing_queue_id ) );
			$emails_queued = ES_DB_Sending_Queue::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id, $list_ids );
			if ( $emails_queued ) {
				return true;
			} else {
				return false;
			}
		}

		public static function maybe_send_mailing_queue( $mailing_queue_id, $mailing_queue_hash ) {
			$mailing_queue = ES_DB_Mailing_Queue::get_mailing_queue_by_id( $mailing_queue_id );
			if ( ! empty( $mailing_queue ) ) {

				$queue_start_at    = $mailing_queue['start_at'];
				$current_timestamp = time();
				$sending_timestamp = strtotime( $queue_start_at );
				// Check if campaign sending time has come.
				if ( ! empty( $sending_timestamp ) && $sending_timestamp <= $current_timestamp ) {
					$request_args = array(
						'action'        => 'ig_es_trigger_mailing_queue_sending',
						'campaign_hash' => $mailing_queue_hash,
					);
					// Send an asynchronous request to trigger sending of campaign emails.
					IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
				}
			}
		}

		public static function is_using_new_category_format( $campaign_id ) {
			$new_flow_campaign_ids     = get_option( 'ig_es_new_category_format_campaign_ids', array() );
			$using_new_category_format = false;
			if ( empty( $campaign_id ) || in_array( (int) $campaign_id, $new_flow_campaign_ids, true  ) ) {
				$using_new_category_format = true;
			}
			return $using_new_category_format;
		}

		public static function add_to_new_category_format_campaign_ids( $campaign_id ) {
			$new_flow_campaign_ids   = get_option( 'ig_es_new_category_format_campaign_ids', array() );
			$new_flow_campaign_ids[] = $campaign_id;
			update_option( 'ig_es_new_category_format_campaign_ids', $new_flow_campaign_ids, false );
		}

		/**
		 * Method to handle campaign status change
		 *
		 * @return string JSON response of the request
		 *
		 * @since 4.4.4
		 */
		public static function toggle_status( $args ) {
			if ( is_array( $args ) && isset( $args['campaign_ids'], $args['new_status'] ) ) {
				$campaign_ids = isset($args['campaign_ids']) ? $args['campaign_ids'] : array();
				$campaign_ids = array_map('absint', $campaign_ids);
				$new_status   = sanitize_text_field( $args['new_status'] );
		
				if (!empty($campaign_ids)) {
					$status_updated = ES()->campaigns_db->update_status( $campaign_ids, $new_status );
					return $status_updated;
				}
			}
			return false; 
		}
		

	/**
	 * Send Test Email
	 *
	 * @since 4.0.0
	 * @since 4.3.2 Call ES()->mailer->send_test_email() method to send test email
	 */
		public static function send_test_email( $campaign_data) {
		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
	
		$response = array();
	
		// Sanitize and validate inputs
		$email         = isset( $campaign_data['es_test_email'] ) ? sanitize_email( $campaign_data['es_test_email'] ) : '';
		$campaign_id   = isset( $campaign_data['id'] ) ? absint( $campaign_data['id'] ) : 0;
		$campaign_type = isset( $campaign_data['type'] ) ? sanitize_text_field( $campaign_data['type'] ) : '';
		$template_id   = isset( $campaign_data['base_template_id'] ) ? absint( $campaign_data['base_template_id'] ) : 0;
		$subject       = isset( $campaign_data['subject'] ) ? sanitize_text_field( $campaign_data['subject'] ) : '';
		$content       = isset( $campaign_data['body'] ) ?$campaign_data['body'] : '';
		$attachments   = isset( $campaign_data['meta']['attachments'] ) ? $campaign_data['meta']['attachments'] : array();
		$preheader     = isset( $campaign_data['meta']['pre_header'] ) ? sanitize_text_field( $campaign_data['meta']['pre_header'] ) : '';
	
			if ( ! empty( $email ) ) {
	
				$merge_tags = array( 'attachments' => $attachments );
	
				if ( ! empty( $campaign_id ) ) {
					if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign_type ) {
						$campaign_data = self::replace_post_notification_merge_tags_with_sample_post( $campaign_data );
					} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign_type ) {
						$campaign_data = self::replace_post_digest_merge_tags_with_sample_posts( $campaign_data );
					}
	
					$merge_tags['campaign_id'] = $campaign_id;
					$merge_tags['preheader']   = $preheader;
	
					$subject = isset( $campaign_data['subject'] ) ? sanitize_text_field( $campaign_data['subject'] ) : '';
					$content = isset( $campaign_data['body'] ) ?  $campaign_data['body']  : '';
				}
	
				$content = ES_Common::es_process_template_body( $content, $template_id, $campaign_id );
	
				$response = ES()->mailer->send_test_email( $email, $subject, $content, $merge_tags );
	
				if ( $response && 'SUCCESS' === $response['status'] ) {
					$response['message'] = __( 'Email has been sent. Please check your inbox', 'email-subscribers' );
				} else {
					$can_promote_ess = ES_Service_Email_Sending::can_promote_ess();
					if ( $can_promote_ess ) {
						$promotion_message_html = ES_Service_Email_Sending::get_ess_promotion_message_html();
						if ( is_array( $response['message'] ) ) {
							$response['message'][] = $promotion_message_html;
						} else {
							$response['message'] .= $promotion_message_html;
						}
					}
				}
			}
	
		return $response;
	
		}
	
		public static function get_posts_block_preview ( $data ) {
			$postsBlockContent = ! empty( $data['postsBlockContent'] ) ? $data['postsBlockContent'] : '';
			$postsBlockSetting = ! empty( $data['postsBlockSetting'] ) ? $data['postsBlockSetting'] : '';
			$response = array();
			if ( ! empty( $postsBlockContent ) ) {
				$postsBlockContent = str_replace( '{{post.title}}', 'Sample post', $postsBlockContent );
				$response['content'] = $postsBlockContent;
			}
			return $response;
		}


			/**
	 * Method to get spam score
	 *
	 * @since 4.6.1
	 */
		public static function get_spam_score( $campaign_data) {
			$response = [
			'status'        => 'error',
			'error_message' => __('Something went wrong', 'email-subscribers'),
			];
	
			$admin_email = ES_Common::get_admin_email();
	
			$sender_data = [
			'from_name'  => $campaign_data['from_name'],
			'from_email' => $campaign_data['from_email'],
			];
	
			$header = self::get_email_headers($sender_data) . "\n";
	
			if (!empty($campaign_data['subject'])) {
				$header .= 'Subject: ' . $campaign_data['subject'] . "\n";
			}
	
			$header .= 'Date: ' . gmdate('r') . "\n";
			$header .= 'To: ' . $admin_email . "\n";
			$header .= 'Message-ID: <' . $admin_email . ">\n";
			$header .= "MIME-Version: 1.0\n";
	
			$data['email'] = $header . $campaign_data['body'];
			$data['tasks'][] = 'spam-score';
	
			$spam_score_service = new ES_Service_Spam_Score_Check();
			$service_response = $spam_score_service->get_spam_score($data);
	
			if (!empty($service_response['status']) && 'success' === $service_response['status'] ) {
				$response['status'] = 'success';
				$response['res'] = $service_response['data'];
			}
	
			return $response;
		}
	

	/**
	 * Method to get email header.
	 *
	 * @param array $sender_data .
	 *
	 * @return array $headers
	 *
	 * @since 4.6.1
	 */
		public static function get_email_headers( $sender_data = array()) {
			$get_email_type = get_option('ig_es_email_type', true);
			$site_title = get_bloginfo();
			$admin_email = get_option('admin_email');
	
			$from_name = isset($sender_data['from_name']) ? $sender_data['from_name'] : get_option('ig_es_from_name', true);
			$from_email = isset($sender_data['from_email']) ? $sender_data['from_email'] : get_option('ig_es_from_email', true);
	
			$sender_email = $from_email ? $from_email: $admin_email;
			$sender_name = $from_name ? $from_name : $site_title;
	
			$headers = [
			"From: \"$sender_name\" <$sender_email>",
			'Return-Path: <' . $sender_email . '>',
			'Reply-To: "' . $sender_name . '" <' . $sender_email . '>',
			];
	
			$email_type_options = ['php_html_mail', 'php_plaintext_mail', 'wp_html_mail'];
			if (in_array($get_email_type, $email_type_options, true)) {
				$headers[] = 'MIME-Version: 1.0';
				$headers[] = 'X-Mailer: PHP' . phpversion();
			}
	
			$content_type = ( in_array($get_email_type, ['wp_html_mail', 'php_html_mail'], true) ) ? 'text/html' : 'text/plain';
			$headers[] = 'Content-Type: ' . $content_type . '; charset="' . get_bloginfo('charset') . '"';
	
			return implode("\n", $headers);
		}

		/**
		 * Method to get preview HTML for campaign
		 *
		 * @return $response
		 *
		 * @since 4.4.7
		 */
		public static function save_and_preview( $campaign_data ) {
			$response = array();

			$result = self::save( $campaign_data );
			if ( ! empty( $result['campaign_id'] ) ) {
				$campaign_data['id'] = $result['campaign_id'];
				$template_data                = array();
				$template_data['content']     = ! empty( $campaign_data['body'] ) ? $campaign_data['body'] : '';
				$template_data['template_id'] = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : '';
				$template_data['campaign_id'] = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
	
				$campaign_data            = self::add_campaign_body_data( $campaign_data );
				$response['preview_html'] = $campaign_data['body'];
				$response['id'] = $campaign_data['id'];
			}


			return $response;
		}

		public static function add_campaign_body_data( $campaign_data ) {

			$template_id = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : 0;
			$campaign_id = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
			if ( ! empty( $campaign_data['body'] ) ) {
				$current_user = wp_get_current_user();
				$username     = $current_user->user_login;
				$useremail    = $current_user->user_email;
				$display_name = $current_user->display_name;

				$contact_id = ES()->contacts_db->get_contact_id_by_email( $useremail );
				$first_name = '';
				$last_name  = '';

				// Use details from contacts data if present else fetch it from wp profile.
				if ( ! empty( $contact_id ) ) {
					$contact_data = ES()->contacts_db->get_by_id( $contact_id );
					$first_name   = $contact_data['first_name'];
					$last_name    = $contact_data['last_name'];
				} elseif ( ! empty( $display_name ) ) {
					$contact_details = explode( ' ', $display_name );
					$first_name      = $contact_details[0];
					// Check if last name is set.
					if ( ! empty( $contact_details[1] ) ) {
						$last_name = $contact_details[1];
					}
				}
				
				$campaign_body = $campaign_data['body'];
				$campaign_body = ES_Common::replace_keywords_with_fallback( $campaign_body, array(
					'FIRSTNAME' => $first_name,
					'NAME'      => $username,
					'LASTNAME'  => $last_name,
					'EMAIL'     => $useremail
				) );

				$custom_field_values = array();
				if ( ! empty( $contact_data ) ) {
					foreach ( $contact_data as $merge_tag_key => $merge_tag_value ) {
						if ( false !== strpos( $merge_tag_key, 'cf_' ) ) {
							$merge_tag_key_parts = explode( '_', $merge_tag_key );
							$merge_tag_key       = $merge_tag_key_parts[2];
							if ( is_null( $merge_tag_value ) ) {
								$merge_tag_value = '';
							}
							$custom_field_values[ 'subscriber.' . $merge_tag_key ] = $merge_tag_value;
						}
					}
				}

				$subscriber_tags_values = array(
					'subscriber.first_name' => $first_name,
					'subscriber.name'      => $username,
					'subscriber.last_name'  => $last_name,
					'subscriber.email'     => $useremail
				);

				$subscriber_tags_values = array_merge( $subscriber_tags_values, $custom_field_values );

				$campaign_body = ES_Common::replace_keywords_with_fallback( $campaign_body, $subscriber_tags_values );

				$campaign_type = $campaign_data['type'];

				$campaign_data['body'] = $campaign_body;

				if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign_type ) {
					$campaign_data = self::replace_post_notification_merge_tags_with_sample_post( $campaign_data );
				} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign_type ) {
					$campaign_data = self::replace_post_digest_merge_tags_with_sample_posts( $campaign_data );
				}

				$campaign_body = ! empty( $campaign_data['body'] ) ? $campaign_data['body'] : '';

				// If there are blocks in this content, we shouldn't run wpautop() on it.
				$priority = has_filter( 'the_content', 'wpautop' );

				if ( false !== $priority ) {
					// Remove wpautop to avoid p tags.
					remove_filter( 'the_content', 'wpautop', $priority );
				}

				$campaign_body = apply_filters( 'the_content', $campaign_body );

				$campaign_body = ES_Common::es_process_template_body( $campaign_body, $template_id, $campaign_id );

				$campaign_data['body'] = $campaign_body;

			}
			
			return $campaign_data;
		}

		public static function replace_post_notification_merge_tags_with_sample_post( $campaign_data ) {
			if ( ! empty( $campaign_data['id'] ) ) {

				$categories_str  = $campaign_data['categories'];
				$categories      = ES_Common::convert_categories_string_to_array( $categories_str, true );
				$meta            = $campaign_data['meta'];
				$no_of_posts     = ( ! empty( $meta['rules'] ) && ! empty( $meta['rules']['no_of_posts'] ) ) ? $meta['rules']['no_of_posts'] : array();
				$categories_data = $categories;

				foreach ($categories_data as $index => $category ) {
					$include_all_post = false;
					$include_no_post  = false;
					$cpt_categories = explode( '|', $category );
					foreach ( $cpt_categories as $cpt_category ) {
						if ( false !== strpos( $cpt_category, ':' ) ) {
							list( $post_type, $post_type_categories ) = explode( ':', $cpt_category );
							if ( 'post' === $post_type ) {
								if ( 'all' === $post_type_categories ) {
									$include_all_post = true;
								} elseif ( 'none' === $post_type_categories ) {
									$include_no_post = true;
								} else {
									$categories = array_map( 'absint', explode( ',', $post_type_categories ) );
								}
							} else {
								$custom_post_type[] = $post_type;
							}
						}
					}
					//$post_ids = array();
					$recent_posts = array();
					$post_count = ! empty( $no_of_posts[$index] ) ? $no_of_posts[$index] : 5;
					$meta_key = 'ig_es_post_notified_' . $campaign_data['id'];
					if ( ( ! empty( $categories ) || $include_all_post ) && ! $include_no_post ) {
						//phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						$post_args =  array(
							'post_type'      => 'post',
							'posts_per_page' => $post_count,
							'orderby'        => 'date',
							'order'          => 'DESC',
							'cat'            => ( ! $include_all_post ) ? implode( ',', $categories ) : '',
							'meta_query'     => array( 
								array(
									'key'     => $meta_key,
									'compare' => 'NOT EXISTS', 
								),
							),
						);
						$recent_posts = get_posts( $post_args );
					}
				
					if ( ! empty( $custom_post_type ) ) {
						$custom_post_args = array(
							'post_type'      => $custom_post_type,
							'posts_per_page' => $post_count,
							'orderby'        => 'date',
							'order'          => 'DESC',
							'meta_query'     => array( 
								array(
									'key'     => $meta_key,
									'compare' => 'NOT EXISTS' 
								),
							),
						);
						// phpcs:enable
						$recent_posts = get_posts( $custom_post_args );
					}
					
				}

				if ( count( $recent_posts ) > 0 ) {
					$post             = array_shift( $recent_posts );
					$post_id          = $post->ID;
					$template_id      = $campaign_data['id'];
					$campaign_body    = ! empty( $campaign_data['body'] ) ? $campaign_data['body'] : '';
					$campaign_subject = ! empty( $campaign_data['subject'] ) ? $campaign_data['subject'] : '';

					$campaign_subject = ES_Handle_Post_Notification::prepare_subject( $campaign_subject, $post );
					if ( ES_Common::contains_posts_block( $campaign_body ) ) {
						$campaign_body = ES_Common::replace_single_posts_block( $campaign_body, array( $post_id ) );
					} else {
						$campaign_body = ES_Handle_Post_Notification::prepare_body( $campaign_body, $post_id, $template_id );
					}

					$campaign_data['subject'] = $campaign_subject;
					$campaign_data['body']    = $campaign_body;
				}
			}

			return $campaign_data;
		}

		public static function replace_post_digest_merge_tags_with_sample_posts( $campaign_data ) {

			if ( ! empty( $campaign_data['id'] ) && class_exists( 'ES_Post_Digest' ) ) {
				$ignore_stored_post_ids = true;
				$ignore_last_run        = true;
				$campaign_id 			= $campaign_data['id'];
				$campaign_body 			= $campaign_data['body'];
				if ( ES_Common::contains_posts_block( $campaign_body ) ) {
					$campaign_post_ids = ES_Post_Digest::get_post_block_matching_post_ids( $campaign_id, $ignore_stored_post_ids, $ignore_last_run );
					$campaign_body   = ES_Common::replace_posts_blocks( $campaign_body, $campaign_post_ids );
				} else {
					$post_ids        = ES_Post_Digest::get_matching_post_ids( $campaign_id, $ignore_stored_post_ids, $ignore_last_run );
					$campaign_body = ES_Post_Digest::process_post_digest_template( $campaign_body, $post_ids );
				}
				$campaign_data['body']  = $campaign_body;
			}

			return $campaign_data;
		}

		public static function is_post_campaign( $campaign_type ) {
			return in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_POST_NOTIFICATION, IG_CAMPAIGN_TYPE_POST_DIGEST ), true );
		}


		public static function get_post_block_matching_post_ids( $campaign_id, $ignore_stored_post_ids = false, $ignore_last_run = false ) {
			
			//get recent no of posts
			$meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
			// Check if we have post ids stored in the campaign.
			if ( ! $ignore_stored_post_ids && ! empty( $meta['post_ids'] ) && is_array( $meta['post_ids'] ) ) {
				// If post ids are set in the campaign, then we don't need to fetch them again as they are already set in the last run of the report.
				return $meta['post_ids'];
			}

			$no_of_posts      = ( !empty( $meta['rules']) && !empty( $meta['rules']['no_of_posts'] ) ) ? $meta['rules']['no_of_posts'] : array();
			$sorting_orders      = ( !empty( $meta['rules']) && !empty( $meta['rules']['sorting_orders'] ) ) ? $meta['rules']['sorting_orders'] : array();
			$categories_str   = ES()->campaigns_db->get_campaign_categories_str_by_id( $campaign_id );
			$categories       = ES_Common::convert_categories_string_to_array( $categories_str, true );
			$custom_post_type = array();
			//decide period to fetch post ids
			$last_run = !empty($meta['last_run']) ? $meta['last_run'] : '';

			$categories_data = $categories;
			$campaign_post_ids = array();
			foreach ($categories_data as $index => $category ) {
				$include_all_post = false;
				$include_no_post  = false;
				$cpt_categories = explode( '|', $category );
				foreach ( $cpt_categories as $cpt_category ) {
					if ( false !== strpos( $cpt_category, ':' ) ) {
						list( $post_type, $post_type_categories ) = explode( ':', $cpt_category );
						if ( 'post' === $post_type ) {
							if ( 'all' === $post_type_categories ) {
								$include_all_post = true;
							} elseif ( 'none' === $post_type_categories ) {
								$include_no_post = true;
							} else {
								$categories = array_map( 'absint', explode( ',', $post_type_categories ) );
							}
						} else {
							$custom_post_type[] = $post_type;
						}
					}
				}
				$post_ids = array();
				$post_count = ! empty( $no_of_posts[$index] ) ? $no_of_posts[$index] : 5;
				$meta_key = 'ig_es_post_notified_' . $campaign_id;
				if ( ( ! empty( $categories ) || $include_all_post ) && ! $include_no_post ) {
				//phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$post_args =  array(
						'post_type'      => 'post',
						'posts_per_page' => $post_count,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'cat'            => ( ! $include_all_post ) ? implode( ',', $categories ) : '',
						'meta_query'     => array( 
							array(
							 'key'     => $meta_key,
							 'compare' => 'NOT EXISTS', // Fetch the post which weren't included in previous notifications
							),
						),
					);
					
					if ( ! empty( $last_run ) && ! $ignore_last_run ) {
						$post_args['date_query'] = array(
							'column'  => 'post_date',
							'after'   => gmdate( 'Y-m-d H:i:s', $last_run )
						);
					}
					// phpcs:enable
					$post_ids = get_posts( $post_args );
				}
				$custom_post_ids = array();
				if ( ! empty( $custom_post_type ) ) {
					//phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$custom_post_args = array(
						'post_type'      => $custom_post_type,
						'posts_per_page' => $post_count,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'meta_query'     => array( 
							array(
							 'key'     => $meta_key,
							 'compare' => 'NOT EXISTS' // Fetch the post which weren't included in previous notifications
							),
						),
					);
					if ( ! empty( $last_run ) && ! $ignore_last_run ) {
						$custom_post_args['date_query'] = array(
							'column'  => 'post_date',
							'after'   => gmdate( 'Y-m-d H:i:s', $last_run )
						);
					}
					// phpcs:enable
					$custom_post_ids = get_posts( $custom_post_args );
				}
				$posts = array_merge( $post_ids, $custom_post_ids );
				$sorting_order = ! empty( $sorting_orders[$index] ) ? $sorting_orders[$index] : 'descending';
				if ( 'descending' === $sorting_order ) {
					usort( $posts, array( 'ES_Campaign_Controller', 'sort_by_date_descending' ) );
				} else {
					usort( $posts, array( 'ES_Campaign_Controller', 'sort_by_date_ascending' ) );
				}
				$posts    =  array_slice( $posts, 0, $post_count, true );
				$post_ids = array_map( array( 'ES_Campaign_Controller', 'get_post_ids' ), $posts );  
				$campaign_post_ids[] = $post_ids;
			}

			
					
			// Allow third party developers to modify posts to be sent for this post digest campaign.
			$campaign_post_ids = apply_filters( 'ig_es_campaign_post_ids_for_post_digest', $campaign_post_ids, $campaign_id );
			return $campaign_post_ids; 
		}

		public static function get_matching_post_ids( $campaign_id, $ignore_stored_post_ids = false, $ignore_last_run = false ) {
			$include_all_post = false;
			$include_no_post  = false;
			//get recent no of posts
			$meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
			// Check if we have post ids stored in the campaign.
			if ( ! $ignore_stored_post_ids && ! empty( $meta['post_ids'] ) && is_array( $meta['post_ids'] ) ) {
				// If post ids are set in the campaign, then we don't need to fetch them again as they are already set in the last run of the report.
				return $meta['post_ids'];
			}

			$no_of_posts      = ( !empty( $meta['rules']) && !empty( $meta['rules']['no_of_posts'] ) ) ? $meta['rules']['no_of_posts'] : 5;
			$categories_str   = ES()->campaigns_db->get_campaign_categories_str_by_id( $campaign_id );
			$categories       = ES_Common::convert_categories_string_to_array( $categories_str, true );
			$custom_post_type = array();
			//decide period to fetch post ids
			$last_run = !empty($meta['last_run']) ? $meta['last_run'] : '';

			if ( self::is_using_new_category_format( $campaign_id ) ) {
				$categories_data = $categories;
				foreach ($categories_data as $key => $category ) {
					$cpt_categories = explode( '|', $category );
					foreach ( $cpt_categories as $cpt_category ) {
						if ( false !== strpos( $cpt_category, ':' ) ) {
							list( $post_type, $post_type_categories ) = explode( ':', $cpt_category );
							if ( 'post' === $post_type ) {
								if ( 'all' === $post_type_categories ) {
									$include_all_post = true;
								} elseif ( 'none' === $post_type_categories ) {
									$include_no_post = true;
								} else {
									$categories = array_map( 'absint', explode( ',', $post_type_categories ) );
								}
							} else {
								$custom_post_type[] = $post_type;
							}
						}
					}
				}
			} else {
				foreach ($categories as $key => $category) {
					if ( false !== strpos( $category, '{T}' ) ) {
						$custom_post_type[] = str_replace('{T}', '', $category);
						if ( isset( $categories ) && isset( $categories[ $key ] ) ) {
							unset( $categories[ $key ] );
						}
					}
					if ( 0 === $key ) {
						if ( 'All' == $category) {
							$include_all_post = true;
							unset( $categories );
						} elseif ( 'None' === $category ) {
							$include_no_post = true;
							unset( $categories );
						}
					}
				}
			}

			$post_ids = array();
			$meta_key = 'ig_es_post_notified_' . $campaign_id;
			if ( ( ! empty( $categories ) || $include_all_post ) && ! $include_no_post ) {
				//phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$post_args =  array(
					'post_type'      => 'post',
					'posts_per_page' => $no_of_posts,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'cat'            => ( ! $include_all_post ) ? implode( ',', $categories ) : '',
					'meta_query'     => array( 
						array(
						 'key'     => $meta_key,
						 'compare' => 'NOT EXISTS', // Fetch the post which weren't included in previous notifications
						),
					),
				);
				
				if ( ! empty( $last_run ) && ! $ignore_last_run ) {
					$post_args['date_query'] = array(
						'column'  => 'post_date',
						'after'   => gmdate( 'Y-m-d H:i:s', $last_run )
					);
				}
				// phpcs:enable
				$post_ids = get_posts( $post_args );
			}
			$custom_post_ids = array();
			if ( ! empty( $custom_post_type ) ) {
				//phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$custom_post_args = array(
					'post_type'      => $custom_post_type,
					'posts_per_page' => $no_of_posts,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'meta_query'     => array( 
						array(
						 'key'     => $meta_key,
						 'compare' => 'NOT EXISTS' // Fetch the post which weren't included in previous notifications
						),
					),
				);
				// phpcs:enable
				$custom_post_ids  = get_posts( $custom_post_args );
			}
			$posts = array_merge( $post_ids, $custom_post_ids );
			usort( $posts, array( 'ES_Campaign_Controller', 'sort_by_date_descending' ) );
			$posts    =  array_slice( $posts, 0, $no_of_posts, true );
			$post_ids = array_map( array( 'ES_Campaign_Controller', 'get_post_ids' ), $posts );  
			
					
			// Allow third party developers to modify posts to be sent for this post digest campaign.
			$post_ids = apply_filters( 'ig_es_post_ids_for_post_digest', $post_ids, $campaign_id );
			return $post_ids; 
		}

		public static function sort_by_date_descending( $a, $b) {
			return strtotime($b->post_date) - strtotime($a->post_date);
		}

		public static function sort_by_date_ascending( $a, $b) {
			return strtotime($a->post_date) - strtotime($b->post_date);
		}

		public static function get_post_ids( $post ) {
			return $post->ID;
		}


		public static function get_matching_recipients_count ( $recipient_rule) {
	
			$can_access_audience = ES_Common::ig_es_can_access( 'audience' );
			$can_access_campaign = ES_Common::ig_es_can_access( 'campaigns' );
			if ( ! ( $can_access_audience || $can_access_campaign ) ) {
				return 0;
			}
			$status     = $recipient_rule['status'];
			$conditions = $recipient_rule['list_conditions'];
		
			$expected_statuses = array( 'subscribed', 'unsubscribed', 'unconfirmed', 'confirmed', 'all' );
	
			if ( ! in_array( $status, $expected_statuses, true ) ) {
				return 0;
			}
	
			$response_data = array();
	
			if ( ! empty( $conditions ) ) {
				$conditions = IG_ES_Campaign_Rules::remove_empty_conditions( $conditions );
				
				if ( ! empty( $conditions ) ) {
					$args                   = array(
						'conditions'        => $conditions,
						'status'            => $status,
						'subscriber_status' => array( 'verified' ),
						'return_count'      => true,
					);
					$query                  = new IG_ES_Subscribers_Query();
					$response_data['total'] = $query->run( $args );
						
				} else {
					$response_data['total'] = 0;
				}
			
			} else {
				$response_data['total'] = ES()->lists_contacts_db->get_total_count_by_list($status );
			}
			if ( ! empty( $response_data['total'] ) ) {
				$response_data['total'] = number_format_i18n( $response_data['total'] );
			}
			
			return $response_data;
		}
		
		public static function get_posts_by_post_type( $post_type) {
			$posts  = ES()->campaigns_db->get_posts_by_type( $post_type );
			echo json_encode($posts);
			wp_die();
		}

		public static function replace_posts_blocks( $campaign_content) {
			
			$response_data=array();
			$innerHTML= '{{campaign.posts}}' . $campaign_content['innerHTML'] . '{{/campaign.posts}}';
			$postIds=[ $campaign_content['postIds'] ];

			$replaced_inner_HTML=ES_common::replace_posts_blocks($innerHTML, $postIds); 
			$response_data['preview_HTML']=$replaced_inner_HTML;
			
			return $response_data;
			 
		}
		

	}

}

ES_Campaign_Controller::get_instance();
