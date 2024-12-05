<?php
$admin_name = wp_get_current_user();
$admin_email = get_option( 'admin_email' );
$trial_expiry_date           = ES()->trial->get_trial_expiry_date();
$formatted_trial_expiry_date = ig_es_format_date_time( $trial_expiry_date );
?>
<div id="ig-es-trial-optin-section" class="hidden">
	<form id="ig-es-trial-optin-form" method="post">
		<?php wp_nonce_field( 'ig-es-trial-optin-nonce', 'ig_es_trial_optin_nonce' ); ?>
		<h3 class="pb-3 text-lg font-medium leading-6 text-gray-400">
			<?php echo esc_html__( 'Premium - Activating Free Trial...', 'email-subscribers' ); ?>
		</h3>
		<div class="py-3">
			<div class="py-2 space-y-2 text-sm">
				<div class="w-full">
					<label><?php echo esc_html__( 'Name', 'email-subscribers' ); ?></label>
					<input
						id="ig-es-sign-up-name"
						class="block w-full mt-1 text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
						placeholder="<?php echo esc_html__('Your name', 'email-subscribers' ); ?>" value="<?php echo esc_attr( $admin_name->user_login ); ?>"
					/>
				</div>
			</div>
			<div class="py-2 space-y-2 text-sm">
				<div class="w-full">
				<label><?php echo esc_html__( 'Email', 'email-subscribers' ); ?></label>
					<input
					type="email"
						id="ig-es-sign-up-email"
						class="es_onboard_email block w-full mt-1 text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
						placeholder="<?php echo esc_html__('Your email', 'email-subscribers' ); ?>"
						value="<?php echo esc_attr( $admin_email ); ?>"
					/>
				</div>
			</div>
			<div class="py-2 space-y-2 text-sm">
				<button id="ig-es-trial-optin-btn" type="button" class="lighter-gray ig-es-inline-loader inline-flex mt-2 text-sm inline-block">
					<span>
						<?php echo esc_html__( 'Continue', 'email-subscribers' ); ?> &rarr;
					</span>
					<svg class="es-btn-loader"
									xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle cx="12" cy="12" r="10" stroke="currentColor"
								stroke-width="4"></circle>
						<path fill="currentColor"
								d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
				</button>
			</div>
		</div>
	</form>
	<div id="ig-es-trial-optin-success-block" class="hidden">
		<p class="sec-title">
			<span>
			<?php echo esc_html__( 'Icegram Express - Premium', 'email-subscribers' ); ?>
			</span>
			
			<span class="block-status"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'Icegram Express - Premium Activated', 'email-subscribers'); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
			</svg> &nbsp; <?php echo esc_html__( 'Free trial', 'email-subscribers' ); ?> &nbsp;</span>
		</p>
		
		<div class="pt-1 space-y-2 text-sm">
			<div class="w-full">
				<p class="pt-3 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
					<?php
						/* translators: 1: Trial period in days. */
						echo esc_html__(
							sprintf(
								'Your free trial of Premium plan is active till %s.',
								$formatted_trial_expiry_date
							),
							'email-subscribers'
						);
						?>
				</p>
				<p class="py-3 mt-3 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
					<?php echo esc_html__('To keep using all the powerful email marketing features beyond the trial, switch to a paid plan. 94% users who take up our paid plans are highly satisfied.', 'email-subscribers'); ?>
				</p>
			</div>
		</div>
		<a href="https://www.icegram.com/express/pricing/" target="_blank">
			<button id="ig-es-trial-optin-btn" type="button" class="lighter-gray ig-es-inline-loader inline-flex mt-2 text-sm inline-block">
				<span>
					<?php echo esc_html__( 'Yes, Choose a plan', 'email-subscribers' ); ?> &rarr;
				</span>
			</button>
		</a>
	</div>
	<div id="ig-es-trial-optin-error-block" class="hidden">
		<div class="pt-1 space-y-2 text-sm">
			<div class="w-full">
				<p id="error-message-text" class="text-sm leading-5 text-red-500">
					<?php echo esc_html__( 'Something went wrong. Please try again later.', 'email-subscribers' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#ig-es-trial-optin-cta').on('click', function(e) {
			e.preventDefault();
			$('#ig-es-trial-optin-block').removeClass('bg-teal-100').addClass('bg-white').html('');
			$('#ig-es-trial-optin-section').removeClass('hidden').detach().appendTo('#ig-es-trial-optin-block');
		});
		$('#ig-es-trial-optin-btn').click(function(){
			let btn_elem = $(this);

			let name  = $('#ig-es-sign-up-name').val();
			let email = $('#ig-es-sign-up-email').val();
			let security = $('#ig_es_trial_optin_nonce').val();

			let data  = {
				action: 'ig_es_trial_optin',
				name: name,
				email: email,
				security: security,
			};
			jQuery.ajax({
				method: 'POST',
				url: ajaxurl,
				data: data,
				dataType: 'json',
				beforeSend: function() {
					$(btn_elem).attr('disabled', 'disabled').addClass('loading');
				},
				success: function (response) {
					if (response.success) {
						$('#ig-es-trial-optin-form,#ig-es-trial-optin-error-block').addClass('hidden');
						$('#ig-es-trial-optin-success-block').removeClass('hidden');

						//$('#ig-es-trial-optin-block').removeClass('bg-white').addClass('bg-teal-100');
					} else {
						if ( response.data.message_text ) {
							$('#error-message-text').text(response.data.message_text);
						}
						$('#ig-es-trial-optin-error-block').removeClass('hidden');
					}
				},
				error: function (err) {
					alert(ig_es_js_data.i18n_data.ajax_error_message);
				}
			}).always(function(){
				$(btn_elem).removeAttr('disabled', 'disabled').removeClass('loading');
			});
		});
	});
</script>
