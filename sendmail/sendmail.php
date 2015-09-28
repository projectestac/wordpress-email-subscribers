<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php if ( ! empty( $_POST ) && ! wp_verify_nonce( $_REQUEST['wp_create_nonce'], 'sendmail-nonce' ) )  { die('<p>Security check failed.</p>'); } ?>
<?php
$es_c_email_subscribers_ver = get_option('email-subscribers');
if ($es_c_email_subscribers_ver <> "2.9")
{
	?>
	<div class="error fade">
		<p>
		Note: You have recently upgraded the plugin and your tables are not sync. 
		Please <a title="Sync plugin tables." href="<?php echo ES_ADMINURL; ?>?page=es-settings&amp;ac=sync"><?php _e('Click Here', ES_TDOMAIN); ?></a> to sync the table. 
		This is mandatory and it will not affect your data.
		</p>
	</div>
	<?php
}

$es_errors = array();
$es_success = '';
$es_error_found = FALSE;

$es_templ_heading = isset($_POST['es_templ_heading']) ? $_POST['es_templ_heading'] : '';
$es_email_group = isset($_POST['es_email_group']) ? $_POST['es_email_group'] : '';
$es_search_query = isset($_POST['es_search_query']) ? $_POST['es_search_query'] : '';
$sendmailsubmit = isset($_POST['sendmailsubmit']) ? $_POST['sendmailsubmit'] : 'no';
$es_sent_type = isset($_POST['es_sent_type']) ? $_POST['es_sent_type'] : '';

