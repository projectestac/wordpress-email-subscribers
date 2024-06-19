<?php
global $wpdb;
$form_url = admin_url( 'admin.php?page=es_forms&action=new' );
$list_url = admin_url( 'admin.php?page=es_lists&action=new' );
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
						<code class="es-code">[email-subscribers-form id="<?php echo esc_attr($forms_val['id']); ?>"]</code>
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

<a href="<?php echo esc_url( $form_url ); ?>">
	<button type="button" class="primary mt-2">
		<span>
			<?php echo esc_html__( 'Create new form', 'email-subscribers' ); ?>
		</span>
	</button>
</a>

<div class="flex items-center pr-2 py-2 md:justify-between">
	<p class="text-lg font-medium leading-6 text-gray-400">
		<?php echo esc_html__( 'Lists', 'email-subscribers' ); ?>
	</p>
	<p class="es_helper_text">
		<a class="hover:underline text-indigo-600 font-semibold" href="https://www.icegram.com/docs/category/icegram-express/audience-subscription-lists-statuses#lists?utm_source=es&utm_medium=in_app&utm_campaign=dashboard_help" target="_blank">
			<?php echo esc_html__('How to create Lists?', 'email-subscribers'); ?>
		</a>
	</p>
</div>
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
					<td class="max-w-10 text-right short-desc">
						<span class="es_helper_text"><?php echo esc_html__($list['description'], 'email-subscribers'); ?>
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

<a href="<?php echo esc_url( $list_url ); ?>">
	<button type="button" class="primary mt-2">
		<span>
			<?php echo esc_html__( 'Create new list', 'email-subscribers' ); ?>
		</span>
	</button>
</a>
