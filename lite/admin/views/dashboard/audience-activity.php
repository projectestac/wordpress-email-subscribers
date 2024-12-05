<?php
global $wpdb;
$import_url = admin_url( 'admin.php?page=es_subscribers&action=import' );
?>
<table style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
	<tbody> 
		<?php
		$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
		if ( ! empty( $audience_activity ) ) {
			foreach ( $audience_activity as $activitiy_key => $activitiy ) {
				?>
				<tr>
					<td class="py-3 text-gray-500">
						<span class="es-ellipsis-text">
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
			<tr><td><?php echo esc_html__( 'You don\'t have active subscribers yet. Start by importing new subscribers.', 'email-subscribers' ); ?></td></tr>
			<?php
		}
		?>
	</tbody>
</table>
