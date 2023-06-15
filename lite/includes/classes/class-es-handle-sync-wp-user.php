<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ES_Handle_Sync_Wp_User {

	public static $instance;

	public function __construct() {
		// Sync upcoming WordPress users
		// add_action( 'ig_es_sync_users_tabs_wordpress', array( $this, 'sync_wordpress_users_settings' ) );

		// add_action( 'user_register', array( $this, 'sync_registered_wp_user' ) );
		// add_action( 'delete_user', array( $this, 'delete_contact' ), 10, 1 );
	}

	/*
	public function sync_wordpress_users_settings( $wordpress_tab ) {

		if ( ! empty( $wordpress_tab['indicator_option'] ) ) {
			update_option( $wordpress_tab['indicator_option'], 'no' );
		}

		$submitted = ig_es_get_post_data( 'submitted' );
		if ( 'submitted' === $submitted ) {

			$form_data = ig_es_get_post_data( 'form_data' );

			$error = false;
			if ( ! empty( $form_data['es_registered'] ) && 'YES' === $form_data['es_registered'] ) {
				$list_id = ! empty( $form_data['es_registered_group'] ) ? $form_data['es_registered_group'] : 0;
				if (0 === $list_id ) {
					$message = __( 'Please select list', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$error = true;
				}
			}

			if ( ! $error ) {
				update_option( 'ig_es_sync_wp_users', $form_data );
				$message = __( 'Settings have been saved successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		$default_form_data = array(
			'es_registered'       => 'NO',
			'es_registered_group' => 0,
		);

		$form_data = get_option( 'ig_es_sync_wp_users', array() );
		$form_data = wp_parse_args( $form_data, $default_form_data );

		?>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="tag-image">
						<?php esc_html_e( 'Sync WordPress Users?', 'email-subscribers' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Whenever someone signup, it will automatically be added into selected list', 'email-subscribers' ); ?></p>
				</th>
				<td>
					<select name="form_data[es_registered]" id="es_email_status">
						<option value='NO'
						<?php
						if ('NO' == $form_data['es_registered'] ) {
							echo "selected='selected'";
						}
						?>
						><?php esc_html_e( 'No', 'email-subscribers' ); ?></option>
						<option value='YES'
						<?php
						if ( 'YES' == $form_data['es_registered'] ) {
							echo "selected='selected'";
						}
						?>
						><?php esc_html_e( 'Yes', 'email-subscribers' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="tag-display-status">
						<?php esc_html_e( 'Select List', 'email-subscribers' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Select the list in which newly registered user will be subscribed to', 'email-subscribers' ); ?></p>
				</th>
				<td>
					<select name="form_data[es_registered_group]">
						<?php
						$lists_dropdown = ES_Common::prepare_list_dropdown_options( $form_data['es_registered_group'], 'Select List' );
						$allowedtags    = ig_es_allowed_html_tags_in_esc();
						echo wp_kses( $lists_dropdown , $allowedtags );
						?>
					</select>
				</td>
			</tr>
			</tbody>
		</table>
		<input type="hidden" name="submitted" value="submitted"/>
		<p style="padding-top:5px;">
			<input type="submit" class="button-primary" value="<?php esc_html_e( 'Save Settings', 'email-subscribers' ); ?>"/>
		</p>

		<?php
	}
	*/

	/**
	 * Sync/Add WP new user into ES
	 *
	 * @param $user_id
	 *
	 * @since 4.0
	 *
	 * @modify 4.3.12
	 */
	public function sync_registered_wp_user( $user_id ) {
		$ig_es_sync_wp_users = get_option( 'ig_es_sync_wp_users', array() );

		if ( ! empty( $ig_es_sync_wp_users ) ) {

			$ig_es_sync_wp_users = maybe_unserialize( $ig_es_sync_wp_users );

			$ig_es_registered = ( ! empty( $ig_es_sync_wp_users['es_registered'] ) ) ? $ig_es_sync_wp_users['es_registered'] : 'NO';

			if ( 'YES' === $ig_es_registered ) {
				$list_id = $ig_es_sync_wp_users['es_registered_group'];
				// get user info
				$user_info = get_userdata( $user_id );
				if ( $user_info instanceof WP_User ) {
					$user_first_name = $user_info->display_name;

					$email = $user_info->user_email;
					if ( empty( $user_first_name ) ) {
						$user_first_name = ES_Common::get_name_from_email( $email );
					}
					// prepare data
					$data = array(
						'first_name' => $user_first_name,
						'email'      => $email,
						'source'     => 'wp',
						'status'     => 'verified',
						'hash'       => ES_Common::generate_guid(),
						'created_at' => ig_get_current_date_time(),
						'wp_user_id' => $user_id,
					);

					do_action( 'ig_es_add_contact', $data, $list_id );
				}
			}
		}
	}

	/**
	 * Delete contact from ES when user deleted from WordPress
	 *
	 * @param $user_id
	 *
	 * @since 4.3.12
	 */
	public function delete_contact( $user_id = 0 ) {

		$ig_es_sync_wp_users = get_option( 'ig_es_sync_wp_users', array() );

		if ( ! empty( $ig_es_sync_wp_users ) ) {

			$ig_es_sync_wp_users = maybe_unserialize( $ig_es_sync_wp_users );

			$ig_es_registered = ( ! empty( $ig_es_sync_wp_users['es_registered'] ) ) ? $ig_es_sync_wp_users['es_registered'] : 'NO';

			if ( 'YES' === $ig_es_registered ) {

				if ( ! empty( $user_id ) ) {
					global $wpdb;

					$user = get_user_by( 'ID', $user_id );

					if ( $user instanceof WP_User ) {
						$email = $user->user_email;

						$where      = $wpdb->prepare( 'email = %s', $email );
						$contact_id = ES()->contacts_db->get_column_by_condition( 'id', $where );

						if ( $contact_id ) {
							ES()->contacts_db->delete_contacts_by_ids( $contact_id );
						}
					}
				}
			}
		}

	}

	public function prepare_sync_user() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'sync';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>
		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="flex">
					<div>
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
								<a class="hover:underline " href="admin.php?page=es_subscribers"><?php esc_html_e( 'Audience ', 'email-subscribers' ); ?></a>
								<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
								</li>
							</ol>
						</nav>
						<h2 class="-mt-1.5 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate"> <?php esc_html_e( 'Sync contacts', 'email-subscribers' ); ?>
						</h2>
					</div>

					<div class="mt-4 ml-2">
						<?php
						ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
						?>
					</div>
				</div>
		</header>

			<?php $this->sync_users_callback(); ?>
		</div>

		<?php
	}

	public function sync_users_callback() {

		$logger = get_ig_logger();
		$logger->trace( 'Sync Users' );
		$active_tab = ig_es_get_request_data( 'tab', 'WordPress' );

		$tabs = array(
			'wordpress' => array(
				'name' => __( 'WordPress', 'email-subscribers' ),
				'url'  => admin_url( 'admin.php?page=es_subscribers&action=sync&tab=wordpress' ),
			),
		);

		$tabs = apply_filters( 'ig_es_sync_users_tabs', $tabs );
		?>
		<div class="ig-es-sync-settings-notice">
			<div class="text-center py-4 lg:px-4 my-8">
				<div class="p-2 bg-indigo-800 items-center text-indigo-100 leading-none lg:rounded-full flex lg:inline-flex mx-4 leading-normal" role="alert">
					<span class="flex rounded-full bg-indigo-500 uppercase px-2 py-1 text-xs font-bold mr-3"><?php echo esc_html__( 'New', 'email-subscribers' ); ?></span>
					<span class="font-semibold text-left flex-auto">
					<?php
					$workflows_page_url = menu_page_url( 'es_workflows', false );
					/* translators: %s: Link to Workflow page */
					echo wp_kses_post( sprintf( __( 'Hey!!! now sync users using Icegram Express\' workflows. <a href="%s" class="text-indigo-400">Create new workflows</a>', 'email-subscribers' ), $workflows_page_url ) );
					?>
				</span>
				</div>
			</div>
		</div>
		<?php
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
