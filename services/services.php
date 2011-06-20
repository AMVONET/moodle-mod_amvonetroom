<?php
/**
 * Example of XML for POST parameter 'service':
 *
 * <service>
 *   <token>4cbd6ba5d29b2</token>
 *   <name>get_quizes</name>
 *   <arg>1</arg>
 * </service>
 *
 */
require_once("../../../config.php");
require_once("../class.Version.php");
require_once("../class.User.php");
require_once("../class.XmlResponse.php");

$ACCESS = null;

$args = array();
$token = null;
$sessionId = null;
$protoVersion = null;
// 'service' parameter passed via GET has different meaning than passed via POST,
// so we we need direct access to $_GET
$metaService = @$_GET['service'];

$serviceName = "";
if (!empty($metaService)) {
    // process as GET request
    foreach ($_GET as $key => $arg) {
        switch($key) {
        case 'service' :
            $serviceName = clean_param($arg, PARAM_SAFEDIR); // a-zA-Z0-9_-
            break;
        case 'token' :
            $token = clean_param($arg, PARAM_ALPHANUM);
            break;
        case 'sessionId' :
            $sessionId = clean_param($arg, PARAM_SAFEDIR);
            break;
        case 'protoVersion' :
            // do not need clean: Version class performs all necessary post-processing
            $protoVersion = $arg;
            break;
        default :
            // meaning of other parameters depends on service to be called, so they can't be cleaned here
            // service is responsible to clean if necessary
            $args[] = $arg;
            break;
        }
    }
} else {
    // 'service' parameter passed via POST has different meaning than passed via GET,
    // so we we need direct access to $_POST
    $metaService = @$_POST['service'];
    if (empty($metaService)) {
        header("HTTP/1.1 400 Service not specified");
        die();
    }

    $xml = null;
    try {
        $xml = new SimpleXMLElement(stripslashes($metaService));
    } catch (Exception $e) {
    }
    if (!$xml) {
        header("HTTP/1.1 400 Input is invalid");
        die();
    }

    $serviceName =  clean_param((string)$xml->name, PARAM_SAFEDIR); // a-zA-Z0-9_-
    $token = clean_param((string)$xml->token, PARAM_ALPHANUM);
    $sessionId = clean_param((string)$xml->sessionId, PARAM_SAFEDIR);
    // do not need clean: Version class performs all necessary post-processing
    $protoVersion = (string)$xml->protoVersion;

    // meaning of other parameters depends on service to be called, so they can't be cleaned here
    // service is responsible to clean if necessary
    $xmlArg = $xml->arg;
    if ($xmlArg) {
        foreach ($xmlArg as $arg) {
            if (count($arg) == 0) {
                // restore slashes after XML decoding because
                // moodle API expects all input parameters to be slashed
                $args[] = addslashes((string)$arg);
            } else
                $args[] = $arg;
        }
    }
}

if (empty($serviceName)) {
    header("HTTP/1.1 400 Service name not specified");
    die();
}

//Authentication
if (empty($token) || empty($sessionId)) {
    header("HTTP/1.1 401 Unauthorized");
    die();
}

if (!($ACCESS = amvonetroom_User::getAccessByToken($token, $sessionId))) {
    header("HTTP/1.1 403 Forbidden");
    die();
}

//Version validation
if (empty($protoVersion))
    $protoVersion="2.0";
amvonetroom_ProtoVersion::checkRequest($protoVersion);

if (!function_exists('amvonetservice_' . $serviceName)) {
    header("HTTP/1.1 404 Service not found");
    die();
}

$response = call_user_func_array('amvonetservice_' . $serviceName, $args);

//Return XML to response and die process
$response->send();
die();

/**
 * Services (should be stats with 'amvonetservice_')
 *
 */

/**
 * Get all quizes for course
 * @param  $course_id [deprecated] course associated with sessionId is used now
 * @return xml
 * for example:
 *   <?xml version="1.0" encoding="UTF-8"?>
 *   <items>
 *       <item>
 *           <id>1</id>
 *           <name><![CDATA[test quiz]]></name>
 *           <intro><![CDATA[test quiz ]]></intro>
 *           <question_count>0</question_count>
 *           <url>http://demo.amvonet.com/amvonet-moodle/mod/quiz/attempt.php?id=167</url>
 *       </item>
 *   </items>
 */

