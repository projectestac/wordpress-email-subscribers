<?php
$allowed_html_tags  = ig_es_allowed_html_tags_in_esc();
$total_subscribed   = isset( $reports_data['total_subscribed'] ) ? $reports_data['total_subscribed'] : 0;
$total_email_opens  = isset( $reports_data['total_email_opens'] ) ? $reports_data['total_email_opens'] : 0;
$total_open_rate    = isset( $reports_data['total_open_rate'] ) ? $reports_data['total_open_rate'] : 0;
$total_message_sent = isset( $reports_data['total_message_sent'] ) ? $reports_data['total_message_sent'] : 0;
$total_links_clicks = isset( $reports_data['total_links_clicks'] ) ? $reports_data['total_links_clicks'] : 0;
$total_click_rate   = isset( $reports_data['total_click_rate'] ) ? $reports_data['total_click_rate'] : 0;
$total_unsubscribed = isset( $reports_data['total_unsubscribed'] ) ? $reports_data['total_unsubscribed'] : 0;
$contacts_growth    = isset( $reports_data['contacts_growth'] ) ? $reports_data['contacts_growth'] : array();
$campaigns          = isset( $reports_data['campaigns'] ) ? $reports_data['campaigns'] : array();

$sent_percentage_growth        = isset( $reports_data['sent_percentage_growth'] ) ? $reports_data['sent_percentage_growth'] : 0;
$sent_before_two_months        = isset( $reports_data['sent_before_two_months'] ) ? $reports_data['sent_before_two_months'] : 0;
$open_percentage_growth        = isset( $reports_data['open_percentage_growth'] ) ? $reports_data['open_percentage_growth'] : 0;
$open_before_two_months        = isset( $reports_data['open_before_two_months'] ) ? $reports_data['open_before_two_months'] : 0;
$click_percentage_growth       = isset( $reports_data['click_percentage_growth'] ) ? $reports_data['click_percentage_growth'] : 0;
$click_before_two_months       = isset( $reports_data['click_before_two_months'] ) ? $reports_data['click_before_two_months'] : 0;
$unsubscribe_percentage_growth = isset( $reports_data['unsubscribe_percentage_growth'] ) ? $reports_data['unsubscribe_percentage_growth'] : 0;
$unsubscribe_before_two_months = isset( $reports_data['unsubscribe_before_two_months'] ) ? $reports_data['unsubscribe_before_two_months'] : 0;

$args = array(
	'days' => $days,
);

