<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.spamassassin.inc');
	

	if(isset($_POST["SpamAssassinTemplate"])){Save();exit;}
	
	page();
	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SpamAssassinTemplate=trim($sock->GET_INFO("SpamAssassinTemplate"));
	if($SpamAssassinTemplate==null){
		$SpamAssassinTemplate="\nSpam detection software, running on the system (_HOSTNAME_), has\nidentified this incoming email as possible spam.  The original message\nhas been attached to this so you can view it (if it isn't spam) or label\nsimilar future email.  If you have any questions, see\n_CONTACTADDRESS_ for details.\n\nContent preview:  _PREVIEW_\n\nContent analysis details:   (_SCORE_ points, _REQD_ required)\n\npts rule name              description\n ---- ---------------------- -----------------------------------------\n_SUMMARY_";
	}
	$tt=time();
	$button_save=$tpl->_ENGINE_parse_body(button("{apply}", "Save$tt()",40));

	$html="
<div style='width:98%' class=form>
<div style='font-size:30px;margin-bottom:30px'>{smtp_notification}: {template}/{body_message}</div>
<center style='margin:10px'>
		<textarea id='text$tt' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px'>$SpamAssassinTemplate</textarea>
		<br>$button_save
	</center>
</div>
	
<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	RefreshTab('main_config_milter_spamass');
}
	
function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('SpamAssassinTemplate',encodeURIComponent(document.getElementById('text$tt').value));
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function Save(){
	$sock=new sockets();
	$content=url_decode_special_tool($_POST["SpamAssassinTemplate"]);
	$sock->SaveConfigFile($content, "SpamAssassinTemplate");
	$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
}
