<?php

require_once("../../../config.php");
require_once("../class.Version.php");
require_once("../class.User.php");
require_once("../class.XmlResponse.php");

amvonetroom_ProtoVersion::checkRequest();

$login = required_param('login', PARAM_RAW);
$password = required_param('password', PARAM_RAW);
$locale = optional_param('locale', 'en', PARAM_ALPHAEXT);

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
$xml = amvonetroom_User::getAsXml($USER, null, true);
$xml->send();
?>