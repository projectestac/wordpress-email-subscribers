<?php
/**
 * Triggers when a user gets registered
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_User_Registered class.
 *
 * @since 4.4.0
 */
class ES_Trigger_User_Registered extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'user', 'coupon' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'User Registered', 'email-subscribers' );
		$this->description = __( 'Fires when someone signup.', 'email-subscribers' );
		$this->group       = __( 'User', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'user_register', array( $this, 'handle_user_register' ) );
	}


	/**
	 * Catch user registered hook
	 *
	 * @param int $user_id User ID.
	 */
	public function handle_user_register( $user_id ) {

		// Get user info.
		$user = get_userdata( $user_id );
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Prepare data.
		$data = array(
			'user' => $user,
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

		$user = $workflow->data_layer()->get_item( 'user' );

		if ( ! $user ) {
			return false;
		}

		return true;
	}

}
