<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.cron.inc');
	
	if(isset($_GET["popup-index"])){popup();exit;}
	
	$user=new usersMenus();
	if($user->AsAnAdministratorGeneric==false){die();exit();}	
	if(isset($_GET["min_0"])){save();exit;}
	
	js();
	
function js(){
	
$page=CurrentPageName();
$prefix=str_replace(".","_",$page);
$tpl=new templates();
$uid=$_GET["uid"];
$title=$tpl->_ENGINE_parse_body('{SET_SCHEDULE}');
$function=$_GET["function"];
$function2=$_GET["function2"];
if($function<>null){$add_func="$function(results);";}else{
	$add_func="document.getElementById('{$_GET["field"]}').value=results";	
}
if($function2<>null){$function2="$function2()";}


$html="

function {$prefix}Loadpage(){
	var field=escape(document.getElementById('{$_GET["field"]}').value);

	YahooWinBrowse('1217','$page?popup-index=yes&field-datas='+field,'$title');
	
	}
	
var x_save_cron= function (obj) {
	var results=obj.responseText;
	if(results.length>0){
	$add_func
	$function2
	YahooWinBrowseHide();
	}
}
	
function UnselectMin(){
	var i;
	for(i=0;i<60;i++){
		id='min_'+i;
		idimg='img_'+id;
		if(document.getElementById(id)){
			document.getElementById(id).value='0';
			document.getElementById(idimg).src='img/status_critical.png';
		}
	}
}
function selectAllMin(){
	var i;
	for(i=0;i<60;i++){
		id='min_'+i;
		idimg='img_'+id;
		if(document.getElementById(id)){
			document.getElementById(id).value='1';
			document.getElementById(idimg).src='img/status_ok.png';
		}
	}
}

function UnselectHour(){
	var i;
	for(i=0;i<24;i++){
		id='hour_'+i;
		idimg='img_'+id;
		if(document.getElementById(id)){
			document.getElementById(id).value='0';
			document.getElementById(idimg).src='img/status_critical.png';
		}
	}
}
function selectAllHour(){
	var i;
	for(i=0;i<24;i++){
		id='hour_'+i;
		idimg='img_'+id;
		if(document.getElementById(id)){
			document.getElementById(id).value='1';
			document.getElementById(idimg).src='img/status_ok.png';
		}
	}
}
	
	
			
	
	


{$prefix}Loadpage();

";
	
	
echo $html;
	
	
}



