<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<div class="wrap">
<?php
$es_errors = array();
$es_success = '';
$es_error_found = FALSE;

$es_roles_subscriber = "";
$es_roles_mail = "";
$es_roles_notification = "";
$es_roles_sendmail = "";
$es_roles_setting = "";
$es_roles_sentmail = "";
$es_roles_help = "";

// Preset the form fields
$form = array(
	'es_roles_subscriber' => '',
	'es_roles_mail' => '',
	'es_roles_notification' => '',
	'es_roles_sendmail' => '',
	'es_roles_setting' => '',
	'es_roles_sentmail' => '',
	'es_roles_help' => ''
);

// Form submitted, check the data
if (isset($_POST['es_form_submit']) && $_POST['es_form_submit'] == 'yes')
{
	//	Just security thingy that wordpress offers us
	check_admin_referer('es_roles_add');
	
	$form['es_roles_subscriber'] = isset($_POST['es_roles_subscriber']) ? $_POST['es_roles_subscriber'] : '';
	$form['es_roles_mail'] = isset($_POST['es_roles_mail']) ? $_POST['es_roles_mail'] : '';
	$form['es_roles_notification'] = isset($_POST['es_roles_notification']) ? $_POST['es_roles_notification'] : '';
	$form['es_roles_sendmail'] = isset($_POST['es_roles_sendmail']) ? $_POST['es_roles_sendmail'] : '';
	$form['es_roles_setting'] = isset($_POST['es_roles_setting']) ? $_POST['es_roles_setting'] : '';
	$form['es_roles_sentmail'] = isset($_POST['es_roles_sentmail']) ? $_POST['es_roles_sentmail'] : '';
	$form['es_roles_help'] = isset($_POST['es_roles_help']) ? $_POST['es_roles_help'] : '';
		
	//	No errors found, we can add this Group to the table
	if ($es_error_found == FALSE)
	{
		update_option('es_c_rolesandcapabilities', $form );
					
		// Reset the form fields
		$form = array(
			'es_roles_subscriber' => '',
			'es_roles_mail' => '',
			'es_roles_notification' => '',
			'es_roles_sendmail' => '',
			'es_roles_setting' => '',
			'es_roles_sentmail' => '',
			'es_roles_help' => ''
		);
	}
}

$es_c_rolesandcapabilities = get_option('es_c_rolesandcapabilities', 'norecord');
if($es_c_rolesandcapabilities <> 'norecord' && $es_c_rolesandcapabilities <> "")
{
	$es_roles_subscriber = $es_c_rolesandcapabilities['es_roles_subscriber'];
	$es_roles_mail = $es_c_rolesandcapabilities['es_roles_mail'];
	$es_roles_notification = $es_c_rolesandcapabilities['es_roles_notification'];
	$es_roles_sendmail = $es_c_rolesandcapabilities['es_roles_sendmail'];
	$es_roles_setting = $es_c_rolesandcapabilities['es_roles_setting'];
	$es_roles_sentmail = $es_c_rolesandcapabilities['es_roles_sentmail'];
	$es_roles_help = $es_c_rolesandcapabilities['es_roles_help'];
}

if ($es_error_found == TRUE && isset($es_errors[0]) == TRUE)
{
	?><div class="error fade"><p><strong><?php echo $es_errors[0]; ?></strong></p></div><?php
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
<script language="javaScript" src="<?php echo ES_URL; ?>roles/roles.js"></script>
<div class="form-wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<form name="form_roles" method="post" action="#" onsubmit="return _es_addroles()"  >
      <h3 class="title"><?php _e('Roles and Capabilities', ES_TDOMAIN); ?></h3>
      
	  <label for="tag-image"><?php _e('Subscribers Menu', ES_TDOMAIN); ?></label>
      <select name="es_roles_subscriber" id="es_roles_subscriber">
		<option value='manage_options' <?php if($es_roles_subscriber == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_subscriber == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_subscriber == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Subscribers Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
	  
	  <label for="tag-image"><?php _e('Compose Menu', ES_TDOMAIN); ?></label>
      <select name="es_roles_mail" id="es_roles_mail">
		<option value='manage_options' <?php if($es_roles_mail == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_mail == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_mail == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Compose Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Notification Menu', ES_TDOMAIN); ?></label>
      <select name="es_roles_notification" id="es_roles_notification">
		<option value='manage_options' <?php if($es_roles_notification == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_notification == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_notification == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Notification Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Send Email Menu/Cron Menu', ES_TDOMAIN); ?></label>
	  <select name="es_roles_sendmail" id="es_roles_sendmail">
		<option value='manage_options' <?php if($es_roles_sendmail == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_sendmail == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_sendmail == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Send Email Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Settings Menu', ES_TDOMAIN); ?></label>
	  <select name="es_roles_setting" id="es_roles_setting">
		<option value='manage_options' <?php if($es_roles_setting == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_setting == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_setting == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Settings Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Sent Mails Menu', ES_TDOMAIN); ?></label>
	  <select name="es_roles_sentmail" id="es_roles_sentmail">
		<option value='manage_options' <?php if($es_roles_sentmail == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_sentmail == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_sentmail == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Sent Mails Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
	  <label for="tag-display-status"><?php _e('Help & Info Menu', ES_TDOMAIN); ?></label>
	  <select name="es_roles_help" id="es_roles_help">
		<option value='manage_options' <?php if($es_roles_help == 'manage_options') { echo "selected='selected'" ; } ?>>Administrator Only</option>
		<option value='edit_others_pages' <?php if($es_roles_help == 'edit_others_pages') { echo "selected='selected'" ; } ?>>Administrator/Editor</option>
		<option value='edit_posts' <?php if($es_roles_help == 'edit_posts') { echo "selected='selected'" ; } ?>>Administrator/Editor/Author/Contributor</option>
	  </select>
      <p><?php _e('Select user role to access plugin Help & Info Menu. Only Admin user can change this value.', ES_TDOMAIN); ?></p>
	  
      <input type="hidden" name="es_form_submit" value="yes"/>
	  <div style="padding-top:5px;"></div>
      <p>
        <input name="publish" lang="publish" class="button add-new-h2" value="<?php _e('Submit', ES_TDOMAIN); ?>" type="submit" />
        <input name="publish" lang="publish" class="button add-new-h2" onclick="_es_redirect()" value="<?php _e('Cancel', ES_TDOMAIN); ?>" type="button" />
        <input name="Help" lang="publish" class="button add-new-h2" onclick="_es_help()" value="<?php _e('Help', ES_TDOMAIN); ?>" type="button" />
      </p>
	  <?php wp_nonce_field('es_roles_add'); ?>
    </form>
</div>
<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>