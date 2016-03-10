<?php
$GLOBALS["ICON_FAMILY"]="VPN";
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.openvpn.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.tcpip.inc');
$users=new usersMenus();
if(!$users->AsSystemAdministrator){die("alert('no access');");}

	if(isset($_GET["tabs"])){tabs();exit();}
	if(isset($_GET["certificate_infos"])){certificate_infos();exit();}
	if(isset($_POST["CountryName"])){save();exit;}
	
	
page();


function page(){

	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$tpl=new templates();
	$db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
	$tbl=explode("\n",$db);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
			$regs[2]=trim($regs[2]);
			$regs[1]=trim($regs[1]);
			$array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
		}
	}
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	$ligne=unserialize($sock->GET_INFO("OpenVPNCertificateSettings"));
	
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}
	if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=2048;}
	
	$hostname=$sock->GET_INFO("myhostname");
	$t=time();
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	$bt_name="{apply}";

	$html[]="<div style='font-size:42px;margin-bottom:15px'>$hostname</div>";
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
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
	$html[]="</table>";
	
$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('index.openvpn.client.progress.php?uid=OpenVPN-MASTER');
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('CommonName',encodeURIComponent('$hostname'));
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

function save(){
	
	$_POST["CommonName"]=strtolower(trim(url_decode_special_tool($_POST["CommonName"])));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($_POST), "OpenVPNCertificateSettings");
}

function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{APP_OPENVPN}::{certificate_infos}");
	$html="YahooWin4('600','$page?tabs=yes','$title')";
	echo $html;
	
}


function tabs(){
	
	$page=CurrentPageName();
	if($html<>null){echo $html;}
	$array["certificate_infos"]="{certificate_infos}";
	
	//$array["adv"]="{clients}";


	
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
			
		}
	$tpl=new templates();
	
	

	$html="
		<div id='main_openvpn_sslkey' style='background-color:white;'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_openvpn_sslkey').tabs();
			

			});
		</script>
	
	";
		
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	
	echo $html;	
	
	
}

function certificate_infos(){
	$sock=new sockets();
	$tbl=unserialize(base64_decode(($sock->getFrameWork("cmd.php?certificate-viewinfos=yes"))));

	if(!is_array($tbl)){return null;}
	while (list ($num, $val) = each ($tbl) ){
		if(trim($val)==null){continue;}
		$val=str_replace("\t","&nbsp;&nbsp;&nbsp;",$val);
		
		if(preg_match('#^([a-zA-Z\s]+):(.*)#',$val,$re)){
			$val="<strong>{$re[1]}:</strong>&nbsp;{$re[2]}";
		}
		
		$t=$t."<div><code>$val</code></div>";
	}
	
	$html="
	<div style='width:99%;height:450px;overflow:auto' class=form>$t</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

