<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

$sentguid = isset($_GET['sentguid']) ? $_GET['sentguid'] : '';
es_cls_security::es_check_guid($sentguid);

if ($sentguid == '') {
	?><div class="error fade">
		<p><strong>
			<?php echo __( 'Oops.. Unexpected error occurred. Please try again.', ES_TDOMAIN ); ?>
		</strong></p>
	</div><?php
}

?>

<style>
	.page-numbers {
		background: none repeat scroll 0 0 #E0E0E0;
		border-color: #CCCCCC;
		color: #555555;
		padding: 5px;
		text-decoration:none;
		margin-left:2px;
		margin-right:2px;
	}
	.current {
		background: none repeat scroll 0 0 #BBBBBB;
	}
</style>

<?php
	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
	es_cls_security::es_check_number($pagenum);
	$limit = 100;
	$offset = ($pagenum - 1) * $limit;
	$total = es_cls_delivery::es_delivery_count($sentguid);
	$fulltotal = $total;
	$total = ceil( $total / $limit );
	$myData = array();
	$myData = es_cls_delivery::es_delivery_select($sentguid, $offset, $limit);

	$page_links = paginate_links( array(
		'base' => add_query_arg( 'pagenum', '%#%' ),
		'format' => '',
		'prev_text' => __( ' &lt;&lt; ' ),
		'next_text' => __( ' &gt;&gt; ' ),
		'total' => $total,
		'show_all' => False,
		'current' => $pagenum
	) );
?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php echo __( ES_PLUGIN_DISPLAY, ES_TDOMAIN ); ?></h2>
	<h3>
		<?php echo __( 'Delivery Report', ES_TDOMAIN ); ?>
		<a class="add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php echo __( 'Help', ES_TDOMAIN ); ?></a>
	</h3>
	<div class="tablenav">
		<div class="alignright" style="padding-bottom:10px;"><?php echo $page_links; ?></div>
	</div>
	<div class="tool-box">
		<form name="frm_es_display" method="post" onsubmit="return _es_bulkaction()">
			<table width="100%" class="widefat" id="straymanage">
				<thead>
					<tr>
						<th width="3%" scope="col"><?php echo __( 'Sno', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Email', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Sent Date', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Status', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Type', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Viewed Status', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Viewed Date', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Database ID', ES_TDOMAIN ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th width="3%" scope="col"><?php echo __( 'Sno', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Email', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Sent Date', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Status', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Type', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Viewed Status', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Viewed Date', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Database ID', ES_TDOMAIN ); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<?php 
						$i = 0;
						if(count($myData) > 0) {
							$i = 1;
							foreach ($myData as $data) {
								?>
								<tr class="<?php if ($i&1) { echo 'alternate'; } else { echo ''; }?>">
									<td align="left"><?php echo $i; ?></td>
									<td><?php echo $data['es_deliver_emailmail']; ?></td>
									<td><?php echo $data['es_deliver_sentdate']; ?></td>
									<td><?php echo es_cls_common::es_disp_status($data['es_deliver_sentstatus']); ?></td>
									<td><?php echo es_cls_common::es_disp_status($data['es_deliver_senttype']); ?></td>
									<td><?php echo es_cls_common::es_disp_status($data['es_deliver_status']); ?></td>
									<td><?php echo $data['es_deliver_viewdate']; ?></td>
									<td><?php echo $data['es_deliver_emailid']; ?></td>
								</tr>
								<?php
									$i = $i+1;
							}
						} else {
							?><tr><td colspan="8" align="center"><?php echo __( 'No records available.', ES_TDOMAIN ); ?></td></tr><?php 
						}
					?>
				</tbody>
			</table>
			<?php wp_nonce_field('es_form_show'); ?>
			<input type="hidden" name="frm_es_display" value="yes"/>
			<div style="padding-top:10px;"></div>
			<div class="tablenav">
				<div class="alignright">
					<?php echo $page_links; ?>
				</div>
			</div>
		</form>
	</div>
	<div style="height:10px;"></div>
	<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>