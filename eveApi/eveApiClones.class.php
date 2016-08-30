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
class eveApiClones extends eveApi
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public $charName;
    public $charId;
    
    public $corpName ="N/A";
    public $corpID ="N/A";
    
    public $balance ="0";

    public $attributes =[];
	public $charAgeSecs ="N/A";
	public $charDOB ="0";
	 
    public $isDirector;
    
    public function fetch($chid, $usid, $apik, $token=false)
    {
        if(SSO_MODE)
            $args = array("characterID"=>$chid,"accessToken"=>$usid);
        else
            $args = array("characterID"=>$chid,"keyID"=>$usid,"vCode"=>$apik);
        return $this->fetch_xml("/char/Clones.xml.aspx",$args);
    }
    
    public function loadAPI()
    {
        $attrib = $this->api->result->attributes;

        $attributes = array("mem" => (int)$attrib->memory,
            "int" => (int)$attrib->intelligence,
            "char" => (int)$attrib->charisma,
            "will" => (int)$attrib->willpower,
            "perc" => (int)$attrib->perception);

        if (isset($this->api->result->attributeEnhancers))
            $attributes["perc"] += (int)$this->api->result->attributeEnhancers->perceptionBonus->augmentatorValue;
        if (isset($this->api->result->attributeEnhancers))
            $attributes["will"] += (int)$this->api->result->attributeEnhancers->willpowerBonus->augmentatorValue;
        if (isset($this->api->result->attributeEnhancers))
            $attributes["mem"] += (int)$this->api->result->attributeEnhancers->memoryBonus->augmentatorValue;
        if (isset($this->api->result->attributeEnhancers))
            $attributes["int"] += (int)$this->api->result->attributeEnhancers->intelligenceBonus->augmentatorValue;
        if (isset($this->api->result->attributeEnhancers))
            $attributes["char"] += (int)$this->api->result->attributeEnhancers->charismaBonus->augmentatorValue;

        $this->attributes = $attributes;

        $this->charName = (string)($this->api->result->name);
        $this->corpName = (string)($this->api->result->corporationName);
        $this->corpID = (string)($this->api->result->corporationID);

        $this->balance = (double)($this->api->result->balance);
        $this->charDOB = strtotime((string)($this->api->result->DoB));
        $this->charAgeSecs = strtotime((string)($this->api->currentTime)) - strtotime((string)($this->api->result->DoB));
        $this->isDirector = count($this->api->xpath("/eveapi/result/rowset[@name='corporationRoles']/row[@roleName='roleDirector']")) != 0;


        
        
        return true;
    }
}

 ?>