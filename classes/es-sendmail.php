<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class es_cls_sendmail {
	public static function es_prepare_optin($type= "", $id = 0, $idlist = "") {
		$subscribers = array();
		switch($type) {
			case 'group':
				$subscribers = es_cls_dbquery::es_view_subscriber_bulk($idlist);
				es_cls_sendmail::es_sendmail("optin", $template = 0, $subscribers, $action = "optin-group", "Immediately");
				break;

			case 'single':
				$subscribers = es_cls_dbquery::es_view_subscriber_search($search = "", $id);
				es_cls_sendmail::es_sendmail("optin", $template = 0, $subscribers, $action = "optin-single", "Immediately");
				break;
		}
		return true;
	}

	public static function es_prepare_welcome($id = 0) {
		$subscribers = array();
		$subscribers = es_cls_dbquery::es_view_subscriber_search("", $id);
		es_cls_sendmail::es_sendmail("welcome", $template = 0, $subscribers, $action = "welcome", 0, "Immediately");
	}

	public static function es_prepare_notification( $post_status, $original_post_status, $post_id ) {
		if( ( $post_status == 'publish' ) && ( $original_post_status != 'publish' ) ) {
			$notification = array();

			// $post_id is Object type containing the post information
			// Thus we need to get post_id from $post_id object
			if(is_numeric($post_id)) {
				$post_id = $post_id;
			} else {
				if(is_object($post_id)) {
					$post_id = $post_id->ID;
				} else {
					$post_id = $post_id;
				}
			}

			$notification = es_cls_notification::es_notification_prepare($post_id);

			if ( count($notification) > 0 ) {

				$template_id = $notification[0]["es_note_templ"];
				$template_status = get_post_status( $template_id );	// to confirm if template exists in ES->Templates

				$mailsenttype = $notification[0]["es_note_status"];
				if ( $mailsenttype == "Enable" ) {
					$mailsenttype = "Immediately";
				} elseif ( $mailsenttype == "Cron" ) {
					$mailsenttype = "Cron";
				} else {
					$mailsenttype = "Immediately";
				}

				$subscribers = array();
				$subscribers = es_cls_notification::es_notification_subscribers($notification);

				if ( count($subscribers) > 0 && !empty( $template_status ) ) {
					es_cls_sendmail::es_sendmail( "notification", $template_id, $subscribers, "Post Notification", $post_id,  $mailsenttype );
				}

			}
		}
	}

	// Function to prepare sending Static Newsletters
	public static function es_prepare_newsletter_manual( $template, $mailsenttype, $group ) {

		$subscribers = array();
		$subscribers = es_cls_dbquery::es_subscribers_data_in_group( $group );

		es_cls_sendmail::es_sendmail( "newsletter", $template, $subscribers, "Newsletter", 0, $mailsenttype );
	}

	public static function es_prepare_send_cronmail($cronmailqueue = array(), $crondeliveryqueue = array()) {
		$subscriber = array();
		$wp_mail = false;
		$php_mail = false;
		$type = $cronmailqueue[0]['es_sent_source'];
		$content = $cronmailqueue[0]['es_sent_preview'];
		$subject = $cronmailqueue[0]['es_sent_subject'];
		$cacheid = es_cls_common::es_generate_guid(100);
		$replacefrom = array("<ul><br />", "</ul><br />", "<li><br />", "</li><br />", "<ol><br />", "</ol><br />", "</h2><br />", "</h1><br />");
		$replaceto = array("<ul>", "</ul>", "<li>" ,"</li>", "<ol>", "</ol>", "</h2>", "</h1>");
		$count = 1;

		$settings = es_cls_settings::es_get_all_settings();
		if( trim($settings['ig_es_fromname']) == "" || trim($settings['ig_es_fromemail']) == '' ) {
			get_currentuserinfo();
			$sender_name = $user_login;
			$sender_email = $user_email;
		} else {
			$sender_name = stripslashes($settings['ig_es_fromname']);
			$sender_email = $settings['ig_es_fromemail'];
		}

		$headers  = "From: \"$sender_name\" <$sender_email>\n";
		$headers .= "Return-Path: <" . $sender_email . ">\n";
		$headers .= "Reply-To: \"" . $sender_name . "\" <" . $sender_email . ">\n";

		if( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "WP PLAINTEXT MAIL" ) {
			$wp_mail = true;
		}

		if( $settings['ig_es_emailtype'] == "PHP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP PLAINTEXT MAIL" ) {
			$php_mail = true;
			// Following headers are needed for PHP type only
			$headers .= "MIME-Version: 1.0\n";
			$headers .= "X-Mailer: PHP" . phpversion() . "\n";
		}

		if( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
			$headers .= "Content-Type: text/html; charset=\"". get_bloginfo('charset') . "\"\n";
		} elseif ( $settings['ig_es_emailtype'] == "WP PLAINTEXT MAIL" || $settings['ig_es_emailtype'] == "PHP PLAINTEXT MAIL" ) {
			$headers .= "Content-Type: text/plain; charset=\"". get_bloginfo('charset') . "\"\n";
		}

		$url = home_url('/');
		$viewstatus = '<img src="'.$url.'?es=viewstatus&delvid={{DELVIID}}" width="1" height="1" />';

		foreach ($crondeliveryqueue as $crondelivery) {
			$es_email_id = $crondelivery['es_deliver_emailid'];
			$es_deliver_id = $crondelivery['es_deliver_id'];
			$subscriber = es_cls_dbquery::es_view_subscriber_search("", $es_email_id);
			if(count($subscriber) > 0) {
				$unsublink = $settings['ig_es_unsublink'];
				$unsublink = str_replace("{{DBID}}", $subscriber[0]["es_email_id"], $unsublink);
				$unsublink = str_replace("{{EMAIL}}", $subscriber[0]["es_email_mail"], $unsublink);
				$unsublink = str_replace("{{GUID}}", $subscriber[0]["es_email_guid"], $unsublink);
				$unsublink  = $unsublink . "&cache=".$cacheid;

				$unsubtext = stripslashes($settings['ig_es_unsubcontent']);
				$unsubtext = str_replace("{{LINK}}", $unsublink , $unsubtext);

				if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
					$unsubtext = '<br>' . $unsubtext;
				} else {
					$unsubtext = '\n' . $unsubtext;
				}

				$viewstslink = str_replace("{{DELVIID}}", $es_deliver_id, $viewstatus);

				$content_send = str_replace("{{EMAIL}}", $subscriber[0]["es_email_mail"], $content);
				$content_send = str_replace("{{NAME}}", $subscriber[0]["es_email_name"], $content_send);

				if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
					$content_send = es_cls_registerhook::es_process_template_body($content_send);
					$content_send = str_replace($replacefrom, $replaceto, $content_send);
				} else {
					$content_send = str_replace("<br />", "\r\n", $content_send);
					$content_send = str_replace("<br>", "\r\n", $content_send);
				}

				if( $wp_mail ) {
					wp_mail($subscriber[0]["es_email_mail"], $subject, $content_send . $unsubtext . $viewstslink, $headers);
				} elseif ( $php_mail ) {
					mail($subscriber[0]["es_email_mail"] ,$subject, $content_send . $unsubtext . $viewstslink, $headers);
				}
				es_cls_delivery::es_delivery_ups_cron($es_deliver_id);
				$count = $count + 1;
			}

			if($count % 25 == 0) {
				sleep(60); //sleep 60 seconds for every 25 emails.
			}

		}

		$es_cron_adminmail = get_option('ig_es_cron_adminmail');
		if($es_cron_adminmail != "") {
			$adminmail = $settings['ig_es_adminemail'];
			$crondate = date('Y-m-d G:i:s');
			$count = $count - 1;

			$es_cron_adminmail = str_replace("{{COUNT}}", $count, $es_cron_adminmail);
			$es_cron_adminmail = str_replace("{{DATE}}", $crondate, $es_cron_adminmail);
			$es_cron_adminmail = str_replace("{{SUBJECT}}", $subject, $es_cron_adminmail);

			if( $wp_mail ) {
				$es_cron_adminmail = es_cls_registerhook::es_process_template_body($es_cron_adminmail);
			} elseif ( $php_mail ) {
				$es_cron_adminmail = str_replace("<br />", "\r\n", $es_cron_adminmail);
				$es_cron_adminmail = str_replace("<br>", "\r\n", $es_cron_adminmail);
			}

			if( $wp_mail ) {
				wp_mail($adminmail, "Cron URL has been triggered successfully", $es_cron_adminmail, $headers);
			} elseif ( $php_mail ) {
				mail($adminmail ,"Cron URL has been triggered successfully", $es_cron_adminmail, $headers);
			}
		}
	}

	public static function es_sendmail($type = "", $template = 0, $subscribers = array(), $action = "", $post_id = 0, $mailsenttype = "Immediately") {
		$data = array();
		$wp_mail = false;
		$php_mail = false;
		$unsublink = "";
		$unsubtext = "";
		$sendguid = "";
		$viewstatus = "";
		$viewstslink = "";
		$adminmail = "";
		$adminmailsubject = "";
		$adminmailcontant = "";
		$reportmail = "";
		$currentdate = date('Y-m-d G:i:s');
		$cacheid = es_cls_common::es_generate_guid(100);
		$replacefrom = array("<ul><br />", "</ul><br />", "<li><br />", "</li><br />", "<ol><br />", "</ol><br />", "</h2><br />", "</h1><br />");
		$replaceto = array("<ul>", "</ul>", "<li>" ,"</li>", "<ol>", "</ol>", "</h2>", "</h1>");

		$settings = es_cls_settings::es_get_all_settings();
		$adminmail = $settings['ig_es_adminemail'];
		$es_c_adminmailoption = $settings['ig_es_notifyadmin'];
		$es_c_usermailoption = $settings['ig_es_welcomeemail'];

		if( trim($settings['ig_es_fromname']) == "" || trim($settings['ig_es_fromemail']) == '' ) {
			get_currentuserinfo();
			$sender_name = $user_login;
			$sender_email = $user_email;
		} else {
			$sender_name = stripslashes($settings['ig_es_fromname']);
			$sender_email = $settings['ig_es_fromemail'];
		}

		$headers  = "From: \"$sender_name\" <$sender_email>\n";
		$headers .= "Return-Path: <" . $sender_email . ">\n";
		$headers .= "Reply-To: \"" . $sender_name . "\" <" . $sender_email . ">\n";

		if( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "WP PLAINTEXT MAIL" ) {
			$wp_mail = true;
		}

		if( $settings['ig_es_emailtype'] == "PHP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP PLAINTEXT MAIL" ) {
			$php_mail = true;
			// Following headers are needed for PHP type only
			$headers .= "MIME-Version: 1.0\n";
			$headers .= "X-Mailer: PHP" . phpversion() . "\n";
		}

		if( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
			$headers .= "Content-Type: text/html; charset=\"". get_bloginfo('charset') . "\"\n";
		} elseif ( $settings['ig_es_emailtype'] == "WP PLAINTEXT MAIL" || $settings['ig_es_emailtype'] == "PHP PLAINTEXT MAIL" ) {
			$headers .= "Content-Type: text/plain; charset=\"". get_bloginfo('charset') . "\"\n";
		}

		switch($type) {
			case 'optin':
				$subject = stripslashes($settings['ig_es_confirmsubject']);
				$content = stripslashes($settings['ig_es_confirmcontent']);
				break;

			case 'welcome':
				$subject = stripslashes($settings['ig_es_welcomesubject']);
				$content = stripslashes($settings['ig_es_welcomecontent']);
				break;

			case 'newsletter':
				$template = es_cls_templates::es_template_select($template);
				$subject = stripslashes($template['es_templ_heading']);
				$content = stripslashes($template['es_templ_body']);
				break;

			case 'notification':
				$template = es_cls_templates::es_template_select($template);
				$subject = stripslashes($template['es_templ_heading']);
				$content = stripslashes($template['es_templ_body']);
				$post_link  = "";
				$post_thumbnail  = "";
				$post_thumbnail_link  = "";
				$post = get_post($post_id);
				$post_description_length = 50;					//Change this value to change the {{POSTDESC}} content in the Post Notification. It also considers spaces as a character.

				$post_title  = "";
				$post_title = get_the_title( $post );
				$blog_charset = get_option( 'blog_charset' );
				// using html_entity_decode() because get_the_title() doesn't handle special characters.
				$post_title = html_entity_decode( $post_title, ENT_QUOTES, $blog_charset );
				$subject = str_replace('{{POSTTITLE}}', $post_title, $subject);

				$post_link = get_permalink($post_id);
				$subject = str_replace('{{POSTLINK}}', $post_link, $subject);
				$post_date = $post->post_modified;

				// Get full post
				$post_full = $post->post_content;
				$post_full = wpautop($post_full);

				// Get post description
				$post_description = $post->post_content;
				$post_description = strip_tags(strip_shortcodes($post_description));
				$words = explode(' ', $post_description, $post_description_length + 1);
				if(count($words) > $post_description_length) {
					array_pop($words);
					array_push($words, '...');
					$post_description = implode(' ', $words);
				}

				// Get post excerpt
				$post_excerpt = get_the_excerpt($post);

				// Size of {{POSTIMAGE}}
				if ( (function_exists('has_post_thumbnail')) && (has_post_thumbnail($post_id)) ) {
					$es_post_image_size = get_option( 'ig_es_post_image_size', 'full' );
					switch ( $es_post_image_size ) {
						case 'full':
							$post_thumbnail = get_the_post_thumbnail( $post_id, 'full' );
							break;
						case 'medium':
							$post_thumbnail = get_the_post_thumbnail( $post_id, 'medium' );
							break;
						case 'thumbnail':
							$post_thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
							break;
					}
				}

				if($post_thumbnail != "") {
					$post_thumbnail_link = "<a href='".$post_link."' target='_blank'>".$post_thumbnail."</a>";
				}

				$content = str_replace('{{POSTLINK-ONLY}}', $post_link, $content);

				if($post_link != "") {
					$post_link_with_title = "<a href='".$post_link."' target='_blank'>".$post_title."</a>";
					$content = str_replace('{{POSTLINK-WITHTITLE}}', $post_link_with_title, $content);

					$post_link = "<a href='".$post_link."' target='_blank'>".$post_link."</a>";
				}

				// To get post author name for {{POSTAUTHOR}}
				$post_author_id = $post->post_author;
				$post_author = get_the_author_meta( 'display_name' , $post_author_id );

				$content = str_replace('{{POSTAUTHOR}}', $post_author, $content);
				$content = str_replace('{{POSTTITLE}}', $post_title, $content);
				$content = str_replace('{{POSTLINK}}', $post_link, $content);
				$content = str_replace('{{POSTIMAGE}}', $post_thumbnail_link, $content);
				$content = str_replace('{{POSTDESC}}', $post_description, $content);
				$content = str_replace('{{POSTEXCERPT}}', $post_excerpt, $content);
				$content = str_replace('{{POSTFULL}}', $post_full, $content);
				$content = str_replace('{{DATE}}', $post_date, $content);
				break;
		}

		if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
			$content = str_replace("\r\n", "<br />", $content);
		} else {
			$content = str_replace("<br />", "\r\n", $content);
		}

		if( $type == "newsletter" || $type == "notification" ) {
			$sendguid = es_cls_common::es_generate_guid(60);
			$url = home_url('/');
			$viewstatus = '<img src="'.$url.'?es=viewstatus&delvid={{DELVIID}}" width="1" height="1" />';
			es_cls_sentmail::es_sentmail_ins($sendguid, $qstring = 0, $action, $currentdate, $enddt = "", count($subscribers), $content, $mailsenttype);
		}

		$count = 1;
		if(count($subscribers) > 0) {
			foreach ($subscribers as $subscriber) {
				$to = $subscriber['es_email_mail'];
				$name = $subscriber['es_email_name'];
				if( $name == "" ) {
					$name = $to;
				}
				$group = $subscriber['es_email_group'];

				switch( $type ) {
					case 'optin':
						$content_send = str_replace("{{NAME}}", $name, $content);
						$content_send = str_replace("{{EMAIL}}", $to, $content_send);

						$optinlink = $settings['ig_es_optinlink'];
						$optinlink = str_replace("{{DBID}}", $subscriber["es_email_id"], $optinlink);
						$optinlink = str_replace("{{EMAIL}}", $subscriber["es_email_mail"], $optinlink);
						$optinlink = str_replace("{{GUID}}", $subscriber["es_email_guid"], $optinlink);
						$optinlink  = $optinlink . "&cache=".$cacheid;

						$content_send = str_replace("{{LINK}}", $optinlink , $content_send);
						break;

					case 'welcome':
						$content_send = str_replace("{{NAME}}", $name, $content);
						$content_send = str_replace("{{EMAIL}}", $to, $content_send);
						$content_send = str_replace("{{GROUP}}", $group, $content_send);

						// Making an unsubscribe link
						$unsublink = $settings['ig_es_unsublink'];
						$unsublink = str_replace("{{DBID}}", $subscriber["es_email_id"], $unsublink);
						$unsublink = str_replace("{{EMAIL}}", $subscriber["es_email_mail"], $unsublink);
						$unsublink = str_replace("{{GUID}}", $subscriber["es_email_guid"], $unsublink);
						$unsublink  = $unsublink . "&cache=".$cacheid;
						$content_send = str_replace("{{LINK}}", $unsublink, $content_send);

						$adminmailsubject = stripslashes($settings['ig_es_admin_new_sub_subject']);
						$adminmailcontant = stripslashes($settings['ig_es_admin_new_sub_content']);
						$adminmailcontant = str_replace("{{NAME}}", $name , $adminmailcontant);
						$adminmailcontant = str_replace("{{EMAIL}}", $to, $adminmailcontant);
						$adminmailcontant = str_replace("{{GROUP}}", $group, $adminmailcontant);

						if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
							$adminmailcontant = es_cls_registerhook::es_process_template_body($adminmailcontant, $template);
							$content_send = str_replace($replacefrom, $replaceto, $content_send);
						} else {
							$adminmailcontant = str_replace("<br />", "\r\n", $adminmailcontant);
							$adminmailcontant = str_replace("<br>", "\r\n", $adminmailcontant);
						}
						break;

					case 'newsletter':
						if( $mailsenttype != "Cron" ) { 					// Cron mail not sending by this method
							$unsublink = $settings['ig_es_unsublink'];
							$unsublink = str_replace("{{DBID}}", $subscriber["es_email_id"], $unsublink);
							$unsublink = str_replace("{{EMAIL}}", $subscriber["es_email_mail"], $unsublink);
							$unsublink = str_replace("{{GUID}}", $subscriber["es_email_guid"], $unsublink);
							$unsublink  = $unsublink . "&cache=".$cacheid;

							$unsubtext = stripslashes($settings['ig_es_unsubcontent']);
							$unsubtext = str_replace("{{LINK}}", $unsublink , $unsubtext);
							if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
								$unsubtext = '<br>' . $unsubtext;
							} else {
								$unsubtext = '\n' . $unsubtext;
							}

							$returnid = es_cls_delivery::es_delivery_ins($sendguid, $subscriber["es_email_id"], $subscriber["es_email_mail"], $mailsenttype);

							$viewstslink = str_replace("{{DELVIID}}", $returnid, $viewstatus);
							$content_send = str_replace("{{EMAIL}}", $subscriber["es_email_mail"], $content);
							$content_send = str_replace("{{NAME}}", $subscriber["es_email_name"], $content_send);

							if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
								$content_send = es_cls_registerhook::es_process_template_body($content_send, $template['es_templ_id']);
								$content_send = str_replace($replacefrom, $replaceto, $content_send);
							} else {
								$content_send = str_replace("<br />", "\r\n", $content_send);
								$content_send = str_replace("<br>", "\r\n", $content_send);
							}
						} else {
							es_cls_delivery::es_delivery_ins($sendguid, $subscriber["es_email_id"], $subscriber["es_email_mail"], $mailsenttype);
						}
						break;

					case 'notification':  // notification mail to subscribers
						if( $mailsenttype != "Cron" ) { 					// Cron mail not sending by this method

							$unsublink = $settings['ig_es_unsublink'];
							$unsublink = str_replace("{{DBID}}", $subscriber["es_email_id"], $unsublink);
							$unsublink = str_replace("{{EMAIL}}", $subscriber["es_email_mail"], $unsublink);
							$unsublink = str_replace("{{GUID}}", $subscriber["es_email_guid"], $unsublink);
							$unsublink  = $unsublink . "&cache=".$cacheid;

							$unsubtext = stripslashes($settings['ig_es_unsubcontent']);
							$unsubtext = str_replace("{{LINK}}", $unsublink , $unsubtext);
							if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
								$unsubtext = '<br>' . $unsubtext;
							} else {
								$unsubtext = '\n' . $unsubtext;
							}

							$returnid = es_cls_delivery::es_delivery_ins($sendguid, $subscriber["es_email_id"], $subscriber["es_email_mail"], $mailsenttype);
							$viewstslink = str_replace("{{DELVIID}}", $returnid, $viewstatus);

							$content_send = str_replace("{{EMAIL}}", $subscriber["es_email_mail"], $content);
							$content_send = str_replace("{{NAME}}", $subscriber["es_email_name"], $content_send);

							if ( $settings['ig_es_emailtype'] == "WP HTML MAIL" || $settings['ig_es_emailtype'] == "PHP HTML MAIL" ) {
								$content_send = es_cls_registerhook::es_process_template_body($content_send, $template['es_templ_id']);
								$content_send = str_replace($replacefrom, $replaceto, $content_send);
							} else {
								$content_send = str_replace("<br />", "\r\n", $content_send);
								$content_send = str_replace("<br>", "\r\n", $content_send);
							}
						} else {
							$returnid = es_cls_delivery::es_delivery_ins($sendguid, $subscriber["es_email_id"], $subscriber["es_email_mail"], $mailsenttype);
						}
						break;
				}

				if( $wp_mail ) {  // WP Mail
					// Users mails
					if( $type == "welcome" ) {
						if( $es_c_usermailoption == "YES" ) {
							wp_mail($to, $subject, $content_send . $unsubtext . $viewstslink, $headers);
						}
					} else {
						if( $mailsenttype != "Cron" ) { 					// Cron mail not sending by this method
							wp_mail($to, $subject, $content_send . $unsubtext . $viewstslink, $headers);
						}
					}

					// Admin emails
					if($type == "welcome" && $adminmail != "" && $es_c_adminmailoption == "YES") {
						wp_mail($adminmail, $adminmailsubject, $adminmailcontant, $headers);
					}
				} elseif ( $php_mail ) {		// PHP Mail
					// Users mails
					if( $type == "welcome" ) {
						if( $es_c_usermailoption == "YES" ) {
							mail($to ,$subject, $content_send . $unsubtext . $viewstslink, $headers);
						}
					} else {
						if( $mailsenttype != "Cron" ) { 					// Cron mail not sending by this method
							mail($to, $subject, $content_send . $unsubtext . $viewstslink, $headers);
						}
					}

					// Admin emails
					if( $type == "welcome" && $adminmail != "" && $es_c_adminmailoption == "YES" ) {
						mail($adminmail, $adminmailsubject, $adminmailcontant, $headers);
					}
				}
				$count = $count + 1;
			}
		}

		if( $type == "newsletter" || $type == "notification" ) {
			$count = $count - 1;
			es_cls_sentmail::es_sentmail_ups($sendguid, $subject);
			if( $adminmail != "" ) {

				$subject = get_option('ig_es_sentreport_subject', 'nosubjectexists');
				if ( $subject == "" || $subject == "nosubjectexists") {
					$subject = es_cls_common::es_sent_report_subject();
				}

				if( $mailsenttype == "Cron" ) {
					$subject = $subject . " - Cron Email scheduled";
				}

				if( $wp_mail ) {
					$reportmail = get_option('ig_es_sentreport', 'nooptionexists');
					if ( $reportmail == "" || $reportmail == "nooptionexists") {
						$reportmail = es_cls_common::es_sent_report_html();
					}
					$reportmail = es_cls_registerhook::es_process_template_body($reportmail, $template['es_templ_id']);
				} elseif( $php_mail ) {
					$reportmail = get_option('ig_es_sentreport', 'nooptionexists');
					if ( $reportmail == "" || $reportmail == "nooptionexists") {
						$reportmail = es_cls_common::es_sent_report_plain();
					}
					$reportmail = str_replace("<br />", "\r\n", $reportmail);
					$reportmail = str_replace("<br>", "\r\n", $reportmail);
				}
				$enddate = date('Y-m-d G:i:s');

				$reportmail = str_replace("{{COUNT}}", $count, $reportmail);
				$reportmail = str_replace("{{UNIQUE}}", $sendguid, $reportmail);
				$reportmail = str_replace("{{STARTTIME}}", $currentdate, $reportmail);
				$reportmail = str_replace("{{ENDTIME}}", $enddate, $reportmail);

				if( $wp_mail ) {
					wp_mail($adminmail, $subject, $reportmail, $headers);
				} elseif ( $php_mail ) {
					mail($adminmail ,$subject, $reportmail, $headers);
				}
			}
		}
	}
}