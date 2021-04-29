<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Contacts_Table extends WP_List_Table {
	/**
	 * @since 4.0.0
	 * @var array
	 *
	 */
	public $contact_lists_statuses = array();

	/**
	 * @since 4.2.1
	 *
	 * @var string
	 */
	public static $option_per_page = 'es_contacts_per_page';

	/**
	 * @since 4.0.0
	 * @var array
	 *
	 */
	public $list_ids = array();

	/**
	 * @since 4.0.0
	 * @var array
	 *
	 */
	public $lists_id_name_map = array();

	/**
	 * @var object|ES_DB_Contacts
	 */
	public $db;

	/**
	 * ES_Contacts_Table constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Contact', 'email-subscribers' ),
			'plural'   => __( 'Contacts', 'email-subscribers' ),
			'ajax'     => false,
			'screen'   => 'es_subscribers'
		) );

		$this->db = new ES_DB_Contacts();

		add_filter( 'ig_es_audience_tab_main_navigation', array( $this, 'get_audience_main_tabs' ), 10, 2 );

		// @since 4.3.1
		add_action( 'ig_es_list_deleted', array( $this, 'delete_contacts_from_list' ), 10, 1 );
		add_action( 'ig_es_form_deleted', array( $this, 'set_default_form_id' ), 10, 1 );
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		// Don't show screen option on Import/ Export subscribers page.
		$action = ig_es_get_request_data( 'action' );

		if ( '' === $action ) {

			$option = 'per_page';
			$args   = array(
				'label'   => __( 'Number of contacts per page', 'email-subscribers' ),
				'default' => 100,
				'option'  => self::$option_per_page
			);

			add_screen_option( $option, $args );
		}

	}

	/**
	 * @param $active_tab
	 * @param array $audience_main_tabs
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_audience_main_tabs( $active_tab, $audience_main_tabs = array() ) {

		$audience_tab_main_navigation = array(
			'new_contact' => array(
				'label'            => __( 'Add New Contact', 'email-subscribers' ),
				'indicator_option' => '',
				'indicator_label'  => '',
				'indicator_type'   => '',
				'action'           => 'new',
				'url'              => add_query_arg( 'action', 'new', 'admin.php?page=es_subscribers' )
			),

			'import' => array(
				'label'            => __( 'Import Contacts', 'email-subscribers' ),
				'indicator_option' => '',
				'indicator_label'  => '',
				'indicator_type'   => '',
				'action'           => 'import',
				'url'              => add_query_arg( 'action', 'import', 'admin.php?page=es_subscribers' )
			),

			'export' => array(
				'label'            => __( 'Export Contacts', 'email-subscribers' ),
				'indicator_option' => '',
				'indicator_label'  => '',
				'indicator_type'   => '',
				'action'           => 'export',
				'url'              => add_query_arg( 'action', 'export', 'admin.php?page=es_subscribers' )
			),

			'sync' => array(
				'label'            => __( 'Sync', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_tab',
				'indicator_label'  => __( 'New', 'email-subscribers' ),
				'indicator_type'   => 'new',
				'action'           => 'sync',
				'url'              => add_query_arg( 'action', 'sync', 'admin.php?page=es_subscribers' )
			),

			'manage_lists' => array(
				'label'            => __( 'Manage Lists', 'email-subscribers' ),
				'indicator_option' => '',
				'indicator_label'  => '',
				'indicator_type'   => '',
				'action'           => 'manage-lists',
				'is_imp'           => true,
				'url'              => add_query_arg( 'action', 'manage-lists', 'admin.php?page=es_lists' )
			)
		);

		$audience_main_tabs = $audience_main_tabs + $audience_tab_main_navigation;

		if ( ! empty( $active_tab ) && isset( $audience_main_tabs[ $active_tab ] ) ) {
			unset( $audience_main_tabs[ $active_tab ] );
		}

		return $audience_main_tabs;
	}

	/**
	 * Render Audience View
	 *
	 * @since 4.2.1
	 */
	public function render() {

		?>
        <div class="wrap">

		<?php

		$action = ig_es_get_request_data( 'action' );
		if ( 'import' === $action ) {
			$this->load_import();
		} elseif ( 'export' === $action ) {
			$this->load_export();
		} elseif ( 'new' === $action || 'edit' === $action ) {
			$contact_id = absint( ig_es_get_request_data( 'subscriber' ) );
			$this->save_contact( $contact_id );
		} elseif ( 'sync' === $action ) {
			update_option( 'ig_es_show_sync_tab', 'no' ); // yes/no
			$this->load_sync();
		} else {

			$audience_tab_main_navigation = array();
			$active_tab                   = '';
			$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

			?>

            <h1 class="wp-heading-inline">
				<?php
				_e( 'Audience > Contacts', 'email-subscribers' );
				ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
				?>
            </h1>

            <div class="es-contact-reports">
				<?php $this->get_contacts_reports() ?>
            </div>

            <div id="poststuff" class="es-audience-view">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
								<?php
								$this->prepare_items();
								$this->display();
								?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
            </div>
		<?php }
	}

	/**
	 * Load Export Feature
	 *
	 * @since 4.0.0
	 */
	public function load_export() {
		$export = new Export_Subscribers();
		$export->export_subscribers_page();
	}

	/**
	 * Load import
	 *
	 * @since 4.0.0
	 */
	public function load_import() {
		$import = new ES_Import_Subscribers();
		$import->import_subscribers_page();
	}

	/**
	 * Load Sync
	 *
	 * @since 4.0.0
	 */
	public function load_sync() {
		$sync = ES_Handle_Sync_Wp_User::get_instance();
		$sync->prepare_sync_user();
	}

	/**
	 * Get Contacts Reports
	 *
	 * @since 4.3.1
	 */
	public function get_contacts_reports() {
		$es_total_contact                = ES_Reports_Data::get_total_contacts();
		$es_total_subscribed_contacts    = ES_Reports_Data::get_total_subscribed_contacts( 60 );
		$es_total_unsubscribed_contacts  = ES_Reports_Data::get_total_unsubscribed_contacts( 60 );
		$es_total_contacts_opened_emails = ES_Reports_Data::get_total_contacts_opened_emails( 60 );
		?>
        <div class="es_total_contact">
            <h2 class="es_contact_kpi_text"><?php _e( 'Contacts', 'email-subscribers' ); ?></h2>
            <span class="es_contact_kpi_no"><?php echo $es_total_contact; ?></span></br>
        </div>
        <div class="es_last_60_days">
            <h2><?php _e( 'Last 60 Days', 'email-subscribers' ); ?></h2>
            <div class="es_contact_kpi">
                <span class="es_contact_kpi_no" style="color: #009e00"><?php echo $es_total_subscribed_contacts; ?></span></br>
                <span class="es_contact_kpi_text"><?php _e( 'Subscribed', 'email-subscribers' ); ?></span>
            </div>
            <div class="es_contact_kpi">
                <span class="es_contact_kpi_no" style="color: #d40303"><?php echo $es_total_unsubscribed_contacts; ?></span></br>
                <span class="es_contact_kpi_text"><?php _e( 'Unsubscribed', 'email-subscribers' ); ?></span>
            </div>
            <div class="es_contact_kpi">
                <span class="es_contact_kpi_no" style="color: #006cc1"><?php echo $es_total_contacts_opened_emails; ?></span></br>
                <span class="es_contact_kpi_text"><?php _e( 'Opened', 'email-subscribers' ); ?></span>
            </div>
			<?php do_action( 'ig_es_after_contacts_kpis' ); ?>
        </div>
		<?php
	}

	/**
	 * Save contact
	 *
	 * @param int $id
	 *
	 * @since 4.0.0
	 */
	public function save_contact( $id = 0 ) {
		global $wpdb;

		$first_name = $action = $last_name = $email = $guid = $created = '';
		$list_ids   = array();
		$is_new     = true;

		if ( $id === 0 ) {
			$title        = __( 'Add New Contact', 'email-subscribers' );
			$title_action = '<a href="admin.php?page=es_lists&action=manage-lists" class="page-title-action es-imp-button">' . __( 'Manage Lists', 'email-subscribers' ) . '</a>';

		} else {
			$is_new       = false;
			$title        = __( 'Edit Contact', 'email-subscribers' );
			$title_action = '<a href="admin.php?page=es_subscribers&action=new" class="page-title-action">' . __( 'Add New', 'email-subscribers' ) . '</a>';

			$contact = $this->db->get( $id );

			if ( ! empty( $contact ) ) {

				$first_name = ig_es_get_data( $contact, 'first_name' );
				$last_name  = ig_es_get_data( $contact, 'last_name' );
				$email      = sanitize_email( ig_es_get_data( $contact, 'email' ) );
				$guid       = ig_es_get_data( $contact, 'hash' );

				$list_ids = ES()->lists_contacts_db->get_list_ids_by_contact( $id );

				$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );
			}
		}

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$contact_data = ig_es_get_post_data( 'contact_data', array() );

			$is_error = false;
			if ( ! empty( $contact_data ) ) {

				$email = sanitize_email( ig_es_get_data( $contact_data, 'email', '', true ) );

				if ( $email ) {

					$lists = ig_es_get_data( $contact_data, 'lists', array() );

					if ( count( $lists ) > 0 ) {
						$first_name = ig_es_get_data( $contact_data, 'first_name', '', true );
						$last_name  = ig_es_get_data( $contact_data, 'last_name', '', true );

						if ( ! empty( $first_name ) ) {

							$contact = array(
								'first_name' => $first_name,
								'last_name'  => $last_name,
								'email'      => $email,
							);

							// Add contact
							$existing_contact_id = ES()->contacts_db->get_contact_id_by_email( $email );

							if ( $existing_contact_id && ( $existing_contact_id != $id ) ) {
								$message = __( 'Contact already exist.', 'email-subscribers' );
								ES_Common::show_message( $message, 'error' );
								$is_error = true;
							} else {
								if ( $id ) {
									ES()->contacts_db->update_contact( $id, $contact );
								} else {
									$contact['source']     = 'admin';
									$contact['status']     = 'verified';
									$contact['hash']       = ES_Common::generate_guid();
									$contact['created_at'] = ig_get_current_date_time();

									$id = ES()->contacts_db->insert( $contact );
								}
							}

							/*
							if ( $id ) {
								ES()->contacts_db->update_contact( $id, $contact );
							} else {
								if ( ! $existing_contact_id ) {
									$contact['source']     = 'admin';
									$contact['status']     = 'verified';
									$contact['hash']       = ES_Common::generate_guid();
									$contact['created_at'] = ig_get_current_date_time();

									$id = ES()->contacts_db->insert( $contact );

								} else {
									$message = __( 'Contact already exist.', 'email-subscribers' );
									ES_Common::show_message( $message, 'error' );
									$is_error = true;
								}

							}
							*/

							if ( ! $is_error ) {

								$lists = ! empty( $lists ) ? $lists : array( 1 => 0 );

								ES()->lists_contacts_db->update_contact_lists( $id, $lists );

								if ( $id ) {

									if ( $is_new ) {

										if ( ! empty( $contact_data['send_welcome_email'] ) ) {

											// Get comma(,) separated list name based on ids
											$list_name = ES_Common::prepare_list_name_by_ids( $list_ids );
											$name      = ES_Common::prepare_name_from_first_name_last_name( $contact['first_name'], $contact['last_name'] );

											$template_data = array(
												'email'      => $contact['email'],
												'contact_id' => $id,
												'name'       => $name,
												'first_name' => $contact['first_name'],
												'last_name'  => $contact['last_name'],
												'guid'       => $contact['hash'],
												'list_name'  => $list_name
											);

											// Send Welcome Email
											ES()->mailer->send_welcome_email( $contact['email'], $template_data );
										}

										$message = __( 'Contact has been added successfully!', 'email-subscribers' );
									} else {
										$message = __( 'Contact has been updated successfully!', 'email-subscribers' );
									}

									ES_Common::show_message( $message, 'success' );
								}
							}
						} else {
							$message = __( 'Please Enter First Name', 'email-subscribers' );
							ES_Common::show_message( $message, 'error' );
						}

					} else {
						$message = __( 'Please Select List', 'email-subscribers' );
						ES_Common::show_message( $message, 'error' );
					}

				} else {
					$message = __( 'Please Enter Valid Email Address', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
				}

			}
		}

		$data = array(
			'id'         => $id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'guid'       => $guid
		);

		?>

        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $title; ?><?php echo $title_action; ?></h1>
            <hr class="wp-header-end">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable es-contact-form">
							<?php echo $this->prepare_contact_form( $data, $is_new ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

		<?php

	}

	/**
	 * Retrieve subscribers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_subscribers( $per_page = 5, $page_number = 1, $do_count_only = false ) {
		global $wpdb;

		$order_by          = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order             = ig_es_get_request_data( 'order' );
		$search            = ig_es_get_request_data( 's' );
		$filter_by_list_id = ig_es_get_request_data( 'filter_by_list_id' );
		$filter_by_status  = ig_es_get_request_data( 'filter_by_status' );

		$contacts_table       = IG_CONTACTS_TABLE;
		$lists_contacts_table = IG_LISTS_CONTACTS_TABLE;

		$add_where_clause = false;

		$args  = array();
		$query = array();

		if ( $do_count_only ) {
			$sql = "SELECT count(*) FROM {$contacts_table}";
		} else {
			$sql = "SELECT * FROM {$contacts_table}";
		}

		// Prepare filter by list query
		if ( ! empty( $filter_by_list_id ) || ! empty( $filter_by_status ) ) {
			$add_where_clause = true;

			$filter_sql = "SELECT contact_id FROM {$lists_contacts_table}";

			$list_filter_sql    = '';
			$where_clause_added = false;

			if ( ! empty( $filter_by_list_id ) ) {
				$list_filter_sql    = $wpdb->prepare( " WHERE list_id = %d", $filter_by_list_id );
				$where_clause_added = true;
			}

			if ( ! empty( $filter_by_status ) ) {
				if ( $where_clause_added ) {
					$list_filter_sql .= $wpdb->prepare( " AND status = %s", $filter_by_status );
				} else {
					$list_filter_sql .= $wpdb->prepare( " WHERE status = %s", $filter_by_status );
				}

			}

			$filter_sql .= $list_filter_sql;
			$query[]    = "id IN ( $filter_sql )";
		}

		// Prepare search query
		if ( ! empty( $search ) ) {
			$query[] = " ( first_name LIKE %s OR last_name LIKE %s OR email LIKE %s ) ";
			$args[]  = "%" . $wpdb->esc_like( $search ) . "%";
			$args[]  = "%" . $wpdb->esc_like( $search ) . "%";
			$args[]  = "%" . $wpdb->esc_like( $search ) . "%";
		}

		if ( $add_where_clause || count( $query ) > 0 ) {
			$sql .= " WHERE ";

			if ( count( $query ) > 0 ) {
				$sql .= implode( " AND ", $query );
				if ( ! empty( $args ) ) {
					$sql = $wpdb->prepare( $sql, $args );
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

			$offset = ( $page_number - 1 ) * $per_page;

			$expected_order_by_values = array( 'name', 'email', 'created_at', 'first_name' );
			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by = 'created_at';
			}

			$order_by = esc_sql( $order_by );

			$order_by_clause = " ORDER BY {$order_by} {$order}";

			$sql .= $order_by_clause;
			$sql .= " LIMIT {$offset}, {$per_page}";

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		} else {
			$result = $wpdb->get_var( $sql );
		}

		return $result;
	}


	public function prepare_contact_form( $data = array(), $is_new = false ) {

		$id                 = ! empty( $data['id'] ) ? $data['id'] : '';
		$created            = ! empty( $data['created'] ) ? $data['created'] : '';
		$guid               = ! empty( $data['guid'] ) ? $data['guid'] : '';
		$action             = ! empty( $data['action'] ) ? $data['action'] : '#';
		$first_name         = ! empty( $data['first_name'] ) ? $data['first_name'] : '';
		$last_name          = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
		$email              = ! empty( $data['email'] ) ? $data['email'] : '';
		$send_welcome_email = ! empty( $data['send_welcome_email'] ) ? true : false;

		$lists_id_name_map = ES()->lists_db->get_list_id_name_map();

		if ( count( $lists_id_name_map ) ) {
			//$list_html = ES_Shortcode::prepare_lists_checkboxes( $lists_id_name_map, array_keys( $lists_id_name_map ), 4, $selected_list_ids, $id, 'contact_data[lists][]' );
			$list_html = $this->prepare_lists_html( $id );
		} else {
			$list_html = "<tr><td>" . __( 'No list found', 'email-subscribers' ) . "</td></tr>";
		}

		?>
        <form method="post" action="<?php echo $action; ?>">
            <table class="ig-es-form-table form-table">
                <tbody>
                <tr class="form-field">
                    <td><label><b><?php _e( 'First Name', 'email-subscribers' ); ?></b></label></td>
                    <td><input type="text" class="ig-es-contact-first-name" id="ig-es-contact-first-name" name="contact_data[first_name]" value="<?php echo esc_attr( $first_name ); ?>"/></td>
                </tr>

                <tr class="form-field">
                    <td><label><b><?php _e( 'Last Name', 'email-subscribers' ); ?></b></label></td>
                    <td><input type="text" class="ig-es-contact-last-name" id="ig-es-contact-last-name" name="contact_data[last_name]" value="<?php echo esc_attr( $last_name ); ?>"/></td>
                </tr>

                <tr class="form-field">
                    <td><label><b><?php _e( 'Email', 'email-subscribers' ); ?></b></label></td>
                    <td><input type="email" id="email" name="contact_data[email]" value="<?php echo esc_attr( $email ); ?>"/></td>
                </tr>

				<?php if ( $is_new ) { ?>
                    <tr class="form-field">
                        <td><label><b><?php _e( 'Send Welcome Email?', 'email-subscribers' ); ?></b></label></td>
                        <td><input type="checkbox" id="ig-es-contact-welcome-email" name="contact_data[send_welcome_email]" <?php if ( $send_welcome_email ) {
								echo "checked='checked'";
							} ?> /></td>
                    </tr>
				<?php } ?>
                <tr class="form-field">
                    <td><label><b><?php _e( 'List(s)', 'email-subscribers' ); ?></b></label></td>
                    <td>
                        <table><?php echo $list_html; ?></table>
                    </td>
                </tr>
                <tr class="form-field">
                    <td></td>
                    <td>
                        <input type="hidden" name="contact_data[created_at]" value="<?php echo $created; ?>"/>
                        <input type="hidden" name="contact_data[guid]" value="<?php echo $guid; ?>"/>
                        <input type="hidden" name="submitted" value="submitted"/>
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'email-subscribers' ); ?>"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
		<?php
	}

	/**
	 * No contacts available
	 *
	 * @since 4.0.0
	 */
	public function no_items() {
		_e( 'No contacts avaliable.', 'email-subscribers' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 *
	 * @since 4.0.0
	 */
	public function column_default( $item, $column_name ) {
		$item = apply_filters( 'es_subscribers_col_data', $item, $column_name );
		switch ( $column_name ) {
			case 'lists':
				return $this->get_lists_to_show( $item['id'] );
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
			case 'first_name':
			case 'email':
			default:
				return $item[ $column_name ]; //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Prepare lists html to set status
	 *
	 * @param int $contact_id
	 * @param int $columns
	 *
	 * @return string
	 *
	 * @since 4.3.6
	 */
	public function prepare_lists_html( $contact_id = 0, $columns = 2 ) {
		$lists = ES()->lists_db->get_id_name_map();

		$lists_html = '';
		if ( count( $lists ) > 0 ) {

			$list_contact_status_map = array();
			if ( ! empty( $contact_id ) ) {
				$list_contact_status_map = ES()->lists_contacts_db->get_list_contact_status_map( $contact_id );
			}

			$lists_html = "<div><table class='ig-es-form-list-html'><tr>";

			$i = 0;
			foreach ( $lists as $list_id => $list_name ) {
				if ( $i != 0 && ( $i % $columns ) === 0 ) {
					$lists_html .= "</tr><tr>";
				}

				$selected = ! empty( $list_contact_status_map[ $list_id ] ) ? $list_contact_status_map[ $list_id ] : '';

				$status_dropdown_html = "<select class='ig-es-statuses-dropdown' name='contact_data[lists][" . $list_id . "]' >";
				$status_dropdown_html .= ES_Common::prepare_statuses_dropdown_options( $selected );
				$status_dropdown_html .= "</select>";

				$status_span = '';
				if ( ! empty( $list_contact_status_map[ $list_id ] ) ) {
					$status_span = '<span class="es_list_contact_status ' . $list_contact_status_map[ $list_id ] . '" title="' . ucwords( $list_contact_status_map[ $list_id ] ) . '">';
				}

				$list_name  = strlen( $list_name ) > 15 ? substr( $list_name, 0, 15 ) . '...' : $list_name;
				$lists_html .= "<td>$status_span$list_name</td><td>$status_dropdown_html</td>";

				$i ++;
			}

			$lists_html .= "</tr></table></div>";
		}

		return $lists_html;
	}

	/**
	 * Show lists with it's status
	 *
	 * @param $contact_id
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public function get_lists_to_show( $contact_id ) {

		$list_str = '';

		if ( isset( $this->contact_lists_statuses[ $contact_id ] ) ) {

			$lists = $this->contact_lists_statuses[ $contact_id ];

			if ( count( $lists ) > 0 ) {
				// Show only 4 lists
				//$contact_lists_to_display = array_slice( $lists, 0, 4 );
				foreach ( $lists as $list_id => $status ) {
					if ( ! empty( $this->lists_id_name_map[ $list_id ] ) ) {
						$list_str .= '<span class="es_list_contact_status ' . strtolower( $status ) . '" title="' . ucwords( $status ) . '">' . $this->lists_id_name_map[ $list_id ] . '</span> ';
					}
				}
			}
		}

		return $list_str;
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="subscribers[]" value="%s"/>', $item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	function column_name( $item ) {
		$delete_nonce = wp_create_nonce( 'ig_es_delete_subscriber' );

		$name  = ES_Common::prepare_name_from_first_name_last_name( $item['first_name'], $item['last_name'] );
		$title = '<strong>' . $name . '</strong>';

		$page = ig_es_get_request_data( 'page' );

		$actions = array(
			'edit'   => sprintf( __( '<a href="?page=%s&action=%s&subscriber=%s&_wpnonce=%s">Edit</a>', 'email-subscribers' ), esc_attr( $page ), 'edit', absint( $item['id'] ), $delete_nonce ),
			'delete' => sprintf( __( '<a href="?page=%s&action=%s&subscriber=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', 'email-subscribers' ), esc_attr( $page ), 'delete', absint( $item['id'] ), $delete_nonce ),
		);

		$actions['resend'] = sprintf( __( '<a href="?page=%s&action=%s&subscriber=%s&_wpnonce=%s">Resend Confirmation<a>', 'email-subscribers' ), esc_attr( ig_es_get_request_data( 'page' ) ), 'resend', absint( $item['id'] ), $delete_nonce );

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox"/>',
			'name'       => __( 'Name', 'email-subscribers' ),
			'email'      => __( 'Email', 'email-subscribers' ),
			'lists'      => __( 'List(s)', 'email-subscribers' ),
			'created_at' => __( 'Created', 'email-subscribers' ),
		);

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name'       => array( 'first_name', true ),
			'email'      => array( 'email', false ),
			// 'status' => array( 'status', false ),
			'created_at' => array( 'created_at', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk_delete'        => __( 'Delete', 'email-subscribers' ),
			'bulk_list_update'   => __( 'Move To List', 'email-subscribers' ),
			'bulk_list_add'      => __( 'Add To List', 'email-subscribers' ),
			'bulk_status_update' => __( 'Change Status', 'email-subscribers' )
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
        <p class="search-box box-ma10">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Contacts', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
        <p class="search-box search-group-box box-ma10">
			<?php $filter_by_status = ig_es_get_request_data( 'filter_by_status' ); ?>
            <select name="filter_by_status">
				<?php echo ES_Common::prepare_statuses_dropdown_options( $filter_by_status, __( 'All Statuses', 'email-subscribers' ) ); ?>
            </select>
        </p>
        <p class="search-box search-group-box box-ma10">
			<?php $filter_by_list_id = ig_es_get_request_data( 'filter_by_list_id' ); ?>
            <select name="filter_by_list_id">
				<?php echo ES_Common::prepare_list_dropdown_options( $filter_by_list_id, __( 'All Lists', 'email-subscribers' ) ); ?>
            </select>
        </p>

	<?php }

	/**
	 * Get Contact id
	 *
	 * @param $contact
	 *
	 * @return mixed
	 *
	 * @since 4.0.0
	 */
	public function get_contact_id( $contact ) {
		return $contact['id'];
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 *
	 * @since 4.0.0
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();
		$this->search_box( ig_es_get_request_data( 's' ), 'subscriber-search-input' );
		$this->prepare_lists_dropdown();
		$this->prepare_statuses_dropdown();

		$per_page     = $this->get_items_per_page( self::$option_per_page, 200 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_subscribers( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$contacts = $this->get_subscribers( $per_page, $current_page );


		$this->items = $contacts;

		if ( count( $contacts ) > 0 ) {

			$contact_ids = array_map( array( $this, 'get_contact_id' ), $contacts );

			$contact_lists_statuses = ES()->lists_contacts_db->get_list_status_by_contact_ids( $contact_ids );

			$this->contact_lists_statuses = $contact_lists_statuses;

			$this->lists_id_name_map = ES()->lists_db->get_list_id_name_map();
		}
	}

	/**
	 * Prepare list dropdown
	 *
	 * @since 4.0.0
	 */
	public function prepare_lists_dropdown() {
		$data = '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="list_id" id="list_id" class="groupsselect" style="display: none">';
		$data .= ES_Common::prepare_list_dropdown_options();
		$data .= '</select>';

		echo $data;
	}

	/**
	 * Edit Status
	 *
	 * @since 4.0.0
	 */
	public function prepare_statuses_dropdown() {
		$data = '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="status_select" id="status_select" class="statusesselect" style="display:none;">';
		$data .= ES_Common::prepare_statuses_dropdown_options();
		$data .= '</select>';

		echo $data;
	}

	/**
	 * Process Bulk Action
	 *
	 * @since 4.0.0
	 */
	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'ig_es_delete_subscriber' ) ) {
				die( 'You do not have a permission to delete contact(s)' );
			} else {
				$subscriber_id = absint( ig_es_get_request_data( 'subscriber' ) );
				$deleted       = ES()->contacts_db->delete_contacts_by_ids( array( $subscriber_id ) );
				if ( $deleted ) {
					$message = __( 'Contact(s) have been deleted successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}

				return;
			}

		}

		if ( 'resend' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'ig_es_delete_subscriber' ) ) {
				die( 'You do not have a permission to resend email confirmation' );
			} else {
				$id         = absint( ig_es_get_request_data( 'subscriber' ) );
				$resend     = ig_es_get_request_data( 'resend', false );
				$subscriber = ES()->contacts_db->get_by_id( $id );

				$email      = $subscriber['email'];
				$merge_tags = array(
					'contact_id' => $subscriber['id'],
				);

				if ( $resend ) {
					$message = __( 'Confirmation email has been sent successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );

					return;
				} else {
					$response = ES()->mailer->send_double_optin_email( $email, $merge_tags );
					$url      = add_query_arg( 'resend', true );
					//redirect to resend link and avoid resending email
					?>
                    <meta http-equiv="refresh" content="0; url=<?php echo $url; ?>"/>
					<?php
				}

				return;

			}

		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );

		$actions = array( 'bulk_delete', 'bulk_status_update', 'bulk_list_update', 'bulk_list_add' );
		if ( in_array( $action, $actions ) || in_array( $action2, $actions ) ) {

			$subscriber_ids = ig_es_get_request_data( 'subscribers' );
			if ( empty( $subscriber_ids ) ) {
				$message = __( 'Please select subscribers to update.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );

				return;
			}

			// If the delete bulk action is triggered
			if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

				$deleted = ES()->contacts_db->delete_contacts_by_ids( $subscriber_ids );

				if ( $deleted ) {
					$message = __( 'Contact(s) have been deleted successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}

				return;
			}

			if ( ( 'bulk_status_update' === $action ) || ( 'bulk_status_update' === $action2 ) ) {
				$status = ig_es_get_request_data( 'status_select' );

				if ( empty( $status ) ) {
					$message = __( 'Please select status.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );

					return;
				}

				// loop over the array of record IDs and delete them
				$edited = ES()->lists_contacts_db->edit_subscriber_status( $subscriber_ids, $status );

				if ( $edited ) {
					$message = __( 'Status has been changed successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}

				return;
			}

			if ( ( 'bulk_list_update' === $action ) || ( 'bulk_list_update' === $action2 ) ) {

				$list_id = ig_es_get_request_data( 'list_id' );
				if ( empty( $list_id ) ) {
					$message = __( 'Please select list.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );

					return;
				}

				$edited = ES()->lists_contacts_db->move_contacts_to_list( $subscriber_ids, $list_id );

				if ( $edited ) {
					$message = __( 'Contact(s) have been moved to list successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}

				return;
			}

			if ( ( 'bulk_list_add' === $action ) || ( 'bulk_list_add' === $action2 ) ) {

				$list_id = ig_es_get_request_data( 'list_id' );

				if ( empty( $list_id ) ) {
					$message = __( 'Please select list.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );

					return;
				}

				$edited = ES()->lists_contacts_db->add_contacts_to_list( $subscriber_ids, $list_id );

				if ( $edited ) {
					$message = __( 'Contact(s) have been added to list successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}

				return;
			}
		}
	}

	/**
	 * Remove contacts from list when list is deleted
	 *
	 * @param $list_id
	 *
	 * @since 4.3.1
	 * @since 4.3.5 Used remove_contacts_from_list method
	 */
	public function delete_contacts_from_list( $list_id = 0 ) {

		if ( empty( $list_id ) ) {
			return;
		}

		return ES()->lists_contacts_db->remove_all_contacts_from_list( $list_id );
	}

	/**
	 * Set form_id = 0 as Form is already deleted
	 *
	 * @param $form_id
	 *
	 * @since 4.3.1
	 */
	public function set_default_form_id( $form_id ) {

		global $wpdb;

		$ig_contacts_table = IG_CONTACTS_TABLE;

		$query = "UPDATE $ig_contacts_table SET form_id = 0 WHERE form_id = %d";

		$wpdb->query( $wpdb->prepare( $query, $form_id ) );
	}

}