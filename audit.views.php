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
<script type="text/javascript" src="jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="jquery-ui.js"></script>
<script type="text/javascript" src="audit.js"></script>
<script type="text/javascript" src="ajax.js"></script>
    <style type="text/css">
        section.center {
            max-width: 150px;
            margin: 100px auto;
        }
        span.clickMe {
            font-size: 30px;
        }
        span.clickMe:hover {
            cursor: pointer;
            color: green;
        }
        section.hidden {
            display: none;
            position: fixed;
        }
        section article.popup {
            position: relative;
            width: 400px;
            height: 300px;
            background: #e3e3e3;
            color: #222;
            border: 1px solid #333;
            border-radius: 3px;
            padding: 5px 7px;
            margin: 10% auto;
        }
        span.close {
            text-transform: uppercase;
            color: #222;
        }
        span.close:hover{
            color: red;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <section class="hidden" >
        <article class="popup">
            <span class="close">Close Me</span>
            <p>Paste red flagged characters, corps, and alliances. One entity per line</p>
            <textarea id="redFlagBox"><?php if(isset($_SESSION)&&isset($_SESSION['redFlagText']))echo $_SESSION['redFlagText']; ?></textarea><br>
            <input type="button" id="saveRedFlag" value="Save">
        </article>
    </section>
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
           if(loggedIn()){
				$Hide="";
				echo "<script type=\"text/javascript\">getCharacterInfo($ch_id,".USER_ID .",'". API_KEY ."')</script>";
			}else{
				echo "<br><input style=\"font-size:80%;align:left\" type=\"button\" onclick=\"getCharacterInfo($ch_id,".USER_ID .",'". API_KEY ."');this.style.display='none';document.getElementById('iskTable".$ch_id."').style.display='block' \" value='Load Char Info'/>";
				$Hide=";display:none;";
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
		exit;
	} /// END CHAR SELECT ///////////////////////////////////////////////////////////////////////////////
}

function api_input($info = "") {
    global $infoBarFunctions,$footerFunctions;
	insert_header();
	$infobar="";
	foreach($infoBarFunctions as $function){
        $infobar.=$function();
    }
    if(!empty($infoBarFunctions))
        $infobar="<span class=\"infobar\">&lt;&nbsp;".$infobar."&gt;</span><br>";
    echo $infobar;

	
	 ?>
	     <h2> Eve JackKnife Api Auditor</h2>
	 <style type="text/css">

        * {
            margin: 0;
        }
        html, body {
            height: 100%;
        }
        .wrapper {
            min-height: 100%;
            height: auto !important;
            height: 100%;
            margin: 0 auto -4em;
        }
        .footer, .push {
            height: 4em;
        }

    </style>
    <div class="wrapper">
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
<?php if (!loggedIn()) {  ?><tr><td>Remember API?</td><td><input type="checkbox" name="save" value="1" checked></td></tr><?php }  ?>
<?php if (isset($_GET['fittingid'])) { ?><input type="hidden" name="fittingid" value="<?php $_GET['fittingid'] ?>"><?php } ?>
</table>
<input type="submit" value="Get Chars"></form><h5>
<a target="_blank" href="https://support.eveonline.com/api/Key/Index">I don't know my NEW apis! (EVE Support)</a>&nbsp;&nbsp;<b><br>
Create API:</b>
<a target="_blank" href="https://community.eveonline.com/support/api-key/createpredefined?accessmask=34013320">Skills-only API</a>&nbsp; or
<a target="_blank" href="https://community.eveonline.com/support/api-key/createpredefined?accessmask=268435455">Everything API</a>&nbsp;<br>
<br>'Remember API' requires cookies to be enabled. Corp apis will not be 'remebered'.<br>If using an old-style Full or Limited API, remember to check the old api checkbox!</h5><pre width="100%"><?php file_get_contents("updates.txt"); ?></pre>
<br>
<br>
This Website is used to audit an api so that you might see your own skills and what ships you can fly, mails, contracts,assets, and any other given access from a specific api key<br>
After inputting your api key either precreated or by using one of the two create links above  you can use the site to view the previously mentioned items from the eve api as well as<br>
checking if you have the skills required to fit a particular ship. This site is mainly for those who want to check another character's api to determine if they meet requirements for their<br>
corp or if what they are telling them is true however this can also be useful to new players to see what ships they can and cannot fly effectively. Green links at the top of the page<br>
can be used to navigate the apis or selecting one-page will display everything at once,WARNING may not load fully on first attempt<br>
<br>
<br>

Remember you can get source code at  <a href="https://bitbucket.org/Whinis/eve-jacknife">Bitbucket</a> or <a href="https://github.com/whinis/eve-jacknife">Github</a>
 <br>
   <?php
        if(defined("public")){ //something noone should need to set, displays a link to my github commit log on the main website
        ?>
            <a href="history.html">Update History</a>
        <?php
        }

 ?>
 <br>
 <br>
 Questions or concerns contact Equto ingame or Email me at Whinis@whinis.com

 <div class="push"></div>
    </div>
    <div class="footer">
EVE Online and the EVE logo are the registered trademarks of CCP hf. All rights are reserved worldwide. All other trademarks are the property of their respective owners. EVE Online, the EVE logo, EVE and all associated logos and designs are the intellectual property of CCP hf. All artwork, screenshots, characters, vehicles, storylines, world facts or other recognizable features of the intellectual property relating to these trademarks are likewise the intellectual property of CCP hf. CCP hf. has granted permission to Eve JackKnife to use EVE Online and all associated logos and designs for promotional and information purposes on its website but does not endorse, and is not in any way affiliated with, Eve JackKnife. CCP is in no way responsible for the content on or functioning of this website, nor can it be liable for any damage arising from the use of this website.
</div>
<?php
    foreach($footerFunctions as $function){
        echo $function();
    }
 ?>
</body>
</html>
<?php 
	exit;
}
 ?>