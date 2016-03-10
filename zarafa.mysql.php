<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.mysql-server.inc');
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}

	if(isset($_POST["EnableZarafaTuning"])){Save();exit;}
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$EnableZarafaTuning=$sock->GET_INFO("EnableZarafaTuning");
	if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
	$ZarafTuningParameters=unserialize(base64_decode($sock->GET_INFO("ZarafaTuningParameters")));
	$zarafa_innodb_buffer_pool_size=$ZarafTuningParameters["zarafa_innodb_buffer_pool_size"];
	$zarafa_query_cache_size=$ZarafTuningParameters["zarafa_query_cache_size"];
	$zarafa_innodb_log_file_size=$ZarafTuningParameters["zarafa_innodb_log_file_size"];
	$zarafa_innodb_log_buffer_size=$ZarafTuningParameters["zarafa_innodb_log_buffer_size"];
	$zarafa_max_allowed_packet=$ZarafTuningParameters["zarafa_max_allowed_packet"];
	$zarafa_max_connections=$ZarafTuningParameters["zarafa_max_connections"];
	
	$memory=$users->MEM_TOTAL_INSTALLEE/1000;
	
	
	if(!is_numeric($zarafa_max_connections)){$zarafa_max_connections=150;}
	if(!is_numeric($zarafa_innodb_buffer_pool_size)){$zarafa_innodb_buffer_pool_size=round($memory/2.8);}
	if(!is_numeric($zarafa_innodb_log_file_size)){$zarafa_innodb_log_file_size=round($zarafa_innodb_buffer_pool_size*0.25);}
	if(!is_numeric($zarafa_innodb_log_buffer_size)){$zarafa_innodb_log_buffer_size=32;}
	if(!is_numeric($zarafa_max_allowed_packet)){$zarafa_max_allowed_packet=16;}
	if(!is_numeric($zarafa_query_cache_size)){$zarafa_query_cache_size=8;}
	if($zarafa_innodb_log_file_size>2000){$zarafa_innodb_log_file_size=2000;}
	$mysql=new mysqlserver();
	$VARIABLES=$mysql->SHOW_VARIABLES();
	
	
	
	$read_buffer_size=$ZarafTuningParameters["read_buffer_size"];
	if(!is_numeric($read_buffer_size)){$read_buffer_size=($VARIABLES["read_buffer_size"]/1024)/1000;}
	
	$read_rnd_buffer_size=$ZarafTuningParameters["read_rnd_buffer_size"];
	if(!is_numeric($read_rnd_buffer_size)){$read_rnd_buffer_size=($VARIABLES["read_rnd_buffer_size"]/1024)/1000;}
	
	$sort_buffer_size=$ZarafTuningParameters["sort_buffer_size"];
	if(!is_numeric($sort_buffer_size)){$sort_buffer_size=($VARIABLES["sort_buffer_size"]/1024)/1000;}
	
	$thread_stack=$ZarafTuningParameters["thread_stack"];
	if(!is_numeric($thread_stack)){$thread_stack=($VARIABLES["thread_stack"]/1024)/1000;}
	
	
	
	$join_buffer_size=$ZarafTuningParameters["join_buffer_size"];
	if(!is_numeric($join_buffer_size)){$join_buffer_size=($VARIABLES["join_buffer_size"]/1024)/1000;}
	
	$key_buffer_size=$ZarafTuningParameters["key_buffer_size"];
	if(!is_numeric($key_buffer_size)){$key_buffer_size=($VARIABLES["key_buffer_size"]/1024)/1000;}
	
	$max_tmp_table_size=$ZarafTuningParameters["max_tmp_table_size"];
	if(!is_numeric($max_tmp_table_size)){$max_tmp_table_size=($VARIABLES["max_tmp_table_size"]/1024)/1000;}
	
	
	$query_cache_size=$ZarafTuningParameters["query_cache_size"];
	if(!is_numeric($query_cache_size)){$query_cache_size=($VARIABLES["query_cache_size"]/1024)/1000;}
	


	
	
	$html="
	<div class=explain id='zarafa_mysql_tuning_text' style='font-size:18px'>{zarafa_mysql_tuning_text}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tR>
		<td class=legend style='font-size:22px'>{enable_tuning_mysql_server}:</td>
		<td>". Field_checkbox_design("EnableZarafaTuning", 1,$EnableZarafaTuning,"EnableZarafaTuningCheck()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=2><div style='font-size:30px'>{threads}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{read_buffer_size}:</td>
		<td style='font-size:22px'>". Field_text("read_buffer_size",$read_buffer_size,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:22px'>{read_rnd_buffer_size}:</td>
		<td style='font-size:22px'>". Field_text("read_rnd_buffer_size",$read_rnd_buffer_size,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>	
		<td class=legend style='font-size:22px'>{sort_buffer_size}:</td>
		<td style='font-size:22px'>". Field_text("sort_buffer_size",$sort_buffer_size,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>	
		
	<tr>
		<td class=legend style='font-size:22px'>thread_stack:</td>
		<td style='font-size:22px'>". Field_text("thread_stack",$thread_stack,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>
		<td colspan=2><hr></td>
	</tr>	
	<tr>
		<td colspan=2><div style='font-size:30px'>{server}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{key_buffer_size}:</td>
		<td style='font-size:22px'>". Field_text("key_buffer_size",$key_buffer_size,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>max_tmp_table_size:</td>
		<td style='font-size:22px'>". Field_text("max_tmp_table_size",$max_tmp_table_size,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{query_cache_size}:</td>
		<td style='font-size:22px'>". Field_text("query_cache_size",$query_cache_size,"font-size:22px;width:110px;padding:3px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{max_allowed_packet}:</td>
		<td style='font-size:22px'>". Field_text("zarafa_max_allowed_packet",$zarafa_max_allowed_packet,"font-size:22px;width:90px")."&nbsp;M</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{max_connections}:</td>
		<td style='font-size:22px'>". Field_text("zarafa_max_connections",$zarafa_max_connections,"font-size:22px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>							
	<tr>
		<td colspan=2><hr></td>
	</tr>	
	<tr>
		<td colspan=2><div style='font-size:30px'>INNODB:</td>
	</tr>		
				
