<?php
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
	if(isset($_GET["pfx"])){pfx();exit;}
	if(isset($_GET["delete-certificate-js"])){certificate_delete_js();exit;}
	if(isset($_POST["delete-certificate"])){certificate_delete();exit;}
	
	if(isset($_GET["certificate-upload-popup"])){certificate_upload_popup();exit;}
	if(isset($_GET["certificate-upload-js"])){certificate_upload_js();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["certificate-edit-js"])){certificate_edit_js();exit;}
	if(isset($_GET["certificate-edit-tabs"])){certificate_edit_tabs();exit;}
	if(isset($_GET["certificate-js"])){certificate_single_js();exit;}
	if(isset($_GET["certificate-popup"])){certificate_infos();exit;}
	if(isset($_POST["commonName"])){certificate_save();exit;}

	if(isset($_GET["SquidValidate"])){SquidValidate();exit;}
	if(isset($_GET["SquidValidatePerform"])){SquidValidatePerform();exit;}
	
	
	
	if(isset($_POST["generate-key"])){generate_key();exit;}
	if(isset($_POST["save-privkey"])){save_private_key();exit;}
	if(isset($_GET["generate-x509"])){generate_x509();exit;}
	if(isset($_GET["tools"])){tools_main();exit;}
	if(isset($_GET["tools-main"])){tools_main();exit;}
	
	if(isset($_GET["x509-js"])){x509_js();exit;}
	if(isset($_POST["delete-certificate"])){certificate_delete();exit;}
	if(isset($_GET["privatekey"])){privatekey();exit;}
	if(isset($_GET["CSR"])){csr_gen();exit;}
	if(isset($_POST["GEN-CSR"])){csr_gen_perform();exit;}
	
	js();
	
function tabs(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=20;
	
	$array["popup"]="{certificates_center}";
	$array["privatekey"]="{private_key}";
	$array["CSR"]="{CSR}";
	
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_certificates_center_tabs',1490)."<script>LeftDesign('certificate-white-256-opac20.png');</script>";
	
	echo $html;
	
	
}


function pfx(){
	
	$CommonName=$_GET["pfx"];
	$q=new mysql();
	$sql="SELECT pks12  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$data=$ligne["pks12"];
	$fsize=strlen($data);
	writelogs_meta("$CommonName -> Send {$fsize}Bytes to client",__FUNCTION__,__FILE__,__LINE__);
	header('Content-type: application/x-pkcs12');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$CommonName.pfx\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $data;
	
	
}


