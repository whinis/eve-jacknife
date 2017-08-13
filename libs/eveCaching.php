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

// api cache bits






$_checkedTables = false;

class CachedEntry {
    public $result;
    public $error = false;
    public $http_error = false;
    public $http_code= 0;
    public $api_error = false;
    public $cached_until = 0;
    public $cached = false;

    protected $key;
    protected $apicall;
    protected $args;
    protected $link;
    protected $_db;
    protected $_opts = [];
 
    public function __construct($apicall, $args = array())
    {
        $this->apicall = $apicall;
        $this->args = $args;
        $this->key = hash('sha256', $this->apicall . implode($this->args));
        if (!empty($args)) {
            $this->_opts['CURLOPT_POST'] = true;
            $this->_opts['CURLOPT_POSTFIELDS'] = $this->args;
        }
        $this->_opts['CURLOPT_PORT'] = 443;
        $this->_opts['CURLOPT_SSL_VERIFYPEER'] = 1;
        $this->_opts['CURLOPT_SSL_VERIFYHOST'] = 2;
        $this->_opts['CURLOPT_USERAGENT'] = "EveJackknife.com;Equto;Whinis@whinis.com";
        $this->_opts['CURLOPT_RETURNTRANSFER'] = true;
        $this->_opts['CURLOPT_TIMEOUT'] = 30;
        $this->_opts['CURLOPT_CAINFO'] = dirname(__FILE__) . '/cacert.pem';
    }
    public function setOpt($header,$value){
        if($value !== null)
            $this->_opts[$header] = $value;
        else if (isset($this->_opts[$header])){
            unset($this->_opts[$header]);
        }
    }

    function getDB(){
        if(is_null($this->_db)){
            $this->_db = db::instance();
        }
        return $this->_db;
    }
    function retrieve(){
        $req = curl_init($this->apicall);

        foreach ($this->_opts as $key=>$value){
            $k = "";
            if(is_string($key)){
                $k = constant($key);
            }else{
                $k = $key;
            }
            curl_setopt($req, $k, $value);
        }

        $resp = curl_exec($req);
        $http_code = curl_getinfo($req,  CURLINFO_HTTP_CODE);
        $http_errno = curl_errno($req);
        curl_close($req);
        $this->http_code = $http_code;
        if ($http_errno != 0)
            $this->http_error = $http_errno;
        if (isset($allApiCalls))
            $allApiCalls[] = array($this->apicall,$this->args, "fetched");
        $this->result = $resp;
        return $resp;
    }

    protected function _cached_retrieve($time=3600){
        $this->getDB()->delete(CACHE_TABLE,['expires'=>['<',gmdate("Y-m-d H:i:s")]]); //delete old cache
        if (defined("API_DEBUG")&&API_DEBUG==true) { // skip the cache, don't want to save it and won't be cached
            return false;
        }
        $result =  $this->getDB()->selectWhere(CACHE_TABLE,['apicall'=>$this->apicall,'keyv'=>$this->key,'expires'=>['>',gmdate("Y-m-d H:i:s")]],['expires','value']);
        if ($result != false) {
            if ($result->rows > 0) {
                // yay! got a cached value
                $row = $result->results[0];
                $this->cached_until =strtotime($row['expires']);
                if (isset($allApiCalls))
                    $allApiCalls[] = array($this->apicall,$this->args, "cached");
                $this->result = gzuncompress($row['value']);
                $this->cached = true;
                return $row['value'];
            }
        }
        return false;
    }
    public function cached_retrieve($time=3600){
        $result = $this->_cached_retrieve($time);
        if($result!==false){
            return $result;
        }else{
            $result = $this->retrieve();
            if($this->http_code ===200 && $this->http_error === false) {
                $this->cached_until = time() + $time;
                $cachedUntil = gmdate("Y-m-d H:i:s", time() + $time);
                $this->getDB()->insert(CACHE_TABLE, [
                    "apicall" => $this->apicall,
                    'keyv' => $this->key,
                    'expires' => $cachedUntil,
                    'value' => gzcompress($result, 6)//  needed for binary data
                ]);
            }
            return $result;
        }
    }
    public function get_timeLeft() {
        $left = $this->cached_until - time();
        return $left;
    }


    public function __toString() {
        return (string)$this->result;
    }
}
 ?>