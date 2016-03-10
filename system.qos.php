<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["nic-items"])){interfaces_items();exit;}
if(isset($_POST["EnableQOS"])){EnableQOS();exit;}
tabs();



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
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.qos.containers.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
				continue;
	
			}
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
		}
	
	
	
		$html=build_artica_tabs($html,'main_qos_center',1020)."<script>LeftDesign('qos-256-white.png');</script>";
	
		echo $html;
}

function EnableQOS(){
	$sock=new sockets();
	$sock->SET_INFO("EnableQOS", $_POST["EnableQOS"]);
	
}


function main(){
	$sock=new sockets();
	$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="<div style='width:98%' class=form>
	". Paragraphe_switch_img("{Q.O.S}", "{qos_artica_explain}","EnableQOS",$EnableQOS,null,750)."
			
	<div style='margin-top:50px;text-align:right'><hr>". button("{apply}","Save$t()",40)."</div>
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	RefreshTab('main_qos_center');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableQOS',document.getElementById('EnableQOS').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function interfaces(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{Q.O.S} {interfaces}");
	$t=time();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$nic_bandwith=$tpl->_ENGINE_parse_body("{nic_bandwith}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$title=$tpl->javascript_parse_text("{Q.O.S} {interfaces}");
	$new_route=$tpl->_ENGINE_parse_body("{new_route}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	
	
	
	
	
	

	// 	$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";

	$buttons="
	buttons : [
	{name: '$new_route', bclass: 'add', onpress : Add$t},
	{name: '$test_a_route', bclass: 'Search', onpress : TestRoute$t},
	{name: '$apply', bclass: 'apply', onpress : Apply$t},


	],";
	$buttons=null;
	$html="
	
	<table class='TABLEAU_MAIN_QOS_INTERFACES' style='display: none' id='TABLEAU_MAIN_QOS_INTERFACES' style='width:100%'></table>

<script>
	var rowid=0;
	$(document).ready(function(){
	$('#TABLEAU_MAIN_QOS_INTERFACES').flexigrid({
	url: '$page?nic-items=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'nothing', width : 85, sortable : true, align: 'center'},
	{display: '$nic', name : 'Interface', width : 250, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'IPADDR', width : 165, sortable : true, align: 'center'},
	{display: '$enabled', name : 'QOS', width : 50, sortable : true, align: 'center'},
	{display: '$nic_bandwith', name : 'QOSMAX', width :165, sortable : true, align: 'left'},
	],
	$buttons
	searchitems : [
	{display: '$nic', name : 'Interface'},
	],
	sortname: 'Interface',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});
</script>
";
echo $html;

}
function interfaces_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";

	$t=$_GET["t"];
	$search='%';
	$table="nics";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

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

	if($searchstring==null){
		
		$data['total']=$data['total']+$array[0];
		$data['rows']=$array[1]["rows"];
	}

	$fontsize=22;

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$color="#8a8a8a";
		
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?route-delete-js=yes&zmd5={$ligne["zmd5"]}&t=$t');");

		$lsprime="javascript:Loadjs('system.nic.edit.php?nic={$ligne["Interface"]}&OnLyQOS=yes&noreboot=yes')";
		
		

		$enabled=$ligne["QOS"];
		$icon="ok-42-grey.png";
		if($enabled==1){$icon="ok-42.png";$color="black";}
		$QOSMAX=intval($ligne["QOSMAX"]);
		if($QOSMAX<10){$QOSMAX=100;}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$js="<a href=\"javascript:blur();\" OnClick=\"$lsprime;\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style><img src='img/folder-network-42.png'></span>",
						"<span $style>$js{$ligne["Interface"]} - {$ligne["NICNAME"]}</a></span>",
						"<span $style>{$js}{$ligne["IPADDR"]}</a></span>",
						"<span $style>{$js}<img src='img/$icon'></a></span>",
						"<span $style>{$js}{$QOSMAX}Mib</a></span>",
						
				)
		);

	}


	echo json_encode($data);

}
