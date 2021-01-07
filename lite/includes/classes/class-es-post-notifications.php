<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Post_Notifications_Table {

	public static $instance;

	public function __construct() {

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
				$cat                = ( ! empty( $es_note_cat_parent ) && '{a}All{a}' == $es_note_cat_parent ) ? array( $es_note_cat_parent ) : $cat;

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
				$sql = $wpbd->prepare( $sql, $args );
			}
		}

		// Prepare Order by clause
		$order_by_clause = '';
		if ( ! empty( $order_by ) ) {
			$order_by_clause = ' ORDER BY ' . esc_sql( $order_by );
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
				if ( '{a}All{a}' === $parent_category_option ) {
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
				//check tempalte id
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
				<form class="ml-5 mr-4 text-left pt-4 mt-2 item-center" method="post" action="admin.php?page=es_notifications&action=<?php echo esc_attr( $action ); ?>&list=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>">

					<table class="max-w-full form-table">
						<tbody>

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
						<?php if ( ! $is_new ) { ?>
							<tr class="border-b border-gray-100">
								<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
									<label for="tag-link"><span class="block ml-6 pr-4 pt-2 text-sm font-medium text-gray-600 pb-2">
												<?php esc_html_e( 'Select Status', 'email-subscribers' ); ?>
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

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


