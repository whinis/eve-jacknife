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

include_once("auditorPage.base.php");
define("gotobar", "<span style=\"font-size:80%;\">[go to: active <a href=\"#sa\">sell</a> <a href=\"#ba\">buy</a>&nbsp;|&nbsp;expired <a href=\"#se\">sell</a> <a href=\"#be\">buy</a> ]</span>");

function disp_orders($entries, $title, $exclude = array(), $totalType = false, $active = false)
{
	 global $cols, $full_url, $sort, $ord, $nosorting, $ordercolors;
	 $output = "";
	 if (count($entries) == 0) {
		  $output .= "$title: <span style=\"font-size:83%;\"><b>[NO DATA]</b></span><br><br>";
		  return;
	 }
	 
	 $output .= gotobar;
	 $output .= "<table class=\"fancy $title\" style=\"font-size:83%;\" border=1>";
	 $output .= "<tr><th align=center style=\"font-size:150%;\" colspan=\"" . (count($cols) - count($exclude)) . "\">$title</th></tr>";
	 $output .= "<tr>";
	 
	 $total = 0;
	 
	 foreach ($cols as $col) {
		  if (in_array($col, $exclude))
				continue;
		  
		  $output .= "<th>";
		  if (!isset($nosorting))
				$output .= "<a href=\"$full_url&view=orders&sort=$col&order=" . (($sort == $col && $ord == "ASC") ? "DESC" : "ASC") . "\">";
		  if ($active && $col == "date") {
				$output .= "timeleft";
		  } else
				$output .= $col;
		  if (!isset($nosorting))
				$output .= "</a>";
		  $output .= "</th>";
	 }
	 
	 $output .= "</tr>\n";
	 $alt = " class=\"main\"";
	 
	 foreach ($entries as $entry) {
		  if ($alt == " class=\"main\"") {
				$alt = " class=\"alt\"";
		  } else
				$alt = " class=\"main\"";
		  $output .= "<tr$alt>";
		  
		  if ($totalType) {
				$total += ($entry["initvol"] - $entry["remaining"]) * $entry["price"];
		  } else
				$total += $entry["remaining"] * $entry["price"];
		  
		  foreach ($cols as $col) {
				if (in_array($col, $exclude))
					 continue;
				
				switch ($col) {
					 case "type":
					 case "station":
						  $output .= "<td style=\"text-align:left;\">";
						  $output .= $entry[$col];
						  break;
					 case "state":
						  $output .= "<td style=\"color: #" . $ordercolors[(int) $entry["ostate"]] . ";\">";
						  $output .= $entry[$col];
						  break;
					 case "remaining":
						  $output .= "<td>";
						  if ($entry[$col] != "Cancelled")
								$output .= ($entry[$col]);
						  break;
					 case "price":
						  $output .= "<td>";
						  $output .= number_format((double) $entry[$col], 2) . " ISK";
						  break;
					 default:
						  $output .= "<td>";
						  $output .= $entry[$col];
						  break;
				}
				$output .= "</td>";
		  }
		  $output .= "</tr>\n";
	 }
	 $output .= "\n</table>&nbsp;&nbsp;&nbsp;<span style=\"font-size:83%;\">" . number_format($total, 2) . " ISK in " . count($entries) . " order" . (count($entries) == 1 ? "" : "s") . "</span><br><br>";
	 return $output;
}

class auditorOrdersPage extends auditorPage {

	public function GetName() { return "orders"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return MarketOrders; }
	public function GetOutput($Db) {
		global $sort,$ord,$cols; // req for sorting

		$Orders = new eveApiOrders($Db);

		$time_start = microtime_float();
		
		if (!$Orders->fetch(CHAR_ID, USER_ID, API_KEY,CORP_MODE)) {
			$this->Output = $Mail->Orders;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		
		$this->Updated = APITime($Orders);
		$this->Title = "Orders for " . USER_NAME;

		/////////////////////////////////////////////////

		$result = $Orders->entries;

		$cols = array(
			 "date",
			 "state",
			 "type",
			 "minvol",
			 "initvol",
			 "remaining",
			 "price",
			 "station",
			 "range"
		);

		if (CORP_MODE) {
			 $cols[] = "owner";
			 $cols[] = "div";
		}

		$ordercolors = array(
			 0 => "FFFFFF",
			 1 => "FFFFFF",
			 2 => "007700",
			 3 => "BB0000",
			 4 => "FFFF00"
		);
		
		$ord         = "DESC";
		$sort        = "date";

		$this->Output .= sort_ctrl(true);

		if (count($result) > 0) {
			 if ($sort != null && array_key_exists($sort, $result[0]))
				  usort($result, "globl_sortfunc");
			 
			 $sellorders = array();
			 $buyorders  = array();
			 
			 $exp_sellorders = array();
			 $exp_buyorders  = array();

			 foreach ($result as $entry)
				  if (!$entry["bid"]) {
						if ($entry["active"]) {
							 $sellorders[] = $entry;
						} else
							 $exp_sellorders[] = $entry;
				  } else if ($entry["active"]) {
						$buyorders[] = $entry;
				  } else
						$exp_buyorders[] = $entry;
			 
			 
			 if (!isset($_GET['sort'])) {
				  $sort = "type";
				  $ord  = "ASC";
				  usort($sellorders, "globl_sortfunc");
				  usort($buyorders, "globl_sortfunc");
			 }
			 
			 $this->Output .= "<h3>Active Orders</h3>";
			 $this->Output .= "<a name=\"sa\"></a>";
			 $this->Output .= disp_orders($sellorders, "Sell Orders", array(
				  "minvol",
				  "range",
				  "state"
			 ), false, true);
			 $this->Output .= "<a name=\"ba\"></a>";
			 $this->Output .= disp_orders($buyorders, "Buy Orders", array(
				  "state"
			 ), false, true);
			 $this->Output .= "<br><h3>Expired Orders</h3>";
			 $this->Output .= "<a name=\"se\"></a>";
			 $this->Output .= disp_orders($exp_sellorders, "Sell Orders", array(
				  "minvol",
				  "range"
			 ), true);
			 $this->Output .= "<a name=\"be\"></a>";
			 $this->Output .= disp_orders($exp_buyorders, "Buy Orders", array());
		} else
			 $this->Output .= (CORP_MODE ? "Corporation" : "Character") . " has no orders history.<br>";
		
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorOrdersPage();		 
 ?>