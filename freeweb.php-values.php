<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["freeweb-php-list"])){items_list();exit;}
	if(isset($_POST["key"])){item_save();exit;}
	if(isset($_POST["item-del"])){item_del();exit;}
	if(isset($_GET["new-item"])){item_popup();exit;}
	
	page();	
	
	
	
function page(){
	
	
$tpl=new templates();
$page=CurrentPageName();
$key=$tpl->_ENGINE_parse_body("{key}");
$value=$tpl->_ENGINE_parse_body("{value}");
$new_alias=$tpl->_ENGINE_parse_body("{new_php_value}");
$apply=$tpl->javascript_parse_text("{apply}");
$t=time();

	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_alias</strong>', bclass: 'Add', onpress : AddNewItem$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},
	
		],";	

$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var mem$t='';
function LaunchTable$t(){
$('#flexRT$t').flexigrid({
	url: '$page?freeweb-php-list=yes&servername={$_GET["servername"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$key', name : 'key', width : 544, sortable : false, align: 'left'},	
		{display: '$value', name : 'value', width :427, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : true, align: 'center'},
		
		],
	$buttons
	searchitems : [
		{display: '$key', name : 'key'},
		{display: '$value', name : 'value'},
		],
	sortname: 'key',
	sortorder: 'asc',
	usepager: true,
	title: 'PHP',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
}

function Apply$t(){
	Loadjs('freeweb.rebuild.progress.php?servername={$_GET["servername"]}');
}

function AddNewItem$t(){
	YahooWin6('600','$page?new-item=yes&servername={$_GET["servername"]}&t=$t','$new_alias');
}
	
var xitemDel$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$('#row'+mem$t).remove();
}		
	
function itemDel$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('item-del',id);
	XHR.appendData('servername','{$_GET["servername"]}');
   	XHR.sendAndLoad('$page', 'POST',xitemDel$t);		
}
setTimeout(\"LaunchTable$t()\",500);

</script>

";	

echo $html;
}	

