<?php

require_once("../../config.php");
require_once("class.Exception.php");

$id = optional_param('id', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);

// TODO: place errors to locale
if ($id) {
	if (! $cm = $DB->get_record("course_modules", array ("id" => $id))) {
        amvonetroom_error("Course Module ID was incorrect");
    }

	if (! $course = $DB->get_record("course", array ("id" => $cm->course))) {
        amvonetroom_error("Course is misconfigured");
    }

	if (! $room = $DB->get_record("amvonetroom", array ("id" => $cm->instance)) ) {
        amvonetroom_error("Course module is incorrect");
    }

} else {
	if (! $room = $DB->get_record("amvonetroom", array ("id" => $a))) {
        amvonetroom_error("Course module is incorrect");
    }

	if (! $course = $DB->get_record("course", array ("id" => $room->course))) {
        amvonetroom_error("Course is misconfigured");
    }

    if (!$cm = get_coursemodule_from_instance("amvonetroom", $room->id, $course->id)) {
        amvonetroom_error("Course Module ID was incorrect");
    }
}

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

redirect("./endpoint.php?id=" . $room->uid);

?>