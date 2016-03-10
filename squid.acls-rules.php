<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		echo "<H2>$alert</H2>";
		die();	
	}
	
	if(isset($_POST["enabled-all"])){acl_rule_enable_all();exit;}
	if(isset($_POST["disable-all"])){acl_rule_disable_all();exit;}
	if(isset($_POST["aclrulename-group"])){acl_rule_group_save();exit;}
	if(isset($_POST["aclname-save"])){acl_rule_save();exit;}
	if(isset($_GET["acls-list"])){acl_list();exit;}
	if(isset($_GET["Addacl-js"])){acl_js();exit;}
	if(isset($_GET["Addacl-group"])){acl_group_js();exit;}
	if(isset($_GET["acl-rule-settings-group"])){acl_group_settings();exit;}
	if(isset($_GET["acl-rule-group"])){page();exit;}
	
	if(isset($_GET["acl-rule-tabs"])){acl_rule_tab();exit;}
	if(isset($_GET["acl-rule-settings"])){acl_rule_settings();exit;}
	if(isset($_POST["acl-rule-delete"])){acl_rule_delete();exit;}
	if(isset($_POST["acl-rule-enable"])){acl_rule_enable();exit;}
	if(isset($_POST["acl-rule-move"])){acl_rule_move();exit;}
	if(isset($_POST["acl-rule-order"])){acl_rule_order();exit;}
	if(isset($_POST["aclrulename"])){acl_main_rule_edit();exit;}
	if(isset($_POST["ApplySquid"])){squid_compile();exit;}
	if(isset($_GET["csv"])){output_scv();exit;}
	if(isset($_POST["EnableSquidPortsRestrictions"])){EnableSquidPortsRestrictions();exit;}
	if(isset($_POST["SquidAllowSmartPhones"])){SquidAllowSmartPhones();exit;}
	
	page();

	
function acl_group_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=-1;}
	if($ID<1){
		$title=$tpl->javascript_parse_text("{new_rule}");
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM webfilters_sqacls WHERE ID='$ID'"));
		$title=utf8_encode($ligne["aclname"]);
	}
	$t=time();
	$html="var x_aclCallBack= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-{$_GET["t"]}').flexReload();
		ExecuteByClassName('SearchFunction');
	}	
	
	
	function AclCreateNewAclRule$t(){
		var aclname=prompt('$title','New Rule...');
		if(!aclname){return;}
		var XHR = new XHRConnection();
		XHR.appendData('aclname-save', aclname);
		XHR.appendData('ID', '$ID');
		XHR.appendData('aclport', '{$_GET["listen-port"]}');
		XHR.appendData('aclgroup', '1');		      
		XHR.sendAndLoad('$page', 'POST',x_aclCallBack);  			
	}

	AclCreateNewAclRule$t();
	";
	
	echo $html;
	
	
}	
	
function acl_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$t=time();
	$ID=$_GET["ID"];
	if(!is_numeric($_GET["rule-master"])){$_GET["rule-master"]=0;}
	if(!is_numeric($ID)){$ID=-1;}
	if($ID<1){
		$title=$tpl->javascript_parse_text("{new_rule}");
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,aclgroup FROM webfilters_sqacls WHERE ID='$ID'"));
		$title=utf8_encode($ligne["aclname"]);
		$aclgroup=$ligne["aclgroup"];
	}
	if(!is_numeric($aclgroup)){$aclgroup=0;}
	
	$t=time();
	$html="var x_aclCallBack= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-{$_GET["t"]}').flexReload();
		
	}	
	
	
	function AclCreateNewAclRule$t(){
		var aclname=prompt('$title','New Rule...');
		if(!aclname){return;}
		var XHR = new XHRConnection();
		XHR.appendData('aclname-save', aclname);
		XHR.appendData('ID', '$ID');
		XHR.appendData('aclport', '{$_GET["listen-port"]}');	
		XHR.appendData('aclgpid', '{$_GET["rule-master"]}');		      
		XHR.sendAndLoad('$page', 'POST',x_aclCallBack);  			
	}
	
	function aclShowMainStart$t(){
		var size=650;
		var aclgroup=$aclgroup;
		var ID=$ID;
		if( ID<0 ){ AclCreateNewAclRule$t(); return; }
		if(aclgroup==1){size=1000;}
		if(aclgroup==1){
			YahooSearchUser(size,'$page?acl-rule-tabs=yes&ID=$ID&t={$_GET["t"]}','$title');
			return;
		}
		YahooWin2(size,'$page?acl-rule-tabs=yes&ID=$ID&t={$_GET["t"]}','$title');
	
	}
	aclShowMainStart$t();
	";
	
	echo $html;
	
	
}

function acl_rule_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,aclgroup FROM webfilters_sqacls WHERE ID='$ID'"));
	
	if($ligne["aclgroup"]==0){
		$array["acl-rule-settings"]='{settings}';
		$array["acl-items"]='{objects}';
	}else{
		$array["acl-rule-group"]='{rules}';
		$array["acl-rule-settings-group"]='{settings}';
		
		
	}
	if($ID==0){unset($array["acl-items"]);}
	

	while (list ($num, $ligne) = each ($array) ){
		if($num=="acl-items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"squid.acls-rules.items.php?$num=yes&aclid=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
			
		}
		if($num=="acl-rule-group"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes&aclgroup-id=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes&ID=$ID&t=$t\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo build_artica_tabs($html, "main_acl_rule_zoom_$ID");
	
}

function acl_group_settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];	
	$t=time();
	$please_choose_a_bandwith_rule=$tpl->javascript_parse_text("{please_choose_a_bandwith_rule}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,acltpl FROM webfilters_sqacls WHERE ID='$ID'"));
	$aclname=utf8_encode($ligne["aclname"]);
	$acltpl=$ligne["acltpl"];
	$t=time();
	
	$html="	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{rule_name}:</td>
		<td>". Field_text("aclrulename-$t",$aclname,"font-size:16px;width:420px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>". button("{apply}","SaveAclRule$ID()","18")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveAclRule$ID= function (obj) {
			document.getElementById('divid$t').innerHTML='';	
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			RefreshTab('main_acl_rule_zoom');
			$('#table-$t').flexReload();
			ExecuteByClassName('SearchFunction');
		}
	
		function SaveAclRule$ID(){
			var XHR = new XHRConnection();
			XHR.appendData('aclrulename-group', document.getElementById('aclrulename-$t').value);

		}
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
		
}


