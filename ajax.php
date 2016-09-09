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
        echo add_api_key($Db,$_SESSION['uid'],$keyID,$vCode,$name,$notes);
}
if(isset($_POST['action'])){
        $info['result']="success";
        switch($_POST['action']){
                case "redFlag":
                        session_start();
                        if(!isset($_POST['characters'])||$_POST['characters']=="")
                                echo "NO";
                        $IDS = new eveApiCharacterID($Db);
                        $input=preg_split ('/$\R?^/m', $_POST['characters']);
                        $IDS->fetch($input);
                        $_SESSION['redFlagIds']=$IDS->IDs;
                        $_SESSION['redFlagText']=$_POST['characters'];
                break;
                case "character":
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

                break;
                case "removeKey":
                        session_start();
                        $id=$_POST['keyID'];
                        $result=remove_api_key($Db,$id);
                        if($result!==false){
                            $info['id']=$id;
                        }else{
                            $info['result']="failure";
                        }
                break;
                case "editKey":
                        session_start();
                        $id=$_POST['keyID'];
                        $result=edit_api_key($Db,$id,$_POST['name'],$_POST['notes']);
                        if($result!==false){
                            $info['id']=$id;
                        }else{
                            $info['result']="failure";
                        }
                break;
                case "save":
                        session_start();
                        $keyID=$_POST['keyID'];
                        $vCode=$_POST['vCode'];
                        $name=$_POST['name'];
                        $notes=$_POST['notes'];
                        $info=add_api_key($Db,$_SESSION['uid'],$keyID,$vCode,$name,$notes);
                break;
                case "changeMailFormat":
                        session_start();
                        if($_POST['val']=="true") {
                            $_SESSION['mailFormatted'] = true;
                        }else {
                            $_SESSION['mailFormatted'] = false;
                        }
                break;
                case "ssoLogin":
                    session_start();
                    if(isset($_POST['scopes'])==true && defined("SSO_URL")) {
                        require_once ("ccpOAuth.php");
                        $sso = new ccpOAuth(SSO_URL, SSO_CLIENTID, SSO_SECRET, SSO_CALLBACK, $curl, false);
                        $info['url']=$sso->generateLink($_POST['scopes']);
                    }else {
                        $_SESSION['mailFormatted'] = false;
                    }
                break;
                default:
                        $info['result']="failure";
                break;

        }

        echo json_encode($info);
}



 ?>