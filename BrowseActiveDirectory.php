<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($argv[1]=='--verbose'){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($GLOBALS["VERBOSE"]){echo "<H1>VERBOSED</H1>";}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

	


	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["users-list"])){users_list();exit;}
	if(isset($_GET["UsersGroup-js"])){UsersBrowse_js();exit;}
	if(isset($_GET["UsersGroup-popup"])){UsersBrowse_popup();exit;}
	if(isset($_GET["UsersGroup-list"])){UsersBrowselist();exit;}
	if(isset($_GET["var-export-js"])){var_export_js();exit;}
	if(isset($_GET["var-export-popup"])){var_export_popup();exit;}
	if(isset($_GET["var-export-tabs"])){var_export_tabs();exit;}
	if(isset($_GET["var-dump-ad-group"])){var_export_popup();exit;}
	if(isset($_GET["var-dump-ad-members"])){var_export_members();exit;}
	
	js();	

function js(){
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	if(!is_numeric($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	if(!is_numeric($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}");
	$html="YahooSearchUser('650','$page?popup=yes&field-user={$_GET["field-user"]}&OnlyGroups={$_GET["OnlyGroups"]}&ADID={$_GET["ADID"]}&OnlyUsers={$_GET["OnlyUsers"]}','$title');";
	echo $html;
	
}
function UsersBrowse_js(){
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	if(!is_numeric($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}::{$_GET["GroupName"]}");
	$html="YahooLogWatcher('550','$page?UsersGroup-popup=yes&dn={$_GET["dn"]}&ADID={$_GET["ADID"]}','$title');";
	echo $html;
	
}

function var_export_js(){
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	if(!is_numeric($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}::DUMP::{$_GET["cn"]}");
	$html="YahooWinS('874','$page?var-export-tabs=yes&data={$_GET["var-export-js"]}&ADID={$_GET["ADID"]}','$title');";
	echo $html;	
	
}

function var_export_tabs(){
	
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$array["var-dump-ad-group"]='{group}';
	$array["var-dump-ad-members"]='{members}';
	$t=$_GET["t"];
	

	
	
	if(!is_numeric($t)){$t=time();}
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&data={$_GET["data"]}&ADID={$_GET["ADID"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_ad_dump_group style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_ad_dump_group').tabs();
			
			
			});
		</script>";		
	
	
	
}


function var_export_popup($array=null,$return=false){
	if(!is_array($array)){
		$dn=base64_decode($_GET["data"]);
		$dnText=utf8_decode($dn);
		$ad=new ActiveDirectory($_GET["ADID"]);
		$array=$ad->DumpDN($dn);
		if($ad->ldap_last_error<>null){
			echo "<div style='color:#d32d2d;font-size:12px'>$ad->ldap_last_error<hr></div>";
		}
	}else{
		if($dnText==null){
			$dn=base64_decode($_GET["data"]);
			$dnText=utf8_decode($dn);	
		}
		
		
	}
	
	
	
	if(!is_array($array)){
		echo "<div style='color:#d32d2d;font-size:12px'>Not an Array()<hr>".base64_decode($_GET["data"])."<hr></div>";
	}
	$html="<table>";
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		if(is_array($ligne)){
			$ligne=var_export_popup($ligne,true);
		}else{
			$ligne=utf8_decode($ligne);
			$ligne=htmlentities($ligne);
			$ligne=str_replace("'", "`", $ligne);
		}
		
		$html=$html."
		<tr>
			<td class=legend style='font-size:13px' valign='top'>$num:</td>
			<td style='font-size:13px'><strong>$ligne</strong></td>
		</tr>
		
		";
		
		
	}
	
	$html=$html."</table>";
	if($return){return $html;}
	echo "
	<div style='font-size:16px'>DN:$dnText</div>
	<div style='width:95%;height:450px;overflow:auto' class=form>$html</div>";
	
	
}

function var_export_members(){
	
	if(!is_array($array)){
			$dn=base64_decode($_GET["data"]);
			$dnText=utf8_decode($dn);
			$ad=new ActiveDirectory($_GET["ADID"]);
			if($ad->ldap_last_error<>null){
				echo "<div style='color:#d32d2d;font-size:12px'>$ad->ldap_last_error<hr></div>";
			}	
		
		}
		
		//$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
		if(!is_numeric($entriesNumber)){$entriesNumber=50;}
		$res=@ldap_read($ad->ldap_connection,$dn,"(objectClass=*)",array("member","MemberOf"),null,$entriesNumber,20);
		
		$log[]="Parse DN: $dn for member, MemberOf";
		
		
		
		if(!$res){
			$log[]='Error LDAP search number ' . ldap_errno($ad->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n$filter\n" . 
			ldap_err2str(ldap_errno($ad->ldap_connection));
			echo @implode("<br>", $log);
			return array();
		}
		
		
		$hash=ldap_get_entries($ad->ldap_connection,$res);
		$log[]="Attribute member =".$hash[0]["member"]["count"];		
		
		
		
		
	for($i=0;$i<$hash[0]["member"]["count"];$i++){
			$dn=$hash[0]["member"][$i];
			$log[]="Found dn = &laquo;$dn&raquo;";
			if($dn==null){continue;}
			$log[]="Dump dn = &laquo;$dn&raquo;";
			$Props=$ad->DumpDN($dn);
			if(!is_array($Props)){continue;}
			
			$html=$html."<table style='width:99%' class=form>
			<tr>
				<td colspan=2 style='font-size:16px;'> &laquo;$dn&raquo;</td>
			</tr>
			";
			
			while (list ($num, $ligne) = each ($Props) ){	
				if(is_array($ligne)){
					$ligne=var_export_popup($ligne,true);
				}else{
					$ligne=utf8_decode($ligne);
					$ligne=htmlentities($ligne);
					$ligne=str_replace("'", "`", $ligne);
				}
				
				$html=$html."
				<tr>
					<td class=legend style='font-size:13px' valign='top'>$num:</td>
					<td style='font-size:13px'><strong>$ligne</strong></td>
				</tr>
				
				";
			}
			
			$html=$html."</table>";
	}

	
	echo "</div style='font-size:12px'><code>".@implode("<br>", $log)."</div>$html"; 
		

}

function UsersBrowse_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$OnlyGroups=$_GET["OnlyGroups"];
	$groups=$tpl->_ENGINE_parse_body("{adgroups}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}");
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	$memberssearch="{display: '$members', name : 'members'},";
	if($OnlyGroups==1){$memberssearch=null;}
	
	$html="
	<div style='margin-right:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>	
	<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?UsersGroup-list=yes&dn={$_GET["dn"]}&ADID={$_GET["ADID"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zDate', width :31, sortable : false, align: 'left'},
		{display: '$members', name : 'members', width : 421, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'left'},
		],
	
	searchitems : [
		{display: '$members', name : 'adgroups'},
		
		
		],
	sortname: 'members',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 540,
	height: 300,
	singleSelect: true,
	rpOptions: [50,100,200,500,1000]
	
	});   
});
</script>";
	echo $html;
}

function ParseUsersGroups($dn,$search){
	$ad=new ActiveDirectory($_GET["ADID"]);
	$Array=$ad->search_users_from_group($dn);
	$CurPage=CurrentPageName();
	if($ad->ldap_last_error<>null){json_error_show("$dn  $ad->ldap_last_error",1);}
	if(count($Array)==0){return;}
	while (list ($dn, $GPARR) = each ($Array) ){
		if($search<>null){if(!preg_match("#$search#i", serialize($GPARR))){continue;}}
		$icon="user-32.png";
		$dnEnc=base64_encode($dn);
		$type=$GPARR["TYPE"];
		
		$GroupxName=$GPARR["cn"];
		$GroupxName=str_replace("'", "`", $GroupxName);
		$GroupxName=replace_accents($GroupxName);
		$descriptions=array();
		$c++;
		while (list ($a, $b) = each ($GPARR) ){
			$GPARR[$a]=utf8_encode($b);
		}
		
		$cn=htmlentities($GPARR["cn"]);
		$cn=str_replace("'", "`", $cn);
		
		$description=$GPARR["description"];
		$description=htmlentities($description);
		$description=str_replace("'", "`", $description);		
		
		if($type=="group"){
			$icon="win7groups-32.png";
			$js="Loadjs('$CurPage?UsersGroup-js=yes&GroupName=$GroupxName&dn=$dnEnc&ADID={$_GET["ADID"]}')";
			if($ad->LDAP_RECURSIVE==1){
				writelogs("Group -> ParseUsersGroups($dn,$search)",__FUNCTION__,__FILE__,__LINE__);
				$newrow=ParseUsersGroups($dn,$search);
				if(count($newrow>0)){
					while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
				}
			}
			
			
		}else{
			$cn=$GPARR["uid"];
			if(strlen($description)>2){
				$descriptions[]=$description;
			}
			if(strlen(trim($GPARR["name"]))>0){
				$descriptions[]="<strong>{$GPARR["name"]}</strong>";
			}
			if(strlen($GPARR["displayname"])>0){
				$descriptions[]="<strong>$displayname:</strong>&nbsp;{$GPARR["displayname"]}";
			}
			if(strlen($GPARR["userprincipalname"])>0){
				$descriptions[]="<strong>$account</strong>:&nbsp;".$GPARR["userprincipalname"];
			}		

			
			
			$js="Loadjs('ActiveDirectory.user.php?dn=$dnEnc&ADID={$_GET["ADID"]}')";
			$js=null;	
		}
		
		$icon=imgsimple($icon,null,"Loadjs('$CurPage?var-export-js=$dnEnc&cn=$cn&ADID={$_GET["ADID"]}')");
		$link="<a href=\"javascript:blur();\" Onclick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>";
		if($js==null){$link="<span style='font-size:14px;'>";}
		
		$md5=md5($dn);
		$f[]=array('id' => $md5,'cell' => array($icon,"<span style='font-size:14px;'>$cn</a></span><div style='font-size:11px'>".@implode("<br>", $descriptions)."</div>",$delete ));
	}
	
	return $f;
}


function UsersBrowselist(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." line ".__LINE__."<br>\n";}
	$CurPage=CurrentPageName();
	$tpl=new templates();
	$search=$_POST["query"];
	$dn=base64_decode($_GET["dn"]);
	$ad=new ActiveDirectory($_GET["ADID"]);
	$Array=$ad->search_users_from_group($dn,$_POST["rp"]);
	
	if($ad->ldap_last_error<>null){json_error_show("$dn  $ad->ldap_last_error",1);}
	if(count($Array)==0){json_error_show("$dn no such user",1);}

	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($Array);
	$data['rows'] = array();	
	$rp=$_POST["rp"];
	if(!is_numeric($rp)){$rp=15;}
	if($rp<10){$rp=15;}
	
	$displayname=$tpl->_ENGINE_parse_body("{displayname}");
	$account=$tpl->_ENGINE_parse_body("{account}");
	$search=$_POST["query"];
	
	if($search<>null){
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}
	
	$c=0;
	while (list ($dn, $GPARR) = each ($Array) ){
		if($search<>null){ if(!preg_match("#$search#i", serialize($GPARR))){continue;} }
		$icon="user-32.png";
		$dnEnc=base64_encode($dn);
		$type=$GPARR["TYPE"];
		
		$GroupxName=$GPARR["cn"];
		$GroupxName=str_replace("'", "`", $GroupxName);
		$GroupxName=replace_accents($GroupxName);
		$descriptions=array();
		while (list ($a, $b) = each ($GPARR) ){ $GPARR[$a]=utf8_encode($b); }
		
		$cn=htmlentities($GPARR["cn"]);
		$cn=str_replace("'", "`", $cn);
		
		$description=$GPARR["description"];
		$description=htmlentities($description);
		$description=str_replace("'", "`", $description);
		
		if($type=="group"){
			$icon="win7groups-32.png";
			$js="Loadjs('$CurPage?UsersGroup-js=yes&GroupName=$GroupxName&dn=$dnEnc&ADID={$_GET["ADID"]}')";
			if($ad->LDAP_RECURSIVE==1){
				writelogs("Group -> ParseUsersGroups($dn,$search)",__FUNCTION__,__FILE__,__LINE__);
				$newrow=ParseUsersGroups($dn,$search);
				if(count($newrow)>0){
					while (list ($a, $b) = each ($newrow) ){
						$data['rows'][]=$b;
						$c++;
						if(!$c>$rp){
							$data['total'] = $c;
							echo json_encode($data);
							return;
						}
		
					}
				}
			}
		}else{
			$cn=$GPARR["uid"];
			if(strlen($description)>2){
				$descriptions[]=$description;
			}
			if(strlen(trim($GPARR["name"]))>0){
				$descriptions[]="<strong>{$GPARR["name"]}</strong>";
			}
			if(strlen($GPARR["displayname"])>0){
				$descriptions[]="<strong>$displayname:</strong>&nbsp;{$GPARR["displayname"]}";
			}
			if(strlen($GPARR["userprincipalname"])>0){
				$descriptions[]="<strong>$account</strong>:&nbsp;".$GPARR["userprincipalname"];
			}
		
				
				
			$js="Loadjs('ActiveDirectory.user.php?dn=$dnEnc&ADID={$_GET["ADID"]}')";
			$js=null;
		}
		
		$icon=imgsimple($icon,null,"Loadjs('$CurPage?var-export-js=$dnEnc&cn=$cn&ADID={$_GET["ADID"]}')");
		$link="<a href=\"javascript:blur();\" Onclick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>";
		if($js==null){$link="<span style='font-size:14px;'>";}
		
		$md5=md5($dn);
		$c++;
		$data['rows'][] = array('id' => $md5,'cell' => array($icon,"<span style='font-size:14px;'>$cn</a></span><div style='font-size:11px'>".@implode("<br>", $descriptions)."</div>",$delete ) );
		if(count($data['rows'])>$rp){
			$data['total'] = $c;
			echo json_encode($data);
			return;
		}	
	}
	
	$data['total'] = $c;
	echo json_encode($data);	
}





