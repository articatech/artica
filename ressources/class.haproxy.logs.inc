<?php



function haproxy_parseline($buffer){
	if(!preg_match("#haproxy.*?HASTATS:::(.+)#", $buffer,$re)){return;}
	$tr=explode(":::",$re[1]);
	$BYTESU=intval($tr[12]);
	$BYTESR=intval($tr[11]);
	$BYTEST=$BYTESU+$BYTESR;
	if($tr[13]=="--"){$tr[13]="OK";}
	$array["TIME"]=$tr[0];
	$array["ipsrc"]=$tr[1];
	$array["servicename"]=$tr[3];
	$array["remote_host"]=$tr[7];
	$array["HTTP_CODE"]=$tr[10];
	$array["BYTES"]=$BYTEST;
	$array["TS"]=$tr[13];
	
	$line=serialize($array);
	$f = @fopen("/home/artica/haproxy-postgres/realtime-events/HAPROXY.LOG", 'a');
	@fwrite($f, "$line\n");
	@fclose($f);
	
	
}