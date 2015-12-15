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

//
//SELECT * FROM `contract_items` WHERE buying='a:0:{}' AND selling='a:0:{}' 

$CONTRACT_STATUS_MAPPING = array("Outstanding" => 10,
    "InProgress" => 12,
    "CompletedByIssuer" => 12,
    "CompletedByContractor" => 12,
    "Expired" => 12,
    "Unclaimed" => 12,
    "Completed" => 8,
    "Cancelled" => 8,
    "Rejected" => 11,
    "Failed" => 8,
    "Deleted" => 8,
    "Reversed" => 8);
	 	 
function CONTRACT_ACTIVE($offset) {
	$CONTRACT_ACTIVE_MAPPING = array("Outstanding" => 1,
    "InProgress" => 1,
    "Expired" => 0,
    "CompletedByIssuer" => 0,
    "CompletedByContractor" => 0,
	 "Unclaimed" => 0,
    "Completed" => 0,
    "Cancelled" => 0,
    "Rejected" => 0,
    "Failed" => 0,
    "Deleted" => 0,
    "Reversed" => 0);
	return $CONTRACT_ACTIVE_MAPPING[(string)$offset];
}

function sortfunc_items_ctrt($a, $b) {
	if ($a["metaLevel"] == $b["metaLevel"]) {
		if ($a["volume"] == $b["volume"]) 
			return 0;

		return (((int)$a["quantity"] * (int)$a["volume"]) > ((int)$b["quantity"] * (int)$b["volume"])) ? -1 : 1;
	}
	return ((int)$a["metaLevel"] > (int)$b["metaLevel"]) ? -1 : 1;
}

function bids_sortfunc($a,$b) {
	if ($a["amount"] == $b["amount"]) return 0;
	return ((float)$a["amount"] > (float)$b["amount"]) ? -1 : 1;
}

function contracts_default_sortfunc($a, $b) {
	global $CONTRACT_STATUS_MAPPING;

	$as = (string)$a["status"];
	$bs = (string)$b["status"];

	if (($as == $bs) || ($CONTRACT_STATUS_MAPPING[$as] == $CONTRACT_STATUS_MAPPING[$bs])) {
		if (isset($a["timeRemaining"]) && $a["timeRemaining"] != 0) {
			return (int)$a["timeRemaining"] < (int)$b["timeRemaining"]  ? -1 : 1;
		} else
			return strtotime($a["dateIssued"]) > strtotime($b["dateIssued"]) ? -1 : 1;
		//return (int)$a["contractID"] > (int)$b["contractID"]  ? -1 : 1;
	}
	
	return ($CONTRACT_STATUS_MAPPING[$as] > $CONTRACT_STATUS_MAPPING[$bs]) ? -1 : 1;
}

