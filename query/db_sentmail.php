<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
class es_cls_sentmail
{
	public static function es_sentmail_select($id = 0, $offset = 0, $limit = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$arrRes = array();
		$sSql = "SELECT * FROM `".$prefix."es_sentdetails` where 1=1";
		if($id > 0)
		{
			$sSql = $sSql . " and es_sent_id=".$id;
			$arrRes = $wpdb->get_row($sSql, ARRAY_A);
		}
		else
		{
			$sSql = $sSql . " order by es_sent_id desc limit $offset, $limit";
			$arrRes = $wpdb->get_results($sSql, ARRAY_A);
		}
		return $arrRes;
	}
	
	public static function es_sentmail_count($id = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$result = '0';
		if($id > 0)
		{
			$sSql = $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `".$prefix."es_sentdetails` WHERE `es_sent_id` = %d", array($id));
		}
		else
		{
			$sSql = "SELECT COUNT(*) AS `count` FROM `".$prefix."es_sentdetails`";
		}
		$result = $wpdb->get_var($sSql);
		return $result;
	}
	
	public static function es_sentmail_delete($id = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$Sentdetails = array();
		$Sentdetails = es_cls_sentmail::es_sentmail_select($id, 0, 1);
		if(count($Sentdetails) > 0)
		{
			$es_deliver_sentguid = $Sentdetails['es_sent_guid'];	
			if($es_deliver_sentguid <> "")
			{
				$sSql = $wpdb->prepare("DELETE FROM `".$prefix."es_deliverreport` WHERE `es_deliver_sentguid` = %s", $es_deliver_sentguid);
				$wpdb->query($sSql);
			}	
			$sSql = $wpdb->prepare("DELETE FROM `".$prefix."es_sentdetails` WHERE `es_sent_id` = %d LIMIT 1", $id);
			$wpdb->query($sSql);
		}

		return true;
	}
	
	public static function es_sentmail_ins($guid = "", $qstring = 0, $source = "", $startdt = "", $enddt = "", $count = "", $preview = "", $mailsenttype = "")
	{
		global $wpdb;
		$returnid = 0;
		$prefix = $wpdb->prefix;
		$currentdate = date('Y-m-d G:i:s'); 
		
		if($mailsenttype == "Instant Mail")
		{
			$es_sent_status = "Sent";
		}
		else
		{
			$es_sent_status = "In Queue";
		}
		
		$sSql = $wpdb->prepare("INSERT INTO `".$prefix."es_sentdetails` (`es_sent_guid`, `es_sent_qstring`, `es_sent_source`,
								`es_sent_starttime`, `es_sent_endtime`, `es_sent_count`, `es_sent_preview`, `es_sent_status`, `es_sent_type`) 
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)", 
								array($guid, $qstring, $source, $startdt, $enddt, $count, $preview, $es_sent_status, $mailsenttype));			
		$wpdb->query($sSql);
		return true;
	}
	
	public static function es_sentmail_ups($guid = "", $sentsubject = "")
	{
		global $wpdb;
		$returnid = 0;
		$prefix = $wpdb->prefix;
		$currentdate = date('Y-m-d G:i:s'); 
		$sSql = $wpdb->prepare("UPDATE `".$prefix."es_sentdetails` SET `es_sent_endtime` = %s, `es_sent_subject` = %s 
			WHERE es_sent_guid = %s LIMIT 1", array($currentdate, $sentsubject, $guid));	
		$wpdb->query($sSql);
		return true;
	}
	
	public static function es_sentmail_cronmail_inqueue()
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$arrRes = array();
		$sSql = "SELECT * FROM `".$prefix."es_sentdetails` where es_sent_type='Cron Mail' and es_sent_status='In Queue'";
		$sSql = $sSql . " order by es_sent_id limit 0, 1";
		$arrRes = $wpdb->get_results($sSql, ARRAY_A);
		return $arrRes;
	}
	
	public static function es_sentmail_cronmail_ups($guid = "")
	{
		global $wpdb;
		$returnid = 0;
		$prefix = $wpdb->prefix;
		$currentdate = date('Y-m-d G:i:s'); 
		$sSql = $wpdb->prepare("UPDATE `".$prefix."es_sentdetails` SET `es_sent_endtime` = %s, `es_sent_status` = %s 
			WHERE es_sent_guid = %s LIMIT 1", array($currentdate, "Sent", $guid));	
		$wpdb->query($sSql);
		return true;
	}
}
?>