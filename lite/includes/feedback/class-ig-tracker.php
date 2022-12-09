<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'IG_Tracker_V_1_2_6' ) ) {

	/**
	 * Class IG_Tracker_V_1_2_6
	 *
	 * Icegram tracker handler class is responsible for sending anonymous plugin
	 * data to Icegram servers for users that actively allowed data tracking.
	 *
	 * @class       IG_Tracker_V_1_2_6
	 * @since       1.0.0
	 *
	 * @package     feedback
	 */
	class IG_Tracker_V_1_2_6 {

		/**
		 * Get Active, Inactive or all plugins info
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_plugins( $status = 'all', $details = false ) {

			$plugins = array(
				'active_plugins'   => array(),
				'inactive_plugins' => array(),
			);

			// Check if get_plugins() function exists. This is required on the front end of the
			// site, since it is in a file that is normally only loaded in the admin.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins    = get_plugins();
			$active_plugins = get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$sitewide_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
				$active_plugins             = ! empty( $active_plugins ) ? array_merge( $sitewide_activated_plugins, $active_plugins ) : $sitewide_activated_plugins;
			}

			foreach ( $all_plugins as $plugin_path => $plugin ) {
				// If the plugin isn't active, don't show it.
				if ( in_array( $plugin_path, $active_plugins ) ) {
					$slug      = 'active_plugins';
					$is_active = 1;
				} else {
					$slug      = 'inactive_plugins';
					$is_active = 0;
				}

				if ( $details ) {

					$plugin_data = array(
						'name'       => $plugin['Name'],
						'version'    => $plugin['Version'],
						'author'     => $plugin['Author'],
						'author_uri' => $plugin['AuthorURI'],
						'plugin_uri' => $plugin['PluginURI'],
						'is_active'  => $is_active,
					);

					$plugins[ $slug ][ $plugin_path ] = $plugin_data;
				} else {
					$plugins[ $slug ][] = $plugin_path;
				}
			}

			if ( 'active' === $status ) {
				return $plugins['active_plugins'];
			} elseif ( 'inactive' === $status ) {
				return $plugins['inactive_plugins'];
			} else {
				return array_merge( $plugins['active_plugins'], $plugins['inactive_plugins'] );
			}
		}

		/**
		 * Get Active Plugins
		 *
		 * @param bool $details
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_active_plugins( $details = false ) {
			return self::get_plugins( 'active', $details );
		}

		/**
		 * Get Inactive plugins
		 *
		 * @param bool $details
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_inactive_plugins( $details = false ) {
			return self::get_plugins( 'inactive', $details );
		}

		/**
		 * Check whether plugin is active or not.
		 *
		 * @param string $plugin
		 *
		 * @return bool
		 *
		 * @since 1.2.2
		 */
		public static function is_plugin_activated( $plugin = '' ) {
			if ( empty( $plugin ) ) {
				return false;
			}

			$active_plugins = self::get_active_plugins();

			if ( count( $active_plugins ) == 0 ) {
				return false;
			}

			if ( in_array( $plugin, $active_plugins ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Is plugin installed?
		 *
		 * @param string $plugin
		 *
		 * @return bool
		 *
		 * @since 1.2.2
		 */
		public static function is_plugin_installed( $plugin = '' ) {
			if ( empty( $plugin ) ) {
				return false;
			}

			$all_plugins = self::get_plugins();

			if ( count( $all_plugins ) == 0 ) {
				return false;
			}

			if ( in_array( $plugin, $all_plugins ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get Current Theme Info
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_current_theme_info() {
			$current_theme = array();
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data    = wp_get_theme();
				$current_theme = array(
					'name'       => $theme_data->get( 'Name' ),
					'version'    => $theme_data->get( 'Version' ),
					'author'     => $theme_data->get( 'Author' ),
					'author_uri' => $theme_data->get( 'AuthorURI' ),
				);
			}

			return $current_theme;
		}

		/**
		 * Get server info
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_server_info() {
			global $wpdb;

			$server_info = array(
				'php_version'                  => PHP_VERSION,
				'mysql_version'                => $wpdb->db_version(),
				'web_server_info'              => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) : '',
				'user_agent'                   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
				'php_memory_limit'             => ini_get( 'memory_limit' ),
				'php_post_max_size'            => ini_get( 'post_max_size' ),
				'php_upload_max_file_size'     => ini_get( 'upload_max_filesize' ),
				'php_max_execution_time'       => ini_get( 'max_execution_time' ),
				'php_curl'                     => function_exists( 'curl_init' ) ? 'yes' : 'no',
				'php_fsockopen'                => function_exists( 'fsockopen' ) ? 'yes' : 'no',
				'session'                      => isset( $_SESSION ) ? 'enabled' : 'disabled',
				'session_name'                 => esc_html( ini_get( 'session.name' ) ),
				'cookie_path'                  => esc_html( ini_get( 'session.cookie_path' ) ),
				'session_save_path'            => esc_html( ini_get( 'session.save_path' ) ),
				'use_cookies'                  => ini_get( 'session.use_cookies' ) ? 'on' : 'off',
				'use_only_cookies'             => ini_get( 'session.use_only_cookies' ) ? 'on' : 'off',
				'ssl_support_extension_loaded' => extension_loaded( 'openssl' ) ? 'yes' : 'no',
				'mb_string_extension_loaded'   => extension_loaded( 'mbstring' ) ? 'yes' : 'no',
			);

			return $server_info;
		}

		/**
		 * Get WordPress information
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_wp_info() {
			global $wpdb;

			$timezone = get_option( 'timezone_string' );
			if ( empty( $timezone ) ) {
				$timezone = get_option( 'gmt_offset' );
			}

			$wp_info = array(
				'site_url'              => site_url(),
				'home_url'              => home_url(),
				'locale'                => get_locale(),
				'timezone'              => $timezone,
				'wp_version'            => get_bloginfo( 'version' ),
				'permalink_structure'   => get_option( 'permalink_structure' ),
				'multisite'             => is_multisite() ? 'yes' : 'no',
				'wp_debug'              => defined( 'WP_DEBUG' ) ? ( WP_DEBUG ? 'enabled' : 'disabled' ) : '',
				'display_errors'        => ( ini_get( 'display_errors' ) ) ? 'on' : 'off',
				'wp_table_prefix'       => $wpdb->prefix,
				'wp_db_charset_Collate' => $wpdb->get_charset_collate(),
				'wp_memory_limit'       => ( size_format( (int) WP_MEMORY_LIMIT * 1048576 ) ),
				'wp_upload_size'        => ( size_format( wp_max_upload_size() ) ),
				'filesystem_method'     => function_exists( 'get_filesystem_method' ) ? get_filesystem_method() : '',
			);

			return $wp_info;
		}

		/**
		 * Checks if current site is a development one.
		 *
		 * @return bool
		 *
		 * @since 1.2.6
		 */
		public static function is_dev_environment() {

			$url                = network_site_url( '/' );
			$is_dev_environment = false;

			// Trim it up
			$url = strtolower( trim( $url ) );

			// Need to get the host...so let's add the scheme so we can use parse_url
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				$url = 'http://' . $url;
			}

			$url_parts = parse_url( $url );
			$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

			if ( ! empty( $url ) && ! empty( $host ) ) {
				if ( false !== ip2long( $host ) ) {
					if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						$is_dev_environment = true;
					}
				} elseif ( 'localhost' === $host ) {
					$is_dev_environment = true;
				}

				$tlds_to_check = array( '.dev', '.local', ':8888' );
				foreach ( $tlds_to_check as $tld ) {
					if ( false !== strpos( $host, $tld ) ) {
						$is_dev_environment = true;
						continue;
					}
				}
				if ( substr_count( $host, '.' ) > 1 ) {
					$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
					foreach ( $subdomains_to_check as $subdomain ) {
						$subdomain = str_replace( '.', '(.)', $subdomain );
						$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
						if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
							$is_dev_environment = true;
							continue;
						}
					}
				}
			}

			return $is_dev_environment;
		}

		/**
		 * Get admin user info
		 *
		 * @return array
		 *
		 * @since 1.2.6
		 */
		public static function get_user_info() {

			$users = get_users(
				array(
					'role'    => 'administrator',
					'orderby' => 'ID',
					'order'   => 'ASC',
					'number'  => 1,
					'paged'   => 1,
				)
			);

			$admin_user = ( is_array( $users ) && ! empty( $users ) ) ? $users[0] : false;
			$first_name = '';
			$last_name  = '';

			if ( $admin_user ) {
				$first_name = $admin_user->first_name ? $admin_user->first_name : $admin_user->display_name;
				$last_name  = $admin_user->last_name;
			}

			$user_info = array(
				'admin_email' => get_option( 'admin_email' ),
				'first_name'  => $first_name,
				'last_name'   => $last_name,
			);

			return $user_info;
		}
	}
}
