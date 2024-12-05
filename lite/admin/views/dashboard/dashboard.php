<?php
// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$audience_url              = admin_url( 'admin.php?page=es_subscribers' );
$new_contact_url           = admin_url( 'admin.php?page=es_subscribers&action=new' );
$new_broadcast_url         = admin_url( 'admin.php?page=es_campaigns#!/gallery?campaignType=newsletter' );
$new_post_notification_url = admin_url( 'admin.php?page=es_campaigns#!/gallery?campaignType=post_notification' );
$new_sequence_url          = admin_url( 'admin.php?page=es_sequence&action=new' );
$new_form_url              = admin_url( 'admin.php?page=es_forms&action=new' );
$form_url                  = admin_url( 'admin.php?page=es_forms' );
$new_list_url              = admin_url( 'admin.php?page=es_lists&action=new' );
$list_url				   = admin_url( 'admin.php?page=es_lists' );
$new_template_url          = admin_url( 'admin.php?page=es_campaigns#!/gallery?manageTemplates=yes' );
$icegram_pricing_url       = 'https://www.icegram.com/email-subscribers-pricing/';
$reports_url               = admin_url( 'admin.php?page=es_reports' );
$templates_url             = admin_url( 'edit.php?post_type=es_template' );
$settings_url              = admin_url( 'admin.php?page=es_settings' );
$facebook_url              = 'https://www.facebook.com/groups/2298909487017349/';
$import_url				   = admin_url( 'admin.php?page=es_subscribers&action=import' );
$campaign_url			   = admin_url( 'admin.php?page=es_campaigns' );


$topics = ES_Common::get_useful_articles();
$allowed_html_tags = ig_es_allowed_html_tags_in_esc(); 

