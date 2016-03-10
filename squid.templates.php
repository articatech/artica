<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');

	
	
	$user=new usersMenus();
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["SquidTemplateSimple"])){SquidTemplateSimple_save();exit;}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["template-settings-js"])){template_settings_js();exit;}
	if(isset($_POST["newtemplate"])){TEMPLATE_ADD_SAVE();exit;}
	if(isset($_POST["template_body"])){ZOOM_SAVE();exit;}
	if(isset($_POST["TEMPLATE_DATA"])){TEMPLATE_SAVE();}
	if(isset($_POST["template_header"])){TEMPLATE_HEADER_SAVE();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup-table"])){popup_table();exit;}
	if(isset($_GET["view-table"])){view_table();exit;}
	if(isset($_GET["TEMP"])){FormTemplate();exit;}
	if(isset($_GET["Zoom-js"])){ZOOM_JS();exit;}
	if(isset($_GET["Zoom-popup"])){ZOOM_POPUP();exit;}
	if(isset($_GET["Select-lang"])){select_lang();exit;}
	if(isset($_GET["headers-js"])){HEADER_JS();exit;}
	if(isset($_GET["Headers-popup"])){HEADERS_POPUP();exit;}
	if(isset($_GET["preview"])){ZOOM_PREVIEW();exit;}
	if(isset($_POST["tpl-remove"])){TEMPLATE_REMOVE();exit;}
	if(isset($_GET["new-template"])){TEMPLATE_ADD();exit;}
	if(isset($_POST["ChooseAclsTplSquid"])){TEMPLATE_AFFECT();exit;}
	if(isset($_GET["replace-js"])){REPLACE_JS();exit;}
	if(isset($_GET["replace-popup"])){REPLACE_POPUP();exit;}
	if(isset($_POST["RebuidSquidTplDefault"])){RebuidSquidTplDefault();exit;}
	if(isset($_POST["replace-from"])){REPLACE_PERFORM();exit;}
	if(isset($_GET["import-default-js"])){IMPORT_DEFAULT_JS();exit;}
	if(isset($_POST["ImportDefault"])){DefaultTemplatesInMysql();exit;}
	
js();

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	
	
	
	$array["status"]='{status}';
	if($SquidTemplateSimple==1){
	$array["skin-gene"]='{default_settings}';
	$array["skin-popup"]='{squid_templates_error}';
	$array["skin-logo"]='{logo}';
	$array["ftp_template"]='{ftp_template}';
	}else{
		$array["popup"]='{squid_templates_error}';
	}
	//$array["ocsagent"]="{APP_OCSI_LNX_CLIENT}";
	$fontsize=26;
	while (list ($num, $ligne) = each ($array) ){
		if($num=="skin-gene"){
			$tab[]="<li><a href=\"squid.templates.skin.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="skin-popup"){
			$tab[]="<li><a href=\"squid.templates.skin.php?table=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="skin-logo"){
			$tab[]="<li><a href=\"squid.templates.skin.php?skin-logo=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="ftp_template"){
			$tab[]="<li><a href=\"squid.ftp.template.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
			
				
		$tab[]="<li><a href=\"$page?$num=yes&viatabs=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	$html=build_artica_tabs($tab,'main_squid_templates-tabs',1490)."<script>LeftDesign('squid-templates-256-opac-20.png');</script>";
	echo $html;
	
	
}

function ZOOM_JS(){
	header("content-type: application/x-javascript");
	$zmd5=$_GET["Zoom-js"];
	$page=CurrentPageName();
	$title=utf8_encode(base64_decode($_GET["subject"]));

	
	$title=str_replace("'","`", $title);
	$html="
	
	YahooWin6(820,'$page?Zoom-popup=yes&zmd5=$zmd5','$title')";
	echo $html;	
	
}
function HEADER_JS(){
	header("content-type: application/x-javascript");
	$zmd5=$_GET["zmd5"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=utf8_encode(base64_decode($_GET["subject"]));
	$title=str_replace("'","`", $title);
	$headers_text=$tpl->_ENGINE_parse_body("{headers}");
	$html="YahooWinBrowse(820,'$page?Headers-popup=yes&zmd5=$zmd5','$title:$headers_text')";
	echo $html;	
	
}
function IMPORT_DEFAULT_JS(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="
	var xImportDefault$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#SquidTemplateErrorsTable').flexReload();
		
    }	    
	
	 function ImportDefault$t(){
      	var XHR = new XHRConnection();
      	XHR.appendData('ImportDefault','yes');
      	XHR.sendAndLoad('$page', 'POST',xImportDefault$t);          
      }			
			
	ImportDefault$t();";
	echo $html;
}
function DefaultTemplatesInMysql(){

}

function status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	$SquidTemplatesMicrosoft=$sock->GET_INFO("SquidTemplatesMicrosoft");
	$t=time();
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{proxy_error_pages}</div>	

	<div style='font-size:20px' class=explain>{proxy_error_pages_explain}</div>
	
	<div style='width:98%' class=form>
	". Paragraphe_switch_img("{use_simple_template_mode}", 
			"{use_simple_template_mode_squid_explain}",
			"SquidTemplateSimple",$SquidTemplateSimple,null,1024)."
					
	". Paragraphe_switch_img("{SquidTemplatesMicrosoft}", 
			"{SquidTemplatesMicrosoft_explain}",
			"SquidTemplatesMicrosoft",$SquidTemplatesMicrosoft,null,1024)."				
					
					
	
	<div style='width:100%;text-align:right;margin-top:20px'>". button("{apply}","Save$t()",42)."		
					
	</div>	
