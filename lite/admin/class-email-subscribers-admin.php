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
	 */
	public function __construct( $email_subscribers, $version ) {

		$this->email_subscribers = $email_subscribers;
		$this->version           = $version;

		// Reorder ES Submenu

		// Commenting out since we are now registering the submenus in the order required. Therefore no need to change the submenu order later on.
		// add_filter( 'custom_menu_order', array( $this, 'submenu_order' ) );

		add_action( 'admin_menu', array( $this, 'email_subscribers_admin_menu' ) );
		add_action( 'wp_ajax_es_klawoo_subscribe', array( $this, 'klawoo_subscribe' ) );
		add_action( 'admin_footer', array( $this, 'remove_submenu' ) );

		add_action( 'admin_init', array( $this, 'ob_start' ) );

		add_action( 'init', array( $this, 'save_screen_option' ) );

		add_action( 'wp_ajax_es_send_auth_test_email', array( &$this, 'send_authentication_header_test_email' ) );
		add_action( 'wp_ajax_es_get_auth_headers', array( &$this, 'get_email_authentication_headers') );
		// Add send cron data action.
		add_action( 'admin_head', array( $this, 'send_cron_data' ) );
		add_action( 'ig_es_after_settings_save', array( $this, 'send_cron_data' ) );

		// Process and add premium service data(Inline CSS, UTM Tracking etc) to template body.
		add_filter( 'es_after_process_template_body', array( $this, 'add_premium_services_data' ) );

		// Filter to add premium service request data.
		add_filter( 'ig_es_util_data', array( $this, 'add_util_data' ) );

		// Filter to check if utm tracking is enabled.
		add_filter( 'ig_es_track_utm', array( $this, 'is_utm_tracking_enabled' ), 10, 2 );

		// Disable Icegram server cron when plugin is deactivated.
		add_action( 'ig_es_plugin_deactivate', array( $this, 'disable_server_cron' ) );

		// add_action( 'admin_init', array( $this, 'ig_es_send_additional_data_for_tracking' ) );

		// Filter to hook custom validation for specific service request.
		add_filter( 'ig_es_service_request_custom_validation', array( $this, 'maybe_override_service_validation' ), 10, 2 );

		// Ajax handler for email preview
		add_action( 'wp_ajax_ig_es_preview_email_report', array( $this, 'preview_email_in_report' ) );
		add_action( 'wp_ajax_ajax_fetch_report_list', array( $this, 'ajax_fetch_report_list_callback' ) );

		if ( class_exists( 'IG_ES_Premium_Services_UI' ) ) {
			IG_ES_Premium_Services_UI::instance();
		}

		add_action( 'wp_dashboard_setup', array( $this, 'es_add_widgets' ) );

		add_action( 'ig_es_campaign_deleted', array( $this, 'delete_child_campaigns' ) );

		add_action( 'ig_es_campaign_failed', array( $this, 'save_campaign_error_details' ) );
		add_action( 'ig_es_campaign_sent', array( $this, 'remove_campaign_failed_flag' ) );
		add_action( 'admin_notices', array( $this, 'show_email_sending_failed_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_post_duplicator_promotion_notice' ) );
		add_action( 'wp_ajax_ig_es_dismiss_post_duplicator_promotion_notice', array( $this, 'dismiss_post_duplicator_promotion_notice' ) );

		add_action( 'admin_init', array( $this, 'maybe_apply_bulk_actions_on_all_contacts' ) );

		add_action( 'wp_ajax_ig_es_get_subscribers_stats', array( 'ES_Dashboard', 'get_subscribers_stats' ) );
		add_action( 'wp_ajax_ig_es_add_list', array( $this, 'add_list_callback' ) );
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

		// Select2 CSS
		if ( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style( 'select2', ES_PLUGIN_URL . 'lite/admin/css/select2.min.css', array(), '4.0.13' );
		}

		if ( ! wp_style_is( 'select2' ) ) {
			wp_enqueue_style( 'select2' );
		}

		$current_page          = ig_es_get_request_data( 'page' );
		$current_action 	   = ig_es_get_request_data( 'action' );
		
		//This below condition will helping to apply main.css file on forms add & update DND editor page.
		$es_forms = ( $current_page == 'es_forms' && ( isset($current_action) && ( $current_action == 'new' || $current_action == 'edit' ) ) ) ? '' : 'es_forms';

		//This below condition will helping to apply main.css file on dashboard onboarding process.
		$es_dashboard = ( IG_ES_Onboarding::is_onboarding_completed() ) ? 'es_dashboard' : '';
		
		$enqueue_tailwind 	   = in_array( $current_page, array( 'es_gallery', 'es_campaigns', 'es_subscribers', 'es_lists', $es_forms, 'es_custom_fields', 'es_settings', $es_dashboard, 'es_reports' ), true );
		
		if ( ! $enqueue_tailwind ) {
			wp_enqueue_style( 'ig-es-style', plugin_dir_url( __FILE__ ) . 'dist/main.css', array(), $this->version, 'all' );
		}
		
		if ( $enqueue_tailwind ) {
			wp_enqueue_style( 'ig-es-tw-style', plugin_dir_url( __FILE__ ) . 'dist/tailwind.css', array(), $this->version, 'all' );
		}

		$enqueue_flag_icon_css = in_array( $current_page, array( 'es_dashboard', 'es_subscribers', 'es_reports' ), true );
		if ( $enqueue_flag_icon_css ) {
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

		$page = ig_es_get_request_data( 'page' );

		wp_enqueue_script( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'js/email-subscribers-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' ), $this->version, false );

		$ig_es_js_data = array(
			'security'   => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
			'i18n_data'  => array(
				// Broadcast messages.
				'ajax_error_message'              => __( 'An error has occured. Please try again later.', 'email-subscribers' ),
				'broadcast_saved_message'         => __( 'Broadcast saved successfully.', 'email-subscribers' ),
				'broadcast_error_message'         => __( 'An error has occured while saving the broadcast. Please try again later.', 'email-subscribers' ),
				'broadcast_subject_empty_message' => __( 'Please add a broadcast subject before saving.', 'email-subscribers' ),
				'campaign_saved_message'          => __( 'Campaign saved successfully.', 'email-subscribers' ),
				'campaign_activated_message'      => __( 'Campaign activated successfully.', 'email-subscribers' ),
				'campaign_scheduled_message'      => __( 'Campaign scheduled successfully.', 'email-subscribers' ),
				'campaign_error_message'          => __( 'An error has occured while saving the campaign. Please try again later.', 'email-subscribers' ),
				'campaign_preivew_error_message'  => __( 'An error has occured while previewing the campaign. Please try again later.', 'email-subscribers' ),
				'campaign_subject_empty_message'  => __( 'Please add a campaign subject before saving.', 'email-subscribers' ),
				'empty_template_message'          => __( 'Please add email body.', 'email-subscribers' ),
				'remove_conditions_message'       => __( 'Do you really like to remove all conditions?', 'email-subscribers' ),
				'add_conditions_message'          => __( 'Please add some recipients before proceeding.', 'email-subscribers' ),

				// Workflows messages.
				'no_trigger_message'              => __( 'Please select a trigger before saving the workflow.', 'email-subscribers' ),
				'no_actions_message'              => __( 'Please add some actions before saving the workflow.', 'email-subscribers' ),
				'no_action_selected_message'      => __( 'Please select an action that this workflow should perform before saving the workflow.', 'email-subscribers' ),
				'trigger_change_message'          => __( 'Changing the trigger will remove existing actions. Do you want to proceed anyway?.', 'email-subscribers' ),
				'placeholder_copied_message'      => __( 'Copied!', 'email-subscribers' ),
				'keyword_field_is_required'       => __( '{{field_name}} field is required!', 'email-subscribers' ),
				'required_field_is_empty'         => __( 'Required field is empty!', 'email-subscribers' ),
				'delete_confirmation_message'     => __( 'Are you sure?', 'email-subscribers' ),

				// Import subscribers messages.
				'select_status'                   => esc_html__( 'Please select the status for the importing contacts!', 'email-subscribers' ),
				'select_list'                     => esc_html__( 'Please select a list for importing contacts!', 'email-subscribers' ),
				'select_email_column'             => esc_html__( 'Please select the email address column!', 'email-subscribers' ),
				'prepare_data'                    => esc_html__( 'Preparing Data', 'email-subscribers' ),
				/* translators: %s: Upload progress */
				'uploading'                       => esc_html__( 'Uploading...%s', 'email-subscribers' ),
				/* translators: %s: Import progress */
				'import_contacts'                 => esc_html__( 'Importing contacts...%s', 'email-subscribers' ),
				/* translators: %s: Import failed svg icon  */
				'import_failed'                   => esc_html__( 'Import failed! %s', 'email-subscribers' ),
				'no_windowclose'                  => esc_html__( 'Please do not close this window until it completes...', 'email-subscribers' ),
				'prepare_import'                  => esc_html__( 'Preparing Import...', 'email-subscribers' ),
				/* translators: 1. Imported contacts count 2. Total contacts count 3. Failed to import count 4. Memory usage */
				'current_stats'                   => esc_html__( 'Currently %1$s of %2$s imported/updated with %3$s errors. %4$s %5$s memory usage', 'email-subscribers' ),
				/* translators: 1 Duplicate found email message */
				'duplicate_emails_found_message'  => __( '%1$s duplicate emails found.', 'email-subscribers' ),
				/* translators: %s: Time left in minutes */
				'estimate_time'                   => esc_html__( 'Estimate time left: %s minutes', 'email-subscribers' ),
				/* translators: %s: Next attempt delaly time */
				'continues_in'                    => esc_html__( 'Continues in %s seconds', 'email-subscribers' ),
				'error_importing'                 => esc_html__( 'There was a problem during importing contacts. Please check the error logs for more information!', 'email-subscribers' ),
				'confirm_import'                  => esc_html__( 'Do you really like to import these contacts?', 'email-subscribers' ),
				/* translators: %s: Process complete svg icon  */
				'import_complete'                 => esc_html__( 'Import complete! %s', 'email-subscribers' ),
				'onbeforeunloadimport'            => esc_html__( 'You are currently importing subscribers! If you leave the page all pending subscribers don\'t get imported!', 'email-subscribers' ),
				'api_verification_success'        => esc_html__( 'API is valid. Fetching lists...', 'email-subscribers' ),
				'mailchimp_notice_nowindow_close' => esc_html__( 'Fetching contacts from MailChimp...Please do not close this window', 'email-subscribers' ),

				// verify Email authentication header messages
				'error_send_test_email'           => esc_html__('SMTP Error : Unable to send test email', 'email-subscribers'),
				'error_server_busy'				  => esc_html__('Server Busy : Please try again later', 'email-subscribers'),
				'success_verify_email_headers'    => esc_html__('Headers verified successfully', 'email-subscribers'),

				'confirm_select_all'			  => esc_html__('Want to select contacts on all pages?', 'email-subscribers'),

				'ess_fallback_text'               => esc_html__('Automatically fallback to selected Sender after crossing Icegram Email Sending Service daily limits.', 'email-subscribers'),

				'add_attachment_text'             => __( 'Add Attachment', 'email-subscribers' ),
				'sending_error_text'              => __( 'Sending error', 'email-subscribers' ),
			),
			'is_pro'     => ES()->is_pro() ? true : false,
			'is_premium' => ES()->is_premium(),
		);

		if ( 'es_settings' === $page ) {
			$ig_es_js_data['popular_domains']                           = ES_Common::get_popular_domains();
			$ig_es_js_data['i18n_data']['delete_rest_api_confirmation'] = __( 'Are you sure you want to delete this key? This action cannot be undone.', 'email-subscribers' );
			$ig_es_js_data['i18n_data']['select_user']                  = __( 'Please select a user.', 'email-subscribers' );
		}

		if ( 'es_forms' === $page && ES_Drag_And_Drop_Editor::is_dnd_editor_page() ) {
			$ig_es_js_data['frontend_css'] = ES_Form_Admin::get_frontend_css();
			$ig_es_js_data['form_styles']  = ES_Form_Admin::get_form_styles();
			$ig_es_js_data['common_css']   = ES_Form_Admin::get_common_css();
		}

		if ( 'es_newsletters' === $page || 'es_notifications' === $page ) {
			$ig_es_js_data['campaign_statuses'] = array(
				'inactive'  => IG_ES_CAMPAIGN_STATUS_IN_ACTIVE,
				'active'    => IG_ES_CAMPAIGN_STATUS_ACTIVE,
				'scheduled' => IG_ES_CAMPAIGN_STATUS_SCHEDULED,
				'queued'    => IG_ES_CAMPAIGN_STATUS_QUEUED,
				'paused'    => IG_ES_CAMPAIGN_STATUS_PAUSED,
				'finished'  => IG_ES_CAMPAIGN_STATUS_FINISHED,
			);
			$ig_es_js_data['campaigns_page_url'] = admin_url( 'admin.php?page=es_campaigns' );
		}

		wp_localize_script( $this->email_subscribers, 'ig_es_js_data', $ig_es_js_data );

		if ( ! wp_script_is( 'clipboard', 'registered' ) ) {
			wp_register_script( 'clipboard', plugin_dir_url( __FILE__ ) . 'js/clipboard.js', array( 'jquery' ), '2.0.6', false );
		}

		wp_enqueue_script( 'clipboard' );

		if ( 'es_workflows' === $page ) {


			if ( ! function_exists( 'ig_es_wp_js_editor_admin_scripts' ) ) {
				/**
				 * Include WP JS Editor library's main file. This file contains required functions to enqueue required js file which being used to create WordPress editor dynamcially.
				 */
				require_once ES_PLUGIN_DIR . 'lite/includes/libraries/wp-js-editor/wp-js-editor.php';
			}

			// Load required html/js for dynamic WordPress editor.
			ig_es_wp_js_editor_admin_scripts();

			// Localize additional required data for workflow functionality
			$workflows_data = ES_Workflow_Admin_Edit::get_workflow_data();
			wp_localize_script( $this->email_subscribers, 'ig_es_workflows_data', $workflows_data );
		} elseif ( 'es_subscribers' === $page ) {

			$action = ig_es_get_request_data( 'action' );
			if ( 'import' === $action ) {
				// Library to handle CSV file upload.
				wp_enqueue_script( 'plupload-all' );
			}
		}

		if ( 'es_campaigns' === $page ) {
			wp_register_script( 'mithril', plugins_url( '/js/mithril.min.js', __FILE__ ), array(), '2.0.4', true );
			wp_enqueue_script( 'mithril' );
			
			wp_register_script( 'ig-es-main-js', plugins_url( '/dist/index.js', __FILE__ ), array( 'mithril' ), '2.0.4', true );
				// wp_register_script( 'ig-es-main-js', plugins_url( '/dist/main.js', __FILE__ ), array( 'mithril' ), '2.0.4', true );
			wp_enqueue_script( 'ig-es-main-js' );

			if ( ! function_exists( 'ig_es_wp_js_editor_admin_scripts' ) ) {
				/**
				 * Include WP JS Editor library's main file. This file contains required functions to enqueue required js file which being used to create WordPress editor dynamcially.
				 */
				require_once ES_PLUGIN_DIR . 'lite/includes/libraries/wp-js-editor/wp-js-editor.php';
			}

			add_filter( 'tiny_mce_before_init', array( 'ES_Common', 'override_tinymce_formatting_options' ), 10, 2 );
			add_filter( 'mce_external_plugins', array( 'ES_Common', 'add_mce_external_plugins' ) );

			// Load required html/js for dynamic WordPress editor.
			ig_es_wp_js_editor_admin_scripts();
		} elseif ( 'es_sequence' === $page ) {
			add_filter( 'tiny_mce_before_init', array( 'ES_Common', 'override_tinymce_formatting_options' ), 10, 2 );
			add_filter( 'mce_external_plugins', array( 'ES_Common', 'add_mce_external_plugins' ) );
		}



		// timepicker
		wp_register_script( $this->email_subscribers . '-timepicker', plugin_dir_url( __FILE__ ) . 'js/jquery.timepicker.js', array( 'jquery' ), ES_PLUGIN_VERSION, true );
		wp_enqueue_script( $this->email_subscribers . '-timepicker' );

		// Select2 JS
		if ( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_script( 'select2', ES_PLUGIN_URL . 'lite/admin/js/select2.min.js', array( 'jquery' ), '4.0.13', true );
		}

		if ( ! wp_script_is( 'select2' ) ) {
			wp_enqueue_script( 'select2' );
		}

		if ( ! empty( $page ) && 'es_dashboard' === $page || 'es_reports' === $page || 'es_subscribers' === $page ) {
			wp_enqueue_script( 'frappe-js', plugin_dir_url( __FILE__ ) . 'js/frappe-charts.min.iife.js', array( 'jquery' ), '1.5.2', false );
		}

	}

	public function remove_submenu() {
		// remove submenues
		?>
		<script type="text/javascript">
			jQuery(document).ready(function () {
				var removeSubmenu = ['ig-es-broadcast', 'ig-es-lists', 'ig-es-post-notifications', 'ig-es-sequence', 'ig-es-custom-fields','ig-es-drag-and-drop', 'ig-es-gallery-submenu'];
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
			add_action( "load-$hook", array( 'ES_Lists_Table', 'screen_options' ) );
		}

		if ( in_array( 'forms', $accessible_sub_menus ) ) {
			// Add Forms Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Forms', 'email-subscribers' ), __( 'Forms', 'email-subscribers' ), 'edit_posts', 'es_forms', array( $this, 'render_forms' ) );
			add_action( "load-$hook", array( 'ES_Forms_Table', 'screen_options' ) );
		}

		if ( in_array( 'campaigns', $accessible_sub_menus ) ) {
			// Add Campaigns Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Campaigns', 'email-subscribers' ), __( 'Campaigns', 'email-subscribers' ), 'edit_posts', 'es_campaigns', array( $this, 'render_campaigns' ) );
			add_action( "load-$hook", array( 'ES_Campaigns_Table', 'screen_options' ) );

			// Start-IG-Code.
			add_submenu_page( 'es_dashboard', __( 'Post Notifications', 'email-subscribers' ), '<span id="ig-es-post-notifications">' . __( 'Post Notifications', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_notifications', array( $this, 'load_campaign_admin_page' ) );
			// End-IG-Code.
			add_submenu_page( 'es_dashboard', __( 'Broadcast', 'email-subscribers' ), '<span id="ig-es-broadcast">' . __( 'Broadcast', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_newsletters', array( $this, 'load_campaign_admin_page' ) );

			// add_submenu_page( null, __( 'Template', 'email-subscribers' ), '<span id="ig-es-gallery-submenu">' . __( 'Templates', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_template', array( $this, 'load_template' ) );
		}

		if ( in_array( 'workflows', $accessible_sub_menus ) ) {

			// Add Workflows Submenu
			$hook = add_submenu_page( 'es_dashboard', __( 'Workflows', 'email-subscribers' ), __( 'Workflows', 'email-subscribers' ), 'edit_posts', 'es_workflows', array( $this, 'render_workflows' ) );

			// add_action( "load-$hook", array( 'ES_Workflows_Table', 'screen_options' ) );
			add_action( "load-$hook", array( 'ES_Workflow_Admin_Edit', 'register_meta_boxes' ) );
			add_action( "admin_footer-$hook", array( 'ES_Workflow_Admin_Edit', 'print_script_in_footer' ) );
			add_action( 'admin_init', array( 'ES_Workflow_Admin_Edit', 'maybe_save' ) );
		}

		if ( in_array( 'reports', $accessible_sub_menus ) ) {
			add_submenu_page( 'es_dashboard', __( 'Reports', 'email-subscribers' ), __( 'Reports', 'email-subscribers' ), 'edit_posts', 'es_reports', array( $this, 'load_reports' ) );
		}

		if ( in_array( 'logs', $accessible_sub_menus ) ) {
			add_submenu_page( 'es_dashboard', __( 'Logs', 'email-subscribers' ), __( 'Logs', 'email-subscribers' ), 'edit_posts', 'es_logs', array( $this, 'load_logs' ) );
		}

		if ( in_array( 'settings', $accessible_sub_menus ) ) {
			$hook = add_submenu_page( 'es_dashboard', __( 'Settings', 'email-subscribers' ), __( 'Settings', 'email-subscribers' ), 'manage_options', 'es_settings', array( $this, 'load_settings' ) );
			add_action( "load-$hook", array( 'ES_Admin_Settings', 'screen_options' ) );
		}

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
		'method'  => $method,
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
		$campaigns = ES_Campaigns_Table::get_instance();
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
	public function load_campaign_admin_page() {
		$campaign_admin = ES_Campaign_Admin::get_instance();
		$campaign_admin->setup();
		$campaign_admin->render();
	}

	/**
	 * Load single template
	 *
	 * @return void
	 */
	public function load_template() {
		$template_admin = ES_Template_Admin::get_instance();
		$template_admin->setup();
		$template_admin->render();
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
	 * Render Logs
	 *
	 * @since 5.6.6
	 */
	public function load_logs() {
		ES_Logs::show_es_logs();
	}

	/**
	 * Render drag and drop
	 *
	 * @since 5.0.3
	 */
	public function load_drag_and_drop() {
		$danddeditor = ES_Drag_And_Drop_Editor::get_instance();
		$danddeditor->es_draganddrop_callback();
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

			// $submenu['es_dashboard'] = $reorder_es_menu;
		}

		// Return the new submenu order
		return $menu_order;
	}

	public function es_dashboard_callback() {
		$ig_es_db_update_history  = ES_Common::get_ig_option( 'db_update_history', array() );
		$ig_es_4015_db_updated_at = ( is_array( $ig_es_db_update_history ) && isset( $ig_es_db_update_history['4.0.15'] ) ) ? $ig_es_db_update_history['4.0.15'] : false;

		$is_sa_option_exists = get_option( 'current_sa_email_subscribers_db_version', false );
		$onboarding_status   = get_option( 'ig_es_onboarding_complete', 'no' );
		if ( ! $is_sa_option_exists && ! $ig_es_4015_db_updated_at && 'yes' !== $onboarding_status ) {
			$this->show_onboarding();
		} else {
			$this->show_dashboard();
		}
	}

	/**
	 * Show onboarding page
	 * 
	 * @since 5.5.4
	 */
	public function show_onboarding() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/onboarding.php';
	}

	/**
	 * Show dashboard page
	 * 
	 * @since 5.5.4
	 */
	public function show_dashboard() {
		$es_dashboard = new ES_Dashboard();
		$es_dashboard->show();
	}

	public function count_contacts_by_list() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_audience = ES_Common::ig_es_can_access( 'audience' );
		$can_access_campaign = ES_Common::ig_es_can_access( 'campaigns' );
		if ( ! ( $can_access_audience || $can_access_campaign ) ) {
			return 0;
		}

		$list_id    = ig_es_get_request_data( 'list_id', 0 );
		$status     = ig_es_get_request_data( 'status', 'all' );
		$conditions = ig_es_get_request_data( 'conditions', array() );
		$get_count  = ig_es_get_request_data( 'get_count', 'no' );

		$list_id = absint( $list_id );
		if ( 0 == $list_id && empty( $conditions ) ) {
			return 0;
		}

		$expected_statuses = array( 'subscribed', 'unsubscribed', 'unconfirmed', 'confirmed', 'all' );

		if ( ! in_array( $status, $expected_statuses, true ) ) {
			return 0;
		}

		$response_data = array();

		if ( ! empty( $conditions ) ) {
			$conditions = IG_ES_Campaign_Rules::remove_empty_conditions( $conditions );
			if ( 'yes' === $get_count ) {
				if ( ! empty( $conditions ) ) {
					$args                   = array(
						'lists'             => $list_id,
						'conditions'        => $conditions,
						'status'            => $status,
						'subscriber_status' => array( 'verified' ),
						'return_count'      => true,
					);
					$query                  = new IG_ES_Subscribers_Query();
					$response_data['total'] = $query->run( $args );
				} else {
					$response_data['total'] = 0;
				}
			}
			ob_start();
			do_action( 'ig_es_campaign_show_conditions', $conditions );
			$response_data['conditions_html'] = ob_get_clean();
		} else {
			$response_data['total'] = ES()->lists_contacts_db->get_total_count_by_list( $list_id, $status );
		}

		if ( ! empty( $response_data['total'] ) ) {
			$response_data['total'] = number_format( $response_data['total'] );
		}

		die( json_encode( $response_data ) );
	}

	/**
	 * Get Icegram Express' screen options
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
				'show_reconnect_notification',
				'show_tracker_notice',
				'show_new_keyword_notice',
				'show_membership_integration_notice',
				'show_email_sending_failed_notice',
				'show_ess_fallback_removal_notice',
				'show_post_duplicator_promotion_notice',
				'show_ess_promotion_notice',
				'ig_es_show_feature_survey',
				'ig_es_show_trial_optin_reminder_notice',
				'show_list_cleanup_notice',
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

			/* translators: 1. WordPress URL 2. Icegram Express version 3. Icegram site URL */
			$footer_text = sprintf( __( '<span id="footer-thankyou">Thank you for creating with <a href="%1$s" target="_blank">WordPress</a> | Icegram Express <b>%2$s</b>. Developed by team <a href="%3$s" target="_blank">Icegram</a></span>', 'email-subscribers' ), esc_url( $wordpress_url ), ES_PLUGIN_VERSION, esc_url( $icegram_url ) );
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

		// adding missing from name
		if ( empty( $from_name ) ) {
			$from_name = get_option( 'ig_es_from_name', true );
		}

		// adding missing from email
		if ( empty( $from_email ) ) {
			$from_email = get_option( 'ig_es_from_email', true );
		}

		$sender_email = ! empty( $from_email ) ? $from_email : $admin_email;
		$sender_name  = ! empty( $from_name ) ? $from_name : $site_title;

		$headers = array(
		"From: \"$sender_name\" <$sender_email>",
		'Return-Path: <' . $sender_email . '>',
		'Reply-To: "' . $sender_name . '" <' . $sender_email . '>',
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

		if ( ! ES()->is_es_admin_screen() ) {
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

			$meta         = ! empty( $data['campaign_id'] ) ? ES()->campaigns_db->get_campaign_meta_by_id( $data['campaign_id'] ) : '';
			$data['html'] = $data['content'];
			$data['css']  = '';
			if ( ! empty( $meta['es_custom_css'] ) ) {
				$data['css'] = $meta['es_custom_css'];
			} elseif ( ! empty( $data['tmpl_id'] ) ) {
				$data['css'] = get_post_meta( $data['tmpl_id'], 'es_custom_css', true );
			}
			$data['tasks'][] = 'css-inliner';
		}

		// Add utm tracking task data to request if valid request.
		if ( ES()->validate_service_request( array( 'utm_tracking' ) ) ) {

			if ( ! empty( $data['campaign_id'] ) ) {
				$campaign_id   = $data['campaign_id'];
				$can_track_utm = ES()->mailer->can_track_utm( $data );
				if ( $can_track_utm ) {
					$meta                             = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );
					$data['html']                     = $data['content'];
					$data['utm_params']['utm_source'] = 'es';
					$data['utm_params']['utm_medium'] = 'email';
					$data['tasks'][]                  = 'utm-tracking';
					// For broadcast campaign, utm campaign name is saved in campaign meta for other campaigns, it is saved in related template.
					if ( ! empty( $meta['es_utm_campaign'] ) ) {
						$data['utm_params']['utm_campaign'] = $meta['es_utm_campaign'];
					} elseif ( ! empty( $data['tmpl_id'] ) ) {
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
					$campaign_type            = $campaign['type'];
					$supported_campaign_types = array(
					IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
					IG_CAMPAIGN_TYPE_POST_DIGEST,
					IG_CAMPAIGN_TYPE_NEWSLETTER
					);
					if ( in_array( $campaign_type, $supported_campaign_types, true ) ) {
						$campaign_meta = ! empty( $campaign['meta'] ) ? maybe_unserialize( $campaign['meta'] ) : array();
						$ig_es_track_utm = ! empty( $campaign_meta['enable_utm_tracking'] ) ? $campaign_meta['enable_utm_tracking'] : $ig_es_track_utm;
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
	 * @param bool  $is_request_valid Is request valid.
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

	/**
	 * Send additional data to Icegram Server for tracking purpose
	 *
	 * @param
	 *
	 * @since 4.6.6
	 */
	/*
	public function ig_es_send_additional_data_for_tracking() {

		// Send data only if user had opted for trial or user is on a premium plan.
		$is_plan_valid      = ES()->trial->is_trial() || ES()->is_premium();

		// Check if the data is already sent once
		$can_send_data      = get_option( 'ig_es_send_additional_data_for_tracking', 'yes' );

		if ( $is_plan_valid && 'yes' === $can_send_data ) {

			update_option( 'ig_es_send_additional_data_for_tracking', 'no' );

			$url    = 'https://api.icegram.com/';
			$data   = array(

			);

			$options         = array(
				'timeout' => 50,
				'method'  => 'POST',
				'body'    => $data
			);

			$response = wp_remote_post( $url, $options );
		}

	}*/

	/**
	 * Method to preview email through AJAX
	 *
	 * @since 4.6.11
	 */
	public function preview_email_in_report() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_reports = ES_Common::ig_es_can_access( 'reports' );
		if ( ! $can_access_reports ) {
			return 0;
		}

		$report_id     = ig_es_get_request_data( 'campaign_id' );
		$campaign_type = ig_es_get_request_data( 'campaign_type' );
		$response      = array();
		$email_body    = '';

		if ( ! empty( $report_id ) ) {

			if ( ! empty( $campaign_type ) && in_array( $campaign_type, array( 'sequence_message', 'workflow_email' ), true ) ) {
				$email_body = ES()->campaigns_db->get_campaign_by_id( $report_id );
			} else {
				$email_body = ES_DB_Mailing_Queue::get_mailing_queue_by_id( $report_id );
			}
			$es_email_type             = get_option( 'ig_es_email_type' );    // Not the ideal way. Email type can differ while previewing sent email.
			$response['template_html'] = ES_Common::es_process_template_body( $email_body['body'] );
			/*
			if ( 'WP HTML MAIL' == $es_email_type || 'PHP HTML MAIL' == $es_email_type ) {
			$response['template_html'] = ES_Common::es_process_template_body( $email_body['body'] );
			} else {
			$response['template_html'] = str_replace( '<br />', "\r\n", $email_body['body'] );
			$response['template_html'] = str_replace( '<br>', "\r\n", $email_body['body'] );
			}
			*/
		}

		if ( ! empty( $response ) ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error();
		}
		?>

		<?php
	}

	public function maybe_apply_bulk_actions_on_all_contacts() {

		$can_access_audience  = ES_Common::ig_es_can_access( 'audience' );
		if ( ! ( $can_access_audience ) ) {
			return 0;
		}

		$page = ig_es_get_request_data( 'page' );
		if ( 'es_subscribers' !== $page ) {
			return;
		}

		$is_ajax = ig_es_get_request_data( 'is_ajax' );
		if ( ! $is_ajax ) {
			return;
		}

		$completed = false;
		$errortype = false;
		
		$contacts_table = new ES_Contacts_Table();
		
		$current_action = $contacts_table->current_action();
		if ( empty( $current_action ) ) {
			return;
		}
		
		check_admin_referer( 'bulk-' . $contacts_table->_args['plural'] );

		$current_page = $contacts_table->get_pagenum();
		$per_page     = $contacts_table->get_items_per_page( $contacts_table::$option_per_page, 200 );
		$total_pages  = ig_es_get_request_data( 'total_pages', 0 );

		if ( empty( $total_pages ) ) {
			$total_contacts = $contacts_table->get_subscribers( $per_page, $current_page, true );
			$total_pages    = ceil( $total_contacts / $per_page );
		}


		$start_page = ig_es_get_request_data( 'start_page', 0 );
		
		if ( empty( $start_page ) ) {
			$start_page = $current_page;
		}

		// For pages greater then the start page, get subscriber ids from db.
		$use_db_ids = (int) $current_page > (int) $start_page;
		if ( $use_db_ids ) {
			
			if ( 'bulk_delete' === $current_action ) {
				$page_to_process = $start_page;// When deleting contacts keep page to process same as start page since using current page results in incorrect calculation.
			} else {
				$page_to_process = $current_page;
			}
			
			$contacts = $contacts_table->get_subscribers( $per_page, $page_to_process );
			
			if ( ! empty( $contacts ) ) {
				$subscribers = array_column( $contacts, 'id' );
				if ( ! empty( $subscribers ) ) {
					$exclude_subscribers = ig_es_get_request_data( 'exclude_subscribers', array() );
					if ( ! empty( $exclude_subscribers ) ) {
						$exclude_subscribers = explode( ',', $exclude_subscribers );
						$subscribers         = array_diff( $subscribers, $exclude_subscribers );
					}
					$_REQUEST['subscribers'] = $subscribers;
				}
			}
		}


		$return_response = true;
		$action_response = $contacts_table->process_bulk_action( $return_response );
		$completed       = (int) $current_page === (int) $total_pages;
		$response        = array(
			'paged'       => $current_page + 1,
			'start_page'  => $start_page,
			'total_pages' => $total_pages,
			'completed'   => $completed,
			'errortype'	  => $action_response['errortype'] ? $action_response['errortype'] : $errortype ,
			'message'     => $action_response['message'],	
			'bulk_action' => $current_action, 			
		);

		if ( 'success' === $action_response['status'] ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}




	/**
	 * Method to display Activity table in Reports through Ajax
	 *
	 * @since 4.6.12
	 */
	public function ajax_fetch_report_list_callback() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_reports = ES_Common::ig_es_can_access( 'reports' );
		if ( ! $can_access_reports ) {
			return 0;
		}

		$wp_list_table = new ES_Campaign_Report();
		$wp_list_table->ajax_response();
	}

	/**
	 * Init Widget on WP Dashboard
	 *
	 * @since 4.7.8
	 */
	public function es_add_widgets() {

		if ( ! ES()->is_current_user_administrator() ) {
			return;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_add_dashboard_widget( 'es_dashboard_stats_widget', __( 'Icegram Express', 'email-subscribers' ), array( $this, 'dashboard_stats_widget' ) );

		if ( in_array( $screen_id, array( 'dashboard' ) ) ) {
			wp_enqueue_style( 'ig_es_dashboard_style', plugin_dir_url( __FILE__ ) . 'css/es-wp-dashboard.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Widget content to show on WP Dashboard
	 *
	 * @since 4.7.8
	 */
	public function dashboard_stats_widget() {

		$args = array(
			'days' => 30
		);
		
		$page               = 'wp_dashboard';
		$override_cache     = false;
		$reports_data       = ES_Reports_Data::get_dashboard_reports_data( $page, $override_cache, $args );
		$total_subscribed   = isset( $reports_data['total_subscribed'] ) ? $reports_data['total_subscribed'] : 0;
		$total_message_sent = isset( $reports_data['total_message_sent'] ) ? $reports_data['total_message_sent'] : 0;
		$total_unsubscribed = isset( $reports_data['total_unsubscribed'] ) ? $reports_data['total_unsubscribed'] : 0;
		$avg_open_rate      = isset( $reports_data['avg_open_rate'] ) ? $reports_data['avg_open_rate'] : 0;

		$campaign_report = isset( $reports_data['campaigns'][0] ) ? $reports_data['campaigns'][0] : '';
		$reports_url     = isset( $campaign_report['hash'] ) ? add_query_arg( 'list', $campaign_report['hash'], add_query_arg( 'action', 'view', admin_url( 'admin.php?page=es_reports' ) ) ) : '';

		$topics = ES_Common::get_useful_articles( false );

		$topics_indexes = array_rand( $topics, 3 );
		?>
		<style type="text/css">
			#es_dashboard_stats_widget .inside {
				padding: 0;
				margin: 0;
			}
			.ig-es p{
				margin: 1em 0;
			}
		</style>
		<div class="ig-es">
			<div class="pb-2 border-b border-gray-200">
				<div class="px-4">
					<p class="text-base font-medium leading-6 text-gray-600">
						<span class="rounded-md bg-gray-200 px-2 py-0.5">
						<?php echo esc_html__( 'Last 30 days', 'email-subscribers' ); ?>
						</span>
					</p>
					<div class="flex">
						<div class="w-1/4 px-4 border-r border-gray-100">
							<span class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_subscribed ); ?>
							</span>
							<p class="font-medium text-gray-500">
							<?php echo esc_html__( 'Subscribed', 'email-subscribers' ); ?>
							</p>
						</div>
						<div class="w-1/4 px-4 border-r border-gray-100">
							<span class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_unsubscribed ); ?>
							</span>
							<p class="font-medium text-gray-500">
							<?php echo esc_html__( 'Unsubscribed', 'email-subscribers' ); ?>
							</p>
						</div>
						<div class="w-1/4 px-4 border-r border-gray-100">
							<span class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $avg_open_rate ); ?> %
							</span>
							<p class="font-medium text-gray-500">
							<?php echo esc_html__( 'Avg Open Rate', 'email-subscribers' ); ?>
							</p>
						</div>
						<div class="w-1/4 px-4">
							<span class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_message_sent ); ?>
							</span>
							<p class="font-medium text-gray-500">
							<?php echo esc_html__( 'Messages Sent', 'email-subscribers' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>

			<div class="overflow-hidden">
				<p class="px-4 text-base font-medium leading-6 text-gray-600">
					<span class="rounded-md bg-gray-200 px-2 py-0.5">
					<?php
					echo esc_html__( 'Last Campaign', 'email-subscribers' );
					?>
					</span>
				</p>
					<?php
					if ( ! empty( $campaign_report ) ) {
						?>
				<a href="<?php echo esc_url( $reports_url ); ?>" class="block px-2 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition duration-150 ease-in-out" target="_blank">
					<div class="flex px-4 pb-2">
						<div class="w-3/5 min-w-0 pt-2 flex-1">
							<div class="flex flex-1 items-center">
								<div class="leading-5 w-2/4 flex items-start text-gray-500 font-medium text-base">
									<svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
									  <path fill-rule="evenodd" d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884zM18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" clip-rule="evenodd"/>
									</svg>
									<?php
									echo esc_html( $campaign_report['type'] );

									$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
									$status            = ES_Common::get_campaign_status_icon( $campaign_report['status'] );
									echo wp_kses( $status, $allowed_html_tags );
									?>

								</div>
							</div>
							<div class="text-sm mt-2 pr-4">
									<?php echo esc_html( $campaign_report['title'] ); ?>
							</div>
						</div>
						<div class="sm:grid sm:grid-cols-2 flex-1">

							<div class="px-3 pb-3 pt-4">
								<span class="leading-none font-medium text-base text-indigo-500">
									<?php echo esc_html( $campaign_report['total_sent'] ); ?>
								</span>
								<p class="mt-1 leading-6 text-gray-400">
									<?php echo esc_html__( 'Sent to', 'email-subscribers' ); ?>
								</p>
							</div>
							<div class="px-3 pb-3 pt-4">
								<span class="leading-none font-medium text-base text-indigo-500">
									<?php echo esc_html( $campaign_report['total_opens'] ); ?> (
										<?php
										echo esc_html( $campaign_report['campaign_opens_rate'] );
										?>
										%)
									</span>
									<p class="mt-1 leading-6 text-gray-400">
										<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>
									</p>
								</div>

							</div>
							<div>
								<svg class="h-5 w-5 text-gray-400 mt-5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
								</svg>
							</div>
					</div>
				</a>
						<?php
					} else {
						echo '<p class="pl-4 font-medium text-gray-500">' . esc_html__( 'No campaigns sent yet', 'email-subscribers' ) . '<p>';
					}
					?>
			</div>
			<div class="border-t border-gray-200">
				<p class="px-4 text-base font-medium leading-6 text-gray-600">
					<span class="rounded-md bg-gray-200 px-2 py-0.5">
					<?php
					echo esc_html__( 'Latest Blog Posts from Icegram', 'email-subscribers' );
					?>
					</span>
				</p>
				<div class="overflow-hidden pb-2">
					<ul class="pl-8 pr-3">
					<?php foreach ( $topics_indexes as $index ) { ?>
							<li class="mb-0 hover:underline text-gray-500" style="list-style-type: square !important">
								<a href="<?php echo esc_url( $topics[ $index ]['link'] ); ?>" class="hover:underline font-medium block pr-3 transition duration-150 ease-in-out focus:outline-none focus:bg-gray-50" target="_blank">
									<div class="flex items-center px-2 py-1 md:justify-between">
										<div class="text-sm leading-5 text-gray-700">
											<?php
											echo wp_kses_post( $topics[ $index ]['title'] );
											if ( ! empty( $topics[ $index ]['label'] ) ) {
												?>
												<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo esc_attr( $topics[ $index ]['label_class'] ); ?>"><?php echo esc_html( $topics[ $index ]['label'] ); ?></span>
											<?php } ?>
										</div>
									</div>
								</a>
							</li>
						<?php } ?>
					</ul>
				</div>
			</div>
			<?php
		$release_notes_from_icegram = get_transient( 'ig_es_release_notes_from_icegram' );

			if ( ! $release_notes_from_icegram ) {
				$api_url = 'https://www.icegram.com/gallery/wp-json/wp/v2/release_notes';
				$api_response = wp_remote_get( $api_url );

				if ( ! is_wp_error( $api_response ) && is_array( $api_response ) ) {
					$api_data = json_decode( wp_remote_retrieve_body( $api_response ), true );

					if ( ! empty( $api_data[0]['content']['rendered'] ) ) {
					
						$release_notes_from_icegram = $api_data[0]['content']['rendered'];
						set_transient( 'ig_es_release_notes_from_icegram', $release_notes_from_icegram, 7 * DAY_IN_SECONDS );
					
					}
				} 
			}

			if ( $release_notes_from_icegram ) {
				$allowedtags = ig_es_allowed_html_tags_in_esc();
				?>
			<div class="border-t border-gray-200">
				<p class="px-4 text-base font-medium leading-6 text-gray-600">
					<span class="rounded-md bg-gray-200 px-2 py-0.5">
						<?php echo esc_html__( 'Latest Updates from Icegram', 'email-subscribers' ); ?>
					</span>
				</p>
				<div class="overflow-hidden pb-2">
					<?php echo wp_kses( $release_notes_from_icegram, $allowedtags ); ?>
				</div>
			</div>
			<?php
			}
			?>

		</div>
			<?php
	}

	/**
	 * Delete all child campaigns based on $parent_campaign_id
	 *
	 * @param int $parent_campaign_id
	 *
	 * @since 4.3.4
	 */
	public function delete_child_campaigns( $parent_campaign_id = 0 ) {

		if ( 0 !== $parent_campaign_id ) {

			$child_campaign_ids = ES()->campaigns_db->get_campaigns_by_parent_id( $parent_campaign_id );

			// Delete All Child Campaigns
			ES()->campaigns_db->delete_campaigns( $child_campaign_ids );
		}
	}

	public function save_campaign_error_details( $error_details ) {
		update_option( 'ig_es_campaign_error', $error_details, false );
	}

	public function remove_campaign_failed_flag() {
		delete_option( 'ig_es_campaign_error' );
	}

	public function show_email_sending_failed_notice() {

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$current_page = ig_es_get_request_data( 'page' );

		if ( 'es_dashboard' === $current_page ) {
			return;
		}

		$campaign_error = get_option( 'ig_es_campaign_error', 0 );
		if ( $campaign_error ) {
			$logs_url             = admin_url( 'admin.php?page=es_logs' );
			$notification_guid    = $campaign_error['notification_guid'];
			$notification         = ES_DB_Mailing_Queue::get_notification_by_hash( $notification_guid );
			$notification_subject = $notification['subject'];
			$error_message        = is_array( $campaign_error['error_message'] ) ? implode( '', $campaign_error['error_message'] ) : $campaign_error['error_message'];
			?>
			<div class="notice notice-error is-dismissible">
				<p>
				<?php
				/* translators: 1: Notificatin subject 2. Error message */
				echo sprintf( esc_html__( 'Campaign %1$s has an error while sending emails: %2$s', 'email-subscribers' ), 
						'<strong>' . esc_html( $notification_subject ) . '</strong>', 
						'<strong>' . esc_html( $error_message ) . '</strong>'
				);
				?>
				</p>
				<p>
				<?php
				/* translators: 1: Anchor tag 2. Closing anchor tag */
				echo sprintf( esc_html__( 'Automatic sending has been paused for this campaign. For more details, view sending logs from %1$shere%2$s.', 'email-subscribers' ),
						'<a href="' . esc_url( $logs_url ) . '" target="_blank">',
						'</a>'
				);
				?>
				</p>
				<?php
				$can_promote_ess = ES_Service_Email_Sending::can_promote_ess();
				if ( $can_promote_ess ) {
					$promotion_message_html = ES_Service_Email_Sending::get_ess_promotion_message_html();
					$allowed_tags           = ig_es_allowed_html_tags_in_esc();
					echo wp_kses( $promotion_message_html, $allowed_tags );
				}
				?>
				</p>
			</div>
			<?php
			delete_option( 'ig_es_campaign_error' );
		}
	}

	public function show_post_duplicator_promotion_notice() {

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
		if ( ! $can_access_settings ) {
			return 0;
		}

		$current_page = ig_es_get_request_data( 'page' );

		if ( 'es_dashboard' === $current_page ) {
			return;
		}

		$fallback_notice_dismissed = 'yes' === get_option( 'ig_es_post_duplicator_promotion_notice_dismissed', 'no' );
		if ( ! $fallback_notice_dismissed ) {
			$optin_url = 'https://wordpress.org/plugins/duplicate-post-page-copy-clone-wp/';
			?>
			<div id="ig_es_post_duplicator_promotion_notice" class="notice notice-success is-dismissible">
				<div id="" class="text-gray-700 not-italic">
					<p class="mb-2">
					&#x1F389;
						<?php echo sprintf( esc_html__( 'We’re thrilled to introduce our latest plugin to ease your daily task - %1$sDuplicate Pages and Posts%2$s. Give it a try and let us know your feedback.', 'email-subscribers' ), '<a href="' . esc_url( $optin_url ) . '" target="_blank" class="text-indigo-600">', '</a>' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=icegram&tab=search&type=author' ) ); ?>" target="_blank" id="ig-es-post-duplicator-promo-button">
							<button class="primary">	<?php echo esc_html__('Try now', 'email-subscribers'); ?>
							</button>
						</a>
					</p>
				</div>
			</div>
			<script>
				jQuery(document).ready(function($) {
					$('#ig_es_post_duplicator_promotion_notice').on('click', '.notice-dismiss, #ig-es-post-duplicator-promo-button', function() {
						$.ajax({
							method: 'POST',
							url: ajaxurl,
							dataType: 'json',
							data: {
								action: 'ig_es_dismiss_post_duplicator_promotion_notice',
								security: ig_es_js_data.security
							}
						}).done(function(response){
							console.log( 'response: ', response );
						});
					});
				});

			</script>
			<?php
		}
	}

	public function dismiss_post_duplicator_promotion_notice() {
		$response = array(
			'status' => 'success',
		);

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
		if ( ! $can_access_settings ) {
			return 0;
		}

		update_option( 'ig_es_post_duplicator_promotion_notice_dismissed', 'yes', false );

		wp_send_json( $response );
	}

	/**
	 * Method to send email for authentication headers test
	 *
	 * @since 5.x
	 */

	public function send_authentication_header_test_email() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
		if ( ! ( $can_access_settings ) ) {
			return 0;
		}

		$response = array(
		'status'        => 'error',
		'error_message' => __( 'Something went wrong', 'email-subscribers' ),
		);

		$mailbox = ES_Common::get_email_verify_test_email();

		if ( ! empty( $_REQUEST['action'] ) && 'es_send_auth_test_email' == $_REQUEST['action'] ) {

			$test_email = new ES_Send_Test_Email();
			$params     = array('email' => $mailbox );
			$response   = $test_email->send_test_email($params);

			wp_send_json($response);
		}
		wp_send_json($response);

	}

	public function get_email_authentication_headers() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
		if ( ! ( $can_access_settings ) ) {
			return 0;
		}

		$response = array(
			'status'        => 'error',
			'error_message' => __( 'Something went wrong', 'email-subscribers' ),
		);

		$header_check = new ES_Service_Auth_Header_Check();
		$response     = $header_check->get_email_authentication_headers();

		if ( 'error' !== $response['status'] && ! empty( $response['data']) ) {

			$email_auth_headers = json_decode( $response['data'], true );
			update_option('ig_es_email_auth_headers', $email_auth_headers);

			wp_send_json( $response );
		}
		wp_send_json( $response );
	}

	public function add_list_callback() {
		check_ajax_referer('ig-es-admin-ajax-nonce', 'security');
	
		if ( !ES_Common::ig_es_can_access( 'audience' ) ) {
			return 0;
		}
	
		$this->db = new ES_Lists_Table();
	
		$action     = ig_es_get_request_data('action');
		$list_name  = ig_es_get_request_data('es_list_name');
		$list_desc  = ig_es_get_request_data('es_list_desc');
		
		$validate_data = array(
			'nonce'     => wp_create_nonce( 'es_list' ),
			'list_name' => sanitize_text_field($list_name),
			'list_desc' => sanitize_textarea_field($list_desc),
		);
	
		$response = $this->db->validate_data($validate_data);
		if ('error' === $response['status']) {
			wp_send_json_error($response['message']);
			return;
		}
	
		$data = array(
			'list_name' => $list_name,
			'list_desc' => $list_desc,
		);
		
		$save = $this->db->save_list(null, $data);		
		if ($save) {
			wp_send_json_success(array(
				'message' => __('List added successfully.', 'email-subscribers'),
				'list_id' => $save,
			));
		} else {
			wp_send_json_error( __('Failed to add list.', 'email-subscribers') );
		}
	}
	
	

}
