<?php
/**
 * Library of functions and constants for module amvonetroom
 *
 **/

require_once("$CFG->dirroot/mod/amvonetroom/class.SchoolPassport.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Session.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Activity.php");

function amvonetroom_add_instance($room) {
    global $USER;

    if (!$id = amvonetroom_Activity::create($room, $USER)) {
        error (amvonetroom_Activity::getError());
		return FALSE;
    }

    return $id;
}

function amvonetroom_update_instance($room) {
    global $USER;

	$room->id = $room->instance;

    if (!amvonetroom_Activity::update($room, $USER)) {
        error (amvonetroom_Activity::getError());
		return FALSE;
    }

    return TRUE;
}

function amvonetroom_delete_instance($id) {
    global $USER;

    if (!amvonetroom_Activity::delete($id, $USER)) {
        echo amvonetroom_Activity::getError();
		return FALSE;
    }

    return TRUE;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function amvonetroom_user_outline($course, $user, $mod, $room) {
	return NULL;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function amvonetroom_user_complete($course, $user, $mod, $room) {
	return TRUE;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in live activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function amvonetroom_print_recent_activity($course, $isteacher, $timestart) {
	return FALSE;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function amvonetroom_cron () {
	return TRUE;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $liveid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function amvonetroom_grades($roomid) {
	return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of live. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $roomid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function amvonetroom_get_participants($roomid) {
	return FALSE;
}

/**
 * This function returns if a scale is being used by one live
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $roomid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function amvonetroom_scale_used ($roomid,$scaleid) {
	return FALSE;
}

?>