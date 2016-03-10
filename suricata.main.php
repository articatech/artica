<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){die();}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status_daemons"])){status_daemons();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["EnableSuricata"])){EnableSuricata();exit;}

tabs();
function tabs(){
	$tpl=new templates();
	$array["status"]='{status}';
	$array["interfaces"]='{network_interfaces}';
	$array["rules"]='{rules}';
	$array["signatures"]='{signatures}';
	$array["firewall"]='{firewall}';
	$page=CurrentPageName();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="interfaces"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.interfaces.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.rules.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="signatures"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.signatures.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="firewall"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.firewall.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "suricata-tabs");
}


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:30px;margin-bottom:30px'>{IDS}</div>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:450px'><div id='suricata-status'></div></td>
		<td valign='top' style='width:1000px'><div id='suricata-mainc'></div></td>
	</tr>
	</table>		
	<script>
		LoadAjax('suricata-mainc','$page?main=yes');
		LoadAjax('suricata-status','$page?status_daemons=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function main(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableSuricata=intval($sock->GET_INFO("EnableSuricata"));
	$SuricataInterface=$sock->GET_INFO("SuricataInterface");
	if($SuricataInterface==null){$SuricataInterface="eth0";}
	$SnortRulesCode=$sock->GET_INFO("SnortRulesCode");
	$SuricataFirewallPurges=intval($sock->GET_INFO("SuricataFirewallPurges"));
	if($SuricataFirewallPurges==0){$SuricataFirewallPurges=24;}
	$SuricataPurge=intval($sock->GET_INFO("SuricataPurge"));
	$p=Paragraphe_switch_img("{IDS}", "{about_ids}","EnableSuricata",$EnableSuricata,null,990);
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	if($SuricataPurge==0){$SuricataPurge=15;}
	
	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$array[null]="{all}";
	$array2[null]="{all}";
	while (list ($eth, $none) = each ($interfaces) ){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
	}
	
	if(is_file("{$GLOBALS["BASEDIR"]}/suricata.dashboard")){
		$IDS_SEVERITIES=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/suricata.dashboard"));
		if(isset($IDS_SEVERITIES["SEVERITIES"][1])){
			IF($IDS_SEVERITIES["SEVERITIES"][1]>0){
				$IDS_ROW="
				<tr>
				<td style='font-size:22px;'class=legend>{events}:</td>
				<td style='font-size:22px;text-decoration:underline' OnClick=\"javascript:GotoSuricataEvents()\" $curs><strong>{$IDS_SEVERITIES["SEVERITIES"][1]}</strong> IDS {detected_rules}</td>
				</tr>";
	
			}
				
		}
				
	}else{
		$IDS_ROW="
		<tr>
		<td style='font-size:22px;' class=legend>{events}:</td>
		<td style='font-size:22px;text-decoration:underline' OnClick=\"javascript:GotoSuricataEvents()\" $curs><strong>{not_calculated}</strong> IDS {detected_rules}</td>
		</tr>";
		
	}
	
	$SuricataPurges[7]="7 {days}";
	$SuricataPurges[15]="15 {days}";
	$SuricataPurges[30]="1 {month}";
	$SuricataPurges[90]="3 {months}";
	$SuricataPurges[180]="6 {months}";
	
	
	$SuricateRulesTTL[5]="5 {hours}";
	$SuricateRulesTTL[24]="1 {day}";
	$SuricateRulesTTL[48]="2 {days}";
	$SuricateRulesTTL[120]="5 {days}";
	$SuricateRulesTTL[168]="1 {week}";
	
	
	
	$html="$p
	<table style=width:100%'>$IDS_ROW
	<tr>
		<td class=legend style='font-size:22px'>{listen_interface}:</td>
		<td style='font-size:22px'>". Field_array_Hash($array, "SuricataInterface",$SuricataInterface,"style:font-size:22px;font-wieght:bold")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{retention_days}","{retention_days}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($SuricataPurges,"SuricataPurges","$SuricataPurge","blur()",null,0,"font-size:22px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{firewall_retention}","{firewall_retention_suricata_explain}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($SuricateRulesTTL,"SuricataFirewallPurges","$SuricataFirewallPurges","blur()",null,0,"font-size:22px")."</td>
	</tr>									
	<tr>
		<td class=legend style='font-size:22px'>{snort_code} (Oinkcode):</td>
		<td>". Field_text("SnortRulesCode","$SnortRulesCode","font-size:22px;width:574px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right' style='font-size:22px'><a href=\"https://www.snort.org/users/sign_up\" target=_new>{free_register}</a></td>
	</tr>
	</table>
	
	<div style='text-align:right;margin-top:15px'>". button("{apply}","Save$t()",40)."</div>
			
<script>
var xSave$t= function (obj) {
	Loadjs('suricata.progress.php');
}		
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSuricata',document.getElementById('EnableSuricata').value);
	XHR.appendData('SnortRulesCode',document.getElementById('SnortRulesCode').value);
	XHR.appendData('SuricataInterface',document.getElementById('SuricataInterface').value);
	XHR.appendData('SuricataPurges',document.getElementById('SuricataPurges').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}			
</script>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function status_daemons(){
	$sock=new sockets();
	$sock->getFrameWork("suricata.php?daemon-status=yes");
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/suricata.status");
	$page=CurrentPageName();
	$tpl=new templates();
	
	$serv[]=DAEMON_STATUS_ROUND("IDS",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("barnyard2",$ini,null,0);
	$serv[]="<div style='text-align:right;margin-top:20px'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('suricata-status','$page?status_daemons=yes',true);")."</div>";
	echo $tpl->_ENGINE_parse_body(@implode("<br>", $serv));
	
}

function EnableSuricata(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSuricata", $_POST["EnableSuricata"]);
	$sock->SET_INFO("SnortRulesCode", $_POST["SnortRulesCode"]);
	$sock->SET_INFO("SuricataInterface", $_POST["SuricataInterface"]);
	$sock->SET_INFO("SuricataPurges", $_POST["SuricataPurges"]);
}