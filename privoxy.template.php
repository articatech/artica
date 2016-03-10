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

if(isset($_POST["TEMPLATE_TITLE"])){TEMPLATE_SAVE();exit;}

TEMPLATE_SETTINGS();

function TEMPLATE_SETTINGS(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$squid=new squidbee();
	$error=null;
	$t=time();
	$button="<hr>".button("{save}", "Save$t()",40);
	$TEMPLATE_TITLE=$_GET["TEMPLATE_TITLE"];
	$SquidTemplatesMicrosoft=intval($sock->GET_INFO("SquidTemplatesMicrosoft"));
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en";}
	$lang=$_GET["lang"];
	$ENABLED=1;
	$xtpl=new template_simple("ERR_ADS_BLOCK",$SquidHTTPTemplateLanguage);

	if(!$users->CORP_LICENSE){
		$ENABLED=0;
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";

		$button=null;
	}
	
$html="
<div style='font-size:40px;margin-bottom:30px'>{error_page}</div>	

$error
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:24px'>{subject}:</td>
		<td>". Field_text("TITLE-$t",utf8_decode($xtpl->TITLE),"font-size:24px;width:90%")."</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:24px;vertical-align:middle'>{content}:</td>
		<td><textarea
		style='width:100%;height:350px;font-size:24px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
		Courier,monospace;background-color:white;color:black' id='BODY-$t'>".utf8_decode($xtpl->BODY)."</textarea>
	</tr>	
<tr>
	<td class=legend style='font-size:24px' width=1% nowrap>{remove_artica_version}:</td>
	<td width=99%>". Field_checkbox_design("SquidADSTemplateNoVersion-$t",1,$xtpl->SquidADSTemplateNoVersion)."</td>
</tr>
	<tr>
		<td class=legend style='font-size:24px'>{background_color}:</td>
		<td>".Field_ColorPicker("SquidADSTemplateBackgroundColor-$t",$xtpl->SquidADSTemplateBackgroundColor,"font-size:24px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{font_family}:</td>
	<td><textarea
	style='width:100%;height:150px;font-size:24px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
	Courier,monospace;background-color:white;color:black' id='SquidADSTemplateFamily-$t'>$xtpl->SquidADSTemplateFamily</textarea>
	</td>
	</tr>
	<tr>
	<td class=legend style='font-size:24px'>{font_color}:</td>
	<td>".Field_ColorPicker("SquidADSTemplateFontColor-$t",$xtpl->SquidADSTemplateFontColor,"font-size:24px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>Smiley:</td>
		<td>". Field_text("SquidADSTemplateSmiley-$t",$xtpl->SquidADSTemplateSmiley,"width:120px;font-size:24px")."</td>
	</tr>		
	<tr>
	<td colspan=2 align='right'>$button</td>
	</tr>
<script>
	var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	Loadjs('privoxy.progress.template.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TEMPLATE_TITLE','ERR_ADS_BLOCK');
	XHR.appendData('lang','$lang');
	XHR.appendData('SquidADSTemplateFamily',document.getElementById('SquidADSTemplateFamily-$t').value);
	XHR.appendData('SquidADSTemplateBackgroundColor',document.getElementById('SquidADSTemplateBackgroundColor-$t').value);
	XHR.appendData('SquidADSTemplateFontColor',document.getElementById('SquidADSTemplateFontColor-$t').value);
	XHR.appendData('SquidADSTemplateSmiley',document.getElementById('SquidADSTemplateSmiley-$t').value);
	XHR.appendData('TITLE',encodeURIComponent(document.getElementById('TITLE-$t').value));
	XHR.appendData('BODY',encodeURIComponent(document.getElementById('BODY-$t').value));
	if(document.getElementById('SquidADSTemplateNoVersion-$t').checked){XHR.appendData('SquidADSTemplateNoVersion',1);}else{XHR.appendData('SquidADSTemplateNoVersion',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function EnableForm$t(){
	var ENABLED=$ENABLED;
	if(ENABLED==1){return;}
	
	document.getElementById('SquidADSTemplateSmiley-$t').disabled=true;
	document.getElementById('SquidADSTemplateFamily-$t').disabled=true;
	document.getElementById('SquidADSTemplateBackgroundColor-$t').disabled=true;
	document.getElementById('SquidADSTemplateFontColor-$t').disabled=true;
	document.getElementById('SquidADSTemplateNoVersion-$t').disabled=true;
}
EnableForm$t();
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}


function TEMPLATE_SAVE(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_POST["TITLE"])){$_POST["TITLE"]=url_decode_special_tool($_POST["TITLE"]);}
	if(isset($_POST["BODY"])){$_POST["BODY"]=url_decode_special_tool($_POST["BODY"]);}
	
	$sock=new sockets();
	$sock->SET_INFO("SquidADSTemplateSmiley", $_POST["SquidADSTemplateSmiley"]);
	$sock->SET_INFO("SquidADSTemplateFamily", $_POST["SquidADSTemplateFamily"]);
	$sock->SET_INFO("SquidADSTemplateBackgroundColor", $_POST["SquidADSTemplateBackgroundColor"]);
	$sock->SET_INFO("SquidADSTemplateFontColor", $_POST["SquidADSTemplateFontColor"]);
	
	
	$sock->SET_INFO("SquidADSTemplateFontColor", $_POST["SquidADSTemplateFontColor"]);
	$sock->SET_INFO("SquidADSTemplateTitle", utf8_encode($_POST["TITLE"]));
	$sock->SET_INFO("SquidADSTemplateBody", utf8_encode($_POST["BODY"]));

	$xtpl=new template_simple("ERR_ADS_BLOCK","en");
	while (list ($num, $ligne) = each ($_POST) ){
		$xtpl->$num=$ligne;
	}

	$xtpl->Save();
}