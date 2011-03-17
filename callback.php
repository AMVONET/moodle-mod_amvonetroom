<?php

require_once('class.Version.php');
require_once('class.User.php');

ProtoVersion::checkRequest();

/**
 * First, try to include lightweight amvonet.config.php because original config.php contains database requests
 * and slow down login process (callback.php called from media-server on user login).
 */
if (file_exists("../../amvonet.config.php")) {
    require_once("../../amvonet.config.php");
} else {
    require_once("../../config.php");
}


$token = @$_GET['token'];

if (!$token) {
	header ("HTTP/1.1 400 Bad Request");
    die();
}

if ($user = User::getByToken($token)) {
    header("Content-Type: text/xml;charset=UTF-8");
    header("Content-Length: " . strlen($user));
	echo $user;
} else {
	header ("HTTP/1.1 401 Unauthorized");
}
?>