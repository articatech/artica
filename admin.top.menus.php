<?php
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="update-white-32-tr"){update_white_32_tr();exit;}
if(isset($_GET["update-white-32-tr"])){update_white_32_tr();exit;}
if(isset($_GET["account-identity"])){account_identity();exit;}
$sock=new sockets();
$ActAsSMTPGatewayStatistics=$sock->GET_INFO("ActAsSMTPGatewayStatistics");
if(!is_numeric($ActAsSMTPGatewayStatistics)){$ActAsSMTPGatewayStatistics=0;}
$page=CurrentPageName();
$users=new usersMenus();
$postfixadded=false;
$AsSquid=false;
$DisableMessaging=intval($sock->GET_INFO("DisableMessaging"));
if($DisableMessaging==1){$users->POSTFIX_INSTALLED=false;$ActAsSMTPGatewayStatistics=0;}
$sock=new sockets();

if($_SESSION["uid"]<>null){
	
		$RESPONSE=trim($sock->getFrameWork("squid.php?idsSQUIDAppliance=yes"));
		if($RESPONSE=="TRUE"){
			$tr[]=BuildIcons("dashboard-white-32.png","dashboard-white-32.png","{dashboard}","GoToIndex()");
		}
		else{
			$tr[]=BuildIcons("dashboard-white-32.png","dashboard-white-32.png","{dashboard}","ConfigureYourserver()");
		}
	
}

$AllowSquid=false;
if($users->AsSquidAdministrator){$AllowSquid=true;$users->AsArticaMetaAdmin=true;}
if($users->AsDansGuardianAdministrator){$AllowSquid=true;}
if($users->AsProxyMonitor){$AllowSquid=true;}
$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
$EnableNginxMail=intval($sock->GET_INFO("EnableNginxMail"));
$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
$users=new usersMenus();
if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
if($AsCategoriesAppliance==1){$SQUIDEnable=0;}
$AsMetaServer=intval($sock->GET_INFO("AsMetaServer"));
if($AsMetaServer==1){$sock->SET_INFO("EnableArticaMetaServer",1);}
$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
$EnableHaProxy=intval($sock->GET_INFO("EnableHaProxy"));
$FreeWebLeftMenu=intval($sock->GET_INFO("FreeWebLeftMenu"));
$WordPressTopMenu=intval($sock->GET_INFO("WordPressTopMenu"));
$ProFTPDLeftMenu=intval($sock->GET_INFO("ProFTPDLeftMenu"));
$QosTopMenu=intval($sock->GET_INFO("QosTopMenu"));
$WordPressInstalled=intval($sock->GET_INFO("WordPressInstalled"));
if($users->WORDPRESS_APPLIANCE){$WordPressInstalled=1;}
$MimeDefangEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangEnabled"));
$MESSAGING_ADMIN=false;
$AS_STATS=false;
$tr[]="<!-- L.".__LINE__." -->";
$action=false;
if($users->SQUID_INSTALLED){
	$AsSquid=true;
	$AS_STATS=true;
}else{
	if($EnableIntelCeleron==1){$AS_STATS=false;}
	$EnableIntelCeleron=0;
}




if($EnableIntelCeleron==1){
	$FreeWebLeftMenu=0;
	$AS_STATS=false;
}

if($users->STATS_APPLIANCE){
	$AS_STATS=true;
	$users->SQUID_INSTALLED=false;
	$AsCategoriesAppliance=1;
}




$tr[]="<!-- L.".__LINE__." -->";

if($users->AsWebMaster){
	
	if($FreeWebLeftMenu==1){
		$tr[]=BuildIcons("free-web-white-32.png","free-web-white-32.png","{webservers}","GotToFreeWeb();");
	}
	
	$tr[]="<!-- AsWebMaster OK  WordPressInstalled=$WordPressInstalled-->";
	if($WordPressInstalled==1){
		if($WordPressTopMenu==1){
			$tr[]=BuildIcons("wp-32.png","wp-32.png","Wordpress","GotoWordPress()");
		}
	}
	
	if($ProFTPDLeftMenu==1){
		$tr[]=BuildIcons("proxy-white-32.png","proxy-white-32.png","{APP_PROFTPD}","GotoVSFTPD();");
	}
}





if(!$users->AsWebStatisticsAdministrator){$AS_STATS=false;}
$NGINX_ADDED=false;
$EVENTS_ADDED=false;

//-------------------------------------------------- SQUID ----------------------------------------

