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

// item search page
 ?>
<html>
<head>
<title>Item Search</title>
</head>
<body>
<form action="itemsrch.php" method="get">
<input type="text" size=50 name="srch" value="<?php if (isset($_GET['srch'])) echo $_GET['srch'];  ?>">
<input type="submit" value="Search">
</form>
<?php 

require_once("eve.php");

$Db = new eveDb($sql, $sql_u, $sql_p, $db);

if (isset($_GET['srch'])) {
 $srch = $_GET['srch'];
 
 if (strlen($srch) < 3)
  die("Enter at least 3 letters!</body></html>");
  
 if (strpos($srch, "group:") !== false) {
  $sql    = "SELECT typeName, typeID FROM ".DB_PREFIX."invTypes WHERE groupId = ".mysql_real_escape_string(substr($srch,strpos($srch, "group:")+6))." ORDER BY typeName";	
 } else if (strpos($srch, "race:") !== false) {
  $sql    = "SELECT typeName, typeID FROM ".DB_PREFIX."invTypes WHERE raceId = ".mysql_real_escape_string(substr($srch,strpos($srch, "race:")+5))." ORDER BY typeName";	
 } else
  $sql    = "SELECT typeName, typeID FROM ".DB_PREFIX."invTypes WHERE typeName LIKE '%".mysql_real_escape_string($srch)."%' ORDER BY typeName";
 
 $result = $Db->query($sql);
 
 echo "Results for '".$srch."':&nbsp;&nbsp;";
 
 if (!$result) {
  echo "No items found.<br>";
 } else {
 
  echo mysql_num_rows($result). " found ";

  echo "<br>\n";
  
  while ($row = mysql_fetch_assoc($result)) {
   echo "<a name=\"".$row['typeID']."\" href=\"itemview.php?id=" . $row['typeID'] . "&srch=".$srch."\">".$row['typeName']."</a><br>";
  }
  mysql_free_result($result);
 
 }
}

$Db->close();
 ?>
</body>
</html>