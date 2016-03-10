<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["nat-section"])){nat_section();exit;}
if(isset($_GET["bridge-section"])){bridge_section();exit;}
if(isset($_GET["tasks-section"])){tasks_section();exit;}
if(isset($_GET["monitor-section"])){monitor_section();exit;}
if(isset($_GET["nics-section"])){nics_section();exit;}
if(isset($_GET["services-section"])){services_section();exit;}




page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$firehol_version=$sock->getFrameWork("firehol.php?firehol-version=yes");
	$t=time();
	$html="
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>{your_firewall} v.$firehol_version &laquo;&laquo;". texttooltip("{refresh}","{refresh}","FireWallDashBoardSequence()")."&raquo;</div>
	<div style='padding-left:30px;padding-right:30px'>	
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/nat-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{nat_title}</div>
				<div id='nat-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/router-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{bridges}</div>
				<div id='bridge-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>
<tr>
<tr style='height:70px'><td colspan=2>&nbsp;</td></tr>

		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/interfaces-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{network_interfaces}</div>
				<div id='nics-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/fw-services-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{rules_and_services}</div>
				<div id='services-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>	
<tr style='height:70px'><td colspan=2>&nbsp;</td></tr>	
	
	
	
	
	<tr style='height:30px'><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/tasks-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{tasks}</div>
				<div id='tasks-section' style='padding-left:15px'></div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/graph-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{monitor}</div>
				<div id='monitor-section-$t' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>	
	</table>
	</div>
	<script>
	
	function FireWallDashBoardSequence(){
		LoadAjaxRound('nat-section','$page?nat-section=yes');
		LoadAjaxRound('bridge-section','$page?bridge-section=yes');
		LoadAjaxRound('tasks-section','$page?tasks-section=yes');
		LoadAjaxRound('monitor-section-$t','$page?monitor-section=yes');
		LoadAjaxRound('nics-section','$page?nics-section=yes');
		LoadAjaxRound('services-section','$page?services-section=yes');
		
	}
	FireWallDashBoardSequence();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function tasks_section(){
	
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/32-stop.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{stop_firewall}",null,"Loadjs('firehol.progress.php?comand=stop')")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/start-32.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{start_firewall}",null,"Loadjs('firehol.progress.php?comand=start')")."</td>
	</tr>";	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/reconfigure-32.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{reconfigure_firewall}",null,"Loadjs('firehol.progress.php');")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/32-install-soft.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{reinstall_firewall}",null,"Loadjs('firehol.wizard.install.progress.php?ask=yes');")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/stop2-32.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{disable_firewall}",null,"Loadjs('firehol.wizard.disable.progress.php');")."</td>
	</tr>";	
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	
	
	
	
}


function cache_section(){
	
	$ahref_caches="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToCaches();\">";
	
}


function bridge_section(){
	
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	

	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{routers}","position:right:{dashboard_router_explain}","GotoRouters()")."</td>
	</tr>";
	
	$q=new mysql();
	if($q->TABLE_EXISTS("pnic_bridges", "artica_backup")){
		$sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne2 = mysql_fetch_assoc($results)) {
			$nic_from=$ligne2["nic_from"];
			$nic_to=$ligne2["nic_to"];
			$RouterName="{$nic_from}2{$nic_to}";
			
			$tr[]="<tr>
			<td valign='middle' style='width:25px'>
			<img src='img/$icon'>
			</td>
			<td valign='middle' style='font-size:18px;width:99%'>".
				texttooltip("{firewall_rules}: $nic_from {to} $nic_to",
						"position:right:{firewall_rules}: $nic_from {to} $nic_to","GotoFireholeRules('$RouterName')")."</td>
			</tr>";
			
			
		}
	}
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
}

function control_section(){
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("Active Directory","position:right:{dashboard_activedirectory_explain}","GoToActiveDirectory()")."</td>
	</tr>";
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	
	
	
}

function monitor_section(){
	$sock=new sockets();
	$tpl=new templates();
	
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	
	$text_ids=null;
	$icon_ids="arrow-right-24.png";
	$color_ids="black";
	$js_ids="GotoSuricata()";
	
	$EnableSuricata=intval($sock->GET_INFO("EnableSuricata"));
	
	if($EnableSuricata==0){
		$icon_ids="arrow-right-24-grey.png";
		$color_ids="#898989";
		$text_ids=" <span style='font-size:14px'>{disabled}</span>";
	}
	
	if(!is_file("/usr/bin/suricata")){
		$icon_ids="arrow-right-24-grey.png";
		$color_ids="#898989";
		$text_ids=" <span style='font-size:14px'>{not_installed}</span>";
		$js_ids="blur();";
	}
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{IDS}$text_ids",
			"position:right:{IDS}",$js_ids)."</td>
		</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{traffic_analysis}","position:right:{traffic_analysis_explain}","GotoNTOPNG()")."</td>
	</tr>";
	
		$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	
	
	
}

