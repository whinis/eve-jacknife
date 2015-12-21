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
include_once("auditorPage.base.php");
// corp about page
class auditorAboutPage extends auditorPage {

	public function GetName() { return "about"; }
	public function GetAccMode() { return ACC_CORP_ONLY; }
	public function GetAccMask($corp) { return 0; }
	public function GetOutput($Db) {
/*<img src="http://image.eveonline.com/Character/<?php echo $chid;  ?>_256.jpg" height=118 width=118>*/
		$full_url = FULL_URL; // TODO
		$time_start = microtime_float();
		
		$corpinf = cache_api_retrieve($Db,"/corp/CorporationSheet.xml.aspx", array("corporationID"=>CORP_ID),3*24*60*60)->value;

		$this->Title = "About ".$corpinf->result->corporationName;

		$time_end = microtime_float();
		$time_api = $time_end - $time_start;

		$this->Output .= <<<EOD
<table><tr><td>
<img src="http://image.eveonline.com/Corporation/{$corpinf->result->corporationID}_128.png" height=108 width=108>
</td><td valign=top>
<table>
<tr><td>CEO </td><td>{$corpinf->result->ceoName}</tr>
<tr><td>Members&nbsp;&nbsp;</td><td>{$corpinf->result->memberCount}</tr>
<tr><td>Tax </td><td>{$corpinf->result->taxRate}%</tr>
<tr><td>URL </td><td><a href="{$corpinf->result->url}" target="_blank">{$corpinf->result->url}</a></td></tr>
EOD;
		if ((int)$corpinf->result->allianceID != 0) 
			$this->Output .=  "<tr><td>Alliance </td><td>".$corpinf->result->allianceName."</tr>";

		$this->Output .= "
		</table>
		</td></table>
		<br>
		<div>";
		$this->Output .= parse_ccptml($corpinf->result->description);
		$this->Output .= "</div><br>";
		
		$this->Times = getPageTimes($Db,$time_api,microtime_float() - $time_start);
		return true;
	}
}
$registered_pages[] = new auditorAboutPage();