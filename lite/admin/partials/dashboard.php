<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$reports_data = ES_Reports_Data::get_dashboard_reports_data( true );

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

$labels = '';
$values = '';
if ( ! empty( $contacts_growth ) ) {
	$labels = array_keys( $contacts_growth );
	$values = array_values( $contacts_growth );
}

$audience_url              = admin_url( 'admin.php?page=es_subscribers' );
$new_contact_url           = admin_url( 'admin.php?page=es_subscribers&action=new' );
$new_broadcast_url         = admin_url( 'admin.php?page=es_newsletters' );
$new_post_notification_url = admin_url( 'admin.php?page=es_notifications&action=new' );
$new_sequence_url          = admin_url( 'admin.php?page=es_sequence&action=new' );
$new_form_url              = admin_url( 'admin.php?page=es_forms&action=new' );
$new_list_url              = admin_url( 'admin.php?page=es_lists&action=new' );
$new_template_url          = admin_url( 'post-new.php?post_type=es_template' );
$icegram_pricing_url       = 'https://www.icegram.com/email-subscribers-pricing/';
$reports_url               = admin_url( 'admin.php?page=es_reports' );
$templates_url             = admin_url( 'edit.php?post_type=es_template' );
$settings_url              = admin_url( 'admin.php?page=es_settings' );
$facebook_url              = 'https://www.facebook.com/groups/2298909487017349/';

$topics = ES_Common::get_useful_articles();

$topics_indexes = array_rand( $topics, 3 );

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

		<section class="py-4 my-8 bg-white rounded-lg shadow md:flex md:items-start md:justify-between sm:px-4 sm:grid sm:grid-cols-3">
			<div class="flex-1 min-w-0">
				<p class="px-3 text-lg font-medium leading-6 text-gray-400">
					<span class="text-black"><?php echo esc_html( $active_contacts ); ?></span><?php echo esc_html__( ' active contacts', 'email-subscribers' ); ?>
				</p>
				<div class="text-center bg-white-100" id="ig-es-contacts-growth">

				</div>
			</div>
			<div class="flex-1 min-w-0">
				<p class="px-3 text-lg font-medium leading-6 text-gray-400">
					<?php echo esc_html__( 'Last 60 days', 'email-subscribers' ); ?>
				</p>
				<div class="sm:grid sm:grid-cols-2">
					<div class="p-3">
						<p class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_email_opens ); ?>
						</p>
						<p class="mt-1 font-medium leading-6 text-gray-500">
							<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>
						</p>
					</div>
					<div class="p-3">
						<p class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $avg_open_rate ); ?> %
						</p>
						<p class="mt-1 font-medium leading-6 text-gray-500">
							<?php echo esc_html__( ' Avg Open Rate', 'email-subscribers' ); ?>
						</p>
					</div>
					<div class="p-3">
						<p class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $total_message_sent ); ?>
						</p>
						<p class="mt-1 font-medium leading-6 text-gray-500">
							<?php echo esc_html__( 'Messages Sent', 'email-subscribers' ); ?>
						</p>
					</div>
					<div class="p-3">
						<p class="text-2xl font-bold leading-none text-indigo-600">
							<?php echo esc_html( $avg_click_rate ); ?> %
						</p>
						<p class="mt-1 font-medium leading-6 text-gray-500">
							<?php echo esc_html__( 'Avg Click Rate', 'email-subscribers' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div class="flex-1 min-w-0">
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


		<?php
		if ( ES()->is_pro() ) {
			include_once ES_PLUGIN_DIR . '/pro/partials/es-dashboard.php';
		}
		?>

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

				const chart = new frappe.Chart("#ig-es-contacts-growth", {
					title: "",
					data: data,
					type: 'line',
					colors: ['#743ee2'],
					lineOptions: {
						hideDots: 1,
						heatline: 1
					},
					height: 150,
					axisOptions: {
						xIsSeries: true
					}
				});
			}

		});

	})(jQuery);

</script>
