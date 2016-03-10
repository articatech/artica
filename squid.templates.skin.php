<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.squid.templates-simple.inc');
	include_once('ressources/class.squid.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	
	$user=new usersMenus();
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["Zoom-js"])){ZOOM_JS();exit;}
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["searchTemplates"])){table_items();exit;}
	if(isset($_GET["TEMPLATE_TAB"])){TEMPLATE_TABS();exit;}
	if(isset($_GET["TEMPLATE_SETTINGS"])){TEMPLATE_SETTINGS();exit;}
	if(isset($_POST["TEMPLATE_TITLE"])){TEMPLATE_SAVE();exit;}
	if(isset($_POST["TEMPLATE_DEFAULT"])){TEMPLATE_DEFAULT_SAVE();exit;}
	if(isset($_GET["TEMPLATE_CONTENT"])){TEMPLATE_CONTENT();exit;}
	if(isset($_GET["skin-logo"])){TEMPLATE_LOGO();exit;}
	if(isset($_POST["SquidHTTPTemplateLogoEnable"])){TEMPLATE_DEFAULT_SAVE();exit;}
	if(isset($_GET["help-js"])){helpjs();exit;}
	general_settings();
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["general"]='{general_settings}';
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes&viatabs=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	}
	$html=build_artica_tabs($tab,'main_squid_templates_skins-tabs');
	echo $html;
}

function helpjs(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$F["%a"]="User identity ";
	$F["%B"]="URL with FTP %2f hack ";
	$F["%c"]="Proxy error code ";
	$F["%d"]="seconds elapsed since request received (not yet implemented) ";
	$F["%D"]="proxy-generated error details.\\nMay contain other error page formating codes.\\nCurrently only SSL connection failures are detailed."; 
	$F["%e"]="errno ";
	$F["%E"]="strerror() ";
	$F["%f"]="FTP request line ";
	$F["%F"]="FTP reply line ";
	$F["%g"]="FTP server message ";
	$F["%h"]="cache hostname ";
	$F["%H"]="server host name ";
	$F["%i"]="client IP address ";
	$F["%I"]="server IP address (NP: upper case i) ";
	$F["%l"]="Local site CSS stylesheet. (proxy-3.1 and later) (NP: lower case L) ";
	$F["%L"]="contents of err_html_text config option ";
	$F["%M"]="Request Method ";
	$F["%m"]="Error message returned by external auth helper ";
	$F["%o"]="Message returned by external acl helper ";
	$F["%p"]="URL port";
	$F["%P"]="Protocol ";
	$F["%R"]="Full HTTP Request ";
	$F["%S"]="proxy default signature.";
	$F["%s"]="caching proxy software with version ";
	$F["%t"]="local time ";
	$F["%T"]="UTC ";
	$F["%U"]="URL without password ";
	$F["%u"]="URL with password.";
	$F["%W"]="Extended error page data URL-encoded for mailto links. ";
	$F["%w"]="cachemgr email address ";
	$F["%z"]="DNS server error message ";
	$F["%Z"]="Message generated during the process which failed. May be ASCII-formatted. Use within HTML PRE tags.";
	
	while (list ($key, $value) = each ($F) ){
		$tr[]=$tpl->javascript_parse_text("Token: `$key` $value\\n");
	}
	
	echo "alert('".@implode("\\n", $tr)."')";
	
	
}

