<div id="sending-service-benefits" class="pr-6 pl-6 w-full">
	<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
		<span class="leading-7">
			<?php echo sprintf( esc_html__( 'Supercharge your emails with our %1$sIcegram Mailer%2$s plugin!', 'email-subscribers' ), '<a class="text-indigo-600" target="_blank" href="https://wordpress.org/plugins/icegram-mailer/">', '</a>') ; ?>
		</span>
	</p>
	<div class="step-1  block-description" style="width: calc(100% - 4rem)">
		<ul class="py-3 space-y-2 text-sm font-medium leading-5 text-gray-400">
			<li class="flex items-start group">
				<div class="item-dots">
					<span></span>
				</div>
				<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'Start with 200 free emails / month', 'email-subscribers' ); ?></p></li>
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
<div id="sending-service-onboarding-tasks-list" class="pr-6 pl-6 w-full hidden">
	<p class="pb-3 text-lg font-medium leading-6 text-gray-400">
		<span class="leading-7">
			<?php echo esc_html__( 'Excellent! Activating Icegram mailer plugin', 'email-subscribers' ); ?>
		</span>
	</p>
	
	<ul class="pt-2 pb-1 space-y-2 text-sm font-medium leading-5 text-gray-400 pt-2">
		<li id="ig-es-onboard-install_mailer_plugin" class="flex items-start space-x-3 group">
			<div class="item-dots">
			<span class="animate-ping absolute w-4 h-4 bg-indigo-200 rounded-full"></span>
			<span class="relative block w-2 h-2 bg-indigo-700 rounded-full"></span>
			</div>
			<p class="text-sm text-indigo-800">
			<?php
			/* translators: 1: Main List 2: Test List */
			echo sprintf( esc_html__( 'Installing...', 'email-subscribers' ), esc_html( IG_MAIN_LIST ), esc_html( IG_DEFAULT_LIST ) );
			?>
			</p>
		</li>
		<li id="ig-es-onboard-activate_mailer_plugin" class="flex items-start space-x-3 group">
			<div
			class="item-dots"
			>
			<span
				class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
			></span>
			</div>
			<p class="text-sm"><?php echo esc_html__( 'Activating...', 'email-subscribers' ); ?></p>
		</li>
		<li id="ig-es-onboard-redirect_to_mailer_plugin_dashboard" class="flex items-start space-x-3 group">
			<div
			class="item-dots"
			>
			<span
				class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
			></span>
			</div>
			<p class="text-sm">
				<?php echo esc_html__( 'Redirecting...', 'email-subscribers' ); ?>
			</p>
		</li>
	</ul>
	<a id="ig-es-complete-ess-onboarding" href="#" class="mt-6">
		<button type="button" class="lighter-gray">
			<span class="button-text inline-block mr-1">
			<?php echo esc_html__( 'Processing', 'email-subscribers' ); ?>
			</span>
		</button>
	</a>
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
