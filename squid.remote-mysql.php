<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["maintenance"])){maintenance_settings();exit;}
	
	if(isset($_GET["remote-users"])){remote_users();exit;}
	if(isset($_GET["local-users"])){local_users();exit;}
	if(isset($_GET["member-add"])){members_add();exit;}
	if(isset($_GET["member-delete"])){members_delete();exit;}		
	
	if(isset($_GET["tools"])){tools();exit;}
	if(isset($_GET["run-compile"])){task_run_sarg();exit;}
	
	if(isset($_GET["events"])){events();exit;}
	if(isset($_POST["EnableSquidRemoteMySQL"])){SAVE();exit;}
	if(isset($_GET["cnx-status"])){squid_mysql_status();exit;}
	if(isset($_GET["migrate-localdata-js"])){migrate_local_datas_js();exit;}
	if(isset($_POST["migratelocal"])){migrate_local_datas_perform();exit;}
	if(isset($_GET["migr-logs"])){migrlogs();exit;}
	if(isset($_POST["migr-rlogs"])){migrrlogs();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body("{remote_mysql_server}");
	header("content-type: application/x-javascript");
	$html="YahooWin2('850','$page?popup=yes','$title');";
	
	echo $html;
	
	
	
}

function migrate_local_datas_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$warn=$tpl->javascript_parse_text("{squidlog_migrate_ask}");	
	$title=$tpl->javascript_parse_text("{migrate_local_datas}");
	$t=time();
	$html="
			
		var xSave$t= function (obj) {
			var results=trim(obj.responseText);
			if(results.length>3){alert(results);}		
			RefreshTab('artica_squid_db_exttabs');
			YahooWinBrowse('850','$page?migr-logs=yes','$title');
		}
	
		function migr$t(){
			if(!confirm('$warn')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('migratelocal','yes');
			XHR.sendAndLoad('$page', 'POST',xSave$t);			
		
		}
			

	
	migr$t();
	";
	
	echo $html;
}

function popup(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["maintenance"]="{remote_mysql_server}";
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	}
	
	$id=time();
	
	echo "
	<div id='artica_squid_db_exttabs' style='width:100%;'>
		<ul style='font-size:14px'>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#artica_squid_db_exttabs').tabs();
			
			
			});
		</script>";		
	
	
}	

function maintenance_settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=time();	
	$sock=new sockets();
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	$squidRemostatisticsServer=$sock->GET_INFO("squidRemostatisticsServer");
	$squidRemostatisticsPort=$sock->GET_INFO("squidRemostatisticsPort");
	$squidRemostatisticsUser=$sock->GET_INFO("squidRemostatisticsUser");
	$squidRemostatisticsPassword=$sock->GET_INFO("squidRemostatisticsPassword");
	$DisableLocalStatisticsTasks=$sock->GET_INFO("DisableLocalStatisticsTasks");

	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top'><div id='cnx-status'></div></td>
	<td valign='top'>
		<div style='font-size:16px;' class=explain>{external_artica_mysql_statistics_generator}</div>
	</td>
	</tr>
	</table>
	<table style='width:99%' class=form>		
	<tr>
		<td colspan=2></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' valign='middle'>{use_external_mysql_server}:</td>
		<td>". Field_checkbox("EnableSquidRemoteMySQL",1,"$EnableSquidRemoteMySQL","CheckSquidMysqlForm()")."</td>
	</tr>	
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px' valign='middle'>{mysqlserver}:</strong></td>
			<td align='left'>" . Field_text('squidRemostatisticsServer',$squidRemostatisticsServer,'width:310px;padding:3px;font-size:14px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px' valign='middle'>{listen_port}:</strong></td>
			<td align='left'>" . Field_text('squidRemostatisticsPort',$squidRemostatisticsPort,'width:90px;padding:3px;font-size:14px',null,null,'')."</td>
		</tr>				
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px' valign='middle'>{mysqlroot}:</strong></td>
			<td align='left'>" . Field_text('squidRemostatisticsUser',$squidRemostatisticsUser,'width:220px;padding:3px;font-size:14px',null,null)."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px' valign='middle'>{mysqlpass}:</strong></td>
			<td align='left'>" . Field_password("squidRemostatisticsPassword",$squidRemostatisticsPassword,"width:210px;padding:3px;font-size:14px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px' valign='middle'>{DisableLocalStatisticsTasks}:</td>
			<td>". Field_checkbox("DisableLocalStatisticsTasks",1,"$DisableLocalStatisticsTasks")."</td>
		</tr>		
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
	</div>
