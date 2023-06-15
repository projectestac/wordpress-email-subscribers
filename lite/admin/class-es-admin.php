<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Admin' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Admin Settings
	 *
	 * @package    Email_Subscribers
	 * @subpackage Email_Subscribers/admin
	 */
	class ES_Admin {

		// class instance
		public static $instance;

		/**
		 * Campaign ID
		 */
		private $template_data = array();

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

			add_action( 'admin_init', array( $this, 'process_submission' ) );


			add_action( 'ig_es_before_template_settings', array( $this, 'show_template_setting_fields' ) );

			// preview popup
			//add_action( 'ig_es_template_preview_options_content', array( $this, 'show_campaign_preview_options_content' ) );

			add_action( 'wp_ajax_ig_es_draft_campaign', array( $this, 'draft_campaign' ) );
			add_action( 'wp_ajax_ig_es_get_template_preview', array( $this, 'get_template_preview' ) );
			add_action( 'wp_ajax_ig_es_delete_template', array( $this, 'delete_template' ) );

			//add_action( 'media_buttons', array( $this, 'add_tag_button' ) );
		}

		public function setup() {
			$template_id = $this->get_template_id_from_url();
			if ( ! empty( $template_id ) ) {
				$template = get_post( $template_id, ARRAY_A );
				if ( $template ) {
					$template_meta = get_post_custom( $template_id );
					if ( ! empty( $template_meta ) ) {
						foreach ( $template_meta as $meta_key => $meta_value ) {
							$template_meta[ $meta_key ] = $meta_value[0];
						}
					}
					if ( empty( $template_meta['es_editor_type'] ) ) {
						$template_meta['es_editor_type'] = IG_ES_CLASSIC_EDITOR;
					}
					$template['meta'] = $template_meta;
					$this->template_data = $template;
				}
			} else {
				$this->template_data['meta']['es_editor_type'] = $this->get_editor_type_from_url();
			}
		}

		public function get_template_id_from_url() {
			$template_id = ig_es_get_request_data( 'id' );
			return $template_id;
		}

		public function get_editor_type_from_url() {
			$editor_type = ig_es_get_request_data( 'editor-type' );
			if ( empty( $editor_type ) ) {
				$editor_type = IG_ES_DRAG_AND_DROP_EDITOR;
			}
			return $editor_type;
		}

		public static function set_screen( $status, $option, $value ) {
			return $value;
		}

		public function render() {

			$data = ig_es_get_request_data( 'data', array(), false );
			$message_data  = array();

			$template_action = ig_es_get_request_data( 'ig_es_template_action' );

			if ( ! empty( $template_action ) ) {

				if ( empty( $data['subject'] ) ) {
					$message      = __( 'Please add a subject.', 'email-subscribers' );
					$message_data = array(
						'message' => $message,
						'type'    => 'error',
					);
				}
			}

			$action = ig_es_get_request_data( 'action' );
			if ( 'added' === $action ) {
				$message      = __( 'Template added successfully.', 'email-subscribers' );
				$message_data = array(
					'message' => $message,
					'type'    => 'success',
				);
			} elseif ( 'updated' === $action ) {
				$message      = __( 'Template updated successfully.', 'email-subscribers' );
				$message_data = array(
					'message' => $message,
					'type'    => 'success',
				);
			}

			$this->show_form( $message_data );
		}


		/**
		 * Add an Tag button to WP Editor
		 *
		 * @param string $editor_id Editor id
		 *
		 * @since 5.4.10
		 */
		public function add_tag_button( $editor_id ) {

			if ( ! ES()->is_es_admin_screen() ) {
				return;
			}

			$template_type = isset( $this->campaign_data['type'] ) ? $this->campaign_data['type'] : '';
			?>

			<div id="ig-es-add-tags-button" class="merge-tags-wrapper relative bg-white inline-block">
				<button type="button" class="button">
					<span class="dashicons dashicons-tag"></span>
					<?php echo esc_html__( 'Add Tags', 'email-subscribers' ); ?>
				</button>
				<div x-show="open" id="ig-es-tags-dropdown" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
				x-transition:leave-end="transform opacity-0 scale-95" class="absolute center-0 z-10 hidden w-56 origin-top-right rounded-md shadow-lg">
					<div class="bg-white rounded-md shadow-xs">
						<?php $this->show_merge_tags( $template_type ); ?>
					</div>
				  </div>
		  </div>
			<?php
		}

		public function get_campaign_tags() {

			$post_notification_tags = $this->get_post_notification_tags();

			$template_tags = array(
				'post_notification' => $post_notification_tags,
			);

			return apply_filters( 'ig_es_campaign_tags', $template_tags );
		}

		public function get_post_notification_tags() {
			$post_notification_tags = array(
				'{{post.date}}',
				'{{post.title}}',
				'{{post.image}}',
				'{{post.excerpt}}',
				'{{post.description}}',
				'{{post.author}}',
				'{{post.link}}',
				'{{post.link_with_title}}',
				'{{post.link_only}}',
				'{{post.full}}',
				'{{post.cats}}',
				'{{post.more_tag}}',
				'{{post.image_url}}'
			);
			return apply_filters( 'ig_es_post_notification_tags', $post_notification_tags );
		}

		public function get_subscriber_tags() {
			$subscriber_tags = array(
				'{{subscriber.name}}',
				'{{subscriber.first_name}}',
				'{{subscriber.last_name}}',
				'{{subscriber.email}}',
			);
			return apply_filters( 'ig_es_subscriber_tags', $subscriber_tags );
		}

		public function get_site_tags() {
			$site_tags = array(
				'{{site.total_contacts}}',
				'{{site.url}}',
				'{{site.name}}',
			);

			return apply_filters( 'ig_es_site_tags', $site_tags );
		}

		public function show_merge_tags( $template_type ) {
			$subscriber_tags = $this->get_subscriber_tags();
			if ( ! empty( $subscriber_tags ) ) {
				?>
				<div id="ig-es-subscriber-tags">
					<?php
						$this->render_merge_tags( $subscriber_tags );
					?>
				</div>
				<?php
			}
			$site_tags = $this->get_site_tags();
			if ( ! empty( $site_tags ) ) {
				?>
				<div id="ig-es-site-tags">
					<?php
						$this->render_merge_tags( $site_tags );
					?>
				</div>
				<?php
			}
			$template_tags = $this->get_campaign_tags();
			if ( ! empty( $template_tags ) ) {
				?>
				<div id="ig-es-campaign-tags">
				<?php foreach ($template_tags as $type => $tags ) : ?>
					<?php
						$class = $type !== $template_type ? 'hidden' : '';
					?>
					<div class="ig-es-campaign-tags <?php echo esc_attr( $type ); ?> <?php echo esc_attr( $class ); ?>">
							<?php
								
								$this->render_merge_tags( $tags );
							?>
					</div>
				<?php endforeach; ?>
				</div>
				<?php
			}
		}

		public function render_merge_tags( $merge_tags = array() ) {
			if ( empty( $merge_tags ) ) {
				return;
			}
			foreach ( $merge_tags as $tag_key => $tag ) {
				?>
				<span data-tag-text="<?php echo is_string( $tag_key ) ? esc_attr( $tag ) : ''; ?>" class="ig-es-merge-tag cursor-pointer block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900">
					<?php echo is_string( $tag_key ) ? esc_html( $tag_key ) : esc_html( $tag ); ?>
				</span>
				<?php
			}
		}

		/**
		 * Method to show send test email and campaign content section.
		 *
		 * @param array $template_data Broadcast data
		 *
		 * @since 5.4.4.1.
		 *
		 */
		public function show_campaign_preview_options_content( $template_data = array() ) {

			$type       = isset( $template_data['type'] ) ? $template_data['type'] : 'campaign';
			$subject    = isset( $template_data['subject'] ) ? $template_data['subject'] : '';
			$test_email = ES_Common::get_admin_email();
			$trim_character_count = 30;

			if ( !( strlen($subject) <= $trim_character_count ) ) {
				$subject = substr( $subject, 0, $trim_character_count );
				$subject = substr( $subject, 0, strrpos( $subject, ' ' ) );
				$subject = $subject . '...';
			}
			
			?>
			<div id="campaign-email-preview-container">


				<div class="campaign-email-preview-container-right">

					<?php do_action( 'ig_es_template_preview_test_email_content', $template_data ); ?>

				</div>


			</div>
			<?php
		}


		/**
		 * Method to display newsletter setting form
		 *
		 * @param array $template_data Posted campaign data
		 *
		 * @since  4.4.2 Added $template_data param
		 */
		public function show_form( $message_data = array() ) {

			$template_data = $this->template_data;

			$template_id      = ! empty( $template_data['ID'] ) ? $template_data['ID'] : 0;
			$template_subject = ! empty( $template_data['post_title'] ) ? $template_data['post_title'] : '';
			$template_status  = ! empty( $template_data['post_status'] ) ? $template_data['post_status'] : 'draft';
			$template_type    = ! empty( $template_data['meta']['es_template_type'] ) ? $template_data['meta']['es_template_type'] : IG_CAMPAIGN_TYPE_NEWSLETTER;
			$editor_type      = ! empty( $template_data['meta']['es_editor_type'] ) ? $template_data['meta']['es_editor_type'] : '';

			?>

			<div id="edit-campaign-form-container" data-editor-type="<?php echo esc_attr( $editor_type ); ?>" data-campaign-type="<?php echo esc_attr( $template_type ); ?>" class="<?php echo esc_attr( $editor_type ); ?> font-sans pt-1.5 wrap">
				<?php
				if ( ! empty( $message_data ) ) {
					$message = $message_data['message'];
					$type    = $message_data['type'];
					ES_Common::show_message( $message, $type );
				}
				?>
				<form action="#" method="POST" id="campaign_form">
					<input type="hidden" id="template_id" name="data[id]" value="<?php echo esc_attr( $template_id ); ?>"/>
					<input type="hidden" id="template_status" name="data[status]" value="<?php echo esc_attr( $template_status ); ?>"/>
					<input type="hidden" id="template_type" name="data[meta][es_template_type]" value="<?php echo esc_attr( $template_type ); ?>"/>
					<input type="hidden" id="editor_type" name="data[meta][es_editor_type]" value="<?php echo esc_attr( $editor_type ); ?>"/>
					<?php wp_nonce_field( 'ig-es-template-nonce', 'ig_es_template_nonce' ); ?>
					<fieldset class="block es_fieldset">
						<div class="mx-auto wp-heading-inline max-w-7xl">
							<header class="mx-auto max-w-7xl">
								<div class="md:flex md:items-center md:justify-between">
									<div class="flex md:3/5 lg:w-7/12 xl:w-3/5">
										<div class=" min-w-0 md:w-3/5 lg:w-1/2">
										   <nav class="text-gray-400 my-0" aria-label="Breadcrumb">
											<ol class="list-none p-0 inline-flex">
													<li class="flex items-center text-sm tracking-wide">
														<a class="hover:underline" href="admin.php?page=es_gallery&manage-templates=yes"><?php echo esc_html__( 'Template Gallery', 'email-subscribers' ); ?>
														</a>
														<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
													</li>
											</ol>
										   </nav>
										</div>
									</div>
									<div class="flex md:mt-0 xl:ml-4">

										<div class="inline-block text-left">
											<button id="view_template_preview_button" type="button"
													class="ig-es-inline-loader inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-indigo-600 transition duration-150 ease-in-out border border-indigo-500 rounded-md cursor-pointer select-none hover:text-indigo-500 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:border-indigo-600 md:px-2 lg:px-3 xl:px-4">
													<span>
													<?php
														echo esc_html__( 'Preview', 'email-subscribers' );
													?>
													</span>
													<svg class="es-btn-loader animate-spin h-4 w-4 text-indigo"
																	xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
														<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
																stroke-width="4"></circle>
														<path class="opacity-75" fill="currentColor"
																d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
													</svg>
											</button>
										</div>
										<div class="inline-block text-left md:mr-2 md:ml-2">
											<button type="submit" id="ig_es_save_campaign_btn" name="ig_es_template_action" class="inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4" value="save">
												<span class="ig_es_campaign_send_option_text">
													<?php echo esc_html__( 'Save', 'email-subscribers' ); ?>
												</span>
											</button>
										</div>
									</div>
								</div>
							</header>
						</div>
						<div class="mx-auto max-w-7xl">
							<hr class="wp-header-end">
						</div>
						<div class="mx-auto mt-6 es_campaign_first max-w-7xl">
							<div>
								<div class="bg-white rounded-lg shadow-md">
									<div class="md:flex">
										<div class="campaign_main_content py-4 pl-2">
											<div class="block px-4 py-2">
												<label for="ig_es_campaign_subject" class="text-sm font-medium leading-5 text-gray-700"><?php echo esc_html__( 'Subject', 'email-subscribers' ); ?></label>
												<div class="w-full mt-1 relative text-sm leading-5 rounded-md shadow-sm form-input border-gray-400">
													
													
													<div>
														<input id="ig_es_campaign_subject"  style="width:95%;" class="outline-none" name="data[subject]" value="<?php echo esc_attr( $template_subject ); ?>"/>
													</div>
													
												</div>
											</div>
											<div class="w-full px-4 pt-1 pb-2 mt-1 message-label-wrapper">
												<label for="message" class="text-sm font-medium leading-5 text-gray-700"><?php echo esc_html__( 'Message', 'email-subscribers' ); ?></label>
												<?php
												if ( IG_ES_CLASSIC_EDITOR === $editor_type ) {
													$editor_id       = 'edit-es-campaign-body';
													$editor_content  = ! empty( $template_data['post_content'] ) ? $template_data['post_content'] : '';
													$editor_settings = array(
														'textarea_name' => 'data[body]',
														'textarea_rows' => 40,
														'media_buttons' => true,
														'tinymce'      => true,
														'quicktags'    => true,
														'editor_class' => 'wp-campaign-body-editor',
													);
													add_filter( 'tiny_mce_before_init', array( 'ES_Common', 'override_tinymce_formatting_options' ), 10, 2 );
													add_filter( 'mce_external_plugins', array( 'ES_Common', 'add_mce_external_plugins' ) );
													wp_editor( $editor_content, $editor_id, $editor_settings );
													$this->show_avaialable_keywords();
												} else {
													?>
													<div id="ig-es-dnd-merge-tags" class="hidden">
														<div x-show="open" id="ig-es-dnd-tags-dropdown" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
														x-transition:leave-end="transform opacity-0 scale-95" class="absolute center-0 z-10 hidden w-56 origin-top-right rounded-md shadow-lg">
															<div class="bg-white rounded-md shadow-xs">
																<?php $this->show_merge_tags( $template_type ); ?>
															</div>
														</div>
													</div>
													
													<style type="text/css">
														.admin_page_es_template #ig-es-dnd-merge-tags-wrapper {
															display: none;
														}
													</style>
													<textarea id="campaign-dnd-editor-data" name="data[meta][es_dnd_editor_data]" style="display:none;">
														<?php
															$dnd_editor_data = ! empty( $template_data['meta']['es_dnd_editor_data'] ) ? wp_json_encode(maybe_unserialize( $template_data['meta']['es_dnd_editor_data'] )) : '';
															echo esc_html( $dnd_editor_data );
														?>
													</textarea>
													<script>
														jQuery(document).ready(function($){
															let editor_data = jQuery('#campaign-dnd-editor-data').val().trim();
															if ( '' !== editor_data ) {
																let is_valid_json = ig_es_is_valid_json( editor_data );
																if ( is_valid_json ) {
																	editor_data = JSON.parse( editor_data );
																}
																jQuery(document).on("es_drag_and_drop_editor_loaded",function (event) {
																	window.esVisualEditor.importMjml(editor_data);
																});
															}
															jQuery(document).on('es_drag_and_drop_editor_loaded',()=>{
																window.esVisualEditor.on('change:changesCount', (editorModel, changesCount) => {
																	if (changesCount > 0) {
																		ig_es_sync_dnd_editor_content('#campaign-dnd-editor-data');
																	}
																});
															});
														});
													</script>
													<?php
												}
												?>
											</div>
											<script>
												jQuery(document).ready(function($){
													var clipboard = new ClipboardJS('.ig-es-merge-tag', {
													text: function(trigger) {
															let tag_text = $(trigger).data('tag-text');
															if ( '' === tag_text ) {
																tag_text = $(trigger).text();
															}
															return tag_text.trim();
													}
													});

													clipboard.on('success', function(e) {
														let sourceElem    = e.trigger;
														let sourceID	  = $(sourceElem).closest('.merge-tags-wrapper').attr('id');
														let targetID      = 'ig-es-add-tag-icon' === sourceID ? 'ig_es_campaign_subject': 'edit-es-campaign-body';
														let clipBoardText = e.text;
														let editorType    = $('#editor_type').val();
														if ( 'classic' === editorType || 'ig_es_campaign_subject' === targetID ) {
															var target        = document.getElementById(targetID);
											
															if (target.setRangeText) {
																target.focus();
																//if setRangeText function is supported by current browser
																target.setRangeText(clipBoardText);
															} else {
																target.focus()
																document.execCommand('insertText', false /*no UI*/, clipBoardText);
															}
															if ( 'edit-es-campaign-body' === targetID && 'undefined' !== typeof tinymce.activeEditor ) {
																tinymce.activeEditor.execCommand('mceInsertContent', false, clipBoardText);
															}
														} else {
															// Insert placeholders into DND editor
															// var canvasDoc = window.esVisualEditor.Canvas.getBody().ownerDocument;
															// // Insert text at the current pointer position
															// canvasDoc.execCommand("insertText", false, 'Test');
															let selectedComponent = window.esVisualEditor.getSelected();
															let selectedContent   = selectedComponent.get('content');
															selectedComponent.set({
																content: selectedContent + clipBoardText
															});
															$("#ig-es-dnd-merge-tags-wrapper #ig-es-dnd-tags-dropdown").hide();
														}
													});
												});
											</script>
											<?php do_action( 'ig_es_after_template_left_pan_settings', $template_data ); ?>
										</div>
										<div class="campaign_side_content ml-2 bg-gray-100 rounded-r-lg">
											<?php
												do_action( 'ig_es_before_template_settings', $template_data );
											?>
											<div class="block pt-1 mx-4">
												<div class="hidden" id="campaign-preview-popup">
													<div class="fixed top-0 left-0 z-50 flex items-center justify-center w-full h-full" style="background-color: rgba(0,0,0,.5);">
														<div id="campaign-preview-main-container" class="absolute h-auto pt-2 ml-16 mr-4 text-left bg-white rounded shadow-xl z-80 w-1/2 md:max-w-5xl lg:max-w-7xl md:pt-3 lg:pt-2">
															<div class="py-2 px-4">
																	<div class="flex">
																		<button id="close-campaign-preview-popup" class="text-sm font-medium tracking-wide text-gray-700 select-none no-outline focus:outline-none focus:shadow-outline-red hover:border-red-400 active:shadow-lg">
																			<svg class="h-5 w-5 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
																				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
																			</svg>
																		</button>
																	</div>
															</div>
															<div id="campaign-browser-preview-container">

																<?php do_action( 'ig_es_template_preview_options_content', $template_data ); ?>

																<div id="campaign-preview-iframe-container" class="pt-4 list-decimal popup-preview">
																</div>
															</div>

														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<?php
									if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type ) {
										?>
										<div class="campaign-drag-and-drop-editor-container">
										<?php
										$editor_settings = array(
											'attributes' => array(
												'data-html-textarea-name'  => 'data[body]',
											),
										);
										( new ES_Drag_And_Drop_Editor() )->show_editor( $editor_settings );
										?>
										</div>
										<?php
										$this->show_avaialable_keywords();
									}
									?>
								</div>
							</div>
					</fieldset>
				</form>
			</div>

			<?php
		}

		/**
		 * Show option to save campaign as template
		 *
		 * @return void
		 *
		 * @since 5.3.3
		 */
		public function show_template_setting_fields( $data ) {
			$template_id = ! empty( $data['ID'] ) ? $data['ID'] : 0;
			$values      = get_post_custom( $template_id );

			$selected = isset( $values['es_template_type'] ) ? esc_attr( $values['es_template_type'][0] ) : '';

			$template_type = ES_Common::get_campaign_types( array( 'sequence' ) );
			?>
			<div class="pt-4 pb-4 mx-4">
				<div class="flex w-full border-b border-gray-200 pb-2">
					<div class="w-11/12 text-sm font-normal text-gray-600 leading-9"><?php esc_html_e( 'Template type', 'email-subscribers' ); ?>												</div>
					<div>
						<label for="es_template_type" class="inline-flex items-center cursor-pointer ">
						<span class="relative">
						<select style="margin: 0.20rem 0;" name="data[es_template_type]" id="es_template_type">
							<?php
							if ( ! empty( $template_type ) ) {
								foreach ( $template_type as $key => $value ) {
									echo '<option value=' . esc_attr( $key ) . ' ' . selected( $selected, $key, false ) . '>' . esc_html( $value ) . '</option>';
								}
							}
							?>
						</select>
						</span>
						</label>
					</div>
				</div>
				<?php
				$thumbnail_url = get_the_post_thumbnail_url( $template_id );
				?>
				<div class="flex w-full pt-3">
					<div class="w-11/12 text-sm font-normal text-gray-600 leading-8">
						<?php esc_html_e( 'Preview image', 'email-subscribers' ); ?>												</div>
					<div>
						<label for="es_template_image" class="inline-flex items-center cursor-pointer ">
						<span class="relative">
							<button type="button" id="ig-es-add-template-image" class="button <?php echo $thumbnail_url ? 'hidden' : ''; ?>">
								<?php echo esc_html__( 'Add image', 'email-subscribers' ); ?>
							</button>
							<input type="hidden" id="ig_es_template_attachment_id" name="data[template_attachment_id]" value=""/>
							<div id="ig-es-template-image-attachment-container" class="my-1 text-sm relative <?php echo $thumbnail_url ? '' : 'hidden'; ?>">
								<span class="flex-1 flex items-center">
									<img src="<?php echo $thumbnail_url ? esc_url( $thumbnail_url ) : ''; ?>" id="ig_es_template_attachment_image" style="width: 50px; height: 50px; max-width: none;">
									<a id="ig-es-delete-template-image" href="#"
									class="font-medium text-red-300 hover:text-red-500 absolute" style="top: -15%;right: -15%;">
										<svg class="w-6 h-6 text-red-400 hover:text-red-500 " fill="none"
											stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path
													stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
													d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
									</a>
								</span>
							</div>
						</span>
						</label>
					</div>
				</div>
			</div>
			<?php
		}

		public function add_campaign_body_data( $template_data ) {

			$template_id = ! empty( $template_data['id'] ) ? $template_data['id'] : 0;
			if ( ! empty( $template_data['body'] ) ) {
				$current_user = wp_get_current_user();
				$username     = $current_user->user_login;
				$useremail    = $current_user->user_email;
				$display_name = $current_user->display_name;

				$contact_id = ES()->contacts_db->get_contact_id_by_email( $useremail );
				$first_name = '';
				$last_name  = '';

				// Use details from contacts data if present else fetch it from wp profile.
				if ( ! empty( $contact_id ) ) {
					$contact_data = ES()->contacts_db->get_by_id( $contact_id );
					$first_name   = $contact_data['first_name'];
					$last_name    = $contact_data['last_name'];
				} elseif ( ! empty( $display_name ) ) {
					$contact_details = explode( ' ', $display_name );
					$first_name      = $contact_details[0];
					// Check if last name is set.
					if ( ! empty( $contact_details[1] ) ) {
						$last_name = $contact_details[1];
					}
				}

				$template_body = $template_data['body'];
				$template_body = ES_Common::es_process_template_body( $template_body, $template_id );
				$template_body = ES_Common::replace_keywords_with_fallback( $template_body, array(
					'FIRSTNAME' => $first_name,
					'NAME'      => $username,
					'LASTNAME'  => $last_name,
					'EMAIL'     => $useremail
				) );


				$template_body = ES_Common::replace_keywords_with_fallback( $template_body, array(
					'subscriber.first_name' => $first_name,
					'subscriber.name'      => $username,
					'subscriber.last_name'  => $last_name,
					'subscriber.email'     => $useremail
				) );

				$template_type = $template_data['meta']['es_template_type'];

				$template_data['body'] = $template_body;

				if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $template_type ) {
					$template_data = self::replace_post_notification_merge_tags_with_sample_post( $template_data );
				} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $template_type ) {
					$template_data = self::replace_post_digest_merge_tags_with_sample_posts( $template_data );
				}

				$template_body = ! empty( $template_data['body'] ) ? $template_data['body'] : '';

				// If there are blocks in this content, we shouldn't run wpautop() on it.
				$priority = has_filter( 'the_content', 'wpautop' );

				if ( false !== $priority ) {
					// Remove wpautop to avoid p tags.
					remove_filter( 'the_content', 'wpautop', $priority );
				}

				$template_body = apply_filters( 'the_content', $template_body );

				$template_data['body'] = $template_body;

				return $template_data;
			}

		}

		/**
		 * Method to get preview HTML for campaign
		 *
		 * @return $response
		 *
		 * @since 4.4.7
		 */
		public function get_template_preview() {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

			$response = array();

			$data = ig_es_get_request_data( 'data', array(), false );

			$template_data            = $this->add_campaign_body_data( $data );
			$response['preview_html'] = $template_data['body'];

			if ( ! empty( $response ) ) {
				wp_send_json_success( $response );
			} else {
				wp_send_json_error();
			}

		}

		/**
		 * Method to get campaign inline preview data.
		 *
		 * @param array $template_data Broadcast data.
		 *
		 * @return array $preview_data
		 *
		 * @since 4.4.7
		 */
		public function get_campaign_inline_preview_data( $template_data = array() ) {
			$list_id      = ! empty( $template_data['list_ids'] ) ? $template_data['list_ids'] : 0;
			$preview_data = array();
			$first_name   = '';
			$last_name    = '';
			$email        = '';

			if ( ! empty( $list_id ) ) {
				// Check if multiple lists selection is enabled.
				if ( is_array( $list_id ) && ! empty( $list_id ) ) {
					// Since we need to get only one sample email for showing the preview, we can get it from the first list itself.
					$list_id = $list_id[0];
				}
				$subscribed_contacts = ES()->lists_contacts_db->get_subscribed_contacts_from_list( $list_id );
				if ( ! empty( $subscribed_contacts ) ) {
					$subscribed_contact = array_shift( $subscribed_contacts );
					$contact_id         = ! empty( $subscribed_contact['contact_id'] ) ? $subscribed_contact['contact_id'] : 0;
					if ( ! empty( $contact_id ) ) {
						$subscriber_data = ES()->contacts_db->get_by_id( $contact_id );
						if ( ! empty( $subscriber_data ) ) {
							$first_name = ! empty( $subscriber_data['first_name'] ) ? $subscriber_data['first_name'] : '';
							$last_name  = ! empty( $subscriber_data['last_name'] ) ? $subscriber_data['first_name'] : '';
							$email      = ! empty( $subscriber_data['email'] ) ? $subscriber_data['email'] : '';
						}
					}
				}
			}

			$preview_data['campaign_subject'] = ! empty( $template_data['subject'] ) ? esc_html( $template_data['subject'] ) : '';
			$preview_data['contact_name']     = esc_html( $first_name . ' ' . $last_name );
			$preview_data['contact_email']    = esc_html( $email );

			return $preview_data;
		}

		public function add_post_notification_data( $template_data ) {

			$categories         = ! empty( $template_data['es_note_cat'] ) ? $template_data['es_note_cat'] : array();
			$es_note_cat_parent = $template_data['es_note_cat_parent'];
			$categories         = ( ! empty( $es_note_cat_parent ) && in_array( $es_note_cat_parent, array( '{a}All{a}', '{a}None{a}' ), true ) ) ? array( $es_note_cat_parent ) : $categories;

			// Check if custom post types are selected.
			if ( ! empty( $template_data['es_note_cpt'] ) ) {
				// Merge categories and selected custom post types.
				$categories = array_merge( $categories, $template_data['es_note_cpt'] );
			}


			$template_data['categories'] = ES_Common::convert_categories_array_to_string( $categories );

			return $template_data;
		}

		public static function replace_post_notification_merge_tags_with_sample_post( $template_data ) {

			if ( ! empty( $template_data['id'] ) ) {

				$args         = array(
					'numberposts' => '1',
					'order'       => 'DESC',
					'post_status' => 'publish',
				);
				$recent_posts = wp_get_recent_posts( $args, OBJECT );

				if ( count( $recent_posts ) > 0 ) {
					$post = array_shift( $recent_posts );

					$post_id          = $post->ID;
					$template_id      = $template_data['id'];
					$template_body    = ! empty( $template_data['body'] ) ? $template_data['body'] : '';
					$template_subject = ! empty( $template_data['subject'] ) ? $template_data['subject'] : '';

					$template_subject = ES_Handle_Post_Notification::prepare_subject( $template_subject, $post );
					$template_body    = ES_Handle_Post_Notification::prepare_body( $template_body, $post_id, $template_id );

					$template_data['subject'] = $template_subject;
					$template_data['body']    = $template_body;
				}
			}

			return $template_data;
		}

		public static function replace_post_digest_merge_tags_with_sample_posts( $template_data ) {

			if ( ! empty( $template_data['id'] ) && class_exists( 'ES_Post_Digest' ) ) {
				$ignore_stored_post_ids = true;
				$ignore_last_run        = true;
				$template_id 			= $template_data['id'];
				$template_body 			= $template_data['body'];
				$post_ids               = ES_Post_Digest::get_matching_post_ids( $template_id, $ignore_stored_post_ids, $ignore_last_run );
				$template_body          = ES_Post_Digest::process_post_digest_template( $template_body, $post_ids );
				$template_data['body']  = $template_body;
			}

			return $template_data;
		}

		public function show_avaialable_keywords() {
			?>
			<div class="campaign-keyword-wrapper mt-1 p-4 w-full border border-gray-300">
				<!-- Start-IG-Code -->
				<p id="post_notification" class="pb-2 border-b border-gray-300">
					<a href="https://www.icegram.com/documentation/what-keywords-can-be-used-while-designing-the-campaign/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Post Notification: ', 'email-subsribers' ); ?>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.first_name | fallback:'there'}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.last_name}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.name | fallback:'there'}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.email}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{DATE}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.title}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.image}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.excerpt}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.description}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.author}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.author_avatar}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.author_avatar_link}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.link}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.link_with_title}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.link_only}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{post.full}}</span>
				</p>
				<!-- End-IG-Code -->
				<p id="newsletter" class="py-2 border-b border-gray-300">
					<a href="https://www.icegram.com/documentation/what-keywords-can-be-used-while-designing-the-campaign/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Broadcast:', 'email-subscribers' ); ?>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.first_name | fallback:'there'}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.last_name}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.name | fallback:'there'}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.email}}</span>
				</p>
				<!-- Start-IG-Code -->
				<div id="post_digest" class="pt-2 pb-0">
					<span style="font-size: 0.8em; margin-left: 0.3em; padding: 2px; background: #e66060; color: #fff; border-radius: 2px; ">Pro</span>&nbsp;
					<a href="https://www.icegram.com/send-post-digest-using-email-subscribers-plugin/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_post_digest_post" target="_blank"><?php esc_html_e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php esc_html_e( 'for Post Digest:', 'email-subscribers' ); ?>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.first_name | fallback:'there'}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.last_name}}</span>
					<span class="ig-es-workflow-variable-outer inline-block px-2 py-2 mr-2 mb-2 text-xs font-bold bg-gray-100 hover:bg-gray-300 rounded-md ">{{subscriber.name | fallback:'there'}}</span>
					<div class="post_digest_block"> {{post.digest}} <br/><?php esc_html_e( 'Any keywords related Post Notification', 'email-subscribers' ); ?> <br/>{{/post.digest}} </div>
				</div>
			</div>
			<!-- End-IG-Code -->
			<?php
		}

		/**
		 * Save campaign as a template
		 */
		public function process_submission() {

			$template_action = ig_es_get_request_data( 'ig_es_template_action' );

			if ( ! empty( $template_action ) ) {

				$template_nonce = ig_es_get_request_data( 'ig_es_template_nonce' );

				// Verify nonce.
				if ( wp_verify_nonce( $template_nonce, 'ig-es-template-nonce' ) ) {
		
					$template_data          = ig_es_get_request_data( 'data', array(), false );
					$template_id            = ! empty( $template_data['id'] ) ? $template_data['id'] : 0;
					$template_type          = ! empty( $template_data['es_template_type'] ) ? $template_data['es_template_type'] : IG_CAMPAIGN_TYPE_NEWSLETTER;
					$template_body          = ! empty( $template_data['body'] ) ? $template_data['body'] : '';
					$template_subject       = ! empty( $template_data['subject'] ) ? $template_data['subject'] : '';
					$template_attachment_id = ! empty( $template_data['template_attachment_id'] ) ? $template_data['template_attachment_id'] : '';
					$template_status        = 'save' === $template_action ? 'publish' : 'draft';

					$data = array(
						'post_title'   => $template_subject,
						'post_content' => $template_body,
						'post_type'    => 'es_template',
						'post_status'  => $template_status,
					);

					$action = '';
					if ( empty( $template_id ) ) {
						$template_id = wp_insert_post( $data );
						$action      = 'added';
					} else {
						$data['ID']  = $template_id;
						$template_id = wp_update_post( $data );
						$action      = 'updated';
					}
	
					$is_template_added = ! ( $template_id instanceof WP_Error );
	
					if ( $is_template_added ) {

						if ( ! empty( $template_attachment_id ) ) {
							set_post_thumbnail( $template_id, $template_attachment_id );
						}
	
						$editor_type = ! empty( $template_data['meta']['es_editor_type'] ) ? $template_data['meta']['es_editor_type'] : '';
	
						$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
	
						if ( $is_dnd_editor ) {
							$dnd_editor_data = array();
							if ( ! empty( $template_data['meta']['es_dnd_editor_data'] ) ) {
								$dnd_editor_data = $template_data['meta']['es_dnd_editor_data'];
								$dnd_editor_data = json_decode( $dnd_editor_data );
								update_post_meta( $template_id, 'es_dnd_editor_data', $dnd_editor_data );
							}
						} else {
							$custom_css = ! empty( $template_data['meta']['es_custom_css'] ) ? $template_data['meta']['es_custom_css'] : '';
							update_post_meta( $template_id, 'es_custom_css', $custom_css );
						}
	
						update_post_meta( $template_id, 'es_editor_type', $editor_type );
						update_post_meta( $template_id, 'es_template_type', $template_type );
					}
	
					if ( ! empty( $template_id ) ) {
						$template_url = admin_url( 'admin.php?page=es_template&id=' . $template_id . '&action=' . $action );
						wp_safe_redirect( $template_url );
						exit();
					} else {
						$message = __( 'An error has occured. Please try again later', 'email-subscribers' );	
						ES_Common::show_message( $message, 'error' );
					}
				}
			}

		}

		public function delete_template() {
			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

			$template_id = ig_es_get_request_data( 'template_id', 0, false );
			if ( ! empty( $template_id ) ) {
				$deleted = wp_delete_post( $template_id );
				if ( $deleted ) {
					wp_send_json_success();
				}
			}

			wp_send_json_error();
		}

		/**
		 * Method to load admin views
		 *
		 * @since 5.5.4
		 *
		 * @param string $view View name.
		 * @param array  $imported_variables Passed variables.
		 * @param mixed  $path Path to view file.
		 */
		public static function get_view( $view, $imported_variables = array(), $path = false ) {

			if ( $imported_variables && is_array( $imported_variables ) ) {
				extract( $imported_variables ); // phpcs:ignore
			}

			if ( ! $path ) {
				$path = ES_PLUGIN_DIR . 'lite/admin/views/';
			}

			include $path . $view . '.php';
		}
	}

}
