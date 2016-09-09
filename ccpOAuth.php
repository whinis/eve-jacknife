<?php
/**
 * Created by PhpStorm.
 * User: John
 * Date: 6/6/2015
 * Time: 8:55 PM
 */
require_once ("Curl.php");
class ccpOAuth {
    private $loginURL;
    private $clientID;
    private $secret;
    private $hostURL;
    private $debug;
    private $curl;
    private $accessArray=array();
    private $userArray=array();

    function __construct($loginURL,$clientID,$secret,$hostURL,$curl,$debug=false){
        $this->loginURL = $loginURL;
        $this->clientID=$clientID;
        $this->secret=$secret;
        $this->hostURL=$hostURL;
        $this->debug=$debug;
        $this->curl=$curl;
    }

    function generateLoginButton($scopes = ['publicData']){
        $login="";
        $login.='<a href="';
        $login.=generateLink($scopes);
        $login.='">';
        $login.='<img alt="EVE SSO Login Buttons Small White" src="https://images.contentful.com/idjq7aai9ylm/18BxKSXCymyqY4QKo8KwKe/c2bdded6118472dd587c8107f24104d7/EVE_SSO_Login_Buttons_Small_White.png?w=195&amp;h=30"></a>';
        return $login;
    }
    function generateLink($scopes = ['publicData']){
        $link="";
        $link.='https://';
        $link.=$this->loginURL;
        $link.='/oauth/authorize/?response_type=code&redirect_uri=';
        $link.=$this->hostURL;
        $link.='&client_id='.$this->clientID;
        $link.='&scope='.implode(" ",$scopes);
        $link.='&state='.session_id();
        return $link;
    }
    function getToken($authCode,$refresh=false){
        if(session_status() == PHP_SESSION_ACTIVE &&isset($_SESSION['CCP']['access'])&&($_SESSION['CCP']['access']['expires']>time())){
            return $_SESSION['CCP']['access']['access_token'];
        }else {
            if($refresh){
                $oauthArray = array('grant_type' => 'refresh_token', 'refresh_token' => $authCode);
            }else {
                $oauthArray = array('grant_type' => 'authorization_code', 'code' => $authCode);
            }
            $this->curl->setHeader("Authorization", "Basic " . base64_encode($this->clientID . ":" . $this->secret));
            $result = $this->curl->post("https://" . $this->loginURL . "/oauth/token", $oauthArray);
            $accessArray = json_decode($result, true);
            if (session_status() == PHP_SESSION_ACTIVE) {
                $_SESSION['CCP']=array();
                $_SESSION['CCP']['access'] = $accessArray;
                $_SESSION['CCP']['access']['expires'] = time() + $accessArray['expires_in'];
            }
        }
        return $accessArray['access_token'];
    }
    function getUserInfo($token){
        if(isset($_SESSION['CCP']['user'])&&($_SESSION['CCP']['access']['expires']>time())){
            return $_SESSION['CCP']['user'];
        }elseif(session_status() != PHP_SESSION_ACTIVE||$_SESSION['CCP']['access']['expires']>time()) {
            $this->curl->setHeader("Authorization", "Bearer " . $token);
            $user = $this->curl->get("https://" . $this->loginURL . "/oauth/verify");
            $userArray = json_decode($user, true);
            if (session_status() == PHP_SESSION_ACTIVE) {
                $_SESSION['CCP']['user'] = $userArray;
            }
            return $userArray;
        }elseif(isset($_SESSION['CCP']['access']['refresh_token'])){
            $token=$this->getToken($_SESSION['CCP']['access']['refresh_token'],true);
            return $this->getUserInfo($token);
        }else{
            return null;
        }
    }
}