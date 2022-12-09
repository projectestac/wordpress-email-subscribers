<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Admin Settings
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */
class ES_Tools {
	// class instance
	public static $instance;

	public function __construct() {
		// Allow only to send test email to user who have Settings & Campaigns permission
		$accessible_sub_menus = ES_Common::ig_es_get_accessible_sub_menus();
		if ( defined( 'DOING_AJAX' ) && ( in_array( 'settings', $accessible_sub_menus ) || in_array( 'campaigns', $accessible_sub_menus ) ) ) {
			add_action( 'wp_ajax_es_send_test_email', array( $this, 'send_test_email' ) );
		}
	}

	/**
	 * Send Test Email
	 *
	 * @since 4.0.0
	 * @since 4.3.2 Call ES()->mailer->send_test_email() method to send test email
	 */
	public function send_test_email() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array();

		$email         = sanitize_email( ig_es_get_request_data( 'es_test_email' ) );
		$campaign_id   = ig_es_get_data( $_POST, 'campaign_id', 0, true );
		$campaign_type = ig_es_get_data( $_POST, 'campaign_type', '', true );
		$template_id   = ig_es_get_data( $_POST, 'template_id', 0, true );
		$subject       = ig_es_get_data( $_POST, 'subject', '', true );
		$content       = ig_es_get_request_data( 'content', '', false );
		$attachments   = ig_es_get_data( $_POST, 'attachments', array(), true );

		if ( ! empty( $email ) ) {

			$merge_tags = array( 'attachments' => $attachments );

			if ( ! empty( $campaign_id ) ) {
				$campaign_data = array(
					'id'               => $campaign_id,
					'type'             => $campaign_type,
					'base_template_id' => $template_id,
					'subject'          => $subject,
					'body'             => $content,
				);
				if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign_type ) {
					$campaign_data = ES_Campaign_Admin::replace_post_notification_merge_tags_with_sample_post( $campaign_data );
				} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign_type ) {
					$campaign_data = ES_Campaign_Admin::replace_post_digest_merge_tags_with_sample_posts( $campaign_data );
				}

				$merge_tags['campaign_id'] = $campaign_id;

				$subject = $campaign_data['subject'];
				$content = $campaign_data['body'];
			}


			$content = ES_Common::es_process_template_body( $content, $template_id, $campaign_id );

			$response = ES()->mailer->send_test_email( $email, $subject, $content, $merge_tags );

			if ( $response && 'SUCCESS' === $response['status'] ) {
				$response['message'] = __( 'Email has been sent. Please check your inbox', 'email-subscribers' );
			}
		}

		echo json_encode( $response );
		exit;
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


