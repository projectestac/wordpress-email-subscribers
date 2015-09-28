<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;
$es_registered = "";
$es_registered_group = "";
$es_commented = "";
$es_commented_group = "";
	
// Preset the form fields
$form = array(
	'es_registered' => '',
	'es_registered_group' => '',
	'es_commented' => '',
	'es_commented_group' => ''
);

// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_add');
	
	$form['es_registered'] = isset($_POST['es_registered']) ? $_POST['es_registered'] : '';
	$form['es_registered_group'] = isset($_POST['es_registered_group']) ? $_POST['es_registered_group'] : '';
	$form['es_commented'] = isset($_POST['es_commented']) ? $_POST['es_commented'] : '';
	$form['es_commented_group'] = isset($_POST['es_commented_group']) ? $_POST['es_commented_group'] : '';
	
	if ($form['es_registered_group'] == '' && $form['es_registered'] == "YES")
	{
		$es_errors[] = __('Please select default group to newly registered user.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}

	if ($form['es_commented_group'] == '' && $form['es_commented'] == "YES")
	{
		$es_errors[] = __('Please select default group to newly commented user.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
		
	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{
		update_option('es_c_emailsubscribers', $form );
		
		// Reset the form fields
		$form = array(
			'es_registered' => '',
			'es_registered_group' => '',
			'es_commented' => '',
			'es_commented_group' => ''
		);
		
		$es_success = __('Sync email successfully updated.', ES_TDOMAIN);
	}
}

$es_c_emailsubscribers = get_option('es_c_emailsubscribers', 'norecord');
if($es_c_emailsubscribers <> 'norecord' && $es_c_emailsubscribers <> "")
{
	$es_registered = $es_c_emailsubscribers['es_registered'];
	$es_registered_group = $es_c_emailsubscribers['es_registered_group'];
	$es_commented = $es_c_emailsubscribers['es_commented'];
	$es_commented_group = $es_c_emailsubscribers['es_commented_group'];
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?></strong></p></div><?php
}

if ($es_error_found == FALSE && isset($es_success[0]) == TRUE)
{
	?>
	<div class="updated fade">
		<p><strong><?php echo $es_success; ?></strong></p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>subscribers/view-subscriber.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<form name="form_addemail" method="post" action="#" onsubmit="return _es_addemail()"  >
      <h3 class="title"><?php _e('Sync email', ES_TDOMAIN); ?></h3>
      
	  <label for="tag-image"><?php _e('Sync newly registered user', ES_TDOMAIN); ?></label>
      <select name="es_registered" id="es_email_status">
        <option value='NO' <?php if($es_registered == 'NO') { echo "selected='selected'" ; } ?>>NO</option>
		<option value='YES' <?php if($es_registered == 'YES') { echo "selected='selected'" ; } ?>>YES</option>
      </select>
      <p><?php _e('Automatically add a newly registered user email address to subscribers list.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Select default group', ES_TDOMAIN); ?></label>
	  <select name="es_registered_group" id="es_email_group">
		<option value=''><?php _e('Select', ES_TDOMAIN); ?></option>
		<?php
		$thisselected = "";
		$groups = array();
		$groups = es_cls_dbquery::es_view_subscriber_group();
		if(count($groups) > 0)
		{
			$i = 1;
			foreach ($groups as $group)
			{
				if($group["es_email_group"] == $es_registered_group) 
				{ 
					$thisselected = "selected='selected'" ; 
				}
				?><option value='<?php echo $group["es_email_group"]; ?>' <?php echo $thisselected; ?>><?php echo $group["es_email_group"]; ?></option><?php
				$thisselected = "";
			}
		}
		?>
	  </select>
      <p><?php _e('Please select default group to newly registered user.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-image"><?php _e('Sync newly commented user', ES_TDOMAIN); ?></label>
      <select name="es_commented" id="es_email_status">
        <option value='NO' <?php if($es_commented == 'NO') { echo "selected='selected'" ; } ?>>NO</option>
		<!--<option value='YES' <?php //if($es_commented == 'YES') { echo "selected='selected'" ; } ?>>YES</option>-->
      </select>
      <p><?php _e('Automatically add a newly commented (who posted comments) user email address to subscribers list.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Select default group', ES_TDOMAIN); ?></label>
	  <select name="es_commented_group" id="es_email_group">
		<option value=''><?php _e('Select', ES_TDOMAIN); ?></option>
		<?php
		//$thisselected = "";
//		$groups = array();
//		$groups = es_cls_dbquery::es_view_subscriber_group();
//		if(count($groups) > 0)
//		{
//			$i = 1;
//			foreach ($groups as $group)
//			{
//				if($group["es_email_group"] == $es_commented_group) 
//				{ 
//					$thisselected = "selected='selected'" ; 
//				}
//				?><!--<option value='<?php //echo $group["es_email_group"]; ?>' <?php //echo $thisselected; ?>><?php //echo $group["es_email_group"]; ?></option>--><?php
//				$thisselected = "";
//			}
//		}
		?>
	  </select>
      <p><?php _e('Please select default group to newly commented user.', ES_TDOMAIN); ?></p>

	  
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