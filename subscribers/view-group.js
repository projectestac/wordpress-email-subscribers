function _es_group_addemail()
{
	if(document.form_addemail.es_email_mail.value=="")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.21 @dgras
        alert("Si us plau, introduïu l'adreça de correu del subscriptor.");
//************ ORIGINAL
/*
        alert("Please enter subscriber email address.")
*/
//************ FI
		document.form_addemail.es_email_mail.focus();
		return false;
	}
	else if(document.form_addemail.es_email_status.value=="" || document.form_addemail.es_email_status.value=="Select")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.21 @dgras
        alert("Si us plau, seleccioneu l'estat de correu del subscriptor.");
//************ ORIGINAL
/*
      alert("Please select subscriber email status.")
*/
//************ FI
		document.form_addemail.es_email_status.focus();
		return false;
	}
	else if( (document.form_addemail.es_email_group.value == "") && (document.form_addemail.es_email_group_txt.value == "") )
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.21 @dgras
        alert("Si us plau seleccioneu o creu el grup per a aquest subscriptor.");
//************ ORIGINAL
/*
      alert("Please select or create group for this subscriber.")
*/
//************ FI
		document.form_addemail.es_email_group.focus();
		return false;
	}
}

function _es_group_delete(id)
{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
	if(confirm("Voleu eliminar aquest registre?"))
//************ ORIGINAL
/*
	if(confirm("Do you want to delete this record?"))
*/
//************ FI
	{
		document.frm_es_display.action="admin.php?page=es-view-subscribers&ac=group&ac2=del&did="+id;
		document.frm_es_display.submit();
	}
}

function _es_group_redirect()
{
	window.location = "admin.php?page=es-view-subscribers&ac=group";
}

function _es_group_help()
{
	window.open("http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/");
}