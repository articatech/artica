<?php

class ecap{
	
	var $SquideCapAVEnabled=0;
	var $ASROOT=false;
	
	function ecap(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		$sock=new sockets();
		$this->SquideCapAVEnabled=$sock->GET_INFO("SquideCapAVEnabled");
		if(!is_numeric($this->SquideCapAVEnabled)){$this->SquideCapAVEnabled=0;}
		if($this->ASROOT){if(!is_file("/usr/lib/ecap_adapter_av.so")){$this->SquideCapAVEnabled=0;}}
	}
	
	
	function build(){
		if($this->SquideCapAVEnabled==0){return;}
		$f[]="ecap_enable on";
		$f[]="acl HTTP_STATUS_OK http_status 200";
		
		$f[]="loadable_modules /usr/lib/ecap_adapter_av.so";
		$f[]="ecap_service AVRESP respmod_precache bypass=0 ecap://www.securepoint.de/ecap_av";
		
		$f[]="adaptation_access AVRESP allow HTTP_STATUS_OK";
		
		
		return @implode("\n", $f)."\n";
		
		
		
	}
	
	
	
}