<?php
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__) . "/ressources/class.ccurl.inc");
include_once(dirname(__FILE__) . "/ressources/class.tcpip.inc");
include_once(dirname(__FILE__).  "/ressources/smtp/smtp.php");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.categorize.generic.inc');

if($argv[1]=="--push"){PushToRepo_alls();exit;}


compile();

function compile(){
$unix=new unix();
$MAIN_CACHE=unserialize(@file_get_contents("/root/UFDB_COMPILE_DATABASES"));
$q=new mysql_squid_builder();
$DB_LISTES=TransArray();
$ufdbGenTable=$unix->find_program("ufdbGenTable");


$WORKDIR="/home/artica/ufdbv10";
$OUTPUTDIR="/home/artica/ufdbv10Export";
@mkdir($OUTPUTDIR,0755,true);
$UPDATED=0;
while (list ($category_table, $category) = each ($DB_LISTES) ){
	echo "Starting Cleaning $category_table\n";
	Clean_table($category_table);
	$CountCategoryTableRows=$q->COUNT_ROWS("$category_table");
	echo "$category_table: $CountCategoryTableRows rows\n";
	
	if($CountCategoryTableRows==0){
		sendEmail("ALERT! $category_table NO ROW!");
		continue;
	}
	
	if(intval($MAIN_CACHE[$category_table]["ROWS"])==$CountCategoryTableRows){
		ToSyslog($category_table ." [SKIPPED] {$MAIN_CACHE[$category_table]["ROWS"]} == $CountCategoryTableRows");
		echo "$category_table: SKIPPED\n";
		continue;
	}
	
	
	$workingtempdir="$WORKDIR/$category_table";
	$workingtempFile="$workingtempdir/domains";
	@mkdir($workingtempdir,0777,true);
	$unix->chmod_func(0777, $workingtempdir);
	if(is_file($workingtempFile)){@unlink($workingtempFile);}
	
	$sql="SELECT pattern FROM $category_table ORDER BY pattern INTO OUTFILE '$workingtempFile' LINES TERMINATED BY '\n';";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		sendEmail("ALERT! $category_table MySQL error",$q->mysql_error);
		die();
	}
	
	@file_put_contents("$workingtempdir/urls", "\n");
	@file_put_contents("$workingtempdir/expressions", "\n");
	@unlink("$workingtempdir/domains.ufdb");
	$categoryKey=compile_databases_categoryKey($category);
	$u=" -u $workingtempdir/urls";
	$d=" -d $workingtempdir/domains";
	$cmd="$ufdbGenTable -n -q -W -t $categoryKey$d$u >/dev/null 2>&1";
	echo "[$category_table]::$category $cmd\n";
	$UPDATED++;
	$t=time();
	$resultsCMD[]=$cmd;
	ToSyslog("[FINISH]:: Compiling $category_table...");
	exec($cmd,$resultsCMD);
	if(!is_file("$workingtempdir/domains.ufdb")){
		sendEmail("ALERT! $category_table domains.ufdb no such file!");
		continue;
	}
	@mkdir("/home/artica/backuped_categories",0755);
	$unix->compress($workingtempFile,"/home/artica/backuped_categories/$category_table.gz");
	
	echo "[$category_table]::$category Compressing to $OUTPUTDIR/$category_table.gz\n";
	if(!$unix->compress("$workingtempdir/domains.ufdb", "$OUTPUTDIR/$category_table.gz")){
		sendEmail("ALERT! unable to compress $workingtempdir/domains.ufdb");
		die();
	}
	@unlink("/home/ufdbcat/$category_table/domains.ufdb");
	@copy("$workingtempdir/domains.ufdb", "/var/lib/ufdbartica/$category_table/domains.ufdb");
	
	echo "[$category_table]::$category Indexing....\n";
	
	$md5file=md5_file("$workingtempdir/domains.ufdb");
	$md5zip=md5_file("$OUTPUTDIR/$category_table.gz");
	ToSyslog("$OUTPUTDIR/$category_table.gz [UPDATED]");
	
	$UPDATED_DBS[]="$category_table ($CountCategoryTableRows)";
	
	$MAIN_CACHE[$category_table]["ROWS"]=$CountCategoryTableRows;
	$MAIN_CACHE[$category_table]["TIME"]=time();
	$MAIN_CACHE[$category_table]["MD5SRC"]=$md5file;
	$MAIN_CACHE[$category_table]["MD5GZ"]=$md5zip;
}
	ToSyslog("[FINISH]:: Building indexes {$UPDATED} updated...");
	@file_put_contents("/root/UFDB_COMPILE_DATABASES", serialize($MAIN_CACHE));
	@file_put_contents("$OUTPUTDIR/index.txt",base64_encode(serialize($MAIN_CACHE)));
	if($UPDATED>0){
		ToSyslog("[FINISH]:: PushToRepo_alls()");
		PushToRepo_alls();
		sendEmail("$UPDATED Official Webfiltering databases updated",@implode("\n", $UPDATED_DBS));
	}


}

