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
class auditorNotificationsPage extends auditorPage {

	public function GetName() { return "notifications"; }
	public function GetAccMode() { return ACC_CHAR_ONLY; }
	public function GetAccMask($corp) { return Notifications; }
	public function GetOutput($Db) {

		$Notifs = new eveApiNotifications($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Notifs->fetch(CHAR_ID, USER_ID, API_KEY)) {
		 	$this->Output = $Notifs->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		
		$this->Updated = APITime($Notifs);
		$this->Title = "Notifications for " . USER_NAME;

		if (count($Notifs->Notifications) > 0) {
		 $this->Output .= "<span style=\"font-size:80%; font-weight: bold;\">";
		 if ($Notifs->unread > 0) 
		  $this->Output .= $Notifs->unread." unread notification".($Notifs->unread ==1?", ":"s, ");
		  
		 $this->Output .= count($Notifs->Notifications) ." notifications total<br>";

		 if ($Notifs->Message != "")
		  $this->Output .= $Notifs->Message."<br>";

		 $this->Output .= "</span>";
		 
		 $idsToResolve = array();
		 
		 foreach ($Notifs->Notifications as $message) // get a list of all ids referenced
			if (!in_array((string)$message["senderID"],$idsToResolve))
			  $idsToResolve[] = (string)$message["senderID"];

		 $ids = idlookup($Db->link,$idsToResolve);

		 $this->Output .= "<br><table class=\"fancy notification\" style=\"font-size:83%;\" border=1>";
		 $this->Output .= "<tr><th>!</th><th>date</th><th>sender</th><th>type</th></tr>";
		 
		 $alt = " class=\"main\"";

		 foreach ($Notifs->Notifications as $message) {
			if ($alt == " class=\"main\"") {
			$alt = " class=\"alt\"";
		  } else $alt = " class=\"main\"";

		  $this->Output .= "<tr$alt>";
		  $this->Output .= "<td>".($message["read"]!=1?"<b>#</b>":"")."</td>";
		  $this->Output .= "<td>".$message["sentDate"]."</td>";
		  $this->Output .= "<td>".$ids[(int)$message["senderID"]]."</td>";
		  $this->Output .= "<td>".notif_type_lookup($message["typeID"])."</td>";
		  $this->Output .= "</tr>";
		 }

		 $this->Output .= "</table>";
		 
		} else 
		 $this->Output .= "<br>Character has no recent notifications.<br>";
						
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorNotificationsPage();
 ?>