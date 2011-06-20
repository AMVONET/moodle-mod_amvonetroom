<?php
require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);

if ($id) {
    $str_input = '<input type="hidden" name="cm" value="' . $id . '">';
    if (!$cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (!$room = get_record("amvonetroom", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
}
else
{
    $str_input = '<input type="hidden" name="a" value="' . $a . '">';
    if (!$room = get_record("amvonetroom", "id", $a)) {
        error("Course module is incorrect");
    }
    if (!$course = get_record("course", "id", $room->course)) {
        error("Course is misconfigured");
    }
    if (!$cm = get_coursemodule_from_instance("amvonetroom", $room->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

redirect("./endpoint.php?id=" . $room->uid);
die();

?>