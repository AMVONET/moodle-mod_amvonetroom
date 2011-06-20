<?php
require_once("$CFG->dirroot/mod/amvonetroom/class.Session.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Activity.php");

//This function executes all the restore procedure about this mod
function amvonetroom_restore_mods($mod, $restore) {

    global $CFG, $USER;

    //Get record from backup_ids
    $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);
    if (!$data)
        return FALSE;

    //Now get completed xmlized object
    $info = $data->info;

    $room = new stdClass();
    $room->course = $restore->course_id;
    $room->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
    $room->introduction_text = backup_todb($info['MOD']['#']['INTRODUCTION_TEXT']['0']['#']);

    if (!amvonetroom_Activity::create($room, $USER)) {
        if (!defined('RESTORE_SILENTLY')) {
            echo '<li> ERROR: "' . format_string(stripslashes($room->name)) . '" ' . amvonetroom_Activity::getError() . '</li>';
            backup_flush(300);
        }
       return FALSE;
    }

    //Do some output
    if (!defined('RESTORE_SILENTLY')) {
        echo "<li>" . get_string("modulename", "amvonetroom") . " \"" . format_string(stripslashes($room->name)) . "\"</li>";
        backup_flush(300);
    }

    //We have the newid, update backup_ids
    backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $room->id);

    return TRUE;
}

function amvonetroom_decode_content_links_caller($restore) {
    return TRUE;
}


?>