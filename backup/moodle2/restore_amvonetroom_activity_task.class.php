<?php

require_once("$CFG->dirroot/mod/amvonetroom/backup/moodle2/restore_amvonetroom_stepslib.php");

/**
 * Amvonetroom restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_amvonetroom_activity_task extends restore_activity_task {

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
        $this->add_step(new restore_amvonetroom_activity_structure_step('amvonetroom_structure', 'amvonetroom.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        //$contents[] = new restore_decode_content('amvonetroom', array('content'), 'amvonetroom');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('AMVONETROOMVIEWBYID', '/mod/amvonetroom/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('AMVONETROOMINDEX', '/mod/amvonetroom/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * amvonetroom logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('amvonetroom', 'add', 'view.php?id={course_module}', '{amvonetroom}');
        $rules[] = new restore_log_rule('amvonetroom', 'update', 'view.php?id={course_module}', '{amvonetroom}');
        $rules[] = new restore_log_rule('amvonetroom', 'view', 'view.php?id={course_module}', '{amvonetroom}');
        $rules[] = new restore_log_rule('amvonetroom', 'amvonetroom', 'view.php?id={course_module}', '{amvonetroom}');
        $rules[] = new restore_log_rule('amvonetroom', 'amvonetroom again', 'view.php?id={course_module}', '{amvonetroom}');
        $rules[] = new restore_log_rule('amvonetroom', 'report', 'report.php?id={course_module}', '{amvonetroom}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        // Fix old wrong uses (missing extension)
        $rules[] = new restore_log_rule('amvonetroom', 'view all', 'index?id={course}', null, null, null, 'index.php?id={course}');
        $rules[] = new restore_log_rule('amvonetroom', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

}

?>
