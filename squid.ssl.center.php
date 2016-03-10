<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
    //ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.mysql.inc');
	if(!isset($_GET["t"])){$_GET["t"]=time();}
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	
	$user=new usersMenus();
	if(($user->AsSquidAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "<script>alert('$text');</script>";
		exit;
	}
	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["sslproxy_version"])){Save();exit;}
	if(isset($_GET["SSL_CERTIF_DOWN"])){SSL_CERTIF_DOWN();exit;}
tabs();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{UseSSL}");
	echo "YahooWin2('950','$page?popup=yes','$title');";
	
	
}

function tabs(){

	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$squid=new squidbee();
	
	$fontsize=20;
	
	$sock=new sockets();
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	
	if($SquidSSLUrgency==1){
		echo FATAL_ERROR_SHOW_128(
			"<div style='font-size:22px'>{proxy_in_ssl_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_ssl_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?ssl=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
		
	
	}
	
	if($SquidUrgency==1){
		echo FATAL_ERROR_SHOW_128(
				"<div style='font-size:22px'>{proxy_in_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?justbutton=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
		return;
	
	}
	
	
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	
	if($DisableSSLStandardPort==0){$StandardPortSSL=1;}else{$StandardPortSSL=0;}
	
	if(!isset($COMPILATION_PARAMS["enable-ssl"])){
		echo FATAL_ERROR_SHOW_128("{error_squid_ssl_not_compiled}");
		return;
	
	}
	
	$array["popup"]="{status}";
	if($StandardPortSSL==1){
		if($squid->SSL_BUMP_WHITE_LIST==1){
			$array["ssl-decrypt"]="{decrypted_ssl_websites}";
		}else{
			$array["ssl-whitelist"]="{whitelist}";
		}
	}
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="ssl-decrypt"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.ssl.encrypt.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="ssl-whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.sslbump.php?whitelist=yes\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	
	}
	
	
	
	$html=build_artica_tabs($html,'main_ssl_center_tabs',975)."<script>LeftDesign('ssl-256-white-opac20.png');</script>";
	
	echo $html;



}

function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$squid=new squidbee();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	
	$sock=new sockets();
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	
	if($SquidSSLUrgency==1){
		echo FATAL_ERROR_SHOW_128(
				"<div style='font-size:22px'>{proxy_in_ssl_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_ssl_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?ssl=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
	
	
	}
	
	if($SquidUrgency==1){
		echo FATAL_ERROR_SHOW_128(
				"<div style='font-size:22px'>{proxy_in_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?justbutton=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
		return;
	
	}
	
	$t=time();
	
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	if($DisableSSLStandardPort==0){$StandardPortSSL=1;}else{$StandardPortSSL=0;}
	
	
	$sslproxy_versions[1]="{default}";
	$sslproxy_versions[2]="SSLv2 {only}";
	$sslproxy_versions[3]="SSLv3 {only}";
	$sslproxy_versions[4]="TLSv1.0 {only}";
	$sslproxy_versions[5]="TLSv1.1 {only}";
	$sslproxy_versions[6]="TLSv1.2 {only}";
	$sslproxy_version=intval($sock->GET_INFO("sslproxy_version"));
	if($sslproxy_version==0){$sslproxy_version=1;}
	
	//$SSL_BUMP_WHITE_LIST=Paragraphe_switch_img("{whitelist_all_domains}",
	//"{sslbump_whitelist_all_domains_explain}<br>{SSL_BUMP_CONNECTED_EXPLAIN}","SSL_BUMP_WHITE_LIST-$t",$squid->SSL_BUMP_WHITE_LIST,null,650);
	
	
	
	$html="
			
	<div style='width:98%' class=form>		
	<div id='SSL_CERTIF_DOWN'></div>
	<table style='width:100%'>
	<tr>
		<td style='font-size:22px;vertical-align:middle' class=legend nowrap>{sslproxy_version}:</td>
		<td>". Field_array_Hash($sslproxy_versions,"sslproxy_version-$t",$sslproxy_version,"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td colspan=2 align=right style='font-size:22px'><hr>".button("{rebuild_ssl_cache}","Loadjs('squid.sslcrtd.flush.progress.php')",36)."&nbsp;|&nbsp;".button("{apply}","Save$t()",36)."</td>
	</tr>
	</table>
	</div>	
<script>
var xSave$t=function(obj){
    var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
	Loadjs('squid.compile.progress.php?restart=yes&ask=yes');

	
}	

function Save$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('sslproxy_version',document.getElementById('sslproxy_version-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}


</script>						
	";
	
	
echo $tpl->_ENGINE_parse_body($html);	
	

	
}


function Save(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$sock->SET_INFO("sslproxy_version", $_POST["sslproxy_version"]);

	
}


function SSL_CERTIF_DOWN(){
	$tpl=new templates();
	$sock=new sockets();
	$EnableSquidSSLCRTD=$sock->GET_INFO("EnableSquidSSLCRTD");
	if(!is_numeric($EnableSquidSSLCRTD)){$EnableSquidSSLCRTD=1;}
	
	
	$html[]="<center style='margin:10px'>
				". button("{generate_certificate}","Loadjs('squid.sslbump.php?gen-certif-js=yes')",18)."
			</center>";
	
	if($EnableSquidSSLCRTD==1){
		if(is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
			$html[]="
			<center>
			<center style='width:650px;border:1px solid #005447;border-radius: 4px 4px 4px 4px;margin-top:15px;margin-bottom:15px'>
			<table style='width:100%'>
		
			<tr>
				<td style='vertical-align:top;width:128px'>
					<img src='img/certificate-128.png' align='left'></td>
				</td>
				<td style='vertical-align:top;padding-top:15px;padding-left:15px'><a href='ressources/squid/certificate.der'
					style='font-size:16px;color:#A91919;font-weight:bold;text-decoration:underline'>
		
			{certificate_to_deploy_explain}
			</a>
			</td>
			</tr>
			</table>
			</center></center>
					";
		}
	}
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}





	
