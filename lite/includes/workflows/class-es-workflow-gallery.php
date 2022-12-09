<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Workflow_Gallery' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Admin Settings
	 *
	 * @package    Email_Subscribers
	 * @subpackage Email_Subscribers/admin
	 */
	class ES_Workflow_Gallery {

		// class instance
		public static $instance;

		// class constructor
		public function __construct() {
			self::init();
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
	
			return self::$instance;
		}

		public static function init() {
			self::register_hooks();
		}
	
		public static function register_hooks() {
			add_filter( 'ig_es_workflow_gallery', array( __CLASS__, 'add_workflow_gallery' ) );
			add_filter( 'ig_es_workflow_gallery', array( __CLASS__, 'filter_workflow_gallery_items' ), 99 );
		}

		public static function add_workflow_gallery( $gallery = array() ) {

			$item_files_path = untrailingslashit( ES_PLUGIN_DIR . 'lite/includes/workflows/gallery-items/*.php' );

			$lite_gallery = self::get_gallery_from_folder( $item_files_path );
			
			if ( ! empty( $lite_gallery ) ) {
				$gallery = array_merge( $gallery, $lite_gallery );
			}

			return $gallery;
		}

		public static function get_gallery_from_folder( $item_path = '' ) {

			global $ig_es_tracker;

			$gallery = array();
			if ( ! empty( $item_path ) ) {
				$item_files = glob( $item_path );
	
				if ( is_array( $item_files ) && count( $item_files ) ) {
	
					foreach ( $item_files as $file ) {
						if ( is_file( $file ) && is_admin() ) {
							$file_name = basename( $file, '.php' );
	
							$item_data = include $file;
	
							if ( ! empty( $item_data['required_plugins'] )) {
	
								$required_plugins = $item_data['required_plugins'];
	
								$required_plugins_active = true;
	
								foreach ( $required_plugins as $required_plugin ) {
									if ( ! $ig_es_tracker::is_plugin_activated( $required_plugin ) ) {
										$required_plugins_active = false;
										break;
									}
								}
	
								if ( ! $required_plugins_active ) {
									continue;
								}
	
								$gallery[ $file_name ] = $item_data;
							} else {
								$gallery[ $file_name ] = $item_data;
							}
						}
					}
				}
			}

			return $gallery;
		}

		public static function get_workflow_gallery_items() {
			$workflow_gallery = apply_filters( 'ig_es_workflow_gallery', array() );
			return $workflow_gallery;
		}

		public static function filter_workflow_gallery_items( $gallery_items = array() ) {
			$integration_plugins = ig_es_get_request_data( 'integration-plugins');
			if ( ! empty( $integration_plugins ) && ! empty( $gallery_items ) ) {
				$integration_plugins = explode( ',', $integration_plugins );
				foreach ( $gallery_items as $item_index => $gallery_item ) {
					$required_plugins = ! empty( $gallery_item['required_plugins'] ) ? $gallery_item['required_plugins'] : array();
					$is_integration_plugin_active = ! empty( array_intersect( $required_plugins, $integration_plugins ) ); 
					if ( ! $is_integration_plugin_active ) {
						unset( $gallery_items[ $item_index ] );
					}
				}
			}
			return $gallery_items;
		}
	}
}
