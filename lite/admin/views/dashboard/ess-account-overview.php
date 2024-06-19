<div id="ig-ess-account-overview-block" class="relative pr-6 pl-6 w-full">
	<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
		<span class="leading-7">
			<?php
				echo esc_html__( 'Email Sending Service status', 'email-subscribers' );
			?>
		</span>
		<?php
		if ( 'success' === $service_status ) {
			?>
			<svg xmlns="http://www.w3.org/2000/svg" class="inline-block ml-1 h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_attr__( 'Email Sending Service working fine', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
			</svg>
			<?php
		} else {
			?>
			<svg xmlns="http://www.w3.org/2000/svg" class="inline-block ml-1 h-5 w-5 text-red-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
				<title><?php echo esc_html__( 'Error in email sending.', 'email-subscribers' ); ?></title>
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
			</svg>
			<?php
		}
		?>
	</p>
	<div id="subscribers-stats" class="clear-both">
		<div class="stats-info">
			<div class="p-1">
				<div class="md:flex md:items-center">
					<span class="text-2xl font-bold leading-none text-indigo-600 pr-1">
						<?php echo esc_html( $allocated_limit ); ?>
					</span>
					<span class="pt-2"><?php echo esc_html( ' / ' . substr($interval, 0, 2) ); ?></span>
				</div>
				<p class="mt-1 font-medium leading-6 text-gray-500">
					<?php
						echo esc_html__( 'Allocated limit', 'email-subscribers' );
					?>
				</p>
			</div>
			<div class="p-1">
				<span class="text-2xl font-bold leading-none text-indigo-600">
					<?php echo esc_html( $used_limit ); ?>			
				</span>
				<span class="es-open-percentage-growth text-2xl font-bold leading-none text-indigo-600">
					<p class="mt-1 font-medium leading-6 text-gray-500">
						<?php echo esc_html__( 'Used', 'email-subscribers' ); ?>
					</p>
				</span>
			</div>
			<div class="p-1">
				<div id="es-dashboard-click-stats">
					<span class="text-2xl font-bold leading-none text-indigo-600">
						<?php echo esc_html( $allocated_limit - $used_limit ); ?>
					</span>
					<p class="mt-1 font-medium leading-6 text-gray-500">
						<?php echo esc_html__( 'Remaining', 'email-subscribers' ); ?>						
					</p>
				</div>
			</div>
		</div>
		<p class="xl:pr-3 2xl:pr-0 text-sm text-gray-500">
			<?php
				/* translators: Mailer name name */
				echo sprintf( esc_html__( 'Emails beyond the first %1$d will be sent through %2$s.', 'email-subscribers' ), esc_html( $allocated_limit ), '<a href="' . esc_url( $settings_url ) . '" target="_blank">' . esc_html( $current_mailer_name ) . '</a>' );
			?>
		</p>
		<p class="py-3 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
			<?php
				echo esc_html__( 'If you have more email to send for a month consider our premium plans of Email Sending', 'email-subscribers' );
			?>
		</p>
		<a href="https://www.icegram.com/email-sending-service/?utm_source=es&utm_medium=dashboard&utm_campaign=revamp-01" target="_blank" class=" mt-3">
			<button type="button" class="primary">
				<?php echo esc_html( 'Upgrade', 'email-subscribers' ); ?> &rarr;
			</button>
		</a>
	</div>
</div>
