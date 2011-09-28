<?php

require_once("../../config.php");
require_once('class.Exception.php');
require_once('class.Version.php');
require_once('class.User.php');
require_once('class.XmlResponse.php');

amvonetroom_ProtoVersion::checkRequest();

$token = optional_param('token', null, PARAM_ALPHANUM);
$sessionId = optional_param('sessionId', null, PARAM_SAFEDIR);

if (empty($token) || empty($sessionId))
    amvonetroom_die(400);  

try {
    $access = amvonetroom_User::getAccessByToken($token, $sessionId);
    $response = amvonetroom_User::getAsXml($access->user, $access->role);
    $response->send();

//TODO: check for specific exception
} catch(Exception $e) {
    amvonetroom_die(403);
}

?>