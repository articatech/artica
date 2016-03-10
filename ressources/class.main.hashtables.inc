<?php
include_once(dirname(__FILE__) . '/class.ldap.inc');
include_once(dirname(__FILE__) . '/class.user.inc');
include_once(dirname(__FILE__) . '/class.mysql.inc');
include_once(dirname(__FILE__) . '/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/class.main_cf.inc');



class main_hash_table{
	var $mydestination_table=array();
	
	public function main_hash_table(){
		if(!isset($GLOBALS["postconf"])){$unix=new unix();$GLOBALS["postconf"]=$unix->find_program("postconf");}
		if(!isset($GLOBALS["postmap"])){$unix=new unix();$GLOBALS["postmap"]=$unix->find_program("postmap");}
		
	}
	
	private function mydestination_search(){
		
		$sock=new sockets();
		$PostfixLocalDomainToRemote=$sock->GET_INFO("PostfixLocalDomainToRemote");
		if(!is_numeric($PostfixLocalDomainToRemote)){$PostfixLocalDomainToRemote=0;}
		if($PostfixLocalDomainToRemote==1){return null;}
			
		$ldap=new clladp();
		if($ldap->ldapFailed){echo "Starting......: ".date("H:i:s")." Postfix Warning !!! LDAP server is not available !!! ". __FUNCTION__."() in file ". basename(__FILE__)."\n"; return;}
		$filter="(&(objectClass=organizationalUnit)(associatedDomain=*))";
		$attrs=array("associatedDomain");
		$dn="$ldap->suffix";
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
		for($i=0;$i<$hash["count"];$i++){
			for($t=0;$t<$hash[$i]["associateddomain"]["count"];$t++){
				$this->mydestination_table[]=$hash[$i][strtolower("associatedDomain")][$t]."\tOK";
			}
			
		}
		echo "Starting......: ".date("H:i:s")." Postfix ".count($this->mydestination_table)." Local domain(s)\n"; 	
	}
	

	public function mydestination(){
			$this->mydestination_search();
			if(!is_array($this->mydestination_table)){return null;}
			if(count($this->mydestination_table)==0){return null;}
			shell_exec("{$GLOBALS["postconf"]} -e \"mydestination =hash:/etc/postfix/mydestination\" >/dev/null 2>&1");
			shell_exec("{$GLOBALS["postconf"]} -e \"virtual_mailbox_domains =\" >/dev/null 2>&1");
			@file_put_contents("/etc/postfix/mydestination",implode("\n",$this->mydestination_table));
			echo "Starting......: ".date("H:i:s")." Postfix compiling mydestination...\n";
			shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/mydestination >/dev/null 2>&1");
	}	
	
	
}


