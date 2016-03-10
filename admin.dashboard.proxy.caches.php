<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

if(isset($_GET["caches-full-remove-js"])){remove_notif_caches_full_js();exit;}
if(isset($_GET["gengraph"])){gengraph();exit;}
if(isset($_POST["caches-full-remove"])){remove_notif_caches_full();exit;}
page();


function gengraph(){
	
	$styleTitle="font-size:18px;color:#005447";	
	if(intval($_GET["current"])==0){
		if(intval($_GET["max"])==0){
			
			echo "document.getElementById('{$_GET["container"]}').innerHTML='<center><span style=\"$styleTitle\">{$_GET["name"]}</span><center style=\"margin-top:10px\"><img src=img/cache-status-off.png></center></center>'";
			return;
		}
		
	}
	
	$free=$_GET["max"]-$_GET["current"];
	
	
	$PieData["{free}"]=$free;
	$PieData["{used}"]=$_GET["current"];
	
	
$highcharts=new highcharts();
$highcharts->container=$_GET["container"];
$highcharts->PieDatas=$PieData;
$highcharts->ChartType="pie";
$highcharts->PiePlotTitle=">>";
$highcharts->PieDataLabels=false;
$highcharts->PieRedGreen=true;
$highcharts->Title=$_GET["name"];
$highcharts->LegendSuffix=" MB";
echo $highcharts->BuildChart();
}

