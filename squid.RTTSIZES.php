<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$TIMES_SLOT["day"]="{this_day}";
	$TIMES_SLOT["hour"]="{this_hour}";
	$TIMES_SLOT["week"]="{this_week}";
	$TIMES_SLOT["month"]="{this_month}";
	$title=$tpl->javascript_parse_text("{$TIMES_SLOT[$_GET["timeslot"]]}");
	
	echo "YahooWin4('720','$page?popup=yes&timeslot={$_GET["timeslot"]}','$title')";

}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$members=$tpl->_ENGINE_parse_body("{members}");
	$add_member=$tpl->_ENGINE_parse_body("{add_member}");
	
	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	
	$progress=$tpl->javascript_parse_text("{progress}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$q=new mysql_squid_builder();
	$mac=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$websites=$tpl->javascript_parse_text("{websites}");
	$categories=$tpl->javascript_parse_text("{categories}");
	$TIMES_SLOT["day"]="{this_day}";
	$TIMES_SLOT["hour"]="{this_hour}";
	$TIMES_SLOT["week"]="{this_week}";
	$TIMES_SLOT["month"]="{this_month}";
	$title=$tpl->javascript_parse_text("{$TIMES_SLOT[$_GET["timeslot"]]}");
	
	
	$t=time();
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px>$uid</strong>', bclass: 'link', onpress : GoToUID$t},
	{name: '<strong style=font-size:16px>$mac</strong>', bclass: 'link', onpress : GotoMAC$t},
	{name: '<strong style=font-size:16px>$ipaddr</strong>', bclass: 'link', onpress : GotoIPADDR$t},
	{name: '<strong style=font-size:16px>$websites</strong>', bclass: 'link', onpress : GotoWEBS$t},
	{name: '<strong style=font-size:16px>$categories</strong>', bclass: 'link', onpress : GotoCATS$t},
	],";

	
	
	$html="
	<table class='RTT$t' style='display: none' id='RTT$t' style='width:100%'></table>
	<script>
$(document).ready(function(){
	$('#RTT$t').flexigrid({
	url: '$page?search=yes&timeslot={$_GET["timeslot"]}',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$members</strong>', name : 'USER', width : 418, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$size</strong>', name : 'SIZE', width : 228, sortable : true, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$members', name : 'USER'},
	
	],
	sortname: 'SIZE',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:26px>". $tpl->javascript_parse_text("{$TIMES_SLOT[$_GET["timeslot"]]}")."</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '500',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
});

function GoToUID$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}'}).flexReload();

}
function GotoMAC$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=MAC'}).flexReload();
}
function GotoIPADDR$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=IPADDR'}).flexReload();
}
function GotoWEBS$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=WEBS'}).flexReload();
}
function GotoCATS$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=CATS'}).flexReload();
}

</script>
";	
	
	echo $html;
}

function search(){
	$base="/home/squid/rttsize";
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	if(!isset($_GET["SUBDIR"])){$_GET["SUBDIR"]="UID";}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	
	$TIMES_SLOT["hour"]="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/$HOUR/{$_GET["SUBDIR"]}";
	$TIMES_SLOT["day"]="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/{$_GET["SUBDIR"]}";
	$TIMES_SLOT["week"]="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/{$_GET["SUBDIR"]}";
	$TIMES_SLOT["month"]="/home/squid/rttsize/$YEAR/$MONTH/{$_GET["SUBDIR"]}";
	$baseWeek="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/{$_GET["SUBDIR"]}";
	$baseMonth="/home/squid/rttsize/$YEAR/$MONTH/{$_GET["SUBDIR"]}";
	
	

	$directory_path=$TIMES_SLOT[$_GET["timeslot"]];
	
	if(!is_dir($directory_path)){
		json_error_show("$directory_path no such dir");
	}
	
	if($GLOBALS["VERBOSE"]){echo "$directory_path\n";}
	$directory=opendir($directory_path);
	$DATAS=array();
	
	while ($file = readdir($directory)) {
		
		if($file=="."){continue;}
		if($file==".."){continue;}
		$dirpath="$directory_path/$file";
		if($GLOBALS["VERBOSE"]){echo "$dirpath\n";}
		
		if($_GET["SUBDIR"]=="WEBS"){
			$TOT=intval(@file_get_contents("$dirpath"));
			if($TOT==0){continue;}
			$DATAS[]="('$file','$TOT')";
			continue;
		}
		if($_GET["SUBDIR"]=="CATS"){
			$TOT=intval(@file_get_contents("$dirpath"));
			if($TOT==0){continue;}
			$DATAS[]="('$file','$TOT')";
			continue;
		}
		
		if(!is_dir($dirpath)){
			if($GLOBALS["VERBOSE"]){echo "$dirpath not a dir\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){
			if(!is_file("$dirpath/TOT")){echo "$dirpath/TOT not a file\n";}
		}
		
		$TOT=intval(@file_get_contents("$dirpath/TOT"));
		if($TOT==0){continue;}
		$DATAS[]="('$file','$TOT')";
	}
	
	closedir($directory);
	if(count($DATAS)==0){json_error_show("no data");}
	
	$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("TMP_RTTSIZE")){
			$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `TMP_RTTSIZE` (
			  `SIZE` BIGINT UNSIGNED NOT NULL,
			  `pattern`  varchar(128) NOT NULL PRIMARY KEY,
			   KEY `SIZE` (`SIZE`)
			 ) ENGINE=MYISAM;");
			
			if(!$q->ok){
				json_error_show("CREATE TABLE:$q->mysql_error");
			}
		}
		
	$q->QUERY_SQL("TRUNCATE TABLE TMP_RTTSIZE");
	$sql="INSERT IGNORE INTO TMP_RTTSIZE (pattern,SIZE) VALUES ". @implode(",", $DATAS);
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		json_error_show("INSERT: $q->mysql_error<br>$sql");
	}
	
	$table="TMP_RTTSIZE";
	
	
	$searchstring=string_to_flexquery();
	
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}
	$page=1;
	$data['page'] = $page;
	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	if($searchstring==null){
		$data['total'] = $q->COUNT_ROWS("FULL_USERS_DAY");
	
	}else{
		$sql="SELECT COUNT(*) as tcount FROM FULL_USERS_DAY WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$data['total']=$ligne["tcount"];
	}
	
	
	
	
	
	$data['rows'] = array();
	$CurrentPage=CurrentPageName();
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	
	$q1=new mysql();
	$t=time();
	$fontsize=22;
	
	$span="<span style='font-size:{$fontsize}px'>";
	$IPTCP=new IP();
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$member_value=trim($ligne["pattern"]);
		$size=FormatBytes($ligne["SIZE"]/1024);
		$ahref=null;
		$member_assoc=null;

	
	
		if($IPTCP->IsvalidMAC($member_value)){
			$mac_encoded=urlencode($member_value);
			$uid=$q->MacToUid($member_value);
			if($uid<>null){$member_assoc="&nbsp; ($uid)";}
			$ahref="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$mac_encoded');\"
			style='font-size:$fontsize;text-decoration:underline'>";
		}
		$c++;
		$data['rows'][] = array(
				'id' => $member_value,
				'cell' => array(
						"$span$ahref$member_value</a>$member_assoc</span>",
						"$span$size</a></span>",
	
				)
		);
	
	}
	$data['total']=$c;
	echo json_encode($data);	
	
	
	
}

