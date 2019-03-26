<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<script type="text/javascript">
        jQuery(function () {
            jQuery("form[name=klawoo_subscribe]").submit(function (e) {

                e.preventDefault();

                jQuery('#klawoo_response').html('');
                jQuery('#klawoo_response').show();

                params = jQuery("form[name=klawoo_subscribe]").serializeArray();
                params.push( {name: 'action', value: 'es_klawoo_subscribe' });

                jQuery.ajax({
                    method: 'POST',
                    type: 'text',
                    url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                    async: false,
                    data: params,
                    success: function(response) {

                        if (response != '') {
                            var parser = new DOMParser()
                            var el = parser.parseFromString(response, "text/xml");

                            jQuery('#klawoo_response').html(el.childNodes[0].firstChild.nextElementSibling.innerHTML);

                            jQuery('.es-emm-optin #name').val('');
                            jQuery('.es-emm-optin #email').val('');
                            jQuery('.es-emm-optin #es-gdpr-agree').attr('checked', false);
                            setTimeout(function() {
                                jQuery('#klawoo_response').hide('slow');
                            }, 2000);

                        } else {
                            jQuery('#klawoo_response').html('error!');
                        }
                    }
                });

            });
        });
</script>

<div class="wrap">
	<div class="about-header">
		<div class="es-upper">
			<div class="es-info">
				<?php 
				$es_upgrade_to_4 = get_option('current_sa_email_subscribers_db_version');
				if(empty($es_upgrade_to_4)){?>
					<h2><?php echo __( "Congratulations! You've successfully upgraded to " . ES_PLUGIN_VERSION , 'email-subscribers' ); ?></h2>
					<ul><strong><?php _e('Here\'s a quick look at changes within the plugin:', 'email-subscribers'); ?></strong>
						<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '1. Newsletters are now <b>Broadcasts</b>. Broadcasts and Post notifications are now merged in <a href="%s" target="_blank">Campaigns</a>', 'email-subscribers' ), admin_url( 'admin.php?page=es_campaigns' )); ?></li>
						<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '2. Subscribers are now called <b>Contacts</b> and part of an <a href="%s" target="_blank">Audience</a>', 'email-subscribers' ), admin_url( 'admin.php?page=es_subscribers' )); ?></li>
						<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '3. Groups are now called <a href="%s" target="_blank">Lists</a>', 'email-subscribers' ), admin_url( 'admin.php?page=es_lists' )); ?></li>
						<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '4. Find <a href="%s" target="_blank">Forms</a> here', 'email-subscribers' ), admin_url( 'admin.php?page=es_forms' )); ?></li>
					</ul>
					<a href="https://www.icegram.com/email-subscribers-plugin-redesign/?utm_source=es&utm_medium=in_app&utm_campaign=es_4" target="_blank" class="button button-main"><?php _e('Explore all changes', 'email-subscribers'); ?></a>
				<?php }else{?>
					<h2><?php echo __( 'Welcome to the Email Subscribers Community!', 'email-subscribers' ); ?></h2>
					<div class="es-about-line"><?php _e('Email Subscribers is a complete newsletter plugin which lets you collect leads, send automated new blog post notification emails, create & send newsletters and manage all this in one single place.', 'email-subscribers')?></div>
					<div class="es-about-text"><?php echo __( 'We hope our plugin adds to your success <img draggable="false" class="emoji" alt="🏆" src="https://s.w.org/images/core/emoji/11/svg/1f3c6.svg">', 'email-subscribers' ); ?></div>
					<div class="es-notify-about-new-post-1"><?php echo __( 'To get started, we did some initial setup to save your time <img draggable="false" class="emoji" alt="😊" src="https://s.w.org/images/core/emoji/11/svg/1f60a.svg">', 'email-subscribers' ); ?></div>
					<ul>
						<li class="es-notify-about-new-post-2"><?php echo __( '1. Created a lead collecting form and added it the default widget area in your WP admin', 'email-subscribers' ); ?></li>
						<li class="es-notify-about-new-post-2"><?php echo __( '2. Created a "Test" subscriber list and added "', 'email-subscribers' ) . $admin_email . __( '" to it.', 'email-subscribers' ); ?></li>
						<li class="es-notify-about-new-post-2"><?php echo __( '3. Sent a test post notification, test broadcasts to the test subscriber list.', 'email-subscribers' ); ?></li>
						<li class="es-notify-about-new-post-2"><?php echo __( '4. Created a first form.', 'email-subscribers' ); ?></li>
					</ul>

				<?php }?>
				<div class="es-quick-links-wrapper" >
					<h3><?php _e('Here are some quick links', 'email-subscribers'); ?></h3>
					<span class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_subscribers' )?>" ><?php _e('Audience', 'email-subscribers')?></a></span>
					<span class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_forms' )?>" ><?php _e('Forms', 'email-subscribers')?></a></span>
					<span class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_campaigns' )?>" ><?php _e('Campaigns', 'email-subscribers')?></a></span>
					<span class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_lists' )?>" ><?php _e('Lists', 'email-subscribers')?></a></span>
					<span class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_reports' )?>" ><?php _e('Reports', 'email-subscribers')?></a></span>
				</div>
				<div class="es-help-wrap" >
					<div class="subscribe-form">
		                <h3><?php echo __( 'Add Subscribe form', 'email-subscribers' ); ?></h3>
		                <p><?php echo __( 'Use any of the following 3 methods :', 'email-subscribers' ); ?></p>
		                <ul>
		                    <li><?php echo __( 'Shortcode in any page/post : <code>[email-subscribers-form id="{form-id}"]</code> ', 'email-subscribers' ); ?></li>
		                    <li><?php echo __( 'Go to Appearance -> Widgets. Click on widget Email subscribers and drag it to the widget area', 'email-subscribers' ); ?></li>
		                    <li><?php echo __( 'Paste below PHP code to your desired location :', 'email-subscribers' ); ?> <p><code><?php echo esc_html( '<?php es_subbox($namefield = "YES", $desc = "", $group = "Public"); ?>' ); ?></code></p></li>
		                </ul>
		            </div>
				</div>
				
			</div>

			<div class="wrap klawoo-form">
				<table class="form-table">
					<tr>
						<td colspan="3" class="es-optin-headline"><?php echo __( 'Build your list and succeed with email marketing <div>in 5 short weeks</div>', 'email-subscribers' ); ?></td>
					</tr>
					<tr>
						<td colspan="3" class="es-emm-image"><img alt="Email Marketing Mastery" src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>images/email-marketing-mastery.png"/></td>
					</tr>
					<tr>
						<td colspan="3"  class="es-emm-text">
							<p><?php echo __( 'Do you want to build your list, keep off spam, write emails that people open and click through? Do you want to build your brand and nurture an amazing tribe?', 'email-subscribers' ); ?></p>
							<p><b><?php echo __( 'Enter your name and email on the form to get it all.', 'email-subscribers' ); ?></b></p>
						</td>
					</tr>
					<tr>
						<td colspan="3" class="es-emm-optin">
							<form name="klawoo_subscribe" action="#" method="POST" accept-charset="utf-8">
								<input class="es-ltr" type="text" name="name" id="name" placeholder="Your Name" />
								<input class="es-ltr" type="text" name="email" id="email" placeholder="Your Email" /> <br />
								<input type="hidden" name="list" value="hN8OkYzujUlKgDgfCTEcIA"/>
	                            <input type="checkbox" name="es-gdpr-agree" id ="es-gdpr-agree" value="1" required="required">
	                            <label for="es-gdpr-agree"><?php echo sprintf(__( 'I have read and agreed to your %s.', 'email-subscribers' ), '<a href="https://www.icegram.com/privacy-policy/" target="_blank">' . __( 'Privacy Policy', 'email-subscribers' ) . '</a>' ); ?></label>
	                            <br /><br />
								<input type="submit" name="submit" id="submit" class="button button-hero" value="<?php echo __( 'Subscribe', 'email-subscribers' ); ?>">
								<br><br>
	                            <p id="klawoo_response"></p>
							</form>
						</td>
					</tr>
                   <tr>
						<td colspan="3"  class="es-emm-text">
                            <div class="column">
                            	<p><strong><?php _e('<span style="color:#ff6f7b">Join our</span> Email Subscribers Secret Club!','email-subscribers'); ?></strong></p>
                                <p><?php _e('Be a part of development, share your valuable feedback and get early access to our upcoming <strong>Email Subscribers 5.0</>', 'email-subscribers'); ?></p>
                                <p><a style="text-decoration: none"  target="_blank" href="https://www.facebook.com/groups/2298909487017349/"><i class="dashicons dashicons-es dashicons-facebook"></i></a></p>
                            </div>
						</td>
					</tr>
				</table>
				
			</div>
		</div>
		<div class="es-lower">
			<div class="es-version">
				<h3><?php echo __( 'Questions? Need Help?', 'email-subscribers' ); ?></h3>
				<a href="https://wordpress.org/support/plugin/email-subscribers" target="_blank"><?php echo __( 'Contact Us', 'email-subscribers' ); ?></a>
				<h5 class="es-badge"><?php echo sprintf( __( 'Version: %s', 'email-subscribers' ), $es_current_version ); ?></h5>
			</div>
		</div>
		
		
	</div>
</div>
