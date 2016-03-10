<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
		
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if( isset($_GET['TargetArticaUploaded']) ){upload_artica_perform();exit();}
if(isset($_GET["file-uploader-demo1"])){upload_artica_final();exit;}
if(isset($_GET["uncompress"])){uncompress();exit;}
if(isset($_GET["remove"])){remove();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["manu"])){manu();exit;}
if(isset($_GET["compile-list"])){squid_compile_list();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{upload_package}   /   {compilation_status}");
	$html="YahooWin4('585','$page?tabs=yes','$title');";
	echo $html;
}


function tabs(){
	
	$page=CurrentPageName();
	$array["manu"]='{manual_update}';
	$array["popup"]='{compilation_status}';
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		$html[]="<li><a href=\"$page?$num=yes\"><span style='font-size:18px'>$ligne</span></a></li>\n";
			
	}
	echo build_artica_tabs($html, "squid_compilation_status");
	
}

function manu(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	
	
	$UploadAFile=$tpl->javascript_parse_text("{upload_package}");
	$allowedExtensions="allowedExtensions: ['gz'],";
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$ArchStruct=$users->ArchStruct;
	if($ArchStruct=="32"){$ArchStruct="i386";}
	if($ArchStruct=="64"){$ArchStruct="x64";}
	
	$realsquidversion=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
	
	
	$html="
	<H2 style='font-size:23px;margin-top:20px;'>Proxy $realsquidversion $ArchStruct</H2>
	<div style='font-size:16px;font-weight:normal;margin-bottom:25px;padding-top:5px;text-align:right;border-top:1px solid black'>{your_system}: ". $sock->getFrameWork("system.php?system-text=yes")."</div>
	<div class=explain style='font-size:16px'>{manual_update_root_text}</div>
	
	<center>
	<table style='width:80%'>
	<tr>
	<td width=1%><img src='img/arrow-blue-left-32.png'></td>
	<td><a href=\"http://www.articatech.net/artica-catzdb.php?ArchStruct=$ArchStruct\"
	target=_new style='font-size:16px;text-decoration:underline;color:black !important'>{find_packages}</a></td>
	</tr>
	</table>
	</center>
	
	<center style='margin:10px;width:99%'>
	<center id='file-uploader-demo1' style='width:100%;text-align:center'></center>
	</center>
	<script>
	function createUploader$t(){
	var uploader$t = new qq.FileUploader({
	element: document.getElementById('file-uploader-demo1'),
	action: '$page',$allowedExtensions
	template: '<div class=\"qq-uploader\">' +
	'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
	'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
	'<ul class=\"qq-upload-list\"></ul>' +
	'</div>',
	debug: false,
	params: {
	TargetArticaUploaded: 'yes',
	//select-file: '{$_GET["select-file"]}'
		},
		onComplete: function(id, fileName){
			PathUploaded(fileName);
	}
	});
	
	}
	
	function PathUploaded(fileName){
	LoadAjax('file-uploader-demo1','$page?file-uploader-demo1=yes&fileName='+fileName);
	}
	createUploader$t();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function popup(){
	header('Content-Type: text/html; charset=utf-8');
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$token=$tpl->_ENGINE_parse_body("{token}");
	$value=$tpl->_ENGINE_parse_body("{value}");
	$tablewidth=530;
	$servername_size=409;
	$t=time();
	
	$html="
	<table class='squid-table-$t' style='display: none' id='squid-table-$t' style='width:100%;margin:-10px'></table>
<script>
FreeWebIDJBB='';
$(document).ready(function(){
$('#squid-table-$t').flexigrid({
	url: '$page?compile-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '$token', name : 'token', width :248, sortable : true, align: 'left'},
		{display: '$value', name : 'value', width :237, sortable : true, align: 'left'},
		
	],
	$buttons

	searchitems : [
		{display: '$token', name : 'token'},
		
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: $tablewidth,
	height: 420,
	singleSelect: true
	
	});   
});
";
	
echo $html;

}




function squid_compile_list(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	
	
	if(count($array)==0){json_error_js("Compilation list is not an array");}
	
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
		$search=$_POST["query"];
	}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$data = array();
	$data['page'] = 1;

	$data['rows'] = array();
	
	$c=0;
	while (list ($num, $val) = each ($array)){
		$searchR=true;
		if($search<>null){
			if(!preg_match("#$search#i", $num)){$searchR=false;}
			if(preg_match("#$search#i", $val)){$searchR=true;}
		}
		if(!$searchR){continue;}
		$c++;
		$md5S=md5($num);
		if($val==null){$val="&nbsp;";}
		
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<span style='font-size:15px'>$num</span>","<span style='font-size:15px'>$val</span>"
					)
				);		
		}
	
		
		$data['total'] = $c;
	echo json_encode($data);		
}	

