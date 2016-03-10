<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){$tpl=new templates();echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["aclrulename"])){save();exit;}

function acl_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$t=time();
	$ID=intval($_GET["ID"]);
	if($ID==0){
		$title=$tpl->javascript_parse_text("{new_rule}");
		echo "YahooWin2(650,'$page?popup=yes&ID=0','$title');";
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM meta_webfilters_acls WHERE ID='$ID'"));
		$title=utf8_encode($ligne["aclname"]);
		$aclgroup=$ligne["aclgroup"];
		echo "YahooWin2(790,'$page?acl-rule-tabs=yes&ID=$ID','$title');";
	}


}


if(isset($_GET["acl-rule-tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}

acl_js();

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$array["popup"]='{settings}';
	$array["acl-items"]='{objects}';
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="acl-items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"artica-meta.squid.acls.groups.php?aclid=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
				
		}

		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes&ID=$ID&t=$t\"><span>$ligne</span></a></li>\n");

	}


	echo build_artica_tabs($html, "meta_main_acl_rule_zoom_$ID");

}

function meta_servers(){
	$q=new mysql_meta();
	$sql="SELECT hostname,uuid FROM metahosts WHERE PROXY=1";
	$metagroups[null]="{none}";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$metagroups[$ligne["uuid"]]=$ligne["hostname"];
	
	}
	return $metagroups;
	
}

