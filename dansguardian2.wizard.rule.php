<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.ActiveDirectory.inc');




if(isset($_GET["step0"])){step0();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_GET["step3"])){step3();exit;}
if(isset($_GET["step4"])){step4();exit;}
if(isset($_GET["step5"])){step5();exit;}
if(isset($_POST["SOURCE_TYPE"])){Save();exit;}
if(isset($_POST["TYPE_VALUE"])){Save();exit;}
if(isset($_POST["CATZ"])){Save();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$compile_rules=$tpl->_ENGINE_parse_body("{wizard_rule}");
	echo "YahooWin5('840','$page?step0=yes','$compile_rules',true)";	
	
	
}

function step0(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	$array=$q->WebFilteringAllSystems();
	if(count($array)>0){
		echo FATAL_ERROR_SHOW_128("{webfiltering_all_system_warning}<br>".@implode("\n", $array));
		return;
			
	}
	
	
	$t=time();
	echo "<div id='main-$t'></div>
	<script>
		LoadAjax('main-$t','$page?step1=yes&t=$t',false);
	</script>
	";


}



function step1(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$ldap=new clladp();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
	$ARRAY["ALL"]="{AllSystems}";
	$ARRAY["IPADDR"]="{ipaddr}";
	if($ldap->IsKerbAuth()){
		$ARRAY["AD"]="{ActiveDirectory}";
	}
	$t=time();
	$html="
<div style='font-size:30px;margin-bottom:20px'>{wizard_rule}</div>
<div class=explain style='font-size:20px'>{wizard_rule_ufdb_1}</div>	
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{source}:</td>
	<td>&nbsp;</td>
	<td>". Field_array_Hash($ARRAY,"SOURCE_TYPE-$t",$WizardUFDB["SOURCE_TYPE"],"style:font-size:22px")."</td>
</tr>
	<tr>
		<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
	</tr>	
	<tr>
		<td align='left'>&nbsp;</td>
		<td>&nbsp;</td>
		<td align='right'>". button("{next}","Save$t()","28px")."</td>
	</tr>			
</table>			
</div>			
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	LoadAjax('main-{$_GET["t"]}','$page?step2=yes&t={$_GET["t"]}');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SOURCE_TYPE',document.getElementById('SOURCE_TYPE-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function step2(){
	$sock=new sockets();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));	
	if($WizardUFDB["SOURCE_TYPE"]=="ALL"){step3();exit;}
	if($WizardUFDB["SOURCE_TYPE"]=="IPADDR"){step2_IPADDR();exit;}
	if($WizardUFDB["SOURCE_TYPE"]=="AD"){step2_AD();exit;}
	
	echo "????";
	
}

