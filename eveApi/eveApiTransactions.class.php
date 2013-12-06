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

// transactions class (basic way to get transactions data... not much special here)

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiTransactions extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public $entries;

	public function fetch($chid,$usid,$apik,$corp = false,$acct = 1000, $count=500) {
		$args = array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik,"rowCount"=>$count);

		if ($corp)
			$args["accountKey"] = $acct;

		return $this->fetch_xml("/".($corp?"corp":"char")."/WalletTransactions.xml.aspx",$args,60*60);
	}

	public function loadAPI() {
		$entries = array();

		foreach ($this->api->xpath("/eveapi/result/rowset[@name='transactions']/row") as $entry) {
			$new = array();

			$new["transType"] = (string) $entry["transactionType"];
			$new["corp"]      = (string) $entry["transactionFor"] == "corporation";

			$new["date"]   = (string) $entry["transactionDateTime"];
			$new["price"]  = (double) $entry["price"];
			$new["client"] = (string) $entry["clientName"];
			$new["type"]   = (string) $entry["typeName"];

			$new["station"]  = (string) $entry["stationName"];
			$new["quantity"] = (double) $entry["quantity"];
			$new["total"]    = $new["price"] * $new["quantity"] * ($new["transType"] == "buy" ? -1 : 1);

			$entries[] = $new;
		}

		$this->entries = $entries;
		return true;
	}
}

 ?>