?>
<div class="font-sans" id="ig-es-container">
	<div class="sticky top-0 z-10">
		<header>
			<nav aria-label="Global" class="pb-5 w-full pt-2">
				<div class="brand-logo">
					<span>
						<img src="<?php echo ES_PLUGIN_URL . 'lite/admin/images/new/brand-logo/IG LOGO 192X192.svg'; ?>" alt="brand logo" />
						<div class="divide"></div>
						<h1><?php echo esc_html_e('Icegram Express', 'email-subscriber'); ?></h1>
					</span>
				</div>

				<div class="cta">
					<div class=" py-2 text-sm leading-5">
						<a href="<?php echo esc_url( $campaign_url ); ?>" class="font-bold hover:font-semibold text-indigo-600" target="_blank"><?php echo esc_html_e('Campaigns', 'email-subscriber'); ?></a>・<a href="<?php echo esc_url( $form_url ); ?>" class="font-bold hover:font-semibold text-indigo-600" target="_blank"><?php echo esc_html_e('Forms', 'email-subscriber'); ?></a>・<a href="<?php echo esc_url( $reports_url ); ?>" class="font-bold hover:font-semibold text-indigo-600" target="_blank"><?php echo esc_html_e('Reports', 'email-subscriber'); ?></a>・<a href="<?php echo esc_url( $settings_url ); ?>" class="font-bold hover:font-semibold text-indigo-600" target="_blank"><?php echo esc_html_e('Settings', 'email-subscriber'); ?></a>
					</div>
				</div>
			</nav>
		</header>
	</div>
	<main class="mx-auto max-w-7xl">
		<div class="overview max-w-7xl mx-auto py-8 sm:px-3 xl:px-8 mr-4 ml-4 dashboard-kpi">
			<p class="text-lg font-medium leading-6 text-gray-400"><?php echo esc_html_e('Last 30 days', 'email-subscriber'); ?></p>
			<?php
				ES_Admin::get_view(
					'dashboard/dashboard-kpi', 
					array(
						'dashboard_kpi' => $dashboard_kpi,
					)
				);
				?>
			
		</div>

		<div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
			<div class="main-section">
				<!-- Recent campaigns section -->
				<section class="overview relative recent-campaigns">
					<div class="es-w-ful">
						<div class="flex items-center pr-2 md:justify-between pb-2">
							<p class="text-lg font-medium leading-6 text-gray-400">
								<?php
									echo esc_html__( 'Recent Campaigns', 'email-subscribers' );
								?>
							</p>
							<div class="cta">
								<span class="ml-3 rounded-md shadow-sm">
									<div id="ig-es-create-button" class="relative inline-block text-left">
										<div>
											<span class="rounded-md shadow-sm">
												<button type="button" class="primary"><?php echo esc_html__( 'Create', 'email-subscribers' ); ?><svg class="w-5 h-5 ml-2 -mr-1 inline-block" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
												</button>
											</span>
										</div>
										<div x-show="open" id="ig-es-create-dropdown" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="">
											<div class="bg-white rounded-md shadow-xs">
												<div class="py-1">
													<a href="<?php echo esc_url( $new_broadcast_url ); ?>"><?php echo esc_html__( 'New Broadcast', 'email-subscribers' ); ?></a>
													<!-- Start-IG-Code -->
													<a href="<?php echo esc_url( $new_post_notification_url ); ?>"><?php echo esc_html__( 'New Post Notification', 'email-subscribers' ); ?></a>
													<!-- End-IG-Code -->
													<?php if ( ES()->is_pro() ) { ?>
														<a href="<?php echo esc_url( $new_sequence_url ); ?>"><?php echo esc_html__( 'New Sequence', 'email-subscribers' ); ?></a>
													<?php } else { ?>
														<a href="<?php echo esc_url( $icegram_pricing_url ); ?>" target="_blank"><?php echo esc_html__( 'New Sequence', 'email-subscribers' ); ?>
														<span class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full"><?php echo esc_html__( 'Premium', 'email-subscribers' ); ?></span></a>
													<?php } ?>
												</div>
												<div class="border-b border-gray-100"></div>
												<div class="py-1">
														<a href="<?php echo esc_url( $new_template_url ); ?>"><?php echo esc_html__( 'New Template', 'email-subscribers' ); ?></a>
												</div>
												<div class="border-b border-gray-100"></div>
												<div class="py-1">
														<a href="<?php echo esc_url( $new_form_url ); ?>"><?php echo esc_html__( 'New Form', 'email-subscribers' ); ?></a>
														<a href="<?php echo esc_url( $new_list_url ); ?>"><?php echo esc_html__( 'New List', 'email-subscribers' ); ?></a>
														<a href="<?php echo esc_url( $new_contact_url ); ?>"><?php echo esc_html__( 'New Contact', 'email-subscribers' ); ?></a>
												</div>
											</div>
										</div>
									</div>
								</span>
							</div>
						</div>
						<?php
							ES_Admin::get_view(
								'dashboard/recent-campaigns', 
								array(
									'campaigns' => $campaigns,
								)
							);
							?>
					</div>
				</section>
				<!-- Forms & Lists -->
				<section class="overview forms-lists">
					<div class="forms-container">
						<div class="flex items-center md:justify-between">
							<p class="text-lg font-medium leading-6 text-gray-400"><?php echo esc_html__( 'Forms', 'email-subscribers' ); ?></a>
							</p>
							<p class="new_form_link">
								<a class="font-bold mr-4 hover:font-semibold" href="<?php echo esc_url( $form_url ); ?>" target="_blank">
									<?php echo esc_html__( 'View all', 'email-subscribers' ); ?> &rarr;
								</a>
								<a class="font-bold hover:font-semibold text-indigo-600" href="<?php echo esc_url( $new_form_url ); ?>" target="_blank"><button class="lighter-gray"><?php echo esc_html__( 'Create', 'email-subscribers' ); ?></button>
								</a>
							</p>
						</div>
						<?php
							ES_Admin::get_view(
								'dashboard/forms',
								array(
									'forms' => $forms,
								)
							);
							?>
					</div>

					<div class="lists-container">
						<div class="flex items-center md:justify-between">
							<p class="text-lg font-medium leading-6 text-gray-400"><?php echo esc_html__( 'Lists', 'email-subscribers' ); ?></a>
							</p>
							<p class="new_list_link">
								<a class="font-bold mr-4 hover:font-semibold" href="<?php echo esc_url( $list_url ); ?>" target="_blank"><?php echo esc_html__('View all', 'email-subscribers'); ?> &rarr;</a>

								<a class="font-bold hover:font-semibold text-indigo-600" href="<?php echo esc_url( $new_list_url ); ?>" target="_blank"><button class="lighter-gray"><?php echo esc_html__('Create', 'email-subscribers'); ?></button>
								</a>
							</p>
						</div>
						<?php
							ES_Admin::get_view(
								'dashboard/lists', 
								array(
									'lists' => $lists,
								)
							);
							?>
					</div>
				</section>
			</div>
			
			<!-- Recent Activity -->
			<section class="overview relative audience-activity">
				<div class="es-w-full">
					<div class="flex items-center pr-2 md:justify-between">
						<p class="text-lg font-medium leading-6 text-gray-400">
							<?php
								echo esc_html__( 'Recent Activity', 'email-subscribers' );
							?>
						</p>
						
						<a href="<?php echo esc_url( $import_url ); ?>">
							<button type="button" class="lighter-gray">
								<span>
									<?php echo esc_html__( 'Import', 'email-subscribers' ); ?>
								</span>
							</button>
						</a>
					</div>
					
					<?php
						ES_Admin::get_view(
							'dashboard/audience-activity', 
							array(
								'audience_activity' => $audience_activity,
							)
						);
						?>
				</div>
			</section>
		</div>

		
		<section class="overview ig-dashboardcommon-section">
			<!-- Trial-optin form -->
			<div class="flex-auto min-w-0 es-w-35 pr-3">
				<?php
					ES_Admin::get_view(
						'dashboard/trial-optin-ess'
					);
					?>
			</div>
			
			<!-- Email sending service section -->
			<div class="flex-auto min-w-0 es-w-35 pr-3 ess-block">
				<?php
				if ( ES_Service_Email_Sending::is_onboarding_completed() ) {
					$current_month       = ig_es_get_current_month();
					$service_status      = ES_Service_Email_Sending::get_sending_service_status();
					$ess_data            = get_option( 'ig_es_ess_data', array() );
					$used_limit          = isset( $ess_data['used_limit'][$current_month] ) ? $ess_data['used_limit'][$current_month]: 0;
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
			
			<!--Tips and Trick section -->
			<div class="flex-auto min-w-0 es-w-35 tips-trick-section">
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
	</main>
</div>

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
