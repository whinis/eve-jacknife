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

function sortfunc_bookmarklocname($a, $b) {
    global $Db;

    if ($a == $b) {
        return 0;
    }

    $as = $Db->getLocationNameFromId($a);
    $bs = $Db->getLocationNameFromId($b);

    return ($as < $bs) ? -1 : 1;
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiBookmarks extends eveApi {

    public $Bookmarks=array();
    public $BookmarksByLocation=array();
    public function fetch($chid, $usid, $apik,$corp = false, $token=false)
    {
        if(SSO_MODE)
            return $this->fetch_xml("/".($corp?"corp":"char")."/Bookmarks.xml.aspx", array(
                "characterID" => $chid,
                "accessToken" => $usid
            ));
        else
            return $this->fetch_xml("/".($corp?"corp":"char")."/Bookmarks.xml.aspx", array(
                "characterID" => $chid,
                "keyID" => $usid,
                "vCode" => $apik
            ));
    }
    public function LoadAPI() {
        $Bookmarks = $this->api->xpath("/eveapi/result/rowset[@name='folders']/row");
        foreach($Bookmarks as $folder){
            foreach($folder->rowset->row as $bookmark){
                $this->Bookmarks[]=$bookmark;
                $this->BookmarksByLocation[(int)locationTranslate($bookmark['locationID'])][]=count($this->Bookmarks)-1;
            }
        }
        uksort($this->BookmarksByLocation,"sortfunc_bookmarklocname");
        return true;
    }
}
  
 ?>