<script>
	var xSave$t= function (obj) {
			var results=trim(obj.responseText);
			if(results.length>3){alert(results);}		
			RefreshTab('artica_squid_db_exttabs');
		}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableSquidRemoteMySQL').checked){XHR.appendData('EnableSquidRemoteMySQL',1);}else{XHR.appendData('EnableSquidRemoteMySQL',0);}
		if(document.getElementById('DisableLocalStatisticsTasks').checked){XHR.appendData('DisableLocalStatisticsTasks',1);}else{XHR.appendData('DisableLocalStatisticsTasks',0);}
		XHR.appendData('squidRemostatisticsServer',document.getElementById('squidRemostatisticsServer').value);
		XHR.appendData('squidRemostatisticsPort',document.getElementById('squidRemostatisticsPort').value);
		XHR.appendData('squidRemostatisticsUser',document.getElementById('squidRemostatisticsUser').value);
		XHR.appendData('squidRemostatisticsPassword',encodeURIComponent(document.getElementById('squidRemostatisticsPassword').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	function CheckSquidMysqlForm(){
		document.getElementById('squidRemostatisticsServer').disabled=true;
		document.getElementById('squidRemostatisticsPort').disabled=true;
		document.getElementById('squidRemostatisticsUser').disabled=true;
		document.getElementById('squidRemostatisticsPassword').disabled=true;
		if(document.getElementById('EnableSquidRemoteMySQL').checked){
			document.getElementById('squidRemostatisticsServer').disabled=false;
			document.getElementById('squidRemostatisticsPort').disabled=false;
			document.getElementById('squidRemostatisticsUser').disabled=false;
			document.getElementById('squidRemostatisticsPassword').disabled=false;	
		}
	}
	
	function RefreshMysqlConnection(){
		LoadAjaxTiny('cnx-status','$page?cnx-status=yes');
	}
	CheckSquidMysqlForm();
	RefreshMysqlConnection();
</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SAVE(){
	$sock=new sockets();
	$_POST["squidRemostatisticsPassword"]=url_decode_special_tool($_POST["squidRemostatisticsPassword"]);
	$sock->SET_INFO("EnableSquidRemoteMySQL",$_POST["EnableSquidRemoteMySQL"]);
	$sock->SET_INFO("squidRemostatisticsServer",$_POST["squidRemostatisticsServer"]);
	$sock->SET_INFO("squidRemostatisticsPort",$_POST["squidRemostatisticsPort"]);
	$sock->SET_INFO("squidRemostatisticsUser",$_POST["squidRemostatisticsUser"]);
	$sock->SET_INFO("squidRemostatisticsPassword",$_POST["squidRemostatisticsPassword"]);
	$sock->SET_INFO("DisableLocalStatisticsTasks", $_POST["DisableLocalStatisticsTasks"]);
	$sock->getFrameWork("squid.php?build-schedules=yes");
	}

function squid_mysql_status(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$img="ok64.png";
	$title="{MYSQL_CONNECTION}";
	$text=date('H:i:s')."<br>".$q->mysql_server.":".$q->mysql_port;
	$button=button("{migrate_local_datas}", "Loadjs('$page?migrate-localdata-js=yes')",16);
	$button="<hr><center style='margin:5px'>$button</center>";
	
	if(!$q->BD_CONNECT()){
		$img="danger64.png";
		$title="{MYSQL_ERROR}";
		$text=$text."<br>".$q->mysql_error;
		$button=null;
	}
	
	$tpl=new templates();
	echo "<center>".$tpl->_ENGINE_parse_body(Paragraphe($img, $title, $text,null,$text)."<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshMysqlConnection()").$button)."</center>";
	
}

function migrate_local_datas_perform(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squidstats.php?migrate-local=yes");
	echo $tpl->javascript_parse_text("{task_executed_in_background}",1);
}

function migrlogs(){
	$page=CurrentPageName();
	$t=time();
	$logfilename="/usr/share/artica-postfix/ressources/logs/web/squidlogs.restore.log";
	echo "
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='squidlogs-$t'></textarea>
	<center id='animate-$t'></center>
	<script>
		
			
			
		var xGetLogs$t= function (obj) {
			var results=trim(obj.responseText);
			if(results.length>3){
				document.getElementById('squidlogs-$t').value=results;
			}
			setTimeout(\"GetLogs$t()\",1000);
		}
	
	
	function GetLogs$t(){
		if(!YahooWinBrowseOpen()){return;}
		var XHR = new XHRConnection();
		XHR.appendData('migr-rlogs','yes');
		XHR.sendAndLoad('$page', 'POST',xGetLogs$t);
	}
	
	setTimeout(\"GetLogs$t()\",1000);
	</script>";
	
	
}
function migrrlogs(){
	$logfilename="/usr/share/artica-postfix/ressources/logs/web/squidlogs.restore.log";
	echo @file_get_contents($logfilename);
	
}

