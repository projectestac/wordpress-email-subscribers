<?php

if ( ! class_exists( 'ES_Template_Controller' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Template_Controller
	 */
	class ES_Template_Controller {

		// class instance
		public static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public static function get_template( $data ) {
			$response    = array();
			$template_id = $data['templateId'];
			if ( ! empty( $template_id ) ) {
				$template = get_post( $template_id );
				if ( $template ) {
					$template_meta = get_post_custom( $template_id );
					if ( ! empty( $template_meta ) ) {
						foreach ( $template_meta as $meta_key => $meta_value ) {
							if ( 'es_dnd_editor_data' === $meta_key ) {
								$template_meta[ $meta_key ] = wp_json_encode( maybe_unserialize( $meta_value[0] ) );
							} else {
								$template_meta[ $meta_key ] = $meta_value[0];
							}
						}
					}
					if ( empty( $template_meta['es_editor_type'] ) ) {
						$template_meta['es_editor_type'] = IG_ES_CLASSIC_EDITOR;
					}
					$response['id']      = $template->ID;
					$response['subject'] = $template->post_title;
					$response['body']    = $template->post_content;
					$response['meta']    = $template_meta;
				}
			}
			return $response;
		}

		public static function save( $template_data ) {
			$response = array();
			$template_id            = ! empty( $template_data['id'] ) ? $template_data['id'] : 0;
			$template_type          = ! empty( $template_data['meta']['es_template_type'] ) ? $template_data['meta']['es_template_type'] : IG_CAMPAIGN_TYPE_NEWSLETTER;
			$template_body          = ! empty( $template_data['body'] ) ? $template_data['body'] : '';
			$template_subject       = ! empty( $template_data['subject'] ) ? $template_data['subject'] : '';
			$template_attachment_id = ! empty( $template_data['template_attachment_id'] ) ? $template_data['template_attachment_id'] : '';
			$template_status        = 'publish';

			$data = array(
				'post_title'   => $template_subject,
				'post_content' => $template_body,
				'post_type'    => 'es_template',
				'post_status'  => $template_status,
			);

			if ( empty( $template_id ) ) {
				$template_id = wp_insert_post( $data );
			} else {
				$data['ID']  = $template_id;
				$template_id = wp_update_post( $data );
			}

			$is_template_added = ! ( $template_id instanceof WP_Error );

			if ( $is_template_added ) {

				$response['templateId'] = $template_id;

				if ( ! empty( $template_attachment_id ) ) {
					set_post_thumbnail( $template_id, $template_attachment_id );
				}

				$editor_type = ! empty( $template_data['meta']['es_editor_type'] ) ? $template_data['meta']['es_editor_type'] : '';

				$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

				if ( $is_dnd_editor ) {
					$dnd_editor_data = array();
					if ( ! empty( $template_data['meta']['es_dnd_editor_data'] ) ) {
						$dnd_editor_data = $template_data['meta']['es_dnd_editor_data'];
						$dnd_editor_data = json_decode( $dnd_editor_data );
						update_post_meta( $template_id, 'es_dnd_editor_data', $dnd_editor_data );
					}
				} else {
					$custom_css = ! empty( $template_data['meta']['es_custom_css'] ) ? $template_data['meta']['es_custom_css'] : '';
					update_post_meta( $template_id, 'es_custom_css', $custom_css );
				}

				update_post_meta( $template_id, 'es_editor_type', $editor_type );
				update_post_meta( $template_id, 'es_template_type', $template_type );
			}
			return $response;
		}
	}
}

ES_Template_Controller::get_instance();
