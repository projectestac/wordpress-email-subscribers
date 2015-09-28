<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;
	
$result = es_cls_settings::es_setting_count(1);
if ($result != '1')
{
	?><div class="error fade"><p><strong><?php _e('Oops, selected details doesnt exist.', ES_TDOMAIN); ?></strong></p></div><?php
	$form = array(
		'es_c_id' => '',
		'es_c_fromname' => '',
		'es_c_fromemail' => '',
		'es_c_mailtype' => '',
		'es_c_adminmailoption' => '',
		'es_c_adminemail' => '',
		'es_c_adminmailsubject' => '',
		'es_c_adminmailcontant' => '',
		'es_c_usermailoption' => '',
		'es_c_usermailsubject' => '',
		'es_c_usermailcontant' => '',
		'es_c_optinoption' => '',
		'es_c_optinsubject' => '',
		'es_c_optincontent' => '',
		'es_c_optinlink' => '',
		'es_c_unsublink' => '',
		'es_c_unsubtext' => '',
		'es_c_unsubhtml' => '',
		'es_c_subhtml' => '',
		'es_c_message1' => '',
		'es_c_message2' => '',
		'es_c_sentreport' => ''
	);
}
else
{
	$es_errors = array();
	$es_success = '';
	$es_error_found = FALSE;
	
	$data = array();
	$data = es_cls_settings::es_setting_select(1);
	
	$es_c_sentreport_subject = '';
	$es_c_sentreport = '';	
	$es_c_sentreport_subject = get_option('es_c_sentreport_subject', 'nosubjectexists');
	$es_c_sentreport = get_option('es_c_sentreport', 'nooptionexists');
	if($es_c_sentreport_subject == "nosubjectexists")
	{	
		$es_sent_report_subject = es_cls_common::es_sent_report_subject();
		add_option('es_c_sentreport_subject', $es_sent_report_subject);
		$es_c_sentreport_subject = $es_sent_report_subject;
	}
	
	if($es_c_sentreport == "nooptionexists")
	{		
		$es_sent_report_plain = es_cls_common::es_sent_report_plain();
		add_option('es_c_sentreport', $es_sent_report_plain);
		$es_c_sentreport = $es_sent_report_plain;
	}
	
	// Preset the form fields
	$form = array(
		'es_c_id' => $data['es_c_id'],
		'es_c_fromname' => $data['es_c_fromname'],
		'es_c_fromemail' => $data['es_c_fromemail'],
		'es_c_mailtype' => $data['es_c_mailtype'],
		'es_c_adminmailoption' => $data['es_c_adminmailoption'],
		'es_c_adminemail' => $data['es_c_adminemail'],
		'es_c_adminmailsubject' => $data['es_c_adminmailsubject'],
		'es_c_adminmailcontant' => $data['es_c_adminmailcontant'],
		'es_c_usermailoption' => $data['es_c_usermailoption'],
		'es_c_usermailsubject' => $data['es_c_usermailsubject'],
		'es_c_usermailcontant' => $data['es_c_usermailcontant'],
		'es_c_optinoption' => $data['es_c_optinoption'],
		'es_c_optinsubject' => $data['es_c_optinsubject'],
		'es_c_optincontent' => $data['es_c_optincontent'],
		'es_c_optinlink' => $data['es_c_optinlink'],
		'es_c_unsublink' => $data['es_c_unsublink'],
		'es_c_unsubtext' => $data['es_c_unsubtext'],
		'es_c_unsubhtml' => $data['es_c_unsubhtml'],
		'es_c_subhtml' => $data['es_c_subhtml'],
		'es_c_message1' => $data['es_c_message1'],
		'es_c_message2' => $data['es_c_message2'],
		'es_c_sentreport' => $es_c_sentreport,
		'es_c_sentreport_subject' => $es_c_sentreport_subject
	);
}

	
// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_edit');
	
	$form['es_c_fromname'] = isset($_POST['es_c_fromname']) ? $_POST['es_c_fromname'] : '';
	if ($form['es_c_fromname'] == '')
	{
		$es_errors[] = __('Please enter sender of notifications from name.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	$form['es_c_fromemail'] = isset($_POST['es_c_fromemail']) ? $_POST['es_c_fromemail'] : '';
	if ($form['es_c_fromemail'] == '')
	{
		$es_errors[] = __('Please enter sender of notifications from email.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	$home_url = home_url('/');
	$optinlink = $home_url . "?es=optin&db=###DBID###&email=###EMAIL###&guid=###GUID###";
	$unsublink = $home_url . "?es=unsubscribe&db=###DBID###&email=###EMAIL###&guid=###GUID###"; 
			
	$form['es_c_mailtype'] = isset($_POST['es_c_mailtype']) ? $_POST['es_c_mailtype'] : '';
	$form['es_c_adminmailoption'] = isset($_POST['es_c_adminmailoption']) ? $_POST['es_c_adminmailoption'] : '';
	$form['es_c_adminemail'] = isset($_POST['es_c_adminemail']) ? $_POST['es_c_adminemail'] : '';
	$form['es_c_adminmailsubject'] = isset($_POST['es_c_adminmailsubject']) ? $_POST['es_c_adminmailsubject'] : '';
	$form['es_c_adminmailcontant'] = isset($_POST['es_c_adminmailcontant']) ? $_POST['es_c_adminmailcontant'] : '';
	$form['es_c_usermailoption'] = isset($_POST['es_c_usermailoption']) ? $_POST['es_c_usermailoption'] : '';
	$form['es_c_usermailsubject'] = isset($_POST['es_c_usermailsubject']) ? $_POST['es_c_usermailsubject'] : '';
	$form['es_c_usermailcontant'] = isset($_POST['es_c_usermailcontant']) ? $_POST['es_c_usermailcontant'] : '';
	$form['es_c_optinoption'] = isset($_POST['es_c_optinoption']) ? $_POST['es_c_optinoption'] : '';
	$form['es_c_optinsubject'] = isset($_POST['es_c_optinsubject']) ? $_POST['es_c_optinsubject'] : '';
	$form['es_c_optincontent'] = isset($_POST['es_c_optincontent']) ? $_POST['es_c_optincontent'] : '';
	$form['es_c_optinlink'] = $optinlink; //isset($_POST['es_c_optinlink']) ? $_POST['es_c_optinlink'] : '';
	$form['es_c_unsublink'] = $unsublink; //isset($_POST['es_c_unsublink']) ? $_POST['es_c_unsublink'] : '';
	$form['es_c_unsubtext'] = isset($_POST['es_c_unsubtext']) ? $_POST['es_c_unsubtext'] : '';
	$form['es_c_unsubhtml'] = isset($_POST['es_c_unsubhtml']) ? $_POST['es_c_unsubhtml'] : '';
	$form['es_c_subhtml'] = isset($_POST['es_c_subhtml']) ? $_POST['es_c_subhtml'] : '';
	$form['es_c_message1'] = isset($_POST['es_c_message1']) ? $_POST['es_c_message1'] : '';
	$form['es_c_message2'] = isset($_POST['es_c_message2']) ? $_POST['es_c_message2'] : '';
	$form['es_c_id'] = isset($_POST['es_c_id']) ? $_POST['es_c_id'] : '1';

	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{	
		$action = "";
		$action = es_cls_settings::es_setting_update($form);
		if($action == "sus")
		{
			$es_success = __('Details was successfully updated.', ES_TDOMAIN);
		}
		else
		{
			$es_error_found == TRUE;
			$es_errors[] = __('Oops, details not update.', ES_TDOMAIN);
		}
	}
	
	$form['es_c_sentreport'] = isset($_POST['es_c_sentreport']) ? $_POST['es_c_sentreport'] : '';
	update_option('es_c_sentreport', $form['es_c_sentreport'] );
	$form['es_c_sentreport_subject'] = isset($_POST['es_c_sentreport_subject']) ? $_POST['es_c_sentreport_subject'] : '';
	update_option('es_c_sentreport_subject', $form['es_c_sentreport_subject'] );
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?>
		<div class="error fade">
			<p><strong><?php echo $es_errors[0]; ?></strong></p>
		</div>
	<?php
}
if ($es_error_found == FALSE && strlen($es_success) > 0)
{
	?>
	<div class="updated fade">
		<p>
			<strong>
				<?php echo $es_success; ?> 
				<a href="<?php echo get_option('siteurl'); ?>/wp-admin/admin.php?page=es-settings"><?php _e('Click here', ES_TDOMAIN); ?></a>
				<?php _e(' to view the details', ES_TDOMAIN); ?>
			</strong>
		</p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>settings/settings.js"></script>
<style>
.form-table th {
    width: 350px;
}
</style>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Settings', ES_TDOMAIN); ?></h3>
	<form name="es_form" method="post" action="#" onsubmit="return _es_submit()"  >
	<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label for="elp"><?php _e('Sender of notifications', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Choose a FROM name and FROM email address for all notifications emails from this plugin.', ES_TDOMAIN); ?></p></label>
			</th>
			<td>
				<input name="es_c_fromname" type="text" id="es_c_fromname" value="<?php echo esc_html(stripslashes($form['es_c_fromname'])); ?>" maxlength="225" />
				<input name="es_c_fromemail" type="text" id="es_c_fromemail" value="<?php echo esc_html(stripslashes($form['es_c_fromemail'])); ?>" size="35" maxlength="225" />
			</td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Mail type', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Option 1 & 2 is to send mails with default Wordpress method wp_mail(). Option 3 & 4 is to send mails with PHP method mail()', ES_TDOMAIN); ?></p></label>
			</th>
			<td>
				<select name="es_c_mailtype" id="es_c_mailtype">
					<option value='WP HTML MAIL' <?php if($form['es_c_mailtype'] == 'WP HTML MAIL') { echo 'selected' ; } ?>>1. WP HTML MAIL</option>
					<option value='WP PLAINTEXT MAIL' <?php if($form['es_c_mailtype'] == 'WP PLAINTEXT MAIL') { echo 'selected' ; } ?>>2. WP PLAINTEXT MAIL</option>
					<option value='PHP HTML MAIL' <?php if($form['es_c_mailtype'] == 'PHP HTML MAIL') { echo 'selected' ; } ?>>3. PHP HTML MAIL</option>
					<option value='PHP PLAINTEXT MAIL' <?php if($form['es_c_mailtype'] == 'PHP PLAINTEXT MAIL') { echo 'selected' ; } ?>>4. PHP PLAINTEXT MAIL</option>
				</select>
			</td>
		</tr>
		<!-------------------------------------------------------------------------------->
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Opt-in option', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Double Opt In, means subscribers need to confirm their email address by an activation link sent them on a activation email message. Single Opt In, means subscribers do not need to confirm their email address.', ES_TDOMAIN); ?></p></label>
			</th>
			<td>			
				<select name="es_c_optinoption" id="es_c_optinoption">
					<option value='Double Opt In' <?php if($form['es_c_optinoption'] == 'Double Opt In') { echo 'selected' ; } ?>>Double Opt In</option>
					<option value='Single Opt In' <?php if($form['es_c_optinoption'] == 'Single Opt In') { echo 'selected' ; } ?>>Single Opt In</option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Opt-in mail subject (Confirmation mail)', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the subject for Double Opt In mail. This will send whenever subscriber added email into our database.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_optinsubject" type="text" id="es_c_optinsubject" value="<?php echo esc_html(stripslashes($form['es_c_optinsubject'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Opt-in mail content (Confirmation mail)', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the content for Double Opt In mail. This will send whenever subscriber added email into our database.', ES_TDOMAIN); ?> (Keyword: ###NAME###)</p></label>
			</th>
			<td><textarea size="100" id="es_c_optincontent" rows="10" cols="58" name="es_c_optincontent"><?php echo esc_html(stripslashes($form['es_c_optincontent'])); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Opt-in link (Confirmation link)', ES_TDOMAIN); ?><p class="description">
				<?php _e('Double Opt In confirmation link. You no need to change this value.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_optinlink" type="text" id="es_c_optinlink" value="<?php echo esc_html(stripslashes($form['es_c_optinlink'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Text to display after email subscribed successfully', ES_TDOMAIN); ?>
				<p class="description"><?php _e('This text will display once user clicked email confirmation link from opt-in (confirmation) email content.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><textarea size="100" id="es_c_subhtml" rows="4" cols="58" name="es_c_subhtml"><?php echo esc_html(stripslashes($form['es_c_subhtml'])); ?></textarea></td>
		</tr>
		<!-------------------------------------------------------------------------------->
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Subscriber welcome email', ES_TDOMAIN); ?>
				<p class="description"><?php _e('To send welcome mail to subscriber, This option must be set to YES.', ES_TDOMAIN); ?></p></label>
			</th>
			<td>			
			<select name="es_c_usermailoption" id="es_c_usermailoption">
				<option value='YES' <?php if($form['es_c_usermailoption'] == 'YES') { echo 'selected' ; } ?>>YES</option>
				<option value='NO' <?php if($form['es_c_usermailoption'] == 'NO') { echo 'selected' ; } ?>>NO</option>
			</select>
			</td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Welcome mail subject', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the subject for subscriber welcome mail. This will send whenever email subscribed (confirmed) successfully.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_usermailsubject" type="text" id="es_c_usermailsubject" value="<?php echo esc_html(stripslashes($form['es_c_usermailsubject'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Subscriber welcome mail content', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the content for subscriber welcome mail. This will send whenever email subscribed (confirmed) successfully.', ES_TDOMAIN); ?> (Keyword: ###NAME###)</p>
			</label>
			</th>
			<td><textarea size="100" id="es_c_usermailcontant" rows="10" cols="58" name="es_c_usermailcontant"><?php echo esc_html(stripslashes($form['es_c_usermailcontant'])); ?></textarea></td>
		</tr>
		<!-------------------------------------------------------------------------------->
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Mail to admin', ES_TDOMAIN); ?>
				<p class="description"><?php _e('To send admin notifications for new subscriber, This option must be set to YES.', ES_TDOMAIN); ?></p></label>
			</th>
			<td>			
			<select name="es_c_adminmailoption" id="es_c_adminmailoption">
				<option value='YES' <?php if($form['es_c_adminmailoption'] == 'YES') { echo 'selected' ; } ?>>YES</option>
				<option value='NO' <?php if($form['es_c_adminmailoption'] == 'NO') { echo 'selected' ; } ?>>NO</option>
			</select>
			</td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Admin email addresses', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the admin email addresses that should receive notifications (separate by comma).', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_adminemail" type="text" id="es_c_adminemail" value="<?php echo esc_html(stripslashes($form['es_c_adminemail'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Admin mail subject', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the subject for admin mail. This will send whenever new email added and confirmed into our database.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_adminmailsubject" type="text" id="es_c_adminmailsubject" value="<?php echo esc_html(stripslashes($form['es_c_adminmailsubject'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Admin mail content', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the mail content for admin. This will send whenever new email added and confirmed into our database.', ES_TDOMAIN); ?> (Keyword: ###NAME###, ###EMAIL###)</p></label>
			</th>
			<td><textarea size="100" id="es_c_adminmailcontant" rows="10" cols="58" name="es_c_adminmailcontant"><?php echo esc_html(stripslashes($form['es_c_adminmailcontant'])); ?></textarea></td>
		</tr>
		<!-------------------------------------------------------------------------------->
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Unsubscribe link', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Unsubscribe link. You no need to change this value.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_unsublink" type="text" id="es_c_unsublink" value="<?php echo esc_html(stripslashes($form['es_c_unsublink'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Unsubscribe text in mail', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Enter the text for unsubscribe link. This text is to add unsubscribe link with newsletter.', ES_TDOMAIN); ?> (Keyword: ###LINK###)</p></label>
			</th>
			<td><textarea size="100" id="es_c_unsubtext" rows="4" cols="58" name="es_c_unsubtext"><?php echo esc_html(stripslashes($form['es_c_unsubtext'])); ?></textarea></td>
		</tr>	
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Text to display after email unsubscribed', ES_TDOMAIN); ?>
				<p class="description"><?php _e('This text will display once user clicked unsubscribed link from our newsletter.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><textarea size="100" id="es_c_unsubhtml" rows="4" cols="58" name="es_c_unsubhtml"><?php echo esc_html(stripslashes($form['es_c_unsubhtml'])); ?></textarea></td>
		</tr>
		<!-------------------------------------------------------------------------------->
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Message 1', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Default message to display if any issue on confirmation link.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><textarea size="100" id="es_c_message1" rows="4" cols="58" name="es_c_message1"><?php echo esc_html(stripslashes($form['es_c_message1'])); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Message 2', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Default message to display if any issue on unsubscribe link.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><textarea size="100" id="es_c_message2" rows="4" cols="58" name="es_c_message2"><?php echo esc_html(stripslashes($form['es_c_message2'])); ?></textarea></td>
		</tr>
		<!-------------------------------------------------------------------------------->
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Sent report subject', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Mail subject for sent mail report.', ES_TDOMAIN); ?></p></label>
			</th>
			<td><input name="es_c_sentreport_subject" type="text" id="es_c_sentreport_subject" value="<?php echo esc_html(stripslashes($form['es_c_sentreport_subject'])); ?>" size="60" maxlength="225" /></td>
		</tr>
		<tr>
			<th scope="row"> 
				<label for="elp"><?php _e('Sent report content', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Mail content for sent mail report.', ES_TDOMAIN); ?> (Keyword: ###COUNT###, ###UNIQUE###, ###STARTTIME###, ###ENDTIME###)</p></label>
			</th>
			<td><textarea size="100" id="es_c_sentreport" rows="8" cols="58" name="es_c_sentreport"><?php echo esc_html(stripslashes($form['es_c_sentreport'])); ?></textarea></td>
		</tr>
		<!-------------------------------------------------------------------------------->
	</tbody>
	</table>
	<div style="padding-top:10px;"></div>
	<input type="hidden" name="es_form_submit" value="yes"/>
	<input type="hidden" name="es_c_id" id="es_c_id" value="<?php echo $form['es_c_id']; ?>"/>
	<p class="submit">
		<input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Save Settings', ES_TDOMAIN); ?>" type="submit" />
		<input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />
		<input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
	</p>
	<?php wp_nonce_field('es_form_edit'); ?>
    </form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>