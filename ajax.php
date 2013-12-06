<?php 
include_once('manage.func.php');
include_once('eve.php');
include_once('audit.funcs.php');
$pages=array("index.php","manage.php");
if(!isset($Db))
        $Db = new eveDb($sql, $sql_u, $sql_p, $db);

if(isset($_POST['Save'])){
		session_start();
        $keyID=$_POST['keyID'];
        $vCode=$_POST['vCode'];
        $name=$_POST['name'];
        $notes=$_POST['notes'];
        echo add_api_key($Db->link,$_SESSION['uid'],$keyID,$vCode,$name,$notes);
}
if(isset($_POST['Edit'])){
		session_start();
        $id=$_POST['keyID'];
        if(isset($_POST['name'])){
                $name=$_POST['name'];
                echo edit_api_key($Db->link,$id,$name,"");
        }
        if(isset($_POST['notes'])){
                $notes=$_POST['notes'];
                echo edit_api_key($Db->link,$id,"",$notes);
        }
}
elseif(isset($_POST['Remove'])){
		session_start();
        $id=$_POST['keyID'];
        echo remove_api_key($Db,$id);

}
elseif(isset($_POST['Character'])){
        $id=$_POST['cID'];
        $uID=$_POST['uID'];
        $vCode=$_POST['vCode'];
        $char= new eveApiSkills($Db);
        if (!$char->fetch($id, $uID, $vCode)) {
                return $id."<321>ERROR";
        }
        $char->loadSkills();
        echo $id."<321>".number_format($char->balance,2)."<321>".number_format($char->SPTotal,0)."<321>".date("Y-m-d",$char->charDOB);
}



 ?>