<?php 
// ****************************************************************************
// 
// ZZJ Audit Tool v1.0
// Copyright (C) 2010  ZigZagJoe (zigzagjoe@gmail.com)
// 
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// 
// ****************************************************************************

// audit functions (seperate from eveAPI)

function redirect($page) {
	if (strpos($page,'http') === FALSE) {
		header("Location: http://". $_SERVER['HTTP_HOST']. str_replace ("\\", "/",dirname($_SERVER['PHP_SELF'])).$page);
	} else
		header("Location: $page");
		
	echo <<<REDIRECT
<html>
<head>
<title>Page Moved</title>
</head>
<body>
<h3><a href="{$page}">Please, click here to continue.</a></h3>
Enable javascript to not get this page.
</body>
</html>	
REDIRECT;
	die();
}


function globl_sortfunc($a, $b) {
	global $sort, $ord;

	if ($a[$sort] == $b[$sort]) {
		if ($sort == "date" && isset($a["ID"])) {
			if ($a["ID"] == $b["ID"]) 
				return 0;
			if ($ord == "ASC") 
				return ((float)$a["ID"] < (float)$b["ID"]) ? -1 : 1;

			return ((float)$a["ID"] > (float)$b["ID"]) ? -1 : 1;
		}
		return 0;
	}

	if ($ord == "ASC") 
		return (strtolower($a[$sort]) < strtolower($b[$sort])) ? -1 : 1;

	return (strtolower($a[$sort]) > strtolower($b[$sort])) ? -1 : 1;
}

function sort_ctrl($invert = false) {
 global $cols, $sort, $ord;
 
 if (!isset($nosorting) && isset($_GET['sort'])) {
  if (in_array($_GET['sort'],$cols)) {
   $sort = $_GET['sort'];
   
   if (isset($_GET['order'])) 
    if ($_GET['order'] == ($invert?"ASC":"DESC"))
     $ord = ($invert?"ASC":"DESC");

   return "<br>sorting by $sort, ".strtolower($ord)." <a href=\"".FULL_URL."&view=".PAGE_VIEW."\">[no sort]</a><br><br>";
  }
 } else return "<br>";
}

function div_select($acct) {
 $output = "";
 
 if (CORP_MODE) {
  $keys = array(1,2,3,4,5,6,7);
  $output .=  "<span style=\"font-size:80%\">division [ ";
  
  foreach ($keys as $acctk) {
   if ($acctk == ($acct-999)) {
    $output .=  $acctk."&nbsp;";
   } else $output .=  "<a href=\"" . FULL_URL . "&view=".PAGE_VIEW."&div=$acctk\">$acctk</a>&nbsp;";
  }
  
  $output .= "]</span><br>";
 }
 
 return $output;
}

function APITime($api) {
 if ($api->cacheHit) {
  return "updated ".niceTime($api->age)." ago";
 } else
  return "updated <b>now</b>";
}

function clear_api_cookie() {
	setcookie("api","");		// clear it
	setcookie("api",false); // try to remove it
	unset($_COOKIE["api"]);
}

function retrieve_api_key($db, $key) {
    if (!$db)
        return null;
    //$result = $link->query("SELECT usid, chid, apik FROM ".DB_PREFIX.API_TABLE." WHERE keyv = '".$link->real_escape_string($key)."' LIMIT 1");
    $result = $db->selectWhere(API_TABLE,['keyv'=>$key],['usid','chid','apik']);
    if ($result != false) {
      if ($result->rows>0) {
        // yay! got a cached value
        $row = $result->results[0];
        return $row;
      }
    }
    return null;
}

function make_short_key($Db, $usid, $apik, $char=null, $chid=null) {
 
    $key = hash('md5', $char.$chid.$usid);
    if (retrieve_api_key($Db, $key) == null) {
        // $sql= "INSERT INTO ".DB_PREFIX.API_TABLE." (keyv, chara, chid, usid, apik) ".
        // "VALUES('$key','".$link->real_escape_string($char)."',$chid,'".$link->real_escape_string($usid)."','".$link->real_escape_string($apik)."')";
        //  $result = $link->query($sql);
        $result = $Db->insert(API_TABLE,[
                 'keyv'=>$key,
                 'chara'=>$char,
                 'chid'=>$chid,
                 'usid'=>$usid,
                 'apik'=>$apik
             ]);
        if ($result) {
            return $key;
        }
    } else {
        $Db->update(API_TABLE,['keyv'=>$key],['apik'=>$apik]);
        //$link->query("UPDATE ".DB_PREFIX.API_TABLE." SET apik='".$link->real_escape_string($apik)."' WHERE keyv='$key'");
        return $key;
    }
 
    return null;
}

function check_email_address($email) {
 $pattern = "/^[\w-]+(\.[\w-]+)*@";
 $pattern .= "([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4})$/i";
 
 if (preg_match($pattern, $email)) {
  $parts = explode("@", $email);
  return checkdnsrr($parts[1], "MX");
 }
 
 return false;  
}
function login_load_creds($Db,$alreadyGotCreds) {
    global $short_api_key;
    global $user;

    define("API_SAVED", !$alreadyGotCreds);
    if(isset($_SESSION['key']))
        $short_api_key=$_SESSION['key'];
    return true;
    // ALL LOGIN APIS SHOULD USE THE SHORT API TABLE! set $short_api_key! in this function.
    // TODO:
    return false; // return false if user is not logged in or loading creds failed, so cookie will be tried
}
function loggedIn(){
  if(isset($_SESSION['login'])&&$_SESSION['login']===true) {
   return true;
  }
 return false;
}


function canAccess($mask) {
    $result = (KEY_MASK & $mask);
    if($result < 0 ){
        $result = (KEY_MASK/536870912 & $mask/536870912);
        return $result == $mask/536870912;
    }
    return $result == $mask;
}

 ?>