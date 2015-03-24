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
	$mysql=new mysqli($sql,$sql_u,$sql_p,$db);
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
			$rows = $mysql->query("SHOW TABLES FROM $db");
			while ($row = mysql_fetch_array($rows)) { 
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
	}
	mysqli_close($mysql);
}else if(isset($_GET['db'])){
	include("eve.config.php");
	session_start ();
	$files=scandir("./SQL/");
	$fileList=array();
	$table=array();
	$sqlFiles=array();
	$mysql=mysqli_connect($sql,$sql_u,$sql_p);
	if (!$mysql) {
		die('Could not connect: ' . mysql_error());
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
	echo "<link REL=\"STYLESHEET\" TYPE=\"text/css\" HREF=\"audit.style.css\">";
	 ?>
	<script type="text/javascript">
	function loadXMLDoc(i)
	{
		document.getElementById('install').onclick="";
		var xmlhttp;
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		while(i<=<?php  echo $fileCount-1;  ?>){
			document.getElementById(i).innerHTML="Installing";
			document.getElementById(i).style.backgroundColor="blue";
			xmlhttp.open("GET",'Installer.php?sql='+i+'&t='+new Date().getTime(),false);
			xmlhttp.send();
			if(xmlhttp.responseText==true){
					document.getElementById(i).style.backgroundColor="green";
					document.getElementById(i).innerHTML="Done";
			}else{
				document.getElementById(i).style.backgroundColor="red";
				document.getElementById(i).innerHTML=xmlhttp.responseText;
				document.getElementById('install').onclick=function(){loadXMLDoc(0)};
				i=null;
				return
			}
			i++;
		}
		xmlhttp.open("GET",'Installer.php?sql=lock&t='+new Date().getTime(),false);
		xmlhttp.send();
		document.getElementById('button').style.display='block';
		
		
		}
	</script>
	<?php 
	$i=0;
	echo "<input type='button' value='Install' onclick='loadXMLDoc(0)' id='install'/>";
	echo "<table>";
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
	echo "</table>";
	echo "<input type='button' value='Go to Main Page' onclick='window.location = \"index.php\"' style='display:none;' id='button'/>";
}elseif (isset($_GET['update'])){
	include("eve.config.php");
	session_start ();
	$files=scandir("./SQL/");
	$fileList=array();
	$table=array();
	$sqlFiles=array();
	$mysql=mysqli_connect($sql,$sql_u,$sql_p);
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
	if(!file_exists("./eve.config.php")){
		header('Location: Installer.php');
	}
	echo "<link REL=\"STYLESHEET\" TYPE=\"text/css\" HREF=\"audit.style.css\">";
	 ?>
	<script type="text/javascript">
	function loadXMLDoc(i)
	{
		document.getElementById('install').onclick="";
		var xmlhttp;
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		while(i<=<?php  echo $fileCount-1;  ?>){
			document.getElementById(i).innerHTML="Installing";
			document.getElementById(i).style.backgroundColor="blue";
			xmlhttp.open("GET",'Installer.php?sql='+i+'&t='+new Date().getTime(),false);
			xmlhttp.send();
			if(xmlhttp.responseText==true){
					document.getElementById(i).style.backgroundColor="green";
					document.getElementById(i).innerHTML="Done";
			}else{
				document.getElementById(i).style.backgroundColor="red";
				document.getElementById(i).innerHTML=xmlhttp.responseText;
				document.getElementById('install').onclick=function(){loadXMLDoc(0)};
				i=null;
				return
			}
			i++;
		}
		xmlhttp.open("GET",'Installer.php?sql=lock&t='+new Date().getTime(),false);
		xmlhttp.send();
		document.getElementById('button').style.display='block';
		
		
		}
	</script>
	<?php 
	$i=0;
	echo "<input type='button' value='Update Database' onclick='loadXMLDoc(0)' id='install'/>";
	echo "<table>";
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
	echo "</table>";
	echo "<input type='button' value='Go to Main Page' onclick='window.location = \"index.php\"' style='display:none;' id='button'></input>";
}elseif (isset($_GET['test'])){
if(isset($_POST['db'])){
	include("eve.config.php");
	$mysql=mysqli_connect($_POST['host'],$_POST['username'],$_POST['pass']);
	if (!$mysql) {
		die('connection');
	}
	if($mysql->select_db($_POST['db'])){
		die('databaseR');
	}else{
		die('database');
	}


}else{
	$mysql=mysqli_connect($_POST['host'],$_POST['username'],$_POST['pass']);
	if (!$mysql) {
		die('connection'.mysql_error());
	}
	die ('connectionR');
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
	
echo "<link REL=\"STYLESHEET\" TYPE=\"text/css\" HREF=\"audit.style.css\">";
echo "
	<script type=\"text/javascript\">
	function checkValues()
	{
		var pass=document.getElementsByName('password')[0].value;
		var host=document.getElementsByName('host')[0].value;
		var db=document.getElementsByName('database')[0].value;
		var username=document.getElementsByName('username')[0].value;
		document.getElementById('button').disabled=\"disabled\";
		var params=\"host=\"+host+\"&pass=\"+pass+\"&username=\"+username;
		var http;
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			http=new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			http=new ActiveXObject(\"Microsoft.XMLHTTP\");
		}
		http.open(\"POST\",'Installer.php?test=1&t='+new Date().getTime(),false);
		//Send the proper header information along with the request
		http.setRequestHeader(\"Content-type\", \"application/x-www-form-urlencoded\");
		http.setRequestHeader(\"Content-length\", params.length);
		http.setRequestHeader(\"Connection\", \"close\");
		http.send(params);
		var params=\"host=\"+host+\"&pass=\"+pass+\"&username=\"+username+\"&db=\"+db;
		if(http.responseText==\"connectionR\"){
			document.getElementById('connection').style.backgroundColor='green';
			document.getElementById('connection').innerHTML='Ready';
			document.getElementById('connection').width='50';
		}else if(http.responseText==\"connection\"){
			document.getElementById('connection').style.backgroundColor='red';
			document.getElementById('connection').innerHTML='Check your Login Details';
			document.getElementById('connection').width='200';
			document.getElementById('button').disabled=\"\";
			return;
		}
		http.open(\"POST\",'Installer.php?test=1&t='+new Date().getTime(),false);
		//Send the proper header information along with the request
		http.setRequestHeader(\"Content-type\", \"application/x-www-form-urlencoded\");
		http.setRequestHeader(\"Content-length\", params.length);
		http.setRequestHeader(\"Connection\", \"close\");
		http.send(params);
		if(http.responseText==\"databaseR\"){
			document.getElementById('database').style.backgroundColor='green';
			document.getElementById('database').innerHTML='Ready';
			document.getElementById('database').width='50';
			document.getElementById('button').style.display='none'
			document.getElementById('button').disabled=false
			document.getElementById('submit').disabled=false
			document.getElementById('submit').style.display='block'
		}else if(http.responseText==\"database\"){
			document.getElementById('database').style.backgroundColor='red';
			document.getElementById('database').innerHTML='Check your database name';
			document.getElementById('database').width='200';
			document.getElementById('button').disabled=\"\";
		
		}
	}
	</script>
	";
echo "
<table>
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
		
<form action=\"Installer.php?config=1\" method='post' accept-charset='UTF-8' name='form'>
<input type='text' name='host'> </input> Mysql Host <br>
<input type='text' name='username'> </input> Mysql Username <br>
<input type='password' name='password'> </input> Mysql Password <br>
<input type='text' name='database'> </input> Mysql Database <br>
<input type='text' name='prefix'> </input> Table Prefix(leave blank for no prefix)<br>
<input type='text' name='emailAddress'> </input> Email address to send password resets from<br>
<input type='text' name='emailHost' value='127.0.0.1'> </input> Mail Server Address<br>
<input type='text' name='emailPort' value='25'> </input> Port for Mail Server<br>
<input type='Button' value='Check' onclick=\"checkValues()\" id='button'></input>
<input type='submit' value='Save' id='submit' style='display:none' disabled='disabled'></input>
</form>





";
}
