<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;

// Preset the form fields
$form = array(
	'es_note_id' => '',
	'es_note_cat' => '',
	'es_note_group' => '',
	'es_note_templ' => '',
	'es_note_status' => ''
);

// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_add');
	
	$form['es_note_group'] = isset($_POST['es_note_group']) ? $_POST['es_note_group'] : '';
	if ($form['es_note_group'] == '')
	{
		$es_errors[] = __('Please select subscribers group.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	$form['es_note_status'] = isset($_POST['es_note_status']) ? $_POST['es_note_status'] : '';
	if ($form['es_note_status'] == '')
	{
		$es_errors[] = __('Please select notification status.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	$form['es_note_templ'] = isset($_POST['es_note_templ']) ? $_POST['es_note_templ'] : '';
	if ($form['es_note_templ'] == '')
	{
		$es_errors[] = __('Please select notification mail subject. Use compose menu to create new.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	$es_note_cat = isset($_POST['es_note_cat']) ? $_POST['es_note_cat'] : '';
	if ($es_note_cat == '')
	{
		$es_errors[] = __('Please select post categories.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}

	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{
		$action = false;
		$listcategory = "";
		$total = count($es_note_cat);
		if( $total > 0 )
		{
			for($i=0; $i<$total; $i++)
			{
				$listcategory = $listcategory . " ##" . $es_note_cat[$i] . "## ";
				if($i <> ($total - 1))
				{
					$listcategory = $listcategory .  "--";
				}
			}
		}
		$form['es_note_cat'] = $listcategory;
		$action = es_cls_notification::es_notification_ins($form, $action = "insert");
		if($action)
		{
			$es_success = __('Notification was successfully created.', ES_TDOMAIN);
		}
		
		// Reset the form fields
		$form = array(
			'es_note_id' => '',
			'es_note_cat' => '',
			'es_note_group' => '',
			'es_note_templ' => '',
			'es_note_status' => ''
		);
	}
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?></strong></p></div><?php
}
if ($es_error_found == FALSE && strlen($es_success) > 0)
{
	?>
	<div class="updated fade">
		<p><strong><?php echo $es_success; ?> <a href="<?php echo ES_ADMINURL; ?>?page=es-notification"><?php _e('Click here', ES_TDOMAIN); ?></a>
		<?php _e(' to view the details', ES_TDOMAIN); ?></strong></p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>notification/notification.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Add Notification', ES_TDOMAIN); ?></h3>
	<form name="es_form" method="post" action="#" onsubmit="return _es_submit()"  >
      
      <label for="tag-link"><?php _e('Subscribers Group', ES_TDOMAIN); ?></label>
      <select name="es_note_group" id="es_note_group">
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
      <p><?php _e('Please select subscribers group.', ES_TDOMAIN); ?></p>

 		<label for="tag-link"><?php _e('Notification Mail', ES_TDOMAIN); ?></label>
		<select name="es_note_templ" id="es_note_templ">
		<option value=''><?php _e('Select', ES_TDOMAIN); ?></option>
		<?php
		$subject = array();
		$subject = es_cls_compose::es_template_select_type($type = "Dynamic Template");
		$thisselected = "";
		if(count($subject) > 0)
		{
			$i = 1;
			foreach ($subject as $sub)
			{
				?><option value='<?php echo $sub["es_templ_id"]; ?>'><?php echo $sub["es_templ_heading"]; ?></option><?php
			}
		}
		?>
		</select>
		<p><?php _e('Please select notification mail subject. Use compose menu to create new.', ES_TDOMAIN); ?></p>

	  <label for="tag-link"><?php _e('Post Categories', ES_TDOMAIN); ?></label>
      <?php
		$args = array( 'hide_empty' => 0, 'orderby' => 'name', 'order' => 'ASC' );
		$categories = get_categories($args); 
		//print_r($categories);
		$count = 0;
		$col=3;
		echo "<table border='0' cellspacing='0'><tr>"; 
		foreach($categories as $category) 
		{     
			echo "<td style='padding-top:4px;padding-bottom:4px;padding-right:10px;'>";
			?>
			<input type="checkbox" value='<?php echo $category->cat_name; ?>' id="es_note_cat[]" name="es_note_cat[]">
			<?php echo $category->cat_name; ?>
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
		echo "</tr></table>";
	  ?>
      <p><?php _e('Please select post categories.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-link"><?php _e('Custom post type', ES_TDOMAIN); ?></label>
	  <?php
		$args=array('public'=> true, 'exclude_from_search'=> false, '_builtin' => false); 
		$output = 'names';
		$operator = 'and';
		$post_types=get_post_types($args,$output,$operator);
		//print_r($post_types);
		$col=3;
		echo "<table border='0' cellspacing='0'><tr>"; 
		foreach($post_types as $post_type) 
		{     
			echo "<td style='padding-top:4px;padding-bottom:4px;padding-right:10px;'>";
			?>
			<input type="checkbox" value='{T}<?php echo $post_type; ?>{T}' id="es_note_cat[]" name="es_note_cat[]">
			<?php echo $post_type; ?>
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
		echo "</tr></table>";
	  ?>
	  <p><?php _e('Please select your custom post type (Optional).', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-link"><?php _e('Notification Status', ES_TDOMAIN); ?></label>
      <select name="es_note_status" id="es_note_status">
        <option value='Enable' selected="selected">Send mail immediately when new post is published.</option>
		<option value='Cron'>Add to cron when new post is published and send via cron job.</option>
		<option value='Disable'>Disable notification.</option>
      </select>
      <p><?php _e('Please select notification status.', ES_TDOMAIN); ?></p>

      <input type="hidden" name="es_form_submit" value="yes"/>
      <p class="submit">
        <input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Submit', ES_TDOMAIN); ?>" type="submit" />
        <input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />
        <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
      </p>
	  <?php wp_nonce_field('es_form_add'); ?>
    </form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>