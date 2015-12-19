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
class auditorContactsPage extends auditorPage {

	public function GetName() { return "contacts"; }
	public function GetAccMode() { return ACC_CHAR_ONLY; }
	public function GetAccMask($corp) { return Notifications; }
	public function GetOutput($Db) {

		$Contacts = new eveApiContacts($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Contacts->fetch(CHAR_ID, USER_ID, API_KEY)) {
		 	$this->Output = $Contacts->Error;
			return false;
		}


		$time_end = microtime_float();
		$time_api = $time_end - $time_start;
		
		$this->Updated = APITime($Contacts);
		$this->Title = "Contacts for " . USER_NAME;

		if (count($Contacts->Contacts) > 0) {
            $redIDS=GetContactInfo($Contacts->Contacts,$Db);

		 	$this->Output .= "<br><table class=\"fancy notification\" style=\"font-size:83%;\" border=1>";
		 	$this->Output .= "<tr><th>Contact</th><th>Standing</th><th>Corp</th><th>Alliance</th><th>In Watchlist</th></tr>";
		 
		 	$alt = " class=\"main\"";
			foreach ($Contacts->Contacts as $contact) {
				if(in_array($contact['contactID'],$redIDS)){
					if (strpos(strtolower($alt),'main') !== false) {
						$alt = " class=\"redAlt\"";
					} else $alt = " class=\"redMain\"";
				}else{
					if (strpos(strtolower($alt),'main') !== false) {
						 $alt = " class=\"alt\"";
					} else $alt = " class=\"main\"";

				}

				$this->Output .= "<tr$alt>";
				$this->Output .= "<td>".$contact["contactName"]."</td>";
				$this->Output .= "<td>".$contact["standing"]."</td>";
				if(isset($contact['corpID'])){
					$this->Output .= "<td>".$contact["corpName"]."</td>";
				}else{
					$this->Output .= "<td></td>";
				}
				if(isset($contact['allianceID'])){
					$this->Output .= "<td>".$contact["allianceName"]."</td>";
				}else{
					$this->Output .= "<td></td>";
				}
				$this->Output .= "<td>".($contact["inWatchlist"]=="True"?"&#10004":"")."</td>";
				$this->Output .= "</tr>";
			 }

			 $this->Output .= "</table>";
		 
		} else 
		 $this->Output .= "<br>Character has no contacts.<br>";
						
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorContactsPage();
 ?>