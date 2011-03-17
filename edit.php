<?php
/**
 * Created by IntelliJ IDEA.
 * User: root
 * Date: Mar 23, 2010
 * Time: 11:27:32 AM
 */

require_once("../../config.php");
require_once("lib.php");

require_login();

$uid = $_GET['id'];

if ($uid) {
    $room = get_record("amvonetroom", "uid", $uid);
}

if (!$room) {
    header ("HTTP/1.1 500 Session not found");
    die("Session not found");
}

$cm = get_coursemodule_from_instance ('amvonetroom', $room->id, $room->course);
header("Location: " . $CFG->wwwroot . "/course/modedit.php?update=" . $cm->id . "&return=0");
die();
?>
