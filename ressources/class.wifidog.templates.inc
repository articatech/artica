<?php
class wifidog_templates{
	private $head_content;
	private $ruleid;
	private $headerAdd;
	public $MainTitle;
	public $FORM_HEAD;
	public $backgroundColor=null;
	public $FontColor=null;
	public $TitleColor=null;
	public $TitleFontSize=0;
	public $FontFamily=null;
	public $FontSize=null;
	public $FormStyle=null;
	public $FieldsStyle=null;
	public $LegendsStyle=null;
	public $Button2014Bgcolor=null;
	public $Button2014BgcolorOver=null;
	public $Button2014BgcolorBorder=null;
	public $Button2014FontSize=null;
	public $ButtonFontColor=null;
	public $SpacerButton=0;
	public $SizePage=0;
	public $MarginPage=0;
	public $BackgroundRepeat=null;
	public $WelcomeMessage=null;
	public $TERMS_EXPLAIN=null;
	public $TERMS_CONDITIONS=null;
	public $REGISTER_MESSAGE_EXPLAIN=null;
	public $REGISTER_MESSAGE_SUCCESS=null;
	public $REGISTER_MESSAGE=null;
	public $REGISTER_GENERIC_PASSWORD=0;
	public $REGISTER_GENERIC_LABEL=null;
	public $REGISTER_GENERIC_PASSTXT=null;
	public $REGISTER_GENERIC_PASSERR=null;
	public $REGISTER_SUBJECT=null;
	public $CONFIRM_MESSAGE=null;
	public $RegisterTitle=null;
	public $SubTitleFontSize=0;
	public $LostPasswordLink=null;
	public $LinksColor=null;
	public $TextErrorStyle=null;
	public $ConnectionButton=null;
	public $ArticaSplashHotSpotRedirectText=null;
	public $LabelUsername=null;
	public $LabelPassword=null;
	public $SMS_SMTP_SUBJECT=null;
	public $SMS_SMTP_BODY=null;
	public $SMS_CONFIRM_MESSAGE=null;
	public $SMS_BUTTON=null;
	public $SMS_INTRO=null;
	public $SMS_FIELD=null;
	public $SMS_FONT_SIZE=0;
	public $BackgroundTOP=0;
	public $BackgroundBottom=0;
	public $FooterText=null;
	public $RECOVER_MESSAGE=null;
	public $FormFontColor=null;
	function wifidog_templates($ruleid,$headerAdd=null){
		$this->ruleid=$ruleid;
		$this->headerAdd=$headerAdd;
		$this->default_values();
		
	}
	
	
	function default_values(){
			$this->MainTitle="HotSpot System";
			$tpl=new templates();
			$sock=new wifidog_settings($this->ruleid);
			$this->WelcomeMessage=utf8_decode($sock->GET_INFO("WelcomeMessage"));
			$this->RegisterTitle=$sock->GET_INFO("RegisterTitle");
			
			
			$this->backgroundColor=$sock->GET_INFO("backgroundColor");
			$this->FontColor=$sock->GET_INFO("FontColor");
			$this->TitleColor=$sock->GET_INFO("TitleColor");
			$this->TitleFontSize=intval($sock->GET_INFO("TitleFontSize"));
			$this->FontFamily=intval($sock->GET_INFO("FontFamily"));
			$this->FontSize=intval($sock->GET_INFO("FontSize"));
			$this->FormStyle=$sock->GET_INFO("FormStyle");
			$this->FieldsStyle=$sock->GET_INFO("FieldsStyle");
			$this->LegendsStyle=$sock->GET_INFO("LegendsStyle");
			$this->Button2014Bgcolor=$sock->GET_INFO("Button2014Bgcolor");
			$this->Button2014BgcolorOver=$sock->GET_INFO("Button2014BgcolorOver");
			$this->Button2014BgcolorBorder=$sock->GET_INFO("Button2014BgcolorBorder");
			$this->Button2014FontSize=intval($sock->GET_INFO("Button2014FontSize"));
			$this->SpacerButton=intval($sock->GET_INFO("SpacerButton"));
			$this->SubTitleFontSize=intval($sock->GET_INFO("SubTitleFontSize"));
			$this->LinksColor=$sock->GET_INFO("LinksColor");
			$this->BackgroundTOP=intval($sock->GET_INFO("BackgroundTOP"));
			$this->BackgroundBottom=intval($sock->GET_INFO("BackgroundBottom"));
			$this->ButtonFontColor=$sock->GET_INFO("ButtonFontColor");
			$this->SizePage=intval($sock->GET_INFO("SizePage"));
			$this->MarginPage=intval($sock->GET_INFO("MarginPage"));
			$this->BackgroundRepeat=$sock->GET_INFO("BackgroundRepeat");
			$this->ConnectionButton=$sock->GET_INFO("ConnectionButton");
			$this->FooterText=$sock->GET_INFO("FooterText");
			$this->FormFontColor=$sock->GET_INFO("FormFontColor");
			
			$this->LabelUsername=utf8_decode($sock->GET_INFO("LabelUsername"));
			$this->LabelPassword=utf8_decode($sock->GET_INFO("LabelPassword"));
			
			$this->TERMS_EXPLAIN=$sock->GET_INFO("TERMS_EXPLAIN");
			$this->TERMS_CONDITIONS=utf8_decode($sock->GET_INFO("TERMS_CONDITIONS"));
			$this->REGISTER_MESSAGE_EXPLAIN=$sock->GET_INFO("REGISTER_MESSAGE_EXPLAIN");
			$this->REGISTER_MESSAGE_SUCCESS=$sock->GET_INFO("REGISTER_MESSAGE_SUCCESS");
			
			$this->REGISTER_MESSAGE=$sock->GET_INFO("REGISTER_MESSAGE");
			$this->REGISTER_SUBJECT=$sock->GET_INFO("REGISTER_SUBJECT");
			$this->LostPasswordLink=$sock->GET_INFO("LostPasswordLink");
			$this->CONFIRM_MESSAGE=$sock->GET_INFO("CONFIRM_MESSAGE");
		
			$this->SMS_SMTP_SUBJECT=$sock->GET_INFO("SMS_SMTP_SUBJECT");
			$this->SMS_SMTP_BODY=$sock->GET_INFO("SMS_SMTP_BODY");
			$this->SMS_INTRO=$sock->GET_INFO("SMS_INTRO");
			$this->SMS_BUTTON=$sock->GET_INFO("SMS_BUTTON");
			$this->SMS_FIELD=$sock->GET_INFO("SMS_FIELD");	
			$this->SMS_FONT_SIZE=intval($sock->GET_INFO("SMS_FONT_SIZE"));
			$this->SMS_CODE_ERROR=$sock->GET_INFO("SMS_CODE_ERROR");
			$this->FORM_HEAD=$sock->GET_INFO("FORM_HEAD");
			
			$this->REGISTER_GENERIC_PASSWORD=intval($sock->GET_INFO("REGISTER_GENERIC_PASSWORD"));
			$this->REGISTER_GENERIC_LABEL=$sock->GET_INFO("REGISTER_GENERIC_LABEL");
			$this->REGISTER_GENERIC_PASSTXT=$sock->GET_INFO("REGISTER_GENERIC_PASSTXT");
			$this->REGISTER_GENERIC_PASSERR=$sock->GET_INFO("REGISTER_GENERIC_PASSERR");
			
			$this->ArticaSplashHotSpotRedirectText=utf8_decode($sock->GET_INFO("ArticaSplashHotSpotRedirectText"));
			
			if($this->REGISTER_MESSAGE==null){$this->REGISTER_MESSAGE="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
			if($this->REGISTER_SUBJECT==null){$this->REGISTER_SUBJECT="HotSpot account validation";}
			if($this->CONFIRM_MESSAGE==null){$this->CONFIRM_MESSAGE="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration<br>\nYour can surf on internet for %s minutes";}
			if($this->SMS_CONFIRM_MESSAGE==null){$this->SMS_CONFIRM_MESSAGE="Success\nAn SMS as been sent to you\nPlease check your mobile";}
			if($this->SMS_FIELD==null){$this->SMS_FIELD="Confirmation code";}
			if($this->SMS_FONT_SIZE==0){$this->SMS_FONT_SIZE=44;}
			
			if($this->FontFamily==null){$this->FontFamily="\"Segoe UI\", \"verdana\"";}
			if($this->backgroundColor==null){$this->backgroundColor="005447";}
			if($this->LinksColor==null){$this->LinksColor="005447";}
			if($this->FontColor==null){$this->FontColor="FFFFFF";}
			if($this->TitleColor==null){$this->TitleColor="FFFFFF";}
			if($this->ButtonFontColor==null){$this->ButtonFontColor="FFFFFF";}
			if($this->FormFontColor==null){$this->FormFontColor="000000";}
			
			if($this->TitleFontSize==0){$this->TitleFontSize=64;}
			if($this->SubTitleFontSize==0){$this->SubTitleFontSize=32;}
			
			
			
			if($this->FontSize==0){$this->FontSize=16;}
			if($this->SpacerButton==0){$this->SpacerButton=20;}
			if($this->Button2014FontSize==0){$this->Button2014FontSize=22;}
			$this->MainTitle=$sock->GET_INFO("MainTitle");
			$this->BackgroundPicturePath=$sock->GET_INFO("BackgroundPicturePath");
			
			if($this->SizePage==0){$this->SizePage=700;}
			if($this->MarginPage==0){$this->MarginPage=120;}
			if($this->Button2014Bgcolor==null){$this->Button2014Bgcolor="5CB85C";}
			if($this->Button2014BgcolorOver==null){$this->Button2014BgcolorOver="47A447";}
			if($this->Button2014BgcolorBorder==null){$this->Button2014BgcolorBorder="4CAE4C";}
			if($this->BackgroundPicturePath==null){$this->BackgroundPicturePath="/img/wifidog-500.png";}
			if($this->BackgroundRepeat==null){$this->BackgroundRepeat="no-repeat";}
			if($this->REGISTER_GENERIC_LABEL==null){$this->REGISTER_GENERIC_LABEL="Unlock password";}
			if($this->REGISTER_GENERIC_PASSTXT==null){$this->REGISTER_GENERIC_PASSTXT="Thisisapassword";}
			if($this->REGISTER_GENERIC_PASSERR==null){$this->REGISTER_GENERIC_PASSERR="Please, ask the correct password to your local administrator";}
			
			
			
			if($this->WelcomeMessage==null){$this->WelcomeMessage="Welcome to our HotSpot system, set here here your user name and password in order to have a free Internet access";}
			
			if($this->REGISTER_MESSAGE_SUCCESS==null){$this->REGISTER_MESSAGE_SUCCESS="Your Account is Now Validated!<br>Thank you for confirming your email address.";}
			if($this->REGISTER_MESSAGE_EXPLAIN==null){$this->REGISTER_MESSAGE_EXPLAIN="As an customer, you can avail of free access to Internet by registering here.<br>After filling the form you will have access to a short period in order to confirm your registration";}
			if($this->TERMS_CONDITIONS==null){$this->TERMS_CONDITIONS=@file_get_contents(dirname(__FILE__)."/databases/wifi-terms.txt");}
			if($this->TERMS_EXPLAIN==null){$this->TERMS_EXPLAIN="To signup you are required to read our \"TERMS and CONDITIONS\".<br>Once you have read these terms and conditions please click \"ACCEPT\" acknowledging you understand and accept these terms and conditions.";}
			if($this->RegisterTitle==null){$this->RegisterTitle=$tpl->javascript_parse_text("{register}");}
			if($this->LostPasswordLink==null){$this->LostPasswordLink=$tpl->_ENGINE_parse_body("{lost_password}");}
			if($this->ConnectionButton==null){$this->ConnectionButton=$tpl->_ENGINE_parse_body("{connection}");}
			
			
			if($this->SMS_SMTP_SUBJECT==null){$this->SMS_SMTP_SUBJECT="sms-%TIME%-1:Account1:pass1:+33612345678:%MOBILE%";}
			if($this->SMS_SMTP_BODY==null){$this->SMS_SMTP_BODY="message=Your proxy unlock code is %CODE%";}
			if($this->SMS_INTRO==null){$this->SMS_INTRO="In order to access to Internet, give your cell number in this form.<br>You will receive a confirmation code by SMS";}
			if($this->SMS_BUTTON==null){$this->SMS_BUTTON="Send me the code";}
			if($this->SMS_CODE_ERROR==null){$this->SMS_CODE_ERROR="Wrong confirmation code entered, please retry";}
			
			
			
			
			
			if($this->LabelUsername==null){$this->LabelUsername=$tpl->_ENGINE_parse_body("{username}");}
			if($this->LabelPassword==null){$this->LabelPassword=$tpl->_ENGINE_parse_body("{password}");}
			
			
			if($this->ArticaSplashHotSpotRedirectText==null){$this->ArticaSplashHotSpotRedirectText=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to}");}
			if($this->RECOVER_MESSAGE==null){$this->RECOVER_MESSAGE="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
			
			if($this->FormStyle==null){
				
				$ff[]="background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;";
				$ff[]="background: rgb(255,255,255); /* Old browsers */";
				$ff[]="background: -moz-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(246,246,246,1) 47%, rgba(237,237,237,1) 100%); /* FF3.6+ */";
				$ff[]="background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(47%,rgba(246,246,246,1)), color-stop(100%,rgba(237,237,237,1))); /* Chrome,Safari4+ */";
				$ff[]="background: -webkit-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* Chrome10+,Safari5.1+ */";
				$ff[]="background: -o-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* Opera 11.10+ */";
				$ff[]="background: -ms-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* IE10+ */";
				$ff[]="background: linear-gradient(to bottom, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* W3C */";
				$ff[]="filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed',GradientType=0 ); /* IE6-9 */";
				$ff[]="border: 1px solid #DDDDDD;";
				$ff[]="border-radius: 5px 5px 5px 5px;";
				$ff[]="box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);";
				$ff[]="margin: 5px;\npadding: 21px;\n";
				$this->FormStyle=@implode("\n", $ff);
				
				
				
				
			}
			
			if($this->FieldsStyle==null){
			$this->FieldsStyle="width:80%;background-color: #FFFFFF;\nborder: 1px solid #CCCCCC;\nbox-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;\ntransition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;\npadding:3px;\ncolor:#4C535C;";
			}
			
			if($this->LegendsStyle==null){
			$this->LegendsStyle="color: #4C535C;\nfont-weight: normal;\ntext-align: right;\ntext-transform: capitalize;";
			}
			
			if($this->TextErrorStyle==null){
				$this->TextErrorStyle="color:#E40501 !important;\nmargin:5px;\npadding:3px;\nborder:1px solid #E40501;\nborder-radius:5px 5px 5px 5px;\n-moz-border-radius:5px;\n-webkit-border-radius:5px;\nbackground-color: #F7E5D9;\nfont-weight:bold;\nfont-size: 18px;\nmargin-bottom: 20px;\npadding: 8px 35px 8px 14px;\ntext-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);\n";		
			}
			
			if($this->FooterText==null){
				$this->FooterText="You using an HotSpot system using a Community License<br>\nFor more informations, see <a href=\"http://artica-proxy.com\" style='color:white !important;text-decoration:underline'>Artica Proxy</a>		
				";
			}
			
			if($this->MainTitle==null){$this->MainTitle="HotSpot System";}
	}
	
	public function char($text)
	{
		$text = htmlentities($text, ENT_NOQUOTES, "UTF-8");
		$text = htmlspecialchars_decode($text);
		return $text;
	}
	
	
	function build($content){
		$head=$this->head();
		
		$f[]="$head";
		$f[]="<div class=mainbody>";
		$f[]="<DIV class=\"mainContent\" id=\"contentContainer\">";
		$f[]="<DIV class=\"title\" id=\"mainTitle\">". $this->char($this->MainTitle)."</DIV>";
		$f[]="<DIV class=\"taskSection\" id=\"taskSection\">";
		$f[]="$content</div>";
		$f[]="</div>";
	
		$f[]="<center class='footer' style='width:100%'>".$this->char($this->FooterText)."</center>";
		$f[]="</body>";
		$f[]="</html>";
		return @implode("\n", $f);
	}
	
	
	function head(){
		
		#005447
		
		
		$this->default_values();
		$f[]="<!DOCTYPE HTML>";
		$f[]="<HTML>";
		$f[]="<HEAD>";
		$f[]="<title>$this->MainTitle</title>";
		$f[]="<META content=\"IE=11.0000\" http-equiv=\"X-UA-Compatible\">";
		$f[]="<META http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">";
		$f[]=$this->headerAdd;
		$f[]="<link href=\"/hotspot.php?wifidog-css=$this->ruleid\" rel=\"stylesheet\"  type=\"text/css\"/>";
		
		$f[]="<script type=\"text/javascript\">
		function blur(){ }
		function Blurz() { }
		function checkIfTopMostWindow()
		{
			if (window.top != window.self)
			{
				document.body.style.opacity    = \"0.0\";
				document.body.style.background = \"#FFFFFF\";
			}
			else
			{
				document.body.style.opacity    = \"1.0\";
				document.body.style.background = \"#$this->backgroundColor\";
			}
		}
		</script>\n";
		
		$f[]="</HEAD>
		<body onLoad='checkIfTopMostWindow()'>";
		return @implode("\n",$f);
	}
	
	
	function css(){
		
		$f[]="body {";
		$f[]="    background-repeat: repeat-x;";
		$f[]="    background-color: white;";
		$f[]="    font-family: \"Segoe UI\", \"verdana\", \"arial\";";
		$f[]="    margin: 0em;";
		$f[]="    color: #$this->FontColor;";
		$f[]="    opacity:            0.0;";
		$f[]="    transition:         opacity 2s;";
		$f[]="    -webkit-transition: opacity 2s;";
		$f[]="    -moz-transition:    opacity 2s;";
		$f[]="    -o-transition:      opacity 2s;";
		$f[]="    -ms-transition:     opacity 2s;";
		$f[]="}";
		$f[]="H1,H2,HR{ display:none; }";
		$f[]="a,a:visited,a:active {";
		$f[]="    color: #$this->LinksColor;";
		$f[]="    font-family: $this->FontFamily;";
		$f[]="	  text-decoration:underline;";
		$f[]="}";
		//no-repeat
		//repeat-y
		//repeat-x
		//repeat
		$f[]=".mainbody{";
		$f[]="	background-image:url(\"$this->BackgroundPicturePath\");";	
		$f[]="  background-repeat: $this->BackgroundRepeat;";
		$f[]="  background-position: {$this->BackgroundTOP}% {$this->BackgroundBottom}%;";
		$f[]="  min-height:550px;";
		$f[]="  padding-top:80px;";
		$f[]="}";
		$f[]="";
		$f[]=".mainContent {";
		$f[]="    width: {$this->SizePage}px;";
		$f[]="    margin-left: {$this->MarginPage}px;";
		$f[]="    margin-right: {$this->MarginPage}px;";
		$f[]="}";
		
		$f[]="p.text-error{";
		$f[]=$this->TextErrorStyle;
		$f[]="}";
		
		$f[]=".text-error a {";
		$f[]=$this->TextErrorStyle;
		$f[]=";border:0px;";
		$f[]="margin:0px;";
		$f[]="padding:0px;";
		$f[]="border-radius:0px;";
		$f[]="}";
		$f[]=".title {";
		$f[]="    color: #$this->TitleColor;";
		$f[]="    font-size: {$this->TitleFontSize}px;";
		$f[]="    font-weight: 300;";
		$f[]="    vertical-align: bottom;";
		$f[]="    margin-bottom: 20px;";
		$f[]="    font-family: $this->FontFamily;";
		$f[]="    position: relative;";
		$f[]="}";
		
		$f[]=".title2 {";
		$f[]="    color: #$this->TitleColor;";
		$f[]="    font-size: {$this->SubTitleFontSize}px;";
		$f[]="    font-weight: 300;";
		$f[]="    vertical-align: bottom;";
		$f[]="    margin-bottom: 20px;";
		$f[]="    font-family: $this->FontFamily;";
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
		$f[]="    font-family: $this->FontFamily;";
		$f[]="    font-weight: 200;";
		$f[]="    font-size: {$this->FontSize}px;";
		$f[]="    padding-top: 5px;";
		$f[]="}";
		
		$f[]=".PInside {";
		$f[]="    color: #000000;";
		$f[]="    font-family: $this->FontFamily;";
		$f[]="    font-weight: 200;";
		$f[]="    font-size: {$this->FontSize}px;";
		$f[]="    padding-top: 5px;";
		$f[]="}";		
		
		$f[]="blockquote {";
		$f[]="    color: #000000;";
		$f[]="    font-family: $this->FontFamily;";
		$f[]="    font-weight: 200;";
		$f[]="    font-size: {$this->FontSize}px;";
		$f[]="    margin-top: -10px;";
		$f[]="}";
		$f[]="";
		$f[]="P,pre {";
		$f[]="    color: #$this->FontColor;";
		$f[]="    font-family: $this->FontFamily;";
		$f[]="    font-weight: 200;";
		$f[]="    font-size: {$this->FontSize}px;";
		$f[]="    padding-top: 5px;";
		$f[]="}";
		$f[]="#footer p {";
		$f[]="    color: #CCCCCC;";
		$f[]="    font-family: $this->FontFamily;";
		$f[]="    font-weight: normal;";
		$f[]="    font-size: 10pt;";
		$f[]="    padding-top: 5px;";
		$f[]="}";
		
		$f[]=".spacer-height { height:{$this->SpacerButton}px;}";
		$f[]="input[type=\"text\"], input[type=\"password\"], input[type=\"datetime\"], input[type=\"datetime-local\"], input[type=\"date\"], input[type=\"month\"], input[type=\"time\"], input[type=\"week\"], input[type=\"number\"], input[type=\"email\"], input[type=\"url\"], input[type=\"search\"], input[type=\"tel\"], input[type=\"color\"], .uneditable-input {";
		$f[]=$this->FieldsStyle;
		$f[]="font-size: {$this->FontSize}px !important;";
		$f[]="}";
		
		$f[]=".legend{";
		$f[]=$this->LegendsStyle;
		$f[]="}";
		
		
		$f[]="td.ButtonCell { 
				text-align:right;
				vertical-align:bottom;
				padding-top:{$this->SpacerButton}px;
				border-top:1px solid #CCCCCC;
	
	}";
		
		$f[]=".Button2014-lg {
			border-radius: 6px 6px 6px 6px;
			font-size: 18px;
			line-height: 1.33;
			padding: 10px 16px;
		}
		.Button2014-success {
			background-color: #$this->Button2014Bgcolor;
			border-color: #$this->Button2014BgcolorBorder;
			color: #{$this->ButtonFontColor};
		}
		.Button2014 {
			-moz-user-select: none;
			border: 1px solid transparent;
			border-radius: 4px 4px 4px 4px;
			cursor: pointer;
			display: inline-block;
			font-size: {$this->Button2014FontSize}px !important;
			font-weight: normal;
			line-height: 1.42857;
			margin-bottom: 0;
			padding: 6px 22px;
			text-align: center;
			vertical-align: middle;
			white-space: nowrap;
		}
		
		a.Button2014, a.Button2014:link, a.Button2014:visited, a.Button2014:hover{
			font-size: {$this->Button2014FontSize}px !important;
			color: #{$this->ButtonFontColor};
			text-decoration:none;
		}
		
		tr.TableBouton2014{
			cursor: pointer;
			background-color: $this->Button2014Bgcolor !important;
			border-color: $this->Button2014BgcolorBorder !important;
			color: #{$this->ButtonFontColor} !important;
		}
		tr.TableBouton2014:hover{
			cursor: pointer;
			background-color: #$this->Button2014BgcolorOver !important;
			border-color: #$this->Button2014BgcolorBorder !important;
			color: #{$this->ButtonFontColor} !important;
		}
		
		td.TableBouton2014{
			border-color: #$this->Button2014BgcolorBorder !important;
			border-color: #$this->Button2014BgcolorBorder !important;
		}
		td.TableBouton2014:hover{
			background-color: $this->Button2014BgcolorOver !important;
			border-color: #$this->Button2014BgcolorBorder !important;
		}
		
		.Button2014-success {
			background-color: $this->Button2014Bgcolor !important;
			border-color: $this->Button2014BgcolorBorder !important;
			color: #{$this->ButtonFontColor} !important;
		}
		.Button2014-success:hover, .Button2014-success:focus, .Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
			background-color: #$this->Button2014BgcolorOver !important;
			border-color: #$this->Button2014BgcolorBorder !important;
			color: #{$this->ButtonFontColor} !important;
		}
		.Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
			background-image: none;
		}
		.Button2014-success.disabled, .Button2014-success[disabled], fieldset[disabled] .Button2014-success, .Button2014-success.disabled:hover, .Button2014-success[disabled]:hover, fieldset[disabled] .Button2014-success:hover, .Button2014-success.disabled:focus, .Button2014-success[disabled]:focus, fieldset[disabled] .Button2014-success:focus, .Button2014-success.disabled:active, .Button2014-success[disabled]:active, fieldset[disabled] .Button2014-success:active, .Button2014-success.disabled.active, .Button2014-success.active[disabled], fieldset[disabled] .Button2014-success.active {
			background-color: #$this->Button2014Bgcolor !important;
			border-color: #$this->Button2014BgcolorBorder !important;
		}";		

		
		
		$f[]="form {";
		$f[]="   $this->FormStyle";
		$f[]="}";
		$f[]="form {";
		$f[]="   color:#{$this->FormFontColor}";
		$f[]="}";
		$f[]="form > p {";
		$f[]="   color:#{$this->FormFontColor}";
		$f[]="}";
		
		
		$f[]="li {";
		$f[]="    margin-top: 8px;";
		$f[]="}";
		$f[]="";
		$f[]=".diagnoseButton {";
		$f[]="    outline: none;";
		$f[]="    font-size: 9pt;";
		$f[]="}";
		return @implode("",$f);
	}
	
	
}