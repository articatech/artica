<?php

class template_simple{
	public $SquidHTTPTemplateNoVersion=0;
	public $SquidHTTPTemplateBackgroundColor="#8c1919";
	public $SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
	public $SquidHTTPTemplateFontColor="#FFFFFF";
	public $SquidHTTPTemplateEnablePostmaster=1;
	public $SquidHTTPTemplateDisableHostname;
	public $SquidHTTPTemplateLanguage;
	public $SquidHTTPTemplateNoProxyVersion=0;
	
	public $SquidFTPTemplateNoVersion=0;
	public $SquidFTPTemplateBackgroundColor="#FFFFF";
	public $SquidFTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
	public $SquidFTPTemplateFontColor="#000000";
	public $SquidFTPTemplateEnablePostmaster=1;
	public $SquidFTPTemplateDisableHostname;
	public $SquidFTPTemplateLanguage;	
	public $SquidFTPTemplateSmiley="0040";
	
	
	public $SquidADSTemplateNoVersion=0;
	public $SquidADSTemplateBackgroundColor="#8c1919";
	public $SquidADSTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
	public $SquidADSTemplateFontColor="#FFFFFF";
	public $SquidADSTemplateEnablePostmaster=1;
	public $SquidADSTemplateDisableHostname;
	public $SquidADSTemplateLanguage;
	public $SquidADSTemplateSmiley="2639";
	
	
	public $TITLE=null;
	public $BODY=null;
	public $TEMPLATE_NAME=null;
	public $lang=null;
	public $arrayxLangs=array();
	private $TemplateConfig=array();
	
	
	function __construct($TEMPLATE_NAME=null,$lang=null){
		$this->languages();
		$this->OthersTemplates();
		if($TEMPLATE_NAME<>null){
			$this->TEMPLATE_NAME=$TEMPLATE_NAME;
			$this->lang=$lang;
			$this->loadTemplate();
		}
		
	}
	
	private function languages(){
		$this->arrayxLangs["fr"][]="fr-ca";
		$this->arrayxLangs["fr"][]="fr-mc";
		$this->arrayxLangs["fr"][]="fr-fr";
		$this->arrayxLangs["fr"][]="fr-lu";
		$this->arrayxLangs["fr"][]="fr-be";
		$this->arrayxLangs["fr"][]="fr-ch";
		$this->arrayxLangs["fr"][]="French";
		$this->arrayxLangs["es"][]="es-ve";
		$this->arrayxLangs["es"][]="es-gt";
		$this->arrayxLangs["es"][]="es-ar";
		$this->arrayxLangs["es"][]="es-py";
		$this->arrayxLangs["es"][]="es-cr";
		$this->arrayxLangs["es"][]="es-ni";
		$this->arrayxLangs["es"][]="es-pe";
		$this->arrayxLangs["es"][]="es-pa";
		$this->arrayxLangs["es"][]="es-pr";
		$this->arrayxLangs["es"][]="es-uy";
		$this->arrayxLangs["es"][]="es-sv";
		$this->arrayxLangs["es"][]="es-es";
		$this->arrayxLangs["es"][]="es-bo";
		$this->arrayxLangs["es"][]="es-mx";
		$this->arrayxLangs["es"][]="es-hn";
		$this->arrayxLangs["es"][]="es-cl";
		$this->arrayxLangs["es"][]="es-do";
		$this->arrayxLangs["es"][]="es-co";
		$this->arrayxLangs["es"][]="es-ec";
		
		$this->arrayxLangs["en"][]="en-us";
		$this->arrayxLangs["en"][]="en-au";
		$this->arrayxLangs["en"][]="en-sg";
		$this->arrayxLangs["en"][]="en-nz";
		$this->arrayxLangs["en"][]="en-bz";
		$this->arrayxLangs["en"][]="en-gb";
		$this->arrayxLangs["en"][]="en-ie";
		$this->arrayxLangs["en"][]="en-uk";
		$this->arrayxLangs["en"][]="en-tt";
		$this->arrayxLangs["en"][]="en-jm";
		$this->arrayxLangs["en"][]="en-za";
		$this->arrayxLangs["en"][]="en-ph";
		$this->arrayxLangs["en"][]="en-ca";
		$this->arrayxLangs["en"][]="en-in";
		$this->arrayxLangs["en"][]="en-zw";
		$this->arrayxLangs["en"][]="uk";
		$this->arrayxLangs["en"][]="uk-ua";
		
		$this->arrayxLangs["ar"][]="ar-dz";
		$this->arrayxLangs["ar"][]="ar-tn";
		$this->arrayxLangs["ar"][]="es-ar";
		$this->arrayxLangs["ar"][]="ar-ma";
		$this->arrayxLangs["ar"][]="ar-iq";
		$this->arrayxLangs["ar"][]="ar-ae";
		$this->arrayxLangs["ar"][]="ar-ly";
		$this->arrayxLangs["ar"][]="ar-jo";
		$this->arrayxLangs["ar"][]="ar-sa";
		$this->arrayxLangs["ar"][]="ar-sy";
		$this->arrayxLangs["ar"][]="ar-ye";
		$this->arrayxLangs["ar"][]="ar-eg";
		$this->arrayxLangs["ar"][]="ar-bh";
		$this->arrayxLangs["ar"][]="ar-om";
		$this->arrayxLangs["ar"][]="ar-kw";
		$this->arrayxLangs["ar"][]="ar-qa";
		$this->arrayxLangs["ar"][]="ar-lb";
		$this->arrayxLangs["zh"][]="zh-mo";
		$this->arrayxLangs["zh"][]="zh-hant-mo";
		$this->arrayxLangs["zh"][]="zh-hant-tw";
		$this->arrayxLangs["zh"][]="zh-hans-cn";
		$this->arrayxLangs["zh"][]="zh-hans";
		$this->arrayxLangs["zh"][]="zh-tw";
		$this->arrayxLangs["zh"][]="zh-hant-hk";
		$this->arrayxLangs["zh"][]="zh-cn";
		$this->arrayxLangs["zh"][]="zh-hans-sg";
		$this->arrayxLangs["zh"][]="zh-sg";
		$this->arrayxLangs["zh"][]="zh-hk";
		$this->arrayxLangs["zh"][]="zh-hant";
		$this->arrayxLangs["pt"][]="pt-br";
		$this->arrayxLangs["pt"][]="pt-pt";
		$this->arrayxLangs["pt"][]="pt-bz";
		$this->arrayxLangs["sr"][]="sr-cyrl-rs";
		$this->arrayxLangs["sr"][]="sr-latn-rs";
		$this->arrayxLangs["sr"][]="sr-sp";
		$this->arrayxLangs["sr"][]="sr-cyrl";
		$this->arrayxLangs["sr"][]="sr-latn-me";
		$this->arrayxLangs["sr"][]="sr-rs";
		$this->arrayxLangs["sr"][]="sr-latn-cs";
		$this->arrayxLangs["sr"][]="sr-latn";
		$this->arrayxLangs["sr"][]="sr-cyrl-me";
		$this->arrayxLangs["sr"][]="sr-cyrl-cs";
		$this->arrayxLangs["nl"][]="nl-nl";
		$this->arrayxLangs["pl"][]="pl-pl";
		$this->arrayxLangs["ru"][]="ru-ru";
		$this->arrayxLangs["hu"][]="hu-hu";
		$this->arrayxLangs["id"][]="id-id";
		$this->arrayxLangs["th"][]="th-th";
		$this->arrayxLangs["tr"][]="tr-tr";
		$this->arrayxLangs["vi"][]="vi-vn";
		$this->arrayxLangs["fi"][]="fi-fi";
		$this->arrayxLangs["lt"][]="lt-lt";
		$this->arrayxLangs["lv"][]="lv-lv";
		$this->arrayxLangs["az"][]="az-az";
		$this->arrayxLangs["bg"][]="bg-bg";
		
		$this->arrayxLangs["sl"][]="sl-si";
		$this->arrayxLangs["sk"][]="sk-sk";
		$this->arrayxLangs["hy"][]="hy-am";
		$this->arrayxLangs["hy"][]="hy-armn";
		
		$this->arrayxLangs["it"][]="it-it";
		$this->arrayxLangs["it"][]="it-ch";
		
		$this->arrayxLangs["sv"][]="sv-fi";
		$this->arrayxLangs["sv"][]="sv-se";
		
		$this->arrayxLangs["ro"][]="ro-md";
		$this->arrayxLangs["ro"][]="ro-ro";
		
		$this->arrayxLangs["fa"][]="fa-ir";
		$this->arrayxLangs["fa"][]="fa-fa";
		
		$this->arrayxLangs["ja"][]="ja-jp";
		$this->arrayxLangs["ja"][]="ja-ja";
		
		$this->arrayxLangs["ko"][]="ko-kp";
		$this->arrayxLangs["ko"][]="ko-kr";
		$this->arrayxLangs["ko"][]="ko-ko";
		
		$this->arrayxLangs["et"][]="et-ee";
		$this->arrayxLangs["et"][]="et-et";
		
		$this->arrayxLangs["he"][]="he-il";
		$this->arrayxLangs["he"][]="he-he";
		
		$this->arrayxLangs["ms"][]="ms-my";
		$this->arrayxLangs["ms"][]="ms-ms";
		
		$this->arrayxLangs["de"][]="de-at";
		$this->arrayxLangs["de"][]="de-de";
		$this->arrayxLangs["de"][]="de-lu";
		$this->arrayxLangs["de"][]="de-ch";
		$this->arrayxLangs["de"][]="de-li";
		
		$this->arrayxLangs["el"][]="el-gr";
		$this->arrayxLangs["el"][]="el-el";
		
		$this->arrayxLangs["cs"][]="cs-cz";
		$this->arrayxLangs["cs"][]="cs-cs";
		
		$this->arrayxLangs["da"][]="da-dk";
		$this->arrayxLangs["da"][]="da-da";
	}
	
