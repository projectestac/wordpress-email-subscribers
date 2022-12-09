<?php

$email_content = <<<EMAIL
Hi {{NAME}},

Just wanted to send you a quick note...

Thank you for joining the awesome {{SITENAME}} tribe.

Only valuable emails from me, promise!

Thanks!
EMAIL;

return array(
	'title'       => __( 'Subscriber: Welcome email', 'email-subscribers' ),
	'description' => __( 'Send welcome email when someone subscribes.', 'email-subscribers' ),
	'type'        => IG_ES_WORKFLOW_TYPE_USER,
	'trigger_name'=> 'ig_es_user_subscribed',
	'rules'       => array(),
	'meta'		  => array(
		'when_to_run' => 'immediately',
	),
	'actions'     => array(
		array(
			'action_name'         => 'ig_es_send_email',
			'ig-es-send-to'       => '{{EMAIL}}',
			'ig-es-email-subject' => __( 'Welcome to {{SITENAME}}', 'email-subscribers' ),
			'ig-es-email-content' => $email_content,
		),
	),
);
