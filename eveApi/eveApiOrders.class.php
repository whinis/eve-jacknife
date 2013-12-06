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

// character orders page
 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiOrders extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public $entries;

	public function fetch($chid,$usid,$apik,$corp = false) {
		$args = array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik);
		return $this->fetch_xml("/".($corp?"corp":"char")."/MarketOrders.xml.aspx",$args,60*60);
	}

	public function loadAPI() {
		$orderstates = array(
			 0 => "Pending",
			 1 => "???",
			 2 => "Complete",
			 3 => "Cancelled",
			 4 => "Partial"
		);
	
		$results = $this->api->xpath("/eveapi/result/rowset[@name='orders']/row");
		
		if (CORP_MODE) {
			  $idsTR = array();
			  foreach ($results as $entry)
					$idsTR[] = (int) $entry["charID"];
			  
			  $ids = idlookup($this->Db->link, $idsTR);
		 }
		$entries=array();
		foreach ($results as $entry) {
			$new = array();

			$new["bid"]     = (int) $entry["bid"];
			$timeleft       = orders_timeleft($entry);
			$active         = ((int) $entry["orderState"] == 0) && ($timeleft > 0);
			$new["active"]  = $active;
			$new["date"]    = ($active) ? str_replace(" ", "&nbsp;", orders_time($timeleft)) : (string) $entry["issued"];
			$new["orderID"] = (int) $entry["orderID"];

			if (CORP_MODE) {
				$new["owner"] = $ids[(int) $entry["charID"]];
				$new["div"]   = (int) $entry["accountKey"] - 999;
			}

			$new["station"]   = $this->Db->getNameFromStationId((string) $entry["stationID"]);
			$new["initvol"]   = (int) $entry["volEntered"];
			$new["remaining"] = (int) $entry["volRemaining"];
			$new["minvol"]    = (int) $entry["minVolume"];
			$new["ostate"]    = (int) $entry["orderState"];
			$new["state"]     = $orderstates[(int) $entry["orderState"]];
			if ((int) $entry["orderState"] == 3 && (int) $entry["volEntered"] != (int) $entry["volRemaining"]) {
				$new["ostate"] = 4;
				$new["state"]  = "Partial";
			}
			$new["type"] = $this->Db->getNameFromTypeId((int) $entry["typeID"]);
			if ($new["type"] == "")
			$new["type"] = "<b><span style='font-size:80%;'>[UNKNOWN ITEM TYPE " . $entry["typeID"] . "]</span></b>";

			$range = (int) ($entry["range"]) . " jumps";

			switch ($range) {
				case 32767:
					$range = "Region";
					break;
				case 0:
					$range = "Station";
					break;
				case -1:
					$range = "Station";
					break;
				case 1:
					$range = "Adjacent";
					break;
			}

			$new["range"]      = $range;
			$new["accountKey"] = (int) $entry["accountKey"];
			$new["duration"]   = (int) $entry["duration"] . " days";
			$new["escrow"]     = (int) $entry["escrow"];
			$new["price"]      = (double) $entry["price"];
			$entries[]         = $new;
		}
		$this->entries = $entries;
		return true;
	}
}

function orders_timeleft($entry) {
	@$oldTz = date_default_timezone_get();
	date_default_timezone_set ("UTC");

	return strtotime((string)$entry["issued"]) + (24*60*60*(int)$entry["duration"])-time();

	date_default_timezone_set($oldTz);
}

function orders_time($timeLeft) {

	@$oldTz = date_default_timezone_get();
	date_default_timezone_set ("UTC");

	$days = (int)($timeLeft/(24*60*60));
	$hours = trimZeros(strftime("%H",$timeLeft));
	$mins = trimZeros(strftime("%M",$timeLeft));
	$secs = trimZeros(strftime("%S",$timeLeft));

	$tl = "";
	if ($days > 0) $tl .= $days ."D ";
	if ($hours > 0) $tl .= $hours ."H ";
	if ($mins > 0)  $tl .= $mins ."M ";
	if ($secs > 0)  $tl .= $secs ."S";

	date_default_timezone_set($oldTz);
	return $tl;
}
 ?>