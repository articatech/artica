#!/usr/bin/php -q
<?php

build_php5();

function LOCATE_PHP5_EXTENSION_BIN(){
	$f[]="/usr/bin/php-config5";
	$f[]="/usr/bin/php-config";
	$f[]="/usr/local/bin/php-config5";
	$f[]="/usr/local/bin/php-config";
	while (list ($num, $bindir) = each ($f)){if(is_file($bindir)){return $bindir;}}
	
}

function LOCATE_PHP5_EXTENSION_DIR(){
$LOCATE_PHP5_EXTENSION_BIN=LOCATE_PHP5_EXTENSION_BIN();
if(is_file("$LOCATE_PHP5_EXTENSION_BIN")){
	shell_exec("$LOCATE_PHP5_EXTENSION_BIN --extension-dir >/etc/artica-postfix/settings/Daemons/php5ExtensionDir");
}

$php5ExtensionDir=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5ExtensionDir"));
if($php5ExtensionDir<>null){return $php5ExtensionDir;}
	$f[]="/usr/lib/php5/20100525+lfs";
	$f[]="/usr/lib/php5/20100525";
	$f[]="/usr/lib/php5/20090626+lfs";
	$f[]="/usr/lib/php5/20090626";
	$f[]="/usr/lib/php5/20060613+lfs";
	$f[]="/usr/lib/php5/20060613";
	while (list ($num, $bindir) = each ($f)){if(is_dir($bindir)){return $bindir;}}
	
}

function locate_roundcube_main_folder(){
$f[]="/usr/share/roundcubemail/index.php";
$f[]="/usr/share/roundcube/index.php";
$f[]="/var/lib/roundcube/index.php";
while (list ($num, $bindir) = each ($f)){if(is_file($bindir)){return dirname($bindir);}}
}
//##############################################################################


