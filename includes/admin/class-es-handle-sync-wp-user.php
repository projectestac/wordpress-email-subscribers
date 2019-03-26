<?php

class ES_Handle_Sync_Wp_User {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		// Sync upcoming WordPress users
		add_action( 'user_register', array($this, 'sync_registered_wp_user' ));
	}

	public function plugin_menu() {
		add_submenu_page( null, 'Sync', __( 'Sync', 'email-subscribers' ), get_option( 'es_roles_subscriber', true ), 'es_sync', array( $this, 'prepare_sync_user' ) );
	}

	public function sync_registered_wp_user( $user_id ){
		//get option
		$ig_es_sync_wp_users = get_option( 'ig_es_sync_wp_users', 'norecord' );
		$ig_es_sync_unserialized_data = maybe_unserialize($ig_es_sync_wp_users);
		$ig_es_registered = $ig_es_sync_unserialized_data['es_registered'];
		if( $ig_es_sync_wp_users != 'norecord' && 'YES' === $ig_es_registered)  {
			$list_id = $ig_es_sync_unserialized_data['es_registered_group'];
			//get user info
			$user_info      = get_userdata( $user_id );
			if( !($user_info instanceof WP_User) ) return false;
			$user_firstname = $user_info->display_name;
			$email = $user_info->user_email;
			if ( empty( $user_firstname ) ) {
				$user_firstname = ES_Common::get_name_from_email( $email );
			}
			//prepare data
			$data  = array(
				'first_name' => $user_firstname,
				'email'      => $email,
				'source'     => 'wp',
				'status'     => 'verified',
				'hash'       => ES_Common::generate_guid(),
				'created_at' => ig_get_current_date_time(),
				'wp_user_id' => $user_id
			);
			$check = ES_DB_Contacts::is_subscriber_exist_in_list( $email, $list_id );
			if ( empty( $check['contact_id'] ) ) {
				$added = ES_DB_Contacts::add_subscriber( $data );
			} else {
				$added = $check['contact_id'];
			}
			if ( empty( $check['list_id'] ) ) {
				$optin_type        = get_option( 'ig_es_optin_type', true );
				$optin_type        = ( $optin_type === 'double_opt_in' ) ? 2 : 1;
				$list_id           = ! empty( $list_id ) ? $list_id : 1;
				$list_contact_data = array(
					'list_id'       => array($list_id),
					'contact_id'    => $added,
					'status'        => 'subscribed',
					'subscribed_at' => ig_get_current_date_time(),
					'optin_type'    => $optin_type,
					'subscribed_ip' => null
				);

				$result = ES_DB_Lists_Contacts::add_lists_contacts( $list_contact_data );
			}
		}
		return true;

	}

	public function prepare_sync_user(){
	?>
		  <div class="wrap">
            <h2> <?php _e( 'Audience > Sync Contacts', 'email-subscribers' ); ?>
                <a href="admin.php?page=es_subscribers&action=new" class="page-title-action"><?php _e( 'Add New Contact', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_subscribers&action=export" class="page-title-action"><?php _e( 'Export Contacts', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_lists" class="page-title-action es-imp-button"><?php _e( 'Manage Lists', 'email-subscribers' ); ?></a>
            </h2>
			<?php $this->sync_users_callback(); ?>
        </div>

		<?php
	}


	public function sync_users_callback(){
		if ( !empty( $_POST["es_sync_submit"] ) && 'yes' === $_POST["es_sync_submit"] ) {
			if( $_POST['es_registered_group'] == 0 ){
				$message = __( 'Please select List', 'email-subscribers' );
				$this->show_message( $message, 'error' );
			}
			$ig_es_sync_wp_users['es_registered'] =  $_POST['es_registered'];
			$ig_es_sync_wp_users['es_registered_group'] = $_POST['es_registered_group'];
			update_option('ig_es_sync_wp_users', $ig_es_sync_wp_users);
		}
		$ig_es_sync_wp_users = get_option( 'ig_es_sync_wp_users', 'norecord' );
		if( $ig_es_sync_wp_users != 'norecord' && $ig_es_sync_wp_users != "") {
			$ig_es_sync_unserialized_data = maybe_unserialize($ig_es_sync_wp_users);
			$ig_es_registered = $ig_es_sync_unserialized_data['es_registered'];
			$ig_es_registered_list = $ig_es_sync_unserialized_data['es_registered_group'];
		}
		?>
		<form name="form_sync" id="form_sync" method="post" action="#" >
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="tag-image">
								<?php echo __( 'Sync newly registered users to subscribers list', 'email-subscribers' ); ?>
							</label>
						</th>
						<td>
							<select name="es_registered" id="es_email_status">
								<option value='NO' <?php if($ig_es_registered == 'NO') { echo "selected='selected'" ; } ?>><?php echo __( 'No', 'email-subscribers' ); ?></option>
								<option value='YES' <?php if($ig_es_registered == 'YES') { echo "selected='selected'" ; } ?>><?php echo __( 'Yes', 'email-subscribers' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<label for="tag-display-status">
								<?php echo __( 'Select list to add newly registered users to', 'email-subscribers' ); ?>
							</label>
						</th>
						<td>
							<select name="es_registered_group">
								<?php echo ES_Common::prepare_list_dropdown_options( $ig_es_registered_list, 'Select Lists' ); ?>
				            </select>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="es_sync_submit" value="yes"/>
			<p style="padding-top:5px;">
				<input type="submit" class="button-primary" value="<?php echo __( 'Sync Now', 'email-subscribers' ); ?>" />
			</p>
		</form>
	<?php
	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}

}


add_action( 'plugins_loaded', function () {
	new ES_Handle_Sync_Wp_User();
} );