<script>	
var xSave$t=function(obj){
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_squid_templates-tabs');
	Loadjs('squid.templates.single.progress.php');
}	    
	
function Save$t(){
	var XHR = new XHRConnection();
    XHR.appendData('SquidTemplateSimple',document.getElementById('SquidTemplateSimple').value);
    XHR.appendData('SquidTemplatesMicrosoft',document.getElementById('SquidTemplatesMicrosoft').value);
    XHR.sendAndLoad('$page', 'POST',xSave$t);          
}

</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function SquidTemplateSimple_save(){
	$users=new usersMenus();
	$sock=new sockets();
	if($_POST["SquidTemplateSimple"]==0){
		if(!$users->CORP_LICENSE){
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}",1);
			$sock->SET_INFO("SquidTemplateSimple", 1);
		}
	}
	
	$sock->SET_INFO("SquidTemplatesMicrosoft", $_POST["SquidTemplatesMicrosoft"]);
	$sock->SET_INFO("SquidTemplateSimple", $_POST["SquidTemplateSimple"]);
	$sock->getFrameWork("squid2.php?build-templates-background=yes");
}


function REPLACE_JS(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{replace}");
	$html="YahooWin5(700,'$page?replace-popup=yes','$title')";
	echo $html;	
}
function REPLACE_POPUP(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$html="<div class=explain style='font-size:16px'>{SQUID_TEMPLATE_REPLACE_EXPLAIN}</div>
	<div id='$t'></div>
	<div style='font-size:22px'>{from}:</div>
	<textarea style='width:95%;height:100px;font-family:monospace;
	overflow:auto;font-size:20px !important;border:4px solid #CCCCCC;background-color:transparent' id='from-$t'></textarea>
	
	<hr>
	<div style='font-size:22px'>{to}:</div>
	<textarea style='width:95%;height:100px;font-family:monospace;
	overflow:auto;font-size:20px !important;border:4px solid #CCCCCC;background-color:transparent' id='to-$t'></textarea>
	<div style='width:100%;text-align:right'>	
	
	
	
	<hr>". button("{apply}","Replace$t()",16)."</div>
	<script>
	var xReplace$t=function(obj){
		var results=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(results.length>3){alert(results);}
		
    }	    
	
	 function Replace$t(){
      	var XHR = new XHRConnection();
      	XHR.appendData('replace-from',document.getElementById('from-$t').value);
      	XHR.appendData('replace-to',document.getElementById('to-$t').value);
      	AnimateDiv('$t');
      	XHR.sendAndLoad('$page', 'POST',xReplace$t);          
      }	
     </script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function REPLACE_PERFORM(){
	$stringfrom=trim(stripslashes($_POST["replace-from"]));
	$stringto=trim(stripslashes($_POST["replace-to"]));
	$q=new mysql_squid_builder();
	if($stringfrom==null){echo "From: NULL -> abort\n";}

	$sql="SELECT * FROM squidtpls";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "Fatal,$q->mysql_error\n";return;}
	$i=0;
	while ($ligne = mysql_fetch_assoc($results)) {	
		$zmd5=$ligne["zmd5"];
		$ligne["template_header"]=stripslashes($ligne["template_header"]);
		$ligne["template_title"]=stripslashes($ligne["template_title"]);
		$ligne["template_body"]=stripslashes($ligne["template_body"]);
		
		$ligne["template_body"]=str_replace($stringfrom, $stringto, $ligne["template_body"]);
		$ligne["template_title"]=str_replace($stringfrom, $stringto, $ligne["template_title"]);
		$ligne["template_header"]=str_replace($stringfrom, $stringto, $ligne["template_header"]);
		
		$ligne["template_header"]=mysql_escape_string2($ligne["template_header"]);
		$ligne["template_title"]=mysql_escape_string2($ligne["template_title"]);
		$ligne["template_body"]=mysql_escape_string2($ligne["template_body"]);
		
		$q->QUERY_SQL("UPDATE squidtpls 
					SET template_header='{$ligne["template_header"]}',
					template_title='{$ligne["template_title"]}',
					template_body='{$ligne["template_body"]}'
					WHERE zmd5='$zmd5'");
		
		if(!$q->ok){
				echo $q->mysql_error."\nAfter $i modification(s)";
				if($i>0){
					$sock=new sockets();
					$sock->getFrameWork("cmd.php?squid-templates=yes");
				}
				break;
		}
		$i++;
					
		
		
	}
	$tpl=new templates();
	echo $tpl->javascript_parse_text("\"$stringfrom\" {to} \"$stringto\"
	{success} $i {templates}",1);
	if($i>0){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?squid-templates=yes");
	}
	
}

function js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{squid_templates_error}");
	if(!is_numeric($_GET["choose-acl"])){$_GET["choose-acl"]=0;}
	$yahoo="YahooWin4";
	$YooKill="YahooWin4Hide();";
	if($_GET["choose-acl"]>0){
		$yahoo="YahooWinBrowse";
		$YooKill="YahooWinBrowseHide();";
	}
	if(isset($_GET["choose-generic"])){
		$yahoo="YahooWinBrowse";
		$YooKill="YahooWinBrowseHide();";
	}
	$html="
	if(document.getElementById('SquidTemplateErrorsTable')){
		$('#SquidTemplateErrorsTable').remove();
	}
	$YooKill
	$yahoo('774','$page?popup=yes&choose-acl={$_GET["choose-acl"]}&choose-generic={$_GET["choose-generic"]}&divid={$_GET["divid"]}&yahoo=$yahoo','$title');";
	echo $html;
	}
	
function ZOOM_PREVIEW(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_body,template_title,template_header FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();
	$newheader=trim(stripslashes($ligne["template_header"]));
	$ligne["template_body"]=stripslashes($ligne["template_body"]);
	if($newheader==null){$newheader=@file_get_contents("ressources/databases/squid.default.header.db");}
	$newheader=str_replace("{TITLE}", $ligne["template_title"], $newheader);
	$templateDatas="$newheader{$ligne["template_body"]}</body></html>";
	echo $templateDatas;
}
	
function HEADERS_POPUP(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_header FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){	$errorsql="<div style='font-size:14px;font-weight:bold;color:#B10000;margin:10px'>{$_GET["zmd5"]}::$q->mysql_error</div>";}
	$page=CurrentPageName();
	$ligne["template_header"]=stripslashes($ligne["template_header"]);
	$tpl=new templates();
	if($ligne["template_header"]==null){
		$error="<div style='font-size:14px;font-weight:bold;color:#B10000;margin:10px'>{$_GET["zmd5"]}::{default_value_is_used}</div>";
		$ligne["template_header"]=@file_get_contents("ressources/databases/squid.default.header.db");}
	$html="
	$error$errorsql
	<textarea style='width:100%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' id='{$_GET["zmd5"]}-header-$t'>
		{$ligne["template_header"]}
	</textarea>
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveTemplateForm$t()",16)."</div>
	</div>
	
	<script>
	var x_SaveTemplateForm$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWinBrowseHide();
    }	    
	
	 function SaveTemplateForm$t(){
      	var XHR = new XHRConnection();
      	XHR.appendData('template_header',document.getElementById('{$_GET["zmd5"]}-header-$t').value);
      	XHR.appendData('zmd5','{$_GET["zmd5"]}');
      	XHR.sendAndLoad('$page', 'POST',x_SaveTemplateForm$t);          
      }	
     </script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function template_settings_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	echo "YahooWin5('815','$page?new-template=yes&t=$t&templateid={$_GET["zmd5"]}','{$_GET["zmd5"]}');";
	
}
	
