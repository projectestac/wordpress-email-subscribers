<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'You are not allowed to call this page directly.' );
}

if( ( isset($_GET['es']) ) && $_GET['es'] == "viewstatus" ) {
	$form = array();
	$form['delvid'] = isset($_GET['delvid']) ? $_GET['delvid'] : 0;
	if(is_numeric($form['delvid'])) {
		es_cls_delivery::es_delivery_ups($form['delvid']);
	}
}
die();