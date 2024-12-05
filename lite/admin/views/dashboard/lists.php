<?php
$list_url = admin_url( 'admin.php?page=es_lists&action=new' );
?>
<table class="mt-2 w-full bg-white rounded-md overflow-hidden" style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
	<tbody>
		<?php
		$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
		if ( ! empty( $lists ) ) {
			foreach ( $lists as $lists_key => $list ) {
				?>
				<tr>
					<td class="py-3 text-gray-500">
						<span>
							<a href="?page=es_lists&action=edit&list=<?php echo esc_attr($list['id']); ?>" target="_blank">
							<?php echo esc_html__( $list['name'], 'email-subscribers' ); ?>
							</a>
						</span>
					</td>
					<td class="max-w-10 text-right">
						<?php 
						$list_subscriber_count = ES()->lists_contacts_db->get_total_count_by_list( $list['id'], 'subscribed' );
						echo number_format( $list_subscriber_count );
						?>
					</td>
				</tr>
				<?php
			}
		} else {
			?>
			<tr><td><?php echo esc_html__( 'No Lists found.', 'email-subscribers' ); ?></td></tr>
			<?php
		}
		?>
	</tbody>
</table>