function ZOOM_POPUP(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$sql="SELECT template_body,template_title FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();	
	$headers_text=$tpl->_ENGINE_parse_body("{headers}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$ligne["template_body"]=trim(utf8_encode($ligne["template_body"]));	
	if($ligne["template_body"]==null){
		$ligne["template_body"]="<table class=\"w100 h100\"><tr><td class=\"c m\"><table style=\"margin:0 auto;border:solid 1px #560000\"><tr><td class=\"l\" style=\"padding:1px\"><div style=\"width:346px;background:#E33630\"><div style=\"padding:3px\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><h1>ERROR: The requested URL could not be retrieved</h1></div><div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div><div style=\"background:#F7F7F7;padding:20px 28px 36px\"> <div id=\"titles\"> <h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> <p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  <blockquote id=\"error\"> <p><b>Access Denied.</b></p> </blockquote>  <p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div></div></td></tr></table></td></tr></table>";
	}
	
	
	$ligne["template_title"]=stripslashes($ligne["template_title"]);
	$ligne["template_body"]=stripslashes($ligne["template_body"]);
	
	$html="
	<table style='width:99%' class=form>
	
	
	
	<tr>
	<td width=99%>&nbsp;</td>
		<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?template-settings-js=yes&zmd5={$_GET["zmd5"]}&t=$t');\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>$parameters</a>
	</td>
	
	<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?headers-js=yes&zmd5={$_GET["zmd5"]}');\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>$headers_text</a>
	</td>
	<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"s_PopUp('$page?preview=yes&zmd5={$_GET["zmd5"]}',800,800);\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>{preview}</a>
	</td>	
	</tr>
	</table>
			
	<center>".Field_text("template_title-$t",utf8_encode($ligne["template_title"]),"font-size:18px;border:4px solid #CCCCCC;width:95%")."</center>
	<div style='width:98%' class=form id='{$_GET["zmd5"]}'>

	<textarea style='width:95%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' id='{$_GET["zmd5"]}-text-$t'>{$ligne["template_body"]}</textarea>
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveTemplateForm()",16)."</div>
	</div>
	
	<script>
	var x_SaveTemplateForm=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWin6Hide();
    }	    
	
	 function SaveTemplateForm(){
      	
      	var XHR = new XHRConnection();
      	XHR.appendData('template_title',document.getElementById('template_title-$t').value);
      	XHR.appendData('template_body',document.getElementById('{$_GET["zmd5"]}-text-$t').value);
      	XHR.appendData('zmd5','{$_GET["zmd5"]}');
      	AnimateDiv('{$_GET["zmd5"]}');
      	XHR.sendAndLoad('$page', 'POST',x_SaveTemplateForm);          
      }	
     </script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function ZOOM_SAVE(){
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{MOD_TEMPLATE_ERROR_LICENSE}");
		return;
	}	
	
	$q=new mysql_squid_builder();	
	$tplbdy=$_POST["template_body"];
	$tplbdy=stripslashes($tplbdy);
	$tplbdy=trim(str_replace("\n", "", $tplbdy));
	$tplbdy=trim(str_replace("\r", "", $tplbdy));
	$tplbdy=trim(str_replace("  ", "", $tplbdy));
	$tplbdy=mysql_escape_string2($tplbdy);
	$tplbdy=trim($tplbdy);
	
	$tpltitle=$_POST["template_title"];
	$tpltitle=trim($tpltitle);
	$tpltitle=mysql_escape_string2($tpltitle);
	
	$sql="UPDATE squidtpls 
	SET template_body='$tplbdy',
	template_title='$tpltitle'
	WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-templates=yes&zmd5={$_POST["zmd5"]}");		
}

