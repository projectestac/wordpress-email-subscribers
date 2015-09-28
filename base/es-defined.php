<?php
$es_plugin_name='email-subscribers';
$es_plugin_folder_name = dirname(dirname(plugin_basename(__FILE__)));
$es_current_folder=dirname(dirname(__FILE__));

if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if(!defined('ES_TDOMAIN')) define('ES_TDOMAIN', $es_plugin_name);
if(!defined('ES_PLUGIN_NAME')) define('ES_PLUGIN_NAME', $es_plugin_name);
if(!defined('ES_PLUGIN_DISPLAY')) define('ES_PLUGIN_DISPLAY', "Email Subscribers");
if(!defined('ES_PLG_DIR')) define('ES_PLG_DIR', dirname($es_current_folder).DS);
if(!defined('ES_DIR')) define('ES_DIR', $es_current_folder.DS);
if(!defined('ES_URL')) define('ES_URL',plugins_url().'/'.strtolower('email-subscribers').'/');
define('ES_FILE',ES_DIR.'email-subscribers.php');
if(!defined('ES_FAV')) define('ES_FAV', 'http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/');
if(!defined('ES_ADMINURL')) define('ES_ADMINURL', get_option('siteurl') . '/wp-admin/admin.php');
define('ES_OFFICIAL', 'Check official website for more information <a target="_blank" href="'.ES_FAV.'">click here</a>');
global $es_includes;
?>