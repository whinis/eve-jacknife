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

echo "<h4>Misc Skills</h4><hr>";
echo "<table style=\"font-size:88%\">\n";
echo "<tr><td colspan=2><center>Scanning</center></td></tr>";
test3("Astrometrics");
test3("Astrometric Acquisition");
test3("Astrometric Pinpointing");
test3("Astrometric Rangefinding");
echo "<tr><td colspan=2><center>Mining</center></td></tr>";
test3("Mining");
test3("Mining Upgrades");
test3("Astrogeology");
test3("Ice Harvesting");
test3("Gas Cloud Harvesting");
test3("Deep Core Mining");
test3("Mining Foreman");
test3("Mining Director");

echo "<tr><td colspan=2><center>Others</center></td></tr>";
test3("Cynosural Field Theory");

echo "</table>";


 ?>