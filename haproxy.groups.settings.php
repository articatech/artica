<?php
if(posix_getuid()==0){die();}
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.haproxy.inc');




$user=new usersMenus();
if($user->AsDansGuardianAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_POST["groupsave"])){group_save();exit;}
if(isset($_GET["ActionItem"])){ActionItem();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=time();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_backends_groups WHERE ID='$ID'","artica_backup"));
	$mode=array(0=>"TCP",1=>"HTTP Web",2=>"HTTP Proxy");
	$servicename=$ligne["servicename"];
	$hap=new haproxy_multi($servicename);
	
	$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
	if(!isset($MainConfig["loadbalancetype"])){
		$MainConfig["loadbalancetype"]=$hap->loadbalancetype;
	}
	if(!isset($MainConfig["tunnel_mode"])){
		$MainConfig["tunnel_mode"]=$hap->tunnel_mode;
	}	
	if(!isset($MainConfig["dispatch_mode"])){
		$MainConfig["dispatch_mode"]=$hap->dispatch_mode;
	}	
	if(!isset($MainConfig["UseCookies"])){
		$MainConfig["UseCookies"]=$hap->MainConfig["UseCookies"];
	}	
	
	if(!isset($MainConfig["contimeout"])){
		$MainConfig["contimeout"]=$hap->MainConfig["contimeout"];
	}
	if(!isset($MainConfig["srvtimeout"])){
		$MainConfig["srvtimeout"]=$hap->MainConfig["srvtimeout"];
	}
	if(!isset($MainConfig["clitimeout"])){
		$MainConfig["clitimeout"]=$hap->MainConfig["clitimeout"];
	}		
	if(!isset($MainConfig["maxretries"])){
		$MainConfig["maxretries"]=$hap->MainConfig["retries"];
	}	
	if(!isset($MainConfig["NTLM_COMPATIBILITY"])){
		$MainConfig["NTLM_COMPATIBILITY"]=$hap->MainConfig["NTLM_COMPATIBILITY"];
	}	
	
	if(!is_numeric($MainConfig["maxretries"])){$MainConfig["maxretries"]=3;}
	
	
	$html="<div style='width:100%;font-size:30px;margin-bottom:20px'>{$ligne["groupname"]}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:20px' nowrap>{default}:</td>
			<td width=99%>". Field_checkbox_design("default-$t",1,$ligne["default"])."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:20px' nowrap>{groupname}:</td>
			<td width=99%>". Field_text("groupname-$t",$ligne["groupname"],"font-size:20px;padding:3px")."&nbsp;<span id='mode-options-$t'></span></td>
		</tr>
		
		
	
		<tr>
			<td class=legend style='font-size:20px' nowrap>{method}:</td>
			<td width=99%>". Field_array_Hash($mode,"loadbalancetype-$t",$MainConfig["loadbalancetype"],"blur()",null,0,"font-size:20px;padding:3px")."&nbsp;<span id='mode-options-$t'></span></td>
		</tr>
		<tr>
			<td class=legend style='font-size:20px' nowrap>{tunnel_mode}:</td>
			<td width=99%>". Field_checkbox_design("tunnel_mode-$t",1,$MainConfig["tunnel_mode"],"blur()")."&nbsp;<span id='mode-tunnel_mode-$t'></span></td>
				
		</tr>				
		<tr>
			<td class=legend style='font-size:20px' nowrap>{dispatch_method}:</td>
			<td width=99%>". Field_array_Hash($hap->algo,"dispatch_mode-$t",$MainConfig["dispatch_mode"],"style:font-size:20px;padding:3px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:20px' nowrap>{UseCookies}:</td>
			<td width=99%>". Field_checkbox_design("UseCookies-$t",1,$MainConfig["UseCookies"])."</td>
		</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>". texttooltip("{NTLM_COMPATIBLE}","{HAP_NTLM_COMPATIBLE}").":</td>
				<td width=99%>". Field_checkbox_design("NTLM_COMPATIBILITY-$t",1,$MainConfig["NTLM_COMPATIBILITY"],"blur()")."</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:20px' nowrap>{contimeout}:</td>
				<td style='font-size:20px;'>".
				 Field_text("contimeout-$t",$MainConfig["contimeout"],"font-size:20px;padding:3px;width:100px",null,
				 "contimeoutcalc$t()",null,false,"contimeout$t()",false).
				 
				 "&nbsp;{milliseconds}&nbsp;<span id='contimeout-span-$t'></span></td>
				 
			</tr>	
			<tr>
				<td class=legend style='font-size:20px' nowrap>{srvtimeout}:</td>
				<td style='font-size:20px;'>".
				 Field_text("srvtimeout-$t",$MainConfig["srvtimeout"],"font-size:20px;padding:3px;width:100px",null,
				 "contimeoutcalc$t()",null,false,"contimeout$t()",false).
				 "&nbsp;{milliseconds}&nbsp;<span id='srvtimeout-span-$t'></span></td>
				 
			</tr>	
			<tr>
				<td class=legend style='font-size:20px' nowrap>{clitimeout}:</td>
				<td style='font-size:20px;'>".
				 Field_text("clitimeout-$t",$MainConfig["clitimeout"],"font-size:20px;padding:3px;width:100px",null,
				 "contimeoutcalc$t()",null,false,"contimeout$t()",false).
				 "&nbsp;{milliseconds}&nbsp;<span id='clitimeout-span-$t'></span></td>
				 
			</tr>				
			<tr>
				<td class=legend style='font-size:20px' nowrap>{maxretries}:</td>
				<td style='font-size:20px;'>".
				 Field_text("maxretries-$t",$MainConfig["maxretries"],"font-size:20px;padding:3px;width:60px",null,
				 "blur()",null,false,"blur()",false).
				 "&nbsp;{times}</td>
				 
			</tr>	
						<tr>			
				<td colspan=2 align='right'>". button("{apply}","Save$t()",30)."</td>
			</tr>
	
	</table>	
	</div>
<script>

var xSave$t=function (obj) {
	var servicename='$servicename';
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
	$('#HAPROXY_GROUPS_TABLE').flexReload();
	$('#HAPROXY_BROWSE_ACL_GROUPS_TOT').flexReload();
	RefreshTab('haproxy_group_$ID');
}	

function Save$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('groupname-$t').value);
	XHR.appendData('groupsave','$ID');
	XHR.appendData('groupname',pp);
    XHR.appendData('loadbalancetype',document.getElementById('loadbalancetype-$t').value);
    XHR.appendData('dispatch_mode',document.getElementById('dispatch_mode-$t').value);
    XHR.appendData('contimeout',document.getElementById('contimeout-$t').value);
    XHR.appendData('clitimeout',document.getElementById('clitimeout-$t').value);
    XHR.appendData('srvtimeout',document.getElementById('srvtimeout-$t').value);
    XHR.appendData('maxretries',document.getElementById('maxretries-$t').value);
    		
    
    if( document.getElementById('default-$t').checked){XHR.appendData('default',1);}else{XHR.appendData('default',0);}
    if( document.getElementById('UseCookies-$t').checked){XHR.appendData('UseCookies',1);}else{XHR.appendData('UseCookies',0);}
    if( document.getElementById('tunnel_mode-$t').checked){XHR.appendData('tunnel_mode',1);}else{XHR.appendData('tunnel_mode',0);}
    if( document.getElementById('NTLM_COMPATIBILITY-$t').checked){XHR.appendData('NTLM_COMPATIBILITY',1);}else{XHR.appendData('NTLM_COMPATIBILITY',0);}
    XHR.sendAndLoad('$page', 'POST',xSave$t);
		
}
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function group_save(){
	$ID=$_POST["groupsave"];
	$groupname=url_decode_special_tool($_POST["groupname"]);
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_backends_groups WHERE ID='$ID'","artica_backup"));
	$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
	$servicename=$ligne["servicename"];
	if($_POST["default"]==1){
		$q->QUERY_SQL("UPDATE haproxy_backends_groups SET `default`=0 WHERE servicename='$servicename'","artica_backup");
		
	}
	
	while (list ($num, $val) = each ($_POST) ){
		$MainConfig[$num]=$val;
	}
	$MainConfig_new=base64_encode(serialize($MainConfig));
	$sql="UPDATE haproxy_backends_groups SET groupname='$groupname',`default`='{$_POST["default"]}',`MainConfig`='$MainConfig_new' WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
	
}


