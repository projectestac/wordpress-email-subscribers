<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'IG_Plugin_Usage_Tracker_V_1_0_0' ) ) {

	/**
	 * Class IG_Plugin_Usage_Tracker_V_1_0_0
	 *
	 * Icegram tracker handler class is responsible for sending anonymous plugin
	 * data to Icegram servers for users that actively allowed data tracking.
	 *
	 * @class       IG_Plugin_Usage_Tracker_V_1_0_0
	 * @since       1.0.0
	 *
	 * @package     feedback
	 */
	class IG_Plugin_Usage_Tracker_V_1_0_0 {

		/**
		 * SDK version
		 *
		 * @var string
		 */
		public $sdk_version = '1.0.0';

		/**
		 * The API URL where we will send plugin tracking data.
		 *
		 * @var string
		 */
		public $api_url = 'https://api.icegram.com/track/'; // Production

		/**
		 * Name for this plugin.
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Unique slug for this plugin.
		 *
		 * @var string
		 */
		public $plugin;

		/**
		 * Plugin Abbreviation
		 *
		 * @var string
		 */
		public $plugin_abbr;

		/**
		 * Product ID
		 *
		 * @var int
		 */
		public $product_id;

		/**
		 * Plugin plan
		 *
		 * @var string
		 */
		public $plugin_plan;


		/**
		 * IG Tracker class name
		 *
		 * @var string
		 */
		public $tracker_class;

		/**
		 * Is tracking allowed by default
		 *
		 * @var bool
		 */
		public $allowed_by_default;

		/**
		 * Primary class constructor.
		 *
		 * @param string  $name Plugin name.
		 * @param string  $text_domain Text domain.
		 * @param string  $plugin_abbr Plugin Abbreviation.
		 * @param int     $product_id Product ID.
		 * @param string  $plugin_plan Plugin Plan.
		 * @param string  $plugin_file_path Main plugin file path.
		 * @param string  $tracker_class Tracker class name used in the plugin.
		 * @param boolean $allowed_by_default Is tracking allowed by default.
		 * @param boolean $enable_on_dev Enable tracker on dev environments.
		 */
		public function __construct( $name, $text_domain, $plugin_abbr, $product_id, $plugin_plan, $plugin_file_path, $tracker_class, $allowed_by_default, $enable_on_dev = false ) {

			$this->name               = $name;
			$this->text_domain        = $text_domain;
			$this->plugin_abbr        = $plugin_abbr;
			$this->product_id         = $product_id;
			$this->plugin_plan        = $plugin_plan;
			$this->tracker_class      = $tracker_class;
			$this->allowed_by_default = $allowed_by_default;

			// Don't run usage tracker on dev environment if not enabled.
			if ( $tracker_class::is_dev_environment() && ! $enable_on_dev ) {
				return;
			}

			register_activation_hook( $plugin_file_path, array( $this, 'do_activation_setup' ) );
			register_deactivation_hook( $plugin_file_path, array( $this, 'do_deactivation_cleanup' ) );

			$tracking_option_name = $this->get_tracking_option_name();

			/**
			 * Tracking consent add/update handler function.
			 * These action hooks are triggered by WordPress when we add/update tracking consent option in DB.
			*/
			add_action( 'add_option_' . $tracking_option_name, array( $this, 'handle_optin_add' ), 10, 2 );
			add_action( 'update_option_' . $tracking_option_name, array( $this, 'handle_optin_update' ), 10, 3 );

			add_action( 'admin_notices', array( $this, 'show_tracker_notice' ) );
			add_action( 'admin_init', array( $this, 'handle_tracker_notice_actions' ) );
			add_filter( 'cron_schedules', array( $this, 'add_weekly_schedule' ) );
			add_action( $this->plugin_abbr . '_send_tracking_data', array( $this, 'send_tracking_data' ) );

			add_filter( $this->plugin_abbr . '_allow_tracking', array( $this, 'is_tracking_allowed' ) );
			add_filter( $this->plugin_abbr . '_tracking_data', array( $this, 'get_tracking_data' ) );
		}

		/**
		 * Handles when optin option is added in db
		 *
		 * @param string $option_name
		 * @param string $value
		 */
		public function handle_optin_add( $option_name, $value ) {
			$this->handle_optin_change( $value );
		}

		/**
		 * Handles when optin option is updated in db
		 *
		 * @param string $old_value
		 * @param string $new_value
		 * @param string $option_name
		 */
		public function handle_optin_update( $old_value, $new_value, $option_name ) {
			$this->handle_optin_change( $new_value );
		}

		/**
		 * Common method to handle optin option add/update
		 *
		 * @param string $value
		 */
		public function handle_optin_change( $opted_in ) {

			if ( 'yes' === $opted_in ) {
				$this->schedule_cron();
				$this->send_tracking_data( true );
			} else {
				$this->clear_scheduled_cron();
			}
		}

		/**
		 * Perform plugin activation related tasks.
		 */
		public function do_activation_setup() {

			add_option( $this->plugin_abbr . '_installed_on', gmdate( 'Y-m-d H:i:s' ), '', false );

			$this->schedule_cron();

			$this->send_tracking_data( true );
		}

		/**
		 * Do deactivation cleanup
		 */
		public function do_deactivation_cleanup() {

			$this->clear_scheduled_cron();

			$survey_status = ig_es_get_request_data( 'survey_status', '' );
			if ( ! empty( $survey_status ) && 'skipped' === $survey_status ) {
				$extra_params = array(
					'is_deactivated' => 1,
				);
				$this->send_tracking_data( true, $extra_params );
			}
		}

		/**
		 * Add weekly cron schedule
		 *
		 * @param array $schedules
		 *
		 * @return array $schedules
		 */
		public function add_weekly_schedule( $schedules = array() ) {

			// Add weekly schedule if not exists already. From WP 5.4, it is added by default.
			if ( empty( $schedules['weekly'] ) ) {
				$schedules['weekly'] = array(
					'interval' => DAY_IN_SECONDS * 7,
					'display'  => __( 'Once Weekly', $this->text_domain ),
				);
			}

			return $schedules;
		}

		/**
		 * Schedule cron
		 */
		public function schedule_cron() {
			// Schedule a weekly cron to send usage data.
			$hook_name = $this->plugin_abbr . '_send_tracking_data';
			if ( ! wp_next_scheduled( $hook_name ) ) {
				wp_schedule_event( time(), 'weekly', $hook_name );
			}
		}

		/**
		 * Clear any scheduled cron
		 */
		public function clear_scheduled_cron() {
			wp_clear_scheduled_hook( $this->plugin_abbr . '_send_tracking_data' );
		}

		/**
		 * Show tracking notice
		 */
		public function show_tracker_notice() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$show_tracking_notice = apply_filters( $this->plugin_abbr . '_show_plugin_usage_tracking_notice', false );

			if ( false === $show_tracking_notice ) {
				return;
			}

			$tracking_allowed = $this->is_tracking_allowed();

			// Check if tracking option is set. Can be yes or no if set else empty.
			// yes/no indicates the notice has already been shown and user has opted in/out. Don't show the notice in this case.
			if ( ! empty( $tracking_allowed ) ) {
				return;
			}

			$optin_url  = wp_nonce_url( add_query_arg( $this->plugin_abbr . '_tracker', 'opt_into' ), $this->plugin_abbr . '_tracker_action' );
			$optout_url = wp_nonce_url( add_query_arg( $this->plugin_abbr . '_tracker', 'opt_out' ), $this->plugin_abbr . '_tracker_action' );

			?>
			<style type="text/css">
				#ig-plugin-usage-tracker-notice .button {
					font-size: 13px;
					line-height: 2;
				}

				#ig-plugin-usage-tracker-notice .button-primary {
					color: #fff;
				}
			</style>
			<div id="ig-plugin-usage-tracker-notice" class="notice notice-success" style="background: #ffefd5;">
				<p>
				<span class="dashicons dashicons-megaphone" style="color: #5850EC;"></span>
				<?php
					/* translators: %s. Plugin name. */
					echo sprintf( esc_html__( 'Help us to improve %s by opting in to share non-sensitive plugin usage data. No personal data is tracked or stored.', $this->text_domain ), '<strong>' . esc_html( $this->name ) . '</strong>' );
				?>
				<a class="<?php echo esc_js( $this->plugin_abbr ); ?>-show-tracked-data-list" href="#">
					<?php echo esc_html__( 'What we collect?', $this->text_domain ); ?>
				</a>
				</p>
				
				<ul class="tracked-data-list my-3" style="display:none;">
					<?php
					$tracked_data_list = $this->get_tracked_data_list();
					if ( ! empty( $tracked_data_list ) ) {
						foreach ( $tracked_data_list as $tracked_data ) {
							?>
							<li>
								&#x23FA;
								<span class="ml-1">
									<?php echo esc_html( $tracked_data ); ?>
								</span>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<p class="ml-4">
					<a href="<?php echo esc_url( $optin_url ); ?>" class="button button-primary">
						<?php
							echo esc_html__( 'Yes, count me in!', $this->text_domain );
						?>
					</a>
					<a href="<?php echo esc_url( $optout_url ); ?>" class="text-gray-500 hover:text-gray-600 hover:underline ml-3">
						<?php
							echo esc_html__( 'No thanks', $this->text_domain );
						?>
					</a>
				</p>
				<script type='text/javascript'>
					jQuery('.<?php echo esc_js( $this->plugin_abbr ); ?>-show-tracked-data-list').on('click', function(e) {
						e.preventDefault();
						jQuery(this).parents('.notice').find('.tracked-data-list').slideToggle('fast');
					});
				</script>
			</div>
			<?php
		}

		/**
		 * Get list of tracked data
		 *
		 * @return array
		 */
		public function get_tracked_data_list() {

			$data = apply_filters(
				$this->plugin_abbr . '_tracked_data_list',
				array(
					__( 'Server environment details (PHP, MYSQL, Server name etc.)', $this->text_domain ),
					__( 'WordPress environment details (Site URL, Site language, Timezone, WordPress version etc.)', $this->text_domain ),
					__( 'Installed plugins details', $this->text_domain ),
					__( 'Active theme details', $this->text_domain ),
					__( 'Admin name and email address', $this->text_domain ),
				)
			);

			return $data;
		}

		/**
		 * Is allow track.
		 *
		 * Checks whether the site admin has opted-in for data tracking, or not.
		 *
		 * @return bool
		 */
		public function is_tracking_allowed() {
			$tracking_option_name = $this->get_tracking_option_name();
			$tracking_allowed     = get_option( $tracking_option_name, '' );

			// Enable tracking by default if allowed_by_default is true.
			if ( empty( $tracking_allowed ) && $this->allowed_by_default ) {
				$tracking_allowed = 'yes';
				$this->set_opt_in( $tracking_allowed );
			}

			return $tracking_allowed;
		}

		/**
		 * Get tracking option name
		 *
		 * @return string
		 */
		public function get_tracking_option_name() {
			return $this->plugin_abbr . '_allow_tracking';
		}

		/**
		 * Handle tracker actions.
		 *
		 * Check if the user opted-in or opted-out and update the database.
		 *
		 * Fired by `admin_init` action.
		 */
		public function handle_tracker_notice_actions() {

			if ( ! isset( $_GET[ $this->plugin_abbr . '_tracker' ] ) ) {
				return;
			}

			check_admin_referer( $this->plugin_abbr . '_tracker_action' );

			$opted_in = 'no';
			if ( 'opt_into' === $_GET[ $this->plugin_abbr . '_tracker' ] ) {
				$opted_in = 'yes';
			}

			$this->set_opt_in( $opted_in );

			wp_safe_redirect( remove_query_arg( array( $this->plugin_abbr . '_tracker', '_wpnonce' ) ) );
			exit;
		}

		/**
		 * Set tracking option
		 *
		 * @param string $opted_in yes/no
		 */
		public function set_opt_in( $opted_in ) {
			$tracking_option_name = $this->get_tracking_option_name();
			update_option( $tracking_option_name, $opted_in, false );
		}

		/**
		 * Send tracking data.
		 *
		 * @param bool  $ignore_last_send Whether to consider last sending time before sending a new request.
		 * @param array $extra_params Extra request params.
		 */
		public function send_tracking_data( $ignore_last_send = false, $extra_params = array() ) {

			$tracking_allowed = $this->is_tracking_allowed();

			// Return if tracking not allowed
			if ( 'yes' !== $tracking_allowed ) {
				return;
			}

			if ( ! $ignore_last_send ) {
				$last_send = $this->get_last_send_time();

				$last_send_interval = strtotime( '-1 week' );

				// Send a maximum of once per week.
				if ( $last_send && $last_send > $last_send_interval ) {
					return;
				}
			}

			// Update time first before sending to ensure it is set.
			$this->update_last_send_time( time() );

			$params = $this->get_tracking_data();
			if ( ! empty( $extra_params ) ) {
				$params = array_merge( $params, $extra_params );
			}

			if ( ! empty( $params ) && is_array( $params ) ) {

				add_filter( 'https_ssl_verify', '__return_false' );
				wp_remote_post(
					$this->api_url,
					array(
						'timeout'  => 25,
						'blocking' => false,
						'body'     => array(
							'data' => $params,
						),
					)
				);
			}
		}

		/**
		 * Get last send time.
		 *
		 * Retrieve the last time tracking data was sent.
		 *
		 * @return int|false The last time tracking data was sent, or false if
		 *                   tracking data never sent.
		 */
		public function get_last_send_time() {
			$last_send_time = get_option( $this->plugin_abbr . '_tracking_last_send', false );

			return $last_send_time;
		}

		/**
		 * Update last send time.
		 *
		 * @param int $send_time
		 */
		public function update_last_send_time( $send_time = 0 ) {
			if ( empty( $send_time ) ) {
				$send_time = time();
			}

			update_option( $this->plugin_abbr . '_tracking_last_send', $send_time );
		}

		/**
		 * Get tracker class name being used by the plugin
		 *
		 * @return string
		 */
		public function get_tracker_class() {
			return $this->tracker_class;
		}

		/**
		 * Get the tracking data
		 *
		 * Retrieve tracking data and apply filter
		 *
		 * @return array
		 */
		public function get_tracking_data() {

			$tracker_class = $this->get_tracker_class();

			$params = array(
				'sdk_version'   => $this->sdk_version,
				'product_id'    => $this->product_id,
				'plan'          => $this->plugin_plan,
				'user_info'     => $tracker_class::get_user_info(),
				'plugins_info'  => array(
					'active_plugins'   => $tracker_class::get_active_plugins( true ),
					'inactive_plugins' => $tracker_class::get_inactive_plugins( true ),
				),
				'current_theme' => $tracker_class::get_current_theme_info(),
				'wp_info'       => $tracker_class::get_wp_info(),
				'server_info'   => $tracker_class::get_server_info(),
			);

			/**
			 * Tracker send tracking data params.
			 *
			 * Filters the data parameters when sending tracking request.
			 *
			 * @param array $params Variable to encode as JSON.
			 */
			$params = apply_filters( $this->plugin_abbr . '_tracking_data_params', $params );

			return $params;
		}
	}
}
