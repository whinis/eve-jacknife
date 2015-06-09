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
class eveApiSkills extends eveApi
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public $charName;
    public $charId;
    
    public $corpName;
    public $corpID;
    
    public $balance;
    
    public $SPTotal;
    public $SkillCount;
    public $Skills;
    public $attributes;
	public $charAgeSecs;
	public $charDOB;
	 
    public $isDirector;
    
    public function fetch($chid, $usid, $apik)
    {
        return $this->fetch_xml("/char/CharacterSheet.xml.aspx", array(
            "characterID" => $chid,
            "keyID" => $usid,
            "vCode" => $apik
        ));
    }
    
    public function loadAPI()
    {
        $this->charId = (string) ($this->api->result->characterID);

        if (!$this->charId) {
            $this->Error = "not a char sheet xml";
            return false;
        }
		  
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

        $this->charName = (string) ($this->api->result->name);
        $this->corpName = (string) ($this->api->result->corporationName);
        $this->corpID   = (string) ($this->api->result->corporationID);
        
        $this->balance = (double) ($this->api->result->balance);
		  $this->charDOB = strtotime((string) ($this->api->result->DoB));
        $this->charAgeSecs = strtotime((string) ($this->api->currentTime)) - strtotime((string) ($this->api->result->DoB));
        $this->SPTotal    = 0;
        $this->SkillCount = 0;
        $this->Skills     = array();
        
        $this->isDirector = count($this->api->xpath("/eveapi/result/rowset[@name='corporationRoles']/row[@roleName='roleDirector']")) != 0;
        
        
        return true;
    }
    
    public function loadSkills()
    {
        $result = $this->api->xpath("/eveapi/result/rowset[@name='skills']/row");
        
        $ids = array();
        foreach ($result as $node)
            $ids[(int) $node["typeID"]] = 1;
        
        $this->Db->cacheItemTypes(array_keys($ids));
        
        foreach ($result as $node) {
            $this->SPTotal += (int) $node["skillpoints"];
            $this->SkillCount++;
            
            $skillInfo = $this->Db->getTypeFromTypeId((int) $node["typeID"]);
            $skillName = isset($skillInfo["typeName"]) ? $skillInfo["typeName"] : ("[UNKNOWN TYPE " . (int) $node["typeID"] . "]");
            $catName   = "";
            
            if ($skillInfo["groupID"] == 989) { // isn't in db...
                $catName = "Subsystems";
            } else if ($skillInfo["groupID"] != "")
                $catName = $this->Db->getNameFromGroupId($skillInfo["groupID"]);
            
            if ($catName == "")
                $catName = "[UNKNOWN CATEGORY]";
            
            if (!isset($this->Skills[$catName]))
                $this->Skills[$catName] = array(
                    0 => 0
                );
            
            $this->Skills[$catName][0] += (int) $node["skillpoints"];
            $this->Skills[$catName][$skillName] = (int) $node["level"];
            
        }
        
        ksort($this->Skills); // sort by key
        
        foreach ($this->Skills as &$arr)
            ksort($arr);
    }
    
    public function canCharUseTypeNames($names)
    {
        $capitalSkillHack=array(24311,24312,24313,24314,20525,20530,20531,20532,3344,3345,3346,3347);
        if (!$skills = $this->Db->getFullSkillsForTypeNames($names))
            return false;
        foreach ($skills as $skill => $level) { // now check to see if each skill is at the min level
            if ($this->getSkillLevelByID($skill) < $level) {

                if ($skill == 20533) { //check if the person has a capital skill trained before the level change
                    $intersect = array_intersect(array_keys($skills), $capitalSkillHack);
                    if(empty($intersect)){
                        return false;
                    }
                }else{
                    return false;
                }

            }
        }
        
        return true;
    }
    
    
    
    public function getSkillLevelByName($name)
    {
        if (!$id = $this->Db->getTypeIdFromName($name))
            return null;
        
        return $this->getSkillLevelByID($id);
    }
    
    public function getSkillLevelByID($id)
    {
        // returns an array of matching nodes (only one in this case, at most)
        $skill = $this->api->xpath("/eveapi/result/rowset/row[@typeID=" . $id . "]");
        
        if (!$skill || count($skill) == 0)
            return -1;
        
        return $skill[0]["level"];
    }
    
    public function canCharUseTypeName($name)
    {

        $typeId = $this->Db->getTypeIdFromName($name);
        
        if (!$typeId)
            return false;
        
        return $this->canCharUseTypeID($typeId);
    }
    
	 public function canCharUseTypeID($id)
    {
        $skills = $this->Db->getFullSkillsForTypeId($id);
        foreach ($skills as $skill => $level) {
            if ($this->getSkillLevelByID($skill) < $level) {
                return false;
            }
        }
        
        return true;
    }
	 
    public function canCharUseTypeIDAdvanced($id)
    {
        $skills = $this->Db->getFullSkillsForTypeId($id);
        $arr = array();

			foreach ($skills as $skill => $level)
				if (($have = $this->getSkillLevelByID($skill)) < $level) 
					$arr[$skill] = "Requires lvl $level" . (($have != -1) ? ", have lvl $have" : "");

        return $arr;
    }
}

 ?>