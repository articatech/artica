<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["iptables"])){iptables_tabs();exit;}
	if(isset($_GET["iptables-table"])){iptables_table();exit;}
	if(isset($_GET["rules"])){rules();exit;}
	if(isset($_GET["ruleid"])){rule_js();exit;}
	if(isset($_GET["rule-tabs"])){rule_tab();exit;}
	if(isset($_GET["rule-popup"])){rule_popup();exit;}
	if(isset($_POST["isFW"])){isFW_save();exit;}
	if(isset($_POST["rule-save"])){rule_save();exit;}
	if(isset($_GET["groupname"])){groupname();exit;}
	if(isset($_POST["rule-order"])){rule_order();exit;}
	if(isset($_POST["rule-delete"])){rule_delete();exit;}
	if(isset($_POST["rule-enable"])){rule_enable();exit;}
	if(isset($_GET["rule-time"])){rule_time();exit;}
	
	if(isset($_GET["generic"])){generic_tabs();exit;}
	if(isset($_POST["EnableArticaAsGateway"])){EnableArticaAsGateway_save();exit;}
	if(isset($_GET["FireHolInstall-js"])){FireHolInstall_js();exit;}
	if(isset($_GET["FireHolInstall-wizard-js"])){FireHolInstall_wizard_js();exit;}
	if(isset($_GET["FireHolInstall-popup"])){echo FireHolInstall();exit;}
	if(isset($_GET["FireHolwizard-popup"])){echo FireHolWizard();exit;}
	
	
	tabs();
	