	private function loadTemplate(){
		$sock=new sockets();
		$SquidHTTPTemplateNoVersion=0;
		$SquidHTTPTemplateBackgroundColor="#8c1919";
		$SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
		$SquidHTTPTemplateFontColor="#FFFFFF";
		
		$SquidFTPTemplateNoVersion=0;
		$SquidFTPTemplateBackgroundColor="#FFFFFF";
		$SquidFTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
		$SquidFTPTemplateFontColor="#000000";
		$SquidFTPTemplateSmiley="0040";
		
		$SquidHTTPTemplateEnablePostmaster=1;
		$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
		$SquidHTTPTemplateNoVersion=intval($sock->GET_INFO("SquidHTTPTemplateNoVersion"));
		$SquidHTTPTemplateBackgroundColor=$sock->GET_INFO("SquidHTTPTemplateBackgroundColor");
		$SquidHTTPTemplateDisableHostname=intval($sock->GET_INFO("SquidHTTPTemplateDisableHostname"));
		$SquidHTTPTemplateEnablePostmaster=$sock->GET_INFO("SquidHTTPTemplateEnablePostmaster");
		$SquidHTTPTemplateFontColor=$sock->GET_INFO("SquidHTTPTemplateFontColor");
		$SquidHTTPTemplateFamily=$sock->GET_INFO("SquidHTTPTemplateFamily");
		$SquidHTTPTemplateNoProxyVersion=intval($sock->GET_INFO("SquidHTTPTemplateNoProxyVersion"));
		
		
		$SquidFTPTemplateEnablePostmaster=1;
		$SquidFTPTemplateNoVersion=intval($sock->GET_INFO("SquidFTPTemplateNoVersion"));
		$SquidFTPTemplateBackgroundColor=$sock->GET_INFO("SquidFTPTemplateBackgroundColor");
		$SquidFTPTemplateDisableHostname=intval($sock->GET_INFO("SquidFTPTemplateDisableHostname"));
		$SquidFTPTemplateEnablePostmaster=$sock->GET_INFO("SquidFTPTemplateEnablePostmaster");
		$SquidFTPTemplateFontColor=$sock->GET_INFO("SquidFTPTemplateFontColor");
		$SquidFTPTemplateFamily=$sock->GET_INFO("SquidFTPTemplateFamily");
		$SquidFTPTemplateSmiley=trim($sock->GET_INFO("SquidFTPTemplateSmiley"));
		
		$SquidADSTemplateEnablePostmaster=1;
		$SquidADSTemplateNoVersion=intval($sock->GET_INFO("SquidADSTemplateNoVersion"));
		$SquidADSTemplateBackgroundColor=$sock->GET_INFO("SquidADSTemplateBackgroundColor");
		$SquidADSTemplateDisableHostname=intval($sock->GET_INFO("SquidADSTemplateDisableHostname"));
		$SquidADSTemplateEnablePostmaster=$sock->GET_INFO("SquidADSTemplateEnablePostmaster");
		$SquidADSTemplateFontColor=$sock->GET_INFO("SquidADSTemplateFontColor");
		$SquidADSTemplateFamily=$sock->GET_INFO("SquidADSTemplateFamily");
		$SquidADSTemplateSmiley=trim($sock->GET_INFO("SquidADSTemplateSmiley"));
		
		
		
		if($SquidHTTPTemplateBackgroundColor==null){$SquidHTTPTemplateBackgroundColor="#8c1919";}
		if($SquidHTTPTemplateFamily==null){$SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
		if(!is_numeric($SquidHTTPTemplateEnablePostmaster)){$SquidHTTPTemplateEnablePostmaster=1;}
		if($SquidHTTPTemplateFontColor==null){$SquidHTTPTemplateFontColor="#FFFFFF";}
		if(!preg_match("#^\##", $SquidHTTPTemplateFontColor)){$SquidHTTPTemplateFontColor="#$SquidHTTPTemplateFontColor";}
		
		$this->SquidHTTPTemplateNoVersion=$SquidHTTPTemplateNoVersion;
		$this->SquidHTTPTemplateNoProxyVersion=$SquidHTTPTemplateNoProxyVersion;
		$this->SquidHTTPTemplateBackgroundColor=$SquidHTTPTemplateBackgroundColor;
		$this->SquidHTTPTemplateFamily=$SquidHTTPTemplateFamily;
		$this->SquidHTTPTemplateFontColor=$SquidHTTPTemplateFontColor;
		$this->SquidHTTPTemplateEnablePostmaster=$SquidHTTPTemplateEnablePostmaster;
		$this->SquidHTTPTemplateDisableHostname=$SquidHTTPTemplateDisableHostname;
		
		
		if($SquidFTPTemplateBackgroundColor==null){$SquidFTPTemplateBackgroundColor="#FFFFF";}
		if($SquidFTPTemplateFamily==null){$SquidFTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
		if(!is_numeric($SquidFTPTemplateEnablePostmaster)){$SquidFTPTemplateEnablePostmaster=1;}
		if($SquidFTPTemplateFontColor==null){$SquidFTPTemplateFontColor="#00000";}
		if(!preg_match("#^\##", $SquidFTPTemplateFontColor)){$SquidFTPTemplateFontColor="#$SquidFTPTemplateFontColor";}
		if($SquidFTPTemplateSmiley==null){$SquidFTPTemplateSmiley="0040";}
		
		
		if($SquidADSTemplateBackgroundColor==null){$SquidADSTemplateBackgroundColor="#8c1919";}
		if($SquidADSTemplateFamily==null){$SquidADSTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
		if(!is_numeric($SquidADSTemplateEnablePostmaster)){$SquidADSTemplateEnablePostmaster=1;}
		if($SquidADSTemplateFontColor==null){$SquidADSTemplateFontColor="#FFFFFF";}
		if(!preg_match("#^\##", $SquidADSTemplateFontColor)){$SquidADSTemplateFontColor="#$SquidADSTemplateFontColor";}
		if($SquidADSTemplateSmiley==null){$SquidADSTemplateSmiley="2639";}
		
		
		$this->SquidFTPTemplateNoVersion=$SquidFTPTemplateNoVersion;
		$this->SquidFTPTemplateBackgroundColor=$SquidFTPTemplateBackgroundColor;
		$this->SquidFTPTemplateFamily=$SquidFTPTemplateFamily;
		$this->SquidFTPTemplateFontColor=$SquidFTPTemplateFontColor;
		$this->SquidFTPTemplateEnablePostmaster=$SquidFTPTemplateEnablePostmaster;
		$this->SquidFTPTemplateDisableHostname=$SquidFTPTemplateDisableHostname;
		$this->SquidFTPTemplateSmiley=$SquidFTPTemplateSmiley;
		
		$this->SquidADSTemplateNoVersion=$SquidADSTemplateNoVersion;
		$this->SquidADSTemplateBackgroundColor=$SquidADSTemplateBackgroundColor;
		$this->SquidADSTemplateFamily=$SquidADSTemplateFamily;
		$this->SquidADSTemplateFontColor=$SquidADSTemplateFontColor;
		$this->SquidADSTemplateEnablePostmaster=$SquidADSTemplateEnablePostmaster;
		$this->SquidADSTemplateDisableHostname=$SquidADSTemplateDisableHostname;
		$this->SquidADSTemplateSmiley=$SquidADSTemplateSmiley;
		
		$this->TemplateConfig=unserialize($sock->GET_INFO("TemplateConfig"));
		
		
		if(!isset($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"])){$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"]=null;}
		if(!isset($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"])){$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"]=null;}
		
		
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"]=$sock->GET_INFO("SquidADSTemplateTitle");
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"]=$sock->GET_INFO("SquidADSTemplateBody");
		
		
		
		if($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"]==null){
			$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"]="Request blocked";
			
		}
		if($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"]==null){
			$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"]="
			<h1>ERROR</h1>
			<h2>Request blocked</h2>
			</div>
			<hr>
			
			<div id=\"content\">
			<p>The following error was encountered while trying to retrieve the URL: <a href=\"@protocol@@hostport@@path@\">@protocol@@hostport@@path@</a></p>
			
			<blockquote id=\"error\">
			<p><b>Access Denied by &laquo;@block-reason@&raquo;</b></p>
			</blockquote>
			
			<p>Access control configuration prevents your request from being allowed at this time.
			Please contact your service provider if you feel this is incorrect.</p>
			
			<p>Your cache administrator is <a href=\"mailto:$cache_mgr_user\">$cache_mgr_user</a>.</p>
			<br>
			</div>
			
			<hr>
			<div id=\"footer\">
			<p>AntiAds Version number @version@</p>
			<!-- %c -->
			</div>";
			
		}

		
		
		if(!isset($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME])){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>Load default content</span><br>\n";}
			$this->defaultContent();
			return;
		}
		
		if($GLOBALS["VERBOSE"]){echo "$this->TEMPLATE_NAME: Build defaults -1- \n";}
		
		$TemplateChoose=$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME];
		
		if(isset($TemplateChoose["SquidHTTPTemplateNoVersion"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateNoVersion"])){
				$this->SquidHTTPTemplateNoVersion=$TemplateChoose["SquidHTTPTemplateNoVersion"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateNoVersion: $this->SquidHTTPTemplateNoVersion\n";}
			}
		}
		
		if(isset($TemplateChoose["SquidHTTPTemplateNoProxyVersion"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateNoProxyVersion"])){
				$this->SquidHTTPTemplateNoVersion=$TemplateChoose["SquidHTTPTemplateNoProxyVersion"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateNoProxyVersion: $this->SquidHTTPTemplateNoProxyVersion\n";}
			}
		}
		
		
		
		if(isset($TemplateChoose["SquidHTTPTemplateEnablePostmaster"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateEnablePostmaster"])){
				$this->SquidHTTPTemplateEnablePostmaster=$TemplateChoose["SquidHTTPTemplateEnablePostmaster"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateEnablePostmaster: $this->SquidHTTPTemplateEnablePostmaster\n";}
			}
		}

		if(isset($TemplateChoose["SquidHTTPTemplateDisableHostname"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateDisableHostname"])){
				$this->SquidHTTPTemplateDisableHostname=$TemplateChoose["SquidHTTPTemplateDisableHostname"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateDisableHostname: $this->SquidHTTPTemplateNoVersion\n";}
			}
		}		
		
		if(isset($TemplateChoose["SquidHTTPTemplateBackgroundColor"])){
			if(trim($TemplateChoose["SquidHTTPTemplateBackgroundColor"])<>null){
				$this->SquidHTTPTemplateBackgroundColor=$TemplateChoose["SquidHTTPTemplateBackgroundColor"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateBackgroundColor: $this->SquidHTTPTemplateBackgroundColor\n";}
			}
		}		
		if(isset($TemplateChoose["SquidHTTPTemplateFamily"])){
			if(trim($TemplateChoose["SquidHTTPTemplateFamily"])<>null){
				$this->SquidHTTPTemplateFamily=$TemplateChoose["SquidHTTPTemplateFamily"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateFamily: $this->SquidHTTPTemplateFamily\n";}
			}
		}	

		if(isset($TemplateChoose["SquidHTTPTemplateFontColor"])){
			if(trim($TemplateChoose["SquidHTTPTemplateFontColor"])<>null){
				$this->SquidHTTPTemplateFontColor=$TemplateChoose["SquidHTTPTemplateFontColor"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateFontColor: $this->SquidHTTPTemplateFontColor\n";}
			}
		}	

		
		

		
		if(!isset($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME])){
			$this->defaultContent();
			return;
		}
		
		if(trim($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"])==null){
			$this->defaultContent();
			return;
		}
			
		if(trim($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"])==null){
			$this->defaultContent();
			return;
		}	

		$this->TITLE=$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"];
		$this->BODY=$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"];
		
	}
	
	public function OthersTemplates(){
		if(isset($GLOBALS["XTPL_SQUID_DEFAULT_OthersTemplates"])){return;}
		$GLOBALS["XTPL_SQUID_DEFAULT_OthersTemplates"]=true;
		if(!isset($GLOBALS["XTPL_SQUID_DEFAULT"])){
			$GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
		}
		$langs=$this->arrayxLangs;
		$langs["en-us"]=true;
		while (list ($lang, $line) = each ($langs)){
			
			
			
			if(!isset($GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["TITLE"])){
				$GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["TITLE"]="ERROR: Blacklisted Website";
			}
			if(!isset($GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["BODY"])){
				$GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["BODY"]="<div id=\"titles\">
			
				<h1>ERROR</h1>
				<h2>Blacklisted Website</h2>
				</div>
				<hr>
				
				<div id=\"content\">
				<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>
				
				<blockquote id=\"error\">
				<p><b>Access Denied by the global policy company</b></p>
				</blockquote>
				
				<p>Access control configuration prevents your request from being allowed at this time.
				Please contact your service provider if you feel this is incorrect.</p>
				
				<p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p>
				<br>
				</div>
				
				<hr>
				<div id=\"footer\">
				<p>Generated %T by %h (%s)</p>
				<!-- %c -->
				</div>";
			}
				
				$f=array();
				$GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PARANOID"]["TITLE"]="ERROR: Banned access";
				$f[]="<div id=\"titles\">";
				$f[]="<h1>Banned Access</h1>";
				$f[]="<h2>The requested URL could not be retrieved</h2>";
				$f[]="</div>";
				$f[]="<hr>";
				$f[]="	";
				$f[]="<div id=\"content\">";
				$f[]="<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>";
				$f[]="	";
				$f[]="<blockquote id=\"error\">";
				$f[]="<p><b>Paranoid mode, banned access to <q>%H</q></b></p>";
				$f[]="</blockquote>";
				$f[]="	";
				$f[]="<p>Error returned:</p>";
				$f[]="<blockquote id=\"data\">";
				$f[]="<pre>- Too many blocking operations - </pre>";
				$f[]="</blockquote>";
				$f[]="	";
				$f[]="<p>This means that too many events has been blocked for your computer or this web site.</p>";
				$f[]="<p>Please contact your Administrator to fix this issue..</p>";
				$f[]="	";
				$f[]="<p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p>";
				$f[]="<br>";
				$f[]="</div>";
				$f[]="	";
				$f[]="<hr>";
				$f[]="<div id=\"footer\">";
				$f[]="<p>Generated %T by %h (%s)</p>";
				$f[]="<!-- %c -->";
				$f[]="</div>";
				$GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PARANOID"]["BODY"]=@implode("\n", $f);				
				
				
		}
		
	}
	
	
	private function defaultContent(){
		if(!isset($GLOBALS["XTPL_SQUID_DEFAULT"])){
			$GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
		}
		



		
		$this->TITLE=utf8_encode(trim($GLOBALS["XTPL_SQUID_DEFAULT"][$this->lang][$this->TEMPLATE_NAME]["TITLE"]));
		$this->BODY=utf8_encode(trim($GLOBALS["XTPL_SQUID_DEFAULT"][$this->lang][$this->TEMPLATE_NAME]["BODY"]));
		
	}
	
	
	public function Save(){
		
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateEnablePostmaster"]=$this->SquidHTTPTemplateEnablePostmaster;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateBackgroundColor"]=$this->SquidHTTPTemplateBackgroundColor;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateDisableHostname"]=$this->SquidHTTPTemplateDisableHostname;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateFamily"]=$this->SquidHTTPTemplateFamily;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateFontColor"]=$this->SquidHTTPTemplateFontColor;
		
		$this->TemplateConfig[$this->lang]["ERR_DIR_LISTING"]["SquidFTPTemplateEnablePostmaster"]=$this->SquidFTPTemplateEnablePostmaster;
		$this->TemplateConfig[$this->lang]["ERR_DIR_LISTING"]["SquidFTPTemplateBackgroundColor"]=$this->SquidFTPTemplateBackgroundColor;
		$this->TemplateConfig[$this->lang]["ERR_DIR_LISTING"]["SquidFTPTemplateDisableHostname"]=$this->SquidFTPTemplateDisableHostname;
		$this->TemplateConfig[$this->lang]["ERR_DIR_LISTING"]["SquidFTPTemplateFamily"]=$this->SquidFTPTemplateFamily;
		$this->TemplateConfig[$this->lang]["ERR_DIR_LISTING"]["SquidFTPTemplateFontColor"]=$this->SquidFTPTemplateFontColor;
		
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["SquidADSTemplateEnablePostmaster"]=$this->SquidADSTemplateEnablePostmaster;
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["SquidADSTemplateBackgroundColor"]=$this->SquidADSTemplateBackgroundColor;
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["SquidADSTemplateDisableHostname"]=$this->SquidADSTemplateDisableHostname;
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["SquidADSTemplateFamily"]=$this->SquidADSTemplateFamily;
		$this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["SquidADSTemplateFontColor"]=$this->SquidADSTemplateFontColor;
				
		
		if($this->TITLE==null){
			$MAINDEF=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
			$this->TITLE=$MAINDEF[$this->lang][$this->TEMPLATE_NAME]["TITLE"];
		}
		if($this->BODY==null){
			$MAINDEF=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
			$this->BODY=$MAINDEF[$this->lang][$this->TEMPLATE_NAME]["BODY"];
		}
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"]=utf8_encode($this->TITLE);
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"]=utf8_encode($this->BODY);
		$sock=new sockets();
		$sock->SaveConfigFile(serialize($this->TemplateConfig),"TemplateConfig");
		$sock->getFrameWork("squid.php?single-templates=yes");
	}
	
	
	public function TemplatesDesign(){
		
		$FOOTER=null;
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}
	
	$sock=new sockets();
	if(!isset($GLOBALS["MICROSOFT_TEMPL"])){
		$GLOBALS["MICROSOFT_TEMPL"]=intval($sock->GET_INFO("SquidTemplatesMicrosoft"));
	}
	
	if($GLOBALS["MICROSOFT_TEMPL"]==1){
		return $this->microsoft_template();
	}
	
	$SquidHTTPTemplateLogoEnable=intval($sock->GET_INFO("SquidHTTPTemplateLogoEnable"));
	
	
	if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");}
	
	$GlobalFontColor=$this->SquidHTTPTemplateFontColor;
	$BackGroundColor=$this->SquidHTTPTemplateBackgroundColor;
	$GlobalFontFamily=$this->SquidHTTPTemplateFamily;
	$SquidHTTPTemplateSmiley=$sock->GET_INFO("SquidHTTPTemplateSmiley");
	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
	
	
	
	
	
	$log1=null;
	$title=$this->TITLE;
	$content=$this->BODY;
	
	if($this->TEMPLATE_NAME=="ERR_DIR_LISTING"){
		$GlobalFontColor=$this->SquidFTPTemplateFontColor;
		$BackGroundColor=$this->SquidFTPTemplateBackgroundColor;
		$GlobalFontFamily=$this->SquidFTPTemplateFamily;
		$SquidFTPTemplateSmiley=$this->SquidFTPTemplateSmiley;
		$SquidHTTPTemplateSmiley=$this->SquidFTPTemplateSmiley;
		$log1="<!-- Use specific FTP template -->";
	}
	if($this->TEMPLATE_NAME=="ERR_ADS_BLOCK"){
		$GlobalFontColor=$this->SquidADSTemplateFontColor;
		$BackGroundColor=$this->SquidADSTemplateBackgroundColor;
		$GlobalFontFamily=$this->SquidADSTemplateFamily;
		$SquidADSTemplateSmiley=$this->SquidADSTemplateSmiley;
		$SquidHTTPTemplateSmiley=$this->SquidADSTemplateSmiley;
		$title=utf8_decode($this->TITLE);
		$content=utf8_decode($this->BODY);
		$log1="<!-- Use specific ADS template -->";
	}	
	

	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<table style='width:75%;border-top:1px solid $GlobalFontColor;margin-top:15px'>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td width=64px><img src='/squid-internal-static/icons/silk/logo-artica-64.png'></td>
		<td style='font-size:14px;padding-left:10px' width=99%>
		You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
		<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
		</td>
		</tr>
		</table>
		</div>";
	}
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	
	$title=utf8_decode($title);
	$content=utf8_decode($content);
	
	$f[]="<head>";
	$f[]="<!-- LOGO: $SquidHTTPTemplateLogoEnable '$this->SquidHTTPTemplateLanguage' - '$this->TEMPLATE_NAME' ".date("Y-m-d H:i:s") ." -->";
	$f[]="$log1";
	$f[]="<title>$title</title>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
	$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
	$f[]="        else";
	$f[]="        {";
	$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"{$BackGroundColor}\";";
		$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            $GlobalFontColor; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GlobalFontFamily}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
	$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";
	
	
	$f[]="    center {";
	$f[]="        color:            $GlobalFontColor; ";
	$f[]="        font-family:      {$GlobalFontFamily}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        12pt; ";
	$f[]="}";
	
	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GlobalFontFamily}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GlobalFontFamily}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	
	$f[]="    #titles h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="hr {
   	border-top: 1px dotted #f00;
  	color: #fff;
  	background-color: #fff;
  	height: 1px;
 	 width:50%;
}
";
	
	$f[]="    #content p {";
	$f[]="       font-size:  11pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="    #footer p {";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";	

	
	$f[]="    #data pre{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        font-weight: bold;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #data pre:before{content: \"\\275D\";margin-right:5px;font-size:22pt}";
	$f[]="    #data pre:after{content: \"\\275E\";margin-left:5px;font-size:22pt}";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\{$SquidHTTPTemplateSmiley}\";}";
	
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: $GlobalFontColor; ";
		$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: $GlobalFontColor; ";
		$f[]="    }";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	$f[]="<!-- LOGO -->";
	if($SquidHTTPTemplateLogoEnable==1){
		$SquidHTTPTemplateLogoPath=$sock->GET_INFO("SquidHTTPTemplateLogoPath");
		$basename=basename($SquidHTTPTemplateLogoPath);
		
		$SquidHTTPTemplateLogoPositionH=$sock->GET_INFO("SquidHTTPTemplateLogoPositionH");
		$SquidHTTPTemplateLogoPositionL=$sock->GET_INFO("SquidHTTPTemplateLogoPositionL");
		$SquidHTTPTemplateLogoPicturemode=$sock->GET_INFO("SquidHTTPTemplateLogoPicturemode");
		$SquidHTTPTemplateLogoPictureAlign=$sock->GET_INFO("SquidHTTPTemplateLogoPictureAlign");
		if($SquidHTTPTemplateLogoPictureAlign==null){$SquidHTTPTemplateLogoPictureAlign="absolute";}
		$widthDiv="100%";
		$heightDiv=null;
		
		list($width, $height, $type, $attr) = getimagesize($SquidHTTPTemplateLogoPath);
		$heightDiv="height:{$height}px;";
		$background="background-position:{$SquidHTTPTemplateLogoPositionL}% {$SquidHTTPTemplateLogoPositionH}%;";
		
		if($SquidHTTPTemplateLogoPicturemode=="absolute"){
			$widthDiv="{$width}px";
			$background=null;
		}
		
		if($SquidHTTPTemplateLogoPictureAlign<>null){
			$align="text-align:$SquidHTTPTemplateLogoPictureAlign;";
		}
		
		
		
		$f[]="<div style='position:{$SquidHTTPTemplateLogoPictureAlign};{$align}width:{$widthDiv};$heightDiv
		background-image:url(\"/squid-internal-static/icons/silk/$basename\");
		background-repeat:no-repeat;$background
		left:{$SquidHTTPTemplateLogoPositionL};
		top:{$SquidHTTPTemplateLogoPositionL};
		'
		>&nbsp;</div>
		";
		
	}
	
	
	
	
	$f[]="<!-- LOGO END -->";
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <div id=\"info\">";
	
	if($this->SquidHTTPTemplateNoProxyVersion==1){
		$content=str_ireplace("<p>Generated %T by %h (%s)</p>","<!-- <p>Generated %T by %h (%s)</p> -->",$content);
	}
	
	$f[]="$content";
	
	
	if($this->SquidHTTPTemplateNoVersion==0){
		$f[]="<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
	}
	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	return @implode("\n", $f);
	}
	
	function microsoft_template(){
		if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");}
		if(!isset($GLOBALS["CORP_LICENSE"])){
			$users=new usersMenus();
			$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
		}
		
		if(!$GLOBALS["CORP_LICENSE"]){
			$FOOTER="
			<UL class=\"tasks\" id=\"cantDisplayTasks\">
			<LI id=\"task1-1\">You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.</LI>
			<LI id=\"task1-1\">Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</LI>
			</UL>";

		}
		
		$title=$this->TITLE;
		$title=str_replace("ERROR:", "",$title);
		$title=str_replace("ERREUR:", "",$title);
		$content=$this->BODY;
		
		if($this->SquidHTTPTemplateNoProxyVersion==1){
			$content=str_ireplace("<p>Generated %T by %h (%s)</p>","<!-- <p>Generated %T by %h (%s)</p> -->",$content);
		}
		
		
		$title=utf8_decode($title);
		$content=utf8_decode($content);
		
			$f[]="<!DOCTYPE HTML>";
			$f[]="<HTML>";
			$f[]="<HEAD>";
			$f[]="<META content=\"IE=11.0000\" http-equiv=\"X-UA-Compatible\">";
			$f[]="<META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">         ";
			$f[]="<title>$title</title>";
			$f[]="<style type=\"text/css\">     ";
			$f[]="body {";
			$f[]="    background-repeat: repeat-x;";
			$f[]="    background-color: white;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\", \"arial\";";
			$f[]="    margin: 0em;";
			$f[]="    color: #575757;";
			$f[]="}";
			$f[]="H1,H2,HR{
					display:none;
					
					}";
			$f[]="";
			$f[]=".mainContent {";
			$f[]="    margin-top: 80px;";
			$f[]="    width: 700px;";
			$f[]="    margin-left: 120px;";
			$f[]="    margin-right: 120px;";
			$f[]="}";
			$f[]="";
			$f[]="";
			$f[]="";
			$f[]=".title {";
			$f[]="    color: #2778ec;";
			$f[]="    font-size: 38pt;";
			$f[]="    font-weight: 300;";
			$f[]="    vertical-align: bottom;";
			$f[]="    margin-bottom: 20px;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    position: relative;";
			$f[]="}";
			$f[]="";
			$f[]=".taskSection {";
			$f[]="    margin-top: 20px;";
			$f[]="    margin-bottom: 40px;";
			$f[]="    position: relative;";
			$f[]="}";
			$f[]="";
			$f[]=".tasks {";
			$f[]="    color: #000000;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: 200;";
			$f[]="    font-size: 12pt;";
			$f[]="    padding-top: 5px;";
			$f[]="}";
			$f[]="blockquote {";
			$f[]="    color: #000000;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: 200;";
			$f[]="    font-size: 12pt;";
			$f[]="    margin-top: -10px;";
			$f[]="}";
			$f[]="";
			$f[]="P,pre {";
			$f[]="    color: #000000;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: 200;";
			$f[]="    font-size: 12pt;";
			$f[]="    padding-top: 5px;";
			$f[]="}";
			$f[]="#footer p {";
			$f[]="    color: #CCCCCC;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: normal;";
			$f[]="    font-size: 10pt;";
			$f[]="    padding-top: 5px;";
			$f[]="}";
			
			$f[]="li {";
			$f[]="    margin-top: 8px;";
			$f[]="}";
			$f[]="";
			$f[]=".diagnoseButton {";
			$f[]="    outline: none;";
			$f[]="    font-size: 9pt;";
			$f[]="}";
			$f[]="</style>";
			$f[]="";
			$f[]="";
			$f[]="";

			$f[]="        ";
			$f[]="<SCRIPT language=\"javascript\" type=\"text/javascript\">";
			$f[]="";
			$f[]="function isExternalUrlSafeForNavigation(urlStr)";
			$f[]="{";
			$f[]="var regEx = new RegExp(\"^(http(s?)|ftp|file)://\", \"i\");";
			$f[]="return regEx.exec(urlStr);";
			$f[]="}";
			$f[]="function clickRefresh()";
			$f[]="{";
			$f[]="var location = window.location.href;";
			$f[]="var poundIndex = location.indexOf('#');";
			$f[]="if (poundIndex != -1 && poundIndex+1 < location.length && isExternalUrlSafeForNavigation(location.substring(poundIndex+1)))";
			$f[]="{";
			$f[]="window.location.replace(location.substring(poundIndex+1));";
			$f[]="}";
			$f[]="}";
			$f[]="function navCancelInit()";
			$f[]="{";
			$f[]="var location = window.location.href;";
			$f[]="var poundIndex = location.indexOf('#');";
			$f[]="if (poundIndex != -1 && poundIndex+1 < location.length && isExternalUrlSafeForNavigation(location.substring(poundIndex+1)))";
			$f[]="{";
			$f[]="var bElement = document.createElement(\"A\");";
			$f[]="bElement.innerText = L_REFRESH_TEXT;";
			$f[]="bElement.href = 'javascript:clickRefresh()';";
			$f[]="navCancelContainer.appendChild(bElement);";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="var textNode = document.createTextNode(L_RELOAD_TEXT);";
			$f[]="navCancelContainer.appendChild(textNode);";
			$f[]="}";
			$f[]="}";
			$f[]="function expandCollapse(elem, changeImage)";
			$f[]="{";
			$f[]="if (document.getElementById)";
			$f[]="{";
			$f[]="ecBlock = document.getElementById(elem);";
			$f[]="if (ecBlock != undefined && ecBlock != null)";
			$f[]="{";
			$f[]="if (changeImage)";
			$f[]="{";
			$f[]="elemImage = document.getElementById(elem + \"Image\");";
			$f[]="}";
			$f[]="if (!changeImage || (elemImage != undefined && elemImage != null))";
			$f[]="{";
			$f[]="if (ecBlock.currentStyle.display == \"none\" || ecBlock.currentStyle.display == null || ecBlock.currentStyle.display == \"\")";
			$f[]="{";
			$f[]="ecBlock.style.display = \"block\";";
			$f[]="if (changeImage)";
			$f[]="{";
			$f[]="elemImage.src = \"up.png\";";
			$f[]="}";
			$f[]="}";
			$f[]="else if (ecBlock.currentStyle.display == \"block\")";
			$f[]="{";
			$f[]="ecBlock.style.display = \"none\";";
			$f[]="if (changeImage)";
			$f[]="{";
			$f[]="elemImage.src = \"down.png\";";
			$f[]="}";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="ecBlock.style.display = \"block\";";
			$f[]="if (changeImage)";
			$f[]="{";
			$f[]="elemImage.src = \"up.png\";";
			$f[]="}";
			$f[]="}";
			$f[]="}";
			$f[]="}";
			$f[]="}";
			$f[]="}";
			$f[]="function initHomepage()";
			$f[]="{";
			$f[]="DocURL=document.location.href;";
			$f[]="var poundIndex = DocURL.indexOf('#');";
			$f[]="if (poundIndex != -1 && poundIndex+1 < location.length && isExternalUrlSafeForNavigation(location.substring(poundIndex+1)))";
			$f[]="{";
			$f[]="protocolIndex=DocURL.indexOf(\"://\", 4);";
			$f[]="serverIndex=DocURL.indexOf(\"/\", protocolIndex + 3);";
			$f[]="BeginURL=DocURL.indexOf(\"#\",1) + 1;";
			$f[]="urlresult=DocURL.substring(BeginURL, serverIndex);";
			$f[]="if (protocolIndex - BeginURL > 7)";
			$f[]="urlresult=\"\";";
			$f[]="displayresult=DocURL.substring(protocolIndex + 3, serverIndex);";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="displayresult = \"\";";
			$f[]="urlresult = \"\";";
			$f[]="}";
			$f[]="var aElement = document.createElement(\"A\");";
			$f[]="aElement.innerText = displayresult;";
			$f[]="aElement.href = urlresult;";
			$f[]="homepageContainer.appendChild(aElement);";
			$f[]="}";
			$f[]="function initConnectionStatus()";
			$f[]="{";
			$f[]="if (navigator.onLine)";
			$f[]="{";
			$f[]="checkConnection.innerText = L_CONNECTION_ON_TEXT;";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="checkConnection.innerText = L_CONNECTION_OFF_TEXT;";
			$f[]="}";
			$f[]="}";
			$f[]="function initGoBack()";
			$f[]="{";
			$f[]="if (history.length < 1)";
			$f[]="{";
			$f[]="var textNode = document.createTextNode(L_GOBACK_TEXT);";
			$f[]="goBackContainer.appendChild(textNode);";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="var bElement = document.createElement(\"A\");";
			$f[]="bElement.innerText = L_GOBACK_TEXT ;";
			$f[]="bElement.href = \"javascript:history.back();\";";
			$f[]="goBackContainer.appendChild(bElement);";
			$f[]="}";
			$f[]="}";
			$f[]="function initMoreInfo(infoBlockID)";
			$f[]="{";
			$f[]="var bElement = document.createElement(\"A\");";
			$f[]="bElement.innerText = L_MOREINFO_TEXT;";
			$f[]="bElement.href = \"javascript:expandCollapse(\'infoBlockID\', true);\";";
			$f[]="moreInfoContainer.appendChild(bElement);";
			$f[]="}";
			$f[]="function initOfflineUser(offlineUserID)";
			$f[]="{";
			$f[]="var bElement = document.createElement(\"A\");";
			$f[]="bElement.innerText = L_OFFLINE_USERS_TEXT;";
			$f[]="bElement.href = \"javascript:expandCollapse('offlineUserID', true);\";";
			$f[]="offlineUserContainer.appendChild(bElement);";
			$f[]="}";
			$f[]="function initUnframeContent()";
			$f[]="{";
			$f[]="var location = window.location.href;";
			$f[]="var poundIndex = location.indexOf('#');";
			$f[]="if (poundIndex != -1 && poundIndex+1 < location.length && isExternalUrlSafeForNavigation(location.substring(poundIndex+1)))";
			$f[]="{";
			$f[]="document.getElementById(\"whatToDoIntro\").style.display=\"\";";
			$f[]="document.getElementById(\"whatToDoBody\").style.display=\"\";";
			$f[]="}";
			$f[]="}";
			$f[]="function removeNoScriptElements() {";
			$f[]="var noScriptElements = document.getElementsByTagName(\"noscript\");";
			$f[]="for (var i = noScriptElements.length - 1; i >= 0; i--)";
			$f[]="{";
			$f[]="var bElement = noScriptElements[i];";
			$f[]="if (bElement !== undefined && bElement !== null)";
			$f[]="{";
			$f[]="bElement.removeNode(true);";
			$f[]="}";
			$f[]="}";
			$f[]="}";
			$f[]="function makeNewWindow()";
			$f[]="{";
			$f[]="var location = window.location.href;";
			$f[]="var poundIndex = location.indexOf('#');";
			$f[]="if (poundIndex != -1 && poundIndex+1 < location.length && isExternalUrlSafeForNavigation(location.substring(poundIndex+1)))";
			$f[]="{";
			$f[]="window.open(location.substring(poundIndex+1));";
			$f[]="}";
			$f[]="}";
			$f[]="function setTabInfo(tabInfoBlockID)";
			$f[]="{";
			$f[]="var bPrevElement = document.getElementById(\"tabInfoTextID\");";
			$f[]="var bPrevImage = document.getElementById(\"tabInfoBlockIDImage\");";
			$f[]="if (bPrevElement != null)";
			$f[]="{";
			$f[]="tabInfoContainer.removeChild(bPrevElement);";
			$f[]="}";
			$f[]="if (bPrevImage != null)";
			$f[]="{";
			$f[]="tabImageContainer.removeChild(bPrevImage);";
			$f[]="}";
			$f[]="var bElement = document.createElement(\"A\");";
			$f[]="var bImageElement = document.createElement(\"IMG\");";
			$f[]="var ecBlock = document.getElementById(tabInfoBlockID);";
			$f[]="if ((ecBlock != undefined && ecBlock != null) &&";
			$f[]="(ecBlock.currentStyle.display == \"none\" || ecBlock.currentStyle.display == null || ecBlock.currentStyle.display == \"\"))";
			$f[]="{";
			$f[]="bElement.innerText = L_SHOW_HOTKEYS_TEXT;";
			$f[]="bImageElement.alt = L_SHOW_HOTKEYS_TEXT;";
			$f[]="bImageElement.src=\"down.png\";";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="bElement.innerText = L_HIDE_HOTKEYS_TEXT;";
			$f[]="bImageElement.alt = L_HIDE_HOTKEYS_TEXT;";
			$f[]="bImageElement.src=\"up.png\";";
			$f[]="}";
			$f[]="bElement.id = \"tabInfoTextID\";";
			$f[]="bElement.href = \"javascript:expandCollapse(\'tabInfoBlockID\', false); setTabInfo('tabInfoBlockID');\";";
			$f[]="bImageElement.id=\"tabInfoBlockIDImage\";";
			$f[]="bImageElement.border=\"0\";";
			$f[]="bImageElement.className=\"actionIcon\";";
			$f[]="tabInfoContainer.appendChild(bElement);";
			$f[]="tabImageContainer.appendChild(bImageElement);";
			$f[]="}";
			$f[]="function launchInternetOptions()";
			$f[]="{";
			$f[]="window.external.msLaunchInternetOptions();";
			$f[]="}";
			$f[]="function diagnoseConnection()";
			$f[]="{";
			$f[]="window.external.DiagnoseConnection();";
			$f[]="}";
			$f[]="function diagnoseConnectionAndRefresh()";
			$f[]="{";
			$f[]="window.external.DiagnoseConnection();";
			$f[]="if (navigator.onLine)";
			$f[]="{";
			$f[]="clickRefresh();";
			$f[]="}";
			$f[]="}";
			$f[]="function getInfo()";
			$f[]="{";
			$f[]="checkConnection();";
			$f[]="if (document.addEventListener)";
			$f[]="{";
			$f[]="addEventListener(\"offline\", reportConnectionEvent, false);";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="attachEvent(\"onoffline\", reportConnectionEvent);";
			$f[]="}";
			$f[]="document.body.ononline = reportConnectionEvent;";
			$f[]="document.body.onoffline = reportConnectionEvent;";
			$f[]="}";
			$f[]="function checkConnection()";
			$f[]="{";
			$f[]="var newHeading = document.getElementById(\"mainTitle\");";
			$f[]="var notConnectedTasks = document.getElementById(\"notConnectedTasks\");";
			$f[]="var cantDisplayTasks = document.getElementById(\"cantDisplayTasks\");";
			$f[]="}";
			$f[]="function reportConnectionEvent(e)";
			$f[]="{";
			$f[]="if (!e) e = window.event;";
			$f[]="if ('online' == e.type)";
			$f[]="{";
			$f[]="setTimeout ( \"clickRefresh()\", 1000 );";
			$f[]="}";
			$f[]="else if ('offline' == e.type)";
			$f[]="{";
			$f[]="checkConnection();";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="checkConnection();";
			$f[]="}";
			$f[]="}";
			$f[]="function addURL()";
			$f[]="{";
			$f[]="var urlResult = \"\";";
			$f[]="var DocURL = document.location.href;";
			$f[]="var urlPlaceholder = document.getElementById(\"webpage\");";
			$f[]="var beginIndex = DocURL.indexOf('#') + 1;";
			$f[]="if (DocURL.indexOf(\"file://\", beginIndex) == -1)";
			$f[]="{";
			$f[]="var protocolEndIndex = DocURL.indexOf(\"://\", beginIndex);";
			$f[]="var endIndex=DocURL.indexOf(\"/\", protocolEndIndex + 3);";
			$f[]="urlResult = DocURL.substring(beginIndex, endIndex);";
			$f[]="}";
			$f[]="urlPlaceholder.innerText = urlResult + \" \";";
			$f[]="}";
			$f[]="function addDomainName()";
			$f[]="{";
			$f[]="var domainNamePlaceholder = document.getElementById(\"DomainName\");";
			$f[]="domainNamePlaceholder.innerText = findValue(\"DomainName=\") + \" \";";
			$f[]="}";
			$f[]="function addProxyDetail()";
			$f[]="{";
			$f[]="var proxyDetailPlaceholder = document.getElementById(\"ProxyDetail\");";
			$f[]="proxyDetailPlaceholder.innerText = findValue(\"ProxyDetail=\");";
			$f[]="}";
			$f[]="function findValue(key)";
			$f[]="{";
			$f[]="var value = '';";
			$f[]="DocQuery = document.location.search;";
			$f[]="BeginString = DocQuery.indexOf(key);";
			$f[]="if (BeginString > 0)";
			$f[]="{";
			$f[]="BeginString += key.length;";
			$f[]="EndString = Math.max(0, Math.min(DocQuery.indexOf(\"&\", BeginString), DocQuery.indexOf(\"#\", BeginString)));";
			$f[]="if (EndString > 0)";
			$f[]="{";
			$f[]="value = DocQuery.substring(BeginString, EndString);";
			$f[]="}";
			$f[]="else";
			$f[]="{";
			$f[]="value = DocQuery.substring(BeginString);";
			$f[]="}";
			$f[]="}";
			$f[]="return value;";
			$f[]="}";
			$f[]="function isHTTPS(cantDisplayTasks)";
			$f[]="{";
			$f[]="var DocURL = document.location.href;";
			$f[]="var poundIndex = DocURL.indexOf('#');";
			$f[]="var protocolIndex = DocURL.indexOf(\"https://\", poundIndex);";
			$f[]="if (protocolIndex>poundIndex)";
			$f[]="{";
			$f[]="var bElement = document.createElement(\"li\");";
			$f[]="bElement.textContent = L_TLS_SSL_TEXT;";
			$f[]="cantDisplayTasks.appendChild(bElement);";
			$f[]="}";
			$f[]="}";
			$f[]="";
			$f[]="</SCRIPT>";
			$f[]="     ";
			$f[]="<META name=\"GENERATOR\" content=\"MSHTML 11.00.9600.17801\"></HEAD>     ";
			$f[]="<BODY onload=\"javascript:getInfo();\">";
			$f[]="<DIV class=\"mainContent\" id=\"contentContainer\">";
			$f[]="<DIV class=\"title\" id=\"mainTitle\">$title</DIV>";
			$f[]="<DIV class=\"taskSection\" id=\"taskSection\">";
			$f[]="$content $FOOTER</DIV>";
			$f[]="</DIV>";
			$f[]="</BODY>";
			$f[]="</HTML>";
			return @implode("\n", $f);
		
		
	}
	
}