<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ccurl.inc');
include_once("ressources/class.compile.ufdbguard.expressions.inc");


page();
if(isset($_GET["checks"])){check_js();exit;}
if(isset($_POST["checks"])){check();exit;}


function check_js(){
	header("content-type: application/x-javascript");
	$website=$_GET["checks"];
	$category=$_GET["category"];
	$mdd5=$_GET["md5"];
	$t=time();
	$page=CurrentPageName();
	
	echo "
	var xSave$t=function(obj){
		var results=obj.responseText;
		document.getElementById('category-$mdd5').innerHTML='$category';
		if(results.length>3){
			document.getElementById('img-$mdd5').innerHTML=results;
		}
		
    }
	
	
function Save$t(){
	if(!document.getElementById('category-$mdd5')){return;}
	var XHR = new XHRConnection();
    XHR.appendData('checks','$website');
    XHR.appendData('category','$category');
  	AnimateDiv('img-$mdd5');   
    XHR.sendAndLoad('$page', 'POST',xSave$t);   
	
	
	}
	
Save$t();";
	
	
	
}

function check(){
	
}

function page(){
	
	$page=CurrentPageName();
	
$trans["society"]="sohu.com.cn";
$trans["associations"]="vgwort.de";
$trans["publicite"]="adnxs.com";
$trans["shopping"]="amazon.com";
$trans["abortion"]="bornsilent.com";
$trans["agressive"]="horror-extreme.com";
$trans["alcohol"]="best-of-vodka.com";
$trans["animals"]="photographsofanimals.info";
$trans["astrology"]="indianastrologers.in";
$trans["youtube"]="youtube.com";
$trans["audio-video"]="youtube.com";
$trans["automobile/bikes"]="breezerbikes.com";
$trans["automobile/boats"]="bsailing.com";
$trans["automobile/carpool"]="covoiturage.fr";
$trans["automobile/cars"]="bmw.com";
$trans["automobile/planes"]="airliners.net";
$trans["bicycle"]="londoncyclist.co.uk";
$trans["blog"]="blogspot.gr";
$trans["books"]="search-ebooks.com";
$trans["browsersplugins"]="addons.mozilla.org";
$trans["celebrity"]="fanpop.com";
$trans["chat"]="purechat.com";
$trans["children"]="toysrus.com";
$trans["cleaning"]="kaspersky.com";
$trans["clothing"]="zara.com";
$trans["converters"]="calculette.net";
$trans["cosmetics"]="sephora.com";
$trans["culture"]="culture.fr";
$trans["dangerous_material"]="modelguns.co.uk";
$trans["dating"]="inchallah.com";
$trans["dictionaries"]="dictionary.com";
$trans["downloads"]="softpedia-static.com";
$trans["drugs"]="cannabis-seed.us";
$trans["dynamic"]="cpe.pppoe.ca";
$trans["electricalapps"]="thermomix.com";
$trans["electronichouse"]="pioneer.com";
$trans["filehosting"]="dropbox.com";
$trans["finance/banking"]="americanexpress.com";
$trans["finance/insurance"]="assurpeople.com";
$trans["finance/moneylending"]="cetelem.com.ar";
$trans["finance/other"]="webmoney.ru";
$trans["finance/realestate"]="ladyrealestate.ca";
$trans["financial"]="home-finance-reports.com";
$trans["forums"]="forumotion.com";
$trans["gamble"]="casino-on-line.cc";
$trans["games"]="xbox360onlinegames.com";
$trans["genealogy"]="accessgenealogy.com";
$trans["gifts"]="lacartedevoeux.com";
$trans["governments"]="cityofnewyork.us";
$trans["green"]="cooperativeecology.us";
$trans["hacking"]="iandrohacker.com";
$trans["handicap"]="handicap-international.ca";
$trans["health"]="cancerscreen.com.sg";
$trans["hobby/arts"]="wikiart.org";
$trans["hobby/cooking"]="cooking-corner.com";
$trans["hobby/other"]="powerhousedancing.com";
$trans["hobby/pets"]="precious-pets-paradise.com";
$trans["paytosurf"]="goldenaffiliate.cz";
$trans["hobby/fishing"]="purefishing.com";
$trans["hospitals"]="mayoclinic.org";
$trans["houseads"]="entreparticuliers.com";
$trans["housing/accessories"]="ikea.com";
$trans["housing/doityourself"]="travauxbricolage.fr";
$trans["housing/builders"]="baileyhome.us";
$trans["humanitarian"]="unesco.org";
$trans["imagehosting"]="photobucket.com";
$trans["industry"]="safe-cronite.com";
$trans["internal"]="touzeau.local";
$trans["isp"]="adhosting.nl";
$trans["smallads"]="olxtunisie.com";
$trans["stockexchange"]="onvista.de";
$trans["jobsearch"]="myjob.com";
$trans["jobtraining"]="afpa.fr";
$trans["justice"]="criminaljusticeusa.com";
$trans["learning"]="thetotallearningcenter.com";
$trans["luxury"]="cartier.com";
$trans["mailing"]="transisky.fr";
$trans["malware"]="pxksviivmcbdc.co.uk";
$trans["manga"]="mangareader.net";
$trans["maps"]="openstreetmap.us";
$trans["marketingware"]="hubspot.com";
$trans["medical"]="onlinepharmacopeia.com";
$trans["mixed_adult"]="sexegifts.com";
$trans["mobile-phone"]="aptoide.com";
$trans["models"]="perubeauties.org";
$trans["movies"]="netflix.com";
$trans["music"]="guitarparty.com";
$trans["nature"]="greenpeace.org";
$trans["news"]="lefigaro.fr";
$trans["passwords"]="lastpass.com";
$trans["phishing"]="vvvwpaypal.com";
$trans["photo"]="photographevents.biz";
$trans["photo"]="picnik.com";
$trans["pictureslib"]="deviantart.com";
$trans["politic"]="quebecpolitique.com";
$trans["porn"]="youporn.com";
$trans["proxy"]="videounblock.com";
$trans["reaffected"]="114king.com";
$trans["recreation/humor"]="2001blagues.com";
$trans["recreation/nightout"]="moulinrouge.fr";
$trans["recreation/schools"]="uillinois.edu";
$trans["recreation/sports"]="planetabasketball.com";
$trans["recreation/travel"]="hoteltravel.com";
$trans["recreation/wellness"]="fitness-news.org";
$trans["redirector"]="tinyurl.com";
$trans["religion"]="catholic.org.hk";
$trans["remote-control"]="teamviewer.com";

$trans["sciences"]="afnor.org";
$trans["science/astronomy"]="thalesaleniaspace-astronomy.com";
$trans["science/computing"]="ibm.com";
$trans["science/weather"]="wunderground.com";
$trans["science/chemistry"]="chemicalsafety.org.cn";
$trans["searchengines"]="google.com";
$trans["sect"]="myspiritualprofile.com";
$trans["sexual_education"]="sexeducationforkids.com";
$trans["sex/lingerie"]="sexydress.com";
$trans["smallads"]="vivastreet.com";
$trans["socialnet"]="facebook.com";
$trans["spyware"]="lolofaa888mfudodkfkfjdkf.info";
$trans["sslsites"]="verisign.com";
$trans["suspicious"]="com-ww79.net";
$trans["teens"]="melty.fr";
$trans["tobacco"]="greensmoke.com";
$trans["tracker"]="2o7.net";
$trans["translators"]="babylon.com";
$trans["transport"]="ht-transport.com";
$trans["updatesites"]="windowsupdate.com";
$trans["violence"]="mafiacrime.org";
$trans["warez"]="warez-bb.org";
$trans["weapons"]="gunclassics.com";
$trans["webapps"]="officelive.com";
$trans["webmail"]="hotmail.com";
$trans["webphone"]="voiphaber.com";
$trans["webplugins"]="jquerymobile.com";
$trans["webradio"]="romantica.fm";
$trans["webtv"]="bbcnews.tv";
$trans["wine"]="bourgogne-vins.com";
$trans["womanbrand"]="journaldesfemmes.com";
$trans["horses"]="find-your-horse.com";
$trans["meetings"]="ezmeeting.com";
$trans["tattooing"]="tattooing.com";
$trans["publicite"]="adnxs.com";
$trans["getmarried"]="happyweddingwishes.com";
$trans["literature"]="citation-ou-proverbe.fr";
$trans["police"]="policja.gov.pl";
$trans["searchengines"]="google.com";

while (list ($category, $website) = each ($trans) ){
	$mdd5=md5($category.$website);
	
	
	$img=getimg($category,$website);
	
	$html[]="<table style='width:100%'>
	<tr>
		<td style='font-size:18px' width=45% nowrap>$website</td>
		<td style='font-size:18px' width=25% nowrap align='left'>$category</span></td>
		$img
	</tr>		
	";

	
}


$echo="<div style='width:98%' class=form>
	<table style='width:100%'>".@implode("\n", $html)."</table>
	</div>
	"
		
			;

			echo $echo;

	
}

function getimg($category, $website){
	$f=new mysql_catz();
	$img="<td style='font-size:18px' width=33% align='left'>&nbsp;</td><td style='width:45px' nowrap><img src='img/ok42.png'></td>";
	$caz=$f->GET_CATEGORIES($website);
	if($caz==null){return "<td style='font-size:18px' width=33% align='left'>&nbsp;</td><td style='width:45px' nowrap><img src='img/42-red.png'></td>";}
	if(trim($category)<>trim($caz)){return "<td style='font-size:18px' width=33% align='left'>$caz</td><td style='width:45px' nowrap><img src='img/warning42.png'></td>";}
	return $img;
	
	
}
