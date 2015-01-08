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

function shortDateTime($str) {
	if ($str == "") return "";
	$time = strtotime((string)$str);
	return date("[m.d.y] H:i",$time);
}

function timeToColor($secs) {
	if ($secs < 3600)
		return "#ff0000; font-weight: bold";
		
	if ($secs < 3600 * 24) 
		return "ffff00";
		
	return "ffffff";
}

function shortVolume($str) {
	$let = "";
	$volume = (float)$str;
	
	if ($volume > 1000) {
		$volume = $volume/1000;
		$let = "k";
	}
	
	if ((int)$volume == $volume) {
		return number_format($volume) . " {$let}M3";
	} else
		return number_format($volume,1) . " {$let}M3";
}

class auditorContractsPage extends auditorPage {
	public function GetName() { return "contracts"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return $corp ? corp_Contracts : Contracts; }
	public function GetOutput($Db) {
		global $sort,$ord,$cols; // req for sorting
		
		$Contracts = new eveApiContracts($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Contracts->fetch(CHAR_ID, USER_ID, API_KEY, CORP_MODE)) {
			$this->Output = $Contracts->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;

		
		$Db->updateConqStations();
		
		
		$this->Updated = APITime($Contracts);
		$this->Title = "Contracts for ".USER_NAME;

		if (isset($_GET['testing']))
			die("<pre>".print_r($Contracts->entries,true)."</pre>");
		
		$ord = "ASC";

		$cols = array(
			 //"contractID",
			 "Type",
			 "status", 
			 "System",
			 "dateIssued",
			 "dateFinished",
			 "issuer",
			 "contractor",
			 "Price",
			 "volume",
			 "desc"
		);

		$sort = null;
		
		$this->Output .= sort_ctrl();

		//$this->Output .= "<h5 style=\"display: inline\">### PAGE UNDER CONSTRUCTION - WATCH FOR ANVILS ###</h5>";
			 
		if (count($Contracts->entries) > 0) {
			 $this->Output .= <<<LEGENDEND
<table border="1" style="font-size: 80%; border-style: solid;">

<tr><th colspan="2">LEGEND</th></tr>
<tr><th>time format</th><td>[Month.Days.Years] Hours:Minutes</td></tr>
<tr><th>blue row:</th><td>this contract is on behalf of your corporation</td></tr>
<tr><th>orange volume:</th><td>volume is greater than 30,000 m3</td></tr>
<tr><th>yellow time:</th><td>less than 24hrs left on auction</td></tr>
<tr><th>red time:</th><td>less than 1 hour left on auction</td></tr>
<tr><th>red price:</th><td>this is the price that was/will be paid <b style="color:red">by</b> the contract acceptor</td></tr>
<tr><th>green price:</th><td>this is the price that was/will be paid <b style="color:#0f0">to</b> the contract acceptor</td></tr>
<tr><th>&lt;assignee&gt;</th><td>contract is assigned to assignee but has not been accepted</td></tr>
</table><br>
LEGENDEND;
			 $this->Output .= count($Contracts->entries) . " entries. <b>Click an entry to get contract details.</b><br><br>";
			  
			  
			 $this->Output .= "<table class=\"contracts_table\" style=\"font-size:83%;\" border=1>";
			 $this->Output .= "<tr>";
			 
			 foreach ($cols as $col) {
				  $this->Output .= "<th>";
				  if (!isset($nosorting))
						$this->Output .= "<a href=\"$full_url&view=contracts&sort=$col&order=" . (($sort == $col && $ord == "ASC") ? "DESC" : "ASC") . "\">";
				  $this->Output .= $col;
				  if (!isset($nosorting))
						$this->Output .= "</a>";
				  $this->Output .= "</th>";
			 }
			 
			 $this->Output .= "</tr>";
			 
			 $entries = $Contracts->entries;	
			
			foreach ($entries as &$entry) {
			  if ((string)$entry["availability"] == "Private" && $entry["acceptor"] == "") {
						$entry["contractor"] = $entry["assignee"];		  
				  } else if ($entry["acceptor"] == "") {
						if (isset($entry["bidder"])) {
							if ($entry["bidder"] == "") {
								$entry["contractor"] = "";
							} else
								$entry["contractor"] = "$entry[bidder]";		
						} else
							$entry["contractor"] = "";		
				  } else
						$entry["contractor"] = $entry["acceptor"];
						
					$entry["Price"] = (($entry["reward"] > 0 || $entry["reward"] == $entry["price"]) ? -1 :1) * ((float)($entry["price"] + (float)$entry["reward"]));
			}
			unset($entry);
			 if ($sort != null && array_key_exists($sort, $entries[0])) 
				 usort($entries, "globl_sortfunc");

			 $alt_b = false;
			 
			 $rowclass = " class=\"nohover" .  (isset($_GET['expandall']) ?"":" contractbody") . "\"";

			 foreach ($entries as $entry) {
				  $alt_b = !$alt_b;
				  $hasItems = isset($entry["buying"]) && ($entry["sellingItems"] != "" || $entry["buyingItems"] != "");
					
				  $this->Output .= "<tr onclick=\"toggle_row_visibility('contract".$entry["contractID"]."'); return false;\" class=\"" . ($alt_b?"main":"alt") . ((($entry["forCorp"]==1)) ? " corp_entry_row" : "") ." hover clickablerow\">";
				  //$this->Output .= "<th scope=\"row\">" . $entry["contractID"] . "</th>";
				  $this->Output .= "<th scope=\"row\">" . $entry["Type"] . "</th>";
				  
				  if (CONTRACT_ACTIVE($entry["status"])) {
						$this->Output .= "<td class=\"pendingcontractstatus\">" . $entry["status"] . "</td>";
				  } else if ($entry["status"] == "Failed") {
						$this->Output .= "<td class=\"failedcontractstatus\">FAILED</td>";
				  	} else if ($entry["status"] == "Rejected") {
						$this->Output .= "<td class=\"failedcontractstatus\">Rejected</td>";
				  } else
						$this->Output .= "<td class=\"contractstatus\">" . $entry["status"] . "</td>";
					
				  $this->Output .= "<td>" . $entry["System"] . "</td>";
				  
				  $this->Output .= "<td>" . shortDateTime($entry["dateIssued"]) . "</td>";
				  
				  if (CONTRACT_ACTIVE((string)$entry["status"])) {
						if ($entry["timeRemaining"] <= 3600 * 24) {
							$this->Output .= "<td style=\"color: " . timeToColor($entry["timeRemaining"]) . "\">";
						} else
							$this->Output .= "<td>";		
						$this->Output .= niceTime($entry["timeRemaining"],2) . " left</td>";
				  } else if ((string)$entry["status"] == "Failed") { 
						$this->Output .= "<td class=\"failedcontractstatus\">FAILED</td>";			
				  } else if ($entry["status"] == "Rejected") {
						$this->Output .= "<td class=\"failedcontractstatus\">Rejected</td>";
				  } else if ($entry["dateFinished"] != "") {
						$this->Output .= "<td>" . shortDateTime($entry["dateFinished"]) . "</td>";
				 } else 
						$this->Output .= "<td>&nbsp;</td>";
				 $this->Output .= "<td>" . $entry["issuer"] . "</td>";
				 			 
				  if ((string)$entry["availability"] == "Private" && $entry["acceptor"] == "") {
						$this->Output .= "<td>&lt;" . $entry["assignee"] . "&gt;</td>";		  
				  } else if ($entry["acceptor"] == "") {
						if (isset($entry["bidder"])) {
							if ($entry["bidder"] == "") {
								$this->Output .= "<td style=\"color:grey;\">&lt;no bids&gt;</td>";
							} else
								$this->Output .= "<td style=\"color:grey;\">&lt;bid $entry[bidder]&gt;</td>";		
						} else
							$this->Output .= "<td style=\"color:grey;\">&lt;public&gt;</td>";		
				  } else
						$this->Output .= "<td>" . $entry["acceptor"] . "</td>";


				  $this->Output .= "<td align=right style=\"color: " . (($entry["reward"] > 0 || $entry["reward"] == $entry["price"]) ? "#007700" : "#BB0000") . ";\">" . number_format((float)$entry["price"] + (float)$entry["reward"],2) . " ISK</td>";
				  
				  if ($entry["volume"] > 30000) {
						$this->Output .= "<td style=\"color: #FFA500\">" . shortVolume($entry["volume"]) . "</td>";
				  } else
						$this->Output .= "<td>" . shortVolume($entry["volume"]) . "</td>";
				  
				  $this->Output .= "<td width=\"500\""/*.($hasItems ? " rowspan=\"2\"" : "")*/ .">" . $entry["desc"];
				  
					if ($hasItems) {
						if ($entry["desc"] != "")
							$this->Output .= "<br>";
						$this->Output .= "<span class=\"contract_items\">";
				
						if ($entry["sellingItems"] != "") {
							switch($entry["Type"]) {
								case "Trade": $this->Output .= "<b>Offering:</b> "; break;
								case "Gift": $this->Output .= "<b>Giving:</b> "; break;
								default: $this->Output .= "<b>Selling:</b> "; break;
							}
							$this->Output .= "$entry[sellingItems]<br>";
						}
						
						if ($entry["buyingItems"] != "") 
							$this->Output .= ($entry["Type"] != "Trade" ? "<b>Buying:</b> ": "<b>Looking for:</b> ") . "$entry[buyingItems]";
						
						$this->Output .= "</span>";
				  }

				  $this->Output .= "&nbsp;</td>\n";
				  $this->Output .= "</tr>\n";
				  
				  /// BEGIN CONTRACT DETAILS
				  $this->Output .= "<tr$rowclass id=\"contract".$entry["contractID"]."\"><td colspan=\"" . count($cols) ."\">";
				  $this->Output .= "<a name=\"".$entry["contractID"]. "\"></a><h3>";
				  
				  if ($entry["title"] != "")
					$this->Output .= "$entry[title] - ";
					
				  if ($entry["type"] == "ItemExchange") {
						$this->Output .= "Item Exchange [{$entry["Type"]}]";
				  } else
						$this->Output .= "{$entry["Type"]}";
					$this->Output .= " - $entry[status]</h3>";
									  	
				  $this->Output .= "<b>Location</b>: {$entry["startStation"]}<br>";
				  
				  if ($entry["type"] == "Courier") 
						$this->Output .= "<b>Destination</b>: {$entry["endStation"]}({$entry["endStationID"]})<br>";
		
				  $this->Output .=  "<b>Posted</b>: $entry[dateIssued]<br>";
				  				  
				  if ((float)$entry["acceptorID"] != 0 && $entry["type"] == "Courier")
						$this->Output .=  "<b>Accepted</b>: $entry[dateAccepted]<br>";
				  
				  if ((string)$entry["status"] != "Completed" && (string)$entry["status"] != "Deleted" && (string)$entry["status"] != "Failed") {
						$this->Output .= "<b>Remaining</b>: <span";
						if ($entry["timeRemaining"] <= 3600 * 24) {
							$this->Output .= " style=\"color: " . timeToColor($entry["timeRemaining"]) . "\">";
						} else
							$this->Output .= ">";		
						$this->Output .= niceTime($entry["timeRemaining"],3) . "</span><br>";
				  } else 
$this->Output .=  "<b>Finished</b>: $entry[dateFinished]<br>";
				  	
				  $this->Output .= "<b>Contractor</b>: ";
				  
				  if ((string)$entry["availability"] == "Private" && $entry["acceptor"] == "") {
						$this->Output .= "&lt;" . $entry["assignee"] . "&gt;";		  
				  } else if ($entry["acceptor"] == "") {
						if (isset($entry["bidder"])) {
							if ($entry["bidder"] == "") {
								$this->Output .= "<span style=\"color:grey;\">&lt;no bids&gt;</span>";
							} else
								$this->Output .= "<span style=\"color:grey;\">&lt;bid $entry[bidder]&gt;</span>";		
						} else
							$this->Output .= "<span style=\"color:grey;\">&lt;public&gt;</span>";		
				  } else
						$this->Output .= $entry["acceptor"];
						
				  $this->Output .= "<br>";
				  
				  if (($entry["reward"] > 0 || $entry["reward"] == $entry["price"])) {
						$this->Output .= "<b>Reward</b>: <span style=\"color:007700\">";
				  } else 
						$this->Output .= "<b>Price</b>: <span style=\"color:BB0000\">";	  
						
				  $this->Output .= number_format((float)$entry["price"] + (float)$entry["reward"],2) . " ISK</span></br>";
	    
				  if ((float)$entry["collateral"] != 0)
						$this->Output .=  "<b>Collateral</b>: <span style=\"color:#f00;\">".number_format((float)$entry["collateral"],2)." ISK</span></br>";
					
				  $this->Output .= "<b>Volume</b>: ";
				  
				  if ($entry["volume"] > 30000) {
						$this->Output .= "<span style=\"color: #FFA500\">" . number_format($entry["volume"],2) . " m3</span>";
				  } else
						$this->Output .= number_format($entry["volume"],2) . " m3";
				  
				  $this->Output .= "<br>";
				  			  
				  if ($hasItems) {
						$this->Output .= "<br><table class=\"contracts_wtt\"><tr>";
						if ($entry["sellingItems"] != "")
							$this->Output .= "<th> " . ($entry["Type"] != "Trade" ? ($entry["Type"] == "Gift" ? "<b>Giving</b>:" :"<b>Selling</b> "): "<b>Offering:</b> ") . "</th>";
							
						if ($entry["buyingItems"] != "") 
							$this->Output .= "<th> " . ($entry["Type"] != "Trade" ? "<b>Buying</b> ": "<b>Looking for:</b> ") . "</th>";	
						
						$this->Output .= "</tr><tr>";
						if ($entry["sellingItems"] != "")
							$this->Output .= "<td>".str_replace("\n","<br>",$entry["sellingItemsList"])."</td>";
							
						if ($entry["buyingItems"] != "") 
							$this->Output .= "<td>".str_replace("\n","<br>",$entry["buyingItemsList"])."</td>";	
							
						$this->Output .= "</tr></table>";
				   }
				  $this->Output .= "";
				  $this->Output .= "<br><h3 style=\"font-size: 150%; border-bottom: 0px;\"><a href=\"#\" onclick=\"toggle_row_visibility('contract".$entry["contractID"]."'); return false;\">[Hide]</a></h3>";
				  $this->Output .= "";
				  $this->Output .= "";
				  $this->Output .= "</td></tr>\n";
				  
			 }
			 
			 $this->Output .= "</table>";
			 
			 if (isset($_GET['contract'])) {
				$this->Output .= "<script>toggle_row_visibility('contract$_GET[contract]'); location.hash='$_GET[contract]';</script>";
			 }
		} else
			 $this->Output .= (CORP_MODE ? "Corporation" : "Character") . " has no Contracts in the last 3 months.<br>";
			
		
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}

$registered_pages[] = new auditorContractsPage();
 ?>