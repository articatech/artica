<?php
include_once('ressources/class.templates.inc');




header("Content-type: text/css");
if(isset($_SESSION["FONT_CSS"])){echo $_SESSION["FONT_CSS"];}

$Green="#005447";
$ButtonOver="#057D6A";
$ButtonGradientStart="#047F6C";
$StrongGreen="#044036";
$Button2014Bgcolor="#5CB85C";
$Button2014BgcolorOver="#47A447";
$Button2014BgcolorBorder="#4CAE4C";
$skinf="#005447";
$sock=new sockets();

$font_family_org=$sock->GET_INFO("InterfaceFonts");
$font_family=$font_family_org;
if($font_family==null){$font_family="'Lucida Grande',Arial, Helvetica, sans-serif";}

$ForceDefaultGreenColor=$sock->GET_INFO("ForceDefaultGreenColor");
$ForceDefaultButtonColor=$sock->GET_INFO("ForceDefaultButtonColor");
$ForceDefaultTopBarrColor=$sock->GET_INFO("ForceDefaultTopBarrColor");
if($ForceDefaultGreenColor<>null){$Green="#".$ForceDefaultGreenColor;}

if($ForceDefaultButtonColor<>null){
	$Button2014Bgcolor=$ForceDefaultButtonColor;
	$Button2014BgcolorOver=$ForceDefaultGreenColor;
	$Button2014BgcolorBorder=$ForceDefaultButtonColor;
	
}


$skinf=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/top-bar-color.conf";
$skinOver=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/top-bar-color-over.conf";
$skinborder=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/top-bar-color-border.conf";
$body=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/body.conf";




$css[]="/* template {$_COOKIE["artica-template"]} */";

if(is_file($skinf)){
	$Green=@file_get_contents($skinf);
	$Button2014Bgcolor=$Green;
	
}

if(is_file($skinOver)){
	$Button2014BgcolorOver=@file_get_contents($skinOver);
}
if(is_file($skinborder)){
	$Button2014BgcolorBorder=@file_get_contents($skinborder);
}

if($Green==null){$Green="#005447";}


if(is_file($body)){
	
	$body_content=@file_get_contents($body);
	$body_content=str_replace("%TEMPLATE%","{$_COOKIE["artica-template"]}",$body_content);
	$css[]="/* *************** $body **************** */";
	$css[]=$body_content;

}

if(preg_match("#; MSIE#",$_SERVER["HTTP_USER_AGENT"])){
	$ASIE=true;
}

$tpl=new templates();
$yes=$tpl->javascript_parse_text("{yes1}");
$no=$tpl->javascript_parse_text("{no1}");

