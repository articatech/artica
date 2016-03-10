<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.mysql.builder.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_POST["deletecontainer"])){qos_containers_delete();exit;}
if(isset($_GET["containers-items"])){qos_containers_items();exit;}
if(isset($_GET["container-js"])){qos_containers_js();exit;}
if(isset($_GET["container-popup"])){qos_containers_popup();exit;}
if(isset($_POST["name"])){qos_containers_save();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}
if(isset($_GET["container-tab"])){qos_containers_tab();exit;}
if(isset($_GET["container-status"])){qos_containers_status();exit;}
if(isset($_GET["container-status-frame"])){qos_containers_status_frame();exit;}
if(isset($_GET["delete-js"])){qos_containers_delete_js();exit;}
if(isset($_POST["TopMenu"])){qos_TopMenu();exit;}
qos_containers();

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$t=time();

	$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();

";

echo $html;

}

function qos_containers_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$delete=$tpl->javascript_parse_text("{delete}");
	header("content-type: application/x-javascript");
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name,eth FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
	
	$t=time();
	
	$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexReload();
}
function Save$t(){
	if(!confirm('$delete $ID {$ligne["name"]}')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('deletecontainer','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
Save$t();
	
	";
	
	echo $html;	
	
	
}

function qos_TopMenu(){
	
	$sock=new sockets();
	$QosTopMenu=intval($sock->GET_INFO("QosTopMenu"));
	if($QosTopMenu==1){$QosTopMenu=0;}else{$QosTopMenu=1;}
	$sock->SET_INFO("QosTopMenu", $QosTopMenu);
}

function qos_containers_delete(){
	$ID=$_POST["deletecontainer"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM qos_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM qos_containers WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function qos_containers_tab(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=18;
	$ID=$_GET["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name,eth FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
	
	$suffix["in"]="{inbound}";
	$suffix["out"]="{outbound}";
	
	$eth=$ligne["eth"];
	if(preg_match("#(.*?)-(.+)$#", $eth,$re)){$eth_text=$re[1]." ".$suffix[$re[2]];}
	$p=new system_nic();
	$eth=$p->NicToOther($eth);
	
	$array["container-popup"]="{Q.O.S} {$ligne["name"]}";
	$array["container-rules"]="{rules}";
	$array["container-DSCP"]="TOS";
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="container-rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.qos.container.rules.php?aclid=$ID\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="container-DSCP"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.qos.container.DSCP.php?aclid=$ID\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&ID=$ID&eth=$eth\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_qos_eth'.$eth)."<script>// LeftDesign('qos-256-white.png');</script>";
	
	echo $html;	
	
}

function qos_containers_status(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$eth=$_GET["eth"];

	echo "
	<div id='qos-container-status-$eth'></div>
	<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png",null,"ChargeQOsStatus$eth()")."</div>
	<script>
		function ChargeQOsStatus$eth(){
		LoadAjaxSilent('qos-container-status-$eth','$page?container-status-frame=$eth');
		}
		
		ChargeQOsStatus$eth();
	</script>
	";
	
}

function qos_containers_status_frame(){
	$sock=new sockets();
	$eth=$_GET["container-status-frame"];
	$filename="/usr/share/artica-postfix/ressources/logs/web/qos-$eth.status";
	$sock->getFrameWork("system.php?qos-status=yes&eth=$eth");
	$data=@file_get_contents($filename);
	echo "	<textarea id='qos-$eth' style='font-family:Courier New;
	font-weight:bold;width:100%;height:620px;border:5px solid #8E8E8E;
	overflow:auto;font-size:12px !important;width:99%;height:390px'>$data</textarea>
	";
	
}

function move_items(){
	$q=new mysql();
	$ID=$_POST["move-item"];
	$OrgID=$ID;
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT prio,eth FROM qos_containers WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
	
	$eth=$ligne["eth"];


	$CurrentOrder=$ligne["prio"];
	//echo "Current $eth Order:$CurrentOrder\n";
	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

			$sql="UPDATE qos_containers SET prio=$CurrentOrder WHERE prio='$NextOrder' AND eth='$eth'";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo  "Line:".__LINE__.":$sql\n".$q->mysql_error;}


			$sql="UPDATE qos_containers SET prio=$NextOrder WHERE ID='$ID' AND eth='$eth'";
			//echo $sql."\n";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo  "Line:".__LINE__.":$sql\n".$q->mysql_error;}

			$results=$q->QUERY_SQL("SELECT ID FROM qos_containers WHERE eth='$eth' ORDER by prio","artica_backup");
			if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
			$c=1;
			while ($ligne = mysql_fetch_assoc($results)) {
				$ID=$ligne["ID"];
				$sql="UPDATE qos_containers SET prio=$c WHERE ID='$ID'";
				//echo $sql."\n";
				$q->QUERY_SQL($sql,"artica_backup");
				if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
				$c++;
			}


}


function qos_containers_js(){

	$ID=intval($_GET["ID"]);
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();

	if($ID==0){
		$title=$tpl->_ENGINE_parse_body("{new_container}");
		echo "YahooWin3('900','$page?container-popup=yes&ID=$ID','$title');";
	}else{
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `name` FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
		$title=utf8_decode($ligne["name"]);
		echo "YahooWin3('900','$page?container-tab=yes&ID=$ID','$title');";
	}
	

}


function qos_containers_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";

	$t=$_GET["t"];
	$search='%';
	$table="qos_containers";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	$suffix["in"]=$tpl->javascript_parse_text("{inbound}");
	$suffix["out"]=$tpl->javascript_parse_text("{outbound}");

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();


	if($searchstring<>null){
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"$database"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error,1);}


	if(mysql_num_rows($results)==0){

		json_error_show("????");
		return;
	}

	$fontsize=22;

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$cellule="";
		$eth_text=null;

		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=yes&ID={$ligne["ID"]}&t=$t');");

		$lsprime="javascript:Loadjs('$MyPage?container-js=yes&ID={$ligne["ID"]}')";
		if(preg_match("#(.*?)-(.+)$#", $ligne["eth"],$re)){
			$ligne["eth"]=$re[1];
			$eth_text=$re[1]." ".$suffix[$re[2]];
		}


		$enabled=$ligne["enabled"];
		$icon="ok32.png";
		if($enabled==0){$icon="ok32-grey.png";$color="#8a8a8a";}
		
		$nic=new system_nic($ligne["eth"]);
		if($nic->FireQOS==0){
			$icon="ok24-grey.png";
			$color="#8a8a8a";
		}
		
		$QOSMAX=intval($ligne["QOSMAX"]);
		if($QOSMAX<10){$QOSMAX=100;}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$js="<a href=\"javascript:blur();\" OnClick=\"$lsprime;\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";


		$ligne["name"]=utf8_encode($ligne["name"]);
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		
		if($ligne["ceil"]>0){
			$cellule="{$ligne["ceil"]}{$ligne["ceil_unit"]}";
			
		}
		

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js{$ligne["prio"]}</a></span>",
						"<span $style>{$js}{$eth_text}</a></span>",
						"<span $style>{$js}{$ligne["name"]}</a></span>",
						"<span $style>{$js}{$ligne["rate"]}{$ligne["rate_unit"]}</a></span>",
						"<span $style>{$js}$cellule</a></span>",
						"<center $style>{$js}<img src='img/$icon'></a></center>",
						"<center $style>$up</center>",
						"<center $style>$down</center>",
						"<center $style>$delete</center>",


				)
		);

	}


	echo json_encode($data);

}



