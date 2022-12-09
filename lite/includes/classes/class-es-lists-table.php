<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Lists_Table extends ES_List_Table {
	/**
	 * ES_DB_Lists object
	 *
	 * @since 4.2.1
	 * @var $db
	 */
	protected $db;
	/**
	 * Numbers of list options per page
	 *
	 * @since 4.2.1
	 * @var string
	 */
	public static $option_per_page = 'es_lists_per_page';

	/**
	 * ES_Lists_Table constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'List', 'email-subscribers' ), // singular name of the listed records
				'plural'   => __( 'Lists', 'email-subscribers' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?,
				'screen'   => 'es_lists',
			)
		);

		$this->db = new ES_DB_Lists();
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Number of lists per page', 'email-subscribers' ),
			'default' => 20,
			'option'  => self::$option_per_page,
		);

		add_screen_option( $option, $args );

	}

	public function render() {

		$action = ig_es_get_request_data( 'action' );

		?>
		<div class="wrap pt-4 font-sans">
			<?php
			if ( 'new' === $action ) {
				$this->es_new_lists_callback();
			} elseif ( 'edit' === $action ) {
				$list = ig_es_get_request_data( 'list' );
				echo wp_kses_post( $this->edit_list( absint( $list ) ) );
			} else {
				?>

		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="flex">
					<div>
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
								<a class="hover:underline " href="admin.php?page=es_subscribers"><?php esc_html_e( 'Audience ', 'email-subscribers' ); ?></a>
								<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
								</li>
							</ol>
						</nav>
						<h2 class="-mt-1.5 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate"> <?php esc_html_e( 'Lists', 'email-subscribers' ); ?>
						</h2>
					</div>
					<div class="mt-4"> <a href="admin.php?page=es_lists&action=new" class="ig-es-title-button ml-2"><?php esc_html_e( 'Add New', 'email-subscribers' ); ?></a>
					</div>
				</div>
			</header>
			<div><hr class="wp-header-end"></div>
			<div id="poststuff" class="es-items-lists es-lists-table">
				<div id="post-body" class="metabox-holder column-1">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="get">
								<input type="hidden" name="page" value="es_lists" />
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

	/**
	 * Validate data
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function validate_data( $data ) {

		$nonce     = $data['nonce'];
		$list_name = $data['list_name'];
		$list_id   = ig_es_get_request_data('list');
		
		$existing_list_data  = $this->db->get_list_by_name( $list_name );
		
		$status  = 'error';
		$message = '';
		if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
			$message = __( 'You do not have permission to edit list', 'email-subscribers' );
		} elseif ( empty( $list_name ) ) {
			$message = __( 'Please add list name', 'email-subscribers' );
		} elseif ( $this->db->is_list_exists( $list_name ) && isset( $existing_list_data['id'] ) && $existing_list_data['id'] != $list_id  ) {
			$message = __( 'List already exists. Please choose a different name', 'email-subscribers' );
		} else {
			$status = 'success';
		}

		$response = array(
			'status'  => $status,
			'message' => $message,
		);

		return $response;

	}

	public function es_new_lists_callback() {

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = ig_es_get_request_data( '_wpnonce' );
			$list_name = ig_es_get_request_data( 'list_name' );
			$list_desc = ig_es_get_request_data( 'list_desc' );

			$validate_data = array(
				'nonce'     => $nonce,
				'list_name' => $list_name,
				'list_desc' => $list_desc,
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				ES_Common::show_message( $message, 'error' );
				$this->prepare_list_form( null, $validate_data );

				return;
			}

			$data = array(
				'list_name' => $list_name,
				'list_desc' => $list_desc,
			);

			$save = $this->save_list( null, $data );

			if ( $save ) {
				$message = __( 'List added successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		$this->prepare_list_form();
	}

	/**
	 * Edit List
	 *
	 * @param $id
	 *
	 * @since 4.0.0
	 */
	public function edit_list( $id ) {

		$list = $this->db->get( $id );

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = ig_es_get_request_data( '_wpnonce' );
			$list_name = ig_es_get_request_data( 'list_name' );
			$list_desc = ig_es_get_request_data( 'list_desc' );

			$validate_data = array(
				'nonce'     => $nonce,
				'list_name' => $list_name,
				'list_desc' => $list_desc,
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				ES_Common::show_message( $message, 'error' );
				$this->prepare_list_form( $id, $validate_data );

				return;
			}

			$data = array(
				'list_name' => $list_name,
				'list_desc' => $list_desc,
				'hash'      => isset( $list['hash'] ) ? $list['hash'] : '',
			);

			$save = $this->save_list( $id, $data );
			if ( $save ) {
				$message = __( 'List updated successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		} else {

			$id = $list['id'];

			$data = array(
				'list_name' => $list['name'],
				'list_desc' => $list['description'],
				'hash' 		=> $list['hash'],
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
		$list_desc = isset( $data['list_desc'] ) ? $data['list_desc'] : '';
		$nonce = wp_create_nonce( 'es_list' );

		?>

		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="md:flex md:items-center md:justify-between justify-center">
					<div class="flex-1 min-w-0">
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
								<a class="hover:underline " href="admin.php?page=es_subscribers"><?php esc_html_e( 'Audience ', 'email-subscribers' ); ?></a>
								<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>

								<a class="hover:underline" href="admin.php?page=es_lists&action=manage-lists"><?php esc_html_e( ' Lists ', 'email-subscribers' ); ?></a> 
								 <svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
								 </li>
							</ol>
						</nav>
						<h2 class="-mt-1 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate">
								<?php
								if ( $is_new ) {
									esc_html_e( 'Add New List', 'email-subscribers' );
								} else {
									esc_html_e( 'Edit List', 'email-subscribers' );
								}

								?>
						</h2>
					</div>
				</div>
			</header>
				<div><hr class="wp-header-end"></div>
				<div class="rounded max-w-full ">
					<div id="poststuff">
						<div id="post-body" class="metabox-holder column-1 mt-0.5">
							<div id="post-body-content">
								<div class="bg-white shadow-md rounded-lg mt-5">
									<form class="ml-5 mr-4 text-left pt-8 mt-2 item-center " method="post" action="admin.php?page=es_lists&action=<?php echo esc_attr( $action ); ?>&list=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>">

										<div class="flex flex-row border-b border-gray-100">
											<div class="flex w-1/5">
												<div class="ml-4 pt-6 px-3	">
													<label for="name" class="block text-sm leading-5 font-medium text-gray-600"><?php esc_html_e( 'List name', 'email-subscribers' ); ?></label>
												</div>
											</div>
											<div class="flex">
												<div class="ml-16 mb-4 h-10 mr-4 mt-4">
													<div class="h-10 relative">

														<input class="form-input block border-gray-400 w-full pl-3 pr-12 focus:bg-gray-100 sm:text-sm sm:leading-5" placeholder="<?php echo esc_html__( 'Enter list name', 'email-subscribers' ); ?>" id="name" name="list_name" value="<?php echo esc_attr( $list_name ); ?>"/>
													</div>
												</div>
											</div>
										</div>

										<div class="flex flex-row border-b border-gray-100">
											<div class="flex w-1/5">
												<div class="ml-4 pt-6 px-3	">
													<label for="name" class="block text-sm leading-5 font-medium text-gray-600"><?php esc_html_e( 'Description', 'email-subscribers' ); ?></label>
												</div>
											</div>
											<div class="flex">
												<div class="ml-16 mb-4 mr-4 mt-4">
													<div class="relative">
														<textarea class="form-textarea text-sm" rows="2" cols="40" name="list_desc"><?php echo esc_html( $list_desc ); ?></textarea>
														
													</div>
												</div>
											</div>
										</div>

										<?php
										if ( 'edit' === $action ) {
											?>
										<div class="flex flex-row border-b border-gray-100">
											<div class="flex w-1/5">
												<div class="ml-4 pt-4 px-3">
													<label for="name" class="block text-sm leading-5 font-medium text-gray-600">
												   <?php 
													   $allowedtags     = ig_es_allowed_html_tags_in_esc();
													   $tooltip_html = ES_Common::get_tooltip_html( __( 'Unique hash key that can be used to subscribe users to the list from external sites.', 'email-subscribers' ) );
													   esc_html_e( 'Hash', 'email-subscribers' ); 
													?>
													&nbsp;
													<?php echo wp_kses( $tooltip_html, $allowedtags ); ?>
													</label>
												</div>
											</div>
											<div class="flex">
												<div class="ml-16 mb-4 mr-4 mt-4">
													<div class="relative">
														<code class="select-all p-1 text-md font-medium text-sm">
														   <?php 
															$hash = isset( $data['hash'] ) ? $data['hash'] : '';
															echo esc_html( $hash ); 
															?>
																
														</code>
														
													</div>
												</div>
											</div>
										</div>

										<?php
										}
										$submit_button_text = $is_new ? __( 'Save List', 'email-subscribers' ) : __( 'Save Changes', 'email-subscribers' );
										?>
										<input type="hidden" name="submitted" value="submitted"/>
										<p><input type="submit" name="submit" id="submit" class="cursor-pointer align-middle ig-es-primary-button px-4 py-2 my-4 ml-6 mr-2" value="<?php echo esc_attr( $submit_button_text ); ?>"/>
										<a href="admin.php?page=es_lists&action=manage-lists" class="cursor-pointer align-middle rounded-md border border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo text-sm leading-5 font-medium transition ease-in-out duration-150 px-4 my-2 py-2 mx-2 "><?php esc_html_e( 'Cancel', 'email-subscribers' ); ?></a></p>
									</form>
								</div>
							</div>
						</div>

						<br class="clear">
					</div>

				</div>

				<?php

	}

	/**
	 * Save list
	 *
	 * @param $id
	 * @param $data
	 *
	 * @return bool|int|void
	 *
	 * @since 4.0.0
	 */
	public function save_list( $id, $data ) {
		$name = sanitize_text_field( $data['list_name'] );
		$desc = isset( $data['list_desc'] ) ? sanitize_text_field( $data['list_desc'] ) : '';

		$list = array(
			'name' => $name,
			'desc' => $desc,
		);

		if ( ! empty( $id ) ) {
			$return = $this->db->update_list( $id, $list );
		} else {
			$return = $this->db->add_list( $list );
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
		global $wpbd;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		if ( $do_count_only ) {
			$sql = 'SELECT count(*) as total FROM ' . IG_LISTS_TABLE;
		} else {
			$sql = 'SELECT * FROM ' . IG_LISTS_TABLE;
		}

		$args  = array();
		$query = array();

		$add_where_clause = false;

		if ( ! empty( $search ) ) {
			$query[] = ' name LIKE %s ';
			$args[]  = '%' . $wpbd->esc_like( $search ) . '%';

			$add_where_clause = true;
		}

		if ( $add_where_clause ) {
			$sql .= ' WHERE ';

			if ( count( $query ) > 0 ) {
				$sql .= implode( ' AND ', $query );
				if ( count( $args ) > 0 ) {
					$sql = $wpbd->prepare( $sql, $args );
				}
			}
		}

		if ( ! $do_count_only ) {

			// Prepare Order by clause
			$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'name', 'created_at' );

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
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'subscribed':
				$count = ES()->lists_contacts_db->get_total_count_by_list( $item['id'], 'subscribed' );
				if ( $count > 0 ) {
					$url = admin_url( 'admin.php?page=es_subscribers&filter_by_status=subscribed&filter_by_list_id=' . $item['id'] );
					/* translators: 1: Subscribed-Filter url  2: Count */
					$count = '<a href="' . $url . '" target="_blank">' . number_format( $count ) . '</a>';
				}

				return $count;
				break;

			case 'unsubscribed':
				$count = ES()->lists_contacts_db->get_total_count_by_list( $item['id'], 'unsubscribed' );
				if ( $count > 0 ) {
					$url = admin_url( 'admin.php?page=es_subscribers&filter_by_status=unsubscribed&filter_by_list_id=' . $item['id'] );
					/* translators: 1: Unsubscribed-Filter url  2: Count */
					$count = '<a href="' . $url . '" target="_blank">' . number_format( $count ) . '</a>';
				}

				return $count;
				break;

			case 'unconfirmed':
				$count = ES()->lists_contacts_db->get_total_count_by_list( $item['id'], 'unconfirmed' );
				if ( $count > 0 ) {
					$url = admin_url( 'admin.php?page=es_subscribers&filter_by_status=unconfirmed&filter_by_list_id=' . $item['id'] );
					/* translators: 1: Unconfirmed-Filter url  2: Count */
					$count = '<a href="' . $url . '" target="_blank">' . number_format( $count ) . '</a>';
				}

				return $count;
				break;

			case 'all_contacts':
				$count = ES()->lists_contacts_db->get_total_count_by_list( $item['id'], 'all' );
				if ( $count > 0 ) {
					$url = admin_url( 'admin.php?page=es_subscribers&filter_by_list_id=' . $item['id'] );
					/* translators: 1: All contacts flters  2: Count */
					$count = '<a href="' . $url . '" target="_blank">' . number_format( $count ) . '</a>';
				}

				return $count;
				break;

			case 'export':
				$export_nonce = wp_create_nonce( 'ig-es-subscriber-export-nonce' );
				return "<a href='admin.php?page=download_report&report=users&status=select_list&list_id={$item['id']}&export-nonce={$export_nonce}'><svg fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' viewBox='0 0 24 24' class='w-8 h-8 text-indigo-600 hover:text-indigo-500 active:text-indigo-600'><path d='M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path></svg></a>";
				break;

			case 'description':
				if ( empty( $item['description'] ) ) {
					return '-';
				}
				
				$description  = '<span class="es_list_desc" title="' . $item['description'] . '">';
				$description .= strlen( $item['description'] ) > 50 ? substr( $item['description'], 0, 50 ) . '...' : $item['description'];
				$description .= '</span>';
				
				return $description;
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
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="lists[]" value="%s" />',
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

		$list_nonce = wp_create_nonce( 'es_list' );

		$title   = '<strong>' . $item['name'] . '</strong>';
		$actions = array();
		if ( 1 != $item['id'] ) {
			$page    = ig_es_get_request_data( 'page' );
			$actions = array(
				'edit'   => '<a href="?page=' . esc_attr( $page ) . '&action=edit&list=' . absint( $item['id'] ) . '&_wpnonce=' . $list_nonce . '" class="text-indigo-600">' . esc_html__( 'Edit', 'email-subscribers' ) . '</a>',

				'delete' => '<a href="?page=' . esc_attr( $page ) . '&action=delete&list=' . absint( $item['id'] ) . '&_wpnonce=' . $list_nonce . '" onclick="return checkDelete()">' . esc_html__( 'Delete', 'email-subscribers' ) . '</a>',
			);
		}

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {

		$allowedtags  = ig_es_allowed_html_tags_in_esc();

		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'name'         => __( 'Name', 'email-subscribers' ),
			'description'  => __( 'Description', 'email-subscribers' ),
			'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
			'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' ),
			'unconfirmed'  => __( 'Unconfirmed', 'email-subscribers' ),
			'all_contacts' => __( 'All contacts', 'email-subscribers' ),
			'export'       => __( 'Export', 'email-subscribers' ),
		);

		return apply_filters( 'ig_es_lists_columns', $columns );
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name'       => array( 'name', true ),
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
			'bulk_delete' => __( 'Delete', 'email-subscribers' ),
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
			<?php submit_button( __( 'Search lists', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
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

		$this->search_box( ig_es_get_request_data( 's' ), 'list-search-input' );

		$per_page     = $this->get_items_per_page( self::$option_per_page, 25 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items.
				'per_page'    => $per_page, // WE have to determine how many items to show on a page.
			)
		);

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		// Detect when a bulk action is being triggered...
		if ( 'edit' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
				$message = __( 'You do not have permission to edit list', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$this->edit_list( absint( ig_es_get_request_data( 'list' ) ) );
				$message = __( 'List updated successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
				$message = __( 'You do not have permission to delete list', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$list = ig_es_get_request_data( 'list' );
				if ( 1 != $list ) {
					$list = ig_es_get_request_data( 'list' );
					$this->db->delete_lists( array( $list ) );
					$message = __( 'List deleted successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}
			}
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$lists = ig_es_get_request_data( 'lists' );

			if ( ! empty( $lists ) > 0 ) {
				$this->db->delete_lists( $lists );
				$message = __( 'List(s) deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			} else {
				$message = __( 'Please select list', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );

				return;
			}
		}
	}

	public function status_label_map( $status ) {

		$statuses = array(
			'enable'  => __( 'Enable', 'email-subscribers' ),
			'disable' => __( 'Disable', 'email-subscribers' ),
		);

		if ( ! in_array( $status, array_keys( $statuses ) ) ) {
			return '';
		}

		return $statuses[ $status ];
	}

	/** Text displayed when no list data is available */
	public function no_items() {
		esc_html_e( 'No lists avaliable.', 'email-subscribers' );
	}

}
