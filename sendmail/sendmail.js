function _es_redirect()
{
	window.location = "admin.php?page=es-sendemail";
}

function _es_help()
{
	window.open("http://www.gopiplus.com/work/2014/05/02/email-subscribers-wordpress-plugin/");
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
		alert("Please select your mail subject.")
		document.es_form.es_templ_heading.focus();
		return false;
	}
	if(document.es_form.es_sent_type.value=="")
	{
		alert("Please select your mail type.")
		document.es_form.es_sent_type.focus();
		return false;
	}
	
	if(confirm("Are you sure you want to send email to all selected email address?"))
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