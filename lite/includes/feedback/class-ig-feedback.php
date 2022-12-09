<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'IG_Feedback_V_1_2_8' ) ) {
	/**
	 * IG Feedback
	 *
	 * The IG Feedback class adds functionality to get quick interactive feedback from users.
	 * There are different types of feedabck widget like Stars, Emoji, Thubms Up/ Down, Number etc.
	 *
	 * @class       IG_Feedback_V_1_2_8
	 * @since       1.0.0
	 * @copyright   Copyright (c) 2019, Icegram
	 * @license     https://opensource.org/licenses/gpl-license GNU Public License
	 * @package     feedback
	 */
	class IG_Feedback_V_1_2_8 {

		/**
		 * Version of Feedback Library
		 *
		 * @since 1.0.13
		 * @var string
		 */
		public $version = '1.2.8';
		/**
		 * The API URL where we will send feedback data.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $api_url = 'https://api.icegram.com/store/feedback/'; // Production

		/**
		 * Name for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $name;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $ajax_action;

		/**
		 * Plugin Abbreviation
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin_abbr;

		/**
		 * Enable/Disable Dev Mode
		 *
		 * @var bool
		 */
		public $is_dev_mode = true;

		/**
		 * Set feedback event
		 *
		 * @var string
		 */
		public $event_prefix;

		/**
		 * Footer HTML
		 */
		public $footer = '<span class="ig-powered-by">Made With&nbsp;💜&nbsp;by&nbsp;<a href="https://www.icegram.com/" target="_blank">Icegram</a></span>';

		/**
		 * Primary class constructor.
		 *
		 * @param string $name Plugin name.
		 * @param string $plugin Plugin slug.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $name = '', $plugin = '', $plugin_abbr = 'ig_fb', $event_prefix = 'igfb.', $is_dev_mode = false ) {

			$this->name         = $name;
			$this->plugin       = $plugin;
			$this->plugin_abbr  = $plugin_abbr;
			$this->event_prefix = $event_prefix;
			$this->ajax_action  = $this->plugin_abbr . '_submit-feedback';
			$this->is_dev_mode  = $is_dev_mode;

			// Don't run deactivation survey on dev sites.
			if ( ! $this->can_show_feedback_widget() ) {
				return;
			}

			add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'submit_feedback' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			/**
			 * Show 5 star review notice to user
			 *
			 * @since 1.0.12
			 */
			add_action( 'admin_notices', array( &$this, 'show_review_notice' ) );
		}

		/**
		 * Ask for user review
		 *
		 * @since 1.0.12
		 */
		public function show_review_notice() {

			if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {

				$enable_review_notice = apply_filters( $this->plugin_abbr . '_enable_review_notice', true );

				$can_ask_user_for_review = true;

				if ( $enable_review_notice ) {

					$current_user_id = get_current_user_id();

					$review_done_option      = $this->plugin_abbr . '_feedback_review_done';
					$no_bug_option           = $this->plugin_abbr . '_feedback_do_not_ask_again';
					$already_did_option      = $this->plugin_abbr . '_feedback_already_did';
					$maybe_later_option      = $this->plugin_abbr . '_feedback_maybe_later';
					$review_done_time_option = $review_done_option . '_time';
					$no_bug_time_option      = $no_bug_option . '_time';
					$already_did_time_option = $already_did_option . '_time';
					$maybe_later_time_option = $maybe_later_option . '_time';

					$no_bug_days_before = 1;
					$no_bug_value       = get_user_meta( $current_user_id, $no_bug_option, true );
					$no_bug_time_value  = get_user_meta( $current_user_id, $no_bug_time_option, true );

					$review_done_value      = get_user_meta( $current_user_id, $review_done_option, true );
					$review_done_time_value = get_user_meta( $current_user_id, $review_done_time_option, true );

					if ( ! empty( $no_bug_time_value ) && 0 !== $no_bug_time_value ) {
						$no_bug_time_diff   = time() - $no_bug_time_value;
						$no_bug_days_before = floor( $no_bug_time_diff / 86400 ); // 86400 seconds == 1 day
					}

					$already_did_value      = get_user_meta( $current_user_id, $already_did_option, true );
					$already_did_time_value = get_user_meta( $current_user_id, $already_did_time_option, true );

					$maybe_later_days_before = 1;
					$maybe_later_value       = get_user_meta( $current_user_id, $maybe_later_option, true );
					$maybe_later_time_value  = get_user_meta( $current_user_id, $maybe_later_time_option, true );

					if ( $maybe_later_value && ! empty( $maybe_later_time_value ) && 0 !== $maybe_later_time_value ) {
						$maybe_later_time_diff   = time() - $maybe_later_time_value;
						$maybe_later_days_before = floor( $maybe_later_time_diff / 86400 ); // 86400 seconds == 1 day
					}

					// Is user fall in love with our plugin in 15 days after when they said may be later?
					// But, make sure we are asking user only after 15 days.
					// We are good people. Respect the user decision.
					if ( $review_done_value || $no_bug_value || $already_did_value || ( $maybe_later_value && $maybe_later_days_before < 15 ) || $already_did_value ) {
						$can_ask_user_for_review = false;
					}

					$review_data = array(
						'review_done_value'      => $review_done_value,
						'review_done_time_value' => $review_done_time_value,
						'no_bug_value'           => $no_bug_value,
						'no_bug_time_value'      => $no_bug_time_value,
						'maybe_later_value'      => $maybe_later_value,
						'maybe_later_time_value' => $maybe_later_time_value,
						'already_did_value'      => $already_did_value,
						'already_did_time_value' => $already_did_time_value,
					);

					$can_ask_user_for_review = apply_filters( $this->plugin_abbr . '_can_ask_user_for_review', $can_ask_user_for_review, $review_data );

					if ( $can_ask_user_for_review ) {

						$current_page_url = '';
						if ( ! empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
							$current_page_url = esc_url_raw( '//' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
						}

						$got_feedback = false;
						/************** Update Review Status */
						$nonce          = ! empty( $_GET['ig_feedback_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['ig_feedback_nonce'] ) ) : '';
						$nonce_verified = wp_verify_nonce( $nonce, 'review' );

						$action = '';
						if ( $nonce_verified ) {
							$action = ! empty( $_GET['ig_feedback_action'] ) ? sanitize_text_field( wp_unslash( $_GET['ig_feedback_action'] ) ) : '';

							if ( ! empty( $action ) && $this->is_valid_action( $action ) ) {
								update_user_meta( $current_user_id, $action, 1 );
								update_user_meta( $current_user_id, $action . '_time', time() );

								// Got the review request?
								// Redirect them to review page
								if ( $action === $review_done_option ) {

									$url = ! empty( $_GET['review_url'] ) ? esc_url_raw( wp_unslash( $_GET['review_url'] ) ) : '';

									if ( ! empty( $url ) ) {
										?>

										<meta http-equiv="refresh" content="0; url=<?php echo esc_url( $url ); ?>"/>

										<?php
									}
								}
							}

							$got_feedback = true;
						}
						/************** Update Review Status (End) */

						if ( ! $got_feedback ) {

							$review_url = "https://wordpress.org/support/plugin/{$this->plugin}/reviews/";
							$icon_url   = plugin_dir_url( __FILE__ ) . 'assets/images/icon-64.png';
							/* translators: %s: Plugin name */
							$message = __( sprintf( "<span><p>We hope you're enjoying <b>%s</b> plugin! Could you please do us a BIG favor and give us a 5-star rating on WordPress to help us spread the word and boost our motivation?</p>", $this->name ), $this->plugin );

							$message_data = array(
								'review_url' => $review_url,
								'icon_url'   => $icon_url,
								'message'    => $message,
							);

							$message_data = apply_filters( $this->plugin_abbr . '_review_message_data', $message_data );

							$message    = ! empty( $message_data['message'] ) ? $message_data['message'] : '';
							$review_url = ! empty( $message_data['review_url'] ) ? $message_data['review_url'] : '';
							$icon_url   = ! empty( $message_data['icon_url'] ) ? $message_data['icon_url'] : '';

							$nonce = wp_create_nonce( 'review' );

							$review_url      = add_query_arg( 'review_url', $review_url, add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $review_done_option, $current_page_url ) ) );
							$maybe_later_url = add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $maybe_later_option, $current_page_url ) );
							$already_did_url = add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $already_did_option, $current_page_url ) );
							$no_bug_url      = add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $no_bug_option, $current_page_url ) );

							?>

							<style type="text/css">

								.ig-feedback-notice-links li {
									display: inline-block;
									margin-right: 15px;
								}

								.ig-feedback-notice-links li a {
									display: inline-block;
									color: #5850EC;
									text-decoration: none;
									padding-left: 26px;
									position: relative;
								}

								.ig-feedback-notice {
									display: flex;
									align-items: center;
								}

								.ig-feedback-plugin-icon {
									float: left;
									margin-right: 0.5em;
								}

							</style>

							<?php

							echo '<div class="notice notice-success ig-feedback-notice">';
							echo '<span class="ig-feedback-plugin-icon"> <img src="' . esc_url( $icon_url ) . '" alt="Logo"/></span>';
							echo wp_kses_post( $message );
							echo "<ul class='ig-feedback-notice-links'>";
							echo sprintf(
								'<li><a href="%s" class="px-4 py-2 ml-6 mr-2 align-middle cursor-pointer button button-primary bg-indigo-600" target="_blank" data-rated="' . esc_attr__(
									'Thank You :) ',
									$this->plugin
								) . '"><span class="dashicons dashicons-external"></span>&nbsp;&nbsp;Ok, you deserve it</a></li> <li><a href="%s"><span class="dashicons dashicons-calendar-alt"></span>&nbsp;&nbsp;Maybe later</a></li><li><a href="%s"><span class="dashicons dashicons-smiley"></span>&nbsp;&nbsp;I already did!</a></li><li><a href="%s"><span class="dashicons dashicons-no"></span>&nbsp;&nbsp;Don\'t ask me again</a></li>',
								esc_url( $review_url ),
								esc_url( $maybe_later_url ),
								esc_url( $already_did_url ),
								esc_url( $no_bug_url )
							);
							echo '</ul></span>';
							echo '</div>';
						}
					}
				}
			}
		}

		/**
		 * Is valid action?
		 *
		 * @param string $action
		 *
		 * @return bool
		 *
		 * @since 1.0.14
		 */
		public function is_valid_action( $action = '' ) {
			if ( empty( $action ) ) {
				return false;
			}

			$available_actions = array(
				'_feedback_review_done',
				'_feedback_already_did',
				'_feedback_maybe_later',
				'_feedback_do_not_ask_again',
			);

			foreach ( $available_actions as $available_action ) {
				if ( strpos( $action, $available_action ) !== false ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Render Deactivation Feedback
		 *
		 * @since 1.0.0
		 */
		public function render_deactivate_feedback() {
			add_action( 'admin_print_footer_scripts', array( $this, 'js' ), 20 );
			add_action( 'admin_print_scripts', array( $this, 'css' ) );
			add_action( 'admin_footer', array( $this, 'modal' ) );
		}

		/**
		 * Load Javascripts
		 *
		 * @since 1.0.1
		 *
		 * @modify 1.1.0
		 */
		public function enqueue_scripts() {

			$can_load = apply_filters( $this->plugin_abbr . '_can_load_sweetalert_js', false );

			if ( $can_load ) {
				wp_enqueue_script( 'sweetalert', plugin_dir_url( __FILE__ ) . 'assets/js/sweetalert2.min.js', array( 'jquery' ) );
			}
		}

		/**
		 * Load Styles
		 *
		 * @since 1.0.1
		 */
		public function enqueue_styles() {

			$can_load = apply_filters( $this->plugin_abbr . '_can_load_sweetalert_css', false );

			if ( $can_load ) {
				wp_register_style( "sweetalert_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/sweetalert2.min.css', array(), $this->version );
				wp_enqueue_style( "sweetalert_{$this->version}" );
	
				wp_register_style( "animate_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/animate.min.css', array(), $this->version );
				wp_enqueue_style( "animate_{$this->version}" );
	
				wp_register_style( "ig-feedback-star-rating_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/star-rating.min.css', array(), $this->version );
				wp_enqueue_style( "ig-feedback-star-rating_{$this->version}" );
	
				wp_register_style( "ig-feedback-emoji_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/emoji.min.css', array(), $this->version );
				wp_enqueue_style( "ig-feedback-emoji_{$this->version}" );
	
				wp_register_style( "ig-feedback_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/feedback.min.css', array(), $this->version );
				wp_enqueue_style( "ig-feedback_{$this->version}" );
			}
		}

		/**
		 * Prepare Widget Params
		 *
		 * @param array $params
		 *
		 * @return array
		 *
		 * @since 1.0.3
		 */
		public function prepare_widget_params( $params = array() ) {

			$default_params = array(
				'event'             => 'feedback',
				'title'             => 'How do you rate ' . $this->plugin,
				'position'          => 'top-end',
				'width'             => 300,
				'set_transient'     => true,
				'allowOutsideClick' => false,
				'allowEscapeKey'    => true,
				'showCloseButton'   => true,
				'confirmButtonText' => 'Ok',
				'backdrop'          => true,
				'delay'             => 3, // In Seconds
				'consent_text'      => 'You are agree to our terms and condition',
				'email'             => $this->get_contact_email(),
				'name'              => '',
				'consent'           => false,
			);

			$params = wp_parse_args( $params, $default_params );

			return $params;
		}

		/**
		 * Render Widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.0
		 */
		public function render_widget( $params = array() ) {

			$params = $this->prepare_widget_params( $params );

			$title = $params['title'];
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . $params['event'];
			$html  = ! empty( $params['html'] ) ? $params['html'] : '';

			?>

			<script>

				function doSend(rating, details) {

					var data = {
						action: '<?php echo esc_js( $this->ajax_action ); ?>',
						feedback: {
							type: '<?php echo esc_js( $params['type'] ); ?>',
							slug: '<?php echo esc_js( $slug ); ?>',
							title: '<?php echo esc_js( $title ); ?>',
							value: rating,
							details: details
						},

						event: '<?php echo esc_js( $event ); ?>',

						// Add additional information
						misc: {
							plugin: '<?php echo esc_js( $this->plugin ); ?>',
							plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
							is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
							set_transient: '<?php echo esc_js( $params['set_transient'] ); ?>'
							//system_info: enable_system_info
						},
						security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
					};

					return jQuery.post(ajaxurl, data);
				}

				function showWidget(delay) {

					setTimeout(function () {

						Swal.mixin({
							footer: '<?php echo wp_kses_post( $this->footer ); ?>',
							position: '<?php echo esc_js( $params['position'] ); ?>',
							width: <?php echo esc_js( $params['width'] ); ?>,
							animation: false,
							focusConfirm: false,
							allowEscapeKey: '<?php echo esc_js( $params['allowEscapeKey'] ); ?>',
							showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
							allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
							showLoaderOnConfirm: true,
							confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
							backdrop: '<?php echo (int) esc_js( $params['backdrop'] ); ?>'
						}).queue([
							{
								title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
								html: '<?php echo wp_kses_post( $html ); ?>',
								customClass: {
									popup: 'animated fadeInUpBig'
								},
								onOpen: () => {
									var clicked = false;
									var selectedReaction = '';
									jQuery('.ig-emoji').hover(function () {
										reaction = jQuery(this).attr('data-reaction');
										jQuery('#emoji-info').text(reaction);
									}, function () {
										if (!clicked) {
											jQuery('#emoji-info').text('');
										} else {
											jQuery('#emoji-info').text(selectedReaction);
										}
									});

									jQuery('.ig-emoji').on('click', function () {
										clicked = true;
										jQuery('.ig-emoji').removeClass('active');
										jQuery(this).addClass('active');
										selectedReaction = jQuery(this).attr('data-reaction');
										jQuery('#emoji-info').text(reaction);
									});
								},
								preConfirm: () => {

									var rating = jQuery("input[name='rating']:checked").val();
									var details = '';

									if (rating === undefined) {
										Swal.showValidationMessage('Please give your input');
										return;
									}

									return doSend(rating, details);
								}
							},

						]).then(response => {

							if (response.hasOwnProperty('value')) {

								Swal.fire({
									type: 'success',
									width: <?php echo esc_js( $params['width'] ); ?>,
									title: '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
									showConfirmButton: false,
									position: '<?php echo esc_js( $params['position'] ); ?>',
									timer: 1500,
									animation: false
								});

							}
						});

					}, delay * 1000);
				}

				var delay = <?php echo esc_js( $params['delay'] ); ?>;
				showWidget(delay);


			</script>
			<?php
		}

		/**
		 * Render star feedback widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.1
		 */
		public function render_stars( $params = array() ) {

			ob_start();

			?>

			<div class="rating">
				<!--elements are in reversed order, to allow "previous sibling selectors" in CSS-->
				<input class="ratings" type="radio" name="rating" value="5" id="5"><label for="5">☆</label>
				<input class="ratings" type="radio" name="rating" value="4" id="4"><label for="4">☆</label>
				<input class="ratings" type="radio" name="rating" value="3" id="3"><label for="3">☆</label>
				<input class="ratings" type="radio" name="rating" value="2" id="2"><label for="2">☆</label>
				<input class="ratings" type="radio" name="rating" value="1" id="1"><label for="1">☆</label>
			</div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$this->render_widget( $params );
		}

		/**
		 * Render Emoji Widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.1
		 */
		public function render_emoji( $params = array() ) {

			ob_start();

			?>

			<div class="emoji">
				<!--elements are in reversed order, to allow "previous sibling selectors" in CSS-->
				<input class="emojis" type="radio" name="rating" value="love" id="5"/><label for="5" class="ig-emoji" data-reaction="Love">😍</label>
				<input class="emojis" type="radio" name="rating" value="smile" id="4"/><label for="4" class="ig-emoji" data-reaction="Smile">😊</label>
				<input class="emojis" type="radio" name="rating" value="neutral" id="3"/><label for="3" class="ig-emoji" data-reaction="Neutral">😐</label>
				<input class="emojis" type="radio" name="rating" value="sad" id="1"/><label for="2" class="ig-emoji" data-reaction="Sad">😠</label>
				<input class="emojis" type="radio" name="rating" value="angry" id="1"/><label for="1" class="ig-emoji" data-reaction="Angry">😡</label>
			</div>
			<div id="emoji-info"></div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$this->render_widget( $params );

		}

		/**
		 * Render General Feedback Sidebar Button Widget
		 *
		 * @since 1.0.3
		 */
		public function render_general_feedback( $params = array() ) {

			$params = $this->prepare_widget_params( $params );

			ob_start();

			?>

			<div class="ig-general-feedback" id="ig-general-feedback-<?php echo esc_attr( $this->plugin ); ?>">
				<form class="ig-general-feedback" id="ig-general-feedback">
					<p class="ig-feedback-data-name">
						<label class="ig-label">Name</label><br/>
						<input type="text" name="feedback_data[name]" id="ig-feedback-data-name" value="<?php echo esc_attr( $params['name'] ); ?>"/>
					</p>
					<p class="ig-feedback-data-email">
						<label class="ig-label"">Email</label><br/>
						<input type="email" name="feedback_data[email]" id="ig-feedback-data-email" value="<?php echo esc_attr( $params['email'] ); ?>"/>
					</p>
					<p class="ig-feedback-data-message">
						<label class="ig-label"">Feedback</label><br/>
						<textarea name="feedback_data[details]" id="ig-feedback-data-message"></textarea>
					</p>
					<?php if ( isset( $params['consent'] ) && true === $params['consent'] ) { ?>
						<p>
							<input type="checkbox" name="feedback_data[collect_system_info]" checked="checked" id="ig-feedback-data-consent"/><?php echo esc_html( $params['consent_text'] ); ?>
						</p>
					<?php } ?>
				</form>
			</div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$title = $params['title'];
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . $params['event'];
			$html  = ! empty( $params['html'] ) ? $params['html'] : '';

			$escape_allowed_tags = $this->get_escape_allowed_tags();

			ob_start();
			?>

			<script type="text/javascript">

				jQuery(document).ready(function ($) {

					function doSend(details, meta, system_info) {

						var data = {
							action: '<?php echo esc_js( $this->ajax_action ); ?>',
							feedback: {
								type: '<?php echo esc_js( $params['type'] ); ?>',
								slug: '<?php echo esc_js( $slug ); ?>',
								title: '<?php echo esc_js( $title ); ?>',
								details: details
							},

							event: '<?php echo esc_js( $event ); ?>',

							// Add additional information
							misc: {
								plugin: '<?php echo esc_js( $this->plugin ); ?>',
								plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
								is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
								set_transient: '<?php echo esc_js( $params['set_transient'] ); ?>',
								meta_info: meta,
								system_info: system_info
							},
							security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
						};

						return jQuery.post(ajaxurl, data);
					}

					function validateEmail(email) {
						var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
						if (!emailReg.test(email)) {
							return false;
						} else {
							return true;
						}
					}

					var feedbackButtonClass = 'ig-feedback-button-<?php echo esc_js( $this->plugin ); ?>';

					$('#wpwrap').append('<div class="ig-es-feedback-button ' + feedbackButtonClass + '">Feedback</div>');

					$('.' + feedbackButtonClass).on('click', function () {
						Swal.mixin({
							footer: '<?php echo wp_kses_post( $this->footer ); ?>',
							position: '<?php echo esc_js( $params['position'] ); ?>',
							width: <?php echo esc_js( $params['width'] ); ?>,
							animation: false,
							focusConfirm: false,
							allowEscapeKey: true,
							showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
							allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
							showLoaderOnConfirm: true,
							confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
							backdrop: '<?php echo (int) $params['backdrop']; ?>'
						}).queue([
							{
								title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
								html: '<?php echo wp_kses( $html, $escape_allowed_tags ); ?>',
								customClass: {
									popup: 'animated fadeInUpBig'
								},
								onOpen: () => {


								},
								preConfirm: () => {
									var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
									var $form = $overlay.find('form');

									var email = $form.find('#ig-feedback-data-email').val();
									var name = $form.find('#ig-feedback-data-name').val();
									var message = $form.find('#ig-feedback-data-message').val();
									var consent = $form.find('#ig-feedback-data-consent').attr('checked');

									if (email !== '' && !validateEmail(email)) {
										Swal.showValidationMessage('Please enter valid email');
										return;
									}

									if (message === '') {
										Swal.showValidationMessage('Please enter your message');
										return;
									}

									var system_info = true;
									if (consent === 'checked') {
										system_info = true;
									}

									var meta = {
										name: name,
										email: email
									};

									return doSend(message, meta, system_info);
								}
							},

						]).then(response => {

							if (response.hasOwnProperty('value')) {

								Swal.fire({
									type: 'success',
									width: <?php echo esc_js( $params['width'] ); ?>,
									title: '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
									showConfirmButton: false,
									position: '<?php echo esc_js( $params['position'] ); ?>',
									timer: 1500,
									animation: false
								});

							}
						});


					});
				});

			</script>


			<?php

		}

		/**
		 * Render Facebook Widget
		 *
		 * @since 1.0.9
		 */
		public function render_fb_widget( $params ) {

			$params = $this->prepare_widget_params( $params );

			$title               = $params['title'];
			$widget_tyoe         = ! empty( $params['widget_tyoe'] ) ? $params['widget_tyoe'] : 'question';
			$slug                = sanitize_title( $title );
			$event               = $this->event_prefix . $params['event'];
			$html                = ! empty( $params['html'] ) ? $params['html'] : '';
			$confirm_button_link = ! empty( $params['confirmButtonLink'] ) ? $params['confirmButtonLink'] : '';
			$cancel_button_link  = ! empty( $params['cancelButtonLink'] ) ? $params['cancelButtonLink'] : '';
			$show_cancel_button  = ! empty( $params['showCancelButton'] ) ? 'true' : 'false';
			$cancel_button_text  = ! empty( $params['cancelButtonText'] ) ? $params['cancelButtonText'] : 'Cancel';

			?>

			<script>

				Swal.mixin({
					type: '<?php echo esc_js( $widget_tyoe ); ?>',
					position: '<?php echo esc_js( $params['position'] ); ?>',
					width: <?php echo esc_js( $params['width'] ); ?>,
					animation: false,
					focusConfirm: true,
					allowEscapeKey: true,
					showCancelButton: <?php echo esc_js( $show_cancel_button ); ?>,
					confirmButtonColor: '#0e9f6e',
					cancelButtonColor: '#5850ec',
					cancelButtonText: '<?php echo  wp_kses_post( $cancel_button_text ); ?>',
					showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
					allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
					showLoaderOnConfirm: true,
					confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
					backdrop: '<?php echo (int) $params['backdrop']; ?>'
				}).queue([
					{
						title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
						html: '<?php echo wp_kses_post( $html ); ?>',
						customClass: {
							popup: 'animated fadeInUpBig'
						},

						preConfirm: () => {
							window.open(
								'<?php echo esc_url( $confirm_button_link ); ?>',
								'_blank' // <- This is what makes it open in a new window.
							);
						}
					}
				]).then(response => {

					if (response.hasOwnProperty('value')) {

						Swal.fire({
							type: 'success',
							width: <?php echo esc_js( $params['width'] ); ?>,
							title: "Thank You!",
							showConfirmButton: false,
							position: '<?php echo esc_js( $params['position'] ); ?>',
							timer: 1500,
							animation: false
						});
					} else if(response.dismiss == 'cancel') {
						window.open(
							'<?php echo esc_url( $cancel_button_link ); ?>',
							'_blank' // <- This is what makes it open in a new window.
						);
					}
				});

			</script>

			<?php
		}

		/**
		 * Render Poll widget
		 *
		 * @param array $params
		 *
		 * @since 1.1.0
		 */
		public function render_poll_widget( $params = array() ) {
			$params = $this->prepare_widget_params( $params );

			$fields = ! empty( $params['fields'] ) ? $params['fields'] : array();
			if ( empty( $fields ) ) {
				return;
			}
			$default_values = ! empty( $params['default_values'] ) ? $params['default_values'] : array();

			$allow_multiple = ! empty( $params['allow_multiple'] ) ? $params['allow_multiple'] : false;

			$title = $params['title'];
			if ( ! empty( $params['slug'] ) ) {
				$slug = $params['slug'];
			} else {
				$slug = sanitize_title( $title );
			}
			$event       = $this->event_prefix . $params['event'];
			$desc        = ! empty( $params['desc'] ) ? $params['desc'] : '';
			$display_as  = ! empty( $params['display_as'] ) ? $params['display_as'] : 'inline';
			$system_info = isset( $params['system_info'] ) ? $params['system_info'] : true;

			ob_start();

			?>

			<div class="ig-general-feedback" id="ig-general-feedback-<?php echo esc_attr( $this->plugin ); ?>">
				<form class="ig-general-feedback" id="ig-general-feedback">
					<?php
					if ( ! empty( $title ) ) {
						?>
						<h2><?php echo wp_kses_post( $title ); ?></h2>
						<?php
					}
					?>
					<?php
					if ( ! empty( $desc ) ) {
						?>
						<p><?php echo wp_kses_post( $desc ); ?></p>
						<?php
					}
					?>
					<p class="ig-general-feedback mb-3">
						<?php 
						foreach ( $fields as $index => $field ) {
							$field_type        = $field['type'];
							$field_name        =  $field['name'];
							$field_value       = ! empty( $field['value'] ) ? $field['value'] : '';
							$field_placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
							$field_required    = isset ( $field['required'] ) ? $field['required'] : false;
							switch ( $field_type ) {
								case 'textarea':
									?>
										<p class="ig-feedback-data-poll-message mb-3" id="ig-feedback-data-poll-message">
											<textarea name="feedback_data[<?php echo esc_attr( $field_name ); ?>]" id="ig-feedback-data-poll-additional-message" placeholder="<?php echo esc_attr( $field_placeholder ); ?>"></textarea>
										</p>
										<br/>
									<?php
									break;
								case 'radio':
								case 'checkbox':
									?>
									<label>
											<input 
												type="<?php echo esc_attr( $field_type ); ?>" 
												name="feedback_data[<?php echo esc_attr( $field_name ); ?>]" 
												value="<?php echo esc_attr( $field_value ); ?>"
											<?php echo ( ! empty( $default_values[ $field_name ] ) && $field_value === $default_values[ $field_name ] ) ? 'checked' : ''; ?>
												class="<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field"
												<?php echo $field_required ? 'required' : ''; ?>>
										<?php echo wp_kses_post( $field['label'] ); ?>
									</label>
									<br/>
									<?php
									break;
								default:
									?>
									<label>
											<input 
												type="<?php echo esc_attr( $field_type ); ?>" 
												name="feedback_data[<?php echo esc_attr( $field_name ); ?>]" 
												value="<?php echo esc_attr( $field['value'] ); ?>"
												class="<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field">
										<?php echo wp_kses_post( $field['label'] ); ?>
									</label>
									<br/>
									<?php
							}
							?>
						<?php
							if ( 'other' === $field['value'] ) {
								?>
								<div class="ig_feedback_text_wrapper">
									<textarea 
										id="feedback_text"
										name="feedback[feedback_text]"
										class="form-textarea text-sm w-2/3 mt-3 mb-1 border-gray-400 w-3/5 hidden"
										placeholder="<?php echo esc_attr__( 'Tell us more about your desired feature', $this->plugin ); ?>"></textarea>
								</div>
								<?php
							}
						}
						?>
					</p>
					<?php
					if ( 'inline' === $display_as ) {
						?>
						<button type="submit" class="button">
							<?php
								echo esc_html__( 'Submit your feedback', $this->plugin );
							?>
						</button>
						<br/>
						<br/>
						<?php
					}
					?>
				</form>
			</div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$event = $this->event_prefix . $params['event'];
			
			if ( 'inline' === $display_as ) {
				$this->render_inline_feedback_widget( $html, $params );
			} elseif ( 'popup' === $display_as ) {
				$this->render_popup_feedback_widget( $html, $params );
			}
			?>
			<script type="text/javascript">

				function doSend(data) {
					var system_info = <?php echo $system_info ? 'true' : 'false'; ?>;
					var requestData = {
						action: '<?php echo esc_js( $this->ajax_action ); ?>',
						feedback: {
							type: '<?php echo esc_js( $params['type'] ); ?>',
							slug: '<?php echo esc_js( $slug ); ?>',
							data: data
						},

						event: '<?php echo esc_js( $event ); ?>',

						// Add additional information
						misc: {
							plugin: '<?php echo esc_js( $this->plugin ); ?>',
							plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
							is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
							set_transient: '<?php echo esc_js( $params['set_transient'] ); ?>',
							system_info: system_info
						},
						security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
					};

					return jQuery.post(ajaxurl, requestData, null, 'json');
				}
			</script>
			<?php
		}

		/**
		 * Render inline feedback widget
		 *
		 * @since 1.2.7
		 * 
		 * @param string $html
		 * @param array $params
		 * @return void
		 */
		public function render_inline_feedback_widget( $html, $params ) {
			$escape_allowed_tags = $this->get_escape_allowed_tags();
			?>
			<style type="text/css">
				.ig-feedback-inline h2{
					font-size:2em;
					padding: 0.5em 0;
				}
				.ig-feedback-inline{
					border:none;
					width:90%;
					margin: 1em auto !important;
					/* background:#fbe2597d; */
				}
				.ig-feedback-inline button{
					background: green !important;
					color: #FFF !important;
				}
				.ig-feedback-inline radio{
					line-height:1em;
				}
				.ig-feedback-logo{
					float:right;
				}
				.ig-feedback-inline label{
					font-size: 1.1em;
					padding-bottom: 0.5em;
				}
				.ig-feedback-inline .button{
				padding: 0.7em;
					font-size: 1.2em;
					border-radius: 0;
				}
				</style>
			<div class="notice ig-feedback-notice ig-feedback-inline">
				<?php
				if ( $params['logo_img_url'] ) {
					?>
				 <div class="ig-feedback-logo">
					 <img src="<?php echo esc_url( $params['logo_img_url'] ); ?>">
				 </div>
					<?php
				}
				?>
				<?php echo wp_kses( $html, $escape_allowed_tags ); ?>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$('.<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field').on('change', function(){
						let value = $(this).val();
						if ( 'other' === value ) {
							$('#feedback_text').removeClass('hidden');
						} else {
							$('#feedback_text').addClass('hidden');
						}
					});

					var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
					var $form = $overlay.find('form');
					$($form).on('submit', function(e){
						e.preventDefault();
						let selected_feature = $form.find("input[name='feedback_data[feature]']:checked").val();
						let form_data = {
							questions_response : {
								selected_feature: selected_feature
							}
						}

						if ( selected_feature === 'other' ) {
							form_data.other_feature = $form.find('textarea[name="feedback[feedback_text]"]').val();
						}

						var request = doSend( form_data );
						request.done(response => {
							if (response.status && response.status === 'success') {
								let success_text = '<?php echo esc_html__( 'Your feedback submitted successfully.', $this->plugin ); ?>';
								$('.ig-feedback-notice').html('<p>' + success_text + '</p>');
							} else {
								let error_text = '<?php echo esc_html__( 'An error has occured. Please try again.', $this->plugin ); ?>';
								$('.ig-feedback-notice').removeClass('notice-success').addClass('notice-error').append('<p>' + error_text + '</p>');
							}
						}).fail( response => {
							let error_text = '<?php echo esc_html__( 'An error has occured. Please try again.', $this->plugin ); ?>';
									$('.ig-feedback-notice').removeClass('notice-success').addClass('notice-error').append('<p>' + error_text + '</p>');
						});
					});
				});
			</script>
			<?php
		}

		/**
		 * Render popup feedback widget
		 *
		 * @since 1.2.7
		 * 
		 * @param string $html
		 * @param array $params
		 * @return void
		 */
		public function render_popup_feedback_widget( $html, $params ) {
			$escape_allowed_tags = $this->get_escape_allowed_tags();
			?>
			<script type="text/javascript">

			jQuery(document).ready(function ($) {

				Swal.mixin({
					footer: '',
					position: '<?php echo esc_js( $params['position'] ); ?>',
					width: <?php echo esc_js( $params['width'] ); ?>,
					animation: false,
					focusConfirm: false,
					allowEscapeKey: true,
					showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
					allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
					showLoaderOnConfirm: true,
					confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
					backdrop: '<?php echo (int) $params['backdrop']; ?>'
				}).queue([
					{
						title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
						html: '<?php echo wp_kses( $html, $escape_allowed_tags ); ?>',
						customClass: {
							popup: 'animated fadeInUpBig'
						},
						onOpen: () => {

						},

						preConfirm: () => {
							var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
							var $form = $overlay.find('form');
							if ( ! jQuery($form)[0].checkValidity()) {
								Swal.showValidationMessage('<?php echo esc_html__( 'Please select option', $this->plugin ); ?>');
								return;
							}

							var poll_options = $form.find("input[name='feedback_data[poll_options]']:checked").val();
							var message = $form.find("#ig-feedback-data-poll-additional-message").val();

							var data = {
								poll_option: poll_options,
								additional_feedback: message
							};

							var meta = {};

							return doSend(data);
						}
					},

				]).then(response => {

					if (response.hasOwnProperty('value')) {

						Swal.fire({
							type: 'success',
							width: <?php echo esc_js( $params['width'] ); ?>,
							title: '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
							showConfirmButton: false,
							position: '<?php echo esc_js( $params['position'] ); ?>',
							timer: 1500,
							animation: false
						});

					}
				});
			});

			</script>
			<?php
		}
		/**
		 * Get Feedback API url
		 *
		 * @param $is_dev_mode
		 *
		 * @return string
		 *
		 * @since 1.0.1
		 */
		public function get_api_url( $is_dev_mode ) {

			if ( $is_dev_mode ) {
				$this->api_url = 'http://192.168.0.130:9094/store/feedback/';
			}

			return $this->api_url;
		}

		/**
		 * Deactivation Survey javascript.
		 *
		 * @since 1.0.0
		 */
		public function js() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$title = 'Why are you deactivating ' . $this->name . '?';
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . 'plugin.deactivation';

			?>
			<script type="text/javascript">
				jQuery(function ($) {
					var $deactivateLink = $('#the-list').find('[data-slug="<?php echo esc_js( $this->plugin ); ?>"] span.deactivate a'),
						$overlay = $('#ig-deactivate-survey-<?php echo esc_js( $this->plugin ); ?>'),
						$form = $overlay.find('form'),
						formOpen = false,
						consent = $('#ig-deactivate-survey-help-consent-<?php echo esc_js( $this->plugin ); ?>');

					function togglePersonalInfoFields(show) {

						if (show) {
							$form.find('#ig-deactivate-survey-info-name').show();
							$form.find('#ig-deactivate-survey-info-email-address').show();
							$form.find('#ig-deactivate-survey-consent-additional-data').show();
						} else {
							$form.find('#ig-deactivate-survey-info-name').hide();
							$form.find('#ig-deactivate-survey-info-email-address').hide();
							$form.find('#ig-deactivate-survey-consent-additional-data').hide();
						}

					};

					function loader($show) {

						if ($show) {
							$form.find('#ig-deactivate-survey-loader').show();
						} else {
							$form.find('#ig-deactivate-survey-loader').hide();
						}

					}

					function validateEmail(email) {
						var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
						if (!emailReg.test(email)) {
							return false;
						} else {
							return true;
						}
					};

					// Plugin listing table deactivate link.
					$deactivateLink.on('click', function (event) {
						event.preventDefault();
						$overlay.css('display', 'table');
						formOpen = true;
						$form.find('.ig-deactivate-survey-option:first-of-type input[type=radio]').focus();
					});
					// Survey radio option selected.
					$form.on('change', 'input[type=radio]', function (event) {
						event.preventDefault();
						$form.find('input[type=text], .error').hide();

						$form.find('.ig-deactivate-survey-option').removeClass('selected');
						$(this).closest('.ig-deactivate-survey-option').addClass('selected').find('input[type=text]').show();

						if (consent.attr('checked') === 'checked') {
							togglePersonalInfoFields(true);
						}
					});
					// Survey Skip & Deactivate.
					$form.on('click', '.ig-deactivate-survey-deactivate', function (event) {
						event.preventDefault();
						let deactivationURL = $deactivateLink.attr('href');
						let skipSurveyURL = deactivationURL + '&survey_status=skipped';
						location.href = skipSurveyURL;
					});

					// Help Consent
					togglePersonalInfoFields(false);
					loader(false);
					consent.on('click', function () {
						if (consent.attr('checked') === 'checked') {
							togglePersonalInfoFields(true);
						} else {
							togglePersonalInfoFields(false);
						}
					});

					// Survey submit.
					$form.submit(function (event) {
						event.preventDefault();
						loader(true);
						if (!$form.find('input[type=radio]:checked').val()) {
							$form.find('.ig-deactivate-survey-footer').prepend('<span class="error"><?php echo esc_js( __( 'Please select an option', $this->plugin ) ); ?></span>');
							return;
						}

						var system_info = false;
						var name = '';
						var email = '';

						if (consent.attr('checked') === 'checked') {
							name = $form.find('#ig-deactivate-survey-info-name').val();
							email = $form.find('#ig-deactivate-survey-info-email-address').val();
							if (email === '' || !validateEmail(email)) {
								alert('Please enter valid email');
								return;
							}
							system_info = true;
						}

						var meta = {
							name: name,
							email: email
						};

						var data = {
							action: '<?php echo esc_js( $this->ajax_action ); ?>',
							feedback: {
								type: 'radio',
								title: '<?php echo esc_js( $title ); ?>',
								slug: '<?php echo esc_js( $slug ); ?>',
								value: $form.find('.selected input[type=radio]').attr('data-option-slug'),
								details: $form.find('.selected input[type=text]').val()
							},

							event: '<?php echo esc_js( $event ); ?>',

							// Add additional information
							misc: {
								plugin: '<?php echo esc_js( $this->plugin ); ?>',
								plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
								is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
								set_cookie: '',
								meta_info: meta,
								system_info: system_info
							},
							security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
						};

						var submitSurvey = $.post(ajaxurl, data);
						submitSurvey.always(function () {
							location.href = $deactivateLink.attr('href');
						});
					});
					// Exit key closes survey when open.
					$(document).keyup(function (event) {
						if (27 === event.keyCode && formOpen) {
							$overlay.hide();
							formOpen = false;
							$deactivateLink.focus();
						}
					});
				});
			</script>
			<?php
		}

		/**
		 * Survey CSS.
		 *
		 * @since 1.0.0
		 */
		public function css() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}
			?>
			<style type="text/css">
				.ig-deactivate-survey-modal {
					display: none;
					table-layout: fixed;
					position: fixed;
					z-index: 9999;
					width: 100%;
					height: 100%;
					text-align: center;
					font-size: 14px;
					top: 0;
					left: 0;
					background: rgba(0, 0, 0, 0.8);
				}

				.ig-deactivate-survey-wrap {
					display: table-cell;
					vertical-align: middle;
				}

				.ig-deactivate-survey {
					background-color: #fff;
					max-width: 550px;
					margin: 0 auto;
					padding: 30px;
					text-align: left;
				}

				.ig-deactivate-survey .error {
					display: block;
					color: red;
					margin: 0 0 10px 0;
				}

				.ig-deactivate-survey-title {
					display: block;
					font-size: 18px;
					font-weight: 700;
					text-transform: uppercase;
					border-bottom: 1px solid #ddd;
					padding: 0 0 18px 0;
					margin: 0 0 18px 0;
				}

				.ig-deactivate-survey-options {
					border-bottom: 1px solid #ddd;
					padding: 0 0 18px 0;
					margin: 0 0 18px 0;
				}

				.ig-deactivate-survey-info-data {
					padding: 0 0 18px 0;
					margin: 10px 10px 10px 30px;
				}

				.ig-deactivate-survey-info-name, .ig-deactivate-survey-info-email-address {
					width: 230px;
					margin: 10px;
				}

				.ig-deactivate-survey-title span {
					color: #999;
					margin-right: 10px;
				}

				.ig-deactivate-survey-desc {
					display: block;
					font-weight: 600;
					margin: 0 0 18px 0;
				}

				.ig-deactivate-survey-option {
					margin: 0 0 10px 0;
				}

				.ig-deactivate-survey-option-input {
					margin-right: 10px !important;
				}

				.ig-deactivate-survey-option-details {
					display: none;
					width: 90%;
					margin: 10px 0 0 30px;
				}

				.ig-deactivate-survey-footer {
					margin-top: 18px;
				}

				.ig-deactivate-survey-deactivate {
					float: right;
					font-size: 13px;
					color: #ccc;
					text-decoration: none;
					padding-top: 7px;
				}

				.ig-deactivate-survey-loader {
					vertical-align: middle;
					padding: 10px;
				}
			</style>
			<?php
		}

		/**
		 * Survey modal.
		 *
		 * @since 1.0.0
		 */
		public function modal() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$email = $this->get_contact_email();

			$options = array(
				1 => array(
					'title' => esc_html__( 'I no longer need the plugin', $this->plugin ),
					'slug'  => 'i-no-longer-need-the-plugin',
				),
				2 => array(
					'title'   => esc_html__( 'I\'m switching to a different plugin', $this->plugin ),
					'slug'    => 'i-am-switching-to-a-different-plugin',
					'details' => esc_html__( 'Please share which plugin', $this->plugin ),
				),
				3 => array(
					'title' => esc_html__( 'I couldn\'t get the plugin to work', $this->plugin ),
					'slug'  => 'i-could-not-get-the-plugin-to-work',
				),
				4 => array(
					'title' => esc_html__( 'It\'s a temporary deactivation', $this->plugin ),
					'slug'  => 'it-is-a-temporary-deactivation',
				),
				5 => array(
					'title'   => esc_html__( 'Other', $this->plugin ),
					'slug'    => 'other',
					'details' => esc_html__( 'Please share the reason', $this->plugin ),
				),
			);
			?>
			<div class="ig-deactivate-survey-modal" id="ig-deactivate-survey-<?php echo esc_js( $this->plugin ); ?>">
				<div class="ig-deactivate-survey-wrap">
					<form class="ig-deactivate-survey" method="post">
						<span class="ig-deactivate-survey-title"><span class="dashicons dashicons-testimonial"></span><?php echo ' ' . esc_html__( 'Quick Feedback', $this->plugin ); ?></span>
						<span class="ig-deactivate-survey-desc"><?php echo esc_html__( sprintf( 'If you have a moment, please share why you are deactivating %s:', $this->name ), $this->plugin ); ?></span>
						<div class="ig-deactivate-survey-options">
							<?php foreach ( $options as $id => $option ) : ?>
								<div class="ig-deactivate-survey-option">
									<label for="ig-deactivate-survey-option-<?php echo esc_attr( $this->plugin ); ?>-<?php echo esc_attr( $id ); ?>" class="ig-deactivate-survey-option-label">
										<input id="ig-deactivate-survey-option-<?php echo esc_attr( $this->plugin ); ?>-<?php echo esc_attr( $id ); ?>" class="ig-deactivate-survey-option-input" type="radio" name="code" value="<?php echo esc_attr( $id ); ?>" data-option-slug="<?php echo esc_attr( $option['slug'] ); ?>"/>
										<span class="ig-deactivate-survey-option-reason"><?php echo esc_attr( $option['title'] ); ?></span>
									</label>
									<?php if ( ! empty( $option['details'] ) ) : ?>
										<input class="ig-deactivate-survey-option-details" type="text" placeholder="<?php echo esc_attr( $option['details'] ); ?>"/>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="ig-deactivate-survey-help-consent">
							<input id="ig-deactivate-survey-help-consent-<?php echo esc_attr( $this->plugin ); ?>" class="ig-deactivate-survey-option-input" type="checkbox" name="code" data-option-slug="<?php echo esc_attr( $option['slug'] ); ?>"/><b><?php echo esc_html__( 'Yes, I give my consent to track plugin usage and contact me back to make this plugin works!', $this->plugin ); ?></b>
						</div>
						<div class="ig-deactivate-survey-info-data">

							<input type="text" class="ig-deactivate-survey-info-name" id="ig-deactivate-survey-info-name" placeholder="Enter Name" name="ig-deactivate-survey-info-name" value=""/>
							<input type="text" class="ig-deactivate-survey-info-email-address" id="ig-deactivate-survey-info-email-address" name="ig-deactivate-survey-info-email-address" value="<?php echo esc_js( $email ); ?>"/>
						</div>
						<div class="ig-deactivate-survey-footer">
							<button type="submit" class="ig-deactivate-survey-submit button button-primary button-large"><?php echo sprintf( esc_html__( 'Submit %s Deactivate', $this->plugin ), '&amp;' ); ?></button>
							<img class="ig-deactivate-survey-loader" id="ig-deactivate-survey-loader" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>/assets/images/loading.gif"/>
							<a href="#" class="ig-deactivate-survey-deactivate"><?php echo sprintf( esc_html__( 'Skip %s Deactivate', $this->plugin ), '&amp;' ); ?></a>
						</div>
					</form>
				</div>
			</div>
			<?php
		}

		/**
		 * Can we show feedback widget in this environment
		 *
		 * @return bool
		 */
		public function can_show_feedback_widget() {

			// Is development mode? Enable it.
			if ( $this->is_dev_mode ) {
				return true;
			}

			// Don't show on dev setup if dev mode is off.
			if ( $this->is_dev_url() ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if current admin screen is the plugins page.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function is_plugin_page() {

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			if ( empty( $screen ) ) {
				return false;
			}

			return ( ! empty( $screen->id ) && in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) );
		}


		/**
		 * Checks if current site is a development one.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function is_dev_url() {

			$url          = network_site_url( '/' );
			$is_local_url = false;

			// Trim it up
			$url = strtolower( trim( $url ) );

			// Need to get the host...so let's add the scheme so we can use parse_url
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				$url = 'http://' . $url;
			}

			$url_parts = parse_url( $url );
			$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

			// Discard our development environment
			if ( '192.168.0.112' === $host ) {
				return false;
			}

			if ( ! empty( $url ) && ! empty( $host ) ) {
				if ( false !== ip2long( $host ) ) {
					if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						$is_local_url = true;
					}
				} elseif ( 'localhost' === $host ) {
					$is_local_url = true;
				}

				$tlds_to_check = array( '.dev', '.local', ':8888' );
				foreach ( $tlds_to_check as $tld ) {
					if ( false !== strpos( $host, $tld ) ) {
						$is_local_url = true;
						continue;
					}
				}
				if ( substr_count( $host, '.' ) > 1 ) {
					$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
					foreach ( $subdomains_to_check as $subdomain ) {
						$subdomain = str_replace( '.', '(.)', $subdomain );
						$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
						if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
							$is_local_url = true;
							continue;
						}
					}
				}
			}

			return $is_local_url;
		}

		/**
		 * Store plugin feedback data into option
		 *
		 * @param $plugin_abbr
		 * @param $event
		 * @param $data
		 *
		 * @since 1.0.1
		 */
		public function set_feedback_data( $plugin_abbr, $event, $data = array() ) {

			$feedback_option = $plugin_abbr . '_feedback_data';

			$feedback_data = maybe_unserialize( get_option( $feedback_option, array() ) );

			$data['created_on'] = gmdate( 'Y-m-d H:i:s' );

			$feedback_data[ $event ][] = $data;

			update_option( $feedback_option, $feedback_data );

		}

		/**
		 * Get plugin feedback data
		 *
		 * @param $plugin_abbr
		 *
		 * @return mixed|void
		 *
		 * @since 1.0.1
		 */
		public function get_feedback_data( $plugin_abbr ) {

			$feedback_option = $plugin_abbr . '_feedback_data';

			return get_option( $feedback_option, array() );
		}

		/**
		 * Get event specific feedback data
		 *
		 * @param $plugin_abbr
		 * @param $event
		 *
		 * @return array|mixed
		 */
		public function get_event_feedback_data( $plugin_abbr, $event ) {

			$feedback_data = $this->get_feedback_data( $plugin_abbr );

			$event_feedback_data = ! empty( $feedback_data[ $event ] ) ? $feedback_data[ $event ] : array();

			return $event_feedback_data;
		}

		/**
		 * Check whether event tracked or not.
		 *
		 * @param $plugin_abbr
		 * @param $event
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		public function is_event_tracked( $plugin_abbr = '', $event = '' ) {

			if ( empty( $plugin_abbr ) || empty( $event ) ) {
				return false;
			}

			$feedback_data = $this->get_feedback_data( $plugin_abbr );

			if ( count( $feedback_data ) > 0 ) {

				$events = array_keys( $feedback_data );

				foreach ( $events as $key => $value ) {
					if ( strpos( $value, $event ) ) {
						return true;
					}
				}
			}

			return false;
		}


		/**
		 * Set event into transient
		 *
		 * @param $event
		 * @param int   $expiry in days
		 */
		public function set_event_transient( $event, $expiry = 45 ) {
			set_transient( $event, 1, time() + ( 86400 * $expiry ) );
		}

		/**
		 * Check whether event transient is set or not.
		 *
		 * @param $event
		 *
		 * @return bool
		 *
		 * @since 1.0.1
		 */
		public function is_event_transient_set( $event ) {
			return get_transient( $event );
		}

		/**
		 * Get contact email
		 *
		 * @return string
		 *
		 * @since 1.0.8
		 */
		public function get_contact_email() {

			$email = '';

			// Get logged in User Email Address
			$current_user = wp_get_current_user();
			if ( $current_user instanceof WP_User ) {
				$email = $current_user->user_email;
			}

			// If email empty, get admin email
			if ( empty( $email ) ) {
				$email = get_option( 'admin_email' );
			}

			return $email;
		}

		/**
		 * Hook to ajax_action
		 *
		 * Send feedback to server
		 */
		public function submit_feedback() {

			check_ajax_referer( $this->plugin_abbr . '-admin-ajax-nonce', 'security' );

			$data = ! empty( $_POST ) ? $_POST : array();

			$data['site'] = esc_url( home_url() );

			$plugin        = ! empty( $data['misc']['plugin'] ) ? $data['misc']['plugin'] : 'ig_feedback';
			$plugin_abbr   = ! empty( $data['misc']['plugin_abbr'] ) ? $data['misc']['plugin_abbr'] : 'ig_feedback';
			$is_dev_mode   = ! empty( $data['misc']['is_dev_mode'] ) ? $data['misc']['is_dev_mode'] : false;
			$set_transient = ! empty( $data['misc']['set_transient'] ) ? $data['misc']['set_transient'] : false;
			$system_info   = ( isset( $data['misc']['system_info'] ) && $data['misc']['system_info'] === 'true' ) ? true : false;
			$meta_info     = ! empty( $data['misc']['meta_info'] ) ? $data['misc']['meta_info'] : array();

			unset( $data['misc'] );

			$default_meta_info = array(
				'plugin'      => sanitize_key( $plugin ),
				'locale'      => get_locale(),
				'wp_version'  => get_bloginfo( 'version' ),
				'php_version' => PHP_VERSION,
			);

			$meta_info = wp_parse_args( $meta_info, $default_meta_info );

			$additional_info = array();
			$additional_info = apply_filters( $plugin_abbr . '_additional_feedback_meta_info', $additional_info, $system_info ); // Get Additional meta information

			if ( is_array( $additional_info ) && count( $additional_info ) > 0 ) {
				$meta_info = array_merge( $meta_info, $additional_info );
			}

			$data['meta'] = $meta_info;

			$data = wp_unslash( $data );

			$args = array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $data,
				'blocking'  => false,
			);

			$this->set_feedback_data( $plugin_abbr, $data['event'], $data['feedback'] );

			// Set Cookie
			if ( $set_transient ) {
				$this->set_event_transient( $data['event'] );
			}

			$response         = wp_remote_post( $this->get_api_url( $is_dev_mode ), $args );
			$result['status'] = 'success';
			if ( $response instanceof WP_Error ) {
				$error_message     = $response->get_error_message();
				$result['status']  = 'error';
				$result['message'] = $error_message;
			}

			die( json_encode( $result ) );
		}

		/**
		 * Get list of tags allowed in escaping
		 * 
		 * @return array $ig_es_escape_allowed_tags
		 */
		public function get_escape_allowed_tags() {
			$ig_es_escape_allowed_tags = apply_filters( $this->plugin_abbr . '_escape_allowed_tags', array() );
			return $ig_es_escape_allowed_tags;
		}

		/**
		 * Render Quick Feedback Widget
		 *
		 * @param $params
		 *
		 * @since 4.1.0
		 */
		public function render_feedback_widget( $params ) {

			if ( ! $this->can_show_feedback_widget() ) {
				return;
			}

			$default_params = array(
				'set_transient' => true,
				'force'         => false,
				'show_once'     => false,
			);

			$params = wp_parse_args( $params, $default_params );

			if ( ! empty( $params['event'] ) ) {

				$event = $this->event_prefix . $params['event'];
				$force = ! empty( $params['force'] ) ? $params['force'] : false;

				$can_show = false;

				if ( $force ) {
					$can_show = true;
				} else {
					if ( ! $this->is_event_transient_set( $event ) ) {
						$can_show = true;

						$feedback_data = $this->get_event_feedback_data( $this->plugin_abbr, $event );
						if ( count( $feedback_data ) > 0 ) {
							$show_once              = $params['show_once'];
							$feedback_data          = array_reverse( $feedback_data );
							$last_feedback_given_on = $feedback_data[0]['created_on'];

							// If event feedback given within 45 days or show event only once?
							// Don't show now
							if ( $show_once || ( strtotime( $last_feedback_given_on ) > strtotime( '-45 days' ) ) ) {
								$can_show = false;
							}
						}
					}
				}

				if ( $can_show ) {
					if ( 'star' === $params['type'] ) {
						$this->render_stars( $params );
					} elseif ( 'emoji' === $params['type'] ) {
						$this->render_emoji( $params );
					} elseif ( 'feedback' === $params['type'] ) {
						$this->render_general_feedback( $params );
					} elseif ( 'fb' === $params['type'] ) {
						/**
						 * We are not calling home for this event and we want to show
						 * this Widget only once. So, we are storing feedback data now.
						 */
						$this->set_feedback_data( $this->plugin_abbr, $event );
						$this->render_fb_widget( $params );
					} elseif ( 'poll' === $params['type'] ) {
						if ( $params['show_once'] ) {
							$this->set_feedback_data( $this->plugin_abbr, $event );
						}
						$this->render_poll_widget( $params );
					}
				}
			}

		}
	}
} // End if().