function amvonetservice_get_quizes(/*$course_id*/) {

    global $CFG, $ACCESS;

    $quizes = get_records("quiz", "course", $ACCESS->room->course);

    $resp_items = amvonetroom_XmlResponse::items();

    if ($quizes) {
        foreach ($quizes as $key => $quiz) {
            $cm = get_coursemodule_from_instance('quiz', $quiz->id);
            $question_count = count_records("quiz_question_instances", "quiz", $quiz->id);

            $resp_item = $resp_items->element("item");
            $resp_item->text("id", $quiz->id);
            $resp_item->text("name", $quiz->name);
            $resp_item->cdata("intro", $quiz->intro);
            $resp_item->text("question_count", $question_count);
            $resp_item->text("url", $CFG->wwwroot . "/mod/quiz/attempt.php?id=" . $cm->id);
        }
    }

    return $resp_items;
}


/**
 * Get all grades for the course
 * @param  $course_id [deprecated] course associated with sessionId is used now
 * @return xml
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *   <item>
 *       <id>32</id>
 *       <name><![CDATA[test quiz]]></name>
 *       <number>0</number>
 *   </item>
 * </items>
 */
function amvonetservice_get_grades(/*$course_id*/) {
    global $CFG, $ACCESS;

    require_once("$CFG->libdir/gradelib.php");

    $grade_items = grade_item::fetch_all(array('courseid' => $ACCESS->room->course));

    $resp_items = amvonetroom_XmlResponse::items();

    if ($grade_items) {
        foreach ($grade_items as $key => $item) {
            if ($item->itemnumber != null) {

                $resp_item = $resp_items->element("item");
                $resp_item->text("id", $item->id);
                $resp_item->cdata("name", stripslashes($item->itemname));
                $resp_item->text("number", $item->itemnumber);
            }
        }
    }

    return $resp_items;
}

/**
 * Add grade
 * @param  $course_id [deprecated] course associated with sessionId is used now
 * @param  $room_id [deprecated] room associated with sessionId is used now
 * @param  $grade_number
 * @param  $grade_name
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <ok>The grade has been added</ok>
 * or if had EPIC FAIL
 * <?xml version="1.0" encoding="UTF-8"?>
 * <error>Can't add grade</error>
 */
function amvonetservice_add_grade($course_id, $room_id, $grade_number, $grade_name) {
    global $CFG, $ACCESS;

    if (!amvonetroom_User::ensureMuPrivileges($ACCESS)) {
        header("HTTP/1.1 403 Forbidden");
        die;
    }

    require_once("$CFG->libdir/gradelib.php");

    // $grade_name doesn't require cleanup, tested with special characters which may affect sql
    if (0 == grade_update('mod/amvonetroom', $ACCESS->room->course, 'mod', 'amvonetroom', $ACCESS->room->id,
        clean_param($grade_number, PARAM_NUMBER), NULL, array(100, 'itemname' => $grade_name))) {
        return amvonetroom_XmlResponse::ok("The grade has been added");
    }
    else {
        return amvonetroom_XmlResponse::error("Can't add grade");
    }

}

/**
 * Delete grade
 * @param  $grade_id
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <ok>The grade has been deleted</ok>
 * or if had EPIC FAIL
 * <?xml version="1.0" encoding="UTF-8"?>
 * <error>Can't delete grade</error>
 */
function amvonetservice_delete_grade($grade_id) {
    global $CFG, $ACCESS;

    if (!amvonetroom_User::ensureMuPrivileges($ACCESS)) {
        header("HTTP/1.1 403 Forbidden");
        die;
    }

    require_once("$CFG->libdir/gradelib.php");

    $grade_item = grade_item::fetch(array('id' => clean_param($grade_id, PARAM_INT)));

    if (!$grade_item) {
        return amvonetroom_XmlResponse::error("Grade $grade_id not found");
    }

    if ($grade_item->delete()) {
        return amvonetroom_XmlResponse::ok("The grade has been deleted");
    } else {
        return amvonetroom_XmlResponse::error("Can't delete grade");
    }
}

/**
 * Save grades for users
 * @param  $grade_id
 * @param  $user_grades
 * @param  $grade_name
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <ok>The user's grades has been saved</ok>
 */
