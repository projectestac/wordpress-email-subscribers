<?php
/**
 * Class to handle REST API Request
 * 
 * @since 5.4.17     
 * @version 1.0    
 * @package Email Subscribers 
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ES_Rest_Api_Handler' ) ) {
	 /**
	 * Class to handle REST API in the plugin
	 * 
	 * @since 5.4.17
	 */
	class ES_Rest_Api_Handler {

		public static function init() {
			self::register_hooks();
		}

		public static function register_hooks() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_api_routes' ) );
		}

		/**
		 * Handle subscribers through REST API in the plugin
		 */
		public static function register_rest_api_routes() {

			register_rest_route( 'email-subscribers/v1', '/subscribers', [
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'add_subscribers' ),     
				'permission_callback' => array( __CLASS__, 'check_permission' )          
			 ]);
			 
			register_rest_route( 'email-subscribers/v1', '/subscribers', [
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( __CLASS__, 'edit_subscribers' ),
				'permission_callback' => array( __CLASS__, 'check_permission' )
			]);
			
			register_rest_route( 'email-subscribers/v1', '/subscribers', [
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( __CLASS__, 'delete_subscribers' ),
				'permission_callback' => array( __CLASS__, 'check_permission' )
			]);
		}

		/**
		 * Check permission
		 * 
		 * @param WP_REST_Request $request
		 *
		 * @return bool
		 */
		public static function check_permission( WP_REST_Request $request ) {

			$rest_api_enabled = 'yes' === get_option( 'ig_es_allow_api', 'no' );

			if ( ! $rest_api_enabled ) {
				return false;
			}

			$rest_api_key      = $request->get_header( 'password' );
			$rest_api_username = $request->get_header( 'username' );

			if ( ! $rest_api_key || ! $rest_api_username ) {
				return false;
			}

			$user = get_user_by( 'slug', $rest_api_username );
			if ( ! $user ) {
				return false;
			}

			$user_id = $user->ID;

			$rest_api_keys = get_user_meta( $user_id, 'ig_es_rest_api_keys', true );
			if ( ! in_array( $rest_api_key, $rest_api_keys, true ) ) {
				return false;
			}

			return true;
		}
	
		/**
		 * Add subscribers through REST API.
		 * 
		 * @since 5.4.17       
		 * @param $request
		 * @return $response 
		 * 
		*/
		public static function add_subscribers( $request ) {            

			$response = ['message_text' => 'something is missing'];
			$params   = $request->get_json_params();   
			
			if ( ! isset( $params['email'] ) ) {
				$message = [
					'message_text' => 'Email is missing.',
					'message'      => 'email_missing',
					'success'      => false,                
				];
	
				$response = new WP_REST_Response( $message, 200 ); 
				return $response; 
			}

		
			$email      = sanitize_email( $params['email'] );
			
			
			if ( ! is_email( $email ) ) {
				$message = [
					'message_text' => 'Email is invalid.',
					'message'      => 'invalid_email',
					'success'      => false,                
				];
	
				$response = new WP_REST_Response( $message, 200 ); 
				return $response;
			}

			$contact_id = ES()->contacts_db->get_contact_id_by_email( $email );
			if ( $contact_id ) {
				$message = [
					'message_text' => 'Email already exists.',
					'message'      => 'email_exists',
					'success'      => false,                
				];
	
				$response = new WP_REST_Response( $message, 200 ); 
				return $response;
			}

			$first_name = isset( $params['first_name'] ) ? sanitize_text_field( $params['first_name'] ) : '';
			$last_name  = isset( $params['last_name'] ) ? sanitize_text_field( $params['last_name'] ) : '';
			$guid       = ES_Common::generate_guid();  

			$contact_data               = array();
			$contact_data['first_name'] = $first_name;
			$contact_data['last_name']  = $last_name;
			$contact_data['source']     = 'rest_api';
			$contact_data['email']      = $email;
			$contact_data['status']     = 'verified';
			$contact_data['hash']       = $guid;
			$contact_data['created_at'] = ig_get_current_date_time();
			$contact_data['updated_at'] = null;
			$contact_data['meta']       = null;

			$contact_id                 = ES()->contacts_db->insert( $contact_data );
			$contact_data['contact_id'] = $contact_id;
			$list_status                = ! empty( $params['list_status'] ) ? $params['list_status'] : ES_Common::get_list_status_from_optin_type();
			$optin_type                 = 'subscribed' === $list_status ? IG_SINGLE_OPTIN : IG_DOUBLE_OPTIN;
			$subscribed_at              = 'subscribed' === $list_status ? ig_get_current_date_time() : '';
			
			$list_contact_data = [
				'contact_id'    => $contact_id,
				'status'        => $list_status,
				'optin_type'    => $optin_type,
				'subscribed_at' => $subscribed_at,
				'subscribed_ip' => '',
			];

			if ( ! empty( $params['list_ids'] ) ) {           
	
				$list_ids = $params['list_ids'];
	
				$list_contact_added = ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $list_ids );

				if ( $list_contact_added ) {
					$message = [
						'message_text' => 'Contact has been inserted with list successfully!',
						'message'      => 'contact_added_with_list',
						'success'      => true,
						'contact_data' => $contact_data,                 
					];
		
					$response = new WP_REST_Response( $message, 200 ); 
					return $response;  
				} else {
					$message = [
						'message_text' => 'Contact not inserted with list!',
						'message'      => 'contact_not_added_with_list',
						'success'      => false,
					];
		
					$response = new WP_REST_Response( $message ); 
					return $response; 
				} 
			} 			

			if ( $contact_id ) {
				$message = [
					'message_text' => 'Contact has been inserted successfully!',
					'message'      => 'contact_added',
					'success'      => true,
					'contact_data' => $contact_data,                 
				];
	
				$response = new WP_REST_Response( $message, 200 ); 
				return $response;  
			} else {
				$message = [
					'message_text' => 'Contact not inserted!',
					'message'      => 'contact_not_added',
					'success'      => false,
				];
	
				$response = new WP_REST_Response( $message ); 
				return $response; 
			}                         
		   
		}


		/**
		 * Edit subscribers contact_data through REST API.
		 * 
		 * @since 5.4.17       
		 * @param $request
		 * @return $response 
		 * 
		*/
		public static function edit_subscribers( $request ) {

			$params = $request->get_json_params();
			
			if ( ! isset( $params['contact_id'] ) ) {
				$message = [
					'message_text' => 'Contact id isn\'t passed.',
					'message'      => 'missing_contact_id',
					'success'      => false,              
				];
	
				$response = new WP_REST_Response( $message, 200 ); 
				return $response;  
			}

			$contact_id = $params['contact_id'];
			if ( isset( $params['first_name'], $params['last_name'] ) ) {
				$contact_id = sanitize_text_field( $params['contact_id'] );
				
				$contact_data = array();
				if ( isset( $params['first_name'] ) ) {
					$first_name                 = sanitize_text_field( $params['first_name'] );
					$contact_data['first_name'] = $first_name;
				}

				if ( isset( $params['last_name'] ) ) {
					$last_name                 = sanitize_text_field( $params['last_name'] );
					$contact_data['last_name'] = $last_name;
				}

				if ( isset( $params['email'] ) ) {
					$email = sanitize_email( $params['email'] );
					if ( ! is_email( $email ) ) {
						$message  = [
							'message_text' => 'Email is not valid.',
							'message'      => 'invalid_email',
							'success'      => false
						];
						$response = new WP_REST_Response( $message, 200 ); 
						return $response;
					}
					$contact_data['email'] = $email;
				}

				if ( ! empty( $contact_data ) ) {
					$updated = ES()->contacts_db->update( $contact_id, $contact_data );
					if ( $updated ) {
						$message = [
							'message_text' => 'Contact has been updated successfully!',
							'message'      => 'contact_updated',
							'success'      => true,
							'contact_data' => $contact_data,                 
						];
			
						$response = new WP_REST_Response( $message, 200 ); 
						return $response;  
					} else {
						$message = [
							'message_text' => 'Failed to update contact.',
							'message'      => 'contact_update_failed',
							'success'      => false,
						];
			
						$response = new WP_REST_Response( $message ); 
						return $response; 
					} 
	
					
				}
				
			}
		  
			if ( ! empty( $params['list_ids'] ) ) {
				$list_ids = $params['list_ids'];

				$list_status   = ! empty( $params['list_status'] ) ? $params['list_status'] : ES_Common::get_list_status_from_optin_type();
				$optin_type    = 'subscribed' === $list_status ? IG_SINGLE_OPTIN : IG_DOUBLE_OPTIN;
				$subscribed_at = 'subscribed' === $list_status ? ig_get_current_date_time() : '';
				
				$list_contact_data = [
					'contact_id'    => $contact_id,
					'status'        => $list_status,
					'optin_type'    => $optin_type,
					'subscribed_at' => $subscribed_at,
					'subscribed_ip' => '',
				];

				$list_contact_updated = ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $list_ids );

				if ( $list_contact_updated ) {
					$message = [
						'message_text' => 'Contact has been updated corresponding to specific list!',
						'message'      => 'contact_updated_corr_to_list',
						'success'      => true,
					];
		
					$response = new WP_REST_Response( $message, 200 ); 
					return $response;
				} else {
					$message = [
						'message_text' => 'Contact not updated corresponding to specific list!',
						'message'      => 'contact_not_updated_corr_to_list',
						'success'      => false,
					];
		
					$response = new WP_REST_Response( $message ); 
					return $response;
				}
				
			}

		}
		
		
		/**
		 * Delete subscribers through REST API.
		 * 
		 * @since 5.4.17       
		 * @param $request
		 * @return $response 
		 * 
		*/
		public static function delete_subscribers( $request ) {

			$response = ['message_text' => 'Contact id is missing!'];
			$params   = $request->get_json_params();
			
			if ( ! isset( $params['contact_id'] ) || empty( $params['contact_id'] ) ) {
				return $response;
			}

			$contact_id = sanitize_text_field( $params['contact_id'] );

			$contact = ES()->contacts_db->get( $contact_id );
			if ( empty ( $contact ) ) {
				$message = [
					'message_text' => 'Contact does not exists.',
					'message'      => 'contact_not_exists',
					'success'      => false,
				];
	
				$response = new WP_REST_Response( $message, 200 ); 
				return $response;
			}

			if ( isset( $params['list_id'] ) ) {
				$list_ids   = [];
				$list_ids[] = sanitize_text_field( $params['list_id'] );
				
				$list_contact_ids = [$contact_id];

				$list_contact_deleted = ES()->lists_contacts_db->remove_contacts_from_lists( $list_contact_ids, $list_ids );

				if ( $list_contact_deleted ) {
					$message = [
						'message_text' => 'Contact has been deleted corresponding to specific list!',
						'message'      => 'contact_deleted_corr_to_list',
						'success'      => true,
					];
		
					$response = new WP_REST_Response( $message, 200 ); 
					return $response;
				} else {
					$message = [
						'message_text' => 'Contact not deleted corresponding to specific list!',
						'message'      => 'contact_not_deleted_corr_to_list',
						'success'      => false,
					];
		
					$response = new WP_REST_Response( $message ); 
					return $response;
				}

			}

			if ( $contact_id ) {

				$deleted = ES()->contacts_db->delete( $contact_id );

				if ( $deleted ) {
					$message = [
						'message_text' => 'Contact has been deleted successfully!',
						'message'      => 'contact_deleted',
						'success'      => true,
					];
		
					$response = new WP_REST_Response( $message, 200 ); 
					return $response;
				} else {
					$message = [
						'message_text' => 'Contact not deleted!',
						'message'      => 'contact_not_deleted',
						'success'      => false,
					];
		
					$response = new WP_REST_Response( $message ); 
					return $response; 
				}                 
			}

		}
	}
	
	ES_Rest_Api_Handler::init();
}

   
