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

class auditorMailPage extends auditorPage {
	public function GetName() { return "mail"; }
	public function GetAccMode() { return ACC_CHAR_ONLY; }
	public function GetAccMask($corp) { return MailBodies | MailMessages; }
	public function GetOutput($Db) {
		$Mail = new eveApiMails($Db);
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		if (!$Mail->fetch(CHAR_ID, USER_ID, API_KEY)) {
			$this->Output = $Mail->Error;
			return false;
		}

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;

		$this->Updated = APITime($Mail);
		$this->Title = "Mail for " . CHAR_NAME;

		if (count($Mail->Messages) > 0) {
			 $idsToResolve = array();
			 
			 foreach ($Mail->Messages as $message) // get a list of all ids referenced
				  foreach (explode(",", $message["toCorpOrAllianceID"] . "," . $message["toCharacterIDs"] . "," /*.$message["toListIDs"].","*/ . $message["senderID"]) as $str)
						if ($str != "" && !in_array($str, $idsToResolve))
							 $idsToResolve[] = $str;
			 
			 $ids = idlookup($Db->link, $idsToResolve);
			 
			 if (isset($_GET['mail'])) {
				  $ret = $Mail->fetchMailBody(CHAR_ID, USER_ID, API_KEY, $_GET['mail']);
				  if (!$ret) {
						$this->Output = "Error fetching mail $_GET[mail]: " . $Mail->Error;
						return false;
				  }
				  
				  foreach ($Mail->Messages as $message) {
						if ($message["messageID"] != $_GET['mail']) {
							 continue;
						} else {
							 $this->Output .= "<h3>$message[title]</h3><h5 style=\"display: inline;\">Sent by " . $ids[(int) $message["senderID"]] . " on $message[sentDate]</h5><br><br>";
							 break;
						}
				  }
				  
				  $this->Output .= parse_ccptml($ret) . "<br><br>";
				  $this->Output .= "<a href=\"$full_url&view=mail\">[back]</a><br><br>";
				  
			 } else {
				  $this->Output .= "<span style=\"font-size:80%; font-weight: bold;\">";
				  /*if ($Mail->unread > 0) 
				  $this->Output .= $Mail->unread." unread message".($Mail->unread ==1?", ":"s, ");*/
				  $this->Output .= count($Mail->Messages) . " messages total<br>";
				  
				  if ($Mail->Message != "")
						$this->Output .= $Mail->Message . "<br>";
				  
				  $this->Output .= "</span>";
				  
				  $this->Output .= "<br><table class=\"fancy Mail\" style=\"font-size:83%;\" border=1>";
				  $this->Output .= "<tr><th>date</th><th>sender</th><th>title</th><th>recipients</th></tr>";
				  
				  $alt = " class=\"main\"";
                 $ids1=array();
                 foreach ($Mail->Messages as $message) {
                     $ids1[]=(string)$message['senderID'];
                     $kindaID=explode(",",(string)$message['toCharacterIDs']);
                     $kindaID2=explode(",",(string)$message['toCorpOrAllianceID']);
                     $ids1=array_merge($ids1,$kindaID,$kindaID2);

                 }
                 $ids1=array_unique($ids1);
                 $redIDS=GetRedIDS($ids1,$Db);
                 if(isset($redIDS[0])&&$redIDS[0]==0)
                     $redIDS=array();
				  
				  foreach ($Mail->Messages as $message) {
                      $sentTo=explode(",",(string)$message['toCharacterIDs']);
                      $sentToGroup=explode(",",(string)$message['toCorpOrAllianceID']);
                      $intersect=array_intersect($redIDS,$sentTo);
                      $intersect2=array_intersect($redIDS,$sentToGroup);
                      if(in_array((string)$message['senderID'],$redIDS)||!empty($intersect)||!empty($intersect2)){
                          if (strpos(strtolower($alt),'main') !== false) {
                              $alt = " class=\"redAlt\"";
                          } else $alt = " class=\"redMain\"";
                      }else{
                          if (strpos(strtolower($alt),'main') !== false) {
                              $alt = " class=\"alt\"";
                          } else $alt = " class=\"main\"";

                      }
						
						if ((int) $message["toListID"] == 0 || (string) $message["toListID"] == "") {
							 $to = array();
							 if ($message["toCorpOrAllianceID"] != "")
								  $to += explode(",", $message["toCorpOrAllianceID"]);
							 if ($message["toCharacterIDs"] != "")
								  $to += explode(",", $message["toCharacterIDs"]);
							 
							 $recp = "";
							 foreach ($to as $rec)
								  $recp .= $ids[(int) $rec] . ", ";
							 
							 $recp = rtrim($recp, ", ");
							 
						} else
							 $recp = "(mailing list)";
						
						$this->Output .= "<tr$alt style=\"cursor: pointer;\" onclick=\"document.location='$full_url&view=mail&mail=" . $message["messageID"] . "'\">";
						//$this->Output .= "<td>".($message["read"]!=1?"<b>#</b>":"")."</td>";
						$this->Output .= "<td>" . $message["sentDate"] . "</td>";
						$this->Output .= "<td>" . $ids[(int) $message["senderID"]] . "</td>";
						$this->Output .= "<td>" . $message["title"] . "</td>";
						$this->Output .= "<td>" . $recp . "</td>\n";
						$this->Output .= "</tr>";
				  }
				  
				  $this->Output .= "</table>";
			 }
		} else
			 $this->Output .= "<br>Character has no recent mails.<br>";
		
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}

$registered_pages[] = new auditorMailPage();
 ?>