$css[]="div .form{
  -moz-border-radius: 5px;
  border-radius: 5px;
  border:1px solid #DDDDDD;
  background:url(\"/img/gr-greybox.gif\") repeat-x scroll 0 0 #FBFBFA;
";

if(!$ASIE){
	$css[]="background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
    background: -webkit-gradient(linear, center top, center bottom, from(#F1F1F1), to(#FFFFFF)) repeat scroll 0 0 transparent;
	background: -webkit-linear-gradient( #F1F1F1, #FFFFFF) repeat scroll 0 0 transparent;
	background: -o-linear-gradient(#F1F1F1, #FFFFFF) repeat scroll 0 0 transparent;
	background: -ms-linear-gradient(#F1F1F1, #ffffff) repeat scroll 0 0 transparent;
	background: linear-gradient(#F1F1F1, #ffffff) repeat scroll 0 0 transparent;
";
}
if($ASIE){
	$css[]="filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#F1F1F1', endColorstr='#ffffff');";
}


$css[]="background:-moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
  margin:5px;padding:5px;
  -webkit-border-radius: 5px;
  -o-border-radius: 5px;
 -moz-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 -webkit-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
}";


$cssplus=@implode("\n", $css);


$MAINCSS= "/* template {$_COOKIE["artica-template"]} */
body{
	font-family:$font_family;
}

.bx-slider-top {
  	background-color: $Green;
    color: #DCDCDC;
    font-size:16px;
    height: 28px;
    margin-left: 0px;
    margin-right: 0;
    margin-top: 0px;
    padding-right: 10px;
    padding-top: 6px;
    text-align: right;
    width: 99%;
      -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
	/* behavior:url(/css/border-radius.htc); */
}

#bx-slider-top a {
    color: #DCDCDC;
    font-weight: normal;
    font-size:16px;
}




.bx-slider-top a:link, a:visited {
    color: #DCDCDC;
    font-weight: normal;
    font-size:16px;
}

.bx-slider-top a:hover {
    color: white;
    font-weight:bold;
    font-size: 16px;
    text-decoration:underline;
    
}



h3{
	font-size:14px;
}



.form-horizontal .control-label {
    float: none;
    width: auto;
    padding-top: 0;
    text-align: left;
}
.form-horizontal .controls {
    margin-left: 0;
}
.form-horizontal .control-list {
    padding-top: 0;
}
.form-horizontal .form-actions {
    padding-right: 10px;
    padding-left: 10px;
}

.controls select,
input[type=\"file\"] {
  height: 30px;
  *margin-top: 4px;
  line-height: 30px;	
	
	
}
.controls select{
  background-color: #ffffff;
  border: 1px solid #cccccc;	
	
}

.controls > .radio:first-child,
.controls > .checkbox:first-child {
  padding-top: 5px;
}

.controls > .radio:first-child,
.controls > .checkbox:first-child {
  padding-top: 5px;
}
.form-horizontal .control-group:after {
  clear: both;
}

.form-horizontal .control-label {
  float: left;
  width: 240px;
  padding-top: 5px;
  text-align: right;
  font-size:14px;
}

.form-horizontal .controls {
  *display: inline-block;
  *padding-left: 20px;
  margin-left: 250px;
  *margin-left: 0;
}

.form-horizontal .controls:first-child {
  *padding-left: 180px;
}

.form-horizontal button, input, select, textarea {
  margin: 0;
  font-size: 100%;
  vertical-align: middle;
}

.form-horizontal button,input {
  *overflow: visible;
  line-height: normal;
}

.form-horizontal label,select,button,input[type=\"button\"],input[type=\"reset\"],input[type=\"submit\"], input[type=\"radio\"], input[type=\"checkbox\"] {
  cursor: pointer;
}

.form-horizontal input, textarea, .uneditable-input {
    width: 250px;
}
.form-horizontal textarea {
    height: auto;
}
.form-horizontal input[type=\"checkbox\"], input[type=\"radio\"] {
    border: 1px solid #ccc;
  }


.form-horizontal textarea, input[type=\"text\"], input[type=\"password\"], input[type=\"datetime\"], input[type=\"datetime-local\"], input[type=\"date\"], input[type=\"month\"], input[type=\"time\"], input[type=\"week\"], input[type=\"number\"], input[type=\"email\"], input[type=\"url\"], input[type=\"search\"], input[type=\"tel\"], input[type=\"color\"], .uneditable-input {
    background-color: #FFFFFF;
    border: 1px solid #CCCCCC;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
    transition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;
}
.form-horizontal textarea:focus, input[type=\"text\"]:focus, input[type=\"password\"]:focus, input[type=\"datetime\"]:focus, input[type=\"datetime-local\"]:focus, input[type=\"date\"]:focus, input[type=\"month\"]:focus, input[type=\"time\"]:focus, input[type=\"week\"]:focus, input[type=\"number\"]:focus, input[type=\"email\"]:focus, input[type=\"url\"]:focus, input[type=\"search\"]:focus, input[type=\"tel\"]:focus, input[type=\"color\"]:focus, .uneditable-input:focus {
    border-color: rgba(82, 168, 236, 0.8);
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6);
    outline: 0 none;
}

.form-horizontal textarea {
  overflow: auto;
  vertical-align: top;
}

.form-horizontal h1,h2,h3,h4,h5,h6 {
  margin: 10px 0;
  font-family: inherit;
  font-weight: bold;
  line-height: 20px;
  color: inherit;
  text-rendering: optimizelegibility;
}

.form-horizontal h1,h2,h3,h4,h5,h6 :first-letter{
  text-transform:capitalize;
}

.form-horizontal legend {
  display: block;
  width: 100%;
  padding: 0;
  margin-bottom: 20px;
  font-size: 21px;
  line-height: 40px;
  color: #333333;
  border: 0;
  border-bottom: 1px solid #e5e5e5;
}

.form-horizontal label,input,button,select,textarea {
  font-size: 14px;
  font-weight: normal;
  line-height: 20px;
}

.form-horizontal input,button,select,textarea {
  font-family: $font_family;
}

label {
  display: block;
  margin-bottom: 5px;
}

.form-horizontal select,textarea,
input[type=\"text\"],
input[type=\"password\"],
input[type=\"datetime\"],
input[type=\"datetime-local\"],
input[type=\"date\"],
input[type=\"month\"],
input[type=\"time\"],
input[type=\"week\"],
input[type=\"number\"],
input[type=\"email\"],
input[type=\"url\"],
input[type=\"search\"],
input[type=\"tel\"],
input[type=\"color\"],
.uneditable-input {
  display: inline-block;
  height: auto;
  padding: 4px 6px;
  margin-bottom: 10px;
  font-size: 14px;
  line-height: 20px;
  color: #555555;
  vertical-align: middle;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
/* behavior:url(/css/border-radius.htc); */
}

.form-horizontal textarea,
input[type=\"text\"],
input[type=\"password\"],
input[type=\"datetime\"],
input[type=\"datetime-local\"],
input[type=\"date\"],
input[type=\"month\"],
input[type=\"time\"],
input[type=\"week\"],
input[type=\"number\"],
input[type=\"email\"],
input[type=\"url\"],
input[type=\"search\"],
input[type=\"tel\"],
input[type=\"color\"],
.uneditable-input {
  background-color: #ffffff;
  border: 1px solid #cccccc;
  -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
     -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
          box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  -webkit-transition: border linear 0.2s, box-shadow linear 0.2s;
     -moz-transition: border linear 0.2s, box-shadow linear 0.2s;
       -o-transition: border linear 0.2s, box-shadow linear 0.2s;
          transition: border linear 0.2s, box-shadow linear 0.2s;
}

.form-horizontal textarea:focus,
input[type=\"text\"]:focus,
input[type=\"password\"]:focus,
input[type=\"datetime\"]:focus,
input[type=\"datetime-local\"]:focus,
input[type=\"date\"]:focus,
input[type=\"month\"]:focus,
input[type=\"time\"]:focus,
input[type=\"week\"]:focus,
input[type=\"number\"]:focus,
input[type=\"email\"]:focus,
input[type=\"url\"]:focus,
input[type=\"search\"]:focus,
input[type=\"tel\"]:focus,
input[type=\"color\"]:focus,
.uneditable-input:focus {
  border-color: rgba(82, 168, 236, 0.8);
  outline: 0;
  outline: thin dotted \9;
  /* IE6-9 */

  -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(82, 168, 236, 0.6);
     -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(82, 168, 236, 0.6);
          box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(82, 168, 236, 0.6);
}
.form-horizontal h3{
	font-size:18px;
}


.form-horizontal input[type=\"radio\"],
input[type=\"checkbox\"] {
  margin: 4px 0 0;
  margin-top: 1px \9;
  *margin-top: 0;
  line-height: normal;
}

.form-horizontal input[type=\"file\"],
input[type=\"image\"],
input[type=\"submit\"],
input[type=\"reset\"],
input[type=\"button\"],
input[type=\"radio\"],
input[type=\"checkbox\"] {
  width: auto;
}

.form-horizontal select,input[type=\"file\"] {
  height: 30px;
  /* In IE7, the height of the select element cannot be changed by height, only font-size */

  *margin-top: 4px;
  /* For IE7, add top margin to align select with labels */

  line-height: 30px;
}

.controls select,
input[type=\"file\"] {
  height: 30px;
  *margin-top: 4px;
  line-height: 30px;	
	
	
}
.controls select{
  background-color: #ffffff;
  border: 1px solid #cccccc;	
	
}

.form-horizontal select:focus,input[type=\"file\"]:focus,input[type=\"radio\"]:focus, input[type=\"checkbox\"]:focus {
  outline: thin dotted #333;
  outline: 5px auto -webkit-focus-ring-color;
  outline-offset: -2px;
}

.uneditable-input,
.uneditable-textarea {
  color: #999999;
  cursor: not-allowed;
  background-color: #fcfcfc;
  border-color: #cccccc;
  -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.025);
     -moz-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.025);
          box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.025);
}
input[disabled],select[disabled],textarea[disabled],input[readonly],select[readonly],
textarea[readonly] {
  cursor: not-allowed;
  background-color: #eeeeee;
}

.form-horizontal input[type=\"radio\"][disabled],input[type=\"checkbox\"][disabled],input[type=\"radio\"][readonly],
input[type=\"checkbox\"][readonly] {
  background-color: transparent;
}

.sDiv2 > select {
    margin-top: -10px;
    font-size:12px !important;
}

.sDiv2 > input[type=\"text\"] {
font-size:12px !important;
}
.tooltip {
  position: absolute;
  z-index: 1030;
  display: block;
  font-size: 11px;
  line-height: 1.1;
  opacity: 0;
  filter: alpha(opacity=0);
  visibility: visible;
}

.tooltip.in {
  opacity: 0.8;
  filter: alpha(opacity=80);
}

.tooltip.top {
  padding: 5px 0;
  margin-top: -3px;
}

.tooltip.right {
  padding: 0 5px;
  margin-left: 3px;
}

.tooltip.bottom {
  padding: 5px 0;
  margin-top: 3px;
}

.tooltip.left {
  padding: 0 5px;
  margin-left: -3px;
}

.tooltip-inner {
  max-width: 500px;
  padding: 8px;
  color: #ffffff;
  text-align: left;
  text-decoration: none;
  background-color: #000000;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
/* behavior:url(/css/border-radius.htc); */
}

.tooltip-arrow {
  position: absolute;
  width: 0;
  height: 0;
  border-color: transparent;
  border-style: solid;
}

.tooltip.top .tooltip-arrow {
  bottom: 0;
  left: 50%;
  margin-left: -5px;
  border-top-color: #000000;
  border-width: 5px 5px 0;
}

.tooltip.right .tooltip-arrow {
  top: 50%;
  left: 0;
  margin-top: -5px;
  border-right-color: #000000;
  border-width: 5px 5px 5px 0;
}

.tooltip.left .tooltip-arrow {
  top: 50%;
  right: 0;
  margin-top: -5px;
  border-left-color: #000000;
  border-width: 5px 0 5px 5px;
}

.tooltip.bottom .tooltip-arrow {
  top: 0;
  left: 50%;
  margin-left: -5px;
  border-bottom-color: #000000;
  border-width: 0 5px 5px;
}

.popover {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1010;
  display: none;
  max-width: 276px;
  padding: 1px;
  text-align: left;
  white-space: normal;
  background-color: #ffffff;
  border: 1px solid #ccc;
  border: 1px solid rgba(0, 0, 0, 0.2);
  -webkit-border-radius: 6px;
     -moz-border-radius: 6px;
          border-radius: 6px;
  -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
     -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
          box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  -webkit-background-clip: padding-box;
     -moz-background-clip: padding;
          background-clip: padding-box;
/* behavior:url(/css/border-radius.htc); */
}

.popover.top {
  margin-top: -10px;
}

.popover.right {
  margin-left: 10px;
}

.popover.bottom {
  margin-top: 10px;
}

.popover.left {
  margin-left: -10px;
}

.popover-title {
  padding: 8px 14px;
  margin: 0;
  font-size: 14px;
  font-weight: normal;
  line-height: 18px;
  background-color: #f7f7f7;
  border-bottom: 1px solid #ebebeb;
  -webkit-border-radius: 5px 5px 0 0;
  -moz-border-radius: 5px 5px 0 0;
  border-radius: 5px 5px 0 0;
/* behavior:url(/css/border-radius.htc); */
}

.popover-title:empty {
  display: none;
}

.popover-content {
  padding: 9px 14px;
}

.popover .arrow,
.popover .arrow:after {
  position: absolute;
  display: block;
  width: 0;
  height: 0;
  border-color: transparent;
  border-style: solid;
}

.popover .arrow {
  border-width: 11px;
}

.popover .arrow:after {
  border-width: 10px;
  content: \"\";
}

.popover.top .arrow {
  bottom: -11px;
  left: 50%;
  margin-left: -11px;
  border-top-color: #999;
  border-top-color: rgba(0, 0, 0, 0.25);
  border-bottom-width: 0;
}

.popover.top .arrow:after {
  bottom: 1px;
  margin-left: -10px;
  border-top-color: #ffffff;
  border-bottom-width: 0;
}

.popover.right .arrow {
  top: 50%;
  left: -11px;
  margin-top: -11px;
  border-right-color: #999;
  border-right-color: rgba(0, 0, 0, 0.25);
  border-left-width: 0;
}

.popover.right .arrow:after {
  bottom: -10px;
  left: 1px;
  border-right-color: #ffffff;
  border-left-width: 0;
}

.popover.bottom .arrow {
  top: -11px;
  left: 50%;
  margin-left: -11px;
  border-bottom-color: #999;
  border-bottom-color: rgba(0, 0, 0, 0.25);
  border-top-width: 0;
}

.popover.bottom .arrow:after {
  top: 1px;
  margin-left: -10px;
  border-bottom-color: #ffffff;
  border-top-width: 0;
}

.popover.left .arrow {
  top: 50%;
  right: -11px;
  margin-top: -11px;
  border-left-color: #999;
  border-left-color: rgba(0, 0, 0, 0.25);
  border-right-width: 0;
}

.popover.left .arrow:after {
  right: 1px;
  bottom: -10px;
  border-left-color: #ffffff;
  border-right-width: 0;
}

.btn.disabled,
.btn[disabled] {
  cursor: default;
  background-image: none;
  opacity: 0.65;
  filter: alpha(opacity=65);
  -webkit-box-shadow: none;
     -moz-box-shadow: none;
          box-shadow: none;
}

.btn-large {
  padding: 11px 19px;
  font-size: 17.5px;
  -webkit-border-radius: 6px;
     -moz-border-radius: 6px;
          border-radius: 6px;
}

.btn-large [class^=\"icon-\"],
.btn-large [class*=\" icon-\"] {
  margin-top: 4px;
}

button.btn.btn-large,
input[type=\"submit\"].btn.btn-large {
  *padding-top: 7px;
  *padding-bottom: 7px;
}

button.btn.btn-large,
input[type=\"submit\"].btn.btn-large {
  *padding-top: 7px;
  *padding-bottom: 7px;
}

.btn-primary.active,
.btn-warning.active,
.btn-danger.active,
.btn-success.active,
.btn-info.active,
.btn-inverse.active {
  color: rgba(255, 255, 255, 0.75);
}

.btn-primary {
  color: #ffffff;
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  background-color: $Green;
  *background-color: $ButtonOver;
  background-image: -moz-linear-gradient(top, $ButtonGradientStart, $ButtonOver);
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from($ButtonGradientStart), to($ButtonOver));
  background-image: -webkit-linear-gradient(top, $ButtonGradientStart, $ButtonOver);
  background-image: -o-linear-gradient(top, $ButtonGradientStart, $ButtonOver);
  background-image: linear-gradient(to bottom, $ButtonGradientStart, $ButtonOver);
  background-repeat: repeat-x;
  border-color: $ButtonOver $ButtonOver $StrongGreen;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='$ButtonGradientStart', endColorstr='$ButtonOver', GradientType=0);
  filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
}

.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active,
.btn-primary.active,
.btn-primary.disabled,
.btn-primary[disabled] {
  color: #ffffff;
  background-color: $ButtonOver;
  *background-color: $Green;
}

.btn-primary:active,
.btn-primary.active {
  background-color: $StrongGreen \9;
}

.blockUI.blockOverlay {
    background-color: 005447;
    opacity: 0.6;
}

.blockUI.blockMsg.blockPage {
height:290px;
-webkit-border-radius: 5px 5px 0 0;
-moz-border-radius: 5px 5px 0 0;
border-radius: 5px 5px 0 0;
opacity: 0.6;
/* behavior:url(/css/border-radius.htc); */	
}

.blockUI h1 {
    font-size: 55px;
    background:none;
    padding-top:95px;
    background-image: none;
}
  
.Button2014-lg {
    border-radius: 6px 6px 6px 6px;
    font-size: 18px;
    line-height: 1.33;
    padding: 10px 16px;
}
.Button2014-success {
    background-color: $Button2014Bgcolor;
    border-color: #4CAE4C;
    color: #FFFFFF;
}
.Button2014 {
    -moz-user-select: none;
    border: 1px solid transparent;
    border-radius: 4px 4px 4px 4px;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857;
    margin-bottom: 0;
    padding: 6px 22px;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

a.Button2014, a.Button2014:link, a.Button2014:visited, a.Button2014:hover{
	color: #FFFFFF;
	text-decoration:none;	
}

tr.TableBouton2014{
	cursor: pointer;
	background-color: $Button2014Bgcolor !important;
	border-color: $Button2014BgcolorBorder !important;
	color: #FFFFFF !important;
}
tr.TableBouton2014:hover{
	cursor: pointer;
	background-color: $Button2014BgcolorOver !important;
	border-color: $Button2014BgcolorBorder !important;
	color: #FFFFFF !important;
}

td.TableBouton2014{
	border-color: $Button2014BgcolorBorder !important;
	border-color: $Button2014BgcolorBorder !important;
}
td.TableBouton2014:hover{
	background-color: $Button2014BgcolorOver !important;
	border-color: $Button2014BgcolorBorder !important;
}

.Button2014-success {
    background-color: $Button2014Bgcolor !important;
    border-color: $Button2014BgcolorBorder !important;
    color: #FFFFFF !important;
}
.Button2014-success:hover, .Button2014-success:focus, .Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
    background-color: $Button2014BgcolorOver !important;
    border-color: $Button2014BgcolorBorder !important;
    color: #FFFFFF !important;
}
.Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
    background-image: none;
}
.Button2014-success.disabled, .Button2014-success[disabled], fieldset[disabled] .Button2014-success, .Button2014-success.disabled:hover, .Button2014-success[disabled]:hover, fieldset[disabled] .Button2014-success:hover, .Button2014-success.disabled:focus, .Button2014-success[disabled]:focus, fieldset[disabled] .Button2014-success:focus, .Button2014-success.disabled:active, .Button2014-success[disabled]:active, fieldset[disabled] .Button2014-success:active, .Button2014-success.disabled.active, .Button2014-success.active[disabled], fieldset[disabled] .Button2014-success.active {
    background-color: $Button2014Bgcolor !important;
    border-color: $Button2014BgcolorBorder !important;
}

.field {
    clear: both;
    margin-bottom: 10px;
    text-align: right;
}

.CheckBoxUnChecked [type=\"checkbox\"]{
	position: absolute;
	left: -9999px;
}
.CheckBoxChecked [type=\"checkbox\"]{
	position: absolute;
	left: -9999px;
}
.CheckBoxDisabled [type=\"checkbox\"]{
	position: absolute;
	left: -9999px;
}


.CheckBoxUnChecked [type=\"checkbox\"] + label{
		position: relative;
		padding-left: 75px;
		cursor: pointer;
}
.CheckBoxChecked [type=\"checkbox\"] + label{
		position: relative;
		padding-left: 75px;
		cursor: pointer;
}
.CheckBoxUnChecked [type=\"checkbox\"] + label:before,
	.CheckBoxUnChecked [type=\"checkbox\"] + label:after {
		content: '';
		position: absolute;
}
.CheckBoxChecked [type=\"checkbox\"] + label:before,
	.CheckBoxChecked [type=\"checkbox\"] + label:after {
		content: '';
		position: absolute;
}

.CheckBoxUnChecked 	[type=\"checkbox\"] + label:before,
	.CheckBoxUnChecked [type=\"checkbox\"]+ label:before {
		left:0; top: -3px;
		width: 78px; height: 30px;
		background: #DDDDDD;
		border-radius: 15px;
		-webkit-transition: background-color .2s;
		-moz-transition: background-color .2s;
		-ms-transition: background-color .2s;
		transition: background-color .2s;
	}

.CheckBoxChecked 	[type=\"checkbox\"] + label:before,
	.CheckBoxChecked [type=\"checkbox\"]+ label:before {
		left:0; top: -3px;
		width: 78px; height: 30px;
		background: #DDDDDD;
		border-radius: 15px;
		-webkit-transition: background-color .2s;
		-moz-transition: background-color .2s;
		-ms-transition: background-color .2s;
		transition: background-color .2s;
	}	
	
	
	
.CheckBoxUnChecked [type=\"checkbox\"] + label:after {
		width: 20px; height: 20px;
		-webkit-transition: all .2s;
		-moz-transition: all .2s;
		-ms-transition: all .2s;
		transition: all .2s;
		border-radius: 50%;
		background: #7F8C9A;
		top: 2px; left: 5px;
	}	
	
	
.CheckBoxChecked [type=\"checkbox\"] + label:after {
		width: 20px; height: 20px;
		-webkit-transition: all .2s;
		-moz-transition: all .2s;
		-ms-transition: all .2s;
		transition: all .2s;
		border-radius: 50%;
		background: #7F8C9A;
		top: 2px; left: 5px;
	}


.CheckBoxUnChecked [type=\"checkbox\"] + label .ui,
	.CheckBoxUnChecked [type=\"checkbox\"] + label .ui:before,
	.CheckBoxUnChecked [type=\"checkbox\"] + label .ui:after {
		position: absolute;
		left: 6px;
		width: 65px;
		border-radius: 15px;
		font-size: 14px;
		font-weight: bold;
		line-height: 22px;
		-webkit-transition: all .2s;
		-moz-transition: all .2s;
		-ms-transition: all .2s;
		transition: all .2s;
	}
	
.CheckBoxChecked [type=\"checkbox\"] + label .ui,
	.CheckBoxChecked [type=\"checkbox\"] + label .ui:before,
	.CheckBoxChecked [type=\"checkbox\"] + label .ui:after {
		position: absolute;
		left: 6px;
		width: 65px;
		border-radius: 15px;
		font-size: 14px;
		font-weight: bold;
		line-height: 22px;
		-webkit-transition: all .2s;
		-moz-transition: all .2s;
		-ms-transition: all .2s;
		transition: all .2s;
	}	
	

.CheckBoxUnChecked [type=\"checkbox\"] + label .ui:before {
		content: \"$no\";
		left: 32px
	}	
	
.CheckBoxChecked [type=\"checkbox\"] + label:after {
		background: #4EE84E;
		top: 2px; left: 55px;
	}

.CheckBoxChecked [type=\"checkbox\"] + label .ui:after {
		content: \"$yes\";
		color: #4C535C;
	}	
	
	
.CheckBoxDisabled [type=\"checkbox\"] + label .ui:before {
	color: white !important;
}
	
.CheckBoxUnChecked [type=\"checkbox\"]:focus + label:before {
		border: 1px solid #777;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		-ms-box-sizing: border-box;
		box-sizing: border-box;
		margin-top: -1px;
	}	
.CheckBoxChecked [type=\"checkbox\"]:focus + label:before {
		border: 1px solid #777;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		-ms-box-sizing: border-box;
		box-sizing: border-box;
		margin-top: -1px;
	}


.formDesign [type=\"checkbox\"]:not(:checked),
	.formDesign [type=\"checkbox\"]:checked {
		position: absolute;
		left: -9999px;
	}
.formDesign [type=\"checkbox\"]:not(:checked) + label,
	.formDesign [type=\"checkbox\"]:checked + label {
		position: relative;
		padding-left: 75px;
		cursor: pointer;
	}
.formDesign [type=\"checkbox\"]:not(:checked) + label:before,
	.formDesign [type=\"checkbox\"]:checked + label:before,
	.formDesign [type=\"checkbox\"]:not(:checked) + label:after,
	.formDesign [type=\"checkbox\"]:checked + label:after {
		content: '';
		position: absolute;
	}
.formDesign 	[type=\"checkbox\"]:not(:checked) + label:before,
	.formDesign [type=\"checkbox\"]:checked + label:before {
		left:0; top: -3px;
		width: 78px; height: 30px;
		background: #DDDDDD;
		border-radius: 15px;
		-webkit-transition: background-color .2s;
		-moz-transition: background-color .2s;
		-ms-transition: background-color .2s;
		transition: background-color .2s;
	}
.formDesign [type=\"checkbox\"]:not(:checked) + label:after,
	.formDesign [type=\"checkbox\"]:checked + label:after {
		width: 20px; height: 20px;
		-webkit-transition: all .2s;
		-moz-transition: all .2s;
		-ms-transition: all .2s;
		transition: all .2s;
		border-radius: 50%;
		background: #7F8C9A;
		top: 2px; left: 5px;
	}

	/* on checked */
.formDesign [type=\"checkbox\"]:checked + label:before {
		//background:#8E8E8E; 
		background:#F3F3F3; 
	}
.formDesign [type=\"checkbox\"]:checked + label:after {
		background: #4EE84E;
		top: 2px; left: 55px;
	}

.formDesign [type=\"checkbox\"]:checked + label .ui,
	.formDesign [type=\"checkbox\"]:not(:checked) + label .ui:before,
	.formDesign [type=\"checkbox\"]:checked + label .ui:after {
		position: absolute;
		left: 6px;
		width: 65px;
		border-radius: 15px;
		font-size: 14px;
		font-weight: bold;
		line-height: 22px;
		-webkit-transition: all .2s;
		-moz-transition: all .2s;
		-ms-transition: all .2s;
		transition: all .2s;
	}
.formDesign [type=\"checkbox\"]:not(:checked) + label .ui:before {
		content: \"$no\";
		left: 32px
	}
.formDesign [type=\"checkbox\"]:checked + label .ui:after {
		content: \"$yes\";
		color: #4C535C;
	}
.formDesign [type=\"checkbox\"]:focus + label:before {
		border: 1px solid #777;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		-ms-box-sizing: border-box;
		box-sizing: border-box;
		margin-top: -1px;
	}
	
/*  *****************************************   TOOL TIPS  ***************************************** */
.tooltipster-default {
	border-radius: 5px; 
	border: 2px solid #000;
	background: #4c4c4c;
	color: #fff !important;
}

/* Use this next selector to style things like font-size and line-height: */
.tooltipster-default .tooltipster-content {
	font-family: $font_family;
	font-size: 18px;
	line-height: 20px;
	padding: 8px 10px;
	overflow: hidden;
	color: #fff !important;
	
}

.tooltipster-default .tooltipster-content li {
	color: #fff !important;
}
.tooltipster-default .tooltipster-content ul {
	color: #fff !important;
}

/* This next selector defines the color of the border on the outside of the arrow. This will automatically match the color and size of the border set on the main tooltip styles. Set display: none; if you would like a border around the tooltip but no border around the arrow */
.tooltipster-default .tooltipster-arrow .tooltipster-arrow-border {
	/* border-color: ... !important; */
}


/* If you're using the icon option, use this next selector to style them */
.tooltipster-icon {
	cursor: help;
	margin-left: 4px;
}


/* This is the base styling required to make all Tooltipsters work */
.tooltipster-base {
	padding: 0;
	font-size: 0;
	line-height: 0;
	position: absolute;
	left: 0;
	top: 0;
	z-index: 9999999;
	pointer-events: none;
	width: auto;
	overflow: visible;
}
.tooltipster-base .tooltipster-content {
	overflow: hidden;
}


/* These next classes handle the styles for the little arrow attached to the tooltip. By default, the arrow will inherit the same colors and border as what is set on the main tooltip itself. */
.tooltipster-arrow {
	display: block;
	text-align: center;
	width: 100%;
	height: 100%;
	position: absolute;
	top: 0;
	left: 0;
	z-index: -1;
}
.tooltipster-arrow span, .tooltipster-arrow-border {
	display: block;
	width: 0; 
	height: 0;
	position: absolute;
}
.tooltipster-arrow-top span, .tooltipster-arrow-top-right span, .tooltipster-arrow-top-left span {
	border-left: 8px solid transparent !important;
	border-right: 8px solid transparent !important;
	border-top: 8px solid;
	bottom: -7px;
}
.tooltipster-arrow-top .tooltipster-arrow-border, .tooltipster-arrow-top-right .tooltipster-arrow-border, .tooltipster-arrow-top-left .tooltipster-arrow-border {
	border-left: 9px solid transparent !important;
	border-right: 9px solid transparent !important;
	border-top: 9px solid;
	bottom: -7px;
}

.tooltipster-arrow-bottom span, .tooltipster-arrow-bottom-right span, .tooltipster-arrow-bottom-left span {
	border-left: 8px solid transparent !important;
	border-right: 8px solid transparent !important;
	border-bottom: 8px solid;
	top: -7px;
}
.tooltipster-arrow-bottom .tooltipster-arrow-border, .tooltipster-arrow-bottom-right .tooltipster-arrow-border, .tooltipster-arrow-bottom-left .tooltipster-arrow-border {
	border-left: 9px solid transparent !important;
	border-right: 9px solid transparent !important;
	border-bottom: 9px solid;
	top: -7px;
}
.tooltipster-arrow-top span, .tooltipster-arrow-top .tooltipster-arrow-border, .tooltipster-arrow-bottom span, .tooltipster-arrow-bottom .tooltipster-arrow-border {
	left: 0;
	right: 0;
	margin: 0 auto;
}
.tooltipster-arrow-top-left span, .tooltipster-arrow-bottom-left span {
	left: 6px;
}
.tooltipster-arrow-top-left .tooltipster-arrow-border, .tooltipster-arrow-bottom-left .tooltipster-arrow-border {
	left: 5px;
}
.tooltipster-arrow-top-right span,  .tooltipster-arrow-bottom-right span {
	right: 6px;
}
.tooltipster-arrow-top-right .tooltipster-arrow-border, .tooltipster-arrow-bottom-right .tooltipster-arrow-border {
	right: 5px;
}
.tooltipster-arrow-left span, .tooltipster-arrow-left .tooltipster-arrow-border {
	border-top: 8px solid transparent !important;
	border-bottom: 8px solid transparent !important; 
	border-left: 8px solid;
	top: 50%;
	margin-top: -7px;
	right: -7px;
}
.tooltipster-arrow-left .tooltipster-arrow-border {
	border-top: 9px solid transparent !important;
	border-bottom: 9px solid transparent !important; 
	border-left: 9px solid;
	margin-top: -8px;
}
.tooltipster-arrow-right span, .tooltipster-arrow-right .tooltipster-arrow-border {
	border-top: 8px solid transparent !important;
	border-bottom: 8px solid transparent !important; 
	border-right: 8px solid;
	top: 50%;
	margin-top: -7px;
	left: -7px;
}
.tooltipster-arrow-right .tooltipster-arrow-border {
	border-top: 9px solid transparent !important;
	border-bottom: 9px solid transparent !important; 
	border-right: 9px solid;
	margin-top: -8px;
}


/* Some CSS magic for the awesome animations - feel free to make your own custom animations and reference it in your Tooltipster settings! */

.tooltipster-fade {
	opacity: 0;
	-webkit-transition-property: opacity;
	-moz-transition-property: opacity;
	-o-transition-property: opacity;
	-ms-transition-property: opacity;
	transition-property: opacity;
}
.tooltipster-fade-show {
	opacity: 1;
}

.tooltipster-grow {
	-webkit-transform: scale(0,0);
	-moz-transform: scale(0,0);
	-o-transform: scale(0,0);
	-ms-transform: scale(0,0);
	transform: scale(0,0);
	-webkit-transition-property: -webkit-transform;
	-moz-transition-property: -moz-transform;
	-o-transition-property: -o-transform;
	-ms-transition-property: -ms-transform;
	transition-property: transform;
	-webkit-backface-visibility: hidden;
}
.tooltipster-grow-show {
	-webkit-transform: scale(1,1);
	-moz-transform: scale(1,1);
	-o-transform: scale(1,1);
	-ms-transform: scale(1,1);
	transform: scale(1,1);
	-webkit-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1);
	-webkit-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-moz-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-ms-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-o-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15);
}

.tooltipster-swing {
	opacity: 0;
	-webkit-transform: rotateZ(4deg);
	-moz-transform: rotateZ(4deg);
	-o-transform: rotateZ(4deg);
	-ms-transform: rotateZ(4deg);
	transform: rotateZ(4deg);
	-webkit-transition-property: -webkit-transform, opacity;
	-moz-transition-property: -moz-transform;
	-o-transition-property: -o-transform;
	-ms-transition-property: -ms-transform;
	transition-property: transform;
}
.tooltipster-swing-show {
	opacity: 1;
	-webkit-transform: rotateZ(0deg);
	-moz-transform: rotateZ(0deg);
	-o-transform: rotateZ(0deg);
	-ms-transform: rotateZ(0deg);
	transform: rotateZ(0deg);
	-webkit-transition-timing-function: cubic-bezier(0.230, 0.635, 0.495, 1);
	-webkit-transition-timing-function: cubic-bezier(0.230, 0.635, 0.495, 2.4); 
	-moz-transition-timing-function: cubic-bezier(0.230, 0.635, 0.495, 2.4); 
	-ms-transition-timing-function: cubic-bezier(0.230, 0.635, 0.495, 2.4); 
	-o-transition-timing-function: cubic-bezier(0.230, 0.635, 0.495, 2.4); 
	transition-timing-function: cubic-bezier(0.230, 0.635, 0.495, 2.4);
}

.tooltipster-fall {
	top: 0;
	-webkit-transition-property: top;
	-moz-transition-property: top;
	-o-transition-property: top;
	-ms-transition-property: top;
	transition-property: top;
	-webkit-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1);
	-webkit-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-moz-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-ms-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-o-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
}
.tooltipster-fall-show {
}
.tooltipster-fall.tooltipster-dying {
	-webkit-transition-property: all;
	-moz-transition-property: all;
	-o-transition-property: all;
	-ms-transition-property: all;
	transition-property: all;
	top: 0px !important;
	opacity: 0;
}

.tooltipster-slide {
	left: -40px;
	-webkit-transition-property: left;
	-moz-transition-property: left;
	-o-transition-property: left;
	-ms-transition-property: left;
	transition-property: left;
	-webkit-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1);
	-webkit-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-moz-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-ms-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	-o-transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15); 
	transition-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1.15);
}
.tooltipster-slide.tooltipster-slide-show {
}
.tooltipster-slide.tooltipster-dying {
	-webkit-transition-property: all;
	-moz-transition-property: all;
	-o-transition-property: all;
	-ms-transition-property: all;
	transition-property: all;
	left: 0px !important;
	opacity: 0;
}


/* CSS transition for when contenting is changing in a tooltip that is still open. The only properties that will NOT transition are: width, height, top, and left */
.tooltipster-content-changing {
	opacity: 0.5;
	-webkit-transform: scale(1.1, 1.1);
	-moz-transform: scale(1.1, 1.1);
	-o-transform: scale(1.1, 1.1);
	-ms-transform: scale(1.1, 1.1);
	transform: scale(1.1, 1.1);
}
$cssplus

/** RESET AND LAYOUT
===================================*/

.bx-wrapper {
	position: relative;
	margin: 0 auto 60px;
	padding: 0;
	*zoom: 1;
}

.bx-wrapper img {
	display: block;
}

/** THEME
===================================*/
.bx-wrapper{
	margin: -15px auto;
}

.bx-wrapper .bx-viewport {
	-moz-box-shadow: 0 0 5px #ccc;
	-webkit-box-shadow: 0 0 5px #ccc;
	box-shadow: 0 0 5px #ccc;
	border:  5px solid #fff;
	left: -5px;
	background: #fff;
	
	/*fix other elements on the page moving (on Chrome)*/
	-webkit-transform: translatez(0);
	-moz-transform: translatez(0);
    	-ms-transform: translatez(0);
    	-o-transform: translatez(0);
    	transform: translatez(0);
}
.form a {
    color: black;
}
.form a:visited {
    color: black;
}

.autocomplete-suggestions {
    text-align: left; cursor: default; border: 1px solid #ccc; border-top: 0; background: #fff; box-shadow: -1px 1px 3px rgba(0,0,0,.1);
    position: absolute; display: none; z-index: 9999; max-height: 254px; overflow: hidden; overflow-y: auto; box-sizing: border-box;
}
.autocomplete-suggestion { position: relative; padding: 0 .6em; line-height: 23px; white-space: nowrap; overflow: hidden; font-size: 18px; color: #333; }
.autocomplete-suggestion b { font-weight: normal; color: #$Green; }
.autocomplete-suggestion.selected { background: #f0f0f0; }

.calamaris th {
    background: none repeat scroll 0 0 transparent;
    border: 0 none;
    color: black;
    font-size: 22px;
    text-align:inherit;
}

.calamaris A{
	text-decoration:underline;
}

.backToTop{
	text-decoration:underline;
	font-size:18px;
}

.headline{
	color: black;
	font-size: 30px;
	background: none repeat scroll 0 0 transparent;
}
.calamaris td {
    font-size: 22px;
    padding:3px;
    border: 1px solid #D6D6D6;
    text-align:initial;
}
.calamaris .head {
    font-size: 26px;
    font-weight:bold;
    background-color:$Green;
    color:white;
    padding:15px 5px 15px;
    
}

.calamaris .head A {
   text-decoration:none;
    
}

.calamaris table {
	border-spacing: 0;
    border-collapse: collapse;
    border:5px solid #CCCCCC;
    border-radius: 5px; 
    -moz-border-radius: 5px;
    width:100%;
}

.calamaris .TableDefinition{
	text-align:left;
	background-color:#CCCCCC;
	font-size: 26px;
	font-weight:bolder;
	padding:0px;
	text-align:right;

}
.calamaris .line_0{
	font-size: 22px;
	font-weight:normal;
	padding:0px;
	text-align:right;
	text-transform:lowercase;
	padding-right:8px;

}
.calamaris .line_1{
	font-size: 22px;
	font-weight:normal;
	background-color:#DEDEDE;
	text-align:right;
	text-transform:lowercase;
	padding-right:8px;

}
.calamaris .line_2{
	font-size: 26px;
	font-weight:bolder;
	background-color:#909090;
	padding:0px;
	color:white;
	text-align:right;
	padding-right:8px;

}
.calamaris address {
	display:none;
}

.calamaris .head a {
    color: white;
}
.calamaris .head a:visited {
    color: white;
}
";

if($ForceDefaultGreenColor<>null){
$MAINCSS=$MAINCSS."
body {
	background-image:none !important;
	background-color:#$ForceDefaultGreenColor !important
}

.ui-widget-header {
	background-image:none !important;
	background-color:#$ForceDefaultGreenColor !important
}

";

if($ForceDefaultButtonColor<>null){
$MAINCSS=$MAINCSS."
.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default {
		background: none !important;
		background-color:#$ForceDefaultButtonColor !important
}

.ui-state-active a, .ui-state-active a:link, .ui-state-active a:visited {
  background-color: white !important;
  color: #$ForceDefaultGreenColor !important;
}

.ui-state-hover a, .ui-state-hover a:hover {
    color: #$ForceDefaultButtonColor !important;
    background-color:#$ForceDefaultGreenColor !important;
    border:1px solid white !important;
}

.TopObjectsOver {
    background-color: #$ForceDefaultButtonColor !important;
}
";
	
}
	
}
if($font_family_org<>null){
	$MAINCSS=$MAINCSS."@font-face {
font-family: \"$font_family_org\" !important;
	src: none !important;
}\n";
}
if($ForceDefaultTopBarrColor<>null){
$MAINCSS=$MAINCSS."
#template-top-menus{
	background-color:#$ForceDefaultTopBarrColor !important;
}\n";
}


$_SESSION["FONT_CSS"]=$MAINCSS;
echo $MAINCSS;