function popup(){
	$page=CurrentPageName();
	$datas=trim($_GET["field-datas"]);
	$font_style="style='font-size:18px'";
	
for($i=0;$i<60;$i++){$def[]=$i;}	
for($i=0;$i<24;$i++){$def1[]=$i;}	
	
	if(trim($datas)<>null){
		$tbl=explode(" ",$datas);
		if($tbl[4]=='*'){$tbl[4]="0,1,2,3,4,5,6";}
		 $defaults_days=explode(",",$tbl[4]);
		 while (list ($num, $line) = each ($defaults_days)){
		 	$value_default_day[$line]=1;
		 }
		 
		 if($tbl[0]=="*"){$tbl[0]=implode(",",$def);}
		 if($tbl[1]=="*"){$tbl[1]=implode(",",$def1);}
		
		$defaults_min=explode(",",$tbl[0]);
		while (list ($num, $line) = each ($defaults_min)){
		 	$value_default_min[$line]=1;
		 }	

		$defaults_hour=explode(",",$tbl[1]);
		while (list ($num, $line) = each ($defaults_hour)){
		 	$value_default_hour[$line]=1;
		 }			 
		 
		
	}
	
$array_days=array("sunday","monday","tuesday","wednesday","thursday","friday","saturday");

$mins="<table style='width:100%;'>";

$group_min="<table style='width:100%;'>
<tr><td valign='top'>";
$count=0;
for($i=0;$i<60;$i++){
	if($i<10){$min_text="0$i";}else{$min_text=$i;}
	
	if($count>10){
		$mins=$mins."</table>";
		$group_min=$group_min."$mins</td><td valign='top'>";
		$mins="<table style='width:100%'>";
		$count=0;
	}
	$mins=$mins."
		<tr>
		<td width=1%>".Field_checkbox_design("min_{$i}",1,$value_default_min[$i])."</td>
		<td nowrap $font_style><strong>$min_text mn</strong></td>
		</tr>";
	
	$scripts[]="if(document.getElementById('min_{$i}').checked){XHR.appendData('min_{$i}',1);}else{XHR.appendData('min_{$i}',0);}";
	$UnselectAllMins[]="document.getElementById('min_{$i}').checked=false;";
	$selectAllMins[]="document.getElementById('min_{$i}').checked=true;";
	$count=$count+1;
}


$group_min=$group_min."</td>
<td valign='top' style='vertical-align:top'>$mins</td>
</tr>
</table>";

$group_hours="<table style='width:100%;'>
<tr>
	<td valign='top' style='vertical-align:top'>
	<table style='width:100%'>";
$count=0;
for($i=0;$i<24;$i++){
	if($i<10){$hour_text="0$i";}else{$hour_text=$i;}
	
	if($count>5){
		$hours=$hours."</table>
		<!-- hours next -->";
		$group_hours=$group_hours."$hours
</td>
<td valign='top' style='vertical-align:top'>";
		$hours="
			<table style='width:100%'>";
		$count=0;
	}
	$hours=$hours."
		<tr>
		<td width=1% style=''>".Field_checkbox_design("hour_{$i}",1,$value_default_hour[$i])."</td>
		<td nowrap $font_style><strong>$hour_text h</strong></td>
		</tr>
		";
	$scripts[]="if(document.getElementById('hour_{$i}').checked){XHR.appendData('hour_{$i}',1);}else{XHR.appendData('hour_{$i}',0);}";
	$UnselectAllHours[]="document.getElementById('hour_{$i}').checked=false;";
	$selectAllHours[]="document.getElementById('hour_{$i}').checked=true;";
	$count=$count+1;
}



$group_hours=$group_hours."
	</td>
	<td valign='top' style='vertical-align:top'>
		$hours
	</td>
</tr>
</table>
<!-- hours end -->";


	while (list ($num, $line) = each ($array_days)){
		$days_html=$days_html."
			<tr style='height:100px'>
			<td width=1%>".Field_checkbox_design("day_{$num}",1,intval($value_default_day[$num]))."</td>
			<td $font_style>{{$line}}</td>
			</tr>";
			$UnselectAlljs[]="document.getElementById('day_{$num}').checked=false;";
			$scripts[]="if(document.getElementById('day_{$num}').checked){XHR.appendData('day_{$num}',1);}else{XHR.appendData('day_{$num}',0);}";
		
	}
	
	$days_html="
<table style='width:100%'>
		$days_html
</table>";
	

$html="
<form name='FFM_CRON'>
<table style='width:100%'>
	<tr>
		<td valign='top' style='vertical-align:top'>
			
			
			<div style='width:98%' class=form>
			<div style='font-size:38px'>{days}</div>
			$days_html
			</div>
		</td>
		<td valign='top' style='vertical-align:top;padding-left:15px'>
		<!-- hours -->
		<div style='width:98%' class=form>
			<div style='font-size:28px'>{hours}</div>
				$group_hours
			</table>
			</div>
			<div style='width:100%;text-align:right;font-size:12px'>
				<a href=\"#\" OnClick=\"javascript:UnselectAllHours();\" style='font-size:12px'>{unselect_all}</a>&nbsp;&nbsp;
				<a href=\"#\" OnClick=\"javascript:SelectAllHours();\" style='font-size:12px'>{all}</a>
			</div>				
			<div style='width:98%' class=form>
			<div style='font-size:28px'>{minutes}</div>
			$group_min
			</table>
			</div>
			<div style='width:100%;text-align:right;font-size:12px'>
				<a href=\"#\" OnClick=\"javascript:UnselectAllMins();\" style='font-size:12px'>{unselect_all}</a>&nbsp;&nbsp;
				<a href=\"#\" OnClick=\"javascript:SelectAllMins();\" style='font-size:12px'>{all}</a>
			</div>
		</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>
	". button("{apply}", "SaveCronInfos()",28)."
	
	</td>
	</tr>
	</table>
</form>
<script>

	function SaveCronInfos(){
		var XHR = new XHRConnection();
		". @implode("\n", $scripts)."
		XHR.sendAndLoad('$page', 'GET',x_save_cron);
	
	}
	
	
	function UnselectAllMins(){
		".@implode($UnselectAllMins, "\n")."
	}
	
	function UnselectAllHours(){
		".@implode($UnselectAllHours, "\n")."	
	}
	
	function SelectAllHours(){
		".@implode($selectAllHours, "\n")."	
	}
	
	function SelectAllMins(){
		".@implode($selectAllMins, "\n")."	
	}	
	


</script>
";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}

function save(){
	
	while (list ($num, $line) = each ($_GET)){
		if(preg_match("#day_([0-9]+)#",$num,$re)){
			if($line==1){
				$day[]=$re[1];
			}
		}
		
		if(preg_match("#min_([0-9]+)#",$num,$re)){
			if($line==1){
				$min[]=$re[1];
			}
		}

		if(preg_match("#hour_([0-9]+)#",$num,$re)){
			if($line==1){
				$hour[]=$re[1];
			}
		}		
		
	}
	
if(count($min)==0){$minutes="*";}else{$minutes=implode(",",$min);}
if(count($hour)==0){$heures="*";}else{$heures=implode(",",$hour);}
if(count($day)==0){$jours="*";}else{$jours=implode(",",$day);}			

if(count($hour)==24){$heures="*";}
if(count($day)==7){$jours="*";}
if(count($min)==60){$minutes="*";}

$cmd="$minutes $heures * * $jours";
echo $cmd;	
}
	
	
	
?>