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

function sortfunc_mails($a, $b) {
 return (int)$a["messageID"] >  (int)$b["messageID"] ? -1 : 1;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiMails extends eveApi {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
    public $Messages;
    public $Message;
    //public $unread;
 
    public function fetchMailBody($chid,$usid,$apik,$id) {
        $api_ret = cache_api_retrieve($this->Db,"/char/MailBodies.xml.aspx",array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik,"ids"=>$id));
        if (!$api_ret)
            return "";
        if(is_int($api_ret->value)){
            return "Http error".$api_ret->value;
        }
        $body = $api_ret->value->xpath("/eveapi/result/rowset[@name='messages']/row");
        if(empty($body)){
            return null;
        }
        if(isset($_SESSION)&&isset($_SESSION['mailFormatted'])&&$_SESSION['mailFormatted']==true){ //should you show the message with or without formatting
            return $body[0];
        }else{
            return preg_replace('#</?font[^>]*>#is', '',$body[0]);
        }
    }

    public function fetch($chid,$usid,$apik, $token=false) {
        try {
            if(SSO_MODE)
                $api_ret = $this->fetch_xml("/char/MailMessages.xml.aspx",array("characterID"=>$chid,"accessToken"=>$usid));
            else
                $api_ret = $this->fetch_xml("/char/MailMessages.xml.aspx",array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik));
        }catch (exception $ex){
            $this->Error = "Invalid XML returned.";
            return null;
        }

        $this->Message = "";

        return $this->LoadAPI();
    }

    public function LoadAPI() {
        $this->Messages = $this->api->xpath("/eveapi/result/rowset[@name='messages']/row");
        //$this->unread = count($this->api->xpath("//row[@read=0]"));
        uasort($this->Messages,"sortfunc_mails");
        return true;
    }
}
  
 ?>