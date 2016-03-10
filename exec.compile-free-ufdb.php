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

GetIndex();

function GetIndex(){
	
	$WORKING_DIR="/home/working_toulouse_databases";
	$WORKING_DOWNLOAD="$WORKING_DIR/dowloads";
	$WORKING_UPLOAD="$WORKING_DIR/uploads";
	@mkdir($WORKING_DOWNLOAD,0755,true);
	@mkdir($WORKING_UPLOAD,0755,true);
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$catz=new mysql_catz();
	$tmpfile=$unix->FILE_TEMP();
	$tmpdir=$unix->TEMP_DIR();
	$rm=$unix->find_program("rm");
	$mainuri="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$EXEC_NICE=$unix->EXEC_NICE();
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$curl=new ccurl("$mainuri/MD5SUM.LST");
	if(!$curl->GetFile($tmpfile)){
		echo "Failed to download MD5SUM.LST\n";
		return;
	}
	
	$tr=explode("\n",@file_get_contents($tmpfile));
	while (list ($index, $line) = each ($tr) ){
		if(preg_match("#(.+?)\s+(.+)#", $line,$re)){
			$filename=trim($re[1]);
			$md5=trim($re[2]);
			$array[$md5]=$filename;
		}
		
	}
	
	@unlink($tmpfile);
	
	$q=new mysql_squid_builder();
	$TLSE_CONVERTION=TLSE_CONVERTION();
	$FINAL_ARRAY=array();
	while (list ($filename, $md5) = each ($array) ){
		$category=str_replace(".tar.gz", "", $filename);
		if(isset($TLSE_CONVERTION[$category])){
			$FINAL_ARRAY[$filename]=$md5;
		}
	}
	
	$UPDATED=0;
	$MAIN_ARRAY=unserialize(base64_decode(@file_get_contents("$WORKING_UPLOAD/index.txt")));
	while (list ($filename, $md5) = each ($FINAL_ARRAY) ){
		$TargetFile="$WORKING_DOWNLOAD/$filename";
		$categoryname=str_replace(".tar.gz", "", $filename);
		if($categoryname=="adult"){continue;}
		if($categoryname=="aggressive"){continue;}
		if($categoryname=="agressif"){continue;}
		if($categoryname=="redirector"){continue;}
		if($categoryname=="ads"){continue;}
		if($categoryname=="drogue"){continue;}
		$MyStoreMd5=md5_file($TargetFile);
		if($MyStoreMd5==$md5){echo "Skipping $filename\n";continue;}
			echo "Downloading $filename\n";
			$curl=new ccurl("$mainuri/$filename");
			$tmpfile=$unix->FILE_TEMP();
			if(!$curl->GetFile($tmpfile)){
				echo "Failed $curl->error\n";
				@unlink($tmpfile);
				continue;
			}
			
			$md5_tmp=md5_file($tmpfile);
			if($md5_tmp<>$md5){
				echo "Failed Corrupted file\n";
				@unlink($tmpfile);
				continue;
			}
			
			if(is_file($TargetFile)){@unlink($TargetFile);}
			if(!@copy($tmpfile, $TargetFile)){
				echo "Failed Copy file\n";
				@unlink($tmpfile);
				@unlink($TargetFile);
				continue;
			}
			@unlink($tmpfile);
			$MyStoreMd5=md5_file($TargetFile);
			if($MyStoreMd5<>$md5){
				echo "Failed MD5 file\n";
				@unlink($TargetFile);
				continue;
			}
			
			@mkdir("$WORKING_DIR/$categoryname",0755,true);
			echo "Extracting $TargetFile\n";
			$cmd="$tar xvf $TargetFile -C $WORKING_DIR/$categoryname/";
			echo $cmd."\n";
			system($cmd);
			$SOURCE_DIR=find_sources("$WORKING_DIR/$categoryname");
			if(!is_file("$SOURCE_DIR/domains")){
				echo "Failed $SOURCE_DIR/domains no such file\n";
				@unlink($TargetFile);
				continue;
			}
			
			$COUNT_OF_DOMAINS=$unix->COUNT_LINES_OF_FILE("$SOURCE_DIR/domains");
			echo "$categoryname $COUNT_OF_DOMAINS domains\n";
			if($COUNT_OF_DOMAINS==0){
				shell_exec("$rm -rf $WORKING_DIR/$categoryname");
				@unlink($TargetFile);
				continue;
			}
			
			if(is_file("$SOURCE_DIR/domains.ufdb")){@unlink("$SOURCE_DIR/domains.ufdb");}
			
			if(!is_file("$SOURCE_DIR/urls")){@touch("$SOURCE_DIR/urls");}
			
			$u=" -u $SOURCE_DIR/urls";
			$d=" -d $SOURCE_DIR/domains";
			$cmd="$EXEC_NICE$ufdbGenTable -n -q -W -t $categoryname$d$u";
			echo $cmd."\n";
			shell_exec($cmd);
			if(!is_file("$SOURCE_DIR/domains.ufdb")){
				echo "Failed to compile $categoryname\n";
				@unlink($TargetFile);
				continue;
			}
			
			$MD5SRC=md5_file("$SOURCE_DIR/domains.ufdb");
			if(is_file("$WORKING_UPLOAD/$categoryname.gz")){@unlink("$WORKING_UPLOAD/$categoryname.gz");}
			$unix->compress("$SOURCE_DIR/domains.ufdb", "$WORKING_UPLOAD/$categoryname.gz");
			$MD5GZ=md5_file("$WORKING_UPLOAD/$categoryname.gz");
			
			$UPDATED++;
			$NOTIFICATIONS[]="$categoryname updated with  $COUNT_OF_DOMAINS domains";
			$MAIN_ARRAY[$categoryname]["ROWS"]=$COUNT_OF_DOMAINS;
			$MAIN_ARRAY[$categoryname]["MD5SRC"]=$MD5SRC;
			$MAIN_ARRAY[$categoryname]["MD5GZ"]=$MD5GZ;
			$MAIN_ARRAY[$categoryname]["TIME"]=time();
			$MAIN_ARRAY[$categoryname]["SIZE"]=@filesize("$WORKING_UPLOAD/$categoryname.gz");
			@file_put_contents("$WORKING_UPLOAD/index.txt", base64_encode(serialize($MAIN_ARRAY)));
			
		
	}
	
	if($UPDATED>0){
		PushToRepo_alls();
		sendEmail("$UPDATED Toulouse Unversity databases uploaded.",@implode("\n", $NOTIFICATIONS));
	}
	
	
	
}

