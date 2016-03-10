<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.meta.squid.acls.inc');

$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){$tpl=new templates();echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

header("content-type: application/x-javascript");
$tpl=new templates();
$page=CurrentPageName();
$t=time();
$ask=$tpl->javascript_parse_text("{js_groupad_add}");
$html="
function Save$t(){
	var group=prompt('$ask');
	var field='{$_GET["field-user"]}';
	if(!group){return;}
	document.getElementById(field).value=group;

}
		
Save$t();		
		
";

echo $html;