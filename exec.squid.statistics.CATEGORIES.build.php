<?php
ini_set('memory_limit','1000M');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");

$GLOBALS["zMD5"]=$argv[1];
BUILD_REPORT($argv[1]);


function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics-{$GLOBALS["zMD5"]}.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}





function GRAB_DATAS($ligne,$md5){
	$GLOBALS["zMD5"]=$md5;
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$USER_FIELD=$params["USER"];
	$md5_table=md5(__FUNCTION__."."."$from$to");
	$searchsites=trim($params["searchsites"]);
	$searchuser=trim($params["searchuser"]);
	$categories=trim($params["categories"]);
	$searchsites_sql=null;
	$searchuser_sql=null;
	if($categories=="*"){$categories=null;}
	if($searchuser=="*"){$searchuser=null;}
	
	if($categories<>null){
		$searchsites_sql=str_replace("*", ".*", $categories);
		if($searchsites_sql<>null){
			$searchsites_sql=" AND category ~* '$searchsites_sql'";
		}
	}
	if($searchuser<>null){
		$searchuser_sql=str_replace("*", ".*", $searchuser);
		if($searchuser_sql<>null){
			$searchuser_sql=" AND $USER_FIELD ~* '$searchuser_sql'";
		}
	}	

	
	$users_fiels="userid,ipaddr,mac";
	
	
	$sql="CREATE TABLE IF NOT EXISTS \"{$md5}report\"
	(zdate timestamp,
	mac macaddr,
	ipaddr INET,
	userid VARCHAR(64) NULL,
	category VARCHAR(64) NULL,
	familysite VARCHAR(128) NULL,
	size BIGINT,
	rqs BIGINT)";
	
	$q=new postgres_sql();
	
	$q->QUERY_SQL($sql);
	echo $sql."\n";
	if(!$q->ok){
		echo "***************\n$q->mysql_error\n***************\n";
		return false;
	}
	
	
	$q->QUERY_SQL("create index zdate{$md5}report on \"{$md5}report\"(zdate);");
	$q->QUERY_SQL("create index familysite{$md5}report on \"{$md5}report\"(familysite);");
	
	
	
	$distance=$influx->DistanceHour($from,$to);
	echo "Distance: {$distance} hours\n";
	if($distance>4){$TimeGroup="date_trunc('hour', zdate) as zdate";}
	

	$Z[]="SELECT SUM(size) as size,SUM(RQS) as rqs,familysite,category,$TimeGroup,userid,ipaddr,mac FROM access_log";
	$Z[]="WHERE (zdate >'".date("Y-m-d H:i:s",$from)."' and zdate < '".date("Y-m-d H:i:s",$to)."')";
	if($searchsites_sql<>null){$Z[]="$searchsites_sql";}
	if($searchuser_sql<>null){$Z[]="$searchuser_sql";}
	$Z[]="GROUP BY familysite,category,zdate,userid,ipaddr,mac";
	
	
	if($distance>23){
		$Z=array();
		$Z[]="SELECT SUM(size) as size,SUM(RQS) as rqs,familysite,category,zdate,userid,ipaddr,mac FROM access_month";
		$Z[]="WHERE (zdate >'".date("Y-m-d H:i:s",$from)."' and zdate < '".date("Y-m-d H:i:s",$to)."')";
		if($searchsites_sql<>null){$Z[]="$searchsites_sql";}
		if($searchuser_sql<>null){$Z[]="$searchuser_sql";}
		$Z[]="GROUP BY familysite,category,zdate,userid,ipaddr,mac";
		
	}
	
	$sql=@implode(" ", $Z);
	
	$sql="INSERT INTO \"{$md5}report\" (size,rqs,familysite,category,zdate,userid,ipaddr,mac) $sql";
	
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	
	$q->QUERY_SQL($sql);

	

	if(!$q->ok){
		echo "***************\n$postgres->mysql_error\n***************\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}
	
	
	
	$sql="SELECT COUNT(*) AS tcount FROM \"{$md5}report\"";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	$total = intval($ligne["tcount"]);
	
	echo "Member $total items inserted to PostGreSQL\n";
	
	if($total==0){
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}

	return true;
}

function REMOVE_TABLES($md5){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `{$md5}sites`");
	$q->QUERY_SQL("DROP TABLE `{$md5}users`");
	
}


function BUILD_REPORT($md5){
	build_progress("{building_query}",5);
	$unix=new unix();
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$from=InfluxQueryFromUTC($params["FROM"]);
	$to=InfluxQueryFromUTC($params["TO"]);
	$interval=$params["INTERVAL"];
	$user=$params["USER"];
	$md5_table=$md5;
	if(!GRAB_DATAS($ligne,$md5)){
		build_progress("{unable_to_query_to_bigdata}",110);
		return;
	}
	
	$q=new postgres_sql();
	$q->QUERY_SQL("COPY (SELECT * from \"{$md5}report\") To '/tmp/{$md5}report.csv' with CSV HEADER;");
	$values_size=@filesize("/tmp/{$md5}report.csv");
	$values=mysql_escape_string2(@file_get_contents("/tmp/{$md5}report.csv"));
	echo "MD5:{$GLOBALS["zMD5"]} {$values_size}Bytes ". FormatBytes($values_size/1024)."\n";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE reports_cache SET `builded`=1,`values`='$values',`values_size`='$values_size' WHERE `zmd5`='{$GLOBALS["zMD5"]}'");
	
	if(!$q->ok){
	echo $q->mysql_error."\n";
		build_progress("PostGreSQL {failed}",110);
		return;
	}
	
	build_progress("{success}",100);

}