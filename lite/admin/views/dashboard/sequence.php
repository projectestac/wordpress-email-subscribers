<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
	<span>
	<?php
		echo esc_html__( 'Auto-responder sequence', 'email-subscribers' );
	?>
	<?php
	if ( ! empty( $upsell ) ) {
		$utm_args = array(
			'utm_medium' => 'dashboard-sequence-stat',
			'url'		 => 'https://www.icegram.com/documentation/email-sequence/'
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
		if ( ! empty( $sequence_messages ) ) {
			?>
			<select id="filter_by_sequence">
				<?php
				foreach ( $sequence_messages as $message ) {
					$message_subject = $message['subject'];
					?>
					<option value="<?php echo esc_html( $message['id'] ); ?>">
						<?php
							echo esc_html( $message_subject );
						?>
					</option>
					<?php
				}
				?>
			</select>
			<?php
		}
		?>
	</span>
	<?php
	}
	?>
</p>
<?php
if ( ! empty( $upsell ) ) {
	$sequence_stats = array(
		'sent'             => 0,
		'opens'            => 0,
		'clicks'           => 0,
		'unsubscribes'     => 0,
		'open_rate'        => 0,
		'click_rate'       => 0,
		'unsubscribe_rate' => 0
	);
	ES_Admin::get_view(
		'dashboard/sequence-stats',
		array(
			'sequence_stats' => $sequence_stats,
			'upsell'         => true,
		)
	);
} else {
	if ( ! empty( $sequence_messages ) ) {
		$first_message_id = $sequence_messages[0]['id'];
		do_action( 'ig_es_show_sequence_message_stats', $first_message_id );
	} else {
		?>
		<p>
		<?php
		$new_sequence_url = admin_url( 'admin.php?page=es_sequence&action=new' );
		echo esc_html__( 'No auto-responder sequence found.', 'email-subscribers' );
		/* translators: %s: Create new sequence link */
		echo ' ' . sprintf( esc_html__( 'Click %1$shere%2$s to create new.', 'email-subscribers' ), '<a href="' . esc_url( $new_sequence_url ) . '" target="_blank">', '</a>' );
		?>
		</p>
		<?php
	}
}

