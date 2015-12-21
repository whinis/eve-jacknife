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

// member list
include_once("auditorPage.base.php");
function ptime($stamp)
{
    $t = time() - strtotime($stamp);
    $t = (int) ($t / (24 * 60 * 60));
    
    if ($t == 0)
        return "today";
    
    if ($t == 1)
        return "yesterday";
    
    if ($t < 7)
        return "this week";
    
    if ($t < 14)
        return "last week";
    
    if ($t < 30)
        return "this month";
    
    if ($t < 60)
        return "last month";
    
    return $t . " days ago";
}

function bin($int)
{
    $i      = 0;
    $binair = "";
    
    while ($int >= pow(2, $i))
        $i++;
    
    if ($i != 0)
        $i = $i - 1; //max i
    
    while ($i >= 0) {
        if ($int - pow(2, $i) < 0) {
            $binair .= "0";
        } else {
            $binair .= "1";
            $int = $int - pow(2, $i);
        }
        
        $i--;
    }
    
    return $binair;
}

class auditorMembersPage extends auditorPage {

	public function GetName() { return "members"; }
	public function GetAccMode() { return ACC_CORP_ONLY; }
	public function GetAccMask($corp) { return array(corp_MemberTrackingLimited, corp_MemberTrackingExtended); }
	public function GetOutput($Db) {
		global $shpgroups, $owngroups, $interesting, $facmods, $offmods,$sort,$ord,$cols;
	 
		$Members = new eveApiMembers($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Members->fetch(CHAR_ID, USER_ID, API_KEY, KEY_MASK & corp_MemberTrackingExtended == corp_MemberTrackingExtended)) {
			$this->Output = $Members->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;

		date_default_timezone_set("UTC");
		
		$this->Updated = APITime($Members);
		$this->Title = isset($_GET['char']) ? "Character roles info" : (USER_NAME . " Membership");

		if (isset($_GET['char'])) { // TODO
			 /* $result = $Members->entries->xpath("//*[@characterID=".$_GET['char']."]");
			 
			 if (count($result) != 0) {
			 $char = $result[0];
			 
			 $this->Output .= "<h3>Roles for " . $char["name"]."</h3>";
			 $roles = bin((float)$char["roles"]);
			 
			 $roleDescriptions = array(1=>"pos fueler",7=>
			 'personal manager','accountant','security officer',
			 'factory manager','station manager','auditor',
			 'hanger can take division 1','hanger can take division 2',
			 'hanger can take division 3','hanger can take division 4',
			 'hanger can take division 5','hanger can take division 6',
			 'hanger can take division 7',
			 'hanger can query division 1','hanger can query division 2',
			 'hanger can query division 3','hanger can query division 4',
			 'hanger can query division 5','hanger can query division 6',
			 'hanger can query division 7',
			 'account can take division 1','account can take division 2',
			 'account can take division 3','account can take division 4',
			 'account can take division 5','account can take division 6',
			 'account can take division 7',
			 'account can query division 1','account can query division 2',
			 'account can query division 3','account can query division 4',
			 'account can query division 5','account can query division 6',
			 'account can query division 7',
			 'equipment config',
			 'container can take division 1','container can take division 2',
			 'container can take division 3','container can take division 4',
			 'container can take division 5','container can take division 6',
			 'container can take division 7',
			 'can rent office','can rent factory slot','can rent research slot',
			 'junior accountant','starbase config','starbase fuel'
			 );
			 
			 $this->Output .= "<br>";
			 } else 
			 $this->Output .= "Character not found";
			 */
		} else {
			 /////////////////////////////////////////////////
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
			 
			 function ship_group_Add($type)
			 {
				  global $Db, $shpgroups, $owngroups;
				  
				  if (array_key_exists((int) $type["groupID"], $shpgroups))
						if (!isset($owngroups[$type["groupID"]])) {
							 $owngroups[$type["groupID"]] = 1;
						} else
							 $owngroups[$type["groupID"]]++;
			 }
			 /////
			 
			 function shortloc($loc)
			 {
				  if (strpos($loc, " - ") != false)
						return substr($loc, 0, strpos($loc, " - "));
				  
				  return $loc;
			 }
			 
			 $result = $Members->entries;
			 $ord    = "ASC";
			 
			 $cols = array(
				  "name",
				  "joined",
				  "title",
				  "laston",
				  "location",
				  "ship",
				  "hasroles"
			 );
			 foreach ($result as $entry) {
				  if (!isset($entry["logoffDateTime"])) {
						$cols = array(
							 "name",
							 "joined",
							 "title"
						);
						$lofi = true;
				  }
				  break;
			 }
			 
			 $sort = null;
			 
			 if (!isset($nosorting) && isset($_GET['sort'])) {
				  if (in_array($_GET['sort'], $cols)) {
						$sort = $_GET['sort'];
						
						if (isset($_GET['order']))
							 if ($_GET['order'] == "DESC")
								  $ord = "DESC";
						
						$this->Output .= "<br>sorting by $sort, " . strtolower($ord) . " <a href=\"$full_url&view=members\">[no sort]</a><br><br>";
				  }
			 } else
				  $this->Output .= "<br>";
			 
			 if (count($result) > 0) {
				  $entries = array();
				  //"name","joined","title","laston","ship","has roles"
				  foreach ($result as $entry) {
						$new = array();
						
						$new["id"]     = (string) $entry["characterID"];
						$new["name"]   = (string) $entry["name"];
						$new["joined"] = (string) $entry["startDateTime"];
						
						$new["title"]    = (string) $entry["title"];
						$new["laston"]   = (string) $entry["logoffDateTime"];
						$new["ship"]     = (string) $entry["shipType"];
						$new["hasroles"] = ((int) $entry["roles"] != 0) ? ((string) $entry["roles"] == "9223372036854775807" ? "DIRECTOR" : "yes") : "";
						$new["location"] = shortloc((string) $entry["location"]);
						if ((int) $entry["shipTypeID"] != -1)
							ship_group_Add($Db->getTypeFromTypeId((int) $entry["shipTypeID"]));
						$entries[] = $new;
				  }
				  
				  if ($sort != null && array_key_exists($sort, $entries[0])) {
						usort($entries, "globl_sortfunc");
						
				  } else {
						$sort = "name";
						usort($entries, "globl_sortfunc");
				  }
				  
				  $namedowngroups = array();
				  foreach ($owngroups as $group_id => $cnt)
						$namedowngroups[$shpgroups[$group_id]] = $cnt;
				  
				  ksort($namedowngroups);
				  $this->Output .= "<table><tr><td>";
				  
				  $this->Output .= count($result) . " members<br>";
				  
				  $this->Output .= "<table class=\"fancy members\" style=\"font-size:83%;\" border=1>";
				  $this->Output .= "<tr>";
				  
				  foreach ($cols as $col) {
						$this->Output .= "<th>";
						if (!isset($nosorting))
							 $this->Output .= "<a href=\"$full_url&view=members&sort=$col&order=" . (($sort == $col && $ord == "ASC") ? "DESC" : "ASC") . "\">";
						$this->Output .= $col;
						if (!isset($nosorting))
							 $this->Output .= "</a>";
						$this->Output .= "</th>";
				  }
				  
				  $this->Output .= "</tr>";
				  
				  
				  $alt = " class=\"main\"";
				  //"name","timeincorp","title","laston","ship","hasroles");
				  foreach ($entries as $entry) {
						if ($alt == " class=\"main\"") {
							 $alt = " class=\"alt\"";
						} else
							 $alt = " class=\"main\"";
						
						$this->Output .= "<tr $alt>";
						$this->Output .= "<th scope=\"row\">" . str_replace(" ", "&nbsp;", $entry["name"]) . "&nbsp;</th>";
						$this->Output .= "<td>" . str_replace(" ", "&nbsp;", ptime($entry["joined"])) . "</td>";
						$this->Output .= "<td width=200>" . str_replace(" ", "&nbsp;", $entry["title"]) . "</td>";
						if (!isset($lofi)) {
							 $this->Output .= "<td>" . str_replace(" ", "&nbsp;", ptime($entry["laston"])) . "</td>";
							 $this->Output .= "<td>" . str_replace(" ", "&nbsp;", $entry["location"]) . "</td>";
							 $this->Output .= "<td>" . str_replace(" ", "&nbsp;", $entry["ship"]) . "</td>";
							 //$this->Output .= "<td><a href=\"$full_url&view=members&char=".$entry["id"]."\">".$entry["hasroles"]."</a></td>";
							 $this->Output .= "<td>" . $entry["hasroles"] . "</td>";
						}
						$this->Output .= "</tr>\n";
				  }
				  
				  $this->Output .= "</table>";
				  $this->Output .= "</td><td valign=\"top\">";
				  ///// shiptypes
				  if (!isset($lofi)) {
						$this->Output .= "<table>";
						$this->Output .= "<tr><th>type</th><th># owned</th></tr>";
						foreach ($namedowngroups as $group => $cnt)
							 $this->Output .= "<tr><td>&nbsp;" . str_replace(" ", "&nbsp;", $group) . "&nbsp;</td><td>$cnt</td></tr>\n";
						$this->Output .= "</table><br>";
				  }
				  $this->Output .= "</td></tr></table><br>";
			 }
		}
		
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorMembersPage();
 ?>