<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;

// Preset the form fields
$form = array(
	'es_templ_heading' => '',
	'es_templ_body' => '',
	'es_templ_status' => '',
	'es_email_type' => ''
);

// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_add');
	
	$form['es_templ_heading'] = isset($_POST['es_templ_heading']) ? $_POST['es_templ_heading'] : '';
	if ($form['es_templ_heading'] == '')
	{
		$es_errors[] = __('Please enter template heading.', 'email-subscribers');
		$es_error_found = TRUE;
	}
	$form['es_templ_body'] = isset($_POST['es_templ_body']) ? $_POST['es_templ_body'] : '';
	$form['es_templ_status'] = isset($_POST['es_templ_status']) ? $_POST['es_templ_status'] : '';
	$form['es_email_type'] = isset($_POST['es_email_type']) ? $_POST['es_email_type'] : '';

	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{
		$action = false;
		$action = es_cls_compose::es_template_ins($form, $action = "insert");
		if($action)
		{
			$es_success = __('Template was successfully created.', 'email-subscribers');
		}
		
		// Reset the form fields
		$form = array(
			'es_templ_heading' => '',
			'es_templ_body' => '',
			'es_templ_status' => '',
			'es_email_type' => ''
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
		<p><strong><?php echo $es_success; ?> <a href="<?php echo ES_ADMINURL; ?>?page=es-compose"><?php _e('Click here', 'email-subscribers'); ?></a>
		<?php _e(' to view the details', 'email-subscribers'); ?></strong></p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>compose/compose.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, 'email-subscribers'); ?></h2>
	<h3><?php _e('Compose Mail', 'email-subscribers'); ?></h3>
	<form name="es_form" method="post" action="#" onsubmit="return _es_submit()"  >
      
      <label for="tag-link"><?php _e('Mail type', 'email-subscribers'); ?></label>
      <select name="es_email_type" id="es_email_type">
        <option value='Static Template' selected="selected">Static Template (For Newsletter Email)</option>
		<option value='Dynamic Template'>Dynamic Template (For Notification Email)</option>
      </select>
      <p><?php _e('Please select your mail type.', 'email-subscribers'); ?></p>
	  
	  <label for="tag-link"><?php _e('Enter mail subject.', 'email-subscribers'); ?></label>
      <input name="es_templ_heading" type="text" id="es_templ_heading" value="" size="80" maxlength="225" />
      <p><?php _e('Please enter your mail subject.', 'email-subscribers'); ?>
	  Keyword: ###POSTTITLE###</p>
	  
	  <label for="tag-link"><?php _e('Mail content', 'email-subscribers'); ?></label>
	  <?php $settings_body = array( 'textarea_rows' => 25 ); ?>
      <?php wp_editor("", "es_templ_body", $settings_body);?>
      <p><?php _e('Please enter content for your mail.', 'email-subscribers'); ?>
	  <br />Keywords: ###POSTTITLE###, ###POSTLINK###, ###POSTIMAGE###, ###POSTDESC###, ###POSTFULL###, ###DATE###, ###POSTLINK-ONLY###, ###POSTLINK-WITHTITLE###</p>
	  
	  <label for="tag-link"><?php _e('Status', 'email-subscribers'); ?></label>
      <select name="es_templ_status" id="es_templ_status">
        <option value='Published' selected="selected">Published</option>
      </select>
      <p><?php _e('Please select your mail status.', 'email-subscribers'); ?></p>

      <input type="hidden" name="es_form_submit" value="yes"/>
      <p class="submit">
        <input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Submit', 'email-subscribers'); ?>" type="submit" />
        <input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', 'email-subscribers'); ?>" type="button" />
        <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', 'email-subscribers'); ?>" type="button" />
      </p>
	  <?php wp_nonce_field('es_form_add'); ?>
    </form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>