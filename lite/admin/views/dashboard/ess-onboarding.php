<div id="sending-service-benefits" class="pr-6 pl-6 w-full <?php echo 1 !== $ess_onboarding_step || 'yes' === $ess_optin ? 'hidden' : ''; ?>">
	<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
		<span class="leading-7">
			<?php echo esc_html__( 'Use Icegram Email Sending Service', 'email-subscribers' ); ?>
		</span>
	</p>
	<div class="step-1  block-description" style="width: calc(100% - 4rem)">
		<ul class="py-3 space-y-2 text-sm font-medium leading-5 text-gray-400">
			<li class="flex items-start group">
				<div class="item-dots">
					<span></span>
				</div>
				<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'Start with 3000 free emails / month', 'email-subscribers' ); ?></p></li>
			<li class="flex items-start group">
				<div class="item-dots">
					<span></span>
				</div>
				<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'High speed email sending', 'email-subscribers' ); ?></p>
			</li>
			<li class="flex items-start group">
				<div class="item-dots">
					<span></span>
				</div>
				<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
				<?php echo esc_html__( 'Reliable email delivery', 'email-subscribers' ); ?>
				</p>
			</li>
		</ul>
		<a id="ig-ess-optin-cta" href="#" class="mt-6">
			<button type="button" class="lighter-gray">
				<?php echo esc_html__( 'Activate for free', 'email-subscribers' ); ?> &rarr;
			</button>
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
			<?php echo esc_html__( 'Excellent! Activating Free Plan', 'email-subscribers' ); ?>
		</span>
	</p>
	
	<ul class="pt-2 pb-1 space-y-2 text-sm font-medium leading-5 text-gray-400 pt-2">
		<li id="ig-es-onboard-create_ess_account" class="flex items-start space-x-3 group">
			<div class="item-dots">
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
			class="item-dots"
			>
			<span
				class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
			></span>
			</div>
			<p class="text-sm"><?php echo esc_html__( 'Sending a test email', 'email-subscribers' ); ?></p>
		</li>
		<li id="ig-es-onboard-check_test_email_on_server" class="flex items-start space-x-3 group">
			<div
			class="item-dots"
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
	<a id="ig-es-complete-ess-onboarding" href="#" class="mt-6 <?php echo 2 === $ess_onboarding_step ? '' : 'opacity-50 pointer-events-none'; ?>">
		<button type="button" class="lighter-gray">
			<span class="button-text inline-block mr-1">
			<?php echo esc_html__( 'Processing...', 'email-subscribers' ); ?>
			</span>
			<svg style="display:none" class="es-btn-loader h-4 w-4 text-white-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
			</svg>
		</button>
	</a>
</div>
