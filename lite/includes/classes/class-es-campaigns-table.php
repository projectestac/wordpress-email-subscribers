<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ES_Campaigns_Table extends WP_List_Table {
	/**
	 * Number of campaigns to be shown on the page
	 *
	 * @since 4.2.1
	 * @var string
	 */
	public static $option_per_page = 'es_campaigns_per_page';

	/**
	 * ES_DB_Campaigns object
	 *
	 * @since 4.3.4
	 * @var $db
	 */
	protected $db;

	/**
	 * ES_Campaigns_Table constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Campaign', 'email-subscribers' ), // singular name of the listed records
				'plural'   => __( 'Campaign', 'email-subscribers' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
				'screen'   => 'es_campaigns',
			)
		);

		$this->db = new ES_DB_Campaigns();

		add_action( 'ig_es_campaign_deleted', array( $this, 'delete_child_campaigns' ), 10, 1 );

	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Number of campaigns per page', 'email-subscribers' ),
			'default' => 20,
			'option'  => self::$option_per_page,
		);

		add_screen_option( $option, $args );

	}

	/**
	 * Delete all child campaigns based on $parent_campaign_id
	 *
	 * @param int $parent_campaign_id
	 *
	 * @since 4.3.4
	 */
	public function delete_child_campaigns( $parent_campaign_id = 0 ) {

		if ( 0 != $parent_campaign_id ) {

			$child_campaign_ids = $this->db->get_campaigns_by_parent_id( $parent_campaign_id );

			// Delete All Child Campaigns
			$this->db->delete_campaigns( $child_campaign_ids );
		}
	}


	/**
	 * Render Campaigns table
	 *
	 * @since 4.0
	 */
	public function render() {
		$action = ig_es_get_request_data( 'action' );
		global $ig_es_tracker;
		?>
		<div class="wrap pt-4 font-sans">
		<?php 
		if ( 'broadcast_created' === $action ) {

			// Trigger feedback popup for broadcast creation.
			do_action( 'ig_es_broadcast_created' );

			$message = __( 'Broadcast created successfully.', 'email-subscribers' );
			ES_Common::show_message( $message, 'success' );
		}
		?>
		<div class="flex">
			<div>
				<h2 class="wp-heading-inline text-3xl font-bold text-gray-700 sm:leading-9 sm:truncate pr-4 pb-1"><?php esc_html_e( 'Campaigns', 'email-subscribers' ); ?>
				</h2>
			</div>
			<div class="mt-1">
			<!-- Start-IG-Code -->
			<a href="admin.php?page=es_notifications&action=new" class="ig-es-title-button ml-2 align-middle"><?php esc_html_e( 'Create Post Notification', 'email-subscribers' ); ?></a>
			<!-- End-IG-Code -->
			<a href="admin.php?page=es_newsletters" class="ig-es-title-button ml-2 align-middle"><?php esc_html_e( 'Send Broadcast', 'email-subscribers' ); ?></a>
			
				<?php 
				do_action( 'ig_es_after_campaign_type_buttons' );

				// Start-IG-Code.
				$icegram_plugin = 'icegram/icegram.php';
				$active_plugins = $ig_es_tracker::get_active_plugins();
				if ( in_array( $icegram_plugin, $active_plugins ) ) {
					$redirect_url = admin_url( 'post-new.php?post_type=ig_campaign' );
					?>
					<a href="<?php echo esc_url( $redirect_url ); ?>" class="ig-es-link-button px-3 py-1 ml-2 align-middle"><?php esc_html_e( 'Onsite Campaigns', 'email-subscribers' ); ?></a>
				<?php } else { ?>
					<a href="admin.php?page=go_to_icegram&action=create_campaign" class="ig-es-link-button px-3 py-1 ml-2 align-middle"><?php esc_html_e( 'Onsite Campaigns', 'email-subscribers' ); ?></a>
					<?php
				}
				// End-IG-Code.
				?>

				<a href="edit.php?post_type=es_template" class="ig-es-imp-button px-3 py-1 ml-2 align-middle"><?php esc_html_e( 'Manage Templates', 'email-subscribers' ); ?></a>


			</div>
		</div>
		<div><hr class="wp-header-end"></div>
			<div id="poststuff" class="es-items-lists mt-4">
				<div id="post-body" class="metabox-holder column-1">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="get">
								<input type="hidden" name="page" value="es_campaigns" />
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
		</div>

		<?php
	}

	public function custom_admin_notice() {
		$es_note_cat = ig_es_get_request_data( 'es_note_cat' );

		if ( $es_note_cat ) {
			echo '<div class="updated"><p>' . esc_html__( 'Notification Added Successfully!', 'email-subscribers' ) . '</p></div>';
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

		global $wpdb, $wpbd;

		$order_by                  = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order                     = ig_es_get_request_data( 'order' );
		$search                    = ig_es_get_request_data( 's' );
		$filter_by_campaign_type   = ig_es_get_request_data( 'filter_by_campaign_type' );
		$filter_by_campaign_status = ig_es_get_request_data( 'filter_by_campaign_status' );

		if ( $do_count_only ) {
			$sql = 'SELECT count(*) as total FROM ' . IG_CAMPAIGNS_TABLE;
		} else {
			$sql = 'SELECT * FROM ' . IG_CAMPAIGNS_TABLE;
		}

		$args             = array(); 
		$query 			  = array();
		$add_where_clause = true;

		$query[] = "( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' )";

		if ( ! empty( $search ) ) {
			$query[] = ' name LIKE %s ';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$query = apply_filters( 'ig_es_campaign_list_where_caluse', $query );

		if ( $add_where_clause ) {
			$sql .= ' WHERE ';

			if ( count( $query ) > 0 ) {
				$sql .= implode( ' AND ', $query );

				if ( count( $args ) > 0 ) {
					$sql = $wpbd->prepare( $sql, $args );
				}
			}
		}

		if ( ! empty( $filter_by_campaign_status ) || ( '0' === $filter_by_campaign_status ) ) {
			if ( $add_where_clause ) {
				$sql .= $wpdb->prepare( ' AND status = %s', $filter_by_campaign_status );
			} else {
				$sql .= $wpdb->prepare( ' WHERE status = %s', $filter_by_campaign_status );
			}
		}

		if ( ! empty( $filter_by_campaign_type ) ) {
			if ( $add_where_clause ) {
				$sql .= $wpdb->prepare( ' AND type = %s', $filter_by_campaign_type );
			} else {
				$sql .= $wpdb->prepare( ' WHERE type = %s', $filter_by_campaign_type );
			}
		}

		if ( ! $do_count_only ) {

			$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'name', 'type', 'created_at' );
			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $order_by );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;
			$sql .= " LIMIT $per_page";
			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

			$result = $wpbd->get_results( $sql, 'ARRAY_A' );
		} else {
			$result = $wpbd->get_var( $sql );
		}

		return $result;
	}

	/**
	 * Text Display when no items available
	 *
	 * @since 4.0
	 */
	public function no_items() {
		esc_html_e( 'No Campaigns Found.', 'email-subscribers' );
	}

	/**
	 * Get Campaign statuses
	 *
	 * @param string $status
	 *
	 * @return array|mixed
	 *
	 * @since 4.3.6
	 */
	public function get_statuses( $status = '' ) {

		$statuses = array(
			IG_ES_CAMPAIGN_STATUS_IN_ACTIVE => __( 'In Active', 'email-subscribers' ),
			IG_ES_CAMPAIGN_STATUS_ACTIVE    => __( 'Active', 'email-subscribers' ),
			IG_ES_CAMPAIGN_STATUS_SCHEDULED => __( 'Scheduled', 'email-subscribers' ),
			IG_ES_CAMPAIGN_STATUS_QUEUED    => __( 'Queued', 'email-subscribers' ),
			IG_ES_CAMPAIGN_STATUS_PAUSED    => __( 'Paused', 'email-subscribers' ),
			IG_ES_CAMPAIGN_STATUS_FINISHED  => __( 'Finished', 'email-subscribers' ),
		);

		// We are getting $status = 0 for "In Active".
		// So, we can't check empty()
		if ( '' != $status ) {
			return $statuses[ $status ];
		}

		return $statuses;
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 *
	 * @modified 4.4.4 Removed 'status' column switch case.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case 'list_ids':
				if ( ! empty( $item[ $column_name ] ) ) {
					$list_ids = explode( ',', $item[ $column_name ] );

					return ES_Common::prepare_list_name_by_ids( $list_ids );
				} else {
					return '-';
				}
				break;
			case 'type':
				$type = ( 'newsletter' === $item[ $column_name ] ) ? __( 'Broadcast', 'email-subscribers' ) : $item[ $column_name ];
				$type = ucwords( str_replace( '_', ' ', $type ) );

				return $type;
				break;
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
				break;
			case 'categories':
				if ( ! empty( $item[ $column_name ] ) ) {
					$categories = ES_Common::convert_categories_string_to_array( $item[ $column_name ], false );
					$categories = strpos( $item[ $column_name ], '{a}All{a}' ) ? __( 'All', 'email-subscribers' ) : trim( trim( implode( ', ', $categories ) ), ',' );

					return $categories;
				} else {
					return '-';
				}
				break;
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="campaigns[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_name( $item ) {
		global $wpdb;

		$actions = array();

		$type = $item['type'];

		$nonce = wp_create_nonce( 'es_post_notification' );

		$template = get_post( $item['base_template_id'] );

		$report = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $item['id'] );

		if ( 'newsletter' !== $type ) {
			/*
			if ( $template instanceof WP_Post ) {
				$title = '<strong>' . $template->post_title . '</strong>';
			} else {
				$title = ! empty( $item['name'] ) ? $item['name'] : '';
			}
			*/

			$title = ! empty( $item['name'] ) ? $item['name'] : '';

			$slug = ( in_array( $item['type'], array( 'post_notification', 'post_digest' ) ) ) ? esc_attr( 'es_notifications' ) : 'es_' . $item['type'];
			/* translators: 1: Slug  2: Edit Action  3: List id  4. WP Nonce */
			$actions ['edit'] = sprintf( __( '<a href="?page=%1$s&action=%2$s&list=%3$s&_wpnonce=%4$s" class="text-indigo-600">Edit</a>', 'email-subscribers' ), $slug, 'edit', absint( $item['id'] ), $nonce );

			if ( in_array( $type, array( 'post_notification', 'post_digest' ) ) ) {
				// Add reports link if there are any reports related to the post notification.
				if ( ! empty( $report ) ) {

					$actions['report'] = sprintf( '<a href="?page=%s&campaign_id=%d" class="text-indigo-600">%s</a>', esc_attr( 'es_reports' ), esc_attr( $item['id'] ), __( 'Report', 'email-subscribers' ) );
				}
			} elseif ( 'sequence' === $type ) {
				$actions['report'] = sprintf( '<a href="?page=%s&campaign_id=%d" class="text-indigo-600">%s</a>', esc_attr( 'es_reports' ), esc_attr( $item['id'] ), __( 'Report', 'email-subscribers' ) );
			}
		} else {

			$title  = $item['name'];
			$slug   = 'es_newsletters';
			$status = $item['status'];

			$broadcast_allowed_edit_statuses = array(
				IG_ES_CAMPAIGN_STATUS_IN_ACTIVE,
				IG_ES_CAMPAIGN_STATUS_SCHEDULED,
			);

			if ( in_array( $status, $broadcast_allowed_edit_statuses ) ) {
				/* translators: 1: Slug  2: Edit Action  3: List id  4. WP Nonce */
				$actions ['edit'] = sprintf( __( '<a href="?page=%1$s&action=%2$s&list=%3$s&_wpnonce=%4$s" class="text-indigo-600">Edit</a>', 'email-subscribers' ), $slug, 'edit', absint( $item['id'] ), $nonce );
			}

			$broadcast_allowed_report_statuses = array(
				IG_ES_CAMPAIGN_STATUS_SCHEDULED,
				IG_ES_CAMPAIGN_STATUS_QUEUED,
				IG_ES_CAMPAIGN_STATUS_ACTIVE,
				IG_ES_CAMPAIGN_STATUS_FINISHED,
			);

			if ( in_array( $status, $broadcast_allowed_report_statuses ) && ! empty( $report ) ) {
				$es_nonce = wp_create_nonce( 'es_notification' );
				/* translators: 1: Slug  2: View Action  3: Hash  4. WP Nonce */
				$actions['report'] = sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" class="text-indigo-600">%s</a>', esc_attr( 'es_reports' ), 'view', $report['hash'], $es_nonce, __( 'Report', 'email-subscribers' ) );
			}

			
		}

		$campaign_type = array( 'post_notification', 'post_digest' );
		if ( ! in_array( $item['type'], $campaign_type ) ) {
			$actions = apply_filters( 'ig_es_campaign_actions', $actions, $item );
		}

		/* translators: 1: Slug  2: Delete Action  3: ID  4. WP Nonce */
		$actions['delete'] = sprintf( __( '<a href="?page=%1$s&action=%2$s&list=%3$s&_wpnonce=%4$s" onclick="return checkDelete()">Delete</a>', 'email-subscribers' ), esc_attr( 'es_campaigns' ), 'delete', absint( $item['id'] ), $nonce );

		$title .= $this->row_actions( $actions );

		return $title;
	}

	/**
	 * Method for campaign status HTML
	 *
	 * @return string $status_html Campaign status HTML.
	 *
	 * @since 4.4.4
	 */
	public function column_status( $item ) {
		$campaign_id       = ! empty( $item['id'] ) ? $item['id'] : 0;
		$campaign_status   = ! empty( $item['status'] ) ? (int) $item['status'] : 0;
		$campaign_statuses = array(
			IG_ES_CAMPAIGN_STATUS_ACTIVE,
			IG_ES_CAMPAIGN_STATUS_IN_ACTIVE,
		);

		$campaign_type = '';
		if ( ! empty( $campaign_id ) ) {
			$campaign_type = ES()->campaigns_db->get_campaign_type_by_id( $campaign_id );
		}

		if ( 'newsletter' !== $campaign_type && in_array( $campaign_status, $campaign_statuses, true ) ) {
			?>
			<label for="<?php echo esc_attr( 'ig-es-campaign-status-toggle-' . $campaign_id ); ?>" class="ig-es-campaign-status-toggle-label inline-flex items-center cursor-pointer">
				<span class="relative">
					<input id="<?php echo esc_attr( 'ig-es-campaign-status-toggle-' . $campaign_id ); ?>" type="checkbox" class="absolute es-check-toggle opacity-0 w-0 h-0" name="<?php echo esc_attr( 'ig-es-campaign-status-toggle-' . $campaign_id ); ?>" value="<?php echo esc_attr( $campaign_id ); ?>" 
										  <?php 
											checked( IG_ES_CAMPAIGN_STATUS_ACTIVE,
											$campaign_status ); 
											?>
						>
					<span class="es-mail-toggle-line"></span>
					<span class="es-mail-toggle-dot"></span>
				</span>
			</label>
			<?php
		} else {
			switch ( $campaign_status ) {

				case IG_ES_CAMPAIGN_STATUS_ACTIVE:
					$notification = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
					if ( ! empty( $notification ) ) {
						$notification_status = $notification['status'];
						if ( 'In Queue' === $notification_status ) {
							?>
							<svg class="flex-shrink-0 ml-2 h-6 w-6 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
								<title><?php echo esc_html__( 'Scheduled', 'email-subscribers' ); ?></title>
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
							</svg>
							<?php
						} elseif ( 'Sending' === $notification_status ) {
							?>
							<svg class="flex-shrink-0 ml-2 h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
								<title><?php echo esc_html__( 'Sending', 'email-subscribers' ); ?></title>
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
							</svg>
							<?php
						} else {
							?>
							<svg class="flex-shrink-0 ml-2 h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
								<title><?php echo esc_html__( 'Sent', 'email-subscribers' ); ?></title>
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
							</svg>
							<?php
						}
					}
					break;

				case IG_ES_CAMPAIGN_STATUS_IN_ACTIVE:
					?>
					<svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" class="ml-2 h-6 w-6 text-indigo-600">
						<title><?php echo esc_html__( 'Draft', 'email-subscribers' ); ?></title>
						<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
					</svg>
					<?php
					break;

				case IG_ES_CAMPAIGN_STATUS_SCHEDULED:
					?>
					<svg class="flex-shrink-0 ml-2 h-6 w-6 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
						<title><?php echo esc_html__( 'Scheduled', 'email-subscribers' ); ?></title>
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
					</svg>
					<?php
					break;

				case IG_ES_CAMPAIGN_STATUS_QUEUED:
					?>
					<svg class="flex-shrink-0 ml-2 h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
						<title><?php echo esc_html__( 'Sending', 'email-subscribers' ); ?></title>
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
					</svg>
					</svg>
					<?php
					break;

				case IG_ES_CAMPAIGN_STATUS_PAUSED:
					?>
					<svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" class="ml-2 h-6 w-6 text-blue-400">
						<title><?php echo esc_html__( 'Paused', 'email-subscribers' ); ?></title>
						<path d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
					</svg>
					<?php
					break;

				default:
					?>
					<svg class="flex-shrink-0 ml-2 h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
						<title><?php echo esc_html__( 'Sent', 'email-subscribers' ); ?></title>
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
					</svg>
					<?php
					break;
			}
		}
		?>
		<?php
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name', 'email-subscribers' ),
			'type'       => __( 'Type', 'email-subscribers' ),
			'list_ids'   => __( 'List(s)', 'email-subscribers' ),
			'categories' => __( 'Categories', 'email-subscribers' ),
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
			// 'base_template_id' => array( 'base_template_id', true ),
			// 'list_ids'         => array( 'list_ids', true ),
			// 'status'           => array( 'status', true )
			'name'       => array( 'name', true ),
			'type'       => array( 'type', true ),
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
			'bulk_delete' => 'Delete',
		);

		return $actions;
	}

	/**
	 * Prepare search box
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @since 4.0.0
	 * @since 4.3.4 Added esc_attr()
	 */
	public function search_box( $text = '', $input_id = '' ) { 
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search campaigns', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<p class="search-box search-group-box box-ma10">
			<?php $filter_by_status = ig_es_get_request_data( 'filter_by_campaign_status' ); ?>
			<select name="filter_by_campaign_status" id="ig_es_filter_campaign_status_by_type">
				<?php 
				$allowedtags 			= ig_es_allowed_html_tags_in_esc();
				add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );
				$campaign_types = ES_Common::prepare_campaign_statuses_dropdown_options( $filter_by_status, __( 'All Statuses', 'email-subscribers' ) ); 
				echo wp_kses( $campaign_types , $allowedtags ); 
				?>
			</select>
		</p>
		<p class="search-box search-group-box box-ma10">
			<?php $filter_by_campaign_type = ig_es_get_request_data( 'filter_by_campaign_type' ); ?>
			<select name="filter_by_campaign_type" id="ig_es_filter_campaign_type">
				<?php 
				$campaign_statuses = ES_Common::prepare_campaign_type_dropdown_options( $filter_by_campaign_type, __( 'All Type', 'email-subscribers' ) );
				echo wp_kses( $campaign_statuses , $allowedtags ); 
				?>
			</select>
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
		$this->search_box( $search, 'notification-search-input' );

		$per_page = $this->get_items_per_page( self::$option_per_page, 25 );

		$current_page = $this->get_pagenum();

		$total_items = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // We have to calculate the total number of items
				'per_page'    => $per_page, // We have to determine how many items to show on a page
			)
		);

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {
		$campaign_id = ig_es_get_request_data( 'list' );

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_post_notification' ) ) {
				$message = __( 'You are not allowed to delete campaign.', 'email-subscribers' );
				$status  = 'error';
			} else {

				$this->db->delete_campaigns( $campaign_id );
				$message = __( 'Campaign deleted successfully!', 'email-subscribers' );
				$status  = 'success';
			}

			ES_Common::show_message( $message, $status );
		}
		$campaign_action = $this->current_action();

		do_action( 'ig_es_campaign_action', $campaign_id, $campaign_action );

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$ids = ig_es_get_request_data( 'campaigns' );

			if ( is_array( $ids ) && count( $ids ) > 0 ) {
				// Delete multiple Campaigns
				$this->db->delete_campaigns( $ids );

				$message = __( 'Campaign(s) deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message );
			} else {

				$message = __( 'Please select campaign(s) to delete.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}
		}
	}
}
