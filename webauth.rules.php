<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.wifidog.settings.inc');
	include_once('ressources/class.wifidog.templates.inc');
	
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDansGuardianAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["backup-download-js"])){backup_download_js();exit;}
	if(isset($_GET["backup-download-popup"])){backup_download_popup();exit;}
	if(isset($_GET["enable-js"])){skin_enable_js();exit;}
	if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
	if(isset($_GET["picture"])){send_picture();exit;}
	if(isset($_GET["rule-js"])){rule_js();exit;}
	if(isset($_GET["list"])){list_items();exit();}
	if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
	if(isset($_GET["SETTINGS"])){SETTINGS();exit;}
	if(isset($_GET["TEMPLATE_CONTENT"])){skin_design();exit;}
	if(isset($_GET["TEMPLATE_LOGO"])){skin_logo();exit;}
	
	if(isset($_POST["rulename"])){SETTINGS_SAVE();exit;}
	if(isset($_POST["UFDBGUARD_TITLE_1"])){skin_design_save();exit;}
	if(isset($_POST["SquidHTTPTemplateLogoEnable"])){skin_logo_save();exit;}
	if(isset($_POST["delete-rule"])){rule_delete();exit;}
	if(isset($_POST["enable-rule"])){skin_enable();exit;}
	if(isset($_GET["WifidogClientTimeout-js"])){WifidogClientTimeout_js();exit;}
	if(isset($_GET["WifidogClientTimeout-popup"])){WifidogClientTimeout_popup();exit;}
	if(isset($_POST["WifidogClientTimeout"])){WifidogClientTimeout_save();exit;}
page();

function rule_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ID=$_GET["ID"];
	$t=time();
	$TEMPLATE_TITLE=$tpl->javascript_parse_text("{new_rule}");
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$sql="SELECT rulename FROM webauth_rules WHERE ID='{$_GET["ID"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$TEMPLATE_TITLE=utf8_encode("{$ligne["rulename"]}");
	}
	
	echo "YahooWin3(1250,'$page?rule-tabs=yes&ID=$ID','$TEMPLATE_TITLE')";
}

function backup_download_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$TEMPLATE_TITLE=$tpl->javascript_parse_text("{backup}");
	$filename="/usr/share/artica-postfix/ressources/logs/web/hotspot.rules.backup.gz";
	if(!is_file("$filename")){
		echo "alert('hotspot.rules.backup.gz no such file');";
		return;
	}
	
	echo "YahooWin3(800,'$page?backup-download-popup=yes','$TEMPLATE_TITLE')";
	
}

function backup_download_popup(){
	$filename="/usr/share/artica-postfix/ressources/logs/web/hotspot.rules.backup.gz";
	$size=FormatBytes(@filesize($filename)/1024);
	echo "<center style='margin:50px'>
			<a href=\"ressources/logs/web/hotspot.rules.backup.gz\"><img src='img/gz-128.png'></a>
			<hr>
			<a href=\"ressources/logs/web/hotspot.rules.backup.gz\" style='font-size:30px;color:black;text-decoration:underline'>
			hotspot.rules.backup.gz ($size)</a></center>
			";
	
}

function WifidogClientTimeout_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$TEMPLATE_TITLE=$tpl->javascript_parse_text("{session_time}");
	echo "YahooWin3(850,'$page?WifidogClientTimeout-popup=yes','$TEMPLATE_TITLE')";
}
function WifidogClientTimeout_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";
	$WifidogClientTimeout=intval($sock->GET_INFO("WifidogClientTimeout"));
	if($WifidogClientTimeout<5){$WifidogClientTimeout=30;}
	$html="
	<div style='width:98%' class=form>
	<table style='width:99%'>
	
	<tr>
		<td  style='font-size:42px' colspan=2>{sessions}:</td>
	</tr>
	<tr>
		<td colspan=2><div class=explain style='font-size:18px' >{wifidog_disconnect_time}</div></td>
	</tr>					
	<tr>
		<td class=legend style='font-size:28px'>{session_time}:</td>
		<td>". Field_array_Hash($Timez,"WifidogClientTimeout-$t", $WifidogClientTimeout,
				"style:font-size:28px")."</td>
	</tr>	
							
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","42px")."</td>
	</tr>	
</table>	
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#HOSTPOT_RULES').flexReload();
		Loadjs('squid.webauth.restart.php');
		

	}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('WifidogClientTimeout',document.getElementById('WifidogClientTimeout-$t').value);
	XHR.sendAndLoad('$page', 'POST', xSave$t);
}		
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function WifidogClientTimeout_save(){
	$sock=new sockets();
	$sock->SET_INFO("WifidogClientTimeout", $_POST["WifidogClientTimeout"]);
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
}

