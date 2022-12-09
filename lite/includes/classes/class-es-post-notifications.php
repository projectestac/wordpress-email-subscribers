<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Post_Notifications_Table {

	public static $instance;

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
		add_action( 'ig_es_' . IG_CAMPAIGN_TYPE_POST_NOTIFICATION . '_content_settings', array( $this, 'show_post_notification_content_settings' ) );
		add_action( 'ig_es_' . IG_CAMPAIGN_TYPE_POST_DIGEST . '_content_settings', array( $this, 'show_post_notification_content_settings' ) );
		add_action( 'ig_es_show_' . IG_CAMPAIGN_TYPE_POST_NOTIFICATION . '_campaign_summary_action_buttons', array( $this, 'show_summary_actions_buttons' ) );
		add_action( 'ig_es_show_' . IG_CAMPAIGN_TYPE_POST_DIGEST . '_campaign_summary_action_buttons', array( $this, 'show_summary_actions_buttons' ) );
		add_action( 'ig_es_' . IG_CAMPAIGN_TYPE_POST_NOTIFICATION . '_default_subject', array( $this, 'get_post_notification_default_subject' ), 10, 2 );
		add_action( 'ig_es_' . IG_CAMPAIGN_TYPE_POST_NOTIFICATION . '_default_content', array( $this, 'get_post_notification_default_content' ), 10, 2 );
	}

	public function es_notifications_callback() {

		$action = ig_es_get_request_data( 'action' );

		?>
		<div class="wrap pt-4 font-sans">
			<?php

			if ( 'edit' === $action ) {
				$list = ig_es_get_request_data( 'list' );
				$this->edit_list( absint( $list ) );
			} else {
				$this->es_newnotification_callback();
			}
			?>
		</div>
		<?php
	}

	public function es_newnotification_callback() {

		$submitted = ig_es_get_request_data( 'submitted' );
		if ( 'submitted' === $submitted ) {

			// Get nonce field value.
			$nonce = ig_es_get_request_data( '_wpnonce' );
			// Verify nonce.
			if ( wp_verify_nonce( $nonce, 'es_post_notification' ) ) {
				$list_id            = ig_es_get_request_data( 'list_id' );
				$template_id        = ig_es_get_request_data( 'template_id' );
				$cat                = ig_es_get_request_data( 'es_note_cat' );
				$es_note_cat_parent = ig_es_get_request_data( 'es_note_cat_parent' );
				$cat                = ( ! empty( $es_note_cat_parent ) && in_array( $es_note_cat_parent, array( '{a}All{a}', '{a}None{a}' ), true ) ) ? array( $es_note_cat_parent ) : $cat;

				if ( empty( $list_id ) ) {
					$message = __( 'Please select list.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$this->prepare_post_notification_form();

					return;
				}

				if ( empty( $cat ) ) {
					$message = __( 'Please select categories.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$this->prepare_post_notification_form();

					return;
				}

				$type  = 'post_notification';
				$title = get_the_title( $template_id );

				$data = array(
					'categories'       => ES_Common::convert_categories_array_to_string( $cat ),
					'list_ids'         => $list_id,
					'base_template_id' => $template_id,
					'status'           => 1,
					'type'             => $type,
					'name'             => $title,
					'slug'             => sanitize_title( $title ),
				);

				$data = apply_filters( 'ig_es_post_notification_data', $data );
				if ( empty( $data['base_template_id'] ) ) {
					$message = __( 'Please select template.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$this->prepare_post_notification_form();

					return;
				}
				$this->save_list( $data );
				$type = ucwords( str_replace( '_', ' ', $data['type'] ) );
				/* translators: %s: Campaign Type */
				$message = sprintf( __( '%s added successfully!', 'email-subscribers' ), $type );
				ES_Common::show_message( $message, 'success' );
			} else {
				$message = __( 'Sorry, you are not allowed to add post notification.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}
		}

		$this->prepare_post_notification_form();

	}

	public function custom_admin_notice() {
		$es_note_cate = ig_es_get_request_data( 'es_note_cate' );

		if ( $es_note_cate ) {
			echo '<div class="updated"><p>Notification Added Successfully!</p></div>';
		}
	}

	public function update_list( $id ) {

		global $wpdb;
		$cat  = ig_es_get_request_data( 'es_note_cat' );
		$data = array(
			'categories'       => ES_Common::convert_categories_array_to_string( $cat ),
			'list_ids'         => ig_es_get_request_data( 'list_id' ),
			'base_template_id' => ig_es_get_request_data( 'template_id' ),
			'status'           => 'active',
		);
		$wpdb->update( IG_CAMPAIGNS_TABLE, $data, array( 'id' => $id ) );

	}

	public function save_list( $data, $id = null ) {
		return ES()->campaigns_db->save_campaign( $data, $id );
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_lists( $per_page = 5, $page_number = 1 ) {

		global $wpdb, $wpbd;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		$add_where_clause = false;
		$sql              = 'SELECT * FROM ' . IG_CAMPAIGNS_TABLE;
		$args             = array();
		$query            = array();

		if ( ! empty( $search ) ) {
			$add_where_clause = true;
			$query[]          = ' name LIKE %s ';
			$args[]           = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( $add_where_clause ) {
			$sql .= ' WHERE ';

			if ( count( $query ) > 0 ) {
				$sql .= implode( ' AND ', $query );
				$sql  = $wpbd->prepare( $sql, $args );
			}
		}

		// Prepare Order by clause
		$order_by_clause = '';
		if ( ! empty( $order_by ) ) {
			$order_by_clause  = ' ORDER BY ' . esc_sql( $order_by );
			$order_by_clause .= ! empty( $order ) ? ' ' . esc_sql( $order ) : ' ASC';
		}

		$sql .= $order_by_clause;
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpbd->get_results( $sql, 'ARRAY_A' );

		return $result;

	}

	public function edit_list( $id ) {

		global $wpdb;

		$notification_query = $wpdb->prepare( ' id = %d LIMIT 0, 1', $id );
		$notifications      = ES()->campaigns_db->get_by_conditions( $notification_query );
		$data               = array();

		$submitted = ig_es_get_request_data( 'submitted' );
		if ( 'submitted' === $submitted ) {
			// Get nonce field value.
			$nonce = ig_es_get_request_data( '_wpnonce' );
			// Verify nonce.
			if ( wp_verify_nonce( $nonce, 'es_post_notification' ) ) {
				$categories = ig_es_get_request_data( 'es_note_cat', array() );

				// all categories selected
				$parent_category_option = ig_es_get_request_data( 'es_note_cat_parent' );
				if ( in_array( $parent_category_option, array( '{a}All{a}', '{a}None{a}' ), true ) ) {
					array_unshift( $categories, $parent_category_option );
				}

				$data = array(
					'categories'       => ES_Common::convert_categories_array_to_string( $categories ),
					'list_ids'         => ig_es_get_request_data( 'list_id' ),
					'base_template_id' => ig_es_get_request_data( 'template_id' ),
					'status'           => ig_es_get_request_data( 'status' ),
				);

				$title = '';
				if ( ! empty( $data['base_template_id'] ) ) {
					$title = get_the_title( $data['base_template_id'] );
				}
				$data['name'] = $title;

				$data         = apply_filters( 'ig_es_post_notification_data', $data );
				$data['type'] = ! empty( $data['type'] ) ? $data['type'] : 'post_notification';
				// check tempalte id
				if ( empty( $data['base_template_id'] ) ) {
					$message = __( 'Please select template.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$this->prepare_post_notification_form( $id, $data );

					return;
				}
				// check categories
				if ( empty( $categories ) ) {
					$message = __( 'Please select Categories.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$this->prepare_post_notification_form( $id, $data );

					return;
				}
				$this->save_list( $data, $id );

				$data['categories'] = ES_Common::convert_categories_string_to_array( $data['categories'], true );
				$type               = ucwords( str_replace( '_', ' ', $data['type'] ) );
				/* translators: %s: Campaign type */
				$message = sprintf( __( '%s updated successfully!', 'email-subscribers' ), $type );
				ES_Common::show_message( $message, 'success' );
			} else {
				$message = __( 'Sorry, you are not allowed to update post notification.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}
		} else {

			$notification = array_shift( $notifications );
			$id           = $notification['id'];

			$categories_str = ! empty( $notification['categories'] ) ? $notification['categories'] : '';
			$categories     = ES_Common::convert_categories_string_to_array( $categories_str, true );
			$data           = array(
				'categories'       => $categories,
				'list_ids'         => $notification['list_ids'],
				'base_template_id' => $notification['base_template_id'],
				'status'           => $notification['status'],
			);
		}

		$this->prepare_post_notification_form( $id, $data );

	}

	public static function prepare_post_notification_form( $id = '', $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action  = 'new';
		$heading = __( ' New Post Notification', 'email-subscribers' );
		if ( ! $is_new ) {
			$action  = 'edit';
			$heading = __( ' Edit Post Notification', 'email-subscribers' );
		}
		$cat     = isset( $data['categories'] ) ? $data['categories'] : '';
		$list_id = isset( $data['list_ids'] ) ? $data['list_ids'] : '';

		$template_id = isset( $data['base_template_id'] ) ? $data['base_template_id'] : '';
		$status      = isset( $data['status'] ) ? $data['status'] : 0;
		$nonce       = wp_create_nonce( 'es_post_notification' );

		$select_list_attr  = ES()->is_pro() ? 'multiple="multiple"' : '';
		$select_list_name  = ES()->is_pro() ? 'list_id[]' : 'list_id';
		$select_list_class = ES()->is_pro() ? 'ig-es-form-multiselect' : 'form-select';
		$allowedtags       = ig_es_allowed_html_tags_in_esc();
		?>

		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="md:flex md:items-center md:justify-between justify-center">
					<div class="flex-1 min-w-0">
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
						<ol class="list-none p-0 inline-flex">
							<li class="flex items-center text-sm tracking-wide">
							<a class="hover:underline" href="admin.php?page=es_campaigns"><?php esc_html_e( 'Campaigns ', 'email-subscribers' ); ?></a>
							<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
							</li>
						</ol>
					</nav>
						<h2 class="-mt-1 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate">
							<?php echo esc_html( $heading ); ?>
						</h2>
					</div>

					<div class="flex md:mt-0">
						<?php if ( 'edit' === $action ) { ?>
							<a href="admin.php?page=es_notifications&action=new" class="ig-es-title-button py-1.5 mx-2"><?php esc_html_e( 'Add New', 'email-subscribers' ); ?></a>
						<?php } ?>
						<a href="edit.php?post_type=es_template" class="ig-es-imp-button px-3 py-1"><?php esc_html_e( 'Manage Templates', 'email-subscribers' ); ?></a>
					</div>
				</div>
			</header>
			<div class="">
				<hr class="wp-header-end">
			</div>

			<div class="bg-white shadow-md rounded-lg mt-8">
				<form id="ig-es-post-notification-form" class="ml-5 mr-4 text-left pt-4 mt-2 item-center" method="post" action="admin.php?page=es_notifications&action=<?php echo esc_attr( $action ); ?>&list=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>">

					<table class="max-w-full form-table">
						<tbody>
						<?php if ( ! $is_new ) { ?>
							<tr class="border-b border-gray-100">
								<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
									<label for="tag-link"><span class="block ml-6 pr-4 pt-2 text-sm font-medium text-gray-600 pb-2">
												<?php esc_html_e( 'Enable/Disable campaign', 'email-subscribers' ); ?>
									</label>

								</th>
								<td class="w-9/12 py-3">
									<label for="status" class="ml-12 inline-flex items-center cursor-pointer"><span class="relative">
												<input id="status" type="checkbox" class="absolute es-check-toggle opacity-0 w-0 h-0"
													   name="status" value="1" <?php checked( $status, '1' ); ?> />

												<span class="es-mail-toggle-line"></span>
												<span class="es-mail-toggle-dot"></span>
											</span></label>
								</td>
							</tr>
						<?php } ?>

						<?php do_action( 'es_before_post_notification_settings', $id ); ?>

							<tr class="border-b  border-gray-100">
								<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
									<label for="tag-link"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-2">
											<?php esc_html_e( 'Select list', 'email-subscribers' ); ?></span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug"><?php esc_html_e( 'Contacts from the selected list will be notified about new post notification.', 'email-subscribers' ); ?></p></label>
								</th>
								<td class="w-9/12 pb-3 ">
									<div class="flex">
										<div class="w-2/4 inline-flex ml-12 relative">
											<select <?php echo esc_attr( $select_list_attr ); ?> class="absolute shadow-sm border border-gray-400 w-2/3 <?php echo esc_attr( $select_list_class ); ?>" name="<?php echo esc_attr( $select_list_name ); ?>" id="ig_es_post_notification_list_ids">
												<?php
												$lists_dropdown = ES_Common::prepare_list_dropdown_options( $list_id );
												echo wp_kses( $lists_dropdown, $allowedtags );
												?>
											</select>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<?php do_action( 'ig_es_add_multilist_options' ); ?>
					<table class="max-w-full form-table">
						<tbody>
						<tr class="border-b border-gray-100">
							<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
								<label for="tag-link"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-2">
											<?php esc_html_e( 'Select template', 'email-subscribers' ); ?></span>
									<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug"><?php esc_html_e( 'Content of the selected template will be sent out as post notification.', 'email-subscribers' ); ?></p>
								</label>
							</th>
							<td class="w-9/12 pb-3">
								<select class="relative form-select shadow-sm border border-gray-400 w-1/3 ml-12" name="template_id" id="base_template_id">
									<?php
									$templates = ES_Common::prepare_templates_dropdown_options( 'post_notification', $template_id );
									echo wp_kses( $templates, $allowedtags );
									?>
								</select>
								<div class="es-preview" style="float: right;width: 25%;">
									<div class="es-templ-img"></div>
								</div>
							</td>
						</tr>
						<?php do_action( 'es_after_post_notification_template', $id ); ?>
						<tr class="border-b border-gray-100">
							<th scope="row" class="pt-3 pb-8 w-3/12 text-left">
								<label for="tag-link"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Select post category', 'email-subscribers' ); ?></span>
									<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug"><?php esc_html_e( 'Notification will be sent out when any post from selected categories will be published.', 'email-subscribers' ); ?></p></label>
							</th>
							<td class="pt-3 w-9/12" style="vertical-align: top;">
								<table border="0" cellspacing="0" class="ml-4 pt-3">
									<tbody>
									<?php
									$categories_lists = ES_Common::prepare_categories_html( $cat );
									echo wp_kses( $categories_lists, $allowedtags );
									?>
									</tbody>
								</table>
							</td>
						</tr>
						<tr class="border-b border-gray-100">
							<th scope="row" class="pt-3 pb-8 w-3/12 text-left">
								<label for="tag-link"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-2">
												<?php esc_html_e( 'Select custom post type(s)', 'email-subscribers' ); ?></span>
									<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug"><?php esc_html_e( '(Optional) Select custom post type for which you want to send notification.', 'email-subscribers' ); ?></p></label>

							</th>
							<td class="w-9/12 pt-3 pb-8">
								<table border="0" cellspacing="0">
									<tbody>
									<?php
									$custom_post_type      = '';
									$custom_post_type_list = ES_Common::prepare_custom_post_type_checkbox( $cat );
									echo wp_kses( $custom_post_type_list, $allowedtags );
									?>
									</tbody>
								</table>
							</td>
						</tr>
						<?php do_action( 'es_after_post_notification_settings', $id ); ?>
						<tr>
							<td><input type="hidden" name="submitted" value="submitted"></td>
						</tr>
						</tbody>
					</table>
					<div>
						<?php
						$submit_button_text = $is_new ? __( 'Save Campaign', 'email-subscribers' ) : __( 'Save Changes', 'email-subscribers' );
						?>
						<p class="submit"><input type="submit" name="submit" id="ig_es_campaign_post_notification_submit_button" class="cursor-pointer align-middle ig-es-primary-button px-4 py-2 ml-6 mr-2" value="<?php echo esc_attr( $submit_button_text ); ?>"/>
							<a href="admin.php?page=es_campaigns" class="cursor-pointer align-middle rounded-md border border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo text-sm leading-5 font-medium transition ease-in-out duration-150 px-4 my-2 py-2 mx-2 "><?php esc_html_e( 'Cancel', 'email-subscribers' ); ?></a></p>
					</div>

				</form>

			</div>
		</div>

		<?php

	}


	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk_delete' => 'Delete',
		);

		return $actions;
	}

	public function search_box( $text, $input_id ) {
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( 'Search Notifications', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

	public function show_summary_actions_buttons( $campaign_data ) {

		$campaign_status = ! empty( $campaign_data['status'] ) ? (int) $campaign_data['status'] : IG_ES_CAMPAIGN_STATUS_IN_ACTIVE;

		$is_campaign_inactive = IG_ES_CAMPAIGN_STATUS_IN_ACTIVE === $campaign_status;

		if ( $is_campaign_inactive ) {
			?>
			<button type="submit" name="ig_es_campaign_action" class="w-24 inline-flex justify-center py-1.5 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md md:px-2 lg:px-3 xl:px-4 md:ml-2 hover:bg-indigo-500 hover:text-white"
					value="activate">
				<?php
					echo esc_html__( 'Activate', 'email-subscribers' );
				?>
			</button>
			<?php
		}
	}

	public function show_post_notification_content_settings( $campaign_data ) {
		$campaign_id   = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
		$campaign_type = ! empty( $campaign_data['type'] ) ? $campaign_data['type'] : '';
		$editor_type   = ! empty( $campaign_data['meta']['editor_type'] ) ? $campaign_data['meta']['editor_type'] : IG_ES_DRAG_AND_DROP_EDITOR;

		if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type  ) {
			$sidebar_id = 'sidebar_campaign_settings_' . $campaign_id;
			?>
			<div id="ig-es-post-notification-settings-popup" class="ig-es-post-notification-settings my-2 block pt-2 pb-4 mx-4 border-b border-gray-200" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>" data-campaign-type="<?php echo esc_attr( $campaign_type ); ?>" x-data="{ <?php echo esc_attr( $sidebar_id ); ?>: false }">
				<h2 class="text-sm font-normal text-gray-600">
					<span class=""><?php echo esc_html__( 'Posts settings:' ); ?></span>
				</h2>
				<p class="clear">
					<a class="block edit-conditions rounded-md border text-indigo-600 border-indigo-500 text-sm leading-5 font-medium transition ease-in-out duration-150 select-none inline-flex justify-center hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg mt-1 px-1.5 py-1 mr-1 cursor-pointer" x-on:click="<?php echo esc_attr( $sidebar_id ); ?>=true">
						<?php esc_html_e( 'Change posts settings', 'email-subscribers' ); ?>
					</a>
				</p>
				<div class="fixed inset-0 overflow-hidden z-50" id='ig-es-post-notification-settings-<?php echo esc_attr( $sidebar_id ); ?>' style="display: none;" x-show="<?php echo esc_attr( $sidebar_id ); ?>">
					<div class="absolute inset-0 overflow-hidden">
						<div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
						<section class="absolute inset-y-0 right-0 pl-10 max-w-full flex" aria-labelledby="slide-over-heading">
							<div class="relative w-screen max-w-3xl mt-8"
							x-transition:enter="ease-out duration-300"
							x-transition:enter-start="opacity-0 -translate-x-full"
							x-transition:enter-end="opacity-100 translate-x-0"
							x-transition:leave="ease-in duration-200"
							x-transition:leave-start="opacity-100 translate-x-0"
							x-transition:leave-end="opacity-0 -translate-x-full">

							<div class="h-full flex flex-col bg-gray-50 shadow-xl overflow-y-auto">
								<div class="flex py-5 px-6 bg-gray-100 shadow-sm sticky">
									<div class="w-9/12">
										<span id="slide-over-heading" class="text-xl font-medium text-gray-600">
											<?php echo esc_html__( 'Posts Settings', 'email-subscribers' ); ?>
										</span>
									</div>
									<div class="w-3/12 text-right">
										<span class="es_spinner_image_admin inline-block align-middle -mt-1 mr-1" id="spinner-image" style="display:none"><img src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/public/images/spinner.gif' ); ?>" alt="<?php echo esc_attr__( 'Loading...', 'email-subscribers' ); ?>"/></span>
										<a class="-mt-1 mr-2 px-3 py-0.5 ig-es-primary-button cursor-pointer close-conditions" x-on:click=" <?php echo esc_attr( $sidebar_id ); ?> = false"><?php esc_html_e( 'Save', 'email-subscribers' ); ?></a>
										<a x-on:click=" <?php echo esc_attr( $sidebar_id ); ?> = false" class="-mt-1 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-white cursor-pointer">
											<span class="sr-only"><?php echo esc_html__( 'Close panel', 'email-subscribers' ); ?></span>
											<!-- Heroicon name: outline/x -->
											<svg class="h-6 w-6 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
											</svg>
										</a>
									</div>
								</div>
								<div class="mt-3 px-6 pb-6 relative flex-1 w-full">
									<?php
									$this->show_post_notification_fields( $campaign_data );
									?>
								</div>
							</div>
						</section>
					</div>
				</div>
			</div>
			<?php
		} else {
			$this->show_post_notification_fields( $campaign_data );
		}
	}

	/**
	 * Show post notification related fields
	 *
	 * Post categories etc.
	 *
	 * @since 5.1.0
	 *
	 * @param array $campaign_data
	 */
	public function show_post_notification_fields( $campaign_data ) {
		// We are storing both post categories and CPTs in one column 'categories'.
		$categories    = isset( $campaign_data['categories'] ) ? $campaign_data['categories'] : '';
		$cat_cpts      = ES_Common::convert_categories_string_to_array( $categories, true );
		$allowedtags   = ig_es_allowed_html_tags_in_esc();
		$campaign_type = ! empty( $campaign_data['type'] ) ? $campaign_data['type'] : '';
		$editor_type   = ! empty( $campaign_data['meta']['editor_type'] ) ? $campaign_data['meta']['editor_type'] : IG_ES_DRAG_AND_DROP_EDITOR;
		?>

		<div class="ig-es-campaign-categories-wrapper block mx-4 border-b border-gray-200 pt-4 pb-4">
			<div scope="row" class="pb-1 text-left">
				<label for="tag-link"><span class="block text-sm font-medium text-gray-600"><?php esc_html_e( 'Select post category', 'email-subscribers' ); ?></span></label>
			</div>
			<div style="vertical-align: top;">
				<table border="0" cellspacing="0" class="pt-3">
					<tbody>
					<?php
					$categories_lists = ES_Common::prepare_categories_html( $cat_cpts );
					echo wp_kses( $categories_lists, $allowedtags );
					?>
					</tbody>
				</table>
			</div>
		</div>


		<div class="ig-es-campaign-custom-post-type-wrapper border-b border-gray-100 mx-4 pt-4 pb-2">

			<div scope="row" class="text-left">
				<label for="tag-link"><span class="block pb-1 text-sm font-medium text-gray-600 pb-2">
								<?php esc_html_e( 'Select post type(s)', 'email-subscribers' ); ?></span>
				</label>
			</div>




			<div class="ig-es-cpt-filters">
				<table border="0" cellspacing="0">
					<tbody>
					<?php

					$selected_post_types = array();

					if ( ! empty( $cat_cpts ) ) {
						foreach ( $cat_cpts as $cat_cpt ) {
							// CPTs are stored in the 'categories' column with {T} prefix/suffix.
							$is_post_type = strpos( $cat_cpt, '{T}' ) !== false;
							if ( $is_post_type ) {
								$selected_post_types[] = str_replace( '{T}', '', $cat_cpt );
							}
						}
					}

					$custom_post_types = ES_Common::get_custom_post_types();
					$default_post_types = ES_Common::get_default_post_types();
					$post_types = array_merge( $custom_post_types, $default_post_types );
					if ( ! empty( $post_types ) ) {
						foreach ( $post_types as $post_type ) {
							$is_cpt_selected = in_array( $post_type, $selected_post_types, true );
							if ( $is_cpt_selected ) {
								$checked = 'checked="checked"';
							} else {
								$checked = '';
							}
							$post_type_object = get_post_type_object( $post_type );
							$post_type__name  = $post_type_object->labels->singular_name;
							?>
							<tr class="es-post-types-row<?php echo $is_cpt_selected ? ' checked' : ''; ?>">
								<td style="padding-top:4px;padding-bottom:4px;padding-right:10px;">
									<span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<input
											type="checkbox"
											id="es_custom_post_type_<?php echo esc_attr( $post_type ); ?>" name="campaign_data[es_note_cpt][]"
											value="<?php echo '{T}' . esc_html( $post_type ) . '{T}'; ?>"
											<?php echo esc_attr( $checked ); ?>
											class="es_custom_post_type form-checkbox"
											>
										<label for="es_custom_post_type_<?php echo esc_attr( $post_type ); ?>">
											<?php echo esc_html( $post_type__name ); ?>
										</label>
									</span>
									<?php
										do_action( 'ig_es_after_post_type_checkbox', $post_type, $campaign_data );
									?>
								</td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<span class="block pr-4 text-sm font-normal text-gray-600 pb-2">
								<?php echo esc_html__( 'No Custom Post Types Available', 'email-subscribers' ); ?>
							</span>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>



		</div>






		<?php
		if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type ) {
			do_action( 'ig_es_show_' . $campaign_type . '_fields', $campaign_data );
		}
	}

	/**
	 * Get default subject for post notification campaign
	 *
	 * @param string $subject
	 * @return string $subject
	 *
	 * @since 5.3.2
	 */
	public function get_post_notification_default_subject( $subject, $campaign_data ) {
		if ( empty( $subject ) ) {
			$subject = __( 'New Post Published - {{post.title}}', 'email-subscribers' );
		}
		return $subject;
	}

	public function get_post_notification_default_content( $content, $campaign_data ) {

		if ( empty( $content ) ) {
			$editor_type   = ! empty( $campaign_data['meta']['editor_type'] ) ? $campaign_data['meta']['editor_type'] : IG_ES_DRAG_AND_DROP_EDITOR;
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

			if ( $is_dnd_editor ) {
				$content = $this->get_dnd_editor_default_content();
			} else {
				$content = $this->get_classic_editor_default_content();
			}
		}

		return $content;
	}

	public function get_classic_editor_default_content() {
		$default_content  = __( "Hello {{subscriber.name | fallback='there'}},", 'email-subscribers' ) . "\r\n\r\n";
		$default_content .= __( 'We have published a new blog article on our website', 'email-subscribers' ) . " : {{post.title}}\r\n";
		$default_content .= "{{post.image}}\r\n\r\n";
		$default_content .= __( 'You can view it from this link', 'email-subscribers' ) . " : {{post.link}}\r\n\r\n";
		$default_content .= __( 'Thanks & Regards', 'email-subscribers' ) . ",\r\n";
		$default_content .= __( 'Admin', 'email-subscribers' ) . "\r\n\r\n";
		$default_content .= __( 'You received this email because in the past you have provided us your email address : {{subscriber.email}} to receive notifications when new updates are posted.', 'email-subscribers' );
		return $default_content;
	}

	public function get_dnd_editor_default_content() {

		$default_content = '<mjml>
			<mj-body>
				<mj-section background-color="#FFFFFF">
					<mj-column width="100%">
						<mj-image src="https://webstockreview.net/images/sample-png-images-14.png" height="70px"
								width="140px"/>
					</mj-column>
				</mj-section>
				<mj-section background-color="#FFFFFF">
					<mj-column width="100%">
						<mj-text line-height="26px">' . __( "Hello {{subscriber.name | fallback='there'}},", 'email-subscribers' ) . '</mj-text>
						<mj-text line-height="26px">' . __( 'We have published a new blog article on our website', 'email-subscribers' ) . ' : {{post.title}}</mj-text>
						<mj-text line-height="26px">{{post.image}}</mj-text>
						<mj-text line-height="26px">' . __( 'You can view it from this link', 'email-subscribers' ) . ' : {{post.link}}</mj-text>
					</mj-column>
				</mj-section>
				<mj-section background-color="#f3f3f3">
					<mj-column width="100%">
						<mj-text align="center" line-height="26px">@2022,' . __( 'Your Brand Name', 'email-subscribers' ) . '.</mj-text>
						<mj-text align="center" line-height="26px">' . __( 'You received this email because in the past you have provided us your email address : {{subscriber.email}} to receive notifications when new updates are posted.', 'email-subscribers' ) . __( 'If you wish to unsubscribe from our newsletter, click', 'email-subscribers' ) . ' <a data-gjs-type="link" href="{{UNSUBSCRIBE-LINK}}" >' . __( 'here', 'email-subscribers' ) . '</a>
						</mj-text>
					</mj-column>
				</mj-section>
			</mj-body>
		</mjml> ';

		return $default_content;
	}

}


ES_Post_Notifications_Table::get_instance();

