<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ES_Forms_Table extends ES_List_Table {

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

		$this->init();
	}

	public function init() {
		add_action( 'ig_es_additional_form_options', array( $this, 'show_additional_form_setting' ) );
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
							<?php esc_html_e( 'Add New', 'email-subscribers' ); ?>
						</a>
						<?php 
							do_action( 'ig_es_after_form_buttons' );
						?>
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
							<?php
								do_action('ig_es_render_after_form_table');
							?>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
				<?php
			}
	}

	public function validate_data( $data ) {

		$editor_type = ! empty( $data['editor_type'] ) ? $data['editor_type'] : '';
		
		$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

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

		if ( ! $is_dnd_editor ) {
			if ( empty( $lists ) ) {
				$message = __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' );
				$error   = true;
			}
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

			$form_data['lists'] = $lists;

			$editor_type = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';

			$validate_data = array(
				'nonce' => $nonce,
				'name'  => ! empty( $form_data['name'] ) ? sanitize_text_field( $form_data['name'] ) : '',
				'lists' => ! empty( $form_data['lists'] ) ? $form_data['lists'] : array(),
				'editor_type' => $editor_type,
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
					$editor_type = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';

					$validate_data = array(
						'nonce' => $nonce,
						'name'  => $form_data['name'],
						'lists' => $form_data['lists'],
						'editor_type' => $editor_type,
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

		$editor_type = '';
		if ( $is_new ) {
			$editor_type = IG_ES_DRAG_AND_DROP_EDITOR;
			$data['settings']['editor_type'] = $editor_type;
		} else {
			$editor_type = ! empty( $data['settings']['editor_type'] ) ? $data['settings']['editor_type'] : '';
		}

		$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
		if ( $is_dnd_editor ) {
			do_action( 'ig_es_render_dnd_form', $id, $data );
		} else {
			do_action( 'ig_es_render_classic_form', $id, $data );
		}
	}

	/**
	 * Show additional form setting for es form
	 *
	 * @param $form_data
	 *
	 * @since 5.6.1
	 */
	public function show_additional_form_setting( $form_data ) {

		$editor_type   = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';
		$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
		if ( $is_dnd_editor ) {
			$this->show_dnd_show_in_popup_settings( $form_data );
			$this->show_success_message( $form_data );
		} else {
			$this->show_classic_show_in_popup_settings( $form_data );
		}
		
	}

	/**
	 * Show success message
	 *
	 * @param $form_data
	 *
	 * @since 5.6.1
	 * 
	 * @modify 5.6.3
	 */
	public function show_success_message( $form_data ) {
		$action_after_submit = ! empty( $form_data['settings']['action_after_submit'] ) ? $form_data['settings']['action_after_submit'] : 'show_success_message';
		$success_message     = ! empty( $form_data['settings']['success_message'] ) ? $form_data['settings']['success_message'] : '';
		?>
		<div class="pt-2 mx-4 border-t border-gray-200">
			<div class="block w-full">
			<span class="block pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'after submit...', 'email-subscribers' ); ?></span>
				<div class="py-2">
					<input id="success_message" type="radio" name="form_data[settings][action_after_submit]" class="form-radio ig_es_action_after_submit" value="show_success_message" <?php checked( $action_after_submit, 'show_success_message' ); ?>/>
					<label for="success_message"
						class="text-sm font-medium text-gray-500"><?php echo esc_html__( 'Show message', 'email-subscribers' ); ?>
					</label>
					<br>					
					<div id="show_message_block" class="pt-2 px-6">												
						<input class="form-input block border-gray-400 w-full pl-3 pr-3 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5" name="form_data[settings][success_message]" value="<?php echo esc_attr( stripslashes( $success_message ) ); ?>" />
					</div>
				</div>
			</div>
		</div>
		<?php
		do_action( 'ig_es_redirect_to_url' );
	}

	/**
	 * Show show in popup setting in classic form
	 *
	 * @param $form_data
	 *
	 * @since 5.6.1
	 */
	public function show_classic_show_in_popup_settings( $form_data ) {
		$popup_field_name  = 'form_data[show_in_popup]';
		$popup_field_value = $form_data['show_in_popup'];

		$headline_field_name  = 'form_data[popup_headline]';
		$headline_field_value = $form_data['popup_headline'];
		?>
		<div class="flex flex-row border-b border-gray-100">
			<div class="flex w-1/5">
				<div class="ml-4 pt-4 mb-2">
					<label for="tag-link"><span class="block ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php esc_html_e( 'Show in popup', 'email-subscribers' ); ?></span></label>
					<p class="italic text-xs text-gray-400 ml-4 leading-snug pt-2"><?php echo esc_html__( 'Show form in popup', 'email-subscribers' ); ?></p>
				</div>
			</div>
			<div>
				<div class="ml-16 mb-3 mr-4 mt-6">
					<label for="show_in_popup" class="inline-flex items-center cursor-pointer">
						<span class="relative">
							<input id="show_in_popup" type="checkbox" class=" absolute es-check-toggle opacity-0 w-0 h-0" name="<?php echo esc_attr( $popup_field_name ); ?>" value="yes"
							<?php
							if ( 'yes' === $popup_field_value ) {
								echo 'checked="checked"';
							}

							?>
							/>

							<span class="es-mail-toggle-line"></span>
							<span class="es-mail-toggle-dot"></span>
						</span>

					</label>
				</div>
				<div class="ml-16 mb-4 mr-4 mt-8" id="popup_input_block" style="display:none">
					<table class="ig-es-form-table ">
						<tr class="form-field">
							<td class="pr-12">
								<b class="text-gray-500 text-sm font-normal pb-2"><?php esc_html_e( 'Headline', 'email-subscribers' ); ?></b>
							</td>
							<td class="pr-12">
								<input id="popup_headline" class="form-input block border-gray-400 w-full pl-3 pr-12 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5"  name="<?php echo esc_attr( $headline_field_name ); ?>" value="<?php echo esc_html( stripslashes( $headline_field_value ) ); ?>" />
							</td>
						</tr>
					</table>
					<p class="italic text-xs text-gray-400 leading-snug pt-2">
						<?php
						/* translators: %s: Form attribute */
						echo sprintf( esc_html__( 'To disable it at a specific instance of a form add this attribute %s in the form\'s shortcode', 'email-subscribers'), '<code class="text-gray-500"><em><strong>show-in-popup="no"</em></strong></code>' );
						?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Show show in popup setting in DND form
	 *
	 * @param $form_data
	 *
	 * @since 5.6.1
	 */
	public function show_dnd_show_in_popup_settings( $form_data ) {
		$popup_field_name  = 'form_data[settings][show_in_popup]';
		$popup_field_value = isset( $form_data['settings']['show_in_popup'] ) ? $form_data['settings']['show_in_popup'] : 'no';

		$headline_field_name  = 'form_data[settings][popup_headline]';
		$headline_field_value = ! empty( $form_data['settings']['popup_headline'] ) ? $form_data['settings']['popup_headline'] : '';
		?>
		<div class="pt-2 pb-4 mx-4">
			<div class="flex w-full">
				<div class="w-11/12 text-sm font-normal text-gray-600">
					<?php echo esc_html__( 'Show in popup', 'email-subscribers' ); ?>
				</div>
				<div>
					<label for="show_in_popup" class="inline-flex items-center cursor-pointer">
						<span class="relative">
							<input id="show_in_popup" type="checkbox"
								class=" absolute es-check-toggle opacity-0 w-0 h-0"
								name="form_data[settings][show_in_popup]"
								value="yes"
							<?php
							if ( 'yes' === $popup_field_value ) {
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
		<div class="pt-2 pb-4 mx-4"  id="popup_input_block" style="display:none">
			<div class="flex w-full">
				<div class="w-4/12 text-sm font-normal text-gray-600">
						<?php echo esc_html__( 'Headline', 'email-subscribers' ); ?>
				</div>
				<div class="w-8/12">
					<input id="popup_headline" class="form-input block border-gray-400 w-full pl-3 pr-3 shadow-sm  focus:bg-gray-100 sm:text-sm sm:leading-5"  name="<?php echo esc_attr( $headline_field_name ); ?>" value="<?php echo esc_html( stripslashes( $headline_field_value ) ); ?>" />
				</div>
			</div>
			<p class="italic text-xs text-gray-400 leading-snug pt-2">
				<?php
				/* translators: %s: Form attribute */
				echo sprintf( esc_html__( 'To disable it at a specific instance of a form add this attribute %s in the form\'s shortcode', 'email-subscribers'), '<code class="text-gray-500"><em><strong>show-in-popup="no"</em></strong></code>' );
				?>
			</p>
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

		$form_data     = array();
		$name          = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$editor_type   = ! empty( $data['settings']['editor_type'] ) ? sanitize_text_field( $data['settings']['editor_type'] ) : '';
		$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

		$es_form_popup         = ! empty( $data['show_in_popup'] ) ? 'yes' : 'no';
		$es_popup_headline     = ! empty( $data['popup_headline'] ) ? sanitize_text_field( $data['popup_headline'] ) : '';
		
		if ( ! $is_dnd_editor ) {
			$desc               = ! empty( $data['desc'] ) ? wp_kses_post( trim( wp_unslash( $data['desc'] ) ) ) : '';
			$email_label        = ! empty( $data['email_label'] ) ? sanitize_text_field( $data['email_label'] ) : '';
			$email_place_holder = ! empty( $data['email_place_holder'] ) ? sanitize_text_field( $data['email_place_holder'] ) : '';
			$name_label         = ! empty( $data['name_label'] ) ? sanitize_text_field( $data['name_label'] ) : '';
			$name_place_holder  = ! empty( $data['name_place_holder'] ) ? sanitize_text_field( $data['name_place_holder'] ) : '';
			$button_label       = ! empty( $data['button_label'] ) ? sanitize_text_field( $data['button_label'] ) : '';
			$name_visible       = ( ! empty( $data['name_visible'] ) && 'yes' === $data['name_visible'] ) ? true : false;
			$name_required      = ( ! empty( $data['name_required'] ) && 'yes' === $data['name_required'] ) ? true : false;
			$list_label         = ! empty( $data['list_label'] ) ? sanitize_text_field( $data['list_label'] ) : '';
			$list_visible       = ( ! empty( $data['list_visible'] ) && 'yes' === $data['list_visible'] ) ? true : false;
			$list_required      = true;
			$list_ids           = ! empty( $data['lists'] ) ? $data['lists'] : array();
			
			$gdpr_consent       = ! empty( $data['gdpr_consent'] ) ? sanitize_text_field( $data['gdpr_consent'] ) : 'no';
			$gdpr_consent_text  = ! empty( $data['gdpr_consent_text'] ) ? wp_kses_post( $data['gdpr_consent_text'] ) : '';
			$captcha            = ! empty( $data['captcha'] ) ? ES_Common::get_captcha_setting( null, $data ) : 'no';

			$body = array(
				array(
					'type'     => 'text',
					'name'     => 'Name',
					'id'       => 'name',
					'params'   => array(
						'label'        => $name_label,
						'place_holder' => $name_place_holder,
						'show'         => $name_visible,
						'required'     => $name_required,
					),
	
					'position' => 1,
				),
	
				array(
					'type'     => 'text',
					'name'     => 'Email',
					'id'       => 'email',
					'params'   => array(
						'label'        => $email_label,
						'place_holder' => $email_place_holder,
						'show'         => true,
						'required'     => true,
					),
	
					'position' => 2,
				),
	
				array(
					'type'     => 'checkbox',
					'name'     => 'Lists',
					'id'       => 'lists',
					'params'   => array(
						'label'    => $list_label,
						'show'     => $list_visible,
						'required' => $list_required,
						'values'   => $list_ids,
					),
	
					'position' => 3,
				),
			);
	
			$form_body = apply_filters( 'es_add_custom_fields_data_in_form_body', $body, $data );
	
			$submit_button_position = count( $form_body ) + 1;
			$submit_data            = array(
				array(
					'type'     => 'submit',
					'name'     => 'submit',
					'id'       => 'submit',
					'params'   => array(
						'label'    => $button_label,
						'show'     => true,
						'required' => true,
					),
	
					'position' => $submit_button_position,
				),
			);
	
			$body = array_merge( $form_body, $submit_data );

			$settings = array(
				'lists'        => $list_ids,
				'desc'         => $desc,
				'form_version' => ES()->forms_db->version,
				'captcha'      => $captcha,
				'gdpr'         => array(
					'consent'      => $gdpr_consent,
					'consent_text' => $gdpr_consent_text,
				),
				'es_form_popup'  => array(
					'show_in_popup'  => $es_form_popup,
					'popup_headline' => $es_popup_headline,
				),						
			);
	
			$settings = apply_filters( 'ig_es_form_settings', $settings, $data );

			$form_data['body'] = maybe_serialize( $body );
		} else {
			$form_data['body'] = $data['body'];
			$settings          = $data['settings'];
		}

		$af_id = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;		

		$form_data['name']       = $name;
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

		$desc          = ! empty( $settings_data['desc'] ) ? $settings_data['desc'] : '';
		$form_version  = ! empty( $settings_data['form_version'] ) ? $settings_data['form_version'] : '0.1';
		$editor_type   = ! empty( $settings_data['editor_type'] ) ? $settings_data['editor_type'] : '';
		$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

		if ( ! $is_dnd_editor ) {
			$gdpr_consent      	  = 'no';
			$gdpr_consent_text 	  = '';
			$es_form_popup     	  = ! empty( $settings_data['es_form_popup']['show_in_popup'] ) ? $settings_data['es_form_popup']['show_in_popup'] : 'no';
			$es_popup_headline 	  = ! empty( $settings_data['es_form_popup']['popup_headline'] ) ? $settings_data['es_form_popup']['popup_headline'] : '';
	
			$captcha = ES_Common::get_captcha_setting( $id, $settings_data );
	
			if ( ! empty( $settings_data['gdpr'] ) ) {
				$gdpr_consent      = ! empty( $settings_data['gdpr']['consent'] ) ? $settings_data['gdpr']['consent'] : 'no';
				$gdpr_consent_text = ! empty( $settings_data['gdpr']['consent_text'] ) ? $settings_data['gdpr']['consent_text'] : '';
			}
	
			$form_data = array(
				'form_id'              => $id,
				'name'                 => $name,
				'af_id'                => $af_id,
				'desc'                 => $desc,
				'form_version'         => $form_version,
				'gdpr_consent'         => $gdpr_consent,
				'gdpr_consent_text'    => $gdpr_consent_text,
				'captcha'              => $captcha,
				'show_in_popup'        => $es_form_popup,
				'popup_headline'       => $es_popup_headline,
				'editor_type'          => $editor_type,
			);
	
			foreach ( $body_data as $d ) {
				if ( 'name' === $d['id'] ) {
					$form_data['name_visible']      = ( true === $d['params']['show'] ) ? 'yes' : '';
					$form_data['name_required']     = ( true === $d['params']['required'] ) ? 'yes' : '';
					$form_data['name_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
					$form_data['name_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
				} elseif ( 'lists' === $d['id'] ) {
					$form_data['list_label']  	= ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
					$form_data['list_visible']  = ( true === $d['params']['show'] ) ? 'yes' : '';
					$form_data['list_required'] = ( true === $d['params']['required'] ) ? 'yes' : '';
					$form_data['lists']         = ! empty( $d['params']['values'] ) ? $d['params']['values'] : array();
				} elseif ( 'email' === $d['id'] ) {
					$form_data['email_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
					$form_data['email_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
				} elseif ( 'submit' === $d['id'] ) {
					$form_data['button_label'] = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
				} elseif ( $d['is_custom_field'] ) {
					$form_data['custom_fields'][] = $d;
				}
			}
			$form_data = apply_filters('ig_es_form_fields_data', $form_data, $settings_data, $body_data);
		} else {
			$form_data = array(
				'form_id'           => $id,
				'body'				=> $body_data,
				'name'              => $name,
				'af_id'             => $af_id,
				'form_version'      => $form_version,
				'settings'			=> $settings_data,
			);
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

				return '<code class="es-code">' . $shortcode . '</code>';
			break;
			case 'total_active_subscribers':
				$total_active_subscribers = ES()->contacts_db->get_total_contacts_by_form_id( $item['id'] );
				return number_format( $total_active_subscribers );
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
			'edit'   => '<a href="?page=' . esc_attr( $page ) . '&action=edit&form=' . absint( $item['id'] ) . '&_wpnonce=' . $list_nonce . '" class="text-indigo-600">' . esc_html__( 'Edit', 'email-subscribers' ) . '</a>',

			'delete' => '<a href="?page=' . esc_attr( $page ) . '&action=delete&form=' . absint( $item['id'] ) . '&_wpnonce=' . $list_nonce . '" onclick="return checkDelete()">' . esc_html__( 'Delete', 'email-subscribers' ) . '</a>',
		);
		$actions = apply_filters('ig_es_form_table_row_actions', $actions, $item);

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'                       => '<input type="checkbox" />',
			'name'                     => __( 'Name', 'email-subscribers' ),
			'shortcode'                => __( 'Shortcode', 'email-subscribers' ),
			'total_active_subscribers' => __( 'Subscribers', 'email-subscribers' ),
			'created_at'               => __( 'Created', 'email-subscribers' ),
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

	public function search_box( $text, $input_id ) {
		?>

		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Forms', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

	/** Text displayed when no list data is available */
	public function no_items() {
		esc_html_e( 'No Forms avaliable.', 'email-subscribers' );
	}
}
