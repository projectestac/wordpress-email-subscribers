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
			<p class="">Hey Buddy,</p>
			<p>Just dropping you a quick note to remind you that you're very close to:</p>
			<p>1. <b>End of your trial usage</b> by tomorrow (14th day of usage)</p>
			<p>2. Getting <b>25% off</b> on any plan (both Pro & Max) using this coupon code:[es-upgrade-25]</p>
			<p>This is a <b>limited-time offer</b>, so be sure to take advantage before it expires! <b>[Upgrade before trial runs out]</b></p>
			<p><b><em>1000+ have become our paid users using this offer over the last 1 year..</em></b></p>
			<p>Join this success group and power up your emails!</p>
			<p>Don't be that regretful, "should've, could've" person.</p>
			<p><b>Have questions or need assistance</b> choosing the right plan for your business? Reach out to us - we're here to help.</p>
			<p>Happy Emails!</p>
			<p>Team Icegram</p>
			<p><b>P.S.</b> Still deciding? Share your thoughts, and I'll address any concerns you may have.Drop us an email at <code>hello@icegram.com</code> </p>
		</div>
		
	</div>
</body>
</html>
