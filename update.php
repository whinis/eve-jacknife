<?php 
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
if(!file_exists("install.lock"))
	die('JackKnife not Installed, Please Run the installer');
if(isset($_GET['sql'])){
	include("eve.config.php");
	session_start ();
	if(!isset($_GET['sql'])){
		header('Location: Installer.php?db=1');
	}
	function import_sql($filename,$mysql) {
		$handle = @gzopen($filename, "r"); // can open normal files, too.
		$query = "";
		$queries = 0;
		while ($handle && !feof($handle)) {
			$line = gzgets($handle, 1024); // keep string manipulations sane
			if ($line != "" && substr($line, 0, 2) != "--") { // line doesnt start with comment
				$query .= $line;

				if (substr(trim($line), -1, 1) == ";") {
					if (!$mysql->query($query))
						if(defined("DEBUG"))
							echo("MYSQL Error: " . $mysql->error ."<Br><br>in query: $query");
						else
							echo("MYSQL Error: " . $mysql->error);

					$query = "";
					$queries++;
				}
			}
		}
		return true;
	}
	$mysql=mysqli_connect($sql,$sql_u,$sql_p);
	if (!$mysql) {
		die('Could not connect: ' . $mysql->connect_error);
	}
    $mysql->select_db($db);
	$i=1;
	$fNum=$_GET['sql'];
	if(is_numeric($fNum)&& $fNum>=0 && $fNum<=$_SESSION['fileCount']){
		$file="./SQL/".$_SESSION['files'][$fNum];
		echo import_sql($file,$mysql);
	}elseif($fNum=="rename"){
		if(defined("DB_PREFIX")){
			$tables = array(); 
			$rows = $mysql->query("SHOW TABLES FROM $db");
			while ($row = mysqli_fetch_array($rows)) {
				$tables[] = $row[0]; 
				
			}
			//Append and Rename all tables in a database
			foreach($tables as $table){
				$sql='RENAME TABLE ' .$table . ' TO '.DB_PREFIX.$table;
                $mysql->query($sql);
			}  
		}
	}
	mysqli_close($mysql);
}else{
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
	while ($row = mysqli_fetch_array($rows)) {
		$tables[$row[0]] = $row[0]; 
		
	}
	foreach ($files as $file)
		if(pathinfo($file, PATHINFO_EXTENSION)=="sql"||pathinfo($file, PATHINFO_EXTENSION)=="gz")
			$fileList[]=$file;
	foreach ($fileList as $file){
	
		$fileName=explode("-",strtolower(substr(pathinfo($file,PATHINFO_BASENAME) ,0,strpos($file,'.'))));
		if(preg_match ("/update/i", $file)!=false)
			array_unshift($sqlFiles,$file);
		else
			if(preg_match ("/tables_layouts/i", $file)==false)
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
			document.getElementById(i).innerHTML="Updating";
			document.getElementById(i).style.backgroundColor="blue";
			xmlhttp.open("GET",'update.php?sql='+i+'&t='+new Date().getTime(),false);
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
		xmlhttp.open("GET",'Installer.php?sql=rename&t='+new Date().getTime(),false);
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
	echo "<input type='button' value='Go to Main Page' onclick='window.location = \"index.php\"' style='display:none;' id='button'></input>";
}
