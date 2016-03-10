<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.certificate-center.inc');
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
	
	if(isset($_GET["wizard-certificate-0"])){wizard_certificate_0();exit;}
	if(isset($_POST["wizard-certificate-commonname"])){wizard_certificate_save();exit;}
	if(isset($_GET["wizard-certificate-js"])){wizard_certificate_js();exit;}
	if(isset($_GET["wizard-official-1"])){wizard_official_1();exit;}
	if(isset($_GET["wizard-official-2"])){wizard_official_2();exit;}
	if(isset($_GET["wizard-official-chain"])){wizard_official_chain();exit;}
	
	
	
	if(isset($_POST["RSA_PRIVATE_KEY"])){RSA_PRIVATE_KEY();exit;}
	if(isset($_POST["CERTIFICATE"])){WIZARD_OFFICIAL_CERTIFICATE_SAVE();exit;}
	if(isset($_POST["CERTIFICATE_CHAIN"])){CERTIFICATE_SAVE_FINAL();exit;}
	
	
	if(isset($_GET["wizard-certificate-1"])){wizard_certificate_1();exit;}
	if(isset($_POST["wizard-certificate-commonname"])){wizard_certificate_save();exit;}
	if(isset($_GET["wizard-godaddy-1"])){wizard_godaddy_1();exit;}
	if(isset($_GET["wizard-godaddy-2"])){wizard_godaddy_2();exit;}
	if(isset($_GET["wizard-godaddy-3"])){wizard_godaddy_3();exit;}
	if(isset($_GET["wizard-godaddy-4"])){wizard_godaddy_4();exit;}
	
	if(isset($_POST["OwnCert"])){OwnCert_save();exit;}
	if(isset($_POST["CommonName"])){wizard_godaddy_save();exit;}
	if(isset($_POST["GODADDY-CERTIFICATE"])){wizard_godaddy_save();exit;}
	if(isset($_POST["GODADDY-BUNDLE"])){wizard_godaddy_save();exit;}
	if(isset($_POST["GODADDY-CSR"])){wizard_godaddy_save();exit;}
	if(isset($_POST["GODADDY-FINAL"])){wizard_godaddy_create();exit;}

	wizard_certificate_js();	
	
function wizard_certificate_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_certificate}");
	echo "YahooWin6(800,'$page?wizard-certificate-0=yes&t={$_GET["t"]}','$title')";
}


