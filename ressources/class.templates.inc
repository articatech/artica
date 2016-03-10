<?php
if(!isset($GLOBALS["SESSION_ID"])){$GLOBALS["SESSION_ID"]=session_id();}
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
//$GLOBALS["DEBUG_LANG"]=true;
@header("Pragma: no-cache");
@header("Expires: 0");
@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
@header("Cache-Control: no-cache, must-revalidate");
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(isset($GLOBALS["DEBUG_PROCESS"])){include_once(dirname(__FILE__)."/logs.inc");}

if(!isset($GLOBALS["EXECUTED_AS_ROOT"])){if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();if($GLOBALS["posix_getuid"]==0){$GLOBALS["EXECUTED_AS_ROOT"]=true;}else{$GLOBALS["EXECUTED_AS_ROOT"]=false;}}}
if(!isset($GLOBALS["DEBUG_MEM"])){$GLOBALS["DEBUG_MEM"]=false;}

if(!isset($GLOBALS["DEBUG_LANG"])){$GLOBALS["DEBUG_LANG"]=false;}
define("BECALL",base64_decode("T05MWV9DT1JQX0hUTUw="));
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
debug_mem();
if(isset($_GET["debug-page"])){	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!$GLOBALS["EXECUTED_AS_ROOT"]){
	if(isset($GLOBALS["KAV4PROXY_NOSESSION"])){
		if(!$GLOBALS["KAV4PROXY_NOSESSION"]){session_start();}}}
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
include_once(dirname(__FILE__) . "/class.sockets.inc");
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
getLocalTimezone();
debug_mem();
include_once(dirname(__FILE__) . '/class.ldap.inc');
debug_mem();
if(!function_exists("writelogs")){include_once(dirname(__FILE__) . '/logs.inc');}
debug_mem();
if(is_file(dirname(__FILE__) . '/class.users.menus.inc')){include_once(dirname(__FILE__) . '/class.users.menus.inc');}
debug_mem();
include_once(dirname(__FILE__).  '/class.os.system.inc');
debug_mem();
if(is_file(dirname(__FILE__) . '/class.privileges.inc')){include_once(dirname(__FILE__).  '/class.privileges.inc');}
debug_mem();
if(is_file(dirname(__FILE__) . '/class.html.tools.inc')){include_once(dirname(__FILE__).  '/class.html.tools.inc');}
debug_mem();
include_once(dirname(__FILE__).  '/class.ini.inc');
include_once(dirname(__FILE__).  '/class.tcpip.inc');
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
include_once(dirname(__FILE__).  '/class.mysql.inc');
include_once(dirname(__FILE__).  '/class.mysql-meta.inc');
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
include_once(dirname(__FILE__)."/class.browser.detection.inc");
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
CheckSessions();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}


$GLOBALS['_DLANG']='en';
$GLOBALS['_LANG'] = array(
		'af',	//	afrikaans.
		'ar',	//	arabic.
		'bg',	//	bulgarian.
		'ca',	//	catalan.
		'cs',	//	czech.
		'da',	//	danish.
		'de',	//	german.
		'el',	//	greek.
		'en',	//	english.
		'es',	//	spanish.
		'et',	//	estonian.
		'fi',	//	finnish.
		'fr',	//	french.
		'gl',	//	galician.
		'he',	//	hebrew.
		'hi',	//	hindi.
		'hr',	//	croatian.
		'hu',	//	hungarian.
		'id',	//	indonesian.
		'it',	//	italian.
		'ja',	//	japanese.
		'ko',	//	korean.
		'ka',	//	georgian.
		'lt',	//	lithuanian.
		'lv',	//	latvian.
		'ms',	//	malay.
		'nl',	//	dutch.
		'no',	//	norwegian.
		'pl',	//	polish.
		'pt',	//	portuguese.
		'ro',	//	romanian.
		'ru',	//	russian.
		'sk',	//	slovak.
		'sl',	//	slovenian.
		'sq',	//	albanian.
		'sr',	//	serbian.
		'sv',	//	swedish.
		'th',	//	thai.
		'tr',	//	turkish.
		'uk',	//	ukrainian.
		'zh' 	//	chinese.
);

function CurrentPageName(){
	$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
	return $phpPage;}  

class templates{
	var $head;
	var $body;
	var $web_page;
	var $language;
	var $left_menus;
	var $top_menus;
	var $search_menus;
	var $title;
	var $js_add;
	var $GLOBAL_CONFIG;
	var $array_config;
	var $AutomaticConfig=false;
	var $pattern_array;
	function templates($title=null,$body=null,$js_add=null,$do_nothing=0,$arrayConfig=array()){
		if($GLOBALS["AS_ROOT"]){$users=new usersMenus();}
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if($GLOBALS["VERBOSE"]){
			$trace=debug_backtrace();
			$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";			
			//writelogs("Instanciate ".__CLASS__ ." $called",__CLASS__."/" .__FUNCTION__,__FILE__);
		}	
		
		
			$GLOBALS["CURRENT_PAGE"]=CurrentPageName();
			if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
			$this->SettingsInc();
			
			if(is_file(dirname(__FILE__) . '/settings.inc')){@include(dirname(__FILE__) . '/settings.inc');$this->GLOBAL_CONFIG=$_GLOBAL;}
			if(!isset($this->GLOBAL_CONFIG["AutomaticConfig"])){$this->GLOBAL_CONFIG["AutomaticConfig"]=0;}
			$this->AutomaticConfig=$this->GLOBAL_CONFIG["AutomaticConfig"];
			$this->array_config=$arrayConfig;
			if($do_nothing==1){
				if(isset($GLOBALS["DEBUG_TEMPLATE"])){if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}=> DO NOTHING... class".__CLASS__ ."(".__FUNCTION__.") in " . basename(__FILE__). " line ".__LINE__);}}else{$GLOBALS["DEBUG_TEMPLATE"]=false;}
				return false;
			}
			
			$this->title=$title;
			$this->js_add=$js_add;
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>LANGUAGE $this->language</strong><br>\n";}
			if($this->language==null){
				if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$this->language=$this->_detect_lang();
				if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::".__FUNCTION__." _detect_lang() return `$this->language` in " . basename(__FILE__). " line ".__LINE__);}
			} 
			
			$this->headers();
			$this->search_menus=$this->search_menus();
			$this->Body($body);
			$this->web_page="$this->head $this->body</html>";
			$this->_parse_body();
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
	private function SettingsInc(){
		if(is_file(dirname(__FILE__) . '/settings.inc')){return;}
		if(!$GLOBALS["EXECUTED_AS_ROOT"]){return;}
		if(!class_exists("unix")){return;}
		$unix=new unix();
		$unix->Process1(true);
	
	}
	
	function search_menus(){}
	
	function headers($array=array()){
		if(!isset($array["TITLE"])){$array["TITLE"]=null;}
		if($this->language==null){$this->language="en";}
		if($array["TITLE"]<>null){$array["TITLE"]=$this->_ENGINE_parse_body($array["TITLE"]);}
		include_once(dirname(__FILE__)."/class.page.builder.inc");
		$pges=new pagebuilder();
		$pges->language=$this->language;
		$this->head=$pges->buildHeads($array);
		return $this->head;	
		
	}
	
	function _parse_body($content=null){
		return $this->_ENGINE_parse_body($content);
	}
	
	
	function YahooBody(){
			include_once(dirname(__FILE__)."/class.page.builder.inc");
			$pges=new pagebuilder();
			$pges->language=$this->language;
			return $pges->YahooBody();
	}
	
	function jsArtica(){
		include_once(dirname(__FILE__)."/class.page.builder.inc");
		$pges=new pagebuilder();
		$pges->language=$this->language;	
		return $pges->jsArtica();
	}
	
	function javascript_senderror($error,$id){
		header("content-type: application/x-javascript");
		if($error<>null){
			$error=$this->javascript_parse_text($error);
			$error=str_replace("\\n", "<br>", $error);
			$error=str_replace("'", "`", $error);
			$t=time();
			echo "function vght$t(){
				var text='<p style=\"font-size:16px;color:#B90000\" class=text-error><strong>$error</strong></p>';
				document.getElementById('$id').innerHTML=text;
			
			}";
		}else{
			echo "function vght$t(){
			$('#$id').remove();
				}";
		}
		
		echo "\nvght$t();\n";
		
