<?php

class ES_Service_List_Cleanup extends ES_Services {  


	/**
	 * ES_Service_List_Cleanup constructor.
	 *
	 * @since 4.6.1
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'ig_es_add_subscriber_data', array( $this, 'handle_spam_email_check' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'show_list_cleanup_notice' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function handle_spam_email_check( $data ) {

		if ( ES()->trial->is_trial_valid() && !ES()->is_premium() ) {
		
			if ( ES()->validate_service_request( array( 'list_cleanup' ) ) ) {

				$email_arr = array( $data['email'] );
				$res       = self::es_list_cleanup( $email_arr );
		
				if ( ! empty( $res['status'] ) && 'SUCCESS' == $res['status'] ) {

					$res           = $res['data'][0];
					$status        = ( 1 == $res['attributes']['disposable'] || 0 == $res['attributes']['deliverable'] ) ? 'spam' : $data['status'];
					update_option( 'ig_es_close_list_cleanup_notice', 'no', false );
					if ( 'spam' === $status ) {
					
						$stored_spam_emails = get_option( 'ig_es_spam_emails', array() );
						$stored_spam_emails[] = $data['email'];
						update_option( 'ig_es_spam_detected', true );
						update_option( 'ig_es_spam_emails', $stored_spam_emails, false );	
					} else {
						update_option( 'ig_es_spam_detected', false );
					}
				}
		
			}
		}

	return $data;
	}


	public static function es_list_cleanup( $subscribers ) {
		
		$url = 'https://ves.icegram.com/email/batch/verify/';

		//TODO :: Timeout
		foreach ( $subscribers as $email ) {
			$data[] = array( 'email' => $email );

		}
		$data_to_send['lists']       = json_encode( $data );
		//$data_to_send['mode']        = 'full';
		$data_to_send['autoCorrect'] = 0;

		$options  = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => $data_to_send
		);
		$response = wp_remote_post( $url, $options );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = $response['body'];
			if ( 'error' != $data ) {
				$data = json_decode( $data, true );
			}
		} else {
			$data['status'] = 'ERROR';
		}
		
		return $data;
	}

	public function show_list_cleanup_notice() {

		if ( !ES()->is_es_admin_screen() ) {
			return;
		}
		if ( !ES()->trial->is_trial_valid() || ES()->is_premium()) {
			return;
		}
		$current_page = ig_es_get_request_data('page');

		if ( 'es_subscribers' === $current_page ) {
	
		$spam_emails = get_option( 'ig_es_spam_emails', array());
		$spam_emails_count=count($spam_emails);
		
			if (ig_es_get_request_data('ig_es_close_list_cleanup_notice') && check_admin_referer('ig_es_close_list_cleanup_notice_nonce')) {
				update_option( 'ig_es_close_list_cleanup_notice', 'yes', false );
			}

			if ('yes'=== get_option('ig_es_close_list_cleanup_notice')) {
				return;
			}

			?>

<div class="notice notice-success is-dismissible" id="ig-list-cleanup-custom-notice">
	<span>
		<?php
		$is_spam_detected = get_option( 'ig_es_spam_detected', false );
			if ( $is_spam_detected) {  
				?>

		  <p> <strong>Alert:</strong>
					<?php 
				echo sprintf(esc_html__( '%s spam emails were found in your audience. Sending to them may harm delivery and site reputation.', 'email-subscribers' ),
					wp_kses_post( $spam_emails_count ));
					?>
			</p>
			<p><strong>[Note]:</strong><?php echo sprintf( esc_html__( ' Spam detection is available in the trial and Max plans. Upgrade to %s to avoid this.', 'email-subscribers' ), '<a target="_blank" href="https://www.icegram.com/docs/category/icegram-express-premium/how-to-mark-an-email-as-spam/?utm_source=in_app&utm_medium=admin_notice&utm_campaign=trial_spam_detect_upsale"  style="color: blue;"> Max</a>'); ?></p>
				<?php
			} else {
				?>
			<p><?php echo esc_html__( 'Congrats! No spam emails found in your audience. It’s safe to send.', 'email-subscribers' ); ?></p>

			<p><strong>[Note]:</strong><?php echo sprintf( esc_html__( ' Spam detection is available only in trial and Max plans. Upgrade to %s to protect your reputation.', 'email-subscribers' ), '<a target="_blank" href="https://www.icegram.com/docs/category/icegram-express-premium/how-to-mark-an-email-as-spam/?utm_source=in_app&utm_medium=admin_notice&utm_campaign=trial_spam_detect_upsale" style="color: blue;"> Max</a>'); ?></p>
			
			<?php
			}
			?>
	</span>
	
	<button type="button" class="notice-dismiss">
		<span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'email-subscribers' ); ?></span>
	</button>

	<form method="post" id="list-cleanup-dismiss-notice-form" style="display: none;">
		<input type="hidden" name="ig_es_close_list_cleanup_notice" value="yes">
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('ig_es_close_list_cleanup_notice_nonce') ); ?>">
	</form>
</div>

		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				var notice = document.getElementById('ig-list-cleanup-custom-notice');
				var form = document.getElementById('list-cleanup-dismiss-notice-form');
				notice.querySelector('.notice-dismiss').addEventListener('click', function() {
					form.submit();
				});
			});
		</script>
		
		<?php
		
		}

	}
}

new ES_Service_List_Cleanup();
