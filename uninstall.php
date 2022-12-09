<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

include_once 'lite/includes/class-email-subscribers-uninstall.php';

$delete_setting = get_option( 'ig_es_delete_plugin_data', 'no' );

if ( 'yes' === $delete_setting ) {
	
	// Delete data
	Email_Subscribers_Uninstall::delete_plugin_data_on_uninstall();
}

