CREATE TABLE IF NOT EXISTS `api_lookup` (
  `keyv` char(80) DEFAULT NULL,
  `chara` char(64) DEFAULT NULL,
  `chid` int(11) DEFAULT NULL,
  `usid` int(11) DEFAULT NULL,
  `apik` varchar(80) DEFAULT NULL,
  KEY `idx_api_lookup` (`keyv`)
);

CREATE TABLE IF NOT EXISTS `fit_lookup` (
  `keyv` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `ship` varchar(32) NOT NULL,
  `fit` varchar(2048) NOT NULL,
  UNIQUE KEY `keyv` (`keyv`)
);

CREATE TABLE IF NOT EXISTS `api_type_cache` (
  `keyv` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL,
  UNIQUE KEY `keyv` (`keyv`)
);

CREATE TABLE IF NOT EXISTS `api_cache` (
  `apicall` varchar(80) default NULL,
  `keyv` char(80) default NULL,
  `expires` datetime default NULL,
  `value` mediumblob,
  KEY `idx_api_cache` (`keyv`)
);

CREATE TABLE IF NOT EXISTS `id_cache` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  UNIQUE KEY `id` (`id`)
);

CREATE TABLE IF NOT EXISTS `contract_bids` (
  `contractID` bigint(20) NOT NULL,
  `bidID` bigint(20) NOT NULL,
  `bidderID` bigint(20) NOT NULL,
  `amount` float NOT NULL,
  `bidTime` varchar(32) NOT NULL
);

CREATE TABLE IF NOT EXISTS `contract_items` (
  `contractID` bigint(20) NOT NULL,
  `buying` mediumtext NOT NULL,
  `selling` mediumtext NOT NULL,
  PRIMARY KEY  (`contractID`)
);

