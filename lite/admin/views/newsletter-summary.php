<?php
$es_wp_styles = wp_styles();
?>
<html>
<?php
$es_wp_styles->do_item( 'google-fonts' );
?>
<style>
	html {
		-moz-tab-size: 4;
		-o-tab-size: 4;
		tab-size: 4;
		line-height: 1.15;
		-webkit-text-size-adjust: 100%;
	}
	body {
		background: #efeeea;
		margin: 0;
		font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif, Apple Color Emoji, Segoe UI Emoji;
	}

	.container {
		background: #FFFFFF;
		border: 1px solid #efeeea;
		max-width: 600px;
		margin: 20px auto;
		padding: 10px;
		border-radius: 5px;
	}

	.center {
		text-align: center;
	}

	.logo-container {
		margin: 20px 10px;
	}

	.heading {
		font-size: 2em;
		margin: 20px auto;
		color: #2a231c;
	}

	.sub-heading {
		color: #433c36;
		font-size: 0.9em;
	}

	.my-30 {
		margin: 30px auto;
		font-size: 15px;
	}

	.mt-10 {
		margin-top: 10px;
	}

	.mb-30 {
		margin-bottom: 30px;
	}

	table {
		width: 100%;
	}

	td {
		padding: 10px;
		text-align: center;
		width: 33.33%;
	}

	td .title {
		color: #433c36;
		margin: 10px 0;
		font-size: 15px;
	}

	td .value {
		color: #5850ec;
		font-size: 24px;
		font-weight: 500;
	}

	.button {
		background: #5850ec;
		margin: unset;
		padding: 20px;
		display: inline-block;
		text-decoration: none;
		color: #FFFFFF;
	}

	a {
		color: #5850ec;
		text-decoration: none;
	}

	.m-auto {
		margin: auto;
	}

	.w-95p {
		width: 90%;
	}

	.desc {
		line-height: 25px;
	}

	.seperator {
		box-sizing:border-box;
		height:0;
		color:inherit;
		margin:0;
		border-top-width:1px;
		border:solid #d1d5db;
		border-width:0 0 1px;
		border-style:dotted;
		border-bottom-width:2px;
		margin-top:2rem;
		margin-bottom:2rem;
	}
</style>
<body>
	<div class="container">
		<div class="logo-container center">
			<img src="<?php esc_attr_e( $logo_url ); ?>" width="64" alt="<?php echo esc_url( 'Icegram Express logo', 'email-subscribers' ); ?>"/>
		</div>
		<div>
			<p class="center heading"><?php esc_html_e( 'Your Weekly Email Summary', 'email-subscribers' ); ?></p>
			<p class="center sub-heading"><?php esc_html_e( $start_date ); ?> - <?php esc_html_e( $end_date ); ?></p>
			<hr class="seperator"/>
			<p class="center"><?php esc_html_e( 'Here are your weekly stats from', 'email-subscribers' ); ?>
				<b><?php esc_html_e( 'Icegram Express.', 'email-subscribers' ); ?></b>
			</p>
		</div>
		<div>
			<table>
				<?php
				if ( 'pro' === $plan ) {
					?>
					<tr>
					<td>
						<div class="value">
							<?php esc_html_e( abs($contacts_growth) ); ?>%
							<?php
							if ( 0 < floatval( $contacts_growth ) ) {
								?>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="green" width="12" height="12"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
								<?php
							} elseif ( 0 > floatval( $contacts_growth ) ) {
								?>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="red" width="12" height="12"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
								<?php
							}
							?>
						</div>
						<div class="title"><?php esc_html_e( 'Contacts growth', 'email-subscribers' ); ?></div>
					</td>
					<td>
						<div class="value"><?php esc_html_e( $total_subscribed ); ?></div>
						<div class="title"><?php esc_html_e( 'Subscribers', 'email-subscribers' ); ?></div>
					</td>
					<td>
						<div class="value"><?php esc_html_e( $total_unsubscribed ); ?></div>
						<div class="title"><?php esc_html_e( 'Unsubscribes', 'email-subscribers' ); ?></div>
					</td>
				</tr>
				<tr>
					<td>
						<div class="value"><?php esc_html_e( $total_sent_mails ); ?></div>
						<div class="title"><?php esc_html_e( 'Sent Emails', 'email-subscribers' ); ?></div>
					</td>
					<td>
						<div class="value"><?php esc_html_e( $total_opened_mails ); ?></div>
						<div class="title"><?php esc_html_e( 'Opens', 'email-subscribers' ); ?></div>
					</td>
					<td>
						<div class="value"><?php esc_html_e( $total_clicked_mails ); ?></div>
						<div class="title"><?php esc_html_e( 'Clicks', 'email-subscribers' ); ?></div>
					</td>
				</tr>
					<?php
				} else {
					?>
					<tr>
						<td>
							<div class="value"><?php esc_html_e( $total_subscribed ); ?></div>
							<div class="title"><?php esc_html_e( 'New Subscribers', 'email-subscribers' ); ?></div>
						</td>
						<td>
							<div class="value"><?php esc_html_e( $total_sent_mails ); ?></div>
							<div class="title"><?php esc_html_e( 'Sent Emails', 'email-subscribers' ); ?></div>
						</td>
						<td>
							<div class="value"><?php esc_html_e( $total_opened_mails ); ?></div>
							<div class="title"><?php esc_html_e( 'Opens', 'email-subscribers' ); ?></div>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
		<?php
		if ( ! empty( $reports_url ) ) {
			?>
			<div class="center mt-10 mb-30">
				<a href="<?php echo esc_url( $reports_url ); ?>"
				class="button"><?php echo esc_html__( 'View More Stats', 'email-subscribers' ); ?></a>
			</div>
			<?php
		}
		?>
		<?php
		if ( ! empty( $latest_newsletters ) ) {
			?>
			<hr class="seperator"/>
		<div>
			<p class="center" style="font-size: 1.2em;"><?php echo esc_html__( 'Latest from Icegram\'s Newsletter', 'email-subscribers' ); ?></p>
			<ul>
				<?php
				foreach ( $latest_newsletters as $newsletter ) {
					?>
					<li><a href="https://www.icegram.com/?action=es-view-archive&archive=<?php echo esc_attr( $newsletter->hash ); ?>"><?php echo esc_html( $newsletter->subject ); ?></a></li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
		}
		?>
		<?php
		if ( ! empty( $unsubscribe_url ) ) {
			?>
			<hr class="seperator"/>
			<div style="font-size: 0.9em; color: gray;">
				<p class="center">
					<?php
						/* translators: 1. Unsubscribe link starting anchor tag 2. Unsubscribe link closing anchor tag */
						echo sprintf( esc_html__( 'If you don\'t want to receive weekly account summary, then click %1$shere%2$s to unsubscribe.' ), '<a href="' . esc_url( $unsubscribe_url ) . '">', '</a>' );
					?>
				</p>
			</div>
			<?php
		}
		?>
	</div>
</body>
</html>
