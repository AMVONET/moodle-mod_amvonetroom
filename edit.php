<?php

require_once("../../config.php");
require_once("class.Exception.php");

require_login();

$sessionId = required_param("id", PARAM_SAFEDIR); // a-zA-Z0-9_-

$room = $DB->get_record("amvonetroom", array("uid" => $sessionId));
if (!$room)
    amvonetroom_error("Session not found"); // TODO: place to locale

$cm = get_coursemodule_from_instance ("amvonetroom", $room->id, $room->course);
if (!$cm)
    amvonetroom_error("Course module not found"); // TODO: place to locale

redirect($CFG->wwwroot . "/course/modedit.php?update=" . $cm->id . "&return=0");

?>