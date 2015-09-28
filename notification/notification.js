function _es_submit()
{
	if(document.es_form.es_note_group.value == "")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
		alert("Si us plau, seleccioneu el grup de subscriptors.")
//************ ORIGINAL
/*
		alert("Please select subscribers group.")
*/
//************ FI
		document.es_form.es_note_group.focus();
		return false;
	}
	else if(document.es_form.es_note_templ.value == "")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
				alert("Seleccioneu la notificació del tema del correu. Utilitzeu el menú per crear-ne de nous.");
//************ ORIGINAL
/*
		alert("Please select notification mail subject. Use compose menu to create new.")
*/
//************ FI
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
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
	if(confirm("Voleu eliminar aquest registre?"))
//************ ORIGINAL
/*
	if(confirm("Do you want to delete this record?"))
*/
//************ FI
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
// XTEC ************ MODIFICAT - Change the url for help
// 2015.10.19 @dgras
	window.open("http://agora.xtec.cat/moodle/moodle/mod/glossary/view.php?id=1741&mode=entry&hook=2501");
//************ ORIGINAL
/*
	window.open("http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/");
*/
//************ FI
}