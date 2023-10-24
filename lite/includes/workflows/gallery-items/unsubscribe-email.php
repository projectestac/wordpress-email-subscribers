<?php
if ( ES()->is_pro() ) {
	$reason='Reason:{{subscriber.unsubscriber_reason}}';
} else {
	$reason='';	
}

$admin_email = ES_Common::get_admin_email();
if ( is_email( $admin_email ) ) {
	$user       = get_user_by( 'email', $admin_email );
	$admin_name = '';
	if ( $user instanceof WP_User ) {
		$admin_name = ucfirst($user->display_name);
	
	}
}

$email_content = <<<EMAIL
Hi {$admin_name},

A user has chosen to unsubscribe from our services. Please find the following details:

Name:{{subscriber.name}}
Email:{{subscriber.email}}
{$reason}

Thanks!
EMAIL;

return array(
	'title'       => __( 'Subscriber: Unsubscribe email', 'email-subscribers' ),
	'description' => __( 'When a user unsubscribes,Send an unsubscribe notification email to the administrator', 'email-subscribers' ),
	'type'        => IG_ES_WORKFLOW_TYPE_USER,
	'trigger_name'=> 'ig_es_user_unsubscribed',
	'rules'       => array(),
	'meta'		  => array(
		'when_to_run' => 'immediately',
	),
	'actions'     => array(
		array(
			'action_name'         => 'ig_es_send_email',
			'ig-es-send-to'       => $admin_email,
			'ig-es-email-subject' => __( '  Important: User unsubscription notification', 'email-subscribers' ),
			'ig-es-email-content' => $email_content,
		),
	),
);
