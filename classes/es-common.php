<?php
class es_cls_common
{
	public static function es_disp_status($value)
	{
		$returnstring = "";
		switch ($value) 
		{
			case "Confirmed":
				$returnstring = '<span style="color:#006600;font-weight:bold;">Confirmed</span>';
				break;
			case "Unconfirmed":
				$returnstring = '<span style="color:#FF0000">Unconfirmed</span>';
				break;
			case "Unsubscribed":
				$returnstring = '<span style="color:#999900">Unsubscribed</span>';
				break;
			case "Single Opt In":
				$returnstring = '<span style="color:#0000FF">Single Opt In</span>';
				break;
			case "Viewed":
				$returnstring = '<span style="color:#00CC00;font-weight:bold">Viewed</span>';
				break;
			case "Nodata":
				$returnstring = '<span style="color:#999900;">Nodata</span>';
				break;
			case "Disable":
				$returnstring = '<span style="color:#FF0000">Disable</span>';
				break;
			case "In Queue":
				$returnstring = '<span style="color:#FF0000">In Queue</span>';
				break;
			case "Sent":
				$returnstring = '<span style="color:#00FF00;font-weight:bold;">Sent</span>';
				break;
			case "Cron Mail":
				$returnstring = '<span style="color:#ffd700;font-weight:bold;">Cron Mail</span>';
				break;	
			case "Instant Mail":
				$returnstring = '<span style="color:#993399;">Instant Mail</span>';
				break;
			default:
       			$returnstring = $value;
		}
		return $returnstring;
	}
	
	public static function es_readcsv($csvFile)
	{
		$file_handle = fopen($csvFile, 'r');
		while (!feof($file_handle) ) 
		{
			$line_of_text[] = fgetcsv($file_handle, 1024);
		}
		fclose($file_handle);
		return $line_of_text;
	}
	
	public static function es_txt_clean($excerpt, $substr=0) 
	{
		$string = strip_tags(str_replace('[...]', '...', $excerpt));
		if ($substr>0) 
		{
			$string = substr($string, 0, $substr);
		}
		return $string;
	}
	
	public static function es_generate_guid($length = 30) 
	{
		$guid = rand();
		$length = 6;
		$rand1 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
		$rand2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
		$rand3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
		$rand4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
		$rand5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
		$rand6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
		$guid = $rand1."-".$rand2."-".$rand3."-".$rand4."-".$rand5;
		return $guid;
	}	
	
	public static function es_client_os() 
	{
		$http_user_agent = $_SERVER['HTTP_USER_AGENT'];
		return $http_user_agent;
	}
	
	public static function download($arrays, $filename = 'output.csv', $option) 
	{
		$string = '';
		$c=0;
		$filename = 'email-subscribers'.$option.'_'.date('Ymd_His').".csv";
		foreach($arrays AS $array) 
		{
			$val_array = array();
			$key_array = array();
			foreach($array AS $key => $val) 
			{
				$key_array[] = $key;
				$val = str_replace('"', '""', $val);
				$val_array[] = "\"$val\"";
			}
			if($c == 0) 
			{
				$string .= implode(",", $key_array)."\n";
			}
			$string .= implode(",", $val_array)."\n";
			$c++;
		}
		ob_clean();
		header('Content-type: application/ms-excel');
		header('Content-Disposition: attachment; filename='.$filename);
		echo $string;
	}
	
	public static function es_sent_report_subject() 
	{
		$report = "Newsletter Sent Report";
		return $report;
	}
	
	public static function es_sent_report_plain() 
	{
		$report = "";
		$report = $report. "Hi Admin,\n\n";
		$report = $report. "Mail has been sent successfully to ###COUNT### email(s). Please find the details below.\n\n";
		$report = $report. "Unique ID : ###UNIQUE### \n";
		$report = $report. "Start Time: ###STARTTIME### \n";
		$report = $report. "End Time: ###ENDTIME### \n";
		$report = $report. "For more information, Login to your Dashboard and go to Sent Mails menu in Email Subscribers. \n\n";
		$report = $report. "Thank You \n";
		$report = $report. "www.gopiplus.com \n";
		return $report;
	}
	
	public static function es_sent_report_html() 
	{
		$report = "";
		$report = $report. "Hi Admin, <br/><br/>";
		$report = $report. "Mail has been sent successfully to ###COUNT### email(s). Please find the details below.<br/><br/>";
		$report = $report. "Unique ID : ###UNIQUE### <br/>";
		$report = $report. "Start Time: ###STARTTIME### <br/>";
		$report = $report. "End Time: ###ENDTIME### <br/>";
		$report = $report. "For more information, Login to your Dashboard and go to Sent Mails menu in Email Subscribers. <br/><br/>";
		$report = $report. "Thank You <br/>";
		$report = $report. "www.gopiplus.com <br/>";
		return $report;
	}
	
	public static function es_special_letters() 
	{
		$string = "/[\'^$%&*()}{@#~?><>,|=_+\"]/";
		return $string;
	}
}

class es_cls_security
{
	public static function es_check_number($value) 
	{
		if(!is_numeric($value)) 
		{ 
			die('<p>Security check failed. Are you sure you want to do this?</p>'); 
		}
	}
	
	public static function es_check_guid($value) 
	{
		$value_length1 = strlen($value);
		$value_noslash = str_replace("-", "", $value);
		$value_length2 = strlen($value_noslash);
		
		if( $value_length1 != 34 || $value_length2 != 30)
		{
			die('<p>Security check failed. Are you sure you want to do this?</p>'); 
		}
		
		if (preg_match('/[^a-z]/', $value_noslash))
		{
			die('<p>Security check failed. Are you sure you want to do this?</p>'); 
		}
	}
}
?>