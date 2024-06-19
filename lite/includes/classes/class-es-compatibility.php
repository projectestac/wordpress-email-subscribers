<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Compatibility' ) ) {
	/**
	 * Make ES Compatible with other plugins
	 *
	 * Class ES_Compatibility
	 *
	 * @since 4.3.9
	 */
	class ES_Compatibility {
		/**
		 * ES_Compatibility constructor.
		 *
		 * @since 4.3.9
		 */
		public function __construct() {
			add_filter( 'wp_mail_smtp_providers_mailer_get_body', array( $this, 'wp_mail_smtp_modify_header' ), 10, 2 );
			add_filter( 'duplicate_post_excludelist_filter', array( $this, 'add_post_notified_meta_key_to_excluded_list' ) );
		}

		/**
		 * Outlook require X-Return-Path instead of Return-Path as a header
		 * So,we can handle it using 'wp_mail_smtp_providers_mailer_get_body'
		 * filter of WP Mail SMTP plugin.
		 *
		 * @param $body
		 * @param $mailer
		 *
		 * @return mixed
		 *
		 * @since 4.3.9
		 */
		public function wp_mail_smtp_modify_header( $body, $mailer ) {

			if ( 'outlook' === $mailer ) {
				$headers = $body['message']['internetMessageHeaders'];
				foreach ( $headers as $key => $header ) {
					if ( 'Return-Path' === $header['name'] ) {
						$body['message']['internetMessageHeaders'][ $key ]['name'] = 'X-Return-Path';
					}
				}
			}

			return $body;
		}

		/**
		 * Add 'ig_es_is_post_notified' post meta key to Yoast Duplicate Post's plugin exclusion list
		 * 
		 * When meta key is in the exclusion list, Yoast Duplicate Post plugin won't copy when duplicating the post
		 * 
		 * @param $meta_excludelist array
		 * 
		 * @param $meta_excludelist array
		 * 
		 * @since 5.7.9
		 */
		public function add_post_notified_meta_key_to_excluded_list( $meta_excludelist = array() ) {
			$meta_excludelist[] = 'ig_es_is_post_notified';
			return $meta_excludelist;
		}
	}
}
