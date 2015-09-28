function _es_delete(id)
{
	if(confirm("Do you want to delete this record?"))
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
	window.open("http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/");
}

function _es_bulkaction()
{
	if(document.frm_es_display.action.value == "optimize-table")
	{
		if(confirm("Do you want to delete all records except latest 10?"))
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