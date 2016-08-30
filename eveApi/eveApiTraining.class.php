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

// skill training page

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiTraining extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
 public $skillID;
 public $skillName;
 public $skillLvl;
 public $skillTraining;
 public $timeLeft;
 public $timeLeftRaw;
 public $gmtTime;
  
 public function fetch($chid,$usid,$apik, $token=false) {
     if(SSO_MODE)
         return $this->fetch_xml("/char/SkillInTraining.xml.aspx",array("characterID"=>$chid,"accessToken"=>$usid));
     else
         return $this->fetch_xml("/char/SkillInTraining.xml.aspx",array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik));
 }
 
 public function loadAPI() {
  $this->skillTraining = ((string)($this->api->result->skillInTraining)) != "0";
  
  if (!$this->skillTraining)
   return false;
  
  $this->skillID = (string)($this->api->result->trainingTypeID);
  $this->skillName = $this->Db->getNameFromTypeId($this->skillID);
  $this->skillLvl = (string)($this->api->result->trainingToLevel);
  
  // figure out the time left
  
  @$oldTz = date_default_timezone_get();
  date_default_timezone_set ("UTC");
  
  $end = strtotime((string)($this->api->result->trainingEndTime));
  $gmtTime = time();
  $timeLeft = ($end - $gmtTime);
  $rem = niceTime($timeLeft);
  
  $this->gmtTime = strftime("%H:%M:%S",$gmtTime);
  $this->timeLeft = $rem;
  $this->timeLeftRaw = $end;

  date_default_timezone_set($oldTz);

  return true;
 }
}

 
 ?>