<?php
function DAEMON_STATUS_ROUND($key,$bsini,$textoadd=null,$noenable=0,$newInterface=0,$noman=0){
	$ADD_ID_DIV=false;
	$idIMG=null;
	$processes=null;
	$cmdline=null;
	if(preg_match("#^ID:(.+)#",$key, $re)){$ADD_ID_DIV=true;$key=$re[1];}
	$version=null;$license_error=null;$icap=null;$pattern=null;$textoadd2=null;$disabled=null;
	
	if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$key: checking service</span><br>\n<hr>";;print_r($bsini->_params[$key]);}
	
	if(!isset($bsini->_params[$key]["service_name"])){
		if($GLOBALS["VERBOSE"]){
			echo "<span style='color:#d32d2d'>".__FUNCTION__."::".__LINE__."::FAILED:: KEY \"service_name\" for &laquo;$key&raquo;<br>\n".print_r($bsini->_params[$key])."<br></span>\n";}return null;}
	if($bsini->_params["$key"]["service_name"]==null){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::FAILED<br>\n";}return null;}
	if($bsini->_params["$key"]["service_disabled"]==null){$bsini->_params["$key"]["service_disabled"]=-1;}
	$app=$bsini->_params["$key"]["service_name"];
	if(isset($bsini->_params["$key"]["monit"])){$monit=$bsini->_params["$key"]["monit"];}else{$monit=0;}
	if(isset($bsini->_params["$key"]["change-name"])){$change_name=$bsini->_params["$key"]["change-name"];}else{$change_name=null;}
	if(!isset($bsini->_params[$key]["service_croned"])){$bsini->_params[$key]["service_croned"]=0;}
	if(!isset($bsini->_params[$key]["master_pid"])){$bsini->_params[$key]["master_pid"]=null;}
	if(!isset($bsini->_params[$key]["uptime"])){$bsini->_params[$key]["uptime"]=null;}
	if(!isset($bsini->_params[$key]["processes_number"])){$bsini->_params[$key]["processes_number"]=0;}
	if(!isset($bsini->_params[$key]["running"])){$bsini->_params[$key]["running"]=0;}
	if(!isset($bsini->_params[$key]["installed"])){$bsini->_params[$key]["installed"]=1;}
	
	
	$installed=$bsini->_params[$key]["installed"];
	$pid=null;
	$pid_text=null;
	$emailqueue=null;
	if(isset($bsini->_params[$key]["queue_num"])){
		if(trim($bsini->_params[$key]["queue_num"])>0){	
			$emailqueue="
					<tr>
						<td align='right' colspan=2 style='background-color:#D5EED9;font-size:11px'><strong>".$bsini->_params[$key]["queue_num"]." {emails}</td>
					</tr>	
				";	
		}
	}

if($monit==1){
		if(!isset($GLOBALS["MONIT_ARRAY"])){$sock=new sockets();$GLOBALS["MONIT_ARRAY"]=unserialize(base64_decode($sock->getFrameWork("monit.php?monit-status=yes")));}
		$monit_status=$GLOBALS["MONIT_ARRAY"][$app]["status"];
		$monit_uptime=$GLOBALS["MONIT_ARRAY"][$app]["uptime"];
		$bsini->_params["$key"]["master_memory"]=$GLOBALS["MONIT_ARRAY"][$app]["memory kilobytes total"];
		$memory_percent_total=$GLOBALS["MONIT_ARRAY"][$app]["memory percent total"];
		$cpu_percent_total=$GLOBALS["MONIT_ARRAY"][$app]["cpu percent total"];
		$application_installed=1;
		$bsini->_params["$key"]["master_pid"]=$GLOBALS["MONIT_ARRAY"][$app]["pid"];
		$uptime=$GLOBALS["MONIT_ARRAY"][$app]["uptime"];
		$service_disabled=0;
		$application_installed=1;
		
		
		switch ($monit_status) {
			case "monitored":$bsini->_params[$key]["running"]=1;$status="sleeping";break;
			case "not monitored":$bsini->_params[$key]["running"]=0;$status="stopped";break;
			default:
				;
			break;
		}	
}


if($noenable==1){
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>$key:Service not enabled -> NULL</span><br>\n";}
	if($bsini->_params["$key"]["service_disabled"]==0){return null;}}
$tpl=new templates();
$prod=$bsini->_params[$key]["service_name"];
$title=$tpl->_ENGINE_parse_body("{{$prod}}");


if($bsini->_params[$key]["running"]==0){
		$img="42-red.png";
		$status="{stopped}";
		$js_service="javascript:StartStopService('".$bsini->_params["$key"]["service_cmd"]."','1','$title');";
		if($bsini->_params[$key]["service_croned"]==1){
			$img="42-green.png";
			$status="Croned";
		}
	}else{
		$img="42-green.png";
		$status="running";
		$js_service="StartStopService('".$bsini->_params["$key"]["service_cmd"]."','0','$title');";
		
	}	

	
	if($bsini->_params["$key"]["running"]==1){
		$button="<input type='button' OnClick=\"$js_service\" value='{stop}&nbsp;&raquo;'>";
	}else{
		$js_service="StartStopService('".$bsini->_params["$key"]["service_cmd"]."','1','$title');";	
		$button="<input type='button' OnClick=\"$js_service\" value='{start}&nbsp;&raquo;'>";
	}
	
	if(!isset($bsini->_params[$key]["pattern_version"])){$bsini->_params[$key]["pattern_version"]=null;}
	if(!isset($bsini->_params[$key]["pattern_date"])){$bsini->_params[$key]["pattern_date"]=null;}
	if(!isset($bsini->_params[$key]["license_error"])){$bsini->_params[$key]["license_error"]=null;}
	if(!isset($bsini->_params[$key]["why"])){$bsini->_params[$key]["why"]=null;}
	if(!isset($bsini->_params[$key]["other"])){$bsini->_params[$key]["other"]=null;}
	if(!isset($bsini->_params[$key]["threads_socket"])){$bsini->_params[$key]["threads_socket"]=null;}
	if(!isset($bsini->_params[$key]["master_memory"])){$bsini->_params[$key]["master_memory"]=null;}
	if(!isset($bsini->_params[$key]["master_cached_memory"])){$bsini->_params[$key]["master_cached_memory"]=null;}
	
	
	
if($bsini->_params[$key]["pattern_version"]<>null && $bsini->_params[$key]["pattern_date"]==null){
	$bsini->_params[$key]["pattern_date"]=$bsini->_params[$key]["pattern_version"];
}
	
if($bsini->_params["$key"]["service_cmd"]==null){$js_service=null;}

if($bsini->_params[$key]["pattern_date"]<>null){
	$date=date('Y-m-d');
	$bsini->_params[$key]["pattern_date"]=str_replace($date,"{today},",$bsini->_params[$key]["pattern_date"]);
	$date=date('Y-d-m');
	$bsini->_params[$key]["pattern_date"]=str_replace($date,"{today},",$bsini->_params[$key]["pattern_date"]);	
	$pattern=
	"<tr>
	<td align='right' valign='top'><strong>{pattern_ver}:</strong></td>
	<td align='left' valing='top'><strong>{$bsini->_params[$key]["pattern_date"]}</strong></td>
	</tr>";
}

if($bsini->_params["$key"]["service_disabled"]=="0"){	
	$img="warning42.png";
	//$js_service="StartStopService('".$bsini->_params["$key"]["service_cmd"]."','1','$title');";
	$disabled="<tr><td colspan=2 align='right'><strong style='font-size:11px;line-height:auto'><i style='font-size:11px'>{service_disabled}</i></strong></td></tr>";
	$disabled_mini="<i style='font-size:11px'>{service_disabled}</i>";
}

if($installed==0){
	$img="42-green-grey.png";
	$disabled="<tr><td colspan=2 align='right'><strong style='font-size:11px;line-height:auto'><i style='font-size:11px'>{not_installed}</i></strong></td></tr>";
	$disabled_mini="<i style='font-size:11px'>{not_installed}</i>";
	$status="&nbsp;-&nbsp;";
}

if($bsini->_params["$key"]["master_pid"]<>null){

	$pid_text="";
		$pid="
		<tr>
			<td align='right' nowrap style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{pid}:</strong></td>
			<td style='background-color:#D5EED9;font-size:11px'><strong>{$bsini->_params["$key"]["master_pid"]}</strong></td>
		</tr>			
		<tr>
			<td align='right' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{memory}:</strong></td>
			<td style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>".FormatBytes($bsini->_params["$key"]["master_memory"])."</strong></td>
		</tr>		
		";
			
		if(trim($bsini->_params[$key]["master_cached_memory"])<>null){	
			$pid=$pid."
				<tr>
					<td align='right' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{virtual_memory}:</strong></td>
					<td style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>".FormatBytes($bsini->_params["$key"]["master_cached_memory"])."</strong></td>
				</tr>	
			";		
			}

		}

	if(trim($bsini->_params[$key]["processes_number"])<>null){
		if($bsini->_params[$key]["processes_number"]>0){
			$processes="<tr>
						<td align='right' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{processes}:</strong></td>
						<td style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>".$bsini->_params["$key"]["processes_number"]."</strong></td>
					</tr>";
		}
	}
	
	if(trim($bsini->_params[$key]["threads_socket"])<>null){
		if($bsini->_params[$key]["threads_socket"]>0){
			$processes="<tr>
						<td align='right' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>Threads sockets:</strong></td>
						<td style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>".$bsini->_params["$key"]["threads_socket"]."</strong></td>
					</tr>";
		}
	}
	


	if(trim($bsini->_params[$key]["license_error"])<>null){
		$img="warning42.png";
		$license_error=
		"<tr>
		<td align='right' valign='top' style='background-color:#D5EED9;font-size:11px'><strong>{license_error}:</strong></td>
		<td align='left' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>". texttooltip(substr($bsini->_params[$key]["license_error"],0,13)."...",$bsini->_params[$key]["license_error"],null,nulll,1)."</strong></td>
		</tr>";
	}

	
	$label_version="{version}";
	if($key<>"APP_UPDATEUTILITYRUN"){
		if(!class_exists("IP")){include_once(dirname(__FILE__)."/class.tcpip.inc");}
		$ipClass=new IP();
		if($ipClass->isValid(trim($bsini->_params["$key"]["master_version"]))){
			$label_version="{ip_addr}";
		}
	}
	if(strlen($bsini->_params["$key"]["master_version"])>14){
		$bsini->_params["$key"]["master_version"]=substr($bsini->_params["$key"]["master_version"],0,14)."...";
	}
	
	
	
$version="
		<tr>
			<td align='right' nowrap valign='top' 
			style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>$label_version:</strong></td>
			<td valign='top' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{$bsini->_params["$key"]["master_version"]}</strong></td>
		</tr>";
	
	$bsini->_params["$key"]["master_version"]=str_replace('/DEBUG','',$bsini->_params["$key"]["master_version"]);
	if($bsini->_params["$key"]["master_version"]==null){$version=null;}

	if($bsini->_params["$key"]["why"]<>null){
		$img="warning42.png";
		$textoadd=
		"<tr>
		<td align='right' valign='top' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{info}:</strong></td>
		<td align='left' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong style='color:#d32d2d'>{$bsini->_params["$key"]["why"]}</strong></td>
		</tr>";
	}
	if($bsini->_params["$key"]["other"]<>null){
		$textoadd2=
		"<tr>
		<td align='right' valign='top' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong>{info}:</strong></td>
		<td align='left' style='background-color:#D5EED9;font-size:11px;line-height:auto'><strong style='color:black'>{$bsini->_params["$key"]["other"]}</strong></td>
		</tr>";
	}

	if($textoadd==null){
		if($bsini->_params["$key"]["uptime"]<>null){
			$textoadd="<div style='text-align:right'><i style='font-size:11px'>{since}: {$bsini->_params["$key"]["uptime"]}</i></div>";
		}else{
			$textoadd="<div style='text-align:right'><i style='font-size:11px'>&nbsp;</i></div>";
		}
		
	}

	if(preg_match("#(.+?):(.+)#",$bsini->_params["$key"]["service_name"],$rz)){$change_name="{{$rz[1]}}";}


	
	$master_title="{{$bsini->_params["$key"]["service_name"]}}";
	
	if(preg_match("#^eth[0-9]+#", $bsini->_params["$key"]["service_name"])){
		$master_title=$bsini->_params["$key"]["service_name"];
	}
	
	if($change_name<>null){$master_title=$change_name;}

	if($newInterface==1){
		return ParagrapheTEXT($img, $master_title, "$status<br>$disabled_mini");
	}
	
	
	if($bsini->_params["$key"]["service_cmd"]<>null){
		if($bsini->_params["$key"]["service_disabled"]=="1"){
			$bsini->_params["$key"]["service_cmd"]=urlencode($bsini->_params["$key"]["service_cmd"]);
			$idIMG=md5("{$bsini->_params["$key"]["service_name"]}_icon".time());
			$cmdline="
				<tr>
				<td>&nbsp;</td>
				<td>
				<table style='width:54px'>
					<tr>
						<td width=18px>". imgtootltip("16-run.png","{start}","Loadjs('system.services.cmd.php?APPNAME={$bsini->_params["$key"]["service_name"]}&action=start&cmd={$bsini->_params["$key"]["service_cmd"]}&appcode=$key')")."</td>
						<td width=18px>". imgtootltip("restart-16.png","{restart}","Loadjs('system.services.cmd.php?APPNAME={$bsini->_params["$key"]["service_name"]}&action=restart&cmd={$bsini->_params["$key"]["service_cmd"]}&appcode=$key')")."</td>
						<td width=18px>". imgtootltip("16-stop.png","{stop}","Loadjs('system.services.cmd.php?APPNAME={$bsini->_params["$key"]["service_name"]}&action=stop&cmd={$bsini->_params["$key"]["service_cmd"]}&appcode=$key')")."</td>		
					</tr>		
				</table>
				</td>
			</tr>
			";
		}
	}
	if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$key: ok BUILDING</span><br>\n";}	
$html="

<table style='width:100%;background-color:#D5EED9;font-size:11px;line-height:auto' class='TableRemove TableMarged'>
<tr>
<td width='34px' valign='top' style='background-color:#D5EED9;font-size:11px;line-height:auto;vertical-align:top'>
	<img src='img/$img' id='$idIMG'>
	
</td>
<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto;vertical-align:top'>
	<table style='width:100%;background:transparent;border:0px;padding:0px;background-color:#D5EED9;font-size:11px;line-height:auto' class=TableRemove>
		<tr>
		<td align='right' nowrap style='background-color:#D5EED9;font-size:11px;line-height:auto;vertical-align:top'><strong>$master_title:</td>
		<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto;vertical-align:top'>
			<strong><span style='font-size:12px'>$status</span>$pid_text</strong></td>
		</tr>
		$pid
		$processes
		$emailqueue
		$license_error
		$version
		$icap
		$pattern
		$cmdline
		$textoadd2$disabled
		
	</table>
	</td>
	</tR>
	
</table>
$textoadd";		
if($ADD_ID_DIV){	
	return "<div id='SERVICE-STATUS-ROUND-$key'>".RoundedLightGreen($html)."</div><br>";
}else{
	if($noman==0){
		return "<div id='MAN-STATUS-ROUND-$key'>".RoundedLightGreen($html)."</div><br>";
		
	}
	return "<div id='MAN-STATUS-ROUND-$key'>".RoundedLightGreen($html)."</div><br>";
	
}
}

function DAEMON_STATUS_ROUND_TEXT($image,$title,$text,$linkjs){
	$a="<a href=\"javascript:blur();\" OnClick=\"javascript:$linkjs\" style='font-size:14px;text-decoration:underline'>";
	$html="
	<table style='width:100%;background-color:#D5EED9;font-size:11px;line-height:auto' class=TableRemove>
	<tr>
		<td width=1% valign='top' style='background-color:#D5EED9;font-size:11px;line-height:auto'>" . imgtootltip($image,null,$linkjs)."</td>
		<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto'>
			<table style='width:100%;background:transparent;border:0px;padding:0px;background-color:#D5EED9;font-size:11px;line-height:auto' class=TableRemove>
				<tr>
				<td align='left' nowrap style='background-color:#D5EED9;font-size:14px;line-height:auto'><strong>$a$title</a></strong></td>
				</tr>
				<tr>
				<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto'>
					<div style='font-size:12px;min-height:93px'><strong>$text</strong></td>
				</tr>
			</table>
	</td>
	</tR>
</table>";
		return RoundedLightGreen($html)."<br>";
	
}

function icon_href($img,$java){
	$size=null;
	if(preg_match("#-48\.#", $img)){$size=";width:48px;max-width:48px;";}
	if(preg_match("#-32\.#", $img)){$size=";width:32px;max-width:32px;";}
	if(preg_match("#-24\.#", $img)){$size=";width:24px;max-width:24px;";}
	if($java<>null){$java="<a href=\"javascript:blur();\" OnClick=\"javascript:$java\">";$javaend="</a>";}
	return "$java<img src=\"img/$img\" style=\"border:0px$size\">$javaend";
}

function RoundedLightGreen($text){
	
return "<div style='margin-left:5px;margin:right:5px'>
  <b class=\"RLightGreen\">
  <b class=\"RLightGreen1\"><b></b></b>
  <b class=\"RLightGreen2\"><b></b></b>
  <b class=\"RLightGreen3\"></b>
  <b class=\"RLightGreen4\"></b>
  <b class=\"RLightGreen5\"></b></b>

  <div class=\"RLightGreenfg\" style='padding:7px;'>
   $text
  </div>

  <b class=\"RLightGreen\">
  <b class=\"RLightGreen5\"></b>
  <b class=\"RLightGreen4\"></b>
  <b class=\"RLightGreen3\"></b>
  <b class=\"RLightGreen2\"><b></b></b>
  <b class=\"RLightGreen1\"><b></b></b></b>
</div>
";	
}

function AboutCollapse($explain){
	$tpl=new templates();
	$explain=$tpl->_ENGINE_parse_body("{{$explain}}");
	$id=md5($explain);
	$aboutTitle=$tpl->_ENGINE_parse_body("{about_title}");
	$html="<div id=\"$id\">
			<H3>$aboutTitle</H3>
			<div>$explain</div>
		</div>
		<script>
		$(function() {
        $('#$id').collapse();
   		 });

	
	</script>";
	return $html;
	
	/*
	 * style='font-size:16px;font-weight:bold' 
	 * 		 $('#$id').collapse({show: function(){
                    this.animate({
                        opacity: 'toggle',
                        height: 'toggle'
                    }, 300);
                },
                hide : function() {
                    
                    this.animate({
                        opacity: 'toggle',
                        height: 'toggle'
                    }, 300);
                }
            });
            */
	 
}

function Field_ColorPicker($name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null){
	return "<input type='text' name='$name' id='$name' value='$value' style='$style'>
	<script>
	$(document).ready(function(){
		$('#$name').ColorPicker({
	onSubmit: function(hsb, hex, rgb, el) {
		$(el).val(hex);
		$(el).ColorPickerHide();
	},
	onBeforeShow: function () {
		$(this).ColorPickerSetColor(this.value);
	}
})
.bind('keyup', function(){
	$(this).ColorPickerSetColor(this.value);
});
	});
	</script>
	";
	
}


function Field_array_Hash($Hash,$FieldName,$default=null,$java=null,$class='',$maxlength=0,$style_of_field=null,$disabled=false){
	$tpl=new templates();
	$GLOBALS["AUTO_FORM"][$FieldName]="text";
	if(!is_array($Hash)){return "<input type='hidden' name='$FieldName' value='0' id='$FieldName'>";}
	if(preg_match("#style:(.+)#",$java,$re)){
		$java=null;
		$style_of_field=$re[1];
	}
	
	if(!is_numeric($maxlength)){
		if(preg_match("#style:(.+)#",$maxlength,$re)){
			$style_of_field=$re[1];
		}		
		$maxlength=0;
		
	}
	
	if(preg_match("#style:(.+)#",$class,$re)){
		$class=null;
		$style_of_field=$re[1];
	}		
	
	if($java==null){$java="blur();";}
	
		$java="OnChange=\"javascript:$java\"";	
		
if(!preg_match("#font-size#",$style_of_field)){$style_of_field=$style_of_field.";font-size:12px !important";}
$style_of_field=str_replace(": ", ":", $style_of_field);
if(preg_match("#font-size:([0-9]+)px#", $style_of_field,$re)){
	$style_of_field=$style_of_field. ";font-size:{$re[1]}px !important";
}


	$DISABLED_TOKEN=null;	
	if($class<>"No_STYLE"){$class="class='$class'  style='$style_of_field'";}else{$class=null;}
	
	if($disabled){$DISABLED_TOKEN="DISABLED";}
	$html="
	<!--Field_array_Hash default value=\"$default\" -->
	<select name=\"$FieldName\" id='$FieldName' $java $class $DISABLED_TOKEN>";	
	reset ($Hash);
	$q=false;
	while (list($num,$val)=each($Hash)){	
		$def=null;
		$val=$tpl->_ENGINE_parse_body($val);
		if(!$q){
			if($num==$default){
				$def="selected";
				$q=true;
			}
		}
	
		if($maxlength>0){if (strlen($val)>$maxlength){$val=substr($val,0,$maxlength-3) . "...";}}
		$html=$html ."\t<option value=\"$num\" $def>$val</option>\n";
	}

	if(!isset($_SESSION["DisableJqueryDropDown"])){
		if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}
		$sock=new sockets();
		$_SESSION["DisableJqueryDropDown"]=$sock->GET_INFO("DisableJqueryDropDown");
		if(!is_numeric($_SESSION["DisableJqueryDropDown"])){
			$_SESSION["DisableJqueryDropDown"]=1;
			$sock->SET_INFO("DisableJqueryDropDown",1);
		}
	}
	
	$html=$html ."</select>\n";
	
	if($_SESSION["DisableJqueryDropDown"]==1){return $html;}
	$html=$html ."
	
	<script>
	$(document).ready(function(){
		$('#$FieldName').selectmenu( { style:'dropdown',maxHeight: 300 });
		
	});
	</script>
	";
return $html;}


