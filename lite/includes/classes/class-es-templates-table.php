<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Templates_Table {

	public static $instance;

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'es_template_meta_box_add' ) );
		add_action( 'save_post', array( $this, 'es_template_meta_save' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'preview_button' ) );
		add_filter( 'manage_edit-es_template_columns', array( $this, 'add_new_columns' ), 10, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ) );
		add_action( 'admin_footer', array( $this, 'add_custom_button' ) );
		add_action( 'edit_form_after_title', array( $this, 'add_template_type' ) );
		// duplicate template
		add_filter( 'post_row_actions', array( &$this, 'add_message_action' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'duplicate_message' ), 10, 1 );
	}

	public function add_template_type() {
		global $post;
		if ( ! ( is_object( $post ) && 'es_template' === $post->post_type ) ) {
			return;
		}
		$values = get_post_custom( $post->ID );

		$selected      = isset( $values['es_template_type'] ) ? esc_attr( $values['es_template_type'][0] ) : '';

		$template_type = ES_Common::get_campaign_types( array( 'sequence' ) );
		?>
		<p class="mt-3">
			<label for="es_template_type"><span class="font-semibold text-sm text-gray-700"><?php esc_html_e( 'Select template type', 'email-subscribers' ); ?></span></label><br/>
			<select style="margin: 0.20rem 0;" name="es_template_type" id="es_template_type">
				<?php
				if ( ! empty( $template_type ) ) {
					foreach ( $template_type as $key => $value ) {
						echo '<option value=' . esc_attr( $key ) . ' ' . selected( $selected, $key, false ) . '>' . esc_html( $value ) . '</option>';
					}
				}
				?>

			</select>
		</p>
		<?php
	}

	public function es_template_meta_box_add() {
		add_meta_box( 'es_template_meta_box', 'Available Keywords', array( $this, 'es_template_type_meta_box' ), 'es_template', 'normal', 'high' );
	}

	public function es_template_type_meta_box( $post ) {

		if ( ! ( is_object( $post ) && 'es_template' === $post->post_type ) ) {
			return;
		}
		?>
		<!-- Start-IG-Code -->
		<p id="post_notification">
			<a href="https://www.icegram.com/documentation/es-what-are-the-available-keywords-in-the-post-notifications/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Post Notification: ', 'email-subsribers' ); ?> {{FIRSTNAME}},
			{{LASTNAME}}, {{NAME}}, {{EMAIL}},
			{{DATE}}, {{POSTTITLE}}, {{POSTIMAGE}}, {{POSTEXCERPT}}, {{POSTDESC}},
		{{POSTAUTHOR}}, {{POSTLINK}}, {{POSTLINK-WITHTITLE}}, {{POSTLINK-ONLY}}, {{POSTFULL}} </p>
		<!-- End-IG-Code -->
		<p id="newsletter">
			<a href="https://www.icegram.com/documentation/es-what-are-the-available-keywords-in-the-newsletters/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Broadcast:', 'email-subscribers' ); ?> {{FIRSTNAME}}, {{LASTNAME}}, {{NAME}},
		{{EMAIL}} </p>
		<!-- Start-IG-Code -->
		<div id="post_digest">
			<span style="font-size: 0.8em; margin-left: 0.3em; padding: 2px; background: #e66060; color: #fff; border-radius: 2px; ">Pro</span>&nbsp;
			<a href="https://www.icegram.com/send-post-digest-using-email-subscribers-plugin/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_post_digest_post" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Post Digest:', 'email-subscribers' ); ?>
			{{FIRSTNAME}}, {{LASTNAME}}, {{NAME}}<div class="post_digest_block"> {{POSTDIGEST}} <br/><?php esc_html_e( 'Any keywords related Post Notification', 'email-subscribers' ); ?> <br/>{{/POSTDIGEST}} </div>
		</div>
		<!-- End-IG-Code -->
		<?php
	}

	public function es_template_meta_save( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}
		if ( is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( 'es_template' != $post->post_type ) {
			return;
		}

		if ( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {

			$es_template_type = ig_es_get_data( $_POST, 'es_template_type', '', true );
	
			if ( ! empty( $es_template_type ) ) {
				update_post_meta( $post_id, 'es_template_type', $es_template_type );
			}
		}

	}


	public function preview_button( $post ) {
		if ( is_object( $post ) && 'es_template' === $post->post_type ) {
			$post_id = $post->ID;
			?>

			<div class="misc-pub-section">
				<div id="" class="es_preview_button" style="display: block;">
					<a style="padding-top: 3px; margin-bottom: 0.2rem;" href="<?php echo esc_url( admin_url() ); ?>admin.php?page=es_template_preview&post=<?php echo esc_attr( $post_id ); ?>&preview=true&preview_id=<?php echo esc_attr( $post_id ); ?>" target="_blank" class="button button-primary es_preview"><?php esc_html_e( 'Preview template', 'email-subscribers' ); ?></a>
					<div class="clear"></div>
				</div>
			</div>

			<?php
		}
	}

	public function add_custom_button() {
		$screen = get_current_screen();
		if ( 'es_template' == $screen->post_type ) {
			?>
			<script type="text/javascript">
				jQuery('<a style="top:-3px;position: relative" href="admin.php?page=es_campaigns" class="ig-es-title-button ml-2 mb-3">Campaigns</a>').insertBefore(".wp-header-end");
			</script>
			<?php
		}
	}

	public function es_template_preview_callback() {

		$template_id = ig_es_get_request_data( 'post' );

		$template = get_post( $template_id, ARRAY_A );

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

			$es_template_body = $template['post_content'];

			$es_template_type = get_post_meta( $template_id, 'es_template_type', true );

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

			$es_template_body = str_replace( '{{NAME}}', $username, $es_template_body );
			$es_template_body = str_replace( '{{EMAIL}}', $useremail, $es_template_body );
			$es_template_body = str_replace( '{{FIRSTNAME}}', $first_name, $es_template_body );
			$es_template_body = str_replace( '{{LASTNAME}}', $last_name, $es_template_body );
			$allowedtags 	  = ig_es_allowed_html_tags_in_esc();
			add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );

			if ( has_post_thumbnail( $template_id ) ) {
				$image_array = wp_get_attachment_image_src( get_post_thumbnail_id( $template_id ), 'full' );
				$image       = '<img src="' . $image_array[0] . '" class="img-responsive" alt="Image for Post ' . $template_id . '" />';
			} else {
				$image = '';
			}
			$html  = '';
			$html .= '<style type="text/css">
			.es-sidebar {
				width: 23%;
				background-color: rgb(230, 230, 230);
				padding:15px;
				border-right: 1px solid #bdbdbd;
			}
			.es-preview {
				float: left;
				padding:15px;
				width: 70%;
				background-color:#FFF;
				font-size:16px;
			}
			.es-main-preview-block{
				display:flex;
			}
			.es-clear-preview{
				clear: both;
			}
			.es-preview-margin{
				margin-bottom: 1em;
			}
			</style>
			<div class="wrap">
			<div class="tool-box">
			<div class="es-main-preview-block">
			<div class="es-sidebar">
			<h2 class="es-preview-margin">
			Template Preview <a class="add-new-h2" href="' . admin_url() . 'admin.php?page=es-general-information">Help</a>
			</h2>
			<p>
			<a class="button-primary"  href="' . admin_url() . 'post.php?post=' . $template_id . '&action=edit">Edit</a>
			</p>
			<p>
			This is how your email may look.<br><br>Note: Different email services (like gmail, yahoo etc) display email content differently. So there could be a slight variation on how your customer will view the email content.				</p>
			</div>
			<div class="es-preview">' . $es_template_body . '</div>
			<div class="es-clear-preview"></div>
			</div>
			<div class="es-clear-preview"></div>
			</div>
			</div>';
			echo wp_kses( apply_filters( 'the_content', $html ), $allowedtags);
		} else {
			echo esc_html__( 'Please publish it or save it as a draft.', 'email-subscribers' );
		}

	}

	public function add_new_columns( $existing_columns ) {

		$date = $existing_columns['date'];
		unset( $existing_columns['date'] );

		$existing_columns['es_template_type']      = __( 'Template type', 'email-subscribers' );
		$existing_columns['es_template_thumbnail'] = __( 'Thumbnail', 'email-subscribers' );
		$existing_columns['date']                  = $date;

		return $existing_columns;

	}

	public function custom_columns( $column ) {

		global $post;

		$es_template_thumbnail      = get_the_post_thumbnail( $post->ID, array( '200', '200' ) );
		$default_template_thumbnail = '<img src="' . ES_PLUGIN_URL . 'lite/admin/images/envelope.png" />';
		$es_template_thumbnail      = apply_filters( 'ig_es_template_thumbnail', $es_template_thumbnail );
		$es_template_thumbnail      = ( ! empty( $es_template_thumbnail ) ) ? $es_template_thumbnail : $default_template_thumbnail;
		switch ( $column ) {
			case 'es_template_type':
				$type = get_post_meta( $post->ID, 'es_template_type', true );
				$type = sanitize_text_field(strtolower( $type ));
				$type = ( 'newsletter' === $type ) ? __( 'Broadcast', 'email-subscribers' ) : $type;
				$type = ucwords( str_replace( '_', ' ', $type ) );
				echo esc_html( $type );
				break;
			case 'es_template_thumbnail':
				echo wp_kses_post( $es_template_thumbnail );
				break;
			default:
				break;
		}

		return $column;
	}

	public function add_message_action( $actions, $post ) {
		if ( 'es_template' != $post->post_type ) {
			return $actions;
		}
		$actions['duplicate_template'] = '<a class="es-duplicate-template"  href="post.php?template_id=' . $post->ID . '&action=duplicate-template" >' . __( 'Duplicate', 'email-subscribers' ) . '</a>';

		return $actions;
	}

	public function duplicate_message() {
		$action      = ig_es_get_request_data( 'action' );
		$template_id = ig_es_get_request_data( 'template_id' );
		if ( ! empty( $template_id ) && 'duplicate-template' === $action ) {
			// duplicate tempalte
			$this->duplicate_in_db( $template_id );
			// $location = admin_url( 'post.php?post='.$duplicate_template_id.'&action=edit');
			$location = admin_url( 'edit.php?post_type=es_template' );
			wp_safe_redirect( $location );
			exit;
		}
	}

	public function duplicate_in_db( $original_id ) {
		// Get access to the database
		global $wpdb;
		// Get the post as an array
		$duplicate = get_post( $original_id, 'ARRAY_A' );
		// Modify some of the elements
		$duplicate['post_title']  = $duplicate['post_title'] . ' ' . __( 'Copy', 'email-subscribers' );
		$duplicate['post_status'] = 'draft';
		// Set the post date
		$timestamp = current_time( 'timestamp', 0 );

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
			$terms = wp_get_post_terms( $original_id, $taxonomy, array( 'fields' => 'names' ) );
			wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
		}

		// Duplicate all custom fields
		$custom_fields = get_post_custom( $original_id );
		foreach ( $custom_fields as $key => $value ) {
			add_post_meta( $duplicate_id, $key, maybe_unserialize( $value[0] ) );
		}

		return $duplicate_id;
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
