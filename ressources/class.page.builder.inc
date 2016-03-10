<?php

class pagebuilder{
	var $language;
	var $js_add=null;
	var $head=null;
	
	function pagebuilder(){
		if(!function_exists("writelogs")){include_once(dirname(__FILE__)."/logs.inc");}
		
	}
	
	
	function buildHeads($array){
		if($GLOBALS["EXECUTED_AS_ROOT"]){return;}
		if(!isset( $GLOBALS["ADD_HTML_HEADER"])){ $GLOBALS["ADD_HTML_HEADER"]="";}
		if(!isset( $array["TITLE"])){ $array["TITLE"]="";}
		if(!isset( $array["css"])){ $array["css"]="";}
		if(!isset( $array["INSERT"])){ $array["INSERT"]="";}
		if(!isset( $array["JS"])){ $array["JS"]=array();}
		if(!isset( $array["refresh"])){ $array["refresh"]=0;}
		
		
		
		$js_addon="";
		$js_addon3="";
		$js_addon_2="";
		$javascripts="";
		$html="";
		$mouse_position_js="";
		
		$backspace="\n\t\t";
		


		if(substr($this->language,strlen($this->language),1)=='/'){
			$this->language=substr($this->language,0,strlen($this->language)-1);
		}
		$page=$this->CurrentPageName();
		if(is_file(dirname(__FILE__) . "/language/$this->language/$page.js")){
		 	$js_addon="$backspace<script type=\"text/javascript\" language=\"JavaScript\" src=\"/ressources/language/$this->language/$page.js\"></script>";
		}
		
		if($array["INSERT"]<>null){
			$js_addon3="$backspace<script type=\"text/javascript\">\n\t".file_get_contents($array["INSERT"]) . "\n</script>";
		}		
		
		if($this->js_add<>null){
			$js_addon_2="$backspace<script type=\"text/javascript\" language=\"JavaScript\" src=\"/ressources/language/$this->language/$this->js_add\"></script>";
		}
		
		if($array["css"]==null){
			$array["css"]="$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"/img/content.css\"/>$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"img/forms.css\"/>";
			
		}
		
		
		if($array["refresh"]>0){$refresh="<meta http-equiv=\"refresh\" content=\"{$array["refresh"]}\">";}
		
		$tafelpages["domains.edit.group.php"]=true;
		$tafelpages["artica.backup.index.php"]=true;
		$tafelpages["SambaBrowse.php"]=true;
		$tafelpages["fileshares.index.php"]=true;
			
			
			
			
			if(is_array($array["JS"])){
				while (list ($num, $val) = each ($array["JS"]) ){
				if($val==null){continue;}
				$javascripts=$javascripts . "$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"$val\"></script>";
				}
			}else{$javascripts=$array["JS"];}
			
		
			$html=$html . $backspace.$mouse_position_js;
			
			//$html=$html . "<link href='/css.php'    rel='styleSheet'  type='text/css' />";
			$html=$html . "$backspace<!-- Artica javascript  -->";
			$html=$html . $this->jsArtica();
			$html=$html . "$backspace<!-- js Artica  -->";
			$html=$html . "$backspace<!--  css files  -->\n";
			$html=$html .  $GLOBALS["ADD_HTML_HEADER"]."\n".$this->headcss();
			
			$html=$html . $js_addon;
			$html=$html . $js_addon_2;
			$html=$html . $js_addon3;
			$html=$html . "$javascripts\n";
			//$html=$html . $this->YahooInstances();
			
			$html=str_replace('<script type="text/javascript" language="javascript" src=""></script>','',$html);
			$this->head=$html;
			return $this->head;		
		
		
	}
	
	private function CurrentPageName(){
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
		return $phpPage;
	}  	
	