function wizard_certificate_0(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$upload_certif="<center style='margin:30px'>".button("{upload_your_certificate}", "Loadjs('certificates.center.wizard.upload.php?t={$_GET["t"]}')",32)."</center>";
	
	//<center style='margin:30px'>".button("{upload_an_official_certificate}", "LoadAjax('$t','$page?wizard-official-1&t={$_GET["t"]}&div=$t')",32)."</center>
	
	$html="
	<div id='$t'>		
	<div style='width:98%' class=form>
			
	<center style='margin:30px'>".button("{create_a_sef_signed_certificate}", "LoadAjax('$t','$page?wizard-certificate-1&t={$_GET["t"]}')",32)."</center>
				
	
			
	<center style='margin:30px'>".button("{upload_your_certificate}", "LoadAjax('$t','$page?wizard-godaddy-1&t={$_GET["t"]}&div=$t')",32)."</center>			
			
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

//UsePrivKeyCrt

function wizard_official_1(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",32));
	$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");
	
	if($_SESSION["WIZARD_PRIVATE_KEY"]==null){$_SESSION["WIZARD_PRIVATE_KEY"]="-----BEGIN RSA PRIVATE KEY-----\n PUT CONTENT HERE\n-----END RSA PRIVATE KEY-----\n";}
	
	$html="
	<center style='font-size:32px'>{RSA_PRIVATE_KEY} (RSA PRIVATE KEY)</center>
	<div class=explain style='font-size:18px'>$ssl_explain</div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["WIZARD_PRIVATE_KEY"]}</textarea>
	<br>$button_save
	</center>
	</div>
	
	<script>
	var xSave$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		LoadAjax('{$_GET["div"]}','$page?wizard-official-2&t={$_GET["t"]}&div={$_GET["div"]}');
		
	}
	
	function Save$tt(CommonName,md5){
		var XHR = new XHRConnection();
		XHR.appendData('RSA_PRIVATE_KEY',encodeURIComponent(document.getElementById('text$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}

	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	}
	
function wizard_official_chain(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",32));
	$ssl_explain=$tpl->_ENGINE_parse_body("{certificate_chain_explain}<br>{certificate_chain_explain2}");
	if(!isset($_SESSION["WIZARD_CERTIFICATE_CHAIN"])){$_SESSION["WIZARD_CERTIFICATE_CHAIN"]=null;}
	
	
	$html="
	<center style='font-size:32px'>{certificate_chain}</center>
	<div class=explain style='font-size:18px'>$ssl_explain</div>
	<center style='margin:10px'>
	<textarea id='chain-certificate$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["WIZARD_CERTIFICATE_CHAIN"]}</textarea>
	<br>$button_save
	</center>
	</div>
<script>
	var xSave$tt= function (obj) {	
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
		YahooWin6Hide();
			
			
			
	}
		
	function Save$tt(CommonName,md5){
		var XHR = new XHRConnection();
		XHR.appendData('CERTIFICATE_CHAIN',encodeURIComponent(document.getElementById('chain-certificate$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
		
	
	
}
	
function wizard_official_2(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",32));
	$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");
	
	if($_SESSION["WIZARD_CERTIFICATE"]==null){$_SESSION["WIZARD_CERTIFICATE"]="-----BEGIN CERTIFICATE-----\n PUT CONTENT HERE\n-----END CERTIFICATE-----\n";}
	
	
	$html="
	<center style='font-size:32px'>{ROOT_CERT} (CERTIFICATE)</center>
	<div class=explain style='font-size:18px'>$ssl_explain</div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["WIZARD_CERTIFICATE"]}</textarea>
	<br>$button_save
	</center>
	</div>
	
	<script>
	
	var xSave$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		LoadAjax('{$_GET["div"]}','$page?wizard-official-chain&t={$_GET["t"]}&div={$_GET["div"]}');
	
	}	
	
	var xSaveold$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
		YahooWin6Hide();
	
	}
	
	function Save$tt(CommonName,md5){
		var XHR = new XHRConnection();
		XHR.appendData('CERTIFICATE',encodeURIComponent(document.getElementById('text$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}
	
function RSA_PRIVATE_KEY(){
	$_SESSION["WIZARD_PRIVATE_KEY"]=url_decode_special_tool($_POST["RSA_PRIVATE_KEY"]);
	$_SESSION["WIZARD_PRIVATE_KEY"]=str_replace("\r\n", "\n", $_SESSION["WIZARD_PRIVATE_KEY"]);
	$_SESSION["WIZARD_PRIVATE_KEY"]=str_replace("\n\n", "\n", $_SESSION["WIZARD_PRIVATE_KEY"]);
}
function WIZARD_OFFICIAL_CERTIFICATE_SAVE(){
	$_SESSION["WIZARD_CERTIFICATE"]=url_decode_special_tool($_POST["CERTIFICATE"]);
	$_SESSION["WIZARD_CERTIFICATE"]=str_replace("\r\n", "\n", $_SESSION["WIZARD_CERTIFICATE"]);
	$_SESSION["WIZARD_CERTIFICATE"]=str_replace("\n\n", "\n", $_SESSION["WIZARD_CERTIFICATE"]);
}

function CERTIFICATE_SAVE_FINAL(){
	$tpl=new templates();
	$_SESSION["WIZARD_CERTIFICATE"]=$_SESSION["WIZARD_CERTIFICATE"];
	$_SESSION["WIZARD_CERTIFICATE_CHAIN"]=url_decode_special_tool($_POST["CERTIFICATE_CHAIN"]);
	$_SESSION["WIZARD_CERTIFICATE_CHAIN"]=str_replace("\r\n", "\n", $_SESSION["WIZARD_CERTIFICATE_CHAIN"]);
	$_SESSION["WIZARD_CERTIFICATE_CHAIN"]=str_replace("\n\n", "\n", $_SESSION["WIZARD_CERTIFICATE_CHAIN"]);
	
	$id=time();
	$CERTIFICATE_PATH=dirname(__FILE__)."/ressources/conf/upload/$id-WIZARD_CERTIFICATE.pem";
	@file_put_contents($CERTIFICATE_PATH, $_SESSION["WIZARD_CERTIFICATE"]);
	$md5=trim(exec("/usr/bin/openssl x509 -noout -modulus -in $CERTIFICATE_PATH | /usr/bin/openssl md5 2>&1"));
	
	
	$RSA_PRIVATE_KEY_PATH=dirname(__FILE__)."/ressources/conf/upload/$id-WIZARD_PRIVATE_KEY";
	@file_put_contents($RSA_PRIVATE_KEY_PATH,$_SESSION["WIZARD_PRIVATE_KEY"]);
	$md52=trim(exec("/usr/bin/openssl rsa -noout -modulus -in $RSA_PRIVATE_KEY_PATH | /usr/bin/openssl md5 2>&1"));
	
	
	@unlink($RSA_PRIVATE_KEY_PATH);
	if($md52<>$md5){
		echo $tpl->javascript_parse_text("Certificate and Private Key match does not macth!");
		return;
	}
	$CommonName=null;
	$emailAddress=null;
	$ISSUER=null;
	$SUBJECT=null;
	$DNS=null;
	$levelenc=2048;
	exec("/usr/bin/openssl x509 -text -in $CERTIFICATE_PATH 2>&1",$results);
	@unlink($CERTIFICATE_PATH);
	
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Issuer:\s+(.+)#", $ligne,$re)){
			$ISSUER=trim($re[1]);
			continue;
		}
		
		if(preg_match("#Subject:\s+(.+)#", $ligne,$re)){
			$SUBJECT=trim($re[1]);
			continue;
		}
		
		if(preg_match("#DNS:(.+)#", $ligne,$re)){
			$DNS=$re[1];
			continue;
		}
		
		if(preg_match("#RSA Public Key:\s+.*?([0-9]+)\s+bit#i",$ligne,$re)){
			$levelenc=$re[1];
			continue;
		}
		
	}
	
	if($ISSUER==null){
		if($SUBJECT==null){
			echo "No information can be extracted...\n";
			return;
		}
	}
	
	$ISSUER_TR=explode(",",$ISSUER);
	while (list ($num, $ligne) = each ($ISSUER_TR) ){
		if(preg_match("#(.*?)=(.*)#", $ligne,$re)){
			$ISSUER_ARRAY[trim(strtoupper($re[1]))]=trim($re[2]);
		}
	}
	$SUBJECT_TR=explode(",",$SUBJECT);
	while (list ($num, $ligne) = each ($SUBJECT_TR) ){
		if(preg_match("#(.*?)=(.*)#", $ligne,$re)){
			$SUBJECT_ARRAY[trim(strtoupper($re[1]))]=trim($re[2]);
		}
	}	
	
	

	$CountryName=$SUBJECT_ARRAY["C"];
	

	if(isset($ISSUER_ARRAY["L"])){
		$localityName=$ISSUER_ARRAY["L"];
	}
	
	if(isset($ISSUER_ARRAY["ST"])){
		$stateOrProvinceName=$ISSUER_ARRAY["ST"];
	}
	
	$OrganizationalUnit=$ISSUER_ARRAY["OU"];
	$OrganizationName=$ISSUER_ARRAY["O"];
	
	
	
	if(isset($SUBJECT_ARRAY["CN"])){
		if(preg_match("#^(.+?)\/#", $SUBJECT_ARRAY["CN"],$re)){
			$CommonName=$re[1];
		}else{
			$CommonName=$SUBJECT_ARRAY["CN"];
		}
		
		if(preg_match("#emailAddress=(.*?)($|\s+)#", $SUBJECT_ARRAY["CN"],$re)){
			$emailAddress=$re[1];
		}
		
	}
	
	if($CommonName==null){
		if($DNS<>null){
			$DNS_TR=explode(",",$DNS);
			if(count($DNS_TR)>0){
				while (list ($num, $ligne) = each ($DNS_TR) ){
					if(preg_match("#DNS:(.+)#", $ligne,$re)){$ligne=$re[1];}
					$CommonName=$ligne;break;
				}
			}else{
				if(preg_match("#DNS:(.+)#", $DNS,$re)){$DNS=$re[1];}
				$CommonName=$DNS;
			}
		}
		
	}
	
	
	if($CommonName==null){
		echo "Unable to determine Common Name in:\n";
		while (list ($num, $ligne) = each ($ISSUER_ARRAY) ){echo "$num = $ligne\n";}
		while (list ($num, $ligne) = each ($SUBJECT_ARRAY) ){echo "$num = $ligne\n";}
		return;
		
	}
	$q=new mysql();
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	
	$WIZARD_CERTIFICATE=mysql_escape_string2($_SESSION["WIZARD_CERTIFICATE"]);
	$WIZARD_PRIVATE_KEY=mysql_escape_string2($_SESSION["WIZARD_PRIVATE_KEY"]);
	$WIZARD_CERTIFICATE_CHAIN=mysql_escape_string2($_SESSION["WIZARD_CERTIFICATE_CHAIN"]);
	
	if(strlen($WIZARD_CERTIFICATE)<20){
		echo "Certificate, no content!\n";
		return;
	}
	
	if($ligne["CommonName"]==null){
		$sql="INSERT INTO sslcertificates
		(commonName,CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,
		emailAddress,localityName,password,UsePrivKeyCrt,`crt`,`privkey`,`srca`,`levelenc`,`bundle`) VALUES
		('$CommonName','$CountryName','$stateOrProvinceName','720',
				'$OrganizationName','$OrganizationalUnit','$emailAddress'
						,'$localityName','',1,'$WIZARD_CERTIFICATE','$WIZARD_PRIVATE_KEY','$WIZARD_PRIVATE_KEY','$levelenc','$WIZARD_CERTIFICATE_CHAIN')";
						$generate=true;
	}else{
	$sql="UPDATE sslcertificates SET
			CountryName='$CountryName',
			stateOrProvinceName='$stateOrProvinceName',
			CertificateMaxDays='720',
			OrganizationName='$OrganizationName',
			OrganizationalUnit='$OrganizationalUnit',
			emailAddress='$emailAddress',
			localityName='$localityName',
			`crt`='$WIZARD_CERTIFICATE',
			`privkey`='$WIZARD_PRIVATE_KEY',
			`srca`='$WIZARD_PRIVATE_KEY',
			`levelenc`='$levelenc',
			`bundle`='$WIZARD_CERTIFICATE_CHAIN',
			password='',
			UsePrivKeyCrt=1
			WHERE CommonName='$CommonName'";
	
						}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}	

function wizard_godaddy_2(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",26));
	$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");
	
	if($_SESSION["GODADDY"]["GODADDY-CERTIFICATE"]==null){$_SESSION["GODADDY"]["GODADDY-CERTIFICATE"]="-----BEGIN CERTIFICATE-----\n PUT CONTENT HERE\n-----END CERTIFICATE-----\n";}
	
	
	$html="
	<center style='font-size:32px'>{certificate}</center>
	<div class=explain style='font-size:18px'>$ssl_explain</div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["GODADDY"]["GODADDY-CERTIFICATE"]}</textarea>
	<br>$button_save
	</center>
	</div>
	
	<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	LoadAjax('{$_GET["div"]}','$page?wizard-godaddy-3&t={$_GET["t"]}&div={$_GET["div"]}');
}
	
	function Save$tt(CommonName,md5){
	var XHR = new XHRConnection();
	XHR.appendData('GODADDY-CERTIFICATE',encodeURIComponent(document.getElementById('text$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	}
function wizard_godaddy_3(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",26));
	$ssl_explain=$tpl->_ENGINE_parse_body("{certificate_chain_explain}<br>{bundle_godaddy_ssl_explain}");
	
	if($_SESSION["GODADDY"]["GODADDY-BUNDLE"]==null){$_SESSION["GODADDY"]["GODADDY-BUNDLE"]="-----BEGIN CERTIFICATE-----\n-----END CERTIFICATE-----\n-----BEGIN CERTIFICATE-----\n-----END CERTIFICATE-----\n-----BEGIN CERTIFICATE-----\n-----END CERTIFICATE-----";}
	
	
$html="
<center style='font-size:32px'></center>
		<div class=explain style='font-size:18px'>$ssl_explain</div>
		<center style='margin:10px'>
		<textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["GODADDY"]["GODADDY-BUNDLE"]}</textarea>
		<br>$button_save
		</center>
		</div>
	
<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	LoadAjax('{$_GET["div"]}','$page?wizard-godaddy-4&t={$_GET["t"]}&div={$_GET["div"]}');
}
	
function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('GODADDY-BUNDLE',encodeURIComponent(document.getElementById('text$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}	
	
	


function wizard_godaddy_1(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{create}", "Save$tt()",26));
	

	
	$html="
	<center style='font-size:32px'>{certificate_name}</center>
	
	<center style='margin:10px'>
	". Field_text("Certificate-$t",null,"font-size:22px;width:90%",null,null,null,false,"SaveCer$t(event)",false)."
	<br>
	
	$button_save
	</center>
	</div>
	
	<script>
	var xSave$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
		YahooWin6Hide();
		var certif=document.getElementById('Certificate-$t').value;
		Loadjs('certificates.center.php?certificate-edit-js=yes&CommonName='+certif);
	}
	
	function SaveCer$t(e){
		if(!checkEnter(e)){return;}
		Save$tt();
	}
	
	function Save$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('OwnCert',encodeURIComponent(document.getElementById('Certificate-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}

	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	}

function wizard_godaddy_4(){
	$CERTIFICATE_PATH=dirname(__FILE__)."/ressources/conf/upload/GODADDY-CSR.csr";
	@file_put_contents($CERTIFICATE_PATH, $_SESSION["GODADDY"]["GODADDY-CSR"]);
	exec("/usr/bin/openssl req -in $CERTIFICATE_PATH -noout -text 2>&1",$results);
	@unlink($CERTIFICATE_PATH);
	
	$MAIN=certificate_center_parse_array($results);
	
	$CountryName=$MAIN["CountryName"];
	$OrganizationalUnit=$MAIN["OrganizationalUnit"];
	$OrganizationName=$MAIN["OrganizationName"];
	$CommonName=$MAIN["CommonName"];
	$emailAddress=$MAIN["emailAddress"];
	$localityName=$MAIN["localityName"];
	$stateOrProvinceName=$MAIN["stateOrProvinceName"];
	$levelenc=$MAIN["levelenc"];
	
	
	if($CommonName==null){
		echo FATAL_ERROR_SHOW_128("{unable_to_find_commonname_incsr}");
		return;
	}
	
	
	$_SESSION["GODADDY"]["commonName"]=$CommonName;
	$_SESSION["GODADDY"]["stateOrProvinceName"]=$stateOrProvinceName;
	$_SESSION["GODADDY"]["localityName"]=$localityName;
	$_SESSION["GODADDY"]["OrganizationName"]=$OrganizationName;
	$_SESSION["GODADDY"]["OrganizationalUnit"]=$OrganizationalUnit;
	$_SESSION["GODADDY"]["emailAddress"]=$emailAddress;
	$_SESSION["GODADDY"]["levelenc"]=$levelenc;
	

	
	$ligne=$_SESSION["GODADDY"];
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{save_certificate}", "Save$tt()",26));
	$ligne=$_SESSION["GODADDY"];
	$html[]="
	<div style='font-size:32px;margin-bottom:20px'>{certificate_details}</div>
	<table style='width:100%'>	
		<tr>
			<td class=legend style='font-size:22px'>{CommonName}:</td>
			<td style='font-size:22px;font-weight:bold'>$CommonName</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{stateOrProvinceName}:</td>
			<td style='font-size:22px;font-weight:bold'>$stateOrProvinceName</td>
		</tr>
			<tr>
			<td class=legend style='font-size:22px'>{localityName}:</td>
			<td style='font-size:22px;font-weight:bold'>$localityName</td>
		</tr>
			<tr>
			<td class=legend style='font-size:22px'>{organizationName}:</td>
			<td style='font-size:22px;font-weight:bold'>$OrganizationName</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{organizationalUnitName}:</td>
			<td style='font-size:22px;font-weight:bold'>$OrganizationalUnit</td>
		</tr>
			<tr>
			<td class=legend style='font-size:22px'>{emailAddress}:</td>
			<td style='font-size:22px;font-weight:bold'>$emailAddress</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{level_encryption}:</td>
			<td style='font-size:22px;font-weight:bold'>$levelenc</td>
		</tr>		
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{CSR}:</td>
		<td style='font-size:22px;font-weight:bold'>". strlen($_SESSION["GODADDY"]["GODADDY-CSR"])." Bytes</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{certificate}:</td>
		<td style='font-size:22px;font-weight:bold'>". strlen($_SESSION["GODADDY"]["GODADDY-CERTIFICATE"])." Bytes</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{certificate_bundle}:</td>
		<td style='font-size:22px;font-weight:bold'>". strlen($_SESSION["GODADDY"]["GODADDY-BUNDLE"])." Bytes</td>
	</tr>	
	<tr>
	<td colspan=2 align='right'><hr>$button_save</td>
	</tr>
	</table>
</div>
<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
	
	
	YahooWin6Hide();
}
	
function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('GODADDY-FINAL','yes');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
</script>	
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

	
function wizard_certificate_1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$sock->GET_INFO("myhostname");
	$t=time();
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
	$title=$tpl->_ENGINE_parse_body("{new_certificate}");
	$html[]="<div class=explain style='font-size:18px'>{wizard_certificate_1}<br><i>{CSR_MULTIPLE_EXPLAIN}</i></div>";
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_text_table("wizard-certificate-commonname","{CommonName}",$hostname,22,null,400);
	$html[]=Field_checkbox_table("AsSquidCertificate", "{proxy_certificate}",0,22,"{proxy_gen_certificate_explain}");
	$html[]=Field_list_table("wizard-certificate-levelenc","{level_encryption}",2048,22,$ENC);
	$html[]=Field_password_table("wizard-certificate-password","{password}","secret",22,null,300);
	$html[]=Field_button_table_autonome("{add}","Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	
	if(document.getElementById('squid_ports_popup_certificates')){
		if(document.getElementById('squid_ports_popup_certificates_num')){
			var id=document.getElementById('squid_ports_popup_certificates_num').value;
			var Common=document.getElementById('wizard-certificate-commonname').value;
			LoadAjaxSilent('squid_ports_popup_certificates','squid.ports.php?certificate-refresh=yes&default='+Common+'&t='+id);
		}
	}	
	
	
	$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
	
	var commonName=document.getElementById('wizard-certificate-commonname').value;
	Loadjs('openssl.CSR.progress.php?generate-csr='+commonName);
	YahooWin6Hide();
	
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('wizard-certificate-commonname',encodeURIComponent(document.getElementById('wizard-certificate-commonname').value));
	XHR.appendData('wizard-certificate-levelenc',document.getElementById('wizard-certificate-levelenc').value);
	XHR.appendData('wizard-certificate-password',encodeURIComponent(document.getElementById('wizard-certificate-password').value));
	if(document.getElementById('AsSquidCertificate').checked){
		XHR.appendData('wizard-certificate-proxy',1);
	}else{
		XHR.appendData('wizard-certificate-proxy',0);
	}
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
}
</script>
		
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}	

function wizard_godaddy_save(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_SESSION["GODADDY"][$num]=url_decode_special_tool($ligne);
		
	}
	
}


function OwnCert_save(){
	$UsePrivKeyCrt=1;
	$CommonName=mysql_escape_string2(url_decode_special_tool($_POST["OwnCert"]));
	$privkey=null;
	if($CommonName==null){$CommonName="Certificate-".time();}
	
	if(is_file("/etc/openssl/private-key/privkey.key")){
		$privkey=mysql_escape_string2(@file_get_contents("/etc/openssl/private-key/privkey.key"));
	}
	$sql="DELETE FROM sslcertificates WHERE CommonName='$CommonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sql="INSERT INTO sslcertificates (commonName,UsePrivKeyCrt,privkey) VALUES ('$CommonName','$UsePrivKeyCrt','$privkey')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function wizard_godaddy_create(){
	
	while (list ($num, $ligne) = each ($_SESSION["GODADDY"]) ){
		$ARRAY[$num]=mysql_escape_string2($ligne);
		
	}
	
	
	$CommonName=$ARRAY["commonName"];
	$stateOrProvinceName=$ARRAY["stateOrProvinceName"];
	$localityName=$ARRAY["localityName"];
	$OrganizationName=$ARRAY["OrganizationName"];
	$OrganizationalUnit=$ARRAY["OrganizationalUnit"];
	$emailAddress=$ARRAY["emailAddress"];
	$levelenc=$ARRAY["levelenc"];
	
	$CSR=$ARRAY["GODADDY-CSR"];
	$WIZARD_CERTIFICATE=$ARRAY["GODADDY-CERTIFICATE"];
	$WIZARD_BUNDLE=$ARRAY["GODADDY-BUNDLE"];
	$WIZARD_PRIVATE_KEY='-----BEGIN RSA PRIVATE KEY----- NONE GODADDY -----END RSA PRIVATE KEY-----';
	$q=new mysql();
	if(!$q->FIELD_EXISTS("sslcertificates","UseGodaddy","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `UseGodaddy` smallint(1) DEFAULT 0";$q->QUERY_SQL($sql,'artica_backup');}
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	
	if($ligne["CommonName"]==null){
		$sql="INSERT INTO sslcertificates
		(commonName,CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,
		emailAddress,localityName,password,UseGodaddy,`crt`,`privkey`,`srca`,`levelenc`,`bundle`,`csr`) VALUES
		('$CommonName','','$stateOrProvinceName','720',
		'$OrganizationName','$OrganizationalUnit','$emailAddress'
		,'$localityName','',1,'$WIZARD_CERTIFICATE','$WIZARD_PRIVATE_KEY','$WIZARD_PRIVATE_KEY','$levelenc','$WIZARD_BUNDLE','$CSR')";
		$generate=true;
	}else{
		$sql="UPDATE sslcertificates SET
		CountryName='',
		stateOrProvinceName='$stateOrProvinceName',
		CertificateMaxDays='720',
		OrganizationName='$OrganizationName',
		OrganizationalUnit='$OrganizationalUnit',
		emailAddress='$emailAddress',
		localityName='$localityName',
		`crt`='$WIZARD_CERTIFICATE',
		`csr`='$CSR',
		`privkey`='$WIZARD_PRIVATE_KEY',
		`srca`='$WIZARD_PRIVATE_KEY',
		`bundle`='$WIZARD_BUNDLE',
		`levelenc`='$levelenc',
		password='',
		UseGodaddy=1
		WHERE CommonName='$CommonName'";
	
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function wizard_certificate_save(){
	$password=url_decode_special_tool($_POST["wizard-certificate-password"]);
	$password=mysql_escape_string2($password);
	$CommonName=url_decode_special_tool($_POST["wizard-certificate-commonname"]);
	$CommonName=strtolower(trim($CommonName));
	if($CommonName==null){
		echo "CommonName, no such data";
		return;
	}
	$q=new mysql();
	
	$AsProxyCertificate=intval($_POST["wizard-certificate-proxy"]);
	if(!$q->FIELD_EXISTS("sslcertificates","AsProxyCertificate","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `AsProxyCertificate` smallint(1) NOT NULL,ADD INDEX ( `AsProxyCertificate` )";$q->QUERY_SQL($sql,'artica_backup');}
	
	
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]<>null){echo "$CommonName Already exists...\n";return;}
	$levelenc=intval($_POST["wizard-certificate-levelenc"]);
	if($levelenc==0){$levelenc=2048;}
	$sql="INSERT IGNORE INTO sslcertificates (CommonName,keyPassword,password,AsProxyCertificate,levelenc) VALUES ('$CommonName','$password','$password','$AsProxyCertificate','$levelenc')";


	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "MySQL Error:\n".$q->mysq_error."\n$sql";return;}



}
