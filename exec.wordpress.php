<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.index.progress";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

$GLOBALS["PREFIX"]="/usr/share/artica-postfix/bin/wp-cli.phar";
$GLOBALS["SUFFIX"]="--path=\"/usr/share/wordpress-src\" --allow-root --debug --no-color";



$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}
$GLOBALS["OUTPUT"]=true;
if($argv[1]=="--scan"){scan();exit;}
if($argv[1]=="--remove-info"){removeBlogInfo($argv[2]);exit;}

config($argv[1]);

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/freeweb.rebuild.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function config($servername){
	$GLOBALS["SERVICE_NAME"]="Wordpress $servername";
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$servername.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		build_progress("$servername Already executed",110);
		die();
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$q=new mysql();
	$cp=$unix->find_program("cp");
	$sock=new sockets();
	$Salts=null;
	$DB_HOST=$q->mysql_server;
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL host: $DB_HOST\n";}
	
	if(  ($q->mysql_server=="127.0.0.1") OR ($q->mysql_server=="localhost") OR ($q->mysql_server=="localhost:") ){
		if($q->SocketPath==null){$q->SocketPath="/var/run/mysqld/mysqld.sock";}
		$DB_HOST="localhost:$q->SocketPath";
	}
	
	if(!is_file("/usr/share/artica-postfix/bin/wp-cli.phar")){build_progress("wp-cli.phar: no such binary",110);return;}
	@chmod("/usr/share/artica-postfix/bin/wp-cli.phar",0755);
	build_progress("$servername: {testing_configuration}",40);
	
	$free=new freeweb($servername);
	$WORKING_DIRECTORY=$free->www_dir;
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Directory: $WORKING_DIRECTORY\n";}
	@unlink("$WORKING_DIRECTORY/wp-config.php");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Duplicate: $free->groupware_duplicate\n";}
	if($free->groupware_duplicate<>null){
		build_progress("$servername: {duplicate} {from} $free->groupware_duplicate",40);
		if(!duplicate_wordpress($servername)){
			build_progress("$servername: {installing} {failed}...",110);
			apache_admin_mysql(0, "Failed to duplicate $servername from $free->groupware_duplicate", null,__FILE__,__LINE__);
			return;
		}
		apache_admin_mysql(2, "Success duplicate $servername from $free->groupware_duplicate", null,__FILE__,__LINE__);
		$free=new freeweb($servername);
		
	}else{
		if(!scan($WORKING_DIRECTORY)){
			build_progress("$servername: {installing}...",42);
			@mkdir($WORKING_DIRECTORY);
			$cmd="$cp -rf /usr/share/wordpress-src/* $WORKING_DIRECTORY/\n";
			shell_exec($cmd);
			if(!scan($WORKING_DIRECTORY)){
				apache_admin_mysql(0, "Failed to install $servername from /usr/share/wordpress-src", null,__FILE__,__LINE__);
				build_progress("$servername: {installing} {failed}...",110);
				return;
			}
			apache_admin_mysql(2, "Success to install $servername from /usr/share/wordpress-src", null,__FILE__,__LINE__);
			
		}
	}
	
	$wordpressDB=$free->mysql_database;
	if($wordpressDB==null){
			$wordpressDB=$free->CreateDatabaseName();
			$free->mysql_database=$wordpressDB;
			$free->CreateSite(true);
	}
	$WordPressDBPass=$free->mysql_password;
	$DB_USER=$free->mysql_username;
	if($DB_USER=="wordpress"){$DB_USER=null;}
	if($DB_USER==null){
			$DB_USER="WP".time();
			$free->mysql_username=$DB_USER;
			$free->CreateSite(true);
	}
	if($WordPressDBPass==null){
		$WordPressDBPass=md5(time());
		$free->mysql_password=$WordPressDBPass;
		$free->CreateSite(true);
			
	}
	
	$DB_PASSWORD=$WordPressDBPass;
	
	if(is_file("$WORKING_DIRECTORY/salts.php")){
		$Salts=@file_get_contents("$WORKING_DIRECTORY/salts.php");
	}
	
	if($Salts==null){
		$TMP=$unix->FILE_TEMP();
		build_progress("$servername: Acquiring Salts...",44);
		$curl=new ccurl("https://api.wordpress.org/secret-key/1.1/salt/");
		if(!$curl->GetFile("$TMP")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Unable to download salts !!\n";}
			build_progress("$servername: Acquiring Salts {failed}...",110);
			return;
		}
		
		
		$ASASLT=false;
		$fa=explode("\n",@file_get_contents($TMP));
		@unlink($TMP);
		while (list ($num, $ligne) = each ($fa)){
			if(preg_match("#define\(#", $ligne)){$ASASLT=true;break;}
			
		}
				
		if(!$ASASLT){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Unable to download salts !!\n";}
			build_progress("$servername: Acquiring Salts {failed}...",110);
			return;
		}		
		
		@file_put_contents("$WORKING_DIRECTORY/salts.php", @implode("\n", $fa));
	}
	
	
	build_progress("$servername: checking...",48);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL host...........: \"$DB_HOST\"\n";}
	if(!$q->DATABASE_EXISTS($wordpressDB)){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Create MySQL database: \"$wordpressDB\"\n";}
		$q->CREATE_DATABASE($wordpressDB);
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL database.......: \"$wordpressDB\"\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL user...........: \"$DB_USER\"\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL Password.......: \"$DB_PASSWORD\"\n";}
	$q->PRIVILEGES($DB_USER,$WordPressDBPass,$wordpressDB);
	
	


	
