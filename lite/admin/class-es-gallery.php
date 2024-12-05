<?php

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Gallery' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Admin Settings
	 *
	 * @package    Email_Subscribers
	 * @subpackage Email_Subscribers/admin
	 */
	class ES_Gallery {
	
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
			add_action( 'admin_init', array( $this, 'import_gallery_item' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Register the JavaScript for ES gallery.
		 */
		public function enqueue_scripts() {

			$current_page = ig_es_get_request_data( 'page' );

			if ( in_array( $current_page, array( 'es_gallery', 'es_campaigns' ), true ) ) {
				wp_register_script( 'mithril', plugins_url( '/js/mithril.min.js', __FILE__ ), array(), '2.0.4', true );
				wp_enqueue_script( 'mithril' );

				$campaign_types        = ES_Common::get_campaign_type_key_name_map();
				$main_js_data = array(
					'dnd_editor_slug'                 => esc_attr( IG_ES_DRAG_AND_DROP_EDITOR ),
					'classic_editor_slug'             => esc_attr( IG_ES_CLASSIC_EDITOR ),
					'post_notification_campaign_type' => esc_attr( IG_CAMPAIGN_TYPE_POST_NOTIFICATION ),
					'newsletter_campaign_type'        => esc_attr( IG_CAMPAIGN_TYPE_NEWSLETTER ),
					'post_digest_campaign_type'       => esc_attr( IG_CAMPAIGN_TYPE_POST_DIGEST ),
					'sequence_campaign_type'          => esc_attr( IG_CAMPAIGN_TYPE_SEQUENCE ),
					'workflow_campaign_type'          => esc_attr( IG_CAMPAIGN_TYPE_WORKFLOW ),
					'local_gallery_type'              => 'local',
					'remote_gallery_type'             => 'remote',
					'es_plan'						  => ES()->get_plan(),
					'image_path'			  		  => ES_PLUGIN_URL,
					'campaign_types'                  => $campaign_types,
					'tags'                            => ES_Common::get_tags(),
				);

				if ( 'es_campaigns' === $current_page ) {

					$campaign_status_names = ES_Common::get_campaign_statuses_key_name_map();
					$campaign_status_codes = ES_Common::get_campaign_status_code_map();
					$post_categories       = ES_Common::get_post_categories();
					$post_types_name       = ES_Common::get_post_types_name();
					$optimization_option   = ES_Common::get_optimization_details();
					
					$main_js_data['campaign_status_names'] = $campaign_status_names;
					$main_js_data['campaign_status_codes'] = $campaign_status_codes;
					$main_js_data['post_categories']       = $post_categories;

					if ( ! empty( $post_types_name) && ES()->is_pro() ) {
						$custom_post_types_categories = array();
						$custom_post_types = array_keys( $post_types_name );
						foreach ( $custom_post_types as $custom_post_type ) {
							$custom_post_type_categories = ES_Common::get_post_type_categories( $custom_post_type );
							if ( ! empty( $custom_post_type_categories ) ) {
								$custom_post_types_categories[ $custom_post_type ] = $custom_post_type_categories;
							}
						}
						if ( ! empty( $custom_post_types_categories ) ) {
							$main_js_data['custom_post_types_categories'] = $custom_post_types_categories;
						}
					}

					$main_js_data['post_types_name'] = $post_types_name;
					
					$recipient_rules_obj = new ES_Recipient_Rules();
					$recipient_rules     = $recipient_rules_obj->get_rules();
					
					$main_js_data['recipient_rules'] = $recipient_rules;
					$campaigns_default_data = array();
					
					foreach ( $campaign_types as $campaign_type => $campaign_name ) {

						$campaign_default_data = ES_Common::get_campaign_default_data( $campaign_type );
						$campaigns_default_data[ $campaign_type ] = $campaign_default_data;

					}

					$main_js_data['campaigns_default_data'] = $campaigns_default_data;

					$from_email = ES_Common::get_ig_option( 'from_email' );
					$from_name = ES_Common::get_ig_option( 'from_name' );

					$main_js_data['sender_details']['from_email'] = $from_email;
					$main_js_data['sender_details']['reply_to_email'] = $from_email;
					$main_js_data['sender_details']['from_name'] = $from_name;
					$main_js_data['sender_details']['reply_to_name'] = $from_name;

					$main_js_data['tracking_details']['is_track_email_opens'] =get_option( 'ig_es_track_email_opens', 'yes' );
					$main_js_data['tracking_details']['ig_es_track_link_clicks'] = get_option( 'ig_es_track_link_click', 'no' );
					$main_js_data['tracking_details']['ig_es_track_utm'] = get_option( 'ig_es_track_utm', 'no' );
					$main_js_data['optimization_option'] = $optimization_option;
					
				}

				if ( ! wp_script_is( 'wp-i18n' ) ) {
					wp_enqueue_script( 'wp-i18n' );
				}

				wp_register_script( 'ig-es-main-js', plugins_url( '/dist/index.js', __FILE__ ), array( 'mithril' ), '2.0.4', true );

				// wp_register_script( 'ig-es-main-js', plugins_url( '/dist/main.js', __FILE__ ), array( 'mithril' ), '2.0.4', true );
				wp_enqueue_script( 'ig-es-main-js' );
				wp_localize_script( 'ig-es-main-js', 'ig_es_main_js_data', $main_js_data );
			}
		}

		public function import_gallery_item() {

			$action = ig_es_get_request_data( 'action' );
			
			
			if ( 'ig_es_import_gallery_item' === $action ) {
				check_admin_referer( 'ig-es-admin-ajax-nonce' );
				$gallery_type         = ig_es_get_request_data( 'gallery-type' );
				$template_id          = ig_es_get_request_data( 'template-id' );
				$campaign_id          = ig_es_get_request_data( 'campaign-id' );
				$campaign_type        = ig_es_get_request_data( 'campaign-type' );
				$imported_campaign_id = ES_Gallery_Controller::import_gallery_item_handler( $gallery_type, $template_id, $campaign_type, $campaign_id );
				if ( ! empty( $imported_campaign_id ) ) {
					$redirect_url = admin_url( 'admin.php?page=es_campaigns#!/campaign/edit/' . $imported_campaign_id );
					wp_safe_redirect( $redirect_url );
					exit();
				}
			} elseif ( 'ig_es_import_remote_gallery_template' === $action ) {
				check_admin_referer( 'ig-es-admin-ajax-nonce' );
				$template_id = ig_es_get_request_data( 'template-id' );
				$imported_template_id = ES_Gallery_Controller::import_remote_gallery_template( $template_id );
				if ( ! empty( $imported_template_id ) ) {
					$redirect_url = admin_url( 'admin.php?page=es_campaigns#!/template/edit/' . $imported_template_id );
					wp_safe_redirect( $redirect_url );
					exit();
				}
			} elseif ( 'ig_es_duplicate_template' === $action ) {
				check_admin_referer( 'ig-es-admin-ajax-nonce' );
				$template_id = ig_es_get_request_data( 'template-id' );
				$duplicate_template_id = ES_Gallery_Controller::duplicate_template( $template_id );
				if ( ! empty( $duplicate_template_id ) ) {
					$redirect_url = admin_url( 'admin.php?page=es_campaigns#!/template/edit/' . $duplicate_template_id );
					wp_safe_redirect( $redirect_url );
					exit();
				}
			}
		}
	}

}

ES_Gallery::get_instance();
