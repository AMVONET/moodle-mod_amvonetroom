<?php
require_once("../../config.php");
require_once("class.Server.php");
require_once("class.User.php");
require_once("class.SchoolPassport.php");

require_login();

$uid = required_param('id', PARAM_SAFEDIR); // a-zA-Z0-9_-
$mode = optional_param('mode', 'index', PARAM_RAW);
$record = optional_param('record', 0, PARAM_INT);

$url = "";

$passport = amvonetroom_SchoolPassport::get();
if (!$passport) {
    error(amvonetroom_SchoolPassport::getError());
}
if (AMVONETROOM_STATUS_BLOCKED == $passport->getStatus()) {
    $msg = get_string("status_blocked", "amvonetroom") .
           get_string("status_blocked_since", "amvonetroom") .
           $passport->getExpirationDate();
    error($msg);
}

$clientLocale = 'en_US';
if(empty($SESSION->lang)) {
    if($USER->lang == 'ru_utf8')
        $clientLocale = 'ru_RU';

    if($USER->lang == 'es_utf8')
        $clientLocale = 'es_ES';
} else {
    if($SESSION->lang == 'ru_utf8')
        $clientLocale = 'ru_RU';

    if($SESSION->lang == 'es_utf8')
        $clientLocale = 'es_ES';
}

if ($uid) {
    $room = get_record("amvonetroom", "uid", $uid);

    if (!$room) {
        header ("HTTP/1.1 500 Session not found");
        die("Session not found");
    }

    $token = amvonetroom_User::registerUser($USER);

    $url = amvonetroom_Server::balancerRedirect($passport->getEntryPoint(), "endpoint/{$room->uid}");

    if ($mode)
        $url .= "&mode=" . $mode;

    if ($record)
        $url .= "&recordId=" . $record;

    $url .= "&token=" . $token . "&locale=" . $clientLocale;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>AMVONET</title>

    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <script type="text/javascript" language="JavaScript">
        // Pass focus to the frame.
        function force_focus() {
            window.frames[0].focus();
        }
    </script>

    <style type="text/css">
        html, body, iframe { margin: 0; padding: 0; border: 0; width: 100%; height: 100%; overflow: hidden; }
    </style>
</head>

<body onfocus="force_focus()">
    <!-- frameBorder is IE specific attribute, used to prevent 3d like border on frame -->
    <iframe id="endpointFrame" name="endpointFrame" src="<?=$url?>" frameBorder="0"></iframe>
</body>
</html>