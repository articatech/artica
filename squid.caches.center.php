<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		header("content-type: application/x-javascript");
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["change-cache-types-js"])){change_cache_type_js();exit;}
	if(isset($_GET["change-cache-types-popup"])){change_cache_type_popup();exit;}
	if(isset($_GET["delete-empty-js"])){delete_empty_js();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["move-item-js"])){move_items_js();exit;}
	
	if(isset($_GET["item-js"])){items_js();exit;}
	if(isset($_GET["enable-js"])){enable_js();exit;}
	if(isset($_POST["enable-item"])){enable_item();exit;}
	
	
	if(isset($_GET["item-popup"])){items_popup();exit;}
	if(isset($_GET["CacheTypeExplain"])){CacheTypeExplain();exit;}
	if(isset($_POST["cache_directory"])){items_save();exit;}
	if(isset($_POST["delete-item"])){items_delete();exit;}
	if(isset($_GET["delete-item-js"])){items_js_delete();exit;}
	if(isset($_POST["move-item"])){move_items();exit;}
	if(isset($_POST["empty-item"])){empty_item();exit;}
	if(isset($_POST["chcachetype"])){chcachetype();exit;}
	table();
	
function delete_empty_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	$ID=$_GET["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cachename FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	$title=$ligne["cachename"];
	$action_empty_cache_ask=$tpl->javascript_parse_text("{action_empty_cache_ask}");	
	$action_empty_cache_ask=str_replace("%s", $title, $action_empty_cache_ask);
	
	$t=time();
	$html="
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){ alert(results); return; }
		$('#flexRT{$_GET["t"]}').flexReload();
		Loadjs('squid.caches.center.empty.progress.php');
	}
	function Save$t(){
		if(!confirm('$action_empty_cache_ask')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('empty-item','$ID');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
		
	Save$t();
		
	";
	
	echo $html;	
}

function empty_item(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?cache-center-empty={$_POST["empty-item"]}");
	
}

function items_js_delete(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		die();
	}
	$ID=$_GET["ID"];	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cachename FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	$title=$ligne["cachename"];
	$action_remove_cache_ask=$tpl->javascript_parse_text("{action_remove_cache_ask}");
	$t=time();
$html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
	if(document.getElementById('proxy-store-caches')){
		LoadAjaxRound('proxy-store-caches','admin.dashboard.proxy.caches.php');
	}
	
}
function Save$t(){
	if(!confirm('$title: $action_remove_cache_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-item','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
			
Save$t();			
			
";

echo $html;
	
}

function enable_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	

	
	$t=time();
	header("content-type: application/x-javascript");
	$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('enable-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();
	
			";
	
			echo $html;	
	
}

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		die();
	}
	
	$t=time();
	header("content-type: application/x-javascript");
	$html="
	
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
		
Save$t();
		
	";
	
	echo $html;
	
}

function move_items(){
	$q=new mysql();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zOrder,cpu FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	
	$cpu=$ligne["cpu"];
	$CurrentOrder=$ligne["zOrder"];
	
	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}
	
	$sql="UPDATE squid_caches_center SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder' AND `cpu`='$cpu'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
	$sql="UPDATE squid_caches_center SET zOrder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM squid_caches_center WHERE `cpu`='$cpu' ORDER by zOrder","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];

		$sql="UPDATE squid_caches_center SET zOrder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}
	
	
}

function enable_item(){
	$ID=$_POST["enable-item"];
	$q=new mysql();
	$enabled=1;
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	if($ligne["enabled"]==1){$enabled=0;}
	$q->QUERY_SQL("UPDATE squid_caches_center SET `enabled`='$enabled' WHERE ID='$ID'","artica_backup");
}


function items_delete(){
	$ID=$_POST["delete-item"];
	$q=new mysql();
	$sql="UPDATE squid_caches_center SET `remove`=1 WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
}
function change_cache_type_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{change_caches_type}");
	header("content-type: application/x-javascript");
	echo "YahooWin2('750','$page?change-cache-types-popup=yes','$title',true)";
}
	
