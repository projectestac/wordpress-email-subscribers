<?php
if ( ! empty( $sequence_stats ) ) {
	?>
	<div id="sequence-stats" class="grid grid-cols-4 gap-4 py-4 text-gray-600 text-center" style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
				<?php echo esc_html( $sequence_stats['sent'] ); ?>
			</span>
			<p class="mt-1 font-medium leading-6 text-gray-500">
				<?php echo esc_html__( 'Sent', 'email-subscribers' ); ?>
			</p>
		</div>
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
				<?php echo esc_html( $sequence_stats['opens'] ); ?>
			</span>
			<span class="text-sm mr-0.5">
				<?php echo esc_html( $sequence_stats['open_rate'] ); ?>%
			</span>
			<p class="mt-1 font-medium leading-6 text-gray-500">
				<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>
			</p>
		</div>
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
					<?php echo esc_html( $sequence_stats['clicks'] ); ?>
			</span>
			<span class="text-sm mr-0.5">
				<?php echo esc_html( $sequence_stats['click_rate'] ); ?>%
			</span>
			<p class="mt-1 font-medium leading-6 text-gray-500">
				<?php echo esc_html__( 'Clicks', 'email-subscribers' ); ?>
			</p>
		</div>
		<div class="p-1">
			<span class="text-2xl font-bold leading-none text-indigo-600">
					<?php echo esc_html( $sequence_stats['unsubscribes'] ); ?>
			</span>
			<span class="text-sm mr-0.5">
				<?php echo esc_html( $sequence_stats['unsubscribe_rate'] ); ?>%
			</span>
			<p class="mt-1 font-medium leading-6 text-gray-500">
				<?php echo esc_html__( 'Unsubscribes', 'email-subscribers' ); ?>
			</p>
		</div>
	</div>
	<?php
}
