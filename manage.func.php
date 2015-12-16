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
					<tr keyID={$id} class="{$class}">
					<td style="color: #FFA500"><a href="index.php?key={$key}" class="keyName">{$name}</a></td>
					<td>{$type}</td>
					<td>{$Characters}</td>
					<td class="keyNotes">{$trunNotes}</td>
					<td><a keyID={$id} class="editKey" href="manage.php?editNotes&id={$id}">Edit Key</a></td>
					<td><a keyID={$id} class="removeKey" href="manage.php?removeKey&id={$id}">Remove Key</a></td>
					</tr>
EOD;
		}
		$table.='<div class="fadeDiv">&nbsp;</div><div id="keyInfoBox" class="floating_login_div" style="display: none;">';
			$table.='<div class="exitbutton"><a href="#">[X]</a></div>';
		$table.=edit_key_form();
		$table.='</div>';
	}else
		$table.="<tr><td colspan=4> No Keys Found </td></tr>";
	$table.="</table>";



	
	$table.="
	</body></html>
	";
	return $table;
}
function retrieve_api_keys(){
    global $Db;
	$uid=$_SESSION['uid'];
    $result = $Db->selectWhere("keyInformation",array('userID'=>$uid));
	if ($result != false&&$result->rows>0) {
			return $result->results;
	}
	return false;


}
// checks if a short key is already assigned to an account
function check_saved_key($key,$uid) {
     global $Db;
     $result = $Db->selectWhere("keyInformation",array('apiKey'=>$key,'userID'=>$uid),null,1);
     if ($result != false&&$result->rows>0) {
	   		return $result->results[0]['id'];
	 }

	 return false;
}
// removed api key's from the table
function remove_api_key($Db,$id){
    global $Db;
	$uid=$_SESSION['uid'];
    $result = $Db->selectWhere("keyInformation",array('id'=>$id),['apiKey'],1);
    if ($result != false&&$result->rows>0) {
        if(!check_saved_key($result->results[0]['apiKey'],$uid)){
            return ["result"=>"failure","id"=>$id,"response"=>"Api key already removed"];
        }
    }else{
        return ["result"=>"failure","id"=>$id,"response"=>"Api key already removed"];
    }
    $result=$Db->delete("keyInformation",['id'=>$id,'userID'=>$uid]);
	if (!$result) {
		if(DEBUG) {
			exit;
		}
		Return false;
	}else{
		return true;
	}
}
// add api key to account
function add_api_key($Db,$uid,$keyID,$vCode,$name,$notes=""){
	$apikey=make_short_key($Db,$keyID,$vCode,$uid);
	$ID=check_saved_key($apikey,$uid);
	if($ID){
        return ["result"=>"failure","id"=>$ID,"response"=>"Api Code Already added"];
	}
	if(!$name) $name="No Name Set";
	$keyInfo = cache_api_retrieve($Db,"/account/APIKeyInfo.xml.aspx", array("keyID"=>$keyID, "vCode" => $vCode),5*60)->value;
	echo $keyInfo->error;
	if ($keyInfo->error){
		if ($keyInfo->error==222) {
			return ["result"=>"failure","response"=>"Api Key expired"];
		}else if ($keyInfo->error==203) {
			return ["result"=>"failure","response"=>"Api vCode or Id Incorrect"];
		}else {
			return ["result"=>"failure","response"=>"Unable to load API. Verify the key is correct and not expired."];
		}
		return false;
	}
	$type=(string)$keyInfo->result->key["type"];
	foreach ($keyInfo->result->key->rowset->row as $char) {
		if(isset($char["characterName"])){
			if(isset($characters)){
				$characters.="<br/>".$char["characterName"].",".$char["characterId"];
			}else{
				$characters=$char["characterName"].",".$char["characterId"];
			}
		}
	}
    $result=$Db->insert("keyInformation",['apiKey'=>$apikey,'userID'=>$uid,'keyName'=>$name,'characters'=>$characters,'type'=>$type,'notes'=>$notes]);
	if (!$result) {
		return false;
	}else{
        return ["id"=>$Db->lastid,"response"=>"Api Key successfully added"];
	}
}
function edit_api_key($Db,$keyID,$name="",$notes=""){
	$uid=$_SESSION['uid'];
	$updateArray=array();
	if($name!="false"){
		$updateArray['keyName']=$name;
	}
	if($notes!="false"){
		$updateArray['notes']=$notes;
	}
	$result=$Db->update("keyInformation",['id'=>$keyID,'userID'=>$uid],$updateArray);
	if (!$result) {
        Return false;
	}else{
		return true;
	}
}




 ?>
