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

function is_interesting($id) {
	 global $Db;
	 
	 if (!$Db->isTypeIdShip($id))
		  return false;
	 
	 $item = $Db->getTypeFromTypeId($id);
	 
	 if (in_array($item["groupID"], array(
		  898,
		  900,
		  547,
		  485,
		  902,
		  883,
		  941,
		  513,
		  963
	 )))
		  return true; // dreads, carriers, blackops, mauraders, orca, rorqual, jump freighters, freighters, strat cruisers
	 
	 if (in_array($item["typeID"], array(
		  17715,
		  17718,
		  17722,
		  11011,
		  17922,
		  17720,
		  17920,
		  17738,
		  17736,
		  17918,
		  17740,
		  17932
	 )))
		  return true; // faction bs and cruisers
	 
	 $name = $Db->getNameFromTypeId($id);
	 
	 // navy ships
	 
	 if (strpos($name, "Issue") !== false)
		  return true;
	 if (strpos($name, "Federation") !== false)
		  return true;
	 if (strpos($name, "Navy") !== false)
		  return true;
	 if (strpos($name, "Fleet") !== false)
		  return true;
	 
	 return false;
}
function type_check($type, $station) {
	 global $Db, $shpgroups, $owngroups, $interesting, $facmods, $offmods;
	 
	 if (array_key_exists((int) $type["groupID"], $shpgroups))
		  if (!isset($owngroups[$type["groupID"]])) {
				$owngroups[$type["groupID"]] = 1;
		  } else
				$owngroups[$type["groupID"]]++;
	 
	 if (is_interesting($type["typeID"])) {
		  $interesting .= "&nbsp;<b>" . $type['typeName'] . "</b> in $station<br>";
		  return;
	 }
}

function is_pos_fuel($type) {
	 $typeId  = $type["typeID"];
	 $groupId = $type["groupID"];
	 
	 if (in_array($typeId, array(
		  3683 /*oxygen*/ ,
		  9848 /*robotics*/ ,
		  9832 /*coolant*/ ,
		  44 /*uranium*/ ,
		  3689
		  /* mech parts */
	 )))
		  return true;
	 
	 if ($groupId == 423 || $groupId == 427)
		  return true;
	 
	 return false;
}

function contains_pos_fuel($item) {
	 global $Db, $pos_fuels;
	 $type = $Db->getTypeFromTypeId($item["typeID"]);
	 
	 if (isset($item["contents"])) {
		  foreach ($item["contents"] as $itemIdC => $itemC)
				if (contains_pos_fuel($itemC))
					 return true;
		  
	 } else
		  return is_pos_fuel($type);
}

function is_space($item) {
	 if (!isset($item['locationID']))
		  return false;
	 
	 return $item['locationID'] < 60000000;
}

function item_display($station, $itemId, $item, $lvl = 0)
{
	 global $Db, $hrwidth, $pos_fuels, $xpandall;
	 $type = $Db->getTypeFromTypeId($item["typeID"]);
	 $name = $type["typeName"];
	 
	 /* New field - rawQuantity

Items in the AssetList (and ContractItems) now include a rawQuantity attribute if the quantity in the DB is negative. Negative quantities are in fact codes, -1 indicates that the item is a singleton (non-stackable). If the item happens to be a Blueprint, -1 is an Original and -2 is a Blueprint Copy. For further information about negative quantities see this devblog http://www.eveonline.com/devblog.asp?a=blog&nbid=2324 */
	 if ((strpos($name," Blueprint") !== false) && isset($item["rawQuantity"]) && (int)$item["rawQuantity"] == -2) 
			$name.=" (Copy)";	
	
	 $output = "";
	 
	 if ($pos_fuels && !contains_pos_fuel($item))
		  return;
	 
	 if ($type == "") {
		  $name = "<b><span style='font-size:80%;'>[UNKNOWN ITEM TYPE " . $item["typeID"] . "]</span></b>";
	 } else
		  type_check($type, $station);
	 
	 $space = "";
	 for ($i = 0; $i < $lvl; $i++)
		  $space .= "&nbsp;";
	 
	 if (isset($item["contents"])) {
		  $output .= "$space<a href=\"#\" onclick=\"return toggle_visibility('i" . $itemId . "');\">";
		  $output .= "$name [" . count($item["contents"]) . "]</a><br>\n";
		  $output .= "<div id=\"i" . $itemId . "\" style=\"" . (($xpandall && (!$pos_fuels || !is_space($item))) ? "display:block;" : "display:none;") . "\">\n<HR align=left>\n";
		  
		  foreach ($item["contents"] as $itemIdC => $itemC) {
				$output .= item_display($station, $itemIdC, $itemC, $lvl + 1);
		  }
		  
		  $output .= "<HR align=left></div>";
	 } else if (!$pos_fuels || ($pos_fuels && is_pos_fuel($type)))
		  $output .= $space . ($item["quantity"] == "1" ? "" : $item["quantity"] . " x ") . $name . "<br>\n";
	 return $output;
}
		
