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

echo "<h4>Capital Ships</h4><hr>\n";
echo "<table style=\"font-size:88%\">\n";

test3("Capital Ships");
test3("Advanced Spaceship Command","Adv. Spaceship Cmd.");
test3("Jump Freighters");
test3("Doomsday Operation");
test3("Jump Portal Generation","Portal Generation");
test3("Tactical Logistics Reconfiguration","Triage");
test3("Tactical Weapon Reconfiguration","Siege");
test3("Industrial Reconfiguration","Indy Siege");

tbBr();
test3("Amarr Titan");
test3("Minmatar Titan");
test3("Gallente Titan");
test3("Caldari Titan");
tbBr();
test3("Gallente Freighter");
test3("Amarr Freighter");
test3("Minmatar Freighter");
test3("Caldari Freighter");
tbBr();
test3("Gallente Carrier");
test3("Amarr Carrier");
test3("Minmatar Carrier");
test3("Caldari Carrier");
tbBr();
test3("Gallente Dreadnought");
test3("Amarr Dreadnought");
test3("Minmatar Dreadnought");
test3("Caldari Dreadnought");
echo "</table>\n<br>";
echo "<a href=\"#\" style=\"font-size:80%;\" onclick=\"return toggle_visibility('capskills');\">Capital Support Skills</a><br>";
echo "<table style=\"font-size:88%; display:none;\" id=\"capskills\">";
test3("Capital Energy Turret");
test3("Capital Hybrid Turret");
test3("Capital Projectile Turret","Capital Proj. Turret");
test3("Citadel Cruise Missiles","Citadel Cruise");
test3("Citadel Torpedoes");
tbBr();
test3("Capital Capacitor Emission Systems","Cap Energy Trans");
test3("Capital Shield Emission Systems","Cap Shield Trans");
test3("Capital Remote Hull Repair Systems","Cap Remote Hull");
test3("Capital Remote Armor Repair Systems","Cap Remote Armour");
tbBr();
test3("Capital Repair Systems","Cap Armour Rep");
test3("Capital Shield Operation","Cap Shield Boost");
echo "</table><br>\n";


echo "<br>\n";
echo "jump skills: " . skillLvl("Jump Drive Operation") ."&nbsp;" .skillLvl("Jump Drive Calibration")."&nbsp;".skillLvl("Jump Fuel Conservation")."<br>";  


 
  ?>