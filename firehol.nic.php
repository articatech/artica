<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.firehol.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if($usersmenus->AsSystemAdministrator==false){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["settings"])){page();exit;}
if(isset($_GET["nic-status"])){nic_status();exit;}
if(isset($_GET["nic-params"])){nic_params();exit;}
if(isset($_GET["interfaces"])){search();exit;}
if(isset($_POST["firewall_policy"])){nic_params_save();exit;}
if(isset($_GET["graph-rx"])){graph_rx();exit;}
if(isset($_GET["graph-tx"])){graph_tx();exit;}

tabs();


function tabs(){

	$t=time();
	$page=CurrentPageName();
	$nic=new system_nic($_GET["nic"]);
	$tpl=new templates();
	$users=new usersMenus();
	$array["settings"]="$nic->NICNAME $nic->netzone ({$_GET["nic"]})";
	$array["rules"]="{rules}";
	$array["services"]="{services}";
	$array["firehol_client_services"]="{local_services}";
	
	$fontsize=22;

	while (list ($num, $ligne) = each ($array) ){

		if($num=="services"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"firehol.nic.services.php?table=yes&xtable=firehol_services&nic={$_GET["nic"]}\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"firehol.nic.rules.php?eth={$_GET["nic"]}\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		if($num=="firehol_client_services"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"firehol.nic.services.php?table=yes&xtable=firehol_client_services&nic={$_GET["nic"]}\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&nic={$_GET["nic"]}\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");

	}

	echo build_artica_tabs($html, "firehol_{$_GET["nic"]}_tabs")."<script>LeftDesign('transparent-256-opac20.png');</script>";


}



function page(){
	$page=CurrentPageName();
	$nic=new system_nic($_GET["nic"]);
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>$nic->NICNAME $nic->netzone ({$_GET["nic"]})</div>	
	<table style='width:100%'>
	<td valign='top' style='width:400px'>
		<div id='NIC_STATUS'></div>
	</td>
	<td valign='top' style='width:1100px;padding-left:20px'>
		<div id='NIC_MIDDLE'></div>
	</td>
	</table>
	<script>
		LoadAjaxRound('NIC_STATUS','$page?nic-status=yes&eth={$_GET["nic"]}');
		LoadAjaxRound('NIC_MIDDLE','$page?nic-params=yes&eth={$_GET["nic"]}');
	</script>
	";
	echo $html;
	
	
	
	
}

function nic_status(){
	$nic=new system_nic($_GET["eth"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td valign='middle' style='font-size:18px' class=legend>{ipaddr}:</td>
			<td style='font-size:18px;font-weight:bold'>$nic->IPADDR</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:18px'>{netmask}:</td>
			<td style='font-size:18px;font-weight:bold'>$nic->NETMASK</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:18px'>{gateway}:</td>
			<td style='font-size:18px;font-weight:bold'>$nic->GATEWAY</td>
		</tr>
		<tr>
			<td style='font-size:18px;font-weight:bold;padding-top:20px' colspan=2 align='right'>". button("{modify}","Loadjs('system.nic.edit.php?nic={$_GET["eth"]}')",18)."</td>
		</tr>	
	</table>
	</div>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function nic_params(){
	$nic=new system_nic($_GET["eth"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$BEHA["reject"]="{strict_mode}";
	$BEHA["accept"]="{trusted_mode}";
	$jsload="Loadjs('$page?graph-rx=yes&nic={$_GET["eth"]}')";
//	$jsload2="Loadjs('$page?graph-rx=yes&nic={$_GET["eth"]}')";
	
	$BEHA2[0]="{not_defined}";
	$BEHA2[1]="{act_as_lan}";
	$BEHA2[2]="{act_as_wan}";
	
	$masquerade[0]="{none}";
	$masquerade[1]="{masquerading}";
	$masquerade[2]="{masquerading_invert}";
	$EnableIpBlocks=intval($sock->GET_INFO("EnableIpBlocks"));
	$IPSetInstalled=intval($sock->GET_INFO("IPSetInstalled"));
	
	if($IPSetInstalled==1){
		$EnableIpBlocks_explain=" &laquo;".texttooltip("{block_countries}","{ipblocks_text}","GotoIpBlocks()")."&raquo;";
		
	}
	
	if($EnableIpBlocks==0){
		$EnableIpBlocks_field="{disabled}$EnableIpBlocks_explain";
	}else{
		$EnableIpBlocks_field=Field_checkbox_design("DenyCountries-$t", 1,$nic->DenyCountries);
	}
	
	
	if(!is_file("{$GLOBALS["BASEDIR"]}/FLUX_{$_GET["eth"]}_RX")){$jsload=null;}
	
	$html="<div style='width:98%;margin-bottom:15px' class=form>
	<table style='width:100%'>
	<tr>
		<tr>
		<td class=legend style='font-size:22px'>{firewall_policy}:</td>
		<td>". Field_array_Hash($BEHA, "firewall_policy-$t",$nic->firewall_policy,"style:font-size:22px")."</td>
	</tr>
	<tr>
		<tr>
		<td class=legend style='font-size:22px'>{firewall_behavior}:</td>
		<td>". Field_array_Hash($BEHA2, "firewall_behavior-$t",$nic->firewall_behavior,"style:font-size:22px")."</td>
	</tr>	
	<tr>
		<tr>
		<td class=legend style='font-size:22px'>{masquerading}:</td>
		<td>". Field_array_Hash($masquerade, "firewall_masquerade-$t",$nic->firewall_masquerade,"style:font-size:22px")."</td>
	</tr>
	<tr>
	<tr>
		<td class=legend style='font-size:22px'>{enable_ipblocks}:</td>
		<td style='font-size:22px'>$EnableIpBlocks_field</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{accept_artica_w}:</td>
		<td style='font-size:22px'>".Field_checkbox_design("firewall_artica-$t", 1,$nic->firewall_artica)."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
	</tr>	
	</table>
	</div>
	<div style='width:1090px;height:300px' id='{$_GET["eth"]}-rx'></div>
	<div style='width:1090px;height:300px' id='{$_GET["eth"]}-tx'></div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	LoadAjaxRound('main-fw-nic-rules','$page?nic={$_GET["eth"]}');
	Loadjs('firehol.progress.php');
}


function Save$t(){
	var XHR = new XHRConnection();
	var DenyCountries=0;
	XHR.appendData('firewall_policy',document.getElementById('firewall_policy-$t').value);
	XHR.appendData('firewall_behavior',document.getElementById('firewall_behavior-$t').value);
	XHR.appendData('firewall_masquerade',document.getElementById('firewall_masquerade-$t').value);
	XHR.appendData('firewall_artica',document.getElementById('firewall_artica-$t').value);
	
	
	if(document.getElementById('DenyCountries-$t')){
		if(document.getElementById('DenyCountries-$t').checked){DenyCountries=1;}
	}
	XHR.appendData('DenyCountries',DenyCountries);
	XHR.appendData('nic','{$_GET["eth"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	
$jsload				
</script>

	
";
	

	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
	
}

function graph_rx(){
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/FLUX_{$_GET["nic"]}_RX"));
	$tpl=new templates();
	
	$title="{$_GET["nic"]} {reception_flow_this_day} (MB)";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container="{$_GET["nic"]}-rx";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{today}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
	if(is_file("{$GLOBALS["BASEDIR"]}/FLUX_{$_GET["nic"]}_TX")){
		$page=CurrentPageName();
		echo "\nLoadjs('$page?graph-tx=yes&nic={$_GET["nic"]}');\n";
	
	}	
	
}

function graph_tx(){
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/FLUX_{$_GET["nic"]}_TX"));
	$tpl=new templates();
	
	$title="{$_GET["nic"]} {transmission_flow_this_day} (MB)";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container="{$_GET["nic"]}-tx";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{today}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
	
}

function nic_params_save(){
	$nic=new system_nic($_POST["nic"]);
	$nic->firewall_policy=$_POST["firewall_policy"];
	$nic->firewall_behavior=$_POST["firewall_behavior"];
	$nic->firewall_masquerade=$_POST["firewall_masquerade"];
	$nic->firewall_artica=$_POST["firewall_artica"];
	$nic->DenyCountries=$_POST["DenyCountries"];
	$nic->SaveNic();
}
