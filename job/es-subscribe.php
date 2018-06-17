<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class es_cls_job_subscribe {

	public function __construct() {
		add_action( 'wp_ajax_es_add_subscriber', array( $this, 'es_add_subscriber' ) );
		add_action( 'wp_ajax_nopriv_es_add_subscriber', array( $this, 'es_add_subscriber' ) );
	}

	public function es_add_subscriber() {

		// check_admin_referer( 'es-subscribe', 'es-subscribe' );

		$es_response = array();

		if ( ( isset( $_REQUEST['es'] ) ) && ( $_REQUEST['es'] == 'subscribe' ) && ( isset( $_REQUEST['action'] ) ) && ( $_REQUEST['action'] == 'es_add_subscriber' ) && !empty( $_REQUEST['esfpx_es-subscribe'] ) ) {
			$es_subscriber_name = '';
			$es_subscriber_email = '';
			$es_subscriber_group = '';

			foreach ($_REQUEST as $key => $value) {
				$new_key = str_replace('_pg', '', $key);
				$_REQUEST[$new_key] = $value;
			}

			$es_subscriber_name  = isset( $_REQUEST['esfpx_es_txt_name'] ) ? $_REQUEST['esfpx_es_txt_name'] : '';
			$es_subscriber_email = isset( $_REQUEST['esfpx_es_txt_email'] ) ? $_REQUEST['esfpx_es_txt_email'] : '';
			$es_subscriber_group = isset( $_REQUEST['esfpx_es_txt_group'] ) ? $_REQUEST['esfpx_es_txt_group'] : '';
			$es_nonce 			 = $_REQUEST['esfpx_es-subscribe'];

			$es_subscriber_name  = trim( $es_subscriber_name );
			$es_subscriber_email = trim( $es_subscriber_email );
			$es_subscriber_group = trim( $es_subscriber_group );

			$subscriber_form = array(
									'es_email_name' => '',
									'es_email_mail' => '',
									'es_email_group' => '',
									'es_email_status' => '',
									'es_nonce' => ''
								);

			if( $es_subscriber_group == '' ) {
				$es_subscriber_group = 'Public';
			}

			if ( $es_subscriber_email != '' ) {
				if ( !filter_var( $es_subscriber_email, FILTER_VALIDATE_EMAIL ) ) {
					$es_response['error'] = 'invalid-email';
				} else {
					$action = '';
					global $wpdb;

					$subscriber_form['es_email_name'] = $es_subscriber_name;
					$subscriber_form['es_email_mail'] = $es_subscriber_email;
					$subscriber_form['es_email_group'] = $es_subscriber_group;
					$subscriber_form['es_nonce'] = $es_nonce;

					$es_optintype = get_option( 'ig_es_optintype' );

					if( $es_optintype == "Double Opt In" ) {
						$subscriber_form['es_email_status'] = "Unconfirmed";
					} else {
						$subscriber_form['es_email_status'] = "Single Opt In";
					}

					$action = es_cls_dbquery::es_view_subscriber_widget($subscriber_form);
					if( $action == "sus" ) {
						$subscribers = array();
						$subscribers = es_cls_dbquery::es_view_subscriber_one($es_subscriber_email,$es_subscriber_group);
						if( $es_optintype == "Double Opt In" ) {
							es_cls_sendmail::es_sendmail("optin", $template = 0, $subscribers, "optin", 0);
							$es_response['success'] = 'subscribed-pending-doubleoptin';
						} else {
							$es_c_usermailoption = get_option( 'ig_es_welcomeemail' );
							if ( $es_c_usermailoption == "YES" ) {
								es_cls_sendmail::es_sendmail("welcome", $template = 0, $subscribers, "welcome", 0);
							}
							$es_response['success'] = 'subscribed-successfully';
						} 
					} elseif( $action == "ext" ) {
						$es_response['success'] = 'already-exist';
					} elseif( $action == "invalid" ) {
						$es_response['error'] = 'invalid-email';
					}
				}
			} else {
				$es_response['error'] = 'no-email-address';
			}
		} else {
			$es_response['error'] = 'unexpected-error';
		}

		echo json_encode($es_response);
		die();
	}
}

new es_cls_job_subscribe();