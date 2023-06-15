<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ES_Handle_Post_Notification {

	public $is_wp_5 = false;

	public $is_rest_request = false;

	public $do_post_notification_via_wp_5_hook = false;

	public $do_post_notification_for = 0;

	public function __construct() {
		global $wp_version;

		// Action is available after WordPress 2.3.0+
		add_action( 'transition_post_status', array( $this, 'es_post_publish_callback' ), 10, 3 );

		// Action is available WordPress 5.0+
		add_action( 'rest_after_insert_post', array( $this, 'handle_post_publish' ), 10, 3 );

		// Filter is available after WordPress 4.7.0+
		add_filter( 'rest_pre_insert_post', array( $this, 'prepare_post_data' ), 10, 2 );

		if ( version_compare( $wp_version, '5.0.0', '>=' ) ) {
			$this->is_wp_5 = true;
		}

		add_action( 'ig_es_refresh_post_notification_content', array( $this, 'refresh_post_content' ), 10, 2 );

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Init hooks required for email queued admin notice functionality
	 */
	public function init() {

		$post_types        = array( 'post', 'page' );
		$custom_post_types = ES_Common::get_custom_post_types();
		$post_types        = array_merge( $post_types, $custom_post_types );
		foreach ( $post_types as $post_type  ) {
			add_filter( 'rest_prepare_' . $post_type, array( $this, 'add_generated_post_mailing_queue_ids' ), 10, 3 );
		}

		add_action( 'admin_notices', array( $this, 'show_emails_queued_notice' ) );
		add_action( 'admin_footer', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function prepare_post_data( $prepared_post, $request ) {
		$this->is_rest_request = true;
		return $prepared_post;
	}

	public function handle_post_publish( $post, $requst, $insert ) {

		// If it's inserted for the first time????
		// Not able to check whether it'a first time post or nth times
		if ( is_object( $post ) && ( $post instanceof WP_Post ) ) { // Do it for the first time only

			if ( $this->do_post_notification_via_wp_5_hook ) {
				$post_id = $post->ID;
				if ( $post_id == $this->do_post_notification_for ) {
					$this->queue_post_notifications( $post_id );
				}
			}
		}

	}


	public function es_post_publish_callback( $post_status, $original_post_status, $post ) {

		if ( ( 'publish' == $post_status ) && ( 'publish' != $original_post_status ) ) {

			if ( is_object( $post ) ) {

				$post_id = $post->ID;

				if ( ! empty( $post_id ) ) {

					$post_date 				  = $post->post_date;
					$last_year_date 		  = gmdate( 'Y-m-d H:i:s', strtotime('-1 year') );
					$post_date_timestamp 	  = strtotime( $post_date );
					$last_year_date_timestamp = strtotime( $last_year_date );

					$old_post = $post_date_timestamp < $last_year_date_timestamp;

					$old_post_notification_disabled = apply_filters( 'ig_es_disable_old_post_notification', true );

					if ( $old_post && $old_post_notification_disabled ) {
						// don't send post notification to old post
						return;
					}

					$is_post_notified = get_post_meta( $post_id, 'ig_es_is_post_notified', true );

					//Return if post notification is already sent once.
					if ( $is_post_notified ) {
						return;
					}

					if ( $this->is_wp_5 && $this->is_rest_request ) {
						$this->do_post_notification_via_wp_5_hook = true;
						$this->do_post_notification_for           = $post_id;
					} else {
						$this->queue_post_notifications( $post_id );
					}
				}
			}
		}
	}

	public function queue_post_notifications( $post_id ) {

		if ( ! empty( $post_id ) ) {

			$notifications = ES()->campaigns_db->get_campaigns_by_post_id( $post_id );

			if ( count( $notifications ) > 0 ) {
				$post_mailing_queue_ids = array();
				foreach ( $notifications as $notification ) {
					$notification_id      = $notification['id'];
					$notification_body    = $notification['body'];
					$notification_subject = $notification['name'];
					if ( ! empty( $notification_subject ) && ! empty( $notification_body ) ) {
						$list_id = $notification['list_ids'];
						if ( ! empty( $list_id ) ) {
							$list_id = explode( ',', $list_id );
						}

						$post = get_post( $post_id );

						if ( is_object( $post ) ) {

							/*
							* Prepare Subject
							* Prepare Body
							* Add entry into mailing queue table
							*/

							// Prepare subject
							$post_subject = self::prepare_subject( $notification_subject, $post );

							$post_content = self::prepare_body( $notification_body, $post_id, 0, $notification_id );

							$guid = ES_Common::generate_guid( 6 );

							$data = array(
								'hash'        => $guid,
								'campaign_id' => $notification['id'],
								'subject'     => $post_subject,
								'body'        => $post_content,
								'count'       => 0,
								'status'      => '',
								'start_at'    => '',
								'finish_at'   => '',
								'created_at'  => ig_get_current_date_time(),
								'updated_at'  => ig_get_current_date_time(),
								'meta'        => maybe_serialize(
									array(
										'post_id' => $post_id,
										'type'    => 'post_notification',
									)
								),
							);

							// Add entry into mailing queue table
							$mailing_queue_id = ES_DB_Mailing_Queue::add_notification( $data );

							if ( $mailing_queue_id ) {

								$mailing_queue_hash = $guid;
								$campaign_id        = $notification['id'];
								$emails_queued		= ES_DB_Sending_Queue::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id, $list_id );

								if ( $emails_queued ) {
									update_post_meta( $post_id, 'ig_es_is_post_notified', 1 );
									$post_mailing_queue_ids[] = $mailing_queue_id;
								}
							}
						}
					}
				}
				if ( ! empty( $post_mailing_queue_ids ) ) {
					$trasient_expiry_time_in_seconds = 3;
					set_transient( 'ig_es_post_mailing_queue_ids_' . $post->ID, $post_mailing_queue_ids, $trasient_expiry_time_in_seconds );
				}
			}
		}
	}

	public static function prepare_subject( $notification_subject, $post ) {
		// convert post subject here

		$post_title  = $post->post_title;

		$blog_charset = get_option( 'blog_charset' );

		$post_title   = html_entity_decode( $post_title, ENT_QUOTES, $blog_charset );
		$post_subject = str_replace( '{{POSTTITLE}}', $post_title, $notification_subject );
		$post_subject = str_replace( '{{post.title}}', $post_title, $post_subject );

		$post_link    = get_permalink( $post );
		$post_subject = str_replace( '{{POSTLINK}}', $post_link, $post_subject );
		$post_subject = str_replace( '{{post.link}}', $post_link, $post_subject );

		return $post_subject;

	}

	public static function prepare_body( $es_templ_body, $post_id, $email_template_id ) {
		$post     = get_post( $post_id );
		$post_key = 'post';
		// Making $post as global using $GLOBALS['post'] key. Can't use 'post' key directly into $GLOBALS since PHPCS throws global variable assignment warning for 'post'.
		$GLOBALS[ $post_key ] = $post;

		//$es_templ_body = $this->workflow->variable_processor()->process_field( $value, $allow_html );

		$post_date     = ES_Common::convert_date_to_wp_date( $post->post_modified );
		$es_templ_body = str_replace( '{{DATE}}', $post_date, $es_templ_body );
		$es_templ_body = str_replace( '{{post.date}}', $post_date, $es_templ_body );

		$post_title    = get_the_title( $post );
		$es_templ_body = str_replace( '{{POSTTITLE}}', $post_title, $es_templ_body );
		$es_templ_body = str_replace( '{{post.title}}', $post_title, $es_templ_body );
		$post_link     = get_permalink( $post_id );

		// Size of {{POSTIMAGE}}
		$post_thumbnail      = '';
		$post_thumbnail_link = '';
		$post_thumbnail_url  = '';
		if ( ( function_exists( 'has_post_thumbnail' ) ) && ( has_post_thumbnail( $post_id ) ) ) {
			$es_post_image_size = get_option( 'ig_es_post_image_size', 'full' );
			switch ( $es_post_image_size ) {
				case 'full':
					$post_thumbnail = get_the_post_thumbnail( $post_id, 'full' );
					break;
				case 'medium':
					$post_thumbnail = get_the_post_thumbnail( $post_id, 'medium' );
					break;
				case 'thumbnail':
				default:
					$post_thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
					break;
			}
		}

		if ( '' != $post_thumbnail ) {
			$post_thumbnail_link = "<a href='" . $post_link . "' target='_blank'>" . $post_thumbnail . '</a>';
		}

		$es_templ_body 		= str_replace( '{{POSTIMAGE}}', $post_thumbnail_link, $es_templ_body );
		$es_templ_body 		= str_replace( '{{post.image}}', $post_thumbnail_link, $es_templ_body );

		$post_thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! empty( $post_thumbnail_id ) ) {
			$post_thumbnail_url = wp_get_attachment_url( $post_thumbnail_id );
		}

		$es_templ_body 		= str_replace( '{{POSTIMAGE-URL}}', $post_thumbnail_url, $es_templ_body );
		$es_templ_body 		= str_replace( '{{post.image_url}}', $post_thumbnail_url, $es_templ_body );

		// Get post description
		$post_description_length = 50;
		$post_description        = $post->post_content;
		$post_description        = strip_tags( self::strip_shortcodes( $post_description ) );
		$words                   = explode( ' ', $post_description, $post_description_length + 1 );
		if ( count( $words ) > $post_description_length ) {
			array_pop( $words );
			array_push( $words, '...' );
			$post_description = implode( ' ', $words );
		}
		$es_templ_body = str_replace( '{{POSTDESC}}', $post_description, $es_templ_body );
		$es_templ_body = str_replace( '{{post.description}}', $post_description, $es_templ_body );

		// Get post excerpt
		$post_excerpt  = get_the_excerpt( $post );
		$post_excerpt  = wpautop( $post_excerpt );
		$post_excerpt  = wptexturize( $post_excerpt );
		$es_templ_body = str_replace( '{{POSTEXCERPT}}', $post_excerpt, $es_templ_body );
		$es_templ_body = str_replace( '{{post.excerpt}}', $post_excerpt, $es_templ_body );

		$more_tag_data = get_extended( $post->post_content );

		// Get text before the more(<!--more-->) tag.
		$text_before_more_tag = $more_tag_data['main'];
		$strip_excluded_tags  = ig_es_get_strip_excluded_tags();
		$text_before_more_tag = strip_tags( self::strip_shortcodes( $text_before_more_tag ), implode( '', $strip_excluded_tags ) );
		$es_templ_body        = str_replace( '{{POSTMORETAG}}', $text_before_more_tag, $es_templ_body );
		$es_templ_body        = str_replace( '{{post.more_tag}}', $text_before_more_tag, $es_templ_body );

		// get post author
		$post_author_id         = $post->post_author;
		$post_author            = get_the_author_meta( 'display_name', $post_author_id );
		$post_author_avatar_url = get_avatar_url( $post_author_id );
		$author_avatar          = '<img src="' . esc_attr( $post_author_avatar_url ) . '" alt="' . esc_attr( $post_author ) . '" width="auto" height="auto" />';
		$es_templ_body          = str_replace( '{{POSTAUTHOR}}', $post_author, $es_templ_body );
		$es_templ_body          = str_replace( '{{post.author}}', $post_author, $es_templ_body );
		$es_templ_body          = str_replace( '{{POSTLINK-ONLY}}', $post_link, $es_templ_body );
		$es_templ_body          = str_replace( '{{post.link_only}}', $post_link, $es_templ_body );
		$es_templ_body          = str_replace( '{{POSTAUTHORAVATAR}}', $author_avatar, $es_templ_body );
		$es_templ_body          = str_replace( '{{post.author_avatar}}', $author_avatar, $es_templ_body );
		$es_templ_body          = str_replace( '{{POSTAUTHORAVATARLINK-ONLY}}', $post_author_avatar_url, $es_templ_body );
		$es_templ_body          = str_replace( '{{post.author_avatar_url}}', $post_author_avatar_url, $es_templ_body );

		// Check if template has {{POSTCATS}} placeholder.
		if ( strpos( $es_templ_body, '{{POSTCATS}}' ) >= 0 ) {
			$taxonomies = get_object_taxonomies( $post );
			$post_cats  = array();

			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					$taxonomy_object = get_taxonomy( $taxonomy );
					// Check if taxonomy is hierarchical e.g. have parent-child relationship like categories
					if ( $taxonomy_object->hierarchical ) {
						$post_terms = get_the_terms( $post, $taxonomy );
						if ( ! empty( $post_terms ) ) {
							foreach ( $post_terms as $term ) {
								$term_name   = $term->name;
								$post_cats[] = $term_name;
							}
						}
					}
				}
			}

			$es_templ_body = str_replace( '{{POSTCATS}}', implode( ', ', $post_cats ), $es_templ_body );
			$es_templ_body = str_replace( '{{post.cats}}', implode( ', ', $post_cats ), $es_templ_body );
		}

		if ( '' != $post_link ) {
			$post_link_with_title = "<a href='" . $post_link . "' target='_blank'>" . $post_title . '</a>';
			$es_templ_body        = str_replace( '{{POSTLINK-WITHTITLE}}', $post_link_with_title, $es_templ_body );
			$es_templ_body        = str_replace( '{{post.link_with_title}}', $post_link_with_title, $es_templ_body );
			$post_link            = "<a href='" . urldecode( $post_link ) . "' target='_blank'>" . urldecode( $post_link ) . '</a>';
		}
		$es_templ_body = str_replace( '{{POSTLINK}}', $post_link, $es_templ_body );
		$es_templ_body = str_replace( '{{post.link}}', $post_link, $es_templ_body );

		// Get full post
		$post_full     = $post->post_content;
		$post_full     = wpautop( $post_full );
		$es_templ_body = str_replace( '{{POSTFULL}}', $post_full, $es_templ_body );
		$es_templ_body = str_replace( '{{post.full}}', $post_full, $es_templ_body );

		// add pre header as post excerpt
		/*
		if ( ! empty( $post_excerpt ) ) {
			$es_templ_body = '<span class="es_preheader" style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0;">' . $post_excerpt . '</span>' . $es_templ_body;
		}
		*/

		if ( $email_template_id > 0 ) {
			$es_templ_body = ES_Common::es_process_template_body( $es_templ_body, $email_template_id );
		}

		return apply_filters( 'ig_es_post_notification_body', $es_templ_body, $post_id );
	}

	public static function refresh_post_content( $content, $args ) {
		$campaign_id        = $args['campaign_id'];
		$post_id            = $args['post_id'];
		$post               = get_post( $post_id );
		$template_id        = ES()->campaigns_db->get_template_id_by_campaign( $campaign_id );
		$template           = get_post( $template_id );
		$campaign           = ES()->campaigns_db->get( $campaign_id );

		$campaign_subject = $campaign['name'];
		if ( ! empty( $campaign['body'] ) ) {
			$template_content = $campaign['body'];
		} else {
			$template_content = $template->post_content;
		}

		$content['subject'] = self::prepare_subject( $campaign_subject, $post );
		$content['body']    = self::prepare_body( $template_content, $post_id, $template_id );

		return $content;
	}

	/**
	 * Add generated post mailing queue ids to REST response.
	 *
	 * @since 5.4.0
	 *
	 * @param object $response REST response.
	 * @param object $post Post object.
	 * @param array $request REST request.
	 *
	 * @return array $response REST response.
	 */
	public function add_generated_post_mailing_queue_ids( $response, $post, $request ) {

		if ( $post instanceof WP_Post ) {
			$response->data['post_mailing_queue_ids'] = array();

			$post_mailing_queue_ids = get_transient( 'ig_es_post_mailing_queue_ids_' . $post->ID );
			if ( ! empty( $post_mailing_queue_ids ) ) {
				$response->data['post_mailing_queue_ids'] = $post_mailing_queue_ids;
			}
		}


		return $response;
	}

	/**
	 * Show emails queued notice when post is published in Classic Editor
	 *
	 * @since 5.4.0
	 */
	public function show_emails_queued_notice() {
		if ( $this->is_post_edit_screen() ) {
			global $post;
			if ( $post instanceof WP_Post ) {
				$post_mailing_queue_ids = get_transient( 'ig_es_post_mailing_queue_ids_' . $post->ID );
				if ( ! empty( $post_mailing_queue_ids ) ) {
					$notice_text     = $this->get_emails_queued_notice_text();
					$report_page_url = menu_page_url( 'es_reports', false );
					?>
					<div id="ig-es-reports-queued-notice" class="notice notice-success">
						<p>
							<strong><?php echo esc_html( $notice_text ); ?></strong>
							<a href="<?php echo esc_url( $report_page_url ); ?>" target="_blank"><?php echo esc_html__( 'View Reports', 'email-subscribers' ); ?></a>
						</p>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * Check if current screen is post edit screen
	 *
	 * @since 5.4.0
	 *
	 * @return boolean $is_post_edit_screen True if current screen is post edit screen else false
	 */
	public function is_post_edit_screen() {
		$current_screen      = get_current_screen();
		$is_post_edit_screen = 'post' === $current_screen->base;
		return $is_post_edit_screen;
	}

	/**
	 * Enqueue admin scripts on post edit screen
	 *
	 * @since 5.4.0
	 */
	public function enqueue_admin_scripts() {

		if ( ! $this->is_post_edit_screen() ) {
			// Return if not on post edit screen.
			return;
		}

		$current_screen = get_current_screen();

		$is_block_editor_page = method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor();
		if ( ! $is_block_editor_page ) {
			// Return if Gutenberg isn't used.
			return;
		}

		$report_page_url = menu_page_url( 'es_reports', false );

		$notice_text = $this->get_emails_queued_notice_text();
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				if ( 'undefined' !== typeof wp.data && 'undefined' !== typeof wp.data.subscribe ) {
					const { subscribe }   = wp.data;
					const report_page_url = '<?php echo esc_js( $report_page_url ); ?>';
					let notice_shown       = false;
					const unsubscribe     = subscribe( () => {
						const coreEditor = wp.data.select( 'core/editor' );

						if ( 'undefined' === typeof coreEditor || 'undefined' === typeof coreEditor.isPublishingPost ) {
							return;
						}

						const isPublishingPost = coreEditor.isPublishingPost();
						// wait for the post to be published before showing the notice
						if ( isPublishingPost ) {
							notice_shown = false;
							return;
						}

						if ( 'undefined' === typeof coreEditor.isCurrentPostPublished ) {
							return;
						}

						const currentPostPublished = coreEditor.isCurrentPostPublished();
						if ( currentPostPublished ) {

							if ( 'undefined' === typeof coreEditor.getCurrentPost ) {
								return;
							}

							const currentPost = coreEditor.getCurrentPost();
							if ( 'undefined' === typeof currentPost.post_mailing_queue_ids || currentPost.post_mailing_queue_ids.length === 0 ) {
								return;
							}

							if ( ! notice_shown ) {
								notice_shown = true;
								setTimeout(() => {
									wp.data.dispatch( 'core/notices' ).createNotice(
										'success', // Can be one of: success, info, warning, error.
											'<?php echo esc_js( $notice_text ); ?>', // Text string to display.
											{
												type: "snackbar",
												isDismissible: true, // Whether the user can dismiss the notice.
												// Any actions the user can perform.
												actions: [
													{
														onClick: () => {
															window.open(report_page_url, '_blank').focus();
														},
														label: '<?php echo esc_attr__( 'View Reports', 'email-subscribers' ); ?>',
													},
												],
											}
									);
								}, 1000);
							}
						}
					} );
				}
			});
		</script>
		<?php
	}

	/**
	 * Get admin notice text.
	 *
	 * @since 5.4.0
	 *
	 * @return string $notice_text Admin Notice text.
	 */
	public function get_emails_queued_notice_text() {
		global $post;
		$notice_text = '';
		if ( $post instanceof WP_Post ) {
			$post_type        = $post->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$post_type__name  = $post_type_object->labels->singular_name;

			/* translators: %s: Post type name */
			$notice_text = sprintf( __( 'Notification emails has been queued for this %s.', 'email-subscribers' ), strtolower( $post_type__name ) );
		}

		return $notice_text;
	}

	public static function strip_shortcodes( $content ) {
		$content = preg_replace('/\[[^\[\]]*\]/', '', $content);
		return $content;
	}

}
