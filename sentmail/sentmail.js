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
		document.frm_es_display.action="admin.php?page=es-sentmail&ac=del&did="+id;
		document.frm_es_display.submit();
	}
}

function _es_redirect()
{
	window.location = "admin.php?page=es-sentmail";
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

function _es_bulkaction()
{
	if(document.frm_es_display.action.value == "optimize-table")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
		if(confirm("Vols eliminar tots els registres, excepte els 10 últims?"))
//************ ORIGINAL
/*
			if(confirm("Do you want to delete all records except latest 10?"))
*/
//************ FI
		{
			document.frm_es_display.frm_es_bulkaction.value = 'delete';
			document.frm_es_display.action="admin.php?page=es-sentmail&bulkaction=delete";
			document.frm_es_display.submit();
		}
		else
		{
			return false;	
		}
	}
	else
	{
		return false;	
	}
}