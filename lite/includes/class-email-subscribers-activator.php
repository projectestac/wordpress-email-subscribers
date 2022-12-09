<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 */
class Email_Subscribers_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    4.0
	 */
	public static function activate() {
		require_once dirname( __FILE__ ) . '/class-es-install.php';
		ES_Install::install();
		/**
		 * Do all plugin related stuff in plugin activation
		 *
		 * @since 4.8.0
		 */
		do_action( 'ig_es_plugin_activate' );
	}

	public static function register_email_templates() {
		$labels = array(
			'name'               => __( 'Templates', 'email-subscribers' ),
			'singular_name'      => __( 'Templates', 'email-subscribers' ),
			'add_new'            => __( 'Add New Template', 'email-subscribers' ),
			'add_new_item'       => __( 'Add New Template', 'email-subscribers' ),
			'edit_item'          => __( 'Edit Templates', 'email-subscribers' ),
			'new_item'           => __( 'New Templates', 'email-subscribers' ),
			'all_items'          => __( 'Templates', 'email-subscribers' ),
			'view_item'          => __( 'View Templates', 'email-subscribers' ),
			'search_items'       => __( 'Search Templates', 'email-subscribers' ),
			'not_found'          => __( 'No Templates found', 'email-subscribers' ),
			'not_found_in_trash' => __( 'No Templates found in Trash', 'email-subscribers' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Icegram Express', 'email-subscribers' ),
			'featured_image'     => __( 'Thumbnail (For Visual Representation only)', 'email-subscribers' ),
			'set_featured_image' => __( 'Set thumbnail', 'email-subscribers' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'es_template' ),
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'es_template', $args );
	}


}

