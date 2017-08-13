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
/*
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
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveESIAssets extends eveESI {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
    protected $assets;
    public $assetsByLocation;
    public $totalCt;
 
    public function fetch($chid,$token,$corp = false) {
        return $this->fetch_esi("/characters/".$chid."/assets/",$token);
    }
    public function LoadAPI() {
        $result = $this->api;
        $assets = [];
        $nested = [];

        //index assets by id
        foreach($result as $entry) { //For now we will rewrite each and every entry to XML standards
            $entry['itemID'] = $entry['item_id'];
            unset($entry['item_id']);
            $entry['singleton'] = $entry['is_singleton'];
            unset($entry['is_singleton']);
            $entry['locationID'] = $entry['location_id'];
            unset($entry['location_id']);
            $entry['typeID'] = $entry['type_id'];
            unset($entry['type_id']);
            if($entry['singleton'] == true){
                $entry['quantity'] = 1;
            }
            $entry['rawQuantity'] = $entry['quantity'];
            if($entry['quantity'] <1){
                $entry['quantity'] = 1;
            }

            $assets[(string)$entry['itemID']]= $entry;
        }


        //get all nested containers
        foreach($result as $entry) {
            $locationID =(string)$entry['location_id'];
            if(isset($assets[$locationID])){
                if(!isset($nested[(string)$entry['location_id']])){
                    $nested[(string)$entry['location_id']] = [];
                }
                $nested[(string)$entry['location_id']][] = (string)$entry['item_id'];
                $assets[(string)$entry['location_id']]['contents'] = [];
            }
        }
        //properly nest items
        $maxiterations = 10;
        $int = 0;
        while(count($nested) > 0){
            if($int>$maxiterations)
                break;
            $int++;
            foreach($nested as $key=>$items){
                foreach($items as $item) {
                    if (isset($nested[(string)$item]))
                        continue;
                    $assets[(string)$key]['contents'][] = $assets[(string)$item];
                    unset($assets[(string)$item]);
                }
                unset($nested[(string)$key]);
            }
        }
        $this->assets = $assets;
        //var_dump($assets);
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

        $this->totalCt = count($result);
        //print_r($this->assets);

        return true;
    }
}
/*
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
*/
 ?>