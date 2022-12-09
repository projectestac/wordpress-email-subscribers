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
		add_filter( 'post_row_actions', array( &$this, 'add_template_action' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'duplicate_template' ), 10, 1 );
		add_action( 'admin_footer', array( $this, 'es_template_preview_callback' ), 10 );

		add_action( 'parse_query', array( $this, 'exclude_dnd_templates' ) );
	}

	public function add_template_type() {
		global $post;
		if ( ! ( is_object( $post ) && 'es_template' === $post->post_type ) ) {
			return;
		}
		$values = get_post_custom( $post->ID );

		$selected = isset( $values['es_template_type'] ) ? esc_attr( $values['es_template_type'][0] ) : '';

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
		add_meta_box( 'es_template_meta_box', __( 'Available Keywords', 'email-subscribers' ), array( $this, 'es_template_type_meta_box' ), 'es_template', 'normal', 'high' );
	}

	public function es_template_type_meta_box( $post ) {

		if ( ! ( is_object( $post ) && 'es_template' === $post->post_type ) ) {
			return;
		}
		?>
		<!-- Start-IG-Code -->
		<p id="post_notification">
			<a href="https://www.icegram.com/documentation/what-keywords-can-be-used-while-designing-the-campaign/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Post Notification: ', 'email-subsribers' ); ?> {{subscriber.first_name | fallback:'there'}},
			{{subscriber.last_name}}, {{subscriber.name}}, {{subscriber.email}},
			{{post.date}}, {{post.title}}, {{post.image}}, {{post.excerpt}}, {{post.description}},
			{{post.author}}, {{post.author_avatar}}, {{post.author_avatar_url}}, {{post.link}}, {{post.link_with_title}}, {{post.link_only}}, {{post.full}} </p>
		<!-- End-IG-Code -->
		<p id="newsletter">
			<a href="https://www.icegram.com/documentation/what-keywords-can-be-used-while-designing-the-campaign/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Broadcast:', 'email-subscribers' ); ?> {{subscriber.first_name | fallback:'there'}}, {{subscriber.last_name}}, {{subscriber.name}},
			{{subscriber.email}} </p>
		<!-- Start-IG-Code -->
		<div id="post_digest">
			<span style="font-size: 0.8em; margin-left: 0.3em; padding: 2px; background: #e66060; color: #fff; border-radius: 2px; ">Pro</span>&nbsp;
			<a href="https://www.icegram.com/send-post-digest-using-email-subscribers-plugin/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_post_digest_post" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Post Digest:', 'email-subscribers' ); ?>
			{{subscriber.first_name | fallback:'there'}}, {{subscriber.last_name}}, {{subscriber.name}}<div class="post_digest_block"> {{post.digest}} <br/><?php esc_html_e( 'Any keywords related Post Notification', 'email-subscribers' ); ?> <br/>{{/post.digest}} </div>
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
					<a style="padding-top: 3px; margin-bottom: 0.2rem;" href="#" data-post-id="<?php echo esc_attr( $post_id ); ?>" class="button button-primary es_template_preview"><?php esc_html_e( 'Preview template', 'email-subscribers' ); ?></a><img class="es-template-preview-loader inline-flex align-middle pl-2 h-5 w-7" src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/spinner-2x.gif" style="display:none;"/>
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
		?>
			<div class="hidden" id="es_preview_template">
			<div class="fixed top-0 left-0 z-50 flex items-center justify-center w-full h-full" style="background-color: rgba(0,0,0,.5);">
				<div style="height:485px" class="absolute h-auto p-4 ml-16 mr-4 text-left bg-white rounded shadow-xl z-80 md:max-w-5xl md:p-6 lg:p-8 ">
					<h3 class="text-2xl text-center"><?php echo esc_html__( 'Template Preview', 'email-subscribers' ); ?></h3>
					<p class="m-4 text-center"><?php echo esc_html__( 'There could be a slight variation on how your customer will view the email content.', 'email-subscribers' ); ?></p>
					<div class="m-4 list-decimal template_preview_container">
					</div>
					<div class="flex justify-center mt-8">
						<button id="es_close_template_preview" class="px-4 py-2 text-sm font-medium tracking-wide text-gray-700 border rounded select-none no-outline focus:outline-none focus:shadow-outline-red hover:border-red-400 active:shadow-lg "><?php echo esc_html__( 'Close', 'email-subscribers' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
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
				$type = sanitize_text_field( strtolower( $type ) );
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

	public function add_template_action( $actions, $post ) {
		
		if ( 'es_template' !== $post->post_type ) {
			return $actions;
		}

		$nonce = wp_create_nonce( 'ig_es_duplicate_template_nonce' );

		$actions['duplicate_template'] = '<a class="es-duplicate-template"  href="post.php?template_id=' . $post->ID . '&action=duplicate-template&_wpnonce=' . $nonce . '" >' . __( 'Duplicate', 'email-subscribers' ) . '</a>';

		return $actions;
	}

	public function duplicate_template() {
		$action      = ig_es_get_request_data( 'action' );
		$template_id = ig_es_get_request_data( 'template_id' );
		if ( ! empty( $template_id ) && 'duplicate-template' === $action ) {

			
			check_admin_referer( 'ig_es_duplicate_template_nonce' );

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

	/**
	 * Exclude DND Templates from template list
	 * 
	 * @since 4.5.3
	 */
	public static function exclude_dnd_templates( $wp_query ) {

		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $wp_query->query_vars['post_type'] ) ||'es_template' !== $wp_query->query_vars['post_type'] ) {
			return;
		}
		
		$wp_query->query_vars['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => 'es_editor_type',
				'value'   => IG_ES_CLASSIC_EDITOR,
				'compare' => '=',
			),
			array(
				'key'     => 'es_editor_type',
				'compare' => 'NOT EXISTS', // if key doesn't exists, then template is created using Classic editor
			),
		);
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
