<?php



function table(){

	if($_GET["table"]=="STATUS"){iptables_status();exit;}

	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$iptable=$_GET["table"];
	$title=$tpl->javascript_parse_text("$eth $ethC->NICNAME {{$iptable}}");
	$new=$tpl->javascript_parse_text("{new_rule}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$type=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");

	$t=time();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>

	function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?rules=yes&eth=$eth&t=$t&table=$iptable',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'zOrder', width :20, sortable : true, align: 'center'},
	{display: '$rulename', name : 'rulename', width : 423, sortable : true, align: 'left'},
	{display: '$enabled', name : 'enabled', width : 70, sortable : true, align: 'center'},
	{display: '$type', name : 'accepttype', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 70, sortable : true, align: 'center'},
	{display: '$delete', name : 'del', width : 70, sortable : false, align: 'center'},

	],
	buttons : [
	{name: '$new', bclass: 'add', onpress : NewRule$t},
	{name: '$apply', bclass: 'Apply', onpress : Apply$t},

	],
	searchitems : [
	{display: '$rulename', name : 'rulename'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
}
var xRuleGroupUpDown$t= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#flexRT$t').flexReload();
ExecuteByClassName('SearchFunction');
}

function RuleGroupUpDown$t(ID,direction){
var XHR = new XHRConnection();
XHR.appendData('rule-order', ID);
XHR.appendData('direction', direction);
XHR.appendData('eth', '$eth');
XHR.appendData('table', '$iptable');
XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function DeleteRule$t(ID){
if(!confirm('$delete '+ID+' ?')){return;}
var XHR = new XHRConnection();
XHR.appendData('rule-delete', ID);
XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
Loadjs('firehol.progress.php');
}

function ChangEnabled$t(ID){
var XHR = new XHRConnection();
XHR.appendData('rule-enable', ID);
XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function NewRule$t() {
Loadjs('$page?ruleid=0&eth=$eth&t=$t&table=$iptable',true);
}
LoadTable$t();
</script>
";
	echo $html;

}