function rule_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webauth_rules WHERE ID='{$_POST["delete-rule"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM webauth_rules_nets WHERE ruleid='{$_POST["delete-rule"]}'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE ruleid='{$_POST["delete-rule"]}'");
	$q->QUERY_SQL("DELETE FROM hotspot_members WHERE ruleid='{$_POST["delete-rule"]}'");
	$q->QUERY_SQL("DELETE FROM webauth_settings WHERE ruleid='{$_POST["delete-rule"]}'");
	$q->QUERY_SQL("DELETE FROM hotspot_activedirectory WHERE ruleid='{$_POST["delete-rule"]}'");
	
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	
}

function skin_enable(){
	$q=new mysql_squid_builder();
	$sql="SELECT enabled FROM webauth_rules WHERE ID='{$_POST["enable-rule"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["enabled"]==1){
		$q->QUERY_SQL("UPDATE webauth_rules SET `enabled`=0 WHERE ID='{$_POST["enable-rule"]}'");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	$q->QUERY_SQL("UPDATE webauth_rules SET `enabled`=1 WHERE ID='{$_POST["enable-rule"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?weberror-cache-remove=yes");
}

function skin_enable_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$zmd5=$_GET["enable-js"];

	$t=time();
	echo "
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#UFDB_SKIN_RULES').flexReload();
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('enable-rule','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	}

function rule_delete_js(){

	
header("content-type: application/x-javascript");
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$ID=$_GET["delete-rule-js"];
$q=new mysql_squid_builder();
$sql="SELECT * FROM webauth_rules WHERE ID={$ID}";
$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
$rulename=utf8_encode($ligne["rulename"]);
$delete=$tpl->javascript_parse_text("{delete}");
$t=time();
echo "
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#HOSTPOT_RULES').flexReload();
	
}
	
	
function Save$t(){
	if(!confirm('$delete $rulename ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
}

function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$array["SETTINGS"]='{settings}';
	if($_GET["ID"]>0){
		$array["NETWORKS"]='{networks}';
		$array["BEHAVIOR"]='{behavior}';
		$array["SKIN"]='{skin}';
		$array["TEXT"]='{messages}';
		$array["BACKGROUND"]='{background_picture}';
		$array["AD"]='Active Directory';
		$array["SMTP"]='{smtp_parameters}';
		$array["SMS"]='SMS';
		
	}
	$fontsize=19;
	while (list ($num, $ligne) = each ($array) ){

		if($num=="NETWORKS"){
			$tab[]="<li><a href=\"webauth.rules.networks.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}
		if($num=="BEHAVIOR"){
			$tab[]="<li><a href=\"webauth.rules.parameters.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}
		if($num=="TEXT"){
			$tab[]="<li><a href=\"webauth.rules.text.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}	
		
		if($num=="AD"){
			$tab[]="<li><a href=\"squid.webauth.activedirectory.php?ruleid={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}		
		
		if($num=="BACKGROUND"){
			$tab[]="<li><a href=\"webauth.rules.picture.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
			
		}
		
		if($num=="SKIN"){
			$tab[]="<li><a href=\"webauth.rules.skin.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}	

		if($num=="SMTP"){
			$tab[]="<li><a href=\"webauth.rules.smtp.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}	

		if($num=="SMS"){
			$tab[]="<li><a href=\"webauth.rules.sms.php?ID={$_GET["ID"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a>
			</li>\n";
			continue;
		}
		
		$tab[]="<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}\">
		<span style='font-size:{$fontsize}px'>$ligne</span></a>
		</li>\n";
			
	}
	$html=build_artica_tabs($tab,"HOTSPOT_TAB");
	echo $html;
	
	
}


function SETTINGS(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$ligne["enabled"]=1;
	$btname="{add}";
	if($_GET["ID"]>0){
		$sql="SELECT * FROM webauth_rules WHERE ID={$_GET["ID"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){echo $q->mysql_error_html();}
		$btname="{apply}";
		
	}
	
	
$html="
<div style='width:98%' class=form>
<table style='width:100%'>
		". 
		Field_text_table("rulename-$t", "{rulename}",utf8_encode($ligne["rulename"]),26,null,450).
		Field_checkbox_table("enabled-$t","{enabled}", $ligne["enabled"],26).
		Field_spacer_table(50).
		Field_button_table_autonome("$btname", "Save$t",34).
"</table>
</div>
<script>
	var xSave$t= function (obj) {
		var ID='{$_GET["ID"]}';
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#HOSTPOT_RULES').flexReload();
		if(ID==0){YahooWin3Hide(); }
	}	

	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('rulename',document.getElementById('rulename-$t').value);
		if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
</script>				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function skin_logo(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$error=null;
	$t=time();
	$q=new mysql_squid_builder();
	$error=null;
	
	$button="<hr>".button("{apply}", "Save$t()",34);

	
	$sql="SELECT * FROM webauth_rules WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	
	$SquidHTTPTemplateLogoPath=$ligne["SquidHTTPTemplateLogoPath"];
	$SquidHTTPTemplateLogoEnable=intval($ligne["SquidHTTPTemplateLogoEnable"]);
	$SquidHTTPTemplateLogoPositionH=$ligne["SquidHTTPTemplateLogoPositionH"];
	$SquidHTTPTemplateLogoPositionL=$ligne["SquidHTTPTemplateLogoPositionL"];
	$SquidHTTPTemplateSmiley=intval($ligne["SquidHTTPTemplateSmiley"]);
	$SquidHTTPTemplateSmileyEnable=$ligne["SquidHTTPTemplateSmileyEnable"];
	$picturealign=$ligne["picturealign"];
	
	$picturemode=$ligne["picturemode"];
	if($picturemode==null){$picturemode="absolute"; }

	if($SquidHTTPTemplateLogoPositionH==null){$SquidHTTPTemplateLogoPositionH="10";}
	if($SquidHTTPTemplateLogoPositionL==null){$SquidHTTPTemplateLogoPositionL="10";}
	if($SquidHTTPTemplateSmiley==0){$SquidHTTPTemplateSmiley=2639;}

	if(!$users->CORP_LICENSE){
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
	
	if($ligne["picturename"]<>null){$logoPic="<center style='margin:20px'>
	<img src='$page?picture={$ligne["zmd5"]}&t=$t&name={$ligne["picturename"]}'></center>";
	}

	$html="<div style='width:98%' class=form>
	$logoPic
	<center style='margin:10px'>". button("{upload_a_picture}","Loadjs('squidguardweb.skins.uploadlogo.php?zmd5={$_GET["zmd5"]}')",32)."</center>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{enable_logo_image}:</td>
		<td>". Field_checkbox_design("SquidHTTPTemplateLogoEnable-$t",1,$SquidHTTPTemplateLogoEnable)."</td>
	</tr>

	<tr><td colspan=2><p>&nbsp;</p></td></tr>			
	<tr>
		<td class=legend style='font-size:34px'>Smiley:</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{enabled}:</td>
		<td>". Field_checkbox_design("SquidHTTPTemplateSmileyEnable-$t",1,$SquidHTTPTemplateSmileyEnable)."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:26px'>Smiley (code):</td>
		<td>". Field_text("SquidHTTPTemplateSmiley-$t",$SquidHTTPTemplateSmiley,"width:120px;font-size:26px")."</td>
	</tr>				
	<tr><td colspan=2><p>&nbsp;</p></td></tr>			

	<tr>
		<td class=legend style='font-size:26px'>{position} TOP:</td>
		<td style='font-size:26px'>". Field_text("SquidHTTPTemplateLogoPositionH-$t",$SquidHTTPTemplateLogoPositionH,"font-size:26px;width:150px")."%</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{position} LEFT:</td>
		<td style='font-size:26px'>". Field_text("SquidHTTPTemplateLogoPositionL-$t",$SquidHTTPTemplateLogoPositionL,"font-size:26px;width:150px")."%</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{type}:</td>
		<td>". Field_array_Hash($picturemode_Hash, "picturemode-$t",$picturemode,"style:font-size:26px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{align}:</td>
		<td>". Field_array_Hash($picturealign_Hash, "picturealign-$t",$picturealign,"style:font-size:26px")."</td>
	</tr>	
	
		<tr>
		<td colspan=2 align='right'>$button</td>
		</tr>
</table>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	RefreshTab('ERROR_PAGE_SKIN_TAB');
}

function Save$t(){
	var XHR = new XHRConnection();
	var SquidHTTPTemplateLogoEnable=0;
	var SquidHTTPTemplateSmileyEnable=0;
	if(document.getElementById('SquidHTTPTemplateLogoEnable-$t').checked){SquidHTTPTemplateLogoEnable=1;}
	if(document.getElementById('SquidHTTPTemplateSmileyEnable-$t').checked){SquidHTTPTemplateSmileyEnable=1;}	
	
	
	
	
	XHR.appendData('zmd5','{$_GET["zmd5"]}');
	XHR.appendData('picturemode',document.getElementById('picturemode-$t').value);
	XHR.appendData('picturealign',document.getElementById('picturealign-$t').value);
	
	XHR.appendData('SquidHTTPTemplateLogoEnable',SquidHTTPTemplateLogoEnable);
	XHR.appendData('SquidHTTPTemplateSmileyEnable',SquidHTTPTemplateSmileyEnable);
	XHR.appendData('SquidHTTPTemplateSmiley',document.getElementById('SquidHTTPTemplateSmiley-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPositionH',document.getElementById('SquidHTTPTemplateLogoPositionH-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPositionL',document.getElementById('SquidHTTPTemplateLogoPositionL-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
</div>";

echo $tpl->_ENGINE_parse_body($html);



}

function skin_logo_save(){
	$sql="UPDATE webauth_rules SET `SquidHTTPTemplateLogoEnable`='{$_POST["SquidHTTPTemplateLogoEnable"]}',
	`SquidHTTPTemplateSmiley`='{$_POST["SquidHTTPTemplateSmiley"]}',
	`SquidHTTPTemplateSmileyEnable`='{$_POST["SquidHTTPTemplateSmileyEnable"]}',
	`SquidHTTPTemplateLogoPositionH`='{$_POST["SquidHTTPTemplateLogoPositionH"]}',
	`SquidHTTPTemplateLogoPositionL`='{$_POST["SquidHTTPTemplateLogoPositionL"]}',
	`picturemode`='{$_POST["picturemode"]}',
	`picturealign`='{$_POST["picturealign"]}'
	WHERE `zmd5`='{$_POST["zmd5"]}'";
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("webauth_rules", "SquidHTTPTemplateSmileyEnable")){
		$q->QUERY_SQL("ALTER TABLE `webauth_rules` ADD `SquidHTTPTemplateSmileyEnable` smallint(1) NOT NULL DEFAULT 1");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?weberror-cache-remove=yes");
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
}

function SETTINGS_SAVE(){
	$q=new mysql_squid_builder();
	$ID=$_POST["ID"];
	$rulename=mysql_escape_string2($_POST["rulename"]);
	
	if($rulename==null){
		echo "Rule name cannot be empty...";
		return;
	}
	
	if($ID==0){
		
		$sql="INSERT IGNORE INTO webauth_rules (rulename,enabled) VALUES ('$rulename','{$_POST["enabled"]}')";
		
	}else{
		$sql="UPDATE webauth_rules SET `rulename`='$rulename',`enabled`='{$_POST["enabled"]}' WHERE `ID`='{$_POST["ID"]}'";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	
}

function skin_design(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$error=null;

	if($_GET["zmd5"]==null){echo FATAL_ERROR_SHOW_128("MD5 key is null!");return;}
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webauth_rules WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
		
	
	$UfdbGuardHTTPNoVersion=$ligne["UfdbGuardHTTPNoVersion"];
	$UfdbGuardHTTPBackgroundColor=$ligne["UfdbGuardHTTPBackgroundColor"];
	$UfdbGuardHTTPFamily=$ligne["UfdbGuardHTTPFamily"];
	$UfdbGuardHTTPFontColor=$ligne["UfdbGuardHTTPFontColor"];
	$UFDBGUARD_TITLE_1=$ligne["UFDBGUARD_TITLE_1"];
	$UFDBGUARD_PARA1=$ligne["UFDBGUARD_PARA1"];
	$UFDBGUARD_TITLE_2=$ligne["UFDBGUARD_TITLE_2"];
	$UFDBGUARD_PARA2=$ligne["UFDBGUARD_PARA2"];
	
	$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
	$UfdbGuardHTTPBackgroundColorBLK=$ligne["UfdbGuardHTTPBackgroundColorBLK"];
	$UfdbGuardHTTPBackgroundColorBLKBT=$ligne["UfdbGuardHTTPBackgroundColorBLKBT"];
	$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	$UFDBGUARD_UNLOCK_LINK=$ligne["UFDBGUARD_UNLOCK_LINK"];
	$UFDBGUARD_TICKET_LINK=$ligne["UFDBGUARD_TICKET_LINK"];
	$TICKET_TEXT=$ligne["TICKET_TEXT"];
	$TICKET_TEXT_SUCCESS=$ligne["TICKET_TEXT_SUCCESS"];
	
	if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
	if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
	if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
	if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
	

	if(!is_numeric($UfdbGuardHTTPEnablePostmaster)){$UfdbGuardHTTPEnablePostmaster=1;}
	if($UfdbGuardHTTPBackgroundColor==null){$UfdbGuardHTTPBackgroundColor="#8c1919";}
	if($UfdbGuardHTTPBackgroundColorBLK==null){$UfdbGuardHTTPBackgroundColorBLK="#0300AC";}
	if($UfdbGuardHTTPBackgroundColorBLKBT==null){$UfdbGuardHTTPBackgroundColorBLKBT="#625FFD";}
	if($UfdbGuardHTTPFontColor==null){$UfdbGuardHTTPFontColor="#FFFFFF";}
	if($TICKET_TEXT==null){$TICKET_TEXT="{ufdb_ticket_text}";}
	if($TICKET_TEXT_SUCCESS==null){$TICKET_TEXT_SUCCESS="{ufdb_ticket_text_success}";}
	
	
	if($UfdbGuardHTTPFamily==null){$UfdbGuardHTTPFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}

	
	$button="<hr>".button("{apply}", "Save$t()",32);

	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		$button=null;
	}

$html="$error<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' width=1% nowrap>{remove_proxy_hostname}:</td>
		<td width=99%>". Field_checkbox_design("UfdbGuardHTTPDisableHostname-$t",1,$UfdbGuardHTTPDisableHostname)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' width=1% nowrap>{remove_artica_version}:</td>
		<td width=99%>". Field_checkbox_design("UfdbGuardHTTPNoVersion-$t",1,$UfdbGuardHTTPNoVersion)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{add_webmaster}:</td>
		<td>". Field_checkbox_design("UfdbGuardHTTPEnablePostmaster-$t",1,$UfdbGuardHTTPEnablePostmaster)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color}:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColor-$t",$UfdbGuardHTTPBackgroundColor,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color} Unlock Page:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColorBLK-$t",$UfdbGuardHTTPBackgroundColorBLK,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color} Button - Unlock Page:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColorBLKBT-$t",$UfdbGuardHTTPBackgroundColorBLKBT,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{font_family}:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UfdbGuardHTTPFamily-$t'>$UfdbGuardHTTPFamily</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{font_color}:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPFontColor-$t",$UfdbGuardHTTPFontColor,"font-size:22px;width:150px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>{titletext} 1:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UFDBGUARD_TITLE_1-$t'>$UFDBGUARD_TITLE_1</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{parapgraph} 1:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UFDBGUARD_PARA1-$t'>$UFDBGUARD_PARA1</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{titletext} 2:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UFDBGUARD_TITLE_2-$t'>$UFDBGUARD_TITLE_2</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{parapgraph} 2:</td>
		<td><textarea
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UFDBGUARD_PARA2-$t'>$UFDBGUARD_PARA2</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{submit_ticket_text}:</td>
		<td><textarea
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='TICKET_TEXT-$t'>$TICKET_TEXT</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{submit_ticket_text} ({success}):</td>
		<td><textarea
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='TICKET_TEXT_SUCCESS-$t'>$TICKET_TEXT_SUCCESS</textarea>
		</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{UFDBGUARD_UNLOCK_LINK}:</td>
		<td>".Field_text("UFDBGUARD_UNLOCK_LINK-$t",$UFDBGUARD_UNLOCK_LINK,"font-size:22px;width:100%")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{UFDBGUARD_TICKET_LINK}:</td>
		<td>".Field_text("UFDBGUARD_TICKET_LINK-$t",$UFDBGUARD_TICKET_LINK,"font-size:22px;width:100%")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>$button</td>
	</tr>
	<script>
		var xSave$t=function(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
			RefreshTab('ERROR_PAGE_SKIN_TAB');
		}

		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('zmd5','{$_GET["zmd5"]}');
			XHR.appendData('UFDBGUARD_TITLE_1',encodeURIComponent(document.getElementById('UFDBGUARD_TITLE_1-$t').value));
			XHR.appendData('UFDBGUARD_TITLE_2',encodeURIComponent(document.getElementById('UFDBGUARD_TITLE_2-$t').value));
			XHR.appendData('UFDBGUARD_PARA1',encodeURIComponent(document.getElementById('UFDBGUARD_PARA1-$t').value));
			XHR.appendData('UFDBGUARD_PARA2',encodeURIComponent(document.getElementById('UFDBGUARD_PARA2-$t').value));
			XHR.appendData('UFDBGUARD_UNLOCK_LINK',encodeURIComponent(document.getElementById('UFDBGUARD_UNLOCK_LINK-$t').value));
			XHR.appendData('UFDBGUARD_TICKET_LINK',encodeURIComponent(document.getElementById('UFDBGUARD_TICKET_LINK-$t').value));
			XHR.appendData('TICKET_TEXT',encodeURIComponent(document.getElementById('TICKET_TEXT-$t').value));
			XHR.appendData('TICKET_TEXT_SUCCESS',encodeURIComponent(document.getElementById('TICKET_TEXT_SUCCESS-$t').value));
			
			
			
			XHR.appendData('UfdbGuardHTTPFamily',document.getElementById('UfdbGuardHTTPFamily-$t').value);
			XHR.appendData('UfdbGuardHTTPBackgroundColor',document.getElementById('UfdbGuardHTTPBackgroundColor-$t').value);
			XHR.appendData('UfdbGuardHTTPBackgroundColorBLK',document.getElementById('UfdbGuardHTTPBackgroundColorBLK-$t').value);
			XHR.appendData('UfdbGuardHTTPBackgroundColorBLKBT',document.getElementById('UfdbGuardHTTPBackgroundColorBLKBT-$t').value);
			XHR.appendData('UfdbGuardHTTPDisableHostname',document.getElementById('UfdbGuardHTTPDisableHostname-$t').value);
			XHR.appendData('UfdbGuardHTTPFontColor',document.getElementById('UfdbGuardHTTPFontColor-$t').value);
			if(document.getElementById('UfdbGuardHTTPNoVersion-$t').checked){XHR.appendData('UfdbGuardHTTPNoVersion',1);}else{XHR.appendData('UfdbGuardHTTPNoVersion',0);}
			if(document.getElementById('UfdbGuardHTTPEnablePostmaster-$t').checked){XHR.appendData('UfdbGuardHTTPEnablePostmaster',1);}else{XHR.appendData('UfdbGuardHTTPEnablePostmaster',0);}
     		XHR.sendAndLoad('$page', 'POST',xSave$t);

	}
</script>
";

echo $tpl->_ENGINE_parse_body($html);

}

function skin_design_save(){
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($ligne));
		
	}
	
	$sql="UPDATE webauth_rules SET
			UFDBGUARD_TITLE_1='{$_POST["UFDBGUARD_TITLE_1"]}',
			UFDBGUARD_TITLE_2='{$_POST["UFDBGUARD_TITLE_2"]}',
			UFDBGUARD_PARA1='{$_POST["UFDBGUARD_PARA1"]}',
			UFDBGUARD_PARA2='{$_POST["UFDBGUARD_PARA2"]}',
			UfdbGuardHTTPFamily='{$_POST["UfdbGuardHTTPFamily"]}',
			UfdbGuardHTTPBackgroundColor='{$_POST["UfdbGuardHTTPBackgroundColor"]}',
			UfdbGuardHTTPBackgroundColorBLK='{$_POST["UfdbGuardHTTPBackgroundColorBLK"]}',
			UfdbGuardHTTPBackgroundColorBLKBT='{$_POST["UfdbGuardHTTPBackgroundColorBLKBT"]}',
			UfdbGuardHTTPBackgroundColorBLKBT='{$_POST["UfdbGuardHTTPBackgroundColorBLKBT"]}',
			UfdbGuardHTTPDisableHostname='{$_POST["UfdbGuardHTTPDisableHostname"]}',
			UfdbGuardHTTPFontColor='{$_POST["UfdbGuardHTTPFontColor"]}',
			UfdbGuardHTTPNoVersion='{$_POST["UfdbGuardHTTPNoVersion"]}',
			UfdbGuardHTTPEnablePostmaster='{$_POST["UfdbGuardHTTPEnablePostmaster"]}',
			UFDBGUARD_UNLOCK_LINK='{$_POST["UFDBGUARD_UNLOCK_LINK"]}',
			UFDBGUARD_TICKET_LINK='{$_POST["UFDBGUARD_TICKET_LINK"]}',
			TICKET_TEXT='{$_POST["TICKET_TEXT"]}',
			TICKET_TEXT_SUCCESS='{$_POST["TICKET_TEXT_SUCCESS"]}'
			WHERE `zmd5`='{$_POST["zmd5"]}'";
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?weberror-cache-remove=yes");
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	
}


function page(){
	$sock=new sockets();
	$q=new mysql_squid_builder();
	
	
	
	if(!$q->TABLE_EXISTS("webauth_rules")){
		$sql="CREATE TABLE IF NOT EXISTS `webauth_rules` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`rulename` varchar(128) NOT NULL,
			`enabled` smallint(1) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `rulename` (`rulename`),
			 KEY `enabled` (`enabled`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error_html());}
		return;
	}

	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$skins=$tpl->javascript_parse_text("{skins}");
	$allow=$tpl->javascript_parse_text("{allow}");
	$category=$tpl->javascript_parse_text("{category}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$title=$tpl->javascript_parse_text("{hostpot_rules}:");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$skin=$tpl->javascript_parse_text("{skin}");
	$backup=$tpl->javascript_parse_text("{backup}");
	$restore=$tpl->javascript_parse_text("{restore}");
	$t=time();
	
	$buttons="
	buttons : [
		{name: '<strong style=font-size:22px>$new_rule</strong>', bclass: 'add', onpress :  NewRule$t},
		{name: '<strong style=font-size:22px>$backup</strong>', bclass: 'export', onpress :  Backup$t},
		{name: '<strong style=font-size:22px>$restore</strong>', bclass: 'import', onpress :  Restore$t},
	],";
	
	
	$html="
<table class='HOSTPOT_RULES' style='display: none' id='HOSTPOT_RULES' style='width:99%'></table>
<script>
$('#HOSTPOT_RULES').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$skin</span>', name : 'null', width :54, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$rulename</span>', name : 'rulename', width :1109, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$enabled</span>', name : 'enabled', width :134, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},
	],
	$buttons
	
	searchitems : [
	
	{display: '$rulename', name : 'rulename'},
	],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>$title</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	
	
function NewRule$t(){
	Loadjs('$page?rule-js=0')
}

function Restore$t(){
	Loadjs('webauth.rules.restore.php');
}

function Backup$t(){
	Loadjs('webauth.rules.bakckup.progress.php');
}

function purge_caches$t(){
	Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
	Loadjs('miniadmin.proxy.reverse.import.php');
}
	
function New$t(){
	Loadjs('nginx.new.php?peer-id={$_GET["ID"]}');
}

</script>";
	echo $html;
}
function list_items(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$searchstring=string_to_flexquery();
	$page=1;
	$table="webauth_rules";
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();

	$fontsize="22";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	
	$cell=array();
	
	$urijs="Loadjs('$MyPage?skin-js=yes&zmd5=$zmd5')";
	$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
	
	$default_text=$tpl->javascript_parse_text("{default}");

	if(mysql_num_rows($results)==0){json_error_show("no rule");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";
		$rulename=utf8_encode($ligne["rulename"]);
		$ID=$ligne["ID"];
		$ColorTime="black";
		$enabled=Field_checkbox($ID, 1,$ligne["enabled"],"Loadjs('$MyPage?enable-js=yes&ID=$ID')");

		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$wifidog_templates=new wifidog_templates($ligne["ID"]);
		
		
		$color="<center style='margin-top:10px'><img src='img/spacer.png' 
		style='width:48px;height:48px;background-color:#$wifidog_templates->backgroundColor;border-radius:5px 5px 5px 5px;\n-moz-border-radius:5px;
		-webkit-border-radius:5px;'></center>";


		$urijs="Loadjs('$MyPage?rule-js=yes&ID=$ID')";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
		$delete=imgtootltip("delete-42.png",null,"Loadjs('$MyPage?delete-rule-js=$ID')");
		$EXPLAIN=HOTSPOT_EXPLAIN_RULE($ID);
		$rulename=trim($rulename);
		if($rulename==null){$rulename="No name";}
		$cell=array();
		$cell[]=$color;
		$cell[]="<span $style>$link$rulename</a></span><br><span style='font-size:16px'>$EXPLAIN</span>";
		$cell[]="<center $style>$enabled</a></center>";
		$cell[]="<center $style>$delete</a></center>";

		$data['rows'][] = array(
				'id' => $ID,
				'cell' => $cell
		);
	}


	echo json_encode($data);
}


function HOTSPOT_EXPLAIN_RULE($ID){
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$page=CurrentPageName();
	$WifidogClientTimeout=intval($sock->GET_INFO("WifidogClientTimeout"));
	if($WifidogClientTimeout<5){$WifidogClientTimeout=30;}
	$Timez[5]="5 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[43200]="1 {month}";
	$Timez[129600]="3 {months}";
	$Timez[259200]="6 {months}";
	$Timez[388800]="9 {months}";
	$Timez[518400]="1 {year}";
	
	$NETS=array();
	$results = $q->QUERY_SQL("SELECT * FROM webauth_rules_nets WHERE ruleid=$ID");
	while ($ligne = mysql_fetch_assoc($results)) {
		$NETS[]=$ligne["pattern"];
		
	}
	
	if(count($NETS)==0){$network="{no_network_defined}";}else{$network=@implode(" {or} ", $NETS);}
	$f[]="{when_a_guest_computer_is_a_part_of_nets}:$network {then}";
	

	$ayDscp = array(0 => '{default}',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
	$sock=new wifidog_settings($ID);
	$ArticaHotSpotNowPassword=intval($sock->GET_INFO("ArticaHotSpotNowPassword"));
	$ENABLED_REDIRECT_LOGIN=intval($sock->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	$ArticaSplashHotSpotEndTime=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	$ENABLED_META_LOGIN=intval($sock->GET_INFO("ENABLED_META_LOGIN"));
	$USE_TERMS=intval($sock->GET_INFO("USE_TERMS"));
	$ArticaSplashHotSpotCacheAuth=intval($sock->GET_INFO("ArticaSplashHotSpotCacheAuth"));
	$USE_MYSQL=intval($sock->GET_INFO("USE_MYSQL"));
	$USE_ACTIVEDIRECTORY=intval($sock->GET_INFO("USE_ACTIVEDIRECTORY"));
	$ENABLED_AUTO_LOGIN=intval($sock->GET_INFO("ENABLED_AUTO_LOGIN"));
	$DO_NOT_AUTENTICATE=intval($sock->GET_INFO("DO_NOT_AUTENTICATE"));
	$LIMIT_BY_SIZE=intval($sock->GET_INFO("LIMIT_BY_SIZE"));
	$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));;
	$SMS_REGISTER=intval($sock->GET_INFO("SMS_REGISTER"));
	$MACWHITE=intval($sock->GET_INFO("MACWHITE"));
	$BOUNCE_AUTH=intval($sock->GET_INFO("BOUNCE_AUTH"));
	$TOS_VALUE=intval($sock->GET_INFO("TOS_VALUE"));
	
	$andadd_text=null;
	if($USE_TERMS==1){
		$andadd_text="{and} ";
		$f[]="{send_first_the_itcharter}";
	}
	
if($SMS_REGISTER==0){	
	if($DO_NOT_AUTENTICATE==0){
		if($ENABLED_AUTO_LOGIN==1){
			$f[]="{$andadd_text}{allow_user_to_be_selfregistred}";
			if($ArticaHotSpotNowPassword==1){$f[]="{without_need_to_set_password}";}
			if($ENABLED_REDIRECT_LOGIN==1){
				if($MACWHITE==0){
					$f[]="{and} {force_user_to_register_again_after_expired_session}";
				}
			}
		}
		
		
		if($ENABLED_REDIRECT_LOGIN==0){
			if($USE_ACTIVEDIRECTORY==1){
				$f[]="$andadd_text{authenticate_trough_activedirectory}";
				
			}
		
			$f[]="{andor} {authenticate_trough_local_database}";
		}
	}else{
		$f[]="{just_ask_an_username_hotspot}";
		
	}
}else{
	
	$f[]="{$andadd_text}{authenticate_users_with_sms}";
	
}
	
	$CLOSE=false;
	
	if($ArticaSplashHotSpotCacheAuth>0){
		if($MACWHITE==0){
			$T[]="{close_session_each}: {$ArticaSplashHotSpotCacheAuth} {minutes}";
		}
		$CLOSE=true;
	}
	
	if($LIMIT_BY_SIZE>0){
		$or=null;
		if($MACWHITE==0){
			if($CLOSE){$or="{or} ";}
			$T[]="$or{close_session_each} {downloaded} {$LIMIT_BY_SIZE} MB";
			$CLOSE=true;
		}
	}
	
	if(!$CLOSE){
		if($MACWHITE==1){$T[]="{save_computer_in_whitelist} {and} ";$ArticaSplashHotSpotEndTime=0;}
		$T[]="{never_close_session}";
	}
	if($ArticaSplashHotSpotEndTime>0){
		
		$T[]="{and} {delete_the_account_after}: {$ArticaSplashHotSpotEndTime} {minutes}";
	}else{
		$T[]="{and} {never_delete_the_account}";
	}
	
	if($BOUNCE_AUTH==1){
		$T[]="{and} <strong>{bounce_if_already_authenticated}</strong>";
	}
	
	if($TOS_VALUE>0){
		$T[]="{and} <strong>{tcp_outgoing_tos} {$ayDscp[$TOS_VALUE]}</strong>";
	}
	
	$f[]=@implode(" ", $T);
	
	if($LANDING_PAGE<>null){
		$f[]="{after_login_redirect_user_to}: $LANDING_PAGE";
		
	}
	
	$WifidogClientTimeout_text=$Timez[$WifidogClientTimeout];
	if($MACWHITE==1){$WifidogClientTimeout_text=null;}
	
	$f[]="<strong>{service_will_globally_force_users_to_reauth_each} <a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('$page?WifidogClientTimeout-js=yes');\"
	style='text-decoration:underline;font-weight:bold'>$WifidogClientTimeout_text</a></strong>";
	
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body(@implode("<br>", $f));
	
}


function send_picture(){
	
	if($GLOBALS["VERBOSE"]){echo "{$_GET["picture"]}<br>\n";}
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webauth_rules WHERE zmd5='{$_GET["picture"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error;}
	}
	
	$path_parts = pathinfo($ligne["picturename"]);
	$ext=$path_parts['extension'];
	
	if($GLOBALS["VERBOSE"]){echo "{$ligne["picturename"]} - > $ext<br>\n";}
	
	switch ($ext) {
	case "gif":$ctype="image/gif";break;
	case "png": $ctype="image/png"; break;
	case "jpeg": $ctype="image/jpg";break;
	case "jpg": $ctype="image/jpg";;break;
	}
	
	
	$fsize=strlen($ligne["picture"]);
	if($GLOBALS["VERBOSE"]){echo "{$ligne["picturename"]} - > {$fsize}Bytes<br>\n";}
	
	if($GLOBALS["VERBOSE"]){return;}
	header("Content-Type: $ctype");
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $ligne["picture"];
	return true;
}