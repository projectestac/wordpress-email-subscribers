<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
class es_cls_group
{
	public static function es_group_select($id = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$arrRes = array();
		$sSql = "SELECT * FROM `".$prefix."es_group` where 1=1";
		if($id > 0)
		{
			$sSql = $sSql . " and es_group_id=".$id;
			$arrRes = $wpdb->get_row($sSql, ARRAY_A);
		}
		else
		{
			$arrRes = $wpdb->get_results($sSql, ARRAY_A);
		}
		return $arrRes;
	}
	
	public static function es_group_count($id = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$result = '0';
		if($id > 0)
		{
			$sSql = $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `".$prefix."es_group` WHERE `es_group_id` = %d", array($id));
		}
		else
		{
			$sSql = "SELECT COUNT(*) AS `count` FROM `".$prefix."es_group`";
		}
		$result = $wpdb->get_var($sSql);
		return $result;
	}
	
	public static function es_group_delete($id = 0)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$sSql = $wpdb->prepare("DELETE FROM `".$prefix."es_group` WHERE `es_group_id` = %d LIMIT 1", $id);
		$wpdb->query($sSql);
		return true;
	}
	
	public static function es_group_ins($data = array(), $action = "insert")
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		$guid = es_cls_common::es_generate_guid(60);

		if($action == "insert")
		{
			$sSql = $wpdb->prepare("INSERT INTO `".$prefix."es_group` (`es_group_name`,
			`es_group_status`, `es_group_type`, `es_group_guid`) VALUES(%s, %s, %s, %s)", 
			array($data["es_group_name"], $data["es_group_status"], $data["es_group_type"], $guid));
		}
		elseif($action == "update")
		{
			$sSql = $wpdb->prepare("UPDATE `".$prefix."es_group` SET `es_group_name` = %s, `es_group_status` = %s, `es_group_type` = %s 
			 WHERE es_group_id = %d	LIMIT 1", 
			array($data["es_group_name"], $data["es_group_status"], $data["es_group_type"], $data["es_group_id"]));
		}
		
		echo $sSql;
		
		$wpdb->query($sSql);
		return true;
	}
	
}
?>