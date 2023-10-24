<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle Icegram site's weekly newsletter summary automation
 */
class ES_Newsletter_Summary_Automation {

	protected $cron_hook = 'ig_es_newsletter_summary_automation';

	protected $option_name = 'ig_es_enable_newsletter_summary_automation';

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( $this->cron_hook, array( $this, 'send_newsletter_summary_email' ) );
		add_action( 'ig_es_plugin_activate', array( $this, 'maybe_enable_newsletter_summary_automation' ) );
		add_action( 'ig_es_enable_newsletter_summary_automation', array( $this, 'maybe_enable_newsletter_summary_automation' ) );
		add_action( 'ig_es_plugin_deactivate', array( $this, 'clear_scheduled_automation' ) );
		add_action( 'admin_init' , array( $this, 'maybe_disable_automation' ) );
	}

	/**
	 * Handle automation while saving the settings.This action may clear or reschedule the automation as per need.
	 *
	 * @param $settings
	 */
	public function maybe_enable_newsletter_summary_automation() {
		$is_automation_enabled = get_option( $this->option_name, 'yes' );
		if ( 'yes' === $is_automation_enabled ) {
			$this->schedule_summary_automation( true, false );
		} else {
			$this->clear_scheduled_automation();
		}
	}

	/**
	 * Handle the cron event
	 *
	 * @return bool
	 */
	public function send_newsletter_summary_email() {

		// Return if pro since we are already doing this in the pro.
		if ( ES()->is_pro() ) {
			$this->clear_scheduled_automation();
			return;
		}

		$email_data = self::get_email_data();
		if ( ! empty( $email_data ) ) {
			ES()->mailer->can_track_open_clicks   = false;
			ES()->mailer->add_unsubscribe_link = false;
	
			return ES()->mailer->send( $email_data['subject'], $email_data['content'], $email_data['email'] );
		}

		return false;
	}

	public static function get_email_data() {
		$admin_email = ES_Common::get_admin_email();
		if ( is_email( $admin_email ) ) {
			$user       = get_user_by( 'email', $admin_email );
			$admin_name = '';
			if ( $user instanceof WP_User ) {
				$admin_name = $user->display_name;
			}
			
			$interval = 7;
			$today    = time();
			$plan     = ES()->get_plan();

			$data = array(
				'plan'               => ES()->get_plan(),
				'site_name'          => get_option( 'blogname' ),
				'admin_name'         => $admin_name,
				'logo_url'           => ES_PLUGIN_URL . 'lite/admin/images/es-logo-64x64.png',
				'start_date'         => gmdate( 'F d', $today - ( $interval * DAY_IN_SECONDS ) ),
				'end_date'           => gmdate( 'F d, Y', $today ),
			);

			$admin_url = admin_url();
			if ( 'pro' === $plan ) {
				$reports_url         = $admin_url . 'admin.php?page=es_reports';
				$data['reports_url'] = $reports_url;
			} else {
				$unsubscribe_url         = $admin_url . '?es=ig-newsletter-unsubscribe';
				$data['unsubscribe_url'] = $unsubscribe_url;
			}

			$email_stats = self::get_email_stats( $interval );
			
			$data    = array_merge( $data, $email_stats );
			$content = self::get_content( $data );

			$email_data = array(
				'subject' => __( 'Weekly Report from Icegram Express', 'email-subscribers' ),
				'email'   => $admin_email,
				'content' => $content,
			);

			return $email_data;
		}

		return false;
	}

	public static function get_email_stats( $interval = 7 ) {
		
		$args = array(
			'days' => $interval
		);

		$distinct_count = false;

		$email_stats = array(
			'total_subscribed'    => ES_Reports_Data::get_total_subscribed_contacts( $args, $distinct_count ),
			'total_sent_mails'   => ES_Reports_Data::get_total_emails_sent( $args, $distinct_count ),
			'total_opened_mails' => ES_Reports_Data::get_total_contacts_opened_emails( $args, $distinct_count ),
		);

		if ( ES()->is_pro() ) {
			$pro_email_stats = array(
				'contacts_growth'     => ES_Reports_Data::get_contacts_growth_percentage( $args ),
				'total_unsubscribed'  => ES_Reports_Data::get_total_unsubscribed_contacts( $args ),
				'total_clicked_mails' => ES_Reports_Data::get_total_contacts_clicks_links( $args ),
			);

			$email_stats = array_merge( $email_stats, $pro_email_stats );
		}

		return $email_stats;
	}

	/**
	 * Get the HTML content required for email
	 *
	 * @param array $data
	 *
	 * @return false|string
	 */
	public static function get_content( $data ) {
		ob_start();
		ES_Admin::get_view(
			'newsletter-summary',
			$data
		);
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * Handles scheduling the automation
	 *
	 * @param false $force_clear Do I need to clear the scheduled event before scheduling the event
	 */
	public function schedule_summary_automation( $force_clear = false, $check_setting_before_schedule = true ) {
		//Don't give workload to site users
		if ( ! is_admin() ) {
			return false;
		}
		if ( $check_setting_before_schedule ) {
			$ig_es_enable_newsletter_summary_automation = get_option( $this->option_name, 'yes' );
			if ( 'no' === $ig_es_enable_newsletter_summary_automation ) {
				return false;
			}
		}
		$run_cron_on   = 'thursday';
		$run_cron_time = '9am';
		$day_and_time  = "{$run_cron_on} {$run_cron_time}";
		//Schedule an action if it's not already scheduled
		if ( ! wp_next_scheduled( $this->cron_hook ) ) {
			$scheduled_time = $this->get_schedule_time( $day_and_time );
			if ( ! is_null( $scheduled_time ) ) {
				$response = wp_schedule_event( $scheduled_time, 'weekly', $this->cron_hook, array(), true );
				if ( $response instanceof WP_Error ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get the schedule time from the day
	 *
	 * @param $day_and_time string
	 *
	 * @return false|float|int
	 */
	public function get_schedule_time( $day_and_time ) {
		try {
			$date                   = new DateTime( $day_and_time );
			$scheduled_datetime     = $date->format( 'Y-m-d h:i:s A' );
			$scheduled_datetime_gmt = get_gmt_from_date( $scheduled_datetime );

			return strtotime( $scheduled_datetime_gmt );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Clear the scheduled automation
	 */
	public function clear_scheduled_automation() {
		wp_clear_scheduled_hook( $this->cron_hook );
	}

	public function maybe_disable_automation() {
		$action = ig_es_get_request_data( 'es' );
		if ( 'ig-newsletter-unsubscribe' === $action  ) {
			$status = 'no';
			$this->update_newsletter_summary_automation( $status );
			$this->clear_scheduled_automation();
			$this->show_unsubscribe_success_message();
		}
	}

	public function update_newsletter_summary_automation( $status = 'no' ) {
		update_option( $this->option_name, $status, false );
	}

	public function show_unsubscribe_success_message() {
		$message = __( 'You have been unsubscribed from Icegram Express\'s Weekly Report.', 'email-subscribers' );
		include ES_PLUGIN_DIR . 'lite/public/partials/subscription-successfull.php';
		die();
	}
}

new ES_Newsletter_Summary_Automation();
