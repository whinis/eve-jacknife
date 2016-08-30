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
// api assets stuff

$sortDb = null;

function sortfunc_locname($a, $b) {
 global $sortDb;

 if ($a["locationID"] == $b["locationID"]) {
  return 0;
 }
 
 $as = $sortDb->getLocationNameFromId($a["locationID"]);
 $bs = $sortDb->getLocationNameFromId($b["locationID"]);
 
 return ($as < $bs) ? -1 : 1;
}
 
function sortfunc_contents($a, $b) {
 $as = isset($a["contents"])?1:-1;
 $bs = isset($b["contents"])?1:-1;
 
  if ($as == $bs) {
  return 0;
 }
 
 return ($as > $bs) ? -1 : 1;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiAssets extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
 protected $assets;
 public $assetsByLocation;
 public $totalCt;
 
 public function fetch($chid,$usid,$apik,$corp = false) {
     if(SSO_MODE)
         $args = array("characterID"=>$chid,"accessToken"=>$usid);
     else
         $args = array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik);
     return $this->fetch_xml("/".($corp?"corp":"char")."/AssetList.xml.aspx",$args);
 }

 public function loadAPI() {
  $result = $this->api->xpath("/eveapi/result/rowset[@name='assets']/row");
   
  $this->assets = array();
  foreach($result as $entry) {
   $contents = getContents($entry);
   uasort($contents,"sortfunc_contents");
   $this->assets[(float)$entry["itemID"]] = $contents;
  }
  
  $itemTypes = array();
  $itemGroups = array();
  $allLocs = array();

  foreach($this->assets as $item) {
   $itemTypes = add_items($itemTypes, $item);
   $allLocs[locationTranslate($item["locationID"])] = 1;
  }
  
  $this->Db->cacheItemTypes(array_keys($itemTypes));
  $this->Db->cacheLocationIds(array_keys($allLocs));

  foreach($this->assets as $item)
        $itemGroups = add_groups($this->Db,$itemGroups, $item);
   
  $this->Db->cacheGroupTypes(array_keys($itemGroups));

  global $sortDb;
  $sortDb = $this->Db;
  uasort($this->assets,"sortfunc_locname");
   
  $this->assetsByLocation = array();
  foreach($this->assets as $itemId => $item) {
   $locid = locationTranslate($item["locationID"]);
   if (!isset($this->assetsByLocation[$locid])) {
    $this->assetsByLocation[locationTranslate($locid)] = array($item['itemID'] => $item);
   } else {
       $this->assetsByLocation[locationTranslate($locid)][$item['itemID']] = $item;
   }
  }
      
  $this->totalCt = count($this->api->xpath("//row"));
	//print_r($this->assets);

  return true;
 }
}

function add_items($itemTypes, $item) {
 $itemTypes[$item["typeID"]] = true;

 if (isset($item["contents"])) 
  foreach($item["contents"] as $item2) 
   $itemTypes = add_items($itemTypes, $item2);
   
 return $itemTypes;
}

function add_groups($Db,$itemGroups, $item) {
 $type = $Db->getTypeFromTypeID($item["typeID"]);
 if ($type["groupID"] != "")
  $itemGroups[$type["groupID"]] = true;

 if (isset($item["contents"])) 
  foreach($item["contents"] as $item2) 
   $itemGroups = add_groups($Db,$itemGroups, $item2);
   
 return $itemGroups;
}

function getContents($entry) {
 $item = array();

 foreach($entry->attributes() as $name => $value)  // copy item attributes
  $item[(string)$name] = (float)$value;  

 if (count($entry) != 0) { // has sub-item
  $item["contents"]= array();
  foreach ($entry->rowset->row as $row)
   $item["contents"][(float)$row["itemID"]] = getContents($row);
   
  uasort($item["contents"],"sortfunc_contents");
 }
 return $item;
}
 ?>