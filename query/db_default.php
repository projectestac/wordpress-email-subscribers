<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

class es_cls_default {
	public static function es_pluginconfig_default() {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$result = es_cls_settings::es_setting_count(0);
		if ($result == 0) {
			$admin_email = get_option('admin_email');
			$blogname = get_option('blogname');

			if($admin_email == "") {
				$admin_email = "admin@gmail.com";
			}

			$home_url = home_url('/');
			$optinlink = $home_url . "?es=optin&db=###DBID###&email=###EMAIL###&guid=###GUID###";
			$unsublink = $home_url . "?es=unsubscribe&db=###DBID###&email=###EMAIL###&guid=###GUID###"; 

			$es_c_fromname = "Admin";
			$es_c_fromemail = $admin_email;
			$es_c_mailtype = "WP HTML MAIL";
			$es_c_adminmailoption = "YES";
			$es_c_adminemail = $admin_email;

// XTEC ************ MODIFICAT - Support localization
// 2015.10.09 @dgras
			$es_c_adminmailsubject = $blogname . (" Subscripci&oacute; nova de correu");
			$es_c_adminmailcontant = "Hola Administrador, \r\n\r\n Hem rebut una sol·licitud de subscripci&oacute; d'aquesta adre&ccedil;a de correu electr&ograve;nic per rebre els articles del nostre lloc web. \r\n\r\n Correu electr&ograve;nic : ###EMAIL### \r\n Nom : ###NAME### \r\n\r\nGr&agrave;cies\r\n".$blogname;
			$es_c_usermailoption = "YES";
			$es_c_usermailsubject = $blogname ." Benvingut al nostre butlletí";
			$es_c_usermailcontant = "Hola ###NAME###, \r\n\r\n Hem rebut una sol·licitud de subscripci&oacute; d'aquesta adre&ccedil;a de correu electr&ograve;nic per rebre el bullet&iacute; del nostre lloc web.\r\n\r\nGr&agrave;cies\r\n".$blogname;
			$es_c_optinoption = "Double Opt In";
			$es_c_optinsubject = $blogname . " confirmeu la subscripció";
			$es_c_optincontent = "Hola ###NAME###,\r\n\r\n Hem rebut una petici&oacute; de subscripci&oacute; d'aquesta adre&ccedil;a de correu electr&ograve;nic. Confirmeu <a href='###LINK###'>fent clic aqu&iacute;</a>. Si no podeu fer clic a l'enlla&ccedil; anterior, si us plau, utilitzeu l'URL seg&uuml;ent.\r\n\r\n ###LINK### \r\n\r\nGr&agrave;cies\r\n".$blogname;
			$es_c_optinlink = $optinlink;
			$es_c_unsublink = $unsublink;
			$es_c_unsubtext = "Si no esteu interessats en rebre correus des de ".$blogname." <a href='###LINK###'>feu clic aqu&iacute;</a> per donar-vos de baixa";
			$es_c_unsubhtml = "Gr&agrave;cies, heu estat donat de baixa amb &egrave;xit. Ja no haur&iacute;eu de rebre not&iacute;cies nostres.";
			$es_c_subhtml = "Gr&agrave;cies, heu estat subscrit amb &egrave;xit al nostre butllet&iacute; de not&iacute;cies.";
			$es_c_message1 = "Vaja... Aquesta subscripci&oacute; no s'ha pogut completar, ho sentim. L'adre&ccedil;a de correu electr&ograve;nic est&agrave; bloquejada o ja est&agrave; subscrita. Gr&agrave;cies.";
			$es_c_message2 = "Vaja... Estem tenint algun error t&egrave;cnic. Torneu-ho a provar o contacteu amb l'administrador.";

// @Comment: The code below would be the right way to do but it does not work
/*
            $es_c_adminmailsubject = $blogname . __(" New email subscription", 'email-subscribers');
            $es_c_adminmailcontant = __("Hi Admin,", 'email-subscribers')." \r\n\r\n". __("We have received a request to subscribe new email address to receive emails from our website.", 'email-subscribers')." \r\n\r\n". __("Email", 'email-subscribers').": ###EMAIL### \r\n".__("Name", 'email-subscribers'). ": ###NAME### \r\n\r\n". __("Thank You", 'email-subscribers')."\r\n".$blogname;
            $es_c_usermailoption = "YES";
            $es_c_usermailsubject = $blogname . __(" Welcome to our newsletter", 'email-subscribers');
            $es_c_usermailcontant = __("Hi ###NAME###,", 'email-subscribers'). "\r\n\r\n" . __("We have received a request to subscribe this email address to receive newsletter from our website.", 'email-subscribers'). "\r\n\r\n". __("Thank You", 'email-subscribers')."\r\n".$blogname;
            $es_c_optinoption = "Double Opt In";
            $es_c_optinsubject = $blogname . __(" confirm subscription",'email-subscribers');
            $es_c_optincontent = __("Hi ###NAME###,", 'email-subscribers'). "\r\n\r\n". __("A newsletter subscription request for this email address was received. Please confirm it by <a href='###LINK###'>clicking here</a>. If you cannot click the link, please use the following link.", 'email-subscribers')."\r\n\r\n ###LINK### \r\n\r\n".__("Thank You", 'email-subscribers')."\r\n".$blogname;
            $es_c_optinlink = $optinlink;
            $es_c_unsublink = $unsublink;
            $es_c_unsubtext = sprintf(__("No longer interested email from %s?. Please <a href='###LINK###'>click here</a> to unsubscribe", 'email-subscribers'), $blogname);
            $es_c_unsubhtml = __("Thank You, You have been successfully unsubscribed. You will no longer hear from us.", 'email-subscribers');
            $es_c_subhtml = __("Thank You, You have been successfully subscribed to our newsletter.", 'email-subscribers');
            $es_c_message1 = __("Oops.. This subscription cant be completed, sorry. The email address is blocked or already subscribed. Thank you.", 'email-subscribers');
            $es_c_message2 = __("Oops.. We are getting some technical error. Please try again or contact admin.", 'email-subscribers');
*/
//************ ORIGINAL
/*
			$es_c_adminmailsubject = $blogname . " New email subscription";
			$es_c_adminmailcontant = "Hi Admin, \r\n\r\nWe have received a request to subscribe new email address to receive emails from our website. \r\n\r\nEmail: ###EMAIL### \r\nName : ###NAME### \r\n\r\nThank You\r\n".$blogname;
			$es_c_usermailoption = "YES";
			$es_c_usermailsubject = $blogname . " Welcome to our newsletter";
			$es_c_usermailcontant = "Hi ###NAME###, \r\n\r\nWe have received a request to subscribe this email address to receive newsletter from our website. \r\n\r\nThank You\r\n".$blogname." \r\n\r\n No longer interested in emails from ".$blogname."?. Please <a href='###LINK###'>click here</a> to unsubscribe";
			$es_c_optinoption = "Double Opt In";
			$es_c_optinsubject = $blogname . " confirm subscription";
			$es_c_optincontent = "Hi ###NAME###, \r\n\r\nA newsletter subscription request for this email address was received. Please confirm it by <a href='###LINK###'>clicking here</a>.\r\n\r\nIf you still cannot subscribe, please click this link : \r\n ###LINK### \r\n\r\nThank You\r\n".$blogname;
			$es_c_optinlink = $optinlink;
			$es_c_unsublink = $unsublink;
			$es_c_unsubtext = "No longer interested in emails from ".$blogname."?. Please <a href='###LINK###'>click here</a> to unsubscribe";
			$es_c_unsubhtml = "Thank You, You have been successfully unsubscribed. You will no longer hear from us.";
			$es_c_subhtml = "Thank You, You have been successfully subscribed to our newsletter.";
			$es_c_message1 = "Oops.. This subscription cant be completed, sorry. The email address is blocked or already subscribed. Thank you.";
			$es_c_message2 = "Oops.. We are getting some technical error. Please try again or contact admin.";
*/
//************ FI

			$sSql = $wpdb->prepare("INSERT INTO `".$prefix."es_pluginconfig` 
					(`es_c_fromname`,`es_c_fromemail`, `es_c_mailtype`, `es_c_adminmailoption`, `es_c_adminemail`, `es_c_adminmailsubject`,
					`es_c_adminmailcontant`,`es_c_usermailoption`, `es_c_usermailsubject`, `es_c_usermailcontant`, `es_c_optinoption`, `es_c_optinsubject`,
					`es_c_optincontent`,`es_c_optinlink`, `es_c_unsublink`, `es_c_unsubtext`, `es_c_unsubhtml`, `es_c_subhtml`, `es_c_message1`, `es_c_message2`)
					VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", 
					array($es_c_fromname,$es_c_fromemail, $es_c_mailtype, $es_c_adminmailoption, $es_c_adminemail, $es_c_adminmailsubject,
					$es_c_adminmailcontant,$es_c_usermailoption, $es_c_usermailsubject, $es_c_usermailcontant, $es_c_optinoption, $es_c_optinsubject,
					$es_c_optincontent,$es_c_optinlink, $es_c_unsublink, $es_c_unsubtext, $es_c_unsubhtml, $es_c_subhtml, $es_c_message1, $es_c_message2));
			$wpdb->query($sSql);
		}
		return true;
	}