function items_js(){
	header("content-type: application/x-javascript");
	
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		header("content-type: application/x-javascript");
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{onlycorpavailable}")."');";
		die();
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$ID=$_GET["ID"];
	
	$title=$tpl->_ENGINE_parse_body("{new_cache}");
	
	$q=new mysql();
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cachename FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
		$title=$ligne["cachename"];
	}
	
	echo "YahooWin2('1010','$page?item-popup=yes&ID=$ID&t={$_GET["t"]}','$ID:$title',true)";
	
	
}

function CacheTypeExplain(){
	$t=$_GET["t"];
	$ID=intval($_GET["ID"]);
	$type=$_GET["CacheTypeExplain"];
	$EXPL["ufs"]="{cache_type_text}";
	$EXPL["aufs"]="{cache_type_text}";
	$EXPL["diskd"]="{cache_type_text}";
	$EXPL["rock"]="{SQUID_ROCK_STORE_EXPLAIN}";
	$EXPL["tmpfs"]="{SQUID_TMPFS_STORE_EXPLAIN}";
	$EXPL["Cachenull"]="{SQUID_NULL_STORE_EXPLAIN}";
	
	
	$explain=$EXPL[$type];
	$tpl=new templates();
	
	$js="
	document.getElementById('cache_dir_level2-$t').disabled=false;
	document.getElementById('cache_dir_level1-$t').disabled=false;
	document.getElementById('CPU-$t').disabled=false;
	document.getElementById('cache_directory-$t').disabled=false;
	document.getElementById('squid-cache-size-$t').disabled=false;
	";
	
	if($type=="rock"){
		$js="document.getElementById('cache_dir_level2-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('CPU-$t').disabled=true;";
	}
	
	if($type=="tmpfs"){
		$js="
		document.getElementById('cache_dir_level2-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('cache_directory-$t').disabled=true;
		
		";
	}
		
	if($type=='Cachenull'){
		$js="
		document.getElementById('cache_dir_level2-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('cache_directory-$t').disabled=true;
		document.getElementById('squid-cache-size-$t').disabled=true;
		";		
	}
		

	if($ID>0){$js=null;}
	
	echo $tpl->_ENGINE_parse_body("
	<div class=explain style='font-size:18px'>
			<strong style='font-size:18px'>$type:</strong><hr>$explain</div>")."<script>$js</script>";
	
}

function change_cache_type_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$caches_types[null]="{select}";
	$caches_types["aufs"]="aufs";
	$caches_types["diskd"]="diskd";
	$t=time();
	$sock=new sockets();
	$SquidForceCacheTypes=$sock->GET_INFO("SquidForceCacheTypes");
	$Fcaches_types=Field_array_Hash($caches_types,"cache_type-$t",$SquidForceCacheTypes,"blur()",null,0,"font-size:30px;padding:3px");
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{change_caches_type}</div>		
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
	<td class=legend style='font-size:30px' nowrap>{type}:</td>
	<td style='font-size:30px'>$Fcaches_types</td>
	</tr>
<tr>
		<td align='right' colspan=2><hr>". button("{apply}","Save$t()",40)."</td>
	</tr>
	</table>
<script>
	
var xSave$t= function (obj) {
	
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	YahooWin2Hide();
	Loadjs('squid.compile.progress.php');
	if(document.getElementById('proxy-store-caches')){
		LoadAjaxRound('proxy-store-caches','admin.dashboard.proxy.caches.php');
	}
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('chcachetype',document.getElementById('cache_type-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function chcachetype(){
	$q=new mysql();
	if($_POST["chcachetype"]==null){echo "Null!??\n";return;}
	$sock=new sockets();
	$sock->SET_INFO("SquidForceCacheTypes", $_POST["chcachetype"]);
	$q->QUERY_SQL("UPDATE squid_caches_center SET cache_type='{$_POST["chcachetype"]}'","artica_backup");
	
}
	
function items_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$max_size_explain=null;
	$ID=intval($_GET["ID"]);
	$DisableAnyCache=intval($sock->GET_INFO("DisableAnyCache"));
	$SquidForceCacheTypes=$sock->GET_INFO("SquidForceCacheTypes");
	if($SquidForceCacheTypes==null){$SquidForceCacheTypes="aufs";}
	$SquidSimpleConfig=intval($sock->GET_INFO("SquidSimpleConfig"));

	if($DisableAnyCache==1){
		FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");
		return;
	}

	$cpunumber=$users->CPU_NUMBER-1;
	if($cpunumber<1){$cpunumber=1;}
	for($i=1;$i<$cpunumber+1;$i++){
		$CPUZ[$i]="{process} $i";
	}

	$t=time();
	$bt="{add}";

	$cpu=1;
	$cachename=time();

	$squid=new squidbee();
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["minimum_object_size"],$re)){
		$minimum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["minimum_object_size"],$re)){$minimum_object_size_unit=$re[1];}
		if($minimum_object_size_unit==null){$minimum_object_size_unit="KB";}
		if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		if($minimum_object_size_unit=="MB"){$minimum_object_size=$minimum_object_size*1024;}
	}



	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size"],$re)){
		$maximum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size"],$re)){$maximum_object_size_unit=$re[1];}
		if($maximum_object_size_unit==null){$maximum_object_size_unit="KB";}
		if($maximum_object_size_unit=="KB"){
			if($maximum_object_size<4096){$maximum_object_size=4096;}
		}
		if($maximum_object_size_unit=="MB"){
			if($maximum_object_size<4){$maximum_object_size=4;}
			$maximum_object_size=$maximum_object_size*1024;
		}
	}

	$min_size=$minimum_object_size;
	$max_size=$maximum_object_size;

	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$cachename=$ligne["cachename"];
		$cache_directory=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$cache_dir_level1=$ligne["cache_dir_level1"];
		$cache_dir_level2=$ligne["cache_dir_level2"];
		$cache_type=$ligne["cache_type"];
		$enabled=$ligne["enabled"];
		$cachename=$ligne["cachename"];
		$cpu=$ligne["cpu"];
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		$bt="{apply}";
	}

	if($max_size==0){$max_size_explain="<u style='font-size:11px'>{default}: $maximum_object_size KB</u>";}

	//default
	if($cache_directory==null){$cache_directory="/home/squid/caches/cache-".time();}
	if(!is_numeric($cache_size)){$cache_size=5000;}
	if(!is_numeric($cache_dir_level1)){$cache_dir_level1=16;}
	if(!is_numeric($cache_dir_level2)){$cache_dir_level2=256;}
	if(!is_numeric($enabled)){$enabled=1;}

	if($cache_size<1){$cache_size=5000;}
	if($cache_dir_level1<16){$cache_dir_level1=16;}
	if($cache_dir_level2<64){$cache_dir_level2=64;}
	if($cache_type==null){$cache_type=$SquidForceCacheTypes;}

	$caches_types=unserialize(base64_decode($sock->getFrameWork("squid.php?caches-types=yes")));
	$caches_types[null]='{select}';
	$caches_types["tmpfs"]="{squid_cache_memory}";
	$caches_types["Cachenull"]="{without_cache}";

	unset($caches_types["rock"]);



	$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",$cache_type,"CheckCachesTypes$t()",null,0,"font-size:22px;padding:3px"));
	$cpus=$tpl->_ENGINE_parse_body(Field_array_Hash($CPUZ,"CPU-$t",$cpu,"blur()",null,0,"font-size:22px;padding:3px"));

	$CPU_FIELD="<tr>
	<td class=legend style='font-size:22px' nowrap>{process}:</td>
	<td>$cpus</td>
	<td>&nbsp;</td>
	</tr>";


	if($SquidSimpleConfig==1){
	$CPU_FIELD="<tr>
	<td class=legend style='font-size:22px' nowrap>{process}:</td>
	<td style='font-size:22px'><input type='hidden' id='CPU-$t' value='1'>#1</td>
	<td>&nbsp;</td>
	</tr>";
	}


	$browse=button("{browse}...", "Loadjs('SambaBrowse.php?no-shares=yes&field=cache_directory-$t')",16);
			if($ID>0){$browse=null;
			$perr="<p class=text-error>{cannot_modify_a_created_cache}</p>";
	}

	$html="
	<div id='waitcache-$t'></div>
	<div style='width:98%' class=form>
	$perr
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{enabled}:</td>
		<td style='font-size:22px'>" . Field_checkbox_design("enabled-$t",1,$enabled,"EnableCheck$t()")."</td>
		<td>&nbsp;</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{name}:</td>
		<td>" . Field_text("cachename-$t",$cachename,"width:350px;font-size:22px;padding:3px")."</td>
		<td></td>
	</tr>
	$CPU_FIELD
	<tr>
		<td class=legend style='font-size:22px' nowrap>{directory}:</td>
		<td>" . Field_text("cache_directory-$t",$cache_directory,"width:350px;font-size:22px;padding:3px")."</td>
		<td>$browse</td>
	</tr>
	<tr>
	<td class=legend style='font-size:22px' nowrap>{type}:</td>
		<td>$type</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{cache_size}","{cache_size_text}").":</td>
		<td style='font-size:22px'>" . Field_text("squid-cache-size-$t",$cache_size,"width:220px;font-size:22px;padding:3px;text-align:right")."&nbsp;MB</td>
		<td style='font-size:22px'>&nbsp;</td>
	</tr>
		<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{cache_dir_level1}","{cache_dir_level1_text}").":</td>
		<td>" . Field_text("cache_dir_level1-$t",$cache_dir_level1,'width:110px;font-size:22px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{cache_dir_level2}","{cache_dir_level2_text}").":</td>
		<td>" . Field_text("cache_dir_level2-$t",$cache_dir_level2,'width:110px;font-size:22px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr><td colspan=3><hr></td></tr>

	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{min_size}","{cache_dir_min_size_text}").":</td>
		<td width=1% nowrap style='font-size:22px'>" . Field_text("min_size-$t",$min_size,'width:220px;font-size:22px;padding:3px;text-align:right')."&nbsp;KB&nbsp;</td>
		<td style='font-size:22px' width=1% nowrap></td>
	</tr>
		<tr>
			<td class=legend nowrap style='font-size:22px'>".texttooltip("{max_size}","{cache_dir_max_size_text}").":</td>
			<td width=1% nowrap style='font-size:22px'>" . Field_text("max_size-$t",$max_size,'width:220px;font-size:22px;padding:3px;text-align:right')."&nbsp;KB&nbsp;</td>
			<td style='font-size:22px' width=1% nowrap>$max_size_explain</td>
		</tr>

				<tr>
				<td align='right' colspan=3><hr>". button($bt,"AddNewCacheSave$t()",36)."</td>
				</tr>
				</table>
				<p>&nbsp;</p>
				<div id='CacheTypeExplain-$t'></div>
				<div style='font-size:12px'><i>{warn_calculate_nothdsize}</i></div>
				<script>
				function CheckCachesTypes$t(){
				cachetypes=document.getElementById('cache_type-$t').value;
				CacheTypeExplain$t();
}

