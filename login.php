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
// main login code
require_once("eve.php");
class User {
	// set sensitive information private
	private $link;
	// run initial checks and setup variables
	function User($DB,$salt,$secureTimeout) {
		if(!session_id()){
			session_start ();
		}
		$this->accountTable=DB_PREFIX."accounts";
		$this->salt=$salt;
		$this->secureTimeout=$secureTimeout;
		$this->checkSession();
		$this->link=$DB;
		$this->Output="";
		
	}
	private function setOutput($Output){
		if(isset($this->Output))
			$_SESSION['Output'].="  ".$Output;
		else
			$_SESSION['Output']=$Output;
	}
	// Checks if user is logged in and checks to which security level
	function checkSession(){
		if (isset($_SESSION['login'])&&isset($_SESSION['secure'])&&$_SESSION['secure']==true) {
			if(time()-$_SESSION['last_activity']>$this->secureTimeout){
				$_SESSION['secure']=false;
				$_SESSION['last_activity']=time();
			}else{
				$_SESSION['last_activity']=time();
			}
			$this->secureSession();
		} elseif(isset($_SESSION['login'])&&$_SESSION['login']==true){
			$sql = "SELECT id FROM ".$this->accountTable." WHERE id = '".$_SESSION['uid']."' ";
			$result = mysql_query($sql);
			if (mysql_num_rows($result))
				return true;
			else
				$this->logout();
		}	elseif	( isset($_COOKIE['login']) ) {
			$this->checkRemembered($_COOKIE['login']);
		}else{
			$_SESSION['login']=false;
		}
	}
	// hashes password for security
	function hash_password($password,$salt)
	{
		$password_length = strlen($password);
		$split_at = $password_length / 2;
		$password_array = str_split($password, $split_at);
		$hash = hash("sha256","4856c".md5($password_array[0] . $salt . $password_array[1]) . $this->salt);
		return $hash;
	}
	// checks if login information correct
	function checkLogin($username, $password, $remember=false,$secure=false) {
		// get users salt
		$username = mysql_real_escape_string($username);
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"username = '$username' ";
		$result = mysql_query($sql, $this->link);
		
		// check if error occured on mysql query
		if (!$result) {
			$this->Fatal=true;
			$this->setOutput("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
			return false;
		}
		
		// checks 
		if (mysql_num_rows($result)) {
			$result=mysql_fetch_assoc($result);
			if($password==null||$password==" ") {
				$this->logout();
				return -1;
			}elseif($result['password']==$this->hash_password($password,$result['salt'])){
				$this->setSession($result, $remember);
				session_regenerate_id();
				$this->setOutput("Logged In");
				if($secure){
					$_SESSION['secure']=1;
					$_SESSION['last_activity']=time();
				}
				return true;
			}else{
				$this->setOutput("Invalid Username or Password");
				return -2;
			}
		}else{
			$this->setOutput("Invalid Username or Password");
			return -3;
		}
		
	}
	// Sets session variables and runs cookie function
	private function setSession(&$values, $remember, $init = true) {
		$_SESSION['uid'] = $values['id'];
		$_SESSION['username'] = htmlspecialchars($values['username']);
		$_SESSION['email'] = htmlspecialchars($values['email']);
		$_SESSION['login'] = true;
		if ($remember) {
			$this->updateCookie();
		}
		if ($init) {
			$session = session_id();
			$ip =$_SERVER['REMOTE_ADDR'];

			$sql = "UPDATE ".$this->accountTable." SET session = '$session', ip = '$ip' WHERE " .
			"id = '".$values['id']."'";
			$result = mysql_query($sql, $this->link);
		}
	}
	// makes a cookie for the Remember  Function
	function updateCookie() {
		$cookie=md5($_SESSION['username'].$_SERVER['REMOTE_ADDR'].$this->generateSalt());
		$session = session_id();
		$ip = $_SERVER['REMOTE_ADDR'];
		
		
		$sql = "UPDATE ".$this->accountTable." SET cookie = '$cookie' WHERE ip = '$ip'" .
		$result = mysql_query($sql, $this->link);
		$cookie = serialize(array($_SESSION['uid'], $cookie) );
		set_cookie("login", $cookie, time() + 31104000, './');
	}
	// checks if a cookie was set is someone is not logged in
	function checkRemembered($cookie) {
		list($uid, $cookie) = @unserialize($cookie);
		if (!$uid or !$cookie) return;
		$uid = $uid;
		$cookie = $cookie;
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"(id = '$uid') AND (cookie = '$cookie')";
		$result = mysql_query($sql, $this->link);
		if (!$result) {
			$this->Fatal=true;
			die("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
			return false;
		}
		if (mysql_num_rows($this->link)) {
			$this->_setSession($result, true);
		}else{
			$this->logout();
		}
	}
	// checks whether this login was from the same IP and uses the same session ID.
	function verifySession() {
		$username = mysql_real_escape_string($_SESSION['username']);
		$session = session_id();
		$ip = $_SERVER['REMOTE_ADDR'];
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"(username = '$username') AND " .
		"(session = '$session') AND (ip = '$ip')";
		$result = mysql_query($sql, $this->link);
		if (!$result) {
			$this->Fatal=true;
			die("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
			return false;
		}
		if (mysql_num_rows($this->link)) {
			session_regenerate_id();
			$this->setSession($result, false, false);
		} else {
			$this->logout();
		}
	}
	// generates a dynamic salt
	function generateSalt($max = 15) {
		$characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$i = 0;
		$salt = "";
		do {
			$salt .= $characterList{mt_rand(0,strlen($characterList)-1)};
			$i++;
		} while ($i < $max);
		return $salt;
	} 
	// user to register a user into the data base
	function registerUser($username,$password,$email){
		$username = mysql_real_escape_string($username,$this->link);	
		// checks if this username exsist
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"(username = '$username')";
		$result = mysql_query($sql, $this->link);
		// if exsist report error
		if (mysql_num_rows($result)) {
			$this->setOutput("Username already in use");
			return false;
		} else {
		
			$salt=$this->generateSalt(); // creates a random salt for this user
			$password =$this->hash_password($password,$salt);
			$email =mysql_real_escape_string($email,$this->link);
			// inserts the information into the database
			$sql = 	"INSERT INTO ".$this->accountTable.
					"(username,password,salt,email) VALUES('$username'
					,'$password','$salt','$email')";
			$result = mysql_query($sql, $this->link);
			if (!$result) {
				$this->Fatal=true;
				$this->setOutput("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
				return false;
			}
			if(mysql_affected_rows ($this->link)){
				$this->setOutput("Registered successful");
				$this->uid=mysql_insert_id();
				return true;
			}
		}
	
	}
	function changePassword($password,$oldPass){
		$uid=$_SESSION['uid'];
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"id = '$uid'";
		$result = mysql_query($sql, $this->link);
		// check if error occured on mysql query
		if (!$result) {
			$this->Fatal=true;
			$this->setOutput("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
			return false;
		}
		
		// checks 
		if (mysql_num_rows($result)) {
			$result=mysql_fetch_assoc($result);
			if($oldPass==null||$oldPass==" ") {
				$this->setOutput("Password not set");
				return -1;
			}else
			if($result['password']==$this->hash_password($oldPass,$result['salt'])){
				$pass=$this->hash_password($password,$result['salt']);
				$sql = "UPDATE ".$this->accountTable.
				" SET password = '$pass' WHERE id = '$uid'";
				$result = mysql_query($sql, $this->link);
				if($result){
					$this->setOutput("Password Changed");
					return true;
				}
				
			}else{
				$this->setOutput("Old Password Incorrect");
				return -2;
			}
		}else{
			$this->setOutput("Error getting information");
			return -3;
		}
	
	
	
	
	}
	function changeEmail($email,$password){
		$uid=$_SESSION['uid'];
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"id = '$uid'";
		$result = mysql_query($sql, $this->link);
		// check if error occured on mysql query
		if (!$result) {
			$this->Fatal=true;
			$this->setOutput("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
			return false;
		}
		
		// checks 
		if (mysql_num_rows($result)) {
			$result=mysql_fetch_assoc($result);
			if($password==null||$password==" ") {
				$this->setOutput("Password not set");
				return -1;
			}else
			if($result['password']==$this->hash_password($password,$result['salt'])){
				$sql = "UPDATE ".$this->accountTable.
				" SET email = '".mysql_real_escape_string($email,$this->link)."' WHERE id = '$uid'";
				$result = mysql_query($sql, $this->link);
				if($result){
					$this->setOutput("Email Changed");
					return true;
				}
			}else{
				$this->setOutput("Invalid Password");
				return -2;
			}
		}else{
			$this->setOutput("Error getting information");
			return -3;
		}
	
	
	
	}
	function resetPassword($email){
		// get users information
		$sql = "SELECT * FROM ".$this->accountTable." WHERE " .
		"email = '".mysql_real_escape_string($email)."' ";
		$result = mysql_query($sql, $this->link);
		
		// check if error occured on mysql query
		if (!$result) {
			$this->Fatal=true;
			$this->setOutput("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
			return false;
		}
		$result=mysql_fetch_row($result);
		
		// checks 
		if ($result) {
			$username=$result[1];
			$pass=$this->generateSalt(10);
			$sql="UPDATE ".$this->accountTable." SET password = '".$this->hash_password($pass,$result[3])."'
			WHERE id='".$result[0]."'";
			$result = mysql_query($sql, $this->link);
			if (!$result) {
				$this->Fatal=true;
				$this->setOutput("QUERY: '$sql'\n\n" . mysql_error()."\n\nBacktrace:\n".debug_print_backtrace());
				return false;
			}
			$subject=" Password Reset for JackKnife";
			$message=
"Someone has requested your password be reset for $username.
Your new password is $pass, if you did not request this
password reset consider changing your email address on the 
account.";
			if(mail($email, $subject, wordwrap($message,70)))
				$this->setOutput("Password reset sent to email account");
			else
				$this->setOutput("Error sending password reset");
		}else{
			$this->setOutput("There is no account associated with this email");
		
		}
	
	
	
	}
	// logs a user out
	function logout(){
		setcookie("login", "", time() -3600);
		$this->setOutput($_SESSION['username']." has successfully logged out");
		$output=$_SESSION['Output'];
		$page=$_SESSION['redirect'];
		$_SESSION=array();
		session_destroy();
		session_start();
		$_SESSION['redirect']=$page;
		$_SESSION['Output']=$output;
		return true;
	}
	
	# Code that generates a random key and then saves it
	function generate_ajax_key(){
		$ajax=hash("sha256",md5(session_id().$this->salt).$this->salt);
		$_SESSION['ajax']=$ajax;
		return $ajax;

	}
	#Code that checks that random key
	function check_ajax_key($key){
		if($_SESSION['ajax']==$key){
			session_regenerate_id();
			return true;
		}
		return false;
	}

}
//initatialize login class and check if logged in
if(!isset($Db))
	$Db = new eveDb($sql, $sql_u, $sql_p, $db);
	$user=new User($Db->link, $salt, $secureTimeout);
	
if(isset($_SESSION['login'])&&$_SESSION['login']){
	$_username=$_SESSION['username'];
	$uid=$_SESSION['uid'];
	$loggedIn=$_SESSION['login'];
}else{
	$_username="";
	$uid="";
	$loggedIn=false;
}	

function login_do_logout() {
	global $user;
	clear_api_cookie();
	$user->logout();
}

function login_load_creds($Db,$alreadyGotCreds) {
	global $short_api_key;
	global $user;
	if (!LOGGED_IN)
		return false;
	
	define("API_SAVED", !$alreadyGotCreds);
	if(isset($_SESSION['key']))
		$short_api_key=$_SESSION['key'];
		return true;
	// ALL LOGIN APIS SHOULD USE THE SHORT API TABLE! set $short_api_key! in this function.
	// TODO: 
	return false; // return false if user is not logged in or loading creds failed, so cookie will be tried
}
function handle_registration($Db) {
	global $user;
	if(isset($_POST['username'])&&isset($_POST['password'])&&($_POST['password']==$_POST['password2'])&&isset($_POST['email'])){
		if($user->registerUser($_POST['username'],$_POST['password'],$_POST['email'])){
			if(isset($_POST['import'])){
				require_once('manage.php');
				add_api_key($Db->link,$user->uid,$_POST['usid'],$_POST['apik'],"",$notes="");
			}
			redirect("/".$_SESSION['redirect']);
		}else{
			echo show_registration($Db). "<a class=\"smalllink\" href=\"index.php\">[api input]</a>&nbsp;</body></html>";
			insert_header("Register");
		}
	}
	else
		echo show_registration($Db). "<a class=\"smalllink\" href=\"index.php\">[api input]</a>&nbsp;</body></html>";
		insert_header("Register");
}


// Checks whether current api information is in the database
function check_saved_api($apiKey,$vCode,$uid) {
global $Db;
 $link=$Db->link;
 if (!$link)
  return null;
  
 $sql="SELECT keyv FROM ".DB_PREFIX.API_TABLE." WHERE apik = '".mysql_real_escape_string($vCode)."' AND chara ='".mysql_real_escape_string($uid)."' AND usid='".mysql_real_escape_string($apiKey)."' LIMIT 1";
 $result = mysql_query($sql,$link);
 if ($result != false) {
  if (mysql_num_rows($result) > 0) {
   $row = mysql_fetch_assoc($result);
   mysql_free_result($result);
   $id=$row['keyv'];
  }else{
	return false;
  }
 }else
	return false;
	
 if($id){
	$sql="SELECT id FROM ".DB_PREFIX."keyInformation WHERE apiKey ='".mysql_real_escape_string($id)."' LIMIT 1";
	$result = mysql_query($sql, $Db->link);
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	return $row['id'];
 }
 
 
 return false;
}
if (isset($_GET['logout'])){
	login_do_logout();
	$_username="";
	$uid="";
	$loggedIn=false;
	redirect("/index.php");
}
// moved login views to allow key checking
require_once("login.views.php");
if(isset($_SESSION['Output'])&&$_SESSION['Output']){
	echo "<script type=\"text/javascript\">setTimeout(\"document.getElementById(\\\"loginOutput\\\").style.display=\\\"none\\\"\",5000)</script><span id=\"loginOutput\" class=\"loginOutput\">".$_SESSION['Output']."<br></span>";
	$_SESSION['Output']=null;
}
if (!defined("AUDIT_PHP") && !defined("MANAGE_PHP")) { // standalone page
	require_once("audit.views.php");
	require_once("audit.funcs.php");
	if ($loggedIn)
		redirect("/".$_SESSION['redirect']);	

	elseif (isset($_GET["register"])) 
		handle_registration($Db);
		
	elseif (isset($_GET["resetPassword"]))
		if(isset($_POST['email'])){
			$user->resetPassword($_POST['email']);
			redirect("/".$_SESSION['redirect']);
		}else{
			echo resetPasswordForm();
			insert_header("Jackknife Password Reset");
			echo "<a class=\"smalllink\" href=\"index.php\">[api input]</a>&nbsp;</body></html>";
		}
		
	elseif (isset($_POST["user"]) && isset($_POST["pass"])) {
		if ($user->checkLogin($_POST["user"], $_POST["pass"])) {
			redirect("/".$_SESSION['redirect']); // logged in OK, redirect to last page
		}
	}else{
		insert_header("Jackknife Login");
		echo (get_login_form() . "&nbsp;<a class=\"smalllink\" href=\"index.php\">[api input]</a>&nbsp;</body></html>");
		exit;
	}
}
define("LOGGED_IN", $loggedIn);
define("LOGIN_NAME",$_username);

 ?>