<?php

if ( ! class_exists( 'ES_Router' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Router
	 */
	class ES_Router {

		// class instance
		public static $instance;

		// class constructor
		public function __construct() {
			$this->init();
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function init() {
			$this->register_hooks();
		}

		public function register_hooks() {
			add_action( 'wp_ajax_icegram-express', array( $this, 'handle_ajax_request' ) );
		}

		/**
		 * Method to draft a campaign
		 *
		 * @return $response Broadcast response.
		 *
		 * @since 4.4.7
		 */
		public function handle_ajax_request() {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
			
			$can_access_audience  = ES_Common::ig_es_can_access( 'audience' );
			$can_access_campaign  = ES_Common::ig_es_can_access( 'campaigns' );
			$can_access_forms     = ES_Common::ig_es_can_access( 'forms' );
			$can_access_sequence  = ES_Common::ig_es_can_access( 'sequence' );
			$can_access_reports   = ES_Common::ig_es_can_access( 'reports' );
			$can_access_workflows = ES_Common::ig_es_can_access( 'workflows' );
			if ( ! ( $can_access_audience || $can_access_campaign || $can_access_forms || $can_access_sequence || $can_access_reports || $can_access_workflows ) ) {
				return 0;
			}
			$response = array();


			$request = $_REQUEST;
			
			$handler       = ig_es_get_data( $request, 'handler' );
			$handler_class = 'ES_' . ucfirst( $handler ) . '_Controller';
			if ( empty( $handler ) || ! class_exists( $handler_class ) ) {
				$response = array(
					'message' => __( 'No request handler found.', 'email-subscribers' ),
				);
				wp_send_json_error( $response );
			}

			$method = ig_es_get_data( $request, 'method' );
			if ( ! method_exists( $handler_class, $method ) || ! is_callable( array( $handler_class, $method ) ) ) {
				$response = array(
					'message' => __( 'No request method found.', 'email-subscribers' ),
				);
				wp_send_json_error( $response );
			}

			$data   = ig_es_get_request_data( 'data', array(), false );
			$result = call_user_func( array( $handler_class, $method ), $data );

			if ( $result ) {
				$response['success'] = true;
				$response['data']    = $result;
			} else {
				$response['success'] = false;
			}

			wp_send_json( $response );
		}
	}
}

ES_Router::get_instance();