function certificate_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$CommonName=$_GET["delete-certificate-js"];
	$id=$_GET["id"];
	$ask=$tpl->javascript_parse_text("{delete} $CommonName {certificate}\\n{delete_certificate_ask}");
	$t=time();
	echo "
	var xDelete$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	if( document.getElementById('row$id') ){ $('#row$id').remove(); return;}
	if( document.getElementById('$id') ){ $('#$id').remove(); }

}
function Delete$t(){
if(!confirm('$ask')){return;}
var XHR = new XHRConnection();
XHR.appendData('delete-certificate','$CommonName');
XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
Delete$t();";
}
function certificate_delete(){
	$q=new mysql();
	$commonName=$_POST["delete-certificate"];
	$q->QUERY_SQL("DELETE FROM sslcertificates WHERE CommonName='$commonName'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

}

function csr_gen(){
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$ligne=unserialize($sock->GET_INFO("CertificateCenterCSR"));
	$t=time();
	if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}
	if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=1024;}
	$button_save=$tpl->_ENGINE_parse_body(button("{edit}", "Save$t()",30));
	if($ligne["password"]==null){$ligne["password"]="MyPassword";}
	
	$sock->getFrameWork("openssl.php?copy-csr=yes");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
	
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_text_table("CommonName-$t","{CommonName}",$ligne["CommonName"],22,null,400);
	$html[]=Field_text_table("CountryName-$t","{countryName}",$ligne["CountryName"],22,null,110);
	$html[]=Field_text_table("stateOrProvinceName-$t","{stateOrProvinceName}",$ligne["stateOrProvinceName"],22,null,400);
	$html[]=Field_text_table("localityName-$t","{localityName}",$ligne["localityName"],22,null,400);
	$html[]=Field_text_table("OrganizationName-$t","{organizationName}",$ligne["OrganizationName"],22,null,400);
	$html[]=Field_text_table("OrganizationalUnit-$t","{organizationalUnitName}",$ligne["OrganizationalUnit"],22,null,400);
	$html[]=Field_text_table("emailAddress-$t","{emailAddress}",$ligne["emailAddress"],22,null,400);
	$html[]=Field_password_table("password-$t","{password}",$ligne["password"],22,null,300);
	$html[]=Field_button_table_autonome("{generate_csr}","Submit$t",30);
	$html[]="</table>";

	
	$html[]="<hr>
	<textarea
	style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px' id='csr-$t'>$data</textarea>
	";	
	
	
	$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('main_certificates_center_tabs');
	if(document.getElementById('squid_ports_popup_certificates')){
		var id=document.getElementById('squid_ports_popup_certificates_num').value;
		var Common=document.getElementById('CommonName-$t').value;
		LoadAjaxSilent('squid_ports_popup_certificates','squid.ports.php?certificate-refresh=yes&default='+Common+'&t='+id);
	}
	
	
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('GEN-CSR','yes');
	XHR.appendData('CommonName',encodeURIComponent(document.getElementById('CommonName-$t').value));
	XHR.appendData('CountryName',encodeURIComponent(document.getElementById('CountryName-$t').value));
	XHR.appendData('stateOrProvinceName',encodeURIComponent(document.getElementById('stateOrProvinceName-$t').value));
	XHR.appendData('localityName',encodeURIComponent(document.getElementById('localityName-$t').value));
	XHR.appendData('OrganizationName',encodeURIComponent(document.getElementById('OrganizationName-$t').value));
	XHR.appendData('OrganizationalUnit',encodeURIComponent(document.getElementById('OrganizationalUnit-$t').value));
	XHR.appendData('emailAddress',encodeURIComponent(document.getElementById('emailAddress-$t').value));
	XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	
</script>
";
echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}	

function csr_gen_perform(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$ARRAY[$num]=url_decode_special_tool($ligne);
	
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/CSR.ARRAY", serialize($ARRAY));
	$sock=new sockets();
	$sock->getFrameWork("openssl.php?gen-csr=yes");
	
}

function privatekey(){
	$tpl=new templates();
	$page=CurrentPageName();
	$tt=time();
	$sock=new sockets();
	$sock->getFrameWork("openssl.php?copy-privatekey=yes");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key");
	$button_save=$tpl->_ENGINE_parse_body(button("{edit}", "Save$tt()",26));
$html="
<textarea
	style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px' id='crt$tt'>$data</textarea>
	<center style='margin:20px'>$button_save</center>
<script>
	var xSave$tt=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	RefreshTab('main_certificates_center_tabs');
	
}
function Save$tt(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('crt$tt').value);
	XHR.appendData('save-privkey',pp);
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);


}

function save_private_key(){
	
	$_POST["save-privkey"]=url_decode_special_tool($_POST["save-privkey"]);
	@file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/privkey.key", $_POST["save-privkey"]);
	$sock=new sockets();
	$sock->getFrameWork("openssl.php?move-privkey=yes");
}

	
function certificate_single_js(){
	$CommonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$YahooWin3="YahooWin3";
	$t=$_GET["t"];
	if(isset($_GET["YahooWin"])){$YahooWin3=$_GET["YahooWin"];}
	echo "$YahooWin3('895','$page?certificate-tabs=yes&t=$t&CommonName=$CommonName&YahooWin=$YahooWin3','$CommonName');";	
	
}	

