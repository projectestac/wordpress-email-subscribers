<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Lists_Table extends WP_List_Table {

	static $instance;

	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'List', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Lists', 'sp' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?,
			'screen'   => 'es_lists'
		) );

		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
	}

	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function es_lists_callback() {

		$action = Email_Subscribers::get_request( 'action' );
		?>
        <div class="wrap">
		<?php if ( 'new' === $action ) {
			$this->es_new_lists_callback();
		} elseif ( 'edit' === $action ) {
			echo $this->edit_list( absint( Email_Subscribers::get_request( 'list' ) ) );
		} else { ?>
            <h1 class="wp-heading-inline"><?php _e( 'Audience > Lists', 'email-subscribers' ); ?> <a href="admin.php?page=es_lists&action=new" class="page-title-action">Add New</a></h1>
			<?php Email_Subscribers_Admin::es_feedback(); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
								<?php
								$this->prepare_items();
								$this->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
            </div>
		<?php }
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Lists', 'email-subscribers' ),
			'default' => 10,
			'option'  => 'lists_per_page'
		);

		add_screen_option( $option, $args );

	}

	public function validate_data( $data ) {

		$nonce     = $data['nonce'];
		$list_name = $data['list_name'];

		$status  = 'error';
		$message = '';
		if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
			$message = __( 'You do not have permission to edit list', 'email-subscribers' );
		} elseif ( empty( $list_name ) ) {
			$message = __( 'Please add list name', 'email-subscribers' );
		} else {
			$status = 'success';
		}

		$response = array(
			'status'  => $status,
			'message' => $message
		);

		return $response;

	}

	public function es_new_lists_callback() {

		$submitted = Email_Subscribers::get_request( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );
			$list_name = Email_Subscribers::get_request( 'list_name' );

			$validate_data = array(
				'nonce'     => $nonce,
				'list_name' => $list_name,
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				$this->show_message( $message, 'error' );
				$this->prepare_list_form( null, $validate_data );

				return;
			}

			$data = array(
				'list_name' => $list_name,
			);

			$this->save_list( null, $data );
			$message = __( 'List has been added successfully!', 'email-subscribers' );
			$this->show_message( $message, 'success' );
		}

		$this->prepare_list_form();
	}


	public function edit_list( $id ) {
		global $wpdb;

		$list = $wpdb->get_results( "SELECT * FROM " . IG_LISTS_TABLE . " WHERE id = $id" );

		$submitted = Email_Subscribers::get_request( 'submitted' );


		if ( 'submitted' === $submitted ) {

			$nonce     = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );
			$list_name = Email_Subscribers::get_request( 'list_name' );

			$validate_data = array(
				'nonce'     => $nonce,
				'list_name' => $list_name,
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				$this->show_message( $message, 'error' );
				$this->prepare_list_form( $id, $validate_data );

				return;
			}

			$data = array(
				'list_name' => $list_name,
			);

			$this->save_list( $id, $data );
			$message = __( 'List has been updated successfully!', 'email-subscribers' );
			$this->show_message( $message, 'success' );
		} else {
			$id = $list[0]->id;

			$data = array(
				'list_name' => $list[0]->name,
			);

		}

		$this->prepare_list_form( $id, $data );

		?>

		<?php
	}

	public function prepare_list_form( $id = 0, $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action = 'new';
		if ( ! $is_new ) {
			$action = 'edit';
		}

		$list_name = isset( $data['list_name'] ) ? $data['list_name'] : '';

		$nonce = wp_create_nonce( 'es_list' );

		?>

        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php
				if ( $is_new ) {
					_e( 'Add New', 'email-subscribers' );
				} else {
					_e( 'Edit List', 'email-subscribers' );
				}

				?>
                <a href="admin.php?page=es_lists&action=manage-lists" class="page-title-action es-imp-button"><?php _e( 'Manage Lists', 'email-subscribers' ); ?></a>
            </h1>

			<?php Email_Subscribers_Admin::es_feedback(); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post" action="admin.php?page=es_lists&action=<?php echo $action; ?>&list=<?php echo $id; ?>&_wpnonce=<?php echo $nonce; ?>">
                                <div class="row-blog">
                                    <label><?php _e( 'Name', 'email-subscribers' ); ?>: </label>
                                    <input type="text" id="name" name="list_name" value="<?php echo $list_name; ?>"/>
                                </div>
                                <input type="hidden" name="submitted" value="submitted"/>
                                <div class="row-blog"><?php submit_button(); ?></div>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>

		<?php

	}

	public function save_list( $id, $data ) {

		global $wpdb;

		$list_data['name']       = $data['list_name'];
		$list_data['slug']       = sanitize_title( $list_data['name'] );
		$list_data['created_at'] = ig_get_current_date_time();

		if ( ! empty( $id ) ) {
			$return = $wpdb->update( IG_LISTS_TABLE, $list_data, array( 'id' => $id ) );
		} else {
			$return = $wpdb->insert( IG_LISTS_TABLE, $list_data );
		}

		return $return;
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		global $wpdb;

		$order_by = Email_Subscribers::get_request( 'orderby' );
		$order    = Email_Subscribers::get_request( 'order' );
		$search   = Email_Subscribers::get_request( 's' );

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM " . IG_LISTS_TABLE;
		} else {
			$sql = "SELECT * FROM " . IG_LISTS_TABLE;
		}

		$args = $query = array();

		$add_where_clause = true;

		$query[] = "( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' )";

		if ( ! empty( $search ) ) {
			$query[] = " name LIKE %s ";
			$args[]  = "%" . $wpdb->esc_like( $search ) . "%";
		}

		if ( $add_where_clause ) {
			$sql .= " WHERE ";

			if ( count( $query ) > 0 ) {
				$sql .= implode( " AND ", $query );
				if ( count( $args ) > 0 ) {
					$sql = $wpdb->prepare( $sql, $args );
				}
			}
		}

		if ( ! $do_count_only ) {

			// Prepare Order by clause
			$order_by_clause = '';
			$order           = ! empty( $order ) ? ' ' . esc_sql( $order ) : ' DESC';
			//$order_by_clause = ' ORDER BY ' . esc_sql( 'created_at' ) . ' ' . $order;
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


	/**
	 * Delete a list record.
	 *
	 * @param int $id list ID
	 */
	public function delete_list( $ids ) {
		global $wpdb;

		$ids          = implode( ',', array_map( 'absint', $ids ) );
		$current_date = ig_get_current_date_time();
		$query        = "UPDATE " . IG_LISTS_TABLE . " SET deleted_at = %s WHERE id IN ($ids)";
		$query        = $wpdb->prepare( $query, array( $current_date ) );
		$wpdb->query( $query );
		$del_query = "DELETE FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE list_id IN ($ids)";
		// $del_query = $wpdb->prepare( $del_query, array($ids) );
		$wpdb->query( $del_query );
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_LISTS_TABLE;

		return $wpdb->get_var( $sql );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {


			case 'active_contacts':
				return ES_DB_Lists_Contacts::get_total_count_by_list( $item['id'], 'active' );
				break;
			case 'all_contacts':
				return ES_DB_Lists_Contacts::get_total_count_by_list( $item['id'], 'all' );
				break;
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
				break;
			default:
				return '';
		}
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
			'<input type="checkbox" name="lists[]" value="%s" />', $item['id']
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

		$list_nonce = wp_create_nonce( 'es_list' );

		$title   = '<strong>' . $item['name'] . '</strong>';
		$actions = array();
		if ( $item['id'] != 1 ) {
			$actions = array(
				'edit'   => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">Edit</a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'edit', absint( $item['id'] ), $list_nonce ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'delete', absint( $item['id'] ), $list_nonce )
			);
		}

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'name'            => __( 'Name', 'email-subscribers' ),
			'active_contacts' => __( 'Active Contacts', 'email-subscribers' ),
			'all_contacts'    => __( 'All Contacts', 'email-subscribers' ),
			'created_at'      => __( 'Created', 'email-subscribers' )
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
			'name' => array( 'name', true ),
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
			'bulk_delete' => __( 'Delete', 'email-subscribers' )
		);

		return $actions;
	}

	public function search_box( $text, $input_id ) { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( 'Search Lists', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
	<?php }

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();


		/** Process bulk action */
		$this->process_bulk_action();
		$this->search_box( Email_Subscribers::get_request( 's' ), 'list-search-input' );

		$per_page     = $this->get_items_per_page( 'lists_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'edit' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
				$message = __( 'You do not have permission to edit list', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			} else {
				$this->edit_list( absint( Email_Subscribers::get_request( 'list' ) ) );
				$message = __( 'List has been updated successfully!', 'email-subscribers' );
				$this->show_message( $message, 'success' );
			}

		}

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
				$message = __( 'You do not have permission to delete list', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			} else {
				if ( Email_Subscribers::get_request( 'list' ) != 1 ) {
					$this->delete_list( array( absint( Email_Subscribers::get_request( 'list' ) ) ) );
					$message = __( 'List has been deleted successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				}
			}
		}

		$action  = Email_Subscribers::get_request( 'action' );
		$action2 = Email_Subscribers::get_request( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$lists = esc_sql( Email_Subscribers::get_request( 'lists' ) );

			if ( ! empty( $lists ) > 0 ) {
				$this->delete_list( $lists );
				$message = __( 'List(s) have been deleted successfully', 'email-subscribers' );
				$this->show_message( $message, 'success' );
			} else {
				$message = __( 'Please select list', 'email-subscribers' );
				$this->show_message( $message, 'error' );

				return;
			}
		}
	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}

	public function status_label_map( $status ) {

		$statuses = array(
			'enable'  => __( 'Enable', 'email-subscribers' ),
			'disable' => __( 'Disable', 'email-subscribers' )
		);

		if ( ! in_array( $status, array_keys( $statuses ) ) ) {
			return '';
		}

		return $statuses[ $status ];
	}

	/** Text displayed when no list data is available */
	public function no_items() {
		_e( 'No lists avaliable.', 'sp' );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