$growth_percentage        = ES_Reports_Data::get_contacts_growth_percentage( $args );
$last_subscribed_contacts = (int) ES()->contacts_db->get_total_subscribed_contacts_between_days( $days );
$convert_date_format      = get_option( 'date_format' );
$last_period_start_date   = gmdate( $convert_date_format, strtotime( '-' . ( 2 * $days ) . ' days' ) );
$last_period_end_date     = gmdate( $convert_date_format, strtotime( '-' . $days . ' days' ) );
?>
<div id="subscribers-stats" class="clear-both">
	<div class="grid grid-cols-3 gap-4 pt-4 text-gray-600 text-center">
	
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
				<?php echo esc_html( $total_subscribed ); ?>
			</span>
			<div class="inline-block es-new-subscriber-growth-percentage es-tooltip relative align-middle cursor-pointer text-left">
				<?php
				if ( 0 !== $growth_percentage ) {
					$text_color_class = '';
					$arraw_html       = '';
					if ( $growth_percentage < 0 ) {
						$text_color_class = 'text-red-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
					} else {
						$text_color_class = 'text-green-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
					}
					?>
					<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
						<?php echo esc_html( number_format_i18n( $growth_percentage, 2 ) ); ?>%
						<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
					</span>
					<?php
				}
				?>
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
				<?php echo esc_html__( 'New subscribers', 'email-subscribers' ); ?>
			</p>
		</div>
		<div class="pt-1 pr-1 pl-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
				<?php echo esc_html( $total_unsubscribed ); ?>
			</span>
			<div class="inline-block es-tooltip relative align-middle cursor-pointer  text-left">
				<?php
				if ( 0 !== $unsubscribe_percentage_growth ) {
					$text_color_class = '';
					$arraw_html       = '';
					if ( $unsubscribe_percentage_growth > 0 ) {
						$text_color_class = 'text-red-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
					} else {
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
						$text_color_class = 'text-green-600';
					}
					?>
					<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
						<?php echo esc_html( $unsubscribe_percentage_growth ); ?>%
						<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
					</span>
					<?php
				}
				?>
				<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
					<div class="text-white-100">
						<div>
							<span class="text-lg text-base">
								<?php echo esc_html__( 'Unsubscribes', 'email-subscribers' ); ?>:
								<?php echo esc_html( $unsubscribe_before_two_months ); ?>
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
				<?php
				if ( ES()->is_pro() ) {
					?>
					<a id="view_unsubscribe_feedback_button" class="pb-2 pl-1 leading-5" style="cursor: pointer;" href="#">
						<?php echo esc_html__( 'Unsubscribed', 'email-subscribers' ); ?>
					</a>
					<?php
				} else {
					?>
					<?php echo esc_html__( 'Unsubscribed', 'email-subscribers' ); ?>
					<?php
				}
				?>
			</p>
			<?php do_action( 'ig_es_show_unsubscribe_feedback_reasons_stats', $days, $total_unsubscribed ); ?>
		</div>
		<div class="p-1">
			<?php
				do_action( 'ig_es_show_bounced_contacts_stats', $days, $reports_data );
			?>
		</div>
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
				<?php echo esc_html( $total_message_sent ); ?>
			</span>
			<div class="inline-block es-tooltip relative align-middle cursor-pointer text-left">
				<?php
				if ( 0 !== $sent_percentage_growth ) {
					$text_color_class = '';
					$arraw_html       = '';
					if ( $sent_percentage_growth < 0 ) {
						$text_color_class = 'text-red-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
					} else {
						$text_color_class = 'text-green-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
					}
					?>
					<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
						<?php echo esc_html( $sent_percentage_growth ); ?>%
						<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
					</span>
					<?php
				}
				?>
				<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
					<div class="text-white-100">
						<div>
							<span class="text-lg text-base">
								<?php echo esc_html__( 'Sent', 'email-subscribers' ); ?>:
								<?php echo esc_html( $sent_before_two_months ); ?>
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
				<?php echo esc_html__( 'Sent', 'email-subscribers' ); ?>
			</p>
		</div>
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
				<?php echo esc_html( $total_email_opens ); ?>
			</span>
			<div class="inline-block es-tooltip relative align-middle cursor-pointer text-left">
				<?php
				if ( 0 !== $open_percentage_growth ) {
					$text_color_class = '';
					$arraw_html       = '';
					if ( $open_percentage_growth < 0 ) {
						$text_color_class = 'text-red-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
					} else {
						$text_color_class = 'text-green-600';
						$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
					}
					?>
					<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
						<?php echo esc_html( $open_percentage_growth ); ?>%
						<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
					</span>
					<?php
				}
				?>
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
			<div id="es-dashboard-click-stats">
				<span class="text-2xl font-bold leading-none text-indigo-600">
					<?php echo esc_html(  $total_links_clicks ); ?>
				</span>
				<div class="inline-block es-tooltip relative align-middle cursor-pointer text-left">
					<?php
					if ( 0 !== $click_percentage_growth ) {
						$text_color_class = '';
						$arraw_html       = '';
						if ( $click_percentage_growth < 0 ) {
							$text_color_class = 'text-red-600';
							$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
						} else {
							$text_color_class = 'text-green-600';
							$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
						}
						?>
						<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
							<?php echo esc_html( $click_percentage_growth ); ?>%
							<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
						</span>
						<?php
					}
					?>
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
							<span class="premium-icon inline-block max"></span>
						</a>
						<?php
					}
					?>
													</p>
			</div>
		</div>
	</div>
</div>
