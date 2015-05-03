<?php
include_once('eve.php');
include_once('audit.funcs.php');
error_reporting(E_ALL);
$pages=array("index.php");
if(!isset($Db))
        $Db = new eveDb($sql, $sql_u, $sql_p, $db);

if(isset($_POST['action'])&&$_POST['action']=="character"){
        $id=$_POST['cID'];
        $uID=$_POST['uID'];
        $vCode=$_POST['vCode'];
        $char= new eveApiSkills($Db);
        if (!$char->fetch($id, $uID, $vCode)) {
                return $id."<321>ERROR";
        }
        $char->loadSkills();
        $info['id']=$id;
        $info['balance']=number_format($char->balance,2);
        $info ['sptotal']=number_format($char->SPTotal,0);
        $info['dob']=date("Y-m-d",$char->charDOB);
        $info['result']="success";
        echo json_encode($info);
}
if(isset($_POST['action'])&&$_POST['action']=="redFlag"){
    session_start();
    if(!isset($_POST['characters'])||$_POST['characters']=="")
        echo "NO";
    $IDS = new eveApiCharacterID($Db);
    $input=preg_split ('/$\R?^/m', $_POST['characters']);
    $IDS->fetch($input);
    $_SESSION['redFlagIds']=$IDS->IDs;
    $_SESSION['redFlagText']=$_POST['characters'];

    $info['session']=  $_SESSION;
    $info['result']="success";
    echo json_encode($info);
}



 ?>