CREATE TABLE IF NOT EXISTS `accounts` (
  `username` varchar(20) DEFAULT NULL,
  `password` char(64) DEFAULT NULL,
  `salt` varchar(15) DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `cookie` varchar(25) DEFAULT NULL,
  `id` int(5) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `keyInformation` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `apiKey` char(80) NOT NULL DEFAULT '0',
  `userID` int(15) DEFAULT NULL,
  `notes` varchar(3000) DEFAULT NULL,
  `characters` varchar(255) DEFAULT NULL,
  `keyName` varchar(45) DEFAULT NULL,
  `type` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- eve tables follow

DROP TABLE IF EXISTS `dgmAttributeTypes`;
CREATE TABLE IF NOT EXISTS `dgmAttributeTypes` (
  `attributeID` smallint(6) NOT NULL,
  `attributeName` varchar(100) default NULL,
  `description` text,
  `iconID` int(11) default NULL,
  `defaultValue` double default NULL,
  `published` tinyint(4) default NULL,
  `displayName` varchar(100) default NULL,
  `unitID` tinyint(4) default NULL,
  `stackable` tinyint(4) default NULL,
  `highIsGood` tinyint(4) default NULL,
  `categoryID` tinyint(4) default NULL,
  PRIMARY KEY  (`attributeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dgmEffects`;
CREATE TABLE IF NOT EXISTS `dgmEffects` (
  `effectID` smallint(6) NOT NULL,
  `effectName` text,
  `effectCategory` smallint(6) default NULL,
  `preExpression` int(11) default NULL,
  `postExpression` int(11) default NULL,
  `description` text,
  `guid` varchar(60) default NULL,
  `iconID` int(11) default NULL,
  `isOffensive` tinyint(4) default NULL,
  `isAssistance` tinyint(4) default NULL,
  `durationAttributeID` smallint(6) default NULL,
  `trackingSpeedAttributeID` smallint(6) default NULL,
  `dischargeAttributeID` smallint(6) default NULL,
  `rangeAttributeID` smallint(6) default NULL,
  `falloffAttributeID` smallint(6) default NULL,
  `disallowAutoRepeat` tinyint(4) default NULL,
  `published` tinyint(4) default NULL,
  `displayName` varchar(100) default NULL,
  `isWarpSafe` tinyint(4) default NULL,
  `rangeChance` tinyint(4) default NULL,
  `electronicChance` tinyint(4) default NULL,
  `propulsionChance` tinyint(4) default NULL,
  `distribution` tinyint(4) default NULL,
  `sfxName` varchar(20) default NULL,
  `npcUsageChanceAttributeID` smallint(6) default NULL,
  `npcActivationChanceAttributeID` smallint(6) default NULL,
  `fittingUsageChanceAttributeID` smallint(6) default NULL,
  PRIMARY KEY  (`effectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dgmTypeAttributes`;
CREATE TABLE IF NOT EXISTS `dgmTypeAttributes` (
  `typeID` int(11) NOT NULL,
  `attributeID` smallint(6) NOT NULL,
  `valueInt` int(11) default NULL,
  `valueFloat` double default NULL,
  PRIMARY KEY  (`typeID`,`attributeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dgmTypeEffects`;
CREATE TABLE IF NOT EXISTS `dgmTypeEffects` (
  `typeID` int(11) NOT NULL,
  `effectID` smallint(6) NOT NULL,
  `isDefault` tinyint(4) default NULL,
  PRIMARY KEY  (`typeID`,`effectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `invGroups`;
CREATE TABLE IF NOT EXISTS `invGroups` (
  `groupID` smallint(6) NOT NULL,
  `categoryID` tinyint(4) default NULL,
  `groupName` varchar(100) default NULL,
  `description` text,
  `iconID` int(11) default NULL,
  `useBasePrice` tinyint(4) default NULL,
  `allowManufacture` tinyint(4) default NULL,
  `allowRecycler` tinyint(4) default NULL,
  `anchored` tinyint(4) default NULL,
  `anchorable` tinyint(4) default NULL,
  `fittableNonSingleton` tinyint(4) default NULL,
  `published` tinyint(4) default NULL,
  PRIMARY KEY  (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `invTypes`;
CREATE TABLE IF NOT EXISTS `invTypes` (
  `typeID` int(11) NOT NULL,
  `groupID` smallint(6) default NULL,
  `typeName` varchar(100) default NULL,
  `description` text,
  `graphicID` int(11) default NULL,
  `radius` double default NULL,
  `mass` double default NULL,
  `volume` double default NULL,
  `capacity` double default NULL,
  `portionSize` int(11) default NULL,
  `raceID` tinyint(4) default NULL,
  `basePrice` decimal(19,4) default NULL,
  `published` tinyint(4) default NULL,
  `marketGroupID` smallint(6) default NULL,
  `chanceOfDuplicating` double default NULL,
  `iconID` int(11) default NULL,
  PRIMARY KEY  (`typeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `invUniqueNames`;
CREATE TABLE IF NOT EXISTS `invUniqueNames` (
  `itemID` int(11) NOT NULL,
  `itemName` varchar(200) NOT NULL,
  `groupID` int(11) default NULL,
  PRIMARY KEY  (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `mapSolarSystems`;
CREATE TABLE IF NOT EXISTS `mapSolarSystems` (
  `regionID` int(11) default NULL,
  `constellationID` int(11) default NULL,
  `solarSystemID` int(11) NOT NULL,
  `solarSystemName` varchar(100) default NULL,
  `x` double default NULL,
  `y` double default NULL,
  `z` double default NULL,
  `xMin` double default NULL,
  `xMax` double default NULL,
  `yMin` double default NULL,
  `yMax` double default NULL,
  `zMin` double default NULL,
  `zMax` double default NULL,
  `luminosity` double default NULL,
  `border` tinyint(4) default NULL,
  `fringe` tinyint(4) default NULL,
  `corridor` tinyint(4) default NULL,
  `hub` tinyint(4) default NULL,
  `international` tinyint(4) default NULL,
  `regional` tinyint(4) default NULL,
  `constellation` tinyint(4) default NULL,
  `security` double default NULL,
  `factionID` int(11) default NULL,
  `radius` double default NULL,
  `sunTypeID` int(11) default NULL,
  `securityClass` varchar(2) default NULL,
  PRIMARY KEY  (`solarSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `staStations`;
CREATE TABLE IF NOT EXISTS `staStations` (
  `stationID` int(11) NOT NULL,
  `security` smallint(6) default NULL,
  `dockingCostPerVolume` double default NULL,
  `maxShipVolumeDockable` double default NULL,
  `officeRentalCost` int(11) default NULL,
  `operationID` tinyint(4) default NULL,
  `stationTypeID` int(11) default NULL,
  `corporationID` int(11) default NULL,
  `solarSystemID` int(11) default NULL,
  `constellationID` int(11) default NULL,
  `regionID` int(11) default NULL,
  `stationName` varchar(100) default NULL,
  `x` double default NULL,
  `y` double default NULL,
  `z` double default NULL,
  `reprocessingEfficiency` double default NULL,
  `reprocessingStationsTake` double default NULL,
  `reprocessingHangarFlag` tinyint(4) default NULL,
  PRIMARY KEY  (`stationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

