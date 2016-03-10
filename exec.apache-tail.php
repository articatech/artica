<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(!Build_pid_func(__FILE__,"MAIN")){
	events("Already executed.. aborting the process");
	die();
}
$pid=getmypid();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
@mkdir("/home/apache/artica-stats",0666,true);
events("running $pid ");
file_put_contents($pidfile,$pid);
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	Parseline(trim($buffer));
	$buffer=null;
}

fclose($pipe);
DumpMemory();
events("Shutdown...");
die();



function Parseline($buffer){
	if($buffer==null){return;}
	$FOUND=false;
	
	
	if(preg_match('#ngx\[(.*?)\]\s+(.+?)\s+(.+?)\s+(.*?)\s+\[(.+?)\]\s+([A-Z]+)\s+(.+?)\s+[A-Z]+\/[0-9\.]+\s+"([0-9]+)"\s+([0-9]+)#', $buffer,$re)){
		$FOUND=true;
		$hostname=$re[1];
		$IPADDR=$re[2];
		$X_FORWARDED=$re[3];
		$FREE_1=$re[4];
		$TIME=strtotime($re[5]);
		$PROTO=$re[6];
		$FILEPATH=$re[7];
		$HTTP_CODE=$re[8];
		$SIZE=$re[9];
		
	}
	
	
	if(!$FOUND){
		if(preg_match('#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+"([A-Z]+)\s+(.+?)\s+([A-Z]+)\/([0-9\.]+)"\s+([0-9]+)\s+(.+?)\s+"(.+?)"\s+"(.+?)"\s+(.+)#', $buffer,$re)){
			$FOUND=true;
			$IPADDR=$re[1];
			$X_FORWARDED=$re[2];
			$FREE_1=$re[3];
			$FREE_2=$re[4];
			$TIME=strtotime($re[5]);
			$PROTO=$re[6];
			$FILEPATH=$re[7];
			$HTTP_CODE=$re[10];
			$SIZE=$re[11];
			$FREE_3=$re[12];
			$USERAGENT=$re[13];
			$hostname=$re[14];
		
		}
	}
	
	
	
	
	
	if(!$FOUND){
		events("not parse [$buffer]");
		return;
		
	}
	
	if($SIZE=="-"){$SIZE=0;}
	if($X_FORWARDED=="-"){$X_FORWARDED=null;}
	if($X_FORWARDED<>null){$IPADDR=$X_FORWARDED;}
	
	SendToMemory($hostname,$TIME,$IPADDR,$HTTP_CODE,$SIZE);
	
}



function SendToMemory($hostname,$TIME,$IPADDR,$HTTP_CODE,$SIZE){
	
	if(!isset($GLOBALS["MEMORY"]["TIME"])){$GLOBALS["MEMORY"]["TIME"]=time();}
	
	$TIMEX=date("YmdHi");
	
	$KEY=md5("$hostname$IPADDR$HTTP_CODE$SIZE$TIMEX");
	
	
	if(!isset($GLOBALS["MEMORY"]["ACCESSES"][$KEY]["SIZE"])){
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["SIZE"]=$SIZE;
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["IPADDR"]=$IPADDR;
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["CODE"]=$HTTP_CODE;
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["TIME"]=$TIME;
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["HOSTNAME"]=$hostname;
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["RQS"]=1;
		
	}else{
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["SIZE"]=intval($GLOBALS["MEMORY"]["ACCESSES"][$KEY]["SIZE"])+intval($SIZE);
		$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["RQS"]=$GLOBALS["MEMORY"]["ACCESSES"][$KEY]["RQS"]+1;
	}
	
	DumpMemory();

}

function DumpMemory(){
	if(!isset($GLOBALS["MEMORY"]["ACCESSES"])){return;}
	if(tool_time_sec($GLOBALS["MEMORY"]["TIME"])<30){return;}
	$GLOBALS["MEMORY"]["TIME"]=time();
	$filename="/home/apache/artica-stats/requests.log";
	$c=0;
	while (list ($KEYMD5, $ARRAY) = each ($GLOBALS["MEMORY"]["ACCESSES"])){
		$RQS=$ARRAY["RQS"];
		$CODE=$ARRAY["CODE"];
		$IPADDR=$ARRAY["IPADDR"];
		$SIZE=$ARRAY["SIZE"];
		$TIME=$ARRAY["TIME"];
		$HOSTNAME=$ARRAY["HOSTNAME"];
		$LINE="$TIME;$HOSTNAME;$IPADDR;$CODE;$RQS;$SIZE";
		$c++;
		writeCompresslogs($filename,$LINE);
	}
	$GLOBALS["MEMORY"]["ACCESSES"]=array();
	$GLOBALS["MEMORY"]["TIME"]=time();
	events("Writing $c events...");
	@unlink("/etc/artica-postfix/apache-tail.time");
	@file_put_contents("/etc/artica-postfix/apache-tail.time", time());
	
}

function writeCompresslogs($filename,$line){
	$f = @fopen($filename, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);
}

function tool_time_sec($last_time){
	if($last_time==0){return 0;}
	$data1 = $last_time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return $difference;
}
function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}

	$unix=new unix();
	$unix->events($text,"/var/log/apache.watchdog.log",false,$sourcefunction,$sourceline);
}