function build_php5(){
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5DisableMagicQuotesGpc")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5DisableMagicQuotesGpc", 0);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5FuncOverloadSeven")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5FuncOverloadSeven", 0);
	}

	if(!is_file("/etc/artica-postfix/settings/Daemons/ApcEnabledInPhp")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ApcEnabledInPhp", 0);
	}	
	if(!is_file("/etc/artica-postfix/settings/Daemons/UseSamePHPMysqlCredentials")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UseSamePHPMysqlCredentials", 1);
	}	
	if(!is_file("/etc/artica-postfix/settings/Daemons/PHPDefaultMysqlserverPort")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/PHPDefaultMysqlserverPort", 3306);
	}	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/ZarafaSessionTime")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ZarafaSessionTime", 1440);
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5PostMaxSize")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5PostMaxSize", 128);
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5UploadMaxFileSize")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5UploadMaxFileSize", 256);
	}	
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5MemoryLimit")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5MemoryLimit", 500);
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5DefaultCharset")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5DefaultCharset", "utf-8");
	}	
	if(!is_file("/etc/artica-postfix/settings/Daemons/timezones")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/timezones", "Europe/Berlin");
	}	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DisableEaccelerator")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableEaccelerator", 0);
	}	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/NoPHPMcrypt")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/NoPHPMcrypt", 0);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMemcached")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableMemcached", 1);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/LighttpdMinimalLibraries")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/LighttpdMinimalLibraries", 0);
	}
	
	
	$NoPHPMcrypt=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/NoPHPMcrypt"));
	$EnableMemcached=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMemcached"));
	$LighttpdMinimalLibraries=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/LighttpdMinimalLibraries"));
	
	
	
	
	$php5DisableMagicQuotesGpc=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5DisableMagicQuotesGpc"));
	$php5FuncOverloadSeven=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5FuncOverloadSeven"));
	$ApcEnabledInPhp=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ApcEnabledInPhp"));
	$UseSamePHPMysqlCredentials=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UseSamePHPMysqlCredentials"));
	$PHPDefaultMysqlserverPort=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PHPDefaultMysqlserverPort"));
	$ZarafaSessionTime=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ZarafaSessionTime"));
	$php5PostMaxSize=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5PostMaxSize"));
	$php5MemoryLimit=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5MemoryLimit"));
	$php5UploadMaxFileSize=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5UploadMaxFileSize"));
	$DisableEaccelerator=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableEaccelerator"));
	
	$php5DefaultCharset=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/php5DefaultCharset"));
	$timezones=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/timezones"));
	
	
	$PHPDefaultMysqlRoot=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
	$PHPDefaultMysqlPass=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
	$PHPDefaultMysqlserver=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
	$mysql_port=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/port")));
	$socket=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/socket"));
	if($PHPDefaultMysqlserver==null){$PHPDefaultMysqlserver="127.0.0.1";}
	if($socket==null){$socket="/var/run/mysqld/mysqld.sock";}
	$LOCATE_PHP5_EXTENSION_DIR=LOCATE_PHP5_EXTENSION_DIR();
	$locate_roundcube_main_folder=locate_roundcube_main_folder();
	if($mysql_port==0){$mysql_port=3306;}
	@mkdir("/var/lib/php5",0755,true);
	

	$f[]="[PHP]";
	$f[]="safe_mode = Off";
	$f[]="safe_mode_gid = Off";
	$f[]="short_open_tag = On";
	$f[]="engine = On";
	$f[]="precision    =  12";
	$f[]="y2k_compliance = On";
	$f[]="output_buffering = 4096";
	$f[]="enable_dl = On";
	$f[]="serialize_precision = 100";
	$f[]="disable_functions =";
	$f[]="disable_classes =";
	$f[]="expose_php = Off";
	$f[]="max_execution_time = 3600";
	$f[]="max_input_time = 3600";
	$f[]="memory_limit = {$php5MemoryLimit}M";
	$f[]="error_reporting  =  E_ALL & ~E_NOTICE";
	$f[]="display_errors = Off";
	$f[]="display_startup_errors = Off";
	$f[]="log_errors = On";
	$f[]="log_errors_max_len = 2048";
	$f[]="ignore_repeated_errors = Off";
	$f[]="ignore_repeated_source = Off";
	$f[]="report_memleaks = On";
	$f[]="track_errors = Off";
	$f[]="error_prepend_string = \"<font color=ff0000><code style='font-size:12px'>\"";
	$f[]="error_append_string = \"</code></font><br>\"";
	$f[]="html_errors = false";
	$f[]="error_log = /usr/share/artica-postfix/ressources/logs/php.log";
	$f[]="variables_order = \"EGPCS\"";
	$f[]="register_argc_argv = On";
	$f[]="auto_globals_jit = On";
	$f[]="post_max_size = {$php5PostMaxSize}M";
	$f[]="auto_prepend_file =";
	$f[]="auto_append_file =";
	$f[]="default_mimetype = \"text/html\"";
	$f[]="default_charset = \"$php5DefaultCharset\"";
	$f[]="unicode.semantics = off";
	$f[]="unicode.runtime_encoding = utf-8";
	$f[]="unicode.script_encoding = utf-8";
	$f[]="unicode.output_encoding = utf-8";
	$f[]="unicode.from_error_mode = U_INVALID_SUBSTITUTE";
	$f[]="unicode.from_error_subst_char = 3f";
	$f[]="include_path = \".:/usr/share/php:/usr/share/obm:/usr/share/php5:/usr/share/obm2:/usr/local/share/php:/usr/share/artica-postfix/ressources/externals:/usr/share/artica-postfix/ressources/externals/Gdata:/usr/share/php5/PEAR:/usr/share/pear\"";
	$f[]="doc_root =";
	$f[]="user_dir =";
	$f[]="extension_dir = \"$LOCATE_PHP5_EXTENSION_DIR\"";
	$f[]="cgi.force_redirect = 1";
	$f[]="cgi.fix_pathinfo = 1";
	$f[]="file_uploads = On";
	$f[]="upload_tmp_dir =/var/lighttpd/upload";
	$f[]="upload_max_filesize = {$php5UploadMaxFileSize}M";
	$f[]="allow_url_fopen = On";
	$f[]="allow_url_include = Off";
	$f[]="from=\"anonymous@anonymous.com\"";
	$f[]="default_socket_timeout = 60";
	$f[]="safe_mode = Off";
if ($php5FuncOverloadSeven==1){
   if(is_dir($locate_roundcube_main_folder)){
     $f[]="mbstring.func_overload = 0";
   }else{
      $f[]="mbstring.func_overload = 7";
   }
}
if ($php5DisableMagicQuotesGpc==1){
   $f[]="magic_quotes_gpc = Off";
}
	