if ($sendmailsubmit == 'yes')
{
	check_admin_referer('es_form_submit');
	
	$form['es_templ_heading'] = isset($_POST['es_templ_heading']) ? $_POST['es_templ_heading'] : '';
	if ($form['es_templ_heading'] == '')
	{
		$es_errors[] = __('Please select your mail subject.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	$form['es_email_group'] = isset($_POST['es_email_group']) ? $_POST['es_email_group'] : '';
	$recipients = isset($_POST['eemail_checked']) ? $_POST['eemail_checked'] : '';
	if ($recipients == '')
	{
		$es_errors[] = __('No email address selected.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	$form['es_sent_type'] = isset($_POST['es_sent_type']) ? $_POST['es_sent_type'] : '';
	if ($form['es_sent_type'] == '')
	{
		$es_errors[] = __('Please select your mail type.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	if ($es_error_found == FALSE)
	{
		es_cls_sendmail::es_prepare_newsletter_manual($es_templ_heading, $recipients, $form['es_sent_type']);				
		$es_success_msg = TRUE;
		$es_success = __('Mail sent successfully', ES_TDOMAIN);
		if ($es_success_msg == TRUE)
		{
			?>
			<div class="updated fade">
			  <p>
				<strong><?php echo $es_success; ?> <a href="<?php echo ES_ADMINURL; ?>?page=es-sentmail"><?php _e('Click here for details', ES_TDOMAIN); ?></a></strong>
			  </p>
			</div>
			<?php
		}
		else
		{
			?>
			<div class="error fade">
			  <p><strong><?php _e('Oops.. We are getting some error. mail not sending.', ES_TDOMAIN); ?></strong></p>
			</div>
			<?php
		}
	}
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?></strong></p></div><?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>sendmail/sendmail.js"></script>
<style>
.form-table th {
    width: 250px;
}
</style>
<div class="wrap">
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Send Email', ES_TDOMAIN); ?></h3>
	<form name="es_form" method="post" action="#" onsubmit="return _es_submit()"  >
	<table class="form-table">
	<tbody>
		<tr>
		<th scope="row">
			<label for="elp">
				<?php _e('Select your mail subject', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Select a mail subject from available list. Go to Compose page to create new mail.', ES_TDOMAIN); ?></p>
			</label>
		</th>
		<td>
			<select name="es_templ_heading" id="es_templ_heading">
			<option value=''><?php _e('Select', ES_TDOMAIN); ?></option>
			<?php
			$subject = array();
			$subject = es_cls_compose::es_template_select_type($type = "Static Template");
			$thisselected = "";
			if(count($subject) > 0)
			{
				$i = 1;
				foreach ($subject as $sub)
				{
					if($sub["es_templ_id"] == $es_templ_heading) 
					{ 
						$thisselected = "selected='selected'" ; 
					}
					?><option value='<?php echo $sub["es_templ_id"]; ?>' <?php echo $thisselected; ?>><?php echo esc_html(stripslashes($sub["es_templ_heading"])); ?></option><?php
					$thisselected = "";
				}
			}
			?>
			</select>
		</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="elp">
					<?php _e('Mail Type', ES_TDOMAIN); ?>
					<p class="description"><?php _e('Select your mail type.', ES_TDOMAIN); ?></p>
				</label>
			</th>
			<td>
				<select name="es_sent_type" id="es_sent_type">
					<option value=''>Select</option>
					<option value='Instant Mail' <?php if($es_sent_type == 'Instant Mail') { echo "selected='selected'" ; } ?>>Send mail immediately.</option>
					<option value='Cron Mail' <?php if($es_sent_type == 'Cron Mail') { echo "selected='selected'" ; } ?>>Send mail via cron job.</option>
				</select>
			</td>
		</tr>
		<tr>
		<th scope="row">
			<label for="elp">
				<?php _e('Select subscriber group', ES_TDOMAIN); ?>
				<p class="description"><?php _e('Select your subscriber group to send email.', ES_TDOMAIN); ?></p>
			</label>
		</th>
		<td>
			<select name="es_email_group" id="es_email_group" onChange="_es_mailgroup(this.options[this.selectedIndex].value)">
			<option value=''><?php _e('Select', ES_TDOMAIN); ?></option>
			<?php
			$groups = array();
			$thisselected = "";
			$groups = es_cls_dbquery::es_view_subscriber_group();
			if(count($groups) > 0)
			{
				$i = 1;
				foreach ($groups as $group)
				{
					if(stripslashes($group["es_email_group"]) == stripslashes($es_email_group)) 
					{ 
						$thisselected = "selected='selected'" ; 
					}
					?><option value="<?php echo esc_html($group["es_email_group"]); ?>" <?php echo $thisselected; ?>><?php echo stripslashes($group["es_email_group"]); ?></option><?php
					$thisselected = "";
				}
			}
			?>
			</select>
			<input type="button" name="CheckAll" value="<?php _e('Check All', ES_TDOMAIN); ?>" onClick="_es_checkall('es_form', 'eemail_checked[]', true);">
			<input type="button" name="UnCheckAll" value="<?php _e('Uncheck All', ES_TDOMAIN); ?>" onClick="_es_checkall('es_form', 'eemail_checked[]', false);">
		</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="tablenav">
					<span style="text-align:left;">
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('A,B,C')">A,B,C</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('D,E,F')">D,E,F</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('G,H,I')">G,H,I</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('J,K,L')">J,K,L</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('M,N,O')">M,N,O</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('P,Q,R')">P,Q,R</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('S,T,U')">S,T,U</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('V,W,X,Y,Z')">V,W,X,Y,Z</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('0,1,2,3,4,5,6,7,8,9')">0-9</a>&nbsp;&nbsp; 
						<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_sendemailsearch('ALL')">ALL</a> 
					<span>
				</div>
			</td>
		</tr>
		<tr>
		<td colspan="2">
			<?php
			$subscribers = array();
			$subscribers = es_cls_dbquery::es_view_subscriber_sendmail($es_search_query, $es_email_group);	
			$i = 1;
			$count = 0;
			if(count($subscribers) > 0)
			{
				echo "<table border='0' cellspacing='0'><tr>";
				$col=3;
				foreach ($subscribers as $subscriber)
				{
					$to = $subscriber['es_email_mail'];
					$id = $subscriber['es_email_id'];
					//$ToAddress = trim($to) . '<||>' . trim($id);
					if($to <> "")
					{
						echo "<td style='padding-top:4px;padding-bottom:4px;padding-right:10px;'>";
						?>
						<input class="radio" type="checkbox" checked="checked" value='<?php echo $id; ?>' id="eemail_checked[]" name="eemail_checked[]">
						<?php echo $to; ?> (<?php echo $id; ?>)
						<?php
						if($col > 1) 
						{
							$col=$col-1;
							echo "</td><td>"; 
						}
						elseif($col = 1)
						{
							$col=$col-1;
							echo "</td></tr><tr>";;
							$col=3;
						}
						$count = $count + 1;
					}
				}
				echo "</tr></table>";
			}
			else
			{
				echo '<span style="color:#FF0000">';
				_e('No subscribers available for this search criteria.', ES_TDOMAIN);
				echo "<br>";
				echo "Email starts with : " . $es_search_query;
				echo "<br>";
				echo "Subscriber group : " . stripslashes($es_email_group);
				echo "<br><br>";
				echo '</span>';
			}
			?>
		</td>
		</tr>
	</tbody>
	</table>
	<?php $nonce = wp_create_nonce( 'sendmail-nonce' ); ?>
	<div style="padding-top:10px;">
	<input type="hidden" name="es_search_query" id="es_search_query" value="<?php echo $es_search_query; ?>"/>
	<input type="hidden" name="sendmailsubmit" id="sendmailsubmit" value=""/>
	<input type="hidden" name="wp_create_nonce" id="wp_create_nonce" value="<?php echo $nonce; ?>"/>
	<?php if( $count <> 0 ) { ?>
	<input type="submit" name="Submit" class="button add-new-h2" value="<?php _e('Send Email', ES_TDOMAIN); ?>" style="width:160px;" />&nbsp;&nbsp;
	<?php } else { ?>
	<input type="submit" name="Submit" disabled="disabled" class="button add-new-h2" value="<?php _e('Send Email', ES_TDOMAIN); ?>" style="width:160px;" />&nbsp;&nbsp;
	<?php } ?>
	<input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />&nbsp;&nbsp;
    <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
	</div>
	<?php wp_nonce_field('es_form_submit'); ?>
	</form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>