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
class auditorStandingsPage extends auditorPage {

	public function GetName() { return "standings"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return Standings; }
	public function GetOutput($Db) {

		$Standings = new eveApiStandings($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Standings->fetch(CHAR_ID, USER_ID, API_KEY,CORP_MODE)) {
		 	$this->Output = $Standings->Error;
			return false;
		}


		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		
		$this->Updated = APITime($Standings);
		$this->Title = "Standings for " . USER_NAME;

		if (count($Standings->aStandings) > 0) {
		 $this->Output .= printPrettyTable($Standings->aStandings,
			"Agent");
		} else 
		 $this->Output .= "<br>Character has no Agent standings.<br>";

		if (count($Standings->nStandings) > 0) {
		 $this->Output .= printPrettyTable($Standings->nStandings,
			"NPC Corporation");
		} else 
		 $this->Output .= "<br>Character has no NPC Corporation standings.<br>";

		if (count($Standings->fStandings) > 0) {
		 $this->Output .= printPrettyTable($Standings->fStandings,
			"Faction");
		} else 
		 $this->Output .= "<br>Character has no Faction standings.<br>";


		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}

}
$registered_pages[] = new auditorStandingsPage();


	function printPrettyTable($data, $type){

		$out = "";
	 	$out .= "<br><table class=\"fancy notification\" style=\"font-size:83%;\" border=1>";

	 	$out .= "<tr><th>". $type . " Name</th><th>Standing</th></tr>";
		 
	 	$alt = " class=\"main\"";

		foreach ($data as $contact) {
                        if ($alt == " class=\"main\"") {
	                        $alt = " class=\"alt\"";
                  	} else $alt = " class=\"main\"";
                  	$out .= "<tr$alt>";

			$out .= "<td>".$contact["fromName"]."</td>";
			$out .= "<td>".$contact["standing"]."</td>";
			$out .= "</tr>";
		 }

		 $out .= "</table>";
		return $out;
	}
 ?>
