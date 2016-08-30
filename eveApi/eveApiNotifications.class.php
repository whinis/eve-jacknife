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

// notifications page. would support partial updates if it were not broken on ccp end

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiNotifications extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
 public $Notifications;
 public $Message;
 public $unread;
 
 public function fetch($chid,$usid,$apik, $token=false){
     if(SSO_MODE)
        $api_ret = $this->fetch_xml("/char/Notifications.xml.aspx",array("characterID"=>$chid,"accessToken"=>$usid),6*60*60);
     else
         $api_ret = $this->fetch_xml("/char/Notifications.xml.aspx",array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik),6*60*60);
  $this->Message = "";
   
 // if (!$api_ret || !$this->cacheHit) // api does not support partial updates! wtf
   return $api_ret;

  if ($this->age < (30*60)) {
   $this->Message = "will check for new notifications in ".niceTime(30*60-$this->age);
   return $api_ret;
  }
  
  // 30 minute timer is just for the first update check, from there on you can check instantly, but that's not very nice... 
  // also, i would have to preserve the initial time, which would be annoying.
  
  // try to update with new mails
  $new_notif = simple_api_retrieve("/char/Notifications.xml.aspx",array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik));

  if ($new_notif != null && !$new_notif->error) { // did it work?
   $api_upd = $new_notif->value;
  
   $newMessages = $api_upd->xpath("/eveapi/result/rowset[@name='notifications']/row");
   $nm = count($newMessages);
   
   if ($nm > 0) { // new mails!
    $this->Message = "retrieved $nm new notification".($nm>1?"s":"");
    
    // merge the old with the new, so it appears like a new one
    foreach ($this->api->xpath("/eveapi/result/rowset[@name='notifications']/row") as $row) {
     $newrow = $api_upd->result->rowset->addChild("row");

     foreach ($row->attributes() as $name => $value)
      $newrow->addAttribute($name, $value);
    }
   
    $api_upd->cachedUntil = $this->api->cachedUntil;
    $this->api = $api_upd;
   } else 
    $this->Message = "retrieved notifications; no new messages found.";   
    
   } else { 
   $this->Message = "api error occured while fetching new notifications.";
    // didn't work, oh well... 
  }
  
  // update the DB with the new list of mails
  $this->api->currentTime = gmdate("Y-m-d H:i:s");
  $this->cache->update($this->api);  
  $this->APIInit($this->cache);
   
  return $this->LoadAPI();
 }

 public function LoadAPI() {
  $this->Notifications = $this->api->xpath("/eveapi/result/rowset[@name='notifications']/row");
  $this->unread = count($this->api->xpath("//row[@read=0]"));
  return true;
 }
}

