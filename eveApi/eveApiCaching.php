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

// api cache bits



define("API_BASE_URL","https://api.eveonline.com");


$_checkedTables = false;

class CacheEntry {
 public $value;
 public $timeLeft;
 public $hit;
 public $error;
 public $http_error;
 public $api_error;

 protected $key;
 protected $apicall;
 protected $link;
 
 public function __construct($value = null, $hit = -1, $timeLeft = -1, $key="", $apicall = "", $link = null) {
  $this->value = $value; 
  $this->timeLeft = $timeLeft;
  $this->hit = $hit;
  
  $this->key = $key;
  $this->apicall = $apicall;
  $this->link = $link;

  $this->http_error = ($hit == -1); 
  @$this->api_error =  $this->value != null && $this->value->error;
  $this->error = $this->http_error || $this->api_error;
}
 
 public function update($value) {
  if ($this->key != "") {
   $this->value = $value;
   $this->hit = false;
   return  mysql_query(            // update the cached response in the DB
  "UPDATE ".DB_PREFIX.CACHE_TABLE." SET value='".addslashes(gzcompress($value->asXML(),6)) . "' WHERE keyv='{$this->key}' AND apicall='{$this->apicall}'"
  ,$this->link);
  //die ("would store " . $value->asXML());
  } else return false;
 }

 public function __toString() {
  return (string)$this->value;
 }
}

function get_timeLeft($time) {
 @$oldTz = date_default_timezone_get();
 date_default_timezone_set ("UTC");
 
 $value_expires = strtotime($time);
 $value = time();
 
 if ($oldTz)
  date_default_timezone_set ($oldTz);
  
 return $value_expires-$value+2;
}

function api_retrieve($link,$apicall, $args = array()) { 
 return cache_api_retrieve($link,$apicall,$args,-1);
}