$f[]="<?php";
$f[]=$Salts;
$f[]="/**";
$f[]=" * The base configurations of the WordPress.";
$f[]=" *";
$f[]=" * This file has the following configurations: MySQL settings, Table Prefix,";
$f[]=" * Secret Keys, WordPress Language, and ABSPATH. You can find more information";
$f[]=" * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing";
$f[]=" * wp-config.php} Codex page. You can get the MySQL settings from your web host.";
$f[]=" *";
$f[]=" * This file is used by the wp-config.php creation script during the";
$f[]=" * installation. You don't have to use the web site, you can just copy this file";
$f[]=" * to \"wp-config.php\" and fill in the values.";
$f[]=" *";
$f[]=" * @package WordPress";
$f[]=" */";
$f[]="";
$f[]="// ** MySQL settings - You can get this info from your web host ** //";
$f[]="/** The name of the database for WordPress */";
$f[]="define('DB_NAME', '$wordpressDB');";
$f[]="";
$f[]="/** MySQL database username */";
$f[]="define('DB_USER', '$DB_USER');";
$f[]="";
$f[]="/** MySQL database password */";
$f[]="define('DB_PASSWORD', '$DB_PASSWORD');";
$f[]="";
$f[]="/** MySQL hostname */";
$f[]="define('DB_HOST', '$DB_HOST');";
$f[]="";
$f[]="/** Database Charset to use in creating database tables. */";
$f[]="define('DB_CHARSET', 'utf8');";
$f[]="";
$f[]="/** The Database Collate type. Don't change this if in doubt. */";
$f[]="define('DB_COLLATE', '');";
$f[]="";
$f[]="/**#@+";
$f[]=" * Authentication Unique Keys and Salts.";
$f[]=" *";
$f[]=" * Change these to different unique phrases!";
$f[]=" * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}";
$f[]=" * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.";
$f[]=" *";
$f[]=" * @since 2.6.0";
$f[]=" */";

$f[]="";
$f[]="/**#@-*/";
$f[]="";
$f[]="/**";
$f[]=" * WordPress Database Table prefix.";
$f[]=" *";
$f[]=" * You can have multiple installations in one database if you give each a unique";
$f[]=" * prefix. Only numbers, letters, and underscores please!";
$f[]=" */";
$f[]="\$table_prefix  = 'wp_';";
$f[]="";
$f[]="/**";
$f[]=" * WordPress Localized Language, defaults to English.";
$f[]=" *";
$f[]=" * Change this to localize WordPress. A corresponding MO file for the chosen";
$f[]=" * language must be installed to wp-content/languages. For example, install";
$f[]=" * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German";
$f[]=" * language support.";
$f[]=" */";
$f[]="define('WPLANG', '');";
$f[]="";
$f[]="/**";
$f[]=" * For developers: WordPress debugging mode.";
$f[]=" *";
$f[]=" * Change this to true to enable the display of notices during development.";
$f[]=" * It is strongly recommended that plugin and theme developers use WP_DEBUG";
$f[]=" * in their development environments.";
$f[]=" */";
$f[]="define('WP_DEBUG', false);";
$f[]="";
$f[]="/* That's all, stop editing! Happy blogging. */";
$f[]="";
$f[]="/** Absolute path to the WordPress directory. */";
$f[]="if ( !defined('ABSPATH') )";
$f[]="	define('ABSPATH', dirname(__FILE__) . '/');";
$f[]="";
$f[]="/** Sets up WordPress vars and included files. */";
$f[]="require_once(ABSPATH . 'wp-settings.php');";
$f[]="?>";

@file_put_contents("$WORKING_DIRECTORY/wp-config.php", @implode("\n", $f));
build_progress("$servername: wp-config.php {done}...",50);

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $WORKING_DIRECTORY/wp-config.php done...\n";}

