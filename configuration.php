<?php

require_once("../../config.php");
require_once('class.SchoolPassport.php');
require_once('class.Version.php');
require_once("class.Server.php");
require_once('class.User.php');

amvonetroom_ProtoVersion::checkRequest();
amvonetroom_User::checkAuthentication($USER);

$sessionId = optional_param('sessionId', null, PARAM_SAFEDIR);
$recordId = optional_param('recordId', null, PARAM_SAFEDIR);

if (empty($sessionId)) {
	header("HTTP/1.1 400 Bad Request");
    die();
}

$passport = amvonetroom_SchoolPassport::get();
if (!$passport) {
    header("HTTP/1.1 500 Internal Server Error");
    die();
}
if ($passport->getStatus() == AMVONETROOM_STATUS_BLOCKED) {
    header("HTTP/1.1 403 Forbidden");
    die();
}

$room = get_record("amvonetroom", "uid", $sessionId);
if (!$room) {
    header("HTTP/1.1 404 Not Found");
    die();
}

$token = amvonetroom_User::registerUser($USER);
if (!$token) {
    header("HTTP/1.1 500 Internal Server Error");
    die();
}

$url = amvonetroom_Server::balancerRedirect($passport->getEntryPoint(), "configuration")
    . "&sessionId=$sessionId"
    . (!empty($recordId) ? "&recordId=$recordId" : "")
    . "&token=$token";

redirect($url);

?>