function simple_api_retrieve($apicall, $args) {
 $req = curl_init(API_BASE_URL . $apicall);

 curl_setopt($req, CURLOPT_POST, true);
 curl_setopt($req, CURLOPT_PORT, 443);
 curl_setopt($req, CURLOPT_POSTFIELDS, $args);
 curl_setopt($req, CURLOPT_SSL_VERIFYPEER, 1);
 curl_setopt($req, CURLOPT_SSL_VERIFYHOST,2 );
 curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($req, CURLOPT_TIMEOUT, 30);
 curl_setopt($req, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem'); //CA cert file
 
 $resp = curl_exec($req);
 $http_code = curl_getinfo($req,  CURLINFO_HTTP_CODE);
 $http_errno = curl_errno($req);
 if (isset($allApiCalls))
	$allApiCalls[] = array($apicall,$args, "fetched_nocache");
 curl_close($req);

 if ($http_errno != 0) 
  return false;

 if ($http_code != 200)       // major api failure
  return false;

 try {
  return new SimpleXMLElement($resp,LIBXML_NOCDATA);
 } catch (Exception $e) {    // malformed XML
  return false;
 }  
}

function cache_api_retrieve($link, $apicall, $args = array(), $expiresOverride = 0) {
	global $allApiCalls;
 if (!$link)
  return null;
	
 date_default_timezone_set ("UTC");
 
 if ($expiresOverride != -1) { // skip the cache, don't want to save it and won't be cached
  global $_checkedTables;
   
  if (!$_checkedTables) { // create the table if it does not exist already otherwise prune old entries
   $_checkedTables = true;
   
   $result = $link->query("SHOW TABLES LIKE '".CACHE_TABLE."'");

   if (!$result || mysqli_num_rows($result) == 0) { // table does not exist... make a new one
       $link->query("CREATE TABLE ".DB_PREFIX.CACHE_TABLE." (apicall VARCHAR(64), keyv CHAR(64), expires DATETIME, value MEDIUMBLOB)");
       $link->query("CREATE INDEX idx_".DB_PREFIX.CACHE_TABLE." ON ".CACHE_TABLE."(keyv)");
   } else {
    // table exists, so delete all outdated cache entries, so they don't gum up the DB
       $link->query("DELETE FROM ".DB_PREFIX.CACHE_TABLE." WHERE expires < '".gmdate("Y-m-d H:i:s")."'");
   }

   if ($result != false)
    mysqli_free_result($result);
  }

  $key = hash('sha256', $apicall . implode($args));
    
	if (isset($args["vCode"]) && strpos($args["vCode"],"old_") === 0) {
		$args["userid"] = $args["keyID"];
		$args["apikey"] = substr($args["vCode"],4);

		unset($args["keyID"]);
		unset($args["vCode"]);
	}
	
  $result = $link->query("SELECT expires, value FROM ".DB_PREFIX.CACHE_TABLE." WHERE apicall = '".$apicall."' AND keyv = '".$key."' LIMIT 1");

  if ($result != false) {
   if (mysqli_num_rows($result) > 0) {
    // yay! got a cached value
    $row = mysqli_fetch_assoc($result);

    mysqli_free_result($result);
	 //echo "result: " .gzuncompress($row['value']);
	 if (isset($allApiCalls))
		$allApiCalls[] = array($apicall,$args, "cached");
	
    return new CacheEntry(new SimpleXMLElement(gzuncompress($row['value']),LIBXML_NOCDATA), 1, get_timeLeft($row['expires']),$key,$apicall,$link);
   }
   mysqli_free_result($result);
  }
  
 }
 // no cached value or it is old... have to do the query proper
 $req = curl_init(API_BASE_URL . $apicall);
 curl_setopt($req, CURLOPT_POST, true);
 curl_setopt($req, CURLOPT_PORT, 443);
 curl_setopt($req, CURLOPT_POSTFIELDS, $args);
 curl_setopt($req, CURLOPT_SSL_VERIFYPEER, 1);
 curl_setopt($req, CURLOPT_SSL_VERIFYHOST,2 );
 curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($req, CURLOPT_TIMEOUT, 30);
 curl_setopt($req, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem'); //CA cert file
 $resp = curl_exec($req);
 
 $http_code = curl_getinfo($req,  CURLINFO_HTTP_CODE);
 $http_errno = curl_errno($req);
 
 if (isset($allApiCalls))
	$allApiCalls[] = array($apicall,$args, "fetched");
	
 curl_close($req);
 if ($http_errno == 60) 
  return new CacheEntry("Ssl is not working for Curl");
 if ($http_errno != 0) 
  return new CacheEntry($http_errno);
 if ($http_code != 200)       // major api failure
  return new CacheEntry("unexpected response code $http_code");

 try {
  $xml = new SimpleXMLElement($resp,LIBXML_NOCDATA);
 } catch (Exception $e) {    // malformed XML
  return new CacheEntry("malformed XML document");
 }
 if ($xml->error)            // error response, don't cache it...
  return new CacheEntry($xml, 0);

 if ($expiresOverride == -1) // skip the cache, don't want to save it and won't be cached
  return new CacheEntry($xml, 0);
 
 $cachedUntil = (string)($xml->cachedUntil);
 
 if ($expiresOverride == 0) {
  // other overrides moved into specific files 
 /* if ($apicall == "/char/MailMessages.xml.aspx") // only can get full msgs so often
   $cachedUntil = gmdate("Y-m-d H:i:s",time()+7*60*60);*/
 } else 
  $cachedUntil = gmdate("Y-m-d H:i:s",time()+$expiresOverride);

    $link->query(            // add the cached response to the DB
  "INSERT INTO ".DB_PREFIX.CACHE_TABLE." (apicall, keyv, expires, value) ". //             v best comprimise for ratio/speed
  "VALUES('".$apicall."', '".$key."', '".$cachedUntil."', '" . addslashes(gzcompress($resp,6)) . "')"
  );//                                                        ^ needed for binary data
  
 return new CacheEntry($xml, 0, get_timeLeft($cachedUntil),$key,$apicall,$link);
}
 ?>