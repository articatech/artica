<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	include_once("ressources/class.autofs.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}
	if(isset($_GET["getlist"])){disk_scan();exit;}
	if(isset($_GET["uuid"])){subdisk();exit;}
	if(isset($_GET["disks"])){disks();exit;}
	if(isset($_GET["show-devices-js"])){show_devices_js();exit;}
	tabs();
	
	


function tabs(){
	$sock=new sockets();
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
		$array["hds"]='{disks}';
		$array["icsi_share"]='{ISCSI_share}';
		
		
		$array["folders-monitor"]='{directories_monitor}';
		if($EnableIntelCeleron==0){$array["seeker"]='{performance}';}
		//$array["disks"]='{disks} BtrFS';
		
		$array["schedules"]='{schedules}';
	
	
		$fontsize=24;
	
	
		while (list ($num, $ligne) = each ($array) ){
			if($num=="hds"){
				$tab[]="<li><a href=\"system.internal.disks.php?display2=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				continue;
			}
			
			if($num=="icsi_share"){
				$tab[]="<li><a href=\"system.disks.iscsi.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				continue;
			}
			
			if($num=="icsi_client"){
				$tab[]="<li><a href=\"system.iscsi.client.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				continue;
			}			
			
			if($num=="folders-monitor"){
				$tab[]="<li><a href=\"system.folders.monitor.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				continue;
			}
			
			if($num=="seeker"){
				$tab[]="<li><a href=\"system.disks.performance.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				continue;
			}	

			if($num=="schedules"){
				$tab[]="<li><a href=\"system.disks.schedules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				continue;
			}
			
			$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
				
		}
	
	
	
	echo build_artica_tabs($tab, "btrfs-tabs",1490)."<script>LeftDesign('harddrive-white-64-opac20.png');</script>";
		
	
	
	}	
function disks(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$label=$tpl->_ENGINE_parse_body("{label}");
	$dev=$tpl->_ENGINE_parse_body("{source}");
	$used=$tpl->_ENGINE_parse_body("{used}");
	$mounted=$tpl->_ENGINE_parse_body("{mounted}");
	$devices=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");

	$TABLE_WIDTH=705;
	
	
	
$buttons="
		buttons : [
		{name: '$add_a_shared_folder', bclass: 'add', onpress : AddShared$t},
		{name: '$default_settings', bclass: 'Reconf', onpress : Defsets$t},
		
		],";	
$buttons=null;	
$html="
<input type='hidden' id='del_folder_name' value='{del_folder_name}'>
<div style='margin-left:-10px'>
	<table class='BRTFS_TABLE1' style='display: none' id='BRTFS_TABLE1' style='width:100%;'></table>
</div>
<div style='margin-left:-10px'><div id='BRTFS_TABLE2'></div></div>	
<script>
var IDTMP=0;
$(document).ready(function(){
$('#BRTFS_TABLE1').flexigrid({
	url: '$page?getlist=yes',
	dataType: 'json',
	colModel : [
		{display: '$label', name : 'label', width :67, sortable : false, align: 'left'},
		{display: '$size', name : 'label', width :54, sortable : false, align: 'left'},
		{display: '$dev', name : 'dev', width :128, sortable : true, align: 'left'},
		{display: '$used', name : 'used', width : 85, sortable : false, align: 'left'},
		{display: '$mounted', name : 'mounted', width : 225, sortable : false, align: 'left'},
		{display: '$devices', name : 'devices', width : 56, sortable : false, align: 'center'},
		],
	$buttons

	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: 'BtrFS',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function btrfsSubdisk(uuid){
	LoadAjax('BRTFS_TABLE2','$page?uuid='+uuid);
}
function Defsets$t(){
	Loadjs('samba.default.settings.php');
}



Loadjs('js/samba.js');
</script>	
";
	
	echo $html;
	
}

function subdisk(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$label=$tpl->_ENGINE_parse_body("{label}");
	$dev=$tpl->_ENGINE_parse_body("{source}");
	$used=$tpl->_ENGINE_parse_body("{used}");
	$mounted=$tpl->_ENGINE_parse_body("{mounted}");
	$devices=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");

	$TABLE_WIDTH=705;
	
	
	
$buttons="
		buttons : [
		{name: '$add_a_shared_folder', bclass: 'add', onpress : AddShared$t},
		{name: '$default_settings', bclass: 'Reconf', onpress : Defsets$t},
		
		],";	
$buttons=null;	
$html="
<table class='BRTFS_TABLE3' style='display: none' id='BRTFS_TABLE3' style='width:100%;'></table>
</div>
<script>
var IDTMP=0;
$(document).ready(function(){
$('#BRTFS_TABLE3').flexigrid({
	url: '$page?uuid-list={$_GET["uuid"]}',
	dataType: 'json',
	colModel : [
		{display: 'ID', name : 'label', width :31, sortable : false, align: 'center'},
		{display: '$size', name : 'label', width :54, sortable : false, align: 'left'},
		{display: '$dev', name : 'dev', width :128, sortable : true, align: 'left'},
		{display: '$used', name : 'used', width : 85, sortable : false, align: 'left'},
		{display: '$mounted', name : 'mounted', width : 225, sortable : false, align: 'left'},
		{display: '$devices', name : 'devices', width : 56, sortable : false, align: 'center'},
		],
	$buttons

	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: 135,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function btrfsSubdisk(uuid){
	LoadAjax('BRTFS_TABLE2','$page?uuid='+uuid);
}
function Defsets$t(){
	Loadjs('samba.default.settings.php');
}



Loadjs('js/samba.js');
</script>	
";	
	
}


function disk_scan(){
	
	$tpl=new templates();
	$sock=new sockets();
	$disks=unserialize(base64_decode($sock->getFrameWork("btrfs.php?btrfs-scan=yes")));
	$MyPage=CurrentPageName();
	//print_r($disks);
	
	
	
	if($_POST["query"]<>null){
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
		$search=$_POST["query"];

	}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	$c=0;
	while (list ($uuid, $array) = each ($disks) ){	
		$md=md5(serialize($array));
		$LABEL=$array["LABEL"];
		$DEV=$array["DEV"];
		$USED=$array["USED"];
		$MOUNTED=$array["MOUNTED"];
		$DEVICES=count($array["DEVICES"]);
		$href="<a href=\"javascript:blur()\" style='font-size:14px;text-decoration:underline'>";
		$hrefdevices="<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('btrfs.devices.php?show-devices-js=$uuid');\" style='font-size:18px;text-decoration:underline;font-weight:bold'>";
		$SIZE=$array["DF"]["SIZE"];
		if($MOUNTED==null){$MOUNTED=$array["DF"]["MOUNTED"];}
		$c++;
		$data['rows'][] = array(
		'id' => $md,
		'cell' => array(
		 "$href$LABEL</a>",
		 "$href$SIZE</a>",
		 "$href$DEV</a>",
		 "$href$USED</a>",
		 "$href$MOUNTED</a>",
		 "$hrefdevices$DEVICES</a>",	
		)
		);			
		
	}
	
	
	$data['total'] = $c;
	if($c==0){json_error_show("no data");}
	echo json_encode($data);		
	
}
