<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

if ( !empty( $_POST ) && ! wp_verify_nonce( $_REQUEST['wp_create_nonce'], 'subscriber-nonce' ) ) {
	die('<p>Security check failed.</p>');
}

$es_c_email_subscribers_ver = get_option('email-subscribers');
if ( $es_c_email_subscribers_ver != "2.9" ) {
	?><div class="error fade">
		<p>
			Note: You have recently upgraded the plugin and your tables are not sync. 
			Please <a title="Sync plugin tables." href="<?php echo ES_ADMINURL; ?>?page=es-settings&amp;ac=sync"><?php echo __( 'Click Here', ES_TDOMAIN ); ?></a> to sync the table. 
			This is mandatory and it will not affect your data.
		</p>
	</div><?php
}

// Form submitted, check the data
$search_sts = isset($_POST['searchquery_sts']) ? $_POST['searchquery_sts'] : '';
$search_count = isset($_POST['searchquery_cnt']) ? $_POST['searchquery_cnt'] : '1';
$search_group = isset($_POST['searchquery_group']) ? $_POST['searchquery_group'] : 'ALL';

if (isset($_POST['frm_es_display']) && $_POST['frm_es_display'] == 'yes') {
	$did = isset($_GET['did']) ? $_GET['did'] : '0';
	es_cls_security::es_check_number($did);
	
	$es_success = '';
	$es_success_msg = FALSE;

	if ( (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] != 'delete') && ($_POST['frm_es_bulkaction'] != 'resend' && $_POST['frm_es_bulkaction'] != 'groupupdate') && ($_POST['frm_es_bulkaction'] != 'search_sts' && $_POST['frm_es_bulkaction'] != 'search_cnt') && ($_POST['frm_es_bulkaction'] != 'search_group') ) {

		//	Just security thingy that wordpress offers us
		check_admin_referer('es_form_show');
				
		// First check if ID exist with requested ID
		$result = es_cls_dbquery::es_view_subscriber_count($did);
		if ($result != '1') {
			?><div class="error fade">
				<p><strong>
					<?php echo __( 'Oops, selected details does not exists.', ES_TDOMAIN ); ?>
				</strong></p>
			</div><?php
		} else {
			// Form submitted, check the action
			if (isset($_GET['ac']) && $_GET['ac'] == 'del' && isset($_GET['did']) && $_GET['did'] != '') {
				//	Delete selected record from the table
				es_cls_dbquery::es_view_subscriber_delete($did);
				
				//	Set success message
				$es_success_msg = TRUE;
				$es_success = __( 'Record deleted.', ES_TDOMAIN );
			}

			if (isset($_GET['ac']) && $_GET['ac'] == 'resend' && isset($_GET['did']) && $_GET['did'] != '') {
				$did = isset($_GET['did']) ? $_GET['did'] : '0';
				$setting = array();
				$setting = es_cls_settings::es_setting_select(1);
				if( $setting['es_c_optinoption'] != "Double Opt In" ) {
					?>
					<div class="error fade">
						<p><strong>
							<?php echo __( 'To send confirmation email, please change the Opt-in option to Double Opt In.', ES_TDOMAIN ); ?>
						</strong></p>
					</div>
					<?php
				} else {
					es_cls_sendmail::es_prepare_optin("single", $did, $idlist = "");
					es_cls_dbquery::es_view_subscriber_upd_status("Unconfirmed", $did);
					$es_success_msg = TRUE;
					$es_success  = __( 'Confirmation email resent successfully.', ES_TDOMAIN );
				}
			}
		}
	} else {
		check_admin_referer('es_form_show');
		
		if (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'delete') {

			$chk_delete = isset($_POST['chk_delete']) ? $_POST['chk_delete'] : '';

			if(!empty($chk_delete)) {			
				$count = count($chk_delete);
				for($i=0; $i<$count; $i++) {
					$del_id = $chk_delete[$i];
					es_cls_dbquery::es_view_subscriber_delete($del_id);
				}

				//	Set success message
				$es_success_msg = TRUE;
				$es_success = __( 'Record deleted.', ES_TDOMAIN );
			} else {
				?>
				<div class="error fade">
					<p><strong>
						<?php echo __( 'Oops, No record was selected.', ES_TDOMAIN ); ?>
					</strong></p>
				</div>
				<?php
			}
		} elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'resend') {

			$chk_delete = isset($_POST['chk_delete']) ? $_POST['chk_delete'] : '';
			
			$setting = array();
			$setting = es_cls_settings::es_setting_select(1);
			if( $setting['es_c_optinoption'] != "Double Opt In" ) {
				?>
				<div class="error fade">
					<p><strong>
						<?php echo __( 'To send confirmation mail, please change the Opt-in option to Double Opt In.', ES_TDOMAIN ); ?>
					</strong></p>
				</div>
				<?php
			} else {
				if(!empty($chk_delete)) {			
					$count = count($chk_delete);
					$idlist = "";
					for($i = 0; $i<$count; $i++) {
						$del_id = $chk_delete[$i];
						if($i < 1) {
							$idlist = $del_id;
						} else {
							$idlist = $idlist . ", " . $del_id;
						}
					}
					es_cls_sendmail::es_prepare_optin("group", 0, $idlist);
					es_cls_dbquery::es_view_subscriber_upd_status("Unconfirmed", $idlist);
					$es_success_msg = TRUE;
					$es_success = __('Confirmation email(s) Resent Successfully.', ES_TDOMAIN );
				} else {
					?>
					<div class="error fade">
						<p><strong>
							<?php echo __( 'Oops, No record was selected.', ES_TDOMAIN ); ?>
						</strong></p>
					</div>
					<?php
				}
			}
		} elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'groupupdate') {

			$chk_delete = isset($_POST['chk_delete']) ? $_POST['chk_delete'] : '';
			if(!empty($chk_delete)) {			
				$es_email_group = isset($_POST['es_email_group']) ? $_POST['es_email_group'] : '';
				if ($es_email_group != "") {
					$count = count($chk_delete);
					$idlist = "";
					for($i = 0; $i < $count; $i++) {
						$del_id = $chk_delete[$i];
						if($i < 1) {
							$idlist = $del_id;
						} else {
							$idlist = $idlist . ", " . $del_id;
						}
					}
					es_cls_dbquery::es_view_subscriber_upd_group($es_email_group, $idlist);
					$es_success_msg = TRUE;
					$es_success = __( 'Selected subscribers group updated.', ES_TDOMAIN );
				} else {
					?>
					<div class="error fade">
						<p><strong>
							<?php echo __( 'Oops, New group name was not selected.', ES_TDOMAIN ); ?>
						</strong></p>
					</div>
					<?php
				}
			} else {
				?>
				<div class="error fade">
					<p><strong>
						<?php echo __( 'Oops, No record was selected.', ES_TDOMAIN ); ?>
					</strong></p>
				</div>
				<?php
			}
		} elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'search_sts') {
			// Nothing
		} elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'search_cnt') {
			// Nothing
		} elseif (isset($_POST['frm_es_bulkaction']) && $_POST['frm_es_bulkaction'] == 'search_group') {
			// Nothing
		}
	}
	
	if ($es_success_msg == TRUE) {
		?>
		<div class="updated fade">
			<p><strong>
				<?php echo $es_success; ?>
			</strong></p>
		</div>
		<?php
	}
}