function find_sources($sourcedir){
	
	if(is_file("$sourcedir/domains")){return $sourcedir;}
	
	$unix=new unix();
	$dirs=$unix->dirdir($sourcedir);
	while (list ($dirname, $md5) = each ($dirs) ){
		if(!is_file("$dirname/domains")){
			echo "$dirname/domains no such file\n";
			continue;
		}
		return $dirname;
		
	}
	
}

function PushToRepo_alls(){
	
	$WORKING_DIR="/home/working_toulouse_databases";
	$WORKING_DOWNLOAD="$WORKING_DIR/dowloads";
	$OUTPUTDIR="$WORKING_DIR/uploads";
	
	
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
	$uri="ftp://mirror.articatech.net/www.artica.fr/WebfilterFree";
	$size=round(filesize($filepath)/1024);
	$ftpass5=$unix->shellEscapeChars($ftpass5);
	echo "Push $filepath ( $size KB ) to $uri\n$curl -T $filepath $uri/ --user $ftpass5\n";
	shell_exec("$curl -T $filepath $uri/ --user $ftpass5");
}


FUNCTION TLSE_CONVERTION(){
	$f["agressif"]="violence";
	$f["agressive"]="violence";
	$f["audio-video"]="audio-video";
	$f["youtube"]="youtube";
	$f["celebrity"]="celebrity";
	$f["cleaning"]="cleaning";
	$f["dating"]="dating";
	$f["filehosting"]="filehosting";
	$f["gambling"]="gamble";
	$f["hacking"]="hacking";
	$f["liste_bu"]="liste_bu";
	$f["manga"]="manga";
	$f["mobile-phone"]="mobile-phone";
	$f["press"]="news";
	$f["radio"]="webradio";
	$f["translation"]="translators";
	$f["bitcoin"]="paytosurf";
	$f["violence"]="violence";
	$f["drugs"]="drugs";
	$f["redirector"]="proxy";
	$f["sexual_education"]="sexual_education";
	$f["sports"]="recreation/sports";
	$f["tricheur"]="tricheur";
	$f["webmail"]="webmail";
	$f["adult"]="porn";
	$f["arjel"]="arjel";
	$f["bank"]="finance/banking";
	$f["chat"]="chat";
	$f["cooking"]="hobby/cooking";
	$f["drogue"]="drugs";
	$f["financial"]="financial";
	$f["games"]="games";
	$f["jobsearch"]="jobsearch";
	$f["marketingware"]="marketingware";
	$f["phishing"]="phishing";
	$f["remote-control"]="remote-control";
	$f["shopping"]="shopping";
	$f["strict_redirector"]="redirector";
	$f["astrology"]="astrology";
	$f["blog"]="blog";
	$f["child"]="children";
	$f["dangerous_material"]="dangerous_material";
	$f["forums"]="forums";
	$f["lingerie"]="sex/lingerie";
	$f["malware"]="malware";
	$f["mixed_adult"]="mixed_adult";
	$f["publicite"]="publicite";
	$f["reaffected"]="reaffected";
	$f["sect"]="sect";
	$f["social_networks"]="socialnet";
	$f["strong_redirector"]="redirector";
	$f["warez"]="warez";
	$f["verisign"]="sslsites";
	$f["ads"]="publicite";
	$f["porn"]="porn";
	$f["aggressive"]="violence";
	$f["download"]="downloads";
	$f["proxy"]="proxy";
	return $f;
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