function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$OnlyGroups=$_GET["OnlyGroups"];
	if(!is_numeric($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	$OnlyUsers=$_GET["OnlyUsers"];
	$groups=$tpl->_ENGINE_parse_body("{adgroups}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}");
	$field=$_GET["field-user"];
	$groupsSearch="{display: '$groups', name : 'adgroups'},";
	$memberssearch="{display: '$members', name : 'members'},";
	if($OnlyGroups==1){$memberssearch=null;}
	if($OnlyUsers==1){$groupsSearch=null;}
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}

	
	
	$html="
	<div style='margin-right:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>	
	<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?users-list=yes&ADID={$_GET["ADID"]}&OnlyGroups={$_GET["OnlyGroups"]}&OnlyUsers={$_GET["OnlyUsers"]}&field-user={$_GET["field-user"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zDate', width :31, sortable : false, align: 'left'},
		{display: '$members', name : 'members', width : 528, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'left'},
		],
	
	searchitems : [
		$groupsSearch
		$memberssearch
		
		],
	sortname: 'members',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 652,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function SelectAdGroup$t(dnenc){
		var field='$field';
		if(field.length==0){return;}
		if(document.getElementById('$field')){
			document.getElementById('$field').value='AD:{$_GET["ADID"]}:'+dnenc;
			YahooSearchUserHide();
			YahooLogWatcherHide();
		}
	
	}
	
	
	function SelectAdUser$t(uid){
		var field='$field';
		if(field.length==0){return;}
		if(document.getElementById('$field')){
			document.getElementById('$field').value=uid;
			YahooSearchUserHide();
			YahooLogWatcherHide();
		}	
	
	}




function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
			LoadAjax('table-1-selected','$page?familysite-show='+id);
		}
	}
	 
	$('table-1-selected').remove();
	$('flex1').remove();		 

