<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
$did = isset($_GET['did']) ? $_GET['did'] : '0';
es_cls_security::es_check_number($did);
$pagenum = isset($_GET['pagenum']) ? $_GET['pagenum'] : 1;
es_cls_security::es_check_number($pagenum);

// First check if ID exist with requested ID
$result = es_cls_sentmail::es_sentmail_count($did);
if ($result != '1')
{
	?><div class="error fade"><p><strong><?php _e('Oops, selected details doesnt exist.', ES_TDOMAIN); ?></strong></p></div><?php
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>template/template.js"></script>
<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
    <h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Preview Mail', ES_TDOMAIN); ?></h3>
    <div class="tool-box">
	<div style="padding:15px;background-color:#FFFFFF;">
	<?php
		$preview =  array();
		$preview = es_cls_sentmail::es_sentmail_select($did, 0, 0);
		$preview = str_replace('###NAME###', "Username", $preview);
		$preview = str_replace('###EMAIL###', "Useremail", $preview);
		echo stripslashes($preview['es_sent_preview']);
	?>
	</div>
	<div class="tablenav">
	  <h2>
		<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-sentmail&pagenum=<?php echo $pagenum; ?>"><?php _e('Back', ES_TDOMAIN); ?></a>
		<a class="button add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php _e('Help', ES_TDOMAIN); ?></a>
	  </h2>
	</div>
	<div style="height:10px;"></div>
	<p class="description"><?php echo ES_OFFICIAL; ?></p>
	</div>
</div>