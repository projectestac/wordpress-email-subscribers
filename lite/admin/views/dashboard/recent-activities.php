<table id="recent-subscribers-activities" class="mt-2 w-full bg-white rounded-md overflow-hidden" style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
	<tbody>
		<?php
		$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
		if ( ! empty( $recent_activities ) ) {
			foreach ( $recent_activities as $activitiy ) {
				?>
				<tr class="border-b border-gray-200 text-sm leading-5">
					<td class="py-3 text-gray-500">
						<span>
							<?php echo wp_kses( $activitiy['text'], $allowed_html_tags ); ?>
						</span>
					</td>
					<td class="pl-1 py-3 text-gray-600 text-right">
						<span>
							<?php echo esc_html( $activitiy['time'] ); ?>
						</span>
					</td>
				</tr>
				<?php
			}
		} else {
			?>
			<tr><td><?php echo esc_html__( 'No recent activities found.', 'email-subscribers' ); ?></td></tr>
			<?php
		}
		?>
	</tbody>
</table>
