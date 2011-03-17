<?php
/**
 * Created by IntelliJ IDEA.
 * User: den
 */

require_once($CFG->dirroot . "/mod/amvonetroom/lib.php");

//This function executes all the restore procedure about this mod
function amvonetroom_restore_mods($mod, $restore) {

    global $CFG;

    $status = true;

    //Get record from backup_ids
    $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);
    if ($data) {
        //Now get completed xmlized object
        $info = $data->info;
        //traverse_xmlize($info);                                                                     //Debug
        //print_object ($GLOBALS['traverse_array']);                                                  //Debug
        //$GLOBALS['traverse_array']="";

        $ar->course = $restore->course_id;
        $ar->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
        $ar->introduction_text = backup_todb($info['MOD']['#']['INTRODUCTION_TEXT']['0']['#']);
        $ar->deleted = backup_todb($info['MOD']['#']['DELETED']['0']['#']);

        //Put ar to mediaserver

        if ($sess = amvonetroom_send_session($ar)) {
            $ar->uid = $sess->uid;
        } else {
            return false;
        }

        //The structure is equal to the db, so insert the ar
        $newid = insert_record("amvonetroom", $ar);

        //Do some output
        if (!defined('RESTORE_SILENTLY')) {
            echo "<li>" . get_string("modulename", "amvonetroom") . " \"" . format_string(stripslashes($ar->name), true) . "\"</li>";
        }
        backup_flush(300);

        if ($newid) {
            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code, $mod->modtype,
                $mod->id, $newid);
        } else {
            $status = false;
        }
    } else {
        $status = false;
    }

    return $status;
}

function amvonetroom_decode_content_links_caller($restore) {
    global $CFG;
    $status = true;

    if ($ars = get_records_sql("SELECT l.id, l.content
                                       FROM {$CFG->prefix}amvonetroom l
                                       WHERE l.course = $restore->course_id")) {
        $i = 0; //Counter to send some output to the browser to avoid timeouts
        foreach ($ars as $ar) {
            //Increment counter
            $i++;
            $content = $ar->introduction_text;
            $result = restore_decode_content_links_worker($content, $restore);

            if ($result != $content) {
                //Update record
                $ar->introduction_text = addslashes($result);
                $status = update_record("amvonetroom", $ar);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />' . s($content) . '<br />changed to<br />' . s($result) . '<hr /><br />';
                    }
                }
            }
            //Do some output
            if (($i + 1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i + 1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }
    return $status;
}


?>