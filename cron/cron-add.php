<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

?>

<div class="wrap">
	<?php
	$es_errors = array();
	$es_success = '';
	$es_error_found = FALSE;
	$cron_adminmail = "";

	// Form submitted, check the data
	if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes') {
		//	Just security thingy that wordpress offers us
		check_admin_referer('es_form_add');

		$es_cron_mailcount = isset($_POST['es_cron_mailcount']) ? $_POST['es_cron_mailcount'] : '';
		if($es_cron_mailcount == "0" && strlen ($es_cron_mailcount) > 0) {
			$es_errors[] = __('Please enter valid mail count.', 'email-subscribers');
			$es_error_found = TRUE;
		}

		$es_cron_adminmail = isset($_POST['es_cron_adminmail']) ? $_POST['es_cron_adminmail'] : '';

		//	No errors found, we can add this Group to the table
		if ($es_error_found == FALSE) {
			update_option('es_cron_mailcount', $es_cron_mailcount );
			update_option('es_cron_adminmail', $es_cron_adminmail );
			$es_success = __( 'Cron details successfully updated.', ES_TDOMAIN );
		}
	}

	$es_cron_url = get_option('es_c_cronurl', 'nocronurl');
	if($es_cron_url == "nocronurl") {
		$guid = es_cls_common::es_generate_guid(60);
		$home_url = home_url('/');
		$cronurl = $home_url . "?es=cron&guid=". $guid;
		add_option('es_c_cronurl', $cronurl);
		$es_cron_url = get_option('es_c_cronurl');
	}

	$es_cron_mailcount = get_option('es_cron_mailcount', '0');
	if($es_cron_mailcount == "0") {
		add_option('es_cron_mailcount', "50");
		$es_cron_mailcount = get_option('es_cron_mailcount');
	}

	$es_cron_adminmail = get_option('es_cron_adminmail', '');
	if($es_cron_adminmail == "") {
		add_option('es_cron_adminmail', "Hi Admin, \r\n\r\nCron URL has been triggered successfully on ###DATE### for the mail ###SUBJECT###. And it sent mail to ###COUNT### recipient. \r\n\r\nThank You");
		$es_cron_adminmail = get_option('es_cron_adminmail');
	}

	if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE) {
		?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?></strong></p></div><?php
	}
	if ($es_error_found == FALSE && strlen($es_success) > 0) {
		?>
		<div class="updated fade">
			<p><strong><?php echo $es_success; ?></strong></p>
		</div>
		<?php
	}
	?>

	<div class="form-wrap">
		<div id="icon-plugins" class="icon32"></div>
		<h2><?php echo __( ES_PLUGIN_DISPLAY, ES_TDOMAIN ); ?></h2>
		<h3>
			<?php echo __( 'Cron Details', ES_TDOMAIN ); ?>
			<a class="add-new-h2" target="_blank" type="button" href="<?php echo ES_FAV; ?>"><?php echo __( 'Help', ES_TDOMAIN ); ?></a>
		</h3>
		<form name="es_form" method="post" action="#" onsubmit="return _es_submit()">
			<label for="tag-link"><?php echo __( 'Cron job URL', ES_TDOMAIN ); ?></label>
			<input name="es_cron_url" type="text" id="es_cron_url" value="<?php echo $es_cron_url; ?>" maxlength="225" size="75" readonly />
			<p><?php echo __( 'This is your cron job URL. It is a readonly field and you are advised not to modify it.', ES_TDOMAIN ); ?></p>
			
			<label for="tag-link"><?php echo __( 'Mail Count', ES_TDOMAIN ); ?></label>
			<input name="es_cron_mailcount" type="text" id="es_cron_mailcount" value="<?php echo $es_cron_mailcount; ?>" maxlength="3" />
			<p><?php echo __( 'Enter number of mails you want to send per hour/trigger (Your web host has limits. We suggest 50 emails per hour to be safe).', ES_TDOMAIN ); ?></p>
			
			<label for="tag-link"><?php echo __( 'Admin Report', 'email-subscribers' ); ?></label>
			<textarea size="100" id="es_cron_adminmail" rows="7" cols="72" name="es_cron_adminmail"><?php echo esc_html(stripslashes($es_cron_adminmail)); ?></textarea>
			<p><?php echo __( 'Send above mail to admin whenever cron URL is triggered from your server.<br />(Available Keywords: ###DATE###, ###SUBJECT###, ###COUNT###)', ES_TDOMAIN ); ?></p>
			<input type="hidden" name="es_form_submit" value="yes"/>
			<p class="submit">
				<input name="publish" lang="publish" class="button add-new-h2" value="<?php echo __( 'Save', ES_TDOMAIN ); ?>" type="submit" />
			</p>
			<?php wp_nonce_field('es_form_add'); ?>
		</form>
	</div>
	<div class="tool-box">
		<h3><?php echo __( 'How to setup auto emails using CRON Job through the cPanel or Plesk?', ES_TDOMAIN ); ?></h3>
		<li><?php echo __( '<a target="_blank" href="http://www.icegram.com/documentation/es-how-to-schedule-cron-mails/">What is Cron?</a>', ES_TDOMAIN ); ?></li>
		<li><?php echo __( '<a target="_blank" href="http://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-parallels-plesk/">Setup cron job in Plesk</a>', ES_TDOMAIN ); ?></li>
		<li><?php echo __( '<a target="_blank" href="http://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/">Setup cron job in cPanal</a>', ES_TDOMAIN ); ?></li>
		<li><?php echo __( '<a target="_blank" href="http://www.icegram.com/documentation/es-what-to-do-if-hosting-doesnt-support-cron-jobs/">Hosting doesnt support cron jobs?</a>', ES_TDOMAIN ); ?></li><br>
	</div>
	<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>