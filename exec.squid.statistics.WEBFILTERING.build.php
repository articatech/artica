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
	$params["TO"]=intval($params["TO"]);$params["FROM"]=abs(intval($params["FROM"]));
	
	while (list ($A, $P) = each ($params) ){
		echo "Params $A......: $P\n";
		
	}
	
	
	echo "Date To......: {$params["TO"]}\n";
	
	
	$influx=new influx();
	$mintime=strtotime("2008-01-01 00:00:00");
	$params["TO"]=intval($params["TO"]);
	$params["FROM"]=abs(intval($params["FROM"]));
	
	echo "Date From....: {$params["FROM"]} <> $mintime\n";
	if($params["FROM"]<$mintime){
		echo "Date From....: {$params["FROM"]} < $mintime !!!\n";
		$params["FROM"]=strtotime(date("Y-m-d 00:00:00"));}
	$params["TO"]=intval($params["TO"]);if($params["TO"]<$mintime){$params["TO"]=time();}
	
	echo "Date From....: {$params["FROM"]}\n";
	$from=InfluxQueryFromUTC($params["FROM"]);
	
	
	$to=InfluxQueryFromUTC($params["TO"]);
	$interval=$params["INTERVAL"];

	$USER_FIELD=$params["USER"];
	echo "LIMIT........: $mintime\n";
	echo "Date From....: {$params["FROM"]}/$from/".date("Y-m-d H:i:s",$from)."\n";
	echo "Date To......: {$params["TO"]}/$to/".date("Y-m-d H:i:s",$to)."\n";
	echo "USER_FIELD...: $USER_FIELD\n";
	$SSEARCH=null;
	
	
	
	
	$searchsites=trim($params["searchsites"]);
	$searchuser=trim($params["searchuser"]);
	$categories=trim($params["categories"]);
	$searchsites_sql=null;
	$searchuser_sql=null;
	if($categories=="*"){$categories=null;}
	if($searchuser=="*"){$searchuser=null;}
	
	
	if($searchuser<>null){
		$searchuser_sql=str_replace(".", "\.", $searchuser);
		$searchuser_sql=str_replace("*", ".*", $searchuser_sql);
		if($searchuser_sql<>null){
			$SSEARCH=" ($USER_FIELD ~* '$searchuser_sql') AND ";
		}
	}	
	
	$SRF["USERID"]=true;
	$SRF["IPADDR"]=true;
	$SRF["MAC"]=true;
	unset($SRF[$USER_FIELD]);
	
	while (list ($A, $P) = each ($SRF) ){
		$srg[]=$A;
	}
	
	$users_fiels=@implode(",",$srg);
	

	if($searchuser<>null){ 
		$searchuser=str_replace(".", "\.", $searchuser);
		$searchuser=str_replace("*", ".*", $searchuser);
		$SSEARCH=" (client ~* '$searchuser') AND ";
	}
	
	$q=new mysql_squid_builder();
	
	
	$TimeGroup="date_trunc('hour', zdate)";
	$distance=$influx->DistanceHour($from,$to);
	echo "Distance: {$distance} hours\n";
	
	
	$sql="CREATE TABLE IF NOT EXISTS \"{$md5}report\" (zDate timestamp,
	website VARCHAR(128),
	category VARCHAR(64),
	rulename VARCHAR(128),
	hostname VARCHAR(128),
	client VARCHAR(128),
	rqs BIGINT)";
	
	$q=new postgres_sql();
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
	echo "***************\n$q->mysql_error\n***************\n";
	return false;
	}
	
	$q->QUERY_SQL("create index zdate{$md5}report on \"{$md5}report\"(zdate);");
	$q->QUERY_SQL("create index website{$md5}report on \"{$md5}report\"(website);");
	$q->QUERY_SQL("create index hostname{$md5}report on \"{$md5}report\"(hostname);");
	$q->QUERY_SQL("create index client{$md5}report on \"{$md5}report\"(client);");
	
	
	
	$q->QUERY_SQL("TRUNCATE TABLE \"{$md5}report\"");
	

	
	$Z[]="SELECT SUM(RQS) AS RQS,$TimeGroup as zdate,rulename,category,hostname,website,client FROM webfilter";
	$Z[]="WHERE $SSEARCH(zdate >='".date("Y-m-d H:i:s",$from)."'";
	$Z[]="and zdate <= '".date("Y-m-d H:i:s",$to)."')";
	$Z[]="GROUP BY $TimeGroup,rulename,category,hostname,website,client";
	


		
	$sql=@implode(" ", $Z);
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	$sql="INSERT INTO \"{$md5}report\" (rqs,zdate,rulename,category,hostname,website,client) $sql";
	
	
	
	$postgres=new postgres_sql();
	$results=$postgres->QUERY_SQL($sql);
	if(!$postgres->ok){
		echo $postgres->mysql_error."\n";
		return false;
	}
	
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM \"{$md5}report\""));
	
	if(!$q->ok){
		echo "***************\nERROR $q->mysql_error\n***************\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}
	
	
	$c=$ligne["tcount"];
	if($c==0){
		echo "No data....\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
			return false;
		}
	
	
	
	
	echo "$c items inserted to PostgreSQL\n";
	$MAIN_ARRAY=array();
	return true;
	
}




function BUILD_REPORT($md5){
	build_progress("{building_query}",5);
	$unix=new unix();
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$mintime=strtotime("2008-01-01 00:00:00");$params["TO"]=intval($params["TO"]);$params["FROM"]=abs(intval($params["FROM"]));
	if($params["FROM"]<$mintime){$params["FROM"]=strtotime(date("Y-m-d 00:00:00"));}
	$params["TO"]=intval($params["TO"]);if($params["TO"]<$mintime){$params["TO"]=time();}
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
		build_progress("reports_cache: PostGreSQL {failed}",110);
		return;
	}
	
	build_progress("{success}",100);
	
	
	

}