function amvonetservice_save_grade_users($grade_id, $user_grades, $grade_name) {
    global $CFG, $ACCESS;

    if (!amvonetroom_User::ensureMuPrivileges($ACCESS)) {
        header("HTTP/1.1 403 Forbidden");
        die;
    }

    require_once("$CFG->libdir/gradelib.php");

    $grade_item = grade_item::fetch(array('id' => clean_param($grade_id, PARAM_INT)));

    if (!$grade_item) {
        return amvonetroom_XmlResponse::error("Grade $grade_id not found");
    }

    $grade_item->itemname = $grade_name;
    $grade_item->update();

    if ($grade_item) {
        foreach ($user_grades->item as $grade)
        {
            $user_id = clean_param((string)$grade->user_id, PARAM_INT);
            // cleaning not necessary due to explicit type cast to float (see below)
            $user_grade = (string)$grade->user_grade;
            // $user_grade_comment doesn't require cleanup, tested with special characters which may affect sql
            $user_grade_comment = (string)$grade->user_grade_comment;


            $grade_item->update_final_grade(
                $user_id,
                !empty($user_grade) ? (float) $user_grade  : null,
                null,
                $user_grade_comment
            );
        }
    }
    return amvonetroom_XmlResponse::ok("The user's grades has been saved");
}

/**
 * Get grades for users
 * @param  $room_id [deprecated] room associated with sessionId is used now
 * @param  $grade_id
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <item>
 *         <user_id>4</user_id>
 *         <user_name>Vasya Pupkin</user_name>
 *         <user_grade_id></user_grade_id>
 *         <user_grade></user_grade>
 *         <user_grade_comment><![CDATA[]]></user_grade_comment>
 *     </item>
 *     <item>
 *         <user_id>3</user_id>
 *         <user_name>Alexey Batareykin</user_name>
 *         <user_grade_id></user_grade_id>
 *         <user_grade></user_grade>
 *         <user_grade_comment><![CDATA[]]></user_grade_comment>
 *     </item>
 * </items>
 *
 */
function amvonetservice_get_grade_users($room_id, $grade_id) {
    global $CFG, $ACCESS;

    require_once("$CFG->libdir/gradelib.php");

    //$cm = get_coursemodule_from_instance('amvonetroom', $ACCESS->room->id, $ACCESS->room->course);
    //$users = amvonetroom_User::getGradees($cm->course);
    
    $users = amvonetroom_User::getGradees($ACCESS->room->course);

    $grade_item = grade_item::fetch(array('id' => clean_param($grade_id, PARAM_INT)));

    if (!$grade_item) {
        return amvonetroom_XmlResponse::error("Grade $grade_id not found");
    }

    $resp_items = amvonetroom_XmlResponse::items();

    if ($users) {
        $user_ids = array();
        foreach ($users as $user_id => $user) {
            $user_ids[] = $user_id;
        }
        $grade_grades = grade_grade::fetch_users_grades($grade_item, $user_ids);

        if ($grade_grades) {
            foreach ($grade_grades as $grade)
            {
                $user_id = $grade->userid;
                $user = $users[$user_id];

                $resp_item = $resp_items->element("item");

                $resp_item->text("user_id", $user_id);
                $resp_item->text("user_name", $user->firstname . ' ' . $user->lastname);
                $resp_item->text("user_grade_id", $grade->id);
                $resp_item->text("user_grade", $grade->finalgrade);
                $resp_item->cdata("user_grade_comment", stripslashes($grade->feedback));
            }
        }
    }
    
    return $resp_items;
}

/**
 * Get files and folders list.
 *
 * @param  $path
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <dir id="4/folder" name="folder">
 *         <file  id="4/folder/AMVONET.jpg" name="AMVONET.jpg" size="52580" time="2011-01-11 11:03:34" url="http://test.amvonet.com/amvonet-moodle/file.php/4/folder/AMVONET.jpg"/>
 *     </dir>
 * </items>
 *
 */
