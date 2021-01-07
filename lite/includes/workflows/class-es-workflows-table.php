<?php
/**
 * Show workflows list in admin dashboard.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class to show workflows list in admin dashboard.
 *
 * @class ES_Workflows_Table
 *
 * @since  4.4.1
 */
class ES_Workflows_Table extends WP_List_Table {

	/**
	 * Number of workflows to show at once.
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public static $option_per_page = 'es_workflows_per_page';

	/**
	 * ES_DB_Workflows object
	 *
	 * @since 4.4.1
	 * @var $db
	 */
	protected $db;

	/**
	 * ES_Workflows_Table constructor.
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Workflow', 'email-subscribers' ), // Singular name of the listed records.
				'plural'   => __( 'Workflows', 'email-subscribers' ), // Plural name of the listed records.
				'ajax'     => false, // Does this table support ajax?
				'screen'   => 'es_workflows',
			)
		);

		$this->db = new ES_DB_Workflows();
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.4.1
	 */
	public static function screen_options() {

		$action = ig_es_get_request_data( 'action' );

		if ( ! in_array( $action, array( 'new', 'edit' ), true ) ) {

			// Admin screen options for workflow list table.
			$option = 'per_page';
			$args   = array(
				'label'   => __( 'Number of workflows per page', 'email-subscribers' ),
				'default' => 20,
				'option'  => self::$option_per_page,
			);

			add_screen_option( $option, $args );
		}

	}