function CacheTypeExplain$t(){
cachetype=document.getElementById('cache_type-$t').value;
LoadAjaxTiny('CacheTypeExplain-$t','$page?t=$t&ID=$ID&CacheTypeExplain='+cachetype);
}

var x_AddNewCacheSave$t= function (obj) {
var cacheid=$ID;
var results=obj.responseText;
document.getElementById('waitcache-$t').innerHTML='';
if(results.length>3){ alert(results); return; }
if(cacheid==0){YahooWin2Hide();}
if(document.getElementById('proxy-store-caches')){
LoadAjaxRound('proxy-store-caches','admin.dashboard.proxy.caches.php');
}
if(document.getElementById('flexRT{$_GET["t"]}')){
$('#flexRT{$_GET["t"]}').flexReload();
}
}

function AddNewCacheSave$t(){
var enabled=1;
var XHR = new XHRConnection();
if(!document.getElementById('enabled-$t').checked){enabled=0;}
XHR.appendData('cache_directory',document.getElementById('cache_directory-$t').value);
XHR.appendData('cache_type',document.getElementById('cache_type-$t').value);
XHR.appendData('size',document.getElementById('squid-cache-size-$t').value);
XHR.appendData('cache_dir_level1',document.getElementById('cache_dir_level1-$t').value);
XHR.appendData('cache_dir_level2',document.getElementById('cache_dir_level2-$t').value);
XHR.appendData('CPU',document.getElementById('CPU-$t').value);
XHR.appendData('cachename',document.getElementById('cachename-$t').value);
XHR.appendData('min_size',document.getElementById('min_size-$t').value);
XHR.appendData('max_size',document.getElementById('max_size-$t').value);

		XHR.appendData('ID','$ID');
		XHR.appendData('enabled',enabled);
		AnimateDiv('waitcache-$t');
		XHR.sendAndLoad('$page', 'POST',x_AddNewCacheSave$t);
}

