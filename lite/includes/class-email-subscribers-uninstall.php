<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's uninstall.
 *
 * @since      4.7.5
 * @package    Email_Subscribers
 */
class Email_Subscribers_Uninstall {

	/**
	 * Delete all tables of the plugin
	 *
	 * @since 4.7.5
	 */
	public static function delete_all_es_tables() {
		global $wpdb, $wpbd;

		$wpbd = $wpdb;

		$tables_to_delete = array(
			$wpbd->prefix . 'ig_blocked_emails',
			$wpbd->prefix . 'ig_campaigns',
			$wpbd->prefix . 'ig_contacts',
			$wpbd->prefix . 'ig_contacts_ips',
			$wpbd->prefix . 'ig_contact_meta',
			$wpbd->prefix . 'ig_es_temp_import',
			$wpbd->prefix . 'ig_temp_import',
			$wpbd->prefix . 'ig_forms',
			$wpbd->prefix . 'ig_lists',
			$wpbd->prefix . 'ig_lists_contacts',
			$wpbd->prefix . 'ig_mailing_queue',
			$wpbd->prefix . 'ig_sending_queue',
			$wpbd->prefix . 'ig_queue',
			$wpbd->prefix . 'ig_actions',
			$wpbd->prefix . 'ig_links',
			$wpbd->prefix . 'ig_workflows',
			$wpbd->prefix . 'ig_workflows_queue',
			$wpbd->prefix . 'ig_unsubscribe_feedback',
			$wpbd->prefix . 'ig_wc_cart',
			$wpbd->prefix . 'ig_wc_guests',
		);

		foreach ( $tables_to_delete as $table ) {
			$wpbd->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	/**
	 * Delete plugin data
	 *
	 * @since 4.7.5
	 */
	public static function delete_plugin_data_on_uninstall() {
		global $wpdb;

		// Delete all tables
		self::delete_all_es_tables();

		$sidebars_widgets = maybe_unserialize( get_option( 'sidebars_widgets', array() ) );
		$option_name      = $wpdb->esc_like( 'ig_es_' ) . '%';
		$post_type        = 'es_template';

		// Remove ES widgets from Sidebars
		foreach ( $sidebars_widgets as $sidebar_index => $sidebar ) {
			if ( is_array( $sidebar ) ) {
				foreach ( $sidebar as $index => $widget_id ) {
					if ( strpos( $widget_id, 'email-subscribers-form' ) !== false ) {
						unset( $sidebars_widgets[ $sidebar_index ][ $index ] );
					}
				}
			}
		}

		update_option( 'sidebars_widgets', $sidebars_widgets );
		delete_option( 'widget_email-subscribers-form' );

		// Delete postmeta data
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}postmeta where post_id IN ( SELECT ID from {$wpdb->prefix}posts where post_type  = %s)", $post_type ) );

		// Delete all templates of ES
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}posts where post_type  = %s", $post_type ) );

		// Delete all options from options table with prefix ig_es
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", $option_name ) );
	}

}