<tr>
		<td class=legend style='font-size:22px'>{innodb_buffer_pool_size}:</td>
		<td style='font-size:22px'>". Field_text("zarafa_innodb_buffer_pool_size",$zarafa_innodb_buffer_pool_size,"font-size:22px;width:90px")."&nbsp;M</td>
		<td width=1%>". help_icon("{zarafa_innodb_buffer_pool_size}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{query_cache_size}:</td>
		<td style='font-size:22px'>". Field_text("zarafa_query_cache_size",$zarafa_query_cache_size,"font-size:22px;width:90px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{innodb_log_file_size}:</td>
		<td style='font-size:22px'>". Field_text("zarafa_innodb_log_file_size",$zarafa_innodb_log_file_size,"font-size:22px;width:90px")."&nbsp;M</td>
		<td width=1%>". help_icon("{zarafa_innodb_log_file_size}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{innodb_log_buffer_size}:</td>
		<td style='font-size:22px'>". Field_text("zarafa_innodb_log_buffer_size",$zarafa_innodb_log_buffer_size,"font-size:22px;width:90px")."&nbsp;M</td>
		<td width=1%>". help_icon("{zarafa_innodb_log_buffer_size}")."</td>
		
	</tr>	

	<tr>
		<td colspan=3 align=right><hr>". button("{apply}","ZarafaTunIngApply()",30)."</td>
	</tr>	
