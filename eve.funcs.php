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

    $keyInfo = cache_api_retrieve($Db,"/account/APIKeyInfo.xml.aspx", array("keyID"=>$userid, "vCode" => $apikey),5*60)->value;
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
 while($str != '' && $str[0] == '0')
  $str = substr($str, 1);

 return $str;
}

function microtime_float() {
 list($usec, $sec) = explode(" ", microtime());
 return ((float)$usec + (float)$sec);
}

function isFullApi($db,$chid,$usid,$apik) {
    $key = md5("IDLOOKUP:".$usid.";".$apik);
    // first try to look up cached values in the DB
    //$result = $link->query("SELECT * FROM ".DB_PREFIX."api_type_cache WHERE keyv='".addslashes($key)."' LIMIT 1");
    $result = $db->selectWhere("api_type_cache",['keyv'=>$key]);

    if ($result != false) {
        if ($result->rows > 0) { // got it! return it and have done
            $row = $result->results[0];
            return $row['type'] == "1";
        }
    }

    $xmlstr = cache_api_retrieve($db,"/char/AccountBalance.xml.aspx", array("characterID" => $chid,"keyID"=>$usid,"vCode"=>$apik));

    if ($xmlstr->http_error)
        return false;

    $isFull = true;

    if ($xmlstr->api_error)
        $isFull = false;
    $db->insert(TYPE_CACHE_TABLE,['keyv'=>$key,'type'=>($isFull?"1":"0")]);
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

function char_idLookup($Db,$list,$names) {
	$len = count($list);

	// get the ids...
	$names = fuck_ccp($list,$names);

	$sql = "";
    $insertStatement=$Db->prepare()->insert(ID_CACHE_TABLE,['id'=>"?","name"=>"?"]);
	foreach ($list as $id) {
        $insertStatement->execute(['id'=>$id,'name'=>$names[$id]]);
    }
    return $names;
}

function idLookup($link,$ids) {
    global $Db;

    if (!is_array($ids)) {
        $ids = array($ids);
    } else {
        $ids = array_unique($ids);
    }
    if(empty($ids)){
        return null;
    }


    $names = array();

    // first try to look up cached values in the DB
    $result=$Db->selectWhere(ID_CACHE_TABLE,['id'=>['IN',$ids]]);

    if ($result != false) {
        if ($result->rows) { // add any found to the list
            foreach($result->results as $row){
                $names[$row['id']] = $row['name'];
            }
        }
    }

    if (count($names) == count($ids))
        return $names; // all names were cached!

    $list = array();

    foreach($ids as $id) { // make a list of ids which were not cached
        if (!isset($names[$id])) {
            $list[] = $id;
        }
    }

    $result = simple_api_retrieve('/eve/CharacterName.xml.aspx',array('ids'=>implode(",",$list)));
    if(!$result)
	    return false;
    // get the ids...
    if ($result->error)
        return char_idLookup($Db,$list,$names);

    $insertStatement=$Db->prepare()->insert(ID_CACHE_TABLE,["id"=>"?","name"=>"?"]);
    foreach($result->value->xpath('//row') as $kvp) { // add them to the array and make an insert query
        $names[(int)$kvp['characterID']] = (string)$kvp['name'];
        $insertStatement->execute([$kvp['characterID'],$kvp['name']]);
    }

    return $names;
}

function getEvePrice($id, $Db) {

     $link = $Db->link;
     $result=$Db->selectWhere("prices",['typeID'=>$id]);
     $sql = "SELECT value FROM ".DB_PREFIX."prices WHERE typeID=".$id;

     if (!$result) {
         return 0;
     }

     if ($result->rows>0) {
         $row = $result->results[0];
         return $row["value"];
     } else {
         $evec = file_get_contents("https://api.eve-central.com/api/marketstat?typeid=".$id);

         $xml = new SimpleXMLElement($evec);

         $value = $xml->marketstat->type->sell->median;

         $Db->insert("prices",['typeID'=>$id,"value"=>$value]);

         return $value;
     }
}
function GetRedIDS($ids,$Db){
    $redIDS=array();
    $IDS = new eveApiCharacterAffiliations($Db);
    $IDS->fetch($ids);
    $characters=$IDS->IDs;
    foreach($characters as $character){
        if(in_array((string)$character['characterID'],$_SESSION['redFlagIds']))
            $redIDS[]=(string)$character['characterID'];
        if($character['corporationID']!=0&&in_array((string)$character['corporationID'],$_SESSION['redFlagIds']))
            $redIDS[]=(string)$character['characterID'];
        if($character['allianceID']!=0&&in_array((string)$character['allianceID'],$_SESSION['redFlagIds']))
            $redIDS[]=(string)$character['characterID'];
    }
    $redIDS=array_unique($redIDS);
    return $redIDS;
}
function GetContactInfo(&$Contacts,$Db){
    $redIDS=array();
    $ids=array();
    foreach ($Contacts as $key=>$contact) {
        $ids[(string)$contact['contactID']]=$key;
    }
    $IDS = new eveApiCharacterAffiliations($Db);
    $IDS->fetch(array_keys($ids));
    $characters=$IDS->IDs;
    foreach($characters as $character){
       if(in_array((string)$character['characterID'],$_SESSION['redFlagIds'])) {
           $redIDS[] = (string)$character['characterID'];
       }
       if($character['corporationID']!=0) {
           if(in_array((string)$character['corporationID'],$_SESSION['redFlagIds'])) {
               $redIDS[] = (string)$character['characterID'];
           }
           $Contacts[$ids[(string)$character['characterID']]]['corpID']=$character['corporationID'];
           $Contacts[$ids[(string)$character['characterID']]]['corpName']=$character['corporationName'];
       }
       if($character['allianceID']!=0) {
           if(in_array((string)$character['allianceID'],$_SESSION['redFlagIds'])) {
               $redIDS[] = (string)$character['characterID'];
           }
           $Contacts[$ids[(string)$character['characterID']]]['allianceID']=$character['allianceID'];
           $Contacts[$ids[(string)$character['characterID']]]['allianceName']=$character['allianceName'];
       }
    }
    $redIDS=array_unique($redIDS);
    return $redIDS;
}
 ?>
