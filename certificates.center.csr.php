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
	if(isset($_GET["certificate-edit-csr"])){certificate_edit_csr();exit;}
	if(isset($_GET["verify-csr"])){certificate_edit_csr_verify();exit;}
	if(isset($_POST["SaveCSR"])){SaveCSR();exit;}
	
	certificate_edit_csr();
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{CSR}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinT(1025,'$page?CommonName=$CommonName&t={$_GET["t"]}','$title')";
}
	
function certificate_edit_csr(){
	$commonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT `csr`,`UsePrivKeyCrt`,`UseGodaddy` FROM sslcertificates WHERE CommonName='$commonName'";
	$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	$tt=time();
	
	$NOT_BUILD=false;
	if($ligne["UsePrivKeyCrt"]==1){$NOT_BUILD=true;}
	if($ligne["UseGodaddy"]==1){$NOT_BUILD=true;}
	
	if(!$NOT_BUILD){
		if(strlen($ligne["csr"])<50){
			$sock=new sockets();
			$CommonName=urlencode($CommonName);
			echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$commonName"));
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		}
	}
	
	$CommonNameURL=urlencode("$commonName");
	$button_upload=button("$upload_text", "Loadjs('certificates.center.upload.php?certificate-upload-js=yes&CommonName=$CommonNameURL&type=csr&t={$_GET["t"]}&textid=text$t&RunAfter=VerifyCertificate$tt',true)");
	$button_save=$tpl->_ENGINE_parse_body(button("{apply}","SaveCRT$tt()",40));
	if($ligne["UsePrivKeyCrt"]==0){$button_upload=null;}
	
	$csr_ssl_explain=$tpl->_ENGINE_parse_body("{csr_ssl_explain}");
	$html="
<div class=explain style='font-size:18px'>$csr_ssl_explain</div>
		<div id='verify-$tt'></div>
		<center>$button_upload</center>
		<center style='margin:10px'>
		<textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px'>{$ligne["csr"]}</textarea>
		</center>
		<center style='margin:10px'>$button_save</center>
	</div>
<script>
function VerifyCertificate$tt(){
	LoadAjax('verify-$tt','$page?verify-csr=yes&CommonName=$CommonNameURL',true);
}
var x_SaveCRT$tt=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	VerifyCertificate$tt();
}
function SaveCRT$tt(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('text$t').value);
	XHR.appendData('SaveCSR',pp);
	XHR.appendData('CommonName','$commonName');
	XHR.sendAndLoad('$page', 'POST',x_SaveCRT$tt);
}



VerifyCertificate$tt();
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	}
	
function SaveCSR(){
	$CommonName=$_POST["CommonName"];
	$csr=url_decode_special_tool($_POST["SaveCSR"]);
	$dataSQL=mysql_escape_string2($csr);
	$q=new mysql();
	$sql="UPDATE sslcertificates SET `csr`='$dataSQL' WHERE `CommonName`='$CommonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}	
	
	
function certificate_edit_csr_verify(){
	
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT `csr` ,`UsePrivKeyCrt`,`UseGodaddy` FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$t=$_GET["t"];
	
	$NOT_BUILD=false;
	if($ligne["UsePrivKeyCrt"]==1){$NOT_BUILD=true;}
	if($ligne["UseGodaddy"]==1){$NOT_BUILD=true;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);return;}
	$tt=time();
	
	if(!$NOT_BUILD){
		if(strlen($ligne["csr"])<50){
			$sock=new sockets();
			$CommonName=urlencode($CommonName);
			echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$CommonName"));
			}
		}
	
	
	
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.csr";
	@file_put_contents($filepath, $ligne["csr"]);
	exec("/usr/bin/openssl req -text -noout -verify -verbose -in $filepath 2>&1",$results);
	$INFO=array();
	$class="text-info";
	$f[]="$CommonName: $filepath ".strlen($ligne["csr"])." bytes";
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#[0-9]+:error:[0-9A-Z]+:PEM routines:#",$ligne)){$class="text-error";}
		if(preg_match("#unable to load#",$ligne)){$class="text-error";}
		if(preg_match("#Subject:(.*)#",$ligne)){$INFO[]=$ligne;}
		if(preg_match("#verify OK#i",$ligne)){$INFO[]=$ligne;}
		$ligne=str_replace($filepath, "Info", $ligne);
		$ligne=htmlentities($ligne);
		$f[]="$ligne";
	}
	if($class=="text-error"){
		echo "<p class='$class' style='font-size:18px'>".@implode("<br>", $f)."</p><script>UnlockPage();</script>";
	}else{
		echo "<p class='$class' style='font-size:18px'>".@implode("<br>", $INFO)."</p><script>UnlockPage();</script>";
	}
}