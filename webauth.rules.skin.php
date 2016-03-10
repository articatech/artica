<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.wifidog.settings.inc');
include_once('ressources/class.wifidog.templates.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["ruleid"])){Save();exit;}



Page();
function Page(){
	$ruleid=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new wifidog_settings($ruleid);
	$wifidog_templates=new wifidog_templates($ruleid);
	$this_feature_is_disabled_corp_license=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	$CORP=0;
	if($users->CORP_LICENSE){$CORP=1;}
	
	$USE_TERMS=intval($sock->GET_INFO("USE_TERMS"));
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
			
	<tr>
		<td class=legend style='font-size:22px'>{page_size}:</td>
		<td>".Field_text("SizePage-$t",$wifidog_templates->SizePage,"font-size:22px;width:230px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{margin}:</td>
		<td>".Field_text("MarginPage-$t",$wifidog_templates->MarginPage,"font-size:22px;width:230px")."</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:22px'>{font_family}:</td>
		<td>	<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:40px;border:5px solid #8E8E8E;
overflow:auto;font-size:18px !important' id='FontFamily-$t'>$wifidog_templates->FontFamily</textarea></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{font_size}:</td>
		<td>".Field_text("FontSize-$t",$wifidog_templates->FontSize,"font-size:22px;width:230px")."</td>
	</tr>						
	<tr>
		<td class=legend style='font-size:22px'>{background_color}:</td>
		<td>".Field_ColorPicker("backgroundColor-$t",$wifidog_templates->backgroundColor,"font-size:22px;width:230px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{font_color}:</td>
		<td>".Field_ColorPicker("FontColor-$t",$wifidog_templates->FontColor,"font-size:22px;width:230px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{links_color}:</td>
		<td>".Field_ColorPicker("LinksColor-$t",$wifidog_templates->LinksColor,"font-size:22px;width:230px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{font_color} ({title2}):</td>
		<td>".Field_ColorPicker("TitleColor-$t",$wifidog_templates->TitleColor,"font-size:22px;width:230px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{font_size} ({title2}):</td>
		<td>".Field_text("TitleFontSize-$t",$wifidog_templates->TitleFontSize,"font-size:22px;width:230px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{font_size} ({subtitle}):</td>
		<td>".Field_text("SubTitleFontSize-$t",$wifidog_templates->SubTitleFontSize,"font-size:22px;width:230px")."</td>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:22px'>{font_color} ({button}):</td>
		<td>".Field_ColorPicker("ButtonFontColor-$t",$wifidog_templates->ButtonFontColor,"font-size:22px;width:230px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{font_size} (button):</td>
		<td style='font-size:22px'>". Field_text("Button2014FontSize-$t",$wifidog_templates->Button2014FontSize,"font-size:22px;width:230px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color} (button):</td>
		<td style='font-size:22px'>". Field_ColorPicker("Button2014Bgcolor-$t",$wifidog_templates->Button2014Bgcolor,"font-size:22px;width:230px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{background_color} (button/Over):</td>
		<td style='font-size:22px'>". Field_ColorPicker("Button2014BgcolorOver-$t",$wifidog_templates->Button2014BgcolorOver,"font-size:22px;width:230px")."</td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{border_color} (button):</td>
		<td style='font-size:22px'>". Field_ColorPicker("Button2014BgcolorBorder-$t",$wifidog_templates->Button2014BgcolorBorder,"font-size:22px;width:230px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{spacer} ({button}):</td>
		<td style='font-size:22px'>". Field_ColorPicker("SpacerButton-$t",$wifidog_templates->SpacerButton,"font-size:22px;width:230px")."</td>
	</tr>	
		<tr>
		<td class=legend style='font-size:22px'>{font_color} ({inside_form}):</td>
		<td>".Field_ColorPicker("FormFontColor-$t",$wifidog_templates->FormFontColor,"font-size:22px;width:230px")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:22px'>{forms_design}:</td>
		<td>	<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:250px;border:5px solid #8E8E8E;
overflow:auto;font-size:18px' id='FormStyle-$t'>$wifidog_templates->FormStyle</textarea></td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:22px'>{FieldsStyle}:</td>
		<td>	<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:250px;border:5px solid #8E8E8E;
overflow:auto;font-size:18px' id='FieldsStyle-$t'>$wifidog_templates->FieldsStyle</textarea></td>
	</tr>				

	<tr>
		<td class=legend style='font-size:22px'>{LegendsStyle}:</td>
		<td>	<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:250px;border:5px solid #8E8E8E;
overflow:auto;font-size:18px' id='LegendsStyle-$t'>$wifidog_templates->LegendsStyle</textarea></td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{TextErrorStyle}:</td>
		<td>	<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:250px;border:5px solid #8E8E8E;
overflow:auto;font-size:18px' id='TextErrorStyle-$t'>$wifidog_templates->TextErrorStyle</textarea></td>
	</tr>		
	
	
				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","42px")."</td>
	</tr>
	</table>
	<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#HOSTPOT_RULES').flexReload();
	}	
	
	function Save$t(){
		var CORP=$CORP;
		if(CORP==0){alert('$this_feature_is_disabled_corp_license');return;}		
		var XHR = new XHRConnection();
		XHR.appendData('ruleid',$ruleid);
		XHR.appendData('MarginPage',encodeURIComponent(document.getElementById('MarginPage-$t').value));
		XHR.appendData('SizePage',encodeURIComponent(document.getElementById('SizePage-$t').value));
		XHR.appendData('backgroundColor',encodeURIComponent(document.getElementById('backgroundColor-$t').value));
		XHR.appendData('FontColor',encodeURIComponent(document.getElementById('FontColor-$t').value));
		XHR.appendData('TitleColor',encodeURIComponent(document.getElementById('TitleColor-$t').value));
		XHR.appendData('TitleFontSize',encodeURIComponent(document.getElementById('TitleFontSize-$t').value));
		XHR.appendData('FontFamily',encodeURIComponent(document.getElementById('FontFamily-$t').value)); 
		XHR.appendData('FontSize',encodeURIComponent(document.getElementById('FontSize-$t').value));
		XHR.appendData('FormStyle',encodeURIComponent(document.getElementById('FormStyle-$t').value));
		XHR.appendData('FieldsStyle',encodeURIComponent(document.getElementById('FieldsStyle-$t').value));
		XHR.appendData('LegendsStyle',encodeURIComponent(document.getElementById('LegendsStyle-$t').value));
		XHR.appendData('SpacerButton',encodeURIComponent(document.getElementById('SpacerButton-$t').value));
		XHR.appendData('ButtonFontColor',encodeURIComponent(document.getElementById('ButtonFontColor-$t').value));
		XHR.appendData('SubTitleFontSize',encodeURIComponent(document.getElementById('SubTitleFontSize-$t').value));
		XHR.appendData('LinksColor',encodeURIComponent(document.getElementById('LinksColor-$t').value));
		XHR.appendData('TextErrorStyle',encodeURIComponent(document.getElementById('TextErrorStyle-$t').value));
		XHR.appendData('FormFontColor',encodeURIComponent(document.getElementById('FormFontColor-$t').value));
		
		
		
		
		XHR.appendData('Button2014FontSize',encodeURIComponent(document.getElementById('Button2014FontSize-$t').value));
		XHR.appendData('Button2014Bgcolor',encodeURIComponent(document.getElementById('Button2014Bgcolor-$t').value));
		XHR.appendData('Button2014BgcolorOver',encodeURIComponent(document.getElementById('Button2014BgcolorOver-$t').value));
		XHR.appendData('Button2014BgcolorBorder',encodeURIComponent(document.getElementById('Button2014BgcolorBorder-$t').value));
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	$sock=new wifidog_settings($_POST["ruleid"]);
	unset($_POST["ruleid"]);
	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$sock->SET_INFO($key, $value);
		
	}
	
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	
}