if(is_file('/usr/local/ioncube/ioncube_loader_lin_5.2.so')){
	$f[]="zend_extension=/usr/local/ioncube/ioncube_loader_lin_5.2.so";
}
	
if(is_file('/usr/lib/libxapian.so.22')){
    if(is_file('/usr/lib/sse2/libxapian.so.22')){
    	system("mv /usr/lib/sse2/libxapian.so.22 /usr/lib/sse2/libxapian-back.so.22");
    }
}
	
	
	
	    $f[]="";
	    $f[]="[Date]";
	    $f[]="date.timezone = \"$timezones\"";
	    $f[]="";
	    $f[]="[filter]";
	    $f[]="[iconv]";
	    $f[]="iconv.input_encoding = utf-8";
	    $f[]="iconv.internal_encoding = utf-8";
	    $f[]="iconv.output_encoding = utf-8";
	    $f[]="[Syslog]";
	    $f[]="define_syslog_variables  = Off";
	    $f[]="";
	    $f[]="[mail function]";
	    $f[]="[SQL]";
	    $f[]="sql.safe_mode = Off";
	    $f[]="";
	    $f[]="[ODBC]";
	    $f[]="odbc.allow_persistent = On";
	    $f[]="odbc.check_persistent = On";
	    $f[]="odbc.max_persistent = -1";
	    $f[]="odbc.max_links = -1";
	    $f[]="odbc.defaultlrl = 4096";
	    $f[]="odbc.defaultbinmode = 1";
	    $f[]="";
		$f[]="[MySQL]";
		$f[]="mysql.allow_persistent = On";
		$f[]="mysql.max_persistent = -1";
		$f[]="mysql.max_links = -1";
		$f[]="mysql.default_port =$mysql_port";
		$f[]="mysql.default_socket =\"$socket\"";
	    $f[]="mysql.default_host =$PHPDefaultMysqlserver";
	    $f[]="mysql.default_user =$PHPDefaultMysqlRoot";
		$f[]="mysql.connect_timeout = 60";
		$f[]="mysql.trace_mode = Off";
		$f[]="[LDAP]";
		$f[]="ldap.max_links = -1";
		$f[]="ldap.allow_persistent = On";
		$f[]="ldap.check_persistent = On";
		$f[]="";
		$f[]="[MySQLi]";
		$f[]="mysqli.max_links = -1";
		$f[]="mysqli.default_port = $mysql_port";
	    $f[]="mysqli.default_socket =\"$socket\"";
	    $f[]="mysqli.default_host =$PHPDefaultMysqlserver";
	    $f[]="mysqli.default_user =$PHPDefaultMysqlRoot";
		$f[]="mysqli.reconnect = Off";
		$f[]="";
		$f[]="[mSQL]";
		$f[]="msql.allow_persistent = On";
		$f[]="msql.max_persistent = -1";
		$f[]="msql.max_links = -1";
		$f[]="";
		$f[]="[OCI8]";
		$f[]="[PostgresSQL]";
		$f[]="[Sybase]";
		$f[]="[Sybase-CT]";
		$f[]="[bcmath]";
		$f[]="[browscap]";
		$f[]="[Informix]";
		$f[]="[Session]";
		$f[]="session.save_handler = files";
		$f[]="session.save_path = \"/var/lib/php5\"";
		$f[]="session.use_cookies = 1";
		$f[]="session.use_only_cookies = 1";
		$f[]="session.name = PHPSESSID";
		$f[]="session.auto_start = 0";
		$f[]="session.cookie_lifetime = 0";
		$f[]="session.cookie_path = /";
		$f[]="session.cookie_domain =";
		$f[]="session.cookie_httponly =";
		$f[]="session.serialize_handler = php";
		$f[]="session.gc_probability = 1";
		$f[]="session.gc_divisor     = 100";
		$f[]="session.gc_maxlifetime = $ZarafaSessionTime";
	    $f[]="session.referer_check =";
	    $f[]="session.entropy_length = 0";
	    $f[]="session.entropy_file =";
	    $f[]="session.cache_limiter = nocache";
	    $f[]="session.cache_expire = 420";
	    $f[]="session.use_trans_sid = 0";
	    $f[]="session.hash_function = 0";
	    $f[]="session.bug_compat_warn = Off";
	    $f[]="session.hash_bits_per_character = 4";
	    $f[]="url_rewriter.tags = \"a=href,area=href,frame=src,input=src,form=,fieldset=\"";
	    $f[]="";
	    $f[]="[MSSQL]";
	    $f[]="mssql.allow_persistent = On";
	    $f[]="mssql.max_persistent = -1";
	    $f[]="mssql.max_links = -1";
	    $f[]="mssql.min_error_severity = 10";
	    $f[]="mssql.min_message_severity = 10";
	    $f[]="mssql.compatability_mode = Off";
	    $f[]="mssql.connect_timeout = 5";
	    $f[]="mssql.timeout = 60";
	    $f[]="mssql.textlimit = 4096";
	    $f[]="mssql.textsize = 4096";
	    $f[]="mssql.batchsize = 0";
	    $f[]="mssql.datetimeconvert = On";
	    $f[]="mssql.secure_connection = Off";
	    $f[]="mssql.max_procs = -1";
	    $f[]="mssql.charset = \"ISO-8859-1\"";
	    $f[]="";
	    $f[]="[Assertion]";
	    $f[]="[COM]";
	    $f[]="[mbstring]";
	    $f[]="[FrontBase]";
	    $f[]="[gd]";
	    $f[]="[exif]";
	    $f[]="[Tidy]";
	    $f[]="tidy.clean_output = Off";
	    $f[]="";
	    $f[]="[soap]";
	    $f[]="soap.wsdl_cache_ttl=86400";
