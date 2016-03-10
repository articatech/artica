<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.updateutility2.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.tasks.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$ERROR_NO_PRIVS');";return;
	}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_GET["products"])){products_tabs();exit;}
	if(isset($_GET["product-section"])){product_section();exit;}
	if(isset($_POST["ProductSubKey"])){product_section_save();exit;}
	if(isset($_POST["UpdateUtilityAllProducts"])){UpdateUtilitySave();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["UpdateUtilityStartTask"])){UpdateUtilityStartTask();exit;}
	if(isset($_GET["webevents"])){webevents_table();exit;}
	if(isset($_GET["web-events"])){webevents_list();exit;}
	if(isset($_GET["dbsize"])){dbsize();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["freewebs"])){frewebslist();exit;}
	if(isset($_GET["add-freeweb-js"])){add_freeweb_js();exit;}
	
tabs();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="YahooWin2('920','$page','UpdateUtility');";
	echo $html;
}

function add_freeweb_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$addfree=$tpl->javascript_parse_text("{add_freeweb_explain}");
	$t=$_GET["t"];
	$html="
			
	var x_AddNewFreeWeb$t= function (obj) {
	      var results=obj.responseText;
	      if(results.length>3){alert(results);}
	      RefreshTab('main_upateutility_config');
	}	

	function AddNewFreeWeb$t(){
			var servername=prompt('$addfree');
			if(!servername){return;}
			var XHR = new XHRConnection();
			XHR.appendData('ADD_DNS_ENTRY','');
			XHR.appendData('ForceInstanceZarafaID','');
			XHR.appendData('ForwardTo','');
			XHR.appendData('Forwarder','0');
			XHR.appendData('SAVE_FREEWEB_MAIN','yes');
			XHR.appendData('ServerIP','');
			XHR.appendData('UseDefaultPort','0');
			XHR.appendData('UseReverseProxy','0');
			XHR.appendData('gpid','');
			XHR.appendData('lvm_vg','');
			XHR.appendData('servername',servername);
			XHR.appendData('sslcertificate','');
			XHR.appendData('uid','');
			XHR.appendData('useSSL','0');
			XHR.appendData('force-groupware','UPDATEUTILITY');
			AnimateDiv('status-$t');
			XHR.sendAndLoad('freeweb.edit.main.php', 'POST',x_AddNewFreeWeb$t);	
		}	
	
	
	AddNewFreeWeb$t();
	
	";
	echo $html;

}


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('services.php?Update-Utility-status=yes'));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("APP_UPDATEUTILITYRUN",$ini,null).
	
	"
	<div id='dbsize' style='width:100%;margin-top:50px'></div>
	<script>
		LoadAjaxTiny('dbsize','$page?dbsize=yes&refresh=dbsize');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($status);

}


function products_tabs(){

	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	
	$update=new updateutilityv2();
	while (list ($num, $ArrayF) = each ($update->families) ){
		$array[$num]=$ArrayF["NAME"];
		
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		$tab[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?product-section=yes&product-key=$num\"><span style='font-size:13px'>$ligne</span></a></li>\n");
			
	}
echo build_artica_tabs($tab, "main_upateutility_pkey");

	
	
	
}


