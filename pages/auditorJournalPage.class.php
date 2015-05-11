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
class auditorJournalPage extends auditorPage {

	public function GetName() { return "journal"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return $corp?corp_WalletJournal:WalletJournal; }
	public function GetOutput($Db) {
		global $sort,$ord,$cols; // req for sorting
		
		$Journal = new eveApiJournal($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		$acct = isset($_GET['div']) ? ($_GET['div']+999) : 1000;

		if (!$Journal->fetch(CHAR_ID, USER_ID, API_KEY,CORP_MODE,$acct)) {
			$this->Output = $Journal->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		$this->Updated = APITime($Journal);
		$this->Title = "Journal for " . USER_NAME;

		/////////////////////////////////////////////////

		$ord = "ASC";

		$cols = array(
			 "date",
			 "type",
			 "amount",
			 "balance",
			 "description",
			 "reason"
		);

		$sort = null;

		$this->Output .= sort_ctrl();

		if (!$sort) {
			 $sort = "date";
			 $ord  = "DESC";
		}

		$this->Output .= div_select($acct);

		if (count($Journal->entries) > 0) {
            $ids=array();
            foreach ($Journal->entries as $entry) {
                $ids[]=(string)$entry['ownerID1'];
                $ids[]=(string)$entry['ownerID2'];
            }
            $ids=array_unique($ids);
            $redIDS=GetRedIDS($ids,$Db);
            if(isset($redIDS[0])&&$redIDS[0]==0)
                $redIDS=array();
			 $this->Output .= number_format((int) $Journal->entries[0]["balance"], 2) . " ISK<br><br>";
			 
			 $this->Output .= count($Journal->entries) . " entries<br>";
			 $this->Output .= "<table class=\"fancy journal\" style=\"font-size:83%;\" border=1>";
			 $this->Output .= "<tr>";
			 
			 foreach ($cols as $col) {
				  $this->Output .= "<th>";
				  if (!isset($nosorting))
						$this->Output .= "<a href=\"$full_url&view=journal&sort=$col&order=" . (($sort == $col && $ord == "ASC") ? "DESC" : "ASC") . "\">";
				  $this->Output .= $col;
				  if (!isset($nosorting))
						$this->Output .= "</a>";
				  $this->Output .= "</th>";
			 }
			 
			 $this->Output .= "</tr>";
			 
			 $entries = $Journal->entries;	
			 
			 if ($sort != null && array_key_exists($sort, $entries[0]))
				  usort($entries, "globl_sortfunc");
			 $alt = " class=\"main\"";


            $alt_b = false;
			 foreach ($entries as $entry) {
                 $alt_b = !$alt_b;
				 $this->Output .= "<tr";
                 if(in_array($entry['ownerID1'],$redIDS)||in_array($entry['ownerID2'],$redIDS)){
                     if ($alt_b) {
                         $this->Output .= " class=\"redAlt\"";
                     } else
                         $this->Output .= " class=\"redMain\"";
                 }else{
                     if ($alt_b) {
                         $this->Output .= " class=\"alt\"";
                     } else
                         $this->Output .= " class=\"main\"";

                 }
                 $this->Output .=">";
				  $this->Output .= "<th scope=\"row\">" . str_replace(" ", "&nbsp;", $entry["date"]) . "&nbsp;</th>";
				  $this->Output .= "<td>" . str_replace(" ", "&nbsp;", $entry["typeNice"]) . "</td>";
				  $this->Output .= "<td align=right style=\"color: " . (($entry["amount"] > -1) ? "#007700" : "#BB0000") . ";\">&nbsp;" . number_format($entry["amount"], 2) . "&nbsp;ISK</td>";
				  $this->Output .= "<td align=right>&nbsp;" . number_format($entry["balance"], 2) . "&nbsp;ISK&nbsp;&nbsp;</td>";
				  $this->Output .= "<td>" . $entry["description"] . "</td>";
				  $this->Output .= "<td nowrap=\"nowrap\" width=\"400\">" . $entry["reason"] . "&nbsp;</td>";
				  $this->Output .= "</tr>\n";
			 }
			 
			 $this->Output .= "</table>";
		} else
			 $this->Output .= (CORP_MODE ? "Division" : "Character") . " has no Journal activity in the last 3 months.<br>";
						
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorJournalPage();
 ?>