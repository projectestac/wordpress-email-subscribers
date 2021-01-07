<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$es_page_request = ig_es_get_request_data( 'es' );

$site_title = get_option( 'blogname' );
$noerror    = true;
$home_url   = home_url( '/' );
?>
	<html>
	<head>
		<title><?php echo esc_html( $site_title ); ?></title>
		<?php do_action( 'es_message_head' ); ?>

		<style type="text/css">
			.es_center_info {
				margin: auto;
				width: 50%;
				padding: 10px;
				text-align: center;
			}
		</style>
	</head>
	<body>
	<div class="es_center_info es_successfully_subscribed">
		<p> <?php echo esc_html( $message ); ?> </p>
		<table class="table">
			<tr>
				<td><?php echo esc_html__( 'Total Emails Sent', 'email-subscribers' ); ?></td>
				<td><?php echo esc_html( $total_emails_sent ); ?></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Total Emails In Queue', 'email-subscribers' ); ?></td>
				<td>
				<?php
					echo esc_html( $total_emails_to_be_sent );
				if ( $total_emails_to_be_sent > 0 ) {
					?>
					<a href="<?php echo esc_url( $cron_url ); ?>"><?php echo esc_html__( 'Send Now', 'email-subscribers' ); ?></a>
					<?php
				}
				?>
				</td>

			</tr>

		</table>

	</div>
	</body>
	</html>
<?php

die();
