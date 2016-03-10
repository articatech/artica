/**
 * @author touzeau ExecuteByClassName 
 * LoadAjaxError(ID,uri,FunctionError)
 * XHRParseElements DisableFieldsFromId 
 * LoadAjaxPreload 
 * LoadAjaxTiny 
 * IsFunctionExists 
 * AjaxTopMenuTiny 
 * ifFnExistsCallIt 
 * IsFunctionExists
 * DetectSpecialChars(xstr,alerterror)
 * UnlockPage
*  LeftDesign
*
 */
var IndexHorlogeTimer;
var LockeMe=false;
var xTimeOut;
var xMousePos=0;
var yMousePos=0;
var secs
var timerID = null;
var timerRunning = false;
var limit="0:20";
var parselimit;
var memory_branch;
var memory_service;
var popup_process_pid_id;
var TEMP_BOX_ID='';
var LoadWindows_id='';
var count_action;
var maxcount;
var mem_page;
var mem_branch_id;
var mem_item;
var mem_search=0;
var imgsrcMem='';
var SeTimeOutIMG32_mem='';
var LoadAjaxError=false;
var tree;
var MEM_ID;
var MEM_TIMOUT=0;
var ParseFormUriReturnedBack='';
var ParseFormUriReturnedID;
document.onmousemove = pointeurDeplace;
UnlimitedSession();
ONKEYPRESS="function(event){e0.onKeyPress(event,0)}";
setTimeout("SearchIds()",10000);
var compteur_global_timerID  = null;
var compteur_global_tant=0;
var compteur_global_reste=0;
var compteur_global_idname;
var compteur_global_num=0;
var compteur_global_max=0;
var SeTimeOutIMG32Mem='';
var Loadjs_src='';
var myMessages = ['info','warning','error','success','messageMenu']; // define the messages types	
var JavaScriptError=0;


/* Top notify */





function Blurz(){}
function zBlur(){}
function zBlut(){}
function AmavisCompileRules(){Loadjs('amavis.general.status.php?compile-rules-js=yes');}
function MimeDefangCompileRules(){Loadjs('mimedefang.compile.php?compile-rules-js=yes');}
function OpenLDAPCompilesRules(){Loadjs('openldap.php?compile-rules-js=yes');}

function RefreshAllAclsTables(){
var id='';
	if( document.getElementById('ACL_ID_MAIN_TABLE') ){
	  id=document.getElementById('ACL_ID_MAIN_TABLE').value;
	  $('#'+id).flexReload();
	}
	if( document.getElementById('ACL_ID_GROUP_TABLE') ){
	  id=document.getElementById('ACL_ID_GROUP_TABLE').value;
	  $('#'+id).flexReload();
	}
	if(document.getElementById('GLOBAL_SSL_CENTER_ID')){
		$('#'+document.getElementById('GLOBAL_SSL_CENTER_ID').value).flexReload();
	}
	if(document.getElementById('SSL_RULES_GROUPS_ID')){
		$('#'+document.getElementById('SSL_RULES_GROUPS_ID').value).flexReload();
	}	
	
	if(document.getElementById('flexRT-refresh-1')){ 
		$('#'+document.getElementById('flexRT-refresh-1').value).flexReload();
	}
	if(document.getElementById('TABLE_BROWSE_ACL_GROUPS_ID')){ 
		$('#'+document.getElementById('TABLE_BROWSE_ACL_GROUPS_ID').value).flexReload();
	}
	
	if(document.getElementById('TABLE_ITEMS_LIST_ACLS')){ 
		$('#'+document.getElementById('TABLE_ITEMS_LIST_ACLS').value).flexReload();
	}
	if(document.getElementById('META_PROXY_ACLS_GROUPS')){ 
		$('#'+document.getElementById('META_PROXY_ACLS_GROUPS').value).flexReload();
	}
	if(document.getElementById('META_PROXY_ACLS_GROUPS')){ 
		$('#'+document.getElementById('META_PROXY_ACLS_GROUPS').value).flexReload();
	}
}


function DashBoardProxy(){

	if(!document.getElementById('MainSlider')){
		LoadAjax('BodyContent','admin.dashboard.proxy.php');
		return;
	}		
	LoadAjaxRound('proxy-dashboard','admin.dashboard.proxy.php?proxy-dashboard=yes');
}


function LoadMemDump(){
	Loadjs('admin.index.php?mem-dump-js=yes',true);
}

function CheckBoxDesignHidden(){
	var inputs = document.querySelectorAll("input[type='checkbox']");
	
	for(var i = 0; i < inputs.length; i++) {
	    if (inputs[i].id){
	     var myID=inputs[i].id;
	     if( document.getElementById(myID).getAttribute('assoc') ){
		var imgid=document.getElementById(myID).getAttribute('assoc');
		if(document.getElementById(myID).disabled){
		   	CheckBoxDesignSetHidden(myID,imgid);
		 }else{
		 	CheckBoxDesignSetActive(myID,imgid);
 		 }	
	     	} 
	   }
 	}
		//document.getElementById('checkbox').getAttribute('assoc');
	
}

function CheckBoxDesignSetHidden(fieldid,imgid){

	if(!document.getElementById(imgid)){return;}

	document.getElementById(imgid).onmouseover=function(){
		document.getElementById(imgid).style.cursor='not-allowed';
		document.getElementById(imgid).style.boxShadow='';
	}

	document.getElementById(imgid).onmouseout=function(){
		document.getElementById(imgid).style.cursor='default';
		document.getElementById(imgid).style.boxShadow='';
	}


	if(document.getElementById(fieldid).checked){
	  document.getElementById(imgid).src='img/checkbox-on-grey-24.png';
	  return;
	}
	document.getElementById(imgid).src='img/checkbox-off-grey-24.png';
}
function CheckBoxDesignSetActive(fieldid,imgid){
	if(!document.getElementById(imgid)){return;}

	document.getElementById(imgid).onmouseover=function(){
		document.getElementById(imgid).style.cursor='pointer';
		document.getElementById(imgid).style.boxShadow='0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6)';
	}

	document.getElementById(imgid).onmouseout=function(){
		document.getElementById(imgid).style.cursor='default';
		document.getElementById(imgid).style.boxShadow='';
	}

	if(document.getElementById(fieldid).checked){
	  document.getElementById(imgid).src='img/checkbox-on-24.png';
	  return;
	}
	document.getElementById(imgid).src='img/checkbox-off-24.png';
}


function CheckBoxDesign(imgid,fieldid){
	if(!document.getElementById(fieldid)){return;}
	if(document.getElementById(fieldid).disabled){return;}
	


	if(document.getElementById(fieldid).checked){
		document.getElementById(imgid).src='img/checkbox-off-24.png';	
		document.getElementById(fieldid).checked=false;
		return;
	}
 document.getElementById(imgid).src='img/checkbox-on-24.png';	
 document.getElementById(fieldid).checked=true;
 CheckBoxDesignHidden();
}

	 
function MessagesTophideAllMessages(){
		 var messagesHeights = new Array();
	 
		 for (i=0; i<myMessages.length; i++){
			 messagesHeights[i] = $('.' + myMessages[i]).outerHeight();
			 $('.' + myMessages[i]).css('top', -messagesHeights[i]); //move element outside viewport	  
		 }
}

function MenuScroll(type){
	MessagesTophideAllMessages();	
	$('.'+type).animate({top:"0"}, 500);
}


function MessagesTopshowMessageDisplay(type){
	MessagesTophideAllMessages();	
	$('.messageMenu').animate({top:"0"}, 500);
	$('.messageMenu').css('z-index',500);
	
	LoadAjaxWhite('messageMenuDiv','quicklinks.php?function='+type,false);
}

function RefreshServiceStatus(id,productkey){
	LoadAjax(id,"admin.index.php?status=yes&KEY="+productkey);
	
}

function initMessagesTop(){
	MessagesTophideAllMessages();
	$('.message').click(function(){ $(this).animate({top: -$(this).outerHeight()}, 500);});		 
	$('.messageMenu').click(function(){ $(this).animate({top: -$(this).outerHeight()}, 500);});	
}

function ifFnExistsCallIt(fnName){
   fn = window[fnName];
   fnExists = typeof fn === 'function';
   if(fnExists)
      fn();
}

function MessagesTopshowMessage(content,type){
	if(!type){type="info";}
	if(type.length<3){type="info";}
	if(type=="warn"){
		document.getElementById('AcaNotifyMessWarn').innerHTML=content;
		MessagesTopshowMessageDisplay('Warning');
	}
	if(type=="info"){
		document.getElementById('AcaNotifyMessInfo').innerHTML=content;
		MessagesTopshowMessageDisplay('info');
	}	
	
}
function SeTimeOutIMG32_return(imgid){
	
	if(document.getElementById(imgid+'_org')){
		var src=document.getElementById(imgid+'_org').value;
		document.getElementById(imgid).src=src;	
		
	}
	
	
	if(document.getElementById(imgid)){
		document.getElementById(imgid).src=SeTimeOutIMG32Mem;	
	}

	
}

function reloadStylesheets() {
	var stylesheets = $('link[rel="stylesheet"]');
	var reloadQueryString = '?reload=' + new Date().getTime();
	stylesheets.each(function () {
		this.href = this.href.replace(/\?.*|$/, reloadQueryString);
	});
}

function SeTimeOutIMG32(imgid){
	if(!document.getElementById(imgid)){return;}
	SeTimeOutIMG32Mem=document.getElementById(imgid).src;
	document.getElementById(imgid).src='img/ajax-loader.gif';
	setTimeout(SeTimeOutIMG32_return, 3000, [imgid]);
}

function  compteur_global_demarre(){
	 compteur_global_tant = compteur_global_tant+1;
		if ( compteur_global_tant <50 ) {                           
			 compteur_global_timerID = setTimeout("compteur_global_demarre()",1500);
	      } else {
	    	  compteur_global_tant = 0;
	    	  compteur_global_actions();
	    	  compteur_global_demarre();
	   }
	}

var x_time_fill= function (obj) {
	var results=obj.responseText;
	document.getElementById('topemnucurrentdate').innerHTML=results;
}


function UnlimitedSession(){

}

function SearchIds(){



}


function IsFunctionExists(fnName){
	if(fnName && window[fnName]) { return true;}
	return false;
}


function DetectSpecialChars(xstr,alerterror){
	if(!alerterror){alerterror="Value has special characters, These are not allowed:";}
	var iChars = "~`!#$%^&*+=-[]\\\';,/{}|\":<>?";

	for (var i = 0; i < xstr.length; i++)
	{
	  if (iChars.indexOf(xstr.charAt(i)) != -1)
	  {
	     alert (alerterror+" ~`!#$%^&*+=-[]\\\';,/{}|\":<>? \n\nSee this video to remove this warning:\nhttp://www.youtube.com/watch?v=g-apAIijPt0");
	     return false;
	  }
	}	
	return true;
}

function ExecuteByClassName(classname){
	var xid='';
	var functioname;
	$(document).ready(function(){
    	var elements = $('.'+classname);
   		elements.each(function() { 
			xid=$(this).attr('id');
			if(document.getElementById(xid)){
				functioname=document.getElementById(xid).value;
				ifFnExistsCallIt(functioname);
			}
			
		});
    
	});	
	
}


function RefreshAllTabs(){
	var xid='';
	var functioname;
	$(document).ready(function(){
    	var elements = $('.ui-tabs');
   		elements.each(function() { 
			xid=$(this).attr('id');
			if(document.getElementById(xid)){
				RefreshTab(xid);
			}
			
		});
    
	});		
	
	
}


	
	


function QuickLinks(){
	var z = $("#middle").css('display');
	if(z!=="none"){
		$('#middle').slideUp('normal');
		$('#middle').html('');
		$('#quick-links').html('');
		$('#middle').slideDown({
			duration:900,
			easing:"easeOutExpo",
			complete:function(){
				QuickLinksLoad();
				}
			});
		}
	
}

function SquidQuickLinks(){
	var z = $("#middle").css('display');
	if(z!=="none"){
		$('#middle').slideUp('normal');
		$('#middle').html('');
		$('#quick-links').html('');
		$('#middle').slideDown({
			duration:900,
			easing:"easeOutExpo",
			complete:function(){
				QuickLinksSquidLoad();
				}
			});
		}
	
}


function SquidQuickLinksStatistics(){
	LoadAjax('BodyContent','squid.statistics.central.php',true)
	
}


function SquidMainQuickLinks(){
	
	LoadAjax('middle','squid.main.quicklinks.php',true);
	return;
	
	
	
	var z = $("#middle").css('display');
	if(z!=="none"){
		$('#middle').slideUp('normal');
		$('#middle').html('');
		$('#quick-links').html('');
		$('#middle').slideDown({
			duration:900,
			easing:"easeOutExpo",
			complete:function(){
				QuickLinksSquidMainLoad();
				}
			});
		}
	
}
function QuickLinksSquidStatsLoad(){
	LoadAjax('middle','squid-quicklinks.php?stats=yes&force=true',true);	
	
}

function QuickLinksSquidLoad(){
	LoadAjax('middle','squid-quicklinks.php',true);
}
function QuickLinksSquidMainLoad(){
	LoadAjax('middle','squid.main.quicklinks.php',true);
}
function QuickLinksLoad(){
	
	LoadAjax('middle','quicklinks.php',true);
	
}
function QuickLinksHide(){
	$('#middle').slideUp('normal');
	$('#middle').html('');
	$('#quick-links').html('');
	$('#middle').slideDown({
		duration:900,
		easing:"easeOutExpo",
		complete:function(){
			LoadAjax('middle','quicklinks.php?off=yes',true)
			}
		});
	}


function QuickLinksHideUsrProxy(){
	$('#middle').slideUp('normal');
	$('#middle').html('');
	$('#quick-links').html('');
	$('#middle').slideDown({
		duration:900,
		easing:"easeOutExpo",
		complete:function(){
			LoadAjax('middle','squid.users.quicklinks.php?off=yes',true)
			}
		});
	}

function x_ChangeHTMLTitle(obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>0){
		document.title=tempvalue;
    }else{
    	document.title="!!! Error !!!";
    }
}

function ChangeHTMLTitle(){
	  setTimeout('ChangeHTMLTitlePerform()',500);
}
function ChangeHTMLTitleUserSquid(){
	  setTimeout('ChangeHTMLTitleUserSquidPerform()',500);
}
 
function ChangeHTMLTitlePerform(){
	var XHR = new XHRConnection();
	XHR.appendData('GetMyTitle','yes');
	XHR.sendAndLoad("change.title.php", 'POST',x_ChangeHTMLTitle);	
}
function ChangeHTMLTitleUserSquidPerform(){
	var XHR = new XHRConnection();
	XHR.appendData('GetMyTitle','yes');
	XHR.sendAndLoad("squid.users.index.php", 'POST',x_ChangeHTMLTitle);	
	}


function RemoveSearchEngine(){
	setTimeout('RemoveSearchEnginePerform()',1500);
	
}

function RemoveSearchEnginePerform(){
	if(!RemoveSearchEnginePage()){return;}

	$('.search').remove();
	$('.search').empty().remove();
}

function RemoveSearchEnginePage(){
	var myPage=CurrentPageName();
	if(myPage=='squid.users.logon.php'){return true;}
	if(myPage=='squid.users.quicklinks.php'){return true;}
	if(myPage=='squid.users.index.php'){return true;}
	
	return false;
}

function compteur_global_actions(){}

function isNumber(v){
	return /^-?(0|[1-9]\d*|(?=\.))(\.\d+)?$/.test(v);
}
function Rebullet(myThis){
	document.getElementById(myThis).src='img/fullbullet.gif';
}

var refresh_action=function(obj){
      var tempvalue=obj.responseText;
      document.getElementById('message_'+count_action).innerHTML=tempvalue;
      count_action=count_action+1;
      
      if(count_action<maxcount){
        setTimeout('action_run('+count_action+')',1500);
      }
}

function StartAction(page,maxcountop){
    mem_page=page;
    maxcount=maxcountop;
    YahooWin(440,page+'?op=-1');
    setTimeout('action_run(0)',1500);
}

function action_run(number){
     var XHR = new XHRConnection();
     document.getElementById('message_'+number).innerHTML='<img src="/img/wait.gif">';
      count_action=number;
      XHR.appendData('op',number);
      XHR.sendAndLoad(mem_page, 'GET',refresh_action);
}

function StartServiceInDebugMode(service_name,cmd){
	YahooWin3(550,'admin.index.php?EmergencyStart='+cmd,service_name);
}

function pointeurDeplace(e){
	xMousePos=pointeurX(e);
    yMousePos = pointeurY(e);
   }
function ShowTopLinks(){
	if(!document.getElementById('TpLink')){return false;}
	document.getElementById('TpLink').style.visibility='visible';
		document.getElementById('TpLink').style.left =xMousePos + "px";
	document.getElementById('TpLink').style.top =yMousePos + "px";	
	
}

function Ipv4FieldDisable(id){
	document.getElementById(id+'_0').disabled=true;
	document.getElementById(id+'_1').disabled=true;
	document.getElementById(id+'_2').disabled=true;
	document.getElementById(id+'_3').disabled=true;
	
}
function Ipv4FieldEnable(id){
	document.getElementById(id+'_0').disabled=false;
	document.getElementById(id+'_1').disabled=false;
	document.getElementById(id+'_2').disabled=false;
	document.getElementById(id+'_3').disabled=false;
	
}

function GlobalSystemNetInfos(ipaddr){
	ipaddr=escape(ipaddr);
	RTMMail('550','system.netinfos.php?ipaddr='+ipaddr,ipaddr);
	
}

function IndexStartPostfix(){LoadAjax('servinfos','users.index.php?StartPostfix=yes');}


function LoadAjaxWhite(ID,uri,concatene) {
	var uri_add='';
	var datas='';
	var xurl='';
	LockeMe=false;
	
	if(concatene==true){
		LockeMe=true;
	}else{
		if(concatene){ uri_add='&datas='+concatene; }
	}

	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
			var WAITX=ID+'_WAITX';
			if(document.getElementById(WAITX)){return;}
	 $.ajax({
        type: "GET",
        timeout: 40000,
        url: uri,
        beforeSend: function() {
		if(LockeMe){ LockPage();}
        	if(document.getElementById(ID)){
        		document.getElementById(ID).innerHTML="<center><img src='/img/ajax-loader-white.gif'></center>";
        	}
		    
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
		  if(LockeMe){ UnlockPage();}
		  var StatusLength=textStatus.length;
	      	if(StatusLength==0){
	      		LoadAjaxWhite(ID,uri,concatene);
	      		return;
	      	}
	      	
	      	if(textStatus=='error'){
	      		LoadAjaxWhite(ID,uri,concatene);
	      		return;
	      	}
	      	
	      	if(textStatus=='Service Unavailable'){
	      		JavaScriptError=JavaScriptError+1;
	      		if(JavaScriptError<2){
			  LoadAjaxWhite(ID,uri,concatene);
			}
	      	}
	      	
	        document.getElementById(ID).innerHTML="<strong style='font-size:14px;color:white'>An error has occurred making the request: &laquo;" + errorThrown+"&raquo;<br>Status:&laquo;"+textStatus+"&raquo;</strong>";
        },
        success: function(data) {
            $('#'+ID).html(data);
            if(LockeMe){ UnlockPage(); }
	    JavaScriptError=0;
        }
	});	
	
	}	
	
	
}
function LoadAjaxError(ID,uri,functionError) {
	
	if(document.getElementById(ID)){ 
			var WAITX=ID+'_WAITX';
			if(document.getElementById(WAITX)){return;}
	 $.ajax({
        type: "GET",
        timeout: 40000,
        url: uri,
        beforeSend: function() {
		    AnimateDiv(ID);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        	eval(functionError);
        },
        success: function(data) {
            $('#'+ID).html(data);
        }
	});	
	
	}
}


