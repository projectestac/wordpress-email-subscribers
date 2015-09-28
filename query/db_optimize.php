<?php
class es_cls_optimize
{
	public static function es_optimize_setdetails()
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		
		$total = es_cls_sentmail::es_sentmail_count($id = 0);
		if ($total > 10)
		{
			$delete = $total - 10;
			$sSql = "DELETE FROM `".$prefix."es_sentdetails` ORDER BY es_sent_id ASC LIMIT ".$delete;
			$wpdb->query($sSql);
		}
		
		$sSql = "DELETE FROM `".$prefix."es_deliverreport` WHERE es_deliver_sentguid NOT IN";
		$sSql = $sSql . " (SELECT es_sent_guid FROM `".$prefix."es_sentdetails`)";
		$wpdb->query($sSql);
		return true;
	}
}
?>