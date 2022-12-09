<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache implementation of the plugin
 *
 * @credit - Inspired by the WooCommerce Cache implementation.
 */
if ( ! class_exists( 'ES_Cache' ) ) {
	/**
	 * Class ES_Cache
	 *
	 * @since 4.4.0
	 */
	class ES_Cache {

		/**
		 * Cache enabled/disabled
		 *
		 * @var bool
		 */
		public static $enabled = true;

		/**
		 * Get default transient expiration
		 *
		 * @return mixed|void
		 */
		public static function get_default_transient_expiration() {
			return apply_filters( 'ig_es_cache_default_expiration', 10 );
		}

		/**
		 * Set the transient
		 *
		 * @param $key
		 * @param $value
		 * @param bool  $expiration
		 *
		 * @return bool
		 */
		public static function set_transient( $key, $value, $expiration = false ) {
			if ( ! self::$enabled ) {
				return false;
			}
			if ( ! $expiration ) {
				$expiration = self::get_default_transient_expiration();
			}

			return set_transient( 'ig_es_cache_' . $key, $value, $expiration * HOUR_IN_SECONDS );
		}

		/**
		 * Get the transient
		 *
		 * @param string $key
		 *
		 * @return bool|mixed
		 *
		 * @since 4.4.0
		 */
		public static function get_transient( $key ) {
			if ( ! self::$enabled ) {
				return false;
			}

			return get_transient( 'ig_es_cache_' . $key );
		}

		/**
		 * Transient delete
		 *
		 * @param $key
		 *
		 * @since 4.4.0
		 */
		public static function delete_transient( $key ) {
			delete_transient( 'ig_es_cache_' . $key );
		}

		/**
		 * Only sets if key is not falsy
		 *
		 * @param string $key
		 * @param mixed  $value
		 * @param string $group
		 *
		 * @since 4.4.0
		 */
		public static function set( $key, $value, $group ) {
			if ( ! $key ) {
				return;
			}

			wp_cache_set( (string) $key, $value, "ig_es_$group" );
		}

		/**
		 * Get the data
		 *
		 * @param $key
		 * @param $group
		 * @param false $force
		 * @param null  $found
		 *
		 * @return false|mixed
		 */
		public static function get( $key, $group, $force = false, &$found = null ) {
			if ( ! $key ) {
				return false;
			}

			return wp_cache_get( (string) $key, "ig_es_$group", $force, $found );
		}

		/**
		 * Checks if key is found in the cache or not
		 *
		 * @param string $key
		 * @param string $group
		 *
		 * @return bool
		 *
		 * @since 4.4.0
		 */
		public static function is_exists( $key, $group ) {
			if ( ! $key ) {
				return false;
			}

			$found = false;

			wp_cache_get( (string) $key, "ig_es_$group", false, $found );

			return $found;
		}


		/**
		 * Only deletes if key is not falsy
		 *
		 * @param string $key
		 * @param string $group
		 *
		 * @since 4.4.0
		 */
		public static function delete( $key, $group ) {
			if ( ! $key ) {
				return;
			}
			wp_cache_delete( (string) $key, "ig_es_$group" );
		}

		/**
		 * Generate cache key
		 *
		 * @param string $string
		 *
		 * @return boolean $exists_in_cache
		 *
		 * @since 4.7.2
		 */
		public static function generate_key( $string = '' ) {
			$cache_key = '';
			if ( ! empty( $string ) ) {
				$cache_key = md5( $string ); // Convert to md5 hash string
			}

			return $cache_key;
		}

		/**
		 * Flush cache
		 *
		 * @since 4.7.2
		 */
		public static function flush() {
			wp_cache_flush();
		}

	}
}
