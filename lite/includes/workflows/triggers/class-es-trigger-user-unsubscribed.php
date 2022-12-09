<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Trigger_User_Unsubscribed' ) ) {
	/**
	 * Class ES_Trigger_User_Unsubscribed
	 */
	class ES_Trigger_User_Unsubscribed extends ES_Workflow_Trigger {
	
		/**
		 * Declares data items available in trigger.
		 *
		 * @var array
		 */
		public $supplied_data_items = array( 'subscriber' );
	
		/**
		 * Load trigger admin details.
		 */
		public function load_admin_details() {
			$this->title       = __( 'User Unsubscribed', 'email-subscribers' );
			$this->description = __( 'Fires when someone unsubscribes.', 'email-subscribers' );
			$this->group       = __( 'Subscriber', 'email-subscribers' );
		}
	
		/**
		 * Register trigger's hooks.
		 */
		public function register_hooks() {
			// Add action for custom trigger event
			add_action( 'ig_es_contact_unsubscribe', array( $this, 'handle_trigger_event' ), 10, 4 );
		}
	
		/**
		 * Handle custom trigger event.
		 *
		 * @param array $user_data
		 */
		public function handle_trigger_event( $subscriber_id, $message_id, $campaign_id, $unsubscribe_lists ) {
	
			if ( ! empty( $subscriber_id ) ) {
				$subscriber = ES()->contacts_db->get( $subscriber_id );
				if ( ! empty( $subscriber ) ) {
					$email      = ! empty( $subscriber['email'] ) && is_email( $subscriber['email'] ) ? $subscriber['email'] : '';
					$first_name = ! empty( $subscriber['first_name'] ) ? $subscriber['first_name']                          : '';
					$last_name  = ! empty( $subscriber['last_name'] ) ? $subscriber['last_name']                            : '';
	
					if ( ! empty( $email ) ) {
						$subscriber = array(
							'email'      => $email,
							'name' => $first_name . ' ' . $last_name,
						);
				
						// Prepare data.
						$data = array(
							'subscriber' => $subscriber,
						);
				
						$this->maybe_run( $data );
					}
				}
			}
			
		}
	
	}
}
