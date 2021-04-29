<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
class es_cls_delivery
{
	public static function es_delivery_select($sentguid = "", $offset = 0, $limit = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$arrRes = array();
		$sSql = "SELECT * FROM `".$prefix."es_deliverreport` where 1=1";
		if($sentguid <> "")
		{
			$sSql = $sSql . " and es_deliver_sentguid='".$sentguid."'";
			$sSql = $sSql . " order by es_deliver_id desc limit $offset, $limit";
		}
		$arrRes = $wpdb->get_results($sSql, ARRAY_A);
		return $arrRes;
	}
	
	public static function es_delivery_count($sentguid = "")
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$result = '0';
		if($sentguid <> "")
		{
			$sSql = $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `".$prefix."es_deliverreport` WHERE `es_deliver_sentguid` = %s", array($sentguid));
		}
		$result = $wpdb->get_var($sSql);
		return $result;
	}
	
	public static function es_delivery_ins($guid = "", $dbid = 0, $email = "", $mailsenttype = "")
	{
		global $wpdb;
		$returnid = 0;
		$prefix = $wpdb->prefix;
		
		if($mailsenttype == "Instant Mail")
		{
			$es_sent_status = "Sent";
			$currentdate = date('Y-m-d G:i:s'); 
		}
		else
		{
			$es_sent_status = "In Queue";
			$currentdate = "0000-00-00"; 
		}
		
		$sSql = $wpdb->prepare("INSERT INTO `".$prefix."es_deliverreport` (`es_deliver_sentguid`,`es_deliver_emailid`, `es_deliver_emailmail`,
								`es_deliver_sentdate`,`es_deliver_status`,`es_deliver_sentstatus`,`es_deliver_senttype`) 
								VALUES (%s, %s, %s, %s, %s, %s, %s)", array($guid, $dbid, $email, $currentdate, "Nodata", $es_sent_status, $mailsenttype));			
		$wpdb->query($sSql);
		$returnid = $wpdb->insert_id;
		return $returnid;
	}
	
	public static function es_delivery_ups($id = 0)
	{
		global $wpdb;
		$returnid = 0;
		$prefix = $wpdb->prefix;
		$currentdate = date('Y-m-d G:i:s'); 
		if(is_numeric($id))
		{
			$sSql = $wpdb->prepare("UPDATE `".$prefix."es_deliverreport` SET `es_deliver_status` = %s, 
						`es_deliver_viewdate` = %s WHERE es_deliver_id = %d LIMIT 1", array("Viewed", $currentdate, $id));	
			$wpdb->query($sSql);
		}
		return true;
	}
	
	public static function es_delivery_ups_cron($id = 0)
	{
		global $wpdb;
		$returnid = 0;
		$prefix = $wpdb->prefix;
		$currentdate = date('Y-m-d G:i:s'); 
		if(is_numeric($id))
		{
			$sSql = $wpdb->prepare("UPDATE `".$prefix."es_deliverreport` SET `es_deliver_sentstatus` = %s, 
						`es_deliver_sentdate` = %s WHERE es_deliver_id = %d LIMIT 1", array("Sent", $currentdate, $id));	
			$wpdb->query($sSql);
		}
		return true;
	}
	
	public static function es_delivery_cronmail_inqueue($limit = 50, $sentguid)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$arrRes = array();
		$sSql = "";
		$sSql = $wpdb->prepare("SELECT * FROM `".$prefix."es_deliverreport` where es_deliver_senttype=%s 
		and es_deliver_sentstatus=%s and es_deliver_sentguid = %s order by es_deliver_id limit 0, $limit", array("Cron Mail", "In Queue", $sentguid));
		//echo $sSql;
		$arrRes = $wpdb->get_results($sSql, ARRAY_A);
		return $arrRes;
	}
	
	public static function es_delivery_cronmail_count($sentguid)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$result = '0';
		if($sentguid <> "")
		{
			$sSql = $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `".$prefix."es_deliverreport` WHERE `es_deliver_sentguid` = %s 
				and es_deliver_senttype=%s and es_deliver_sentstatus = %s", array($sentguid, "Cron Mail", "In Queue"));
			$result = $wpdb->get_var($sSql);
		}
		return $result;
	}
}
?>