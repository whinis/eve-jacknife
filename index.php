<?php 
// ****************************************************************************
// 
// ZZJ Audit Tool v1.5
// Copyright (C) 2010  ZigZagJoe (zigzagjoe@gmail.com) and
// Copyright (C) 2012  Equto   (whinis@whinis.com)
// 
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License,or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,


// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc.,59 Temple Place,Suite 330,Boston,MA  02111-1307  USA
// 
// ****************************************************************************
ini_set("zlib.output_compression", "On");
ini_set("zlib.output_compression", 4096);

define("SELF_URL", "http://" . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?");
define("AUDIT_PHP", true);

$cookielogin = false;
$charSelect=true;

$infoBarFunctions=array();
$footerFunctions=array();

require_once("eve.php");
require_once("audit.funcs.php");
require_once("audit.views.php");


if(defined("allow_login")&&allow_login==true) {
	require_once("login.php");
}
$_SESSION['redirect']="index.php";


if (isset($_GET['newapi'])) 
	clear_api_cookie();
// load variables n such - defined here temporary before being set to constants later on
if (isset($_GET['usid']))
	$userid = trim($_GET['usid']);

if (isset($_GET['apik']))
	$apikey = trim($_GET['apik']);

if (isset($_GET['chid']))
	$chid = trim($_GET['chid']);

if (isset($_GET['key']))
	$short_api_key = trim($_GET['key']);
if (!login_load_creds($Db, (isset($userid) && isset($apikey) || isset($short_api_key)))) {
	// handle cookie stuff
	if (isset($_COOKIE["api"]) && ($_COOKIE["api"] != "") && !isset($userid) && !isset($short_api_key)) { // if a cookie was present, and we were not passed args via get, try to use what is in the cookie
		$login = explode(',',$_COOKIE["api"]);
		$cookielogin = true;
		if ($login[0] == "api") {
			$userid = trim($login[1]);
			$apikey = trim($login[2]);
			if (count($login) > 3)
				$chid = trim($login[3]);
		} else 
			$short_api_key = trim($login[1]);
			
		$_GET['save'] = "1";
	}
} else 
		$_GET['save'] = "1";

if (isset($short_api_key)) { // get stored apikey
	$charSelect=false;
	$ret = retrieve_api_key($Db, $short_api_key);

	if ($ret != null) {
		$chid = $ret["chid"];
		$userid = $ret["usid"];
		$apikey = $ret["apik"];
	} else {
		unset($short_api_key); // invalid key
		$cookielogin = false;
	}
}
if(!isset($chid)&&isset($_GET['chid'])){
	$chid = trim($_GET['chid']);
	$charSelect=true;
}

define("COOKIE_LOGIN", $cookielogin);

if ((!isset($apikey) || !isset($userid)) && !isset($short_api_key)) {
	$info = "";
		
	if (isset($_GET['fittingid'])) {
		$result=$Db->selectWhere(FITTINGS_TABLE,["keyv"=>$_GET['fittingid']]);
		if ($result != false && $result->rows) {
			// yay! got a cached value
			$row = $result->results[0];
			$info = "Your ability to use the fitting '$row[name]' ($row[ship]) will be shown after you log in."; 
		}	
		$Db->close();
	} 
	
	api_input($info); // die
}
if(!isset($_SESSION['redFlagText']))
    $_SESSION['redFlagText']="";
if(!isset($_SESSION['redFlagIds']))
    $_SESSION['redFlagIds']=array();

define("USER_ID", $userid);
define("API_KEY",$apikey);

$multiplechars = false;

function canAccess($mask) {
	return (KEY_MASK & $mask) == $mask;
}


$keyInfo = cache_api_retrieve($Db,"/account/APIKeyInfo.xml.aspx", array("keyID"=>USER_ID, "vCode" => API_KEY),5*60)->value;
if (!is_object($keyInfo)||$keyInfo->error)
	fatal_error("Unable to load API. Verify the key is correct and not expired.");
define("KEY_MASK",(float)$keyInfo->result->key["accessMask"]);
$multiplechars = count($keyInfo->result->key->rowset->row) > 1;

if (!$multiplechars)
	$chid = (string)$keyInfo->result->key->rowset->row[0]["characterID"];

if (isset($chid)) {
	$char = $keyInfo->api->xpath("//row[@characterID='$chid']");
	if (count($char) == 0)
		fatal_error("The character ID was not found on this account.");

	define("CHAR_NAME",(string)$char[0]["characterName"]);
}

define("KEY_TYPE",(string)$keyInfo->result->key["type"]);

if (KEY_TYPE == "Corporation") {
	define("CORP_MODE",true);
	define("CORP_ID",(string)$keyInfo->result->key->rowset->row[0]["corporationID"]);
	define("CORP_NAME",(string)$keyInfo->result->key->rowset->row[0]["corporationName"]);
	$multiplechars = false;
} else
	define("CORP_MODE",false);


if (isset($short_api_key)&&$charSelect)
	$urlAuthInfo = "key=$short_api_key&chid=$chid";
elseif (isset($short_api_key)&&!$charSelect)
	$urlAuthInfo = "key=$short_api_key";
else 
	$urlAuthInfo = (isset($chid) ? "chid=$chid&" :"") . "usid=" . USER_ID . "&apik=" . API_KEY ;

define("FULL_URL", /*SELF_URL .*/ "?" . $urlAuthInfo . ((isset($_GET['save']) && $_GET['save'] == "1") ? "&save=1":""));

/* . ((!isset($_GET['view']) && isset($_GET['fittingid'])) ? "&fittingid=$_GET[fittingid]" : "")*/
	
if ($multiplechars && !isset($chid)) { // must resolve a character id
	if (!isset($chars))
		$chars = $Db->fetchApiChars(USER_ID, API_KEY);

	character_select($Db, $chars);
}
	
define("CHAR_ID",$chid);
define("USER_NAME",CORP_MODE ? CORP_NAME: CHAR_NAME); // set to corp name or char name depending on key type

if (!CORP_MODE && isset($_GET['save']) && $_GET['save'] == "1") { // save api key into a cookie if present
	if (isset($short_api_key)) {
		$auth="key,$short_api_key";
	} else 
		$auth="api," . USER_ID . "," . API_KEY . "," .CHAR_ID;
	setcookie("api",$auth,time()+60*60*24*30);
}

// load pages into registered_pages (all pages),eligible_pages (supported by key type),enabled_pages 
require_once("audit.pages.php");

if (isset($_GET['makeshorturl'])) {
	$key = make_short_key($Db,USER_ID,API_KEY,CHAR_NAME,CHAR_ID);

	if ($key) {
		header("Location: ".SELF_URL."key=$key" . (isset($_GET['view']) ? "&view=".$_GET['view'] : ""));
		die("<html><body><a href=\"" .SELF_URL."key=$key" . (isset($_GET['view']) ? "&view=".$_GET['view'] : "") . "\">Click here to continue.</a><body></html>");
	} else 
		fatal_error("Unable to create key.","Please try again later.",true);
}
	
$infobar = "<span id='infobar' class=\"infobar\">&lt;&nbsp;";
$getpage = "none";

if (isset($_GET['view'])) {
	$getpage = trim($_GET['view']);
	if (!isset($eligible_pages[$getpage]))
		$getpage = "none";
}

foreach ($eligible_pages as $name=>$page) {
	if (isset($enabled_pages[$name])) {
		if ($getpage == "none")
			$getpage = $name;
			
		if ($name != $getpage) {
			$infobar .=  "<a href=\"" . FULL_URL . "&view=$name\">$name</a>";
		} else 
			$infobar .=  "<span class=\"current_page\">$name</span>";
	
	} else {
		$infobar .=  "<span class=\"disabled_page\">$name</span>";
	}
	$infobar .=  "&nbsp;";
}

if ($getpage == "none") 
	fatal_error("This API has no pages that the Jackknife can display.");

define("PAGE_VIEW", $getpage);

$infobar .= "|&nbsp;";
$infobar .= "<a href=\"".SELF_URL."newapi\">new api</a>&nbsp;";
if ($multiplechars&&$charSelect)
	if(isset($short_api_key))
		$infobar .= "<a href=\"".SELF_URL."key=$short_api_key\">char select</a>&nbsp;";
	else
		$infobar .= "<a href=\"".SELF_URL."usid=". USER_ID."&apik=".API_KEY. ((isset($_GET['save']) && $_GET['save'] == "1") ? "&save=1":"")."\">char select</a>&nbsp;";
if (!isset($short_api_key)) {
		$infobar .= "<a href=\"".FULL_URL."&makeshorturl&view=".PAGE_VIEW."\">short url</a>&nbsp;";
	}
$infobar .= "|<a id='redFlag' href=\"#redflag\">Set Red Flags</a>&nbsp;";
foreach($infoBarFunctions as $function){
	$infobar.="|&nbsp;".$function();
}
$infobar .= "&gt;&nbsp;<b>" . strtoupper(KEY_TYPE);
$infobar.="</b></span>";

//////// FINAL OUTPUT
if (PAGE_VIEW == "onepage") {
	insert_header("Ataglance for " . USER_NAME);
	echo $infobar . "<br>";
	$next = 1;
	$time_start2 = microtime_float();
	foreach($enabled_pages as $name => $page) {
		if ($page == "onepage")
			continue;
			
		$error = !$page->GetOutput($Db);

		if ($error) {
			//fatal_error("API: " . $page->Output);
		} else {
			echo "<br>";
			echo "<a name=\"s".($next-1)."\"></a>";
			echo "<span style=\"font-size: 70%\"><a href=\"#s$next\">skip to next section</a></span><br>";
			 $next++;
			echo "<h2>" . ucfirst($name) . "</h2>";

			if ($page->Header != "") 
				echo $page->Header . "<br>";
			echo $page->Output . "<hr>";	
		}
	}
	echo "<a name=\"s".($next-1)."\"></a>";

	$time_end2 = microtime_float();
	$time_exec = $time_end2 - $time_start2;

	echo "<br><span style=\"font-size:80%;\"><a href=\"#top\">top</a></span><br>\n";
	echo "<span style=\"font-size:80%;\">";
	if ($Db->queries != 0)
		echo "\n".$Db->queries." queries<br>";
	echo "exec time: $time_exec s<br>";
	echo "</span>";
	echo "</body></html>";
} else {
	$page = $eligible_pages[PAGE_VIEW];
	$error = !$page->GetOutput($Db);
	echo $infobar . "<br>";
	if ($error) {
		fatal_error("API: " . $page->Output,"",true);
	} else {
		$page->SetHeaders();
		insert_header($page->Title);
		
		if ($page->Updated)
			echo "<span class=\"updated_text\">" . $page->Updated . "</span><br>";
			
		if ($page->Header != "") {
			echo $page->Header . "<br>";
		} else 
			echo "<span style=\"font-size:450%\">". USER_NAME . "</span><br>";
	
		echo $page->Output . "<br>";
		echo $page->Times . "<br>";
		echo "</body></html>";
	}
}
 ?>
