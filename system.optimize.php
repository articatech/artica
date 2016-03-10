<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');


$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
if(isset($_POST["EnableSystemOptimize"])){EnableSystemOptimize();exit;}
if(isset($_POST["EnableIntelCeleron"])){EnableIntelCeleron();exit;}
if(isset($_POST["cgroupsEnabled"])){cgroupsEnabled();exit;}
if(isset($_GET["popup"])){popup();exit();}

tabs();

function tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{system_optimization}';
	$fontsize=22;
	while (list ($num, $ligne) = each ($array) ){
	
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	echo build_artica_tabs($tab, "main_system_optimize",1490)."<script>LeftDesign('optimize-256.png');</script>";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnableSystemOptimize=intval($sock->GET_INFO("EnableSystemOptimize"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$cgroupsEnabled=intval($sock->GET_INFO("cgroupsEnabled"));
	$EnableIRQBalance=intval($sock->GET_INFO("EnableIRQBalance"));
	
	$CPUSHARE[102]="10%";
	$CPUSHARE[204]="20%";
	$CPUSHARE[256]="25%";
	$CPUSHARE[307]="30%";
	$CPUSHARE[512]="50%";
	$CPUSHARE[620]="60%";
	$CPUSHARE[716]="70%";
	$CPUSHARE[819]="80%";
	$CPUSHARE[921]="90%";
	$CPUSHARE[1024]="100%";
	
	
	$BLKIO[100]="10%";
	$BLKIO[200]="20%";
	$BLKIO[250]="25%";
	$BLKIO[300]="30%";
	$BLKIO[450]="45%";
	$BLKIO[500]="50%";
	$BLKIO[700]="70%";
	$BLKIO[800]="80%";
	$BLKIO[900]="90%";
	$BLKIO[1000]="100%";
	
	
	
	$cgroupsPHPCpuShares=intval($sock->GET_INFO("cgroupsPHPCpuShares"));
	$cgroupsPHPDiskIO=intval($sock->GET_INFO("cgroupsPHPDiskIO"));
	if($cgroupsPHPCpuShares==0){$cgroupsPHPCpuShares=256;}
	if($cgroupsPHPDiskIO==0){$cgroupsPHPDiskIO=450;}
	
	$cgroupsMySQLCpuShares=intval($sock->GET_INFO("cgroupsMySQLCpuShares"));
	$cgroupMySQLDiskIO=intval($sock->GET_INFO("cgroupsMySQLDiskIO"));
	if($cgroupsMySQLCpuShares==0){$cgroupsMySQLCpuShares=620;}
	if($cgroupMySQLDiskIO==0){$cgroupMySQLDiskIO=800;}
	

	if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
		$sock=new sockets();
		$CPU_NUMBER=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
	}else{
		$CPU_NUMBER=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
	}
	
	
	$cgroupsEnabled_paragraph=Paragraphe_switch_img("{enable_processes_limitation}",
			"{enable_processes_limitation_explain}","cgroupsEnabled",$cgroupsEnabled,null,1200);
	
	
	
	$t=time();
	$html="
	<div style='font-size:40px;margin-bottom:20px'>{system} $CPU_NUMBER CPU(s)&nbsp;&nbsp;|&nbsp;&nbsp;". button("{refresh_system_information}","Loadjs('system.refreshcpu.progress.php')",30)."&nbsp;&nbsp|&nbsp;&nbsp</div>		
	<div style='width:98%' class=form>
			
	". Paragraphe_switch_img("{enable_system_optimization}", 
			"{enable_system_optimization_text}","EnableSystemOptimize",$EnableSystemOptimize,null,1200)."
	<div style='margin-top:25px;text-align:right'>". button("{apply}","Save$t()",40)."</div>
	</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	". Paragraphe_switch_img("{enable_intel_celeron}", 
			"{enable_intel_celeron_text}","EnableIntelCeleron",$EnableIntelCeleron,null,1200)."
		<div style='margin-top:25px;text-align:right;font-size:40px'>
					
					". button("{apply}","Save2$t()",40)."</div>
	
