<?php 
// ****************************************************************************
// 
// ZZJ Audit Tool v1.5
// Copyright (C) 2010  ZigZagJoe (zigzagjoe@gmail.com) and
// Copyright (C) 2012  Equto   (whinis@whinis.com)
// 
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License,or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,


// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc.,59 Temple Place,Suite 330,Boston,MA  02111-1307  USA
// 
// ****************************************************************************

// config file

$sql = "localhost";
$sql_u = "root";
$sql_p = "790825";
$db ="jack";

define("DB_PREFIX","jack_");
define("DEBUG","TRUE");

//Email Settings
ini_set("sendmail_from", "");
ini_set("SMTP", "127.0.0.1");
ini_set("smtp_port", "25");


// Login Settings
$secureTimeout = "300";
$salt = "bmneNR9s5juwvwv";

date_default_timezone_set ("UTC");

define("API_TABLE","api_lookup");
define("FITTINGS_TABLE","fit_lookup");
define("CACHE_TABLE","api_cache");
define("ID_CACHE_TABLE","id_cache");
define("TYPE_CACHE_TABLE", "api_type_cache");
define("CONTRACT_BIDS_TABLE", "contract_bids");
define("CONTRACT_CONTENTS_TABLE", "contract_items");

 ?>