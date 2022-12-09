<?php
// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$number_of_days = 60;
$reports_data = ES_Reports_Data::get_dashboard_reports_data( 'es_dashboard', true );

$total_contacts_subscribed    = isset( $reports_data['total_contacts_subscribed'] ) ? $reports_data['total_contacts_subscribed'] : 0;
$active_contacts    = isset( $reports_data['total_contacts'] ) ? $reports_data['total_contacts'] : 0;
$total_forms        = isset( $reports_data['total_forms'] ) ? $reports_data['total_forms'] : 0;
$total_campaigns    = isset( $reports_data['total_campaigns'] ) ? $reports_data['total_campaigns'] : 0;
$total_lists        = isset( $reports_data['total_lists'] ) ? $reports_data['total_lists'] : 0;
$total_email_opens  = isset( $reports_data['total_email_opens'] ) ? $reports_data['total_email_opens'] : 0;
$total_open_rate    = isset( $reports_data['total_open_rate'] ) ? $reports_data['total_open_rate'] : 0;
$total_message_sent = isset( $reports_data['total_message_sent'] ) ? $reports_data['total_message_sent'] : 0;
$total_links_clicks = isset( $reports_data['total_links_clicks'] ) ? $reports_data['total_links_clicks'] : 0;
$total_click_rate   = isset( $reports_data['total_click_rate'] ) ? $reports_data['total_click_rate'] : 0;
$total_contact_lost = isset( $reports_data['total_message_lost'] ) ? $reports_data['total_message_lost'] : 0;
$total_lost_rate    = isset( $reports_data['total_lost_rate'] ) ? $reports_data['total_lost_rate'] : 0;
$avg_open_rate      = isset( $reports_data['avg_open_rate'] ) ? $reports_data['avg_open_rate'] : 0;
$avg_click_rate     = isset( $reports_data['avg_click_rate'] ) ? $reports_data['avg_click_rate'] : 0;
$contacts_growth    = isset( $reports_data['contacts_growth'] ) ? $reports_data['contacts_growth'] : array();
$campaigns          = isset( $reports_data['campaigns'] ) ? $reports_data['campaigns'] : array();

$open_percentage_growth   = isset( $reports_data['open_percentage_growth'] ) ? $reports_data['open_percentage_growth'] : 0;
$open_before_two_months   = isset( $reports_data['open_before_two_months'] ) ? $reports_data['open_before_two_months'] : 0;
$click_percentage_growth  = isset( $reports_data['click_percentage_growth'] ) ? $reports_data['click_percentage_growth'] : 0;
$click_before_two_months  = isset( $reports_data['click_before_two_months'] ) ? $reports_data['click_before_two_months'] : 0;
$average_engagement_score = isset( $reports_data['average_engagement_score'] ) ? $reports_data['average_engagement_score'] : 0;
$top_performing_campaigns = isset( $reports_data['top_performing_campaigns'] ) ? $reports_data['top_performing_campaigns'] : array();

$growth_percentage        = ES_Reports_Data::get_contacts_growth_percentage( $number_of_days );
$total_unsubscribed       = ES_Reports_Data::get_total_unsubscribed_contacts( $number_of_days );
$last_subscribed_contacts = (int) ES()->contacts_db->get_total_subscribed_contacts_between_days( $number_of_days );
$convert_date_format      = get_option( 'date_format' );
$last_period_start_date   = gmdate( $convert_date_format, strtotime( '-' . ( 2 * $number_of_days ) . ' days' ) );
$last_period_end_date     = gmdate( $convert_date_format, strtotime( '-' . $number_of_days . ' days' ) );


$labels = '';
$values = '';
if ( ! empty( $contacts_growth ) ) {
	$labels = array_keys( $contacts_growth );
	$values = array_values( $contacts_growth );
}

