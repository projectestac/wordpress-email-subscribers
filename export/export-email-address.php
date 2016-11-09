<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

if ( !empty($_GET) && !empty($_GET['es']) && $_GET['es'] == 'export' ) {
	if ( !empty($_SERVER) && !empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
		$referer = wp_get_referer();
		$siteurl = get_option( 'siteurl' );
		if( strpos( $referer, $siteurl ) !== false ) {
			global $wpdb;
			$option = isset($_REQUEST['option']) ? $_REQUEST['option'] : '';
			switch ($option) {
				case "view_subscriber":
					$sSql = "select es_email_mail as Email, es_email_name as Name, es_email_status as Status, es_email_created as Created,";
					$sSql = $sSql . " es_email_group as Emailgroup from ". $wpdb->prefix . "es_emaillist ORDER BY es_email_mail";
					$data = $wpdb->get_results($sSql);
					es_cls_common::download($data, 's', '');
					break;
				case "registered_user":
					$data = $wpdb->get_results("select user_email as 'Email', user_nicename as 'Name' from ". $wpdb->prefix . "users ORDER BY user_nicename");
					es_cls_common::download($data, 'r', '');
					break;
				case "commentposed_user":
					$sSql = "SELECT DISTINCT(comment_author_email) as Email, comment_author as 'Name'";
					$sSql = $sSql . "from ". $wpdb->prefix . "comments WHERE comment_author_email <> '' ORDER BY comment_author_email";
					$data = $wpdb->get_results($sSql);
					es_cls_common::download($data, 'c', '');
					break;
				default:
					echo __( 'Unexpected url submit has been detected', ES_TDOMAIN );
					break;
			}
		} else {
			echo __( 'Unexpected url submit has been detected', ES_TDOMAIN );
		}
	} else {
		echo __( 'Unexpected url submit has been detected', ES_TDOMAIN );
	}
}
die();