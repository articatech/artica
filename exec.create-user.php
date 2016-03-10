<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
$GLOBALS["WAIT"]=false;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.pure-ftpd.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/charts.php");
include_once(dirname(__FILE__)."/ressources/class.mimedefang.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ini.inc");

if($argv[1]=="--create"){create_user($argv[2]);die();}
if($argv[1]=="--progress"){create_user_from_mysql();die();}


function create_user_from_mysql(){
	$q=new mysql();
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$GLOBALS["WAIT"]=true;
	
	build_progress("{start}",10);
	$results=$q->QUERY_SQL("SELECT * FROM CreateUserQueue","artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress("MySQL error",110);
		return;
	}
	
	@mkdir("/usr/share/artica-postfix/ressources/logs/web/create-users",0755,true);
	echo mysql_num_rows($results)." member(s) to create...\n";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=$ligne["zMD5"];
		$content=$ligne["content"];
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/create-users/$zMD5", $content);
		if(create_user($zMD5)){
			build_progress("{removing_order}",95);
			$q->QUERY_SQL("DELETE FROM `CreateUserQueue` WHERE `zMD5`='$zMD5'","artica_backup");
		}else{
			$q->QUERY_SQL("DELETE FROM `CreateUserQueue` WHERE `zMD5`='$zMD5'","artica_backup");
			build_progress("{failed}",110);
			return;
		}
	}
	
	build_progress("{done}",100);
}

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/create-user.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	if($GLOBALS["WAIT"]){usleep(800);}
}


function create_user($filename){
	$tpl=new templates();
	$unix=new unix();
	$nohup=null;
	$path="/usr/share/artica-postfix/ressources/logs/web/create-users/$filename";
	echo "Path:$path\n";
	build_progress("Open $filename",10);
	
	if(!is_file($path)){
		echo "$path no such file...\n";
		return false;	
	}
	
	$MAIN=unserialize(base64_decode(@file_get_contents($path)));

	
	build_progress("Create new member {$MAIN["login"]}",15);
	
	$users=new user($MAIN["login"]);
	if($users->password<>null){
		echo "User already exists {$MAIN["login"]}\n";
		build_progress("{account_already_exists}",110);
		@unlink($path);
		return;
	}
	$ou=$MAIN["ou"];
	$password=url_decode_special_tool($MAIN["password"]);
	$MAIN["firstname"]=url_decode_special_tool($MAIN["firstname"]);
	$MAIN["lastname"]=url_decode_special_tool($MAIN["lastname"]);
	
	build_progress("{$MAIN["firstname"]} {$MAIN["lastname"]}",20);
	 
	 
	if(trim($MAIN["internet_domain"])==null){$MAIN["internet_domain"]="localhost.localdomain";}
	echo "Add new user {$MAIN["login"]} {$MAIN["ou"]} {$MAIN["gpid"]}\n";
	$users->ou=$MAIN["ou"];
	$users->password=url_decode_special_tool($MAIN["password"]);
	$users->mail="{$MAIN["email"]}@{$MAIN["internet_domain"]}";
	$users->DisplayName="{$MAIN["firstname"]} {$MAIN["lastname"]}";
	$users->givenName=$MAIN["firstname"];
    $users->sn=$MAIN["lastname"];
    $users->group_id=$MAIN["gpid"];
	$users->homeDirectory="/home/{$MAIN["login"]}";
	      
   
	      
	      
if(is_numeric($MAIN["gpid"])){
	$gp=new groups($MAIN["gpid"]);
	echo "privileges: {$MAIN["gpid"]} -> AsComplexPassword = \"{$gp->Privileges_array["AsComplexPassword"]}\"\n";
	if($gp->Privileges_array["AsComplexPassword"]=="yes"){
		$ldap=new clladp();
		$hash=$ldap->OUDatas($ou);
		$privs=$ldap->_ParsePrivieleges($hash["ArticaGroupPrivileges"],array(),true);
		$policiespwd=unserialize(base64_decode($privs["PasswdPolicy"]));
		if(is_array($policiespwd)){
			$priv=new privileges();
		    	if(!$priv->PolicyPassword($password,$policiespwd)){
		    	build_progress("Need complex password",110);
		     	echo "Need complex password";@unlink($path);return;
		     }
		  }
	 }
}

build_progress("{$MAIN["firstname"]} {$MAIN["lastname"]} {save}",25);

if(!$users->add_user()){
	echo $users->error."\n".$users->ldap_error;
	build_progress("{failed}",110);
	@unlink($path);
	return;
}

@mkdir("$users->homeDirectory");
@chown("$users->homeDirectory",$users->uid);


if($MAIN["ByZarafa"]=="yes"){
	$terminated=" >/dev/null";
	$zarafa_admin=$unix->find_program("zarafa-admin");
	if(!$GLOBALS["WAIT"]){
		$nohup=$unix->find_program("nohup");
		$terminated=null;
	}

	if(isset($MAIN["ZARAFA_LANG"])){
		$users->SaveZarafaMbxLang($MAIN["ZARAFA_LANG"]);
		$langcmd=" --lang {$MAIN["ZARAFA_LANG"]} ";
	}
	$ldap=new clladp();
	$dn="ou={$MAIN["ou"]},dc=organizations,$ldap->suffix";
	$upd["objectClass"]="zarafa-company";
	$upd["cn"]=$MAIN["ou"];
	if(!$ldap->Ldap_add_mod("$dn",$upd)){
		echo $ldap->ldap_last_error; 
		build_progress("{failed} OpenLDAP Error",110);
		@unlink($path);
		return; 
	}
	build_progress("{create_store} {language}: {$MAIN["ZARAFA_LANG"]}",30);
	$cmd="$nohup $zarafa_admin $langcmd--create-store {$MAIN["login"]} >/dev/null 2>&1 &";
	system(trim($cmd));
	
	if(!$GLOBALS["WAIT"]){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?zarafa-hash=yes&rebuild=yes");
		return;
	}
	
	@unlink("/usr/share/artica-postfix/ressources/databases/ZARAFA_DB_STATUS.db");
	@unlink("/etc/artica-postfix/zarafa-export.db");
	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.zarafa.build.stores.php --export-hash";
	build_progress("{export_stores_data}",35);
	echo "$cmd\n";
	system($cmd);
	
	
}
echo "Remove $path\n";
@unlink($path);
return true;
}


