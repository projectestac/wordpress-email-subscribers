function _es_submit()
{
	if(document.es_form.es_set_name.value=="")
	{
		alert("Please enter name for configuration.")
		document.es_form.es_set_name.focus();
		return false;
	}
	else if(document.es_form.es_set_templid.value=="")
	{
		alert("Please select template for this configuration.")
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
		document.frm_es_display.action="admin.php?page=es-compose&ac=del&did="+id;
		document.frm_es_display.submit();
	}
}

function _es_redirect()
{
	window.location = "admin.php?page=es-compose";
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