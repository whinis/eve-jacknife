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

echo "<h4>Fleet Ships</h4><hr><span style=\"font-size:90%\">\n";
echo "<center>Pvp</center>";

$snipebs = false;
$snipebs |= test7(array("Tempest","1400mm Howitzer Artillery II", "Adaptive Nano Plating II"),"a snipe 'pest");
$snipebs |= test7(array("Rokh","425mm Railgun II", "Invulnerability Field II", "Large Shield Extender II"),"a snipe rokh");
$snipebs |= test7(array("Apocalypse","Tachyon Beam Laser II","Adaptive Nano Plating II"),"a snipe apoc");
$snipebs |= test7(array("Megathron","425mm Railgun II","Adaptive Nano Plating II"),"a snipe mega");
$snipebs |= test7(array("Raven","Cruise Missile Launcher II","Invulnerability Field II"),"a 'snipe' raven");
if (!$snipebs)
 echo "can't use any snipe bs<br>\n";

echo "<br>\n";

$rrbs = false;
$rrbs |= test7(array("Tempest","800mm Repeating Artillery II", "Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a rr 'pest");
$rrbs |= test7(array("Typhoon","800mm Repeating Artillery II","Ogre II","Siege Missile Launcher II", "Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a rr 'phoon");
$rrbs |= test7(array("Armageddon","Mega Pulse Laser II","Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a rr geddon");
$rrbs |= test7(array("Megathron","Neutron Blaster Cannon II","Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a rr mega");
$rrbs |= test7(array("Dominix","Ogre II","Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a hospice domi");
$rrbs |= test7(array("Raven","Siege Missile Launcher II","Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a rr raven");
$rrbs |= test7(array("Scorpion","ECM - Multispectral Jammer II","Cruise Missile Launcher I","Adaptive Nano Plating II","Large Remote Armor Repair System I"),"a rr scorpion");
if (!$rrbs)
 echo "can't use any rr bs<br>\n";

echo "<br>\n";

$snipehac = false;
$snipehac |= test7(array("Deimos","Tracking Enhancer II","250mm Railgun II"),"a snipe deimos");
$snipehac |= test7(array("Zealot","Tracking Enhancer II","Heavy Beam Laser II"),"a snipe zealot");
$snipehac |= test7(array("Muninn","Tracking Enhancer II","720mm Howitzer Artillery II"),"a snipe muninn");
$snipehac |= test7(array("Eagle","Tracking Computer II","250mm Railgun II"),"a snipe eagle");
if (!$snipehac)
 echo "can't use any snipe hac<br>\n";

echo "<br>";
$pvp = false;
if(!$ishtar=test7(array("Ishtar","Drone Link Augmentor II","Drone Damage Amplifier II","Large Shield Extender II","Hammerhead II","Curator II","Garde II"),"T2 Sentry Ishtar")){
    $ishtar=test7(array("Ishtar","Drone Link Augmentor II","Drone Damage Amplifier II","Large Shield Extender II","Hammerhead II","Curator I","Garde I"),"T1 Sentry Ishtar");
}
$pvp|=$ishtar;
$pvp|=test7(array("Tengu","250mm Railgun II","Large Shield Extender II","Adaptive Invulnerability Field II","Magnetic Field Stabilizer II"),"Rail Tengu");

if(!$harpy=test7(array("Harpy","150mm Railgun II","Adaptive Invulnerability Field II","Magnetic Field Stabilizer II"),"Rail Harpy")){
    $harpy=test7(array("Merlin","Limited Light Neutron Blaster I","Initiated Harmonic Warp Scrambler I","Experimental 1MN Afterburner I"),"Tackle Merlin");
}
$pvp|=$harpy;
if (!$pvp)
    echo "can't use fleet pvp ships<br>\n";

echo "<br>\n";

$pve = false;

if (!$pve |= test7(array("Drake","Heavy Missile Launcher II","Large Shield Extender II"),"a t2 drake")) 
 $pve |= test7(array("Drake","Heavy Missile Launcher I","Large Shield Extender I"),"a t1 drake");

if (!$pve |= test7(array("Raven","Cruise Missile Launcher II","Large Shield Booster II"),"a t2 pve raven"))
 $pve |= test7(array("Raven","Cruise Missile Launcher I","Large Shield Booster I"),"a t1 pve raven");
 
if (!$pve |= test7(array("Dominix","Ogre II","Large Armor Repairer II"),"a t2 pve domi"))
 $pve |= test7(array("Dominix","Ogre I","Large Armor Repairer I"),"a t1 pve domi");

if (!$pve |= test7(array("Tempest","800mm Repeating Artillery II"),"a t2 pve pest"))
 $pve |= test7(array("Tempest","800mm Repeating Artillery I"),"a t1 pve pest");

if (!$pve |= test7(array("Armageddon","Ogre I","Mega Pulse Laser II","Large Armor Repairer II"),"a t2 pve geddon"))
 $pve |= test7(array("Armageddon","Ogre I","Mega Pulse Laser I","Large Armor Repairer I"),"a t1 pve geddon");

 
if ($pve) {
 echo "<br>\n";
} else 
echo "can't use a ratting ship<br><br>\n";

$dictors = false;

$dictors |= test7(array("Sabre","Interdiction Sphere Launcher I", "Medium Shield Extender I"));
$dictors |= test7(array("Eris","Interdiction Sphere Launcher I", "Medium Shield Extender I"));
$dictors |= test7(array("Heretic","Interdiction Sphere Launcher I", "Medium Shield Extender I"));
$dictors |= test7(array("Flycatcher","Interdiction Sphere Launcher I", "Medium Shield Extender I"));

if (!$dictors)
echo "can't use any dictors<br>\n";
echo "<br>\n";

$logi = false;

if(!$logi |= test7(array("Guardian","Large Remote Armor Repairer II", "Large Remote Capacitor Transmitter II"),"a t2 Guardian"))
	$logi |= test7(array("Guardian","Large Remote Armor Repairer I", "Large Remote Capacitor Transmitter I"),"a Guardian");
if(!$logi |= test7(array("Scimitar","Large Remote Shield Booster II", "10MN Microwarpdrive I"),"a t2 Schimitar"))
	$logi |= test7(array("Scimitar","Large Remote Shield Booster I", "10MN Microwarpdrive I"),"a t2 Schimitar");
if(!$logi |= test7(array("Oneiros","Large Remote Armor Repairer II", "10MN Microwarpdrive I"),"a t2 Oneiros"))
	$logi |= test7(array("Oneiros","Large Remote Armor Repairer I", "10MN Microwarpdrive I"),"a Onerios");
if(!$logi |= test7(array("Basilisk","Large Remote Shield Booster II", "Large Remote Capacitor Transmitter II"),"a t2 Basilisk"))
	$logi |= test7(array("Basilisk","Large Remote Shield Booster I", "Large Remote Capacitor Transmitter I"),"a t2 Basilisk");

if (!$logi)
	echo "can't use any logi<br>\n";

echo "<br>\n";
echo " <center> Mining </center>";
// mining Ships
$exhumer = false;

if(!$exhumer |= test7(array("Hulk","Modulated Strip Miner II", "Mining Laser Upgrade II"),"a t2 Hulk"))
	$exhumer |= test7(array("Hulk","Strip Miner I", "Mining Laser Upgrade I"),"a Hulk");
if(!$exhumer |= test7(array("Skiff","Modulated Deep Core Strip Miner II", "Mining Laser Upgrade II","Mercoxit Mining Crystal II"),"a t2 Skiff"))
	$exhumer |= test7(array("Skiff","Strip Miner I", "Mining Laser Upgrade I"),"a t2 Skiff");
if(!$exhumer |= test7(array("Mackinaw","Ice Harvester II", "Ice Harvester Upgrade II"),"a t2 Mackinaw"))
	$exhumer |= test7(array("Mackinaw","Ice Harvester I", "	Ice Harvester Upgrade I"),"a Mackinaw");

if($exhumer)
	echo "<br>\n";
$barge = false;

	$barge |= test7(array("Retriever","Strip Miner I", "Mining Laser Upgrade I"),"a Retriever");
	$barge |= test7(array("Procurer","Strip Miner I", "Mining Laser Upgrade I"),"a Procurer");
	$barge |= test7(array("Covetor","Strip Miner I", "Mining Laser Upgrade I"),"a Covetor");

if($barge)
	echo "<br>\n";
if (!$barge&&!$exhumer)
	echo "<br>can't use any mining ships<br>\n";	
elseif (!$exhumer&&$barge)
	echo "<br>can't use any exhumers<br>\n";
elseif ($exhumer&&!$barge)
	echo "<br>can't use any mining barges<br>\n";

echo "<br>\n";

// Capitals


echo "<center> Capitals</center>";
$capital=false;
$capital|=test7(array("Orca","Mining Foreman Link - Harvester Capacitor Efficiency I", "Small Tractor Beam I", "Expanded Cargohold II"),"a Orca");

$capital|=test7(array("Moros","Capital Armor Repairer I","Dual 1000mm Railgun I","Siege Module I"),"a moros");
$capital|=test7(array("Revelation","Capital Armor Repairer I","Dual Giga Beam Laser I","Siege Module I"),"a revelation");
$capital|=test7(array("Naglfar","Capital Shield Booster I", "Quad 3500mm Siege Artillery I","Siege Module I"),"a naglfar");
$capital|=test7(array("Phoenix","Capital Shield Booster I", "Citadel Cruise Launcher I", "Siege Module I"),"a phoenix");

$capital|=test7(array("Thanatos","Capital Armor Repairer I", "Fighters"),"a thanatos",($SkillsApi->canCharUseTypeNames(array("Capital Remote Armor Repair System I","Capital Remote Shield Booster I"))?"well":"poorly"));

$capital|=test7(array("Archon","Capital Armor Repairer I", "Fighters"),"an archon",($SkillsApi->canCharUseTypeNames(array("Capital Remote Armor Repair System I","Capital Energy Transfer Array I"))?"well":"poorly"));

$capital|=test7(array("Nidhoggur","Capital Armor Repairer I", "Fighters"),"a nidhoggur",($SkillsApi->canCharUseTypeNames(array("Capital Remote Armor Repair System I","Capital Remote Shield Booster I"))?"well":"poorly"));

$capital|=test7(array("Chimera","Capital Shield Booster I", "Bouncer I", "Fighters"),"a chimera",($SkillsApi->canCharUseTypeNames(array("Capital Energy Transfer Array I","Capital Remote Shield Booster I"))?"well":"poorly"));

if (!$capital)
	echo "<br>can't use any capitals<br>\n";	

echo "</span>";
 ?>