function LoadAjaxRound(ID,uri,concatene) {
 	if(!document.getElementById(ID)){ return;}
	var uri_add='';
	var datas='';
	var xurl='';
	LockeMe=false;
	if(concatene==true){ LockeMe=true; }else{ if(concatene){ uri_add='&datas='+concatene; } }
	uri=uri+uri_add;
	var WAITX=ID+'_WAITX';
	if(document.getElementById(WAITX)){return;}

 $.ajax({
        type: "GET",
        timeout: 40000,
        url: uri,
        beforeSend: function() {
		    AnimateDivRound(ID);
		    if(LockeMe){ LockPage();}
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        	if(LockeMe){ UnlockPage(); }
        	var StatusLength=textStatus.length;
        	if(StatusLength==0){
        	   LoadAjaxRound(ID,uri,concatene);
        	   return;
        	}
        	
	      	if(textStatus=='error'){
 	      	  LoadAjaxRound(ID,uri,concatene);
	      	  return;
	      	}

	      	if(textStatus=='Service Unavailable'){
	      		JavaScriptError=JavaScriptError+1;
	      		if(JavaScriptError<2){
			  LoadAjaxRound(ID,uri,concatene);
			}
	      	}
        	
        	document.getElementById(ID).innerHTML="<strong style='font-size:14px'>An error has occurred making the request: error Thrown:&laquo;" + errorThrown+"&raquo;<br>Status: &laquo;"+textStatus+"&raquo;</strong>";
        },
        success: function(data) {
            $('#'+ID).html(data);
	    JavaScriptError=0;
            if(LockeMe){ UnlockPage(); }
        }
});	
	
	
}





function LoadAjax(ID,uri,concatene) {
	var uri_add='';
	var datas='';
	var xurl='';
	LockeMe=false;
	
	if(concatene==true){
		LockeMe=true;
	}else{
		if(concatene){ uri_add='&datas='+concatene; }
	}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
			var WAITX=ID+'_WAITX';
			if(document.getElementById(WAITX)){return;}
	 $.ajax({
        type: "GET",
        timeout: 40000,
        url: uri,
        beforeSend: function() {
		    AnimateDiv(ID);
		    if(LockeMe){ LockPage();}
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        	if(LockeMe){ UnlockPage(); }
        	var StatusLength=textStatus.length;
        	if(StatusLength==0){
        	   LoadAjax(ID,uri,concatene);
        	   return;
        	}
        	
	      	if(textStatus=='error'){
 	      	  LoadAjax(ID,uri,concatene);
	      	  return;
	      	}

	      	if(textStatus=='Service Unavailable'){
	      		JavaScriptError=JavaScriptError+1;
	      		if(JavaScriptError<2){
			  LoadAjax(ID,uri,concatene);
			}
	      	}
        	
        	document.getElementById(ID).innerHTML="<strong style='font-size:14px'>An error has occurred making the request: error Thrown:&laquo;" + errorThrown+"&raquo;<br>Status: &laquo;"+textStatus+"&raquo;</strong>";
        },
        success: function(data) {
            $('#'+ID).html(data);
	    JavaScriptError=0;
            if(LockeMe){ UnlockPage(); }
        }
	});	
	
	}
}
function LoadAjaxSilent(ID,uri,concatene) {
	var uri_add='';
	if(concatene){uri_add='&datas='+concatene;}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
	  UnlockPage();
	  $('#'+ID).load(uri);
	}

}
function LoadAjaxVerySilent(ID,uri,concatene) {
	if(concatene){uri_add='&datas='+concatene;}
	if(document.getElementById(ID)){ 
	    $('#'+ID).load(uri);
	}

}

function LoadAjaxTiny(ID,uri,concatene) {
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){uri_add='&datas='+concatene;}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
	  document.getElementById(ID).innerHTML='<img src=ajax-menus-loader.gif>';
	  UnlockPage();
	  $('#'+ID).load(uri);
	}

}
function LoadAjaxPreload(ID,uri,concatene) {
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){uri_add='&datas='+concatene;}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
	  document.getElementById(ID).innerHTML='<img src="/img/preloader.gif">';
	  $('#'+ID).load(uri);
	}

}

function AjaxTopMenu(ID,uri,concatene){
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){uri_add='&datas='+concatene;}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
		 	document.getElementById(ID).innerHTML='<img src="/img/ajax-top-menu-loader.gif">';
	        $('#'+ID).load(uri);
	}
	
	
}
function AjaxTopMenuTiny(ID,uri,concatene){
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){uri_add='&datas='+concatene;}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
		 	document.getElementById(ID).innerHTML='<img src="/img/4-1.gif">';
	        $('#'+ID).load(uri);
	}
	
	
}


function ValidateIPAddress(ipaddr) {
    ipaddr = ipaddr.replace( /\s/g, "");
    var re = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/; 
    
    if (re.test(ipaddr)) {
        var parts = ipaddr.split(".");
        //if the first unit/quadrant of the IP is zero
        //if (parseInt(parseFloat(parts[0])) == 0) {
         //   return false;
        //}
       return true;
    } else {
        return false;
    }
}


function LoadAjaxHidden(ID,uri,concatene) {
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){uri_add='&datas='+concatene;}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
			var WAITX=ID+'_WAITX';
			if(document.getElementById(WAITX)){return;}
	 $.ajax({
        type: "GET",
        timeout: 40000,
        url: uri,
        beforeSend: function() {
			document.getElementById(ID).innerHTML='wait...';
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        	document.getElementById(ID).innerHTML=errorThrown;
        },
        success: function(data) {
            $('#'+ID).html(data);
        }
	});	
	
	}
}

function execute_function(funcName){
	
	if (typeof funcName == 'string' && eval('typeof ' + funcName) == 'function') {

			eval(funcName+'()');
	}
	
}

function LoadAjaxSequence(ID,uri,next) {
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){
		uri_add='&datas='+concatene;
	}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
			var WAITX=ID+'_WAITX';
			if(document.getElementById(WAITX)){return;}
	        document.getElementById(ID).innerHTML='<center style="margin:20px;padding:20px" id='+WAITX+'><img src="/img/ajax-loader.gif"></center>';
	        $('#'+ID).load(uri, next);
	}	
}


function LoadAjaxTiny(ID,uri,concatene) {
	var uri_add='';
	var datas='';
	var xurl='';
	if(concatene){
		uri_add='&datas='+concatene;
	}
	uri=uri+uri_add;
	if(document.getElementById(ID)){ 
			var WAITX=ID+'_WAITX';
			if(document.getElementById(WAITX)){return;}
	        document.getElementById(ID).innerHTML='<center><img src="/img/ajax-menus-loader.gif"></center>';
	        //$('#'+ID).load(uri_add, function() {Orgfillpage();});
	        $('#'+ID).load(uri);
	}

}


function XHRParseElements(idToParse){
	var XHR = new XHRConnection();
	 //select-one
	$('input,select,hidden,textarea', '#'+idToParse).each(function() {
	 	var $t = $(this);
	 	var id=$t.attr('id');
	 	var value=$t.attr('value');
	 	var type=$t.attr('type');
	 	
	 	if(type=='checkbox'){
	 		if(!document.getElementById(id).checked){
	 			if(value==1){value=0;}
	 			if(value=='yes'){value='no';}
	 		}
	 	}
	 	XHR.appendData(id,value);
	 });
	
	return XHR;
}


function LoadAjax2(ID,uri) {
	var XHR = new XHRConnection();
        MEM_ID=ID;
	XHR.setRefreshArea(ID);
        XHR.sendAndLoad(uri,"GET")
	//XHR.sendAndLoad(uri,"GET",x_ajax);
}

function UploadstartCallback() {
         return true;
        }


function UploadcompleteCallback(response) {
            document.getElementById('UploadedResponse').innerHTML = response;
        }


function LoadPostfixHistoryMsgID(mid,id){
	if(id.length>0){
	var txt=document.getElementById(id).innerHTML;
	if(txt.length>1){document.getElementById(id).innerHTML=''}else{
	LoadAjax(id,'users.index.php?PostfixHistoryMsgID='+mid);
	}}
}

function LoadHelp(textz,currpage,notitle){
        if(!notitle){notitle='Help';}
        YahooWinT(450,'users.index.php?loadhelp='+ textz + '&title='+ notitle + '&currpage='+currpage);
	}
        
        
        
function add_fetchmail_rules(){
	YahooWinHide();
	YahooWin2('1050','artica.wizard.fetchmail.php?AddNewFetchMailRule=yes','Fetchmail rules');
}


function LoadTaskManager(){
	Loadjs('/system.tasks.manager.php');
}
function ProcessTaskEdit(PID){
        YahooWin2('550','system.tasks.manager.php?PID='+ PID,'Process ' +PID);
        }
function KillProcessByPid(PID){
		var XHR = new XHRConnection();
		XHR.sendAndLoad('system.tasks.manager.php?KillProcessByPid='+ PID,"GET",x_parseform);	
		YAHOO.example.container.dialog2.hide();
		ReloadTaskManager();
	}

function ReloadTaskManager(){
	if(document.getElementById('page_taskM')){
		document.getElementById('page_taskM').innerHTML='<center><img src="/img/frw8at_ajaxldr_7.gif"></center>';
		setTimeout('ReloadTaskManager2()',1200);			
	}
	
}
function ReloadTaskManager2(){
		var XHR = new XHRConnection();
		XHR.setRefreshArea('page_taskM');
		XHR.sendAndLoad('system.tasks.manager.php?reload=yes',"GET");
		}
	
	
function HelpExpand(key){
	var html=document.getElementById(key+'_fill').innerHTML;
	
	if(html.length==0){
		document.getElementById(key+'_fill').style.border='2px solid #CCCCCC'
		document.getElementById(key+'_fill').innerHTML=document.getElementById(key+'_source').value;
		document.getElementById(key+'_fill').style.backgroundColor='#F4F2F2';
		document.getElementById(key+'_fill').style.padding='3px;';
		document.getElementById(key+'_fill').style.margin='5px;';
		document.getElementById(key+'_img').src='img/collapse.gif';
	}else
	document.getElementById(key+'_fill').innerHTML='';
	document.getElementById(key+'_fill').style.border='0px solid #CCCCCC'
	document.getElementById(key+'_fill').style.padding='0px;';
	document.getElementById(key+'_fill').style.margin='0px;';
	document.getElementById(key+'_img').src='img/expand.gif';
	document.getElementById(key+'_fill').style.backgroundColor='transparent';
	}
	
function rol1_(id){
	document.getElementById(id).className='RLightGreen';
	document.getElementById(id + "_0").className='RLightGreen';								
	document.getElementById(id + "_1").className='RLightGreen1';				
	document.getElementById(id + "_2").className='RLightGreen2';					
	document.getElementById(id + "_3").className='RLightGreen3';	
	document.getElementById(id + "_4").className='RLightGreen4';	
	document.getElementById(id + "_5").className='RLightGreen5';	
	document.getElementById(id + "_6").className='RLightGreen5';	
	document.getElementById(id + "_7").className='RLightGreen4';	
	document.getElementById(id + "_8").className='RLightGreen3';	
	document.getElementById(id + "_9").className='RLightGreen2';					
	document.getElementById(id + "_10").className='RLightGreen1';									
	document.getElementById(id + "_11").className='RLightGreenfg';					
}
				
function rol0_(id){
	document.getElementById(id).className='RLightGrey';
	document.getElementById(id + "_0").className='RLightGrey';								
	document.getElementById(id + "_1").className='RLightGrey1';				
	document.getElementById(id + "_2").className='RLightGrey2';					
	document.getElementById(id + "_3").className='RLightGrey3';	
	document.getElementById(id + "_4").className='RLightGrey4';	
	document.getElementById(id + "_5").className='RLightGrey5';	
	document.getElementById(id + "_6").className='RLightGrey5';	
	document.getElementById(id + "_7").className='RLightGrey4';	
	document.getElementById(id + "_8").className='RLightGrey3';	
	document.getElementById(id + "_9").className='RLightGrey2';					
	document.getElementById(id + "_10").className='RLightGrey1';									
	document.getElementById(id + "_11").className='RLightGreyfg';					
}		
	

function CurrentPageName(){
		var sPath = window.location.pathname;
		var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);
		return sPage;		
	}
	
function IsNumeric(sText){
   var ValidChars = "0123456789.";
   var IsNumber=true;
   var Char;

 
   for (i = 0; i < sText.length && IsNumber == true; i++) { 
      Char = sText.charAt(i); 
      if (ValidChars.indexOf(Char) == -1) {IsNumber = false;}}
   return IsNumber;}
	
function Help(field){
	hide_explains();
	var id_name=field;
	var close_this;
	var text_html;
	var html;

	text_html=document.getElementById(id_name).value;
	html="<div id='SHADOW' style='position:relative; top:7px; left:7px;background:black;'>";
	html=html + "<div style='position:relative;top:-7px; left:-7px;background:#FCFCFC;border:1px solid #005447;'>";
	html=html + "<div  id='locker' style='padding:0px;background-color:#005447;background-image:url(img/barrecroix.gif);";
	html=html + "background-repeat:no-repeat;height:19px;padding-right:3px;background-position:right;cursor:pointer'>";
	html=html + "<a href='#' OnClick=\"javascript:HideDive('windows');\">";
	html=html + "<img src='http://images.kaspersky.fr/vide.gif' height=18 width=90 border=0 align='right'></a>";
	html=html + "</div>";
	html=html + "<div style='margin:4px;padding:15px;'>" + text_html + "</div>";
	html=html + "</div>";
	html=html + "</div>";
	document.onmousemove = pointeurDeplace
	document.getElementById('windows').style.visibility="visible";
	document.getElementById('windows').style.border ="none";
	document.getElementById('windows').style.width ="550px";
	document.getElementById('windows').style.padding ="0";
	document.getElementById('windows').style.left =xMousePos-550 + "px";
	document.getElementById('windows').style.top =yMousePos-100 + "px";	
	document.getElementById('windows').style.backgroundColor="#FFFFFF";
	document.getElementById('windows').style.zIndex='9000';	
	document.getElementById('windows').innerHTML=html;
	
}
function HelpIcon(div_name){Help(div_name);}
function lightup(imageobject, opacity){
if (navigator.appName.indexOf("Netscape")!=-1 &&parseInt(navigator.appVersion)>=5){
        imageobject.style.MozOpacity=opacity/100;
        imageobject.style.backgroundColor='none';
        }
else if (navigator.appName.indexOf("Microsoft")!= -1 &&parseInt(navigator.appVersion)>=4){imageobject.filters.alpha.opacity=opacity}
}


function closediv(div){
	document.getElementById(div).style.visibility="hidden";
	}
	
