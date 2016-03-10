<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

tabs();
function tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$isProxyAll=$artica_meta->isProxyAll();
	if($isProxyAll>0){
		$array["proxys"]="Proxys ($isProxyAll)";
		
	}
	
	$array["hosts"]='{hosts}';
	$array["groups"]='{groups2}';
	if($isProxyAll>0){
		if($artica_meta->COUNT_ROWS("hotspot_members")>0){
			$array["hotspot_members"]='{hotspot_members}';
		}
	}
	
	if($isProxyAll>0){
		$array["categories"]='{your_categories}';
		$array["acls"]='{acls}';
		
	}
	
	$array["server-params"]="{main_parameters}";
	//$array["uploads"]='{uploads}';
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="proxys"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.proxys.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="hosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.hosts.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="categories"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.proxys.categories.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="groups"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.groups.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="server-params"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.php?parameters=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="hotspot_members"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.hotspot_members.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="uploads"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.uploads.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="acls"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.proxy.acls.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
				
		}		
		

		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:22px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "meta-start",1490);

}