function nat_section(){
	$sock=new sockets();
	$tpl=new templates();

	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/add-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{new_nat}",
			"position:right:{nat_title}","Loadjs('system.network.nat.php?rule-js=yes&ID=0&t=".time()."',true);")."</td>
	</tr>";	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{rules}","position:right:{nat_title}","GotoNATRules()")."</td>
	</tr>";
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}

function nics_section(){
	$sock=new sockets();
	$tpl=new templates();
	$datas=TCP_LIST_NICS();
	
	
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	while (list ($num, $val) = each ($datas) ){
		writelogs("Found: $val",__FUNCTION__,__FILE__,__LINE__);
		$val=trim($val);
		$nic=new system_nic($val);
	
		$BEHA["reject"]="{strict_mode}";
		$BEHA["accept"]="{trusted_mode}";
		
		
		$BEHA2[0]="{not_defined}";
		$BEHA2[1]="{act_as_lan}";
		$BEHA2[2]="{act_as_wan}";
		
		$b1=$BEHA2[$nic->firewall_behavior]."/".$BEHA[$nic->firewall_policy];

		if($nic->firewall_artica==1){
			$b1=$b1."<br>{accept_artica_w}";
		}
	
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/interfaces-24.png'>
		</td>
		<td valign='middle' style='font-size:20px;width:99%'>".texttooltip("$val: $nic->NICNAME<br><span style='font-size:14px'>$b1</span>",
					"position:right:$nic->IPADDR - $nic->netzone","GoToNicFirewallConfiguration('$val')")."</td>
		</tr>
		<tr>					
			<td valign='middle' style='width:25px'>&nbsp;</td>
			<td valign='top'>
						<table style='width:100%'>
						<td valign='middle' style='width:18px'><img src='img/arrow-right-16.png'></td>
						<td valign='middle' style='font-size:14px'>".texttooltip("{firewall_rules}",
				"position:top:{rules}","GotoFireholeRules('$val')")."</td>
						</tr>
						</table>
			</td>
		</tR>
								
		";
	}
	

	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	

}
function services_section(){
	$tpl=new templates();
	$sock=new sockets();
	$IPSetInstalled=intval($sock->GET_INFO("IPSetInstalled"));
	$EnableIpBlocks=intval($sock->GET_INFO("EnableIpBlocks"));
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{services}",
				"position:top:{services_firewall_explain}","GotoFireholServices()")."</td>
	</tr>";
	
	$EnableSecureGateway=intval($sock->GET_INFO("EnableSecureGateway"));
	$icon_secure_gateway="arrow-right-24.png";
	$color_secure_gateway="black";
	$text_secure_gateway=null;
	
	$block_countries_icon="arrow-right-24.png";
	$block_countries_color="black";
	$block_countries_js="GotoIpBlocks()";
	
	$block_countries_text=null;
	
	if($EnableSecureGateway==0){
		$icon_secure_gateway="arrow-right-24-grey.png";
		$color_secure_gateway="#898989";
		$text_secure_gateway=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	if($EnableIpBlocks==0){
		$block_countries_icon="arrow-right-24-grey.png";
		$block_countries_color="#898989";
		$block_countries_text=" <span style='font-size:12px'>({disabled})</span>";
		
	}
	
	if($IPSetInstalled==0){
		$block_countries_icon="arrow-right-24-grey.png";
		$block_countries_color="#898989";
		$block_countries_text=" <span style='font-size:12px'>({ERROR_IPSET_NOT_INSTALLED})</span>";
		$block_countries_js="blur();";
		
		
	}
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{all_rules}","{all_rules}","GotoFireholeRules()")."</td>
	</tr>";
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{objects}","{objects}","GoToSquidAclsGroups()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$block_countries_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$block_countries_color'>".texttooltip("{block_countries}$block_countries_text","{ipblocks_text}","GotoIpBlocks()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_secure_gateway'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_secure_gateway'>".texttooltip("{secure_gateway}$text_secure_gateway","{secure_gateway_explain}","GotoGatewaySecure()")."</td>
	</tr>";
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}

