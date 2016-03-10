<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_POST["ssl"])){Save();exit;}
	
	
page();	
	
function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$you_need_to_compile=$tpl->javascript_parse_text("{you_need_to_compile}");
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	if(!is_numeric($ligne["ssl_backend_port"])){$ligne["ssl_backend_port"]=443;}
	$t=time();
	$ssl_use_rcert=0;
	$to=null;
	$AS_PEER_CERTIFICATE=0;
	$AS_PEER_CERTIFICATE_EXPLAIN=null;
	$cache_peer_id=$ligne["cache_peer_id"];
	
	if($cache_peer_id>0){
		if(!$q->FIELD_EXISTS("reverse_sources", "ssl_remotecert")){
			$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ssl_remotecert` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo $q->mysql_error_html();}
		}
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ssl_remotecert FROM reverse_sources WHERE ID='$cache_peer_id'"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$to=" {to} &laquo;{$ligne2["servername"]}&raquo;";
	}
	
	
	if(intval($cache_peer_id)>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT certificate FROM reverse_sources WHERE ID='$cache_peer_id'"));
		if(trim($ligne2["certificate"]<>null)){
			$ligne["certificate"]=$ligne2["certificate"];
			$AS_PEER_CERTIFICATE=1;
		}
	}
	
	if($AS_PEER_CERTIFICATE==1){
		$AS_PEER_CERTIFICATE_EXPLAIN="<div class=explain style=font-size:22px>
		{reverse_proxy_use_destination_server_certificate}</div>";
		
	}
	
	if($ligne["ssl_protocols"]==null){
		$ligne["ssl_protocols"]="TLSv1 TLSv1.1 TLSv1.2";
	}
	
	if($ligne["ssl_ciphers"]==null){
		$ligne["ssl_ciphers"]="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";
	}
	
	
	$html[]="<div style='width:98%' class=form>$AS_PEER_CERTIFICATE_EXPLAIN";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td colspan=2 style='font-size:40px;padding-bottom:20px'>{port}:{$ligne["port"]} &laquo;$servername&raquo;$to</td></tr>";
	$html[]="<tr><td colspan=2>". Paragraphe_switch_img("{reverse_proxy_ssl}", "{NGINX_USE_SSL_EXPLAIN}",
			"ssl-$t",$ligne["ssl"],null,1400,"SwitchOffCertificate$t")."</td></tr>";
	
	$html[]=Field_list_table("certificate-$t","{certificate}",$ligne["certificate"],28,$sslcertificates,null,450);
	$html[]=Field_text_table("ssl_protocols-$t", "{ssl_protocols}",$ligne["ssl_protocols"],28,'{ssl_protocols_text}',600);
	$html[]=Field_text_table("ssl_ciphers-$t", "{ssl_ciphers}",$ligne["ssl_ciphers"],28,null,900);
	$html[]="<tr><td colspan=2><p>&nbsp;</p></td></tr>";
	
	
	
	if($cache_peer_id>0){
		
	$html[]="<tr><td colspan=2>". Paragraphe_switch_img("{destination_use_ssl}", "{NGINX_USE_SSL_EXPLAIN2}",
				"ssl_backend-$t",$ligne["ssl_backend"],null,1400)."</td></tr>";		
		
	$html[]="<tr><td colspan=2>". Paragraphe_switch_img("{SSL_CLIENT_VERIFICATION}", "{SSL_CLIENT_VERIFICATION_EXPLAIN}",
			"ssl_client_certificate-$t",$ligne["ssl_client_certificate"],null,1400)."</td></tr>";
	
	
	
	
	}
	
	//javascript:
	
	
	$html[]="
	<tr>
		<td colspan=2 align='right' style='font-size:40px'>
			". button("{apply_parameters}","Submit$t(1);",40)."&nbsp;&nbsp;|&nbsp;&nbsp;"
			. button("{apply}","Submit$t()",40)."</td>
		</tr>
			
	";
	
	
	
	$html[]="</table>";
	$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#NGINX_MAIN_TABLE').flexReload();
	alert('$you_need_to_compile');
}
var xSubmitCompile$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#NGINX_MAIN_TABLE').flexReload();
	Loadjs('nginx.single.progress.php?servername=$servername');
}




	
	
function Submit$t(Compile){
	var XHR = new XHRConnection();
	var AS_PEER_CERTIFICATE=$AS_PEER_CERTIFICATE;
	XHR.appendData('servername','$servername');
	XHR.appendData('cache_peer_id','$cache_peer_id');
	XHR.appendData('ssl',document.getElementById('ssl-$t').value);
	if(document.getElementById('ssl_backend-$t')){
		XHR.appendData('ssl_backend',document.getElementById('ssl_backend-$t').value);
	}
	if(document.getElementById('ssl_client_certificate-$t')){
		XHR.appendData('ssl_client_certificate',document.getElementById('ssl_client_certificate-$t').value);
	}	
	
	
	XHR.appendData('ssl_protocols',document.getElementById('ssl_protocols-$t').value);
	XHR.appendData('certificate',document.getElementById('certificate-$t').value);
	XHR.appendData('ssl_ciphers',document.getElementById('ssl_ciphers-$t').value);
	
	if(!Compile){
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}else{
		XHR.sendAndLoad('$page', 'POST',xSubmitCompile$t);
	}
}

function SwitchOffCertificate$t(){
	var ssl=document.getElementById('ssl-$t').value;
	
}

function Check$t(){
	var AS_PEER_CERTIFICATE=$AS_PEER_CERTIFICATE;
	if( AS_PEER_CERTIFICATE==1){
		document.getElementById('certificate-$t').disabled=true;
	}
	SwitchOffCertificate$t();
}
Check$t();
</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	}	

function Save(){
	
	$q=new mysql_squid_builder();
	
	if(isset($_POST["cache_peer_id"])){
		$cache_peer_id=$_POST["cache_peer_id"];
		unset($_POST["cache_peer_id"]);
	}

	
	
	
	$sqlz=FORM_CONSTRUCT_SQL_FROM_POST("reverse_www","servername");
	
	$q->QUERY_SQL($sqlz[1]);
	
	if(!$q->ok){echo $sq->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?compile-single=yes&servername=".urlencode($_POST["servername"]));
	
}






