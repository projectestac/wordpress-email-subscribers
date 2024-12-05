<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Campaign_Admin' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Admin Settings
	 *
	 * @package    Email_Subscribers
	 * @subpackage Email_Subscribers/admin
	 */
	class ES_Campaign_Admin extends ES_Admin {

		// class instance
		public static $instance;

		/**
		 * Campaign ID
		 */
		private $campaign_data = array();

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

			add_action( 'media_buttons', array( $this, 'add_tag_button' ) );
		}

		public function setup() {
			$campaign_id = $this->get_campaign_id_from_url();
			if ( ! empty( $campaign_id ) ) {
				$campaign = new ES_Campaign( $campaign_id );
				if ( $campaign->exists ) {
					$this->campaign_data = (array) $campaign;
					if ( empty( $this->campaign_data['meta']['editor_type'] ) ) {
						$this->campaign_data['meta']['editor_type'] = IG_ES_CLASSIC_EDITOR;
					}
				}
			} else {
				if ( empty( $this->campaign_data ) ) {
					$this->campaign_data['type']                = $this->get_campaign_type_from_url();
					$this->campaign_data['meta']['editor_type'] = $this->get_editor_type_from_url();
				}
			}
		}

		public function get_campaign_id_from_url() {
			$campaign_id = ig_es_get_request_data( 'id' );
			return $campaign_id;
		}

		public function get_campaign_type_from_url() {
			$current_page = ig_es_get_request_data( 'page' );

			$campaign_type = '';
			if ( 'es_newsletters' === $current_page ) {
				$campaign_type = IG_CAMPAIGN_TYPE_NEWSLETTER;
			} elseif ( 'es_notifications' === $current_page ) {
				$campaign_type = IG_CAMPAIGN_TYPE_POST_NOTIFICATION;
			}

			return $campaign_type;
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

		/**
		 * Method to process campaign submission.
		 *
		 * @since 4.4.7
		 * 
		 * @modify 5.6.4
		 */
		public function process_submission() {

			$campaign_action = ig_es_get_request_data( 'ig_es_campaign_action' );

			if ( ! empty( $campaign_action ) ) {

				$campaign_nonce = ig_es_get_request_data( 'ig_es_campaign_nonce' );

				// Verify nonce.
				if ( wp_verify_nonce( $campaign_nonce, 'ig-es-campaign-nonce' ) ) {
					$campaign_data = ig_es_get_request_data( 'data', array(), false );
					$list_id       = ! empty( $campaign_data['list_ids'] ) ? $campaign_data['list_ids'] : '';
					$template_id   = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : '';
					$subject       = ! empty( $campaign_data['subject'] ) ? $campaign_data['subject'] : '';

					// Check if user has added required data for creating campaign.
					if ( ! empty( $campaign_data['subject'] ) && ! empty( $campaign_data['body'] ) && ! empty( $subject ) ) {
						$campaign_data['subject']          = wp_strip_all_tags( $campaign_data['subject'] );
						$is_updating_campaign              = ! empty( $campaign_data['id'] ) ? true : false;
						$campaign_data['base_template_id'] = $template_id;
						$campaign_data['list_ids']         = $list_id;
						$meta                              = ! empty( $campaign_data['meta'] ) ? $campaign_data['meta'] : array();
						$meta['scheduling_option']         = ! empty( $campaign_data['scheduling_option'] ) ? $campaign_data['scheduling_option'] : 'schedule_now';
						$meta['es_schedule_date']          = ! empty( $campaign_data['es_schedule_date'] ) ? $campaign_data['es_schedule_date'] : '';
						$meta['es_schedule_time']          = ! empty( $campaign_data['es_schedule_time'] ) ? $campaign_data['es_schedule_time'] : '';
						$meta['pre_header']                = ! empty( $campaign_data['pre_header'] ) ? $campaign_data['pre_header'] : '';
						$meta['preheader']                 = ! empty( $campaign_data['preheader'] ) ? $campaign_data['preheader'] : '';

						if ( ! empty( $meta['list_conditions'] ) ) {
							$meta['list_conditions'] = IG_ES_Campaign_Rules::remove_empty_conditions( $meta['list_conditions'] );
						}
						
						$meta = apply_filters( 'ig_es_before_save_campaign_meta', $meta, $campaign_data );

						$campaign_data['meta'] = maybe_serialize( $meta );

						if ( 'schedule' === $campaign_action ) {
							$campaign_data['status'] = IG_ES_CAMPAIGN_STATUS_SCHEDULED;
						} elseif ( 'activate' === $campaign_action ) {
							$campaign_data['status'] = IG_ES_CAMPAIGN_STATUS_ACTIVE;
						}

						$campaign_saved = self::save_campaign( $campaign_data );

						$campaign_status = ! $is_updating_campaign && $campaign_saved ? 'campaign_created' : 'error';
						$campaign_status = $is_updating_campaign && $campaign_saved ? 'campaign_updated' : 'error';

						if ( 'schedule' === $campaign_action ) {
							if ( empty( $meta['list_conditions'] ) ) {
								$error_message = __( 'No recipients were found. Please add some recipients.', 'email-subscribers' );
							} else {
								$scheduling_status = self::schedule_campaign( $campaign_data );
								if ( 'success' !== $scheduling_status ) {
									if ( 'emails_not_queued' === $scheduling_status ) {
										$error_message = __( 'Campaign not scheduled. Please check if there are some recipients according to the selected campaign rules.', 'email-subscribers' );
									} elseif ( '' === $scheduling_status ) {
										$error_message = __( 'Campaign not scheduled due to some error. Please try again later.', 'email-subscribers' );
									}
								}
							}
							if ( ! empty( $error_message ) ) {
								
								$campaign_data['status'] = IG_ES_CAMPAIGN_STATUS_IN_ACTIVE; // Revert back camaign status to inactive(draft), if scheduling fails.
								self::save_campaign( $campaign_data );
								$this->campaign_data = ig_es_get_request_data( 'data' );
								ES_Common::show_message( $error_message, 'error' );
								return;
							}
						}

						$campaign_url = admin_url( 'admin.php?page=es_campaigns&id=' . $campaign_data['id'] . '&action=' . $campaign_status );

						wp_safe_redirect( $campaign_url );
						exit();
					}
				} else {
					$message = __( 'Sorry, you are not allowed to add/edit campaign.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					return;
				}
			}
		}

		public function render() {

			global $wpdb;

			$campaign_id   = ig_es_get_request_data( 'id' );
			$submitted     = ig_es_get_request_data( 'ig_es_campaign_submitted' );
			$campaign_data = ig_es_get_request_data( 'data', array(), false );
			$message_data  = array();

			$campaign_action = ig_es_get_request_data( 'ig_es_campaign_action' );

			if ( ! empty( $campaign_action ) ) {

				if ( empty( $campaign_data['subject'] ) ) {
					$message      = __( 'Please add a campaign subject.', 'email-subscribers' );
					$message_data = array(
						'message' => $message,
						'type'    => 'error',
					);
				}
			}

			$this->show_campaign_form( $message_data );
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

			$campaign_type = isset( $this->campaign_data['type'] ) ? $this->campaign_data['type'] : '';
			?>

			<div id="ig-es-add-tags-button" data-editor-id="<?php echo esc_attr( $editor_id ); ?>" class="relative bg-white inline-block">
				<button type="button" class="button">
					<span class="dashicons dashicons-tag"></span>
					<?php echo esc_html__( 'Add Tags', 'email-subscribers' ); ?>
				</button>
				<div x-show="open" id="ig-es-tags-dropdown" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
				x-transition:leave-end="transform opacity-0 scale-95" class="absolute center-0 z-10 hidden w-56 origin-top-right rounded-md shadow-lg">
					<div class="bg-white rounded-md shadow-xs">
						<?php $this->show_merge_tags( $campaign_type, $editor_id ); ?>
					</div>
				  </div>
		  </div>
			<?php
		}

		public function get_campaign_tags() {

			$post_notification_tags = $this->get_post_notification_tags();

			$campaign_tags = array(
				'post_notification' => $post_notification_tags,
			);

			return apply_filters( 'ig_es_campaign_tags', $campaign_tags );
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

		public function get_dnd_campaign_tags() {

			$post_notification_tags = array(
				array(
					'keyword'  => 'post.title',
					'label' => __( 'Post title', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M5.5 4.25C5.5 4.05109 5.57902 3.86032 5.71967 3.71967C5.86032 3.57902 6.05109 3.5 6.25 3.5H18.75C18.9489 3.5 19.1397 3.57902 19.2803 3.71967C19.421 3.86032 19.5 4.05109 19.5 4.25V6.25C19.5 6.44891 19.421 6.63968 19.2803 6.78033C19.1397 6.92098 18.9489 7 18.75 7C18.5511 7 18.3603 6.92098 18.2197 6.78033C18.079 6.63968 18 6.44891 18 6.25V5H13.25V18H14.75C14.9489 18 15.1397 18.079 15.2803 18.2197C15.421 18.3603 15.5 18.5511 15.5 18.75C15.5 18.9489 15.421 19.1397 15.2803 19.2803C15.1397 19.421 14.9489 19.5 14.75 19.5H10.25C10.0511 19.5 9.86032 19.421 9.71967 19.2803C9.57902 19.1397 9.5 18.9489 9.5 18.75C9.5 18.5511 9.57902 18.3603 9.71967 18.2197C9.86032 18.079 10.0511 18 10.25 18H11.75V5H7V6.25C7 6.44891 6.92098 6.63968 6.78033 6.78033C6.63968 6.92098 6.44891 7 6.25 7C6.05109 7 5.86032 6.92098 5.71967 6.78033C5.57902 6.63968 5.5 6.44891 5.5 6.25V4.25Z" fill="#575362"/>
					</svg>
					',
					'description' => __( 'Show a post title', 'email-subscribers' ),
				),
				array( 
					'keyword'  => 'post.image',
					'label' => __( 'Post image', 'email-subscribers' ),
					'icon' => '<svg  width="36" height="36" viewBox="0 0 172 172" style=" fill:#000000;"><g transform="translate(0.516,0.516) scale(0.994,0.994)"><g fill="none" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-size="none" style="mix-blend-mode: normal"><g fill="#000000" stroke="#cccccc" stroke-linejoin="round"><path d="M172,17.2v137.6h-172v-137.6zM6.88,94.89563l31.4975,-31.4975l14.80813,22.22562l16.74312,-3.35937l14.05563,14.05562h20.3175l24.08,17.2h36.73812v-89.44h-158.24zM144.48,60.2c0,8.50594 -6.97406,15.48 -15.48,15.48c-8.50594,0 -15.48,-6.97406 -15.48,-15.48c0,-8.50594 6.97406,-15.48 15.48,-15.48c8.50594,0 15.48,6.97406 15.48,15.48zM120.4,60.2c0,4.78375 3.81625,8.6 8.6,8.6c4.78375,0 8.6,-3.81625 8.6,-8.6c0,-4.78375 -3.81625,-8.6 -8.6,-8.6c-4.78375,0 -8.6,3.81625 -8.6,8.6zM6.88,104.62438v43.29562h158.24v-27.52h-38.94187l-24.08,-17.2h-20.9625l-13.46438,-13.46437l-17.65687,3.52062l-12.71188,-19.05437z"></path></g><path d="M0,172v-172h172v172z" fill="none" stroke="none" stroke-linejoin="miter"></path><g fill="#000000" stroke="none" stroke-linejoin="miter"><path d="M0,17.2v137.6h172v-137.6zM6.88,24.08h158.24v89.44h-36.73812l-24.08,-17.2h-20.3175l-14.05563,-14.05562l-16.74312,3.35937l-14.80813,-22.22562l-31.4975,31.4975zM129,44.72c-8.50594,0 -15.48,6.97406 -15.48,15.48c0,8.50594 6.97406,15.48 15.48,15.48c8.50594,0 15.48,-6.97406 15.48,-15.48c0,-8.50594 -6.97406,-15.48 -15.48,-15.48zM129,51.6c4.78375,0 8.6,3.81625 8.6,8.6c0,4.78375 -3.81625,8.6 -8.6,8.6c-4.78375,0 -8.6,-3.81625 -8.6,-8.6c0,-4.78375 3.81625,-8.6 8.6,-8.6zM37.3025,74.20188l12.71188,19.05437l17.65687,-3.52062l13.46438,13.46437h20.9625l24.08,17.2h38.94187v27.52h-158.24v-43.29562z"></path></g><path d="" fill="none" stroke="none" stroke-linejoin="miter"></path></g></g></svg>				
				  ',
				),
				array(
					'keyword'  => 'post.date',
					'label' => __( 'Post date', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18.1538 22H5.84615C4.82609 22 3.84781 21.5948 3.12651 20.8735C2.40522 20.1522 2 19.1739 2 18.1538V7.38461C2 6.36454 2.40522 5.38626 3.12651 4.66496C3.84781 3.94367 4.82609 3.53845 5.84615 3.53845H18.1538C19.1739 3.53845 20.1522 3.94367 20.8735 4.66496C21.5948 5.38626 22 6.36454 22 7.38461V18.1538C22 19.1739 21.5948 20.1522 20.8735 20.8735C20.1522 21.5948 19.1739 22 18.1538 22ZM5.84615 5.07691C5.23412 5.07691 4.64715 5.32004 4.21437 5.75282C3.78159 6.1856 3.53846 6.77257 3.53846 7.38461V18.1538C3.53846 18.7659 3.78159 19.3528 4.21437 19.7856C4.64715 20.2184 5.23412 20.4615 5.84615 20.4615H18.1538C18.7659 20.4615 19.3529 20.2184 19.7856 19.7856C20.2184 19.3528 20.4615 18.7659 20.4615 18.1538V7.38461C20.4615 6.77257 20.2184 6.1856 19.7856 5.75282C19.3529 5.32004 18.7659 5.07691 18.1538 5.07691H5.84615Z" fill="#575362" stroke="white" stroke-width="0.25"/>
					<path d="M18.1547 18.9231H15.0778C14.8738 18.9231 14.6782 18.8421 14.5339 18.6978C14.3896 18.5536 14.3086 18.3579 14.3086 18.1539V15.077C14.3086 14.873 14.3896 14.6773 14.5339 14.533C14.6782 14.3888 14.8738 14.3077 15.0778 14.3077H18.1547C18.3588 14.3077 18.5544 14.3888 18.6987 14.533C18.8429 14.6773 18.924 14.873 18.924 15.077V18.1539C18.924 18.3579 18.8429 18.5536 18.6987 18.6978C18.5544 18.8421 18.3588 18.9231 18.1547 18.9231ZM15.8471 17.3847H17.3855V15.8462H15.8471V17.3847Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M20.3077 9.61533H3.69231C3.5087 9.61533 3.33261 9.54239 3.20277 9.41256C3.07294 9.28272 3 9.10663 3 8.92302C3 8.73941 3.07294 8.56332 3.20277 8.43349C3.33261 8.30365 3.5087 8.23071 3.69231 8.23071H20.3077C20.4913 8.23071 20.6674 8.30365 20.7972 8.43349C20.9271 8.56332 21 8.73941 21 8.92302C21 9.10663 20.9271 9.28272 20.7972 9.41256C20.6674 9.54239 20.4913 9.61533 20.3077 9.61533Z" fill="#575362"/>
					<path d="M8.154 6.61538C7.94998 6.61538 7.75433 6.53434 7.61007 6.39008C7.46581 6.24582 7.38477 6.05017 7.38477 5.84615V2.76923C7.38477 2.56522 7.46581 2.36956 7.61007 2.2253C7.75433 2.08104 7.94998 2 8.154 2C8.35801 2 8.55367 2.08104 8.69792 2.2253C8.84218 2.36956 8.92323 2.56522 8.92323 2.76923V5.84615C8.92323 6.05017 8.84218 6.24582 8.69792 6.39008C8.55367 6.53434 8.35801 6.61538 8.154 6.61538Z" fill="#575362"/>
					<path d="M15.8454 6.61538C15.6414 6.61538 15.4457 6.53434 15.3015 6.39008C15.1572 6.24582 15.0762 6.05017 15.0762 5.84615V2.76923C15.0762 2.56522 15.1572 2.36956 15.3015 2.2253C15.4457 2.08104 15.6414 2 15.8454 2C16.0494 2 16.2451 2.08104 16.3893 2.2253C16.5336 2.36956 16.6146 2.56522 16.6146 2.76923V5.84615C16.6146 6.05017 16.5336 6.24582 16.3893 6.39008C16.2451 6.53434 16.0494 6.61538 15.8454 6.61538Z" fill="#575362"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.excerpt',
					'label' => __( 'Post excerpt', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M17.5 5.59998H3.5H17.5ZM21.5 11.6H3.5H21.5ZM15.6 17.5H3.5H15.6Z" fill="#575362"/>
					<path d="M17.5 5.59998H3.5M21.5 11.6H3.5M15.6 17.5H3.5" stroke="#575362" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.description',
					'label' => __( 'Post description', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M23 4H1M23 9.33333H1M23 14.6667H1M23 20H1" stroke="#575362" stroke-width="1.71429" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.author',
					'label' => __( 'Post author', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4.02773 20.9122L3.99847 21.1125H4.20089H19.7991H20.0015L19.9723 20.9122C19.5618 18.1026 17.0563 15.95 14.0402 15.95H9.95982C6.94371 15.95 4.43815 18.1026 4.02773 20.9122ZM15.7464 6.5C15.7464 5.5397 15.35 4.62015 14.6467 3.94324C13.9437 3.26654 12.9915 2.8875 12 2.8875C11.0085 2.8875 10.0563 3.26654 9.35326 3.94324C8.64997 4.62015 8.25357 5.5397 8.25357 6.5C8.25357 7.4603 8.64997 8.37985 9.35326 9.05676C10.0563 9.73346 11.0085 10.1125 12 10.1125C12.9915 10.1125 13.9437 9.73346 14.6467 9.05676C15.35 8.37985 15.7464 7.4603 15.7464 6.5ZM6.46071 6.5C6.46071 5.08993 7.04262 3.73619 8.08075 2.737C9.1191 1.73759 10.5288 1.175 12 1.175C13.4712 1.175 14.8809 1.73759 15.9193 2.737C16.9574 3.73619 17.5393 5.08993 17.5393 6.5C17.5393 7.91007 16.9574 9.26381 15.9193 10.263C14.8809 11.2624 13.4712 11.825 12 11.825C10.5288 11.825 9.1191 11.2624 8.08075 10.263C7.04262 9.26381 6.46071 7.91007 6.46071 6.5ZM2.175 21.7238C2.175 17.5943 5.65279 14.2375 9.95982 14.2375H14.0402C18.3472 14.2375 21.825 17.5943 21.825 21.7238C21.825 22.3256 21.316 22.825 20.6741 22.825H3.32589C2.68404 22.825 2.175 22.3256 2.175 21.7238Z" fill="#575362" stroke="white" stroke-width="0.35"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.link',
					'label' => __( 'Post link', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15.7244 3C14.2761 3 13.0348 3.51724 12.0003 4.55172L9.72441 6.82759C9.31061 7.24138 9.31061 7.86207 9.72441 8.27586C10.1382 8.68965 10.7589 8.68965 11.1727 8.27586L13.4485 6C14.6899 4.75862 16.7589 4.75862 18.0003 6C18.621 6.62069 18.9313 7.44827 18.9313 8.27586C18.9313 9.10345 18.621 9.93103 18.0003 10.5517L15.7244 12.8276C15.3106 13.2414 15.3106 13.8621 15.7244 14.2759C15.9313 14.4828 16.2416 14.5862 16.4485 14.5862C16.6554 14.5862 16.9658 14.4828 17.1727 14.2759L19.4485 12C20.483 10.9655 21.0003 9.72414 21.0003 8.27586C21.0003 6.82758 20.483 5.58621 19.4485 4.55172C18.4141 3.51724 17.1727 3 15.7244 3Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M8.27586 11.2758C8.68965 10.862 8.68965 10.2413 8.27586 9.82756C7.86207 9.41376 7.24138 9.41376 6.82759 9.82756L4.55172 12C3.51724 13.0345 3 14.2758 3 15.7241C3 17.1724 3.51724 18.4138 4.55172 19.4482C5.58621 20.4827 6.82758 21 8.27586 21C9.72414 21 10.9655 20.4827 12 19.4482L14.2759 17.1724C14.6897 16.7586 14.6897 16.1379 14.2759 15.7241C13.8621 15.3103 13.2414 15.3103 12.8276 15.7241L10.5517 18C9.31034 19.2413 7.24138 19.2413 6 18C5.37931 17.3793 5.06897 16.5517 5.06897 15.7241C5.06897 14.8965 5.37931 14.0689 6 13.4482L8.27586 11.2758Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M8.9998 15C9.20669 15.2069 9.51704 15.3103 9.72394 15.3103C9.93083 15.3103 10.2412 15.2069 10.4481 15L14.8963 10.5517C15.3101 10.1379 15.3101 9.51723 14.8963 9.10344C14.4826 8.68964 13.8619 8.68964 13.4481 9.10344L8.9998 13.5517C8.586 13.8621 8.586 14.5862 8.9998 15Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.link_with_title',
					'label' => __( 'Post link with title', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 11.5156C12 11.3789 12.0564 11.2477 12.1569 11.151C12.2574 11.0543 12.3936 11 12.5357 11H21.4643C21.6064 11 21.7426 11.0543 21.8431 11.151C21.9436 11.2477 22 11.3789 22 11.5156V12.8906C22 13.0274 21.9436 13.1585 21.8431 13.2552C21.7426 13.3519 21.6064 13.4063 21.4643 13.4063C21.3222 13.4063 21.1859 13.3519 21.0855 13.2552C20.985 13.1585 20.9286 13.0274 20.9286 12.8906V12.0313H17.5357V20.9688H18.6071C18.7492 20.9688 18.8855 21.0231 18.9859 21.1198C19.0864 21.2165 19.1429 21.3476 19.1429 21.4844C19.1429 21.6211 19.0864 21.7523 18.9859 21.849C18.8855 21.9457 18.7492 22 18.6071 22H15.3929C15.2508 22 15.1145 21.9457 15.0141 21.849C14.9136 21.7523 14.8571 21.6211 14.8571 21.4844C14.8571 21.3476 14.9136 21.2165 15.0141 21.1198C15.1145 21.0231 15.2508 20.9688 15.3929 20.9688H16.4643V12.0313H13.0714V12.8906C13.0714 13.0274 13.015 13.1585 12.9145 13.2552C12.8141 13.3519 12.6778 13.4063 12.5357 13.4063C12.3936 13.4063 12.2574 13.3519 12.1569 13.2552C12.0564 13.1585 12 13.0274 12 12.8906V11.5156Z" fill="#575362"/>
					<path d="M11.0873 2C10.0529 2 9.16621 2.33888 8.42731 3.01665L6.80175 4.50773C6.50619 4.77883 6.50619 5.18549 6.80175 5.4566C7.0973 5.7277 7.54064 5.7277 7.8362 5.4566L9.46177 3.96552C10.3484 3.1522 11.8262 3.1522 12.7129 3.96552C13.1562 4.37218 13.3779 4.91439 13.3779 5.4566C13.3779 5.99881 13.1562 6.54102 12.7129 6.94768L11.0873 8.43876C10.7918 8.70987 10.7918 9.11653 11.0873 9.38763C11.2351 9.52319 11.4568 9.59096 11.6046 9.59096C11.7523 9.59096 11.974 9.52319 12.1218 9.38763L13.7474 7.89655C14.4862 7.21879 14.8557 6.40547 14.8557 5.4566C14.8557 4.50773 14.4862 3.69441 13.7474 3.01665C13.0085 2.33888 12.1218 2 11.0873 2Z" fill="#575362" stroke="white" stroke-width="0.327586"/>
					<path d="M5.76836 7.4221C6.06392 7.15099 6.06392 6.74433 5.76836 6.47323C5.4728 6.20212 5.02947 6.20212 4.73391 6.47323L3.10834 7.89653C2.36945 8.5743 2 9.38761 2 10.3365C2 11.2854 2.36945 12.0987 3.10834 12.7764C3.84724 13.4542 4.73391 13.7931 5.76836 13.7931C6.80281 13.7931 7.68949 13.4542 8.42838 12.7764L10.054 11.2854C10.3495 11.0142 10.3495 10.6076 10.054 10.3365C9.75839 10.0654 9.31506 10.0654 9.0195 10.3365L7.39393 11.8276C6.50726 12.6409 5.02947 12.6409 4.14279 11.8276C3.69946 11.4209 3.47779 10.8787 3.47779 10.3365C3.47779 9.79427 3.69946 9.25206 4.14279 8.8454L5.76836 7.4221Z" fill="#575362" stroke="white" stroke-width="0.327586"/>
					<path d="M6.28612 9.86212C6.4339 9.99767 6.65557 10.0654 6.80335 10.0654C6.95113 10.0654 7.17279 9.99767 7.32057 9.86212L10.4978 6.94773C10.7934 6.67663 10.7934 6.26997 10.4978 5.99886C10.2023 5.72776 9.75893 5.72776 9.46337 5.99886L6.28612 8.91325C5.99056 9.11658 5.99056 9.59101 6.28612 9.86212Z" fill="#575362" stroke="white" stroke-width="0.327586"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.link_only',
					'label' => __( 'Post link only', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15.7244 3C14.2761 3 13.0348 3.51724 12.0003 4.55172L9.72441 6.82759C9.31061 7.24138 9.31061 7.86207 9.72441 8.27586C10.1382 8.68965 10.7589 8.68965 11.1727 8.27586L13.4485 6C14.6899 4.75862 16.7589 4.75862 18.0003 6C18.621 6.62069 18.9313 7.44827 18.9313 8.27586C18.9313 9.10345 18.621 9.93103 18.0003 10.5517L15.7244 12.8276C15.3106 13.2414 15.3106 13.8621 15.7244 14.2759C15.9313 14.4828 16.2416 14.5862 16.4485 14.5862C16.6554 14.5862 16.9658 14.4828 17.1727 14.2759L19.4485 12C20.483 10.9655 21.0003 9.72414 21.0003 8.27586C21.0003 6.82758 20.483 5.58621 19.4485 4.55172C18.4141 3.51724 17.1727 3 15.7244 3Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M8.27586 11.2758C8.68965 10.862 8.68965 10.2413 8.27586 9.82756C7.86207 9.41376 7.24138 9.41376 6.82759 9.82756L4.55172 12C3.51724 13.0345 3 14.2758 3 15.7241C3 17.1724 3.51724 18.4138 4.55172 19.4482C5.58621 20.4827 6.82758 21 8.27586 21C9.72414 21 10.9655 20.4827 12 19.4482L14.2759 17.1724C14.6897 16.7586 14.6897 16.1379 14.2759 15.7241C13.8621 15.3103 13.2414 15.3103 12.8276 15.7241L10.5517 18C9.31034 19.2413 7.24138 19.2413 6 18C5.37931 17.3793 5.06897 16.5517 5.06897 15.7241C5.06897 14.8965 5.37931 14.0689 6 13.4482L8.27586 11.2758Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M8.9998 15C9.20669 15.2069 9.51704 15.3103 9.72394 15.3103C9.93083 15.3103 10.2412 15.2069 10.4481 15L14.8963 10.5517C15.3101 10.1379 15.3101 9.51723 14.8963 9.10344C14.4826 8.68964 13.8619 8.68964 13.4481 9.10344L8.9998 13.5517C8.586 13.8621 8.586 14.5862 8.9998 15Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.full',
					'label' => __( 'Post full', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g clip-path="url(#clip0_655_5005)">
					<path d="M23.6431 3.78564H1.35742M23.6431 8.9285H1.35742M23.6431 14.0714H1.35742M13.4196 19.2142H1.35742" stroke="#575362" stroke-width="1.71429" stroke-linecap="round" stroke-linejoin="round"/>
					</g>
					<defs>
					<clipPath id="clip0_655_5005">
					<rect width="24" height="24" fill="white"/>
					</clipPath>
					</defs>
					</svg>',
				),
				array( 
					'keyword'  => 'post.cats',
					'label' => __( 'Post categories', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M9.05219 10.6688H4.67273C4.22931 10.6684 3.80416 10.4921 3.49057 10.1786C3.17697 9.86516 3.00055 9.44011 3 8.99673V4.67206C3.00055 4.22868 3.17697 3.80363 3.49057 3.49016C3.80416 3.17669 4.22931 3.00041 4.67273 3H9.05219C9.49552 3.00055 9.92053 3.17689 10.234 3.49034C10.5475 3.80379 10.7239 4.22877 10.7244 4.67206V8.99673C10.7239 9.44002 10.5475 9.865 10.234 10.1784C9.92053 10.4919 9.49552 10.6682 9.05219 10.6688ZM4.67273 4.29497C4.5727 4.2951 4.47681 4.33486 4.40603 4.40553C4.33525 4.47621 4.29536 4.57204 4.29508 4.67206V8.99673C4.29536 9.09675 4.33525 9.19258 4.40603 9.26326C4.47681 9.33393 4.5727 9.37369 4.67273 9.37382H9.05219C9.15217 9.37369 9.24801 9.33391 9.31871 9.26322C9.3894 9.19253 9.42918 9.0967 9.42932 8.99673V4.67206C9.42918 4.57209 9.3894 4.47625 9.31871 4.40557C9.24801 4.33488 9.15217 4.2951 9.05219 4.29497H4.67273Z" fill="#575362"/>
					<path d="M9.20258 21H4.82312C4.37965 20.9994 3.95451 20.823 3.64093 20.5095C3.32735 20.1959 3.15094 19.7708 3.15039 19.3274V15.0032C3.15094 14.5599 3.32737 14.1348 3.64096 13.8213C3.95455 13.5079 4.3797 13.3316 4.82312 13.3312H9.20258C9.64573 13.332 10.0705 13.5085 10.3837 13.8219C10.697 14.1353 10.8732 14.5601 10.8738 15.0032V19.3274C10.8733 19.7706 10.6972 20.1955 10.3839 20.5091C10.0706 20.8226 9.64582 20.9991 9.20258 21ZM4.82312 14.6261C4.7231 14.6263 4.6272 14.666 4.55642 14.7367C4.48564 14.8074 4.44575 14.9032 4.44547 15.0032V19.3274C4.44561 19.4275 4.48544 19.5235 4.55624 19.5942C4.62703 19.665 4.72301 19.7049 4.82312 19.705H9.20258C9.3026 19.7047 9.39845 19.6648 9.46913 19.5941C9.53981 19.5233 9.57957 19.4274 9.57971 19.3274V15.0032C9.57957 14.9033 9.53979 14.8074 9.4691 14.7367C9.3984 14.6661 9.30256 14.6263 9.20258 14.6261H4.82312Z" fill="#575362"/>
					<path d="M19.3295 21H14.9501C14.5066 20.9994 14.0815 20.823 13.7679 20.5095C13.4543 20.1959 13.2779 19.7708 13.2773 19.3274V15.0032C13.2779 14.5599 13.4543 14.1348 13.7679 13.8213C14.0815 13.5079 14.5067 13.3316 14.9501 13.3312H19.3295C19.7729 13.3317 20.1979 13.5081 20.5114 13.8215C20.8248 14.135 21.0012 14.5599 21.0017 15.0032V19.3274C21.0013 19.7708 20.825 20.1959 20.5115 20.5094C20.198 20.823 19.773 20.9994 19.3295 21ZM14.9501 14.6287C14.85 14.6289 14.7541 14.6686 14.6834 14.7393C14.6126 14.81 14.5727 14.9058 14.5724 15.0058V19.33C14.5726 19.4301 14.6124 19.5261 14.6832 19.5968C14.754 19.6676 14.85 19.7075 14.9501 19.7076H19.3295C19.4296 19.7073 19.5254 19.6674 19.5961 19.5967C19.6668 19.5259 19.7065 19.43 19.7067 19.33V15.0032C19.7065 14.9033 19.6667 14.8074 19.596 14.7367C19.5254 14.6661 19.4295 14.6263 19.3295 14.6261L14.9501 14.6287Z" fill="#575362"/>
					<path d="M17.1428 10.7983C16.3859 10.7927 15.6476 10.5631 15.0209 10.1385C14.3943 9.71391 13.9074 9.11331 13.6216 8.41243C13.3358 7.71154 13.2639 6.94175 13.4151 6.20009C13.5662 5.45842 13.9335 4.7781 14.4707 4.24488C15.008 3.71165 15.6911 3.34941 16.4339 3.2038C17.1767 3.05819 17.946 3.13575 18.6448 3.42668C19.3437 3.71762 19.9407 4.20891 20.3607 4.83863C20.7806 5.46835 21.0048 6.2083 21.0048 6.9652C20.9997 7.98517 20.5903 8.96151 19.8663 9.68007C19.1424 10.3986 18.1629 10.8008 17.1428 10.7983ZM17.1428 4.42706C16.6421 4.43269 16.1541 4.58631 15.7405 4.8686C15.3268 5.15089 15.0059 5.54922 14.8181 6.01347C14.6304 6.47772 14.5841 6.98714 14.6853 7.4776C14.7864 7.96805 15.0304 8.41764 15.3865 8.76976C15.7426 9.12188 16.1949 9.3608 16.6865 9.45644C17.1781 9.55209 17.687 9.50019 18.1492 9.30728C18.6114 9.11436 19.0061 8.78906 19.2838 8.3723C19.5615 7.95555 19.7097 7.46597 19.7097 6.9652C19.7056 6.28813 19.433 5.64035 18.9518 5.16402C18.4706 4.68769 17.82 4.42172 17.1428 4.42447V4.42706Z" fill="#575362"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.more_tag',
					'label' => __( 'Post more tag', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<line x1="2" y1="9.25" x2="22" y2="9.25" stroke="#575362" stroke-width="1.5"/>
					<line x1="2" y1="13.5" x2="22" y2="13.5" stroke="#575362" stroke-dasharray="2.54 2.54"/>
					</svg>
					',
				),
				array( 
					'keyword'  => 'post.image_url',
					'label' => __( 'Post image URL', 'email-subscribers' ),
					'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15.7244 3C14.2761 3 13.0348 3.51724 12.0003 4.55172L9.72441 6.82759C9.31061 7.24138 9.31061 7.86207 9.72441 8.27586C10.1382 8.68965 10.7589 8.68965 11.1727 8.27586L13.4485 6C14.6899 4.75862 16.7589 4.75862 18.0003 6C18.621 6.62069 18.9313 7.44827 18.9313 8.27586C18.9313 9.10345 18.621 9.93103 18.0003 10.5517L15.7244 12.8276C15.3106 13.2414 15.3106 13.8621 15.7244 14.2759C15.9313 14.4828 16.2416 14.5862 16.4485 14.5862C16.6554 14.5862 16.9658 14.4828 17.1727 14.2759L19.4485 12C20.483 10.9655 21.0003 9.72414 21.0003 8.27586C21.0003 6.82758 20.483 5.58621 19.4485 4.55172C18.4141 3.51724 17.1727 3 15.7244 3Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M8.27586 11.2758C8.68965 10.862 8.68965 10.2413 8.27586 9.82756C7.86207 9.41376 7.24138 9.41376 6.82759 9.82756L4.55172 12C3.51724 13.0345 3 14.2758 3 15.7241C3 17.1724 3.51724 18.4138 4.55172 19.4482C5.58621 20.4827 6.82758 21 8.27586 21C9.72414 21 10.9655 20.4827 12 19.4482L14.2759 17.1724C14.6897 16.7586 14.6897 16.1379 14.2759 15.7241C13.8621 15.3103 13.2414 15.3103 12.8276 15.7241L10.5517 18C9.31034 19.2413 7.24138 19.2413 6 18C5.37931 17.3793 5.06897 16.5517 5.06897 15.7241C5.06897 14.8965 5.37931 14.0689 6 13.4482L8.27586 11.2758Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					<path d="M8.9998 15C9.20669 15.2069 9.51704 15.3103 9.72394 15.3103C9.93083 15.3103 10.2412 15.2069 10.4481 15L14.8963 10.5517C15.3101 10.1379 15.3101 9.51723 14.8963 9.10344C14.4826 8.68964 13.8619 8.68964 13.4481 9.10344L8.9998 13.5517C8.586 13.8621 8.586 14.5862 8.9998 15Z" fill="#575362" stroke="white" stroke-width="0.5"/>
					</svg>
					',
					'description' => __( 'Show a post image URL', 'email-subscribers' ),
				),
			);

			$campaign_tags = array(
				'post_notification' => $post_notification_tags,
			);

			return apply_filters( 'ig_es_dnd_campaign_tags', $campaign_tags );
		}

		public function get_dnd_subscriber_tags() {
			$subscriber_tags = array(
				array(
					'keyword' => 'subscriber.name',
					'label'   => __( 'Name', 'email-subscribers' ),
				),
				array(
					'keyword' => 'subscriber.first_name',
					'label'   => __( 'First name', 'email-subscribers' ),
				),
				array(
					'keyword' => 'subscriber.last_name',
					'label'   => __( 'Last name', 'email-subscribers' ),
				),
				array(
					'keyword' => 'subscriber.email',
					'label'   => __( 'Email', 'email-subscribers' ),
				),
			);

			return apply_filters( 'ig_es_dnd_subscriber_tags', $subscriber_tags );
		}

		public function get_dnd_site_tags() {
			$site_tags = array(
				array(
					'keyword' => 'site.name',
					'label'   => __( 'Name', 'email-subscribers' ),
				),
				array(
					'keyword' => 'site.url',
					'label'   => __( 'URL', 'email-subscribers' ),
				),
				array(
					'keyword' => 'site.total_contacts',
					'label'   => __( 'Total contacts', 'email-subscribers' ),
				),
			);

			return apply_filters( 'ig_es_dnd_site_tags', $site_tags );
		}

		public function show_merge_tags( $campaign_type, $target_elem_id ) {
			$subscriber_tags = $this->get_subscriber_tags();
			?>
			<div class="merge-tags-wrapper" data-target-elem-id="<?php echo esc_attr( $target_elem_id ); ?>">
			<?php
			if ( ! empty( $subscriber_tags ) ) {
				?>
				<div id="ig-es-subscriber-tags" class="pt-2">
					<?php
						$this->render_merge_tags( $subscriber_tags );
					?>
				</div>
				<?php
			}
			$site_tags = $this->get_site_tags();
			if ( ! empty( $site_tags ) ) {
				?>
				<div id="ig-es-site-tags" class="pt-2">
					<?php
						$this->render_merge_tags( $site_tags );
					?>
				</div>
				<?php
			}
			$campaign_tags = $this->get_campaign_tags();
			if ( ! empty( $campaign_tags ) ) {
				?>
				<div id="ig-es-campaign-tags" class="pt-2">
				<?php foreach ($campaign_tags as $type => $tags ) : ?>
					<?php
						$class = $type !== $campaign_type ? 'hidden' : '';
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
			?>
			</div>
			<?php
		}

		public function render_merge_tags( $merge_tags = array() ) {
			if ( empty( $merge_tags ) ) {
				return;
			}

			$subscriber_tags = $this->get_subscriber_tags();
			$site_tags 		 = $this->get_site_tags();
			$campaign_tags   = $this->get_campaign_tags();

			if ( $merge_tags === $campaign_tags['post_notification'] ) {
				?>
				<b class="pl-2 pb-2"><?php echo esc_html__( 'Post', 'email-subscribers' ); ?></b>
				<?php
			} elseif ( ! empty( $campaign_tags['post_digest'] ) && $merge_tags === $campaign_tags['post_digest'] ) {
				?>
				<b class="pl-2 pb-2"><?php echo esc_html__( 'Post', 'email-subscribers' ); ?></b>
				<?php
			} elseif ( $merge_tags === $subscriber_tags ) {
				?>
				<b class="pl-2 pb-2"><?php echo esc_html__( 'Subscriber', 'email-subscribers' ); ?></b>
				<?php
			} elseif ( $merge_tags === $site_tags ) {
				?>
				<b class="pl-2 pb-2"><?php echo esc_html__( 'Site', 'email-subscribers' ); ?></b>
				<?php
			}

			foreach ( $merge_tags as $tag_key => $tag ) {				
				?>
				<span data-tag-text="<?php echo is_string( $tag_key ) ? esc_attr( $tag ) : ''; ?>" class="ig-es-merge-tag cursor-pointer block px-2 pl-5 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900">
					<?php echo is_string( $tag_key ) ? esc_html( $tag_key ) : esc_html( $tag ); ?>
				</span>
				<?php
			}
			?>
			<hr>
			<?php
		}


		/**
		 * Method to display newsletter setting form
		 *
		 * @param array $campaign_data Posted campaign data
		 *
		 * @since  4.4.2 Added $campaign_data param
		 * 
		 * @modify 5.6.4
		 */
		public function show_campaign_form( $message_data = array() ) {

			$from_email = ES_Common::get_ig_option( 'from_email' );

			$campaign_data = $this->campaign_data;

			$campaign_id        = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
			$campaign_from_name = ! empty( $campaign_data['from_name'] ) ? $campaign_data['from_name'] : get_option( 'ig_es_from_name' );
			$campaign_email     = ! empty( $campaign_data['from_email'] ) ? $campaign_data['from_email'] : $from_email;
			$campaign_reply_to  = ! empty( $campaign_data['reply_to_email'] ) ? $campaign_data['reply_to_email'] : $from_email;
			$campaign_subject   = ! empty( $campaign_data['subject'] ) ? $campaign_data['subject'] : $this->get_campaign_default_subject();
			$campaign_status    = ! empty( $campaign_data['status'] ) ? (int) $campaign_data['status'] : IG_ES_CAMPAIGN_STATUS_IN_ACTIVE;
			$campaign_type      = ! empty( $campaign_data['type'] ) ? $campaign_data['type']               : '';
			$editor_type        = ! empty( $campaign_data['meta']['editor_type'] ) ? $campaign_data['meta']['editor_type'] : '';
			$campaign_preheader = ! empty( $campaign_data['meta']['preheader'] ) ? $campaign_data['meta']['preheader'] : '';
			$campaign_text      = '';
			$gallery_page_url   = admin_url( 'admin.php?page=es_gallery' );

			if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign_type ) {
				$campaign_text = __( 'Post notification', 'email-subscribers' );
			} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign_type ) {
				$campaign_text = __( 'Post digest', 'email-subscribers' );
			} elseif ( IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign_type ) {
				$campaign_text = __( 'Broadcast', 'email-subscribers' );
			}

			
			?>

			<div id="edit-campaign-form-container" data-editor-type="<?php echo esc_attr( $editor_type ); ?>" data-campaign-type="<?php echo esc_attr( $campaign_type ); ?>" class="<?php echo esc_attr( $editor_type ); ?> font-sans pt-1.5 wrap">
				<?php
				if ( ! empty( $message_data ) ) {
					$message = $message_data['message'];
					$type    = $message_data['type'];
					ES_Common::show_message( $message, $type );
				}
				?>
				<form action="#" method="POST" id="campaign_form">
					<input type="hidden" id="campaign_id" name="data[id]" value="<?php echo esc_attr( $campaign_id ); ?>"/>
					<input type="hidden" id="campaign_status" name="data[status]" value="<?php echo esc_attr( $campaign_status ); ?>"/>
					<input type="hidden" id="campaign_type" name="data[type]" value="<?php echo esc_attr( $campaign_type ); ?>"/>
					<input type="hidden" id="editor_type" name="data[meta][editor_type]" value="<?php echo esc_attr( $editor_type ); ?>"/>
					<?php wp_nonce_field( 'ig-es-campaign-nonce', 'ig_es_campaign_nonce' ); ?>
					<fieldset class="block es_fieldset">
						<div class="mx-auto wp-heading-inline max-w-7xl">
							<header class="mx-auto max-w-7xl">
								<div class="md:flex md:items-center md:justify-between">
									<div class="flex md:3/5 lg:w-7/12 xl:w-3/5">
										<div class=" min-w-0 md:w-3/5 lg:w-1/2">
										   <nav class="text-gray-400 my-0" aria-label="Breadcrumb">
											<ol class="list-none p-0 inline-flex">
													<li class="flex items-center text-sm tracking-wide">
														<a class="hover:underline" href="admin.php?page=es_campaigns"><?php echo esc_html__( 'Campaigns', 'email-subscribers' ); ?>
														</a>
														<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
													</li>
													<li class="flex items-center text-sm tracking-wide">
														<a href="<?php echo esc_url( $gallery_page_url ); ?>&campaign-type=<?php echo esc_attr( $campaign_type ); ?>&campaign-id=<?php echo esc_attr( $campaign_id ); ?>"><?php echo esc_html__( 'Select template'); ?></a>
														<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
													</li>
											</ol>
										   </nav>

											<h2 class="campaign-heading-label -mt-1 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate" data-post-notification-type-text="<?php echo esc_attr__( 'Post notification', 'email-subscribers' ); ?>" data-post-digest-type-text="<?php echo esc_attr__( 'Post digest', 'email-subscribers' ); ?>">
												<?php echo esc_html( $campaign_text ); ?>
											</h2>
										</div>
										<div class="flex pt-4 md:-mr-8 lg:-mr-16 xl:mr-0 md:ml-8 lg:ml-16 xl:ml-20">
											<ul class="ig-es-tabs overflow-hidden">
												<li id="campaign_content_menu" class="relative float-left px-1 pb-2 text-center list-none cursor-pointer active ">
													<span class="mt-1 text-base font-medium tracking-wide text-gray-400 active"><?php echo esc_html__( 'Content', 'email-subscribers' ); ?></span>
												</li>
												<li id="campaign_summary_menu" class="relative float-left px-1 pb-2 ml-5 text-center list-none cursor-pointer hover:border-2 ">
													<span class="mt-1 text-base font-medium tracking-wide text-gray-400"><?php echo esc_html__( 'Summary', 'email-subscribers' ); ?></span>
												</li>
											</ul>
										</div>
									</div>
									<div class="flex md:mt-0 xl:ml-4">

										<div class="inline-block text-left">
											<button id="view_campaign_preview_button" type="button"
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
											<button id="view_campaign_summary_button" type="button"
													class="inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4">
													<?php
													echo esc_html__( 'Next', 'email-subscribers' );
													?>
												<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 20 20" class="w-3 h-3 my-1 ml-2 -mr-1 text-white hover:text-white">
													<path d="M9 5l7 7-7 7"></path>
												</svg>
											</button>
										</div>

										<div id="view_campaign_content_button" class="flex hidden mt-4 md:mt-0">
											<button type="button"
													class="inline-flex justify-center w-full py-1.5 text-sm font-medium leading-5 text-indigo-600 transition duration-150 ease-in-out border border-indigo-500 rounded-md cursor-pointer select-none pre_btn md:px-1 lg:px-3 xl:px-4 hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg ">
											<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" viewBox="0 0 20 20" class="w-3 h-3 my-1 mr-1"><path d="M15 19l-7-7 7-7"></path></svg><?php echo esc_html__( 'Previous', 'email-subscribers' ); ?>
											</button>
										</div>

										<span id="campaign_summary_actions_buttons_wrapper" class="hidden md:ml-2 xl:ml-2">
											<button type="button" id="save_campaign_btn" name="ig_es_campaign_action" class="ig-es-inline-loader inline-flex justify-center w-24 py-1.5 text-sm font-medium leading-5 text-indigo-600 transition duration-150 ease-in-out border border-indigo-500 rounded-md cursor-pointer select-none pre_btn md:px-1 lg:px-3 xl:px-4 hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg" value="save">
												<span class="ig_es_campaign_send_option_text">
													<?php echo esc_html__( 'Save', 'email-subscribers' ); ?>
												</span>
												<svg class="es-btn-loader animate-spin h-4 w-4 text-indigo"
																xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
													<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
															stroke-width="4"></circle>
													<path class="opacity-75" fill="currentColor"
															d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
												</svg>
											</button>
											<?php
												do_action( 'ig_es_show_' . $campaign_type . '_campaign_summary_action_buttons', $campaign_data );
											?>
										</span>
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
													<div id="ig-es-add-tag-icon" class="merge-tags-wrapper float-right items-center" style="width:3%;">
														<span class="dashicons dashicons-tag cursor-pointer"></span>
														<div x-show="open" id="ig-es-tag-icon-dropdown" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
														 x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 z-10 hidden w-56 origin-top-right rounded-md shadow-lg">
															<div class="bg-white rounded-md shadow-xs">
																<?php
																$target_elem_id = 'ig_es_campaign_subject';
																$this->show_merge_tags( $campaign_type, $target_elem_id );
																?>
															</div>
														</div>
													</div>
													<div>
														<input id="ig_es_campaign_subject"  style="width:95%;" class="outline-none" name="data[subject]" value="<?php echo esc_attr( $campaign_subject ); ?>"/>
													</div>
													
												</div>
											</div>
											<div class="block px-4 py-2">
												<label class="text-sm font-medium leading-5 text-gray-700"><?php echo esc_html__( 'Preheader', 'email-subscribers' ); ?></label>
												<div class="w-full mt-1 relative text-sm leading-5 rounded-md shadow-sm form-input border-gray-400">
													<div>
														<input style="width:100%;"  id= "ig_es_campaign_preheader"  class="outline-none" name="data[preheader]" value="<?php echo esc_attr( $campaign_preheader ); ?>"/>
													</div>
												</div>
											</div>
											<div class="w-full px-4 pt-1 pb-2 mt-1 message-label-wrapper">
												<label for="message" class="text-sm font-medium leading-5 text-gray-700"><?php echo esc_html__( 'Message', 'email-subscribers' ); ?></label>
												<?php
												if ( IG_ES_CLASSIC_EDITOR === $editor_type ) {
													$editor_id       = 'edit-es-campaign-body';
													$editor_content  = ! empty( $campaign_data['body'] ) ? $campaign_data['body'] : $this->get_campaign_default_content();
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
													<textarea id="campaign-dnd-editor-data" name="data[meta][dnd_editor_data]" style="display:none;">
														<?php
															$dnd_editor_data     = ! empty( $campaign_data['meta']['dnd_editor_data'] ) ? $campaign_data['meta']['dnd_editor_data'] : $this->get_campaign_default_content();
															echo esc_html( $dnd_editor_data );
														?>
													</textarea>
													<script>
														jQuery(document).ready(function($){
															let editor_data = jQuery('#campaign-dnd-editor-data').val().trim();
															if ( '' !== editor_data ) {
																let is_valid_json = ig_es_is_valid_json( editor_data );
																if ( is_valid_json ) {
																	editor_data = JSON.parse( `${editor_data}` );
																}
																jQuery(document).on("es_drag_and_drop_editor_loaded",function (event) {
																	window.esVisualEditor.importMjml(editor_data);
																});
															}
															jQuery(document).on('es_drag_and_drop_editor_loaded',()=>{
																let dropdown = jQuery('#ig-es-dnd-merge-tags #ig-es-dnd-tags-dropdown').clone();
																	
																	jQuery('#ig-es-dnd-merge-tags-wrapper').append(dropdown);
																	jQuery('#ig-es-dnd-merge-tags #ig-es-dnd-tags-dropdown').remove();
																	jQuery(document).on("click", function (event) {
																		var $trigger = jQuery("#ig-es-dnd-add-merge-tag-button");
																		if ($trigger !== event.target && !$trigger.has(event.target).length) {
																			//jQuery("#ig-es-dnd-merge-tags-wrapper #ig-es-dnd-tags-dropdown").hide();
																		}
																	});

																	// Toggle Dropdown
																	jQuery('#ig-es-dnd-add-merge-tag-button').click(function () {
																		jQuery('#ig-es-dnd-merge-tags-wrapper #ig-es-dnd-tags-dropdown').toggle();
																	});
																	ig_es_add_dnd_rte_tags( '<?php echo esc_js( $campaign_type ); ?>' );
															});
														});
													</script>
													<?php
												}
												?>
											</div>
											<script>
												jQuery(document).ready(function($){
													

													<?php
													if ( IG_ES_DRAG_AND_DROP_EDITOR === $editor_type ) {
														?>
														let campaign_type = '<?php echo esc_attr( $campaign_type ); ?>';
														if ( 'newsletter' === campaign_type ) {
															window.esVisualEditor.RichTextEditor.remove('es-tags');
														}
														<?php
													}
													?>
												});
											</script>
											<?php do_action( 'ig_es_after_campaign_left_pan_settings', $campaign_data ); ?>
										</div>
										<div class="campaign_side_content ml-2 bg-gray-100 rounded-r-lg">
											<?php
												do_action( 'ig_es_before_' . $campaign_type . '_content_settings', $campaign_data );
											?>
											<?php
												do_action( 'ig_es_' . $campaign_type . '_content_settings', $campaign_data );
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

																<?php do_action( 'ig_es_campaign_preview_options_content', $campaign_data ); ?>

																<div id="campaign-preview-iframe-container" class="py-4 list-decimal popup-preview">
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

					<fieldset class="es_fieldset">

						<div class="mt-7 hidden mx-auto es_campaign_second max-w-7xl">
							<span class="ig-es-ajax-loader">
								<img src="<?php echo esc_url(ES_PLUGIN_URL); ?>lite/admin/images/spinner-2x.gif">
							</span>
							<?php
							$inline_preview_data = $this->get_campaign_inline_preview_data( $campaign_data );
							?>
							<div class="max-w-7xl">
								<div class="bg-white rounded-lg shadow md:flex">
									<div class="py-4 my-4 campaign_main_content pt-3 pl-2">
										<div class="block pb-2 mx-4">
											<span class="text-sm font-medium text-gray-500">
												<?php echo esc_html__( 'Email Content Preview', 'email-subscribers' ); ?>
											</span>
										</div>

										<div class="block pb-2 mx-4 mt-4 inline_campaign-popup-preview-container">
											<div class="block">
												<span class="text-2xl font-normal text-gray-600 campaign_preview_subject">
													<?php
														echo ! empty( $campaign_data['subject'] ) ? esc_html( $campaign_data['subject'] ) : '';
													?>
											</span>
											</div>
											<div class="block mt-3">
												<span class="text-sm font-bold text-gray-800 campaign_preview_contact_name"><?php echo ! empty( $inline_preview_data['contact_name'] ) ? esc_html( $inline_preview_data['contact_name'] ) : ''; ?></span>
												<span class="pl-1 text-sm font-medium text-gray-700 campaign_preview_contact_email"><?php echo ! empty( $inline_preview_data['contact_email'] ) ? esc_html( '&lt;' . $inline_preview_data['contact_email'] . '&gt;' ) : ''; ?></span>
											</div>
											<div class="block mt-3 campaign_preview_content"></div>
										</div>
									</div>

									<div class="campaign_side_content ml-2 bg-gray-100 rounded-r-lg">
										<div class="ig-es-campaign-sender block pt-4 pb-2 mx-4 border-b border-gray-200">
											<a id="toggle-sender-details" href="#" class="ig-es-campaign-sender-label pt-3 text-sm font-medium leading-5">
												<?php echo esc_html__( 'Sender details', 'email-subscribers' ); ?>
												<svg xmlns="http://www.w3.org/2000/svg" class="detail-hidden-icons inline-block h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
												</svg>
											</a>
											<div id="sender-details-container" style="display:none;">
												<div class="flex w-full pb-1">
													<div class="w-4/12 text-sm font-normal text-gray-600">
														<label for="from_name" class="text-sm font-medium leading-10 text-gray-700">
															<?php echo esc_html__( 'From Name', 'email-subscribers' ); ?>
														</label>
													</div>
													<div class="w-8/12">
														<input id="from_name" class="block w-full mt-1 text-sm leading-5 border-gray-400 rounded-md shadow-sm form-input" name="data[from_name]" value="<?php echo esc_attr( $campaign_from_name ); ?>"/>
													</div>
												</div>
												<div class="flex w-full pb-1">
													<div class="w-4/12 text-sm font-normal text-gray-600">
														<label for="from_email" class="text-sm font-medium leading-10 text-gray-700"><?php echo esc_html__( 'From Email', 'email-subscribers' ); ?></label>
													</div>
													<div class="w-8/12">
														<input id="from_email" class="block w-full mt-1 text-sm leading-5 border-gray-400 rounded-md shadow-sm form-input" name="data[from_email]" value="<?php echo esc_attr( $campaign_email ); ?>"/>
													</div>
												</div>
												<div class="flex w-full pb-1">
													<div class="w-4/12 text-sm font-normal text-gray-600">
														<label for="reply_to" class="text-sm font-medium leading-10 text-gray-700"><?php echo esc_html__( 'Reply To', 'email-subscribers' ); ?></label>
													</div>
													<div class="w-8/12">
														<input id="reply_to" class="block w-full mt-1 text-sm leading-5 border-gray-400 rounded-md shadow-sm form-input" name="data[reply_to_email]" value="<?php echo esc_attr( $campaign_reply_to ); ?>"/>
													</div>
												</div>
											</div>
										</div>

										<div class="ig-es-campaign-rules block pt-2 pb-4 mx-4 border-b border-gray-200">
											<span id="ig_es_total_contacts">
													<h2 class='text-sm font-normal text-gray-600'>
														<span class=""><?php echo esc_html__( 'Total recipients:', 'email-subscribers' ); ?> </span>
														<span class='text-base font-medium text-gray-700'>
															<span class='ig_es_list_contacts_count'></span>
														</span>
													</h2>
											</span>
											<?php do_action( 'ig_es_show_campaign_rules', $campaign_id, $campaign_data ); ?>
										</div>
										<?php
										do_action( 'ig_es_after_campaign_right_pan_settings', $campaign_data );
										$enable_open_tracking = ! empty( $campaign_data['meta']['enable_open_tracking'] ) ? $campaign_data['meta']['enable_open_tracking'] : get_option( 'ig_es_track_email_opens', 'yes' );
										?>
										<div class="ig-es-campaign-tracking-options pt-2 pb-4 mx-4">
											<div class="flex w-full">
												<div class="w-11/12 text-sm font-normal text-gray-600"><?php echo esc_html__( 'Open tracking', 'email-subscribers' ); ?>
												</div>
												<div>
													<label for="enable_open_tracking" class="inline-flex items-center cursor-pointer ">
													<span class="relative">
														<input id="enable_open_tracking" type="checkbox" class="absolute w-0 h-0 opacity-0 es-check-toggle"
															name="data[meta][enable_open_tracking]" value="yes"  <?php checked( $enable_open_tracking, 'yes' ); ?>/>
														<span class="block w-8 h-5 bg-gray-300 rounded-full shadow-inner es-mail-toggle-line"></span>
														<span class="absolute inset-y-0 left-0 block w-3 h-3 mt-1 ml-1 transition-all duration-300 ease-in-out bg-white rounded-full shadow es-mail-toggle-dot focus-within:shadow-outline"></span>
													</span>
													</label>
												</div>
											</div>
											<?php do_action( 'ig_es_after_campaign_tracking_options_settings', $campaign_data ); ?>
										</div>
										<?php do_action( 'ig_es_' . $campaign_type . '_scheduling_options_settings', $campaign_data ); ?>
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
		 * Get default subject for campaign
		 *
		 * @return string $default_subject
		 *
		 * @since 5.3.3
		 */
		public function get_campaign_default_subject() {
			$campaign_data   = $this->campaign_data;
			$campaign_type   = $campaign_data['type'];
			$default_subject = apply_filters( 'ig_es_' . $campaign_type . '_default_subject', '', $campaign_data );
			return $default_subject;
		}

		/**
		 * Get default content for campaign
		 *
		 * @return string $default_content
		 *
		 * @since 5.3.3
		 */
		public function get_campaign_default_content() {
			$campaign_data   = $this->campaign_data;
			$campaign_type   = $campaign_data['type'];
			$default_content = apply_filters( 'ig_es_' . $campaign_type . '_default_content', '', $campaign_data );
			return $default_content;
		}

		/**
		 * Save campaign data
		 *
		 * @param array $campaign_data
		 * @return boolean $campaign_saved
		 *
		 * @since 5.3.3
		 */
		public static function save_campaign( $campaign_data ) {
			$campaign_saved = false;
			if ( ! empty( $campaign_data['body'] ) ) {
				$campaign_id   = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
				$campaign_type = ! empty( $campaign_data['type'] ) ? $campaign_data['type'] : IG_ES_DRAG_AND_DROP_EDITOR;


				$campaign_data['name'] = $campaign_data['subject'];
				$campaign_data['slug'] = sanitize_title( sanitize_text_field( $campaign_data['name'] ) );

				$campaign_data = apply_filters( 'ig_es_campaign_data', $campaign_data );
				$campaign_data = apply_filters( 'ig_es_' . $campaign_type . '_data', $campaign_data );

				if ( ! empty( $campaign_id ) ) {

					$campaign_saved = ES()->campaigns_db->save_campaign( $campaign_data, $campaign_id );
				}
			}

			return $campaign_saved;
		}

		/**
		 * Schedule a campaign
		 *
		 * @param array $data
		 * @return string $scheduling_status
		 *
		 * @since 5.3.3
		 */
		public static function schedule_campaign( $data ) {

			$scheduling_status = '';
			if ( ! empty( $data['id'] ) ) {
				$campaign_id   = ! empty( $data['id'] ) ? $data['id'] : 0;
				$campaign_meta = ES()->campaigns_db->get_campaign_meta_by_id( $campaign_id );

				$notification = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
				$data['body'] = ES_Common::es_process_template_body( $data['body'], $data['base_template_id'], $campaign_id );

				$guid = ES_Common::generate_guid( 6 );

				$meta = apply_filters( 'ig_es_before_save_campaign_notification_meta', array( 'type' => 'newsletter' ), $campaign_meta );
				$data = array(
					'hash'        => $guid,
					'campaign_id' => $campaign_id,
					'subject'     => $data['subject'],
					'body'        => $data['body'],
					'status'      => '',
					'start_at'    => ! empty( $campaign_meta['date'] ) ? $campaign_meta['date'] : '',
					'finish_at'   => '',
					'created_at'  => ig_get_current_date_time(),
					'updated_at'  => ig_get_current_date_time(),
					'meta'        => maybe_serialize( $meta ),
				);

				$should_queue_emails = false;
				$mailing_queue_id    = 0;

				// Add notification to mailing queue if not already added.
				if ( empty( $notification ) ) {
					$data['count']       = 0;
					$mailing_queue_id    = ES_DB_Mailing_Queue::add_notification( $data );
					$mailing_queue_hash  = $guid;
					$should_queue_emails = true;
				} else {
					$mailing_queue_id    = $notification['id'];
					$mailing_queue_hash  = $notification['hash'];
					$notification_status = $notification['status'];
					// Check if notification is not sending or already sent then only update the notification.
					if ( ! in_array( $notification_status, array( 'Sending', 'Sent' ), true ) ) {
						// Don't update this data.
						$data['hash']        = $notification['hash'];
						$data['campaign_id'] = $notification['campaign_id'];
						$data['created_at']  = $notification['created_at'];

						// Check if list has been updated, if yes then we need to delete emails from existing lists and requeue the emails from the updated lists.
						$should_queue_emails = true;
						$data['count']       = 0;

						$notification = ES_DB_Mailing_Queue::update_notification( $mailing_queue_id, $data );
					}
				}

				if ( ! empty( $mailing_queue_id ) ) {
					if ( $should_queue_emails ) {
						$list_ids = '';
						// Delete existing sending queue if any already present.
						ES_DB_Sending_Queue::delete_by_mailing_queue_id( array( $mailing_queue_id ) );
						$emails_queued = ES_DB_Sending_Queue::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id, $list_ids );
						if ( $emails_queued ) {
							$scheduling_status = 'success';
						} else {
							$scheduling_status = 'emails_not_queued';
						}
					}

					$mailing_queue = ES_DB_Mailing_Queue::get_mailing_queue_by_id( $mailing_queue_id );
					if ( ! empty( $mailing_queue ) ) {

						$queue_start_at    = $mailing_queue['start_at'];
						$current_timestamp = time();
						$sending_timestamp = strtotime( $queue_start_at );
						// Check if campaign sending time has come.
						if ( ! empty( $sending_timestamp ) && $sending_timestamp <= $current_timestamp ) {
							$request_args = array(
								'action'        => 'ig_es_trigger_mailing_queue_sending',
								'campaign_hash' => $mailing_queue_hash,
							);
							// Send an asynchronous request to trigger sending of campaign emails.
							IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
						}
					}
				}
			}

			return $scheduling_status;
		}

		public function add_campaign_body_data( $campaign_data ) {

			$template_id = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : 0;
			$campaign_id = ! empty( $campaign_data['id'] ) ? $campaign_data['id'] : 0;
			if ( ! empty( $campaign_data['body'] ) ) {
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

				$campaign_body = $campaign_data['body'];
				$campaign_body = ES_Common::es_process_template_body( $campaign_body, $template_id, $campaign_id );
				$campaign_body = ES_Common::replace_keywords_with_fallback( $campaign_body, array(
					'FIRSTNAME' => $first_name,
					'NAME'      => $username,
					'LASTNAME'  => $last_name,
					'EMAIL'     => $useremail
				) );


				$subscriber_tags = array(
					'subscriber.first_name' => $first_name,
					'subscriber.name'      => $username,
					'subscriber.last_name'  => $last_name,
					'subscriber.email'     => $useremail
				);

				$custom_field_values = array();
				foreach ( $contact_data as $merge_tag_key => $merge_tag_value ) {
					if ( false !== strpos( $merge_tag_key, 'cf_' ) ) {
						$merge_tag_key_parts = explode( '_', $merge_tag_key );
						$merge_tag_key       = $merge_tag_key_parts[2];
						$custom_field_values[ 'subscriber.' . $merge_tag_key ] = $merge_tag_value;
					}
				}

				$subscriber_tags_values = array(
					'subscriber.first_name' => $first_name,
					'subscriber.name'      => $username,
					'subscriber.last_name'  => $last_name,
					'subscriber.email'     => $useremail
				);

				$subscriber_tags_values = array_merge( $subscriber_tags_values, $custom_field_values );

				$campaign_body = ES_Common::replace_keywords_with_fallback( $campaign_body, $subscriber_tags_values );

				$campaign_type = $campaign_data['type'];

				$campaign_data['body'] = $campaign_body;

				if ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign_type ) {
					$campaign_data = self::replace_post_notification_merge_tags_with_sample_post( $campaign_data );
				} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign_type ) {
					$campaign_data = self::replace_post_digest_merge_tags_with_sample_posts( $campaign_data );
				}

				$campaign_body = ! empty( $campaign_data['body'] ) ? $campaign_data['body'] : '';

				// If there are blocks in this content, we shouldn't run wpautop() on it.
				$priority = has_filter( 'the_content', 'wpautop' );

				if ( false !== $priority ) {
					// Remove wpautop to avoid p tags.
					remove_filter( 'the_content', 'wpautop', $priority );
				}

				$campaign_body = apply_filters( 'the_content', $campaign_body );

				$campaign_data['body'] = $campaign_body;

			}
			
			return $campaign_data;
		}

		/**
		 * Method to get campaign inline preview data.
		 *
		 * @param array $campaign_data Broadcast data.
		 *
		 * @return array $preview_data
		 *
		 * @since 4.4.7
		 */
		public function get_campaign_inline_preview_data( $campaign_data = array() ) {
			$list_id      = ! empty( $campaign_data['list_ids'] ) ? $campaign_data['list_ids'] : 0;
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

			$preview_data['campaign_subject'] = ! empty( $campaign_data['subject'] ) ? wp_strip_all_tags( $campaign_data['subject'] ) : '';
			$preview_data['contact_name']     = esc_html( $first_name . ' ' . $last_name );
			$preview_data['contact_email']    = esc_html( $email );

			return $preview_data;
		}

		public static function replace_post_notification_merge_tags_with_sample_post( $campaign_data ) {

			if ( ! empty( $campaign_data['id'] ) ) {

				$args         = array(
					'numberposts' => '1',
					'order'       => 'DESC',
					'post_status' => 'publish',
				);
				$recent_posts = wp_get_recent_posts( $args, OBJECT );

				if ( count( $recent_posts ) > 0 ) {
					$post = array_shift( $recent_posts );

					$post_id          = $post->ID;
					$template_id      = $campaign_data['id'];
					$campaign_body    = ! empty( $campaign_data['body'] ) ? $campaign_data['body'] : '';
					$campaign_subject = ! empty( $campaign_data['subject'] ) ? $campaign_data['subject'] : '';

					$campaign_subject = ES_Handle_Post_Notification::prepare_subject( $campaign_subject, $post );
					$campaign_body    = ES_Handle_Post_Notification::prepare_body( $campaign_body, $post_id, $template_id );

					$campaign_data['subject'] = $campaign_subject;
					$campaign_data['body']    = $campaign_body;
				}
			}

			return $campaign_data;
		}

		public static function replace_post_digest_merge_tags_with_sample_posts( $campaign_data ) {

			if ( ! empty( $campaign_data['id'] ) && class_exists( 'ES_Post_Digest' ) ) {
				$ignore_stored_post_ids = true;
				$ignore_last_run        = true;
				$campaign_id 			= $campaign_data['id'];
				$campaign_body 			= $campaign_data['body'];
				$post_ids               = ES_Post_Digest::get_matching_post_ids( $campaign_id, $ignore_stored_post_ids, $ignore_last_run );
				$campaign_body          = ES_Post_Digest::process_post_digest_template( $campaign_body, $post_ids );
				$campaign_data['body']  = $campaign_body;
			}

			return $campaign_data;
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
					<span style="font-size: 0.8em; margin-left: 0.3em; padding: 2px; background: #e66060; color: #fff; border-radius: 2px; ">MAX</span>&nbsp;
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
	}

}

ES_Campaign_Admin::get_instance();
