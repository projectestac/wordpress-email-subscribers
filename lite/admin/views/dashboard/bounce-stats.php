<?php
$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
?>
<span class="text-2xl font-bold leading-none text-indigo-600">
	<?php echo esc_html( $total_hard_bounced_contacts ); ?>
</span>
<div class="inline-block es-tooltip relative align-middle cursor-pointer text-left">
	<?php
	if ( 0 !== $bounces_percentage_growth ) {
		if ( $bounces_percentage_growth > 0 ) {
			$text_color_class = 'text-red-600';
			$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
		} else {
			$arraw_html       = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
			$text_color_class = 'text-green-600';
		}
		?>
		<span class="text-sm mr-0.5 <?php echo esc_attr( $text_color_class ); ?>">
			<?php echo esc_html( $bounces_percentage_growth ); ?>%
			<?php echo wp_kses( $arraw_html, $allowed_html_tags ); ?>
		</span>
		<?php
	} 
	?>
	<span class="break-words invisible h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
		<div class="text-white-100">
			<div>
				<span class="text-lg text-base">
					<?php echo esc_html__( 'Hard bounces', 'email-subscribers' ); ?>:
					<?php echo esc_html( $bounces_before_two_months ); ?>
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
	<?php echo esc_html__( 'Bounced', 'email-subscribers' ); ?>
	<?php
	if ( ! empty( $upsell ) ) {
		$utm_args = array(
			'utm_medium' => 'dashboard-bounce-stat',
			'url'		 => 'https://www.icegram.com/documentation/how-to-handle-bounced-email-addresses-in-email-subscribers/'
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
