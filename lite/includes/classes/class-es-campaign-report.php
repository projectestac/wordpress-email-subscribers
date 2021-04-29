<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Campaign_Report extends ES_List_Table {

	public static $instance;

	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Report', 'email-subscribers' ), // singular name of the listed records
				'plural'   => __( 'Reports', 'email-subscribers' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?,
				'screen'   => 'es_reports',
			)
		);

		add_action( 'ig_es_view_activity_table_html', array( $this, 'view_activity_report_table' ), 10, 3 );
		add_action( 'admin_footer', array( $this, 'es_view_activity_report_sort_and_filter') );		
	}

	public function es_campaign_report_callback() {
		?>
		
		<?php
		$this->ajax_response();
		?>
		<div id="poststuff" class="es-items-lists es-campaign-reports-table">
			<div id="post-body" class="metabox-holder column-1">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="get" class="es_campaign_report" id="es_campaign_report">
							<input type="hidden" name="order" />
							<input type="hidden" name="orderby" />
							<div class="mb-2 max-w-7xl">
								<div>
									<p class="text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Activity Info', 'email-subscribers' ); ?></p>
								</div>
						</div>
							<?php $this->display(); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
	<?php	
	}

	public function ajax_response() {

		$this->prepare_items();
		$no_placeholder = ig_es_get_request_data( 'no_placeholder', '' );
		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $no_placeholder ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		$response = array( 'rows' => $rows );
		
		$response['column_headers'] = $headers;

		if ( isset( $total_items ) ) {
			/* translators: %s: Total items in the table */
			$response['total_items_i18n'] = sprintf( _n( '%s item', '%s items', $total_items, 'email-subscribers' ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			die( json_encode( $response ));
			
		} else {
			return $response;
		}
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'email'	 		=> array( 'email', false ),
			'country_flag'	=> array( 'country_flag', false ),
			'os'   			=> array( 'os', false ),
			'email_client'  => array( 'email_client', false ),
			'sent_at' 		=> array( 'sent_at', false ),
			'opened_at' 	=> array( 'opened_at', false ),
			'status'		=> array( 'status', false ),
		);

		return $sortable_columns;
	}
	

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
			
		$this->_column_headers = array($columns, $hidden, $sortable);
		// Search box
		
		$data = $this->get_activity_table_data();
		if ( ! empty( $data ) ) {
			usort( $data, array( $this,'usort_reorder') );
		}
		
		$this->items = $data;
	}

	/**
	 * Handles sorting of the data
	 */
	public function usort_reorder( $a, $b ) {
			$orderby_colname = ig_es_get_request_data( 'orderby', '' );
			$col_order		 = ig_es_get_request_data( 'order', '' );
			$orderby 		 = ( ! empty( $orderby_colname ) ) ? $orderby_colname : 'opened_at';
			$order 			 = ( ! empty( $col_order ) ) ? $col_order : 'desc';
			$result 		 = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * Method to handle display of WP_List table 
	 *
	 * @Override of display method
	 */
	public function display() {
		$search = ig_es_get_request_data( 's' );
		$this->search_box( $search, 'campaign-reports-search-input' );
		parent::display();
	}

	/**
	 * Prepare search box
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @since 4.6.12
	 */
	public function search_box( $text = '', $input_id = '' ) {
		do_action( 'ig_es_campaign_reports_filter_options', $text, $input_id  );
	}

	
	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'sr_no'        	=> __( 'Sr No', 'email-subscribers' ),
			'email'   		=> __( 'Email', 'email-subscribers' ),
			'status'      	=> __( 'Status', 'email-subscribers' ),
			'sent_at'   	=> __( 'Sent Date', 'email-subscribers' ),
			'opened_at'  	=> __( 'Viewed Date', 'email-subscribers' ),
		
		);

		$columns = apply_filters( 'additional_es_campaign_report_columns', $columns );

		return $columns;
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
			case 'sr_no':
				break;
			case 'email':
				return $item['email'];
			case 'sent_at':
				return ig_es_format_date_time( $item['sent_at'] );
			case 'opened_at':
				return ig_es_format_date_time( $item['opened_at'] );
			default:
				$column_data = ! empty( $item[ $column_name ] ) ? $item[ $column_name ] : '-';
				return $column_data;
		}
	}

	public function column_status( $item ) {
		$status    = ! empty( $item['status'] ) ? $item['status'] : ( ! empty( $item['es_deliver_sentstatus'] ) ? $item['es_deliver_sentstatus'] : '' );

		switch ( $status ) {
			case 'Sent': 
				?>
				<svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
					<title><?php echo esc_html__( 'Sent', 'email-subscribers' ); ?></title>
					<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
				</svg>
				<?php
				break;
			case 'In Queue': 
				?>
				<svg class=" h-6 w-6 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'In Queue', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
			</svg>
				<?php
				break;
			case 'Sending': 
				?>
				<svg class=" h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'Sending', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
			</svg>
				<?php
				break;
			case 'Opened': 
				?>
				<svg xmlns="http://www.w3.org/2000/svg" class="" width="28" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color:green">
					<title><?php echo esc_html__( 'Opened', 'email-subscribers' ); ?></title>
					  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
					  <path d="M7 12l5 5l10 -10" />
					  <path d="M2 12l5 5m5 -5l5 -5" />
				</svg>
				<?php
				break;
			case '': 
				?>
				<i class="dashicons dashicons-es dashicons-minus"/>
				<?php
				break;
			default: 
				echo esc_html( $status );
				break;

		}
	}

	/**
	 * Get view activity table data
	 *
	 */
	public function get_activity_table_data() {

		global $wpbd;

		$hash  					= ig_es_get_request_data( 'list', '' );
		$campaign_id   			= ig_es_get_request_data( 'campaign_id', '' );
		$filter_by_status  		= ig_es_get_request_data( 'status', '' );
		$filter_by_country   	= ig_es_get_request_data( 'country_code', '' );
		$orderby   				= ig_es_get_request_data( 'orderby', 'created_at' );
		$order   				= ig_es_get_request_data( 'order', 'desc' );
		$message_id 			= 0;
		$queue_data 			= array();
		$view_activity_data 	= array();

		if ( ! empty( $hash )  ) {
			$notification_data_from_hash = ES_DB_Mailing_Queue::get_notification_by_hash( $hash );
			$campaign_id 				 = $notification_data_from_hash['campaign_id'];
			$message_id  				 = $notification_data_from_hash['id'];
			$queue_data             	 = ES_DB_Sending_Queue::get_queue_data( $campaign_id, $message_id );
		}
		
		$notification       = $wpbd->get_row( $wpbd->prepare( "SELECT * FROM {$wpbd->prefix}ig_campaigns WHERE `id`= %d", $campaign_id ), ARRAY_A );
		$total_email_sent   = ES()->actions_db->get_count_based_on_id_type( $notification['id'], $message_id, IG_MESSAGE_SENT );
		$email_viewed_count = ES()->actions_db->get_count_based_on_id_type( $notification['id'], $message_id, IG_MESSAGE_OPEN );
		$email_click_count  = ES()->actions_db->get_count_based_on_id_type( $notification['id'], $message_id, IG_LINK_CLICK );
		
		$where      		= $wpbd->prepare( 'campaign_id = %d AND message_id = %d', $campaign_id, $message_id );

		if ( ! empty( $filter_by_status) ) {
			if ( 'opened' === $filter_by_status ) {
				$contact_report_status = array( IG_MESSAGE_SENT, IG_MESSAGE_OPEN );
				$where .=  $wpbd->prepare( " AND contact_id IN (select contact_id from {$wpbd->prefix}ig_actions where type = %d AND campaign_id = %d AND message_id = %d)", IG_MESSAGE_OPEN, $campaign_id, $message_id);
			} elseif ( 'not_opened' === $filter_by_status ) {

				$contact_report_status = array( IG_MESSAGE_SENT );
				$where .=  $wpbd->prepare( " AND contact_id NOT IN (SELECT contact_id from {$wpbd->prefix}ig_actions where type = %d AND campaign_id = %d AND message_id = %d)", IG_MESSAGE_OPEN , $campaign_id, $message_id);
			}
			$type_count        = count( $contact_report_status );
			$type_placeholders = array_fill( 0, $type_count, '%d' );
			$where .= $wpbd->prepare( ' AND type IN( ' . implode( ',', $type_placeholders ) . ' )', $contact_report_status );
			
		}

		if ( ! empty( $filter_by_country ) ) {
				$where .=  $wpbd->prepare( " AND contact_id IN (SELECT contact_id from {$wpbd->prefix}ig_actions where type = %d AND country = %s AND campaign_id = %d AND message_id = %d)", IG_MESSAGE_OPEN , $filter_by_country, $campaign_id, $message_id);
		}

		$where .= $wpbd->prepare( ' AND %d ORDER BY updated_at DESC', 1 );

		$notification_actions = ES()->actions_db->get_by_conditions( $where );
		
		$contact_ids_arr = array_column( $notification_actions, 'contact_id' );
		if ( ! empty( $contact_ids_arr ) ) {
			$contacts_data = ES()->contacts_db->get_details_by_ids( $contact_ids_arr );
			if ( ! empty( $notification_actions ) ) {				
				foreach ( $notification_actions as $notification_action ) {
					$action_type = (int) $notification_action['type'];

					$contact_id = $notification_action['contact_id'];

					if ( ! isset( $view_activity_data[$contact_id] ) ) {
						$view_activity_data[$contact_id] = array(
							'email'		   => ! empty( $contacts_data[$contact_id]['email'] ) ? $contacts_data[$contact_id]['email'] : '',
							'opened'	   => 0,
							'opened_at'    => 0,
							'status' 	   => 0,
							'sent_at'  	   => 0,
							'country_flag' => '',
							'device'       => '',
							'email_client' => '',
							'os'           => '',
						);
					}
					if ( IG_MESSAGE_OPEN === $action_type ) {
						$view_activity_data[$contact_id]['opened'] = 'Viewed';
						$view_activity_data[$contact_id]['status'] = 'Opened';
						$view_activity_data[$contact_id]['opened_at']   = ! empty( $notification_action['created_at'] ) ? ES_Common::convert_timestamp_to_date( $notification_action['created_at'], 'Y-m-d H:i:s' ) : '' ;
						
						$view_activity_data = apply_filters( 'additional_es_report_activity_data', $view_activity_data, $contact_id, $notification_action );
					} elseif ( IG_MESSAGE_SENT === $action_type ) {
						if ( empty( $view_activity_data[$contact_id]['status'] ) ) {
						   $view_activity_data[$contact_id]['status'] = 'Sent';
						}
						   $view_activity_data[$contact_id]['sent_at']     =  ! empty( $notification_action['created_at'] ) ? ES_Common::convert_timestamp_to_date( $notification_action['created_at'], 'Y-m-d H:i:s' ) : '' ;
					}

				}
			}
		}

		if ( ! empty( $queue_data ) ) {
			foreach ( $queue_data as $data ) {
				$contact_id = $data['contact_id'];

				if ( ! isset( $view_activity_data[$contact_id] ) ) {
					$view_activity_data[$contact_id] = array(
						'email'		   => ! empty( $data['email'] ) ? $data['email'] : '',
						'opened'	   => 0,
						'opened_at'    => 0,
						'status' 	   => ! empty( $data['status'] ) ? $data['status'] : '',
						'sent_at'  	   => ! empty( $data['sent_at'] ) ? $data['sent_at'] : '',
						'country_flag' => '',
						'device'       => '',
						'email_client' => '',
						'os'           => '',
					);
				}
			}
		}

		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$insight  = ig_es_get_request_data( 'insight', '' );
			$_wpnonce = ig_es_get_request_data( '_wpnonce', '' );
			
			if ( ( ES()->is_pro() || $insight ) && 0 !== $message_id ) {
				do_action( 'ig_es_view_report_data', $hash );
			}
			?>

			<div class="wrap">
				<?php if ( ! ES()->is_pro() && ! $insight ) { ?>
					<a href="?page=es_reports&action=view&list=<?php echo esc_attr( $hash ); ?>&_wpnonce=<?php echo esc_attr( $_wpnonce ); ?>&insight=true" class="float-right top-10 relative ig-es-title-button px-2 py-2 mx-2 ig-es-imp-button cursor-pointer"><?php esc_html_e( 'Campaign Analytics', 'email-subscribers' ); ?></a>
				<?php } ?>
			</div>
			<div class="mt-2 mb-2 inline-block relative top-20">
				<span class="pt-3 pb-4 leading-5 tracking-wide text-gray-600"><?php echo esc_html( 'Viewed ' . $email_viewed_count . '/' . $total_email_sent ); ?>
				</span>
			</div>
			<?php
		}
		 return $view_activity_data;

	}

	/**
	 * Handling filtering and sorting for view activity table
	 */
	public function es_view_activity_report_sort_and_filter() {
		$hash  			= ig_es_get_request_data( 'list', '' );
		$campaign_id   	= ig_es_get_request_data( 'campaign_id', '' );

		?>

		<script type="text/javascript">

		(function ($) {

			$(document).ready(

				function () {
							
					$('#es_campaign_report').on('click', '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a', function (e) {
						e.preventDefault();
						var query = this.search.substring(1);
						var order = list.__query( query, 'order' ) || 'desc';
						var orderby = list.__query( query, 'orderby' ) || 'opened_at';
						$("input[name='order']").val(order);
						$("input[name='orderby']").val(orderby);
						check_filter_value();
						
					});

					$('#es-activity-report-filter').on('click', function (e) {
						e.preventDefault();
						
						check_filter_value();
					});
				});

		
				list = {

					/** AJAX call
					 *
					 * Send the call and replace table parts with updated version!
					 *
					 * @param    object    data The data to pass through AJAX
					 */
					update: function (data) {

						$.ajax({

							url: ajaxurl,
							data: $.extend(
								{
									action: 'ajax_fetch_report_list',
								},
								data
							),
							beforeSend: function(){
								$('#es_campaign_report table.wp-list-table.widefat.fixed.striped.table-view-list.reports tbody').addClass('animate-pulse').css({'filter': 'blur(1px)', '-webkit-filter' : 'blur(1px)'});
							},
							success: function (response) {
								var response = $.parseJSON(response);

								if (response.rows.length)
									$('#the-list').html(response.rows);
								if (response.column_headers.length)
									$('#es_campaign_report thead tr, #es_campaign_report tfoot tr').html(response.column_headers);
							},
							error: function (err) {
	
							}
						}).always(function(){
							$('#es_campaign_report table.wp-list-table.widefat.fixed.striped.table-view-list.reports tbody').removeClass('animate-pulse').css({'filter': 'blur(0px)', '-webkit-filter' : 'blur(0px)'});
						});
					},

					/**
					 * Filter the URL Query to extract variables
					 *
					 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
					 *
					 * @param    string    query The URL query part containing the variables
					 * @param    string    variable Name of the variable we want to get
					 *
					 * @return   string|boolean The variable value if available, false else.
					 */
					__query: function (query, variable) {

						var vars = query.split("&");
						for (var i = 0; i < vars.length; i++) {
							var pair = vars[i].split("=");
							if (pair[0] == variable)
								return pair[1];
						}
						return false;
					},
				}


				function check_filter_value( filter_value = '' ){
						var country_code 			= $('#ig_es_filter_activity_report_by_country').val();
						var report_activity_status 	= $('#ig_es_filter_activity_report_by_status').val();
						var order = $("input[name='order']").val();
						var orderby = $("input[name='orderby']").val();
						
						data = 
						{
							list : "<?php echo esc_html( $hash ); ?>",
							campaign_id : <?php echo ( ! empty( $campaign_id ) ? esc_html( $campaign_id ) : 0 ); ?>,
							order : order,
							orderby : orderby,
							country_code : country_code,
							status : report_activity_status

						};
						
						list.update(data);
				}
			})(jQuery);

		</script>
		<?php
	}


}
