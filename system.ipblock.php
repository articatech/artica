<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsArticaAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
	}	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){enable_form();exit;}
	if(isset($_GET["EnableIpBlocks"])){save();exit;}
	if(isset($_GET["list"])){list_threats();exit;}
	if(isset($_GET["ipchecks-list"])){list_threats_list();exit;}
	if(isset($_POST["cn"])){SaveIpChecks();exit;}
	if(isset($_GET["show"])){show();exit;}
	
popup();


function list_threads_perform(){
	$se=$_GET["se"];
	$se=str_replace("*",".+",$se);
	$se=str_replace(".","\.",$se);
	
	$tpl=new templates();
$list=unserialize(@file_get_contents("ressources/logs/EnableEmergingThreatsBuild.db"));
	if(!is_array($list)){
		echo $tpl->_ENGINE_parse_body("<H2>{ERROR_NO_DATA}</H2>");
		return;
	}
	
$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99.5%'>
<thead class='thead'>
	<tr>
	<th colspan=2>{$list["COUNT"]} {rules}</th>
	</tr>
</thead>
<tbody class='tbody'>";
$count=0;
while (list ($num, $ligne) = each ($list["THREADS"]) ){
if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(trim($se)<>null){if(!preg_match("#$se#",$ligne)){continue;}}
		$count++;
		$html=$html.
		"<tr class=$classtr>
			<td width=1%><img src='img/dns-cp-22.png'></td>
			<td><strong style='font-size:11px'>$ligne</td>
		</tr>";
		if($count>500){break;}
		
	}

	$html=$html."</table>";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function list_threats(){
		$page=CurrentPageName();
		$tpl=new templates();
		$t=time();
		$countries=$tpl->_ENGINE_parse_body("{countries}");
		$enable=$tpl->_ENGINE_parse_body("{enable}");
		
		
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var IptableRow$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?ipchecks-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:20px>$countries</span>', name : 'countries', width :850, sortable : false, align: 'left'},
		{display: '<span style=font-size:20px>$enable</span>', name : 'disable', width :120, sortable : true, align: 'center'},
		
		
	],
	$buttons
	
	searchitems : [
		{display: '$countries', name : 'countries'},	
		],	
	
	sortname: 'saved_date',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$countries</span>',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});

function ShowBlockIPList(num,title){
		YahooWin4('550','$page?show='+num,title);
	}
	
	
	function x_EnableDisableIpCheck(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
	}	
	
	function EnableDisableIpCheck(cn){
		var XHR = new XHRConnection();
		var val=0;
		if(document.getElementById(cn).checked){val=1;}
		XHR.appendData('cn',cn);
		XHR.appendData('val',val);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableIpCheck);			
		}
</script>

";		

	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function show(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sql="SELECT * FROM ipblocks_db WHERE country='{$_GET["show"]}'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code>$sql</code>";}	
	
		$html="
		<div style='width:100%;height:550px;overflow:auto'>
		<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
	<thead class='thead'>
		<tr>
		<th>{cdir}</th>
	</thead>
	<tbody class='tbody'>";	
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr class=$classtr>
		<td style='font-size:14px' colspan=2>{$ligne["cdir"]}</td>
		</tr>
		
		";
	}
	$html=$html."</table></div>";			
	echo $tpl->_ENGINE_parse_body($html);	
}