	/**
	 * Render Workflows table
	 *
	 * @since 4.4.1
	 */
	public function render() {

		$action      = ig_es_get_request_data( 'action' );
		$workflow_id = ig_es_get_request_data( 'id' );

		// After successfully adding/updating a workflow, ES redirects user to workflow list page with the status of the performed action in 'action_status' URL query parameter.
		$action_status = ig_es_get_request_data( 'action_status' );

		if ( ! empty( $action_status ) ) {
			if ( ! empty( $workflow_id ) ) {
				$workflow_edit_url = ES_Workflow_Admin_Edit::get_edit_url( $workflow_id );
				if ( ! empty( $workflow_edit_url ) ) {
					$workflow_edit_url = esc_url( $workflow_edit_url );
				}
				if ( 'added' === $action_status ) {
					/* translators: %s: Workflow edit URL */
					$message = sprintf( __( 'Workflow added. <a href="%s" class="text-indigo-600">Edit workflow</a>', 'email-subscribers' ), $workflow_edit_url );
					$status  = 'success';
				} elseif ( 'updated' === $action_status ) {
					/* translators: %s: Workflow edit URL */
					$message = sprintf( __( 'Workflow updated. <a href="%s" class="text-indigo-600">Edit workflow</a>', 'email-subscribers' ), $workflow_edit_url );
					$status  = 'success';
				} elseif ( 'not_saved' === $action_status ) {
					$message = __( 'Unable to save workflow. Please try again later.', 'email-subscribers' );
					$status  = 'error';
				} elseif ( 'not_allowed' === $action_status ) {
					$message = __( 'You are not allowed to add/edit workflows.', 'email-subscribers' );
					$status  = 'error';
				} else {
					$message = __( 'An error has occured. Please try again later', 'email-subscribers' );
					$status  = 'error';
				}
			}
		}

		if ( ! empty( $message ) && ! empty( $status ) ) {
			ES_Common::show_message( $message, $status );
		}
		?>
		<div class="wrap pt-4 font-sans">
			<?php
			if ( 'new' === $action ) {
				ES_Workflow_Admin_Edit::load_workflow();
			} elseif ( 'edit' === $action && ! empty( $workflow_id ) ) {
				ES_Workflow_Admin_Edit::load_workflow( $workflow_id );
			} else {
				$this->load_workflow_list();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Workflows list
	 *
	 * @since 4.4.1
	 * 
	 * @modified 4.4.4 Added wp-heading-inline class to heading tag.
	 */
	public function load_workflow_list() {
		?>
		<div class="flex">
			<div>
				<h2 class="wp-heading-inline text-3xl pb-1 font-bold text-gray-700 sm:leading-9 sm:truncate pr-4">
					<?php esc_html_e( 'Workflows', 'email-subscribers' ); ?>
				</h2>
			</div>
			<div class="mt-1">
			<a href="admin.php?page=es_workflows&action=new" class="px-3 py-1 ml-2 leading-5 align-middle ig-es-title-button">
				<?php esc_html_e( 'Add New', 'email-subscribers' ); ?></a>
				<?php do_action( 'ig_es_after_workflow_type_buttons' ); ?>
			</div>
		</div>
		<div><hr class="wp-header-end"></div>
		<div id="poststuff" class="es-items-lists">
			<div id="post-body" class="metabox-holder column-1">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="get">
							<input type="hidden" name="page" value="es_workflows" />
							<?php
							// Display search field and other available filter fields.
							$this->prepare_items();
							?>
						</form>
						<form method="post">
							<?php
							// Display bulk action fields, pagination and list items.
							$this->display();
							?>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
		<?php
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page Workflows to show per page.
	 * @param int $page_number Page number to show.
	 * @param int $do_count_only Flag to fetch only count.
	 *
	 * @return mixed
	 */
	public static function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		$args = array(
			's'           => $search,
			'order'       => $order,
			'order_by'    => $order_by,
			'per_page'    => $per_page,
			'page_number' => $page_number,
			'type' 		  => 0, // Fetch only user defined workflows.
		);

		$result = ES()->workflows_db->get_workflows( $args, ARRAY_A, $do_count_only );

		return $result;
	}

	/**
	 * Text Display when no items available
	 *
	 * @since 4.4.1
	 */
	public function no_items() {
		echo esc_html__( 'No Workflows Found.', 'email-subscribers' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item Workflow item.
	 * @param string $column_name Column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		$output = '';

		switch ( $column_name ) {

			case 'created_at':
				$output = ig_es_format_date_time( $item[ $column_name ] );
				break;
			default:
				$output = $item[ $column_name ];
		}

		return $output;
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item Workflow item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="workflows[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Method for title column
	 *
	 * @param array $item an array of DB data.
	 *
	 * @return string
	 */
	public function column_title( $item ) {

		$nonce = wp_create_nonce( 'es_post_workflow' );

		$title = $item['title'];

		$actions ['edit']  = sprintf( '<a href="?page=%s&action=%s&id=%s" class="text-indigo-600">%s</a>', $this->screen->id, 'edit', absint( $item['id'] ), __( 'Edit', 'email-subscribers' ) );
		$actions['delete'] = sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s" onclick="return checkDelete()">%s</a>', esc_attr( 'es_workflows' ), 'delete', absint( $item['id'] ), $nonce, __( 'Delete', 'email-subscribers' ) );

		$title .= $this->row_actions( $actions );

		return $title;
	}

	/**
	 * Method for status column
	 *
	 * @param array $item an array of DB data.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		return '<button type="button" class="ig-es-switch js-toggle-workflow-status" '
		. 'data-workflow-id="' . $item['id'] . '" '
		. 'data-ig-es-switch="' . ( '1' === $item['status'] ? 'active' : 'inactive' ) . '">'
		. __( 'Toggle Status', 'email-subscribers' ) . '</button>';
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Title', 'email-subscribers' ),
			'created_at' => __( 'Created', 'email-subscribers' ),
			'status'     => __( 'Status', 'email-subscribers' ),
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
			'title'      => array( 'title', true ),
			'created_at' => array( 'created_at', true ),
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
			'bulk_activate'   => esc_html__( 'Activate', 'email-subscribers' ),
			'bulk_deactivate' => esc_html__( 'Deactivate', 'email-subscribers' ),
			'bulk_delete'     => esc_html__( 'Delete', 'email-subscribers' ),
		);

		return $actions;
	}

	/**
	 * Prepare search box
	 *
	 * @param string $text Search text.
	 * @param string $input_id Input field id.
	 *
	 * @since 4.4.1
	 */
	public function search_box( $text = '', $input_id = '' ) {
		?>
		<p class="search-box">
			<label class="screen-reader-text"
			for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( __( 'Search workflows', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		// Note: Disable Search box for now.
		$search = ig_es_get_request_data( 's' );
		$this->search_box( $search, 'workflow-search-input' );

		$per_page = $this->get_items_per_page( self::$option_per_page, 25 );

		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // We have to calculate the total number of items.
				'per_page'    => $per_page, // We have to determine how many items to show on a page.
			)
		);

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	/**
	 * Method to process bulk actions on workflows
	 */
	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_post_workflow' ) ) {
				$message = __( 'You are not allowed to delete workflow.', 'email-subscribers' );
				$status  = 'error';
			} else {
				$workflow_id = ig_es_get_request_data( 'id' );

				$this->db->delete_workflows( $workflow_id );
				$message = __( 'Workflow deleted successfully!', 'email-subscribers' );
				$status  = 'success';
			}

			ES_Common::show_message( $message, $status );
		}

		$action = ig_es_get_request_data( 'action' );
		// If the delete bulk action is triggered.
		if ( 'bulk_delete' === $action ) {

			$ids = ig_es_get_request_data( 'workflows' );

			if ( is_array( $ids ) && count( $ids ) > 0 ) {
				// Delete multiple Workflows.
				$this->db->delete_workflows( $ids );

				$message = __( 'Workflow(s) deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message );
			} else {

				$message = __( 'Please select workflow(s) to delete.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}
		} elseif ( 'bulk_activate' === $action || 'bulk_deactivate' === $action ) {

			$ids = ig_es_get_request_data( 'workflows' );

			if ( is_array( $ids ) && count( $ids ) > 0 ) {

				$new_status = ( 'bulk_activate' === $action ) ? 1 : 0;

				// Update multiple Workflows.
				$this->db->update_status( $ids, $new_status );

				$workflow_action = 'bulk_activate' === $action ? __( 'activated', 'email-subscribers' ) : __( 'deactivated', 'email-subscribers' );

				/* translators: %s: Workflow action */
				$message = sprintf( __( 'Workflow(s) %s successfully!', 'email-subscribers' ), $workflow_action );

				ES_Common::show_message( $message );
			} else {

				$workflow_action = 'bulk_activate' === $action ? __( 'activate', 'email-subscribers' ) : __( 'deactivate', 'email-subscribers' );

				/* translators: %s: Workflow action */
				$message = sprintf( __( 'Please select workflow(s) to %s.', 'email-subscribers' ), $workflow_action );

				ES_Common::show_message( $message, 'error' );
			}
		}
	}
}
