<?php

$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.status.inc');


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["InfluxAdminEnabled"])){InfluxAdminEnabled_save();exit;}
page();


function page(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$html="
	<span id='influxdb08654648-upgrade'></span>		
	<div style='font-size:30px;margin-bottom:20px'>{incompatible_bigdata_engine}</div>
	<div style='font-size:22px' class=explain>{incompatible_bigdata_engine_explain2}</div>

	<center style='margin:30px'>". button("{upgrade}", "Loadjs('influxdb.install.progress.php?migration=yes')",42)."</center>
			
			
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}