function listItems($Db, $items, &$commadStr, &$listdStr) {
	// returns string of items, ordered by PLEX, ships, towers, metalevel (tiebreaker: qty * volume)
	if (count($items) == 0) 
		return "";
	
	$sorted = array();
	
	foreach ($items as $item) {
		if (isset($sorted[$item[0]])) {
			$sorted[$item[0]]["quantity"] += (int)$item[1];
			continue;
		}

		$type = $Db->getTypeFromTypeId($item[0]);
		if (!$type) 
			continue;
			
		$meta = 0;
	
		if ((int)$item[0] == 29668) { // PLEX
			$meta = 30; 
		} else if ($Db->isTypeIdShip($item[0])) {
			$meta = 25;
		} else if ($type["groupID"] == 365) {
			$meta = 20;
		} else {
			$meta = $Db->getMetaLevelForID($item[0]);
		}
		
		$type["quantity"] = $item[1];
		$type["metaLevel"] = $meta;
		$type["singleton"] = $item[2];
		$sorted[$item[0]] = $type;
	}
	
	uasort($sorted,"sortfunc_items_ctrt");

	$commadStr = "";
	$listdStr ="";

	foreach ($sorted as $item) {
		$name = $item["typeName"];

		if ($item["quantity"] == -2) 
			$name.=" (Copy)";	

		if ($item["quantity"] != 1) {
			$commadStr .= $item["quantity"] . " x $name, ";
			$listdStr .= $item["quantity"] . " x $name\n";
		} else {
			$listdStr .= "1 x $name\n";
			$commadStr .= "$name, ";
		}
	}

	$commadStr = rtrim($commadStr,", ");		
	$listdStr = rtrim($listdStr);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiContracts extends eveApi {
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public $entries;
	public $corp = false;
	public $getItems = true;
	public $hasAuctions = false;
	
	private $chid = "";
	private $usid = "";
	private $apik = "";
	private $typesToCache = array();
	private $bids = array();
	 
	public $start = 0;
	public $limit = 100;
	public $count = 0;
	 
	public function fetch($chid,$usid,$apik, $corp = false, $getItems = true) {
		$args = array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik);
		$this->chid = $chid;
		$this->usid = $usid;
		$this->apik = $apik;
		
		$this->corp = $corp;
		$this->getItems = $getItems;
		return $this->fetch_xml("/".($corp?"corp":"char")."/Contracts.xml.aspx",$args);
	}

	public function fetchItems($ids) {
		$items = array();

		$result = $this->Db->selectWhere(CONTRACT_CONTENTS_TABLE,['contractID'=>["IN",$ids]]);
		if ($result) {
			foreach($result->results as $row) {
				$id = (float)$row["contractID"];
				if (!isset($items[$id]))
					$items[$id] = array();

				$items[$id]["buying"] = unserialize($row["buying"]);
				$items[$id]["selling"] = unserialize($row["selling"]);

				foreach ($items[$id]["buying"] as $item)
					$this->typesToCache[$item[0]] = "1";
				foreach ($items[$id]["selling"] as $item)
					$this->typesToCache[$item[0]] = "1";
			}
	  }
		$this->Db->prepare("insert",CONTRACT_CONTENTS_TABLE,['id'=>'?','buying'=>'?','selling'=>'?']);
		foreach ($ids as $id) {
			if (isset($items[$id])) continue;
			
			$args = array("characterID"=>$this->chid,"keyID"=>$this->usid,"vCode"=>$this->apik,"contractID"=>$id);
			$result = cache_api_retrieve($this->Db,"/".($this->corp?"corp":"char")."/ContractItems.xml.aspx",$args, 60); // short cache as we use db in the future
			
			$buying = array();
			$selling = array();
			
			foreach ($result->value->xpath("/eveapi/result/rowset/row") as $item) {
				$newitem = array((float)$item["typeID"],(float)$item["quantity"], (float)$item["singleton"]);
				
				$this->typesToCache[(float)$item["typeID"]] = "1";
				if ($item["included"] == "1") {
					$selling[] = $newitem;
				} else 
					$buying[] = $newitem;	
			}
			
			$items[$id]["buying"] = $buying;
			$items[$id]["selling"] = $selling;
			$this->Db->execute(['id'=>$id,'buying'=>serialize($buying),'selling'=>serialize($selling)]);
		}
		return $items;
	}
	
	public function fetchBids($ids) {
		$this->hasAuctions = true;
		$args = array("characterID"=>$this->chid,"keyID"=>$this->usid,"vCode"=>$this->apik);
		//$sql    = "SELECT * FROM ".DB_PREFIX.CONTRACT_BIDS_TABLE." WHERE contractID IN (".implode(",",$ids).")";
      	//$result = $this->Db->query($sql);
		$result = $this->Db->selectWhere(CONTRACT_BIDS_TABLE,['contractID'=>["IN",$ids]]);
        
      $bids = array();
		
		if ($result) {
			foreach ($result->results as $row){
				$id = (float)$row["contractID"];
				if (!isset($bids[$id]))
					$bids[$id] = array();

				$bids[$id][$row["bidID"]] = $row;
			}
	  	}
	  
	  	foreach ($bids as &$bidset)
			usort($bidset,"bids_sortfunc");

      $args = array("characterID"=>$this->chid,"keyID"=>$this->usid,"vCode"=>$this->apik);
		$result = cache_api_retrieve($this->Db,"/".($this->corp?"corp":"char")."/ContractBids.xml.aspx",$args);
		
		if ($result->error) {
			$this->bids = $bids;
			return;
		}
		
		$rows = $result->value->xpath("/eveapi/result/rowset/row");
		
		if (count($rows) == 0) {
			$this->bids = $bids;
			return;
		}

		$this->Db->prepare("insert",CONTRACT_BIDS_TABLE,['contractID'=>'?','bidID'=>'?','bidderID'=>'?','amount'=>'?','bidTime'=>'?']);
		foreach ($rows as $bid) {
			$id = (float)$bid["contractID"];
			$bidid = (float)$bid["bidID"];

			if (!isset($bids[$id])) {
				$bids[$id] = array();
			} else if (isset($bids[$id][$bidid])) 
				continue;
					
			$bids[$id][$bidid] = array((float)$bid["amount"],(float)$bid["bidderID"],(string)$bid["dateBid"]);
			$this->Db->execute(['contractID'=>$id,'bidID'=>$bidid,'bidderID'=>(float)$bid["bidderID"],'amount'=>(float)$bid["amount"],'bidTime'=>(string)$bid["dateBid"]]);
		}

		foreach ($bids as &$bidset)
			usort($bidset,"bids_sortfunc");

		$this->bids = $bids;
	}
	
	public function loadAPI() {
		$result = $this->api->xpath("/eveapi/result/rowset/row");
		usort($result,"contracts_default_sortfunc");

		if ($this->corp) {
			$filtered = array();
			foreach ($result as $entry) {
				if (($entry["issuerCorpID"] == CORP_ID && $entry["forCorp"] == "1") || $entry["acceptorID"] == CORP_ID || $entry["assigneeID"] == CORP_ID) {
					// TODO: should not depend on CORP_ID - api should be unaware of audit.php
					$filtered[] = $entry;
				}
			}
			$result = $filtered;
		}
		
		$result = array_slice($result,$this->start,$this->limit);

		$idsTR = array();
		$locations = array();
		
		$ctrctids = array();
		$nonCourierIDs = array();
		
		$this->count = count($result);
		
		foreach ($result as $entry) {
			$ctrctids[] = (float)$entry["contractID"];
			if ($entry["type"] != "Courier")
				$nonCourierIDs[] = (float)$entry["contractID"];
		}
					
		if ($this->getItems&&$nonCourierIDs) {
			$items = $this->fetchItems($nonCourierIDs);
		}
			
		foreach ($result as $entry) {
			$idsTR[(float) $entry["issuerID"]] = "1";
			$idsTR[(float) $entry["issuerCorpID"]] = "1";
			
			if ((float) $entry["assigneeID"] != 0)
				$idsTR[(float) $entry["assigneeID"]] = "1";
				
			if ((float) $entry["acceptorID"] != 0)
				$idsTR[(float) $entry["acceptorID"]] = "1";
				
			if ((string)($entry["type"]) == "Auction") {
				if (!$this->hasAuctions) {
					$this->fetchBids($ctrctids);
					if (count($this->bids) > 0)
						foreach ($this->bids as $bidset) 
							foreach ($bidset as $bid) 
								$idsTR[$bid["bidderID"]] = "1";
				}
			}
			
			$locations[locationTranslate((float) $entry["startStationID"])] = "1";
			$locations[locationTranslate((float) $entry["endStationID"])] = "1";		
		}
		
					
		$ids = idlookup($this->Db->link, array_keys($idsTR));
		$this->Db->cacheLocationIds(array_keys($locations)); // ensuring only one lookup of each

		$locations = array();
		
		foreach ($result as $entry) {	
			$locations[$this->Db->getSystemFromStationId(locationTranslate((float) $entry["startStationID"]))] = "1";
			$locations[$this->Db->getSystemFromStationId(locationTranslate((float) $entry["endStationID"]))] = "1";	
		}
		
		$this->Db->cacheLocationIds(array_keys($locations)); // system names
		
		$entries = array();

		foreach ($result as $entry) {	
			$new = array();
					
			foreach($entry->attributes() as $name => $value)  // copy item attributes
				$new[(string)$name] = (string)$value;  // todo 
				
			$cid = (float)$entry["contractID"];
					
			$new["issuer"] = $ids[(float) $entry["issuerID"]];
			$new["issuerCorp"] = $ids[(float) $entry["issuerCorpID"]];
			
			$new["assignee"] = ((float) $entry["assigneeID"] != 0) ? $ids[(float) $entry["assigneeID"]] : "";
			$new["acceptor"] = ((float) $entry["acceptorID"] != 0) ? $ids[(float) $entry["acceptorID"]] : "";
			
			$new["startStation"] = $this->Db->getLocationNameFromId(locationTranslate((float) $entry["startStationID"]));
			$new["endStation"] = $this->Db->getLocationNameFromId(locationTranslate((float) $entry["endStationID"]));	
			
			$new["startSystemID"] = $this->Db->getSystemFromStationId(locationTranslate((float) $entry["startStationID"]));
			$new["endSystemID"] = $this->Db->getSystemFromStationId(locationTranslate((float) $entry["endStationID"]));	
			
			$new["startSystem"] = $this->Db->getLocationNameFromId($new["startSystemID"]);
			$new["System"] = $new["startSystem"];
			$new["endSystem"] = $this->Db->getLocationNameFromId($new["endSystemID"]);	
										
			if ((string)$new["status"] == "CompletedByContractor") 
					$new["status"]  = "Unclaimed";	
					
			if (!CONTRACT_ACTIVE((string)$entry["status"])) {
				if ((string)$entry["status"] == "Failed") {
					$new["dateFinished"] = "FAILED"; 
				} else
					$new["dateFinished"] = (string)$entry["dateCompleted"]; 
				$new["timeRemaining"] = 0;		
			} else {
				if ((string)($entry["type"]) == "Courier" && (string)($entry["status"]) == "InProgress") {
					$new["dateFinished"] = "";// $entry["dateExpired"];
					$new["timeRemaining"] = ((int)(string)$entry["numDays"] * 3600 * 24) - (strtotime((string)$this->api->currentTime) - strtotime((string)$entry["dateAccepted"]));
				} else {						
					$new["timeRemaining"] = strtotime((string)$entry["dateExpired"]) - strtotime((string)$this->api->currentTime);
					if ($new["timeRemaining"] < 0) {
						$new["dateFinished"] = (string)$entry["dateExpired"];
						$new["timeRemaining"] = 0;
						$new["status"] = "Expired";
					}else
						$new["dateFinished"] = "";// $entry["dateExpired"];
				}
			}
					
			if ($entry["title"]  != "") {
				$new["desc"] = "<b>Title: </b>$entry[title]";
			} else 
				$new["desc"] = "";
			
			if ((string)($entry["type"]) == "Courier") {
				$new["desc"] .= ($new["desc"] = "" ? "<br>" :"") . "<b>Destination: </b>$new[endStation]";
				if ((float)$entry["collateral"] != 0)
					$new["desc"] .= "<br><b>Collateral: <span style=\"color:#aa0;\">".number_format((float)$entry["collateral"],2)." ISK</span></b>";
					
				if ((float)$entry["acceptorID"] != 0)
					$new["desc"] .= "<br><b>Accepted:</b> $entry[dateAccepted]</span>";
			}	
			
			if ((string)($entry["type"]) == "ItemExchange") {
				if ((float)$entry["reward"] > 0) {
					$new["Type"] = "WTB";
				} else if ((float)$entry["reward"] == (float)$entry["price"]) {
					$new["Type"] = "Gift";
				} else
					$new["Type"] = "WTS";
			} else {
				$new["Type"] = (string)($entry["type"]);

				if ((string)($entry["type"]) == "Auction") {		
					if (isset($this->bids[$cid])) {
						$bid = $this->bids[$cid][0];
						$new["price"] = $bid["amount"];
						$new["desc"] .= "<br><b>Last Bid:</b> ". $bid["bidTime"] . "</span>";
						$new["bidder"] = "#" . (count($this->bids[$cid])) . ", ". $ids[$bid["bidderID"]];
					} else $new["bidder"] = "";
				}
			}
			
			if ($this->getItems && (string)($entry["type"]) != "Courier") {
				$buying = $items[(float)$entry["contractID"]]["buying"];
				$selling = $items[(float)$entry["contractID"]]["selling"];
			
				if (count($buying) > 0 && count($selling) > 0)
					$new["Type"] = "Trade";
	
				$new["buying"] = $buying;
				$new["selling"] = $selling;
			}
	
			$entries[]= $new;			
		}

		if ($this->getItems) {
			if (count($this->typesToCache) > 0) {
				$types = array_keys($this->typesToCache);
				$this->Db->cacheItemTypes($types); 
				
				$groupsToCache = array();
				foreach ($types as $typeID) {
					$type = $this->Db->getTypeFromTypeId($typeID);
					$groupsToCache[] = $type["groupID"];
				}

				$this->Db->cacheGroupTypes($groupsToCache);
				$this->Db->cacheMetaLevelsIDs($types);
			}
			
			foreach ($entries as &$entry) {
				$entry["buyingItems"] = "";
				$entry["buyingItemsList"] = "";
				$entry["sellingItems"] = "";
				$entry["sellingItemsList"] = "";
					
				if (isset($entry["buying"])) {
					listItems($this->Db, $entry["buying"], $entry["buyingItems"], $entry["buyingItemsList"]);
					listItems($this->Db, $entry["selling"], $entry["sellingItems"], $entry["sellingItemsList"]);	
				} else {
					$entry["buying"] = array();
					$entry["selling"] = array();
				}	
			}
	
		}		
		usort($entries,"contracts_default_sortfunc");

		$this->entries = $entries;
		return true;
	}
}
  
 ?>