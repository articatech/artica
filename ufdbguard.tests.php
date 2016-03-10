<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["www"])){test();exit;}
	js();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$width=995;
		$statusfirst=null;
		$title=$tpl->_ENGINE_parse_body("{verify_rules}");
		$start="YahooWinBrowse('700','$page?popup=yes','$title');";
		$html="$start";
		echo $html;
	
	}	
	
	
function popup() {
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$ipaddr=$_SESSION["UFDBT"]["IP"];
	$username=$_SESSION["UFDBT"]["USER"];
	$www=$_SESSION["UFDBT"]["WWW"];
	if($ipaddr==null){$ipaddr=$_SERVER["REMOTE_ADDR"];}
	if($www==null){$www="http://www.youporn.com";}
	
	$html="<div style='font-size:18px' class=explain>{ufdbguard_verify_rules_explain}</div>
	<div style='width:95%;padding:15px' class=form>
	<center>
	<div id='check-$t'></div>
	<table style='width:99%'>
		<tr>
			<td class=legend style='font-size:22px'>{request}:</td>
			<td>". Field_text("www-$t",$www,"font-size:22px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."</td>
		</tr>				
		<tr>
			<td class=legend style='font-size:22px'>{ipaddr}:</td>
			<td>". Field_text("ipaddr-$t",$ipaddr,"font-size:22px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{username}:</td>
			<td>". Field_text("user-$t",$username,"font-size:22px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."</td>
		</tr>	
				<td colspan=2 align='right'>". button("{check}", "check$t()",32)."</td>
		</tr>
	</table>
	</center>
	</div>		
	<div id='results-$t'></div>		
	<script>
	
	var xRun$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('check-$t').innerHTML='';
			if(results.length>2){
				document.getElementById('check-$t').innerHTML=results;
			}
			
		}		
	
		function Run$t(e){
			if(!checkEnter(e)){return;}
			check$t();
			
		
		}
		
		
	function check$t(){
		var XHR = new XHRConnection();
		document.getElementById('check-$t').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
		XHR.appendData('www',document.getElementById('www-$t').value);
		XHR.appendData('user',document.getElementById('user-$t').value);
		XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
		XHR.sendAndLoad('$page', 'POST',xRun$t);		
	
	}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function test(){
	$tpl=new templates();
	$www=$_GET["test"];
	$q=new mysql_squid_builder();
	$ipaddr=$_POST["ipaddr"];
	$user=$_POST["user"];
	$www=$_POST["www"];
	$_SESSION["UFDBT"]["IP"]=$ipaddr;
	$_SESSION["UFDBT"]["USER"]=$user;
	$_SESSION["UFDBT"]["WWW"]=$www;
	if($user==null){$user="-";}
	if($ipaddr==null){$ipaddr="-";}
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
	if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
	if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	
	if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
	$address=null;
	
	
	if($datas["UseRemoteUfdbguardService"]==1){
		if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
		$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";
		
	}
	
	
	if($address==null){
		if($datas["tcpsockets"]==1){
			$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";
			
		}else{
			$address="-S 127.0.0.1 -p {$datas["listen_port"]} ";
		}
	}
	if($address==null){echo "<strong style='color:#d32d2d'>Cannot determine address</strong>\n";return;}
	
	$cmdline="$address $www $ipaddr $user";
	$cmdline=urlencode(base64_encode($cmdline));
	$datas=base64_decode($sock->getFrameWork("squid.php?ufdbclient=$cmdline"));
	
	if(preg_match("#^http.*#", $www)){
		$url_www=parse_url($www);
		$url_host=$url_www["host"];
	}else{
		$url_host=$www;
	}
	
	$tpl=new templates();
	$title_pass=$tpl->_ENGINE_parse_body("{access_to_internet}");
	$redirected=$tpl->_ENGINE_parse_body("{redirected}");
	$datas=trim($datas);
	if($datas=="OK"){$datas=null;}
	
	if(trim($datas)==null){
		
		$catz=new mysql_catz();
		$category=$catz->GET_CATEGORIES($url_host);
		if($category<>null){
			$category_text=$tpl->_ENGINE_parse_body("<br>{category}: $category");
		}
		
		
		echo "
			<table style='width:100%'>
			<tr>
				<td valign='top' style='width:256px'><img src='img/shield-ok-256.png'>
				<td valign='top' style='width:99%;vertical-align:middle'>
					<div style='font-size:26px;color:#46a346'>$title_pass$category_text</div></td>
			</tr>
			</table>
			
			\n";return;}
	
	
	
	
	$HTTP_CODE="{http_status_code}: 302<br>";
	if(preg_match('#status=([0-9]+)\s+url="(.*?)"#', $datas,$re)){
		$datas=$re[2];
		$HTTP_CODE="{http_status_code}: {$re[1]}<br>";
	}
	
	$url=parse_url($datas);

	if(!is_numeric($url["port"])){$url["port"]=80;}
	
	
	echo "<table style='width:100%'>
			<tr>
				<td valign='top' style='width:256px'><img src='img/shield-red-256.png'>
				<td valign='top' style='width:99%;vertical-align:middle'>
			<div style='font-size:22px;color:#d32d2d'>";
				
	echo "$redirected: {$url["scheme"]}://{$url["host"]}:{$url["port"]}<br>";
	
	
	$queries=explode("&",$url["query"]);
	
	while (list ($num, $line) = each ($queries)){
		if(preg_match("#(.+?)=(.+)#", $line,$re)){
			$array[$re[1]]=$re[2];
		}
	}
		
	echo $tpl->_ENGINE_parse_body($HTTP_CODE);
	
	
	if($array["targetgroup"]=="none"){
		$catz=new mysql_catz();
		$category=$catz->GET_CATEGORIES($url_host);
		if($category==null){$array["targetgroup"]="{ufdb_none} - {unknown}";}else{$array["targetgroup"]="{ufdb_none} - $category";}
		
	}
	
	
	if($url["path"] == "/ufdbguardd.php"){
		if(isset($array["rule-id"])){
			$sql="SELECT * FROM webfilter_rules WHERE ID={$array["rule-id"]}";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			echo $tpl->_ENGINE_parse_body("{rulename}: {$ligne["rulename"]} - {$array["clientgroup"]}<br>");
		}
		if(isset($array["clientaddr"])){
			echo $tpl->_ENGINE_parse_body("{address}: {$array["clientaddr"]}<br>");
		}
		if(isset($array["clientuser"])){
			echo $tpl->_ENGINE_parse_body("{member}: {$array["clientuser"]}<br>");
		}	
		if(isset($array["targetgroup"])){
			echo $tpl->_ENGINE_parse_body("{category}: {$array["targetgroup"]}<br>");
		}		
	}
	
	echo "</div></td></tr></table>";
	
}