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
		 *
		 */
		public static $enabled = true;

		/**
		 * Get default transient expiration
		 *
		 * @return mixed|void
		 *
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
		 * Only gets if key is not falsy
		 *
		 * @param string $key
		 * @param string $group
		 *
		 * @return bool|mixed
		 *
		 * @since 4.4.0
		 */
		public static function get( $key, $group ) {
			if ( ! $key ) {
				return false;
			}

			return wp_cache_get( (string) $key, "ig_es_$group" );
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
		public static function exists( $key, $group ) {
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

	}
}