function meta_groups(){
	
	$q=new mysql_meta();
	$metagroups[0]="{none}";
	$sql="SELECT * FROM metagroups ORDER BY groupname";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$metagroups[$ligne["ID"]]=$ligne["groupname"];
		
	}
	return $metagroups;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=intval($_GET["ID"]);
	$t=time();
	$please_choose_a_bandwith_rule=$tpl->javascript_parse_text("{please_choose_a_bandwith_rule}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM meta_webfilters_acls WHERE ID='$ID'"));
	$metagroup=$ligne["metagroup"];
	$metauuid=$ligne["metauuid"];
	$enabled=$ligne["enabled"];
	
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	
	
	
	$metagroups=meta_groups();
	$metauuid=meta_servers();
	$btname="{apply}";
	if($ID==0){$btname="{add}";}

	$aclname=utf8_encode($ligne["aclname"]);
	$acltpl=$ligne["acltpl"];
	$aclgpid=$ligne["aclgpid"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='url_rewrite_access_deny'"));
	$url_rewrite_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($url_rewrite_access_deny)){$url_rewrite_access_deny=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='url_rewrite_access_allow'"));
	$url_rewrite_access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($url_rewrite_access_allow)){$url_rewrite_access_allow=0;}
	
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='access_deny'"));
	$access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($access_deny)){$access_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='adaptation_access_deny'"));
	$adaptation_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($adaptation_access_deny)){$adaptation_access_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='cache_deny'"));
	$cache_deny=$ligne["httpaccess_value"];
	if(!is_numeric($cache_deny)){$cache_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='access_allow'"));
	$access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($access_allow)){$access_allow=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='http_reply_access_deny'"));
	$http_reply_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($http_reply_access_deny)){$http_reply_access_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='http_reply_access_allow'"));
	$http_reply_access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($http_reply_access_allow)){$http_reply_access_allow=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='cache_parent'"));
	$cache_parent=$ligne["httpaccess_value"];
	if(!is_numeric($cache_parent)){$cache_parent=0;}


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='snmp_access_allow'"));
	$snmp_access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($snmp_access_allow)){$snmp_access_allow=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='log_access'"));
	$log_access=$ligne["httpaccess_value"];
	if(!is_numeric($log_access)){$log_access=0;}


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='deny_access_except'"));
	$deny_access_except=$ligne["httpaccess_value"];
	if(!is_numeric($deny_access_except)){$deny_access_except=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='tcp_outgoing_tos'"));
	$tcp_outgoing_tos=$ligne["httpaccess_value"];
	$tcp_outgoing_tos_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_tos)){$tcp_outgoing_tos=0;}
	if($tcp_outgoing_tos_value==null){$tcp_outgoing_tos_value="0x20";}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='reply_body_max_size'"));
	$reply_body_max_size=intval($ligne["httpaccess_value"]);
	$reply_body_max_size_value=intval($ligne["httpaccess_data"]);



	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='delay_access'"));
	$delay_access=$ligne["httpaccess_value"];
	$delay_access_id=$ligne["httpaccess_data"];
	if(!is_numeric($delay_access)){$delay_access=0;}
	if(!is_numeric($delay_access_id)){$delay_access_id=0;}




	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='tcp_outgoing_address'"));
	$tcp_outgoing_address=$ligne["httpaccess_value"];
	$tcp_outgoing_address_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_address)){$tcp_outgoing_address=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='deny_quota_rule'"));
	$deny_quota_rule=$ligne["httpaccess_value"];
	$deny_quota_rule_id=$ligne["httpaccess_data"];
	if(!is_numeric($deny_quota_rule)){$deny_quota_rule=0;}
	if($deny_quota_rule_id>0){
		$q3=new mysql();
		$ligne3=mysql_fetch_array($q3->QUERY_SQL("SELECT QuotaName FROM ext_time_quota_acl WHERE ID=$deny_quota_rule_id","artica_backup"));
		$deny_quota_rule_value=$ligne3["QuotaName"];
	}


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='deny_log'"));
	$deny_log=$ligne["httpaccess_value"];
	if(!is_numeric($deny_log)){$deny_log=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM meta_webfilters_acls WHERE ID='$ID' AND httpaccess='request_header_add'"));
	$request_header_add=$ligne["httpaccess_value"];
	$request_header_add_value=unserialize(base64_decode($ligne["httpaccess_data"]));
	if(!is_numeric($request_header_add)){$request_header_add=0;}else{
		$request_header_add_name=$request_header_add_value["header_name"];
		$request_header_add_value=$request_header_add_value["header_value"];
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

	if($delay_access_id>0){
		$q2=new mysql();
		$sql="SELECT rulename FROM squid_pools WHERE ID='$delay_access_id'";
		$ligne=mysql_fetch_array($q2->QUERY_SQL($sql,"artica_backup"));
		$delay_access_id_text=$tpl->javascript_parse_text(utf8_encode($ligne["rulename"]));
	}

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
		<td class=legend style='font-size:18px'>{proxys_group}:</td>
		<td>". Field_array_Hash($metagroups,"metagroup-$t",$metagroup,null,null,0,"font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{or} {server}:</td>
		<td>". Field_array_Hash($metauuid,"metauuid-$t",$metauuid,null,null,0,"font-size:18px")."</td>
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
		<td class=legend style='font-size:18px'>{force_using_thewebfilter_engine}:</td>
		<td>". Field_checkbox_design("url_rewrite_access_allow",1,$url_rewrite_access_allow,"url_rewrite_access_allow_check()")."</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:18px'>{pass_trough_thewebfilter_engine}:</td>
		<td>". Field_checkbox_design("url_rewrite_access_deny",1,$url_rewrite_access_deny,"url_rewrite_access_deny_check()")."</td>
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
			<td>". Field_text("tcp_outgoing_tos_value",$tcp_outgoing_tos_value,'font-size:18px;width:90px')."</td>
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
		<td>". field_ipv4("tcp_outgoing_address_value",$tcp_outgoing_address_value,null,null,0,"font-size:18px")."</td>
	</tr>
	</table>



	<table style='width:100%'>
	<tr>
		<td colspan=2 align='right'><hr>". button("$btname", "SaveAclRule$ID()",22)."</td>
				</tr>
				</table>
				</div>

				<script>

var x_SaveAclRule$ID= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#META_PROXY_ACLS_MAIN').flexReload();
	var ID=$ID;
	if(ID==0){YahooWin2Hide();}
}

function  SaveAclRule{$ID}Check(e){
	if(!checkEnter(e)){return;}
	SaveAclRule$ID();
}

function SaveAclRule$ID(){
	var XHR = new XHRConnection();
	var rulename=document.getElementById('aclrulename').value;
	if(rulename.length==0){return;}
	XHR.appendData('aclrulename', encodeURIComponent(document.getElementById('aclrulename').value));
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
XHR.appendData('metagroup', document.getElementById('metagroup-$t').value);
XHR.appendData('metauuid', document.getElementById('metauuid-$t').value);

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
deny_quota_rule_check(true);
http_reply_access_deny_check(true);
http_reply_access_allow_check(true);
CheckBoxDesignRebuild();
</script>



";

echo $tpl->_ENGINE_parse_body($html);

}

function save(){
	$q=new mysql_squid_builder();
	$ID=$_POST["ID"];
	$aclname=mysql_escape_string2(url_decode_special_tool($_POST["aclrulename"]));
	
	$f["access_allow"]=true;
	$f["access_deny"]=true;
	$f["adaptation_access_deny"]=true;
	$f["cache_deny"]=true;
	$f["cache_parent"]=true;
	$f["delay_access"]=true;
	
	$f["deny_access_except"]=true;
	$f["deny_log"]=true;
	$f["deny_quota_rule"]=true;
	$f["http_reply_access_allow"]=true;
	$f["http_reply_access_deny"]=true;
	$f["log_access"]=true;
	$f["reply_body_max_size"]=true;
	$f["request_header_add"]=true;
	$f["snmp_access_allow"]=true;
	$f["tcp_outgoing_address"]=true;
	$f["tcp_outgoing_tos"]=true;
	$f["url_rewrite_access_deny"]=true;
	$f["url_rewrite_access_allow"]=true;
	
	while (list ($token, $explain) = each ($f) ){
		if($_POST[$token]==1){
			$httpaccess=$token;
			$httpaccess_value=1;
		}
		
	}
	
	
	if($httpaccess=="reply_body_max_size"){
		$httpaccess_data=$_POST["reply_body_max_size_value"];
	}
	if($httpaccess=="delay_access"){
		$httpaccess_data=$_POST["delay_access_id"];
	}	
	if($httpaccess=="deny_quota_rule"){
		$httpaccess_data=$_POST["deny_quota_rule_id"];
	}	
	if($httpaccess=="request_header_add"){
		$request_header_add_value["header_name"]=$_POST["request_header_add_name"];
		$request_header_add_value["header_value"]=$_POST["request_header_add_value"];
		$httpaccess_data=serialize($request_header_add_value);
	}	
	if($httpaccess=="tcp_outgoing_address"){
		$httpaccess_data=$_POST["tcp_outgoing_address_value"];
	}	
	if($httpaccess=="tcp_outgoing_tos"){
		$httpaccess_data=$_POST["tcp_outgoing_tos_valuex20"];
	}	
	
	$metagroup=$_POST["metagroup"];
	$metauuid=$_POST["metauuid"];
	$acltpl=$_POST["acltpl"];
	$enabled=$_POST["enabled"];
	$httpaccess_data=mysql_escape_string2($httpaccess_data);
	$xORDER=$_POST["xORDER"];
	
	if($ID==0){
		$sql="INSERT INTO meta_webfilters_acls (aclname,enabled,metagroup,metauuid,httpaccess,httpaccess_value,httpaccess_data,xORDER)
		VALUES ('$aclname','1','$metagroup','$metauuid','$httpaccess','$httpaccess_value','$httpaccess_data',1)";
	}else{
		$sql="UPDATE meta_webfilters_acls 
		SET aclname='$aclname',
		metagroup='$metagroup',
		metauuid='$metauuid',
		httpaccess='$httpaccess',
		httpaccess_value='$httpaccess_value',
		httpaccess_data='$httpaccess_data'
		WHERE ID='$ID'";
		
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
	
}
