<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Notifications {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	/**
	 */
	public static function get_notifications_by_post_id( $post_id = 0 ) {

		global $wpdb;

		$arrNotification = array();

		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
			$sSql      = "SELECT * FROM " . IG_CAMPAIGNS_TABLE . " WHERE status = 1 AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') AND type = 'post_notification'";
			if ( $post_type == "post" ) {
				$categories       = get_the_category( $post_id );
				$total_categories = count( $categories );
				if ( $total_categories > 0 ) {
					for ( $i = 0; $i < $total_categories; $i ++ ) {
						if ( $i == 0 ) {
							$sSql .= " and (";
						} else {
							$sSql .= " or";
						}

						$category_str = ES_Common::prepare_category_string( $categories[ $i ]->cat_name );
						$sSql         .= " categories LIKE '%" . wp_specialchars_decode( addslashes( $category_str ) ) . "%'";
						if ( $i == ( $total_categories - 1 ) ) {
							$sSql .= ")";
						}
					}
				}
			} else {
				$post_type = ES_Common::prepare_custom_post_type_string( $post_type );
				$sSql      .= " and categories LIKE '%" . wp_specialchars_decode( addslashes( $post_type ) ) . "%'";
			}

			$arrNotification = $wpdb->get_results( $sSql, ARRAY_A );
		}


		return $arrNotification;

	}

	public static function migratate_post_notification_es_template_type() {
		global $wpdb;

		$sql    = "UPDATE {$wpdb->prefix}postmeta SET meta_value = %s WHERE meta_key = %s AND meta_value = %s";
		$query  = $wpdb->prepare( $sql, array( 'post_notification', 'es_template_type', 'Post Notification' ) );
		$update = $wpdb->query( $query );

		return $update;
	}

}
