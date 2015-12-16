<?php 
// login html bits
require_once("login.php");
function show_registration($Db) {
	// show a login form.
	$username="";
	$email="";
	if (isset($_GET['usid']))
		$userid = trim($_GET['usid']);

	if (isset($_GET['apik']))
		$apikey = trim($_GET['apik']);
		
	if (isset($_GET['key']))
		$short_api_key = trim($_GET['key']);
		
	if (!isset($userid) && isset($_COOKIE["api"]) && ($_COOKIE["api"] != "")) {
		$login = explode(',',$_COOKIE["api"]);
		if ($login[0] == "api") {
			$userid = trim($login[1]);
			$apikey = trim($login[2]);
		} else 
			$short_api_key = trim($login[1]);
	}
	if (isset($short_api_key)&&$short_api_key) {
		$ret = retrieve_api_key($Db, $short_api_key);
		if ($ret != null) {
			$userid = $ret["usid"];
			$apikey = $ret["apik"];
		}
	}
	
	$extra = "";		
	$apiinfo = "";
	
	if (isset($userid) && isset($apikey)) 
		$apiinfo = get_key_info($Db, $userid, $apikey);
	
	if ($apiinfo != "") 
		$extra = <<<EOD
<input type="checkbox" name="import" value="1" checked> Import the {$apiinfo}
<input type="hidden" name="usid" value="{$userid}">
<input type="hidden" name="apik" value="{$apikey}">
EOD;
	

	if(isset($_POST['username']))
		$username=$_POST['username'];
	if(isset($_POST['email']))
		$email=$_POST['email'];
	return <<<EOD
<h3>Register</h3>
<form action="?register=1" method="post">
<table>
<tr><td>User</td><td><input type="Text" name='username' id='username' value='{$username}'></td></tr>
<tr><td>Pass</td><td><input type="password" name='password' id='password'></td></tr>
<tr><td>Verify</td><td><input type="password" name='password2' id='password2'></td></tr>
<tr><td>Email</td><td><input type="Text" name='email' id='email' value='{$email}'></td></tr> 
</table>
{$extra}
<br>
<input type="submit" value="Register">
</form>
<a class="smalllink" href="?login=true" onclick="if(!hide_div('register')&&!show_div('login'))return false;">[login]</a>
EOD;
}
function edit_key_form(){
$div= <<<EOD
<h3> Please Name Your Key</h3>
<form id="apiForm" action="" method="post">
<div style="display:inline-block;padding-right:8px;">Key Name:</div><input changed="false" type="textbox" name="keyName" id="keyName" size="16"><br>
<div style="display:inline-block;padding-right:40px;">Notes:</div><textarea changed="false" name='notes' Cols='28' id='notes' style='resize:none;' onKeyDown="limitText(this,90)"></textarea><br>
<input type="button" value="Save Api" id="saveKey"
EOD;
$div.= "></form>";
return $div;
}
function resetPasswordForm() {
		$output= <<<EOD
		<h3>Reset Password</h3>
		<form action="?resetPassword=1" method="post">
		<table>
		<tr><td>Email</td><td><input type="Text" name='email' id='email'></td></tr> 
		</table>
		<br>
		<input type="submit" value="Reset Password">
		</form>
		<a class="smalllink" href="?login=true" onclick="if(!hide_div('reset')&&!show_div('login'))return false;">[login]</a>
		<a class="smalllink" href="?register=1" onclick="if(!hide_div('reset')&&!show_div('register'))return false;">[register]</a>
EOD;
	return $output;
}