if ($DisableEaccelerator==0){
	if(is_file("$LOCATE_PHP5_EXTENSION_DIR/eaccelerator.so")){
		@mkdir('/tmp/eaccelerator2',0700,true);
		@chmod('/tmp/eaccelerator2',0700);
		@chown('/tmp/eaccelerator2',"www-data");
		$f[]="extension=\"eaccelerator.so\"";
		$f[]="eaccelerator.shm_size=\"16\"";
		$f[]="eaccelerator.cache_dir=\"/tmp/eaccelerator2\"";
		$f[]="eaccelerator.enable=\"1\"";
		$f[]="eaccelerator.optimizer=\"1\"";
		$f[]="eaccelerator.check_mtime=\"1\"";
		$f[]="eaccelerator.debug=\"0\"";
		$f[]="eaccelerator.filter=\"\"";
		$f[]="eaccelerator.shm_max=\"0\"";
		$f[]="eaccelerator.shm_ttl=\"0\"";
		$f[]="eaccelerator.shm_prune_period=\"0\"";
		$f[]="eaccelerator.shm_only=\"0\"";
		$f[]="eaccelerator.compress=\"1\"";
		$f[]="eaccelerator.compress_level=\"9\"";
	}
}



	
if(is_file("$LOCATE_PHP5_EXTENSION_DIR/apc.so")){
   if($ApcEnabledInPhp==1){
	    $f[]="";
	    $f[]="extension=apc.so";
	    $f[]="[APC]";
	    $f[]="apc.enable_cli=\"1\"";
	    $f[]="apc.stat =\"0\"";
	    $f[]="apc.include_once_override=\"0\"";
	    $f[]="apc.cache_by_default=\"0\"";
	    $f[]="apc.filters = \"-(\.php|\.inc)\"";
	    $f[]="";
   }
}	

if($LighttpdMinimalLibraries==0){
if($EnableMemcached==1){
   if(is_file("$LOCATE_PHP5_EXTENSION_DIR/memcache.so")){
  	  @mkdir('/var/lib/memcache',0777,true);
      	$f[]="[memcache]";
	 	$f[]="memcache.dbpath=\"/var/lib/memcache\"";
		$f[]="memcache.maxreclevel=0";
		$f[]="memcache.maxfiles=0";
		$f[]="memcache.archivememlim=0";
		$f[]="memcache.maxfilesize=0";
		$f[]="memcache.maxratio=0";
   }
}}

