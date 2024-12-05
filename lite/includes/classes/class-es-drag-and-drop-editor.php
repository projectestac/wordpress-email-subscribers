<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Drag_And_Drop_Editor {

	public static $instance;

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public static function is_dnd_editor_page() {

		$is_dnd_editor_page = false;

		if ( ES()->is_es_admin_screen() ) {
			
			$current_page = ig_es_get_request_data( 'page' );

			$edit_campaign_pages = array(
				'es_notifications',
				'es_newsletters',
				'es_campaigns'
			);
	
			$is_edit_campaign_page = in_array( $current_page, $edit_campaign_pages, true );
	
			if ( $is_edit_campaign_page ) {
				$is_dnd_editor_page = true;
			} else {
				if ( 'es_forms' === $current_page ) {
					$action 	 = ig_es_get_request_data( 'action' );
					$is_new_form = 'new' === $action;
					if ( $is_new_form ) {
						$is_dnd_editor_page = true;
					} else {
						$form_id = ig_es_get_request_data( 'form' );
						if ( ! empty( $form_id ) ) {
							$form_data          = ES()->forms_db->get( $form_id );
							$settings_data      = maybe_unserialize( $form_data['settings'] );
							$editor_type        = ! empty( $settings_data['editor_type'] ) ? $settings_data['editor_type'] : '';
							$is_dnd_editor_page = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
						}
					}
				} elseif ( 'es_template' === $current_page ) {
					$action 	 = ig_es_get_request_data( 'action' );
					$is_new_template = 'new' === $action;
					if ( $is_new_template ) {
						$is_dnd_editor_page = true;
					} else {
						$template_id = ig_es_get_request_data( 'id' );
						if ( ! empty( $template_id ) ) {
							$editor_type        = get_post_meta( $template_id, 'es_editor_type', true );
							$is_dnd_editor_page = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
						}
					}
				}
			}
		}
		
		return $is_dnd_editor_page;

	}

	public function enqueue_scripts() {

		if ( ! self::is_dnd_editor_page() ) {
			return;
		}
		
		$current_page = ig_es_get_request_data( 'page' );
		//Only for development - this branch only
		if ( defined( 'IG_ES_DEV_MODE' ) && IG_ES_DEV_MODE ) {
			wp_register_script( 'es_editor_js', 'http://localhost:9000/main.js', array(), time(), false );
		} else {
			$js_file_name = '';
			if ( 'es_forms' === $current_page) {
				//TODO: Change this when form UI is updated with new UI
				$js_file_name = 'form-editor.js';
			} else {
				$js_file_name = 'editor.js';
			}
			wp_register_script( 'es_editor_js', ES_PLUGIN_URL . 'lite/admin/js/' . $js_file_name, array( ), ES_PLUGIN_VERSION, false );
		}
		
		if ( 'es_forms' === $current_page ) {

			$lists = ES()->lists_db->get_lists();
			
			//Get the site tags for DND editor
			$campaign_admin  = ES_Campaign_Admin::get_instance();
			$site_tags       = $campaign_admin->get_dnd_site_tags();

			$form_editor_data = array(
				'plan'     => ES()->get_plan(),
				'site_url' => home_url(),
				'siteTags' => $site_tags,
				'lists'    => $lists,
				'i18n'     => array(
					'no_list_selected_message' => __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' ),
				),
			);

			$form_editor_data = apply_filters( 'ig_es_form_editor_data', $form_editor_data );

			wp_localize_script( 'es_editor_js', 'ig_es_form_editor_data', $form_editor_data );
		} else {
			$campaign_admin  = ES_Campaign_Admin::get_instance();
			$campaign_tags   = $campaign_admin->get_dnd_campaign_tags();
			$subscriber_tags = $campaign_admin->get_dnd_subscriber_tags();
			$site_tags       = $campaign_admin->get_dnd_site_tags();
			$campaign_editor_data = array(
				'classicEditor'   => IG_ES_CLASSIC_EDITOR,
				'dndEditor'       => IG_ES_DRAG_AND_DROP_EDITOR,
				'postTags'        => $campaign_tags['post_notification'],
				'subscriberTags'  => $subscriber_tags,
				'siteTags'        => $site_tags,
				'isPro'		      => ES()->is_pro(),
				'plugin_url'      => ES_PLUGIN_URL,
				'i18n'            => array(
					'survey_block_heading_text'           => __( 'Happy with our products and services?', 'email-subscribers' ),
					'survey_block_default_thank_you_text' => __( 'Thank you for your response! Your feedback is highly appreciated.', 'email-subscribers' ),
					'survey_type_field_label'             => __( 'Survey type', 'email-subscribers' ),
					'survey_message_field_label'          => __( 'Survey message', 'email-subscribers' ),
				),
			);

			$campaign_editor_data = apply_filters( 'ig_es_campaign_editor_data', $campaign_editor_data );

			wp_localize_script( 'es_editor_js', 'ig_es_campaign_editor_data', $campaign_editor_data );
		}

		wp_enqueue_script( 'es_editor_js' );
		wp_enqueue_media();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    4.0
	 */
	public function enqueue_styles() {

		if ( ! self::is_dnd_editor_page() ) {
			return;
		}
		
		if ( defined( 'IG_ES_DEV_MODE' ) && IG_ES_DEV_MODE ) {
			wp_enqueue_style( 'es_editor_css', 'http://localhost:9000/main.css', array(), time(), 'all' );
		} else {
			$css_file_name = '';
			$current_page = ig_es_get_request_data('page');
			if ( 'es_forms' === $current_page) {
				//TODO: Change this when form UI is updated with new UI
				$css_file_name = 'form-editor.css';
			} else {
				$css_file_name = 'editor.css';
			}
			wp_enqueue_style( 'es_editor_css', ES_PLUGIN_URL . 'lite/admin/css/' . $css_file_name, array(), ES_PLUGIN_VERSION, 'all' );
		}
	}

	public function show_editor( $editor_args = array() ) {
		$editor_attributes = ! empty( $editor_args['attributes'] ) ? $editor_args['attributes'] : array();
		?>
		<div id="ig-es-dnd-builder"
			<?php
			if ( ! empty( $editor_attributes ) ) :
				foreach ( $editor_attributes as $arg_key => $arg_value ) :
					echo esc_attr( $arg_key ) . '="' . esc_attr( $arg_value ) . '" ';
				endforeach;
			endif;
			?>
		>
		</div>
		<?php
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

ES_Drag_And_Drop_Editor::get_instance();