function url_decode_special_tool($path){
	
	$path=str_replace("%C3%A7","ç",$path);
	$path=str_replace("%C3%C2§","ç",$path);
	$path=str_replace("%5C","\\",$path);
	$path=str_replace("%20"," ",$path);
	$path=str_replace("%0A","\n",$path);
	$path=str_replace("%C2£","£",$path);
	$path=str_replace("%C2§","§",$path);
	$path=str_replace("%C3§","ç",$path);
	$path=str_replace("%E2%82%AC","€",$path);
	$path=str_replace("%C5%93","œ",$path);
	$path=str_replace("%C2%AB","«",$path);
	$path=str_replace("%C2%BB","»",$path);
	
	
	$path=str_replace("%C3%89","É",$path);
	$path=str_replace("%C3%A9","é",$path);
	$path=str_replace("%C3%A8","è",$path);
	$path=str_replace("%C3%AA","ê",$path);
	$path=str_replace("%C3%AB","ë",$path);
	
	$path=str_replace("%C3%A0","à",$path);
	
	$path=str_replace("%C3%B9","ù",$path);
	$path=str_replace("%C3%BB","û",$path);
	$path=str_replace("%C3%A8","è",$path);
	$path=str_replace("%C3%A2","â",$path);
	$path=str_replace("%C3%A4","ä",$path);
	$path=str_replace("%C3%A0","à",$path);
	$path=str_replace("%C3%A0","à",$path);
	$path=str_replace("%C3%A1","á",$path);
	$path=str_replace("%C3%A2","â",$path);
	$path=str_replace("%C3%A3","ã",$path);
	$path=str_replace("%C3%A4","ä",$path);
	$path=str_replace("%C3%A5","å",$path);
	$path=str_replace("%C3%A6","æ",$path);
	
	$path=str_replace("%C3%B4","ô",$path);
	
	$path=str_replace("%C3%AC","ì",$path);
	$path=str_replace("%C3%AD","í",$path);
	$path=str_replace("%C3%AE","î",$path);
	$path=str_replace("%C3%AF","ï",$path);
	$path=str_replace("%C3%B6","ö",$path);
	$path=str_replace("%C3%B0","ð",$path);
	$path=str_replace("%C3%B1","ñ",$path);
	$path=str_replace("%C3%B2","ò",$path);
	$path=str_replace("%C3%B3","ó",$path);
	$path=str_replace("%C3%B4","ô",$path);
	$path=str_replace("%C3%B5","õ",$path);
	$path=str_replace("%C3%B6","ö",$path);

	
	
	$path=str_replace("%C2%A1","¡",$path);
	$path=str_replace("%C2%A2","¢",$path);
	$path=str_replace("%C2%A2","£",$path);
	$path=str_replace("%C2%A4","¤",$path);
	$path=str_replace("%C2%A5","¥",$path);
	$path=str_replace("%C2%A6","¦",$path);
	$path=str_replace("%C2%A7","§",$path);
	$path=str_replace("%C2%A8","¨",$path);
	$path=str_replace("%C2%A9","©",$path);
	$path=str_replace("%C2%AE","®",$path);
	$path=str_replace("%C2%AA","ª",$path);
	$path=str_replace("%C2%AB","«",$path);
	$path=str_replace("%C2%AC","¬",$path);
	$path=str_replace("%C2%AF","¯",$path);
	$path=str_replace("%C2%B0","°",$path);
	$path=str_replace("%C2%B1","±",$path);
	$path=str_replace("%C2%B2","²",$path);
	$path=str_replace("%C2%B3","³",$path);
	$path=str_replace("%C2%B4","´",$path);
	$path=str_replace("%C2%B5","µ",$path);
	$path=str_replace("%C2%B6","¶",$path);
	$path=str_replace("%C2%B7","·",$path);
	$path=str_replace("%C2%B8","¸",$path);
	$path=str_replace("%C2%B9","¹",$path);
	$path=str_replace("%C2%BA","º",$path);
	$path=str_replace("%C2%BB","»",$path);
	$path=str_replace("%C2%BC","¼",$path);
	$path=str_replace("%C2%BD","½",$path);
	$path=str_replace("%C2%BE","¾",$path);
	$path=str_replace("%C2%BF","¿",$path);
	
	$path=str_replace("%C3%80","À",$path);
	$path=str_replace("%C3%81","Á",$path);
	$path=str_replace("%C3%82","Â",$path);
	$path=str_replace("%C3%83","Ã",$path);
	$path=str_replace("%C3%84","Ä",$path);
	$path=str_replace("%C3%85","Å",$path);
	$path=str_replace("%C3%86","Æ",$path);
	$path=str_replace("%C3%87","Ç",$path);
	$path=str_replace("%C3%88","È",$path);
	$path=str_replace("%C3%89","É",$path);
	
	$path=str_replace("%C3%8A","Ê",$path);
	$path=str_replace("%C3%8B","Ë",$path);
	$path=str_replace("%C3%8C","Ì",$path);
	$path=str_replace("%C3%8D","Í",$path);
	$path=str_replace("%C3%8E","Î",$path);
	$path=str_replace("%C3%8F","Ï",$path);

	$path=str_replace("%C3%90","Ð",$path);
	$path=str_replace("%C3%91","Ñ",$path);
	$path=str_replace("%C3%92","Ò",$path);
	$path=str_replace("%C3%93","Ó",$path);
	$path=str_replace("%C3%94","Ô",$path);
	$path=str_replace("%C3%95","Õ",$path);
	$path=str_replace("%C3%96","Ö",$path);
	$path=str_replace("%C3%97","×",$path);
	
	$path=str_replace("%C3%98","Ø",$path);
	$path=str_replace("%C3%99","Ù",$path);
	$path=str_replace("%C3%9A","Ú",$path);
	$path=str_replace("%C3%9B","Û",$path);
	$path=str_replace("%C3%9C","Ü",$path);
	$path=str_replace("%C3%9D","Ý",$path);
	$path=str_replace("%C3%9E","Þ",$path);
	$path=str_replace("%C3%B7","÷",$path);

	
	$path=str_replace("%E9","é",$path);
	$path=str_replace("%E2","â",$path);
	$path=str_replace("%E0","à",$path);
	$path=str_replace("%F9","ù",$path);
	$path=str_replace("%20"," ",$path);
	$path=str_replace("%E8","è",$path);
	$path=str_replace("%E7","ç",$path);
	$path=str_replace("%26","&",$path);
	$path=str_replace("%FC","ü",$path);
	$path=str_replace("%2F","/",$path);
	$path=str_replace("%F6","ö",$path);
	$path=str_replace("%EB","ë",$path);
	$path=str_replace("%EF","ï",$path);
	$path=str_replace("%EE","î",$path);
	$path=str_replace("%EA","ê",$path);

	$path=str_replace("%FB","û",$path);
	$path=str_replace("%u20AC","€",$path);
	$path=str_replace("%u2014","–",$path);
	$path=str_replace("%u2013","—",$path);
	$path=str_replace("%24","$",$path);
	$path=str_replace("%21","!",$path);
	$path=str_replace("%23","#",$path);
	$path=str_replace("%2C",",",$path);
	$path=str_replace("%7E",'~',$path);
	$path=str_replace("%22",'"',$path);
	$path=str_replace("%25",'%',$path);
	$path=str_replace("%27","'",$path);
	$path=str_replace("%F8","ø",$path);
	$path=str_replace("%2C",",",$path);
	$path=str_replace("%3A",":",$path);
	$path=str_replace("%A1","¡",$path);
	$path=str_replace("%A7","§",$path);
	$path=str_replace("%B2","²",$path);
	$path=str_replace("%3B",";",$path);
	$path=str_replace("%3C","<",$path);
	$path=str_replace("%3E",">",$path);
	$path=str_replace("%B5","µ",$path);
	$path=str_replace("%B0","°",$path);
	$path=str_replace("%C3§","ç",$path);
	$path=str_replace("%7C","|",$path);
	$path=str_replace("%5E","^",$path);
	$path=str_replace("%60","`",$path);
	$path=str_replace("%25","%",$path);
	$path=str_replace("%A3","£",$path);
	$path=str_replace("%3D","=",$path);
	$path=str_replace("%3F","?",$path);
	$path=str_replace("%3F","€",$path);
	$path=str_replace("%28","(",$path);
	$path=str_replace("%29",")",$path);
	$path=str_replace("%5B","[",$path);
	$path=str_replace("%5D","]",$path);
	$path=str_replace("%7B","{",$path);
	$path=str_replace("%7D","}",$path);
	$path=str_replace("%2B","+",$path);
	$path=str_replace("%40","@",$path);
	$path=str_replace("%09","\t",$path);
	$path=str_replace("%u0430","а",$path);
	$path=str_replace("%u0431","б",$path);
	$path=str_replace("%u0432","в",$path);
	$path=str_replace("%u0433","г",$path);
	$path=str_replace("%u0434","д",$path);
	$path=str_replace("%u0435","е",$path);
	$path=str_replace("%u0451","ё",$path);
	$path=str_replace("%u0436","ж",$path);
	$path=str_replace("%u0437","з",$path);
	$path=str_replace("%u0438","и",$path);
	$path=str_replace("%u0439","й",$path);
	$path=str_replace("%u043A","к",$path);
	$path=str_replace("%u043B","л",$path);
	$path=str_replace("%u043C","м",$path);
	$path=str_replace("%u043D","н",$path);
	$path=str_replace("%u043E","о",$path);
	$path=str_replace("%u043F","п",$path);
	$path=str_replace("%u0440","р",$path);
	$path=str_replace("%u0441","с",$path);
	$path=str_replace("%u0442","т",$path);
	$path=str_replace("%u0443","у",$path);
	$path=str_replace("%u0444","ф",$path);
	$path=str_replace("%u0445","х",$path);
	$path=str_replace("%u0446","ц",$path);
	$path=str_replace("%u0447","ч",$path);
	$path=str_replace("%u0448","ш",$path);
	$path=str_replace("%u0449","щ",$path);
	$path=str_replace("%u044A","ъ",$path);
	$path=str_replace("%u044B","ы",$path);
	$path=str_replace("%u044C","ь",$path);
	$path=str_replace("%u044D","э",$path);
	$path=str_replace("%u044E","ю",$path);
	$path=str_replace("%u044F","я",$path);	
	return $path;
}