function CheckCacheid(){
var cacheid=$ID;
var SquidSimpleConfig=$SquidSimpleConfig;
if(cacheid>0){
document.getElementById('cache_type-$t').disabled=true;
document.getElementById('cache_directory-$t').disabled=true;
document.getElementById('squid-cache-size-$t').disabled=true;
document.getElementById('cache_type-$t').disabled=true;
document.getElementById('cache_dir_level1-$t').disabled=true;
document.getElementById('cache_dir_level2-$t').disabled=true;
}

if(SquidSimpleConfig==1){
document.getElementById('CPU-$t').disabled=true;
}
}

function EnableCheck$t(){
var enabled=1;
var cacheid=$ID;
if(!document.getElementById('enabled-$t').checked){enabled=0;}

document.getElementById('cache_directory-$t').disabled=true;
document.getElementById('squid-cache-size-$t').disabled=true;
document.getElementById('cache_type-$t').disabled=true;
document.getElementById('cache_dir_level1-$t').disabled=true;
document.getElementById('cache_dir_level2-$t').disabled=true;

if(enabled==1){
if(cacheid==0){
document.getElementById('cache_directory-$t').disabled=false;
document.getElementById('squid-cache-size-$t').disabled=false;
document.getElementById('cache_type-$t').disabled=false;
document.getElementById('cache_dir_level1-$t').disabled=false;
document.getElementById('cache_dir_level2-$t').disabled=false;
}
}

}