function PushToRepo_alls(){
	$OUTPUTDIR="/home/artica/ufdbv10Export";
	$unix=new unix();
	$FILES=$unix->DirFiles($OUTPUTDIR);
	
	while (list ($filename, $category) = each ($FILES) ){
		$srcfile="$OUTPUTDIR/$filename";
		PushToRepo($srcfile);
	}
	
	
}


function PushToRepo($filepath){
	$curl="/usr/bin/curl";
	$unix=new unix();
	$ftpass5=trim(@file_get_contents("/root/ftp-password5"));
	$uri="ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS";
	$size=round(filesize($filepath)/1024);
	$ftpass5=$unix->shellEscapeChars($ftpass5);
	ToSyslog("Push $filepath ( $size KB ) to $uri\n$curl -T $filepath $uri/ --user $ftpass5");
	shell_exec("$curl -T $filepath $uri/ --user $ftpass5");
}


function compile_databases_categoryKey($category){
		$category_compile=$category;
		if(strpos($category, "other")>0){$category_compile=str_replace("/", "", $category);}
		if(preg_match("#.+?\/(.+)#", $category_compile,$re)){$category_compile=$re[1];}
		if(strlen($category_compile)>15){
			$category_compile=str_replace("recreation_","recre_",$category_compile);
			$category_compile=str_replace("automobile_","auto_",$category_compile);
			$category_compile=str_replace("finance_","fin_",$category_compile);
			if(strlen($category_compile)>15){
				$category_compile=str_replace("_", "", $category_compile);
				if(strlen($category_compile)>15){$category_compile=substr($category_compile, strlen($category_compile)-15,15);}
			}
		}
		
	return $category_compile;
}