function percent_encoding($value){
	$value=str_replace("!", "%21", $value);
	$value=str_replace("#", "%23", $value);
	$value=str_replace("$", "%24", $value);
	$value=str_replace("&", "%26", $value);
	$value=str_replace("'", "%27", $value);
	$value=str_replace("(", "%28", $value);
	$value=str_replace(")", "%29", $value);
	$value=str_replace("*", "%2A", $value);
	$value=str_replace("+", "%2B", $value);
	$value=str_replace(",", "%2C", $value);
	$value=str_replace("/", "%2F", $value);
	$value=str_replace(":", "%3A", $value);
	$value=str_replace(";", "%3B", $value);
	$value=str_replace("=", "%3D", $value);
	$value=str_replace("?", "%3F", $value);
	$value=str_replace("@", "%40", $value);
	$value=str_replace("[", "%5B", $value);
	$value=str_replace("]", "%5D", $value);
	return $value;
}
function autofs_escape_chars($value){
	$value=str_replace("!", "\\!", $value);
	$value=str_replace("#", "\\#", $value);
	$value=str_replace("$", "\\$", $value);
	$value=str_replace("&", "\\&", $value);
	$value=str_replace("'", "\\'", $value);
	$value=str_replace("(", "\\(", $value);
	$value=str_replace(")", "\\)", $value);
	$value=str_replace("*", "\\*", $value);
	$value=str_replace("+", "\\+", $value);
	$value=str_replace(",", "\\,", $value);
	$value=str_replace("/", "\\/", $value);
	$value=str_replace(":", "\\:", $value);
	$value=str_replace(";", "\\;", $value);
	$value=str_replace("=", "\\=", $value);
	$value=str_replace("?", "\\?", $value);
	$value=str_replace("@", "\\@", $value);
	return $value;
}

