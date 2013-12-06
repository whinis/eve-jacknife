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

if (isset($skillInf) && $skillTraining) {
echo "<span style=\"font-size:80%\" blah=\"".$skillInf->timeLeftRaw."\">";
 echo "training ". $skillInf->skillName . " " . $skillInf->skillLvl . "<br>";
 echo "<div id=\"skilltime\">".$skillInf->timeLeft . "</div></span><br><br>
 <script type=\"text/javascript\">
 update_skill_time(".$skillInf->timeLeftRaw.")
 </script>
 ";
}

// (C) ZZJ 5/xx/10

echo "<table width=100% style=\"font-size:87%\">";
foreach ($SkillsApi->Skills as $group => $skills) {
 $skillc = (count($skills)-1);
 
 echo "<tr style=\"cursor:pointer;\" onclick=\"return toggle_visibility('".$group."');\"><td>";
 echo "<a href=\"#\" onclick=\"return false\">".$group ."</a>&nbsp;&nbsp;";
 echo "</td><td>".number_format($skills[0],0) ."&nbsp;SP<br>\n" . $skillc ." skill" . ($skillc!=1?"s":"") ." total</td></tr>\n";
 echo "<tr><td colspan=2><div id=\"".$group."\" style=\"display:none\">\n";
 echo "<table class='fancy' style=\"font-size:100%\">\n";
 
 foreach ($skills as $skill => $level) {
  if ($skill == "0") // sp count entry
   continue;
  
  $col = lvlToColour($level);
  
  echo "<tr><td width=\"200\">".$skill."</td>";
  echo "<td style=\"color: #000000; font-size:120%; background-color: ".$col.";\">&nbsp;".$level."&nbsp;</td></tr>\n";
 
 }
 
 echo "</table></div></td></tr>\n";

}

echo "</table>";
   
 ?>