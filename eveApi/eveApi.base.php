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

// base class for API classes

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
abstract class eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

 protected $Db;
 protected $api;
 
 public $Error = "";
 public $cachedSecsLeft;
 public $cacheHit;
 protected $cache;
 
 public $age;
 
 public function __construct($eveDb, $api = null) { 
  $this->Db = $eveDb;
  
  if ($api)
   $this->api = $api;
 }

 protected function fetch_xml($apicall, $args = array(),$override=0) {
  $result = cache_api_retrieve($this->Db,$apicall, $args, $override);

  if ($result->http_error) {
   $this->Error = "HTTP error " . $result->value;
   return false;
  }
  
  if (!$this->APIInit($result))
   return false;
  
  return $this->LoadAPI();
 }
 
 protected function APIInit($result) {
  $this->cache = $result;
  $this->cachedSecsLeft = $result->timeLeft;
  $this->cacheHit = ($result->hit == 1);
  
  $this->api = $result->value;

  if ($this->api->error) {
   $this->Error = (string)$this->api->error;
   return false;
  }
  
  $this->age = -get_timeLeft($this->api->currentTime);
  
  return true;
 }
 
 public function LoadXML($xmlstr) {
  $this->cachedSecsLeft = 0;
  $this->cacheHit = false;
  
  try {
   $result = new SimpleXMLElement($xmlstr);
  } catch (Exception $e) {// malformed XML
   $this->Error = "malformed XML provided";
   return false;
  }
 
  $this->api = $result;

  if ($this->api->error) {
   $this->Error = (string)$this->api->error;
   return false;
  }
  
  $this->age = -get_timeLeft($this->api->currentTime);
  
  return $this->LoadAPI(); 
 }
 
 public function timeLeft() {
  if (!$this->cacheHit)
   return "Updated Now";
   
  return date("H:i:s",$this->cachedSecsLeft);
 }

 abstract public function LoadAPI();
}

 ?>