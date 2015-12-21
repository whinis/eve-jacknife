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

// main eveAPI file. 

set_include_path(get_include_path() . PATH_SEPARATOR . "eveApi");

require_once("eve.config.php");
require_once("eve.funcs.php");

require_once("eveApiCaching.php");
require_once("class_db.php");
require_once("eveDb.php");
require_once("eveAccessMasks.php");
require_once("eveApi.base.php");

require_once("eveApiOrders.class.php");
require_once("eveApiTransactions.class.php");
require_once("eveApiJournal.class.php");
require_once("eveApiSkills.class.php");
require_once("eveApiTraining.class.php");
require_once("eveApiAssets.class.php");
require_once("eveApiKillLog.class.php");
require_once("eveApiMails.class.php");
require_once("eveApiNotifications.class.php");
require_once("eveApiMembers.class.php");
require_once("eveApiContracts.class.php");
require_once("eveApiContacts.class.php");
require_once("eveApiCharacterID.class.php");
require_once("eveApiCharacterAffiliations.class.php");
require_once("eveApiAccount.class.php");

if(!isset($sql_port)){
    $sql_port=3306;
}
$Db = new eveDb($sql, $sql_u, $sql_p, $db,$sql_port,false);

 ?>