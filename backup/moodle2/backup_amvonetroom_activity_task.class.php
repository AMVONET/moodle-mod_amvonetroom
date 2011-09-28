<?php

require_once("$CFG->dirroot/mod/amvonetroom/backup/moodle2/backup_amvonetroom_stepslib.php");
require_once("$CFG->dirroot/mod/amvonetroom/backup/moodle2/backup_amvonetroom_settingslib.php");

/**
 * Amvonetroom backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_amvonetroom_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new backup_amvonetroom_activity_structure_step('amvonetroom_structure', 'amvonetroom.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of amvonetrooms
        $search="/(".$base."\/mod\/amvonetroom\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@AMVONETROOMINDEX*$2@$', $content);

        // Link to amvonetroom view by moduleid
        $search="/(".$base."\/mod\/amvonetroom\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@AMVONETROOMBYID*$2@$', $content);

        return $content;
    }
}

?>