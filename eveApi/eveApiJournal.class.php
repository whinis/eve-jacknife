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

// api journal page with support for corp or char info
 

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiJournal extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
 public $entries;
 public $transTypes = null;
 public $corp = false;
 
 public function fetch($chid,$usid,$apik,$corp = false,$acct = 1000, $count=500) {
  $args = array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik,"rowCount"=>$count);
  $this->corp = $corp;
  
  if ($corp)
   $args["accountKey"] = $acct;
   
  return $this->fetch_xml("/".($corp?"corp":"char")."/WalletJournal.xml.aspx",$args,60*60);
 }

 private function getTransTypes() {
  $trans = cache_api_retrieve($this->Db->link,"/eve/RefTypes.xml.aspx");
  $transTypeIds = $trans->value;

  $result = $transTypeIds->xpath("/eveapi/result/rowset/row");
  $types = array();
  
  foreach($result as $entry)
   $types[(int)$entry['refTypeID']] = (string)$entry['refTypeName'];

  $this->transTypes = $types;
 }

 function getNiceTransType($trans) {
  $map = array(
 37 => "Corp Withdrawal",
 63 => "Auction Bid",
 64 => "Bid Refund",
 67 => "Auction Sold",
 68 => "Contract Reward",
 69 => "[C] Collateral Refund",
 70 => "[C] Collateral Payout",
 71 => "Contract Price",
 72 => "[C] Brokers Fee",
 73 => "[C] Sales Tax",
 74 => "[C] Deposit",
 75 => "[C] Deposit Sales Tax",
 82 => "[C] Deposit Refund", 
 34 => "[M] Time Bonus",
 30 => "[M] Collateral",
 31 => "[M] Collateral Refund",
 33 => "[M] Reward",
 83 => "[C] Reward Deposited",
 77 => "[C] Auction Bid (corp)",
 78 => "[C] Collateral Deposited (corp)",
 79 => "[C] Price (corp)",
 80 => "[C] Brokers Fee (corp)",
 81 => "[C] Deposit (corp)",
 84 => "[C] Reward (corp)",
 38 => "Dividend Payment"
 );

  if (array_key_exists($trans,$map))
   return $map[$trans];
  
  return $this->transTypes[$trans];
 }
 
	 public function loadAPI() {
	 
		if ($this->transTypes == null)
			$this->getTransTypes();
		if ($this->corp) {
			$list = $this->api->xpath("/eveapi/result/rowset[@name='entries']/row");
		} else 
			$list = $this->api->xpath("/eveapi/result/rowset[@name='transactions']/row");
			
			
		$entries =  array();
		 foreach ($list as $entry) {

			  $new             = array();
              $new['ownerID1']=(string)$entry['ownerID1'];
              $new['ownerID2']=(string)$entry['ownerID2'];
			  $new["ID"] 		= (float)$entry["refID"];
			  $new["date"]     = (string) $entry["date"];
			  $new["type"]     = $this->transTypes[(int)$entry["refTypeID"]];
			  $new["typeNice"] = $this->getNiceTransType((int) $entry["refTypeID"]);
			  $new["amount"]   = (double) $entry["amount"];
			  $new["balance"]  = (double) $entry["balance"];
			  $new["reason"]   = (string) $entry["reason"];
			  $new["reason"]=str_replace("DESC: ","",$new["reason"]);
			  
			  $desc = $new["type"] . " for " . $entry["ownerName1"] . " payable to " . $entry["ownerName2"];
			  
			  switch ((int) $entry["refTypeID"]) {
					case 2:
						 $desc = $entry["ownerName1"] . " bought stuff from " . $entry["ownerName2"];
						 break;
					case 1:
						 $desc = $entry["ownerName1"] . " trading with " . $entry["ownerName2"];
						 break;
					case 85:
						 if (!CORP_MODE) {
							  $desc = "Bounty prizes for killing pirates in " . $entry['argName1'];
						 } else
							  $desc = $entry["ownerName2"] . " got bounty prizes for killing pirates in " . $entry['argName1'];
						
						$bounties = "";
						foreach (explode(",",$new["reason"]) as $bount) {
							$pair = explode(":",$bount);
							if (count($pair) != 2) continue;
							$bounties .= "$pair[1] x " . $this->Db->getNameFromTypeId($pair[0]) . ", ";
						}
						
						$new["reason"] = rtrim($bounties, ", ");
						break;
					case 10:
						 $desc = "Donation from " . $entry["ownerName1"] . " to " . $entry["ownerName2"];
						 break;
					case 63:
						 $desc = "Bid on an Auction";
						 break;
					case 64:
						 $desc = "Bid Refund: You have been outbid.";
						 break;
					case 54:
						 $desc = "SCC Transaction Tax";
						 break;
					case 37:
						 if ((int) $entry["ownerID1"] == (int) $entry["ownerID2"])
							  $desc = "Inter-wallet transfer by " . $entry['argName1'];
						 break;
					case 19:
						 if ($entry["argName1"] < 0) {
							  $desc = "Buying insurance; Ref ID " . $entry["argName1"] * -1;
						 } else
							  $desc = "Insurance covering the loss of a " . $this->Db->getNameFromTypeId((string) $entry["argName1"]);
						 break;
					case 42:
					case 72:
					case 46:
					
					case 8:
					case 35:
						 $desc = $new["type"];
						 break;
			  }			  
			  $new["description"] = str_replace(" ", "&nbsp;", $desc);

			  $entries[] = $new;
		 }
		 
		 $this->entries = $entries;
		return true;
	}
}
  
 ?>