function item_popup(){
$page=CurrentPageName();
$tpl=new templates();
$free=new freeweb($_GET["servername"]);
$t=$_GET["t"];
$php_values='a:646:{s:30:"allow_call_time_pass_reference";s:30:"allow_call_time_pass_reference";s:15:"allow_url_fopen";s:15:"allow_url_fopen";s:17:"allow_url_include";s:17:"allow_url_include";s:29:"always_populate_raw_post_data";s:29:"always_populate_raw_post_data";s:20:"apc.cache_by_default";s:20:"apc.cache_by_default";s:11:"apc.enabled";s:11:"apc.enabled";s:14:"apc.enable_cli";s:14:"apc.enable_cli";s:26:"apc.file_update_protection";s:26:"apc.file_update_protection";s:11:"apc.filters";s:11:"apc.filters";s:10:"apc.gc_ttl";s:10:"apc.gc_ttl";s:25:"apc.include_once_override";s:25:"apc.include_once_override";s:14:"apc.localcache";s:14:"apc.localcache";s:19:"apc.localcache.size";s:19:"apc.localcache.size";s:17:"apc.max_file_size";s:17:"apc.max_file_size";s:18:"apc.mmap_file_mask";s:18:"apc.mmap_file_mask";s:18:"apc.num_files_hint";s:18:"apc.num_files_hint";s:16:"apc.optimization";s:16:"apc.optimization";s:21:"apc.report_autofilter";s:21:"apc.report_autofilter";s:11:"apc.rfc1867";s:11:"apc.rfc1867";s:16:"apc.rfc1867_freq";s:16:"apc.rfc1867_freq";s:16:"apc.rfc1867_name";s:16:"apc.rfc1867_name";s:18:"apc.rfc1867_prefix";s:18:"apc.rfc1867_prefix";s:16:"apc.shm_segments";s:16:"apc.shm_segments";s:12:"apc.shm_size";s:12:"apc.shm_size";s:16:"apc.slam_defense";s:16:"apc.slam_defense";s:8:"apc.stat";s:8:"apc.stat";s:14:"apc.stat_ctime";s:14:"apc.stat_ctime";s:7:"apc.ttl";s:7:"apc.ttl";s:21:"apc.user_entries_hint";s:21:"apc.user_entries_hint";s:12:"apc.user_ttl";s:12:"apc.user_ttl";s:14:"apc.write_lock";s:14:"apc.write_lock";s:11:"apd.bitmask";s:11:"apd.bitmask";s:11:"apd.dumpdir";s:11:"apd.dumpdir";s:21:"apd.statement_tracing";s:21:"apd.statement_tracing";s:13:"arg_separator";s:13:"arg_separator";s:19:"arg_separator.input";s:19:"arg_separator.input";s:20:"arg_separator.output";s:20:"arg_separator.output";s:8:"asp_tags";s:8:"asp_tags";s:13:"assert.active";s:13:"assert.active";s:11:"assert.bail";s:11:"assert.bail";s:15:"assert.callback";s:15:"assert.callback";s:17:"assert.quiet_eval";s:17:"assert.quiet_eval";s:14:"assert.warning";s:14:"assert.warning";s:10:"async_send";s:10:"async_send";s:16:"auto_append_file";s:16:"auto_append_file";s:24:"auto_detect_line_endings";s:24:"auto_detect_line_endings";s:16:"auto_globals_jit";s:16:"auto_globals_jit";s:17:"auto_prepend_file";s:17:"auto_prepend_file";s:17:"axis2.client_home";s:17:"axis2.client_home";s:22:"axis2.enable_exception";s:22:"axis2.enable_exception";s:18:"axis2.enable_trace";s:18:"axis2.enable_trace";s:14:"axis2.log_path";s:14:"axis2.log_path";s:12:"bcmath.scale";s:12:"bcmath.scale";s:17:"bcompiler.enabled";s:17:"bcompiler.enabled";s:18:"birdstep.max_links";s:18:"birdstep.max_links";s:14:"blenc.key_file";s:14:"blenc.key_file";s:8:"browscap";s:8:"browscap";s:22:"cgi.check_shebang_line";s:22:"cgi.check_shebang_line";s:16:"cgi.discard_path";s:16:"cgi.discard_path";s:16:"cgi.fix_pathinfo";s:16:"cgi.fix_pathinfo";s:18:"cgi.force_redirect";s:18:"cgi.force_redirect";s:7:"cgi.nph";s:7:"cgi.nph";s:23:"cgi.redirect_status_env";s:23:"cgi.redirect_status_env";s:19:"cgi.rfc2616_headers";s:19:"cgi.rfc2616_headers";s:15:"child_terminate";s:15:"child_terminate";s:9:"cli.pager";s:9:"cli.pager";s:10:"cli.prompt";s:10:"cli.prompt";s:16:"cli_server.color";s:16:"cli_server.color";s:23:"coin_acceptor.autoreset";s:23:"coin_acceptor.autoreset";s:29:"coin_acceptor.auto_initialize";s:29:"coin_acceptor.auto_initialize";s:24:"coin_acceptor.auto_reset";s:24:"coin_acceptor.auto_reset";s:30:"coin_acceptor.command_function";s:30:"coin_acceptor.command_function";s:19:"coin_acceptor.delay";s:19:"coin_acceptor.delay";s:25:"coin_acceptor.delay_coins";s:25:"coin_acceptor.delay_coins";s:24:"coin_acceptor.delay_prom";s:24:"coin_acceptor.delay_prom";s:20:"coin_acceptor.device";s:20:"coin_acceptor.device";s:27:"coin_acceptor.lock_on_close";s:27:"coin_acceptor.lock_on_close";s:28:"coin_acceptor.start_unlocked";s:28:"coin_acceptor.start_unlocked";s:14:"com.allow_dcom";s:14:"com.allow_dcom";s:30:"com.autoregister_casesensitive";s:30:"com.autoregister_casesensitive";s:24:"com.autoregister_typelib";s:24:"com.autoregister_typelib";s:24:"com.autoregister_verbose";s:24:"com.autoregister_verbose";s:13:"com.code_page";s:13:"com.code_page";s:16:"com.typelib_file";s:16:"com.typelib_file";s:24:"crack.default_dictionary";s:24:"crack.default_dictionary";s:11:"curl.cainfo";s:11:"curl.cainfo";s:23:"daffodildb.default_host";s:23:"daffodildb.default_host";s:27:"daffodildb.default_password";s:27:"daffodildb.default_password";s:25:"daffodildb.default_socket";s:25:"daffodildb.default_socket";s:23:"daffodildb.default_user";s:23:"daffodildb.default_user";s:15:"daffodildb.port";s:15:"daffodildb.port";s:21:"date.default_latitude";s:21:"date.default_latitude";s:22:"date.default_longitude";s:22:"date.default_longitude";s:19:"date.sunrise_zenith";s:19:"date.sunrise_zenith";s:18:"date.sunset_zenith";s:18:"date.sunset_zenith";s:13:"date.timezone";s:13:"date.timezone";s:19:"dba.default_handler";s:19:"dba.default_handler";s:17:"dbx.colnames_case";s:17:"dbx.colnames_case";s:15:"default_charset";s:15:"default_charset";s:16:"default_mimetype";s:16:"default_mimetype";s:22:"default_socket_timeout";s:22:"default_socket_timeout";s:23:"define_syslog_variables";s:23:"define_syslog_variables";s:14:"detect_unicode";s:14:"detect_unicode";s:15:"disable_classes";s:15:"disable_classes";s:17:"disable_functions";s:17:"disable_functions";s:14:"display_errors";s:14:"display_errors";s:22:"display_startup_errors";s:22:"display_startup_errors";s:10:"docref_ext";s:10:"docref_ext";s:11:"docref_root";s:11:"docref_root";s:8:"doc_root";s:8:"doc_root";s:9:"enable_dl";s:9:"enable_dl";s:6:"engine";s:6:"engine";s:19:"error_append_string";s:19:"error_append_string";s:9:"error_log";s:9:"error_log";s:20:"error_prepend_string";s:20:"error_prepend_string";s:15:"error_reporting";s:15:"error_reporting";s:21:"etpan.default.charset";s:21:"etpan.default.charset";s:22:"etpan.default.protocol";s:22:"etpan.default.protocol";s:21:"exif.decode_jis_intel";s:21:"exif.decode_jis_intel";s:24:"exif.decode_jis_motorola";s:24:"exif.decode_jis_motorola";s:25:"exif.decode_unicode_intel";s:25:"exif.decode_unicode_intel";s:28:"exif.decode_unicode_motorola";s:28:"exif.decode_unicode_motorola";s:15:"exif.encode_jis";s:15:"exif.encode_jis";s:19:"exif.encode_unicode";s:19:"exif.encode_unicode";s:15:"exit_on_timeout";s:15:"exit_on_timeout";s:14:"expect.logfile";s:14:"expect.logfile";s:14:"expect.loguser";s:14:"expect.loguser";s:14:"expect.timeout";s:14:"expect.timeout";s:10:"expose_php";s:10:"expose_php";s:9:"extension";s:9:"extension";s:13:"extension_dir";s:13:"extension_dir";s:19:"fastcgi.impersonate";s:19:"fastcgi.impersonate";s:15:"fastcgi.logging";s:15:"fastcgi.logging";s:22:"fbsql.allow_persistant";s:22:"fbsql.allow_persistant";s:22:"fbsql.allow_persistent";s:22:"fbsql.allow_persistent";s:16:"fbsql.autocommit";s:16:"fbsql.autocommit";s:15:"fbsql.batchsize";s:15:"fbsql.batchsize";s:22:"fbsql.default_database";s:22:"fbsql.default_database";s:31:"fbsql.default_database_password";s:31:"fbsql.default_database_password";s:18:"fbsql.default_host";s:18:"fbsql.default_host";s:22:"fbsql.default_password";s:22:"fbsql.default_password";s:18:"fbsql.default_user";s:18:"fbsql.default_user";s:23:"fbsql.generate_warnings";s:23:"fbsql.generate_warnings";s:21:"fbsql.max_connections";s:21:"fbsql.max_connections";s:15:"fbsql.max_links";s:15:"fbsql.max_links";s:20:"fbsql.max_persistent";s:20:"fbsql.max_persistent";s:17:"fbsql.max_results";s:17:"fbsql.max_results";s:29:"fbsql.show_timestamp_decimals";s:29:"fbsql.show_timestamp_decimals";s:12:"file_uploads";s:12:"file_uploads";s:14:"filter.default";s:14:"filter.default";s:20:"filter.default_flags";s:20:"filter.default_flags";s:4:"from";s:4:"from";s:22:"gd.jpeg_ignore_warning";s:22:"gd.jpeg_ignore_warning";s:22:"geoip.custom_directory";s:22:"geoip.custom_directory";s:23:"geoip.database_standard";s:23:"geoip.database_standard";s:9:"gpc_order";s:9:"gpc_order";s:14:"hidef.ini_path";s:14:"hidef.ini_path";s:12:"highlight.bg";s:12:"highlight.bg";s:17:"highlight.comment";s:17:"highlight.comment";s:17:"highlight.default";s:17:"highlight.default";s:14:"highlight.html";s:14:"highlight.html";s:17:"highlight.keyword";s:17:"highlight.keyword";s:16:"highlight.string";s:16:"highlight.string";s:11:"html_errors";s:11:"html_errors";s:21:"htscanner.config_file";s:21:"htscanner.config_file";s:25:"htscanner.default_docroot";s:25:"htscanner.default_docroot";s:21:"htscanner.default_ttl";s:21:"htscanner.default_ttl";s:23:"htscanner.stop_on_error";s:23:"htscanner.stop_on_error";s:20:"http.allowed_methods";s:20:"http.allowed_methods";s:24:"http.allowed_methods_log";s:24:"http.allowed_methods_log";s:14:"http.cache_log";s:14:"http.cache_log";s:18:"http.composite_log";s:18:"http.composite_log";s:14:"http.etag.mode";s:14:"http.etag.mode";s:14:"http.etag_mode";s:14:"http.etag_mode";s:15:"http.force_exit";s:15:"http.force_exit";s:24:"http.log.allowed_methods";s:24:"http.log.allowed_methods";s:14:"http.log.cache";s:14:"http.log.cache";s:18:"http.log.composite";s:18:"http.log.composite";s:18:"http.log.not_found";s:18:"http.log.not_found";s:17:"http.log.redirect";s:17:"http.log.redirect";s:20:"http.ob_deflate_auto";s:20:"http.ob_deflate_auto";s:21:"http.ob_deflate_flags";s:21:"http.ob_deflate_flags";s:20:"http.ob_inflate_auto";s:20:"http.ob_inflate_auto";s:21:"http.ob_inflate_flags";s:21:"http.ob_inflate_flags";s:20:"http.only_exceptions";s:20:"http.only_exceptions";s:29:"http.persistent.handles.ident";s:29:"http.persistent.handles.ident";s:29:"http.persistent.handles.limit";s:29:"http.persistent.handles.limit";s:17:"http.redirect_log";s:17:"http.redirect_log";s:30:"http.request.datashare.connect";s:30:"http.request.datashare.connect";s:29:"http.request.datashare.cookie";s:29:"http.request.datashare.cookie";s:26:"http.request.datashare.dns";s:26:"http.request.datashare.dns";s:26:"http.request.datashare.ssl";s:26:"http.request.datashare.ssl";s:28:"http.request.methods.allowed";s:28:"http.request.methods.allowed";s:27:"http.request.methods.custom";s:27:"http.request.methods.custom";s:28:"http.send.deflate.start_auto";s:28:"http.send.deflate.start_auto";s:29:"http.send.deflate.start_flags";s:29:"http.send.deflate.start_flags";s:28:"http.send.inflate.start_auto";s:28:"http.send.inflate.start_auto";s:29:"http.send.inflate.start_flags";s:29:"http.send.inflate.start_flags";s:23:"http.send.not_found_404";s:23:"http.send.not_found_404";s:25:"hyerwave.allow_persistent";s:25:"hyerwave.allow_persistent";s:26:"hyperwave.allow_persistent";s:26:"hyperwave.allow_persistent";s:22:"hyperwave.default_port";s:22:"hyperwave.default_port";s:22:"ibase.allow_persistent";s:22:"ibase.allow_persistent";s:16:"ibase.dateformat";s:16:"ibase.dateformat";s:21:"ibase.default_charset";s:21:"ibase.default_charset";s:16:"ibase.default_db";s:16:"ibase.default_db";s:22:"ibase.default_password";s:22:"ibase.default_password";s:18:"ibase.default_user";s:18:"ibase.default_user";s:15:"ibase.max_links";s:15:"ibase.max_links";s:20:"ibase.max_persistent";s:20:"ibase.max_persistent";s:16:"ibase.timeformat";s:16:"ibase.timeformat";s:21:"ibase.timestampformat";s:21:"ibase.timestampformat";s:15:"ibm_db2.binmode";s:15:"ibm_db2.binmode";s:23:"ibm_db2.i5_all_pconnect";s:23:"ibm_db2.i5_all_pconnect";s:23:"ibm_db2.i5_allow_commit";s:23:"ibm_db2.i5_allow_commit";s:21:"ibm_db2.i5_dbcs_alloc";s:21:"ibm_db2.i5_dbcs_alloc";s:21:"ibm_db2.instance_name";s:21:"ibm_db2.instance_name";s:24:"ibm_db2.i5_ignore_userid";s:24:"ibm_db2.i5_ignore_userid";s:20:"iconv.input_encoding";s:20:"iconv.input_encoding";s:23:"iconv.internal_encoding";s:23:"iconv.internal_encoding";s:21:"iconv.output_encoding";s:21:"iconv.output_encoding";s:20:"ifx.allow_persistent";s:20:"ifx.allow_persistent";s:14:"ifx.blobinfile";s:14:"ifx.blobinfile";s:17:"ifx.byteasvarchar";s:17:"ifx.byteasvarchar";s:17:"ifx.charasvarchar";s:17:"ifx.charasvarchar";s:16:"ifx.default_host";s:16:"ifx.default_host";s:20:"ifx.default_password";s:20:"ifx.default_password";s:16:"ifx.default_user";s:16:"ifx.default_user";s:13:"ifx.max_links";s:13:"ifx.max_links";s:18:"ifx.max_persistent";s:18:"ifx.max_persistent";s:14:"ifx.nullformat";s:14:"ifx.nullformat";s:17:"ifx.textasvarchar";s:17:"ifx.textasvarchar";s:22:"ignore_repeated_errors";s:22:"ignore_repeated_errors";s:22:"ignore_repeated_source";s:22:"ignore_repeated_source";s:17:"ignore_user_abort";s:17:"ignore_user_abort";s:26:"imlib2.font_cache_max_size";s:26:"imlib2.font_cache_max_size";s:16:"imlib2.font_path";s:16:"imlib2.font_path";s:14:"implicit_flush";s:14:"implicit_flush";s:12:"include_path";s:12:"include_path";s:19:"intl.default_locale";s:19:"intl.default_locale";s:16:"intl.error_level";s:16:"intl.error_level";s:19:"intl.use_exceptions";s:19:"intl.use_exceptions";s:23:"ingres.allow_persistent";s:23:"ingres.allow_persistent";s:24:"ingres.array_index_start";s:24:"ingres.array_index_start";s:11:"ingres.auto";s:11:"ingres.auto";s:26:"ingres.blob_segment_length";s:26:"ingres.blob_segment_length";s:18:"ingres.cursor_mode";s:18:"ingres.cursor_mode";s:23:"ingres.default_database";s:23:"ingres.default_database";s:23:"ingres.default_password";s:23:"ingres.default_password";s:19:"ingres.default_user";s:19:"ingres.default_user";s:15:"ingres.describe";s:15:"ingres.describe";s:24:"ingres.fetch_buffer_size";s:24:"ingres.fetch_buffer_size";s:16:"ingres.max_links";s:16:"ingres.max_links";s:21:"ingres.max_persistent";s:21:"ingres.max_persistent";s:23:"ingres.reuse_connection";s:23:"ingres.reuse_connection";s:17:"ingres.scrollable";s:17:"ingres.scrollable";s:12:"ingres.trace";s:12:"ingres.trace";s:20:"ingres.trace_connect";s:20:"ingres.trace_connect";s:11:"ingres.utf8";s:11:"ingres.utf8";s:17:"ircg.control_user";s:17:"ircg.control_user";s:24:"ircg.keep_alive_interval";s:24:"ircg.keep_alive_interval";s:28:"ircg.max_format_message_sets";s:28:"ircg.max_format_message_sets";s:20:"ircg.shared_mem_size";s:20:"ircg.shared_mem_size";s:13:"ircg.work_dir";s:13:"ircg.work_dir";s:13:"last_modified";s:13:"last_modified";s:12:"ldap.base_dn";s:12:"ldap.base_dn";s:14:"ldap.max_links";s:14:"ldap.max_links";s:11:"log.dbm_dir";s:11:"log.dbm_dir";s:10:"log_errors";s:10:"log_errors";s:18:"log_errors_max_len";s:18:"log_errors_max_len";s:16:"magic_quotes_gpc";s:16:"magic_quotes_gpc";s:20:"magic_quotes_runtime";s:20:"magic_quotes_runtime";s:19:"magic_quotes_sybase";s:19:"magic_quotes_sybase";s:17:"mail.add_x_header";s:17:"mail.add_x_header";s:27:"mail.force_extra_parameters";s:27:"mail.force_extra_parameters";s:8:"mail.log";s:8:"mail.log";s:21:"mailparse.def_charset";s:21:"mailparse.def_charset";s:16:"maxdb.default_db";s:16:"maxdb.default_db";s:18:"maxdb.default_host";s:18:"maxdb.default_host";s:16:"maxdb.default_pw";s:16:"maxdb.default_pw";s:18:"maxdb.default_user";s:18:"maxdb.default_user";s:18:"maxdb.long_readlen";s:18:"maxdb.long_readlen";s:18:"max_execution_time";s:18:"max_execution_time";s:23:"max_input_nesting_level";s:23:"max_input_nesting_level";s:14:"max_input_vars";s:14:"max_input_vars";s:14:"max_input_time";s:14:"max_input_time";s:21:"mbstring.detect_order";s:21:"mbstring.detect_order";s:29:"mbstring.encoding_translation";s:29:"mbstring.encoding_translation";s:22:"mbstring.func_overload";s:22:"mbstring.func_overload";s:19:"mbstring.http_input";s:19:"mbstring.http_input";s:20:"mbstring.http_output";s:20:"mbstring.http_output";s:26:"mbstring.internal_encoding";s:26:"mbstring.internal_encoding";s:17:"mbstring.language";s:17:"mbstring.language";s:24:"mbstring.script_encoding";s:24:"mbstring.script_encoding";s:35:"mbstring.http_output_conv_mimetypes";s:35:"mbstring.http_output_conv_mimetypes";s:25:"mbstring.strict_detection";s:25:"mbstring.strict_detection";s:29:"mbstring.substitute_character";s:29:"mbstring.substitute_character";s:21:"mcrypt.algorithms_dir";s:21:"mcrypt.algorithms_dir";s:16:"mcrypt.modes_dir";s:16:"mcrypt.modes_dir";s:23:"memcache.allow_failover";s:23:"memcache.allow_failover";s:19:"memcache.chunk_size";s:19:"memcache.chunk_size";s:21:"memcache.default_port";s:21:"memcache.default_port";s:22:"memcache.hash_function";s:22:"memcache.hash_function";s:22:"memcache.hash_strategy";s:22:"memcache.hash_strategy";s:30:"memcache.max_failover_attempts";s:30:"memcache.max_failover_attempts";s:12:"memory_limit";s:12:"memory_limit";s:16:"mime_magic.debug";s:16:"mime_magic.debug";s:20:"mime_magic.magicfile";s:20:"mime_magic.magicfile";s:22:"mongo.allow_empty_keys";s:22:"mongo.allow_empty_keys";s:22:"mongo.allow_persistent";s:22:"mongo.allow_persistent";s:16:"mongo.chunk_size";s:16:"mongo.chunk_size";s:9:"mongo.cmd";s:9:"mongo.cmd";s:18:"mongo.default_host";s:18:"mongo.default_host";s:18:"mongo.default_port";s:18:"mongo.default_port";s:24:"mongo.is_master_interval";s:24:"mongo.is_master_interval";s:20:"mongo.long_as_object";s:20:"mongo.long_as_object";s:17:"mongo.native_long";s:17:"mongo.native_long";s:19:"mongo.ping_interval";s:19:"mongo.ping_interval";s:10:"mongo.utf8";s:10:"mongo.utf8";s:21:"msql.allow_persistent";s:21:"msql.allow_persistent";s:14:"msql.max_links";s:14:"msql.max_links";s:19:"msql.max_persistent";s:19:"msql.max_persistent";s:22:"mssql.allow_persistent";s:22:"mssql.allow_persistent";s:15:"mssql.batchsize";s:15:"mssql.batchsize";s:13:"mssql.charset";s:13:"mssql.charset";s:24:"mssql.compatability_mode";s:24:"mssql.compatability_mode";s:21:"mssql.connect_timeout";s:21:"mssql.connect_timeout";s:21:"mssql.datetimeconvert";s:21:"mssql.datetimeconvert";s:15:"mssql.max_links";s:15:"mssql.max_links";s:20:"mssql.max_persistent";s:20:"mssql.max_persistent";s:15:"mssql.max_procs";s:15:"mssql.max_procs";s:24:"mssql.min_error_severity";s:24:"mssql.min_error_severity";s:26:"mssql.min_message_severity";s:26:"mssql.min_message_severity";s:23:"mssql.secure_connection";s:23:"mssql.secure_connection";s:15:"mssql.textlimit";s:15:"mssql.textlimit";s:14:"mssql.textsize";s:14:"mssql.textsize";s:13:"mssql.timeout";s:13:"mssql.timeout";s:24:"mysql.allow_local_infile";s:24:"mysql.allow_local_infile";s:22:"mysql.allow_persistent";s:22:"mysql.allow_persistent";s:20:"mysql.max_persistent";s:20:"mysql.max_persistent";s:15:"mysql.max_links";s:15:"mysql.max_links";s:16:"mysql.trace_mode";s:16:"mysql.trace_mode";s:18:"mysql.default_port";s:18:"mysql.default_port";s:20:"mysql.default_socket";s:20:"mysql.default_socket";s:18:"mysql.default_host";s:18:"mysql.default_host";s:18:"mysql.default_user";s:18:"mysql.default_user";s:22:"mysql.default_password";s:22:"mysql.default_password";s:21:"mysql.connect_timeout";s:21:"mysql.connect_timeout";s:25:"mysqli.allow_local_infile";s:25:"mysqli.allow_local_infile";s:23:"mysqli.allow_persistent";s:23:"mysqli.allow_persistent";s:21:"mysqli.max_persistent";s:21:"mysqli.max_persistent";s:16:"mysqli.max_links";s:16:"mysqli.max_links";s:19:"mysqli.default_port";s:19:"mysqli.default_port";s:21:"mysqli.default_socket";s:21:"mysqli.default_socket";s:19:"mysqli.default_host";s:19:"mysqli.default_host";s:19:"mysqli.default_user";s:19:"mysqli.default_user";s:17:"mysqli.default_pw";s:17:"mysqli.default_pw";s:16:"mysqli.reconnect";s:16:"mysqli.reconnect";s:17:"mysqli.cache_size";s:17:"mysqli.cache_size";s:33:"mysqlnd.collect_memory_statistics";s:33:"mysqlnd.collect_memory_statistics";s:26:"mysqlnd.collect_statistics";s:26:"mysqlnd.collect_statistics";s:13:"mysqlnd.debug";s:13:"mysqlnd.debug";s:16:"mysqlnd.log_mask";s:16:"mysqlnd.log_mask";s:28:"mysqlnd.mempool_default_size";s:28:"mysqlnd.mempool_default_size";s:27:"mysqlnd.net_cmd_buffer_size";s:27:"mysqlnd.net_cmd_buffer_size";s:28:"mysqlnd.net_read_buffer_size";s:28:"mysqlnd.net_read_buffer_size";s:24:"mysqlnd.net_read_timeout";s:24:"mysqlnd.net_read_timeout";s:32:"mysqlnd.sha256_server_public_key";s:32:"mysqlnd.sha256_server_public_key";s:19:"mysqlnd.trace_alloc";s:19:"mysqlnd.trace_alloc";s:23:"mysqlnd_memcache.enable";s:23:"mysqlnd_memcache.enable";s:17:"mysqlnd_ms.enable";s:17:"mysqlnd_ms.enable";s:29:"mysqlnd_ms.force_config_usage";s:29:"mysqlnd_ms.force_config_usage";s:19:"mysqlnd_ms.ini_file";s:19:"mysqlnd_ms.ini_file";s:22:"mysqlnd_ms.config_file";s:22:"mysqlnd_ms.config_file";s:29:"mysqlnd_ms.collect_statistics";s:29:"mysqlnd_ms.collect_statistics";s:23:"mysqlnd_ms.multi_master";s:23:"mysqlnd_ms.multi_master";s:27:"mysqlnd_ms.disable_rw_split";s:27:"mysqlnd_ms.disable_rw_split";s:18:"mysqlnd_mux.enable";s:18:"mysqlnd_mux.enable";s:20:"mysqlnd_qc.enable_qc";s:20:"mysqlnd_qc.enable_qc";s:14:"mysqlnd_qc.ttl";s:14:"mysqlnd_qc.ttl";s:27:"mysqlnd_qc.cache_by_default";s:27:"mysqlnd_qc.cache_by_default";s:25:"mysqlnd_qc.cache_no_table";s:25:"mysqlnd_qc.cache_no_table";s:27:"mysqlnd_qc.use_request_time";s:27:"mysqlnd_qc.use_request_time";s:26:"mysqlnd_qc.time_statistics";s:26:"mysqlnd_qc.time_statistics";s:29:"mysqlnd_qc.collect_statistics";s:29:"mysqlnd_qc.collect_statistics";s:38:"mysqlnd_qc.collect_statistics_log_file";s:38:"mysqlnd_qc.collect_statistics_log_file";s:30:"mysqlnd_qc.collect_query_trace";s:30:"mysqlnd_qc.collect_query_trace";s:31:"mysqlnd_qc.query_trace_bt_depth";s:31:"mysqlnd_qc.query_trace_bt_depth";s:41:"mysqlnd_qc.collect_normalized_query_trace";s:41:"mysqlnd_qc.collect_normalized_query_trace";s:30:"mysqlnd_qc.ignore_sql_comments";s:30:"mysqlnd_qc.ignore_sql_comments";s:23:"mysqlnd_qc.slam_defense";s:23:"mysqlnd_qc.slam_defense";s:27:"mysqlnd_qc.slam_defense_ttl";s:27:"mysqlnd_qc.slam_defense_ttl";s:24:"mysqlnd_qc.std_data_copy";s:24:"mysqlnd_qc.std_data_copy";s:21:"mysqlnd_qc.apc_prefix";s:21:"mysqlnd_qc.apc_prefix";s:22:"mysqlnd_qc.memc_server";s:22:"mysqlnd_qc.memc_server";s:20:"mysqlnd_qc.memc_port";s:20:"mysqlnd_qc.memc_port";s:27:"mysqlnd_qc.sqlite_data_file";s:27:"mysqlnd_qc.sqlite_data_file";s:17:"mysqlnd_uh.enable";s:17:"mysqlnd_uh.enable";s:29:"mysqlnd_uh.report_wrong_types";s:29:"mysqlnd_uh.report_wrong_types";s:18:"nsapi.read_timeout";s:18:"nsapi.read_timeout";s:21:"oci8.connection_class";s:21:"oci8.connection_class";s:21:"oci8.default_prefetch";s:21:"oci8.default_prefetch";s:11:"oci8.events";s:11:"oci8.events";s:19:"oci8.max_persistent";s:19:"oci8.max_persistent";s:28:"oci8.old_oci_close_semantics";s:28:"oci8.old_oci_close_semantics";s:23:"oci8.persistent_timeout";s:23:"oci8.persistent_timeout";s:18:"oci8.ping_interval";s:18:"oci8.ping_interval";s:23:"oci8.privileged_connect";s:23:"oci8.privileged_connect";s:25:"oci8.statement_cache_size";s:25:"oci8.statement_cache_size";s:21:"odbc.allow_persistent";s:21:"odbc.allow_persistent";s:21:"odbc.check_persistent";s:21:"odbc.check_persistent";s:19:"odbc.defaultbinmode";s:19:"odbc.defaultbinmode";s:15:"odbc.defaultlrl";s:15:"odbc.defaultlrl";s:15:"odbc.default_db";s:15:"odbc.default_db";s:23:"odbc.default_cursortype";s:23:"odbc.default_cursortype";s:15:"odbc.default_pw";s:15:"odbc.default_pw";s:17:"odbc.default_user";s:17:"odbc.default_user";s:14:"odbc.max_links";s:14:"odbc.max_links";s:19:"odbc.max_persistent";s:19:"odbc.max_persistent";s:21:"odbtp.datetime_format";s:21:"odbtp.datetime_format";s:28:"odbtp.detach_default_queries";s:28:"odbtp.detach_default_queries";s:17:"odbtp.guid_format";s:17:"odbtp.guid_format";s:20:"odbtp.interface_file";s:20:"odbtp.interface_file";s:23:"odbtp.truncation_errors";s:23:"odbtp.truncation_errors";s:31:"opendirectory.default_separator";s:31:"opendirectory.default_separator";s:22:"opendirectory.max_refs";s:22:"opendirectory.max_refs";s:23:"opendirectory.separator";s:23:"opendirectory.separator";s:12:"open_basedir";s:12:"open_basedir";s:23:"oracle.allow_persistent";s:23:"oracle.allow_persistent";s:16:"oracle.max_links";s:16:"oracle.max_links";s:21:"oracle.max_persistent";s:21:"oracle.max_persistent";s:16:"output_buffering";s:16:"output_buffering";s:14:"output_handler";s:14:"output_handler";s:15:"pam.servicename";s:15:"pam.servicename";s:20:"pcre.backtrack_limit";s:20:"pcre.backtrack_limit";s:20:"pcre.recursion_limit";s:20:"pcre.recursion_limit";s:27:"pdo_odbc.connection_pooling";s:27:"pdo_odbc.connection_pooling";s:26:"pdo_odbc.db2_instance_name";s:26:"pdo_odbc.db2_instance_name";s:17:"pfpro.defaulthost";s:17:"pfpro.defaulthost";s:17:"pfpro.defaultport";s:17:"pfpro.defaultport";s:20:"pfpro.defaulttimeout";s:20:"pfpro.defaulttimeout";s:18:"pfpro.proxyaddress";s:18:"pfpro.proxyaddress";s:16:"pfpro.proxylogon";s:16:"pfpro.proxylogon";s:19:"pfpro.proxypassword";s:19:"pfpro.proxypassword";s:15:"pfpro.proxyport";s:15:"pfpro.proxyport";s:22:"pgsql.allow_persistent";s:22:"pgsql.allow_persistent";s:27:"pgsql.auto_reset_persistent";s:27:"pgsql.auto_reset_persistent";s:19:"pgsql.ignore_notice";s:19:"pgsql.ignore_notice";s:16:"pgsql.log_notice";s:16:"pgsql.log_notice";s:15:"pgsql.max_links";s:15:"pgsql.max_links";s:20:"pgsql.max_persistent";s:20:"pgsql.max_persistent";s:15:"phar.cache_list";s:15:"phar.cache_list";s:17:"phar.extract_list";s:17:"phar.extract_list";s:13:"phar.readonly";s:13:"phar.readonly";s:17:"phar.require_hash";s:17:"phar.require_hash";s:24:"enable_post_data_reading";s:24:"enable_post_data_reading";s:13:"post_max_size";s:13:"post_max_size";s:9:"precision";s:9:"precision";s:23:"printer.default_printer";s:23:"printer.default_printer";s:18:"python.append_path";s:18:"python.append_path";s:19:"python.prepend_path";s:19:"python.prepend_path";s:19:"realpath_cache_size";s:19:"realpath_cache_size";s:18:"realpath_cache_ttl";s:18:"realpath_cache_ttl";s:18:"register_argc_argv";s:18:"register_argc_argv";s:16:"register_globals";s:16:"register_globals";s:20:"register_long_arrays";s:20:"register_long_arrays";s:15:"report_memleaks";s:15:"report_memleaks";s:17:"report_zend_debug";s:17:"report_zend_debug";s:13:"request_order";s:13:"request_order";s:24:"runkit.internal_override";s:24:"runkit.internal_override";s:18:"runkit.superglobal";s:18:"runkit.superglobal";s:9:"safe_mode";s:9:"safe_mode";s:26:"safe_mode_allowed_env_vars";s:26:"safe_mode_allowed_env_vars";s:18:"safe_mode_exec_dir";s:18:"safe_mode_exec_dir";s:13:"safe_mode_gid";s:13:"safe_mode_gid";s:21:"safe_mode_include_dir";s:21:"safe_mode_include_dir";s:28:"safe_mode_protected_env_vars";s:28:"safe_mode_protected_env_vars";s:13:"sendmail_from";s:13:"sendmail_from";s:13:"sendmail_path";s:13:"sendmail_path";s:19:"serialize_precision";s:19:"serialize_precision";s:18:"session.auto_start";s:18:"session.auto_start";s:21:"session.bug_compat_42";s:21:"session.bug_compat_42";s:23:"session.bug_compat_warn";s:23:"session.bug_compat_warn";s:20:"session.cache_expire";s:20:"session.cache_expire";s:21:"session.cache_limiter";s:21:"session.cache_limiter";s:21:"session.cookie_domain";s:21:"session.cookie_domain";s:23:"session.cookie_httponly";s:23:"session.cookie_httponly";s:23:"session.cookie_lifetime";s:23:"session.cookie_lifetime";s:19:"session.cookie_path";s:19:"session.cookie_path";s:21:"session.cookie_secure";s:21:"session.cookie_secure";s:20:"session.entropy_file";s:20:"session.entropy_file";s:22:"session.entropy_length";s:22:"session.entropy_length";s:19:"session.gc_dividend";s:19:"session.gc_dividend";s:18:"session.gc_divisor";s:18:"session.gc_divisor";s:22:"session.gc_maxlifetime";s:22:"session.gc_maxlifetime";s:22:"session.gc_probability";s:22:"session.gc_probability";s:31:"session.hash_bits_per_character";s:31:"session.hash_bits_per_character";s:21:"session.hash_function";s:21:"session.hash_function";s:12:"session.name";s:12:"session.name";s:21:"session.referer_check";s:21:"session.referer_check";s:20:"session.save_handler";s:20:"session.save_handler";s:17:"session.save_path";s:17:"session.save_path";s:25:"session.serialize_handler";s:25:"session.serialize_handler";s:31:"session.upload_progress.cleanup";s:31:"session.upload_progress.cleanup";s:31:"session.upload_progress.enabled";s:31:"session.upload_progress.enabled";s:28:"session.upload_progress.freq";s:28:"session.upload_progress.freq";s:32:"session.upload_progress.min_freq";s:32:"session.upload_progress.min_freq";s:28:"session.upload_progress.name";s:28:"session.upload_progress.name";s:30:"session.upload_progress.prefix";s:30:"session.upload_progress.prefix";s:23:"session.use_strict_mode";s:23:"session.use_strict_mode";s:19:"session.use_cookies";s:19:"session.use_cookies";s:24:"session.use_only_cookies";s:24:"session.use_only_cookies";s:21:"session.use_trans_sid";s:21:"session.use_trans_sid";s:26:"session_pgsql.create_table";s:26:"session_pgsql.create_table";s:16:"session_pgsql.db";s:16:"session_pgsql.db";s:21:"session_pgsql.disable";s:21:"session_pgsql.disable";s:27:"session_pgsql.failover_mode";s:27:"session_pgsql.failover_mode";s:25:"session_pgsql.gc_interval";s:25:"session_pgsql.gc_interval";s:26:"session_pgsql.keep_expired";s:26:"session_pgsql.keep_expired";s:27:"session_pgsql.sem_file_name";s:27:"session_pgsql.sem_file_name";s:26:"session_pgsql.serializable";s:26:"session_pgsql.serializable";s:27:"session_pgsql.short_circuit";s:27:"session_pgsql.short_circuit";s:26:"session_pgsql.use_app_vars";s:26:"session_pgsql.use_app_vars";s:29:"session_pgsql.vacuum_interval";s:29:"session_pgsql.vacuum_interval";s:14:"short_open_tag";s:14:"short_open_tag";s:15:"simple_cvs.host";s:15:"simple_cvs.host";s:9:"smtp_port";s:9:"smtp_port";s:15:"soap.wsdl_cache";s:15:"soap.wsdl_cache";s:19:"soap.wsdl_cache_dir";s:19:"soap.wsdl_cache_dir";s:23:"soap.wsdl_cache_enabled";s:23:"soap.wsdl_cache_enabled";s:21:"soap.wsdl_cache_limit";s:21:"soap.wsdl_cache_limit";s:19:"soap.wsdl_cache_ttl";s:19:"soap.wsdl_cache_ttl";s:13:"sql.safe_mode";s:13:"sql.safe_mode";s:17:"sqlite.assoc_case";s:17:"sqlite.assoc_case";s:21:"sqlite3.extension_dir";s:21:"sqlite3.extension_dir";s:23:"sybase.allow_persistent";s:23:"sybase.allow_persistent";s:15:"sybase.hostname";s:15:"sybase.hostname";s:21:"sybase.interface_file";s:21:"sybase.interface_file";s:20:"sybase.login_timeout";s:20:"sybase.login_timeout";s:16:"sybase.max_links";s:16:"sybase.max_links";s:21:"sybase.max_persistent";s:21:"sybase.max_persistent";s:26:"sybase.min_client_severity";s:26:"sybase.min_client_severity";s:25:"sybase.min_error_severity";s:25:"sybase.min_error_severity";s:27:"sybase.min_message_severity";s:27:"sybase.min_message_severity";s:26:"sybase.min_server_severity";s:26:"sybase.min_server_severity";s:14:"sybase.timeout";s:14:"sybase.timeout";s:22:"sybct.allow_persistent";s:22:"sybct.allow_persistent";s:26:"sybct.deadlock_retry_count";s:26:"sybct.deadlock_retry_count";s:14:"sybct.hostname";s:14:"sybct.hostname";s:19:"sybct.login_timeout";s:19:"sybct.login_timeout";s:15:"sybct.max_links";s:15:"sybct.max_links";s:20:"sybct.max_persistent";s:20:"sybct.max_persistent";s:25:"sybct.min_client_severity";s:25:"sybct.min_client_severity";s:25:"sybct.min_server_severity";s:25:"sybct.min_server_severity";s:17:"sybct.packet_size";s:17:"sybct.packet_size";s:13:"sybct.timeout";s:13:"sybct.timeout";s:12:"sys_temp_dir";s:12:"sys_temp_dir";s:16:"sysvshm.init_mem";s:16:"sysvshm.init_mem";s:17:"tidy.clean_output";s:17:"tidy.clean_output";s:19:"tidy.default_config";s:19:"tidy.default_config";s:12:"track_errors";s:12:"track_errors";s:10:"track_vars";s:10:"track_vars";s:25:"unserialize_callback_func";s:25:"unserialize_callback_func";s:37:"uploadprogress.file.filename_template";s:37:"uploadprogress.file.filename_template";s:19:"upload_max_filesize";s:19:"upload_max_filesize";s:16:"max_file_uploads";s:16:"max_file_uploads";s:14:"upload_tmp_dir";s:14:"upload_tmp_dir";s:17:"url_rewriter.tags";s:17:"url_rewriter.tags";s:10:"user_agent";s:10:"user_agent";s:8:"user_dir";s:8:"user_dir";s:18:"user_ini.cache_ttl";s:18:"user_ini.cache_ttl";s:17:"user_ini.filename";s:17:"user_ini.filename";s:22:"valkyrie.auto_validate";s:22:"valkyrie.auto_validate";s:20:"valkyrie.config_path";s:20:"valkyrie.config_path";s:15:"variables_order";s:15:"variables_order";s:17:"velocis.max_links";s:17:"velocis.max_links";s:10:"vld.active";s:10:"vld.active";s:11:"vld.execute";s:11:"vld.execute";s:15:"vld.skip_append";s:15:"vld.skip_append";s:16:"vld.skip_prepend";s:16:"vld.skip_prepend";s:24:"windows.show_crt_warning";s:24:"windows.show_crt_warning";s:24:"windows_show_crt_warning";s:24:"windows_show_crt_warning";s:8:"xbithack";s:8:"xbithack";s:19:"xdebug.auto_profile";s:19:"xdebug.auto_profile";s:24:"xdebug.auto_profile_mode";s:24:"xdebug.auto_profile_mode";s:17:"xdebug.auto_trace";s:17:"xdebug.auto_trace";s:23:"xdebug.collect_includes";s:23:"xdebug.collect_includes";s:21:"xdebug.collect_params";s:21:"xdebug.collect_params";s:21:"xdebug.collect_return";s:21:"xdebug.collect_return";s:19:"xdebug.collect_vars";s:19:"xdebug.collect_vars";s:21:"xdebug.default_enable";s:21:"xdebug.default_enable";s:19:"xdebug.dump_globals";s:19:"xdebug.dump_globals";s:16:"xdebug.dump_once";s:16:"xdebug.dump_once";s:21:"xdebug.dump_undefined";s:21:"xdebug.dump_undefined";s:20:"xdebug.extended_info";s:20:"xdebug.extended_info";s:13:"xdebug.idekey";s:13:"xdebug.idekey";s:17:"xdebug.manual_url";s:17:"xdebug.manual_url";s:24:"xdebug.max_nesting_level";s:24:"xdebug.max_nesting_level";s:17:"xdebug.output_dir";s:17:"xdebug.output_dir";s:25:"xdebug.profiler_aggregate";s:25:"xdebug.profiler_aggregate";s:22:"xdebug.profiler_append";s:22:"xdebug.profiler_append";s:22:"xdebug.profiler_enable";s:22:"xdebug.profiler_enable";s:30:"xdebug.profiler_enable_trigger";s:30:"xdebug.profiler_enable_trigger";s:26:"xdebug.profiler_output_dir";s:26:"xdebug.profiler_output_dir";s:27:"xdebug.profiler_output_name";s:27:"xdebug.profiler_output_name";s:23:"xdebug.remote_autostart";s:23:"xdebug.remote_autostart";s:20:"xdebug.remote_enable";s:20:"xdebug.remote_enable";s:21:"xdebug.remote_handler";s:21:"xdebug.remote_handler";s:18:"xdebug.remote_host";s:18:"xdebug.remote_host";s:17:"xdebug.remote_log";s:17:"xdebug.remote_log";s:18:"xdebug.remote_mode";s:18:"xdebug.remote_mode";s:18:"xdebug.remote_port";s:18:"xdebug.remote_port";s:27:"xdebug.show_exception_trace";s:27:"xdebug.show_exception_trace";s:22:"xdebug.show_local_vars";s:22:"xdebug.show_local_vars";s:21:"xdebug.show_mem_delta";s:21:"xdebug.show_mem_delta";s:19:"xdebug.trace_format";s:19:"xdebug.trace_format";s:20:"xdebug.trace_options";s:20:"xdebug.trace_options";s:23:"xdebug.trace_output_dir";s:23:"xdebug.trace_output_dir";s:24:"xdebug.trace_output_name";s:24:"xdebug.trace_output_name";s:31:"xdebug.var_display_max_children";s:31:"xdebug.var_display_max_children";s:27:"xdebug.var_display_max_data";s:27:"xdebug.var_display_max_data";s:28:"xdebug.var_display_max_depth";s:28:"xdebug.var_display_max_depth";s:13:"xmlrpc_errors";s:13:"xmlrpc_errors";s:19:"xmlrpc_error_number";s:19:"xmlrpc_error_number";s:9:"xmms.path";s:9:"xmms.path";s:12:"xmms.session";s:12:"xmms.session";s:18:"xsl.security_prefs";s:18:"xsl.security_prefs";s:14:"y2k_compliance";s:14:"y2k_compliance";s:21:"yami.response.timeout";s:21:"yami.response.timeout";s:13:"yaz.keepalive";s:13:"yaz.keepalive";s:12:"yaz.log_file";s:12:"yaz.log_file";s:12:"yaz.log_mask";s:12:"yaz.log_mask";s:13:"yaz.max_links";s:13:"yaz.max_links";s:19:"zend.detect_unicode";s:19:"zend.detect_unicode";s:14:"zend.enable_gc";s:14:"zend.enable_gc";s:14:"zend.multibyte";s:14:"zend.multibyte";s:20:"zend.script_encoding";s:20:"zend.script_encoding";s:17:"zend.signal_check";s:17:"zend.signal_check";s:27:"zend.ze1_compatibility_mode";s:27:"zend.ze1_compatibility_mode";s:14:"zend_extension";s:14:"zend_extension";s:20:"zend_extension_debug";s:20:"zend_extension_debug";s:23:"zend_extension_debug_ts";s:23:"zend_extension_debug_ts";s:17:"zend_extension_ts";s:17:"zend_extension_ts";s:23:"zlib.output_compression";s:23:"zlib.output_compression";s:29:"zlib.output_compression_level";s:29:"zlib.output_compression_level";s:19:"zlib.output_handler";s:19:"zlib.output_handler";}';
$array=unserialize($php_values);
$html="

	<div id='alias-animate-$t'></div>
	<div class=explain style='font-size:18px'>{freeweb_phpvalues_explain}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:22px'>{key}:</td>
		<td>". Field_array_Hash($array,"key-$t",null,"style:font-size:16px;padding:3px;width:420px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{value}:</td>
		<td>". Field_text("value-$t",null,"font-size:16px;width:420px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{add} {key}","FreeWebAddAlias$t()","26px")."</td>
	</tr>
	</table>
	
	<script>
		var x_FreeWebAddAlias$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			document.getElementById('alias-animate-$t').innerHTML='';
			$('#flexRT$t').flexReload();
		}

		function FreeWebAddAliasCheck$t(e){
			if(checkEnter(e)){FreeWebAddAlias$t();}
		
		}
		

		function FreeWebAddAlias$t(){
			var XHR = new XHRConnection();
			var key=document.getElementById('key-$t').value;
			if(key.length<1){return;}
			var value=document.getElementById('value-$t').value;
						
			if(value.length<1){return;}		
			XHR.appendData('key',document.getElementById('key-$t').value);
			XHR.appendData('value',value);
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('alias-animate-$t');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddAlias$t);			
		}
	</script>	
	
	
	";	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function item_save(){
	$_POST["key"]=trim(strtolower($_POST["key"]));
	
	$md5=md5("{$_POST["key"]}{$_POST["servername"]}");
	$sql="INSERT INTO freeweb_php (zmd5,`key`,`value`,servername) VALUES('$md5',
	'{$_POST["key"]}','{$_POST["value"]}','{$_POST["servername"]}')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");
	
}

function item_del(){
	
	$sql="DELETE FROM freeweb_php WHERE zmd5='{$_POST["item-del"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");	
}



function items_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$search='%';
	$table="freeweb_php";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" servername='{$_GET["servername"]}'";
	
	
	if(!$q->TABLE_EXISTS("freeweb_php", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `freeweb_php` (
			`zmd5`varchar(90) NOT NULL ,
			`servername` varchar(255) NOT NULL,
			`key` varchar(40),
			`value` varchar(255) NOT NULL,
			 KEY `key` (`key`),
			 PRIMARY KEY (`zmd5`)
			  ) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	
	if($q->COUNT_ROWS("freeweb_php",'artica_backup')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("No data...$sql",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$delete=imgsimple("delete-24.png","{delete}","itemDel$t('{$ligne["zmd5"]}')");
		
	$data['rows'][] = array(
		'id' => "{$ligne["zmd5"]}",
		'cell' => array(
			"<span style='font-size:22px;;font-family:Courier New;font-weight:bold'>{$ligne["key"]}</a></span>",
			"<span style='font-size:22px;;font-family:Courier New;font-weight:bold'>{$ligne["value"]}</a></span>",$delete
			)
		);
	}
	
	
echo json_encode($data);		

}
