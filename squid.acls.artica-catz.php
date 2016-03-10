<?php
    //ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.squid.acls.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	
	
	
	if(!isset($_GET["t"])){$_GET["t"]=time();}
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	
	$user=new usersMenus();
	if(($user->AsSquidAdministrator==false) OR ($user->AsDansGuardianAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["Save"])){Save();exit;}
	
	js();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{artica_categories}");
		echo "YahooWinT(1025,'$page?popup=yes&t={$_GET["t"]}','$title')";
	}
	

	
function popup(){
	$t=$_GET["t"];
	$CommonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	
	$tt=time();
	$button_save=button("{import}", "SaveCRT$tt()",35);
	
	
	$html="
<div class=explain style='font-size:18px' id='$tt-adddis'>{artica_categories_bulk_import}</div>
		
		
<textarea
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px' id='crt$tt'></textarea>
		<center style='margin:10px'>$button_save</center>
<script>
	var x_SaveCRT$tt=function (obj) {
	var results=obj.responseText;
	if (results.length>3){
		document.getElementById('crt$tt').value=results;
		if(document.getElementById('ACL_ID_MAIN_TABLE')){ $('#'+document.getElementById('ACL_ID_MAIN_TABLE').value).flexReload(); }
	}else{
		document.getElementById('crt$tt').value='Error!!!';
	}
}
function SaveCRT$tt(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('crt$tt').value);
	XHR.appendData('Save',pp);
	XHR.sendAndLoad('$page', 'POST',x_SaveCRT$tt);
}
</script>
	";
echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	$acl=new squid_acls_groups();
	$DATAS=url_decode_special_tool($_POST["Save"]);
	$MAIN=explode("\n",$DATAS);
	
	while (list ($index, $ligne) = each ($MAIN) ){
		if(strpos($ligne,";")==0){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
		$tr=explode(";",$ligne);
		if(count($tr)<2){echo "$ligne -> FALSE\n";continue;}
		$GroupName=$tr[0];
		$Categories=explode(",",$tr[1]);
		if($Categories[0]==null){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
			
		$gpid=GetADGroupID($GroupName);
		echo "$GroupName ID $gpid\n";
		if($gpid==0){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
			
		$Category_groupnane="$GroupName - categories";
		$CategoryID=GetCategoryGroupID($GroupName);
		if($CategoryID==0){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
		
		FillCategoryItems($CategoryID,$Categories);
		
		
		$RuleName="$GroupName - categories";
		$RuleID=GetRuleID($RuleName);
		if($RuleID==0){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
		
		
		AssociatesG($RuleID,$gpid,0);
		AssociatesG($RuleID,$CategoryID,1);
		
		if(!$acl->aclrule_edittype($RuleID,"url_rewrite_access_allow",1)){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
		if(!$acl->aclrule_edittype($RuleID,"url_rewrite_access_deny",1)){echo "$ligne -> FALSE ERR.".__LINE__."\n";continue;}
		echo "Associates {$RuleName}[$RuleID] to $CategoryID,$gpid ($Category_groupnane,$GroupName) [OK]\n";
		
		
		
		
	}

	
}


function GetCategoryGroupID($GroupName){
	$q=new mysql_squid_builder();
	$sql="SELECT ID FROM webfilters_sqgroups WHERE GroupName='$GroupName' AND GroupType='categories'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(intval($ligne["ID"])==0){
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled) VALUES ('$GroupName','categories',1)");
		$sql="SELECT ID FROM webfilters_sqgroups WHERE GroupName='$GroupName' AND GroupType='categories'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	return $ligne["ID"];	
	
	
}

function FillCategoryItems($gpid,$array){
	$q=new mysql_squid_builder();
	while (list ($index, $category) = each ($array) ){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilters_sqitems WHERE pattern='$category' AND gpid=$gpid"));
		if(intval($ligne["ID"])==0){
			$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqitems (pattern,gpid,enabled) VALUES ('$category','$gpid',1)");
		}
		
	}
	
}
function GetRuleID($RuleName){
	$q=new mysql_squid_builder();
	$sql="SELECT ID FROM webfilters_sqacls WHERE aclname='$RuleName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(intval($ligne["ID"])==0){
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqacls (aclname,enabled,aclgpid) VALUES ('$RuleName',1,0)");
		$sql="SELECT ID FROM webfilters_sqacls WHERE aclname='$RuleName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	return $ligne["ID"];	
	
	
}

function AssociatesG($aclid,$gpid,$order){
	$q=new mysql_squid_builder();
	$md5=md5($aclid.$gpid);
	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE zmd5='$md5'");
	$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)");
	
	
}


function GetADGroupID($GroupName){
	
	$q=new mysql_squid_builder();
	$sql="SELECT ID FROM webfilters_sqgroups WHERE GroupName='$GroupName' AND GroupType='proxy_auth_ads'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(intval($ligne["ID"])==0){
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled) VALUES ('$GroupName','proxy_auth_ads',1)");
		$sql="SELECT ID FROM webfilters_sqgroups WHERE GroupName='$GroupName' AND GroupType='proxy_auth_ads'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	return $ligne["ID"];
}
