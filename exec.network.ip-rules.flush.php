<?php


$LOCAL1=false;
$LOCAL2=false;
$LOCAL3=false;



exec("/sbin/ip rule show 2>&1",$results);

while (list ($index, $line) = each ($results) ){
	$line=trim($line);
	if($line==null){continue;}
	if(preg_match("#from all lookup local#i", $line)){
		echo "SKIP rule: $line\n";
		$LOCAL1=true;
		continue;
	}
	if(preg_match("#from all lookup main#i", $line)){
		echo "SKIP rule:  $line\n";
		$LOCAL2=true;
		continue;
	}	
	if(preg_match("#from all lookup default#i", $line)){
		echo "SKIP rule: $line\n";
		$LOCAL3=true;
		continue;
	}	
	echo "Analyze rule: $line\n";
	if(preg_match("#^([0-9]+):\s+(.+)#", $line,$re)){
		echo "Remove rule {$re[2]}\n";
		system("/sbin/ip rule del {$re[2]}");
		continue;
	}
	
}

if(!$LOCAL1){
	system("/sbin/ip rule add from all lookup local");
}
if(!$LOCAL2){
	system("/sbin/ip rule add from all lookup main");
}
if(!$LOCAL3){
	system("/sbin/ip rule add from all lookup default");
}
