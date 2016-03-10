<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');




$usersmenus=new usersMenus();
if($usersmenus->AsSystemAdministrator==false){exit;}

if(isset($_POST["FireQOS"])){save();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$nic=$_GET["nic"];
	$eth=new system_nic($nic);
	$sock=new sockets();
	
	if(intval($sock->getFrameWork("firehol.php?is-installed=yes"))==0){
		echo $tpl->_ENGINE_parse_body("<p class=text-error style='font-size:18px'>{firewall_package_is_not_installed}</p>");
		die();
	}
	
	$p=Paragraphe_switch_img("{enable_qos_fornic}", "{enable_qos_fornic_explain}",
			"FireQOS-$t",$eth->FireQOS,null,585);
	
	if($eth->InputSpeed==0){$eth->InputSpeed=100000;}
	if($eth->OutputSpeed==0){$eth->OutputSpeed=100000;}
	
	$modemType[null]="[LAN] Switch/Hub/Router";
	$modemType["adsl:pppoe-llc"]="[ADSL] PPPoE LLC/SNAP";
	$modemType["adsl:pppoe-vcmux"]="[ADSL] PPPoE VC/Mux";
	$modemType["adsl:pppoa-llc"]="[ADSL] PPPoA LLC/SNAP";
	$modemType["adsl:pppoa-vcmux"]="[ADSL] PPPoA VC/Mux";
	$modemType["adsl:ipoa-llc"]="[ADSL] IPoA LLC/SNAP";
	$modemType["adsl:ipoa-vcmux"]="[ADSL] IPoA VC/Mux";
	$modemType["adsl:bridged-llc"]="[ADSL] Bridged LLC/SNAP";
	$modemType["adsl:bridged-vcmux"]="[ADSL] Bridged VC/Mux";
	
	$UNITS["kbit"]="(Kbit/Kbps) kilobits per second";
	$UNITS["bps"]="(bps) Bytes per second";
	$UNITS["mbps"]="(mbps) Megabytes per second";
	$UNITS["gbps"]="(gbps) gigabytes per second";
	$UNITS["bit"]="(bits) per second";
	$UNITS["mbit"]="(Mbit) megabits per second";
	$UNITS["gbit"]="(Gbit) gigabits per second";
	
	
	
	$html="
	<div style='width:98%' class=form>	
	$p
	<div style='margin:20px;font-size:18px' class=explain>{FireQOS_interface_explain}</div>
			
			
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{ModemType}:</td>
		<td style='font-size:22px'>". Field_array_Hash($modemType,"ModemType-$t",$eth->ModemType,"style:font-size:22px")."</td>
	</tr>	
	

	
	<tr>
		<td class=legend style='font-size:22px'>{download_speed}:</td>
		<td style='font-size:22px'>". Field_text("InputSpeed-$t",$eth->InputSpeed,"font-size:22px;width:150px")."&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{upload_speed}:</td>
		<td style='font-size:22px'>". Field_text("OutputSpeed-$t",$eth->OutputSpeed,"font-size:22px;width:150px")."&nbsp;</td>
	</tr>	
				
	<tr>
		<td class=legend style='font-size:22px'>{unit}:</td>
		<td style='font-size:22px'>". Field_array_Hash($UNITS,"SpeedUnit-$t",$eth->SpeedUnit,"style:font-size:22px")."</td>
	</tr>					
	
	<tr><td colspan=2 align='right' style='padding-top:30px'>". button("{apply}", "Save$t()",30)."</td></tr>
			
	</table>
	</div>
<script>			
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjaxRound('system-main-status','admin.dashboard.system.php');
}		
	
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nic','$nic');
	XHR.appendData('FireQOS',document.getElementById('FireQOS-$t').value);
	XHR.appendData('InputSpeed',document.getElementById('InputSpeed-$t').value);
	XHR.appendData('OutputSpeed',document.getElementById('OutputSpeed-$t').value);
	XHR.appendData('ModemType',document.getElementById('ModemType-$t').value);
	XHR.appendData('SpeedUnit',document.getElementById('SpeedUnit-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}	
</script>				
			
";
echo $tpl->_ENGINE_parse_body($html);
		
}

function save(){
	
	$eth=new system_nic($_POST["nic"]);
	$eth->FireQOS=$_POST["FireQOS"];
	$eth->InputSpeed=$_POST["InputSpeed"];
	$eth->OutputSpeed=$_POST["OutputSpeed"];
	$eth->ModemType=$_POST["ModemType"];
	$eth->SpeedUnit=$_POST["SpeedUnit"];
	$eth->SaveNic();
	
}