if($SQUIDEnable==1){
	if($users->SQUID_INSTALLED){
		$AsSquid=true;
		
		if(AsSquidPersonalCategoriesOnly()){
			$tr[]=BuildIcons("proxy-white-32.png","proxy-white-32.png","{your_categories}","GotoYourcategories();");
			
		}
		
		
		if($AllowSquid){
			$tr[]=BuildIcons("proxy-white-32.png","proxy-white-32.png","{your_proxy}","LoadMainDashProxy();");
			
			if($EnableNginx==1){
				if($users->NGINX_INSTALLED){
					if($users->AsWebMaster){
						$tr[]=BuildIcons("proxy-white-32.png","proxy-white-32.png","Reverse Proxy","GotoReverseProxy();");
						$NGINX_ADDED=true;
					}
			
				}
			}
			
			$tr[]=BuildIcons("action-white-32.png","action-white-32.png","{action}","MessagesTopshowMessageDisplay('quicklinks_proxy_action');");
			
			$action=true;
		}
		
		if($users->AsWebStatisticsAdministrator){
			$tr[]=BuildIcons("eye-32-w.png","eye-32-w.png","{events}","GotoMainLogs()");
			$EVENTS_ADDED=true;
			
		}
	}
}


if(!$NGINX_ADDED){
	if($EnableNginx==1){
		if($users->NGINX_INSTALLED){
			if($users->AsWebMaster){
				$tr[]=BuildIcons("proxy-white-32.png","proxy-white-32.png","Reverse Proxy","GotoReverseProxy();");
				$NGINX_ADDED=true;
				if(!$EVENTS_ADDED){
					$tr[]=BuildIcons("eye-32-w.png","eye-32-w.png","{events}","GotoMainLogs()");
				}
			}
				
		}
	}
}


if($AS_STATS){
	$tr[]=BuildIcons("statistics-32-white.png","statistics-32-white.png","{statistics}","MessagesTopshowMessageDisplay('quicklinks_statistics_options');");
	if(!$EVENTS_ADDED){
		if($users->AsWebStatisticsAdministrator){
			$tr[]=BuildIcons("eye-32-w.png","eye-32-w.png","{events}","GotoMainLogs()");
		}
	}
	
}


if($SQUIDEnable==0){
		if($AsCategoriesAppliance==1){
			$tr[]=BuildIcons("proxy-white-32.png","proxy-white-32.png","{APP_UFDBCAT}","AnimateDiv('BodyContent');
					LoadAjax('BodyContent','ufdbcat.php');");
			$action=true;
			$tr[]=BuildIcons("action-white-32.png","action-white-32.png","{action}","MessagesTopshowMessageDisplay('quicklinks_proxy_action');");
			
		}
	}
	



if($users->POSTFIX_INSTALLED){
	$postfixadded=true;
	if($users->AsPostfixAdministrator){
		$MESSAGING_ADMIN=true;
		$tr[]=BuildIcons("messaging-service-32.png","messaging-server-white-32.png","{messaging}","GoToMessaging();");
		$tr[]=BuildIcons("eye-32-w.png","eye-32-w.png","{events}","GotoPostfixMainLogs()");
		$tr[]=BuildIcons("statistics-32-white.png","statistics-32-white.png","{statistics}","MessagesTopshowMessageDisplay('quicklinks_statistics_options');");
	}
	
	if(!$MESSAGING_ADMIN){
		if(VerifyRights_smtp_org()){
			$tr[]=BuildIcons("messaging-service-32.png","messaging-server-white-32.png","{messaging}","GoToMessagingOU('{$_GET["ou"]}');");
			if($MimeDefangEnabled==1){
				$tr[]=BuildIcons("eye-32-w.png","eye-32-w.png","{events}","GotoSMTPTransactions()");
			}
			$tr[]=BuildIcons("statistics-32-white.png","statistics-32-white.png","{statistics}","MessagesTopshowMessageDisplay('quicklinks_statistics_options');");
		}
	}
}



if(!$users->AsArticaAdministrator){
	if($_SESSION["uid"]<>null){
		$menus["{account}"]="javascript:Loadjs(\"users.account.php?js=yes\")";
	}
}
if(!$action){
	if($users->POSTFIX_INSTALLED){
		if($users->AsPostfixAdministrator){
			$tr[]=BuildIcons("action-white-32.png","action-white-32.png","{action}","MessagesTopshowMessageDisplay('quicklinks_proxy_action');");
			
		}
	}
}
				
				
if($users->AsSystemAdministrator){
	$tr[]=BuildIcons("top-48-mycomp-tr.png","top-48-mycomp.png","{system}",
			"GoToSystem()");
	//$tr[]=BuildIcons("32-settings-white-tr.png","32-settings-white.png","{advanced_options}","Loadjs('admin.left.php?old-menu=yes')");
	//$tr[]=BuildIcons("32-cd-scan-white-tr.png","32-cd-scan-white.png","{install_upgrade_new_softwares}","Loadjs('setup.index.php?js=yes')");
	//$tr[]=BuildIcons("services-32-white-tr.png","services-32-white.png","{display_running_services}","Loadjs('admin.index.services.status.php?js=yes')");
}

if($users->AsArticaMetaAdmin){
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){
		$ProductName="Artica";
		$ProductNamef=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/ProducName.conf";
		if(is_file($ProductNamef)){$ProductName=trim(@file_get_contents($ProductNamef));}
		$tr[]=BuildIcons("management-console-32.png","management-console-32.png","$ProductName Meta","GoToMeta();");
	}
}

