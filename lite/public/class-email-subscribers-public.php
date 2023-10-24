<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/public
 */
class Email_Subscribers_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0
	 * @var      string $email_subscribers The ID of this plugin.
	 */
	private $email_subscribers;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $email_subscribers The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0
	 *
	 */
	public function __construct( $email_subscribers, $version ) {

		$this->email_subscribers = $email_subscribers;
		$this->version           = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    4.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Email_Subscribers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Email_Subscribers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'css/email-subscribers-public.css', array(), $this->version, 'all' );
		wp_register_style( 'ig-es-popup-frontend', plugin_dir_url( __FILE__ ) . 'css/frontend.css', array(), $this->version, 'all' );
		wp_register_style( 'ig-es-popup-css', plugin_dir_url( __FILE__ ) . 'css/popup.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    4.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Email_Subscribers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Email_Subscribers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */


		wp_enqueue_script( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'js/email-subscribers-public.js', array( 'jquery' ), $this->version, true );
		wp_register_script( 'ig-es-pre-data', plugin_dir_url( __FILE__ ) . 'js/icegram_messages_data.js', array(), $this->version, false );
		wp_register_script( 'ig-es-popup-js', plugin_dir_url( __FILE__ ) . 'js/icegram.js', array( 'jquery', 'ig-es-pre-data' ), $this->version, false );

		$es_data = array(

			'messages' => array(
				'es_empty_email_notice'           => __( 'Please enter email address', 'email-subscribers' ),
				'es_rate_limit_notice'            => __( 'You need to wait for some time before subscribing again', 'email-subscribers' ),
				'es_single_optin_success_message' => __( 'Successfully Subscribed.', 'email-subscribers' ),
				// 'es_double_optin_success_message' => __( 'Your subscription was successful! Kindly check your mailbox and confirm your subscription. If you don\'t see the email within a few minutes, check the spam/junk folder.', 'email-subscribers' ),
				'es_email_exists_notice'          => __( 'Email Address already exists!', 'email-subscribers' ),
				'es_unexpected_error_notice'      => __( 'Oops.. Unexpected error occurred.', 'email-subscribers' ),
				'es_invalid_email_notice'         => __( 'Invalid email address', 'email-subscribers' ),
				'es_try_later_notice'             => __( 'Please try after some time', 'email-subscribers' )
			),

			'es_ajax_url' => admin_url( 'admin-ajax.php' ),

		);

		wp_localize_script( $this->email_subscribers, 'es_data', $es_data );


	}

	public function es_email_subscribe_init() {
		global $wpdb, $ig_es_tracker;
		//initialize
		new ES_Handle_Subscription();
		new ES_Shortcode();

		$option = ig_es_get_request_data( 'es' );
		$hash   = ig_es_get_request_data( 'hash' );

		if ( ! empty( $hash ) ) {

			$data        = ig_es_decode_request_data( $hash );
			$db_id       = ! empty( $data['contact_id'] ) ? (int) $data['contact_id'] : 0;
			$email       = ! empty( $data['email'] ) ? $data['email'] : '';
			$guid        = ! empty( $data['guid'] ) ? $data['guid'] : '';
			$message_id  = ! empty( $data['message_id'] ) ? (int) $data['message_id'] : 0;
			$campaign_id = ! empty( $data['campaign_id'] ) ? (int) $data['campaign_id'] : 0;
			$list_ids 	 = ! empty( $data['list_ids'] ) ? $data['list_ids'] : '';
		} else {
			$db_id       = ig_es_get_request_data( 'db' );
			$email       = ig_es_get_request_data( 'email' );
			$guid        = ig_es_get_request_data( 'guid' );
			$message_id  = 0;
			$campaign_id = 0;
		}

		
		if ( ! empty( $option ) ) {
			$email = sanitize_email( $email );
			$email = str_replace( ' ', '+', $email );
			if ( ( 'optin' === $option || 'unsubscribe' === $option ) && ! empty( $db_id ) ) {
				//check if contact exist with id and email
				$is_contact_exists = ES()->contacts_db->is_contact_exists( $db_id, $email );

				if ( $is_contact_exists ) {
					$ids                       = array( $db_id );
					$status                    = '';
					$subject                   = '';
					$content                   = '';
					$unsubscribed              = 0;
					$status                    = ( 'optin' === $option ) ? 'subscribed': 'unsubscribed';
					$is_status_update_required = ES()->lists_contacts_db->is_status_update_required( $ids, $status );

					if ( $is_status_update_required ) {
						if ( 'optin' === $option ) {
							$message = get_option( 'ig_es_subscription_success_message' );
							ES()->contacts_db->edit_contact_global_status( $ids, $unsubscribed );
							ES()->lists_contacts_db->edit_subscriber_status( $ids, $status, $list_ids  );
							//send welcome email
							$contact = ES()->contacts_db->get_contacts_email_name_map( array( $email ) );
							$data    = array(
								'name'       => ! empty( $contact[ $email ] ) ? $contact[ $email ]['name'] : '',
								'first_name' => ! empty( $contact[ $email ] ) ? $contact[ $email ]['first_name'] : '',
								'last_name'  => ! empty( $contact[ $email ] ) ? $contact[ $email ]['last_name'] : '',
								'email'      => $email,
								'contact_id' => $db_id,
								'guid'       => $guid,
								'list_ids'   => $list_ids,
							);

							$lists     = ES()->lists_db->get_all_lists_name_by_contact( $db_id );
							$list_name = implode( ', ', $lists );

							$data['list_name'] = $list_name;

							do_action( 'ig_es_contact_subscribed', $data );

						} elseif ( 'unsubscribe' === $option ) {
							$unsubscribed = 1;

							$submitted         = '';
							$unsubscribe_lists = array();
							$list_selected     = ig_es_get_request_data( 'list_selected' );

							// Check if nonce value is not empty.
							if ( ! empty( $_POST['ig_es_unsubscribe_nonce'] ) ) {
								// Verify nonce value.
								if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ig_es_unsubscribe_nonce'] ) ), 'ig-es-unsubscribe-nonce' ) ) {
									$submitted = ig_es_get_data( $_POST, 'submitted', '', true );
									if ( ! empty( $submitted ) ) {
										$unsubscribe_lists = ig_es_get_data( $_POST, 'unsubscribe_lists', array() );
									}
								} else {
									echo esc_html__( 'Sorry, you are not allowed to access this page.', 'email-subscribers' );
									die();
								}
							} elseif ( ! empty( $_POST['List-Unsubscribe'] ) && 'One-Click' === $_POST['List-Unsubscribe'] ) {
								$unsubscribe_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $db_id, 'subscribed' );
							}

							$message = get_option( 'ig_es_unsubscribe_success_message' );

							if ( ES()->is_starter() && empty( $submitted ) && empty( $unsubscribe_lists ) && ! $list_selected ) {
								do_action( 'ig_es_update_subscriber', $db_id );
							}

							if ( empty( $unsubscribe_lists ) ) {
								// We don't get any lists to unsubscribe. Which means we have to
								// ask contact for confirmation about unsubscription
								// If we haven't received confirmation about unsubscription,
								// Show confirmation message
								$confirm_unsubscription = ig_es_get_request_data( 'confirm_unsubscription' );
								if ( empty( $submitted ) && ! $confirm_unsubscription ) {
									do_action( 'ig_es_confirm_unsubscription' );
								}

								$unsubscribe_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $db_id, 'subscribed' );
							}

							// Confirm if there are lists to unsubscribe before we unsubscribe the contact.
							if ( ! empty( $unsubscribe_lists ) ) {
								//update list status
								ES()->contacts_db->edit_list_contact_status( array( $db_id ), $unsubscribe_lists, 'unsubscribed' );
							}

							//check if all list have same status
							$list_ids = ES()->lists_contacts_db->get_list_ids_by_contact( $db_id, 'subscribed' );
							if ( count( $list_ids ) == 0 ) {
								//update global
								ES()->contacts_db->edit_contact_global_status( array( $db_id ), 1 );
							}

							do_action( 'ig_es_contact_unsubscribe', $db_id, $message_id, $campaign_id, $unsubscribe_lists );

						}

						do_action( 'es_redirect_to_optin_page', $option );
					} else {
						if ( 'subscribed' === $status ) {
							$message = __( 'You are already subscribed!', 'email-subscribers' );
						} else {
							$message = __( 'You are already unsubscribed!', 'email-subscribers' );
						}
					}

				} else {
					$message = __( 'Sorry, we couldn\'t find you. Please contact admin.', 'email-subscribers' );
				}

				// We are using $message in following file
				include 'partials/subscription-successfull.php';

			} elseif ( in_array( $option, array( 'viewstatus', 'open' ) ) ) {
				if ( ! empty( $guid ) && ! empty( $email ) ) {

					if ( $campaign_id > 0 && $db_id > 0 ) {
						do_action( 'ig_es_message_open', $db_id, $message_id, $campaign_id );
					}

				}
			} elseif ( 'click' === $option ) {

				if ( ! empty( $data['link_hash'] ) ) {
					$hash = $data['link_hash'];
					$link = ES()->links_db->get_by_hash( $hash );

					if ( ! empty( $link ) ) {
						$campaign_id = ! empty( $link['campaign_id'] ) ? $link['campaign_id'] : 0;
						$message_id  = ! empty( $link['message_id'] ) ? $link['message_id'] : 0;
						$contact_id  = ! empty( $data['contact_id'] ) ? $data['contact_id'] : 0;
						$link_id     = ! empty( $link['id'] ) ? $link['id'] : 0;

						// Track Link Click
						do_action( 'ig_es_message_click', $link_id, $contact_id, $message_id, $campaign_id );

						$redirect_link = htmlspecialchars_decode( $link['link'] );
						// Now, redirect to target
						wp_redirect( $redirect_link );
						exit;
					}
				}

			} elseif ( 'survey' === $option ) {
				if ( ! empty( $data['survey_number'] ) ) {
					$campaign_id   = $data['campaign_id'];
					$message_id    = $data['message_id'];
					$survey_number = $data['survey_number'];
					if ( ! empty( $survey_number ) ) {
						if ( ! empty( $message_id ) ) {
							$notification = ES_DB_Mailing_Queue::get_mailing_queue_by_id( $message_id );
							if ( ! empty( $notification ) ) {
								$notificaion_meta = maybe_unserialize( $notification['meta'] );
								$survey           = ! empty( $notificaion_meta['survey'] ) ? $notificaion_meta['survey'] : array();
								$message          = $survey[$survey_number]['message'];
							}
						} elseif ( ! empty( $campaign_id ) ) {
							$campaign = ES()->campaigns_db->get( $campaign_id );
							if ( ! empty( $campaign ) ) {
								$campaign_meta    = maybe_unserialize( $campaign['meta'] );
								$survey           = ! empty( $campaign_meta['survey'] ) ? $campaign_meta['survey'] : array();
								$message          = $survey[$survey_number]['message'];
							}
						}
						include 'partials/subscription-successfull.php';
					}
				}
			}

		}

	}

	public function add_contact( $contact_data, $list_id ) {

		$email = $contact_data['email'];


		$user_list_status = isset( $contact_data['user_list_status'] ) ? $contact_data['user_list_status'] : 'subscribed' ;

		$default_data = array(
			'status'     => 'verified',
			'hash'       => ES_Common::generate_guid(),
			'created_at' => ig_get_current_date_time(),
			'wp_user_id' => 0
		);

		$contact_data = wp_parse_args( $contact_data, $default_data );

		$contact_data = apply_filters( 'ig_es_add_subscriber_data', $contact_data );

		// Return if contact status has an error.
		if ( ! empty( $contact_data['status'] ) && 'ERROR' === $contact_data['status'] ) {
			return;
		}

		$contact = ES()->contacts_db->is_contact_exist_in_list( $email, $list_id );

		if ( empty( $contact['contact_id'] ) ) {
			$contact_id = ES()->contacts_db->insert( $contact_data );
		} else {
			$contact_id = $contact['contact_id'];
		}

		$optin_type        = get_option( 'ig_es_optin_type', true );
		$optin_type        = ( 'double_opt_in' === $optin_type ) ? 2 : 1;
		$list_id           = ! empty( $list_id ) ? $list_id : 1;
		$list_contact_data = array(
			'contact_id'    => $contact_id,
			'status'        => $user_list_status,
			'subscribed_at' => ig_get_current_date_time(),
			'optin_type'    => $optin_type,
			'subscribed_ip' => '',
		);

		ES()->lists_contacts_db->remove_contacts_from_lists( $contact_id, $list_id );

		ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $list_id );

	}

	/**
	 * Allow user to select the list from which they want to unsubscribe
	 *
	 * @since 4.2
	 */
	public function confirm_unsubscription() {
		global $wp;
		$get    = ig_es_get_request_data();
		$action = home_url( add_query_arg( $get, $wp->request ) );
		$action = add_query_arg( 'confirm_unsubscription', 1, $action );
		$hash   = ig_es_get_request_data( 'hash' );

		if ( ! empty( $hash ) ) {
			$data  = ig_es_decode_request_data( $hash );
			$email = ! empty( $data['email'] ) ? $data['email']                  : '';
		}

		wp_register_style( 'tailwind', ES_PLUGIN_URL . 'lite/admin/dist/main.css', array(), $this->version, 'all' );
		$es_wp_styles = wp_styles();
		$site_name    = get_bloginfo( 'name' );
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title><?php echo esc_html__( 'Unsubscribe', 'email-subscribers' ); ?> - <?php echo esc_html( $site_name ); ?></title>
				<?php
					$es_wp_styles->do_item( 'tailwind' );
				?>
				<style type="text/css">
					.ig_es_form_wrapper {
						width: 30%;
						margin: 0 auto;
						border: 2px #e8e3e3 solid;
						padding: 0.9em;
						border-radius: 5px;
					}

					.ig_es_form_heading {
						font-size: 1.3em;
						line-height: 1.5em;
						margin-bottom: 0.25em;
					}

					.ig_es_list_checkbox {
						margin-right: 0.5em;
					}

					.ig_es_submit {
						color: #FFFFFF !important;
						border-color: #03a025 !important;
						background: #03a025 !important;
						box-shadow: 0 1px 0 #03a025;
						font-weight: bold;
						height: 2.4em;
						line-height: 1em;
						cursor: pointer;
						border-width: 1px;
						border-style: solid;
						-webkit-appearance: none;
						border-radius: 3px;
						white-space: nowrap;
						box-sizing: border-box;
						font-size: 1em;
						padding: 0 2em;
					}

					.confirmation-no {
						border-color: #FF0000 !important;
						background: #FF0000 !important;
						box-shadow: 0 1px 0 #FF0000;
					}

					.ig_es_submit:hover {
						color: #FFF !important;
						background: #0AAB2E !important;
						border-color: #0AAB2E !important;
					}

					.ig_es_form_wrapper hr {
						display: block;
						height: 1px;
						border: 0;
						border-top: 1px solid #ccc;
						margin: 1em 0;
						padding: 0;
					}

				</style>
			</head>
			<body>
				<div class="min-h-screen px-4 pt-10 pb-12 mx-auto bg-gray-100 sm:px-6 lg:px-8">
					<section class="bg-white mt-12 py-7 shadow-md sm:rounded-lg mx-auto sm:w-2/4 xl:w-6/12">
						<div class="flex">
							<div class="w-full pl-6 pr-6 leading-6">
								<form action="<?php echo esc_attr( $action ); ?>" method="post" id="">
									<?php wp_nonce_field( 'ig-es-unsubscribe-nonce', 'ig_es_unsubscribe_nonce' ); ?>
									<?php
									do_action( 'ig_es_unsubscribe_form_after_start' );
									?>
									<?php
									if ( ! empty( $email ) ) {
										?>
										<div class="ig_es_unsubscribe_header text-center pb-3 border-b border-gry-150">
											<span class="block text-xl font-medium text-gray-600"><?php echo esc_html( $email ); ?></span>
												<span>
												<?php
													echo esc_html__( 'is subscribed to our mailing list(s).', 'email-subscribers' );
												?>
												</span>
										</div>
										<?php
									}
									?>
									<div class="ig_es_form_heading px-3">
										<p class="pt-2 text-base tracking-wide text-gray-600 font-medium"><?php echo esc_html__( 'Unsubscribe from all list(s)', 'email-subscribers' ); ?></p>
										<span class="text-sm text-gray-500"><?php echo esc_html__( 'You will be unsubscribed from receiving all future emails sent from us.', 'email-subscribers' ); ?></span>
									</div>
									<?php
									do_action( 'ig_es_unsubscribe_form_before_end' );
									?>
									<input type="hidden" name="submitted" value="submitted">
									<input class="ml-3 mt-4 rounded-md border border-transparent px-4 py-2 bg-white text-sm leading-5 font-medium text-white bg-indigo-600  transition ease-in-out duration-150 hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500 cursor-pointer" type="submit" name="unsubscribe" value="<?php echo esc_attr__( 'Unsubscribe', 'email-subscribers' ); ?>">
								</form>
							</div>
						</div>
					</section>
				</div>
			</body>
		</html>
		<?php
		die();
	}

	/**
	 * Add Icegram Express template types
	 *
	 * @param array $template_type Template types
	 *
	 * @return array $template_type Template types
	 *
	 * @since 5.0.0
	 */
	public function add_template_type( $template_type = array() ) {

		$template_type['newsletter'] = __( 'Broadcast', 'email-subscribers' );

		// Start-IG-Code.
		$template_type['post_notification'] = __( 'Post Notification', 'email-subscribers' );
		// End-IG-Code.

		return $template_type;
	}
}
