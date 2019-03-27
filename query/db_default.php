<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class es_cls_default {
	public static function es_pluginconfig_default() {

        global $wpdb;
        $prefix = $wpdb->prefix;

        //Needs work-temp fixed in v3.3.3
        $result = es_cls_dbquery::es_view_subscriber_count(0);
        if ($result == 0) {

            $admin_email = get_option('admin_email');
            $blogname = get_option('blogname');

            if ($admin_email == "") {
                $admin_email = "admin@gmail.com";
            }

            $home_url = home_url('/');
            $optinlink = $home_url . "?es=optin&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}";
            $unsublink = $home_url . "?es=unsubscribe&db={{DBID}}&email={{EMAIL}}&guid={{GUID}}";

            $default = array();
            $default['ig_es_fromname'] = $blogname;
            $default['ig_es_fromemail'] = $admin_email;
            $default['ig_es_emailtype'] = "WP HTML MAIL";
            $default['ig_es_notifyadmin'] = "YES";
            $default['ig_es_adminemail'] = $admin_email;
            $default['ig_es_admin_new_sub_subject'] = $blogname . " - New email subscription";
            $default['ig_es_admin_new_sub_content'] = "Hi Admin,\r\n\r\nCongratulations! You have a new subscriber.\r\n\r\nName : {{NAME}}\r\nEmail: {{EMAIL}}\r\nGroup: {{GROUP}}\r\n\r\nHave a nice day :)\r\n".$blogname;
            $default['ig_es_welcomeemail'] = "YES";
            $default['ig_es_welcomesubject'] = $blogname . " - Welcome!";
            $default['ig_es_welcomecontent'] = "Hi {{NAME}},\r\n\r\nThank you for subscribing to " . $blogname .
                    ".\r\n\r\nWe are glad to have you onboard.\r\n\r\nBest,\r\n" . $blogname . "\r\n\r\nGot subscribed to " .
                    $blogname . " by mistake? Click <a href='{{LINK}}'>here</a> to unsubscribe.";
            $default['ig_es_optintype'] = "Double Opt In";
            $default['ig_es_confirmsubject'] = $blogname . " - Please confirm your subscription";
            $default['ig_es_confirmcontent'] = "Hi {{NAME}},\r\n\r\nWe have received a subscription request from this email address. Please confirm it by <a href='{{LINK}}'>clicking here</a>.\r\n\r\nIf you still cannot subscribe, please copy this link and paste it in your browser :\r\n{{LINK}} \r\n\r\nThank You\r\n".$blogname;
            $default['ig_es_optinlink'] = $optinlink;
            $default['ig_es_unsublink'] = $unsublink;
            $default['ig_es_unsubcontent'] = "No longer interested in emails from ".$blogname."? Please <a href='{{LINK}}'>click here</a> to unsubscribe.";
            $default['ig_es_unsubtext'] = "Thank You, You have been successfully unsubscribed. You will no longer hear from us.";
            $default['ig_es_successmsg'] = "You have been successfully subscribed.";
            $default['ig_es_suberror'] = "Oops.. Your request couldn't be completed. This email address seems to be already subscribed / blocked.";
            $default['ig_es_unsuberror'] = "Oops.. There was some technical error. Please try again later or contact us.";

            foreach ($default as $option_name => $option_value) {
                update_option($option_name, $option_value);
            }

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

	public static function es_template_default() {

        // Temp workaround - in future use option=ig_es_sample_data_imported to check against
        $result = es_cls_dbquery::es_view_subscriber_count(0);
        if ($result == 0) {

			//Adding a sample Post Notification Template
			$es_b = "Hola ###EMAIL###,\r\n\r\n";
            $es_b .= "Hem publicat un article nou al nostre lloc web. ###POSTTITLE###\r\n";
            $es_b .= "###POSTIMAGE###\r\n";
            $es_b .= "###POSTFULL###\r\n";
            $es_b .= "Podeu veure l'últim article a ";
            $es_b .= "###POSTLINK###\r\n";
            $es_b .= "Heu rebut aquest correu perquè vau demanar que se us notifiqués la publicació d'articles nous\r\n\r\n";
            $es_b .= "Gràcies i salutacions\r\n";
            $es_b .= "Admin";

			// Create Post Notification object
			$es_post = array(
			  'post_title'    => 'New Post Published - {{POSTTITLE}}',
			  'post_content'  => $es_b,
			  'post_status'   => 'publish',
			  'post_type'     => 'es_template',
			  'meta_input'    => array( 'es_template_type' => 'Post Notification'
										)
			);
			// Insert the post into the database
			$last_inserted_id = wp_insert_post( $es_post );

			// Adding a Post Notification for above created template
			$form["es_note_group"] = "Public";
			$form["es_note_status"] = "Enable";
			$form["es_note_templ"] = $last_inserted_id;

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

			// Adding a sample Newsletter Template
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
			$es_post = array(
			  'post_title'    => 'Welcome To Email Subscribers',
			  'post_content'  => $Sample,
			  'post_status'   => 'publish',
			  'post_type'     => 'es_template',
			  'meta_input'    => array( 'es_template_type' => 'Newsletter'
										)
			);
			// Insert the post into the database
			$last_inserted_id = wp_insert_post( $es_post );

			update_option( 'ig_es_sample_data_imported', 'yes' );
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
            //************ FI
            $form["es_email_status"] = "Confirmed";
            es_cls_dbquery::es_view_subscriber_ins($form, "insert");

            $form["es_email_mail"] = "a.example@example.com";
            $form["es_email_name"] = "Example";
            //XTEC ************ MODIFICAT - Changed default group from Public to Portada
            //2016.03.29 @sarjona
            $form["es_email_group"] = 'Portada';
            //************ FI
            $form["es_email_status"] = "Confirmed";
            es_cls_dbquery::es_view_subscriber_ins($form, "insert");
        }

        return true;
    }
}