<?php

	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	
	if(isset($_GET["popup"])){table();exit;}
	if(isset($_GET["list"])){table_list();exit;}
	if(isset($_GET["network-bridge-js"])){network_bridge_js();exit;}
	if(isset($_GET["network-bridge-delete-js"])){network_bridge_delete_js();exit;}
	if(isset($_GET["network-bridge"])){network_bridge_popup();exit;}
	if(isset($_POST["Create"])){network_bridge_save();exit;}
	if(isset($_POST["Delete"])){network_bridge_del();exit;}
	
	if(isset($_GET["statistics"])){ipband_statistics();exit;}
	
	if(isset($_GET["add-item-to"])){add_item_to();exit;}

	//http://networktools.nl/asinfo/youporn.com
	//  whois -h whois.radb.net -- '-i origin AS30361' | grep ^route
	
	tabs();
	
function add_item_to(){
	header("content-type: application/x-javascript");
	$t=time();
	$pattern=$_GET["pattern"];
	$mkey=$_GET["mkey"];
	$html="
	var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		var ID=document.getElementById('$mkey').value;
		XHR.appendData('item-pattern',encodeURIComponent('$pattern'));
		XHR.appendData('ID',ID);
		XHR.appendData('ECHO',ID);
		XHR.sendAndLoad('squid.acls.groups.php', 'POST',xSave$t);
	
	}			
			
	 Save$t();		
	";
	echo $html;
	
}



	
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["ccurl"]="{artica_HTTP_traffic}";
	$array["ntopng"]="{traffic_analysis}";
	$array["popup"]="{connections_tracking}";
	
	
	
	
	
	//$array["statistics"]="{statistics}";
	$fontsize="font-size:26px";
		
	
		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="ntopng"){
				$html[]= "<li><a href=\"system.ntopng.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
				continue;
			}	

			if($num=="ccurl"){
				$html[]= "<li><a href=\"system.articahttp.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
				continue;
			}
			
				
			if($num=="arpspoof"){
				$html[]= "<li><a href=\"arp.spoof.php?none=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
				continue;
			}
	
			$html[]= "<li><a href=\"$page?$num=yes\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
		}
	
		$tab=time();
	
		echo build_artica_tabs($html, "tabs_networktrack")."<script>LeftDesign('conntrack-white-256.png');</script>";
	}	
	
function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	
	if(!$users->CONNTRACK_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{conntrackd_not_installed}");
		return;
	}
	
	
	$network_bridges=$tpl->_ENGINE_parse_body("{network_bridges}");
	$from=$tpl->javascript_parse_text("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$source_port=$tpl->javascript_parse_text("{source_port}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_network_bridge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$destination_port=$tpl->javascript_parse_text("{destination_port}");
	$connections_tracking=$tpl->javascript_parse_text("{connections_tracking}");
	
	
	$bts[]="{name: '$add', bclass: 'add', onpress :RuleAdd$t},";
	$bts[]="{name: '$reconstruct', bclass: 'apply', onpress : BuildVLANs$t},";
	
	
	
	
		$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
		if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
		if(count($bts)>0){
			$buttons="buttons : [".@implode("\n", $bts)." ],";
		}
		$reboot_network_explain=$tpl->_ENGINE_parse_body("{bridges_iptables_explain}<p>&nbsp;</p>{reboot_network_explain}");
		
		$buttons=null;
		
		$html="
		
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
		<script>
		var mm$t=0;
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?list=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: 'PROTO', name : 'PROTO', width : 50, sortable : false, align: 'center'},
		{display: '$from', name : 'from', width : 169, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'fromZ', width : 160, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'fromT', width : 45, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 121, sortable : false, align: 'center'},
		{display: '$to', name : 'to', width : 169, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'ToZ', width : 160, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'ToT', width : 45, sortable : false, align: 'left'},
		{display: '$status', name : 'delete', width : 120, sortable : false, align: 'center'},
		],$buttons
		searchitems : [
		{display: '$from', name : 'src'},
		{display: '$source_port', name : 'sport'},
		{display: '$to', name : 'dst'},
		{display: '$destination_port', name : 'dport'},
		
		
	
		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '$connections_tracking',
		useRp: true,
		rp: 25,
		rpOptions: [25,50,100,200,500,1000],
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	
	function RuleAdd$t(){
	Loadjs('$page?network-bridge-js=yes&ID=0&t=$t',true);
	}
	
	function BuildVLANs$t(){
	Loadjs('network.restart.php?t=$t');
	
	}
	
	
	
	function EmptyTask$t(){
	if(confirm('$empty::{$_GET["taskid"]}')){
	
	}
	}

	
	</script>
	
	";
	
		echo $tpl->_ENGINE_parse_body($html);
}	

