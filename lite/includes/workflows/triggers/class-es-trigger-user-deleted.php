<?php
/**
 * Triggers when a user gets deleted
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_User_Deleted class.
 *
 * @since 4.4.1
 */
class ES_Trigger_User_Deleted extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'subscriber' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'User Deleted', 'email-subscribers' );
		$this->description = __( 'Fires when user deleted from WordPress.', 'email-subscribers' );
		$this->group       = __( 'User', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'delete_user', array( $this, 'handle_user_delete' ), 10, 1 );
	}


	/**
	 * Catch user deleted hook
	 *
	 * @param int $user_id User ID.
	 */
	public function handle_user_delete( $user_id = 0 ) {

		// Get user info.
		$user = get_userdata( $user_id );
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		$email = $user->user_email;

		if ( ! empty( $email ) ) {

			$subscriber = array(
				'email' => $email,
			);

			// Prepare data.
			$data = array(
				'subscriber' => $subscriber,
			);

			$this->maybe_run( $data );
		}
		
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

		if ( ! $subscriber ) {
			return false;
		}

		return true;
	}

}
