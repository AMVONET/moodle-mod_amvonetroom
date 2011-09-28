<?php

require_once("../../../config.php");
require_once("../class.Exception.php");
require_once("../class.Version.php");
require_once("../class.User.php");
require_once("../class.XmlResponse.php");

amvonetroom_ProtoVersion::checkRequest();

$login = required_param('login', PARAM_RAW);
$password = required_param('password', PARAM_RAW);
$locale = optional_param('locale', 'en', PARAM_ALPHAEXT);

if ($USER->id == 0 && !$login)
    amvonetroom_die(401);

if ($login) {

    $myUser = authenticate_user_login($login, $password);

    if (!$myUser)
        amvonetroom_die(401);

    $myUser = complete_user_login($myUser);

    if ($locale)
        $USER->lang = $locale;
}

$xml = amvonetroom_User::getAsXml($USER, null, true);
$xml->send();

?>