function list_threats_list(){
	$page=CurrentPageName();
	$q=new mysql();
	$q->BuildTables();	
	$sock=new sockets();
	$tpl=new templates();
	$list=array("af"=>"afghanistan",
	"al"=>"albania","dz"=>"algeria","as"=>"samoa","ad"=>"andorra","ao"=>"angola","ai"=>"anguilla",
	"ag"=>"barbuda","ar"=>"argentina","am"=>"armenia","aw"=>"aruba","au"=>"australia",
	"at"=>"austria","az"=>"azerbaijan","bs"=>"bahamas","bh"=>"bahrain","bd"=>"bangladesh","bb"=>"barbados","by"=>"belarus","be"=>"belgium","bz"=>"belize","bj"=>"benin","bm"=>"bermuda","bt"=>"bhutan","bo"=>"bolivia","ba"=>"herzegovina","bw"=>"botswana","br"=>"brazil","io"=>"territory","bn"=>"darussalam","bg"=>"bulgaria","bf"=>"faso","bi"=>"burundi","kh"=>"cambodia","cm"=>"cameroon","ca"=>"canada","ky"=>"islands","cf"=>"republic","cl"=>"chile","cn"=>"china","co"=>"colombia",
	"cd"=>"Democratic republic of congo","ck"=>"islands","cr"=>"rica","ci"=>"ivoire","hr"=>"croatia","cu"=>"cuba","cy"=>"cyprus",
	"cz"=>"republic","dk"=>"denmark","dj"=>"djibouti","do"=>"republic","ec"=>"ecuador","eg"=>"egypt","sv"=>"salvador","er"=>"eritrea",
	"ee"=>"estonia","et"=>"ethiopia","fo"=>"islands","fj"=>"fiji","fi"=>"finland","fr"=>"france","gf"=>"guiana","pf"=>"polynesia","ga"=>"gabon","gm"=>"gambia","ge"=>"georgia","de"=>"germany","gh"=>"ghana","gi"=>"gibraltar","gr"=>"greece","gl"=>"greenland","gd"=>"grenada","gu"=>"guam","gt"=>"guatemala","gw"=>"bissau","gy"=>"guyana","ht"=>"haiti","hn"=>"honduras","hk"=>"kong","hu"=>"hungary","is"=>"iceland","in"=>"india","id"=>"indonesia","ir"=>"of","iq"=>"iraq","ie"=>"ireland","il"=>"israel","it"=>"italy","jm"=>"jamaica","jp"=>"japan","jo"=>"jordan","kz"=>"kazakhstan","ke"=>"kenya","ki"=>"kiribati","kr"=>"of","kw"=>"kuwait","kg"=>"kyrgyzstan","la"=>"republic","lv"=>"latvia","lb"=>"lebanon","ls"=>"lesotho","lr"=>"liberia","ly"=>"jamahiriya","li"=>"liechtenstein","lt"=>"lithuania","lu"=>"luxembourg","mo"=>"macao","mk"=>"of","mg"=>"madagascar","mw"=>"malawi","my"=>"malaysia","mv"=>"maldives","ml"=>"mali","mt"=>"malta","mr"=>"mauritania","mu"=>"mauritius","mx"=>"mexico","fm"=>"of","md"=>"of","mc"=>"monaco","mn"=>"mongolia","ma"=>"morocco","mz"=>"mozambique","mm"=>"myanmar","na"=>"namibia","nr"=>"nauru","np"=>"nepal","nl"=>"netherlands","an"=>"antilles","nc"=>"caledonia","nz"=>"zealand","ni"=>"nicaragua","ne"=>"niger","ng"=>"nigeria","nu"=>"niue","nf"=>"island","mp"=>"islands","no"=>"norway","om"=>"oman","pk"=>"pakistan","pw"=>"palau","ps"=>"occupied","pa"=>"panama","pg"=>"guinea","py"=>"paraguay","pe"=>"peru","ph"=>"philippines","pl"=>"poland","pt"=>"portugal","pr"=>"rico","qa"=>"qatar","ro"=>"romania","ru"=>"federation","rw"=>"rwanda","kn"=>"nevis","lc"=>"lucia","ws"=>"samoa","sm"=>"marino","sa"=>"arabia","sn"=>"senegal","sc"=>"seychelles","sl"=>"leone","sg"=>"singapore","sk"=>"slovakia","si"=>"slovenia","sb"=>"islands","za"=>"africa","es"=>"spain","lk"=>"lanka","sd"=>"sudan","sr"=>"suriname","sz"=>"swaziland","se"=>"sweden","ch"=>"switzerland","sy"=>"republic","tw"=>"china","tj"=>"tajikistan","tz"=>"of","th"=>"thailand","tg"=>"togo","to"=>"tonga","tt"=>"tobago","tn"=>"tunisia","tr"=>"turkey","tm"=>"turkmenistan","tv"=>"tuvalu","ug"=>"uganda","ua"=>"ukraine","ae"=>"emirates","gb"=>"kingdom","us"=>"states","uy"=>"uruguay","uz"=>"uzbekistan","vu"=>"vanuatu","ve"=>"venezuela","vn"=>"nam","vg"=>"british","ye"=>"yemen","zm"=>"zambia","zw"=>"zimbabwe");	
	
	ksort($list);
	
	$sql="SELECT * FROM ipblocks_set";

	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code>$sql</code>";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$cn[$ligne["country"]]=1;
	}
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	$c=0;
	
if($_POST["query"]<>null){
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
		$pattern=$_POST["query"];
}	
	
	while (list ($num, $ligne) = each ($list) ){
		if($pattern<>null){
			if(!preg_match("#$pattern#",$ligne)){continue;}
		}
		$c++;
		$href="<a href=\"javascript:blur();\" 
		OnClick=\"ShowBlockIPList('$num','{$list[$num]}');\" style='font-size:24px;text-decoration:underline'>";
		
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
					"$href$ligne ($num)</a>",
					"". Field_checkbox("$num", 1,$cn[$num],"EnableDisableIpCheck('$num')")."",
					)
				);		
	}
	
$data['total'] = $c;
	echo json_encode($data);		
	
}
	
function popup(){
	
	$page=CurrentPageName();
	$array["status"]='{status}';
	$array["list"]='{rules}';
	$tpl=new templates();


	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:26px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_ipblocks",1490);

	
	
}

function enable_form(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableIpBlocks=intval($sock->GET_INFO("EnableIpBlocks"));
	$p=Paragraphe_switch_img("{enable_ipblocks}","{ipblocks_explain}<br>{ipblocks_text}","EnableIpBlocks",
	$EnableIpBlocks,null,1450);
	
	$html="
	
	<div id='EnableIpBlocksDiv' class=form style='width:98%'>
		$p
	
	<div style='text-align:right'><hr>". button("{apply}","SaveEnableIpBlocks()",45)."</div>
	
	</div>
	<script>
	function x_SaveEnableIpBlocks(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		Loadjs('firehol.progress.php');
		RefreshTab('main_config_ipblocks');
	}	
	
	
	function SaveEnableIpBlocks(){
	    var XHR = new XHRConnection();
		XHR.appendData('EnableIpBlocks',document.getElementById('EnableIpBlocks').value);
		XHR.sendAndLoad('$page', 'GET',x_SaveEnableIpBlocks);
	}
	</script>
	";
		
	echo $tpl->_ENGINE_parse_body($html);	
		
	
}	

function save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableIpBlocks",$_GET["EnableIpBlocks"]);
	$sock->getFrameWork("network.php?ipdeny=yes");
	
}
function SaveIpChecks(){
	$sql="DELETE FROM ipblocks_set WHERE country='{$_POST["cn"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if($_POST["val"]==0){return;}
	$sql="INSERT IGNORE INTO ipblocks_set (country) VALUE('{$_POST["cn"]}')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("network.php?ipdeny=yes");
	
}
	
