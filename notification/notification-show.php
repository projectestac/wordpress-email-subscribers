<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
// Form submitted, check the data
if (isset($_POST['frm_es_display']) && $_POST['frm_es_display'] == 'yes')
{
	$did = isset($_GET['did']) ? $_GET['did'] : '0';
	es_cls_security::es_check_number($did);
	
	$es_success = '';
	$es_success_msg = FALSE;
	
	// First check if ID exist with requested ID
	$result = es_cls_notification::es_notification_count($did);
	if ($result != '1')
	{
		?><div class="error fade"><p><strong><?php _e('Oops, selected details doesnt exist.', ES_TDOMAIN); ?></strong></p></div><?php
	}
	else
	{
		// Form submitted, check the action
		if (isset($_GET['ac']) && $_GET['ac'] == 'del' && isset($_GET['did']) && $_GET['did'] != '')
		{
			//	Just security thingy that wordpress offers us
			check_admin_referer('es_form_show');
			
			//	Delete selected record from the table
			es_cls_notification::es_notification_delete($did);
			
			//	Set success message
			$es_success_msg = TRUE;
			$es_success = __('Selected record was successfully deleted.', ES_TDOMAIN);
		}
	}
	
	if ($es_success_msg == TRUE)
	{
		?><div class="updated fade"><p><strong><?php echo $es_success; ?></strong></p></div><?php
	}
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>notification/notification.js"></script>
<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
    <h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
	<h3><?php _e('Notification', ES_TDOMAIN); ?>  
	<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-notification&amp;ac=add"><?php _e('Add New', ES_TDOMAIN); ?></a></h3>
    <div class="tool-box">
	<?php
	$myData = array();
	$myData = es_cls_notification::es_notification_select(0);
	?>
	<form name="frm_es_display" method="post">
      <table width="100%" class="widefat" id="straymanage">
        <thead>
          <tr>
			<th scope="col"><?php _e('Mail Subject', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Subscribers Group', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Categories / Custom Post', ES_TDOMAIN); ?></th>
            <th scope="col"><?php _e('Notification Status', ES_TDOMAIN); ?></th>
          </tr>
        </thead>
		<tfoot>
          <tr>
			<th scope="col"><?php _e('Mail Subject', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Subscribers Group', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Categories / Custom Post', ES_TDOMAIN); ?></th>
            <th scope="col"><?php _e('Notification Status', ES_TDOMAIN); ?></th>
          </tr>
        </tfoot>
		<tbody>
			<?php 
			$i = 0;
			$displayisthere = FALSE;
			if(count($myData) > 0)
			{
				$i = 1;
				foreach ($myData as $data)
				{
					?>
					<tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; }?>">
					  	<td>
						<?php 
						$template = es_cls_compose::es_template_select($data['es_note_templ']);
						if (count($template) > 0)
						{
							echo $template['es_templ_heading'];
						}
						?>
						<div class="row-actions">
							<span class="edit">
							<a title="Edit" href="<?php echo ES_ADMINURL; ?>?page=es-notification&amp;ac=edit&amp;did=<?php echo $data['es_note_id']; ?>"><?php _e('Edit', ES_TDOMAIN); ?></a> 
							</span>
							<span class="trash">
							| <a onClick="javascript:_es_delete('<?php echo $data['es_note_id']; ?>')" href="javascript:void(0);"><?php _e('Delete', ES_TDOMAIN); ?></a>
							</span>
						</div>
						</td>
						<td><?php echo stripslashes($data['es_note_group']); ?></td>
						<td>
						<?php 
						$es_note_cat = str_replace("## -- ##", ", ", $data['es_note_cat']);
						$es_note_cat = str_replace("##", "", $es_note_cat);
						$es_note_cat = str_replace("{T}", "", $es_note_cat);
						$j=0;
						$caegorydisplay = explode(",", $es_note_cat);
						if(count($caegorydisplay) > 0)
						{
							for($j=0; $j < count($caegorydisplay); $j++)
							{
								echo $caegorydisplay[$j] . ", ";
								if (($j > 0) && ($j % 3 == 0)) 
								{
									echo "<br>";
								}
							}
						}
						?>
						</td>
						<td>
						<?php 
						if ($data['es_note_status'] == "Enable")
						{
							echo "Send mail immediately<br> when new post is published.";
						}
						elseif ($data['es_note_status'] == "Cron")
						{
							echo "Add to cron and send mail via cron job.";
						}
						else
						{
							echo es_cls_common::es_disp_status($data['es_note_status']);
						}
						?>
						</td>
					</tr>
					<?php
					$i = $i+1;
				}
			}
			else
			{
				?><tr><td colspan="4" align="center"><?php _e('No records available.', ES_TDOMAIN); ?></td></tr><?php 
			}
			?>
		</tbody>
        </table>
		<?php wp_nonce_field('es_form_show'); ?>
		<input type="hidden" name="frm_es_display" value="yes"/>
      </form>	
	  <div class="tablenav">
		  <h2>
			<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-notification&amp;ac=add"><?php _e('Add New', ES_TDOMAIN); ?></a>
			<a class="button add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php _e('Help', ES_TDOMAIN); ?></a>
		  </h2>
	  </div>
	  <div style="height:10px;"></div>
	  <p class="description"><?php echo ES_OFFICIAL; ?></p>
	</div>
</div>