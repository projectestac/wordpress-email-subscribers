<table id="top-countries" class="mt-2 w-full bg-white rounded-md overflow-hidden" style="<?php echo ! empty( $upsell ) ? 'filter:blur(1px);' : ''; ?>">
	<thead>
		<tr>
			<th class="w-1/3 px-4 py-3 font-bold text-center" colspan="2">
				<span class="font-">
					<?php echo esc_html__( 'Country', 'email-subscribers' ); ?>
				</span>
			</th>
			<th class="w-1/3 px-1 py-3 font-bold text-right">
				<span>
					<?php echo esc_html__( 'Subscribers', 'email-subscribers' ); ?>
				</span>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php
		if ( ! empty( $top_countries ) ) {
			$countries = ES_Geolocation::get_countries();
			foreach ( $top_countries as $country_code => $total_subscribers ) {
				if ( 'others' === $country_code ) {
					$country_name = __('Others', 'email-subscribers');
				} else {
					$country_name = ! empty( $countries[ $country_code ] ) ? $countries[ $country_code ] : '';
				}
				?>
				<tr class="border-b border-gray-200 text-xs leading-5">
					<td class="mx-4 my-3 px-1 py-1 country_flag column-country_flag">
						<p id="es-flag-icon" class="ml-0 xl:ml-1.5 inline-block leading-5 flag-icon flag-icon-<?php echo esc_attr( strtolower( $country_code ) ); ?>"></p>
					</td>
					<td class="pl-4 py-3 text-gray-500 text-left">
						<span>
							<?php echo esc_html( $country_name ); ?>
						</span>
					</td>
					<td class="pl-1 py-3 text-gray-600 text-right">
						<span>
							<?php echo esc_html( number_format_i18n( $total_subscribers ) ); ?>
						</span>
					</td>
				</tr>
				<?php
			}
		} else {
			?>
			<tr>
				<td colspan="3">
					<span>
						<?php echo esc_html__( 'No country data found.', 'email-subscribers' ); ?>
					</span>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
