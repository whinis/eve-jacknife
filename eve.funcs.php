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

// eveAPI funcs

function get_key_info($Db, $userid, $apikey) {
	if (strpos($apikey,"old_") === 0) { // old api key, must load characters and other horseshit	
		$chars = $Db->fetchApiChars($userid, $apikey);
		if (!$chars) return "";
		
		$apiinfo = "<b>OLD KEY</b> #$userid for ";
		foreach ($chars as $char) 
			$apiinfo .= "$char[name], ";
	} else {	
		$keyInfo = cache_api_retrieve($Db->link, "/account/APIKeyInfo.xml.aspx", array("keyID"=>$userid, "vCode" => $apikey),5*60)->value;
		if ($keyInfo->error) return "";
		
		$mask = (float)$keyInfo->result->key["accessMask"];
		$keytype = (string)$keyInfo->result->key["type"];
		$apiinfo = "<b>$keytype</b> CAK (mask $mask) for ";
		
		if ($keytype == "Corporation") {
			$apiinfo .= (string)$keyInfo->result->key->rowset->row[0]["corporationName"];
		} else {
			foreach ($keyInfo->api->xpath("//row") as $char) 
				$apiinfo .= "$char[characterName], ";
		}
	}
	
	return rtrim($apiinfo,", ");
}


function niceTime($timeLeft, $count = 10) {
 @$oldTz = date_default_timezone_get();
 date_default_timezone_set ("UTC");
 
 $tl = array();

 $days = (int)($timeLeft/(24*60*60));
 $hours = trimZeros(strftime("%H",$timeLeft));
 $mins = trimZeros(strftime("%M",$timeLeft));
 $secs = trimZeros(strftime("%S",$timeLeft));

 date_default_timezone_set($oldTz);

 if ($days > 0)  $tl[] = $days ." day".($days == 1?"":"s");
 if ($hours > 0) $tl[] = $hours ." hour".($hours == 1?"":"s");
 if ($mins > 0)  $tl[] = $mins ." minute".($mins == 1?"":"s");
 if ($secs > 0)  $tl[] = $secs ." second".($secs == 1?"":"s");

 if (empty($tl[0]))
  return "";

 $rem = $tl[0];

 for ($i = 1; $i < count($tl) && $i < $count; $i ++)
  $rem .= ", ". $tl[$i];

 return $rem;
}


function lvlToColour($level) {
 switch ($level) {
  default: $col = "#FFFFFF"; break;
  case 0:  $col = "#AAAAAA"; break;
  case 1:  $col = "#FF3333"; break;
  case 2:  $col = "#FFCC00"; break;
  case 3:  $col = "#BBBB44"; break;
  case 4:  $col = "#00AA00"; break;
  case 5:  $col = "#11FF00"; break;
 }
 return $col;
}

function locationTranslate($location) {
 if ($location >= 66000000 && $location < 67000000)
  return $location - 6000001; // office offset

 if ($location >= 67000000 && $location < 68000000)
  return $location - 6000000; // conquerable station offset

 return $location;
}

function trimZeros($str) {
 while($str[0] == '0')
  $str = substr($str, 1);
 
 return $str;
}

function microtime_float() {
 list($usec, $sec) = explode(" ", microtime());
 return ((float)$usec + (float)$sec);
}

function isFullApi($link,$chid,$usid,$apik) {
 $key = md5("IDLOOKUP:".$usid.";".$apik);
 // first try to look up cached values in the DB
 $result = $link->query("SELECT * FROM ".DB_PREFIX."api_type_cache WHERE keyv='".addslashes($key)."' LIMIT 1");

 if ($result != false) {
  if (mysqli_num_rows($result) > 0) { // got it! return it and have done
   $row = mysqli_fetch_assoc($result);
   mysqli_free_result($result);
   return $row['type'] == "1";
  }
  mysqli_free_result($result);
 }
 
 $xmlstr = cache_api_retrieve($link, "/char/AccountBalance.xml.aspx", array("characterID" => $chid,"keyID"=>$usid,"vCode"=>$apik));
 
 if ($xmlstr->http_error)
  return false; 
 
 $isFull = true;
 
 if ($xmlstr->api_error) 
  $isFull = false;

    $link->query("INSERT INTO ".DB_PREFIX.TYPE_CACHE_TABLE." (keyv, type) VALUES ('".addslashes($key)."', ".($isFull?"1":"0").")"); // insert the new values into cache
 return $isFull;
}

