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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiStandings extends eveApi {

    public $aStandings=array();
    public $nStandings=array();
    public $fStandings=array();
    public function fetch($chid, $usid, $apik,$corp = false, $token=false)
    {
        if(SSO_MODE)
            return $this->fetch_xml("/".($corp?"corp":"char")."/Standings.xml.aspx", array(
                "characterID" => $chid,
                "accessToken" => $usid,
            ));
        else
            return $this->fetch_xml("/".($corp?"corp":"char")."/Standings.xml.aspx", array(
                "characterID" => $chid,
                "keyID" => $usid,
                "vCode" => $apik
            ));
    }
    public function LoadAPI() {
        $this->aStandings = $this->api->xpath("/eveapi/result/characterNPCStandings/rowset[@name='agents']/row");
        $this->nStandings = $this->api->xpath("/eveapi/result/characterNPCStandings/rowset[@name='NPCCorporations']/row");
        $this->fStandings = $this->api->xpath("/eveapi/result/characterNPCStandings/rowset[@name='factions']/row");

	usort($this->aStandings, "custom_sort");
	usort($this->nStandings, "custom_sort");
	usort($this->fStandings, "custom_sort");

        return true;
    }
}

        function custom_sort($a,$b){
                if ((float)$a["standing"] == (float)$b["standing"]) return 0;
                return ((float)$a["standing"]>(float)$b["standing"]) ? -1 : 1;
        }
  
 ?>