</script>
	
	
	";
	
	echo $html;
	

	
	
}

function users_list(){
	$tpl=new templates();
	$CurPage=CurrentPageName();
	$search=$_POST["query"];
	
	$t=$_GET["t"];
	$ad=new ActiveDirectory();
	if(!is_numeric($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	
	
	
	if($_GET["OnlyUsers"]==0){
		$OnlyGroups=1;
		$icon="win7groups-32.png";
		$Array=$ad->search_groups($search,$_POST["rp"]);
		if($ad->ldap_last_error<>null){json_error_show($ad->ldap_last_error,1);}
	}else{
		$OnlyUsers=1;
		$OnlyGroups=0;
		writelogs("->UserSearch(null,$search,{$_POST["rp"]}",__FUNCTION__,__FILE__,__LINE__);
		$icon="user-32.png";
		$Array=$ad->UserSearch_formated(null,$search,$_POST["rp"]);
		if($ad->ldap_last_error<>null){json_error_show($ad->ldap_last_error,1);}
		
	}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($Array);
	$data['rows'] = array();	
	$members=$tpl->_ENGINE_parse_body("{members}");
	$c=0;
	while (list ($dn, $GPARR) = each ($Array) ){
		$dnEnc=base64_encode($dn);
		$GroupxSourceName=$GPARR[0];
		$GroupxName=$GPARR[0];
		$GroupxName=replace_accents($GroupxName);
		$GPARR[0]=htmlentities($GPARR[0]);
		$GPARR[0]=str_replace("'", "`", $GPARR[0]);
		$GroupxName=str_replace("'", "`", $GroupxName);
		$GPARR[1]=htmlentities($GPARR[1]);
		$GPARR[1]=str_replace("'", "`", $GPARR[1]);	
		$link="<span style='font-size:14px;'>";
		$addtitile=null;
		$select=null;			
		
		if($OnlyGroups==1){
			$js="Loadjs('$CurPage?UsersGroup-js=yes&GroupName=$GroupxName&dn=$dnEnc&ADID={$_GET["ADID"]}')";	
			$link="<a href=\"javascript:blur();\" Onclick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>";
			$addtitile=" <span style='font-size:11px'>({$GPARR[2]} $members)</span>";
			
			$select=imgtootltip("arrow-right-24.png",null,"SelectAdGroup$t('$dnEnc')");
			
			if($GPARR[2]==0){
				$link="<span style='font-size:14px;'>";
				$addtitile=null;
				
			}
			
			
		}
		
		$image=imgsimple($icon,null,"Loadjs('$CurPage?var-export-js=$dnEnc&cn=$cn&ADID={$_GET["ADID"]}')");
		
		if($OnlyUsers==1){
			$icon="user-32.png";
			
			
			$select=imgtootltip("arrow-right-24.png",null,"SelectAdUser$t('$GroupxSourceName')");
			$image=imgsimple($icon);
			$link="<a href=\"javascript:blur();\" Onclick=\"javascript:SelectAdUser$t('$GroupxSourceName')\" 
			style='font-size:16px;text-decoration:underline;font-weight:bold'>";
			if($GPARR[1]<>null){
				$addtitile=" <span style='font-size:14px'><i>{$GPARR[1]}</i></span>";
			}
			$substr=substr($GroupxSourceName, strlen($GroupxSourceName)-1,1);
			if($substr=="$"){
				if($_GET["OnlyUsers"]==1){continue;}
				$GPARR[0]=str_replace("$", "", $GPARR[0]);
				$icon="computer-32.png";
				$image=imgsimple($icon);
				$link="<span style='font-size:16px;font-weight:bold'>";
				$addtitile=null;
				$select="&nbsp;";
			}
			$GPARR[1]=null;
			
		}
		
		
		if($GPARR[0]==null){continue;}
		
		
		$c++;
		
		$md5=md5($dn);
		$data['rows'][] = array(
			'id' => $md5,
			'cell' => array(
				$image,
				"<span style='font-size:14px;'>$link{$GPARR[0]}</a>$addtitile</span><div style='font-size:11px'>{$GPARR[1]}</div>",
				$select )
			);		
	}
	
	$data['total'] = $c;
	echo json_encode($data);	
}
?>