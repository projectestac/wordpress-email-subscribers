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
			<p>How is it going?</p>
			<p>I was curious about your experience with the free trial. Did you try out premium features like; <a href="https://www.icegram.com/docs/category/icegram-express-premium/add-utm-parameters-email/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-20"><b>Google Analytics UTM tracking</b></a>, <a href="https://www.icegram.com/docs/category/icegram-express-premium/check-spam-score#what-to-do-if-my-spam-score-is-higher-than-5/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-20"><b>Spam score check</b></a>, <a href="https://www.icegram.com/docs/category/icegram-express-premium/enable-automatic-cron/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-20"><b>Background email sending</b></a>, <a href="https://www.icegram.com/css-inliner/?utm_source=es&utm_medium=email&utm_campaign=plugin-email-20"><b>CSS inliner?</b></a></p>
			<p>Also, I noticed you haven’t upgraded to a paid plan yet, so I wanted to personally reach out and ask why.</p>
			<p>What’s holding you back?</p>
			<ul>
				<li><p>Pricing</p></li>
				<li><p>Didn't find what you needed</p></li>
				<li><p>Happy with the free plugin features</p></li>
				<li><p>Something else</p></li>
			</ul>
			<p>Your feedback is super important to us. Just reach out - I’m here for you! <code>hello@icegram.com</code></p>
			<p>Best, <br>Sandhya</p>
		</div>
	</div>
</body>
</html>
