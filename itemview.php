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

// item viewer

if (!isset($_GET['id'])) {
 include("itemsrch.php");
 exit;
} 

require_once("eve.php");

$id = (int)$_GET['id'];

$Db = new eveDb($sql, $sql_u, $sql_p, $db);
$name = $Db->getNameFromTypeId($id);
 ?>
<html>
<head>
<title><?php echo $name ?></title>
</head>
<body>
<?php 

$sql = "SELECT description FROM ".DB_PREFIX."invTypes WHERE typeID = '".mysql_real_escape_string($id)."'";
 
 $result = $Db->query($sql);
 
if (!$result) 
 exit;
 
$desc = mysql_fetch_assoc($result);
$desc = $desc['description'];
mysql_free_result($result);

$sql = "SELECT groupID,radius,mass,volume,capacity,portionSize,raceID,published FROM ".DB_PREFIX."invTypes WHERE typeID = '".mysql_real_escape_string($id) ."' LIMIT 1";
 
$result = $Db->query($sql);
 
if (!$result)
 exit;
 ?>
<table>
<tr><td>
<h2><?php echo $name; ?></h2>

</td><td>
<button type="button" onclick="CCPEVE.showInfo(<?php echo $id; ?>)">Show Info</button><br>
<button type="button" onclick="CCPEVE.showPreview(<?php echo $id; ?>)">IG Preview</button><br>
<a href="itemsrch.php<?php echo (isset($_GET['srch'])?("?srch=".$_GET['srch']):"");  ?>#<?php echo $id; ?>">Back to search</a>
<br>
</td></tr>
<tr><td colspan=2><div style="font-size:13;width:350px;border:1px solid blue;">
<?php echo (($desc != "")?str_replace("\n","<br>\n",$desc):"No description present.");  ?>
</div>
</td></tr>
<tr><th>Attribute</th><th>Value</th></tr>
<?php 
function str_contains($str, $value) {
 return strpos($str,$value) !== false;
}

function str_icontains($str, $value) {
 return stripos($str,$value) !== false;
}

function value_units($value, $name) {
 if (str_contains($name,"Resistance"))
  if ($value != 0) {
   $value = (1-$value) * 100 ." %";
  } else $value = "0 %";
  
 if (str_contains($name,"Bounty")) 
  $value = $value . " ISK";
  
 if (str_contains($name,"volume")) 
 
 if ($value > 1000) {
  $value = $value/1000 . " k m3";
 } else $value = $value . " m3";
/* if (str_contains($name,"")) 
  $value = $value;*/
   
 if (str_contains($name, "published")) 
  $value = ($value == 1)?"true":"false";
 if (str_contains($name,"capacity")) 
  $value .= " m3";
 if ($name == "radius" || str_icontains($name,"falloff") || str_icontains($name,"optimal") || str_icontains($name,"range") || str_icontains($name,"distance") || str_contains($name,"Signature Radius"))
  $value .= " m";   
 if ($name=="Rate of fire") 
 if ($value > 1000) {
  $value = $value/1000 . " s";
 } else $value = $value . " s";
 
 if ($name=="Signature Resolution")
  $value .= " mm";
  
 if (!str_icontains($name,"chance")) {
  if (str_icontains($name,"duration") || $name == "Activation time / duration") {
   $value = $value/1000 . "s";
  } else if (str_contains($name,"time") && $name != "Activation time / duration") 
   $value = $value/1000 . "s";
 }
 if ((str_icontains($name,"bonus") && !str_icontains($name,"duration") && ($name != "Power Bonus") && ($name != "shield Bonus") && ($name != "armor Bonus"))|| str_icontains($name,"modifier") ||  str_icontains($name,"Multiplier")) 
  $value = "x".$value;
 if (str_contains($name,"mass")) 
  $value .= " kg";
 if (str_contains($name,"powergrid") || $name == "Power Bonus")
  $value .= " mw";
 if ($name == "Drone Bandwidth")
  $value .= " mbit";
  
   if ($name == "Trackingspeed / Accuracy")
  $value .= " rad/s";
 if (str_contains($name,"CPU") && !str_icontains($name,"bonus"))
  $value .= " tf";
 if (str_contains($name,"Velocity")) 
  $value .= " ms"; 
 return $value;   
}

$row = mysql_fetch_assoc($result);
mysql_free_result($result);

foreach($row as $key => $value) {
 echo "<tr><td>";
 
 echo $key;
 echo "</td><td>";
 echo value_units($value, $key);
 if ($key == "groupID")
  echo "&nbsp;&nbsp;<a href=\"itemsrch.php?srch=group%3A" . $value ."\">See others...</a>";
	
 if ($key == "raceID" && $value != "")
  echo "&nbsp;&nbsp;<a href=\"itemsrch.php?srch=race%3A" . $value ."\">See others...</a>";
  
 echo "</td></tr>\n";
}

$sql = "SELECT attributeID, valueInt, valueFloat FROM ".DB_PREFIX."dgmTypeAttributes WHERE typeID = '".mysql_real_escape_string($id)."'";
 
$result = $Db->query($sql);
 
if (!$result)
 exit;

while ($row = mysql_fetch_assoc($result)) {
 $attr = $Db->getAttribInfo($row['attributeID']);
 $name = $attr['displayName'] != "" ? $attr['displayName'] : $attr['attributeName'];
 $value = $row['valueInt'] != "" ? $row['valueInt'] : $row['valueFloat'];
 $attr_id = $row['attributeID'];
 
 echo "<tr><td>";
 echo $name;
 echo "</td><td>";
 
 if ($attr_id > 181 && $attr_id < 185) {
  echo $Db->getNameFromTypeId($value);
 } else {
  $value = value_units($value, $name);
  
  echo $value;
 }
  
 echo "</td></tr>\n";
}

mysql_free_result($result);

$sql = "SELECT effectID FROM ".DB_PREFIX."dgmTypeEffects WHERE typeID = '".mysql_real_escape_string($id)."'";
$result = $Db->query($sql);
 
if ($result) {
 echo "<tr><th colspan=2>Effects</th></tr>\n";
 
 while ($row = mysql_fetch_assoc($result)) {
  $effect = $Db->getEffectInfo($row['effectID']);
  echo "<tr><td colspan=2>" . "($row[effectID]): ";
  echo $effect['displayName'] != "" ? $effect['displayName'] : $effect['effectName'];
  echo "</td></tr>\n";
 }

 mysql_free_result($result);
}
$Db->close();

echo "</table>";
 ?>
</body></html>