$audience_url              = admin_url( 'admin.php?page=es_subscribers' );
$new_contact_url           = admin_url( 'admin.php?page=es_subscribers&action=new' );
$new_broadcast_url         = admin_url( 'admin.php?page=es_gallery&campaign-type=newsletter' );
$new_post_notification_url = admin_url( 'admin.php?page=es_gallery&campaign-type=post_notification' );
$new_sequence_url          = admin_url( 'admin.php?page=es_sequence&action=new' );
$new_form_url              = admin_url( 'admin.php?page=es_forms&action=new' );
$new_list_url              = admin_url( 'admin.php?page=es_lists&action=new' );
$new_template_url          = admin_url( 'admin.php?page=es_gallery&manage-templates=yes' );
$icegram_pricing_url       = 'https://www.icegram.com/email-subscribers-pricing/';
$reports_url               = admin_url( 'admin.php?page=es_reports' );
$templates_url             = admin_url( 'edit.php?post_type=es_template' );
$settings_url              = admin_url( 'admin.php?page=es_settings' );
$facebook_url              = 'https://www.facebook.com/groups/2298909487017349/';


$feature_blocks = array(

	'form'                => array(
		'title'        => __( 'Add a Subscription Form', 'email-subscribers' ),
		'desc'         => __( 'Grow subscribers. Add a newsletter signup form to your site.', 'email-subscribers' ),
		'cta_text'     => __( 'Create', 'email-subscribers' ),
		'feature_url'  => $new_form_url,
		'graphics_img' => 'lite/admin/images/dashboard-subscriber-form.png',
	),

	'import_contacts'     => array(
		'title'        => __( 'Import Contacts', 'email-subscribers' ),
		'desc'         => __( 'Coming from another email marketing system? Upload a CSV file to import subscribers.', 'email-subscribers' ),
		'cta_text'     => __( 'Import', 'email-subscribers' ),
		'feature_url'  => admin_url( 'admin.php?page=es_subscribers&action=import' ),
		'graphics_img' => 'lite/admin/images/dashboard-import-contacts.png',
	),

	'setup_email_sending' => array(
		'title'        => __( 'Configure Email Sending', 'email-subscribers' ),
		'desc'         => __( ' Essential for high email delivery and reaching the inbox. SMTP, email service providers... set it all up.', 'email-subscribers' ),
		'cta_text'     => __( 'Setup', 'email-subscribers' ),
		'feature_url'  => admin_url( 'admin.php?page=es_settings#tabs-email_sending' ),
		'graphics_img' => 'lite/admin/images/dashboard-configure-email-sending.png',
	),

	'broadcast'           => array(
		'title'        => __( 'Send a Newsletter', 'email-subscribers' ),
		'desc'         => __( 'Broadcast a newsletter campaign to all or selected subscribers.', 'email-subscribers' ),
		'cta_text'     => __( 'Begin', 'email-subscribers' ),
		'feature_url'  => $new_broadcast_url,
		'graphics_img' => 'lite/admin/images/dashboard-send-newsletter.png',
	),

	'autoresponder'       => array(
		'title'             => __( 'Create an Auto-responder Sequence', 'email-subscribers' ),
		'desc'              => __( 'Welcome emails, drip campaigns... Send automatic emails at regular intervals to engage readers.', 'email-subscribers' ),
		'cta_text'          => __( 'Start', 'email-subscribers' ),
		'feature_url'       => $new_sequence_url,
		'graphics_img'      => 'lite/admin/images/dashboard-autoresponder-sequence.png',
		'documentation_url' => 'https://www.icegram.com/documentation/email-sequence/?utm_source=in_app&utm_medium=sequence&utm_campaign=es_doc_upsell',
	),
);

