<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.ndpi.services.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	echo "alert('$error')";
	die();
}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_POST["rule-dup"])){duplicate();exit;}
if(isset($_GET["dup-js"])){duplicate_js();exit;}
rule_popup();


function interfaces_field(){
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	
}

function duplicate_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	
	$rulename=$tpl->javascript_parse_text("{rulename}:");
	
	echo 
"
	
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#FIREWALL_NIC_RULES').flexReload();
}

function Save$t(){
	var rr=prompt('$rulename');
	if(!rr){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rule-dup',  '{$_GET["ID"]}');
	XHR.appendData('rulename',  encodeURIComponent(rr));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	
}


function duplicate(){
	$ID=$_POST["rule-dup"];
	$rulename=url_decode_special_tool($_POST["rulename"]);
	
	$sql="INSERT IGNORE INTO iptables_main (`rulename`,`eth`,`accepttype`,`enabled`)
	VALUES ('$rulename','$eth','ACCEPT','1')";
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("iptables_main","service","artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `iptables_main` ADD `service` varchar(50) NULL ,ADD INDEX ( service );","artica_backup");
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$NEW_RULE_ID=$q->last_id;
	if(!is_numeric($NEW_RULE_ID)){echo "Failed\n";return;}
	if($NEW_RULE_ID==0){echo "Failed\n";return;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
	
	unset($ligne["ID"]);
	unset($ligne["rulename"]);
	
	while (list ($key, $value) = each ($ligne) ){
		if(is_numeric($key)){continue;}
		$f[]="`$key`='".mysql_escape_string2($value)."'";
		
	}
	
	$sql="UPDATE `iptables_main` SET ".@implode(",", $f)." WHERE ID='$NEW_RULE_ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT * FROM firewallfilter_sqacllinks WHERE aclid='$ID'");
	
	$prefix="INSERT IGNORE INTO firewallfilter_sqacllinks (zmd5,aclid,gpid,zOrder,direction,negation) VALUES ";
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$direction=$ligne["direction"];
		$gpid=$ligne["gpid"];
		$zOrder=$ligne["zOrder"];
		$negation=$ligne["negation"];
		$aclid=$NEW_RULE_ID;
		$md5=md5($aclid.$gpid.$direction);
		$f[]="('$md5','$aclid','$gpid',$zOrder,$direction,$negation)";
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("$prefix".@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
}


function rule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$table=$_GET["table"];
	$ID=intval($_GET["ID"]);
	$iptablesNDPI_error="&nbsp;";
	$t=time();
	$title="$eth::".$tpl->_ENGINE_parse_body("{new_rule}");
	$bt="{add}";
	$enabled=1;
	$LOCKFORWARD=1;
	$HIDEFORMARK=0;
	$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
	$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
	$q=new mysql();
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
		$title="{rule} $ID) $eth::".$tpl->javascript_parse_text($ligne["rulename"]);
		$enabled=$ligne["enabled"];
		$table=$ligne["MOD"];
		$eth=$ligne["eth"];
		$bt="{apply}";
		$jlog=$ligne["jlog"];

	}



	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$interface=$tpl->javascript_parse_text("{interface}");
	
	while (list ($yinter, $line) = each ($nicZ) ){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		$NICS[$yinter]="$interface:$yinter - $znic->NICNAME";
		$NICS["{$yinter}2{$yinter}"]=$tpl->javascript_parse_text("{router}: $yinter");
		
	}
	
	if($q->TABLE_EXISTS("pnic_bridges", "artica_backup")){
		$sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne2 = mysql_fetch_assoc($results)) {
			$nic_from=$ligne2["nic_from"];
			$nic_to=$ligne2["nic_to"];
			$RouterName="{$nic_from}2{$nic_to}";
			$NICS[$RouterName]=$tpl->javascript_parse_text("{router}: $nic_from {to} $nic_to");
		}
	}
	ksort($NICS);
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
	


	$iptablesNDPI=intval($sock->GET_INFO("iptablesNDPI"));
	$ndpi=new ndpi_services();
	
	if($iptablesNDPI==0){
		$iptablesNDPI_error=imgtootltip("warning-panneau-24.png","{not_installed}");
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
	
	
	
	$duplicate="&nbsp;&laquo;<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('$page?dup-js=yes&ID=$ID')\"
			style='font-size:16px;text-decoration:underline'>{duplicate_this_rule}</a>&raquo;";

	$html="
	<div style='width:98%' class=form>
	<div style='font-size:22px;margin-bottom:25px;margin-top:10px;margin-left:5px'>$title$duplicate</div>

	<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:22px' nowrap>{rulename}:</td>
	<td>". Field_text("rulename-$t",$rulename,"font-size:22px;width:450px",null,null,null,false,"SaveCHK$t()")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{order}:</td>
		<td>". Field_text("zOrder-$t",$ligne["zOrder"],"font-size:22px;width:90px",null,null,null,false,"SaveCHK$t()")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t", 1,$enabled)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{interface}:</td>
		<td colspan=2>". Field_array_Hash($NICS,"eth-$t",$ligne["eth"],"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{log_all_events}:</td>
		<td>". Field_checkbox_design("jlog-$t", 1,$jlog)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
	<td class=legend style='font-size:22px' nowrap>{service2}:</td>
		<td>". Field_text("service-$t",$ligne["service"],"font-size:22px;width:255px",null,null,null,false,"SaveCHK$t()")."</td>
		<td width=1%>". button("{browse}...", "Loadjs('firehol.BrowseService.php?SelectField=service-$t')",18)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{application}:</td>
		<td>". Field_array_Hash($ndpi->dpiArray,"application-$t",$ligne["application"],"style:font-size:22px;padding:5px;width:250px;")."</td>
		<td width=1%>$iptablesNDPI_error</td>
	</tr>				
	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{action}:</td>
		<td colspan=2>". Field_array_Hash($accepttypes,"accepttype-$t",$accepttype,"style:font-size:22px;padding:5px;width:250px;")."</td>
	</tr>
	<tr>
	<td colspan=3 align='right'><hr>". button($bt,"Save$t()",30)."</td>
	</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#FIREWALL_NIC_RULES').flexReload();
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rule-save',  '$ID');
	XHR.appendData('rulename',  encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('service',  document.getElementById('service-$t').value);
	XHR.appendData('accepttype',  document.getElementById('accepttype-$t').value);
	XHR.appendData('interface',  document.getElementById('eth-$t').value);
	XHR.appendData('application',  document.getElementById('application-$t').value);
	
	if(document.getElementById('enabled-$t').checked){
	XHR.appendData('enabled',1); }else{ XHR.appendData('enabled',0); }

	if(document.getElementById('jlog-$t').checked){
		XHR.appendData('jlog',1); }else{ XHR.appendData('jlog',0); 
	}

	XHR.appendData('zOrder',  document.getElementById('zOrder-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function check$t(){
	var iptablesNDPI=$iptablesNDPI;
	if(iptablesNDPI==0){
		 document.getElementById('application-$t').disabled=true;
	}
}


</script>";
echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$ID=$_POST["rule-save"];
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));


	$FADD_FIELDS[]="`rulename`";
	$FADD_FIELDS[]="`service`";
	$FADD_FIELDS[]="`accepttype`";
	$FADD_FIELDS[]="`enabled`";
	$FADD_FIELDS[]="`eth`";
	$FADD_FIELDS[]="`zOrder`";
	$FADD_FIELDS[]="`jlog`";
	$FADD_FIELDS[]="`application`";
	



	$FADD_VALS[]=$_POST["rulename"];
	$FADD_VALS[]=$_POST["service"];
	$FADD_VALS[]=$_POST["accepttype"];
	$FADD_VALS[]=$_POST["enabled"];
	$FADD_VALS[]=$_POST["interface"];
	$FADD_VALS[]=$_POST["zOrder"];
	$FADD_VALS[]=$_POST["jlog"];
	$FADD_VALS[]=$_POST["application"];
	



	while (list ($num, $field) = each ($FADD_FIELDS)){
		$EDIT_VALS[]="$field ='".$FADD_VALS[$num]."'";
	}

	reset($FADD_VALS);
	while (list ($num, $field) = each ($FADD_VALS)){
		$ITEMSADD[]="'$field'";
	}

	$q=new mysql();
	if(!$q->FIELD_EXISTS("iptables_main","MARK","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `MARK` INT( 10 ) NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	if(!$q->FIELD_EXISTS("iptables_main","QOS","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `QOS` INT( 10 ) NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("iptables_main","L7Mark","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `L7Mark` INT( 10 ) NULL DEFAULT 0,ADD INDEX ( L7Mark ) ";
		$q->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$q->FIELD_EXISTS("iptables_main","L7Mark","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `L7Mark` INT( 10 ) NULL DEFAULT 0,ADD INDEX ( L7Mark ) ";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->FIELD_EXISTS("iptables_main","application","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `application` VARCHAR( 50 ) NULL,ADD INDEX ( `application` )";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	if(!$q->FIELD_EXISTS("iptables_main","service","artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `iptables_main` ADD `service` varchar(50) NULL INDEX ( `service` )","artica_backup");
	}
	
	
	if($ID==0){
		$sql="INSERT IGNORE INTO iptables_main ( ". @implode(",", $FADD_FIELDS).") VALUES (".@implode(",", $ITEMSADD).")";

	}else{
		$sql="UPDATE iptables_main SET  ". @implode(",", $EDIT_VALS)." WHERE ID='$ID'";

	}






	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
}