</table>
</div>
<script>
	function EnableZarafaTuningCheck(){
		document.getElementById('zarafa_innodb_buffer_pool_size').disabled=true;
		document.getElementById('zarafa_query_cache_size').disabled=true;
		document.getElementById('zarafa_innodb_log_file_size').disabled=true;
		document.getElementById('zarafa_innodb_log_buffer_size').disabled=true;
		document.getElementById('zarafa_max_allowed_packet').disabled=true;
		document.getElementById('zarafa_max_connections').disabled=true;
		
		document.getElementById('read_buffer_size').disabled=true;
		document.getElementById('read_rnd_buffer_size').disabled=true;
		document.getElementById('sort_buffer_size').disabled=true;
		document.getElementById('thread_stack').disabled=true;
		
		document.getElementById('key_buffer_size').disabled=true; 
		document.getElementById('max_tmp_table_size').disabled=true; 
		document.getElementById('query_cache_size').disabled=true;
		
		if(document.getElementById('EnableZarafaTuning').checked){
			document.getElementById('zarafa_innodb_buffer_pool_size').disabled=false;
			document.getElementById('zarafa_query_cache_size').disabled=false;
			document.getElementById('zarafa_innodb_log_file_size').disabled=false;
			document.getElementById('zarafa_innodb_log_buffer_size').disabled=false;
			document.getElementById('zarafa_max_allowed_packet').disabled=false;
			document.getElementById('zarafa_max_connections').disabled=false;	
			document.getElementById('read_buffer_size').disabled=false;
			document.getElementById('read_rnd_buffer_size').disabled=false;
			document.getElementById('sort_buffer_size').disabled=false;
			document.getElementById('thread_stack').disabled=false;	
			document.getElementById('key_buffer_size').disabled=false; 
			document.getElementById('max_tmp_table_size').disabled=false; 
			document.getElementById('query_cache_size').disabled=false;
		}
	
	}
	var x_ZarafaTunIngApply= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		
		}	
	
	function ZarafaTunIngApply(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableZarafaTuning').checked){
			XHR.appendData('EnableZarafaTuning',1);}else{XHR.appendData('EnableZarafaTuning',0);}
			XHR.appendData('zarafa_innodb_buffer_pool_size',document.getElementById('zarafa_innodb_buffer_pool_size').value);
			XHR.appendData('zarafa_query_cache_size',document.getElementById('zarafa_query_cache_size').value);
			XHR.appendData('zarafa_innodb_log_file_size',document.getElementById('zarafa_innodb_log_file_size').value);
			XHR.appendData('zarafa_innodb_log_buffer_size',document.getElementById('zarafa_innodb_log_buffer_size').value);
			XHR.appendData('zarafa_max_allowed_packet',document.getElementById('zarafa_max_allowed_packet').value);
			XHR.appendData('zarafa_max_connections',document.getElementById('zarafa_max_connections').value);
			XHR.appendData('key_buffer_size',document.getElementById('key_buffer_size').value); 
			XHR.appendData('max_tmp_table_size',document.getElementById('max_tmp_table_size').value); 
			XHR.appendData('query_cache_size',document.getElementById('query_cache_size').value);
			XHR.appendData('read_buffer_size',document.getElementById('read_buffer_size').value);
			XHR.appendData('read_rnd_buffer_size',document.getElementById('read_rnd_buffer_size').value);
			XHR.appendData('sort_buffer_size',document.getElementById('sort_buffer_size').value);
			XHR.appendData('thread_stack',document.getElementById('thread_stack').value);
			XHR.sendAndLoad('$page', 'POST',x_ZarafaTunIngApply);
	
	}
	
EnableZarafaTuningCheck();
</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$sock=new sockets();
	$datas=base64_decode($sock->GET_INFO("ZarafaTuningParameters"));
	$ZarafTuningParametersSrcMD=md5($datas);
	$datas=serialize($_POST);
	$newparamas=md5($datas);
	if($newparamas<>$ZarafTuningParametersSrcMD){
		$sock->SET_INFO("MysqlRemoveidbLogs", 1);
	}
	
	$sock->SET_INFO("EnableZarafaTuning", $_POST["EnableZarafaTuning"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ZarafaTuningParameters");
	$sock->getFrameWork("services.php?restart-mysql=yes");
	}
	
	
	
	