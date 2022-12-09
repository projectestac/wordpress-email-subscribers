<?php

$email_content = <<<EMAIL
Hi {{NAME}},

Just one more step before we share the awesomeness from {{SITENAME}}!

Please confirm your subscription by clicking on <a href='{{SUBSCRIBE-LINK}}'>this link</a>

Thanks!
EMAIL;

return array(
	'title'       => __( 'Subscriber: Confirmation email', 'email-subscribers' ),
	'description' => __( 'Send confirmation email when someone subscribes.', 'email-subscribers' ),
	'type'        => IG_ES_WORKFLOW_TYPE_USER,
	'trigger_name'=> 'ig_es_user_unconfirmed',
	'rules'       => array(),
	'meta'		  => array(
		'when_to_run' => 'immediately',
	),
	'actions'     => array(
		array(
			'action_name'         => 'ig_es_send_email',
			'ig-es-send-to'       => '{{EMAIL}}',
			'ig-es-email-subject' => __( 'Thanks!', 'email-subscribers' ),
			'ig-es-email-content' => $email_content,
		),
	),
);
