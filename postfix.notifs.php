<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	if(!isset($_REQUEST["ou"])){$_REQUEST["ou"]="master";}
	
	if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}
	if(!isset($_GET["hostname"])){$_GET["hostname"]="master";}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{$_GET["hostname"]}::{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["params"])){id_sender();exit;}
	if(isset($_GET["double_bounce_sender"])){SaveParams();exit;}
	if(isset($_GET["templates"])){templates_postfix();exit;}
	if(isset($_GET["postfix-notifs-template"])){templates_postfix_form();exit;}
	if(isset($_POST["template_save"])){templates_postfix_save();exit;}
	
	
	
tabs();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->_ENGINE_parse_body("{POSTFIX_SMTP_NOTIFICATIONS}");
	if(!isset($_GET["hostname"])){$_GET["hostname"]="master";}
	$html="YahooWin3('570','$page?tabs=yes&hostname={$_GET["hostname"]}','{$_GET["hostname"]}::$title');";
	echo $html;
	
	
}

function tabs(){
	$array["params"]="{parameters}";
	$page=CurrentPageName();
	$tpl=new templates();
	if(!isset($_GET["hostname"])){$_GET["hostname"]="master";}

	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n");
	}
	
	$main=new bounces_templates();
	while (list ($num, $ligne) = each ($main->templates_array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?postfix-notifs-template=$num&hostname={$_GET["hostname"]}\"><span style='font-size:20px'>{template}:{{$num}}</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_postfix_notifs",1490);

	
}


