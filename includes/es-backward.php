<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class es_cls_dbquery {

	public static function es_view_subscriber_group() {
		$res = ES_DB_Lists::get_list_id_name_map();
		foreach ( $res as $id => $name ) {
			$list['id']             = $id;
			$list['es_email_group'] = $name;
			$es_lists[]             = $list;
		}

		return $es_lists;
	}

	public static function es_view_subscriber_ins( $data = array(), $action = "insert" ) {

		if ( empty( $data['es_email_mail'] ) ) {
			return;
		}

		$email     = trim( $data['es_email_mail'] );
		$name      = trim( $data['es_email_name'] );
		$last_name = '';
		if ( ! empty( $name ) ) {
			$name_parts = ES_Common::prepare_first_name_last_name( $name );
			$first_name = $name_parts['first_name'];
			$last_name  = $name_parts['last_name'];
		} else {
			$first_name = ES_Common::get_name_from_email( $email );
			$name       = $first_name;
		}

		$guid     = ES_Common::generate_guid();
		$sub_data = array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'source'     => 'api',
			'status'     => 'verified',
			'hash'       => $guid,
			'created_at' => ig_get_current_date_time(),
		);

		$contact_id = ES_DB_Contacts::add_subscriber( $sub_data );

		if ( $contact_id ) {

			$optin_type = get_option( 'ig_es_optin_type', true );
			$optin_type = ( $optin_type === 'double_opt_in' ) ? 2 : 1;

			$status = 'subscribed';
			if ( $optin_type == 2 ) {
				$status = 'unconfirmed';
			}

			$list_data         = ES_DB_Lists::get_list_by_name( $data['es_email_group'] );
			$list_id           = ! empty( $list_data['id'] ) ? $list_data['id'] : 1;
			$list_ids          = array( $list_id );
			$list_contact_data = array(
				'list_id'       => $list_ids,
				'contact_id'    => $contact_id,
				'status'        => $status,
				'optin_type'    => $optin_type,
				'subscribed_at' => ig_get_current_date_time(),
				'subscribed_ip' => ES_Subscription_Throttaling::getUserIP()
			);

			ES_DB_Lists_Contacts::delete_list_contacts( $contact_id, $list_ids );
			ES_DB_Lists_Contacts::add_lists_contacts( $list_contact_data );

			// Send Email Notification
			$data = array(
				'name'  => $name,
				'email' => $email,
				'db_id' => $contact_id,
				'guid'  => $guid
			);

			if ( $optin_type == 1 ) {

				// Send Welcome Email
				$enable_welcome_email = get_option( 'ig_es_enable_welcome_email', 'no' );

				if ( $enable_welcome_email === 'yes' ) {
					$content = ES_Mailer::prepare_welcome_email( $data );
					$subject = ES_Mailer::prepare_welcome_email_subject( $data );
					ES_Mailer::send( $email, $subject, $content );
				}

			} else {

				// Send Confirmation mail
				$subject = get_option( 'ig_es_confirmation_mail_subject', __( 'Confirm Your Subscription!', 'email-subscribers' ) );
				$content = ES_Mailer::prepare_double_optin_email( $data );

				ES_Mailer::send( $email, $subject, $content );
			}


			$list_name     = ES_DB_Lists::get_list_id_name_map( $list_id );
			$template_data = array(
				'name'      => $name,
				'email'     => $email,
				'list_name' => $list_name
			);

			ES_Common::send_signup_notification_to_admins( $template_data );

		}
	}

}

class es_cls_settings {

	public static function es_setting_select() {
		return array( 'es_c_optinoption' => '' );
	}
}

?>