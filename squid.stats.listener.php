<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	include_once('ressources/class.squid.remote-stats-appliance.inc');
	//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	
	if(isset($_POST["STATS_LINE"])){STATS_LINE();exit;}
	if(isset($_POST["INSCRIPTION"])){INSCRIPTION();exit;}
	if(isset($_POST["CHANGE_CONFIG"])){CHANGE_CONFIG();exit;}
	if(isset($_POST["SQUID_TABLES_INDEX"])){export_tables();exit;}
	if(isset($_POST["squid_nodes_settings"])){squid_nodes_settings();exit;}
	if(isset($_GET["webtests"])){webtests();exit;}
	



function STATS_LINE(){
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==0){die();}	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	if(isset($_POST["MYSSLPORT"])){
		writelogs($_SERVER["REMOTE_ADDR"] .":{$_POST["MYSSLPORT"]} production server....",__FUNCTION__,__FILE__,__LINE__);
		$hostname=gethostbyaddr($_SERVER["REMOTE_ADDR"]);
		$time=date('Y-m-d H:i:s');
		$sql="INSERT IGNORE INTO `squidservers`
		 (ipaddr,hostname,port,created,udpated) 
		 VALUES ('{$_SERVER["REMOTE_ADDR"]}','$hostname','{$_POST["MYSSLPORT"]}','$time','$time')";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM squidservers WHERE ipaddr='{$_SERVER["REMOTE_ADDR"]}'"));
		if($ligne["ipaddr"]==null){$q->QUERY_SQL($sql);}else{
			$q->QUERY_SQL("UPDATE `squidservers` SET udpated='$time' WHERE ipaddr='{$ligne["ipaddr"]}'");
		}
		
		
	}else{
		writelogs("MYSSLPORT is not set....",__FUNCTION__,__FILE__,__LINE__);
	}
	
	
	$array=unserialize(base64_decode($_POST["STATS_LINE"]));
	while (list ($table, $contentArray) = each ($array) ){
		
		if(!$q->TABLE_EXISTS($table)){
			writelogs("$table no such table, aborting ",__FUNCTION__,__FILE__,__LINE__);
			echo "<ANSWER>$table no such table</ANSWER>\n";
			die();
		}
		$prefixsql="INSERT IGNORE INTO $table (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`zDate`,`zMD5`,`remote_ip`,`country`,`QuerySize`,`uid`,`cached`,`MAC`,`hostname`) VALUES ";
		if(count($contentArray)>0){
			$sql="$prefixsql".@implode(",",$contentArray);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
				if(preg_match("#Column count doesn.+?t match#",$q->mysql_error)){continue;}
				echo "ERROR: $q->mysql_error\n";
				return;
				}
		}
		
		
	}
	writelogs("Received ".strlen($_POST["STATS_LINE"])." bytes ". count($array). " lines from ".$_SERVER["REMOTE_ADDR"] .":{$_POST["MYSSLPORT"]} (success)",__FUNCTION__,__FILE__,__LINE__);
	echo "<ANSWER>OK</ANSWER>\n";
	
	
}

function squid_nodes_settings(){
	$q=new mysql();
	$uuid=$_POST["squid_nodes_settings"];
	if($uuid==null){
		echo "<ERROR>No uuid set...</ERROR>";
		return;
	}
	
	$sql="SELECT `content` FROM squid_nodes_settings WHERE uuid='$uuid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){
		echo "<ERROR>$q->mysql_error</ERROR>";
		return;
	}
	if(strlen($ligne["content"])<10){
		echo "<ERROR>No data for uuid  `$uuid`...</ERROR>";
		return;
	}
	
	echo "<CONTENT>{$ligne["content"]}</CONTENT>";
}

function CHANGE_CONFIG(){
	if($_POST["CHANGE_CONFIG"]=="FILTERS"){
		$sock=new sockets();
		$sock->send_email_events_notroot("Order to rebuild web filters engine", "The statistics appliance send order \"FILTERS\"", "proxy");
		$sock->getFrameWork("squid.php?rebuild-filters=yes");	
		echo "<ANSWER>OK</ANSWER>\n";
	}
	
	if($_POST["CHANGE_CONFIG"]=="DNSMASQ"){
		$sock=new sockets();
		$sock->send_email_events_notroot("Order to rebuild DNS engine", "The statistics appliance send order \"DNSMASQ\"", "proxy");
		$sock->getFrameWork("services.php?dnsmasq-reconfigure=yes");	
		echo "<ANSWER>OK</ANSWER>\n";		
	}
	
	if($_POST["CHANGE_CONFIG"]=="SQUID_LANG_PACK"){
		$sock=new sockets();
		$sock->send_email_events_notroot("Order to rebuild HTML templates", "The statistics appliance send order \"SQUID_LANG_PACK\"", "proxy");
		$sock->getFrameWork("squid.php?build-templates=yes");	
		echo "<ANSWER>OK</ANSWER>\n";		
	}
	
	if($_POST["CHANGE_CONFIG"]=="USERSMAC"){
		$sock=new sockets();
		$sock->getFrameWork("squid.php?squid-reconfigure=yes");
		$sock->getFrameWork("services.php?build-system-tasks=yes");
		$sock->send_email_events_notroot("Order to rebuild UsersMac database", "The statistics appliance send order \"USERSMAC\"", "proxy");
		echo "<ANSWER>OK</ANSWER>\n";		
	}	
	
	if($_POST["CHANGE_CONFIG"]=="BUILDCONF"){
		$sock=new sockets();
		$sock->getFrameWork("squid.php?squid-reconfigure=yes");
		$sock->getFrameWork("services.php?build-system-tasks=yes");
		$sock->send_email_events_notroot("Order to rebuild squid config", "The statistics appliance send order \"BUILDCONF\"", "proxy");
		echo "<ANSWER>OK</ANSWER>\n";		
	}		
	
	
	
	
	
}

function INSCRIPTION(){
	if(!isset($_POST["MYSSLPORT"])){
		
		echo "MYSSLPORT = NULL \n";
		return;
		
	}
	$q=new mysql_squid_builder();
	$q->CheckTables();
	writelogs($_SERVER["REMOTE_ADDR"] .":{$_POST["MYSSLPORT"]} production server....",__FUNCTION__,__FILE__,__LINE__);
	$hostname=gethostbyaddr($_SERVER["REMOTE_ADDR"]);
	$time=date('Y-m-d H:i:s');
	$sql="INSERT IGNORE INTO `squidservers` (ipaddr,hostname,port,created) VALUES ('{$_SERVER["REMOTE_ADDR"]}','$hostname','{$_POST["MYSSLPORT"]}','$time')";
	$ligne=mysql_fetch_array("SELECT ipaddr FROM squidservers WHERE ipaddr='{$_SERVER["REMOTE_ADDR"]}'");
	if($ligne["ipaddr"]==null){
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		echo "\n<ANSWER>OK</ANSWER>\n";
		return;
	}
		
	$sql="UPDATE `squidservers` SET updated='$time' WHERE ipaddr='{$ligne["ipaddr"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	echo "\n<ANSWER>OK</ANSWER>\n";
}

function export_tables(){
	$q=new squid_stats_appliance();
	echo $q->GET_INDEX();
	
}

function webtests(){
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT sitename FROM webtests");
	while ($ligne = mysql_fetch_assoc($results)) {
		echo $ligne["sitename"]."\n";
	}
}



?>