function notif_type_lookup($id) {
 $ntypes = array();
 $ntypes[2] = "Character deleted";
 $ntypes[3] = "Medal granted to character";
 $ntypes[4] = "Alliance maintenance bill";
 $ntypes[5] = "Alliance war declared";
 $ntypes[6] = "Alliance war surrender";
 $ntypes[7] = "Alliance war retracted";
 $ntypes[8] = "Alliance war invalidated by Concord";
 $ntypes[9] = "Bill issued to a character";
 $ntypes[10] = "Bill issued to corporation or alliance";
 $ntypes[11] = "Bill not paid due to no ISK";
 $ntypes[12] = "Bill, issued by a character, paid";
 $ntypes[13] = "Bill, issued by a corporation or alliance, paid";
 $ntypes[14] = "Bounty claimed";
 $ntypes[15] = "Clone activated";
 $ntypes[16] = "New corp member application";
 $ntypes[17] = "Corp application rejected";
 $ntypes[18] = "Corp application accepted";
 $ntypes[19] = "Corp tax rate changed";
 $ntypes[20] = "Corp news report, typically for shareholders";
 $ntypes[21] = "Player leaves corp";
 $ntypes[22] = "Corp news, new CEO";
 $ntypes[23] = "Corp dividend/liquidation, sent to shareholders";
 $ntypes[24] = "Corp dividend payout, sent to shareholders";
 $ntypes[25] = "Corp vote created";
 $ntypes[26] = "Corp CEO votes revoked during voting";
 $ntypes[27] = "Corp declares war";
 $ntypes[28] = "Corp war has started";
 $ntypes[29] = "Corp surrenders war";
 $ntypes[30] = "Corp retracts war";
 $ntypes[31] = "Corp war invalidated by Concord";
 $ntypes[32] = "Container password retrieval";
 $ntypes[33] = "Contraband or low standings cause an attack or items being confiscated";
 $ntypes[34] = "Noobship granted";
 $ntypes[35] = "Insurance paid";
 $ntypes[36] = "Insurance contract expired";
 $ntypes[37] = "Sovereignty claim fails (alliance)";
 $ntypes[38] = "Sovereignty claim fails (corporation)";
 $ntypes[39] = "Sovereignty bill late (alliance)";
 $ntypes[40] = "Sovereignty bill late (corporation)";
 $ntypes[41] = "Sovereignty claim lost (alliance)";
 $ntypes[42] = "Sovereignty claim lost (corporation)";
 $ntypes[43] = "Sovereignty claim acquired (alliance)";
 $ntypes[44] = "Sovereignty claim acquired (corporation)";
 $ntypes[45] = "Alliance anchoring alert";
 $ntypes[46] = "Alliance structure turns vulnerable";
 $ntypes[47] = "Alliance structure turns invulnerable";
 $ntypes[48] = "Sovereignty disruptor anchored";
 $ntypes[49] = "Structure won/lost";
 $ntypes[50] = "Corp office lease expiration notice";
 $ntypes[51] = "Clone contract revoked by station manager";
 $ntypes[52] = "Corp member clones moved between stations";
 $ntypes[53] = "Clone contract revoked by station manager";
 $ntypes[54] = "Insurance contract expired";
 $ntypes[55] = "Insurance contract issued";
 $ntypes[56] = "Jump clone destroyed";
 $ntypes[57] = "Jump clone destroyed";
 $ntypes[58] = "Corporation joining factional warfare";
 $ntypes[59] = "Corporation leaving factional warfare";
 $ntypes[60] = "Corporation kicked from factional warfare on startup because of too low standing to the faction";
 $ntypes[61] = "Character kicked from factional warfare on startup because of too low standing to the faction";
 $ntypes[62] = "Corporation in factional warfare warned on startup because of too low standing to the faction";
 $ntypes[63] = "Character in factional warfare warned on startup because of too low standing to the faction";
 $ntypes[64] = "Character loses factional warfare rank";
 $ntypes[65] = "Character gains factional warfare rank";
 $ntypes[66] = "Agent has moved";
 $ntypes[67] = "Mass transaction reversal message";
 $ntypes[68] = "Reimbursement message";
 $ntypes[69] = "Agent locates a character";
 $ntypes[70] = "Research mission becomes available from an agent";
 $ntypes[71] = "Agent mission offer expires";
 $ntypes[72] = "Agent mission times out";
 $ntypes[73] = "Agent offers a storyline mission";
 $ntypes[74] = "Tutorial message sent on character creation";
 $ntypes[75] = "Tower alert";
 $ntypes[76] = "Tower resource alert";
 $ntypes[77] = "Station aggression message";
 $ntypes[78] = "Station state change message";
 $ntypes[79] = "Station conquered message";
 $ntypes[80] = "Station aggression message";
 $ntypes[81] = "Corporation requests joining factional warfare";
 $ntypes[82] = "Corporation requests leaving factional warfare";
 $ntypes[83] = "Corporation withdrawing a request to join factional warfare";
 $ntypes[84] = "Corporation withdrawing a request to leave factional warfare";
 $ntypes[85] = "Corporation liquidation";
 $ntypes[86] = "Territorial Claim Unit under attack";
 $ntypes[87] = "Sovereignty Blockade Unit under attack";
 $ntypes[88] = "Infrastructure Hub under attack";
 $ntypes[89] = "Contact notification";
 
 if (isset($ntypes[(int)$id])) 
  return $ntypes[(int)$id];

 return null;
}
  
 ?>