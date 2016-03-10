<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once("ressources/class.os.system.inc");
include_once("ressources/class.lvm.org.inc");
include_once("ressources/class.autofs.inc");

$user=new usersMenus();
if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
if(isset($_POST["DisksBenchs"])){Save();exit; }
page();

function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$DirectoriesMonitorH=intval($sock->GET_INFO("DirectoriesMonitorH"));
	$DirectoriesMonitorM=intval($sock->GET_INFO("DirectoriesMonitorM"));
	$DisksBenchs=intval($sock->GET_INFO("DisksBenchs"));
	$t=time();
	
	
	for($i=0;$i<24;$i++){
		$H=$i;
		if($i<10){$H="0$i";}
		$Hours[$i]=$H;
	}
	
	for($i=0;$i<60;$i++){
		$M=$i;
		if($i<10){$M="0$i";}
		$Mins[$i]=$M;
	}	
	
	$EACH[0]="{never}";
	$EACH[3]="{each}: 3 {hours}";
	$EACH[4]="{each}: 4 {hours}";
	$EACH[5]="{each}: 5 {hours}";
	$EACH[6]="{each}: 6 {hours}";
	$EACH[12]="{each}: 12 {hours}";
	$EACH[24]="{each}: 1 {day}";
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr style='height:90px'>
		<td class=legend style='font-size:22px'>". texttooltip("{disks_benchmarks}","{disks_benchmarks_explain}")."</td>
		<td>".Field_array_Hash($EACH, "DisksBenchs",$DisksBenchs,"style:font-size:22px")."</td>
	</tr>

	<tr style='height:90px'>
		<td class=legend style='font-size:22px'>". texttooltip("{scan_filesystem_size}","{scan_filesystem_size_explain}")."</td>
		<td style='font-size:22px' colspan=2>
				<table style='width:135px'>
				<tr>
					<td style='font-size:22px'>".Field_array_Hash($Hours, "DirectoriesMonitorH",$DirectoriesMonitorH,"style:font-size:22px;padding:10px")."</td>
					<td style='font-size:22px'>:</td>
					<td style='font-size:22px'>".Field_array_Hash($Mins, "DirectoriesMonitorM",$DirectoriesMonitorM,"style:font-size:22px;padding:10px")."</td>
				</tr>
				</table>
		</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t();",30)."</td>
	</tr>
	</table>
	</div>
<script>
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	RefreshTab('btrfs-tabs');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('DisksBenchs',document.getElementById('DisksBenchs').value);
	XHR.appendData('DirectoriesMonitorH',document.getElementById('DirectoriesMonitorH').value);
	XHR.appendData('DirectoriesMonitorM',document.getElementById('DirectoriesMonitorM').value);
    XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>				
	";	
	
echo $tpl->_ENGINE_parse_body($html);
	
}
function Save(){
	$sock=new sockets();
	$sock->SET_INFO("DisksBenchs", $_POST["DisksBenchs"]);
	$sock->SET_INFO("DirectoriesMonitorH", $_POST["DirectoriesMonitorH"]);
	$sock->SET_INFO("DirectoriesMonitorM", $_POST["DirectoriesMonitorM"]);
	$sock->getFrameWork("system.php?DirectoriesMonitorSchedules=yes");
	
}
