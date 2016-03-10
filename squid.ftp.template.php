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
	$xtpl=new template_simple("ERR_DIR_LISTING",$SquidHTTPTemplateLanguage);

	if(!$users->CORP_LICENSE){
		$ENABLED=0;
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";

		$button=null;
	}
	
	if($SquidTemplatesMicrosoft==1){
		$ENABLED=0;
		$error="<p class=text-error>{MOD_TEMPLATE_MICROSOFT_USED}</p>";
		$button=null;
		
	}
	
	
$html="
<div style='font-size:40px;margin-bottom:30px'>{ftp_template}</div>		
$error
	<div style='width:98%' class=form>
	<table style='width:100%'>
<tr>
	<td class=legend style='font-size:24px' width=1% nowrap>{remove_artica_version}:</td>
	<td width=99%>". Field_checkbox_design("SquidFTPTemplateNoVersion-$t",1,$xtpl->SquidFTPTemplateNoVersion)."</td>
</tr>
<tr>
	<td class=legend style='font-size:24px'>{background_color}:</td>
	<td>".Field_ColorPicker("SquidFTPTemplateBackgroundColor-$t",$xtpl->SquidFTPTemplateBackgroundColor,"font-size:24px;width:150px")."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:24px'>{font_family}:</td>
	<td><textarea
	style='width:100%;height:150px;font-size:24px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
	Courier,monospace;background-color:white;color:black' id='SquidFTPTemplateFamily-$t'>$xtpl->SquidFTPTemplateFamily</textarea>
	</td>
	</tr>
	<tr>
	<td class=legend style='font-size:24px'>{font_color}:</td>
	<td>".Field_ColorPicker("SquidFTPTemplateFontColor-$t",$xtpl->SquidFTPTemplateFontColor,"font-size:24px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>Smiley:</td>
		<td>". Field_text("SquidFTPTemplateSmiley-$t",$xtpl->SquidFTPTemplateSmiley,"width:120px;font-size:24px")."</td>
	</tr>		
	<tr>
	<td colspan=2 align='right'>$button</td>
	</tr>
<script>
	var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	Loadjs('squid.templates.single.progress.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TEMPLATE_TITLE','ERR_DIR_LISTING');
	XHR.appendData('lang','$lang');
	XHR.appendData('SquidFTPTemplateFamily',document.getElementById('SquidFTPTemplateFamily-$t').value);
	XHR.appendData('SquidFTPTemplateBackgroundColor',document.getElementById('SquidFTPTemplateBackgroundColor-$t').value);
	XHR.appendData('SquidFTPTemplateFontColor',document.getElementById('SquidFTPTemplateFontColor-$t').value);
	XHR.appendData('SquidFTPTemplateSmiley',document.getElementById('SquidFTPTemplateSmiley-$t').value);
	if(document.getElementById('SquidFTPTemplateNoVersion-$t').checked){XHR.appendData('SquidFTPTemplateNoVersion',1);}else{XHR.appendData('SquidFTPTemplateNoVersion',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function EnableForm$t(){
	var ENABLED=$ENABLED;
	if(ENABLED==1){return;}
	
	document.getElementById('SquidFTPTemplateSmiley-$t').disabled=true;
	document.getElementById('SquidFTPTemplateFamily-$t').disabled=true;
	document.getElementById('SquidFTPTemplateBackgroundColor-$t').disabled=true;
	document.getElementById('SquidFTPTemplateFontColor-$t').disabled=true;
	document.getElementById('SquidFTPTemplateNoVersion-$t').disabled=true;
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
	$sock->SET_INFO("SquidFTPTemplateSmiley", $_POST["SquidFTPTemplateSmiley"]);
	$sock->SET_INFO("SquidFTPTemplateFamily", $_POST["SquidFTPTemplateFamily"]);
	$sock->SET_INFO("SquidFTPTemplateBackgroundColor", $_POST["SquidFTPTemplateBackgroundColor"]);
	$sock->SET_INFO("SquidFTPTemplateFontColor", $_POST["SquidFTPTemplateFontColor"]);

	$xtpl=new template_simple("ERR_DIR_LISTING",$_POST["lang"]);
	while (list ($num, $ligne) = each ($_POST) ){
		$xtpl->$num=$ligne;
	}

	$xtpl->Save();
}