function page(){
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$tpl=new templates();
	$error=null;
	$defined_size=$tpl->_ENGINE_parse_body("{Defined_size}");
	$DisableAnyCache=intval($sock->GET_INFO("DisableAnyCache"));
	$COUNT_DE_CACHES=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES"));
	$COUNT_DE_MEMBERS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_COUNT"));
	$users=new usersMenus();
	if($DisableAnyCache==1){
		echo FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");
		return;
	
	}
	
	$CACHES_AVG=round(@file_get_contents("{$GLOBALS["BASEDIR"]}/CACHES_AVG"),1);

	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db";
	$MAIN_CACHES=unserialize(@file_get_contents($cachefile));
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\" 
	OnClick=\"javascript:Loadjs('squid.caches.center.wizard.php',true);\"";
	
	
	$curs2="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"javascript:Loadjs('squid.caches.progress.php',true);\"";
	
	$curs3="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"javascript:Loadjs('squid.refresh-status.php',true);\"";
	
	$curs4="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"javascript:Loadjs('squid.rebuild.caches.progress.php');\"";	
	
	$curs5="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"LoadAjaxRound('proxy-store-caches','admin.dashboard.proxy.caches.php');\"";

	
	$curs6="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"GoToCachesCenter();\"";	
	
	$curs7="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"Loadjs('squidclient.mgr-storedir.php');\"";	
	
	$curs8="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"Loadjs('squid.caches.center.php?change-cache-types-js=yes');\"";	
	
	$main_icon="add-180.png";
	$color="black";
	
	if(!$users->CORP_LICENSE){
		$main_icon="add-180-grey.png";
		$curs=null;
		$color="#898989";
		$curs6=null;
		$error="<p class=text-error style='font-size:18px'>{welcome_new_cache_wizard_license}</p>";
	}
	
	$SquidCacheFullHideWarn=intval($sock->GET_INFO("SquidCacheFullHideWarn"));
	
	if($SquidCacheFullHideWarn==0){
	if($CACHES_AVG>85){
			$error=$error."

			<p class=text-error style='font-size:18px'>{caches_are_full_explain}
			<br><br>
					&laquo;&nbsp;<a href=\"javascript:Loadjs('$page?caches-full-remove-js=yes');\"
					style='font-size:18px;text-decoration:underline;color:#B94A48;font-weight:bold'>{hide_info_def}</a>&nbsp;&raquo;
					
			</p>";
			
		}
	}
	
	if($COUNT_DE_CACHES>0){
		if($COUNT_DE_MEMBERS>15){
			if($COUNT_DE_CACHES<20000){
				$undersized_proxy_caches_explain=$tpl->_ENGINE_parse_body("{undersized_proxy_caches_explain}");
				$COUNT_DE_CACHES_KB=$COUNT_DE_CACHES*1024;
				$COUNT_DE_CACHES_TEXT=FormatBytes($COUNT_DE_CACHES_KB);
				$undersized_proxy_caches_explain=str_replace("%S", $COUNT_DE_CACHES_TEXT, $undersized_proxy_caches_explain);
				$undersized_proxy_caches_explain=str_replace("%U", $COUNT_DE_MEMBERS, $undersized_proxy_caches_explain);
				$error=$error."<p class=text-error><strong>{undersized_proxy_caches}</strong><br>$undersized_proxy_caches_explain</p>";
			}
				
		}
	}	
	

	
	
	$tr[]="
			
	<table style='width:100%;border:1px solid #CCCCCC;margin:8px'>
	<tr>
			<td valign='top' style='width:410px'>
			<center style='width:410px;height:230px;padding-top:20px' $curs>
				<img src='img/$main_icon'>
			</center>
			
			</td>
			<td valign='top' style='width:99%;vertical-align:middle' nowrap>
				<table style='width:100%'>
				<tr>
					<td valign='middle' width='24px'><img src='img/plus-24.png'></td>
					<td valign='middle' $curs><span style='font-size:22px;text-decoration:underline;color:$color'>{new_cache}</span></td>
				</tr>
				<tr>
					<td valign='middle' width='24px'><img src='img/24-refresh.png'></td>
					<td valign='middle' $curs8><span style='font-size:22px;text-decoration:underline'>{change_caches_type}</span></td>
				</tr>				
				<tr style='height:50px'><td colspan=2>&nbsp;</td></tr>
				<tr>
					<td valign='middle' width='24px'><img src='img/arrow-right-24.png'></td>
					<td valign='middle' $curs7><span style='font-size:22px;text-decoration:underline'>{display_details}</span></td>
				</tr>				
				<tr>
					<td valign='middle' width='24px'><img src='img/arrow-right-24.png'></td>
					<td valign='middle' $curs6><span style='font-size:22px;text-decoration:underline;color:$color'>{caches_center}</span></td>
				</tr>						
				<tr>
					<td valign='middle' width='24px'><img src='img/arrow-right-24.png'></td>
					<td valign='middle' $curs4><span style='font-size:22px;text-decoration:underline'>{reconstruct_caches}</span></td>
				</tr>
				<tr>
					<td valign='middle' width='24px'><img src='img/24-refresh.png'></td>
					<td valign='middle' $curs3><span style='font-size:22px;text-decoration:underline'>{refresh}</span></td>
				</tr>
				
				</table>
				
			</td>
			$error
		</tr>
		</table>	
	";
	
	$q=new mysql();
	$sql="SELECT * FROM squid_caches_center WHERE remove=0";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$SquidBoosterEnable=intval($sock->GET_INFO("SquidBoosterEnable"));
	
	
	if($SquidBoosterEnable==1){
		$MAIN=$MAIN_CACHES["/var/squid/cache_booster"];
		$MAX=$MAIN["MAX"];
		$CURRENT=$MAIN["CURRENT"];
		$FULL_SIZE=$MAIN["FULL_SIZE"];
		$FULL_SIZE=FormatBytes($FULL_SIZE/1024);
		$cache_size=FormatBytes($cache_size*1024);
		$CURRENT_TEXT=FormatBytes($CURRENT);
		$SquidBoosterMem=intval($sock->GET_INFO("SquidBoosterMem"));
		$js[]="Loadjs('$page?gengraph=yes&container=cache-booster&current=$CURRENT&max=$MAX&name=CacheBooster');";
		$tr[]="<table style='width:100%;border:1px solid #CCCCCC;margin:8px'>
		<tr>
		<td valign='top' style='width:430px'>
		<div id='cache-booster' style='width:410px;height:250px'></div>
		</td>
		<td valign='top' style='width:99%'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='font-size:18px' class=legend>{used}:</td>
		<td valign='top' style='font-size:18px;font-weight:bold'>$CURRENT_TEXT</td>
		</tr>
		<tr>
			<td valign='top' style='font-size:18px' class=legend>$defined_size:</td>
			<td valign='top' style='font-size:18px;font-weight:bold'>{$SquidBoosterMem}MB</td>
		</tr>
		<tr>
			<td valign='top' style='font-size:18px' class=legend>{parameters}:</td>
			<td valign='top' style='font-size:18px;font-weight:bold'>". texttooltip("{squid_booster}","{squid_booster_text}","GotoProxyBooster()")."</td>
		</tr>	
		<tr>
		<td colspan=2 align='right'>
		<table style='width:180px'>
		
		</table>
		</td>
		</table>
		</td>
		</tr>
		</table>
		";
		
		
	}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$cachename=$ligne["cachename"];
		$ID=$ligne["ID"];
		if($cache_type=="Cachenull"){continue;}
		if($cache_type=="tmpfs"){$ligne["cache_dir"]="/home/squid/cache/MemBooster$ID";}
		
		$cachedir=$ligne["cache_dir"];
		$MAIN=$MAIN_CACHES[$cachedir];
		
		$MAX=$MAIN["MAX"];
		$CURRENT=$MAIN["CURRENT"];
		$FULL_SIZE=$MAIN["FULL_SIZE"];
		$FULL_SIZE=FormatBytes($FULL_SIZE/1024);
		$cache_size=FormatBytes($cache_size*1024);
		$CURRENT_TEXT=FormatBytes($CURRENT);
		$cachenameen=urlencode($cachename);
		if(!is_numeric($CURRENT)){$CURRENT=0;}
		if(!is_numeric($MAX)){$MAX=0;}
		$enabled_text=null;
		$remove=$ligne["remove"];
		$delete_text=null;
		
		if($remove==0){
			$js[]="Loadjs('$page?gengraph=yes&container=cache-$ID&current=$CURRENT&max=$MAX&name=$cachenameen');";
			
			$curs3="OnMouseOver=\"this.style.cursor='pointer';\"
			OnMouseOut=\"this.style.cursor='auto'\"
			OnClick=\"javascript:Loadjs('squid.caches.center.php?delete-item-js=yes&ID={$ligne["ID"]}')\"";			
			
			$curs4="OnMouseOver=\"this.style.cursor='pointer';\"
			OnMouseOut=\"this.style.cursor='auto'\"
			OnClick=\"javascript:Loadjs('squid.caches.center.php?delete-empty-js=yes&ID={$ligne["ID"]}')\"";
				
			
			
			
			$delete_text="
			<tr>
			
				<tr $curs3>
					<td width=32px'><img src='img/delete-32.png'></td>
					<td style='font-size:18px;text-decoration:underline'>{delete}</td>
				</tr>
				<tr $curs4>
					<td width=32px'><img src='img/dustbin-32.png'></td>
					<td style='font-size:18px;text-decoration:underline'>".texttooltip("{purge}","{purge_cache_explain}")."</td>
				</tr>				
			";
		
		}
		
		if($ligne["enabled"]==0){
			

			$enabled_text="<tr>
			<td valign='top' style='font-size:18px' class=legend>&nbsp;</td>
			<td valign='top' style='font-size:18px' >{disabled}</td>
			</tr>";
		}
		
		$tr[]="<table style='width:100%;border:1px solid #CCCCCC;margin:8px'>
		<tr>
			<td valign='top' style='width:430px'>
			<div id='cache-$ID' style='width:410px;height:250px'></div>
			</td>
			<td valign='top' style='width:99%'>
				<table style='width:100%'>$enabled_text
					<tr>
						<td valign='top' style='font-size:18px' class=legend>{used}:</td>
						<td valign='top' style='font-size:18px;font-weight:bold'>$CURRENT_TEXT</td>
					</tr>					
					<tr>
						<td valign='top' style='font-size:18px' class=legend>$defined_size:</td>
						<td valign='top' style='font-size:18px;font-weight:bold'>$cache_size</td>
					</tr>
					<tr>
						<td colspan=2 align='right'>
							<table style='width:180px'>					
							$delete_text
							</table>
						</td>
				</table>
			</td>
		</tr>
		</table>	
				
		";
	}
	
	

	echo "
	<div style='font-size:45px;margin-bottom:20px;margin-top:10px'>".$tpl->_ENGINE_parse_body('{your_proxy_caches}')."</div>		
	<div style='width:98%'>
		".$tpl->_ENGINE_parse_body(CompileTr2($tr,true))."</div>
	<script>".@implode("\n", $js)."</script>";
	
	
	
	
}

function remove_notif_caches_full(){
	
	$sock=new sockets();
	$sock->SET_INFO("SquidCacheFullHideWarn", 1);
	
}

function remove_notif_caches_full_js(){
	header("content-type: application/x-javascript");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$hide_info_def=$tpl->javascript_parse_text("{hide_info_def}");
	echo "
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	LoadAjaxRound('proxy-store-caches','admin.dashboard.proxy.caches.php');
}
function Save$t(zmd5){
	if(!confirm('$hide_info_def ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('caches-full-remove', 'yes');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();
";
	
	
}