function tabs(){

		$tpl=new templates();
		$users=new usersMenus();
		$page=CurrentPageName();
		$fontsize=18;
	
		$array["main"]="{Q.O.S}";
		$array["interfaces"]="{network_interfaces}";
		$array["containers"]="{containers}";
	
		$t=time();
		while (list ($num, $ligne) = each ($array) ){
	
			if($num=="containers"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
				continue;
	
			}
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
		}
	
	
	
		$html=build_artica_tabs($html,'main_qos_center',1020)."<script>LeftDesign('qos-256-white.png');</script>";
	
		echo $html;
}


function qos_containers_popup(){
	
	$ID=intval($_GET["ID"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	$btname="{apply}";
	$results=$q->QUERY_SQL("SELECT InputSpeed,OutputSpeed,SpeedUnit,Interface FROM nics WHERE FireQOS=1 ORDER BY Interface","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$HASH[$ligne["Interface"]."-in"]=$ligne["Interface"]." {inbound}/{download2} {$ligne["InputSpeed"]}{$ligne["SpeedUnit"]}";
		$HASH[$ligne["Interface"]."-out"]=$ligne["Interface"]." {outbound}/{upload2} {$ligne["OutputSpeed"]}{$ligne["SpeedUnit"]}";
	}
	
	if($ID==0){
		$btname="{add}";
		$title=$tpl->_ENGINE_parse_body("{new_container}");
	}else{
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
		$title=utf8_decode($ligne["name"]);
	
	}
	
	$UNITS1["%"]="(%) {percent}";
	$UNITS1["kbit"]="(Kbit) kilobits per second";
	$UNITS1["bps"]="(bps) Bytes per second";
	$UNITS1["kbps"]="(kbps) Kilobytes per second";
	$UNITS1["mbps"]="(mbps) Megabytes per second";
	$UNITS1["gbps"]="(gbps) gigabytes per second";
	$UNITS1["bit"]="(bits) per second";
	$UNITS1["mbit"]="(Mbit) megabits per second";
	$UNITS1["gbit"]="(Gbit) gigabits per second";
	
	
	$UNITS["kbit"]="(Kbit) kilobits per second";
	$UNITS["bps"]="(bps) Bytes per second";
	$UNITS["kbps"]="(kbps) Kilobytes per second";
	$UNITS["mbps"]="(mbps) Megabytes per second";
	$UNITS["gbps"]="(gbps) gigabytes per second";
	$UNITS["bit"]="(bits) per second";
	$UNITS["mbit"]="(Mbit) megabits per second";
	$UNITS["gbit"]="(Gbit) gigabits per second";
	
	   
	
	    
	
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if($ID==0){$ligne["enabled"]=1;}
	$t=time();
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:30px;margin-bottom:30px'>$title</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px;vertical-align=middle'>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px;vertical-align=middle' nowrap>{container}:</td>
		<td colspan=2>". Field_text("name-$t",$ligne["name"],"font-size:22px;width:100%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px;vertical-align=middle' nowrap>{interface}:</td>
		<td colspan=2>". Field_array_Hash($HASH,"eth-$t",$ligne["eth"],"style:font-size:22px;width:100%")."</td>
	</tr>
	<tr style='height:70px'><td colspan=3>&nbsp;<br></td></tr>
	<tr>
		<td class=legend style='font-size:22px;vertical-align=middle' nowrap>". texttooltip("{guaranteed_rate}","{Guaranteed_Rate_explain}").":</td>
		<td style='font-size:22px;vertical-align=middle'>". Field_text("rate-$t",$ligne["rate"],"font-size:22px;width:100%")."</td>
		<td style='font-size:22px;vertical-align=middle'>". Field_array_Hash($UNITS1,"rate_unit-$t",$ligne["rate_unit"],"style:font-size:22px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px;vertical-align=middle' nowrap>".texttooltip("{max_bandwidth}","{qos_ceil_explain}").":</td>
		<td style='font-size:22px;vertical-align=middle'>". Field_text("ceil-$t",$ligne["ceil"],"font-size:22px;width:100%")."</td>
		<td style='font-size:22px;vertical-align=middle' width=1% nowrap>". Field_array_Hash($UNITS,"ceil_unit-$t",$ligne["rate_unit"],"style:font-size:22px")."</td>
	</tr>											
</table>
	<div style='margin-top:50px;text-align:right'><hr>". button("$btname","Save$t()",40)."</div></div>
<script>
var xSave$t= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexReload();
	if(ID==0){ YahooWin3Hide();}
}

function Save$t(){
	var XHR = new XHRConnection();
	enabled=0;
	XHR.appendData('ID',$ID);
	XHR.appendData('name',document.getElementById('name-$t').value);
	XHR.appendData('eth',document.getElementById('eth-$t').value);
	XHR.appendData('rate',document.getElementById('rate-$t').value);
	XHR.appendData('rate_unit',document.getElementById('rate_unit-$t').value);
	XHR.appendData('ceil',document.getElementById('ceil-$t').value);
	XHR.appendData('ceil_unit',document.getElementById('ceil_unit-$t').value);
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('enabled',document.getElementById('enabled-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function qos_containers_save(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$_POST["name"]=replace_accents($_POST["name"]);
	$_POST["name"]=str_replace(" ", "", $_POST["name"]);
	$_POST["name"]=str_replace("-", "", $_POST["name"]);
	$_POST["name"]=str_replace("_", "", $_POST["name"]);
	$_POST["name"]=str_replace("/", "", $_POST["name"]);
	$_POST["name"]=str_replace("\\", "", $_POST["name"]);
	
	
$table="qos_containers";
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	$eth=$_POST["eth"];
	if($ID>0){
		$sql="UPDATE $table SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO $table (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	
	$q=new mysql_builder();
	$q->CheckTables_qos();
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$results=$q->QUERY_SQL("SELECT ID FROM qos_containers WHERE eth='$eth' ORDER by prio ","artica_backup");
	if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$sql="UPDATE qos_containers SET prio=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
		$c++;
	}
	
}

function qos_containers(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$title=$tpl->javascript_parse_text("{Q.O.S}: {interfaces}");
	$t=time();
	$type=$tpl->javascript_parse_text("{type}");
	$nic_bandwith=$tpl->javascript_parse_text("{nic_bandwith}");
	$guaranteed_rate=$tpl->javascript_parse_text("{guaranteed_rate}");
	$ceil=$tpl->javascript_parse_text("{ceil}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$rulename=$tpl->javascript_parse_text("{container}");
	$title=$tpl->javascript_parse_text("{Q.O.S}: {containers}");
	$new_route=$tpl->javascript_parse_text("{new_route}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$order=$tpl->javascript_parse_text("{order}");
	$bandwith=$tpl->javascript_parse_text("{max_bandwidth}");
	$new_container=$tpl->javascript_parse_text("{new_container}");
	$apply=$tpl->javascript_parse_text("{apply}");
	// 	$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";
//{name: '$apply', bclass: 'apply', onpress : Apply$t},

	$add_to_menu=$tpl->_ENGINE_parse_body("{add_to_menu}");
	$remove_from_menu=$tpl->_ENGINE_parse_body("{remove_from_menu}");
	$QosTopMenu=intval($sock->GET_INFO("QosTopMenu"));
	if($QosTopMenu==1){
		$menu="{name: '<strong style=font-size:18px>$remove_from_menu</strong>', bclass: 'Delz', onpress : TopMenu$t},";
		
	}else{
		$menu="{name: '<strong style=font-size:18px>$add_to_menu</strong>', bclass: 'link', onpress : TopMenu$t},";
		
	}
	
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_container</strong>', bclass: 'add', onpress : Add$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : apply$t},
	$menu


	],";
	
	$html="
	
	<table class='TABLEAU_MAIN_QOS_CONTAINERS' style='display: none' id='TABLEAU_MAIN_QOS_CONTAINERS' style='width:100%'></table>
<script>
	var rowid=0;
	$(document).ready(function(){
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexigrid({
	url: '$page?containers-items=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$order</span>', name : 'prio', width : 75, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$nic</span>', name : 'eth', width : 244, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$rulename</span>', name : 'name', width : 211, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$guaranteed_rate</span>', name : 'rate', width : 185, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$bandwith</span>', name : 'ceil', width : 134, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$enabled</span>', name : 'enabled', width : 90, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width :80, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'down', width :80, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width :80, sortable : true, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$rulename', name : 'name'},
	],
	sortname: 'prio',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function Add$t(){
	Loadjs('$page?container-js=yes&ID=0');

}
var xTopMenu$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	AjaxTopMenu('template-top-menus','admin.top.menus.php');		
	LoadAjaxRound('qos-div','system.qos.containers.php');	
}

function TopMenu$t(){
	var XHR = new XHRConnection();
	XHR.appendData('TopMenu',1);
	XHR.sendAndLoad('$page', 'POST',xTopMenu$t);
}

function apply$t(){
	Loadjs('fireqos.progress.php');
}

</script>
";
echo $html;

}

