<?php
/**
 * Admin View: Notice - Trial To Premium Offer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$referer = wp_get_referer();

$notice_optin_action = 'ig_es_trial_to_premium_redirect';

$optin_url = wp_nonce_url(
	add_query_arg(
		array(
			'es_dismiss_admin_notice' => 1,
			'option_name'             => 'trial_to_premium_notice',
			'action'                  => $notice_optin_action,
		),
		$referer
	),
	'es_dismiss_admin_notice'
);

$notice_optout_action = 'ig_es_trial_to_premium_dismiss';

$optout_url = wp_nonce_url(
	add_query_arg(
		array(
			'es_dismiss_admin_notice' => 1,
			'option_name'             => 'trial_to_premium_notice',
			'action'                  => $notice_optout_action,
		),
		$referer
	),
	'es_dismiss_admin_notice'
);

$remaining_trial_days = ES()->trial->get_remaining_trial_days();
$day_or_days          = _n( 'day', 'days', $remaining_trial_days, 'email-subscribers' );

$discount_messages = array(
	'bfcm' => array(
		'message' => __( 'Get flat <strong>50%</strong> discount on annual plan if you upgrade now!<br/><strong>No coupon code</strong> required. Discount will be applied automatically.', 'email-subscribers' ),
	),
);

$offer_type_to_show = 'trial';

$offers_date_ranges = array(
	'bfcm' => array(
		'start_date' => '2022-11-23 7:00:00',
		'end_date'   => '2022-11-30 7:00:00',
	),
);

foreach ( $offers_date_ranges as $offer_type => $offer_dates ) {
	$offer_start_date = $offer_dates['start_date'];
	$offer_end_date   = $offer_dates['end_date'];

	if ( ( $ig_current_date >= strtotime( $offer_start_date ) ) && ( $ig_current_date <= strtotime( $offer_end_date ) ) ) {
		$offer_type_to_show = $offer_type;
		break;
	}
}

$trial_expiration_message = '';
if ( $remaining_trial_days > 1 ) {
	/* translators: 1. Remaining trial days. 2. day or days text based on number of remaining trial days. */
	$trial_expiration_message = sprintf( __( 'Your free trial is going to <strong>expire in %1$s %2$s</strong>.', 'email-subscribers' ), $remaining_trial_days, $day_or_days );
} else {
	$trial_expiration_message = __( 'Today is the <strong>last day</strong> of your free trial.', 'email-subscribers' );
}

// Add default value to message.
/* translators: 1. Discount % 2. Premium coupon code */
$discount_message      = sprintf( __( 'Get flat %1$s discount if you upgrade now!<br/>Use coupon code %2$s during checkout.', 'email-subscribers' ), '<strong>10%</strong>', '<span class="ml-2 px-1.5 py-1 font-medium bg-yellow-100 rounded-md border-2 border-dotted border-indigo-300 select-all">PREMIUM10</span>' );
$offer_cta_optin_text  = __( 'Upgrade now', 'email-subscribers' );
$offer_cta_optout_text = __( 'No, it\'s ok', 'email-subscribers' );

// Override offer message with current active offer message.
if ( ! empty( $discount_messages[ $offer_type_to_show ] ) ) {
	$discount_message = ! empty( $discount_messages[ $offer_type_to_show ]['message'] ) ? $discount_messages[ $offer_type_to_show ]['message'] : $discount_message;
}

/* translators: 1. Trial expiration message. 2. Discount message. */
$offer_message = sprintf( __( 'Hi there,<br/>Hope you are enjoying <strong>Icegram Express MAX trial</strong>.<br/>%1$s<br/>Upgrade now to continue uninterrupted use of premium features like <strong>block fake signups, prevent bot attacks, broadcast scheduling, automatic email sending, detailed campaign report, prevent emails from going to spam</strong> & lot more....<br/>%2$s', 'email-subscribers' ), $trial_expiration_message, $discount_message );

?>
<div id="ig-es-trial-to-premium-notice" class="notice notice-success">
	<p>
	<?php
		echo wp_kses_post( $offer_message );
	?>
	<br/>
	<a href="<?php echo esc_url( $optin_url ); ?>" target="_blank" id="ig-es-optin-trial-to-premium-offer" class="ig-es-primary-button px-3 py-1 mt-2 align-middle">
		<?php
			echo esc_html( $offer_cta_optin_text );
		?>
	</a>
	<a href="<?php echo esc_url( $optout_url ); ?>" class="ig-es-title-button px-3 py-1 mt-2 ml-2 align-middle">
		<?php
			echo esc_html( $offer_cta_optout_text );
		?>
	</a>
	</p>
</div>