$f=array();
$f[]="<?php";
$f[]="/*";
$f[]="WP-Cache Config Sample File";
$f[]="";
$f[]="See wp-cache.php for author details.";
$f[]="*/";
$f[]="";
$f[]="if ( ! defined('WPCACHEHOME') )";
$f[]="	define( 'WPCACHEHOME', WP_PLUGIN_DIR . '/wp-super-cache/' );";
$f[]="";
$f[]="\$cache_compression = 0; // Super cache compression";
$f[]="\$cache_enabled = false;";
$f[]="\$super_cache_enabled = false;";
$f[]="\$cache_max_time = 3600; //in seconds";
$f[]="//\$use_flock = true; // Set it true or false if you know what to use";
$f[]="\$cache_path = WP_CONTENT_DIR . '/cache/';";
$f[]="\$file_prefix = 'wp-cache-';";
$f[]="\$ossdlcdn = 0;";
$f[]="";
$f[]="// Array of files that have 'wp-' but should still be cached";
$f[]="\$cache_acceptable_files = array( 'wp-comments-popup.php', 'wp-links-opml.php', 'wp-locations.php' );";
$f[]="";
$f[]="\$cache_rejected_uri = array('wp-.*\\.php', 'index\\.php');";
$f[]="\$cache_rejected_user_agent = array ( 0 => 'bot', 1 => 'ia_archive', 2 => 'slurp', 3 => 'crawl', 4 => 'spider', 5 => 'Yandex' );";
$f[]="";
$f[]="\$cache_rebuild_files = 1;";
$f[]="";
$f[]="// Disable the file locking system.";
$f[]="// If you are experiencing problems with clearing or creating cache files";
$f[]="// uncommenting this may help.";
$f[]="\$wp_cache_mutex_disabled = 1;";
$f[]="";
$f[]="// Just modify it if you have conflicts with semaphores";
$f[]="\$sem_id = 5419;";
$f[]="";
$f[]="if ( '/' != substr(\$cache_path, -1)) {";
$f[]="	\$cache_path .= '/';";
$f[]="}";
$f[]="";
$f[]="\$wp_cache_mobile = 0;";
$f[]="\$wp_cache_mobile_whitelist = 'Stand Alone/QNws';";
$f[]="\$wp_cache_mobile_browsers = 'Android, 2.0 MMP, 240x320, AvantGo, BlackBerry, Blazer, Cellphone, Danger, DoCoMo, Elaine/3.0, EudoraWeb, hiptop, IEMobile, iPhone, iPod, KYOCERA/WX310K, LG/U990, MIDP-2.0, MMEF20, MOT-V, NetFront, Newt, Nintendo Wii, Nitro, Nokia, Opera Mini, Palm, Playstation Portable, portalmmm, Proxinet, ProxiNet, SHARP-TQ-GX10, Small, SonyEricsson, Symbian OS, SymbianOS, TS21i-10, UP.Browser, UP.Link, Windows CE, WinWAP';";
$f[]="";
$f[]="// change to relocate the supercache plugins directory";
$f[]="\$wp_cache_plugins_dir = WPCACHEHOME . 'plugins';";
$f[]="// set to 1 to do garbage collection during normal process shutdown instead of wp-cron";
$f[]="\$wp_cache_shutdown_gc = 0;";
$f[]="\$wp_super_cache_late_init = 0;";
$f[]="";
$f[]="// uncomment the next line to enable advanced debugging features";
$f[]="\$wp_super_cache_advanced_debug = 0;";
$f[]="\$wp_super_cache_front_page_text = '';";
$f[]="\$wp_super_cache_front_page_clear = 0;";
$f[]="\$wp_super_cache_front_page_check = 0;";
$f[]="\$wp_super_cache_front_page_notification = '0';";
$f[]="";
$f[]="\$wp_cache_object_cache = 0;";
$f[]="\$wp_cache_anon_only = 0;";
$f[]="\$wp_supercache_cache_list = 0;";
$f[]="\$wp_cache_debug_to_file = 0;";
$f[]="\$wp_super_cache_debug = 0;";
$f[]="\$wp_cache_debug_level = 5;";
$f[]="\$wp_cache_debug_ip = '';";
$f[]="\$wp_cache_debug_log = '';";
$f[]="\$wp_cache_debug_email = '';";
$f[]="\$wp_cache_pages[ \"search\" ] = 0;";
$f[]="\$wp_cache_pages[ \"feed\" ] = 0;";
$f[]="\$wp_cache_pages[ \"category\" ] = 0;";
$f[]="\$wp_cache_pages[ \"home\" ] = 0;";
$f[]="\$wp_cache_pages[ \"frontpage\" ] = 0;";
$f[]="\$wp_cache_pages[ \"tag\" ] = 0;";
$f[]="\$wp_cache_pages[ \"archives\" ] = 0;";
$f[]="\$wp_cache_pages[ \"pages\" ] = 0;";
$f[]="\$wp_cache_pages[ \"single\" ] = 0;";
$f[]="\$wp_cache_pages[ \"author\" ] = 0;";
$f[]="\$wp_cache_hide_donation = 0;";
$f[]="\$wp_cache_not_logged_in = 0;";
$f[]="\$wp_cache_clear_on_post_edit = 0;";
$f[]="\$wp_cache_hello_world = 0;";
$f[]="\$wp_cache_mobile_enabled = 0;";
$f[]="\$wp_cache_cron_check = 0;";
$f[]="?>";
if(is_file("$WORKING_DIRECTORY/wp-content/plugins/wp-super-cache/wp-cache-config-sample.php")){ 
	@file_put_contents("$WORKING_DIRECTORY/wp-content/plugins/wp-super-cache/wp-cache-config.php",@implode("\n", $f));
}


build_progress("$servername: wp-config.php {done}...",50);
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $WORKING_DIRECTORY/wp-config.php done...\n";}


if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Testing configuration...\n";}

if($free->groupware_admin==null){
	$ldap=new clladp();
	$free->groupware_admin=$ldap->ldap_admin;
	$free->groupware_password=$ldap->ldap_password;
}

$admin=$unix->shellEscapeChars($free->groupware_admin);
$password=$unix->shellEscapeChars($free->groupware_password);
$WORKING_DIRECTORY_CMDLINE=$unix->shellEscapeChars($WORKING_DIRECTORY);
$cmd=array();
$cmd[]="/usr/share/artica-postfix/bin/wp-cli.phar core install";
$cmd[]="--url=\"$servername\"";
$cmd[]="--title=\"$servername\"";
$cmd[]="--admin_user=$admin";
$cmd[]="--admin_password=$password";
$cmd[]="--admin_email=$admin@$servername"; 
$cmd[]="--path=$WORKING_DIRECTORY_CMDLINE";
$cmd[]="--allow-root --debug --no-color 2>&1";
$cmdline=@implode(" ", $cmd);
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $cmdline\n";}
build_progress("$servername: {install_wordpress} {please_wait} !...",51);
exec($cmdline,$results1);
while (list ($num, $ligne) = each ($results1) ){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $ligne\n";}
}

build_progress("$servername: {enforce_security}",52);
secure_wp($WORKING_DIRECTORY);

build_progress("$servername: {directory_size}",53);
$size=$unix->DIRSIZE_BYTES($free->WORKING_DIRECTORY);
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $free->WORKING_DIRECTORY {$size}Bytes\n";}
$q->QUERY_SQL("UPDATE freeweb SET DirectorySize=$size WHERE servername='$servername'","artica_backup");
if(!$q->ok){system_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"freewebs");}

}

