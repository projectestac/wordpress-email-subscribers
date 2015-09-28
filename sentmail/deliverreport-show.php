<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<script language="javaScript" src="<?php echo ES_URL; ?>sentmail/sentmail.js"></script>
<?php
$sentguid = isset($_GET['sentguid']) ? $_GET['sentguid'] : '';
es_cls_security::es_check_guid($sentguid);

if ($sentguid == '')
{
	?>
	<div class="error fade">
	  <p><strong><?php _e('Oops.. Unexpected error occurred. Please try again.', ES_TDOMAIN); ?></strong></p>
	</div>
	<?php
}
?>
<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
    <h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Delivery Report', ES_TDOMAIN); ?></h3>
    <div class="tool-box">
	<?php
	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
	es_cls_security::es_check_number($pagenum);
	$limit = 200;
	$offset = ($pagenum - 1) * $limit;
	$total = es_cls_delivery::es_delivery_count($sentguid);
	$fulltotal = $total;
	$total = ceil( $total / $limit );

	$myData = array();
	$myData = es_cls_delivery::es_delivery_select($sentguid, $offset, $limit);
	?>
	<form name="frm_es_display" method="post" onsubmit="return _es_bulkaction()">
      <table width="100%" class="widefat" id="straymanage">
        <thead>
          <tr>
            <th width="3%" scope="col"><?php _e('Sno', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Email', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Sent Date', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Status', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Type', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Viewed Status', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Viewed Date', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Database ID', ES_TDOMAIN); ?></th>
          </tr>
        </thead>
		<tfoot>
          <tr>
            <th width="3%" scope="col"><?php _e('Sno', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Email', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Sent Date', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Status', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Type', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Viewed Status', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Viewed Date', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Database ID', ES_TDOMAIN); ?></th>
          </tr>
        </tfoot>
		<tbody>
			<?php 
			$i = 0;
			if(count($myData) > 0)
			{
				$i = 1;
				foreach ($myData as $data)
				{
					?>
					<tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; }?>">
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
			}
			else
			{
				?><tr><td colspan="8" align="center"><?php _e('No records available.', ES_TDOMAIN); ?></td></tr><?php 
			}
			?>
		</tbody>
        </table>
		<?php wp_nonce_field('es_form_show'); ?>
		<input type="hidden" name="frm_es_display" value="yes"/>
		<div style="padding-top:10px;"></div>
		<?php
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
		<div class="tablenav">
			<div class="alignleft">
				<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-sentmail"><?php _e('Back', ES_TDOMAIN); ?></a> &nbsp;
				<a class="button add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php _e('Help', ES_TDOMAIN); ?></a> 
			</div>
			<div class="alignright">
				<?php echo $page_links; ?>
			</div>
		</div>
      </form>
	  <p class="description"><?php echo ES_OFFICIAL; ?></p>
	</div>
</div>