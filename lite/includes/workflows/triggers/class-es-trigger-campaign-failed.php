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
 * ES_Trigger_Campaign_Failed class.
 *
 * @since 5.0.1
 */
class ES_Trigger_Campaign_Failed extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'campaign' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Campaign failed', 'email-subscribers' );
		$this->description = __( 'Fires when a campaign isn\'t sent after trying 3 times.', 'email-subscribers' );
		$this->group       = __( 'Admin', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'ig_es_campaign_failed', array( $this, 'handle_campaign_failed' ) );
	}


	/**
	 * Catch user subscribed hook
	 *
	 * @param int $notification_guid Notification ID.
	 */
	public function handle_campaign_failed( $notification_guid ) {

		// Prepare data.
		$data = array(
			'campaign' => array(
				'notification_guid' => $notification_guid
			)
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

		$campaign = $workflow->data_layer()->get_item( 'campaign' );

		if ( empty( $campaign ) ) {
			return false;
		}

		return true;
	}

}
