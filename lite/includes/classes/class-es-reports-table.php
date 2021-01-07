<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ES_Reports_Table extends WP_List_Table {

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

	}

	public function es_reports_callback() {

		$campaign_id   = ig_es_get_request_data( 'campaign_id' );
		$campaign_type = '';
		//Since, currently we are not passing campaign_id with broadcast $campaign_type will remain empty for broadcast
		if ( ! empty ( $campaign_id ) ) {
			$campaign_type = ES()->campaigns_db->get_campaign_type_by_id( $campaign_id );
		}

		$campaign_types = array('sequence', 'sequence_message');
		//Only if it is sequence then control will transfer to Sequence Reports class.
		if ( ! empty ( $campaign_type ) && in_array( $campaign_type, $campaign_types ) ) {
			if ( ES()->is_pro() ) { 
				$reports = ES_Pro_Sequence_Reports::get_instance();
				$reports->es_sequence_reports_callback();
			} else {
				do_action( 'ig_es_view_report_data' );
			}
		} else {
			$action = ig_es_get_request_data( 'action' );
			if ( 'view' === $action ) {
				$list = ig_es_get_request_data( 'list' );
				$this->view_list( $list );
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
								$content = sprintf( __( "<a href='%s' class='px-3 py-2 ig-es-imp-button'>Send Queued Emails Now</a>", 'email-subscribers' ), $cron_url );
							} else {
								$content  = sprintf( __( "<span class='ig-es-send-queue-emails px-3 button-disabled'>Send Queued Emails Now</span>", 'email-subscribers' ) );
								$content .= sprintf( __( "<br /><span class='es-helper pl-6'>No emails found in queue</span>", 'email-subscribers' ) );
							}
							?>
							<div class="flex flex-row">
								<div>
									<span class="ig-es-process-queue"><?php echo wp_kses_post( $content ); ?></span>
								</div>
							</div>
						</div>
					</header>
					<div><hr class="wp-header-end"></div>
					<div id="poststuff" class="es-items-lists">
						<div id="post-body" class="metabox-holder column-1">
							<div id="post-body-content">
								<div class="meta-box-sortables ui-sortable">
									<form method="get">
										<input type="hidden" name="page" value="es_reports" />
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

	public function prepare_header_footer_row() {

		?>
		<tr>
			<th width="8%" class="py-3 pl-4 es_reports_table_header"><?php esc_html_e( 'Sr No', 'email-subscribers' ); ?></th>
			<th width="24%" class="py-3 pl-4 es_reports_table_header"><?php esc_html_e( 'Email', 'email-subscribers' ); ?></th>
			<th width="12%" class=" py-3 pl-6 es_reports_table_header"><?php esc_html_e( 'Status', 'email-subscribers' ); ?></th>
			<th width="22%" class="py-3 pl-2 es_reports_table_header"><?php esc_html_e( 'Sent Date', 'email-subscribers' ); ?></th>
			<th width="17%" class="py-3 pl-6 es_reports_table_header"><?php esc_html_e( 'Viewed Status', 'email-subscribers' ); ?></th>
			<th width="22%" class=" py-3 pl-6 es_reports_table_header"><?php esc_html_e( 'Viewed Date', 'email-subscribers' ); ?></th>
		</tr>

		<?php
	}

	public function view_list( $id ) {
		$emails             = ES_DB_Sending_Queue::get_emails_by_hash( $id );
		$email_viewed_count = ES_DB_Sending_Queue::get_viewed_count_by_hash( $id );
		$total_email_sent   = ES_DB_Sending_Queue::get_total_email_count_by_hash( $id );


		$insight  = ig_es_get_request_data( 'insight', '' );
		$_wpnonce = ig_es_get_request_data( '_wpnonce', '' );

		if ( ES()->is_pro() || $insight ) {
			do_action( 'ig_es_view_report_data', $id, $emails );
		}
		?>
		<div class="wrap">
			<div class="mt-6 mb-2 max-w-7xl">
				<div class="pt-3">
					<span class="text-left text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'View activity ', 'email-subscribers' ); ?></span>

					<?php if ( ! ES()->is_pro() && ! $insight ) { ?>
						<a href="?page=es_reports&action=view&list=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo esc_attr( $_wpnonce ); ?>&insight=true" class="float-right ig-es-title-button px-2 py-2 mx-2 ig-es-imp-button cursor-pointer"><?php esc_html_e( 'Campaign Analytics', 'email-subscribers' ); ?></a>
					<?php } ?>
				</div>
			</div>

			<div class="mt-2 mb-2 block">
				<span class="pt-3 pb-4 leading-5 tracking-wide text-gray-600"><?php echo esc_html('Viewed ' . $email_viewed_count . '/' . $total_email_sent ); ?>
				</span>
			</div>

			<div class="mb-2 max-w-7xl flex">
				<div class="flex w-full bg-white shadow rounded-md break-all">

					<form name="frm_es_display" method="post">
						<table class="w-full table-fixed">
							<thead>
							<?php echo wp_kses_post( $this->prepare_header_footer_row() ); ?>
							</thead>
							<tbody>
							<?php echo wp_kses_post( $this->prepare_body( $emails ) ); ?>
							</tbody>
							<tfoot>
							<?php echo wp_kses_post( $this->prepare_header_footer_row() ); ?>
							</tfoot>
						</table>
					</form>
				</div>
			</div>
		</div>
		<?php
		// $wpdb->update( EMAIL_SUBSCRIBERS_STATS_TABLE, array( 'viewdate' => date( 'Y-m-d H:i:s' ) ), array( 'viewdate' => $id ) );
	}


	public function prepare_body( $emails ) {

		$i = 1;
		foreach ( $emails as $key => $email ) {
			$class = '';
			if ( 0 === $i % 2 ) {
				$class = 'alternate';
			}

			$email_id  = ! empty( $email['email'] ) ? $email['email'] : ( ! empty( $email['es_deliver_emailmail'] ) ? $email['es_deliver_emailmail'] : '' );
			$status    = ! empty( $email['status'] ) ? $email['status'] : ( ! empty( $email['es_deliver_sentstatus'] ) ? $email['es_deliver_sentstatus'] : '' );
			$sent_at   = ! empty( $email['sent_at'] ) ? $email['sent_at'] : ( ! empty( $email['es_deliver_sentdate'] ) ? $email['es_deliver_sentdate'] : '' );
			$opened    = ! empty( $email['opened'] ) ? $email['opened'] : ( ! empty( $email['es_deliver_status'] ) && 'Viewed' === $email['es_deliver_status'] ? 1 : 0 );
			$opened_at = ! empty( $email['opened_at'] ) ? $email['opened_at'] : ( ! empty( $email['es_deliver_viewdate'] ) ? $email['es_deliver_viewdate'] : '' );

			?>

			<tr>
				<td class="pl-6 py-2 border-b border-gray-200 text-sm leading-5 text-gray-500"><?php echo esc_html( $i ); ?></td>
				<td class="pl-4 py-2 border-b border-gray-200 text-sm leading-5 text-gray-600"><?php echo esc_html( $email_id ); ?></td>
				<td class="pl-6 pr-2 py-2 border-b border-gray-200 text-sm leading-5 text-gray-500"><span style="color:#03a025;font-weight:bold;"><?php echo esc_html( $status ); ?></span></td>
				<td class="pl-2 pr-2 py-2 border-b border-gray-200 text-sm leading-5 text-gray-500"><?php echo wp_kses_post( ig_es_format_date_time( $sent_at ) ); ?></td>
				<td class="pl-6 pr-2 py-2 border-b border-gray-200 text-sm leading-5 text-gray-600"><span>
				<?php 
				/* translators: 1: Italic tag 2: Class attribute */
				echo ! empty( $opened ) && 1 == $opened ? esc_html__( 'Viewed', 'email-subscribers' ) : wp_kses_post( '<i title="Not yet viewed" class="dashicons dashicons-es dashicons-minus"/>' ); 
				?>
				</span></td>
				<td class="pl-6 pr-1 py-2 border-b border-gray-200 text-sm leading-5 text-gray-500"><?php echo wp_kses_post(ig_es_format_date_time( $opened_at ) ); ?></td>
			</tr>

			<?php
			$i ++;
		}

	}


	/** Text displayed when no list data is available */
	public function no_items() {
		esc_html_e( 'No Reports avaliable.', 'email-subscribers' );
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
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}

	public function column_status( $item ) {
		if ( 'Sent' == $item['status'] ) {
			return __( 'Completed', 'email-subscribers' );
		} else {

			$actions = array(
				'send_now' => $this->prepare_send_now_url( $item ),
			);

			return $item['status'] . $this->row_actions( $actions, true );
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
			'delete'        => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">%s</a>', esc_attr( $page ), 'delete', absint( $item['id'] ), $es_nonce, __( 'Delete', 'email-subscribers' ) ),
			'preview_email' => sprintf( '<a target="_blank" href="?page=%s&action=%s&list=%s&_wpnonce=%s" class="text-indigo-600">%s</a>', esc_attr( $page ), 'preview', absint( $item['id'] ), $es_nonce, __( 'Preview', 'email-subscribers' ) ),

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
			'cb'        => '<input type="checkbox" />',
			'subject'   => __( 'Subject', 'email-subscribers' ),
			'type'      => __( 'Type', 'email-subscribers' ),
			'status'    => __( 'Status', 'email-subscribers' ),
			'start_at'  => __( 'Start Date', 'email-subscribers' ),
			'finish_at' => __( 'End Date', 'email-subscribers' ),
			'count'     => __( 'Total contacts', 'email-subscribers' ),
		);

		return $columns;
	}

	public function column_count( $item ) {

		$campaign_hash = $item['hash'];

		$total_emails_sent 		 = $item['count'];
		$total_emails_to_be_sent = $item['count'];
		// if ( ! empty( $campaign_hash ) ) {
		// $total_emails_sent = ES_DB_Sending_Queue::get_total_emails_sent_by_hash( $campaign_hash );
		// }

		// $content = $total_emails_sent . "/" . $total_emails_to_be_sent;

		return $total_emails_to_be_sent;

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
			$content = __( sprintf( "<a href='%s' target='_blank'>Send</a>", $cron_url ), 'email-subscribers' );
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
		global $wpdb,$wpbd;

		$order_by   			   			= sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order       			   			= ig_es_get_request_data( 'order' );
		$campaign_id 			   			= ig_es_get_request_data( 'campaign_id' );
		$search                    			= ig_es_get_request_data( 's' );
		$filter_reports_by_campaign_status  = ig_es_get_request_data( 'filter_reports_by_status' );
		$filter_reports_by_campaign_type    = ig_es_get_request_data( 'filter_reports_by_campaign_type' );

		$ig_mailing_queue_table = IG_MAILING_QUEUE_TABLE;

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$ig_mailing_queue_table}";
		} else {
			$sql = "SELECT * FROM {$ig_mailing_queue_table}";
		}

		$where_columns = array();
		$where_args    = array();
		$add_where_clause = true;

		if ( ! empty( $campaign_id ) && is_numeric( $campaign_id ) ) {
			$where_columns[] = 'campaign_id = %d';
			$where_args[]    = $campaign_id;
		}

		$where_query = '';
		if ( ! empty( $where_columns ) ) {
			$where_query = implode( ' AND ', $where_columns );
			$where_query = $wpbd->prepare( $where_query, $where_args );
		}

		if ( ! empty( $where_query ) ) {
			$sql .= ' WHERE ' . $where_query;
			$add_where_clause = false;
		}

		if ( ! empty( $filter_reports_by_campaign_status ) || ( '0' === $filter_reports_by_campaign_status ) ) {
			if ( ! $add_where_clause ) {
				$sql .= $wpdb->prepare( ' AND status = %s', $filter_reports_by_campaign_status );
			} else {
				$sql .= $wpdb->prepare( ' WHERE status = %s', $filter_reports_by_campaign_status );
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
		$allowedtags 			= ig_es_allowed_html_tags_in_esc();
		// Detect when a bulk action is being triggered...
		if ( 'view' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to view notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$this->view_list( ig_es_get_request_data( 'list' ) );
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
		} elseif ( 'preview' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to preview notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$report_id = ig_es_get_request_data( 'list' );
				echo wp_kses( $this->preview_email( $report_id ), $allowedtags );
				die();
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

	public function preview_email( $report_id ) {
		ob_start();
		$allowedtags 			= ig_es_allowed_html_tags_in_esc();
		add_filter( 'safe_style_css', 'ig_es_allowed_css_style' ); 
		?>
		<div class="wrap">
			<h2 style="margin-bottom:1em;">
				<?php esc_html_e( 'Preview Email', 'email-subscribers' ); ?>
			</h2>
			<p>
				<?php echo wp_kses_post( __( 'This is how the email you sent may look. <br>Note: Different email services (like gmail, yahoo etc) display email content differently. So there could be a slight variation on how your customer will view the email content.', 'email-subscribers' ) ); ?>
			</p>
			<div class="tool-box">
				<div style="padding:15px;background-color:#FFFFFF;">
					<?php
					$preview = array();
					$preview = ES_DB_Mailing_Queue::get_email_by_id( $report_id );

					$es_email_type = get_option( 'ig_es_email_type' );    // Not the ideal way. Email type can differ while previewing sent email.

					if ( 'WP HTML MAIL' == $es_email_type || 'PHP HTML MAIL' == $es_email_type ) {
						$preview['body'] = ES_Common::es_process_template_body( $preview['body'] );
					} else {
						$preview['body'] = str_replace( '<br />', "\r\n", $preview['body'] );
						$preview['body'] = str_replace( '<br>', "\r\n", $preview['body'] );
					}

					echo wp_kses( stripslashes( $preview['body'] ), $allowedtags );
					?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;

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
				$allowedtags 			= ig_es_allowed_html_tags_in_esc();
				add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );
				$campaign_report_status = ES_Common::prepare_campaign_report_statuses_dropdown_options( $filter_by_status, __( 'All Status', 'email-subscribers' ) ); 
				echo wp_kses( $campaign_report_status , $allowedtags ); 
				?>
			</select>
		</p>
		<p class="search-box search-group-box box-ma10">
			<?php $filter_by_campaign_type = ig_es_get_request_data( 'filter_reports_by_campaign_type' ); ?>
			<select name="filter_reports_by_campaign_type" id="ig_es_filter_reports_by_campaign_type">
				<?php 
				$campaign_report_type = ES_Common::prepare_campaign_type_dropdown_options( $filter_by_campaign_type, __( 'All Type', 'email-subscribers' ) );
				echo wp_kses( $campaign_report_type , $allowedtags ); 
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
