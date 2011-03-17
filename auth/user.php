<?php

require_once("../../../config.php");
require_once("../class.Version.php");
require_once("../class.User.php");

ProtoVersion::checkRequest();

$login = @$_GET['login'];
$password = @$_GET['password'];
$locale = @$_GET['locale'];

if ($USER->id == 0 && !$login) {
    header ("HTTP/1.1 401 Unauthorized");
    die();
}

if ($login) {

    $myUser = authenticate_user_login($login, $password);

    if (!$myUser) {
        header ("HTTP/1.1 401 Unauthorized");
        die();
    }

    $myUser = complete_user_login($myUser);
    
    if ($locale) {
        $USER->lang = $locale + '_utf8';
    }
}
$xml = User::getAsXml($USER, null, true);

header('Content-Type:text/xml;charset=UTF-8');
header("Content-Length: " . strlen($xml));
echo $xml;
?>