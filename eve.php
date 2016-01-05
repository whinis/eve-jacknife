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
require_once("eveApi.base.php");


//Auto load the eveApi pages
$files=scandir("./eveApi/");
$includes=array();

foreach ($files as $file){
    if(pathinfo($file, PATHINFO_EXTENSION)=="php"){
        include_once("./eveApi/".pathinfo($file, PATHINFO_BASENAME));
    }
}

if(!isset($sql_port)){
    $sql_port=3306;
}
$Db = new eveDb();
$Db->loadByParams($sql, $sql_u, $sql_p, $db,$sql_port,false);

 ?>