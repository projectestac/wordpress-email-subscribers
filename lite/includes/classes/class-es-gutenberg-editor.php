<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The gutenberg email editor specific functionality of the plugin.
 *
 * @credit - Inspired by the Newspack Newsletters plugin implementation by Automattic [https://wordpress.org/plugins/newspack-newsletters/]
 * @package    Email_Subscribers
 */


if ( ! class_exists( 'ES_Gutenberg_Editor' ) ) {
	
	/**
	 * The onboarding-specific functionality of the plugin.
	 *
	 */
	class ES_Gutenberg_Editor {


	/**
	 * The color palette to be used.
	 *
	 * @var Object
	 */
		public static $color_palette = null;

	/**
	 * Class instance.
	 *
	 * @var ES_Gutenberg_Editor instance
	 */
		protected static $instance = null;

		/**
		 * The header font.
		 *
		 * @var String
		 */
		protected static $font_header = 'Arial';

		/**
		 * The body font.
		 *
		 * @var String
		 */
		protected static $font_body = 'Georgia';

		const CAMPAIGN_COLOR_PALETTE = 'ig_es_campaign_color_palette';

		const API_NAMESPACE = 'icegram-express/v1';

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 5.7.56
		 */
		public function __construct() {
			add_action( 'enqueue_block_editor_assets', array($this,'enqueue_assets'), 99 );
			add_action( 'enqueue_block_editor_assets', array($this,'dequeue_theme_assets'), 99 );
			add_action( 'enqueue_block_assets', array($this,'dequeue_theme_assets'), 99 );
			add_action( 'ig_es_render_gutenberg_editor', array( $this, 'render_gutenberg_editor' ), 10);
			add_filter( 'allowed_block_types_all', array( $this, 'allowed_block_types' ), 10, 2 );
			add_action( 'wp_ajax_ig_es_convert_to_mjml', array( $this, 'ig_es_convert_to_mjml_ajax' ), 10);
			add_action( 'wp_ajax_nopriv_ig_es_convert_to_mjml', array( $this, 'ig_es_convert_to_mjml_ajax' ), 10);
			add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );
			add_action( 'admin_init', array( $this, 'show_icegram_mailer_promotion_notice' ) );
		}
	
		/**
		 * Get class instance.
		 * 
		 * @since 5.7.56
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	/**
	 * Convert a Gutenberg content to MJML markup.
	 *
	 * @param Gutenberg content
	 * @return string MJML markup.
	 */
		public static function ig_es_convert_to_mjml_ajax() {
			 // Verify nonce first
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field(   wp_unslash( $_POST['_wpnonce'] ) ), 'ig_es_convert_to_mjml_nonce' ) ) {
				wp_send_json_error( [ 'message' => __( 'Security check failed.', 'email-subscribers' ) ] );
			}


			if (!isset($_POST['content']) || empty($_POST['content'])) {
				wp_send_json_error(['message' => __('Please enter content in the Gutenberg editor.', 'email-subscribers')]);
			}
			$gutenberg_content = wp_kses_post( stripslashes( $_POST['content'] ) ); //phpcs:ignore

		// Convert Gutenberg to MJML
		$mjml_content = self::get_mjml_components($gutenberg_content);
			if (empty($mjml_content) || strpos($mjml_content, '<mjml>') === false) {
				 wp_send_json_error(['message' => __('Failed to convert to MJML.', 'email-subscribers')]);
			}
	
		wp_send_json_success(['html' => $mjml_content]);
		}

		private static function get_mjml_components( $gutenberg_content = '' ) {

		$mjml_content = self::gutenberg_blocks_to_mjml($gutenberg_content);
   
			if ( empty($mjml_content) ) {
				return;
			}

		$mjml_html = '
    <mjml>
        <mj-head>
			<mj-attributes>
				<mj-all font-family="georgia, serif" />
			</mj-attributes>
            <mj-style>
                /* Paragraph */
                p { margin-top: 0 !important; margin-bottom: 0 !important; }
                /* Link */
                a { color: inherit !important; text-decoration: underline; }
                a:active, a:focus, a:hover { text-decoration: none; }
                a:focus { outline: thin dotted #000; }
                /* Button */
                .is-style-outline a { background: #fff; border: 2px solid; display: block; }
                /* Heading */
                h1 { font-size: 2.44em; line-height: 1.4; }
                h2 { font-size: 1.95em; line-height: 1.4; }
                h3 { font-size: 1.56em; line-height: 1.4; }
                h4 { font-size: 1.25em; line-height: 1.5; }
                h5 { font-size: 1em; line-height: 1.8; }
                h6 { font-size: 0.8em; line-height: 1.8; }
                h1, h2, h3, h4, h5, h6 { margin-top: 0; margin-bottom: 0; }
                /* List */
                ul, ol { margin-bottom: 0; margin-top: 0; padding-left: 1.3em; }
                /* Quote */
                .wp-block-quote { border-left: 4px solid #000; margin: 0; padding-left: 20px; }
                .wp-block-quote cite { color: #767676; }
                .wp-block-quote p { padding-bottom: 12px; }
                .wp-block-quote.is-style-large { border-left: 0; padding-left: 0; }
                .wp-block-quote.is-style-large p { font-size: 24px; font-style: italic; line-height: 1.6; }
                /* Image */
                @media all and (max-width: 590px) { img { height: auto !important; } }
                /* Social links */
                .social-element img { border-radius: 0 !important; }
                /* Has Background */
                .mj-column-has-width .has-background, .mj-column-per-50 .has-background { padding: 12px; }
            </mj-style>
        </mj-head>
        <mj-body>'
		. $mjml_content . 
		'</mj-body>
    </mjml>';
		return $mjml_html;
		}

		public static function get_latest_gutenberg_draftpost() {
			$args = [
				'post_type'   => 'ig_es_campaign',
				'post_status' => 'draft',
				'numberposts' => 1,
				'orderby'     => 'modified',
			];
			$posts = get_posts($args);
			return isset( $posts[0] ) ? $posts[0] : null;

		}

		public function enqueue_assets() {
	   
			if ( ! $this->is_editor_page() ) {
				return;
			}

			wp_enqueue_style( 'ig-es-gutenberg-editor-css', ES_PLUGIN_URL . 'lite/admin/css/gutenberg-editor.css', array(), ES_PLUGIN_VERSION, 'all' );
			
		}

		public function dequeue_theme_assets() {
	   
			if ( ! $this->is_editor_page() ) {
				return;
			}

			$parent_theme_slug = get_template();      // Parent theme directory (e.g., "twentytwentyone")
			$active_theme_slug = get_stylesheet();    // Child theme directory if active (e.g., "child-theme")

			// Remove editor styles added via add_editor_style()
			wp_dequeue_style($parent_theme_slug . '-editor-style');  // Parent theme's editor CSS
			wp_dequeue_style($active_theme_slug . '-editor-style');  // Child theme's editor CSS (if exists)

			remove_editor_styles();
		
			// Remove any other theme-specific styles enqueued for the editor
			global $wp_styles;
			foreach ($wp_styles->queue as $handle) {
				$src = $wp_styles->registered[$handle]->src ?? '';
				// Check if the style is from the parent or child theme directory
				if (strpos($src, "/themes/{$parent_theme_slug}/") !== false || strpos($src, "/themes/{$active_theme_slug}/") !== false) {
					wp_dequeue_style($handle);
				}
			}
			
		}

		public function is_editor_page() {
			global $pagenow, $typenow;
			
			$post_type = '';
			if ( 'post-new.php' === $pagenow || 'edit.php' === $pagenow ) {
				$post_type = $typenow;
			} elseif ( 'post.php' === $pagenow ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$post_type = ! empty( $_GET['post'] ) ? get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) : '';
			}

			$is_campaign_cpt =  'ig_es_campaign' ===  $post_type;
			return $is_campaign_cpt;
		}

		public function render_gutenberg_editor() {

			$latest = self::get_latest_gutenberg_draftpost();
			if ($latest) {
				wp_redirect(admin_url('post.php?post=' . $latest->ID . '&action=edit'));
				exit;
			} else {
				wp_redirect(admin_url('post-new.php?post_type=ig_es_campaign'));
				exit;
			}
  
		}

		

		/**
		 * Convert isolated Gutenberg blocks to MJML.
		 *
		 * @param array $blocks Isolated Gutenberg blocks.
		 * @return string MJML markup.
		 */
		public static function gutenberg_blocks_to_mjml( $gutenberg_content) {
			$body   = '';
			$valid_blocks = array_filter(
			parse_blocks( $gutenberg_content ),
			function ( $block ) {
				return null !== $block['blockName'];
			}
			);
			foreach ( $valid_blocks as $block ) {
				$block_content = '';
				$attrs ='';
				$attrs_value ='';
				if ( 'core/group' === $block['blockName'] ) {
					$default_attrs = [];
					$attrs         = ! empty( $block['attrs'] ) && is_array( $block['attrs'] ) ? self::process_attributes( $block['attrs'] ) : [];

					if ( isset( $attrs['color'] ) ) {
						$default_attrs['color'] = $attrs['color'];
					}

					$mjml_markup = '<mj-wrapper ' . self::array_to_attributes( $attrs ) . '>';
					foreach ( $block['innerBlocks'] as $block ) {
						$inner_block_content = self::render_mjml_component( $block, false, true, $default_attrs );
						$mjml_markup        .= $inner_block_content;
					}
					$block_content = $mjml_markup . '</mj-wrapper>';
				} elseif ('core/quote' === $block['blockName'] && !empty($block['innerBlocks'])) {
					$inner_html = '';
					$attrs      = !empty($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : []; 

					$text_attrs = array_merge(
						array(
							'padding'     => '0',
							'line-height' => '1.8',
							'font-size'   => '16px'
						),
						$attrs
					);

					// Only mj-text has to use container-background-color attr for background color.
					if (isset($text_attrs['background-color'])) {
						$text_attrs['container-background-color'] = $text_attrs['background-color'];
						unset($text_attrs['background-color']);
					}

					foreach ($block['innerBlocks'] as $block) {
						$inner_html .= self::render_mjml_component($block);
					}

					$block_content = str_replace(
						'<mj-text', 
						'<mj-text font-style="italic" color="#333" font-size="16px" line-height="1.8" padding="10px 20px" border-left="4px solid #ccc"',
						$inner_html
					);
				} else {
					$block_content = self::render_mjml_component( $block );
				}

				$body .= $block_content;
			}

			return $body;
		}

	/**
		 * Filter to short-circuit the markup generation for a block.
		 *
		 * @param string|null $markup The markup to return. If null, the default markup will be generated.
		 * @param WP_Block    $block The block.
		 * @param bool        $is_in_column Whether the component is a child of a column component.
		 * @param bool        $is_in_group Whether the component is a child of a group component.
		 * @param array       $default_attrs Default attributes for the component.
		 * @param bool        $is_in_list_or_quote Whether the component is a child of a list or quote block.
	 *
		 * @return string|null The markup to return. If null, the default markup will be generated.
		 */
		public static function render_mjml_component( $block, $is_in_column = false, $is_in_group = false, $default_attrs = [], $is_in_list_or_quote = false ) {

			$block_name    = $block['blockName'];
			$attrs         = isset($block['attrs']) ?$block['attrs'] : '';
			$inner_blocks  = $block['innerBlocks'];
			$inner_html    = $block['innerHTML'];
			$inner_content = isset( $block['innerContent'] ) ? $block['innerContent'] : [ $inner_html ];

			if ( ! isset( $attrs['innerBlocksToInsert'] ) && self::is_empty_block( $block ) ) {
				return '';
			}

			// Verify if block is configured to be web-only.
			if ( isset( $attrs['newsletterVisibility'] ) && 'web' === $attrs['newsletterVisibility'] ) {
				return '';
			}

			$block_mjml_markup = '';
			if ( !empty( $attrs ) ) {
			$attrs             = self::process_attributes( array_merge( $default_attrs, $attrs ) );
			}

			// Default attributes for the section which will envelop the mj-column.
			$section_attrs = array_merge(
			is_array($attrs) ? $attrs : [], 
			array(
				'padding' => '0',
			)
			);

			// Default attributes for the column which will envelop the component.
			$column_attrs = array_merge(
			array(
				'padding' => '12px',
			)
			);

			$font_family = 'core/heading' === $block_name ? self::$font_header : self::$font_body;

			if ( ! empty( $inner_html ) ) {
				// Replace <mark /> with <span />.
				$inner_html = preg_replace( '/<mark\s(.+?)>(.+?)<\/mark>/is', '<span $1>$2</span>', $inner_html );
			}
			switch ( $block_name ) {
				/**
				 * Text-based blocks.
				 */
				case 'core/paragraph':
				case 'core/heading':
				case 'core/site-title':
				case 'core/site-tagline':
					$text_attrs = array_merge(
					array(
						'padding'     => '0',
						'line-height' => '1.5',
						'font-size'   => '16px',
						'font-family' => $font_family
					),
					is_array($attrs) ? $attrs : [] 
					);

					if ( 'core/site-tagline' === $block_name ) {
						$inner_html = get_bloginfo( 'description' );
					}

					if ( 'core/site-title' === $block_name ) {
						$inner_html = get_bloginfo( 'name' );
						$tag_name   = 'h1';
						if ( isset( $attrs['level'] ) ) {
							$tag_name = 0 === $attrs['level'] ? 'p' : 'h' . (int) $attrs['level'];
						}
						if ( ! ( isset( $attrs['isLink'] ) && ! $attrs['isLink'] ) ) {
							$link_attrs = array(
							'href="' . esc_url( get_bloginfo( 'url' ) ) . '"',
							);
							if ( isset( $attrs['linkTarget'] ) && '_blank' === $attrs['linkTarget'] ) {
								$link_attrs[] = 'target="_blank"';
							}
							$inner_html = sprintf( '<a %1$s>%2$s</a>', implode( ' ', $link_attrs ), esc_html( $inner_html ) );
						}
						$inner_html = sprintf( '<%1$s>%2$s</%1$s>', $tag_name, $inner_html );
					}

					// Initialize the MJML markup.
					$block_mjml_markup = '';

					// Only mj-text has to use container-background-color attr for background color.
					if ( isset( $text_attrs['background-color'] ) ) {
						$text_attrs['container-background-color'] = $text_attrs['background-color'];
						unset( $text_attrs['background-color'] );
					}

					// Handle link colors.
					if ( isset( $attrs['link'] ) ) {
						// Apply inline style to links.
						$inner_html = preg_replace(
						'/<a([^>]*?)>/i',
						'<a$1 style="color: ' . esc_attr( $attrs['link'] ) . ';">',
						$inner_html
						);
					}

					// Avoid wrapping markup in `mj-text` if the block is an inner block.
					$block_mjml_markup .= $is_in_list_or_quote ? $inner_html : '<mj-text ' . self::array_to_attributes( $text_attrs ) . '>' . $inner_html . '</mj-text>';
					break;

				/**
				 * Site logo block.
				 */
				case 'core/site-logo':
					$custom_logo_id = get_theme_mod( 'custom_logo' );
					$image          = wp_get_attachment_image_src( $custom_logo_id, 'full' );
					$markup         = '';
					if ( ! empty( $image ) ) {
						$img_attrs = array(
						'padding' => '0',
						'width'   => sprintf( '%spx', isset( $attrs['width'] ) ? $attrs['width'] : '125' ),
						'align'   => isset( $attrs['align'] ) ? $attrs['align'] : 'left',
						'src'     => $image[0],
						'href'    => isset( $attrs['isLink'] ) && ! $attrs['isLink'] ? '' : esc_url( home_url( '/' ) ),
						'target'  => isset( $attrs['linkTarget'] ) && '_blank' === $attrs['linkTarget'] ? '_blank' : '',
						'alt'     => self::get_image_alt( $custom_logo_id ),
						);
						$markup   .= '<mj-image ' . self::array_to_attributes( $img_attrs ) . ' />';
					}
					$block_mjml_markup = $markup;
					break;

				/**
					 * Image block.
					 */
				case 'core/image':
					// Parse block content.
					$dom = new DomDocument();
					@$dom->loadHTML( $inner_html ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					$xpath      = new DOMXpath( $dom );
					$img        = $xpath->query( '//img' )[0];
					$img_src = $img->getAttribute('src');
					$img_src = str_replace(['\"', "\'"], '', $img_src);
					$img_src = trim($img_src, "\"'");
					$img_src = filter_var($img_src, FILTER_SANITIZE_URL);
					$figcaption = $xpath->query( '//figcaption/text()' )[0];

					$img_attrs = array(
						'padding' => '0',
						'align'   => isset( $attrs['align'] ) ? $attrs['align'] : 'left',
						'src'     => $img_src,
					);

					if ( isset( $attrs['sizeSlug'] ) ) {
						if ( 'medium' === $attrs['sizeSlug'] ) {
							$img_attrs['width'] = '300px';
						}
						if ( 'thumbnail' === $attrs['sizeSlug'] ) {
							$img_attrs['width'] = '150px';
						}
					} elseif ( isset( $attrs['className'] ) ) {
						if ( 'size-medium' === $attrs['className'] ) {
							$img_attrs['width'] = '300px';
						}
						if ( 'size-thumbnail' === $attrs['className'] ) {
							$img_attrs['width'] = '150px';
						}
					}
					if ( isset( $attrs['width'] ) ) {
						$img_attrs['width'] = $attrs['width'] . 'px';
					}
					if ( isset( $attrs['height'] ) ) {
						$img_attrs['height'] = $attrs['height'] . 'px';
					}
					if ( isset( $attrs['href'] ) ) {
						$img_attrs['href'] = $attrs['href'];
					} else {
						$maybe_link = $img->parentNode;// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						if ( $maybe_link && 'a' === $maybe_link->nodeName && $maybe_link->getAttribute( 'href' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$img_attrs['href'] = trim( $maybe_link->getAttribute( 'href' ) );
						}
					}
					if ( isset( $attrs['className'] ) && strpos( $attrs['className'], 'is-style-rounded' ) !== false ) {
						$img_attrs['border-radius'] = '999px';
					}
					$markup = '<mj-image ' . self::array_to_attributes( $img_attrs ) . ' />';

					if ( $figcaption ) {
						$caption_attrs = array(
							'align'       => 'center',
							'color'       => '#555d66',
							'font-size'   => '13px',
						);
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$markup .= '<mj-text ' . self::array_to_attributes( $caption_attrs ) . '>' . $figcaption->wholeText . '</mj-text>';
					}
					$block_mjml_markup = $markup;
					break;

				/**
				 * Buttons block.
				 */
				case 'core/buttons':
					// Total percentage of button colunns with defined widths.
					$total_defined_width = array_reduce(
					$inner_blocks,
					function ( $acc, $block ) {
						if ( isset( $block['attrs']['width'] ) ) {
							$acc .= intval( $block['attrs']['width'] );
						}
						return $acc;
					},
					0
					);

					// Number of button columns with no defined width.
					$no_widths = count(
						array_filter(
						$inner_blocks,
						function ( $block ) {
							return empty( $block['attrs']['width'] );
						}
					)
					);

					$is_multi_row  = false;
					$default_width = ! $no_widths ? 25 : max( 25, floor( ( 100 - $total_defined_width ) / $no_widths ) );
					$alignment     = isset( $attrs['layout'], $attrs['layout']['justifyContent'] ) ? $attrs['layout']['justifyContent'] : 'left';
					$wrapper_attrs = [
						'padding'    => '0',
						'text-align' => $alignment,
					];

					// If the total width of the buttons is greater than 100%, reduce the default width.
					if ( ( $default_width * $no_widths ) + $total_defined_width > 100 ) {
						$default_width = 25;
						$is_multi_row  = true;
					}

					$remaining_width  = 100;
					$block_mjml_array = [];

					foreach ( $inner_blocks as $button_block ) {
						if ( empty( $button_block['innerHTML'] ) ) {
							break;
						}

						// Parse block content.
						$dom = new DomDocument();
						libxml_use_internal_errors( true );
						@$dom->loadHTML( $button_block['innerHTML'] );
						$xpath         = new DOMXpath( $dom );// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						$anchor        = $xpath->query( '//a' )[0];
						$attrs         = self::process_attributes( $button_block['attrs'] );
						$text          = $anchor->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$border_radius = isset( $attrs['borderRadius'] ) ? $attrs['borderRadius'] : '999px';
						$is_outlined   = isset( $attrs['className'] ) && 'is-style-outline' == $attrs['className'];

						if ( ! $anchor ) {
							break;
						}

						$default_button_attrs = array(
						'align'         => $alignment,
						'padding'       => '0',
						'inner-padding' => '12px 24px',
						'line-height'   => '1.5',
						'href'          => $anchor->getAttribute( 'href' ),
						'border-radius' => $border_radius,
						'font-size'     => ! empty( $attrs['font-size'] ) ? $attrs['font-size'] : '16px',
						'font-family'   => $font_family,
						'font-weight'   => 'bold',
						// Default color - will be replaced by get_colors if there are colors set.
						'color'         => $is_outlined ? '#32373c' : '#fff !important',
						);
						if ( $is_outlined ) {
							$default_button_attrs['background-color'] = 'transparent';
						} else {
							$default_button_attrs['background-color'] = '#32373c';
						}
						if ( ! empty( $attrs['background-color'] ) ) {
							$default_button_attrs['background-color'] = $attrs['background-color'];
						}
						if ( ! empty( $attrs['color'] ) ) {
							$default_button_attrs['color'] = $attrs['color'];
						}

						if ( ! empty( $attrs['padding'] ) ) {
							$default_button_attrs['inner-padding'] = $attrs['padding'];
						}
						$button_attrs = array_merge(
						$default_button_attrs,
						self::get_colors( $attrs )
						);

						if ( $is_outlined ) {
							$button_attrs['css-class'] = $attrs['className'];
						}

						$column_attrs['css-class'] = 'mj-column-has-width';
						$column_width              = $default_width;
						if ( ! empty( $attrs['width'] ) ) {
							$column_width                  = $attrs['width'];
							$default_button_attrs['width'] = '100%'; // Buttons with defined width should fill their column.
						}

						$column_attrs['width'] = $column_width . '%';
						$remaining_width      -= $column_width;

						$block_mjml_array[] = [
						'<mj-column ' . self::array_to_attributes( $column_attrs ) . '>',
						'<mj-button ' . self::array_to_attributes( $default_button_attrs ) . ">$text</mj-button>",
						'</mj-column>',
						];

						if ( $remaining_width < $default_width ) {
							$remaining_width    = 100;
							$block_mjml_array[] = [
							'<mj-section ' . self::array_to_attributes( $wrapper_attrs ) . '>',
							null,
							'</mj-section>',
							];
						}
					}

					if ( $is_multi_row && $remaining_width < 100 ) {
						$block_mjml_array[] = [
						'<mj-section ' . self::array_to_attributes( $wrapper_attrs ) . '>',
						null,
						'</mj-section>',
						];
					}

					$markup     = '';
					$button_row = '';
					foreach ( $block_mjml_array as $block_mjml ) {
						if ( isset( $block_mjml[1] ) ) {
							// Add a button to the button row markup.
							$button_row .= $block_mjml[0] . $block_mjml[1] . $block_mjml[2];
						} else {
							// Wrap button row in a section, then reset button row markup.
							$markup    .= $block_mjml[0] . $button_row . $block_mjml[2];
							$button_row = '';
						}
					}
					$block_mjml_markup = '<mj-wrapper ' . self::array_to_attributes( $wrapper_attrs ) . '>' . $markup . '</mj-wrapper>';
					break;

				/**
				 * Separator block.
				 */
				case 'core/separator':
					$is_wide       = isset( $block['attrs']['className'] ) && 'is-style-wide' === $block['attrs']['className'];
					$divider_attrs = array(
					'padding'      => '0',
					'border-width' => '1px',
					'width'        => $is_wide ? '100%' : '128px',
					);
					// Remove colors from section attrs.
					unset( $section_attrs['background-color'] );
					if ( isset( $block['attrs']['backgroundColor'] ) && isset( self::$color_palette[ $block['attrs']['backgroundColor'] ] ) ) {
						$divider_attrs['border-color'] = self::$color_palette[ $block['attrs']['backgroundColor'] ];
					}
					if ( isset( $block['attrs']['style']['color']['background'] ) ) {
						$divider_attrs['border-color'] = $block['attrs']['style']['color']['background'];
					}
					$block_mjml_markup .= '<mj-divider ' . self::array_to_attributes( $divider_attrs ) . '/>';

					break;

				/**
				 * Spacer block.
				 */
				case 'core/spacer':
					$attrs['height']    = $attrs['height'];
					$block_mjml_markup .= '<mj-spacer ' . self::array_to_attributes( $attrs ) . '/>';
					break;

				/**
				 * Social links block.
				 */
				case 'core/social-links':
					$social_wrapper_attrs = array(
					'icon-size'     => '24px',
					'mode'          => 'horizontal',
					'border-radius' => '999px',
					'icon-padding'  => isset( $attrs['className'] ) && 'is-style-filled-primary-text' === $attrs['className'] ? '0px' : '7px',
					'padding'       => '0',
					);
					if ( isset( $attrs['align'] ) ) {
						$social_wrapper_attrs['align'] = $attrs['align'];
					} else {
						$social_wrapper_attrs['align'] = 'left';
					}

					$markup = '<mj-social ' . self::array_to_attributes( $social_wrapper_attrs ) . '>';
					foreach ( $inner_blocks as $index => $link_block ) {
						$service_name = isset( $link_block['attrs']['service'] ) ? strtolower( $link_block['attrs']['service'] ) : '';
						if ( empty( $service_name ) ) {
							continue; // Skip if service name is missing
						}
						 
						$url = isset( $link_block['attrs']['url'] ) ? $link_block['attrs']['url'] :  '#' ;
						$social_icon = self::get_social_icon( $service_name, $attrs );

						if ( ! empty( $social_icon ) ) {
							$img_attrs = array(
							'href'             => $url,
							'src' => $social_icon['icon'], 
							'background-color' => $social_icon['color'],
							'css-class'        => 'social-element',
							'padding'          => '8px',
							);

							if ( 0 === $index || count( $inner_blocks ) - 1 === $index ) {
								$img_attrs['padding-left']  = 0 === $index ? '0' : '8px';
								$img_attrs['padding-right'] = 0 === $index ? '8px' : '0';
							}
							$markup .= '<mj-social-element ' . self::array_to_attributes( $img_attrs ) . '/>';
						}
					}
					$block_mjml_markup .= $markup . '</mj-social>';
					break;

				/**
				 * Single Column block.
				 */
				case 'core/column':
					if ( isset( $attrs['verticalAlignment'] ) ) {
						if ( 'center' === $attrs['verticalAlignment'] ) {
							$column_attrs['vertical-align'] = 'middle';
						} else {
							$column_attrs['vertical-align'] = $attrs['verticalAlignment'];
						}
					}

					if ( isset( $attrs['width'] ) ) {
						$column_attrs['width']     = $attrs['width'];
						$column_attrs['css-class'] = 'mj-column-has-width';
					}

					$markup = '<mj-column ' . self::array_to_attributes( $column_attrs ) . '>';
					foreach ( $inner_blocks as $block ) {
						$markup .= self::render_mjml_component( $block, true, false, $default_attrs );
					}
					$block_mjml_markup = $markup . '</mj-column>';
					break;

				/**
				 * Columns block.
				 */
				case 'core/columns':
					// Some columns might have no width set.
					$widths_sum            = 0;
					$no_width_cols_indexes = [];
					foreach ( $inner_blocks as $i => $block ) {
						if ( isset( $block['attrs']['width'] ) ) {
							$widths_sum += floatval( $block['attrs']['width'] );
						} else {
							array_push( $no_width_cols_indexes, $i );
						}
					}
					foreach ( $no_width_cols_indexes as $no_width_cols_index ) {
						$inner_blocks[ $no_width_cols_index ]['attrs']['width'] = ( 100 - $widths_sum ) / count( $no_width_cols_indexes ) . '%';
					}

					if ( isset( $attrs['color'] ) ) {
						$default_attrs['color'] = $attrs['color'];
					}
					$stack_on_mobile = ! isset( $attrs['isStackedOnMobile'] ) || true === $attrs['isStackedOnMobile'];
					if ( ! $stack_on_mobile ) {
						$markup = '<mj-group>';
					} else {
						$markup = '';
					}
					foreach ( $inner_blocks as $block ) {
						$markup .= self::render_mjml_component( $block, true, false, $default_attrs );
					}
					if ( ! $stack_on_mobile ) {
						$markup .= '</mj-group>';
					}
					$block_mjml_markup = $markup;
					break;

				/**
				 * List, list item, and quote blocks.
				 * These blocks may or may not contain innerBlocks with their actual content.
				 */
				case 'core/list':
				case 'core/list-item':
				case 'core/quote':
					$text_attrs = array_merge(
					array(
						'padding'     => '0',
						'line-height' => '1.5',
						'font-size'   => '16px',
						'font-family' => $font_family
					),
					is_array($attrs) ? $attrs : [] 
					);

					// If a wrapper block, wrap in mj-text.
					if ( ! $is_in_list_or_quote ) {
						$block_mjml_markup .= '<mj-text ' . self::array_to_attributes( $text_attrs ) . '>';
					}

					$block_mjml_markup .= $inner_content[0];
					if ( ! empty( $inner_blocks ) && 1 < count( $inner_content ) ) {
						foreach ( $inner_blocks as $inner_block ) {
							$block_mjml_markup .= self::render_mjml_component( $inner_block, false, false, [], true );
						}
						$block_mjml_markup .= $inner_content[ count( $inner_content ) - 1 ];
					}

					if ( ! $is_in_list_or_quote ) {
						$block_mjml_markup .= '</mj-text>';
					}

					break;

				/**
				 * Group block.
				 */
				case 'core/group':
					if ( isset( $attrs['color'] ) ) {
						$default_attrs['color'] = $attrs['color'];
					}

					$markup = '<mj-wrapper ' . self::array_to_attributes( $attrs ) . '>';
					foreach ( $inner_blocks as $block ) {
						$markup .= self::render_mjml_component( $block, false, true, $default_attrs );
					}
					$block_mjml_markup = $markup . '</mj-wrapper>';
					break;
			}

			$is_grouped_block        = in_array( $block_name, [ 'core/group', 'core/list', 'core/list-item', 'core/quote' ], true );

			if (
			! $is_in_column &&
			! $is_in_list_or_quote &&
			! $is_grouped_block &&
			'core/buttons' != $block_name &&
			'core/columns' != $block_name &&
			'core/column' != $block_name &&
			'core/separator' != $block_name 
			) {
				$column_attrs['width'] = '100%';
				$block_mjml_markup     = '<mj-column ' . self::array_to_attributes( $column_attrs ) . '>' . $block_mjml_markup . '</mj-column>';
			}

			if ( ! $is_in_column && ! $is_in_list_or_quote) {
				$block_mjml_markup = '<mj-section ' . self::array_to_attributes( $section_attrs ) . '>' . $block_mjml_markup . '</mj-section>';
			}

			if ( ! empty( $conditionals ) ) {
				$block_mjml_markup = '<mj-raw>' . $conditionals['before'] . '</mj-raw>' . $block_mjml_markup . '<mj-raw>' . $conditionals['after'] . '</mj-raw>';
			}

			return $block_mjml_markup;
		}

		

		/**
		 * Restrict block types 
		 *
		 * @param array   $allowed_block_types default block types.
		 * @param WP_Post $post the post to consider.
		 * 
		 * @return array Allowed blocked types for ES templates
		 */
		public static function allowed_block_types( $allowed_block_types, $editor_context  ) {
			
			if ( isset( $editor_context->post ) && $editor_context->post instanceof WP_Post && 'ig_es_campaign' === $editor_context->post->post_type ) {
			$allowed_block_types = array(
				'core/spacer',
				'core/block',
				'core/group',
				'core/paragraph',
				'core/heading',
				'core/column',
				'core/columns',
				'core/buttons',
				'core/button',
				'core/image',
				'core/separator',
				'core/list',
				'core/list-item',
				'core/quote',
				'core/social-links',
				'core/social-link',
				'core/site-logo',
				'core/site-tagline',
				'core/site-title',
			);
			}

			return $allowed_block_types;
		}
			/**
		 * Convert a list to HTML attributes.
		 *
		 * @param array $attributes Array of attributes.
		 * @return string HTML attributes as a string.
		 */
		private static function array_to_attributes( $attributes ) {
			return join(
				' ',
				array_map(
					function( $key ) use ( $attributes ) {
						if (
							isset( $attributes[ $key ] ) &&
							( is_string( $attributes[ $key ] ) || is_numeric( $attributes[ $key ] ) ) // Don't convert values that can't be expressed as a string.
						) {
							return $key . '="' . esc_attr( $attributes[ $key ] ) . '"';
						} else {
							return '';
						}
					},
					array_keys( $attributes )
				)
			);
		}

		/**
		 * Get font size based on block attributes.
		 *
		 * @param array $block_attrs Block attributes.
		 * @return string font size.
		 */
		private static function get_font_size( $block_attrs ) {
			if ( isset( $block_attrs['customFontSize'] ) ) {
				return $block_attrs['customFontSize'] . 'px';
			}
			if ( isset( $block_attrs['fontSize'] ) ) {
				$sizes = array(
				'small'   => '12px',
				'normal'  => '16px',
				'medium'  => '16px',
				'large'   => '24px',
				'huge'    => '36px',
				'x-large' => '36px',
				);
				return $sizes[ $block_attrs['fontSize'] ];
			}
		}

		/**
	 * Get a value for an image alt attribute.
	 *
	 * @param int $attachment_id Attachment ID of the image.
	 *
	 * @return string A value for the alt attribute.
	 */
		private static function get_image_alt( $attachment_id ) {
			if ( ! $attachment_id ) {
				return '';
			}

			$alt        = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$attachment = get_post( $attachment_id );

			if ( empty( $alt ) ) {
				$alt = $attachment->post_content;
			}
			if ( empty( $alt ) ) {
				$alt = $attachment->post_excerpt;
			}
			if ( empty( $alt ) ) {
				$alt = $attachment->post_title;
			}

			return $alt;
		}

	/**
	 * Get the social icon and color based on the block attributes.
	 *
	 * @param string $service_name The service name.
	 * @param array  $block_attrs  Block attributes.
	 *
	 * @return array[
	 *   'icon'  => string,
	 *   'color' => string,
	 * ] The icon and color or empty array if service not found.
	 */
		private static function get_social_icon( $service_name, $block_attrs ) {
			$services_colors = self::get_social_icons_services_colors();
			if ( ! isset( $services_colors[ $service_name ] ) ) {
				return [];
			}
			$icon  = 'white';
			$color = $services_colors[ $service_name ];
			if ( isset( $block_attrs['className'] ) ) {
				if ( 'is-style-filled-black' === $block_attrs['className'] || 'is-style-circle-white' === $block_attrs['className'] ) {
					$icon = 'black';
				} elseif ( 'is-style-filled-primary-text' === $block_attrs['className'] ) {
					$palette = json_decode( get_option( self::CAMPAIGN_COLOR_PALETTE, '{}' ), true );

					if ( isset( $palette['primary-text'] ) && ( 'black' === $palette['primary-text'] || '#000000' === $palette['primary-text'] ) ) {
						$icon = 'black';
					}
				}
				if ( 'is-style-filled-black' === $block_attrs['className'] || 'is-style-filled-white' === $block_attrs['className'] || 'is-style-filled-primary-text' === $block_attrs['className'] ) {
					$color = 'transparent';
				} elseif ( 'is-style-circle-black' === $block_attrs['className'] ) {
					$color = '#000';
				} elseif ( 'is-style-circle-white' === $block_attrs['className'] ) {
					$color = '#fff';
				}
			}
			return [
			//'icon'  => sprintf( '%s-%s.png', $icon, $service_name ),
			  'icon'  => sprintf( 'https://www.mailjet.com/images/theme/v1/icons/ico-social/%s.png', $service_name ),
			'color' => $color,
		];
		}
		private static function get_social_icons_services_colors() {
			return [
			'bluesky'   => '#0a7aff',
			'facebook'  => '#1977f2',
			'instagram' => '#f00075',
			'linkedin'  => '#0577b5',
			'threads'   => '#000000',
			'tiktok'    => '#000000',
			'tumblr'    => '#011835',
			'twitter'   => '#21a1f3',
			'x'         => '#000000',
			'wordpress' => '#3499cd',
			'youtube'   => '#ff0100',
			];
		}
		/**
		 * Get colors based on block attributes.
		 *
		 * @param array $block_attrs Block attributes.
		 * @return array Array of color attributes for MJML component.
		 */
		private static function get_colors( $block_attrs ) {
			$colors = array();

			if ( empty(  self::$color_palette ) ) {
				self::$color_palette = json_decode( get_option( self::CAMPAIGN_COLOR_PALETTE, false ), true );
			}

			// For text.
			if ( isset( $block_attrs['textColor'], self::$color_palette[ $block_attrs['textColor'] ] ) ) {
				$colors['color'] = self::$color_palette[ $block_attrs['textColor'] ];
			}
			// customTextColor is set inline, but it's passed here for consistency.
			if ( isset( $block_attrs['customTextColor'] ) ) {
				$colors['color'] = $block_attrs['customTextColor'];
			}
			if ( isset( $block_attrs['backgroundColor'], self::$color_palette[ $block_attrs['backgroundColor'] ] ) ) {
				$colors['background-color'] = self::$color_palette[ $block_attrs['backgroundColor'] ];
			}
			// customBackgroundColor is set inline, but not on mjml wrapper element.
			if ( isset( $block_attrs['customBackgroundColor'] ) ) {
				$colors['background-color'] = $block_attrs['customBackgroundColor'];
			}

			// For separators.
			if ( isset( $block_attrs['color'], self::$color_palette[ $block_attrs['color'] ] ) ) {
				$colors['border-color'] = self::$color_palette[ $block_attrs['color'] ];
			}
			if ( isset( $block_attrs['customColor'] ) ) {
				$colors['border-color'] = $block_attrs['customColor'];
			}

			// Custom color handling.
			if ( isset( $block_attrs['style'] ) ) {
				if ( isset( $block_attrs['style']['color']['background'] ) ) {
					$colors['background-color'] = $block_attrs['style']['color']['background'];
				}
				if ( isset( $block_attrs['style']['color']['text'] ) ) {
					$colors['color'] = $block_attrs['style']['color']['text'];
				}
			}

			return $colors;
		}
			/**
	 * Get spacing value.
	 *
	 * @param string $value Spacing value.
	 *
	 * @return string Spacing value.
	 */
		private static function get_spacing_value( $value ) {
			$presets = [
			'50' => 'clamp( 1.25rem, 1rem + 0.8333vw, 1.5rem )',
			'60' => 'clamp( 1.5rem, 0.75rem + 2.5vw, 2.25rem )',
			'70' => 'clamp( 1.75rem, 0.12rem + 5.4333vw, 3.38rem )',
			'80' => 'clamp( 2rem, -1.06rem + 10.2vw, 5.06rem )',
			];
			if ( 0 === strpos( $value, 'var' ) ) {
				$preset_key = explode( '|', $value );
				$preset     = end( $preset_key );
				if ( isset( $presets[ $preset ] ) ) {
					return $presets[ $preset ];
				}
				return '';
			}
			return $value;
		}

	/**
		 * Add color attributes and a padding, if component has a background color.
		 *
		 * @param array $attrs Block attributes.
		 * @return array MJML component attributes.
		 */
		private static function process_attributes( $attrs ) {
		$attrs     = array_merge(
			$attrs,
			self::get_colors( $attrs )
		);
		$font_size = self::get_font_size( $attrs );
			if ( isset( $font_size ) ) {
				$attrs['font-size'] = $font_size;
			}

			if ( isset( $attrs['style']['spacing']['padding'] ) ) {
				$padding = $attrs['style']['spacing']['padding'];
				foreach ( $padding as $key => $value ) {
					$padding[ $key ] = self::get_spacing_value( $value, $key );
				}
				$attrs['padding'] = sprintf( '%s %s %s %s', $padding['top'], $padding['right'], $padding['bottom'], $padding['left'] );
			}

			if ( ! empty( $attrs['borderRadius'] ) ) {
				$attrs['borderRadius'] = $attrs['borderRadius'] . 'px';
			}
			if ( isset( $attrs['style']['border']['radius'] ) ) {
				$attrs['borderRadius'] = $attrs['style']['border']['radius'];
			}

		// Remove block-only attributes.
		array_map(
			function ( $key ) use ( &$attrs ) {
				if ( isset( $attrs[ $key ] ) ) {
					unset( $attrs[ $key ] );
				}
			},
			[ 'customBackgroundColor', 'customTextColor', 'customFontSize', 'fontSize', 'backgroundColor', 'style' ]
		);

			if ( ! isset( $attrs['padding'] ) && isset( $attrs['background-color'] ) ) {
				$attrs['padding'] = '0';
			}

			if ( isset( $attrs['textAlign'] ) && ! isset( $attrs['align'] ) ) {
				$attrs['align'] = $attrs['textAlign'];
				unset( $attrs['textAlign'] );
			}

			if ( isset( $attrs['align'] ) && 'full' == $attrs['align'] ) {
				$attrs['full-width'] = 'full-width';
				unset( $attrs['align'] );
			}

			if ( ! isset( $attrs['padding'] ) && isset( $attrs['full-width'] ) && 'full-width' == $attrs['full-width'] && isset( $attrs['background-color'] ) ) {
				$attrs['padding'] = '12px 0';
			}

		return $attrs;
		}

		/**
		 * Whether the block is empty.
		 *
		 * @param WP_Block $block The block.
		 *
		 * @return bool Whether the block is empty.
		 */
		public static function is_empty_block( $block ) {
			$blocks_without_inner_html = [
			'core/site-logo',
			'core/site-title',
			'core/site-tagline',
			];

			$empty_block_name = empty( $block['blockName'] );
			$empty_html       = ! in_array( $block['blockName'], $blocks_without_inner_html, true ) && empty( $block['innerHTML'] );

			return $empty_block_name || $empty_html;
		}

		/**
		 * Init Rest API
		 */
		public static function rest_api_init() {
			
			\register_rest_route(
				self::API_NAMESPACE,
				'color-palette',
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ __CLASS__, 'api_set_color_palette' ],
					'permission_callback' => [ __CLASS__, 'api_authoring_permissions_check' ],
				]
			);
		}

		/**
			 * Check capabilities for using the API for authoring tasks.
			 *
			 * @param WP_REST_Request $request API request object.
			 * @return bool|WP_Error
			 */
		public static function api_authoring_permissions_check( $request ) {
			if ( ! current_user_can( 'edit_others_posts' ) ) {
				return new \WP_Error(
					'ig_es_rest_forbidden',
					esc_html__( 'You cannot use this resource.', 'email-subscribers' ),
					[
						'status' => 403,
					]
				);
			}
			return true;
		}

		/**
		 * The default color palette lives in the editor frontend and is not
		 * retrievable on the backend. The workaround is to set it as an option
		 * so that it's available to the email renderer.
		 *
		 * The editor can send multiple color palettes, so we're merging them.
		 *
		 * @param WP_REST_Request $request API request object.
		 */
		public static function api_set_color_palette( $request ) {
			self::update_color_palette( json_decode( $request->get_body(), true ) );

			return \rest_ensure_response( [] );
		}

		/**
		 * Updates the default newsletters color palette option.
		 *
		 * @param array $palette The updated color palette.
		 *
		 * @return bool True if the option was updated, false otherwise.
		 */
		public static function update_color_palette( $palette ) {
			return update_option(
				self::CAMPAIGN_COLOR_PALETTE,
				wp_json_encode(
					array_merge(
						json_decode( (string) get_option( self::CAMPAIGN_COLOR_PALETTE, '{}' ), true ) ?? [],
						$palette
					)
				)
			);
		}

		public function show_icegram_mailer_promotion_notice() {
			$notice_html = '';
			$cta_url     = admin_url( 'admin.php?page=es_gutenberg_editor' );
			ob_start();
			?>
			<div id="" class="text-gray-700">
				<p class="mb-2">
					<?php
					/* translators: 1: Starting strong tag 2. Closing strong tag 3: Starting strong tag 4. Closing strong tag 5: Starting anchor tag 6. Closing anchor tag*/
					echo sprintf( esc_html__( '%1$s[NEW]%2$s Our Express plugin now features a powerful %3$sEmail Editor%4$s built on Gutenberg — making it easier than ever to design stunning emails. %5$sTry it now%6$s', 'email-subscribers' ),
					'<strong>', '</strong>', '<strong>', '</strong>', '<a class="ig-es-dismiss-notice text-indigo-600" target="_blank" href=" ' . esc_url( $cta_url ) . '">', '</a>' )
					?>
					
				</p>
			</div>
			<?php
			$notice_html = ob_get_clean();
			new ES_Admin_Notice(
				'gutenberg_editor_promotion',
				$notice_html,
				'success',
				'edit_posts',
				array( 'es_campaigns', 'es_dashboard' )
			);
		}
	}

	
	ES_Gutenberg_Editor::instance();
}
