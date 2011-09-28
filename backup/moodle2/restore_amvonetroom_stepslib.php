<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Activity.php");

/**
 * Structure step to restore one amvonetroom activity
 */
class restore_amvonetroom_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('amvonetroom', '/activity/amvonetroom');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_amvonetroom($data) {
        global $DB, $USER;

        $data = (object)$data;
        $data->course    = $this->get_courseid();
        $data->moderator = $this->get_mappingid('user', $data->moderator, $USER->id);

        $moderator = $DB->get_record('user', array('id' => $data->moderator));
        if(!$moderator)
            $moderator = $USER;

        $newid = amvonetroom_Activity::create($data, $moderator);

        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newid);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context)
        //$this->add_related_files('mod_amvonetroom', 'content', null);
    }
}

?>
 