function upload_artica_perform(){

	usleep(300);
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);

	$sock=new sockets();
	$sock->getFrameWork("services.php?lighttpd-own=yes");

	if (isset($_GET['qqfile'])){
		$fileName = $_GET['qqfile'];
		if(function_exists("apache_request_headers")){
			$headers = apache_request_headers();
			if ((int)$headers['Content-Length'] == 0){
				writelogs("content length is zero",__FUNCTION__,__FILE__,__LINE__);
				die ('{error: "content length is zero"}');
			}
		}else{
			writelogs("apache_request_headers() no such function",__FUNCTION__,__FILE__,__LINE__);
		}
	} elseif (isset($_FILES['qqfile'])){
		$fileName = basename($_FILES['qqfile']['name']);
		writelogs("_FILES['qqfile']['name'] = $fileName",__FUNCTION__,__FILE__,__LINE__);
		if ($_FILES['qqfile']['size'] == 0){
			writelogs("file size is zero",__FUNCTION__,__FILE__,__LINE__);
			die ('{error: "file size is zero"}');
		}
	} else {
		writelogs("file not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "file not passed"}');
	}

	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);

	if (count($_GET)){
		$datas=json_encode(array_merge($_GET, array('fileName'=>$fileName)));
		writelogs($datas,__FUNCTION__,__FILE__,__LINE__);

	} else {
		writelogs("query params not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "query params not passed"}');
	}
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']} upload_max_filesize=".ini_get('upload_max_filesize')." post_max_size:".ini_get('post_max_size'),__FUNCTION__,__FILE__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/class.file.upload.inc");
	$allowedExtensions = array();
	$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
	$sizeLimit2 = qqFileUploader::toBytes(ini_get('post_max_size'));

	if($sizeLimit2<$sizeLimit){$sizeLimit=$sizeLimit2;}

	$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($content_dir);

	writelogs("upload_form_perform() -> OK",__FUNCTION__,__FILE__,__LINE__);



	if(is_file("$content_dir$fileName")){
		writelogs("upload_form_perform() -> $content_dir$fileName OK",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
		return;

	}
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;

}
function upload_artica_final(){
	$fileName=$_GET["fileName"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$fileNameEnc=urlencode($fileName);
	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>{installing} $fileName</div>");
	echo "$text<div id='$t'></div>
	<script>
	Loadjs('squid.update.progress.php?filename=$fileName');
	RefreshTab('squid_compilation_status');
	</script>

	";

}
function uncompress(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$fileName=$_GET["uncompress"];
	$sock=new sockets();
	$fileName=urlencode($fileName);
	$data=unserialize(base64_decode($sock->getFrameWork("system.php?uncompress-root=$fileName")));

	if($data==null){
		echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{failed}</div>");
		return;
	}

	if(!is_array($data)){
		echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{failed}</div>");
		return;
	}

	if(!$data["R"]){
		echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{$data["T"]}</div>");
		return;
	}


	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>{$data["T"]}</div>");
	echo "$text<div id='$t'></div>
	<script>
	LoadAjaxTiny('$t','$page?remove=$fileName');
	</script>

	";

}

function remove(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$fileName=$_GET["remove"];
	$sock=new sockets();

	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$fileName";
	@unlink($content_dir);
	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>$fileName: {deleted}</div>");
	echo "$text<div id='$t'></div>
	<script>
		CacheOff();
	</script>

	";

}
?>