function secure_wp($maindir){
	
	$ToDelete["wp-admin/import.php"]=true;
	$ToDelete["wp-admin/install.php"]=true;
	$ToDelete["wp-admin/install-helper.php"]=true;
	$ToDelete["wp-admin/upgrade.php"]=true;
	$ToDelete["wp-admin/upgrade-functions.php"]=true;
	$ToDelete["readme.html"]=true;
	$ToDelete["license.txt"]=true;
	
	while (list ($filename,$none) = each ($ToDelete) ){
		if(!is_file("$maindir/$filename")){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $filename\n";}
		@unlink("$maindir/$filename");
	
	}
	
	removeBlogInfo($maindir);
	
}


function scan($maindir){
	

	

$f['wp-admin/admin-footer.php'] = True;
$f['wp-admin/options.php'] = True;
$f['wp-admin/ms-upgrade-network.php'] = True;
$f['wp-admin/user-edit.php'] = True;
$f['wp-admin/ms-admin.php'] = True;
$f['wp-admin/network/freedoms.php'] = True;
$f['wp-admin/network/plugins.php'] = True;
$f['wp-admin/network/site-settings.php'] = True;
$f['wp-admin/network/settings.php'] = True;
$f['wp-admin/network/user-edit.php'] = True;
$f['wp-admin/network/user-new.php'] = True;
$f['wp-admin/network/index.php'] = True;
$f['wp-admin/network/admin.php'] = True;
$f['wp-admin/network/theme-editor.php'] = True;
$f['wp-admin/network/edit.php'] = True;
$f['wp-admin/network/site-new.php'] = True;
$f['wp-admin/network/credits.php'] = True;
$f['wp-admin/network/plugin-editor.php'] = True;
$f['wp-admin/network/theme-install.php'] = True;
$f['wp-admin/network/themes.php'] = True;
$f['wp-admin/network/site-info.php'] = True;
$f['wp-admin/network/users.php'] = True;
$f['wp-admin/network/menu.php'] = True;
$f['wp-admin/network/about.php'] = True;
$f['wp-admin/network/update-core.php'] = True;
$f['wp-admin/network/profile.php'] = True;
$f['wp-admin/network/site-themes.php'] = True;
$f['wp-admin/network/setup.php'] = True;
$f['wp-admin/network/upgrade.php'] = True;
$f['wp-admin/network/sites.php'] = True;
$f['wp-admin/network/update.php'] = True;
$f['wp-admin/network/site-users.php'] = True;
$f['wp-admin/network/plugin-install.php'] = True;
$f['wp-admin/user-new.php'] = True;
$f['wp-admin/index.php'] = True;
$f['wp-admin/admin.php'] = True;
$f['wp-admin/edit-link-form.php'] = True;
$f['wp-admin/tools.php'] = True;
$f['wp-admin/theme-editor.php'] = True;
$f['wp-admin/edit.php'] = True;
$f['wp-admin/ms-sites.php'] = True;
$f['wp-admin/ms-themes.php'] = True;
$f['wp-admin/link.php'] = True;
$f['wp-admin/custom-background.php'] = True;
$f['wp-admin/edit-comments.php'] = True;
$f['wp-admin/network.php'] = True;
$f['wp-admin/edit-form-comment.php'] = True;
$f['wp-admin/ms-users.php'] = True;
$f['wp-admin/options-permalink.php'] = True;
$f['wp-admin/admin-header.php'] = True;
$f['wp-admin/options-general.php'] = True;
$f['wp-admin/my-sites.php'] = True;
$f['wp-admin/credits.php'] = True;
$f['wp-admin/options-head.php'] = True;
$f['wp-admin/media.php'] = True;
$f['wp-admin/admin-functions.php'] = True;
$f['wp-admin/edit-form-advanced.php'] = True;
$f['wp-admin/plugin-editor.php'] = True;
$f['wp-admin/link-parse-opml.php'] = True;
$f['wp-admin/revision.php'] = True;
$f['wp-admin/theme-install.php'] = True;
$f['wp-admin/load-styles.php'] = True;
$f['wp-admin/themes.php'] = True;
$f['wp-admin/comment.php'] = True;
$f['wp-admin/media-upload.php'] = True;
$f['wp-admin/export.php'] = True;
$f['wp-admin/widgets.php'] = True;
$f['wp-admin/media-new.php'] = True;
$f['wp-admin/users.php'] = True;
$f['wp-admin/menu-header.php'] = True;
$f['wp-admin/menu.php'] = True;
$f['wp-admin/ms-delete-site.php'] = True;
$f['wp-admin/moderation.php'] = True;
$f['wp-admin/ms-options.php'] = True;
$f['wp-admin/user/freedoms.php'] = True;
$f['wp-admin/user/user-edit.php'] = True;
$f['wp-admin/user/index.php'] = True;
$f['wp-admin/user/admin.php'] = True;
$f['wp-admin/user/credits.php'] = True;
$f['wp-admin/user/menu.php'] = True;
$f['wp-admin/user/about.php'] = True;
$f['wp-admin/user/profile.php'] = True;
$f['wp-admin/post.php'] = True;
$f['wp-admin/about.php'] = True;
$f['wp-admin/options-discussion.php'] = True;
$f['wp-admin/link-add.php'] = True;
$f['wp-admin/admin-ajax.php'] = True;
$f['wp-admin/update-core.php'] = True;
$f['wp-admin/admin-post.php'] = True;
$f['wp-admin/profile.php'] = True;
$f['wp-admin/ms-edit.php'] = True;
$f['wp-admin/maint/repair.php'] = True;
$f['wp-admin/load-scripts.php'] = True;
$f['wp-admin/options-reading.php'] = True;
$f['wp-admin/link-manager.php'] = True;
$f['wp-admin/edit-tags.php'] = True;
$f['wp-admin/nav-menus.php'] = True;
$f['wp-admin/edit-tag-form.php'] = True;
$f['wp-admin/upload.php'] = True;
$f['wp-admin/press-this.php'] = True;
$f['wp-admin/customize.php'] = True;
$f['wp-admin/setup-config.php'] = True;
$f['wp-admin/options-writing.php'] = True;
$f['wp-admin/update.php'] = True;
$f['wp-admin/plugin-install.php'] = True;
$f['wp-admin/includes/class-wp-plugins-list-table.php'] = True;
$f['wp-admin/includes/class-wp-themes-list-table.php'] = True;
$f['wp-admin/includes/class-wp-theme-install-list-table.php'] = True;
$f['wp-admin/includes/plugin.php'] = True;
$f['wp-admin/includes/user.php'] = True;
$f['wp-admin/includes/ms-deprecated.php'] = True;
$f['wp-admin/includes/class-wp-ms-sites-list-table.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-ftpsockets.php'] = True;
$f['wp-admin/includes/import.php'] = True;
$f['wp-admin/includes/admin.php'] = True;
$f['wp-admin/includes/class-wp-posts-list-table.php'] = True;
$f['wp-admin/includes/file.php'] = True;
$f['wp-admin/includes/image.php'] = True;
$f['wp-admin/includes/bookmark.php'] = True;
$f['wp-admin/includes/class-ftp-sockets.php'] = True;
$f['wp-admin/includes/ms.php'] = True;
$f['wp-admin/includes/template.php'] = True;
$f['wp-admin/includes/class-wp-ms-themes-list-table.php'] = True;
$f['wp-admin/includes/class-wp-upgrader.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-direct.php'] = True;
$f['wp-admin/includes/continents-cities.php'] = True;
$f['wp-admin/includes/media.php'] = True;
$f['wp-admin/includes/meta-boxes.php'] = True;
$f['wp-admin/includes/class-ftp.php'] = True;
$f['wp-admin/includes/schema.php'] = True;
$f['wp-admin/includes/revision.php'] = True;
$f['wp-admin/includes/theme-install.php'] = True;
$f['wp-admin/includes/class-wp-importer.php'] = True;
$f['wp-admin/includes/class-wp-plugin-install-list-table.php'] = True;
$f['wp-admin/includes/comment.php'] = True;
$f['wp-admin/includes/taxonomy.php'] = True;
$f['wp-admin/includes/export.php'] = True;
$f['wp-admin/includes/class-wp-upgrader-skins.php'] = True;
$f['wp-admin/includes/class-wp-links-list-table.php'] = True;
$f['wp-admin/includes/theme.php'] = True;
$f['wp-admin/includes/widgets.php'] = True;
$f['wp-admin/includes/ajax-actions.php'] = True;
$f['wp-admin/includes/class-ftp-pure.php'] = True;
$f['wp-admin/includes/menu.php'] = True;
$f['wp-admin/includes/class-wp-media-list-table.php'] = True;
$f['wp-admin/includes/class-pclzip.php'] = True;
$f['wp-admin/includes/class-wp-ms-users-list-table.php'] = True;
$f['wp-admin/includes/post.php'] = True;
$f['wp-admin/includes/misc.php'] = True;
$f['wp-admin/includes/update-core.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-base.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-ftpext.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-ssh2.php'] = True;
$f['wp-admin/includes/deprecated.php'] = True;
$f['wp-admin/includes/nav-menu.php'] = True;
$f['wp-admin/includes/upgrade.php'] = True;
$f['wp-admin/includes/class-wp-list-table.php'] = True;
$f['wp-admin/includes/class-wp-terms-list-table.php'] = True;
$f['wp-admin/includes/image-edit.php'] = True;
$f['wp-admin/includes/list-table.php'] = True;
$f['wp-admin/includes/screen.php'] = True;
$f['wp-admin/includes/class-wp-comments-list-table.php'] = True;
$f['wp-admin/includes/dashboard.php'] = True;
$f['wp-admin/includes/update.php'] = True;
$f['wp-admin/includes/plugin-install.php'] = True;
$f['wp-admin/includes/class-wp-users-list-table.php'] = True;
$f['wp-admin/custom-header.php'] = True;
$f['wp-links-opml.php'] = True;
$f['index.php'] = True;
$f['wp-cron.php'] = True;
$f['wp-activate.php'] = True;
$f['wp-load.php'] = True;
$f['wp-signup.php'] = True;
$f['wp-login.php'] = True;
$f['wp-content/index.php'] = True;
$f['wp-content/plugins/index.php'] = True;
$f['wp-content/plugins/akismet/index.php'] = True;
$f['wp-content/plugins/akismet/class.akismet.php'] = True;
$f['wp-content/plugins/akismet/views/get.php'] = True;
$f['wp-content/plugins/akismet/views/notice.php'] = True;
$f['wp-content/plugins/akismet/views/config.php'] = True;
$f['wp-content/plugins/akismet/views/start.php'] = True;
$f['wp-content/plugins/akismet/views/strict.php'] = True;
$f['wp-content/plugins/akismet/views/stats.php'] = True;
$f['wp-content/plugins/akismet/class.akismet-widget.php'] = True;
$f['wp-content/plugins/akismet/class.akismet-admin.php'] = True;
$f['wp-content/plugins/akismet/wrapper.php'] = True;
$f['wp-content/plugins/akismet/akismet.php'] = True;
$f['wp-content/plugins/hello.php'] = True;
$f['wp-content/themes/index.php'] = True;
$f['xmlrpc.php'] = True;
$f['wp-includes/plugin.php'] = True;
$f['wp-includes/class-wp-customize-manager.php'] = True;
$f['wp-includes/user.php'] = True;
$f['wp-includes/wp-diff.php'] = True;
$f['wp-includes/class-wp.php'] = True;
$f['wp-includes/vars.php'] = True;
$f['wp-includes/class-feed.php'] = True;
$f['wp-includes/ms-deprecated.php'] = True;
$f['wp-includes/feed-rss2-comments.php'] = True;
$f['wp-includes/pluggable-deprecated.php'] = True;
$f['wp-includes/post-template.php'] = True;
$f['wp-includes/class-oembed.php'] = True;
$f['wp-includes/cron.php'] = True;
$f['wp-includes/class-wp-admin-bar.php'] = True;
$f['wp-includes/feed-atom.php'] = True;
$f['wp-includes/theme-compat/sidebar.php'] = True;
$f['wp-includes/theme-compat/comments.php'] = True;
$f['wp-includes/theme-compat/header.php'] = True;
$f['wp-includes/theme-compat/footer.php'] = True;
$f['wp-includes/theme-compat/comments-popup.php'] = True;
$f['wp-includes/author-template.php'] = True;
$f['wp-includes/script-loader.php'] = True;
$f['wp-includes/feed-atom-comments.php'] = True;
$f['wp-includes/category-template.php'] = True;
$f['wp-includes/canonical.php'] = True;
$f['wp-includes/feed-rss.php'] = True;
$f['wp-includes/class.wp-scripts.php'] = True;
$f['wp-includes/template-loader.php'] = True;
$f['wp-includes/load.php'] = True;
$f['wp-includes/functions.wp-scripts.php'] = True;
$f['wp-includes/class.wp-styles.php'] = True;
$f['wp-includes/ms-settings.php'] = True;
$f['wp-includes/post-formats.php'] = True;
$f['wp-includes/class-wp-http-ixr-client.php'] = True;
$f['wp-includes/class-wp-walker.php'] = True;
$f['wp-includes/class-json.php'] = True;
$f['wp-includes/class-wp-ajax-response.php'] = True;
$f['wp-includes/meta.php'] = True;
$f['wp-includes/class-wp-image-editor-gd.php'] = True;
$f['wp-includes/atomlib.php'] = True;
$f['wp-includes/general-template.php'] = True;
$f['wp-includes/bookmark-template.php'] = True;
$f['wp-includes/bookmark.php'] = True;
$f['wp-includes/rss-functions.php'] = True;
$f['wp-includes/class-simplepie.php'] = True;
$f['wp-includes/nav-menu-template.php'] = True;
$f['wp-includes/template.php'] = True;
$f['wp-includes/admin-bar.php'] = True;
$f['wp-includes/link-template.php'] = True;
$f['wp-includes/class-pop3.php'] = True;
$f['wp-includes/date.php'] = True;
$f['wp-includes/pluggable.php'] = True;
$f['wp-includes/media.php'] = True;
$f['wp-includes/pomo/entry.php'] = True;
$f['wp-includes/pomo/po.php'] = True;
$f['wp-includes/pomo/mo.php'] = True;
$f['wp-includes/pomo/translations.php'] = True;
$f['wp-includes/pomo/streams.php'] = True;
$f['wp-includes/js/tinymce/wp-tinymce.php'] = True;
$f['wp-includes/revision.php'] = True;
$f['wp-includes/compat.php'] = True;
$f['wp-includes/functions.php'] = True;
$f['wp-includes/class-wp-customize-section.php'] = True;
$f['wp-includes/comment.php'] = True;
$f['wp-includes/taxonomy.php'] = True;
$f['wp-includes/formatting.php'] = True;
$f['wp-includes/registration-functions.php'] = True;
$f['wp-includes/default-constants.php'] = True;
$f['wp-includes/class-smtp.php'] = True;
$f['wp-includes/http.php'] = True;
$f['wp-includes/theme.php'] = True;
$f['wp-includes/version.php'] = True;
$f['wp-includes/locale.php'] = True;
$f['wp-includes/class-wp-customize-widgets.php'] = True;
$f['wp-includes/widgets.php'] = True;
$f['wp-includes/category.php'] = True;
$f['wp-includes/class-wp-embed.php'] = True;
$f['wp-includes/rewrite.php'] = True;
$f['wp-includes/class-wp-customize-control.php'] = True;
$f['wp-includes/class-wp-error.php'] = True;
$f['wp-includes/kses.php'] = True;
$f['wp-includes/post-thumbnail-template.php'] = True;
$f['wp-includes/rss.php'] = True;
$f['wp-includes/class-wp-customize-setting.php'] = True;
$f['wp-includes/feed.php'] = True;
$f['wp-includes/query.php'] = True;
$f['wp-includes/l10n.php'] = True;
$f['wp-includes/ID3/module.audio-video.asf.php'] = True;
$f['wp-includes/ID3/module.audio.ogg.php'] = True;
$f['wp-includes/ID3/module.tag.lyrics3.php'] = True;
$f['wp-includes/ID3/module.tag.id3v2.php'] = True;
$f['wp-includes/ID3/module.audio.flac.php'] = True;
$f['wp-includes/ID3/module.audio-video.quicktime.php'] = True;
$f['wp-includes/ID3/module.audio.mp3.php'] = True;
$f['wp-includes/ID3/module.tag.id3v1.php'] = True;
$f['wp-includes/ID3/module.audio-video.matroska.php'] = True;
$f['wp-includes/ID3/module.audio-video.flv.php'] = True;
$f['wp-includes/ID3/getid3.lib.php'] = True;
$f['wp-includes/ID3/module.tag.apetag.php'] = True;
$f['wp-includes/ID3/module.audio.ac3.php'] = True;
$f['wp-includes/ID3/module.audio.dts.php'] = True;
$f['wp-includes/ID3/module.audio-video.riff.php'] = True;
$f['wp-includes/ID3/getid3.php'] = True;
$f['wp-includes/default-filters.php'] = True;
$f['wp-includes/class.wp-dependencies.php'] = True;
$f['wp-includes/post.php'] = True;
$f['wp-includes/ms-functions.php'] = True;
$f['wp-includes/capabilities.php'] = True;
$f['wp-includes/class-wp-image-editor.php'] = True;
$f['wp-includes/class-IXR.php'] = True;
$f['wp-includes/cache.php'] = True;
$f['wp-includes/feed-rdf.php'] = True;
$f['wp-includes/media-template.php'] = True;
$f['wp-includes/wp-db.php'] = True;
$f['wp-includes/option.php'] = True;
$f['wp-includes/class-phpass.php'] = True;
$f['wp-includes/shortcodes.php'] = True;
$f['wp-includes/deprecated.php'] = True;
$f['wp-includes/ms-blogs.php'] = True;
$f['wp-includes/class-wp-image-editor-imagick.php'] = True;
$f['wp-includes/nav-menu.php'] = True;
$f['wp-includes/ms-default-constants.php'] = True;
$f['wp-includes/class-wp-theme.php'] = True;
$f['wp-includes/functions.wp-styles.php'] = True;
$f['wp-includes/class-wp-editor.php'] = True;
$f['wp-includes/ms-default-filters.php'] = True;
$f['wp-includes/SimplePie/XML/Declaration/Parser.php'] = True;
$f['wp-includes/SimplePie/Misc.php'] = True;
$f['wp-includes/SimplePie/Credit.php'] = True;
$f['wp-includes/SimplePie/Sanitize.php'] = True;
$f['wp-includes/SimplePie/HTTP/Parser.php'] = True;
$f['wp-includes/SimplePie/Net/IPv6.php'] = True;
$f['wp-includes/SimplePie/Parser.php'] = True;
$f['wp-includes/SimplePie/gzdecode.php'] = True;
$f['wp-includes/SimplePie/Author.php'] = True;
$f['wp-includes/SimplePie/Caption.php'] = True;
$f['wp-includes/SimplePie/Exception.php'] = True;
$f['wp-includes/SimplePie/Core.php'] = True;
$f['wp-includes/SimplePie/Decode/HTML/Entities.php'] = True;
$f['wp-includes/SimplePie/Cache/DB.php'] = True;
$f['wp-includes/SimplePie/Cache/Base.php'] = True;
$f['wp-includes/SimplePie/Cache/File.php'] = True;
$f['wp-includes/SimplePie/Cache/Memcache.php'] = True;
$f['wp-includes/SimplePie/Cache/MySQL.php'] = True;
$f['wp-includes/SimplePie/Registry.php'] = True;
$f['wp-includes/SimplePie/Item.php'] = True;
$f['wp-includes/SimplePie/File.php'] = True;
$f['wp-includes/SimplePie/Parse/Date.php'] = True;
$f['wp-includes/SimplePie/Category.php'] = True;
$f['wp-includes/SimplePie/IRI.php'] = True;
$f['wp-includes/SimplePie/Locator.php'] = True;
$f['wp-includes/SimplePie/Restriction.php'] = True;
$f['wp-includes/SimplePie/Enclosure.php'] = True;
$f['wp-includes/SimplePie/Source.php'] = True;
$f['wp-includes/SimplePie/Copyright.php'] = True;
$f['wp-includes/SimplePie/Cache.php'] = True;
$f['wp-includes/SimplePie/Rating.php'] = True;
$f['wp-includes/SimplePie/Content/Type/Sniffer.php'] = True;
$f['wp-includes/ms-files.php'] = True;
$f['wp-includes/class-phpmailer.php'] = True;
$f['wp-includes/class-http.php'] = True;
$f['wp-includes/registration.php'] = True;
$f['wp-includes/comment-template.php'] = True;
$f['wp-includes/Text/Diff/Engine/xdiff.php'] = True;
$f['wp-includes/Text/Diff/Engine/string.php'] = True;
$f['wp-includes/Text/Diff/Engine/native.php'] = True;
$f['wp-includes/Text/Diff/Engine/shell.php'] = True;
$f['wp-includes/Text/Diff/Renderer.php'] = True;
$f['wp-includes/Text/Diff/Renderer/inline.php'] = True;
$f['wp-includes/Text/Diff.php'] = True;
$f['wp-includes/feed-rss2.php'] = True;
$f['wp-includes/update.php'] = True;
$f['wp-includes/default-widgets.php'] = True;
$f['wp-includes/class-wp-xmlrpc-server.php'] = True;
$f['wp-includes/class-snoopy.php'] = True;
$f['wp-includes/ms-load.php'] = True;
$f['wp-mail.php'] = True;
$f['wp-settings.php'] = True;
$f['wp-blog-header.php'] = True;
	

	
	while (list ($filename,$none) = each ($f) ){
		if(!is_file("$maindir/$filename")){
			echo "$maindir/$filename - No such file\n";
			return false;}
		
	}
	
	return true;
	
}

function removeBlogInfo($maindir){
	
	$files["wp-includes/general-template.php"]=true;
	
	while (list ($filename,$none) = each ($files) ){
		if(!is_file("$maindir/$filename")){continue;}
		
		
		$REP=false;
		$f=array();
		$f=explode("\n",@file_get_contents("$maindir/$filename"));
		
		while (list ($num,$line) = each ($f) ){
			if(preg_match("#generator.*?get_bloginfo\((.*?)\)#", $line,$re)){
				$token=$re[1];
				$token=str_replace("'", "", $token);
				$token=str_replace('"', "", $token);
				if(trim(strtolower($token))<>"version"){continue;}
				$line=str_replace("get_bloginfo({$re[1]})", '"0.0.0"', $line);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $maindir/$filename, remove blog_info line $num\n";}
				$f[$num]=$line;$REP=true;
					
				
			}
		}
		
		if($REP){@file_put_contents("$maindir/$filename", @implode("\n", $f)); }
		
		
	
	}
	
}

function duplicate_wordpress($servername){
	$unix=new unix();
	$q=new mysql();
	$free=new freeweb($servername);
	$WORKING_DIRECTORY=$free->www_dir;
	if($free->groupware_duplicate==null){
		build_progress("$servername: {duplicate} $servername no duplicate set...",42);
		sleep(2);
		return false;
	}
	
	$free2=new freeweb($free->groupware_duplicate);
	if($free2->mysql_database==null){
		echo "Fatal: $free->groupware_duplicate did not have any such DB set, try to find it..\n";
		$free2->mysql_database=$free2->CreateDatabaseName();
		echo "Fatal: $free->groupware_duplicate = $free2->mysql_database\n";
	}
	
	if(!$q->DATABASE_EXISTS($free2->mysql_database,true)){
		build_progress("$servername: {duplicate} $free->groupware_duplicate did not have any database...",42);
		sleep(2);
		return false;
		
	}
	
	
	$srcdir=$free2->www_dir;
	$Mysqlpassword=null;
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	
	if(@is_link($WORKING_DIRECTORY)){$WORKING_DIRECTORY=@readlink($WORKING_DIRECTORY);}
	if(is_dir($WORKING_DIRECTORY)){
		build_progress("$servername: {removing} $WORKING_DIRECTORY...",42);
		sleep(2);
		shell_exec("$rm -rf $WORKING_DIRECTORY/*");
		
	}
	
	
	@mkdir($WORKING_DIRECTORY,0755,true);
	build_progress("$servername: {installing} {from} $srcdir...",42);
	
	build_progress("$servername: Copy from $srcdir",42);
	
	build_progress("$servername: Copy To $WORKING_DIRECTORY",42);
	shell_exec("$cp -rfv $srcdir/* $WORKING_DIRECTORY/");
	$wordpressDB=$free->mysql_database;
	
	build_progress("$servername: {creating_databases}...",42);
	if($wordpressDB==null){$wordpressDB=$free->CreateDatabaseName();}
	
	
	if($q->DATABASE_EXISTS($wordpressDB)){
		build_progress("$servername: {remove_database} $wordpressDB...",42);
		sleep(2);
		if(!$q->DELETE_DATABASE($wordpressDB)){
			build_progress("$servername: {remove_database} $wordpressDB {failed}...",42);
			return false;
		}
		if(!$q->CREATE_DATABASE($wordpressDB,true)){
			build_progress("$servername: {create_database} $wordpressDB {failed}...",42);
			return false;
		}
	}
	
	if(!$q->DATABASE_EXISTS($wordpressDB)){
		if(!$q->CREATE_DATABASE($wordpressDB,true)){
		build_progress("$servername: {create_database} $wordpressDB {failed}...",42);
		return false;
		}
	}
	build_progress("$servername: {backup_database} $free2->mysql_database...",42);
	$mysqldump=$unix->find_program("mysqldump");
	$q=new mysql();
	if($q->mysql_password<>null){$Mysqlpassword=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$t=time();
	$TMP_FILE=$unix->FILE_TEMP();
	$cmdline=trim("$mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$Mysqlpassword $free2->mysql_database >$TMP_FILE 2>&1");
	if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
	$results=array();
	exec($cmdline,$results);
	while (list ($num, $ligne) = each ($results)){
		echo "$ligne\n";
		if(preg_match("#ERROR\s+([0-9]+)#", $ligne)){
		build_progress("$servername: {restore_database} {to} $wordpressDB {failed}..",42);
		sleep(3);
		return false;
		}
	}
	
	
	build_progress("$servername: {restore_database} {to} $wordpressDB..",42);
	$mysqlbin=$unix->find_program("mysql");
	$cmd="$mysqlbin --batch --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$Mysqlpassword --database=$wordpressDB <$TMP_FILE 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	
	while (list ($num, $ligne) = each ($results)){
		echo "$ligne\n";
		if(preg_match("#ERROR\s+([0-9]+)#", $ligne)){
			build_progress("$servername: {restore_database} {to} $wordpressDB {failed}..",42);
			sleep(3);
			return false;
		}
	}
	
	
	build_progress("$servername: {restore_database} {to} $wordpressDB..{done}",42);
	@unlink($TMP_FILE);
	if(!scan($WORKING_DIRECTORY)){
		build_progress("$servername: {install} {failed}",42);
		sleep(3);
		return false;
	}
	
	$proto="http";
	if($free->useSSL==1){$proto="https";}
	$sql="UPDATE `wp_options` SET `option_value`='$proto://$servername' WHERE `option_name`='siteurl'";
	$q->QUERY_SQL($sql,$wordpressDB);
	if(!$q->ok){echo $q->mysql_error;build_progress("$servername: {install} {failed}",42);sleep(3);return false;}
	
	$sql="UPDATE `wp_options` SET `option_value`='$proto://$servername' WHERE `option_name`='home'";
	$q->QUERY_SQL($sql,$wordpressDB);
	if(!$q->ok){echo $q->mysql_error;build_progress("$servername: {install} {failed}",42);sleep(3);return false;}
	
	$free->groupware_duplicate=null;
	$free->CreateSite(true);
	
	return true;
}


?>
