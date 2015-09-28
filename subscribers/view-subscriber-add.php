<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;

// Preset the form fields
$form = array(
	'es_email_name' => '',
	'es_email_status' => '',
	'es_email_group' => '',
	'es_email_mail' => ''
);

// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_add');
	
	$form['es_email_status'] = isset($_POST['es_email_status']) ? $_POST['es_email_status'] : '';
	$form['es_email_name'] = isset($_POST['es_email_name']) ? $_POST['es_email_name'] : '';
	
	$form['es_email_mail'] = isset($_POST['es_email_mail']) ? $_POST['es_email_mail'] : '';
	if ($form['es_email_mail'] == '')
	{
		$es_errors[] = __('Please enter subscriber email address.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	$es_email_group = isset($_POST['es_email_group']) ? $_POST['es_email_group'] : '';
	if ($es_email_group == '')
	{
		$es_email_group = isset($_POST['es_email_group_txt']) ? $_POST['es_email_group_txt'] : '';
		$form['es_email_group'] = $es_email_group;
	}
	else
	{
		$form['es_email_group'] = $es_email_group;
	}
	
	if ($form['es_email_group'] == '')
	{
		$es_errors[] = __('Please select or create your group for this email.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	
	if($form['es_email_group'] <> "")
	{
		$special_letters = es_cls_common::es_special_letters();
		if (preg_match($special_letters, $form['es_email_group']))
		{
			$es_errors[] = __('Error: Special characters ([\'^$%&*()}{@#~?><>,|=_+\"]) are not allowed in the group name.', ES_TDOMAIN);
			$es_error_found = TRUE;
		}
	}

	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{
		$action = "";
		$action = es_cls_dbquery::es_view_subscriber_ins($form, "insert");
		if($action == "sus")
		{
			$es_success = __('Email was successfully inserted.', ES_TDOMAIN);
		}
		elseif($action == "ext")
		{
			$es_errors[] = __('Email already exist in our list.', ES_TDOMAIN);
			$es_error_found = TRUE;
		}
		elseif($action == "invalid")
		{
			$es_errors[] = __('Email is invalid.', ES_TDOMAIN);
			$es_error_found = TRUE;
		}
			
		// Reset the form fields
		$form = array(
			'es_email_name' => '',
			'es_email_status' => '',
			'es_email_group' => '',
			'es_email_mail' => ''
		);
	}
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?>
	<a href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers"><?php _e('Click here', ES_TDOMAIN); ?></a> <?php _e(' to view the details', ES_TDOMAIN); ?>
	</strong></p></div><?php
}
if ($es_error_found == FALSE && isset($es_success[0]) == TRUE)
{
	?>
	<div class="updated fade">
	<p><strong>
	<?php echo $es_success; ?>
	<a href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers"><?php _e('Click here', ES_TDOMAIN); ?></a> <?php _e(' to view the details', ES_TDOMAIN); ?>
	</strong></p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>subscribers/view-subscriber.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<form name="form_addemail" method="post" action="#" onsubmit="return _es_addemail()"  >
      <h3 class="title"><?php _e('Add email', ES_TDOMAIN); ?></h3>
      
	  <label for="tag-image"><?php _e('Enter full name', ES_TDOMAIN); ?></label>
      <input name="es_email_name" type="text" id="es_email_name" value="" maxlength="225" size="30"  />
      <p><?php _e('Please enter subscriber full name.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-image"><?php _e('Enter email address.', ES_TDOMAIN); ?></label>
      <input name="es_email_mail" type="text" id="es_email_mail" value="" maxlength="225" size="50" />
      <p><?php _e('Please enter subscriber email address.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Status', ES_TDOMAIN); ?></label>
      <select name="es_email_status" id="es_email_status">
        <option value='Confirmed' selected="selected">Confirmed</option>
		<option value='Unconfirmed'>Unconfirmed</option>
		<option value='Unsubscribed'>Unsubscribed</option>
		<option value='Single Opt In'>Single Opt In</option>
      </select>
      <p><?php _e('Please select subscriber email status.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Select (or) Create Group', ES_TDOMAIN); ?></label>
	  <select name="es_email_group" id="es_email_group">
		<option value=''><?php _e('Select', ES_TDOMAIN); ?></option>
		<?php
		$groups = array();
		$groups = es_cls_dbquery::es_view_subscriber_group();
		if(count($groups) > 0)
		{
			$i = 1;
			foreach ($groups as $group)
			{
				?><option value="<?php echo stripslashes($group["es_email_group"]); ?>"><?php echo stripslashes($group["es_email_group"]); ?></option><?php
			}
		}
		?>
	  </select>
	  (or) 
	  <input name="es_email_group_txt" type="text" id="es_email_group_txt" value="" maxlength="225" />
      <p><?php _e('Please select or create group for this subscriber.', ES_TDOMAIN); ?></p>
	  
      <input type="hidden" name="es_form_submit" value="yes"/>
	  <div style="padding-top:5px;"></div>
      <p>
        <input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Submit', ES_TDOMAIN); ?>" type="submit" />
        <input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />
        <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
      </p>
	  <?php wp_nonce_field('es_form_add'); ?>
    </form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>