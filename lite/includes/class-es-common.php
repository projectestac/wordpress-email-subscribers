<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_General.
 *
 * @since 4.0
 */
class ES_Common {
	/**
	 * Convert email subscribe templates.
	 *
	 * @param string $template Get email subscribe templates.
	 * @param string $name Get subscriber name.
	 * @param string $email Get subscriber email.
	 *
	 * @return string $convert_template
	 *
	 * @since 4.0
	 */
	public static function convert_es_templates( $template, $name, $email, $es_templ_id = 0 ) {
		$convert_template = str_replace( '{{NAME}}', $name, $template );
		$convert_template = str_replace( '{{EMAIL}}', $email, $convert_template );

		return $convert_template;
	}


	/**
	 * Collect all emails.
	 *
	 * @return string $all_admin_email
	 *
	 * @since 5.3.18
	 */
	public static function get_admin_email() {
		$admin_email     = get_option( 'ig_es_admin_emails', '' );
		$all_admin_email = explode(',', $admin_email);

		return $all_admin_email[0];
	}


	/**
	 * Count sent emails
	 *
	 * @return int $total_emails_sent
	 *
	 * @since 5.3.18
	 */
	public static function count_sent_emails() {
		$current_date = ig_es_get_current_date();
		$current_hour = ig_es_get_current_hour();

		// Get total emails sent in this hour
		$email_sent_data = self::get_ig_option( 'email_sent_data', array() );

		$total_emails_sent = 0;
		if ( is_array( $email_sent_data ) && ! empty( $email_sent_data[ $current_date ] ) && ! empty( $email_sent_data[ $current_date ][ $current_hour ] ) ) {
			$total_emails_sent = $email_sent_data[ $current_date ][ $current_hour ];
		}

		return $total_emails_sent;
	}

	/**
	 * Process the email template and get variable fallbacks
	 *
	 * @param $template
	 */
	public static function get_template_fallbacks( $template ) {
		preg_match_all( '/{{(.*?)}}/', $template, $matches );
		$default_keywords = array();
		if ( 1 < count( $matches ) ) {
			$fallback_matches = $matches[1];
			foreach ( $fallback_matches as $keyword ) {
				if ( strstr( $keyword, '|' ) ) {
					list( $variable_name, $variable_params ) = explode( '|', $keyword, 2 );
				} else {
					$variable_name   = $keyword;
					$variable_params = '';
				}
				$variable_name = trim( $variable_name );
				$variable      = new IG_ES_Workflow_Variable_Parser();
				$parameters    = $variable->parse_parameters_from_string( trim( $variable_params ) );
				if ( is_array( $parameters ) && ! empty( $parameters ) ) {
					if ( isset( $parameters['fallback'] ) && ! empty( $parameters['fallback'] ) ) {
						$replace_with_fallback = self::un_quote( $parameters['fallback'] );
						$is_nested_variable    = strpos( $variable_name, '.' ); // Check if variable has dont(.) in its name
						if ( $is_nested_variable ) {
							$variable_parts = explode( '.', $variable_name );
							$variable_type  = $variable_parts[0];
							$variable_slug  = $variable_parts[1];
							/**
							 * For variables like subscribers.name, we need to pass the fallback data as nested array
							 * $default_keywords['subscribers']['name'] = fallback_value
							 **/ 
							$default_keywords[ $variable_type ][ $variable_slug ] = $replace_with_fallback;
						} else {
							$default_keywords[ $variable_name ] = $replace_with_fallback;
						}
					}
				}
			}
		}
		return $default_keywords;
	}


	/**
	 * Callback to replace keywords
	 *
	 * @param $keyword
	 * @param $search_and_replace
	 *
	 * @return mixed|string
	 */
	public static function callback_replace_keywords( $keyword, $search_and_replace ) {
		if ( strstr( $keyword, '|' ) ) {
			list( $variable_name, $variable_params ) = explode( '|', $keyword, 2 );
		} else {
			$variable_name   = $keyword;
			$variable_params = '';
		}
		$variable_name = trim( $variable_name );

		//If there is no key found in replaceable array, then return the keyword
		if ( ! isset( $search_and_replace[ $variable_name ] ) ) {
			return '{{' . $keyword . '}}';
		}

		$replace_with          = $search_and_replace[ $variable_name ];
		$replace_with_fallback = '';

		//Extract fallback content from the keyword
		$variable   = new IG_ES_Workflow_Variable_Parser();
		$parameters = $variable->parse_parameters_from_string( trim( $variable_params ) );
		if ( is_array( $parameters ) && ! empty( $parameters ) ) {
			if ( isset( $parameters['fallback'] ) && ! empty( $parameters['fallback'] ) ) {
				$replace_with_fallback = self::un_quote($parameters['fallback']);
			}
		}

		//If replaceable value is contain fallback keyword, then return the replaceable value with fallback value
		if ( strstr( $replace_with, '%%fallback%%' ) ) {
			return str_replace( '%%fallback%%', $replace_with_fallback, $replace_with );
		}

		//If replaceable value is not empty, then return the replaceable value
		if ( ! empty( $replace_with ) ) {
			return $replace_with;
		}

		// return fallback value
		return $replace_with_fallback;
	}

	/**
	 * Decode the html quotes
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function decode_html_quotes( $string ) {
		$entities_dictionary = [
			'&#145;'  => "'", // Opening single quote
			'&#146;'  => "'", // Closing single quote
			'&#147;'  => '"', // Closing double quote
			'&#148;'  => '"', // Opening double quote
			'&#8216;' => "'", // Closing single quote
			'&#8217;' => "'", // Opening single quote
			'&#8218;' => "'", // Single low quote
			'&#8220;' => '"', // Closing double quote
			'&#8221;' => '"', // Opening double quote
			'&#8222;' => '"', // Double low quote
		];

		// Decode decimal entities
		$string = str_replace( array_keys( $entities_dictionary ), array_values( $entities_dictionary ), $string );

		return html_entity_decode( $string, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Remove quotes from string
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function un_quote( $string ) {
		$string = self::decode_html_quotes( $string );

		return trim( trim( $string ), "'" );
	}

	/**
	 * Parse keywords
	 *
	 * @since 5.3.5
	 * @return false|void
	 */
	public static function replace_keywords_with_fallback( $content, $search_and_replace ) {
		if ( empty( $content ) || empty( $search_and_replace ) ) {
			return $content;
		}
		$replacer = new IG_ES_Replace_Helper( $content, 'ES_Common::callback_replace_keywords', 'variables', $search_and_replace );

		$processed_content = $replacer->process();

		if ( $processed_content ) {
			return $processed_content;
		}

		return $content;
	}

	/**
	 * Process template body
	 *
	 * @param $content
	 * @param int     $tmpl_id
	 * @param int     $campaign_id
	 *
	 * @return mixed|string
	 *
	 * @since 4.0.0
	 */
	public static function es_process_template_body( $content, $tmpl_id = 0, $campaign_id = 0 ) {
		$content = convert_smilies( wptexturize( $content ) );
		
		// When wptexturize function is called, it converts '&' character into '&#038;'
		// When UTM tracking is enabled, URL part after '&#038;' is striped off
		// To fix it, we are converting HTML '&#038;' and '&amp;' into '&'
		$content = str_replace( array( '&#038;', '&amp;' ), '&', $content );

		$content = self::handle_oembed_content( $content );

		// Add p tag only if we aren't getting <html> tags inside the content, otherwise html gets marked as invalid.
		if ( false === strpos( $content, '<html' ) ) {
			$content = wpautop( $content );
		}

		$content             = do_shortcode( shortcode_unautop( $content ) );
		$data                = array();
		$data['content']     = $content;
		$data['tmpl_id']     = $tmpl_id;
		$data['campaign_id'] = $campaign_id;
		$data                = apply_filters( 'es_after_process_template_body', $data );
		$content             = $data['content'];
		// total contacts
		$total_contacts = ES()->contacts_db->count_active_contacts_by_list_id();
		$content        = str_replace( '{{TOTAL-CONTACTS}}', $total_contacts, $content );
		$content        = str_replace( '{{site.total_contacts}}', $total_contacts, $content );
		// blog title
		$blog_name = get_option( 'blogname' );
		$content   = str_replace( '{{SITENAME}}', $blog_name, $content );
		$content   = str_replace( '{{site.name}}', $blog_name, $content );
		// site url
		$site_url = home_url( '/' );
		$content  = str_replace( '{{SITEURL}}', $site_url, $content );
		$content  = str_replace( '{{site.url}}', $site_url, $content );

		/*
		TODO: Enable it once Pre header issue fix
		$meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
		$meta['pre_header'] = !empty($meta['pre_header']) ? $meta['pre_header'] : '';
		if( !empty( $meta['pre_header'] )){
			$content = '<span class="es_preheader" style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0;">'.$meta['pre_header'].'</span>'.$content;
		}
		*/

		return $content;
	}

	/**
	 * Method to handle oembed content
	 *
	 * @param string @content Content.
	 *
	 * @return string $content
	 *
	 * @since 4.4.9
	 */
	public static function handle_oembed_content( $content = '' ) {

		if ( ! empty( $content ) && isset( $GLOBALS['wp_embed'] ) ) {
			add_filter( 'embed_oembed_html', array( 'ES_Common', 'handle_link_in_email_content' ), 10, 4 );
			add_filter( 'embed_handler_html', array( 'ES_Common', 'handle_link_in_email_content' ), 10, 3 );
			$content = $GLOBALS['wp_embed']->autoembed( $content );
			remove_filter( 'embed_oembed_html', array( 'ES_Common', 'handle_link_in_email_content' ), 10, 4 );
			remove_filter( 'embed_handler_html', array( 'ES_Common', 'handle_link_in_email_content' ), 10, 3 );
		}

		return $content;
	}


	/**
	 * Method to handle link in email content
	 *
	 * URL from {{POSTLINK-ONLY}} was being converted to oembed html if it is not wrapped inside <a> tag's href attribute and is on a seperate line in ES template content
	 * resulting in a link html for {{POSTLINK-ONLY}} instead of plain text link.
	 *
	 * Most email clients like GMail, Outlook do not support videos in the email. To handle it, we are replacing the WordPress's oembed generated HTML for video links to their respective thubmnail images which are then linked the original video URL.
	 *
	 * @param string $html HTML for current URL.
	 * @param string $url Current URL.
	 * @param array  $attr Shortcode attribute.
	 * @param int    $post_ID Current post id.
	 *
	 * @return string $html HTML for current URL.
	 *
	 * @since 4.4.9
	 */
	public static function handle_link_in_email_content( $html, $url, $attr, $post_ID = 0 ) {

		$post_link = '';

		if ( ! empty( $post_ID ) ) {
			$post_link = get_permalink( $post_ID );
		}

		// Check if current URL is same as current post's permalink.
		if ( ! empty( $post_link ) && $url === $post_link ) {
			// Convert URL HTML back to URL itself if it a current post URL.
			$html = $url;
		} else {

			if ( ! class_exists( 'WP_oEmbed' ) ) {
				require_once ABSPATH . 'wp-includes/class-wp-oembed.php';
			}

			$oembed   = new WP_oEmbed();
			$provider = $oembed->get_provider( $url );
			if ( ! empty( $provider ) ) {
				$oembed_response = $oembed->fetch( $provider, $url, $attr );
				if ( is_object( $oembed_response ) && ! empty( $oembed_response->type ) && 'video' === $oembed_response->type && ! empty( $oembed_response->thumbnail_url ) ) {
					$thumbnail_url = $oembed_response->thumbnail_url;
					$title         = $oembed_response->title;
					$provider_name = $oembed_response->provider_name;
					$play_icon_url = '';

					switch ( $provider_name ) {
						case 'YouTube':
							$play_icon_url = ES_PLUGIN_URL . 'lite/public/images/youtube-play-button.png';
							break;

						case 'Vimeo':
							$play_icon_url = ES_PLUGIN_URL . 'lite/public/images/vimeo-play-button.png';
							break;

						default:
							$play_icon_url = ES_PLUGIN_URL . 'lite/public/images/default-play-button.png';
							break;
					}

					ob_start();
					$thumbnail_width  = 'auto';
					$thumbnail_height = 'auto';

					if ( ! empty( $oembed_response->thumbnail_width ) && ! empty( $oembed_response->thumbnail_height ) ) {
						$thumbnail_width  = $oembed_response->thumbnail_width . 'px';
						$thumbnail_height = $oembed_response->thumbnail_height . 'px';
					} elseif ( ! empty( $oembed_response->width ) && ! empty( $oembed_response->height ) ) {
						$thumbnail_width  = $oembed_response->width . 'px';
						$thumbnail_height = $oembed_response->height . 'px';
					}
					?>
					<table style="margin-bottom: 1em;">
						<tbody>
						<tr>
							<td style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>');height:<?php echo esc_attr( $thumbnail_height ); ?>;width:<?php echo esc_attr( $thumbnail_width ); ?>;background-size: 100% 100%;background-repeat: no-repeat;text-align:center;">
								<a href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $title ); ?>"
								   target="_blank">
									<img src="<?php echo esc_url( $play_icon_url ); ?>"
										 style="height: 75px; margin: auto;">
								</a>
							</td>
						</tr>
						</tbody>
					</table>
					<?php
					$html = ob_get_clean();
				}
			}
		}