class htmltools_inc{
	
	function replace_accents($s) {
		$source=$s;
	    $s = htmlentities($s);
	   	if($s==null){$s=$source;$s=utf8_encode($s); $s = htmlentities($s);}
	    $s = preg_replace ('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|ring);/', '$1', $s);
	    $s=str_replace("&Ntilde;","N",$s);
	    $s=str_replace("&ntilde;","n",$s);
	    $s=str_replace("&Oacute;","O",$s);
	    $s=str_replace("&oacute;","O",$s);
	    $s=str_replace("&Ograve;","O",$s);
	    $s=str_replace("&ograve;","o",$s);
	    $s=str_replace("&Ocirc;","O",$s);
	    $s=str_replace("&ocirc;","o",$s);
	    $s=str_replace("&Ouml;","O",$s);
	    $s=str_replace("&ouml;","o",$s);
	    $s=str_replace("&Otilde;","O",$s);
	    $s=str_replace("&otilde;","o",$s);
	    $s=str_replace("&Oslash;","O",$s);
	    $s=str_replace("&oslash;","o",$s);
	    $s=str_replace("&szlig;","b",$s);
	    $s=str_replace("&Thorn;","T",$s);
	    $s=str_replace("&thorn;","t",$s);
	    $s=str_replace("&Uacute;","U",$s);
	    $s=str_replace("&uacute;","u",$s);
	    $s=str_replace("&Ugrave;","U",$s);
	    $s=str_replace("&ugrave;","u",$s);
	    $s=str_replace("&Ucirc;","U",$s);
	    $s=str_replace("&ucirc;","u",$s);
	    $s=str_replace("&Uuml;","U",$s);
	    $s=str_replace("&uuml;","u",$s);
	    $s=str_replace("&Yacute;","Y",$s);
	    $s=str_replace("&yacute;","y",$s);
	    $s=str_replace("&yuml;","y",$s);
	    $s=str_replace("&Icirc;","I",$s);
	    $s=str_replace("&icirc;","i",$s);  
		$s = html_entity_decode($s);
		
		return $s;		
	}	
	
