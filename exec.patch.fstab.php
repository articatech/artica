<?php
$GLOBALS["OUTPUT"]=true;
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Patching EXT4 filesystem...\n";}
$f=explode("\n",@file_get_contents("/etc/fstab"));

$change=false;

exec("/sbin/blkid 2>&1",$results);

while (list ($num, $val) = each ($results) ){
	$val=trim($val);
	if(preg_match("#^(.+?):.*?UUID=\"(.+?)\"\s+#", $val,$re)){
		
		$UUIDS[$re[2]]=$re[1];
	}
	
}




while (list ($num, $val) = each ($f) ){
	
	if(preg_match("#(.+?)\s+(.+?)\s+ext4\s+(.+?)\s+([0-9]+)\s+([0-9]+)#", $val,$re)){
		$newoptions=PatchOptions($re[3]);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EXT4 {$re[1]} with options {$re[3]} changed to $newoptions\n";}
		$f[$num]="{$re[1]}\t{$re[2]}\text4\t$newoptions\t{$re[4]}\t{$re[5]}";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EXT4 {$re[1]} change journal to journal_data_writeback\n";}
		$dev=$re[1];
		
		if(preg_match("#UUID=(.+)#", $dev,$rz)){
			if(!isset($UUIDS[$rz[1]])){continue;}
			$dev=$UUIDS[$rz[1]];
		}
		
		shell_exec("/sbin/tune2fs -o journal_data_writeback $dev");
		shell_exec("/sbin/tune2fs -O dir_index $dev");
		$change=true;
		continue;
		
	}
}

if($change){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Saving /etc/fstab\n";}
	@file_put_contents("/etc/fstab", @implode("\n", $f));
}



function PatchOptions($options){
	$f=explode(",",$options);
	$OPTS["defaults"]=true;
	while (list ($num, $val) = each ($f) ){
		$val=trim($val);
		if(trim($val)==null){continue;}
		$OPTS[$val]=true;
	}
	
	unset($OPTS["defaults"]);
	unset($OPTS["commit=100"]);
	unset($OPTS["nobh"]);
	unset($OPTS["nodiratime"]);
	
	
	$OPTS["rw"]=true;
	$OPTS["noatime"]=true;
	$OPTS["discard"]=true;
	$OPTS["data=writeback"]=true;
	$OPTS["barrier=0"]=true;
	$OPTS["commit=120"]=true;
	$OPTS["nodiratime"]=true;
	$OPTS["user_xattr"]=true;
	$OPTS["acl"]=true;
	
	while (list ($opts2, $val) = each ($OPTS) ){
		$t[]=$opts2;
		
	}
	return @implode(",", $t);
	
	
}