class auditorAssetsPage extends auditorPage {

	public function GetName() { return "assets"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return AssetList; }
	public function GetOutput($Db) {
		global $shpgroups, $owngroups, $interesting, $facmods, $offmods;
	 
		$Assets = new eveApiAssets($Db);
		
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();

		if (!$Assets->fetch(CHAR_ID, USER_ID, API_KEY,CORP_MODE)) {
			$this->Output = $Assets->Error;
			return false;
		}

		$this->Updated = APITime($Assets);
		$this->Title = "Assets for " . USER_NAME;

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;

		$Db->updateConqStations();

		$result = $Db->selectWhere("invGroups",['categoryID'=>6],['groupName','groupID']);
		$shpgroups = array();
		if ($result != false) {
			if ($result->rows > 0) {
				foreach ($result->results as $row){
					$shpgroups[$row["groupID"]] = $row["groupName"];
				}
			}
		}
		$owngroups = array();
		$hrwidth   = 450;
		$start     = true;

		$last        = "";
		$interesting = "";

		$facmods = 0;
		$offmods = 0;

		$pos_fuels = isset($_GET['posfuels']);
		$xpandall  = isset($_GET['xpandall']);
		/*
		if (isset($_GET['query'])) {
		$query = array();
		$pairs = explode(";",$_GET['query']);
		foreach($pairs as $arg) {
		$arg2 = explode(":",$arg);

		switch($arg2[0]) {
		case "type": $query[$arg2[1]] = "typeID"; break;
		case "group": $query[$arg2[1]] = "typeID"; break;
		case "cat": $query[$arg2[1]] = "cat"; break;
		}
		}
		}
		*/

		$this->Output .= "<table><tr><td  VALIGN=\"top\" >";
		$this->Output .= $Assets->totalCt . " items total in " . count($Assets->assetsByLocation) . " locations<br>";
		if (!$xpandall) {
			 $this->Output .= "<a href=\"$full_url&view=assets&xpandall\">Expand all entries</a><br>";
		} else
			 $this->Output .= "<a href=\"$full_url&view=assets\">Collapse all entries</a><br>";
		$this->Output .= "<br><table class='fancy2'>";

		foreach ($Assets->assetsByLocation as $location => $items) {
			 $station = $Db->getLocationNameFromId($location);
			 
			 /*
			 if ($station == null)
			 $station = $item["locationID"];
			 */
			 if ($pos_fuels) {
				  $bad = true;
				  foreach ($items as $itemId => $item)
						if (contains_pos_fuel($item))
							 $bad = false;
				  
				  if ($bad)
						continue;
			 }
			 
			 $this->Output .= "<tr><td style='cursor:pointer;' onclick=\"return toggle_visibility('l$location');\"><a href=\"#\" onclick=\"return false\">";
			 $this->Output .= "<b>" . $station . "</b></a>" . ($location < 60000000 ? " (in space)" : "") . "</td><td align=right><b>" . count($items) . "&nbsp;item" . (count($items) == 1 ? "" : "s") . "</b>&nbsp;&nbsp;&nbsp;<br></td></tr>";
			 $this->Output .= "<tr><td colspan=2><div id=\"l$location\" style=\"" . ($xpandall ? "display:block;" : "display:none;") . "\"><br>\n";
			 
			 foreach ($items as $itemId => $item)
				  $this->Output .= item_display($station, $itemId, $item);
			 
			 $this->Output .= "<br></div></td></tr>\n";
		}
		$this->Output .= "</table></td><td valign=top>";
		$this->Output .= "Significant assets:";
		$this->Output .= "<br><br>";

		if ($interesting != "") {
			 $this->Output .= $interesting;
		} else
			 $this->Output .= "&nbsp;Character has no significant assets.<br>";

		$this->Output .= "<br>";

		$namedowngroups = array();
		foreach ($owngroups as $group_id => $cnt)
			 $namedowngroups[$shpgroups[$group_id]] = $cnt;

		ksort($namedowngroups);
		$this->Output .= "&nbsp;Ships by Group:<br><br>";

		$this->Output .= "<table>";
		$this->Output .= "<tr><th>type</th><th># owned</th></tr>";
		foreach ($namedowngroups as $group => $cnt)
			 $this->Output .= "<tr><td>&nbsp;$group&nbsp;</td><td>$cnt</td></tr>\n";

		$this->Output .= "</table>";
		$this->Output .= "</td></tr></table>";
						
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}

$registered_pages[] = new auditorAssetsPage();
 ?>