function RebuidSquidTplDefault(){
	$q=new mysql_squid_builder();	
	$q->QUERY_SQL("TRUNCATE TABLE squidtpls");
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-default-tpls=yes");
	$sock->getFrameWork("squid.php?build-templates=yes");		
	
	
}

function TEMPLATE_AFFECT(){
	$ID=$_POST["ChooseAclsTplSquid"];
	$q=new mysql_squid_builder();	
	$sql="UPDATE webfilters_sqgroups SET acltpl='{$_POST["zmd5"]}' WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		
		if(preg_match("#Unknown column#", $q->mysql_error)){$q->CheckTables();$q->QUERY_SQL($sql);}
	}
	if(!$q->ok){
		echo $q->mysql_error;return;
	
	}
	$sql="SELECT template_name FROM squidtpls WHERE `zmd5`='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	echo $ligne["template_name"];
}




function TEMPLATE_ADD(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$sql="SELECT lang FROM squidtpls GROUP BY lang ORDER BY lang";
	$results=$q->QUERY_SQL($sql);
	$t=time();
	$btname="{add}";
	$lang[]="{select}";
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$txt=$ligne["lang"];
		if(is_numeric($txt)){continue;}
		if($ligne["lang"]==null){continue;}
		$lang[$ligne["lang"]]=$txt;
	}

	
	if(strlen($_GET["templateid"])>5){
		$sql="SELECT * FROM squidtpls WHERE `zmd5`='{$_GET["templateid"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$btname="{apply}";
	}

	$statusCode=$q->LoadStatusCodes();
	if($ligne["lang"]==null){$ligne["lang"]=$tpl->language;}
	
	if(preg_match("#([0-9]+):(.+)#", $ligne["template_uri"],$re)){
		$ligne["template_uri"]=$re[2];
		$status_code=$re[1];
		if(strpos($ligne["template_uri"], "%FREEWEBS%")>1){
			$freewebs_compliance=1;
			$ligne["template_uri"]=str_replace("%FREEWEBS%", "", $ligne["template_uri"]);
		}
	}
	
	if(!is_numeric($status_code)){$status_code=303;}
	if(!is_numeric($freewebs_compliance)){$freewebs_compliance=0;}
	
	
	$q=new mysql();
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM freeweb WHERE `groupware`='ERRSQUID'","artica_backup"));
	if($ligne2["tcount"]>0){
		$freewebsButton="<input type='button' 
		OnClick=\"javascript:Loadjs('BrowseFreeWebs.php?groupware=ERRSQUID&field=template_uri-$t&withhttp=1');\"
		value='{browse}...'>";
	}
	
	
	$field=Field_array_Hash($lang, "lang1-$t",$ligne["lang"],null,null,0,"font-size:22px");
	$html="<div id='$t'></div>
	<div style='width:98%' class=form>
		<table style='width:100%;margin-top:20px;margin-bottom:8px' >
		<tbody>
			<tr>
				<td class=legend style='font-size:22px'>{template_name}:</td>
				<td>". Field_text("tplname-$t",$ligne["template_name"],"font-size:22px;width:95%;font-weight:bold")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{empty_template}:</td>
				<td>". Field_checkbox("empty-$t",1,$ligne["emptytpl"],"empty$t()")."</td>
				<td>&nbsp;</td>
			</tr>							
			<tr>
				<td class=legend style='font-size:22px'>{subject}:</td>
				<td>". Field_text("template_title-$t",$ligne["template_title"],"font-size:22px;width:95%")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:22px'>{language}:</td>
				<td>$field</td>
				<td>&nbsp;</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:22px'>{UseALink}:</td>
				<td>". Field_checkbox("template_link-$t",1,$ligne["template_link"],"UseALink$t()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{http_status_code}:</td>
				<td>". Field_array_Hash($statusCode, "status_code-$t",303,null,null,0,"font-size:22px;")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{freewebs_compliance}:</td>
				<td>". Field_checkbox("freewebs_compliance-$t",1,$freewebs_compliance)."</td>
				<td>&nbsp;</td>
			</tr>								
			<tr>
				<td class=legend style='font-size:22px'>{url}:</td>
				<td>". Field_text("template_uri-$t",$ligne["template_uri"],"font-size:22px;width:95%")."</td>
				<td>$freewebsButton</td>
			</tr>
			<tr>
				<td colspan=3 align='right'><hr>". button($btname,"SaveNewTemplate$t()",32)."</td>
			</tR>
		</tbody>
		</table>
	</div>	
		
		
		<script>
			var x_SaveNewTemplate$t= function (obj) {
					var res=obj.responseText;
					document.getElementById('$t').innerHTML='';
					if (res.length>3){alert(res);return;}
					YahooWin5Hide();
					$('#SquidTemplateErrorsTable').flexReload();
			}	
		function SaveNewTemplate$t(){
				var template_link=0;
				var emptytpl=0;
				var freewebs_compliance=0;
				var lang=document.getElementById('lang1-$t').value;
				var tplname=document.getElementById('tplname-$t').value;
				var tplTitle=document.getElementById('template_title-$t').value;
				var status_code=document.getElementById('status_code-$t').value;
				template_uri=document.getElementById('template_uri-$t').value;
				if(document.getElementById('template_link-$t').checked){template_link=1;}
				if(document.getElementById('freewebs_compliance-$t').checked){freewebs_compliance=1;}
				if(document.getElementById('empty-$t').checked){emptytpl=1;}
				
				if(emptytpl==0){
					if(template_link==0){
						if(lang.length==0){alert('Please select a language..');return;}
						if(tplname.length==0){alert('Please select a template name..');return;}
						if(tplTitle.length==0){alert('Please select a template subject..');return;}
					}else{
						if(template_uri.length==0){alert('Please define an URL..');return;}
					}
				}
				
		    	var XHR = new XHRConnection();
		    	XHR.appendData('newtemplate',tplname);
		      	XHR.appendData('template_name',tplname);
		      	XHR.appendData('template_title',tplTitle);
		      	XHR.appendData('template_link',template_link);
		      	XHR.appendData('template_uri',template_uri);
		      	XHR.appendData('emptytpl',emptytpl);
		      	XHR.appendData('status_code',status_code);
		      	XHR.appendData('freewebs_compliance',freewebs_compliance);
		      	XHR.appendData('lang',lang);
		      	XHR.appendData('templateid','{$_GET["templateid"]}');
		      	
		      	
		      	
		      	
		      	AnimateDiv('$t');
		      	XHR.sendAndLoad('$page', 'POST',x_SaveNewTemplate$t);   				
				
				
			}
			
		function UseALink$t(){
		
			document.getElementById('status_code-$t').disabled=true;
			document.getElementById('freewebs_compliance-$t').disabled=true;
			document.getElementById('template_uri-$t').disabled=true;
			
			if(document.getElementById('empty-$t').checked){return;}
			
			if(document.getElementById('template_link-$t').checked){
				document.getElementById('status_code-$t').disabled=false;
				document.getElementById('freewebs_compliance-$t').disabled=false;
				document.getElementById('template_uri-$t').disabled=false;		
			}
		}
		
		function empty$t(){
			document.getElementById('template_link-$t').disabled=true;
			document.getElementById('template_title-$t').disabled=true;
			document.getElementById('lang1-$t').disabled=true;
			
			
			
			if(document.getElementById('empty-$t').checked){return;}
			document.getElementById('template_link-$t').disabled=false;
			document.getElementById('template_title-$t').disabled=false;
			document.getElementById('lang1-$t').disabled=false;
		}
		
		
		 UseALink$t();
		 empty$t();
			
		</script>
		";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function TEMPLATE_ADD_SAVE(){
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{MOD_TEMPLATE_ERROR_LICENSE}");
		return;
	}
	
	if($_POST["template_uri"]<>null){
		if(!preg_match("#^http#", $_POST["template_uri"])){
			$_POST["template_uri"]="http://". $_POST["template_uri"];
		}
	}
	
	
	if($_POST["status_code"]<>null){
		if($_POST["status_code"]>0){
			$_POST["template_uri"]="{$_POST["status_code"]}:{$_POST["template_uri"]}";
		}
	}
		
	if($_POST["freewebs_compliance"]==1){
		$_POST["template_uri"]=$_POST["template_uri"]."%FREEWEBS%";
	}
	
	$tpl_name=$_POST["template_name"];
	$tpl_name=replace_accents($tpl_name);
	$tpl_name=str_replace(" ","_", $tpl_name);
	$tpl_name=str_replace("-","_", $tpl_name);
	$tpl_name=str_replace("/","_", $tpl_name);
	$tpl_name=str_replace("\\","_", $tpl_name);
	$tpl_name=str_replace(".","_", $tpl_name);
	$tpl_name=str_replace(";","_", $tpl_name);
	$tpl_name=str_replace(",","_", $tpl_name);
	$tpl_name=str_replace("?","_", $tpl_name);
	$tpl_name=str_replace("\$","_", $tpl_name);
	$tpl_name=str_replace("%","_", $tpl_name);
	$tpl_name=str_replace("{","_", $tpl_name);
	$tpl_name=str_replace("}","_", $tpl_name);
	$tpl_name=str_replace("[","_", $tpl_name);
	$tpl_name=str_replace("]","_", $tpl_name);
	$tpl_name=str_replace("(","_", $tpl_name);
	$tpl_name=str_replace(")","_", $tpl_name);
	$tpl_name=str_replace("\"","_", $tpl_name);
	$tpl_name=str_replace("'","_", $tpl_name);
	$tpl_name=str_replace("&","_", $tpl_name);
	$tpl_name=strtoupper($tpl_name);
	$tpl_name=addslashes($tpl_name);
	$md5=md5(serialize($_POST));
	$_POST["template_name"]=$tpl_name;
	$_POST["template_body"]="<table class=\"w100 h100\"><tr><td class=\"c m\"><table style=\"margin:0 auto;border:solid 1px #560000\"><tr><td class=\"l\" style=\"padding:1px\"><div style=\"width:346px;background:#E33630\"><div style=\"padding:3px\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><h1>ERROR: The requested URL could not be retrieved</h1></div><div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div><div style=\"background:#F7F7F7;padding:20px 28px 36px\"> <div id=\"titles\"> <h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> <p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  <blockquote id=\"error\"> <p><b>Access Denied.</b></p> </blockquote>  <p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div></div></td></tr></table></td></tr></table>";
	
	while (list ($num, $line) = each ($_POST)){
		$_POST[$num]=addslashes($line);
	}
	
	
	 
	$sql="INSERT IGNORE INTO squidtpls (zmd5,template_name,template_title,lang,template_body,template_link,template_uri,emptytpl) 
	VALUES ('$md5','{$_POST["template_name"]}','{$_POST["template_title"]}','{$_POST["lang"]}',
	'{$_POST["template_body"]}','{$_POST["template_link"]}','{$_POST["template_uri"]}','{$_POST["emptytpl"]}')";
	
	if(strlen($_POST["templateid"])>5){
		$sql="UPDATE squidtpls SET 
				template_name='{$_POST["template_name"]}',
				template_title='{$_POST["template_title"]}',
				lang='{$_POST["lang"]}',
				emptytpl='{$_POST["emptytpl"]}',
				template_link='{$_POST["template_link"]}',
				template_uri='{$_POST["template_uri"]}'
				WHERE zmd5='{$_POST["templateid"]}'";
		
	}
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("squidtpls", "emptytpl")){$q->QUERY_SQL("ALTER TABLE `squidtpls` ADD `emptytpl` smallint(1)  NOT NULL");}
	
	
	$q->CheckTables();
	$q->QUERY_SQL($sql,"artica_backup");			
	if(!$q->ok){echo $q->mysql_error;	writelogs( $q->mysql_error,__FUNCTION__,__FILE__,__LINE__);return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-templates=yes&zmd5=$md5");		
	
}

