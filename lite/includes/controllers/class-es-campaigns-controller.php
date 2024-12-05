<?php

if ( ! class_exists( 'ES_Campaigns_Controller' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Campaigns_Controller
	 */
	class ES_Campaigns_Controller {

		// class instance
		public static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public static function get_campaigns_and_kpis( $args ) {
			$campaigns = self::get_campaigns( $args );
			$kpis      = self::get_kpis( $args );
			return array(
				'campaigns' => $campaigns,
				'kpis'      => $kpis,
			);
		}
		public static function	get_campaigns_count( $args) {
			$campaigns_table = ES_Campaigns_Table::get_instance();
			$per_page = $campaigns_table->get_items_per_page(ES_Campaigns_Table::$option_per_page, 20);
			$total_items = ES_DB_Campaigns::get_lists(0, 0, true , $args);
			$total_campaign_pages = ceil($total_items / $per_page); 
			$current_page=$args['currentPage'];
			return [$total_items,$total_campaign_pages,$current_page,$per_page];

		}

		public static function get_campaigns( $args ) {
			$campaigns_table = ES_Campaigns_Table::get_instance();
			$per_page = $campaigns_table->get_items_per_page(ES_Campaigns_Table::$option_per_page, 20);
			$current_page = ! empty( $args['currentPage'] ) ? $args['currentPage'] : 1;
			$total_items = ES_DB_Campaigns::get_lists(0, 0, true, array());
			$offset = ( $current_page - 1 ) * $per_page;
			$args['offset']=$offset;
			$args['per_page']=$per_page;
			$args['is_campaigns_listing']='is_campaigns_listing';
			
			$campaigns = ES()->campaigns_db->get_campaigns($args);
			
			if (!empty($campaigns)) {
				foreach ($campaigns as $index => $campaign) {
					$formatted_campaign = self::format_campaign_data($campaign);
					$campaigns[$index] = $formatted_campaign;
				}
			}
			
		  $campaigns['campaigns']=$campaigns;
		  $campaigns['currentPage'] = $current_page ? $current_page:1;
			return $campaigns;
			
		}

		private static function format_campaign_data( $campaign ) {
			$campaigns_table = ES_Campaigns_Table::get_instance();
			if ( ! empty( $campaign ) ) {
				$campaign['es_admin_email'] = ES_Common::get_admin_email();
				$campaign_id = $campaign['id'];
				$campaign_status = (int) $campaign['status'];
				$campaign_type = $campaign['type'];

				if ( self::is_post_campaign( $campaign_type ) ) {
					$campaign['formatted_categories'] = self::format_categories( $campaign['categories'] );
				}
				foreach ( $campaign as $column_name => $item ) {
					if ( method_exists( $campaigns_table, 'column_' . $column_name ) ) {
						$output = call_user_func( array( $campaigns_table, 'column_' . $column_name ), $campaign );
					} else {
						$output = $campaigns_table->column_default( $campaign, $column_name );
					}
					$campaign [ $column_name ] = $output;
				}
				$campaign['status_text'] = $campaigns_table->column_status_text( $campaign );
				$campaign['meta']        = ig_es_maybe_unserialize( $campaign['meta']);
				$args = array(
					'campaign_id' => $campaign_id,
					'types' => array(
						IG_MESSAGE_SENT,
						IG_MESSAGE_OPEN,
						IG_LINK_CLICK
					)
				);
				$actions_count       = ES()->actions_db->get_actions_count( $args );
				$total_email_sent    = $actions_count['sent'];
				$total_email_opened  = $actions_count['opened'];
				$total_email_clicked = $actions_count['clicked'];
				$open_rate  = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $total_email_opened * 100 ) / $total_email_sent ), 2 ) : 0 ;
				$click_rate = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $total_email_clicked * 100 ) / $total_email_sent ), 2 ) : 0;
				$campaign['open_rate']  = $open_rate;
				$campaign['click_rate'] = $click_rate;
				$campaign['meta'] = ig_es_maybe_unserialize( $campaign['meta']);
				if ( self::is_post_campaign( $campaign_type ) ) {
					$report = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
					if ( $report ) {
						$campaign['report_link'] = admin_url( 'admin.php?page=' . esc_attr( 'es_reports' ) . '&campaign_id=' . esc_attr( $campaign_id ) );
					}
				} elseif ( IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign_type ) {
					$broadcast_allowed_report_statuses = array(
						IG_ES_CAMPAIGN_STATUS_SCHEDULED,
						IG_ES_CAMPAIGN_STATUS_QUEUED,
						IG_ES_CAMPAIGN_STATUS_ACTIVE,
						IG_ES_CAMPAIGN_STATUS_FINISHED,
						IG_ES_CAMPAIGN_STATUS_PAUSED,
					);
					if ( in_array( $campaign_status, $broadcast_allowed_report_statuses, true ) ) {
						$report = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
						if ( $report ) {
							$campaign['report_link'] = admin_url( 'admin.php?page=' . esc_attr( 'es_reports' ) . '&action=view&list=' . $report['hash'] );
							$campaign['start_at']    = ig_es_format_date_time( $report['start_at'] );
						}
					}
				} else {
					$campaign['report_link'] = admin_url( 'admin.php?page=' . esc_attr( 'es_reports' ) . '&campaign_id=' . $campaign_id );
				}
			}
			return $campaign;
		}

		public static function get_kpis( $args ) {
			$page           = 'es_campaigns';
			$override_cache = true;
			$reports_data   = ES_Reports_Data::get_dashboard_reports_data( $page, $override_cache, $args );
			return $reports_data;
		}

		public static function delete_campaigns( $args ) {
			$campaign_ids = $args['campaign_ids'];
			if ( ! empty( $campaign_ids ) ) {
				return ES()->campaigns_db->delete_campaigns( $campaign_ids );
			}
			return false;
		}

		/**
		 * Method to Duplicate broadcast content
		 *
		 * @return void
		 *
		 * @since 4.6.3
		 */
		public static function duplicate_campaign( $campaign_id = 0 ) {
			
			if ( empty( $campaign_id ) ) {
				return false;
			}

			$duplicated_campaign_id = ES()->campaigns_db->duplicate_campaign( $campaign_id );
			if ( empty( $duplicated_campaign_id ) ) {
				return false;
			}

			$duplicated_campaign = ES()->campaigns_db->get( $duplicated_campaign_id );
			if ( empty( $duplicated_campaign ) ) {
				return false;
			}

			$duplicated_campaign = self::format_campaign_data( $duplicated_campaign );

			return $duplicated_campaign;
		}

		public static function format_categories( $categories ) {
			$categories = explode( '##', trim( trim( $categories, '##' ) ) );
			$formatted_categories = array();
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					if ( ! empty( $category ) ) {
						$post_categories = explode( '|', $category );
						foreach ( $post_categories as $post_category ) {
							list( $post_type, $categories_list ) = explode( ':', $post_category );
							if ( 'none' !== $categories_list && 'all' !== $categories_list ) {
								$categories_list = array_map( 'absint', explode( ',', $categories_list ) );
							}
							$formatted_categories[$post_type] = $categories_list;
						}
					} 
				} 
			}
			return $formatted_categories;
		}

		public static function is_post_campaign( $campaign_type ) {
			return in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_POST_NOTIFICATION, IG_CAMPAIGN_TYPE_POST_DIGEST ), true );
		}

		public static function paginate_campaigns() {
			$campaigns_table =  ES_Campaigns_Table::get_instance();
			$per_page = $campaigns_table->get_items_per_page( ES_Campaigns_Table::$option_per_page, 25 );
			$current_page = $campaigns_table->get_pagenum();
			$total_items = $campaigns_table->get_lists( 0, 0, true );
	
			$campaigns_table->set_pagination_args(
				array(
					'total_items' => $total_items, // We have to calculate the total number of items
					'per_page'    => $per_page, // We have to determine how many items to show on a page
				)
			);
			$campaigns = $campaigns_table->get_lists( $per_page, $current_page );

			if ( ! empty( $campaigns ) ) {
				foreach ( $campaigns as $index => $campaign ) {
					$campaign            = self::format_campaign_data(  $campaign );
					$campaigns[ $index ] = $campaign;
				}
			}
			return $campaigns;
			
		}

	}

}

ES_Campaigns_Controller::get_instance();
