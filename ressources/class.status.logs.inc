<?php
function statusLogs($line){

	$stat=array(
			"daemon started"=>"S",
			"failed to parse line"=>"C",
			"fatal"=>"C",
			"No route to host"=>"W",
			"Connection refused"=>"W",
			"Unable to"=>"W",
			"warning"=>"W",
			"KASINFO"=>"K",
			"kav4mailservers/bin/smtpscanner"=>"K",
			"server killed"=>"SS",
			"server pid"=>"ST",
			"process\s+[0-9]+\s+exited"=>"SS",
			"server successfully"=>"SR",
			"Startup"=>"SR",
			"Starting"=>"SR",
			"Listening for"=>"NET",
			"server successfully spawned"=>"SR",
			"prefork: child states"=>"ST",
			"server started"=>"S",
			"statistics:"=>"STAT",
			"No ap-mailfiler"=>"SS",
			"KASNOTICE Logs"=>"K",
			"permission denied"=>"PERM",
			"terminating on signal 15"=>"SS",
			"unknown user"=>"UK",
			"Must issue a STARTTLS"=>"BC",
			"Binding to TCP port"=>"S",
			"starting! pid"=>"S",
			"Recipient address rejected"=>"W",
			"Permission denied"=>"W",
			"status=deferred"=>"W",
			"Server closing!"=>"SS",
			"stopping authdaemond"=>"SS",
			"SIGTERM: Stopping"=>"SS",
			"exited normally"=>"SS",
			"resource usage:"=>"INFO",
			"stopping the"=>"SS",
			"keepup2date failed"=>"W",
			"socket error"=>"W",
			"timeout after"=>"W",
			"reload configuration"=>"SR",
			"awakened"=>"ST",
			"protocol error"=>"W",
			"UNEXPECTEDLY"=>"C",
			"Can't locate"=>"W",
			"bounced"=>"W",
			"couldn't find"=>"W",
			"sleeping"=>"ST",
			"verification error"=>"W",
			"connect to\s+.+failed"=>"C",
			"transaction error"=>"W",
			"mismatch"=>"W",
			"authorization failure"=>"W",
			"No clamd server "=>"W",
			"\:\: ERROR "=>"C",
			"SASL\(\-13\)"=>"W",
			"archiving log file"=>"AR",
			"archiving database"=>"AR",
			"checkpointing cyrus databases"=>"AR",
			"Ok: queued as "=>"MBX",
			"Delivered:\s+"=>"MBX",
			"postfix\/qmgr.+removed"=>"MBXR",
			"to=<.+?>,\s+relay=procmail,\s+delay"=>"MDA",
			"postfix\/smtpd.+client=.+"=>"NETINFOS",
			"postfix\/smtpd.+\s+connect from.+"=>"NET",
			"postfix\/smtpd.+\s+disconnect from.+"=>"NETSTOP",
			"postfix\/pipe.+to=.+relay=.+status=sent\s+\(delivered via.+"=>"MDA",
			"postfix\/cleanup.+message.+"=>"MAIL",
			"Subject: "=>"MAIL",
			"procmail: Match on"=>"INFO",
			"procmail: No match on"=>"DELETE",
			"Connection timed out"=>"W",
			"Config error in line"=>"W",
			"no such file"=>"W",
			"mailbox does not"=>"W",
			"is restarted"=>"S",
			"Starting slave"=>"S",
			"accepted connection"=>"NET",
			"refreshing the Postfix"=>"S",
			"authenticate failed"=>"C",
			"is not running"=>"C",
			"access denied"=>"W",
			"reload.+?version"=>"S",
			"1.1\" 40[2-9]"=>"UK",
			"check failed of .*? in groups"=>"UK",
			"_DIE"=>"W",
			"TROUBLE in"=>"W",
			'"\s+404\s+[0-9]+\s+"'=>"DELETE"
	);

	while (list ($num, $val) = each ($stat) ){
		if(preg_match('#' . $num . '#i',$line)>0){$img=$val;}

	}

	switch ($img) {
		case "AR":return 'img/cab_small.gif';break;
		case "BC":return 'img/status_bad_config.jpg';break;
		case "UK":return 'img/unknown-user-22.png';break;
		case "PERM":return 'img/status_permission.gif';break;
		case "STAT":return 'img/status_statistics.jpg';break;
		case "SR":return 'img/status_service_run.png';break;
		case "S":return 'img/status_service_run.png';break;
		case "ST":return 'img/status_service_wait.png';break;
		case"SS":return 'img/status_service_removed.png';break;
		case "C":return 'img/status_critical.png';break;
		case "W":return 'img/status_warning.png';break;
		case "MBX":return 'img/mailbox_hd.png';break;
		case "MBXR":return 'img/mailbox_hd_ed.gif';break;
		case "MDA":return 'img/mailbox-2-arrow.gif';break;
		case 'NET':return 'img/tree-folder-network-24.png';break;
		case 'NETSTOP':return 'img/tree-network-delete.gif';break;
		case 'NETINFOS':return 'img/tree-network-infos.gif';break;
		case 'MAIL':return 'img/mailbox_storage.gif';break;
		case "INFO":return 'img/16-infos.png';break;
		case "DELETE":return "img/ed_delete.gif";break;
		case "K":return 'img/k.gif';break;
		default:return 'img/status_ok.png';break;

	}

}