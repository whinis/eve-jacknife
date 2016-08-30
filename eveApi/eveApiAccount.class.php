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

// mails page. supports partial updates every 30minutes

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiAccount extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public $created;
	public $logonCount;
	public $logonMinutes;
	public $paidUntil;
	public function fetch($usid, $apik)
    {
        if(SSO_MODE)
		    return $this->fetch_xml("/account/AccountStatus.xml.aspx ",array("accessToken"=>$usid));
        else
            return $this->fetch_xml("/account/AccountStatus.xml.aspx ",array("keyID"=>$usid,"vCode"=>$apik));
    }
	public function loadAPI() {
		$account=$this->api;
		$this->created=$account->result->createDate;
		$this->logonCount=$account->result->logonCount;
		$this->logonMinutes=$account->result->createMinutes;
		  
		$end = strtotime((string)($account->result->paidUntil));
		$gmtTime = time();
		$timeLeft = ($end - $gmtTime);
		if($timeLeft>86400){
			$this->paidUntil=floor((($timeLeft/60)/60)/24)." Days";
		}else{
			$this->paidUntil = "less than a day";
		}
		return true;
	}
}
 ?>