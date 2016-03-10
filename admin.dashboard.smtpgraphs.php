<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/smtp-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');


if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}


graph1();



function graph1(){
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){graph1_ou();exit;}
	$sock=new sockets();
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled",true));
	if($MimeDefangEnabled==1){graph1_all();exit;}
	
	$page=CurrentPageName();
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS"));

	
	if(count($MAIN)<2){
		header("content-type: application/x-javascript");
		die();
	}

	$tpl=new templates();

	$title="{received}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$MAIN["RECEIVED"]["X"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{size}"=>$MAIN["RECEIVED"]["Y"]);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph2=yes');\n";


}

function graph1_ou(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$page=CurrentPageName();
	$ldap=new clladp();
	$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
	$StartTime = date("Y-m-d H:i:s", strtotime("-24 hours"));
	
	while (list ($domain,$MAIN) = each ($domains) ){
		$domain=trim(strtolower($domain));
		if($domain==null){continue;}
		$FDOMS[]="domainto = '$domain'";
		//$FDOMS2[]="domainfrom ='$domain'";
	}
	$imploded1=@implode(" OR ", $FDOMS);
	//$imploded2=@implode(" OR ", $FDOMS2);
	$sql="select date_trunc('hour', zdate) as zdate,COUNT(*) as tcount FROM smtpstats WHERE 
	zdate >'$StartTime' AND ($imploded1) GROUP BY date_trunc('hour', zdate) ORDER BY zdate";
	
	
	$q=new postgres_sql();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){die();}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$x[]=$ligne["zdate"];
		$y[]=$ligne["tcount"];
	}
	
	
	$tpl=new templates();
	
	$title="{received} {from} $StartTime";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$x;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;
	
	$highcharts->datas=array("{messages}"=>$y);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph2=yes');\n";
	
	
}

function graph1_all(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$page=CurrentPageName();
	$StartTime = date("Y-m-d H:i:s", strtotime("-24 hours"));
	$sql="select date_trunc('hour', zdate) as zdate,COUNT(*) as tcount FROM smtpstats WHERE
	zdate >'$StartTime' GROUP BY date_trunc('hour', zdate) ORDER BY zdate";
	
	
	$q=new postgres_sql();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){die();}
	
	while ($ligne = pg_fetch_assoc($results)) {
	$x[]=$ligne["zdate"];
	$y[]=$ligne["tcount"];
	}
	
	
	$tpl=new templates();
	
		$title="{received}/{delivered} {from} $StartTime";
		$timetext="{hours}";
		$highcharts=new highcharts();
		$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$x;
		$highcharts->Title=$title;
		$highcharts->TitleFontSize="22px";
		$highcharts->AxisFontsize="12px";
		$highcharts->yAxisTtitle="{messages}";
		$highcharts->xAxis_labels=false;
		$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
		$highcharts->LegendSuffix="Mails";
		$highcharts->xAxisTtitle=$timetext;
	
		$highcharts->datas=array("{messages}"=>$y);
	echo $highcharts->BuildChart();
		echo "\nLoadjs('$page?graph2=yes');\n";	
	
}


function graph2_ou(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$page=CurrentPageName();
	$ldap=new clladp();
	$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
	$StartTime = date("Y-m-d H:i:s", strtotime("-24 hours"));

	while (list ($domain,$MAIN) = each ($domains) ){
		$domain=trim(strtolower($domain));
		if($domain==null){continue;}
		//$FDOMS[]="domainto = '$domain'";
		$FDOMS2[]="domainfrom ='$domain'";
	}
	//$imploded1=@implode(" OR ", $FDOMS);
	$imploded2=@implode(" OR ", $FDOMS2);
	$sql="select date_trunc('hour', zdate) as zdate,COUNT(*) as tcount FROM smtpstats WHERE
	zdate >'$StartTime' AND ($imploded2) GROUP BY date_trunc('hour', zdate) ORDER BY zdate";


	$q=new postgres_sql();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){die();}

	while ($ligne = pg_fetch_assoc($results)) {
		$x[]=$ligne["zdate"];
		$y[]=$ligne["tcount"];
	}


	$tpl=new templates();

	$title="{delivered} {from} $StartTime";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph2-dashboard";
	$highcharts->xAxis=$x;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{messages}"=>$y);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph3=yes');\n";


}
function graph2_all(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$page=CurrentPageName();
	
	$StartTime = date("Y-m-d H:i:s", strtotime("-24 hours"));

	$sql="select date_trunc('hour', zdate) as zdate,SUM(size) as tcount FROM smtpstats WHERE
	zdate >'$StartTime' GROUP BY date_trunc('hour', zdate) ORDER BY zdate";


	$q=new postgres_sql();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){die();}

	while ($ligne = pg_fetch_assoc($results)) {
		$ligne["tcount"]=$ligne["tcount"]/1024;
		$ligne["tcount"]=$ligne["tcount"]/1024;
		$x[]=$ligne["zdate"];
		$y[]=$ligne["tcount"];
	}


	$tpl=new templates();

	$title="{flow} {size} MB {from} $StartTime";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph2-dashboard";
	$highcharts->xAxis=$x;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{messages}"=>$y);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph3=yes');\n";


}



