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
if(isset($infoBarFunctions)){
	$infoBarFunctions[]="get_loginbar";
	$footerFunctions[]="get_form_divs";
}
class User {
	// set sensitive information private
	private $link;
	// run initial checks and setup variables
	function User($salt,$secureTimeout,$Db) {
		if(!session_id()){
			session_start ();
		}
		$this->accountTable="accounts";
		$this->salt=$salt;
		$this->secureTimeout=$secureTimeout;
		$this->Db=$Db;
		$this->Output="";
		$this->checkSession();
		
	}
	private function setOutput($Output){
        if(!isset($_SESSION['Output'])){
            $_SESSION['Output']="";
        }
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
			$result=$this->Db->selectWhere($this->accountTable,['id'=>$_SESSION['uid']]);
			if ($result->rows)
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
		$result=$this->Db->selectWhere($this->accountTable,['username'=>$username]);
		
		// check if error occured on mysql query
		if (!$result) {
			return false;
		}
		// checks 
		if ($result->rows) {
			$results=$result->results[0];
			if($password==null||$password==" ") {
				$this->logout();
				return -1;
			}elseif($results['password']==$this->hash_password($password,$results['salt'])){
				$this->setSession($results, $remember);
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


			$this->Db->update($this->accountTable,array('id'=>$values['id']),array('session'=>$session,'ip'=>$ip));
		}
	}
	// makes a cookie for the Remember  Function
	function updateCookie() {
		$cookie=md5($_SESSION['username'].$_SERVER['REMOTE_ADDR'].$this->generateSalt());
		$session = session_id();
		$ip = $_SERVER['REMOTE_ADDR'];
		

		$this->Db->update($this->accountTable,array('ip'=>$ip),array('cookie'=>$cookie));
		$cookie = serialize(array($_SESSION['uid'], $cookie) );
		set_cookie("login", $cookie, time() + 31104000, './');
	}
	// checks if a cookie was set is someone is not logged in
	function checkRemembered($cookie) {
		list($uid, $cookie) = @unserialize($cookie);
		if (!$uid or !$cookie) return;
		$result = $this->Db->selectWhere($this->accountTable,array('id'=>$uid,'cookie'=>$cookie));
		if (!$result) {
			return false;
		}
		if ($result->rows) {
			$this->_setSession($result->results, true);
		}else{
			$this->logout();
		}
	}
	// checks whether this login was from the same IP and uses the same session ID.
	function verifySession() {
		$username = $_SESSION['username'];
		$session = session_id();
		$ip = $_SERVER['REMOTE_ADDR'];
		$result = $this->Db->selectWhere($this->accountTable,array('username'=>$username,'session'=>$session,'ip'=>$ip));
		if (!$result) {
			return false;
		}
		if ($result->rows) {
			session_regenerate_id();
			$this->setSession($result->results, false, false);
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

		$result = $this->Db->selectWhere($this->accountTable,array('username'=>$username));
		// if exsist report error
		if ($result&&$result->rows>0) {
			$this->setOutput("Username already in use");
			return false;
		} else {

			$salt=$this->generateSalt(); // creates a random salt for this user
			$password =$this->hash_password($password,$salt);
			// inserts the information into the database
			$result = $this->Db->insert($this->accountTable,array('username'=>$username,'password'=>$password,'salt'=>$salt,'email'=>$email));
			if (!$result) {
				return false;
			}
			if(mysqli_affected_rows ($this->link)){
				$this->setOutput("Registered successful");
				$this->uid=mysqli_insert_id();
				return true;
			}
		}
	
	}
	function changePassword($password,$oldPass){
		$uid=$_SESSION['uid'];
		$result = $this->Db->selectWhere($this->accountTable,array('id'=>$uid));
		// check if error occured on mysql query
		if (!$result) {
			return false;
		}
		
		// checks 
		if ($result->rows) {
			$result=$result->results;
			if($oldPass==null||$oldPass==" ") {
				$this->setOutput("Password not set");
				return -1;
			}else
			if($result['password']==$this->hash_password($oldPass,$result['salt'])){
				$pass=$this->hash_password($password,$result['salt']);
				$result=$this->Db->update($this->accountTable,array('id'=>$uid),array('password'=>$pass));
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
		$result = $this->Db->selectWhere($this->accountTable,array('id'=>$uid));
		// check if error occured on mysql query
		if (!$result) {
			return false;
		}
		
		// checks 
		if ($result->rows) {
			$result=$result->results;
			if($password==null||$password==" ") {
				$this->setOutput("Password not set");
				return -1;
			}else
			if($result['password']==$this->hash_password($password,$result['salt'])){
				$result=$this->Db->update($this->accountTable,array('id'=>$uid),array('email'=>$email));
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
		$result = $this->Db->selectWhere($this->accountTable,array('email'=>$email));
		// check if error occured on mysql query
		if (!$result) {
			return false;
		}
		
		// checks 
		if ($result) {
			$username=$result[1];
			$pass=$this->generateSalt(10);

			$result=$this->Db->update($this->accountTable,array('id'=>$result[0]),array('password'=>$this->hash_password($pass,$result[3])));
			if (!$result) {
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
$user=new User($salt, $secureTimeout,$Db);
	
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
function handle_registration($Db) {
	global $user;
	if(isset($_POST['username'])&&isset($_POST['password'])&&($_POST['password']==$_POST['password2'])&&isset($_POST['email'])){
		if($user->registerUser($_POST['username'],$_POST['password'],$_POST['email'])){
			if(isset($_POST['import'])){
				require_once('manage.php');
				add_api_key($Db,$user->uid,$_POST['usid'],$_POST['apik'],"",$notes="");
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

	$result = $Db->selectWhere(API_TABLE,array('apik'=>$vCode,'chara'=>$uid,'usid'=>$apiKey),null,1);
	if ($result != false&&$result->rows>0) {
		$id=$result->results[0]['keyv'];
	}else
		return false;

	if($id){
		$result = $Db->selectWhere("keyInformation",array('apiKey'=>$id),['id'],1);
		if ($result != false&&$result->rows>0) {
			return $result->results[0]['id'];
		}else{
			return false;
		}
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

if (isset($_GET["register"])) {
	handle_registration($Db);
	exit;
}elseif (isset($_GET["resetPassword"])) {
	if (isset($_POST['email'])) {
		$user->resetPassword($_POST['email']);
		redirect("/" . $_SESSION['redirect']);
	} else {
		echo resetPasswordForm();
		insert_header("Jackknife Password Reset");
		echo "<a class=\"smalllink\" href=\"index.php\">[api input]</a>&nbsp;</body></html>";
	}
}

if(isset($_GET['login'])) {
	if (isset($_POST["user"]) && isset($_POST["pass"]) && isset($_GET['login'])) {
		if ($user->checkLogin($_POST["user"], $_POST["pass"])) {
			redirect("/" . $_SESSION['redirect']); // logged in OK, redirect to last page
			exit;
		}else{
			fatal_error("Invalid Username or Password");
			exit;
		}
	} else {
		insert_header("Jackknife Login");
		echo(get_login_form() . "&nbsp;<a class=\"smalllink\" href=\"index.php\">[api input]</a>&nbsp;</body></html>");
		exit;
	}
}

 ?>