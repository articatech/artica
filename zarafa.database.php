<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["mysql-dir"])){mysql_dir_popup();exit;}
	if(isset($_POST["ChangeMysqlDir"])){ChangeMysqlDir();exit;}
	if(isset($_GET["dbsize"])){dbsize();exit;}
	if(isset($_GET["page"])){page();exit;}
tabs();

function tabs(){
	
	$page=CurrentPageName();
	$fontsize="font-size:16px;";
	$adduri="&font-size=18";$adduri2="?font-size={$_GET["font-size"]}";
	
	//$array["popup-instances"]="{multiple_webmail}";
	$array["status"]="{status}";
	
	$array["popup-mysql"]="{APP_MYSQL}";
	$array["tune-mysql"]="{mysql_tuning}";
	$array["popup-importAD"]="{active_directory_importation}";
	
	$fontsize="font-size:18px";
	
	while (list ($num, $ligne) = each ($array) ){
	
	
		if($num=="status"){
			$html[]="<li><a href=\"zarafa.database.php?page=yes\" style='$fontsize' ><span>$ligne</span></a></li>\n";
			continue;
		}
	
		if($num=="popup-importAD"){
			$html[]="<li><a href=\"zarafa.import-ad-contacts.php\" style='$fontsize' ><span>$ligne</span></a></li>\n";
			continue;
		}	
		
		
		if($num=="popup-mysql"){
			$html[]="<li><a href=\"zarafa.mysqlparams.php\" style='font-size:18px'><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="tune-mysql"){
			$html[]="<li><a href=\"zarafa.mysql.php\" style='font-size:18px'><span>$ligne</span></a></li>\n";
			continue;
		}
		
	
		$html[]="<li><a href=\"$page?$num=yes$adduri\" style='$fontsize' ><span>$ligne</span></a></li>\n";
			
	}
	$tabwidth=759;
	if(is_numeric($_GET["tabwith"])){$tabwitdh=$_GET["tabwith"];}
	
	echo build_artica_tabs($html, "main_config_zarafaDB");
		
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	
	$movefolder=Paragraphe('folder-storage-64.png','{storage_directory}',
	'{change_mysql_directory_text}',"javascript:YahooWin3(650,'$page?mysql-dir=yes','{storage_directory}');",null);

	
	$tasks=Paragraphe('processor-64.png','{processes_list}',
			'{processes_list_mysql_explain}',"javascript:Loadjs('zarafa.dabatase.processlist.php');",null);	
	

	$trash=Paragraphe("database-linker-delete-64.png", "{REMOVE_DATABASE}", "{REMOVE_DATABASE_ZARAFA_TEXT}","javascript:REMOVE_DATABASE()");
	// mysqladmin --socket /var/run/mysqld/zarafa-db.sock -u root processlist
	
	$sock=new sockets();
	$ZarafaDedicateMySQLServer=intval($sock->GET_INFO("ZarafaDedicateMySQLServer"));
	
	if($ZarafaDedicateMySQLServer==1){
		$trash=Paragraphe("database-linker-delete-64.png", "{REMOVE_DATABASE}", "{REMOVE_DATABASE_ZARAFA_TEXT}","javascript:Loadjs('zarafa.trash.php')");
	}
	
	
	$tr[]=$movefolder;
	$tr[]=$restore;
	$tr[]=$tasks;
	$tr[]=$trash;
	$confirm_remove_zarafa_db=$tpl->javascript_parse_text("{confirm_remove_zarafa_db}");
	
	$table=CompileTr3($tr,"form");
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top'><div id='dbsize' style=width:300px></div></td>		
	<td valign='top'><div style=width:550px><center>$table</center></div></td>
	</tr>
	</table>
	
	<script>
		LoadAjaxTiny('dbsize','$page?dbsize=yes&refresh=dbsize');
		

var x_REMOVE_DATABASE=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>5){alert(tempvalue);}
     	RefreshTab('main_config_zarafa');
      }	
		
	function REMOVE_DATABASE(){
		if(confirm('$confirm_remove_zarafa_db')){
			var XHR = new XHRConnection();
			XHR.appendData('remove-db','1');
			XHR.sendAndLoad('zarafa.tools.php', 'POST',x_REMOVE_DATABASE);
			}
	}

		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function dbsize(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$refresh=$_GET["refresh"];
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/zarafadb.size.db";
	$array=unserialize(@file_get_contents($arrayfile));
	if(!is_array($array)){
		$sock->getFrameWork("zarafa.php?artica-dbsize=yes");
		echo "<script>LoadAjaxTiny('$refresh','$page?dbsize=yes&refresh=$refresh')</script>";
		return;
		
	}
	
	if(isset($_GET["recalc"])){
		$sock->getFrameWork("zarafa.php?artica-dbsize=yes");
		$array=unserialize(@file_get_contents($arrayfile));
	}
	
	$color="black";
	if($array["IPOURC"]>99){$color="#CC0A0A";}
	if($array["POURC"]>99){$color="#CC0A0A";}	
	
	$t=time();
	$html="
	
	<table style='width:98%' class=form>
	<tr>
		<td class=legend>{current_size}:</td>		
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["DBSIZE"])."</td>
	</tr>	
	<tr>
		<td class=legend>{hard_drive}:</td>		
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["SIZE"])."</td>
	</tr>	
	<tr>
		<td class=legend>{used}:</td>		
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["USED"])."</td>
	</tr>	
	<tr>
		<td class=legend>{free}:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>". FormatBytes($array["AIVA"])." {$array["POURC"]}%</td>
	</tr>
	<tr>
		<td class=legend>inodes:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>{$array["IUSED"]}/{$array["ISIZE"]} ({$array["IPOURC"]}%)</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjax('$refresh','$page?dbsize=yes&recalc=yes&refresh=$refresh')")."</td>
	</tr>														
	</table>	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mysql_dir_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ChangeMysqlDir=base64_decode($sock->getFrameWork("zarafa.php?ChangeMysqlDir-zarafa=yes"));
	if($ChangeMysqlDir==null){$ChangeMysqlDir="/home/zarafa-db";}
	$t=time();
	$html="
	<div id='ChangeMysqlDirDiv$t'></div>
	<div class=explain style='font-size:16px'>{ChangeMysqlDir_explain}</div>
	<p>&nbsp;</p>
	<table style='width:100%'>
	<tr>
		<td class=legend>{directory}:</td>
		<td>". Field_text("ChangeMysqlDir-zarafa",$ChangeMysqlDir,"font-size:18px;padding:3px;width:280px")."</td>
		<td>". button_browse("ChangeMysqlDir-zarafa")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
			<hr>". button("{apply}","SaveChangeMysqlDir$t()",26)."</td>
			</tr>
			</table>
<script>
	var x_SaveChangeMysqlDir= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('ChangeMysqlDirDiv$t').innerHTML='';
			if(tempvalue.length>3){alert(tempvalue)};
			
	}

	function SaveChangeMysqlDir$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ChangeMysqlDir',document.getElementById('ChangeMysqlDir-zarafa').value);
		AnimateDiv('ChangeMysqlDirDiv$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveChangeMysqlDir);
	}
</script>
</div>
";

echo $tpl->_ENGINE_parse_body($html);

}

function ChangeMysqlDir(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?ChangeMysqlDir-articadb=yes&dir=".base64_decode($_POST["ChangeMysqlDir"]));
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{zarafadb_changedir_exp}");
	
	
}
