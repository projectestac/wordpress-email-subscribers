<?php
/**
 * Action to send an email to provided email address.
 *
 * @since       4.5.3
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle send email action
 * 
 * @class ES_Action_Send_Email
 * 
 * @since 4.5.3
 */

if ( ! class_exists( 'ES_Action_Send_Email' ) ) {
	class ES_Action_Send_Email extends ES_Action_Send_Email_Abstract {
	
		/**
		 * Load action admin details.
		 *
		 * @since 4.5.3
		 */
		public function load_admin_details() {
			$this->group = __( 'Email', 'email-subscribers' );
			$this->title = __( 'Send Email', 'email-subscribers' );
		}
	
		/**
		 * Load action fields
		 *
		 * @since 4.5.3
		 */
		public function load_fields() {

			global $ig_es_tracker;

			parent::load_fields();

			$is_woocommerce_active = $ig_es_tracker::is_plugin_activated( 'woocommerce/woocommerce.php' );
			if ( $is_woocommerce_active ) {

				$email_template_field = new ES_Select( false );
				$email_template_field->set_name( 'ig-es-email-template' );
				$email_template_field->set_title( __( 'Email styling', 'email-subscribers' ) );
				$email_template_field->set_description( __( 'Select which style to use when formatting the email.', 'email-subscribers' ) );

				$email_template_field_options = array(
					'none'	      => __( 'None', 'email-subscribers' ),
					'woocommerce' => 'WooCommerce email styling',
				);

				$email_template_field->set_options( $email_template_field_options );
				$email_template_field->set_required();

				$this->add_field( $email_template_field );

				$email_heading_field = new ES_Text();
				$email_heading_field->set_name( 'ig-es-email-heading' );
				$email_heading_field->set_title( __( 'Email heading', 'email-subscribers' ) );
				$email_heading_field->set_description( __( 'Enter text to be shown in email header area.', 'email-subscribers' ) );

				$email_template = $this->get_option( 'ig-es-email-template', false );

				$is_wocoomerce_template = 'woocommerce' === $email_template;
				if ( ! $is_wocoomerce_template ) {
					$email_heading_field->add_container_classes( 'hidden' );
				}

				$this->add_field( $email_heading_field );
			}

	
			$tracking_campaign_id = $this->get_option( 'ig-es-tracking-campaign-id', false );

			if ( empty( $tracking_campaign_id ) ) {
				$tracking_campaign_id = uniqid();
			}

			$email_content = new ES_WP_Editor();
			$email_content->set_id( 'ig-es-workflow-email-content-' . $tracking_campaign_id );
			$email_content->set_name( 'ig-es-email-content' );
			$email_content->set_title( __( 'Email content', 'email-subscribers' ) );
			$email_content->set_required();
	
			$this->add_field( $email_content );
	
			$tracking_field = new ES_Checkbox();
			$tracking_field->set_name( 'ig-es-email-tracking-enabled' );
			$tracking_field->set_title( __( 'Track opens and clicks', 'email-subscribers' ) );
			$tracking_field->add_classes( 'form-checkbox text-indigo-600' );
			$tracking_field->default_to_checked = false;
			$this->add_field( $tracking_field );
	
			$tracking_campaign_id = new ES_Hidden_Field();
			$tracking_campaign_id->set_name( 'ig-es-tracking-campaign-id' );
			$this->add_field( $tracking_campaign_id );

		}

		/**
		 * Create content for showing preview
		 *
		 * @return mixed|null
		 */
		public function load_preview() {
			$email_content  = $this->get_option( 'ig-es-email-content', true, true );
			$email_template = $this->get_option( 'ig-es-email-template', false );
			$email_heading  = $this->get_option( 'ig-es-email-heading', false );
			$email_content  = wpautop( $email_content );

			$email_content = $this->add_template_styling( $email_content, $email_heading, $email_template );
			$current_user  = wp_get_current_user();

			$email_content = ES_Common::replace_keywords_with_fallback( $email_content, array(
				'subscriber.first_name' => $current_user->first_name,
				'subscriber.name'      => $current_user->display_name,
				'subscriber.last_name'  => $current_user->last_name,
				'subscriber.email'     => $current_user->user_email
			) );

			return ES_Common::replace_keywords_with_fallback( $email_content, array(
				'EMAIL'     => $current_user->user_email,
				'NAME'      => $current_user->display_name,
				'FIRSTNAME' => $current_user->first_name,
				'LASTNAME'  => $current_user->last_name,
			) );
		}

		/**
		 * Called when an action should be run
		 *
		 * @since 4.5.3
		 */
		public function run() {
	
			$recipients           = $this->get_option( 'ig-es-send-to', true );
			$subject              = $this->get_option( 'ig-es-email-subject', true );
			$email_template       = $this->get_option( 'ig-es-email-template', false );
			$email_heading        = $this->get_option( 'ig-es-email-heading', false );
			$email_content        = $this->get_option( 'ig-es-email-content', true, true );
			$tracking_enabled     = $this->get_option( 'ig-es-email-tracking-enabled', false );
			$tracking_campaign_id = $this->get_option( 'ig-es-tracking-campaign-id', false );
	
			$recipients = explode(',', $recipients );
			$recipients = array_map( 'trim', $recipients );
			
			// Check if we have all required data to send the email.
			if ( empty( $recipients ) || empty( $email_content ) || empty( $subject ) ) {
				return;
			}
			
			// Replace line breaks with paragraphs in email body.
			$email_content = wpautop( $email_content );
			
			$raw_data = $this->workflow->data_layer()->get_raw_data();

			if ( ! empty( $raw_data ) ) {
				foreach ( $raw_data as $data_type_id => $data_item ) {
					$data_type = ES_Workflow_Data_Types::get( $data_type_id );
					if ( ! $data_type || ! $data_type->validate( $data_item ) ) {
						continue;
					}
	
					$data = array();
					if ( method_exists( $data_type, 'get_data' ) ) {
						$data = $data_type->get_data( $data_item );
						
						if ( ! empty( $data['email'] ) ) {
							foreach ( $recipients as $index => $recipient_email ) {
								// Replace placeholder tags with the got data from the triggerred event.
								$recipients[$index] = str_replace( '{{EMAIL}}', $data['email'], $recipient_email );
							}
							
							// If source is 'es, it means it is from ES subscriber form, replace {{EMAIL}}, {{NAME}} placeholders with subscriber's email, name
							// If we don't replace it here then for workflow configured to be sent to admins, {{EMAIL}}, {{NAME}} gets replaced with admin email and names which is not desired for subscriber based workflows.
							if ( 'es' === $data['source'] ) {
								$subject = str_replace( '{{EMAIL}}', $data['email'], $subject );
								$subject = str_replace( '{{NAME}}', $data['name'], $subject );
								
								$email_content = str_replace( '{{EMAIL}}', $data['email'], $email_content );
								$email_content = str_replace( '{{NAME}}', $data['name'], $email_content );
							}
						}
	
						if ( 'campaign' === $data_type_id && ! empty( $data['notification_guid'] ) ) {
							$notification     = ES_DB_Mailing_Queue::get_notification_by_hash( $data['notification_guid'] );
							$subject          = str_replace( '{{SUBJECT}}', $notification['subject'], $subject );
							$email_count      = $notification['count'];
							$campaign_subject = $notification['subject'];
							$cron_date        = gmdate( 'Y-m-d H:i:s' );
							$cron_local_date  = get_date_from_gmt( $cron_date ); // Convert from GMT to local date/time based on WordPress time zone setting.
							$cron_date        = ES_Common::convert_date_to_wp_date( $cron_local_date ); // Get formatted date from WordPress date/time settings.
			
							$email_content = str_replace( '{{DATE}}', $cron_date, $email_content );
							$email_content = str_replace( '{{COUNT}}', $email_count, $email_content );
							$email_content = str_replace( '{{SUBJECT}}', $campaign_subject, $email_content );
						}
					}
					
				}
	
				$email_content = $this->add_template_styling( $email_content, $email_heading, $email_template );
	
				$es_mailer = ES()->mailer;
				
				if ( ! empty( $tracking_campaign_id ) ) {
					$data['campaign_id'] = $tracking_campaign_id;
				}

				if ( $tracking_enabled ) {
					$es_mailer->add_tracking_pixel = true;
				} else {
					$es_mailer->add_tracking_pixel = false;
				}
	
				$es_mailer->add_unsubscribe_link = false;

				$es_mailer->send( $subject, $email_content, $recipients, $data );
			}
		}
		
		/**
		 * Add template styling to email content.
		 */
		public function add_template_styling( $email_content, $email_heading = '', $email_template = 'none' ) {

			if ( 'woocommerce' === $email_template ) {

				// Make sure WC function exisists before calling it.
				if ( function_exists( 'WC' ) ) {
					$email_content = WC()->mailer()->wrap_message( $email_heading, $email_content );
					$wc_email      = new WC_Email();
					$email_content = $wc_email->style_inline( $email_content );
					// When inlining CSS, curly braces {{UNSUBSCRIBE-LINK}} gets converted to following characters. We are reverting them back 
					$email_content = str_replace( array( '%5C%7B', '%5C%7D' ), array( '{', '}' ), $email_content );
					$email_content = str_replace( array( '%7B', '%7D' ), array( '{', '}' ), $email_content );
				}
			}

			return $email_content;
		}
	}
} 
