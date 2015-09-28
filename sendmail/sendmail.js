function _es_redirect()
{
	window.location = "admin.php?page=es-sendemail";
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

function _es_checkall(FormName, FieldName, CheckValue)
{
	if(!document.forms[FormName])
		return;
	var objCheckBoxes = document.forms[FormName].elements[FieldName];
	if(!objCheckBoxes)
		return;
	var countCheckBoxes = objCheckBoxes.length;
	if(!countCheckBoxes)
		objCheckBoxes.checked = CheckValue;
	else
		// set the check value for all check boxes
		for(var i = 0; i < countCheckBoxes; i++)
			objCheckBoxes[i].checked = CheckValue;
}

function _es_mailgroup(es_email_group)
{
	document.getElementById("es_templ_heading").value = document.es_form.es_templ_heading.value;
	document.getElementById("es_email_group").value = es_email_group;
	document.getElementById("es_sent_type").value = document.es_form.es_sent_type.value;
	document.getElementById("sendmailsubmit").value = "no";
	document.getElementById("wp_create_nonce").value = document.es_form.wp_create_nonce.value;
	document.es_form.action="admin.php?page=es-sendemail";
	document.es_form.submit();
}

function _es_sendemailsearch(es_search_query)
{
	
	document.getElementById("es_templ_heading").value = document.es_form.es_templ_heading.value;
	document.getElementById("es_email_group").value = document.es_form.es_email_group.value;	
	document.getElementById("es_sent_type").value = document.es_form.es_sent_type.value;
	document.getElementById("es_search_query").value = es_search_query;
	document.getElementById("sendmailsubmit").value = "no";
	document.getElementById("wp_create_nonce").value = document.es_form.wp_create_nonce.value;
	document.es_form.action="admin.php?page=es-sendemail";
	document.es_form.submit();
}

function _es_submit()
{
	if(document.es_form.es_templ_heading.value=="")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
		alert("Si us plau, seleccioneu el tema del vostre correu.")
//************ ORIGINAL
/*
		alert("Please select your mail subject.")
*/
//************ FI
		document.es_form.es_templ_heading.focus();
		return false;
	}
	if(document.es_form.es_sent_type.value=="")
	{
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
		alert("Si us plau, seleccioneu el tipus de correu.")
//************ ORIGINAL
/*
		alert("Please select your mail type.")
*/
//************ FI
		document.es_form.es_sent_type.focus();
		return false;
	}
// XTEC ************ MODIFICAT - Localization support
// 2015.10.06 @dgras
	if(confirm("Esteu segurs que voleu enviar el correu a totes les adreces de correu seleccionades?"))
//************ ORIGINAL
/*
	if(confirm("Are you sure you want to send email to all selected email address?"))
*/
//************ FI
	{
		document.getElementById("es_templ_heading").value = document.es_form.es_templ_heading.value;
		document.getElementById("es_email_group").value = document.es_form.es_email_group.value;
		document.getElementById("es_search_query").value = document.es_form.es_search_query.value;
		document.getElementById("es_sent_type").value = document.es_form.es_sent_type.value;
		document.getElementById("wp_create_nonce").value = document.es_form.wp_create_nonce.value;
		document.getElementById("sendmailsubmit").value = "yes";
		document.es_form.submit();
	}
	else
	{
		return false;
	}
}