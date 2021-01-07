<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ES_Forms_Table extends WP_List_Table {

	/**
	 * Number of form options per page
	 *
	 * @since 4.2.1
	 * @var string
	 */
	public static $option_per_page = 'es_forms_per_page';

	/**
	 * ES_DB_Forms object
	 *
	 * @since 4.3.1
	 * @var $db
	 */
	protected $db;

	/**
	 * ES_Forms_Table constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Forms', 'email-subscribers' ), // singular name of the listed records
				'plural'   => __( 'Forms', 'email-subscribers' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?,
				'screen'   => 'es_forms',
			)
		);

		$this->db = new ES_DB_Forms();
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		$action = ig_es_get_request_data( 'action' );

		if ( empty( $action ) ) {

			$option = 'per_page';
			$args   = array(
				'label'   => __( 'Number of forms per page', 'email-subscribers' ),
				'default' => 20,
				'option'  => self::$option_per_page,
			);

			add_screen_option( $option, $args );
		}
	}


	/**
	 * Render Forms list view
	 *
	 * @since 4.0
	 */
	public function render() {

		$action = ig_es_get_request_data( 'action' );
		?>
		<div class="wrap pt-4 font-sans">
			<?php 
			if ( 'new' === $action ) {
				$this->es_new_form_callback();
			} elseif ( 'edit' === $action ) {
				$form = ig_es_get_request_data( 'form' );
				echo wp_kses_post( $this->edit_form( absint( $form ) ) );
			} else { 
				?>
		<div class="flex">
			<div>
				<h2 class="wp-heading-inline text-3xl font-bold text-gray-700 sm:leading-9 sm:truncate pr-4">
						<?php esc_html_e( 'Forms', 'email-subscribers' ); ?>
				</h2>
			</div>
			<div class="mt-1">
				<a href="admin.php?page=es_forms&action=new" class="ig-es-title-button ml-2 leading-5 align-middle">
					<?php esc_html_e('Add New', 'email-subscribers'); ?>
				</a>
			</div>
		</div>
		<div><hr class="wp-header-end"></div>
				<?php 
				if ( 'form_created' === $action ) {
					$message = __( 'Form added successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				} elseif ( 'form_updated' === $action ) {
					$message = __( 'Form updated successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}
				?>
				<div id="poststuff" class="es-items-lists">
					<div id="post-body" class="metabox-holder column-1">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="get">
									<input type="hidden" name="page" value="es_forms" />
									<?php
									// Display search field and other available filter fields.
									$this->prepare_items();
									?>
								</form>
								<form method="post">
									<?php
									// Display bulk action fields, pagination and list items.
									$this->display();
									?>
								</form>
							</div>

						</div>
					</div>
					<br class="clear">
				</div>
			</div>
				<?php 
			}
	}

	public function validate_data( $data ) {

		$nonce     = $data['nonce'];
		$form_name = $data['name'];
		$lists     = $data['lists'];

		$status  = 'error';
		$error   = false;
		$message = '';
		if ( ! wp_verify_nonce( $nonce, 'es_form' ) ) {
			$message = __( 'You do not have permission to edit this form.', 'email-subscribers' );
			$error   = true;
		} elseif ( empty( $form_name ) ) {
			$message = __( 'Please add form name.', 'email-subscribers' );
			$error   = true;
		}

		if ( empty( $lists ) ) {
			$message = __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' );
			$error   = true;
		}

		if ( ! $error ) {
			$status = 'success';
		}

		$response = array(
			'status'  => $status,
			'message' => $message,
		);

		return $response;

	}

	public function es_new_form_callback() {

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = ig_es_get_request_data( '_wpnonce' );
			$form_data = ig_es_get_request_data( 'form_data', array(), false );
			$lists     = ig_es_get_request_data( 'lists' );

			$form_data['captcha'] = ! empty( $form_data['captcha'] ) ? $form_data['captcha'] : 'no';

			$form_data['lists'] = $lists;

			$validate_data = array(
				'nonce' => $nonce,
				'name'  => ! empty( $form_data['name'] ) ? sanitize_text_field( $form_data['name'] ) : '',
				'lists' => ! empty( $form_data['lists'] ) ? $form_data['lists'] : array(),
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				ES_Common::show_message( $message, 'error' );
				$this->prepare_list_form( null, $form_data );

				return;
			}

			$this->save_form( null, $form_data );
			$form_url = admin_url( 'admin.php?page=es_forms&action=form_created' );
			wp_safe_redirect( $form_url );
			exit();
		}

		$this->prepare_list_form();
	}


	public function edit_form( $id ) {
		global $wpdb;

		if ( $id ) {

			$form_data = array();

			$data = ES()->forms_db->get_by_conditions( $wpdb->prepare( ' id = %d', $id ) );

			if ( count( $data ) > 0 ) {

				$submitted = ig_es_get_request_data( 'submitted' );

				if ( 'submitted' === $submitted ) {

					$nonce     = ig_es_get_request_data( '_wpnonce' );
					$form_data = ig_es_get_request_data( 'form_data', array(), false );
					$lists     = ig_es_get_request_data( 'lists' );

					$form_data['captcha'] = ! empty( $form_data['captcha'] ) ? $form_data['captcha'] : 'no';

					$form_data['lists'] = $lists;

					$validate_data = array(
						'nonce' => $nonce,
						'name'  => $form_data['name'],
						'lists' => $form_data['lists'],
					);

					$response = $this->validate_data( $validate_data );

					if ( 'error' === $response['status'] ) {
						$message = $response['message'];
						ES_Common::show_message( $message, 'error' );
						$this->prepare_list_form( $id, $form_data );

						return;
					}

					$this->save_form( $id, $form_data );
					$form_url = admin_url( 'admin.php?page=es_forms&action=form_updated' );
					wp_safe_redirect( $form_url );
					exit();

				} else {

					$data      = $data[0];
					$id        = $data['id'];
					$form_data = self::get_form_data_from_body( $data );
				}
			} else {
				$message = __( 'Sorry, form not found', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}

			$this->prepare_list_form( $id, $form_data );
		}
	}

	public function prepare_list_form( $id = 0, $data = array() ) {

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
		$form_data['gdpr_consent_text']  = ! empty( $data['gdpr_consent_text'] ) ? $data['gdpr_consent_text'] : __( 'Please accept terms & condition', 'email-subscribers' );
		$form_data['lists']              = ! empty( $data['lists'] ) ? $data['lists'] : array();
		$form_data['af_id']              = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;
		$form_data['desc']               = ! empty( $data['desc'] ) ? sanitize_text_field( $data['desc'] ) : '';
		$form_data['captcha']            = ES_Common::get_captcha_setting( 0, $data );

		$lists = ES()->lists_db->get_list_id_name_map();
		$nonce = wp_create_nonce( 'es_form' );

		?>

		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="md:flex md:items-center md:justify-between justify-center">
					<div class="flex-1 min-w-0">
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
						<ol class="list-none p-0 inline-flex">
							<li class="flex items-center text-sm tracking-wide">
							<a class="hover:underline" href="admin.php?page=es_forms"><?php esc_html_e('Forms ', 'email-subscribers'); ?></a>
							<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
							</li>
						</ol>
					</nav>
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
					</div>
				</header>
				<div class=""><hr class="wp-header-end"></div>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder column-1">
						<div id="post-body-content" class="pt-0.5">
							<div class="bg-white shadow-md rounded-lg mt-5 pt-1">
								<form class="pt-8 ml-5 mr-4 text-left flex-row mt-2 item-center " method="post" action="admin.php?page=es_forms&action=<?php echo esc_attr( $action ); ?>&form=<?php echo esc_attr( $id ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>">


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
														echo wp_kses( $lists_checkboxes , $allowedtags );

													} else {
														$create_list_link = admin_url( 'admin.php?page=es_lists&action=new' );
														?>
														<span><b class="text-sm font-normal text-gray-600 pb-2">
															<?php 
															/* translators: %s: Create list page url */
															echo sprintf( esc_html__('List not found. Please %s', 'email-subscribers' ), '<a href="' . esc_url( $create_list_link ) . '"> ' . esc_html__( 'create your first list', 'email-subscribers') . '</a>' );
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
													<label for="allow_contact" class=" inline-flex items-center cursor-pointer">
														<span class="relative">
															<input id="allow_contact" type="checkbox" class=" absolute es-check-toggle opacity-0 w-0 h-0" name="form_data[list_visible]" value="yes" 
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
											</div>
										</div>

										
										<?php do_action('ig_es_add_additional_options', $form_data); ?>


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
											<p class="submit"><input type="submit" name="submit" id="ig_es_campaign_post_notification_submit_button" class="cursor-pointer align-middle ig-es-primary-button px-4 py-2 ml-6 mr-2" value="<?php echo esc_attr( $submit_button_text ); ?>"/>
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


					<?php

	}

	public function save_form( $id, $data ) {

		global $wpdb;

		$form_data = self::prepare_form_data( $data );

		if ( ! empty( $id ) ) {
			$form_data['updated_at'] = ig_get_current_date_time();

			// We don't want to change the created_at date for update
			unset( $form_data['created_at'] );
			$return = $wpdb->update( IG_FORMS_TABLE, $form_data, array( 'id' => $id ) );
		} else {
			$return = $wpdb->insert( IG_FORMS_TABLE, $form_data );
		}

		return $return;
	}

	public static function prepare_form_data( $data ) {

		$form_data          = array();
		$name               = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$desc               = ! empty( $data['desc'] ) ? sanitize_text_field( $data['desc'] ) : '';
		$email_label        = ! empty( $data['email_label'] ) ? sanitize_text_field( $data['email_label'] ) : '';
		$email_place_holder = ! empty( $data['email_place_holder'] ) ? sanitize_text_field( $data['email_place_holder'] ) : '';
		$name_label         = ! empty( $data['name_label'] ) ? sanitize_text_field( $data['name_label'] ) : '';
		$name_place_holder  = ! empty( $data['name_place_holder'] ) ? sanitize_text_field( $data['name_place_holder'] ) : '';
		$button_label       = ! empty( $data['button_label'] ) ? sanitize_text_field( $data['button_label'] ) : '';
		$name_visible       = ( ! empty( $data['name_visible'] ) && 'yes' === $data['name_visible'] ) ? true : false;
		$name_required      = ( ! empty( $data['name_required'] ) && 'yes' === $data['name_required'] ) ? true : false;
		$list_visible       = ( ! empty( $data['list_visible'] ) && 'yes' === $data['list_visible'] ) ? true : false;
		$list_required      = true;
		$list_ids           = ! empty( $data['lists'] ) ? $data['lists'] : array();
		$af_id              = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;
		$gdpr_consent       = ! empty( $data['gdpr_consent'] ) ? sanitize_text_field( $data['gdpr_consent'] ) : 'no';
		$gdpr_consent_text  = ! empty( $data['gdpr_consent_text'] ) ? wp_kses_post( $data['gdpr_consent_text'] ) : '';
		$captcha  			= ! empty( $data['captcha'] ) ? ES_Common::get_captcha_setting(null, $data) : 'no';

		$body = array(
			array(
				'type'   => 'text',
				'name'   => 'Name',
				'id'     => 'name',
				'params' => array(
					'label'        => $name_label,
					'place_holder' => $name_place_holder,
					'show'         => $name_visible,
					'required'     => $name_required
				),

				'position' => 1
			),

			array(
				'type'   => 'text',
				'name'   => 'Email',
				'id'     => 'email',
				'params' => array(
					'label'        => $email_label,
					'place_holder' => $email_place_holder,
					'show'         => true,
					'required'     => true
				),

				'position' => 2
			),

			array(
				'type'   => 'checkbox',
				'name'   => 'Lists',
				'id'     => 'lists',
				'params' => array(
					'label'    => 'Lists',
					'show'     => $list_visible,
					'required' => $list_required,
					'values'   => $list_ids
				),

				'position' => 3
			),

			array(
				'type'   => 'submit',
				'name'   => 'submit',
				'id'     => 'submit',
				'params' => array(
					'label'    => $button_label,
					'show'     => true,
					'required' => true
				),

				'position' => 4
			),

		);

		$settings = array(
			'lists'        => $list_ids,
			'desc'         => $desc,
			'form_version' => ES()->forms_db->version,
			'captcha' 	   => $captcha,
			'gdpr'         => array(
				'consent'      => $gdpr_consent,
				'consent_text' => $gdpr_consent_text
			)

		);

		$form_data['name']       = $name;
		$form_data['body']       = maybe_serialize( $body );
		$form_data['settings']   = maybe_serialize( $settings );
		$form_data['styles']     = null;
		$form_data['created_at'] = ig_get_current_date_time();
		$form_data['updated_at'] = null;
		$form_data['deleted_at'] = null;
		$form_data['af_id']      = $af_id;

		return $form_data;
	}

	public static function get_form_data_from_body( $data ) {

		$name          = ! empty( $data['name'] ) ? $data['name'] : '';
		$id            = ! empty( $data['id'] ) ? $data['id'] : '';
		$af_id         = ! empty( $data['af_id'] ) ? $data['af_id'] : '';
		$body_data     = maybe_unserialize( $data['body'] );
		$settings_data = maybe_unserialize( $data['settings'] );

		$desc         = ! empty( $settings_data['desc'] ) ? $settings_data['desc'] : '';
		$form_version = ! empty( $settings_data['form_version'] ) ? $settings_data['form_version'] : '0.1';

		$gdpr_consent      = 'no';
		$gdpr_consent_text = '';

		$captcha = ES_Common::get_captcha_setting( $id , $settings_data);

		if ( ! empty( $settings_data['gdpr'] ) ) {
			$gdpr_consent      = ! empty( $settings_data['gdpr']['consent'] ) ? $settings_data['gdpr']['consent'] : 'no';
			$gdpr_consent_text = ! empty( $settings_data['gdpr']['consent_text'] ) ? $settings_data['gdpr']['consent_text'] : '';
		}

		$form_data = array( 'form_id' => $id, 'name' => $name, 'af_id' => $af_id, 'desc' => $desc, 'form_version' => $form_version, 'gdpr_consent' => $gdpr_consent, 'gdpr_consent_text' => $gdpr_consent_text, 'captcha' => $captcha );

		foreach ( $body_data as $d ) {
			if ( 'name' === $d['id'] ) {
				$form_data['name_visible']      = ( true === $d['params']['show'] ) ? 'yes' : '';
				$form_data['name_required']     = ( true === $d['params']['required'] ) ? 'yes' : '';
				$form_data['name_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
				$form_data['name_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
			} elseif ( 'lists' === $d['id'] ) {
				$form_data['list_visible']  = ( true === $d['params']['show'] ) ? 'yes' : '';
				$form_data['list_required'] = ( true === $d['params']['required'] ) ? 'yes' : '';
				$form_data['lists']         = ! empty( $d['params']['values'] ) ? $d['params']['values'] : array();
			} elseif ( 'email' === $d['id'] ) {
				$form_data['email_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
				$form_data['email_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
			} elseif ( 'submit' === $d['id'] ) {
				$form_data['button_label'] = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
			}
		}
		return $form_data;
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		global $wpdb, $wpbd;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		$forms_table = IG_FORMS_TABLE;
		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$forms_table}";
		} else {
			$sql = "SELECT * FROM {$forms_table}";
		}

		$args  = array(); 
		$query = array();

		$add_where_clause = false;

		if ( ! empty( $search ) ) {
			$query[] = ' name LIKE %s ';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';

			$add_where_clause = true;
		}

		if ( $add_where_clause ) {
			$sql .= ' WHERE ';

			if ( count( $query ) > 0 ) {
				$sql .= implode( ' AND ', $query );
				if ( count( $args ) > 0 ) {
					$sql = $wpbd->prepare( $sql, $args );
				}
			}
		}

		if ( ! $do_count_only ) {

			$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'name', 'created_at' );

			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $order_by );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;
			$sql .= " LIMIT $per_page";
			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

			$result = $wpbd->get_results( $sql, 'ARRAY_A' );
		} else {
			$result = $wpbd->get_var( $sql );
		}

		return $result;
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			// case 'status':
			// return $this->status_label_map( $item[ $column_name ] );
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
			break;
			case 'shortcode':
				$shortcode = '[email-subscribers-form id="' . $item['id'] . '"]';

				return '<code id="es-shortcode">' . $shortcode . '</code>';
			break;
			case 'total_active_subscribers':
				return ES()->contacts_db->get_total_contacts_by_form_id( $item['id'] );
			default:
				return '';
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="forms[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_name( $item ) {

		$list_nonce = wp_create_nonce( 'es_form' );

		$title = '<strong>' . stripslashes( $item['name'] ) . '</strong>';

		$page    = ig_es_get_request_data( 'page' );
		$actions = array(
			/* translators: 1: Page  2: Edit Action  3: Form id  4. WP Nonce */
			'edit'   => sprintf( __( '<a href="?page=%1$s&action=%2$s&form=%3$s&_wpnonce=%4$s" class="text-indigo-600">Edit</a>', 'email-subscribers' ), esc_attr( $page ), 'edit', absint( $item['id'] ), $list_nonce ),
			/* translators: 1: Page  2: Delete Action  3: Form id  4. WP Nonce */
			'delete' => sprintf( __( '<a href="?page=%1$s&action=%2$s&form=%3$s&_wpnonce=%4$s" onclick="return checkDelete()">Delete</a>', 'email-subscribers' ), esc_attr( $page ), 'delete', absint( $item['id'] ), $list_nonce )
		);

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'         				=> '<input type="checkbox" />',
			'name'       				=> __( 'Name', 'email-subscribers' ),
			'shortcode'  				=> __( 'Shortcode', 'email-subscribers' ),
			'total_active_subscribers' 	=> __( 'Subscribers', 'email-subscribers' ),
			'created_at' 				=> __( 'Created', 'email-subscribers' ),
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name'       => array( 'name', true ),
			'created_at' => array( 'created_at', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'bulk_delete' => __( 'Delete', 'email-subscribers' ),
		);
	}

	/**
	 * Prepare search box
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @since 4.0.0
	 * @since 4.3.4 Added esc_attr()
	 */
	public function search_box( $text, $input_id ) { 
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search forms', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php 
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$search_str = ig_es_get_request_data( 's' );
		$this->search_box( $search_str, 'form-search-input' );

		$per_page     = $this->get_items_per_page( self::$option_per_page, 25 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items
				'per_page'    => $per_page, // WE have to determine how many items to show on a page
			)
		);

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_form' ) ) {
				$message = __( 'You do not have permission to delete this form.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {

				$form = ig_es_get_request_data( 'form' );

				$this->db->delete_forms( array( $form ) );
				$message = __( 'Form deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$forms = ig_es_get_request_data( 'forms' );

			if ( ! empty( $forms ) > 0 ) {
				$this->db->delete_forms( $forms );

				$message = __( 'Form(s) deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			} else {
				$message = __( 'Please select form(s) to delete.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );

				return;
			}
		}
	}

	public function status_label_map( $status ) {

		$statuses = array(
			'enable'  => __( 'Enable', 'email-subscribers' ),
			'disable' => __( 'Disable', 'email-subscribers' ),
		);

		if ( ! in_array( $status, array_keys( $statuses ) ) ) {
			return '';
		}

		return $statuses[ $status ];
	}

	/** Text displayed when no list data is available */
	public function no_items() {
		esc_html_e( 'No Forms avaliable.', 'email-subscribers' );
	}
}
