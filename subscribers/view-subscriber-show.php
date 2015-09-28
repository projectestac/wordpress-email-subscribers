<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php if ( ! empty( $_POST ) && ! wp_verify_nonce( $_REQUEST['wp_create_nonce'], 'subscriber-nonce' ) )  { die('<p>Security check failed.</p>'); } ?>
<?php
$es_c_email_subscribers_ver = get_option('email-subscribers');
if ($es_c_email_subscribers_ver <> "2.9")
{
	?>
	<div class="error fade">
		<p>
		Note: You have recently upgraded the plugin and your tables are not sync. 
		Please <a title="Sync plugin tables." href="<?php echo ES_ADMINURL; ?>?page=es-settings&amp;ac=sync"><?php _e('Click Here', ES_TDOMAIN); ?></a> to sync the table. 
		This is mandatory and it will not affect your data.
		</p>
	</div>
	<?php
}

// Form submitted, check the data
$search = isset($_POST['searchquery']) ? $_POST['searchquery'] : 'ALL';
$search_sts = isset($_POST['searchquery_sts']) ? $_POST['searchquery_sts'] : '';
$search_count = isset($_POST['searchquery_cnt']) ? $_POST['searchquery_cnt'] : '1';
$search_group = isset($_POST['searchquery_group']) ? $_POST['searchquery_group'] : 'ALL';

