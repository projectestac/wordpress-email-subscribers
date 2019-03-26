<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Forms_Table extends WP_List_Table {

	static $instance;

	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Forms', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Forms', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?,
			'screen'   => 'es_forms'
		) );

		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
	}

	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function es_forms_callback() {

		$action = Email_Subscribers::get_request( 'action' );
		?>
        <div class="wrap">
		<?php if ( 'new' === $action ) {
			$this->es_new_form_callback();
		} elseif ( 'edit' === $action ) {
			echo $this->edit_form( absint( Email_Subscribers::get_request( 'form' ) ) );
		} else { ?>
            <h1 class="wp-heading-inline">Forms<a href="admin.php?page=es_forms&action=new" class="page-title-action">Add New</a></h1>
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
			'label'   => __( 'Forms', 'email-subscribers' ),
			'default' => 10,
			'option'  => 'forms_per_page'
		);

		add_screen_option( $option, $args );

	}

	public function validate_data( $data ) {

		$nonce     = $data['nonce'];
		$form_name = $data['name'];
		$lists     = $data['lists'];

		$status  = 'error';
		$error   = false;
		$message = '';
		if ( ! wp_verify_nonce( $nonce, 'es_form' ) ) {
			$message = __( 'You do not have permission to edit this form.', 'email-subscribers' );
			$error   = true;
		} elseif ( empty( $form_name ) ) {
			$message = __( 'Please add form name.', 'email-subscribers' );
			$error   = true;
		}

		if ( empty( $lists ) ) {
			$message = __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' );
			$error   = true;
		}

		if ( ! $error ) {
			$status = 'success';
		}

		$response = array(
			'status'  => $status,
			'message' => $message
		);

		return $response;

	}

	public function es_new_form_callback() {

		$submitted = Email_Subscribers::get_request( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce              = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );
			$form_data          = Email_Subscribers::get_request( 'form_data' );
			$lists              = Email_Subscribers::get_request( 'lists' );
			$form_data['lists'] = $lists;

			$validate_data = array(
				'nonce' => $nonce,
				'name'  => ! empty( $form_data['name'] ) ? $form_data['name'] : '',
				'lists' => ! empty( $form_data['lists'] ) ? $form_data['lists'] : array()
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				$this->show_message( $message, 'error' );
				$this->prepare_list_form( null, $form_data );

				return;
			}

			$this->save_form( null, $form_data );
			$message = __( 'Form has been added successfully!', 'email-subscribers' );
			$this->show_message( $message, 'success' );
		}

		$this->prepare_list_form();
	}


	public function edit_form( $id ) {
		global $wpdb;

		if ( $id ) {

			$data = $wpdb->get_results( "SELECT * FROM " . IG_FORMS_TABLE . " WHERE id = $id", ARRAY_A );

			if ( count( $data ) > 0 ) {

				$submitted = Email_Subscribers::get_request( 'submitted' );


				if ( 'submitted' === $submitted ) {

					$nonce              = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );
					$form_data          = Email_Subscribers::get_request( 'form_data' );
					$lists              = Email_Subscribers::get_request( 'lists' );
					$form_data['lists'] = $lists;


					$validate_data = array(
						'nonce' => $nonce,
						'name'  => $form_data['name'],
						'lists' => $form_data['lists']
					);

					$response = $this->validate_data( $validate_data );

					if ( 'error' === $response['status'] ) {
						$message = $response['message'];
						$this->show_message( $message, 'error' );
						$this->prepare_list_form( $id, $form_data );

						return;
					}

					$this->save_form( $id, $form_data );
					$message = __( 'Form has been updated successfully!', 'email-subscribers' );
					$this->show_message( $message, 'success' );
				} else {

					$data      = $data[0];
					$id        = $data['id'];
					$form_data = self::get_form_data_from_body( $data );
				}
			} else {
				$message = __( 'Sorry, form not found', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			}

			$this->prepare_list_form( $id, $form_data );
		}
	}

	public function prepare_list_form( $id = 0, $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action = 'new';
		if ( ! $is_new ) {
			$action = 'edit';
		}

		$form_data['name']          = ! empty( $data['name'] ) ? $data['name'] : '';
		$form_data['name_visible']  = ! empty( $data['name_visible'] ) ? $data['name_visible'] : 'no';
		$form_data['name_required'] = ! empty( $data['name_required'] ) ? $data['name_required'] : 'no';
		$form_data['list_visible']  = ! empty( $data['list_visible'] ) ? $data['list_visible'] : 'no';
		$form_data['lists']         = ! empty( $data['lists'] ) ? $data['lists'] : array();
		$form_data['af_id']         = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;
		$form_data['desc']          = ! empty( $data['desc'] ) ? $data['desc'] : '';

		$lists = ES_DB_Lists::get_list_id_name_map();
		$nonce = wp_create_nonce( 'es_form' );

		?>

        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php
				if ( $is_new ) {
					_e( 'New Form', 'email-subscribers' );
				} else {
					_e( 'Edit Form', 'email-subscribers' );
				}

				?>
            </h1>

			<?php Email_Subscribers_Admin::es_feedback(); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post" action="admin.php?page=es_forms&action=<?php echo $action; ?>&form=<?php echo $id; ?>&_wpnonce=<?php echo $nonce; ?>">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Form Name', 'email-subscribers' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="form_data[name]" id="ig_es_title" value="<?php echo stripslashes( $form_data['name'] ); ?>" size="30" maxlength="100"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Description', 'email-subscribers' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="form_data[desc]" id="ig_es_title" value="<?php echo stripslashes( $form_data['desc'] ); ?>" size="30" maxlength="100"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Form Fields', 'email-subscribers' ); ?></label>
                                        </th>
                                        <td>
                                            <table class="">
                                                <tr class="form-field">
                                                    <td><?php _e( 'Field', 'email-subscribers' ); ?></td>
                                                    <td><?php _e( 'Show?', 'email-subscribers' ); ?></td>
                                                    <td><?php _e( 'Required?', 'email-subscribers' ); ?></td>
                                                </tr>
                                                <tr class="form-field">
                                                    <td><?php _e( 'Email', 'email-subscribers' ); ?></td>
                                                    <td><input type="checkbox" class="" name="form_data[email_visible]" value="yes" disabled="disabled" checked="checked"></td>
                                                    <td><input type="checkbox" class="" name="form_data[email_required]" value="yes" disabled="disabled" checked="checked"></td>
                                                </tr>
                                                <tr class="form-field">
                                                    <td><?php _e( 'Name', 'email-subscribers' ); ?></td>
                                                    <td><input type="checkbox" class="es_visible" name="form_data[name_visible]" value="yes" <?php if ( $form_data['name_visible'] === 'yes' ) {
															echo 'checked="checked"';
														} ?> /></td>
                                                    <td><input type="checkbox" class="es_required" name="form_data[name_required]" value="yes" <?php if ( $form_data['name_required'] === 'yes' ) {
															echo 'checked=checked';
														} ?>></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Lists', 'email-subscribers' ); ?></label>
                                            <p class="helper"> <?php _e( 'Contacts will be added into selected list(s)', 'email-subscribers' ); ?></p>
                                        </th>
                                        <td>
											<?php

											if ( count( $lists ) > 0 ) {

												echo ES_Shortcode::prepare_lists_checkboxes( $lists, array_keys( $lists ), 3, (array) $form_data['lists'] );

											} else {
												$create_list_link = admin_url( 'admin.php?page=es_lists&action=new' );
												?>
                                                <span><?php _e( sprintf( 'List not found. Please <a href="%s">create your first list</a>.', $create_list_link ) ); ?></span>
											<?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Allow contact to choose list(s)', 'email-subscribers' ); ?></label>
                                            <p class="helper"> <?php _e( 'Allow contacts to choose list(s) in which they want to subscribe.', 'email-subscribers' ); ?></p>
                                        </th>
                                        <td>
                                            <input type="radio" name="form_data[list_visible]" value="yes" <?php if ( $form_data['list_visible'] === 'yes' ) {
												echo 'checked="checked"';
											} ?> /><?php _e( 'Yes', 'email-subscribers' ); ?>

                                            <input type="radio" name="form_data[list_visible]" value="no" <?php if ( $form_data['list_visible'] === 'no' ) {
												echo 'checked="checked"';
											} ?> /> <?php _e( 'No', 'email-subscribers' ); ?>
                                        </td>


                                    </tr>

                                    </tbody>
                                </table>
                                <input type="hidden" name="form_data[af_id]" value="<?php echo $form_data['af_id']; ?>"/>
                                <input type="hidden" name="submitted" value="submitted"/>
								<?php if ( count( $lists ) > 0 ) { ?>
                                    <div class="row-blog"><?php submit_button(); ?></div>
								<?php } else {
									$lists_page_url = admin_url( 'admin.php?page=es_lists' );
									$message        = __( sprintf( 'List(s) not found. Please create a first list from <a href="%s">here</a>', $lists_page_url ), 'email-subscribers' );
									$status         = 'error';
									$this->show_message( $message, $status );
								} ?>

                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>

		<?php

	}

	public function save_form( $id, $data ) {

		global $wpdb;

		$form_data = self::prepare_form_data( $data );

		if ( ! empty( $id ) ) {
			$form_data['updated_at'] = ig_get_current_date_time();
			$return                  = $wpdb->update( IG_FORMS_TABLE, $form_data, array( 'id' => $id ) );
		} else {
			$return = $wpdb->insert( IG_FORMS_TABLE, $form_data );
		}

		return $return;
	}

	public static function prepare_form_data( $data ) {
		$form_data     = array();
		$name          = ! empty( $data['name'] ) ? $data['name'] : '';
		$desc          = ! empty( $data['desc'] ) ? $data['desc'] : '';
		$name_visible  = ( ! empty( $data['name_visible'] ) && $data['name_visible'] === 'yes' ) ? true : false;
		$name_required = ( ! empty( $data['name_required'] ) && $data['name_required'] === 'yes' ) ? true : false;
		$list_visible  = ( ! empty( $data['list_visible'] ) && $data['list_visible'] === 'yes' ) ? true : false;
		$list_requried = true;
		$list_ids      = ! empty( $data['lists'] ) ? $data['lists'] : array();
		$af_id         = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;

		$body = array(
			array(
				'type'   => 'text',
				'name'   => 'Name',
				'id'     => 'name',
				'params' => array(
					'label'    => 'Name',
					'show'     => $name_visible,
					'required' => $name_required
				),

				'position' => 1
			),

			array(
				'type'   => 'text',
				'name'   => 'Email',
				'id'     => 'email',
				'params' => array(
					'label'    => 'Email',
					'show'     => true,
					'required' => true
				),

				'position' => 2
			),

			array(
				'type'   => 'checkbox',
				'name'   => 'Lists',
				'id'     => 'lists',
				'params' => array(
					'label'    => 'Lists',
					'show'     => $list_visible,
					'required' => $list_requried,
					'values'   => $list_ids
				),

				'position' => 3
			),

			array(
				'type'   => 'submit',
				'name'   => 'submit',
				'id'     => 'submit',
				'params' => array(
					'label' => 'Submit',
					'show'  => true
				),

				'position' => 4
			),

		);

		$settings = array(
			'lists' => $list_ids,
			'desc'  => $desc
		);

		$form_data['name']       = $name;
		$form_data['body']       = maybe_serialize( $body );
		$form_data['settings']   = maybe_serialize( $settings );
		$form_data['styles']     = null;
		$form_data['created_at'] = ig_get_current_date_time();
		$form_data['updated_at'] = null;
		$form_data['deleted_at'] = null;
		$form_data['af_id']      = $af_id;

		return $form_data;
	}

	public static function get_form_data_from_body( $data ) {

		$name          = ! empty( $data['name'] ) ? $data['name'] : '';
		$id            = ! empty( $data['id'] ) ? $data['id'] : '';
		$af_id         = ! empty( $data['af_id'] ) ? $data['af_id'] : '';
		$body_data     = maybe_unserialize( $data['body'] );
		$settings_data = maybe_unserialize( $data['settings'] );

		$desc = ! empty( $settings_data['desc'] ) ? $settings_data['desc'] : '';

		$form_data = array( 'form_id' => $id, 'name' => $name, 'af_id' => $af_id, 'desc' => $desc );
		foreach ( $body_data as $d ) {
			if ( $d['id'] === 'name' ) {
				$form_data['name_visible']  = ( $d['params']['show'] === true ) ? 'yes' : '';
				$form_data['name_required'] = ( $d['params']['required'] === true ) ? 'yes' : '';
			} elseif ( $d['id'] === 'lists' ) {
				$form_data['list_visible']  = ( $d['params']['show'] === true ) ? 'yes' : '';
				$form_data['list_required'] = ( $d['params']['required'] === true ) ? 'yes' : '';
				$form_data['lists']         = ! empty( $d['params']['values'] ) ? $d['params']['values'] : array();
			}
		}

		return $form_data;
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
		$search   = stripslashes( Email_Subscribers::get_request( 's' ) );

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM " . IG_FORMS_TABLE;
		} else {
			$sql = "SELECT * FROM " . IG_FORMS_TABLE;
		}
		$args = $query = array();

		$add_where_clause = true;

		$query[] = '( deleted_at IS NULL OR deleted_at = "0000-00-00 00:00:00" )';

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
			if ( ! empty( $order_by ) ) {
				$order_by_clause = ' ORDER BY ' . esc_sql( $order_by );
				$order_by_clause .= ! empty( $order ) ? ' ' . esc_sql( $order ) : ' ASC';
			}

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

		$ids          = "'" . implode( "', '", array_map( 'absint', $ids ) ) . "'";
		$current_date = ig_get_current_date_time();
		$query        = "UPDATE " . IG_FORMS_TABLE . " SET deleted_at = %s WHERE id IN ({$ids})";
		$query        = $wpdb->prepare( $query, array( $current_date ) );
		$wpdb->query( $query );
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_FORMS_TABLE;

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
			// case 'status':
			// 	return $this->status_label_map( $item[ $column_name ] );
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
				break;
			case 'shortcode':
				$shortcode = '[email-subscribers-form id="' . $item['id'] . '"]';

				return '<code>' . $shortcode . '</code>';
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
			'<input type="checkbox" name="forms[]" value="%s" />', $item['id']
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

		$list_nonce = wp_create_nonce( 'es_form' );

		$title   = '<strong>' . stripslashes( $item['name'] ) . '</strong>';
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s">Edit</a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'edit', absint( $item['id'] ), $list_nonce ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', esc_attr( Email_Subscribers::get_request( 'page' ) ), 'delete', absint( $item['id'] ), $list_nonce )
		);

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
			'shortcode'  => __( 'Shortcode', 'email-subscribers' ),
			'created_at' => __( 'Created', 'email-subscribers' )
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
			<?php submit_button( 'Search Forms', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
	<?php }

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();


		/** Process bulk action */
		$this->process_bulk_action();
		$this->search_box( Email_Subscribers::get_request( 's' ), 'form-search-input' );

		$per_page     = $this->get_items_per_page( 'forms_per_page', 25 );
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

			if ( ! wp_verify_nonce( $nonce, 'es_form' ) ) {
				$message = __( 'You do not have permission to delete this form.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			} else {

				$form = Email_Subscribers::get_request( 'form' );

				$this->delete_list( array( $form ) );
				$message = __( 'Form has been deleted successfully!', 'email-subscribers' );
				$this->show_message( $message, 'success' );
			}
		}

		$action  = Email_Subscribers::get_request( 'action' );
		$action2 = Email_Subscribers::get_request( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$forms = Email_Subscribers::get_request( 'forms' );

			if ( ! empty( $forms ) > 0 ) {
				$this->delete_list( $forms );

				$message = __( 'Form(s) have been deleted successfully!', 'email-subscribers' );
				$this->show_message( $message, 'success' );
			} else {
				$message = __( 'Please select form(s) to delete.', 'email-subscribers' );
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
		_e( 'No Forms avaliable.', 'email-subscribers' );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

add_action( 'admin_menu', function () {
	ES_Forms_Table::get_instance();
} );
