<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Subscribers_Table extends WP_List_Table {

	static $instance;

	public function __construct() {

		//set_error_handler(array( 'Email_General' , 'es_handle_error'));
		parent::__construct( array(
			'singular' => __( 'Contact', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Contacts', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false,//does this table support ajax?
			'screen'   => 'es_subscribers'
		) );


		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
	}

	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_settings_page() {

		?>
        <div class="wrap">

		<?php

		$action = Email_Subscribers::get_request( 'action' );
		if ( 'import' === $action ) {
			$this->load_import();
		} elseif ( 'export' === $action ) {
			$this->load_export();
		} elseif ( 'new' === $action ) {
			$this->es_newsubscriber_callback();
		} elseif ( 'edit' === $action ) {
			echo $this->edit_list( absint( Email_Subscribers::get_request( 'subscriber' ) ) );
		} elseif ( 'sync' === $action ) {
			$this->load_sync();
		} else { ?>
            <h1 class="wp-heading-inline"><?php _e( 'Audience > Contacts', 'email-subscribers' ); ?>
                <a href="admin.php?page=es_subscribers&action=new" class="page-title-action"><?php _e( 'Add New Contact', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_subscribers&action=export" class="page-title-action"><?php _e( 'Export Contacts', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_subscribers&action=import" class="page-title-action"><?php _e( 'Import Contacts', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_subscribers&action=sync" class="page-title-action"><?php _e( 'Sync', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_lists&action=manage-lists" class="page-title-action es-imp-button"><?php _e( 'Manage Lists', 'email-subscribers' ); ?></a>
				<?php
				do_action( 'es_after_action_buttons' );
				?>
            </h1>
			<?php Email_Subscribers_Admin::es_feedback(); ?>
            <div id="poststuff" class="es-audience-view">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
								<?php
								$this->prepare_items();
								$this->display();
								?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
            </div>
		<?php }
	}

	public function load_export() {
		$export = new Export_Subscribers();
		$export->export_subscribers_page();
	}

	public function load_import() {
		$import = new ES_Import_Subscribers();
		$import->import_subscribers_page();
	}

	public function load_sync() {
		$sync = new ES_Handle_Sync_Wp_User();
		$sync->prepare_sync_user();
	}

	public function manage_lists() {
		$list = ES_Lists_Table::get_instance();
		$list->es_lists_callback();
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Contacts', 'email-subscribers' ),
			'default' => 150,
			'option'  => 'subscribers_per_page'
		);

		add_screen_option( $option, $args );

	}


	public function es_newsubscriber_callback() {
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Add New Contact', 'email-subscribers' ); ?>
                <a href="admin.php?page=es_lists&action=manage-lists" class="page-title-action es-imp-button"><?php _e( 'Manage Lists', 'email-subscribers' ); ?></a>
            </h1>
			<?php Email_Subscribers_Admin::es_feedback(); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable es-contact-form">
							<?php echo $this->prepare_contact_form(); ?>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>

		<?php
		global $wpdb;

		if ( Email_Subscribers::get_request( 'email' ) ) {


			$list_id = Email_Subscribers::get_request( 'lists' );

			if ( empty( $list_id ) ) {
				$message = __( 'Please Select List', 'email-subscribers' );
				$this->show_message( $message, 'error' );

				return '';
			}
			$email = Email_Subscribers::get_request( 'email' );
			$data  = array(
				'first_name' => Email_Subscribers::get_request( 'subscriber_name' ),
				'email'      => $email,
				'source'     => 'admin',
				'status'     => 'verified',
				'hash'       => ES_Common::generate_guid(),
				'created_at' => ig_get_current_date_time(),
			);
			$check = ES_DB_Contacts::is_subscriber_exist_in_list( $email, $list_id );
			if ( empty( $check['contact_id'] ) ) {
				$added = ES_DB_Contacts::add_subscriber( $data );
			} else {
				$added = $check['contact_id'];
			}
			if ( empty( $check['list_id'] ) ) {
				$optin_type_option = get_option( 'ig_es_optin_type', true );
				if ( in_array( $optin_type_option, array( 'double_opt_in', 'double_optin' ) ) ) {
					$optin_type = 2;
				} else {
					$optin_type = 1;
				}
				$list_id           = ! empty( $list_id ) ? $list_id : 1;
				$list_contact_data = array(
					'list_id'       => $list_id,
					'contact_id'    => $added,
					'status'        => 'subscribed',
					'subscribed_at' => ig_get_current_date_time(),
					'optin_type'    => $optin_type,
					'subscribed_ip' => null
				);

				$result = ES_DB_Lists_Contacts::add_lists_contacts( $list_contact_data );
				if ( $added ) {
					$message = __( 'Contact has been added successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				}
			} else {
				$message = __( 'Contact already exist.', 'email-subscribers' );
				$this->show_message( $message, 'success' );
			}

			return '';
		} else {
			return false;
		}
	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}

	/**
	 * Retrieve subscribers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_subscribers( $per_page = 5, $page_number = 1, $do_count_only = false ) {
		global $wpdb;

		$order_by          = Email_Subscribers::get_request( 'orderby' );
		$order             = Email_Subscribers::get_request( 'order' );
		$search            = Email_Subscribers::get_request( 's' );
		$filter_by_list_id = Email_Subscribers::get_request( 'filter_by_list_id' );
		$filter_by_status  = Email_Subscribers::get_request( 'filter_by_status' );

		$add_where_clause = false;

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM " . IG_CONTACTS_TABLE;
		} else {
			$sql = "SELECT * FROM " . IG_CONTACTS_TABLE;
		}

		$args  = array();
		$query = array();

		// Prepare filter by list query
		if ( ! empty( $filter_by_list_id ) || ! empty( $filter_by_status ) ) {
			$add_where_clause = true;
			$list_sql         = "SELECT contact_id FROM " . IG_LISTS_CONTACTS_TABLE;
			$filter_sql       = ! empty( $filter_by_list_id ) ? " list_id = $filter_by_list_id " : ' ';
			$filter_sql       .= ! empty( $filter_by_status ) ? ( ( ! empty( $filter_by_list_id ) ) ? "AND status = '$filter_by_status' " : " status = '$filter_by_status' " ) : ' ';
			$list_sql         = ! empty( $filter_sql ) ? $list_sql . " WHERE " . $filter_sql : $list_sql;
			$query[]          = "id IN ( $list_sql )";
		}

		// Prepare search query
		if ( ! empty( $search ) ) {
			$add_where_clause = true;
			$query[]          = " ( first_name LIKE %s OR email LIKE %s ) ";
			$args[]           = "%" . $wpdb->esc_like( $search ) . "%";
			$args[]           = "%" . $wpdb->esc_like( $search ) . "%";
		}

		if ( $add_where_clause ) {
			$sql .= " WHERE ";

			if ( count( $query ) > 0 ) {
				$sql .= implode( " AND ", $query );
				if ( ! empty( $args ) ) {
					$sql = $wpdb->prepare( $sql, $args );
				}
			}
		}


		if ( ! $do_count_only ) {

			// Prepare Order by clause
			$order_by_clause = '';
			$order           = ! empty( $order ) ? ' ' . esc_sql( $order ) : ' DESC';
			$order_by_clause = ' ORDER BY ' . esc_sql( 'created_at' ) . ' ' . $order;
			$order_by_clause = ! empty( $order_by ) ? $order_by_clause . ' , ' . esc_sql( $order_by ) . ' ' . $order : $order_by_clause;

			$sql .= $order_by_clause;
			$sql .= " LIMIT $per_page";
			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		} else {
			$result = $wpdb->get_var( $sql );
		}

		return $result;
	}

	public function edit_list( $id ) {
		global $wpdb;

		$notificationid = $wpdb->get_results( "SELECT * FROM " . IG_CONTACTS_TABLE . " WHERE id = $id" );

		$title         = $notificationid[0]->first_name . ' ' . $notificationid[0]->last_name;
		$email         = $notificationid[0]->email;
		$contact_lists = ES_DB_Lists_Contacts::get_list_ids_by_contact( $notificationid[0]->id );
		if ( 'updated' === Email_Subscribers::get_request( 'status' ) ) {

			$email_address = Email_Subscribers::get_request( 'email' );
			if ( ! empty( $email_address ) ) {
				$this->update_list( $id );
				$title         = Email_Subscribers::get_request( 'subscriber_name' );
				$contact_lists = Email_Subscribers::get_request( 'lists' );
				$email         = $email_address;
			}
		}

		$updated = '';
		// $status_options_html = ES_Common::prepare_statuses_dropdown_options( $status );
		$id      = $notificationid[0]->id;
		$guid    = $notificationid[0]->hash;
		$created = $notificationid[0]->created_at;
		$nonce   = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

		$data = array(
			'id'                => $id,
			'action'            => "admin.php?page=es_subscribers&action=edit&subscriber={$id}&_wpnonce={$nonce}&status=updated",
			'name'              => $title,
			'email'             => $email,
			'created'           => $created,
			'guid'              => $guid,
			'selected_list_ids' => $contact_lists
		);

		if ( Email_Subscribers::get_request( 'subscriber_name' ) ) {
			$message = __( 'Contact updated successfully!', 'email-subscribers' );
			$this->show_message( $message, 'success' );
		}

		$editform = '<div class="wrap">
            <h1 class="wp-heading-inline">' . __( 'Edit Contact', 'email-subscribers' ) . '<a href="admin.php?page=es_subscribers&action=new" class="page-title-action">Add New</a></h1>' . Email_Subscribers_Admin::es_feedback() . '
            <hr class="wp-header-end">
            <div id="poststuff">' . $updated . '
            <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable es-contact-form">'
		            . $this->prepare_contact_form( $data ) .
		            '</div>
                    </div>
                </div>
            </div>
        </div>';

		return $editform;
	}

	public function prepare_contact_form( $data = array() ) {

		$id           = ! empty( $data['id'] ) ? $data['id'] : '';
		$created           = ! empty( $data['created'] ) ? $data['created'] : '';
		$guid              = ! empty( $data['guid'] ) ? $data['guid'] : '';
		$action            = ! empty( $data['action'] ) ? $data['action'] : '#';
		$name              = ! empty( $data['name'] ) ? $data['name'] : '';
		$email             = ! empty( $data['email'] ) ? $data['email'] : '';
		$selected_list_ids = ! empty( $data['selected_list_ids'] ) ? $data['selected_list_ids'] : array();

		$lists_id_name_map = ES_DB_Lists::get_list_id_name_map();

		if ( count( $lists_id_name_map ) ) {
			$list_html = ES_Shortcode::prepare_lists_checkboxes( $lists_id_name_map, array_keys( $lists_id_name_map ), 4, $selected_list_ids, $id );
		} else {
			$list_html = "<tr><td>" . __( 'No list found', 'email-subscribers' ) . "</td></tr>";
		}

		$form = '<form method="post" action="' . $action . '">
                    <table class="form-table">
                        <tbody>
                            <tr class="form-field">
                                <td><label><b>' . __( 'Name', 'email-subscribers' ) . '</b></label></td>
                                <td><input type="text" id="name" name="subscriber_name" value="' . $name . '"/></td>
                            </tr>
                            <tr class="form-field">
                                <td><label><b>' . __( 'Email', 'email-subscribers' ) . '</b></label></td>
                                <td><input type="email" id="email" name="email" value="' . $email . '"/></td>
                            </tr>
                            <tr class="form-field">
                                <td><label><b>' . __( 'List(s)', 'email-subscribers' ) . '</b></label></td>
                                <td>
                                    <table>' . $list_html . '</table>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <td></td>
                                <td>
                                    <input type="hidden" name="created_on" value="' . $created . '" />
						            <input type="hidden" name="guid" value="' . $guid . '" />
                                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>';

		return $form;
	}

	public function update_list( $id ) {

		global $wpdb;

		$email = Email_Subscribers::get_request( 'email' );
		$name  = Email_Subscribers::get_request( 'subscriber_name' );

		if ( ! empty( $email ) ) {

			$name_parts = ES_Common::prepare_first_name_last_name( $name );
			$first_name = $name_parts['first_name'];
			$last_name  = $name_parts['last_name'];

			$data = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'      => $email,
				'updated_at' => ig_get_current_date_time()
			);

			$wpdb->update( IG_CONTACTS_TABLE, $data, array( 'id' => $id ) );
			$list_ids = Email_Subscribers::get_request( 'lists' );

			if ( count( $list_ids ) > 0 ) {
				ES_DB_Lists_Contacts::update_list_contacts( $id, $list_ids );
			}
		}
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_CONTACTS_TABLE;

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no subscriber data is available */


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		$item = apply_filters( 'es_subscribers_col_data', $item, $column_name );
		switch ( $column_name ) {
			case 'lists':
				return $this->get_lists_to_show( $item['id'] );
			//implode( ", ", ES_DB_Lists::get_all_lists_name_by_contact( $item['id'] ) );
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
			case 'first_name':
			case 'email':
			default:
				return $item[ $column_name ]; //Show the whole array for troubleshooting purposes
		}
	}

	public function get_lists_to_show( $contact_id ) {
		$contact_lists = ES_DB_Lists_Contacts::get_list_details_by_contact( $contact_id );
		$list_str      = '';
		if ( count( $contact_lists ) > 0 ) {
			$contact_lists_to_display = array_slice( $contact_lists, 0, 4 );
			$list_id_name_map         = ES_DB_Lists::get_list_id_name_map();

			foreach ( $contact_lists_to_display as $contact_list ) {
				if(!empty( $list_id_name_map[ $contact_list['list_id'] ] )){
					$list_str .= '<span class="es_list_contact_status ' . strtolower($contact_list['status']) . '" title="' . ucwords( $contact_list['status'] ) . '">' . $list_id_name_map[ $contact_list['list_id'] ] . '</span> ';
				}
			}
		}

		return $list_str;
	}

	public function status_label_map( $status ) {

		$statuses = array(
			// 'confirmed'     => __( 'Confirmed', 'email-subscribers' ),
			'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
			'unconfirmed'  => __( 'Unconfirmed', 'email-subscribers' ),
			'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' ),
			// 'single_opt_in' => __( 'Single Opt In', 'email-subscribers' ),
			// 'double_opt_in' => __( 'Double Opt In', 'email-subscribers' )
		);


		if ( ! in_array( $status, array_keys( $statuses ) ) ) {
			return '';
		}

		return $statuses[ $status ];
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="subscribers[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {
		$delete_nonce = wp_create_nonce( 'sp_delete_subscriber' );

		$name  = ES_Common::prepare_name_from_first_name_last_name( $item['first_name'], $item['last_name'] );
		$title = '<strong>' . $name . '</strong>';

		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&subscriber=%s&_wpnonce=%s">Edit</a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'edit', absint( $item['id'] ), $delete_nonce ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&subscriber=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'delete', absint( $item['id'] ), $delete_nonce ),
		);

		$optin_type = get_option( 'ig_es_optin_type' );

		//if ( in_array( $optin_type, array( 'double_optin', 'double_opt_in' ) ) ) {
		$actions['resend'] = sprintf( '<a href="?page=%s&action=%s&subscriber=%s&_wpnonce=%s">Resend<a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'resend', absint( $item['id'] ), $delete_nonce );
		//}

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name', 'email-subscribers' ),
			'email'      => __( 'Email', 'email-subscribers' ),
			'lists'      => __( 'List(s)', 'email-subscribers' ),
			'created_at' => __( 'Created', 'email-subscribers' ),
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'first_name' => array( 'first_name', true ),
			'email'      => array( 'email', false ),
			// 'status'     => array( 'status', false ),
			'created_at' => array( 'created_at', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk_delete'        => __( 'Delete', 'email-subscribers' ),
			'bulk_list_update'   => __( 'Move To List', 'email-subscribers' ),
			'bulk_status_update' => __( 'Change Status', 'email-subscribers' )
		);

		return $actions;
	}


	public function search_box( $text, $input_id ) { ?>
        <p class="search-box box-ma10">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( 'Search Contacts', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
        <p class="search-box search-group-box box-ma10">
			<?php $filter_by_status = Email_Subscribers::get_request( 'filter_by_status' ); ?>
            <select name="filter_by_status">
				<?php echo ES_Common::prepare_statuses_dropdown_options( $filter_by_status, 'All Status' ); ?>
            </select>
        </p>
        <p class="search-box search-group-box box-ma10">
			<?php $filter_by_list_id = Email_Subscribers::get_request( 'filter_by_list_id' ); ?>
            <select name="filter_by_list_id">
				<?php echo ES_Common::prepare_list_dropdown_options( $filter_by_list_id, 'All Lists' ); ?>
            </select>
        </p>

	<?php }


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();
		$this->search_box( Email_Subscribers::get_request( 's' ), 'subscriber-search-input' );
		$this->edit_group();
		$this->edit_status();

		$per_page     = $this->get_items_per_page( 'subscribers_per_page', 200 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_subscribers( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_subscribers( $per_page, $current_page );
	}

	public function edit_group() {
		$data = '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="list_id" id="list_id" class="groupsselect" style="display: none">';
		$data .= ES_Common::prepare_list_dropdown_options();
		$data .= '</select>';

		echo $data;
	}

	public function edit_status() {
		$data = '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="status_select" id="status_select" class="statusesselect" style="display:none;">';
		$data .= ES_Common::prepare_statuses_dropdown_options();
		$data .= '</select>';

		echo $data;
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...

		if ( 'edit' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_subscriber' ) ) {
				die( 'You do not have a permission to delete subscriber' );
			} else {
				$this->edit_list( absint( Email_Subscribers::get_request( 'subscriber' ) ) );
				$message = __( 'Contact have been updated successfully!', 'email-subscribers' );
				$this->show_message( $message, 'success' );

				return;
			}
		}

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_subscriber' ) ) {
				die( 'You do not have a permission to delete contact(s)' );
			} else {
				$deleted = ES_DB_Contacts::delete_subscribers( array( absint( Email_Subscribers::get_request( 'subscriber' ) ) ) );
				if ( $deleted ) {
					$message = __( 'Contact(s) have been deleted successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				}

				return;
			}

		}

		if ( 'resend' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );
			if ( ! wp_verify_nonce( $nonce, 'sp_delete_subscriber' ) ) {
				die( 'You do not have a permission to resend email confirmation' );
			} else {
				$id            = absint( Email_Subscribers::get_request( 'subscriber' ) );
				$subscriber    = ES_DB_Contacts::get_subscribers_by_id( $id );
				$template_data = array(
					'email' => $subscriber['email'],
					'db_id' => $subscriber['id'],
					'name'  => $subscriber['first_name'],
					'guid'  => $subscriber['hash']
				);

				$subject  = get_option( 'ig_es_confirmation_mail_subject', true );
				$content  = ES_Mailer::prepare_double_optin_email( $template_data );
				$response = ES_Mailer::send( $subscriber['email'], $subject, $content );

				if ( $response ) {
					return true;
				}

				return false;

			}

		}

		$action  = Email_Subscribers::get_request( 'action' );
		$action2 = Email_Subscribers::get_request( 'action2' );

		$actions = array( 'bulk_delete', 'bulk_status_update', 'bulk_list_update' );
		if ( in_array( $action, $actions ) || in_array( $action2, $actions ) ) {

			$subscriber_ids = esc_sql( Email_Subscribers::get_request( 'subscribers' ) );
			if ( empty( $subscriber_ids ) ) {
				$message = __( 'Please select subscribers to update.', 'email-subscribers' );
				$this->show_message( $message, 'error' );

				return;
			}

			// If the delete bulk action is triggered
			if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

				$deleted = ES_DB_Contacts::delete_subscribers( $subscriber_ids );

				if ( $deleted ) {
					$message = __( 'Contact(s) have been deleted successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				}

				return;
			}

			if ( ( 'bulk_status_update' === $action ) || ( 'bulk_status_update' === $action2 ) ) {
				$status = Email_Subscribers::get_request( 'status_select' );

				if ( empty( $status ) ) {
					$message = __( 'Please select status.', 'email-subscribers' );
					$this->show_message( $message, 'error' );

					return;
				}

				// loop over the array of record IDs and delete them
				$edited = ES_DB_Contacts::edit_subscriber_status( $subscriber_ids, $status );

				if ( $edited ) {
					$message = __( 'Status has been changed successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				}

				return;
			}

			if ( ( 'bulk_list_update' === $action ) || ( 'bulk_list_update' === $action2 ) ) {

				$list_id = Email_Subscribers::get_request( 'list_id' );
				if ( empty( $list_id ) ) {
					$message = __( 'Please select list.', 'email-subscribers' );
					$this->show_message( $message, 'error' );

					return;
				}

				$edited = ES_DB_Contacts::edit_subscriber_group( $subscriber_ids, $list_id );

				if ( $edited ) {
					$message = __( 'Contact(s) have been moved to list successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				}

				return;
			}
		}
	}

	public function no_items() {
		_e( 'No contacts avaliable.', 'email-subscribers' );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}