</div><p>&nbsp;</p>
<div style='width:98%' class=form>
	". Paragraphe_switch_img("{EnableIRQBalance}", 
			"{EnableIRQBalance_text}","EnableIRQBalance",$EnableIRQBalance,null,1200)."
	<div style='margin-top:25px;text-align:right'>". button("{apply}","Save2$t()",40)."</div>
</div><p>&nbsp;</p>

	
	
<div style='width:98%' class=form>
	$cgroupsEnabled_paragraph
	<p>&nbsp;</p>
	<table style='width:100%'>
	<tr><td colspan=2 style='font-size:28px'>{artica_processes}</td></tr>
	<tr>
		<td class=legend style='font-size:22px'>{cpu_performance}:</td>
		<td>".Field_array_Hash($CPUSHARE, "cgroupsPHPCpuShares",$cgroupsPHPCpuShares,"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{disk_performance}:</td>
		<td>".Field_array_Hash($BLKIO, "cgroupsPHPDiskIO",$cgroupsPHPDiskIO,"style:font-size:22px")."</td>
	</tr>
	<tr><td colspan=2><p>&nbsp;</p></td></tr>
	<tr><td colspan=2 style='font-size:28px'>{MySQL_performance}</td></tr>
	<tr>
		<td class=legend style='font-size:22px'>{cpu_performance}:</td>
		<td>".Field_array_Hash($CPUSHARE, "cgroupsMySQLCpuShares",$cgroupsMySQLCpuShares,"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{disk_performance}:</td>
		<td>".Field_array_Hash($BLKIO, "cgroupMySQLDiskIO",$cgroupMySQLDiskIO,"style:font-size:22px")."</td>
	</tr>	
	</table>				
	<div style='margin-top:25px;text-align:right'>". button("{apply}","Save3$t()",40)."</div>	
</div>	
	
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('system.optimize.progress.php');
}	
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSystemOptimize',document.getElementById('EnableSystemOptimize').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

var xSave2$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('system.optimize.celeron.php');
}
var xSave3$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	
}
function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableIntelCeleron',document.getElementById('EnableIntelCeleron').value);
	XHR.appendData('EnableIRQBalance',document.getElementById('EnableIRQBalance').value);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
}

function SaveIntelCeleron(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableIntelCeleron',document.getElementById('EnableIntelCeleron').value);
	XHR.appendData('EnableIRQBalance',document.getElementById('EnableIRQBalance').value);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
}

function RefreshHardware$t(){


}

function Save3$t(){
	var XHR = new XHRConnection();
	XHR.appendData('cgroupsEnabled',document.getElementById('cgroupsEnabled').value);
	XHR.appendData('cgroupsPHPCpuShares',document.getElementById('cgroupsPHPCpuShares').value);
	XHR.appendData('cgroupsPHPDiskIO',document.getElementById('cgroupsPHPDiskIO').value);
	
	XHR.appendData('cgroupMySQLDiskIO',document.getElementById('cgroupMySQLDiskIO').value);
	XHR.appendData('cgroupsMySQLCpuShares',document.getElementById('cgroupsMySQLCpuShares').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave3$t);
}
</script>			
</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function cgroupsEnabled(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST)){
		$sock->SET_INFO($num, $ligne);
	
	}
	$sock->getFrameWork("cgroup?cgconfig=yes");
}


function EnableSystemOptimize(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSystemOptimize", $_POST["EnableSystemOptimize"]);
	
	
}

function EnableIntelCeleron(){
	$sock=new sockets();
	$sock->SET_INFO("EnableIntelCeleron", $_POST["EnableIntelCeleron"]);
	$sock->SET_INFO("EnableIRQBalance", $_POST["EnableIRQBalance"]);
}