$FIREWALL_INSTALLED=intval($sock->getFrameWork("firehol.php?is-installed=yes"));
if($users->AsSystemAdministrator){
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	if($EnableArticaHotSpot==0){
		if($FIREWALL_INSTALLED==1){
			$FireHolConfigured=intval($sock->GET_INFO("FireHolConfigured"));
			if($FireHolConfigured==1){
				$tr[]=BuildIcons("firewall-32-white.png","firewall-32-white.png","FireWall","GotoFirewall()");
			}
		}
	}
}

if($QosTopMenu==1){
	if($users->AsSystemAdministrator){
		if($FIREWALL_INSTALLED==1){
			$tr[]=BuildIcons("firewall-32-white.png","firewall-32-white.png","{Q.O.S}","GotoQOS()");
			
		}
	}
	
}
$tr[]="<!-- L.".__LINE__." -->";				

if($users->AsSquidAdministrator){
	if($users->HAPROXY_INSTALLED){
		if($EnableHaProxy==1){
			$tr[]=BuildIcons("load-balance-white-32.png","load-balance-white-32.png","{load_balancing}","GotToHAPROXY()");
		}
	}
}


if(!$AsSquid){
	if($users->AsSambaAdministrator){
		if($users->SAMBA_INSTALLED){
			$tr[]=BuildIcons("filesharing-32-white.png","filesharing-32-white.png","{file_sharing_services}","LoadAjax('BodyContent','quicklinks.fileshare.php');");
		}
		
	}
}
	




$tr[]="<!-- L.".__LINE__." -->";
if($_SESSION["uid"]<>null){
	if($users->AsAnAdministratorGeneric){
		$ldap=new clladp();
		if($ldap->IsKerbAuth()){
			$tr[]=BuildIcons("windows-white-32.png","windows-white-32.png","AD {members}","GotoMembersSearch()");
		}else{
			if($EnableIntelCeleron==0){
				$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
				if($EnableOpenLDAP==1){
					$tr[]=BuildIcons("users-white-32.png","users-white-32.png","{local_members}","GotoMembersSearch()");
				}
			}
		}
		
		$tr[]=BuildIcons("members-settings-32-white.png","members-settings-32-white.png","{members_settings}","MessagesTopshowMessageDisplay('quicklinks_members');");
	}
}

//32-settings-white.png
//close-white-32.png
				
				
$fleche_js="MessagesTopshowMessageDisplay('quicklinks_main_menu');";
if($_SESSION["uid"]==null){
	$fleche_js="Loadjs('public.logon.php');";
}

if(!$users->AsAnAdministratorGeneric){
	$fleche_js="Loadjs('public.logon.php');";
	
}

$logo="/css/images/logo.gif";

