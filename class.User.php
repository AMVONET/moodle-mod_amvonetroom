<?php

define ("AMVONETROOM_ROLE_STUDENT", "st");
define ("AMVONETROOM_ROLE_MODERATOR", "mu");
define ("AMVONETROOM_ROLE_GUEST_MODERATOR", "gmu");

define ("AMVONETROOM_USER_TIME_CACHE", 60 * 60 * 24);

class amvonetroom_User {
    private static $error = NULL;
    private static $token = NULL;

    /**
     * Registers Moodle's user as AMVONET user.
     * Creates temporary file with unique name with user information.
     *
     * @param object $user - Moodle's user
     * @return string token
     */
    public static function registerUser($user) {
        self::clearError();

        // first, clean expired tokens
        self::clean();

        // second, check the token already presented in db
        $access = get_record("amvonetroom_access", "user_id", $user->id);
        if ($access) {
            // refresh token
            $access->last_access = time();
            update_record("amvonetroom_access", $access);
        } else {
            // third, if no token in db or its expired then generate new one
            $access = new stdClass();
            $access->token = uniqid();
            $access->user_id = $user->id;
            $access->last_access = time();

            $id = insert_record("amvonetroom_access", $access);
            if (!$id) {
                self::$error = "Unable to generate security token";
                return FALSE;
            }
            $access->id = $id;
        }
        self::setToken($access->token);

        return $access->token;
    }

    /**
     * Returns compound access object by token.
     *
     * $access
     *   id
     *   token
     *   user_id
     *   last_access
     *   room
     *     id
     *     uid
     *     course
     *     moderator
     *   user
     *    *
     * 
     * @static
     * @param string $token
     * @param string $sessionId
     * @return mixed access object or false if failed
     */
    public static function getAccessByToken ($token, $sessionId) {
        global $CFG;

        self::clearError();

        $meta = get_record_sql("SELECT u.*, a.id AS access_id, a.token, a.user_id, a.last_access, r.id AS room_id, r.uid, r.course, r.moderator
            FROM {$CFG->prefix}user u, {$CFG->prefix}amvonetroom r, {$CFG->prefix}amvonetroom_access a
            WHERE a.token='$token' AND a.user_id=u.id AND r.uid='$sessionId'");

        if (!$meta) {
            self::$error = "Forbidden";
            return FALSE;
        }

        $ttl = time() - AMVONETROOM_USER_TIME_CACHE;
        if (intval($meta->last_access) < $ttl) {
            self::$error = "Forbidden";
            return FALSE;
        }

        // re-compound to linked objects
        $access = new stdClass();
        $access->id = $meta->access_id; unset($meta->access_id);
        $access->token = $meta->token; unset($meta->token);
        $access->last_access = time();
        $access->user_id = $meta->user_id; unset($meta->user_id);

        $room = new stdClass();
        $room->id = $meta->room_id; unset($meta->room_id);
        $room->uid = $meta->uid; unset($meta->uid);
        $room->course = $meta->course; unset($meta->course);
        $room->moderator = $meta->moderator; unset($meta->moderator);
        $access->room = $room;

        $access->user = $meta;
        /////////////////////////////////

        $access->role = self::getRole($access->user, $access->room);
        if (empty($access->role)) {
            self::$error = "Forbidden";
            return FALSE;
        }

        update_record("amvonetroom_access", $access);

        return $access;
    }

    public static function ensureMuPrivileges($access) {
        if ($access->role != AMVONETROOM_ROLE_MODERATOR && $access->role != AMVONETROOM_ROLE_GUEST_MODERATOR) {
            self::$error = "Forbidden";
            return FALSE;
        }

        return TRUE;
    }

    public static function getToken() {
        return self::$token;
    }

    public static function setToken($token) {
        self::$token = $token;
    }

    private static function clean() {
        global $CFG;

        $ttl = time() - AMVONETROOM_USER_TIME_CACHE;
        delete_records_select("amvonetroom_access", "last_access < $ttl");
    }

    /**
     * Returns role for specified user against given amvonet room
     *
     * @param object $user - Moodle's user
     * @param object $room - AMVONET room instance
     * @return string role
     */
    public static function getRole($user, $room) {
        $role = "";

        if ((int)$room->moderator === (int)$user->id) {
            $role = AMVONETROOM_ROLE_MODERATOR;
        } else {
            $ctx = get_context_instance(CONTEXT_COURSE, $room->course);
            if (has_capability('moodle/course:manageactivities', $ctx, $user->id)) {
                $role = AMVONETROOM_ROLE_GUEST_MODERATOR;
            } else if (has_capability('moodle/course:view', $ctx, $user->id)) {
                $role = AMVONETROOM_ROLE_STUDENT;
            }
        }

        return $role;
    }

    /**
     * Returns list of users who have course:manageactivities capability
     * and therefore can be a moderators.
     *
     * @param int $courseId course id
     * @return array of user instances
     */
    public static function getModerators($courseId) {
        return get_users_by_capability (
            get_context_instance(CONTEXT_COURSE, $courseId),
            'moodle/course:manageactivities'
        );
    }

    /**
     * Returns list of users who have course:view capability.
     *
     * @param int $courseId course id
     * @return array of user instances
     */
    public static function getParticipants($courseId) {
        return get_users_by_capability (
            get_context_instance(CONTEXT_COURSE, $courseId),
            'moodle/course:view'
        );
    }

    /**
     * Returns list of users who can be graded.
     *
     * @param int $courseId course id
     * @return array of user instances
     */
    public static function getGradees($courseId) {
        global $CFG;

        $users = array();

        if (empty($CFG->gradebookroles))
            return $users;

        $context = get_context_instance(CONTEXT_COURSE, $courseId);

        foreach(split(',', $CFG->gradebookroles) as $role_id) {
            if (empty($role_id))
                continue;

            $role_users = get_role_users($role_id, $context, true);
			if ($role_users) {
				$users += $role_users;
			}
		}

        return $users;
	}

    /**
     *
     * @return string error or NULL
     */
    public static function getError () {
        return self::$error;
    }

    public static function clearError() {
        self::$error = NULL;
    }

    /**
     * Formats and returns user information for callback.
     * Used to return user info to media-server alse.
     *
     * @param $user - Moodle's user
     * @param $role - user role
     * @return XmlResponse
     */
    public static function getAsXml($user, $role = null, $useNamespace = false) {
        global $CFG;

        $xml = amvonetroom_XmlResponse::user($useNamespace);

        if (!empty($role))
            $xml->attribute("role", $role);

        $xml->attribute("login", $user->username);
        $xml->attribute("firstName", $user->firstname);
        $xml->attribute("lastName", $user->lastname);
        $xml->attribute("lmsId", $user->id);
        $xml->attribute("email", $user->email);

        $urlInfo = parse_url($CFG->wwwroot);
        $xml->attribute("domain", $urlInfo['host']);

        return $xml;
    }

    /**
     * Checks Moodle authentication.
     */
    public static function checkAuthentication($user) {
        if ($user->id == 0) {
            header ("HTTP/1.1 401 Unauthorized");
            die();
        }
    }
}
?>