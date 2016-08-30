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

// api killlog
 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiKillLog extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
 public $Kills;
 public $Losses;
 public $All;
 
 protected $chid;
 protected $corp;
 
 public function fetch($chid,$usid,$apik, $corp = false, $corpid = "", $token=false) {
     $this->chid = $corp?$corpid:$chid;
     $this->corp = $corp;
     if(SSO_MODE)
         return $this->fetch_xml("/".($corp?"corp":"char")."/KillLog.xml.aspx",array("characterID"=>$chid,"accessToken"=>$usid));
     else
         return $this->fetch_xml("/".($corp?"corp":"char")."/KillLog.xml.aspx",array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik));
 }

 public function loadAPI() {
  if ($this->corp) {
   $this->Kills = $this->api->xpath("/eveapi/result/rowset[@name='kills']/row/victim[@corporationID!='$this->chid']/..");
   $this->Losses = $this->api->xpath("/eveapi/result/rowset[@name='kills']/row/victim[@corporationID='$this->chid']/..");
  } else {
   $this->Kills = $this->api->xpath("/eveapi/result/rowset[@name='kills']/row/victim[@characterID!='$this->chid']/..");
   $this->Losses = $this->api->xpath("/eveapi/result/rowset[@name='kills']/row/victim[@characterID='$this->chid']/..");
  }
  
  $this->All = $this->api;
  
  return true;
 }
}
  
function getcharname($kill) {
 global $Db;

 if ((int)$kill->victim["characterID"] != 0)
  return $kill->victim["characterName"];
  
 if ((int)$kill["moonID"] != 0) {
  $moon = $Db->getNameFromMoonId((int)$kill["moonID"]);
  if ($moon != null)
   return $moon;
  
  $id = idLookup($Db->link,(int)$kill["moonID"]);
  if (!isset ($id[(int)$kill["moonID"]])) return "Unknown Character"; // dunno? missmatched db?
  return $id[(int)$kill["moonID"]];
 }
 return $Db->getNameFromTypeId((int)$kill->victim['shipTypeID']);
}

