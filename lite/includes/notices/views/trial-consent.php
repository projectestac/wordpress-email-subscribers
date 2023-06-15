<?php
/**
 * Admin View: Notice - Trial Optin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$referer   = wp_get_referer();
$optin_url = wp_nonce_url(
	add_query_arg( 'ig_es_trial_consent', 'yes', $referer ),
	'ig_es_trial_consent'
);

$optout_url = wp_nonce_url(
	add_query_arg( 'ig_es_trial_consent', 'no', $referer ),
	'ig_es_trial_consent'
);
?>
<div class="notice notice-success">
	<p>
	<?php
		/* translators: %s: Trial period in days */
		echo esc_html__( sprintf( 'Start your %s days free trial of Icegram Express’ premium services like email delivery check, spam protection, cron handling & lot more..', ES()->trial->get_trial_period( 'in_days' ) ), 'email-subscribers' );
	?>
	<br/>
	<a href="<?php echo esc_url( $optin_url ); ?>" class="ig-es-primary-button px-3 py-1 mt-2 align-middle">
		<?php
			echo esc_html__( 'Yes, start my free trial!', 'email-subscribers' );
		?>
	</a>
	<a href="<?php echo esc_url( $optout_url ); ?>" class="ig-es-title-button px-3 py-1 mt-2 ml-2 align-middle">
		<?php
			echo esc_html__( 'No, it’s ok!', 'email-subscribers' );
		?>
	</a>
	</p>
</div>
