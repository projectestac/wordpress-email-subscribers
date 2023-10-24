<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Plugin_Usage_Data_Collector' ) ) {
	/**
	 * Class ES_Plugin_Usage_Data_Collector.
	 *
	 * @since 5.6.15
	 */
	class ES_Plugin_Usage_Data_Collector {
		
		public static $instance;
	
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
			add_filter( 'ig_es_tracking_data_params', array( __CLASS__, 'add_tracking_data' ) );
		}
	
		/**
		 * Method to add additional plugin usage tracking data specific to Icegram Express
		 *
		 * @param array $tracking_data
		 *
		 * @return array $tracking_data
		 *
		 * @since 4.7.7
		 */
		public static function add_tracking_data( $tracking_data = array() ) {
	
			$tracking_data['plugin_meta_info'] = self::get_ig_es_meta_info();
			$tracking_data['guid']             = ES()->cron->get_cron_guid();
			
			return $tracking_data;
		}
	
		/**
		 * Get plugin meta info
		 *
		 * @return array
		 *
		 * @since 4.1.0
		 */
		public static function get_ig_es_meta_info() {
	
			$plan 						= ES()->get_plan();
			$total_contacts             = ES()->contacts_db->count();
			$total_unconfirmed_contacts = ES()->lists_contacts_db->get_unconfirmed_contacts_count();
			$total_lists                = ES()->lists_db->count_lists();
			$total_forms                = ES()->forms_db->count_forms();
			$total_newsletters          = ES()->campaigns_db->get_total_newsletters();
			$total_post_notifications   = ES()->campaigns_db->get_total_post_notifications();
			$total_post_digests 		= ( 'pro' === $plan ) ? ES()->campaigns_db->get_total_post_digests() : 0;
			$total_sequences            = ( 'pro' === $plan ) ? ES()->campaigns_db->get_total_sequences() : 0;
			$active_workflows_count     = ES()->workflows_db->get_active_workflows_count();
			$remote_gallery_items   	= get_option('ig_es_imported_remote_gallery_template_ids', array());
			$editor_count_by_type		= ES()->campaigns_db->get_count_by_editor_type();
			$workflows_count_by_type	= ES()->workflows_db->get_workflows_count_by_triggername();
			$mailer_name 				= ES()->mailer->get_current_mailer_slug();
			$campaign_sending_frequency = ES_DB_Mailing_Queue::get_campaign_sending_frequency(10);
	
			$feedback_counts   = array();
			$custom_field_used = 'no';
			if ( 'pro' === $plan ) {
				$custom_fields     = ES()->custom_fields_db->get_custom_fields();
				$custom_field_used = ! empty( $custom_fields ) ? 'yes' : 'no';
				$number_of_days    = 180;
				$feedback_counts   = IG_ES_DB_Unsubscribe_Feedback::get_feedback_counts( $number_of_days );
			}
	
			$form_stats         = self::get_form_stats();
			$reports_stats      = self::get_campaign_wise_reports_stats();
			$survey_usage_stats = self::get_survey_usage_stats();
	
			return array(
				'version'                    => ES_PLUGIN_VERSION,
				'installed_on'               => get_option( 'ig_es_installed_on', '' ),
				'is_premium'                 => ES()->is_premium() ? 'yes' : 'no',
				'plan'                       => $plan,
				'is_trial'                   => ES()->trial->is_trial() ? 'yes' : 'no',
				'is_trial_expired'           => ES()->trial->is_trial_expired() ? 'yes' : 'no',
				'trial_start_at'             => ES()->trial->get_trial_start_date(),
				'total_contacts'             => $total_contacts,
				'unconfirmed_contacts'	     => $total_unconfirmed_contacts,		// Added in 5.5.7
				'total_lists'                => $total_lists,
				'total_forms'                => $total_forms,
				'total_newsletters'          => $total_newsletters,
				'total_post_notifications'   => $total_post_notifications,
				'total_post_digests'	     => $total_post_digests,				// Added in 5.5.7
				'total_sequences'            => $total_sequences,
				'editor_count_by_type'	     => $editor_count_by_type, 			// Added in 5.5.7
				'active_workflows_count'     => $active_workflows_count, 			// Added in 5.5.7
				'campaign_sending_frequency' => $campaign_sending_frequency,	// Added in 5.5.7
				'workflows_count_by_type'    => $workflows_count_by_type, 		// Added in 5.5.7
				'mailer'				     => $mailer_name,						// Added in 5.5.7
				'remote_gallery_items'	     => $remote_gallery_items, 			// Added in 5.5.7
				'feedback_counts'            => $feedback_counts,               // Added in 5.6.15
				'is_custom_field_used'       => $custom_field_used,             // Added in 5.6.15
				'form_stats'                 => $form_stats,                    // Added in 5.6.15
				'reports_stats'              => $reports_stats,                 // Added in 5.6.15
				'survey_usage_stats'         => $survey_usage_stats,            // Added in 5.6.15
				'is_rest_api_used'	         => self::is_rest_api_used(),		 	// Added in 5.5.7
				'settings'                   => self::get_all_settings(),
			);
		}

		/**
		 * Get all ES settings
		 *
		 * @return array
		 *
		 * @since 4.1.0
		 */
		public static function get_all_settings() {

			global $wpdb;

			$option_name_like = 'ig_es_%';
			$results          = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE %s  AND option_name != %s", $option_name_like, 'ig_es_managed_blocked_domains' ), ARRAY_A );

			$options_name_value_map = array();
			if ( count( $results ) > 0 ) {
				$restricted_settings = ES_Common::get_restricted_settings();
				foreach ( $results as $result ) {

					if ( in_array( $result['option_name'], $restricted_settings ) ) {
						continue;
					}

					$options_name_value_map[ $result['option_name'] ] = $result['option_value'];
				}
			}

			return $options_name_value_map;
		}

		/**
		 * Check if WordPress User is using Rest API feature
		 * 
		 * @since 5.5.7
		 * 
		 * @return string yes|no
		 */
		public static function is_rest_api_used() {
			
			// Check if REST API settings option is enabled
			$is_api_enabled = get_option('ig_es_allow_api', 'no');

			if ( 'no' === $is_api_enabled ) {
				return 'no';
			}

			// Ensure there is atleast one users for whom REST API keys are generated
			$rest_api_users_ids = get_users( array(
				'meta_key' => 'ig_es_rest_api_keys',
				'fields'   => 'ID'
			) );

			if ( empty( $rest_api_users_ids ) ) {
				return 'no';
			}
			
			return 'yes';

		}
	
		public static function get_form_stats() {
			$forms = ES()->forms_db->get_all();
			
			$form_style_usage_counts = self::get_form_style_usage_counts( $forms );
			$form_embed_usage_counts = self::get_form_embed_usage_counts( $forms );
			$form_popup_usage_counts = self::get_form_popup_usage_counts( $forms );

			$form_stats = array(
				'form_counts'             => count( $forms ),
				'form_style_usage_counts' => $form_style_usage_counts,
				'form_embed_usage_counts' => $form_embed_usage_counts,
				'form_popup_usage_counts' => $form_popup_usage_counts,
			);

			return $form_stats;
		}
	
		public static function get_form_style_usage_counts( $forms = array() ) {
			$form_style_usage_counts = array();
			if ( ! empty( $forms ) ) {
				foreach ( $forms as $form ) {
					$form_setting = maybe_unserialize( $form['settings'] );
					$editor_type  = ! empty( $form_setting['editor_type'] ) ? $form_setting['editor_type'] : IG_ES_CLASSIC_EDITOR;
					if ( IG_ES_DRAG_AND_DROP_EDITOR !== $editor_type ) {
						continue;
					}
					$form_style = ! empty( $form_setting['form_style'] ) ? $form_setting['form_style'] : 'theme-styling';
					if ( ! isset( $form_style_usage_counts[ $form_style ] ) ) {
						$form_style_usage_counts[ $form_style ] = 0;
					}
					$form_style_usage_counts[ $form_style ]++;
				}
			}
			return $form_style_usage_counts;
		}
	
		public static function get_form_embed_usage_counts( $forms = array() ) {
			$form_embed_counts = 0;
			if ( ! empty( $forms ) ) {
				foreach ( $forms as $form ) {
					$form_setting          = maybe_unserialize( $form['settings'] );
					$is_embed_form_enabled = ! empty( $form_setting['is_embed_form_enabled'] ) ? $form_setting['is_embed_form_enabled'] : 'no';
					if ( 'yes' === $is_embed_form_enabled ) {
						$form_embed_counts++;
					}
				}
			}
			return $form_embed_counts;
		}

		public static function get_form_popup_usage_counts( $forms = array() ) {
			$form_popup_counts = 0;
			if ( ! empty( $forms ) ) {
				foreach ( $forms as $form ) {
					$form_setting  = maybe_unserialize( $form['settings'] );
					$show_in_popup = ! empty( $form_setting['show_in_popup'] ) ? $form_setting['show_in_popup'] : 'no';
					if ( 'yes' === $show_in_popup ) {
						$form_popup_counts++;
					}
				}
			}
			return $form_popup_counts;
		}

		public static function get_survey_usage_stats() {
			global $wpbd;
			$regex     = '[{][{] *campaign.survey([^}]*)[}][}]';
			$raw_query = "SELECT
			SUM(IF (type = %s,1, 0) ) AS %s, 
			SUM(IF (type = %s,1, 0) ) AS %s,
			SUM(IF (type = %s,1, 0) ) AS %s
			FROM `{$wpbd->prefix}ig_campaigns` WHERE `body` REGEXP %s";

			$survey_usage_stats = $wpbd->get_row(
				$wpbd->prepare(
					$raw_query, 
					array(
						IG_CAMPAIGN_TYPE_NEWSLETTER,
						IG_CAMPAIGN_TYPE_NEWSLETTER,
						IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
						IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
						IG_CAMPAIGN_TYPE_POST_DIGEST,
						IG_CAMPAIGN_TYPE_POST_DIGEST,
						$regex
					)
				),
				ARRAY_A
			);

			return $survey_usage_stats;
		}
		
		public static function get_campaign_wise_reports_stats() {
			global $wpbd;
			$raw_query = "SELECT
			SUM(IF (`meta` LIKE '%\"type\";s:10:\"newsletter\"%',1, 0) ) AS `newsletter`, 
			SUM(IF (`meta` LIKE '%\"type\";s:17:\"post_notification\"%',1, 0) ) AS `post_notification`,
			SUM(IF (`meta` LIKE '%\"type\";s:11:\"post_digest\"%',1, 0) ) AS `post_digest`
			FROM `{$wpbd->prefix}ig_mailing_queue`";

			$reports_stats = $wpbd->get_row(
				$wpbd->prepare(
					$raw_query
				),
				ARRAY_A
			);

			return $reports_stats;
		}
	}
	
	ES_Plugin_Usage_Data_Collector::get_instance();
}
