<?php

if ( ! class_exists( 'ES_Gallery_Controller' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Gallery_Controller
	 */
	class ES_Gallery_Controller {

		// class instance
		public static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get campaign templates
		 */
		public static function get_gallery_items() {
	
			$response = array();
			$gallery_items = array();
			$blog_charset = get_option( 'blog_charset' );

			$campaign_templates = ES_Common::get_templates();
			
			if ( !empty( $campaign_templates ) ) {
				foreach ( $campaign_templates as $campaign_template) {
					$template_slug = $campaign_template->post_name;
					$editor_type = get_post_meta( $campaign_template->ID, 'es_editor_type', true );
					$categories = array();
					$gallery_item['ID'] = $campaign_template->ID;
					$gallery_item['title'] = html_entity_decode( $campaign_template->post_title, ENT_QUOTES, $blog_charset );
					$gallery_item['type'] = get_post_meta( $campaign_template->ID, 'es_template_type', true );
					$gallery_item['editor_type'] = !empty($editor_type) ? $editor_type : IG_ES_CLASSIC_EDITOR;
					$gallery_type  = 'local';
					$categories[] = !empty($gallery_item['type']) ?  $gallery_item['type'] : IG_CAMPAIGN_TYPE_NEWSLETTER;
					$categories[] = !empty($editor_type) ? $editor_type : IG_ES_CLASSIC_EDITOR;
					$gallery_item['categories'] = $categories;
					$thumbnail_url = ( ! empty( $campaign_template->ID ) ) ? get_the_post_thumbnail_url(
						$campaign_template->ID,
						array(
							'200',
							'200',
						) ): '';
					$gallery_item['thumbnail'] = ( !empty ($thumbnail_url) ) ? $thumbnail_url : '';
					$gallery_item['gallery_type'] = $gallery_type;
					$gallery_items[$template_slug] = $gallery_item;
				}
			}

			$remote_gallery_items = self::get_remote_gallery_items();
			if ( ! empty( $remote_gallery_items ) ) {
				foreach ( $remote_gallery_items as $item ) {
					$template_version = $item->template_version;
					if ( in_array( $template_version, array('1.0.0', '1.0.1') ) ) {
						$template_slug = $item->slug;
						// Don't add remote template if local template with same slug already exists. This is to avoid duplicates.
						if ( isset( $gallery_items[ $template_slug ] ) ) {
							continue;
						}
						$item_id       = $item->id;
						$item_title    = $item->title->rendered;
						$item_title    = html_entity_decode( $item_title, ENT_QUOTES, $blog_charset );
						$thumbnail_url = ! empty( $item->thumbnail->guid ) ? $item->thumbnail->guid : '';
						$editor_type   = ! empty( $item->es_editor_type ) ? $item->es_editor_type : IG_ES_CLASSIC_EDITOR;
						$campaign_type = ! empty( $item->es_template_type ) ? $item->es_template_type : IG_CAMPAIGN_TYPE_NEWSLETTER;
						$es_plan       = ! empty( $item->es_plan ) ? $item->es_plan : 'lite';
						$gallery_type  = 'remote';
						$template_version = ! empty( $item->template_version ) ? $item->template_version : '1.0.0';
						
						$categories = array(
							$campaign_type,
							$editor_type
						);

						if ( 'lite' !== $es_plan ) {
							$categories[] = $es_plan;
						}

						$gallery_items[$template_slug] = array(
							'ID'           		=> $item_id,
							'title'        		=> $item_title,
							'thumbnail'    		=> $thumbnail_url,
							'categories'   		=> $categories,
							'type'		   		=> $campaign_type,
							'editor_type'  		=> $editor_type,
							'gallery_type' 		=> 'remote',
							'es_plan'      		=> $es_plan,
							'template_version'	=> $template_version,
						);
					}
				}
			}
			
			$response['items'] = array_values( $gallery_items );

			wp_send_json_success( $response );
		}

		public static function get_remote_gallery_items() {
			$remote_gallery_items_updated = get_transient( 'ig_es_remote_gallery_items_updated' );
			if ( ! $remote_gallery_items_updated ) {
				$remote_gallery_items_url = 'https://icegram.com/gallery/wp-json/wp/v2/es_gallery_item?filter[posts_per_page]=200';
				$request_args = array(
					'timeout' => 15
				);
				$response = wp_remote_get( $remote_gallery_items_url, $request_args );
				if ( ! is_wp_error( $response ) ) {
					$json_response = wp_remote_retrieve_body( $response );
					if ( ! empty( $json_response ) && ES_Common::is_valid_json( $json_response ) ) {
						$gallery_items = json_decode( $json_response );
						if ( is_array( $gallery_items ) ) {
							$updated = update_option( 'ig_es_remote_gallery_items', $gallery_items, 'no' );
							if ( $updated ) {
								set_transient( 'ig_es_remote_gallery_items_updated', time(), 24 * HOUR_IN_SECONDS ); // 1 day
							}
							return $gallery_items;
						}
					}
				} 
			}

			$remote_gallery_items = get_option( 'ig_es_remote_gallery_items', array() );
			return $remote_gallery_items;
		}

		public static function get_remote_gallery_item( $item_id ) {

			$gallery_item = array();
			if ( empty( $item_id ) ) {
				return $gallery_item;
			}

			$request_args = array(
				'timeout' => 15
			);
			$remote_gallery_item_url = 'https://icegram.com/gallery/wp-json/wp/v2/es_gallery_item/' . $item_id;
			$response                = wp_remote_get( $remote_gallery_item_url, $request_args );
			
			if ( ! is_wp_error( $response ) ) {
				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
					$json_response = wp_remote_retrieve_body( $response );
					if ( ! empty( $json_response ) && ES_Common::is_valid_json( $json_response ) ) {
						$gallery_item = json_decode( $json_response );
					}
				}
			}
			
			return $gallery_item;
		}

		public static function import_gallery_item_handler( $gallery_type, $template_id, $campaign_type, $campaign_id = 0 ) {
			
			if ( 'remote' === $gallery_type ) {
				$campaign_id = self::import_remote_gallery_item( $template_id, $campaign_type, $campaign_id );
			} else {
				$campaign_id = self::import_local_gallery_item( $template_id, $campaign_type, $campaign_id );
			}
			
			return $campaign_id;
		}

		public static function import_local_gallery_item( $template_id, $campaign_type, $campaign_id = 0 ) {
			if ( ! empty( $template_id ) ) {
				$template = get_post( $template_id );
				if ( ! empty( $template ) ) {
					$subject     = $template->post_title;
					$content     = $template->post_content;
					$from_email  = ES_Common::get_ig_option( 'from_email' );
					$from_name   = ES_Common::get_ig_option( 'from_name' );
					$editor_type = get_post_meta( $template_id, 'es_editor_type', true );
					
					if ( empty( $editor_type ) ) {
						$editor_type = IG_ES_CLASSIC_EDITOR;
					}

					$campaign_meta = array(
						'editor_type' => $editor_type,
					);

					if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type ) {
						$dnd_editor_data = get_post_meta( $template_id, 'es_dnd_editor_data', true );
						if ( ! empty( $dnd_editor_data ) ) {
							$campaign_meta['dnd_editor_data'] = wp_json_encode( $dnd_editor_data );
						}
					} else {
						if ( false === strpos( $content, '<html' ) ) {
							// In classic edior, we need to add p tag to content when not already added.
							$content = wpautop( $content );
						}
						$custom_css = get_post_meta( $template_id, 'es_custom_css', true );
						if ( ! empty( $custom_css ) ) {
							$campaign_meta['es_custom_css'] = $custom_css;
						}
					}

					$campaign_meta = maybe_serialize( $campaign_meta );

					$campaign_data = array(
						'name'       => $subject,
						'subject'    => $subject,
						'slug'       => sanitize_title( sanitize_text_field( $subject ) ),
						'body'       => $content,
						'from_name'  => $from_name,
						'from_email' => $from_email,
						'type'       => $campaign_type,
						'meta'		 => $campaign_meta,
					);

					if ( ! empty( $campaign_id ) ) {
						ES()->campaigns_db->update( $campaign_id, $campaign_data );
					} else {
						$campaign_id = ES()->campaigns_db->save_campaign( $campaign_data );
						if ( in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_POST_NOTIFICATION, IG_CAMPAIGN_TYPE_POST_DIGEST ), true ) ) {
							ES_Campaign_Controller::add_to_new_category_format_campaign_ids( $campaign_id );
						}
					}

				}
			}

			return $campaign_id;
		}

		public static function import_remote_gallery_item( $template_id, $campaign_type, $campaign_id = 0 ) {
			$gallery_item  = self::get_remote_gallery_item( $template_id );
			if ( empty( $gallery_item ) ) {
				return $campaign_id;
			}

			$template_version = ! empty( $gallery_item->template_version ) ? $gallery_item->template_version : '';
			
			if ( in_array( $template_version, array('1.0.0', '1.0.1') ) ) {
				$subject       = $gallery_item->title->rendered;
				$content       = $gallery_item->content->rendered;
				$from_email    = ES_Common::get_ig_option( 'from_email' );
				$from_name     = ES_Common::get_ig_option( 'from_name' );
				$editor_type   = ! empty( $gallery_item->es_editor_type ) ? $gallery_item->es_editor_type : IG_ES_CLASSIC_EDITOR;
				$campaign_meta = array(
					'editor_type' => $editor_type,
				);
				if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type ) {
					$dnd_editor_data = maybe_unserialize( $gallery_item->es_dnd_editor_data );
					if ( ! empty( $dnd_editor_data ) ) {
						$campaign_meta['dnd_editor_data'] = $gallery_item->es_dnd_editor_data;
					}
				} else {
					if ( false === strpos( $content, '<html' ) ) {
						// In classic edior, we need to add p tag to content when not already added.
						$content = wpautop( $content );
					}

					$custom_css = ! empty( $gallery_item->es_custom_css ) ? $gallery_item->es_custom_css : '';
					if ( ! empty( $custom_css ) ) {
						$campaign_meta['es_custom_css'] = $custom_css;
					}
				}

				
				$campaign_meta = maybe_serialize( $campaign_meta );

				preg_match_all( '#<img\s+(?:[^>]*?\s+)?src=(\'|")?(https?[^\'"]+)(\'|")?#', $content, $image_urls );
				$image_urls = ! empty( $image_urls[2] ) ? $image_urls[2] : array();
				if ( ! empty( $image_urls ) ) {
					foreach ( $image_urls as $image_url ) {
						$is_ig_image_link = false !== strpos( $image_url , 'icegram.com' );
						if ( $is_ig_image_link ) {
							$new_image_url = ES_Common::download_image_from_url( $image_url );
							if ( ! empty( $new_image_url ) ) {
								$old_url       = ' src="' . $image_url . '"';
								$new_url       = ' src="' . $new_image_url . '"';
								$pos           = strpos( $content, $old_url );
								if ( false !== $pos ) {
									$content = preg_replace( '/' . preg_quote( $old_url, '/' ) . '/', $new_url, $content, 1 );
								}
							}
						}
					}
				}

				$campaign_data = array(
					'name'       => $subject,
					'subject'    => $subject,
					'slug'       => sanitize_title( sanitize_text_field( $subject ) ),
					'body'       => $content,
					'from_name'  => $from_name,
					'from_email' => $from_email,
					'type'       => $campaign_type,
					'meta'		 => $campaign_meta,
				);

				if ( ! empty( $campaign_id ) ) {
					ES()->campaigns_db->update( $campaign_id, $campaign_data );
				} else {
					$campaign_id = ES()->campaigns_db->save_campaign( $campaign_data );
					if ( ! empty( $campaign_id ) ) {
						$imported_gallery_template_ids   = get_option( 'ig_es_imported_remote_gallery_template_ids', array() );
						$imported_gallery_template_ids[] = $template_id;
						update_option( 'ig_es_imported_remote_gallery_template_ids', $imported_gallery_template_ids );
						if ( in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_POST_NOTIFICATION, IG_CAMPAIGN_TYPE_POST_DIGEST ), true ) ) {
							ES_Campaign_Controller::add_to_new_category_format_campaign_ids( $campaign_id );
						}
					}
				}
			}

			return $campaign_id;
		}

		public static function import_remote_gallery_template( $template_id ) {
			$imported_template_id = 0;
			$gallery_item  = self::get_remote_gallery_item( $template_id );
			if ( empty( $gallery_item ) ) {
				return $imported_template_id;
			}

			$template_version = ! empty( $gallery_item->template_version ) ? $gallery_item->template_version : '';
			
			if ( in_array( $template_version, array('1.0.0', '1.0.1') ) ) {
				$subject       = $gallery_item->title->rendered;
				$content       = $gallery_item->content->rendered;
				$editor_type   = ! empty( $gallery_item->es_editor_type ) ? $gallery_item->es_editor_type : IG_ES_CLASSIC_EDITOR;
				$template_type   = ! empty( $gallery_item->es_template_type ) ? $gallery_item->es_template_type : IG_CAMPAIGN_TYPE_NEWSLETTER;
				$campaign_meta = array(
					'es_editor_type' => $editor_type,
				);
				if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type ) {
					$dnd_editor_data = maybe_unserialize( $gallery_item->es_dnd_editor_data );
					if ( ! empty( $dnd_editor_data ) ) {
						$campaign_meta['es_dnd_editor_data'] = $gallery_item->es_dnd_editor_data;
					}
				} else {
					if ( false === strpos( $content, '<html' ) ) {
						// In classic edior, we need to add p tag to content when not already added.
						$content = wpautop( $content );
					}

					$custom_css = ! empty( $gallery_item->es_custom_css ) ? $gallery_item->es_custom_css : '';
					if ( ! empty( $custom_css ) ) {
						$campaign_meta['es_custom_css'] = $custom_css;
					}
				}

				preg_match_all( '#<img\s+(?:[^>]*?\s+)?src=(\'|")?(https?[^\'"]+)(\'|")?#', $content, $image_urls );
				$image_urls = ! empty( $image_urls[2] ) ? $image_urls[2] : array();
				if ( ! empty( $image_urls ) ) {
					foreach ( $image_urls as $image_url ) {
						$is_ig_image_link = false !== strpos( $image_url , 'icegram.com' );
						if ( $is_ig_image_link ) {
							$new_image_url = ES_Common::download_image_from_url( $image_url );
							if ( ! empty( $new_image_url ) ) {
								$old_url       = ' src="' . $image_url . '"';
								$new_url       = ' src="' . $new_image_url . '"';
								$pos           = strpos( $content, $old_url );
								if ( false !== $pos ) {
									$content = preg_replace( '/' . preg_quote( $old_url, '/' ) . '/', $new_url, $content, 1 );
								}
							}
						}
					}
				}

				$template_data = array(
					'post_title'   => $subject,
					'post_content' => $content,
					'post_type'    => 'es_template',
					'post_status'  => 'draft',
				);

				$imported_template_id = wp_insert_post( $template_data );

				$is_template_added = ! ( $imported_template_id instanceof WP_Error );
		
				if ( $is_template_added ) {

					$editor_type = ! empty( $campaign_meta['es_editor_type'] ) ? $campaign_meta['es_editor_type'] : '';

					$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

					if ( $is_dnd_editor ) {
						$dnd_editor_data = array();
						if ( ! empty( $campaign_meta['es_dnd_editor_data'] ) ) {
							$dnd_editor_data = $campaign_meta['es_dnd_editor_data'];
							$dnd_editor_data = json_decode( $dnd_editor_data );
							update_post_meta( $imported_template_id, 'es_dnd_editor_data', $dnd_editor_data );
						}
					} else {
						$custom_css = ! empty( $campaign_meta['es_custom_css'] ) ? $campaign_meta['es_custom_css'] : '';
						update_post_meta( $imported_template_id, 'es_custom_css', $custom_css );
					}

					update_post_meta( $imported_template_id, 'es_editor_type', $editor_type );
					update_post_meta( $imported_template_id, 'es_template_type', $template_type );
				}
				
			}

			return $imported_template_id;
		}

		public static function duplicate_template( $template_id ) {
			// Get access to the database
			global $wpdb;
			// Get the post as an array
			$duplicate = get_post( $template_id, 'ARRAY_A' );
			// Modify some of the elements
			$duplicate['post_title']  = $duplicate['post_title'] . ' ' . __( 'Copy', 'email-subscribers' );
			$duplicate['post_status'] = 'publish';
			// Set the post date
			$timestamp = time();

			$duplicate['post_date'] = gmdate( 'Y-m-d H:i:s', $timestamp );

			// Remove some of the keys
			unset( $duplicate['ID'] );
			unset( $duplicate['guid'] );
			unset( $duplicate['comment_count'] );

			$current_user_id = get_current_user_id();
			if ( ! empty( $current_user_id ) ) {
				// Set post author to current logged in author.
				$duplicate['post_author'] = $current_user_id;
			}

			// Insert the post into the database
			$duplicate_id = wp_insert_post( $duplicate );

			// Duplicate all taxonomies/terms
			$taxonomies = get_object_taxonomies( $duplicate['post_type'] );

			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $template_id, $taxonomy, array( 'fields' => 'names' ) );
				wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
			}

			// Duplicate all custom fields
			$custom_fields = get_post_custom( $template_id );
			foreach ( $custom_fields as $key => $value ) {
				add_post_meta( $duplicate_id, $key, maybe_unserialize( $value[0] ) );
			}

			return $duplicate_id;
		}

		public static function preview_template( $args ) {
			$template_id  = $args['template_id'];
			$gallery_type = $args['gallery_type'];

			if ( 'remote' === $gallery_type ) {
				$template = self::get_remote_gallery_item( $template_id );
				
				$es_template_body = $template->content->rendered;
				$es_template_type = $template->es_template_type;
				$custom_css       = $template->es_custom_css;
				$es_template_body = $custom_css . $es_template_body;
			} else {
				$template         = get_post( $template_id, ARRAY_A );
				$es_template_body = $template['post_content'];
				$es_template_type = get_post_meta( $template_id, 'es_template_type', true );
			}
			
			if ( $template ) {
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

				// Don't replace placeholder keywords in remote templates.
				if ( 'remote' !== $gallery_type ) {
					if ( 'post_notification' === $es_template_type ) {
						$args         = array(
							'numberposts' => '1',
							'order'       => 'DESC',
							'post_status' => 'publish',
						);
						$recent_posts = wp_get_recent_posts( $args );
		
						if ( count( $recent_posts ) > 0 ) {
							$recent_post = array_shift( $recent_posts );
		
							$post_id          = $recent_post['ID'];
							$es_template_body = ES_Handle_Post_Notification::prepare_body( $es_template_body, $post_id, $template_id );
						}
					} else {
						$es_template_body = ES_Common::es_process_template_body( $es_template_body, $template_id );
					}
				}

				$es_template_body = ES_Common::replace_keywords_with_fallback( $es_template_body, array(
					'FIRSTNAME' => $first_name,
					'NAME'      => $username,
					'LASTNAME'  => $last_name,
					'EMAIL'     => $useremail
				) );

				$es_template_body = ES_Common::replace_keywords_with_fallback( $es_template_body, array(
					'subscriber.first_name' => $first_name,
					'subscriber.name'      => $username,
					'subscriber.last_name'  => $last_name,
					'subscriber.email'     => $useremail
				) );

				add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );
				$response['template_html'] = apply_filters( 'the_content', $es_template_body );
			} else {
				$response['template_html'] = __( 'Please publish it or save it as a draft.', 'email-subscribers' );
			}

			return $response;
		}

		public static function delete_template( $args ) {
			
			$template_id = absint($args['template_id']);
			if ( ! empty( $template_id ) ) {
				$deleted = wp_delete_post( $template_id );
				if ( $deleted ) {
					return true;
				}
			}

			return false;
		}
	}

}

ES_Gallery_Controller::get_instance();
