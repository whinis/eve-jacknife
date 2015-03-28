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

echo "<h4>Leadership</h4><hr>\n";

if (isLvl5("Leadership")) {
 echo "<table style=\"font-size:88%\">\n";
 test3("Leadership");
 test3("Warfare Link Specialist");
 test3("Armored Warfare Specialist");
 test3("Information Warfare Specialist");
 test3("Siege Warfare Specialist");
 test3("Skirmish Warfare Specialist");
 echo "</table>\n";
  echo "<br>\n";
 test_module_fit_true("Armored Warfare Mindlink");
 test_module_fit_true("Information Warfare Mindlink");
 test_module_fit_true("Siege Warfare Mindlink");
 test_module_fit_true("Skirmish Warfare Mindlink");
 test_module_fit_true("Mining Forman Mindlink");
  echo "<br>\n";
 test_module_fit_true(array("Eos","Information Warfare Link - Recon Operation"),"a booster eos");
 test_module_fit_true(array("Damnation","Armored Warfare Link - Damage Control"),"a booster damnation");
 test_module_fit_true(array("Claymore","Skirmish Warfare Link - Evasive Maneuvers"),"a booster claymore");
 test_module_fit_true(array("Vulture","Siege Warfare Link - Active Shielding"),"a booster vulture");

 test_module_fit_true(array("Damnation","Armored Warfare Link - Damage Control","Skirmish Warfare Link - Evasive Maneuvers"),"an atlas damnation");
 test_module_fit_true(array("Claymore","Skirmish Warfare Link - Evasive Maneuvers","Siege Warfare Link - Active Shielding"),"an atlas claymore");
 test_module_fit_true(array("Vulture","Siege Warfare Link - Active Shielding","Skirmish Warfare Link - Evasive Maneuvers"),"an atlas vulture");

 echo "<br>\n";
 echo "FC/WC: "; 
 echo skillLvl("Fleet Command") ."/";
 echo skillLvl("Wing Command") ."<br>\n";
 
 echo "Sq Skills: ";
 echo skillLvl("Armored Warfare") ."/";
 echo skillLvl("Information Warfare") ."/";
 echo skillLvl("Siege Warfare") ."/";
 echo skillLvl("Skirmish Warfare") ."<br>\n";
 
} else 
 echo "No leadership skills.\n";

 ?>