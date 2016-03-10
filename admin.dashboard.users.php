<?php
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');

if(isset($_GET["BACKUP_EMAIL_BEHAVIOR_DASHBOARD"])){BACKUP_EMAIL_BEHAVIOR_DASHBOARD();exit;}
if(isset($_GET["QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD"])){QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD();exit;}







function QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD(){
	$tpl=new templates();
	$ou=$_SESSION["ou"];
	@mkdir("/usr/share/artica-postfix/ressources/web/cache_$ou",0755,true);
	$filebackup="/usr/share/artica-postfix/ressources/web/cache_$ou/QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD";
	
	
	if(file_time_min_Web($filebackup)>30){
		$q=new postgres_sql();
		$ldap=new clladp();
		$domains=$ldap->hash_get_domains_ou($ou);
	
		while (list ($domain,$MAIN) = each ($domains) ){
			$domain=trim(strtolower($domain));
			if($domain==null){continue;}
			$FDOMS[]="domainto='$domain'";
			$FDOMS2[]="domainfrom='$domain'";
		}
		$imploded1=@implode(" OR ", $FDOMS);
		$imploded2=@implode(" OR ", $FDOMS2);
		$sql="select count(*) as tcount, SUM(size) as size FROM quarmsg WHERE ($imploded1) OR ($imploded2)";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		@unlink($filebackup);
		@file_put_contents($filebackup, serialize($ligne));
	}
	
	$ligne=unserialize(@file_get_contents($filebackup));
	$tcount=$ligne["tcount"];
	$size=$ligne["size"];
	
	if($tcount==0){
		echo $tpl->_ENGINE_parse_body("0 {message}");
		return;
	}
	$size=FormatBytes($size/1024);
	$tcount=FormatNumber($tcount);
	echo $tpl->_ENGINE_parse_body("<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoQuarantineMails()\"
			style='text-decoration:underline'>$tcount {messages}</a> ($size)");
		
	
}


function BACKUP_EMAIL_BEHAVIOR_DASHBOARD(){
	
	
	$tpl=new templates();
	$ou=$_SESSION["ou"];
	@mkdir("/usr/share/artica-postfix/ressources/web/cache_$ou",0755,true);
	$filebackup="/usr/share/artica-postfix/ressources/web/cache_$ou/BACKUP_EMAIL_BEHAVIOR_DASHBOARD";
	
	
	if(file_time_min_Web($filebackup)>30){
		$q=new postgres_sql();
		$ldap=new clladp();
		$domains=$ldap->hash_get_domains_ou($ou);
		
		while (list ($domain,$MAIN) = each ($domains) ){
			$domain=trim(strtolower($domain));
			if($domain==null){continue;}
			$FDOMS[]="domainto='$domain'";
			$FDOMS2[]="domainfrom='$domain'";
		}
		$imploded1=@implode(" OR ", $FDOMS);
		$imploded2=@implode(" OR ", $FDOMS2);
		$sql="select count(*) as tcount, SUM(size) as size FROM backupmsg WHERE ($imploded1) OR ($imploded2)";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		@unlink($filebackup);
		@file_put_contents($filebackup, serialize($ligne));
	}
	
	$ligne=unserialize(@file_get_contents($filebackup));
	$tcount=$ligne["tcount"];
	$size=$ligne["size"];
	
	if($tcount==0){
		echo $tpl->_ENGINE_parse_body("0 {message}");
		return;
	}
	$size=FormatBytes($size/1024);
	$tcount=FormatNumber($tcount);
	echo $tpl->_ENGINE_parse_body("<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoBackupMails()\"
		style='text-decoration:underline'>$tcount {messages}</a> ($size)");
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}