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

echo "<h4>Modules</h4><hr><span style=\"font-size:90%\">\n";
  
if (isLvl5("Anchoring") && (skillLvl("Starbase Defense Management")!=-1))
 test5("Starbase Defense Management","Pos Guns");
 
test("Cynosural Field Generator I","Cyno Gen");

echo "<br>";

if (!test2("100MN Microwarpdrive II","T2 MWDs"))
 test("100MN Microwarpdrive I","T1 MWDs");
 
test("Large Shield Extender II", "LSE II");
test("Invulnerability Field II", "Invuln II");
test("Energized Adaptive Nano Membrane II","EANM II");
test("Damage Control II", "DCU II");
test("1600mm Reinforced Rolled Tungsten Plates I","1600mm RT");
test("Sensor Booster II","SB II");
test("Tracking Computer II", "TC II");
test("Tracking Enhancer II", "TE II");
test("Power Diagnostic System II","PDU II");
test("Reactor Control Unit II","RCU II");
test("Gyrostabilizer II","T2 dmg mods");

if(!test2("Covert Ops Cloaking Device II","CovOps Cloak"))
 test("Prototype Cloaking Device I","Proto Cloak");

test("Warp Disruptor II","T2 Tackle");
test("Stasis Webifier II", "Webifier II");
test("Large Shield Booster II", "LSB II");
test("Large Armor Repairer II", "LAR II");

echo "</span>";
 ?>