function ZOOM_JS(){
	header("content-type: application/x-javascript");
	$TEMPLATE_TITLE=$_GET["Zoom-js"];
	$lang=$_GET["lang"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	echo "YahooWin3(990,'$page?TEMPLATE_TAB=yes&TEMPLATE_TITLE=$TEMPLATE_TITLE&lang=$lang','$TEMPLATE_TITLE')";
}


function TEMPLATE_LOGO(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$error=null;
	$t=time();
	$users=new usersMenus();
	$error=null;
	$t=time();
	$button="<hr>".button("{build_templates}","Loadjs('squid.templates.single.progress.php')",32)."&nbsp;&nbsp;|&nbsp;&nbsp;".button("{save}", "Save$t()",34);
	$SquidHTTPTemplateLogoPath=$sock->GET_INFO("SquidHTTPTemplateLogoPath");
	$SquidHTTPTemplateLogoEnable=intval($sock->GET_INFO("SquidHTTPTemplateLogoEnable"));
	$SquidHTTPTemplateLogoPositionH=$sock->GET_INFO("SquidHTTPTemplateLogoPositionH");
	$SquidHTTPTemplateLogoPositionL=$sock->GET_INFO("SquidHTTPTemplateLogoPositionL");
	$SquidHTTPTemplateLogoPicturemode=$sock->GET_INFO("SquidHTTPTemplateLogoPicturemode");
	$SquidHTTPTemplateLogoPictureAlign=$sock->GET_INFO("SquidHTTPTemplateLogoPictureAlign");
	
	
	if($SquidHTTPTemplateLogoPositionH==null){$SquidHTTPTemplateLogoPositionH="10%";}
	if($SquidHTTPTemplateLogoPositionL==null){$SquidHTTPTemplateLogoPositionL="10%";}
	$SquidHTTPTemplateSmiley=$sock->GET_INFO("SquidHTTPTemplateSmiley");
	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
	
	if(!$users->CORP_LICENSE){
		$SquidHTTPTemplateLogoEnable=0;
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		$button=null;
	}
	
	$picturemode_Hash["float"]="{float}";
	$picturemode_Hash["absolute"]="{absolute}";
	$picturemode_Hash["fixed"]="{fixed}";
	
	$picturealign_Hash[null]="{default}";
	$picturealign_Hash["left"]="{left}";
	$picturealign_Hash["right"]="{right}";
	$picturealign_Hash["center"]="{center}";
	
	
	if($SquidHTTPTemplateLogoPath<>null){$logoPic="<center style='margin:20px'><img src='img/upload/".basename($SquidHTTPTemplateLogoPath)."'></center>";}
	
	$html="
		$error	
		<div style='width:98%' class=form>
	$logoPic
	<center style='margin:10px'>". button("{upload_a_picture}","Loadjs('squid.templates.skin.uploadlogo.php?zmd5={$_GET["zmd5"]}')",32)."</center>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>Smiley:</td>
		<td>". Field_text("SquidHTTPTemplateSmiley-$t",$SquidHTTPTemplateSmiley,"width:120px;font-size:26px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:26px'>{display_logo}:</td>
		<td>". Field_checkbox_design("SquidHTTPTemplateLogoEnable-$t",1,$SquidHTTPTemplateLogoEnable,"Check$t()")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:26px'>{position} TOP:</td>
		<td>". Field_text("SquidHTTPTemplateLogoPositionH-$t",$SquidHTTPTemplateLogoPositionH,"font-size:26px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{position} LEFT:</td>
		<td>". Field_text("SquidHTTPTemplateLogoPositionL-$t",$SquidHTTPTemplateLogoPositionL,"font-size:26px;width:150px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{type}:</td>
		<td>". Field_array_Hash($picturemode_Hash, "picturemode-$t",$SquidHTTPTemplateLogoPicturemode,"style:font-size:26px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{align}:</td>
		<td>". Field_array_Hash($picturealign_Hash, "picturealign-$t",$SquidHTTPTemplateLogoPictureAlign,"style:font-size:26px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right' style='font-size:40px'>$button</td>
	</tr>
	</table>
</div>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	RefreshTab('main_squid_templates-tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var SquidHTTPTemplateLogoEnable=0;
	if(document.getElementById('SquidHTTPTemplateLogoEnable-$t').checked){SquidHTTPTemplateLogoEnable=1;}
	XHR.appendData('SquidHTTPTemplateLogoEnable',SquidHTTPTemplateLogoEnable);
	XHR.appendData('SquidHTTPTemplateSmiley',document.getElementById('SquidHTTPTemplateSmiley-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPositionH',document.getElementById('SquidHTTPTemplateLogoPositionH-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPositionL',document.getElementById('SquidHTTPTemplateLogoPositionL-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPicturemode',document.getElementById('picturemode-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPictureAlign',document.getElementById('picturealign-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
function Check$t(){
	document.getElementById('SquidHTTPTemplateSmiley-$t').disabled=true;
	document.getElementById('SquidHTTPTemplateLogoPositionH-$t').disabled=true;
	document.getElementById('SquidHTTPTemplateLogoPositionL-$t').disabled=true;
	document.getElementById('picturemode-$t').disabled=true;
	document.getElementById('picturealign-$t').disabled=true;
	
	if(document.getElementById('SquidHTTPTemplateLogoEnable-$t').checked){
		document.getElementById('SquidHTTPTemplateSmiley-$t').disabled=false;
		document.getElementById('SquidHTTPTemplateLogoPositionH-$t').disabled=false;
		document.getElementById('SquidHTTPTemplateLogoPositionL-$t').disabled=false;
		document.getElementById('picturemode-$t').disabled=false;
		document.getElementById('picturealign-$t').disabled=false;	
	}
}

Check$t();


</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);

	
	
}




function TEMPLATE_CONTENT(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$error=null;
	$t=time();
	$button="<hr>".button("{apply}", "Save$t()",22);
	$TEMPLATE_TITLE=$_GET["TEMPLATE_TITLE"];
	$lang=$_GET["lang"];

	$xtpl=new template_simple($_GET["TEMPLATE_TITLE"],$_GET["lang"]);
	
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		$button=null;
	}

	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{subject}:</td>
		<td>". Field_text("TITLE-$t",utf8_decode($xtpl->TITLE),"font-size:16px")."</td>	
	</tr>
	<tr><td colspan=2 align='right'>". button("{help}", "Loadjs('$page?help-js=yes')")."</td></tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:middle'>{content}:</td>
		<td><textarea
		style='width:100%;height:350px;font-size:16px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
		Courier,monospace;background-color:white;color:black' id='BODY-$t'>".utf8_decode($xtpl->BODY)."</textarea>
	</tr>	
<tr>
	<td colspan=2 align='right'>$button</td>
</tr>
</table>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	RefreshTab('$TEMPLATE_TITLE-$lang-tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TEMPLATE_TITLE','$TEMPLATE_TITLE');
	XHR.appendData('lang','$lang');
	XHR.appendData('TITLE',encodeURIComponent(document.getElementById('TITLE-$t').value));
	XHR.appendData('BODY',encodeURIComponent(document.getElementById('BODY-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function TEMPLATE_SETTINGS(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$squid=new squidbee();
	$error=null;
	$t=time();
	$button="<hr>".button("{apply}","Loadjs('squid.templates.single.progress.php')",22)."&nbsp;&nbsp;|&nbsp;&nbsp;".button("{save}", "Save$t()",22);
	$TEMPLATE_TITLE=$_GET["TEMPLATE_TITLE"];
	
	$lang=$_GET["lang"];
	$ENABLED=1;
	$xtpl=new template_simple($_GET["TEMPLATE_TITLE"],$_GET["lang"]);
	
	if(!$users->CORP_LICENSE){
		$ENABLED=0;
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		
		$button=null;
	}
	
	
	
	$html="$error
<div style='width:98%' class=form>
<table style='width:100%'>




<tr>
	<td class=legend style='font-size:16px' width=1% nowrap>{remove_artica_version}:</td>
	<td width=99%>". Field_checkbox_design("SquidHTTPTemplateNoVersion-$t",1,$xtpl->SquidHTTPTemplateNoVersion)."</td>
</tr>
<tr>
	<td class=legend style='font-size:16px'>{background_color}:</td>
	<td>".Field_ColorPicker("SquidHTTPTemplateBackgroundColor-$t",$xtpl->SquidHTTPTemplateBackgroundColor,"font-size:16px;width:150px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:16px'>{font_family}:</td>
	<td><textarea
		style='width:100%;height:150px;font-size:16px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
		Courier,monospace;background-color:white;color:black' id='SquidHTTPTemplateFamily-$t'>$xtpl->SquidHTTPTemplateFamily</textarea>
	</td>
</tr>
<tr>
	<td class=legend style='font-size:16px'>{font_color}:</td>
	<td>".Field_ColorPicker("SquidHTTPTemplateFontColor-$t",$xtpl->SquidHTTPTemplateFontColor,"font-size:16px;width:150px")."</td>
</tr>
<tr>
	<td colspan=2 align='right'>$button</td>
</tr>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	RefreshTab('$TEMPLATE_TITLE-$lang-tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TEMPLATE_TITLE','$TEMPLATE_TITLE');
	XHR.appendData('lang','$lang');
	XHR.appendData('SquidHTTPTemplateFamily',document.getElementById('SquidHTTPTemplateFamily-$t').value);
	XHR.appendData('SquidHTTPTemplateBackgroundColor',document.getElementById('SquidHTTPTemplateBackgroundColor-$t').value);
	XHR.appendData('SquidHTTPTemplateFontColor',document.getElementById('SquidHTTPTemplateFontColor-$t').value);
	if(document.getElementById('SquidHTTPTemplateNoVersion-$t').checked){XHR.appendData('SquidHTTPTemplateNoVersion',1);}else{XHR.appendData('SquidHTTPTemplateNoVersion',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function EnableForm$t(){
	var ENABLED=$ENABLED;
	if(ENABLED==1){return;}
	document.getElementById('SquidHTTPTemplateFamily-$t').disabled=true;
	document.getElementById('SquidHTTPTemplateBackgroundColor-$t').disabled=true;
	document.getElementById('SquidHTTPTemplateFontColor-$t').disabled=true;
	document.getElementById('SquidHTTPTemplateNoVersion-$t').disabled=true;
	
}
EnableForm$t();
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function TEMPLATE_DEFAULT_SAVE(){
	$sock=new sockets();
	if(isset( $_POST["cache_mgr_user"])){$sock->SET_INFO("cache_mgr_user", $_POST["cache_mgr_user"]);}
	
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	
}

function TEMPLATE_SAVE(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_POST["TITLE"])){$_POST["TITLE"]=url_decode_special_tool($_POST["TITLE"]);}
	if(isset($_POST["BODY"])){$_POST["BODY"]=url_decode_special_tool($_POST["BODY"]);}
	
	$xtpl=new template_simple($_POST["TEMPLATE_TITLE"],$_POST["lang"]);
	while (list ($num, $ligne) = each ($_POST) ){
		$xtpl->$num=$ligne;
	}
	
	$xtpl->Save();
}
	
	
function TEMPLATE_TABS(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$TEMPLATE_TITLE=$_GET["TEMPLATE_TITLE"];
	$lang=$_GET["lang"];
	
	
	$array["TEMPLATE_SETTINGS"]='{settings}';
	$array["TEMPLATE_CONTENT"]='{content}';
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
		
		$tab[]="<li><a href=\"$page?$num=yes&TEMPLATE_TITLE=$TEMPLATE_TITLE&lang=$lang\">
				<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			
	}
	$html=build_artica_tabs($tab,"$TEMPLATE_TITLE-$lang-tabs")."<script>LeftDesign('squid-templates-256-opac-20.png');</script>";
	echo $html;	
	
	
}

function general_settings(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$squid=new squidbee();
	$error=null;
	
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
	$LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
	
	
	$SquidHTTPTemplateNoVersion=0;
	$SquidHTTPTemplateBackgroundColor="#8c1919";
	$SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
	$SquidHTTPTemplateFontColor="#FFFFFF";
	
	$SquidHTTPTemplateEnablePostmaster=1;
	$SquidHTTPTemplateNoVersion=intval($sock->GET_INFO("SquidHTTPTemplateNoVersion"));
	$SquidHTTPTemplateBackgroundColor=$sock->GET_INFO("SquidHTTPTemplateBackgroundColor");
	$SquidHTTPTemplateBackgroundColorBLK=$sock->GET_INFO("SquidHTTPTemplateBackgroundColorBLK");
	$SquidHTTPTemplateBackgroundColorBLKBT=$sock->GET_INFO("SquidHTTPTemplateBackgroundColorBLKBT");
	$SquidHTTPTemplateDisableHostname=intval($sock->GET_INFO("SquidHTTPTemplateDisableHostname"));
	$SquidHTTPTemplateEnablePostmaster=$sock->GET_INFO("SquidHTTPTemplateEnablePostmaster");
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	$SquidHTTPTemplateNoProxyVersion=intval($sock->GET_INFO("SquidHTTPTemplateNoProxyVersion"));
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	$SquidHTTPTemplateFontColor=$sock->GET_INFO("SquidHTTPTemplateFontColor");
	$SquidHTTPTemplateFamily=$sock->GET_INFO("SquidHTTPTemplateFamily");
	
	if($SquidHTTPTemplateFontColor==null){$SquidHTTPTemplateFontColor="#FFFFFF";}
	if($SquidHTTPTemplateBackgroundColor==null){$SquidHTTPTemplateBackgroundColor="#8c1919";}
	if($SquidHTTPTemplateBackgroundColorBLK==null){$SquidHTTPTemplateBackgroundColorBLK="#0300AC";}
	if($SquidHTTPTemplateBackgroundColorBLKBT==null){$SquidHTTPTemplateBackgroundColorBLKBT="#625FFD";}
	if($SquidHTTPTemplateFamily==null){$SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
	if(!is_numeric($SquidHTTPTemplateEnablePostmaster)){$SquidHTTPTemplateEnablePostmaster=1;}
	$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
	
	$t=time();
	$button="<hr>".button("{apply}", "Save$t()",32);
	
	$LICENSE=1;
	
if(!$users->CORP_LICENSE){
	$LICENSE=0;
	$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
	$cache_mgr_user=$LicenseInfos["EMAIL"];
	$button=null;
}

if($cache_mgr_user==null){$cache_mgr_user=$LicenseInfos["EMAIL"];}



$languages=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
while (list ($lang, $line) = each ($languages)){
	if($lang=="templates"){continue;}
	$flang[$lang]="$lang";
}

$xtpl=new template_simple();
reset($xtpl->arrayxLangs);

while (list ($lang, $xarr) = each ($xtpl->arrayxLangs)){
		while (list ($index, $z) = each ($xarr)){
		unset($flang[$z]);
	}
}
$flang[null]="{not_defined}";
$flang[$SquidHTTPTemplateLanguage]=$SquidHTTPTemplateLanguage;
unset($flang["templates"]);
ksort($flang);
	
$html="$error
<div class=explain style='font-size:22px'>{simple_template_gene_explain}</div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:22px' width=1% nowrap>{postmaster}:</td>
	<td width=99%>". Field_text("cache_mgr_user-$t",$cache_mgr_user,"font-size:22px;width:80%")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' width=1% nowrap>{language}:</td>
	<td width=99%>". Field_array_Hash($flang,"SquidHTTPTemplateLanguage-$t",$SquidHTTPTemplateLanguage,"style:font-size:22px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' width=1% nowrap>{remove_artica_version}:</td>
	<td width=99%>". Field_checkbox_design("SquidHTTPTemplateNoVersion-$t",1,$SquidHTTPTemplateNoVersion)."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' width=1% nowrap>{remove_proxy_version}:</td>
	<td width=99%>". Field_checkbox_design("SquidHTTPTemplateNoProxyVersion-$t",1,$SquidHTTPTemplateNoProxyVersion)."</td>
</tr>			
<tr>
	<td class=legend style='font-size:22px'>{background_color}:</td>
	<td>".Field_ColorPicker("SquidHTTPTemplateBackgroundColor-$t",$SquidHTTPTemplateBackgroundColor,"font-size:22px;width:150px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{font_family}:</td>
	<td><textarea
		style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
		Courier,monospace;background-color:white;color:black' id='SquidHTTPTemplateFamily-$t'>$SquidHTTPTemplateFamily</textarea>
	</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{font_color}:</td>
	<td>".Field_ColorPicker("SquidHTTPTemplateFontColor-$t",$SquidHTTPTemplateFontColor,"font-size:22px;width:150px")."</td>
</tr>
	
	
<tr>
<tr>
	<td colspan=2 align='right' style='font-size:40px'>$button</td>
</tr>
<script>
	var xSave$t=function(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_squidguardweb_error_pages');
		Loadjs('squid.templates.single.progress.php');
	}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TEMPLATE_DEFAULT','1');
	XHR.appendData('cache_mgr_user',document.getElementById('cache_mgr_user-$t').value);
	XHR.appendData('SquidHTTPTemplateLanguage',document.getElementById('SquidHTTPTemplateLanguage-$t').value);
	XHR.appendData('SquidHTTPTemplateFamily',document.getElementById('SquidHTTPTemplateFamily-$t').value);
	XHR.appendData('SquidHTTPTemplateBackgroundColor',document.getElementById('SquidHTTPTemplateBackgroundColor-$t').value);
	XHR.appendData('SquidHTTPTemplateFontColor',document.getElementById('SquidHTTPTemplateFontColor-$t').value);
	if(document.getElementById('SquidHTTPTemplateNoVersion-$t').checked){XHR.appendData('SquidHTTPTemplateNoVersion',1);}else{XHR.appendData('SquidHTTPTemplateNoVersion',0);}
	if(document.getElementById('SquidHTTPTemplateNoProxyVersion-$t').checked){XHR.appendData('SquidHTTPTemplateNoProxyVersion',1);}else{XHR.appendData('SquidHTTPTemplateNoProxyVersion',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}


function CheckForm$t(){
	var lic=$LICENSE;
	if(lic==0){
		document.getElementById('cache_mgr_user-$t').disabled=true;
		document.getElementById('SquidHTTPTemplateLanguage-$t').disabled=true;
		document.getElementById('SquidHTTPTemplateFamily-$t').disabled=true;
		document.getElementById('SquidHTTPTemplateBackgroundColor-$t').disabled=true;
		document.getElementById('SquidHTTPTemplateFontColor-$t').disabled=true;
		document.getElementById('SquidHTTPTemplateNoVersion-$t').disabled=true;
	}
}
CheckForm$t();
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function table(){
	$error=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	
	$squid_choose_template=$tpl->_ENGINE_parse_body("{squid_choose_template}");
	
	
	
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$template_name=$tpl->_ENGINE_parse_body("{template_name}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$lang=$tpl->_ENGINE_parse_body("{language}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$title=$tpl->_ENGINE_parse_body("{subject}");
	$new_template=$tpl->javascript_parse_text("{new_template}");
	$ask_remove_template=$tpl->javascript_parse_text("{ask_remove_template}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$replace=$tpl->_ENGINE_parse_body("{replace}");
	$squid_tpl_import_default=$tpl->javascript_parse_text("{squid_tpl_import_default}");
	$defaults=$tpl->javascript_parse_text("{add_defaults}");
	$t=time();
	$backToDefault=$tpl->_ENGINE_parse_body("{backToDefault}");
	$ERROR_SQUID_REBUILD_TPLS=$tpl->javascript_parse_text("{ERROR_SQUID_REBUILD_TPLS}");
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("squidtpls")==0){$sock=new sockets();$sock->getFrameWork("squid.php?build-default-tpls=yes");}
	$back="		{name: '$backToDefault', bclass: 'Reconf', onpress : RebuidSquidTplDefault},";
	$template_title_size=325;
	if($_GET["choose-acl"]>0){
		$chooseacl_column="{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'center'},";
		$template_title_size=283;
	}
	
	
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{MOD_TEMPLATE_ERROR_LICENSE}")."</p>";
	}
	

		$row1=75;
		$row2=334;
		$rows3=630;
		$rows4=149;
		$rows5=57;

	
	$html="
	$error
	<div style='margin-left:-10px'>
	<table class='SquidTemplateErrorsTable' style='display: none' id='SquidTemplateErrorsTable' style='width:99%'></table>
	</div>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#SquidTemplateErrorsTable').flexigrid({
	url: '$page?searchTemplates=yes&lang=$SquidHTTPTemplateLanguage',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:20px>$template_name</span>', name : 'template_name', width :513, sortable : false, align: 'left'},
		{display: '<span style=font-size:20px>$title</span>', name : 'template_title', width : 768, sortable : false, align: 'left'},
	],
	
	
	
	searchitems : [
	{display: '$template_name', name : 'template_name'},
	{display: '$title', name : 'template_title'},
	
	],
	sortname: 'template_time',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$squid_choose_template ($SquidHTTPTemplateLanguage)</span>',
	useRp: true,
	rp: 250,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
	});
	
	function help$t(){
	s_PopUpFull('http://proxy-appliance.org/index.php?cID=385','1024','900');
	}
	
	function Replace$t(){
	Loadjs('$page?replace-js=yes');
	}
	
	function Defaults$t(){
	if(!confirm('$squid_tpl_import_default')){return;}
	Loadjs('$page?import-default-js=yes&t=$t');
	
	}
	
	function SearchLanguage(){
	YahooWin5(350,'$page?Select-lang=yes&choose-acl={$_GET["choose-acl"]}&choose-generic={$_GET["choose-generic"]}','$lang');
	}
	
	function NewTemplateNew(){
	YahooWin5('815','$page?new-template=yes&t=$t','$new_template');
	}
	
	function NewTemplate(templateid){
	var title='$new_template';
	if(!templateid){templateid='';}else{title=templateid;}
	if(templateid.length<20){
	title='$new_template';
	templateid='';
	}
	
	YahooWin5('700','$page?new-template=yes&t=$t&templateid='+templateid,title);
	}
	
	var x_RebuidSquidTplDefault=function(obj){
	$('#SquidTemplateErrorsTable').flexReload();
	}
	
	var x_TemplateDelete= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#row'+mem$t).remove();
	
	}
	
	function TemplateDelete(zmd5){
	if(confirm('$ask_remove_template')){
	mem$t=zmd5;
	var XHR = new XHRConnection();
	XHR.appendData('tpl-remove',zmd5);
	XHR.sendAndLoad('$page', 'POST',x_TemplateDelete);
	 
	}
	}
	
	var x_ChooseAclsTplSquid=function (obj) {
	var res=obj.responseText;
	if (res.length>3){
	if(document.getElementById('acltplTxt')){
	document.getElementById('acltplTxt').innerHTML=res;
	}
		
	}
	
	
	}
	
	function ChooseGenericTemplate(tmplname){
	if(document.getElementById('{$_GET["choose-generic"]}')){
	document.getElementById('{$_GET["choose-generic"]}').value=tmplname;
	}
	if(document.getElementById('{$_GET["divid"]}')){
	document.getElementById('{$_GET["divid"]}').innerHTML=tmplname;
	}
	{$_GET["yahoo"]}Hide();
	
	
	}
	
	
	function ChooseAclsTplSquid(acl,zmd5){
	var XHR = new XHRConnection();
	XHR.appendData('ChooseAclsTplSquid',acl);
	XHR.appendData('zmd5',zmd5);
	XHR.sendAndLoad('$page', 'POST',x_ChooseAclsTplSquid);
	
	}
	
	function RebuidSquidTplDefault(){
	if(confirm('$ERROR_SQUID_REBUILD_TPLS')){
	var XHR = new XHRConnection();
	XHR.appendData('RebuidSquidTplDefault','yes');
	XHR.sendAndLoad('$page', 'POST',x_RebuidSquidTplDefault);
	}
	}
</script>
";
echo $html;
}
function table_items(){
	
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$lang=$_GET["lang"];
	$sock=new sockets();
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$data = array();
	$data['rows'] = array();
	
	$delete_icon="delete-24.png";
	$fontsize=22;
	if(isset($_GET["viatabs"])){$fontsize=18;$delete_icon="delete-32.png";}
	$span="<span style='font-size:{$fontsize}px'>";
	$searchstring=string_to_flexregex();
	
	$templates=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
	$templates[$lang]["ERR_BLACKLISTED_SITE"]["TITLE"]="ERROR: Blacklisted Website";
	$templates[$lang]["ERR_PARANOID"]["TITLE"]="ERROR: Banned access";
	
	
	$TemplateConfig=unserialize($sock->GET_INFO("TemplateConfig"));
		
	
	$MAIN=$templates[$lang];
	$data['page'] = $page;
	$data['total'] = count($MAIN);
	
	
	while (list ($TEMPLATE_TITLE, $subarray) = each ($MAIN)){
		$zmd5=md5(serialize($subarray));
		$subtitle2=null;
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", $TEMPLATE_TITLE)){continue;}
		}
		
		$title=utf8_decode($TemplateConfig[$TEMPLATE_TITLE][$lang]["TITLE"]);
		if($title==null){ $title=$subarray["TITLE"]; }
		$xtpl=new template_simple($TEMPLATE_TITLE,$lang);
		$subtitle=utf8_decode($xtpl->TITLE);
		if($subtitle<>$title){
		$subtitle2="<br><i style='font-size:16px;color:#46a346;font-weight:bold'>&laquo;&nbsp;$subtitle&nbsp;&raquo;&nbsp;<i>";
		}
		
		
		$linkZoom="<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$Mypage?Zoom-js=$TEMPLATE_TITLE&lang=$lang');\" style='font-size:{$fontsize}px;text-decoration:underline'>";
		$cell=array();
		$cell[]="$span$linkZoom$TEMPLATE_TITLE</a></span>";
		$cell[]="$span$linkZoom$title</a></span>$subtitle2";
		
		
		$data['rows'][] = array(
		'id' => $zmd5,
		'cell' =>$cell
		);
	}
	
	
	echo json_encode($data);
		
	
}

	
