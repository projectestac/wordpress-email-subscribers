<?php


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */
class Email_Subscribers_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0
	 * @var      string $email_subscribers The ID of this plugin.
	 */
	private $email_subscribers;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $email_subscribers The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0
	 *
	 */
	public function __construct( $email_subscribers, $version ) {

		$this->email_subscribers = $email_subscribers;
		$this->version           = $version;

		// Reorder ES Submenu

		// Commenting out since we are now registering the submenus in the order required. Therefore no need to change the submenu order later on.
		//add_filter( 'custom_menu_order', array( $this, 'submenu_order' ) );

		add_action( 'admin_menu', array( $this, 'email_subscribers_admin_menu' ) );
		add_action( 'wp_ajax_es_klawoo_subscribe', array( $this, 'klawoo_subscribe' ) );
		add_action( 'admin_footer', array( $this, 'remove_submenu' ) );
		add_action( 'admin_init', array( $this, 'es_save_onboarding_skip' ) );

		// Ajax handler for campaign status toggle.
		add_action( 'wp_ajax_ig_es_toggle_campaign_status', array( $this, 'toggle_campaign_status' ) );

		add_action( 'admin_init', array( $this, 'ob_start' ) );
		
		add_action( 'init', array( $this, 'save_screen_option' ) );
		
		// Add spam score ajax action.
		add_action( 'wp_ajax_es_get_spam_score', array( &$this, 'get_spam_score' ) );
		
		// Add send cron data action.
		add_action( 'admin_head', array( $this, 'send_cron_data' ) );
		add_action( 'ig_es_after_settings_save', array( $this, 'send_cron_data' ) );
		
		
		// Process and add premium service data(Inline CSS, UTM Tracking etc) to template body.
		add_filter( 'es_after_process_template_body', array( $this, 'add_premium_services_data') );
		
		// Filter to add premium service request data.
		add_filter( 'ig_es_util_data', array( $this, 'add_util_data') );
		
		// Filter to check if utm tracking is enabled.
		add_filter( 'ig_es_track_utm', array( $this, 'is_utm_tracking_enabled'), 10, 2 );
		
		// Disable Icegram server cron when plugin is deactivated.
		add_action( 'ig_es_plugin_deactivate', array( $this, 'disable_server_cron' ) );
		
		// Filter to hook custom validation for specific service request.
		add_filter( 'ig_es_service_request_custom_validation', array( $this, 'maybe_override_service_validation' ), 10, 2 );

		if ( class_exists( 'IG_ES_Premium_Services_UI' ) ) {
			IG_ES_Premium_Services_UI::instance();
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    4.0
	 */
	public function enqueue_styles() {

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Email_Subscribers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Email_Subscribers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'css/email-subscribers-admin.css', array(), $this->version, 'all' );

		wp_register_style( $this->email_subscribers . '-timepicker', plugin_dir_url( __FILE__ ) . 'css/jquery.timepicker.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->email_subscribers . '-timepicker' );

		wp_enqueue_style( 'ig-es-style', plugin_dir_url( __FILE__ ) . 'dist/main.css', array(), $this->version, 'all' );

		$get_page = ig_es_get_request_data( 'page' );
		if ( ! empty( $get_page ) && 'es_reports' === $get_page ) {
			wp_enqueue_style( 'flag-icon-css', 'https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    4.0
	 */
	public function enqueue_scripts() {

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		wp_enqueue_script( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'js/email-subscribers-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' ), $this->version, false );

		$ig_es_js_data = array(
			'security'  => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
			'i18n_data' => array(
				'ajax_error_message'              => __( 'An error has occured. Please try again later.', 'email-subscribers' ),
				'broadcast_draft_success_message' => __( 'Broadcast saved as draft successfully.', 'email-subscribers' ),
				'broadcast_draft_error_message'   => __( 'An error has occured while saving the broadcast. Please try again later.', 'email-subscribers' ),
				'broadcast_subject_empty_message' => __( 'Please add a broadcast subject before saving.', 'email-subscribers' ),
				'empty_template_message'          => __( 'Please add email body.', 'email-subscribers' ),
			),
		);

		wp_localize_script( $this->email_subscribers, 'ig_es_js_data', $ig_es_js_data );

		$page_prefix = ES()->get_admin_page_prefix();

		if ( ES()->is_es_admin_screen( $page_prefix . '_page_es_workflows' ) ) {

			wp_enqueue_script( $this->email_subscribers . '-workflows', plugin_dir_url( __FILE__ ) . 'js/ig-es-workflows.js', array( 'jquery', 'jquery-ui-datepicker' ), $this->version, false );

			$workflows_data = array(
				'security'                   => wp_create_nonce( 'ig-es-workflow-nonce' ),
				'no_trigger_message'         => __( 'Please select a trigger before saving the workflow.', 'email-subscribers' ),
				'no_actions_message'         => __( 'Please add some actions before saving the workflow.', 'email-subscribers' ),
				'no_action_selected_message' => __( 'Please select an action that this workflow should perform before saving the workflow.', 'email-subscribers' ),
				'trigger_change_message'     => __( 'Changing the trigger will remove existing actions. Do you want to proceed anyway?.', 'email-subscribers' ),
			);

			wp_localize_script( $this->email_subscribers . '-workflows', 'ig_es_workflows_data', $workflows_data );

			if ( ! function_exists( 'ig_es_wp_js_editor_admin_scripts' ) ) {
				/**
				 * Include WP JS Editor library's main file. This file contains required functions to enqueue required js file which being used to create WordPress editor dynamcially.
				 */
				require_once ES_PLUGIN_DIR . 'lite/includes/libraries/wp-js-editor/wp-js-editor.php';
			}
	
			// Load required html/js for dynamic WordPress editor.
			ig_es_wp_js_editor_admin_scripts();
		}

		//timepicker
		wp_register_script( $this->email_subscribers . '-timepicker', plugin_dir_url( __FILE__ ) . 'js/jquery.timepicker.js', array( 'jquery' ), ES_PLUGIN_VERSION, true );
		wp_enqueue_script( $this->email_subscribers . '-timepicker' );

		$get_page = ig_es_get_request_data( 'page' );
		if ( ! empty( $get_page ) && 'es_dashboard' === $get_page || 'es_reports' === $get_page ) {
			wp_enqueue_script( 'frappe-js', 'https://unpkg.com/frappe-charts@1.5.2/dist/frappe-charts.min.iife.js', array( 'jquery' ), $this->version, false );
		}
		

	}

	public function remove_submenu() {
		//remove submenues
		?>
		<script type="text/javascript">
			jQuery(document).ready(function () {
				var removeSubmenu = ['ig-es-broadcast', 'ig-es-lists', 'ig-es-post-notifications', 'ig-es-sequence'];
				jQuery.each(removeSubmenu, function (key, id) {
					jQuery("#" + id).parent('a').parent('li').hide();
				});
			})
		</script>
		<?php
	}
	
	public function email_subscribers_admin_menu() {
		
		$accessible_sub_menus = ES_Common::ig_es_get_accessible_sub_menus();
		
		if ( count( $accessible_sub_menus ) > 0 ) {

			$menu_title = ES()->get_admin_menu_title();

			// This adds the main menu page.
			add_menu_page( $menu_title, $menu_title, 'edit_posts', 'es_dashboard', array( $this, 'es_dashboard_callback' ), 'dashicons-email', 30 );
			
			if ( 'woo' === IG_ES_PLUGIN_PLAN ) {
				// Add Icegram submenu under WooCommerce marketing admin menu.
				add_submenu_page( 'woocommerce-marketing', $menu_title, $menu_title, 'manage_woocommerce', 'es_dashboard', array( $this, 'es_dashboard_callback' ) );
			}
			
			// Submenu.
			add_submenu_page( 'es_dashboard', __( 'Dashboard', 'email-subscribers' ), __( 'Dashboard', 'email-subscribers' ), 'edit_posts', 'es_dashboard', array( $this, 'es_dashboard_callback' ) );
		}
		
		if ( in_array( 'audience', $accessible_sub_menus ) ) {
			// Add Contacts Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Audience', 'email-subscribers' ), __( 'Audience', 'email-subscribers' ), 'edit_posts', 'es_subscribers', array( $this, 'render_contacts' ) );
			add_action( "load-$hook", array( 'ES_Contacts_Table', 'screen_options' ) );
	
			// Add Lists Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Lists', 'email-subscribers' ), '<span id="ig-es-lists">' . __( 'Lists', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_lists', array( $this, 'render_lists' ) );
			//add_action( "load-$hook", array( 'ES_Lists_Table', 'screen_options' ) );
		}

		if ( in_array( 'forms', $accessible_sub_menus ) ) {
			// Add Forms Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Forms', 'email-subscribers' ), __( 'Forms', 'email-subscribers' ), 'edit_posts', 'es_forms', array( $this, 'render_forms' ) );
			//add_action( "load-$hook", array( 'ES_Forms_Table', 'screen_options' ) );
		}
		
		if ( in_array( 'campaigns', $accessible_sub_menus ) ) {
			// Add Campaigns Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Campaigns', 'email-subscribers' ), __( 'Campaigns', 'email-subscribers' ), 'edit_posts', 'es_campaigns', array( $this, 'render_campaigns' ) );
			//add_action( "load-$hook", array( 'ES_Campaigns_Table', 'screen_options' ) );
			
			// Start-IG-Code.
			add_submenu_page( 'es_dashboard', __( 'Post Notifications', 'email-subscribers' ), '<span id="ig-es-post-notifications">' . __( 'Post Notifications', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_notifications', array( $this, 'load_post_notifications' ) );
			// End-IG-Code.
			add_submenu_page( 'es_dashboard', __( 'Broadcast', 'email-subscribers' ), '<span id="ig-es-broadcast">' . __( 'Broadcast', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_newsletters', array( $this, 'load_newsletters' ) );
			add_submenu_page( null, __( 'Template Preview', 'email-subscribers' ), __( 'Template Preview', 'email-subscribers' ), 'edit_posts', 'es_template_preview', array( $this, 'load_preview' ) );
			
		}
		
		if ( in_array( 'workflows', $accessible_sub_menus ) ) {

			// Add Workflows Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Workflows', 'email-subscribers' ), __( 'Workflows', 'email-subscribers' ), 'edit_posts', 'es_workflows', array( $this, 'render_workflows' ) );

			//add_action( "load-$hook", array( 'ES_Workflows_Table', 'screen_options' ) );
			add_action( "load-$hook", array( 'ES_Workflow_Admin_Edit', 'register_meta_boxes' ) );
			add_action( "admin_footer-$hook", array( 'ES_Workflow_Admin_Edit', 'print_script_in_footer' ) );
			add_action( 'admin_init', array( 'ES_Workflow_Admin_Edit', 'maybe_save' ) );
		}

		if ( in_array( 'reports', $accessible_sub_menus ) ) {
			add_submenu_page( 'es_dashboard', __( 'Reports', 'email-subscribers' ), __( 'Reports', 'email-subscribers' ), 'edit_posts', 'es_reports', array( $this, 'load_reports' ) );
		}

		if ( in_array( 'settings', $accessible_sub_menus ) ) {
			add_submenu_page( 'es_dashboard', __( 'Settings', 'email-subscribers' ), __( 'Settings', 'email-subscribers' ), 'manage_options', 'es_settings', array( $this, 'load_settings' ) );
		}

		// Start-IG-Code.
		if ( in_array( 'ig_redirect', $accessible_sub_menus ) ) {
			add_submenu_page( null, __( 'Go To Icegram', 'email-subscribers' ), '<span id="ig-es-onsite-campaign">' . __( 'Go To Icegram', 'email-subscribers' ) . '</span>', 'edit_posts', 'go_to_icegram', array( $this, 'go_to_icegram' ) );
		}
		// End-IG-Code.

		/**
		 * Add Other Submenu Pages
		 *
		 * @since 4.3.0
		 */
		do_action( 'ig_es_add_submenu_page', $accessible_sub_menus );

	}

	public function plugins_loaded() {
		ES_Templates_Table::get_instance();
		new Export_Subscribers();
		new ES_Handle_Post_Notification();
		ES_Handle_Sync_Wp_User::get_instance();
		new ES_Import_Subscribers();
		// Start-IG-Code.
		ES_Info::get_instance();
		// End-IG-Code.
		ES_Newsletters::get_instance();
		ES_Tools::get_instance();
		new ES_Tracking();
	}

	/** 
	 * Function for Klawoo's Subscribe form on Help & Info page
	 * 
	 * @param boolean $return Flag to check return response instead of exiting in the function itself.
	*/
	public static function klawoo_subscribe( $return = false ) {

		// We don't need to do nonce validation in case if the function is being called from other function.
		if ( ! $return ) {
			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		}

		$response = array(
			'status' => 'error',
		);

		$url = 'http://app.klawoo.com/subscribe';

		$form_source = ig_es_get_request_data( 'from_source' );
		if ( ! empty( $form_source ) ) {
			update_option( 'ig_es_onboarding_status', $form_source );
		}

		if ( ! empty( $_POST ) ) {
			$params = ig_es_get_data( $_POST, '', array(), true );
		} else {
			if ( ! $return ) {
				exit();
			} else {
				return $response;
			}
		}
		$method = 'POST';
		$qs     = http_build_query( $params );

		$options = array(
			'timeout' => 15,
			'method'  => $method
		);

		if ( 'POST' == $method ) {
			$options['body'] = $qs;
		} else {
			if ( strpos( $url, '?' ) !== false ) {
				$url .= '&' . $qs;
			} else {
				$url .= '?' . $qs;
			}
		}

		$request_response = wp_remote_request( $url, $options );
		if ( 200 == wp_remote_retrieve_response_code( $request_response ) ) {
			$data = $request_response['body'];
			if ( 'error' != $data ) {

				$message_start = substr( $data, strpos( $data, '<body>' ) + 6 );
				$remove        = substr( $message_start, strpos( $message_start, '</body>' ) );
				$message       = trim( str_replace( $remove, '', $message_start ) );
				if ( ! $return ) {
					echo wp_kses_post( $message );
					exit();
				} else {
					$response['status']  = 'success';
					$response['message'] = $message;
					return $response;
				}
			}
		}
		if ( ! $return ) {
			exit();
		} else {
			return $response;
		}
	}

	/**
	 * Render Workflows Screen
	 *
	 * @since 4.2.1
	 */
	public function render_workflows() {
		$workflows = new ES_Workflows_Table();
		$workflows->render();
	}

	/**
	 * Render Campaigns Screen
	 *
	 * @since 4.2.1
	 */
	public function render_campaigns() {
		$campaigns = new ES_Campaigns_Table();
		$campaigns->render();
	}

	/**
	 * Render Contacts Screen
	 *
	 * @since 4.2.1
	 */
	public function render_contacts() {
		$contacts = new ES_Contacts_Table();
		$contacts->render();
	}

	/**
	 * Render Forms Screen
	 *
	 * @since 4.2.1
	 */
	public function render_forms() {
		$forms = new ES_Forms_Table();
		$forms->render();
	}

	/**
	 * Render Lists Screen
	 *
	 * @since 4.2.1
	 */
	public function render_lists() {
		$lists = new ES_Lists_Table();
		$lists->render();
	}

	/**
	 * Render Post Notifications
	 *
	 * @since 4.2.1
	 */
	public function load_post_notifications() {
		$post_notifications = ES_Post_Notifications_Table::get_instance();
		$post_notifications->es_notifications_callback();
	}

	/**
	 * Render Newsletters
	 *
	 * @since 4.2.1
	 */
	public function load_newsletters() {
		$newsletters = ES_Newsletters::get_instance();
		$newsletters->es_newsletters_settings_callback();
	}

	/**
	 * Render Reports
	 *
	 * @since 4.2.1
	 */
	public function load_reports() {
		$reports = ES_Reports_Table::get_instance();
		$reports->es_reports_callback();
	}

	/**
	 * Render Settings
	 *
	 * @since 4.2.1
	 */
	public function load_settings() {
		$settings = ES_Admin_Settings::get_instance();
		$settings->es_settings_callback();
	}

	/**
	 * Render Preview
	 *
	 * @since 4.2.1
	 */
	public function load_preview() {
		$preview = ES_Templates_Table::get_instance();
		$preview->es_template_preview_callback();
	}

	/**
	 * Redirect to icegram if required
	 *
	 * @since 4.4.1
	 */
	public function go_to_icegram() {
		ES_IG_Redirect::go_to_icegram();
	}


	public function submenu_order( $menu_order ) {
		global $submenu;

		$es_menus = isset( $submenu['es_dashboard'] ) ? $submenu['es_dashboard'] : array();

		if ( ! empty( $es_menus ) ) {

			$es_menu_order = array(
				'es_dashboard',
				'es_subscribers',
				'es_lists',
				'es_forms',
				'es_campaigns',
				'es_workflows',
				'edit.php?post_type=es_template',
				'es_notifications',
				'es_newsletters',
				'es_sequence',
				'es_integrations',
				'es_reports',
				'es_tools',
				'es_settings',
				'es_general_information',
				'es_pricing',
			);

			$order = array_flip( $es_menu_order );

			$reorder_es_menu = array();
			foreach ( $es_menus as $menu ) {
				$reorder_es_menu[ $order[ $menu[2] ] ] = $menu;
			}

			ksort( $reorder_es_menu );

			//$submenu['es_dashboard'] = $reorder_es_menu;
		}

		# Return the new submenu order
		return $menu_order;
	}

	public function es_dashboard_callback() {
		// $es_plugin_data           = get_plugin_data( ES_PLUGIN_DIR .'/' .ES_PLUGIN_FILE );
		$es_current_version       = ES_PLUGIN_VERSION;
		$admin_email              = get_option( 'admin_email' );
		$ig_es_db_update_history  = ES_Common::get_ig_option( 'db_update_history', array() );
		$ig_es_4015_db_updated_at = ( is_array( $ig_es_db_update_history ) && isset( $ig_es_db_update_history['4.0.15'] ) ) ? $ig_es_db_update_history['4.0.15'] : false;

		$is_sa_option_exists = get_option( 'current_sa_email_subscribers_db_version', false );
		$onboarding_status   = get_option( 'ig_es_onboarding_complete', 'no' );
		if ( ! $is_sa_option_exists && ! $ig_es_4015_db_updated_at && 'yes' !== $onboarding_status ) {
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/onboarding.php';
		} else {
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/dashboard.php';
		}

	}

	//save skip signup option
	public function es_save_onboarding_skip() {

		$es_skip     = ig_es_get_request_data( 'es_skip' );
		$option_name = ig_es_get_request_data( 'option_name' );


		if ( '1' == $es_skip && ! empty( $option_name ) ) {
			/**
			 * If user logged in then only save option.
			 */
			$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
			if ( $can_access_settings ) {
				update_option( 'ig_es_ob_skip_' . $option_name, 'yes' );
			}

			$referer = wp_get_referer();

			wp_safe_redirect( $referer );
			exit();
		}
	}

	public function count_contacts_by_list() {

		$list_id = ig_es_get_request_data( 'list_id', 0 );
		$status  = ig_es_get_request_data( 'status', 'all' );

		if ( 0 == $list_id ) {
			return 0;
		}

		$total_count = ES()->lists_contacts_db->get_total_count_by_list( $list_id, $status );

		die( json_encode( array( 'total' => $total_count ) ) );
	}

	public function get_template_content() {
		global $ig_es_tracker;

		$template_id = (int) ig_es_get_request_data( 'template_id', 0 );
		if ( 0 == $template_id ) {
			return 0;
		}
		$post_temp_arr     = get_post( $template_id );
		$result['subject'] = ! empty( $post_temp_arr->post_title ) ? $post_temp_arr->post_title : '';
		$result['body']    = ! empty( $post_temp_arr->post_content ) ? $post_temp_arr->post_content : '';
		//get meta data of template
		// $active_plugins = $ig_es_tracker::get_active_plugins();
		if ( ES()->is_starter() ) {
			$result['inline_css']      = get_post_meta( $template_id, 'es_custom_css', true );
			$result['es_utm_campaign'] = get_post_meta( $template_id, 'es_utm_campaign', true );
		}

		die( json_encode( $result ) );
	}

	/**
	 * Get Email Subscribers' screen options
	 *
	 * @return array
	 * 
	 * @since 4.5.4
	 */
	public function get_admin_screen_options() {

		$admin_screen_options = array(
			'es_campaigns_per_page',
			'es_contacts_per_page',
			'es_lists_per_page',
			'es_forms_per_page',
			'es_workflows_per_page',
		);

		return apply_filters( 'ig_es_admin_screen_options', $admin_screen_options );
	}

	/**
	 * Hooked to 'set-screen-options' filter
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 *
	 * @since 4.2.1
	 */
	public function save_screen_options( $status, $option, $value ) {

		$admin_screen_options = $this->get_admin_screen_options();

		if ( in_array( $option, $admin_screen_options ) ) {

			return $value;
		}

		return $status;
	}

	/**
	 * Hook 'save_screen_options' function to "set_screen_option_{$option}" filter to allow saving of ES custom screen options in WP 5.4.2
	 *
	 * @since 4.5.4
	 */
	public function save_screen_option() {

		$admin_screen_options = $this->get_admin_screen_options();

		if ( ! empty( $admin_screen_options ) && is_array( $admin_screen_options ) ) {
			foreach ( $admin_screen_options as $option ) {
				add_filter( "set_screen_option_{$option}", array( $this, 'save_screen_options' ), 10, 3 );
			}
		}
	}

	/**
	 * Remove all admin notices
	 *
	 * @since 4.4.0
	 */
	public function remove_other_admin_notices() {
		global $wp_filter;

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$get_page = ig_es_get_request_data( 'page' );

		if ( ! empty( $get_page ) && 'es_dashboard' == $get_page ) {

			// Allow only Icegram Connection popup on Dashboard
			$es_display_notices = array(
				'connect_icegram_notification',
			);

		} else {

			$es_display_notices = array(
				'connect_icegram_notification',
				'show_review_notice',
				'custom_admin_notice',
				'output_custom_notices',
				'ig_es_fail_php_version_notice',
			);
		}

		// User admin notices
		if ( ! empty( $wp_filter['user_admin_notices']->callbacks ) && is_array( $wp_filter['user_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['user_admin_notices']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $name => $details ) {

					if ( is_object( $details['function'] ) && $details['function'] instanceof \Closure ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}

					if ( ! empty( $details['function'][0] ) && is_object( $details['function'][0] ) && count( $details['function'] ) == 2 ) {
						$notice_callback_name = $details['function'][1];
						if ( ! in_array( $notice_callback_name, $es_display_notices ) ) {
							unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}
		}

		// Admin notices
		if ( ! empty( $wp_filter['admin_notices']->callbacks ) && is_array( $wp_filter['admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['admin_notices']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $name => $details ) {

					if ( is_object( $details['function'] ) && $details['function'] instanceof \Closure ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}

					if ( ! empty( $details['function'][0] ) && is_object( $details['function'][0] ) && count( $details['function'] ) == 2 ) {
						$notice_callback_name = $details['function'][1];
						if ( ! in_array( $notice_callback_name, $es_display_notices ) ) {
							unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}
		}

		// All admin notices
		if ( ! empty( $wp_filter['all_admin_notices']->callbacks ) && is_array( $wp_filter['all_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['all_admin_notices']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $name => $details ) {

					if ( is_object( $details['function'] ) && $details['function'] instanceof \Closure ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}

					if ( ! empty( $details['function'][0] ) && is_object( $details['function'][0] ) && count( $details['function'] ) == 2 ) {
						$notice_callback_name = $details['function'][1];
						if ( ! in_array( $notice_callback_name, $es_display_notices ) ) {
							unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}
		}

	}

	/**
	 * Method to handle campaign status change
	 *
	 * @return string JSON response of the request
	 *
	 * @since 4.4.4
	 */
	public function toggle_campaign_status() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$campaign_id         = ig_es_get_request_data( 'campaign_id' );
		$new_campaign_status = ig_es_get_request_data( 'new_campaign_status' );

		if ( ! empty( $campaign_id ) ) {

			$status_updated = ES()->campaigns_db->update_status( $campaign_id, $new_campaign_status );

			if ( $status_updated ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}
	}

	/**
	 * Update admin footer text
	 *
	 * @param $footer_text
	 *
	 * @return string
	 *
	 * @since 4.4.6
	 */
	public function update_admin_footer_text( $footer_text ) {

		// Update Footer admin only on ES pages
		if ( ES()->is_es_admin_screen() ) {

			$wordpress_url = 'https://www.wordpress.org';
			$icegram_url   = 'https://www.icegram.com';

			/* translators: 1. WordPress URL 2. Email Subscribers version 3. Icegram site URL */
			$footer_text = sprintf( __( '<span id="footer-thankyou">Thank you for creating with <a href="%1$s" target="_blank">WordPress</a> | Email Subscribers <b>%2$s</b>. Developed by team <a href="%3$s" target="_blank">Icegram</a></span>', 'email-subscribers' ), $wordpress_url, ES_PLUGIN_VERSION, $icegram_url );
		}

		return $footer_text;
	}

	/**
	 * Method to start output buffering to allows admin screens to make redirects later on.
	 * 
	 * @since 4.5.2
	 */
	public function ob_start() {
		ob_start();
	}

	/**
	 * Method to get spam score
	 * 
	 * @since 4.6.1
	 */
	public function get_spam_score() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		
		global $post;

		$response = array(
			'status'        => 'error',
			'error_message' => __( 'Something went wrong', 'email-subscribers' ),
		);

		$admin_email       = get_option( 'admin_email' );
		if ( ! empty( $_REQUEST['action'] ) && 'es_get_spam_score' == $_REQUEST['action'] ) {

			$sender_data = array();

			if ( ! empty( $_REQUEST['tmpl_id'] ) ) {
				$content_post = get_post( sanitize_text_field( $_REQUEST['tmpl_id'] ) );
				$content      = $content_post->post_content;
				$subject      = $content_post->post_title;
			} else {
				$content    = ig_es_get_request_data( 'content', '', false );
				$subject    = ig_es_get_request_data( 'subject', '', false );
				$from_email = ig_es_get_request_data( 'from_email' );
				$from_name  = ig_es_get_request_data( 'from_name' );

				$sender_data['from_name']  = $from_name;
				$sender_data['from_email'] = $from_email;
			}
			// $data['content'] = $content;
			$header = $this->get_email_headers( $sender_data );

			// Add a new line character to allow following header data to be appended correctly.
			$header .= "\n";
				
			// Add subject if set.
			if ( ! empty( $subject ) ) {
				$header .= 'Subject:' . $subject . "\n";
			}

			$header             .= 'Date: ' . gmdate( 'r' ) . "\n";
			$header             .= 'To: ' . $admin_email . "\n";
			$header             .= 'Message-ID: <' . $admin_email . ">\n";
			$header             .= "MIME-Version: 1.0\n";
			$data['email']       = $header . $content;
			$data['tasks'][]     = 'spam-score';

			$spam_score_service = new ES_Service_Spam_Score_Check();
			$service_response   = $spam_score_service->get_spam_score( $data );
			if ( ! empty( $service_response['status'] ) && 'success' === $service_response['status'] ) {
				$response['status'] = 'success';
				$response['res']    = $service_response['data'];
			}

			wp_send_json( $response );
		}
	}

	/**
	 * Method to get email header.
	 *
	 * @param array $sender_data .
	 *
	 * @return array $headers
	 *
	 * @since 4.6.1
	 */
	public function get_email_headers( $sender_data = array() ) {
		$get_email_type = get_option( 'ig_es_email_type', true );
		$site_title     = get_bloginfo();
		$admin_email    = get_option( 'admin_email' );

		$from_name  = '';
		$from_email = '';
		if ( ! empty( $sender_data ) ) {
			$from_name  = $sender_data['from_name'];
			$from_email = $sender_data['from_email'];
		}

		//adding missing from name
		if ( empty( $from_name ) ) {
			$from_name = get_option( 'ig_es_from_name', true );
		}

		//adding missing from email
		if ( empty( $from_email ) ) {
			$from_email = get_option( 'ig_es_from_email', true );
		}

		$sender_email = ! empty( $from_email ) ? $from_email : $admin_email;
		$sender_name  = ! empty( $from_name ) ? $from_name : $site_title;

		$headers = array(
			"From: \"$sender_name\" <$sender_email>",
			'Return-Path: <' . $sender_email . '>',
			'Reply-To: "' . $sender_name . '" <' . $sender_email . '>'
		);

		if ( in_array( $get_email_type, array( 'php_html_mail', 'php_plaintext_mail' ) ) ) {
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'X-Mailer: PHP' . phpversion();
		}

		if ( in_array( $get_email_type, array( 'wp_html_mail', 'php_html_mail' ) ) ) {
			$headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
		} else {
			$headers[] = 'Content-Type: text/plain; charset="' . get_bloginfo( 'charset' ) . '"';
		}
		$headers = implode( "\n", $headers );

		return $headers;
	}

	/**
	 * Method to send cron data to our server if not already sent.
	 *
	 * @since 4.6.1
	 */
	public function send_cron_data( $options = array() ) {

		if ( ! ES()->is_es_admin_screen()) {
			return;
		}
		
		$handle_cron_data_service = new ES_Service_Handle_Cron_Data();
		
		// Send cron data to server
		$handle_cron_data_service->handle_es_cron_data( $options );
	}
	
	/**
	 * Method to add ES service data(Inline CSS, UTM tracking to links etc) to email content
	 *
	 * @param array $data
	 * 
	 * @return array $data
	 * 
	 * @since 4.6.2
	 */
	public function add_premium_services_data( $data = array() ) {

		$process_email_content_service = new ES_Service_Process_Email_Content();
		
		$data = $process_email_content_service->process_email_content( $data );
		
		return $data;
	}
	
	/**
	 * Method to add ES service data(Inline CSS, UTM tracking to links etc) to email content
	 *
	 * @param array $data
	 * 
	 * @return array $data
	 * 
	 * @since 4.6.2
	 */
	public function add_util_data( $data = array() ) {

		// Add CSS inliner task data to request if valid request.
		if ( ES()->validate_service_request( array( 'css_inliner' ) ) ) {

			$meta            = ! empty( $data['campaign_id'] ) ? ES()->campaigns_db->get_campaign_meta_by_id( $data['campaign_id'] ) : '';
			$data['html']    = $data['content'];
			$data['css']     = ! empty( $meta['es_custom_css'] ) ? $meta['es_custom_css'] : get_post_meta( $data['tmpl_id'], 'es_custom_css', true );
			$data['tasks'][] = 'css-inliner';
		}
		
		// Add utm tracking task data to request if valid request.
		if ( ES()->validate_service_request( array( 'utm_tracking' ) ) ) {

			if ( ! empty( $data['campaign_id'] ) ) {
				$campaign_id = $data['campaign_id'];
				$can_track_utm = ES()->mailer->can_track_utm( $data );
				if ( $can_track_utm ) {
					$meta         = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
					$data['html'] = $data['content'];
					$data['utm_params']['utm_source']   = 'es';
					$data['utm_params']['utm_medium']   = 'email';
					$data['tasks'][]                    = 'utm-tracking';
					// For broadcast campaign, utm campaign name is saved in campaign meta for other campaigns, it is saved in related template.
					if ( ! empty( $meta['es_utm_campaign'] ) ) {
						$data['utm_params']['utm_campaign'] = $meta['es_utm_campaign'];
					} else if ( ! empty( $data['tmpl_id'] ) ) {
						$data['utm_params']['utm_campaign'] = get_post_meta( $data['tmpl_id'], 'es_utm_campaign', true );
					}
				}
			}

		}

		return $data;
	}
	
	/**
	 * Method to check if utm tracking is enabled.
	 *
	 * @param array $data
	 * 
	 * @return array $data
	 * 
	 * @since 4.6.2
	 */
	public function is_utm_tracking_enabled( $tracking_enabled = false, $data = array() ) {
		$ig_es_track_utm = get_option( 'ig_es_track_utm', 'no' );

		if ( ! empty( $data ) ) {
			$campaign_id = ! empty( $data['campaign_id'] ) ? $data['campaign_id'] : 0;
			if ( ! empty( $campaign_id ) ) {
				/**
				 * For newsletter campaign, utm tracking can be enabled/disabled at campaign level therefore check if it is enabled at campaign level or not
				 * For other type of campaigns, global utm tracking option is used.
				*/
				$campaign = ES()->campaigns_db->get( $campaign_id );
				if ( ! empty( $campaign ) ) {
					$campaign_type = $campaign['type'];
					if ( 'newsletter' === $campaign_type ) {
						$campaign_meta        = maybe_unserialize( $campaign['meta'] );
						$ig_es_track_utm = ! empty( $campaign_meta['enable_utm_tracking'] ) ? $campaign_meta['enable_utm_tracking']: $ig_es_track_utm;
					}
				}
			}
		}

		if ( 'yes' === $ig_es_track_utm ) {
			$tracking_enabled = true;
		}

		return $tracking_enabled;
	}
	
	/**
	 * Method to disable Icegram server cron.
	 *
	 * @since 4.6.1
	 */
	public function disable_server_cron() {

		$handle_cron_data_service = new ES_Service_Handle_Cron_Data();
		$handle_cron_data_service->delete_cron_data();
	}
	
	/**
	 * Method to override service validation for some specific request
	 *
	 * @param bool $is_request_valid Is request valid.
	 * @param array $request_data Request data.
	 * 
	 * @return bool $is_request_valid Is request valid.
	 * 
	 * @since 4.6.2
	 */
	public function maybe_override_service_validation( $is_request_valid, $request_data = array() ) {

		if ( empty( $request_data ) ) {
			return $is_request_valid;
		}
		
		$request_body = ! empty( $request_data['body'] ) ? $request_data['body'] : array();
		
		// Check if there are any request related tasks present.
		if ( empty( $request_body ) || empty( $request_body['tasks'] ) ) {
			return $is_request_valid;
		}
		
		$request_tasks = $request_body['tasks'];
		
		// Check if request request is for storing es cron data.
		if ( in_array( 'store-cron', $request_tasks, true ) ) {
			// If request is for disable es cron.
			$is_disable_cron_request = empty( $request_body['es_enable_background'] ) ? true : false;
			if ( $is_disable_cron_request ) {
				$is_request_valid = true;
			}
		}

		return $is_request_valid;
	}
}
