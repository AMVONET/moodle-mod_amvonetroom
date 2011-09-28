<?php

/**
 * Define the complete amvonetroom structure for backup, with file and id annotations
 */
class backup_amvonetroom_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated
        $amvonetroom = new backup_nested_element('amvonetroom', array('id'), array(
            'uid', 'name', 'introduction_text', 'moderator', 'course'));

        // Build the tree

        // Define sources
        $amvonetroom->set_source_table('amvonetroom', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations

        // Define file annotations
        $amvonetroom->annotate_files('mod_amvonetroom', 'content', null); // This file area hasn't itemid

        // Return the root element (amvonetroom), wrapped into standard activity structure
        return $this->prepare_activity_structure($amvonetroom);
    }
}

?>