function id_sender(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$main=new maincf_multi($_GET["hostname"]);
	$double_bounce_sender=$main->GET("double_bounce_sender");
	$address_verify_sender=$main->GET("address_verify_sender");
	$twobounce_notice_recipient=$main->GET("2bounce_notice_recipient");
	$error_notice_recipient=$main->GET("error_notice_recipient");
	$delay_notice_recipient=$main->GET("delay_notice_recipient");
	$empty_address_recipient=$main->GET("empty_address_recipient");
	
	$sock=new sockets();
	$PostfixPostmaster=$sock->GET_INFO("PostfixPostmaster");
	if(trim($PostfixPostmaster)==null){$PostfixPostmaster="postmaster";}
	
	if($double_bounce_sender==null){$double_bounce_sender="double-bounce";};
	if($address_verify_sender==null){$address_verify_sender="\$double_bounce_sender";}
	if($twobounce_notice_recipient==null){$twobounce_notice_recipient="postmaster";}
	if($error_notice_recipient==null){$error_notice_recipient=$PostfixPostmaster;}
	if($delay_notice_recipient==null){$delay_notice_recipient=$PostfixPostmaster;}
	if($empty_address_recipient==null){$empty_address_recipient=$PostfixPostmaster;}
	
	$notify_class=unserialize(base64_decode($main->GET_BIGDATA("notify_class")));
	if(!is_array($notify_class)){
		$notify_class["notify_class_resource"]=1;
		$notify_class["notify_class_software"]=1;
	}
	
		$notify_class_software=$notify_class["notify_class_software"];
		$notify_class_resource=$notify_class["notify_class_resource"];
		$notify_class_policy=$notify_class["notify_class_policy"];
		$notify_class_delay=$notify_class["notify_class_delay"];
		$notify_class_2bounce=$notify_class["notify_class_2bounce"];
		$notify_class_bounce=$notify_class["notify_class_bounce"];
		$notify_class_protocol=$notify_class["notify_class_protocol"];
	
	
	$html="
	
	<div id='ffm1notif' style='width:99%' class=form>
	<table style='width:100%'>	
	<tr><td colspan=2 style='font-size:30px;padding-bottom:30px'>{POSTFIX_SMTP_NOTIFICATIONS_TEXT}</td></tr>
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{double_bounce_sender}","{double_bounce_sender_text}").":</td>
		<td>" . Field_text('double_bounce_sender',$double_bounce_sender,'font-size:24px;width:450px')."</td>
		
	</tr>
	<tr>
		<td class=legend nowrap nowrap style='font-size:24px'>". texttooltip("{address_verify_sender}","{address_verify_sender_text}").":</td>
		<td>" . Field_text('address_verify_sender',$address_verify_sender,'font-size:24px;width:450px')."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{2bounce_notice_recipient}","{2bounce_notice_recipient_text}").":</td>
		<td>" . Field_text('2bounce_notice_recipient',$twobounce_notice_recipient,'font-size:24px;width:450px')."</td>
		
	</tr>
	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{error_notice_recipient}","{error_notice_recipient_text}").":</td>
		<td>" . Field_text('error_notice_recipient',$error_notice_recipient,'font-size:24px;width:450px')."</td>
		
	</tr>
	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{delay_notice_recipient}","{delay_notice_recipient_text}").":</td>
		<td>" . Field_text('delay_notice_recipient',$delay_notice_recipient,'font-size:24px;width:450px')."</td>
		
	</tr>
	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{empty_address_recipient}","{empty_address_recipient_text}").":</td>
		<td>" . Field_text('empty_address_recipient',$empty_address_recipient,'font-size:24px;width:450px')."</td>
		
	</tr>
	</table>
	</div>
<div id='ffm1notif' style='width:99%' class=form>
<table style='width:100%'>	
	</tr>
				<tr><td colspan=2 style='font-size:30px;padding-bottom:30px'>{notify_class}</td></tr>
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_bounce}","{notify_class_bounce_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_bounce',1,$notify_class_bounce)."</td>
		
	</tr>	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_2bounce}","{notify_class_2bounce_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_2bounce',1,$notify_class_2bounce)."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_delay}","{notify_class_delay_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_delay',1,$notify_class_delay)."</td>
		
	</tr>	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_policy}","{notify_class_policy_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_policy',1,$notify_class_policy)."</td>
		
	</tr>	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_protocol}","{notify_class_protocol_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_protocol',1,$notify_class_protocol)."</td>
		
	</tr>	
	
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_resource}","{notify_class_resource_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_resource',1,$notify_class_resource)."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:24px' nowrap>". texttooltip("{notify_class_software}","{notify_class_software_text}").":</td>
		<td>" . Field_checkbox_design('notify_class_software',1,$notify_class_software)."</td>
		
	</tr>
	

	
	<tr>
		<td colspan=3 align='right'>
		<hr>
		". button("{apply}","SavePostfixNotificationsForm()",44)."
		</td>
	</tr>		
</table>	
</div>
	<script>
	
	var x_SavePostfixNotificationsForm= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);}
		RefreshTab('main_config_postfix_notifs');
		Loadjs('postfix.notifs.progress.php');
	}	
	
	function SavePostfixNotificationsForm(){
		var XHR = new XHRConnection();
		XHR.appendData('double_bounce_sender',document.getElementById('double_bounce_sender').value);
		XHR.appendData('address_verify_sender',document.getElementById('address_verify_sender').value);
		XHR.appendData('2bounce_notice_recipient',document.getElementById('2bounce_notice_recipient').value);
		XHR.appendData('error_notice_recipient',document.getElementById('error_notice_recipient').value);
		XHR.appendData('delay_notice_recipient',document.getElementById('delay_notice_recipient').value);
		XHR.appendData('empty_address_recipient',document.getElementById('empty_address_recipient').value);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		if(document.getElementById('notify_class_software').checked){XHR.appendData('notify_class_software','1');}else{XHR.appendData('notify_class_software','0');}
		if(document.getElementById('notify_class_resource').checked){XHR.appendData('notify_class_resource','1');}else{XHR.appendData('notify_class_resource','0');}
		if(document.getElementById('notify_class_policy').checked){XHR.appendData('notify_class_policy','1');}else{XHR.appendData('notify_class_policy','0');}
		if(document.getElementById('notify_class_delay').checked){XHR.appendData('notify_class_delay','1');}else{XHR.appendData('notify_class_delay','0');}
		if(document.getElementById('notify_class_2bounce').checked){XHR.appendData('notify_class_2bounce','1');}else{XHR.appendData('notify_class_2bounce','0');}
		if(document.getElementById('notify_class_bounce').checked){XHR.appendData('notify_class_bounce','1');}else{XHR.appendData('notify_class_bounce','0');}
		if(document.getElementById('notify_class_protocol').checked){XHR.appendData('notify_class_protocol','1');}else{XHR.appendData('notify_class_protocol','0');}
	
		XHR.sendAndLoad('$page', 'GET',x_SavePostfixNotificationsForm);
	}
		
	
	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function templates_postfix(){
$main=new bounces_templates();
$page=CurrentPageName();
	while (list ($num, $ligne) = each ($main->templates_array) ){
		$tmpl=$tmpl. Paragraphe('64-templates.png',$num,"{{$num}}","javascript:ShowTemplateFrom('$num')",null,210,null,0,true);
			
		}
$html=$html."
<center>
	<div id='id_templates'>$tmpl</div>
</center>
<script>
	function ShowTemplateFrom(template){
		YahooWin4(650,'$page?postfix-notifs-template='+template+'&hostname={$_GET["hostname"]}',template); 
	
	}
</script>

";		
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function SaveParams(){
	$main=new maincf_multi($_GET["hostname"]);
	$main->SET_VALUE("double_bounce_sender",$_GET["double_bounce_sender"]);
	$main->SET_VALUE("address_verify_sender",$_GET["address_verify_sender"]);
	$main->SET_VALUE("2bounce_notice_recipient",$_GET["2bounce_notice_recipient"]);
	$main->SET_VALUE("error_notice_recipient",$_GET["error_notice_recipient"]);
	$main->SET_VALUE("delay_notice_recipient",$_GET["delay_notice_recipient"]);
	$main->SET_VALUE("empty_address_recipient",$_GET["empty_address_recipient"]);
		$notif["notify_class_software"]=$_GET["notify_class_software"];
		$notif["notify_class_resource"]=$_GET["notify_class_resource"];
		$notif["notify_class_policy"]=$_GET["notify_class_policy"];
		$notif["notify_class_delay"]=$_GET["notify_class_delay"];
		$notif["notify_class_2bounce"]=$_GET["notify_class_2bounce"];
		$notif["notify_class_bounce"]=$_GET["notify_class_bounce"];
		$notif["notify_class_protocol"]=$_GET["notify_class_protocol"];
		$main->SET_BIGDATA("notify_class",base64_encode(serialize($notif)));
		
}

function templates_postfix_form(){
	$template=$_GET["postfix-notifs-template"];
	$tpl=new templates();
	$page=CurrentPageName();
	$mainTPL=new bounces_templates();
	$main=new maincf_multi($_GET["hostname"]);
	$t=time();
	
	$array=unserialize(base64_decode($main->GET_BIGDATA($template)));
	if(!is_array($array)){
		$array=$mainTPL->templates_array[$template];
	}
	$html="
		<div id='ffm1notif2'>
		<div style='font-size:30px;margin-bottom:30px'>{{$template}}</div>
		<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:22px;'>Charset:</td>
			<td>" . Field_text("Charset-$t",$array["Charset"],'width:450px;font-size:22px;padding:3px')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>{mail_from}:</td>
			<td>" . Field_text("From-$t",$array["From"],'width:450px;font-size:22px;padding:3px')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>{subject}:</td>
			<td>" . Field_text("Subject-$t",$array["Subject"],'width:450px;font-size:22px;padding:3px')."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px;'>Postmaster-Subject:</td>
			<td>" . Field_text("Postmaster-Subject-$t",$array["Postmaster-Subject"],'width:450px;font-size:22px;padding:3px')."</td>
		</tr>	
		<tr>
			<td valign='top' colspan=2 align='right'>
			". button("{apply}","SavePostfixNotifTemplateForm$t()",40)."
			</td>
			
		</tr>	
		<tr>
			<td valign='top' colspan=2>
				<textarea id='template-Body-$t' style=';font-size:22px !important;padding:3px;width:100%;border:1px dotted #CCCCCC;height:400px;font-family:Courier New;margin:4px;padding:4px'>{$array["Body"]}</textarea></td>
		</tr>
			
		</table>

	<script>
	
	var x_SavePostfixNotifTemplateForm$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);}
		RefreshTab('main_config_postfix_notifs');
		Loadjs('postfix.notifs.progress.php');
	}	
	
	function SavePostfixNotifTemplateForm$t(){
		var XHR = new XHRConnection();
		XHR.appendData('Charset',encodeURIComponent(document.getElementById('Charset-$t').value));
		XHR.appendData('From',encodeURIComponent(document.getElementById('From-$t').value));
		XHR.appendData('Subject',encodeURIComponent(document.getElementById('Subject-$t').value));
		XHR.appendData('Postmaster-Subject',encodeURIComponent(document.getElementById('Postmaster-Subject-$t').value));
		XHR.appendData('Body',encodeURIComponent(document.getElementById('template-Body-$t').value));
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('template_save','$template');		
		XHR.sendAndLoad('$page', 'POST',x_SavePostfixNotifTemplateForm$t);
	}
		
	
	</script>		
		";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function templates_postfix_save(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=url_decode_special_tool($ligne);
	}
	
	$template=$_POST["template_save"];
	$main=new maincf_multi($_POST["hostname"]);
	$main->SET_BIGDATA($template,base64_encode(serialize($_POST)));

	
}


function Isright(){
	$users=new usersMenus();
	if($users->AsArticaAdministrator){return true;}
	if($users->AsPostfixAdministrator){return true;}
	if(!$users->AsOrgStorageAdministrator){return false;}
	if(isset($_REQUEST["ou"])){if($_SESSION["ou"]<>$_GET["ou"]){return false;}}
	return true;
	
	}