function SwitchOnOff(id){
	id_value=document.getElementById(id).value;

	if(!id_value){
		document.getElementById(id).value='yes';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
	
	if(id_value=='yes'){
		document.getElementById(id).value='no';
		document.getElementById('img_' + id).src='img/status_critical.gif';
		return;
	}else{
		document.getElementById(id).value='yes';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
}

function SwitchOnOff_on(id){
id_value=document.getElementById(id).value;

	if(!id_value){
		document.getElementById(id).value='on';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
	
	if(id_value=='on'){
		document.getElementById(id).value='off';
		document.getElementById('img_' + id).src='img/status_critical.gif';
		return;
	}else{
		document.getElementById(id).value='on';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}        
}


function SwitchDenySkip(id){
id_value=document.getElementById(id).value;

	if(!id_value){
		document.getElementById(id).value='skip';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
	
	if(id_value=='skip'){
		document.getElementById(id).value='deny';
		document.getElementById('img_' + id).src='img/status_critical.gif';
		return;
	}else{
		document.getElementById(id).value='skip';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
}



function SwitchBigNumeric(id,callback){
	id_value=document.getElementById(id).value;

	if(!id_value){
		document.getElementById(id).value='1';
		document.getElementById('img_' + id).src='img/64-green.png';
		execute_function(callback);
		CheckBoxDesignHidden();
		return;
	}
	
	if(id_value=='1'){
		document.getElementById(id).value='0';
		document.getElementById('img_' + id).src='img/64-red.png';
		execute_function(callback);
		CheckBoxDesignHidden();
		return;
	}else{
		document.getElementById(id).value='1';
		document.getElementById('img_' + id).src='img/64-green.png';
		execute_function(callback);
		CheckBoxDesignHidden();
		return;
	}        
        
        
}

function Switch32Numeric(id){
	id_value=document.getElementById(id).value;

	if(!id_value){
		document.getElementById(id).value='1';
		document.getElementById('img_' + id).src='img/ok32.png';
		return;
	}
	
	if(id_value=='1'){
		document.getElementById(id).value='0';
		document.getElementById('img_' + id).src='img/danger32.png';
		return;
	}else{
		document.getElementById(id).value='1';
		document.getElementById('img_' + id).src='img/ok32.png';
		return;
	}        
}

function sleep(milliseconds) {
	  var start = new Date().getTime();
	  while(true) {
	    if ((new Date().getTime() - start) > milliseconds){
	      break;
	    }
	  }
	}


function SwitchNumeric(id){
	id_value=document.getElementById(id).value;

	document.getElementById('img_' + id).src='img/wait.gif';
	if(!id_value){
		document.getElementById(id).value='1';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
	
	if(id_value=='1'){
		document.getElementById(id).value='0';
		document.getElementById('img_' + id).src='img/status_critical.gif';
		return;
	}else{
		document.getElementById(id).value='1';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
}
function SwitchKeyOnOff(id){
	id_value=document.getElementById(id).value;

	if(!id_value){
		document.getElementById(id).value='justkey';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
	
	if(id_value=='justkey'){
		document.getElementById(id).value='nokey';
		document.getElementById('img_' + id).src='img/status_critical.gif';
		return;
	}else{
		document.getElementById(id).value='justkey';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
}	
function s_PopUp(url,l,h,asc){
	var PopupWindow=null;
        var toolbal="scrollbars=no";
        if(asc){
            toolbal="scrollbars=yes";    
        }
	settings='width='+l +',height='+h +',location=no,directories=no,menubar=no,toolbar=no,status=no,'+toolbal+',resizable=no,dependent=yes';
	PopupWindow=window.open(url,'',settings);
	PopupWindow.focus();
	} 
	
function s_PopUpScroll(url,l,h,mtitle){
	var PopupWindow=null;
	settings='width='+l +',height='+h +',location=no,directories=no,menubar=no,toolbar=no,status=no,scrollbars=yes,resizable=yes,dependent=yes';
	PopupWindow=window.open(url,mtitle,settings);
	PopupWindow.focus();
	PopupWindow.moveTo(0,0);
	}
        
function s_PopUpFull(url,l,h,mtitle){
	var PopupWindow=null;
	settings='width='+l +',height='+h +',location=no,directories=no,menubar=yes,toolbar=yes,status=yes,scrollbars=yes,resizable=yes,dependent=yes';
	PopupWindow=window.open(url,mtitle,settings);
	PopupWindow.focus();
	PopupWindow.moveTo(0,0);
	}      

function CheckBoxValidate(id){
	if(document.getElementById(id).checked){return 1;}
	return 0;
	
}

function SwitchTRUEFALSE(id){
	id_value=document.getElementById(id).value;
	id_value=id_value.toUpperCase();
	if(id_value.length==0){id_value='FALSE';}
	
	
	if(!id_value){
		document.getElementById(id).value='TRUE';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}
	
	if(id_value=='TRUE'|id_value=='true'|id_value=='1'){
		document.getElementById(id).value='FALSE';
		document.getElementById('img_' + id).src='img/status_critical.gif';
		return;
	}
	
	if(id_value=='FALSE'|id_value=='false'|id_value=='0'){
		document.getElementById(id).value='TRUE';
		document.getElementById('img_' + id).src='img/status_ok.gif';
		return;
	}	
	
}	

var xmain_cf_submit_fields= function (obj) {
	alert(obj.responseText);
}

function BuildXHRForms(){
	var inputs	= document.getElementsByTagName('input');
	var count	= inputs.length;
	var XHR = new XHRConnection();
	for (i = 0; i < count; i++) {
		_input = inputs.item(i);
		if(_input.type=='text'){
			XHR.appendData(_input.id, _input.value);
		}
		if(_input.type=='hidden'){
			XHR.appendData(_input.id, _input.value);
		}		
		
		
		if(_input.type=='checkbox'){
			if(_input.checked==true){
				XHR.appendData(_input.id, '1');
			}
			
			if(_input.checked==false){
				XHR.appendData(_input.id, '0');
			}
		}
	}
	return XHR;
	}
	
function FreeForms(list){
	var inputs	= document.getElementsByTagName('input');
	for (var i = 0; i < inputs.length; i++) {
		_input = inputs.item(i);
		if(hide_forms_parse(list,_input.id)==true){
			_input.disabled=false;
			}
		
		}
	}	
	
function hideForms(list){
	var inputs	= document.getElementsByTagName('input');
	for (var i = 0; i < inputs.length; i++) {
		_input = inputs.item(i);
		if(hide_forms_parse(list,_input.id)==true){
			_input.disabled=true;
			_input.value='';
			}
		
		}
	}
function hide_forms_parse(list,xname){
		var s_array;
		var divid;
		if(list.lastIndexOf(",")==-1){
			if(list==xname){return true}else{return false}
		}
		s_array=list.split(',');
	 	for(var i = 0; i < s_array.length; i++){
			divid=s_array[i];
			if (xname==divid){return true;}
		}	
	return false;
}
function Findusr(e){
	if(checkEnter(e)){
		FindUser();
	}
	
}

function checkEnter(e){
	var characterCode 
	characterCode = (typeof e.which != "undefined") ? e.which : event.keyCode;
	if(characterCode == 13){ return true;}else{return false;}
}

	
function main_cf_submit_fields(){
	var inputs	= document.getElementsByTagName('input');
	var count	= inputs.length;
	var XHR = new XHRConnection();
	for (i = 0; i < count; i++) {
		_input = inputs.item(i);
		if(_input.type=='text'){XHR.appendData(_input.id, _input.value);}
	}
	XHR.sendAndLoad('post.main.cf.php', "POST",xmain_cf_submit_fields);
}	
	
function hide_explains(){
var inputs	= document.getElementsByTagName('div');
	var count	= inputs.length;
	var id_name;
	for (i = 0; i < count; i++) {
		
		_input = inputs.item(i);
		id_name=_input.id;
		if (id_name.lastIndexOf("_explain")>0){
			closediv(id_name);
		}		
		
	    }
	}
	


function StartTimer(){
if (!document.images){return}
	parselimit=limit.split(":")
	parselimit=parselimit[0]*60+parselimit[1]*1;
	beginrefresh();	
	
}
function artica_StartTimer(){
if (!document.images){return}
	parselimit=limit.split(":")
	parselimit=parselimit[0]*60+parselimit[1]*1;
	artica_beginrefresh();	
	
}
function Load_artica_log(){
		var XHR = new XHRConnection();
		XHR.setRefreshArea('log_area');
		XHR.sendAndLoad('artica.log.php?logs=yes',"GET");	
}

function Load_mail_log(){
		var XHR = new XHRConnection();
		XHR.setRefreshArea('log_area');
		XHR.sendAndLoad('mail.log.php?logs=yes',"GET");	
}
function artica_beginrefresh(){
	if (parselimit==1){
		Load_artica_log();
		artica_StartTimer();
		}
	else{ 
		parselimit-=1
		curmin=Math.floor(parselimit/60)
		cursec=parselimit%60
		setTimeout("artica_beginrefresh()",500)
		}
}
	
function beginrefresh(){
	if (parselimit==1){
		Load_mail_log();
		StartTimer();
		}
	else{ 
		parselimit-=1
		curmin=Math.floor(parselimit/60)
		cursec=parselimit%60
		setTimeout("beginrefresh()",500)
		}
}
function HideBulle() {
	document.onmousemove = pointeurDeplace
	document.getElementById('PopUpInfos').style.visibility="hidden";
	document.getElementById('PopUpInfos').style.border ="none";
	document.getElementById('PopUpInfos').style.padding ="0";
	document.getElementById('PopUpInfos').style.zIndex='0';
	
}

function BuildLeftMenus(){
	var XHR = new XHRConnection();
	var currentPage=document.URL;
	XHR.setRefreshArea('menu');
	XHR.sendAndLoad('index.php?leftmenus=yes&url='+ currentPage,"GET");
}
function BuildTopMenus(){
	var XHR = new XHRConnection();
	var currentPage=document.URL;
	XHR.setRefreshArea('topmenus');
	XHR.sendAndLoad('index.php?topmenus=yes&url='+ currentPage,"GET");	
	}

function RemoveLogonCSS(){
	$('link[title="styles_main_css"]').remove();
	$('link[title="blurps_css"]').remove();
	
}


function AffBulle(texte) {
		document.onmousemove = pointeurDeplace;
  		var contenu=texte;
  		contenu=contenu.replace("'","`"); 
 	 	document.getElementById('PopUpInfos').innerHTML="<div style='background-color: transparent' OnMouseOver=\"javascript:HideBulle();\" ><table class=form><tr><td style='text-align:left;font-size:14px;background-color: transparent'>"+ contenu+ "</td></tr></table></div>";
	 	document.getElementById('PopUpInfos').style.width='auto';
		document.getElementById('PopUpInfos').style.height='auto';
        document.getElementById('PopUpInfos').style.top=(yMousePos -20) + 'px';
        document.getElementById('PopUpInfos').style.left=(xMousePos +15)+ 'px';
        document.getElementById('PopUpInfos').style.visibility="visible";
        
        if(document.getElementById('PopUpInfos').style.zIndex>99999){
            document.getElementById('PopUpInfos').style.zIndex=document.getElementById('PopUpInfos').style.zIndex+100;
        }else{document.getElementById('PopUpInfos').style.zIndex = "10000";}
		
  	}
	
function HideDive(DivName){
	 document.getElementById(DivName).innerHTML='';
	 document.getElementById(DivName).style.zIndex = "0";
	 document.getElementById(DivName).style.visibility = "hidden";
	 document.getElementById("windows").style.left='';
	
}
function x_ChangeFetchMailUser(obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>0){
                alert(tempvalue);
                document.getElementById('is').value='';
                document.getElementById('is_html').innerHTML='Change...';
        }
}

function mindTerm() { 
window.open("index.mindterm.php","","width=1,height=1,resizable=no,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no");
}





//----------------------------------------------------------- Global fetchmail rules

function SwitchFetchMailUserForm(id){
   document.getElementById('server_options').style.display='none';
   document.getElementById('users_options').style.display='none';
   document.getElementById(id).style.display='block';     
        
}


function ChangeFetchMailUser(){
	var text=document.getElementById('ChangeFetchMailUserText').value;
	var email=prompt(text);
	if(email){
		document.getElementById('_is').value=email;
		document.getElementById('is_html').innerHTML=email;
		
	}
        
        var XHR = new XHRConnection();
        XHR.appendData('ChangeFetchMailUser',email);
        XHR.sendAndLoad('artica.wizard.fetchmail.php', 'GET',x_ChangeFetchMailUser);        
	}

function UserFetchMailRule(num,userid){
if(document.getElementById('dialog3_c')){
        if(document.getElementById('dialog3_c').style.visibility=='visible'){
            YahooWin4('1050','artica.wizard.fetchmail.php?LdapRules='+ num + '&uid='+ userid,'Fetchmail rule');
            return true;
        }
}
       	YahooWin2('1050','artica.wizard.fetchmail.php?LdapRules='+ num + '&uid='+ userid,'Fetchmail rule');
        }
        
function UserDeleteFetchMailRule(num){
       var uid=document.getElementById('uid').value;
       if(confirm(document.getElementById('confirm').value)){
        var XHR = new XHRConnection();
        XHR.appendData('UserDeleteFetchMailRule',num);
        XHR.appendData('uid',uid);
        XHR.sendAndLoad('artica.wizard.fetchmail.php', 'GET',x_FetchMailPostForm);
       }
       if(document.getElementById('left')){
        LoadAjax('left',Working_page + '?LoadFetchMailRuleFromUser='+uid);              
       }
        if(document.getElementById(TEMP_BOX_ID)){RemoveDocumentID(TEMP_BOX_ID);}
        
        
        YahooWin2Hide();
        
        if(document.getElementById('timestamp-flexgrid')){
        	flex=document.getElementById('timestamp-flexgrid').value;
        	$('#flexRT'+flex).flexReload();
        	return;
        }        
       
                
}

function IsANetMask(value) {
    var mask = /^[1-2]{1}[2,4,5,9]{1}[0,2,4,5,8]{1}\.[0-2]{1}[0,2,4,5,9]{1}[0,2,4,5,8]{1}\.[0-2]{1}[0,2,4,5,9]{1}[0,2,4,5,8]{1}\.[0-9]{1,3}$/;    
    return value.match(mask);
}

function IsAnIpv4(value) {
    var ipv4 = /^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/;    
    return value.match(ipv4);
}

var x_FetchMailPostForm= function (obj) {
	var tempvalue=obj.responseText;
	var flex='';
	if(tempvalue.length>0){alert(tempvalue);}
        if(document.getElementById('fetchmail_users_datas')){LoadAjax('fetchmail_users_datas','users.fetchmail.index.php?LoadRules=yes');}
        
        if(document.getElementById('timestamp-flexgrid')){
        	flex=document.getElementById('timestamp-flexgrid').value;
        	YahooWin2Hide();
        	$('#flexRT'+flex).flexReload();
        	return;
        }
        if(document.getElementById('fetchmail_daemon_rules')){
        	LoadAjax('fetchmail_daemon_rules','fetchmail.daemon.rules.php?Showlist=yes&section=yes&tab=0',true);
        }
        YahooWin2Hide();
	}

function FetchMailPostForm(edit_mode){
	var pool=document.getElementById('MailBoxServer').value;
	if(pool.length==0){
		alert('Mailbox server = null !');
		return;
	}
	
	
	var XHR = new XHRConnection();
    XHR.appendData('edit_mode',edit_mode);
    XHR.appendData('rule_number',document.getElementById('rule_number').value);
	XHR.appendData('poll',document.getElementById('MailBoxServer').value);
	XHR.appendData('proto',document.getElementById('_proto').value);	
	XHR.appendData('port',document.getElementById('_port').value);	
	XHR.appendData('timeout',document.getElementById('_timeout').value);		
	XHR.appendData('interval',document.getElementById('_interval').value);		
	XHR.appendData('user',document.getElementById('_user').value);	
	XHR.appendData('pass',document.getElementById('_pass').value);		
	XHR.appendData('is',document.getElementById('_is').value);
    XHR.appendData('enabled',document.getElementById('_enabled').value);
    XHR.appendData('aka',document.getElementById('_aka').value);
    XHR.appendData('limit',document.getElementById('_limit').value);
    XHR.appendData('smtp_host',document.getElementById('_smtp_host').value);
    XHR.appendData('smtp_port',document.getElementById('_smtp_port').value);
    
    
    
    if(document.getElementById('_dropdelivered').checked){XHR.appendData('dropdelivered',1);}else{XHR.appendData('dropdelivered',0);}	
    if(document.getElementById('_multidrop').checked){XHR.appendData('multidrop',1);}else{XHR.appendData('multidrop',0);}	
	if(document.getElementById('_tracepolls').checked){XHR.appendData('tracepolls',1);}else{XHR.appendData('tracepolls',0);}	
	if(document.getElementById('_ssl').checked){XHR.appendData('ssl',1);}else{XHR.appendData('ssl',0);}
	if(document.getElementById('_fetchall').checked){XHR.appendData('fetchall',1);}else{XHR.appendData('fetchall',0);}		
	if(document.getElementById('_keep').checked){XHR.appendData('keep',1);}else{XHR.appendData('keep',0);}
	
	if(document.getElementById('_schedule')){XHR.appendData('schedule',document.getElementById('_schedule').value);}
	if(document.getElementById('_fingerprint')){XHR.appendData('sslfingerprint',document.getElementById('_fingerprint').value);}
	if(document.getElementById('_sslcertck').checked){XHR.appendData('sslcertck',1);}else{XHR.appendData('sslcertck',0);}
	if(document.getElementById('UseDefaultSMTP').checked){XHR.appendData('UseDefaultSMTP',1);}else{XHR.appendData('UseDefaultSMTP',0);}
	XHR.sendAndLoad('artica.wizard.fetchmail.php', 'GET',x_FetchMailPostForm);
       
        
}

//-----------------------------------------------------------
function RemoveDocumentID(ID){
	var element = document.getElementById(ID);
	element.remove(0);
	}


var x_parseform= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
		if(ParseFormUriReturnedBack){
			if(ParseFormUriReturnedBack.length>0){
              LoadAjax(ParseFormUriReturnedID,ParseFormUriReturnedBack);
			}
        }
	}
        
        
function ParseYahooForm(Form_name,pageToSend,return_box,noHidden){
		var XHR = new XHRConnection();
		var tetss;
		var type;
		if(!noHidden){noHidden=false;}
		
		with(window.document.forms[Form_name]){
                       
    		for (i=0; i<elements.length; i++){
                        type = elements[i].type;
                        FieldDisabled=elements[i].disabled;
			if(FieldDisabled==false){XHR.appendData(elements[i].name,elements[i].value);}
		}
	}
	if(return_box==true){		
		XHR.sendAndLoad(pageToSend, 'GET',x_parseform);}
		else{XHR.sendAndLoad(pageToSend, 'GET');}			
}


	 
function ParseForm(Form_name,pageToSend,return_box,noHidden,ReturnValues,idRefresh,uriRefresh,function_callback){
		var XHR = new XHRConnection();
		var type;
		var i;
		if(!noHidden){noHidden=false;}
        if(!ReturnValues){ReturnValues=false;}
		
		with(window.document.forms[Form_name]){
                       
    		for (i=0; i<elements.length; i++){
                        
        		type = elements[i].type;
                       // alert('type='+type+' '+ i+' '+elements[i].value+ ' diabled='+elements[i].disabled)
                        
				FieldDisabled=elements[i].disabled;
				if(FieldDisabled==false){
				
					switch (type){
            			case "text" :
							XHR.appendData(elements[i].name,elements[i].value);
							break;
					 
            			case "password" : 
							XHR.appendData(elements[i].name,elements[i].value);
							break;
            			case "hidden" :
							if(noHidden==false){
								XHR.appendData(elements[i].name,elements[i].value);
							}
							break;
            			case "textarea" :
							XHR.appendData(elements[i].name,elements[i].value);
							break;
                		case "radio" :
            			case "checkbox" :
                			if(elements[i].checked == true){
								XHR.appendData(elements[i].name,elements[i].value);
								}else{
								    if(elements[i].value=='1'){XHR.appendData(elements[i].name,"0");}
								    if(elements[i].value=='yes'){XHR.appendData(elements[i].name,"no")};
                                                                }
                    		break;			
            			case "select-one" :XHR.appendData(elements[i].name,elements[i].value);break;
            			case "select-multiple" :
                		}
			}
		}
	}
        
        if(ReturnValues==true){
                return XHR;
        }
        
	if(return_box==true){
		 		AnimateDiv(idRefresh);
                if(uriRefresh){
                  if(uriRefresh.length>0){
                        ParseFormUriReturnedBack=uriRefresh;
                        ParseFormUriReturnedID=idRefresh;
                  }
                }
	
                
                
       XHR.sendAndLoad(pageToSend, 'GET',x_parseform);
       }else{
    	   AnimateDiv(idRefresh);
           
    	   
    	   if(uriRefresh){
               if(uriRefresh.length>0){
                     ParseFormUriReturnedBack=uriRefresh;
                     ParseFormUriReturnedID=idRefresh;
               }}    	   
    	   
			if(isDefined(function_callback)){
				XHR.sendAndLoad(pageToSend, 'GET',function_callback);
			}else{
				XHR.sendAndLoad(pageToSend, 'GET');
			}
		}
}

function isDefined(variable){
return (!(!( variable||false )))
}


function ParseFormPOST(Form_name,pageToSend,return_box){
		var XHR = new XHRConnection();
		var tetss;
		var type;
		
		with(window.document.forms[Form_name]){
    		for (i=0; i<elements.length; i++){
        		type = elements[i].type;
				FieldDisabled=elements[i].disabled;
				if(FieldDisabled==false){
				
					switch (type){
            			case "text" :
							XHR.appendData(elements[i].name,elements[i].value);
							break;
					 
            			case "password" : 
							XHR.appendData(elements[i].name,elements[i].value);
							break;
            			case "hidden" :
							XHR.appendData(elements[i].name,elements[i].value);
							break;
            			case "textarea" :
							XHR.appendData(elements[i].name,elements[i].value);
							break;
                		case "radio" :
            			case "checkbox" :
                			if(elements[i].checked == true){
									if(elements[i].value=='1'){XHR.appendData(elements[i].name,"1");}
									if(elements[i].value=='yes'){XHR.appendData(elements[i].name,"yes");}
							}else{
								    if(elements[i].value=='1'){XHR.appendData(elements[i].name,"0");}
								    if(elements[i].value=='yes'){XHR.appendData(elements[i].name,"no")};
							}
                    		break;			
            			case "select-one" :XHR.appendData(elements[i].name,elements[i].value);break;
            			case "select-multiple" :
                		}
			}
		}
	}
	if(return_box==true){

		XHR.sendAndLoad(pageToSend, 'POST',x_parseform);}
		else{XHR.sendAndLoad(pageToSend, 'POST');}			
}

	

function LoadWindows(Windowswidth,windowsHeight,ajax,ajax_parameters,ShowIndex,ShowCenter){
                YahooWin(Windowswidth,ajax + '?'+ajax_parameters);
        }
function LoadFind(Windowswidth){
			if (document.getElementById("find").style.left==''){
	document.getElementById("find").style.left=xMousePos - 250 + 'px';document.getElementById("windows").style.top='100px';}
			document.getElementById("find").style.height='auto';
			document.getElementById("find").style.width=Windowswidth + 'px';
			document.getElementById("find").style.zIndex='3000';
    		document.getElementById("find").style.visibility="visible";	
			document.getElementById("find").innerHTML='<center>Loading</center>';
			
			}			



function EditLdapUser(dn){
		LoadWindows(450);
		var XHR = new XHRConnection();
		XHR.setRefreshArea('windows');
		XHR.appendData('EditLdapUser',dn);
		XHR.sendAndLoad('users.edit.php', 'GET');	
		}
		
		
function PageEditGroup(gpid){
		LoadWindows(650);
		var XHR = new XHRConnection();
		XHR.setRefreshArea('windows');
		XHR.appendData('PageEditGroup',gpid);
		XHR.sendAndLoad('group.edit.php', 'GET');	
		}
		
function TabGroupEdit(gpid,tab){
		LoadWindows(650);
		var XHR = new XHRConnection();
		XHR.setRefreshArea('windows');
		XHR.appendData('GroupEdit',gpid);
		XHR.appendData('tab',tab);
		XHR.sendAndLoad('group.edit.php', 'GET');	
	
}

function MyHref(url,add){
        var uri='';
        if(add){uri='&data=' + add}
	document.location.href=url+uri;
	}
function RefreshPostfixStatus(){
	
	
}

function YahooWinS(width,uri,title,waitfor){
	AnimateDiv('dialogS');
LockPage();
	if(!width){width='300';}
    if(!title){title='Windows';}
	$('#dialogS').dialog( 'destroy' );
	$(function(){
	$('#dialogS').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
	$('#dialogS').dialog( "option", "zIndex", 8999 );
}

function YahooError(width,uri,title,waitfor){
LockPage();
	$("#SetupControl").dialog( {
			buttons: {"Ok" : function () {
				$(this).dialog("close");
			}
		},

	   
	    width: width+'px',
	    modal: true,
	    resizable: false,
	    title: title

	} ).load(uri, function() { UnlockPage(); });
	$('#SetupControl').dialog( "option", "zIndex", 10000 );
}







function YahooWinT(width,uri,title,waitfor){
	if(!width){width='300';}
	AnimateDiv('dialogT');
    	if(!title){title='Windows';}
 	
        if(!title){title='Windows';}
	$(function(){$('#dialogT').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
	$('#dialogT').dialog( "option", "zIndex", 8999 );
	}


function YahooWin0(width,uri,title,waitfor){
        if(!width){width='750';}
        if(!title){title='Windows';}	
        
	$('#dialog0').dialog( 'destroy' );
	LockPage();
	AnimateDiv('dialog0');
	$(function(){$('#dialog0').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
	$('#dialog0').dialog( "option", "zIndex", 8999 );
	}

function AnimateDiv(id){
	var animated="/img/wait_verybig.gif";
	if(!document.getElementById(id)){return;}
	if(document.getElementById("LoadAjaxPicture")){animated=document.getElementById("LoadAjaxPicture").value;}
	
	if(!document.getElementById("globalContainer")){
		if(id=='BodyContent'){animated='/img/ajax-loader-white.gif';}
		if(id=='middle'){animated='/img/ajax-loader-white.gif';}
		if(id=='TEMPLATE_LEFT_MENUS'){animated='/img/ajax-loader-white.gif';}
	}

	document.getElementById(id).innerHTML='<div style="width:100%;height:auto"><center><img src="'+animated+'"></center></div>';
}

function AnimateDivRound(id){
	document.getElementById(id).innerHTML='<div style="width:100%;height:auto"><center style="margin:50px"><img src="img/loader.gif"></center></div>';

}

function YahooSetupControlModal(width,uri,title){
    if(!width){width='300';}
    if(!title){title='Windows';}
    $('#SetupControl').dialog( 'destroy' );
    AnimateDiv('SetupControl');
    LockPage();
    YahooWin2Hide();
    $(function(){
    		$('#SetupControl').dialog({
    			autoOpen: true,
    			modal:true,
    			closeOnEscape: true,
    			width: width+'px',
    			title: title,
    			position: 'top'
    		}
    		).load(uri, function() { UnlockPage(); });
    	}
    );
    $('#SetupControl').dialog({ stack: true }); 
    $('#SetupControl').dialog( "option", "zIndex", 5000 );
    }

function YahooSetupControlModalFixed(width,uri,title){
    if(!width){width='300';}
    if(!title){title='Windows';}
    $('#SetupControl').dialog( 'destroy' );
    AnimateDiv('SetupControl');
    LockPage();
    YahooWin2Hide();
    $(function(){$('#SetupControl').dialog({autoOpen: true,modal:true,closeOnEscape: true,draggable: false,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
    
    $('#SetupControl').dialog({ stack: true }); 
    $('#SetupControl').dialog( "option", "zIndex", 5000 );
    }
function YahooSetupControlModalFixedNoclose(width,uri,title){
    if(!width){width='300';}
    if(!title){title='Windows';}
    $('#SetupControl').dialog( 'destroy' );
    AnimateDiv('SetupControl');
    LockPage();
    YahooWin2Hide();
    $(function(){$('#SetupControl').dialog(
    		{
    		autoOpen: true,modal:true,closeOnEscape: false,
    		open: function(event, ui) {
    				$(".ui-dialog-titlebar-close", ui.dialog || ui).hide(); 
    			},
    	draggable: false,width: width+'px',title: title}).load(uri, function() { UnlockPage(); });});
    
    $('#SetupControl').dialog({ stack: true }); 
    $('#SetupControl').dialog( "option", "zIndex", 5000 );
    }



function YahooWin(width,uri,title,waitfor,pos){
	
        if(!width){width='300';}
        if(!title){title='Windows';}
        document.getElementById('dialog1').innerHTML='';
        $('#dialog1').dialog( 'destroy' );
        $('#dialog1').dialog( "option", "zIndex", 8999 );
        AnimateDiv('dialog1');
	LockPage();

        if(waitfor){
        	if(pos){
        		$(function(){
        			$('#dialog1').dialog({
        				autoOpen: true,modal:true,closeOnEscape: false,
        				width: width+'px',
        				title: title,position: pos,
        				zIndex:8999,
        				open: function(event, ui) { 
        				$(this).parent().children().children('.ui-dialog-titlebar-close').hide();
        				}}).load(uri, function() { UnlockPage(); });});
        		

        	}else{
        		$(function(){$('#dialog1').dialog({autoOpen: true,zIndex:8999,modal:true,closeOnEscape: true,width: width+'px',title: title,position: 'center',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        	}
        	
        }else{
        	 $(function(){$('#dialog1').dialog({autoOpen: true,zIndex:8999,closeOnEscape: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri,function() { UnlockPage(); });});
        }


	}

function HideTips(md5,uid){
	var XHR = new XHRConnection();
	XHR.appendData('HideTips',md5+'-'+uid);
	XHR.sendAndLoad('admin.index.php', 'GET');	
	document.getElementById(md5+'-id').innerHTML='';
	document.getElementById(md5+'-id').style.width='0px';
	document.getElementById(md5+'-id').style.heigth='0px';
	document.getElementById(md5+'-id').className='';
}

function LockPage(){
	$.blockUI();
	setTimeout("UnlockPage()",15000);
	
}

function UnlockPage(){
	$.unblockUI();
}
        
function YahooWin2(width,uri,title,waitfor){
        if(!width){width='300';}
        if(!title){title='Windows';}
        document.getElementById('dialog2').innerHTML='';
        $('#dialog2').dialog( 'destroy' );
        AnimateDiv('dialog2');
        LockPage();
        $(function(){$('#dialog2').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#dialog2').dialog({ closeOnEscape: true });
        $('#dialog2').dialog({ stack: true });
        $('#dialog2').dialog( "option", "zIndex", 8999 );
        } 

function YahooSetupControl(width,uri,title,waitfor){
    if(!width){width='300';}
    if(!title){title='Windows';}
    $('#SetupControl').dialog( 'destroy' );
    AnimateDiv('SetupControl');
    YahooWin2Hide();
    LockPage();
    $(function(){$('#SetupControl').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
    $('#SetupControl').dialog({ closeOnEscape: true });
    $('#SetupControl').dialog({ stack: true }); 
    $('#SetupControl').dialog( "option", "zIndex", 8999 );
    }

function RTMMail(width,uri,title,waitfor){
    if(!width){width='300';}
    if(!title){title='Windows';}
    $('#RTMMail').dialog( 'destroy' );
    AnimateDiv('RTMMail');
    LockPage();
    $(function(){$('#RTMMail').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
    $('#RTMMail').dialog({ closeOnEscape: true });
    $('#RTMMail').dialog({ stack: true }); 
    $('#RTMMail').dialog( "option", "zIndex", 8999 );
   }

function YahooWinBrowse(width,uri,title,waitfor){
	if(!width){width='300';}
    if(!title){title='Windows';}
    $('#Browse').dialog( 'destroy' );
    AnimateDiv('Browse');
    LockPage();
    $(function(){$('#Browse').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
    $('#Browse').dialog({ closeOnEscape: true });
    $('#Browse').dialog({ stack: true });
    $('#Browse').dialog( "option", "zIndex", 8999 );
   }

function YahooWin3(width,uri,title,waitfor){
		if(YahooWin3Open()){return;}
        if(!width){width='300';}
        if(!title){title='Help';}
    	$('#dialog3').dialog( 'destroy' );
    	AnimateDiv('dialog3');
    	LockPage();
    	$(function(){$('#dialog3').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#dialog3').dialog({ closeOnEscape: true });
        $('#dialog3').dialog({ stack: true });
        $('#dialog3').dialog( "option", "zIndex", 8999 );
        }

function YahooWin4(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#dialog4').dialog( 'destroy' );
    	AnimateDiv('dialog4');
	LockPage();
    	$(function(){$('#dialog4').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#dialog4').dialog({ closeOnEscape: true });
        $('#dialog4').dialog({ stack: true }); 
        $('#dialog4').dialog( "option", "zIndex", 8999 );
        }

function YahooWin5(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#dialog5').dialog( 'destroy' );
    	AnimateDiv('dialog5');
	LockPage();
    	$(function(){$('#dialog5').dialog({autoOpen: true,width: width+'px',zIndex:8999,title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#dialog5').dialog({ closeOnEscape: true });
        $('#dialog5').dialog({ stack: true });  
        $('#dialog5').dialog( "option", "zIndex", 8999 );
        }

// A supprimer document.getElementById("YahooUser_c") is null;
function YahooWin6(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#dialog6').dialog( 'destroy' );
    	AnimateDiv('dialog6');
	LockPage();
    	$(function(){$('#dialog6').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#dialog6').dialog({ closeOnEscape: true });
        $('#dialog6').dialog({ stack: true });
        $('#dialog6').dialog( "option", "zIndex", 8999 );
        }
function LoadWinORG(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#WinORG').dialog( 'destroy' );
    	AnimateDiv('WinORG');
	LockPage();
    	$(function(){$('#WinORG').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#WinORG').dialog({ closeOnEscape: true });
        $('#WinORG').dialog({ stack: true }); 
        $('#WinORG').dialog( "option", "zIndex", 8999 );
        }
function LoadWinORG2(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#WinORG2').dialog( 'destroy' );
    	AnimateDiv('WinORG2');
	LockPage();
    	$(function(){$('#WinORG2').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#WinORG2').dialog({ closeOnEscape: true });
        $('#WinORG2').dialog({ stack: true }); 
        $('#WinORG2').dialog( "option", "zIndex", 8999 );
        }


function DisplayYoutube(uri){
	YahooLogWatcher(900,'youtube.play.php?uri='+uri,'Youtube Video');
}

function YahooLogWatcher(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#logsWatcher').dialog( 'destroy' );
    	AnimateDiv('logsWatcher');
	LockPage();
    	$(function(){$('#logsWatcher').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#logsWatcher').dialog({ closeOnEscape: true });
        $('#logsWatcher').dialog({ stack: true }); 
        $('#logsWatcher').dialog( "option", "zIndex", 8999 );
        }
function YahooUser(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#YahooUser').dialog( 'destroy' );
    	AnimateDiv('YahooUser');
	LockPage();
    	$(function(){$('#YahooUser').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#YahooUser').dialog({ closeOnEscape: true });
        $('#YahooUser').dialog({ stack: true }); 
        $('#YahooUser').dialog( "option", "zIndex", 8999 );
        }  
function YahooSearchUser(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#SearchUser').dialog( 'destroy' );
    	AnimateDiv('SearchUser');
	LockPage();
    	$(function(){$('#SearchUser').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#SearchUser').dialog({ closeOnEscape: true });
        $('#SearchUser').dialog({ stack: true }); 
        $('#SearchUser').dialog( "option", "zIndex", 8999 );
        } 

function YahooPopup(width,uri,title,waitfor){
	if(!width){width='300';}
        if(!title){title='Windows';}
    	$('#PopUpInfos').dialog( 'destroy' );
    	AnimateDiv('PopUpInfos');
	LockPage();
    	$(function(){$('#PopUpInfos').dialog({autoOpen: true,width: width+'px',title: title,position: 'top',zIndex:8999}).load(uri, function() { UnlockPage(); });});
        $('#PopUpInfos').dialog({ closeOnEscape: true });
        $('#PopUpInfos').dialog({ stack: true }); 
        $('#PopUpInfos').dialog( "option", "zIndex", 8999 );
        } 



function YahooWin0Hide(){  $('#dialog0').dialog( "option", "zIndex", 10 );$('#dialog0').empty();$('#dialog0').dialog( 'destroy' );}
function YahooWinBrowseHide(){$('#Browse').dialog( "option", "zIndex", 10 );$('#Browse').empty();$('#Browse').dialog( 'destroy' );}
function RTMMailHide(){$('#RTMMail').dialog( "option", "zIndex", 10 );$('#RTMMail').empty();$('#RTMMail').dialog( 'destroy' );}
function YahooSetupControlHide(){$('#SetupControl').dialog( "option", "zIndex", 10 );$('#SetupControl').empty();$('#SetupControl').dialog( 'destroy' );}
function YahooWinHide(){$('#dialog1').dialog( "option", "zIndex", 10 );$('#dialog1').empty();$('#dialog1').dialog('destroy');}
function YahooWin2Hide(){$('#dialog2').dialog( "option", "zIndex", 10 );$('#dialog2').empty();$('#dialog2').dialog('destroy');}
function YahooWin3Hide(){$('#dialog3').dialog( "option", "zIndex", 10 );$('#dialog3').empty();$('#dialog3').dialog('destroy');}
function YahooWin4Hide(){$('#dialog4').dialog( "option", "zIndex", 10 );$('#dialog4').empty();$('#dialog4').dialog('destroy');}
function YahooWin5Hide(){$('#dialog5').dialog( "option", "zIndex", 10 );$('#dialog5').empty();$('#dialog5').dialog('destroy');}
function YahooWin6Hide(){$('#dialog6').dialog( "option", "zIndex", 10 );$('#dialog6').empty();$('#dialog6').dialog('destroy');}
function YahooWinTHide(){$('#dialogT').dialog( "option", "zIndex", 10 );$('#dialogT').empty();$('#dialogT').dialog('destroy');}
function YahooLogWatcherHide(){$('#logsWatcher').dialog( "option", "zIndex", 10 );$('#logsWatcher').empty();$('#logsWatcher').dialog( 'destroy' );}
function YahooUserHide(){$('#YahooUser').dialog( "option", "zIndex", 10 );$('#YahooUser').empty();$('#YahooUser').dialog( 'destroy' );}
function WinORGHide(){$('#WinORG').dialog( "option", "zIndex", 10 );$('#WinORG').empty();$('#WinORG').dialog( 'destroy' );}
function WinORG2Hide(){$('#WinORG2').dialog( "option", "zIndex", 10 );$('#WinORG2').empty();$('#WinORG2').dialog( 'destroy' );}
function YahooLogWatcherHide(){$('#logsWatcher').dialog( "option", "zIndex", 10 );$('#logsWatcher').empty();$('#logsWatcher').dialog( 'destroy' );}
function YahooSearchUserHide(){$('#SearchUser').dialog( "option", "zIndex", 10 );$('#SearchUser').empty();$('#SearchUser').dialog( 'destroy' );}
function YahooWinSHide(){$('#dialogS').dialog( "option", "zIndex", 10 );$('#dialogS').empty();$('#dialogS').dialog( 'destroy' );}
function YahooPopupHide(){$('#PopUpInfos').dialog( "option", "zIndex", 10 );$('#PopUpInfos').empty();$('#PopUpInfos').dialog( 'destroy' );}



function RemoveZindexes(){
	$('#dialogS').dialog( "option", "zIndex", 10 );
	$('#SearchUser').dialog( "option", "zIndex", 10 );
	$('#logsWatcher').dialog( "option", "zIndex", 10 );
	$('#WinORG2').dialog( "option", "zIndex", 10 );
	$('#WinORG').dialog( "option", "zIndex", 10 );
	$('#YahooUser').dialog( "option", "zIndex", 10 );
	$('#logsWatcher').dialog( "option", "zIndex", 10 );
}

function RTMMailOpen(){
	var html=$("#RTMMail").html();
	if(html.length==0){return false;}
	return $('#RTMMail').dialog('isOpen');
	}
function YahooWinSOpen(){
	var html=$("#dialogS").html();
	if(html.length==0){return false;}	
	return $('#dialogS').dialog('isOpen');
	}
function YahooWinOpen(){
	var html=$("#dialog1").html();
	if(html.length==0){return false;}		
	return $('#dialog1').dialog('isOpen');
	}  
function YahooWin5Open(){
	var html=$("#dialog5").html();
	if(html.length==0){return false;}		
	return $('#dialog5').dialog('isOpen');
	}  
function YahooWin3Open(){
	var html=$("#dialog3").html();
	if(html.length==0){return false;}	
	return $('#dialog3').dialog('isOpen');
	}  
function YahooLogWatcherOpen(){
	var html=$("#logsWatcher").html();
	if(html.length==0){return false;}		
	return $('#logsWatcher').dialog('isOpen');
	}  
function YahooSetupControlOpen(){
	var html=$("#SetupControl").html();
	if(html.length==0){return false;}			
	return $('#SetupControl').dialog('isOpen');
	}  
function YahooSearchUserOpen(){
	var html=$("#SearchUser").html();
	if(html.length==0){return false;}			
	return $('#SearchUser').dialog('isOpen');
	}  
function YahooUserOpen(){
	var html=$("#YahooUser").html();
	if(html.length==0){return false;}		
	return $('#YahooUser').dialog('isOpen');
	}  
function YahooWin6Open(){
	var html=$("#dialog6").html();
	if(html.length==0){return false;}		
	return $('#dialog6').dialog('isOpen');
	} 
function YahooWin4Open(){
	var html=$("#dialog4").html();
	if(html.length==0){return false;}			
	return $('#dialog4').dialog('isOpen');
	} 
function YahooWin5Open(){
	var html=$("#dialog5").html();
	if(html.length==0){return false;}		
	return $('#dialog5').dialog('isOpen');
	} 
function YahooWin3Open(){
	var html=$("#dialog3").html();
	if(html.length==0){return false;}		
	return $('#dialog3').dialog('isOpen');
	} 
function YahooWin2Open(){
	var html=$("#dialog2").html();
	if(html.length==0){return false;}		
	return $('#dialog2').dialog('isOpen');} 
function WinORGOpen(){
	var html=$("#WinORG").html();
	if(html.length==0){return false;}		
	return $('#WinORG').dialog('isOpen');
	}
function YahooWinBrowseOpen(){
	var html=$("#Browse").html();
	if(html.length==0){return false;}		
	return $('#Browse').dialog('isOpen');
	}

function IfWindowsOpen(){
	if(RTMMailOpen()){return true;}
	RTMMailHide();
	
	if(YahooWinSOpen()){return true;}
	YahooWinSHide();
	
	if(YahooWinOpen()){return true;}
	YahooWinHide();
	
	if(YahooWin5Open()){return true;}
	YahooWin5Hide();
	
	if(YahooLogWatcherOpen()){return true;}
	YahooLogWatcherHide();
	
	if(YahooSetupControlOpen()){return true;}
	YahooSetupControlHide();
	
	if(YahooSearchUserOpen()){return true;}
	YahooSearchUserHide();
	
	if(YahooUserOpen()){return true;}
	YahooUserHide();
	
	if(YahooWin6Open()){return true;}
	YahooWin6Hide();
	
	if(YahooWin4Open()){return true;}
	YahooWin4Hide();
	if(YahooWin5Open()){return true;}
	YahooWin5Hide();
	
	if(YahooWin3Open()){return true;}
	YahooWin3Hide();
	
	if(YahooWin2Open()){return true;}
	YahooWin2Hide();
	
	if(WinORGOpen()){return true;}
	
	if(YahooWinBrowseOpen()){return true;}
	
	return false;
	}



function RefreshTab(id){
	var $tabs = $('#'+id).tabs();
	var selected =$tabs.tabs('option', 'selected'); 
	$tabs.tabs( 'load' , selected );
	
}

function DisableFieldsFromId(idToParse){
	$('input,select,hidden,textarea', '#'+idToParse).each(function() {
	 	var $t = $(this);
	 	var id=$t.attr('id');
	 	var value=$t.attr('value');
	 	var type=$t.attr('type');
	 	document.getElementById(id).disabled=true;
	});	
	
}
function EnableFieldsFromId(idToParse){
	$('input,select,hidden,textarea', '#'+idToParse).each(function() {
	 	var $t = $(this);
	 	var id=$t.attr('id');
	 	var value=$t.attr('value');
	 	var type=$t.attr('type');
	 	document.getElementById(id).disabled=false;
	});	
	
}

function SelectTabID(tabid,num){
	var $tabs = $('#'+tabid).tabs();
	$tabs.tabs( 'select' , num );
}

function RefreshLeftMenu(){
	LoadAjax('TEMPLATE_LEFT_MENUS','/admin.tabs.php?left-menus=yes',true);	
}

function CloseCacheOff(){
	
	YahooLogWatcherHide();
	UnlockPage();
	ReloadjQuery();
}




var x_CacheOff= function (obj) {
	var response=obj.responseText;
	
	
	
	if(response){
		document.getElementById('logsWatcher').innerHTML=response;
		$(function(){$('#logsWatcher').dialog({
				autoOpen: true,width: '550px',
				position: 'center',
				modal:true,
				open: function(event, ui) { 
    				$(this).parent().children().children('.ui-dialog-titlebar-close').hide();
    				}	
		
			}).fadeIn(2000);
		});
		$('#logsWatcher').fadeOut(3000);
		setTimeout("CloseCacheOff()",3000);
	}
	
	RefreshLeftMenu();
	AjaxTopMenu('template-top-menus','admin.top.menus.php');
	LayersTabsAllAfter();
}
function LayersTabsAllAfter(){
	if(document.getElementById('squid_main_config')){RefreshTab('squid_main_config');}
	if(document.getElementById('TEMPLATE_LEFT_MENUS')){ RefreshLeftMenu();}
	if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
	if(document.getElementById('squid_hotspot')){RefreshTab('squid_hotspot');;}
	if(document.getElementById('squid_main_svc')){RefreshTab('squid_main_svc');}
	if(document.getElementById('main_dansguardian_mainrules')){RefreshTab('main_dansguardian_mainrules');}
	if(document.getElementById('main_cache_rules_main_tabs')){RefreshTab('main_cache_rules_main_tabs');}
	if(document.getElementById('rules-toolbox-left')){LoadAjaxTiny('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');}
	if(document.getElementById('ufdb-main-toolbox-status')){LoadAjaxTiny('ufdb-main-toolbox-status','dansguardian2.mainrules.php?rules-toolbox-left=yes');}	
	if(document.getElementById('squid_main_config')){RefreshTab('squid_main_config');}
	if(document.getElementById('main_system_settings')){RefreshTab('main_system_settings');}
	if(document.getElementById('main_config_postfix_security')){RefreshTab('main_config_postfix_security');}
	if(document.getElementById('org_main')){RefreshTab('org_main');}
	if(document.getElementById('main_config_samba')){RefreshTab('main_config_samba');}
	if(document.getElementById('main_squidcachperfs')){RefreshTab('main_squidcachperfs');}
	if(document.getElementById('main_group_config')){RefreshTab('main_group_config');}
	if(document.getElementById('main_config_postfix')){RefreshTab('main_config_postfix');}
	if(document.getElementById('main_post_perfs_tabs')){RefreshTab('main_post_perfs_tabs');}
	if(document.getElementById('main_config_dhcpd')){RefreshTab('main_config_dhcpd');}
	if(document.getElementById('admin_perso_tabs')){RefreshTab('admin_perso_tabs');}
	if(document.getElementById('squid_blocked_stats')){RefreshTab('squid_blocked_stats');}
	if(document.getElementById('main_squid_quicklinks_tabs')){RefreshTab('main_squid_quicklinks_tabs');}
	if(document.getElementById('squid_stats_consumption')){RefreshTab('squid_stats_consumption');}
	if(document.getElementById('main_config_cyrus')){RefreshTab('main_config_cyrus');}
	if(document.getElementById('main_config_roundcube')){RefreshTab('main_config_roundcube');}
	if(document.getElementById('main_config_fetchmail')){RefreshTab('main_config_fetchmail');}
	if(document.getElementById('template-top-menus')){LoadAjaxTiny('template-top-menus','admin.top.menus.php');}
	if(document.getElementById('squid_main_svc')){RefreshTab('squid_main_svc');}
	if(document.getElementById('main_samba_quicklinks_config')){RefreshTab('main_samba_quicklinks_config');}
	if(document.getElementById('main_computer_infos_quicklinks')){RefreshTab('main_computer_infos_quicklinks');}
	if(document.getElementById('main_config_freeweb')){RefreshTab('main_config_freeweb');}
	if(document.getElementById('container-users-tabs')){RefreshTab('container-users-tabs');}
	if(document.getElementById('main_squid_statsquicklinks_config')){RefreshTab('main_squid_statsquicklinks_config');}
	if(document.getElementById('main_config_zarafa2')){RefreshTab('main_config_zarafa2');}
	if(document.getElementById('tabs_listnics2')){RefreshTab('tabs_listnics2');}
	if(document.getElementById('squid_main_caches_new')){RefreshTab('squid_main_caches_new');}
	if(document.getElementById('main_config_zarafa')){RefreshTab('main_config_zarafa');}
	if(document.getElementById('squid_stats_central')){RefreshTab('squid_stats_central');}
	if(document.getElementById('admin_index_settings')){RefreshTab('admin_index_settings');}
	if(document.getElementById('main_config_roundcube')){RefreshTab('main_config_roundcube');}
	if(document.getElementById('main_config_cicap')){RefreshTab('main_config_cicap');}
	if(document.getElementById('squid_compilation_status')){RefreshTab('squid_compilation_status');}
	if(document.getElementById('main_cache_rules_main_tabs')){RefreshTab('main_cache_rules_main_tabs');}
	if(document.getElementById('main_config_cicap')){RefreshTab('main_config_cicap');}
	if(document.getElementById('squid_conf_tabs')){RefreshTab('squid_conf_tabs');}
	if(document.getElementById('main_kav4proxy_config')){RefreshTab('main_kav4proxy_config');}
	if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
	if(document.getElementById('container-computer-tabs')){RefreshTab('container-computer-tabs');}
	if(document.getElementById('admin_perso_tabs-ID')){RefreshTab(document.getElementById('admin_perso_tabs-ID').value);}
	if(document.getElementById('main_config_artica_update')){RefreshTab('main_config_artica_update');}
	if(document.getElementById('main_squid_videocache_tabs')){RefreshTab('main_squid_videocache_tabs');}
	if(document.getElementById('main_squid_templates-tabs')){RefreshTab('main_squid_templates-tabs');}
	if(document.getElementById('main_artica_license')){RefreshTab('main_artica_license');}
	if(document.getElementById('main_icapwebfilter_tabs')){RefreshTab('main_icapwebfilter_tabs');}
	if(document.getElementById('squid_booster_tab')){RefreshTab('squid_booster_tab');}
	if(document.getElementById('main-config-sslbump-id') ){RefreshTab(document.getElementById('main-config-sslbump-id').value); }
	if(document.getElementById('container-users-tabs') ){RefreshTab('container-users-tabs'); }
	if(document.getElementById('main_artica_wordpress') ){RefreshTab('main_artica_wordpress'); }
	if(document.getElementById('squid_transparent_popup_tabs') ){RefreshTab('squid_transparent_popup_tabs'); }
	if(document.getElementById('main_l7filter_center') ){RefreshTab('main_l7filter_center'); }
	if(document.getElementById('sarg_tabs') ){RefreshTab('sarg_tabs'); }
	if(document.getElementById('main_ufdbcat_config') ){RefreshTab('main_ufdbcat_config'); }


	if(document.getElementById('btrfs-tabs') ){RefreshTab('btrfs-tabs');}
	if(document.getElementById('partinfosdiv') ){RefreshTab('partinfosdiv');}

	if(document.getElementById('squid-identd-upd-error')){ AnimateDiv('BodyContent');LoadAjax('BodyContent','squid.identd.php');}
	
	if(document.getElementById('CACHE_CENTER_TABLEAU')){ $('#'+document.getElementById('CACHE_CENTER_TABLEAU').value).flexReload();}
	if(document.getElementById('freewebs-table-id')){  $('#'+document.getElementById('freewebs-table-id').value).flexReload();}

	if(document.getElementById('squid-services')){ LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes',false); }
	if(document.getElementById('logger-status')){ LoadAjax('logger-status','squid.loggers.status.php?logger-status=yes',false); }
	if(document.getElementById('kerbchkconf')){ LoadAjaxRound('kerbchkconf','squid.adker.php?kerbchkconf=yes'); }
	if(document.getElementById('squid.update.php')){ LoadAjaxRound('main-proxy-update-table','squid.update.php'); }
	if(document.getElementById('UFDBCAT_STATUS')){LoadAjax('UFDBCAT_STATUS','ufdbcat.php?status=yes');}
	if(document.getElementById('thisIsTheSquidDashBoard')){LoadAjaxRound('main-dashboard-proxy','squid.dashboard.php');}
	if(document.getElementById('TABLE_SQUID_PORTS')){ $('#'+document.getElementById('TABLE_SQUID_PORTS').value).flexReload();}
	if(document.getElementById('MAIN_PAGE_ORGANIZATION_LIST')){ $('#'+document.getElementById('MAIN_PAGE_ORGANIZATION_LIST').value).flexReload();}
	if(document.getElementById('TABLE_SEARCH_USERS')){ $('#'+document.getElementById('TABLE_SEARCH_USERS').value).flexReload();}
	if(document.getElementById('system-main-status')){ LoadAjaxRound('system-main-status','admin.dashboard.system.php');}
	if(document.getElementById('thisIsThePostfixDashBoard')){LoadAjaxRound('messaging-dashboard','admin.dashboard.postfix.php');}


}	
		



var x_CacheOffSilent= function (obj) {
	var response=obj.responseText;
	
}


function CacheOffSilent(){
	var XHR = new XHRConnection();
	XHR.appendData('cache','yes');
	XHR.sendAndLoad('CacheOff.php', 'GET',x_CacheOffSilent);	
	}

function CacheOff(){
	LockPage();
	var XHR = new XHRConnection();
	XHR.appendData('cache','yes');
	XHR.sendAndLoad('CacheOff.php', 'GET',x_CacheOff);	
	}

var x_remove_cache= function (obj) {
	var response=obj.responseText;
	RefreshLeftMenu();
}

function remove_cache(){
	var XHR = new XHRConnection();
	XHR.appendData('cache','yes');
	XHR.sendAndLoad('/CacheOff.php', 'GET',x_remove_cache);
}


function execJS(node){
  var bSaf = (navigator.userAgent.indexOf('Safari') != -1);
  var bOpera = (navigator.userAgent.indexOf('Opera') != -1);
  var bMoz = (navigator.appName == 'Netscape');

  if (!node) return;

  /* IE wants it uppercase */
  var st = node.getElementsByTagName('SCRIPT');
  var strExec;

  for(var i=0;i<st.length; i++)
  {
    if (bSaf) {
      strExec = st[i].innerHTML;
      st[i].innerHTML = "";
    } else if (bOpera) {
      strExec = st[i].text;
      st[i].text = "";
    } else if (bMoz) {
      strExec = st[i].textContent;
      st[i].textContent = "";
    } else {
      strExec = st[i].text;
      st[i].text = "";
    }

    try {
      var x = document.createElement("script");
      x.type = "text/javascript";

      /* In IE we must use .text! */
      if ((bSaf) || (bOpera) || (bMoz))
        x.innerHTML = strExec;
      else x.text = strExec;

      document.getElementsByTagName("head")[0].appendChild(x);
    } catch(e) {
      alert(e);
    }
  }
};        
        
        
function Default_ApplyConfigPostfix(){Loadjs('/postfix.compile.php');}
        
        
function LoadIframe(iframe_id){
	var iframeids=[iframe_id]
	var iframehide="yes"
	var getFFVersion=navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1]
	var FFextraHeight=parseFloat(getFFVersion)>=0.1? 16 : 0 


		var dyniframe=new Array()
			for (i=0; i<iframeids.length; i++){
				if (document.getElementById){ 
					dyniframe[dyniframe.length] = document.getElementById(iframeids[i]);
					if (dyniframe[i] && !window.opera){
						dyniframe[i].style.display="block"
						if (dyniframe[i].contentDocument && dyniframe[i].contentDocument.body.offsetHeight) //ns6 syntax
							dyniframe[i].height = dyniframe[i].contentDocument.body.offsetHeight+FFextraHeight; 
						else if (dyniframe[i].Document && dyniframe[i].Document.body.scrollHeight) //ie5+ syntax
							dyniframe[i].height = dyniframe[i].Document.body.scrollHeight;
						}
					}
				
					if ((document.all || document.getElementById) && iframehide=="no"){
						var tempobj=document.all? document.all[iframeids[i]] : document.getElementById(iframeids[i])
						tempobj.style.display="block"
					}
				}				
	

}	

		
		
		

	
function Tree_Internet_domain_delete_transport(MyDomain,ou,suffix){
	var XHR = new XHRConnection();
	XHR.appendData('Tree_Internet_domain_delete_transport',MyDomain);
	XHR.appendData('ou',ou);
	XHR.setRefreshArea('rightInfos');
	XHR.sendAndLoad('domains.php', 'GET');	
	ReloadBranch('ou:ou=' + ou + ','+ suffix);	
	}
	


function Tree_ou_Add_user(ou,suffix){
	var MyUserName;
	var input_text=document.getElementById('inputbox add user').value;
if (MyUserName=prompt(input_text)){
		var XHR = new XHRConnection();
		XHR.appendData('Tree_ou_Add_user',MyUserName);
		XHR.appendData('ou',ou);
		XHR.setRefreshArea('rightInfos');
		XHR.sendAndLoad('domains.php', 'GET');	
		ReloadBranch('ou:ou=' + ou + ','+ suffix);			
		}	
}


function Tree_group_edit1(gid,ou,suffix){
		var XHR = new XHRConnection();
		XHR.appendData('Tree_group_edit1',gid);
		XHR.appendData('ou',ou);
		XHR.appendData('description',document.getElementById("description").value);
		XHR.appendData('group_name',document.getElementById("group_name").value);
		XHR.setRefreshArea('rightInfos');
		XHR.sendAndLoad('domains.php', 'GET');	
		ReloadBranch('ou:ou=' + ou + ','+ suffix);		
		}
		
function Tree_group_delete(gid,ou,suffix){
		var XHR = new XHRConnection();
		var input_text=document.getElementById('inputbox delete group').value;
		if(confirm(input_text)){
		XHR.appendData('Tree_group_delete',gid);
		XHR.appendData('ou',ou);
		XHR.setRefreshArea('rightInfos');
		XHR.sendAndLoad('domains.php', 'GET');	
		ReloadBranch('ou:ou=' + ou + ','+ suffix);		
		}		
		}
	


function TreeKavSelect(branch){
	var id=branch.getId();
if (document.getElementById("windows").style.left==''){
	document.getElementById("windows").style.left=xMousePos - 250 + 'px';
	document.getElementById("windows").style.top='100px';
				}
			document.getElementById("windows").style.height='auto';
    		
			document.getElementById("windows").style.width='750px';
			document.getElementById("windows").style.zIndex='3000';
    		document.getElementById("windows").style.visibility="visible";
				
		var XHR = new XHRConnection();
		XHR.setRefreshArea('windows');
		XHR.appendData('TreeKasSelect',id);
		XHR.sendAndLoad('users.kav.php', 'GET');		

}

function TreeKasSelect(branch){
	var id=branch.getId();
if (document.getElementById("windows").style.left==''){
	document.getElementById("windows").style.left=xMousePos - 250 + 'px';
	document.getElementById("windows").style.top='100px';
				}
			document.getElementById("windows").style.height='auto';
    		
			document.getElementById("windows").style.width='750px';
			document.getElementById("windows").style.zIndex='3000';
    		document.getElementById("windows").style.visibility="visible";
				
		var XHR = new XHRConnection();
		XHR.setRefreshArea('windows');
		XHR.appendData('TreeKasSelect',id);
		XHR.sendAndLoad('users.kas.php', 'GET');		

}

function UserAddWhiteList(){
	var XHR = new XHRConnection();
	XHR.appendData('UserAddWhiteList',document.getElementById("white").value);
        document.getElementById('whitelist').innerHTML='<center style="margin:20px;padding:20px"><img src="/img/wait.gif"></center>';
	XHR.setRefreshArea('whitelist');
	XHR.sendAndLoad('users.aswb.php', 'GET');			
}


function UserAddBlackList(){
	var XHR = new XHRConnection();
	XHR.appendData('UserAddBlackList',document.getElementById("black").value);
        document.getElementById('blacklist').innerHTML='<center style="margin:20px;padding:20px"><img src="/img/wait.gif"></center>';
	XHR.setRefreshArea('blacklist');
	XHR.sendAndLoad('users.aswb.php', 'GET');	
	
}
		
function UserDeleteWhiteList(mail,userid){
	var XHR = new XHRConnection();
        document.getElementById('whitelist').innerHTML='<center style="margin:20px;padding:20px"><img src="/img/wait.gif"></center>';
	XHR.appendData('UserDeleteWhiteList',mail);
	XHR.appendData('UserDeleteWhiteUid',userid);
	XHR.setRefreshArea('whitelist');
	XHR.sendAndLoad('users.aswb.php', 'GET');	
	}
function UserDeleteBlackList(mail,userid){
	var XHR = new XHRConnection();
        document.getElementById('blacklist').innerHTML='<center style="margin:20px;padding:20px"><img src="/img/wait.gif"></center>';
	XHR.appendData('UserDeleteBlackList',mail);
	XHR.appendData('UserDeleteBlackListUid',userid);
	XHR.setRefreshArea('blacklist');
	XHR.sendAndLoad('users.aswb.php', 'GET');	
	}	
	
function index_LoadStatus(){
	var XHR = new XHRConnection();
	XHR.setRefreshArea('status');	
	XHR.appendData('SelectBranch',memory_branch);
	XHR.sendAndLoad('tree.functions.php', 'GET');
	
}
   
                

			
			
function WaitingPlease(){
	
var html='<fieldset><legend>Service operation</legend><table><tr><td width=1%><img src="/img/wait.gif"></td><td style="font-size:14px;font-weight:bolder;color:red">Waiting&nbsp;&nbsp;</td></tr></table></fieldset>'	
	return html;	
}	
function SaveFetchForm(){
	ParseForm('ffmFetch','users.account.php',true);
	var vadd='';
	if(document.getElementById("array_num")){
		vadd='&Fetchedit='+document.getElementById("array_num").value;
	}
	document.location.href='users.account.php?tab=1'+vadd;
	
}	
function TreeFetchmailShowServer(server_pool){
	LoadWindows(450);
	var XHR = new XHRConnection();
	XHR.appendData('TreeFetchmailShowServer',server_pool);
	XHR.setRefreshArea('windows');
	XHR.sendAndLoad('domains.php', 'GET');
	
}
function TreeArticaSaveSettings(){
	var XHR = new XHRConnection();
	ParseForm('ffmArtica1','domains.php',true);
	XHR.setRefreshArea('rightInfos');
	XHR.appendData('SelectBranch','Root');
	XHR.sendAndLoad('tree.functions.php', 'GET');	
}

var x_TreeFetchMailApplyConfig= function (obj) {
	var response=obj.responseText;
	if(response){alert(response);}
	
}

function TreeFetchMailApplyConfig(){
	var XHR = new XHRConnection();
	XHR.appendData('TreeFetchMailApplyConfig','yes');
	XHR.sendAndLoad('domains.php', 'GET',x_TreeFetchMailApplyConfig);
	}
function TreePostfixHeaderCheckInfoActions(){
	var XHR = new XHRConnection();
	XHR.appendData('TreePostfixHeaderCheckInfoActions',document.getElementById("fields_action").value);
	XHR.setRefreshArea('TreeRegexFiltersexplain');
	XHR.sendAndLoad('tree.listener.postfix.php', 'GET');	
	}	
function TreePostfixAddHeaderCheckRule(num){
	LoadWindows(650,450,'tree.listener.postfix.php','TreePostfixAddHeaderCheckRule='+num);
		
}
function TreePostfixDeleteHeaderCheckRule(num){
	var XHR = new XHRConnection();	
	XHR.setRefreshArea('rightInfos');
	XHR.appendData('TreePostfixDeleteHeaderCheckRule',num);
	XHR.sendAndLoad('tree.listener.postfix.php', 'GET');	
	}
function TreePostfixHeaderCheckUpdateForm(){
	ParseForm('TreeRegexFilterRuleForm','tree.listener.postfix.php',true)
	var XHR = new XHRConnection();
	XHR.appendData('SelectBranch','settings:postfix:rules');
	XHR.setRefreshArea('rightInfos');
	XHR.sendAndLoad('tree.functions.php', 'GET');	
	}


function LoadTree(params){
	var XHR = new XHRConnection();
	XHR.appendData('SelectBranch',params);
	XHR.setRefreshArea('rightInfos');
	XHR.sendAndLoad('tree.functions.php', 'GET');	
}


function TreeDeleteOrganisation(org){
	var texte=document.getElementById("delete_organisation_text").value;
	var res=confirm(texte);
	if(res){
	var XHR = new XHRConnection();
		XHR.appendData('TreeDeleteOrganisation',org);
		XHR.sendAndLoad('domains.php', 'GET',x_TreeFetchMailApplyConfig);	
		ReloadBranch('server:organisations');	
		LoadTree('server:organisations');
	}
}
function TreeSynchronyzeMailBoxes(){
		var XHR = new XHRConnection();
		XHR.appendData('TreeSynchronyzeMailBoxes','yes');
		XHR.sendAndLoad('domains.php', 'GET',x_TreeFetchMailApplyConfig);	
		LoadTree('applications:cyrus');
		ReloadBranch('applications:cyrus');	
			
}

function LoadUserSectionAjax(num,dn){
	var user_id=document.getElementById('user_id').value;
        var uri='';
        if(!dn){dn='';}
        if(document.getElementById('DnsZoneName')){uri='&zone-name='+ document.getElementById('DnsZoneName').value; }
        LoadAjax('userform','domains.edit.user.php?userid='+ user_id + '&ajaxmode=yes&section='+num+uri+'&dn='+dn)
	}
function LoadUserAliasesAjax(num){
        var user_id=document.getElementById('user_id').value;
        if(!document.getElementById('user_id')){alert('no');}
        LoadAjax('userform','domains.edit.user.php?userid='+ user_id + '&ajaxmode=yes&section=aliases&aliases-section='+num);
}


function TreeUserMailBoxForm(dn){
	LoadWindows(450);
	var XHR = new XHRConnection();
	XHR.setRefreshArea('windows');
	XHR.appendData('TreeUserMailBoxForm',dn);
	XHR.sendAndLoad('domains.php', 'GET');
}
function TreeOuLoadPageFindUser(ou){
	LoadFind(450);
	var XHR = new XHRConnection();
	XHR.setRefreshArea('find');
	XHR.appendData('TreeOuLoadPageFindUser',ou);
	XHR.sendAndLoad('domains.php', 'GET');	
}
function TreeOuFindUser(ou){
	var XHR = new XHRConnection();
	XHR.setRefreshArea('find');
	XHR.appendData('TreeOuLoadPageFindUser',ou);
	XHR.appendData('TreeOuFindUser',document.getElementById("Tofind").value);
	XHR.sendAndLoad('domains.php', 'GET');	
}
function TreePostfixBuildConfiguration(){
	var XHR = new XHRConnection();
	XHR.appendData('TreePostfixBuildConfiguration','yes');
	XHR.sendAndLoad('tree.listener.postfix.php','GET', x_TreeFetchMailApplyConfig);	
	LoadTree('applications:postfix');
}
function TreeAveServerLicenceDeleteKey(licenceFile){
	var XHR = new XHRConnection();
	XHR.appendData('TreeAveServerLicenceDeleteKey',licenceFile);
	XHR.sendAndLoad('tree.listener.postfix.php','GET', x_TreeFetchMailApplyConfig);
	LoadTree('settings:aveserver:licence');
	
}
function artica_ldap_settings(){
	LoadWindows(450);
	var XHR = new XHRConnection();
	XHR.setRefreshArea('windows');
	XHR.appendData('artica_ldap_settings','yes');	
	XHR.sendAndLoad('tree.functions.php', 'GET');
	}
function TreeLoadKas3Tab(num){
var XHR = new XHRConnection();
	XHR.appendData('SelectBranch','settings:kas3:generalSettings');
	XHR.appendData('tab',num);
	XHR.setRefreshArea('rightInfos');
	XHR.sendAndLoad('tree.functions.php', 'GET');		
	}
	

function TreeProcMailRules(){
	LoadWindows(450);
	var XHR = new XHRConnection();
	XHR.setRefreshArea('windows');
	XHR.appendData('TreeProcMailRules','yes');	
	XHR.sendAndLoad('procmail.functions.php', 'GET');	
	
}
function ProcmailAddRule(array_number){
	LoadWindows(450);
	var XHR = new XHRConnection();
	XHR.setRefreshArea('windows');
	XHR.appendData('ProcmailAddRule',array_number);	
	XHR.sendAndLoad('procmail.functions.php', 'GET');	
	
}
function ProcMailRuleMove(num,move,other){
	var XHR = new XHRConnection();
	XHR.setRefreshArea('rules');
	XHR.appendData('ProcMailRuleMove',num);
	XHR.appendData('direction',move);		
	XHR.sendAndLoad('procmail.functions.php', 'GET');		
	}
function ProcMailRuleDelete(num,other){
	var XHR = new XHRConnection();
	XHR.setRefreshArea('rules');
	XHR.appendData('ProcMailRuleDelete',num);
	XHR.sendAndLoad('procmail.functions.php', 'GET');	
	
}
function TreeProcMailApplyConfig(){
var XHR = new XHRConnection();
	XHR.setRefreshArea('rules');
	XHR.appendData('TreeProcMailApplyConfig','yes');
	XHR.sendAndLoad('procmail.functions.php', 'GET',x_TreeFetchMailApplyConfig);		
}
function FindUser(){
    var id='';
    mem_search=0; 
    var ss=document.getElementById("finduser").value;
    if(isXSS(ss)){alert('NO XSS !');return;}
    var findstr=escape(ss);
    YahooSearchUser('850','domains.manage.users.index.php?SearchUserNull='+findstr,findstr);
    FindUserTimeout();
}

function LeftDesign(picture){
	if(!document.getElementById('design-right-picture')){return;}
	document.getElementById('design-right-picture').style.backgroundImage='url(/img/'+picture+')';
	document.getElementById('design-right-picture').style.backgroundRepeat="no-repeat";
	document.getElementById('design-right-picture').style.minHeight="280px";
	document.getElementById('design-right-picture').style.backgroundPosition="85% 100%";

}

function FindUserTimeout(){
	 mem_search=mem_search+1;
	 if(mem_search>20){
		 alert('time-out -> FindUserTimeout(); could not perform request');
		 return;
	 }
	 if(!document.getElementById('SearchUserNull')){
		 setTimeout("FindUserTimeout()",500);
		 return;
	 }
	 mem_search=0;
	 LoadAjax('SearchUserNull','domains.manage.users.index.php?finduser='+document.getElementById("finduser").value);
}

function isXSS(TheString){
	
	var pos=(" "+TheString+" ").indexOf("<");
	if(pos>0){return true;}
	pos=(" "+TheString+" ").indexOf("</scrip");
	if(pos>0){return true;}
	
	pos=(" "+TheString+" ").indexOf("function (");
	if(pos>0){return true;}	
	
	pos=(" "+TheString+" ").indexOf("function(");
	if(pos>0){return true;}		
	
	return false;
	
}

function QuickLinkShow(id){}

	
			
function LeftMenusSwitch(eId, thisImg, state) {
	if (e = document.getElementById(eId)) {
		if (state == null) {
			state = e.style.display == 'none';
			e.style.display = (state ? '' : 'none');
		}
		//...except for this, probably a better way of doing this, but it works at any rate...
		if (state == true){				
			Set_Cookie('ARTICA-MENU_'+eId, '1', '3600', '/', '', '');
			document.getElementById(thisImg).src="/img/fullbullet-down.gif";
                      //  MyHref(document.getElementById(eId+'_link').value);
		}
		if (state == false){
			Delete_Cookie('ARTICA-MENU_'+eId, '/', '');
			document.getElementById(thisImg).src="/img/fullbullet.gif";
		}
	}
}

function LeftMenushide(){
       var re = new RegExp(/ARTICA-MENU_(.+?)_/);
       var m;
       var id;
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
                m=re.exec(c);
                if(m){
                     id=m[1];   
                     if(document.getElementById(id+'_menubullet')){document.getElementById(id+'_menubullet').src="/img/fullbullet-down.gif";}
                     if(document.getElementById(id+'_menubox')){document.getElementById(id+'_menubox').style.display='block';}
                }
		
	}
	return null;
}

function SwitchOrgTabs(num,ou){
     Delete_Cookie('SwitchOrgTabs', '/', '');
     Set_Cookie('SwitchOrgTabs', num, '3600', '/', '', '');
     Set_Cookie('SwitchOrgTabsOu', ou, '3600', '/', '', ''); 
     LoadAjax('org_main','domains.manage.org.index.php?org_section=0&SwitchOrgTabs='+num +'&ou=' +ou);   
}


function DeleteAllCookies(){
   Delete_Cookie('SwitchOrgTabs', '/', '');
   Delete_Cookie('SwitchOrgTabsOu', '/', '');
   Delete_Cookie('ARTICA-POSTFIX-REGEX-PAGE-DIV', '/', '');
   Delete_Cookie('ArticaIsDefaultSelectedGroupId', '/', '');
   Delete_Cookie('ARTICA-POSTFIX-REGEX-PAGE-URI', '/', '');
   Delete_Cookie('ArticaIsDefaultSelectedGroupIdIndex', '/', '');   
   
   
 var re = new RegExp(/ARTICA-MENU_(.+?)_/);
       var m;
       var id;
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
                m=re.exec(c);
                if(m){
                  Delete_Cookie(c, '/', '');
                }
        }
}


function OrgStartPage(){
   var ouselected=document.getElementById('ouselected').value;
   if(ouselected.length>0){Set_Cookie('SwitchOrgTabsOu',ouselected, '3600', '/', '', '');}
   LoadAjax('RightOrgSection','domains.manage.org.index.php?RightOrgSection=yes&ou='+Get_Cookie('SwitchOrgTabsOu'));
   LoadAjax('org_main','domains.manage.org.index.php?org_section=0&SwitchOrgTabs=' + Get_Cookie('SwitchOrgTabs') + '&ou='+Get_Cookie('SwitchOrgTabsOu')+'&mem=yes');        
}
function ChangeOrg(){
        MyHref('domains.manage.org.index.php?ou='+ document.getElementById('ouList').value);
}

function FetchMailParseConfig(){
       var proto=document.getElementById('proto').value;
       if(proto=='hotmail'){
          document.getElementById('poll').value='hotmail';
          document.getElementById('poll').disabled=true;
          document.getElementById('port').value='http';
          document.getElementById('port').disabled=true;
          document.getElementById('timeout').value='0';
          document.getElementById('timeout').disabled=true;
          document.getElementById('interval').disabled=true;
          document.getElementById('tracepolls').disabled=true;
          document.getElementById('ssl').disabled=true;
          document.getElementById('hotmailexplain').innerHTML=document.getElementById('hotmail_text').value;
          return true;
       }
       
       if(proto=='httpp'){
          document.getElementById('poll').value='127.0.0.1';
          document.getElementById('poll').disabled=true;
          document.getElementById('port').value='113';
          document.getElementById('port').disabled=true;
          document.getElementById('ssl').disabled=true;
          document.getElementById('hotmailexplain').innerHTML=document.getElementById('hotwayd_text').value;
          return true;
       }
       
          
          
          document.getElementById('poll').disabled=false;
          document.getElementById('port').value='';
          document.getElementById('port').disabled=false;
          document.getElementById('timeout').value='';
          document.getElementById('timeout').disabled=false;
          document.getElementById('interval').disabled=false;
          document.getElementById('tracepolls').disabled=false;
          document.getElementById('ssl').disabled=false;
          document.getElementById('hotmailexplain').innerHTML='';
        }
        
        
function ExecuteIDScript(id){
        var did=document.getElementById(id);
        var sCodeJavascript = did.getElementsByTagName("script");
        for (var i = 0; i < sCodeJavascript.length; i++){
               var contentScript = sCodeJavascript[i];
               if (contentScript.src && contentScript.src != "") z = 1;
               else{
                       window.eval(contentScript.innerHTML);
                }
        }
}



var x_YahooTreeFolders= function (obj) {
       document.getElementById("dialog1_content").innerHTML=obj.responseText;
       tree = new TafelTree('folderTree', Folerstruct, 'img/', '100%', 'auto');
       tree.generate();   
}

var x_YahooTreeFoldersWhatToRefresh= function (obj) {
     page=CurrentPageName();
     if (page=='domains.edit.group.php'){
         if(document.getElementById("groupid")){
                SharedFolders(document.getElementById("groupid").value);
         }
        
     }
     
     if(page=='samba.index.php'){
        LoadAjax('main_config','samba.index.php?main=shared_folders')
     }
     
}

function YahooSelectedFolders(branch){
        page=CurrentPageName();
        branchid=branch.getId();
        if(branchid!=='/'){
           var text=document.getElementById("YahooSelectedFolders_ask").value;
           text=text+'\n'+branchid;
           if (confirm(text)){
                
                
                
              YAHOO.example.container.dialog1.hide();
              var XHR = new XHRConnection();
              XHR.appendData('AddTreeFolders',branchid);
              if(document.getElementById("groupid")){XHR.appendData('groupid',document.getElementById("groupid").value);}
              if(document.getElementById("YahooSelectedFolders_ask2")){
                        var YahooSelectedFolders_ask2=prompt(document.getElementById("YahooSelectedFolders_ask2").value,ExtractPathName(branchid));
                        XHR.appendData('YahooSelectedFolders_ask2',YahooSelectedFolders_ask2);
                }
              
              XHR.sendAndLoad(page, 'GET',x_YahooTreeFoldersWhatToRefresh); 
           }  
                
        }
}



function YahooTreeClick(branch,status){
     var branch_id=branch.getId();
     page=CurrentPageName();
     if(document.getElementById('TreeRightInfos')){
        LoadAjax('TreeRightInfos',page+'?TreeRightInfos='+branch_id);
     }
        
     return true;   
}


var x_YahooTreeAddSubFolder= function (obj) {
    page=CurrentPageName();
    var branch = tree.getBranchById(mem_branch_id);  
   var item = {
        "id" : mem_item,
        "txt" : ExtractPathName(mem_item),
        'onopenpopulate' : YahooTreeFoldersPopulate,
	'openlink' : 'yahoo.tree.populate.php?p='+page,
	'onclick' : YahooTreeClick,
	'canhavechildren' : true,
	'ondblclick' : YahooSelectedFolders,
        'img':'folder.gif',
        'imgopen':'folderopen.gif', 
	'imgclose':'folder.gif'}
var newBranch = branch.insertIntoLast(item);
}
var x_YahooTreeDelSubFolder= function (obj) {
    page=CurrentPageName();
    tree.removeBranch(mem_branch_id);
    
}

function YahooTreeDelSubFolder(){
  page=CurrentPageName();
      var text=document.getElementById('del_folder_name').value;
      var base=document.getElementById('YahooBranch').value;
      mem_branch_id=base;
      if(confirm(text)){
        var XHR = new XHRConnection();
        mem_item=base;
        XHR.appendData('rmdirp',base);
        XHR.sendAndLoad('yahoo.tree.populate.php', 'GET',x_YahooTreeDelSubFolder);
        }              
        
}

function YahooTreeAddSubFolder(){
      page=CurrentPageName();
      var text=document.getElementById('give_folder_name').value;
      var base=document.getElementById('YahooBranch').value;
      mem_branch_id=base;
      var newfolder=prompt(text,'New folder');
      if(newfolder){
        var XHR = new XHRConnection();
        mem_item=base + '/'+newfolder;
        XHR.appendData('mkdirp',base + '/'+newfolder);
        XHR.sendAndLoad(page, 'GET',x_YahooTreeAddSubFolder);
        }       
}


function YahooTreeFolders(width,page){
        if(!width){width='300';}
        title='Browse...';
        YAHOO.example.container.dialog1.show();
        document.getElementById("dialog1").style.width=width + 'px';
        document.getElementById("dialog1_title").innerHTML=title;
        var XHR = new XHRConnection();
	XHR.appendData('GetTreeFolders','yes');
	XHR.sendAndLoad(page, 'GET',x_YahooTreeFolders);        

}
function YahooTreeFoldersPopulate (branch, response) {
//alert(response);
//alert(branch);

if (response.length>0) {
return response;

}
else {
        return false;
        }

}


function ApplySettings(page){
     mem_page=page;
     maxcount=4;
     count_action=0;
     YahooWin(440,page+'?ApplySettings=-1');
     setTimeout('ApplySettings_run(0)',1500);
    }

var x_ApplySettings_run=function(obj){
      var tempvalue=obj.responseText;
      document.getElementById(memory_branch).innerHTML=tempvalue;
      count_action=count_action+1;
      
      if(count_action<maxcount){
        setTimeout('ApplySettings_run('+count_action+')',1500);
      }
}


function ApplySettings_run(number){
memory_branch='message_'+number;
if( document.getElementById(memory_branch)){
        var XHR = new XHRConnection();
        document.getElementById(memory_branch).innerHTML='<img src="/img/wait.gif">';
	XHR.appendData('ApplySettings',number);
	XHR.sendAndLoad(mem_page, 'GET',x_ApplySettings_run);  
        }
}

function ExtractPathName(path){
        var reg=new RegExp("[\/]+", "g");
         tableau=path.split(reg);
         return tableau[tableau.length-1];
}

function ConfigureYourserver(title){
	if(document.getElementById('MainSlider')){
	  if(IsFunctionExists('GoToIndex')){GoToIndex();return;}
	  LoadAjax('BodyContent','admin.dashboard.proxy.php');	
	  return;
	
	}

	if(!document.getElementById('QuickLinksTop')){QuickLinks();}else{QuickLinksHide();}
}

function SquidStatsInterface(){
	if(!document.getElementById('QuickLinksTop')){SquidQuickLinks();}else{QuickLinksHide();}
	}


function ConfigureYourserver_Cancel(){
        var X;
   if(document.getElementById('ConfigureYourserverStart')){
    if(document.getElementById('ConfigureYourserverStart').checked){
        X=1;
    }else{X=0;}
    var XHR = new XHRConnection();
    XHR.appendData('cancel',X);
    XHR.sendAndLoad('firstwizard.php', 'GET');
   }
}


function GetAllIdElements(pattern){
        var ie = (document.all) ? true : false;
        var elements = (ie) ? document.all : document.getElementsByTagName('*');
        var re = new RegExp(pattern);
        var a=new Array();
        var m;
  for (i=0; i<elements.length; i++){
        if (elements[i].id){
               var m=re.exec(elements[i].id);
               if(m){
                a.push(elements[i].id);
               }
        }
   }
  return(a);
}

function DeleteElementByID(eid){
	if(!document.getElementById(eid)){alert('unable to find ' + eid);return;}
	var who=document.getElementById(eid);
	who.parentNode.removeChild(who);
}

function ShowFileLogs(filename){
     YahooWin3('550','admin.index.php?ShowFileLogs='+filename);  
}

function PostfixPopupEvents(){
        s_PopUp("postfix.events.php?pop=true",450,400);
}

function logoffUser(){
        if(document.getElementById('isanuser')){
          if(document.getElementById('isanuser').value==1){
                MyHref('/logoff.php');
          }
        }


}

function logoff(){
        YahooWin(300,'/logoff.php?menus=yes','Logoff');
        setTimeout("logoffUser()",1000);
   }
   

 
function ShutDownCOmputer(){
        var text=document.getElementById('shutdown_computer_text').value;
        if(confirm(text)){
        	var XHR = new XHRConnection();
        	XHR.appendData('perform','shutdown');
        	XHR.sendAndLoad('/logoff.php', 'GET');       
        }
 }

function IsNumeric(sText){
   var ValidChars = "0123456789.";
   var IsNumber=true;
   var Char;
   if(!sText){
	   if(sText==0){return true;}
	   return false;}
 
   for (i = 0; i < sText.length && IsNumber == true; i++) 
      { 
      Char = sText.charAt(i); 
      if (ValidChars.indexOf(Char) == -1) 
         {
         IsNumber = false;
         }
      }
   return IsNumber;
   
}

 
 function trim(str, chars) {
    return ltrim(rtrim(str, chars), chars);
}

function ltrim(str, chars) {
    chars = chars || "\\s";
    return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
}

function rtrim(str, chars) {
    chars = chars || "\\s";
    return str.replace(new RegExp("[" + chars + "]+$", "g"), "");
}

function loadssfile(filename){
 
  var fileref=document.createElement("link")
  fileref.setAttribute("rel", "stylesheet")
  fileref.setAttribute("type", "text/css")
  fileref.setAttribute("href", filename)
 
 if (typeof fileref!="undefined")
  document.getElementsByTagName("head")[0].appendChild(fileref)
}


function SwitchPassword(md,field){
var fontsize=document.getElementById(field).style.fontSize;
var stylesize=document.getElementById(field).style.width;
var type=document.getElementById(field).type;
var value=document.getElementById(field).value;
var padding=document.getElementById(field).style.padding;
if(type=='password'){
     document.getElementById(md).innerHTML="<input type='text' id='"+field+"' name='"+field+"' value='"+value+"'>";

}else{
    document.getElementById(md).innerHTML="<input type='password' id='"+field+"' name='"+field+"' value='"+value+"'>";
}
document.getElementById(field).style.fontSize=fontsize;
document.getElementById(field).style.width=stylesize;
document.getElementById(field).style.padding=padding;



}

function ReloadjQuery(){
	Loadjs('CacheOff.php?jquery=yes');
	
}

function TestsJQuery(){
	

	if(typeof jQuery=='undefined') {
	    var headTag = document.getElementsByTagName("head")[0];
	    var jqTag = document.createElement('script');
	    jqTag.type = 'text/javascript';
	    jqTag.src = '/js/jquery-ui-1.8.22.custom.min.js';
	    headTag.appendChild(jqTag);
	}	
	
}
function LoadjsFinal(){
	
	
}


function LoadjsSilent(src){
	$.getScript( src );
}

function Loadjs(src,lock){
	
	if(lock==true){
		LockPage();
		$.getScript( src )
		.done(function( script, textStatus ) {
		
		})
		.fail(function( jqxhr, settings, exception ) {
			 if(src=='Inotify.php'){return;}

			if(! exception){
    				var t=setTimeout(function(){ $.getScript(src,true); },800);
				UnlockPage();
				return;
			};

			 if(exception=='Service Not Available'){
				 UnlockPage();
				 var t=setTimeout(function(){ $.getScript(src,true); },800);
				
				 return;
			 }
			 
			 if(exception=='Parent proxy unreacheable'){
				 UnlockPage();
				 var t=setTimeout(function(){ $.getScript(src,true); },800);
				 return;
			 }

			if(exception=='Internal Server Error'){
				 UnlockPage();
				 var t=setTimeout(function(){ $.getScript(src,true); },1500);
				return;
			}
			 
			 if(exception.length==0){
				 UnlockPage();
				 var t=setTimeout(function(){ $.getScript(src,true); },800);
				 return; 
			 }


			if(exception==""){
			  UnlockPage();
			  var t=setTimeout(function(){ $.getScript(src,true); },1000);
			  return; 
			 }



			if(exception.IndexOf("is null")>0){
			 	UnlockPage();
			 	return; 
			}




			 
			 alert('Loadjs()version 1.2\nCannot load javascript: '+src+'\nError ( length:<'+exception.length+'>)\nException:"'+exception+'"');
			 UnlockPage();
		});
		 UnlockPage();
		return;
		
		
	}
	
	$.getScript( src )
	.done(function( script, textStatus ) {
	
	})
	.fail(function( jqxhr, settings, exception ) {
		if(src=='Inotify.php'){return;}

		if(!exception){
		  var t=setTimeout(function(){ $.getScript(src,true); },800);
		  UnlockPage();
		 return; 

		}

		 if(exception=='Service Not Available'){
			 var t=setTimeout(function(){ $.getScript(src,false); },800);
			 return;}
		 
		 if(exception=='Parent proxy unreacheable'){
			 var t=setTimeout(function(){ $.getScript(src,true); },800);
			 UnlockPage();
			 return;
		 }	
		 
		 if(exception.lenth==0){
			 var t=setTimeout(function(){ $.getScript(src,true); },800);
			 UnlockPage();
			 return; 
		 }
		 
		 alert('Loadjs: [LINE: 3637] Cannot load javascript: '+src+'\nError:<'+exception+'>\nException lenght:<'+exception.lenth+'>');
	});
	 
	return;
	
	
	
	return;
	
	
	var t=setTimeout(function(){ $.getScript(src); },500);
	//$.ajax({ type: "GET", url: src, dataType: "script",error: function(){alert(src+':error');}});
	//$.ajax({ type: "GET", url: src, dataType: "script",error: function(){return;}});
//http://forum.jquery.com/topic/destroy-old-loaded-script
}



function applysettings_dansguardian(){
      Loadjs('/dansguardian.index.php?CompilePolicies=yes');  
        
}

function WizardFindMyNetworksMask(){
	YahooWin5('400','/index.gateway.php?popup-network-masks=yes');
	
}

function RefreshMainFilterTable(){
	if(!document.getElementById('WebFilteringMainTableID')){return;}
	var tableid=document.getElementById('WebFilteringMainTableID').value;
	if(!document.getElementById(tableid)){return;}
	$('#'+tableid).flexReload();
	if( document.getElementById('MAIN_TABLE_UFDB_GROUPS_ALL') ){ $('#'+document.getElementById('MAIN_TABLE_UFDB_GROUPS_ALL').value).flexReload(); }
	
	
	
	
}

function CheckBoxDesignRebuild(){
$('p[checkboxfunc]').each(function(i,e) {
    var func=$(this).attr( 'checkboxfunc' ); 
    setTimeout(func,0);
});
}


function ParagrapheWhiteToYellow(id,switch_color){
	if(switch_color==0){
		document.getElementById(id+'_0').className='RLightyellowfg';
		document.getElementById(id+'_1').className='RLightyellow';
		document.getElementById(id+'_2').className='RLightyellow1';
		document.getElementById(id+'_3').className='RLightyellow2';
		document.getElementById(id+'_4').className='RLightyellow3';
		document.getElementById(id+'_5').className='RLightyellow4';		
		document.getElementById(id+'_6').className='RLightyellow5';
		document.getElementById(id+'_7').className='RLightyellow';
		document.getElementById(id+'_8').className='RLightyellow5';
		document.getElementById(id+'_9').className='RLightyellow4';
		document.getElementById(id+'_10').className='RLightyellow3';
		document.getElementById(id+'_11').className='RLightyellow2';
		document.getElementById(id+'_12').className='RLightyellow1';
		lightup(document.getElementById(id+'_img'), 100);
		
	
		
	}
	if(switch_color==1){
		document.getElementById(id+'_0').className='RLightWhitefg';
		document.getElementById(id+'_1').className='RLightWhite';
		document.getElementById(id+'_2').className='RLightWhite1';
		document.getElementById(id+'_3').className='RLightWhite2';
		document.getElementById(id+'_4').className='RLightWhite3';
		document.getElementById(id+'_5').className='RLightWhite4';		
		document.getElementById(id+'_6').className='RLightWhite5';
		document.getElementById(id+'_7').className='RLightWhite';
		document.getElementById(id+'_8').className='RLightWhite5';
		document.getElementById(id+'_9').className='RLightWhite4';
		document.getElementById(id+'_10').className='RLightWhite3';
		document.getElementById(id+'_11').className='RLightWhite2';
		document.getElementById(id+'_12').className='RLightWhite1';	
		lightup(document.getElementById(id+'_img'), 50);
		
	}
	
}

compteur_global_demarre();
setTimeout("compteur_global_demarre()",1000);

function LoadMasterTabs(){
	LoadAjax('BodyContentTabs','admin.tabs.php',true);	
	
}

function base64_decode (data) {
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        dec = "",
        tmp_arr = [];

    if (!data) {
        return data;
    }

    data += '';

    do { // unpack four hexets into three octets using index points in b64
        h1 = b64.indexOf(data.charAt(i++));
        h2 = b64.indexOf(data.charAt(i++));
        h3 = b64.indexOf(data.charAt(i++));
        h4 = b64.indexOf(data.charAt(i++));

        bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

        o1 = bits >> 16 & 0xff;
        o2 = bits >> 8 & 0xff;
        o3 = bits & 0xff;

        if (h3 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1);
        } else if (h4 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1, o2);
        } else {
            tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
        }
    } while (i < data.length);

    dec = tmp_arr.join('');
    dec = this.utf8_decode(dec);

    return dec;
}

function utf8_decode (str_data) {

    var tmp_arr = [],
        i = 0,
        ac = 0,
        c1 = 0,
        c2 = 0,
        c3 = 0;

    str_data += '';

    while (i < str_data.length) {
        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if (c1 > 191 && c1 < 224) {
            c2 = str_data.charCodeAt(i + 1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i + 1);
            c3 = str_data.charCodeAt(i + 2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }

    return tmp_arr.join('');
}



function base64_encode(text) {
	  var dwOctets = 0;
	  var nbChars = 0;
	  var ret = "";
	  var b;

	  for (i = 0; i < 3 * ((text.length + 2) / 3); i++) {
	    if (i < text.length) b = text.charCodeAt(i);
	    else b = 0;
	    dwOctets <<= 8;
	    dwOctets += b;
	    if (++nbChars == 3) {
	      for (j = 0; j < 4; j++) {
	        b = (dwOctets & 0x00FC0000) >> 18;
	        if (b < 26) ret += String.fromCharCode(b + 65);
	        else if (b < 52) ret += String.fromCharCode(b + 71);
	        else if (b < 62) ret += String.fromCharCode(b - 4);
	        else if (b == 62) ret += "+";
	        else if (b == 63) ret += "/";
	        dwOctets <<= 6;
	      }
	      dwOctets = 0;
	      nbChars = 0;
	    }
	  }

	  ret += "=";

	  return ret;
	}

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];
			
			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}


function CornPictures(){
	$(document).ready(function() {

		$('img.rounded').one('load',function () {
			var img = $(this);
			var img_width = img.width();
			var img_height = img.height();
			
			// build wrapper
			var wrapper = $('<div class="rounded_wrapper"></div>');
			wrapper.width(img_width);
			wrapper.height(img_height);
			
			// move CSS properties from img to wrapper
			wrapper.css('float', img.css('float'));
			img.css('float', 'none')
			
			wrapper.css('margin-right', img.css('margin-right'));
			img.css('margin-right', '0')

			wrapper.css('margin-left', img.css('margin-left'));
			img.css('margin-left', '0')

			wrapper.css('margin-bottom', img.css('margin-bottom'));
			img.css('margin-bottom', '0')

			wrapper.css('margin-top', img.css('margin-top'));
			img.css('margin-top', '0')

			wrapper.css('display', 'block');
			img.css('display', 'block')

			// IE6 fix (when image height or width is odd)
			if ($.browser.msie && $.browser.version == '6.0')
			{
				if(img_width % 2 != 0)
				{
					wrapper.addClass('ie6_width')
				}
				if(img_height % 2 != 0)
				{
					wrapper.addClass('ie6_height')			
				}
			}

			// wrap image
			img.wrap(wrapper);
			
			// add rounded corners
			img.after('<div class="tl"></div>');
			img.after('<div class="tr"></div>');
			img.after('<div class="bl"></div>');
			img.after('<div class="br"></div>');
		}).each(function(){
			if(this.complete) $(this).trigger("load");
		});
			
		});	
	
}

function is_integer(value){
	  if((parseFloat(value) == parseInt(value)) && !isNaN(value)){
	      return true;
	  } else {
	      return false;
	  }
	}

function IndexHorloge() {
	
	if(!document.getElementById('time-clock-front')){return;}
	if( IfWindowsOpen() ){return;}
	LoadAjaxVerySilent('time-clock-front','admin.index.php?CurrentTime=yes');
}


function logon_stringToHex (s) {
  var r = "0x";
  var hexes = new Array ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
  for (var i=0; i<s.length; i++) {r += hexes [s.charCodeAt(i) >> 4] + hexes [s.charCodeAt(i) & 0xf];}
  return r;
}
function login_des (key, message, encrypt, mode, iv, padding) {
  //declaring this locally speeds things up a bit
  var spfunction1 = new Array (0x1010400,0,0x10000,0x1010404,0x1010004,0x10404,0x4,0x10000,0x400,0x1010400,0x1010404,0x400,0x1000404,0x1010004,0x1000000,0x4,0x404,0x1000400,0x1000400,0x10400,0x10400,0x1010000,0x1010000,0x1000404,0x10004,0x1000004,0x1000004,0x10004,0,0x404,0x10404,0x1000000,0x10000,0x1010404,0x4,0x1010000,0x1010400,0x1000000,0x1000000,0x400,0x1010004,0x10000,0x10400,0x1000004,0x400,0x4,0x1000404,0x10404,0x1010404,0x10004,0x1010000,0x1000404,0x1000004,0x404,0x10404,0x1010400,0x404,0x1000400,0x1000400,0,0x10004,0x10400,0,0x1010004);
  var spfunction2 = new Array (-0x7fef7fe0,-0x7fff8000,0x8000,0x108020,0x100000,0x20,-0x7fefffe0,-0x7fff7fe0,-0x7fffffe0,-0x7fef7fe0,-0x7fef8000,-0x80000000,-0x7fff8000,0x100000,0x20,-0x7fefffe0,0x108000,0x100020,-0x7fff7fe0,0,-0x80000000,0x8000,0x108020,-0x7ff00000,0x100020,-0x7fffffe0,0,0x108000,0x8020,-0x7fef8000,-0x7ff00000,0x8020,0,0x108020,-0x7fefffe0,0x100000,-0x7fff7fe0,-0x7ff00000,-0x7fef8000,0x8000,-0x7ff00000,-0x7fff8000,0x20,-0x7fef7fe0,0x108020,0x20,0x8000,-0x80000000,0x8020,-0x7fef8000,0x100000,-0x7fffffe0,0x100020,-0x7fff7fe0,-0x7fffffe0,0x100020,0x108000,0,-0x7fff8000,0x8020,-0x80000000,-0x7fefffe0,-0x7fef7fe0,0x108000);
  var spfunction3 = new Array (0x208,0x8020200,0,0x8020008,0x8000200,0,0x20208,0x8000200,0x20008,0x8000008,0x8000008,0x20000,0x8020208,0x20008,0x8020000,0x208,0x8000000,0x8,0x8020200,0x200,0x20200,0x8020000,0x8020008,0x20208,0x8000208,0x20200,0x20000,0x8000208,0x8,0x8020208,0x200,0x8000000,0x8020200,0x8000000,0x20008,0x208,0x20000,0x8020200,0x8000200,0,0x200,0x20008,0x8020208,0x8000200,0x8000008,0x200,0,0x8020008,0x8000208,0x20000,0x8000000,0x8020208,0x8,0x20208,0x20200,0x8000008,0x8020000,0x8000208,0x208,0x8020000,0x20208,0x8,0x8020008,0x20200);
  var spfunction4 = new Array (0x802001,0x2081,0x2081,0x80,0x802080,0x800081,0x800001,0x2001,0,0x802000,0x802000,0x802081,0x81,0,0x800080,0x800001,0x1,0x2000,0x800000,0x802001,0x80,0x800000,0x2001,0x2080,0x800081,0x1,0x2080,0x800080,0x2000,0x802080,0x802081,0x81,0x800080,0x800001,0x802000,0x802081,0x81,0,0,0x802000,0x2080,0x800080,0x800081,0x1,0x802001,0x2081,0x2081,0x80,0x802081,0x81,0x1,0x2000,0x800001,0x2001,0x802080,0x800081,0x2001,0x2080,0x800000,0x802001,0x80,0x800000,0x2000,0x802080);
  var spfunction5 = new Array (0x100,0x2080100,0x2080000,0x42000100,0x80000,0x100,0x40000000,0x2080000,0x40080100,0x80000,0x2000100,0x40080100,0x42000100,0x42080000,0x80100,0x40000000,0x2000000,0x40080000,0x40080000,0,0x40000100,0x42080100,0x42080100,0x2000100,0x42080000,0x40000100,0,0x42000000,0x2080100,0x2000000,0x42000000,0x80100,0x80000,0x42000100,0x100,0x2000000,0x40000000,0x2080000,0x42000100,0x40080100,0x2000100,0x40000000,0x42080000,0x2080100,0x40080100,0x100,0x2000000,0x42080000,0x42080100,0x80100,0x42000000,0x42080100,0x2080000,0,0x40080000,0x42000000,0x80100,0x2000100,0x40000100,0x80000,0,0x40080000,0x2080100,0x40000100);
  var spfunction6 = new Array (0x20000010,0x20400000,0x4000,0x20404010,0x20400000,0x10,0x20404010,0x400000,0x20004000,0x404010,0x400000,0x20000010,0x400010,0x20004000,0x20000000,0x4010,0,0x400010,0x20004010,0x4000,0x404000,0x20004010,0x10,0x20400010,0x20400010,0,0x404010,0x20404000,0x4010,0x404000,0x20404000,0x20000000,0x20004000,0x10,0x20400010,0x404000,0x20404010,0x400000,0x4010,0x20000010,0x400000,0x20004000,0x20000000,0x4010,0x20000010,0x20404010,0x404000,0x20400000,0x404010,0x20404000,0,0x20400010,0x10,0x4000,0x20400000,0x404010,0x4000,0x400010,0x20004010,0,0x20404000,0x20000000,0x400010,0x20004010);
  var spfunction7 = new Array (0x200000,0x4200002,0x4000802,0,0x800,0x4000802,0x200802,0x4200800,0x4200802,0x200000,0,0x4000002,0x2,0x4000000,0x4200002,0x802,0x4000800,0x200802,0x200002,0x4000800,0x4000002,0x4200000,0x4200800,0x200002,0x4200000,0x800,0x802,0x4200802,0x200800,0x2,0x4000000,0x200800,0x4000000,0x200800,0x200000,0x4000802,0x4000802,0x4200002,0x4200002,0x2,0x200002,0x4000000,0x4000800,0x200000,0x4200800,0x802,0x200802,0x4200800,0x802,0x4000002,0x4200802,0x4200000,0x200800,0,0x2,0x4200802,0,0x200802,0x4200000,0x800,0x4000002,0x4000800,0x800,0x200002);
  var spfunction8 = new Array (0x10001040,0x1000,0x40000,0x10041040,0x10000000,0x10001040,0x40,0x10000000,0x40040,0x10040000,0x10041040,0x41000,0x10041000,0x41040,0x1000,0x40,0x10040000,0x10000040,0x10001000,0x1040,0x41000,0x40040,0x10040040,0x10041000,0x1040,0,0,0x10040040,0x10000040,0x10001000,0x41040,0x40000,0x41040,0x40000,0x10041000,0x1000,0x40,0x10040040,0x1000,0x41040,0x10001000,0x40,0x10000040,0x10040000,0x10040040,0x10000000,0x40000,0x10001040,0,0x10041040,0x40040,0x10000040,0x10040000,0x10001000,0x10001040,0,0x10041040,0x41000,0x41000,0x1040,0x1040,0x40040,0x10000000,0x10041000);

  //create the 16 or 48 subkeys we will need
  var keys = des_createKeys (key);
  var m=0, i, j, temp, temp2, right1, right2, left, right, looping;
  var cbcleft, cbcleft2, cbcright, cbcright2
  var endloop, loopinc;
  var len = message.length;
  var chunk = 0;
  //set up the loops for single and triple des
  var iterations = keys.length == 32 ? 3 : 9; //single or triple des
  if (iterations == 3) {looping = encrypt ? new Array (0, 32, 2) : new Array (30, -2, -2);}
  else {looping = encrypt ? new Array (0, 32, 2, 62, 30, -2, 64, 96, 2) : new Array (94, 62, -2, 32, 64, 2, 30, -2, -2);}

  //pad the message depending on the padding parameter
  if (padding == 2) message += "        "; //pad the message with spaces
  else if (padding == 1) {temp = 8-(len%8); message += String.fromCharCode (temp,temp,temp,temp,temp,temp,temp,temp); if (temp==8) len+=8;} //PKCS7 padding
  else if (!padding) message += "\0\0\0\0\0\0\0\0"; //pad the message out with null bytes

  //store the result here
  result = "";
  tempresult = "";

  if (mode == 1) { //CBC mode
    cbcleft = (iv.charCodeAt(m++) << 24) | (iv.charCodeAt(m++) << 16) | (iv.charCodeAt(m++) << 8) | iv.charCodeAt(m++);
    cbcright = (iv.charCodeAt(m++) << 24) | (iv.charCodeAt(m++) << 16) | (iv.charCodeAt(m++) << 8) | iv.charCodeAt(m++);
    m=0;
  }

  //loop through each 64 bit chunk of the message
  while (m < len) {
    left = (message.charCodeAt(m++) << 24) | (message.charCodeAt(m++) << 16) | (message.charCodeAt(m++) << 8) | message.charCodeAt(m++);
    right = (message.charCodeAt(m++) << 24) | (message.charCodeAt(m++) << 16) | (message.charCodeAt(m++) << 8) | message.charCodeAt(m++);

    //for Cipher Block Chaining mode, xor the message with the previous result
    if (mode == 1) {if (encrypt) {left ^= cbcleft; right ^= cbcright;} else {cbcleft2 = cbcleft; cbcright2 = cbcright; cbcleft = left; cbcright = right;}}

    //first each 64 but chunk of the message must be permuted according to IP
    temp = ((left >>> 4) ^ right) & 0x0f0f0f0f; right ^= temp; left ^= (temp << 4);
    temp = ((left >>> 16) ^ right) & 0x0000ffff; right ^= temp; left ^= (temp << 16);
    temp = ((right >>> 2) ^ left) & 0x33333333; left ^= temp; right ^= (temp << 2);
    temp = ((right >>> 8) ^ left) & 0x00ff00ff; left ^= temp; right ^= (temp << 8);
    temp = ((left >>> 1) ^ right) & 0x55555555; right ^= temp; left ^= (temp << 1);

    left = ((left << 1) | (left >>> 31)); 
    right = ((right << 1) | (right >>> 31)); 

    //do this either 1 or 3 times for each chunk of the message
    for (j=0; j<iterations; j+=3) {
      endloop = looping[j+1];
      loopinc = looping[j+2];
      //now go through and perform the encryption or decryption  
      for (i=looping[j]; i!=endloop; i+=loopinc) { //for efficiency
        right1 = right ^ keys[i]; 
        right2 = ((right >>> 4) | (right << 28)) ^ keys[i+1];
        //the result is attained by passing these bytes through the S selection functions
        temp = left;
        left = right;
        right = temp ^ (spfunction2[(right1 >>> 24) & 0x3f] | spfunction4[(right1 >>> 16) & 0x3f]
              | spfunction6[(right1 >>>  8) & 0x3f] | spfunction8[right1 & 0x3f]
              | spfunction1[(right2 >>> 24) & 0x3f] | spfunction3[(right2 >>> 16) & 0x3f]
              | spfunction5[(right2 >>>  8) & 0x3f] | spfunction7[right2 & 0x3f]);
      }
      temp = left; left = right; right = temp; //unreverse left and right
    } //for either 1 or 3 iterations

    //move then each one bit to the right
    left = ((left >>> 1) | (left << 31)); 
    right = ((right >>> 1) | (right << 31)); 

    //now perform IP-1, which is IP in the opposite direction
    temp = ((left >>> 1) ^ right) & 0x55555555; right ^= temp; left ^= (temp << 1);
    temp = ((right >>> 8) ^ left) & 0x00ff00ff; left ^= temp; right ^= (temp << 8);
    temp = ((right >>> 2) ^ left) & 0x33333333; left ^= temp; right ^= (temp << 2);
    temp = ((left >>> 16) ^ right) & 0x0000ffff; right ^= temp; left ^= (temp << 16);
    temp = ((left >>> 4) ^ right) & 0x0f0f0f0f; right ^= temp; left ^= (temp << 4);

    //for Cipher Block Chaining mode, xor the message with the previous result
    if (mode == 1) {if (encrypt) {cbcleft = left; cbcright = right;} else {left ^= cbcleft2; right ^= cbcright2;}}
    tempresult += String.fromCharCode ((left>>>24), ((left>>>16) & 0xff), ((left>>>8) & 0xff), (left & 0xff), (right>>>24), ((right>>>16) & 0xff), ((right>>>8) & 0xff), (right & 0xff));

    chunk += 8;
    if (chunk == 512) {result += tempresult; tempresult = ""; chunk = 0;}
  } //for every 8 characters, or 64 bits in the message

  //return the result as an array
  return result + tempresult;
} //end of des



//des_createKeys
//this takes as input a 64 bit key (even though only 56 bits are used)
//as an array of 2 integers, and returns 16 48 bit keys
function des_createKeys (key) {
  //declaring this locally speeds things up a bit
  pc2bytes0  = new Array (0,0x4,0x20000000,0x20000004,0x10000,0x10004,0x20010000,0x20010004,0x200,0x204,0x20000200,0x20000204,0x10200,0x10204,0x20010200,0x20010204);
  pc2bytes1  = new Array (0,0x1,0x100000,0x100001,0x4000000,0x4000001,0x4100000,0x4100001,0x100,0x101,0x100100,0x100101,0x4000100,0x4000101,0x4100100,0x4100101);
  pc2bytes2  = new Array (0,0x8,0x800,0x808,0x1000000,0x1000008,0x1000800,0x1000808,0,0x8,0x800,0x808,0x1000000,0x1000008,0x1000800,0x1000808);
  pc2bytes3  = new Array (0,0x200000,0x8000000,0x8200000,0x2000,0x202000,0x8002000,0x8202000,0x20000,0x220000,0x8020000,0x8220000,0x22000,0x222000,0x8022000,0x8222000);
  pc2bytes4  = new Array (0,0x40000,0x10,0x40010,0,0x40000,0x10,0x40010,0x1000,0x41000,0x1010,0x41010,0x1000,0x41000,0x1010,0x41010);
  pc2bytes5  = new Array (0,0x400,0x20,0x420,0,0x400,0x20,0x420,0x2000000,0x2000400,0x2000020,0x2000420,0x2000000,0x2000400,0x2000020,0x2000420);
  pc2bytes6  = new Array (0,0x10000000,0x80000,0x10080000,0x2,0x10000002,0x80002,0x10080002,0,0x10000000,0x80000,0x10080000,0x2,0x10000002,0x80002,0x10080002);
  pc2bytes7  = new Array (0,0x10000,0x800,0x10800,0x20000000,0x20010000,0x20000800,0x20010800,0x20000,0x30000,0x20800,0x30800,0x20020000,0x20030000,0x20020800,0x20030800);
  pc2bytes8  = new Array (0,0x40000,0,0x40000,0x2,0x40002,0x2,0x40002,0x2000000,0x2040000,0x2000000,0x2040000,0x2000002,0x2040002,0x2000002,0x2040002);
  pc2bytes9  = new Array (0,0x10000000,0x8,0x10000008,0,0x10000000,0x8,0x10000008,0x400,0x10000400,0x408,0x10000408,0x400,0x10000400,0x408,0x10000408);
  pc2bytes10 = new Array (0,0x20,0,0x20,0x100000,0x100020,0x100000,0x100020,0x2000,0x2020,0x2000,0x2020,0x102000,0x102020,0x102000,0x102020);
  pc2bytes11 = new Array (0,0x1000000,0x200,0x1000200,0x200000,0x1200000,0x200200,0x1200200,0x4000000,0x5000000,0x4000200,0x5000200,0x4200000,0x5200000,0x4200200,0x5200200);
  pc2bytes12 = new Array (0,0x1000,0x8000000,0x8001000,0x80000,0x81000,0x8080000,0x8081000,0x10,0x1010,0x8000010,0x8001010,0x80010,0x81010,0x8080010,0x8081010);
  pc2bytes13 = new Array (0,0x4,0x100,0x104,0,0x4,0x100,0x104,0x1,0x5,0x101,0x105,0x1,0x5,0x101,0x105);

  //how many iterations (1 for des, 3 for triple des)
  var iterations = key.length > 8 ? 3 : 1; //changed by Paul 16/6/2007 to use Triple DES for 9+ byte keys
  //stores the return keys
  var keys = new Array (32 * iterations);
  //now define the left shifts which need to be done
  var shifts = new Array (0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0);
  //other variables
  var lefttemp, righttemp, m=0, n=0, temp;

  for (var j=0; j<iterations; j++) { //either 1 or 3 iterations
    left = (key.charCodeAt(m++) << 24) | (key.charCodeAt(m++) << 16) | (key.charCodeAt(m++) << 8) | key.charCodeAt(m++);
    right = (key.charCodeAt(m++) << 24) | (key.charCodeAt(m++) << 16) | (key.charCodeAt(m++) << 8) | key.charCodeAt(m++);

    temp = ((left >>> 4) ^ right) & 0x0f0f0f0f; right ^= temp; left ^= (temp << 4);
    temp = ((right >>> -16) ^ left) & 0x0000ffff; left ^= temp; right ^= (temp << -16);
    temp = ((left >>> 2) ^ right) & 0x33333333; right ^= temp; left ^= (temp << 2);
    temp = ((right >>> -16) ^ left) & 0x0000ffff; left ^= temp; right ^= (temp << -16);
    temp = ((left >>> 1) ^ right) & 0x55555555; right ^= temp; left ^= (temp << 1);
    temp = ((right >>> 8) ^ left) & 0x00ff00ff; left ^= temp; right ^= (temp << 8);
    temp = ((left >>> 1) ^ right) & 0x55555555; right ^= temp; left ^= (temp << 1);

    //the right side needs to be shifted and to get the last four bits of the left side
    temp = (left << 8) | ((right >>> 20) & 0x000000f0);
    //left needs to be put upside down
    left = (right << 24) | ((right << 8) & 0xff0000) | ((right >>> 8) & 0xff00) | ((right >>> 24) & 0xf0);
    right = temp;

    //now go through and perform these shifts on the left and right keys
    for (var i=0; i < shifts.length; i++) {
      //shift the keys either one or two bits to the left
      if (shifts[i]) {left = (left << 2) | (left >>> 26); right = (right << 2) | (right >>> 26);}
      else {left = (left << 1) | (left >>> 27); right = (right << 1) | (right >>> 27);}
      left &= -0xf; right &= -0xf;

      //now apply PC-2, in such a way that E is easier when encrypting or decrypting
      //this conversion will look like PC-2 except only the last 6 bits of each byte are used
      //rather than 48 consecutive bits and the order of lines will be according to 
      //how the S selection functions will be applied: S2, S4, S6, S8, S1, S3, S5, S7
      lefttemp = pc2bytes0[left >>> 28] | pc2bytes1[(left >>> 24) & 0xf]
              | pc2bytes2[(left >>> 20) & 0xf] | pc2bytes3[(left >>> 16) & 0xf]
              | pc2bytes4[(left >>> 12) & 0xf] | pc2bytes5[(left >>> 8) & 0xf]
              | pc2bytes6[(left >>> 4) & 0xf];
      righttemp = pc2bytes7[right >>> 28] | pc2bytes8[(right >>> 24) & 0xf]
                | pc2bytes9[(right >>> 20) & 0xf] | pc2bytes10[(right >>> 16) & 0xf]
                | pc2bytes11[(right >>> 12) & 0xf] | pc2bytes12[(right >>> 8) & 0xf]
                | pc2bytes13[(right >>> 4) & 0xf];
      temp = ((righttemp >>> 16) ^ lefttemp) & 0x0000ffff; 
      keys[n++] = lefttemp ^ temp; keys[n++] = righttemp ^ (temp << 16);
    }
  } //for each iterations
  //return the keys we've created
  return keys;
} //end of des_createKeys


function login_hexToString (h) {
  var r = "";
  for (var i= (h.substr(0, 2)=="0x")?2:0; i<h.length; i+=2) {r += String.fromCharCode (parseInt (h.substr (i, 2), 16));}
  return r;
}

function login_crypt(xstring){
var key = "artica2014";
var ciphertext = login_des(key, xstring, 1, 0);
return logon_stringToHex(ciphertext);

}