$trial_block      = array();
$show_trial_optin =  ! ES()->trial->is_trial() && ! ES()->is_premium();
if ( $show_trial_optin ) {
	$trial_period_in_days = ES()->trial->get_trial_period( 'in_days' );

	$trial_block = array(
		'trial-optin' => array(
			'title'        => __( 'Try Icegram Express (formerly known as Email Subscribers & Newsletters) Premium', 'email-subscribers' ),
			/* translators: %d: Trial period in days */
			'desc'         => sprintf( __( 'Start your %d days free trial to get automatic email sending, advance spam protection and more.', 'email-subscribers' ), $trial_period_in_days),
			'cta_text'     => __( 'Start trial', 'email-subscribers' ),
			'feature_url'  => '',
		),
	);
} elseif ( ! ES()->is_premium() && ES()->trial->is_trial() && ES()->trial->is_trial_valid() ) {
	$trial_period_in_days        = ES()->trial->get_trial_period( 'in_days' );
	$trial_expiry_date           = ES()->trial->get_trial_expiry_date();
	$formatted_trial_expiry_date = ig_es_format_date_time( $trial_expiry_date );

	$trial_block = array(
		'trial-active' => array(
			/* translators: %d: Trial period in days */
			'title'        => sprintf( __( 'Your free %d days trial is on', 'email-subscribers' ), $trial_period_in_days ),
			/* translators: %s: Number of days remaining in trial */
			'desc'         => sprintf( __( 'Hope you are enjoying the premium features of Icegram Express (formerly known as Email Subscribers & Newsletters). It will expire on %s. You can anytime upgrade it to MAX.', 'email-subscribers' ), $formatted_trial_expiry_date ),
			'cta_text'     => __( 'Upgrade to Max', 'email-subscribers' ),
			'feature_url'  => 'https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=upsell&utm_campaign=es_upsell',
		),
	);
} elseif ( ! ES()->is_premium() && ES()->trial->is_trial() && ES()->trial->is_trial_expired() ) {
	$trial_period_in_days = ES()->trial->get_trial_period( 'in_days' );

	$trial_block = array(
		'trial-expired' => array(
			/* translators: %d: Trial period in days */
			'title'        => sprintf( __( 'Your %d days trial is expired', 'email-subscribers' ), $trial_period_in_days ),
			'desc'         => __( 'Upgrade now to continue uninterrupted use of premium features like automatic email sending and more.', 'email-subscribers' ),
			'cta_text'     => __( 'Upgrade to Max', 'email-subscribers' ),
			'feature_url'  => 'https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=upsell&utm_campaign=es_upsell',
		),
	);
}

$feature_blocks = array_merge( $trial_block, $feature_blocks );
$feature_blocks = apply_filters( 'ig_es_admin_dashboard_feature_blocks', $feature_blocks );

$topics = ES_Common::get_useful_articles();

$topics_indexes = array_rand( $topics, 3 );
$allowed_html_tags = ig_es_allowed_html_tags_in_esc();

