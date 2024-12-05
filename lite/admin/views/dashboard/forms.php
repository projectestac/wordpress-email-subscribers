<?php
global $wpdb;
$form_url = admin_url( 'admin.php?page=es_forms&action=new' );
?>
<table class="mt-2 w-full bg-white rounded-md overflow-hidden" style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
	<tbody>
		<?php
		$allowed_html_tags = ig_es_allowed_html_tags_in_esc();
		if ( ! empty( $forms ) ) {
			foreach ( $forms as $forms_key => $forms_val ) {
				?>
				<tr>
					<td class="py-3 text-gray-500">
						<span>
							<a href="?page=es_forms&action=edit&form=<?php echo esc_attr($forms_val['id']); ?>" target="_blank">
								<?php echo esc_html__( $forms_val['name'], 'email-subscribers' ); ?>
							</a>
						</span>
					</td>
					<td class="text-right">
						<?php
						echo number_format( ES()->contacts_db->get_total_contacts_by_form_id( $forms_val['id'] ) );
						?>
					</td>
				</tr>
				<?php
			}
		} else {
			?>
			<tr><td><?php echo esc_html__( 'No form found.', 'email-subscribers' ); ?>
			<?php
		}
		?>
	</tbody>
</table>
