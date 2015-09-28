<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$did = isset($_GET['did']) ? $_GET['did'] : '0';
es_cls_security::es_check_number($did);

// First check if ID exist with requested ID
$result = es_cls_compose::es_template_count($did);
if ($result != '1')
{
	?><div class="error fade"><p><strong><?php _e('Oops, selected details doesnt exist.', ES_TDOMAIN); ?></strong></p></div><?php
}
else
{
	$es_errors = array();
	$es_success = '';
	$es_error_found = FALSE;
	
	$data = array();
	$data = es_cls_compose::es_template_select($did);
	
	// Preset the form fields
	$form = array(
		'es_templ_id' => $data['es_templ_id'],
		'es_templ_heading' => stripslashes($data['es_templ_heading']),
		'es_templ_body' => stripslashes($data['es_templ_body']),
		'es_templ_status' => $data['es_templ_status'],
		'es_email_type' => $data['es_email_type']
	);
}
// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_form_edit');
	
	$form['es_templ_heading'] = isset($_POST['es_templ_heading']) ? $_POST['es_templ_heading'] : '';
	if ($form['es_templ_heading'] == '')
	{
		$es_errors[] = __('Please enter template heading.', ES_TDOMAIN);
		$es_error_found = TRUE;
	}
	$form['es_templ_body'] = isset($_POST['es_templ_body']) ? $_POST['es_templ_body'] : '';
	$form['es_templ_status'] = isset($_POST['es_templ_status']) ? $_POST['es_templ_status'] : '';
	$form['es_email_type'] = isset($_POST['es_email_type']) ? $_POST['es_email_type'] : '';
	$form['es_templ_id'] = isset($_POST['es_templ_id']) ? $_POST['es_templ_id'] : '0';

	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{	
		$action = "";
		$action = es_cls_compose::es_template_ins($form, $action = "update");
		if($action == "sus")
		{
			$es_success = __('Template was successfully updated.', ES_TDOMAIN);
		}
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
		<p>
			<strong>
			<?php echo $es_success; ?> 
			<a href="<?php echo get_option('siteurl'); ?>/wp-admin/admin.php?page=es-compose"><?php _e('Click here', ES_TDOMAIN); ?></a>
			<?php _e(' to view the details', ES_TDOMAIN); ?>
			</strong>
		</p>
	</div>
	<?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>compose/compose.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Compose Mail', ES_TDOMAIN); ?></h3>
	<form name="es_form" method="post" action="#" onsubmit="return _es_submit()"  >
      
      <label for="tag-link"><?php _e('Mail type', ES_TDOMAIN); ?></label>
      <select name="es_email_type" id="es_email_type">
        <option value='Static Template' <?php if($form['es_email_type']=='Static Template') { echo 'selected="selected"' ; } ?>>Static Template (For Newsletter Email)</option>
		<option value='Dynamic Template' <?php if($form['es_email_type']=='Dynamic Template') { echo 'selected="selected"' ; } ?>>Dynamic Template (For Notification Email)</option>
      </select>
      <p><?php _e('Please select your mail type.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-link"><?php _e('Enter mail subject.', ES_TDOMAIN); ?></label>
      <input name="es_templ_heading" type="text" id="es_templ_heading" value="<?php echo esc_html(stripslashes($form['es_templ_heading'])); ?>" size="80" maxlength="225" />
      <p><?php _e('Please enter your mail subject.', ES_TDOMAIN); ?> Keyword: ###POSTTITLE###</p>
	  
	  <label for="tag-link"><?php _e('Mail content', ES_TDOMAIN); ?></label>
	  <?php $settings_body = array( 'textarea_rows' => 25 ); ?>
      <?php wp_editor(stripslashes($form['es_templ_body']), "es_templ_body", $settings_body);?>
      <p><?php _e('Please enter content for your mail.', ES_TDOMAIN); ?>
	  <br />Keywords: ###POSTTITLE###, ###POSTLINK###, ###POSTIMAGE###, ###POSTDESC###, ###POSTFULL###, ###DATE###, ###POSTLINK-ONLY###, ###POSTLINK-WITHTITLE###</p>
	  
	  <label for="tag-link"><?php _e('Status', ES_TDOMAIN); ?></label>
      <select name="es_templ_status" id="es_templ_status">
        <option value='Published' <?php if($form['es_templ_status']=='Published') { echo 'selected="selected"' ; } ?>>Published</option>
      </select>
      <p><?php _e('Please select your mail status.', ES_TDOMAIN); ?></p>

      <input type="hidden" name="es_form_submit" value="yes"/>
	  <input type="hidden" name="es_templ_id" id="es_templ_id" value="<?php echo $form['es_templ_id']; ?>"/>
      <p class="submit">
        <input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Submit', ES_TDOMAIN); ?>" type="submit" />
        <input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />
        <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
      </p>
	  
	  <?php wp_nonce_field('es_form_edit'); ?>
    </form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>