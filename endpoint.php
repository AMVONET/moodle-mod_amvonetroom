<?php
/**
 * Created by IntelliJ IDEA.
 * User: root
 * Date: Feb 16, 2010
 * Time: 12:11:41 PM
 */

require_once("../../config.php");
require_once("class.Server.php");
require_once("lib.php");


require_login();

$uid = @$_GET['id'];
$mode = @$_GET['mode'];
$record = @$_GET['record'];

$url = "";
$server_url = amvonetroom_get_serverurl();

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

    $token = User::registerUser($USER, amvonetroom_get_current_user_role ($room));

    $url = Server::combineServletUrl($server_url, 'endpoint', $room->uid);

    if ($mode)
        $url .= "&mode=" . $mode;

    if ($record)
        $url .= "&recordId=" . $record;

    $url .= "&token=" . $token . "&locale=" . $clientLocale;
}
?>
<html>
<head>
    <title>AMVONET</title>

    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <script type="text/javascript" language="JavaScript">
        document.domain = document.domain.substring (document.domain.indexOf (".")+1);
    </script>

    <script type="text/javascript" language="JavaScript">
        // Pass focus to the frame.
        function force_focus() {
            window.frames[0].focus();
        }
    </script>

    <style type="text/css">
        html, body, iframe { margin: 0; padding:0; border:0; width: 100%; height:100%; overflow: visible;  }
    </style>
</head>

<body onfocus="force_focus()">
    <iframe id="endpointFrame" name="endpointFrame" src="<?=$url?>"></iframe>
</body>
</html>