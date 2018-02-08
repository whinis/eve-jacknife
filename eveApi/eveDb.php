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

// eve database stuff main file

define("SLOT_NONE", 0);
define("SLOT_HIGH",12);
define("SLOT_MED",13);
define("SLOT_LOW",11);
define("SLOT_RIG",2663);
define("SLOT_DRONE",14);
define("SLOT_SUBSYSTEM",3772);

$def_eve_db = 'eve_tyr';

//$allApiCalls = array();
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class eveDb  extends db{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    //public $allQueries = array();
    // cache of all queries
    
    public $link = null;
    public $queries = 0;
    // count of queries
    
    protected $locationCache = array();
    protected $citadelLocationCache = array();
    protected $citadelLocationsDownladed = false;
    protected $stationToSystemCache = array();
    
    protected $typesCache = array();
    // full cache of type id to type 4
    protected $groupCache = array();
    // full cache of group id to group
    
    protected $skillCacheP = array();
    // partial skill cache
    protected $skillCacheF = array();
    // full skill set cache
	 
    protected $metaCache = array();
    // meta level cache
   
    protected $emptyQueries = array();
    // cache of empty queries
/*
    public function __construct($server = "", $user= "", $pass= "", $db = null) {
        global $def_eve_db;

        if ($db == null) {
            $db = $def_eve_db;
        }
        
        if ($server == "") {
            return;
        }
        
        $this->connect($server, $user, $pass, $db);
    }*/
    
    /*
    public function query($sql) {
        
        if (isset($this->emptyQueries[$sql])) {
            return null;
        }
        
        $this->queries++;
        
        $result = $this->ref->query($sql);

        if (isset($this->allQueries)) {
            if (!isset($this->allQueries[$sql])) {
                $this->allQueries[$sql] = 0;
            }
            
            $this->allQueries[$sql]++;
        }
        
        if (!$result&& DEBUG) {
            echo "<html><head><title>error</title></head><body><pre width=100>";
            echo "<h3>FATAL SQL ERROR</h3>";
            echo "QUERY: '$sql'\n\n" . mysqli_error()."\n\nBacktrace:\n";
            debug_print_backtrace();
            die("</pre></body></html>");
        }elseif(!$result){
			die("sql error");
		}
        
        if (is_bool($result)) {
            return $result;
        }
        if (mysqli_num_rows($result) == 0) {
            $this->emptyQueries[$sql] = true;
            // will not do any more queries on this
            return null;
        }
        
        return $result;
    }*/
    
    function fetchApiChars($usid, $apik)
    {
        
        $xmlstr = cache_api_retrieve($this,"/account/Characters.xml.aspx", array("keyID"=>$usid,"vCode"=>$apik));
        //print_r($xmlstr);
        
        if ($xmlstr->error) {
            return null;
        }
        
        $result = $xmlstr->value->xpath("/eveapi/result/rowset/row");
        
        if (count($result) == 0) {
            return null;
        }
        
        $chars = array();
        
        foreach($result as $char) {
            $charA = array();
            
            foreach($char->attributes() as $name => $value) // copy item attributes
            $charA[(string)$name] = (string)$value;
            
            $corpinf = cache_api_retrieve($this,"/corp/CorporationSheet.xml.aspx", array("corporationID"=>$charA["corporationID"]))->value;
            
            $charA["allianceID"] = (string)$corpinf->result->allianceID;
            if ($charA["allianceID"] == 0) {
                $charA["allianceName"] = "NONE";
            } else {
                $charA["allianceName"] = (string)$corpinf->result->allianceName;
            }
            
            $charA["corpTicker"] = (string)$corpinf->result->ticker;
            
            $chars[$charA["characterID"]] = $charA;
        }
        
        return $chars;
    }
    
    public function updateConqStations() {
        $result = cache_api_retrieve($this, "/eve/ConquerableStationList.xml.aspx",array(),3*24*60*60);
        
        if ($result->error) {
            return false;
        }
        
        if ($result->hit) {
            return true;
        }
        // it was cached still, no point in updating
        
        $result = $result->value->xpath("/eveapi/result/rowset[@name='outposts']/row");
        foreach($result as $station) {
            $stationName = /*$this->getNameFromSystemId((string)$station["solarSystemID"])." - ".*/$station["stationName"];
            
            //$sql = "SELECT corporationID FROM ".DB_PREFIX."staStations WHERE stationID='".$this->ref->real_escape_string($station["stationID"])."'";

            $result = $this->selectWhere("staStations",['stationID'=>$station["stationID"]],['corporationID']);
            
            if (!$result) {
                echo 'MySQL Error: ' . $this->ref->error;
                return false;
            }
            
            if ($result->rows > 0) {
                //$sql = "UPDATE ".DB_PREFIX."staStations SET corporationID='".$this->ref->real_escape_string($station["corporationID"])."', stationName='".$this->ref->real_escape_string(addslashes($stationName))."' WHERE stationID=".$this->ref->real_escape_string($station["stationID"]);
                //$result = $this->ref->query($sql);
                $this->update("staStations",
                    [
                        'stationID'=>$station["stationID"]
                    ],
                    [
                        'corporationID'=>$station["corporationID"],
                        'stationName'=>$stationName
                    ]);
            } else {
                //$sql = "INSERT INTO ".DB_PREFIX."staStations (stationID, stationTypeID, corporationID, solarSystemID, stationName) VALUES (".$this->ref->real_escape_string($station["stationID"]).", ".$this->ref->real_escape_string($station["stationTypeID"]).", ".$this->ref->real_escape_string($station["corporationID"]).", ".$this->ref->real_escape_string($station["solarSystemID"]).", '".$this->ref->real_escape_string(addslashes($stationName))."');";
                //$result = $this->ref->query($sql);
                $this->insert('staStations',[
                    'stationID'=>$station["stationID"],
                    'stationTypeID'=>$station["stationTypeID"],
                    'corporationID'=>$station["corporationID"],
                    'solarSystemID'=>$station["solarSystemID"],
                    'stationName'=>$stationName
                ]);
            }
        }
        
        $this->conqUpdated = true;
        return true;
    }
    
    public function getMetaLevelForID($id) {
			if (isset($this->metaCache[$id])) 
				return $this->metaCache[$id];

       // $sql = "SELECT valueInt, valueFloat FROM ".DB_PREFIX."dgmTypeAttributes WHERE typeID=".$this->ref->real_escape_string($id)." AND attributeID=633";
        //$result = $this->query($sql);
        $result = $this->selectWhere('dgmTypeAttributes',['typeID'=>$id,'attributeID'=>633],['valueInt', 'valueFloat']);
        if (!$result) {
				$this->metaCache[$id] = 0;
            return 0;
        }
		  					
        if ($result->rows > 0) {
            $row = $result->results[0];
            $this->metaCache[$id] = $this->getMeta($row);
            return $this->getMeta($row);
        }
        
		  $this->metaCache[$id] = 0;
        return 0;
    }
	 
	 public function cacheMetaLevelsIDs($ids) {
		  if (count ($ids) == 0) return;
		  
        //$sql = "SELECT typeID, valueInt, valueFloat FROM ".DB_PREFIX."dgmTypeAttributes WHERE typeID IN (".implode(",",$ids).") AND attributeID=633";
        //$result = $this->ref->query($sql);
         $result = $this->selectWhere('dgmTypeAttributes',['typeID'=>['IN',$ids],'attributeID'=>633],['typeID','valueInt', 'valueFloat']);

         if (!$result)
				return;
        
        if ($result->rows > 0) {
            foreach($result->results as $row){
                $this->metaCache[$row["typeID"]] = $this->getMeta($row);
            }
            foreach($ids as $typeid)
                if (!isset($this->metaCache[$typeid]))
                    $this->metaCache[$typeid] = 0;

            return;
        }
    }
	 
	 function getMeta($row) {
		if ($row['valueFloat'] != "")
			return $row['valueFloat'];
      return $row['valueInt'];
	 }
    
    public function isTypeIdShip($id) {
        $type = $this->getTypeFromTypeId($id);
        if (!$type) {
            return false;
        }
        
        $group = $this->getGroupFromGroupId($type["groupID"]);
        
        if (!$group) {
            return false;
        }
        
        return $group["categoryID"] == 6;
    }
    
    public function isTypeIdNoobShip($id) {
        $type = $this->getTypeFromTypeId($id);
        if (!$type) {
            return false;
        }
        
        $groupId = $type["groupID"];
        
        return($groupId == 237 || $groupId == 31);
    }
    
    public function isTypeIdFrigate($id) {
        $type = $this->getTypeFromTypeId($id);
        if (!$type) {
            return false;
        }
        
        $groupId = $type["groupID"];
        
        return($groupId == 237 || $groupId == 31);
    }
    
    public function isTypeIdNoncombat($id) {
        $type = $this->getTypeFromTypeId($id);
        if (!$type) {
            return false;
        }
        
        $groupId = $type["groupID"];
        
        return($groupId == 237 || $groupId == 31);
    }
    
    public function getNameFromStationId($id) {
        
        if (isset($this->locationCache[$id])) {
            return $this->locationCache[$id];
        }
        
        //$sql    = "SELECT stationID, stationName FROM ".DB_PREFIX."staStations WHERE stationID = '".$this->ref->real_escape_string($id)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("staStations",['stationID'=>$id],['stationID','stationName'],1);
        
        if (!$result|| $result->rows ==0) {
            return "Name not found, try refreshing";
        }
        
        $row = $result->results[0];
        
        $this->locationCache[$row['stationID']] = $row['stationName'];
        
        return $row['stationName'];
    }
	 
    public function getSystemFromStationId($id) {
        
        if (isset($this->stationToSystemCache[$id])) {
            return $this->stationToSystemCache[$id];
        }
        //$sql    = "SELECT stationID, solarSystemID FROM ".DB_PREFIX."staStations WHERE stationID = '".$this->ref->real_escape_string($id)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("staStations",['stationID'=>$id],['stationID','stationName'],1);

        if (!$result || $result->rows ==0) {
            return null;
        }
        
        $row = $result->results[0];
        
        $this->stationToSystemCache[$row['stationID']] = $row['solarSystemID'];
        
        return $row['solarSystemID'];
    }
	 
	 //
    
    public function getTypeIdFromName($name) {
        if (isset($this->typesCache[$name])) {
            return $this->typesCache[$name];
        }
        
        $sql    = "SELECT typeID FROM ".DB_PREFIX."invTypes WHERE typeName LIKE '".$this->ref->real_escape_string($name)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("invTypes",['typeName'=>['LIKE',$name]],['typeID'],1);

        if (!$result || $result->rows ==0) {
            return null;
        }

        $row = $result->results[0];
        
        $this->typesCache[$name] = $row['typeID'];
        
        return $row['typeID'];
    }
    
    public function getNameFromTypeId($id) {
        $type = $this->getTypeFromTypeId($id);
        
        if (!$type) {
            return null;
        }
        
        return $type['typeName'];
    }
    
    public function getNameFromMoonId($id) {
        
        if (isset($this->locationCache[$id])) {
            return $this->locationCache[$id];
        }
        
        //$sql    = "SELECT itemID, itemName FROM ".DB_PREFIX."invUniqueNames WHERE itemID = '".$this->ref->real_escape_string($id)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("invUniqueNames",['itemID'=>$id],['itemID','itemName'],1);

        if (!$result || $result->rows ==0) {
            return null;
        }

        $row = $result->results[0];
        
        $this->locationCache[$row['itemID']] = $row['itemName'];
        
        return $row['itemName'];
    }

    public function getNameFromSystemId($id) {

        if (isset($this->locationCache[$id])) {
            return $this->locationCache[$id];
        }

        //$sql    = "SELECT solarSystemID, solarSystemName FROM ".DB_PREFIX."mapSolarSystems WHERE solarSystemID = '".$this->ref->real_escape_string($id)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("mapSolarSystems",['solarSystemID'=>$id],['solarSystemID','solarSystemName'],1);

        if (!$result || $result->rows ==0) {
            return null;
        }

        $row = $result->results[0];

        $this->locationCache[$row['solarSystemID']] = $row['solarSystemName'];

        return $row['solarSystemName'];
    }

    public function getNameFromCitadelId($id) {

        if ($this->citadelLocationsDownladed) {
            if (isset($this->citadelLocationCache[$id])) {
                return $this->citadelLocationCache[$id];
            } else {
                return "Unknown Citadel - ".substr($id, -5);
            }
        }

        // Get cURL resource
        $curl = curl_init();

        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://stop.hammerti.me.uk/api/citadel/all',
            CURLOPT_USERAGENT => 'EveJackKnife'
        ));

        // Send the request & save response to $resp
        $resp = curl_exec($curl);

        $response = json_decode(trim($resp));
        curl_close($curl);

        $response = (array)$response;

        foreach ($response as $citID => $citData) {
            $this->citadelLocationCache[$citID] = $citData->name." - ".$citData->typeName." - ".substr($citID, -5);
        }

        $this->citadelLocationsDownladed = true;

        if (isset($this->citadelLocationCache[$id])) {
            return $this->citadelLocationCache[$id];
        } else {
            return "Unknown Citadel - ".substr($id, 0, -5);
        }
    }
    
    public function getSystemSecurity($id) {
        
       // $sql    = "SELECT security FROM ".DB_PREFIX."mapSolarSystems WHERE solarSystemID = '".$this->ref->real_escape_string($id)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("mapSolarSystems",['solarSystemID'=>$id],['security'],1);

        if (!$result || $result->rows ==0) {
            return 0;
        }

        $row = $result->results[0];
        
        $value = $row['security'];
        if ($value < 0) {
            $value = 0;
        }
        return number_format($value,1);
    }
    
    public function getSlotFromTypeId($id) {
       // $sql    = "SELECT effectID FROM ".DB_PREFIX."dgmTypeEffects WHERE typeID = '".$this->ref->real_escape_string($id) . "' AND effectID IN (11,12,13,2663,3772)";
        //$result = $this->query($sql);

        $effectID=array(11,12,13,2663,3772);
        $result = $this->selectWhere("dgmTypeEffects",['typeID'=>$id,'effectID'=>['IN',$effectID]],['effectID']);
        
        if ($result&& $result->rows >0) {
            $row = $result->results[0];
            if (count($row) != 0  && isset($row["effectID"])) {
                return $row["effectID"];
            }
        }
        
        $type = $this->getTypeFromTypeId($id);
        if (in_array($type["groupID"],array(640,100,639,641)))
				return SLOT_DRONE;
        
        return SLOT_NONE;
    }
    
    public function getTypeFromTypeId($id) {
        if (isset($this->typesCache[$id])) {
            return $this->typesCache[$id];
        }
		$id=(int)$id;
		settype($offset, 'integer');
       // $sql    = "SELECT * FROM ".DB_PREFIX."invTypes WHERE typeID = '".$this->ref->real_escape_string($id)."' LIMIT 1 OFFSET $offset;";
        //$result = $this->query($sql);
        $fields=[
            'typeID',
            'groupID',
            'typeName',
            'description'=>
                'substring(description,1,4000)',
            'mass',
            'volume',
            'capacity',
            'portionSize',
            'raceID',
            'basePrice',
            'published',
            'marketGroupID',
            'iconID',
            'soundID'
        ];
        $result = $this->selectWhere("invTypes",['typeID'=>$id],$fields,1);
        //debug_print_backtrace();
        if ($result&& $result->rows >0) {
			$row = $result->results[0];

			$this->typesCache[$row['typeName']] = $row['typeID'];
			$this->typesCache[$row['typeID']] = $row;
        }
		else
			return null;
        return $row;
    }
    
    public function getNameFromGroupId($id) {
        
        $group = $this->getGroupFromGroupId($id);
        
        if (!$group) {
            return null;
        }
        
        //$this->groupCache[$row['groupName']] = $row['groupID'];
        
        return $group['groupName'];
    }
    
    public function getGroupFromGroupId($id) {
        
        if (isset($this->groupCache[$id])) {
            return $this->groupCache[$id];
        }
        
       // $sql    = "SELECT * FROM ".DB_PREFIX."invGroups WHERE groupID = '".$this->ref->real_escape_string($id)."' LIMIT 1";
        //$result = $this->query($sql);

        $result = $this->selectWhere("invGroups",['groupID'=>$id],null,1);

        if (!$result || $result->rows ==0) {
            return null;
        }

        $row = $result->results[0];
        
        $this->groupCache[$row['groupID']] = $row;
        
        return $row;
    }
    
    public function getAttribInfo($attrID) {
        
       // $sql    = "SELECT * FROM ".DB_PREFIX."dgmAttributeTypes WHERE attributeId = '".$this->ref->real_escape_string($attrID)."' LIMIT 1";
        //$result = $this->query($sql);
        $result = $this->selectWhere("dgmAttributeTypes",['attributeId'=>$attrID],null,1);
        
        if (!$result|| $result->rows ==0) {
            return array();
        }

        $row = $result->results[0];
        
        return $row;
    }
    
    function getLocationNameFromId($location)
    {
        if ($location < 60000000) {
            // items in space
            return $this->getNameFromSystemId($location);
        } elseif ($location > 60000000000) {
            // items in citadels
            return $this->getNameFromCitadelId($location);
        } else {
            return $this->getNameFromStationId(locationTranslate($location));
        }
    }
    
    public function cacheItemTypes($items) {
		$array=array();
		foreach($items as $id){
			if(!isset($this->typesCache[$id]))
				$array[]=$id;
		}
		$items=$array;
        if (count($items) == 0) {
            return;
        }
        
       // $sql    = "SELECT * FROM ".DB_PREFIX."invTypes WHERE typeID IN (" . implode(",",$items) . ")";
        //$result = $this->query($sql);
        $fields=[
            'typeID',
            'groupID',
            'typeName',
            'description'=>
                'substring(description,1,4000)',
            'mass',
            'volume',
            'capacity',
            'portionSize',
            'raceID',
            'basePrice',
            'published',
            'marketGroupID',
            'iconID',
            'soundID'
        ];
        $result = $this->selectWhere("invTypes",['typeID'=>['IN',$items]],$fields);

        if (!$result|| $result->rows ==0) {
            return;
        }
        foreach ($result->results as $row){
            $this->typesCache[$row['typeName']] = $row['typeID'];
            $this->typesCache[$row['typeID']] = $row;
        }
    }
    
    public function cacheGroupTypes($items) {
        if (count($items) == 0) {
            return;
        }
        
        //$sql    = "SELECT * FROM ".DB_PREFIX."invGroups WHERE groupID IN (" . implode(",",array_filter($items)). ")";
        //$result = $this->query($sql);

        $result = $this->selectWhere("invGroups",['groupID'=>['IN',array_filter($items)]],null);

        if (!$result|| $result->rows ==0) {
            return;
        }

        foreach ($result->results as $row){
            $this->groupCache[$row['groupID']] = $row;
        }
    }
    
    public function cacheLocationIds($locations) {
        
        $sqlStations = array();
        $sqlSystems = array();
        
        foreach($locations as $location) {
            if ($location < 60000000) {
                // items in space
                $sqlSystems[$location]=1;
            } else {
                $sqlStations[locationTranslate($location)]=1;
            }
        }
        
        $Locations = array();
        
        if (count($sqlStations) != 0) {
            //$result = $this->query("SELECT stationID, stationName, solarSystemID FROM ".DB_PREFIX."staStations WHERE stationID IN (".implode(",",array_keys($sqlStations)).")");
            $result = $this->selectWhere("staStations",['stationID'=>['IN',array_keys($sqlStations)]],['stationID', 'stationName', 'solarSystemID']);
            foreach($result->results as $row){
                $this->locationCache[$row['stationID']] = $row['stationName'];
					 $this->stationToSystemCache[$row['stationID']] = $row['solarSystemID'];
            }
        }
        
        if (count($sqlSystems) != 0) {
           // $result = $this->query("SELECT solarSystemID, solarSystemName FROM ".DB_PREFIX."mapSolarSystems WHERE solarSystemID IN ('".implode("','",array_keys($sqlSystems))."')");
            $result = $this->selectWhere("mapSolarSystems",['solarSystemID'=>['IN',array_keys($sqlSystems)]],['solarSystemID', 'solarSystemName']);
            foreach($result->results as $row){
                $this->locationCache[$row['solarSystemID']] = $row['solarSystemName'];
            }
        }
    }
    
    public function getEffectInfo($effectID) {
        
        //$sql    = "SELECT * FROM ".DB_PREFIX."dgmEffects WHERE effectId = '".$this->ref->real_escape_string($effectID)."' LIMIT 1";
        //$result = $this->query($sql);
        $fields=[
            'effectID',
            'effectName',
            'effectCategory',
            'preExpression',
            'postExpression',
            'description',
            'guid',
            'iconID',
            'isOffensive',
            'isAssistance',
            'durationAttributeID',
            'trackingSpeedAttributeID',
            'dischargeAttributeID',
            'rangeAttributeID',
            'falloffAttributeID',
            'disallowAutoRepeat',
            'published',
            'displayName',
            'isWarpSafe',
            'rangeChance',
            'electronicChance',
            'propulsionChance',
            'distribution',
            'sfxName',
            'npcUsageChanceAttributeID',
            'npcActivationChanceAttributeID',
            'fittingUsageChanceAttributeID',
            'modifierInfo'=>
                'substring(modifierInfo,1,2000)',
        ];

        $result = $this->selectWhere("dgmEffects",['effectId'=>$effectID],null,1);


        if (!$result|| $result->rows ==0) {
            return;
        }

        $row = $result->results[0];
        
        return $row;
    }
    
    public function getSkillsForTypeId($typeId) {
        if (isset($this->skillCache1[$typeId])) {
            return $this->skillCache1[$typeId];
        }
        
        //$sql = "SELECT attributeID, valueInt, valueFloat FROM ".DB_PREFIX."dgmTypeAttributes WHERE typeID = '".$this->ref->real_escape_string($typeId) ."' AND ((attributeID > 181 AND attributeID < 185) OR (attributeID > 276 AND attributeID < 280)) LIMIT 6";
        // only select the skill attributes
        //$result = $this->query($sql);
        $result = $this->selectWhere(
            "dgmTypeAttributes", //table
            [ //where statement
                'typeID'=>$typeId,
                [
                    [
                        [
                            'attributeID'=>['>',181],
                        ],
                        [
                            'attributeID'=>['<',185],
                        ],
                        'or'=>true
                    ],
                    [
                        [
                            'attributeID'=>['>',276],
                        ],
                        [
                            'attributeID'=>['<',280],
                        ]

                    ],

                ]
            ],
            [ //fields wanted
                'attributeID',
                'valueInt',
                'valueFloat'
            ],
            6 //limit
        );

        if (!$result|| $result->rows ==0) {
            $this->skillCache1[$typeId] = array();
            return null;
        }
        
        $res = array();
        
        foreach ($result->results as $row){
            $value = $row['valueInt'] != "" ? $row['valueInt'] : $row['valueFloat'];
            
            if ($row['attributeID'] > 181 && $row['attributeID'] < 185) {
                // skill req attribute
                $res[$row['attributeID'] - 182] = $value;
                // sets the primary, secondary, tetierary skills to 0, 1, 2 respectively
            } else {
                $ind = $row['attributeID'] - 277;
                // get this associated temp index for this skill
                $res[$res[$ind]] = $value;
                // set the value of the skill ID to the skill level
                unset($res[$ind]);
                // remove the temporary index
            }
        }

        $this->skillCache1[$typeId] = $res;
        
        return $res;
    }
    
    public function cacheSkillsForTypeIds($typeIds) {
        //$sql = "SELECT typeID, attributeID, valueInt, valueFloat FROM ".DB_PREFIX."dgmTypeAttributes WHERE typeID IN (" . implode(",",$typeIds) . ") AND ((attributeID > 181 AND attributeID < 185) OR (attributeID > 276 AND attributeID < 280)) ORDER BY typeID ASC";
        // only select the skill attributes
        //$result = $this->query($sql);
        $result = $this->selectWhere(
            "dgmTypeAttributes", //table
            [ //where statement
                'typeID'=>['IN',$typeIds],
                [
                    [
                        [
                            'attributeID'=>['>',181],
                        ],
                        [
                            'attributeID'=>['<',185],
                        ],
                        'or'=>true
                    ],
                    [
                        [
                            'attributeID'=>['>',276],
                        ],
                        [
                            'attributeID'=>['<',280],
                        ]

                    ],

                ]
            ],
            [ //fields wanted
                'typeID',
                'attributeID',
                'valueInt',
                'valueFloat'],
            null, //limit
            ['typeID',"ASC"]
        );
        if (!$result|| $result->rows ==0) {
            return;
        }
        
        $res = array();
        
        $typeId = "-1";
        foreach($result->results as $row){
            if ($row["typeID"] != $typeId && $typeId != "-1") {
                $this->skillCache1[$typeId] = $res;
                $res = array();
            }

            $typeId = $row["typeID"];
            $value = $row['valueInt'] != "" ? $row['valueInt'] : $row['valueFloat'];
            if ($row['attributeID'] > 181 && $row['attributeID'] < 185) {
                // skill req attribute
                $res[$row['attributeID'] - 182] = $value;
                // sets the primary, secondary, tetierary skills to 0, 1, 2 respectively
            } else {
                $ind = $row['attributeID'] - 277;
                // get this associated temp index for this skill
                $res[$res[$ind]] = $value;
                // set the value of the skill ID to the skill level
                unset($res[$ind]);
                // remove the temporary index
            }
        }
    }
    
    private function recurse_skills($skills, $allSkills) {
        
        array_push($allSkills, $skills);
        foreach($skills as $skill => $level)
        if ($skill2 = $this->getSkillsForTypeId($skill)) {
            // if it requires more skills to use, it will be non-null
            $allSkills = $this->recurse_skills($skill2, $allSkills);
        }
        
        return $allSkills;
    }
    
    
    public function getFullSkillsForTypeId($typeId) {
        if (isset($this->skillCacheF[$typeId])) {
            return $this->skillCacheF[$typeId];
        }
        
        $allSkills = array();
        // ends up as an array of arrays with skills
        $result = array();
        
        $skills = $this->getSkillsForTypeId($typeId);
        if (!$skills) {
            return array();
        }
        
        $allSkills = $this->recurse_skills($skills,$allSkills);
        
        // combine all the arrays in allskills into one
        foreach($allSkills as $skills)
            foreach($skills as $skill => $level)
            if (!isset($result[$skill]) || ($result[$skill] < $level)) {
                $result[$skill] = $level;
            }
        // only set if not added already or less than required level
        
        $this->skillCacheF[$typeId] = $result;
        return $result;
    }
    
    public function getNiceSkillsForTypeName($name) {
        if (!$typeid = $this->getTypeIdFromName($name)) {
            return null;
        }
        
        return $this->getNiceSkillsForTypeId($typeid);
    }
    
    
    public function getNiceSkillsForTypeId($typeId) {
        $skills = $this->getFullSkillsForTypeId($typeId);
        return $this->getNiceSkillsForSkillArray($skills);
    }
    
    public function getNiceSkillsForSkillArray($skills) {
        $result = array();
        
        foreach($skills as $skill_id => $level)
			$result[$this->getNameFromTypeId($skill_id)] = $level;
        
        return $result;
        
    }
    
    public function getFullSkillsForTypeNames($names) {
        
        $skills = array();
        
        foreach($names as $name) {
            $typeId = $this->getTypeIdFromName($name);
            
            if (!$typeId) {
                return null;
            }
            
            $tskills = $this->getFullSkillsForTypeId($typeId);
            
            foreach($tskills as $skill => $level) // add these skills to the main skill list
            if (!isset($skills[$skill]) || ($skills[$skill] < $level)) {
                $skills[$skill] = $level;
            }
            // only set if not present or is less than the current val
            
        }
        
        return $skills;
    }

    
    public function getFullSkillsForTypeName($name) {
        
        $typeId = $this->getTypeIdFromName($name);
        
        if (!$typeId) {
            return null;
        }
        
        return $this->getFullSkillsForTypeId($typeId);
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}

 ?>