<?php



$mx=$argv[1];


for($i=1;$i<256;$i++){
	if(strlen($i)>0){$iprime="0$i";}
	$xRes=str_replace("@", $i, $xRes);
	$ipaddr=gethostbyname($xRes);
	if($ipaddr<>$xRes){echo "$ipaddr\n";}
	$xRes=str_replace("@", $iprime, $xRes);
	$ipaddr=gethostbyname($xRes);
	if($ipaddr<>$xRes){echo "$ipaddr\n";}
	
}
?>