function amvonetservice_get_files($path, $depth = -1) {
    global $ACCESS;

    $path = str_replace("\\", "/", clean_param($path, PARAM_PATH));
    
    $first = true;
    foreach (explode("/", $path) as $part) {
        if ($part == "." || $part == "..")
            return amvonetroom_XmlResponse::error("Invalid path.");
        if (empty($part))
            continue;
        if ($first) {
            $first = false;
            if (!is_numeric($part))
                return amvonetroom_XmlResponse::error("Course must be integer.");
            if (intval($part) != $ACCESS->room->course) {
                header("HTTP/1.1 403 Forbidden");
                die;
            }
        }
    }
    if ($first)
        return amvonetroom_XmlResponse::error("Course must be specified.");

    $items = amvonetroom_XmlResponse::items();
    amvonetroom__list_dir($items, $path, $depth);
    return $items;
}

/**
 * Helper method to list directory.
 *
 * @param  $parent
 * @param  $path
 * @return none
 */
function amvonetroom__list_dir($parent, $path, $depth = -1) {
    global $CFG;

    if ($depth == 0)
        return;

    $fullpath = $CFG->dataroot . '/' . $path;
    $directory = @opendir($fullpath);
    if ($directory === FALSE) // no such dir
        return;

    while (FALSE !== ($file = readdir($directory))) {
        if ($file == "." || $file == "..") {
            continue;
        }

        $fullname = $path . '/' . $file;
        if (is_dir($fullpath . '/' . $file)) {
            $node = $parent->element("dir", array(
                'id' => $fullname,
                'name' => $file
            ));
            amvonetroom__list_dir($node, $fullname, $depth > 0 ? $depth-1 : -1);
        } else {
            $furl  = $CFG->wwwroot . '/file.php/' . $fullname;
            $fpath = $fullpath . '/' . $file;
	        $fsize = filesize($fpath);
	        $ftime = date ("Y-m-d H:i:s", filemtime($fpath));
            $parent->element("file", array(
                'id' => $fullname,
                'name' => $file,
                'size' => $fsize,
                'time' => $ftime,
                'url' => $furl
            ));
        }
    }
    closedir($directory);
}

/**
 * Get activities list.
 *
 * @param  $course [deprecated] course associated with sessionId is used now
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <activity type="forum" name="News forum" url="http://test.amvonet.com/amvonet-moodle/mod/forum/view.php?id=6"/>
 * </items>
 *
 */
function amvonetservice_get_activities(/*$course*/) {
    global $CFG, $ACCESS;

    $items = amvonetroom_XmlResponse::items();

    foreach (get_course_mods($ACCESS->room->course)  as $mod) {
        $instance = get_record($mod->modname, 'id', $mod->instance);
        if (!$instance)
            continue;
        
        $url = $CFG->wwwroot . '/mod/' . $mod->modname . '/view.php?id=' . $mod->id;
        $items->element("activity", array(
            'type' => $mod->modname,
            'name' => $instance->name,
            'url' => $url
        ));
    }

    return $items;
}

/**
 * Upload specified package into course files and create scorm activity.
 *
 * @param string $name
 * @param string $path
 * @param boolean $collision
 * 
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <activity type="scorm" name="AMVONET SCORM" url="http://test.amvonet.com/amvonet-moodle/mod/scorm/view.php?id=76"/>
 * </items>
 *
 */