?>
<div class="wrap pt-4 font-sans" id="ig-es-container">
	<header class="mx-auto max-w-7xl">
		<div class="md:flex md:items-center md:justify-between">
			<div class="flex-1 min-w-0">
				<h2 class="text-3xl font-bold leading-7 text-gray-700 sm:leading-9 sm:truncate">
					<?php echo esc_html__( 'Dashboard', 'email-subscribers' ); ?>
				</h2>
			</div>
			<div class="flex mt-4 md:mt-0 md:ml-4">
				<a href="<?php echo esc_url( $audience_url ); ?>">
				<span class="rounded-md shadow-sm">
				<button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:shadow-outline focus:border-blue-300">
					<?php echo esc_html__( 'Audience', 'email-subscribers' ); ?>
				</button>
				</span>
				</a>
				<span class="ml-3 rounded-md shadow-sm">
				<div id="ig-es-create-button" class="relative inline-block text-left">
						<div>
						  <span class="rounded-md shadow-sm">
							<button type="button" class="w-full ig-es-primary-button">
								<?php echo esc_html__( 'Create', 'email-subscribers' ); ?>
							  <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
							  </svg>
							</button>
						  </span>
						</div>
						<div x-show="open" id="ig-es-create-dropdown" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
							 x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 hidden w-56 mt-2 origin-top-right rounded-md shadow-lg">
						  <div class="bg-white rounded-md shadow-xs">
							<div class="py-1">
							  <a href="<?php echo esc_url( $new_broadcast_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Broadcast', 'email-subscribers' ); ?></a>
								<!-- Start-IG-Code -->
							  <a href="<?php echo esc_url( $new_post_notification_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Post Notification', 'email-subscribers' ); ?></a>
								<!-- End-IG-Code -->
								<?php if ( ES()->is_pro() ) { ?>
								  <a href="<?php echo esc_url( $new_sequence_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Sequence', 'email-subscribers' ); ?></a>
								<?php } else { ?>
								  <a href="<?php echo esc_url( $icegram_pricing_url ); ?>" target="_blank" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Sequence', 'email-subscribers' ); ?>
									  <span class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full"><?php echo esc_html__( 'Premium', 'email-subscribers' ); ?></span></a>
								<?php } ?>
							</div>
							<div class="border-t border-gray-100"></div>
							<div class="py-1">
									<a href="<?php echo esc_url( $new_template_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Template', 'email-subscribers' ); ?></a>
							</div>
							<div class="border-t border-gray-100"></div>
							<div class="py-1">
									<a href="<?php echo esc_url( $new_form_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Form', 'email-subscribers' ); ?></a>
									<a href="<?php echo esc_url( $new_list_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New List', 'email-subscribers' ); ?></a>
									<a href="<?php echo esc_url( $new_contact_url ); ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php echo esc_html__( 'New Contact', 'email-subscribers' ); ?></a>
							</div>
						  </div>
						</div>
				</div>
			</span>
			</div>
		</div>
	</header>

	<main class="mx-auto max-w-7xl">

		<section class="es-dashboard-stats-item py-4 my-8 bg-white rounded-lg shadow md:flex md:items-start md:justify-between sm:px-4 sm:grid sm:grid-cols-3">
		<div class="flex-auto min-w-0">
				<p class="px-3 text-lg font-medium leading-6 text-gray-400">
					<?php echo esc_html__( 'Last 60 days', 'email-subscribers' ); ?>
				</p>
				<div class="grid grid-cols-12 gap-8 p-4 pr-0 text-gray-600">

					<div class="col-span-6">
						<div class="p-1">
							<span class="text-2xl font-bold leading-none text-indigo-600">
								<?php echo esc_html( $total_contacts_subscribed ); ?>
							</span>
							<?php
							$text_color_class = '';
							$arraw_html = '';
							if ( $growth_percentage < 0 ) {
								$text_color_class = 'text-red-600';
								$arraw_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
							} elseif ( 0 === $growth_percentage ) {
								$text_color_class = 'text-orange-600';
								$arraw_html = '';
							} else {
								$text_color_class = 'text-green-600';
								$arraw_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
							}
							?>
							<div class="inline-block es-new-subscriber-growth-percentage es-tooltip relative align-middle cursor-pointer">
								<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
									<?php echo esc_html( number_format_i18n( $growth_percentage, 2 ) ); ?>%
									<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
								</span>
								<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
									<div class="text-white-100">
										<div>
											<span class="text-lg text-base">
												<?php echo esc_html__( 'New subscribers', 'email-subscribers' ); ?>:
												<?php echo esc_html( number_format_i18n( $last_subscribed_contacts ) ); ?>
											</span>
										</div>
									</div>
									<div class="text-xs mt-1 pt-1 text-gray-100 border-t border-gray-100">
										<?php
											/* translators: 1. Start date 2. End date */
											echo esc_html__( sprintf( '%1$s to %2$s', $last_period_start_date, $last_period_end_date ), 'email-subscribers' );
										?>
									</div>
									<svg class="tooltip-arrow absolute mt-2 text-black text-opacity-100 h-2.5 left-0" viewBox="0 0 255 255">
										<polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon>
									</svg>
								</span>
							</div>
							<p class="mt-1 font-medium leading-6 text-gray-500">
								<?php echo esc_html__( 'New subscribers', 'email-subscribers' ); ?>						</p>
						</div>
						<div class="pt-1 pr-1 pl-1">
							<span class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_unsubscribed ); ?>						</span>

							<p class="mt-1 font-medium leading-6 text-gray-500">
							<?php echo esc_html__( 'Unsubscribed', 'email-subscribers' ); ?>						</p>
						</div>

						<?php do_action( 'ig_es_show_unsubscribe_feedback_reasons_stats', $number_of_days, $total_unsubscribed ); ?>

						<div class="p-1">
							<span class="text-2xl font-bold leading-none text-indigo-600 dashboard-engagement-score-stat">
								<?php
									$score_class = '';
									$score_text  = '';

								if ( ! ES()->is_pro() ) {
									$average_engagement_score = 4.2;
								}

									$average_engagement_score_html = ES_Common::get_engagement_score_html( $average_engagement_score );
								?>
								<?php echo wp_kses_post( $average_engagement_score_html ) ; ?>
							</span>
							<p class="mt-1 font-medium leading-6 text-gray-500">
								<?php echo esc_html__( 'Engagement score', 'email-subscribers' ); ?>
								<?php
								if ( ! ES()->is_pro() ) {
									$utm_args = array(
										'utm_medium' => 'dashboard-engagement-score-stat',
										'url'		 => 'https://www.icegram.com/documentation/how-does-engagement-score-work-in-the-email-subscribers/'
									);

									$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
									?>
									<a  target="_blank" href="<?php echo esc_url( $pricing_url ); ?>">
										<span class="premium-icon inline-block"></span>
									</a>
									<?php
								}
								?>
															</p>
						</div>
					</div>
					<div class="col-span-6">
						<div class="p-1">
							<span class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_message_sent ); ?>						</span>



							<p class="mt-1 font-medium leading-6 text-gray-500">
							<?php echo esc_html__( 'Messages sent', 'email-subscribers' ); ?>						</p>
						</div>
						<div class="p-1">
							<span class="text-2xl font-bold leading-none text-indigo-600">
								<?php echo esc_html( $total_email_opens ); ?>
							</span>
							<?php
								$text_color_class = '';
								$arraw_html = '';
							if ( $open_percentage_growth < 0 ) {
								$text_color_class = 'text-red-600';
								$arraw_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
							} elseif ( 0 === $open_percentage_growth ) {
								$text_color_class = 'text-orange-600';
								$arraw_html = '';
							} else {
								$text_color_class = 'text-green-600';
								$arraw_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
							}
							?>
							<div class="inline-block es-tooltip relative align-middle cursor-pointer">
								<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
									<?php echo esc_html( $open_percentage_growth ); ?>%
									<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
								</span>
								<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
									<div class="text-white-100">
										<div>
											<span class="text-lg text-base">
												<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>:
												<?php echo esc_html( $open_before_two_months ); ?>
											</span>
										</div>
									</div>
									<div class="text-xs mt-1 pt-1 text-gray-100 border-t border-gray-100">
										<?php
											/* translators: 1. Start date 2. End date */
											echo esc_html__( sprintf( '%1$s to %2$s', $last_period_start_date, $last_period_end_date ), 'email-subscribers' );
										?>
									</div>
									<svg class="tooltip-arrow absolute mt-2 text-black text-opacity-100 h-2.5 left-0" viewBox="0 0 255 255">
										<polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon>
									</svg>
								</span>
							</div>
							<span class="es-open-percentage-growth text-2xl font-bold leading-none text-indigo-600">
								<p class="mt-1 font-medium leading-6 text-gray-500">
									<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>
								</p>
							</span>
						</div>
						<div class="p-1">
							<?php
								$text_color_class = '';
								$arraw_html = '';
							if ( $click_percentage_growth < 0 ) {
								$text_color_class = 'text-red-600';
								$arraw_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
							} elseif ( 0 === $click_percentage_growth ) {
								$text_color_class = 'text-orange-600';
								$arraw_html = '';
							} else {
								$text_color_class = 'text-green-600';
								$arraw_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
							}
							?>
							<div id="es-dashboard-click-stats">
								<span class="text-2xl font-bold leading-none text-indigo-600">
									<?php echo esc_html(  $total_links_clicks ); ?>
								</span>
								<div class="inline-block es-tooltip relative align-middle cursor-pointer">
									<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
										<?php echo esc_html( $click_percentage_growth ); ?>%
										<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
									</span>
									<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
										<div class="text-white-100">
											<div>
												<span class="text-lg text-base">
													<?php echo esc_html__( 'Clicks', 'email-subscribers' ); ?>:
													<?php echo esc_html( $click_before_two_months ); ?>
												</span>
											</div>
										</div>
										<div class="text-xs mt-1 pt-1 text-gray-100 border-t border-gray-100">
											<?php
												/* translators: 1. Start date 2. End date */
												echo esc_html__( sprintf( '%1$s to %2$s', $last_period_start_date, $last_period_end_date ), 'email-subscribers' );
											?>
										</div>
										<svg class="tooltip-arrow absolute mt-2 text-black text-opacity-100 h-2.5 left-0" viewBox="0 0 255 255">
											<polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon>
										</svg>
									</span>
								</div>
								<p class="mt-1 font-medium leading-6 text-gray-500">
									<?php echo esc_html__( 'Clicks', 'email-subscribers' ); ?>
									<?php
									if ( ! ES()->is_pro() ) {
										$utm_args = array(
											'utm_medium' => 'dashboard-click-stat',
											'url'		 => 'https://www.icegram.com/documentation/what-analytics-does-email-subscribers-track/'
										);

										$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
										?>
										<a  target="_blank" href="<?php echo esc_url( $pricing_url ); ?>">
											<span class="premium-icon inline-block"></span>
										</a>
										<?php
									}
									?>
																	</p>
							</div>
						</div>

					</div>

				</div>
			</div>
			<div class="flex-auto min-w-0">
					<p class="px-3 text-lg font-medium leading-6 text-gray-400">
						<?php echo esc_html__( 'Top campaigns', 'email-subscribers' ); ?>
					</p>

				<?php
				if ( ! empty( $top_performing_campaigns ) ) {
					$es_nonce = wp_create_nonce( 'es_notification' );
					?>
					<ul class="pr-4">
					<?php
					foreach ( $top_performing_campaigns as $campaign ) {
						$campaign_id = $campaign['campaign_id'];
						$message_id  = $campaign['message_id'];
						$message     = ES_DB_Mailing_Queue::get_mailing_queue_by_id( $message_id );
						if ( empty( $message ) ) {
							continue;
						}
						$message_title = $message['subject'];
						$campaign_report_url = admin_url( 'admin.php?page=es_reports&action=view&list=' . $message['hash'] . '&_wpnonce=' . $es_nonce );
						$results = $wpdb->get_results( $wpdb->prepare( "SELECT type, count(DISTINCT (contact_id) ) as total FROM {$wpdb->prefix}ig_actions WHERE message_id = %d AND campaign_id = %d GROUP BY type", $message_id, $campaign_id ), ARRAY_A );

						$stats     = array();
						$action_type      = '';
						$type_text = '';

						if ( count( $results ) > 0 ) {

							foreach ( $results as $result ) {

								$action_type  = $result['type'];
								$total = $result['total'];

								switch ( $action_type ) {
									case IG_MESSAGE_SENT:
										$type_text = 'total_sent';
										break;
									case IG_MESSAGE_OPEN:
										$type_text = 'total_opens';
										break;
									case IG_LINK_CLICK:
										$type_text = 'total_clicks';
										break;
									case IG_CONTACT_UNSUBSCRIBE:
										$type_text = 'total_unsubscribe';
										break;
								}

								$stats[ $type_text ] = $total;
							}
						}

						$stats = wp_parse_args(
							$stats,
							array(
								'total_sent'        => 0,
								'total_opens'       => 0,
								'total_clicks'      => 0,
								'total_unsubscribe' => 0,
							)
						);

						if ( 0 != $stats['total_sent'] ) {
							$campaign_opens_rate  = ( $stats['total_opens'] * 100 ) / $stats['total_sent'];
							$campaign_clicks_rate = ( $stats['total_clicks'] * 100 ) / $stats['total_sent'];
							$campaign_losts_rate  = ( $stats['total_unsubscribe'] * 100 ) / $stats['total_sent'];
						} else {
							$campaign_opens_rate  = 0;
							$campaign_clicks_rate = 0;
							$campaign_losts_rate  = 0;
						}
						?>
						<li class="border-b border-gray-200">
							<a href="<?php echo esc_url( $campaign_report_url ); ?>" target="_blank" class="block hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition duration-150 ease-in-out" target="_blank">
								<div class="flex items-center px-2 py-2">
									<div class="w-3/5 min-w-0 flex-1">
										<div class="flex flex-1 items-center text-sm ">
											<span class="truncate">
												<?php echo esc_html( $message_title ); ?>
											</span>
										</div>
									</div>
									<div class="grid grid-cols-3">
										<div class="p-3">
											<span class="leading-none text-sm text-indigo-500">
												<?php echo esc_html( number_format_i18n( $campaign_opens_rate, 2 ) ); ?>%
											</span>
											<p class="mt-1 leading-6 text-gray-400">
												<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>
											</p>
										</div>
										<div class="p-3">
											<span class="leading-none text-sm text-indigo-500">
												<?php echo esc_html( number_format_i18n( $campaign_clicks_rate, 2 ) ); ?>%
											</span>
											<p class="mt-1 leading-6 text-gray-400">
												<?php echo esc_html__( 'Clicks', 'email-subscribers' ); ?>
											</p>
										</div>
										<div class="p-3">
											<span class="leading-none text-sm text-indigo-500">
												<?php echo esc_html( number_format_i18n( $campaign_losts_rate, 2 ) ); ?>%
											</span>
											<p class="mt-1 leading-6 text-gray-400">
												<?php echo esc_html__( 'Lost', 'email-subscribers' ); ?>
											</p>
										</div>
									</div>
									<div>
										<svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
										</svg>
									</div>
								</div>
							</a>
						</li>
						<?php
					}
					?>
					</ul>
					<?php
				} else {
					?>
					<div class="px-3 mt-1 text-sm leading-5 text-gray-900">
					<?php
						echo esc_html__( 'No campaigns found', 'email-subscribers' );
					?>
					</div>
					<?php
				}
				?>
			</div>
			<div class="flex-auto min-w-0">
				<div class="overflow-hidden">
					<ul>
						<!-- Start-IG-Code -->
						<?php foreach ( $topics_indexes as $index ) { ?>
							<li class="border-b border-gray-200 mb-0">
								<a href="<?php echo esc_url( $topics[ $index ]['link'] ); ?>" class="block transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:bg-gray-50" target="_blank">

									<div class="flex items-center px-2 py-2 md:justify-between">
										<div class="text-sm leading-5 text-gray-900">
											<?php
											echo wp_kses_post( $topics[ $index ]['title'] );
											if ( ! empty( $topics[ $index ]['label'] ) ) {
												?>
												<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo esc_attr( $topics[ $index ]['label_class'] ); ?>"><?php echo esc_html( $topics[ $index ]['label'] ); ?></span>
											<?php } ?>
										</div>
										<div>
											<svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
												<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
											</svg>
										</div>
									</div>
								</a>
							</li>
						<?php } ?>
						<!-- End-IG-Code -->
						<li class="">
							<div class="px-2 py-2 text-sm leading-5 text-gray-900 sm:px-2">
								<?php echo esc_html__( 'Jump to: ', 'email-subscribers' ); ?>
								<a href="<?php echo esc_url( $reports_url ); ?>" class="font-bold pl-1" target="_blank">
									<?php echo esc_html__( 'Reports', 'email-subscribers' ); ?>
								</a>
								・
								<a href="<?php echo esc_url( $templates_url ); ?>" class="font-bold" target="_blank">
									<?php echo esc_html__( 'Templates', 'email-subscribers' ); ?>
								</a>
								・
								<a href="<?php echo esc_url( $settings_url ); ?>" class="font-bold" target="_blank">
									<?php echo esc_html__( 'Settings', 'email-subscribers' ); ?>
								</a>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</section>

		<section class="my-16">
			<div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
			<?php
			foreach ( $feature_blocks as $feature => $data ) {
				$is_trial_block = strpos( $feature, 'trial' ) !== false;
				$bg = $is_trial_block ? 'bg-teal-100' : 'bg-white';
				?>
				<div id="ig-es-<?php echo esc_attr( $feature ); ?>-block" class="relative p-6 rounded-lg shadow <?php echo esc_attr( $bg ); ?>">
					<h3 class="text-lg font-medium tracking-tight text-gray-900">
						<?php echo esc_html( $data['title'] ); ?>
					</h3>
					<?php
					if ( ! empty( $data['graphics_img'] ) ) {
						$extra_css = ! empty( $data['graphics_img_class'] ) ? $data['graphics_img_class'] : '';
						?>
						<img
						class="absolute bottom-0 right-0 w-24 -mr-3 <?php echo esc_attr( $extra_css ); ?>"
						src= "<?php echo esc_url( ES_PLUGIN_URL . $data['graphics_img'] ); ?>"
						/>
						<?php
					}
					?>
					<div class="block-description" style="width: calc(100% - 4rem)">
						<p class="pt-3 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
							<?php
							if ( ! empty( $data['html_desc'] ) ) {
								echo wp_kses_post( $data['html_desc'] );
							} else {
								echo esc_html( $data['desc'] );
							}
							?>
						</p>

						<?php
						if ( !empty($data['feature_url'])) {
							$feature_url = $data['feature_url'];
							if ( ! ES()->is_pro() && isset( $data['documentation_url'] ) ) {
								$feature_url = $data['documentation_url'];
							}
							?>
							<a id="ig-es-<?php echo esc_attr( $feature ); ?>-cta" href="<?php echo esc_url( $feature_url ); ?>" target="_blank" class="es_primary_link">
								<?php echo esc_html( $data['cta_text'] ); ?> &rarr;
							</a>
						<?php
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</section>


		<?php
		if ( ES()->is_pro() ) {
			include_once ES_PLUGIN_DIR . '/pro/partials/es-dashboard.php';
		}
		?>

	</main>
</div>
<?php
if ( $show_trial_optin ) {
	include_once 'trial-optin-form.php';
}
?>
<script type="text/javascript">

	(function ($) {

		$(document).ready(function () {

			// When we click outside, close the dropdown
			$(document).on("click", function (event) {
				var $trigger = $("#ig-es-create-button");
				if ($trigger !== event.target && !$trigger.has(event.target).length) {
					$("#ig-es-create-dropdown").hide();
				}
			});

			// Toggle Dropdown
			$('#ig-es-create-button').click(function () {
				$('#ig-es-create-dropdown').toggle();
			});

			var labels =
			<?php
			if ( ! empty( $labels ) ) {
				echo json_encode( $labels );
			} else {
				echo "''";
			}
			?>
			;

			var values =
			<?php
			if ( ! empty( $values ) ) {
				echo json_encode( $values );
			} else {
				echo "''";
			}
			?>
			;

			if (labels != '' && values != '') {
				const data = {
					labels: labels,
					datasets: [
						{
							values: values
						},
					]
				};
			}

		});

	})(jQuery);

</script>
