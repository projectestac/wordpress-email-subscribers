<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Campaigns_Table extends WP_List_Table {

	static $instance;

	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Campaign', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Campaign', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?
			'screen'   => 'es_campaigns'
		) );

		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
	}

	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Campaigns', 'email-subscribers' ),
			'default' => 20,
			'option'  => 'campaigns_per_page'
		);

		add_screen_option( $option, $args );
	}

	public function es_campaigns_callback() {

		$action = Email_Subscribers::get_request( 'action' );
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Campaigns', 'email-subscribers' ) ?>
                <a href="admin.php?page=es_notifications&action=new" class="page-title-action"><?php _e( 'Create Post Notification', 'email-subscribers' ) ?></a>
                <a href="admin.php?page=es_newsletters" class="page-title-action"><?php _e( 'Send Broadcast', 'email-subscribers' ) ?></a>
                <a href="edit.php?post_type=es_template" class="page-title-action es-imp-button"><?php _e( 'Manage Templates', 'email-subscribers' ) ?></a></h1>
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
		<?php
	}

	public function custom_admin_notice() {
		if ( Email_Subscribers::get_request( 'es_note_cat' ) ) {
			echo '<div class="updated"><p>Notification Added Successfully!</p></div>';
		}
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		global $wpdb;

		$order_by = Email_Subscribers::get_request( 'orderby' );
		$order    = Email_Subscribers::get_request( 'order' );
		$search   = Email_Subscribers::get_request( 's' );

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM " . IG_CAMPAIGNS_TABLE;
		} else {
			$sql = "SELECT * FROM " . IG_CAMPAIGNS_TABLE;
		}

		$args             = $query = array();
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

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_CAMPAIGNS_TABLE;

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no list data is available */
	public function no_items() {
		_e( 'No Campaigns Found.', 'email-subscribers' );
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
			case 'base_template_id':
				return $item[ $column_name ];
			case 'list_ids':
				if ( ! empty( $item[ $column_name ] ) ) {
					return ES_DB_Lists::get_list_id_name_map( $item[ $column_name ] );
				} else {
					return '-';
				}

			case 'status':
				$status = ( $item[ $column_name ] == 1 ) ? __( 'Active', 'email-subscribers' ) : __( 'Inactive', 'email-subscribers' );

				return $status;
			case 'type':
				$type = ( $item[ $column_name ] === 'newsletter' ) ? __( 'Broadcast', 'email-subscribers' ) : $item[ $column_name ];
				$type = ucwords( str_replace( '_', ' ', $type ) );

				return $type;
			default:
				return $item[ $column_name ]; //Show the whole array for troubleshooting purposes
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
			'<input type="checkbox" name="campaigns[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_base_template_id( $item ) {

		$type = $item['type'];

		if ( $type === 'post_notification' ) {

			$nonce = wp_create_nonce( 'es_post_notification' );

			$template = get_post( $item['base_template_id'] );

			if ( $template instanceof WP_Post ) {
				$title = '<strong>' . $template->post_title . '</strong>';
			} else {
				$title = '';
			}

			if ( ! empty( $item['type'] ) && $item['type'] == 'post_notification' ) {
				$actions ['edit'] = sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">Edit</a>', esc_attr( 'es_notifications' ), 'edit', absint( $item['id'] ), $nonce );
			}

			$actions['delete'] = sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', esc_attr( 'es_campaigns' ), 'delete', absint( $item['id'] ), $nonce );
			$title             = $title . $this->row_actions( $actions );
		} else {
			$title = $item['name'];
		}

		return $title;
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />',
			'base_template_id' => __( 'Name', 'sp' ),
			'type'             => __( 'Type', 'sp' ),
			'list_ids'         => __( 'List', 'sp' ),
			'status'           => __( 'Status', 'sp' )
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
			'base_template_id' => array( 'base_template_id', true ),
			'list_ids'         => array( 'list_ids', true ),
			'status'           => array( 'status', true )
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
			'bulk_delete' => 'Delete'
		);

		return $actions;
	}

	public function search_box( $text, $input_id ) { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( 'Search Campaigns', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
	<?php }

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		// Note: Disable Search box for now.
		$this->search_box( Email_Subscribers::get_request( 's' ), 'notification-search-input' );

		$per_page     = $this->get_items_per_page( 'campaigns_per_page', 25 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_post_notification' ) ) {
				$message = __( 'You are not allowed to delete campaign.', 'email-subscribers' );
				$status  = 'error';
			} else {
				$this->delete_list( array( Email_Subscribers::get_request( 'list' ) ) );
				$message = __( 'Campaign has been deleted successfully!', 'email-subscribers' );
				$status  = 'success';
			}

			$this->show_message( $message, $status );
		}

		$action  = Email_Subscribers::get_request( 'action' );
		$action2 = Email_Subscribers::get_request( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$ids = esc_sql( Email_Subscribers::get_request( 'campaigns' ) );

			if(is_array($ids) && count($ids) > 0) {

                $deleted = $this->delete_list( $ids );

                if ( $deleted ) {
                    $message = __( 'Campaign(s) have been deleted successfully!', 'email-subscribers' );
                    $this->show_message( $message );
                }
            } else {
			    
				$message = __( 'Please check campaign(s) to delete.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
            }


		}
	}

	/**
	 * Delete a list record.
	 *
	 * @param int $id list ID
	 */
	public function delete_list( $ids ) {
		global $wpdb;
		$ids = implode( ',', array_map( 'absint', $ids ) );

		$current_date = gmdate( 'Y-m-d G:i:s' );
		$query        = "UPDATE " . IG_CAMPAIGNS_TABLE . " SET deleted_at = %s WHERE id IN ($ids)";
		$query        = $wpdb->prepare( $query, array( $current_date ) );
		$result       = $wpdb->query( $query );

		if ( $result ) {
			return true;
		}

		return false;
	}


	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 */
	public static function fetch_notification( $post_id = 0 ) {

		global $wpdb;

		$arrNotification = array();

		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
			$sSql      = "SELECT * FROM {$wpdb->prefix}es_notifications WHERE (status = 'Enable' OR status = 'Cron') ";
			if ( $post_type == "post" ) {
				$category    = get_the_category( $post_id );
				$totcategory = count( $category );
				if ( $totcategory > 0 ) {
					for ( $i = 0; $i < $totcategory; $i ++ ) {
						if ( $i == 0 ) {
							$sSql .= " and (";
						} else {
							$sSql .= " or";
						}
						$sSql .= " cat LIKE '%" . addslashes( htmlspecialchars_decode( $category[ $i ]->cat_name ) ) . "%'";    // alternative addslashes(htmlspecialchars_decode(text)) = mysqli_real_escape_string but not working all the time
						if ( $i == ( $totcategory - 1 ) ) {
							$sSql .= ")";
						}
					}
					$arrNotification = $wpdb->get_results( $sSql, ARRAY_A );
				}
			} else {
				$sSql            .= " and cat LIKE '%{T}" . $post_type . "{T}%'";
				$arrNotification = $wpdb->get_results( $sSql, ARRAY_A );
			}
		}

		return $arrNotification;

	}


	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}

}

add_action( 'admin_menu', function () {
	ES_Campaigns_Table::get_instance();
} );