	function StripSpecialsChars($pattern){
		
		$pattern=$this->replace_accents($pattern);
		$pattern=str_replace(" ","",$pattern);
		$pattern=str_replace(".","",$pattern);
		$pattern=str_replace("-","",$pattern);
		$pattern=str_replace("&","",$pattern);
		$pattern=str_replace(",","",$pattern);
		$pattern=str_replace(";","",$pattern);
		$pattern=str_replace("%","",$pattern);
		$pattern=str_replace("*","",$pattern);
		$pattern=str_replace("ø","",$pattern);
		$pattern=str_replace("$","",$pattern);
		$pattern=str_replace("/","",$pattern);
		$pattern=str_replace("\\","",$pattern);
		$pattern=str_replace("?","",$pattern);
		$pattern=str_replace("µ","",$pattern);
		$pattern=str_replace("£","",$pattern);
		$pattern=str_replace(")","",$pattern);
		$pattern=str_replace("(","",$pattern);
		$pattern=str_replace("[","",$pattern);
		$pattern=str_replace("]","",$pattern);
		$pattern=str_replace("#","",$pattern);
		$pattern=str_replace("'","",$pattern);
		$pattern=str_replace("\"","",$pattern);
		$pattern=str_replace("+","_",$pattern);
		$pattern=substr($pattern,0,20);		
		return $pattern;
		
	}

