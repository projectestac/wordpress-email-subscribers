<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Subscribers_Pricing {

	public static function sm_show_pricing() {

		$utm_medium  = apply_filters( 'ig_es_pricing_page_utm_medium', 'in_app_pricing' );
		$allowedtags = ig_es_allowed_html_tags_in_esc();

		?>
		<style type="text/css">
			.update-nag {
				display: none;
			}
			.wrap.about-wrap.ig_es {
				margin: 0 auto;
				max-width: 100%;
			}
			body{
				background-color: white;
			}
			.ig_es_main_heading {
				font-size: 2em;
				background-color: #252f3f !important;
				color: #ffffff;
				text-align: center;
				font-weight: 500;
				margin: auto;
				padding-top: 0.75em;
				padding-bottom: 0.5em;
				/* max-width: 1375px; */
			}
			.ig_es_discount_code {
				/* color: #6875F5; */
				font-weight: 600;
				font-size: 2.5rem;
			}
			.ig_es_sub_headline {
				font-size: 1.6em;
				font-weight: 400;
				color: #00848D !important;
				text-align: center;
				line-height: 1.5em;
				margin: 0 auto 1em;
			}
			.ig_es_row {
				/* padding: 1em !important;
				margin: 1.5em !important; */
				clear: both;
				position: relative;
			}
			#ig_es_price_column_container {
				display: -webkit-box;
				display: -webkit-flex;
				display: -ms-flexbox;
				display: flex;
				max-width: 1190px;
				margin-right: auto;
				margin-left: auto;
				margin-top: 4em;
				padding-bottom: 1.2em;
			}
			.ig_es_column {
				padding: 2em;
				margin: 0 1em;
				background-color: #fff;
				border: 1px solid rgba(0, 0, 0, 0.1);
				text-align: center;
				color: rgba(0, 0, 0, 0.75);
			}
			.column_one_fourth {
				width: 30%;
				border-radius: 3px;
				margin-right: 4%;
			}
			.ig_es_last {
				margin-right: 0;
			}
			.ig_es_price {
				margin: 1.5em 0;
				color: #1e73be;
			}
			.ig_es_button {
				color: #FFFFFF !important;
				padding: 15px 32px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 16px;
				font-weight: 500;
				margin: 2em 2px 1em 2px;
				cursor: pointer;
			}
			.ig_es_button.green {
				background: #23B191;
				border-color: #23B191;
			}
			.ig_es_button.green:hover {
				background: #66C78E;
				border-color: #66C78E;
			}

			.ig_es_button.small {
				text-transform: uppercase !important;
				box-shadow: none;
				padding: 0.8em;
				font-size: 1rem;
				border-radius: 0.25rem;
				margin-top: 1em;
				font-weight: 600;
			}
			.ig_es_discount_amount {
				font-size: 1.3em !important;
			}
			.dashicons.dashicons-yes {
				color: green;
				font-size: 2em;
			}
			.dashicons.dashicons-no-alt {
				color: #ed4337;
				font-size: 2em;
			}
			.dashicons.dashicons-yes.yellow {
				color: #BDB76B;
				line-height: unset;
			}
			.dashicons.dashicons-awards,
			.dashicons.dashicons-testimonial {
				margin-right: 0.25em !important;
				color: #15576F;
				font-size: 1.25em;
			}
			.ig_es_license_name {
				font-size: 1.1em !important;
				color: #1a72bf !important;
				font-weight: 500 !important;
			}
			.ig_es_old_price {
				font-size: 1.3em;
				color: #ed4337;
				vertical-align: top;
			}
			.ig_es_new_price {
				font-size: 1.6em;
				padding-left: 0.2em;
				font-weight: 400;
			}
			.ig_es_most_popular {
				position: absolute;
				right: 0px;
				top: -39px;
				background-color: #41495b;
				background-color: #596174;
				text-align: center;
				color: white;
				padding: 10px;
				font-size: 18px;
				border-top-right-radius: 4px;
				border-top-left-radius: 4px;
				font-weight: 500;
				width: 275px;
			}
			#ig-es-testimonial {
				text-align: center;
			}
			.ig-es-testimonial-content {
				width: 50%;
				margin: 0 auto;
				margin-bottom: 1em;
				background-color: #FCFEE9;
			}
			.ig-es-testimonial-content img {
				width: 12% !important;
				border-radius: 9999px;
				margin: 0 auto;
			}

			#ig_es_testimonial-others .ig-es-testimonial-content img.star-ratings {
				width: 18% !important;
			}

			.ig_es_testimonial_headline {
				margin: 0.6em 0 0 !important;
				font-weight: 500 !important;
				font-size: 1.5em !important;
			}
			.ig_es_testimonial_text {
				text-align: left;
				font-size: 1.2em;
				line-height: 1.6;
				padding: 1em 1em 0;
			}
			.pricing {
				border-radius: 5px;
				position: relative;
				padding: 0.25em;
				margin: 2em auto;
				background-color: #fff;
				border: 1px solid rgba(0, 0, 0, 0.1);
				text-align: center;
				color: rgba(0, 0, 0, 0.75);
			}
			.pricing h4 {
				margin-top: 1em;
				margin-bottom: 1em;
			}
			.pricing del {
				font-size: 1.3em;
				color: grey;
			}
			.pricing h2 {
				margin-top: 0!important;
				margin-bottom: 0.5em;
				text-align: center;
				font-weight: 600;
				line-height: 1.218;
				color: #515151;
				font-size: 2.5em;
			}
			.pricing p {
				text-align: center;
				margin: 0em;
			}
			.pricing:hover{
				border-color: #15576F;
			}
			.pricing.scaleup{
				transform: scale(1.2);
			}
			.fidget.spin{
				animation: spin 1.2s 0s linear both infinite;
			}
			@keyframes spin {
				0% {
						transform: rotate(0deg);
					}
				100% {
						transform: rotate(360deg);
					}
			}
			table.ig_es_feature_table {
				width: 90%;
				margin-left: 5%;
				margin-right: 5%;
			}
			table.ig_es_feature_table th,
			table.ig_es_feature_table tr,
			table.ig_es_feature_table td,
			table.ig_es_feature_table td span {
				padding: 0.5em;
				text-align: center !important;
				background-color: transparent !important;
				vertical-align: middle !important;
			}
			table.ig_es_feature_table,
			table.ig_es_feature_table th,
			table.ig_es_feature_table tr,
			table.ig_es_feature_table td {
				border: 1px solid #eaeaea;
			}
			table.ig_es_feature_table.widefat th,
			table.ig_es_feature_table.widefat td {
				color: #515151;
			}
			table.ig_es_feature_table th {
				font-weight: bolder !important;
				font-size: 1.3em;
			}
			table.ig_es_feature_table tr td {
				font-size: 15px;
			}
			table.ig_es_feature_table th.ig_es_features {
				background-color: #F4F4F4 !important;
				color: #A1A1A1 !important;
				width:16em;
			}
			table.ig_es_feature_table th.ig_es_free_features {
				background-color: #F7E9C8 !important;
				color: #D39E22 !important;
			}
			table.ig_es_feature_table th.ig_es_pro_features{
				background-color: #CCFCBF !important;
				color: #14C38E !important;
				width:16em;
			}
			table.ig_es_feature_table th.ig_es_starter_features {
				background-color: #DCDDFC !important;
				color: #6875F5 !important;
			}
			table.ig_es_feature_table td{
				padding: 0.5em;
			}
			table.ig_es_feature_table td.ig_es_feature_name {
				text-transform: capitalize;
				padding:1em 2em;
			}
			table.ig_es_feature_table td.ig_es_free_feature_name {
				background-color: #FCF7EC !important;
				padding:1em 2em;
			}
			table.ig_es_feature_table td.ig_es_starter_feature_name {
				background-color: #F4F5FD !important;
				padding:1em 3em;
			}
			table.ig_es_feature_table td.ig_es_pro_feature_name {
				background-color: #E3FCBF !important;
				padding:1em 2em;
			}
			#ig_es_product_page_link {
				text-align: center;
				font-size: 1.1em;
				margin-top: 2em;
				line-height: 2em;
			}
			.clr-a {
				color: #00848D !important;
			}
			.update-nag , .error, .updated{
				display:none;
			}
			table .dashicons {
				padding-top: 0 !important;
			}
			#wpcontent {
				padding-left: 0!important;
			}
			#ig_es_testimonial-others, #ig_es_activity{
				margin-top: 4em;
			}
			#ig_es_comparison_table{
				margin-top: 4em;
			}

			.ig-es-testimonial-content .ig_es_testimonial_user_name{
				font-size: 1em;
				margin-top: 0.5em;
			}
			.ig_es_renew_headline{
				font-size: 1em;
				font-weight: 400;
				color: #00848D !important;
				text-align: center;
				line-height: 1.5em;
				margin: 0 auto 1em;
				padding-bottom: 3em;
			}

		</style>

		<div class="wrap about-wrap ig_es">
			<div class="ig_es_row" id="ig-es-pricing">
				<div class="ig_es_main_heading">
					<div style="display: inline-flex;">
						<div style="padding-right: 0.5rem;">🎉</div>
						<div style="line-height: 2.5rem;">
							<?php
								/* translators: %s: Offer text */
								echo sprintf( esc_html__( 'Congratulations! You unlocked %s on Icegram Express Premium plans!', 'email-subscribers' ), '<span class="ig_es_discount_code">' . esc_html__( '25% off', 'email-subscribers' ) . '</span>' );
							?>
						</div>
						<div style="padding-left: 0.5rem;">🎉</div>
					</div>
					<div style="padding-top: 1em;font-size: 0.5em;"><?php echo esc_html__( '⏰ Limited time offer', 'email-subscribers' ); ?></div>
				</div>
				<div id="ig_es_price_column_container">
						<div class="ig_es_column column_one_fourth pricing ig_es_lifetime_price">
							<span class="ig_es_plan"><h4 class="clr-a center"><?php echo esc_html__( 'Pro', 'email-subscribers' ); ?></h4></span>
							<span class="ig_es_plan"><h4 class="clr-a center"><?php echo esc_html__( '1 site (Annual)', 'email-subscribers' ); ?></h4></span>
							<span class="ig_es_price">
								<p><del class="center"><?php echo esc_html__( '$129', 'email-subscribers' ); ?></del></p>
								<h2><?php echo esc_html__( '$97', 'email-subscribers' ); ?></h2>
							</span>

							<div class="center">
								<a class="ig_es_button small green center" href="https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=<?php echo esc_attr( $utm_medium ); ?>&utm_campaign=pro" target="_blank" rel="noopener"><?php esc_html_e( 'Buy Now', 'email-subscribers' ); ?></a>
							</div>
						</div>
						<div class="ig_es_column column_one_fourth pricing scaleup" style="border-color: #15576F;padding: 0;border-width: 0.2em;">
							<div style="text-align: center;background-color: #15576F;color: #FFF;padding: 1em;font-weight: 900;text-transform: uppercase;"> <?php echo esc_html__( 'Best Seller', 'email-subscribers' ); ?> </div>
							<span class="ig_es_plan"><h4 class="clr-a center"><?php echo esc_html__( 'Max', 'email-subscribers' ); ?></h4></span>
							<span class="ig_es_plan"><h4 class="clr-a center"><?php echo esc_html__( '3 sites (Annual)', 'email-subscribers' ); ?></h4></span>
							<span class="ig_es_price">
								<p><del class="center"><?php echo esc_html__( '$229', 'email-subscribers' ); ?></del></p>
								<h2><?php echo esc_html__( '$172', 'email-subscribers' ); ?></h2>
							</span>

							<div class="center">
								<a class="ig_es_button small green center" href="https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=<?php echo esc_attr( $utm_medium ); ?>&utm_campaign=max" target="_blank" rel="noopener"><?php esc_html_e( 'Buy Now', 'email-subscribers' ); ?><span style="width: 1em; height: 1em; background-image: url('https://www.storeapps.org/wp-content/themes/storeapps/assets/images/fidget.svg'); display: inline-block; margin-left: 0.5em" class="fidget spin"></span></a>
							</div>
						</div>
				</div>
				<div class="ig_es_renew_headline"><?php echo esc_html__( ' * Renew at same discounted price', 'email-subscribers' ); ?></div>
			</div>
			<div class="ig_es_row" id="ig-es-testimonial">
				<div class="ig_es_column ig-es-testimonial-content">
					<img src="https://secure.gravatar.com/avatar/bd09132e1396bf948a5710bcdec25126?s=150&d=retro&r=g" alt="laurendevine" />
					<h3 class="ig_es_testimonial_headline">
						<?php echo esc_html__( 'Worked where other subscriber plugins failed…', 'email-subscribers' ); ?>
					</h3>
					<img src="<?php echo esc_url( ES_IMG_URL . '/five-stars.png' ); ?>" class="star-ratings" alt="Star ratings">
					<div class="ig_es_testimonial_text">
						<?php 
						echo esc_html__( 'This plugin was quick and easy to set up and implement…and, more important than anything…IT WORKS. Other big-name plugins didn’t work when re: sending out notifications to users re: new blog entries and THIS ONE DOES. Use with their free RAINMAKER plugin to format the look and feel of your form a bit more, and its even better!', 'email-subscribers' );
						?>
						<p class="ig_es_testimonial_user_name">
						- Lauren Devine
								</p>
					</div>
				</div>
			</div>
			<div class="ig_es_row" id="ig_es_comparison_table">
				<div class="ig_es_sub_headline"><span class="dashicons dashicons-awards"></span><?php echo esc_html__( ' More powerful features with Icegram Express Premium!', 'email-subscribers' ); ?></div>
				<table class="ig_es_feature_table wp-list-table widefat">
					<thead>
						<tr>
							<th class="ig_es_features">
								<?php echo esc_html__( 'Features', 'email-subscribers' ); ?>
							</th>
							<th class="ig_es_free_features">
								<?php echo esc_html__( 'Free', 'email-subscribers' ); ?>
							</th>
							<th class="ig_es_starter_features">
								<?php echo esc_html__( 'Pro', 'email-subscribers' ); ?>
							</th>
							<th class="ig_es_pro_features">
								<?php echo esc_html__( 'Max', 'email-subscribers' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="ig_es_feature_name">
								<strong><?php echo esc_html__( 'Plan Highlights', 'email-subscribers' ); ?></strong>
							</td>
							<td class="ig_es_free_feature_name">
								<?php echo esc_html__( 'Unlimited contacts, emails, forms & lists. Automatic welcome emails and new post notifications.', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_starter_feature_name">
								<?php echo esc_html__( 'Everything in Free + Automatic batch sending, Captcha, Advanced blocks.', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_pro_feature_name">
								<?php
								/* translators: %s. Line break */
								echo sprintf( esc_html__( 'Everything in Pro +%s Integrations, List cleanup, Cart recovery emails, Autoresponders', 'email-subscribers' ), '<br/>' );
								?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( '3rd Party SMTP Configuration', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Connect with SMTP services to reliable send transactional emails. Also supports automatic bounce handling.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								<?php echo esc_html__( 'Pepipost', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_starter_feature_name">
								<?php echo esc_html__( 'Default SMTP', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'Amazon SES, Mailgun, SendGrid, SparkPost, Postmark, Sendinblue, Mailjet & Mailersend.', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Detailed Reports/analytics', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Get a detailed email campaign report such as open rate, avg. click rate, user device, browser, country info, IP and more. Also, use built-in UTM to track metrics.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								<?php echo esc_html__( 'Overall Summary', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_starter_feature_name">
								<?php echo esc_html__( 'Overall Summary', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'Detailed Report', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Weekly Summary Email', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Receive a weekly summary of your all email campaigns & growth of your email list.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								<?php echo esc_html__( 'Basic Summary', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_starter_feature_name">
								<?php echo esc_html__( 'Basic Summary', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'Advanced Summary', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Drag and Drop Campaign Editor', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Build email campaigns faster and better with an intuitive drag and drop interface.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								<?php echo esc_html__( 'Basic Blocks', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_starter_feature_name">
								<?php echo esc_html__( 'Advanced Blocks', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'Advanced Blocks', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Automatic Batch Sending', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Send reliable emails via our cron that automates triggering pending queues every 5 minutes. Also, schedule your campaign at a specific time.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Captcha & Security', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Combat spams with the robust Captcha system built-in. Add extra security to your email list by blacklisting domains suggested by our experts.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'List Unsubscribe', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Allow subscribers to select the specific email list to opt out.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Comment Optin', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Whenever a reader leaves a blog comment, add him/her to a specific email list.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Gmail API', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Send reliable transactional emails using your Gmail API safely.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Automatic List Cleanup', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Automatically clean up bad/spam/bounced emails & maintain a healthy email list.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Custom Contact Fields', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Create custom contact fields in your forms and receive responses.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Autoresponder & Workflows', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Setup autoresponder email series based on event triggers.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Send WooCommerce Coupons', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Automatically send unique WooCommerce coupons when someone subscribes, places an order, left a product review and more.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Abandoned Cart Recovery Email', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Send automatic abandoned cart recovery emails when the visitor abandons his/her shopping cart.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Post Digest Notifications', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Send automatic blog post notification when a new blog post gets published. Also, send post digest email on a specific day.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Email Newsletter Archive', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Display a list of all existing email campaign newsletters on your website using a shortcode.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Resend Confirmation Email', 'email-subscribers' ); ?>
								<?php echo wp_kses( ES_Common::get_tooltip_html('Resend confirmation emails to those who abandon it when you\'re using the double opt-in feature.'), $allowedtags ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<span class='dashicons dashicons-yes'></span>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Membership Plugin Integration', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'Integrate with WooCommerce Memberships, MemberPress, Paid Memberships Pro, Ultimate Members.', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Popular Integrations', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								-
							</td>
							<td class="ig_es_starter_feature_name">
								-
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'Integrate with WooCommerce Abandoned Cart, Easy Digital Downloads, GiveWP Donation, Yith Wishlist Item On Sale, LearnDash, Contact Form 7, Ninja Forms, Forminator, Gravity Forms & WP Forms', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Support', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								<?php echo esc_html__( 'WordPress Forum Support', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_starter_feature_name">
								<?php echo esc_html__( 'Premium Support (Email)', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_pro_feature_name">
								<?php echo esc_html__( 'VIP Support (Email + Facebook)', 'email-subscribers' ); ?>
							</td>
						</tr>
						<tr>
							<td class="ig_es_feature_name">
								<?php echo esc_html__( 'Pricing', 'email-subscribers' ); ?>
							</td>
							<td class="ig_es_free_feature_name">
								<span><?php echo esc_html__( 'Free', 'email-subscribers' ); ?></span>
							</td>
							<td class="ig_es_starter_feature_name">
								<div class="center">
									<a class="ig_es_button small green center" href="https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=<?php echo esc_attr( $utm_medium ); ?>&utm_campaign=pro" target="_blank" style="text-transform: none;"><?php esc_html_e( 'Buy Pro', 'email-subscribers' ); ?></a>
								</div>
							</td>
							<td class="ig_es_pro_feature_name">
									<div class="center">
										<a class="ig_es_button small green center" href="https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=<?php echo esc_attr( $utm_medium ); ?>&utm_campaign=max" target="_blank" style="text-transform: none;"><?php esc_html_e( 'Buy Max', 'email-subscribers' ); ?></a>
									</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="ig_es_row" id="ig_es_testimonial-others">
				<div style="width: 70%; margin: 0 auto; display: flex; gap: 2em;">
					<div class="ig_es_column ig-es-testimonial-content">
						<img src="https://secure.gravatar.com/avatar/df87927c83228d3ab0c85a7167a708b4?s=150&d=retro&r=g" alt="Resolve">
						<h3 class="ig_es_testimonial_headline">
							<?php echo esc_html__( 'Perfect plugin for blog promotion', 'email-subscribers' ); ?>
						</h3>
						<img src="<?php echo esc_url( ES_IMG_URL . '/five-stars.png' ); ?>" class="star-ratings" alt="Star ratings">
						<div class="ig_es_testimonial_text">
						<?php
							echo esc_html__( 'This plugin works great in WordPress. Simple, yet effective. When a new blog is released, it sends a customized email along with a link to the blog title. Great to stimulate web traffic, yet sends a simple email. Have been using for over 6 months.', 'email-subscribers' );
						?>
							<p class="ig_es_testimonial_user_name">
								- Resolve
							</p>
						</div>
					</div>
					<div class="ig_es_column ig-es-testimonial-content">
						<img src="https://secure.gravatar.com/avatar/5f23eacce811025ec51f7bc95f9bd6c7?s=150&d=retro&r=g" alt="Rick Vidallon">
						<h3 class="ig_es_testimonial_headline">
							<?php echo esc_html__( 'Great for Professional Bloggers', 'email-subscribers' ); ?>
						</h3>
						<img src="<?php echo esc_url( ES_IMG_URL . '/five-stars.png' ); ?>" class="star-ratings" alt="Star ratings">
						<div class="ig_es_testimonial_text">
						<?php
							echo esc_html__( 'Great for Professional Bloggers and great support! Icegram was very responsive to our questions. I highly recommend this WordPress plugin and the PAID version is worth the cost. The paid version shows intuitive stats and drill-down information.', 'email-subscribers' );
						?>
						<p class="ig_es_testimonial_user_name">
								- Rick Vidallon
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="ig_es_row" id="ig_es_activity" style="width: 70%; margin: 0 auto; margin-top: 4em;">
				<div class="ig_es_sub_headline"> <?php echo esc_html__( 'Few hours left to grab this deal!', 'email-subscribers' ); ?> </div>
				<p> 
					<?php
					/* translators: %s: HTML tag */
					echo sprintf( esc_html__( '%1$sEmbrace the power of choice%2$s: Choose between the traditional approach of manually sending email marketing campaigns or embrace the revolutionary power of Icegram Express, enabling the seamless automation of reliable emails that effortlessly land in subscribers\' inboxes, untainted by the clutches of spam folders.
', 'email-subscribers' ), '<strong>', '</strong>' );
					?>
				</p>
				<div style="text-align: center;"><a class="ig_es_button small green center" href="#ig_es_price_column_container" style="text-transform: none;  margin-top: 1.5em;"><?php echo esc_html__( 'Select a plan now', 'email-subscribers' ); ?></a></div>
			</div>
		</div>
		<?php
	}
}

new Email_Subscribers_Pricing();
