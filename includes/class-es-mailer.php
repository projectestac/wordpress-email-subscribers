<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ES_Mailer {

	public function __construct() {

	}

	/* prepare cron email*/
	public static function prepare_and_send_email( $mails, $notification ) {

		if ( count( $mails ) <= 0 ) {
			return;
		}

		// $source      = $notification['source'];
		$content     = $notification['body'];
		$subject     = $notification['subject'];
		$guid        = $notification['hash'];
		$template_id = ES_DB_Campaigns::get_templateid_by_campaign( $notification['id'] );
		function temp_fun( $mail ) {
			return $mail['email'];
		}
		$emails = array_map( "temp_fun", $mails );

		$emails_name_map = ES_DB_Contacts::get_subsribers_email_name_map( $emails );

		foreach ( $mails as $mail ) {
			$email      = $mail['email'];
			$id         = $mail['contact_id'];
			$guid       = $mail['mailing_queue_hash'];
			$email_name = ! empty( $emails_name_map[ $email ] ) ? $emails_name_map[ $email ] : '';

			$keywords = array(
				'name'  => $email_name,
				'email' => $email,
				'guid'  => $guid,
				'dbid'  => $id
			);

			// Preparing email body
			$body = self::prepare_email_template( $content, $keywords );
			//add links

			$send = self::send( $email, $subject, $body );

			if ( $send ) {
				ES_DB_Sending_Queue::update_sent_status( $mail['id'], 'Sent' );
			}

		}

	}

	public static function prepare_email( $content = '', $data = array() ) {

		$blog_name = get_option( 'blogname' );

		$name      = isset( $data['name'] ) ? $data['name'] : '';
		$email     = isset( $data['email'] ) ? $data['email'] : '';
		$list_name = isset( $data['list_name'] ) ? $data['list_name'] : '';

		$content = str_replace( "{{NAME}}", $name, $content );
		$content = str_replace( "{{EMAIL}}", $email, $content );
		$content = str_replace( "{{GROUP}}", $list_name, $content );
		$content = str_replace( "{{LIST}}", $list_name, $content );
		$content = str_replace( "{{SITENAME}}", $blog_name, $content );


		$content = nl2br( $content );

		return $content;
	}

	public static function prepare_admin_signup_subject( $data ) {

		$content = get_option( 'ig_es_admin_new_contact_email_subject' );

		$result = self::prepare_email( $content, $data );

		return $result;
	}

	public static function prepare_admin_signup_email( $data ) {

		$content = get_option( 'ig_es_admin_new_contact_email_content' );

		$result = self::prepare_email( $content, $data );

		return $result;
	}

	public static function prepare_welcome_email_subject( $data = array() ) {

		$content = stripslashes( get_option( 'ig_es_welcome_email_subject', __( 'Welcome !', 'email-subscribers' ) ) );

		$result = self::prepare_email( $content, $data );

		return $result;
	}

	public static function prepare_welcome_email( $data ) {

		$blog_name = get_option( 'blogname' );
		$content   = stripslashes( get_option( 'ig_es_welcome_email_content', '' ) );

		$name      = isset( $data['name'] ) ? $data['name'] : '';
		$email     = isset( $data['email'] ) ? $data['email'] : '';
		$list_name = isset( $data['list_name'] ) ? $data['list_name'] : '';
		$db_id     = isset( $data['db_id'] ) ? $data['db_id'] : '';
		$guid      = isset( $data['guid'] ) ? $data['guid'] : '';

		$unsubscribe_link = self::get_unsubscribe_link( $db_id, $email, $guid );

		$content = str_replace( "{{NAME}}", $name, $content );
		$content = str_replace( "{{EMAIL}}", $email, $content );
		$content = str_replace( "{{SITENAME}}", $blog_name, $content );
		$content = str_replace( "{{GROUP}}", $list_name, $content );
		$content = str_replace( "{{LIST}}", $list_name, $content );
		$content = str_replace( "{{UNSUBSCRIBE-LINK}}", $unsubscribe_link, $content );
		$content = str_replace( "{{LINK}}", $unsubscribe_link, $content );
		$content = nl2br( $content );

		return $content;

	}

	public static function prepare_double_optin_email( $data ) {

		$blog_name      = get_option( 'blogname' );
		$content        = stripslashes( get_option( 'ig_es_confirmation_mail_content', '' ) );
		$subscribe_link = get_option( 'ig_es_optin_link', true );

		$db_id = isset( $data['db_id'] ) ? $data['db_id'] : '';
		$guid  = isset( $data['guid'] ) ? $data['guid'] : '';
		$email = isset( $data['email'] ) ? $data['email'] : '';
		$name  = isset( $data['name'] ) ? $data['name'] : '';

		$subscribe_link = str_replace( "{{DBID}}", $db_id, $subscribe_link );
		$subscribe_link = str_replace( "{{GUID}}", $guid, $subscribe_link );
		$subscribe_link = str_replace( "{{EMAIL}}", $email, $subscribe_link );

		$content = str_replace( "{{NAME}}", $name, $content );
		$content = str_replace( "{{EMAIL}}", $email, $content );
		$content = str_replace( "{{LINK}}", $subscribe_link, $content );
		$content = str_replace( "{{SITENAME}}", $blog_name, $content );
		$content = str_replace( "{{SUBSCRIBE-LINK}}", $subscribe_link, $content );

		$content = nl2br( $content );

		return $content;

	}

	public static function prepare_email_template( $template_content, $keywords, $template_id = 0 ) {

		$name  = isset( $keywords['name'] ) ? $keywords['name'] : '';
		$email = isset( $keywords['email'] ) ? $keywords['email'] : '';

		$template_content = str_replace( "{{NAME}}", $name, $template_content );
		$template_content = str_replace( "{{EMAIL}}", $email, $template_content );

		$template_content = convert_chars( convert_smilies( wptexturize( $template_content ) ) );
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			$template_content = $GLOBALS['wp_embed']->autoembed( $template_content );
		}
		$template_content = wpautop( $template_content );

		$template_content = do_shortcode( shortcode_unautop( $template_content ) );
		$data['content']  = $template_content;
		$data['tmpl_id']  = $template_id;
		$data             = apply_filters( 'es_after_process_template_body', $data );
		$template_content = $data['content'];

		$dbid  = $keywords['dbid'];
		$guid  = $keywords['guid'];
		$email = $keywords['email'];

		$unsubscribe_link = self::get_unsubscribe_link( $dbid, $email, $guid );
		$unsubtext        = self::get_unsubscribe_text( $unsubscribe_link );

		$viewstslink = self::get_view_tracking_image( $guid, $email );

		$template_content = $template_content . $unsubtext . $viewstslink;

		return $template_content;
	}

	public static function get_unsubscribe_link( $dbid, $email, $guid ) {
		$home_url  = home_url( '/' );
		$unsublink = $home_url . "?es=unsubscribe&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}";
		$unsublink = str_replace( "{{DBID}}", $dbid, $unsublink );
		$unsublink = str_replace( "{{EMAIL}}", $email, $unsublink );
		$unsublink = str_replace( "{{GUID}}", $guid, $unsublink );

		return $unsublink;
	}

	public static function get_unsubscribe_text( $unsublink ) {

		$unsubtext = get_option( 'ig_es_unsubscribe_link_content', '' );
		$unsubtext = stripslashes( $unsubtext );
		$unsubtext = str_replace( "{{LINK}}", $unsublink, $unsubtext );
		$unsubtext = str_replace( "{{UNSUBSCRIBE-LINK}}", $unsublink, $unsubtext );

		return $unsubtext;
	}

	public static function get_view_tracking_image( $guid, $email ) {

		$url             = home_url( '/' );
		$viewstatus      = '<img src="' . $url . '?es=viewstatus&guid={{GUID}}&email={{EMAIL}}" width="1" height="1" />';
		$viewstatus_link = str_replace( "{{GUID}}", $guid, $viewstatus );
		$viewstatus_link = str_replace( "{{EMAIL}}", $email, $viewstatus_link );

		return $viewstatus_link;
	}

	public static function prepare_unsubscribe_email() {
		$content = get_option( 'ig_es_unsubscribe_success_message' );

		return $content;
	}

	public static function prepare_subscribe_email() {
		$content = get_option( 'ig_es_subscription_success_message' );

		return $content;
	}

	public static function prepare_es_cron_admin_email( $notification_guid ) {

		$notification = ES_DB_Mailing_Queue::get_notification_by_hash( $notification_guid );

		$template = '';

		if ( isset( $notification['subject'] ) ) {
			$email_count  = $notification['count'];
			$post_subject = $notification['subject'];
			$cron_date    = date( 'Y-m-d h:i:s' );

			$template = get_option( 'ig_es_cron_admin_email' );

			$template = str_replace( '{{DATE}}', $cron_date, $template );
			$template = str_replace( '{{COUNT}}', $email_count, $template );
			$template = str_replace( '{{SUBJECT}}', $post_subject, $template );

			$template = nl2br($template);
		}

		return $template;
	}

	public static function send( $to_email, $subject, $email_template ) {

		$get_email_type = get_option( 'ig_es_email_type', true );
		$site_title     = get_bloginfo();
		$admin_email    = get_option( 'admin_email' );

		//adding missing header
		$from_name  = get_option( 'ig_es_from_name', true );
		$from_email = get_option( 'ig_es_from_email', true );

		$sender_email = ! empty( $from_email ) ? $from_email : $admin_email;
		$sender_name  = ! empty( $from_name ) ? $from_name : $site_title;

		$headers = array(
			"From: \"$sender_name\" <$sender_email>",
			"Reply-To: \"$sender_name\" <$sender_email>",
			"Return-Path: <$sender_email>",
			"MIME-Version: 1.0",
			"X-Mailer: PHP" . phpversion()
		);

		if ( in_array( $get_email_type, array( 'wp_html_mail', 'php_html_mail' ) ) ) {
			$headers[] = "Content-Type: text/html; charset=\"" . get_bloginfo( 'charset' );
		} else {
			$headers[]      = "Content-Type: text/plain; charset=\"" . get_bloginfo( 'charset' );
			$email_template = str_replace( "<br />", "\r\n", $email_template );
			$email_template = str_replace( "<br>", "\r\n", $email_template );
			$email_template = strip_tags( $email_template );
		}

		$headers = implode( "\n", $headers );

		if ( in_array( $get_email_type, array( 'wp_plaintext_mail', 'wp_html_mail' ) ) ) {
			$email_response = wp_mail( $to_email, $subject, $email_template, $headers );
		} else {
			$email_response = mail( $to_email, $subject, $email_template, $headers );
		}

		return $email_response;

	}
}