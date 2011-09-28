<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Exception.php");

class amvonetroom_Activity {

    public static function create($room, $user) {
        global $DB;

        $id = 0;
        $room->id = 0;
        // we use temporary uid for new session this is necessary to
        //  a. satisfy uid unique index
        //  b. pass callback authorization
        $room->uid = uniqid();

        try {
            // we need to insert record before server-call to pass callback authorization
            $id = $DB->insert_record('amvonetroom', $room);

            $room->id = $id;

            amvonetroom_User::registerUser($user);

            $session = new amvonetroom_Session ($room->uid);
            $session->create($room->course, $room->name, $room->introduction_text);

            $room->uid = $session->getId(); //UID set to new generated value

            $DB->update_record('amvonetroom', $room);

        } catch(dml_exception $e) {
            // cleanup for a broken record
            try {
                $DB->delete_records('amvonetroom', array('id' => $id));
            } catch(Exception $e) {
            }

            amvonetroom_error('Unable to insert amvonetroom instance.'); // TODO: place to locale

        } catch(Exception $e) {
            // cleanup for a broken record
            try {
                $DB->delete_records('amvonetroom', array('id' => $id));
            } catch(Exception $e) {
            }

            throw $e;
        }

        return $id;
    }

    public static function update($room, $user) {
        global $DB;

        if ($room->uid == '1' || $room->uid == '2') {
            amvonetroom_error('It is system activity. You cannot update the activity.'); // TODO: place to locale
        }

        try {
            amvonetroom_User::registerUser($user);

            $session = new amvonetroom_Session ($room->uid);
            $session->update($room->name, $room->introduction_text);

            $DB->update_record('amvonetroom', $room);

        } catch(dml_exception $e) {
            amvonetroom_error('Unable to insert amvonetroom instance.'); // TODO: place to locale
        }
    }

    public static function delete($id, $user) {
        global $DB;

        $room = $DB->get_record("amvonetroom", array("id" => "$id"));
        if (!$room)
            return;

        // error log
        $err = "";

        // trying to delete as much as possible

        // delete amvonet room on server
        try {
            amvonetroom_User::registerUser($user);
            $session = new amvonetroom_Session($room->uid);
            $session->delete();

        } catch(moodle_exception $e) {
            $err .= $e->getMessage() . "\n";
        }

        try {
            $DB->delete_records('amvonetroom', array('id' => $id));
        } catch(dml_exception $e) {
            $err .= "Unable to delete amvonetroom instance.\n"; // TODO: place to locale
        }

        // delete any dependent records
        $pagetypes = page_import_types('mod/amvonetroom/');
        if ($pagetypes) {
            foreach($pagetypes as $pagetype) {
                try {
                    $DB->delete_records('block_instance', array('pageid' => $room->id, 'pagetype' => $pagetype));
                } catch(dml_exception $e) {
                    $err .= "Unable to delete blocks [$pagetype].\n"; // TODO: place to locale
                }
            }
        }

        try {
            $DB->delete_records('event', array('modulename' => 'amvonetroom', 'instance' => $room->id));
        } catch(dml_exception $e) {
            $err .= "Unable to delete events.\n"; // TODO: place to locale
        }

        if (!empty($err))
            amvonetroom_error($err);
    }
}
