<?php
// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

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
			'feature_url'  => '#',
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

$topics            = ES_Common::get_useful_articles();
$allowed_html_tags = ig_es_allowed_html_tags_in_esc();

?>
<div class="wrap pt-4 font-sans" id="ig-es-dashboard">
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
							 x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 hidden w-56 mt-2 origin-top-right rounded-md shadow-lg z-50">
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

		<section id="es-dashboard-stats" class="relative py-4 my-8 bg-white rounded-lg shadow md:flex md:items-start md:justify-between sm:px-4 sm:grid sm:grid-cols-3">
			<div class="flex-auto min-w-0 es-w-55 pl-2">
				<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
					<span class="leading-7">
						<?php
							/* translators: %s. Number of days */
							echo sprintf( esc_html__( 'Subscribers activities in last %d days', 'email-subscribers' ), esc_html( $days ) );
						?>
					</span>
					<span class="float-right">
						<select id="filter_by_list">
							<?php
							$lists_dropdown = ES_Common::prepare_list_dropdown_options( '', __( 'All lists', 'email-subscribers' ) );
							echo wp_kses( $lists_dropdown, $allowed_html_tags );
							?>
						</select>
					</span>
				</p>
				<?php
				ES_Admin::get_view(
					'dashboard/subscribers-stats',
					array(
						'reports_data' => $reports_data,
						'days'         => $days,
					)
				);
				?>
			</div>
			<div class="flex-auto min-w-0 es-w-45 px-3">
			<?php
			if ( ES_Service_Email_Sending::is_onboarding_completed() ) {
				$current_date        = ig_es_get_current_date();
				$service_status      = ES_Service_Email_Sending::get_sending_service_status();
				$ess_data            = get_option( 'ig_es_ess_data', array() );
				$used_limit          = isset( $ess_data['used_limit'][$current_date] ) ? $ess_data['used_limit'][$current_date]: 0;
				$allocated_limit     = isset( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit']                    : 0;
				$interval            = isset( $ess_data['interval'] ) ? $ess_data['interval']                                  : '';
				$current_mailer_name = ES()->mailer->get_current_mailer_name();

				ES_Admin::get_view(
					'dashboard/ess-account-overview',
					array(
						'service_status'      => $service_status,
						'allocated_limit'     => $allocated_limit,
						'used_limit'          => $used_limit,
						'interval'            => $interval,
						'current_mailer_name' => $current_mailer_name,
						'settings_url'        => $settings_url,
					)
				);
			} else {
				$ess_onboarding_step = get_option( 'ig_es_ess_onboarding_step', 1 );
				$ess_optin           = ig_es_get_request_data( 'ess_optin' );
				ES_Admin::get_view(
					'dashboard/ess-onboarding', 
					array(
						'ess_onboarding_step' => (int) $ess_onboarding_step,
						'ess_optin'           => $ess_optin,
					)
				);
			}
			?>
			</div>
		</section>

		<section id="es-sending-service" class="py-4 my-8 bg-white rounded-lg shadow md:flex md:items-start md:justify-between sm:px-4 sm:grid sm:grid-cols-2">
		<div class="flex-auto min-w-0 es-w-35 px-2">
				<?php
				$countries_count = 5;
				?>
				<p class="text-lg font-medium leading-7 text-gray-400">
					<?php
						/* Translators: %s. Country count */
						echo sprintf( esc_html__( 'Top %s countries', 'email-subscribers' ), esc_html( $countries_count ) );
					?>
					<?php
					if ( ! ES()->is_pro() ) {
						$utm_args = array(
							'utm_medium' => 'dashboard-top-countries',
							'url'		 => 'https://www.icegram.com/documentation/what-analytics-does-email-subscribers-track/'
						);
				
						$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
						?>
						<a  target="_blank" href="<?php echo esc_url( $pricing_url ); ?>">
							<span class="premium-icon inline-block max"></span>
						</a>
						<?php
					}
					?>
				</p>
				<?php
					do_action( 'ig_es_show_top_countries_stats', $countries_count );
				?>
			</div>
			<div class="flex-auto min-w-0 es-w-65 pr-2">
				<p class="text-lg font-medium leading-6 text-gray-400">
					<span class="leading-7">
						<?php
							echo esc_html__( 'Recent activities', 'email-subscribers' );
						?>
						<?php
						if ( ! ES()->is_pro() ) {
							$utm_args = array(
								'utm_medium' => 'dashboard-recent-activites',
							);
					
							$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
							?>
							<a  target="_blank" href="<?php echo esc_url( $pricing_url ); ?>">
								<span class="premium-icon inline-block max"></span>
							</a>
							<?php
						}
						?>
					</span>
					<?php
					if ( ES()->is_pro() ) {
						?>
					<span class="float-right">
						<?php
						$activities = array(
							IG_CONTACT_SUBSCRIBE   => esc_html__( 'Subscribes', 'email-subscribers' ),
							IG_CONTACT_UNSUBSCRIBE => esc_html__( 'Unsubscribes', 'email-subscribers' ),
							IG_MESSAGE_SENT        => esc_html__( 'Received', 'email-subscribers' ),
							IG_MESSAGE_OPEN        => esc_html__( 'Opens', 'email-subscribers' ),
							IG_LINK_CLICK          => esc_html__( 'Clicks', 'email-subscribers' ),
							IG_MESSAGE_SOFT_BOUNCE => esc_html__( 'Soft bounces', 'email-subscribers' ),
							IG_MESSAGE_HARD_BOUNCE => esc_html__( 'Hard bounces', 'email-subscribers' ),
						);
						?>
						<select id="filter_by_activity">
							<option value="">
								<?php echo esc_html__( 'All activities', 'email-subscribers' ); ?>
							</option>
							<?php
							foreach ( $activities as $activity_id => $activity ) {
								?>
								<option value="<?php echo esc_attr( $activity_id ); ?>"><?php echo esc_html( $activity ); ?></option>
								<?php
							}
							?>
						</select>
					</span>
					<?php
					}
					?>
				</p>
				<?php
					do_action( 'ig_es_show_recent_activities' );
				?>
			</div>
		</section>

		<section id="es-campaign-stats" class="pt-4 my-8 bg-white rounded-lg shadow md:flex md:items-start md:justify-between sm:px-4 sm:grid sm:grid-cols-2">
			<div class="flex-auto min-w-0 es-w-65 px-2">
				<p class="px-2 text-lg font-medium leading-6 text-gray-400">
					<?php
						echo esc_html__( 'Recent campaigns', 'email-subscribers' );
					?>
				</p>
				<?php
					$campaigns = ! empty( $reports_data ) ? $reports_data['campaigns'] : array();
					ES_Admin::get_view(
						'dashboard/recent-campaigns',
						array(
							'campaigns' => $campaigns,
							'upsell'    => ! ES()->is_pro(),
						)
					);
					?>
			</div>
			<div class="flex-auto min-w-0 es-w-35 pr-2">
				<p class="text-lg font-medium leading-6 text-gray-400">
					<span>
						<?php
							echo esc_html__( 'Tips & Tricks', 'email-subscribers' );
						?>
					</span>
				</p>
				<?php
					ES_Admin::get_view(
						'dashboard/tip-and-tricks',
						array(
							'topics' => $topics
						)
					);
					?>
			</div>
		</section>

		<section class="my-16">
			<div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
			<?php
			foreach ( $feature_blocks as $feature => $data ) {
				$is_trial_block = strpos( $feature, 'trial' ) !== false;
				$bg             = $is_trial_block ? 'bg-teal-100' : 'bg-white';
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

		});

	})(jQuery);

</script>