if(is_file(dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/img/logo.png")){
	$logo="ressources/templates/{$_COOKIE["artica-template"]}/img/logo.png";
}else{
	$html[]="<!-- ".dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/img/logo.png no such file -->";
}
				
$html[]="

<table style='width:100%;'>
		<tr>
		<td style='margin:0;padding:0;vertical-align:middle;' width=1% nowrap>
			<img src='$logo' style='margin:0px;padding:0px;cursor:pointer'>
		</td>
		
		<td valign='middle' 
		style='border-left:1px solid white;padding-left:15px;padding-right:15px;margin:0;vertical-align:middle'
		onmouseout=\"javascript:this.className='TopObjectsOut';\" 
		onmouseover=\"javascript:this.className='TopObjectsOver';\"
		OnClick=\"javascript:$fleche_js\"	>
		<img src='img/mini-arrow-down.png'>
		</td>";

$html[]= "<td width=99% nowrap align='left'>
<table style='width:5%'><tr>";
while (list ($num, $line) = each ($tr)){
	$html[]= $line."\n";
}
$html[]= "</tr></table>

<td align='center' style='margin:0;padding:0;vertical-align:middle' width=1% nowrap>
	<span id='account-identity'>
	</span>
</td>
</tr>
</tbody>
</table>
		
</center>


<script>
	LoadAjaxTiny('account-identity','$page?account-identity=yes');
	initMessagesTop();
	//
</script>

";
$datas=@implode("\n", $html);
echo $datas;


function AsSquidPersonalCategoriesOnly(){
	if($_SESSION["uid"]==-100){return false;}
	$users=new usersMenus();
	if($users->AsAnAdministratorGeneric){return false;}
	if($users->AsWebStatisticsAdministrator){return false;}
	if($users->AsSquidAdministrator){return false;}
	if($users->AsDansGuardianAdministrator){return false;}
	if($users->AsSystemAdministrator){return false;}
	if($users->AsProxyMonitor){return false;}
	if($users->AsSquidPersonalCategories){return true;}
}


function update_white_32_tr(){
	
	$tpl=new templates();
	$sock=new sockets();
	if(!$GLOBALS["AS_ROOT"]){
		
		if($_SESSION["uid"]<>-100){if(is_numeric($_SESSION["uid"])){return null;}}
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.notify.html")){
			$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.notify.html");
			if(strlen($data)>45){
				echo $tpl->_ENGINE_parse_body($data);
				return;	
				}
			}
		}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$packagesNumber=$q->COUNT_ROWS("syspackages_updt", "artica_backup");	
	if($packagesNumber>0){
		$f=BuildIcons("update-white-tr-w32.png","update-white-32.png","$packagesNumber {system_packages_can_be_upgraded}","Loadjs('artica.update.php?js=yes')");
	}else{
		$f=BuildIcons("update-white-32-tr.png","update-white-32.png","{update}","Loadjs('artica.update.php?js=yes')");
	}
	
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?is-dpkg-running=yes")));
	if(count($datas)>0){
		$f=BuildIcons("ajax-top-menu-loader.gif","ajax-top-menu-loader.gif","{update} {running}","Loadjs('artica.update.php?js=yes')");
	}
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?ARTICA-MAKE=yes")));
	if(count($datas)>0){
		
		
		while (list ($num, $line) = each ($datas)){
			$t[]="<b>{{$num}}</b> {since} $line<br>";
		}
		$f=BuildIcons("ajax-top-menu-loader.gif","ajax-top-menu-loader.gif","{install} {running}<br>".@implode($t, ""),"Loadjs('artica.update.php?js=yes')");
	}

	$sock=new sockets();
	$notifyScript=false;
	$scheduledAR=unserialize(base64_decode($sock->getFrameWork("squid.php?schedule-import-exec=yes")));
	if($scheduledAR["RUNNING"]){
		$db_import=$tpl->_ENGINE_parse_body("<i style='font-size:16px;color:#BA0000'>{update_currently_running_since} {$scheduledAR["TIME"]}Mn</i>");	
		$f=$f."<script>MessagesTopshowMessage(\"$db_import\")</script>";
		$notifyScript=true;
	}
	
	if(!$notifyScript){
		$color="black";
		$notify=unserialize(base64_decode($sock->GET_INFO("TOP_NOTIFY")));
		if(!is_array($notify)){$notify=array();}
		if(count($notify)>0){
			@krsort($notify);
			while (list ($index, $array) = each ($notify) ){
			if(is_numeric($array["TIME"])){
				$took=distanceOfTimeInWords($array["TIME"],time());
				$array["CONTENT"]=$tpl->_ENGINE_parse_body($array["CONTENT"]);	
				if($array["PRIO"]=="info"){$color="white";}
				$f=$f."<script>MessagesTopshowMessage(\"<span style=font-size:18px;color:$color>". $tpl->_ENGINE_parse_body("{since}:$took, {$array["CONTENT"]}")."</span>\",'{$array["PRIO"]}' )</script>";
				$notifyScript=true;
				unset($notify[$index]);
				break;
			}
			unset($notify[$index]);
			continue;
		}
		$newArray=base64_encode(serialize($notify));	
		$sock->SaveConfigFile($newArray, "TOP_NOTIFY");
		}
	}
	
	echo $tpl->_ENGINE_parse_body($f);
	
}




