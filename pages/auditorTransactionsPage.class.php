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
class auditorTransactionsPage extends auditorPage {

	public function GetName() { return "transactions"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return $corp?corp_WalletTransactions:WalletTransactions; }
	public function GetOutput($Db) {
		global $sort,$ord,$cols; // req for sorting
		
		$Transactions = new eveApiTransactions($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		$acct = isset($_GET['div']) ? ($_GET['div']+999) : 1000;

		if (!$Transactions->fetch(CHAR_ID, USER_ID, API_KEY,CORP_MODE,$acct)) {
			$this->Output = $Transactions->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		$this->Updated = APITime($Transactions);
		$this->Title = "Transactions for " . USER_NAME;

		/////////////////////////////////////////////////

		$result = $Transactions->entries;
		$ord    = "ASC";

		$cols = array(
			 "date",
			 "type",
			 "price",
			 "quantity",
			 "total",
			 "client",
			 "station"
		);

		$sort = null;

		$this->Output .= sort_ctrl();

		if (!$sort) {
			 $sort = "date";
			 $ord  = "DESC";
		}

		$this->Output .= div_select($acct);

		if (count($result) > 0) {
			 $this->Output .= "<br>" . count($result) . " entries<br>";
			 $this->Output .= "<table class=\"fancy trans\" style=\"font-size:83%;\" border=1>";
			 $this->Output .= "<tr>";
			 
			 foreach ($cols as $col) {
				  $this->Output .= "<th>";
				  if (!isset($nosorting))
						$this->Output .= "<a href=\"$full_url&view=transactions&sort=$col&order=" . (($sort == $col && $ord == "ASC") ? "DESC" : "ASC") . "\">";
				  $this->Output .= $col;
				  if (!isset($nosorting))
						$this->Output .= "</a>";
				  $this->Output .= "</th>";
			 }
            $ids=array();
            foreach ($result as &$entry) {
                $ids[]=(string)$entry['clientID'];
            }
            $ids=array_unique($ids);
            $redIDS=GetRedIDS($ids,$Db);
            if(isset($redIDS[0])&&$redIDS[0]==0)
                $redIDS=array();
			 
			 $this->Output .= "</tr>";
			 
			 //("date","type","price","total","client","station"
			 if ($sort != null && array_key_exists($sort, $result[0]))
				  usort($result, "globl_sortfunc");
			 
			 $alt = " class=\"main\"";
			 $alt_b = false;
			 foreach ($result as $entry) {
				  $alt_b = !$alt_b;
					
				  $this->Output .= "<tr ";
                  if(in_array($entry['clientID'],$redIDS)){
                     if ($alt_b) {
                         $this->Output .= " class=\"redAlt";
                     } else
                         $this->Output .= " class=\"redMain";
                 }else{
                     if ($alt_b) {
                         $this->Output .= " class=\"alt";
                     } else
                         $this->Output .= " class=\"main";

                 }
                  $this->Output .=(($entry["corp"] && !CORP_MODE) ? " corp_entry_row" : "") . "\">";
				  $this->Output .= "<th scope=\"row\">" . str_replace(" ", "&nbsp;", $entry["date"]) . "&nbsp;</th>";
				  $this->Output .= "<td>" . str_replace(" ", "&nbsp;", $entry["type"]) . "</td>";
				  $this->Output .= "<td align=right >" . number_format($entry["price"], 2) . "&nbsp;ISK</td>";
				  $this->Output .= "<td align=center> " . $entry["quantity"] . "</td>";
				  $this->Output .= "<td align=right style=\"color: " . (($entry["total"] > -1) ? "#007700" : "#BB0000") . ";\">&nbsp;" . number_format($entry["total"], 2) . "&nbsp;ISK</td>";
				  $this->Output .= "<td>&nbsp;" . $entry["client"] . "&nbsp;&nbsp;</td>";
				  $this->Output .= "<td width=\"*\">" . $entry["station"] . "&nbsp;</td>";
				  $this->Output .= "</tr>\n";
			 }
			 
			 $this->Output .= "</table>";
		} else
			 $this->Output .= (CORP_MODE ? "Division" : "Character") . " has no recent transactions.<br><br>";
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorTransactionsPage();
 ?>