<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Post_Notifications_Table {

	static $instance;

	public function __construct() {

	}

	public function es_notifications_callback() {

		$action = Email_Subscribers::get_request( 'action' );

		?>
        <div class="wrap">
			<?php if ( 'new' === $action ) {
				$this->es_newnotification_callback();
			} elseif ( 'edit' === $action ) {
				$this->edit_list( absint( Email_Subscribers::get_request( 'list' ) ) );
			}
			?>
        </div>
		<?php
	}

	public function es_newnotification_callback() {

		$submitted = Email_Subscribers::get_request( 'submitted' );
		if ( 'submitted' === $submitted ) {

			$list_id     = Email_Subscribers::get_request( 'list_id' );
			$template_id = Email_Subscribers::get_request( 'template_id' );
			// $es_note_status = Email_Subscribers::get_request( 'es_note_status' );
			$cat = Email_Subscribers::get_request( 'es_note_cat' );

			if ( empty( $list_id ) ) {
				$message = __( 'Please select list.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
				$this->prepare_post_notification_form();

				return;
			}

			if ( empty( $template_id ) ) {
				$message = __( 'Please select template.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
				$this->prepare_post_notification_form();

				return;
			}

			if ( empty( $cat ) ) {
				$message = __( 'Please select categories.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
				$this->prepare_post_notification_form();

				return;
			}
			$type  = 'post_notification';
			$title = get_the_title( $template_id );
			$data  = array(
				'categories'       => ES_Common::convert_categories_array_to_string( $cat ),
				'list_ids'         => $list_id,
				'base_template_id' => $template_id,
				'status'           => 1,
				'type'             => $type,
				'name'             => $title,
				'slug'             => sanitize_title( $title )
			);

			$this->save_list( $data );
			$message = __( 'Post notification has been added successfully!', 'email-subscribers' );
			$this->show_message( $message, 'success' );
		}

		$this->prepare_post_notification_form();

	}

	public function custom_admin_notice() {
		if ( Email_Subscribers::get_request( 'es_note_cat' ) ) {
			echo '<div class="updated"><p>Notification Added Successfully!</p></div>';
		}
	}

	public function update_list( $id ) {

		global $wpdb;
		$cat  = Email_Subscribers::get_request( 'es_note_cat' );
		$data = array(
			'categories'       => ES_Common::convert_categories_array_to_string( $cat ),
			'list_ids'         => Email_Subscribers::get_request( 'list_id' ),
			'base_template_id' => Email_Subscribers::get_request( 'template_id' ),
			'status'           => 'active'
		);
		$wpdb->update( IG_CAMPAIGNS_TABLE, $data, array( 'id' => $id ) );

	}

	public function save_list( $data, $id = null ) {
		return ES_DB_Campaigns::save_campaign( $data, $id );
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

		global $wpdb;

		$order_by = Email_Subscribers::get_request( 'orderby' );
		$order    = Email_Subscribers::get_request( 'order' );
		$search   = Email_Subscribers::get_request( 's' );

		$add_where_clause = false;
		$sql              = "SELECT * FROM " . IG_CAMPAIGNS_TABLE;
		$args             = array();
		$query            = array();

		if ( ! empty( $search ) ) {
			$add_where_clause = true;
			$query[]          = " name LIKE %s ";
			$args[]           = "%" . $wpdb->esc_like( $search ) . "%";
		}

		if ( $add_where_clause ) {
			$sql .= " WHERE ";

			if ( count( $query ) > 0 ) {
				$sql .= implode( " AND ", $query );
				$sql = $wpdb->prepare( $sql, $args );
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

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;

	}

	public function edit_list( $id ) {

		global $wpdb;

		$notifications = $wpdb->get_results( "SELECT * FROM " . IG_CAMPAIGNS_TABLE . " WHERE id = $id LIMIT 0, 1", ARRAY_A );

		$submitted = Email_Subscribers::get_request( 'submitted' );
		if ( 'submitted' === $submitted ) {

			$categories   = Email_Subscribers::get_request( 'es_note_cat' );

			$data         = array(
				'categories'       => ES_Common::convert_categories_array_to_string( $categories ),
				'list_ids'         => Email_Subscribers::get_request( 'list_id' ),
				'base_template_id' => Email_Subscribers::get_request( 'template_id' ),
				'status'           => Email_Subscribers::get_request( 'status' )
			);

			$title = '';
			if(!empty($data['base_template_id'])) {
			    $title        = get_the_title( $data['base_template_id'] );
            }

			$data['name'] = $title;

			$this->save_list( $data, $id );

			$data['categories'] = ES_Common::convert_categories_string_to_array($data['categories']);
			$message = __( 'Post notification has been updated successfully!', 'email-subscribers' );
			$this->show_message( $message, 'success' );
		} else {

			$notification = array_shift( $notifications );
			$id           = $notification['id'];

			$categories_str = ! empty( $notification['categories'] ) ? $notification['categories'] : '';
			$categories     = ES_Common::convert_categories_string_to_array( $categories_str );

			$data           = array(
				'categories'       => $categories,
				'list_ids'         => $notification['list_ids'],
				'base_template_id' => $notification['base_template_id'],
				'status'           => $notification['status']
			);
		}

		$this->prepare_post_notification_form( $id, $data );

	}

	public static function prepare_post_notification_form( $id = '', $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action  = 'new';
		$heading = __( 'Campaigns > New Post Notification', 'email-subscribers' );
		if ( ! $is_new ) {
			$action  = 'edit';
			$heading = __( 'Campigns > Edit Post Notification', 'email-subscribers' );
		}

		$cat         = isset( $data['categories'] ) ? $data['categories'] : '';
		$list_id     = isset( $data['list_ids'] ) ? $data['list_ids'] : '';
		$template_id = isset( $data['base_template_id'] ) ? $data['base_template_id'] : '';
		$status      = isset( $data['status'] ) ? $data['status'] : '';
		$nonce       = wp_create_nonce( 'es_post_notification' );

		?>

        <div class="wrap">
            <h2 class="wp-heading-inline"><?php echo $heading; ?>
                <a href="admin.php?page=es_campaigns" class="page-title-action"><?php _e( 'Campaigns', 'email-subscribers' ) ?></a>
				<?php if ( $action === 'edit' ) { ?>
                    <a href="admin.php?page=es_notifications&action=new" class="page-title-action">Add New</a>
                <?php } ?>
                <a href="edit.php?post_type=es_template" class="page-title-action es-imp-button"><?php _e( 'Manage Templates', 'email-subscribers' ) ?></a>
            </h2>
            <hr class="wp-header-end">
            <div class="meta-box-sortables ui-sortable" style="width: 80%;display:inline;float:left">
                <form method="post" action="admin.php?page=es_notifications&action=<?php echo $action; ?>&list=<?php echo $id; ?>&_wpnonce=<?php echo $nonce; ?>">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="tag-link"><?php _e( 'Select List', 'email-subscribers' ); ?></label>
                                <p class="helper"><?php _e( 'Contacts from the selected list will be notified about new post notification.', 'email-subscribers' ); ?></p>
                            </th>
                            <td>
                                <select name="list_id" id="list_id">
									<?php echo ES_Common::prepare_list_dropdown_options( $list_id ); ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tag-link">
									<?php _e( 'Select template', 'email-subscribers' ); ?>
                                    <p class="helper"><?php _e( 'Content of the selected template will be sent out as post notification.', 'email-subscribers' ); ?></p>
                                </label>
                            </th>
                            <td>
                                <select name="template_id" id="base_template_id">
									<?php echo ES_Common::prepare_templates_dropdown_options( 'post_notification', $template_id ); ?>
                                </select>
                            </td>
                        </tr>
						<?php if ( ! $is_new ) { ?>
                            <tr>
                                <th scope="row">
                                    <label for="tag-link">
										<?php _e( 'Select Status', 'email-subscribers' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="status" id="status">
										<?php echo ES_Common::prepare_status_dropdown_options( $status ); ?>
                                    </select>
                                </td>
                            </tr>
						<?php } ?>
                        <tr>
                            <th scope="row">
                                <label for="tag-link"><?php _e( 'Select Post Category', 'email-subscribers' ); ?></label>
                                <p class="helper"><?php _e( 'Notification will be sent out when any post from selected categories will be published.', 'email-subscribers' ); ?></p>
                            </th>
                            <td>
                                <table border="0" cellspacing="0">
                                    <tbody>
									<?php echo ES_Common::prepare_categories_html( $cat ); ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tag-link">
									<?php _e( 'Select custom post type(s)', 'email-subscribers' ); ?>
                                    <p class="helper"><?php _e( '(Optional) Select custom post type for which you want to send notification.', 'email-subscribers' ); ?></p>
                                </label>
                            </th>
                            <td>
                                <table border="0" cellspacing="0">
                                    <tbody>
									<?php $custom_post_type =''; echo ES_Common::prepare_custom_post_type_checkbox( $cat  ); ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="hidden" name="submitted" value="submitted"></td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="row-blog">
                        <div class="leftside">
							<?php echo get_submit_button(); ?>
                        </div>
                    </div>
                </form>
            </div>
            <div clas="es-preview" style="float: right;width: 19%;">
                <div class="es-templ-img"></div>
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
			'bulk_delete' => 'Delete'
		);

		return $actions;
	}

	public function search_box( $text, $input_id ) { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( 'Search Notifications', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
		<?php
	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}


	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


