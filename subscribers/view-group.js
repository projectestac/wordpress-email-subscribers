function _es_group_addemail()
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

function _es_group_delete(id)
{
	if(confirm("Do you want to delete this record?"))
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