function select_lang(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	$lang=unserialize('a:45:{i:0;s:8:"{select}";s:2:"af";s:2:"af";s:2:"ar";s:2:"ar";s:2:"az";s:2:"az";s:2:"bg";s:2:"bg";s:2:"ca";s:2:"ca";s:2:"cs";s:2:"cs";s:2:"da";s:2:"da";s:2:"de";s:2:"de";s:2:"el";s:2:"el";s:2:"en";s:2:"en";s:2:"es";s:2:"es";s:2:"et";s:2:"et";s:2:"fa";s:2:"fa";s:2:"fi";s:2:"fi";s:2:"fr";s:2:"fr";s:2:"he";s:2:"he";s:2:"hu";s:2:"hu";s:2:"hy";s:2:"hy";s:2:"id";s:2:"id";s:2:"it";s:2:"it";s:2:"ja";s:2:"ja";s:2:"ko";s:2:"ko";s:2:"lt";s:2:"lt";s:2:"lv";s:2:"lv";s:2:"ms";s:2:"ms";s:2:"nl";s:2:"nl";s:2:"oc";s:2:"oc";s:2:"pl";s:2:"pl";s:2:"pt";s:2:"pt";s:5:"pt-br";s:5:"pt-br";s:2:"ro";s:2:"ro";s:2:"ru";s:2:"ru";s:2:"sk";s:2:"sk";s:5:"sr-cy";s:5:"sr-cy";s:5:"sr-la";s:5:"sr-la";s:2:"sv";s:2:"sv";s:5:"templ";s:5:"templ";s:2:"th";s:2:"th";s:2:"tr";s:2:"tr";s:2:"uk";s:2:"uk";s:2:"uz";s:2:"uz";s:2:"vi";s:2:"vi";s:5:"zh-cn";s:5:"zh-cn";s:5:"zh-tw";s:5:"zh-tw";}');
	

	
	
	$field=Field_array_Hash($lang, "lang1-$t",null,"RefreshSquidLangTemplateErrorsTable$t()",null,0,"font-size:16px");
	$html="
	
		<table style='width:99%;margin-top:50px;margin-bottom:8px;' class=form>
		<tbody>
			<tr>
			<td class=legend style='font-size:16px'>{language}:</td>
			<td>$field</td>
		</tr>
		</tbody>
		</table>
		
		
		
		<script>
			function RefreshSquidLangTemplateErrorsTable$t(){
				var lang=document.getElementById('lang1-$t').value;
				if(lang.length==0){alert('Please select a language..');return;}
				$('#SquidTemplateErrorsTable').flexOptions({ url: '$page?view-table=yes&choose-acl={$_GET["choose-acl"]}&choose-generic={$_GET["choose-generic"]}&lang='+lang+'&xlang='+lang }).flexReload();
				YahooWin5Hide();
			}
		</script>
		";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function popup(){
	$error=null;
	$page=CurrentPageName();	
	$tpl=new templates();	
	$users=new usersMenus();
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
	
	$table_width=771;
	$row1=32;
	$row2=190;
	$rows3=$template_title_size;
	$rows4=110;
	$rows5=31;
	if(isset($_GET["viatabs"])){
		$viatabs="&viatabs=yes";
		$table_width="'99%'";
		$row1=75;
		$row2=233;
		$rows3=371;
		$rows4=149;
		$rows5=57;
	}
	
	
	
	$html="
	$error
	<div style='margin-left:-10px'>
	<table class='SquidTemplateErrorsTable' style='display: none' id='SquidTemplateErrorsTable' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#SquidTemplateErrorsTable').flexigrid({
	url: '$page?view-table=yes{$viatabs}&lang=&choose-acl={$_GET["choose-acl"]}&choose-generic={$_GET["choose-generic"]}&divid={$_GET["divid"]}',
	dataType: 'json',
	colModel : [
		{display: '$lang', name : 'lang', width :$row1, sortable : true, align: 'left'},
		{display: '$template_name', name : 'template_name', width :$row2, sortable : true, align: 'left'},
		{display: '$title', name : 'template_title', width : $rows3, sortable : false, align: 'left'},
		{display: '$date', name : 'template_time', width : $rows4, sortable : true, align: 'left'},
		$chooseacl_column
		{display: '&nbsp;', name : 'delete', width : $rows5, sortable : false, align: 'center'},
	],
	
	buttons : [
		{name: '$new_template', bclass: 'add', onpress : NewTemplateNew},
		{name: '$lang', bclass: 'Search', onpress : SearchLanguage},
		{separator: true},
		{name: '$replace', bclass: 'Copy', onpress : Replace$t},
		{name: '$defaults', bclass: 'add', onpress : Defaults$t},
		
		{name: '$online_help', bclass: 'Help', onpress : help$t},

		],
	
	searchitems : [
		{display: '$template_name', name : 'template_name'},
		{display: '$title', name : 'template_title'},
		{display: 'URL', name : 'template_uri'},
		
		
		],
	sortname: 'template_time',
	sortorder: 'desc',
	usepager: true,
	title: '$squid_choose_template',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $table_width,
	height: 400,
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

function view_table(){
	$q=new mysql_squid_builder();
	$Mypage=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$search='%';
	$table="squidtpls";
	$page=1;
	$searchstring=null;
	if($_GET["lang"]<>null){$FORCEQ=" AND lang='{$_GET["lang"]}'";}
	$choose_generic=$_GET["choose-generic"];
	if(!$q->FIELD_EXISTS($table, "template_time")){$q->CheckTables();}
	
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table no such table",2);}
	if($q->COUNT_ROWS($table)==0){json_error_show("No data in $table",2);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCEQ$searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql",2);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
		if(!$q->ok){json_error_show("$q->mysql_error",2);}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$data = array();
	$data['rows'] = array();
	
	$delete_icon="delete-24.png";
	$fontsize=12;
	if(isset($_GET["viatabs"])){$fontsize=14;$delete_icon="delete-32.png";}
	$span="<span style='font-size:{$fontsize}px'>";
	
	
	
	$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
	$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
	if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
	if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
	$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
	if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
	$URLAR=parse_url($SplashScreenURI);
	if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}
	if(!preg_match("^http.*", $SplashScreenURI)){$SplashScreenURI="http://$SplashScreenURI";}
	if($searchstring==null){
		if($EnableSplashScreen==1){
			if($EnableSplashScreenAsObject==1){
				$linkZoom=null;
				$cell=array();
				$delete="&nbsp;";
				$cell[]=$tpl->_ENGINE_parse_body("{all}</span>");
				$cell[]="<span style='font-size:{$fontsize}px;font-weight:bold'>$linkZoom$SplashScreenURI</a></span>";
				$cell[]=$tpl->_ENGINE_parse_body("<span style='font-size:{$fontsize}px;fonct-weight:bold'>$linkZoom{hotspot_auth}</a></span>");
				$cell[]="&nbsp;</span>";
				if($_GET["choose-acl"]>0){
					$cell[]=imgsimple("arrow-right-24.png",null,"ChooseAclsTplSquid('{$_GET["choose-acl"]}','ARTICA_SLASH_SCREEN')");
						
				}
				
				if(strlen($choose_generic)>3){
					$cell[]=imgsimple("arrow-right-24.png",null,"ChooseGenericTemplate('ARTICA_SLASH_SCREEN')");
				}			
				$cell[]=$delete;
				$data['rows'][] = array(
						'id' => "SplashScreen",
						'cell' =>$cell
				);
				$total++;
			}
			
		}
	}

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $FORCEQ$searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	
	$data['page'] = $page;
	$data['total'] = $total;
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",2);}
	$statuscodes=$q->LoadStatusCodes();
	$empty_template=$tpl->_ENGINE_parse_body("{empty_template}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$title=base64_encode($ligne["template_title"]);
		$linkZoom="<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$Mypage?Zoom-js={$ligne['zmd5']}&subject=$title');\" style='font-size:{$fontsize}px;text-decoration:underline'>";
	
		
		if($ligne["template_link"]==1){
			$ligne['lang']=imgsimple("24-parameters.png",null,"NewTemplate('$zmd5')");
			
			if(preg_match("#^([0-9]+):(.+)#", $ligne["template_uri"],$ri)){
				$ligne["template_uri"]=$ri[2];
				$ligne["template_uri"]=str_replace("%FREEWEBS%", "<div>+FreeWebs</div>", $ligne["template_uri"]);
				$ligne["template_uri"]=$ligne["template_uri"]."<div>".$statuscodes[$ri[1]]."</div>";
			}
			$ligne["template_title"]=$ligne["template_uri"];
			
		}
		
		$cell=array();
		if($ligne["template_title"]==null){$ligne["template_title"]=$empty_template;}
		if($ligne["emptytpl"]==1){
			$linkZoom="<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$Mypage?template-settings-js=yes&zmd5={$ligne["zmd5"]}&t={$_GET["t"]}');\" style='font-size:{$fontsize}px;text-decoration:underline'>";
		}
		
		$ligne['template_name']=utf8_encode($ligne['template_name']);
		$ligne['template_title']=utf8_encode($ligne['template_title']);
		
		$delete=imgsimple($delete_icon,null,"TemplateDelete('{$ligne['zmd5']}')");
		$cell[]="$span$linkZoom{$ligne['lang']}</a></span>";
		$cell[]="$span$linkZoom{$ligne['template_name']}</a></span>";
		$cell[]="$span$linkZoom{$ligne["template_title"]}</a></span>";
		$cell[]="$span$linkZoom{$ligne["template_time"]}</a></span>";
		if($_GET["choose-acl"]>0){
			$cell[]=imgsimple("arrow-right-24.png",null,"ChooseAclsTplSquid('{$_GET["choose-acl"]}','{$ligne['zmd5']}')");
			
		}

		if(strlen($choose_generic)>3){
			$cell[]=imgsimple("arrow-right-24.png",null,"ChooseGenericTemplate('{$ligne['template_name']}')");
		}
		
		$cell[]=$delete;
		

		
		$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' =>$cell
		);
	}
echo json_encode($data);	
	
}