function tabs(){
	
	$page=CurrentPageName();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	$tpl=new templates();
	$generic=$tpl->_ENGINE_parse_body("{central_rules}");
	unset($interfaces["lo"]);
	ksort($interfaces);
	$fontsize="font-size:18px;";
	$users=new usersMenus();
	$sock=new sockets();
	$FireHolConfigured=intval($sock->GET_INFO("FireHolConfigured"));
	if(intval($sock->getFrameWork("firehol.php?is-installed=yes"))==1){
		if($FireHolConfigured==0){FireHolWizard();return;}
	}else{
		FireHolInstall();
		return;
	}
	
	
	$array["cartes"]='{interfaces}';
	$array["services"]='{services}';
	
	
	$fontsize="font-size:18px";
	while (list ($index, $ligne) = each ($array) ){
		if($index=="cartes"){
			$html[]= "<li><a href=\"firehole.interfaces.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
		
		if($index=="services"){
			$html[]= "<li><a href=\"firehole.services.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}		
	
		if($index=="nat-proxy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.nat.proxy.php\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($index=="l7filter"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.l7filter.php\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($index=="antihack"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.iptables.php?tab-iptables-rules=yes&sshd=yes\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($index=="firewall-white"){
			$html[]= "<li><a href=\"whitelists.admin.php?popup-hosts=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
	}
	
	
	$html=build_artica_tabs($html,'main_firewall',1100)."
		<script>LeftDesign('firewall-256-white-opac20.png');</script>";
	
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
	echo $html;
}

function FireHolWizard(){
	$page=CurrentPageName();
	$tpl=new templates();	
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.tcpip.inc');
	$net=new networking();
	
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	
	$t=$_GET["t"];
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	}
	
	$router="<center style='margin:50px'>". button("{router_mode_require_2nics}",
			"Loadjs('firehol.wizard.router.php')",34)."</center>";
	if(count($array)<2){$router=null;}
	
	$t=time();
	$html="<div style='width:98%' class=form>
	<div style='font-size:42px;margin-bottom:10px'>{firewall_wizard}</div>
		$router
		<center style='margin:50px'>". button("{single_mode}","Loadjs('firehol.wizard.single.php')",34)."</center>
	</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

function FireHolInstall(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div style='width:98%' class=form>
	<div style='font-size:42px;margin-bottom:10px'>{firewall_wizard_install}</div>
	<div style='font-size:22px;margin-bottom:20px'>
		{firewall_wizard_install_explain}
	</div>
		<center style='margin:50px'>". button("{install_firewall}","Loadjs('firehol.wizard.install.progress.php')",34)."</center>
	</div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function FireHolInstall_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{firewall_wizard}");
	echo "YahooWin('1024','$page?FireHolInstall-popup=yes','$title')";
	
}
function FireHolInstall_wizard_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{install_firewall}");
	echo "YahooWin('1024','$page?FireHolwizard-popup=yes','$title')";

}

function generic_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$fontsize="font-size:18px;";
	$eth=$_GET["eth"];
	$ID=$_GET["ID"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	
	$array["l7filter"]='{application_detection}';
	$array["firewall"]='{incoming_firewall}';
	$array["antihack"]='Anti-hack SSH';
	$array["firewall-white"]='{whitelist}';
	//$array["nat-proxy"]='NAT Proxy';
	
	$fontsize="font-size:18px";
	while (list ($index, $ligne) = each ($array) ){
			if($index=="firewall"){
			$html[]= "<li><a href=\"system.firewall.in.php?no=no$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
		
		if($index=="nat-proxy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.nat.proxy.php\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}		

		if($index=="l7filter"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.l7filter.php\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($index=="antihack"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.iptables.php?tab-iptables-rules=yes&sshd=yes\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}

		if($index=="firewall-white"){
			$html[]= "<li><a href=\"whitelists.admin.php?popup-hosts=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
	}
	
	
	echo build_artica_tabs($html,'tabs_central_rules');	
	
}

function iptables_tabs(){
	
	$eth=$_GET["eth"];
	
	if(GET_CACHED(__FILE__, __FUNCTION__, $eth)){return;}
	$page=CurrentPageName();
	$fontsize="font-size:16px;";
	
	$array["STATUS"]="{status}";
	$array["INPUT"]="{INPUT}";
	$array["OUTPUT"]="{OUTPUT}";
	$array["FORWARD"]="{FORWARD}";
	$array["MARK"]="{MARK}";
	$array["EVENTS"]="{events}";
	while (list ($index, $ligne) = each ($array) ){
		
		if($index=="EVENTS"){
			$html[]="<li><a href=\"system.firewall.events.php?eth=$eth\" style='$fontsize' ><span>$ligne</span></a></li>\n";
			continue;
		}
		
		$html[]="<li><a href=\"$page?iptables-table=yes&eth=$eth&table=$index\" style='$fontsize' ><span>$ligne</span></a></li>\n";
	}
	
	
	$html=build_artica_tabs($html,"main_firewall_table_$eth",1060);
	SET_CACHED(__FILE__, __FUNCTION__, $eth, $html);
	echo $html;
}

function rule_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ruleid"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("$eth::{new_rule}::$table::");}
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
		$title="$eth::".$tpl->javascript_parse_text($ligne["rulename"]);
	}

	echo "YahooWin('1005','$page?rule-tabs=yes&ID=$ID&t=$t&table=$table&eth=$eth','$title')";
}

function rule_tab(){
	$page=CurrentPageName();
	$fontsize="font-size:16px;";
	$eth=$_GET["eth"];
	$ID=$_GET["ID"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	
	
	$array["rule-popup"]="{rule}";
	if($ID>0){
		$array["rule-time"]="{time_restriction}";
	}
	$fontsize="font-size:16px";
	while (list ($index, $ligne) = each ($array) ){
		$html[]="<li><a href=\"$page?$index=yes&eth=$eth&table=$table&ID=$ID&t=$t\" style='$fontsize' ><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html,'main_firewall_rule_'.$ID);	
	
}






function rule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$table=$_GET["table"];
	$ID=$_GET["ID"];
	$t=time();
	$title="$eth::".$tpl->_ENGINE_parse_body("{new_rule}");
	$bt="{add}";
	$enabled=1;
	$LOCKFORWARD=1;
	$HIDEFORMARK=0;
	$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
	$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
		$title="$eth::".$tpl->javascript_parse_text($ligne["rulename"]);
		$enabled=$ligne["enabled"];
		$table=$ligne["MOD"];
		$eth=$ligne["eth"];
		$bt="{apply}";
		$jlog=$ligne["jlog"];

	}

	$L7Mark=intval($ligne["L7Mark"]);

	if($EnableL7Filter==1){
		$q=new mysql();
		$L7Filters[0]="{select}";
		$sql="SELECT ID,keyitem  FROM `l7filters_items` WHERE enabled=1";
		$resultsL7Filter = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligneL7Filter = mysql_fetch_assoc($resultsL7Filter)) {
			$L7Filters[$ligneL7Filter["ID"]]=strtoupper($ligneL7Filter["keyitem"]);
			$L7Field="<tr><td colspan=3><div style='height:30px'>&nbsp;</div></td></tr>
		<tr>
			<td class=legend style='font-size:22px' nowrap>{applications}:</td>
			<td colspan=2>". Field_array_Hash($L7Filters,"L7Mark-$t",$L7Mark,"style:font-size:22px")."</td>

	</tr>	";
		}

	}

	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	unset($nicZ[$eth]);
	$ForwardNICs[null]="{none}";
	while (list ($yinter, $line) = each ($nicZ) ){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		$ForwardNICs[$yinter]="$yinter - $znic->NICNAME";
	}

	$FORWARD_NIC="<tr>
					<td class=legend style='font-size:22px' nowrap>{output_interface}:</td>
					<td>". Field_array_Hash($ForwardNICs,"ForwardNIC-$t",$ligne["ForwardNIC"],"style:font-size:22px")."</td>
					<td width=1%>&nbsp;</td>
				</tr>";

	if($table=="FORWARD"){$LOCKFORWARD=0;}


	$rulename=$ligne["rulename"];
	$proto=$ligne["proto"];
	$accepttype=$ligne["accepttype"];
	$source_group=intval($ligne["source_group"]);
	$dest_group=intval($ligne["dest_group"]);

	$destport_group=intval($ligne["destport_group"]);

	if($proto==null){$proto="tcp";}
	$protos[null]="{all}";
	$protos["udp"]="UDP";
	$protos["tcp"]="tcp";

	$accepttypes["ACCEPT"]="{accept}";
	$accepttypes["DROP"]="{drop}";
	$accepttypes["RETURN"]="{return}";
	$accepttypes["LOG"]="{log_only}";

	if($table=="MARK"){
		$LOCKFORWARD=1;
		$HIDEFORMARK=1;
		$accepttypes=array();
		$accepttypes["MARK"]="{MARK}";
		if($EnableQOS==1){$containers=containers_from_eth($eth);}


		$MARK_SECTION="
		<table>
		<tr><td colspan=3>&nbsp;</td></tr>
		<tr>
			<td class=legend style='font-size:22px' nowrap>{Q.O.S} {containers}:</td>
			<td>". Field_array_Hash($containers,"QOS-$t",$ligne ["QOS"],"style:font-size:22px")."</td>
			<td width=1%>&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px' nowrap>{or} {MARK_ITEM}:</td>
			<td>". Field_text("MARK-$t",$ligne["MARK"],"font-size:22px;width:90px")."</td>
			<td width=1%>&nbsp;</td>
			</tr>$FORWARD_NIC
			</table>
			";
			$FORWARD_NIC=null;

	}





	$AllSystems=$tpl->javascript_parse_text("{AllSystems}");
	$AllPorts=$tpl->javascript_parse_text("{AllPorts}");

	if($source_group==0){
	$inbound_object=$AllSystems;
	}
	if($dest_group==0){
	$outbound_object=$AllSystems;
	}

	if($destport_group==0){
	$destports_object=$AllPorts;
	}

	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=1;}
	if(!is_numeric($ligne["masquerade"])){$ligne["masquerade"]=1;}
	$jsGroup1="squid.BrowseAclGroups.php?callback=LinkInBoundGroup$t&FilterType=FW-IN";
	$jsGroup2="squid.BrowseAclGroups.php?callback=LinkOutbBoundGroup$t&FilterType=FW-OUT";
	$jsGroup3="squid.BrowseAclGroups.php?callback=LinkPortGroup$t&FilterType=FW-PORT";

	$sDel1=imgtootltip("22-delete.png","{unlink}","Delgroup1$t()");
	$sDel2=imgtootltip("22-delete.png","{unlink}","Delgroup2$t()");
	$sDel3=imgtootltip("22-delete.png","{unlink}","Delgroup3$t()");

	$html="
	<div style='width:98%' class=form>
	". Field_hidden("source_group-$t", $ligne["source_group"])."
	". Field_hidden("dest_group-$t", $ligne["dest_group"])."
	". Field_hidden("destport_group-$t", $ligne["destport_group"])."
	<div style='font-size:26px;margin-bottom:25px;margin-top:10px;margin-left:5px'>[$table] $title</div>

	<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:22px' nowrap>{rulename}:</td>
	<td>". Field_text("rulename-$t",$rulename,"font-size:22px;width:450px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{order}:</td>
		<td>". Field_text("zOrder-$t",$ligne["zOrder"],"font-size:22px;width:90px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t", 1,$enabled)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
		<tr>
		<td class=legend style='font-size:22px' nowrap><span id='OverideNet-label-$t'>{OverideNet}:</span></td>
		<td><span id='OverideNet-field-$t'>". Field_checkbox("OverideNet-$t", 1,$ligne["OverideNet"])."</span></td>
		<td width=1%><span id='OverideNet-explain-$t'>". help_icon("{OverideNet_explain}")."</span></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{log_all_events}:</td>
		<td>". Field_checkbox("jlog-$t", 1,$jlog)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
	<td class=legend style='font-size:22px' nowrap>{protocol}:</td>
		<td>". Field_array_Hash($protos,"proto-$t",$proto,"style:font-size:22px")."</td>
		<td width=1%>&nbsp;</td>
		</tr>
		<tr><td colspan=3>
		<center style='width:90%;margin:20px;border:1px solid #DDDDDD;border-radius:4px 4px 4px 4px'>
		<table >
		<tr><td colspan=4>&nbsp;</td></tr>
		<tr>
		<td width=42% align='center' style='font-size:22px;font-weight:bold;'>{inbound_object}</td>
		<td width=5% align='center'>&nbsp;</td>
		<td width=42% align='center' style='font-size:22px;font-weight:bold'>{outbound_object}</td>
		</tr>
		<tr><td colspan=4>&nbsp;</td></tr>
		<tr>
		<td width=42% align='center'><a href=\"javascript:Loadjs('$jsGroup1');\"
		style='font-size:22px;text-decoration:underline'>
		<span id='in-$t'>$inbound_object</span></a>$sDel1</td>
		<td width=5% align='center'><img src='img/arrow-blue-left-64.png'></td>
		<td width=42% align='center'><a href=\"javascript:Loadjs('$jsGroup2');\"
		style='font-size:22px;text-decoration:underline'>
		<span id='out-$t'>$outbound_object</span></a>$sDel2</td>
		</tr>
		<tr><td colspan=4>&nbsp;</td></tr>
		</table>
		</center>
		</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px' nowrap>{dest_ports}:</td>
		<td><a href=\"javascript:Loadjs('$jsGroup3');\"
		style='font-size:22px;text-decoration:underline'>
		<span id='port-$t'>$destports_object</span></a><div style='float:left;margin-right:5px'>$sDel3</div></td>
		<td width=1%>&nbsp;</td>
		</tr>
		$L7Field
		<tr><td colspan=3><div style='height:30px'>&nbsp;</div></td></tr>
		<tr>
		<td class=legend style='font-size:26px' nowrap>{action}:</td>
		<td colspan=2>". Field_array_Hash($accepttypes,"accepttype-$t",$accepttype,"style:font-size:26px;padding:5px;width:250px;")."</td>

	</tr>
	<tr>
		<td colspan=3>
		<center style='width:90%;margin:20px;border:1px solid #DDDDDD;border-radius:4px 4px 4px 4px'>
		$MARK_SECTION


		<table id='FORWARD-$t'>
		<tr><td colspan=3>&nbsp;</td></tr>

		$FORWARD_NIC
		<tr>
			<td class=legend style='font-size:22px' nowrap>{forward_to}:</td>
			<td>". Field_text("ForwardTo-$t",$ligne["ForwardTo"],"font-size:22px;width:450px")."</td>
			<td width=1%>". help_icon("{forward_to_iptables_explain}")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px' nowrap colspan=2>{rewrite_source_address}:</td>
			<td>". Field_checkbox("masquerade-$t",1,$ligne["masquerade"])."</td>
			
		</tr>		
		<tr><td colspan=3>&nbsp;</td></tr>
			</table>
				</center>
	</td>
	</tr>

	<tr>
		<td colspan=3 align='right'><hr>". button($bt,"Save$t()",30)."</td>
	</tr>
	</table>
</div>		
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID=$ID;
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	if(ID==0){YahooWinHide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function LinkInBoundGroup$t(ID){
	var RID=$ID;
	document.getElementById('source_group-$t').value=ID;
	LoadAjaxTiny('in-$t','$page?groupname=yes&gpid='+ID);
	if(RID>0){Save$t();}
}
function LinkOutbBoundGroup$t(ID){
	var RID=$ID;
	document.getElementById('dest_group-$t').value=ID;
	LoadAjaxTiny('out-$t','$page?groupname=yes&gpid='+ID);
	if(RID>0){Save$t();}
}	
function LinkPortGroup$t(ID){
	var RID=$ID;
	document.getElementById('destport_group-$t').value=ID;
	LoadAjaxTiny('port-$t','$page?groupname=yes&gpid='+ID);
	if(RID>0){Save$t();}
}
function Delgroup1$t(){
	var RID=$ID;
	document.getElementById('source_group-$t').value=0;
	document.getElementById('in-$t').innerHTML='$AllSystems';
	if(RID>0){Save$t();}
}
function Delgroup2$t(){
	var RID=$ID;
	document.getElementById('dest_group-$t').value=0;
	document.getElementById('out-$t').innerHTML='$AllSystems';
	if(RID>0){Save$t();}
}
function Delgroup3$t(){
	var RID=$ID;
	document.getElementById('destport_group-$t').value=0;
	document.getElementById('port-$t').innerHTML='$AllSystems';
	if(RID>0){Save$t();}
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rule-save',  '$ID');
	XHR.appendData('rulename',  encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('proto',  document.getElementById('proto-$t').value);
	XHR.appendData('accepttype',  document.getElementById('accepttype-$t').value);
	XHR.appendData('table',  '$table');
	XHR.appendData('interface',  '$eth');
	if(document.getElementById('enabled-$t').checked){
	XHR.appendData('enabled',1); }else{ XHR.appendData('enabled',0); }
	
	if(document.getElementById('OverideNet-$t').checked){
	XHR.appendData('OverideNet',1); }else{ XHR.appendData('OverideNet',0); }

	if(document.getElementById('jlog-$t').checked){
	XHR.appendData('jlog',1); }else{ XHR.appendData('jlog',0); }	
	
	
	
	XHR.appendData('source_group',  document.getElementById('source_group-$t').value);
	XHR.appendData('dest_group',  document.getElementById('dest_group-$t').value);
	XHR.appendData('destport_group',  document.getElementById('destport_group-$t').value);
	XHR.appendData('zOrder',  document.getElementById('zOrder-$t').value);
	XHR.appendData('ForwardTo',  document.getElementById('ForwardTo-$t').value);
	XHR.appendData('ForwardNIC',  document.getElementById('ForwardNIC-$t').value);
	XHR.appendData('masquerade',  document.getElementById('masquerade-$t').value);
	
	
	
	if(document.getElementById('QOS-$t') ){
		XHR.appendData('QOS',  document.getElementById('QOS-$t').value);
	}
	if(document.getElementById('MARK-$t') ){
		XHR.appendData('MARK',  document.getElementById('MARK-$t').value);
	}	
	if(document.getElementById('L7Mark-$t') ){
		XHR.appendData('L7Mark',  document.getElementById('L7Mark-$t').value);
	}	
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	
		
		
	}
function Dyn$t(){
	var RID=$ID;
	var LOCKFORWARD=$LOCKFORWARD;
	var HIDEFORMARK=$HIDEFORMARK;
	var source_group=$source_group;
	var dest_group={$dest_group};
	var destport_group=$destport_group;
	
	if(LOCKFORWARD==1){
		document.getElementById('ForwardNIC-$t').disabled=true;
		document.getElementById('ForwardTo-$t').disabled=true;
		document.getElementById('FORWARD-$t').style.visibility='hidden';
	}
	
	if(HIDEFORMARK==1){
		document.getElementById('OverideNet-label-$t').style.visibility='hidden';
		document.getElementById('OverideNet-field-$t').style.visibility='hidden';
		document.getElementById('OverideNet-explain-$t').style.visibility='hidden';
		document.getElementById('ForwardNIC-$t').disabled=false;
	}
	
	if(RID==0){return;}
	if(source_group>0){
		LoadAjaxTiny('in-$t','$page?groupname=yes&gpid='+source_group);
	}
	if(dest_group>0){
		LoadAjaxTiny('out-$t','$page?groupname=yes&gpid='+dest_group);
	}
	if(destport_group>0){
		LoadAjaxTiny('port-$t','$page?groupname=yes&gpid='+destport_group);
	}
	
	

}
		
Dyn$t();

</script>";
echo $tpl->_ENGINE_parse_body($html);
	
}

function groupname(){
	$ID=$_GET["gpid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$ligne['GroupName']=utf8_encode($ligne['GroupName']);
	echo $ligne['GroupName'];
	
}



function containers_from_bridge($eth){
	$q=new mysql();
	$sql="SELECT 
			`nics`.BridgedTo,`nics`.QOS,`qos_containers`.* FROM `nics`,`qos_containers`
			WHERE `qos_containers`.`eth`=`nics`.`Interface` AND `nics`.`QOS`=1 
			AND `nics`.`BridgedTo`='$eth'
			AND `qos_containers`.`enabled`=1
			ORDER BY `qos_containers`.prio";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error_html();}
	$HASH[0]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$HASH[$ligne["ID"]]=$ligne["name"]." {$ligne["rate"]}{$ligne["rate_unit"]}/{$ligne["ceil"]}{$ligne["ceil_unit"]}";
	
	}
	return $HASH;
}
function containers_from_eth($eth){
	if(preg_match("#^br#", $eth)){return containers_from_bridge($eth);}
	$q=new mysql();
	$sql="SELECT
	SELECT qos_containers.* FROM qos_containers WHERE enabled=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$HASH[0]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
	$HASH[$ligne["ID"]]=$ligne["name"]." {$ligne["rate"]}{$ligne["rate_unit"]}/{$ligne["ceil"]}{$ligne["ceil_unit"]}";

	}
	return $HASH;
}

function iptables_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$eth=$_GET["eth"];
	$sock=new sockets();
	$EnableArticaAsGateway=intval($sock->GET_INFO("EnableArticaAsGateway"));
	
	$ethC=new system_nic($eth);
	$isFW=$ethC->isFW;
	$isFWAcceptNet=$ethC->isFWAcceptNet;
	$isFWLogBlocked=$ethC->isFWLogBlocked;
	if($ethC->Bridged==1){
		$text=$tpl->_ENGINE_parse_body("{warn_this_interface_is_bridged_to}");
		$text=str_replace("%s", $ethC->BridgedTo, $text);
		$error_bridge=FATAL_ERROR_SHOW_128($text);
		
	}
	
	if(preg_match("#^br([0-9]+)#", $eth,$re)){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT isFW,isFWAcceptNet,isFWLogBlocked FROM nics_bridge WHERE ID='{$re[1]}'","artica_backup"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		$isFW=$ligne["isFW"];
		$isFWAcceptNet=$ligne["isFWAcceptNet"];
		$isFWLogBlocked=$ligne["isFWLogBlocked"];
		$isFWAcceptArtica=$ligne["isFWAcceptArtica"];
		$error_bridge=null;
		
	}
	if(!is_numeric($isFWAcceptNet)){$isFWAcceptNet=1;}
	if(!is_numeric($isFWLogBlocked)){$isFWLogBlocked=0;}
	$t=time();
	
	$EnableArticaAsGateway=Paragraphe_switch_img('{ARTICA_AS_GATEWAY}','{ARTICA_AS_GATEWAY_EXPLAIN}',"EnableArticaAsGateway-$t",$EnableArticaAsGateway,null,550);
	$p=Paragraphe_switch_img("{activate_firewall_nic}", "{activate_firewall_nic_explain}","isFW-$t",$isFW,null,550);
	$p1=Paragraphe_switch_img("{trust_local_networks}", "{trust_local_networks_explain}","isFWAcceptNet-$t",$isFWAcceptNet,null,550);
	$p3=Paragraphe_switch_img("{isFWAcceptArtica}", "{isFWAcceptArtica_explain}","isFWAcceptArtica-$t",$isFWAcceptArtica,null,550);
	$p2=Paragraphe_switch_img("{isFWLogBlocked}", "{isFWLogBlocked_explain}","isFWLogBlocked-$t",$isFWLogBlocked,null,550);
	
	
	$CountOfRules=$q->COUNT_ROWS("iptables_webint","artica_backup");
	if($CountOfRules>0){
		$p3=Paragraphe_switch_disable("{isFWAcceptArtica}", "{isFWAcceptArtica_explain}","isFWAcceptArtica-$t",$isFWAcceptArtica,null,550);
	}
	
	$html="<div style='width:98%' class=form>
	$error_bridge
	<table style='width:100%'>
		<tr>
			<td colspan=2 align='right' style='font-size:22px'><hr>
			". button("{firewall_rules}","Save$t();Loadjs('firewall.view.php')",22)."&nbsp;|&nbsp;
			". button("{apply_firewall_rules}","Save$t();Loadjs('{ipto}')",22)."
			<hr>		
			</td>
		</tr>
		<tr>
			<td colspan=2>
				$EnableArticaAsGateway
				<div style='text-align:right'><hr>". button("{apply}","SaveGateway$t()",22)."</div>
				</td>
		</tr>		
		<tr>
			<td colspan=2>$p</td>
		</tr>
		<tr>
			<td colspan=2>$p1</td>
		</tr>
		<tr>
			<td colspan=2>$p3</td>
		</tr>			
		<tr>
			<td colspan=2>$p2</td>
		</tr>			
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",22)."</td>
		</tr>
	</table>
	</div>
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	RefreshTab('main_firewall_table_$eth');
}

	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('eth',  '$eth');
	XHR.appendData('isFW',  document.getElementById('isFW-$t').value);
	XHR.appendData('isFWLogBlocked',  document.getElementById('isFWLogBlocked-$t').value);
	XHR.appendData('isFWAcceptNet',  document.getElementById('isFWAcceptNet-$t').value);
	XHR.appendData('isFWAcceptArtica',  document.getElementById('isFWAcceptArtica-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}	

function SaveGateway$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArticaAsGateway',  document.getElementById('EnableArticaAsGateway-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
</script>					
					
";
	
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function isFW_save(){
	$eth=$_POST["eth"];
	
	if(preg_match("#^br([0-9]+)#", $eth,$re)){
		$q=new mysql();
		$q->QUERY_SQL("UPDATE `nics_bridge` SET 
		`isFW`='{$_POST["isFW"]}',
		`isFWLogBlocked`='{$_POST["isFWLogBlocked"]}',
		`isFWAcceptNet`='{$_POST["isFWAcceptNet"]}',
		`isFWAcceptArtica`='{$_POST["isFWAcceptArtica"]}',	
		WHERE `ID`='{$re[1]}'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}	
	
	
	$ethC=new system_nic($eth);
	$ethC->isFW=$_POST["isFW"];
	$ethC->isFWAcceptNet=$_POST["isFWAcceptNet"];
	$ethC->isFWAcceptArtica=$_POST["isFWAcceptArtica"];
	$ethC->isFWLogBlocked=$_POST["isFWLogBlocked"];
	$ethC->SaveNic();
}

	
function iptables_table(){
	
	if($_GET["table"]=="STATUS"){iptables_status();exit;}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$iptable=$_GET["table"];
	$title=$tpl->javascript_parse_text("$eth $ethC->NICNAME {{$iptable}}");
	$new=$tpl->javascript_parse_text("{new_rule}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$type=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
	$t=time();
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>

function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?rules=yes&eth=$eth&t=$t&table=$iptable',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zOrder', width :20, sortable : true, align: 'center'},
		{display: '$rulename', name : 'rulename', width : 423, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 70, sortable : true, align: 'center'},
		{display: '$type', name : 'accepttype', width : 70, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'up', width : 70, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'down', width : 70, sortable : true, align: 'center'},
		{display: '$delete', name : 'del', width : 70, sortable : false, align: 'center'},
	
	],
	buttons : [
		{name: '$new', bclass: 'add', onpress : NewRule$t},
		{name: '$apply', bclass: 'Apply', onpress : Apply$t},
		
	],
	searchitems : [
		{display: '$rulename', name : 'rulename'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
}
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	
}

function RuleGroupUpDown$t(ID,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', '$eth');
	XHR.appendData('table', '$iptable');
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function DeleteRule$t(ID){
	if(!confirm('$delete '+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rule-delete', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
	Loadjs('firehol.progress.php');
}

function ChangEnabled$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function NewRule$t() {
	Loadjs('$page?ruleid=0&eth=$eth&t=$t&table=$iptable',true);
}	
LoadTable$t();
</script>
";
echo $html;	
	
}
function rule_delete(){
	$ID=$_POST["rule-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM iptables_main WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	
}




function rules(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
$tpl=new templates();
$MyPage=CurrentPageName();
$q=new mysql();
$eth=$_GET["eth"];
$table_type=$_GET["table"];
$sock=new sockets();
$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
$t=$_GET["t"];
$FORCE_FILTER=null;
$search='%';
$table="(SELECT iptables_main.* FROM iptables_main WHERE iptables_main.eth='$eth' AND iptables_main.MOD='$table_type' 
	ORDER BY zOrder ) as t";
$page=1;
	
if($q->COUNT_ROWS("iptables_main","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
if(isset($_POST["sortname"])){
	if($_POST["sortname"]<>null){
		$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
	}
}
	
if (isset($_POST['page'])) {$page = $_POST['page'];}
	
$searchstring=string_to_flexquery();
if($searchstring<>null){
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$log_all_events=$tpl->_ENGINE_parse_body("{log_all_events}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$time=null;
		$mkey=md5(serialize($ligne));
		$delete=imgsimple("delete-24.png",null,"DeleteRule$t('{$ligne["ID"]}')");
		$enabled=Field_checkbox("enabled-{$ligne["ID"]}", 1,$ligne["enabled"],"ChangEnabled{$_GET["t"]}('{$ligne["ID"]}')");
		$up=imgsimple("arrow-up-16.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',0)");
		$down=imgsimple("arrow-down-18.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',1)");
		$source_group=inbound_object($ligne["source_group"]);
		$dest_group=inbound_object($ligne["dest_group"]);
		$destport_group=inbound_object($ligne["destport_group"],1);
		$L7Mark=$ligne["L7Mark"];
		$FORWARD_TEXT=null;
		if($EnableL7Filter==0){$L7Mark=0;}
		
		if($destport_group<>null){$destport_group="<br>$destport_group";}
		$explain=$tpl->_ENGINE_parse_body("<span style='font-size:12px'>{from} $source_group {to} $dest_group$destport_group</span>");
		$rulename=trim(utf8_encode($ligne["rulename"]));
		
		if($ligne["jlog"]==1){
				$explain=$explain."<br><span style='font-size:12px'>$log_all_events</span>";
		}
		
		$explain=$explain.Layer7_object($L7Mark);
		
		if($rulename==null){$rulename=$tpl->_ENGINE_parse_body("{rule} {$ligne["ID"]}");}
		
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		
		$js="Loadjs('$MyPage?ruleid={$ligne["ID"]}&eth=$eth&t={$_GET["t"]}&table=$table_type',true);";
		
		$ACTION="cloud-goto-32.png";
		if($ligne["accepttype"]=="DROP"){
			$ACTION="cloud-deny-32.png";
		}
		
		if($ligne["accepttype"]=="RETURN"){
			$ACTION="arrow-right-32.png";
		}
		if($ligne["accepttype"]=="LOG"){
			$ACTION="log-32.png";
		}
		
		
		if($table_type=="FORWARD"){
			if($ligne["accepttype"]=="ACCEPT"){
				if($ligne["ForwardNIC"]<>null){$FORWARD_TEXT="{$ligne["ForwardNIC"]}:";}
				$FORWARD_TEXT=$FORWARD_TEXT.$tpl->_ENGINE_parse_body("<div style='font-size:12px'>{forward_to} {$ligne["ForwardTo"]}</div>");
			}
			
		}
		
		if($ligne["enablet"]==1){
			$time=$tpl->_ENGINE_parse_body("<span style='font-size:12px;color:$color'>{time_restriction}</span>").":&nbsp;".buildtime($ligne);
		}
		
		if($table_type=="MARK"){
			if($ligne["QOS"]>0){$ligne["MARK"]=0;}
			if($ligne["QOS"]>0){
				if($EnableQOS==0){$color="#8a8a8a";}
				$lsprime="javascript:Loadjs('system.qos.containers.php?container-js=yes&ID={$ligne["QOS"]}')";
				$FORWARD_TEXT=$FORWARD_TEXT.$tpl->_ENGINE_parse_body("<div style='font-size:12px'>
						{mark_packets_for_bandwidth} <a href=\"javascript:blur();\" OnClick=\"$lsprime\"
						style='font-size:12px;font-weight:bold;text-decoration:underline;color:$color'> - {$ligne["QOS"]} - ". qos_name($ligne["QOS"])."</a></div>");
			}
			
			if($ligne["MARK"]>0){
				$FORWARD_TEXT=$FORWARD_TEXT.$tpl->_ENGINE_parse_body("<div style='font-size:12px'>
				{mark_packets_with} {$ligne["MARK"]}</div>");
			}
			
		}
		
		$JSRULE="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
			style='font-size:14px;font-weight:bold;text-decoration:underline;color:$color'>";
		
		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		$data['rows'][] = array(
			'id' => "$mkey",
			'cell' => array(
			"<span style='font-size:14px;font-weight:bold;color:$color'>{$ligne["zOrder"]}</span>",
			"$JSRULE$rulename</a>&nbsp;$explain$FORWARD_TEXT$time",
			"<div style=\"margin-top:5px\">$enabled</div>",
			"<span style='font-size:14px;font-weight:bold;color:$color'><img src='img/$ACTION'></span>",
			"<div style=\"margin-top:5px\">$up</div>",
			"<div style=\"margin-top:5px\">$down</div>",
			"<div style=\"margin-top:4px\">$delete</div>")
		);
	}
	
	echo json_encode($data);	
	
}

function qos_name($ID){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
	return utf8_decode($ligne["name"]);
	
}

function buildtime($ligne){
	$color="black";
	if($ligne["enabled"]==0){$color="#8a8a8a";}
	$tpl=new templates();
	
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);
	
	$TTIME=unserialize($ligne["time_restriction"]);
	
	$DDS=array();
	
	while (list ($num, $maks) = each ($array_days)){
		if($TTIME["D{$num}"]==1){$DDS[]="{{$maks}}";}
		
	}
	
	if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
		$DDS[]="{from} {$TTIME["ftime"]} {to_time} {$TTIME["ttime"]}";
	}
	
	if(count($DDS)>0){
		return $tpl->_ENGINE_parse_body("<div style='font-size:12px'>".@implode(" ", $DDS))."<div>";
	}
	
	
}

function Layer7_object($ID){
	if($ID==0){return;}
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT keyitem,`explain` FROM l7filters_items WHERE ID='$ID' AND enabled=1","artica_backup"));
	if($ligne["keyitem"]==null){return null;}
	$text="( {application} <span style='font-size:11px'>{$ligne["explain"]}</span> )";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($text);
}

function inbound_object($ID,$asport=0){
	if($ID==0){
		$val="{AllSystems}";
		if($asport==1){$val="{port} {AllPorts}";}
		return $val;
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$ligne['GroupName']=utf8_encode($ligne['GroupName']);
	if($asport==1){return "{port}: 
	<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$ID&table-acls-t=0');\"
	style=\"font-size:12px;text-decoration:underline\">
	{$ligne['GroupName']}</a>";}
	
	
	return "<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$ID&table-acls-t=0');\"
	style=\"font-size:12px;text-decoration:underline\">
	{$ligne['GroupName']}</a>";
	
	
	
}
function EnableArticaAsGateway_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaAsGateway", $_POST["EnableArticaAsGateway"]);
	$sock->getFrameWork("services.php?KernelTuning=yes");
	
}	



