<?php

	if(isset($_GET["verbose"])){
		$GLOBALS["VERBOSE"]=true;
		$GLOBALS["DEBUG_MEM"]=true;
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',null);
		ini_set('error_append_string',null);
	}

	if($GLOBALS["VERBOSE"]){echo "<H1>DEBUG</H1>";}

    include_once(dirname(__FILE__).'/ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.artica.inc');
	include_once(dirname(__FILE__).'/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__).'/ressources/class.squid.inc');
	include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	//header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
		
	}
	
	
page();


function page(){
	$sock=new sockets();
	$ArticaTechNetInfluxRepo=unserialize(base64_decode($sock->GET_INFO("ArticaTechNetHaProxyRepo")));
	$tpl=new templates();
	$version=$sock->GET_INFO("HaproxyVersion");
	$manual_update=$tpl->javascript_parse_text("{manual_update}");
	

	$html="
			
	<div style='font-size:30px;margin-bottom:30px'>{available_versions} &nbsp;|&nbsp; {current}:&nbsp;$version
	&nbsp;|&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('update.upload.php')\"
	style='text-decoration:underline'>$manual_update</a>
	&nbsp;|&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('influx.refresh.update.php')\"
	style='text-decoration:underline'>{refresh}</a>
	
	
	</div>
	<table style='width:100%'>
	<tr>
	<th style='font-size:22px'>{version}</th>
	<th style='font-size:22px'>{OS}</th>
	<th style='font-size:22px'>{filename}</th>
	<th style='font-size:22px'>{filesize}</th>
	<th style='font-size:22px'>&nbsp;</tf>
	</tr>				
	";
	$color=null;
	
	
	
	while (list ($key, $array) = each ($ArticaTechNetInfluxRepo) ){
		while (list ($OS, $MAIN) = each ($array) ){
		
		$URL=$MAIN["URL"];
		$VERSION=$MAIN["VERSION"];
		$FILESIZE=$MAIN["FILESIZE"];
		$FILENAME=$MAIN["FILENAME"];
		$FILESIZE=FormatBytes($FILESIZE/1024);
		
		$button=button("{update2}","Loadjs('haproxy.update.progress.php?key=$key&OS=$OS&filename=$FILENAME');",32);
		
		
		if($color==null){$color="#F2F0F1";}else{$color=null;}
		$html=$html."
		<tr style='background-color:$color;height:80px'>
			<td style='font-size:28px;padding-left:10px'><center>$VERSION</center></td>
			<td style='font-size:28px;padding-left:10px'><center>$OS</center></td>
			<td style='font-size:28px;padding-left:10px;text-align:right'><a href=\"$URL\" target=_new style='text-decoration:underline'>$FILENAME</a></td>	
			<td style='font-size:28px;padding-left:10px;text-align:right'>$FILESIZE</td>	
			<td style='font-size:28px;padding-left:10px'><center>$button</center></td>	
		</tr>
		";
		
	}
	}
	$html=$html."</table>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}
