function es_submit_page(url)
{
	es_email = document.getElementById("es_txt_email");
	es_name = document.getElementById("es_txt_name");
	es_group = document.getElementById("es_txt_group");
    if( es_email.value == "" )
    {
        alert("Please enter email address.");
        es_email.focus();
        return false;    
    }
	if( es_email.value!="" && ( es_email.value.indexOf("@",0) == -1 || es_email.value.indexOf(".",0) == -1 ))
    {
        alert("Please provide a valid email address.")
        es_email.focus();
        es_email.select();
        return false;
    }
	document.getElementById("es_msg").innerHTML = "loading...";
	var date_now = "";
    var mynumber = Math.random();
	var str= "es_email="+ encodeURI(es_email.value) + "&es_name=" + encodeURI(es_name.value) + "&es_group=" + encodeURI(es_group.value) + "&timestamp=" + encodeURI(date_now) + "&action=" + encodeURI(mynumber);
	es_submit_request(url+'/?es=subscribe', str);
	//alert(url+'/?es=subscribe' + str);
}

var http_req = false;
function es_submit_request(url, parameters) 
{
	http_req = false;
	if (window.XMLHttpRequest) 
	{
		http_req = new XMLHttpRequest();
		if (http_req.overrideMimeType) 
		{
			http_req.overrideMimeType('text/html');
		}
	} 
	else if (window.ActiveXObject) 
	{
		try 
		{
			http_req = new ActiveXObject("Msxml2.XMLHTTP");
		} 
		catch (e) 
		{
			try 
			{
				http_req = new ActiveXObject("Microsoft.XMLHTTP");
			} 
			catch (e) 
			{
				
			}
		}
	}
	if (!http_req) 
	{
		alert('Cannot create XMLHTTP instance');
		return false;
	}
	http_req.onreadystatechange = eemail_submitresult;
	http_req.open('POST', url, true);
	http_req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_req.setRequestHeader("Content-length", parameters.length);
	http_req.setRequestHeader("Connection", "close");
	http_req.send(parameters);
}

function eemail_submitresult() 
{
	//alert(http_req.readyState);
	//alert(http_req.responseText); 
	if (http_req.readyState == 4) 
	{
		if (http_req.status == 200) 
		{
		 	if (http_req.readyState==4 || http_req.readyState=="complete")
			{ 
				if((http_req.responseText).trim() == "subscribed-successfully")
				{
					document.getElementById("es_msg").innerHTML = "Subscribed successfully.";
					document.getElementById("es_txt_email").value="";
				}
				else if((http_req.responseText).trim() == "subscribed-pending-doubleoptin")
				{
					alert('You have successfully subscribed to the newsletter. You will receive a confirmation email in few minutes. Please follow the link in it to confirm your subscription. If the email takes more than 15 minutes to appear in your mailbox, please check your spam folder.');
					document.getElementById("es_msg").innerHTML = "Subscribed successfully.";
					document.getElementById("es_txt_email").value="";
					document.getElementById("es_txt_name").value="";
				}
				else if((http_req.responseText).trim() == "already-exist")
				{
					document.getElementById("es_msg").innerHTML = "Email already exist.";
				}
				else if((http_req.responseText).trim() == "unexpected-error")
				{
					document.getElementById("es_msg").innerHTML = "Oops.. Unexpected error occurred.";
				}
				else if((http_req.responseText).trim() == "invalid-email")
				{
					document.getElementById("es_msg").innerHTML = "Invalid email address.";
				}
				else
				{
					document.getElementById("es_msg").innerHTML = "Please try after some time.";
					document.getElementById("es_txt_email").value="";
					document.getElementById("es_txt_name").value="";
				}
			} 
		}
		else 
		{
			alert('There was a problem with the request.');
		}
	}
}