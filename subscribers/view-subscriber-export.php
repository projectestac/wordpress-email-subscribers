<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
  die('You are not allowed to call this page directly.');
}

//XTEC ************ MODIFICAT - To fix bug when exporting CSV
//2015.12.07 @sarjona
$home_url = preg_replace('/^http:/i', 'https:', home_url('/'));
//************ ORIGINAL
/*
$home_url = home_url('/');
*/
//************ FI

// Total Subscribers (with all status)
$cnt_subscriber = 0;
$cnt_subscriber = es_cls_dbquery::es_view_subscriber_count(0);

// WordPress Registered Users
$cnt_users = 0;
$cnt_users = $wpdb->get_var( "SELECT count(DISTINCT user_email) from ". $wpdb->prefix . "users" );

// Users who comments on blog posts
$cnt_comment_author = 0;
$cnt_comment_author = $wpdb->get_var( "SELECT count(DISTINCT comment_author_email) FROM ". $wpdb->prefix . "comments WHERE comment_author_email != ''" );

?>

<div class="wrap">
	<h2 style="margin-bottom:1em;">
		<?php echo __( 'Export Email Addresses', ES_TDOMAIN ); ?>
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=add"><?php echo __( 'Add New Subscriber', ES_TDOMAIN ); ?></a>
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=import"><?php echo __( 'Import', ES_TDOMAIN ); ?></a>

		<!-- XTEC ************ AFEGIT - Modify the visiblity if the user is not a xtec_super_admin -->
		<!-- 2017.02.15 @xaviernietosanchez -->
		<?php if ( is_xtec_super_admin() ) { ?>
		<!-- ************ FI -->
		<a class="add-new-h2" href="<?php echo ES_ADMINURL; ?>?page=es-view-subscribers&amp;ac=sync"><?php echo __( 'Sync', ES_TDOMAIN ); ?></a>
		<!-- XTEC ************ AFEGIT - Modify the visiblity if the user is not a xtec_super_admin -->
		<!-- 2017.02.15 @xaviernietosanchez -->
		<?php } ?>
		<!-- ************ FI -->

		<a class="add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php echo __( 'Help', ES_TDOMAIN ); ?></a>
	</h2>
	<div class="tool-box">
		<form name="frm_es_subscriberexport" method="post">
			<table width="100%" class="widefat" id="straymanage">
				<thead>
					<tr>
						<th scope="col"><?php echo __( 'Sno', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Type of List to Export', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Total Emails', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Action', ES_TDOMAIN ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col"><?php echo __( 'Sno', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Type of List to Export', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Total Emails', ES_TDOMAIN ); ?></th>
						<th scope="col"><?php echo __( 'Action', ES_TDOMAIN ); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td><?php echo __( '1', ES_TDOMAIN ); ?></td>
						<td><?php echo __( 'All Subscribers List', ES_TDOMAIN ); ?></td>
						<td><?php echo $cnt_subscriber; ?></td>
						<td><a onClick="javascript:_es_exportcsv('<?php echo $home_url. "?es=export"; ?>', 'view_subscriber')" href="javascript:void(0);"><?php echo __( 'Click to Export in CSV', ES_TDOMAIN ); ?></a></td>
					</tr>
					<tr class="alternate">
						<td><?php echo __( '2', ES_TDOMAIN ); ?></td>
						<td><?php echo __( 'WordPress Registered Users', ES_TDOMAIN ); ?></td>
						<td><?php echo $cnt_users; ?></td>
						<td><a onClick="javascript:_es_exportcsv('<?php echo $home_url. "?es=export"; ?>', 'registered_user')" href="javascript:void(0);"><?php echo __( 'Click to Export in CSV', ES_TDOMAIN ); ?></a></td>
					</tr>
					<tr>
						<td><?php echo __( '3', ES_TDOMAIN ); ?></td>
						<td><?php echo __( 'Commented Authors', ES_TDOMAIN ); ?></td>
						<td><?php echo $cnt_comment_author; ?></td>
						<td><a onClick="javascript:_es_exportcsv('<?php echo $home_url. "?es=export"; ?>', 'commentposed_user')" href="javascript:void(0);"><?php echo __( 'Click to Export in CSV', ES_TDOMAIN ); ?></a></td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
	<div style="height:10px;"></div>

	<!--XTEC ************ MODIFICAT - Modify the visiblity if the user is not a xtec_super_admin -->
	<!-- 2015.10.01 @dgras-->
	<?php if(is_xtec_super_admin()) : ?>
	  <p class="description"><?php echo ES_OFFICIAL; ?></p>
	<?php endif; ?>
	<!--************ ORIGINAL	-->
	<!--
	<p class="description"><?php echo ES_OFFICIAL; ?></p>
	-->
	<!--************ FI-->

</div>
