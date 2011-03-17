<?php  // $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * Library of functions and constants for module amvonetroom
 *
 **/

require_once('class.SchoolPassport.php');
require_once('class.Session.php');

function amvonetroom_get_users($id, $roles)
{
	$context = get_context_instance(CONTEXT_MODULE, $id);

	$users = array();

	foreach( $roles as $role_name )
	{
		$role_records = get_records('role', 'shortname', $role_name);

		foreach( $role_records as $role )
		{
			if ($addusers = get_role_users($role->id, $context, true, 'u.id, u.firstname, u.lastname, u.email, ra.hidden')) {
				$users += $addusers;
			}
		}
	}
	return $users;
}

function amvonetroom_user_has_role($id, $user, $rolenames )
{
	if (! $cm = get_record("course_modules", "id", $id)) {
		return FALSE;
	}

	$context = get_context_instance(CONTEXT_MODULE, $cm->id);

	$roles = get_user_roles( $context, $user );
	foreach( $rolenames as $rolename )
	{
		foreach( $roles as $role )
		{
			if( $role->shortname == $rolename ) return TRUE;
		}
	}
	return FALSE;
}

function amvonetroom_get_serverurl () {
    $passport = SchoolPassport::get();
    if (!$passport) {
        error(SchoolPassport::getError());
    }
    if (STATUS_BLOCKED == $passport->getStatus()) {
        $msg = get_string("status_blocked", "amvonetroom") .
               get_string("status_blocked_since", "amvonetroom") .
               $passport->getExpirationDate();
        error($msg);
    }

	return $passport->getEntryPoint();
}

function amvonetroom_add_instance($room) {
	global $CFG;
    global $USER;

	$room->timemodified = time();
	$room->uid = 0; //UID set to 0 for add new session

    // TODO: we need $room->id here, think about it
    //User::registerUser($USER, amvonetroom_get_current_user_role ($room));

    $session = new Session ("0");
    // $room->name and introduction_text are escaped with slashes, so we need to unescape them
    if (!$session->create($room->course, stripslashes($room->name), stripslashes($room->introduction_text))) {
        error ($session->getError());
		return FALSE;
    }

	$room->uid = $session->getId(); //UID set to new generated value

	if( $id = insert_record("amvonetroom", $room) )
	{
		$event = NULL;
		$event->name        = $room->name;
		$event->description = $room->introduction_text;
		$event->courseid    = $room->course;
		$event->groupid     = 0;
		$event->userid      = 0;
		$event->modulename  = 'amvonetroom';
		$event->instance    = $id;
		$event->eventtype   = 0;
		$event->timestart   = time();
		$event->timeduration = 0;

		add_event($event);
	}

	$room->id = $id;

	return $id;
}

function amvonetroom_update_instance($room) {
    global $USER;

	$room->timemodified = time();
	$room->id = $room->instance;

    if ($room->uid == '1' || $room->uid == '2') {
       error ('It is system activity. You cannot update the activity.');
       return FALSE;
    }

    User::registerUser($USER, amvonetroom_get_current_user_role ($room));

    $session = new Session ($room->uid);
    if (!$session->update($room->name, $room->introduction_text)) {
        error ($session->getError());
		return FALSE;
    }

	return update_record("amvonetroom", $room);
}

function amvonetroom_delete_instance($id) {
    global $USER;

	if (!$room = get_record("amvonetroom", "id", "$id")) {
		return FALSE;
	}

    User::registerUser($USER, amvonetroom_get_current_user_role ($room));

    $session = new Session ($room->uid);
    if (!($result = $session->delete())) {
        echo $session->getError();
    }

	# Delete any dependent records here #

	$room->deleted = 1;
	if (! update_record("amvonetroom", $room)) {
		$result = FALSE;
	}

	$pagetypes = page_import_types('mod/amvonetroom/');
    if ($pagetypes) {
        foreach($pagetypes as $pagetype) {
            if(!delete_records('block_instance', 'pageid', $room->id, 'pagetype', $pagetype)) {
                $result = FALSE;
            }
        }
    }

	if (! delete_records('event', 'modulename', 'amvonetroom', 'instance', $room->id)) {
		$result = FALSE;
	}

	return $result;
}

function amvonetroom_get_current_user_role ($room) {
	global $USER;

    $cm = get_coursemodule_from_instance ("amvonetroom", $room->id, $room->course);
    if (!$cm)
        return "";

	$role = ROLE_STUDENT;
	if ((int)$room->moderator === (int)$USER->id)
	{
		$role = ROLE_MODERATOR;
	} else {
		$roles = array ("editingteacher", "teacher");
		$teachers = amvonetroom_get_users($cm->id, $roles);

		foreach ($teachers as $teacher)	{
			if ((int)$teacher->id == (int)$USER->id) {
				$role = ROLE_GUEST_MODERATOR;
				break;
			}
		}
	}

	return $role;

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