function generate_kill($kill) {
 global $Db;
 
 @$oldTz = date_default_timezone_get();
 date_default_timezone_set ("UTC");
 
 $killtime = strtotime((string)($kill["killTime"]));
 /*print_r($kill);
 die();*/
 if ($oldTz)
  date_default_timezone_set($oldTz);
 
 $ret = "";
 
 $ret .= date("Y.m.d H:i",$killtime)."\n";
 $ret .= "\n";
 $ret .= "Victim: " . getcharname($kill) . "\n";
 $ret .= "Corp: ". $kill->victim["corporationName"] . "\n";
 $ret .= "Alliance: " . (($kill->victim["allianceName"] == "")?"NONE": $kill->victim["allianceName"]) ."\n";
 $ret .= "Faction: " . (($kill->victim["factionName"] == "")?"NONE": $kill->victim["factionName"]) ."\n";
 $ret .= "Destroyed: " . $Db->getNameFromTypeId((int)$kill->victim['shipTypeID']) ."\n";
 $ret .= "System: " . $Db->getNameFromSystemId((int)$kill['solarSystemID']) ."\n";
 $ret .= "Security: " . $Db->getSystemSecurity((int)$kill['solarSystemID']) ."\n";
  
 $dmgtot = 0;
 
 $attackers = array();
 $dmg = array();
 
 foreach($kill->rowset[0]->row as $attacker) {

  if ((int)$attacker["characterID"] == 0) {
   $attckr = "Name: " . (($attacker["shipTypeID"] != 0)?$Db->getNameFromTypeId((int)$attacker['shipTypeID']):"Unknown") . " / " . $attacker["corporationName"]  . ($attacker["finalBlow"] == 1?" (laid the final blow)":"") ."\n";
   $attckr .= "Damage Done: ". $attacker["damageDone"] . "\n";
  } else {
   $attckr = "Name: " . $attacker["characterName"] . ($attacker["finalBlow"] == 1?" (laid the final blow)":"")."\n";
   $attckr .= "Security: " . number_format((double)$attacker["securityStatus"],1) . "\n";
   $attckr .= "Corp: ". $attacker["corporationName"] . "\n";
   $attckr .= "Alliance: " . (($attacker["allianceName"] == "")?"NONE": $attacker["allianceName"]) ."\n";
   $attckr .= "Faction: " . (($attacker["factionName"] == "")?"NONE": $attacker["factionName"]) ."\n";
   $attckr .= "Ship: " . (($attacker["shipTypeID"] != 0)?$Db->getNameFromTypeId((int)$attacker['shipTypeID']):"Unknown") ."\n";
   $attckr .= "Weapon: " . (($attacker["weaponTypeID"] != 0)?$Db->getNameFromTypeId((int)$attacker['weaponTypeID']):"Unknown") ."\n";
   $attckr .= "Damage Done: ". $attacker["damageDone"] . "\n";
  }
  
  $attckr .= "\n";
  
  $key = (string)$attacker["characterID"].(string)$attacker["shipTypeID"].(string)$attacker["corporationID"];
  
  $attackers[$key] = $attckr;
  $dmg[$key] = (int)$attacker["damageDone"];
  
  $dmgtot += (int)$attacker["damageDone"];
 }
  
 $ret .= "Damage Taken: $dmgtot\n";
 $ret .= "\nInvolved parties:\n\n";
 
 arsort($dmg);
 
 foreach ($dmg as $attacker => $d) 
  $ret .= $attackers[$attacker];

 $dropped = "";
 $destroyed = ""; 

 $flags = array(
  5  => "Cargo",
  87 => "Drone Bay",
  89 => "Installed Implant"
 );
 
 foreach($kill->rowset[1]->row as $item) {
  $qDropped = (int)$item["qtyDropped"];
  $qDestryd = (int)$item["qtyDestroyed"];
  
  $type = $Db->getNameFromTypeId((int)$item['typeID']); 
	if(strpos($type," Blueprint") !== false) {
		if(isset($item["singleton"]) && (int)$item["singleton"] == 2) { // Value is 2 if the item is a blueprint copy, 0 for all other items, including blueprint originals. 
			$type .=" (Copy)";
		} else
			$type .=" (Original)";	 
	}
	
  $flag = (int)$item['flag'];
  
  if ($qDropped > 0) {
   $dropped .= $type;

   if ($qDropped > 1) 
    $dropped.=", Qty: $qDropped";  
   
   if (isset($flags[$flag]))
    $dropped .= " (".$flags[$flag].")";
    
   $dropped .="\n";
  }
  
  if ($qDestryd > 0) {
   $destroyed .= $type;
  
   if ($qDestryd > 1) 
    $destroyed.=", Qty: $qDestryd";  
   
   if (isset($flags[$flag]))
    $destroyed .= " (".$flags[$flag].")";
    
   $destroyed .="\n";
  }
  
  if ($item->rowset) 
   foreach($item->rowset->row as $item) {
    $qDropped = (int)$item["qtyDropped"];
    $qDestryd = (int)$item["qtyDestroyed"];
    
    $type = $Db->getNameFromTypeId((int)$item['typeID']);
    
    if ($qDropped > 0) 
     $dropped .= $type.(($qDropped > 1)?", Qty: $qDropped":"")." (In Container)\n";
    
    if ($qDestryd > 0) 
     $destroyed .= $type.(($qDestryd > 1)?", Qty: $qDestryd":"")." (In Container)\n"; 
   }
 }

 if ($destroyed != "") $ret .= "Destroyed items:\n\n$destroyed\n";
 if ($dropped != "")   $ret .= "Dropped items:\n\n$dropped\n";
 
 return $ret;
}
 ?>