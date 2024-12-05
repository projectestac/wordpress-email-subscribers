<?php
defined( 'ABSPATH' ) || exit;

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

	a {
		color: #5e19cf;
	}

	p {
		font-size: 1.2em;
	}
</style>
<body>
	<div class="container">
		<div class="logo-container center">
			<img src="<?php esc_attr_e( $logo_url ); ?>" width="64" alt="<?php echo esc_url( 'Icegram Express logo', 'email-subscribers' ); ?>"/>
		</div>
		<div>
			<p class="">Hey <?php echo esc_html($first_name); ?>,</p>
			<p>Just a quick reminder: your trial ends tomorrow!</p>
			<p>And sadly <b>you will lose access to these key email marketing features:</b></p>
			<ul>
				<li>
					<p><a href="https://www.icegram.com/docs/category/icegram-express-premium/add-utm-parameters-email/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-13"><b>Google Analytics UTM tracking</b></a> - Measure the effectiveness of your email on autopilot.. This feature automatically add UTMs to all the links in your email, thus making sure each link is tracked and measured. </p>
				</li>
				<li>
					<p><a href="https://www.icegram.com/docs/category/icegram-express-premium/check-spam-score#what-to-do-if-my-spam-score-is-higher-than-5/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-13"><b>Spam score checking</b></a> - This feature checks the spam score of your email. Use this feature smartly to ensure your emails land in the inbox and not the spam folder of your subscribers.
					</p>
				</li>
				<li>
					<p><a href="https://www.icegram.com/docs/category/icegram-express-premium/enable-automatic-cron/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-13"><b>Background email sending</b></a> - Hit send and relax. This feature will ensure that all your emails are sent without any mishaps or bottlenecks.
					</p>
				</li>
				<li>
					<p><a href="https://www.icegram.com/css-inliner/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-13"><b>CSS inliner</b></a> - You don’t need to pay for a team of graphic designers anymore. This feature helps you create professional looking emails that go with your brand within minutes.
					</p>
				</li>
			</ul>

			<p>I have been an email marketer for a decade now and these features are a total must-have.</p>
			<p>So don’t let go of this opportunity.</p>
			<p>You can <a href="https://www.icegram.com/express/pricing/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-13"><b>grab the Pro Or Max plan at 25% OFF</b></a> </p>
			<p>Apply the code: <b>es-upgrade-25</b> at checkout</p>
			<p>NOTE: <b>This offer expires in 24 hours.</b></p>
			<p>So upgrade before your trial runs out…..</p>
			<p>Avoid regret. Don't be the "should've, could've" person.</p>
			<p>Emails are here for the long run. So upgrading is a worthy investment.</p>
			<p><a href="https://www.icegram.com/express/pricing/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-13"><b>Upgrade today at 25% OFF</b></a></p>
			<p>Cheers, <br>Sandhya</p>
			<p><b>P.S.</b> On the fence? Need help deciding the right plan/ Any other concern? Just reach out - I’m here for you! <code>hello@icegram.com</code> </p>
		</div>
		
	</div>
</body>
</html>