function table_list(){
	$tpl=new templates();
	$sock=new sockets();
	$MyPage=CurrentPageName();
	
	
	$query=urlencode(string_to_flexregex());
	$sock->getFrameWork("network.php?conntrack=yes&rp={$_POST["rp"]}&qtype={$_POST["qtype"]}&query=$query");
	$MAIN=unserialize(@file_get_contents("ressources/logs/web/conntrack.inc"));
	
	$q=new mysql_squid_builder();
	unset($q->acl_GroupType_iptables["arp"]);
	while (list ($mkey, $ligne) = each ($q->acl_GroupType_iptables) ){
		$tt[]="( webfilters_sqgroups.GroupType='$mkey')";
		
	}

	
	$sql="SELECT ID,GroupName FROM webfilters_sqgroups WHERE 1 AND (". @implode(" OR ", $tt).") ORDER BY GroupName";
	if(!isset($_SESSION["webfilters_sqgroups_iptables"])){
		$_SESSION["webfilters_sqgroups_iptables"][null]="{CopyTo}";
		$results=$q->QUERY_SQL($sql);
		$fgroupts[null]="{select}";
		while ($ligne = mysql_fetch_assoc($results)) {
			$_SESSION["webfilters_sqgroups_iptables"][$ligne["ID"]]=$ligne["GroupName"];
		}
	}
	
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($MAIN);
	$data['rows'] = array();
	if(count($MAIN)==0){json_error_show("no data");}
	$arrow_right="<img src='img/arrow-right-32.png'>";
	
	
	$style="<span style='font-size:16px'>";
	while (list ($mkey, $ligne) = each ($MAIN) ){
		
		$val=0;
		$mkey=$ligne["mkey"];
		
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks('$mkey')");

		$ligne["dst2"]=gethostbyaddr($ligne["dst"]);
		
		$field_FROM=Field_array_Hash($_SESSION["webfilters_sqgroups_iptables"], "fgrp{$mkey}",null,null,null,0,"font-size:16px");
		$field_to=Field_array_Hash($_SESSION["webfilters_sqgroups_iptables"], "tgrp{$mkey}",null,null,null,0,"font-size:16px");
		
		$srcenc=urlencode($ligne["src"]);
		$ok_FROM=imgsimple("ok-blue-left-42.png",null,"Loadjs('$MyPage?add-item-to=yes&mkey=fgrp{$mkey}&pattern=$srcenc')");
		
		
		$dstenc=urlencode($ligne["dst"]);
		$ok_To=imgsimple("ok-blue-left-42.png",null,"Loadjs('$MyPage?add-item-to=yes&mkey=tgrp{$mkey}&pattern=$dstenc')");
		
		
		$pattern=$_GET["pattern"];
		$gpid=$_GET["gpid"];
		
	
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array("$style{$ligne["proto"]}</span>",
						"$style{$ligne["src"]}:{$ligne["sport"]}</span>",
						$field_FROM,$ok_FROM,
						$arrow_right,
						"$style{$ligne["dst"]}:{$ligne["dport"]}</span><div style='font-size:12px'>{$ligne["dst2"]}</div>",
						$field_to,$ok_To,
						"$style{$ligne["status"]}</span>",

						$delete)
		);
	}
	
	
	echo json_encode($data);	
	
}
