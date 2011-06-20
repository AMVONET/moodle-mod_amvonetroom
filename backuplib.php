<?php

function amvonetroom_backup_mods($bf,$preferences) {
    global $CFG;

    $status = TRUE;

    $rooms = get_records("amvonetroom", "course", $preferences->backup_course, "id");
    if (!$rooms)
        return $status;

    //Iterate amvonet rooms
    foreach ($rooms as $room) {
        if (backup_mod_selected($preferences, 'amvonetroom', $room->id)){
             $status = $status && amvonetroom_backup_one_mod($bf, $preferences, $room);
        }
    }

    return $status;
}

function amvonetroom_backup_one_mod($bf,$preferences,$roomOrId) {
    global $CFG;

    if (!$roomOrId)
        return FALSE;

    if (is_numeric($roomOrId)) {
        $room = get_record('amvonetroom', 'id', $roomOrId);
    } else {
        $room = $roomOrId;
    }

    //Start mod
    fwrite($bf, start_tag("MOD",3,true));
    //Print data
    fwrite ($bf,full_tag("ID",4,false,$room->id));
    fwrite ($bf,full_tag("MODTYPE",4,false,"amvonetroom"));
    fwrite ($bf,full_tag("NAME",4,false,$room->name));
    fwrite ($bf,full_tag("INTRODUCTION_TEXT",4,false,$room->introduction_text));
    //End mod
    return fwrite ($bf,end_tag("MOD",3,true));
}

////Return an array of info (name,value)
function amvonetroom_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += amvonetroom_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }

     //First the course data
     $info[0][0] = get_string("modulenameplural","amvonetroom");
     $info[0][1] = count_records("amvonetroom", "course", "$course");
     return $info;
}

////Return an array of info (name,value)
function amvonetroom_check_backup_mods_instances($instance,$backup_unique_code) {
     //First the course data
    $info[$instance->id.'0'][0] = '<b>' . format_string(stripslashes($instance->name)) . '</b>';
    $info[$instance->id.'0'][1] = '';
    return $info;
}


?>