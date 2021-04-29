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
	 * Process template body
	 *
	 * @param $content
	 * @param int $tmpl_id
	 * @param int $campaign_id
	 *
	 * @return mixed|string
	 *
	 * @since 4.0.0
	 */
	public static function es_process_template_body( $content, $tmpl_id = 0, $campaign_id = 0 ) {
		$content = convert_smilies( wptexturize( $content ) );

		$content = self::handle_oembed_content( $content );

		$content             = wpautop( $content );
		$content             = do_shortcode( shortcode_unautop( $content ) );
		$data['content']     = $content;
		$data['tmpl_id']     = $tmpl_id;
		$data['campaign_id'] = $campaign_id;
		$data                = apply_filters( 'es_after_process_template_body', $data );
		$content             = $data['content'];
		//total contacts
		$total_contacts = ES()->contacts_db->count_active_contacts_by_list_id();
		$content        = str_replace( '{{TOTAL-CONTACTS}}', $total_contacts, $content );
		//blog title
		$blog_name = get_option( 'blogname' );
		$content   = str_replace( '{{SITENAME}}', $blog_name, $content );
		// site url
		$site_url = home_url( '/' );
		$content  = str_replace( '{{SITEURL}}', $site_url, $content );

		/*TODO: Enable it once Pre header issue fix
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
			$content = $GLOBALS['wp_embed']->autoembed( $content );
			remove_filter( 'embed_oembed_html', array( 'ES_Common', 'handle_link_in_email_content' ), 10, 4 );
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
	 * @param array $attr Shortcode attribute.
	 * @param int $post_ID Current post id.
	 *
	 * @return string $html HTML for current URL.
	 *
	 * @since 4.4.9
	 */
	public static function handle_link_in_email_content( $html, $url, $attr, $post_ID ) {

		$post_link = get_permalink( $post_ID );
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
					$thumbnail_width  = ! empty( $oembed_response->width ) ? $oembed_response->width . 'px' : 'auto';
					$thumbnail_height = ! empty( $oembed_response->height ) ? $oembed_response->height . 'px' : 'auto';
					?>
					<table style="margin-bottom: 1em;">
						<tbody>
						<tr>
							<td style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>');height:<?php echo esc_attr( $thumbnail_height ); ?>;width:<?php echo esc_attr( $thumbnail_width ); ?>;background-size: 100% 100%;background-repeat: no-repeat;text-align:center;">
								<a href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $title ); ?>" target="_blank">
									<img src="<?php echo esc_url( $play_icon_url ); ?>" style="height: 75px; margin: auto;">
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
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public static function get_statuses_key_name_map( $reverse = false ) {

		$statuses = array(
			'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
			'unconfirmed'  => __( 'Unconfirmed', 'email-subscribers' ),
			'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' )
		);

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
	public static function prepare_statuses_dropdown_options( $selected = '', $default_label = '' ) {

		if ( empty( $default_label ) ) {
			$default_label = __( 'Select Status', 'email-subscribers' );
		}

		$default_status[0] = $default_label;

		$statuses = self::get_statuses_key_name_map();
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
	 * Prepare template dropdown options
	 *
	 * @param string $type
	 * @param string $selected
	 *
	 * @return string
	 */
	public static function prepare_templates_dropdown_options( $type = 'newsletter', $selected = '' ) {

		$default_template_option = new stdClass();

		$default_template_option->ID         = '';
		$default_template_option->post_title = __( 'Select Template', 'email-subscribers' );

		$default_template_option = array( $default_template_option );

		$templates   = self::get_templates( $type );
		$allowedtags = ig_es_allowed_html_tags_in_esc();
		if ( is_array( $templates ) ) {
			$templates = array_merge( $default_template_option, $templates );
		}


		$dropdown = '';
		foreach ( $templates as $key => $template ) {
			$es_templ_thumbnail = ( ! empty( $template->ID ) ) ? get_the_post_thumbnail_url( $template->ID, array( '200', '200' ) ) : ES_PLUGIN_URL . 'images/envelope.png';
			$dropdown           .= "<option data-img-url='" . $es_templ_thumbnail . "' value='" . $template->ID . "'";

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
			'0' => __( 'Inactive', 'email-subscribers' )
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
	public static function get_templates( $type = 'newsletter' ) {

		$es_args = array(
			'posts_per_page'   => - 1,
			'post_type'        => 'es_template',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post_status'      => 'publish',
			'suppress_filters' => true,
			'meta_query'       => array(
				array(
					'key'     => 'es_template_type',
					'value'   => $type,
					'compare' => '='
				)
			)
		);

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
		$categories = get_terms( array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
		) );
		if ( ! is_array( $category_names ) ) {
			$category_names = array();
		}
		$checked_selected = ! array_intersect( array( 'All', 'None' ), $category_names ) ? "checked='checked'" : '';
		$category_html    = '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;" ><span class="block ml-6 pr-4 text-sm font-normal text-gray-600 pb-1"><input class="es-note-category-parent form-radio text-indigo-600" type="radio" ' . esc_attr( $checked_selected ) . ' value="selected_cat"  name="es_note_cat_parent">' . __( 'Select Categories',
				'email-subscribers' ) . '</td></tr>';
		foreach ( $categories as $category ) {

			if ( in_array( $category->term_id, $category_names ) ) {
				$checked = "checked='checked'";
			} else {
				$checked = '';
			}

			$category_html .= '<tr class="es-note-child-category"><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block ml-6 pr-4 text-sm font-normal text-gray-600 pb-1"><input type="checkbox" class="form-checkbox" ' . esc_attr( $checked ) . ' value="' . esc_attr( $category->term_id ) . '" id="es_note_cat[]" name="es_note_cat[]">' . esc_html( $category->name ) . '</td></tr>';
		}
		$checked_all = in_array( 'All', $category_names ) ? "checked='checked'" : '';
		$all_html    = '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block ml-6 pr-4 text-sm font-normal text-gray-600 pb-1"><input type="radio" class="form-radio text-indigo-600 es-note-category-parent"  ' . esc_attr( $checked_all ) . ' value="{a}All{a}"  name="es_note_cat_parent">' . __( 'All Categories (Also include all categories which will create later)',
				'email-subscribers' ) . '</td></tr>';

		$checked_none = in_array( 'None', $category_names, true ) ? "checked='checked'" : '';
		$none_html = '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block ml-6 pr-4 text-sm font-normal text-gray-600 pb-1"><input type="radio" class="form-radio text-indigo-600 es-note-category-parent"  ' . esc_attr( $checked_none ) . ' value="{a}None{a}"  name="es_note_cat_parent">' . __( 'None (Don\'t include post from any category)',
		'email-subscribers' ) . '</td></tr>';

		return $none_html . $all_html . $category_html;
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
		$args       = array( 'public' => true, 'exclude_from_search' => false, '_builtin' => false );
		$output     = 'names';
		$operator   = 'and';
		$post_types = get_post_types( $args, $output, $operator );
		if ( ! empty( $post_types ) ) {
			$custom_post_type_html = '';
			foreach ( $post_types as $post_type ) {
				$post_type_search = '{T}' . $post_type . '{T}';
				if ( is_array( $custom_post_types ) && in_array( $post_type_search, $custom_post_types ) ) {
					$checked = "checked='checked'";
				} else {
					$checked = '';
				}
				$custom_post_type_html .= '<tr><td style="padding-top:4px;padding-bottom:4px;padding-right:10px;"><span class="block ml-12 pr-4 text-sm font-medium text-gray-600 pb-2"><input type="checkbox" ' . esc_attr( $checked ) . ' value="{T}' . esc_html( $post_type ) . '{T}" id="es_note_cat[]" class="es_custom_post_type form-checkbox" name="es_note_cat[]">' . esc_html( $post_type ) . '</td></tr>';
			}

		} else {
			$custom_post_type_html = '<tr><span class="block ml-12 pr-4 text-sm font-normal text-gray-600 pb-2">' . __( 'No Custom Post Types Available', 'email-subscribers' ) . '</tr>';
		}

		return $custom_post_type_html;
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
			'double_opt_in' => __( 'Double Opt-In', 'email-subscribers' )
		);

		return $types;
	}

	/**
	 * Get Image sizes
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public static function get_image_sizes() {
		$sizes = array(
			'full'      => __( 'Full Size', 'email-subscribers' ),
			'medium'    => __( 'Medium Size', 'email-subscribers' ),
			'thumbnail' => __( 'Thumbnail', 'email-subscribers' )
		);

		return $sizes;
	}

	/**
	 * Get IG Option
	 *
	 * @param $option
	 * @param null $default
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

	/**
	 * Convert categories string to array
	 *
	 * @param string $categories_str
	 * @param bool $keep_ids
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
			'last_name'  => ''
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
							'af_id'         => 0
						);

						$prepared_form_data = ES_Forms_Table::prepare_form_data( $data );

						$inserted_form_id = ES()->forms_db->add_form( $prepared_form_data );

						$data_to_set = array(
							'title'   => $title,
							'form_id' => $inserted_form_id
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
							'af_id'         => 0
						);

						$prepared_form_data = ES_Forms_Table::prepare_form_data( $data );

						$inserted_form_id = ES()->forms_db->add_form( $prepared_form_data );

						$data_to_set = array(
							'title'   => $title,
							'form_id' => $inserted_form_id
						);

						$es_4_widget_option_data[ $key ] = $data_to_set;
					}
				}

				update_option( $latest_widget_option, $es_4_widget_option_data );
			}
		}

		//Update sidebars_widgets options.
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
	 * @param bool $is_dismissible
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

			<a href="<?php echo esc_url( $url ); ?>" class="ig-es-title-button ml-2
								<?php
								if ( $is_imp ) {
									echo esc_attr( ' ig-es-imp-button' );
								}
								?>
			"><?php echo esc_html( $action_label ); ?>
				<?php if ( $show_indicator ) { ?>
					<span class="ig-es-indicator <?php echo esc_attr( $indicator_class ); ?>">
								<?php echo esc_html( $indicator_label ); ?>
							</span>

				<?php } ?>
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
			'show_icon'  => true
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
			'show_once'     => false
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
			'ig_es_unsubscribe_link',
			'ig_es_unsubscribe_link_content',
			'ig_es_unsubscribe_page',
			'ig_es_unsubscribe_success_message',
			'ig_es_update_processed_tasks',
			'ig_es_update_tasks_to_process',
			'ig_es_welcome_email_content',
			'ig_es_welcome_email_subject',
			'ig_es_email_sent_data'
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
			$restricted_settings = self::get_restricted_settings();
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
	 * Get plugin meta info
	 *
	 * @return array
	 *
	 * @since 4.1.0
	 */
	public static function get_ig_es_meta_info() {

		$total_contacts           = ES()->contacts_db->count();
		$total_lists              = ES()->lists_db->count_lists();
		$total_forms              = ES()->forms_db->count_forms();
		$total_newsletters        = ES()->campaigns_db->get_total_newsletters();
		$total_post_notifications = ES()->campaigns_db->get_total_post_notifications();
		$total_sequences          = ES()->campaigns_db->get_total_sequences();

		return array(
			'version'                  => ES_PLUGIN_VERSION,
			'is_premium'               => ES()->is_premium() ? 'yes' : 'no',
			'plan'                     => ES()->get_plan(),
			'is_trial'                 => ES()->is_trial() ? 'yes' : 'no',
			'is_trial_expired'         => ES()->is_trial_expired() ? 'yes' : 'no',
			'trial_start_at'           => ES()->get_trial_start_date(),
			'total_contacts'           => $total_contacts,
			'total_lists'              => $total_lists,
			'total_forms'              => $total_forms,
			'total_newsletters'        => $total_newsletters,
			'total_post_notifications'  => $total_post_notifications,
			'total_sequences'          => $total_sequences,
			'settings'                 => self::get_all_settings()
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
	 * @since 4.1.15
	 */
	public static function update_total_email_sent_count() {

		$current_date = ig_es_get_current_date();
		$current_hour = ig_es_get_current_hour();

		$email_sent_data_option = 'email_sent_data';

		//Get total emails sent in this hour
		$email_sent_data = self::get_ig_option( $email_sent_data_option, array() );

		$total_emails_sent = 0;
		$data              = array();
		if ( is_array( $email_sent_data ) && ! empty( $email_sent_data[ $current_date ] ) && ! empty( $email_sent_data[ $current_date ][ $current_hour ] ) ) {
			$total_emails_sent = $email_sent_data[ $current_date ][ $current_hour ];
		}

		$total_emails_sent ++;
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
				'forms',
				'campaigns',
				'sequences',
				'settings',
				'ig_redirect',
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
	public static function get_useful_articles() {

		$articles = array(
			array( 'title' => __( '8 Tips To Improve Email Open Rates', 'email-subscribers' ), 'link' => 'https://www.icegram.com/2bx5' ),
			array( 'title' => __( 'Prevent Your Email From Landing In Spam', 'email-subscribers' ), 'link' => 'https://www.icegram.com/2bx6' ),
			array( 'title' => __( '<b>Email Subscribers Secret Club</b>', 'email-subscribers' ), 'link' => 'https://www.facebook.com/groups/2298909487017349/', 'label' => __( 'Join Now', 'email-subscribers' ), 'label_class' => 'bg-green-100 text-green-800' ),
			array( 'title' => __( 'Best Way To Keep Customers Engaged', 'email-subscribers' ), 'link' => 'https://www.icegram.com/ymrn' ),
			array( 'title' => __( 'Access Control', 'email-subscribers' ), 'link' => 'https://www.icegram.com/81z9' ),
			array( 'title' => __( 'Prevent Spam Subscription Using Captcha', 'email-subscribers' ), 'link' => 'https://www.icegram.com/3jy4' ),
			array( 'title' => __( 'Email Subscribers PRO', 'email-subscribers' ), 'link' => 'https://www.icegram.com/er6r', 'label' => __( 'Lifetime', 'email-subscribers' ), 'label_class' => 'bg-green-100 text-green-800' )
		);

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
				$string = preg_replace( '%(?:
						\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
					| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
					| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
				)%xs', '', $string );
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
	 * @param bool $reverse
	 *
	 * @return array
	 *
	 * @since 4.4.8
	 */
	public static function get_campaign_statuses_key_name_map( $reverse = false ) {

		$statuses = array(
			'0' => __( 'Draft', 'email-subscribers' ),
			'3' => __( 'Sending', 'email-subscribers' ),
			'2' => __( 'Scheduled', 'email-subscribers' ),
			'5' => __( 'Sent', 'email-subscribers' ),
			'1' => __( 'Active', 'email-subscribers' ),
		);

		if ( $reverse ) {
			$statuses = array_flip( $statuses );
		}

		return $statuses;
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
			'count'           => $shown_count + 1
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
			$convert_date_format = get_option( 'date_format' );
			$convert_time_format = get_option( 'time_format' );
			$format = $convert_date_format . ' ' . $convert_time_format;
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
							<path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'/>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm leading-5 font-medium text-blue-800 hover:underline">
							<?php
							/* translators: 1: Anchor opening tag with href attribute 2: Target attribute  3: Anchor closing tag */
							echo sprintf( esc_html__( '%1$s' . esc_url( $upsell_info['pricing_url'] ) . '%2$s' . esc_html( $upsell_info['upgrade_title'] ) . '%3$s', 'email-subscribers' ), '<a href="', '" target="_blank">', '</a>' );
							?>
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
						echo wp_kses_post( 'Upgrade now & get <b> 10% discount!</b> <br/><br/>Use coupon code:' );
							?>

						<span class="ml-2 px-1.5 py-1 font-medium bg-yellow-100 rounded-md border-2 border-dotted border-indigo-300 select-all"><?php esc_html_e( 'PREMIUM10', 'email-subscribers' ); ?> </span>
					</p>
					<?php
						}
						if ( $upsell_info['cta_html'] ) {
							?>
						<div class="pt-6 text-center -ml-6 pb-2">
							<a href="<?php echo esc_url( $upsell_info['pricing_url'] ); ?>" target="_blank" class="rounded-md border border-transparent px-3 py-2 bg-white text-sm leading-7 font-medium text-white bg-indigo-600 hover:text-white hover:bg-indigo-500 transition ease-in-out duration-150 mt-2">
												<?php 
							esc_html_e( 'Upgrade',
									'email-subscribers' ); 
												?>
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
					<svg class="absolute mt-2 text-black text-opacity-100 h-2.5 left-0" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
						<polygon class="fill-current" points="0,0 127.5,127.5 255,0"/>
					</svg>
				</span>
			</div>';
		}
		return $tooltip_html;
	}

}