function BuildIcons($imageoff,$imageon,$help,$js){
	
	$id=md5("$help$js");
	$tpl=new templates();
	$help=$tpl->_ENGINE_parse_body($help);
	return "<td align='center' style='margin:0;padding-right:10px;padding-left:10px;border-left:1px solid white;vertical-align:middle' width=1% nowrap
	onmouseout=\"javascript:this.className='TopObjectsOut';\" 
	onmouseover=\"javascript:this.className='TopObjectsOver';\" id=\"$id\" 
	OnClick=\"javascript:$js\"
	>
	
	<table style='width:100%'>
	<tr>
		<td align='center' style='margin:0;padding:0;vertical-align:middle;padding-right:10px' width=1% nowrap><img src='img/$imageon'></td>
		<td align='center' style='margin:0;padding:0;vertical-align:middle' width=1% nowrap>
			<span style='color:white;font-size:14px'>$help</span>
		</td>
	</tr>
	</table>
	
	</td>";
	
			$tpl=new templates();	
			$help=str_replace("[br][br]","[br]",$help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);
			$help=str_replace('"',"`",$help);		
			$help=$tpl->_ENGINE_parse_body($help,$additional_langfile);
			$help=htmlentities($help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);	
			
	$md5=md5($imageoff);
	
	$bullon="AffBulle('$help');this.style.cursor='pointer';";
	$bulloff="HideBulle();this.style.cursor='default';";
	$html="<div 
	OnMouseOver=\"javascript:document.getElementById('$md5').src='img/$imageon';$bullon\"
	OnMouseOut=\"javscript:document.getElementById('$md5').src='img/$imageoff';$bulloff\"
	OnClick=\"javascript:$js\"
	style='width:45px'
	><center><img src='img/$imageoff' id='$md5'></center>";
	return $html;
}

function account_identity(){
	$ldap=new clladp();
	$uid=$_SESSION["uid"];
	if($_SESSION["uid"]==-100){
		$uid=$ldap->ldap_admin;
		if($uid==null){$uid="Manager";}
	}
	
	if($uid==null){
		if(isset($_SESSION["RADIUS_ID"])){
			if($_SESSION["RADIUS_ID"]>0){
				$uid=$_SESSION["uid"];
			}
		}
		
	}
	
	if($uid==null){
		$tpl=new templates();
		$logon=$tpl->_ENGINE_parse_body("{logon}");
		$html="<table style='width:100%;padding:0;border:0;margin:0'>
		<tr>
		<td nowrap style='font-size:14px;color:#FFFFFF;text-transform:capitalize'
		onmouseout=\"javascript:this.className='TopObjectsOut';\"
		onmouseover=\"javascript:this.className='TopObjectsOver';\"
		OnClick=\"javascript:Loadjs('public.logon.php');\"
		>$logon</td>
		<td width=1% nowrap
		onmouseout=\"javascript:this.className='TopObjectsOut';\"
		onmouseover=\"javascript:this.className='TopObjectsOver';\"
		OnClick=\"javascript:Loadjs('public.logon.php');\"
		
		><img src='img/unknown-user-32.png'></td>
		</tr>
		</table>
			
		";
		echo $html;
		return;
	}
	
	
	$html="<table style='width:100%;padding:0;border:0;margin:0'>
	<tr>
		<td nowrap style='font-size:14px;color:#FFFFFF;text-transform:capitalize'
		onmouseout=\"javascript:this.className='TopObjectsOut';\" 
		onmouseover=\"javascript:this.className='TopObjectsOver';\"
		OnClick=\"javascript:MessagesTopshowMessageDisplay('quicklinks_account');\"
		>$uid</td>
		<td width=1% nowrap
		onmouseout=\"javascript:this.className='TopObjectsOut';\" 
		onmouseover=\"javascript:this.className='TopObjectsOver';\"
		OnClick=\"javascript:MessagesTopshowMessageDisplay('quicklinks_account');\"
		
		><img src='img/unknown-user-32.png'></td>
	</tr>
	</table>		
			
	";
	
	
	echo $html;
	
	
}

function VerifyRights_smtp_org(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsOrgPostfixAdministrator){$_GET["ou"]=$_SESSION["ou"];return true;}
	if($usersmenus->AsMessagingOrg){$_GET["ou"]=$_SESSION["ou"];return true;}
	if(!$usersmenus->AllowChangeDomains){$_GET["ou"]=$_SESSION["ou"];return true;}
}
