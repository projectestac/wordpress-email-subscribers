<?php
/**
 * Triggers when a user is updated
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_User_Updated class.
 *
 * @since 4.4.1
 */
class ES_Trigger_User_Updated extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'user' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'User Updated', 'email-subscribers' );
		$this->description = __( 'Fires when a user is updated.', 'email-subscribers' );
		$this->group       = __( 'User', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'profile_update', array( $this, 'handle_user_updated' ), 10, 2 );
	}


	/**
	 * Catch user updated hook
	 *
	 * @param int     $user_id User ID.
	 * @param WP_User $old_user_data User object having old data.
	 */
	public function handle_user_updated( $user_id, $old_user_data ) {

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
