<?php 
// ****************************************************************************
// 
// ZZJ Audit Tool v1.5
// Copyright (C) 2010  ZigZagJoe (zigzagjoe@gmail.com) and
// Copyright (C) 2012  Equto   (whinis@whinis.com)
// 
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License,or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,


// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc.,59 Temple Place,Suite 330,Boston,MA  02111-1307  USA
// 
// ****************************************************************************
// acount management
define("MANAGE_PHP", true);
define("SELF_URL", "http://" . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?");

require_once("eve.php");
require_once("audit.funcs.php");
require_once("audit.views.php");	
require_once("login.php");
require_once("manage.func.php");
if(!loggedIn()){
	echo "<span class=\"infobar\">&lt;&nbsp;".get_loginbar()."&gt;</span><br>";
	echo get_form_divs();
	echo "You are not logged in";
	insert_header("Jackknife - Manage Account");	
}else
#save a key
if(isset($_GET['saveKey'])){
	if(isset($_POST['save'])){
		$id=add_api_key($Db,$_SESSION['uid'],$_POST['apik'],$_POST['vcode'],$_POST['keyName'],$_POST['notes']);
		echo $id['id'];
		echo"<br><a href='index.php'> Auditor</a>   <a href='manage.php'> Manage Account </a>";
		insert_header("Jackknife - Save Key");
	
	}else{
		echo "<span class=\"infobar\">&lt;&nbsp;".get_loginbar(true)."&gt;</span><br>";
		echo get_form_divs();
		echo save_api_view();
		insert_header("Jackknife - Save Key");	
	}

}else
#remove a key
if(isset($_GET['removeKey'])){
	insert_header("Jackknife - Remove Key");
	if(isset($_GET['id'])){
		$id=remove_api_key($Db,$_GET['id']);
		echo $id['id'];
	}else{
		echo "Key to remove not supplied";
	}
	echo"<br><a href='index.php'> Auditor</a>   <a href='manage.php'> Manage Account </a>";
}else {
	# editing a key name
	if (isset($_GET['editName'])) {
		if (isset($_POST['name'])) {
			$id = edit_api_key($Db, $_POST['id'], $_POST['name']);
			redirect("/manage.php");
		} else {
			$result = $Db->selectWhere("keyInformation",['id'=>$_GET['id'],'userID'=>$_SESSION['uid']],['keyName']);
			if ($result != false) {
				if ($result->rows > 0) {
					$row = $result->results[0];
					echo <<<EOD
			<form id='name' action="manage.php?editName" method="POST">
			<input type="hidden" name="id" value="{$_GET['id']}">
			<input type='text' name='name' style=\"display:none;\"id='name' value='{$row['keyName']}'/>
			<input type="submit" value="Edit name">
			</form>
EOD;
				} else {
					echo "This key does not exist, please try again";
				}
			}

		}
		echo "<br><a href='index.php'> Auditor</a>   <a href='manage.php'> Manage Account </a>";
		insert_header("Jackknife - Edit Notes");
	} else
#Editing notes of a key
		if (isset($_GET['editNotes'])) {
			# if notes have been changed
			if (isset($_POST['Notes'])) {
				$id = edit_api_key($Db, $_POST['id'], $name = "", $_POST['Notes']);
				redirect("/manage.php");
			} else {
				# first page
				$result = $Db->selectWhere("keyInformation",['id'=>$_GET['id'],'userID'=>$_SESSION['uid']],['notes']);
				if ($result != false) {
					if ($result->rows > 0) {
						$row = $result->results[0];
						echo <<<EOD
			<form id='notes' action="manage.php?editNotes" method="POST">
			<input type="hidden" name="id" value="{$_GET['id']}">
			<textarea name="Notes" Cols="50" Rows="15" id="noteText" style="resize:none;background-color:#222222; color:#EEEEEE;" onKeyDown="limitText(this,3000)">{$row['notes']}</textarea><br>
			<input type="submit" value="Save Notes"> 
			</form>
EOD;
					} else {
						echo "This key does not exist, please try again";
					}
				}
			}
			echo "<br><a href='index.php'> Auditor</a>   <a href='manage.php'> Manage Account </a>";
			insert_header("Jackknife - Edit Notes");
		} else
# Editing password and emails
			if (isset($_GET['editAccount'])) {

				if (isset($_POST['oldPass'])) {
					if (isset($_POST['email']) && ($_POST['email'] != $_SESSION['email']))

						$email = (($user->changeEmail($_POST['email'], $_POST['oldPass']) > 0) ? "Email changed" : "");

					if (isset($_POST['Password1']) && isset($_POST['Password2']) && ($_POST['Password2'] == $_POST['Password1']) && !empty($_POST['Password1']))

						$pass = (($user->changePassword($_POST['Password1'], $_POST['oldPass']) > 0) ? "Password changed" : "");

					if ($email && $pass)

						$pass .= " and ";

					if ($pass || $email)

						mail($_SESSION['email'], $pass . $email, wordwrap("Your " . $pass . $email . " has been changed for Eve Jackknife", 70));

					redirect("/manage.php");
				} else {
					# account page
					$result = $Db->selectWhere("accounts",['id'=>$_GET['id']]);
					if ($result != false) {
						if ($result->rows > 0) {
							$row = $result->results[0];
						}
					}
				}
				echo "<br><a href='index.php'> Auditor</a>   <a href='manage.php'> Manage Account </a>";
				insert_header("Jackknife - Edit Notes");
			} #standard account page
			else {
				echo "<span class=\"infobar\">&lt;&nbsp;" . get_loginbar() . "<a onclick=\"if(!show_div('account')) return false;\" href='manage.php?editAccount=1'> Account Settings</a> &gt;</span><br>";
				echo get_form_divs();
				echo makeDiv("account", get_account_change($_SESSION['email']));
				echo management_view($Db);
				insert_header("Jackknife - Manage Account");
				$_SESSION['redirect'] = "manage.php";
			}
}

 ?>
