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

		public static function save( $campaign_data ) {
			$response      = array();
			$campaign_data = self::prepare_campaign_data( $campaign_data );
			$campaign_saved = self::save_campaign( $campaign_data );

			if ( $campaign_saved ) {
				$campaign_id = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : $campaign_saved;
				$response['success']     = true;
				$response['data']        = array(
					'campaign_id' => $campaign_id
				);
			} else {
				$response['success'] = false;
			}

			return $response;
		}

		public static function activate( $campaign_data ) {
			$response = array();
			$meta       = ! empty( $campaign_data['meta'] ) ? $campaign_data['meta'] : array();
			if ( ! empty( $meta['list_conditions'] ) ) {
				$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
			}
			if ( empty( $meta['list_conditions'] ) ) {
				$response['success'] = false;
				$response['message'] = __( 'Please add recipients before activating.', 'email-subscribers' );
				return $response;
			}

			$response = self::save( $campaign_data );
			return $response;
		}

		public static function save_and_schedule( $campaign_data ) {
			$response = array();
			$meta                              = ! empty( $campaign_data['meta'] ) ? $campaign_data['meta'] : array();
			if ( ! empty( $meta['list_conditions'] ) ) {
				$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
			}
			if ( empty( $meta['list_conditions'] ) ) {
				$response['success'] = false;
				$response['message'] = __( 'Please add recipients before scheduling.', 'email-subscribers' );
				return $response;
			}

			$result = self::save( $campaign_data );
			if ( $result['success'] ) {
				$response = self::schedule( $campaign_data );
			}
			return $response;
		}

		public static function schedule( $campaign_data ) {
			$response = array(
				'success' => false,
			);
			$scheduling_status = '';
			if ( ! empty( $campaign_data['id'] ) ) {
				$campaign_id   = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
				$campaign_meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );

				$notification = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
				$base_template_id = ! empty( $campaign_data['base_template_id'] ) ? $campaign_data['base_template_id'] : 0;
				$campaign_data['body'] = ES_Common::es_process_template_body( $campaign_data['body'], $base_template_id, $campaign_id );

				$guid = ES_Common::generate_guid( 6 );

				$meta = apply_filters( 'ig_es_before_save_campaign_notification_meta', array( 'type' => 'newsletter' ), $campaign_meta );
				$data = array(
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
						$campaign_data['hash']        = $notification['hash'];
						$campaign_data['campaign_id'] = $notification['campaign_id'];
						$campaign_data['created_at']  = $notification['created_at'];

						// Check if list has been updated, if yes then we need to delete emails from existing lists and requeue the emails from the updated lists.
						$should_queue_emails = true;
						$campaign_data['count']       = 0;

						$notification = ES_DB_Mailing_Queue::update_notification( $mailing_queue_id, $data );
					}
				}

				if ( ! empty( $mailing_queue_id ) ) {
					if ( $should_queue_emails ) {
						$email_queued = self::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id );
						if ( $email_queued ) {
							$response['success'] = true;
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
			$meta['pre_header']                = ! empty( $campaign_data['pre_header'] ) ? $campaign_data['pre_header'] : '';
			$meta['preheader']                 = ! empty( $campaign_data['preheader'] ) ? $campaign_data['preheader'] : '';

			if ( ! empty( $meta['list_conditions'] ) ) {
				$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
			}
			
			$meta = apply_filters( 'ig_es_before_save_campaign_meta', $meta, $campaign_data );

			$campaign_data['meta'] = maybe_serialize( $meta );

			return $campaign_data;
		}

		public static function save_campaign( $campaign_data ) {
			$campaign_saved = false;
			$campaign_id    = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
			$campaign_type  = ! empty( $campaign_data['type'] ) ? $campaign_data['type'] : IG_ES_DRAG_AND_DROP_EDITOR;


			$campaign_data['name'] = $campaign_data['subject'];
			$campaign_data['slug'] = sanitize_title( sanitize_text_field( $campaign_data['name'] ) );

			$campaign_data = apply_filters( 'ig_es_campaign_data', $campaign_data );
			$campaign_data = apply_filters( 'ig_es_' . $campaign_type . '_data', $campaign_data );

			if ( ! empty( $campaign_id ) ) {
				$campaign_saved = ES()->campaigns_db->save_campaign( $campaign_data, $campaign_id );
			} else {
				$campaign_saved = ES()->campaigns_db->save_campaign( $campaign_data );
				if ( $campaign_saved ) {
					$new_flow_campaign_ids = get_option( 'ig_es_new_category_format_campaign_ids', array() );
					$new_flow_campaign_ids[] = $campaign_saved;
					update_option( 'ig_es_new_category_format_campaign_ids', $new_flow_campaign_ids, false );
				}
			}

			return $campaign_saved;
		}

		public static function add_post_notification_data( $campaign_data ) {

			$new_flow_campaign_ids = get_option( 'ig_es_new_category_format_campaign_ids', array() );
			if ( empty( $campaign_data['id'] ) || in_array( (int) $campaign_data['id'], $new_flow_campaign_ids, true ) ) {
				$categories         = ! empty( $campaign_data['es_note_cat'] ) ? $campaign_data['es_note_cat'] : array();
				$es_note_cat_parent = $campaign_data['es_note_cat_parent'];
				$category_array = array();
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
				$campaign_data['categories'] = $categories;
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

			$scheduling_option = ! empty( $data['scheduling_option'] ) ? $data['scheduling_option'] : 'schedule_now';

			$schedule_str = '';

			if ( 'schedule_now' === $scheduling_option ) {
				// Get time without GMT offset, as we are adding later on.
				$schedule_str = current_time( 'timestamp', false );
			}

			if ( ! empty( $schedule_str ) ) {
				$gmt_offset_option = get_option( 'gmt_offset' );
				$gmt_offset        = ( ! empty( $gmt_offset_option ) ) ? $gmt_offset_option : 0;
				$schedule_date     = gmdate( 'Y-m-d H:i:s', $schedule_str - ( $gmt_offset * HOUR_IN_SECONDS ) );

				$data['start_at'] = $schedule_date;
				$meta             = ! empty( $data['meta'] ) ? maybe_unserialize( $data['meta'] ) : array();
				$meta['type']     = 'one_time';
				$meta['date']     = $schedule_date;
				$data['meta']     = maybe_serialize( $meta );
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

			$campaign_meta = ! empty( $campaign_data['meta'] ) ? maybe_unserialize( $campaign_data['meta'] ) : array();

			if ( empty( $campaign_meta['enable_open_tracking'] ) ) {
				$campaign_meta['enable_open_tracking'] = 'no';
			}

			$campaign_data['meta'] = maybe_serialize( $campaign_meta );

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
			$new_flow_campaign_ids = get_option( 'ig_es_new_category_format_campaign_ids', array() );
			$using_new_category_format = false;
			if ( empty( $campaign_id ) || in_array( (int) $campaign_id, $new_flow_campaign_ids, true  ) ) {
				$using_new_category_format = true;
			}
			return $using_new_category_format;
		}

		public static function add_to_new_category_format_campaign_ids( $campaign_id ) {
			$new_flow_campaign_ids = get_option( 'ig_es_new_category_format_campaign_ids', array() );
			$new_flow_campaign_ids[] = $campaign_id;
			update_option( 'ig_es_new_category_format_campaign_ids', $new_flow_campaign_ids, false );
		}
	}

}

ES_Campaign_Controller::get_instance();
