function _es_addroles()
{
	if(document.form_addemail.es_email_mail.value=="")
	{
		alert("Please enter subscriber email address.")
		document.form_addemail.es_email_mail.focus();
		return false;
	}
	else if(document.form_addemail.es_email_status.value=="" || document.form_addemail.es_email_status.value=="Select")
	{
		alert("Please select subscriber email status.")
		document.form_addemail.es_email_status.focus();
		return false;
	}
	else if( (document.form_addemail.es_email_group.value == "") && (document.form_addemail.es_email_group_txt.value == "") )
	{
		alert("Please select or create group for this subscriber.")
		document.form_addemail.es_email_group.focus();
		return false;
	}
}

function _es_redirect()
{
	window.location = "admin.php?page=es-roles";
}

function _es_help()
{
	// XTEC ************ MODIFICAT - Change the url for help
// 2015.10.19 @dgras
	window.open("http://agora.xtec.cat/moodle/moodle/mod/glossary/view.php?id=1741&mode=entry&hook=2501");
//************ ORIGINAL
/*
	window.open("http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/");
*/
//************ FI
}