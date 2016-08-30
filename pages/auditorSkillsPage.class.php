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

//default audit view
include_once("auditorPage.base.php");
set_include_path(get_include_path() . PATH_SEPARATOR . "skills");
$SkillsApi = null;

function parse_eft_fit($Db, $fit, &$warnings, &$ship, &$name) {
	$warnings = "";
	$c = 0;

	$shipname = "";
	$typeIDs = array();

	foreach (explode("\n", $fit) as $line_in) {
		$c++;
		$line = trim($line_in);
		if (strlen($line) == 0)
			continue;

		if (strpos($line, "[empty") === 0)
			continue;

		if ($line[0] == '[') { // ship type and name
			if ($ship == "") {
				$test = trim($line,"[]");
				if (strpos($test,',') !== FALSE) {
					$bits = explode(',', $test, 2);
					$ship = trim($bits[0]);
					$name = trim($bits[1]);
				} else {
					$ship = $test;
					$name = "Untitled fit";
				}
			}
			$line = substr($line,1);

		}
		if (strpos($line,',') !== FALSE)
			$line = substr($line,0,strpos($line,','));

		if (strpos($line,'(') !== FALSE)
			$line = trim(substr($line,0,strpos($line,'(')));

		$matches = array();
		preg_match("/ x([0-9]{1,3})$/", $line, $matches, PREG_OFFSET_CAPTURE);
		$count = 1;
		if (count($matches) != 0) {
			$count = $matches[1][0];
			$line = trim(preg_replace("/ x([0-9]{1,3})$/", "", $line));
		}

		$ret = $Db->getTypeIdFromName($line);
		if (!$ret) {
			$warnings .= "line $c: Unrecognized item '$line'<br>";
		} else if (!isset($typeIDs[$ret])) {
			$typeIDs[$ret] = $count;
		} else
			$typeIDs[$ret]+=$count;
	}

	return $typeIDs;
}

class auditorSkillsPage extends auditorPage {
	public function GetName() { return "skills"; }
	public function GetAccMode() { return ACC_CHAR_ONLY; }
	public function GetAccMask($corp) {
		if(SSO_MODE)
			return Skills && Clones;
		else
			return CharacterSheet;
	}
	public $stored = false;
	public $key = "";
	public $redirect = false;

	public function SetHeaders() {
		if ($this->redirect) {
			header("Location: ".FULL_URL."&view=skills&fittingid={$this->key}");
		}
	}