function get_login_form() {
	global $short_api_key, $apikey, $userid;
	$ret = <<<EOD
<h3> Please log in.</h3>
<form id="loginForm" action="?login=true" method="post">
<table>
<tr><td>Username:</td><td><input type="textbox" name="user" size="16"></td></tr>
<tr><td>Password:</td><td><input type="password" name="pass" size="16"></td></tr>
</table>
<input type="submit" value="Login"></form>
<a class="smalllink" href="?register=1
EOD;
	if (isset($short_api_key)) {
		$ret.="&key=$short_api_key";
	} else if (isset($apikey) && isset($userid)) {
		$ret.="&apik=$apikey&usid=$userid";
	}
$ret .= <<<EOD
" onclick="if(!hide_div('login')&&!show_div('register'))return false;">[register]</a>
<a class="smalllink" href="?resetPassword=1" onclick="if(!hide_div('login')&&!show_div('reset')) return false;">[reset password]</a>
EOD;
	return $ret;
}
function get_account_change($email=""){
		$output= <<<EOD
		<h3>Edit Account</h3>
			<form action="manage.php?editAccount" method="post">
			<table>
			<tr><td>New Password</td><td><input type="password" name='Password1' id='Password1'></td></tr> 
			<tr><td>Verify</td><td><input type="password" name='Password2' id='Password2'></td></tr>
			<tr><td>Email</td><td><input type="text" name='email' id='email' value="{$email}"></td></tr> 
			<tr><td>Old Password</td><td><input type="password" name='oldPass' id='oldPass'></td></tr> 
			</table>
			<br>
			<input type="submit" value="Edit Account">
			</form>
EOD;
	return $output;

}

// Gets the links and forms for the api bar
function get_api_bar(){
	$infobar="&nbsp";
	// if there is an api key
	if(defined("API_KEY"))
		// and its not a short url with a character assigned
		if(!(isset($_GET['key'])&&!isset($_GET['chid'])))
			// and user is logged in
			if(loggedIn()){
				// then check the key
				$id=check_saved_api(USER_ID,API_KEY,$_SESSION['uid']);
					if($id) 
						$infobar .= "<a keyID='{$id}' class=\"removeKey\" href=\"manage.php?removeKey&id=$id\" id='keyAction'>remove</a>&nbsp";
					else{
						$infobar .= "<a href=\"manage.php?saveKey&apik=".USER_ID."&vcode=".API_KEY."\" id='keyAction'>save</a>&nbsp";
					}
			}
	// add links to either the auditor or manage page
	if(!defined("AUDIT_PHP"))
		$infobar .= "<a href=\"index.php\">auditor</a>&nbsp";
	if(!defined("MANAGE_PHP")&&loggedIn())
		$infobar .= "<a href=\"manage.php\">manage account</a>&nbsp";
	return $infobar;
}
function get_form_divs(){
	global $Db;
	$infobar="";
	if(!loggedIn()){
		$infobar .=makeDiv("register",show_registration($Db));
		$infobar .= makediv("login",get_login_form());
		$infobar .= makediv("reset",resetPasswordForm());
	}else{
		if(!(isset($_GET['key'])&&!isset($_GET['chid']))&&defined("API_KEY"))
			$infobar .=makediv("api",edit_key_form());
	}

	return $infobar;
}

// gets the links and forms for the login bar
function get_loginbar() {
	$infobar="";
	if(loggedIn())
		$infobar.=$_SESSION['username']."&nbsp;|&nbsp;";
	if (loggedIn())
		$infobar.="<a href=\"".SELF_URL."logout\">logout</a>";
	else {
		// shows registration link
		$infobar = "<a href=\"?register=1\" onclick=\"if(!show_div('register')) return false;\">register</a>";
		$infobar .= "&nbsp;<a onclick=\"if(!show_div('login')) return false;\" href=\"?login=1\">login</a>";
	}
	return $infobar.get_api_bar();
	
}
// function to make a floating div
// name is how it will be called 
// contents is what is inside the floating div
function makeDiv($name,$contents) {
return <<<EOD
<div onclick="hide_div('{$name}')" id="{$name}Div" class="fade_div">&nbsp;</div>
<div id="{$name}" class="floating_login_div">
<div class="exitbutton"><a href="#" onclick="hide_div('{$name}')">[X]</a></div>
{$contents}
</div><script>watch_for_scroll();</script>
EOD;
}
 ?>