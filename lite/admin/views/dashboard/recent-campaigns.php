<?php
global $wpdb;
$campaign_url = admin_url( 'admin.php?page=es_campaigns' );
if ( ! empty( $campaigns ) && count( $campaigns ) > 0 ) { ?>
	<table class="mt-2 w-full bg-white rounded-md overflow-hidden " style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
		<thead>
		    <th class="text-center text-sm font-medium leading-6 text-gray-400 "><?php echo esc_html__( 'Name', 'email-subscribers' ); ?></th>
			<th class="text-right text-sm font-medium leading-6 text-gray-400"><?php echo esc_html__( 'Sent', 'email-subscribers' ); ?></th>
			<th class="text-right text-sm font-medium leading-6 text-gray-400"><?php echo esc_html__( 'Opens', 'email-subscribers' ); ?></th>
			<th class="text-right text-sm font-medium leading-6 text-gray-400"><?php echo esc_html__( 'Clicks', 'email-subscribers' ); ?></th>
			<th class="text-right text-sm font-medium leading-6 text-gray-400"><?php echo esc_html__( 'Actions', 'email-subscribers' ); ?></th>
		</thead>
		<tbody>
			<?php
			$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
			if ( ! empty( $campaigns ) ) {
				foreach ( $campaigns as $campaign_id => $campaign ) {
					$campaign_kpi = ES_Dashboard::get_recent_campaigns_kpis( $campaign['id'] );
					if( !empty( $campaign['name'] ) ) {
					?>
					<tr>
						<td class="avatar-column">
							<div class="avatar">
								<?php
								if ( IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign['type'] ) {
									$img_name = 'broadcast';
								} elseif ( IG_CAMPAIGN_TYPE_WORKFLOW === $campaign['type'] ) {
									$img_name = 'sequences';
								} elseif ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign['type'] ) {
									$img_name = 'notification';
								} elseif( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign['type'] ) {
									$img_name = 'notification';
								}

								if( !empty($img_name) ) {
									?>
									<div class='dash-avatar'>
									<img src="<?php echo esc_url( ES_PLUGIN_URL . '/lite/admin/images/new/' . esc_attr( $img_name ) . '.svg' ); ?>" alt="">
								    </div>
									<?php
								} ?>
							</div>
							<div class="font-medium">
								<?php echo "<a class='dash-recent-p es-ellipsis-text' href='admin.php?page=es_campaigns#!/campaign/edit/" . esc_html( $campaign['id'] ) . "' target='_blank'>" . esc_html( $campaign['name'] ) . '</a>'; ?>
							</div>
						</td>
						<td class="text-right"><?php echo ( $campaign_kpi['total_email_sent'] ) ? esc_html( $campaign_kpi['total_email_sent'] ) : '0'; ?></td>
						<td class="text-right"><?php echo esc_html( $campaign_kpi['open_rate'] ) . '%'; ?></td>
						<td class="text-right"><?php echo esc_html( $campaign_kpi['click_rate'] ) . '%'; ?></td>
						<td class="pl-1 py-3 text-gray-600 text-right"> 
							<div>
							<?php
							if ( IG_ES_CAMPAIGN_STATUS_ACTIVE  === (int) $campaign['status']) { 
								echo wp_kses("<p class='text-green-600 font-medium dash-status'><span class='bg-green-600 dashboard_dot'></span> Active</p>", $allowed_html_tags);
							} elseif (IG_ES_CAMPAIGN_STATUS_IN_ACTIVE === (int) $campaign['status']) {
								echo wp_kses("<p class='text-indigo-600 font-medium dash-status'><span class='bg-indigo-600 dashboard_dot'></span>Draft</p>", $allowed_html_tags);
							}
							?>
							</div>
						</td>
					</tr>
					<?php
					}
				}
			} else {
				?>
				<tr><td><?php echo esc_html__( 'No audience activities found.', 'email-subscribers' ); ?></td></tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php 
} else {
	?>
	<p class="px-2 py-2 text-sm leading-5 text-gray-900">
		<?php echo esc_html__( 'There is no active or draft campaign found.', 'email-subscribers' ); ?>
	</p>
	<a href="<?php echo esc_url( $campaign_url ); ?>" class="primary">
		<button type="button" class="primary">
			<span>
				<?php echo esc_html__( 'Create new campaign', 'email-subscribers' ); ?>
			</span>
		</button>
	</a>
	<?php
}
?>
