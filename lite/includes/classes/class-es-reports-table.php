<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Reports_Table extends ES_List_Table {

	public static $instance;

	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Report', 'email-subscribers' ), // singular name of the listed records
				'plural'   => __( 'Reports', 'email-subscribers' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?,
				'screen'   => 'es_reports',
			)
		);

		add_action( 'admin_footer', array( $this, 'display_preview_email' ), 10 );
	}

	public function es_reports_callback() {

		$campaign_id   = ig_es_get_request_data( 'campaign_id' );
		$campaign_type = '';
		// Since, currently we are not passing campaign_id with broadcast $campaign_type will remain empty for broadcast
		if ( ! empty( $campaign_id ) ) {
			$campaign_type = ES()->campaigns_db->get_campaign_type_by_id( $campaign_id );
		}

		$campaign_types = array( 'sequence', 'sequence_message', 'workflow', 'workflow_email' );
		// Only if it is sequence then control will transfer to Sequence Reports class.
		if ( ! empty( $campaign_type ) && in_array( $campaign_type, $campaign_types, true ) ) {
			if ( ES()->is_pro() ) {
				$reports = ES_Pro_Sequence_Reports::get_instance();
				$reports->es_sequence_reports_callback();
			} else {
				do_action( 'ig_es_view_report_data' );
			}
		} else {
			$action = ig_es_get_request_data( 'action' );
			if ( 'view' === $action ) {
				$view_report = new ES_Campaign_Report();
				$view_report->es_campaign_report_callback();
			} else {
				?>
				<div class="wrap pt-4 font-sans">
					<header class="wp-heading-inline">
						<div class="flex">
							<div class="flex-1 min-w-0">
								<h2 class="text-3xl font-bold leading-9 text-gray-700 sm:truncate"><?php esc_html_e( 'Reports', 'email-subscribers' ); ?>
								</h2>
							</div>
							<?php
							$emails_to_be_sent = ES_DB_Sending_Queue::get_total_emails_to_be_sent();
							if ( $emails_to_be_sent > 0 ) {
								$cron_url = ES()->cron->url( true );
								/* translators: %s: Cron url */
								$content = '<a href="' . esc_url( $cron_url ) . '" class="px-3 py-2 ig-es-imp-button">' . esc_html__( 'Send Queued Emails Now', 'email-subscribers' ) . '</a>';
							} else {
								$content  = '<span class="ig-es-send-queue-emails px-3 button-disabled">' . esc_html__( 'Send Queued Emails Now', 'email-subscribers' ) . '</span>';
								$content .= '<br /><span class="es-helper pl-6">' . esc_html__( 'No emails found in queue', 'email-subscribers' ) . '</span>';
							}
							?>
							<div class="flex flex-row">
								<div>
									<span class="ig-es-process-queue"><?php echo wp_kses_post( $content ); ?></span>
								</div>
							</div>
						</div>
					</header>
					<?php
					$show_campaign_notice = $emails_to_be_sent > 0 && ES()->is_starter();
					if ( $show_campaign_notice ) {
						?>
						<style>
							#ig-es-edit-campaign-notice p {
								margin: 0.2em 0;
							}
						</style>
						<div id="ig-es-edit-campaign-notice" class="px-5 py-2 notice notice-info">
							<p>
								<?php
									/* translators: 1. Pause icon HTML 2. Resume icon HTML */
									echo sprintf( esc_html__( 'While the campaign is still sending, you can pause %1$s it anytime and update the campaign. Once you are done, resume %2$s the campaign.', 'email-subscribers' ), '<svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" class="h-6 w-6 text-gray-500 ml-1 inline">
									<path d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
								</svg>',
									'<svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" class="h-6 w-6 text-blue-500 inline">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
								</svg>' );
								?>
							</p>
							<p>
								<strong>
									<?php
										echo esc_html__( 'Note: ', 'email-subscribers' );
									?>
								</strong>
								<?php
									echo esc_html__( 'Changes will reflect from the next sending batch.', 'email-subscribers' );
								?>
							</p>
						</div>
						<?php
					}
					?>
					<div>
						<hr class="wp-header-end">
					</div>
					<div id="poststuff" class="es-items-lists">
						<div id="post-body" class="metabox-holder column-1">
							<div id="post-body-content">
								<div class="meta-box-sortables ui-sortable">
									<form method="get">
										<input type="hidden" name="page" value="es_reports"/>
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
		}

	}

	public function screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Reports', 'email-subscribers' ),
			'default' => 10,
			'option'  => 'reports_per_page',
		);

		add_screen_option( $option, $args );

	}

	/** Text displayed when no list data is available */
	public function no_items() {
		esc_html_e( 'No Reports avaliable.', 'email-subscribers' );
	}

	/**
	 * Generates content for a single row of the table.
	 * Overrides WP_List_Table class single_row function.
	 *
	 * @since 4.7.8
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr data-status="' . esc_attr( strtolower( $item['status'] ) ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		global $wpdb;
		$item = apply_filters( 'es_add_additional_report_column_data', $item, $column_name );
		switch ( $column_name ) {
			case 'start_at':
			case 'finish_at':
				return ig_es_format_date_time( $item[ $column_name ] );
			case 'type':
				if ( empty( $item['campaign_id'] ) ) {
					$type = __( 'Post Notification', 'email-subscribers' );
				} else {
					$type = ES()->campaigns_db->get_campaign_type_by_id( $item['campaign_id'] );
					$type = strtolower( $type );
					$type = ( 'newsletter' === $type ) ? __( 'Broadcast', 'email-subscribers' ) : $type;
				}

				$type = ucwords( str_replace( '_', ' ', $type ) );

				return $type;
			case 'subject':
				// case 'type':
				// return ucwords($item[ $column_name ]);
			case 'count':
				return $item[ $column_name ];
			case 'total_sent':
				$total_emails_sent = ES()->actions_db->get_count_based_on_id_type( $item['campaign_id'], $item['id'], IG_MESSAGE_SENT );
				return number_format_i18n( $total_emails_sent );
			default:
				$column_data = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '-';

				return $column_data;
		}
	}

	public function column_status( $item ) {
		$report_status = $item['status'];
		$status_html   = '';
		if ( IG_ES_MAILING_QUEUE_STATUS_SENT === $report_status ) {
			$status_html = sprintf(
				'<svg class="flex-shrink-0 h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
			<title>%s</title>
			<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
		</svg>',
				__( 'Sent', 'email-subscribers' )
			);
		} else {
			if ( IG_ES_MAILING_QUEUE_STATUS_SENDING === $report_status ) {
				$status_html = sprintf(
					'<svg class="flex-shrink-0 h-6 w-6 text-yellow-400 inline" fill="currentColor" viewBox="0 0 20 20">
				<title>%s</title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
			</svg>',
					__( 'Sending', 'email-subscribers' )
				);
			} elseif ( IG_ES_MAILING_QUEUE_STATUS_PAUSED === $report_status ) {
				$status_html = sprintf(
					'<svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 h-6 w-6 inline text-gray-500" viewBox="0 0 20 20" fill="currentColor">
				<title>%s</title>
				<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
			</svg>',
					__( 'Paused', 'email-subscribers' )
				);
			} elseif ( IG_ES_MAILING_QUEUE_STATUS_QUEUED === $report_status ) {
				$status_html = sprintf(
					'<svg class="flex-shrink-0 h-6 w-6 inline text-orange-400" fill="currentColor" viewBox="0 0 20 20">
				<title>%s</title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
			</svg>',
					__( 'Scheduled', 'email-subscribers' )
				);
			} elseif ( IG_ES_MAILING_QUEUE_STATUS_FAILED === $report_status ) {
				$status_html = sprintf(
					'<svg class="flex-shrink-0 h-6 w-6 inline text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<title>%s</title>
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
					__( 'Failed', 'email-subscribers' )
				);
			}

			$actions = array();
			if ( in_array( $report_status, array( IG_ES_MAILING_QUEUE_STATUS_QUEUED, IG_ES_MAILING_QUEUE_STATUS_SENDING, IG_ES_MAILING_QUEUE_STATUS_FAILED ), true ) ) {
				$actions['send_now'] = $this->prepare_send_now_url( $item );
			}

			$actions = apply_filters( 'ig_es_report_row_actions', $actions, $item );

			$status_html = $status_html . $this->row_actions( $actions, true );
		}
		return $status_html;
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
			'<input type="checkbox" name="bulk_delete[]" value="%s" />',
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
	public function column_subject( $item ) {

		$es_nonce = wp_create_nonce( 'es_notification' );
		$page     = ig_es_get_request_data( 'page' );

		$title = '<strong>' . $item['subject'] . '</strong>';

		$actions = array(
			'view'          => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" class="text-indigo-600">%s</a>', esc_attr( $page ), 'view', $item['hash'], $es_nonce, __( 'View', 'email-subscribers' ) ),
			'delete'        => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" onclick="return checkDelete()">%s</a>', esc_attr( $page ), 'delete', absint( $item['id'] ), $es_nonce, __( 'Delete', 'email-subscribers' ) ),
			'preview_email' => sprintf( '<a href="#" data-campaign-id="%s" class="es-preview-report text-indigo-600">%s</a><img class="es-preview-loader inline-flex align-middle pl-2 h-5 w-7" src="%s" style="display:none;"/>', absint( $item['id'] ), __( 'Preview', 'email-subscribers' ), esc_url( ES_PLUGIN_URL ) . 'lite/admin/images/spinner-2x.gif' ),

		);

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'subject'    => __( 'Subject', 'email-subscribers' ),
			'type'       => __( 'Type', 'email-subscribers' ),
			'status'     => __( 'Status', 'email-subscribers' ),
			'start_at'   => __( 'Start Date', 'email-subscribers' ),
			'finish_at'  => __( 'End Date', 'email-subscribers' ),
			'count'      => __( 'Total contacts', 'email-subscribers' ),
			'total_sent' => __( 'Total sent', 'email-subscribers' ),
		);

		return $columns;
	}

	public function column_count( $item ) {

		$campaign_hash = $item['hash'];

		$total_emails_sent       = $item['count'];
		$total_emails_to_be_sent = $item['count'];
		// if ( ! empty( $campaign_hash ) ) {
		// $total_emails_sent = ES_DB_Sending_Queue::get_total_emails_sent_by_hash( $campaign_hash );
		// }

		// $content = $total_emails_sent . "/" . $total_emails_to_be_sent;

		return number_format_i18n( $total_emails_to_be_sent );

	}

	public function prepare_send_now_url( $item ) {
		$campaign_hash = $item['hash'];

		$cron_url = '';
		if ( ! empty( $campaign_hash ) ) {
			$cron_url = ES()->cron->url( true, false, $campaign_hash );
		}

		$content = '';
		if ( ! empty( $cron_url ) ) {
			/* translators: %s: Cron url */
			$content = sprintf(
				'<a href="%s" target="_blank">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<title>%s</title>
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
		  </svg></a>',
				$cron_url,
				__( 'Send now', 'email-subscribers' )
			);
		}

		return $content;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'subject'   => array( 'subject', true ),
			'status'    => array( 'status', true ),
			'start_at'  => array( 'start_at', true ),
			'finish_at' => array( 'finish_at', true ),
			'count'     => array( 'count', true ),
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
			'bulk_delete' => __( 'Delete', 'email-subscribers' ),
		);

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		// Search box
		$search = ig_es_get_request_data( 's' );

		$this->search_box( $search, 'reports-search-input' );

		$per_page     = $this->get_items_per_page( 'reports_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_notifications( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items
				'per_page'    => $per_page, // WE have to determine how many items to show on a page
			)
		);

		$this->items = $this->get_notifications( $per_page, $current_page, false );
	}

	public function get_notifications( $per_page = 5, $page_number = 1, $do_count_only = false ) {
		global $wpdb, $wpbd;

		$order_by                          = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order                             = ig_es_get_request_data( 'order' );
		$campaign_id                       = ig_es_get_request_data( 'campaign_id' );
		$search                            = ig_es_get_request_data( 's' );
		$filter_reports_by_campaign_status = ig_es_get_request_data( 'filter_reports_by_status' );
		$filter_reports_by_campaign_type   = ig_es_get_request_data( 'filter_reports_by_campaign_type' );
		$filter_reports_by_month_year	   = ig_es_get_request_data( 'filter_reports_by_date' );

		$ig_mailing_queue_table = IG_MAILING_QUEUE_TABLE;

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$ig_mailing_queue_table}";
		} else {
			$sql = "SELECT * FROM {$ig_mailing_queue_table}";
		}

		$where_columns    = array();
		$where_args       = array();
		$add_where_clause = true;

		if ( ! empty( $campaign_id ) && is_numeric( $campaign_id ) ) {
			$where_columns[] = 'campaign_id = %d';
			$where_args[]    = $campaign_id;
		}

		if ( ! empty( $filter_reports_by_month_year ) ) {

			if ( preg_match('/^[0-9]{6}$/', $filter_reports_by_month_year) ) {

				$year_val 	= substr($filter_reports_by_month_year, 0, 4);
				$month_val 	= substr($filter_reports_by_month_year, 4 );

				$date_string = $year_val . '-' . $month_val;
				$date = new DateTime($date_string);

				$start_date = $date->format('Y-m-01 H:i:s') ;
				$end_date = $date->format('Y-m-t H:i:s');

				array_push( $where_columns, 'start_at >= %s', 'start_at <= %s' );
				array_push($where_args, $start_date, $end_date);
			}
		}
		
		$where_query = '';
		if ( ! empty( $where_columns ) ) {
			$where_query = implode( ' AND ', $where_columns );
			$where_query = $wpbd->prepare( $where_query, $where_args );
		}

		if ( ! empty( $where_query ) ) {
			$sql             .= ' WHERE ' . $where_query;
			$add_where_clause = false;
		}

		if ( ! empty( $filter_reports_by_campaign_status ) || ( '0' === $filter_reports_by_campaign_status ) ) {
			if ( ! $add_where_clause ) {
				$sql .= $wpdb->prepare( ' AND status = %s', $filter_reports_by_campaign_status );
			} else {
				$sql             .= $wpdb->prepare( ' WHERE status = %s', $filter_reports_by_campaign_status );
				$add_where_clause = false;
			}
		}

		if ( ! empty( $filter_reports_by_campaign_type ) ) {
			if ( ! $add_where_clause ) {
				$sql .= $wpdb->prepare( ' AND meta LIKE %s', '%' . $wpdb->esc_like( $filter_reports_by_campaign_type ) . '%' );
			} else {
				$sql .= $wpdb->prepare( ' WHERE meta LIKE %s', '%' . $wpdb->esc_like( $filter_reports_by_campaign_type ) . '%' );
			}
		}

		if ( ! $do_count_only ) {

			// Prepare Order by clause
			$order = ! empty( $order ) ? strtolower( $order ) : 'desc';

			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'subject', 'type', 'status', 'start_at', 'count', 'created_at' );

			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $order_by );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql   .= $order_by_clause;
			$sql   .= " LIMIT $per_page";
			$sql   .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
			$result = $wpbd->get_results( $sql, 'ARRAY_A' );

		} else {
			$result = $wpbd->get_var( $sql );
		}

		return $result;
	}

	public function process_bulk_action() {
		$allowedtags = ig_es_allowed_html_tags_in_esc();
		// Detect when a bulk action is being triggered...
		if ( 'view' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to view notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}
		} elseif ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to delete notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$notification_ids = absint( ig_es_get_request_data( 'list' ) );
				ES_DB_Mailing_Queue::delete_notifications( array( $notification_ids ) );
				ES_DB_Sending_Queue::delete_sending_queue_by_mailing_id( array( $notification_ids ) );
				$message = __( 'Report deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {
			$notification_ids = ig_es_get_request_data( 'bulk_delete' );

			if ( count( $notification_ids ) > 0 ) {
				ES_DB_Mailing_Queue::delete_notifications( $notification_ids );
				ES_DB_Sending_Queue::delete_sending_queue_by_mailing_id( $notification_ids );
				$message = __( 'Reports deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}
	}

	/*
	* Display the preview of the email content
	*/
	public function display_preview_email() {
		?>
		<div class="hidden" id="report_preview_template">
			<div class="fixed top-0 left-0 z-50 flex items-center justify-center w-full h-full" style="background-color: rgba(0,0,0,.5);">
				<div style="height:485px" class="absolute h-auto p-4 ml-16 mr-4 text-left bg-white rounded shadow-xl z-80 md:max-w-5xl md:p-6 lg:p-8 ">
					<h3 class="text-2xl text-center"><?php echo esc_html__( 'Template Preview', 'email-subscribers' ); ?></h3>
					<p class="m-4 text-center"><?php echo esc_html__( 'There could be a slight variation on how your customer will view the email content.', 'email-subscribers' ); ?></p>
					<div class="m-4 list-decimal report_preview_container">
					</div>
					<div class="flex justify-center mt-8">
						<button id="es_close_preview" class="px-4 py-2 text-sm font-medium tracking-wide text-gray-700 border rounded select-none no-outline focus:outline-none focus:shadow-outline-red hover:border-red-400 active:shadow-lg "><?php echo esc_html__( 'Close', 'email-subscribers' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Prepare search box
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @since 4.6.5
	 */
	public function search_box( $text = '', $input_id = '' ) {
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Reports', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<p class="search-box search-group-box box-ma10">
			<?php
			$filter_by_status = ig_es_get_request_data( 'filter_reports_by_status' );
			?>
			<select name="filter_reports_by_status" id="ig_es_filter_report_by_status">
				<?php
				$allowedtags = ig_es_allowed_html_tags_in_esc();
				add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );
				$statuses               = array(
					'Sent'     => __( 'Completed', 'email-subscribers' ),
					'In Queue' => __( 'In Queue', 'email-subscribers' ),
					'Sending'  => __( 'Sending', 'email-subscribers' ),
				);
				$campaign_report_status = ES_Common::prepare_campaign_report_statuses_dropdown_options( $statuses, $filter_by_status, __( 'All Status', 'email-subscribers' ) );
				echo wp_kses( $campaign_report_status, $allowedtags );
				?>
			</select>
		</p>
		<p class="search-box search-group-box box-ma10">
			<?php $filter_by_campaign_type = ig_es_get_request_data( 'filter_reports_by_campaign_type' ); ?>
			<select name="filter_reports_by_campaign_type" id="ig_es_filter_reports_by_campaign_type">
				<?php
				$campaign_report_type = ES_Common::prepare_campaign_type_dropdown_options( $filter_by_campaign_type, __( 'All Type', 'email-subscribers' ) );
				echo wp_kses( $campaign_report_type, $allowedtags );
				?>
			</select>
		</p>
		<p class="search-box search-group-box box-ma10">
			<?php $filter_by_date = ig_es_get_request_data( 'filter_reports_by_date' ); ?>
			<select name = "filter_reports_by_date" id="ig_es_filter_report_by_date">
				<?php 
				$filter_by_monthyear = ES_COMMON::prepare_datefilter_dropdown_options( $filter_by_date , __('All Dates', 'email-subscribers'));
				echo wp_kses( $filter_by_monthyear, $allowedtags);
				?>
			</select>	
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