	function LanguageArray(){
		$lang["en"]="English";
		$lang["fr"]="Francais";
		$lang["po"]="Portugues";
		$lang["br"]="Brazilian";
		$lang["es"]="Espanol";
		$lang["it"]="Italiano";
		$lang["de"]="Deutsch";
		$lang["pol"]="Polish";
		return $lang;
	}
	
	
	
}


function WEBFILTERING_TOP_MENU(){
	$users=new usersMenus();
	
	if($users->AsDansGuardianAdministrator){
	$top_menu[]=texttooltip("{service_status}","position:bottom:{dashboard_webfiltering_explain}","GoToUfdbguardMain()");
	$top_menu[]=texttooltip("{webfiltering_rules}","position:bottom:{webfiltering_rules_text}",
			"GoToUfdbguardRules()");
	
	$top_menu[]=texttooltip("{your_categories}","position:bottom:{your_categories_explain}",
			"GotoYourcategories()");
	
	
	
	$top_menu[]=texttooltip("{unlock_rules}","position:bottom:{unlock_rules_explain_text}",
			"GotoUfdbUnlockPages()");
	
	$top_menu[]=texttooltip("{paranoid_mode}","position:bottom:{paranoid_squid_mode_explain}",
			"GotoParanoidMode()");
	
	
	
	
	}
	
	
	
	return @implode("&nbsp;|&nbsp;", $top_menu);
	
	
}


?>