function certificate_edit_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWin6(1025,'$page?certificate-edit-tabs=yes&CommonName=$CommonName&t={$_GET["t"]}','$title')";

}
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificates_center}");
	$html="YahooWin2('990','$page?popup=yes','$title')";
	echo $html;
}
function x509_js(){
	$CommonName=$_GET["x509-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("$CommonName::{generate_x509}");
	$warn_gen_x509=$tpl->javascript_parse_text("{warn_gen_x509}");
	$html="
	function Gen$t(){
		if(confirm('$warn_gen_x509')){
			YahooWin4('750','$page?generate-x509=$CommonName','$title');
			}
		}	
		Gen$t();
	";
	echo $html;	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$Organization=$tpl->_ENGINE_parse_body("{organizationName}");
	$organizationalUnitName=$tpl->_ENGINE_parse_body("{organizationalUnitName}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$emailAddress=$tpl->javascript_parse_text("{emailAddress}");
	$new_certificate=$tpl->javascript_parse_text("{new_certificate}");
	$title=$tpl->_ENGINE_parse_body("{certificates_center}");
	$delete_certificate_ask=$tpl->javascript_parse_text("{delete_certificate_ask}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_certificate</strong>', bclass: 'Add', onpress : new_certificate$t},
	],";		
	
$html="
<div style='margin-left:0px'>
<table class='TABLE_CERTIFICATE_CENTER_MAIN' style='display: none' id='TABLE_CERTIFICATE_CENTER_MAIN' style='width:100%'></table>
</div>
<script>
var rowid$t='';
function LoadTable$t(){
$('#TABLE_CERTIFICATE_CENTER_MAIN').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>$hostname</span>', name : 'CommonName', width : 320, sortable : true, align: 'left'},	
		{display: '<span style=font-size:22px>Expire</span>', name : 'DateTo', width :128, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$Organization</span>', name : 'Organization', width :250, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$organizationalUnitName</span>', name : 'OrganizationalUnit', width :250, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$emailAddress</span>', name : 'emailAddress', width :250, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>PKCS12</span>', name : 'pcs12', width :87, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'del', width :65, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'CommonName'},
		{display: '$Organization', name : 'Organization'},
		{display: '$organizationalUnitName', name : 'OrganizationalUnit'},
		{display: '$emailAddress', name : 'emailAddress'},
		],
		
	sortname: 'CommonName',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
}

function new_certificate$t(){
	Loadjs('certificates.center.wizard.php?t=$t');
	
}
function certificate$t(CommonName){
	YahooWin3('895','$page?certificate-tabs=yes&t=$t&CommonName='+CommonName+'&YahooWin=YahooWin3',CommonName);
}

var xDeletSSlCertificate$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;};
		$('#row'+rowid$t).remove();
		
	
	}
		
function DeletSSlCertificate$t(CommonName,md5){
		if(!confirm('$delete_certificate_ask')){return;}
		var XHR = new XHRConnection();
		rowid$t=md5;
		XHR.appendData('delete-certificate',CommonName);
		XHR.sendAndLoad('$page', 'POST',xDeletSSlCertificate$t);				
	}
	
LoadTable$t();
</script>
";

	echo $html;	
	
}
function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$search='%';
	$table="sslcertificates";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS("sslcertificates", $database)){$q->BuildTables();}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	$AsProxyCertificate_text="<br><i>".$tpl->javascript_parse_text("{proxy_certificate}")."</i>";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$AsProxyCertificate=null;
		$color="black";
		$zmd5=md5(serialize($ligne));
		$delete=imgsimple("delete-42.png","","DeletSSlCertificate$t('{$ligne["CommonName"]}','$zmd5')");
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-certificate-js={$ligne["CommonName"]}&id=$zmd5')");
	
	$jsEdit="Loadjs('$MyPage?certificate-edit-js=yes&CommonName={$ligne["CommonName"]}&t=$t');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\"
	style='font-size:20px;color:$color;text-decoration:underline;font-weight:bold'>";
	
	if($ligne["AsProxyCertificate"]==1){
		$AsProxyCertificate=$AsProxyCertificate_text;
	}
	$pfx=null;
	if($ligne["pks12"]<>null){
		$pfx="<center><a href=\"$MyPage?pfx={$ligne["CommonName"]}\"><img src='img/pfx-42.png'></a></center>";
		
	}
	$treste=null;
	$DateFrom=$ligne["DateFrom"];
	$DateTo=$ligne["DateTo"];
	if($DateTo<>"0000-00-00"){
		$t1=time();
		$t2=strtotime("$DateTo 00:00:00");
		$treste=$tpl->javascript_parse_text(distanceOfTimeInWords($t1,$t2));
	}else{
		$DateTo="-";
	}
	
	$UsedBy=UsedBy($ligne["CommonName"]);
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:20px;color:$color'>$urljs{$ligne["CommonName"]}</a>$AsProxyCertificate<br>$treste</span>$UsedBy",
			"<span style='font-size:20px;color:$color'>$urljs{$DateTo}</a></span>",
			"<span style='font-size:20px;color:$color'>$urljs{$ligne["OrganizationName"]}</a></span>",
			"<span style='font-size:20px;color:$color'>$urljs{$ligne["OrganizationalUnit"]}</a></span>",
			"<span style='font-size:20px;color:$color'>$urljs{$ligne["emailAddress"]}</a></span>",
			"<span style='font-size:20px;color:$color'>$pfx</a></span>",
			"<span style='font-size:20px;color:$color'><center>$delete</center></a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}



