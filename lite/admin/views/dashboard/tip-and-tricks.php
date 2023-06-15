<?php
$manage_templates_url = admin_url( 'admin.php?page=es_gallery&manage-templates=yes' );
$reports_url          = admin_url( 'admin.php?page=es_reports' );
$settings_url         = admin_url( 'admin.php?page=es_settings' );
$topics_indexes       = array_rand( $topics, 4 );
?>
<ul>
	<!-- Start-IG-Code -->
	<?php foreach ( $topics_indexes as $index ) { ?>
		<li class="border-b border-gray-200 mb-0">
			<a href="<?php echo esc_url( $topics[ $index ]['link'] ); ?>" class="block transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:bg-gray-50" target="_blank">

				<div class="flex items-center pr-2 py-2 md:justify-between">
					<div class="text-sm leading-5 text-gray-900">
						<?php
						echo wp_kses_post( $topics[ $index ]['title'] );
						if ( ! empty( $topics[ $index ]['label'] ) ) {
							?>
							<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo esc_attr( $topics[ $index ]['label_class'] ); ?>"><?php echo esc_html( $topics[ $index ]['label'] ); ?></span>
						<?php } ?>
					</div>
					<div>
						<svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
						</svg>
					</div>
				</div>
			</a>
		</li>
	<?php } ?>
	<!-- End-IG-Code -->
	<li class="">
		<div class="pr-2 py-2 text-sm leading-5 text-gray-900 sm:pr-2">
			<?php echo esc_html__( 'Jump to: ', 'email-subscribers' ); ?>
			<a href="<?php echo esc_url( $reports_url ); ?>" class="font-bold" target="_blank">
				<?php echo esc_html__( 'Reports', 'email-subscribers' ); ?>
			</a>
			・
			<a href="<?php echo esc_url( $manage_templates_url ); ?>" class="font-bold" target="_blank">
				<?php echo esc_html__( 'Templates', 'email-subscribers' ); ?>
			</a>
			・
			<a href="<?php echo esc_url( $settings_url ); ?>" class="font-bold" target="_blank">
				<?php echo esc_html__( 'Settings', 'email-subscribers' ); ?>
			</a>
		</div>
	</li>
</ul>