		return $html;
	}

	/**
	 * Get Statuses key name map
	 *
	 * @param bool $reverse
	 * @param string $page
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public static function get_statuses_key_name_map( $reverse = false, $page = '') {

		$statuses = array(
			'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
			'unconfirmed'  => __( 'Unconfirmed', 'email-subscribers' ),
			'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' ),
		);

		$statuses = apply_filters( 'ig_es_get_statuses_key_name_map', $statuses, $page );
		if ( $reverse ) {
			$statuses = array_flip( $statuses );
		}

		return $statuses;
	}

	/**
	 * Prepare Statuses dropdown
	 *
	 * @param string $selected
	 * @param string $default_label
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function prepare_statuses_dropdown_options( $selected = '', $default_label = '', $page = '' ) {

		if ( empty( $default_label ) ) {
			$default_label = __( 'Select Status', 'email-subscribers' );
		}

		$default_status[0] = $default_label;

		$statuses = self::get_statuses_key_name_map(false, $page);
		$statuses = array_merge( $default_status, $statuses );

		$dropdown = '';
		foreach ( $statuses as $key => $status ) {
			$dropdown .= '<option class="text-sm" value="' . esc_attr( $key ) . '" ';

			if ( strtolower( $selected ) === strtolower( $key ) ) {
				$dropdown .= 'selected = selected';
			}

			$dropdown .= '>' . esc_html( $status ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Prepare list dropdown
	 *
	 * @param string $selected
	 * @param string $default_label
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function prepare_list_dropdown_options( $selected = '', $default_label = 'Select List' ) {

		$default_option[0] = __( $default_label, 'email-subscribers' );

		$lists    = ES()->lists_db->get_list_id_name_map();
		$lists    = $default_option + $lists;
		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $lists as $key => $list ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $list ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Prepare dropdown with form names
	 *
	 * @param string $selected
	 * @param string $default_label
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 * @since 4.3.2 Removed $where condition to find forms
	 */
	public static function prepare_form_dropdown_options( $selected = '', $default_label = 'Select Form' ) {

		$forms = ES()->forms_db->get_id_name_map();

		if ( ! is_null( $default_label ) ) {
			$default_option[0] = __( $default_label, 'email-subscribers' );
			$forms             = $default_option + $forms;
		}

		$dropdown = '';
		foreach ( $forms as $key => $form ) {
			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( $selected == $key ) {
				$dropdown .= 'selected = selected';
			}

			$dropdown .= '>' . esc_html( $form ) . '</option>';
		}

		return $dropdown;
	}


	/**
	 * Generate GUID
	 *
	 * @param int $length
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function generate_guid( $length = 6 ) {

		$str        = 'abcdefghijklmnopqrstuvwxyz';
		$random_str = array();
		for ( $i = 1; $i <= 5; $i ++ ) {
			$random_str[] = substr( str_shuffle( $str ), 0, $length );
		}

		$guid = implode( '-', $random_str );

		return $guid;
	}


	/**
	 * Generate random string
	 *
	 * @param int $length
	 *
	 * @return string
	 *
	 * @since 4.8.2
	 */
	public static function generate_random_string( $length = 6 ) {
		$str = 'abcdefghijklmnopqrstuvwxyz';

		return substr( str_shuffle( $str ), 0, $length );
	}

	/**
	 * Prepare template dropdown options
	 *
	 * @param string $type
	 * @param string $selected
	 *
	 * @return string
	 */
	public static function prepare_templates_dropdown_options( $type = 'newsletter', $selected = '', $editor_type = IG_ES_DRAG_AND_DROP_EDITOR ) {

		$default_template_option = new stdClass();

		$default_template_option->ID         = '';
		$default_template_option->post_title = __( 'Select Template', 'email-subscribers' );

		$default_template_option = array( $default_template_option );

		$templates   = self::get_templates( $type, $editor_type );
		$allowedtags = ig_es_allowed_html_tags_in_esc();
		if ( is_array( $templates ) ) {
			$templates = array_merge( $default_template_option, $templates );
		}

		$dropdown = '';
		foreach ( $templates as $key => $template ) {
			$es_templ_thumbnail = ( ! empty( $template->ID ) ) ? get_the_post_thumbnail_url(
				$template->ID,
				array(
					'200',
					'200',
				)
			) : ES_PLUGIN_URL . 'images/envelope.png';
			$dropdown          .= "<option data-img-url='" . $es_templ_thumbnail . "' value='" . $template->ID . "'";

			if ( absint( $selected ) === absint( $template->ID ) ) {
				$dropdown .= ' selected="selected"';
			}

			$dropdown .= '>' . $template->post_title . '</option>';
		}

		return $dropdown;

	}

	/**
	 * Prepare status dropdown options
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function prepare_status_dropdown_options( $selected ) {
		$statuses = array(
			'1' => __( 'Active', 'email-subscribers' ),
			'0' => __( 'Inactive', 'email-subscribers' ),
		);

		$dropdown = '';
		foreach ( $statuses as $key => $status ) {
			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( strtolower( $selected ) === strtolower( $key ) ) {
				$dropdown .= 'selected = selected';
			}

			$dropdown .= '>' . esc_html( $status ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Get ES Templates
	 *
	 * @param string $type
	 *
	 * @return int[]|WP_Post[]
	 *
	 * @since 4.0.0
	 */
	public static function get_templates( $type = '', $editor_type = '' ) {
// phpcs:diable WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
		$es_args = array(
			'posts_per_page'   => - 1,
			'post_type'        => 'es_template',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post_status'      => 'publish',
			'suppress_filters' => true, 
		);
// phpcs:enable
		if ( ! empty( $type ) ) {
			$es_args['meta_query'][] = array(
				'key'     => 'es_template_type',
				'value'   => $type,
				'compare' => '=',
			);
		}

		if ( ! empty( $editor_type ) ) {
			$es_args['meta_query'][] = array(
				array(
					'key'     => 'es_editor_type',
					'value'   => $editor_type,
					'compare' => '=',
				),
			);
		}

		$es_templates = get_posts( $es_args );

		return $es_templates;

	}

	/**
	 * Prepare categories checkboxes
	 *
	 * @param array $category_names
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function prepare_categories_html( $category_names = array() ) {
		$categories = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $category_names ) ) {
			$category_names = array();
		}

		// By default select All Categories option.
		if ( empty( $category_names ) ) {
			$category_names = array( 'All' );
		}

		$checked_selected = ! array_intersect( array( 'All', 'None', 'all', 'none' ), $category_names ) ? "checked='checked'" : '';
		$category_html    = '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;" ><span class="block pr-4 text-sm font-normal text-gray-600 pb-1"><input class="es-note-category-parent form-radio text-indigo-600" type="radio" ' . esc_attr( $checked_selected ) . ' value="selected_cat"  name="data[es_note_cat_parent]">' . __(
			'Select Categories',
			'email-subscribers'
		) . '</td></tr>';
		foreach ( $categories as $category ) {

			if ( in_array( $category->term_id, $category_names ) ) {
				$checked = "checked='checked'";
			} else {
				$checked = '';
			}

			$category_html .= '<tr class="es-note-child-category"><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block pr-4 text-sm font-normal text-gray-600 pb-1"><input type="checkbox" class="form-checkbox" ' . esc_attr( $checked ) . ' value="' . esc_attr( $category->term_id ) . '" id="es_note_cat[]" name="data[es_note_cat][]">' . esc_html( $category->name ) . '</td></tr>';
		}
		$checked_all = in_array( 'All', $category_names ) || in_array( 'all', $category_names ) ? "checked='checked'" : '';
		$all_html    = '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block pr-4 text-sm font-normal text-gray-600 pb-1"><input type="radio" class="form-radio text-indigo-600 es-note-category-parent"  ' . esc_attr( $checked_all ) . ' value="{a}All{a}"  name="data[es_note_cat_parent]">' . __(
			'All Categories (Also include all categories which will create later)',
			'email-subscribers'
		) . '</td></tr>';

		$checked_none = in_array( 'None', $category_names, true ) || in_array( 'none', $category_names, true ) ? "checked='checked'" : '';
		$none_html    = '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block pr-4 text-sm font-normal text-gray-600 pb-1"><input type="radio" class="form-radio text-indigo-600 es-note-category-parent"  ' . esc_attr( $checked_none ) . ' value="{a}None{a}"  name="data[es_note_cat_parent]">' . __(
			'None (Don\'t include post from any category)',
			'email-subscribers'
		) . '</td></tr>';

		return $none_html . $all_html . $category_html;
	}

	public static function get_post_categories() {
		$categories = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
			)
		);
		if ( empty( $categories ) ) {
			return array();
		}
		$post_categories = array();
		foreach ( $categories as $category ) {
			$post_categories[$category->term_id ] = $category->name;
		}
		return $post_categories;
	}

	/**
	 * Get list of default post types
	 *
	 * @since 5.3.17
	 *
	 * @return array $default_post_types List of default post types
	 */
	public static function get_default_post_types() {

		$args = array(
			'public'              => true,
			'exclude_from_search' => false,
			'_builtin'            => true,
		);

		$default_post_types = get_post_types( $args );

		// remove attachment from the list
		unset( $default_post_types['attachment'] );

		// remove post from the list
		//unset( $default_post_types['post'] );

		return $default_post_types;
	}


	/**
	 * Get list of registered custom post types
	 *
	 * @since 5.4.0
	 *
	 * @return array $custom_post_types List of custom post types
	 */
	public static function get_custom_post_types() {

		$args = array(
			'public'              => true,
			'exclude_from_search' => false,
			'_builtin'            => false,
		);

		$custom_post_types = get_post_types( $args );

		return $custom_post_types;
	}

	public static function get_post_types_name() {
		$default_post_types = self::get_default_post_types();
		$custom_post_types  = self::get_custom_post_types();
		$post_types         = array_merge( $default_post_types, $custom_post_types );
		$post_type_names    = array();
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				$post_type_name                = self::get_post_type_name( $post_type );
				$post_type_names[ $post_type ] = $post_type_name;
			}
		}

		return $post_type_names;
	}

	public static function get_post_type_name( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		$post_type_name   = $post_type_object->labels->singular_name;
		return $post_type_name;
	}

	/**
	 * Prepare custom post types checkboxes
	 *
	 * @param $custom_post_types
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function prepare_custom_post_type_checkbox( $custom_post_types ) {
		$args       = array(
			'public'              => true,
			'exclude_from_search' => false,
			'_builtin'            => false,
		);
		$output     = 'names';
		$operator   = 'and';
		$post_types = get_post_types( $args, $output, $operator );
		if ( ! empty( $post_types ) ) {
			$custom_post_type_html = '';
			foreach ( $post_types as $post_type ) {
				$post_type_search = '{T}' . $post_type . '{T}';
				if ( is_array( $custom_post_types ) && in_array( $post_type_search, $custom_post_types, true ) ) {
					$checked = "checked='checked'";
				} else {
					$checked = '';
				}
				$custom_post_type_html .= '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2"><input type="checkbox" ' . esc_attr( $checked ) . ' value="{T}' . esc_html( $post_type ) . '{T}" class="es_custom_post_type form-checkbox" name="data[es_note_cpt][]">' . esc_html( $post_type ) . '</td></tr>';
			}
		} else {
			$custom_post_type_html = '<tr><span class="block pr-4 text-sm font-normal text-gray-600 pb-2">' . __( 'No Custom Post Types Available', 'email-subscribers' ) . '</tr>';
		}

		return $custom_post_type_html;
	}


	/**
	 * Get categories for given post types
	 *
	 * @since 5.3.13
	 *
	 * @param array $post_type Post type
	 *
	 * @return array $post_type_categories List of categories for given post types
	 */
	public static function get_post_type_categories( $post_type ) {

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		if ( empty( $taxonomies ) ) {
			return array();
		}

		$post_type_categories = array();

		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			$is_category_taxonomy = $taxonomy->hierarchical;
			if ( ! $is_category_taxonomy ) {
				continue;
			}
			$categories = get_categories(
				array(
					'hide_empty' => false,
					'taxonomy'   => $taxonomy_slug,
					'type'       => $post_type,
					'orderby'    => 'id',
				)
			);

			if ( empty( $categories ) ) {
				continue;
			}
			
			foreach ( $categories as $category ) {
				$post_type_categories[ $category->term_id ] = $category->name;
			}
		}

		return $post_type_categories;
	}

	/**
	 * Get Opt-in types
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public static function get_optin_types() {

		$types = array(
			'single_opt_in' => __( 'Single Opt-In', 'email-subscribers' ),
			'double_opt_in' => __( 'Double Opt-In', 'email-subscribers' ),
		);

		return $types;
	}

	/**
	 * Get options to sending mails of weekly summary.
	 *
	 * @return array
	 */
	public static function run_summary_cron_on() {

		$types = array(
			'monday'    => __( 'Monday', 'email-subscribers' ),
			'tuesday'   => __( 'Tuesday', 'email-subscribers' ),
			'wednesday' => __( 'Wednesday', 'email-subscribers' ),
			'thursday'  => __( 'Thursday', 'email-subscribers' ),
			'friday'    => __( 'Friday', 'email-subscribers' ),
			'saturday'  => __( 'Saturday', 'email-subscribers' ),
			'sunday'    => __( 'Sunday', 'email-subscribers' ),
		);

		return apply_filters( 'ig_es_run_summary_cron_on_types', $types );
	}

	/**
	 * Get time options to sending mails of weekly summary.
	 *
	 * @return array
	 */
	public static function get_railway_hrs_timings() {

		$timings = array();

		for ( $i = 1; $i <= 24; $i ++ ) {
			if ( $i <= 12 ) {
				$timings[ "{$i}am" ] = "{$i}:00 AM";
			} else {
				$time                   = $i - 12;
				$timings[ "{$time}pm" ] = "{$time}:00 PM";
			}
		}

		return apply_filters( 'ig_es_get_railway_hrs_timings', $timings );
	}


	/**
	 * Get registered image sizes
	 *
	 * @return array
	 *
	 * @since 5.7.23
	 */
	public static function get_registered_image_sizes() {
		global $_wp_additional_image_sizes;
	
		// Get all default and custom image sizes
		$sizes 		   = array();
		$default_sizes = get_intermediate_image_sizes();
	
		foreach ( $default_sizes as $size ) {
			// Check if the size is in the additional image sizes array
			if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$sizes[ $size ] = array(
					'width'  => $_wp_additional_image_sizes[ $size ]['width'],
					'height' => $_wp_additional_image_sizes[ $size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
				);
			} else {
				// Get default sizes
				$sizes[ $size ] = array(
					'width'  => get_option( "{$size}_size_w" ),
					'height' => get_option( "{$size}_size_h" ),
					'crop'   => get_option( "{$size}_crop" ),
				);
			}
		}
	
		// Create a formatted array with size names => labels
		$formatted_sizes = array(
			'full'      => __( 'Full Size', 'email-subscribers' ),
			'medium'    => __( 'Medium Size', 'email-subscribers' ),
			'thumbnail' => __( 'Thumbnail', 'email-subscribers' ),
		);

		foreach ( $sizes as $name => $size ) {
			$label 					  = ucfirst( str_replace( array( '-', '_' ), ' ', $name) );
			$formatted_sizes[ $name ] = $label;
		}
	
		return $formatted_sizes;
	}
	

	/**
	 * Get IG Option
	 *
	 * @param $option
	 * @param null   $default
	 *
	 * @return mixed|void|null
	 *
	 * @since 4.0.15
	 */
	public static function get_ig_option( $option, $default = null ) {

		if ( empty( $option ) ) {
			return null;
		}

		$option_prefix = 'ig_es_';

		return get_option( $option_prefix . $option, $default );

	}

	/**
	 * Set ig option
	 *
	 * @param $option
	 * @param $value
	 *
	 * @return bool|null
	 *
	 * @since 4.0.15
	 */
	public static function set_ig_option( $option, $value ) {

		if ( empty( $option ) ) {
			return null;
		}

		$option_prefix = 'ig_es_';

		return update_option( $option_prefix . $option, $value, false );

	}

	/**
	 * Delete email subscriber options
	 *
	 * @param string $option
	 *
	 * @return bool|null
	 *
	 * @since 4.0.15
	 */
	public static function delete_ig_option( $option = null ) {
		if ( empty( $option ) ) {
			return null;
		}

		$option_prefix = 'ig_es_';

		return delete_option( $option_prefix . $option );
	}

	/**
	 * Convert categories array to string
	 *
	 * @param array $categories
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function convert_categories_array_to_string( $categories = array() ) {

		$categories_str = '';

		if ( is_array( $categories ) && count( $categories ) > 0 ) {
			$categories_str = '##' . implode( '##', $categories ) . '##';
			$categories_str = wp_specialchars_decode( $categories_str, ENT_QUOTES );
		}

		return $categories_str;
	}

	public static function onboarding_convert_category_value_to_string( $category_value = array() ) {
		$category_str = '';
	
		if (!empty($category_value) && is_array($category_value)) {
			$category_str = '##post:' . $category_value[0] . '##';
			$category_str = wp_specialchars_decode( $category_str, ENT_QUOTES );
		}
	
		return $category_str;
	}


	/**
	 * Convert categories string to array
	 *
	 * @param string $categories_str
	 * @param bool   $keep_ids
	 *
	 * @return array|mixed
	 *
	 * @since 4.0.0
	 */
	public static function convert_categories_string_to_array( $categories_str = '', $keep_ids = true ) {
		$categories = array();
		if ( strlen( $categories_str ) > 0 ) {
			$categories_str = trim( trim( $categories_str ), '##' );
			$categories     = explode( '##', $categories_str );
			$categories     = str_replace( '{a}', '', $categories );

			if ( ! $keep_ids ) {
				$categories = array_map( array( 'ES_Common', 'convert_id_to_name' ), $categories );
			}
		}

		return $categories;
	}

	/**
	 * Convert Category id to name
	 *
	 * @param $category
	 *
	 * @return string
	 *
	 * @since 4.1.0
	 */
	public static function convert_id_to_name( $category ) {
		if ( ! in_array( $category, array( 'All', 'None' ), true ) ) {
			return get_cat_name( $category );
		} else {
			return $category;
		}
	}

	/**
	 * Get category id based on name
	 *
	 * @param $category
	 *
	 * @return int|string
	 *
	 * @since 4.1.0
	 */
	public static function convert_name_to_id( $category ) {
		if ( strpos( $category, '{T}' ) === false ) {
			$category = wp_specialchars_decode( addslashes( $category ) );

			return get_cat_ID( $category );
		} else {
			return $category;
		}
	}

	/**
	 * Prepare categories ids string
	 *
	 * @param string $category
	 *
	 * @return string
	 *
	 * @since 4.1.0
	 */
	public static function prepare_category_string( $category = '' ) {
		$category_str = '';
		if ( ! empty( $category ) ) {
			$category_str = '##' . $category . '##';
		}

		return $category_str;
	}

	/**
	 * Prepare custom post types string
	 *
	 * @param string $post_type
	 *
	 * @return string
	 *
	 * @since 4.1.0
	 */
	public static function prepare_custom_post_type_string( $post_type = '' ) {
		$post_type_str = '';
		if ( ! empty( $post_type ) ) {
			$post_type_str = '##{T}' . $post_type . '{T}##';
		}

		return $post_type_str;
	}

	/**
	 * Convert categories name string into ids string
	 *
	 * @param $categories_str
	 *
	 * @return string
	 *
	 * @since 4.1.0
	 */
	public static function prepare_categories_migration_string( $categories_str ) {
		$categories     = self::convert_categories_string_to_array( $categories_str, true );
		$categories     = array_map( array( 'ES_Common', 'convert_name_to_id' ), $categories );
		$categories_str = self::convert_categories_array_to_string( $categories );

		return $categories_str;

	}

	/**
	 * Prepare first name & last name from name
	 *
	 * @param string $name
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public static function prepare_first_name_last_name( $name = '' ) {
		$result = array(
			'first_name' => '',
			'last_name'  => '',
		);

		if ( ! empty( $name ) ) {
			// Find out first name and last name
			$name_parts = explode( ' ', $name );
			$last_name  = '';
			if ( count( $name_parts ) > 1 ) {
				$first_name = array_shift( $name_parts );
				$last_name  = implode( ' ', $name_parts );
			} else {
				$first_name = array_shift( $name_parts );
			}

			$result['first_name'] = trim( $first_name );
			$result['last_name']  = trim( $last_name );
		}

		return $result;
	}

	/**
	 * Prepare name from first name & last name
	 *
	 * @param string $first_name
	 * @param string $last_name
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function prepare_name_from_first_name_last_name( $first_name = '', $last_name = '' ) {
		$first_name = trim( $first_name );
		$last_name  = trim( $last_name );

		return trim( $first_name . ' ' . $last_name );
	}

	/**
	 * Get name from email
	 *
	 * @param $email
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public static function get_name_from_email( $email ) {
		$name = strstr( $email, '@', true );

		return trim( $name );
	}

	/**
	 * Migrate Older wigets into newer one.
	 *
	 * @since 4.0.5
	 */
	public static function migrate_widgets() {
		/**
		 * Option - 1 -> ES < 3.x email-subscribers
		 * Option - 2 -> ES < 4.0.4 email_subscriber_widget
		 * Option - 3 -> ES > 4.0.5 email-subscribers-form
		 *
		 *   - Fetch Option 1 from options table
		 *   - Create a form
		 *   - Set a new option with Option - 3 (title, form_id)
		 *
		 *   - Fetch Option 2 from options table
		 *   - Create a form with data
		 *   - Set a new option with Option - 3 (title, form_id)
		 *
		 *   - Fetch sidebar_widgets option from options table
		 *   - Change Option 1 with Option 3
		 *   - Change Option 2 with Option 3
		 */
		$es_3_widget_option   = 'widget_email-subscribers';
		$es_4_widget_option   = 'widget_email_subscriber_widget';
		$latest_widget_option = 'widget_email-subscribers-form';

		$es_3_widget_option_data = get_option( $es_3_widget_option, '' );
		if ( ! empty( $es_3_widget_option_data ) ) {
			$es_3_widget_option_data = maybe_unserialize( $es_3_widget_option_data );

			if ( is_array( $es_3_widget_option_data ) ) {
				foreach ( $es_3_widget_option_data as $key => $data ) {
					if ( is_array( $data ) && count( $data ) > 0 && isset( $data['es_title'] ) && isset( $data['es_desc'] ) && isset( $data['es_name'] ) && isset( $data['es_group'] ) ) {
						$title        = ! empty( $data['es_title'] ) ? $data['es_title'] : '';
						$name_visible = ( ! empty( $data['es_name'] ) && 'yes' === strtolower( $data['es_name'] ) ) ? 'yes' : 'no';
						$desc         = ! empty( $data['es_desc'] ) ? $data['es_desc'] : '';
						$group        = ! empty( $data['es_group'] ) ? $data['es_group'] : '';

						$list = ES()->lists_db->get_list_by_name( $group );

						$list_id = 1;
						if ( ! empty( $list ) ) {
							$list_id = $list['id'];
						}

						$name = 'Widget - ' . $title;

						$data = array(
							'name'          => $name,
							'desc'          => $desc,
							'name_visible'  => $name_visible,
							'name_required' => 'no',
							'list_visible'  => 'no',
							'lists'         => array( $list_id ),
							'af_id'         => 0,
						);

						$prepared_form_data = ES_Forms_Table::prepare_form_data( $data );

						$inserted_form_id = ES()->forms_db->add_form( $prepared_form_data );

						$data_to_set = array(
							'title'   => $title,
							'form_id' => $inserted_form_id,
						);

						$es_3_widget_option_data[ $key ] = $data_to_set;
					}
				}

				update_option( $latest_widget_option, $es_3_widget_option_data );
			}
		}

		$es_4_widget_option_data = get_option( $es_4_widget_option, '' );
		if ( ! empty( $es_4_widget_option_data ) ) {
			$es_4_widget_option_data = maybe_unserialize( $es_4_widget_option_data );

			if ( is_array( $es_4_widget_option_data ) ) {
				foreach ( $es_4_widget_option_data as $key => $data ) {
					if ( is_array( $data ) && count( $data ) > 0 && isset( $data['title'] ) && isset( $data['short_desc'] ) && isset( $data['display_name'] ) && isset( $data['subscribers_group'] ) ) {
						$title        = ! empty( $data['title'] ) ? $data['title'] : '';
						$name_visible = ( ! empty( $data['display_name'] ) && 'yes' === strtolower( $data['display_name'] ) ) ? 'yes' : 'no';
						$desc         = ! empty( $data['short_desc'] ) ? $data['short_desc'] : '';
						$list_id      = ! empty( $data['subscribers_group'] ) ? $data['subscribers_group'] : '';

						if ( empty( $list_id ) ) {
							$list_id = 1;
						}

						$name = 'Widget - ' . $title;

						$data = array(
							'name'          => $name,
							'desc'          => $desc,
							'name_visible'  => $name_visible,
							'name_required' => 'no',
							'list_visible'  => 'no',
							'lists'         => array( $list_id ),
							'af_id'         => 0,
						);

						$prepared_form_data = ES_Forms_Table::prepare_form_data( $data );

						$inserted_form_id = ES()->forms_db->add_form( $prepared_form_data );

						$data_to_set = array(
							'title'   => $title,
							'form_id' => $inserted_form_id,
						);

						$es_4_widget_option_data[ $key ] = $data_to_set;
					}
				}

				update_option( $latest_widget_option, $es_4_widget_option_data );
			}
		}

		// Update sidebars_widgets options.
		$sidebars_widgets = get_option( 'sidebars_widgets', '' );
		if ( ! empty( $sidebars_widgets ) ) {
			$widgets_data = maybe_unserialize( $sidebars_widgets );

			if ( is_array( $widgets_data ) && count( $widgets_data ) > 0 ) {
				foreach ( $widgets_data as $key => $data ) {
					if ( is_array( $data ) && count( $data ) > 0 ) {
						foreach ( $data as $k => $v ) {
							if ( strstr( $v, 'email-subscribers-' ) ) {
								$v                          = str_replace( 'email-subscribers-', 'email-subscribers-form-', $v );
								$widgets_data[ $key ][ $k ] = $v;
							}

							if ( strstr( $v, 'email_subscriber_widget-' ) ) {
								$v                          = str_replace( 'email_subscriber_widget-', 'email-subscribers-form-', $v );
								$widgets_data[ $key ][ $k ] = $v;
							}
						}
					}
				}

				update_option( 'sidebars_widgets', $widgets_data );
			}
		}

	}

	/**
	 * Filter Category
	 *
	 * @param $category
	 *
	 * @return string
	 *
	 * @sinc 4.1.0
	 */
	public static function temp_filter_category( $category ) {
		return trim( trim( $category ), '#' );
	}

	/**
	 * Show Message
	 *
	 * @param string $message
	 * @param string $status
	 * @param bool   $is_dismissible
	 *
	 * @since 4.1.0
	 */
	public static function show_message( $message = '', $status = 'success', $is_dismissible = true ) {

		$class = 'notice notice-success';
		if ( 'error' === $status ) {
			$class = 'notice notice-error';
		}

		if ( $is_dismissible ) {
			$class .= ' is-dismissible';
		}
		/* translators: 1: Class name 2: Message */
		echo sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );

	}

	/**
	 * Prepare navigation tabs
	 *
	 * @param $navigation_tabs
	 *
	 * @since 4.1.0
	 */
	public static function prepare_main_header_navigation( $navigation_tabs ) {

		foreach ( $navigation_tabs as $key => $navigation ) {
			$action          = ! empty( $navigation['action'] ) ? $navigation['action'] : '';
			$action_label    = ! empty( $navigation['label'] ) ? $navigation['label'] : '';
			$url             = ! empty( $navigation['url'] ) ? $navigation['url'] : '';
			$indicator_class = ! empty( $navigation['indicator_type'] ) ? 'ig-es-indicator-' . $navigation['indicator_type'] : 'ig-es-indicator-new';
			$show_indicator  = ! empty( $navigation['indicator_option'] ) ? ( ( get_option( $navigation['indicator_option'], 'yes' ) === 'yes' ) ? true : false ) : false;
			$indicator_label = ! empty( $navigation['indicator_label'] ) ? $navigation['indicator_label'] : '';
			$is_imp          = ! empty( $navigation['is_imp'] ) ? $navigation['is_imp'] : false;
			?>

			<a href="<?php echo esc_url( $url ); ?>">
				<button  class="
								<?php
								if ( $is_imp ) {
									echo esc_attr( 'primary' );
								} else {
									echo esc_attr( 'secondary' );
								}
								?>
				"><?php echo esc_html( $action_label ); ?>
				<?php if ( $show_indicator ) { ?>
					<span class="ig-es-indicator <?php echo esc_attr( $indicator_class ); ?>">
								<?php echo esc_html( $indicator_label ); ?>
							</span>

				<?php } ?>
				</button>
			</a>
			<?php
		}
	}

	/**
	 * Prepare information box to show different kind of information
	 * info | warnings | success | error
	 *
	 * @param $info
	 * @param $content_html
	 *
	 * @since 4.1.0
	 */
	public static function prepare_information_box( $info, $content_html ) {

		$default_args = array(
			'type'       => 'info',
			'center'     => true,
			'box_shadow' => true,
			'show_icon'  => true,
		);

		$info = wp_parse_args( $info, $default_args );

		$type          = $info['type'];
		$show_icon     = $info['show_icon'];
		$is_center     = $info['center'];
		$is_box_shadow = $info['box_shadow'];

		$div_class = 'ig-es-information-box';
		if ( $is_center ) {
			$div_class .= ' ig-es-center';
		}

		if ( $is_box_shadow ) {
			$div_class .= ' ig-es-box-shadow';
		}

		if ( $type ) {
			$div_class .= ' ig-es-' . $type;
		}

		?>

		<div class="<?php echo esc_attr( $div_class ); ?>">
			<div class="ig-vertical-align">
				<?php if ( $show_icon ) { ?>
					<div class="ig-es-icon text-center">
						<span class="dashicons ig-es-icon-<?php echo esc_attr( $type ); ?>"></span>
					</div>
				<?php } ?>
				<div class="ig-es-info-message">
					<?php echo wp_kses_post( $content_html ); ?>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Render Quick Feedback Widget
	 *
	 * @param $params
	 *
	 * @since 4.1.0
	 */
	public static function render_feedback_widget( $params ) {
		global $ig_es_feedback;

		$feedback = $ig_es_feedback;

		if ( ! $feedback->can_show_feedback_widget() ) {
			return;
		}

		$default_params = array(
			'set_transient' => true,
			'force'         => false,
			'show_once'     => false,
		);

		$params = wp_parse_args( $params, $default_params );

		if ( ! empty( $params['event'] ) ) {

			$event = $feedback->event_prefix . $params['event'];
			$force = ! empty( $params['force'] ) ? $params['force'] : false;

			$can_show = false;

			if ( $force ) {
				$can_show = true;
			} else {
				if ( ! $feedback->is_event_transient_set( $event ) ) {
					$can_show = true;

					$feedback_data = $feedback->get_event_feedback_data( $feedback->plugin_abbr, $event );
					if ( count( $feedback_data ) > 0 ) {
						$show_once              = $params['show_once'];
						$feedback_data          = array_reverse( $feedback_data );
						$last_feedback_given_on = $feedback_data[0]['created_on'];

						// If event feedback given within 45 days or show event only once?
						// Don't show now
						if ( $show_once || ( strtotime( $last_feedback_given_on ) > strtotime( '-45 days' ) ) ) {
							$can_show = false;
						}
					}
				}
			}

			if ( $can_show ) {
				if ( 'star' === $params['type'] ) {
					$feedback->render_stars( $params );
				} elseif ( 'emoji' === $params['type'] ) {
					$feedback->render_emoji( $params );
				} elseif ( 'feedback' === $params['type'] ) {
					$feedback->render_general_feedback( $params );
				} elseif ( 'fb' === $params['type'] ) {
					/**
					 * We are not calling home for this event and we want to show
					 * this Widget only once. So, we are storing feedback data now.
					 */
					$feedback->set_feedback_data( 'ig_es', $event );
					$feedback->render_fb_widget( $params );
				} elseif ( 'poll' === $params['type'] ) {
					$feedback->set_feedback_data( 'ig_es', $event );
					$feedback->render_poll_widget( $params );
				}
			}
		}

	}

	/**
	 * Get all restricted settings which we can't share
	 *
	 * @return array
	 *
	 * @since 4.6.6
	 */
	public static function get_restricted_settings() {

		return array(
			'ig_es_admin_new_contact_email_content',
			'ig_es_admin_emails',
			'ig_es_admin_new_contact_email_subject',
			'ig_es_admin_notices',
			'ig_es_confirmation_mail_content',
			'ig_es_confirmation_mail_subject',
			'ig_es_coupons',
			'ig_es_cron_admin_email',
			'ig_es_cron_admin_email_subject',
			'ig_es_cronurl',
			'ig_es_current_version_date_details',
			'ig_es_custom_admin_notice_bfcm_2019',
			'ig_es_custom_admin_notice_covid_19',
			'ig_es_custom_admin_notice_halloween_offer_2020',
			'ig_es_db_update_history',
			'ig_es_default_subscriber_imported',
			'ig_es_feedback_data',
			'ig_es_form_submission_success_message',
			'ig_es_last_cron_run',
			'ig_es_last_updated_blocked_domains',
			'ig_es_mailer_settings',
			'ig_es_ob_skip_email_receive_error',
			'ig_es_offer_bfcm_done_2019',
			'ig_es_offer_covid_19',
			'ig_es_onboarding_test_campaign_error',
			'ig_es_opt_in_consent_text',
			'ig_es_optin_link',
			'ig_es_optin_page',
			'ig_es_send_email_action_response',
			'ig_es_roles_and_capabilities',
			'ig_es_send_email_action_response',
			'ig_es_sent_report_content',
			'ig_es_sent_report_subject',
			'ig_es_set_widget',
			'ig_es_show_opt_in_consent',
			'ig_es_show_sync_tab',
			'ig_es_subscription_error_messsage',
			'ig_es_subscription_success_message',
			'ig_es_sync_wp_users',
			'ig_es_unsubscribe_error_message',
			'ig_es_run_cron_on',
			'ig_es_run_cron_time',
			'ig_es_unsubscribe_link',
			'ig_es_unsubscribe_link_content',
			'ig_es_unsubscribe_page',
			'ig_es_unsubscribe_success_message',
			'ig_es_update_processed_tasks',
			'ig_es_update_tasks_to_process',
			'ig_es_welcome_email_content',
			'ig_es_welcome_email_subject',
			'ig_es_email_sent_data',
			'ig_es_remote_gallery_items',
		);

	}

	/**
	 * Get coma(,) separated lists name based on list ids
	 *
	 * @param array $list_ids
	 *
	 * @return string
	 *
	 * @since 4.1.13
	 */
	public static function prepare_list_name_by_ids( $list_ids = array() ) {
		$list_name = '';
		if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {
			$lists_id_name_map = ES()->lists_db->get_list_id_name_map();
			$lists_name        = array();
			foreach ( $list_ids as $list_id ) {
				if ( ! empty( $lists_id_name_map[ $list_id ] ) ) {
					$lists_name[] = $lists_id_name_map[ $list_id ];
				}
			}

			$list_name = implode( ', ', $lists_name );
		}

		return $list_name;
	}

	/**
	 * Update Total Email Sent count
	 *
	 * @param int $sent_count Sent emails count
	 *
	 * @since 4.1.15
	 *
	 * @since 4.7.5 Added $sent_count parameter
	 */
	public static function update_total_email_sent_count( $sent_count = 0 ) {

		$current_date = ig_es_get_current_date();
		$current_hour = ig_es_get_current_hour();

		$email_sent_data_option = 'email_sent_data';

		// Get total emails sent in this hour
		$email_sent_data = self::get_ig_option( $email_sent_data_option, array() );

		$total_emails_sent = 0;
		$data              = array();
		if ( is_array( $email_sent_data ) && ! empty( $email_sent_data[ $current_date ] ) && ! empty( $email_sent_data[ $current_date ][ $current_hour ] ) ) {
			$total_emails_sent = $email_sent_data[ $current_date ][ $current_hour ];
		}

		// Add count for sent emails.
		$total_emails_sent += $sent_count;

		// We want to store only current hour data.
		$data[ $current_date ][ $current_hour ] = $total_emails_sent;

		self::set_ig_option( $email_sent_data_option, $data );

	}

	/**
	 * Check whether user has access to this page
	 *
	 * @param $page
	 *
	 * @return bool|mixed|void
	 *
	 * @since 4.2.3
	 */
	public static function ig_es_can_access( $page ) {

		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return false;
		}

		$default_permission = 'manage_options';

		$can_access = $user->has_cap( $default_permission );

		// Is Admin? Have full access.
		if ( $can_access ) {
			return true;
		}

		// We are using this filter in ES Premium to check permission.
		return apply_filters( 'ig_es_can_access', $can_access, $page );

	}

	/**
	 * Get accessible submenus
	 *
	 * @return array|mixed|void
	 *
	 * @since 4.2.3
	 */
	public static function ig_es_get_accessible_sub_menus() {

		$sub_menus = array();

		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return $sub_menus;
		}

		$default_permission = 'manage_options';
		$is_administrator   = $user->has_cap( $default_permission );

		// Is user administrator? User has access to all submenus
		if ( $is_administrator ) {
			$sub_menus = array(
				'dashboard',
				'workflows',
				'audience',
				'reports',
				'logs',
				'forms',
				'campaigns',
				'sequences',
				'settings',
				'ig_redirect',
				'custom_fields',
				'drag_drop_editor',
				'gallery',
				'template',
			);

			return $sub_menus;
		}

		// We are using this in ES Premium
		$sub_menus = apply_filters( 'ig_es_accessible_sub_menus', $sub_menus );

		return array_unique( $sub_menus );
	}

	/**
	 * Generate Hash
	 *
	 * @param $length
	 *
	 * @return false|string
	 *
	 * @since 4.2.4
	 */
	public static function generate_hash( $length ) {

		$length = ( $length ) ? $length : 12;

		return substr( md5( uniqid() . uniqid() . wp_rand( $length, 64 ) ), 0, $length );
	}

	/**
	 * Get useful article links
	 *
	 * @return array
	 *
	 * @since 4.4.2
	 */
	public static function get_useful_articles( $upsell = true ) {

		$articles_upsell = array();

		$blog_articles = array(
			array(
				'title' => __( 'Top 10 Tips on How to Build an Email List', 'email-subscribers' ),
				'link'  => 'https://www.icegram.com/email-list/',
			),
			array(
				'title' => __( 'Why are Your Email Unsubscribes Increasing and How to Fix Them?', 'email-subscribers' ),
				'link'  => 'https://www.icegram.com/unsubscribes/',
			),
			array(
				'title' => __( 'Balance Email Marketing and Social Media Marketing', 'email-subscribers' ),
				'link'  => 'https://www.icegram.com/email-marketing-and-social-media-marketing/',
			),
			array(
				'title' => __( 'Use social proof to grow blog traffic through email', 'email-subscribers' ),
				'link'  => 'https://www.icegram.com/social-proof/',
			),
			array(
				'title' => __( '5 Simple Tricks to Improve Email Marketing Campaign Results', 'email-subscribers' ),
				'link'  => 'https://www.icegram.com/email-marketing-campaign/',
			),
			array(
				'title' => __( '<b>Icegram</b> recommends <b>Kloudbean hosting</b> for best performance of your website', 'email-subscribers' ),
				'link'  => 'https://www.kloudbean.com/?aff=qkn6qxtvcvc4sb',
				'label'       => __( 'Migrate Now', 'email-subscribers' ),
				'label_class' => 'bg-green-100 text-green-800',
			)
		);

		if ( $upsell ) {

			$pricing_page_url = admin_url( 'admin.php?page=es_pricing' );

			$articles_upsell[] = array(
				'title'       => __( '<b>Icegram Express</b> Secret Club', 'email-subscribers' ),
				'link'        => 'https://www.facebook.com/groups/2298909487017349/',
				'label'       => __( 'Join Now', 'email-subscribers' ),
				'label_class' => 'bg-green-100 text-green-800',
			);

			if ( ! ES()->is_premium() ) {
				$articles_upsell[] = array(
					'title'       => __( 'Unlock all premium features', 'email-subscribers' ),
					'link'        => $pricing_page_url,
					'label'       => __( '25% OFF', 'email-subscribers' ),
					'label_class' => 'bg-green-100 text-green-800',
				);
			}
		}

		$articles = array_merge( $blog_articles, $articles_upsell );
		
		return $articles;
	}

	/**
	 * Get utm tracking url
	 *
	 * @param array $utm_args
	 *
	 * @return mixed|string
	 *
	 * @since 4.4.5
	 */
	public static function get_utm_tracking_url( $utm_args = array() ) {

		$url          = ! empty( $utm_args['url'] ) ? $utm_args['url'] : 'https://icegram.com/email-subscribers-pricing/';
		$utm_source   = ! empty( $utm_args['utm_source'] ) ? $utm_args['utm_source'] : 'in_app';
		$utm_medium   = ! empty( $utm_args['utm_medium'] ) ? $utm_args['utm_medium'] : '';
		$utm_campaign = ! empty( $utm_args['utm_campaign'] ) ? $utm_args['utm_campaign'] : 'es_upsell';

		if ( ! empty( $utm_source ) ) {
			$url = add_query_arg( 'utm_source', $utm_source, $url );
		}

		if ( ! empty( $utm_medium ) ) {
			$url = add_query_arg( 'utm_medium', $utm_medium, $url );
		}

		if ( ! empty( $utm_campaign ) ) {
			$url = add_query_arg( 'utm_campaign', $utm_campaign, $url );
		}

		return $url;

	}

	/**
	 * Get Captcha setting
	 *
	 * @param $id null|int
	 * @param $data array
	 *
	 * @return bool|mixed|void
	 *
	 * @since 4.4.7
	 */
	public static function get_captcha_setting( $form_id = null, $data = array() ) {

		if ( ! empty( $form_id ) ) {

			$form_id = (int) $form_id;

			$form_data = ES()->forms_db->get_form_by_id( $form_id );

			$settings = ig_es_get_data( $form_data, 'settings', array() );

			if ( ! empty( $settings ) ) {

				$settings = maybe_unserialize( $settings );

				if ( isset( $settings['captcha'] ) ) {
					return empty( $settings['captcha'] ) ? 'no' : $settings['captcha'];
				}
			}

			return get_option( 'ig_es_enable_captcha', 'no' );
		}

		if ( ! isset( $data['captcha'] ) || empty( $data['captcha'] ) ) {
			$setting = get_option( 'ig_es_enable_captcha', 'no' );
		} else {
			$setting = $data['captcha'];
		}

		return $setting;
	}

	public static function convert_date_to_wp_date( $date ) {
		$convert_date_format = get_option( 'date_format' );
		$convert_time_format = get_option( 'time_format' );

		return date_i18n( "$convert_date_format $convert_time_format", strtotime( $date ) );
	}

	/**
	 * Get next local midnight time
	 * 
	 * @since 5.5.2
	 * 
	 * @return string $local_next_midnight_time next local midnight time
	 */
	public static function get_next_local_midnight_time() {
		$next_day_utc_time   = time() + DAY_IN_SECONDS;
		$offset_in_seconds   = self::get_timezone_offset_in_seconds();
		$next_day_local_time = $next_day_utc_time + $offset_in_seconds;
		$next_day_local_date = date_i18n( 'Y-m-d H:i:s', $next_day_local_time );
	
		$local_date_obj = new DateTime( $next_day_local_date );
		$local_date_obj->setTime( 0, 0, 0 );
	
		$local_next_midnight_time = $local_date_obj->getTimestamp();

		return $local_next_midnight_time;
	}

	/**
	 * Convert UTC time for local midnight time
	 * 
	 * @since 5.5.2
	 * 
	 * @return string $utc_time_for_local_midnight UTC time local midnight time
	 */
	public static function get_utc_time_for_local_midnight_time() {
		$offset_in_seconds           = self::get_timezone_offset_in_seconds();
		$next_local_midnight_time    = self::get_next_local_midnight_time();
		$utc_time_for_local_midnight = $next_local_midnight_time - $offset_in_seconds;
		return $utc_time_for_local_midnight;
	}

	/**
	 * Get site's timezone offset in seconds
	 * 
	 * @since 5.5.2
	 * 
	 * @return int $offset_in_seconds
	 */
	public static function get_timezone_offset_in_seconds() {
		$offset            = get_option( 'gmt_offset' );
		$offset_in_seconds = $offset * HOUR_IN_SECONDS;
		return $offset_in_seconds;
	}

	/**
	 * Method to convert emojis character into their equivalent HTML entity in the given string if conversion supported
	 * else remove them
	 *
	 * @param string $string String with emojis characters.
	 *
	 * @return string $string Converted string with equivalent HTML entities
	 *
	 * @since 4.4.7
	 */
	public static function handle_emoji_characters( $string = '' ) {

		if ( ! empty( $string ) ) {
			if ( function_exists( 'wp_encode_emoji' ) ) {
				$string = wp_encode_emoji( $string );
			} else {
				$string = preg_replace(
					'%(?:
						\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
					| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
					| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
				)%xs',
					'',
					$string
				);
			}
		}

		return $string;
	}

	/**
	 * Get Campaign type
	 *
	 * @param bool $reverse
	 *
	 * @return array
	 *
	 * @since 4.4.8
	 */
	public static function get_campaign_type_key_name_map( $reverse = false ) {

		$campaign_type = self::get_campaign_types();

		if ( $reverse ) {
			$campaign_type = array_flip( $campaign_type );
		}

		return $campaign_type;
	}

	/**
	 * Get Campaign type
	 *
	 * @return array
	 *
	 * @since 4.6.1
	 */
	public static function get_campaign_types( $disallowed_types = array() ) {

		$template_types = apply_filters( 'es_template_type', array() );

		if ( ! empty( $disallowed_types ) ) {
			foreach ( $disallowed_types as $disallowed_type ) {
				if ( isset( $template_types[ $disallowed_type ] ) ) {
					unset( $template_types[ $disallowed_type ] );
				}
			}
		}

		return $template_types;
	}

	/**
	 * Prepare Campaign Status dropdown
	 *
	 * @param string $selected
	 * @param string $default_label
	 *
	 * @return string
	 *
	 * @since 4.4.8
	 */
	public static function prepare_campaign_type_dropdown_options( $selected = '', $default_label = '' ) {

		$campaign_type = self::get_campaign_type_key_name_map();

		$dropdown = '<option class="text-sm" value="">' . esc_html__( 'All Types', 'email-subscribers' ) . '</option>';
		foreach ( $campaign_type as $key => $type ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( strtolower( $selected ) === strtolower( $key ) ) {
				$dropdown .= 'selected = selected';
			}

			$dropdown .= '>' . esc_html( $type ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Get Campaign Statuses
	 *
	 * @param string $campaign_type
	 * @param bool   $reverse
	 *
	 * @return array
	 *
	 * @since 4.4.8
	 */
	public static function get_campaign_statuses_key_name_map( $reverse = false ) {

		$statuses = array(
			'0' => __( 'Draft', 'email-subscribers' ),
			'1' => __( 'Active', 'email-subscribers' ),
			'2' => __( 'Scheduled', 'email-subscribers' ),
			'3' => __( 'Sending', 'email-subscribers' ),
			'4' => __( 'Paused', 'email-subscribers' ),
			'5' => __( 'Sent', 'email-subscribers' ),
		);

		if ( $reverse ) {
			$statuses = array_flip( $statuses );
		}

		return $statuses;
	}

	public static function get_campaign_status_code_map() {
		$status_codes = array(
			'draft'      => IG_ES_CAMPAIGN_STATUS_IN_ACTIVE,
			'active'     => IG_ES_CAMPAIGN_STATUS_ACTIVE,
			'scheduled'  => IG_ES_CAMPAIGN_STATUS_SCHEDULED,
			'queued'     => IG_ES_CAMPAIGN_STATUS_QUEUED,
			'paused'     => IG_ES_CAMPAIGN_STATUS_PAUSED,
			'finished'   => IG_ES_CAMPAIGN_STATUS_FINISHED,
		);

		return $status_codes;
	}

	/**
	 * Prepare Campaign Status dropdown
	 *
	 * @param string $selected
	 * @param string $default_label
	 *
	 * @return string
	 *
	 * @since 4.4.8
	 */
	public static function prepare_campaign_statuses_dropdown_options( $selected = '', $default_label = '' ) {

		$statuses = self::get_campaign_statuses_key_name_map();

		$dropdown = '<option class="text-sm" value="">' . esc_html__( 'All Statuses', 'email-subscribers' ) . '</option>';

		foreach ( $statuses as $key => $status ) {

			$dropdown .= '<option class="text-sm" value="' . esc_attr( $key ) . '" ';

			if ( strtolower( $selected ) === strtolower( $key ) ) {
				$dropdown .= 'selected = selected';
			}

			$dropdown .= '>' . esc_html( $status ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Can show coupon code?
	 *
	 * @param string $coupon_code
	 *
	 * @return bool
	 *
	 * @since 4.4.8
	 */
	public static function can_show_coupon( $coupon = 'PREMIUM10' ) {

		$can_show = true;

		if ( $can_show ) {
			self::update_coupon_data( $coupon );
		}

		return $can_show;

		/*
		$coupons = get_option( 'ig_es_coupons', array() );

		$can_show = true;

		if ( ! empty( $coupons ) ) {

			if ( isset( $coupons[ $coupon ] ) ) {
				$last_shown_time = $coupons[ $coupon ]['last_shown_time'];

				if ( $last_shown_time <= time() - ( 7 * 24 * 60 * 60 ) ) {
					$can_show = true;
				} else {
					$can_show = false;
				}
			} else {
				$can_show = true;
			}
		}

		if ( $can_show ) {
			self::update_coupon_data( $coupon );
		}
		return $can_show;
		*/
	}

	/**
	 * Update coupons data
	 *
	 * @param $coupon
	 *
	 * @since 4.4.8
	 */
	public static function update_coupon_data( $coupon ) {
		$coupons = get_option( 'ig_es_coupons', array() );

		$shown_count = ! empty( $coupons[ $coupon ]['count'] ) ? $coupons[ $coupon ]['count'] : 0;

		$coupons[ $coupon ] = array(
			'last_shown_time' => time(),
			'count'           => $shown_count + 1,
		);

		update_option( 'ig_es_coupons', $coupons );
	}

	/**
	 * Method to convert timestamp to date
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public static function convert_timestamp_to_date( $timestamp, $format = '' ) {

		if ( empty( $format ) ) {
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$format      = $date_format . ' ' . $time_format;
		}

		return gmdate( $format, $timestamp );
	}

	/**
	 * Generate test mailbox user
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public static function generate_test_mailbox_user() {

		$admin_email = get_bloginfo( 'admin_email' );

		$parts = explode( '@', $admin_email );

		if ( count( $parts ) > 0 ) {
			$user = $parts[0];
		} else {
			$user = 'test';
		}

		$blog_url = get_bloginfo( 'url' );

		// If URI is like, eg. www.way2tutorial.com/
		$blog_url = trim( $blog_url, '/' );

		// If not have http:// or https:// then prepend it
		if ( ! preg_match( '#^http(s)?://#', $blog_url ) ) {
			$blog_url = 'http://' . $blog_url;
		}

		$url_parts = parse_url( $blog_url );

		// Remove www.
		$domain = preg_replace( '/^www\./', '', $url_parts['host'] );

		$hash = self::generate_hash( 5 );

		return $hash . '_' . $user . '_' . $domain;
	}

	/**
	 * Get mailbox name
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public static function get_test_email() {
		$mailbox_user = get_option( 'ig_es_test_mailbox_user', '' );

		if ( empty( $mailbox_user ) ) {
			$mailbox_user = self::generate_test_mailbox_user();
			update_option( 'ig_es_test_mailbox_user', $mailbox_user );
		}

		return $mailbox_user . '@box.icegram.com';
	}

	/**
	 * Get upselling information box
	 *
	 * @since 4.6.2
	 */
	public static function upsell_description_message_box( $upsell_info = array(), $echo = true ) {
		ob_start();
		?>
		<div class="inline-flex rounded-md shadow bg-teal-50 px-2 pt-1 my-2 w-full font-sans">
			<div class="px-2 pt-2 pb-2">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class='h-5 w-5 text-teal-400' fill='currentColor' viewBox='0 0 20 20'>
							<path fill-rule='evenodd'
								  d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z'
								  clip-rule='evenodd'/>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm leading-5 font-medium text-blue-800 hover:underline">
							<a href="<?php echo esc_url( $upsell_info['pricing_url'] ); ?>" target="_blank">
								<?php
								echo esc_html( $upsell_info['upgrade_title'] );
								?>
							</a>
						</h3>
					</div>
				</div>
				<div class="mt-2 ml-8 text-sm leading-5 text-teal-700">
					<p>
						<?php
						$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
						if ( ! empty( $upsell_info['upsell_message'] ) ) {
							echo wp_kses( $upsell_info['upsell_message'], $allowed_html_tags );
						}

						$timezone_format = _x( 'Y-m-d', 'timezone date format' );
						$ig_current_date = strtotime( date_i18n( $timezone_format ) );
						if ( ( ( $ig_current_date < strtotime( '2020-11-24' ) ) || ( $ig_current_date > strtotime( '2020-12-02' ) ) ) && self::can_show_coupon( 'PREMIUM10' ) ) {
							?>
					<p class="mb-1 mt-3">
							<?php
							echo wp_kses_post( __( 'Upgrade now & get <b> 10% discount!</b> <br/><br/>Use coupon code:' ), 'email-subscribers' );
							?>

						<span class="ml-2 px-1.5 py-1 font-medium bg-yellow-100 rounded-md border-2 border-dotted border-indigo-300 select-all"><?php echo esc_html( 'PREMIUM10' ); ?> </span>
					</p>
							<?php
						}
						if ( $upsell_info['cta_html'] ) {
							?>
						<div class="pt-6 text-center -ml-6 pb-2">
							<a href="<?php echo esc_url( $upsell_info['pricing_url'] ); ?>" target="_blank"
							   class="">
							   <button type="button" class="primary">
									<?php
									esc_html_e(
										'Upgrade',
										'email-subscribers'
									);
									?>
								</button>
							</a>
						</div>
							<?php
						}
						?>
				</div>
			</div>
		</div>

		<?php
		$message_html = ob_get_clean();
		if ( $echo ) {
			echo wp_kses( $message_html, $allowed_html_tags );
		} else {
			return $message_html;
		}
	}

	/**
	 * Prepare Campaign Report Status dropdown
	 *
	 * @param string $selected
	 * @param string $default_label
	 *
	 * @return string
	 *
	 * @since 4.6.5
	 */
	public static function prepare_campaign_report_statuses_dropdown_options( $statuses = array(), $selected = '', $default_label = '' ) {

		if ( ! empty( $statuses ) ) {
			$dropdown = '<option class="text-sm" value="">' . esc_html__( 'All Status', 'email-subscribers' ) . '</option>';

			foreach ( $statuses as $key => $status ) {

				$dropdown .= '<option class="text-sm" value="' . esc_attr( $key ) . '" ';

				if ( strtolower( $selected ) === strtolower( $key ) ) {
					$dropdown .= 'selected = selected';
				}

				$dropdown .= '>' . esc_html( $status ) . '</option>';
			}

			return $dropdown;
		}
	}

	/**
	 * Check whether the string is a valid JSON or not.
	 *
	 * @param string $string String we want to test if it's json.
	 *
	 * @return bool
	 *
	 * @since 4.6.14
	 */
	public static function is_valid_json( $string ) {

		return is_string( $string ) && is_array( json_decode( $string, true ) ) && ( json_last_error() === JSON_ERROR_NONE ) ? true : false;
	}

	/**
	 * Get HTML for tooltip
	 *
	 * @param string $tooltip_text
	 *
	 * @return string $tooltip_html
	 *
	 * @since 4.7.0
	 */
	public static function get_tooltip_html( $tooltip_text = '' ) {
		$tooltip_html = '';
		if ( ! empty( $tooltip_text ) ) {
			$tooltip_html = '<div class="inline-block es-tooltip relative align-middle cursor-pointer">
				<svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
				<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
					' . $tooltip_text . '
					<svg class="tooltip-arrow absolute mt-2 text-black text-opacity-100 h-2.5 left-0" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
						<polygon class="fill-current" points="0,0 127.5,127.5 255,0"/>
					</svg>
				</span>
			</div>';
		}

		return $tooltip_html;
	}

	/**
	 * Decode HTML entities
	 *
	 * @param string $string
	 *
	 * @return string $string
	 *
	 * @since 4.7.1
	 */
	public static function decode_entities( $string ) {

		preg_match_all( '/&#?\w+;/', $string, $entities, PREG_SET_ORDER );
		$entities = array_unique( array_column( $entities, 0 ) );

		if ( ! empty( $entities ) ) {
			foreach ( $entities as $entity ) {
				$decoded = mb_convert_encoding( $entity, 'UTF-8', 'HTML-ENTITIES' );
				$string  = str_replace( $entity, $decoded, $string );
			}
		}

		return $string;
	}

	/**
	 * Override wp editor tinymce formatting options
	 *
	 * @param array  $init
	 * @param string $editor_id
	 *
	 * @return array $init
	 *
	 * @since 4.7.3
	 */
	public static function override_tinymce_formatting_options( $init, $editor_id = '' ) {

		$init['wpautop']      = false; // Disable stripping of p tags in Text mode.
		$init['tadv_noautop'] = true; // Disable stripping of p tags in Text mode.
		$init['indent']       = true;

		// To disable stripping of some HTML elements like span when switching modes in wp editor from text-visual-text.
		$opts                            = '*[*]';
		$init['valid_elements']          = $opts;
		$init['extended_valid_elements'] = $opts;

		return $init;
	}

	/**
	 * Add external plugins for TinyMCE(WordPress classic) editor
	 *
	 * @param array  $plugin_array
	 * @return array $plugin_array
	 *
	 * @since 5.0.6
	 */
	public static function add_mce_external_plugins( $plugin_array ) {

		if ( is_array( $plugin_array ) ) {
			$plugin_array['undo_style_to_image'] = ES_PLUGIN_URL . 'lite/admin/js/tinymce-plugins/custom/undo-style-to-img-tag-conversion/plugin.js';
			$plugin_array['fullpage'] 			 = ES_PLUGIN_URL . 'lite/admin/js/tinymce-plugins/pre-built/fullpage/plugin.min.js';
		}

		return $plugin_array;
	}

	/**
	 * Get current request URL
	 *
	 * @return string $request_url
	 *
	 * @since 4.7.6
	 */
	public static function get_current_request_url() {
		static $request_url = '';

		if ( empty( $request_url ) ) {
			$request_url = add_query_arg( array() );
		}

		return esc_url_raw( $request_url );
	}

	public static function get_campaign_status_icon( $status = '' ) {
		ob_start();
		switch ( $status ) {
			case 'Sent':
				?>
				<svg class="inline-block mt-0.5 ml-2 h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
					<title><?php esc_attr__( 'Sent', 'email-subscribers' ); ?></title>
					<path fill-rule="evenodd"
						  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
						  clip-rule="evenodd"/>
				</svg>
				<?php
				break;
			case 'In Queue':
				?>
				<svg class="inline-block mt-0.5 ml-2 h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
					<title><?php esc_attr__( 'In Queue', 'email-subscribers' ); ?></title>
					<path fill-rule="evenodd"
						  d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
						  clip-rule="evenodd"/>
				</svg>
				<?php
				break;
			case 'Sending':
				?>
				<svg class="inline-block mt-0.5 ml-2 h-4 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
					<title><?php esc_attr__( 'Sending', 'email-subscribers' ); ?></title>
					<path fill-rule="evenodd"
						  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z"
						  clip-rule="evenodd"/>
				</svg>
				<?php
				break;
			case '1':
				?>
				<span class="inline-flex px-2 text-green-800 bg-green-100 rounded-full"><?php esc_html__( 'Active', 'email-subscribers' ); ?></span>
				<?php
				break;
			case '':
				?>
				<span class="inline-flex px-2 text-red-800 bg-red-100 rounded-full"><?php esc_html__( 'Inactive', 'email-subscribers' ); ?></span>
				<?php
		}

		$status = ob_get_clean();

		return $status;
	}

	/**
	 * Prepare custom field type dropdown options
	 *
	 * @param $selected
	 * @param $default_label
	 *
	 * @return string
	 *
	 * @since 4.8.4
	 */
	public static function prepare_fieldtype_dropdown_options( $selected = '', $default_label = '' ) {

		$default_status[0] = __( 'Select field type', 'email-subscribers' );

		$cf_type = array(
			'text' 		=> __( 'Text', 'email-subscribers' ),
			'textarea'  => __( 'TextArea', 'email-subscribers' ),
			'dropdown'  => __( 'Dropdown', 'email-subscribers' ),
			'radio'  	=> __( 'Radio', 'email-subscribers' ),
			'number' 	=> __( 'Number', 'email-subscribers' ),
			'date' 		=> __( 'Date', 'email-subscribers' ),
		);

		$field_types = array_merge( $default_status, $cf_type );

		$dropdown = '';
		foreach ( $field_types as $key => $type ) {
			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( strtolower( $selected ) === strtolower( $key ) ) {
				$dropdown .= 'selected = selected';
			}

			$dropdown .= '>' . esc_html( $type ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Get slug name without prefix
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 * @since 4.8.4
	 */
	public static function get_slug_without_prefix( $slug ) {

		$slug_name = explode( '_', $slug );
		unset( $slug_name[0] );
		unset( $slug_name[1] );
		return implode( '_', $slug_name	 );
	}

	/**
	 * Check if the domain is blocked based on email
	 *
	 * @param $email
	 *
	 * @return bool
	 *
	 * @since 4.1.0
	 */
	public static function is_domain_blocked( $email ) {

		if ( empty( $email ) ) {
			return true;
		}

		$domains = trim( get_option( 'ig_es_blocked_domains', '' ) );

		// No domains to block? Return
		if ( empty( $domains ) ) {
			return false;
		}

		$domains = explode( PHP_EOL, $domains );

		$domains = apply_filters( 'ig_es_blocked_domains', $domains );

		if ( empty( $domains ) ) {
			return false;
		}

		$rev_email = strrev( $email );
		foreach ( $domains as $domain ) {
			$domain = trim( $domain );
			if ( ! empty( $domain ) && strpos( $rev_email, strrev( $domain ) ) === 0 ) {
				$email_parts = explode( '@', $email );
				if ( ! empty( $email_parts[1] ) ) {
					$email_domain = $email_parts[1];
					if ( $email_domain === $domain ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get column datatype for custom field in contacts table
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 * @since 4.8.4
	 */
	public static function get_custom_field_col_datatype( $custom_field_type = 'text' ) {

		switch ( $custom_field_type ) {
			case 'number':
				return 'int(50)';
			case 'date':
				return 'date';
			case 'radio':
			case 'dropdown':
				return 'varchar(100)';
			default:
				return 'longtext';
		}

	}

	/**
	 * Prepare month year date filter dropdown options
	 *
	 * @param $selected
	 * @param $default_label
	 *
	 * @return string
	 *
	 * @since 5.0.5
	 */
	public static function prepare_datefilter_dropdown_options( $selected = '', $default_label = '' ) {

		global $wpdb;
	   // phpcs:disable
		$results = $wpdb->get_results( "SELECT DISTINCT MONTHNAME(`start_at`), YEAR(`start_at`) FROM {$wpdb->prefix}ig_mailing_queue;", ARRAY_A );
	   // phpcs:enable
		$field_options = array(
			'default' => $default_label
		);

		$field_key = '';
		$dropdown  = '';

		foreach ( $results as $key => $record ) {

			$month_name_value = $record['MONTHNAME(`start_at`)'];
			$year_value 	  = $record['YEAR(`start_at`)'];

			$field_key   = $year_value . gmdate( 'm', strtotime($month_name_value));
			$field_value = $month_name_value . ' ' . $year_value;

			$field_options[$field_key] = $field_value;

		}

		foreach ($field_options as $key => $option_value) {

			$value = ( 'default'  !== $key ) ? esc_attr($key):'';

			$dropdown .= '<option value = "' . $value . '" ';
			$dropdown .= selected( $selected, $key, false );
			$dropdown .= '>' . esc_html($option_value) . '</option>';

		}

		return $dropdown;
	}

	public static function get_in_between_content( $content, $start, $end ) {
		$r = explode( $start, $content );
		if ( isset( $r[1] ) ) {
			$r = explode( $end, $r[1] );

			return $r[0];
		}

		return '';
	}

	public static function get_popular_domains() {
		/** Domains list from https://github.com/mailcheck/mailcheck/wiki/List-of-Popular-Domains */
		$popular_domains = array(
			/* Default domains included */
			'aol.com', 'att.net', 'comcast.net', 'facebook.com', 'gmail.com', 'gmx.com', 'googlemail.com',
			'google.com', 'hotmail.com', 'hotmail.co.uk', 'mac.com', 'me.com', 'mail.com', 'msn.com',
			'live.com', 'sbcglobal.net', 'verizon.net', 'yahoo.com', 'yahoo.co.uk',

			/* Other global domains */
			'email.com', 'fastmail.fm', 'games.com' /* AOL */, 'gmx.net', 'hush.com', 'hushmail.com', 'icloud.com',
			'iname.com', 'inbox.com', 'lavabit.com', 'love.com' /* AOL */, 'outlook.com', 'pobox.com', 'protonmail.ch', 'protonmail.com', 'tutanota.de', 'tutanota.com', 'tutamail.com', 'tuta.io',
		   'keemail.me', 'rocketmail.com' /* Yahoo */, 'safe-mail.net', 'wow.com' /* AOL */, 'ygm.com' /* AOL */,
			'ymail.com' /* Yahoo */, 'zoho.com', 'yandex.com',

			/* United States ISP domains */
			'bellsouth.net', 'charter.net', 'cox.net', 'earthlink.net', 'juno.com',

			/* British ISP domains */
			'btinternet.com', 'virginmedia.com', 'blueyonder.co.uk', 'live.co.uk',
			'ntlworld.com', 'orange.net', 'sky.com', 'talktalk.co.uk', 'tiscali.co.uk',
			'virgin.net', 'bt.com',

			/* Domains used in Asia */
			'sina.com', 'sina.cn', 'qq.com', 'naver.com', 'hanmail.net', 'daum.net', 'nate.com', 'yahoo.co.jp', 'yahoo.co.kr', 'yahoo.co.id', 'yahoo.co.in', 'yahoo.com.sg', 'yahoo.com.ph', '163.com', 'yeah.net', '126.com', '21cn.com', 'aliyun.com', 'foxmail.com',

			/* French ISP domains */
			'hotmail.fr', 'live.fr', 'laposte.net', 'yahoo.fr', 'wanadoo.fr', 'orange.fr', 'gmx.fr', 'sfr.fr', 'neuf.fr', 'free.fr',

			/* German ISP domains */
			'gmx.de', 'hotmail.de', 'live.de', 'online.de', 't-online.de' /* T-Mobile */, 'web.de', 'yahoo.de',

			/* Italian ISP domains */
			'libero.it', 'virgilio.it', 'hotmail.it', 'aol.it', 'tiscali.it', 'alice.it', 'live.it', 'yahoo.it', 'email.it', 'tin.it', 'poste.it', 'teletu.it',

			/* Russian ISP domains */
			'bk.ru', 'inbox.ru', 'list.ru', 'mail.ru', 'rambler.ru', 'yandex.by', 'yandex.com', 'yandex.kz', 'yandex.ru', 'yandex.ua', 'ya.ru',

			/* Belgian ISP domains */
			'hotmail.be', 'live.be', 'skynet.be', 'voo.be', 'tvcablenet.be', 'telenet.be',

			/* Argentinian ISP domains */
			'hotmail.com.ar', 'live.com.ar', 'yahoo.com.ar', 'fibertel.com.ar', 'speedy.com.ar', 'arnet.com.ar',

			/* Domains used in Mexico */
			'yahoo.com.mx', 'live.com.mx', 'hotmail.es', 'hotmail.com.mx', 'prodigy.net.mx',

			/* Domains used in Canada */
			'yahoo.ca', 'hotmail.ca', 'bell.net', 'shaw.ca', 'sympatico.ca', 'rogers.com',

			/* Domains used in Brazil */
			'yahoo.com.br', 'hotmail.com.br', 'outlook.com.br', 'uol.com.br', 'bol.com.br', 'terra.com.br', 'ig.com.br', 'r7.com', 'zipmail.com.br', 'globo.com', 'globomail.com', 'oi.com.br'
		);

		return $popular_domains;
	}

	public static function is_popular_domain( $email ) {

		$is_email = is_email( $email );
		if ( ! $is_email ) {
			return false;
		}

		$email_parts = explode( '@', $email );
		$domain 	 = end( $email_parts );
		$$domain     = strtolower( $domain );

		$popular_domains = self::get_popular_domains();

		return in_array( $domain, $popular_domains, true );
	}

	public static function get_domain_from_url( $url ) {
		$pieces = parse_url($url);
		$domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
		return $domain;
	}
	
	public static function get_engagement_score_html( $engagement_score ) {
		if ( is_numeric( $engagement_score ) ) {
			$score_class = 'bad';

			if ( $engagement_score > 0 ) {
				$score_text = number_format_i18n( $engagement_score, 1 );
			} else {
				$score_text = 0;
			}

			if ( $engagement_score >= 4 ) {
				$score_class = 'excellent';
			} elseif ( $engagement_score >= 3 ) {
				$score_class = 'good';
			} elseif ( $engagement_score >= 2 ) {
				$score_class = 'low';
			} elseif ( $engagement_score >= 1 ) {
				$score_class = 'very-low';
			}
		}
		$engagement_score_html = ( is_numeric( $engagement_score ) ? '<div class="es-engagement-score ' . $score_class . '">' . $score_text . '</div>' : '-' );

		return $engagement_score_html;
	}

	public static function get_email_verify_mailbox_user() {
		$username = get_option( 'ig_es_test_mailbox_user', '' );
		if ( !empty( $username) ) {
			$username = 'spam_check_' . $username;
		}
		return( $username );
	}

	public static function get_ig_es_mailbox_domain() {
		$domain = 'box.icegram.com';
		return( $domain );
	}

	public static function get_email_verify_test_email() {
		$username = self::get_email_verify_mailbox_user();
		$domain   = self::get_ig_es_mailbox_domain();

		if ( !empty( $username) ) {
			$email = $username . '@' . $domain;
			return( $email );
		}
		return '';
	}

	public static function download_image_from_url( $image_url ) {

		$attachment_url = '';
		$upload_dir     = wp_upload_dir();
		$image_data     = file_get_contents( $image_url );
		$filename       = basename( $image_url );
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents( $file, $image_data );

		$wp_filetype = wp_check_filetype( $filename, null );
		$attachment  = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id   = wp_insert_attachment( $attachment, $file );
		if ( ! empty( $attach_id ) ) {
			$attachment_url = wp_get_attachment_url( $attach_id );
		}
		return $attachment_url;
	}

	/**
	 * Get list status(subscribed or unconfirmed) based on optin type(single optin or double optin)
	 * 
	 * @since 5.4.18
	 * 
	 * @return string
	 */
	public static function get_list_status_from_optin_type() {
		$es_optin_type = get_option( 'ig_es_optin_type' );
			
		if ( in_array( $es_optin_type, array( 'double_opt_in', 'double_optin' ), true ) ) { 
			$status = 'unconfirmed';
		} else {
			$status = 'subscribed';
		}

		return $status;
	}

	/**
	 * Check if WordPress has REST API support or not
	 * 
	 * @since 5.4.18
	 * 
	 * @return bool
	 */
	public static function is_rest_api_supported() {
		global $wp_version;

		if ( version_compare( $wp_version, '4.4.0', '<' ) ) {
			return false;
		}

		return true;
	}

	

	public static function is_positive_number( $number ) {
		return is_numeric( $number ) && $number > 0;
	}

	/**
	 * Get the date when plugin was activated for first time
	 * 
	 * @return string $installation_date
	 * 
	 * @since 5.6.0
	 */
	public static function get_plugin_installation_date() {
		$installation_date = get_option( 'ig_es_installed_on' );
		return $installation_date;
	}

	public static function get_gmt_timestamp_from_day_and_time( $day_and_time ) {
		try {
			$date                   = new DateTime( $day_and_time );
			$scheduled_datetime     = $date->format( 'Y-m-d h:i:s A' );
			$scheduled_datetime_gmt = get_gmt_from_date( $scheduled_datetime );

			return strtotime( $scheduled_datetime_gmt );
		} catch ( Exception $e ) {
			return null;
		}
	}

	public static function replace_posts_blocks( $content, $post_ids ) {
		foreach ( $post_ids as $single_block_post_ids ) {
			$content = self::replace_single_posts_block( $content, $single_block_post_ids );
		}
		return $content;
	}
	
	public static function replace_single_posts_block( $content, $single_block_post_ids ) {
		$posts_block_inner_content = self::get_in_between_content( $content, '{{campaign.posts}}', '{{/campaign.posts}}' );
		$search_start = '{{campaign.posts}}';
		$search_end   = '{{/campaign.posts}}';
		$start_pos    = strpos($content, $search_start);
		if ( false !== $start_pos ) {
			// Find the corresponding closing tag
			$end_pos = strpos($content, $search_end, $start_pos + strlen($search_start));
			$replace                   = '';	
			if ( false !== $end_pos ) {
				if ( ! empty( $single_block_post_ids ) ) {
					foreach ( $single_block_post_ids as $post_id ) {
						$replace .= ES_Handle_Post_Notification::prepare_body( $posts_block_inner_content, $post_id, 0 );
					}
				}
				// Replace the entire block with the replacement content
				$content = substr_replace($content, $replace, $start_pos, $end_pos + strlen($search_end) - $start_pos);
			}
		}
		$content = ES_Handle_Post_Notification::prepare_body( $content, $post_id, 0 );
		return $content;
	}	
	
	public static function contains_posts_block( $content ) {
		return strpos( $content, '{{campaign.posts}}' ) !== false;
	}

	public static function replace_post_digest_keyword_with_posts_keyword( $content ) {
		$find = array( '{{post.digest}}', '{{/post.digest}}' );
		$replace = array( '{{campaign.posts}}', '{{/campaign.posts}}' );
		$content = str_replace( $find, $replace , $content );

		$find = array( '{{POSTDIGEST}}', '{{/POSTDIGEST}}' );
		$replace = array( '{{campaign.posts}}', '{{/campaign.posts}}' );
		$content = str_replace( $find, $replace , $content );
		return $content;
	}

	public static function wrap_post_keywords_between_campaign_posts_keyword( $text ) {
		$pattern = '/(<[^>]*>\s*\{{(DATE|[Pp][^\{\}]*)}}\s*<\/[^>]*>)|(<[^>]*?({{(DATE|[Pp][^\{\}]*)}})[^>]*>.*?<\/\w+>)|(<[^>]*{{(DATE|[Pp][^\{\}]*)}}[^>]*>)|(\{\{[PpD][^\}]*\}\})/';
		preg_match_all($pattern, $text, $matches);

		if (!empty($matches[0])) {
			if ( count( $matches[0] ) > 1 ) {
				$firstKeyword = $matches[0][0];
				$lastKeyword = $matches[0][count($matches[0]) - 1];
		
				$wrappedText = preg_replace('/(' . preg_quote($firstKeyword, '/') . '.*?' . preg_quote($lastKeyword, '/') . ')/s', '{{campaign.posts}}$1{{/campaign.posts}}', $text, 1);

			} else {
				$keyword = $matches[0][0];
				$wrappedText = str_replace( $keyword, '{{campaign.posts}}' . $keyword . '{{/campaign.posts}}', $text );
			}

			return $wrappedText;
		} else {
			return $text;
		}
	}

	public static function get_campaign_default_data( $campaign_type ) {
		$default_content = apply_filters( 'ig_es_' . $campaign_type . '_default_content', '' );
		$default_subject = apply_filters( 'ig_es_' . $campaign_type . '_default_subject', '' );
		return array(
			'subject' => $default_subject,
			'content' => $default_content
		);
	}

	public static function get_tags() {
		$subscriber_tags = self::get_subscriber_tags();
		$site_tags 		 = self::get_site_tags();
		$campaign_tags   = self::get_campaign_tags();
		return array(
			'subscriber_tags' => $subscriber_tags,
			'site_tags' => $site_tags,
			'campaign_tags' => $campaign_tags,
		);
	}

	public static function get_campaign_tags() {

		$post_notification_tags = self::get_post_notification_tags();

		$campaign_tags = array(
			'post_notification' => $post_notification_tags,
		);

		return apply_filters( 'ig_es_campaign_tags', $campaign_tags );
	}

	public static function get_post_notification_tags() {
		$post_notification_tags = array(
			'{{post.date}}',
			'{{post.title}}',
			'{{post.image}}',
			'{{post.excerpt}}',
			'{{post.description}}',
			'{{post.author}}',
			'{{post.link}}',
			'{{post.link_with_title}}',
			'{{post.link_only}}',
			'{{post.full}}',
			'{{post.cats}}',
			'{{post.more_tag}}',
			'{{post.image_url}}'
		);
		return apply_filters( 'ig_es_post_notification_tags', $post_notification_tags );
	}

	public static function get_subscriber_tags() {
		$subscriber_tags = array(
			'{{subscriber.name}}',
			'{{subscriber.first_name}}',
			'{{subscriber.last_name}}',
			'{{subscriber.email}}',
		);
		return apply_filters( 'ig_es_subscriber_tags', $subscriber_tags );
	}

	public static function get_site_tags() {
		$site_tags = array(
			'{{site.total_contacts}}',
			'{{site.url}}',
			'{{site.name}}',
		);

		return apply_filters( 'ig_es_site_tags', $site_tags );
	}

	public static function is_optimizer_enabled() {
		$send_time_optimizer_enabled = get_option('ig_es_send_time_optimizer_enabled', 'no' );
		return 'yes' === $send_time_optimizer_enabled;
	}
	public static function get_optimization_method() {
		$optimization_method = get_option('ig_es_send_time_optimization_method', 'subscriber_timezone' );
		return $optimization_method;
	}

	public static function get_optimization_details() {
		if (self::is_optimizer_enabled()) {
			$optimization_option = self::get_optimization_method();
			return $optimization_option;
		}
		return '';
	}

	public static function process_subscriber_fallbacks($content, $merge_tags) {

		if ( empty( $content ) ) {
			return $merge_tags;
		}
	
		if ( ! is_array( $merge_tags ) || empty( $merge_tags ) ) {
			$merge_tags = [];
		}
	
		$decoded_content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
		$normalized_content = str_replace(['‘', '’', '“', '”'], ["'", "'", '"', '"'], $decoded_content);
	
		preg_match_all('/{{([a-zA-Z0-9_.]+)(?: \| fallback=(\'|"|&quot;)(.+?)\2)?}}/', $normalized_content, $fallback_matches, PREG_SET_ORDER);
	
		foreach ($fallback_matches as $match) {
			$key = $match[1];
			$provided_fallback = isset($match[3]) ? $match[3] : null;
	
			if ('subscriber.name' === $key || 'NAME' === $key) {
				$merge_tag_key = 'name';
	
			} elseif ('subscriber.first_name' === $key || 'FIRSTNAME' === $key) {
				$merge_tag_key = 'first_name';
	
			} elseif ('subscriber.last_name' === $key || 'LASTNAME' === $key) {
				$merge_tag_key = 'last_name';
	
			} elseif ('subscriber.email' === $key || 'EMAIL' === $key) {
				$merge_tag_key = 'email';
	
			} else {
				
				if (strpos($key, 'subscriber.') === 0) {
					$merge_tag_key = str_replace('subscriber.', '', $key);
				} else {
					$merge_tag_key = $key;
				}
			}
	
			if ( ! isset($merge_tags[$merge_tag_key] ) || empty( $merge_tags[$merge_tag_key] ) ) {
				$merge_tags[$merge_tag_key] = $provided_fallback ?? null;
			}
		}
	
		return $merge_tags;
	}
	
	
	
	public static function strip_js_code( $html ) {

		$html = preg_replace( '/<\/?(script)[^>]*>/i', '', $html );
		
		// Remove inline JS event handlers
		$html = preg_replace('/\s*(on[a-z]+|javascript|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html );
		return $html;
	}

}
