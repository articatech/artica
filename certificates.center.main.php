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
	
	if(isset($_GET["certificate-edit-js"])){certificate_edit_js();exit;}
	if(isset($_GET["certificate-edit-settings"])){certificate_edit_settings();exit;}
	if(isset($_POST["CommonName"])){certificate_edit_settings_save();exit;}
	if(isset($_GET["pkcs12"])){pkcs12_download();exit;}
	certificate_edit_settings();	
	
function pkcs12_download(){
	
	$commonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT pkcs12 FROM sslcertificates WHERE CommonName='$commonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	header('Content-type: application/x-pkcs12');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$commonName.p12\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	
	$fsize = strlen($ligne["pkcs12"]);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $ligne["pkcs12"];
	
	
	
	
}	
	
	
function certificate_edit_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWin6(950,'$page?certificate-edit-tabs=yes&CommonName=$CommonName&t={$_GET["t"]}&textid={$_GET["textid"]}','$title')";	
	
}

function certificate_edit_settings(){
	$commonName=$_GET["CommonName"];
	$commonNameADD=null;
	$q=new mysql();
	
	


	$db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
	$tbl=explode("\n",$db);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
			$regs[2]=trim($regs[2]);
			$regs[1]=trim($regs[1]);
			$array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
			$array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
		}
	}
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;


	if(!$q->FIELD_EXISTS("sslcertificates","UsePrivKeyCrt","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `UsePrivKeyCrt` smallint(1) DEFAULT 0";$q->QUERY_SQL($sql,'artica_backup');}
	$tpl=new templates();
	$choose_UsePrivKeyCrt=$tpl->javascript_parse_text("{choose_UsePrivKeyCrt}");
	$sql="SELECT * FROM sslcertificates WHERE CommonName='$commonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["UseGodaddy"]==1){$ligne["UsePrivKeyCrt"]=1;$commonNameADD=" (Godaddy)";}
	
	if($ligne["UsePrivKeyCrt"]==0){
		if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
		if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
		if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
		if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
		if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
		if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
		if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}
		if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=1024;}
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$sock->GET_INFO("myhostname");
	$choose_UsePrivKeyCrt=$tpl->javascript_parse_text("{choose_UsePrivKeyCrt}");
	$t=time();
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	$commonNameEnc=urlencode($commonName);
	$bt_name="{apply}";
	if(strlen($ligne["pkcs12"])>50){
		
		$cleint_certificate="<div style='float:right;width:30%;text-align:right;margin:5px'>
			<center>
			<a href=\"$page?pkcs12=yes&CommonName=".urlencode($_GET["CommonName"])."\">
				<img src='img/certificate-128.png'>
			</a><br>
			<span style='font-size:18px'>PKCS12 {client_certificate}</span> 
			</center>
			</div>";
	}
	if($ligne["UsePrivKeyCrt"]==0){	$bt_name="{generate_x509}";}
	
	
	if(strlen($ligne["CountryName"])==2){
		$ligne["CountryName"]=$array_country_codes2[$ligne["CountryName"]];
		
	}
	
	$html[]="<div style='font-size:42px;margin-bottom:15px'>$commonName$commonNameADD</div>";
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	if($ligne["UsePrivKeyCrt"]==0){
		$html[]="<tr><td colspan=2>$cleint_certificate".Paragraphe_switch_img("{UsePrivKeyCrt}", "{UsePrivKeyCrt_text}","UsePrivKeyCrt",$ligne["UsePrivKeyCrt"],null,820)."</td></tr>";
		$html[]=Field_list_table("CountryName-$t","{countryName}",$ligne["CountryName"],22,$array_country_codes);
		$html[]=Field_text_table("stateOrProvinceName","{stateOrProvinceName}",$ligne["stateOrProvinceName"],22,null,400);
		$html[]=Field_text_table("localityName","{localityName}",$ligne["localityName"],22,null,400);
		$html[]=Field_text_table("OrganizationName","{organizationName}",$ligne["OrganizationName"],22,null,400);
		$html[]=Field_text_table("OrganizationalUnit","{organizationalUnitName}",$ligne["OrganizationalUnit"],22,null,400);
		$html[]=Field_text_table("emailAddress","{emailAddress}",$ligne["emailAddress"],22,null,400);
		$html[]=Field_text_table("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"],22,null,150);
		$html[]=Field_list_table("levelenc","{level_encryption}",$ligne["levelenc"],22,$ENC);
		$html[]=Field_password_table("password-$t","{password}",$ligne["password"],22,null,300);
		$html[]=Field_button_table_autonome($bt_name,"Submit$t",30);
	}else{
		$html[]="<tr>
		<td class=legend style='font-size:22px'>{countryName} ({$ligne["CountryName"]}):</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["CountryName"]}</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{stateOrProvinceName}:</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["stateOrProvinceName"]}</td>
		</tr>		
		<tr>
		<td class=legend style='font-size:22px'>{localityName}:</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["localityName"]}</td>
		</tr>			
		<tr>
		<td class=legend style='font-size:22px'>{organizationName}:</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["OrganizationName"]}</td>
		</tr>	
		<tr>
		<td class=legend style='font-size:22px'>{organizationalUnitName}:</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["OrganizationalUnit"]}</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{emailAddress}:</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["emailAddress"]}</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{level_encryption}:</td>
		<td style='font-size:22px;font-weight:bold'>{$ligne["levelenc"]}</td>
		</tr>
		<tr>
			<td colspan=2 align='right'>". button("{generate_pfx}", 
					"Loadjs('certificate.center.pfx.progress.php?CommonName=$commonNameEnc')",30)."</td>
		</tr>
		";		
		
	}
	$html[]="</table>";
	
	$html[]="</div>
	<script>
		var xSubmit$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
		var UsePrivKeyCrt=document.getElementById('UsePrivKeyCrt').value;
		if(UsePrivKeyCrt==1){
			Loadjs('openssl.x509.progress.php?generate-x509=$commonNameEnc');
		}else{
			Loadjs('openssl.CSR.progress.php?generate-csr=$commonNameEnc');
		}
		YahooWin6Hide();
	}
	
	
	function Submit$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('CommonName',encodeURIComponent('{$_GET["CommonName"]}'));
	XHR.appendData('UsePrivKeyCrt',document.getElementById('UsePrivKeyCrt').value);
	XHR.appendData('CountryName',document.getElementById('CountryName-$t').value);
	
	XHR.appendData('CertificateMaxDays',document.getElementById('CertificateMaxDays').value);
	XHR.appendData('stateOrProvinceName',document.getElementById('stateOrProvinceName').value);
	XHR.appendData('localityName',document.getElementById('localityName').value);
	XHR.appendData('OrganizationName',document.getElementById('OrganizationName').value);
	XHR.appendData('OrganizationalUnit',document.getElementById('OrganizationalUnit').value);
	XHR.appendData('emailAddress',document.getElementById('emailAddress').value);
	XHR.appendData('levelenc',document.getElementById('levelenc').value);
	XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}


	</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}
function certificate_edit_settings_save(){
	$q=new mysql();
	$q->BuildTables();



	$CommonName=strtolower(trim(url_decode_special_tool($_POST["CommonName"])));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	while (list ($num, $vl) = each ($_POST) ){$_POST[$num]=mysql_escape_string2($vl);}


	$sql="SELECT CommonName,csr  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));

	$sql="UPDATE `sslcertificates` SET
	CountryName='{$_POST["CountryName"]}',
	stateOrProvinceName='{$_POST["stateOrProvinceName"]}',
	CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
	OrganizationName='{$_POST["OrganizationName"]}',
	OrganizationalUnit='{$_POST["OrganizationalUnit"]}',
	emailAddress='{$_POST["emailAddress"]}',
	localityName='{$_POST["localityName"]}',
	levelenc='{$_POST["levelenc"]}',
	CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
	password='{$_POST["password"]}',
	UsePrivKeyCrt='{$_POST["UsePrivKeyCrt"]}'
	WHERE CommonName='$CommonName'";


	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "MySQL Error:\n$q->mysql_error\n\n$sql\n";return;}
	$sock=new sockets();
	$CommonName=str_replace('*', "_ALL_", $CommonName);
	$CommonName=urlencode($CommonName);
	


}
