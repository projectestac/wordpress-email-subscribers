function _es_submit()
{
	if(document.es_form.es_cron_mailcount.value == "")
	{
		alert("Please select enter number of mails you want to send per hour/trigger.")
		document.es_form.es_cron_mailcount.focus();
		return false;
	}
	else if(isNaN(document.es_form.es_cron_mailcount.value))
	{
		alert("Please enter the mail count, only number.")
		document.es_form.es_cron_mailcount.focus();
		return false;
	}
}

function _es_redirect()
{
	window.location = "admin.php?page=es-cron";
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