		exit;
		
		
	}
		

		
	function javascript_parse_text($content,$noremovecrlf=0){
		$content=$this->_ENGINE_parse_body($content);
		$content=str_replace("<li>", "-", $content);
		$content=str_replace("</li>", "", $content);
		$content=str_replace("<ul>", "", $content);
		$content=str_replace("</ul>", "", $content);
		$content=str_replace("<br>", "\n", $content);
		$content=str_replace("\n\n", "\n", $content);
		$content=str_replace("\n\n", "\n", $content);
		$content=str_replace("&nbsp;", " ", $content);
		$content=str_replace("<strong>", " ", $content);
		$content=str_replace("</strong>", " ", $content);
		
		//$content=replace_accents($content);
		$content=html_entity_decode($content);
		$content=replace_accents($content);
		if(!isset($_SESSION["DoNotutf8EncodeJS"])){
			$sock=new sockets();
			$_SESSION["DoNotutf8EncodeJS"]=$sock->GET_INFO("DoNotutf8EncodeJS");
			if(!is_numeric($_SESSION["DoNotutf8EncodeJS"])){$_SESSION["DoNotutf8EncodeJS"]=0;}
		}
		
		$DoNotutf8EncodeJS=$_SESSION["DoNotutf8EncodeJS"];
		if($this->language=="fr"){$DoNotutf8EncodeJS=1;}
		
		if($DoNotutf8EncodeJS==0){
			$content=utf8_encode($content);
		}
		
		if($noremovecrlf==0){
			$content=nl2br($content);
			$content=str_replace("<br>",'\n',$content);
			$content=str_replace("<br />",'\n',$content);
			$content=str_replace("\n\n",'\n',$content);
			$content=str_replace("\n",'\\n',$content);

			
		}else{
			$content=str_replace("<br />",'',$content);
			$content=str_replace("\\n","\n",$content);
			
		}
		$content=str_replace("'","`",$content);
		$content=str_replace("\`","`",$content);
		$content=str_replace("\\n\\n","\\n",$content);
		
		return $content;
	}

	
	private function get_cached_text($text){
		if(strpos(" $text", " document.getElementById")>0){return;}
		if(strpos(" $text", " if(")>0){return;}
		if(strpos(" $text", ".qtip(")>0){return;}
		if(strpos(" $text", "RefreshTab(")>0){return;}
		if(strpos(" $text", "XHRConnection")>0){return;}
		if(strpos(" $text", "LoadAjax('")>0){return;}
		
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		if($this->language<>null){$lang=$this->language;}else{$lang=$this->_detect_lang();$this->language=$lang;}
		
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>LANGUAGE $this->language</strong><br>\n";}
		
		
		if($this->language=="undefined"){
			writelogs("WARNING language = undefined !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
			$FileCookyLang=$sock->GET_INFO($FileCookyKey);
			if($FileCookyLang==="undefined"){$FileCookyLang=null;}
			$logon_parameters=unserialize(base64_decode($sock->GET_INFO("LogonPageSettings")));
			if($logon_parameters["LANGUAGE_SELECTOR_REMOVE"]==null){$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]="0";}	
			if(!is_numeric($logon_parameters["LANGUAGE_SELECTOR_REMOVE"])){$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]=0;}
			if($logon_parameters["DEFAULT_LANGUAGE"]==null){$logon_parameters["DEFAULT_LANGUAGE"]="en";}
			if($FileCookyLang==null){
				if($logon_parameters["LANGUAGE_SELECTOR_REMOVE"]==1){$FileCookyLang=$logon_parameters["DEFAULT_LANGUAGE"];}
			}
			
			
			
			if($FileCookyLang==null){
				if(isset($_SESSION["detected_lang"])){
					$this->language=$_SESSION["detected_lang"];
				}
			}else{$this->language=$FileCookyLang;}
			
			
		}
		
		if($this->language==null){
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::".__FUNCTION__." undefined language, language will be en " . basename(__FILE__). " line ".__LINE__);}
			$this->language="en";
		}
		
		$_SESSION["detected_lang"]=$this->language;
		if(!isset($GLOBALS["translation"])){$GLOBALS["translation"]=array();}
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>LANGUAGE $this->language</strong><br>\n";}
		
		
		if(count($GLOBALS["translation"])<100){
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>DUMP LANGUAGE $this->language</strong><br>\n";}
			$GLOBALS["translation"]=$sock->LANGUAGE_DUMP($this->language);
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>DUMP LANGUAGE ". count($GLOBALS["translation"])." items</strong><br>\n";}
		}
		
		$value=null;
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>Find $text</strong><br>\n";}
		
	
		
		if(!isset($GLOBALS["translation-en"])){$GLOBALS["translation-en"]=array();}
		if(isset($GLOBALS["translation"][$text])){
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>$value {$GLOBALS["translation"][$text]}</strong><br>\n";}
				$value=$GLOBALS["translation"][$text];
		}
		if($value==null){
			if(count($GLOBALS["translation-en"])<100){
				if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>DUMP LANGUAGE EN for en translation</strong><br>\n";}
				$GLOBALS["translation-en"]=$sock->LANGUAGE_DUMP("en");}
			if(isset($GLOBALS["translation-en"][$text])){
				if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>$text ONLY FOUND IN US MODE</strong><br>\n";}
				$value=$GLOBALS["translation-en"][$text];}
		}
		
		return $value;
	}
	
	function parsePHPModules() {
		ob_start();
		phpinfo(INFO_MODULES);
		$s = ob_get_contents();
		ob_end_clean();
	
		$s = strip_tags($s,'<h2><th><td>');
		$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
		$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
		$vTmp = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
		$vModules = array();
		for ($i=1;$i<count($vTmp);$i++) {
			if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
				$vName = trim($vMat[1]);
				$vTmp2 = explode("\n",$vTmp[$i+1]);
				foreach ($vTmp2 AS $vOne) {
					$vPat = '<info>([^<]+)<\/info>';
					$vPat3 = "/$vPat\s*$vPat\s*$vPat/";
					$vPat2 = "/$vPat\s*$vPat/";
					if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
						$vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
					} elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
						$vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
					}
				}
			}
		}
		return $vModules;
	}
	
	
	
	function time_to_date($xtime,$time=false){
		if(intval($xtime)==0){return "-";}
		$dateT=date("{l} {F} d",$xtime);
		if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
		if($this->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
		return $this->_ENGINE_parse_body($dateT);
	
	}
	
	
	
	function _ENGINE_parse_body($content=null,$currpage=null){
		if(isset($GLOBALS["AS_ROOT"])){if($GLOBALS["AS_ROOT"]){return $content;}}
		if(is_array($content)){return ;}
		if(!isset($content)){return;}
		if(empty($content)){return;}
		if(trim($content)==null){return;}
		$key=md5($content);
		if(strlen($content)<400){
			
			if(isset($GLOBALS["_ENGINE_parse_body"][$key])){return $GLOBALS["_ENGINE_parse_body"][$key];}
		}
		

		
		
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		if(!isset($GLOBALS["FIXED_LANGUAGE"])){$GLOBALS["FIXED_LANGUAGE"]=$sock->GET_INFO("FixedLanguage");}
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		$content=str_replace("javascript:blur()", "javascript:Blurz()", $content);
		
		if($GLOBALS["posix_getuid"]==0){
			if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] posix_getuid = 0 -> abort\n" ;}
			return $content;}
		
		if($this->language==null){
			if($GLOBALS["FIXED_LANGUAGE"]<>null){$this->language=$GLOBALS["FIXED_LANGUAGE"];}
		}
		
		if($this->language==null){
			if($GLOBALS["DEBUG_TEMPLATE"]){writelogs("Language is null...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>LANGUAGE $this->language</strong><br>\n";}
		
		
		if(is_array($content)){$content=implode(" ",$content);}
		
		
		

		
		
		if(preg_match_all('#{(.+?)}#is',$content,$regs)){
			while (list ($num, $val) = each ($regs[1]) ){
						$text=$this->get_cached_text($val);
						if($text<>null){
							$text=str_replace('"',"&quot;",$text);
							$text=str_replace("'","`",$text);
							$text=str_ireplace('[code]','<code>',$text);
							$text=str_ireplace('[/code]','</code>',$text);
							$text=str_ireplace('[br]','<br>',$text);
							$text=str_ireplace('[b]','<b>',$text);
							$text=str_ireplace('[/b]','</b>',$text);
							$text=str_ireplace('[ul]','<ul>',$text);
							$text=str_ireplace('[/ul]','</ul>',$text);
							$text=str_ireplace('[li]','<li>',$text);
							$text=str_ireplace('[/li]','</li>',$text);
							$text=str_ireplace('[/center]','</center>',$text);
							$text=str_ireplace('[center]','<center>',$text);
							$text=str_ireplace('[/strong]','</strong>',$text);
							$text=str_ireplace('[strong]','<strong>',$text);	
							$text=str_ireplace('[/h4]','</h4>',$text);
							$text=str_ireplace('[h4]','<h4>',$text);
							$text=str_ireplace('[h5]','<h5>',$text);
							$text=str_ireplace('[h5r]','<h5 style="color:#d32d2d">',$text);
							$text=str_ireplace('[/h5r]','</h5>',$text);														
							$text=str_ireplace('[/h5]','</h5>',$text);
							$text=str_ireplace('[i]','<i>',$text);	
							$text=str_ireplace('[/i]','</i>',$text);	
							$text=str_ireplace('[/a]','</a>',$text);
							$text=str_ireplace('[/u]','</u>',$text);
							$text=str_ireplace('[b12]','<strong style="font-size:12px">',$text);							
							$text=str_ireplace('[/b12]','</strong>',$text);
							$text=str_replace('-&amp;raquo;','-&raquo;',$text);
							$text=str_replace('[h3]','<H3>',$text);
							$text=str_replace('[/h3]','</H3>',$text);
							$text=str_replace('\"',"&quot;",$text);
							if(isset($_SESSION["ou"])){
								$text=str_replace('%ORGA',$_SESSION["ou"],$text);
							}
							
							
							$text=str_ireplace('%u0153','oe',$text);
							$text=str_ireplace('%u201C','&laquo;',$text);
							$text=str_ireplace('%u201D','&raquo;',$text);
							$text=str_ireplace('%u2019','`',$text);
							
							$text=str_ireplace('[u]','<u>',$text);
							
							$text=str_ireplace('[blk]','<blockquote>',$text);	
							$text=str_ireplace('[/blk]','</blockquote>',$text);	
							
							$text=str_ireplace('<b>','<strong>',$text);
							$text=str_ireplace('</b>','</strong>',$text);
							
							
							if(preg_match_all('#\[a\s+(.+?)\]#',$text,$r)){
								while (list ($a, $b) = each ($r[1]) ){
								$text=str_replace($r[0][$a],"<a href=\"$b\">",$text);}
							}
							if(isset($_COOKIE["change-artica-name"])){
								$text=str_ireplace("d'Artica",$_COOKIE["change-artica-name"],$text);
								$text=str_ireplace("d`Artica",$_COOKIE["change-artica-name"],$text);
								$text=str_ireplace("Artica",$_COOKIE["change-artica-name"],$text);
								$text=str_ireplace("artica",$_COOKIE["change-artica-name"],$text);
							}
							if(isset($_SESSION["change-artica-name"])){
								$text=str_ireplace("d'Artica",$_SESSION["change-artica-name"],$text);
								$text=str_ireplace("d`Artica",$_SESSION["change-artica-name"],$text);
								$text=str_ireplace("Artica",$_SESSION["change-artica-name"],$text);
								$text=str_ireplace("artica",$_SESSION["change-artica-name"],$text);
							}							
													
							$content=str_replace("{{$val}}",$text,$content);
						}
					}}
					
					
		if(is_file(dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/top-bar-color.conf")){
			$backcoulor=@file_get_contents(dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/top-bar-color.conf");
			$content=str_replace("#005447",$backcoulor,$content);
		}
		
		if(preg_match_all('#\[url\](.+?)\[\/url\]#is',$content,$regs)){
			while (list ($num, $val) = each ($regs[1]) ){
				$content=str_replace($regs[0][$num],"<a href=\"$val\">$val</a>",$content);
			}
				
		}
					
		
		$content=str_replace("<input type='button'","<input type='button' class='button'",$content);
		$content=str_replace("web sites","websites",$content);
		$content=str_replace("\`","`",$content);
		$content=str_replace('\"',"&quot;",$content);
		$content=str_replace('%C3%89',"&Eacute;",$content);
		
		//$content=str_replace("Loadjs(",'$.getScript(',$content);
		if(strlen($content)<400){
			$GLOBALS["_ENGINE_parse_body"][$key]=$content;
		}
		
		
		return $content;
		}	
	
	function _readfile($filepath){
	if(isset($GLOBALS[md5($filepath)])){return $GLOBALS[md5($filepath)];}		
	$file = @fopen($filepath, "r");
	if ($file<>null){
			$content=@fread($file,filesize($filepath));
			fclose($file);
			$GLOBALS[md5($filepath)]=$content;
			return $content; 
			return null;
			}else{
				if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::".__FUNCTION__." Error while open $filepath  in " . basename(__FILE__). " line ".__LINE__);}
			}
		
	}
	
	function get_path_lang(){
		
		
	}
	
	
	function build_left_menus(){
		return;
		
	}
	
	

	
	function BuildMenusBarr($menus){
		$color="#005447";
		$skinf=dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/top-bar-color.conf";
		if(is_file($skinf)){
			$color=@file_get_contents(dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/top-bar-color.conf");
		}else{
			$error="<!-- $skinf no such file -->\n";
		}
		
		
		return "$error<div id='template-top-menus' style='position:static;min-width:768px;height:45px;background-color:$color;color:white' >
				</div>
					<script>AjaxTopMenu('template-top-menus','admin.top.menus.php');</script>";

		}	
	
	
	function Build_high_menus(){
		return null;
		return "<div id='div-high-menus' style='margin-top:-7px'>
			<table style='width:80%;padding:0px;margin:0px;border:0px;margin-top:2px'>
			<tr>
				<td>&nbsp;</td>
			</tr>
			</table>
			<script>AjaxTopMenu('div-high-menus','Inotify.php?switch-high=yes');</script>
		</div>";
	}
	
	
	function Body($content=null){
		if(isset($GLOBALS[__CLASS__][__FUNCTION__])){$this->body=$GLOBALS[__CLASS__][__FUNCTION__];return;}
		if(!isset($_SESSION["detected_lang"])){$_SESSION["detected_lang"]=null;}
		if(!isset($_SESSION["uid"])){
			$this->search_menus=null;
			$this->top_menus=null;
			}
			
		if(isset($_COOKIE["artica-template"])){
			if($GLOBALS["VERBOSE"]){
				if(function_exists("debug_mem")){debug_mem();}
				echo "Template: {$_COOKIE["artica-template"]} [".__CLASS__."/".__LINE__."]\n";
			}
		}else{$_COOKIE["artica-template"]=null;}
		$template_file="index.html";
		if(isset($GLOBALS["CHANGE_TEMPLATE"])){$template_file=$GLOBALS["CHANGE_TEMPLATE"];}		
		$template_path=dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/$template_file";
		if(!is_file($template_path)){
			if($GLOBALS["VERBOSE"]){echo "Template: $template_path no such file	\n";if(function_exists("debug_mem")){debug_mem();}}
			$template_path=dirname(__FILE__) . "/templates/default/index.html";
			$GLOBALS["CHANGE_TEMPLATE"]="default";
		}
		
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:::LANG:[{$_SESSION["detected_lang"]}]::".__FUNCTION__." open $template_path in " . basename(__FILE__). " line ".__LINE__);}
		if(strlen($_SESSION["detected_lang"])<2){
				$_SESSION["detected_lang"]=$this->_detect_lang();
				if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:::LANG:[{$_SESSION["detected_lang"]}] new LANG IS NOW {$_SESSION["detected_lang"]}::".__FUNCTION__." open $template_path ". strlen($template)." bytes in " . basename(__FILE__). " line ".__LINE__);}
		}
		if($GLOBALS["VERBOSE"]){echo "Template: open $template_path [".__CLASS__."/".__LINE__."\n";if(function_exists("debug_mem")){debug_mem();}}
		$template=$this->_readfile($template_path);
		if(function_exists("debug_mem")){debug_mem();}
		$template=str_replace("{TEMPLATE_SEARCH}",$this->search_menus,$template);
		$template=str_replace("{TEMPLATE_LEFT_MENUS}",$this->left_menus,$template);
		$template=str_replace("{TEMPLATE_TOP_MENUS}",$this->top_menus,$template);
		$template=str_replace("{TEMPLATE_CONTENT}",$content,$template);
		$template=str_replace("{TEMPLATE_TITLE}",$this->title,$template);
		$template=str_replace("{TEMPLATE_HIGH_MENUS}",$this->Build_high_menus(),$template);
		$template=str_replace("{TEMPLATE_BODY_YAHOO}",$this->YahooBody(),$template);
		if(is_file(dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/img/logo.png")){$template=str_replace("/css/images/logo.gif","templates/{$_COOKIE["artica-template"]}/img/logo.png",$template);}
		
		$right="<div style='position:absolute;left:600px;top:50px'><img src='img/rght.gif'></div>";
		$template=str_replace("{RIGHT}",$right,$template);
		
		if($GLOBALS["DEBUG_TEMPLATE"]){
			$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			if(function_exists("getmypid")){$getmypid="PID:".getmypid()." ";}
			error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::$getmypid:LANG:[{$_SESSION["detected_lang"]}]".__FUNCTION__." finish ". strlen($template)." bytes $called in " . basename(__FILE__). " line ".__LINE__);
		}
		$GLOBALS[__CLASS__][__FUNCTION__]=$template;
		$this->body=$template;
	}
	
function _get_env_var($Var){
	if(empty($GLOBALS[$Var])){
		$GLOBALS[$Var] = (!empty($GLOBALS['_SERVER'][$Var])) ?$GLOBALS['_SERVER'][$Var] : (!empty($GLOBALS['HTTP_SERVER_VARS'][$Var])) ? $GLOBALS['HTTP_SERVER_VARS'][$Var] : '';
		}
	}
	
	
function _detect_lang_forced(){
	if(isset($_SESSION["DetectedLanguage"])){
		$this->language=$_SESSION["DetectedLanguage"];
		return $_SESSION["DetectedLanguage"];
	}
	if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/class.langages.inc");}
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	
	$langfile=dirname(__FILE__)  ."/language/{$_SESSION["detected_lang"]}.db";
	if(!is_file($langfile)){$DetectedLanguage="en";}
	
	
	$this->language=$DetectedLanguage;
	return $DetectedLanguage;
}	

function _detect_lang(){
	if(!$GLOBALS["AS_ROOT"]){
		$currentPageName=CurrentPageName();
		if($currentPageName=="exec.squidguard.php"){$this->_detect_lang_forced();}
	}
	
		if(!isset($_COOKIE["artica-language"])){$_COOKIE["artica-language"]=null;}
	
		if(!$GLOBALS["AS_ROOT"]){
			if(!isset($_SESSION["MINIADM"])){
					$_SESSION["MINIADM"]=false;
					setcookie("MINIADM", "NO", time()+1000);
			}
		}
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		if($GLOBALS["EXECUTED_AS_ROOT"]){
			if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] return EXECUTED_AS_ROOT=TRUE (". basename(__FILE__).")\n" ;}
			return;}
		
		if(!$_SESSION["MINIADM"]){
			if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage"); }
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>FixedLanguage {$GLOBALS["FixedLanguage"]}</strong><br>\n";}
			if(trim($GLOBALS["FixedLanguage"])<>null){return $GLOBALS["FixedLanguage"];}
		}
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>artica-language {$_COOKIE["artica-language"]}</strong><br>\n";}
		if(isset($_COOKIE["artica-language"])){return $_COOKIE["artica-language"];}
		$GLOBALS["CURRENT_PAGE"]=CurrentPageName();
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:LANG:[{$_SESSION["detected_lang"]}] => language: _COOKIE=`{$_COOKIE["artica-language"]}` _SESSION=`{$_SESSION["detected_lang"]}` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		if(!isset($_SESSION["detected_lang"])){$_SESSION["detected_lang"]=null;}
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>detected_lang {$_SESSION["detected_lang"]}</strong><br>\n";}
		
		
		if($_SESSION["detected_lang"]<>null){
			$this->language=$_SESSION["detected_lang"];
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::return _SESSION[detected_lang] {$_SESSION["detected_lang"]} function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			return $_SESSION["detected_lang"];
		}
		if($_COOKIE["artica-language"]<>null){
		  	if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::return _COOKIE[artica-language] {$_COOKIE["artica-language"]} function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			$this->language=$_COOKIE["artica-language"];
			return $_COOKIE["artica-language"];
		}
		
	
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>language $this->language</strong><br>\n";}
	 if($this->language==null){
	 	if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/class.langages.inc");}
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::this->language is null function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
	 	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
				if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
				$disk_session_language=$sock->GET_INFO("session_language");
	 			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::disk_session_language return `$disk_session_language` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
				if($disk_session_language==null){
					$langAutodetect=new articaLang();
					$DetectedLanguage=$langAutodetect->get_languages();
					if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::->get_languages() return `$DetectedLanguage` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
					if($DetectedLanguage<>null){$disk_session_language=$DetectedLanguage;}
				}
				
				if($disk_session_language==null){
					if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::-disk_session_language return NULL, force to EN LANG function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
				    $disk_session_language="en";
				}
				$this->language=$disk_session_language;
				return $disk_session_language;
		}}
	
	
	if(isset($_SESSION["detected_lang"])){
		$langfile=dirname(__FILE__)  ."/language/{$_SESSION["detected_lang"]}.db";
		if(!is_file($langfile)){
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::LANGUAGE:Unable to stat $langfile, return EN  has default language function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			writelogs("LANGUAGE:Unable to stat $langfile, return en language has default",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return "en";}else{return $_SESSION["detected_lang"];
		}
	}
	
	 
	if(isset($_SESSION["OU_LANG"])){
		if(trim($_SESSION["OU_LANG"])<>null){
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::".__FUNCTION__." OU_LANG=`{$_SESSION["OU_LANG"]}` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);} 
			$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
			return $_SESSION["OU_LANG"];
		}
	}
	if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::LANGUAGE:Unable to define language.... function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
	
	
	include_once(dirname(__FILE__)."/class.langages.inc");
	$lang=new articaLang();
	$_SESSION["detected_lang"]=$lang->get_languages();
	if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::LANGUAGE: lang->get_languages() return {$_SESSION["detected_lang"]} function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
	writelogs("LANGUAGE: lang->get_languages() return {$_SESSION["detected_lang"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	setcookie("artica-language", $_SESSION["detected_lang"], time()+172800);
	if(strlen($_SESSION["detected_lang"])>1){return $_SESSION["detected_lang"];}
	
	// Detect HTTP_ACCEPT_LANGUAGE & HTTP_USER_AGENT.
	$this->_get_env_var('HTTP_ACCEPT_LANGUAGE');
	$this->_get_env_var('HTTP_USER_AGENT');
	
	$_AL=strtolower($GLOBALS['HTTP_ACCEPT_LANGUAGE']);
	$_UA=strtolower($GLOBALS['HTTP_USER_AGENT']);
	
	// Try to detect Primary language if several languages are accepted.
	if(!is_array($GLOBALS['_LANG'])){
		writelogs("LANGUAGE: GLOBALS['_LANG'] is not an array, return en",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return 'en';
	}
	foreach($GLOBALS['_LANG'] as $K){
		if(strpos($_AL, $K) === 0){
			if (!is_dir(dirname(__FILE__)  ."/language/$K")){return "en";}
			return $K;}
		}
	
	// Try to detect any language if not yet detected.
	foreach($GLOBALS['_LANG'] as $K){
		if(strpos($_AL, $K) !== false){
			if (!is_dir(dirname(__FILE__)  ."/language/$K")){return "en";}
			return $K;}
	}
	foreach($GLOBALS['_LANG'] as $K){
		if(preg_match("/[\[\( ]{$K}[;,_\-\)]/",$_UA)){
			if (!is_dir(dirname(__FILE__)  ."/language/$K")){return "en";}
			return $K;}
	}
	
	if (!is_dir(dirname(__FILE__)  ."/language/{$GLOBALS['_DLANG']}")){return "en";}	
	return $GLOBALS['_DLANG'];
}


	
	
	
}

function writequeries($function=null,$file=null){
	writelogs($_SERVER['REMOTE_ADDR'] . "->" . CurrentPageName() . "?{$_SERVER['QUERY_STRING']}",__FUNCTION__,__FILE__);
	
	
}


	
function writelogsSocketsLogs($text=null,$function=null,$file_source=null){
		$text=str_replace("\n"," ",$text);
		if($file_source==null){$file_source= __FILE__ ;}
		$file_source=basename($file_source);
			@mkdir(dirname(__FILE__) . '/logs/web');
		    $logFile=dirname(__FILE__) . '/logs/web/artica-socket-web.log';
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>100000){unlink($logFile);}
   		}
		 $logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:($file_source) [$function()][{$_SERVER['REMOTE_ADDR']}]:: $text\n");
		@fclose($f);
				
		
	}	
	
function Field_hidden($name,$value){
	$GLOBALS["AUTO_FORM"][$name]="text";
	return "<input type='hidden' name='$name' id='$name' value=\"$value\">";}
	
	
function Field_checkbox($name,$enabledatas,$value=null,$js=null,$help=null,$DISABLED=false){
		$def=$value;
		$checked=false;
		$CheckBoxDesignHidden=null;
		$GLOBALS["AUTO_FORM"][$name]="check";
		if($value<>null){if($value==$enabledatas){$value="checked";$checked=true;}else{$value="";}}else{$value=null;}
		if($js<>null){
			$js="$js;";
		}
	
	
		if($help<>null){
			$tpl=new templates();
			$help=str_replace("[br][br]","[br]",$help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);
			$help=str_replace('"',"`",$help);
			$help=$tpl->_ENGINE_parse_body($help);
			$help=htmlentities($help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);
			$help="OnMouseOver=\"javascript:AffBulle('$help');\" OnMouseOut=\"javascript:HideBulle();\"";
		}
	
		if($DISABLED){$disabled_infos=" DISABLED"; }
	
		
	
		if(!isset($disabled_infos)){$disabled_infos=null ;}
		
	
		
		
		if($js==null){$js="blur();";}
		$html="<input type=\"checkbox\" id='$name' name=\"$name\" 
		value=\"$enabledatas\"
		$help $disabled_infos OnClick=\"javascript:{$js}\" $value>";
		return $html;
		
	}
	
function Field_checkbox_design($name,$enabledatas,$value=null,$js=null,$help=null,$DISABLED=false){
	$def=$value;
	$checked=false;
	$CheckBoxDesignHidden=null; 
	if($name==null){$name=md5(microtime());}
	$GLOBALS["AUTO_FORM"][$name]="check";
	$help=str_replace("\n", "<br>", $help);
	$default_text="// js:$js,";
	
	
	if($value<>null){if($value==$enabledatas){$value="checked";$checked=true;}else{$value="";}}else{$value=null;}
	if($js<>null){
		$js="$js;";
	}
		
		
		if($help<>null){
			$tpl=new templates();	
			$help=str_replace("[br][br]","[br]",$help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);
			$help=str_replace('"',"`",$help);		
			$help=$tpl->_ENGINE_parse_body($help,$additional_langfile);
			$help=htmlentities($help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);
			$help="OnMouseOver=\"javascript:AffBulle('$help');\" OnMouseOut=\"javascript:HideBulle();\"";
		}
		
		if($DISABLED){
			$disabled_infos=" DISABLED";
		}

		$pic="checkbox-off-24.png";	
		if($checked){$pic="checkbox-on-24.png";}
		
if(!isset($disabled_infos)){$disabled_infos=null ;}
if(!isset($GLOBALS["CheckBoxDesignHidden"])){$CheckBoxDesignHidden="<script>setTimeout('CheckBoxDesignHidden()',900);</script>";$GLOBALS["CheckBoxDesignHidden"]=true;}
$md5=md5($name.microtime());
$id_picture="pic-".md5($name.microtime());


if($js==null){$js="blur();";}
$html="<p style='padding-top:1px;padding-bottom:10px' id='$md5-form' checkboxfunc='Design$md5()'>
<input type=\"checkbox\" id='$name' assoc='$id_picture' name=\"$name\" value=\"$enabledatas\" $value 
$help $disabled_infos><label for=\"$name\" OnClick=\"javascript:Check$md5()\"><span class=\"ui\" id='uid-$name'></span></label></p>
<script>
// $default_text
function Check$md5(){
	if(document.getElementById('$name').disabled){setTimeout('Design$md5()',150);return;}
	setTimeout('Design$md5()',150);
	setTimeout('run$md5()',250);
	setTimeout('CheckBoxDesignRebuild()',350);
	
}

function run$md5(){
{$js};

}

function Design$md5(){
var addclass='';
	document.getElementById('$md5-form').onmouseover=document.getElementById('$md5-form').style.cursor='pointer';
	document.getElementById('$md5-form').onmouseout=document.getElementById('$md5-form').style.cursor='default';
	
if(document.getElementById('$name').disabled){	
	addclass=' CheckBoxDisabled';
	document.getElementById('$md5-form').onmouseover=document.getElementById('$md5-form').style.cursor='default';
	document.getElementById('$md5-form').onmouseout=document.getElementById('$md5-form').style.cursor='default';
}



if(!document.getElementById('$name').checked){
	document.getElementById('$md5-form').className='CheckBoxUnChecked'+addclass;

		
	}else{
	document.getElementById('$md5-form').className='CheckBoxChecked'+addclass;
	
	}
	


}


setTimeout('Design$md5()',150);

</script>
";
return $html;

}

function Field_checkbox_yesno_design($name,$value=null,$js=null,$help=null,$DISABLED=false){
	$def=$value;
	$checked=false;
	$CheckBoxDesignHidden=null;
	$GLOBALS["AUTO_FORM"][$name]="check";
	$help=str_replace("\n", "<br>", $help);
	$default_text="// js:$js,";
	$enabledatas="no";

	if($value<>null){
			if($value=="yes"){
					$value="checked";
					$checked=true;
					$enabledatas="yes";
			}else{$value="";}}else{$value=null;$enabledatas="no";}
	if($js<>null){
		$js="$js;";
	}


	if($help<>null){
		$tpl=new templates();
		$help=str_replace("[br][br]","[br]",$help);
		$help=str_replace("\n","",$help);
		$help=str_replace("\r\n","",$help);
		$help=str_replace("\r","",$help);
		$help=str_replace('"',"`",$help);
		$help=$tpl->_ENGINE_parse_body($help);
		$help=htmlentities($help);
		$help=str_replace("\n","",$help);
		$help=str_replace("\r\n","",$help);
		$help=str_replace("\r","",$help);
		$help="OnMouseOver=\"javascript:AffBulle('$help');\" OnMouseOut=\"javascript:HideBulle();\"";
	}

	if($DISABLED){
		$disabled_infos=" DISABLED";
	}

	$pic="checkbox-off-24.png";
	if($checked){$pic="checkbox-on-24.png";}

	if(!isset($disabled_infos)){$disabled_infos=null ;}
	if(!isset($GLOBALS["CheckBoxDesignHidden"])){$CheckBoxDesignHidden="<script>setTimeout('CheckBoxDesignHidden()',900);</script>";$GLOBALS["CheckBoxDesignHidden"]=true;}
	$md5=md5($name.microtime());
	$id_picture="pic-".md5($name.microtime());


	if($js==null){$js="blur();";}
	$html="<p style='padding-top:1px;padding-bottom:10px' id='$md5-form' checkboxfunc='Design$md5()'>
	<input type=\"checkbox\" id='$name' assoc='$id_picture' name=\"$name\" value=\"$enabledatas\" $value
	$help $disabled_infos><label for=\"$name\" OnClick=\"javascript:Check$md5()\"><span class=\"ui\" id='uid-$name'></span></label></p>
	<script>
	// $default_text
	function Check$md5(){
	if(document.getElementById('$name').disabled){setTimeout('Design$md5()',150);return;}
			setTimeout('Design$md5()',150);
			setTimeout('run$md5()',250);
					setTimeout('CheckBoxDesignRebuild()',350);

}

					function run$md5(){
					{$js};

}

function Design$md5(){
	var addclass='';
	document.getElementById('$md5-form').onmouseover=document.getElementById('$md5-form').style.cursor='pointer';
	document.getElementById('$md5-form').onmouseout=document.getElementById('$md5-form').style.cursor='default';

	if(document.getElementById('$name').disabled){
		addclass=' CheckBoxDisabled';
		document.getElementById('$md5-form').onmouseover=document.getElementById('$md5-form').style.cursor='default';
		document.getElementById('$md5-form').onmouseout=document.getElementById('$md5-form').style.cursor='default';
	}



	if(!document.getElementById('$name').checked){
		document.getElementById('$md5-form').className='CheckBoxUnChecked'+addclass;
		document.getElementById('$name').value='no';
		
	
	}else{
		document.getElementById('$md5-form').className='CheckBoxChecked'+addclass;
		document.getElementById('$name').value='yes'
	}



}


setTimeout('Design$md5()',150);

	</script>
	";
return $html;

}

function texthref($text,$js,$style=null){
	if($style==null){$style="font-size:14px;text-decoration:underline;font-weight:bold'";}
	if($js<>null){$js="OnClick=\"javascript:$js\"";}
	return "<a href=\"javascript:Blurz();\"  $js style='$style'>$text</a>";
	
}

function Field_yesno_checkbox($name,$value,$js=null){
	$value=strtolower(trim($value));
	$def=$value;
	if($js<>null){$js=" OnClick=\"javascript:$js\"";}
if($value=="yes"){$value="checked";}else{$value="";}	
$html="<!-- $value: $def -->
<input type=\"checkbox\" id='$name' name=\"$name\" value=\"yes\" $value style='padding:0px;margin:0px;border:0px' $js>";
return $html;}

function miniadm_BuildFieldToolTip($help){
	$tpl=new templates();
	$n = rand(10e16, 10e20);
	$rand=base_convert($n, 10, 36);
	$fieldid=md5($help.$rand);
	$text=$help;
	
	$text=$tpl->_ENGINE_parse_body($text);
	$text=str_replace("\n" ,"<br>", $text);
	$text=str_replace("\"" ,"'", $text);

return "
<img src='img/help.png'  id='$fieldid'>
<script>		
$(\"#$fieldid\").on({
	\"mouseover\": function() {
	$(this).tooltip(\"show\");
	},
	blur: function() {
	$(this).tooltip(\"hide\");
	},
	mouseout: function() {
	$(this).tooltip(\"hide\");
	}
}).tooltip({
placement: \"left\",
trigger: \"manual\",
html:true,
title: \"<div style='font-size:12px'>$text</div>\",

});\n</script>";
}



function help_icon($help,$no_float=false,$additional_langfile=null){
	$DisableHelpToolTips=0;
	if(isset($_COOKIE["DisableHelpToolTips"])){$DisableHelpToolTips=$_COOKIE["DisableHelpToolTips"];}
	if($DisableHelpToolTips==1){return null;}
	$myfloat="float:right";
		
return "<div style='$myfloat;margin-top:-5px'>". imgtootltip("help-32.png",$help)."</div>";
	
}


function field_ipv4($field_name,$defaultvalue,$style=null,$netwrok=false,$jsAdd=null){
	$jsPressKey=null;
	$OnClick=null;
	$OnFocus=null;
	$OnChange=null;
	$OnMouseOver=null;
	$OnMouseOut=null;
	$size=null;
	$help=null;
	$disa=null;
	$jsAddInside=null;
	$jsCheck=null;
	$watermark="{$_SERVER["REMOTE_ADDR"]}";
	$width=160;
	if(preg_match("#explain=(.+?);#", $style,$re)){
		$help=$re[1];
		$style=str_replace("explain={$re[1]};", "", $style);
	}
	
	
	if(preg_match("#font-size:([0-9]+)px#", $style,$re)){
		$defined_size=intval($re[1]);
		if($defined_size>16){$width=170;}
		if($defined_size>21){$width=204;}
		if($defined_size>24){$width=210;}
		if($defined_size>25){$width=256;}
		if($defined_size>30){$width=320;}
	}
	
	
	if(preg_match("#(netmask)#", $field_name)){
		$watermark="255.255.255.0";
	}
	if(preg_match("#(cdir|network)#", $field_name)){
		$t=explode(".",$_SERVER["REMOTE_ADDR"]);
		unset($t[count($t)-1]);
		$ipaddr=@implode(".", $t).".0";
		$watermark="$ipaddr/32";
	}
	
	if(preg_match("#(broadcast)#", $field_name)){
		$t=explode(".",$_SERVER["REMOTE_ADDR"]);
		unset($t[count($t)-1]);
		$watermark=@implode(".", $t).".255";
	}
	
	if(preg_match("#(gateway)#", $field_name)){
		$t=explode(".",$_SERVER["REMOTE_ADDR"]);
		unset($t[count($t)-1]);
		$watermark=@implode(".", $t).".1";
	}	
	
	$FocusCss="onfocus=\"this.setAttribute('class','active')\" 
	onblur=\"this.removeAttribute('class');\" style='$style'";
	if($_SESSION["MINIADM"]==true){
		$FocusCss=null;
	}
	
	if(preg_match("#.+?:(.+)#",$jsAdd)){$jsAddInside=$jsAdd;}
	if(preg_match("#\(event\)#",$jsAdd)){$jsCheck=$jsAdd;}
	
	if($jsCheck<>null){
		$jsCheck="OnKeyPress=\"$jsCheck\"";
	}
	
	if($help<>null){
		$tpl=new templates();
		$helptxt=$tpl->_ENGINE_parse_body($help);
		$helptxt=str_replace("\"", "'", $helptxt);
		$helptxt=str_replace("\n", "", $helptxt);
		$OnMouseOver="\nOnMouseOver=\"javascript:$(this).tooltip('show');\"";
		$OnMouseOut="\nOnMouseOut=\"javascript:$(this).tooltip('hide');\"\n";
		$OnMouseOut="{$OnMouseOut}data-toggle=\"tooltip\" data-html='true'\n";
		$OnMouseOut="{$OnMouseOut}data-title=\"<div style=font-size:12px>$helptxt</div>\"\n";
	
	
	}
	
	return "
	<!-- - FONT SIZE : $defined_size -->
	<input type='text' id='$field_name' name='$field_name' $jsAddInside$jsCheck value=\"$defaultvalue\" 
	style=\"$style;min-width:145px;width:{$width}px\" $OnFocus $OnChange $jsPressKey $OnClick  $FocusCss $size $OnMouseOver $OnMouseOut$disa>
<script>
	
	$(function(){
    	  $('#$field_name').watermark('$watermark');
	});
	
	</script>	
	
	";	
	
	
	
	
	$withPX=38;
	if(preg_match("#font-size:([0-9]+)#", $style,$re)){
		if($re[1]>16){$withPX=42;}
	}
	
	preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$defaultvalue,$re);
	$locknet=0;	
	if($netwrok){$locknet=1;}	
	if(!preg_match("#.+?\(.*?\)#", $jsAdd)){$jsAdd=null;}
		
	if(!isset($re[1])){$re[1]=null;}
	if(!isset($re[2])){$re[2]=null;}
	if(!isset($re[3])){$re[3]=null;}
	if(!isset($re[4])){$re[4]=null;}
$html="
<table width=10%>
<tr>
	<td width=1% nowrap class='FieldIpv4'>" . Field_text($field_name.'_0',$re[1],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td style='font-size:13px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>.</td>
	<td width=1% nowrap class='FieldIpv4'>" . Field_text($field_name.'_1',$re[2],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td style='font-size:13px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>.</td>
	<td width=1% nowrap class='FieldIpv4'>" . Field_text($field_name.'_2',$re[3],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td style='font-size:13px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>.</td>
	<td width=1% nowrap class='FieldIpv4'>" . Field_text($field_name.'_3',$re[4],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td width=1% nowrap><span id='ico-ipv4-$field_name'><img src=img/ok16.png></span></td>
	
</tr>
</table>
<input type='hidden' value='$defaultvalue' id='$field_name' name='$field_name'>
<script>



$(document).ready(function(){
	var locknet=$locknet;
	if(locknet==1){
		document.getElementById('{$field_name}_3').disabled=true;
		document.getElementById('{$field_name}_3').value=0;
	}
	
$('input[id^={$field_name}_]').keypress(function(event){
    var key;
    if($.browser.mozilla) {key = event.which;} else {key = event.keyCode;}
    //$('#dnshosts_ip_p0').focus();
    var myid=$(this).attr('id');
    var re = new RegExp(/{$field_name}_([0-9]+)/);
    var m = re.exec(myid);
    var number=Math.floor(m[1]);
	var next=number+1
	document.getElementById('$field_name').value=document.getElementById('{$field_name}_0').value+'.'+document.getElementById('{$field_name}_1').value+'.'+document.getElementById('{$field_name}_2').value+'.'+document.getElementById('{$field_name}_3').value;
	
    if(key == 190 || key == 46) {
    	if(document.getElementById('{$field_name}_'+next)){ $('#{$field_name}_'+next).focus();}
   	 	document.getElementById('$field_name').value=document.getElementById('{$field_name}_0').value+'.'+document.getElementById('{$field_name}_1').value+'.'+document.getElementById('{$field_name}_2').value+'.'+document.getElementById('{$field_name}_3').value;
    	$jsAdd;
    	document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/ok16.png>';
   	 	if(!ValidateIPAddress(document.getElementById('$field_name').value)){
   	 		document.getElementById('$field_name').value='';
			document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/status_warning.png>';
		}
    	return false;
    } 
    else if(key == 8) {
      if($(this).val() == '') {
        $(this).prev().focus();
        return false;
      }
    }

    return true;
  });
  
  
$('input[id^={$field_name}_]').change(function () {
	document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/ok16.png>';
	document.getElementById('$field_name').value=document.getElementById('{$field_name}_0').value+'.'+document.getElementById('{$field_name}_1').value+'.'+document.getElementById('{$field_name}_2').value+'.'+document.getElementById('{$field_name}_3').value;
	$jsAdd;
	if(!ValidateIPAddress(document.getElementById('$field_name').value)){
		document.getElementById('$field_name').value='';
		document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/status_warning.png>';
	}
   
})
  
  

});

</script>

";
return $html;	
	
}
function field_ipv4_cdir($field_name,$defaultvalue,$style=null,$netwrok=false,$jsAdd=null){
	if($style==null){$style="font-size:13px";}
	$fontsize="13";
	$withPX=35;
	if(preg_match("#font-size:([0-9]+)#", $style,$re)){
		$fontsize=$re[1];
		if($re[1]>16){$withPX=40;}
	}
	
	preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\/([0-9]+)#',$defaultvalue,$re);
	$locknet=0;	
	if($netwrok){$locknet=1;}	
	if(!preg_match("#.+?\(.*?\)#", $jsAdd)){$jsAdd=null;}
		
$html="
<table width=10%>
<tr>
	<td width=1% nowrap>" . Field_text($field_name.'_0',$re[1],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td style='font-size:{$fontsize}px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>.</td>
	<td width=1% nowrap>" . Field_text($field_name.'_1',$re[2],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td style='font-size:{$fontsize}px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>.</td>
	<td width=1% nowrap>" . Field_text($field_name.'_2',$re[3],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td style='font-size:{$fontsize}px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>.</td>
	<td width=1% nowrap>" . Field_text($field_name.'_3',$re[4],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
	<td width=1% nowrap><span id='ico-ipv4-$field_name'><img src=img/ok16.png></span></td>
	<td width=1% style='font-size:{$fontsize}px;padding:0px;margin:0px;height:auto;width:auto' width=1% nowrap>/</td>
	<td width=1% nowrap>" . Field_text($field_name.'_4',$re[5],"width:{$withPX}px;$style;padding:3px;text-align:center")."</td>
</tr>
<tr>
	<td colspan=10 align='right'><code id='$field_name-show' style='font-size:11px;text-align:right'>$defaultvalue</code></td>
</tr>
<input type='hidden' value='$defaultvalue' id='$field_name' name='$field_name'>
<script>



$(document).ready(function(){
	var locknet=$locknet;
	if(locknet==1){
		document.getElementById('{$field_name}_3').disabled=true;
		document.getElementById('{$field_name}_3').value=0;
	}
	
	
$('input[id^={$field_name}_]').keypress(function(event){
    var key;
    if($.browser.mozilla) {key = event.which;} else {key = event.keyCode;}
    //$('#dnshosts_ip_p0').focus();
    var myid=$(this).attr('id');
    var re = new RegExp(/{$field_name}_([0-9]+)/);
    var m = re.exec(myid);
    var number=Math.floor(m[1]);
	var next=number+1
	var cdir=document.getElementById('{$field_name}_4').value;
	document.getElementById('$field_name').value=document.getElementById('{$field_name}_0').value+'.'+document.getElementById('{$field_name}_1').value+'.'+document.getElementById('{$field_name}_2').value+'.'+document.getElementById('{$field_name}_3').value;
	
    if(key == 190 || key == 46) {
    	if(document.getElementById('{$field_name}_'+next)){ $('#{$field_name}_'+next).focus();}
   	 	document.getElementById('$field_name').value=document.getElementById('{$field_name}_0').value+'.'+document.getElementById('{$field_name}_1').value+'.'+document.getElementById('{$field_name}_2').value+'.'+document.getElementById('{$field_name}_3').value;
   	 	document.getElementById('$field_name-show').innerHTML=document.getElementById('$field_name').value;
    	$jsAdd;
    	document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/ok16.png>';
   	 	if(!ValidateIPAddress(document.getElementById('$field_name').value)){
   	 		document.getElementById('$field_name').value='';
			document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/status_warning.png>';
		}
		if(cdir.length==0){
   	 		document.getElementById('$field_name').value='';
			document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/status_warning.png>';		
		}
		
    	return false;
    } 
    else if(key == 8) {
      if($(this).val() == '') {
        $(this).prev().focus();
        return false;
      }
    }

    return true;
  });
  
  
$('input[id^={$field_name}_]').change(function () {
	var cdir=document.getElementById('{$field_name}_4').value;
	document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/ok16.png>';
	document.getElementById('$field_name').value=document.getElementById('{$field_name}_0').value+'.'+document.getElementById('{$field_name}_1').value+'.'+document.getElementById('{$field_name}_2').value+'.'+document.getElementById('{$field_name}_3').value;
	document.getElementById('$field_name').value=document.getElementById('$field_name').value+'/'+cdir;
	document.getElementById('$field_name-show').innerHTML=document.getElementById('$field_name').value;
	$jsAdd;
	
	if(cdir.length==0){
   		document.getElementById('$field_name').value='';
		document.getElementById('ico-ipv4-$field_name').innerHTML='<img src=img/status_warning.png>';		
	}	
   
})
  
  

});

</script>

";
return $html;	
	
}
function field_date($name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null){
	$value=trim($value);
	if($class<>null){
		if(preg_match("#script:(.+)#",$class,$re)){$class=null;$jsPressKey=$re[1];}
	}	
	
	if(preg_match("#mindate:([0-9\-]+);maxdate:([0-9\-]+)#", $class,$re)){
		$mindate=",minDate: \"{$re[1]}\"";
		$maxdate=",maxDate: \"{$re[2]}\"";
		$class=null;
	}
	
	if($style==null){$style='width:98%;font-size:12px';$size="size=50";}
	if($class<>null){$class="class='$class'";}
	if($OnChange<>null){$OnChange="\nOnChange=\"javascript:$OnChange\"";}
	if($jsPressKey<>null){$jsPressKey="\nonkeypress=\"javascript:$jsPressKey;\"";}
	if($OnClick<>null){$OnClick="OnClick=\"javascript:$OnClick;\"";}
	

	
	if(!preg_match("#font-size#",$style)){$style=$style.";font-size:12px";}
	
	if($help<>null){
		$tpl=new templates();
		$help_source=$help;
		$help_source=str_replace('{','',$help_source);
		$help_source=str_replace('}','',$help_source);
		$help=$tpl->_ENGINE_parse_body($help);
		$help=str_replace("\n","",$help);
		$help=str_replace("\r\n","",$help);
		$help=str_replace("\r","",$help);
		$help=str_replace('"',"`",$help);	
	
		if($helpInside==false){
		$help=help_icon($help);
		}
		else{
		$OnMouseOver="OnMouseOver=\"javascript:AffBulle('$help');lightup(this, 100);\" ";
		$OnMouseOut="OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\"";
		$help=null;
		}
	}
	
	if($DISABLED){$disa=" DISABLED";}
	return "$help
	<input type='text' id='$name' name='$name' value=\"$value\" $OnChange $jsPressKey $OnClick style='$style' $class $size $OnMouseOver $OnMouseOut$disa>
	<script>
		$(document).ready(function(){
		$( \"#$name\").datepicker({ dateFormat: \"yy-mm-dd\" $mindate$maxdate});
	});
	</script>
	";	
	
	
}

function field_iphone_time($name,$value){
	$t=time();
	$tpl=new templates();
	if(trim($value)==null){$value=date("H:i");}
	$hourt=$tpl->javascript_parse_text("{hours}");
	$style="style=\"display:none\"";
return "$help
	<input type='text' id='$name' name='$name' value=\"$value\" $style>
	<script>
			$(function(){
    $('#$name').scroller({
        preset: 'time',
        theme: 'ios',
        display: 'inline',
        mode: 'scroller',
        hourText:'$hourt',
        timeFormat:'HH:ii',
        timeWheels:'HH:ii'
        
    });    
});
	</script>
	";			
	

	
}

function field_time($name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null){
	$value=trim($value);
	$size=null;
	if($class<>null){
		if(preg_match("#script:(.+)#",$class,$re)){$class=null;$jsPressKey=$re[1];}
	}	
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{time}");
	
	
	if($style==null){$style='width:98%;font-size:12px';$size="size=50";}
	if($class<>null){$class="class='$class'";}
	if($OnChange<>null){$OnChange="\nOnChange=\"javascript:$OnChange\"";}
	if($jsPressKey<>null){$jsPressKey="\nonkeypress=\"javascript:$jsPressKey;\"";}
	if($OnClick<>null){$OnClick="OnClick=\"javascript:$OnClick;\"";}
	

	
	if(!preg_match("#font-size#",$style)){$style=$style.";font-size:12px";}
	
	if($help<>null){
		$tpl=new templates();
		$help_source=$help;
		$help_source=str_replace('{','',$help_source);
		$help_source=str_replace('}','',$help_source);
		$help=$tpl->_ENGINE_parse_body($help);
		$help=str_replace("\n","",$help);
		$help=str_replace("\r\n","",$help);
		$help=str_replace("\r","",$help);
		$help=str_replace('"',"`",$help);	
	
		if($helpInside==false){
		$help=help_icon($help);
		}
		else{
		$OnMouseOver="OnMouseOver=\"javascript:AffBulle('$help');lightup(this, 100);\" ";
		$OnMouseOut="OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\"";
		$help=null;
		}
	}
	
	if($DISABLED){$disa=" DISABLED";}	
	
	

	return "$help
	<input type='text' id='$name' name='$name' value=\"$value\" $OnChange $jsPressKey $OnClick style='$style' $class $size $OnMouseOver $OnMouseOut$disa>
	<script>
		$(document).ready(function(){
		$('#$name').timepicker({
			timeOnlyTitle: '$title',
			hourGrid: 4,minuteGrid: 10});
		
	});
	</script>
	";		
	
	
}

function Field_spacer_table($size){
	return "<tr style='height:{$size}px'><td colspan=2 style='text-align:right;padding-top:20px;font-size:{$size}px'>
	&nbsp;</td></tr>";
	
}

function Field_button_table_autonome($name,$function,$size=22){
	if($GLOBALS["AUTO_FORM_LOCK"]==true){return null;}
	$t=time();
	$GLOBALS["AUTO_FORM_FUNC"]="Save$t";
	return "<tr style='height:70px'><td colspan=2 style='text-align:right;padding-top:20px;font-size:{$size}px'>
			<div style='text-align:right;width:100%;border-top:1px solid #CCCCCC;padding-top:5px;padding-bottom:20px'>
			".button($name,"$function()",$size)."</div></td></tr>";
}

function Field_buttons_table_autonome($array,$size=22){
	if($GLOBALS["AUTO_FORM_LOCK"]==true){return null;}
	$t=time();
	$GLOBALS["AUTO_FORM_FUNC"]="Save$t";
	$f[]= "<tr>
			<td colspan=2 style='text-align:right;padding-top:20px;font-size:{$size}px'>
			<div style='text-align:right;width:100%;border-top:2px solid #CCCCCC;padding-top:5px'><table style='float:right'><tr>";
	while (list ($name, $function) = each ($array) ){
		$f[]="<td style='padding-right:10px;padding-left:10px;'>".button($name,"{$function}()",$size)."</td>";
		
	}
	$f[]="</tr></table></div></td></tr>";
	return @implode("\n", $f);
}

function Field_button_table($name,$size=22){
	if($GLOBALS["AUTO_FORM_LOCK"]==true){return null;}
	$t=time();
	$GLOBALS["AUTO_FORM_FUNC"]="Save$t";
	return "<tr><td colspan=2 style='text-align:right'><hr>".button($name,"Save$t()",$size)."</td></tr>";
}

function Field_text_table($name,$caption,$value=null,$fontsize=18,$explain=null,$fieldsize=200){
	$script=null;
	if(preg_match("#^script:(.+)#", $explain,$re)){
		$script=$re[1];
		$explain=null;
	}
	
	if($explain<>null){
		$caption=texttooltip($caption,"$explain");
	}
		
	
	$html[]="<tr><td class=legend style='font-size:{$fontsize}px'>{$caption}:</td>";
	
	
	//$name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null
	
	$html[]="<td style='font-size:18px'>".Field_text(
			$name,$value,"width:{$fieldsize}px;font-size:{$fontsize}px",null,null,null,false,$script)."</td></tr>";
	return @implode("\n", $html);
}

function Field_area_table($name,$caption,$value=null,$fontsize=18,$explain=null,$height=145){
	$html[]="<tr><td class=legend style='font-size:{$fontsize}px'>{$caption}:</td>";
	if($explain<>null){$explain="<div class=explain>$explain</div>";}
	$html[]="<td style='font-size:18px'>
			$explain
			<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:{$height}px;border:5px solid #8E8E8E;
	overflow:auto;font-size:{$fontsize}px !important' id='$name' name='$name'>$value</textarea></td></tr>";
	return @implode("\n", $html);
	
}


function Field_password_table($name,$caption,$value=null,$fontsize=18,$explain=null,$fieldsize=200){
	$script=null;
	if(preg_match("#^script:(.+)#", $explain,$re)){
		$script=$re[1];
		$explain=null;
	}
	
	$html[]="<tr><td class=legend style='font-size:{$fontsize}px'>{$caption}:</td>";
	if($explain<>null){$explain="explain=$explain;";}
	$html[]="<td style='font-size:18px'>".Field_password(
	$name,$value,"{$explain}width:{$fieldsize}px;font-size:{$fontsize}px",null,null,null,false,$script)."</td></tr>";
	return @implode("\n", $html);
}
function Field_ipv4_table($name,$caption,$value=null,$fontsize=18,$explain=null,$fieldsize=200){
	$html[]="<tr><td class=legend style='font-size:{$fontsize}px'>{$caption}:</td>";
	if($explain<>null){$explain="explain=$explain;";}
	$html[]="<td style='font-size:18px'>".field_ipv4($name,$value,"{$explain}width:{$fieldsize}px;font-size:{$fontsize}px")."</td></tr>";
	return @implode("\n", $html);
}
function Field_checkbox_table($name,$caption,$value=null,$fontsize=18,$explain=null,$javascript=null){
	
	
	if($explain<>null){$caption=texttooltip($caption,$explain);$explain=null;}
	$html[]="<tr><td class=legend style='font-size:{$fontsize}px'>{$caption}:</td>";
	
	$html[]="<td style='font-size:{$fontsize}px'>".Field_checkbox_design($name,1,$value,$javascript,$explain)."</td></tr>";
	return @implode("\n", $html);	
	
}

function Field_list_table($name,$caption,$value=null,$fontsize=18,$ARRAY,$JS_SCRIPT=null){
	$html[]="<tr><td class=legend style='font-size:{$fontsize}px'>{$caption}:</td>";
	
	//$Hash,$FieldName,$default=null,$java=null,$class='',$maxlength=0,$style_of_field=null
	
	$html[]="<td style='font-size:18px'>".Field_array_Hash($ARRAY, $name,$value,$JS_SCRIPT,null,0,"font-size:{$fontsize}px")."</td></tr>";
	return @implode("\n", $html);	
}

function field_textarea($name,$value,$fontsize,$width="100%",$height=450){
	
	return "<textarea style='margin-top:5px;font-family:Courier New;
			font-weight:bold;width:{$width};height:{$height}px;border:5px solid #8E8E8E;
			overflow:auto;font-size:{$fontsize}px !important' 
			id='$name' name=\"$name\">$value</textarea>";
	
}

function Field_autocomplete($name,$value=null,$style=null,$autocomplete_script=null,$jsPressKey=null,$autocomplete_function=null){
	$value=trim($value);
	$GLOBALS["AUTO_FORM"][$name]="text";
	$OnMouseOver=null;
	$OnMouseOut=null;
	$size=null;
	$disa=null;
	$shadow=null;
	$shadowjs=null;
	$helptxt=null;
	$class=null;
	$help=null;

	if(preg_match("#explain=(.+?);#", $style,$re)){
		$help=$re[1];
		$style=str_replace("explain={$re[1]};", "", $style);
	}


	if(preg_match("#^shadow:(.*)#", $value,$re)){
		$shadow=$re[1];
		$value=null;
	}


	

	if(preg_match("#^focus:(.*)#", $value,$re)){
		$OnFocus="OnFocus=\"javascript:this.value='';\"";
		$value=$re[1];
	}

	if($style==null){$style='width:98%;font-size:12px';$size="size=50";}
	if($class<>null){$class="class='$class'";}
	if($jsPressKey<>null){$jsPressKey="\nonkeypress=\"javascript:$jsPressKey;\"";}
	



	if(!preg_match("#font-size#",$style)){$style=$style.";font-size:12px";}

	if($help<>null){
		$tpl=new templates();
		$helptxt=$tpl->_ENGINE_parse_body($help);
		$helptxt=str_replace("\"", "'", $helptxt);
		$helptxt=str_replace("\n", "", $helptxt);
		$OnMouseOver="\nOnMouseOver=\"javascript:$(this).tooltip('show');\"";
		$OnMouseOut="\nOnMouseOut=\"javascript:$(this).tooltip('hide');\"\n";
		$OnMouseOut="{$OnMouseOut}data-toggle=\"tooltip\" data-html='true'\n";
		$OnMouseOut="{$OnMouseOut}data-title=\"<div style=font-size:12px>$helptxt</div>\"\n";


	}






	$FocusCss="\nonfocus=\"this.setAttribute('class','active')\"\nonblur=\"this.removeAttribute('class');\" $class";
	if($_SESSION["MINIADM"]==true){
		$FocusCss=null;
	}

	

	if($shadow<>null){
		$shadowjs=" <script>$(function(){ $('#$name').watermark('$shadow'); }); </script>";
	}
	$autojs=null;
	if($autocomplete_function<>null){
		$autojs=",
   	 		onSelect: function(e, term, item){ $autocomplete_function(term); }";
		
	}

	$autocomplete="<script>
	$('#$name').autoComplete({
		source: function(term, response){
        $.getJSON('$autocomplete_script', { q: term }, function(data){ response(data); });
    	}$autojs
	});</script>";

	return "<!-- help=$helptxt -->
	<input type='text' id='$name' name='$name' value=\"$value\" style=\"$style\"$FocusCss$jsPressKey$size$OnMouseOver $OnMouseOut$disa>$shadowjs$autocomplete";
}

function Field_text($name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null){
	$value=trim($value);
	$GLOBALS["AUTO_FORM"][$name]="text";
	$OnMouseOver=null;
	$OnMouseOut=null;
	$size=null;
	$disa=null;
	$shadow=null;
	$shadowjs=null;
	$helptxt=null;
	
	
	if(preg_match("#explain=(.+?);#", $style,$re)){
		$help=$re[1];
		$style=str_replace("explain={$re[1]};", "", $style);
	}
	
	
	if(preg_match("#^shadow:(.*)#", $value,$re)){
		$shadow=$re[1];
		$value=null;
	}
	
	
	if($class<>null){
		if(preg_match("#script:(.+)#",$class,$re)){$class=null;$jsPressKey=$re[1];}
	}

	if(preg_match("#^focus:(.*)#", $value,$re)){
		$OnFocus="OnFocus=\"javascript:this.value='';\"";
		$value=$re[1];
	}
	
	if($style==null){$style='width:98%;font-size:12px';$size="size=50";}
	if($class<>null){$class="class='$class'";}
	if($OnChange<>null){$OnChange="\nOnChange=\"javascript:$OnChange\"";}
	if($jsPressKey<>null){$jsPressKey="\nonkeypress=\"javascript:$jsPressKey;\"";}
	if($OnClick<>null){$OnClick="\nOnClick=\"javascript:$OnClick;\"";}
	

	
	if(!preg_match("#font-size#",$style)){$style=$style.";font-size:12px";}
	
	if($help<>null){
		$tpl=new templates();
		$helptxt=$tpl->_ENGINE_parse_body($help);
		$helptxt=str_replace("\"", "'", $helptxt);
		$helptxt=str_replace("\n", "", $helptxt);
		$OnMouseOver="\nOnMouseOver=\"javascript:$(this).tooltip('show');\"";
		$OnMouseOut="\nOnMouseOut=\"javascript:$(this).tooltip('hide');\"\n";
		$OnMouseOut="{$OnMouseOut}data-toggle=\"tooltip\" data-html='true'\n";
		$OnMouseOut="{$OnMouseOut}data-title=\"<div style=font-size:12px>$helptxt</div>\"\n";
		
		
	}
	
	
	
	
	
	
	$FocusCss="\nonfocus=\"this.setAttribute('class','active')\"\nonblur=\"this.removeAttribute('class');\" $class";
	if($_SESSION["MINIADM"]==true){
		$FocusCss=null;
	}
	
	if($DISABLED){$disa=" DISABLED";}
	
	if($shadow<>null){
		$shadowjs=" <script> $(function(){ $('#$name').watermark('$shadow'); }); </script>";
	}
	
	
	
	return "<!-- help=$helptxt -->
	<input type='text' id='$name' name='$name' value=\"$value\" style=\"$style\"$FocusCss$OnChange$jsPressKey$OnClick$size$OnMouseOver $OnMouseOut$disa>$shadowjs";
}





function Field_password($name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null){
	$nolock=false;
	$OnMouseOver=null;
	$OnMouseOut=null;
	$size=null;
	if(preg_match("#nolock:(.+)#", $name,$re)){$name=$re[1];$nolock=true;}
	if($style==null){$style='width:120px';$size="size=50";}
	if($class<>null){
		if(preg_match("#script:(.+)#",$class,$re)){$class=null;$jsPressKey=$re[1];}
	}		
	if($class<>null){$class="class='$class'";}
	if($OnChange<>null){$OnChange="\nOnChange=\"javascript:$OnChange\"";}
	if($jsPressKey<>null){$jsPressKey="\nonkeypress=\"javascript:$jsPressKey;\"";}
	if(!preg_match("#font-size#",$style)){$style=$style.";font-size:12px";}
	if($help<>null){
		$tpl=new templates();
		$help_source=$help;
		$help_source=str_replace('{','',$help_source);
		$help_source=str_replace('}','',$help_source);
		$help=$tpl->_ENGINE_parse_body($help);
		$help=str_replace("\n","",$help);
		$help=str_replace("\r\n","",$help);
		$help=str_replace("\r","",$help);
		$help=str_replace('"',"`",$help);	
	
		if($helpInside==false){
		$help=help_icon($help);
		}
		else{
		$OnMouseOver="OnMouseOver=\"javascript:AffBulle('$help');lightup(this, 100);\"";
		$OnMouseOut="OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\"";
		$help=null;
		}
	}
	
	$md=md5($name);
	$img=imgtootltip('key_small.gif','{switch_view}',"SwitchPassword('$md','$name')");
	if($nolock){
		return "<input type='password' id='$name' name='$name' value=\"$value\" $OnChange $jsPressKey style='$style' $class $size $OnMouseOver $OnMouseOut>";
		
	}
	return "
	<div style='background-image:url(img/bg_pic-1.png);background-repeat:repeat-x;'>
		$help$img
			<span id='$md'>
				<input type='password' id='$name' name='$name' value=\"$value\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" $OnChange $jsPressKey style='$style' $class $size $OnMouseOver $OnMouseOut>
			</span>
	</div>";
}

function imgsimple($img,$tooltip=null,$js=null,$align=null,$imgid=null){
	$size=null;
	if(preg_match("#-48\.#", $img)){$size=";width:48px;max-width:48px;";}
	if(preg_match("#-64\.#", $img)){$size=";width:64px;max-width:64px;";}
	if(preg_match("#-32\.#", $img)){$size=";width:32px;max-width:32px;";}
	if(preg_match("#-24\.#", $img)){$size=";width:24px;max-width:24px;";}
	if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
	if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
	$md5=md5("$img$tooltip$js$align$imgid".time());
	if($imgid<>null){$id="id=\"$imgid\"";}else{$id=null;}
	$image_path="img/$img";
	if(preg_match('#images\.listener\.php#',$img)){$image_path=$img;}
	if(preg_match('#^ressources#',$img)){$image_path=$img;}	
	if(preg_match('#img\/(.+)#',$img,$re)){$image_path=$img;}
	if(is_file($img)){$image_path=$img;}
	if($js<>null){$js="OnClick=\"javascript:$js;\"";}	
	if($js==null){$js="OnClick=\"javascript:Blurz();\"";}
	$IMAGE="<a href=\"javascript:Blurz();\" $js><img src=\"$image_path\" style=\"border:0px;$size\" $id></a>";
	return $IMAGE;
		
	
	
}

function string_to_regex($pattern){
	if(trim($pattern)==null){return null;}
	$pattern=str_replace("/", "\/", $pattern);
	$pattern=str_replace(".", "\.", $pattern);
	//$pattern=str_replace("-", "\-", $pattern);
	$pattern=str_replace("[", "\[", $pattern);
	$pattern=str_replace("]", "\]", $pattern);
	$pattern=str_replace("(", "\(", $pattern);
	$pattern=str_replace(")", "\)", $pattern);
	$pattern=str_replace("$", "\$", $pattern);
	$pattern=str_replace("?", "\?", $pattern);
	$pattern=str_replace("#", "\#", $pattern);
	$pattern=str_replace("{", "\{", $pattern);
	$pattern=str_replace("}", "\}", $pattern);
	$pattern=str_replace("^", "\^", $pattern);
	$pattern=str_replace("!", "\!", $pattern);
	$pattern=str_replace("+", "\+", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);
	$pattern=str_replace("|", "\|", $pattern);
	return $pattern;
	
}

function string_to_sql_search($pattern,$STRICT=false){
	$pattern=trim($pattern);
	if(is_numeric($pattern)){return $pattern;}
	if(!$STRICT){$pattern="*$pattern*";}
	$pattern=str_replace("**", "*", $pattern);
	$pattern=str_replace("**", "*", $pattern);
	$pattern=str_replace("*", "%", $pattern);
	return $pattern;
}

function string_to_flexregex($searchindix=null){
	
	if(isset($_GET["SearchQuery"])){
		$_POST["query"]=$_GET["SearchQuery"];
		$_POST["qtype"]=$_REQUEST["qtype"];
	}
	
	if(isset($_GET["$searchindix"])){
		$_POST["query"]=$_GET[$searchindix];
		$_POST["qtype"]=$_REQUEST["qtype"];
	
	}	
	
	if(!isset($_POST["query"])){return;}
	if($_POST["query"]==null){return;}
	return string_to_regex($_POST["query"]);
}

function string_to_flexPostGresquery($searchindix=null){
	if(isset($_GET["SearchQuery"])){
		$_POST["query"]=$_GET["SearchQuery"];
		$_POST["qtype"]=$_REQUEST["qtype"];
	}

	if(isset($_GET["$searchindix"])){
		$_POST["query"]=$_GET[$searchindix];
		$_POST["qtype"]=$_REQUEST["qtype"];

	}

	if(!isset($_POST["qtype"])){return "true";}
	if(!isset($_POST["query"])){return "true";}
	if(trim($_POST["query"])==null){return "true";}
	$STRICT=false;
	
	$search=$_POST["query"];
	if(preg_match("#^regex\s+(.+)#", $_POST["query"],$re)){$search=$re[1];$REGEXP=true;}
	if(preg_match("#^regex:(.+)#", $_POST["query"],$re)){$search=$re[1];$REGEXP=true;}
	if(preg_match("#^strict:(.+)#", $_POST["query"],$re)){$search=$re[1];$REGEXP=true;}

	if(!$REGEXP){
		$search=string_to_sql_search($_POST["query"],$STRICT);
	}else{
		$search="*$search";
		$search=str_replace("**", "*", $search);
		$search=str_replace("*", "*?", $search);
	}
	if($search==null){return null;}

	if(strpos($_POST["qtype"], ',')>0){
		$Fields=explode(",",$_POST["qtype"]);

	}else{
		$Fields[]=$_POST["qtype"];
	}
	$COM=" ILIKE ";
	if($REGEXP){$COM="~*";}
	
	
	


	while (list ($index, $F) = each ($Fields) ){
		if(trim($F)==null){continue;}
		$tta[]="( CAST( $F as text) $COM '$search')";
	}
	
	if(count($tta)==1){return " {$tta[0]}";}
	return " (" .@implode($tta, " OR ").")";

}

function string_to_flexquery($searchindix=null,$POSTGREY=false){
	if(isset($_GET["SearchQuery"])){
		$_POST["query"]=$_GET["SearchQuery"];
		$_POST["qtype"]=$_REQUEST["qtype"];
	}
	
	if(isset($_GET["$searchindix"])){
		$_POST["query"]=$_GET[$searchindix];
		$_POST["qtype"]=$_REQUEST["qtype"];		
		
	}
	
	if(!isset($_POST["qtype"])){return;}
	if(!isset($_POST["query"])){return;}
	if(trim($_POST["query"])==null){return;}
	$STRICT=false;
	$REGEXP=false;
	if(preg_match("#^regex\s+(.+)#", $_POST["query"],$re)){$search=$re[1];$REGEXP=true;}
	if(preg_match("#^regex:(.+)#", $_POST["query"],$re)){$search=$re[1];$REGEXP=true;}
	if(preg_match("#^strict:(.+)#", $_POST["query"],$re)){$search=$re[1];$STRICT=true;}
	
	if(!$REGEXP){
		$search=string_to_sql_search($_POST["query"],$STRICT);
	}
	if($search==null){return null;}
	
	if(strpos($_POST["qtype"], ',')>0){
		$Fields=explode(",",$_POST["qtype"]);
		
	}else{
		$Fields[]=$_POST["qtype"];
	}
	$COM=" = ";
	if(strpos("  $search", "%")>0){$COM=" LIKE ";}
	if($REGEXP){$COM=" REGEXP ";}
	
	
	while (list ($index, $F) = each ($Fields) ){
		if(trim($F)==null){continue;}
		$tta[]="(`$F` $COM '$search')";
	}
	
	if(count($tta)==1){return " AND {$tta[0]}";}
	if($POSTGREY){
		return "WHERE (" .@implode($tta, " OR ").")";
	}
	return "AND (" .@implode($tta, " OR ").")";
	
}


function imgtootltip($img,$tooltip=null,$js=null,$align=null,$imgid=null){
	$lang=null;
	$DisableToolTips=0;
	if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
	$tpl=new templates();
	$tooltip_pos=null;
	if(preg_match("#^position:(.+?):(.+)#",$tooltip,$re)){
		$tooltip_pos="position:'{$re[1]}',";
		$tooltip=$re[2];
	}
	$curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";
	$tooltipencode=urlencode(base64_encode($tooltip));
	$float=null;
	$n = rand(10e16, 10e20);
	$rand=base_convert($n, 10, 36);
	if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
	$width=null;
	if(preg_match("#Mozilla.*?Chrome\/#",$_SERVER["HTTP_USER_AGENT"])){$Chrome=true;}else{$Chrome=false;}
	
	$filecache=null;
	if($tooltip==null){$tooltip="&nbsp;";}
	$md5=md5("$img$tooltipencode$js$align$imgid$rand".microtime());

	
	
	//$tooltip=ParseTooltip($tooltip);
	if($imgid<>null){$id=$imgid;}else{$id=$md5;}
	if($align<>null){$align=' align=\''.$align.'\' ';}

	$image_path="img/$img";
	if(preg_match('#images\.listener\.php#',$img)){$image_path=$img;}
	if(preg_match('#^ressources#',$img)){$image_path=$img;}	
	if(preg_match('#img\/(.+)#',$img,$re)){$image_path=$img;}
	if(is_file($img)){$image_path=$img;}
	if(preg_match("#SwitchPassword#",$js)){$float="float:right;"; }
	if($js<>null){$js="OnClick=\"javascript:$js;\"";}
	if(preg_match("#-([0-9]+)\.(png|gif|jpeg)$#", basename($image_path),$re)){
		$width=";max-width:{$re[1]}px;width:{$re[1]}px";
	}
	if(preg_match("#-([0-9]+)-grey\.(png|gif|jpeg)$#",basename($image_path),$re)){
			$width=";max-width:{$re[1]}px;width:{$re[1]}px";
	}	
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/help")){@mkdir("/usr/share/artica-postfix/ressources/logs/web/help",0755,true);}
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/help/$md5")){
		$tpl=new templates();
		$content=$tpl->_ENGINE_parse_body($tooltip);
		
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/help/$md5",$content );
	}else{
		$content=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/help/$md5");
	}	

	
	$javascript_final="	
<script>
	$('#$id').tooltipster({
			$tooltip_pos
			contentAsHTML: true,
			onlyOne:true,
			functionReady: function(){
				$('#$id').attr('aria-hidden', false);
			},
			functionAfter: function(){
				$('#$id').attr('aria-hidden', true);
			}
		});
</script>
";
	
	//if($Chrome){$javascript_final=null;}
	
	
	
	return "
	<!-- $image_path L.".__LINE__." $width -->
	<img src='/$image_path' $align id='$id' $js $curs style='$float$width' title='$content'>
	$javascript_final";
	
}

function PostFixTimeToPhp($PostFixDate){
	$months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
	if(preg_match('#(\w+),\s+([0-9]+)\s+(\w+)\s+([0-9]+)\s+([0-9]+):([0-9]+):([0-9]+)#',$PostFixDate,$ar)){
			if(strlen($ar[2])==1){$ar[2]="0{$ar[2]}";}
			$date=$ar[2] . "/" . $months[$ar[3]] . "/" . $ar[4];
			return $date. ' ' . $ar[5].":".$ar[6];
			
		}
	if(preg_match('#(\w+)\s+(\w+)\s+([0-9]+)\s+([0-9\:]+)\s+([0-9]+)#',$PostFixDate,$ar)){
		if(strlen($ar[3])==1){$ar[3]="0{$ar[3]}";}
		$date=$ar[3] . '/' . $months[$ar[2]] . '/' . $ar[5] . ' ' . $ar[4];
		return $date;
	}
	//
		
	return $PostFixDate;
	
}

function PasswordGenerator(){
	
$totalChar = 7; // number of chars in the password
$salt = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789";  // salt to select chars from
srand((double)microtime()*1000000); // start the random generator
$password=""; // set the inital variable
for ($i=0;$i<$totalChar;$i++)  // loop and create password
            $password = $password . substr ($salt, rand() % strlen($salt), 1);
// *************************
// Display Password
// *************************
return $password;	
	
}

function tooltipjs($tooltip,$noalpha=0){
	$tooltip=ParseTooltip($tooltip);
	$lightup1="lightup(this, 100);";
	
	$lightup1="\$(this).fadeOut('slow');";
	
	//$lightup2="lightup(this, 50);";
	
	$lightup1="\$(this).fadeOut('slow');$(this).fadeIn('fast');AffBulle('$tooltip');";
	//$lightup1="\$(this).slideUp('slow');$(this).fadeIn('fast');\"";	
	
	if($noalpha==1){$alpha=null;$lightup1=null;$lightup2=null;}
	
	
	return " onMouseOver=\"javascript:\$(this).cluetip();$lightup1\" OnMouseOut=\"javascript:HideBulle();$lightup2\"";
	
}
function CellRollOverCMP($jsOnClick=null,$tooltips=null){
	if($jsOnClick<>null){
		$jsOnClick=str_replace("javascript:",'',$jsOnClick);
		$java="OnClick=\"javascript:$jsOnClick\"";
		$cursor="this.style.cursor='pointer'";
		}
	if($tooltips<>null){
		$tooltips=str_replace("'","`",$tooltips);
		$lightup1="lightup(this, 100);";
		$lightup2="lightup(this, 50);";
		$mtips_1="AffBulle('$tooltips');";
		$mtips_2="HideBulle();";
		}
	
	return "OnMouseOver=\"this.style.background='#D1D1D1';$cursor;$mtips_1\" OnMouseOut=\"this.style.background='#FFFFFF';this.style.cursor='default';$mtips_2\" $java ";
	}


function CellRollOver($jsOnClick=null,$tooltips=null){
	if($jsOnClick<>null){
		$jsOnClick=str_replace("javascript:",'',$jsOnClick);
		$java="OnClick=\"javascript:$jsOnClick\"";
		$cursor="this.style.cursor='pointer'";
		}
	if($tooltips<>null){
		$tooltips=str_replace("'","`",$tooltips);
		$lightup1="lightup(this, 100);";
		$lightup2="lightup(this, 50);";
		$mtips_1="AffBulle('$tooltips');";
		$mtips_2="HideBulle();";
		}
	if(!isset($mtips_1)){$mtips_1=null;}
	if(!isset($mtips_2)){$mtips_2=null;}
	if(!isset($cursor)){$cursor=null;}
	if(!isset($java)){$java=null;}
	return "OnMouseOver=\"this.style.background='#D1D1D1';$cursor;$mtips_1\" OnMouseOut=\"this.style.background='transparent';this.style.cursor='default';$mtips_2\" $java ";
	}
function CellRollOver_jaune($jsOnClick=null,$tooltips=null){
	if($jsOnClick<>null){
		$java="OnClick=\"javascript:$jsOnClick\"";
		$cursor="this.style.cursor='pointer'";
		}
	if($tooltips<>null){
		$lightup1="lightup(this, 100);";
		$lightup2="lightup(this, 50);";
		$mtips_1="AffBulle('$tooltips');";
		$mtips_2="HideBulle();";
	}
	
	return "OnMouseOver=\"this.style.background='#feffd6';$cursor;$mtips_1\" OnMouseOut=\"this.style.background='transparent';this.style.cursor='default';$mtips_2\" $java ";
	
}

function CellRollOver_rouge($jsOnClick=null,$tooltips=null){
	if($jsOnClick<>null){
		$java="OnClick=\"javascript:$jsOnClick\"";
		$cursor="this.style.cursor='pointer'";
		}
	if($tooltips<>null){
		$lightup1="lightup(this, 100);";
		$lightup2="lightup(this, 50);";
		$mtips_1="AffBulle('$tooltips');";
		$mtips_2="HideBulle();";
	}
	
	return "style='background-color:#d32d2d;color:white' OnMouseOver=\"this.style.background='black';this.style.color='white';$cursor;$mtips_1\" OnMouseOut=\"this.style.background='red';this.style.color='white';this.style.cursor='default';$mtips_2\" $java ";
	
}

function CellRollOver_black($jsOnClick=null,$tooltips=null,$orignal_color){
	if($jsOnClick<>null){
		$java="OnClick=\"javascript:$jsOnClick\"";
		
		}
	if($tooltips<>null){
		$lightup1="lightup(this, 100);";
		$lightup2="lightup(this, 50);";
		$mtips_1="AffBulle('$tooltips');";
		$mtips_2="HideBulle();";
		$cursor="this.style.cursor='pointer'";
	}
	
	return "OnMouseOver=\"this.style.background='#000000';this.style.color='white';$cursor;$mtips_1\" 
	OnMouseOut=\"this.style.background='$orignal_color';this.style.color='black';this.style.cursor='default';$mtips_2\" 
	$java ";
	
}


function icon_help($help,$nofloat=0){
	$f="float:right";
	$page=CurrentPageName();
	$text="{" . $help . "}<br>{click_for_help}";
	if($nofloat==1){$f=null;}
	$id=md5($help);
	$img="
	<input type='hidden' id='Text_$id' value='$text<br><hr><div style=text-align:right><a href=\"javascript:Blurz();\" OnClick=javascript($(\"$id\").qtip(\"toggle\", false); style=\"font-size:11px\">{close}</a></div>'>
	<div style='$f;width:1%;margin-top:-5px;'>
		<img src='img/help.png' OnClick=\"javascript:LoadHelp('$help','$page');\" id='$id'>
	</div>
	<script>
	function qtyp_{$id}(){
			$(document).ready(function(){		
				$('#$id').qtip({
		  			content: $('#Text_$id').val(),
		  			show: 'mouseover',
		   			hide: 'mouseout',
		   			style: 'cream',
		   			hide: {event: 'click mouseleave',inactive: 3000},
		   			tip: true
					});	
		});
	}
	qtyp_{$id}();
	</script>\n";
	return "$img&nbsp;";
	
}

function texttooltip($text,$tooltip=null,$js=null,$dbclk=null,$NOHREF=0,$style=null,$forcejs=0){
	$lang=null;
	$DisableToolTips=0;
	$curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";
	
	if(preg_match("#SwitchPassword#",$js)){$float="float:right;"; }
	$tooltip=str_replace("'", "\"", $tooltip);
	
	
	if($tooltip==null){
		if($js<>null){$js="OnClick=\"javascript:$js;\"";}
		return "<span style='background-image: linear-gradient(to right, black 33%, rgba(255,255,255,0) 0%);
		background-position: bottom;
		background-size: 3px 1px;
		background-repeat: repeat-x;$style' $curs $js >$text</span>";
		
	}
	
	
	if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
	$tpl=new templates();
	$tooltip_pos=null;
	if(preg_match("#^position:(.+?):(.+)#",$tooltip,$re)){
		$tooltip_pos="position:'{$re[1]}',";
		$tooltip=$re[2];
	}
	
	$tooltipencode=base64_encode($tooltip);
	$float=null;
	$n = rand(10e16, 10e20);
	$rand=base_convert($n, 10, 36);
	if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
	
	
	
	$filecache=null;
	if($tooltip==null){$tooltip="&nbsp;";}
	$md5=md5("$text$tooltip$js$n".time());
	if($js<>null){$js="OnClick=\"javascript:$js;\"";}


	if(isset($_SESSION["detected_lang"])){
		$lang=$_SESSION["detected_lang"];
		
	}
	if($lang==null){
		if(isset($_COOKIE["artica-language"])){
			$lang= $_COOKIE["artica-language"];
		}
	}
	
	$time=microtime();
	$md5=md5($tooltipencode.$lang.$time);
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/help/$md5")){
		$tpl=new templates();
		$content=$tpl->_ENGINE_parse_body($tooltip);
		
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/help/$md5",$content );
	}else{
		$content=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/help/$md5");
	}
	
	$content=str_replace("\n", "", $content);
	$content=htmlspecialchars($content);
	
	return "<span 
		title='$content'
		style='background-image: linear-gradient(to right, black 33%, rgba(255,255,255,0) 0%);
		background-position: bottom;
		background-size: 3px 1px;
		background-repeat: repeat-x;$style' $js id='$md5' $curs>$text</span>
		<script>
		$('#$md5').tooltipster({
			$tooltip_pos
			contentAsHTML: true,
			onlyOne:true,
			functionReady: function(){
				$('#$md5').attr('aria-hidden', false);
			},
			functionAfter: function(){
				$('#$md5').attr('aria-hidden', true);
			}
		});
		</script>";
	

	
}
function LinkToolTipjs($tooltip=null,$js=null,$style=null){
	
	if($js==null){$js='void(0)';}
	$pointer_on="this.style.cursor='pointer'"; 
	$pointer_off="this.style.cursor='default'";
	
if($style<>null){$style=" style='$style'";}
	if($tooltip<>null){
		$tooltip=ParseTooltip($tooltip);
		$tooltip="onMouseOver=\"javascript:$pointer_on;AffBulle('$tooltip');this.style.textDecoration='underline';this.style.backgroundColor='none'\"  OnMouseOut=\"javascript:$pointer_off;HideBulle();this.style.textDecoration='none'\"";
		}
	
	if($js<>null){
		if(strpos($js,'ttp://')>0){$jshref="<a href=\"$js;\" target=_new $style>";}else
		{$js="OnClick=\"$js;\"";}
	}
		
	return "<a $style $js $tooltip>";
	
}
function ParseTooltip($tooltip){
	if($GLOBALS["EXECUTED_AS_ROOT"]){return $tooltip;}
if($tooltip<>null){
		$tpl=new templates();
		$tooltip=$tpl->_ENGINE_parse_body($tooltip);
		$tooltip=str_replace("\n",'',$tooltip);	
		$tooltip=str_replace("\r",'',$tooltip);	
		$tooltip=str_replace("\r\n"," ",$tooltip);
		$tooltip=str_replace("'","`",$tooltip);
		$tooltip=trim($tooltip);
		
}$tooltip=ASCII_TO_HTML($tooltip);return $tooltip;
}

function Field_yesno_checkbox_img($name,$value,$tooltip=null){
	$value=strtolower($value);
	if($tooltip==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	if($value==null){$value="no";}
	if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	if($value=='yes'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
	$html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchOnOff('$name');\"><img src='$img' id='img_$name' $tooltip></a>";
	return $html;
	
}



function Field_onoff_checkbox_img($name,$value,$tooltip=null){
	$value=strtolower($value);
	if($tooltip==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	if($value==null){$value="off";}
	if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	if($value=='on'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
	$html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchOnOff_on('$name');\"><img src='$img' id='img_$name' $tooltip></a>";
	return $html;
	
}
function Field_numeric_checkbox_img($name,$value,$tooltip=null,$OnClick=null){
	
	if($tooltip==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	if($value==null){$value="0";}
	if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');this.style.cursor='pointer'\" OnMouseOut=\"javascript:HideBulle();this.style.cursor='default'\"";}
	
	if($OnClick<>null){$click="$OnClick('$name');";}
	
	if($value=='1'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
	$html="
	<div OnClick=\"javascript:SwitchNumeric('$name');{$click}\" $tooltip style='width:22px;height:22px'>
		<input type='hidden' name='$name' id='$name' value='$value'>
		<img src='$img' id='img_$name' >
	</div>
	";
	return $html;
	
}
function Field_numeric_checkbox_img_disabled($name,$value,$tooltip=null){
	if($tooltip==null){$tooltip='{disbaled}';}
	$tooltip=ParseTooltip($tooltip);
	$name=md5(date('Y-mdhis'));
	$value=$name;
	if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	$img='img/status_disabled.gif';
	$html="
	<input type='hidden' name='$name' id='$name' value='$value'>
	<img src='$img' id='img_$name' $tooltip>";
	return $html;
	
}
function Field_key_checkbox_img($name,$value,$tooltip=null){
	$value=strtolower($value);
	if($tooltip==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	if($value==null){$value="nokey";}
	if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	if($value=='justkey'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
	$html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchKeyOnOff('$name');\"><img src='$img' id='img_$name' $tooltip></a>";
	return $html;
	
}

function KeyPress_enter($javascript_function){return "onkeypress=\"javascript:if(checkEnter(event)){".$javascript_function.";}\"";}

function array_reverse_key($array){
	if(!isset($array)){return $array;}
	while (list ($num, $ligne) = each ($array) ){
		$ret[$ligne]=$num;
	}
	return $ret;
}

function helpCoolapse($datas){
	$key=md5($datas);
	
	$tooltip='{click_to_expand_or_collapse}';
	$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\"";
	$html="
	<input type='hidden' id='{$key}_source' value='$datas'>
	<div style='float:right;margin:5px;cursor:pointer;filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;
	border:1px solid #CCCCCC;padding:3px;margin:3px;background-color:#FFFFFF' OnClick=\"javascript:HelpExpand('$key');\" $tooltip>
		<img id='{$key}_img' src='img/expand.gif' >&nbsp;<strong id='{$key}_text'>expand</strong>
	</div>
	<div id='{$key}_fill' style='width:99%;text-align:justify;font-size:12px;padding:3px;margin:3px;'></div>";
	return $html;
	
	
	
}


function Field_TRUEFALSE_checkbox_img($name,$value,$tooltip=null){
	if($value==true){$value=='true';}
	if($value==false){$value=='false';}
	if($tooltip==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	$value=strtoupper($value);
	if($value==null){$value="FALSE";}
	if($value==TRUE){$img='img/status_ok.png';}
	if($value=='TRUE'){$img='img/status_ok.png';}
	if($value=='FALSE'){$img='img/status_critical.png';}
	if($value==false){$img='img/status_critical.png';}
	
	if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	
	
	$html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchTRUEFALSE('$name');\" ><img src=\"$img\" id='img_$name' $tooltip></a>";
	return $html;
	
}
function Field_TRUEFALSE_checkbox($name,$value,$js=null){
	$value=strtolower(trim($value));
	$def=$value;
	if($js<>null){$js=" OnClick=\"javascript:$js\"";}
if($value=="true"){$value="checked";}else{$value="";}	
$html="<!-- $value: $def -->
<input type=\"checkbox\" id='$name' name=\"$name\" value=\"TRUE\" $value style='padding:0px;margin:0px;border:0px' $js>";
return $html;}


function array_move_element_flat_moveUp($input,$index) {
      $new_array = $input;
     
       if((count($new_array)>$index) && ($index>0)){
                 array_splice($new_array, $index-1, 0, $input[$index]);
                 array_splice($new_array, $index+1, 1);
             }

       return $new_array;
}

function array_move_element_flat_moveDown($input,$index) {
       $new_array = $input;
        
       if(count($new_array)>$index) {
                 array_splice($new_array, $index+2, 0, $input[$index]);
                 array_splice($new_array, $index, 1);
             }
  
       return $new_array;
 }


function array_move_element_flat($array,$index,$direction='up'){
	if($direction=="up"){
		return 	array_move_element_flat_moveUp($array,$index);
	}else{
		return 	array_move_element_flat_moveDown($array,$index);
	}
}


function array_move_element($array, $value, $direction = 'up') {
    $temp = array();
    while (list ($num, $ligne) = each ($array) ){
    	if($ligne==$value){
    		$index=$num;
    		break;
    	}
    }
    reset($array);
    if($direction == 'down'){
    	$index2=$index-1;
    }
    
    if($direction == 'up'){
    	$index2=$index+1;
    }   
    if($index2>count($array)-1){return $array;}
	if($index2<0){return $array;}
    $arrc=new tweak_array();
   	return $arrc->move($array,$index,$index2);
    
    
    if(end($array) == $value && $direction == 'down') {
        return $array;
    }
    if(reset($array) == $value && $direction == 'up') {
        return $array;
    }
	reset($array);
    while ($array_value = current($array)) {
       
        $this_key = key($array);

        if ($array_value == $value) {
            if($direction == 'down') {
                $next_value = next($array);
                $temp[key($array)] = $next_value;
                $temp[$this_key] = $array_value;
            } else {
                $prev_value = prev($array);
                $prev_key = key($array);
                unset($temp[$prev_key]);
                $temp[$this_key] = $array_value;
                $temp[$prev_key] = $prev_value;
                next($array);
                next($array);
            }
            continue;
        } else {
            $temp[$this_key] = $array_value;
        }

        next($array);
    }
    return $temp;
   
}

function element_rollover($js){
	$js=str_replace("javascript:",'',$js);
	$html="
	OnMouseOver=\"this.style.cursor='pointer';this.style.backgroundImage='url(img/bg_jaune-deg.png)';this.style.backgroundRepeat='repeat-y'\"
	OnMouseOut=\"this.style.cursor='auto';this.style.backgroundImage='';\"
	OnClick=\"javascript:$js\"";
	
	return $html;
	
}


function array_move_position($array = '', $from = '', $to = ''){  
		if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/class.arrayManip.inc\n";}
		include_once(dirname(__FILE__) . '/class.arrayManip.inc');
		$array_t=new ArrayManip(1);
		writelogs("move an array of {" . count($array) . "} elements from $from to $to",__FUNCTION__,__FILE__);
		return $array_t->moveElement($array,$from,$to);
	
}

function array_insert_artica($array = array(), $position = '' , $elements= ''){
writelogs("insert in position=$position elements ($elements)",__FUNCTION__,__FILE__);
	
	if ($position == '' || (!is_array($array)) || $elements == ''){
		writelogs("error insert in position=$position elements ($elements) in " .count($array) . " array rows",__FUNCTION__,__FILE__);
	}
	else{
		$left = array_slice ($array, 0, $position-1);
		$right = array_slice ($array, $position-1);
		$insert = explode ('\,', $elements);
		$array = array_merge ($left, $insert, $right);
		unset($left, $right, $insert);
		unset ($position, $elements);
		
	}
	return $array; 
} 



FUNCTION DIV_SHADOW($text,$zoneToKill=null,$color='#FCFCFC',$height=0){
		$tpl=new templates();
		$text=$tpl->_parse_body($text);
		return $text;
		if($color==null){$color='#FCFCFC';}
		$zoneToKill=AJAX_CLOSE($zoneToKill);
		if($height>0){$h="height:" .$height . "px;";}
$html="	
<div id='SHADOW' style=\"position:relative; top:7px; left:7px;background:black;\">
	<div style=\"position:relative;
 		top:-7px; left:-7px;
  		background:$color;border:1px solid #005447;\">
		<div  id='locker' style='padding:0px;
			cursor:pointer;
			background-color:#005447;
			background-image:url(img/barrecroix.gif);
			background-repeat:no-repeat;height:19px;
			padding-right:3px;
			background-position:right;'>
			
		$zoneToKill
		<img src='http://images.kaspersky.fr/vide.gif' height=18 width=90 border=0 align='right'></a>
		</div>
		<div style='margin:4px;$h'>$text</div>
	</div>
</div>";
return $html;
}
FUNCTION AJAX_CLOSE($divname='zone'){
return "<a href='#' OnClick=\"javascript:HideDive('$divname');\">";
	
}


class template_users{
	var $head;
	var $body;
	var $web_page;
	var $language;
	var $left_menus;
	var $top_menus;
	var $title;
	var $js_add;	
	var $nogbPopup;
	function template_users($title=null,$html=null,$nosession=0,$Popup=0,$nogbPopup=0,$refresh=0,$cfg=array()){
		$GLOBALS["CURRENT_PAGE"]=CurrentPageName();
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::{$GLOBALS["CURRENT_PAGE"]}:template_users()=>".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		if(!isset($GLOBALS["template_users_no_cache"])){$GLOBALS["template_users_no_cache"]=false;}
		
		if($html<>null){
			if(!$GLOBALS["template_users_no_cache"]){
				$datas=GET_CACHED(__FILE__,__FUNCTION__,$title,true);
				if($datas<>null){
				$this->web_page=$datas;
				return $datas;
				}
			}
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::{$GLOBALS["CURRENT_PAGE"]} =>".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			$tpl=new templates(null,null,null,1,$cfg);
			$this->body=$html;
			$this->nogbPopup=$nogbPopup;
			$this->title=$tpl->_ENGINE_parse_body($title,$cfg["LANG_FILE"]);
			
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:LANG:[{$_SESSION["detected_lang"]}]:::Title  =>$this->title function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			
			$cfg["refresh"]=$refresh;
			$cfg["TITLE"]=$this->title;
			$this->language=$tpl->_detect_lang();
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG:::LANG:[{$_SESSION["detected_lang"]}]:: {$GLOBALS["CURRENT_PAGE"]}:language  =>$this->language function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			$this->head="<!-- Artica head template_users  -->\n".$tpl->headers($cfg);
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG:::LANG:[{$_SESSION["detected_lang"]}]:: {$GLOBALS["CURRENT_PAGE"]}:head  =>" .strlen($this->head). " bytes function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			$this->body=$tpl->_ENGINE_parse_body($this->body,$cfg["LANG_FILE"]);		
			
			if($Popup==1){
				if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG:::LANG:[{$_SESSION["detected_lang"]}]::{$GLOBALS["CURRENT_PAGE"]}:Body  =>" .strlen($this->body). " bytes (popup=$Popup) function  ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
				$this->_BuildPopUp($html,$title);}else{
					if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG:::LANG:[{$_SESSION["detected_lang"]}]::{$GLOBALS["CURRENT_PAGE"]}:->_BuildBody() function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
					$this->_BuildBody();
			}
			
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:LANG:[{$_SESSION["detected_lang"]}]:: finish =>".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
			$this->web_page=$tpl->_ENGINE_parse_body($this->body);
			SET_CACHED(__FILE__,__FUNCTION__,$title,$this->web_page);
		}
	}
	
	function _BuildPopUpNew($html,$title){
		$tpl=new templates();
		$html=$tpl->_ENGINE_parse_body($html);
		$title=$tpl->_ENGINE_parse_body($title);
		include_once(dirname(__FILE__)."/class.page.builder.inc");
		$pges=new pagebuilder();
		$pges->language=$this->language;	
		$js=$pges->jsArtica();
		$css=$pges->headcss();
		$yahoo=$pges->YahooBody();
		$this->body="
		<body>
		$yahoo
		$html
		</body>";
		$this->web_page="
			<html>
			<head>
				<title>$title</title>
				$js
				$css
				{$GLOBALS["ADD_HTML_HEADER"]}
				$this->head
				</head>
				$this->body 
			</html>";
	}	
	
	function _BuildPopUp($html,$title){
		$tpl=new templates();
		$html=$tpl->_ENGINE_parse_body($html);
		$title=$tpl->_ENGINE_parse_body($title);
		$bg="background-image:url(img/bg-coins-blc-450.jpg);background-repeat:no-repeat;clear:both;width:453px;height:465px";
		if($this->nogbPopup==1){$bg=null;}
		$this->body="
		<body style='background-image:none;background-color:white;margin-left:10px'>
			<style type=\"text/css\">
	  			html {  background-color: #FFFFFF }
	  		</style>
			<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
			
			<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
			<div style='$bg;padding-right:15px;padding-left:12px;'>
				$html
			</div>
			
			</body>";
			$this->web_page="
			<html>
			<head>
				<title>$title</title>
				{$GLOBALS["ADD_HTML_HEADER"]}
				$this->head
				</head>
				$this->body 
			</html>";
	}
	
	
	function _BuildBody(){
		if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/class.users.menus.inc\n";}
		include_once(dirname(__FILE__) . "/class.users.menus.inc");
		$menus=new usersMenus();
		$cuurentpage=CurrentPageName();
		$template_file="index.html";
		if(isset($GLOBALS["CHANGE_TEMPLATE"])){$template_file=$GLOBALS["CHANGE_TEMPLATE"];}
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}::LANG:[{$_SESSION["detected_lang"]}]::->new templates(); function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		$tpl=new templates();
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}::LANG:[{$_SESSION["detected_lang"]}]::menus->BuildUsersTabs(); function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		$buildtabs=$menus->BuildUsersTabs();
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}::LANG:[{$_SESSION["detected_lang"]}]::menus->BuildMenusBarr(menus->menus); function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		$top_menus=$tpl->BuildMenusBarr($menus->menus);
		$template_users_menus_h=$menus->template_users_menus_h;
		$template_user_addr=$menus->template_user_addr;
		
		
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:LANG:[{$_SESSION["detected_lang"]}]:: OK function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		if($cuurentpage=="logon.php"){writelogs("$cuurentpage ->"._FUNCTION__.'/'.__LINE__." file ".__FILE__);}
		if(!isset($_SESSION["uid"])){
			$menus->menus=null;
			$menus->menusright=null;
			$menus->toplinks=null;
			$buildtabs=null;
			
		}
			
		if(!isset($_SESSION["template"])){$_SESSION["template"]="default";}
			
		if($cuurentpage=="logon.php"){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::_SESSION[\"template\"]={$_SESSION["template"]} function ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}	
		$template_path=dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/$template_file";
		
			
		if(!is_file($template_path)){
			if($GLOBALS["VERBOSE"]){echo "Template: $template_path no such file	". __LINE__."\n";}
			$template_path=dirname(__FILE__) . "/templates/default/index.html";
			$GLOBALS["CHANGE_TEMPLATE"]="default";
		}
		
		$template=file_get_contents($template_path);

		if($cuurentpage=="logon.php"){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::$template_file =>". strlen($template)." bytes function ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}::LANG:[{$_SESSION["detected_lang"]}]::tpl->search_menus() ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		$template=str_replace("{TEMPLATE_SEARCH}",$tpl->search_menus(),$template);
		if($cuurentpage=="logon.php"){error_log("[{$_SESSION["uid"]}]::$cuurentpage:LANG:[{$_SESSION["detected_lang"]}]:: $template_file =>{TEMPLATE_SEARCH} done ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$template=str_replace("{TEMPLATE_HEAD}",$this->head,$template);
		if($cuurentpage=="404.php"){
			$template=str_replace("{TEMPLATE_LEFT_MENUS}","",$template);
			$template=str_replace("{TEMPLATE_HIGH_MENUS}","",$template);
			$template=str_replace("{TEMPLATE_TOP_LINKS}","",$template);
			$template=str_replace("{TEMPLATE_RIGHT_MENUS}","",$template);
			$template=str_replace("{TEMPLATE_USER_MENUS}","",$template);
			$template=str_replace("{TEMPLATE_USER_MENUS_H}","",$template);
			$template=str_replace("{TEMPLATE_TOP_MENUS}","",$template);
			$template=str_replace("{TEMPLATE_SEARCH}","",$template);
			
		}
		$template=str_replace("{TEMPLATE_LEFT_MENUS}","<div id='TEMPLATE_LEFT_MENUS'></div><script>LoadAjax('TEMPLATE_LEFT_MENUS','/admin.tabs.php?left-menus=yes');</script>",$template);
		if($cuurentpage=="logon.php"){error_log("[{$_SESSION["uid"]}]::$cuurentpage:: $template_file =>{TEMPLATE_LEFT_MENUS} done ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$template=str_replace("{TEMPLATE_TOP_MENUS}",$top_menus,$template);
		$template=str_replace('{TEMPLATE_RIGHT_MENUS}',$tpl->_ENGINE_parse_body($menus->menusright),$template);
		$template=str_replace("{TEMPLATE_CONTENT}",$tpl->_ENGINE_parse_body($this->body),$template);
		$template=str_replace("{TEMPLATE_TOP_LINKS}",$tpl->_ENGINE_parse_body($menus->toplinks),$template);
		$template=str_replace("{TEMPLATE_TITLE_HEAD}",strip_tags($this->title),$template);
		$template=str_replace("{TEMPLATE_TITLE}",$this->title,$template);
		if($cuurentpage=="logon.php"){error_log("[{$_SESSION["uid"]}]::$cuurentpage:: $template_file =>{TEMPLATE_TITLE} done ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$template=str_replace("{TEMPLATE_USER_MENUS}",$tpl->_ENGINE_parse_body($buildtabs),$template);
		$template=str_replace("{TEMPLATE_USER_MENUS_H}",$tpl->_ENGINE_parse_body($template_users_menus_h),$template);
		$template=str_replace("{ACCOUNT}",$_SESSION["uid"],$template);
		$template=str_replace("{TOOLBOX}",$_GET["TOOLBOX"],$template);
		$template=str_replace("{TEMPLATE_USER_ADDR}",$template_user_addr,$template);
		$template=str_replace("{TEMPLATE_HIGH_MENUS}",$tpl->Build_high_menus(),$template);
		$template=str_replace("{TEMPLATE_BODY_YAHOO}",$tpl->YahooBody(),$template);
		if(is_file(dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/img/logo.png")){
			$template=str_replace("/css/images/logo.gif","ressources/templates/{$_COOKIE["artica-template"]}/img/logo.png",$template);
			if($GLOBALS["VERBOSE"]){echo dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/img/logo.png -> replace\n";}
		}else{
			if($GLOBALS["VERBOSE"]){echo dirname(__FILE__) . "/templates/{$_COOKIE["artica-template"]}/img/logo.png no such file\n";}
			
		}
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:Checking Administrator privileges ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		if($menus->AsSystemAdministrator==true){
			writelogs("AsSystemAdministrator=\"$menus->AsSystemAdministrator\" = TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$right="" .imgtootltip('rght-cut.gif','{logoff}',"javascript:/logoff()")."";
		}else{
			$right="" .imgtootltip('rght-cut.gif','{logoff}',"MyHref('/logoff.php')")."";
		}
		if(!isset($_SESSION["uid"])){$right=null;}
		$template=str_replace("{RIGHT}",$right,$template);
		if($cuurentpage=="logon.php"){error_log("[{$_SESSION["uid"]}]::$cuurentpage: $template_file FINISH=>". strlen($template)." bytes function ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}: this->body=finish function ".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		$this->body=$template;
		}
		
		
	
	function _ParsePrivieleges($content=null){
		if($content==null){$content=$_SESSION["privileges"]["ArticaGroupPrivileges"];}
		
			if(preg_match_all('#\[([a-zA-Z]+)\]="([a-z0-9]+)"#is',$content,$reg)){
				while (list ($num, $ligne) = each ($reg[1]) ){
					$priv[$ligne]=$reg[2][$num];
					}}
			return $priv;
	}
	

	
	
}

function is_array_key($key,$array){
		$key=strtolower($key);
		$r=array_change_key_case($array,CASE_LOWER);
		if(array_key_exists($key,$r)){return true;}return false;
}

function  _FileExt($ext){
if(is_file("img/file_ico/$ext.gif")){ return "img/file_ico/$ext.gif";}
if(is_file("img/file_ico/$ext.png")){ return "img/file_ico/$ext.png";}
}

function get_img_ext($value){
	if(preg_match('#\.([a-zA-Z0-9]+)$#',$value,$reg)){
		$ext=$reg[1];
		$img_path=_FileExt($ext);
		if($img_path==null){$img_path="img/file_ico/unknown.gif";}	
		return "<img src=\"$img_path\">";
		
	}else{
	 return '<img src="img/file_ico/unknown.gif">';
	}
}
function Get_extension($filename){
	$parts = explode('.',$filename);
   	$last = count($parts) - 1;
   	$ext = $parts[$last];
   	return $ext;
}
		
function replace_accents($s) {
  $t=new htmltools_inc();
  return $t->replace_accents($s);
}

function DirListTable($dir,$extToFind){
$Files = array();
		$It=  @opendir($dir);
		if (! $It){return null;}
		while ($Filename = readdir($It)){
			if ($Filename == '.' || $Filename == '..')
			continue;
			$ext=Get_extension($Filename);
				if($ext<>null){
					if($ext==$extToFind){$Files[]=$Filename;}
				}

		}

		return $Files;
		}
		
function DirFolders($dir){
$Files = array();
		$It=  @opendir($dir);
		if (! $It){return null;}
		while ($Filename = readdir($It)){
			
			if ($Filename == '.' || $Filename == '..')
			continue;
			if(is_dir("$dir/$Filename")){
				$Files[$Filename]=$Filename;
			}

		}

		return $Files;
		}		
		
function CleanEscapes($html){
	$html=str_replace("\n",'',$html);
	$html=str_replace("\t",'',$html);
	$html=str_replace("\r",'',$html);
	return $html;
}

function RoundedWhite($text){
	return "
	<div style='width:100%;padding:-1px;border:#005447 1px solid;'>
	<div class='abl' style='width:100%;background-color:#FFFFFF'>
			<div class='abr' style='background-color:#FFFFFF'>
				<div class='atl' style='background-color:#FFFFFF'>
					<div class='atr' style='padding:3px;'>
						<div class='insides' style='width:99%'>$text</div>
					</div>
				</div>
			</div>
		</div>
		</div>
		<div class='clear'>&nbsp;</div>";
	
}

function RoundedGrey($text){
	return "<div class='bl'>
			<div class='br'>
				<div class='tl'>
					<div class='tr'>
						<div class='insides'>$text</div>
					</div>
				</div>
			</div>
		</div>
		<div class='clear'>&nbsp;</div>";
	
}

function RoundedLightGrey($text,$url=null,$asrolover=0){
	
	$id=md5($text);
	
	
	if($url<>null){
		$add="this.style.cursor='pointer';";
		$add1="this.style.cursor='auto'";
		if(preg_match('#javascript#i',$url)){
			$url="OnClick=\"$url\"";
		}else{$url="OnClick=\"javascript:MyHref('$url');\"";}
	}
	
	if($asrolover==1){
		$ls="OnMouseOver=\"javascript:rol1_('$id');$add\" OnMouseOut=\"javascript:rol0_('$id');$add1\"" . " $url";	
	}else{
		if($url<>null){
			$ls="OnMouseOver=\"javascript:$add\" OnMouseOut=\"javascript:$add1\"" . " $url";	
			
		}
	}
	

	
return "
<div $ls>
  <b class=\"RLightGrey\" id='$id'>
  <b class=\"RLightGrey1\" id ='$id" . "_1'><b></b></b>
  <b class=\"RLightGrey2\" id ='$id" . "_2'><b></b></b>
  <b class=\"RLightGrey3\" id ='$id" . "_3'></b>
  <b class=\"RLightGrey4\" id ='$id" . "_4'></b>
  <b class=\"RLightGrey5\" id ='$id" . "_5'></b></b>

  <div class=\"RLightGreyfg\" style='padding:8px;'  id ='$id" . "_11'>
   			$text
  </div>

  <b class=\"RLightGrey\" id ='$id" . "_0'>
  <b class=\"RLightGrey5\" id ='$id" . "_6'></b>
  <b class=\"RLightGrey4\" id ='$id" . "_7'></b>
  <b class=\"RLightGrey3\" id ='$id" . "_8'></b>
  <b class=\"RLightGrey2\" id ='$id" . "_9'><b></b></b>
  <b class=\"RLightGrey1\" id ='$id" . "_10'><b></b></b></b>
</div>
";	
	
}


function RoundedLightYellow($text){
	
return "<div>
  <b class=\"RLightyellow\">
  <b class=\"RLightyellow1\"><b></b></b>
  <b class=\"RLightyellow2\"><b></b></b>
  <b class=\"RLightyellow3\"></b>
  <b class=\"RLightyellow4\"></b>
  <b class=\"RLightyellow5\"></b></b>

  <div class=\"RLightyellowfg\" style='padding:7px;'>
   $text
  </div>

  <b class=\"RLightyellow\">
  <b class=\"RLightyellow5\"></b>
  <b class=\"RLightyellow4\"></b>
  <b class=\"RLightyellow3\"></b>
  <b class=\"RLightyellow2\"><b></b></b>
  <b class=\"RLightyellow1\"><b></b></b></b>
</div>
";	
}
function RoundedLightBlue($text){
	
return "<div>
  <b class=\"RLightBlue\">
  <b class=\"RLightBlue1\"><b></b></b>
  <b class=\"RLightBlue2\"><b></b></b>
  <b class=\"RLightBlue3\"></b>
  <b class=\"RLightBlue4\"></b>
  <b class=\"RLightBlue5\"></b></b>

  <div class=\"RLightBluefg\" style='padding:7px;'>
   $text
  </div>

  <b class=\"RLightBlue\">
  <b class=\"RLightBlue5\"></b>
  <b class=\"RLightBlue4\"></b>
  <b class=\"RLightBlue3\"></b>
  <b class=\"RLightBlue2\"><b></b></b>
  <b class=\"RLightBlue1\"><b></b></b></b>
</div>
";	
}
function RoundedLightWhite($text){
	
return "<div>
  <b class=\"RLightWhite\">
  <b class=\"RLightWhite1\"><b></b></b>
  <b class=\"RLightWhite2\"><b></b></b>
  <b class=\"RLightWhite3\"></b>
  <b class=\"RLightWhite4\"></b>
  <b class=\"RLightWhite5\"></b></b>

  <div class=\"RLightWhitefg\" style='padding:7px;'>
   $text
  </div>

  <b class=\"RLightWhite\">
  <b class=\"RLightWhite5\"></b>
  <b class=\"RLightWhite4\"></b>
  <b class=\"RLightWhite3\"></b>
  <b class=\"RLightWhite2\"><b></b></b>
  <b class=\"RLightWhite1\"><b></b></b></b>
</div>
";	
}
function RoundedBlack($text){
return "<div>
  <b class=\"RLightBlack\">
  <b class=\"RLightBlack1\"><b></b></b>
  <b class=\"RLightBlack2\"><b></b></b>
  <b class=\"RLightBlack3\"></b>
  <b class=\"RLightBlack4\"></b>
  <b class=\"RLightBlack5\"></b></b>

  <div class=\"RLightBlackfg\"style='padding:5px;'>
   $text
  </div>

  <b class=\"RLightBlack\">
  <b class=\"RLightBlack5\"></b>
  <b class=\"RLightBlack4\"></b>
  <b class=\"RLightBlack3\"></b>
  <b class=\"RLightBlack2\"><b></b></b>
  <b class=\"RLightBlack1\"><b></b></b></b>
</div>";
}

function my_mkdir($dir, $chmod = 0777){
		if (!empty($dir)) {
			$array = explode('/', $dir);
			$base_dir = '';

			foreach ($array as $key => $value) {
				if ($key){
					$base_dir .= '/' . $value;
					$base_dir=str_replace("\\","",$base_dir);
					//WriteLogs("Fichiers:my_mkdir:base_dir=$base_dir");
				}
				else{
					$base_dir .= $value;
				}

		
				if (!file_exists($base_dir) && !empty($value))
				if (!mkdir($base_dir, $chmod))
				echo 'Unable to create folder "' . $base_dir . '"<br />'; }

		}
	}
	

	
function Paragraphe_switch_disable($title,$text,$field_name=null,$value=null,$tips=null,$width="220"){
	$MINIADM=false;
	if($_SESSION["MINIADM"]==true){$MINIADM=true;}
	if(isset($_COOKIE["MINIADM"])){$MINIADM=true;}
	$id=md5('Paragraphe_switch_img'.$field_name.$text.$title);
	$hrf="<a href='javascript:blur();' class='nostylea'>";

	if(trim($tips)==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	if($value==null){$value="0";}
	if($tooltip<>null){$tooltip="
	onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this,100);\" 
	OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" 
	style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	
	
	$img='img/64-grey.png';
	$field="<input type='hidden' name='$field_name' id='$field_name' value='$value'>";
	
	
	
	
	
	$img="<div OnClick=\"javascript:blur();\" OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" >
			<img src='$img' id='img_$field_name'  $tooltip>
		</div>";
	$margin=0;
	$fontsize=12;
	$fontsize_title=14;
	if($width>400){$fontsize=14;$fontsize_title=16;}
	if($width>550){$fontsize=16;$fontsize_title=18;$margin="margin-bottom:15px;";}
	$class="BodyContent";
	if($MINIADM){$class="MiniAdmParagrapheSwitchIm";}
	$html="
	<div style='width:{$width}px;$margin' class=$class>
		<table style='width:100%;'>
		<tr>
			<td width=1% valign='top' style='line-height:normal;'><div style='min-height: 120px;'>$img</a></div></td>
			<td valign='top' id='$id' style='line-height:normal;;padding-left:5px'><h3 style='font-size:{$fontsize_title}px;color:black;text-transform:capitalize'>$title</h3>
			<div 
				style='text-align:left;
				font-size:{$fontsize}px; 
				id='text_$id'>$field$text</div>
		</td>
	</tr>
	</table>
	
	</div>";
	return $html;
	
	
}
	
function Paragraphe_switch_img_32($title,$text,$field_name=null,$value=null,$tips=null,$width="120"){
	$text_key=$text;
	$id=md5('Paragraphe_switch_img'.$field_name.$text.$title);
	$hrf="<a href='$link' class='nostylea'>";

	if(trim($tooltip)==null){$tooltip='{click_enable_disable}';}
	$tooltip=ParseTooltip($tooltip);
	if($value==null){$value="0";}
	if($tooltip<>null){$tooltip="
	onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this,100);\" 
	OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" 
	style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	
	if(strpos($width,"%")>0){$width="width:{$width};";}else{$width="width:{$width}px;";}
		
	
	
	if($value=='1'){$img='img/ok32.png';}else{$img='img/danger32.png';}
	$field="<input type='hidden' name='$field_name' id='$field_name' value='$value'>";
	
	
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body($text);
	if(strlen($text)>60){$text="<table style='width:100%'>
	<tr>
	<td valign='top' width=99% style='text-align:left;font-size:11px'>" . substr($text,0,60)."...</td>
	<td valign='top' width=1%>" . help_icon($text_key)."</td>
	</tr>
	</table>";
	}
	
	
	$img="<a href=\"javascript:Switch32Numeric('$field_name');\">
			<img src='$img' id='img_$field_name' 
			$tooltip
			>
		</a>";
	
	
	
	$html="<div style='{$width}' >
	<table style='width:100%'>
	<tr>
	<td width=1% valign='top'>$img</a></td>
	<td valign='top' id='$id'><h3>$title</h3>
	<div style='text-align:left;font-size:11px' id='text_$id'>$field$text</div>
	</td>
	</tr>
	</table>
	
	</div>";
	return "<div style='margin:4px'>".RoundedLightWhite($html)."</div>";
	
	
}
	
function Paragraphe_switch_img($title,$text,$field_name=null,$value=null,$tips=null,$width="220",$callback=null){
	$MINIADM=false;
	$GLOBALS["AUTO_FORM"][$field_name]="text";
	if($_SESSION["MINIADM"]==true){$MINIADM=true;}
	if(isset($_COOKIE["MINIADM"])){$MINIADM=true;}
	$id=md5('Paragraphe_switch_img'.$field_name.$text.$title);
	if($callback==null){$callback="blur()";}
	$hrf="<a href='javascript:blur();' class='nostylea'>";

	if(trim($tips)==null){$tips='{click_enable_disable}';}
	$tooltip=ParseTooltip($tips);
	if($value==null){$value="0";}
	if($tooltip<>null){$tooltip="
	onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this,100);\" 
	OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" 
	style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
	
	
	if($value=='1'){$img='img/64-green.png';}else{$img='img/64-red.png';}
	$field="<input type='hidden' name='$field_name' id='$field_name' value='$value'>";
	
	
	
	
	
	$img="<div OnClick=\"javascript:SwitchBigNumeric('$field_name','$callback');\" 
			OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" >
			<img src='$img' id='img_$field_name'  $tooltip>
		</div>";
	$margin=0;
	$fontsize=12;
	$fontsize_title=14;
	if($width>400){$fontsize=14;$fontsize_title=16;}
	if($width>550){$fontsize=16;$fontsize_title=18;$margin="margin-bottom:15px;";}
	if($width>849){$fontsize=18;$fontsize_title=22;$margin="margin-bottom:15px;";}
	if($width>1000){$fontsize=22;$fontsize_title=24;$margin="margin-bottom:20px;";}
	if($width>1399){$fontsize=26;$fontsize_title=28;$margin="margin-bottom:30px;";}
	$class="BodyContent";
	if($MINIADM){$class="MiniAdmParagrapheSwitchIm";}
	
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body("$text");
	$text=stripslashes($text);
	
	$html="
	<div style='width:{$width}px;$margin' class='$class'>
		<table style='width:100%;'>
		<tr>
			<td width=1% valign='top' style='line-height:normal;'><div style='min-height: 120px;'>$img</a></div></td>
			<td valign='top' id='$id' style='line-height:normal;padding-left:5px'><h3 style='font-size:{$fontsize_title}px;color:black;text-transform:capitalize'>$title</h3>
			<div 
				style='text-align:left;
				font-size:{$fontsize}px;' 
				id='text_$id'>$field$text</div>
		</td>
	</tr>
	</table>
	
	</div>";
	return $html;
	
	
}
function ParagrapheSimple($img,$title,$text,$link=null,$tips="go_to_section",$width="210",$height=null){
	
	$nojs=false;
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body($title);
	$text=$tpl->_ENGINE_parse_body($text);	
	$tips=str_replace("'","",$tips);
	$tips=str_replace("	","",$tips);
	
	$logs[]="<!-- Height $height  -->";
	
	$height=intval($height);
	if($height==0){$height=70;}
	

	
	$id=md5($img.$text.$title.$link);

	
	if($tips<>null){
		$tips=str_replace('{','',$tips);
		$tips=str_replace('}','',$tips);
		}

		
	if (strpos($link,'script:')){
		$jslink="$link";
		$jslink=str_replace("javascript:",'',$jslink);
	}else{$jslink="MyHref('$link');";}
	
	$jslink=str_replace('"',"'",$jslink);
	$tooltip=imgtootltip($img,"{$tips}",null,null,"img_$id");
	

	
		$lightup1="lightup(document.getElementById('img_$id'), 100);";
		$lightup2="lightup(document.getElementById('img_$id'), 50);";	
	
	if(($tips<>null)&& ($link<>null)){
		$js_tips=ParseTooltip($tips);
		$tooltip_over="AffBulle('{{$js_tips}}');";
		$tooltip_out="HideBulle()";}
	
	$h3style="
		OnMouseOver=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';$lightup1;$tooltip_over\"
		OnMouseOut=\"javascript:this.className='paragraphe';this.style.cursor='default';$lightup2;$tooltip_out\"
		OnClick=\"javascript:$jslink\"";
	
		if($link==null){$hrf=null;$h3style=null;$tooltip="<img src='img/$img'>";}


	
	
	if($height<70){
		$min_height=null;
		$min_height_title=null;
	}
	
		
	
	$title="<h3>$title</h3>";
	$text="<div id='text_$id' style='$min_height;font-size:11px'>$text</div>";
		
	
	
	if($nojs){
		$bigA="<a href=\"$link\" target=_new>";
		$bigZ="</a>";
	}
	
	$html=
	"\n<!-- ------------------------------------------------------------- -->\n".
	@implode("\n", $logs)."
	<div style='width:{$width}px;min-height:{$height}px !important' class='paragraphe' id='$id' $h3style>
		<table style='width:100%'>
			<tr>
				<td width=1% valign='top'>$bigA$hrf$tooltip</a></td>
				<td valign='top' >$title$text</td>
			</tr>
		</table>
	</div>
	<!-- ------------------------------------------------------------- -->\n";
	return $html;
	
	
}	

function Buildicon64($ICON_KEY,$width=210,$height=null,$param=null){
	if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/class.icons.inc\n";}
	include_once(dirname(__FILE__)."/class.icons.inc");
	$tpl=new templates();
	$icon=new deficons();
	if(!is_array($icon->array_icons[$ICON_KEY])){return null;}
	
	if($param<>null){
		$icon->array_icons[$ICON_KEY]["JS"]=str_replace("')","$param')",$icon->array_icons[$ICON_KEY]["JS"]);
	}
	
	$p=Paragraphe($icon->array_icons[$ICON_KEY]["IMG"],"{{$icon->array_icons[$ICON_KEY]["TITLE"]}}",
	"{{$icon->array_icons[$ICON_KEY]["TEXT"]}}",
	"javascript:".$icon->array_icons[$ICON_KEY]["JS"],"{{$icon->array_icons[$ICON_KEY]["TEXT"]}}",$width,$height);
	if(posix_getuid()==0){return $p;}
	return $tpl->_ENGINE_parse_body($p,$icon->array_icons[$ICON_KEY]["LANG"]);
	
		
}	


function Paragraphe32($title,$text,$js,$img,$size=220){
	$MINIADM=false;
	if($_SESSION["MINIADM"]==true){$MINIADM=true;}
	if($_COOKIE["MINIADM"]=="YES"){$MINIADM=true;}
	
	$fontsizeDef=12;
	
	if(preg_match("#^noacco:(.+)#is",trim($text),$re)){
			$text=$re[1];
	}else{
		if(strpos($text, "}")==0){ $text="{{$text}}"; }
	}
	
	if(preg_match("#noacco:(.+)#",$title,$re)){
		$title=$re[1];
		
	}else{
		if(strpos($title, "}")==0){
		$title="{{$title}}";
		}
	}
	
	
	$title_size=14;
	$text_size=12;
	
	if($size>499){
		$title_size=24;
		$text_size=22;
	}
	
		$id=md5($js);
		$img_id="{$id}_img";
		
		
		if($MINIADM){
			$html="<div style='min-height:110px;width:100%;text-align:left'>
			<table style='width:100%;padding:0px;margin:0'
					>
			<tr>
			<td width=32px valign='top' style='vertical-align:top';>
			<input type='hidden' name='{$img_id}_org' id='{$img_id}_org' value='img/$img'>
			" . imgtootltip($img,"{$text}","$js",null,$img_id)."</td>
			<td style='text-align:left'>
				<strong style='font-size:{$title_size}px;line-height:normal'>$title</strong>
				<div style='font-size:{$text_size}px;line-height:normal'>$text</div><!-- replace -->
				
			</td>
			</tr>
			</table>
			</div>
			";
			return "<div style='margin:5px;width:{$size}px' OnMouseOver=\"this.style.cursor='pointer';\" 
			OnMouseOut=\"this.style.cursor='auto'\" 
				OnClick=\"javascript:SeTimeOutIMG32('$img_id');$js\">".RoundedLightGrey($html,null,1)."</div>";
		
		
		
		
		
		}	
		
		
		
		
		
	return "
	<div style='width:93%;min-height:110px' class=form 		
		OnMouseOver=\"javascript:this.className='formOver';this.style.cursor='pointer';\" 
		OnMouseOut=\"javascript:this.className='form';this.style.cursor='auto';\" 
		OnClick=\"javascript:SeTimeOutIMG32('$img_id');$js\">
	<table style='width:{$size}px;'>
	<tr>
	<td width=1% valign='top' style='vertical-align:top';>
	<input type='hidden' name='{$img_id}_org' id='{$img_id}_org' value='img/$img'>
	" . imgtootltip($img,"{$text}","$js",null,$img_id)."</td>
	<td>
	<strong style='font-size:{$title_size}px;line-height:normal'>$title</strong>
	<div style='min-height:75px'>
		<div style='font-size:{$text_size}px;line-height:normal'>$text</div><!-- replace -->
	</div>
		</td>
	</tr>
	</table></div>";
	

return "<div style=\"width:{$size}px;margin:2px\" 
	OnMouseOver=\"javascript:ParagrapheWhiteToYellow('$id',0);this.style.cursor='pointer';\" 
	OnMouseOut=\"javascript:ParagrapheWhiteToYellow('$id',1);this.style.cursor='auto'\" 
	OnClick=\"javascript:$js\">
  <b id='{$id}_1' class=\"RLightWhite\">
  <b id='{$id}_2' class=\"RLightWhite1\"><b></b></b>
  <b id='{$id}_3' class=\"RLightWhite2\"><b></b></b>
  <b id='{$id}_4' class=\"RLightWhite3\"></b>
  <b id='{$id}_5' class=\"RLightWhite4\"></b>
  <b id='{$id}_6' class=\"RLightWhite5\"></b></b>

  <div id='{$id}_0' class=\"RLightWhitefg\" style='padding:2px;'>
   $html
  </div>

  <b id='{$id}_7' class=\"RLightWhite\">
  <b id='{$id}_8' class=\"RLightWhite5\"></b>
  <b id='{$id}_9' class=\"RLightWhite4\"></b>
  <b id='{$id}_10' class=\"RLightWhite3\"></b>
  <b id='{$id}_11' class=\"RLightWhite2\"><b></b></b>
  <b id='{$id}_12' class=\"RLightWhite1\"><b></b></b></b>
</div>
";		
		
	
}
function hide_ressources($pattern){
	if(preg_match("#smb://(.+?):(.+?)@#",$pattern,$re)){
		return str_replace($re[2],'*****',$pattern);
	}
	return $pattern;
}


function ParagrapheTEXT($img,$title,$text,$link=null,$minheight=0,$nomax=false){
	$MDKEY=md5("$img$title$text$link$minheight");
	$datasCache=GET_CACHED(__FILE__, __FUNCTION__, $MDKEY,true);
	if($datasCache<>null){return $datasCache;}
	
	$max_text_caracts=85;
	$nomysql=false;
	if(is_numeric($link)){$link=null;$minheight=$link;$nomysql=true;}
	if($minheight>100){$minheight=0;}
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body($title);
		$text=$tpl->_ENGINE_parse_body($text);
		$len_text_caracts=strlen($text);
		$lang=$_COOKIE["artica-language"];
		if($lang==null){$lang="en";}
		$uid=$_SESSION["uid"];
		if($uid==-100){$uid="RootMaster";}
		$len_text_caracts=strlen($text);
			
			if(!$nomysql){
			if(!$GLOBALS["EXECUTED_AS_ROOT"]){
				$md5=md5("$title$text$link$img$lang{$GLOBALS["ICON_FAMILY"]}");
				if(!isset($_SESSION["ICON_MYSQL_CACHE"][$md5])){
					$title_sql=addslashes(strtolower($title));
					$text_sql=addslashes(strtolower($text));
					$link_sql=addslashes(base64_encode($link));
					$sql="INSERT IGNORE INTO icons_db (zmd5,title,text,icon,js,lang,family,uid) VALUES ('$md5','$title_sql','$text_sql','$img','$link_sql','$lang','{$GLOBALS["ICON_FAMILY"]}','$uid')";
					$q=new mysql();
					$q->QUERY_SQL($sql,"artica_backup");
					$_SESSION["ICON_MYSQL_CACHE"][$md5]=true;
				}
			}
		}		
		
		if(!$nomax){
			if($len_text_caracts>$max_text_caracts){$text=substr($text, 0,$max_text_caracts-3)."...";}
		}
	}
	if($minheight>0){$minheight_css="min-height:{$minheight}px;";}
	$link="<a href=\"javascript:Blurz();\" 
	style='cursor: pointer;text-align: left;color:#005447;font-size:12px;font-weight:normal;text-decoration:underline;text-transform:capitalize;' 
	OnClick=\"$link\">$title</a>";
	
	
	$html="
	\n	
	<div style='margin-bottom:10px;$minheight_css' class=explainNoPic>
		<img src='img/$img' style='float:left;margin-right:9px;clear:right;'>
		<div style='padding-left:10px'>$link</div>
		<p style='margin-top:1px;margin-bottom:0px;font-size:11px;margin-left:40px'>$text
		</p>
	</div>\n";
	
	
	SET_CACHED(__FILE__, __FUNCTION__, $MDKEY, $html);
	return $html;
	
	
}

function ParagrapheTEXT_disabled($img,$title,$text,$link=null,$minheight=0){
	$max_text_caracts=85;
	$nomysql=false;
	
	if(is_numeric($link)){$link=null;$minheight=$link;$nomysql=true;}
	if($minheight>100){$minheight=0;}	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
			$lang=$_COOKIE["artica-language"];
			if($lang==null){$lang="en";}
			$uid=$_SESSION["uid"];
			if($uid==-100){$uid="RootMaster";}
			$tpl=new templates();
			$text=$tpl->_ENGINE_parse_body($text);
			$title=$tpl->_ENGINE_parse_body($title);
			$len_text_caracts=strlen($text);
			if($len_text_caracts>$max_text_caracts){$text=substr($text, 0,$max_text_caracts-3)."...";}
	}
if($minheight>0){$minheight_css="min-height:{$minheight}px;";}
	$html="
	\n	
	<div style='margin-bottom:10px;$minheight_css' class=explainNoPic>
		<img src='img/$img' style='float:left;margin-right:9px;clear:right;opacity:0.4;filter:alpha(opacity=40)'>
		<H3 style='padding-left:10px;padding-right:0px;padding-bottom:0px;padding-top:0px;font-size:12px;color:#CCCCCC;font-weight:bold;line-height:17px;margin:0'>$title</H3>
		<p style='margin-top:-2px;margin-bottom:0px;font-size:11px;margin-left:40px;color:#CCCCCC'>
			$text
		</p>
		
	</div>\n";
	return $html;	
	
	
}
function Paragraphe_tips($img,$title,$text,$link=null,$width=210){
	$styleFloat='';
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if($link==null){$link="javascript:Blurz()";}
	$bigA=null;
	$hrf=null;
	$img_add_1=null;
	$img_add_2=null;
	$H3_STYLE_FONT_COLOR=null;
	$H3_STYLE_TITLE_FONT_COLOR=null;
	$textOrg=$text;
	$tpl=new templates();
	if(!isset($_COOKIE["artica-language"])){$_COOKIE["artica-language"]=$tpl->language;}
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="RootMaster";}
	if(!isset($_COOKIE["DisableToolTips"])){$_COOKIE["DisableToolTips"]=0;}
	$fieldMD5=md5("$title$text");
	
	$enabled=$sock->GET_INFO("$fieldMD5-$uid");
	if(is_numeric($enabled)){if($enabled==1){return null;}}
	
	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		$lang=$_COOKIE["artica-language"];
		if($lang==null){$lang="en";}
		$md5=md5("$img$title$text$link$width");
		$cachefile="ressources/logs/web/cache/$uid/icons-$md5.object.$lang.cache";
		if(is_file($cachefile)){return @file_get_contents($cachefile);}
	}
	
	if(strpos($title, ":error_notinstalled")){
		$title=str_replace(":error_notinstalled", "", $title);
		$H3_STYLE_FONT_COLOR="style='color:#CCCCCC'";
		$H3_STYLE_TITLE_FONT_COLOR="color:#CCCCCC";
	}
	
	if(is_array($img)){
		$img_add_1=$img[1];
		$img_add_2=$img[2];
		$img=$img[0];
	}
	
	
	$tips=$text;
	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		$title=$tpl->_ENGINE_parse_body($title);
		$text=$tpl->_ENGINE_parse_body($text);	
	}
	$tips=str_replace("'","",$tips);
	$tips=str_replace("	","",$tips);
	$height=null;
	if($height==null){$height="min-height:70px;";}
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		if(strpos($title," ")==0){
		if(strlen($title)>20){$title=wordwrap($title,19," ",true);}
		}}
	
	$n = rand(10e16, 10e20);
	$rand=base_convert($n, 10, 36);				
	$id=md5($img.$text.$title.$link.$rand);
	
	if(preg_match('#new:#',$link)){
		$link=str_replace('new:','',$link);
		$link="s_PopUpFull('$link',800,600);";
	}
	

	
	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){$tips=$tpl->_ENGINE_parse_body($tips);}
		
		if(strlen($title)>30){$tips="<b>$title</b><br>$tips";}
	
	if (strpos($link,'script:')){
		$jslink="$link";
		$jslink=str_replace("javascript:",'',$jslink);
	}else{$jslink="MyHref('$link');";}
	
	$jslink=str_replace('"',"'",$jslink);
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){$textOrg=$tpl->_ENGINE_parse_body($textOrg);}
	
	$jsjstip="		
	<script>
			function qtyp_{$id}(){
					$(document).ready(function(){		
						$('#text_$id').qtip({
				  			content: $('#p-text-$id').val(),
				  			show: 'mouseover',
				   			hide: 'mouseout',
				   			style: 'cream',
				   			hide: {event: 'click mouseleave',inactive: 3000},
				   			tip: true
							});	
				});
			}
			qtyp_{$id}();
		</script>";
	if($_COOKIE["DisableToolTips"]==1){$jsjstip=null;}

		$js_tips="
		<input type='hidden' id='p-text-$id' value='<strong style=font-size:13px>$title</strong><hr>$textOrg<br><hr><div style=text-align:right><a href=\"javascript:Blurz();\" OnClick=javascript($(\"$id\").qtip(\"toggle\", false); style=\"font-size:11px\">{close}</a></div>'>
		$jsjstip
		";
	
	$img_pid=md5("$img$jslink");	
	$h3style="OnClick=\"javascript:Paragraphe_{$img_pid}_Switch()\" $H3_STYLE_FONT_COLOR";
	

		
		if($img<>null){
			if(preg_match("#\/#",$img)){
				$imgsrc=$img;
				$img_p="<img src='$img' id='$img_pid'>";
			}else{
				$imgsrc="img/$img";
				$img_p="<img src='img/$img' id='$img_pid'>";
			}
		
		
					$jsaddon="
					<script>
					function Paragraphe_reverse_{$img_pid}_Switch(){
						if(document.getElementById('$img_pid')){
							document.getElementById('$img_pid').src='$imgsrc';
						}
					}
					
					function Paragraphe_{$img_pid}_Switch(){
						if(document.getElementById('$img_pid')){
							document.getElementById('$img_pid').src='img/wait_64.gif';
							}
						$jslink;
						setTimeout('Paragraphe_reverse_{$img_pid}_Switch()',1500);
					}
					</script>
					";		
		
		}
		if($link==null){
			$hrf=null;
			$h3style=$H3_STYLE_FONT_COLOR;
			$jsaddon=null;
		}

	
	$min_height="height:70px";
	$min_height_title="height:36px;";
	
	$tbl=explode("\n",$text);
	if(is_array($tbl)){
		$text=null;
	while (list ($num, $ligne) = each ($tbl) ){
		$ligne=str_replace("  "," ",$ligne);
		$ligne=str_replace("\r","",$ligne);
		if(trim($ligne)==null){continue;}
		
		$text=$text.$ligne."<br>";
	}
	
	}
		
	$max_caracts=30;
	$max_text_caracts=83;
	if($width>290){
		$max_caracts=40;
		$max_text_caracts=130;
	}	
	
	$title_entity_decode=html_entity_decode($title);

	if(trim($title)<>null){
			if(strlen($title_entity_decode)>$max_caracts){
			if(!$GLOBALS["EXECUTED_AS_ROOT"]){
				if($nowrap==0){$title_entity_decode=substr($title_entity_decode,0,($max_caracts-3))."...";}
				$title=htmlentities($title_entity_decode);
			}
			
			
		}		
		
		
		$title_lenght=strlen($title);
		if($title_lenght<25){$min_height_title=null;}
		$title="<h3 style='$min_height_title;text-transform:none;$H3_STYLE_TITLE_FONT_COLOR'>$title</h3>";

		$nowrap=1;
		if($nowrap==0){
			if(strlen($text)>$max_text_caracts){
				$text=substr($text,0,($max_text_caracts-3))."...";
				$img_p=imgtootltip($img,$textOrg,null,null,"img_$id");
				
			
			}else{
				$js_tips=null;
			}
		}
		
		$text="
		<div id='text_$id' style='$min_height;'>
			$text
		</div>";
		}else{
			$text="<span id='text_$id' style='letter-spacing:-1p'>$text</span>";
		}
	
	$height=null;
	if(trim($height)<>null){$height=";height:{$height}px";}
	
	if($float){$styleFloat=";float:left;";}
	

	
	$text=trim($text);
	if($nojs){
		$bigA="<a href=\"$link\" target=_new>";
		$bigZ="</a>";
	}
	
	//$text=$text.strlen($text);
	
	
	$text=str_replace("<br/>"," ",$text);
	$text=str_replace("<br />"," ",$text);
	$text=str_replace("<br>"," ",$text);

	$OnMouseOver="OnMouseOver=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\"";
	$OnMouseOut="OnMouseOut=\"javascript:this.className='paragraphe';this.style.cursor='default';\"";
	
	//$OnMouseOverToolTip="OnMouseOver=\"$tooltip_over\"";
	//$OnMouseOutToolTip="OnMouseOut=\"$tooltip_out\"";
	
	$html="
	<div style='$height$styleFloat;min-height:112px' class='paragraphe' id='$fieldMD5-id' $OnMouseOver $OnMouseOut>
		<table style='width:100%'>
			<tr>
				<td width=1% valign='top'>
					<div $h3style  >
					$bigA$hrf$img_p</a>
					</div>$img_add_1$img_add_2</td>
				<td valign='top' >
					<div $h3style >
						$title
						$text
					</div>
					<div style='text-align:right'>
					<table class=form style='width:99.9%'>
					<tbody>
					<tr>
						<td class=legend>{hide}:</td>
						<td>". Field_checkbox("$fieldMD5",1,0,"HideTips('$fieldMD5','$uid')")."</td>
						</tr>
					</tbody>
					</table>
					</div>
				</td>
			</tr>
		</table>
	</div>
	$jsaddon
	$js_tips
	";
	
	return $html;
	
	
}

function paragrapheWin($img,$title,$link){
	
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body($title);
	$TEMPLATE=$_COOKIE["artica-template"];
	if(isset($GLOBALS["CHANGE_TEMPLATE"])){if($GLOBALS["CHANGE_TEMPLATE"]<>null){$TEMPLATE=$GLOBALS["CHANGE_TEMPLATE"];}}
	
	
	
	$ff=dirname(__FILE__) . "/templates/$TEMPLATE/vignettes-color.conf";
	
	if(!isset($GLOBALS["paragrapheWinBG"][$TEMPLATE])){
		if(is_file($ff)){
			$GLOBALS["paragrapheWinBG"][$TEMPLATE]=@file_get_contents($ff);
		}else{
			$GLOBALS["paragrapheWinBG"][$TEMPLATE]="#0C9C86";
		}
	
	}
	if($GLOBALS["paragrapheWinBG"][$TEMPLATE]==null){$GLOBALS["paragrapheWinBG"][$TEMPLATE]="#0C9C86";}
	
	$html="<div style='width:120px;height:120px;text-align:center;
	vertical-align:bottom;background-color:{$GLOBALS["paragrapheWinBG"][$TEMPLATE]};padding:10px;border:2px solid #ABABAB'
	onmouseout=\"javascript:this.BorderColor='#ABABAB';this.style.cursor='default'\" 
		onmouseover=\"javascript:this.BorderColor='#FFFFFF';this.style.cursor='pointer'\"
		template=\"{$TEMPLATE}\"
		OnClick=\"javascript:$link\">
		<table style='width:100%'>
		<tr>
			<td style='text-align:center;vertical-align:middle;height:90px;'>
				<center style='margin-5px;'><img src='img/$img'></center>
			</td>
		</tr>
		<tr>
			<td style='text-align:left;vertical-align:bottom;'>
				<div style='text-align:left;color:#FFFFFF;font-size:14px'>$title</div>
			</td>
		</tr>
		</table>
		</div>
			";
	return $html;
	
}
function paragrapheFleche($title,$link){
	$tpl=new templates();
	$md=md5("($title,$link");
	$title=$tpl->_ENGINE_parse_body($title);
	$html="<div style='padding:10px;border:1px solid #2975B8'
	onmouseout=\"javascript:this.style.BorderColor='#2975B8';this.style.cursor='default';document.getElementById('$md-text').style.color='#2975B8'\"
	onmouseover=\"javascript:this.style.BorderColor='#B83E29';this.style.cursor='pointer';document.getElementById('$md-text').style.color='#B83E29'\"
	OnClick=\"javascript:$link\">
	<table style='width:100%'>
	<tr>
		<td style='text-align:center;vertical-align:middle;height:24px;width:25px'>
		<center><img src='img/arrow-blue-left-24.png'></center>
	</td>
	<td style='text-align:left;vertical-align:middle;'>
		<div style='text-align:left;color:#2975B8;font-size:14px' id='$md-text'>$title</div>
	</td>
	</tr>
	</table>
	</div>
	";
	return $html;

}

function paragraph_youtube($title,$uri){
	$OnMouseOver="OnMouseOver=\"this.style.cursor='pointer';\"";
	$OnMouseOut="OnMouseOut=\"this.style.cursor='default';\"";
	$uri=urlencode($uri);
	$OnClick="OnClick=\"javascript:DisplayYoutube('$uri');\"";
	
	return "
		<center $OnMouseOver $OnMouseOut $OnClick style='margin:5px;min-height:173px;border:3px solid #A0A0A0;padding:5px;margin;5px;border-radius:5px 5px 5px 5px;-moz-border-radius:5px;-webkit-border-radius:5px;'>
		<center style='font-size:18px;margin-bottom:20px;min-height:42px;'>Video &raquo; $title</center>
		<img src='img/youtube-play-64.png' style='margin:20px'>
		</center>	
			
		";
	
}


	
function Paragraphe($img,$title,$text,$link=null,$tips="go_to_section",$width="210",$height=null,$nowrap=0,$float=false,$nojs=false){
	$MINIADM=false;
	if(!isset($GLOBALS["ICON_FAMILY"])){$GLOBALS["ICON_FAMILY"]=null;}
	$lang=null;
	if($_SESSION["MINIADM"]==true){$MINIADM=true;}
	if($_COOKIE["MINIADM"]=="YES"){$MINIADM=true;}	
	$styleFloat='';
	if(preg_match("#^nowrap:(.+)#", $title,$re)){
		$title=$re[1];
		$nowrap=1;
	}
	if(preg_match("#(.+?):(.+)#", $img,$re)){$img=$re[1];$next_image=$re[2];}
	$nomysql=false;
	if($link==null){$link="javascript:Blurz()";$nomysql=true;}
	$bigA=null;
	$hrf=null;
	$img_add_1=null;
	$img_add_2=null;
	$H3_STYLE_FONT_COLOR=null;
	$H3_STYLE_TITLE_FONT_COLOR=null;
	$textOrg=str_replace("'", "`", $text);;
	$tpl=new templates();
	if(!isset($_COOKIE["artica-language"])){$_COOKIE["artica-language"]=$tpl->language;}
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="RootMaster";}
	if(!isset($_COOKIE["DisableToolTips"])){$_COOKIE["DisableToolTips"]=0;}
	if($nowrap==0){$text=str_replace("'", "`", $text);}
	$next_image=null;
	
	if(!$nowrap==0){
		if(!$GLOBALS["EXECUTED_AS_ROOT"]){
			$lang=$_COOKIE["artica-language"];
			if($lang==null){$lang="en";}
			$md5=md5("$img$title$text$link$width");
			if(!$MINIADM){
				$datas=GET_CACHED(__FILE__,__FUNCTION__,$md5,true);
				if($datas<>null){return $datas;}
			}
		}
	}
	
	if(strpos($title, ":error_notinstalled")){
		$title=str_replace(":error_notinstalled", "", $title);
		$H3_STYLE_FONT_COLOR="style='color:#CCCCCC'";
		$H3_STYLE_TITLE_FONT_COLOR="color:#CCCCCC";
	}
	
	if(is_array($img)){
		$img_add_1=$img[1];
		$img_add_2=$img[2];
		$img=$img[0];
	}
	
	if($tips==null){
		$tips=$text;
	}
	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		$title=$tpl->_ENGINE_parse_body($title);
		$title=html_entity_decode($title);
		
		$text=$tpl->_ENGINE_parse_body($text);	
	}
	$tips=str_replace("'","",$tips);
	$tips=str_replace("	","",$tips);
	$min_height=";min-height:112px";
	if(is_numeric($height)){
		if($height>112){
			$min_height="min-height:{$height}px;";
		}
	}
	
	
	
	if(preg_match('#new:#',$link)){
		$link=str_replace('new:','',$link);
		$link="s_PopUpFull('$link',800,600);";
	}	
	
	if (strpos($link,'script:')){
		$jslink="$link";
		$jslink=str_replace("javascript:",'',$jslink);
	}else{$jslink="MyHref('$link');";}
	
	$jslink=str_replace('"',"'",$jslink);	
	if(!$nomysql){
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		$md5=md5("$title$text$link$img$lang{$GLOBALS["ICON_FAMILY"]}");
		if(!isset($_SESSION["ICON_MYSQL_CACHE"][$md5])){
			$title_sql=addslashes(strtolower(html_entity_decode($title)));
			$text_sql=addslashes(strtolower(html_entity_decode($text)));
			$link_sql=addslashes(base64_encode($link));
			$sql="INSERT IGNORE INTO icons_db (zmd5,title,text,icon,js,lang,family,uid) VALUES ('$md5','$title_sql','$text_sql','$img','$link_sql','$lang','{$GLOBALS["ICON_FAMILY"]}','$uid')";
			$q=new mysql();
			$q->QUERY_SQL($sql,"artica_backup");
			$_SESSION["ICON_MYSQL_CACHE"][$md5]=true;
		}
	}}
	
	
	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
		if(strpos($title," ")==0){
		if(strlen($title)>20){$title=wordwrap($title,19," ",true);}
		}}
	$title=htmlentities($title);
	$n = rand(10e16, 10e20);
	$rand=base_convert($n, 10, 36);				
	$id=md5($img.$text.$title.$link.$rand);
	
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){$tips=$tpl->_ENGINE_parse_body($tips);}
		
	if(strlen($title)>30){$tips="<b>$title</b><br>$tips";}
	

	if(!$GLOBALS["EXECUTED_AS_ROOT"]){$textOrg=$tpl->_ENGINE_parse_body($textOrg);}
	
	$jsjstip="		
	<script>
			function qtyp_{$id}(){
					$(document).ready(function(){		
						$('#text_$id').qtip({
				  			content: $('#p-text-$id').val(),
				  			show: 'mouseover',
				   			hide: 'mouseout',
				   			style: 'cream',
				   			hide: {event: 'click mouseleave',inactive: 3000},
				   			tip: true
							});	
				});
			}
			qtyp_{$id}();
		</script>";
	if($_COOKIE["DisableToolTips"]==1){$jsjstip=null;}
	if($MINIADM){$jsjstip=null;}
		$js_tips="
		<input type='hidden' id='p-text-$id' value='<strong style=font-size:13px>$title</strong><hr>$textOrg<br><hr><div style=text-align:right><a href=\"javascript:Blurz();\" OnClick=javascript($(\"$id\").qtip(\"toggle\", false); style=\"font-size:11px\">{close}</a></div>'>
		$jsjstip
		";
	
	$img_pid=md5("$img$jslink");	
	$h3style="OnClick=\"javascript:Paragraphe_{$img_pid}_Switch()\" $H3_STYLE_FONT_COLOR";
	

		
		if($img<>null){
			if(preg_match("#\/#",$img)){
				$imgsrc=$img;
				$img_p="<img src='$img' id='$img_pid'>";
			}else{
				$imgsrc="img/$img";
				$img_p="<img src='img/$img' id='$img_pid'>";
			}
		
		
					$jsaddon="
					<script>
					function Paragraphe_reverse_{$img_pid}_Switch(){
						if(document.getElementById('$img_pid')){
							document.getElementById('$img_pid').src='$imgsrc';
						}
					}
					
					function Paragraphe_{$img_pid}_Switch(){
						if(document.getElementById('$img_pid')){
							document.getElementById('$img_pid').src='img/wait_64.gif';
							}
						$jslink;
						setTimeout('Paragraphe_reverse_{$img_pid}_Switch()',1500);
					}
					</script>
					";		
		
		}
		if($link==null){
			$hrf=null;
			$h3style=$H3_STYLE_FONT_COLOR;
			$jsaddon=null;
		}

	
	
	if($nowrap==0){
		$min_height_title="height:36px;";
	}
	
	$tbl=explode("\n",$text);
	if(is_array($tbl)){
		$text=null;
	while (list ($num, $ligne) = each ($tbl) ){
		$ligne=str_replace("  "," ",$ligne);
		$ligne=str_replace("\r","",$ligne);
		if(trim($ligne)==null){continue;}
		
		$text=$text.$ligne."<br>";
	}
	
	}
	
	
	
	$titlefontsize=14;
	$textfontsize=12;
	$max_caracts=30;
	$max_text_caracts=110;
	
	
	if($width>290){
		$max_caracts=40;
		$max_text_caracts=150;
	}	

	if($width>350){
		$max_caracts=80;
		$max_text_caracts=250;
		$titlefontsize=18;
		$textfontsize=16;
	}	
	
	if($width>749){
		$max_caracts=90;
		$max_text_caracts=350;
		$titlefontsize=22;
		$textfontsize=18;
	}
	
	$title_entity_decode=html_entity_decode($title);

	if(trim($title)<>null){
			if(strlen($title_entity_decode)>$max_caracts){
			if(!$GLOBALS["EXECUTED_AS_ROOT"]){
				if($nowrap==0){$title_entity_decode=substr($title_entity_decode,0,($max_caracts-3))."...";}
				$title=htmlentities($title_entity_decode);
			}
			
			
		}		
		
		$titleSrc=$title;
		$title_lenght=strlen($title);
		if($title_lenght<25){$min_height_title=null;}
		//supprimé, $min_height_title
		$title="<h3 style='text-transform:none;$H3_STYLE_TITLE_FONT_COLOR;font-size:{$titlefontsize}px'>$title</h3>";

		if($nowrap==0){
			if(strlen($text)>$max_text_caracts){
				$text=substr($text,0,($max_text_caracts-3))."...";
				$img_p=imgtootltip($img,$textOrg,null,null,"img_$id");
				
			
			}else{
				$js_tips=null;
			}
		}
		
		$text="
		<div id='text_$id' style='font-size:{$textfontsize}px;margin-top:5px'>
			$text
		</div>";
		}else{
			$text="<span id='text_$id'>$text</span>";
		}
	
	
	if($float){$styleFloat=";float:left;";}
	

	
	$text=trim($text);
	if($nojs){
		$bigA="<a href=\"$link\" target=_new>";
		$bigZ="</a>";
	}
	
	//$text=$text.strlen($text);
	
	
	$text=str_replace("<br/>"," ",$text);
	$text=str_replace("<br />"," ",$text);
	$text=str_replace("<br>"," ",$text);

	$OnMouseOver="OnMouseOver=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\"";
	$OnMouseOut="OnMouseOut=\"javascript:this.className='paragraphe';this.style.cursor='default';\"";
	
	//$OnMouseOverToolTip="OnMouseOver=\"$tooltip_over\"";
	//$OnMouseOutToolTip="OnMouseOut=\"$tooltip_out\"";
	if($next_image<>null){$next_image="<center style='padding:3px'><img src='img/$next_image'></center>";}
	
	if($MINIADM){
		$html="<div style='min-height:110px;text-align:left'>
		<table style='width:99.9%;'
		OnMouseOver=\"this.style.cursor='pointer';\"
		OnMouseOut=\"this.style.cursor='auto'\"
		
		>
		<tr>
		<td width=1% valign='top' style='vertical-align:top';>
			$bigA$hrf$img_p</a>
		</td>
		<td style='padding-left:5px'>
		<div >
			<strong style='font-size:{$titlefontsize}px;line-height:normal'>$titleSrc</strong>
			<div style='font-size:{$textfontsize}px;line-height:normal'>$text</div><!-- replace -->
		</div>
		</td>
		</tr>
		</table>
		</div>
		$jsaddon
		$js_tips
		";
		return "<div style='margin:5px'>".RoundedLightGrey($html,"javascript:Paragraphe_{$img_pid}_Switch()",1)."</div>";
				
		
		
	}
	
	
	$html="
	<div style='width:{$width}px$styleFloat;$min_height;margin-bottom:10px' class='paragraphe' id='$id' $OnMouseOver $OnMouseOut>
		<table style='width:100%'>
			<tr>
				<td width=1% valign='top'>
					<div $h3style  >
					$bigA$hrf$img_p</a>
					</div>$next_image$img_add_1$img_add_2</td>
				<td valign='top' >
					<div $h3style >
						$title
						$text
					</div>
				</td>
			</tr>
		</table>
	</div>
	$jsaddon
	$js_tips
	";
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){SET_CACHED(__FILE__, __FUNCTION__, $md5, $html);}
	
	return $html;
	
	
}
function ip2cidr($ip_start,$ip_end) {
	if(long2ip(ip2long($ip_start))!=$ip_start or long2ip(ip2long($ip_end))!=$ip_end) return NULL;
	$ipl_start=(int)ip2long($ip_start);
	$ipl_end=(int)ip2long($ip_end);
	if($ipl_start>0 && $ipl_end<0) $delta=($ipl_end+4294967296)-$ipl_start;
	else $delta=$ipl_end-$ipl_start;
	$netmask=str_pad(decbin($delta),32,"0","STR_PAD_LEFT");
	if(ip2long($ip_start)==0 && substr_count($netmask,"1")==32) return "0.0.0.0/0";
	if($delta<0 or ($delta>0 && $delta%2==0)) return NULL;
	for($mask=0;$mask<32;$mask++) if($netmask[$mask]==1) break;
	if(substr_count($netmask,"0")!=$mask) return NULL;
	return "$ip_start/$mask";
}

function ae_detect_ie(){
	//return false;
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)){return true;}
	
        
}

function TinyMce($FiledName,$datas,$nojs=false){
	
	$js="<script language=\"javascript\" type=\"text/javascript\" src=\"js/tiny_mce/tinymce.min.js\"></script>";
	if($nojs){$js=null;}
$html="
<textarea id=\"$FiledName\" name=\"$FiledName\" rows=\"15\" cols=\"80\" style=\"width: 100%\">$datas</textarea>		
$js
<script language=\"javascript\" type=\"text/javascript\">
tinymce.baseURL = \"/js/tiny_mce\";
	tinymce.init({
		mode : \"textareas\",
		plugins : \"pagebreak,layer,table,save,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template code\",
		theme_advanced_buttons1 : \"newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect\",
		theme_advanced_buttons2 : \"cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor\",
		theme_advanced_buttons3 : \"tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen\",
		theme_advanced_buttons4 : \"insertlayer,moveforward,movebackward,absolute,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak\",
		theme_advanced_toolbar_location : \"top\",
		theme_advanced_toolbar_align : \"left\",
		theme_advanced_statusbar_location : \"bottom\",
		theme_advanced_resizing : true,
	});
</script>
";
return $html;	
}

function TinyMceInit(){
	
return "	
	tinyMCE.init({
		mode : \"textareas\",
		theme : \"advanced\",
		skin : \"o2k7\",
		skin_variant : \"silver\",
		plugins : \"pagebreak,layer,table,save,advhr,advimage,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups\",
		theme_advanced_buttons1 : \"save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect\",
		theme_advanced_buttons2 : \"cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor\",
		theme_advanced_buttons3 : \"tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen\",
		theme_advanced_buttons4 : \"insertlayer,moveforward,movebackward,absolute,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak\",
		theme_advanced_toolbar_location : \"top\",
		theme_advanced_toolbar_align : \"left\",
		theme_advanced_statusbar_location : \"bottom\",
		theme_advanced_resizing : true,
	});\n";	
	
}

function applysettings($module_name,$js=null){
	$mod=$module_name;
	if($js==null){$js="ApplyConfig('$module_name')";}
	if($mod=='fetch'){$mod='fetchmail';}
	$html="<table style='width:100%'>
	<tr>
		<td valign='top'>" . imgtootltip('system-64.png','{apply_settings_text}',"$js")."</td>
		<td valign='top'><H5>{apply config} ($mod)</H5>{apply config text}</td>
	</tr>
	</table>
	
	";
	$tpl=new templates();
	
	return $tpl->_ENGINE_parse_body(RoundedLightGrey($html)) . "<br>";
	
}
function applysettings_dansguardian($float=false){
	
	return Paragraphe('system-64.png','{apply config}','{APPLY_SETTINGS_DANSGUARDIAN}','javascript:applysettings_dansguardian()','APPLY_SETTINGS_DANSGUARDIAN',210,null,0,$float);
}

function applysettings_miltergreylist($float=false){
	
	return Paragraphe('system-64.png','{apply config}','{APPLY_SETTINGS_MILTERGREYLIST}','javascript:applysettings_miltergreylist()','APPLY_SETTINGS_MILTERGREYLIST',210,null,0,$float);
}

function applysettingsGeneral($title,$java,$text,$noparse=false){
	$mod=$module_name;
	
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' width=1%>" . imgtootltip('system-64.png',"{".$text."}",$java)."</td>
		<td valign='top'><H5>{".$title."}</H5>{".$text."}</td>
	</tr>
	</table>
	
	";
	if(!$noparse){
		$tpl=new templates();
		return $tpl->_ENGINE_parse_body(RoundedLightWhite($html)) . "<br>";
	}else{
		return RoundedLightWhite($html) . "<br>";
	}
	
}

function applysettings_postfix($float=false){
	
	return Paragraphe('system-64.png','{apply config}','{APPLY_SETTINGS_POSTFIX}','javascript:Default_ApplyConfigPostfix()','APPLY_SETTINGS_POSTFIX',210,null,0,$float);
}
function applysettings_fetchmail($float=false){
	
	return Paragraphe('system-64.png','{apply config}','{apply_settings_text}','javascript:ApplyConfig(\'fetchmail\')','apply_settings_text',210,null,0,$float);
}

function iframe($content,$refresh=20,$bodywidth=null){
	if($bodywidth<>null){$bodywidth="width:$bodywidth";}else{$bodywidth="width:auto";}
	if($refresh>0){$refresh="<META HTTP-EQUIV='REFRESH' CONTENT='$refresh'>";}else{$refresh=null;}
	$tpl=new templates();
	
		$html="<html style='$bodywidth;margin:0px;padding:0px'>
		
		<head>$tpl->head
			<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
			$refresh
		</head><body style='margin:0px;padding:0px;background:transparent;$bodywidth'>$content</body></html>";	
	
	return  $html;	
	
}


function BuildSessionAuth(){
	$debug=false;
	$page=CurrentPageName();
	
	if($page=="miniadm.index.php"){
		if($GLOBALS["VERBOSE"]){
			$debug=true;
			if($GLOBALS["VERBOSE"]){echo "Page:$page -> DEBUG\n";}
		}
	}
	error_log("[{$_SESSION["uid"]}]::BuildSessionAuth.. in".__FUNCTION__." line ".__LINE__);
	if(!isset($_SERVER['PHP_AUTH_USER'])){
		if($debug){echo "<li>Verify ->PHP_AUTH_USER'] -> FALSE</li>\n";}
		return false;
	}
	if(!function_exists('ldap_connect')){
		if($debug){echo "<li>ldap_connect -> FALSE</li>\n";}
		return;
		
	}
	$ldap=new clladp();
	if($debug){echo "<li>Verify ->ldap->UserFromAuth({$_SERVER['PHP_AUTH_USER']}) line:".__LINE__."</li>\n";}
	$GLOBALS["UserFromAuth_debug"]=true;
	
	if($_SESSION["uid"]=="-100"){
		return;
		
	}
	
	if($ldap->IsKerbAuth()){
		BuildSession($_SERVER['PHP_AUTH_USER']);
		return;
	}
	
	$hash=$ldap->UserFromAuth($_SERVER['PHP_AUTH_USER']);
	
	if($hash[0]["uid"][0]==null){$hash[0]["uid"][0]=$hash[0]["cn"][0];}
	$uid=$hash[0]["uid"][0];
	
	if($debug){echo "<li>uid:$uid line:".__LINE__."</li>\n";}
	
	if(trim(strtolower($uid))==trim(strtolower($ldap->ldap_admin))){
		if($_SERVER["PHP_AUTH_PW"]==$ldap->ldap_password){	
		$uid="-100";
		$_SESSION["passwd"]=$_SERVER["PHP_AUTH_PW"];
		}
	}
	if(trim($uid)<>null){
		if($debug){echo "<li>uid -> $uid</li>\n";}
		$_SESSION["uid"]=$uid;
		$_SESSION["passwd"]=$_SERVER["PHP_AUTH_PW"];
		return true;
	}
	
	}
	
function DefineSessions(){


if (ini_get("session.use_trans_sid") == true) {
    ini_set("url_rewriter.tags", "");
    ini_set("session.use_trans_sid", false);

}



}

function BuildSession($uid,$nominiadm=false){
	writelogs("Building session",__FUNCTION__,__FILE__,__LINE__);
	if(isset($GLOBALS[__FILE__][__FUNCTION__])){return;}
	$GLOBALS[__FILE__][__FUNCTION__]=true;
	if($uid==null){
		if($GLOBALS["VERBOSE"]){
			echo "<strong>BuildSession() UID is null, aborting...</strong><br>\n";
			return;
		}
	}
	if($_SESSION["VirtAclUser"]){return;}
	if(!class_exists("user")){include_once(dirname(__FILE__)."/class.user.inc");}
	if($GLOBALS["VERBOSE"]){echo "<strong>BuildSession() -> `$uid`</strong><br>\n";}
	$ldap=new clladp();
	$users=new usersMenus();
	$privs=new privileges($uid);
	
	$u=new user($uid);
	$sock=new sockets();
	if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/class.langages.inc");}
	if(isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");}
	$privileges_array=$privs->privs;
	setcookie("mem-logon-user", $_POST["artica_username"], time()+172800);
	$_SESSION["privileges_array"]=$privs->privs;
	$_SESSION["privs"]=$privileges_array;
	$_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];
	$_SESSION["uid"]=$uid;
	if(isset($_POST["artica_password"])){
		$_SESSION["passwd"]=$_POST["artica_password"];
	}
	$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
	$_SESSION["groupid"]=$ldap->UserGetGroups($uid,1);
	$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
	$_SESSION["MailboxActive"]=$u->MailboxActive;
	if(!$nominiadm){$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";}
	$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
	$_SESSION["CORP"]=$users->CORP_LICENSE;
	$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
	$_SESSION["VIRTUALS_SERVERS"]=$privileges_array["VIRTUALS_SERVERS"];
	$_SESSION["POSTFIX_SERVERS"]=$privileges_array["POSTFIX_SERVERS"];
	
	
	
	$_SESSION["groupid"]=$ldap->UserGetGroups($_POST["username"],1);
	$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
	$_SESSION["MailboxActive"]=$u->MailboxActive;
	if(!$nominiadm){$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";}
	$_SESSION["ou"]=$u->ou;
	$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
	$_SESSION["AsWebStatisticsAdministrator"]=$users->AsWebStatisticsAdministrator;
	if($_SESSION["OU_LANG"]==null){
		if($GLOBALS["FixedLanguage"]<>null){
			$_SESSION["OU_LANG"]=$GLOBALS["FixedLanguage"];
		}
	}
	
	$lang=new articaLang();
	writelogs("[{$_POST["artica_username"]}]: Default organization language={$_SESSION["OU_LANG"]}",__FUNCTION__,__FILE__);
	if(trim($_SESSION["OU_LANG"])<>null){
		$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
		setcookie("artica-language", $_SESSION["OU_LANG"], time()+172800);
	}else{
		setcookie("artica-language", $_POST["lang"], time()+172800);
		$_SESSION["detected_lang"]=$lang->get_languages();
	}	
	
}
	
function CheckSessions(){
	if($GLOBALS["EXECUTED_AS_ROOT"]){return true;}
	if(!isset($_SESSION["uid"])){$_SESSION["uid"]=null;}
	if(!isset($_SESSION["MINIADM"])){$_SESSION["MINIADM"]=false;}
	if(!isset($_COOKIE["MINIADM"])){$_COOKIE["MINIADM"]="NO";}
	$suffixLogoff=null;

		
		$page=CurrentPageName();
		

		if($GLOBALS["DEBUG_TEMPLATE"]){
			if(function_exists("getmypid")){$getmypid="PID:".getmypid()." ";}
			$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			error_log("[{$_SESSION["uid"]}]::$page:{$getmypid}CheckSessions() language=\"{$_SESSION["detected_lang"]}\" $called function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);
		}
		if($page=="exec.squidguard.php"){error_log("[{$_SESSION["uid"]}]::$page:CheckSessions()=>".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
		
		if($_SESSION["uid"]==null){
			if(isset($_GET["session"])){
				if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/class.user.inc\n";}
				include_once(dirname(__FILE__).'/class.user.inc');	
				$users=new user($_GET["uid"]);
				if(md5($users->password)==$_GET["session"]){
					writelogs("Creating new session for uid={$_GET["uid"]}",__CLASS__.'/'.__FUNCTION__,__FILE__);
					$_SESSION["uid"]=$_GET["uid"];
					return true;
				}
		}}	
		
		$noback["squid.hostspot.emergency.disable.progress.php"]=true;
		$noback["squid.hostspot.emergency.enable.progress.php"]=true;
		$noback["squid.hostspot.restart.web.progress.php"]=true;
		$noback["squid.webauth.maintenance.php"]=true;
		$noback["SquidReloadInpublicAlias.php"]=true;
		$noback["itchart.php"]=true;
		$noback["ufdbgclient.php"]=true;
		$noback["public.logon.php"]=true;
		$noback["squid.categories.url.export.php"]=true;
		$noback["squid.categories.export.php"]=true;
		$noback["squid.categories.urls.php"]=true;
		$noback["squid.categories.php"]=true;
		$noback["admin.top.menus.php"]=true;
		$noback["TestsClasses.php"]=true;
		$noback["cron.obm.synchro.php"]=true;
		$noback["logon.php"]=true;
		$noback["squid.urgency.php"]=true;
		$noback["404.php"]=true;
		$noback["obm.export.php"]=true;
		$noback["auto-account.php"]=true;
		$noback["exec.scan-networks.php"]=true;
		$noback["exec.vacationtime.php"]=true;
		$noback["dansguardian.users.index.php"]=true;
		$noback["cyrus.murder.listener.php"]=true;
		$noback["cluster.listener.php"]=true;
		$noback["exec.squidguard.php"]=true;
		$noback["ufdbguardd.php"]=true;
		$noback["proxy.pac.php"]=true;
		$noback["logon.form.php"]=true;
		$noback["squid.logon.php"]=true;
		$noback["miniadm.php"]=true;
		$noback["squid.access-sql.php"]=true;
		$noback["exec.shm.php"]=true;
		$noback["cron.notifs.php"]=true;
		$noback["change.title.php"]=true;
		$noback["Inotify.php"]=true;
		$noback["squid.stats.listener.php"]=true;
		$noback["squid.blocks.listener.php"]=true;
		$noback["import.users.listener.php"]=true;
		$noback["external_acl_quota.php"]=true;
		$noback["nodes.listener.php"]=true;
		$noback["streamget.php"]=true;
		$noback["squid.users.logon.php"]=true;
		$noback["squid-helper.php"]=true;
		$noback["exec.articaget.php"]=true;
		$noback["mysqldefrag.php"]=true;
		$noback["wizard.install.php"]=true;
		$noback["xapian.index.php"]=true;
		$noback["android.logon.php"]=true;
		$noback["fonts.css.php"]=true;
		$noback["mailattach.php"]=true;
		$noback["splashproxy.php"]=true;
		$noback["authenticator.php"]=true;
		$noback["nginx.error.php"]=true;
		$noback["admin.help.php"]=true;
		$noback["jabberauth.php"]=true;
		$noback["public.squid.error.php"]=true;
		$noback["exec.squid.sslpassword_program.php"]=true;
		$noback["external_acl_dynamic.php"]=true;
		$noback["external_ufdbunblock.php"]=true;
		$noback["external_acl_squid.php"]=true;
		$noback["execdomainlist.php"]=true;
		$noback["external_acl_squid_quota.php"]=true;
		$noback["exec.logfile_daemon.php"]=true;
		$noback["external_acl_squid.php"]=true;
		$noback["hotspot.php"]=true;
		$noback["squid.access.log.php"]=true;
		$noback["wizard.automationscript.progress.php"]=true;
		$noback["import.squid.zip.php"]=true;
		$noback["squid.access.webfilter.log.php"]=true;
		$noback["artica.meta.listener.php"]=true;
		$noback["pfx.php"]=true;
		
		$noback["artica.stats.receiver.php"]=true;
		$noback["smtp.autoconfig.php"]=true;
		$noback["public.categories.personnal.php"]=true;
		if(preg_match('#^exec\..+\.php$#',$page)){$noback[$page]=true;}
		if(preg_match('#listener\.php$#',$page)){$noback[$page]= true;}		
		if(preg_match("#^prxy\..*?\.php$#", $page)){$noback[$page]=true;}
		$CUR_PAGE=CurrentPageName();
		
		if(isset($noback[$CUR_PAGE])){
			if($noback[$CUR_PAGE]){return true;}
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::`{$_SESSION["uid"]}`<br>\n";}
		if($_SESSION["uid"]==null){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::PHP_AUTH_USER -> `{$_SERVER["PHP_AUTH_USER"]}`<br>\n";}
			if(isset($_SERVER["PHP_AUTH_USER"])){
				BuildSession($_SERVER["PHP_AUTH_USER"]);
		
			}
		}
		
		if($_SESSION["uid"]<>-100){
			if(!isset($_SESSION["privileges_array"])){
				if($_SESSION["uid"]<>null){
					BuildSession($_SESSION["uid"]);
					if(!isset($_SESSION["privileges_array"])){$_SESSION["privileges_array"]=null;}
				}
			}}		
		
		
		if(!class_exists("usersMenus")){
			writelogs("Unable to stat class usersMenus include ".dirname(__FILE__)."/class.users.menus.inc",__CLASS__.'/'.__FUNCTION__,__FILE__);
			include_once(dirname(__FILE__)."/class.users.menus.inc");
		}
		

		

		
		

		
		$currentdir=basename(dirname($_SERVER['SCRIPT_FILENAME']));
		if($currentdir<>'smartsieve'){
			//session END --------------------------------------------
			if(!isset($_SESSION["uid"])){
				if(!$noback[$page]){
					$user=new usersMenus();
					if($user->LIGHTTPD_LDAP_AUTH){BuildSessionAuth();}
				}
			}
		
			if(!isset($_SESSION["uid"])){
					if(!$noback[$page]){
						if($page=="nodes.popup.php"){echo "document.location.href='logoff.php'";die();}
						if(isset($GLOBALS["STOP_SESSION_JS"])){echo "document.location.href='logoff.php'";die();}
						if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/logs.inc\n";}
						include_once(dirname(__FILE__).'/logs.inc');
						error_log("[{$_SESSION["uid"]}]::$page is not allowed, no session, `{$_SESSION["uid"]}` die.. in ".__FILE__." line ". __LINE__);
						if(isset($_GET["in-front-ajax"])){echo "document.location.href='logoff.php'";die();}
						
						while (list ($a, $b) = each ($_REQUEST) ){
							$QUERYS[]="$a=$b";
							
						}
						
						if(preg_match("#^android\.#", $page)){$suffixLogoff="android.";}
						
						if(preg_match("#-js=#", @implode("&", $QUERYS))){
							header("content-type: application/x-javascript");
							echo "alert('Session end...');\n";
							echo "document.location.href='{$suffixLogoff}logoff.php';";
							die();
						}
						
						
						echo "<html><!-- $page  -->\n<!-- ".@implode("&", $QUERYS)."  --><head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=logoff.php\"> </head>
						<body>
						<script>document.location.href='{$suffixLogoff}logoff.php';</script>
						</body>
						</html>";die();
						
						echo header('location:'.$suffixLogoff.'logoff.php');die();
					
						}
				
					
			}}

}


function FormatBytes($kbytes,$nohtml=false){
	
	$spacer="&nbsp;";
	if($nohtml){$spacer="";}
	
	if($kbytes>1048576){
		$value=round($kbytes/1048576, 2);
		if($value>1000){
			$value=round($value/1000, 2);
			return "$value{$spacer}TB";
		}
		return "$value{$spacer}GB";
	}
	elseif ($kbytes>=1024){
		$value=round($kbytes/1024, 2); 
		return "$value{$spacer}MB";
	}
	else{
		$value=round($kbytes, 2);
		return "$value{$spacer}KB";
	}
}


function ParseBytes($bytes){
	$kb=$bytes/1024;
	return FormatBytes($kb);
}



class filesClass{
	
		function FindFileInFolder($dir,$extension){
		$f=new filesClass();
		$array=$f->DirListTable($dir,true);
		while (list($num,$val)=each($array)){
			if($f->Get_extension($val)==$extension){return $val;}
		}
	}
	
	function Get_extension($filename){
		   	$parts = explode('.',$filename);
   			$last = count($parts) - 1;
   			$ext = $parts[$last];
   			return $ext;
		
		
		}
		
			
	function DirListTable($dir,$onlyfiles=false){
		$Files = array();
		$It=  @opendir($dir);
		if (! $It){return null;}
		while ($Filename = readdir($It)){
			if ($Filename == '.' || $Filename == '..')
			continue;
			
			if($onlyfiles==true){
				$ext=$this->Get_extension($Filename);
				if($ext<>null)
					$Files[]=$Filename;
				}
			else{$Files[]=$Filename;}
		}

		return $Files;
	}
	
	
	function isMounted($mountpoint){
		$mountpoint=str_replace('/','\/',$mountpoint);
		$mountpoint=str_replace('.','\.',$mountpoint);
		$datas=explode("\n",file_get_contents('/proc/mounts'));
		
		if(!is_array($datas)){return false;}
		while (list($num,$line)=each($datas)){
			if(preg_match("#\s+$mountpoint#",$line)){
				return true;
			}else{
				
			}
			
		}
		
		
	}
	
	
}


function browserTree($target_field){
	return "
		<div style='width:100%;text-align:right'>
				<input type='button' OnClick=\"javascript:Loadjs('TreeBrowse.php?t=$target_field');\" value='{browse}...&nbsp;&raquo;'>
			</div>
		";
	
}

function DAEMON_STATUS_IS_OK($key,$bsini){
	if(!isset($bsini->_params["$key"])){return true;}
	if($bsini->_params["$key"]["service_disabled"]==null){$bsini->_params["$key"]["service_disabled"]=-1;}
	if($bsini->_params["$key"]["service_disabled"]==0){return true;}
	if($bsini->_params[$key]["running"]==0){return false;}
	$master_pid=$bsini->_params[$key]["master_pid"];
	if(!is_numeric($master_pid)){$master_pid=0;}
	if($master_pid==0){return false;}
	return true;
}

function DAEMON_STATUS_TEXT($key,$bsini){
	return DAEMON_STATUS_ROUND($key,$bsini,null,0,1);
}



function DAEMON_STATUS_LINE($key,$bsini,$textoadd=null,$noenable=0,$width=null,$nostartandstop=false,$newInterface=0,$jsReplace=null){
if($bsini->_params["$key"]["service_name"]==null){return null;}
if($bsini->_params["$key"]["service_disabled"]==null){$bsini->_params["$key"]["service_disabled"]=-1;}
if($noenable==1){if($bsini->_params["$key"]["service_disabled"]==0){
	if($GLOBALS["VERBOSE"]){echo "<li>".__FUNCTION__."::".__LINE__."::$key -> Disabled</li>\n";}
	return null;}}
$service_cmd=$bsini->_params["$key"]["service_cmd"];
$monit=$bsini->_params["$key"]["monit"];
$app=$bsini->_params["$key"]["service_name"];
if($width==null){$width="98%";}

if($monit==1){
if(!isset($GLOBALS["MONIT_ARRAY"])){
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		$GLOBALS["MONIT_ARRAY"]=unserialize(base64_decode($sock->getFrameWork("monit.php?monit-status=yes")));
	}
	
	
	
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

if($bsini->_params[$key]["running"]==0){
		$img="danger16.png";
		$status="{stopped}";
		$status_color="#D01A1A";
		$js_service="Loadjs('StartStopServices.php?APP={$bsini->_params["$key"]["service_name"]}&cmd=$service_cmd&action=start')";
		if($bsini->_params[$key]["service_croned"]==1){
			$img="ok16.png";
			$status="Croned";
			$status_color="#318B2F";
		}
	}else{
		$js_service="Loadjs('StartStopServices.php?APP={$bsini->_params["$key"]["service_name"]}&cmd=$service_cmd&action=stop')";
		$img="ok16.png";
		$status="{running}";
		$status_color="#318B2F";
		
	}	
if($nostartandstop){$js_service=null;}
if($jsReplace<>null) {$js_service=$jsReplace;}
$title="{{$bsini->_params["$key"]["service_name"]}}";
if($textoadd=="noaccoloade"){$title=$bsini->_params["$key"]["service_name"];$textoadd=null;}
if(preg_match("#replacetitle:(.+)#", $textoadd,$re)){$title=$re[1];}

if($bsini->_params["$key"]["service_disabled"]==0){
	$img="warning16.png";
	$status="{disabled}";
	$status_color="#918F8F";
}


return "<table style='width:$width;margin:0px;' " .CellRollOver($js_service).">
		<tr>
			<td width=1%><img src='img/$img'></td>
			<td align='left' nowrap><strong>$title:</td>
			<td width=1% align='right' nowrap><strong><span style='font-size:10px;color:$status_color'>$status</span>$pid_text</strong></td>
		</tr>
	</table>";	
	
}

function icon_mysql_error($error){
	
	return imgtootltip("status_warning.png",$error);
}




function ASCII_TO_HTML($data){
	$data=str_replace("'","&#39",$data);
	$data=str_replace('"',"&#34",$data);
	$data=str_replace('"',"&#34",$data);
	$data=str_replace('�',"&eacute;",$data);
	$data=str_replace('�',"&egrave;",$data);
	$data=str_replace('�',"&agrave;",$data);
	$data=str_replace('�',"&acirc;",$data);
	$data=str_replace("\rn","<br>",$data);
	$data=str_replace("\n","<br>",$data);
	$data=str_replace("\r","<br>",$data);
	
	return $data;
}


function CalendarPickup(){
	$page=CurrentPageName();
	$field=$_GET["field"];
	$html="
	loadssfile('js/dhtmlxcalendar/codebase/dhtmlxcalendar.css');
	Loadjs('js/dhtmlxcalendar/dhtmlxcalendar.js');
	Loadjs('js/dhtmlxcalendar/dhtmlxcommon.js');
	window.dhx_globalImgPath='js/dhtmlxcalendar/codebase/imgs/';
	function LoadAll(){
		 if (typeof dhtmlxCalendarObject=='undefined'){	
			Loadjs('$page?CalendarPickup=yes&field=$field');
			return null;
		}
	
	var cal1 = new dhtmlxCalendarObject('$field',true,{isYearEditable: true,headerButtons: 'TMX'});
	cal1.show();
	//cal1.curDate=document.getElementById('$field').value;
	
	}
	

	LoadAll();

	
	";
	
echo $html;	
	
}


function button_browse($field_name){
	
	$btid=md5($field_name.time());
	return "<a href=\"javascript:blur()\"  OnClick=\"javascript:Loadjs('SambaBrowse.php?no-shares=yes&field=$field_name');\"
	id='$btid' class=\"Button2014 Button2014-success Button2014-lg\"  style=\"font-size:16px;text-transform:capitalize\"
	data-loading-text=\"{browse}...\" >{browse}...&nbsp;&raquo;</a>";
	
	$button="&nbsp;<input type='button' OnClick=\"javascript:Loadjs('SambaBrowse.php?no-shares=yes&field=$field_name');\" value='{browse}...&nbsp;'>";
	$button=button("{browse}...&nbsp;", "Loadjs('SambaBrowse.php?no-shares=yes&field=$field_name');");
	return $button;
	}
	
function button_browse_ldap_group($function){
	$button="&nbsp;<input type='button' OnClick=\"javascript:Loadjs('browse-ldap-groups.php?function=$function');\" value='{browse}...&nbsp;'>";
	$button=button("{browse}...&nbsp;", "Loadjs('browse-ldap-groups.php?function=$function');");
	return $button;	
	
}	
	
function button_browse_computer($computer,$field_name){
	$button="&nbsp;<input type='button' OnClick=\"javascript:Loadjs('ComputerBrowse.php?&computer=$computer&field=$field_name');\" value='{browse}...&nbsp;'>";
	return $button;
	}
	
function js_browse_direct($startfolder,$fieldname){
	return "Loadjs('SambaBrowse.php?jdisk=disk&mounted=$startfolder&t=&homeDirectory=&no-shares=yes&field=$fieldname&without-start=yes')";
	
}

function button_browse_direct($field_name,$startfolder,$size=18){

	$btid=md5($field_name.time());
	$js="Loadjs('SambaBrowse.php?jdisk=disk&mounted=$startfolder&t=&homeDirectory=&no-shares=yes&field=$field_name&without-start=yes')";
	
	$button=button("{browse}...&nbsp;", "$js",$size);
	return $button;	
	
}
	


function EMPTY_CACHE(){
	while (list ($num, $val) = each ($_SESSION) ){
		if(preg_match("#^cachepage_(.+)#",$num)){
			unset($_SESSION[$num]);
		}
	}
}

function formatQueryResultsTable($ligne,$count=null,$memory=true,$js=null,$bgcolor=null){
	if($memory){
		if($_GET["ID"][$ligne["MessageID"]]=="OK"){return null;}
		$_GET["ID"][$ligne["MessageID"]]="OK";
	}
	
	if($js==null){$js="ShowBackupMail('{$ligne["MessageID"]}')";}
	$title=$ligne["subject"];
	$title=utf8_decode($title);
	if(strlen($title)>50){
		$title=texttooltip(substr($title,0,47).'...',$title,$js,null,0,"text-decoration:underline;font-size:11px");
	}else{
	$title="<div OnClick=\"javascript:$js\" style='text-decoration:underline;font-size:11px'>$title</div>";
	}
	$body=$ligne["MessageBody"];
	if(preg_match('#<!--X-Body-of-Message-->(.+?)<!--X-Body-of-Message-End-->#is',$body,$re)){
		$body=$re[1];
		
		
	}
	
	$y=date('Y');
	$today=date('Y-m-d');
	$ligne["zDate"]=str_replace("$today",'{today}',$ligne["zDate"]);
	$ligne["zDate"]=str_replace("-$y",'',$ligne["zDate"]);
	
	
	if(is_numeric($count)){$number="$count.&nbsp;";}
	$body=strip_tags($body);
	$body=substr($body,0,300).'...';
	$body=wordwrap($body, 100, "<br />\n");
	$html="
	<tr style='background-color:$bgcolor'>
		<td style='padding:4px'>$number</td>
		<td style='padding:4px'>". round($ligne["pertinence"],2)."</td>
		<td style='padding:4px'>$title</td>
		<td style='padding:4px'>{$ligne["mailfrom"]}</td>
		<td nowrap style='padding:4px'><strong>{$ligne["zDate"]}</strong></td>
	</tR>";
	
	return $html;
	
	
	
	$tt="
	<table style='width:99%;margin-top:6px'>
	<tr>
		<td>$number$title</td>
	</tr>
	<tr>
	<td><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;-&nbsp;{$ligne["zDate"]}</span></td>
	</tr>
	<tr>
	<td>
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><img src='img/outicon_1002.gif'></td>
			<td width=99%><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailto"]}&nbsp;&raquo;</span></td>
		</tr>
		</table>
	</tr>	
	<tr>
	<td style='font-size:small'>$body</td>
	</tr>
	<tr>
	<td style='font-size:small;color:green' align='left'>{$ligne["MessageID"]} (".round($ligne["pertinence"],2).")</td>
	</tr>	
	</table>
	";
	
	return $html;
	
	
}

function formatQueryResultsAsGoogle($ligne,$count=null,$memory=true,$js=null){
	if($memory){
		if($_GET["ID"][$ligne["MessageID"]]=="OK"){return null;}
		$_GET["ID"][$ligne["MessageID"]]="OK";
	}
	
	if($js==null){$js="ShowBackupMail('{$ligne["MessageID"]}')";}
	$title=$ligne["subject"];

	if(strlen($title)>65){
		$title=texttooltip(substr($title,0,61).'...',$title,$js,null,0,"color:#0000CC;text-decoration:underline;font-size:medium");
	}else{
	$title="<a href='#' OnClick=\"javascript:$js\" style='color:#0000CC;text-decoration:underline;font-size:medium'>$title</a>";
	}
	$body=$ligne["MessageBody"];
	if(preg_match('#<!--X-Body-of-Message-->(.+?)<!--X-Body-of-Message-End-->#is',$body,$re)){
		$body=$re[1];
		
		
	}
	if(is_numeric($count)){$number="$count.&nbsp;";}
	$body=strip_tags($body);
	$body=substr($body,0,300).'...';
	$body=wordwrap($body, 100, "<br />\n");
	$html="
	
	<table style='width:99%;margin-top:6px'>
	<tr>
		<td>$number$title</td>
	</tr>
	<tr>
	<td><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;-&nbsp;{$ligne["zDate"]}</span></td>
	</tr>
	<tr>
	<td>
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><img src='img/outicon_1002.gif'></td>
			<td width=99%><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailto"]}&nbsp;&raquo;</span></td>
		</tr>
		</table>
	</tr>	
	<tr>
	<td style='font-size:small'>$body</td>
	</tr>
	<tr>
	<td style='font-size:small;color:green' align='left'>{$ligne["MessageID"]} (".round($ligne["pertinence"],2).")</td>
	</tr>	
	</table>
	";
	
	return $html;
	
	
}

function CleanMail($data){
		$data=str_replace("<hr>",'',$data);
	$data=str_replace("<o:p></o:p>","",$data);
	$data=str_replace('face="Times New Roman"',"",$data);
	$data=str_replace('face="Calibri"',"",$data);		
	$data=str_replace('face="Arial"',"",$data);
	$data=str_replace('face="Tahoma"',"",$data);	
	$data=str_replace('face=Tahoma',"",$data);		
	$data=str_replace('face=Arial',"",$data);
	$data=str_replace('face=Calibri',"",$data);
	$data=str_replace('font-family:Arial','',$data);
	$data=str_replace('font-family:Calibri','',$data);	
	$data=str_replace('font-family:Tahoma','',$data);		
	$data=str_replace('size="2"','',$data);		
	$data=str_replace('size="1"','',$data);		
	$data=str_replace('size="3"','',$data);	
	$data=str_replace('size=2','',$data);		
	$data=str_replace('size=1','',$data);		
	$data=str_replace('size=3','',$data);
	$data=str_replace('FONT-FAMILY: Verdana','',$data);
	$data=str_replace('10pt','',$data);
	$data=str_replace('10.0pt','',$data);
	$data=str_replace('11.0pt','',$data);	
	$data=str_replace('12.0pt','',$data);		
	
	$data=str_replace('<o:p>&nbsp;</o:p>','',$data);
	
	$data=str_replace("<h1>","<p style='font-size:15px;font-weight:bold;
	border-bottom:1px solid #CCCCCC;margin-bottom:5px;padding:5px;'>",$data);
	$data=str_replace("</h1>","</p>",$data);
	return $data;
}

function ICON_BIND9(){
	return Paragraphe('folder-64-bind9.png','{APP_BIND9}','{APP_BIND9_TEXT}',"javascript:Loadjs('index.bind9.php?script=yes')");
}


function ICON_USB_FORMAT($dev){
	$tpl=new templates();
	
	$js="Loadjs('usb.index.php?format-index=yes&dev=$dev')";
	$html="

	
	<table style='width:100%;'
	OnMouseOver=\";this.style.cursor='pointer';\"
	OnMouseOut=\";this.style.cursor='default';\"
	OnClick=\"javascript:$js\"
	>
	<tr>
		<td valign='top' width=1%>
			". imgtootltip('usb-32-format.png','{format_device_explain}')
			."
		</td>
		<td valign='top'><strong style='font-size:12px'>
			{formatdevice}
		</strong>
		</td>
	</tr>
	</table>";
	return $tpl->_ENGINE_parse_body($html,'usb.index.php');
	
}

function ICON_BIND9_COMPILE($nodiv=0){
$tpl=new templates();
$icon=$tpl->_ENGINE_parse_body(
Paragraphe("system-64.png",'{apply_settings_bind9}',"{apply_settings_bind9_text}",
"javascript:Loadjs('index.bind9.php?CompileBind9=yes');","apply_settings_bind9"));
if($nodiv==1){return $icon;}
return "<div id='CompileBind9'>$icon</div>";
}

function ICON_ADD_COMPUTER(){
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body(Paragraphe("computer-64-add.png","{add_computer}","{add_computer_text}","javascript:YahooWin3(670,\"domains.edit.user.php?userid=newcomputer$&ajaxmode=yes&gpid=$gpid&zone-name=$zone\",\"windows: New {add_computer}\");"),'domains.edit.group.php');
}

function ICON_DANSGUARDIAN_STATISTICS(){
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body(Paragraphe("system-64.png","{APP_DANSGUARDIAN} {compile_statistics}","{compile_statistics_text}",
	"javascript:YahooWin3(500,\"squid.newbee.php?dansguardian-stats-compile\",\"windows:{compile_statistics}\");"));
}

function ICON_OPENVPN_APPLY(){
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body(Paragraphe("system-64.png","{APP_OPENVPN_APPLY}","{APP_OPENVPN_APPLY_TEXT}",
	"javascript:YahooWin3(500,\"index.openvpn.php?restart-server=yes\",\"windows:{APP_OPENVPN_APPLY}\");"));
}

function ICON_FETCHMAIL(){
$tpl=new templates();
return $tpl->_ENGINE_parse_body(Paragraphe('folder-64-fetchmail.png',
'{APP_FETCHMAIL_TINY}','{APP_FETCHMAIL_TEXT}',"javascript:Loadjs('fetchmail.index.php?ajax=yes');",null,210,100,0,true));
	
}

function FORM_START(){
	$GLOBALS["AUTO_FORM_CONTENT"]=array();
	$GLOBALS["AUTO_FORM"]=array();
	$GLOBALS["AUTO_FORM_LOCK"]=false;
	$GLOBALS["AUTO_FORM_FUNC"]=null;
	$GLOBALS["AUTO_FORM_CLOSE"]=null;
	$GLOBALS["AUTO_FORM_HIDDEN"]=array();
	$GLOBALS["FORM_TIME"]=time();
}
function FORM_START_TABLE(){
	$GLOBALS["AUTO_FORM_CONTENT"][]="<div style='width:98%' class=form>\n\t<table style='width:100%'>";
}
function FORM_ADD_ARRAY_HASH($hash=array(),$fieldname,$value,$caption){
	$fieldname=$fieldname."-".$GLOBALS["FORM_TIME"];
	$GLOBALS["AUTO_FORM_CONTENT"][]= Field_list_table($fieldname,$caption,$value,18,$hash);
}
function FORM_ADD_HIDDEN($fieldname,$value){
	$GLOBALS["AUTO_FORM_HIDDEN"][$fieldname]=$value;
}
function FORM_ADD_FIELD($fieldname,$value,$caption,$explain=null,$size){
	$fieldname=$fieldname."-".$GLOBALS["FORM_TIME"];
	$GLOBALS["AUTO_FORM_CONTENT"][]=Field_text_table($fieldname,$caption,$value,18,$explain,$size);
}
function FORM_ADD_PARAGRAPH($fieldname,$value=0,$title,$text,$size=600){
	$fieldname=$fieldname."-".$GLOBALS["FORM_TIME"];
	$GLOBALS["AUTO_FORM_CONTENT"][]="\t\t<tr>";
	$GLOBALS["AUTO_FORM_CONTENT"][]="\t\t\t<td colspan=2>";
	$GLOBALS["AUTO_FORM_CONTENT"][]="\t\t\t".Paragraphe_switch_img($title, $text,$fieldname,$value,null,$size);
	$GLOBALS["AUTO_FORM_CONTENT"][]="\t\t\t</td>";
	$GLOBALS["AUTO_FORM_CONTENT"][]="\t\t</tr>";
}
function FORM_COMPILE($pagename,$btname){
	$GLOBALS["AUTO_FORM_CONTENT"][]=Field_button_table($btname);
	$GLOBALS["AUTO_FORM_CONTENT"][]="\t</table>\n</div>";
	$html=@implode("\n", $GLOBALS["AUTO_FORM_CONTENT"]);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(FORM_END($pagename,$GLOBALS["AUTO_FORM_CONTENT"]));
}
function FORM_ADD_TITLE($text,$size=22){
	$tpl=new templates();
	$GLOBALS["AUTO_FORM_CONTENT"][]=$tpl->_ENGINE_parse_body("<div style='font-size:{$size}px;margin-bottom:20px'>$text</div>");;
}
function FORM_CONSTRUCT_SQL_FROM_POST($table,$MasterKey){
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	$sqlEdit="UPDATE `$table` SET ".@implode(",", $edit)." WHERE `$MasterKey`='{$_POST[$MasterKey]}'";
	$sqlAdd="INSERT IGNORE INTO `$table` (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	return array($sqlAdd,$sqlEdit);
}

function FORM_END($pagename,$arrayHTML=array()){
	$html=null;
	if(count($arrayHTML)>0){$html=@implode("\n", $arrayHTML);}

	if($GLOBALS["AUTO_FORM_LOCK"]){return $html;}


	$f[]=$html;
	$f[]="<script>";
	$f[]="\tvar x{$GLOBALS["AUTO_FORM_FUNC"]}= function (obj) {";
	$f[]="\t\tvar tempvalue=obj.responseText;";
	$f[]="\t\tUnlockPage();";
	$f[]="\t\tif(tempvalue.length>3){";
	$f[]="\t\t\talert(tempvalue);";
	$f[]="\t\t\treturn;";
	$f[]="\t\t}";
	if($GLOBALS["AUTO_FORM_CLOSE"]<>null){$f[]="\t{$GLOBALS["AUTO_FORM_CLOSE"]}Hide();";}
	$f[]="\t}";
	$f[]="\tfunction {$GLOBALS["AUTO_FORM_FUNC"]}(){";
	$f[]="\t\tvar XHR = new XHRConnection();";
	while (list ($fieldname, $type) = each ($GLOBALS["AUTO_FORM"]) ){
		$fieldpost=$fieldname;
		if(preg_match("#^(.+?)-([0-9]+)$#", $fieldname,$re)){$fieldpost=$re[1];}
		
		if($type=="text"){
			$f[]="\t\tif(document.getElementById('$fieldname')){";
			$f[]="\t\t\tXHR.appendData('$fieldpost',encodeURIComponent(document.getElementById('$fieldname').value));";
			$f[]="}else{ alert('$fieldname no such id');}";
			continue;
		}
		if($type=="pass"){
			$f[]="\t\tif(document.getElementById('$fieldname')){";
			$f[]="\t\t\tXHR.appendData('$fieldpost',encodeURIComponent(document.getElementById('$fieldname').value));";
			$f[]="}";
			continue;
		}
		if($type=="checkbox"){
			$f[]="\t\tif(document.getElementById('$fieldname')){";
			$f[]="\t\t\tif(document.getElementById('$fieldname').checked){XHR.appendData('$fieldpost',1);}else{XHR.appendData('$fieldpost',0);}";
			$f[]="}";
			continue;
		}
	}
	
	while (list ($fieldname, $value) = each ($GLOBALS["AUTO_FORM_HIDDEN"]) ){
		$f[]="\t\tXHR.appendData('$fieldname','$value');";
	}
	

	$f[]="\t\tXHR.sendAndLoad('$pagename', 'POST',x{$GLOBALS["AUTO_FORM_FUNC"]});";
	$f[]="\t}";

	$html=@implode("\n", $f);
	$GLOBALS["AUTO_FORM"]=array();
	$GLOBALS["AUTO_FORM_LOCK"]=false;
	$GLOBALS["AUTO_FORM_FUNC"]=null;
	return $html;

}


function FORM_CLOSE($Yahoowin){
	$GLOBALS["AUTO_FORM_CLOSE"]=$Yahoowin;
}

function FORM_LOCK(){
	$GLOBALS["AUTO_FORM_LOCK"]=true;
}


function URL_COMPUTER($uid,$win){
	$uid=urlencode($uid);
	return "YahooWin$win(986,'domains.edit.user.php?userid=$uid$&ajaxmode=yes&Yahoowin=$win','$uid')";
}
function MEMBER_JS($uid,$noclik=0,$nojavascript=0,$dn=null,$t=null){
	$uid_text=str_replace("$","",$uid);
	$uid=trim($uid);
	$uid=urlencode($uid);
	if($t==null){if(isset($_GET["t"])){$t=$_GET["t"];}}
	
	if($dn<>null){$dn=urlencode(base64_encode($dn));}
	$java="javascript:";
	if($nojavascript==1){$java=null;}
	$js="{$java}YahooUser(1051,'domains.edit.user.php?userid=$uid&ajaxmode=yes&dn=$dn&t=$t','$uid_text',true);";
	if($noclik==0){
		return "OnClick=\"$js\"";
	}
	return $js;
}
function COMPUTER_JS($uid,$noclik=0,$nojavascript=0,$dn=null){
	$uid_text=str_replace("$","",$uid);
	$uid=trim($uid);
	if($dn<>null){$dn=urlencode(base64_encode($dn));}
	$java="javascript:";
	if($nojavascript==1){$java=null;}
	$js="{$java}YahooUser(1051,'domains.edit.user.php?userid=$uid&ajaxmode=yes&dn=$dn','$uid_text');";
	if($noclik==0){
		return "OnClick=\"$js\"";
	}
	return $js;
}
function br2nl($string){
	return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

function p2nl ($str) {
   return preg_replace(array("/<p[^>]*>/iU","/<\/p[^>]*>/iU"),array("","\n"),$str);
}

function strip_tabs($array){
$tpl=new templates()	;
while (list ($num, $ligne) = each ($array) ){
	$title=$tpl->_ENGINE_parse_body($ligne);
	$title=html_entity_decode($title);
	$title=replace_accents($title);
	$calc=str_replace(" ","-",$title);
	if(strlen($calc)>15){
		$text=$title;
		$title=substr($title,0,11);
		$title=str_replace(" ","&nbsp;",$title);
		$link=texttooltip($title."...",$text,null,null,1);
		$re[$num]=$link;
	}else{
		
		$title=str_replace(" ","&nbsp;",$title);
		$re[$num]=$title;
	}
	
	
}

return $re;
	
}

function IsIPValid($ip) {
    $ip_segments = explode('.', $ip);

    if (count($ip_segments) != 4)
        return FALSE;

    if ($ip_segments[0] == 0)
        return FALSE;

    foreach ($ip_segments as $segment) {
        if (!ctype_digit($segment))
            return FALSE;
        if (strlen($segment) > 3)
            return FALSE;
        if ($segment > 255)
            return FALSE;
    }
        
    return TRUE;
}

function isBuildPid($file){
$pid=getmypid();
$file=basename($file);
$pidfile="/etc/artica-postfix/croned.1/$file.pid";
if(file_exists($pidfile)){
	$currentpid=trim(@file_get_contents($pidfile));
	write_syslog("NewPID PID: $pid");
	write_syslog("Current PID: $currentpid");
	if($currentpid<>$pid){
		if(is_dir('/proc/'.$currentpid)){
			write_syslog("Already instance executed");
			return false;
	}else{
		write_syslog("$currentpid is not executed continue...");
		return true;
	}
		
	}
	
}	

return true;
}
function BuildParagraphe($title,$content,$js,$image,$div=false){
	$page=CurrentPageName();
	$element=element_rollover($js);
	$html="<div style='float:none;padding:3px;'>
	<table $element style='width:99%;border-right:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;margin:3px'>
	<tr>
	<td width=1% valign='top'>" . imgtootltip($image,"{{$content}}")."</td>
	<td valign='top'>
	<table style='width:100%'>
	<tr>
	<td style='border-bottom:1px dotted #CCCCCC'><strong style='font-size:13px'>{{$title}}</strong></td>
	</tr>
	<tr>
	<td valign='top'><div style='min-height:59px'><a href=\"javascript:$js\" style='font-size:11px'>{{$content}}</a></div></td>
	</tr>
	</table>
	</td>
	</tr>
	</table></div>";
	
	if($div){
		$html="<div style='float:left;width:200px;margin:5px'>$html</div>";
	}
return $html;	
	
}



function system_alreadyExecuted($phpfile,$logfile=null){
	
	if(is_file("framework/frame.class.inc")){
		if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::". dirname(__FILE__)."framework/frame.class.inc\n";}
		include_once("framework/frame.class.inc");
	}
	if(is_file("../framework/frame.class.inc")){
		if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::". dirname(__FILE__)."../framework/frame.class.inc\n";}
		include_once("../framework/frame.class.inc");
	}
	
	if(function_exists("Build_pid_func")){
		return Build_pid_func($phpfile,"MAIN");
	}
	
	//system_alreadyExecuted_events("want to find ". basename($phpfile),$logfile);
	$filetemp=OS_FILE_TEMP();
	$phpfile=basename($phpfile);
	$phpfile=str_replace('.','\.',$phpfile);
	$pid=getmypid();
	exec("/usr/bin/pgrep -l -f \"$phpfile\"",$vals);
	
	while (list ($num, $val) = each ($vals) ){
		$val=trim($val);
		if($val==null){continue;}
		if(preg_match("#pgrep#",$val)){continue;}
		if(preg_match('#([0-9]+)#',$val,$re)){
			
			if(is_file("/proc/{$re[1]}/exe")){$realfile=@readlink("/proc/{$re[1]}/exe");}
			system_alreadyExecuted_events("Found pid $val $realfile",$logfile);
			$array[$re[1]]="[{$re[1]}]:$realfile";
		}else{
			system_alreadyExecuted_events("unable to preg_match $val",$logfile);
		}
		
	}
	
	if(!is_array($array)){
		system_alreadyExecuted_events("no array given..");
		return false;
	}
	
	//system_alreadyExecuted_events("remove pid $pid of ".implode(",",$array),$logfile);
	unset($array[$pid]);
	unset($array[null]);
	if(!is_array($array)){
		system_alreadyExecuted_events("no array given..",$logfile);
		return false;
		}
	
	while (list ($num, $val) = each ($array) ){
		
		if(is_file("/proc/$num/exe")){
			$realfile=@readlink("/proc/$num/exe");
			if($realfile=="/bin/bash"){continue;}
			if($realfile=="/bin/dash"){continue;}
			//if($realfile=="/usr/bin/php5"){continue;}
			//system_alreadyExecuted_events("$date [$pid]: /proc/$num/exe exists \"". @readlink("/proc/$num/exe")."\" for \"$phpfile\"",$logfile);
			system_alreadyExecuted_events("$phpfile FOUND IN MEMORY",$logfile);
			return true;
		}
	}
	
	

}
function system_alreadyExecuted_events($text,$filepath=null){
		if($filepath==null){return null;}
		if(!is_file($filepath)){return null;}		
		$filename=basename(__FILE__);
		$pid=getmypid();
		$date=date("H:i:s");
		$logFile=$filepath;
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid] $filename system_alreadyExecuted(): $text\n");
		@fclose($f);		
		}
		
function TransFormBounceError($bounce_error){
	if(preg_match('#User unknown#',$bounce_error)){$bounce_error="User unknown";}
	if(preg_match('#250.+?[OokK]+#',$bounce_error)){$bounce_error="Sended";}
	if(preg_match('#delivered via#',$bounce_error)){$bounce_error="Sended";}	
	return $bounce_error;	
	
}

function SET_CACHE($content,$page){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$sock->DATA_CACHE_SAVE($page,$content);
}
function GET_CACHE($page){
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		return $sock->DATA_CACHE($page,false);
}

function distanceMinStrings($lastSeen){
	$toTime=time();
	$fromTime=strtotime($lastSeen);
	$distanceInSeconds = round(abs($toTime - $fromTime));
    $distanceInMinutes = round($distanceInSeconds / 60);
    return $distanceInMinutes;
}


function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
	    $distanceInSeconds = round(abs($toTime - $fromTime));
	    $distanceInMinutes = round($distanceInSeconds / 60);
	       
	        if ( $distanceInMinutes <= 1 ) {
	            if ( !$showLessThanAMinute ) {
	                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
	            } else {
	                if ( $distanceInSeconds < 5 ) {
	                    return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 10 ) {
	                    return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 20 ) {
	                    return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
	                }
	                if ( $distanceInSeconds < 40 ) {
	                    return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 60 ) {
	                    return '{lessthanaminute}';
	                }
	               
	                return '1 minute';
	            }
	        }
	        if ( $distanceInMinutes < 45 ) {
	            return $distanceInMinutes . ' {minutes}';
	        }
	        if ( $distanceInMinutes < 90 ) {
	            return '{abouttime} 1 {hour}';
	        }
	        if ( $distanceInMinutes < 1440 ) {
	            return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
	        }
	        if ( $distanceInMinutes < 2880 ) {
	            return '1 {day}';
	        }
	        if ( $distanceInMinutes < 43200 ) {
	            return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
	        }
	        if ( $distanceInMinutes < 86400 ) {
	            return '{abouttime} 1 {month}';
	        }
	        if ( $distanceInMinutes < 525600 ) {
	            return round(floatval($distanceInMinutes) / 43200) . ' {months}';
	        }
	        if ( $distanceInMinutes < 1051199 ) {
	            return '{abouttime} 1 {year}';
	        }
	       
	        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
	        
}

function file_ext($filename)
{
$filename = strtolower($filename) ;
$exts = explode(".", $filename) ;
$n = count($exts)-1;
$exts = $exts[$n];
return $exts;
} 
function file_get_time_min($path){
	if(!is_file($path)){if(!is_dir($path)){return 10000;}}
	 $last_modified = filemtime($path);
	 
$data1 = $last_modified;

$data2 = time();
$difference = ($data2 - $data1); 	 
return round($difference/60);	 
}


function send_email_events($subject,$text,$context,$date=null,$attached_files=array(),$recipient=null){
 	if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
  	if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
  	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
  	$text=$called."\n".$text;
	$GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context,$date,$attached_files,$recipient);
	}
	
function THREAD_COMMAND_SET($zcommands){
 	if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
  	if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
  	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($zcommands);
}

function _admin_event($text,$function,$file,$line,$category){
	$mem=round(((memory_get_usage(true)/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	
	if($GLOBALS["EXECUTED_AS_ROOT"]){
		$md5=md5($serialize);
		if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events",0755,true);}
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events/$md5.log", $serialize);	
		return;
	}
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?admin-events=".base64_encode($serialize));
	
}

function _udfbguard_admin_events($text,$function,$file,$line,$category){
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	$text=addslashes($text);
	$file=basename($file);
	$zdate=date("Y-m-d H:i:s");
	$f="('$zdate','$function','$file','$line','$text','$category','{$GLOBALS["SCHEDULE_ID"]}')";
	$prefix="INSERT IGNORE INTO ufdbguard_admin_events 
		(`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES $f";
	if(!class_exists("mysql")){include_once(dirname(__FILE__)."/class.mysql.inc");}
	$q=new mysql();
	$q->QUERY_SQL($prefix,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
}

function infected_queue($TaskName,$InfectedPath,$ComputerName,$virusname){
	$date=date('Y-m-d H:i:s');
	$md5=md5($date.$product.$from.$virusname);
	$filename="{$GLOBALS["ARTICALOGDIR"]}/infected-queue/$md5.sql";
	if(is_file($filename)){return null;}
	if($TaskName=="kavmilter"){$TaskName="eMail Scan Kaspersky";}
	$sql="INSERT INTO antivirus_events (zDate,TaskName,VirusName,InfectedPath,ComputerName,zmd5) VALUES('$date','$TaskName','$virusname','$InfectedPath','$ComputerName','$md5');";
	@file_put_contents($filename,$sql);
	}


//##############################################################################	
function autoencoder($s)//encode if necessary
{
$is_utf=1;
$ss=SplitByLength($s,5000);//with 10000+ => segfault (apache/php)
foreach($ss AS $s_)
{
if
(
preg_match('%^(?:
[\x09\x0A\x0D\x20-\x7E]              # ASCII
| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
)*$%xs',
$s_)
)
$is_utf=0;
}
if(!$is_utf)
$s=utf8_encode($s);
return $s;
}
function SplitByLength($string, $chunkLength=1){
$Result     = array();
$Remainder  = strlen($string) % $chunkLength;
$cycles = ((strlen($string) - $Remainder) / $chunkLength) + (($Remainder != 0) ? 1 : 0);
for ($x=0; $x < $cycles; $x++)
$Result[$x] = substr($string, ($x * $chunkLength), $chunkLength);
return $Result;
}
class tweak_array{
		var $error_l = '<p><b>Warning:</b> Check your input parameters in your call to <i><b>';
		var $error_r = '</b></i></p>';
		
		function insert($array = '', $position = '' , $elements= ''){
			if ($position == '' || $array == '' || $elements == '' || $position < 1 || $position > count($array)+1){
				return $array;
			}else{
				$left = array_slice ($array, 0, $position-1);
				$right = array_slice ($array, $position-1);
				$insert = explode ('\,', $elements);
				$array = array_merge ($left, $insert, $right);
				unset($left, $right, $insert);
			}
		
			unset ($position, $elements);
			return $array;
		}
		
		function delete($array = '', $from = '', $to=''){
			if ($to == '') $to = $from;
			if ($array == '' || $from == '' || $to > count($array) || $to < 0 || $from > count($array)){
				return $array;
			}
			elseif ($to < $from){
				return $array;
			}
			else{
			$left = array_slice ($array, 0, $from-1);
			$right = array_slice ($array, $to);
			$array = array_merge ($left, $right);
			unset ($left, $right);
			}
			unset ($from, $to);
			return $array;
		}
		
		function move($array = '', $from = '', $to = ''){
				if ($array == ''|| $from == ''|| $to == '' || $to > count($array) || $to < 0 || $from > count($array) || $from < 0){
					return $array;
				}
				else{
					$hopper = $array[$from-1];
					$array = $this->delete($array, $from);
					$array = $this->insert($array, $to, $hopper);
				}
				unset ($hopper, $from, $to);
				return $array;
		}
		
		function replace($array = '', $position = '', $new_element = ' '){
			if ($array == '' || $position == '' || $position > count($array) || $position < 1){return $array;}
			else{
				$array = $this->insert($array, $position, $new_element);
				$array = $this->delete($array, $position+count(explode('\,', $new_element)));
			}
		
			unset ($position, $new_element);
			return $array;
		}
} 
function button($text,$js,$fontsize=12.5,$width=0,$height=0){
	$fontsizeSrc=$fontsize;
	$width_text=null;
	$tpl=new templates();
	if(is_numeric($fontsize)){if($fontsize>0){$fontsize="{$fontsize}px";}}
	if($fontsize==null){$fontsize="12px";}
	$miniadm=false;
	$height_text=null;
	$lineheight=null;
	if($_SESSION["MINIADM"]){$miniadm=true;}
	$page=CurrentPageName();
	$Loading=$tpl->javascript_parse_text("{loading}");
	if(preg_match("#^miniadm#", $page)){$miniadm=true;}
		
	if(preg_match("#^([0-9]+)px#", $fontsize,$re)){$fontsize=$re[1];}
	$btid=md5("$text$js$fontsize".time());
	
	if($height>0){
		if($height>29){$lineheight=";line-height:1.9 !important";}
		$height_text=";height:{$height}px;vertical-align:middle$lineheight";
	}
	
	if($miniadm){
		
		
		
		
		$script="<script> $('#$btid').on('click', function (e) { $js }) </script>";
		if(preg_match("#^([0-9]+)#", $fontsize,$re)){$fontsize=$re[1];}
		
		$text=$tpl->_ENGINE_parse_body($text);
		if($fontsizeSrc<12.5){
			return "<button class=\"btn\" type=\"button\" id='$btid'
			style='font-size:{$fontsize}px' data-loading-text=\"$Loading...\">$text</button>$script";
		}
		if($fontsize<12.5){$fontsize=16;}
		return "<button class=\"btn-large btn-primary\" type=\"button\" id='$btid' 
		style='font-size:{$fontsize}px' data-loading-text=\"$Loading...\">$text</button>$script";
	
		
		return "<button class=\"btn btn-primary\" type=\"button\">$text</button>$script";
	}
	
	if($width>0){$width_text=";width:{$width}px";}
	return "<a href=\"javascript:blur()\" OnClick=\"javascript:$js\" 
	id='$btid' class=\"Button2014 Button2014-success Button2014-lg\"  
	style=\"font-size:{$fontsize}px;text-transform:capitalize$width_text$height_text\" 
	data-loading-text=\"$Loading...\" >&laquo;&nbsp;$text&nbsp;&raquo;</a>";
	
	
	
	
}

function pourcentage_basic($pourc,$bandwith_color,$text){
	$text=str_replace(" ", "&nbsp;", $text);
return"<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $bandwith_color;line-height:normal'>
			<div style='width:{$pourc}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;line-height:normal;background-color:$bandwith_color;font-size:12px !important'><strong>$text</strong></div>
		</div>";
	
}

function pourcentage($pourc,$submargin=0,$color="red"){
	$pourc=str_replace('%','',$pourc);
	$orginal_pourc=$pourc;
	$marginTop=null;
	$pourc=round($pourc);
	$back="percentImage_back4.png";
	if($color=="red"){
		$back="percentImage_back4.png";
	}
	
	if($color=="green"){
		$back="percentImage_back1.png";
	}
	
	if($pourc<0){$pourc=0;}
	if($pourc==0){$int=-119;}
	if($pourc>0){$int=$pourc-118;}
	if($pourc>50){$int=$pourc-100;}
	if($submargin>0){$marginTop="margin-top:-{$submargin}px;";}
	
	$precent="
		<table style=\"border:0px;background-color:transparent;$marginTop;width:5%\">
			<tbody>
			<tr style=\"border:0px;background-color:transparent;\">
				<td style=\"border:0px;background-color:transparent;padding:3px\">
					<img style=\"background:white url(img/$back) no-repeat scroll 1px 0;margin:5px 0 0;padding:0;background-position:{$int}px 0pt;\" src=\"img/percentImage.png\" width=123px ></td>
				<td width=1% nowrap style=\"font-size:12px !important;border:0px;background-color: transparent;vertical-align:bottom\">$orginal_pourc%</td>
			</tr>
		</tbody>
		</table>";
	
	return $precent;
}

/*
 * Array
(
    [unix:///var/run/memcached.sock:0] => Array
        (
            [pid] => 28040
            [uptime] => 15
            [time] => 1394840023
            [version] => 1.4.17
            [libevent] => 2.0.19-stable
            [pointer_size] => 64
            [rusage_user] => 0.000000
            [rusage_system] => 0.000000
            [curr_connections] => 1
            [total_connections] => 8
            [connection_structures] => 2
            [reserved_fds] => 20
            [cmd_get] => 3
            [cmd_set] => 4
            [cmd_flush] => 0
            [cmd_touch] => 0
            [get_hits] => 0
            [get_misses] => 3
            [delete_misses] => 0
            [delete_hits] => 0
            [incr_misses] => 0
            [incr_hits] => 0
            [decr_misses] => 0
            [decr_hits] => 0
            [cas_misses] => 0
            [cas_hits] => 0
            [cas_badval] => 0
            [touch_hits] => 0
            [touch_misses] => 0
            [auth_cmds] => 0
            [auth_errors] => 0
            [bytes_read] => 10828
            [bytes_written] => 3198
            [limit_maxbytes] => 67108864
            [accepting_conns] => 1
            [listen_disabled_num] => 0
            [threads] => 4
            [conn_yields] => 0
            [hash_power_level] => 16
            [hash_bytes] => 524288
            [hash_is_expanding] => 0
            [malloc_fails] => 0
            [bytes] => 10882
            [curr_items] => 4
            [total_items] => 4
            [expired_unfetched] => 0
            [evicted_unfetched] => 0
            [evictions] => 0
            [reclaimed] => 0
        )

)

*/


function GET_CACHED($file,$function,$token=null,$return=false,$maxmin=0){
	
	if($GLOBALS["VERBOSE"]){return;}
	if($GLOBALS["AS_ROOT"]){return false;}
	$MD5=GET_CACHED_KEY($file,$function,$token);
	

	$tpl=new templates();
	if(class_exists("Memcache")){
		if(_is_socket("/var/run/memcached.sock")){	
			$memcache = new Memcache();
			if($memcache->connect('unix:///var/run/memcached.sock', 0)){
				$data=$memcache->get($MD5);
				if(trim($data)<>null){ 
					if($return){ return $tpl->_ENGINE_parse_body($data);  }	
					echo $tpl->_ENGINE_parse_body($data);
					return true;
				}
			}
		}
	}	
	
	
	
	$dir=dirname(__FILE__)."/logs/web/cache";
	$targetFile="$dir/$MD5.cache";
	if(!is_file($targetFile)){return false;}
	$data=trim(@file_get_contents($targetFile));
	if(trim($data)==null){return false;}
	if($return){return $tpl->_ENGINE_parse_body($data);}
	echo $tpl->_ENGINE_parse_body($data);
	return true;
	
	
	
}

function _alt_stat($file) {
	$ss=@stat($file);
	if(!$ss) return false; 
	$ts=array( 0140000=>'ssocket', 0120000=>'llink', 0100000=>'-file', 0060000=>'bblock', 0040000=>'ddir', 0020000=>'cchar', 0010000=>'pfifo');
	$p=$ss['mode'];
	$t=decoct($ss['mode'] & 0170000); // File Encoding Bit
	$str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
	$str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
	$str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
	$str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
	$str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
	$str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
	$str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
	$s=array(	'filetype'=>array( 'type'=>substr($ts[octdec($t)],1)));
	return $s;
}

function _is_socket($socketpath){
	if(isset($GLOBALS["_is_socket"][$socketpath])){return $GLOBALS["_is_socket"][$socketpath];}
	$stat=_alt_stat($socketpath);
	$type=$stat["filetype"]["type"];
	if($type=="socket"){
		$GLOBALS["_is_socket"][$socketpath]=true;
		return true;
	}
	$GLOBALS["_is_socket"][$socketpath]=false;
	return false;
}

function GET_CACHED_KEY($file,$function,$token){
	if(trim($token)==null){$token=$function;}
	$file=basename($file);
	$token=md5($token);
	
	if($GLOBALS["AS_ROOT"]){$uid=-100;}else{
		if(!isset($_SESSION["uid"])){$_SESSION["uid"]=null;}
		$uid=$_SESSION["uid"];
	}
	if($uid==-100){$uid="RootMaster";}
	return md5("$uid$file$function$token");
	
}

function SET_CACHED($file,$function=null,$token=null,$datas=null){
	if(strlen($datas)<5){return;}
	if($GLOBALS["AS_ROOT"]){$uid=-100;}
	
	
	if(trim($token)==null){$token=$function;}
	$file=basename($file);
	$token=md5($token);
	$MD5=GET_CACHED_KEY($file,$function,$token);
	
	

	if(class_exists("Memcache")){
		if(_is_socket("/var/run/memcached.sock")){	
			$memcache = new Memcache();
			if($memcache->connect('unix:///var/run/memcached.sock', 0)){
				if($memcache->set($MD5, $datas, 0, 300)){ return; } 
			}
		}
	}
	
	$dir=dirname(__FILE__)."/logs/web/cache";
	if(!is_dir($dir)){@mkdir($dir,0755,true);}
	$targetFile="$dir/$MD5.cache";
	@file_put_contents($targetFile,$datas);
	@chmod($targetFile, 0777);	
}


FUNCTION REMOVE_CACHED($file=null){
	$sizetot=0;
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="RootMaster";}	
	$dir=dirname(__FILE__)."/logs/web/cache/$uid";
	foreach (glob("$dir/".basename($file)."*.*") as $filename) {
		writelogs("removing $filename",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$size=@filesize($filename)/1024;
		$sizetot=$sizetot+$size;
		unlink($filename);
	}
	
	if(class_exists("Memcache")){
		if(_is_socket("/var/run/memcached.sock")){	
			$memcache = new Memcache();
			$memcache->connect('unix:///var/run/memcached.sock', 0);
			$ARRAY=unserialize($memcache->get('ARTICACACHEARRAY'));
			$size=strlen(serialize($ARRAY))/1024;
			$sizetot=$sizetot+$size;
			//writelogs("Memcache SET ".strlen(serialize($ARRAY))." bytes length",__FUNCTION__,__FILE__,__LINE__);
			$memcache->set('ARTICACACHEARRAY', array(), 0, 300); 
		}	
	}
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$sock->DATA_CACHE_EMPTY();
	unset($_SESSION["cached-pages"]);
	return $sizetot;
}
function finduser_format($array,$faltomde=false){
	$tpl=new templates();
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	if(strpos($array["sn"], ',')>0){$array["sn"]=null;}
	
	$uid=$array["uid"];
	
	$disp="{$array["givenname"]} {$array["sn"]}";
	if(trim($disp)=="{unknown}"){$disp=null;}
	
	if(trim($disp)==null){
		$disp=$array["displayname"];
	}	
	$keyimg="uid={$array["uid"]}";
	if($array["JS"]<>null){
		if(isset($array["employeenumber"])){
			$array["JS"]="javascript:Loadjs('contact.php?employeeNumber={$array["employeenumber"]}')";
			if($array["img"]=="img/contact-unknown-user.png"){$array["img"]="img/contact-60.png";}
			$keyimg="employeeNumber={$array["employeenumber"]}";	
			
		}
		
	}	
	if(is_numeric($array["uidNumber"])){
		$uidn="ID:&nbsp;<b>{$array["uidNumber"]}</b><br>";
	}
	if(trim($array["ou"])<>null){
		$ou="$organization:<b>{$array["ou"]}</b><br>";
	}
	
	if($array["mail"]<>null){
		$mail=$array["mail"];
	}	
	if($array["phone"]<>null){
		if($array["mobile"]<>null){$array["mobile"]="&nbsp|&nbsp;{$array["mobile"]}";}
	
		$phone="{$array["phone"]}{$array["mobile"]}<br>";
	}
	
	if($array["title"]<>null){
		$title=$array["title"]."<br>";
	}
	
	if($faltomde){
		$html="
		<div style='width:98%' class=form>		
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><a href=\"javascript:Blurz();\" OnClick=\"javascript:s_PopUp('edit.thumbnail.php?$keyimg',550,250);\"><img src='{$array["img"]}'></a></td>
			<td valign='top'>
				<table>
				<tr>
					<td><span style='font-size:14px;font-weight:bold'>$disp</span>
					<div style='font-size:11px'><i>$mail</i></div>
					<div style='font-size:11px'><i>$ou$title
					<a href=\"javascript:Blurz();\" 
						OnClick=\"javascript:Loadjs('domains.edit.user.phones.php?$keyimg')\" style='font-size:11px;text-decoration:underline'>
						$phone</a>
						$uidn</i></div>
				</td>
			</tr>
			</table>
			</td>
		</tr>
		</table>
		</div>
		";
		return $html;
		
		
	}
	
	$color="black";
return array(
		'id' => "$uidn",
		'cell' => array(
		"<a href=\"javascript:Blurz();\" 
				OnClick=\"javascript:s_PopUp('edit.thumbnail.php?$keyimg',550,250);\">
				<img src='{$array["img"]}'></a>",
		"<a href=\"javascript:Blurz();\"  OnClick=\"{$array["JS"]};\" 
		style='font-size:20px;text-decoration:underline;color:$color'>$uid</a>
		<br><span style='font-size:18px;margin-bottom:-10px;text-transform:capitalize'>$disp</span>
		<br><span style='font-size:16px'><i>$ou$title$phone$uidn</i>",
		"<a href=\"javascript:Blurz();\"  OnClick=\"{$array["JS"]};\" 
			style='font-size:20px;text-decoration:underline;color:$color'>$mail</a></span>",
		
		
		)
		);	
		

	
}
function is_base64_encoded($encodedString) {
	if($encodedString==null){return false;}
	$fincaracts=substr($encodedString,strlen($encodedString)-2,2);
	writelogs("\"$encodedString\" fin = \"$fincaracts\"",__FUNCTION__,__FILE__,__LINE__);
 	//writelogs("$encodedString =\"".substr($encodedString,strlen($encodedString)-2,2)."\"",__FUNCTION__,__FILE__,__LINE__);
 	if(substr($encodedString,strlen($encodedString)-2,2)=="=="){return true;}
 	$arr["1"]=true;
 	$arr["2"]=true;
 	$arr["3"]=true;
 	$arr["4"]=true;
 	$arr["6"]=true;
 	$arr["16"]=true;
 	$arr["17"]=true;
 	$arr["18"]=true;
 	$arr["19"]=true;
 	$arr["20"]=true;
 	$arr["21"]=true;
 	$arr["22"]=true;
 	$arr["23"]=true;
 	$arr["24"]=true;
 	$arr["25"]=true;
 	$arr["26"]=true;
 	$arr["27"]=true;
 	$arr["28"]=true;
 	$arr["29"]=true;
 	$arr["30"]=true;
 	$arr["31"]=true;
 	$arr["28"]=true;
 	$arr["126"]=true;
 	$arr["127"]=true;
 	$arr["128"]=true;
 	$arr["129"]=true;
 	$arr["131"]=true;
 	$arr["132"]=true;
 	$arr["133"]=true;
 	$arr["134"]=true;
 	$arr["135"]=true;
 	$arr["136"]=true; 	
	$arr["137"]=true;
	$arr["138"]=true;	
 	$arr["139"]=true;
 	$arr["140"]=true;
 	$arr["146"]=true;
	$arr["150"]=true;
	$arr["154"]=true;
	$arr["157"]=true;
	$arr["155"]=true;
 	$arr["158"]=true;
 	$arr["159"]=true;
 	$arr["181"]=true;
 	$arr["182"]=true;
 	$arr["183"]=true;
  	$arr["164"]=true;
 	$arr["166"]=true;
 	$arr["169"]=true;
 	$arr["179"]=true;
 	$arr["177"]=true;
 	$arr["171"]=true;
 	$arr["173"]=true;
 	$arr["186"]=true;
 	$arr["187"]=true;
 	$arr["198"]=true;
 	$arr["202"]=true;
 	$arr["211"]=true;
 	$arr["214"]=true;
 	$arr["221"]=true;
 	$arr["222"]=true;
 	$arr["225"]=true;
 	$arr["227"]=true;
 	$arr["229"]=true;
 	$arr["231"]=true;
 	$arr["232"]=true;
 	$arr["247"]=true;
 	$arr["250"]=true;
	$arr["128"]=true;
	$arr["129"]=true;
	$arr["130"]=true;
	$arr["131"]=true;
	$arr["132"]=true;
	$arr["133"]=true;
	$arr["134"]=true;
	$arr["135"]=true;
	$arr["136"]=true;
	$arr["137"]=true;
	$arr["138"]=true;
	$arr["139"]=true;
	$arr["140"]=true;
	$arr["141"]=true;
	$arr["144"]=true;
	$arr["145"]=true;
	$arr["146"]=true;
	$arr["147"]=true;
	$arr["148"]=true;
	$arr["149"]=true;
	$arr["150"]=true;
	$arr["151"]=true;
	$arr["152"]=true;
	$arr["153"]=true;
	$arr["154"]=true;
	$arr["155"]=true;
	$arr["156"]=true;
	$arr["157"]=true;
	$arr["158"]=true;
	$arr["159"]=true;
	$arr["160"]=true;
	$arr["161"]=true;
	$arr["162"]=true;
	$arr["163"]=true;
	$arr["164"]=true;
	$arr["165"]=true;
	$arr["166"]=true;
	$arr["167"]=true;
	$arr["168"]=true;
	$arr["169"]=true;
	$arr["170"]=true;
	$arr["171"]=true;
	$arr["172"]=true;
	$arr["173"]=true;
	$arr["174"]=true;
	$arr["175"]=true;
	$arr["176"]=true;
	$arr["177"]=true;
	$arr["178"]=true;
	$arr["179"]=true;
	$arr["180"]=true;
	$arr["181"]=true;
	$arr["182"]=true;
	$arr["183"]=true;
	$arr["184"]=true;
	$arr["185"]=true;
	$arr["186"]=true;
	$arr["187"]=true;
	$arr["188"]=true;
	$arr["189"]=true;
	$arr["190"]=true;
	$arr["191"]=true;
	$arr["192"]=true;
	$arr["193"]=true;
	$arr["194"]=true;
	$arr["195"]=true;
	$arr["196"]=true;
	$arr["197"]=true;
	$arr["198"]=true;
	$arr["199"]=true;
	$arr["200"]=true;
	$arr["201"]=true;
	$arr["202"]=true;
	$arr["203"]=true;
	$arr["204"]=true;
	$arr["205"]=true;
	$arr["206"]=true;
	$arr["207"]=true;
	$arr["208"]=true;
	$arr["209"]=true;
	$arr["210"]=true;
	$arr["211"]=true;
	$arr["212"]=true;
	$arr["213"]=true;
	$arr["214"]=true;
	$arr["215"]=true;
	$arr["216"]=true;
	$arr["217"]=true;
	$arr["218"]=true;
	$arr["219"]=true;
	$arr["220"]=true;
	$arr["221"]=true;
	$arr["222"]=true;
	$arr["223"]=true;
	$arr["224"]=true;
	$arr["225"]=true;
	$arr["226"]=true;
	$arr["227"]=true;
	$arr["228"]=true;
	$arr["229"]=true;
	$arr["230"]=true;
	$arr["231"]=true;
	$arr["232"]=true;
	$arr["233"]=true;
	$arr["234"]=true;
	$arr["235"]=true;
	$arr["236"]=true;
	$arr["237"]=true;
	$arr["238"]=true;
	$arr["239"]=true;
	$arr["240"]=true;
	$arr["241"]=true;
	$arr["242"]=true;
	$arr["243"]=true;
	$arr["244"]=true;
	$arr["245"]=true;
	$arr["246"]=true;
	$arr["247"]=true;
	$arr["248"]=true;
	$arr["249"]=true; 	
 	
 	$datas=base64_decode($encodedString);
	for($i=0;$i<strlen($datas);$i++){
		$chr=getChr($datas[$i]);
		if($arr[$chr]){
			writelogs("{$datas[$i]} == NOT ENCODED",__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		writelogs("{$datas[$i]}  -> \"".getChr($datas[$i])."\"",__FUNCTION__,__FILE__,__LINE__);
	}
	return true;  
  }	
  
  
function getChr($car){
	for($i=0;$i<256;$i++){
		if($car==chr($i)){return $i;}
	}
}

function IsPhysicalAddress($address){
	$address=strtoupper(trim($address));
	if($address=="UNKNOWN"){return null;}
	if($address=="00:00:00:00:00:00"){return false;}
	$address=str_replace(":","-",$address);
	
	If(strlen($address) > 18){
		writelogs("$address too many caracters, exceed 18 ",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If($address == ""){
		writelogs("$address is null !",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If(!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$address)){
		writelogs("$address did not match pattern ",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	$Array=explode("-",$address);
	If(strlen($Array[0]) != 2){
		writelogs("$address: array 0 did not match 2",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If(strlen($Array[1]) != 2){
		writelogs("$address:array 1 did not match 2",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If(strlen($Array[2]) != 2){
		writelogs("$address:array 2 did not match 2",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If(strlen($Array[3]) != 2){
		writelogs("$address:array 3 did not match 2",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If(strlen($Array[4]) != 2){
		writelogs("$address:array 4 did not match 2",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	If(strlen($Array[5]) != 2){
		writelogs("$address:array 5 did not match 2",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	return true;
}

function getLocalTimezone(){
	if(!class_exists("sockets")){return;}
	$content=trim(@file_get_contents("/etc/timezone"));
	if($content==null){
		$sock=new sockets();
		$content= $sock->GET_INFO("system.php?etc-timezone=yes");
	}
	return $content;
	
}

  function artica_get_memory_usage() {
  	writelogs("memory : "._artica_get_memory_usage());
  	
  }

  function _artica_get_memory_usage() {
        $mem_usage = memory_get_usage(true);
        if ($mem_usage < 1024){return $mem_usage." bytes";}
        if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
        return round($mem_usage/1048576,2)." megabytes";
 		} 	

function format_mysql_table($table){
	$table=str_replace(" ","_",$table);
	$table=str_replace(".","_",$table);
	$table=str_replace("!","_",$table);
	$table=str_replace("\$","_",$table);
	$table=str_replace("%","_",$table);
	$table=str_replace("ø","_",$table);
	$table=str_replace("£","_",$table);
	$table=str_replace("€","_",$table);
	$table=str_replace(",","",$table);
	$table=str_replace(";","",$table);
	$table=str_replace("+","_",$table);
	$table=replace_accents($table);
	return $table;
} 	

function ParseSpecialsCharacters($content){
	$f["%u0413"]="Е";
	$f["%u0435"]="в";
	$f["%u0440"]="г";
	$f["%u0433"]="е";
	$f["%u0435"]="н";
	$f["%u0440"]="и";
	$f["%u0435"]="й";
	$f["%u0439"]="Г";
	$f["%u0434"]="е";
	$f["%u0435"]="р";
	$f["%u0440"]="г";
	$f["%u0415"]="е";
	$f["%u0432"]="р";
	$f["%u0433"]="е";
	$f["%u043D"]="й";
	$f["%u0438"]="д";
	$f["%u0439"]="р";
	while ( list ( $key, $line ) = each ( $f ) ) {$content=str_replace($key,$line,$content);}
	return $content;
	}
 		
function artica_mysql_events($subject,$text,$file,$context){
	$file=basename($file);
	include_once(dirname(__FILE__)."/class.mysql.inc");
	$users=new usersMenus();
	$subject=addslashes($subject);
	$text=addslashes($text);
	$sql="INSERT IGNORE INTO events (zDate,hostname,process,text,context,sended,content)
	VALUES(NOW(),'$users->hostname','$file','$subject','$context','1','$text')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){writelogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
} 	
function numberFormat  ($number  , $decimals = 2 , $dec_point = '.' , $sep = ',', $group=3   ){
    $num = sprintf("%0.{$decimals}f",$number);   
    $num = explode('.',$num);
    
    while (strlen($num[0]) % $group) $num[0]= ' '.$num[0];
    $num[0] = str_split($num[0],$group);
    $num[0] = join($sep[0],$num[0]);
    $num[0] = trim($num[0]);
    $num = @join($dec_point,$num);
   
    return $num;
}

function CompileTrGen($array,$title=null,$rowNumber=5){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}
	$tdcenter=null;
	$trpurc=100/$rowNumber;
	$tables[]="<table style='width:99%' $classform><tr>";
	if($title<>null){$tables[]="<tr><td colspan=$rowNumber>$title</td></tr>";}
	$t=0;
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td width=$trpurc% valign='top'$tdcenter>$line</td>";
		if($t==$rowNumber){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<$rowNumber){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td width=$trpurc% valign='top'$tdcenter>&nbsp;</td>";
		}
	}

	$tables[]="</tr></table>";
	return implode("\n",$tables);
}

function CompileTr5_win($array){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}
	
	
	$tables[]="<table style='width:765px' cellspacing='15px' cellpadding='15px'><tr>";
	
	$t=0;
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td width=25% valign='top'>$line</td>";
		if($t==5){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<5){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td width=25% valign='top'>&nbsp;</td>";
		}
	}

	$tables[]="</tr>
			</table>";
	return implode("\n",$tables);
}
function CompileTr6_win($array){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}


	$tables[]="<table style='width:765px' cellspacing='15px' cellpadding='15px'><tr>";

	$t=0;
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td width=25% valign='top'>$line</td>";
		if($t==6){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<6){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td width=25% valign='top'>&nbsp;</td>";
		}
	}

	$tables[]="</tr>
			</table>";
	return implode("\n",$tables);
}
function CompileTr7_win($array){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}


	$tables[]="<table style='width:765px' cellspacing='15px' cellpadding='15px'><tr>";

	$t=0;
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td width=25% valign='top'>$line</td>";
		if($t==7){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<7){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td width=25% valign='top'>&nbsp;</td>";
		}
	}

	$tables[]="</tr>
			</table>";
	return implode("\n",$tables);
}
function CompileTr4($array,$noform=false,$title=null,$center=false){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}
	$tdcenter=null;
	if(!$noform){$classform="class=form";}
	if($center){$tdcenter=" align='center' ";}
	$tables[]="<table style='width:99%' $classform><tr>";
	if($title<>null){$tables[]="<tr><td colspan=3>$title</td></tr>";}
	$t=0;
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td width=25% valign='top'$tdcenter>$line</td>";
		if($t==4){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<4){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td width=25% valign='top'$tdcenter>&nbsp;</td>";
		}
	}

	$tables[]="</tr></table>";
	return implode("\n",$tables);
}	
function CompileTr5($array,$noform=false,$title=null,$center=false){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}
	$tdcenter=null;
	if(!$noform){$classform="class=form";}
	if($center){$tdcenter=" align='center' ";}
	$tables[]="<table style='width:99%' $classform><tr>";
	if($title<>null){$tables[]="<tr><td colspan=3>$title</td></tr>";}
	$t=0;
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td width=20% valign='top'$tdcenter>$line</td>";
		if($t==5){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<5){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td width=20% valign='top'$tdcenter>&nbsp;</td>";
		}
	}

	$tables[]="</tr></table>";
	return implode("\n",$tables);
}
function CompileTr3($array,$noform=false,$title=null,$center=false){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}
	$classform=null;
	if(!$noform){$classform="class=form";}
	$tdcenter=null;
	if($center){$tdcenter=" align='center' ";}
$tables[]="<table style='width:99%' $classform><tr>";
if($title<>null){$tables[]="<tr><td colspan= $tdcenter>$title</td></tr>";}
$t=0;
while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top' $tdcenter width=33%>$line</td>";
		if($t==3){$t=0;$tables[]="</tr><tr>";}
		
}
if($t<3){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top' width=33%>&nbsp;</td>";				
	}
}
				
	$tables[]="</tr></table>";	
	return implode("\n",$tables);
}

function file_time_min_Web($path){
	if(!is_file($path)){
		if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}
		return 100000;
		}
	 $last_modified = filemtime($path);
	 $data1 = $last_modified;
	 $data2 = time();
	$difference = ($data2 - $data1); 	 
	return round($difference/60);	 
}

function file_time_sec_Web($path){
	if(!is_file($path)){
		if($GLOBALS["VERBOSE"]){echo "file_time_sec_Web() -> unable to stat $path\n";}
		return 100000;
	}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return $difference;
}
function time_between_day_Web($xtime){
	$now = time(); // or your date as well
     $your_date = $xtime;
     $datediff = $your_date - $now ;
     return floor($datediff/(60*60*24));
}

function TCP_LIST_NICS(){
	$datas=explode("\n",@file_get_contents("/proc/net/dev"));
	while (list ($num, $line) = each ($datas) ){
		if($GLOBALS["VERBOSE"]){echo "<br>TCP_LIST_NICS: Found: $line\n";}
		if(preg_match("#^(.+?):#",$line,$re)){
			if(trim($re[1])=="lo"){continue;}
			if(preg_match("#pan[0-9]+#",$re[1])){continue;}
			if(preg_match("#tun[0-9]+#",$re[1])){continue;}
			if(preg_match("#vboxnet[0-9]+#",$re[1])){continue;}
			if(preg_match("#wmaster[0-9]+#",$re[1])){continue;}
			if(preg_match('#master#',$re[1])){continue;}
			if(preg_match("#^veth.+?#",$re[1])){continue;}
			if(preg_match("#^tunl[0-9]+#",$re[1])){continue;}
			if(preg_match("#^dummy[0-9]+#",$re[1])){continue;}
			if(preg_match("#^gre[0-9]+#",$re[1])){continue;}
			if(preg_match("#^ip6tnl[0-9]+#",$re[1])){continue;}
			if(preg_match("#^sit[0-9]+#",$re[1])){continue;}
			if(preg_match("#^vlan[0-9]+#",$re[1])){continue;}
			if(preg_match("#^virt[0-9]+#",$re[1])){continue;}
			$re[1]=trim($re[1]);
			$array[]=trim($re[1]);
		}
	}
	return	$array;

}



function CompileTr2($array,$class=null){
	if(!is_array($array)){return null;}
	if(count($array)==0){return null;}	
	$with=100;
	if(strtolower($class)=="form"){$with=99;}
$tables[]="<table style='width:$with%' class='$class'><tr>";
$t=0;
while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		
}
if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
	$tables[]="</tr></table>";	
	return implode("\n",$tables);
}

function NotifyAdmin($img,$title,$text,$js,$critical=0){
	$zmd5=md5("$img,$title,$text,$js");
	$date=date('Y-m-d H:i:s');
	$js=addslashes($js);
	$title=addslashes($title);
	if(!is_numeric($critical)){$critical=0;}
	$sql="INSERT IGNORE INTO adminevents(zmd5,zDate,subject,text,jslink,icon,critical,enabled)
	VALUES ('$zmd5','$date','$title','$text','$js','$img','$critical',1);";
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/adminevents")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/adminevents",644,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/adminevents/$zmd5",$sql);
}
function quicklinks_paragraphe($img,$title,$text,$link){
	
	$GLOBALS["QUICKLINKS-ITEMS"][]="quicklinks-$title";
	
	if(trim($text)<>null){$text="{{$text}}";}
	
	$html="
	
	<table style='width:205px;margin-top:2px' OnClick=\"javascript:$link\" id='quicklinks-$title' class=QuickLinkTable>
	<tbody>
	<tr>
		<td width=1% valign='top'>". imgtootltip($img,$text)."</td>
		<td style='padding-left:2px;' valign='top' width=99%>
		<div style='font-size:14px;font-weight:bold;letter-spacing:-1px;padding-bottom:3px;border-bottom:1px solid white;margin-bottom:3px'  
		>{{$title}}
		</div>
	</tr>
	</tbody>
	</table>
	
	";
	return $html;
}

function CACHE_SESSION_GET($function,$file,$maxtimeMin=30){
	if(!isset($_SESSION[$file][$function])){return CACHE_SESSION_GETF("$function",$file,$maxtimeMin);}
	$time=$_SESSION[$file][$function]["time"];
	$datas=$_SESSION[$file][$function]["datas"];
	if(!is_numeric($time)){return;}
	if(strlen($datas)==0){return;}
	$distanceInSeconds = round(abs(time() - $time));
	$distanceInMinutes = round($distanceInSeconds / 60);
	if($distanceInMinutes>$maxtimeMin){unset($_SESSION[$file][$function]);return false;}
	echo $datas;
	return true;
	
}
function CACHE_SESSION_GETF($function,$file,$maxtimeMin=30){
	$md5=md5("$file$function{$_SESSION["uid"]}");
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/cache/{$_SESSION["uid"]}.$md5.cache";
	if(!is_file($cachefile)){return false;}
	if(file_get_time_min($cachefile)>$maxtimeMin){@unlink($cachefile);return false;}
	$datas=@file_get_contents($cachefile);
	if(strlen($datas)<5){return false;}
	echo $datas;return true;
}	

function table_error_showZoom($error,$underline=1){
	$full=base64_encode($error);
	if($underline==1){$underline_js="text-decoration:underline";}
	return "<a href=\"javascript:Blurz();\" 
	Onclick=\"javascript:Loadjs('admin.index.php?json-error-js=$full');\" 
	style=\"$underline_js\">$error<a>";
}

function json_error_show($error,$indexColumn=0,$full=false){
	
	$trace=@debug_backtrace();
	if(is_array($trace)){
		$filename=basename($trace[1]["file"]);
		$function=$trace[1]["function"];
		$line=$trace[0]["line"];
	}
	
	$error=str_replace("no data", "{this_request_contains_no_data}", $error);
	$error=str_replace("No data", "{this_request_contains_no_data}", $error);
	$error=str_replace("Not found...", "{this_request_contains_no_data}", $error);
	$error=str_replace("no item", "{this_request_contains_no_data}", $error);
	$error=str_replace("no schedule", "{no_schedule}", $error);
	$error=str_replace("QUERY_SQL","MySQL Error<br>Script:$filename<br>function $function in line $line<br>", $error);
	
	$tpl=new templates();
	$id=md5($error);
	$error=$tpl->javascript_parse_text($error,1);
	$error=str_replace("\n", "<br>", $error);
	if(strlen($error)>10){
		$full=urlencode(base64_encode($error));
		if(!$full){$error="<a href=\"javascript:Blurz();\" Onclick=\"javascript:Loadjs('admin.index.php?json-error-js=$full');\" style=\"font-size:14px;color:#AC0909;text-decoration:underline\">".
		substr($error, 0,10)."...";
		}else{
			$error="<span style=\"font-size:12px;color:#AC0909;\">$error</span>";
		}
		
	}
	if(!is_numeric($indexColumn)){$indexColumn=0;}
	for($i=0;$i<13;$i++){
		$tf[$i]="&nbsp;";
	}
	$tf[$indexColumn]="<strong style=\"font-size:12px;color:#AC0909\">$error</a></strong>";
	for($i=0;$i<13;$i++){
		$finalArray[$i]=$tf[$i];
		
	}
	
	$cellerrornodata=array('id' => 1,'cell' => $finalArray);		
	$data['page'] = 1;$data['total'] = 1;$data['rows'][] = $cellerrornodata;echo json_encode($data);
	die();
	
	
}

function CACHE_SESSION_SET($function,$file,$datas){
	$_SESSION[$file][$function]["time"]=time();
	$_SESSION[$file][$function]["datas"]=$datas;
	$md5=md5("$file$function{$_SESSION["uid"]}");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/$md5.cache", $datas);
	echo $datas;
}

function service_logs_to_table($filename){
		$tpl=new templates();
		$datas=file($filename);
	$html="
	
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
	<thead class='thead'>
		<tr>
		<th>{events}</th>
		</tr>
	</thead>
	<tbody class='tbody'>";	
		
		while (list ($key, $val) = each ($datas) ){
			if(trim($val)==null){continue;}
			if(trim($val=="->")){continue;}
			if(isset($alread[trim($val)])){continue;}
			$alread[trim($val)]=true;
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$val=htmlentities($val);
				$html=$html."
				<tr class=$classtr>
				<td width=99%><code style='font-size:12px'>$val</code></td>
				</tr>
				";
		}
	
	$html=$html."
	</tbody>
	</table>";
	
	return $html;
}

function internal_load(){
	if(!function_exists('sys_getloadavg')){return 0;}
	$array_load=sys_getloadavg();
	return $array_load[0];
}
function SimpleXMLElementToArray(SimpleXMLElement $xml) {
        $array = (array)$xml;

        foreach ( array_slice($array, 0) as $key => $value ) {
            if ( $value instanceof SimpleXMLElement ) {
                $array[$key] = empty($value) ? NULL : SimpleXMLElementToArray($value);
            }
        }
        return $array;
    }
    
    
    function FATAL_INFO_SHOW_128($text,$noform=false,$nomargin=false){
    	$tpl=new templates();
    	$margin=null;
    	$width="95%";
    	$classform="class=form";
    	if($noform){$classform=null;$width="99%";}
    	if(!$nomargin){$margin="margin:30px";}
    	$text=$tpl->_ENGINE_parse_body($text);
    	$html="
    	<center style='$margin'>
    	<div style='width:95%' $classform>
    	<table style='width:{$width}'>
    	<tr>
    	<td valign='top' style='width:1%'>
    	<img src='img/info-128.png'>
    	</td>
    	<td><div style='font-size:18px;color:#2975b8'>$text</div></td>
    	</tr>
    	</table>
    	</div>
    	</center>
    	";
    	return $tpl->_ENGINE_parse_body($html);
    
    
    }    
    
function FATAL_ERROR_SHOW_128($text,$noform=false,$nomargin=false){
	$tpl=new templates();
	$margin=null;
	$width="95%";
	$classform="class=form";
	if($noform){$classform=null;$width="99%";}
	if(!$nomargin){$margin="margin:30px";}
	$text=$tpl->_ENGINE_parse_body($text);
	$html="
	<center style='$margin'>
	<div style='width:95%' $classform>
	<table style='width:{$width}'>
	<tr>
		<td valign='top' style='width:1%'>
			<img src='img/error-128.png'>
		</td>
		<td><div style='font-size:18px;color:#d32d2d'>$text</div></td>
	</tr>
	</table>
	</div>
	</center>
	";
	return $tpl->_ENGINE_parse_body($html);
	
	
}

function FATAL_ERROR_SHOW_128_DESIGN($title,$text,$javascript=null){
	$tpl=new templates();
	$margin=null;
	$width="95%";
	$button=null;
	
	if($javascript<>null){
		$button="<div style='text-align:right;margin-bottom:20px'>".button("{goto}",$javascript,28)."</div>";
		
	}
	
	$text=$tpl->_ENGINE_parse_body($text);
	$html="
	<center style='margin:10px'>
	<div style='width:95%;border: 2px solid #d32d2d;border-radius: 5px 5px 5px 5px;'>
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:140px'>
	<center style='margin:5px'><img src='img/att-red-128.png'></center>
	</td>
	<td style='vertical-align:top;padding:10px'>
		<div style='font-size:28px;color:#d32d2d;font-weight:bold;margin-bottom:20px'>$title</div>
		<div style='font-size:22px;color:#d32d2d;font-weight:bold;margin-bottom:20px'>$text</div>
		$button
	</td>
	</tr>
	</table>
	</div>
	</center>
	";
	return $tpl->_ENGINE_parse_body($html);


}



function SIMPLE_PARAGRAPHE_BLUE_ARROWTR($title,$text,$js,$img="arrow-blue-left-24.png"){
	$link="javascript:blur()";
	if(strpos("  $js", "javasc")==0){
		$link=$js;
		$js=null;
	}else{
		$js="OnClick=\"$js\"";
	}
	
	$tpl=new templates();
	if(preg_match("#noacc:(.+)#", $text,$re)){$text=$re[1];}
	else{
		$text=$tpl->_ENGINE_parse_body("{{$text}}");
	}
	$text=str_replace("%ou", $_SESSION["ou"], $text);
	$title=$tpl->_ENGINE_parse_body("{{$title}}");
	
	return $tpl->_ENGINE_parse_body("<div class=\"span5\"><div class=bs-docs-example>
	<h2>{$title}</h2>
	<p>$text</p>
	<p><a class=\"btn\" href=\"$link\" $js>$title &raquo;</a></p>
	</div></div>");
	
	
	return "		<tr>
		<td valign='top'><img src='img/$img'></td>
		<td valign='top'>
			<a href=\"$link\" $js
			style='text-transform:capitalize;text-decoration:underline;font-weight:bold;font-size:14px'
			>{{$title}}</a>
			<div style='
				font-size:12.5px;
				text-align:justify;
				text-justify: auto;
				line-height:12px !important;
				'>$text</div>
		</td>
		</tr><tr><td colspan=2 style='line-height:auto !important'>&nbsp;</td></tr>";
	
	
}
function events_debug($text=null,$function=null,$line=0){echo "$function::$line $text<br>\n";}
function debug_mem($text=null){
	if(!function_exists("writelogs")){include_once(dirname(__FILE__) . '/logs.inc');}
	
	
	
	$calledscript=null;$functioncript=null;$linescript=null;
	if(!isset($GLOBALS["DEBUG_MEM"])){return;}
	if(!$GLOBALS["DEBUG_MEM"]){return;}
	$memory_get_usage=memory_get_usage(true);
	$ko=round($memory_get_usage/1024,2);
	$trace=debug_backtrace();
	
	$line=$trace[0]["line"];
	$file=basename($trace[0]["file"]);
	if(isset($trace[1]["file"])){$calledscript=basename($trace[1]["file"]);}
	if(isset($trace[1]["line"])){$linescript=basename($trace[1]["line"]);}
	if(isset($trace[1]["function"])){$functioncript=basename($trace[1]["function"]);}
	unset($trace);
	
	if(isset($GLOBALS["DEBUG_INCLUDES"])){
		writelogs("Memory: {$ko}Ko line:$line of $file (called by $calledscript in $linescript func: $functioncript())\n",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	}
	
	
	echo "Memory: {$ko}Ko line:$line of $file (called by $calledscript in $linescript func: $functioncript())\n<br>"; 
	
	
	
	
}

function lic_generate_string($string){
	$ascii=NULL;
	$serial=NULL;
	$secret_num=5;
	for ($i = 0; $i < strlen($string); $i++)
	{
	$ascii .= $secret_num+ ord($string[$i]);
	}
	$ascii=substr($ascii,0,20);
	for ($i = 0; $i < strlen($ascii); $i+=2)
	{
	$string=substr($ascii,$i,2);
	switch($string)
	{
	case $string>122:
	$string-=40;
	break;
	case $string<=48:
	$string+=40;
	break;
	}
	$serial .= chr($string);
	}
	return $serial;
	}
function senderrors($text){sendserrors($text);}
function senderror($text){sendserrors($text);}
function sendserrors($text){
		if(strtolower(trim($text))=="no data"){$text="{this_request_contains_no_data}";}
		if(strtolower(trim($text))=="no rights"){$text="{ERROR_NO_PRIVS}";}
		if(strtolower(trim($text))=="no privs"){$text="{ERROR_NO_PRIVS}";}
		if(strtolower(trim($text))=="no priv"){$text="{ERROR_NO_PRIVS}";}
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<p class=text-error style='margin:20px'>$text</p>");exit();
	}
function build_artica_tabs($tabsArray,$id,$width=0){
	$t=rand(0,time());
	if(!isset($_SESSION["build_artica_tabs_timeout"])){
		$sock=new sockets();
		$_SESSION["build_artica_tabs_timeout"]=$sock->GET_INFO("ArticaTabsTimeout");
		if(!is_numeric($_SESSION["build_artica_tabs_timeout"])){$_SESSION["build_artica_tabs_timeout"]=800;}
	}
	$width_text="100%";
	if($width>0){$width_text="{$width}px";}
	$md=md5(serialize($tabsArray).",$id,$t");
	
	//Stuff cool: $('#$id').tabs({ fx: { opacity: 'toggle' } }).tabs('rotate', 3000);
	
	$html="
		<div id='$id' style='margin-left:0px;min-height:450px;width:$width_text !important'>
			<ul>
			". implode("\n",$tabsArray). "
			</ul>
		</div>
<script>
function tab$md(){
    $.ajaxSetup({
        cache:false

        }); 

    $(function() {
        $( \"#$id\" ).tabs({
        	fx: { opacity: 'toggle' },
            ajaxOptions: {
                success:function(){
                   
                },
                error: function( xhr, status, index, anchor ) {
                	if(xhr.status==0){
                		setTimeout('tab$md()',{$_SESSION["build_artica_tabs_timeout"]});
                	}
                
                    $( anchor.hash ).html(
                        \"Couldn't load this tab, Error \"+xhr.status+ \"[\"+xhr.statusText+\"] We'll try to fix this as soon as possible. \" + \"If this wouldn't be a demo.\" );
                }
            }
        });



    });

}

	function tab2$md(){
	
		var tabAjaxOpts = { 
			beforeSend: function(){ $( anchor.hash ).html('<img src=\"img/wait_verybig_mini_red.gif\">');},
	   	 	success:function(){UnlockPage();}, 
	    	error:function(){UnlockPage();alert('onFail');}
		};

		$('#$id').tabs({
		    fx: { opacity: 'toggle' },
		    select: function () {
		        $(this).tabs(\"option\", { ajaxOptions: tabAjaxOpts});
		    },
		    ajaxOptions: tabAjaxOpts
		});
	
// $('#$id').tabs({ fx: { opacity: 'toggle' } });
	}
		
	setTimeout('tab$md()',{$_SESSION["build_artica_tabs_timeout"]});
</script>
";
	if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
	return $GLOBALS["TPL"]->_ENGINE_parse_body($html);	
	
}
function OutputDebugVerbose($text){
	if(!$GLOBALS["VERBOSE_SYSLOG"]){return;}
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$filename=basename($trace[1]["file"]);
	$function=$trace[1]["function"];
	$line=$trace[1]["line"];
	echo "$filename::".__CLASS__."/$function $text in line $line<br>\n";
}
if(isset($GLOBALS["DEBUG_PROCESS"])){
	if(!function_exists("writelogs")){include_once(dirname(__FILE__) . '/logs.inc');}
	writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
}


function header_location($returnpage){
	if(strpos($_SERVER["HTTP_ACCEPT"], "/javascript")>0){
		header("content-type: application/x-javascript");
		echo "document.location.href='$returnpage';";
		die();
		
	}
	header("location:$returnpage");
	die();
}

function ip2Long2($dottedFormatAddress){
	return  sprintf("%u", ip2long($dottedFormatAddress));
	
}

function strip_bad_characters($servername){
	$servername=replace_accents($servername);
	$servername=str_replace('/', '.', $servername);
	$servername=str_replace('\\', '.', $servername);
	$servername=str_replace(' ', '.', $servername);
	$servername=str_replace('$', '.', $servername);
	$servername=str_replace('!', '.', $servername);
	$servername=str_replace('(', '.', $servername);
	$servername=str_replace(')', '.', $servername);
	$servername=str_replace('[', '.', $servername);
	$servername=str_replace(']', '.', $servername);
	$servername=str_replace('}', '.', $servername);
	$servername=str_replace('{', '.', $servername);
	$servername=str_replace('&', '.', $servername);
	$servername=str_replace('|', '.', $servername);
	$servername=str_replace('^', '.', $servername);
	$servername=str_replace('@', '.', $servername);
	$servername=str_replace('=', '.', $servername);
	$servername=str_replace('+', '.', $servername);
	$servername=str_replace('?', '.', $servername);
	$servername=str_replace('*', '.', $servername);
	$servername=str_replace('#', '.', $servername);
	$servername=str_replace('"', '.', $servername);
	$servername=str_replace('%', '.', $servername);
	$servername=str_replace('*', '.', $servername);
	$servername=str_replace(',', '.', $servername);
	$servername=str_replace(',', '.', $servername);
	$servername=str_replace(';', '.', $servername);
	$servername=str_replace(':', '.', $servername);
	$servername=str_replace('..', '.', $servername);
	$servername=str_replace('..', '.', $servername);
	$servername=str_replace('..', '.', $servername);
	$servername=str_replace('..', '.', $servername);
	$servername=str_replace('...', '.', $servername);
	return $servername;
	
}
function squid_stats_default_values(){
	if(!isset($_SESSION["SQUID_STATS_MEMBER"])){$_SESSION["SQUID_STATS_MEMBER"]="IPADDR";}
	if(!isset($_SESSION["SQUID_STATS_MEMBER_SEARCH"])){$_SESSION["SQUID_STATS_MEMBER_SEARCH"]="*";}
	if(!isset($_SESSION["SQUID_STATS_DATE1"])){$_SESSION["SQUID_STATS_DATE1"]=date("Y-m-d");}
	if(!isset($_SESSION["SQUID_STATS_TIME1"])){$_SESSION["SQUID_STATS_TIME1"]="00:00";}
	if(!isset($_SESSION["SQUID_STATS_DATE2"])){$_SESSION["SQUID_STATS_DATE2"]=date("Y-m-d");}
	if(!isset($_SESSION["SQUID_STATS_TIME2"])){$_SESSION["SQUID_STATS_TIME2"]="23:00";}
	if(!isset($_SESSION["SQUID_STATS_MAX_LINES"])){$_SESSION["SQUID_STATS_MAX_LINES"]=100;}
}

function InfluxToTime($influxtime){
	if(preg_match("#^(.+?)T(.+?)\.[0-9]+Z$#", $influxtime,$re)){return QueryFromUTC(strtotime("{$re[1]} {$re[2]}"));}
	if(preg_match("#^(.+?)T(.+?)Z$#", $influxtime,$re)){return QueryFromUTC(strtotime("{$re[1]} {$re[2]}"));}
	
}

function QueryFromUTC($time){
	if(!isset($GLOBALS["QueryFromUTC"])){$GLOBALS["QueryFromUTC"]=_QueryFromUTC();}
	return strtotime(date("Y-m-d H:i:s",$time)." {$GLOBALS["QueryFromUTC"]} seconds");
	
	
}

function _QueryFromUTC(){
	$UTC_TIME=strtotime(gmdate("Y-m-d H:i:s"));
	$CURRENTIME=time();
	if($CURRENTIME>$UTC_TIME){
		$diff=$CURRENTIME-$UTC_TIME;
		$sep="+";
	}
	if($UTC_TIME>$CURRENTIME){
		$diff=$UTC_TIME-$CURRENTIME;
		$sep="-";
	}
	
	return "$sep$diff";
	
}

function InfluxQueryFromUTC($time){return QueryToUTC($time);}

function QueryToUTC($time){

	$timestamp =$time+date("Z");
	return strtotime(gmdate("Y-m-d H:i:s",$timestamp));

	
}
function is_iterable($var)
{
	return $var !== null
	&& (is_array($var)
			|| $var instanceof Traversable
			|| $var instanceof Iterator
			|| $var instanceof IteratorAggregate
	);
}
?>