<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The premium services-ui specific functionality of the plugin.
 *
 * @since      4.6.1
 *
 * @package    Email_Subscribers
 */

if ( ! class_exists( 'IG_ES_Premium_Services_UI' ) ) {
	
	/**
	 * The premium services-ui specific functionality of the plugin.
	 *
	 */
	class IG_ES_Premium_Services_UI {

		/**
		 * Class instance.
		 *
		 * @var Onboarding instance
		 */
		protected static $instance = null;
		
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 4.6.1
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
		}
	
		/**
		 * Get class instance.
		 * 
		 * @since 4.6.1
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		/**
		 * Method to hook required action/filters to show ui components used in premium services.
		 * 
		 * @since 4.6.1
		 */
		public function init() {

			// Add ui components only if trial is valid or user is a premium user.
			if ( ES()->is_trial_valid() || ES()->is_premium() ) {
				
				// Add UI for CSS inliner only if service is valid.
				if ( ES()->validate_service_request( array( 'css_inliner' ) ) ) {
					add_action( 'ig_es_after_broadcast_left_pan_settings', array( &$this, 'add_custom_css_field' ) );
					add_action( 'edit_form_after_editor', array( &$this, 'add_custom_css_block' ), 11, 2 );
					add_action( 'save_post', array( &$this, 'update_template' ), 10, 2 );
				}

				// Add UI for spam score check only if service is valid.
				if ( ES()->validate_service_request( array( 'spam_score_check' ) ) ) {
					add_action( 'add_meta_boxes', array( &$this, 'add_metaboxes' ) );
					add_action( 'ig_es_after_broadcast_right_pan_settings', array( &$this, 'add_check_spam_score_button' ) );
				}

				// Add UI for utm tracking only if service is valid.
				if ( ES()->validate_service_request( array( 'utm_tracking' ) ) ) {
					add_action( 'add_meta_boxes', array( &$this, 'register_utm_tracking_metabox' ) );
					add_action( 'ig_es_save_template', array( &$this, 'save_utm_campaign' ), 10, 2 );
					add_action( 'ig_es_after_broadcast_tracking_options_settings', array( &$this, 'add_broadcast_utm_tracking_option' ) );
					add_filter( 'ig_es_registered_settings', array( &$this, 'add_utm_tracking_option_in_settings' ), 10, 2 );
				}
			}
		}

		/**
		 * Method to add custom CSS field in the broadcast screen
		 *
		 * @param array $broadcast_data
		 * @return void
		 */
		public function add_custom_css_field( $broadcast_data ) {
			$custom_css = ! empty( $broadcast_data['meta']['es_custom_css'] ) ? $broadcast_data['meta']['es_custom_css'] : '';
			?>
			<div class="w-full px-4 py-2">
				<label for="email" class="block text-sm font-medium leading-5 text-gray-700"><?php echo esc_html__( 'Inline CSS', 'email-subscribers' ); ?></label>
				<textarea class="mt-1 w-full h-10 border border-gray-300 rounded-md"  name="broadcast_data[meta][es_custom_css]"  id="inline_css"><?php echo esc_html( $custom_css ); ?></textarea>
			</div>
			<?php
		}
		
		/**
		 * Add Custom CSS block for ES Template
		 *
		 * @since 3.x
		 */
		public function add_custom_css_block() {
			global $post, $pagenow;
			if ( 'es_template' != $post->post_type ) {
				return;
			}
			$es_custom_css = '';
			if ( 'post-new.php' != $pagenow ) {
				$es_custom_css = get_post_meta( $post->ID, 'es_custom_css', true );
			}
			?>
			<p>
				<label><?php echo esc_html__( 'Custom CSS', 'email-subscribers' ); ?></label><br/>
				<textarea style="height:50%;width: 100%" name="es_custom_css"><?php esc_attr_e( $es_custom_css ); ?></textarea>
			</p>
			<?php
		}
		
		/**
		 * Hooked to save_post WordPress action
		 * Update ES Template data
		 *
		 * @param $post_id
		 * @param $post
		 *
		 * @since 3.x
		 */
		public function update_template( $post_id, $post ) {
			if ( empty( $post_id ) || empty( $post ) ) {
				return;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( is_int( wp_is_post_revision( $post ) ) ) {
				return;
			}
			if ( is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( 'es_template' != $post->post_type ) {
				return;
			}
			
			if ( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
				// Get custom CSS code. Don't sanitize it since it removes CSS code.
				$es_custom_css = ig_es_get_data( $_POST, 'es_custom_css', false, false );
				if ( false !== $es_custom_css ) {
					update_post_meta( $post_id, 'es_custom_css', $es_custom_css );
				}

				/**
				 * Save ES Template action
				 *
				 * @since 4.3.1
				 */
				do_action( 'ig_es_save_template', $post_id, $post_id );
			}
		}
		
		/**
		 * Method to add metaboxes
		 * 
		 * @since 4.6.1
		 */
		public function add_metaboxes() {
			add_meta_box( 'es_spam', __( 'Get Spam Score', 'email-subscribers' ), array( &$this, 'add_spam_score_metabox' ), 'es_template', 'side', 'default' );
		}
		
		/**
		 * Method to add spam score metabox
		 * 
		 * @since 4.6.1
		 */
		public function add_spam_score_metabox() {
			global $post;
			?>
			<a style="margin: 0.4rem 0 0 0;padding-top: 3px;" href="#" class="button button-primary es_spam"><?php echo esc_html__( 'Check', 'email-subscribers' ); ?></a>
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/spinner-2x.gif" class="es-loader-img inline-flex align-middle pl-2 h-5 w-7" style="display:none;"/>
			<span class="es-spam-score"></span>
			<input type="hidden" id="es_template_id" value="<?php echo esc_attr( $post->ID ); ?>"/>
			<div class="es-logs es-spam-success" style="display:none;"><?php echo esc_html__( 'Awesome score. Your email is almost perfect.', 'email-subscribers' ); ?></div>
			<div class="es-logs es-spam-error" style="display:none;"><?php echo esc_html__( 'Ouch! your email needs improvement. ', 'email-subscribers' ); ?></div>
			<div class="es-spam-error-log" style="display:none;">
				<?php echo esc_html__( 'Here are some things to fix: ', 'email-subscribers' ); ?>
				<ul></ul>
			</div>
			<?php
		}
		
		/**
		 * Method to show left pan fields in broadcast summary section.
		 *
		 * @param array $broadcast_data Broadcast data
		 *
		 * @since 4.4.7
		 *
		 */
		public function add_check_spam_score_button( $broadcast_data = array() ) {
			?>
			<div class="block mx-4 my-3 pb-5 border-b border-gray-200">
				<span class="pt-3 text-sm font-medium leading-5 text-gray-700"><?php echo esc_html__( 'Get Spam Score', 'email-subscribers' ); ?> </span>
				<button type="button" id="spam_score"
						class="float-right es_spam rounded-md border text-indigo-600 border-indigo-500 text-sm leading-5 font-medium transition ease-in-out duration-150 select-none inline-flex justify-center hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg px-3 py-1">
						<?php 
						echo esc_html__( 'Check',
						'email-subscribers' ); 
						?>
				</button>
				<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/spinner-2x.gif" class="es-loader-img inline-flex align-middle pl-2 h-5 w-7" style="display:none;"/>

				<div class="spinner"></div>
				<span class="es-spam-score font-medium text-xl align-middle text-center "></span>
				<div class="hidden" id="spam_score_modal">
					<div class="fixed z-50 top-0 left-0 w-full h-full flex items-center justify-center" style="background-color: rgba(0,0,0,.5);">
						<div class="text-left bg-white h-auto p-2 md:max-w-xl md:p-2 lg:p-6 shadow-xl rounded mx-2 md:mx-0">
							<h3 class="text-2xl uppercase text-center text-gray-800"><?php echo esc_html__( 'Spam score', 'email-subscribers' ); ?></h3>
							<h3 class="es-spam-score text-4xl font-bold pb-1 text-center mt-8"></h3>
							<div class="es-logs es-spam-success" style="display:none;"><?php echo esc_html__( 'Awesome score. Your email is almost perfect.', 'email-subscribers' ); ?></div>
							<div class="es-logs es-spam-error text-base font-normal text-gray-500 pb-2 text-center pt-4 list-none" style="display:none;"><?php echo esc_html__( 'Ouch! your email needs improvement. ', 'email-subscribers' ); ?></div>
							<div class="es-spam-error-log" style="display:none;">
								<div class="text-base font-normal text-gray-500 pb-2 list-none text-center">
									<?php echo esc_html__( 'Here are some things to fix: ', 'email-subscribers' ); ?>
								</div>
								<ul></ul>
							</div>

							<li class="text-base font-normal text-gray-500 pb-2 list-none text-center">
								<div class="flex justify-center mt-8">
									<button id="close_score" class="border text-sm tracking-wide font-medium text-gray-700 px-4 py-2 rounded no-outline focus:outline-none focus:shadow-outline-red select-none hover:border-red-400 active:shadow-lg "><?php echo esc_html__( 'Close', 'email-subscribers' ); ?></button>
								</div>
							</li>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Method to show available tracking option in pro version
		 *
		 * @param array $broadcast_data
		 *
		 * @return void
		 */
		public function add_broadcast_utm_tracking_option( $broadcast_data = array() ) {
			$enable_utm_tracking  = ! empty( $broadcast_data['meta']['enable_utm_tracking'] ) ? $broadcast_data['meta']['enable_utm_tracking'] : get_option( 'ig_es_track_utm', 'no' );

			$campaign_name = ! empty( $broadcast_data['meta']['es_utm_campaign'] ) ? $broadcast_data['meta']['es_utm_campaign'] : '';

			?>

			<div class="flex mt-3 pb-1 w-full">
				<div class="w-11/12 text-sm font-normal text-gray-600"><?php echo esc_html__( 'UTM tracking', 'email-subscribers' ); ?>
				</div>

				<div>
					<label for="enable_utm_tracking" class=" inline-flex items-center cursor-pointer">
							<span class="relative">
								<input id="enable_utm_tracking" name="broadcast_data[meta][enable_utm_tracking]" type="checkbox" class="absolute es-check-toggle opacity-0 w-0 h-0" value="yes" <?php checked( $enable_utm_tracking, 'yes' ); ?>
								/>
								<span class="es-mail-toggle-line block w-8 h-5 bg-gray-300 rounded-full shadow-inner"></span>
								<span class="es-mail-toggle-dot absolute transition-all duration-300 ease-in-out block w-3 h-3 mt-1 ml-1 bg-white rounded-full shadow inset-y-0 left-0 focus-within:shadow-outline"></span>
							</span>
					</label>
				</div>
			</div>
			<div class="py-1 ig_es_broadcast_campaign_name_wrapper <?php echo 'no' === $enable_utm_tracking ? esc_attr( 'hidden' ) : ''; ?>">
				<input name="broadcast_data[meta][es_utm_campaign]" placeholder="<?php echo esc_html__( 'Campaign Name', 'email-subscribers' ); ?>" id="es_utm_campaign" class="form-input border-gray-400 text-sm relative rounded-md shadow-sm block w-2/4 sm:leading-5" value="<?php echo esc_attr( $campaign_name ); ?>">
			</div>
			<?php
		}

		/**
		 * Method to register UTM tracking side metabox on ES template page
		 * 
		 * @since 4.6.2
		 */
		public function register_utm_tracking_metabox() {
			$meta_box_title_for_utm    = __( 'Google Analytics link tracking', 'email-subscribers' );
			add_meta_box( 'es_utm', $meta_box_title_for_utm, array( &$this, 'add_utm_tracking_metabox' ), 'es_template', 'side', 'default' );
		}

		/**
		 * Method to add UTM tracking metabox on ES template page
		 * 
		 * @since 4.6.2
		 */
		public function add_utm_tracking_metabox() {
			global $post;
			$es_utm_campaign = get_post_meta( $post->ID, 'es_utm_campaign', true );
			$es_utm_campaign = ! empty( $es_utm_campaign ) ? $es_utm_campaign : '';
			?>
			<label class="es_utm_label"><span class="font-medium text-sm text-gray-700"><?php echo esc_html__( 'Campaign Name', 'email-subscribers' ); ?></span><span class="help_tip es_help_tip"
				data-tip="<?php echo sprintf( esc_html__( 'This will be appended to every URL in this template with parameters: utm_source=es&utm_medium=email&utm_campaign=campaign_name', 'email-subscribers' ) ); ?>"></span> </label><br>
			<input style="margin: 0.20rem 0;" type="text" name="es_utm_campaign" value="<?php echo esc_attr( $es_utm_campaign ); ?>" placeholder="<?php echo esc_html__( 'Campaign Name', 'email-subscribers' ); ?>" id="es_utm_campaign"/><br/>
			<?php
		}

		/**
		 * Method to save utm campaign name
		 *
		 * @param $post_id
		 * @param $post
		 *
		 * @since 4.6.2
		 */
		public function save_utm_campaign( $post_id, $post ) {

			if ( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
				
				$es_utm_campaign = ig_es_get_data( $_POST, 'es_utm_campaign', false );
				if ( false !== $es_utm_campaign ) {
					update_post_meta( $post_id, 'es_utm_campaign', $es_utm_campaign );
				}
			}
		}

		/**
		 * Method to add UTM tracking option in settings.
		 * 
		 * @param array $fields Setting fields
		 * 
		 * @return array $fields Setting fields
		 * 
		 * @since 4.6.2
		 */
		public function add_utm_tracking_option_in_settings( $fields ) {
			
			// UTM tracking option
			$track_utm        = array(
				'ig_es_track_utm' => array(
					'id'      => 'ig_es_track_utm',
					'name'    => __( 'UTM tracking', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'no'
				)
			);

			$general_fields = $fields['general'];

			$general_fields = ig_es_array_insert_after( $general_fields, 'ig_es_track_link_click', $track_utm );

			$fields['general'] = $general_fields;

			return $fields;
		}
	}
}
