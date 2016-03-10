<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	if(isset($_POST["BandwidthLimit"])){SaveRule();exit;}
	if(isset($_GET["id-js"])){ID_JS();exit;}
	if(isset($_GET["id-popup"])){ID_POPUP();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_GET["button"])){genbutton();exit;}
	table();
	
	
function create_table(){
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `bandquotas` (
	`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	PatternGroup VARCHAR(255) NOT NULL,
	RuleName VARCHAR(255) NULL,
	GroupType smallint(1) NOT NULL DEFAULT 1,
	enabled smallint(1) NOT NULL DEFAULT 1,
	QuotaSizeBytes BIGINT UNSIGNED NOT NULL,
	BandwidthLimit BIGINT UNSIGNED NOT NULL,
	TimeFrame smallint(1) NOT NULL DEFAULT 1,
	Notify smallint(1) NOT NULL DEFAULT 0,
		KEY `GroupType` (`GroupType`),
		KEY `enabled` (`enabled`),
		KEY `QuotaSizeBytes` (`QuotaSizeBytes`),
		KEY `TimeFrame` (`TimeFrame`),
		KEY `Notify` (`Notify`)
	)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();die();}	
	
	if(!$q->FIELD_EXISTS("bandquotas", "RuleName")){
		$sql="ALTER TABLE `bandquotas` ADD `RuleName` varchar(255) default NULL";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html();die();}
	}
	
	
}
function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$nic=$_GET["nic"];
	$xtable=$_GET["xtable"];
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#SQUID_QUOTA_BDW').flexReload();
}
function Add$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete', '{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";



}
function delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM `bandquotas` WHERE `ID`='{$_POST["delete"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
}
function ID_JS(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ID=$_GET["id-js"];

	if($ID>0){
		$title=$tpl->javascript_parse_text("{rule}:$ID");
	}else{
		$title=$tpl->javascript_parse_text("{new_rule}");
	}
	echo "YahooWin('990','$page?id-popup=yes&ID=$ID','$title')";


}

function genbutton(){
	$t=$_GET["t"];
	$gp=$_GET["gp"];
	$tpl=new templates();
	if($gp==0){
		$button=button("{browse}...","Loadjs('browse-ad-groups.php?field-user=PatternGroup-$t&field-type=2&CallBack2=DisablePattern$t');");
		
	}
	
	echo $tpl->_ENGINE_parse_body($button);
}

function hash_during(){
	
	$tpl=new templates();
	$during[60]=$tpl->javascript_parse_text("1 {hour}");
	$during[1440]=$tpl->javascript_parse_text("1 {day}");
	$during[10080]=$tpl->javascript_parse_text("1 {week}");
	return $during;
}

function hash_grouptype(){
	$tpl=new templates();
	$sock=new sockets();
	
	
	
	$groupmode[0]=$tpl->javascript_parse_text("{active_directory_group}");
	$groupmode[1]=$tpl->javascript_parse_text("{member}");
	$groupmode[2]=$tpl->javascript_parse_text("{ipaddr}");
	$groupmode[3]=$tpl->javascript_parse_text("{MAC}");
	$groupmode[4]=$tpl->javascript_parse_text("{network2}");
	return $groupmode;
	
}

function ID_POPUP(){
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	$button_name="{add}";

	if($ID>0){
		$button_name="{apply}";
		$sql="SELECT * FROM bandquotas WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));

	}else{
		$ligne["fixup"]="-";
		$ligne["mode"]=1;
		$ligne["enabled"]=1;
		$ligne["zorder"]=0;
		$ligne["src_host"]="0.0.0.0/0";
		$ligne["dst_host"]="0.0.0.0/0";
		$ligne["src_port"]=0;
		$ligne["dst_port"]=0;
	}




	if($ligne["QuotaSizeBytes"]>0){
		$ligne["QuotaSizeBytes"]=$ligne["QuotaSizeBytes"]/1024;
		$ligne["QuotaSizeBytes"]=$ligne["QuotaSizeBytes"]/1024;
	}
	
	$BDW_CURRENT=intval($ligne["BandwidthLimit"]);
	if($BDW_CURRENT>0){
		$BDWT=$BDW_CURRENT*8;
		$BDWT=$BDWT/1000;
		$BDWT_UNIT="kbits";
		if($BDWT>1000){
			$BDWT_UNIT="mbits";
			$BDWT=$BDWT/1000;
		}
		
		$BDWAR[$BDW_CURRENT]="{$BDWT}{$BDWT_UNIT}";
	}
	$BDWAR["8000"]="64kbits";
	$BDWAR["16000"]="128kbits";
	$BDWAR["64000"]="512kbits";
	for($i=1;$i<101;$i++){
		$val=$i*1000;
		$val=$val*1000;
		$val=$val/8;
		$BDWAR[$val]="{$i}mbits";
	
	
	}
	
	
	$during=hash_during();
	$groupmode=hash_grouptype();

	$html="
<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:22px'>{rulename}:</td>
		<td style='font-size:18px'>". Field_text("RuleName-$t",$ligne["RuleName"],"font-size:22px;width:460px")."</td>
		<td></td>
	</tr>			

	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td style='font-size:22px'>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{mode}:</td>
		<td style='font-size:22px'>". Field_array_Hash($groupmode,"GroupType-$t",$ligne["GroupType"],"Change$t()",null,0,"font-size:22px;")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{source}:</td>
		<td style='font-size:18px'>". Field_text("PatternGroup-$t",$ligne["PatternGroup"],"font-size:22px;width:460px")."</td>
		<td><span id='button-$t'></span></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{quota2}:</td>
		<td style='font-size:22px'>". Field_text("QuotaSizeBytes-$t",$ligne["QuotaSizeBytes"],"font-size:22px;width:140px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{during}:</td>
		<td style='font-size:16px'>". Field_array_Hash($during,"TimeFrame-$t",$ligne["TimeFrame"],
				"style:font-size:22px;")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{limit_to_bandwidth}:</td>
		<td style='font-size:22px'>". Field_array_Hash($BDWAR,"BandwidthLimit-$t",$ligne["BandwidthLimit"],
				"style:font-size:22px;")."</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td colspan=3 align='right'><hr>". button($button_name,"Save$t()",30)."</td>
	</tr>
</tbody>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	var ID='$ID';
	if (res.length>3){alert(res);return;}
	if(ID==0){YahooWinHide();}
	$('#SQUID_QUOTA_BDW').flexReload();
}

function Change$t(){
	var gp=document.getElementById('GroupType-$t').value;
	LoadAjaxTiny('button-$t','$page?button=yes&t=$t&gp='+gp);
}

function DisablePattern$t(){
	YahooWinBrowseHide();
	document.getElementById('PatternGroup-$t').disabled=true;
}

function Save$t(){
var XHR = new XHRConnection();
if(document.getElementById('enabled-$t').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}
XHR.appendData('GroupType', document.getElementById('GroupType-$t').value);
XHR.appendData('RuleName', encodeURIComponent(document.getElementById('RuleName-$t').value));
XHR.appendData('PatternGroup', encodeURIComponent(document.getElementById('PatternGroup-$t').value));
XHR.appendData('QuotaSizeBytes', document.getElementById('QuotaSizeBytes-$t').value);
XHR.appendData('BandwidthLimit', document.getElementById('BandwidthLimit-$t').value);
XHR.appendData('TimeFrame', document.getElementById('TimeFrame-$t').value);
XHR.appendData('ID','$ID');
XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Change$t();
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}	
function table(){
	create_table();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$TB_HEIGHT=450;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	$new_rule=$tpl->_ENGINE_parse_body("{zDate}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$mode=$tpl->javascript_parse_text("{mode}");
	$source=$tpl->javascript_parse_text("{source}");
	$quota=$tpl->javascript_parse_text("{quota2}");
	$bandwitdh=$tpl->javascript_parse_text("{bandwitdh}");
	$apply_firewall_rules=$tpl->javascript_parse_text("{apply}");
	$during=$tpl->javascript_parse_text("{during}");
	
	$title=$tpl->javascript_parse_text("{quotas_bandwidth} {rules}");
	
	$buttons="
	buttons : [
	
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'Add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply_firewall_rules</strong>', bclass: 'Apply', onpress : FW$t},
	
	],	";
	$html="
	<table class='SQUID_QUOTA_BDW' style='display: none' id='SQUID_QUOTA_BDW' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#SQUID_QUOTA_BDW').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>&nbsp;</span>', name : 'ID', width :50, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$mode</span>', name : 'GroupType', width :213, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$source</span>', name : 'PatternGroup', width :372, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$quota</span>', name : 'QuotaSizeBytes', width :111, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$during</span>', name : 'TimeFrame', width :139, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$bandwitdh</span>', name : 'BandwidthLimit', width :385, sortable : true, align: 'right'},
	{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},
	
	],
	$buttons
	
	searchitems : [
	{display: '$source', name : 'PatternGroup'},
	],
	
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
	});
	

function FW$t(){
	Loadjs('squid.quotasband.progress.php');
}
	
function NewRule$t(){
	Loadjs('$page?id-js=0');
}

	
</script>";
echo $html;

}
function SaveRule(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	$_POST["QuotaSizeBytes"]=intval($_POST["QuotaSizeBytes"]);
	if($_POST["QuotaSizeBytes"]==0){$_POST["QuotaSizeBytes"]=1;}
	$_POST["QuotaSizeBytes"]=$_POST["QuotaSizeBytes"]*1024;
	$_POST["QuotaSizeBytes"]=$_POST["QuotaSizeBytes"]*1024;
	$_POST["PatternGroup"]=url_decode_special_tool($_POST["PatternGroup"]);
	$_POST["RuleName"]=url_decode_special_tool($_POST["RuleName"]);
	
	
	if(preg_match("#AD:[0-9]+:(.+)#", $_POST["PatternGroup"],$re)){
		$_POST["PatternGroup"]=base64_decode($re[1]);
	}
	
	if($_POST["GroupType"]==4){
		if(!preg_match("#^[0-9\.]+\/[0-9]+$#", $_POST["PatternGroup"])){
			echo " Required 1.2.3.4/24 format...\n";
			return;
		}
	}
	
	$_POST["RuleName"]=mysql_escape_string2($_POST["RuleName"]);
	$_POST["PatternGroup"]=mysql_escape_string2($_POST["PatternGroup"]);
	
	
	while (list ($field, $value) = each ($_POST) ){

		$addF[]="`$field`";
		$addV[]="'$value'";
		$editF[]="`$field`='$value'";
	}

	$insert="INSERT IGNORE INTO `bandquotas` (".@implode(",", $addF).") VALUES (".@implode(",", $addV).")";
	$edit="UPDATE `bandquotas` SET ".@implode(",", $editF)." WHERE ID='$ID'";
	$q=new mysql_squid_builder();
	

	if(!$q->FIELD_EXISTS("bandquotas", "RuleName")){
		$sql="ALTER TABLE `bandquotas` ADD `RuleName` varchar(255) default NULL";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	
	if($ID==0){$sql=$insert;$q->QUERY_SQL($insert);}else{$sql=$edit;$q->QUERY_SQL($edit);}
	if(!$q->ok){echo $q->mysql_error."\n$sql";}

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$database="squidlogs";
	$search='%';
	$table="bandquotas";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}
	$EnableKerbAuth=@intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKerbAuth"));

	$searchstring=string_to_flexquery();


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];

	if(!isset($_POST['rp'])){$_POST['rp']=50;}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table  WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=18;

	$during=hash_during();
	$groupmode=hash_grouptype();
	$all=$tpl->javascript_parse_text("{all}");

	while ($ligne = mysql_fetch_assoc($results)) {
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$color="black";
		
		
		if($ligne["GroupType"]==0){if($EnableKerbAuth==0){$ligne["enabled"]=0;}}
		if($ligne["GroupType"]==1){if($EnableKerbAuth==0){$ligne["enabled"]=0;}}
		
		if($ligne["enabled"]==0){$color="#8a8a8a";$img="arrow-right-24-grey.png";}
			


		$link="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?id-js={$ligne["ID"]}');\"
		style='text-decoration:underline;color:$color'>";


		
		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}')");

		/*{display: '<span style=font-size:18px>&nbsp;</span>', name : 'ID', width :50, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$mode</span>', name : 'GroupType', width :134, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$source</span>', name : 'PatternGroup', width :190, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$quota</span>', name : 'QuotaSizeBytes', width :190, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$during</span>', name : 'TimeFrame', width :190, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$bandwitdh</span>', name : 'BandwidthLimit', width :480, sortable : true, align: 'right'},
	{*/

		
		if($ligne["BandwidthLimit"]==0){$ligne["BandwidthLimit"]="-";}
		if($ligne["BandwidthLimit"]>0){
			$bytes=$ligne["BandwidthLimit"]." bytes/s";
			$bits=$ligne["BandwidthLimit"]*8;
			$kbs=$bits/1000;
			$Mbps=round($kbs/1000,2);
			$kbs=round($kbs,2);
			$ligne["BandwidthLimit"]="$bytes - {$kbs}kb/s - {$Mbps}Mb/s";
		}

		
		$QuotaSizeBytes=intval($ligne["QuotaSizeBytes"]);
		$QuotaSizeBytes=FormatBytes($QuotaSizeBytes/1024);
		
		
		

		$data['rows'][] = array(
				'id' => $ligne["ID"],
				'cell' => array(
						"<span style='font-size:22px'>{$ligne["ID"]}</a></span>",
						"<span style='font-size:22px'>$link{$groupmode[$ligne["GroupType"]]}</a></span>",
						"<span style='font-size:22px'>$link{$ligne["PatternGroup"]}</a></span>",
						"<span style='font-size:22px'>$link{$QuotaSizeBytes}</a></span>",
						"<span style='font-size:22px'>$link{$during[$ligne["TimeFrame"]]}</a></span>",
						"<span style='font-size:22px'>{$ligne["BandwidthLimit"]}</span>",
						"<center style='font-size:18px'>$delete</center>",

							

				)
		);
	}


	echo json_encode($data);

}