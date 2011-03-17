<?php

$mod_amvonetroom_capabilities = array(

    'mod/amvonetroom:moderator' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    )
);

?>