function certificate_edit_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page="miniadmin.certificates.php";
	$PageMe=CurrentPageName();
	$CommonName=urlencode($_GET["CommonName"]);
	$array["settings"]="{settings}";
	$array["certificates"]="{certificates}";
	
	//$array["apache_chain"]="{apache_chain}";
	//$array["DynCert"]="{dynamic_chain}";
	$array["tools"]="{tools}";
	
	$fontsize=22;
	$id=md5($CommonName);

	while (list ($num, $ligne) = each ($array) ){
		if($num=="settings"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.main.php?CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="certificates"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.table.php?CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="ROOT_CERT"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.srca.php?CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="CSR"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.csr.php?CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="privkey"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.privkey.php?CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="certificate"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.crt.php?CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="apache_chain"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?certificate-edit-bundle=yes&CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="DynCert"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?certificate-edit-DynCert=yes&CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="tools"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$PageMe?tools=yes&CommonName=$CommonName&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		
	}
	
	echo build_artica_tabs($html, "main_certificate_$id");
}


function certificate_save(){
	$q=new mysql();
	$q->BuildTables();
	$CommonName=strtolower(trim($_POST["CommonName"]));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	while (list ($num, $vl) = each ($_POST) ){$_POST[$num]=addslashes($vl);}
	$generate=false;
	
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){
		$sql="INSERT INTO sslcertificates 
			(commonName,CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,
			emailAddress,localityName,password) VALUES
			('{$_POST["commonName"]}','{$_POST["CountryName"]}','{$_POST["stateOrProvinceName"]}','{$_POST["CertificateMaxDays"]}',
			'{$_POST["OrganizationName"]}','{$_POST["OrganizationalUnit"]}','{$_POST["emailAddress"]}'
			,'{$_POST["localityName"]}','{$_POST["password"]}')";
			$generate=true;
	}else{
		$sql="UPDATE sslcertificates SET 
			CountryName='{$_POST["CountryName"]}',
			stateOrProvinceName='{$_POST["stateOrProvinceName"]}',
			CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
			OrganizationName='{$_POST["OrganizationName"]}',
			OrganizationalUnit='{$_POST["OrganizationalUnit"]}',
			emailAddress='{$_POST["emailAddress"]}',
			localityName='{$_POST["localityName"]}',
			password='{$_POST["password"]}'
			WHERE CommonName='$CommonName'";
		
	}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n\n$sql\n";return;}
	if($generate){
		$sock=new sockets();
		$sock->getFrameWork("openssl.php?generate-key=$CommonName");
	}
	
}



function generate_key(){
	$sock=new sockets();
	$sock->getFrameWork("openssl.php?generate-key={$_GET["generate-key"]}");	
	
}
function generate_x509(){
	$sock=new sockets();
	$tpl=new templates();
	$_GET["generate-x509"]=urlencode($_GET["generate-x509"]);
	$datas=base64_decode($sock->getFrameWork("openssl.php?generate-x509={$_GET["generate-x509"]}"));
	$html="
	
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:620px;border:5px solid #8E8E8E;overflow:auto;font-size:12px' 
		id='textToParseCats$t'>$datas</textarea>
			
		";
	
	echo $tpl->_ENGINE_parse_body($html);		
				
}


function tools(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=20;
	$CommonName=$_GET["CommonName"];
	$commonNameEnc=urlencode($CommonName);
	$array["tools-main"]="{tools}";
	
	
	
	$t=time();
		while (list ($num, $ligne) = each ($array) ){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&CommonName=$commonNameEnc\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
		}
	
	
	
		$html=build_artica_tabs($html,'main_certificates_tools_tabs');
	
		echo $html;
	
	
		
	
	
	
}

