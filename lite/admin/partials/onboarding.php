<?php
	$onboarding_step = IG_ES_Onboarding::get_onboarding_step();
	$current_year    = gmdate( 'Y' );
	$admin_email     = get_option( 'admin_email' );
	$from_name       = get_option( 'ig_es_from_name' );
	$from_email      = get_option( 'ig_es_from_email' );
?>
<!-- Start-IG-Code -->
<div class="mx-auto mt-6 sm:mt-5">
	<img src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/icegram_logo.svg' ); ?>" class="mx-auto h-7" alt="Icegram" />
</div>
<!-- End-IG-Code -->
<div id="slider-wrapper font-sans">
	<div id="slider">
		<div class="sp es-send-email-screen<?php echo esc_attr( 1 === $onboarding_step ? ' active' : '' ); ?>" style="<?php echo esc_attr( 1 === $onboarding_step ? '' : 'display:none' ); ?>">

			<section class="mx-auto my-6 sm:my-7">
			  <div
				class="w-full h-full overflow-hidden bg-white lg:flex md:rounded-lg md:shadow-xl md:mx-auto lg:max-w-3xl xl:max-w-4xl"
			  >
				<div class="relative hidden w-1/4 overflow-hidden bg-blue-300 lg:block">
				  <svg
					class="absolute object-cover w-full top-8"
					viewBox="0 0 128 172"
					xmlns="http://www.w3.org/2000/svg"
				  >
					<g fill="none" stroke="#fff" stroke-width="20">
					  <circle cx="64" cy="84" opacity=".15" r="70"></circle>
					  <circle cx="64" cy="84" opacity=".15" r="35"></circle>
					</g>
				  </svg>
				  <img
					src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/anne-email-suggestion.svg' ); ?>"
					class="absolute object-cover w-full px-3 bottom-2"
				  />
				</div>

				<div class="flex-1">
				  <div class="p-4 md:px-8 md:py-5">
					<span class="text-xs text-gray-400"><?php echo esc_html__( 'STEP 1 of 3', 'email-subscribers' ); ?> </span>
					<h3
					  class="mb-1 text-2xl font-bold leading-snug text-gray-800 sm:text-3xl"
					>
						<?php echo esc_html__( 'Welcome!', 'email-subscribers' ); ?>
					</h3>
					<form id="es-send-email-form">
					<div class="space-y-5 text-gray-800">
					 <p class="text-base -mb-2">
						<?php
						echo esc_html__(
							'We\'ve simplified and automated email marketing, so you can get
		                results quickly.',
							'email-subscribers'
						);
						?>
					  </p>

					  <div class="space-y-1">
						<h3 class="text-base font-medium text-gray-900"><?php echo esc_html__( 'Essentials:', 'email-subscribers' ); ?></h3>

						<div
						  class="space-y-2 text-sm sm:space-y-0 sm:space-x-4 sm:flex sm:items-center"
						>

						  <div class="w-full sm:w-1/2">
							<label for="es_from_name"><?php echo esc_html__( '"From" name for emails: ', 'email-subscribers' ); ?></label>
							<input
							  id="es_from_name" name="es_from_name" value="<?php echo esc_attr( $from_name ); ?>" required
							  class="es_from_name block w-full mt-1 text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
							  placeholder="Name" autocomplete="nope"
							/>
						  </div>

						  <div class="w-full sm:w-1/2">
							<label for="es_from_email"><?php echo esc_html__( '"From" email: ', 'email-subscribers' ); ?></label>
							<input type="email"
							  id="es_from_email" name="es_from_email" value="<?php echo esc_attr( $from_email ); ?>" required
							  class="es_from_email es_onboard_email block w-full mt-1 text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
							  placeholder="name@domain.com" autocomplete="nope"
							/>
						  </div>
						</div>
					  </div>
					  <div class="">
						<h3 class="text-base font-medium text-gray-900">
							<?php echo esc_html__( 'Email delivery testing:', 'email-subscribers' ); ?>
						</h3>

						<p class="text-sm leading-6 pt-1">
							<?php
							echo esc_html__(
								'Add a couple of your own email addresses below. We will add
		                  them to your audience lists.',
								'email-subscribers'
							);
							?>
						</p>
						<div
						  class="my-2 space-y-2 sm:my-0 sm:space-y-0 sm:space-x-4 sm:flex sm:items-center"
						>
						  <div class="w-full sm:w-1/2">
							<input type="email"
							  name="es_test_email[]"
							  required
							  class="es_email es_onboard_email block w-full text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
							  placeholder="name@domain.com" autocomplete="nope"
							/>
						  </div>
						  <div class="w-full sm:w-1/2">
							<input type="email"
							  name="es_test_email[]"
							  class="es_email es_onboard_email block w-full text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
							  placeholder="name@domain.com" autocomplete="nope"
							/>
						  </div>
						</div>
					  </div>

					  <div class="space-y-1 leading-5">
						<h3 class="text-base font-medium text-gray-900 -mb-0.5"><?php echo esc_html__( 'Your preferences:', 'email-subscribers' ); ?></h3>
						<!-- Start-IG-Code -->
						<?php if ( ! ES()->is_premium() ) { ?>
							<div class="flex pt-1">
								<div class="pt-1">
									<input
									id="es_allow_tracking"
									type="checkbox"
									checked="checked"
									class="w-4 h-4 transition duration-150 ease-in-out form-checkbox"
									/>
								</div>
								<div class="pl-3">
									<label for="es_allow_tracking" class="text-sm">
									<?php
										/* translators: %s. Plugin name. */
										echo sprintf( esc_html__( 'Help us to improve %s by opting in to share non-sensitive plugin usage data. No personal data is tracked or stored.', 'email-subscribers' ), '<strong>Icegram Express</strong>' );
									?>
									</label>
								</div>
							</div>
						<?php } ?>

						<div class="flex">
						  <div class="pt-1">
							<input
							  id="es_post_notification_preference"
							  type="checkbox"
							  checked="checked"
							  class="w-4 h-4 transition duration-150 ease-in-out form-checkbox"
							/>
						  </div>
						  <div class="pl-3">
							<label for="es_post_notification_preference" class="text-sm">
								<?php
								echo esc_html__(
									'I want to send email notifications when new blog posts are
		                      published',
									'email-subscribers'
								);
								?>
							</label>
						  </div>
						</div>
						<!-- End-IG-Code -->
						<div class="flex">
						  <div class="pt-1">
							<input
							  id="ig_es_enable_double_optin"
							  type="checkbox"
							  checked="checked"
							  class="w-4 h-4 transition duration-150 ease-in-out form-checkbox"
							/>
						  </div>
						  <div class="pl-3">
							<label for="ig_es_enable_double_optin" class="text-sm">
								<?php
								echo esc_html__(
									'Enable double opt-in (people have to click a confirmation
		                      link in email before they\'re subscribed)',
									'email-subscribers'
								);
								?>
							</label>
						  </div>
						</div>
						<div class="flex">
						  <div class="pt-1">
							<input
							  id="ig_es_add_gdpr_consent"
							  type="checkbox"
							  class="w-4 h-4 transition duration-150 ease-in-out form-checkbox"
							/>
						  </div>
						  <div class="pl-3">
							<label for="ig_es_add_gdpr_consent" class="text-sm">
								<?php echo esc_html__( 'Add GDPR consent in subscription forms', 'email-subscribers' ); ?>
							</label>
						  </div>
						</div>
					  </div>
					</div>
					</form>
				  </div>
				  <div class="px-4 py-3 text-right bg-gray-50 md:px-8 -mt-5">
					<button
					  type="button" id="es-button-send"
					  class="es-button-send relative inline-flex items-center px-4 py-2 text-base font-medium leading-5 text-white bg-indigo-800 border border-transparent rounded-md hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
					>
						<?php echo esc_html__( 'Ok, set it up for me →', 'email-subscribers' ); ?>
					</button>
				  </div>
				</div>
			  </div>
			</section>
		</div>
		<div class="sp es-delivery-check<?php echo esc_attr( 2 === $onboarding_step ? ' active' : '' ); ?>" style="<?php echo esc_attr( 2 === $onboarding_step ? '' : 'display:none' ); ?>">
			<section class="mx-auto my-6 sm:my-7">
			  <div
				class="w-full h-full overflow-hidden bg-white lg:flex md:rounded-lg md:shadow-xl md:mx-auto lg:max-w-3xl xl:max-w-4xl">
				<div class="relative hidden w-1/4 overflow-hidden bg-pink-200 lg:block">
				  <svg
					class="absolute object-cover w-full animate animate-pulse top-8"
					viewBox="0 0 128 172"
					xmlns="http://www.w3.org/2000/svg"
				  >
					<g fill="none" stroke="#fff" stroke-width="20">
					  <circle cx="64" cy="84" opacity=".25" r="70"></circle>
					  <circle cx="64" cy="84" opacity=".15" r="35"></circle>
					</g>
				  </svg>
				  <img
					src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/anne-working.svg' ); ?>"
					class="absolute object-cover w-full px-3 bottom-2"
				  />
				</div>

				<div class="flex-1">
				  <div style="height:33.2rem" class="p-4 md:px-8 md:py-5">
					<span class="text-xs text-gray-400"><?php echo esc_html__( 'STEP 2 of 3', 'email-subscribers' ); ?> </span>
					<h3
					  class="mb-2 text-2xl font-bold leading-snug text-gray-800 sm:text-3xl"
					>
						<?php echo esc_html__( 'Hold on, personalizing for you...', 'email-subscribers' ); ?>
					</h3>
					<div class="space-y-4 text-gray-800">
					  <p class="text-base">
						<?php
						echo esc_html__(
							'We\'ll create audience lists, campaigns and a subscription form.
		                And then try to send a test email to make sure everything works.',
							'email-subscribers'
						);
						?>
					  </p>
					  <ul class="space-y-4 text-sm font-medium leading-5 text-gray-400">
						<li id="ig-es-onboard-create_default_lists" class="flex items-start space-x-3 group">
						  <div class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5">
							<span class="animate-ping absolute w-4 h-4 bg-indigo-200 rounded-full"></span>
							<span class="relative block w-2 h-2 bg-indigo-700 rounded-full"></span>
						  </div>
						  <p class="text-sm text-indigo-800">
							<?php
							/* translators: 1: Main List 2: Test List */
							echo sprintf( esc_html__( 'Creating audience lists - %1$s &amp; %2$s', 'email-subscribers' ), esc_html( IG_MAIN_LIST ), esc_html( IG_DEFAULT_LIST ) );
							?>
						  </p>
						</li>

						<li id="ig-es-onboard-create_contacts_and_add_to_list" class="flex items-start space-x-3 group">
						  <div
							class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
						  >
							<span
							  class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
						  </div>
						  <p class="text-sm">
							<?php echo esc_html__( 'Subscribing you and ', 'email-subscribers' ); ?>
							<span id="es_onboarding_emails_list"></span>
							<?php echo esc_html__( ' to these lists', 'email-subscribers' ); ?>
						  </p>
						</li>

						<li id="ig-es-onboard-create_default_newsletter_broadcast" class="flex items-start space-x-3 group">
						  <div
							class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
						  >
							<span
							  class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
						  </div>
						  <p class="text-sm"><?php echo esc_html__( 'Creating a campaign - newsletter broadcast test', 'email-subscribers' ); ?></p>
						</li>

						<!-- Start-IG-Code -->
						<li id="ig-es-onboard-create_default_post_notification" class="flex items-start space-x-3 group" id="es_create_post_notification">
						  <div
							class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
						  >
							<span
							  class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
						  </div>
						  <p class="text-sm"><?php echo esc_html__( 'Creating a campaign - new post notification test', 'email-subscribers' ); ?></p>
						</li>
						<!-- End-IG-Code -->

						<li id="ig-es-onboard-create_default_subscription_form" class="flex items-start space-x-3 group">
						  <div
							class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
						  >
							<span
							  class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
						  </div>
						  <p class="text-sm"><?php echo esc_html__( 'Creating a subscription opt-in form for the Main list', 'email-subscribers' ); ?></p>
						</li>

						<li id="ig-es-onboard-add_widget_to_sidebar" class="flex items-start space-x-3 group">
						  <div
							class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"
						  >
							<span
							  class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
						  </div>
						  <p class="text-sm">
							<?php
							echo esc_html__(
								'Adding the form to an active sidebar, so you can show it on
		                    the site',
								'email-subscribers'
							);
							?>
						  </p>
						</li>
					</ul>
					</div>
				</div>
				<div class="px-4 py-3 text-right bg-gray-50 md:px-8">
					<button
					type="button"
					id="es_delivery_check_processed"
					class="relative inline-flex items-center px-4 py-2 text-base font-medium leading-5 text-white bg-indigo-800 border border-transparent rounded-md hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
					data-error-text="<?php echo esc_attr__( 'Continue anyway →', 'email-subscribers' ); ?>"
					>
					<?php echo esc_html__( 'All good, let\'s finish up →', 'email-subscribers' ); ?>
					</button>
				</div>
			</div>
			</section>
		</div>
		<div class="sp es-onboard-success<?php echo esc_attr( 3 === $onboarding_step ? ' active' : '' ); ?>" style="<?php echo esc_attr( 3 === $onboarding_step ? '' : 'display:none' ); ?>">
			<section class="mx-auto my-6 sm:my-7" >
			  <div class="w-full overflow-hidden bg-white lg:flex md:rounded-lg md:shadow-xl md:mx-auto lg:max-w-3xl xl:max-w-4xl">
				<div class="relative hidden w-1/4 overflow-hidden bg-green-300 lg:block">
				  <svg
					class="absolute object-cover w-full text-white top-8"
					fill="none"
					stroke="currentColor"
					viewBox="0 0 24 24"
					xmlns="http://www.w3.org/2000/svg"
				  >
					<path
					  stroke-linecap="round"
					  stroke-linejoin="round"
					  stroke-width="2.5"
					  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
					  opacity="0.25"
					></path>
				  </svg>

				  <img
					src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/anne-welcome.svg' ); ?>"
					class="absolute object-cover w-full px-3 lg:top-1/2 xl:top-1/3"
				  />
				</div>

				<div class="flex-1">
					<form id="ig-es-onboarding-final-steps-form">
						<div style="height:33.2rem" class="p-4 md:px-8 md:py-5">
							<span class="text-xs text-gray-400"><?php echo esc_html__( 'STEP 3 of 3', 'email-subscribers' ); ?> </span>
							<h3
							  class="mb-2 text-2xl font-bold leading-snug text-gray-800 sm:text-3xl"
							>
								<?php echo esc_html__( 'Done! Now speed up your success!', 'email-subscribers' ); ?>
							</h3>
							<input type="hidden"  id="sign-up-list" name="list[]" value="<?php echo esc_attr( ES()->get_es_optin_list_hash() ); ?>"/>
							<input type="hidden" id="sign-up-form-source" name="form-source" value="es-onboarding"/>
							<div class="space-y-3 text-gray-800">
								<div class="space-y-5 text-gray-800">
							  <p class="text-base -mb-1"><?php echo esc_html__( 'Setup is complete. Couple of things to support you...', 'email-subscribers' ); ?>
							  </p>
							  <!-- Start-IG-Code -->
							  <div class="">
								<?php
								if ( ! ES()->is_premium() ) {
									?>
																		
									<p class="pt-2 text-sm leading-6">
										<?php
											/* translators: %d. Current year */
											echo sprintf( esc_html__( 'Get free WordPress Email Marketing Masterclass %d Course and grow your audience.', 'email-subscribers' ), esc_html( $current_year ) );
										?>
									</p>
									<?php
								} else {
									?>
									<h3 class="text-base font-medium text-gray-900">
										<?php
											/* translators: %d. Current year */
											echo sprintf( esc_html__( 'Free course: WordPress Email Marketing Masterclass %d', 'email-subscribers' ), esc_html( $current_year ) );
										?>
									</h3>
									<p class="pt-2 text-sm leading-6">
										<?php
										echo esc_html__(
											'How to build your list, make sure your email reach your
									audience and influence your audience.',
											'email-subscribers'
										);
										?>
									</p>
									<?php
								}
								?>
									<div
									class="pt-1 space-y-2 text-sm sm:space-y-0 sm:space-x-4 sm:flex sm:items-center"
									>
								  <div class="w-full sm:w-1/2">
									<input
									  id="ig-es-sign-up-name"
									  class="block w-full mt-1 text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
									  placeholder="<?php echo esc_html__('Your name', 'email-subscribers' ); ?>"
									/>
								  </div>

								  <div class="w-full sm:w-1/2">
									<input
									type="email"
									  id="ig-es-sign-up-email"
									  class="es_onboard_email block w-full mt-1 text-sm transition duration-150 ease-in-out rounded-md shadow-sm form-input sm:leading-5"
									  placeholder="<?php echo esc_html__('Your email', 'email-subscribers' ); ?>"
									  value="<?php echo esc_attr( $admin_email ); ?>"
									/>
								  </div>
								</div>
							  </div>


							  <!-- End-IG-Code -->

							  <div class="space-y-1">
								<h3 class="text-base font-medium text-gray-900 pt-2">
									<?php echo esc_html__( 'Recommended next steps:', 'email-subscribers' ); ?>
								</h3>
								<ul class="ml-4 space-y-2 text-sm list-disc pt-1.5">
								  <li><?php echo esc_html__( 'Review "Settings" and make adjustments if needed', 'email-subscribers' ); ?></li>
								  <li><?php echo esc_html__( 'Import your contacts, create new campaigns and test', 'email-subscribers' ); ?></li>
								  <!-- Start-IG-Code -->
								  <li>
									<?php echo esc_html__( 'Review', 'email-subscribers' ); ?>
									<a
									  class="text-indigo-800 hover:underline"
									  href="https://www.icegram.com/knowledgebase_category/email-subscribers/"
									  target="_blank"
									  ><?php echo esc_html__( 'documentation', 'email-subscribers' ); ?></a
									>
									<?php echo esc_html__( 'if you need any help', 'email-subscribers' ); ?>
								  </li>
								  <!-- End-IG-Code -->
								</ul>
							  </div>
							</div>
						   </div>
						</div>
					</form>
				  <div class="px-4 py-3 text-right bg-gray-50 md:px-8">
					<button
					  type="button" id="ig-es-complete-onboarding"
					  class="relative inline-flex items-center px-4 py-2 text-base font-medium leading-5 text-white bg-indigo-800 border border-transparent rounded-md hover:bg-indigo-600 focus:outline-none focus:shadow-outline">
						<span class="mr-1"><?php echo esc_html__( 'Complete setup &amp; take me to "Dashboard" ', 'email-subscribers' ); ?></span>
						<span class="es-btn-arrow"> → </span>
						<svg style="display:none" class="es-btn-loader h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
								<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
						</svg>
					</button>
				  </div>
				</div>
			   </div>
			</section>
		  </div>

		<div class="sp es-popup-message" style="display:none">
			<div class="fixed flex inset-0 overflow-x-hidden overflow-y-auto z-50 flex justify-center w-full h-full" style="background-color: rgba(0,0,0,.5);">
				<section class="absolute flex justify-center mx-auto md:mx-auto lg:mx-auto my-12 sm:my-12 lg:my-24">
				  <div
					class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
					role="dialog"
					aria-modal="true"
					aria-labelledby="modal-headline"
				  >
					<div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
						  <div class="sm:flex sm:items-start">
							<div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
							  <svg
								class="w-6 h-6 text-red-600"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							  >
								<path
								  stroke-linecap="round"
								  stroke-linejoin="round"
								  stroke-width="2"
								  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
								></path>
							  </svg>
							</div>
							<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
								  <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
									<?php echo esc_html__( 'Email sending did not work', 'email-subscribers' ); ?>
								</h3>
								  <div class="mt-2 space-y-4 leading-5 text-gray-800">
									<div>
									  <p class="font-medium text-sm text-gray-900">
										<?php echo esc_html__( 'Here\'s the error we encountered:', 'email-subscribers' ); ?>
									  </p>
									  <div class="px-1 py-0.5 text-sm font-mono message">
										[error-message]
									  </div>
									</div>
									<p class="additional-message text-sm">

									</p>
									<p class="error-message font-medium text-sm">
										<?php
										echo esc_html__(
											'We recommend you solve this problem quickly after completing
					                  the setup. Do make sure emails are getting delivered before
					                  you send any real campaigns.',
											'email-subscribers'
										);
										?>
									</p>
								  </div>
							</div>
						  </div>
					</div>
					<div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
					  <span class="flex w-full rounded-md shadow-sm sm:ml-3 sm:w-auto">
						<button
						  type="button"
						  id="es-delivery-error-button"
						  class="es-delivery-error-button relative inline-flex items-center px-4 py-2 text-base font-medium leading-5 text-white bg-indigo-800 border border-transparent rounded-md hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
						>
							<?php echo esc_html__( ' Understood, continue for now →', 'email-subscribers' ); ?>
						</button>
					  </span>
					</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</div>
