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
class ES_Newsletters {

	// class instance
	static $instance;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_init', array( $this, 'setup_sections' ) );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function es_newsletters_settings_callback() {

		$this->email_subscribers_settings_fields();

		$submitted = Email_Subscribers::get_request( 'submitted' );


		if ( 'submitted' === $submitted ) {

			// $email_sent_type = __('Active', 'email-subscribers');
			$list_id     = Email_Subscribers::get_request( 'list_ids' );
			$template_id = Email_Subscribers::get_request( 'base_template_id' );

			if ( empty( $template_id ) ) {
				$message = __( 'Please select template.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			} elseif ( empty( $list_id ) ) {
				$message = __( 'Please select list.', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			}

			$data = array(
				'base_template_id' => $template_id,
				'list_ids'         => $list_id,
				'status'           => 1
			);

			self::es_send_email_callback( $data );

			$reports_url = admin_url('admin.php?page=es_reports');
			$message  = __( sprintf( 'A new broadcast has been created successfully! Contacts from selected list will be notified within an hour. Want to notify now? <a href="%s" target="_blank">Click here</a>', $reports_url ), 'email-subscribers' );

			$this->show_message( $message, 'success' );
		}

		$this->prepare_newsletter_settings_form();
	}

	public function prepare_newsletter_settings_form() {

		?>

        <div class="wrap">
            <h2 class="wp-heading-inline"><?php _e( 'Campaigns > Broadcast', 'email-subscribers' ); ?>
                <a href="admin.php?page=es_campaigns" class="page-title-action"><?php _e( 'Campaigns', 'email-subscribers' ) ?></a>
                <a href="edit.php?post_type=es_template" class="page-title-action es-imp-button"><?php _e( 'Manage Templates', 'email-subscribers' ) ?></a>
            </h2>
            <div class="es-form" style="width: 80%;display:inline;float:left">
                <form method="post" action="#">
					<?php settings_fields( 'es_newsletters_settings' ); ?>
					<?php do_settings_sections( 'newsletters_settings' ); ?>
                    <div class="email-newsletters">
                        <input type="submit" id="" name="es_send_email" value="Send Broadcast" class="button button-primary">
                        <input type="hidden" name="submitted" value="submitted">
                    </div>
                </form>
            </div>
            <div clas="es-preview" style="float: right;width: 19%;">
                <div class="es-templ-img"></div>
            </div>
        </div>

		<?php

	}

	public function setup_sections() {
		add_settings_section( 'newsletters_settings', '', array( $this, 'email_subscribers_settings_callback' ), 'newsletters_settings' );
	}

	public function email_subscribers_settings_callback( $arguments ) {

		?>
        <!--<div class="email-newsletters">
            <input type="button" id="es_send_email" name="es_send_email" value="Send Email" class="button button-primary">
        </div>-->
		<?php

	}

	public function email_subscribers_settings_fields() {

		$templates = ES_Common::prepare_templates_dropdown_options( 'newsletter' );
		// $sent_types = ES_Common::prepare_notification_send_type_dropdown_options();
		$groups = ES_Common::prepare_list_dropdown_options();

		$fields = array(
			array(
				'uid'          => 'base_template_id',
				'label'        => __( 'Select Template', 'email-subscribers' ),
				'section'      => 'newsletters_settings',
				'type'         => 'select',
				'options'      => $templates,
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => __( 'Content of the selected template will be broadcasted.', 'email-subscribers' ),
				'default'      => ''
			),

			array(
				'uid'          => 'list_ids',
				'label'        => __( 'Select List', 'email-subscribers' ),
				'section'      => 'newsletters_settings',
				'type'         => 'select',
				'options'      => $groups,
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => __( 'Contacts from the selected list will be notified.', 'email-subscribers' ),
				'default'      => ''
			),
		);
		$fields = apply_filters( 'email_newsletter_settings_fields', $fields );
		foreach ( $fields as $field ) {
			add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), $field['section'], $field['section'], $field );
			register_setting( 'es_newsletters_settings', $field['uid'] );
		}

	}

	public function field_callback( $arguments ) {
		$value = get_option( $arguments['uid'] ); // Get the current value, if there is one
		if ( ! $value ) { // If no value exists
			$value = $arguments['default']; // Set to our default
		}

		// Check which type of field we want
		switch ( $arguments['type'] ) {
			case 'text': // If it is a text field
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
				break;
			case 'email':
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
				break;
			case 'textarea':
				printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
				break;
			case 'file':
				echo '<input type="text" id="logo_url" name="' . $arguments['uid'] . '" value="' . $value . '" />
				<input id="upload_logo_button" type="button" class="button" value="Upload Logo" />';
				break;
			case 'select':
				if ( ! empty ( $arguments['options'] ) ) {
					printf( '<select name="%1$s" id="%1$s">%2$s</select>', $arguments['uid'], $arguments['options'] );
				}
				break;
		}

		// If there is help text
		if ( $helper = $arguments['helper'] ) {
			printf( '<span class="helper"> %s</span>', $helper ); // Show it
		}

		// If there is supplemental text
		if ( $supplimental = $arguments['supplemental'] ) {
			printf( '<p class="description">%s</p>', $supplimental ); // Show it
		}
	}

	public static function es_send_email_callback( $data ) {
		global $wpdb;

		$template_id = ! empty( $data['base_template_id'] ) ? $data['base_template_id'] : '';
		$send_type   = ! empty( $data['status'] ) ? $data['status'] : '';
		$list_id     = ! empty( $data['list_ids'] ) ? $data['list_ids'] : '';

		$data['type'] = 'newsletter';
		$data['name'] = get_the_title( $template_id );
		$data['slug'] = sanitize_title( $data['name'] );

		if ( ! empty( $template_id ) ) {

			$campaign_id = ES_DB_Campaigns::save_campaign( $data );

			$post_temp_arr = get_post( $template_id );

			if ( is_object( $post_temp_arr ) ) {

				$post_subject          = ! empty( $post_temp_arr->post_title ) ? $post_temp_arr->post_title : '';
				$post_template_content = ! empty( $post_temp_arr->post_content ) ? $post_temp_arr->post_content : '';
				$post_template_content = ES_Common::es_process_template_body( $post_template_content, $template_id );

				$subscribers = ES_DB_Contacts::get_active_subscribers_by_list_id( $list_id );
				if ( ! empty( $subscribers ) && count( $subscribers ) > 0 ) {
					$guid = ES_Common::generate_guid( 6 );
					$data = array(
						'hash'        => $guid,
						'campaign_id' => $campaign_id,
						'subject'     => $post_subject,
						'body'        => $post_template_content,
						'count'       => count( $subscribers ),
						'status'      => 'In Queue',
						'start_at'    => '',
						'finish_at'   => '',
						'created_at'  => ig_get_current_date_time(),
						'updated_at'  => ig_get_current_date_time()
					);

					$last_report_id = ES_DB_Mailing_Queue::add_notification( $data );

					$delivery_data                     = array();
					$delivery_data['hash']             = $guid;
					$delivery_data['subscribers']      = $subscribers;
					$delivery_data['campaign_id']      = $campaign_id;
					$delivery_data['mailing_queue_id'] = $last_report_id;
					ES_DB_Sending_Queue::do_batch_insert( $delivery_data );
				}
			}
		}

		return;

	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

add_action( 'plugins_loaded', function () {
	ES_Newsletters::get_instance();
} );