function tabs(){
	$tpl=new templates();
	
	if(!is_file("/etc/UpdateUtility/UpdateUtility-Console")){
		
		echo FATAL_ERROR_SHOW_128(
			"<span style='font-size:40px'>{updateutility_not_installed}</span>
			<center style='margin:50px'>".button("{install_now}","Loadjs('UpdateUtility.install.progress.php')",40)."</center>");		
				
		die();
		
	}
	
	
	$sock=new sockets();
	$UpdateUtilityWizard=intval($sock->GET_INFO("UpdateUtilityWizard"));
	
	if($UpdateUtilityWizard==0){
		echo $tpl->_ENGINE_parse_body("<center style='margin:50px'>".button("{configuration_wizard}", 
				"Loadjs('UpdateUtility.wizard.php')",40));
		return;
	}
	
	$fontsize=24;
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$array["settings"]="{parameters}";
	$array["webevents"]="{update_events}";

// Total downloaded: 100%, Result: Retranslation successful and update is not requested
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="webevents"){
			$tab[]="<li><a href=\"UpdateUtility.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}

	echo build_artica_tabs($tab, "main_upateutility_config");
		
	
}


function settings(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$redirect_updates_to_localservice=null;
	$UpdateUtilityEnableHTTP=$sock->GET_INFO("UpdateUtilityEnableHTTP");
	$UpdateUtilityHTTPPort=$sock->GET_INFO("UpdateUtilityHTTPPort");
	$UpdateUtilityHTTPIP=$sock->GET_INFO("UpdateUtilityHTTPIP");
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	$UpdateUtilityOnlyForKav4Proxy=$sock->GET_INFO("UpdateUtilityOnlyForKav4Proxy");
	$UpdateUtilityRedirectEnable=$sock->GET_INFO("UpdateUtilityRedirectEnable");
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	$UpdateUtilityUpdatesType=intval($sock->GET_INFO("UpdateUtilityUpdatesType"));
	$UpdateUtilitySchedule=intval($sock->GET_INFO("UpdateUtilitySchedule"));
	$UpdateUtilityWebServername=$sock->GET_INFO("UpdateUtilityWebServername");
	$q=new mysql_squid_builder();
	
	if($q->TABLE_EXISTS("dashboard_apache_sizes")){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(SIZE) as SIZE FROM dashboard_apache_sizes WHERE `SITENAME`='$UpdateUtilityWebServername'"));
		$DSIZE=FormatBytes($ligne["SIZE"]/1024);
		if(intval($ligne["SIZE"])>0){
			$js="Loadjs('$page?graph1=yes&t=$t')";
		}
		
	}
	
	
	$users=new usersMenus();
	$APP_UFDBGUARD_INSTALLED=0;
	if($users->APP_UFDBGUARD_INSTALLED){
		$APP_UFDBGUARD_INSTALLED=1;
	}
	
	if($users->KAV4PROXY_INSTALLED){
		if(!is_numeric($UpdateUtilityAllProducts)){
			$UpdateUtilityAllProducts=0;
		}
		if(!is_numeric($UpdateUtilityOnlyForKav4Proxy)){$UpdateUtilityOnlyForKav4Proxy=1;}
	}else{
		if(!is_numeric($UpdateUtilityOnlyForKav4Proxy)){$UpdateUtilityOnlyForKav4Proxy=0;}
	}
	
	if(!is_numeric($UpdateUtilityRedirectEnable)){$UpdateUtilityRedirectEnable=0;}
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}
	if(!is_numeric($UpdateUtilityOnlyForKav4Proxy)){$UpdateUtilityOnlyForKav4Proxy=1;}
	if(!is_numeric($UpdateUtilityHTTPPort)){$UpdateUtilityHTTPPort=9222;}
	
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	if($UpdateUtilityOnlyForKav4Proxy==1){$UpdateUtilityAllProducts=0;}
	
	$containerjs="Loadjs('UpdateUtility.container-wizard.php');";
	if($UpdateUtilityUseLoop==1){$containerjs="Loadjs('system.disks.loop.php?js=yes');";}
	$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
	
	$run_update_task_now=$tpl->javascript_parse_text("{run_update_task_now}");
	$ip=new networking();
	$hash=$ip->ALL_IPS_GET_ARRAY();
	
	unset($hash["127.0.0.1"]);
	
	
	$array[0]="Kaspersky Security Center";
	$array[1]="{system_protection_only}";
	$array[2]="{server_protection_only}";
	$array[3]="{gateways_protection_only}";
	$array[5]="{all}";
	
	$SCHEDULES[0]="1 {hour}";
	$SCHEDULES[1]="2 {hours}";
	$SCHEDULES[2]="4 {hours}";
	$SCHEDULES[3]="6 {hours}";
	
	$CPUSHARE[102]="10%";
	$CPUSHARE[204]="20%";
	$CPUSHARE[256]="25%";
	$CPUSHARE[307]="30%";
	$CPUSHARE[512]="50%";
	$CPUSHARE[620]="60%";
	$CPUSHARE[716]="70%";
	$CPUSHARE[819]="80%";
	$CPUSHARE[921]="90%";
	$CPUSHARE[1024]="100%";
	
	
	$BLKIO[100]="10%";
	$BLKIO[200]="20%";
	$BLKIO[250]="25%";
	$BLKIO[300]="30%";
	$BLKIO[450]="45%";
	$BLKIO[500]="50%";
	$BLKIO[700]="70%";
	$BLKIO[800]="80%";
	$BLKIO[900]="90%";
	$BLKIO[1000]="100%";
	
	if(is_file("/etc/artica-postfix/settings/Daemons/UpdateUtilityCpuShares")){
		$UpdateUtilityCpuShares=intval($sock->GET_INFO("UpdateUtilityCpuShares"));
		$UpdateUtilityDiskIO=intval($sock->GET_INFO("UpdateUtilityDiskIO"));
		if($UpdateUtilityCpuShares==0){$UpdateUtilityCpuShares=256;}
		if($UpdateUtilityDiskIO==0){$UpdateUtilityDiskIO=450;}
		
		$cpushare="	<tr>
		<td class=legend style='font-size:24px'>{cpu_performance}:</td>
		<td style='font-size:24px'>{$CPUSHARE[$UpdateUtilityCpuShares]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{disk_performance}:</td>
		<td style='font-size:24px'>{$BLKIO[$UpdateUtilityDiskIO]}</td>
	</tr>	";
		
	}else{
		$error_cpu="<p class=text-error style='font-size:18px'>{UpdateUtility_no_cpu}</p>";
	}
		
	
	$sock->getFrameWork("updateutility.php?iscron=yes");
	$UpdateUtilityIsCron=intval($sock->GET_INFO("UpdateUtilityIsCron"));
	
	if($UpdateUtilityIsCron==0){
		$error_cron="<p class=text-error style='font-size:18px'>{UpdateUtility_no_cron}</p>";
		
	}
	
	$SQUID_INSTALLED=true;
	if(!$users->SQUID_INSTALLED){$SQUID_INSTALLED=false;}
	
	if($SQUID_INSTALLED){
		$UpdateUtilityForceProxy_ON="{yes}";
		$UpdateUtilityForceProxy=$sock->GET_INFO("UpdateUtilityForceProxy");
		if($UpdateUtilityForceProxy==0){$UpdateUtilityForceProxy_ON="{no}";}
		$redirect_updates_to_localservice="
	<tr>
		<td class=legend style='font-size:24px'>{redirect_updates_to_localservice}:</td>
		<td style='font-size:24px'>$UpdateUtilityForceProxy_ON</td>
	</tr>";
		
	}
	
	
	
	$infos="$error_cron$error_cpu
<div style='width:98%;min-height:417px;' class=form>
<table style='width:100%' style='margin-top:10px'>



	<tr>
		<td class=legend style='font-size:24px'>{webserver_name}:</td>
		<td style='font-size:24px'>$UpdateUtilityWebServername</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{uploaded}:</td>
		<td style='font-size:24px'>$DSIZE</td>
	</tr>	
	
	
	
	<tr>
		<td class=legend style='font-size:24px'>{directory}:</td>
		<td style='font-size:24px'>$UpdateUtilityStorePath</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{kaspersky_products}:</td>
		<td style='font-size:24px'>{$array[$UpdateUtilityUpdatesType]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{schedule} ({each}):</td>
		<td style='font-size:24px'>{$SCHEDULES[$UpdateUtilitySchedule]}</td>
	</tr>	
	$cpushare
	$redirect_updates_to_localservice
	<tr><td colspan=2 align=right>&nbsp;</td></tr>
	<tr>
		<td colspan=2 align=right>". button("{configuration_wizard}","Loadjs('UpdateUtility.wizard.php')",30)."</td>
	</tr>
</table>
</div>";
	
	
$raccourcis="
<table style='width:95%' style='margin-top:10px' class=form>
<tr>
	<td width=1%><img src='img/arrow-blue-left-24.png'></td>
	<td width=99%>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ufdbguard.UpdateUtility.php');\"
		style='font-size:24px;text-decoration:underline'>{enable_filter_redirection}</a>
	</td>
</tr>
<tr>
	<td width=1%><img src='img/arrow-blue-left-24.png'></td>
	<td width=99%>
		<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$containerjs;\"
		style=\"font-size:24px;text-decoration:underline\">{create_a_dedicated_container}</a>
	</td>
</tr>
						<tr>
							<td width=1%><img src='img/arrow-blue-left-24.png'></td>
							
							<td width=99%>
								<a href=\"javascript:blur();\" 
								OnClick=\"javascript:Loadjs('$page?add-freeweb-js=yes&t=$t');\"
						 		style=\"font-size:24px;text-decoration:underline\">{add_a_web_service}</a>
							</td>
						</tr>	
									
					</table>";
	
	
	$html="
<div style='font-size:40px;margin-bottom:10px'>{APP_KASPERSKY_UPDATE_UTILITY}</div>
<table style='width:100%'>
	<tr>
		<td valign='top' style='width:570px'><div id='status-$t' style='width:98%' class=form></div></td>
		<td valign='top' style='width:1130px'>$infos</td>
	</tr>
</table>
<div id='freewebs-$t' style='width:1450px;height:550px'></div>
	
	
	<script>
		function UpdateUtilityStatus(){
			var UpdateUtilityUseLoop=$UpdateUtilityUseLoop;
			if(UpdateUtilityUseLoop==1){
				document.getElementById('UpdateUtilityStorePath').disabled=true;
			}
		
			LoadAjax('status-$t','$page?status=yes');
		}
	
		

		
	var x_SaveUpdateUtilityConf= function (obj) {
	      var results=obj.responseText;
	      if(results.length>3){alert(results);}
	      RefreshTab('main_upateutility_config');
	}	

	function SaveUpdateUtilityConf(){
			var XHR = new XHRConnection();
			if(document.getElementById('UpdateUtilityAllProducts').checked){XHR.appendData('UpdateUtilityAllProducts','1');}else{XHR.appendData('UpdateUtilityAllProducts','0');}
			if(document.getElementById('UpdateUtilityOnlyForKav4Proxy').checked){XHR.appendData('UpdateUtilityOnlyForKav4Proxy','1');}else{XHR.appendData('UpdateUtilityOnlyForKav4Proxy','0');}
			
			XHR.appendData('UpdateUtilityStorePath',document.getElementById('UpdateUtilityStorePath').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveUpdateUtilityConf);	
		}		
	
	function UpdateUtilityStartTask(){
		if(confirm('$run_update_task_now ?')){
			var XHR = new XHRConnection();
			XHR.appendData('UpdateUtilityStartTask','yes');
			XHR.sendAndLoad('$page', 'POST',x_SaveUpdateUtilityConf);
		}
	
	}
	
	
	
	UpdateUtilityStatus();		
	
	YahooWin3Hide();
	$js
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function UpdateUtilitySave(){
	$sock=new sockets();
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	
	if($_POST["UpdateUtilityOnlyForKav4Proxy"]==1){
		$_POST["UpdateUtilityAllProducts"]=0;
	}
	
	$sock->SET_INFO("UpdateUtilityAllProducts", $_POST["UpdateUtilityAllProducts"]);
	$sock->SET_INFO("UpdateUtilityRedirectEnable", $_POST["UpdateUtilityRedirectEnable"]);
	$sock->SET_INFO("UpdateUtilityOnlyForKav4Proxy", $_POST["UpdateUtilityOnlyForKav4Proxy"]);
	if($UpdateUtilityUseLoop==0){
		$sock->SET_INFO("UpdateUtilityStorePath", $_POST["UpdateUtilityStorePath"]);
	}
	$sock->getFrameWork("services.php?restart-updateutility=yes");
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-updateutility=yes");
	
}

function dbsize(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$refresh=$_GET["refresh"];
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/UpdateUtilitySize.size.db";
	$array=unserialize(@file_get_contents($arrayfile));
	if(!is_array($array)){
		$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
		echo "<script>LoadAjaxTiny('$refresh','$page?dbsize=yes&refresh=$refresh')</script>";
		return;

	}

	if(isset($_GET["recalc"])){
		$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
		$array=unserialize(@file_get_contents($arrayfile));
	}
	$arrayT["DBSIZE"]=$array["DBSIZE"];
	$t=time();
	$color="black";
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	
	$SIZEDSK="<td nowrap style='font-weight:bold;font-size:24px'>". FormatBytes($array["SIZE"])."</td>";
	$SIZEDSKU="<td nowrap style='font-weight:bold;font-size:24px'>". FormatBytes($array["USED"])."</td>";
	$SIZEDSKA="<td nowrap style='font-weight:bold;font-size:24px;color:$color'>". FormatBytes($array["AIVA"])." {$array["POURC"]}%</td>";
	if($UpdateUtilityUseLoop==1){
		$sql="SELECT `path`,`loop_dev` FROM loop_disks WHERE `disk_name`='UpdateUtility'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$array=unserialize(base64_decode($sock->getFrameWork("system.php?tune2fs-values=".base64_encode($ligne["loop_dev"])."&dirscan=".base64_encode("/automounts/UpdateUtility"))));
		$array["IPOURC"]=$array["INODES_POURC"];
		$array["IUSED"]=$array["INODES_USED"];
		$array["ISIZE"]=$array["INODES_MAX"];
		$SIZEDSK="<td nowrap style='font-weight:bold;font-size:24px'>". $array["SIZE"]."</td>";
		$SIZEDSKU="<td nowrap style='font-weight:bold;font-size:24px'>". $array["USED"]."</td>";
		$array["POURC"]=100-$array["POURC"];
		$SIZEDSKA="<td nowrap style='font-weight:bold;font-size:24px;color:$color'>{$array["AIVA"]} {$array["POURC"]}%</td>";
		
	}
	
	
	
	if($array["IPOURC"]>99){$color="red";}
	if($array["POURC"]>99){$color="red";}
	
	$DBSIZE_COLOR="#46a346";
	if($arrayT["DBSIZE"]<10240){$DBSIZE_COLOR="#898989";}
	
	$path=UpdateUtilityPatternDatePath();
	$pattern_dateU=base64_decode($sock->getFrameWork("cmd.php?UpdateUtility-pattern-date=yes&path=".urlencode($path)));
	
	$pattern_date_orgU=$pattern_dateU;
	if($pattern_dateU<>null){
		$day=substr($pattern_dateU, 0,2);
		$month=substr($pattern_dateU, 2,2);
		$year=substr($pattern_dateU, 4,4);
		$re=explode(";",$pattern_date_orgU);
		$time=$re[1];
		$H=substr($time, 0,2);
		$M=substr($time, 2,2);
		$pattern_dateU="$year/$month/$day $H:$M:00";
	}else{
		$pattern_dateU="-";
	}
	
	$html="

	<table style='width:100%;margin-top:20px'>
		<tr>
			<td class=legend style='font-size:24px;font-weight:bold;color:$DBSIZE_COLOR' valign='top'>{pattern_version}:</td>
			<td style='font-size:24px;font-weight:bold;color:$DBSIZE_COLOR'>$pattern_dateU</td>
		</tr>	
	<tr>
		<td class=legend style='font-size:24px;color:$DBSIZE_COLOR'>{current_size}:</td>
		<td nowrap style='font-weight:bold;font-size:24px;color:$DBSIZE_COLOR'>". FormatBytes($arrayT["DBSIZE"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{hard_drive}:</td>
		$SIZEDSK
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{used}:</td>
		$SIZEDSKU
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{free}:</td>
		$SIZEDSKA
		
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>inodes:</td>
		<td nowrap style='font-weight:bold;font-size:24px;color:$color'>{$array["IUSED"]}/{$array["ISIZE"]} ({$array["IPOURC"]}%)</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'>". imgtootltip("20-refresh.png","{refresh}","UpdateUtilityStatus()")."</td>
	</tr>
	</table>
		
	<div style='margin-top:15px;text-align:right'>". button("{update_now}", "UpdateUtilityStartTask()",22)."</div>

	";


	echo $tpl->_ENGINE_parse_body($html);

}

function product_section(){
	$sock=new sockets();
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$productKey=$_GET["product-key"];
	$update=new updateutilityv2();
	$Array=$update->families[$productKey]["LIST"];
	$html="<center><center class=form style='width:65%'>";
	while (list ($ProductKey, $ProductKeyArray) = each ($Array) ){
		$ProductName=$ProductKeyArray["NAME"];
		if(count($ProductKeyArray["PRODUCTS"])==0){continue;}
		$html=$html."
		
		<table cellspacing='0' cellpadding='0' border='0' class='tableView' >
		<thead class='thead'>
			<tr>
			<th colspan=2 style='font-size:24px'>{$ProductName}</th>
			</tr>
		</thead>
		<tbody class='tbody'>";		
		$classtr=null;	
		while (list ($ProductSubKey, $ProductVersion) = each ($ProductKeyArray["PRODUCTS"]) ){
				if($ProductVersion=="Administration Tools"){continue;}
				if($ProductVersion=="Kaspersky Administration Kit"){continue;}
				if($ProductVersion=="Kaspersky Security Center"){continue;}
				if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$enabled=0;
				if($update->MAIN_ARRAY["ComponentSettings"][$ProductSubKey]=="true"){
					$img=imgtootltip("check-32.png","{enable}","UpdateUtilityEnable('$ProductSubKey')",null,$ProductSubKey);
				}else{
					$img=imgtootltip("check-32-grey.png","{enable}","UpdateUtilityEnable('$ProductSubKey')",null,$ProductSubKey);
				}
				
				if($UpdateUtilityAllProducts==1){
					$img="<img src='img/service-check-32.png'>";
				}
				
				
			$html=$html . "
		<tr class=$classtr>
			
			<td style='font-size:16px'>$ProductVersion</td>
			<td style='font-size:16px' width=1%>$img</td>
		</tr>";
			
		}
		
		$html=$html . "</tbody>
		</table><br>";
		
	}
	
	
	$html=$html."</center></center>
	<script>
		function UpdateUtilityEnable(ProductSubKey){
			var XHR = new XHRConnection();
			XHR.appendData('ProductSubKey',ProductSubKey);
			var img=document.getElementById(ProductSubKey).src;
			if(img.indexOf('32-grey')>0){
				document.getElementById(ProductSubKey).src='/img/check-32.png';
				XHR.appendData('value','true');
			}else{
				document.getElementById(ProductSubKey).src='/img/check-32-grey.png';
				XHR.appendData('value','false');
			}
			
			XHR.sendAndLoad('$page', 'POST');
		}
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function product_section_save(){
	$update=new updateutilityv2();
	$update->MAIN_ARRAY["ComponentSettings"][$_POST["ProductSubKey"]]=$_POST["value"];
	$update->Save();
}

function UpdateUtilityStartTask(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?UpdateUtilityStartTask=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
	
	
}


function frewebslist(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$sql="SELECT * FROM freeweb WHERE groupware='UPDATEUTILITY'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$servername=$ligne["servername"];
		
		$tr[]="
		<tr>
			<td width=1%><img src=\"img/arrow-right-24.png\"></td>
			<td width=99%>
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername');\" 
				style=\"font-size:22px;text-decoration:underline\">http://$servername</a>
				</td>
		</tr>
		";
		
	}
	
	$html="
			<div style=\"font-size:30px;margin-top:30px\">{web_services}:</div>
			<table style=\"width:99%\">".@implode("\n", $tr)."</table>";
	

	
}
function UpdateUtilityPatternDatePath(){
	
	$sock=new sockets();
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	return "$UpdateUtilityStorePath/database/Updates/index/u0607g.xml";

}
function DATE_START(){

	if(isset($_SESSION["APACHE_GRAPH_DATE_START"])){return $_SESSION["APACHE_GRAPH_DATE_START"];}
	$tpl=new templates();
	$q=new mysql_squid_builder();

	$table="dashboard_apache_sizes";
	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	$_SESSION["APACHE_GRAPH_DATE_START"]= $tpl->javascript_parse_text("{date_start} $time1, {last_date} $time2");
	return $_SESSION["APACHE_GRAPH_DATE_START"];
}

function graph1(){
	$tpl=new templates();
	$sock=new sockets();
	$UpdateUtilityWebServername=$sock->GET_INFO("UpdateUtilityWebServername");
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(SIZE) as SIZE,TIME FROM dashboard_apache_sizes WHERE SITENAME='$UpdateUtilityWebServername' GROUP BY TIME ORDER BY TIME;";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["SIZE"]/1024;
		$SIZE=$SIZE/1024;
		$xdata[]=$ligne["TIME"];
		$ydata[]=round($SIZE,2);
	}
	
	$title="{downloaded_flow} (MB) ".DATE_START();
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="freewebs-{$_GET["t"]}";
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();

	

}

