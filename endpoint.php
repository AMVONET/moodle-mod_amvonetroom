<?php
require_once("../../config.php");
require_once("class.Exception.php");
require_once("class.Server.php");
require_once("class.User.php");
require_once("class.SchoolPassport.php");

require_login();

$uid = required_param('id', PARAM_SAFEDIR);
$mode = optional_param('mode', 'index', PARAM_RAW);
$record = optional_param('record', 0, PARAM_INT);

$passport = amvonetroom_SchoolPassport::get();

if (AMVONETROOM_STATUS_BLOCKED == $passport->getStatus()) {
    $msg = get_string("status_blocked", "amvonetroom") .
           get_string("status_blocked_since", "amvonetroom") .
           $passport->getExpirationDate();
    amvonetroom_error($msg);
}

switch (current_language()) {
    default:
    case 'en': $clientLocale = 'en_US'; break;
    case 'ru': $clientLocale = 'ru_RU'; break;
    case 'es': $clientLocale = 'es_ES'; break;
}

$room = $DB->get_record("amvonetroom", array("uid" => $uid));
if (!$room)
    amvonetroom_die(404);

$token = amvonetroom_User::registerUser($USER);

$url = amvonetroom_Server::balancerRedirect($passport->getEntryPoint(), "endpoint/" . $room->uid)
        . "&token=" . $token
        . "&locale=" . $clientLocale;

if (!empty($mode))
    $url .= "&mode=" . $mode;
if (!empty($record))
    $url .= "&recordId=" . $record;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title>AMVONET</title>

    <link rel="icon" href="pix/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="pix/favicon.ico" type="image/x-icon">

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