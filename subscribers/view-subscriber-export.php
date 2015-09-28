<?php
//if (!session_id())
//{
//    session_start();
//}
?>
<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<script language="javaScript" src="<?php echo ES_URL; ?>subscribers/view-subscriber.js"></script>
<?php
//$_SESSION['es_exportcsv'] = "YES"; 
$home_url = home_url('/');
$cnt_subscriber = 0;
$cnt_users = 0;
$cnt_comment_author = 0;
$cnt_subscriber = es_cls_dbquery::es_view_subscriber_count(0);
$cnt_users = $wpdb->get_var("select count(DISTINCT user_email) from ". $wpdb->prefix . "users");
$cnt_comment_author = $wpdb->get_var("SELECT count(DISTINCT comment_author_email) from ". $wpdb->prefix . "comments WHERE comment_author_email <> ''");
?>

<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
  <h2><?php _e(ES_PLUGIN_DISPLAY, ES_TDOMAIN); ?></h2>
  <div class="tool-box">
  <h3 class="title"><?php _e('Export email address in csv format', ES_TDOMAIN); ?></h3>
  <form name="frm_es_subscriberexport" method="post">
  <table width="100%" class="widefat" id="straymanage">
    <thead>
      <tr>
        <th scope="col"><?php _e('Sno', ES_TDOMAIN); ?></th>
        <th scope="col"><?php _e('Export option', ES_TDOMAIN); ?></th>
		<th scope="col"><?php _e('Total email', ES_TDOMAIN); ?></th>
        <th scope="col"><?php _e('Action', ES_TDOMAIN); ?></th>
      </tr>
    </thead>
    <tfoot>
      <tr>
        <th scope="col"><?php _e('Sno', ES_TDOMAIN); ?></th>
        <th scope="col"><?php _e('Export option', ES_TDOMAIN); ?></th>
		<th scope="col"><?php _e('Total email', ES_TDOMAIN); ?></th>
        <th scope="col"><?php _e('Action', ES_TDOMAIN); ?></th>
      </tr>
    </tfoot>
    <tbody>
      <tr>
        <td>1</td>
        <td><?php _e('Subscriber email address', ES_TDOMAIN); ?></td>
		<td><?php echo $cnt_subscriber; ?></td>
        <td><a onClick="javascript:_es_exportcsv('<?php echo $home_url. "?es=export"; ?>', 'view_subscriber')" href="javascript:void(0);"><?php _e('Click to export csv', ES_TDOMAIN); ?></a> </td>
      </tr>
      <tr class="alternate">
        <td>2</td>
        <td><?php _e('Registered email address', ES_TDOMAIN); ?></td>
		<td><?php echo $cnt_users; ?></td>
        <td><a onClick="javascript:_es_exportcsv('<?php echo $home_url. "?es=export"; ?>', 'registered_user')" href="javascript:void(0);"><?php _e('Click to export csv', ES_TDOMAIN); ?></a> </td>
      </tr>
      <tr>
        <td>3</td>
        <td><?php _e('Comments author email address', ES_TDOMAIN); ?></td>
		<td><?php echo $cnt_comment_author; ?></td>
        <td><a onClick="javascript:_es_exportcsv('<?php echo $home_url. "?es=export"; ?>', 'commentposed_user')" href="javascript:void(0);"><?php _e('Click to export csv', ES_TDOMAIN); ?></a> </td>
      </tr>
    </tbody>
  </table>
  </form>
  <div class="tablenav">
	  <h2>
		<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>/wp-admin/admin.php?page=es-view-subscribers&amp;ac=add"><?php _e('Add Email', ES_TDOMAIN); ?></a> 
		<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>/wp-admin/admin.php?page=es-view-subscribers&amp;ac=import"><?php _e('Import Email', ES_TDOMAIN); ?></a>
		<a class="button add-new-h2" href="<?php echo ES_ADMINURL; ?>/wp-admin/admin.php?page=es-view-subscribers"><?php _e('Back', ES_TDOMAIN); ?></a>
		<a class="button add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php _e('Help', ES_TDOMAIN); ?></a>
	  </h2>
  </div>
  <div style="height:10px;"></div>
  <p class="description"><?php echo ES_OFFICIAL; ?></p>
  </div>
</div>