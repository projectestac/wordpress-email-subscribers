<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
$did = isset($_GET['did']) ? $_GET['did'] : '0';
es_cls_security::es_check_number($did);

// First check if ID exist with requested ID
$result = es_cls_compose::es_template_count($did);
if ($result != '1')
{
	?><div class="error fade"><p><strong><?php _e('Oops, selected details doesnt exist.', ES_TDOMAIN); ?></strong></p></div><?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>compose/compose.js"></script>
<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
    <h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Preview Mail', ES_TDOMAIN); ?></h3>
    <div class="tool-box">
	<div style="padding:15px;background-color:#FFFFFF;">
	<?php
		$preview = es_cls_compose::es_template_select($did);
		$es_templ_body = $preview["es_templ_body"];
		$es_templ_body = nl2br($es_templ_body);
		echo stripslashes($es_templ_body);
	?>
	</div>
	<div class="tablenav">
	  <h2>
		<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-compose"><?php _e('Back', ES_TDOMAIN); ?></a>
		<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-compose&ac=edit&did=<?php echo $did; ?>"><?php _e('Edit', ES_TDOMAIN); ?></a>
		<a class="button add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php _e('Help', ES_TDOMAIN); ?></a>
	  </h2>
	</div>
	<div style="height:10px;"></div>
	<p class="description"><?php echo ES_OFFICIAL; ?></p>
	</div>
</div>