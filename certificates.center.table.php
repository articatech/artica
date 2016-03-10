<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
if(!isset($_GET["t"])){$_GET["t"]=time();}
if(!is_numeric($_GET["t"])){$_GET["t"]=time();}

$user=new usersMenus();
if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	$text=replace_accents(html_entity_decode($text));
	echo "alert('$text');";
	exit;
}

if(isset($_GET["items"])){items();exit;}


table();

function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$certificates=$tpl->_ENGINE_parse_body("{certificates}");
	$Organization=$tpl->_ENGINE_parse_body("{organizationName}");
	$organizationalUnitName=$tpl->_ENGINE_parse_body("{organizationalUnitName}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$type=$tpl->javascript_parse_text("{type}");
	$new_certificate=$tpl->javascript_parse_text("{new_certificate}");
	$title=$tpl->_ENGINE_parse_body("{certificates_center}:{$_GET["CommonName"]}");
	$delete_certificate_ask=$tpl->javascript_parse_text("{delete_certificate_ask}");
	$buttons="
	buttons : [
	{name: '$new_certificate', bclass: 'Add', onpress : new_certificate$t},
	],";
	
	$html="
	<div style='margin-left:0px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	<script>
	var rowid$t='';
	function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&CommonName={$_GET["CommonName"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'del', width :60, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$certificates</span>', name : 'certificates', width : 490, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$type</span>', name : 'certificates', width : 313, sortable : false, align: 'left'},
	
	],
	
	sortname: 'CommonName',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
}
LoadTable$t();
</script>
";

echo $html;
	
	
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$UseGodaddy=intval($ligne["UseGodaddy"]);
	$bundle=trim($ligne["bundle"]);
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	$UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
	
	$keyfield="privkey";
	$Certfield="crt";
	if($UsePrivKeyCrt==0){
		$Certfield="SquidCert";
		$keyfield="Squidkey";
	}
	

	
	$PrivateKeyLength=strlen(trim($ligne[$keyfield]));
	$CertLength=strlen(trim($ligne[$Certfield]));
	$CSRLength=strlen(trim($ligne["csr"]));
	
	if($UseGodaddy==0){
		$title=$tpl->javascript_parse_text("{CA_CERTIFICATE}");
		$jsEdit="Loadjs('certificates.center.srca.php?CommonName=$CommonName&js=yes');";
		$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:26px;text-decoration:underline'>";
	
		$data['rows'][] = array(
			'id' => "srca",
			'cell' => array(
			"<img src='img/32-cert.png'>",
			"<span style='font-size:26px;'>$urljs{$title}</a></span>",
			"<span style='font-size:26px;'>$title</a></span>"
			)
			);
		
		
	if($ligne["UploadCertWizard"]==0){	
			$color="black";
			$fontweight="bold";
			
		if($PrivateKeyLength<5){
			$color="#898989";
			$fontweight="normal";
			$PrivateKeyLength=null;
		}else{
			$PrivateKeyLength=FormatBytes($PrivateKeyLength/1024);
		}
		
		
			$title=$tpl->javascript_parse_text("{RSA_PRIVATE_KEY}");
			$jsEdit="Loadjs('certificates.center.privkey.php?CommonName=$CommonName&js=yes');";
			$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" 
			style='font-size:26px;text-decoration:underline;color:$color;font-weight:$fontweight'>";
			
			$data['rows'][] = array(
					'id' => "privkey",
					'cell' => array(
							"<img src='img/32-cert.png'>",
							"<span style='font-size:26px;'>$urljs{$title}</a> {$PrivateKeyLength}</span>",
							"<span style='font-size:26px;color:$color;font-weight:$fontweight'>RSA PRIVATE KEY</a></span>"
					)
			);	
		
		}
	}
	
	$title=$tpl->javascript_parse_text("{certificate}");
	$color="black";
	$fontweight="bold";
		
	if($CertLength<5){
		$color="#898989";
		$fontweight="normal";
	}else{
		$CertLength=FormatBytes($CertLength/1024);
	}
	
	$jsEdit="Loadjs('certificates.center.crt.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" 
	style='font-size:26px;text-decoration:underline;color:$color;font-weight:$fontweight'>";
	
	
	$data['rows'][] = array(
			'id' => "certificate",
			'cell' => array(
					"<img src='img/32-cert.png'>",
					"<span style='font-size:26px;'>$urljs{$title}</a> $CertLength</span>",
					"<span style='font-size:26px;color:$color;font-weight:$fontweight'>CERTIFICATE</a></span>"
			)
	);
	
	
	$color="black";
	$fontweight="bold";
	
	if($CSRLength<5){
		$color="#898989";
		$fontweight="normal";
	}else{
		$CSRLength=FormatBytes($CSRLength/1024);
	}
	$title=$tpl->javascript_parse_text("{CSR}");
	$jsEdit="Loadjs('certificates.center.csr.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" 
	style='font-size:26px;text-decoration:underline;color:$color;font-weight:$fontweight'>";
	
	
	
	$data['rows'][] = array(
			'id' => "CSR",
			'cell' => array(
					"<img src='img/32-cert.png'>",
					"<span style='font-size:26px;'>$urljs{$title}</a></span>",
					"<span style='font-size:26px;color:$color;font-weight:$fontweight'>CERTIFICATE REQUEST</a></span>"
							
			)
	);
	
	
if(strlen($bundle)>10){
	$title=$tpl->javascript_parse_text("{certificate_bundle}");
	$jsEdit="Loadjs('certificates.center.bundle.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:26px;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "BUNDLE",
			'cell' => array(
					"<img src='img/32-cert.png'>",
					"<span style='font-size:26px;'>$urljs{$title}</a></span>",
					"<span style='font-size:26px;'>CERTIFICATE BUNDLE</a></span>"
	
							)
	);
	
}
	
	

	
if($UseGodaddy==1){	
	$title=$tpl->javascript_parse_text("{certificate_bundle}");
	$jsEdit="Loadjs('certificates.center.bundle.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:26px;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "BUNDLE",
			'cell' => array(
					"<img src='img/32-cert.png'>",
					"<span style='font-size:26px;'>$urljs{$title}</a></span>",
					"<span style='font-size:26px;'>CERTIFICATE BUNDLE</a></span>"
				
							)
	);	

}	


	
	
	$data['total']=count($data['rows']);
		
	echo json_encode($data);

}