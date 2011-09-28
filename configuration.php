<?php

require_once("../../config.php");
require_once('class.Exception.php');
require_once('class.SchoolPassport.php');
require_once('class.Version.php');
require_once("class.Server.php");
require_once('class.User.php');

amvonetroom_ProtoVersion::checkRequest();
amvonetroom_User::checkAuthentication($USER);

$sessionId = optional_param('sessionId', null, PARAM_SAFEDIR);
$recordId = optional_param('recordId', null, PARAM_SAFEDIR);

if (empty($sessionId))
    amvonetroom_die(400);

try {
    $passport = amvonetroom_SchoolPassport::get();
    if ($passport->getStatus() == AMVONETROOM_STATUS_BLOCKED)
        amvonetroom_die(403);

    $room = $DB->get_record("amvonetroom", array("uid" => $sessionId));
    if (!$room)
        amvonetroom_die(404);

    $token = amvonetroom_User::registerUser($USER);

    $url = amvonetroom_Server::balancerRedirect($passport->getEntryPoint(), "configuration")
        . "&sessionId=" . $sessionId
        . "&token=" . $token;

    if (!empty($recordId))
        $url .= "&recordId=" . $recordId;

    redirect($url);

} catch (Exception $e) {
    amvonetroom_die(500);
}

?>