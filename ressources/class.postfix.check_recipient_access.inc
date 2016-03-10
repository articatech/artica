<?php
include_once(dirname(__FILE__) . '/class.ldap.inc');
include_once(dirname(__FILE__) . '/class.mysql.inc');


class check_recipient_access_ou{
	
	private $classes=array();
	private $classes_def=array();
	private $ouvalids=array();
	
	public function check_recipient_access_ou(){
		if(!isset($GLOBALS["postmap"])){$unix=new unix();$GLOBALS["postmap"]=$unix->find_program("postmap");}
		if(!isset($GLOBALS["postconf"])){$unix=new unix();$GLOBALS["postconf"]=$unix->find_program("postconf");}
	}
	
	
	public function build(){
		
		$q=new mysql();
		$sql="SELECT * FROM smptd_client_access";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ou=$ligne["ou"];
			echo "Starting......: ".date("H:i:s")." Postfix ORG `{$ligne["ou"]}`\n";
			$MAIN=unserialize(base64_decode($ligne["configuration"]));
			$this->build_classes($ou,$MAIN);
			
		}
		
		if(count($this->classes)>0){
			shell_exec("{$GLOBALS["postconf"]} -e \"smtpd_restriction_classes = ".@implode(", ", $this->classes)."\"");
			
		}
		if(count($this->classes_def)>0){
			while (list ($className,$ClassParameters) = each ($this->classes_def) ){
				shell_exec("{$GLOBALS["postconf"]} -e \"$className = $ClassParameters\"");
			}
				
		}
		
		$this->build_database();
		
	}
	
	
	private function check_client_access($ou){
		
		@file_put_contents("/etc/postfix/check_client_access_$ou", "\n");
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/check_recipient_access_$ou >/dev/null 2>&1");
	}
	
	
	private function build_classes($ou,$MAIN){
		if($ou==null){
			echo "Starting......: ".date("H:i:s")." Postfix ORG is null!\n";
			return;
		}
		$md5=md5($ou);
		if(count($MAIN)==0){return;}
		$reject_unknown_client_hostname=$MAIN['reject_unknown_client_hostname'];
		$reject_unknown_reverse_client_hostname=$MAIN['reject_unknown_reverse_client_hostname'];
		$reject_unknown_sender_domain=$MAIN['reject_unknown_sender_domain'];
		$reject_invalid_hostname=$MAIN['reject_invalid_hostname'];
		$reject_non_fqdn_sender=$MAIN['reject_non_fqdn_sender'];
		
		$tot=$reject_unknown_client_hostname+$reject_unknown_reverse_client_hostname+$reject_unknown_sender_domain+$reject_invalid_hostname+$reject_non_fqdn_sender;
		
		
		$f[]="permit_mynetworks,check_client_access hash:/etc/postfix/check_client_access_$ou";
		if($reject_unknown_client_hostname==1){
			$f[]="reject_unknown_client_hostname";
		}
		if($reject_unknown_reverse_client_hostname==1){
			$f[]="reject_unknown_reverse_client_hostname";
		}		
		if($reject_unknown_sender_domain==1){
			$f[]="reject_unknown_sender_domain";
		}
		if($reject_invalid_hostname==1){
			$f[]="reject_invalid_hostname";
		}
		if($reject_non_fqdn_sender==1){
			$f[]="reject_non_fqdn_sender";
		}		
		
		$f[]="permit";
		
		$class_name="classou_$md5";
		echo "Starting......: ".date("H:i:s")." Postfix ORG: $ou class:$class_name...\n";
		$this->classes[]=$class_name;
		$this->ouvalids[$ou]=$class_name;
		$this->classes_def[$class_name]=@implode(", ", $f);
		
		
	}
	
	private function build_database(){
		$ldap=new clladp();
		while (list ($ou, $className) = each ($this->ouvalids) ){
			$domains=$ldap->hash_get_domains_ou($ou);
			
			while (list ($num, $ligne) = each ($domains) ){
				echo "Starting......: ".date("H:i:s")." Postfix ORG: $ou Domain:$num...\n";
				$f[]="$num\t$className";
				
			}
			
		}
		
		@file_put_contents("/etc/postfix/check_recipient_access_ou", @implode("\n", $f));
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/check_recipient_access_ou >/dev/null 2>&1");
		
		
	}
	
	
	
}