?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php echo __( ES_PLUGIN_DISPLAY, ES_TDOMAIN ); ?></h2>
	<h3>
		<?php echo __( 'View Subscribers', ES_TDOMAIN ); ?>
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=add"><?php echo __( 'Add New Subscriber', ES_TDOMAIN ); ?></a>
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=import"><?php echo __( 'Import', ES_TDOMAIN ); ?></a>
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=export"><?php echo __( 'Export', ES_TDOMAIN ); ?></a>
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=sync"><?php echo __( 'Sync', ES_TDOMAIN ); ?></a>
		<a class="add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php echo __( 'Help', ES_TDOMAIN ); ?></a>
	</h3>
	<div class="tool-box">
		<form name="frm_es_display" method="post" onsubmit="return _es_bulkaction()">
			<?php
			$myData = array();
			$offset = 0;
			$limit = 200;

			if ( $search_count == 0 ) {
				$limit = 9999;
			}

			if ( $search_count > 1 ) {
				$offset = $search_count;
			}

			if ( $search_count == 1001 ) {
				$limit = 1000;
			} elseif ( $search_count == 2001 ) {
				$limit = 3000;
			} elseif ( $search_count == 5001 ) {
				$limit = 5000;
			}

			$myData = es_cls_dbquery::es_view_subscribers_details(0, $search_sts, $offset, $limit, $search_group);
			?>

			<div class="tablenav">
				<span style="text-align:left;">
					<select name="bulk_action" id="bulk_action" onchange="return _es_action_visible(this.value)">
						<option value=""><?php echo __( 'Bulk Actions', ES_TDOMAIN ); ?></option>
						<option value="delete"><?php echo __( 'Delete', ES_TDOMAIN ); ?></option>
						<option value="resend"><?php echo __( 'Resend Confirmation', ES_TDOMAIN ); ?></option>
						<option value="groupupdate"><?php echo __( 'Update Subscribers Group', ES_TDOMAIN ); ?></option>
					</select>
					<select name="es_email_group" id="es_email_group" disabled="disabled">
						<option value=''><?php echo __( 'Select Group', ES_TDOMAIN ); ?></option>
						<?php
						$groups = array();
						$groups = es_cls_dbquery::es_view_subscriber_group();
						if(count($groups) > 0) {
							$i = 1;
							foreach ($groups as $group) {
								?><option value='<?php echo $group["es_email_group"]; ?>'>
									<?php echo $group["es_email_group"]; ?>
								</option><?php
							}
						}
						?>
					</select>
					<input type="submit" value="<?php echo __( 'Apply', ES_TDOMAIN ); ?>" class="button action" id="doaction" name="doaction">
				</span>
				<span style="float:right;">
					<select name="search_group_action" id="search_group_action" onchange="return _es_search_group_action(this.value)">
						<option value=""><?php echo __( 'All Groups', ES_TDOMAIN ); ?></option>
							<?php
								$groups = array();
								$groups = es_cls_dbquery::es_view_subscriber_group();
								if(count($groups) > 0) {
									$i = 1;
									foreach ($groups as $group) {
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
						<option value=""><?php echo __( 'All Status', ES_TDOMAIN ); ?></option>
						<option value="Confirmed" <?php if($search_sts=='Confirmed') { echo 'selected="selected"' ; } ?>><?php echo __( 'Confirmed', ES_TDOMAIN ); ?></option>
						<option value="Unconfirmed" <?php if($search_sts=='Unconfirmed') { echo 'selected="selected"' ; } ?>><?php echo __( 'Unconfirmed', ES_TDOMAIN ); ?></option>
						<option value="Unsubscribed" <?php if($search_sts=='Unsubscribed') { echo 'selected="selected"' ; } ?>><?php echo __( 'Unsubscribed', ES_TDOMAIN ); ?></option>
						<option value="Single Opt In" <?php if($search_sts=='Single Opt In') { echo 'selected="selected"' ; } ?>><?php echo __( 'Single Opt In', ES_TDOMAIN ); ?></option>
					</select>
					<select name="search_count_action" id="search_count_action" onchange="return _es_search_count_action(this.value)">
						<option value="1" <?php if($search_count=='1') { echo 'selected="selected"' ; } ?>><?php echo __( '1 to 200 emails', ES_TDOMAIN ); ?></option>
						<option value="201" <?php if($search_count=='201') { echo 'selected="selected"' ; } ?>><?php echo __( '201 to 400', ES_TDOMAIN ); ?></option>
						<option value="401" <?php if($search_count=='401') { echo 'selected="selected"' ; } ?>><?php echo __( '401 to 600', ES_TDOMAIN ); ?></option>
						<option value="601" <?php if($search_count=='601') { echo 'selected="selected"' ; } ?>><?php echo __( '601 to 800', ES_TDOMAIN ); ?></option>
						<option value="801" <?php if($search_count=='801') { echo 'selected="selected"' ; } ?>><?php echo __( '801 to 1000', ES_TDOMAIN ); ?></option>
						<option value="1001" <?php if($search_count=='1001') { echo 'selected="selected"' ; } ?>><?php echo __( '1001 to 2000', ES_TDOMAIN ); ?></option>
						<option value="2001" <?php if($search_count=='2001') { echo 'selected="selected"' ; } ?>><?php echo __( '2001 to 5000', ES_TDOMAIN ); ?></option>
						<option value="5001" <?php if($search_count=='5001') { echo 'selected="selected"' ; } ?>><?php echo __( '5001 to 10000', ES_TDOMAIN ); ?></option>
						<option value="0" <?php if($search_count=='0') { echo 'selected="selected"' ; } ?>><?php echo __( 'Display All', ES_TDOMAIN );?></option>
					</select>
				</span>
			</div>

			<table width="100%" class="widefat" id="straymanage">
				<thead>
					<tr>
						<th scope="col" class="check-column" style="padding: 17px 2px;">
							<input type="checkbox" name="es_checkall" id="es_checkall" onClick="_es_checkall('frm_es_display', 'chk_delete[]', this.checked);" />
						</th>
						<th scope="col"><?php echo __( 'Sno', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Email Address', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Name', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Status', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Group', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Submission Date<br>(YYYY-MM-DD)', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Action', ES_TDOMAIN ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" class="check-column" style="padding: 17px 2px;">
							<input type="checkbox" name="es_checkall" id="es_checkall" onClick="_es_checkall('frm_es_display', 'chk_delete[]', this.checked);" />
						</th>
						<th scope="col"><?php echo __( 'Sno', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Email Address', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Name', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Status', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Group', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Submission Date<br>(YYYY-MM-DD)', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Action', ES_TDOMAIN ); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<?php 
						$i = 0;
						$displayisthere = FALSE;
						if(count($myData) > 0) {
							if ($offset == 0) {
								$i = 1;
							} else {
								$i = $offset;
							}
							foreach ($myData as $data) {					
								?>
								<tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; } ?>">
									<td align="left"><input name="chk_delete[]" id="chk_delete[]" type="checkbox" value="<?php echo $data['es_email_id'] ?>" /></td>
									<td><?php echo $i; ?></td>
									<td><?php echo $data['es_email_mail']; ?></td>
									<td><?php echo stripslashes($data['es_email_name']); ?></td>     
									<td><?php echo es_cls_common::es_disp_status($data['es_email_status']); ?></td>
									<td><?php echo stripslashes($data['es_email_group']); ?></td>
									<td><?php echo date_format(date_create($data['es_email_created']), 'Y-m-d'); ?></td>
									<td>
										<div> 
											<span class="edit">
												<a title="Edit" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=edit&amp;did=<?php echo $data['es_email_id']; ?>">
													<?php echo __( 'Edit', ES_TDOMAIN ); ?>
												</a> |
											</span> 
											<span class="trash">
												<a onClick="javascript:_es_delete('<?php echo $data['es_email_id']; ?>')" href="javascript:void(0);">
													<?php echo __( 'Delete', ES_TDOMAIN ); ?>
												</a>
											</span>
											<?php
											if($data['es_email_status'] != "Confirmed") {
												?>
												<span class="edit"> 
													| <a onClick="javascript:_es_resend('<?php echo $data['es_email_id']; ?>')" href="javascript:void(0);">
													<?php echo __( 'Resend Confirmation', ES_TDOMAIN ); ?>
													</a>
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
						} else {
							?>
							<tr>
								<td colspan="7" align="center"><?php echo __( 'No records available.', ES_TDOMAIN ); ?></td>
							</tr>
							<?php 
						}
						?>
				</tbody>
			</table>
			<?php wp_nonce_field('es_form_show'); ?>
			<input type="hidden" name="frm_es_display" id="frm_es_display" value="yes"/>
			<input type="hidden" name="frm_es_bulkaction" id="frm_es_bulkaction" value=""/>
			<input type="hidden" name="searchquery_sts" id="searchquery_sts" value="<?php echo $search_sts; ?>" />
			<input type="hidden" name="searchquery_cnt" id="searchquery_cnt" value="<?php echo $search_count; ?>" />
			<input type="hidden" name="searchquery_group" id="searchquery_group" value="<?php echo $search_group; ?>" />
			<?php $nonce = wp_create_nonce( 'subscriber-nonce' ); ?>
			<input type="hidden" name="wp_create_nonce" id="wp_create_nonce" value="<?php echo $nonce; ?>"/>
		</form>
	</div>
	<div style="height:10px;"></div>
	<p class="description"><?php echo ES_OFFICIAL; ?></p>
</div>