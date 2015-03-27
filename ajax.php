<?php
include_once('eve.php');
include_once('audit.funcs.php');
$pages=array("index.php","manage.php");
if(!isset($Db))
        $Db = new eveDb($sql, $sql_u, $sql_p, $db);

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