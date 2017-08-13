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
abstract class eveESI {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected $Db;
    protected $api;
 
    public $Error = "";
    public $cachedSecsLeft;
    public $cacheHit;
    protected $cache;
 
    public $age;
 
    public function __construct($api = null) {
        $this->Db = db::instance();
  
        if ($api)
            $this->api = $api;
    }

    protected function fetch_esi($apicall,$token= null, $args = array(),$override=0) {
        $cachedEntry = new CachedEntry(API_ESI_BASE.$apicall, $args);
        $cachedEntry->setHeaders("Authorization","Bearer ".$token);
        $cachedEntry->cached_retrieve();
        if ($cachedEntry->http_error) {
            $this->Error = "HTTP error " . $cachedEntry->http_error;
            return false;
        }

        if (!$this->APIInit($cachedEntry))
            return false;

        return $this->LoadAPI();
    }
 
    protected function APIInit($cachedEntry) {
        $this->cache = $cachedEntry->result;
        $this->cachedSecsLeft = $cachedEntry->get_timeLeft();
        $this->cacheHit = ($cachedEntry->cached == true);

        $this->api = json_decode($cachedEntry->result,true);
        if (isset($this->api['error'])) {
            $this->Error = (string)$this->api['error'];
            return false;
        }

        return true;
    }

     abstract public function LoadAPI();
}

 ?>