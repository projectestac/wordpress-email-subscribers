<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Form_Admin' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Admin Settings
	 *
	 * @package    Email_Subscribers
	 * @subpackage Email_Subscribers/admin
	 */
	class ES_Form_Admin {
		// class instance
		public static $instance;
		
		// class constructor
		public function __construct() {
			$this->init();
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function init() {
			$this->register_hooks();
		}

		public function register_hooks() {
			add_action( 'wp_ajax_ig_es_get_form_preview', array( $this, 'get_form_preview' ) );
			add_action( 'ig_es_render_dnd_form', array( $this, 'render_dnd_form' ), 10, 2 );
			add_action( 'ig_es_render_classic_form', array( $this, 'render_classic_form' ), 10, 2 );
		}

		public function render_classic_form( $id, $data ) {

			$is_new = empty( $id ) ? 1 : 0;

			$action = 'new';
			if ( ! $is_new ) {
				$action = 'edit';
			}

			$form_data['name']               = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
			$form_data['name_visible']       = ! empty( $data['name_visible'] ) ? sanitize_text_field( $data['name_visible'] ) : 'no';
			$form_data['name_required']      = ! empty( $data['name_required'] ) ? sanitize_text_field( $data['name_required'] ) : 'no';
			$form_data['name_label']         = ! empty( $data['name_label'] ) ? sanitize_text_field( $data['name_label'] ) : '';
			$form_data['name_place_holder']  = ! empty( $data['name_place_holder'] ) ? sanitize_text_field( $data['name_place_holder'] ) : '';
			$form_data['email_label']        = ! empty( $data['email_label'] ) ? sanitize_text_field( $data['email_label'] ) : '';
			$form_data['email_place_holder'] = ! empty( $data['email_place_holder'] ) ? sanitize_text_field( $data['email_place_holder'] ) : '';
			$form_data['button_label']       = ! empty( $data['button_label'] ) ? sanitize_text_field( $data['button_label'] ) : __( 'Subscribe', 'email-subscribers' );
			$form_data['list_visible']       = ! empty( $data['list_visible'] ) ? $data['list_visible'] : 'no';
			$form_data['gdpr_consent']       = ! empty( $data['gdpr_consent'] ) ? $data['gdpr_consent'] : 'no';
			$form_data['gdpr_consent_text']  = ! empty( $data['gdpr_consent_text'] ) ? $data['gdpr_consent_text'] : __( 'Please read our <a href="https://www.example.com">terms and conditions</a>', 'email-subscribers' );
			$form_data['list_label']         = ! empty( $data['list_label'] ) ? $data['list_label'] : '';
			$form_data['lists']              = ! empty( $data['lists'] ) ? $data['lists'] : array();
			$form_data['af_id']              = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;
			$form_data['desc']               = ! empty( $data['desc'] ) ? wp_kses_post( trim( wp_unslash( $data['desc'] ) ) ) : '';
			$form_data['captcha']            = ES_Common::get_captcha_setting( 0, $data );
			$form_data['show_in_popup']      = ! empty( $data['show_in_popup'] ) ? $data['show_in_popup'] : 'no';
			$form_data['popup_headline']     = ! empty( $data['popup_headline'] ) ? $data['popup_headline'] : '';

			$lists = ES()->lists_db->get_list_id_name_map();
			$nonce = wp_create_nonce( 'es_form' );

			?>

			<div class="max-w-full -mt-3 font-sans">
				<header class="wp-heading-inline">
					<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
						<div class="flex">
							<div class="w-1/2">
								<ol class="list-none p-0 inline-flex">
									<li class="flex items-center text-sm tracking-wide">
										<a class="hover:underline" href="admin.php?page=es_forms"><?php esc_html_e( 'Forms ', 'email-subscribers' ); ?></a>
										<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
									</li>
								</ol>
							</div>
						</div>
					</nav>
					<div class="flex">
						<div class="w-1/2">
							<h2 class="-mt-1 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate">
								<?php
								if ( $is_new ) {
									esc_html_e( ' New Form', 'email-subscribers' );
								} else {
									esc_html_e( ' Edit Form', 'email-subscribers' );
								}

								?>
							</h2>
						</div>
						<div class="w-1/2 -mt-2.5 inline text-right">
							<a class="px-1.5 py-2 mt-2 es-documentation" href="https://www.icegram.com/documentation/how-to-create-a-form-in-email-subscribers/?utm_source=in_app&utm_medium=es_forms&utm_campaign=es_doc" target="_blank">
								<svg class="w-6 h-6 -mt-1 inline text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
									<title><?php esc_html_e( 'Documentation ', 'email-subscribers' ); ?></title>
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
								</svg>
							</a>
						</div>
					</header>
					<div class=""><hr class="wp-header-end"></div>
					<div id="poststuff">
						<div id="post-body" class="metabox-holder column-1">
							<div id="post-body-content" class="pt-0.5">
								<div class="bg-white shadow-md rounded-lg mt-5 pt-1">
									<form class="pt-8 ml-5 mr-4 text-left mt-2 item-center " method="post" action="admin.php?page=es_forms&action=<?php echo esc_attr( $action ); ?>&form=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>">
										<div class="flex flex-row border-b border-gray-100">
											<div class="flex w-1/5">
												<div class="ml-4 pt-6">
													<label for="tag-link"><span class="block ml-4 pt-1 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Form name', 'email-subscribers' ); ?></span></label>
												</div>
											</div>
											<div class="flex">
												<div class="ml-16 mb-4 h-10 mr-4 mt-4">
													<div class="h-10 relative">
														<input id="ig_es_title" class="form-input block border-gray-400 w-full pl-3 pr-12 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" placeholder="<?php echo esc_html__( 'Enter form name', 'email-subscribers' ); ?>"  name="form_data[name]" value="<?php echo esc_html( stripslashes( $form_data['name'] ) ); ?>" size="30" maxlength="100"/>
													</div>
												</div>
											</div>
										</div>
										<div class="flex flex-row border-b border-gray-100">
											<div class="flex w-1/5">
												<div class="ml-4 pt-6">
													<label for="tag-link"><span class="block pt-1 ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Description', 'email-subscribers' ); ?></span></label>
												</div>
											</div>
											<div class="flex ">
												<div class="ml-16 mb-4 h-10 mr-4 mt-4">
													<div class="h-10 relative ">
														<input id="ig_es_title" class="form-input block border-gray-400 w-full pl-3 pr-12 shadow-sm focus:bg-gray-100 sm:text-sm sm:leading-5" placeholder="<?php echo esc_html__( 'Enter description', 'email-subscribers' ); ?>"  name="form_data[desc]" id="ig_es_title" value="<?php echo esc_html( stripslashes( $form_data['desc'] ) ); ?>" size="30" />
													</div>
												</div>
											</div>
										</div>
										<div class="flex flex-row border-b border-gray-100">
											<div class="flex w-1/5">
												<div class="ml-4 pt-4 mb-2">
													<label for="tag-link"><span class="block ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Form fields', 'email-subscribers' ); ?></span></label>
												</div>
											</div>
											<div class="flex ">
												<div class="ml-16 mr-4 mt-4">
													<table class="ig-es-form-table">
														<tr class="form-field">
															<td class="pr-6 pb-8"><b class=" font-medium text-gray-500 pb-2"><?php esc_html_e( 'Field', 'email-subscribers' ); ?></b></td>
															<td class="pr-6 pb-8"><b class=" font-medium text-gray-500 pb-2"><?php esc_html_e( 'Show?', 'email-subscribers' ); ?></b></td>
															<td class="pr-6 pb-8"><b class=" font-medium text-gray-500 pb-2"><?php esc_html_e( 'Required?', 'email-subscribers' ); ?></b></td>
															<td class="pr-6 pb-8"><b class=" font-medium text-gray-500 pb-2"><?php esc_html_e( 'Label', 'email-subscribers' ); ?></b></td>
															<td class="pr-6 pb-8"><b class="font-medium text-gray-500 pb-2"><?php esc_html_e( 'Placeholder', 'email-subscribers' ); ?></b></td>
														</tr>
														<tr class="form-field ">
															<td class="pr-6 pb-8"><b class="text-gray-500 text-sm font-normal pb-2"><?php esc_html_e( 'Email', 'email-subscribers' ); ?></b></td>
															<td class="pr-6 pb-8">
																<input type="checkbox" class="form-checkbox opacity-0"  name="form_data[email_visible]" value="yes" disabled="disabled" checked="checked" />
															</td>


															<td class="pr-6 pb-8">
																<input type="checkbox" class="form-checkbox opacity-0" name="form_data[email_required]" value="yes" disabled="disabled" checked="checked"></td>

																<td class="pr-6 pb-8">
																	<input class="form-input block border-gray-400 w-5/6 pr-12 h-8 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" name="form_data[email_label]" value="<?php echo esc_attr( $form_data['email_label'] ); ?>">
																</td>
																<td class="pr-6 pb-8">
																	<input class="form-input block border-gray-400 w-5/6 pr-12 h-8 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" name="form_data[email_place_holder]" value="<?php echo esc_attr( $form_data['email_place_holder'] ); ?>">
																</td>
															</tr>
															<tr class="form-field">
																<td class="pr-6 pb-8"><b class="text-gray-500 text-sm font-normal pb-2"><?php esc_html_e( 'Name', 'email-subscribers' ); ?></b></td>

																<td class="pr-6 pb-8">
																	<input type="checkbox" class="form-checkbox es_visible" name="form_data[name_visible]" value="yes"
																	<?php
																	if ( 'yes' === $form_data['name_visible'] ) {
																		echo 'checked="checked"';
																	}
																	?>
																	/>
																</td>
																<td class="pr-6 pb-8">
																	<input type="checkbox" class="form-checkbox es_required" name="form_data[name_required]" value="yes"
																	<?php
																	if ( 'yes' === $form_data['name_required'] ) {
																		echo 'checked="checked"';
																	}
																	?>
																	/>
																</td>
																<td class="pr-6 pb-8"><input class="es_name_label form-input block border-gray-400 w-5/6 pr-12 h-8 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" name="form_data[name_label]" value="<?php echo esc_attr( $form_data['name_label'] ); ?>"
																	<?php
																	if ( 'yes' === $form_data['name_required'] ) {
																		echo 'disabled=disabled';
																	}
																	?>
																	></td>
																	<td class="pr-6 pb-8"><input class="es_name_label form-input block border-gray-400 w-5/6 pr-12 h-8 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" name="form_data[name_place_holder]" value="<?php echo esc_attr( $form_data['name_place_holder'] ); ?>"
																		<?php
																		if ( 'yes' === $form_data['name_required'] ) {
																			echo 'disabled=disabled';
																		}
																		?>
																		></td>
																	</tr>
																	<?php do_action('ig_es_additional_form_fields', $form_data, $data ); ?>
																	<tr class="form-field">
																		<td class="pr-6 pb-6"><b class="text-gray-500 text-sm font-normal pb-2"><?php esc_html_e( 'Button', 'email-subscribers' ); ?></b></td>
																		<td class="pr-6 pb-6"><input type="checkbox" class="form-checkbox" name="form_data[button_visible]" value="yes" disabled="disabled" checked="checked"></td>
																		<td class="pr-6 pb-6"><input type="checkbox" class="form-checkbox" name="form_data[button_required]" value="yes" disabled="disabled" checked="checked"></td>
																		<td class="pr-6 pb-6"><input class="form-input block border-gray-400 w-5/6 pr-12 h-8 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" name="form_data[button_label]" value="<?php echo esc_attr( $form_data['button_label'] ); ?>"></td>
																	</tr>

																</table>
															</div>
														</div>
													</div>
													<div class="flex flex-row border-b border-gray-100">
														<div class="flex w-1/5">
															<div class="ml-4 pt-4 mb-2">
																<label for="tag-link"><span class="block ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Lists', 'email-subscribers' ); ?></span></label>
																<p class="italic text-xs text-gray-400 mt-2 ml-4 leading-snug pb-8"><?php esc_html_e( 'Contacts will be added into selected list(s)', 'email-subscribers' ); ?></p>
															</div>
														</div>
														<div class="flex">
															<div class="ml-16 mb-6 mr-4 mt-4">
																<?php
																$allowedtags = ig_es_allowed_html_tags_in_esc();
																if ( count( $lists ) > 0 ) {
																	$lists_checkboxes = ES_Shortcode::prepare_lists_checkboxes( $lists, array_keys( $lists ), 3, (array) $form_data['lists'] );
																	echo wp_kses( $lists_checkboxes, $allowedtags );

																} else {
																	$create_list_link = admin_url( 'admin.php?page=es_lists&action=new' );
																	?>
																	<span><b class="text-sm font-normal text-gray-600 pb-2">
																		<?php
																		/* translators: %s: Create list page url */
																		echo sprintf( esc_html__( 'List not found. Please %s', 'email-subscribers' ), '<a href="' . esc_url( $create_list_link ) . '"> ' . esc_html__( 'create your first list', 'email-subscribers' ) . '</a>' );
																		?>
																	</b></span>
																<?php } ?>
															</div>
														</div>
													</div>

													<div class="flex flex-row border-b border-gray-100">
														<div class="flex w-1/5">
															<div class="ml-4 pt-4 mb-2">
																<label for="tag-link"><span class="block ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Allow contact to choose list(s)', 'email-subscribers' ); ?></span></label>
																<p class="italic text-xs text-gray-400 mt-2 ml-4 leading-snug pb-4"><?php esc_html_e( 'Allow contacts to choose list(s) in which they want to subscribe.', 'email-subscribers' ); ?></p>
															</div>
														</div>
														<div class="flex ">
															<div class="ml-16 mb-4 mr-4 mt-12">
																<label for="es_allow_contact" class=" inline-flex items-center cursor-pointer">
																	<span class="relative">
																		<input id="es_allow_contact" type="checkbox" class=" absolute es-check-toggle opacity-0 w-0 h-0" name="form_data[list_visible]" value="yes"
																		<?php
																		if ( 'yes' === $form_data['list_visible'] ) {
																			echo 'checked="checked"';
																		}

																		?>
																		/>

																		<span class="es-mail-toggle-line"></span>
																		<span class="es-mail-toggle-dot"></span>
																	</span>

																</label>
															</div>
															<div class="ml-8 mb-4 mr-4 mt-10" id="es_list_label" style="display:none">
																<input id="list_label" class="form-input block border-gray-400 w-full pl-3 pr-12 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" placeholder="<?php echo esc_html__( 'Enter label', 'email-subscribers' ); ?>"  name="form_data[list_label]" value="<?php echo esc_html( stripslashes( $form_data['list_label'] ) ); ?>" size="30" maxlength="100"/>
															</div>
														</div>
													</div>


													<?php do_action( 'ig_es_additional_form_options', $form_data, $data ); ?>


													<div class="flex flex-row border-b border-gray-100">
														<div class="flex w-1/5">
															<div class="ml-4 pt-4 mb-2">
																<label for="tag-link"><span class="block ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Show GDPR consent checkbox', 'email-subscribers' ); ?></span></label>
																<p class="italic text-xs text-gray-400 mt-2 ml-4 leading-snug pb-8"><?php esc_html_e( 'Show consent checkbox to get the consent of a contact before adding them to list(s)', 'email-subscribers' ); ?></p>
															</div>
														</div>
														<div class="flex ">
															<div class="ml-16 mb-2 mr-4 mt-6">
																<table class="ig_es_form_table">
																	<tr>
																		<td>
																			<label for="gdpr_consent" class=" inline-flex items-center cursor-pointer">
																				<span class="relative">
																					<input id="gdpr_consent" type="checkbox" class="absolute es-check-toggle opacity-0 w-0 h-0" name="form_data[gdpr_consent]" value="yes"
																					<?php
																					if ( 'yes' === $form_data['gdpr_consent'] ) {
																						echo 'checked="checked"';
																					}
																					?>
																					/>

																					<span class="es-mail-toggle-line"></span>
																					<span class="es-mail-toggle-dot"></span>
																				</span>
																			</label>
																		</td>
																	</tr>
																	<tr>
																		<td>
																			<textarea class="form-textarea text-sm" rows="2" cols="50" name="form_data[gdpr_consent_text]"><?php echo wp_kses_post( $form_data['gdpr_consent_text'] ); ?></textarea>
																			<p class="italic text-xs text-gray-400 mt-2 leading-snug pb-4"><?php esc_html_e( 'Consent text will show up at subscription form next to consent checkbox.', 'email-subscribers' ); ?></p>
																		</td>
																	</tr>
																</table>
															</div>
														</div>
													</div>

													<input type="hidden" name="form_data[af_id]" value="<?php echo esc_attr( $form_data['af_id'] ); ?>"/>
													<input type="hidden" name="submitted" value="submitted"/>
													<?php
													$submit_button_text = $is_new ? __( 'Save Form', 'email-subscribers' ) : __( 'Save Changes', 'email-subscribers' );
													if ( count( $lists ) > 0 ) {
														?>
														<p class="submit"><input type="submit" name="submit" id="ig_es_dnd_form_submit_button" class="cursor-pointer align-middle ig-es-primary-button px-4 py-2 ml-6 mr-2" value="<?php echo esc_attr( $submit_button_text ); ?>"/>
															<a href="admin.php?page=es_forms" class="cursor-pointer align-middle rounded-md border border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo text-sm leading-5 font-medium transition ease-in-out duration-150 px-4 my-2 py-2 mx-2 "><?php esc_html_e( 'Cancel', 'email-subscribers' ); ?></a></p>
															<?php
													} else {
														$lists_page_url = admin_url( 'admin.php?page=es_lists' );
														/* translators: %s: List Page url */
														$message = __( sprintf( 'List(s) not found. Please create a first list from <a href="%s">here</a>', $lists_page_url ), 'email-subscribers' );
														$status  = 'error';
														ES_Common::show_message( $message, $status );
													}
													?>
													</form>
												</div>
											</div>
										</div>
									</div>
			</div>
			<?php

		}

		public function render_dnd_form( $id, $data ) {

			$form_data = $data;

			$form_id     = ! empty( $form_data['form_id'] ) ? $form_data['form_id'] : 0;
			$form_name   = ! empty( $form_data['name'] ) ? $form_data['name'] : __( 'Untitled Form', 'email-subscribers' );
			$editor_type = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';
			$form_style  = ! empty( $form_data['settings']['form_style'] ) ? $form_data['settings']['form_style'] : '';

			$action = 'new';
			if ( $form_id ) {
				$action = 'edit';
			}

			$nonce = wp_create_nonce( 'es_form' );
			?>

			<div id="es-edit-form-container" data-editor-type="<?php echo esc_attr( $editor_type ); ?>" class="<?php echo esc_attr( $editor_type ); ?> font-sans pt-1.5 wrap">
				<?php
				if ( ! empty( $message_data ) ) {
					$message = $message_data['message'];
					$type    = $message_data['type'];
					ES_Common::show_message( $message, $type );
				}
				?>
				<form  id="es-edit-form" method="POST" action="admin.php?page=es_forms&action=<?php echo esc_attr( $action ); ?>&form=<?php echo esc_attr( $form_id ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>">
					<input type="hidden" id="form_id" name="form_data[id]" value="<?php echo esc_attr( $form_id ); ?>"/>
					<input type="hidden" id="editor_type" name="form_data[settings][editor_type]" value="<?php echo esc_attr( $editor_type ); ?>"/>
					<input type="hidden" id="form_style" name="form_data[settings][form_style]" value="<?php echo esc_attr( $form_style ); ?>"/>
					<?php wp_nonce_field( 'ig-es-form-nonce', 'ig_es_form_nonce' ); ?>
					<fieldset class="block es_fieldset">
						<div class="mx-auto wp-heading-inline max-w-7xl">
							<header class="mx-auto max-w-7xl">
								<div class="md:flex md:items-center md:justify-between">
									<div class="flex md:3/5 lg:w-7/12 xl:w-3/5">
										<div class=" min-w-0 md:w-3/5 lg:w-1/2">
											<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
											<ol class="list-none p-0 inline-flex">
													<li class="flex items-center text-sm tracking-wide">
														<a class="hover:underline" href="admin.php?page=es_forms"><?php echo esc_html__( 'Forms', 'email-subscribers' ); ?>
														</a>
													</li>
											</ol>
											</nav>

											<input name="form_data[name]" value="<?php echo esc_html( $form_name ); ?>" id="es-form-name" class="form-heading-label bg-transparent outline-0 -mt-1 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate inline-block w-1/2" readonly="readonly">
											<span id="es-toggle-form-name-edit" class="dashicons dashicons-edit cursor-pointer"></span>
										</div>
										<div class="flex pt-4 md:-mr-8 lg:-mr-16 xl:mr-0 md:ml-8 lg:ml-16 xl:ml-20">
											<ul class="ig-es-tabs overflow-hidden">
												<li id="form_design_menu" class="es-first-step-tab relative float-left px-1 pb-2 text-center list-none cursor-pointer active ">
													<span class="mt-1 text-base font-medium tracking-wide text-gray-400 active"><?php echo esc_html__( 'Design', 'email-subscribers' ); ?></span>
												</li>
												<li id="form_settings_menu" class="es-second-step-tab relative float-left px-1 pb-2 ml-5 text-center list-none cursor-pointer hover:border-2 ">
													<span class="mt-1 text-base font-medium tracking-wide text-gray-400"><?php echo esc_html__( 'Settings', 'email-subscribers' ); ?></span>
												</li>
											</ul>
										</div>
									</div>
									<div class="flex md:mt-0 xl:ml-4">
										<div class="es-first-step-buttons-wrapper inline-block text-left md:mr-2 md:ml-2">
											<button id="view_form_summary_button" type="button"
													class="inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4">
													<?php
													echo esc_html__( 'Next', 'email-subscribers' );
													?>
												<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 20 20" class="w-3 h-3 my-1 ml-2 -mr-1 text-white hover:text-white">
													<path d="M9 5l7 7-7 7"></path>
												</svg>
											</button>
										</div>

										<div id="view_form_content_button" class="es-second-step-buttons-wrapper flex hidden mt-4 md:mt-0">
											<button type="button"
													class="inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-indigo-600 transition duration-150 ease-in-out border border-indigo-500 rounded-md cursor-pointer select-none pre_btn md:px-1 lg:px-3 xl:px-4 hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg ">
											<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" viewBox="0 0 20 20" class="w-3 h-3 my-1 mr-1"><path d="M15 19l-7-7 7-7"></path></svg><?php echo esc_html__( 'Previous', 'email-subscribers' ); ?>
											</button>
										</div>

										<span id="form_summary_actions_buttons_wrapper" class="es-second-step-buttons-wrapper hidden md:ml-2 xl:ml-2">
											<input type="hidden" name="submitted" value="submitted"/>
											<button type="submit" id="ig_es_save_form_btn" name="ig_es_form_action" class="inline-flex justify-center w-24 py-1.5 text-sm font-medium leading-5 text-indigo-600 transition duration-150 ease-in-out border border-indigo-500 rounded-md cursor-pointer select-none pre_btn md:px-1 lg:px-3 xl:px-4 hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg" value="save">
												<span class="ig_es_form_send_option_text">
													<?php echo esc_html__( 'Save', 'email-subscribers' ); ?>
												</span>
											</button>
										</span>
									</div>
								</div>
							</header>
						</div>
						<div class="mx-auto max-w-7xl">
							<hr class="wp-header-end">
						</div>
						<div class="mx-auto mt-6 es_form_first es-first-step max-w-7xl">
							<div>
								<textarea id="form-dnd-editor-data" name="form_data[settings][dnd_editor_data]" style="display:none;">
									<?php
									$dnd_editor_data = ! empty( $form_data['settings']['dnd_editor_data'] ) ? $form_data['settings']['dnd_editor_data'] : '';
									if ( ! empty( $dnd_editor_data ) ) {
										echo esc_html( $dnd_editor_data );
									}
									?>
								</textarea>
								<script>
									jQuery(document).ready(function($){
										if ( 'undefined' !== typeof wp && 'undefined' !== typeof wp.i18n ) {
											window.__ = wp.i18n.__;
										} else {
											// Create a dummy fallback function incase i18n library isn't available.
											window.__ = ( text, textDomain ) => {
												return text;
											}
										}

										let editorData = $('#form-dnd-editor-data').val().trim();
										$(document).on('es_drag_and_drop_editor_loaded',function (event) {
											let frontendCSS    = ig_es_js_data.frontend_css;
											let canvasHeadHTML = esVisualEditor.Canvas.getDocument().head.innerHTML;
											canvasHeadHTML     += frontendCSS; // Append links/styles tags in Canvas head section
											esVisualEditor.Canvas.getDocument().head.innerHTML = canvasHeadHTML;
											if ( '' !== editorData ) {
												let is_valid_json = ig_es_is_valid_json( editorData );
												if ( is_valid_json ) {
													editorData = JSON.parse( editorData );
													window.esVisualEditor.importMjml(editorData);
												}
											}

											let formStyles      = ig_es_js_data.form_styles;
											let commonCSS       = ig_es_js_data.common_css;
											let currentStyleId  = $('#form_style').val();
											currentStyleId      = currentStyleId ? currentStyleId : 'theme-styling'; // Set default styling to theme style.
											let currentStyle    = formStyles.find( style => currentStyleId === style.id );
											let currentStyleCSS = '';

											if ( currentStyle ) {
												currentStyleCSS = currentStyle ? currentStyle.css : '';
											} else {
												// Set default style to theme styling.
												let themeStyle  = formStyles.find( style => style.id === 'theme-styling' );
												currentStyleCSS = themeStyle.css;
											}
											
											esVisualEditor.setStyle( commonCSS + currentStyleCSS);

											let esPlan             = ig_es_js_data.es_plan;
											let isPremium          = ig_es_js_data.is_premium;
											let canUpsellFormStyle = ! isPremium;

											let formStylesHTML = `<div class="es-form-editor-options-sidebar">
												<div class="pt-2 pb-4 mx-4">
													<div class="flex w-full border-b border-gray-200 pb-2">
														<div class="w-1/3 text-sm font-normal text-gray-600 leading-9">${__( 'Form style', 'email-subscribers' )}</div>
														<div class="w-2/3 text-right">
															<span class="relative inline-block">
																<button id="form-style-button" type="button" class="py-1 px-2 ig-es-title-button">
																	<span>${currentStyle ? currentStyle.name : __( 'Theme style', 'email-subscribers' ) }</span>
																	<svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20">
																		<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
																	</svg>
																</button>
																${canUpsellFormStyle ? '<span class="premium-icon ml-1 align-text-bottom"></span>' : ''}
																<div x-show="open" id="form-styles-options" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
																		x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-50 right-0 hidden w-56 mt-2 origin-top-right rounded-md shadow-lg text-left">
																	<div class="bg-white rounded-md shadow-xs">
																		<div class="py-1">
																			${formStyles.map( style => `<span data-style-id="${style.id}" class="style-option block px-4 py-2 text-sm leading-5 text-gray-700 cursor-pointer hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900">${style.name}</span>`).join("")}
																		</div>
																	</div>
																</div>
															</span>
														</div>
													</div>
												</div>
											</div>`;
		
											$(formStylesHTML).insertBefore('.es-content');
											
											$('form#es-edit-form #form-style-button').on('click',()=>{
												if ( canUpsellFormStyle ) {
													window.open('https://www.icegram.com/express/pricing/?utm_source=in_app&utm_medium=form_styles&utm_campaign=es_upsell', '_blank');
												} else {
													$('form#es-edit-form #form-styles-options').toggle();
												}
											});

											$(document).on("click", function (event) {
												var $trigger = $("form#es-edit-form #form-style-button");
												if ($trigger !== event.target && !$trigger.has(event.target).length) {
													$("form#es-edit-form #form-styles-options").hide();
												}
											});

											$('form#es-edit-form .style-option').on('click', (e) => {
												e.preventDefault();
												let style_id   = $(e.target).data('style-id');
												let style_text = $(e.target).text();
												$('#form_style').val(style_id).trigger('change');
												$('#form-style-button span').text(style_text);
												$('#ig-es-styles-options').toggle();
											});
											
											$('form#es-edit-form #form_style').on('change',function(){
												let selected_style_id  = $(this).val();
												let selected_style     = formStyles.find(style => style.id === selected_style_id);
												let selected_style_css = selected_style.css ? selected_style.css : '';
												esVisualEditor.setStyle( commonCSS + selected_style_css );
											});
											var editor_type='DND_editor_form';
											ig_es_add_dnd_rte_tags( editor_type );

										});
									});
								</script>
								<div class="bg-white rounded-lg shadow-md">
									<div class="form-drag-and-drop-editor-container">
										<textarea id="ig-es-export-css-data-textarea" name="form_data[settings][dnd_editor_css]" style="display:none;"></textarea>
										<?php
										$form_editor_settings = array(
											'attributes' => array(
												'data-html-textarea-name' => 'form_data[body]',
												'data-page'               => 'form',
											),
										);
										( new ES_Drag_And_Drop_Editor() )->show_editor( $form_editor_settings );
										?>
									</div>
								</div>
							</div>
					</fieldset>

					<fieldset class="es_fieldset">

						<div class="mt-7 hidden mx-auto es_form_second max-w-7xl es-second-step">
							<div class="max-w-7xl">
								<div class="bg-white rounded-lg shadow md:flex">
									<div class="py-4 my-4 form_main_content pt-3 pl-2">
										<div class="block pb-2 mx-4">
											<span class="text-sm font-medium text-gray-500">
												<?php echo esc_html__( 'Form Preview', 'email-subscribers' ); ?>
											</span>
										</div>

										<div class="block pb-2 mx-4 mt-4 inline_form-popup-preview-container">
											<div class="block mt-3 form_preview_content"></div>
										</div>
									</div>
									<?php
										$allowedtags = ig_es_allowed_html_tags_in_esc();
										$lists       = ES()->lists_db->get_list_id_name_map();
									?>
									<div class="form_side_content bg-gray-100 rounded-r-lg">
										<div class="pt-4 pb-4 mx-4 border-b border-gray-200 es-form-lists ">
											<div class="flex w-full ">
												<div class="w-4/12">
														<label for="tag-link">
															<span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
																<?php esc_html_e( 'Lists', 'email-subscribers' ); ?>
															</span>
														</label>
												</div>
												<div class="w-8/12">
													<?php
													$allowedtags = ig_es_allowed_html_tags_in_esc();
													if ( count( $lists ) > 0 ) {
														$form_lists       = ! empty( $form_data['settings']['lists'] ) ? $form_data['settings']['lists'] : array();
														$lists_checkboxes = ES_Shortcode::prepare_lists_checkboxes( $lists, array_keys( $lists ), 3, (array) $form_lists, '', '', 'form_data[settings][lists][]' );
														echo wp_kses( $lists_checkboxes, $allowedtags );
													} else {
														$create_list_link = admin_url( 'admin.php?page=es_lists&action=new' );
														?>
														<span><b class="text-sm font-normal text-gray-600 pb-2">
															<?php
															/* translators: %s: Create list page url */
															echo sprintf( esc_html__( 'List not found. Please %s', 'email-subscribers' ), '<a href="' . esc_url( $create_list_link ) . '"> ' . esc_html__( 'create your first list', 'email-subscribers' ) . '</a>' );
															?>
														</b></span>
													<?php } ?>
												</div>
											</div>
										</div>
										<?php
										 do_action( 'ig_es_additional_form_options', $form_data, $data );
										?>

									</div>

								</div>
							</div>
						</div>

					</fieldset>
				</form>
			</div>
			<?php
		}

		/**
		 * Method to get preview HTML for form
		 *
		 * @return $response
		 *
		 * @since 4.4.7
		 */
		public function get_form_preview() {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

			$response = array();

			$form_data = ig_es_get_request_data( 'form_data', array(), false );
			$template_data            = array();
			$template_data['content'] = ! empty( $form_data['body'] ) ? $form_data['body'] : '';
			$template_data['form_id'] = ! empty( $form_data['id'] ) ? $form_data['id'] : 0;
			$editor_css 	          = ! empty( $form_data['settings']['dnd_editor_css'] ) ? $form_data['settings']['dnd_editor_css'] : '';
			$form_body                = ! empty( $form_data['body'] ) ? do_shortcode( $form_data['body'] ) : '';

			$preview_html             = '<style>' . $editor_css . '</style>' . $form_body;
			$response['preview_html'] = $preview_html;
			$response = self::process_form_body( $response);
			if ( ! empty( $response ) ) {
				wp_send_json_success( $response );
			} else {
				wp_send_json_error();
			}
		}

		//The code to replace the keywords in DND editor
		public static function process_form_body( $content) {
			if (!empty($content)) {
				// Define the replacements as an associative array
				$replacements = array(
					'{{TOTAL-CONTACTS}}' => ES()->contacts_db->count_active_contacts_by_list_id(),
					'{{site.total_contacts}}' => ES()->contacts_db->count_active_contacts_by_list_id(),
					'{{SITENAME}}' => get_option('blogname'),
					'{{site.name}}' => get_option('blogname'),
					'{{SITEURL}}' => home_url('/'),
					'{{site.url}}' => home_url('/'),
				);
		
				// Perform the replacements
				$content = str_replace(array_keys($replacements), array_values($replacements), $content);
			}
		
			return $content;
		}
		

		public static function get_styles_path() {
			$form_styles_path = ES_PLUGIN_DIR . 'lite/admin/css/form-styles/';
			return $form_styles_path;
		}

		public static function get_form_styles() {
			$form_styles_path = self::get_styles_path();

			$form_styles = array(
				array(
					'id'   => 'theme-styling',
					'name' => __( 'Theme styling', 'email-subscribers' ),
					'css'  => file_get_contents( $form_styles_path . 'theme-styling.css' ),
				),
			);
			$form_styles = apply_filters( 'ig_es_form_styles', $form_styles );
			return $form_styles;
		}

		public static function get_frontend_css() {
			$css_html = '';
			$response = wp_remote_get(get_home_url());
			if ( is_wp_error( $response )) {
				return $css_html;
			}
			$content = $response['body'];
			preg_match_all( '/<link\s+rel=[\'"]stylesheet[\'"]\s+.*?>/', $content, $matches );
			$mateched_link_tags = $matches[0];
			if ( ! empty( $mateched_link_tags ) ) {
				$css_html .= implode( '', $mateched_link_tags );
			}
	
			preg_match_all('/<style[^>]*>[\s\S]*?<\/style>/', $content, $matches );
			
			$matched_style_tags = $matches[0];
			if ( ! empty( $matched_style_tags ) ) {
				$css_html .= implode( '', $matched_style_tags );
			}
	
			return $css_html;
		}

		public static function get_common_css() {
			$form_styles_path = self::get_styles_path();
			$common_css       = file_get_contents( $form_styles_path . 'common.css' );
			return $common_css;
		}
	}

}

ES_Form_Admin::get_instance();
