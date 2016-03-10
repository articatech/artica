<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dnsmasq.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	
	if(posix_getuid()<>0){
		$user=new usersMenus();
		if($user->AsDansGuardianAdministrator==false){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
			die();exit();
		}
	}
	
	if(isset($_GET["popup"])){tabs();exit;}
	if(isset($_GET["status"])){popup();exit;}
	if(isset($_POST["sig"])){save();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	
	$q=new postgres_sql();
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT description FROM suricata_sig WHERE signature='{$_GET["sig"]}'"));
	
	$title=$tpl->_ENGINE_parse_body("{signature}::{$_GET["sig"]} {$ligne["description"]}");
	$html="YahooWin('900','$page?popup=yes&sig={$_GET["sig"]}','$title')";
	echo $html;
	
}
function tabs(){
	$tpl=new templates();
	$array["status"]='{status}';
	$array["events"]='{events}';
	$array["firewall"]='{firewall} {rules}';
	$page=CurrentPageName();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){

		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.rule-zoom.events.php?sig={$_GET["sig"]}\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.rules.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="signatures"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.signatures.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="firewall"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.rule-zoom.firewall.php?sig={$_GET["sig"]}\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&sig={$_GET["sig"]}\"><span style='font-size:22px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "suricata-tabs-zoom");
}
	
function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='{$_GET["sig"]}'"));
	
	$ligne2=pg_fetch_assoc($q->QUERY_SQL("SELECT SUM(xcount) as tcount FROM suricata_events WHERE signature='{$_GET["sig"]}'"));
	
	
	$sum=FormatNumber($ligne2["tcount"]);
	
	$t=time();
	
	$html="<div style='font-size:30px;margin-bottom:8px;'>{signature} {ID} <strong>{$_GET["sig"]}</strong></div>
	<div style='font-size:18px;margin-bottom:30px;border-top:1px solid #CCCCCC;padding-top:8px'><i>{$ligne["description"]}</i></div>
	<div style='width:98%' class=form>
	
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{events}:</td>
			<td style='font-size:22px'>$sum</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{enabled}:</td>
			<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>".texttooltip("{firewall}","{suricata_firewall}").":</td>
			<td>". Field_checkbox_design("firewall-$t",1,$ligne["firewall"])."</td>
		</tr>					
					
					
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",30)."</td>
	</table>
	</div>			
	<script>
	var xSave$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#TABLE_SURICATA_EVENTS').flexReload();
		YahooWinHide();
		Loadjs('suricata.progress.php');
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
		var enabled=0;
		var firewall=0;
		if(document.getElementById('enabled-$t').checked){enabled=1;}
		if(document.getElementById('firewall-$t').checked){firewall=1;}
		XHR.appendData('enabled',enabled);
		XHR.appendData('firewall',firewall);
		XHR.appendData('sig','{$_GET["sig"]}');
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}	

	</script>			
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function save(){
	$q=new postgres_sql();
	$sock=new sockets();
	$sig=intval($_POST["sig"]);
	if($sig==0){echo "No signature ID\n";return;}
	$q->suricata_tables();
	$q->QUERY_SQL("UPDATE suricata_sig SET enabled='{$_POST["enabled"]}',firewall='{$_POST["firewall"]}' WHERE signature='{$_POST["sig"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($_POST["enabled"]==0){
		$q->QUERY_SQL("DELETE FROM suricata_events WHERE signature='{$_POST["sig"]}'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock->getFrameWork("suricata.php?disable-sid=yes&sig={$_POST["sig"]}");
	}else{
		$sock->getFrameWork("suricata.php?enable-sid=yes&sig={$_POST["sig"]}");
		if($_POST["firewall"]==1){
			$sock->getFrameWork("suricata.php?firewall-sid=yes&sig={$_POST["sig"]}");
		}
	}
	$sock->getFrameWork("suricata.php?restart-tail=yes");
	
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
