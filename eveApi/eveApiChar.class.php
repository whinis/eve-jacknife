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

// skills page, various funcs to test if a character can use x

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveApiChar extends eveApi
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public $charName;
    public $charId;
    
    public $corpName;
    public $corpID;

	public $charAgeSecs;
	public $charDOB;

    public $corpHistory;
    public $corpIDs;

    public $location = null;

    
    public function fetch($chid,$usid=null,$apik=null)
    {
        if($usid != null)
            return $this->fetch_xml("/eve/CharacterInfo.xml.aspx", array(
                "characterID" => $chid,
                "keyID" => $usid,
                "vCode" => $apik
            ));
        return $this->fetch_xml("/eve/CharacterInfo.xml.aspx", array(
            "characterID" => $chid,
        ));
    }
    
    public function loadAPI()
    {
        $this->charId = (string) ($this->api->result->characterID);

        if (!$this->charId) {
            $this->Error = "not a char sheet xml";
            return false;
        }
        $this->charName = (string) ($this->api->result->characterName);
        $this->corpName = (string) ($this->api->result->corporation);
        $this->corpID   = (string) ($this->api->result->corporationID);

        if(isset($this->api->result->lastKnownLocation)){
            $this->location=$this->api->result->lastKnownLocation." in ".$this->api->result->shipName."(".$this->api->result->shipTypeName.")";
        }

        $previousTime = 0;
        foreach($this->api->result->rowset->row as $corp){
            $corp['endDate'] = $previousTime -1;
            $previousTime = strtotime((string)$corp['startDate']);
            $corp['startDate'] = strtotime((string)$corp['startDate']);
            $this->corpHistory[]=$corp;
            $this->corpIDs[] = (int)$corp['corporationID'];
        }
        
        
        return true;
    }
}

 ?>