if(is_dir("/etc/php5/conf.d")){
	shell_exec("/bin/rm -f /etc/php5/conf.d/*.ini");
}
if(is_dir("/etc/php.d")){
	shell_exec("/bin/rm -f /etc/php.d/*.ini");
}
if(is_dir("/etc/php5/cli/conf.d")){
	shell_exec("/bin/rm -f /etc/php5/cli/conf.d/*.ini");
}

system('/bin/ln -s /usr/share/artica-postfix/ressources/logs/php.log /var/log/php.log >/dev/null 2>&1');
$f[]="";
$SO["ctype.so"]=true;
$SO["pcntl.so"]=true;
$SO["curl.so"]=true;
$SO["openssl.so"]=true;
$SO["fileinfo.so"]=true;
if($LighttpdMinimalLibraries==0){$SO["dom.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["ftp.so"]=true;}
$SO["gd.so"]=true;
$SO["iconv.so"]=true;
$SO["imap.so"]=true;
$SO["ldap.so"]=true;
$SO["mysql.so"]=true;
$SO["readline.so"]=true;
$SO["hash.so"]=true;
$SO["xml.so"]=true;
$SO["sockets.so"]=true;
//$SO["xmlreader.so"]=true;
$SO["xmlwriter.so"]=true;
$SO["filter.so"]=true;
if($LighttpdMinimalLibraries==0){$SO["phpcups.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["mysqli.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["pdo.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["pdo_mysql.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["pdo_sqlite.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["sqlite.so"]=true;}
$SO["posix.so"]=true;
if($LighttpdMinimalLibraries==0){$SO["zip.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["xapian.so"]=true;}
$SO["geoip.so"]=true;
$SO["zlib.so"]=true;
$SO["tokenizer.so"]=true;
$SO["mailparse.so"]=true;
$SO["json.so"]=true;
if($LighttpdMinimalLibraries==0){$SO["uploadprogress.so"]=true;}
$SO["xmlrpc.so"]=true;
$SO["session.so"]=true;
$SO["gettext.so"]=true;
$SO["mbstring.so"]=true;
$SO["pgsql.so"]=true;
$SO["snmp.so"]=true;
$SO["mapi.so"]=true;
if($NoPHPMcrypt==0){$SO["mcrypt.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["ssh2.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["pspell.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["rrd.so"]=true;}
if($LighttpdMinimalLibraries==0){$SO["rrdtool.so"]=true;}

if($LighttpdMinimalLibraries==0){
	if($EnableMemcached==1){$SO['memcache.so']=true;}
}


if($LighttpdMinimalLibraries==0){$SO['ming.so']=true;}


if($DisableEaccelerator==1){
	if($LighttpdMinimalLibraries==0){
		$SO['eaccelerator.so']=true;
	}
}

$c=0;
while (list ($Library, $NONE) = each ($SO)){
	$c++;
	$sofile="$LOCATE_PHP5_EXTENSION_DIR/$Library";
	if(is_file($sofile)){
		$f[]="extension=$Library";
		echo "Starting......: [INIT]: Artica PHP Adding Extension $Library\n";
	}else{
		echo "Starting......: [INIT]: Artica PHP skipping $Library\n";
		$SKIPPED[]=$Library;
		
	}
}
echo "Starting......: [INIT]: Artica PHP Skipped Extensions ".@implode(", " , $SKIPPED)."\n";

$f[]="";
$PHP_INI["/etc/php.ini"]=true;
$PHP_INI["/etc/php5/cgi/php.ini"]=true;
$PHP_INI["/etc/php5/apache2/php.ini"]=true;
$PHP_INI["/etc/php-cgi-fcgi.ini"]=true;
$PHP_INI["/etc/php5/cli/php.ini"]=true;
$PHP_INI["/etc/php5/fastcgi/php.ini"]=true;
$PHP_INI["/etc/php5/fpm/php.ini"]=true;
$PHP_INI["/etc/artica-postfix/roundcube/php.ini"]=true;

while (list ($inifile, $NONE) = each ($PHP_INI)){
	if(!is_file($inifile)){
		continue;}
	@file_put_contents($inifile, @implode("\n", $f));
	echo "Starting......: [INIT]: Artica PHP Saving $inifile\n";
	
}
	
system("php /usr/share/artica-postfix/exec.shm.php --parse-langs"); 



}