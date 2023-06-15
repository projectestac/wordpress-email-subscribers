<div id="sending-service-benefits" class="pr-6 pl-6 w-full <?php echo 1 !== $ess_onboarding_step || 'yes' === $ess_optin ? 'hidden' : ''; ?>">
	<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
		<span class="leading-7">
			<?php echo esc_html__( 'Use our Email Sending Service', 'email-subscribers' ); ?>
		</span>
	</p>
	<img class="absolute bottom-0 right-0 w-24 -mr-3 " src="<?php echo esc_url( ES_PLUGIN_URL . '/lite/admin/images/dashboard-send-newsletter.png' ); ?>">
	<div class="step-1  block-description" style="width: calc(100% - 4rem)">
		<ul class="pt-2 pb-1 space-y-2 text-sm font-medium leading-5 text-gray-400">
			<li class="flex items-start space-x-3 group">
				<div class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5">
					<span class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"></span>
				</div>
				<p class="xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'Start with 100 free emails / day', 'email-subscribers' ); ?></p></li>
			<li class="flex items-start space-x-3 group">
				<div class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5">
					<span
					class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
				></span>
				</div>
				<p class="xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'High speed email sending', 'email-subscribers' ); ?></p>
			</li>
			<li class="flex items-start space-x-3 group">
				<div class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5">
					<span
						class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
					></span>
				</div>
				<p class="xl:pr-3 2xl:pr-0 text-sm text-gray-500">
				<?php echo esc_html__( 'Reliable email delivery', 'email-subscribers' ); ?>
				</p>
			</li>
		</ul>
		<a id="ig-ess-optin-cta" href="#" class="inline-flex justify-center px-1.5 py-1 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4 mt-6">
			<?php echo esc_html__( 'Let\'s start →', 'email-subscribers' ); ?>
		</a>
	</div>
</div>
<?php
if ( 'yes' === $ess_optin ) {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			setTimeout(function(){
				jQuery('#ig-ess-optin-cta').trigger('click');
			},1000);
		});
	</script>
	<?php
}
?>
<div id="sending-service-onboarding-tasks-list" class="pr-6 pl-6 w-full <?php echo 2 !== $ess_onboarding_step && 'yes' !== $ess_optin ? 'hidden' : ''; ?>">
	<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
		<span class="leading-7">
			<?php echo esc_html__( 'Excellent choice!', 'email-subscribers' ); ?>
		</span>
	</p>
	<img class="absolute bottom-0 right-0 w-24 -mr-3 " src="<?php echo esc_url( ES_PLUGIN_URL . '/lite/admin/images/dashboard-send-newsletter.png' ); ?>">
	<ul class="pt-2 pb-1 space-y-2 text-sm font-medium leading-5 text-gray-400 pt-2">
		<li id="ig-es-onboard-create_ess_account" class="flex items-start space-x-3 group">
			<div class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5">
			<span class="animate-ping absolute w-4 h-4 bg-indigo-200 rounded-full"></span>
			<span class="relative block w-2 h-2 bg-indigo-700 rounded-full"></span>
			</div>
			<p class="text-sm text-indigo-800">
			<?php
			/* translators: 1: Main List 2: Test List */
			echo sprintf( esc_html__( 'Creating your account', 'email-subscribers' ), esc_html( IG_MAIN_LIST ), esc_html( IG_DEFAULT_LIST ) );
			?>
			</p>
		</li>
		<li id="ig-es-onboard-dispatch_emails_from_server" class="flex items-start space-x-3 group">
			<div
			class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
			>
			<span
				class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
			></span>
			</div>
			<p class="text-sm"><?php echo esc_html__( 'Sending a test email', 'email-subscribers' ); ?></p>
		</li>
		<li id="ig-es-onboard-check_test_email_on_server" class="flex items-start space-x-3 group">
			<div
			class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
			>
			<span
				class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
			></span>
			</div>
			<p class="text-sm">
				<?php echo esc_html__( 'Confirming email delivery', 'email-subscribers' ); ?>
			</p>
		</li>
	</ul>
	<a id="ig-es-complete-ess-onboarding" href="#" class="inline-flex justify-center px-1.5 py-1 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4 mt-6 <?php echo 2 === $ess_onboarding_step ? '' : 'opacity-50 pointer-events-none'; ?>">
		<span class="button-text inline-block mr-1">
		<?php echo esc_html__( 'Processing', 'email-subscribers' ); ?>
		</span>
		<span class="es-btn-arrow"> → </span>
		<svg style="display:none" class="es-btn-loader h-4 w-4 text-white-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
				<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
				<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
		</svg>
	</a>
</div>
