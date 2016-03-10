<?php
include_once(dirname(__FILE__).'/class.squid.acls.groups.inc');
include_once(dirname(__FILE__).'/class.squid.inc');

class meta_squid_acls{
	
	
	public function meta_squid_acls(){
		
		
	}
	
	
	public function explain_this_acl($ID,$httpaccess,$enabled){
		$tpl=new templates();
		$q=new mysql_squid_builder();
		$explain_deny_color="#A71A05";
		$explain_allow_color="#0AAB3D";
		$enabled_color="black";
		$font_size=";font-size:16px";
		if($enabled==0){
			$explain_deny_color="#8a8a8a";
			$explain_allow_color="#8a8a8a";
			$enabled_color="#8a8a8a";
		}
		
		$color_translate[1]=$explain_deny_color;
		$color_translate[0]=$explain_allow_color;
		
		if(intval($ID)==0){
			return $tpl->_ENGINE_parse_body("<strong style='color:$explain_deny_color'>ID was not correctly sended!</strong></a>");
		}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM meta_webfilters_acls WHERE ID='$ID'"));
		$metagroup=intval($ligne["metagroup"]);
		$metauuid=$ligne["metauuid"];

		
		
		if($metagroup==0){
			if($metauuid==null){
				return $tpl->_ENGINE_parse_body("<strong style='color:$explain_deny_color'>{disable_this_rule_no_target_defined}</strong></a>");
			}
		}
		
		$translate["access_deny"]="{deny_access}";
		$translate["http_reply_access_deny"]="{deny_reply_access}";
		$translate["cache_parent"]="{use_parent_proxy}";
		$translate["http_reply_access_allow"]="{allow_reply_access}";
		$translate["access_allow"]="{allow}";
		$translate["snmp_access_allow"]="{allow_snmp_access}";
		$translate["log_access"]="{log_to_csv}";
		$translate["deny_access_except"]="{deny_access_except}";
		$translate["request_header_add"]="{add_http_header}";
		$translate["deny_log"]="{deny_logging}";
		$translate["deny_quota_rule"]="{affect_quota_rule}";
		$translate["url_rewrite_access_deny"]="{pass_trough_thewebfilter_engine}";
		$translate["adaptation_access_deny"]="{pass_trough_antivirus_engine}";
		$translate["tcp_outgoing_tos"]="{set_specific_dcsp}";
		$translate["tcp_outgoing_address"]="{acl_tcp_outgoing_address}";
		$translate["reply_body_max_size"]="{acl_reply_body_max_size}";
		

	
		$translate_color["access_deny"]=1;
		$translate_color["http_reply_access_deny"]=1;
		$translate_color["cache_parent"]="{use_parent_proxy}";
		$translate_color["http_reply_access_allow"]=0;
		$translate_color["access_allow"]=0;
		$translate_color["snmp_access_allow"]=0;
		$translate_color["log_access"]=0;
		$translate_color["deny_access_except"]=1;
		$translate_color["request_header_add"]=0;
		$translate_color["deny_log"]=1;
		$translate_color["deny_quota_rule"]=2;
		$translate_color["url_rewrite_access_deny"]=0;
		$translate_color["url_rewrite_access_allow"]=0;
		
		$translate_color["adaptation_access_deny"]=0;
		$color_integer=$translate_color[$httpaccess];
		if(!isset($color_translate[$color_integer])){$color_translate[$color_integer]=$explain_allow_color;}
		
		$MyColor=$color_translate[$color_integer];
		
		if($metagroup>0){
			$q=new mysql_meta();
			$metagroup_name=$q->gpid_to_name($metagroup);
			$js="Loadjs('artica-meta.groups.php?group-js=yes&ID=$metagroup')";
			$intro="<span style='color:$enabled_color;$font_size'>{for_proxy_group}:</span><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('artica-meta.groups.php?group-js=yes&ID=$metagroup')\" 
			style='color:$enabled_color;text-decoration:underline'>$metagroup_name</a></strong></span><br>";
		}
		
		if($metauuid<>null){
			$q=new mysql_meta();
			$hostname=$q->uuid_to_host($metauuid);
			$js="Loadjs('artica-meta.menus.php?js=yes&uuid=$metauuid')";
			$intro="<span style='color:$enabled_color;$font_size'>{for_proxy}:</span><a href=\"javascript:blur();\"
			OnClick=\"javascript:$js\"
			style='color:$enabled_color;text-decoration:underline'>$hostname</a></strong></span><br>";
		}
		
		if($httpaccess==null){
			return $tpl->_ENGINE_parse_body("<strong style='color:$explain_deny_color;$font_size'>{disable_this_rule_no_access_set}</strong></a>");
		}
		
		

		
		$gpsz=$this->explain_these_groups($ID,$enabled);
		
		$then="<span style='color:$enabled_color;$font_size'>$intro</span> $gpsz <span style='color:$enabled_color;$font_size'>{then}</span> <strong style='color:$MyColor;$font_size'>{$translate[$httpaccess]}</strong>";
		
		
		return $tpl->_ENGINE_parse_body("$then");
		}
		
		private function explain_these_groups($aclid,$enabled){
			$MyColor="black";
			$font_size=";font-size:16px";
			
			if($enabled==0){$MyColor="#8a8a8a";}
			
			$tpl=new templates();
			$acl=new squid_acls_groups();
			$q=new mysql_squid_builder();
			$sql="SELECT meta_webfilters_sqacllinks.gpid,
			meta_webfilters_sqacllinks.ID as tid,
			meta_webfilters_sqacllinks.negation,
			meta_webfilters_sqacllinks.zorder,
			webfilters_sqgroups.* FROM meta_webfilters_sqacllinks,webfilters_sqgroups
			WHERE meta_webfilters_sqacllinks.gpid=webfilters_sqgroups.ID 
			AND webfilters_sqgroups.enabled=1
			AND meta_webfilters_sqacllinks.aclid=$aclid
			ORDER BY meta_webfilters_sqacllinks.zorder";
			$results = $q->QUERY_SQL($sql);
			$pp=array();
			while ($ligne = mysql_fetch_assoc($results)) {
				$not=null;
				$mkey=$ligne["tid"];
				$arrayF=$acl->FlexArray($ligne['ID'],$enabled,16);
				if($ligne["negation"]==1){$not="{notfor}";}
				$pp[]="$not {$arrayF["ROW"]} ({$arrayF["ITEMS"]} {items})";
			
		}
		
		if(count($pp)>0){
			return $tpl->_ENGINE_parse_body("<span style='color:$MyColor;$font_size'>{and_for_groups}</span> ".@implode(" <span style='color:$MyColor;'>{and}</span> ", $pp)."<br>");
		}
	}
	
	private function build_acls(){
		$q=new mysql_squid_builder();
		
		$sql="SELECT * FROM meta_webfilters_acls WHERE enabled=1 ORDER by xORDER";
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$httpaccess=$ligne["httpaccess"];
			$ID=$ligne["ID"];
			$MAIN[$httpaccess][]=$this->build_single_acl($ID,$httpaccess);
			
			
		}
		
		
		
	}
	
	private function build_single_acl($ID,$httpaccess){
		
		
	}
	

}