	public static function es_subscriber_default() {
		$result = es_cls_dbquery::es_view_subscriber_count(0);
		if ($result == 0) {
			$form["es_email_mail"] = get_option('admin_email');
			$form["es_email_name"] = "Admin";
//XTEC ************ MODIFICAT - Changed default group from Public to Portada
//2016.03.29 @sarjona
			$form["es_email_group"] = 'Portada';
//************ ORIGINAL
/*
			$form["es_email_group"] = "Public";
*/
//************ FI
			$form["es_email_status"] = "Confirmed";
			es_cls_dbquery::es_view_subscriber_ins($form, "insert");

			$form["es_email_mail"] = "a.example@example.com";
			$form["es_email_name"] = "Example";
//XTEC ************ MODIFICAT - Changed default group from Public to Portada
//2016.03.29 @sarjona
			$form["es_email_group"] = 'Portada';
//************ ORIGINAL
/*
			$form["es_email_group"] = "Public";
*/
//************ FI
			$form["es_email_status"] = "Confirmed";
			es_cls_dbquery::es_view_subscriber_ins($form, "insert");
		}
		return true;
	}

	public static function es_template_default() {
		$result = es_cls_compose::es_template_count(0);
		if ($result == 0) {

// XTEC ************ MODIFICAT - Support localization
// 2015.10.09 @dgras
			$form['es_templ_heading'] = "S'ha publicat un article nou:  ###POSTTITLE###";
			$es_b = "Hola ###NAME###,\r\n\r\n";
			$es_b = $es_b . "Hem publicat un article nou al nostre lloc web. ###POSTTITLE###\r\n";
			$es_b = $es_b . "###POSTDESC###\r\n";
			$es_b = $es_b . "Podeu veure l'últim article a ";
			$es_b = $es_b . "###POSTLINK###\r\n";
			$es_b = $es_b . "Heu rebut aquest correu perquè vau demanar que se us notifiqués la publicació d'articles nous\r\n\r\n";
			$es_b = $es_b . "Gràcies i salutacions\r\n";

// @Comment: The code below would be the right way to do but it does not work
/*
            $form['es_templ_heading'] = __('New post published ###POSTTITLE###', 'email-subscribers');
            $es_b = __("Hello ###NAME###", 'email-subscribers').",\r\n\r\n";
            $es_b = $es_b . __("We have published new blog in our website. ###POSTTITLE###", 'email-subscribers')."\r\n";
            $es_b = $es_b . "###POSTDESC###\r\n";
            $es_b = $es_b . __("You may view the latest post at ", 'email-subscribers');
            $es_b = $es_b . "###POSTLINK###\r\n";
            $es_b = $es_b . __("You received this e-mail because you asked to be notified when new updates are posted.", 'email-subscribers')."\r\n\r\n";
            $es_b = $es_b . __("Thanks & Regards", 'email-subscribers')."\r\n";
*/
			$es_b = $es_b . "Admin";
			$form['es_templ_body'] = $es_b;
			$form['es_templ_status'] = 'Published';
			$form['es_email_type'] = 'Dynamic Template';
			$action = es_cls_compose::es_template_ins($form, $action = "insert");

			$form['es_templ_heading'] = "Notificació d'article nou ###POSTTITLE###";
			$es_b = "Hola ###EMAIL###,\r\n\r\n";
			$es_b = $es_b . "Hem publicat un article nou al nostre lloc web. ###POSTTITLE###\r\n";
			$es_b = $es_b . "###POSTIMAGE###\r\n";
			$es_b = $es_b . "###POSTFULL###\r\n";
			$es_b = $es_b . "Podeu veure l'últim article a ";
			$es_b = $es_b . "###POSTLINK###\r\n";
			$es_b = $es_b . "Heu rebut aquest correu perquè vau demanar que se us notifiqués la publicació d'articles nous\r\n\r\n";
			$es_b = $es_b . "Gràcies i salutacions\r\n";

// @Comment: The code below would be the right way to do but it does not work
/*
            $form['es_templ_heading'] = __('Post notification ###POSTTITLE###', 'email-subscribers');
            $es_b = __("Hello ###EMAIL###", 'email-subscribers').",\r\n\r\n";
            $es_b = $es_b . __("We have published new blog in our website. ###POSTTITLE###", 'email-subscribers')."\r\n";
            $es_b = $es_b . "###POSTIMAGE###\r\n";
            $es_b = $es_b . "###POSTFULL###\r\n";
            $es_b = $es_b . __("You may view the latest post at ", 'email-subscribers');
            $es_b = $es_b . "###POSTLINK###\r\n";
            $es_b = $es_b . __("You received this e-mail because you asked to be notified when new updates are posted.", 'email-subscribers')."\r\n\r\n";
            $es_b = $es_b . __("Thanks & Regards", 'email-subscribers')."\r\n";
*/
            $es_b = $es_b . "Admin";
			$form['es_templ_body'] = $es_b;
			$form['es_templ_status'] = 'Published';
			$form['es_email_type'] = 'Dynamic Template';
			$action = es_cls_compose::es_template_ins($form, $action = "insert");

			$Sample = '<strong style="color: #990000"> Subscriptors de correu</strong>';
			$Sample .= "<p>
							L\'extensió subscripcions de correu de correu té diferents opcions per enviar butlletins als subscriptors.
							Té una pàgina separada amb un editor HTML per crear	un butlletí amb aquest format.
							L\'extensió disposa d\'opcions per enviar correus de notificació als subscriptors quan es publiquen articles nous al lloc web. També té una pàgina per poder afegir i eliminar les categories a les que s\'enviaran les notificacions.
							Utilitzant les opcions de l\'extensió d\'importació i exportació els administradors podran importar fàcilment els usuaris registrats.
						</p>";
			$Sample .= ' <strong style="color: #990000">Característiques de l\'extensió</strong><ol>';
			$Sample .= " <li>Correu de notificació als subscriptors quan es publiquin articles nous.</li>";
			$Sample .= " <li>Giny de subscripció</li><li>Correu de subscripció amb confirmació per correu i subscripció simple per facilitar la subscripció.</li>";
			$Sample .= " <li>Notificació per correu electrònic a l\'administrador quan els usuaris es subscriguin (Opcional)</li>";
			$Sample .= " <li>Correu de benvinguda automàtic als subscriptors (Opcional).</li>";
			$Sample .= " <li>Enllaç per donar-se de baixa del correu.</li>";
			$Sample .= " <li>Importació / Exportació dels correus dels subscriptors.</li>";
			$Sample .= " <li>Editor d\'HTML per redactar el butlletí.</li>";
			$Sample .= " </ol>";
			$Sample .= " <strong>Gràcies i salutacions</strong><br>Admin";

            $form['es_templ_heading'] = 'Butlletí Hola Món';

// @Comment: The code below would be the right way to do but it does not work
/*
            $Sample = '<strong style="color: #990000"> '. __("Email subscribers",'email-subscribers') .'</strong><p>'.__("Email subscribers plugin has options to send newsletters to subscribers. It has a separate page with HTML editor to create a HTML newsletter.",'email-subscribers');
            $Sample .= __('Also have options to send notification email to subscribers when new posts are published to your blog. Separate page available to include and exclude categories to send notifications.','email-subscribers');
            $Sample .= __("Using plugin Import and Export options admins can easily import registered users and commenters to subscriptions list.",'email-subscribers').'</p>';
            $Sample .= ' <strong style="color: #990000">'.__("Plugin Features",'email-subscribers').'</strong><ol>';
            $Sample .= ' <li>'.__("Send notification email to subscribers when new posts are published.",'email-subscribers').'</li>';
            $Sample .= ' <li>'.__("Subscription box",'email-subscribers').'</li><li>'.__("Double opt-in and single opt-in facility for subscriber.",'email-subscribers') .'</li>';
            $Sample .= ' <li>'.__("Email notification to admin when user signs up (Optional).",'email-subscribers').'</li>';
            $Sample .= ' <li>'.__("Automatic welcome mail to subscriber (Optional).",'email-subscribers').'</li>';
            $Sample .= ' <li>'.__("Unsubscribe link in the mail.",'email-subscribers') .'</li>';
            $Sample .= ' <li>'.__("Import/Export subscriber emails.",'email-subscribers') .'</li>';
            $Sample .= ' <li>'.__("HTML editor to compose newsletter.",'email-subscribers') .'</li>';
            $Sample .= ' </ol>';
            $Sample .= ' <p>'.__("Plugin live demo and video tutorial available on the official website. Check official website for more information.",'email-subscribers') .'</p>';
            $Sample .= ' <strong>'.__("Thanks & Regards",'email-subscribers') .'</strong><br>Admin';

            $form['es_templ_heading'] = __('Hello World Newsletter','email-subscribers');
*/
			$form['es_templ_body'] = $Sample;
			$form['es_templ_status'] = 'Published';
			$form['es_email_type'] = 'Static Template';

//************ ORIGINAL
/*
			$form['es_templ_heading'] = 'New post published ###POSTTITLE###';
			$es_b = "Hello ###NAME###,\r\n\r\n";
			$es_b = $es_b . "We have published new blog in our website. ###POSTTITLE###\r\n";
			$es_b = $es_b . "###POSTDESC###\r\n";
			$es_b = $es_b . "You may view the latest post at ";
			$es_b = $es_b . "###POSTLINK###\r\n";
			$es_b = $es_b . "You received this e-mail because you asked to be notified when new updates are posted.\r\n\r\n";
			$es_b = $es_b . "Thanks & Regards\r\n";
			$es_b = $es_b . "Admin";
			$form['es_templ_body'] = $es_b;
			$form['es_templ_status'] = 'Published';
			$form['es_email_type'] = 'Dynamic Template';
			$action = es_cls_compose::es_template_ins($form, $action = "insert");

			$form['es_templ_heading'] = 'Post notification ###POSTTITLE###';
			$es_b = "Hello ###EMAIL###,\r\n\r\n";
			$es_b = $es_b . "We have published new blog in our website. ###POSTTITLE###\r\n";
			$es_b = $es_b . "###POSTIMAGE###\r\n";
			$es_b = $es_b . "###POSTFULL###\r\n";
			$es_b = $es_b . "You may view the latest post at ";
			$es_b = $es_b . "###POSTLINK###\r\n";
			$es_b = $es_b . "You received this e-mail because you asked to be notified when new updates are posted.\r\n\r\n";
			$es_b = $es_b . "Thanks & Regards\r\n";
			$es_b = $es_b . "Admin";
			$form['es_templ_body'] = $es_b;
			$form['es_templ_status'] = 'Published';
			$form['es_email_type'] = 'Dynamic Template';
			$action = es_cls_compose::es_template_ins($form, $action = "insert");

			$Sample = '<strong style="color: #990000"> Email Subscribers</strong><p>Email Subscribers plugin has options to send newsletters to subscribers. It has a separate page with HTML editor to create a HTML newsletter.'; 
			$Sample .= ' Also have options to send notification email to subscribers when new posts are published to your blog. Separate page available to include and exclude categories to send notifications.';
			$Sample .= ' Using plugin Import and Export options admins can easily import registered users and commenters to subscriptions list.</p>';
			$Sample .= ' <strong style="color: #990000">Plugin Features</strong><ol>';
			$Sample .= ' <li>Send notification email to subscribers when new posts are published.</li>';
			$Sample .= ' <li>Subscription box.</li><li>Double opt-in and single opt-in facility for subscriber.</li>';
			$Sample .= ' <li>Email notification to admin when user signs up (Optional).</li>';
			$Sample .= ' <li>Automatic welcome mail to subscriber (Optional).</li>';
			$Sample .= ' <li>Unsubscribe link in the mail.</li>';
			$Sample .= ' <li>Import/Export subscriber emails.</li>';
			$Sample .= ' <li>HTML editor to compose newsletter.</li>';
			$Sample .= ' </ol>';
			$Sample .= ' <p>Plugin live demo and video tutorial available on the official website. Check official website for more information.</p>';
			$Sample .= ' <strong>Thanks & Regards</strong><br>Admin';

			$form['es_templ_heading'] = 'Hello World Newsletter';
			$form['es_templ_body'] = $Sample;
			$form['es_templ_status'] = 'Published';
			$form['es_email_type'] = 'Static Template';
*/
//************ FI

			$action = es_cls_compose::es_template_ins($form, $action = "insert");
		}
		return true;
	}
	
	public static function es_notifications_default() {
		$result = es_cls_notification::es_notification_count(0);
		if ($result == 0) {
			$form["es_note_group"] = "Public";
			$form["es_note_status"] = "Enable";
			$form["es_note_templ"] = "1";

			$listcategory = "";
			$args = array( 'hide_empty' => 0, 'orderby' => 'name', 'order' => 'ASC' );
			$categories = get_categories($args); 
			$total = count($categories);
			$i = 1;
			foreach($categories as $category) {
				$listcategory = $listcategory . " ##" . $category->cat_name . "## ";
				if($i < $total) {
					$listcategory = $listcategory .  "--";
				}
				$i = $i + 1;
			}
			$form["es_note_cat"] = $listcategory;
			es_cls_notification::es_notification_ins($form, "insert");
		}
		return true;
	}
}