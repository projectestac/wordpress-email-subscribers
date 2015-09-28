<?php
/*
Plugin Name: Email Subscribers
Plugin URI: http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/
Description: Email subscribers plugin has options to send newsletters to subscribers. It has a separate page with HTML editor to create a HTML newsletter. Also have options to send notification email to subscribers when new posts are published to your blog. Separate page available to include and exclude categories to send notifications.
Version: 2.9.1
Author: Gopi Ramasamy
Donate link: http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/
Author URI: http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*  
Copyright 2015 Email subscribers (http://www.gopiplus.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'base'.DIRECTORY_SEPARATOR.'es-defined.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'es-stater.php');
add_action('admin_menu', array( 'es_cls_registerhook', 'es_adminmenu' ));
register_activation_hook(ES_FILE, array( 'es_cls_registerhook', 'es_activation' ));
register_deactivation_hook(ES_FILE, array( 'es_cls_registerhook', 'es_deactivation' ));
add_action( 'widgets_init', array( 'es_cls_registerhook', 'es_widget_loading' ));
add_shortcode( 'email-subscribers', 'es_shortcode' );
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'es-directly.php');

function es_textdomain() 
{
	  load_plugin_textdomain( 'email-subscribers' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'es_textdomain');
add_action( 'transition_post_status', array( 'es_cls_sendmail', 'es_prepare_notification' ), 10, 3 );

add_action( 'user_register', 'es_sync_registereduser');
?>