function parse_ccptml($str) {
	$str = str_replace("=\"12\"","=\"2\"",$str);
	//TODO: showinfo links, regex em out and such
	return $str;
}


function fuck_ccp($list, $names) { // stupid durable id lookup
 $result = simple_api_retrieve('/eve/CharacterName.xml.aspx',array('ids'=>implode(",",$list)));
 
 if ($result->error) {
	$c = count($list);
	if ($c == 1) {
		$names[$list[0]] = "[ID " .$list[0]. "]";
	} else {
		$names = fuck_ccp(array_slice($list,0,$c/2),$names);
		$names = fuck_ccp(array_slice($list,$c/2),$names);
	}
	return $names;
 }
 
 foreach($result->value->xpath('//row') as $kvp)
  $names[(int)$kvp['characterID']] = (string)$kvp['name'];
 
 return $names;
}

function ass_idLookup($link,$list,$names) {
// fuck you, ccp.
	$len = count($list);

	// get the ids...
	$names = fuck_ccp($list,$names);

	$sql = "";
	foreach ($list as $id) 
		$sql .= "(".$id.",'".addslashes($names[$id])."'),";
    $link->query("INSERT INTO ".DB_PREFIX.ID_CACHE_TABLE." (id, name) VALUES ".rtrim($sql,","));
   return $names;
}

function idLookup($link,$ids) {
 global $Db;
 
 if (!is_array($ids)) {
  $ids = array($ids);
 } else
  $ids = array_unique($ids);
  
 $names = array();
 
 // first try to look up cached values in the DB
 $sql = "SELECT * FROM ".DB_PREFIX.ID_CACHE_TABLE." WHERE id IN (".implode(",",$ids).")";
 $result = $link->query($sql) ;

 if ($result != false) {
  if (mysqli_num_rows($result) > 0) // add any found to the list
   while($row = mysqli_fetch_assoc($result))
    $names[$row['id']] = $row['name'];
    
  mysqli_free_result($result);
 }
 
 if (count($names) == count($ids))
  return $names; // all names were cached!
 
 $list = array();

 foreach($ids as $id) // make a list of ids which were not cached
  if (!isset($names[$id])) 
   $list[] = $id;
   
 $result = simple_api_retrieve('/eve/CharacterName.xml.aspx',array('ids'=>implode(",",$list)));
 if(!$result)
	return false;
  // get the ids...
 if ($result->error) // ccp sucks, look it up by divide and conquer
  return ass_idLookup($link,$list,$names);

 $sql_ins = "";
 
 foreach($result->value->xpath('//row') as $kvp) { // add them to the array and make an insert query
  $names[(int)$kvp['characterID']] = (string)$kvp['name'];
  $sql_ins .= "(".$kvp['characterID'].",'".addslashes($kvp['name'])."'),";
 }

    $link->query("INSERT INTO ".DB_PREFIX.ID_CACHE_TABLE." (id, name) VALUES ".rtrim($sql_ins,",")); // insert the new values into cache
 
 return $names;
}

function getEvePrice($id, $Db) {

 $link = $Db->link;
 
 $sql = "SELECT value FROM ".DB_PREFIX."prices WHERE typeID=".$id;

 $result = $link->query($sql);

 if (!$result) {
  echo 'MySQL Error: ' . mysql_error();
  return 0;
 }
 
 if (mysqli_num_rows($result) > 0) {
  $row = mysqli_fetch_assoc($result);
  mysqli_free_result($result);
  
  return $row["value"];
 } else {
  $evec = file_get_contents("http://api.eve-central.com/api/marketstat?typeid=".$id);
  
  $xml = new SimpleXMLElement($evec);

  $value = $xml->marketstat->type->sell->median;

  $sql = "INSERT INTO ".DB_PREFIX."prices (typeID, value) VALUES (".$id.",".$value.")";
  $result = $link->query($sql);

  return $value;
 }
 
}
 ?>