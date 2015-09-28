<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;
$cron_adminmail = "";

// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_add');
	
	$es_cron_mailcount = isset($_POST['es_cron_mailcount']) ? $_POST['es_cron_mailcount'] : '';
	if($es_cron_mailcount == "0" && strlen ($es_cron_mailcount) > 0)
	{
		$es_errors[] = __('Please enter valid mail count.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	$es_cron_adminmail = isset($_POST['es_cron_adminmail']) ? $_POST['es_cron_adminmail'] : '';

	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{
		update_option('es_cron_mailcount', $es_cron_mailcount );
		update_option('es_cron_adminmail', $es_cron_adminmail );
		$es_success = __('Cron details successfully updated.', ES_TDOMAIN);
	}
}

$es_cron_url = get_option('es_c_cronurl', 'nocronurl');
if($es_cron_url == "nocronurl")
{
	$guid = es_cls_common::es_generate_guid(60);
	$home_url = home_url('/');
	$cronurl = $home_url . "?es=cron&guid=". $guid;
	add_option('es_c_cronurl', $cronurl);
	$es_cron_url = get_option('es_c_cronurl');
}

$es_cron_mailcount = get_option('es_cron_mailcount', '0');
if($es_cron_mailcount == "0")
{
	add_option('es_cron_mailcount', "50");
	$es_cron_mailcount = get_option('es_cron_mailcount');
}
$es_cron_adminmail = get_option('es_cron_adminmail', '');
if($es_cron_adminmail == "")
{
	add_option('es_cron_adminmail', "Hi Admin, \r\n\r\nCron URL has been triggered successfully on ###DATE### for the mail ###SUBJECT###. And it sent mail to ###COUNT### recipient. \r\n\r\nThank You");
	$es_cron_adminmail = get_option('es_cron_adminmail');
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?></strong></p></div><?php
}
if ($es_error_found == FALSE && strlen($es_success) > 0)
{
	?>
	<div class="updated fade">
		<p><strong><?php echo $es_success; ?></strong></p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>cron/cron.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Cron Details', ES_TDOMAIN); ?></h3>
	<form name="es_form" method="post" action="#" onsubmit="return _es_submit()"  >
      
      <label for="tag-link"><?php _e('Cron job URL', ES_TDOMAIN); ?></label>
      <input name="es_cron_url" type="text" id="es_cron_url" value="<?php echo $es_cron_url; ?>" maxlength="225" size="75"  />
      <p><?php _e('Please find your cron job URL. This is read only field not able to modify from admin.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-link"><?php _e('Mail Count', ES_TDOMAIN); ?></label>
      <input name="es_cron_mailcount" type="text" id="es_cron_mailcount" value="<?php echo $es_cron_mailcount; ?>" maxlength="3" />
      <p><?php _e('Enter number of mails you want to send per hour/trigger.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-link"><?php _e('Admin Report', ES_TDOMAIN); ?></label>
	  <textarea size="100" id="es_cron_adminmail" rows="6" cols="73" name="es_cron_adminmail"><?php echo esc_html(stripslashes($es_cron_adminmail)); ?></textarea>
	  <p><?php _e('Send above mail to admin whenever cron URL triggered in your server.', ES_TDOMAIN); ?><br />(Keywords: ###DATE###, ###SUBJECT###, ###COUNT###)</p>

      <input type="hidden" name="es_form_submit" value="yes"/>
      <p class="submit">
        <input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Submit', ES_TDOMAIN); ?>" type="submit" />
        <input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />
        <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
      </p>
	  <?php wp_nonce_field('es_form_add'); ?>
    </form>
</div>
<div class="tool-box">
	<h3><?php _e('How to setup auto emails?', ES_TDOMAIN); ?></h3>
	<p><?php _e('I strongly recommend you to use "Send mail via cron job" option to send your newsletters and notification. The following link explains how to create a CRON job through the cPanel or Plesk.', ES_TDOMAIN); ?></p>
	<p><?php _e('How to setup auto emails (cron job) in Plesk', ES_TDOMAIN); ?> <a target="_blank" href="#"><?php _e('Click here', ES_TDOMAIN); ?></a>.</p>
	<p><?php _e('How to setup auto emails (cron job) in cPanal', ES_TDOMAIN); ?> <a target="_blank" href="#"><?php _e('Click here', ES_TDOMAIN); ?></a>.</p>
	<p><?php _e('Hosting doesnt support cron jobs?', ES_TDOMAIN); ?> <a target="_blank" href="#"><?php _e('Click here', ES_TDOMAIN); ?></a> for solution.</p>
</div>
</div>