	public function GetOutput($Db) {
		global $SkillsApi;
		$full_url = FULL_URL; // TODO
		$small = isset($_GET['small']);

		$SkillsApi = new eveApiSkills($Db);
		$time_start = microtime_float();

		if (!$SkillsApi->fetch(CHAR_ID, USER_ID, API_KEY)) {
			$this->Output = $SkillsApi->Error;
			return false;
		}

		$HistoryApi = new eveApiChar($Db);
		$uid=null;
		$akey=null;
		if(!SSO_MODE) {
			$uid=USER_ID;
			$akey=API_KEY;
		}
		if (!$HistoryApi->fetch(CHAR_ID,$uid,$akey)) {
			$this->Output = $HistoryApi->Error;
			return false;
		}

		if (isset($_GET['fitcheck']) || isset($_GET['fittingid'])) {
			$time_end = microtime_float();
			$time_api = $time_end - $time_start;

			$this->Title = "Jackknife Fitting Checker";

			if (isset($_POST['fitting']) || isset($_GET['fittingid'])) {

				$warnings = "";
				$ship = "";
				$fit_name = "";

				if (isset($_POST['fitting'])) {
					$fit = trim($_POST['fitting']);
					$fitting = parse_eft_fit($Db, $fit, $warnings, $ship, $fit_name);
				} else {
					$result = $Db->selectWhere(FITTINGS_TABLE,['keyv'=>$_GET['fittingid']]);
					if ($result != false && $result->rows > 0) {
						// yay! got a cached value
						$row = $result->results[0];
						$ship = $row["ship"];
						$fit_name = $row["name"];
						$fitting = unserialize($row["fit"]);
						$this->stored = true;
						$this->key = $_GET['fittingid'];
					} else {
						$this->Output .= "<br><b>Fit Key unknown.</b> You muppet.<br>";
						$this->Output .= "<br><a href=\"" . FULL_URL . "&view=skills&fitcheck=1\">[try another fit]</a> <a href=\"" . FULL_URL . "&view=skills\">[back to skills]</a><br>";
						return true;
					}
				}

				if (strlen($warnings) > 0)
					$this->Output .= "<br>\n<b>Fit parsing warnings:</b><br> $warnings\n<h4 style=\"color: red\">Your fit will not be saved until these warnings are resolved.</h4>";

				if (count($fitting) == 0) {
					$this->Output .= "<br><b>FIT WAS UNABLE TO BE PARSED!</b> You muppet.<br>";
					$this->Output .= "<br><a href=\"" . FULL_URL . "&view=skills&fitcheck=1\">[try another fit]</a> <a href=\"" . FULL_URL . "&view=skills\">[back to skills]</a><br>";
					return true;
				} else {
					$canuse = true;

					$low = "";
					$med = "";
					$high = "";
					$rigs = "";
					$drones = "";
					$subsy = "";

					$skillsneeded = "";

					$this->key = md5(serialize($fitting));

					if (!$this->stored && strlen($warnings) == 0) {
						$result = $Db->selectWhere(FITTINGS_TABLE,['keyv'=>$_GET['fittingid']]);

						$this->redirect = true;
						$this->stored = true;
						if ($result == false || $result->rows  == 0) {
							//$sql = "INSERT INTO ".DB_PREFIX.FITTINGS_TABLE." (keyv, name, ship, fit) VALUES('{$this->key}', '".$Db->link->real_escape_string($fit_name) . "', '" . $Db->link->real_escape_string($ship). "', '" . $Db->link->real_escape_string(serialize($fitting)) . "')";
                            $Db->insert(FITTINGS_TABLE,['keyv'=>$this->key,'name'=>$fit_name,'ship'=>$ship,'fit'=>serialize($fitting)]);
							//$Db->link->query($sql);
						}
					}

					foreach ($fitting as $typeID=>$qty) {
						$name = $Db->getNameFromTypeId($typeID);
						$slot = $Db->getSlotFromTypeId($typeID);

						if ($slot == SLOT_DRONE) {
							$drones .= "$name x$qty<br>";
						} else {
							for ($i = 0; $i < $qty; $i++)
								switch ($slot) {
									case SLOT_LOW:	$low .= $name ."<br>\n"; break;
									case SLOT_HIGH:$high .= $name ."<br>\n"; break;
									case SLOT_MED: $med .= $name ."<br>\n"; break;
									case SLOT_RIG: $rigs .= $name ."<br>\n"; break;
									case SLOT_SUBSYSTEM: $subsy .= $name ."<br>\n"; break;
								}
						}
						$arr = $SkillsApi->canCharUseTypeIDAdvanced($typeID);
						if (count($arr) != 0) {
							$canuse = false;
							$skillsneeded .=  "<b><span style=\"color:darkred;\">$name: Missing skills!</span></b>&nbsp;&nbsp;<br><span style=\"font-size: 80%\">\n";
							foreach ($arr as $typeIDSkill=>$missing)
								$skillsneeded .=  $Db->getNameFromTypeId($typeIDSkill) . ": $missing<br>\n";
							$skillsneeded .=  "</span><br>";
						}
					}

					if ($canuse) {
						$this->Output .=  "<h3 style=\"color:green;\">You can use this fit!</h3>\n";
					} else
						$this->Output .=  "<h3 style=\"color:red;\">You are missing skills to use this fit.</b></h3>\n";

					$this->Output .=  "<table class=\"fittingskills\"><tr>";
					if ($skillsneeded != "")
						$this->Output .=  "<th>missing skills</th>";

					$this->Output .=  "<th>fitting</th>";
					$this->Output .=  "</tr><tr>";
					$this->Output .=  "<td valign=\"top\">$skillsneeded</td>";
					$this->Output .=  "<td valign=\"top\">[$ship, $fit_name]<br>$low<br>$med<br>$high<br>";

					if ($rigs != "")
						$this->Output .=  "$rigs<br>";

					if ($subsy != "")
						$this->Output .=  "$subsy<br>";

					if ($drones != "")
						$this->Output .=  "<br>$drones";

					$this->Output .= "</td></tr></table><br>";
				}

				if (strlen($warnings) == 0)
					$this->Output .= "<h4 style=\"display:inline;\" >Fitting Test URL</h4> - Give this link to friends so they can test their skills against this fit!<br><input type=\"text\" value=\"" . SELF_URL . "fittingid={$this->key}\"  onclick=\"this.select()\" size=85 readonly>";


				$this->Output .= "<h4><a href=\"" . FULL_URL . "&view=skills&fitcheck=1\">[try another fitting]</a>&nbsp;";
				if (strlen($warnings) == 0)
					$this->Output .= "<a href=\"" . SELF_URL . "newapi&fittingid={$this->key}\">[try another account]</a>&nbsp;";

				$this->Output .= "<a href=\"" . FULL_URL . "&view=skills\">[back to skills]</a><br></h4>";
			} else {
				$this->Output = "<H4>Paste an EFT-style fit here!</h4><form method=\"post\" action=\"" . FULL_URL . "&view=skills&fitcheck=1\"><textarea name=\"fitting\" cols=\"50\" rows=\"20\"></textarea><br><input type=\"submit\" value=\"Test Ship Fitting\"></form>";
				$this->Output .= "<br><a href=\"" . FULL_URL . "&view=skills\">[back to skills]</a><br>";
			}


			//print_r($typeIDs);
		} else { // skills view
			$skillInf = new eveApiTraining($Db);
			$skillTraining = $skillInf->fetch(CHAR_ID, USER_ID, API_KEY);

			$time_end = microtime_float();
			$time_api = $time_end - $time_start;

			$SkillsApi->loadSkills();

			$itemTypesUsed = array(3336,3327,3332,3328,3339,3335,3331,3337,3333,3329,3338,3334,3330,16591,12095,3413,3392,3318,3300,28609,3435,3426,3449,22761,12093,3432,3431,12096,3428,23950,12099,3354,3348,28656,11579,21611,3456,3455,3402,28667,3424,11207,19719,3380,12092,3453,12098,12097,11569,20494,3352,20495,3351,3350,11572,3349,13209,3411,21889,21888,21890,22442,20405,22474,20069,22468,20070,22446,20124,20533,20342,29029,24563,3421,24562,3412,27906,22043,28585,24625,3387,3347,3345,3344,3346,20527,3340,20524,3343,20528,3341,20526,3342,24313,3442,3436,24311,24314,24312,20531,20525,20532,20530,20327,3309,3306,3303,21666,3307,3304,3301,21667,3308,3305,3302,32435,3319,3326,3324,3321,21668,3325,24572,3423,24571,3422,27936,27902,24568,16069,3393,21803,3394,21802,3419,3416,28352,28374,17940,3410,3386,28606,29637,22552,22536,17476,22544,22551,3320,25719,23069,639,2961,12203,3311,12202,12201,1306,24688,3090,12207,12206,11082,2281,3420,3841,3425,642,3065,12205,12204,11083,641,638,19739,20212,2929,12209,3312,12208,11084,11359,644,2446,3441,12486,2420,20213,643,3057,12215,12214,12213,3186,12212,12211,12210,645,640,2567,3427,13320,12023,1999,3082,12003,3025,12015,2969,12011,1978,3317,16229,24696,24702,16227,24698,2410,20211,10858,3540,2444,22456,22782,11446,3829,22460,22452,22464,19724,20701,20448,23563,23594,23606,20280,19720,20446,19722,20703,20454,32444,19726,24569,3616,23911,12219,23757,24483,23915,21096,21603,12084,3454,3450,11269,2048,11325,1952,1541,1355,519,11578,3244,527);

			// speed things up - cache ahead of time
			$Db->cacheItemTypes($itemTypesUsed);
			$Db->cacheSkillsForTypeIds($itemTypesUsed);
			$Db->cacheGroupTypes(array(257,272,269,273,255,268,256,270,275,266,258,271,274,278));


			if(canAccess(CharacterSheet))
				$characterInfo = $SkillsApi;
			else{
				$characterInfo = $HistoryApi;
				if(canAccess(Clones)) {
					$clone = new eveApiClones($Db);
					$clone->fetch(CHAR_ID, USER_ID, API_KEY);
					$characterInfo->attributes = $clone->attributes;
				}
				if(canAccess(AccountBalance)){
					$balance = new eveApiBalance($Db);
					$balance->fetch(CHAR_ID, USER_ID, API_KEY);
					$characterInfo->balance = $balance->balance;
				}
			}


			$this->Updated = APITime($SkillsApi);
			$this->Title = "Skills for " . CHAR_NAME;
			$this->Header = <<<EOD
<table style="font-size:95%; border-spacing: 10px;">
<tr>
<td VALIGN="top">
<table><tr><td>
<img src="https://image.eveonline.com/Character/
EOD;
			$this->Header .= CHAR_ID."_256.jpg\" height=118 width=118></td><td><span style=\"font-size:";
			$this->Header .= (strlen($characterInfo->charName) > 18) ? "250" : "300";
			$this->Header .= "%\">".$characterInfo->charName."</span><br>";
			$this->Header .= $characterInfo->corpName." <a href='#' id='corpHistory'> (history)</a>";
			$this->Header .= "<br><span style=\"font-size:75%\">";
			$this->Header .= number_format($SkillsApi->SPTotal, 0) ." SP in " . $SkillsApi->SkillCount ." skills<br>";
			if($characterInfo->balance != null)
				$this->Header .= number_format($characterInfo->balance, 2) . " ISK<br>";
			else
				$this->Header .= "N/A ISK<br>";
			if($characterInfo->charDOB != null)
				$this->Header .= "Born ".date("Y-m-d",$characterInfo->charDOB)."<br>";
			else
				$this->Header .= "Born N/A<br>";

			if($HistoryApi->location != null)
				$this->Header .= "Last Location : ".$HistoryApi->location."</span></td></tr>";
			else
				$this->Header .= "Last Location : Not Available</span></td></tr>";

			$this->Header .= "<tr><td colspan=\"2\">";
			$this->Header .= "<span style=\"font-size:75%;\"><a href=\"" . FULL_URL ."&view=skills&fitcheck\">check " . CHAR_NAME."'s skills against a ship fit</a><br><a target=\"_blank\" href=\"http://eve-search.com/search/author/";
			$this->Header .= str_replace(" ", "%20", $characterInfo->charName);
			$this->Header .= "/forum/734105\">search character sale forums for character</a></span><br></td></tr>";
			$this->Header .= <<<EOD
</table>
<br>
<br></td>
<td valign=top align="right">
<table class="skills_legends">
<tr><th colspan="2">Legend</th></tr>
<tr><td>level "U"</td><td>Skill is trainable but not owned</td></tr>
<tr><td>"can fly a"</td><td>can use ship and the mods needed (t2 weaps)</td></tr>
<tr><td>guided m</td><td>guided missiles; eg. standard, heavy, cruise</td></tr>
<tr><td>unguided</td><td>unguided missiles; rockets, heavy ass., torp</td></tr>
<tr><td>poorly vs well</td><td>can use both of associated carrier's remote reps</td></tr>
<tr><td>snipe</td><td>long range t2 weapons and associated modules</td></tr>
<tr><td>RR</td><td>remote rep; short range t2 weapons and armour tank</td></tr>
</table></td></tr><tr><td colspan=2>
EOD;
			if (!ob_start())
				die ("fatal error - OB opening failed.");

			$this->Output="<table class=\"skills_table\"><tr VALIGN=\"top\">";

			if ($small) {
				 echo "<td VALIGN=\"top\" width=\"250\" rowspan=\"2\">\n";
				 include("skills.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("racial.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("caps.tab.php");
				 echo "<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("weaps.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("fleet.tab.php");
				 echo "</td></tr>\n<tr>";
				 echo "<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("skillst2.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("ldrskil.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("t2mods.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("misc.tab.php");
			} else {
				 echo "<td VALIGN=\"top\" width=\"250\">\n";
				 include("skills.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("racial.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("skillst2.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("ldrskil.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("caps.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("weaps.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("fleet.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("t2mods.tab.php");
				 echo "</td>\n<td VALIGN=\"top\">\n"; /////////////////////////////////////////////////////
				 include("misc.tab.php");
			}
			$this->Output .= ob_get_contents();
			ob_end_clean();

			$this->Output .= "</td>";
			if ($small)
				 $this->Output .= "<td></td>";

			$this->Output .= "</tr>\n</table></td></tr></table>";

            $this->Output .='<section class="hidden" >';
            $this->Output .='<article class="popup" style="width: 50%">';
            $this->Output .='<span class="close">Close Me</span>';
            $this->Output .='<p id="corpHistoryBox">Corp History</p>';
            $this->Output .="<div class='corpHistoryTable'><table>";
            $redIDS = GetRedIDS($HistoryApi->corpIDs,$Db);
            foreach($HistoryApi->corpHistory as $item){
                if(in_array($item['corporationID'],$redIDS))
                    $this->Output .="<tr class='red'>";
                else
                    $this->Output .="<tr>";
                $this->Output .= "<td>".$item['corporationName']."</td>     <td>".date('Y-m-d H:i:s',(string)$item['startDate'])."</td><td>-</td>";
                if((string)$item['endDate']>0)
                    $this->Output .= "<td>".date('Y-m-d H:i:s',(string)$item['endDate'])."</td>";
                else
                    $this->Output .= "<td>present</td>";
                $this->Output .="</tr>";
            }
            $this->Output .="</table></div> ";
            $this->Output .='<br>';
            $this->Output .='</article>';
            $this->Output .='</section>';

			$this->Output .= "<span style=\"font-size:80%;\">";
			$this->Output .= "best viewed in " . ($small ? "1024x768" : "1440x900") . "<br>";
			if (!$small)
				$this->Output .= "<a href=\"$full_url&small\">compact view</a><br>";
			$this->Output .= "</span>";
		}
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}

// single-line test
function test_module_fit($mod, $name = "") {
     global $SkillsApi;

     if (is_array($mod)) {
         $ret = $SkillsApi->canCharUseTypeNames($mod);
         echo ($ret ? "can":"cannot") ." use " . $name ."<br>\n";
         return $ret;
     } else {
         if (is_numeric($mod)) {
             $ret = $SkillsApi->canCharUseTypeId($mod);
         } else {
             $ret = $SkillsApi->canCharUseTypeName($mod);
         }
         echo ($ret ? "can":"cannot") . " use " . ($name!=""?$name:$mod) ."<br>\n";
         return $ret;
     }
}

// table break
function tbBr() {
 echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
}

// single-line test, only show up if it is usable
function test_module_fit_true($mod, $name = "",$add = "") {
 global $SkillsApi;

 if (is_array($mod)) {
  if ($name == "")
   $name = "a " . $mod[0];

  $ret = $SkillsApi->canCharUseTypeNames($mod);
   if ($ret)
   echo "can use " . $name. " " .$add ."<br>\n";
  return $ret;
 } else {

     if (is_numeric($mod)) {
         $ret = $SkillsApi->canCharUseTypeId($mod);
     } else {
         $ret = $SkillsApi->canCharUseTypeName($mod);
     }
  if ($ret)
   echo "can use " . ($name!=""?$name:$mod) . " " .$add ."<br>\n";
  return $ret;
 }
}
// single-line test, only show up if it is usable, different wording
function test7($mod, $name = "",$add = "") {
 global $SkillsApi;

 if (is_array($mod)) {
  if ($name == "")
   $name = "a " . $mod[0];

   $ret = $SkillsApi->canCharUseTypeNames($mod);
   if ($ret)
		echo "<a href=\"javascript: return false;\" class=\"canfly\">can fly " . $name. " " .$add ."<span>".implode($mod,', ') ."</span></a><br>\n";

   return $ret;
 } else {
   $ret = $SkillsApi->canCharUseTypeName($mod);
   if ($ret)
    echo "can fly " . ($name!=""?$name:$mod) . " " .$add ."<br>\n";
   return $ret;
 }
}

// table row for skill: display name if usable,  and level if owned or U if usable
function test3($skill, $name = "") {
    global $SkillsApi;

    if ($name == "")
        $name = $skill;
    //debug_print_backtrace();
    $canuse = $SkillsApi->canCharUseTypeName($skill)||($SkillsApi->getSkillLevelByName($skill)!==-1);
    $level = $SkillsApi->getSkillLevelByName($skill);
    $level = (($level != -1)?$level:"U");
     if ($canuse)
        echo "<tr><td>" . $name . "&nbsp;</td><td style=\"color: #000000; background-color:".lvlToColour($level).";\">&nbsp;" . $level . "&nbsp;</td></tr>\n";
     return $level;
}

// table cell for skill: display U if usable, level otherwise, or if not usable blank
function test4($skill) {
 global $SkillsApi;

 $canuse = $SkillsApi->canCharUseTypeName($skill)||($SkillsApi->getSkillLevelByName($skill)!==-1);
 $level = $SkillsApi->getSkillLevelByName($skill); $level = (($level != -1)?$level:"U");

 if ($canuse) {
  echo "<td style=\"color: #000000; background-color:".lvlToColour($level).";\">&nbsp;" . $level . "&nbsp;</td>\n";
  } else echo "<td></td>";
 return $level;
}

function skillLvl($skill) {
 global $SkillsApi;
 $level =  $SkillsApi->getSkillLevelByName($skill);
  if ($level == "-1")
  return "&nbsp;&nbsp;&nbsp;";
 return "<a href=\"javascript: return false;\" style=\"font-size:90%;color: #000000; background-color:".lvlToColour($level).";\" class=\"canfly\" >&nbsp;" . $level ."&nbsp;<span style=\"display: table;\">$skill</span></a>";
}

// single line skill: displays level/usable if canuse as a line
function test_has_skill($skill, $name = "") {
 global $SkillsApi, $xmlskills;

 if ($name == "")
 $name = $skill;

 $canuse = $SkillsApi->canCharUseTypeName($skill);
 $level = $SkillsApi->getSkillLevelByName($skill);

 if ($canuse)
  echo $name .": " . (($level != -1)?$level:"U") . "</br>\n";

 return $level;
}

// returns true if skill is lvl5
function isLvl5($skill) {
 global $SkillsApi, $xmlskills;
 $level = $SkillsApi->getSkillLevelByName($skill);

 return $level == 5;
}

$registered_pages[] = new auditorSkillsPage();
 ?>
