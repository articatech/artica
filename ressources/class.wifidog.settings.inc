<?php
include_once(dirname(__FILE__)."/class.wifidog.sql.inc");
include_once(dirname(__FILE__)."/class.wifidog.tools.inc");
include_once(dirname(__FILE__)."/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/class.wifidog.rules.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");

class wifidog_settings{
	private $ruleid=0;
	
	function wifidog_settings($ruleid=0){
		if(!is_numeric($ruleid)){$ruleid=0;}
		if($ruleid==0){
			$wifidog_rules=new wifidog_rules();
			$this->ruleid=$wifidog_rules->ruleid;
		}else{
			$this->ruleid=$ruleid;
		}
		
		
	}
	
	
	public function GET_INFO($key){
		$cachefile="{$GLOBALS["CACHEDIR"]}/rule.$this->ruleid.$key";
		if(is_file($cachefile)){return @file_get_contents($cachefile);}
		$q=new mysql_hotspot();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,MasterValue FROM webauth_settings WHERE ruleid='$this->ruleid' AND `MasterKey`='$key'"));
		if(intval($ligne["ID"])==0){return null;}
		@file_put_contents($cachefile, $ligne["MasterValue"]);
		return $ligne["MasterValue"];
		
	}
	
	
	public function SET_INFO($key,$value){
		$cachefile="{$GLOBALS["CACHEDIR"]}/rule.$this->ruleid.$key";
		if(is_file($cachefile)){@unlink($cachefile);}
		$value=mysql_escape_string2($value);
		$q=new mysql_hotspot();
		
		$sql="CREATE TABLE IF NOT EXISTS `webauth_settings` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ruleid` BIGINT UNSIGNED NOT NULL,
			`MasterKey` VARCHAR(128) NOT NULL,
			`MasterValue` TEXT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `ruleid` (`ruleid`),
			 KEY `MasterKey` (`MasterKey`)
		)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,MasterValue FROM webauth_settings WHERE ruleid='$this->ruleid' AND `MasterKey`='$key'"));
		
		
		
		if(intval($ligne["ID"])==0){
			
			$q->QUERY_SQL("INSERT IGNORE INTO webauth_settings (MasterValue,MasterKey,ruleid) VALUES ('$value','$key','$this->ruleid')");
			if(!$q->ok){return false;}
			return true;
		}
		
		$q->QUERY_SQL("UPDATE webauth_settings SET MasterValue='$value' WHERE ID='{$ligne["ID"]}'");
		if(!$q->ok){return false;}
		return true;
		
	}

	
	
}