function FormTemplate(){
	
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT TEMPLATE_DATA FROM squid_templates WHERE TEMPLATE_NAME='{$_GET["TEMP"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	
	$tpl=new templates();
	$button=$tpl->_ENGINE_parse_body("<input type='submit' value='{apply}'>");
	$tiny=TinyMce('TEMPLATE_DATA',$ligne["TEMPLATE_DATA"]);
	$html="
	<html>
	<head>
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<script type='text/javascript' language='JavaScript' src='mouse.js'></script>
	<script type='text/javascript' language='javascript' src='XHRConnection.js'></script>
	<script type='text/javascript' language='javascript' src='default.js'></script>
	<script type='text/javascript' language='javascript' src='/js/jquery.uilock.min.js'></script>
	<script type='text/javascript' language='javascript' src='/js/jquery.blockUI.js'></script>  
		
	</head>
	<body width=100% style='background-color:#005447;margin:0px;padding:0px'> 
	<form name='FFM1' METHOD=POST style='margin:0px;padding:0px'>
	<input type='hidden' name='TEMPLATE_NAME' value='{$_GET["TEMP"]}'>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:10px'>$button<br></div>
	<center>
	<div style='width:750px;height:650px'>$tiny</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px;padding:10px'>$button<br></div>
	
	</form>
	</body>
	
	</html>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function TEMPLATE_REMOVE(){
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?squid-templates=yes");
		echo $tpl->javascript_parse_text("{MOD_TEMPLATE_ERROR_LICENSE}");
		return;
	}
	
	$sql="DELETE FROM squidtpls WHERE `zmd5`='{$_POST["tpl-remove"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-templates=yes");	
}


function TEMPLATE_HEADER_SAVE(){
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{MOD_TEMPLATE_ERROR_LICENSE}");
		return;
	}
	
	$template_header=addslashes($_POST["template_header"]);
	if(strlen($template_header)==0){echo "template_header: no data\n";return;}
	$zmd5=$_POST["zmd5"];
	if($zmd5==null){echo "Fatal Key is null!\n";return;}
	$sql="UPDATE squidtpls SET `template_header`='$template_header' WHERE `zmd5`='$zmd5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		echo $q->mysql_error;
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-templates=yes&zmd5=$zmd5");	
}

?>