function tools_main(){
	$t=$_GET["t"];
	$CommonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$commonNameEnc=urlencode($CommonName);
	
	if($CommonName==null){
		echo FATAL_ERROR_SHOW_128("No certificate selected !");
		return;
	}
	
	$tt=time();
	$tr[]="<center style='margin-bottom:10px'>".button("{generate_x509}","Loadjs('openssl.x509.progress.php?generate-x509=$commonNameEnc')",22)."
		<div style='font-size:16px;margin-top:15px'>{generate_x509_text}</div>
		";
	
	$tr[]="<center style='margin-bottom:10px'>".button("{generate_x509_client}",
			"Loadjs('openssl.x509-client.progress.php?generate-x509=$commonNameEnc')",22)."
		<div style='font-size:16px;margin-top:15px'>{generate_x509_client_explain}</div>
		";	


	$tr[]="<center style='margin-bottom:10px'>".button("{generate_pfx}",
			"Loadjs('certificate.center.pfx.progress.php?CommonName=$commonNameEnc')",22)."</center>";
	
	$tr[]="<center style='margin-bottom:10px'>".button("{import_pvk_file}",
			"Loadjs('certificates.center.pvk.upload.php?CommonName=$commonNameEnc')",22)."</center>";	
	
	
	$tr[]="<center style='margin-bottom:10px'>".button("{import_pfx_file}",
			"Loadjs('certificates.center.pfx.upload.php?CommonName=$commonNameEnc')",22)."</center>";
	
	
	
	
	
	$table=@implode("<p>&nbsp;</p>", $tr);
	
	
	$html="
	$table";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function SquidValidate(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$CommonName=$_GET["CommonName"];
	$t=time();
	$html="<center style='font-size:18px'>{please_wait_validate_your_certitificate}...</center>
	<div id='$t'></div>

	<script>
		LoadAjax('$t','$page?SquidValidatePerform=yes&CommonName=$CommonName');
	</script>
			
	";
}
function SquidValidatePerform(){
	$page=CurrentPageName();
	$tpl=new templates();
	$CommonName=$_GET["CommonName"];	
	$sock=new sockets();
	$sock->getFrameWork("openssl.php?squid-validate=yes&CommonName=$CommonName");
	
	
}
function UsedBy($certificate){
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	$EnableNginx=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableNginx"));
	$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
	$LighttpdArticaCertificateName=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/LighttpdArticaCertificateName"));
	$SquidGuardWebSSLCertificate=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebSSLCertificate"));
	$ArticaSplashHotSpotCertificate=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotCertificate"));
	$FINAL=array();
	
	if($certificate==$LighttpdArticaCertificateName){
		$FINAL[]="<li style='font-size:16px'>Artica WebConsole (9000)</li>";
	}
	if($certificate==$SquidGuardWebSSLCertificate){
		$FINAL[]="<li style='font-size:16px'>Web error page service</li>";
	}
	if($certificate==$ArticaSplashHotSpotCertificate){
		$FINAL[]="<li style='font-size:16px'>HotSpot Service</li>";
	}	
	
	$RP=array();
	if($EnableNginx==1){
		$q=new mysql_squid_builder();
		$sql="SELECT reverse_www.servername FROM reverse_sources,reverse_www WHERE reverse_sources.ID = reverse_www.cache_peer_id AND reverse_sources.certificate='$certificate'";
		
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$RP[$ligne["servername"]]="<li style='font-size:16px'>Reverse Proxy: <strong>{$ligne["servername"]}></strong></li>";
			
		}
		$sql="SELECT servername FROM reverse_www WHERE certificate='$certificate'";
		
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$RP[$ligne["servername"]]="<li style='font-size:16px'>Reverse Proxy: <strong>{$ligne["servername"]}</strong></li>";
				
		}
		
		if(count($RP)>0){
			while (list ($num, $ligne) = each ($RP) ){
				$FINAL[]=$ligne;
			}
			
		}

	}
	
	
	if($SQUIDEnable==1){
		$q=new mysql_squid_builder();
		$sql="SELECT PortName,port FROM proxy_ports WHERE UseSSL=1 AND sslcertificate='$certificate'";
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["PortName"]=utf8_encode($ligne["PortName"]);
			$FINAL[]="<li style='font-size:16px'>Web Proxy: <strong>{$ligne["PortName"]} ({$ligne["port"]})</strong></li>";
		
		}
	}
	
	if(count($FINAL)>0){return ".<ul>".@implode("", $FINAL)."</ul>";}
	
}
