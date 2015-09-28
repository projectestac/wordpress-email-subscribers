<?php
class es_cls_widget
{
	public static function load_subscription($arr)
	{
		$es_name = trim($arr['es_name']);
		$es_desc = trim($arr['es_desc']);
		$es_group = trim($arr['es_group']);
		$url = "'" . home_url() . "'";
		$es = "";
		
		global $es_includes;
		if (!isset($es_includes) || $es_includes !== true) 
		{ 
			$es_includes = true;
			$es = $es . '<link rel="stylesheet" media="screen" type="text/css" href="'.ES_URL.'widget/es-widget.css" />';
		} 
		$es = $es . '<script language="javascript" type="text/javascript" src="'.ES_URL.'widget/es-widget-page.js"></script>';
		$es = $es . "<div>";
		
		if( $es_desc <> "" ) 
		{ 
			$es = $es . '<div class="es_caption">'.$es_desc.'</div>';
		} 
		$es = $es . '<div class="es_msg"><span id="es_msg_pg"></span></div>';
		if( $es_name == "YES" ) 
		{
			$es = $es . '<div class="es_lablebox">'.__('Name', ES_TDOMAIN).'</div>';
			$es = $es . '<div class="es_textbox">';
				$es = $es . '<input class="es_textbox_class" name="es_txt_name_pg" id="es_txt_name_pg" value="" maxlength="225" type="text">';
			$es = $es . '</div>';
		}
		$es = $es . '<div class="es_lablebox">'.__('Email *', ES_TDOMAIN).'</div>';
		$es = $es . '<div class="es_textbox">';
			$es = $es . '<input class="es_textbox_class" name="es_txt_email_pg" id="es_txt_email_pg" onkeypress="if(event.keyCode==13) es_submit_pages('.$url.')" value="" maxlength="225" type="text">';
		$es = $es . '</div>';
		$es = $es . '<div class="es_button">';
			$es = $es . '<input class="es_textbox_button" name="es_txt_button_pg" id="es_txt_button_pg" onClick="return es_submit_pages('.$url.')" value="'.__('Subscribe', ES_TDOMAIN).'" type="button">';
		$es = $es . '</div>';
		if( $es_name != "YES" ) 
		{
			$es = $es . '<input name="es_txt_name_pg" id="es_txt_name_pg" value="" type="hidden">';
		}
		$es = $es . '<input name="es_txt_group_pg" id="es_txt_group_pg" value="'.$es_group.'" type="hidden">';
		$es = $es . '</div>';
		return $es;
	}
}

function es_shortcode( $atts ) 
{
	if ( ! is_array( $atts ) )
	{
		return '';
	}
	
	//[email-subscribers namefield="YES" desc="" group="Public"]
	$es_name = isset($atts['namefield']) ? $atts['namefield'] : 'YES';
	$es_desc = isset($atts['desc']) ? $atts['desc'] : '';
	$es_group = isset($atts['group']) ? $atts['group'] : '';
	
	$arr = array();
	$arr["es_title"] 	= "";
	$arr["es_desc"] 	= $es_desc;
	$arr["es_name"] 	= $es_name;
	$arr["es_group"] 	= $es_group;
	return es_cls_widget::load_subscription($arr);
}

function es_subbox( $namefield = "YES", $desc = "", $group = "" )
{
	$arr = array();
	$arr["es_title"] 	= "";
	$arr["es_desc"] 	= $desc;
	$arr["es_name"] 	= $namefield;
	$arr["es_group"] 	= $group;
	echo es_cls_widget::load_subscription($arr);
}
?>