	public function jqueryPath(){
		$jquerys[]="jquery-1.8.3.js";
		$jquerys[]="jquery-1.8.0.min.js";
		//$jquerys[]="jquery-1.7.2.min.js";
		$jquerys[]="jquery-1.6.2.min.js";
		$jquerys[]="jquery-1.6.1.min.js";
		$jquerys[]="jquery-1.6.min.js";
		$jquerys[]="jquery-1.5.1.min.js";
		$jquerys[]="jquery-1.4.4.min.js";
		$jquerys[]="jquery-1.4.2.min.js";
			
		while (list ($num, $val) = each ($jquerys) ){
			if(is_file("/usr/share/artica-postfix/js/$val")){return "js/$val";}
		}

		
		
	}
	
	
	public function jQueryCustom(){
		//$lqueryCustom[]="jquery-ui-1.9.2.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.8.22.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.8.16.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.8.13.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.8.10.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.8.6.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.8.custom.min.js";
		$lqueryCustom[]="jquery-ui-1.7.2.custom.js";
			
		while (list ($num, $val) = each ($lqueryCustom) ){
		
			if(is_file("/usr/share/artica-postfix/js/$val")){return "js/$val";}
		}		
		
	}
	
	
	public function jsArtica($template=null){
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$filename=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}
		}
		
		
		if(isset($GLOBALS[__CLASS__][$template][__FUNCTION__])){return $GLOBALS[__CLASS__][$template][__FUNCTION__];}
		if(!isset($GLOBALS["JS_HEAD_PREPREND"])){$GLOBALS["JS_HEAD_PREPREND"]=null;}
		if(!isset($GLOBALS["JS_NO_CACHE"])){$GLOBALS["JS_NO_CACHE"]=false;}
		if($template<>null){$this->SetTemplateMemory($template);}
		
		if(!$GLOBALS["JS_NO_CACHE"]){
			if($GLOBALS["EXECUTED_AS_ROOT"]){
				if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] EXECUTED_AS_ROOT = true...\n";}
				return;}
			$mem_id=__CLASS__.__FUNCTION__.$_SERVER['HTTP_USER_AGENT'];
				if(isset($GLOBALS[$mem_id])){if($GLOBALS[$mem_id]<>null){
					if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] return GLOBAL[$mem_id]\n";}
					return $GLOBALS[$mem_id];
				}
			}
			
			if(isset($_SESSION["CACHE_PAGE"][$mem_id])){if($_SESSION["CACHE_PAGE"][$mem_id]<>null){
				if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] return SESSION[$mem_id]\n";}
				return $_SESSION["CACHE_PAGE"][$mem_id];
				}
			}
		}
			if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] Building JS...\n";}
			$backspace="\t\t";
			$page=$this->CurrentPageName();
			$mouse="";
			$tafel=null;
			$jsTable=array();
			$html="\n$backspace<!--Called by  $filename/$function/$line -->\n" ;
			$html="\n$backspace<!-- Template $template  -->\n" ;
			$html=$html."$backspace<!-- {$_SERVER['HTTP_USER_AGENT']}  -->\n" ;
			$html=$html."$backspace<!-- Prepend:{$GLOBALS['JS_HEAD_PREPREND']}  -->\n" ;
			
			
			if(preg_match("#curl\/[0-9\.]+#",$_SERVER['HTTP_USER_AGENT'])){
				$IP=$_SERVER["REMOTE_ADDR"];
				$file=basename($_SERVER["SCRIPT_FILENAME"]);
				if($file=="miniadm.php"){die();}

			}
			$jquerys[]="jquery-1.8.3.js";
			$jquerys[]="jquery-1.8.0.min.js";
			//$jquerys[]="jquery-1.7.2.min.js";
			$jquerys[]="jquery-1.6.2.min.js";
			$jquerys[]="jquery-1.6.1.min.js";
			$jquerys[]="jquery-1.6.min.js";
			$jquerys[]="jquery-1.5.1.min.js";
			$jquerys[]="jquery-1.4.4.min.js";
			$jquerys[]="jquery-1.4.2.min.js";
			
			while (list ($num, $val) = each ($jquerys) ){
				if(is_file("/usr/share/artica-postfix/js/$val")){$jqueryok=$val;break;}
			}
			
			//$lqueryCustom[]="jquery-ui-1.9.2.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.8.22.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.8.16.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.8.13.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.8.10.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.8.6.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.8.custom.min.js";
			$lqueryCustom[]="jquery-ui-1.7.2.custom.js";
			
			while (list ($num, $val) = each ($lqueryCustom) ){
				
				if(is_file("/usr/share/artica-postfix/js/$val")){$jquery_custom=$val;break;}
			}			
			
			if($GLOBALS["VERBOSE"]){echo  "Memory: ".round(memory_get_usage(true)/1024)." Ko\n<br>";}
			
			$jsTable[]="/js/$jqueryok";
			$jsTable[]="/js/$jquery_custom";
			$jsTable[]="/default.js";
			$jsTable[]="/js/rloader1.5.4_min.js";
			$jsTablesrc[]="/js/md5.js";
			$jsTablesrc[]="/js/float-barr.js";
			$jsTablesrc[]="/TimersLogs.js";
			$jsTablesrc[]="/js/artica_confapply.js";
			$jsTablesrc[]="/js/edit.user.js";
			$jsTablesrc[]="/js/cookies.js";
			
			$jsTablesrc[]="/js/jqueryFileTree.js";
			$jsTablesrc[]="/js/jquery.easing.1.3.js";
			$jsTablesrc[]="/js/thickbox-compressed.js";
			$jsTablesrc[]="/js/jquery.simplemodal-1.3.3.min.js";
			$jsTablesrc[]="/js/jquery.jgrowl_minimized.js";
			$jsTablesrc[]="/js/jquery.cluetip.js";
			$jsTablesrc[]="/js/jquery.blockUI.js";
			$jsTablesrc[]="/js/jquery.treeview.min.js";
			$jsTablesrc[]="/js/jquery.treeview.async.js";
			$jsTablesrc[]="/js/jquery.tools.min.js";
			$jsTablesrc[]="/js/jquery.qtip.js";
			$jsTablesrc[]="/js/jquery.kwicks-1.5.1.pack.js";
			$jsTablesrc[]="/js/jquery.auto-complete.min.js";
			$jsTablesrc[]="/js/flexigrid.pack.js";
			$jsTablesrc[]="/js/jquery-ui-timepicker-addon.js";
			$jsTablesrc[]="/js/ui.selectmenu.js";
			//$jsTablesrc[]="/js/jquery.collapse.js";
			$jsTablesrc[]="/js/jquery.cookie.js";
			$jsTablesrc[]="/js/fileuploader.js";
			
			$jsTablesrc[]="/js/modules/exporting.js";
			$jsTablesrc[]="/js/mobiscroll-2.1.custom.min.js";
			$jsTablesrc[]="/js/tiny_mce/tinymce.min.js";
			$jsTablesrc[]="/js/colorpicker.js";
			$jsTablesrc[]="/js/jquery.watermark.min.js";
			$jsTablesrc[]="/js/jquery.uilock.min.js";
			$jsTablesrc[]="/js/jquery.blockUI.js";
			$jsTablesrc[]="/js/justgage.1.0.1.min.js";
			$jsTablesrc[]="/js/raphael.2.1.0.min.js";
			$jsTablesrc[]="/js/jquery.bxslider.min.js";
			$jsTablesrc[]="/js/jquery.tooltipster.min.js";
			
			$jsTablesrc[]="/bootstrap/js/bootstrap-tab.js";
			$jsTablesrc[]="/bootstrap/js/bootstrap-tooltip.js";
			$jsTablesrc[]="/bootstrap/js/bootstrap-button.js";
			
			$chrome[]=$this->favicon($this->GetTemplateMemory());

			if(($this->CurrentPageName()=="logon.php") OR ($this->CurrentPageName()=="logoff.php") OR ($this->CurrentPageName()=="404.php")){
				$tafel=null;
				$floatbarr=null;
				$TimersLogs=null;
				$artica_confapply=null;
				$editusers=null;
				}
			
			// view http://headjs.com/
			$f[]="\n{$backspace}<script>";
			if(!isset($GLOBALS["JS_HEAD_PREPREND"])){$GLOBALS["JS_HEAD_PREPREND"]=null;}
			while (list ($num, $func) = each ($jsTable) ){
				$f[]="{$backspace}head.js('$func');";
				$chrome[]="$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}$func\"></script>";
				
			}
			
			while (list ($num, $func) = each ($jsTablesrc) ){
				$srcs[]="{src:'$func'}";
				
			}
			$chrome[]="$backspace<script type=\"text/javascript\" language=\"javascript\">{$backspace}$.rloader([ ".@implode(",\n\t", $srcs)." ]);</script>";
			$chrome[]="$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.blockUI.js\"></script>";
			$chrome[]="$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"/js/highcharts.js\"></script>";
			$chrome[]="$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>";
			$chrome[]="$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>";
			
			
			
			
			$f[]="{$backspace}if(head.browser.ie){head.js('/mouse_ie.js');}";
			$f[]="{$backspace}</script>\n";
			
			$oldMethod=false;
			if(preg_match("#Chrome\/8#",$_SERVER['HTTP_USER_AGENT'])){$oldMethod=true;}
			if(preg_match("#Safari\/#",$_SERVER['HTTP_USER_AGENT'])){$oldMethod=true;}
			if(preg_match("#Firefox\/4\.0b[0-9]+#",$_SERVER['HTTP_USER_AGENT'])){$oldMethod=true;}
			$oldMethod=true;
			$html=$html . "\n<script type=\"text/javascript\">window.history.forward(0);function noBack(){window.history.forward(0);}</script>\n";
			$html=$html . "\n<script type=\"text/javascript\">var bajb_backdetect={Version:'1.0.0',Description:'Back Button Detection',Browser:{IE:!!(window.attachEvent&&!window.opera),Safari:navigator.userAgent.indexOf('Apple')>-1,Opera:!!window.opera},FrameLoaded:0,FrameTry:0,FrameTimeout:null,OnBack:function(){alert('Back Button Clicked')},BAJBFrame:function(){var BAJBOnBack=document.getElementById('BAJBOnBack');if(bajb_backdetect.FrameLoaded>1){if(bajb_backdetect.FrameLoaded==2){bajb_backdetect.OnBack();history.back()}}bajb_backdetect.FrameLoaded++;if(bajb_backdetect.FrameLoaded==1){if(bajb_backdetect.Browser.IE){bajb_backdetect.SetupFrames()}else{bajb_backdetect.FrameTimeout=setTimeout(\"bajb_backdetect.SetupFrames();\",700)}}},SetupFrames:function(){clearTimeout(bajb_backdetect.FrameTimeout);var BBiFrame=document.getElementById('BAJBOnBack');var checkVar=BBiFrame.src.substr(-11,11);if(bajb_backdetect.FrameLoaded==1&&checkVar!=\"HistoryLoad\"){BBiFrame.src=\"blank.html?HistoryLoad\"}else{if(bajb_backdetect.FrameTry<2&&checkVar!=\"HistoryLoad\"){bajb_backdetect.FrameTry++;bajb_backdetect.FrameTimeout=setTimeout(\"bajb_backdetect.SetupFrames();\",700)}}},SafariHash:'false',Safari:function(){if(bajb_backdetect.SafariHash=='false'){if(window.location.hash=='#b'){bajb_backdetect.SafariHash='true'}else{window.location.hash='#b'}setTimeout(\"bajb_backdetect.Safari();\",100)}else if(bajb_backdetect.SafariHash=='true'){if(window.location.hash==''){bajb_backdetect.SafariHash='back';bajb_backdetect.OnBack();history.back()}else{setTimeout(\"bajb_backdetect.Safari();\",100)}}},Initialise:function(){if(bajb_backdetect.Browser.Safari){setTimeout(\"bajb_backdetect.Safari();\",600)}else{document.write('<iframe src=\"blank.html\" style=\"display:none;\" id=\"BAJBOnBack\" onunload=\"alert(\'de\')\" onload=\"bajb_backdetect.BAJBFrame();\"></iframe>')}}};bajb_backdetect.Initialise();</script>";
			$html=$html . "\n<script type=\"text/javascript\">window.location.replace('#');bajb_backdetect.OnBack = function(){alert('This application is not compatible using forward and back browsers buttons\\nTry to not use it!');window.location.replace('#');}</script>";
			$html=$html . "\n<script type=\"text/javascript\">if(window.history.forward(1) != null) window.history.forward(1);</script>";
			if($oldMethod){
				$html=$html . "$backspace".@implode("\n",$chrome);
				
			}else{
				$html=$html . "\n$backspace<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/head.min.js\"></script>";
				$html=$html . "$backspace".@implode("\n",$f);				
			}
			$GLOBALS[__CLASS__][__FUNCTION__]=$html;
			return "$html\n";		
	}
	
	function headcss(){
		if($GLOBALS["EXECUTED_AS_ROOT"]){return;}
		$backspace="\n\t\t";
		$Template=$this->GetTemplateMemory();
		
		
		if(!isset($_COOKIE["artica-template"])){$_COOKIE["artica-template"]="";}
		//if(strlen($_SESSION["ARTICA_HEAD_TEMPLATE"])>100){return $backspace."<!-- Session head -->".$_SESSION["ARTICA_HEAD_TEMPLATE"];}
		if($_COOKIE["artica-template"]==null){$_COOKIE["artica-template"]="default";}
		if($_COOKIE["artica-template"]=='!nil'){$_COOKIE["artica-template"]="default";}
		$template_file="index.html";
		$GLOBALS["artica-template"]=$_COOKIE["artica-template"];
		if(!is_file(dirname(__FILE__)."/templates/{$_COOKIE["artica-template"]}/$template_file")){
			$GLOBALS["artica-template"]="default";
		}
		
	
		if(isset($GLOBALS["CHANGE_TEMPLATE"])){$template_file=$GLOBALS["CHANGE_TEMPLATE"];}
		
			$favicon=$this->favicon();
			$html=$backspace."<!-- template used :".dirname(__FILE__)."/templates/{$_COOKIE["artica-template"]}/$template_file-->";
			if($favicon<>null){$html=$html.$backspace.$favicon;}
			
			if(!isset($GLOBALS["CSS_FILES"]["menu.css"])){$GLOBALS["CSS_FILES"]["menu.css"]="menu.css";}
			if(!isset($GLOBALS["CSS_FILES"]["contact.css"])){$GLOBALS["CSS_FILES"]["contact.css"]="contact.css";}
			if(!isset($GLOBALS["CSS_FILES"]["style.css"])){$GLOBALS["CSS_FILES"]["style.css"]="style.css";}
			if(!isset($GLOBALS["CSS_FILES"]["styles_rounded.css"])){$GLOBALS["CSS_FILES"]["styles_rounded.css"]="styles_rounded.css";}
			if(!isset($GLOBALS["CSS_FILES"]["styles_tabs.css"])){$GLOBALS["CSS_FILES"]["styles_tabs.css"]="styles_tabs.css";}
			if(!isset($GLOBALS["CSS_FILES"]["styles_forms.css"])){$GLOBALS["CSS_FILES"]["styles_forms.css"]="styles_forms.css";}
			if(!isset($GLOBALS["CSS_FILES"]["styles_tables.css"])){$GLOBALS["CSS_FILES"]["styles_tables.css"]="styles_tables.css";}
			if(!isset($GLOBALS["CSS_FILES"]["titles.css"])){$GLOBALS["CSS_FILES"]["titles.css"]="titles.css";}
			if(!isset($GLOBALS["CSS_FILES"]["nav.css"])){$GLOBALS["CSS_FILES"]["nav.css"]="nav.css";}
			if(!isset($GLOBALS["CSS_FILES"]["menus_top.css"])){$GLOBALS["CSS_FILES"]["menus_top.css"]="menus_top.css";}
			if(!isset($GLOBALS["CSS_FILES"]["blurps.css"])){$GLOBALS["CSS_FILES"]["blurps.css"]="blurps.css";}
			if(!isset($GLOBALS["CSS_FILES"]["isoqlog.css"])){$GLOBALS["CSS_FILES"]["isoqlog.css"]="isoqlog.css";}
			if(!isset($GLOBALS["CSS_FILES"]["jquery.bxslider.css"])){$GLOBALS["CSS_FILES"]["jquery.bxslider.css"]="jquery.bxslider.css";}
			
			
			
			
			if((CurrentPageName()=="logon.php") OR (CurrentPageName()=="logoff.php") OR (CurrentPageName()=="404.php")){
				unset($GLOBALS["CSS_FILES"]["menu.css"]);
				unset($GLOBALS["CSS_FILES"]["style.css"]);
				unset($GLOBALS["CSS_FILES"]["isoqlog.css"]);
				unset($GLOBALS["CSS_FILES"]["blurps.css"]);
				unset($GLOBALS["CSS_FILES"]["nav.css"]);
				unset($GLOBALS["CSS_FILES"]["styles_tables.css"]);	
				unset($GLOBALS["CSS_FILES"]["styles_forms.css"]);
				unset($GLOBALS["CSS_FILES"]["styles_tabs.css"]);
				unset($GLOBALS["CSS_FILES"]["styles_rounded.css"]);		
			}
			
			if(!isset($GLOBALS["JQUERY_UI"])){$GLOBALS["JQUERY_UI"]=null;}
			if($GLOBALS["JQUERY_UI"]<>null){
				//unset($css_files["styles_forms.css"]);
				unset($GLOBALS["CSS_FILES"]["style.css"]);
				unset($GLOBALS["CSS_FILES"]["styles_tabs.css"]);
				//unset($css_files["styles_rounded.css"]);
				//unset($css_files["menus_top.css"]);
				//unset($css_files["styles_tables.css"]);	
			
			}
			
				$cssPerso=$this->PersoCSS();
				$html=$html . "$backspace<!-- CSS PERSO ". count($cssPerso). " FILE(S)/". count($GLOBALS["CSS_FILES"])." ( ". date("Y-m-d H:i:s")." ) -->\n";
				if(count($cssPerso)>0){$html=$html .@implode("\n", $cssPerso);}					
			
				reset($GLOBALS["CSS_FILES"]);
				while (list ($num, $val) = each ($GLOBALS["CSS_FILES"]) ){
					if(file_exists(dirname(__FILE__)."/templates/{$GLOBALS["artica-template"]}/$val")){
						$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/{$GLOBALS["artica-template"]}/$val'}";
						$css_arrayOLD[]="$backspace<link rel=\"stylesheet\" type=\"text/css\" rel=\"styleSheet\"  href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/{$GLOBALS["artica-template"]}/$val\" />";
					}else{
						$csserror[]="$backspace<!-- missing ".dirname(__FILE__)."/templates/{$GLOBALS["artica-template"]}/$val -->";
						if(is_file("ressources/templates/default/$val")){
							$css_arrayOLD[]="$backspace<link rel=\"stylesheet\" type=\"text/css\" rel=\"styleSheet\"  href=\"ressources/templates/default/$val\" />";
							$css_array[]="{src:'ressources/templates/default/$val'}";
						}
					}
				}
				
			if(is_array($csserror)){$html=$html . "$backspace".implode("",$csserror);}
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/calendar.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/js/jqueryFileTree.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/uploadify.css'}";
			
			
			//$html=$html . "$backspace<link href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/calendar.css\" rel=\"stylesheet\" type=\"text/css\">";
			//$html=$html . "$backspace<link href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/jqueryFileTree.css\" rel=\"stylesheet\" type=\"text/css\">";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/uploadify.css\" />";	
		
			
			$jqueryUi="artica-theme";
			if(isset($GLOBALS["JQUERY_UI"])){$jqueryUi=$GLOBALS["JQUERY_UI"];}
			$uicss=$this->JqueryUiCss($jqueryUi);
			$GLOBALS["CSS_FILES"][basename($uicss)]=basename($uicss);

			
			
			
			$html=$html . "$backspace<!-- jqueryUi:$jqueryUi -->";
			$css_array[]="{src:'$uicss'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.jgrowl.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.cluetip.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.treeview.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/thickbox.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.qtip.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/flexigrid.pack.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/ui.selectmenu.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/fileuploader.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/mobiscroll-2.1.custom.min.css'}";
			$css_array[]="{src:'{$GLOBALS["JS_HEAD_PREPREND"]}/css/colorpicker.css'}";
			
			
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"$uicss\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.jgrowl.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.cluetip.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.treeview.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/thickbox.css\" media=\"screen\"/>";		
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/jquery.qtip.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/flexigrid.pack.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/ui.selectmenu.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/css/fileuploader.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/mobiscroll-2.1.custom.min.css\" />";
			//$html=$html . "$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/colorpicker.css\" />";
			
			
			return "
			$backspace<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />		
			<script type=\"text/javascript\" language=\"javascript\">{$backspace}$.rloader([ ".@implode(",\n\t", $css_array)." ]);</script>";
			
			return $html;
		}
		
		private function PersoCSS(){
			$css=array();
			$backspace="\t\t";
			
			$template=$this->GetTemplateMemory();
			if($template=="default"){return;}
			foreach (glob("ressources/templates/$template/css/*.css") as $filename) {
				if(isset($GLOBALS["CSS_FILES"][basename($filename)])){unset($GLOBALS["CSS_FILES"][basename($filename)]);}
				$css_array[]="{src:'$filename'}";
				$css[]="$backspace<link href=\"/$filename\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" >";;
			}

			return "<script type=\"text/javascript\" language=\"javascript\">{$backspace}$.rloader([ ".@implode(",\n\t", $css_array)." ]);</script>";
			
		}
		
		public function SetTemplateMemory($template=null){
			if($template==null){return;}
			$_SESSION["artica-template"]=$template;
			$GLOBALS["artica-template"]=$template;
			$_COOKIE["artica-template"]=$template;
		}
		
		public function GetTemplateMemory(){
			if(isset($_SESSION["FORCED_TEMPLATE"])){return $_SESSION["FORCED_TEMPLATE"];}
			if(isset($GLOBALS["artica-template"])){$_COOKIE["artica-template"]=$GLOBALS["artica-template"];return $GLOBALS["artica-template"];}
			if(isset($_SESSION["artica-template"])){
					$_COOKIE["artica-template"]=$_SESSION["artica-template"];
					$GLOBALS["artica-template"]=$_SESSION["artica-template"];
					return $_SESSION["artica-template"];
			}
			if(!isset($_COOKIE["artica-template"])){$GLOBALS["artica-template"]="default";return;}
			if(!is_file(dirname(__FILE__)."/templates/{$_COOKIE["artica-template"]}/index.html")){$GLOBALS["artica-template"]="default";return;}
			$GLOBALS["artica-template"]=$_COOKIE["artica-template"];
		}
		
		public function favicon($template=null){
			
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$filename=basename($trace[1]["file"]);
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
				}
			}
			
			
			if(isset($GLOBALS["FAVICON"])){return $GLOBALS["FAVICON"];}
			if(!isset($GLOBALS["JS_HEAD_PREPREND"])){$GLOBALS["JS_HEAD_PREPREND"]=null;}
			if($template=="default"){$template=null;}
			if($template<>null){
				$_COOKIE["artica-template"]=$template;
				$_SESSION["artica-template"]=$template;
			}else{
				$template=$this->GetTemplateMemory();
			}
			
			
			$favicon="ressources/templates/default/favicon.ico";
			if(is_file(dirname(__FILE__)."/templates/$template/favicon.ico")){
				$favicon="ressources/templates/$template/favicon.ico";
			}else{
				$f[]="<!-- favicon :".dirname(__FILE__)."/templates/$template/favicon.ico no such file -->\n";
			}
			
			$f[]="<!-- favicon ". __CLASS__."/".__FUNCTION__."/".__LINE__ ." -->";
			$f[]="<!-- called by $filename/$function/$line -->";
			$f[]="<!-- Template Param..:  $template -->";
			$f[]="<!-- Template Cookie.:  {$_COOKIE["artica-template"]} -->";
			$f[]="<!-- Template Memory.:  {$GLOBALS["artica-template"]} -->";
			if(isset($_SESSION["artica-template"])){$f[]="<!-- Template Session:  {$_SESSION["artica-template"]} -->";}
			if(isset($_SESSION["artica-template"])){$f[]="<!-- Template Session Forced:  {$_SESSION["FORCED_TEMPLATE"]} -->";}
			$f[]="<!-- File:  {$_COOKIE["artica-template"]} -->";
			$f[]="<link rel=\"icon\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/$favicon\" type=\"image/x-icon\" />";
			$f[]="<link rel=\"shortcut icon\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/$favicon\" type=\"image/x-icon\" />\n";
			$GLOBALS["FAVICON"]=@implode("\n", $f);
			
			return $GLOBALS["FAVICON"];
			
			
		}
		

		public function JqueryUiCss($theme){
			if($GLOBALS["EXECUTED_AS_ROOT"]){return;}
			$fileDesign="/templates/{$_COOKIE["artica-template"]}/jquery-custom/custom.min.css";
			$Design=dirname(__FILE__) . $fileDesign;
			
			
			
			$files["jquery-ui.custom.css"]=true;
			$files["jquery-ui-1.8.10.custom.css"]=true;
			$files["jquery-ui-1.8.6.custom.css"]=true;
			$files["jquery-ui-1.7.2.custom.css"]=true;
			$files["jquery-ui-1.8.16.custom.css"]=true;
			
			$template=$this->GetTemplateMemory();
			
			if($template<>"default"){
				while (list ($num, $val) = each ($files) ){
					if(is_file(dirname(__FILE__)."/templates/{$_COOKIE["artica-template"]}/css/$num")){return dirname(__FILE__)."/templates/{$_COOKIE["artica-template"]}/css/$num";}
				}
				reset($files);
			}
			while (list ($num, $val) = each ($files) ){
				if(is_file("/usr/share/artica-postfix/css/$theme/$num")){return "{$GLOBALS["JS_HEAD_PREPREND"]}/css/$theme/$num";}
			}
			writelogs("Warning, unable to found jquery-ui-x.x.x.css !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
		}

	function YahooBody(){
			if($GLOBALS["EXECUTED_AS_ROOT"]){return;}
			$conf="";
			$conf=$conf ."<div id=\"SetupControl\" style='width:0;height:0'></div>\n";			
			$conf=$conf. "<div id=\"dialogS\" style='width:0;height:0'></div> \n";
			$conf=$conf. "<div id=\"dialogT\" style='width:0;height:0'></div> \n";
			$conf=$conf ."<div id=\"dialog0\" style='width:0;height:0'></div> \n";
			$conf=$conf ."<div id=\"dialog1\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"dialog2\" style='width:0;height:0'></div> \n";
			$conf=$conf ."<div id=\"dialog3\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"dialog4\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"dialog5\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"dialog6\" style='width:0;height:0'></div>\n"; 
			$conf=$conf ."<div id=\"YahooUser\" style='width:0;height:0'></div>\n"; 
			$conf=$conf ."<div id=\"logsWatcher\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"WinORG\" style='width:0;height:0'></div>\n";	
			$conf=$conf ."<div id=\"WinORG2\" style='width:0;height:0'></div>\n";						
			$conf=$conf ."<div id=\"RTMMail\" style='width:0;height:0'></div>\n";			
			$conf=$conf ."<div id=\"Browse\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"SearchUser\" style='width:0;height:0'></div>\n";
			$conf=$conf ."<div id=\"UnityDiv\" style='width:0;height:0'></div>\n";	
			$conf=$conf ."<div id='PopUpInfos' style='position:absolute'></div>\n";
			$conf=$conf ."<div id='find' style='position:absolute'></div>\n";	
			$conf=$conf ."<div class=\"messageMenuDisplay messageMenu\" id='messageMenuDiv'></div>\n";
			
			return $conf;		
	}

	function monitorix_array($type="day"){
				$arrayz[]="monitorix/cpu1z.day.png";
				$arrayz[]="monitorix/cpu1z.month.png";
				$arrayz[]="monitorix/cpu1z.week.png";
				$arrayz[]="monitorix/cpu1z.year.png";
				$arrayz[]="monitorix/cpu2z.day.png";
				$arrayz[]="monitorix/cpu2z.month.png";
				$arrayz[]="monitorix/cpu2z.week.png";
				$arrayz[]="monitorix/cpu2z.year.png";	
				$arrayz[]="monitorix/cpu3z.day.png";
				$arrayz[]="monitorix/cpu3z.month.png";
				$arrayz[]="monitorix/cpu3z.week.png";
				$arrayz[]="monitorix/cpu3z.year.png";
				
				$arrayz[]="monitorix/kern1z.day.png";
				$arrayz[]="monitorix/kern1z.month.png";
				$arrayz[]="monitorix/kern1z.week.png";
				$arrayz[]="monitorix/kern1z.year.png";				
				
				$arrayz[]="monitorix/disk3z.day.png";
				$arrayz[]="monitorix/disk3z.month.png";
				$arrayz[]="monitorix/disk3z.week.png";
				$arrayz[]="monitorix/disk3z.year.png";				
				
				$arrayz[]="monitorix/int1z.day.png";
				$arrayz[]="monitorix/int1z.month.png";
				$arrayz[]="monitorix/int1z.week.png";
				$arrayz[]="monitorix/int1z.year.png";				
				
				$arrayz[]="monitorix/disk2z.day.png";
				$arrayz[]="monitorix/disk2z.month.png";
				$arrayz[]="monitorix/disk2z.week.png";
				$arrayz[]="monitorix/disk2z.year.png";				

				$arrayz[]="monitorix/disk1z.day.png";
				$arrayz[]="monitorix/disk1z.month.png";
				$arrayz[]="monitorix/disk1z.week.png";
				$arrayz[]="monitorix/disk1z.year.png";	
				
				$arrayz[]="monitorix/kern2z.day.png";
				$arrayz[]="monitorix/kern2z.month.png";
				$arrayz[]="monitorix/kern2z.week.png";
				$arrayz[]="monitorix/kern2z.year.png";	
				
				$arrayz[]="monitorix/net02z.day.png";
				$arrayz[]="monitorix/net02z.month.png";
				$arrayz[]="monitorix/net02z.week.png";
				$arrayz[]="monitorix/net02z.year.png";							

				
				$arrayz[]="monitorix/int2z.day.png";
				$arrayz[]="monitorix/int2z.month.png";
				$arrayz[]="monitorix/int2z.week.png";
				$arrayz[]="monitorix/int2z.year.png";	

				$arrayz[]="monitorix/net01z.day.png";
				$arrayz[]="monitorix/net01z.month.png";
				$arrayz[]="monitorix/net01z.week.png";
				$arrayz[]="monitorix/net01z.year.png";								
	
				$arrayz[]="monitorix/cpu1.day.png";
				$array[]="monitorix/cpu2.day.png";
				$array[]="monitorix/cpu3.day.png";
				$arrayz[]="monitorix/disk1.day.png";
				$array[]="monitorix/disk2.day.png";
				$array[]="monitorix/disk3.day.png";
				
			
				
				
				$array[]="monitorix/cpu1.month.png";
				$array[]="monitorix/cpu1.week.png";
				$array[]="monitorix/cpu1.year.png";
				
				
				
				$array[]="monitorix/cpu2.month.png";
				$array[]="monitorix/cpu2.week.png";
				$array[]="monitorix/cpu2.year.png";
				
				
				$array[]="monitorix/cpu3.month.png";
				$array[]="monitorix/cpu3.week.png";
				$array[]="monitorix/cpu3.year.png";
				
				
				$array[]="monitorix/disk1.month.png";
				$array[]="monitorix/disk1.week.png";
				$array[]="monitorix/disk1.year.png";
				
				
				$array[]="monitorix/disk2.month.png";
				$array[]="monitorix/disk2.week.png";
				$array[]="monitorix/disk2.year.png";
				
				
				$array[]="monitorix/disk3.month.png";
				$array[]="monitorix/disk3.week.png";
				$array[]="monitorix/disk3.year.png";
				
				$arrayz[]="monitorix/int1.day.png";
				$array[]="monitorix/int1.month.png";
				$array[]="monitorix/int1.week.png";
				$array[]="monitorix/int1.year.png";
				
				$array[]="monitorix/int2.day.png";
				$array[]="monitorix/int2.month.png";
				$array[]="monitorix/int2.week.png";
				$array[]="monitorix/int2.year.png";
				
				
				$arrayz[]="monitorix/kern1.day.png";
				$array[]="monitorix/kern1.month.png";
				$array[]="monitorix/kern1.week.png";
				$array[]="monitorix/kern1.year.png";
				
				$array[]="monitorix/kern2.day.png";
				$array[]="monitorix/kern2.month.png";
				$array[]="monitorix/kern2.week.png";
				$array[]="monitorix/kern2.year.png";
				
				$arrayz[]="monitorix/net01.day.png";
				$array[]="monitorix/net01.month.png";
				$array[]="monitorix/net01.week.png";
				$array[]="monitorix/net01.year.png";

				$array[]="monitorix/net02.day.png";
				$array[]="monitorix/net02.month.png";
				$array[]="monitorix/net02.week.png";
				$array[]="monitorix/net02.year.png";
				
				$array[]="monitorix/net03.day.png";
				$array[]="monitorix/net03.month.png";
				$array[]="monitorix/net03.week.png";
				$array[]="monitorix/net03.year.png";
				$arrayz[]="monitorix/net03z.day.png";
				$arrayz[]="monitorix/net03z.month.png";
				$arrayz[]="monitorix/net03z.week.png";
				$arrayz[]="monitorix/net03z.year.png";
				$arrayz[]="monitorix/port01.day.png";
				$array[]="monitorix/port01.month.png";
				$array[]="monitorix/port01.week.png";
				$array[]="monitorix/port01.year.png";
				$arrayz[]="monitorix/port01z.day.png";
				$arrayz[]="monitorix/port01z.month.png";
				$arrayz[]="monitorix/port01z.week.png";
				$arrayz[]="monitorix/port01z.year.png";
				$array[]="monitorix/port02.day.png";
				$array[]="monitorix/port02.month.png";
				$array[]="monitorix/port02.week.png";
				$array[]="monitorix/port02.year.png";
				$arrayz[]="monitorix/port02z.day.png";
				$arrayz[]="monitorix/port02z.month.png";
				$arrayz[]="monitorix/port02z.week.png";
				$arrayz[]="monitorix/port02z.year.png";
				$array[]="monitorix/port03.day.png";
				$array[]="monitorix/port03.month.png";
				$array[]="monitorix/port03.week.png";
				$array[]="monitorix/port03.year.png";
				$array[]="monitorix/port03z.day.png";
				$array[]="monitorix/port03z.month.png";
				$array[]="monitorix/port03z.week.png";
				$array[]="monitorix/port03z.year.png";
				$array[]="monitorix/port04.day.png";
				$array[]="monitorix/port04.month.png";
				$array[]="monitorix/port04.week.png";
				$array[]="monitorix/port04.year.png";
				$array[]="monitorix/port04z.day.png";
				$array[]="monitorix/port04z.month.png";
				$array[]="monitorix/port04z.week.png";
				$array[]="monitorix/port04z.year.png";
				$array[]="monitorix/port05.day.png";
				$array[]="monitorix/port05.month.png";
				$array[]="monitorix/port05.week.png";
				$array[]="monitorix/port05.year.png";
				$array[]="monitorix/port05z.day.png";
				$array[]="monitorix/port05z.month.png";
				$array[]="monitorix/port05z.week.png";
				$array[]="monitorix/port05z.year.png";
				$array[]="monitorix/port06.day.png";
				$array[]="monitorix/port06.month.png";
				$array[]="monitorix/port06.week.png";
				$array[]="monitorix/port06.year.png";
				$array[]="monitorix/port06z.day.png";
				$array[]="monitorix/port06z.month.png";
				$array[]="monitorix/port06z.week.png";
				$array[]="monitorix/port06z.year.png";
				$array[]="monitorix/port07.day.png";
				$array[]="monitorix/port07.month.png";
				$array[]="monitorix/port07.week.png";
				$array[]="monitorix/port07.year.png";
				$array[]="monitorix/port07z.day.png";
				$array[]="monitorix/port07z.month.png";
				$array[]="monitorix/port07z.week.png";
				$array[]="monitorix/port07z.year.png";
				$array[]="monitorix/port08.day.png";
				$array[]="monitorix/port08.month.png";
				$array[]="monitorix/port08.week.png";
				$array[]="monitorix/port08.year.png";
				$array[]="monitorix/port08z.day.png";
				$array[]="monitorix/port08z.month.png";
				$array[]="monitorix/port08z.week.png";
				$array[]="monitorix/port08z.year.png";
				$array[]="monitorix/port09.day.png";
				$array[]="monitorix/port09.month.png";
				$array[]="monitorix/port09.week.png";
				$array[]="monitorix/port09.year.png";
				$array[]="monitorix/port09z.day.png";
				$array[]="monitorix/port09z.month.png";
				$array[]="monitorix/port09z.week.png";
				$array[]="monitorix/port09z.year.png";
				$array[]="monitorix/port10.day.png";
				$array[]="monitorix/port10.month.png";
				$array[]="monitorix/port10.week.png";
				$array[]="monitorix/port10.year.png";
				$array[]="monitorix/port10z.day.png";
				$array[]="monitorix/port10z.month.png";
				$array[]="monitorix/port10z.week.png";
				$array[]="monitorix/port10z.year.png";
				$array[]="monitorix/port11.day.png";
				$array[]="monitorix/port11.month.png";
				$array[]="monitorix/port11.week.png";
				$array[]="monitorix/port11.year.png";
				$array[]="monitorix/port11z.day.png";
				$array[]="monitorix/port11z.month.png";
				$array[]="monitorix/port11z.week.png";
				$array[]="monitorix/port11z.year.png";
				$array[]="monitorix/port12.day.png";
				$array[]="monitorix/port12.month.png";
				$array[]="monitorix/port12.week.png";
				$array[]="monitorix/port12.year.png";
				$array[]="monitorix/port12z.day.png";
				$array[]="monitorix/port12z.month.png";
				$array[]="monitorix/port12z.week.png";
				$array[]="monitorix/port12z.year.png";
				$arrayz[]="monitorix/proc0.day.png";
				$array[]="monitorix/proc0z.day.png";
				$arrayz[]="monitorix/proc1.day.png";
				$array[]="monitorix/proc1z.day.png";
				$arrayz[]="monitorix/servu1.day.png";
				$array[]="monitorix/servu1.month.png";
				$array[]="monitorix/servu1.week.png";
				$array[]="monitorix/servu1.year.png";
				$arrayz[]="monitorix/servu1z.day.png";
				$arrayz[]="monitorix/servu1z.month.png";
				$arrayz[]="monitorix/servu1z.week.png";
				$arrayz[]="monitorix/servu1z.year.png";
				$array[]="monitorix/servu2.day.png";
				$array[]="monitorix/servu2.month.png";
				$array[]="monitorix/servu2.week.png";
				$array[]="monitorix/servu2.year.png";
				$arrayz[]="monitorix/servu2z.day.png";
				$arrayz[]="monitorix/servu2z.month.png";
				$arrayz[]="monitorix/servu2z.week.png";
				$arrayz[]="monitorix/servu2z.year.png";
				$array[]="monitorix/servu3.day.png";
				$array[]="monitorix/servu3.month.png";
				$array[]="monitorix/servu3.week.png";
				$array[]="monitorix/servu3.year.png";
				$arrayz[]="monitorix/servu3z.day.png";
				$arrayz[]="monitorix/servu3z.month.png";
				$arrayz[]="monitorix/servu3z.week.png";
				$arrayz[]="monitorix/servu3z.year.png";
				$arrayz[]="monitorix/user1.day.png";
				$array[]="monitorix/user1.month.png";
				$array[]="monitorix/user1.week.png";
				$array[]="monitorix/user1.year.png";
				$array[]="monitorix/user1z.day.png";
				$array[]="monitorix/user1z.month.png";
				$array[]="monitorix/user1z.week.png";
				$array[]="monitorix/user1z.year.png";
				$array[]="monitorix/user2.day.png";
				$array[]="monitorix/user2.month.png";
				$array[]="monitorix/user2.week.png";
				$array[]="monitorix/user2.year.png";
				$array[]="monitorix/user2z.day.png";
				$array[]="monitorix/user2z.month.png";
				$array[]="monitorix/user2z.week.png";
				$array[]="monitorix/user2z.year.png";
				$array[]="monitorix/user3.day.png";
				$array[]="monitorix/user3.month.png";
				$array[]="monitorix/user3.week.png";
				$array[]="monitorix/user3.year.png";
				$array[]="monitorix/user3z.day.png";
				$array[]="monitorix/user3z.month.png";
				$array[]="monitorix/user3z.week.png";
				$array[]="monitorix/user3z.year.png";	
	
			while (list ($num, $val) = each ($array) ){
				
				if(!preg_match('#z\.#',$val)){
					if(preg_match('#month#',$val)){$array_m["month"][$val]=$val;}
					if(preg_match('#week#',$val)){$array_m["week"][$val]=$val;}
					if(preg_match('#day#',$val)){$array_m["day"][$val]=$val;}
				}	
				
			}
			return $array_m[$type];
				
			}	
	
	
	
}

?>