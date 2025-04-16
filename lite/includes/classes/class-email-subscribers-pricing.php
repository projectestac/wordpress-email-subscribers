<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Subscribers_Pricing {

	public static function es_show_pricing() {
		$utm_medium  = apply_filters( 'ig_es_pricing_page_utm_medium', 'in_app_pricing' );
		$allowedtags = ig_es_allowed_html_tags_in_esc();

		$pro_url = 'https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=' . esc_attr( $utm_medium ) . '&utm_campaign=pro';
		$max_url = 'https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=' . esc_attr( $utm_medium ) . '&utm_campaign=max';
		
		$premium_links = array(
				'pro_url' => $pro_url,
				'max_url' => $max_url
			);
		$premium_links = apply_filters( 'ig_es_premium_links', $premium_links );
		?>
		<style>
			#wpcontent {
				padding-left: 0;
			}

			.ig_es_container {
				background-color: #FFF;
				color: #cccccc;
				width: 70%;
				border-radius: 0.438rem;
				padding: 3% 5%;
				margin: 2% auto 5% auto;
			}

			.ig_es_row {
				clear: both;
				position: relative;
			}

			.ig_es_flex {
				display: flex;
				justify-content: space-around;
			}

			.ig_es_flex2 {
				border-right: 0.006rem solid rgba(178, 170, 171, 0.185);
			}

			.ig_es_flex_content {
				padding: 0 10%;
				flex: 1;
			}

			.ig_es_flex_content h2 {
				font-weight: 600;
				font-size: 2rem;
				line-height: 1.75rem;
				color: rgb(107 114 128);
				margin: 2rem 0;
			}

			.ig_es_flex_content h4 {
				font-weight: 400;
			}

			.ig_es_flex_content span {
				font-weight: 500;
				font-size: 0.875rem;
				line-height: 1.25rem;
				color: rgb(107 114 128);
			}

			.ig_es_flex_content h4 {
				margin: 0;
			}

			.ig_es_flex_content ul {
				margin-top: 1rem;
			}

			.ig_es_flex_content button {
				outline: none;
				border: none;
				background: #5e19cf;
				color: #ffffff;
				width: 100%;
				height: 3.125rem;
				border-radius: 0.313rem;
				cursor: pointer;
				margin-top: 1rem;
				font-size: 0.875rem;
				font-family: "Inter", sans-serif;
				font-weight: 600;
			}

			.ig_es_flex_content:nth-of-type(1) button {
				background: transparent;
				border: 1px solid #5e19cf;
				color: #5e19cf;
			}
			
			ul.ig_es_features li {
				position: relative;
				padding-left: 1.875rem;
				list-style: none; /* Removes default bullet points */
				font-size: 0.875rem;
				line-height: 1.25rem;
				color: rgb(107 114 128);
				font-weight: 500;
			}

			ul.ig_es_features li:before {
				background-image: url(<?php echo esc_url( ES_PLUGIN_URL . '/lite/admin/images/new/cabf7076e8de0d8db868.svg'); ?>);
				background-position: center;
				background-repeat: no-repeat;
				background-size: 0.625rem;
				border-radius: 0.25rem;
				content: " ";
				font-size: 0.625rem;
				height: 1.375rem;
				width: 1.375rem;
				position: absolute;
				left: 0;
				top: 50%;
				transform: translateY(-50%);
			}

			ul.ig_es_features li.ig_es_cross_item:before {
				background-image: none;
				color: red;
				content: "+";
				font-size: 1.5rem;
				font-style: italic;
				font-weight: 600;
				rotate: 20deg;
			}

			.ig_es_feature_heading {
				display: flex;
			}

			.ig_es_pro_feature_img {
				background-image: url(<?php echo esc_url( ES_PLUGIN_URL . '/lite/admin/images/dollar.png'); ?>);
				background-repeat: no-repeat;
				height: 5.938rem;
				width: 5.938rem;
				background-position:center;
			}
			
			.ig_es_max_feature_img {
				background-image: url(<?php echo esc_url( ES_PLUGIN_URL . '/lite/admin/images/project-launch.png'); ?>);
				background-repeat: no-repeat;
				height: 5.938rem;
				width: 5.938rem;
				background-position:center;
			}

			.ig_es_pricing_sec {
				margin-top: 2rem;
			}

			.ig_es_price_symbol {
				font-weight: 400;
				padding-right: 0.063rem;
				font-size: 1rem;
			}

			.ig_es_pricing_sec .ig_es_pricing_value {
				font-size: 2rem;
			}

			.ig_es_pricing_sec p {
				font-weight: 500;
				font-size: 0.875rem;
				line-height: 1.25rem;
				color: rgb(107 114 128);
			}

			.ig_es_features_heading {
				margin-top: 2.5rem;
			}

			.ig_es_features_heading span.ig_es_is_bold {
				font-weight: 700;
			}

			.ig_es_main_heading {
				font-size: 2em;
				/* background-color: #252f3f !important; */
				color: #5e19cf;
				text-align: center;
				font-weight: 500;
				margin: auto;
				padding-top: 0.75em;
				padding-bottom: 0.5em;
			}
			.ig_es_discount_code {
				font-weight: 600;
				font-size: 2.5rem;
			}

			#ig-es-testimonial {
				text-align: center;
			}
			.ig-es-testimonial-content {
				width: 50%;
				margin: 0 auto;
				margin-bottom: 1em;
				background-color: #FFF;
			}
			.ig-es-testimonial-content img {
				width: 12%;
				border-radius: 9999px;
				margin: 0 auto;
			}

			#ig_es_testimonial-others .ig-es-testimonial-content img.star-ratings {
				width: 18%;
				margin-top:1em;
			}
			.ig-es-testimonial-content .ig_es_testimonial_user_name{
				font-size: 1em;
				margin-top: 0.5em;
				font-weight: bold;
			}

			.ig_es_testimonial_headline {
				margin: 0.6em 0 0;
				font-weight: 500;
				font-size: 1.5em;
			}
			.ig_es_testimonial_text {
				text-align: left;
				font-size: 1.2em;
				line-height: 1.6;
				padding: 1em 1em 0;
			}
			.ig_es_column {
				padding: 2em;
				margin: 0 1em;
				border: 0.063rem solid rgba(0, 0, 0, 0.1);
				text-align: center;
				color: rgba(0, 0, 0, 0.75);
			}
			table.ig_es_feature_table {
				width: 90%;
				margin-left: 5%;
				margin-right: 5%;
			}

			#ig_es_comparison_table{
				margin-top: 4em;
			}

			.ig_es_sub_headline {
				font-size: 1.6em;
				font-weight: 400;
				color: #5e19cf;
				text-align: center;
				line-height: 1.5em;
				margin: 0 auto 1em;
			}

			table.ig_es_feature_table th,
			table.ig_es_feature_table tr,
			table.ig_es_feature_table td,
			table.ig_es_feature_table td span {
				padding: 0.5em;
				text-align: center;
				background-color: transparent;
				vertical-align: middle;
			}
			table.ig_es_feature_table,
			table.ig_es_feature_table tr{
				border: 0.063rem solid #eaeaea;
			}
			table.ig_es_feature_table.widefat th,
			table.ig_es_feature_table.widefat td {
				color: #515151;
			}
			table.ig_es_feature_table th {
				font-weight: bolder;
				font-size: 1.3em;
			}
			table.ig_es_feature_table tr td {
				font-size: 0.938rem;
			}
			table.ig_es_feature_table th.ig_es_features {
				/* background-color: #F4F4F4; */
				/* color: #A1A1A1; */
				width:20rem;
			}
			table.ig_es_feature_table th.ig_es_free_features {
				/* background-color: #F7E9C8; */
				/* color: #D39E22; */
			}
			table.ig_es_feature_table th.ig_es_pro_features{
				/* background-color: #CCFCBF; */
				/* color: #14C38E; */
				width:16rem;
			}
			table.ig_es_feature_table th.ig_es_starter_features {
				/* background-color: #DCDDFC; */
				/* color: #6875F5; */
			}
			table.ig_es_feature_table td{
				padding: 0.5rem;
			}
			table.ig_es_feature_table td.ig_es_feature_name {
				text-transform: capitalize;
				padding:1rem 2rem;
			}
			table.ig_es_feature_table td.ig_es_free_feature_name {
				background-color: #f9fafb;
				padding:1rem 2rem;
			}
			table.ig_es_feature_table td.ig_es_starter_feature_name {
				background-color: #f9fafb;
				padding:1rem 3rem;
			}
			table.ig_es_feature_table td.ig_es_pro_feature_name {
				background-color: #f9fafb;
				padding:1rem 2rem;
			}

			.ig_es_button {
				color: #FFFFFF;
				padding: 0.938rem 2rem;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 1rem;
				font-weight: 500;
				margin: 2rem 0.125rem 1rem 0.125rem;
				cursor: pointer;
			}
			.ig_es_button.green {
				background: #5e19cf;
				border-color: #5e19cf;
			}
			.ig_es_button.green:hover {
				/* background: #66C78E; */
				/* border-color: #66C78E; */
				color:#fFf;
			}

			.ig_es_button.small {
				text-transform: uppercase;
				box-shadow: none;
				padding: 0.8rem;
				font-size: 1rem;
				border-radius: 0.25rem;
				margin-top: 1rem;
				font-weight: 600;
			}

			.dashicons.dashicons-awards {
				margin-right: 0.25rem;
				color: #15576F;
				font-size: 1.25rem;
			}
		</style>
		<div class="ig_es_main_heading">
			<div style="display: inline-flex;">
				<div style="line-height: 2.5rem;">
					<?php
						/* translators: %s: Offer text */
						echo sprintf( esc_html__( '🎉 Congratulations! You unlocked %s on Icegram Express Premium plans! 🎉', 'email-subscribers' ), '<span class="ig_es_discount_code">' . esc_html__( '25% off', 'email-subscribers' ) . '</span>' );
					?>
				</div>
			</div>
			<div style="padding-top: 1em;font-size: 0.5em;"><?php echo esc_html__( '⏰ Limited time offer', 'email-subscribers' ); ?></div>
		</div>
		<div class="ig_es_container ig_es_flex">
			<div class="ig_es_flex_content ig_es_flex2">
				<div class="ig_es_feature_heading">
					<div class="ig_es_pro_feature_img"></div>
					<h2>Pro</h2>
				</div>
				<div class="ig_es_pricing_sec">
					<h3 class="ig_es_pricing_info">
						<del class="" style="padding-right: 1em;font-size: 1.3em;color: #b70c0c;">$129</del>
						<span class="ig_es_pricing_value">$96.75</span>
					</h3>
					<p>Secure forms, CAPTCHA protection, and better engagement!</p>
				</div>
				<a class="ig_es_button small green center" href="https://www.icegram.com/?buy-now=39043&amp;qty=1&amp;coupon=es-upgrade-25&amp;page=6&amp;with-cart=1&amp;utm_source=ig_es&amp;utm_medium=in_app_pricing&amp;utm_campaign=pro" target="_blank" style="text-transform: none;width:90%;padding:1em 2em;background: transparent;border: 1px solid #5e19cf;color: #5e19cf;margin-top:3em;">Buy Pro</a>
				<p class="ig_es_features_heading">
					<span class="ig_es_is_bold">All Free plan features,</span>
					<span>plus:</span>
				</p>
				<ul class="ig_es_features">
					<li>Captcha & Security</li>
					<li>Icegram Managed Spammer Blacklist	</li>
					<li>Single / Double Optin</li>
					<li>Form Plugin Integration	</li>
					<li>Unsubscribe	from list</li>
				</ul>
			</div>

			<div class="ig_es_flex_content">
				<div class="ig_es_feature_heading">
					<div class="ig_es_max_feature_img"></div>
					<h2>Max</h2>
				</div>
				<div class="ig_es_pricing_sec">
				<h3 class="ig_es_pricing_info">
						<del class="" style="padding-right: 1em;font-size: 1.3em;color: #b70c0c;">$229</del>
						<span class="ig_es_pricing_value">$171.75</span>
					</h3>
					<p>Boost automation & conversions with Max</p>
				</div>
				<a class="ig_es_button small green center" href="https://www.icegram.com/?buy-now=404335&amp;qty=1&amp;coupon=es-upgrade-25&amp;page=6&amp;with-cart=1&amp;utm_source=ig_es&amp;utm_medium=in_app_pricing&amp;utm_campaign=max" target="_blank" style="text-transform: none; width:90%; padding:1em 2em;margin-top:3em;">Buy Max</a>
				<p class="ig_es_features_heading">
					<span class="ig_es_is_bold">All Free plan features,</span>
					<span>plus:</span>
				</p>
				<ul class="ig_es_features">
					<li>Automatic Batch Sending	</li>
					<li>Automatic Bounce Handling	</li>
					<li>Post Digests	</li>
					<li>Automation Workflows	</li>
					<li>Abandoned Cart Recovery Sequences	</li>
				</ul>
				
			</div>
		</div>
		<div class="ig_es_row" id="ig_es_testimonial-others">
			<div style="width: 95%; margin: 0 auto; display: flex; gap: 2em;">
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
				<div class="ig_es_column ig-es-testimonial-content">
					<img src="https://secure.gravatar.com/avatar/8e921f159e986459bb39e5a7aa25b0b453d04a444c8155612f3b96c2a94ba172?s=200&d=retro&r=g" alt="Rick Vidallon">
					<h3 class="ig_es_testimonial_headline">
						<?php echo esc_html__( 'Perfect subscriber solution: Worked where other plugins failed…–', 'email-subscribers' ); ?>
					</h3>
					<img src="<?php echo esc_url( ES_IMG_URL . '/five-stars.png' ); ?>" class="star-ratings" alt="Star ratings">
					<div class="ig_es_testimonial_text">
					<?php
						echo esc_html__( 'Easy setup and instant action. The best part? It actually delivers results! Unlike those big shots, it nails blog entry notifications. Plus, pair it with Icegram Collect for an even better form makeover!', 'email-subscribers' );
					?>
					<p class="ig_es_testimonial_user_name">
							- Lauren Devine
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="ig_es_row" id="ig_es_comparison_table">
			<div class="ig_es_sub_headline"><?php echo esc_html__( ' More powerful features with Icegram Express Premium!', 'email-subscribers' ); ?></div>
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
								<a class="ig_es_button small green center" href="<?php echo esc_url($premium_links['pro_url']); ?>" target="_blank" style="text-transform: none;"><?php esc_html_e( 'Buy Pro', 'email-subscribers' ); ?></a>
							</div>
						</td>
						<td class="ig_es_pro_feature_name">
								<div class="center">
									<a class="ig_es_button small green center" href="<?php echo esc_url($premium_links['max_url']); ?>" target="_blank" style="text-transform: none;"><?php esc_html_e( 'Buy Max', 'email-subscribers' ); ?></a>
								</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}

new Email_Subscribers_Pricing();