function acl_rule_settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];	
	$t=time();
	
	$results=$q->QUERY_SQL("SELECT aclname,ID FROM webfilters_sqacls WHERE aclgroup=1");
	if(mysql_num_rows($results)>0){
		
		$ACLSGROUPS[0]="{none}";
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclname=utf8_encode($ligne["aclname"]);
			$ACLSGROUPS[$ligne["ID"]]=$ligne["aclname"];
		
		}
		
	}
	
	if(!$q->FIELD_EXISTS("webfilters_sqacls", "PortDirection")){$q->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `PortDirection` smallint(1) NOT NULL DEFAULT '0',ADD INDEX(`PortDirection`)");}
	
	
	$please_choose_a_bandwith_rule=$tpl->javascript_parse_text("{please_choose_a_bandwith_rule}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,acltpl,aclgpid,PortDirection FROM webfilters_sqacls WHERE ID='$ID'"));
	
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	
	$aclname=utf8_encode($ligne["aclname"]);
	$acltpl=$ligne["acltpl"];
	$aclgpid=$ligne["aclgpid"];
	$PortDirection=$ligne["PortDirection"];
	
	
	
	if(!is_numeric($PortDirection)){$PortDirection=0;}
	$PortDirectionS[0]="{all_methods}";
	$PortDirectionS[1]="{standard_method}";
	$PortDirectionS[2]="{transparent_method}";
	$PortDirectionS[3]="{smartphones_port}";
	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$arrayNICZ[null]="0.0.0.0";

	while (list ($eth, $none) = each ($interfaces) ){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		$arrayNICZ[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	}
	
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$resultsPorts = $q->QUERY_SQL($sql);
	while ($lignePorts = mysql_fetch_assoc($resultsPorts)) {
		$eth=$lignePorts["nic"];
		$ipaddr=$arrayNICZ[$eth];
		$port=$lignePorts["port"];
		$IDPort=$lignePorts["ID"];
		$PortDirectionS[$IDPort]="{port} $port [$ipaddr]";
	}
	

	
	
	$squid=new squidbee();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='url_rewrite_access_deny'"));
	$url_rewrite_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($url_rewrite_access_deny)){$url_rewrite_access_deny=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='url_rewrite_access_allow'"));
	$url_rewrite_access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($url_rewrite_access_allow)){$url_rewrite_access_allow=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='access_deny'"));
	$access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($access_deny)){$access_deny=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='adaptation_access_deny'"));
	$adaptation_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($adaptation_access_deny)){$adaptation_access_deny=0;}		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='cache_deny'"));
	$cache_deny=$ligne["httpaccess_value"];
	if(!is_numeric($cache_deny)){$cache_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='access_allow'"));
	$access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($access_allow)){$access_allow=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='http_reply_access_deny'"));
	$http_reply_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($http_reply_access_deny)){$http_reply_access_deny=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='http_reply_access_allow'"));
	$http_reply_access_allow=$ligne["http_reply_access_allow"];
	if(!is_numeric($http_reply_access_allow)){$http_reply_access_allow=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='cache_parent'"));
	$cache_parent=$ligne["httpaccess_value"];
	if(!is_numeric($cache_parent)){$cache_parent=0;}	
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='snmp_access_allow'"));
	$snmp_access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($snmp_access_allow)){$snmp_access_allow=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='log_access'"));
	$log_access=$ligne["httpaccess_value"];
	if(!is_numeric($log_access)){$log_access=0;}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_access_except'"));
	$deny_access_except=$ligne["httpaccess_value"];
	if(!is_numeric($deny_access_except)){$deny_access_except=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='tcp_outgoing_tos'"));
	$tcp_outgoing_tos=$ligne["httpaccess_value"];
	$tcp_outgoing_tos_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_tos)){$tcp_outgoing_tos=0;}	
	if($tcp_outgoing_tos_value==null){$tcp_outgoing_tos_value="0x20";}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='reply_body_max_size'"));
	$reply_body_max_size=intval($ligne["httpaccess_value"]);
	$reply_body_max_size_value=intval($ligne["httpaccess_data"]);
		
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='delay_access'"));
	$delay_access=$ligne["httpaccess_value"];
	$delay_access_id=$ligne["httpaccess_data"];
	if(!is_numeric($delay_access)){$delay_access=0;}	
	if(!is_numeric($delay_access_id)){$delay_access_id=0;}
	
	
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='tcp_outgoing_address'"));
	$tcp_outgoing_address=$ligne["httpaccess_value"];
	$tcp_outgoing_address_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_address)){$tcp_outgoing_address=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_quota_rule'"));
	$deny_quota_rule=$ligne["httpaccess_value"];
	$deny_quota_rule_id=$ligne["httpaccess_data"];
	if(!is_numeric($deny_quota_rule)){$deny_quota_rule=0;}	
	if($deny_quota_rule_id>0){
		$q3=new mysql();
		$ligne3=mysql_fetch_array($q3->QUERY_SQL("SELECT QuotaName FROM ext_time_quota_acl WHERE ID=$deny_quota_rule_id","artica_backup"));
		$deny_quota_rule_value=$ligne3["QuotaName"];
	}
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_log'"));
	$deny_log=$ligne["httpaccess_value"];
	if(!is_numeric($deny_log)){$deny_log=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='request_header_add'"));
	$request_header_add=$ligne["httpaccess_value"];
	$request_header_add_value=unserialize(base64_decode($ligne["httpaccess_data"]));
	if(!is_numeric($request_header_add)){$request_header_add=0;}else{
		$request_header_add_name=$request_header_add_value["header_name"];
		$request_header_add_value=$request_header_add_value["header_value"];
	}	
	
	
	$is33=0;
	$explain_no33squid="{explain_no33squid}: $squid->SQUID_VERSION";
	if($squid->IS_33){
		$is33=1;
		$explain_no33squid=null;
	}
	
	
	
	
	
	if($acltpl==null){$acltpl="{default}";}
	
	
	else{
			$md5=$acltpl;
			$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$acltpl}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$acltpl=addslashes($ligne2["template_title"]);
			$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($acltpl)."');";
			$acltpl="<a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:14px;text-decoration:underline'>$acltpl</a>";
		
	}
	
	if(intval($ligne["tplreset"])==1){
		$acltpl="<span style='font-size:14px;'>{reset_connection}</span>";
		
	}
	
	
	
	if($delay_access_id>0){
		$q2=new mysql();
		$sql="SELECT rulename FROM squid_pools WHERE ID='$delay_access_id'";
		$ligne=mysql_fetch_array($q2->QUERY_SQL($sql,"artica_backup"));	
		$delay_access_id_text=$tpl->javascript_parse_text(utf8_encode($ligne["rulename"]));
	}
	$ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
	$ipz=new networking();
	$ipss=$ipz->ALL_IPS_GET_ARRAY();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$html="
	<div id='FormToParse$t'>
	<div id='divid$t' ></div> 
	
	<div style='width:98%' class=form>
	<table style='width:100%' class=TableRemove>
	<tr>
		<td class=legend style='font-size:18px'>{rule_name}:</td>
		<td>". Field_text("aclrulename",$aclname,"font-size:18px;width:360px",null,null,null,false,"SaveAclRule{$ID}Check(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{method}:</td>
		<td>". Field_array_Hash($PortDirectionS,"PortDirection-$t",$PortDirection,null,null,0,"font-size:18px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{rule_group}:</td>
		<td>". Field_array_Hash($ACLSGROUPS,"aclgpid-$t",$aclgpid,null,null,0,"font-size:18px")."</td>
	</tr>
				
	
	</table>
	
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{allow}:</td>
		<td>". Field_checkbox_design("access_allow",1,$access_allow,"access_allow_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{deny_access}:</td>
		<td>". Field_checkbox_design("access_deny",1,$access_deny,"access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{deny_reply_access}:</td>
		<td>". Field_checkbox_design("http_reply_access_deny",1,$http_reply_access_deny,"http_reply_access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_reply_access}:</td>
		<td>". Field_checkbox_design("http_reply_access_allow",1,$http_reply_access_allow,"http_reply_access_allow_check()")."</td>
	</tr>
				
	
	<tr>
		<td class=legend style='font-size:18px'>{deny_access_except}:</td>
		<td>". Field_checkbox_design("deny_access_except",1,$deny_access_except,"deny_access_except_check()")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{pass_trough_thewebfilter_engine}:</td>
		<td>". Field_checkbox_design("url_rewrite_access_deny",1,$url_rewrite_access_deny,"url_rewrite_access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{force_using_thewebfilter_engine}:</td>
		<td>". Field_checkbox_design("url_rewrite_access_allow",1,$url_rewrite_access_allow,"url_rewrite_access_allow_check()")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{pass_trough_antivirus_engine}:</td>
		<td>". Field_checkbox_design("adaptation_access_deny",1,$adaptation_access_deny,"adaptation_access_deny_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{allow_snmp_access}:</td>
		<td>". Field_checkbox_design("snmp_access_allow",1,$snmp_access_allow,"snmp_access_allow_check()")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:18px'>{do_not_cache}:</td>
		<td>". Field_checkbox_design("cache_deny",1,$cache_deny,"cache_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.acls.proxy.parent.php?aclid=$ID');\"
		style='text-decoration:underline'>
		{use_parent_proxy}</a>:</td>
		<td>". Field_checkbox_design("cache_parent",1,$cache_parent,"cache_parent_check()")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{log_to_csv}:</td>
		<td>". Field_checkbox_design("log_access",1,$log_access,"log_access_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{deny_logging}:</td>
		<td>". Field_checkbox_design("deny_log",1,$deny_log,"deny_log_check()")."</td>
	</tr>
	</table>

	<hr>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{limit_bandwidth}:</td>
			<td>". Field_checkbox_design("delay_access",1,$delay_access,"limit_bandwidth_check()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{bandwidth}:</td>
			<td>
				<span id='delay_access_id_text' style='font-size:18px;font-weight:bold'>$delay_access_id_text</span>
				<input type='hidden' id='delay_access_id' value='$delay_access_id'>
			</td>
			<td width=1%>". button('{browse}...',"Loadjs('squid.bandwith.php?browser-acl-js=yes&aclruleid=$ID')")."</td>
		</tr>			
		</table>
	
				
	<hr>		
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{affect_quota_rule}:</td>
		<td>". Field_checkbox_design("deny_quota_rule",1,$deny_quota_rule,"deny_quota_rule_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{quota_rule}:</td>
		<td>
			<span id='deny_quota_rule_id_text' style='font-size:18px;font-weight:bold'>[$deny_quota_rule_id]:$deny_quota_rule_value</span>
			<input type='hidden' id='deny_quota_rule_id' value='$deny_quota_rule_id'>
		</td>
		<td width=1%>". button('{browse}...',"Loadjs('squid.ext_time_quota_acl.php?browser-quota-js=yes&checkbowid=deny_quota_rule&textid=deny_quota_rule_id_text&idnum=deny_quota_rule_id')")."</td>
	</tr>			
	</table>				
	
				
	<hr>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{request_header_add}:</td>
		<td>". Field_checkbox_design("request_header_add",1,$request_header_add,"request_header_addCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{header_name}:</td>
		<td>". Field_text("request_header_add_name",$request_header_add_name,'font-size:18px;width:210px')."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{header_value}:</td>
		<td>". Field_text("request_header_add_value",$request_header_add_value,'font-size:18px;width:210px')."</td>
	</tr>					
	</table>
	<div><i style='font-size:11px'>$explain_no33squid</i></div>	

	
	<hr>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{reply_body_max_size_acl}:</td>
			<td>". Field_checkbox_design("reply_body_max_size",1,$reply_body_max_size,"reply_body_max_sizeCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{max_size}:</td>
			<td style='font-size:18px'>". Field_text("reply_body_max_size_value",$reply_body_max_size_value,'font-size:18px;width:90px')."&nbsp;MB</td>
		</tr>	
	</table>
	
	<hr>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{tcp_outgoing_tos}:</td>
			<td>". Field_checkbox_design("tcp_outgoing_tos",1,$tcp_outgoing_tos,"tcp_outgoing_tosCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{tcp_outgoing_tos_value}:</td>
			<td>". Field_array_Hash($ayDscp,"tcp_outgoing_tos_value",$tcp_outgoing_tos_value,'style:font-size:18px;')."</td>
		</tr>	
	</table>


	
	<hr>
	<table style='width:100%'>	
	<tr>
		<td class=legend style='font-size:18px'>{acl_tcp_outgoing_address}:</td>
		<td>". Field_checkbox_design("tcp_outgoing_address-$t",1,$tcp_outgoing_address,"tcp_outgoing_address_check$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{ipaddr}:</td>
		<td>". Field_array_Hash($ipss,"tcp_outgoing_address_value",$tcp_outgoing_address_value,null,null,0,"font-size:18px")."</td>
	</tr>	
	</table>	
	
	
	
	<table style='width:100%'>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveAclRule$ID()",22)."</td>
	</tr>
	</table>
	</div>
	
	<script>
	
		var x_SaveAclRule$ID= function (obj) {
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			$('#table-$t').flexReload();
			ExecuteByClassName('SearchFunction');
		}
		
		function  SaveAclRule{$ID}Check(e){
			if(!checkEnter(e)){return;}
			SaveAclRule$ID();
		}
	
		function SaveAclRule$ID(){
			var XHR = new XHRConnection();
			XHR.appendData('aclrulename', document.getElementById('aclrulename').value);
			XHR.appendData('aclgpid', document.getElementById('aclgpid-$t').value);
			XHR.appendData('PortDirection', document.getElementById('PortDirection-$t').value);
			
			
			XHR.appendData('tcp_outgoing_tos_value', document.getElementById('tcp_outgoing_tos_value').value);
			XHR.appendData('tcp_outgoing_address_value', document.getElementById('tcp_outgoing_address_value').value);
			var delay_access_id=document.getElementById('delay_access_id').value;
			
			if(document.getElementById('delay_access').checked){
				if(delay_access_id==0){
					alert('$please_choose_a_bandwith_rule');
					return;
				}
			}
			XHR.appendData('delay_access_id', document.getElementById('delay_access_id').value);
			XHR.appendData('ID', '$ID');
			if(document.getElementById('tcp_outgoing_tos').checked){XHR.appendData('tcp_outgoing_tos', '1');}else{XHR.appendData('tcp_outgoing_tos', '0');}
			if(document.getElementById('access_allow').checked){XHR.appendData('access_allow', '1');}else{XHR.appendData('access_allow', '0');}
			if(document.getElementById('deny_access_except').checked){XHR.appendData('deny_access_except', '1');}else{XHR.appendData('deny_access_except', '0');}
			if(document.getElementById('url_rewrite_access_deny').checked){XHR.appendData('url_rewrite_access_deny', '1');}else{XHR.appendData('url_rewrite_access_deny', '0');}
			if(document.getElementById('url_rewrite_access_allow').checked){XHR.appendData('url_rewrite_access_allow', '1');}else{XHR.appendData('url_rewrite_access_allow', '0');}
			if(document.getElementById('access_deny').checked){XHR.appendData('access_deny', '1');}else{XHR.appendData('access_deny', '0');}
			if(document.getElementById('adaptation_access_deny').checked){XHR.appendData('adaptation_access_deny', '1');}else{XHR.appendData('adaptation_access_deny', '0');}
			if(document.getElementById('cache_deny').checked){XHR.appendData('cache_deny', '1');}else{XHR.appendData('cache_deny', '0');}
			if(document.getElementById('delay_access').checked){XHR.appendData('delay_access', '1');}else{XHR.appendData('delay_access', '0');}
			if(document.getElementById('tcp_outgoing_address-$t').checked){XHR.appendData('tcp_outgoing_address', '1');}else{XHR.appendData('tcp_outgoing_address', '0');}
			if(document.getElementById('snmp_access_allow').checked){XHR.appendData('snmp_access_allow', '1');}else{XHR.appendData('snmp_access_allow', '0');}
			if(document.getElementById('log_access').checked){XHR.appendData('log_access', '1');}else{XHR.appendData('log_access', '0');}
			if(document.getElementById('request_header_add').checked){XHR.appendData('request_header_add', '1');}else{XHR.appendData('request_header_add', '0');}
			if(document.getElementById('deny_log').checked){XHR.appendData('deny_log', '1');}else{XHR.appendData('deny_log', '0');}
			if(document.getElementById('deny_quota_rule').checked){XHR.appendData('deny_quota_rule', '1');}else{XHR.appendData('deny_quota_rule', '0');}
			if(document.getElementById('cache_parent').checked){XHR.appendData('cache_parent', '1');}else{XHR.appendData('cache_parent', '0');}
			
			if(document.getElementById('http_reply_access_allow').checked){XHR.appendData('http_reply_access_allow', '1');}else{XHR.appendData('http_reply_access_allow', '0');}
			if(document.getElementById('http_reply_access_deny').checked){XHR.appendData('http_reply_access_deny', '1');}else{XHR.appendData('http_reply_access_deny', '0');}
			
			if(document.getElementById('reply_body_max_size').checked){XHR.appendData('reply_body_max_size', '1');}else{XHR.appendData('reply_body_max_size', '0');}
			XHR.appendData('reply_body_max_size_value', document.getElementById('reply_body_max_size_value').value);
			
			XHR.appendData('deny_quota_rule_id', document.getElementById('deny_quota_rule_id').value);
			XHR.appendData('request_header_add_name', document.getElementById('request_header_add_name').value);
			XHR.appendData('request_header_add_value', document.getElementById('request_header_add_value').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveAclRule$ID);  		
		
		}
		
		

	
	function CheckAll(){
	var c=0;
	$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		if(document.getElementById(id).checked){c=c+1;}
		 	}
		 	
		});		
		
	if(c==0){
		$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
			 	var \$t = $(this);
			 	var id=\$t.attr('id');
			 	var value=\$t.attr('value');
			 	var type=\$t.attr('type');
			 	if(type=='checkbox'){
			 		document.getElementById(id).disabled=false;
			 	}
			 	
			});			
	
		}
	
	}
	
	function DisableAllInstead(zid){
		$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	if(zid==id){return;}
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		document.getElementById(id).checked=false;
		 		document.getElementById(id).disabled=true;
		 	}
		 	
		});	
	}
	
	function limit_bandwidth_check(nosave){
		if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('delay_access').checked){DisableAllInstead('delay_access');}else{CheckAll();}
		
	}
	
	function access_allow_check(nosave){
		if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('access_allow').checked){DisableAllInstead('access_allow');}else{CheckAll();}
	}
	
	
	function access_deny_check(nosave){
		if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('access_deny').checked){DisableAllInstead('access_deny');}else{CheckAll();}
	}
	
	function http_reply_access_deny_check(nosave){
		if(!nosave){SaveAclRule$ID(nosave);}	
		if(document.getElementById('http_reply_access_deny').checked){DisableAllInstead('http_reply_access_deny');}else{CheckAll();}
	}
	
	function http_reply_access_allow_check(nosave){
		if(!nosave){SaveAclRule$ID();}	
		if(document.getElementById('http_reply_access_allow').checked){DisableAllInstead('http_reply_access_allow');}else{CheckAll();}
	}
	
	function deny_log_check(nosave){
		if(!nosave){SaveAclRule$ID();}	
		if(document.getElementById('deny_log').checked){DisableAllInstead('deny_log');}else{CheckAll();}
	}

	function cache_deny_check(nosave){
		if(!nosave){SaveAclRule$ID();}	
		if(document.getElementById('cache_deny').checked){DisableAllInstead('cache_deny');}else{CheckAll();}
	
	}
	
	function cache_parent_check(nosave){
		if(!nosave){SaveAclRule$ID();}	
		if(document.getElementById('cache_parent').checked){DisableAllInstead('cache_parent');}else{CheckAll();}
	}
	
	function adaptation_access_deny_check(nosave){
		if(!nosave){SaveAclRule$ID();}	
		if(document.getElementById('adaptation_access_deny').checked){DisableAllInstead('adaptation_access_deny');}else{CheckAll();}
	}
	
	function url_rewrite_access_deny_check(nosave){
		if(!nosave){SaveAclRule$ID(nosave);}	
		if(document.getElementById('url_rewrite_access_deny').checked){DisableAllInstead('url_rewrite_access_deny');}else{CheckAll();}
	}
	function url_rewrite_access_allow_check(nosave){
		if(!nosave){SaveAclRule$ID(nosave);}	
		if(document.getElementById('url_rewrite_access_allow').checked){DisableAllInstead('url_rewrite_access_allow');}else{CheckAll();}
	}	
	
	
	function snmp_access_allow_check(nosave){
		if(!nosave){SaveAclRule$ID(nosave);}	
		if(document.getElementById('snmp_access_allow').checked){DisableAllInstead('snmp_access_allow');}else{CheckAll();}
	}
	
	function log_access_check(nosave){
		if(!nosave){SaveAclRule$ID(nosave);}	
		if(document.getElementById('log_access').checked){DisableAllInstead('log_access');}else{CheckAll();}
	}
	
	
	function deny_quota_rule_check(nosave){
		if(!nosave){SaveAclRule$ID();}	
		if(document.getElementById('deny_quota_rule').checked){
			DisableAllInstead('deny_quota_rule');


		}else{CheckAll();}
		
	 } 
	
	
	function tcp_outgoing_address_check$t(nosave){
		if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('tcp_outgoing_address-$t').checked){
		
			
			DisableAllInstead('tcp_outgoing_address');
			document.getElementById('tcp_outgoing_address_value').disabled=false;
			document.getElementById('tcp_outgoing_address-$t').checked=true;
			document.getElementById('tcp_outgoing_address-$t').disabled=false;
		}else{
			document.getElementById('tcp_outgoing_address_value').disabled=true;
			CheckAll();
		}
	}	
	

	function tcp_outgoing_tosCheck(nosave){
				if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('tcp_outgoing_tos').checked){
			DisableAllInstead('tcp_outgoing_tos');
			document.getElementById('tcp_outgoing_tos_value').disabled=false;
		}else{
			document.getElementById('tcp_outgoing_tos_value').disabled=true;
			CheckAll();
		}
	}
	
	function deny_access_except_check(nosave){
			if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('deny_access_except').checked){DisableAllInstead('deny_access_except');}else{CheckAll();}
	}
	
	function request_header_addCheck(nosave){
		var is33=$is33;
		if(is33==0){return;}
		if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('request_header_add').checked){
			DisableAllInstead('request_header_add');
			document.getElementById('request_header_add_name').disabled=false;
			document.getElementById('request_header_add_value').disabled=false;
		}else{
			document.getElementById('request_header_add_name').disabled=true;
			document.getElementById('request_header_add_value').disabled=true;	
			CheckAll();	
		}
		
	}
	
	
	function reply_body_max_sizeCheck(nosave){
			if(!nosave){SaveAclRule$ID();}
		if(document.getElementById('reply_body_max_size').checked){
			DisableAllInstead('reply_body_max_size');
			document.getElementById('reply_body_max_size').disabled=false;
			document.getElementById('reply_body_max_size_value').disabled=false;
		}else{
			document.getElementById('reply_body_max_size').disabled=true;
			document.getElementById('reply_body_max_size_value').disabled=true;	
			CheckAll();	
		}
	
	}
	function features33_check(){
		var is33=$is33;
		document.getElementById('request_header_add').disabled=true;
		document.getElementById('request_header_add_value').disabled=true;
		document.getElementById('request_header_add_name').disabled=true;
		if(is33==0){return;}

		document.getElementById('request_header_add').disabled=false;
		if(document.getElementById('request_header_add').checked){
			DisableAllInstead('request_header_add');
			document.getElementById('request_header_add_name').disabled=false;
			document.getElementById('request_header_add_value').disabled=false;		
		}
		
	}
	
	
	limit_bandwidth_check(true);
	access_allow_check(true);
	access_deny_check(true);
	deny_access_except_check(true);
	tcp_outgoing_tosCheck(true);
	cache_deny_check(true);
	adaptation_access_deny_check(true);
	url_rewrite_access_deny_check(true);
	url_rewrite_access_allow_check(true);
	tcp_outgoing_address_check$t(true);
	snmp_access_allow_check(true);
	log_access_check(true);
	features33_check(true);
	deny_quota_rule_check(true);
	http_reply_access_deny_check(true);	
	http_reply_access_allow_check(true);
	CheckBoxDesignRebuild();
	</script>
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function acl_main_rule_edit(){
	if(!isset($_POST["aclgpid"])){$_POST["aclgpid"]=0;}
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	if(!isset($_POST["tcp_outgoing_tos_value"])){$_POST["tcp_outgoing_tos_value"]=null;}
	if(!isset($_POST["tcp_outgoing_address_value"])){$_POST["tcp_outgoing_address_value"]=null;}
	try {
		
		$q=new mysql_squid_builder();
		$acl=new squid_acls_groups();
		$ID=$_POST["ID"];
		$aclname=$_POST["aclrulename"];
		
		
		if(isset($_POST["PortDirection"])){$PortDirection=",`PortDirection`='{$_POST["PortDirection"]}'";}
		
		$sql="UPDATE webfilters_sqacls SET aclname='$aclname',
		`aclgpid`='{$_POST["aclgpid"]}'$PortDirection WHERE ID='$ID'";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		if(!$acl->aclrule_edittype($ID,"access_allow",$_POST["access_allow"])){return;}
		if(!$acl->aclrule_edittype($ID,"url_rewrite_access_deny",$_POST["url_rewrite_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"url_rewrite_access_allow",$_POST["url_rewrite_access_allow"])){return;}
		if(!$acl->aclrule_edittype($ID,"access_deny",$_POST["access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"adaptation_access_deny",$_POST["adaptation_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"cache_deny",$_POST["cache_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_access_except",$_POST["deny_access_except"])){return;}
		if(!$acl->aclrule_edittype($ID,"tcp_outgoing_tos",$_POST["tcp_outgoing_tos"],$_POST["tcp_outgoing_tos_value"])){return;}
		
		
		if(!$acl->aclrule_edittype($ID,"reply_body_max_size",$_POST["reply_body_max_size"],$_POST["reply_body_max_size_value"])){return;}
		
		
		
		if(!$acl->aclrule_edittype($ID,"tcp_outgoing_address",$_POST["tcp_outgoing_address"],$_POST["tcp_outgoing_address_value"])){return;}
		if(!$acl->aclrule_edittype($ID,"delay_access",$_POST["delay_access"],$_POST["delay_access_id"])){return;}
		if(!$acl->aclrule_edittype($ID,"snmp_access_allow",$_POST["snmp_access_allow"],$_POST["snmp_access_allow"])){return;}
		if(!$acl->aclrule_edittype($ID,"log_access",$_POST["log_access"],$_POST["log_access"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_log",$_POST["deny_log"])){return;}
		if(!$acl->aclrule_edittype($ID,"cache_parent",$_POST["cache_parent"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_quota_rule",$_POST["deny_quota_rule"],$_POST["deny_quota_rule_id"])){return;}
		if(!$acl->aclrule_edittype($ID,"http_reply_access_deny",$_POST["http_reply_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"http_reply_access_allow",$_POST["http_reply_access_allow"])){return;}
		
		
		
		
		$request_header_add_value["header_name"]=$_POST["request_header_add_name"];
		$request_header_add_value["header_value"]=$_POST["request_header_add_value"];		
		$request_header_add_value_final=base64_encode(serialize($request_header_add_value));
		if(!$acl->aclrule_edittype($ID,"request_header_add",$_POST["request_header_add"],$request_header_add_value_final)){return;}
		


		
		
		
										
	} catch (Exception $e) {
		echo $e->getMessage();
		return ;
	}
	
	
	

}

function TemplateName($md5){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	if($md5==null){return $tpl->_ENGINE_parse_body("<br>{and_display_error_page}: <strong>{default}</strong>");}
	$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$acltpl}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($ligne["template_title"])."');";
	return $tpl->_ENGINE_parse_body("<br>{and_display_error_page}: <a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:12px;text-decoration:underline'>{$ligne["template_title"]}</a>");
	}
	
function EnableSquidPortsRestrictions(){
	$sock=new sockets();
	$EnableSquidPortsRestrictions=$sock->GET_INFO("EnableSquidPortsRestrictions");
	if(!is_numeric($EnableSquidPortsRestrictions)){$EnableSquidPortsRestrictions=0;}
	if($EnableSquidPortsRestrictions==0){$sock->SET_INFO("EnableSquidPortsRestrictions",1);}
	if($EnableSquidPortsRestrictions==1){$sock->SET_INFO("EnableSquidPortsRestrictions",0);}
	
}
function SquidAllowSmartPhones(){
	$sock=new sockets();
	$sock->SET_INFO("SquidAllowSmartPhones", $_POST["SquidAllowSmartPhones"]);
}

function acl_rule_group_save(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_sqacls SET aclname='{$_POST["aclrulename-group"]}' WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function acl_rule_save(){
	$q=new mysql_squid_builder();
	$ID=$_POST["ID"];
	$aclname=$_POST["aclname-save"];
	if(!isset($_POST["aclgroup"])){$_POST["aclgroup"]=0;}
	if(!isset($_POST["aclgpid"])){$_POST["aclgpid"]=0;}
	if(!isset($_POST["aclport"])){$_POST["aclport"]=0;}
	if(is_numeric($_POST["aclport"])){$_POST["aclport"]=0;}
	
	$aclname=str_replace("'", "`", $aclname);
	
	if(!$q->FIELD_EXISTS("webfilters_sqacls", "aclgroup")){
		$q->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `aclgroup` smallint(1) NOT NULL,ADD INDEX(`aclgroup`)");
	}
	if(!$q->FIELD_EXISTS("webfilters_sqacls", "aclgpid")){
		$q->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `aclgpid` INT UNSIGNED NOT NULL,ADD INDEX(`aclgpid`)");
	}	
	
	if(!$q->FIELD_EXISTS("webfilters_sqacls", "aclport")){
		$q->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `aclport` smallint(5) NOT NULL,
		ADD INDEX(`aclport`)");
	}
	
	if(!$q->FIELD_EXISTS("webfilters_sqacls", "PortDirection")){
		$q->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `PortDirection` smallint(1) NOT NULL DEFAULT '0',ADD INDEX(`PortDirection`)");
	}	
	
	if($ID<0){
		$q->CheckTables();
				
		$sql="SELECT xORDER FROM webfilters_sqacls WHERE ORDER BY xORDER DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$xORDER=$ligne["xORDER"];
		if(!is_numeric($xORDER)){$xORDER=0;}
		$xORDER=$xORDER+1;
		$aclport=trim($_POST["aclport"]);
		if(!is_numeric($aclport)){$aclport=0;}
		$sql="INSERT INTO webfilters_sqacls (aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid) 
		VALUES ('$aclname',1,'','$xORDER','$aclport','{$_POST["aclgroup"]}','{$_POST["aclgpid"]}')";
	}
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\nLine:".__LINE__."\nfunction\n".__FUNCTION__."\nsql:$sql";return;}
		
}

function acl_rule_delete(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ID=$_POST["acl-rule-delete"];
	acl_rule_delete_perform($ID);

	
	
}

function acl_rule_delete_perform($ID){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilters_sqacls WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sql="SELECT ID,enabled FROM webfilters_sqacls WHERE aclgpid=$ID";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		acl_rule_delete_perform($ligne["ID"]);
	}
	
}

function squid_compile(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
}

function acl_rule_enable(){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET enabled={$_POST["enable"]} WHERE ID={$_POST["acl-rule-enable"]}");
		if(!$q->ok){echo $q->mysql_error;return;}	
}
function acl_rule_disable_all(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE webfilters_sqacls SET enabled=0");
	if(!$q->ok){echo $q->mysql_error;return;}	
}
function acl_rule_enable_all(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE webfilters_sqacls SET enabled=1");
	if(!$q->ok){echo $q->mysql_error;return;}
}	
function acl_rule_order(){
	$q=new mysql_squid_builder();
	$ID=$_POST["acl-rule-order"];
	$sql="SELECT xORDER,aclgpid,aclport FROM webfilters_sqacls WHERE `ID`='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$aclport=$ligne["aclport"];
	$aclgpid=$ligne["aclgpid"];
	
	$neworder=$_POST["acl-rule-value"];
	$sql="SELECT ID,aclgpid,aclport FROM webfilters_sqacls WHERE `xORDER`='{$neworder}' AND aclport=$aclport AND aclgpid=$aclgpid";	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["ID"]>0){
		$alt=$neworder+1;
		$sql="UPDATE webfilters_sqacls SET xORDER=$alt WHERE `ID`={$ligne["ID"]}";
		$q->QUERY_SQL($sql);
	}
	
	$sql="UPDATE webfilters_sqacls SET xORDER=$neworder WHERE `ID`={$ID}";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$c=0;
	$sql="SELECT ID FROM webfilters_sqacls WHERE aclport=$aclport AND aclgpid=$aclgpid ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {	
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER=$c `ID`={$ligne["ID"]}");
		$c++;
	}	
	
}
	
function acl_rule_move(){
	$aclport=$_GET["aclport"];
	if(!is_numeric($aclport)){$aclport=0;}
	$q=new mysql_squid_builder();
	$sql="SELECT xORDER,aclgpid FROM webfilters_sqacls WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$xORDER_ORG=$ligne["xORDER"];
	$aclgpid=$ligne["aclgpid"];
	

	
	$xORDER=$xORDER_ORG;
	if($_POST["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	//echo $sql."\n";
	
	if($_POST["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET 
		xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}' 
		AND xORDER=$xORDER AND aclport=$aclport AND aclgpid=$aclgpid";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}' 
		AND xORDER=$xORDER AND aclport=$aclport AND aclgpid=$aclgpid";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM webfilters_sqacls WHERE aclport=$aclport AND aclgpid=$aclgpid ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {	
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}
	
	
}

function page(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$t=time();
	$order=$tpl->javascript_parse_text("{order}");
	$squid_templates_error=$tpl->javascript_parse_text("{squid_templates_error}");
	$bandwith=$tpl->javascript_parse_text("{bandwith}");
	$session_manager=$tpl->javascript_parse_text("{session_manager}");
	$new_group=$tpl->javascript_parse_text("{new_group_of_rules}");
	$disable_all_rules=$tpl->javascript_parse_text("{disable_all}");
	$enable_all_rules=$tpl->javascript_parse_text("{enable_all}");

	$session_manager="{name: '$session_manager', bclass: 'clock', onpress : SessionManager$t},";
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$create_a_snapshot=$tpl->javascript_parse_text("{create_a_snapshot}");
	$delete_all_acls=$tpl->javascript_parse_text("{delete_all_acls}");
	$Table_title=$tpl->javascript_parse_text("{ACLS}");
	
	
	$table_width=905;
	$newgroup_bt="{name: '<strong style=font-size:18px>$new_group</strong>', bclass: 'add', onpress : AddAclGroup},";
	$apply_paramsbt="{separator: true},{name: '<strong style=font-size:18px>$apply_params</strong>', bclass: 'apply', onpress : SquidBuildNow$t},";
	$optionsbt="{name: '<strong style=font-size:18px>$options</strong>', bclass: 'Settings', onpress : AclOptions$t},";
	
	
	
	if(!is_numeric($_GET["aclgroup-id"])){$_GET["aclgroup-id"]=0;}
	if($_GET["aclgroup-id"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,aclgroup FROM webfilters_sqacls WHERE ID='{$_GET["aclgroup-id"]}'"));
		$ligne["aclname"]=utf8_encode($ligne["aclname"]);
		$Table_title=$tpl->javascript_parse_text("{rules}:{$ligne["aclname"]}");
		$newgroup_bt=null;
		$apply_paramsbt=null;$optionsbt=null;$bandwithbt=null;$session_manager=null;
		$GROUPE_RULE_ID_NEW_RULE="&rule-master={$_GET["aclgroup-id"]}";
		$table_width=959;
	}
	
	// removed {name: '$squid_templates_error', bclass: 'Script', onpress : SquidTemplatesErrors$t},
	
	if($SquidDebugAcls==1){echo FATAL_ERROR_SHOW_128_DESIGN("{debug_acls}", "{debug_acls_explain}","Loadjs('squid.acls.options.php')");}
	
	$fields_size=22;
	$aclname_size=363;
	$items_size=682;
	$icon_size=70;
	if(isset($_GET["aclgroup-id"])){
		if(is_numeric($_GET["aclgroup-id"])){
			if($_GET["aclgroup-id"]>0){
				$fields_size=18;
				$aclname_size=200;
				$items_size=438;
				$icon_size=40;
			}
		}
		
	}
	
	
	
	$html="
	<input type='hidden' name='ACL_ID_MAIN_TABLE' id='ACL_ID_MAIN_TABLE' value='table-$t'>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
function flexigridStart$t(){
$('#table-$t').flexigrid({
	url: '$page?acls-list=yes&t=$t&toexplainorg=table-$t&t=$t&aclgroup-id={$_GET["aclgroup-id"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:{$fields_size}px>$rule', name : 'aclname', width : $aclname_size, sortable : true, align: 'left'},
		{display: '<span style=font-size:{$fields_size}px>$description</span>', name : 'items', width : $items_size, sortable : false, align: 'left'},
		{display: '', name : 'up', width : $icon_size, sortable : false, align: 'center'},
		{display: '', name : 'xORDER', width : $icon_size, sortable : true, align: 'center'},
		{display: '', name : 'none2', width : $icon_size, sortable : true, align: 'center'},
		{display: '', name : 'none3', width : $icon_size, sortable : false, align: 'center'},
		{display: '', name : 'none4', width : $icon_size, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : AddAcl},
	$newgroup_bt
	{separator: true},
	$optionsbt
	{separator: true},
	{name: '<strong style=font-size:18px>$create_a_snapshot</strong>', bclass: 'apply', onpress : SnapShot$t},
	
	{separator: true},
	
	{name: '<strong style=font-size:18px>$enable_all_rules</strong>', bclass: 'Statok', onpress : EnableAll$t},
	{name: '<strong style=font-size:18px>$disable_all_rules</strong>', bclass: 'Disable', onpress : DisableAll$t},
	{name: '<strong style=font-size:18px>$delete_all_acls</strong>', bclass: 'Delz', onpress : DeleteAll$t},
	$apply_paramsbt
		],	
	searchitems : [
		{display: '$rule', name : 'aclname'},
		],
	sortname: 'xORDER',
	sortorder: 'asc',
	usepager: true,
	title: '<strpng style=font-size:30px>$Table_title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 620,
	singleSelect: true
	
	});   
}
function AddAcl() {
	Loadjs('$page?Addacl-js=yes&ID=-1&t=$t$GROUPE_RULE_ID_NEW_RULE');
	
}
function SnapShot$t() {
	Loadjs('snapshots.progress.php');
	
}




function AddAclGroup(){
	Loadjs('$page?Addacl-group=yes&ID=-1&t=$t');
}

function SessionManager$t(){
	Loadjs('squid.ext_time_quota_acl.php?t=$t')
}

function GroupsSection$t(){
	Loadjs('squid.acls.groups.php?js=yes&toexplainorg=table-$t');
}

function BandwithSection$t(){
	Loadjs('squid.bandwith.php?by-acls-js=yes&t=$t');

}

function AclOptions$t(){
	Loadjs('squid.acls.options.php?t=$t');
}

var x_EnableDisableAclRule$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
}

function AclUpDown(ID,dir){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-move', ID);
		XHR.appendData('acl-rule-dir', dir);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  	
}

function ChangeRuleOrder(ID,xdef){
	var neworder=prompt('$order',xdef);
	if(neworder){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-order', ID);
		XHR.appendData('acl-rule-value', neworder);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  	
	}
}

function SquidTemplatesErrors$t(){
	Loadjs('squid.templates.php');
}

function DeleteAll$t(){
	Loadjs('squid.acls.delete.php?t=$t');
}

function DisableAll$t(){
	var XHR = new XHRConnection();
	XHR.appendData('disable-all', 'yes');
	XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  
}
function EnableAll$t(){
	var XHR = new XHRConnection();
	XHR.appendData('enabled-all', 'yes');
	XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  
}


	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	

	
	var x_SquidBuildNow= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}	
	
	
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

	var x_DeleteSquidAclRule$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowacl'+DeleteSquidAclGroupTemp).remove();
	}	
	
	
	function DeleteSquidAclRule(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_rule_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-delete', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclRule$t);
		}  		
	}


	
	function EnableDisableAclRule$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-enable', ID);
		if(document.getElementById('aclid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  		
	}		
	
	function EnableSquidPortsRestrictionsCK(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableSquidPortsRestrictions', 'yes');
	    XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  
	}
	function SquidAllowSmartPhones(){
		var XHR = new XHRConnection();
		if(document.getElementById('SquidAllowSmartPhones').checked){XHR.appendData('SquidAllowSmartPhones', '1');}else{XHR.appendData('SquidAllowSmartPhones', '0');}
	    XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  
	}	
	
	
	
	setTimeout('flexigridStart$t()',800);
	
</script>
	
	";
	
	echo $html;
	
}	


function acl_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	
	$RULEID=$_GET["RULEID"];
	$GROUPE_RULE_ID=$_GET["aclgroup-id"];
	if(!is_numeric($GROUPE_RULE_ID)){$GROUPE_RULE_ID=0;}
	$t=$_GET["t"];
	$search='%';
	$table="webfilters_sqacls";
	$GROUPE_RULE_ID_NEW_RULE=null;
	$page=1;
	$data = array();
	$data['rows'] = array();
	$sock=new sockets();
	$EnableSquidPortsRestrictions=$sock->GET_INFO("EnableSquidPortsRestrictions");
	if(!is_numeric($EnableSquidPortsRestrictions)){$EnableSquidPortsRestrictions=0;}
	if($GROUPE_RULE_ID>0){
		$FORCE_FILTER=" AND aclgpid=$GROUPE_RULE_ID";
	}else{
		$FORCE_FILTER=" AND aclgpid=0";
	}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
		$default=$tpl->_ENGINE_parse_body("{default}");
		$default2=$tpl->_ENGINE_parse_body("{blacklist}");
		$ports_restrictions=$tpl->_ENGINE_parse_body("{ports_restrictions}");
		$http_safe_ports=$tpl->_ENGINE_parse_body("{http_safe_ports}");
		$deny_ports_expect=$tpl->_ENGINE_parse_body("{deny_ports_expect}");
		$q2=new mysql();
		$items=$q2->COUNT_ROWS("urlrewriteaccessdeny", "artica_backup");
		$items2=$q->COUNT_ROWS("deny_websites");
		$explain=$tpl->_ENGINE_parse_body("{urlrewriteaccessdeny_explain} <strong>$items {items}</strong>");
		$explain2=$tpl->_ENGINE_parse_body("{blocked_sites_acl_explain} <strong>$items2 {items}</strong>");
		
		$font_size=20;
		
		
		$data['rows'][] = array(
				'id' => "aclNone1",
				'cell' => array("<a href=\"javascript:blur();\"  
				OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?t={$_GET["t"]}');\"
				style='font-size:20px;text-decoration:underline;color:black'>$default</span></A>
				",
				"<span style='font-size:18px;color:black'>$explain</span>",
				"&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;")
		);
		
		$data['rows'][] = array(
				'id' => "aclNone2",
				'cell' => array("<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('squid.www-blacklist.php?t={$_GET["t"]}');\"
						style='font-size:20px;text-decoration:underline;color:black'>$default2</span></A>
						",
		"<span style='font-size:18px;color:black'>$explain2</span>",
		"&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;")
		);		

		$color="black";
		$colored="#0AAB3D";

		

		$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsSSLList")));
		if(is_array($ports)){while (list ($port, $explain) = each ($ports) ){$bbcSSL[]=$port;}}
		$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsList")));
		if(is_array($ports)){while (list ($port, $explain) = each ($ports) ){$bbcHTTP[]=$port;}}
		
		$color="black";
		$colored="#A71A05";
		if($EnableSquidPortsRestrictions==0){$color="#8a8a8a";$colored=$color;}
		$sslp="$deny_ports_expect: $http_safe_ports SSL: ".@implode(", ", $bbcSSL);
		$http="$deny_ports_expect: $http_safe_ports: ".@implode(", ", $bbcHTTP);
		$enableSSL=Field_checkbox("EnableSquidPortsRestrictions", 1,$EnableSquidPortsRestrictions,
				"EnableSquidPortsRestrictionsCK()");
		
		
		$data['rows'][] = array(
				'id' => "aclNone2",
				'cell' => array("<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('squid.advParameters.php?t={$_GET["t"]}&OnLyPorts=yes');\"
						style='font-size:20px;text-decoration:underline;color:$color'>$ports_restrictions</span></A>
						",
		"<span style='font-size:18px;color:$colored;font-weight:bold'><div>$sslp</div><div>$http</div></span>",
		"&nbsp;","&nbsp;","$enableSSL","&nbsp;","&nbsp;")
		);		
		
		
	}
	$rp=50;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	// Reset for acl group
	if($GROUPE_RULE_ID>0){$data['rows']=array();$total=0;}

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$font_size=18;
	
	if(isset($_GET["aclgroup-id"])){
		if(is_numeric($_GET["aclgroup-id"])){
			if($_GET["aclgroup-id"]>0){
			$font_size=15;
			}
		}
	
	}
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	
	$c=0;
	$acls=new squid_acls_groups();
	$order=$tpl->_ENGINE_parse_body("{order}:");
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$val=0;
		$color="black";
		$disable=Field_checkbox("aclid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableAclRule$t('{$ligne['ID']}')");
		$ligne['aclname']=utf8_encode($ligne['aclname']);
		$delete=imgsimple("delete-42.png",null,"DeleteSquidAclRule('{$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		
		$explain=$tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ligne["enabled"],$ligne["aclgroup"]));
		
		$up=imgsimple("arrow-up-42.png","","AclUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-42.png","","AclUpDown('{$ligne['ID']}',0)");
		$export=imgsimple("42-export.png","","Loadjs('squid.acls.export.php?single-id={$ligne['ID']}')");
		
		if($GROUPE_RULE_ID>0){
			
			$export=null;}
		
	$data['rows'][] = array(
		'id' => "acl{$ligne['ID']}",
		'cell' => array("<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('$MyPage?Addacl-js=yes&ID={$ligne['ID']}&t={$_GET["t"]}');\" 
		style='font-size:{$font_size}px;text-decoration:underline;color:$color'>{$ligne['aclname']}</span></A>
		<div style='font-size:14px'><i>$order&laquo;<a href=\"javascript:blur();\"
		Onclick=\"javascript:ChangeRuleOrder({$ligne['ID']},{$ligne["xORDER"]});\"
		style=\"text-decoration:underline\">{$ligne["xORDER"]}</a>&raquo;</i></div>",
		"<span style='font-size:{$font_size}px;color:$color'>$explain</span>",
		"<center>$up</center>",
		"<center>$down</center>",
		"<center>$disable</center>",
		"<center>$export</center>",
		"<center>$delete</center>")
		);
	}
	
	if($GROUPE_RULE_ID>0){$data['total']=$c;}
	echo json_encode($data);	
}
function output_scv(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$aclid=$_GET["csv"];
	$path=dirname(__FILE__)."/ressources/logs/web/access_acl_$aclid.csv";
	$file=basename($path);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?link-csv=$aclid");
	
	header('Content-type: application/vnd.ms-excel');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé */
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	@unlink($path);
	
}