function graph2(){
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){graph2_ou();exit;}
	$sock=new sockets();
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled",true));
	if($MimeDefangEnabled==1){graph2_all();exit;}
	
	
	$page=CurrentPageName();
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS"));
	$tpl=new templates();

	$tpl=new templates();

	$title="{delivered}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph2-dashboard";
	$highcharts->xAxis=$MAIN["DELIVERED"]["X"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{size}"=>$MAIN["DELIVERED"]["Y"]);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph3=yes');\n";
}
function graph3(){
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){graph3_ou();exit;}
	$sock=new sockets();
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled",true));
	if($MimeDefangEnabled==1){graph3_all();exit;}
	
	$page=CurrentPageName();
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS"));
	$tpl=new templates();
	
	$tpl=new templates();
	
	$title="{rejected}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph3-dashboard";
	$highcharts->xAxis=$MAIN["REJECTED"]["X"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;
	
	$highcharts->datas=array("{size}"=>$MAIN["REJECTED"]["Y"]);
	echo $highcharts->BuildChart();
	

}
function graph3_all(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$page=CurrentPageName();
	
	$StartTime = date("Y-m-d H:i:s", strtotime("-24 hours"));

	
	$imploded2=@implode(" OR ", $FDOMS2);
	$sql="select date_trunc('hour', zdate) as zdate,COUNT(*) as tcount FROM smtprefused WHERE
	zdate >'$StartTime' GROUP BY date_trunc('hour', zdate) ORDER BY zdate";


	$q=new postgres_sql();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){die();}

	while ($ligne = pg_fetch_assoc($results)) {
		$x[]=$ligne["zdate"];
		$y[]=$ligne["tcount"];
	}


	$tpl=new templates();

	$title="{rejected} {from} $StartTime";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph3-dashboard";
	$highcharts->xAxis=$x;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{messages}"=>$y);
	echo $highcharts->BuildChart();
	//echo "\nLoadjs('$page?graph3=yes');\n";


}
function graph3_ou(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$page=CurrentPageName();
	$ldap=new clladp();
	$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
	$StartTime = date("Y-m-d H:i:s", strtotime("-24 hours"));

	while (list ($domain,$MAIN) = each ($domains) ){
		$domain=trim(strtolower($domain));
		if($domain==null){continue;}
		//$FDOMS[]="domainto = '$domain'";
		$FDOMS2[]="mailto LIKE '%$domain'";
	}
	//$imploded1=@implode(" OR ", $FDOMS);
	$imploded2=@implode(" OR ", $FDOMS2);
	$sql="select date_trunc('hour', zdate) as zdate,COUNT(*) as tcount FROM smtprefused WHERE
	zdate >'$StartTime' AND ($imploded2) GROUP BY date_trunc('hour', zdate) ORDER BY zdate";


	$q=new postgres_sql();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){die();}

	while ($ligne = pg_fetch_assoc($results)) {
		$x[]=$ligne["zdate"];
		$y[]=$ligne["tcount"];
	}


	$tpl=new templates();

	$title="{rejected} {from} $StartTime";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph3-dashboard";
	$highcharts->xAxis=$x;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{messages}"=>$y);
	echo $highcharts->BuildChart();
	//echo "\nLoadjs('$page?graph3=yes');\n";


}