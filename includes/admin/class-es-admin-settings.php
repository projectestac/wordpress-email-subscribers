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
 * @author     Your Name <email@example.com>
 */
class ES_Admin_Settings {

	// class instance
	static $instance;

	// subscriber WP_Template_Table object
	public $subscribers_obj;

	// class constructor
	public function __construct() {
		//add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		// add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}


	public function es_settings_callback() {

		$submitted     = ! empty( $_POST['submitted'] ) ? $_POST['submitted'] : '';
		$submit_action = ! empty( $_POST['submit_action'] ) ? $_POST['submit_action'] : '';

		$nonce = esc_attr( Email_Subscribers::get_request( '_wpnonce' ) );

		if ( 'submitted' === $submitted && 'ig-es-save-admin-settings' === $submit_action ) {
			$options = $_POST;
			$options = apply_filters( 'ig_es_before_save_settings', $options );
			foreach ( $options as $key => $value ) {
				if ( substr( $key, 0, 6 ) === 'ig_es_' ) {
					update_option( $key, stripslashes( $value ) );
				}
			}
			$message = __( 'Settings have been saved successfully!' );
			$status  = 'success';
			$this->show_message( $message, $status );
		}


		?>

        <div class="wrap essettings">
            <h1 class="wp-heading-inline">Settings</h1>
            <form action="#" method="post" id="email_tabs_form" class="ig-settings-form rcorners">

				<?php settings_fields( 'email_subscribers_settings' ); ?>

                <div id="tabs">
                    <div id="menu-tab-listing" class="">
                        <ul class="main-tab-nav">
                            <li class="ig-menu-tab"><a href="#tabs-1"><i class="dashicons dashicons-admin-generic"></i>&nbsp;<?php echo __( 'General', 'email_subscribers' ); ?></a></li>
                            <li class="ig-menu-tab"><a href="#tabs-2"><i class="dashicons dashicons-groups"></i>&nbsp;<?php echo __( 'Notifications', 'email_subscribers' ); ?></a></li>
                            <li class="ig-menu-tab"><a href="#tabs-3"><i class="dashicons dashicons-schedule"></i>&nbsp;<?php echo __( 'Email Sending', 'email_subscribers' ); ?></a></li>
                            <li class="ig-menu-tab"><a href="#tabs-4"><i class="dashicons dashicons-lock"></i>&nbsp;<?php echo __( 'Security', 'email_subscribers' ); ?></a></li>
                        </ul>
                    </div>
                    <div id="menu-tab-content">
						<?php $settings = self::get_registered_settings(); ?>
                        <div id="tabs-1"><?php $this->render_settings_fields( $settings['general'] ); ?></div>
                        <div id="tabs-2"><?php $this->render_settings_fields( $settings['signup_confirmation'] ); ?></div>
                        <div id="tabs-3"><?php $this->render_settings_fields( $settings['cron_settings'] ); ?></div>
                        <div id="tabs-4"><?php $this->render_settings_fields( $settings['security_settings'] ); ?></div>
                    </div>

                </div>

                <!--
                <div class="content save">
                    <input type="hidden" name="submitted" value="submitted"/>
                    <input type="hidden" name="submit_action" value="ig-es-save-admin-settings"/>
					<?php $nonce = wp_create_nonce( 'es-update-settings' ); ?>

                    <input type="hidden" name="update-settings" id="ig-update-settings" value="<?php echo $nonce; ?>"/>
					<?php submit_button(); ?>
                </div>
                -->
            </form>
        </div>
		<?php

	}

	public function es_roles_sanitize_options( $input ) {
		$input['option_display_mode'] = wp_filter_nohtml_kses( $input['option_display_mode'] );
		$input['option_font_size']    = sanitize_text_field( absint( $input['option_font_size'] ) );
		$input['option_font_color']   = sanitize_text_field( $input['option_font_color'] );
		$input['option_custom_css']   = esc_textarea( $input['option_custom_css'] );

		return $input;
	}

	public static function get_registered_settings() {


		$es_settings = array(

			'general' => array(

				'sender_information' => array(
					'id'         => 'sender_information',
					'name'       => __( 'Sender', 'email-subscribers' ),
					'sub_fields' => array(
						'from_name' => array(
							'id'          => 'ig_es_from_name',
							'name'        => __( 'Name', 'email-subscribers' ),
							'desc'        => 'Choose a FROM name for all the emails to be sent from this plugin.',
							'type'        => 'text',
							'placeholder' => __( 'Name', 'email-subscribers' ),
							'default'     => ''
						),

						'from_email' => array(
							'id'          => 'ig_es_from_email',
							'name'        => __( 'Email', 'email-subscribers' ),
							'desc'        => __( 'Choose a FROM email address for all the emails to be sent from this plugin', 'email-subscribers' ),
							'type'        => 'text',
							'placeholder' => __( 'Email Address', 'email-subscribers' ),
							'default'     => ''
						),
					)
				),

				'admin_email' => array(
					'id'      => 'ig_es_admin_emails',
					'name'    => __( 'Email Addresses', 'email-subscribers' ),
					'type'    => 'text',
					'desc'    => __( 'Enter the admin email addresses that should receive notifications (separated by comma).', 'email-subscribers' ),
					'default' => ''
				),

				'email_type' => array(
					'id'      => 'ig_es_email_type',
					'name'    => __( 'Email Type', 'email-subscribers' ),
					'desc'    => __( 'Select whether to send HTML or Plain Text email.', 'email-subscribers' ),
					'type'    => 'select',
					'options' => ES_Common::get_email_sending_type(),
					'default' => 'wp_html_mail'
				),

				'ig_es_optin_type' => array(
					'id'      => 'ig_es_optin_type',
					'name'    => __( 'Opt-in Type', 'email-subscribers' ),
					'desc'    => '',
					'type'    => 'select',
					'options' => ES_Common::get_optin_types(),
					'default' => ''
				),

				'ig_es_post_image_size'          => array(
					'id'      => 'ig_es_post_image_size',
					'name'    => __( 'Image Size', 'email-subscribers' ),
					'type'    => 'select',
					'options' => ES_Common::get_image_sizes(),
					'desc'    => '<p>Select image size for {{POSTIMAGE}} to be shown in the Post Notification Emails.</p>',
					'default' => 'full'
				),

				//'ig_es_unsubscribe_link'             => array( 'type' => 'text', 'options' => false, 'placeholder' => '', 'readonly' => 'readonly', 'supplemental' => '', 'default' => '', 'id' => 'ig_es_unsubscribe_link', 'name' => __( 'Unsubscribe Link', 'email-subscribers' ), 'desc' => '', ),
				'ig_es_unsubscribe_link_content' => array(
					'type'         => 'textarea',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_unsubscribe_link_content',
					'name'         => __( 'Show Unsubscribe Message In Email Footer', 'email-subscribers' ),
					'desc'         => __( 'Add text which you want your contact to see in footer to unsubscribe. Use {{UNSUBSCRIBE-LINK}} keyword to add unsubscribe link.', 'email-subscribers' ),
				),

				//'ig_es_optin_link'                   => array( 'type' => 'text', 'options' => false, 'readonly' => 'readonly', 'placeholder' => '', 'supplemental' => '', 'default' => '', 'id' => 'ig_es_optin_link', 'name' => 'Double Opt-In Confirmation Link', 'desc' => '', ),

				'subscription_messages' => array(
					'id'         => 'subscription_messages',
					'name'       => __( 'Subscription Success/ Error Messages', 'email-subscribers' ),
					'sub_fields' => array(
						'ig_es_subscription_success_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'You have been subscribed successfully!', 'email-subscribers' ),
							'id'           => 'ig_es_subscription_success_message',
							'name'         => __( 'Success Message', 'email-subscribers' ),
							'desc'         => __( 'Show this message if contact is successfully subscribed from Double Opt-In (Confirmation) Email', 'email-subscribers' )
						),

						'ig_es_subscription_error_messsage' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Oops.. Your request couldn\'t be completed. This email address seems to be already subscribed / blocked.', 'email-subscribers' ),
							'id'           => 'ig_es_subscription_error_messsage',
							'name'         => __( 'Error Message', 'email-subscribers' ),
							'desc'         => __( 'Show this message if any error occured after clicking confirmation link from Double Opt-In (Confirmation) Email.', 'email-subscribers' )
						),

					)
				),

				'unsubscription_messages' => array(
					'id'         => 'unsubscription_messages',
					'name'       => __( 'Unsubscribe Success/ Error Messages', 'email-subscribers' ),
					'sub_fields' => array(

						'ig_es_unsubscribe_success_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Thank You, You have been successfully unsubscribed. You will no longer hear from us.', 'email-subscribers' ),
							'id'           => 'ig_es_unsubscribe_success_message',
							'name'         => __( 'Success Message', 'email-subscribers' ),
							'desc'         => __( 'Once contact clicks on unsubscribe link, he/she will be redirected to a page where this message will be shown.', 'email-subscribers' )
						),


						'ig_es_unsubscribe_error_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => 'Oops.. There was some technical error. Please try again later or contact us.',
							'id'           => 'ig_es_unsubscribe_error_message',
							'name'         => __( 'Error Message', 'email-subscribers' ),
							'desc'         => __( 'Show this message if any error occured after clicking on unsubscribe link.', 'email-subscribers' )
						)
					)
				),


				/*
				'sent_report_subject' => array(
					'id'      => 'ig_es_sent_report_subject',
					'name'    => __( 'Sent Report Subject', 'email-subscribers' ),
					'type'    => 'text',
					'desc'    => __( 'Subject for the email report which will be sent to admin.', 'email-subscribers' ),
					'default' => 'Your email has been sent'
				),

				'sent_report_content' => array(
					'id'   => 'ig_es_sent_report_content',
					'name' => __( 'Sent Report Content', 'email-subscribers' ),
					'type' => 'textarea',
					'desc' => __( 'Content for the email report which will be sent to admin.</p><p>Available Keywords: {{COUNT}}, {{UNIQUE}}, {{STARTTIME}}, {{ENDTIME}}', 'email-subscribers' ),
				),
				*/
			),

			'signup_confirmation' => array(

				'welcome_emails' => array(
					'id'         => 'welcome_emails',
					'name'       => __( 'Welcome Email', 'email-subscribers' ),
					'sub_fields' => array(

						'ig_es_enable_welcome_email' => array(
							'type'         => 'select',
							'options'      => array( 'yes' => 'Yes', 'no' => 'No', ),
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => 'yes',
							'id'           => 'ig_es_enable_welcome_email',
							'name'         => __( 'Enable?', 'email-subscribers' ),
							'desc'         => __( 'Send welcome email to new contact after signup.', 'email-subscribers' ),
						),

						'ig_es_welcome_email_subject' => array(
							'type'         => 'text',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_welcome_email_subject',
							'name'         => __( 'Subject', 'email-subscribers' ),
							'desc'         => '',
						),
						'ig_es_welcome_email_content' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_welcome_email_content',
							'name'         => __( 'Content', 'email-subscribers' ),
							'desc'         => 'Available keywords. {{NAME}}, {{EMAIL}}, {{LIST}}, {{UNSUBSCRIBE-LINK}}',
						),
					)
				),

				'confirmation_notifications' => array(
					'id'         => 'confirmation_notifications',
					'name'       => __( 'Confirmation Email', 'email-subscribers' ),
					'sub_fields' => array(

						'ig_es_confirmation_mail_subject' => array(
							'type'         => 'text',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_confirmation_mail_subject',
							'name'         => __( 'Subject', 'email-subscribers' ),
							'desc'         => '',
						),

						'ig_es_confirmation_mail_content' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_confirmation_mail_content',
							'name'         => __( 'Content', 'email-subscribers' ),
							'desc'         => __( 'If Double Optin is set, contact will receive confirmation email with above content. You can use {{NAME}}, {{EMAIL}}, {{SUBSCRIBE-LINK}} keywords', 'email-subscribers' ),
						)
					)
				),

				'admin_notifications' => array(

					'id'         => 'admin_notifications',
					'name'       => __( 'Admin Notification On New Subscription', 'email-subscribers' ),
					'sub_fields' => array(

						'notify_admin' => array(
							'id'      => 'ig_es_notify_admin',
							'name'    => __( 'Notify?', 'email-subscribers' ),
							'type'    => 'select',
							'options' => array(
								'yes' => __( 'Yes', 'email-subscribers' ),
								'no'  => __( 'No', 'email-subscribers' )
							),
							'desc'    => __( 'Set this option to "Yes" to notify admin(s) for new contact signup.', 'email-subscribers' ),
							'default' => 'yes'
						),


						'new_contact_email_subject' => array(
							'id'      => 'ig_es_admin_new_contact_email_subject',
							'name'    => __( 'Subject', 'email-subscribers' ),
							'type'    => 'text',
							'desc'    => __( 'Subject for the admin email whenever a new contact signs up and is confirmed', 'email-subscribers' ),
							'default' => __( 'New email subscription', 'email-subscribers' )
						),

						'new_contact_email_content' => array(
							'id'      => 'ig_es_admin_new_contact_email_content',
							'name'    => __( 'Content', 'email-subscribers' ),
							'type'    => 'textarea',
							'desc'    => __( 'Content for the admin email whenever a new subscriber signs up and is confirmed. Available Keywords: {{NAME}}, {{EMAIL}}, {{LIST}}', 'email-subscribers' ),
							'default' => '',
						),
					)
				),

				'ig_es_cron_report' => array(
					'id'         => 'ig_es_cron_report',
					'name'       => __( 'Admin Notification On Every Campaign Sent', 'email-subscribers' ),
					'sub_fields' => array(

						'ig_es_enable_cron_admin_email'  => array(
							'id'      => 'ig_es_enable_cron_admin_email',
							'name'    => __( 'Notify?', 'email-subscribers' ),
							'type'    => 'select',
							'options' => array(
								'yes' => __( 'Yes', 'email-subscribers' ),
								'no'  => __( 'No', 'email-subscribers' )
							),
							'desc'    => __( 'Set this option to "Yes" to notify admin(s) on every campaign sent.', 'email-subscribers' ),
							'default' => 'yes'
						),
						'ig_es_cron_admin_email_subject' => array(
							'type'         => 'text',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Campaign Sent!', 'email-subscribers' ),
							'id'           => 'ig_es_cron_admin_email_subject',
							'name'         => __( 'Subject', 'email-subscribers' ),
							'desc'         => '',
						),

						'ig_es_cron_admin_email' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_cron_admin_email',
							'name'         => __( 'Content', 'email-subscribers' ),
							'desc'         => __( 'Send report to admin(s) whenever campaign is successfully sent to all contacts. Available Keywords: {{DATE}}, {{SUBJECT}}, {{COUNT}}', 'email-subscribers' ),
						)

					)
				)
			),

			'cron_settings' => array(
				'ig_es_cronurl' => array(
					'type'         => 'text',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'readonly'     => 'readonly',
					'id'           => 'ig_es_cronurl',
					'name'         => __( 'Cron URL', 'email-subscribers' ),
					'desc'         => __( sprintf( "You need to visit this URL to send email notifications. Know <a href='%s' target='_blank'>how to run this in background</a>",
						"https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page" ) )
				),

				'ig_es_hourly_email_send_limit' => array(
					'type'         => 'text',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => 50,
					'id'           => 'ig_es_hourly_email_send_limit',
					'name'         => __( 'Maximum Emails Send In An Hour', 'email-subscribers' ),
					'desc'         => __( 'Total emails your host can send in an hour.', 'email-subscribers' )
				),


			),

			'security_settings' => array(
				'blocked_domains' => array(
					'id'      => 'ig_es_blocked_domains',
					'name'    => __( 'Blocked Domain(s)', 'email-subscribers' ),
					'type'    => 'textarea',
					'desc'    => __( 'System won\'t allow email addresses which ends with any of domain availble in above lists. Add list of domain(s) one per line to block.', 'email-subscribers' ),
					'default' => 'mail.ru'
				),
			)


		);

		return apply_filters( 'ig_es_registered_settings', $es_settings );
	}

	public function field_callback( $arguments ) {
		$field_html = '';

		if ( 'ig_es_cronurl' === $arguments['id'] ) {
			$value = ES_Common::get_cron_url();
		} else {
			$value = get_option( $arguments['id'] ); // Get the current value, if there is one
		}
		if ( ! $value ) { // If no value exists
			$value = ! empty( $arguments['default'] ) ? $arguments['default'] : ''; // Set to our default
		}

		$uid         = ! empty( $arguments['id'] ) ? $arguments['id'] : '';
		$type        = ! empty( $arguments['type'] ) ? $arguments['type'] : '';
		$placeholder = ! empty( $arguments['placeholder'] ) ? $arguments['placeholder'] : '';
		$readonly    = ! empty( $arguments['readonly'] ) ? $arguments['readonly'] : '';

		// Check which type of field we want
		switch ( $arguments['type'] ) {
			case 'text': // If it is a text field
				$field_html = sprintf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" %5$s />', $uid, $type, $placeholder, $value, $readonly );
				break;
			case 'email':
				$field_html = sprintf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $uid, $type, $placeholder, $value );
				break;
			case 'textarea':
				$field_html = sprintf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" size="100" rows="12" cols="58">%3$s</textarea>',
					$uid, $placeholder, $value );
				break;
			case 'file':
				$field_html = '<input type="text" id="logo_url" name="' . $uid . '" value="' . $value . '" /> <input id="upload_logo_button" type="button" class="button" value="Upload Logo" />';
				break;
			case 'checkbox' :
				$field_html = '<input type="checkbox" name="' . $uid . '"  value="yes" ' . checked( $value, 'yes', false ) . '/>' . $placeholder . '</input>';
				break;
			case 'select':
				if ( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = "";
					foreach ( $arguments['options'] as $key => $label ) {
						$options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key,
							selected( $value, $key, false ), $label );
					}
					$field_html = sprintf( '<select name="%1$s" id="%1$s">%2$s</select>', $uid, $options_markup );
				}
				break;
		}

		$field_html .= '<br />';

		//If there is help text
		if ( ! empty( $arguments['desc'] ) ) {
			$helper     = $arguments['desc'];
			$field_html .= sprintf( '<span class="helper"> %s</span>', $helper ); // Show it
		}

		return $field_html;
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function es_get_all_settings() {

		global $wpdb;

		$condition                        = 'ig_es';
		$get_all_es_settings_from_options = $wpdb->prepare( "SELECT option_name, option_value
	 														FROM {$wpdb->prefix}options
	 														WHERE option_name LIKE %s", $wpdb->esc_like( $condition ) . '%' );
		$result                           = $wpdb->get_results( $get_all_es_settings_from_options, ARRAY_A );

		$settings = array();

		if ( ! empty( $result ) ) {
			foreach ( $result as $index => $data ) {
				$settings[ $data['option_name'] ] = $data['option_value'];
			}
		}

		return $settings;
	}

	function render_settings_fields( $fields ) {

		$html = "<table class='form-table'>";
		$html .= "<tbody>";

		foreach ( $fields as $field ) {
			$html .= "<tr><th scope='row'>";
			$html .= $field['name'];
			$html .= "</th>";
			$html .= "<td>";
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					$html .= ( $sub_field !== reset( $field['sub_fields'] ) ) ? '<br/>' : '';
					$html .= '<div class="es_sub_headline"><strong>' . $sub_field['name'] . '</strong></div>';
					$html .= $this->field_callback( $sub_field ) . '<br/>';
				}
			} else {
				$html .= $this->field_callback( $field );
			}

			$html .= "</td></tr>";
		}

		$html  .= "<tr><td></td><td class='es-settings-submit-btn'>";
		$html  .= '<input type="hidden" name="submitted" value="submitted"/>';
		$html  .= '<input type="hidden" name="submit_action" value="ig-es-save-admin-settings"/>';
		$nonce = wp_create_nonce( 'es-update-settings' );
		$html  .= '<input type="hidden" name="update-settings" id="ig-update-settings" value="' . $nonce . '"/>';
		$html  .= '<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">';
		$html  .= "</td></tr>";
		$html  .= "</tbody>";
		$html  .= "</table>";
		echo $html;

	}

}