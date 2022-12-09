<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to allow admin to manage REST API Keys(creating,revoking,deleting API keys)
 * 
 * @since 5.4.18
 */
class ES_Rest_API_Admin {
	
	public static function init() {
		self::register_hooks();
	}

	public static function register_hooks() {

		add_action( 'wp_ajax_ig_es_generate_rest_api_key', array( __CLASS__, 'handle_generate_rest_api_key_request' ) );
		add_action( 'wp_ajax_ig_es_delete_rest_api_key', array( __CLASS__, 'handle_delete_rest_api_key_request' ) );
	}

	public static function handle_generate_rest_api_key_request() {
		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		$response = array();
		$user_id  = ig_es_get_request_data( 'user_id', 0 );
		if ( empty( $user_id ) ) {
			$response['status']  = 'error';
			$response['message'] = __( 'Please select a user.', 'email-subscribers' );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user ) {
			$response['status']  = 'error';
			$response['message'] = __( 'Selected user doesn\'t exists. Please select a different user.', 'email-subscribers' );
		} else {
			$generated_api_key = self::generate_rest_api_key( $user_id );
			if ( $generated_api_key ) {
				$rest_api_keys   = get_user_meta( $user_id, 'ig_es_rest_api_keys', true );
				$rest_api_keys   = ! empty( $rest_api_keys ) ? $rest_api_keys : array();
				$rest_api_keys[] = $generated_api_key;
				update_user_meta( $user_id, 'ig_es_rest_api_keys', $rest_api_keys );
				$response['status'] = 'success';
				/* Translators: %s: new API key */
				$message  = sprintf( __( 'Here is your new API key: %s.', 'email-subscribers' ), '<code class="es-code">' . $generated_api_key . '</code>' );
				$message .= '<br/>' . __( 'Be sure to save this in a safe location. You will not be able to retrieve it later on.', 'email-subscribers' );
				
				$response['message'] = $message;
			}
		}
		
		wp_send_json( $response );
	}

	/**
	 * Generate a new REST API key from the user email
	 */
	public static function generate_rest_api_key( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$user_email = $user->user_email;
		$auth_key   = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';

		$generated_api_key = hash( 'md5', $user_email . $auth_key . gmdate( 'U' ) );

		return $generated_api_key;
	}

	public static function handle_delete_rest_api_key_request() {
		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		$response = array();
		$user_id  = ig_es_get_request_data( 'user_id', 0 );
		if ( empty( $user_id ) ) {
			$response['status']  = 'error';
			$response['message'] = __( 'User missing.', 'email-subscribers' );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user ) {
			$response['status']  = 'error';
			$response['message'] = __( 'User missing.', 'email-subscribers' );
		} else {
			$api_index = ig_es_get_request_data( 'api_index', 0 );
			$api_key_deleted = self::delete_rest_api_key( $user_id, $api_index );
			if ( $api_key_deleted ) {
				$response['status'] = 'success';
			}
		}
		
		wp_send_json( $response );
	}

	public static function delete_rest_api_key( $user_id, $index = 0 ) {
		$rest_api_keys = get_user_meta( $user_id, 'ig_es_rest_api_keys', true );
		unset( $rest_api_keys[ $index ] );

		if ( empty( $rest_api_keys ) ) {
			$success = delete_user_meta( $user_id, 'ig_es_rest_api_keys' );
		} else {
			$success = update_user_meta( $user_id, 'ig_es_rest_api_keys', $rest_api_keys );
		}

		if ( true === $success ) {
			return true;
		}
		return false;
	}
}

ES_Rest_API_Admin::init();
