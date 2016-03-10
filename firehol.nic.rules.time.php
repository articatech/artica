<?php
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
if(isset($_POST["time-save"])){time_save();exit;}
rule_time();

function rule_time(){
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$table=$_GET["table"];
	$ID=$_GET["ID"];
	$t=time();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
	$title="{time_restriction}: $eth::".$tpl->javascript_parse_text($ligne["rulename"]);
	$enabled=$ligne["enabled"];
	$table=$ligne["MOD"];
	$eth=$ligne["eth"];
	$bt="{apply}";

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

	$tr[]="<table>";

	while (list ($num, $maks) = each ($array_days)){

		$tr[]="<tr>
		<td class=legend style='font-size:22px'>{{$maks}}</td>
		<td>". Field_checkbox_design("D{$num}-$t", 1,$TTIME["D{$num}"])."</td>
			</tr>";
				$jsF[]="if(document.getElementById('D{$num}-$t').checked){XHR.appendData('D{$num}',1); }else{ XHR.appendData('D{$num}',0); }";
				$jsD[]="document.getElementById('D{$num}-$t').disabled=true;";
				$jsE[]="document.getElementById('D{$num}-$t').disabled=false;";

	}
	$tr[]="</table>";

	if($TTIME["ftime"]==null){$TTIME["ftime"]="20:00:00";}
	if($TTIME["ttime"]==null){$TTIME["ttime"]="23:59:00";}

	$html="
	<div style='font-size:18px' class=explain>{fwtime_explain}</div>
	<div style='width:98%' class=form>
	<div style='font-size:28px;margin-bottom:25px;margin-top:10px;margin-left:5px'>$title</div>
	<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:22px'>{enabled}:</td>
	<td style='font-size:16px'>". Field_checkbox_design("enabled-$t", 1,$ligne["enablet"],"EnableCK$t()")."
	</tr>
	<tr>
		<tr>
		<td style='font-size:26px;vertical-align:top'>{hours}:<hr></td>
	<td>
	<table style='width:325px'>
	<td class=legend style='font-size:22px'>{from_time}:</td>
	<td style='font-size:16px'>". field_text("ftime-$t",$TTIME["ftime"],"font-size:22px;width:130px;text-align:right")."
	</tr>
	<tr>
	<td class=legend style='font-size:22px'>{to_time}:</td>
	<td style='font-size:16px'>". field_text("ttime-$t",$TTIME["ttime"],"font-size:22px;width:130px;text-align:right")."
	</tr>
	</table>
	</td>
	</tr>
	<tr>
		<td style='font-size:26px;vertical-align:top'>{days}:<hr></td>
		<td>".@implode("", $tr)."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",40)."</td>
		</tr>
		</table>
		</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#FIREWALL_NIC_RULES').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('time-save',  '$ID');
	XHR.appendData('ttime',  document.getElementById('ttime-$t').value);
	XHR.appendData('ftime',  document.getElementById('ftime-$t').value);
	if(document.getElementById('enabled-$t').checked){ XHR.appendData('enablet',1); }else{ XHR.appendData('enablet',0); }
	".@implode("\n", $jsF)."
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function EnableCK$t(){
	if(document.getElementById('enabled-$t').checked){
		document.getElementById('ttime-$t').disabled=false;
		document.getElementById('ftime-$t').disabled=false;
		".@implode("\n", $jsE)."
	}else{
		document.getElementById('ttime-$t').disabled=true;
		document.getElementById('ftime-$t').disabled=true;
		".@implode("\n", $jsD)."
}


}

EnableCK$t();
</script>";
	echo $tpl->_ENGINE_parse_body($html);

}
function time_save(){

	$ID=$_POST["time-save"];

	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);

	while (list ($num, $maks) = each ($array_days)){
		if($_POST["D{$num}"]==1){$TTIME["D{$num}"]=1;}
	}
	$TTIME["ttime"]=$_POST["ttime"];
	$TTIME["ftime"]=$_POST["ftime"];
	
	
	$rule1=strtotime(date("Y-m-d")." {$TTIME["ftime"]}");
	$rule2=strtotime(date("Y-m-d")." {$TTIME["ttime"]}");
	
	if($rule1>$rule2){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{fwtime_explain}");
		return;
	}

	$TTIMEZ=mysql_escape_string2(serialize($TTIME));


	$q=new mysql();
	if(!$q->FIELD_EXISTS("iptables_main","time_restriction","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `time_restriction` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	if(!$q->FIELD_EXISTS("iptables_main","enablet","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `enablet` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( enablet ) ";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	$sql="UPDATE iptables_main SET `enablet`='{$_POST["enablet"]}',`time_restriction`='$TTIMEZ' WHERE ID='$ID'";

	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";}

}