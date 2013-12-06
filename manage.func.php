<?php 

function save_api_view(){
	$apik="";
	$vcode="";
	if(isset($_GET['apik']))
		$apik=$_GET['apik'];
	if(isset($_GET['vcode']))
		$vcode=$_GET['vcode'];
	$addKey=<<<EOD
	<h3> Please Name Your Key</h3>
	<form id="api" action="manage.php?saveKey" method="post">
	<table>
	<tr><td>Key Name:</td><td><input type="textbox" name="keyName" id="keyName" size="30"></td></tr>
	<tr><td>Api Key:</td><td><input type="textbox" name="apik" id="keyName" size="16" value="$apik"></td></tr>
	<tr><td>vCode:</td><td><input type="textbox" name="vcode" id="keyName" size="80" value="$vcode"></td></tr>
	<tr><td>Notes:</td><td><textarea name='notes' Cols='28' id='notes' style='resize:none;' onKeyDown="limitText(this,3000)"></textarea> </td></tr>
	</table>
	<input type="hidden" value='save' name='save'>
	<input type="submit" value="Save Api"> 
	</form>
EOD;


return $addKey;
}

function management_view($db){
	// Table for management
	$table=<<<EOD
	<table id='keysTable' class="fancy" style="font-size:83%;" border=1>
		<tr>
			<th>
				<a href="">Name</a>
			</th>
			<th>
				<a href="">Type</a>
			</th>
			<th>
				<a href="">Characters</a>
			</th>
			<th>
				<a href="">Notes</a>
			</th>
		</tr>
EOD;
	$apiResults=retrieve_api_keys($db);
	if($apiResults){
		$alt_b = false;
		foreach($apiResults as $row){
			$alt_b = !$alt_b;
			$rand=rand();
			$name=$row['keyName'];
			$type=$row['type'];
			$Characters=$row['characters'];
			$key=$row['apiKey'];
			$notes=$row['notes'];
			if(!$notes)
				$trunNotes="no notes";
			elseif(strlen($notes)>140)
				$trunNotes=substr($notes,0,130)." . . . ";
			else
				$trunNotes=$notes;
			$id=$row['id'];
			$class=($alt_b?"main":"alt");
			$table.=<<<EOD
					<tr id="row{$id}" class="{$class}">
					<td style="color: #FFA500"><a href="index.php?key={$key}" id="name{$id}">{$name}</a><input type="text" style="display:none;" id="nameEdit{$id}" value="{$name}"/>
					<input id="nameButton{$id}" style="display:none;" type="button" value="Edit" onclick="editName({$id}); hide('nameEdit{$id}'); hide('nameButton{$id}');show('name{$id}') return false;"></th>
					<td>{$type}</td>
					<td>{$Characters}</td>
					<td id="note{$id}">{$trunNotes}</td>
					<td><a onclick="show_notes({$id}); return false;" href="manage.php?editNotes&id={$id}">Edit notes</a></td>
					<td><a onclick="removeKey({$id}); return false;" href="manage.php?removeKey&id={$id}">Remove Key</a></td>
					<td><a onclick="show('nameEdit{$id}'); hide('name{$id}'); show('nameButton{$id}'); return false;" href="manage.php?editName&id={$id}">Edit Name</a></td>
					</tr>
EOD;
		}
		$table.=<<<EOD
				<div onclick="hide_notes(); return false;" id="notesDiv" class="fade_div">&nbsp;</div>
				<div id="notes" class="floating_login_div">
				<div class="exitbutton"><a href="#" onclick="hide_notes(); return false;">[X]</a></div>
				Notes<br/>
				<textarea name="noteText" Cols="50" Rows="15" id="noteText" style="resize:none;background-color:#222222; color:#EEEEEE;" onKeyDown="limitText(this,3000)"></textarea>
				<br>
				<input type="button" value="Edit" onclick="saveNotes($id); hide_notes();">
				</div>
EOD;
$table.=<<<EOD
				<div onclick="hide_name(); return false;" id="nameDiv" class="fade_div">&nbsp;</div>
				<div id="edi" class="floating_login_div">
				<div class="exitbutton"><a href="#" onclick="hide_notes(); return false;">[X]</a></div>
				Notes<br/>
				<textarea name="noteText" Cols="50" Rows="15" id="noteText" style="resize:none;background-color:#222222; color:#EEEEEE;" onKeyDown="limitText(this,3000)"></textarea>
				<br>
				<input type="button" value="Edit" onclick="saveNotes($id); hide_notes();">
				</div>
EOD;
	}else
		$table.="<tr><td colspan=4> No Keys Found </td></tr>";
	$table.="</table>";



	
	$table.="
	</body></html>
	";
	return $table;
}
function retrieve_api_keys($db){
	$link=$db->link;
	$uid=$_SESSION['uid'];
	$sql="SELECT * FROM ".DB_PREFIX."keyInformation WHERE userID ='".mysql_real_escape_string($uid)."'";
	$result = mysql_query($sql,$link);
	if ($result != false) {
		if (mysql_num_rows($result) > 0) {
			while($rows[]=mysql_fetch_assoc($result));
			array_pop($rows);
			mysql_free_result($result);
			return $rows;
		}
	}
	return false;


}
// checks if a short key is already assigned to an account
function check_saved_key($link,$key,$uid) {
 if (!$link)
  return null;
 $sql="SELECT id FROM ".DB_PREFIX."keyInformation WHERE apiKey = '".mysql_real_escape_string($key)."' AND userID ='".mysql_real_escape_string($uid)."' LIMIT 1";
 $result = mysql_query($sql,$link);
 if ($result != false) {
  if (mysql_num_rows($result) > 0) {
   $row = mysql_fetch_assoc($result);
   mysql_free_result($result);
   $id=$row['id'];
   return $id;
  }
  mysql_free_result($result);
 }
 
 return false;
}
// removed api key's from the table
function remove_api_key($Db,$id){
	$uid=$_SESSION['uid'];
	$sql="SELECT apiKey FROM ".DB_PREFIX."keyInformation WHERE id ='".mysql_real_escape_string($id)."' LIMIT 1";
	$result = mysql_query($sql, $Db->link);
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$apikey=$row['apiKey'];
	if(!check_saved_key($Db->link, $apikey,$uid)){
		return("$id<321>Api key already removed");
	}
	$sql = "DELETE FROM ".DB_PREFIX."keyInformation WHERE id='".mysql_real_escape_string($id)."' AND userID='".mysql_real_escape_string($uid)."'";
	$result = mysql_query($sql, $Db->link);
	if (!$result) {
		echo "QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n";
		debug_print_backtrace();
		exit;
	}
	if (mysql_affected_rows ($Db->link)) {
		return ("$id<321>Api Key successfully removed");
	}else{
		Return("0<321>Api Key Not removed");
	}
}
// add api key to account
function add_api_key($link,$uid,$keyID,$vCode,$name,$notes=""){
	$apikey=make_short_key($link,$keyID,$vCode,$uid);
	$ID=check_saved_key($link,$apikey,$uid);
	if($ID){
		return $ID."<321>Api Code Already added";
	}
	if(!$name) $name="No Name Set";
	$name=mysql_real_escape_string($name,$link);
	$keyInfo = cache_api_retrieve($link, "/account/APIKeyInfo.xml.aspx", array("keyID"=>$keyID, "vCode" => $vCode),5*60)->value;
	echo $keyInfo->error;
	if ($keyInfo->error){
		if ($keyInfo->error==222) {
			return "0<321>Api Key expired";
		}else if ($keyInfo->error==203) {
			return "0<321>Api vCode or Id Incorrect";
		}else
			return "0<321>Unable to load API. Verify the key is correct and not expired.";
		return false;
	}
	$type=(string)$keyInfo->result->key["type"];
	$notes=mysql_real_escape_string($notes,$link);
	foreach ($keyInfo->result->key->rowset->row as $char) {
		if(isset($char["characterName"])){
			if(isset($characters)){
				$characters.="<br/>".$char["characterName"].",".$char["characterId"];
			}else{
				$characters=$char["characterName"].",".$char["characterId"];
			}
		}
	}
	$keyID=mysql_real_escape_string($apikey,$link);
	$sql = "INSERT INTO ".DB_PREFIX."keyInformation (apiKey,userID,keyName,characters,type,notes) VALUES ('$keyID','$uid','$name','$characters','$type','$notes')";
	$result = mysql_query($sql, $link);
	if (!$result) {
		echo "QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n";
		debug_print_backtrace();
		exit;
	}
	if (mysql_affected_rows ($link)) {
		return mysql_insert_id($link)."<321>Api Key successfully added";
	}
}
function edit_api_key($link,$keyID,$name="",$notes=""){
	$uid=$_SESSION['uid'];
	if($name){
		$name=mysql_real_escape_string($name,$link);
		$sql = "UPDATE ".DB_PREFIX."keyInformation SET keyName='$name'WHERE id='".mysql_real_escape_string($keyID)."' AND userID='".mysql_real_escape_string($uid)."'";
	}else if($notes){
		$notes=mysql_real_escape_string($notes,$link);
		$sql = "UPDATE ".DB_PREFIX."keyInformation SET notes='$notes' WHERE id='".mysql_real_escape_string($keyID)."' AND userID='".mysql_real_escape_string($uid)."'";
	}
	$result = mysql_query($sql, $link);
	if (!$result) {
		echo "QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n";
		debug_print_backtrace();
		exit;
	}
	if (mysql_affected_rows ($link)){;
		return true;
	}
}




 ?>
