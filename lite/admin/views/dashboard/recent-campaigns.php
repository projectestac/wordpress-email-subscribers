<?php

if ( ! empty( $campaigns ) && count( $campaigns ) > 0 ) { ?>
	<ul>
		<?php 
		$is_pro = ES()->is_pro();
		foreach ( $campaigns as $campaign_id => $campaign ) {

			$reports_url = add_query_arg( 'list', $campaign['hash'], add_query_arg( 'action', 'view', admin_url( 'admin.php?page=es_reports' ) ) );
			?>

			<li 
			<?php 
			if ( count($campaigns) - 1 != $campaign_id ) {
				?>
					class="border-b border-gray-200" <?php } ?>>
				<a href="<?php echo esc_url( $reports_url ); ?>" class="block py-3 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition duration-150 ease-in-out" target="_blank">
					<div class="flex items-center px-3">
						<div class="w-3/5 min-w-0 flex-1">
							<div class="items-center text-sm ">
								<span class="inline-block leading-5 flex items-start text-gray-500">
									<svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
										<?php 
										if ( in_array( $campaign['campaign_type'], array( 'newsletter' ), true ) ) {
											?>
										<path fill-rule="evenodd" d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884zM18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" clip-rule="evenodd"/>
									</svg>
									<?php } elseif ( in_array( $campaign['campaign_type'], array( 'post_notification', 'post_digest' ) ) ) { ?>
										<path d="M7,0A6,6,0,0,0,1,6V9.59l-.71.7a1,1,0,0,0-.21,1.09A1,1,0,0,0,1,12H13a1,1,0,0,0,.92-.62,1,1,0,0,0-.21-1.09L13,9.59V6A6,6,0,0,0,7,0Z"/>
										<path d="M7,16a3,3,0,0,1-3-3h6A3,3,0,0,1,7,16Z"/>
										</svg>
									<?php } ?>
									<span class="inline-block mr-1"><?php echo esc_html( $campaign['type'] ); ?></span>
									<?php
									if ( 'Sent' === $campaign['status'] ) {
										?>
										<svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
										</svg>
										<span><?php echo esc_html__( 'Sent on', 'email-subscribers' ); ?>
										<time datetime=""> <?php echo esc_html( $campaign['finish_at'] ); ?> </time>
									</span>
										<?php 
									} elseif ( 'In Queue' === $campaign['status'] ) {
										?>
									<svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
										<span><?php echo esc_html__( 'In Queue', 'email-subscribers' ); ?>
									</span>
										<?php } elseif ( ( 'Sending' === $campaign['status'] ) ) { ?>
										<svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
											<span><?php echo esc_html__( 'Started at', 'email-subscribers' ); ?>
										<time datetime=""><?php echo esc_html( $campaign['start_at'] ); ?>  </time>
									</span>
											<?php 
										}
										?>
								</span>
							</div>
							<div class="text-base mt-2 pr-4">
								<?php echo esc_html( $campaign['title'] ); ?>
							</div>
						</div>
						<?php
						if ( $is_pro ) {
							?>
							<div class="sm:grid sm:grid-cols-4 flex-1">
							<?php
						} else {
							?>
							<div class="sm:grid sm:grid-cols-3 flex-1">
							<?php
						}
						?>
						<div class="p-3">
										<span class="leading-none text-sm text-indigo-500">
											<?php echo esc_html( number_format_i18n( $campaign['total_sent'] ) ); ?>
										</span>
								<p class="mt-1 leading-6 text-gray-400">
									<?php echo esc_html__( 'Sent', 'email-subscribers' ); ?>
								</p>
							</div>
							<div class="p-3">
										<span class="leading-none text-sm text-indigo-500">
											<?php echo esc_html( number_format_i18n( $campaign['total_opens'] ) ); ?> (
														<?php
														echo esc_html( $campaign['campaign_opens_rate'] ) 
														?>
											%)
										</span>
								<p class="mt-1 leading-6 text-gray-400">
									<?php echo esc_html__( 'Opens', 'email-subscribers' ); ?>
								</p>
							</div>
							<?php
							if ( ES()->is_pro() ) {
								?>
								<div class="p-3">
											<span class="leading-none text-sm text-indigo-500">
													<?php echo esc_html( number_format_i18n( $campaign['total_clicks'] ) ); ?> (
																<?php
																echo esc_html( $campaign['campaign_clicks_rate'] ) 
																?>
												%)
													</span>
									<p class="mt-1 leading-6 text-gray-400">
										<?php echo esc_html__( 'Clicks', 'email-subscribers' ); ?>
									</p>
								</div>
								<?php
							}
							?>
							<div class="p-3">
										<span class="leading-none text-sm text-indigo-500">
												<?php echo esc_html( number_format_i18n( $campaign['total_unsubscribe'] ) ); ?> (
															<?php
															echo esc_html( $campaign['campaign_losts_rate'] ) 
															?>
											%)
												</span>
								<p class="mt-1 leading-6 text-gray-400">
									<?php echo esc_html__( 'Unsubscribes', 'email-subscribers' ); ?>
								</p>
							</div>
						</div>
					</div>
				</a>
			</li>
			<?php
		}
		?>
	</ul>
<?php 
} else {
	$campaign_url = admin_url( 'admin.php?page=es_campaigns' );
	?>
	<p class="px-2 py-2 text-sm leading-5 text-gray-900">
		<?php echo esc_html__( 'No recent campaigns were found.', 'email-subscribers' ); ?>
	</p>
	<a href="<?php echo esc_url( $campaign_url ); ?>" class="inline-flex justify-center py-1 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-1 lg:px-3 xl:px-3 ml-2">
		<span>
			<?php echo esc_html__( 'Create Campaign', 'email-subscribers' ); ?>
		</span>
	</a>
	<?php
}

