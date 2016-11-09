function _es_submit() {
	if(document.es_form.es_cron_mailcount.value == "") {
		alert(es_cron_notices.es_cron_number);
		document.es_form.es_cron_mailcount.focus();
		return false;
	} else if(isNaN(document.es_form.es_cron_mailcount.value)) {
		alert(es_cron_notices.es_cron_input_type);
		document.es_form.es_cron_mailcount.focus();
		return false;
	}
}

function _es_redirect() {
	window.location = "admin.php?page=es-cron";
}

function _es_help() {
	window.open("https://wordpress.org/plugins/email-subscribers/faq/");
}