CacheTypeExplain$t();
EnableCheck$t();
CheckCacheid();
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function items_save(){
	
	
	$_POST=mysql_escape_line_query($_POST);
	$cache_directory=$_POST["cache_directory"];
	$cache_type=$_POST["cache_type"];
	$size=$_POST["size"];
	$cache_dir_level1=$_POST["cache_dir_level1"];
	$cache_dir_level2=$_POST["cache_dir_level2"];
	$CPU=$_POST["CPU"];
	$cachename=$_POST["cachename"];
	$enabled=$_POST["enabled"];
	$min_size=$_POST["min_size"];
	$max_size=$_POST["max_size"];
	$ID=$_POST["ID"];
	if($cache_type=="rock"){$CPU=0;}
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("squid_caches_center","min_size","artica_backup")){
		$sql="ALTER TABLE `squid_caches_center` ADD `min_size` BIGINT UNSIGNED NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	

	if(!$q->FIELD_EXISTS("squid_caches_center","wizard","artica_backup")){
		$sql="ALTER TABLE `squid_caches_center` ADD `wizard` smallint NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	
	
	
	if(!$q->FIELD_EXISTS("squid_caches_center","max_size","artica_backup")){
		$sql="ALTER TABLE `squid_caches_center` ADD `max_size` BIGINT UNSIGNED NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo $q->mysql_error."\n$sql";
			writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	if($cache_type=="tmpfs"){
		$users=new usersMenus();
		$memMB=$users->MEM_TOTAL_INSTALLEE/1024;
		$memMB=$memMB-1500;
		if($size>$memMB){
			$size=$memMB-100;
		}
	}
	
	
	if(substr($cache_directory, 0,1)<>'/'){$cache_directory="/$cache_directory";}
	
	
	if($ID==0){
		$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center 
		(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size)
		VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',$enabled,0,0,1,$min_size,$max_size)","artica_backup");
	}else{
		$q->QUERY_SQL("UPDATE squid_caches_center SET 
			cachename='$cachename',
			cpu=$CPU,
			cache_size='$size',
			min_size='$min_size',
			max_size='$max_size',
			enabled=$enabled
			WHERE ID=$ID","artica_backup");
		
	}
	
if(!$q->ok){echo $q->mysql_error;}
	
	
}



function table(){
$page=CurrentPageName();
$tpl=new templates();
$users=new usersMenus();
$sock=new sockets();
$SquidSimpleConfig=intval($sock->GET_INFO("SquidSimpleConfig"));
$tt=time();
$t=$_GET["t"];
$_GET["ruleid"]=$_GET["ID"];
$cache=$tpl->javascript_parse_text("{cache}");
$directory=$tpl->_ENGINE_parse_body("{directory}");
$type=$tpl->javascript_parse_text("{type}");
$rule=$tpl->javascript_parse_text("{rule}");
$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
$new_cache=$tpl->javascript_parse_text("{new_cache}");
$license=$tpl->javascript_parse_text("{artica_license}");
$rules=$tpl->javascript_parse_text("{rules}");
$cpu=$tpl->javascript_parse_text("{process}");
$apply=$tpl->javascript_parse_text("{apply}");
$action=$tpl->javascript_parse_text("{action}");
$restricted_ports=$tpl->javascript_parse_text("{restricted_ports}");
$title=$tpl->javascript_parse_text("{caches_center}");
$size=$tpl->javascript_parse_text("{size}");
$order=$tpl->javascript_parse_text("{order}");
$all=$tpl->javascript_parse_text("{all}");
$reconstruct_caches=$tpl->javascript_parse_text("{reconstruct_caches}");
$enable=$tpl->_ENGINE_parse_body("{enable}");
$refresh=$tpl->javascript_parse_text("{refresh}");
$cpu_affinity=$tpl->javascript_parse_text("{cpu_affinity}");
$tt=time();
$sock=new sockets();

$DisableAnyCache=intval($sock->GET_INFO("DisableAnyCache"));

if($DisableAnyCache==1){
	echo FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");
	return;
}

$smp_mode_is_disabled=null;

$cpunumber=$users->CPU_NUMBER-1;
if($cpunumber<1){$cpunumber=1;}

$q=new mysql();
$q->CheckTablesSquid();




$bts[]="{name: '<strong style=font-size:18px>$new_cache</strong>', bclass: 'add', onpress : NewRule$tt}";
if($SquidSimpleConfig==0){
	$bts[]="{name: '<strong style=font-size:18px>$all</strong>', bclass: 'cpu', onpress : Bycpu0}";
	
	$CPUZ[]="function Bycpu0(){
	$('#flexRT$tt').flexOptions({url: '$page?items=yes&t=$tt&tt=$tt&cpu=0'}).flexReload();
	}";
	
	for($i=1;$i<$cpunumber+1;$i++){
		$bts[]="{name: '<strong style=font-size:18px>Proc. $i</strong>', bclass: 'cpu', onpress : Bycpu$i}";
		$CPUZ[]="function Bycpu$i(){
			$('#flexRT$tt').flexOptions({url: '$page?items=yes&t=$tt&tt=$tt&cpu=$i'}).flexReload(); 
		}";
	}
	
	$bts[]="{name: '<strong style=font-size:18px>$cpu_affinity</strong>', bclass: 'Settings', onpress : CpuAff$tt}";
}
$bts[]="{name: '<strong style=font-size:18px>$refresh</strong>', bclass: 'Reload', onpress : Refresh$tt}";
$bts[]="{name: '<strong style=font-size:18px>$reconstruct_caches</strong>', bclass: 'recycle', onpress : ReconstructCaches$tt}";
$bts[]="{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Apply$tt}";


$sock=new sockets();
$sock->getFrameWork("squid.php?squid_caches_infos=yes");



if(!$users->CORP_LICENSE){
	$bts[]="{name: '$license', bclass: 'Warn', onpress : License$tt}";
}


	
$buttons="buttons : [ ".@implode(",", $bts)." ],";
	
$html="$smp_mode_is_disabled
<input type='hidden' id='CACHE_CENTER_TABLEAU' value='flexRT$tt'>
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
	function Start$tt(){
		$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&t=$tt&tt=$tt',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'none', width :32, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>$order</span>', name : 'cpu', width :48, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$cpu</span>', name : 'cpu', width :32, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$cache</span>', name : 'cachename', width :210, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$directory</span>', name : 'cache_dir', width :332, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$type</span>', name : 'cache_type', width :68, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$size</span>', name : 'cache_size', width :236, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>%</span>', name : 'percentcache', width :55, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$enable</span>', name : 'enabled', width :55, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'up', width :55, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width :55, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'rebuild', width : 70, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$cache', name : 'cachename'},
		{display: '$cpu', name : 'cpu'},
		{display: '$type', name : 'cache_type'},
		{display: '$directory', name : 'cache_dir'},
		],
		sortname: 'zOrder',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:30px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 500,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
var xNewRule$tt= function (obj) {
var res=obj.responseText;
if (res.length>3){alert(res);return;}
$('#flexRT$t').flexReload();
$('#flexRT$tt').flexReload();
}
	
function Apply$tt(){
	Loadjs('squid.caches.progress.php');
	
}

function CpuAff$tt(){
	Loadjs('squid.caches.center.cpuaff.php');
}

function Refresh$tt(){
	Loadjs('squid.refresh-status.php');
}

	
function  License$tt(){
	GoToArticaLicense();
}
". @implode("\n", $CPUZ)."
	
function NewRule$tt(){
	Loadjs('squid.caches.center.wizard.php',true);
	//Loadjs('$page?item-js=yes&ID=0&t=$tt',true);
}
function ReconstructCaches$tt(){
	Loadjs('squid.rebuild.caches.progress.php');
}
	
function INOUT$tt(ID){
var XHR = new XHRConnection();
XHR.appendData('INOUT', ID);
XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}
	
function rports(){
Loadjs('squid.webauth.hotspots.restricted.ports.php',true);
}
	
function reverse$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('reverse', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}
	
var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload();
	}
	
	function MoveRuleDestination$tt(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	
	function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('rules-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	Start$tt();
	
	</script>
	";
		echo $html;
}

function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$sock=new sockets();
	$DisableAnyCache=intval($sock->GET_INFO("DisableAnyCache"));
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){$DisableAnyCache=1;$SquidBoosterEnable=0;}
	$DisableSquidSMP=intval($sock->GET_INFO("DisableSquidSMP"));
	$SquidSimpleConfig=intval($sock->GET_INFO("SquidSimpleConfig"));
	$squid_caches_infos=unserialize(base64_decode($sock->getFrameWork("squid.php?squid_get_caches_infos=yes")));
	
	
	$squid=new squidbee();
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["minimum_object_size"],$re)){
		$minimum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["minimum_object_size"],$re)){$minimum_object_size_unit=$re[1];}
		if($minimum_object_size_unit==null){$minimum_object_size_unit="KB";}
		if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		if($minimum_object_size_unit=="MB"){$minimum_object_size=$minimum_object_size*1024;}
	}
	
	
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size"],$re)){
		$maximum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size"],$re)){$maximum_object_size_unit=$re[1];}
		if($maximum_object_size_unit==null){$maximum_object_size_unit="KB";}
		if($maximum_object_size_unit=="KB"){
			if($maximum_object_size<4096){$maximum_object_size=4096;}
		}
		if($maximum_object_size_unit=="MB"){
			if($maximum_object_size<4){$maximum_object_size=4;}
			$maximum_object_size=$maximum_object_size*1024;
		}
	}
	

	
	
	
	

	$t=$_GET["t"];
	$search='%';
	$table="squid_caches_center";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	if(isset($_GET["cpu"])){
		if($_GET["cpu"]>0){
			$FORCE_FILTER="AND `cpu`={$_GET["cpu"]}";
		}
	}
	
	
	if($q->COUNT_ROWS($table, "artica_backup")==0){
		$squid=new squidbee();
		$cachename=basename($squid->CACHE_PATH);
		$q->QUERY_SQL("INSERT IGNORE INTO $table (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache)
		VALUES('$cachename',1,'$squid->CACHE_PATH','$squid->CACHE_TYPE','2000','128','256',1,0,0)","artica_backup");
		if(!$q->ok){json_error_show($q->mysql_error."<br>",1);}
	}


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
		
	}
	


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql",1);}
	$caches_disabled="<br>".$tpl->_ENGINE_parse_body("{caches_are_disabled}");
	$muliproc_disabled="<br>".$tpl->javascript_parse_text("{muliproc_disabled}");
	$fontsize="20";
	$files=$tpl->javascript_parse_text("{files}");
	$to=$tpl->javascript_parse_text("{to}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$options_text=null;
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-item-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')");
		$reconstruct=imgsimple("dustbin-32.png",null,"Loadjs('$MyPage?delete-empty-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')");
		$ID=$ligne["ID"];
		$cachename=$ligne["cachename"];
		$cache_dir=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=abs($ligne["cache_size"]);
		$percentcache=$ligne["percenttext"];
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		
		
		if($max_size==0){$max_size=$maximum_object_size;}
		
		
		$cpu=$ligne["cpu"];
		$icon_status="ok32.png";
		$explainSMP=null;
		$infos=null;
		if($cache_type=="tmpfs"){
			$ligne["cache_dir"]="/home/squid/cache/MemBooster$ID";
			$cache_dir="-";}
		if(!$users->CORP_LICENSE){$link=null;$delete=null;}
		
		$cache_size=FormatBytes($cache_size*1024);
		
		
		if($ligne["remove"]==1){$color="#C7C7C7";$delete="&nbsp;";$icon_status="ok32-grey.png";}
		if($DisableAnyCache==1){$color="#9A9A9A";$infos=$caches_disabled;$icon_status="ok32-grey.png";}
		$usedcache=FormatBytes($ligne["usedcache"]);
		
		if($DisableSquidSMP==1){
			if($cpu>1){
				$explainSMP=$muliproc_disabled;
				$ligne["enabled"]=0;
			}
		}
		
		if($ligne["enabled"]==1){
			if(!isset($squid_caches_infos[$ligne["cache_dir"]])){
				$icon_status="warning32.png";
			}else{
				if($squid_caches_infos[$ligne["cache_dir"]]["USED"]>0){
					$icon_status="ok32.png";
				}
			}
		}else{
			$color="#9A9A9A";$infos=$caches_disabled;$icon_status="ok32-grey.png";
		}
		
		
	
		
		$link="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?item-js=yes&ID=$ID&t={$_GET["t"]}',true);\"
		style='font-size:{$fontsize}px;font-weight:normal;color:$color;text-decoration:underline'
		>";
		
		
		

		
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		$enable=Field_checkbox("enable-{$ligne['ID']}", 1,$ligne["enabled"],"Loadjs('$MyPage?enable-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')");
		
		
		if($cache_type=="Cachenull"){
			$icon_status="ok32.png";
			$usedcache="-";
			$cache_size="-";
			$percentcache="0";
			$cache_type="-";
			$cache_dir="-";
			$reconstruct="&nbsp;";
		}
		$CPUAF_TEXT=null;
		
		if($SquidSimpleConfig==1){$cpu="-";}
		
		if($cache_type<>"rock"){
			$CPUAF=$ligne["CPUAF"];
			if($CPUAF>0){
				$CPUAF_TEXT=" <span style='font-size:14px'>(CPU #$CPUAF)</span>";
			}
		}
		
		
		if($ligne["remove"]==1){$link=null;}
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<img src='img/$icon_status'>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["zOrder"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$cpu</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cachename</a>$CPUAF_TEXT</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cache_dir</a></span>$infos<i>$explainSMP</i></strong>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cache_type</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$usedcache/$cache_size</a></span><br>
						<i style='font-size:14px'>$files: ".FormatBytes($min_size)." $to ".FormatBytes($max_size)."</i>
						",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link{$percentcache}%</a></span>",$enable,
						"<center>$up</center>",
						"<center>$down</center>",
						
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</center>",
						"<center>$reconstruct</center>")
		);
	}


	echo json_encode($data);

}
