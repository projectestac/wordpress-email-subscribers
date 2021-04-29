<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class es_cls_dbquery {

	public static function es_view_subscriber_group() {
		$res = ES()->lists_db->get_list_id_name_map();
		foreach ( $res as $id => $name ) {
			$list['id']             = $id;
			$list['es_email_group'] = $name;
			$es_lists[]             = $list;
		}

		return $es_lists;
	}

	public static function es_view_subscriber_ins( $data = array(), $action = 'insert' ) {

		if ( empty( $data['es_email_mail'] ) ) {
			return;
		}

		$email = trim( $data['es_email_mail'] );

		if ( ! is_email( $email ) ) {
			return;
		}

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

		$guid       = ES_Common::generate_guid();
		$sub_data   = array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'source'     => 'api',
			'status'     => 'verified',
			'hash'       => $guid,
			'created_at' => ig_get_current_date_time(),
		);
		$contact_id = ES()->contacts_db->get_contact_id_by_email( $email );

		if ( ! $contact_id ) {
			$contact_id = ES()->contacts_db->insert( $sub_data );
		}

		if ( $contact_id ) {

			$optin_type = get_option( 'ig_es_optin_type', true );
			$optin_type = ( 'double_opt_in' === $optin_type ) ? 2 : 1;

			$status = 'subscribed';

			if ( 2 == $optin_type ) {
				$status = 'unconfirmed';
			}

			$list_data         = ES()->lists_db->get_list_by_name( $data['es_email_group'] );
			$list_id           = ! empty( $list_data['id'] ) ? $list_data['id'] : 1;
			$list_ids          = array( $list_id );
			$list_contact_data = array(
				'contact_id'    => $contact_id,
				'status'        => $status,
				'optin_type'    => $optin_type,
				'subscribed_at' => ig_get_current_date_time(),
				'subscribed_ip' => '',
			);

			ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $list_id );

			$list_name = ES_Common::prepare_list_name_by_ids( $list_ids );

			// Send Email Notification
			$data = array(
				'name'       => $name,
				'first_name' => $sub_data['first_name'],
				'last_name'  => $sub_data['last_name'],
				'email'      => $email,
				'contact_id' => $contact_id,
				'guid'       => $guid,
				'list_name'  => $list_name
			);

			if ( 1 == $optin_type ) {

				// Send Welcome Email.
				ES()->mailer->send_welcome_email( $email, $data );

				$list_name     = ES()->lists_db->get_list_id_name_map( $list_id );
				$template_data = array(
					'name'       => $name,
					'first_name' => $sub_data['first_name'],
					'last_name'  => $sub_data['last_name'],
					'email'      => $email,
					'list_name'  => $list_name
				);

				ES()->mailer->send_add_new_contact_notification_to_admins( $template_data );

			} else {
				// Send Confirmation mail
				ES()->mailer->send_double_optin_email( $email, $data );
			}

		}
	}

}

class es_cls_settings {

	public static function es_setting_select() {
		return array( 'es_c_optinoption' => '' );
	}
}


