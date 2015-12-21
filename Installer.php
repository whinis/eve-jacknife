<?php  
ini_set("display_errors",true);
set_time_limit(300);
if (isset ($_GET[ 'source']))    
       {    
         echo '<a <p> href="',$_SERVER['PHP_SELF'],'"> Back </ a> </ p>';    
         echo '<p> This is the code php file: </p>';
         $page = highlight_file($_SERVER [ 'SCRIPT_FILENAME'], TRUE);    
         $page = str_replace(    
           array ( '<code>', '/ code>', '','</ are >','< font color ="'),    
           array ( '<pre style="padding:1em;border:2px solid black;overflow:scroll">', '/ pre>', '','</ span >','< span style = "color:' ), $page);    
         echo $page;    
         echo '<a <p> href="',$_SERVER['PHP_SELF'],'"> Back </ a> </ p>';    
         echo '</ body> </ html>';    
         exit;    
       }    
if(file_exists("install.lock")&&!(isset($_GET['update'])||isset($_GET['sql']))){
	header('Location: Installer.php?update=1');
	die('JackKnife Already Installed, Please run the upgrader or delete the lock file');
}
if(isset($_GET['sql'])){
	$sql_port=3306;
	include("eve.config.php");
	session_start ();
	if(!isset($_GET['sql'])){
		header('Location: Installer.php?db=1');
	}
	function import_sql($filename) {
		global $mysql;
		$handle = @gzopen($filename, "r"); // can open normal files, too.
		$query = "";
		$queries = 0;
		while ($handle && !feof($handle)) {
			$line = gzgets($handle, 4096); // keep string manipulations sane
			if ($line != "" && substr($line, 0, 2) != "--") { // line doesnt start with comment
				$query .= $line;
				if (substr(trim($line), -2, 2) == ");") {
					$query=str_replace("NOT EXISTS `","NOT EXISTS `".DB_PREFIX,$query);
					$query=str_replace("IF EXISTS `","IF EXISTS `".DB_PREFIX,$query);
					$query=str_replace("TABLE `","TABLE `".DB_PREFIX,$query);
					$query=str_replace("INTO `","INTO `".DB_PREFIX,$query);
					$query=str_replace("TABLES `","TABLES `".DB_PREFIX,$query);
					if (!$mysql->query($query))
						if(defined("DEBUG"))
							die("MYSQL Error: " . $mysql->error ."<Br><br>in query: <textarea>$query</textarea><br>");
						else
							die("MYSQL Error: " . $mysql->error."<br>");
					$query = "";
					$queries++;
				}elseif(substr(trim($line), -1, 1) == ";") {
                    $query=str_replace("NOT EXISTS `","NOT EXISTS `".DB_PREFIX,$query);
                    $query=str_replace("IF EXISTS `","IF EXISTS `".DB_PREFIX,$query);
                    $query=str_replace("TABLE `","TABLE `".DB_PREFIX,$query);
                    $query=str_replace("INTO `","INTO `".DB_PREFIX,$query);
                    $query=str_replace("TABLES `","TABLES `".DB_PREFIX,$query);
                    if (!$mysql->query($query))
                        if(defined("DEBUG"))
                            die("MYSQL Error: " . $mysql->error ."<Br><br>in query: <textarea>$query</textarea><br>");
                        else
                            die("MYSQL Error: " . $mysql->error."<br>");
                    $query = "";
                    $queries++;

                }
			}
		}
		if($queries==0){
            die("NO QUERIES RUN<br>");
        }
		return true;
	}
	$mysql=new mysqli($sql,$sql_u,$sql_p,$db,$sql_port);
	if ($mysql->connect_errno) {
		die('Could not connect: ' . $mysql->connect_error);
	}
	$i=1;
	$fNum=$_GET['sql'];
	if(is_numeric($fNum)&& $fNum>=0 && $fNum<=$_SESSION['fileCount']){
		$file="./SQL/".$_SESSION['files'][$fNum];
		echo import_sql($file);
	}elseif($fNum=="rename"){
		if(defined("DB_PREFIX")){
			$tables = array(); 
			$rows = $mysql->query("SHOW TABLES FROM `$db`");
			while ($row = mysqli_fetch_array($rows)) {
				$tables[] = $row[0]; 
				
			}
			//Append and Rename all tables in a database
			foreach($tables as $table){
				$sql='RENAME TABLE ' .$table . ' TO '.DB_PREFIX.$table;
				$mysql->query($sql);
			}  
		}
	}elseif($fNum=="lock"){
		$fp=fopen("install.lock",'w');
		fclose($fp);
        if(!file_exists("install.lock")){
            echo "error";
        }
        echo "success";
	}
	mysqli_close($mysql);
}else if(isset($_GET['db'])){
	$sql_port=3306;
	include("eve.config.php");
	session_start ();
	$files=scandir("./SQL/");
	$fileList=array();
	$table=array();
	$sqlFiles=array();
	$mysql=mysqli_connect($sql,$sql_u,$sql_p,$db,$sql_port);
	if (!$mysql) {
		die('Could not connect: ' . $mysql->error);
	}
	$rows = $mysql->query("SHOW TABLES FROM $db");
	if(!$rows) {
		trigger_error("(SQL)" . ($mysql->error) . " query: " . "SHOW TABLES FROM $db", E_USER_NOTICE);
	}
	while ($row = $rows->fetch_array()) {
		$tables[$row[0]] = $row[0]; 
		
	}
	foreach ($files as $file)
		if(pathinfo($file, PATHINFO_EXTENSION)=="sql"||pathinfo($file, PATHINFO_EXTENSION)=="gz")
			$fileList[]=$file;
	foreach ($fileList as $file){
	
		$fileName=explode("-",strtolower(substr(pathinfo($file,PATHINFO_BASENAME) ,0,strpos($file,'.'))));
		if(!isset($tables[$fileName[0]]))
			if(preg_match ("/tables_layouts/i", $file)!=false)
				array_unshift($sqlFiles,$file);
			else
				$sqlFiles[]=$file;
	}
	$fileCount=count($sqlFiles);
	$_SESSION['fileCount']=$fileCount;
	$_SESSION['files']=$sqlFiles;
	if(!file_exists("./eve.config.php")){
		header('Location: Installer.php');
	}
    ?>
	<link REL="STYLESHEET" TYPE="text/css" HREF="audit.style.css">
    <script type="text/javascript" src="jquery-1.11.2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            window.document.currentFile=0;
            $("#install").click(function(e) {
                $(this).prop("disabled",true);
                processFile();
            });
        });
        function processFile(){
            var i = window.document.currentFile;
            if (i <=<?php  echo $fileCount-1;  ?>) {
                $.ajax({
                    type: 'POST',
                    url: 'Installer.php?sql=' + i + '&t=' + new Date().getTime(),
                    beforeSend:function (XHR, textStatus){
                        $("#" + window.document.currentFile).css("backgroundColor", "Blue");
                        $("#" + window.document.currentFile).html("Installing");
                    },
                    success: function (data, textStatus, XHR) {
                        if (data == 1) {
                            $("#" + window.document.currentFile).css("backgroundColor", "Green");
                            $("#" + window.document.currentFile).html("Done");
                            window.document.currentFile++;
                            processFile();
                        } else {
                            $("#" + window.document.currentFile).css("backgroundColor", "Red");
                            $("#" + window.document.currentFile).html(data);
                            $("#install").removeProp("disabled");
                        }
                    }
                });
            } else {
                $.ajax({
                    type: 'POST',
                    url: 'Installer.php?sql=lock&t=' + new Date().getTime(),
                    success: function (data, textStatus, XHR) {
                        if(data=="success") {
                            $("#lock").css("backgroundColor", "Green");
                            $("#lock").html("Done");
                            $("#button").css("display", "block");
                        }else{
                            $("#lock").css("backgroundColor", "Red");
                            $("#lock").html(data);
                            $("#install").removeProp("disabled");
                        }
                    }
                });
            }
        }
    </script>

	<input type='button' value='Install' id='install'/>
	<table>
        	<?php
	$i=0;
	While( $i <= $fileCount-1){
		echo"
			<tr>
				<td>
					".$sqlFiles[$i]."
				</td>
				<td id=$i style=\"background-color:grey\">
					Not Started
				</td>
			</tr>
	";
	$i++;
	}
	?>
        <tr>
            <td>
                Create Lock File
            </td>
            <td id="lock" style="background-color:grey">
                Not Started
            </td>
        </tr>
	</table>
	<input type='button' value='Go to Main Page' onclick='window.location = "index.php"' style='display:none;' id='button'/>
    <?php
}elseif (isset($_GET['update'])){
	$sql_port=3306;
	include("eve.config.php");
	session_start ();
	$files=scandir("./SQL/");
	$fileList=array();
	$table=array();
	$sqlFiles=array();
	$mysql=mysqli_connect($sql,$sql_u,$sql_p,$db,$sql_port);
	if (!$mysql) {
		die('Could not connect: ' . $mysql->connect_error);
	}
	$rows = $mysql->query("SHOW TABLES FROM $db");
	while ($row = $rows->fetch_array()) {
		$tables[$row[0]] = $row[0]; 
		
	}
	foreach ($files as $file)
		if(pathinfo($file, PATHINFO_EXTENSION)=="sql"||pathinfo($file, PATHINFO_EXTENSION)=="gz")
			$fileList[]=$file;
	foreach ($fileList as $file){
		$fileName=explode("-",strtolower(substr(pathinfo($file,PATHINFO_BASENAME) ,0,strpos($file,'.'))));
		if(preg_match ("/tables_layouts/i", $file)!=false)
			continue;
		else
			$sqlFiles[]=$file;
	}
	$fileCount=count($sqlFiles);
	$_SESSION['fileCount']=$fileCount;
	$_SESSION['files']=$sqlFiles;

    if(!defined("API_BASE_URL")){ //add the changed location of api base url
        $config=file_get_contents("eve.config.php");
        $config=str_replace('define("CONTRACT_CONTENTS_TABLE", "contract_items");','define("CONTRACT_CONTENTS_TABLE", "contract_items");'." \r\n".'define("API_BASE_URL","https://api.eveonline.com");',$config);
        file_put_contents("eve.config.php",$config);
    }

	if(!file_exists("./eve.config.php")){
		header('Location: Installer.php');
	}
    ?>
	<link REL="STYLESHEET" TYPE="text/css" HREF="audit.style.css">
    <script type="text/javascript" src="jquery-1.11.2.min.js"></script>
	<script type="text/javascript">
        $(document).ready(function() {
            window.document.currentFile=0;
            $("#install").click(function(e) {
                $(this).prop("disabled",true);
                processFile();
            });
        });
        function processFile(){
            var i = window.document.currentFile;
            if (i <=<?php  echo $fileCount-1;  ?>) {
                $.ajax({
                    type: 'POST',
                    url: 'Installer.php?sql=' + i + '&t=' + new Date().getTime(),
                    beforeSend:function (XHR, textStatus){
                        $("#" + window.document.currentFile).css("backgroundColor", "Blue");
                        $("#" + window.document.currentFile).html("Installing");
                    },
                    success: function (data, textStatus, XHR) {
                        if (data == 1) {
                            $("#" + window.document.currentFile).css("backgroundColor", "Green");
                            $("#" + window.document.currentFile).html("Done");
                            window.document.currentFile++;
                            processFile();
                        } else {
                            $("#" + window.document.currentFile).css("backgroundColor", "Red");
                            $("#" + window.document.currentFile).html(data);
                            $("#install").removeProp("disabled");
                        }
                    }
                });
            } else {
                $.ajax({
                    type: 'POST',
                    url: 'Installer.php?sql=lock&t=' + new Date().getTime(),
                    success: function (data, textStatus, XHR) {
                        $("#button").css("display", "block");
                    }
                });
            }
        }
	</script>

	<input type='button' value='Update Database'  id='install'/>
	<table>
    <?php
	$i=0;
	While( $i <= $fileCount-1){
		echo"
			<tr>
				<td>
					".$sqlFiles[$i]."
				</td>
				<td id=$i style=\"background-color:grey\">
					Not Started
				</td>
			</tr>
	";
	$i++;
	}
	?>
	</table>
	<input type='button' value='Go to Main Page' onclick='window.location = "index.php"' style='display:none;' id='button'>
    <?php
}elseif (isset($_GET['test'])){
if(isset($_POST['db'])){
	include("eve.config.php");
	$mysql=mysqli_connect($_POST['host'],$_POST['username'],$_POST['pass'],null,$_POST['port']);
	if (!$mysql) {
		die('connection');
	}
	if($mysql->select_db($_POST['db'])){
		die('databaseR');
	}else{
		die('database');
	}


}elseif(isset($_POST['host'])){
	$mysql=mysqli_connect($_POST['host'],$_POST['username'],$_POST['pass'],null,$_POST['port']);
	if (!$mysql) {
		die('connection'.$mysql->error);
	}
	die ('connectionR');
}else{
    $writeable=is_writable(__DIR__);
    if($writeable&&file_exists("eve.config.php")&&!is_writable("eve.config.php")){
        $writeable=false;
    }
    if($writeable&&file_exists("install.lock")&&!is_writable("install.lock")){
        $writeable=false;
    }

    if (!$writeable) {
        die("permission");
    }
    die ('permissionR');
}





}elseif (isset($_GET['config'])){
$copyright="<?php 
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

// config file
";
$characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
$i = 0;
$salt = "";
do {
	$salt .= $characterList{mt_rand(0,strlen($characterList)-1)};
	$i++;
} while ($i < 15);
$prefix=(($_POST['prefix'])?'"'.$_POST['prefix'].'_"':"\"\"");
$config='
$sql = "'.$_POST['host'].'";
$sql_u = "'.$_POST['username'].'";
$sql_p = "'.$_POST['password'].'";
$db ="'.$_POST['database'].'";
$sql_port="'.$_POST['port'].'";

define("DB_PREFIX",'.$prefix.');

//Email Settings
ini_set("sendmail_from", "'.$_POST['emailAddress'].'");
ini_set("SMTP", "'.$_POST['emailHost'].'");
ini_set("smtp_port", "'.$_POST['emailPort'].'");


// Login Settings
$secureTimeout = "300";
$salt = "'.$salt.'";

date_default_timezone_set ("UTC");

define("API_TABLE","api_lookup");
define("FITTINGS_TABLE","fit_lookup");
define("CACHE_TABLE","api_cache");
define("ID_CACHE_TABLE","id_cache");
define("TYPE_CACHE_TABLE", "api_type_cache");
define("CONTRACT_BIDS_TABLE", "contract_bids");
define("CONTRACT_CONTENTS_TABLE", "contract_items");


define("allow_login",false);


define("API_BASE_URL","https://api.eveonline.com");
define("DEBUG",false);



 ?>';
$fp=fopen("eve.config.php","w");
if($fp){
	if (fwrite($fp,$copyright.$config) === FALSE) {
        echo "Cannot write config file";
        exit;
	}else{
		fclose($fp);
		header('Location: Installer.php?db=1');
		/*echo "
		<script type=\"text/javascript\">
		<!--
		window.location = \"Installer.php?db=1/\"
		//-->
		</script>
		";
		*/
	}
}else{
	echo "Can't write files to this directory, Please make sure you have permissions to";
}


}else{
    $sql="localhost";
    $sql_u="";
    $sql_p="";
    $db="jackknife";
    $sql_port=3306;
    $prefix="";
    if(file_exists("./eve.config.php")){
        include("eve.config.php");
        $prefix=DB_PREFIX;
    }
	?>
    <link REL="STYLESHEET" TYPE="text/css" HREF="audit.style.css">
    <script type="text/javascript" src="jquery-1.11.2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#checkButton").click(function(e) {
                $("#checkButton").prop("disabled",true);
                $.ajax({
                    type: 'POST',
                    url: 'Installer.php?test=1&t='+new Date().getTime(),
                    async: true,
                    success: function (data, textStatus, XHR) {
                        if(data=="permissionR"){
                            $("#permission").css('backgroundColor',"green");
                            $("#permission").css('width',"50");
                            $("#permission").html('Ready');
                            var array={
                                host:$("#dbHost").val(),
                                username:$("#dbUser").val(),
                                pass:$("#dbPass").val(),
                                port: $("#dbPort").val()
                            };
                            $.ajax({
                                type: 'POST',
                                url: 'Installer.php?test=1&t='+new Date().getTime(),
                                data: array,
                                async: true,
                                success: function (data, textStatus, XHR) {
                                    if(data=="connectionR"){
                                        $("#connection").css('backgroundColor',"green");
                                        $("#connection").css('width',"50");
                                        $("#connection").html('Ready');
                                        var array={
                                            host:$("#dbHost").val(),
                                            username:$("#dbUser").val(),
                                            pass:$("#dbPass").val(),
                                            port: $("#dbPort").val(),
                                            db:$("#dbDatabase").val()
                                        };
                                        $.ajax({
                                            type: 'POST',
                                            url: 'Installer.php?test=1&t='+new Date().getTime(),
                                            data: array,
                                            async: true,
                                            success: function (data, textStatus, XHR) {
                                                if(data=="databaseR"){
                                                    $("#database").css('backgroundColor',"green");
                                                    $("#database").css('width',"50");
                                                    $("#database").html('Ready');
                                                    $("#checkButton").hide();
                                                    $("#submit").show();
                                                    $("#submit").removeProp("disabled");
                                                }else{
                                                    $("#database").css('backgroundColor',"red");
                                                    $("#database").css('width',"200");
                                                    $("#database").html('Check your database name');
                                                    $("#checkButton").removeProp("disabled");
                                                }
                                            }
                                        });
                                    }else{
                                        $("#connection").css('backgroundColor',"red");
                                        $("#connection").css('width',"200");
                                        $("#connection").html('Check your Login Details');
                                        $("#checkButton").removeProp("disabled");
                                    }
                                }
                            });
                        }else{
                            $("#permission").css('backgroundColor',"red");
                            $("#permission").css('width',"220");
                            $("#permission").html('Unable to write to base directory');
                            $("#checkButton").removeProp("disabled");
                        }
                    }
                });
            });
        });
    </script>
    <table>
        <tr>
            <td>
                Permission Check:
            </td>
            <td id='permission' width=50>
            </td>
        </tr>
        <tr>
            <td>
                Connection Check:
            </td>
            <td id='connection' width=50>
            </td>
        </tr>
        <tr>
			<td>
				Database Check:
			</td>
			<td id='database' width=50>
			</td>
		</tr>
    </table>

    <form action="Installer.php?config=1" method='post' accept-charset='UTF-8' name='form'>
    <input type='text' name='host' id="dbHost" value="<?php echo $sql; ?>"> Mysql Host <br>
    <input type='text' name='username' id="dbUser" value="<?php echo $sql_u; ?>"> Mysql Username <br>
    <input type='password' name='password' id="dbPass" value="<?php echo $sql_p; ?>"> Mysql Password <br>
    <input type='text' name='database' id="dbDatabase" value="<?php echo $db; ?>"> Mysql Database <br>
    <input type='text' name='port' id="dbPort" value="<?php echo $sql_port; ?>"> Mysql Port <br>
    <input type='text' name='prefix' id="dbPrefix" value="<?php echo $prefix; ?>"> Table Prefix(leave blank for no prefix)<br>
    <input type='text' name='emailAddress' value=""> Email address to send password resets from (Only needed for logins)<br>
    <input type='text' name='emailHost' value='127.0.0.1'> Mail Server Address(Only needed for logins)<br>
    <input type='text' name='emailPort' value='25'> Port for Mail Server(Only needed for logins)<br>
    <input type='Button' value='Check'id='checkButton'>
    <input type='submit' value='Save' id='submit' style='display:none' disabled='disabled'>
    </form>
<?php
}
