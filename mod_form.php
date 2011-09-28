<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/amvonetroom/class.User.php');

class mod_amvonetroom_mod_form extends moodleform_mod {

    function definition() 
    {
        global $COURSE, $USER;

        $mform =& $this->_form;

//-------------------------------------------------------------------------------

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('hidden', 'uid');
        
        $mform->addElement('text', 'name', get_string('name_live_this_session', 'amvonetroom'), array('size'=>'70'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('htmleditor', 'introduction_text', get_string('introduction_text', 'amvonetroom'));
        $mform->setType('introduction_text', PARAM_RAW);
        //$mform->addRule('introduction_text', null, 'required', null, 'client');

        $moderators = amvonetroom_User::getModerators($COURSE->id);
        
        $options=array();

        $user_is_teacher = FALSE;
        foreach ($moderators as $key=>$moderator) {
            $options[$moderator->id] = $moderator->firstname . " " . $moderator->lastname;
            if ($moderator->id == $USER->id) {
                $user_is_teacher = TRUE;
            }
        }

        //Add USER to teachers list if not exist
        if (!$user_is_teacher) {
            $_arr = array();
            $_arr[$USER->id] = $USER->firstname . " " . $USER->lastname;
            $options = $_arr + $options;
        }

        $mform->addElement('select', 'moderator', get_string('moderator', 'amvonetroom'), $options);
        $mform->setDefault('moderator', $USER->id);

//-------------------------------------------------------------------------------

        $features = new stdClass;
        $features->groups = FALSE;
        $features->groupings = FALSE;
        $features->groupmembersonly = FALSE;
        $this->standard_coursemodule_elements($features);

//-------------------------------------------------------------------------------
        
        $this->add_action_buttons();
    }

    /**
     * Enforce validation rules here
     *
     * @param object $data Post data to validate
     * @return array
     **/
    function validation($data, $files) 
    {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
?>