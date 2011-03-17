<?php
/**
 * Created by IntelliJ IDEA.
 * User: den
 */


function amvonetroom_backup_mods($bf,$preferences) {
    global $CFG;

    $status = true;

    ////Iterate ARs
    if ($ars = get_records("amvonetroom", "course", $preferences->backup_course, "id")) {
        foreach ($ars as $ar) {
            if (backup_mod_selected($preferences, 'amvonetroom', $ar->id)){
                 $status = amvonetroom_backup_one_mod($bf, $preferences, $ar);
            }
        }
    }
    return $status;
}

function amvonetroom_backup_one_mod($bf,$preferences,$arid) {
    global $CFG;

    if (is_numeric($arid)) {
        $ar = get_record('amvonetroom', 'id', $arid);
    }

    $status = true;

    //Start mod
    fwrite($bf, start_tag("MOD",3,true));
    //Print data
    fwrite ($bf,full_tag("ID",4,false,$ar->id));
    fwrite ($bf,full_tag("MODTYPE",4,false,"amvonetroom"));
    fwrite ($bf,full_tag("NAME",4,false,$ar->name));
    fwrite ($bf,full_tag("INTRODUCTION_TEXT",4,false,$ar->introduction_text));
    fwrite ($bf,full_tag("DELETED",4,false,$ar->deleted));
    //End mod
    $status = fwrite ($bf,end_tag("MOD",3,true));

    return $status;
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
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';
    return $info;
}


?>