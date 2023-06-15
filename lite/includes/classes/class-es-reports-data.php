<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Reports_Data' ) ) {
	/**
	 * Get Reports Data
	 * Class ES_Reports_Data
	 *
	 * @since 4.3.2
	 */
	class ES_Reports_Data {

		/**
		 * Get total Contacts
		 *
		 * @since 4.3.2
		 */
		public static function get_total_contacts() {
			return ES()->contacts_db->get_total_contacts();
		}

		/**
		 * Get total subscribed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 * @since 4.3.5 Modified ES_DB_Lists_Contacts::get_total_subscribed_contacts to
		 * ES()->lists_contacts_db->get_total_subscribed_contacts
		 * @since 4.3.6 Modified function name from get_subscribed_contacts_count to get_subscribed_contacts_count
		 */
		public static function get_total_subscribed_contacts( $args = array() ) {
			$distinct = true;
			$list_id  = ! empty( $args['list_id'] ) ? $args['list_id'] : 0;
			$days     = ! empty( $args['days'] ) ? $args['days'] : 0;
			return ES()->lists_contacts_db->get_contacts( 'subscribed', $list_id, $days, true, $distinct );
		}

		/**
		 * Get total unsubscribed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 * @since 4.3.5 Modified ES_DB_Lists_Contacts::get_total_unsubscribed_contacts to
		 * ES()->lists_contacts_db->get_total_unsubscribed_contacts
		 * @since 4.3.6 Modified function name from get_total_unsubscribed_contacts to get_unsubscribed_contacts_count
		 */
		public static function get_total_unsubscribed_contacts( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 0;
			return ES()->lists_contacts_db->get_unsubscribed_contacts_count( $days );
		}

		/**
		 * Get total unconfiremed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.5.7
		 */
		public static function get_total_unconfirmed_contacts( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 0;
			return ES()->lists_contacts_db->get_unconfirmed_contacts_count( $days );
		}

		/**
		 * Get total contacts have opened emails in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 *
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_contacts_opened_emails( $args = array(), $distinct = true ) {
			$args['type'] = IG_MESSAGE_OPEN;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get total contacts have clicked on links in emails in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 *
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_contacts_clicks_links( $args = array(), $distinct = true ) {
			$args['type'] = IG_LINK_CLICK;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get total emails sent in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_emails_sent( $args = array(), $distinct = true ) {
			$args['type'] = IG_MESSAGE_SENT;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get total contacts lost in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 * 
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_contact_unsubscribed( $args = array(), $distinct = true ) {
			$args['type'] = IG_CONTACT_UNSUBSCRIBE;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get contacts growth
		 *
		 * @param int $days
		 *
		 * @return array
		 *
		 * @since 4.4.0
		 */
		public static function get_contacts_growth( $days = 60 ) {

			$contacts = ES()->contacts_db->get_total_contacts_by_date();

			$total = ES()->contacts_db->get_total_subscribed_contacts_before_days( $days );

			$data = array();
			for ( $i = $days; $i >= 0; $i -- ) {
				$date = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) );

				$count = isset( $contacts[ $date ] ) ? $contacts[ $date ] : 0;

				$total += $count;

				$data[ $date ] = $total;
			}

			return $data;
		}


		/**
		 * Get contacts growth percentage
		 *
		 * @param int $days
		 *
		 * @return float|integer
		 *
		 * @since 4.8.0
		 */
		public static function get_contacts_growth_percentage( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 60;
			//For example, It will get last 60'days subscribers count
			$present_subscribers_count = ES()->lists_contacts_db->get_subscribed_contacts_count( $days );
			//For example, It will get last 120'days subscribers count
			$past_to_present_subscribers_count = ES()->lists_contacts_db->get_subscribed_contacts_count( $days * 2 );
			//For example, It will get last 60-120'days subscribers count
			$past_subscribers_count = intval( $past_to_present_subscribers_count ) - intval( $present_subscribers_count );

			if ( 0 === $past_subscribers_count ) {
				return 0;
			} else {
				return round( ( $present_subscribers_count - $past_subscribers_count ) / $past_subscribers_count * 100, 2 );
			}
		}

		/**
		 * Collect dashboard reports data
		 *
		 * @return array
		 *
		 * @since 4.4.0
		 */
		public static function get_dashboard_reports_data( $page, $override_cache = false, $args = array(), $campaign_count = 3 ) {

			/**
			 * - Get Total Contacts
			 * - Get Total Forms
			 * - Get Total Lists
			 * - Get Total Campaigns
			 * - Get Last 3 months contacts data
			 * - Total Email Opened in last 60 days
			 * - Total Message Sent in last 60 days
			 * - Avg. Email Click rate
			 */
			$cache_key = 'dashboard_reports_data';

			if ( ! $override_cache ) {

				$cached_data = ES_Cache::get_transient( $cache_key );

				if ( ! empty( $cached_data ) ) {
					return $cached_data;
				}
			}

			$total_subscribed = self::get_total_subscribed_contacts( $args );

			$action_types       = ES()->get_action_types();
			$args['types']      = $action_types;
			$actions_counts     = ES()->actions_db->get_actions_count( $args );
			$total_email_opens  = $actions_counts['opened'];
			$total_links_clicks = $actions_counts['clicked'];
			$total_message_sent = $actions_counts['sent'];
			$total_unsubscribed = $actions_counts['unsubscribed'];
			$contacts_growth    = self::get_contacts_growth();

			$avg_open_rate = 0;
			if ( $total_message_sent > 0 ) {
				$avg_open_rate = ( $total_email_opens * 100 ) / $total_message_sent;
			}

			/**
			 * - Get recent 10 campaigns
			 *      - Get total open (3)
			 *      - Get total clicks (4)
			 *      - Get total unsubscribe (5)
			 */
			
			$data = self::get_campaign_stats( $campaign_count );

			$reports_data = array(
				'total_subscribed'   => number_format( $total_subscribed ),
				'total_email_opens'  => number_format( $total_email_opens ),
				'total_links_clicks' => number_format( $total_links_clicks ),
				'total_message_sent' => number_format( $total_message_sent ),
				'total_unsubscribed' => number_format( $total_unsubscribed ),
				'avg_open_rate'      => number_format( $avg_open_rate, 2 ),
				'contacts_growth'    => $contacts_growth,
			);

			$is_dashboard_page = 'es_dashboard' === $page;
			if ( $is_dashboard_page ) {
				$comp_args         = $args;
				$comp_args['days'] = $args['days'] * 2;

				$last_four_months_actions_count = ES()->actions_db->get_actions_count( $comp_args );
				
				$last_four_months_sent  = $last_four_months_actions_count['sent'];
				$sent_before_two_months = $last_four_months_sent - $total_message_sent;
				if ( $sent_before_two_months > 0 ) {
					$sent_percentage_growth = ( ( $total_message_sent - $sent_before_two_months ) / $sent_before_two_months ) * 100;
				} else {
					$sent_percentage_growth = 0;
				}
				
				$last_four_months_opens = $last_four_months_actions_count['opened'];
				$open_before_two_months = $last_four_months_opens - $total_email_opens;
				if ( $open_before_two_months > 0 ) {
					$open_percentage_growth = ( ( $total_email_opens - $open_before_two_months ) / $open_before_two_months ) * 100;
				} else {
					$open_percentage_growth = 0;
				}

				$last_four_months_clicks = $last_four_months_actions_count['clicked'];
				$click_before_two_months = $last_four_months_clicks - $total_links_clicks;
				if ( $click_before_two_months > 0 ) {
					$click_percentage_growth = ( ( $total_links_clicks - $click_before_two_months ) / $click_before_two_months ) * 100;
				} else {
					$click_percentage_growth = 0;
				}

				$last_four_months_unsubscribes  = $last_four_months_actions_count['unsubscribed'];
				$unsubscribes_before_two_months = $last_four_months_unsubscribes - $total_unsubscribed;
				if ( $unsubscribes_before_two_months > 0 ) {
					$unsubscribes_percentage_growth = ( ( $total_unsubscribed - $unsubscribes_before_two_months ) / $unsubscribes_before_two_months ) * 100;
				} else {
					$unsubscribes_percentage_growth = 0;
				}

				if ( isset( $actions_counts['hard_bounced'] ) ) {
					$total_hard_bounces             = $actions_counts['hard_bounced'];
					$last_four_months_hard_bounces  = $last_four_months_actions_count['hard_bounced'];
					$hard_bounces_before_two_months = $last_four_months_hard_bounces - $total_hard_bounces;
					if ( $hard_bounces_before_two_months > 0 ) {
						$hard_bounces_percentage_growth = ( ( $total_hard_bounces - $hard_bounces_before_two_months ) / $hard_bounces_before_two_months ) * 100;
					} else {
						$hard_bounces_percentage_growth = 0;
					}
	
					$reports_data['total_hard_bounced_contacts']    = number_format_i18n( $total_hard_bounces );
					$reports_data['hard_bounces_before_two_months'] = number_format_i18n( $hard_bounces_before_two_months );
					$reports_data['hard_bounces_percentage_growth'] = 0 !== $hard_bounces_percentage_growth ? number_format_i18n( $hard_bounces_percentage_growth, 2 ) : 0;
				}

				$reports_data['sent_percentage_growth']        = 0 !== $sent_percentage_growth ? number_format_i18n( $sent_percentage_growth, 2 ) : 0;
				$reports_data['sent_before_two_months']        = number_format_i18n( $sent_before_two_months );
				$reports_data['open_percentage_growth']        = 0 !== $open_percentage_growth ? number_format_i18n( $open_percentage_growth, 2 ) : 0;
				$reports_data['open_before_two_months']        = number_format_i18n( $open_before_two_months );
				$reports_data['click_percentage_growth']       = 0 !== $click_percentage_growth ? number_format_i18n( $click_percentage_growth, 2 ) : 0;
				$reports_data['click_before_two_months']       = number_format_i18n( $click_before_two_months );
				$reports_data['unsubscribe_percentage_growth'] = 0 !== $unsubscribes_percentage_growth ? number_format_i18n( $unsubscribes_percentage_growth, 2 ) : 0;
				$reports_data['unsubscribe_before_two_months'] = number_format_i18n( $unsubscribes_before_two_months );
				
			}

			$data = array_merge( $data, $reports_data );

			ES_Cache::set_transient( $cache_key, $data, 1 * HOUR_IN_SECONDS );

			return $data;
		}

		/**
		 * Get Campaigns Stats
		 *
		 * @return array
		 *
		 * @since 4.7.8
		 */
		public static function get_campaign_stats( $total_campaigns = 5 ) {

			global $wpdb;

			$campaigns = ES_DB_Mailing_Queue::get_recent_campaigns( $total_campaigns );

			$campaigns_data = array();
			if ( ! empty( $campaigns ) && count( $campaigns ) > 0 ) {

				foreach ( $campaigns as $key => $campaign ) {

					$message_id  = $campaign['id'];
					$campaign_id = $campaign['campaign_id'];

					if ( 0 === $campaign_id ) {
						continue;
					}

					$results = $wpdb->get_results( $wpdb->prepare( "SELECT type, count(DISTINCT (contact_id) ) as total FROM {$wpdb->prefix}ig_actions WHERE message_id = %d AND campaign_id = %d GROUP BY type", $message_id, $campaign_id ), ARRAY_A );

					$stats     = array();
					$type      = '';
					$type_text = '';

					if ( count( $results ) > 0 ) {

						foreach ( $results as $result ) {

							$type  = $result['type'];
							$total = $result['total'];

							switch ( $type ) {
								case IG_MESSAGE_SENT:
									$type_text = 'total_sent';
									break;
								case IG_MESSAGE_OPEN:
									$type_text = 'total_opens';
									break;
								case IG_LINK_CLICK:
									$type_text = 'total_clicks';
									break;
								case IG_CONTACT_UNSUBSCRIBE:
									$type_text = 'total_unsubscribe';
									break;
							}

							$stats[ $type_text ] = $total;
						}
					}

					$stats = wp_parse_args(
						$stats,
						array(
							'total_sent'        => 0,
							'total_opens'       => 0,
							'total_clicks'      => 0,
							'total_unsubscribe' => 0,
						)
					);

					if ( 0 != $stats['total_sent'] ) {
						$campaign_opens_rate  = ( $stats['total_opens'] * 100 ) / $stats['total_sent'];
						$campaign_clicks_rate = ( $stats['total_clicks'] * 100 ) / $stats['total_sent'];
						$campaign_losts_rate  = ( $stats['total_unsubscribe'] * 100 ) / $stats['total_sent'];
					} else {
						$campaign_opens_rate  = 0;
						$campaign_clicks_rate = 0;
						$campaign_losts_rate  = 0;
					}

					$campaign_type = ES()->campaigns_db->get_column( 'type', $campaign_id );

					if ( 'newsletter' === $campaign_type ) {
						$type = __( 'Broadcast', 'email-subscribers' );
					} elseif ( 'post_notification' === $campaign_type ) {
						$type = __( 'Post Notification', 'email-subscribers' );
					} elseif ( 'post_digest' === $campaign_type ) {
						$type = __( 'Post Digest', 'email-subscribers' );
					}

					$start_at  = gmdate( 'd F', strtotime( $campaign['start_at'] ) );
					$finish_at = gmdate( 'd F', strtotime( $campaign['finish_at'] ) );

					$campaigns_data[ $key ]                         = $stats;
					$campaigns_data[ $key ]['title']                = $campaign['subject'];
					$campaigns_data[ $key ]['hash']                 = $campaign['hash'];
					$campaigns_data[ $key ]['status']               = $campaign['status'];
					$campaigns_data[ $key ]['campaign_type']        = $campaign_type;
					$campaigns_data[ $key ]['type']                 = $type;
					$campaigns_data[ $key ]['total_sent']           = $stats['total_sent'];
					$campaigns_data[ $key ]['campaign_opens_rate']  = round( $campaign_opens_rate );
					$campaigns_data[ $key ]['campaign_clicks_rate'] = round( $campaign_clicks_rate );
					$campaigns_data[ $key ]['campaign_losts_rate']  = round( $campaign_losts_rate );
					$campaigns_data[ $key ]['start_at']             = $start_at;
					$campaigns_data[ $key ]['finish_at']            = $finish_at;
				}
			}
			$data['campaigns'] = $campaigns_data;

			return $data;
		}

		public static function can_show_campaign_stats( $source = '' ) {
			if ( 'es_dashboard' === $source && ! ES()->is_pro() ) {
				return false;
			}

			return true;
		}

		public static function get_top_performing_campaigns( $start_time, $campaign_count = 3 ) {
			global $wpdb;
			
			$top_campaigns = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT campaign_id,message_id, SUM( IF( `type` = 2, 1, 0 ) ) AS 'sent',SUM(IF( `type` = 3, 1, 0 )) AS 'opens_count', (SUM(IF( `type` = 3, 1, 0 ))/SUM( IF( `type` = 2, 1, 0 ))) * 100 AS opened_percentage FROM `{$wpdb->prefix}ig_actions` WHERE campaign_id IS NOT NULL AND message_id IS NOT NULL AND message_id != 0 AND updated_at > %d GROUP BY campaign_id, message_id ORDER BY `opened_percentage` DESC LIMIT %d",
					$start_time,
					$campaign_count
				),
				ARRAY_A
			);

			return $top_campaigns;
		}

		public static function show_device_opens_stats( $device_opens_data ) {
			
				//Graph for Device Opens
				$device_opened = array();
				$device_label  = array();
				ob_start();
			if ( ! empty( $device_opens_data ) && ! empty( array_filter( $device_opens_data ) ) ) {
				$device_label  = array_map( 'ucfirst' , array_keys( $device_opens_data ) );
				$device_opened = array_values( $device_opens_data );
					
				?>
			<div class="relative bg-white mt-2" id="device_open_graph"></div>
			
			<?php
			} else {
				?>
				<div class="mt-2 bg-white text-sm text-gray-500 py-3 px-6 tracking-wide">
					<?php echo esc_html__( 'No device data found', 'email-subscribers' ); ?>
				</div>
				<?php
			}
			$stats_html = ob_get_clean();
			$allowedtags     = ig_es_allowed_html_tags_in_esc();
			//$stats_html = ES_Common::get_tooltip_html( $stats_html );
			echo wp_kses( $stats_html, $allowedtags );
			?>
			<script type="text/javascript">

				jQuery(document).ready(function ($) {
					let device_data = {
						labels: <?php echo json_encode( $device_label ); ?>,
						datasets: [
							{
								name: "device", 
								type: "pie",
								values: <?php echo json_encode( $device_opened ); ?>,
							}
						]
					}

					const device_chart = new frappe.Chart("#device_open_graph", {
						title: "",
						data: device_data,
						type: 'pie',
						colors: ['#743ee2', '#5DADE2', '#F6608B'],
						height: 30,
						width:30,
						maxSlices: 3,
					});

				});
			</script>
			<?php
		}

		public static function show_sources_stats( $subscriber_source_counts ) {
			
			
			//Graph for Device Opens
			$source_opened = array();
			$source_label  = array();
			ob_start();
			if ( ! empty( $subscriber_source_counts ) && ! empty( array_filter( $subscriber_source_counts ) ) ) {
				$source_label  = array_map( 'ucfirst' , array_keys( $subscriber_source_counts ) );
				$source_opened = array_values( $subscriber_source_counts );
				?>
		<div class="bg-white mt-2" id="sources_graph"></div>
		
		<?php
			} else {
				?>
			<div class="mt-2 bg-white text-sm text-gray-500 py-3 px-6">
				<?php echo esc_html__( 'No source data found', 'email-subscribers' ); ?>
			</div>
			<?php
			}
		$stats_html = ob_get_clean();
		$allowedtags     = ig_es_allowed_html_tags_in_esc();
		//$stats_html = ES_Common::get_tooltip_html( $stats_html );
		echo wp_kses( $stats_html, $allowedtags );
			?>
		<script type="text/javascript">

			jQuery(document).ready(function ($) {
				let source_data = {
					labels: <?php echo json_encode( $source_label ); ?>,
					datasets: [
						{
							name: "source", 
							type: "percentage",
							values: <?php echo json_encode( $source_opened ); ?>,
						}
					]
				}

				const source_chart = new frappe.Chart("#sources_graph", {
					title: "",
					data: source_data,
					type: 'percentage',
					colors: ['#743ee2', '#5DADE2', '#F6608B'],
					height: 80,
					maxSlices: 3,
				});

			});
		</script>
		<?php
		}

		public static function show_unsubscribe_feedback_percentage_stats( $feedback_percentages ) {
			
			
			//Graph for Device Opens
			$unsubscribe_feedback_opened = array();
			$unsubscribe_feedback_label  = array();
			ob_start();
			if ( ! empty( $feedback_percentages ) && ! empty( array_filter( $feedback_percentages ) ) ) {
				$unsubscribe_feedback_label  = array_map( 'ucfirst' , array_keys( $feedback_percentages ) );
				$unsubscribe_feedback_opened = array_values( $feedback_percentages );
			
				?>
	<div class="relative bg-white mt-2 rounded-md shadow" id="unsubscribe_feedbacks_graph"></div>
	
		<?php
			} else {
				?>
		<div class="mt-2 bg-white text-sm text-gray-500 rounded-md shadow py-3 px-6 tracking-wide">
				<?php echo esc_html__( 'No data found', 'email-subscribers' ); ?>
		</div>
			<?php
			}
		$stats_html  = ob_get_clean();
		$allowedtags = ig_es_allowed_html_tags_in_esc();
		$stats_html  = ES_Common::get_tooltip_html( $stats_html );
		echo wp_kses( $stats_html, $allowedtags );
			?>
	<script type="text/javascript">

		jQuery(document).ready(function ($) {
			let unsubscribe_feedback_data = {
				labels: <?php echo json_encode( $unsubscribe_feedback_label ); ?>,
				datasets: [
					{
						name: "unsubscribe_feedback", 
						type: "pie",
						values: <?php echo json_encode( $unsubscribe_feedback_opened ); ?>,
					}
				]
			}

			const unsubscribe_feedback_chart = new frappe.Chart("#unsubscribe_feedbacks_graph", {
				title: "",
				data: unsubscribe_feedback_data,
				type: 'pie',
				colors: ['#743ee2', '#5DADE2', '#F6608B'],
				height: 280,
				maxSlices: 3,
			});

		});
	</script>
	<?php
		}
	}

}