function TransArray(){

	$trans["category_society"]="society";
	$trans["category_publicite"]="publicite";
	$trans["category_shopping"]="shopping";
	$trans["category_abortion"]="abortion";
	$trans["category_agressive"]="agressive";
	$trans["category_alcohol"]="alcohol";
	$trans["category_animals"]="animals";
	$trans["category_associations"]="associations";
	$trans["category_astrology"]="astrology";
	$trans["category_audio_video"]="audio-video";
	$trans["category_youtube"]="youtube";
	$trans["category_automobile_bikes"]="automobile/bikes";
	$trans["category_automobile_boats"]="automobile/boats";
	$trans["category_automobile_carpool"]="automobile/carpool";
	$trans["category_automobile_cars"]="automobile/cars";
	$trans["category_automobile_planes"]="automobile/planes";
	$trans["category_bicycle"]="bicycle";
	$trans["category_blog"]="blog";
	$trans["category_books"]="books";
	$trans["category_browsersplugins"]="browsersplugins";
	$trans["category_celebrity"]="celebrity";
	$trans["category_chat"]="chat";
	$trans["category_children"]="children";
	$trans["category_cleaning"]="cleaning";
	$trans["category_clothing"]="clothing";
	$trans["category_converters"]="converters";
	$trans["category_cosmetics"]="cosmetics";
	$trans["category_culture"]="culture";
	$trans["category_dangerous_material"]="dangerous_material";
	$trans["category_dating"]="dating";
	$trans["category_dictionaries"]="dictionaries";
	$trans["category_downloads"]="downloads";
	$trans["category_drugs"]="drugs";
	$trans["category_dynamic"]="dynamic";
	$trans["category_electricalapps"]="electricalapps";
	$trans["category_electronichouse"]="electronichouse";
	$trans["category_filehosting"]="filehosting";
	$trans["category_finance_banking"]="finance/banking";
	$trans["category_finance_insurance"]="finance/insurance";
	$trans["category_finance_moneylending"]="finance/moneylending";
	$trans["category_finance_other"]="finance/other";
	$trans["category_finance_realestate"]="finance/realestate";
	$trans["category_financial"]="financial";
	$trans["category_forums"]="forums";
	$trans["category_gamble"]="gamble";
	$trans["category_games"]="games";
	$trans["category_genealogy"]="genealogy";
	$trans["category_gifts"]="gifts";
	$trans["category_governments"]="governments";
	$trans["category_green"]="green";
	$trans["category_hacking"]="hacking";
	$trans["category_handicap"]="handicap";
	$trans["category_health"]="health";
	$trans["category_hobby_arts"]="hobby/arts";
	$trans["category_hobby_cooking"]="hobby/cooking";
	$trans["category_hobby_other"]="hobby/other";
	$trans["category_hobby_pets"]="hobby/pets";
	$trans["category_paytosurf"]="paytosurf";
	$trans["category_terrorism"]="terrorism";
	$trans["category_hobby_fishing"]="hobby/fishing";
	$trans["category_hospitals"]="hospitals";
	$trans["category_houseads"]="houseads";
	$trans["category_smallads"]="smallads";
	$trans["category_housing_accessories"]="housing/accessories";
	$trans["category_housing_doityourself"]="housing/doityourself";
	$trans["category_housing_builders"]="housing/builders";
	$trans["category_humanitarian"]="humanitarian";
	$trans["category_imagehosting"]="imagehosting";
	$trans["category_industry"]="industry";
	$trans["category_internal"]="internal";
	$trans["category_isp"]="isp";
	$trans["category_jobsearch"]="jobsearch";
	$trans["category_jobtraining"]="jobtraining";
	$trans["category_justice"]="justice";
	$trans["category_learning"]="learning";
	$trans["category_liste_bu"]="liste_bu";
	$trans["category_luxury"]="luxury";
	$trans["category_mailing"]="mailing";
	$trans["category_malware"]="malware";
	$trans["category_manga"]="manga";
	$trans["category_maps"]="maps";
	$trans["category_marketingware"]="marketingware";
	$trans["category_medical"]="medical";
	$trans["category_mixed_adult"]="mixed_adult";
	$trans["category_mobile_phone"]="mobile-phone";
	$trans["category_models"]="models";
	$trans["category_movies"]="movies";
	$trans["category_music"]="music";
	$trans["category_nature"]="nature";
	$trans["category_news"]="news";
		
	$trans["category_passwords"]="passwords";
	$trans["category_phishing"]="phishing";
	$trans["category_photo"]="photo";
	$trans["category_pictureslib"]="pictureslib";
	$trans["category_politic"]="politic";
	$trans["category_porn"]="porn";
	$trans["category_proxy"]="proxy";
	$trans["category_reaffected"]="reaffected";
	$trans["category_recreation_humor"]="recreation/humor";
	$trans["category_recreation_nightout"]="recreation/nightout";
	$trans["category_recreation_schools"]="recreation/schools";
	$trans["category_recreation_sports"]="recreation/sports";
	$array["category_getmarried"]="getmarried";
	$array["category_police"]="police";
	$trans["category_recreation_travel"]="recreation/travel";
	$trans["category_recreation_wellness"]="recreation/wellness";
	$trans["category_redirector"]="redirector";
	$trans["category_religion"]="religion";
	$trans["category_remote_control"]="remote-control";
		
	$trans["category_sciences"]="sciences";
	$trans["category_science_astronomy"]="science/astronomy";
	$trans["category_science_computing"]="science/computing";
	$trans["category_science_weather"]="science/weather";
	$trans["category_science_chemistry"]="science/chemistry";
	$trans["category_searchengines"]="searchengines";
	$trans["category_sect"]="sect";
	$trans["category_sexual_education"]="sexual_education";
	$trans["category_sex_lingerie"]="sex/lingerie";
	$trans["category_smallads"]="smallads";
	$trans["category_socialnet"]="socialnet";
	$trans["category_spyware"]="spyware";
	$trans["category_sslsites"]="sslsites";
	$trans["category_stockexchange"]="stockexchange";
	$trans["category_suspicious"]="suspicious";
	$trans["category_teens"]="teens";
	$trans["category_tobacco"]="tobacco";
	$trans["category_tracker"]="tracker";
	$trans["category_translators"]="translators";
	$trans["category_transport"]="transport";
	$trans["category_tricheur"]="tricheur";
	$trans["category_updatesites"]="updatesites";
	$trans["category_violence"]="violence";
	$trans["category_warez"]="warez";
	$trans["category_weapons"]="weapons";
	$trans["category_webapps"]="webapps";
	$trans["category_webmail"]="webmail";
	$trans["category_webphone"]="webphone";
	$trans["category_webplugins"]="webplugins";
	$trans["category_webradio"]="webradio";
	$trans["category_webtv"]="webtv";
	$trans["category_wine"]="wine";
	$trans["category_womanbrand"]="womanbrand";
	$trans["category_horses"]="horses";
	$trans["category_meetings"]="meetings";
	$trans["category_tattooing"]="tattooing";
	$trans["category_getmarried"]="getmarried";
	$trans["category_literature"]="literature";
	$trans["category_police"]="police";
	
		
	return $trans;

}
function Clean_table($category_table){
	$noclean["categoryuris_phishing"]=true;
	$noclean["categoryuris_malware"]=true;
	echo "Cleaning $category_table\n";
	$q=new mysql_squid_builder();
	

	if(isset($noclean[$category_table])){return;}

	$q->QUERY_SQL("DELETE FROM $category_table WHERE pattern LIKE '%?%'");
	if(!$q->ok){
		sendEmail("Error $category_table in line ".__LINE__." $q->mysql_error", "Function:".__FUNCTION__);
		ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "cloud");
		return;
	}
	$q->QUERY_SQL("DELETE FROM $category_table WHERE pattern LIKE '%/%'");
	if(!$q->ok){
		sendEmail("Error $category_table in line ".__LINE__." $q->mysql_error", "Function:".__FUNCTION__);
		ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "cloud");
		return;
	}

	$sql="DELETE FROM $category_table WHERE enabled=0";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		sendEmail("Error $category_table in line ".__LINE__." $q->mysql_error", "Function:".__FUNCTION__);
		ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "cloud");
		return;
	}



}
function sendEmail($subject,$content=null){
	$unix=new unix();

	$hostname="ks220503.kimsufi.com";
	$mailfrom="root@$hostname";
	$recipient="david@articatech.com";
	$TargetHostname="37.187.142.164";
	$params["helo"]=$hostname;
	$params["host"]=$TargetHostname;
	$params["do_debug"]=true;
	$params["debug"]=true;


	$smtp=new smtp($params);

	if(!$smtp->connect($params)){
		smtp::events("Error $smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	$random_hash = md5(date('r', time()));

	$content=str_replace("\r\n", "\n", $content);
	$content=str_replace("\n", "\r\n", $content);
	$body[]="Return-Path: <$mailfrom>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $mailfrom (robot)";
	$body[]="Subject: $subject";
	$body[]="To: $recipient";
	$body[]="";
	$body[]="";
	$body[]=$content;
	$body[]="";
	$finalbody=@implode("\r\n", $body);

	if(!$smtp->send(array(
			"from"=>"$mailfrom",
			"recipients"=>$recipient,
			"body"=>$finalbody,"headers"=>null)
	)
	){
		smtp::events("Error $smtp->error_number: Could not send to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		$smtp->quit();
		return;
	}

	smtp::events("Success sending message trough [{$TargetHostname}:25]",__FUNCTION__,__FILE__,__LINE__);
	$smtp->quit();


}
function ToSyslog($text){
	echo "$text\n";
	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