function amvonetservice_import_scorm($name, $path, $collision) {
    global $CFG, $ACCESS;

    if (!amvonetroom_User::ensureMuPrivileges($ACCESS))
        return amvonetroom_XmlResponse::error(amvonetroom_User::getError());

    require_once("$CFG->libdir/uploadlib.php");

    // initially, clean params and load course instance
    
    $courseId = $ACCESS->room->course;
    $course = get_record("course", "id", $courseId);
    if (!$course)
        return amvonetroom_XmlResponse::error("Course not found.");

    $path = clean_param($path, PARAM_PATH);
    if (empty($path)) {
        $path = '/';
    } else if ($path[0] != '/') {
        $path = '/' . $path;
    }

    $collision = clean_param($collision, PARAM_BOOL);

    $name = clean_param($name, PARAM_TEXT);

    if (empty($_FILES['package']))
        return amvonetroom_XmlResponse::error("Package is missed.");
    // w/a for wrong passing non-ASCII characters
    $_FILES['package']['name'] = urldecode($_FILES['package']['name']);

    // first, upload scorm package to course's file repository

    if (!$basedir = make_upload_directory($courseId))
        return amvonetroom_XmlResponse::error("Unable to create upload dir.");
    
    $um = new upload_manager('package', false, $collision, $course, false, 0, true);
    if (!$um->process_file_uploads("$basedir$path"))
        return amvonetroom_XmlResponse::error("Unable to upload file.");

    $filename = $um->get_new_filename();
    if (empty($name))
        $name = $filename;

    // second, create scorm activity if it doesn't exists yet
    
    $scorm = get_record("scorm", "reference", "$path/$filename");
    if (!$scorm) {
        $scorm = new stdClass();
        $scorm->instance = '';
        $scorm->course = $courseId;
        $scorm->name = $name;
        $scorm->reference = "$path/$filename";
        $scorm->width = 100; // in percents
        $scorm->height = 800; // in pixels (?)
        $scorm->maxgrade = 100;
        $scorm->grademethod = 1;
        $scorm->timemodified = time();

        $ret = amvonetroom__create_activity('scorm', $scorm);
        if (!$ret['result'])
            return amvonetroom_XmlResponse::error($ret['error']);

        $id = $ret['id'];
    } else {
        $scorm->name = $name;
        
        $ret = amvonetroom__update_activity('scorm', $scorm);
        if (!$ret['result'])
            return amvonetroom_XmlResponse::error($ret['error']);

        $id = $ret['id'];
    }

    rebuild_course_cache($courseId);

    // finally, compose response
    
    $items = amvonetroom_XmlResponse::items();
    $items->element("activity", array(
        'type' => 'scorm',
        'name' => $scorm->name,
        'url' => "$CFG->wwwroot/mod/scorm/view.php?id=$id"
    ));

    return $items;
}

function amvonetroom__create_activity($moduleName, $moduleData) {
    global $CFG, $token;

    $ret = array('result' => false, 'id' => '', 'error' => '');

    if (!course_allowed_module($moduleData->course, $moduleName)) {
        $ret['error'] = 'Module not allowed.';
        return $ret;
    }

    if (!$module = get_record('modules', 'name', $moduleName)) {
        $ret['error'] = 'Module not installed.';
        return $ret;
    }

    require_once("$CFG->dirroot/mod/$moduleName/lib.php");

    $addFunc = "{$moduleName}_add_instance";
    $id = $addFunc($moduleData);
    if (!$id) {
        $ret['error'] = 'Add instance failed.';
        return $ret;
    }

    $cm = new stdClass();
    $cm->course = $moduleData->course;
    $cm->module = $module->id;
    $cm->instance = $id;
    $cm->added = time();

    if (!$cmId = add_course_module($cm) ) {
        $ret['error'] = 'Could not add a new course module.';
        return $ret;
    }

    $sec = new stdClass();
    $sec->course = $moduleData->course;
    $sec->section = amvonetroom__get_section();
    $sec->coursemodule = $cmId;

    if (!$secId = add_mod_to_section($sec)) {
        $ret['error'] = 'Could not add the new course module to section.';
        return $ret;
    }

    if (!set_field("course_modules", "section", $secId, "id", $cmId)) {
        $ret['error'] = 'Could not update the course module with the correct section.';
        return $ret;
    }

    $ret['result'] = true;
    $ret['id'] = $cmId;

    return $ret;
}

function amvonetroom__update_activity($moduleName, $moduleData) {
    global $CFG;

    $ret = array('result' => false, 'id' => '', 'error' => '');

    if (!$cm = get_coursemodule_from_instance($moduleName, $moduleData->id)) {
        $ret['error'] = 'Course module not found.';
        return $ret;
    }

    require_once("$CFG->dirroot/mod/$moduleName/lib.php");

    $moduleData->instance = $moduleData->id;
    $updateFunc = "{$moduleName}_update_instance";
    if (!$updateFunc($moduleData)) {
        $ret['error'] = 'Update instance failed.';
        return $ret;
    }

    $ret['result'] = true;
    $ret['id'] = $cm->id;

    return $ret;
}

function amvonetroom__get_section() {
    global $ACCESS;

    if (!$cm = get_coursemodule_from_instance('amvonetroom', $ACCESS->room->id))
        return 0;

    if (!$sec = get_record('course_sections', 'id', $cm->section))
        return 0;

    return $sec->section;
}

?>