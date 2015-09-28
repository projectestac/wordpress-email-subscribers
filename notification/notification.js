function _es_submit()
{
	if(document.es_form.es_note_group.value == "")
	{
		alert("Please select subscribers group.")
		document.es_form.es_note_group.focus();
		return false;
	}
	else if(document.es_form.es_note_templ.value == "")
	{
		alert("Please select notification mail subject. Use compose menu to create new.")
		document.es_form.es_note_templ.focus();
		return false;
	}
	else if(document.es_form.es_note_status.value == "")
	{
		alert("Please select notification status.")
		document.es_form.es_note_status.focus();
		return false;
	}
}

function _es_delete(id)
{
	if(confirm("Do you want to delete this record?"))
	{
		document.frm_es_display.action="admin.php?page=es-notification&ac=del&did="+id;
		document.frm_es_display.submit();
	}
}

function _es_redirect()
{
	window.location = "admin.php?page=es-notification";
}

function _es_help()
{
	window.open("http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/");
}