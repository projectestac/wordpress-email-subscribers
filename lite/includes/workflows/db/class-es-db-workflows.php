<?php
/**
 * Workflow DB
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_DB_Workflows class
 *
 * @since 4.4.1
 */
class ES_DB_Workflows extends ES_DB {

	/**
	 * Workflow table name
	 *
	 * @since 4.4.1
	 * @var string $table_name
	 */
	public $table_name;

	/**
	 * Workflow table version
	 *
	 * @since 4.4.1
	 * @var string $version
	 */
	public $version;

	/**
	 * Workflow table primary key
	 *
	 * @since 4.4.1
	 * @var string
	 */
	public $primary_key;

	/**
	 * ES_DB_Workflows constructor.
	 *
	 * @since 4.4.1
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_workflows';

		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Get columns and formats
	 *
	 * @since  4.4.1
	 */
	public function get_columns() {
		return array(
			'id'              => '%d',
			'name'            => '%s',
			'title'           => '%s',
			'trigger_name'    => '%s',
			'trigger_options' => '%s',
			'rules'           => '%s',
			'actions'         => '%s',
			'meta'            => '%s',
			'status'          => '%d',
			'type'            => '%d',
			'priority'        => '%d',
			'created_at'      => '%s',
			'updated_at'      => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.4.1
	 */
	public function get_column_defaults() {

		return array(
			'name'            => null,
			'title'           => null,
			'trigger_name'    => null,
			'trigger_options' => '',
			'rules'           => '',
			'actions'         => '',
			'meta'            => '',
			'status'          => 1,
			'type'            => 0,
			'priority'        => 0,
			'created_at'      => ig_get_current_date_time(),
			'updated_at'      => '',
		);
	}

	/**
	 * Get workflows based on arguements
	 *
	 * @param  array   $query_args    Query arguements.
	 * @param  string  $output        Output format.
	 * @param  boolean $do_count_only Count only flag.
	 *
	 * @return mixed $result Query result
	 *
	 * @since 4.4.1
	 */
	public function get_workflows( $query_args = array(), $output = ARRAY_A, $do_count_only = false ) {

		global $wpdb, $wpbd;
		if ( $do_count_only ) {
			$sql = 'SELECT count(*) as total FROM ' . IG_WORKFLOWS_TABLE;
		} else {
			$sql = 'SELECT ';
			if ( ! empty( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
				$sql .= implode( ' ,', $query_args['fields'] );
			} else {
				$sql .= '*';
			}

			$sql .= ' FROM ' . IG_WORKFLOWS_TABLE;
		}

		$args  = array();
		$query = array();

		if ( ! empty( $query_args['ids'] ) ) {
			$ids_count        = count( $query_args['ids'] );
			$ids_placeholders = array_fill( 0, $ids_count, '%d' );
			$query[]          = ' id IN( ' . implode( ',', $ids_placeholders ) . ' )';
			$args             = array_merge( $args, $query_args['ids'] );
		}

		if ( ! empty( $query_args['s'] ) ) {
			$query[] = ' title LIKE %s ';
			$args[]  = '%' . $wpdb->esc_like( $query_args['s'] ) . '%';
		}

		if ( ! empty( $query_args['trigger_name'] ) ) {
			$query[] = ' trigger_name = %s ';
			$args[]  = $query_args['trigger_name'];
		}

		if ( ! empty( $query_args['trigger_names'] ) ) {
			$trigger_names_count        = count( $query_args['trigger_names'] );
			$trigger_names_placeholders = array_fill( 0, $trigger_names_count, '%s' );
			$query[]                    = ' trigger_name IN( ' . implode( ',', $trigger_names_placeholders ) . ' )';
			$args                       = array_merge( $args, $query_args['trigger_names'] );
		}

		if ( isset( $query_args['status'] ) ) {
			$query[] = ' status = %d ';
			$args[]  = $query_args['status'];
		}

		if ( isset( $query_args['type'] ) ) {
			if ( is_numeric( $query_args['type'] ) ) {
				$query[] = ' type = %d ';
				$args[]  = $query_args['type'];
			} elseif ( is_array( $query_args['type'] ) && count( $query_args['type'] ) > 0 ) {
				$type_count        = count( $query_args['type'] );
				$type_placeholders = array_fill( 0, $type_count, '%d' );
				$query[]           = ' type IN( ' . implode( ',', $type_placeholders ) . ' )';
				$args              = array_merge( $args, $query_args['type'] );
			}
		}

		$query = apply_filters( 'ig_es_workflow_list_where_caluse', $query );

		if ( count( $query ) > 0 ) {
			$sql .= ' WHERE ';

			$sql .= implode( ' AND ', $query );

			if ( count( $args ) > 0 ) {
				$sql = $wpbd->prepare( $sql, $args ); // phpcs:ignore
			}
		}

		if ( ! $do_count_only ) {

			$order                 = ! empty( $query_args['order'] ) ? strtolower( $query_args['order'] ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values, true ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'title', 'created_at', 'priority' );
			if ( empty( $query_args['order_by'] ) || ! in_array( $query_args['order_by'], $expected_order_by_values, true ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $query_args['order_by'] );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;

			if ( ! empty( $query_args['per_page'] ) ) {
				$sql .= ' LIMIT ' . $query_args['per_page'];
				if ( ! empty( $query_args['page_number'] ) ) {
					$sql .= ' OFFSET ' . ( $query_args['page_number'] - 1 ) * $query_args['per_page'];
				}
			}

			$result = $wpbd->get_results( $sql, $output ); // phpcs:ignore
		} else {
			$result = $wpbd->get_var( $sql ); // phpcs:ignore
		}

		return $result;

	}

	/**
	 * Get workflows by id
	 *
	 * @since 4.4.1
	 *
	 * @param int    $id     Workflow.
	 * @param string $output Output format.
	 *
	 * @return array|object|null
	 */
	public function get_workflow( $id = 0, $output = ARRAY_A ) {

		if ( empty( $id ) ) {
			return array();
		}

		$args = array(
			'ids' => array( $id ),
		);

		$workflows = $this->get_workflows( $args, $output );

		$workflow = array();
		if ( ! empty( $workflows ) ) {
			$workflow = array_shift( $workflows );
		}

		return $workflow;
	}

	/**
	 * Add workflow into database
	 *
	 * @since 4.4.1
	 *
	 * @param array $workflow_data Workflow data.
	 *
	 * @return int
	 */
	public function insert_workflow( $workflow_data = array() ) {

		if ( empty( $workflow_data ) || ! is_array( $workflow_data ) ) {
			return 0;
		}

		$workflow_id = $this->insert( $workflow_data );
		if ( $workflow_id ) {
			do_action( 'ig_es_workflow_inserted', $workflow_id, $workflow_data );
		}

		return $workflow_id;
	}

	/**
	 * Update Workflow
	 *
	 * @param int   $workflow_id   Workflow ID.
	 * @param array $workflow_data Workflow data.
	 *
	 * @return bool|void
	 *
	 * @since 4.4.1
	 */
	public function update_workflow( $workflow_id = 0, $workflow_data = array() ) {

		if ( empty( $workflow_id ) || empty( $workflow_data ) || ! is_array( $workflow_data ) ) {
			return;
		}

		// Set updated_at if not set.
		$workflow_data['updated_at'] = ! empty( $workflow_data['updated_at'] ) ? $workflow_data['updated_at'] : ig_get_current_date_time();

		$updated = $this->update( $workflow_id, $workflow_data );

		if ( $updated ) {
			// Clear workflow cache, so that while fetching we can get latest workflow data.
			ES_Cache::delete( $workflow_id, 'workflows' );
			
			do_action( 'ig_es_workflow_updated', $workflow_id, $workflow_data );
		}

		return $updated;
	}

	/**
	 * Delete Workflows
	 *
	 * @since 4.4.1
	 *
	 * @param array $ids Workflow IDs.
	 */
	public function delete_workflows( $ids = array() ) {

		global $wpbd;

		if ( ! is_array( $ids ) ) {
			$ids = array( absint( $ids ) );
		}

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				$id    = absint( $id );
				$where = $wpbd->prepare( "$this->primary_key = %d AND type != %d", $id, IG_ES_WORKFLOW_TYPE_SYSTEM );

				$workflow_deleted = $this->delete_by_condition( $where );

				if ( $workflow_deleted ) {
					/**
					 * Take necessary cleanup steps using this hook
					 *
					 * @since 4.4.1
					 */
					do_action( 'ig_es_workflow_deleted', $id );
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to update workflow status
	 *
	 * @param  array   $workflow_ids  Workflow IDs.
	 * @param  integer $status      New status.
	 * @return bool $updated        Update status
	 *
	 * @since 4.4.1
	 */
	public function update_status( $workflow_ids = array(), $status = 0 ) {
		global $wpbd;

		$updated = false;
		if ( empty( $workflow_ids ) ) {
			return $updated;
		}

		$workflow_ids = esc_sql( $workflow_ids );

		// Variable to hold workflow ids seperated by commas.
		$workflow_ids_str = '';
		if ( is_array( $workflow_ids ) && count( $workflow_ids ) > 0 ) {
			$workflow_ids_str = implode( ',', $workflow_ids );
		} elseif ( is_numeric( $workflow_ids ) ) {
			$workflow_ids_str = $workflow_ids;
		}

		if ( ! empty( $workflow_ids_str ) ) {
			$updated = $wpbd->query( $wpbd->prepare( "UPDATE {$wpbd->prefix}ig_workflows SET status = %d WHERE id IN ($workflow_ids_str)", $status ) );
		}

		do_action( 'ig_es_workflow_status_changed', $workflow_ids, $status );

		return $updated;

	}

	/**
	 * Method to migrate existing audience sync settings to workflows
	 *
	 * @since 4.4.1
	 */
	public function migrate_audience_sync_settings_to_workflows() {

		$audience_sync_settings = array(
			'ig_es_sync_wp_users'            => array(
				'workflow_title' => __( 'User Registered', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_user_registered',
			),
			'ig_es_sync_comment_users'       => array(
				'workflow_title' => __( 'Comment Added', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_comment_added',
			),
			'ig_es_sync_woocommerce_users'   => array(
				'workflow_title' => __( 'WooCommerce Order Completed', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_wc_order_completed',
			),
			'ig_es_sync_edd_users'           => array(
				'workflow_title' => __( 'EDD Purchase Completed', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_edd_complete_purchase',
			),
			'ig_es_sync_cf7_users'           => array(
				'workflow_title' => __( 'Contact Form 7 Submitted', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_cf7_submitted',
			),
			'ig_es_sync_ninja_forms_users'   => array(
				'workflow_title' => __( 'Ninja Form Submitted', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_ninja_forms_submitted',
			),
			'ig_es_sync_wpforms_users'       => array(
				'workflow_title' => __( 'WP Form Submitted', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_wpforms_submitted',
			),
			'ig_es_sync_give_users'          => array(
				'workflow_title' => __( 'Give Donation Added', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_give_donation_made',
			),
			'ig_es_sync_gravity_forms_users' => array(
				'workflow_title' => __( 'Gravity Form Submitted', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_gravity_forms_submitted',
			),
		);

		$workflows_data = array();
		foreach ( $audience_sync_settings as $sync_setting_name => $setting_workflow_data ) {
			$sync_settings = get_site_option( $sync_setting_name, false );
			$workflow_data = array();
			if ( ! empty( $sync_settings ) && is_array( $sync_settings ) ) {
				$workflow_data = $this->convert_audience_sync_setting_to_workflow( $sync_setting_name, $setting_workflow_data, $sync_settings );
			}
			if ( ! empty( $workflow_data ) ) {
				$workflows_data[] = $workflow_data;
			}
		}

		// Additional workflow required to support existing Audience synce settings e.g. Updating/Deleting contact list when a user gets updated/deleted.
		$additional_workflows = array(
			array(
				'workflow_title' => __( 'User deleted', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_user_deleted',
				'actions'        => array(
					'ig_es_delete_contact',
				),
				'requires'       => 'ig_es_sync_wp_users', // Sync setting required for the workflow to be active.
			),
			array(
				'workflow_title' => __( 'User updated', 'email-subscribers' ),
				'trigger_name'   => 'ig_es_user_updated',
				'actions'        => array(
					'ig_es_update_contact',
				),
				'requires'       => 'ig_es_sync_wp_users', // Sync setting required for the workflow to be active.
			),
		);

		foreach ( $additional_workflows as $workflow ) {
			$workflow_data = $this->get_additional_workflow( $workflow );
			if ( ! empty( $workflow_data ) ) {
				$workflows_data[] = $workflow_data;
			}
		}

		if ( ! empty( $workflows_data ) ) {
			return $this->bulk_insert( $workflows_data );
		}

		return false;
	}

	/**
	 * Method to convert audience sync setting to workflow
	 *
	 * @param string $sync_setting_name Sync setting option name.
	 * @param array  $setting_workflow_data Sync workflow name.
	 * @param array  $sync_settings Sync setting.
	 *
	 * @return array $workflows_data Workflow data
	 *
	 * @since 4.4.1
	 */
	public function convert_audience_sync_setting_to_workflow( $sync_setting_name = '', $setting_workflow_data = array(), $sync_settings = array() ) {

		$workflow_data = array();

		if ( empty( $sync_setting_name ) || empty( $setting_workflow_data ) || empty( $sync_settings ) ) {
			return $workflow_data;
		}

		$workflow_title = ! empty( $setting_workflow_data['workflow_title'] ) ? ES_Clean::string( $setting_workflow_data['workflow_title'] ) : '';
		$workflow_name  = ! empty( $workflow_title ) ? sanitize_title( $workflow_title ) : '';
		$trigger_name   = isset( $setting_workflow_data['trigger_name'] ) ? $setting_workflow_data['trigger_name'] : '';
		$actions        = array();

		// For ig_es_sync_wp_users option, list id is stored in 'es_registered_group' key, for others it is stored in 'list_id'.
		$list_key = ( 'ig_es_sync_wp_users' === $sync_setting_name ) ? 'es_registered_group' : 'list_id';
		if ( ! empty( $sync_settings[ $list_key ] ) ) {
			$list_id = $sync_settings[ $list_key ];
			if ( ! empty( $list_id ) ) {
				$actions[] = array(
					'action_name' => 'ig_es_add_to_list',
					'ig-es-list'  => ES_Clean::id( $list_id ),
				);
			}
		}

		$status = 0;
		// For ig_es_sync_wp_users option, enabled state is stored in 'es_registered' key, for others it is stored in 'enable'.
		$enabled_key = ( 'ig_es_sync_wp_users' === $sync_setting_name ) ? 'es_registered' : 'enable';
		if ( ! empty( $sync_settings[ $enabled_key ] ) ) {
			$is_sync_enabled = $sync_settings[ $enabled_key ];
			$is_sync_enabled = strtolower( $is_sync_enabled );
			if ( 'yes' === $is_sync_enabled ) {
				$status = 1;
			}
		}
		$workflow_meta                = array();
		$workflow_meta['when_to_run'] = 'immediately';

		$workflow_data = array(
			'name'         => $workflow_name,
			'title'        => $workflow_title,
			'trigger_name' => $trigger_name,
			'actions'      => maybe_serialize( $actions ),
			'meta'         => maybe_serialize( $workflow_meta ),
			'priority'     => 0,
			'status'       => $status,
		);

		return $workflow_data;
	}

	/**
	 * Migrate site notication setting into workflows
	 *
	 * @since 5.0.1
	 */
	public function migrate_notifications_to_workflows() {

		$notification_workflows = $this->get_notification_workflows();

		if ( ! empty( $notification_workflows ) ) {
			foreach ( $notification_workflows as $workflow ) {
				$workflow_title               = $workflow['title'];
				$workflow_name                = sanitize_title( $workflow_title );
				$trigger_name                 = $workflow['trigger_name'];
				$workflow_meta                = array();
				$workflow_meta['when_to_run'] = 'immediately';
				$workflow_status              = $workflow['status'];
	
				$workflow_actions = $workflow['actions'];
	
				$workflow_data = array(
					'name'         => $workflow_name,
					'title'        => $workflow_title,
					'trigger_name' => $trigger_name,
					'actions'      => maybe_serialize( $workflow_actions ),
					'meta'         => maybe_serialize( $workflow_meta ),
					'priority'     => 0,
					'status'       => $workflow_status,
				);
	
				ES()->workflows_db->insert_workflow( $workflow_data );
			}
		}
	}

	/**
	 * Get workflows for site notifications(user subscribed,confirmed,campaign sent)
	 *
	 * @return array $notification_workflows Workflow data
	 *
	 * @since 5.0.1
	 */
	public function get_notification_workflows() {

		$admin_emails = ES()->mailer->get_admin_emails();
		if ( ! empty( $admin_emails ) ) {
			$admin_emails = implode( ',', $admin_emails );
		}
		
		$notification_workflows = array(
			array(
				'trigger_name' => 'ig_es_user_subscribed',
				'title' 	   => __( 'Send welcome email when someone subscribes', 'email-subscribers' ),
				'actions'	   => array(
					array(
						'action_name' => 'ig_es_send_email',
						'ig-es-send-to'  => '{{EMAIL}}',
						'ig-es-email-subject'  => ES()->mailer->get_welcome_email_subject(),
						'ig-es-email-content'  => ES()->mailer->get_welcome_email_content(),
					),
				),
				'status' => ES()->mailer->can_send_welcome_email() ? 1 : 0,
				'type'   => IG_ES_WORKFLOW_TYPE_SYSTEM,
			),
			array(
				'trigger_name' => 'ig_es_user_unconfirmed',
				'title' 	   => __( 'Send confirmation email', 'email-subscribers' ),
				'actions'	   => array(
					array(
						'action_name' 	       => 'ig_es_send_email',
						'ig-es-send-to'  	   => '{{EMAIL}}',
						'ig-es-email-subject'  => ES()->mailer->get_confirmation_email_subject(),
						'ig-es-email-content'  => ES()->mailer->get_confirmation_email_content(),
					)
				),
				'status' => 1,
				'type'   => IG_ES_WORKFLOW_TYPE_SYSTEM,
			),
			array(
				'trigger_name' => 'ig_es_user_subscribed',
				'title' 	   => __( 'Notify admin when someone subscribes', 'email-subscribers' ),
				'actions'	   => array(
					array(
						'action_name' 		  => 'ig_es_send_email',
						'ig-es-send-to'       => $admin_emails,
						'ig-es-email-subject' => ES()->mailer->get_admin_new_contact_email_subject(),
						'ig-es-email-content' => ES()->mailer->get_admin_new_contact_email_content(),
					),
				),
				'status' => ES()->mailer->can_send_add_new_contact_notification() ? 1 : 0,
				'type'   => IG_ES_WORKFLOW_TYPE_SYSTEM,
			),
			array(
				'trigger_name' => 'ig_es_campaign_sent',
				'title' 	   => __( 'Notify admin when campaign is sent', 'email-subscribers' ),
				'actions'	   => array(
					array(
						'action_name' => 'ig_es_send_email',
						'ig-es-send-to'       => $admin_emails,
						'ig-es-email-subject' => ES()->mailer->get_cron_admin_email_subject(),
						'ig-es-email-content' => ES()->mailer->get_cron_admin_email_content(),
					),
				),
				'status' => ES()->mailer->can_send_cron_admin_email() ? 1 : 0,
				'type'   => IG_ES_WORKFLOW_TYPE_SYSTEM,
			),
		);

		return $notification_workflows;
	}

	/**
	 * Method to convert audience sync setting to workflow
	 *
	 * @param array $workflow workflow array.
	 *
	 * @return array $workflows_data Workflow data
	 *
	 * @since 4.4.1
	 */
	public function get_additional_workflow( $workflow = array() ) {

		$workflow_data = array();

		if ( empty( $workflow ) ) {
			return array();
		}

		$workflow_title = ! empty( $workflow['workflow_title'] ) ? ES_Clean::string( $workflow['workflow_title'] ) : '';
		$workflow_name  = ! empty( $workflow_title ) ? sanitize_title( $workflow_title ) : '';
		$trigger_name   = isset( $workflow['trigger_name'] ) ? $workflow['trigger_name'] : '';
		$actions        = array();

		if ( ! empty( $workflow['actions'] ) ) {
			foreach ( $workflow['actions'] as $action_name ) {
				$actions[] = array(
					'action_name' => $action_name,
				);
			}
		}

		$status = 0;
		if ( ! empty( $workflow['requires'] ) ) {
			$sync_setting_name = $workflow['requires'];
			$sync_setting      = get_site_option( $sync_setting_name );
			if ( ! empty( $sync_setting ) ) {
				// For ig_es_sync_wp_users option, enabled state is stored in 'es_registered' key, for others it is stored in 'enable'.
				$enabled_key = ( 'ig_es_sync_wp_users' === $sync_setting_name ) ? 'es_registered' : 'enable';
				if ( ! empty( $sync_setting[ $enabled_key ] ) ) {
					$is_sync_enabled = $sync_setting[ $enabled_key ];
					$is_sync_enabled = strtolower( $is_sync_enabled );
					if ( 'yes' === $is_sync_enabled ) {
						$status = 1;
					}
				}
			} else {
				return array();
			}
		}

		$workflow_meta                = array();
		$workflow_meta['when_to_run'] = 'immediately';

		$workflow_data = array(
			'name'         => $workflow_name,
			'title'        => $workflow_title,
			'trigger_name' => $trigger_name,
			'actions'      => maybe_serialize( $actions ),
			'meta'         => maybe_serialize( $workflow_meta ),
			'priority'     => 0,
			'status'       => $status,
		);

		return $workflow_data;
	}

	/**
	 * Method to migrate existing audience sync settings to ES admin settings
	 *
	 * @since 4.4.1
	 */
	public function migrate_audience_sync_settings_to_admin_settings() {

		$ig_es_sync_comment_users = get_option( 'ig_es_sync_comment_users', array() );

		if ( ! empty( $ig_es_sync_comment_users ) ) {

			$show_opt_in_consent = ! empty( $ig_es_sync_comment_users['enable'] ) && 'yes' === $ig_es_sync_comment_users['enable'] ? 'yes' : 'no';
			$opt_in_consent_text = ! empty( $ig_es_sync_comment_users['consent_text'] ) ? $ig_es_sync_comment_users['consent_text'] : '';

			update_site_option( 'ig_es_show_opt_in_consent', $show_opt_in_consent );
			update_site_option( 'ig_es_opt_in_consent_text', $opt_in_consent_text );
		}
	}

	/**
	 * Get workflow campaign ID
	 *
	 * @since 4.5.3
	 *
	 * @param int $workflow_id Wordkfow ID
	 *
	 * @return int $campaign_id ID of campaign used for tracking workflow emails.
	 */
	public function get_workflow_parent_campaign_id( $workflow_id ) {

		$campaign_id   = 0;
		$parent_id     = $workflow_id;
		$campaigns_ids = ES()->campaigns_db->get_campaigns_by_parent_id( $parent_id );

		if ( ! empty( $campaigns_ids ) ) {
			$campaign_id = $campaigns_ids[0];
		}

		return $campaign_id;
	}

	/**
	 * Create parent campaign for workflow
	 *
	 * @since 4.5.3
	 *
	 * @param string $workflow_title Wordkfow title
	 *
	 * @return int $tracking_campaign_id Created tracking campaign ID.
	 */
	public function create_parent_workflow_campaign( $workflow_id, $workflow_data ) {
		$campaign_name   = ! empty( $workflow_data['title'] ) ? $workflow_data['title'] : '';
		$campaign_slug   = ! empty( $campaign_name ) ? sanitize_title( $campaign_name ) : '';
		$campaign_type   = IG_CAMPAIGN_TYPE_WORKFLOW;
		$campaign_status = 1;
		$parent_id       = $workflow_id;
		$parent_type     = 'workflow';

		$campaing_data = array(
			'name'        => $campaign_name,
			'slug'        => $campaign_slug,
			'type'        => $campaign_type,
			'status'      => $campaign_status,
			'parent_id'   => $parent_id,
			'parent_type' => $parent_type,
		);

		$campaign_id = ES()->campaigns_db->save_campaign( $campaing_data );

		return $campaign_id;
	}

	/**
	 * Create parent campaign for workflow
	 *
	 * @since 4.5.3
	 *
	 * @param string $workflow_title Wordkfow title
	 *
	 * @return int $tracking_campaign_id Created tracking campaign ID.
	 */
	public function update_parent_workflow_campaign( $parent_campaign_id, $workflow_data ) {
		
		if ( empty( $parent_campaign_id ) ) {
			return;
		}

		$campaign_name   = ! empty( $workflow_data['title'] ) ? $workflow_data['title'] : '';
		$campaign_slug   = ! empty( $campaign_name ) ? sanitize_title( $campaign_name ) : '';
		$campaign_status = $workflow_data['status'];

		$campaing_data = array(
			'name'        => $campaign_name,
			'slug'        => $campaign_slug,
			'status'      => $campaign_status,
		);

		$campaign_id = ES()->campaigns_db->save_campaign( $campaing_data, $parent_campaign_id );

		return $campaign_id;
	}

	/**
	 * Delete workflows campaign
	 *
	 * @param array $workflow_ids
	 * @return boolean 
	 */
	public function delete_workflows_campaign( $workflow_ids ) {
		
		$delete_status = array();

		if ( ! is_array( $workflow_ids ) ) {
			$workflow_ids = array( absint( $workflow_ids ) );
		}

		if ( is_array( $workflow_ids ) && count( $workflow_ids ) > 0 ) {

			foreach ( $workflow_ids as $workflow_id ) {
				$parent_campaign_id = $this->get_workflow_parent_campaign_id( $workflow_id );
				if ( ! empty( $parent_campaign_id ) ) {
					$is_deleted = ES()->campaigns_db->delete_campaigns( $parent_campaign_id );
					if ( $is_deleted ) {
						$delete_status[ $workflow_id ] = 'deleted';
					} else {
						$delete_status[ $workflow_id ] = 'failed';
					}
				}
			}
		}

		return $delete_status;
	}

	/**
	 * Create child campaign for individual workflow send email action
	 *
	 * @since 4.5.3
	 *
	 * @param int $parent_campaign_id Parent campaign ID
	 * @param array  $action Action.
	 *
	 * @return int $tracking_campaign_id Created tracking campaign ID.
	 */
	public function create_child_tracking_campaign( $parent_campaign_id, $action ) {
		$campaign_name   = ! empty( $action['ig-es-email-subject'] ) ? $action['ig-es-email-subject'] : '';
		$campaign_body   = ! empty( $action['ig-es-email-content'] ) ? $action['ig-es-email-content'] : '';
		$campaign_slug   = ! empty( $campaign_name ) ? sanitize_title( $campaign_name ) : '';
		$campaign_type   = 'workflow_email';
		$parent_type     = 'workflow';
		$campaign_status = 1;
		$campaign_meta   = array(
			'enable_open_tracking' => ! empty( $action['ig-es-email-tracking-enabled'] ) ? 'yes' : 'no',
			'enable_link_tracking' => ! empty( $action['ig-es-email-tracking-enabled'] ) ? 'yes' : 'no',
			'attachments'		   => ! empty( $action['attachments'] ) ? $action['attachments'] : array(),
		);

		$campaing_data = array(
			'name'        => $campaign_name,
			'subject'     => $campaign_name,
			'body'        => $campaign_body,
			'slug'        => $campaign_slug,
			'type'        => $campaign_type,
			'status'      => $campaign_status,
			'parent_id'   => $parent_campaign_id,
			'parent_type' => $parent_type,
			'meta'		  => maybe_serialize( $campaign_meta ),
		);

		$campaign_id = ES()->campaigns_db->save_campaign( $campaing_data );
		return $campaign_id;
	}

	/**
	 * Update child campaign for individual workflow send email action
	 *
	 * @since 4.5.3
	 *
	 * @param int $campaign_id Campaign ID
	 * @param array  $action Action.
	 *
	 * @return boolean $updated Created tracking campaign ID.
	 */
	public function update_child_tracking_campaign( $campaign_id, $action ) {

		$campaign_name = ! empty( $action['ig-es-email-subject'] ) ? $action['ig-es-email-subject'] : '';
		$campaign_body = ! empty( $action['ig-es-email-content'] ) ? $action['ig-es-email-content'] : '';

		$campaign_meta = array(
			'enable_open_tracking' => ! empty( $action['ig-es-email-tracking-enabled'] ) ? 'yes' : 'no',
			'enable_link_tracking' => ! empty( $action['ig-es-email-tracking-enabled'] ) ? 'yes' : 'no',
			'attachments'		   => ! empty( $action['attachments'] ) ? $action['attachments'] : array(),
		);

		$campaing_data = array(
			'name'    => $campaign_name,
			'subject' => $campaign_name,
			'body'    => $campaign_body,
			'meta'    => maybe_serialize( $campaign_meta ),
		);

		$updated = ES()->campaigns_db->update( $campaign_id, $campaing_data );
		return $updated;
	}

	/**
	 * Get ids of child tracking campaigns
	 *
	 * @param int $workflow_id
	 * @return array $child_tracking_campaign_ids
	 * 
	 * @since 5.0.6
	 */
	public function get_all_child_tracking_campaign_ids( $workflow_id ) {

		$child_tracking_campaign_ids = array();

		$parent_campaign_id       = $this->get_workflow_parent_campaign_id( $workflow_id );
		$child_tracking_campaigns = ES()->campaigns_db->get_campaign_by_parent_id( $parent_campaign_id );
		if ( ! empty( $child_tracking_campaigns ) ) {
			foreach ( $child_tracking_campaigns as $tracking_campaign ) {
				$child_tracking_campaign_ids[] = $tracking_campaign['id'];
			}
		}

		return $child_tracking_campaign_ids;
	}

	/**
	 * Get count of active workflows
	 *
	 * @return int active workflows count
	 * 
	 * @since 5.5.7
	 */
	public function get_active_workflows_count() {

		$args = array( 
			'status' => '1',
		);

		return self::get_workflows( $args, null, true );
	}

	/**
	 * Get count of active workflows grouped by common trigger names
	 *
	 * @return array $result
	 * 
	 * @since 5.5.7
	 */
	public function get_workflows_count_by_triggername() {

		global $wpdb;

		$result = $wpdb->get_results( "SELECT trigger_name, count(trigger_name) AS trigger_count FROM {$wpdb->prefix}ig_workflows WHERE status = 1 GROUP BY trigger_name", ARRAY_A );

		return $result;
	}

}

