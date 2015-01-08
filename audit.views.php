<?php 
// main views

function insert_header($title = "API Jackknife") {
 ?>
<html>
<head>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"> 
<title><?php echo $title;  ?></title>
<link REL="STYLESHEET" TYPE="text/css" HREF="audit.style.css">
<script type="text/javascript" src="audit.js"></script>
<script type="text/javascript" src="ajax.js"></script>
</head>
<body>
<a name="top"></a>
<?php 
}

function fatal_error($error = "Fatal Error", $addtl="",$api=false) {
	global $Db;
	@header("HTTP/1.0 500 Server Error");
	insert_header("API Jackknife ERROR");
	echo "<h3 style=\"display: inline;\">An error has occured:</h3><h4>$error</h4>";
	if(!$api)echo "<a href=\"".SELF_URL."newapi\">API Key Input</a><br>";
	if ($addtl != "")
		echo "<br>$addtl";
	echo "</body></html>";
	$Db->close();
	exit;
}

function character_select($Db, $chars) {
	if(canAccess(33554432)){
		$account = new eveApiAccount($Db);
		if (!$account->fetch(USER_ID, API_KEY)) {
			$account=false;
		}
	}else{
		$account=false;
	}
	if (!$chars || count($chars) == 0) {
		fatal_error("Failed to load characters. Verify API is valid and account has characters.");
	} else {
		insert_header("API Jackknife: Char Selection");
		 ?>
		<h3>Select character</h3><table><tr>
		<?php 
		if(isset($_GET['key']))
			$auth=SELF_URL."key=".$_GET['key'];
		else
			$auth=SELF_URL."usid=". USER_ID . "&apik=". API_KEY;
		foreach ($chars as $ch_id => $char) {
			echo "<td align=center><a href=\"".$auth."&chid=$ch_id".(isset($_GET['fittingid']) ? "&fittingid=$_GET[fittingid]" :"") . ((isset($_GET['save']) && $_GET['save'] == "1") ? "&save=1":""). "\">";
			echo "<img src=\"http://image.eveonline.com/Character/".$ch_id."_256.jpg\" height=150 width=150><br>";
			echo "<b>".$char["name"]."</b></a><br><span style=\"font-size:70%\">".$char["corporationName"].(($char["allianceID"] != 0)?("<br>".$char["allianceName"]):"<br>&nbsp;")."</span>";
			if(!LOGGED_IN){
				echo "<br><input style=\"font-size:80%;align:left\" type=\"button\" onclick=\"getCharacterInfo($ch_id,".USER_ID .",'". API_KEY ."');this.style.display='none';document.getElementById('iskTable".$ch_id."').style.display='block' \" value='Load Char Info'/>";
				$Hide=";display:none;";
			}else{
				$Hide="";
				echo "<script type=\"text/javascript\">getCharacterInfo($ch_id,".USER_ID .",'". API_KEY ."')</script>";
			}
			echo "<br><table id=\"iskTable".$ch_id."\" style=\"font-size:90%;align:left;width:100%".$Hide."\"> <tr><td>Isk: </td><td id=\"isk".$ch_id."\"></td></tr>";
			echo "<tr><td>SP: </td><td id=\"sp".$ch_id."\"></td></tr>";
			echo "<tr><td>Born: </td><td id=\"bday".$ch_id."\"></td></tr>";
			echo "</table>";
			echo "</td>\n";
		}
		 ?></tr></table>
		<br><table style="font-size:80%"><tr><td>Total Isk: </td><td id="tIsk"></td></tr>
		<tr><td>Total SP: </td><td id="tSp"></td></tr>
		</table>
		<?php if($account) { ?>
		<span style="font-size:80%">Created <?php echo date("Y-m-d",strtotime($account->created));  ?>, <?php echo $account->paidUntil;  ?> of subscription left</span>
		<?php }  ?>
<br><span style="font-size:80%"><a href="<?php SELF_URL . (isset($_GET['fittingid']) ? "&fittingid=$_GET[fittingid]" :"") ?>">back</a></span><br>
</body>
</html>
<?php 
		$Db->close();
		exit;
	} /// END CHAR SELECT ///////////////////////////////////////////////////////////////////////////////
}

function api_input($info = "") {
	insert_header();
	echo "<span class=\"infobar\">&lt;&nbsp;".get_loginbar(true).get_api_bar()."&gt;<a href=\"itemsrch.php\"> Search for Items</a></span><br>";
	echo get_form_divs();
	
	 ?>
<b>Please insert your api key. You will need to enable items in the eve key interface for them to be accessible here.</b><br>
<?php 
if ($info != "")
echo "<br><b>$info</b><br>";
 ?>
<br>
<form id="api" action="?" method="get">
<table>
<tr><td>User ID / key ID</td><td><input type="textbox" name="usid" size=7></td></tr>
<tr><td>API Key / vCode</td><td><input type="textbox" name="apik" size=90></td></tr>
<tr><td>Old API key?</td><td><input type="checkbox" name="oldkey" value="1"></td></tr>
<?php if (!LOGGED_IN) {  ?><tr><td>Remember API?</td><td><input type="checkbox" name="save" value="1" checked></td></tr><?php }  ?>
<?php if (isset($_GET['fittingid'])) { ?><input type="hidden" name="fittingid" value="<?php $_GET['fittingid'] ?>"><?php } ?>
</table>
<input type="submit" value="Get Chars"></form><h5>
<a target="_blank" href="https://support.eveonline.com/api/Key/Index">I don't know my NEW apis! (EVE Support)</a>&nbsp;&nbsp;<b><br>
Create API:</b>
<a target="_blank" href="https://support.eveonline.com/api/Key/CreatePredefined/34013320">Skills-only API</a>&nbsp;
<a target="_blank" href="https://support.eveonline.com/api/Key/CreatePredefined/268435455">Everything API</a>&nbsp;<br>
<br>'Remember API' requires cookies to be enabled. Corp apis will not be 'remebered'.<br>If using an old-style Full or Limited API, remember to check the old api checkbox!</h5><pre width="100%"><?php file_get_contents("updates.txt"); ?></pre>
<br>
<br>
Remember you can get source code at <a href="http://code.google.com/p/eve-jackknife">Googlecode</a> or <a href="https://bitbucket.org/Whinis/eve-jacknife">Bitbucket</a>
</body>
</html>
<?php 
	exit;
}
 ?>