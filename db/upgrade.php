<?php

// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_amvonetroom_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

    if ($oldversion < 2011040400) { // before this version security tokens were stored on file system
        $result = install_from_xmldb_file($CFG->dirroot . '/mod/amvonetroom/db/upgrade-20110404.xml');
    } else if ($oldversion <= 2011053000) { // before this version security tokens were linked with both user and amvonetroom activity
        // clear all existing token to prevent non-unique index after removing fields
        delete_records_select("amvonetroom_access", "(1)"); 

        $table = new XMLDBTable('amvonetroom_access');
        drop_field($table, new XMLDBField('role'));
        drop_field($table, new XMLDBField('room_id'));
    }

    return $result;
}

?>
