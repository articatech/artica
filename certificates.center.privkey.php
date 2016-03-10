<?php
    //ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	if(!isset($_GET["t"])){$_GET["t"]=time();}
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["verify-privkey"])){certificate_edit_privkey_verify();exit;}
	if(isset($_POST["certificate_edit_privkey_save"])){certificate_edit_privkey_save();exit;}
	if(isset($_GET["certificate-info-privkey-js"])){certificate_info_privkey_js();exit;}
	if(isset($_GET["certificate-info-privkey-popup"])){certificate_info_privkey_popup();exit;}
	
page();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinT(1025,'$page?CommonName=$CommonName&t={$_GET["t"]}','$title')";
}

function certificate_info_privkey_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{privkey}:{info}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinBrowse(650,'$page?certificate-info-privkey-popup=yes&CommonName=$CommonName&type={$_GET["type"]}&t={$_GET["t"]}','$title')";
}


function page(){
	$commonName=$_GET["CommonName"];
		$page=CurrentPageName();
		$q=new mysql();
		$tpl=new templates();
		
		
		
		
		$sql="SELECT `privkey`,`Squidkey`,`UsePrivKeyCrt` FROM sslcertificates WHERE CommonName='$commonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		$keyfield="privkey";
		
		if($ligne["UsePrivKeyCrt"]==0){
			$keyfield="Squidkey";
		}
		
		
		
		
		$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
		$t=$_GET["t"];
		if(!is_numeric($t)){$t=time();}
		
		if(!$q->ok){senderror($q->mysql_error);}
		$tt=time();
	
		$CommonNameURL=urlencode("$commonName");
		$button_upload=button("$upload_text", "Loadjs('certificates.center.upload.php?certificate-upload-js=yes&CommonName=$CommonNameURL&type=privkey&t={$_GET["t"]}&textid=text$t&RunAfter=VerifyCertificate$tt',true)",20);
		$button_extract=$tpl->_ENGINE_parse_body(button("{info}", "Loadjs('$page?certificate-info-privkey-js=yes&CommonName=$CommonNameURL&type=crt&t={$_GET["t"]}&textid=crt$tt',true)",20));
		$button_save=$tpl->_ENGINE_parse_body(button("{apply}", "Save$tt()",20));
		
		if($ligne["UsePrivKeyCrt"]==0){
			$button_upload=null;
			$button_save=null;
		}
	
		$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");
		$html="
	
<div class=explain style='font-size:18px'>$ssl_explain</div>
<center>$button_upload&nbsp;$button_extract</center>
<div id='verify-$tt'></div>
	<center style='margin:10px'>
		<textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px'>{$ligne[$keyfield]}</textarea>
		<br>$button_save
		</center>
		</div>
	
<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
	VerifyCertificate$tt();
}
	
function Save$tt(CommonName,md5){
	var XHR = new XHRConnection();
	XHR.appendData('certificate_edit_privkey_save','$commonName');
	XHR.appendData('certificate_content',encodeURIComponent(document.getElementById('text$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
	
function VerifyCertificate$tt(){
	LoadAjax('verify-$tt','$page?verify-privkey=yes&CommonName=$CommonNameURL',true);
}
VerifyCertificate$tt();
</script>
	";
	echo $html;
	}	


function certificate_edit_privkey_save(){
	$commonName=$_POST["certificate_edit_privkey_save"];
	$content=url_decode_special_tool($_POST["certificate_content"]);
	$sql="UPDATE sslcertificates SET `privkey`='".mysql_escape_string2($content)."' WHERE CommonName='$commonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}
	

function certificate_info_privkey_popup(){
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT `privkey`,`Squidkey`,`UsePrivKeyCrt`  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$field="privkey";
	if($ligne["UsePrivKeyCrt"]==0){$field="Squidkey";}
	
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.key";
	@file_put_contents($filepath, $ligne["privkey"]);
	exec("/usr/bin/openssl rsa -noout -text -in  $filepath 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#unable to load#", $ligne)){
			$addon="\n--------------\n{$ligne["privkey"]}\n--------------\n";
		}
	
		$ligne=trim($ligne);
		$tt[]=$ligne;
	}
	
		echo "<textarea
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:12px !important;width:99%;height:390px' id='crt$tt'>".@implode("\n", $tt)."$addon</textarea>";
	
	
	
	}
	
	function certificate_edit_privkey_verify(){
		$CommonName=$_GET["CommonName"];
		$q=new mysql();
		$sql="SELECT `crt`,`UsePrivKeyCrt`,`privkey`,`SquidCert`,`Squidkey`  FROM sslcertificates WHERE CommonName='$CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		$certField="crt";
		$keyfield="privkey";
		
		if($ligne["UsePrivKeyCrt"]==0){
			$certField="SquidCert";
			$keyfield="Squidkey";
		}
		
		
		if($ligne[$certField]==null){
			$tpl=new templates();
			echo "<p class='text-error' style='font-size:14px'>". $tpl->_ENGINE_parse_body("{failed}...{no_certificate}")."</p>";
			return;
		}
		
		
		$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
		@file_put_contents($filepath, $ligne[$certField]);
		if(!is_file($filepath)){
			echo "<p class='text-error' style='font-size:14px'>$filepath permission denied</p>";
			
		}
		
		
		$md5=trim(exec("/usr/bin/openssl x509 -noout -modulus -in $filepath | /usr/bin/openssl md5 2>&1"));
	
		
		$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.key";
		@file_put_contents($filepath, $ligne[$keyfield]);
		$md52=trim(exec("/usr/bin/openssl rsa -noout -modulus -in $filepath | /usr/bin/openssl md5 2>&1"));
	
		if($md5<>$md52){
			echo "<p class='text-error' style='font-size:22px'>ID: $CommonName<br>Private Key failed &laquo;$md5&raquo; / &laquo;$md52&raquo;</p><script>UnlockPage();</script>";
		}else{
			echo "<p class='text-info' style='font-size:22px'>ID: $CommonName<br>Private Key Success</p><script>UnlockPage();</script>";
		}
	
	}