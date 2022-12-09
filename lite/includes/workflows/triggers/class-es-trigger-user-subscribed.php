<?php
/**
 * Triggers when a user gets subscribed
 *
 * @since       5.0.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_User_Subscribed class.
 *
 * @since 5.0.1
 */
class ES_Trigger_User_Subscribed extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'subscriber', 'coupon' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'User Subscribed', 'email-subscribers' );
		$this->description = __( 'Fires when someone subscribes.', 'email-subscribers' );
		$this->group       = __( 'Subscriber', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'ig_es_contact_subscribed', array( $this, 'handle_user_subscribe' ) );
	}


	/**
	 * Catch user subscribed hook
	 *
	 * @param int $subscriber_id User ID.
	 */
	public function handle_user_subscribe( $subscriber ) {

		// Prepare data.
		$data = array(
			'subscriber' => $subscriber
		);

		$this->maybe_run( $data );
	}


	/**
	 * Validate a workflow.
	 *
	 * @param ES_Workflow $workflow Workflow object.
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$subscriber = $workflow->data_layer()->get_item( 'subscriber' );

		if ( empty( $subscriber ) ) {
			return false;
		}

		return true;
	}

}
