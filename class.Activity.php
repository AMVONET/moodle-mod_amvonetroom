<?php

class amvonetroom_Activity {
    private static $error = NULL;
    
    public static function create($room, $user) {
        self::clearError();

        $room->id = 0;
        // we use temporary uid for new session this is necessary to
        //  a. satisfy uid unique index
        //  b. pass callback authorization
        $room->uid = uniqid();

        // we need to insert record before server-call to pass callback authorization
        if (!$id = insert_record('amvonetroom', $room)) {
            self::setError('Unable to insert amvonetroom instance.');
            return FALSE;
        }

        $room->id = $id;

        if (!amvonetroom_User::registerUser($user, $room)) {
            self::setError(amvonetroom_User::getError());
            return FALSE;
        }

        $session = new amvonetroom_Session ($room->uid);
        // $room->name and introduction_text are escaped with slashes, so we need to unescape them
        if (!$session->create($room->course, stripslashes($room->name), stripslashes($room->introduction_text))) {
            delete_records('amvonetroom', 'id', $id);

            self::setError($session->getError());
		    return FALSE;
        }

	    $room->uid = $session->getId(); //UID set to new generated value
    	if(!update_record('amvonetroom', $room)) {
            delete_records('amvonetroom', 'id', $id);

            self::setError('Unable to update amvonetroom uid.');
		    return FALSE;
        }

        return $id;
    }

    public static function update($room, $user) {
        self::clearError();

        if ($room->uid == '1' || $room->uid == '2') {
            self::setError('It is system activity. You cannot update the activity.');
            return FALSE;
        }

        if (!amvonetroom_User::registerUser($user, $room)) {
            self::setError(amvonetroom_User::getError());
            return FALSE;
        }

        $session = new amvonetroom_Session ($room->uid);
        // $room->name and introduction_text are escaped with slashes, so we need to unescape them
        if (!$session->update(stripslashes($room->name), stripslashes($room->introduction_text))) {
            self::setError($session->getError());
            return FALSE;
        }

	    if (!update_record("amvonetroom", $room)) {
            self::setError('Unable to update amvonetroom instance.');
            return FALSE;
        }

        return TRUE;
    }

    public static function delete($id, $user) {
        self::clearError();

        if (!$room = get_record("amvonetroom", "id", "$id")) {
            return TRUE;
        }

        // error log
        $err = "";

        // trying to delete as much as possible

        // delete amvonet room on server
        if (amvonetroom_User::registerUser($user, $room)) {
            $session = new amvonetroom_Session ($room->uid);
            if (!$session->delete()) {
                $err .= $session->getError() . "\n";
            }
        } else {
            $err .= amvonetroom_User::getError() . "\n";
        }

        if (!delete_records('amvonetroom', 'id', $id)) {
            $err .= 'Unable to delete amvonetroom instance.' . "\n";
        }

        // delete any dependent records
        $pagetypes = page_import_types('mod/amvonetroom/');
        if ($pagetypes) {
            foreach($pagetypes as $pagetype) {
                if (!delete_records('block_instance', 'pageid', $room->id, 'pagetype', $pagetype)) {
                    $err .= "Unable to delete blocks [$pagetype].\n";
                }
            }
        }

        if (!delete_records('event', 'modulename', 'amvonetroom', 'instance', $room->id)) {
            $err .= "Unable to delete events.\n";
        }

        if (!empty($err)) {
            self::setError($err);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @return string error or NULL
     */
    public static function getError () {
        return self::$error;
    }

    /**
     * Clears last error.
     *
     * @static
     * @return void
     */
    public static function clearError() {
        self::$error = NULL;
    }

    /**
     * Sets error message.
     * 
     * @static
     * @param  $msg
     * @return void
     */
    public static function setError($msg) {
        self::$error = $msg;
    }
}
