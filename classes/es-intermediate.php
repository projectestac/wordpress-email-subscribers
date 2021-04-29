<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class es_cls_intermediate {
	public static function es_subscribers() {
		global $wpdb;
		$current_page = isset($_GET['ac']) ? $_GET['ac'] : '';
		switch($current_page) {
			case 'add':
				require_once(ES_DIR.'subscribers'.DIRECTORY_SEPARATOR.'view-subscriber-add.php');
				break;
			case 'edit':
				require_once(ES_DIR.'subscribers'.DIRECTORY_SEPARATOR.'view-subscriber-edit.php');
				break;
			case 'export':
				require_once(ES_DIR.'subscribers'.DIRECTORY_SEPARATOR.'view-subscriber-export.php');
				break;
			case 'import':
				require_once(ES_DIR.'subscribers'.DIRECTORY_SEPARATOR.'view-subscriber-import.php');
				break;
			case 'sync':
				require_once(ES_DIR.'subscribers'.DIRECTORY_SEPARATOR.'view-subscriber-sync.php');
				break;
			default:
				require_once(ES_DIR.'subscribers'.DIRECTORY_SEPARATOR.'view-subscriber-show.php');
				break;
		}
	}

	public static function es_notification() {
		$current_page = isset($_GET['ac']) ? $_GET['ac'] : '';
		switch($current_page) {
			case 'add':
				require_once(ES_DIR.'notification'.DIRECTORY_SEPARATOR.'notification-add.php');
				break;
			case 'edit':
				require_once(ES_DIR.'notification'.DIRECTORY_SEPARATOR.'notification-edit.php');
				break;
			case 'preview':
				require_once(ES_DIR.'templates'.DIRECTORY_SEPARATOR.'template-preview.php');
				break;
			default:
				require_once(ES_DIR.'notification'.DIRECTORY_SEPARATOR.'notification-show.php');
				break;
		}
	}

	public static function es_sendemail() {
		$current_page = isset($_GET['ac']) ? $_GET['ac'] : '';
		if($current_page && $current_page == 'preview'){
			require_once(ES_DIR.'templates'.DIRECTORY_SEPARATOR.'template-preview.php');
				return;
		}
		require_once(ES_DIR.'sendmail'.DIRECTORY_SEPARATOR.'sendmail.php');
	}

	public static function es_settings() {
		$current_page = isset($_GET['ac']) ? $_GET['ac'] : '';
		switch($current_page) {
			case 'sync':
				require_once(ES_DIR.'settings'.DIRECTORY_SEPARATOR.'setting-sync.php');
				break;
			default:
				require_once(ES_DIR.'settings'.DIRECTORY_SEPARATOR.'settings-edit.php');
				break;
		}
	}

	public static function es_sentmail() {
		$current_page = isset($_GET['ac']) ? $_GET['ac'] : '';
		switch($current_page) {
			case 'delivery':
				require_once(ES_DIR.'sentmail'.DIRECTORY_SEPARATOR.'deliverreport-show.php');
				break;
			case 'preview':
				require_once(ES_DIR.'sentmail'.DIRECTORY_SEPARATOR.'sentmail-preview.php');
				break;
			default:
				require_once(ES_DIR.'sentmail'.DIRECTORY_SEPARATOR.'sentmail-show.php');
				break;
		}
	}	

	public static function es_information() {
		require_once(ES_DIR.'help'.DIRECTORY_SEPARATOR.'help.php');
	}
}