function step2_IPADDR(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
	$html="
<div style='font-size:30px;margin-bottom:20px'>{ipaddr}</div>
<div class=explain style='font-size:20px'>{wizard_rule_ufdb_2}</div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{ipaddr}:</td>
	<td>&nbsp;</td>
	<td>". field_ipv4("IPADDR-$t",$WizardUFDB["TYPE_VALUE"],"font-size:18px")."</td>
</tr>
	<tr>
		<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
	</tr>
	<tr>
		<td align='left'>". button("{back}","LoadAjax('main-$t','$page?step1=yes&t={$_GET["t"]}',false);","28px")."</td>
		<td>&nbsp;</td>
		<td align='right'>". button("{next}","Save$t()","28px")."</td>
	</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	LoadAjax('main-{$_GET["t"]}','$page?step3=yes&t={$_GET["t"]}');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TYPE_VALUE',document.getElementById('IPADDR-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function step2_AD(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
	
	$TYPE_VALUE=$WizardUFDB["TYPE_VALUE"];
	if(preg_match("#^AD:([0-9]+):(.+)#", $TYPE_VALUE,$re)){
		
		$TYPE_VALUE=base64_decode($re[2]);
		if(preg_match("#^cn=(.+?),#i", $TYPE_VALUE,$re)){$TYPE_VALUE=$re[1];}
	}
		
	$html="
<div style='font-size:30px;margin-bottom:20px'>{ActiveDirectory}</div>
<div class=explain style='font-size:20px'>{wizard_rule_ufdb_ad}</div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{group}:<span id='AD-TEXT-$t' style='font-weight:bold'>{$TYPE_VALUE}</span></td>
	<td>".button("{browse}..","Loadjs('browse-ad-groups.php?field-user=AD-$t&field-type=2&CallBack2=CallBack$t')",18)."</td>
	<td>". Field_hidden("AD-$t",$WizardUFDB["TYPE_VALUE"])."</td>
</tr>
	<tr>
		<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
	</tr>
	<tr>
		<td align='left'>". button("{back}","LoadAjax('main-$t','$page?step1=yes&t={$_GET["t"]}',false);","28px")."</td>
		<td>&nbsp;</td>
		<td align='right'>". button("{next}","Save$t()","28px")."</td>
	</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	LoadAjax('main-{$_GET["t"]}','$page?step3=yes&t={$_GET["t"]}');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TYPE_VALUE',document.getElementById('AD-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function CallBack$t(base64,Name){
	document.getElementById('AD-TEXT-$t').innerHTML=Name+'&nbsp;&nbsp';
	YahooWinBrowseHide();
}

</script>	";
	echo $tpl->_ENGINE_parse_body($html);

}



function step3(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
$ARRAY[0]="{block_unproductive_websites}";	
$ARRAY[1]="{block_sexual_websites}";
$ARRAY[2]="{block_susp_websites}";
$ARRAY[3]="{block_multi_websites}";

$sock=new sockets();
$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
if(!is_numeric($WizardUFDB["CATZ"])){$WizardUFDB["CATZ"]=0;}
	
	$html="
<div style='font-size:30px;margin-bottom:20px'>{categories}</div>
<div class=explain style='font-size:20px'>{wizard_rule_ufdb_3}</div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{categories}:</td>
	<td>&nbsp;</td>
	<td>". Field_array_Hash($ARRAY,"CATZ-$t",$WizardUFDB["CATZ"],"style:font-size:26px")."</td>
</tr>
<tr>
	<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
</tr>
<tr>
	<td align='left'>". button("{back}","LoadAjax('main-$t','$page?step2=yes&t={$_GET["t"]}',false);","28px")."</td>
	<td>&nbsp;</td>
	<td align='right'>". button("{next}","Save$t()","28px")."</td>
</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	LoadAjax('main-{$_GET["t"]}','$page?step4=yes&t={$_GET["t"]}');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('CATZ',document.getElementById('CATZ-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function step4(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	
	$ldap=new clladp();
	$ARRAY1["ALL"]="{AllSystems}";
	$ARRAY1["IPADDR"]="{ipaddr}";
	if($ldap->IsKerbAuth()){
		$ARRAY1["AD"]="{ActiveDirectory}";
	}
	
$ARRAY[0]="{block_unproductive_websites}";	
$ARRAY[1]="{block_sexual_websites}";
$ARRAY[2]="{block_susp_websites}";
$ARRAY[3]="{block_multi_websites}";
	$sock=new sockets();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
	if(!is_numeric($WizardUFDB["CATZ"])){$WizardUFDB["CATZ"]=0;}
	if($WizardUFDB["SOURCE_TYPE"]=="ALL"){
		$WizardUFDB["TYPE_VALUE"]=null;
	}
	
	if($WizardUFDB["SOURCE_TYPE"]=="AD"){
		$dndata=$WizardUFDB["TYPE_VALUE"];
		if(preg_match("#AD:(.*?):(.+)#", $WizardUFDB["TYPE_VALUE"],$re)){
			$dnEnc=$re[2];
			$LDAPID=$re[1];
		}
		$GPS["localldap"]=2;
		$GPS["gpid"]=0;
		$GPS["dn"]=$dndata;
		$ACtiveDir=new ActiveDirectory($LDAPID);
		$array=$ACtiveDir->ObjectProperty(base64_decode($dnEnc));
		$WizardUFDB["TYPE_VALUE"]=$array["cn"];
	}	
	
	$html="
<div style='font-size:30px;margin-bottom:20px'>{build_the_rule}</div>
<div class=explain style='font-size:20px'>{wizard_rule_ufdb_4}</div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:26px'>{$ARRAY1[$WizardUFDB["SOURCE_TYPE"]]}:</td>
	<td>&nbsp;</td>
	<td style='font-size:26px'>{$WizardUFDB["TYPE_VALUE"]}</td>
</tr>
<tr>
	<td class=legend style='font-size:26px'>{categories}:</td>
	<td>&nbsp;</td>
	<td style='font-size:26px'>{$ARRAY[$WizardUFDB["CATZ"]]}</td>
</tr>
<tr>
	<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
</tr>
<tr>
	<td align='left'>". button("{back}","LoadAjax('main-$t','$page?step3=yes&t={$_GET["t"]}',false);","28px")."</td>
	<td>&nbsp;</td>
	<td align='right'>". button("{build_the_rule}","Save$t()","28px")."</td>
</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	LoadAjax('main-{$_GET["t"]}','$page?step5=yes&t={$_GET["t"]}');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ACCEPT','yes');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function step5(){
	$allsystems=0;
	
	$final="<script>
			YahooWin5Hide();
			Loadjs('dansguardian2.compile.php');
			CacheOff();
			LoadAjaxRound('main-ufdb-frontend','ufdbguard.status.php');
				
		</script>";
		
	
	$tpl=new templates();
	$sock=new sockets();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
	if($WizardUFDB["SOURCE_TYPE"]=="ALL"){$allsystems=1;}
	
	$ARRAY[0]="{block_sexual_websites}";
	$ARRAY[1]="{block_susp_websites}";
	$ARRAY[2]="{block_multi_websites}";
	
	
	$wizard_Name=strtolower('Wizard - rule '.$tpl->javascript_parse_text($ARRAY[$WizardUFDB["CATZ"]]));
	$wizard_Name_tests=addslashes(utf8_encode($wizard_Name));
	$q=new mysql_squid_builder();
	$sql="SELECT ID FROM webfilter_rules WHERE `groupname`='$wizard_Name_tests'";
	$results=$q->QUERY_SQL($sql);
	$mysql_num_rows=intval(mysql_num_rows($results));
	if($mysql_num_rows>0 ){$wizard_Name="$wizard_Name - ".intval($mysql_num_rows+1); }
	
	
	$RULES["AllSystems"]=$allsystems;
	$RULES["ExternalWebPage"]=null;
	$RULES["UseExternalWebPage"]=0;
	$RULES["UseSecurity"]=	0;
	$RULES["bypass"]=	0;
	$RULES["enabled"]=	1;
	$RULES["endofrule"]='any';
	$RULES["freeweb"]='';
	$RULES["groupmode"]=1;
	$RULES["groupname"]=$wizard_Name;
	$RULES["zOrder"]=0;
	
	$fieldsAddA=array();
	$fieldsAddB=array();
	while (list ($num, $ligne) = each ($RULES) ){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".addslashes(utf8_encode($ligne))."'";
		$fieldsEDIT[]="`$num`='".addslashes(utf8_encode($ligne))."'";
		$DEFAULTARRAY[$num]=$ligne;
	}
	$sql_add="INSERT IGNORE INTO webfilter_rules (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";

	$q->QUERY_SQL($sql_add);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	$ruleid=$q->last_id;
	if($ruleid==0){echo "<p class=text-error>Fatal last ID = 0</p>";return;}
	
	
	
	$array["malware"]=true;
	$array["warez"]=true;
	$array["hacking"]=true;
	$array["phishing"]=true;
	$array["spyware"]=true;
	
	$array["weapons"]=true;
	$array["violence"]=true;
	$array["suspicious"]=true;
	$array["paytosurf"]=true;
	$array["sect"]=true;
	$array["proxy"]=true;
	$array["gamble"]=true;
	$array["redirector"]=true;
	
	if($WizardUFDB["CATZ"]==0){
		$array["porn"]=true;
		$array["agressive"]=true;
		$array["dynamic"]=true;
		
		$array["alcohol"]=true;
		$array["astrology"]=true;
		$array["dangerous_material"]=true;
		$array["drugs"]=true;
		$array["hacking"]=true;
		$array["tattooing"]=true;
		$array["terrorism"]=true;
		
		$array["dating"]=true;
		$array["mixed_adult"]=true;
		$array["sex/lingerie"]=true;
		$array["publicite"]=true;
		$array["tracker"]=true;
		$array["marketingware"]=true;
		$array["mailing"]=true;
		$array["downloads"]=true;
		$array["gamble"]=true;
	}	
	
	
	if($WizardUFDB["CATZ"]==1){
		$array["porn"]=true;
		$array["dating"]=true;
		$array["mixed_adult"]=true;
		$array["sex/lingerie"]=true;
	}
	if($WizardUFDB["CATZ"]==2){
		$array["publicite"]=true;
		$array["tracker"]=true;
		$array["marketingware"]=true;
		$array["mailing"]=true;
	}
	if($WizardUFDB["CATZ"]==3){
		$array["audio-video"]=true;
		$array["youtube"]=true;
		$array["webtv"]=true;
		$array["music"]=true;
		$array["movies"]=true;
		$array["games"]=true;
		$array["gamble"]=true;
		$array["socialnet"]=true;
		$array["webradio"]=true;
		$array["chat"]=true;
		$array["webphone"]=true;
		$array["downloads"]=true;
	}

	if(count($array)<2){echo "<p class=text-error>No category set</p>\n";return;}
	
	while (list ($key, $val) = each ($array) ){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$key' AND modeblk=0 AND webfilter_id='$ruleid'");
		$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ruleid','$key','0')");
		if(!$q->ok){echo $q->mysql_error_html();return;}
	}
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='liste_bu' AND modeblk=1 AND webfilter_id='$ruleid'");
	$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ruleid','liste_bu','1')");
	
	if($allsystems==1){echo $final;return; }
	
	
	$GPS["description"]="Wizard new group for<br>$wizard_Name";
	$GPS["enabled"]=1;
	$GPS["gpid"]=null;
	$GPS["groupname"]=mysql_escape_string2("Group: {$WizardUFDB["TYPE_VALUE"]}");
	
	if($WizardUFDB["SOURCE_TYPE"]=="IPADDR"){
		$GPS["localldap"]=1;
	}
	if($WizardUFDB["SOURCE_TYPE"]=="AD"){	
		$dndata=$WizardUFDB["TYPE_VALUE"];
		if(preg_match("#AD:(.*?):(.+)#", $WizardUFDB["TYPE_VALUE"],$re)){
			$dnEnc=$re[2];
			$LDAPID=$re[1];
		}
		$GPS["localldap"]=2;
		$GPS["gpid"]=0;
		$GPS["dn"]=$dndata;
		$ACtiveDir=new ActiveDirectory($LDAPID);
		$array=$ACtiveDir->ObjectProperty(base64_decode($dnEnc));
		$GPS["groupname"]=$array["cn"];
	}
	
	$fieldsAddA=array();
	$fieldsAddB=array();	
	$q=new mysql_squid_builder();
	while (list ($num, $ligne) = each ($GPS) ){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".addslashes(utf8_encode($ligne))."'";
		$fieldsEDIT[]="`$num`='".addslashes(utf8_encode($ligne))."'";
	
	}
	$sql_add="INSERT IGNORE INTO webfilter_group (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql_add);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	$gpid=$q->last_id;
	if($gpid==0){echo "<p class=text-error>Fatal:".__LINE__." last ID = 0</p>";return;}
	
	
	
	$md5=md5("$ruleid$gpid");
	
	$q->QUERY_SQL("INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5',$ruleid,$gpid)");
	if(!$q->ok){echo $q->mysql_error_html();return;}
	
	

	$PAT["enabled"]=1;
	$PAT["groupid"]=$gpid;	
	
	if($WizardUFDB["SOURCE_TYPE"]<>"IPADDR"){echo $final;return;}
		
		$PAT["membertype"]=1;
		if(preg_match("#(.+?)\/(.+)#", $WizardUFDB["TYPE_VALUE"])){	$PAT["membertype"]=2;}
		$PAT["pattern"]=$WizardUFDB["TYPE_VALUE"];
	
	
	$fieldsAddA=array();
	$fieldsAddB=array();
	$q=new mysql_squid_builder();
	while (list ($num, $ligne) = each ($GPS) ){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".addslashes(utf8_encode($ligne))."'";
		$fieldsEDIT[]="`$num`='".addslashes(utf8_encode($ligne))."'";
	
	}
	
	$sql_add="INSERT IGNORE INTO webfilter_members (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";
	$q->QUERY_SQL($sql_add);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	echo $final;
}


function Save(){
	$sock=new sockets();
	$WizardUFDB=unserialize(base64_decode($sock->GET_INFO("WizardUFDB")));
	while (list ($key, $val) = each ($_POST) ){
		$WizardUFDB[$key]=$val;
		
	}
	$sock->SaveConfigFile(base64_encode(serialize($WizardUFDB)), "WizardUFDB");
	$sock->SET_INFO("EnableUfdbGuard", 1);
	
}