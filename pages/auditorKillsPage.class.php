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
function force_nowrap($str) {
 return str_replace(" ","&nbsp;",$str);
}

function display_log($entries,$name) {
 global $Db;

 $output = "<table class=\"fancy $name\" style=\"font-size:80%\">";
 $output .= "<tr><th colspan=7 style=\"font-size:120%\">$name</th></tr>";
 $output .= "<tr><th>date</th><th>destroyed</th><th>victim</th>";
 if (CORP_MODE) $output .= "<th>final blow</th>";
 $output .= "<th>system</th><th>corp</th><th>alliance</th><th>#P</th></tr>";
 $alt = " class=\"main\"";
 
 foreach ($entries as $kill) {
  if ($alt == " class=\"main\"") {
	$alt = " class=\"alt\"";
  } else $alt = " class=\"main\"";
  
  $output .= "<tr style=\"cursor: pointer;\" $alt onclick=\"document.location='".FULL_URL."&view=kills&kill=".$kill["killID"]."';\">";
  $output .= "<th scope=\"row\">".force_nowrap($kill['killTime'])."</th>";
  $output .= "<td>".force_nowrap($Db->getNameFromTypeId((int)$kill->victim['shipTypeID']))."</td>";
  $output .= "<td>".force_nowrap(getcharname($kill))."</td>";
  if (CORP_MODE) {
	$killer = $kill->xpath("/eveapi/result/rowset[@name='kills']/row[@killID=".$kill["killID"]."]/rowset[@name='attackers']/row[@finalBlow=1]");
	if ((string)$killer[0]["characterName"] == "") {
	 $killer = $Db->getNameFromTypeId((int)($killer[0]['shipTypeID']));
	} else 
	 $killer = (string)$killer[0]["characterName"];
	
	$output .= "<td>". force_nowrap($killer)."</td>";
  }
  $output .= "<td>".force_nowrap($Db->getNameFromSystemId((int)$kill['solarSystemID']))."</td>";
  $output .= "<td>".force_nowrap($kill->victim['corporationName'])."</td>";
  $output .= "<td>".force_nowrap($kill->victim['allianceName'])."</td>";
  $output .= "<td>".count($kill->xpath("/eveapi/result/rowset[@name='kills']/row[@killID='".$kill['killID']."']/rowset[@name='attackers']/*"))."</td>";
  $output .= "</tr>\n";
 }
 $output .= "</table>\n";
 return $output;
}
		
		
class auditorKillsPage extends auditorPage {

	public function GetName() { return "kills"; }
	public function GetAccMode() { return ACC_BOTH; }
	public function GetAccMask($corp) { return Killlog; }
	public function GetOutput($Db) {
		$KillLog = new eveApiKillLog($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$KillLog->fetch(CHAR_ID, USER_ID, API_KEY, CORP_MODE, CORP_MODE ? CORP_ID : 0)) {
			$this->Output = $KillLog->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		$this->Updated = APITime($KillLog);

		$killmail = null;

		if (isset($_GET['kill'])) {
			$kill = $KillLog->All->xpath("/eveapi/result/rowset[@name='kills']/row[@killID=".$_GET['kill']."]");
			if (count($kill) > 0)
				$killmail = generate_kill($kill[0]);
		}

		if (!$killmail) {
			$this->Title = "PVP Activity for ".USER_NAME;

			if (isset($_GET['kill'])) 
				$this->Output .= "Bad killID!<br>";

			$this->Output .= "<table><tr><td valign=top>\n";

			if (count($KillLog->Kills) > 1) { // TODO is this correct?
				$this->Output .= display_log($KillLog->Kills,"Kills");
				$this->Output .= "</td><td valign=top>";
			} else 
				$this->Output .= "<b><span style=\"font-size:80%\">".strtoupper("[".(CORP_MODE?"corp":"char")." has no recent kills]")."</b></span><br><br>";

			if (count($KillLog->Losses) > 1) {
				$this->Output .= display_log($KillLog->Losses,"Losses");
				$this->Output .= "</td>";
			} else 
				$this->Output .= "<b><span style=\"font-size:80%\">".strtoupper("[".(CORP_MODE?"corp":"char")." has no recent losses]")."</b></span><br><br>";
			$this->Output .= "</tr></table>";
		} else {
		  $this->Title = "Details for kill ".$_GET['kill'];
		  $this->Output .= "<br>Displaying killID " .$_GET['kill'];
		  $this->Output .= "<br>[ <a href=\"" . FULL_URL ."&view=kills\">back</a> ]";
		  $this->Output .= "<pre>$killmail</pre>";
		 //}
		}
				
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorKillsPage();
 ?>