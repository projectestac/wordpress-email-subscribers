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
class ES_Workflows_Table extends ES_List_Table {

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
		$this->init();
	}

	public function init() {
		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'ig_es_show_workflows', array( $this, 'show_workflows' ) );
		add_action( 'ig_es_show_workflow_gallery', array( $this, 'show_workflow_gallery' ) );
	}

	/**
	 * Show existing workflows
	 * 
	 * @since 5.3.8
	 */
	public function show_workflows() {
		?>
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
	 * Show workflow gallery tab
	 * 
	 * @since 5.3.8
	 */
	public function show_workflow_gallery() {
		$workflow_gallery_items = ES_Workflow_Gallery::get_workflow_gallery_items();
		?>
		<div class="ig-es-workflow-gallery-tab-description">
			<p class="pb-2 text-sm font-normal text-gray-500">
				<?php
					/* translators: 1. Opening strong tag(<strong>) 2: Closing strong tag(</strong>) */
					echo sprintf( esc_html__( 'Here\'s a collection of some useful workflows for you. Simply click on %1$sUse workflow%2$s button to begin.', 'email-subscribers' ), '<strong>', '</strong>' );
				?>
			</p>
		</div>
		<div class="ig-es-workflow-gallery-tab-content bg-white rounded-lg shadow-md">
			<div class="w-full overflow-auto py-4 px-6 mt-2">
			<?php
			if ( ! empty( $workflow_gallery_items ) ) {
				?>
				<div class="ig-es-workflow-gallery-list">
				<?php
				foreach ( $workflow_gallery_items as $item_name => $item ) {
					?>
					<div class="ig-es-workflow-gallery-item flex mt-3 mb-2 pb-3 border-b border-gray">
						<div class="ig-es-workflow-gallery-item-detail">
							<h2 class="ig-es-workflow-gallery-item-title font-medium text-gray-600 tracking-wide text-lg">
								<?php echo esc_html( $item['title'] ); ?>
							</h2>
							<p class="ig-es-workflow-gallery-item-description text-gray-600 text-sm pt-1">
								<?php echo esc_html( $item['description'] ); ?>
							</p>
						</div>
						<div class="ig-es-workflow-gallery-item-actions">
							<button data-item-name=<?php echo esc_attr( $item_name ); ?> type="button" class="ig-es-create-workflow-from-gallery-item ig-es-inline-loader inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4">
								<span>
									<?php echo esc_html__( 'Use workflow', 'email-subscribers' ); ?>
								</span>
								<svg class="es-btn-loader animate-spin h-4 w-4 text-indigo" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
								</svg>
							</button>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<?php
			} else {
				?>
				<h2 class="text-base font-medium text-gray-600 tracking-wide text-lg text-xl">
					<?php echo esc_html__( 'No items found in workflow gallery.', 'email-subscribers' ); ?>
				</h2>
				<?php
			}
			?>
			</div>
		</div>
		<?php
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
	 * Render Workflows list | Save/Edit Workflow page
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
				$workflow = new ES_Workflow( $workflow_id );
				if ( $workflow->exists ) {
					$run_workflow = get_transient( 'ig_es_run_workflow' );
					if ( $workflow->is_runnable() && 'yes' === $run_workflow ) {
						?>
						<script>
							jQuery(document).ready(function(){
								let workflow_id = <?php echo esc_js( $workflow_id ); ?>;
								ig_es_run_workflow( workflow_id );
							});
						</script>
						<?php
					} else {
						// Show workflow added/updated notice only if there is not workflow to run to avoid notice cluster on the page.
						$workflow_edit_url = $workflow->get_edit_url();
						if ( 'added' === $action_status ) {
							/* translators: 1. Workflow edit URL anchor tag 2: Anchor close tag */
							$message = sprintf( __( 'Workflow added. %1$sEdit workflow%2$s.', 'email-subscribers' ), '<a href="' . esc_url( $workflow_edit_url ) . '" class="text-indigo-600">', '</a>' );
							$status  = 'success';
						} elseif ( 'updated' === $action_status ) {
							/* translators: 1. Workflow edit URL anchor tag 2: Anchor close tag */
							$message = sprintf( __( 'Workflow updated. %1$sEdit workflow%2$s', 'email-subscribers' ), '<a href="' . esc_url( $workflow_edit_url ) . '" class="text-indigo-600">', '</a>' );
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
		$tab = ig_es_get_request_data( 'tab' );
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
		<div class="mt-2">
			<ul class="ig-es-tabs overflow-hidden">
				<li class="ig-es-tab-heading relative float-left px-1 pb-2 text-center list-none cursor-pointer <?php echo '' === $tab ? esc_attr( 'active' ) : ''; ?>">
					<a href="admin.php?page=es_workflows">
						<span class="mt-1 text-base font-medium tracking-wide text-gray-400">
							<?php echo esc_html__( 'Workflows', 'email-subscribers' ); ?>
						</span>
					</a>
				</li>
				<li class="ig-es-tab-heading relative float-left px-1 pb-2 ml-5 text-center list-none cursor-pointer hover:border-2 <?php echo 'gallery' === $tab ? esc_attr( 'active' ) : ''; ?>">
					<a href="admin.php?page=es_workflows&tab=gallery">
						<span class="mt-1 text-base font-medium tracking-wide text-gray-400">
							<?php echo esc_html__( 'Workflow gallery', 'email-subscribers' ); ?>
						</span>
					</a>
				</li>
			</ul>
		</div>
		<?php
		if ( 'gallery' === $tab ) {
			do_action( 'ig_es_show_workflow_gallery' );
		} else {
			do_action( 'ig_es_show_workflows' );
		}
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
	public function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );
		$type     = ig_es_get_request_data( 'type' );

		$args = array(
			's'           => $search,
			'order'       => $order,
			'order_by'    => $order_by,
			'per_page'    => $per_page,
			'page_number' => $page_number,
			'type'        => $type,
		);

		if ( '' !== $type ) {
			if ( 'system' === $type ) {
				$type = IG_ES_WORKFLOW_TYPE_SYSTEM;
			} elseif ( 'user' === $type ) {
				$type = IG_ES_WORKFLOW_TYPE_USER;
			}

			$args['type'] = $type;
		}

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

			case 'last_ran_at':
				$workflow_id  = $item['id'];
				$item['meta'] = maybe_unserialize( $item['meta'] );
				$output      .= '<span class="last_ran_at_date_time" data-workflow-id="' . $workflow_id . '">';
				if ( isset( $item['meta']['last_ran_at'] ) ) {
					$output .= ig_es_format_date_time( $item['meta']['last_ran_at'] );
				} else {
					$output .= '-';
				}
				$output  .= '</span>';
				$workflow = new ES_Workflow( $workflow_id );
				if ( $workflow->exists && $workflow->is_runnable() ) {
					/* translators: 1. Run workflow button start tag 2: Button close tag */
					$output .= sprintf( __( ' %1$sRun%2$s', 'email-subscribers' ), '<button type="button" class="inline-flex justify-center rounded-md border border-transparent px-2 py-0.5 bg-white text-sm leading-5 font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue transition ease-in-out duration-150 ig-es-run-workflow-btn" data-workflow-id="' . $workflow_id . '">', '</button>' );
				}
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
		
		$is_system_workflow = ( IG_ES_WORKFLOW_TYPE_SYSTEM === (int) $item['type'] ) ? true : false;
		if ( ! $is_system_workflow ) {
			$actions['delete'] = sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s" onclick="return checkDelete()">%s</a>', esc_attr( 'es_workflows' ), 'delete', absint( $item['id'] ), $nonce, __( 'Delete', 'email-subscribers' ) );
		}

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
			'cb'          => '<input type = "checkbox" />',
			'title'       => __( 'Title', 'email-subscribers' ),
			'last_ran_at' => __( 'Last ran at', 'email-subscribers' ),
			'status'      => __( 'Status', 'email-subscribers' ),
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
			'title'       => array( 'title', true ),
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
			<?php submit_button( __( 'Search Workflows', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
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
				$this->db->delete_workflows_campaign( $workflow_id );
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
				$this->db->delete_workflows_campaign( $ids );
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
