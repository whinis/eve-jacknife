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
 test_has_skill("Starbase Defense Management","Pos Guns");
 
test_module_fit("21096","Cyno Gen");

echo "<br>";

if (!test_module_fit_true("12084","T2 MWDs"))
 test_module_fit("12054","T1 MWDs");
 
test_module_fit("3841", "LSE II");
test_module_fit("2281", "Invuln II");
test_module_fit("11269","EANM II");
test_module_fit("2048", "DCU II");
test_module_fit("11325","1600mm RT");
test_module_fit("1952","SB II");
test_module_fit("1978", "TC II");
test_module_fit("1999", "TE II");
test_module_fit("1541","PDU II");
test_module_fit("1355","RCU II");
test_module_fit("519","T2 dmg mods");

if(!test_module_fit_true("11578","CovOps Cloak"))
 test_module_fit("11370","Proto Cloak");

test_module_fit("3244","T2 Tackle");
test_module_fit("527", "Webifier II");
test_module_fit("10858", "LSB II");
test_module_fit("3540", "LAR II");

echo "</span>";
 ?>