if (isset($_POST['frm_es_display']) && $_POST['frm_es_display'] == 'yes')
{
	$did = isset($_GET['did']) ? $_GET['did'] : '0';
	es_cls_security::es_check_number($did);
	
	$es_success = '';
	$es_success_msg = FALSE;

	if (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] != 'delete' 
			&& $_POST['frm_es_bulkaction'] != 'resend' && $_POST['frm_es_bulkaction'] != 'groupupdate' 
				&& $_POST['frm_es_bulkaction'] != 'search_sts' && $_POST['frm_es_bulkaction'] != 'search_cnt'
					&& $_POST['frm_es_bulkaction'] != 'search_group')
	{	
		//	Just security thingy that wordpress offers us
		check_admin_referer('es_form_show');
				
		// First check if ID exist with requested ID
		$result = es_cls_dbquery::es_view_subscriber_count($did);
		if ($result != '1')
		{
			?>
			<div class="error fade">
			  <p><strong><?php _e('Oops, selected details doesnt exist.', ES_TDOMAIN); ?></strong></p>
			</div>
			<?php
		}
		else
		{
			// Form submitted, check the action
			if (isset($_GET['ac']) && $_GET['ac'] == 'del' && isset($_GET['did']) && $_GET['did'] != '')
			{
				//	Delete selected record from the table
				es_cls_dbquery::es_view_subscriber_delete($did);
				
				//	Set success message
				$es_success_msg = TRUE;
				$es_success = __('Selected record was successfully deleted.', ES_TDOMAIN);
			}
			
			if (isset($_GET['ac']) && $_GET['ac'] == 'resend' && isset($_GET['did']) && $_GET['did'] != '')
			{
				$did = isset($_GET['did']) ? $_GET['did'] : '0';
				$setting = array();
				$setting = es_cls_settings::es_setting_select(1);
				if($setting['es_c_optinoption'] <> "Double Opt In")
				{
					?>
					<div class="error fade">
					  <p><strong><?php _e('To send confirmation mail, Please change the Opt-in option to Double Opt In.', ES_TDOMAIN); ?></strong></p>
					</div>
					<?php
				}
				else
				{
					es_cls_sendmail::es_prepare_optin("single", $did, $idlist = "");
					es_cls_dbquery::es_view_subscriber_upd_status("Unconfirmed", $did);
					$es_success_msg = TRUE;
					$es_success  = __('Confirmation email resent successfully.', ES_TDOMAIN);
				}
			}
		}
	}
	else
	{
		check_admin_referer('es_form_show');
		
		if (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'delete')
		{
			$chk_delete = isset($_POST['chk_delete']) ? $_POST['chk_delete'] : '';
			if(!empty($chk_delete))
			{			
				$count = count($chk_delete);
				for($i=0; $i<$count; $i++)
				{
					$del_id = $chk_delete[$i];
					es_cls_dbquery::es_view_subscriber_delete($del_id);
				}
				
				//	Set success message
				$es_success_msg = TRUE;
				$es_success = __('Selected record was successfully deleted.', ES_TDOMAIN);
			}
			else
			{
				?>
				<div class="error fade">
				  <p><strong><?php _e('Oops, No record was selected.', ES_TDOMAIN); ?></strong></p>
				</div>
				<?php
			}
		}
		elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'resend')
		{
			$chk_delete = isset($_POST['chk_delete']) ? $_POST['chk_delete'] : '';
			
			$setting = array();
			$setting = es_cls_settings::es_setting_select(1);
			if($setting['es_c_optinoption'] <> "Double Opt In")
			{
				?>
				<div class="error fade">
				  <p><strong><?php _e('To send confirmation mail, Please change the Opt-in option to Double Opt In.', ES_TDOMAIN); ?></strong></p>
				</div>
				<?php
			}
			else
			{
				if(!empty($chk_delete))
				{			
					$count = count($chk_delete);
					$idlist = "";
					for($i = 0; $i<$count; $i++)
					{
						$del_id = $chk_delete[$i];
						if($i < 1)
						{
							$idlist = $del_id;
						}
						else
						{
							$idlist = $idlist . ", " . $del_id;
						}
					}
					es_cls_sendmail::es_prepare_optin("group", 0, $idlist);
					es_cls_dbquery::es_view_subscriber_upd_status("Unconfirmed", $idlist);
					$es_success_msg = TRUE;
					$es_success = __('Confirmation email(s) resent successfully.', ES_TDOMAIN);
				}
				else
				{
					?>
					<div class="error fade">
					  <p><strong><?php _e('Oops, No record was selected.', ES_TDOMAIN); ?></strong></p>
					</div>
					<?php
				}
			}
		}
		elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'groupupdate')
		{
			$chk_delete = isset($_POST['chk_delete']) ? $_POST['chk_delete'] : '';
			if(!empty($chk_delete))
			{			
				$es_email_group = isset($_POST['es_email_group']) ? $_POST['es_email_group'] : '';
				if ($es_email_group <> "")
				{
					$count = count($chk_delete);
					$idlist = "";
					for($i = 0; $i < $count; $i++)
					{
						$del_id = $chk_delete[$i];
						if($i < 1)
						{
							$idlist = $del_id;
						}
						else
						{
							$idlist = $idlist . ", " . $del_id;
						}
					}
					es_cls_dbquery::es_view_subscriber_upd_group($es_email_group, $idlist);
					$es_success_msg = TRUE;
					$es_success = __('Selected subscribers group was successfully updated.', ES_TDOMAIN);
				}
				else
				{
					?>
					<div class="error fade">
					  <p><strong><?php _e('Oops, New group name was not selected.', ES_TDOMAIN); ?></strong></p>
					</div>
					<?php
				}	
			}
			else
			{
				?>
				<div class="error fade">
				  <p><strong><?php _e('Oops, No record was selected.', ES_TDOMAIN); ?></strong></p>
				</div>
				<?php
			}
		}
		elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'search_sts')
		{
			// Nothing
		}
		elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'search_cnt')
		{
			// Nothing
		}
		elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'search_group')
		{
			// Nothing
		}
	}
	
	if ($es_success_msg == TRUE)
	{
		?><div class="updated fade"><p><strong><?php echo $es_success; ?></strong></p></div><?php
	}
}
?>
<script language="javaScript" src="<?php echo ES_URL; ?>subscribers/view-subscriber.js"></script>
<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
  <h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
  <div class="tool-box">
  <form name="frm_es_display" method="post" onsubmit="return _es_bulkaction()">
  <h3><?php _e('View subscriber', ES_TDOMAIN); ?> 
  <a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=add"><?php _e('Add New', ES_TDOMAIN); ?></a></h3>
	<?php
	$myData = array();
	$offset = 0;
	$limit = 200;
	
	if ($search_count == 0)
	{
		$limit = 9999;
	}
	
	if ($search_count > 1)
	{
		$offset = $search_count;
	}
	
	if ($search_count == 1001)
	{
		$limit = 1000;
	}
	elseif ($search_count == 2001)
	{
		$limit = 3000;
	}
	elseif ($search_count == 5001)
	{
		$limit = 5000;
	}
	
	$myData = es_cls_dbquery::es_view_subscriber_search2($search, 0, $search_sts, $offset, $limit, $search_group);
	?>
	
	<div class="tablenav">
		<span style="text-align:left;">
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('A,B,C')">A,B,C</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('D,E,F')">D,E,F</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('G,H,I')">G,H,I</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('J,K,L')">J,K,L</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('M,N,O')">M,N,O</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('P,Q,R')">P,Q,R</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('S,T,U')">S,T,U</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('V,W,X,Y,Z')">V,W,X,Y,Z</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('0,1,2,3,4,5,6,7,8,9')">0-9</a>&nbsp;&nbsp; 
			<a class="button add-new-h2" href="javascript:void(0);" onClick="javascript:_es_search_sub_action('ALL')">ALL</a> 
		<span>
		<span style="float:right;">
		<select name="search_group_action" id="search_group_action" onchange="return _es_search_group_action(this.value)">
			<option value=""><?php _e('All Groups', ES_TDOMAIN); ?></option>
			<?php
			$groups = array();
			$groups = es_cls_dbquery::es_view_subscriber_group();
			if(count($groups) > 0)
			{
				$i = 1;
				foreach ($groups as $group)
				{
					?>
					<option value="<?php echo esc_html(stripslashes($group["es_email_group"])); ?>" <?php if(stripslashes($search_group) == $group["es_email_group"]) { echo 'selected="selected"' ; } ?>>
					<?php echo stripslashes($group["es_email_group"]); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
		<select name="search_sts_action" id="search_sts_action" onchange="return _es_search_sts_action(this.value)">
			<option value=""><?php _e('All Status', ES_TDOMAIN); ?></option>
			<option value="Confirmed" <?php if($search_sts=='Confirmed') { echo 'selected="selected"' ; } ?>><?php _e('Confirmed', ES_TDOMAIN); ?></option>
			<option value="Unconfirmed" <?php if($search_sts=='Unconfirmed') { echo 'selected="selected"' ; } ?>><?php _e('Unconfirmed', ES_TDOMAIN); ?></option>
			<option value="Unsubscribed" <?php if($search_sts=='Unsubscribed') { echo 'selected="selected"' ; } ?>><?php _e('Unsubscribed', ES_TDOMAIN); ?></option>
			<option value="Single Opt In" <?php if($search_sts=='Single Opt In') { echo 'selected="selected"' ; } ?>><?php _e('Single Opt In', ES_TDOMAIN); ?></option>
		</select>
		<select name="search_count_action" id="search_count_action" onchange="return _es_search_count_action(this.value)">
			<option value="1" <?php if($search_count=='1') { echo 'selected="selected"' ; } ?>>1 to 200 emails</option>
			<option value="201" <?php if($search_count=='201') { echo 'selected="selected"' ; } ?>>201 to 400</option>
			<option value="401" <?php if($search_count=='401') { echo 'selected="selected"' ; } ?>>401 to 600</option>
			<option value="601" <?php if($search_count=='601') { echo 'selected="selected"' ; } ?>>601 to 800</option>
			<option value="801" <?php if($search_count=='801') { echo 'selected="selected"' ; } ?>>801 to 1000</option>
			<option value="1001" <?php if($search_count=='1001') { echo 'selected="selected"' ; } ?>>1001 to 2000</option>
			<option value="2001" <?php if($search_count=='2001') { echo 'selected="selected"' ; } ?>>2001 to 5000</option>
			<option value="5001" <?php if($search_count=='5001') { echo 'selected="selected"' ; } ?>>5001 to 10000</option>
			<option value="0" <?php if($search_count=='0') { echo 'selected="selected"' ; } ?>>Display all</option>
		</select>
		</span>
    </div>
    
      <table width="100%" class="widefat" id="straymanage">
        <thead>
          <tr>
            <th class="check-column" scope="col" style="padding: 8px 2px;">
			<input type="checkbox" name="es_checkall" id="es_checkall" onClick="_es_checkall('frm_es_display', 'chk_delete[]', this.checked);" /></th>
            <th scope="col"><?php _e('Sno', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Email address', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Name', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Status', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Group', ES_TDOMAIN); ?></th>
            <th scope="col"><?php _e('Database ID', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Action', ES_TDOMAIN); ?></th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th class="check-column" scope="col" style="padding: 8px 2px;">
			<input type="checkbox" name="es_checkall" id="es_checkall" onClick="_es_checkall('frm_es_display', 'chk_delete[]', this.checked);" /></th>
            <th scope="col"><?php _e('Sno', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Email address', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Name', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Status', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Group', ES_TDOMAIN); ?></th>
            <th scope="col"><?php _e('Database ID', ES_TDOMAIN); ?></th>
			<th scope="col"><?php _e('Action', ES_TDOMAIN); ?></th>
          </tr>
        </tfoot>
        <tbody>
          <?php 
			$i = 0;
			$displayisthere = FALSE;
			if(count($myData) > 0)
			{
				if ($offset == 0)
				{
					$i = 1;
				}
				else
				{
					$i = $offset;
				}
				foreach ($myData as $data)
				{					
					?>
					<tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; }?>">
					<td align="left"><input name="chk_delete[]" id="chk_delete[]" type="checkbox" value="<?php echo $data['es_email_id'] ?>" /></td>
					<td><?php echo $i; ?></td>
					<td><?php echo $data['es_email_mail']; ?></td>
					<td><?php echo stripslashes($data['es_email_name']); ?></td>     
					<td><?php echo es_cls_common::es_disp_status($data['es_email_status']); ?></td>
					<td><?php echo stripslashes($data['es_email_group']); ?></td>
					<td><?php echo $data['es_email_id']; ?></td>
					<td><div> 
					<span class="edit">
					<a target="_blank" title="Edit" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=edit&amp;did=<?php echo $data['es_email_id']; ?>">
					<?php _e('Edit', ES_TDOMAIN); ?></a> | </span> 
					<span class="trash">
					<a onClick="javascript:_es_delete('<?php echo $data['es_email_id']; ?>','<?php echo $search; ?>')" href="javascript:void(0);">
					<?php _e('Delete', ES_TDOMAIN); ?></a>
					</span>
					<?php
					if($data['es_email_status'] != "Confirmed")
					{
						?>
						<span class="edit"> 
						| <a onClick="javascript:_es_resend('<?php echo $data['es_email_id']; ?>','<?php echo $search; ?>')" href="javascript:void(0);">
						<?php _e('Resend Confirmation', ES_TDOMAIN); ?></a>
						</span> 
						<?php
					}
					?>
					</div>
					</td>
					</tr>
					<?php
					$i = $i+1;
				} 
			}
			else
			{
				?>
				<tr>
					<td colspan="7" align="center"><?php _e('No records available. Please use the above alphabet search button to search.', ES_TDOMAIN); ?></td>
				</tr>
				<?php 
			}
			?>
        </tbody>
      </table>
      <?php wp_nonce_field('es_form_show'); ?>
      <input type="hidden" name="frm_es_display" id="frm_es_display" value="yes"/>
	  <input type="hidden" name="frm_es_bulkaction" id="frm_es_bulkaction" value=""/>
	  <input type="hidden" name="searchquery" id="searchquery" value="<?php echo $search; ?>" />
	  <input type="hidden" name="searchquery_sts" id="searchquery_sts" value="<?php echo $search_sts; ?>" />
	  <input type="hidden" name="searchquery_cnt" id="searchquery_cnt" value="<?php echo $search_count; ?>" />
	  <input type="hidden" name="searchquery_group" id="searchquery_group" value="<?php echo $search_group; ?>" />
	  <?php $nonce = wp_create_nonce( 'subscriber-nonce' ); ?>
	  <input type="hidden" name="wp_create_nonce" id="wp_create_nonce" value="<?php echo $nonce; ?>"/>
	<div style="padding-top:10px;"></div>
    <div class="tablenav">
		<div class="alignleft">
			<select name="bulk_action" id="bulk_action" onchange="return _es_action_visible(this.value)">
				<option value=""><?php _e('Bulk Actions', ES_TDOMAIN); ?></option>
				<option value="delete"><?php _e('Delete', ES_TDOMAIN); ?></option>
				<option value="resend"><?php _e('Resend Confirmation', ES_TDOMAIN); ?></option>
				<option value="groupupdate"><?php _e('Update Subscribers Group', ES_TDOMAIN); ?></option>
			</select>
			<select name="es_email_group" id="es_email_group" disabled="disabled">
			<option value=''><?php _e('Select Group', ES_TDOMAIN); ?></option>
			<?php
			$groups = array();
			$groups = es_cls_dbquery::es_view_subscriber_group();
			if(count($groups) > 0)
			{
				$i = 1;
				foreach ($groups as $group)
				{
					?><option value='<?php echo $group["es_email_group"]; ?>'><?php echo $group["es_email_group"]; ?></option><?php
				}
			}
			?>
		  </select>
		  <input type="submit" value="<?php _e('Apply', ES_TDOMAIN); ?>" class="button action" id="doaction" name="doaction">
		</div>
		<div class="alignright">
			<a class="button add-new-h2" title="Click to add new email." href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=add"><?php _e('Add New', ES_TDOMAIN); ?></a> 
			<a class="button add-new-h2" title="Click to import emails." href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=import"><?php _e('Import Email', ES_TDOMAIN); ?></a> 
			<a class="button add-new-h2" title="Click to export emails into CSV file." href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=export"><?php _e('Export Email', ES_TDOMAIN); ?></a> 
			<a class="button add-new-h2" title="Automatically add registered user." href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=sync"><?php _e('Sync Email', ES_TDOMAIN); ?></a> 
			<a class="button add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php _e('Help', ES_TDOMAIN); ?></a> 
		</div>
    </div>
	</form>
	<br />
    <p class="description"><?php echo ES_OFFICIAL; ?></p>
  </div>
</div>