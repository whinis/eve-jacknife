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

// (C) ZZJ 5/xx/10

echo "<h4>Weapons</h4><hr>";
echo "\n<table style=\"font-size:95%\">\n";
echo "<tr><th>type</th><th>S</th><th>M</th><th>L</th></tr><tr>\n";
echo "<td>lasers</td>\n";
test4("Small Energy Turret");
test4("Medium Energy Turret");
test4("Large Energy Turret");
echo "</tr>\n<tr>";
echo "<td>hybrids</td>\n";
test4("Small Hybrid Turret");
test4("Medium Hybrid Turret");
test4("Large Hybrid Turret");
echo "</tr>\n<tr>";
echo "<td>projektiles</td>\n";
test4("Small Projectile Turret");
test4("Medium Projectile Turret");
test4("Large Projectile Turret");
echo "</tr>\n<tr>";
echo "<td>guided m</td>\n";
test4("Light Missiles");
test4("Heavy Missiles");
test4("Cruise Missiles");
echo "</tr>\n<tr>";
echo "<td>unguided</td>\n";
test4("Rockets");
test4("Heavy Assault Missiles");
test4("Torpedoes");
echo "</tr>";
echo "</table>\n<br>\n";

if (!isLvl5("Drones")) {
 echo "Does NOT have Drones 5<br>\n";
} else {

 if (isLvl5("Scout Drone Operation")) {
  echo "Has T2 Lights<br>\n"; 
 } else 
  test_has_skill("Scout Drone Interfacing","Light Drones");
 
 if (isLvl5("Heavy Drone Operation")) {
  echo "Has T2 Heavies<br>\n";
 } else 
  test_has_skill("Heavy Drone Operation","Heavy Drones");
  
 if (isLvl5("Sentry Drone Interfacing")) {
  echo "Has T2 Sentries<br>\n"; 
 } else 
  test_has_skill("Sentry Drone Interfacing","Sentry Drones");
  
 if (isLvl5("Fighters") && (skillLvl("Fighter Bombers")!=-1)) {
 test_has_skill("Fighter Bombers","Fighter Bombers");
  
} else 
 test_has_skill("Fighters","Fighters");
 
 test_has_skill("Drone Interfacing");
}

$delim = "&nbsp;";
$delim = "</td><td>";

echo "<br><table><tr><td>WU/AWU:</td><td>";
echo skillLvl("Weapon Upgrades") .$delim;
echo skillLvl("Advanced Weapon Upgrades");
echo "</td></tr><tr><td>CPUM/PGM:</td><td>";
echo skillLvl("CPU Management") .$delim;
echo skillLvl("Power Grid Management");
echo "</td></tr></table><br>Support skills:\n<br><table style=\"font-size:95%\"><tr>";
echo "<td>turr&nbsp;</td><td>";

echo skillLvl("Controlled Bursts") .$delim;
echo skillLvl("Gunnery") .$delim;
echo skillLvl("Motion Prediction") .$delim;
echo skillLvl("Rapid Firing") .$delim;
echo skillLvl("Sharpshooter") .$delim;
echo skillLvl("Surgical Strike") .$delim;
echo skillLvl("Trajectory Analysis");
echo "</td></tr><tr><td>\n";  
echo "miss&nbsp;</td><td>";
echo skillLvl("Guided Missile Precision") .$delim;
echo skillLvl("Missile Bombardment") .$delim;
echo skillLvl("Missile Launcher Operation") .$delim;
echo skillLvl("Missile Projection") .$delim;
echo skillLvl("Rapid Launch") .$delim;
echo skillLvl("Target Navigation Prediction") .$delim;
echo skillLvl("Warhead Upgrades");
echo "</td></tr></table><br>\n";  

 ?>