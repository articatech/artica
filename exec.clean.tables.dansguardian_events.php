<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");






$q=new mysql();
$tables=LIST_TABLES_ARTICA_EVENTS_INVERSE("_dcurl");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table","artica_events");
}








echo "Scanning tables\n";
$LIST_TABLES_dansguardian_events=LIST_TABLES_dansguardian_events();

$q=new mysql_squid_builder();

while (list ($num, $table) = each ($LIST_TABLES_dansguardian_events) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}

echo "Scanning tables mgreyh_\n";
$tables=LIST_TABLES("mgreyh_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}

echo "Scanning tables cachehour_\n";
$tables=LIST_TABLES("cachehour_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables youtubeday_\n";
$tables=LIST_TABLES("youtubeday_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables quotamonth_\n";
$tables=LIST_TABLES("quotamonth_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables squidhour_\n";
$tables=LIST_TABLES("squidhour_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables quotaday_\n";
$tables=LIST_TABLES("quotaday_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables sizehour_\n";
$tables=LIST_TABLES("sizehour_");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}


echo "Scanning tables _gsize\n";
$tables=LIST_TABLES_REVERSE("_gsize");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _hour\n";
$tables=LIST_TABLES_REVERSE("_hour");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}

echo "Scanning tables _dcache\n";
$tables=LIST_TABLES_REVERSE("_dcache");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _gcache\n";
$tables=LIST_TABLES_REVERSE("_gcache");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _week\n";
$tables=LIST_TABLES_REVERSE("_week");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _day\n";
$tables=LIST_TABLES_REVERSE("_day");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _blocked_days\n";
$tables=LIST_TABLES_REVERSE("_blocked_days");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _catfam\n";
$tables=LIST_TABLES_REVERSE("_catfam");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}
echo "Scanning tables _cacheperfs\n";
$tables=LIST_TABLES_REVERSE("_cacheperfs");
while (list ($num, $table) = each ($tables) ){
	echo "DROP TABLE $table\n";
	$q->QUERY_SQL("DROP TABLE $table");
}




function LIST_TABLES_REVERSE($pattern){
	$q=new mysql_squid_builder();

	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%$pattern'";
	$results=$q->QUERY_SQL($sql);

	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#[0-9]+{$pattern}#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}
function LIST_TABLES($pattern){
	$q=new mysql_squid_builder();
	
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '$pattern%'";
	$results=$q->QUERY_SQL($sql);

	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#{$pattern}[0-9]+#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}

function LIST_TABLES_dansguardian_events(){
	$q=new mysql_squid_builder();
	if(isset($GLOBALS["LIST_TABLES_dansguardian_events"])){return $GLOBALS["LIST_TABLES_dansguardian_events"];}
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
	$results=$q->QUERY_SQL($sql);

	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#dansguardian_events_[0-9]+#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_dansguardian_events"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}
function LIST_TABLES_ARTICA_BACKUP($pattern){
	$q=new mysql();

	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_backup' AND table_name LIKE '$pattern%'";
	$results=$q->QUERY_SQL($sql,"artica_backup");

	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#{$pattern}[0-9]+#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}
function LIST_TABLES_ARTICA_EVENTS_INVERSE($pattern){
	$q=new mysql();

	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE '%$pattern'";
	$results=$q->QUERY_SQL($sql,"artica_backup");

	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#[0-9]+{$pattern}#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}

