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
class auditorBookmarksPage extends auditorPage {

	public function GetName() { return "bookmarks"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return Bookmarks; }
	public function GetOutput($Db) {

		$Bookmarks = new eveApiBookmarks($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Bookmarks->fetch(CHAR_ID, USER_ID, API_KEY,CORP_MODE)) {
		 	$this->Output = $Bookmarks->Error;
			return false;
		}


		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		
		$this->Updated = APITime($Bookmarks);
		$this->Title = "Bookmarks for " . USER_NAME;
        $xpandall  = isset($_GET['xpandall']);
		if (count($Bookmarks->BookmarksByLocation) > 0) {
            $this->Output .= "";
            $this->Output .= count($Bookmarks->Bookmarks) . " items total in " . count($Bookmarks->BookmarksByLocation) . " locations<br>";
            if (!$xpandall) {
                $this->Output .= "<a href=\"$full_url&view=assets&xpandall\">Expand all entries</a><br>";
            } else
                $this->Output .= "<a href=\"$full_url&view=assets\">Collapse all entries</a><br>";
            $this->Output .= "<br><table class='fancy2'>";
            foreach ($Bookmarks->BookmarksByLocation as $location => $items) {
                $system = $Db->getLocationNameFromId($location);
                $this->Output .= "<tr><td style='cursor:pointer;' onclick=\"return toggle_visibility('l$location');\"><a href=\"#\" onclick=\"return false\">";
                $this->Output .= "<b>" . $system . "</b></a></td><td align=right><b>" . count($items) . "&nbsp;item" . (count($items) == 1 ? "" : "s") . "</b>&nbsp;&nbsp;&nbsp;<br></td></tr>";
                $this->Output .= "<tr><td colspan=2><div id=\"l$location\" style=\"" . ($xpandall ? "display:block;" : "display:none;") . "\"><br>\n";
                foreach($items as $i=>$bookmark){
                    $this->Output.= $Bookmarks->Bookmarks[$bookmark]['memo']."<br>";
                }
                $this->Output .= "<br></div></td></tr>\n";
            }

            $this->Output .= "</table>";
		} else {
            $this->Output .= "<br>Character has no Bookmarks.<br>";
        }
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorBookmarksPage();
 ?>