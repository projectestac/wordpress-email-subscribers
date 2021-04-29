<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

$did = isset($_GET['did']) ? $_GET['did'] : '0';
es_cls_security::es_check_number($did);

// First check if ID exist with requested ID
$result = es_cls_compose::es_template_count($did);

if ($result != '1') {
	?>
	<div class="error fade">
		<p><strong>
			<?php echo __( 'Oops, selected details does not exists.', ES_TDOMAIN ); ?>
		</strong></p>
	</div><?php
}

?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php echo __( ES_PLUGIN_DISPLAY, ES_TDOMAIN ); ?></h2>
	<h3>
		<?php echo __( 'Preview Mail', ES_TDOMAIN ); ?>
		<a class="add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php echo __( 'Help', ES_TDOMAIN ); ?></a>
	</h3>
	<div class="tool-box">
		<div style="padding:15px;background-color:#FFFFFF;">
			<?php
				$preview = es_cls_compose::es_template_select($did);
				$es_templ_body = $preview["es_templ_body"];
				$es_templ_body = nl2br($es_templ_body);
				echo stripslashes($es_templ_body);
			?>
		</div>
		<p>
			<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-compose&ac=edit&did=<?php echo $did; ?>"><?php echo __( 'Edit', ES_TDOMAIN ); ?></a>
		</p>
		<p class="description"><?php echo ES_OFFICIAL; ?></p>
	</div>
</div>