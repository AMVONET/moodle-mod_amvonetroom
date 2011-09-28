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
require_once("../class.Exception.php");
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
    if (empty($metaService))
        amvonetroom_die(400, "Service not specified");

    $xml = null;
    try {
        $xml = new SimpleXMLElement($metaService);
    } catch (Exception $e) {
    }
    if (!$xml)
        amvonetroom_die(400, "Input is invalid");

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
                $args[] = (string)$arg;
            } else
                $args[] = $arg;
        }
    }
}

if (empty($serviceName))
    amvonetroom_die(400, "Service name not specified");

//Authentication
if (empty($token) || empty($sessionId))
    amvonetroom_die(401);

try {
    $ACCESS = amvonetroom_User::getAccessByToken($token, $sessionId);
    // authenticate user by token, it's need to browse files.
    $USER = $ACCESS->user;
} catch (amvonetroom_Exception $e) {
    amvonetroom_die(403);
}

// set locale
$SESSION->lang = !empty($ACCESS->lang) ? $ACCESS->lang : $USER->lang;
moodle_setlocale();

//Version validation
if (empty($protoVersion))
    $protoVersion="2.0";
amvonetroom_ProtoVersion::checkRequest($protoVersion);

if (!function_exists('amvonetservice_' . $serviceName))
    amvonetroom_die(404, "Service not found");

try {
    $response = call_user_func_array('amvonetservice_' . $serviceName, $args);
} catch (Exception $e) {
    $response = amvonetroom_XmlResponse::error('Unexpected error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
}

//Return XML to response and die process
$response->send();
die();

/**
 * Services (should be stats with 'amvonetservice_')
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

    global $CFG, $DB, $ACCESS;

    $resp_items = amvonetroom_XmlResponse::items();

    $quizes = $DB->get_records("quiz", array("course" => $ACCESS->room->course));
    if ($quizes) {
        foreach ($quizes as $key => $quiz) {
            $cm = get_coursemodule_from_instance('quiz', $quiz->id);
            $question_count = $DB->count_records("quiz_question_instances", array("quiz" => $quiz->id));

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

    $resp_items = amvonetroom_XmlResponse::items();

    $grade_items = grade_item::fetch_all(array('courseid' => $ACCESS->room->course));
    if ($grade_items) {
        foreach ($grade_items as $key => $item) {
            if ($item->itemnumber != null) {

                $resp_item = $resp_items->element("item");
                $resp_item->text("id", $item->id);
                $resp_item->cdata("name", $item->itemname);
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

    amvonetroom_User::checkMuPrivileges($ACCESS);

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

    amvonetroom_User::checkMuPrivileges($ACCESS);

    require_once("$CFG->libdir/gradelib.php");

    $grade_item = grade_item::fetch(array('id' => clean_param($grade_id, PARAM_INT)));
    if (!$grade_item)
        return amvonetroom_XmlResponse::error("Grade $grade_id not found");

    try {
        $grade_item->delete();
    } catch(dml_exception $e) {
        return amvonetroom_XmlResponse::error("Can't delete grade");
    }

    return amvonetroom_XmlResponse::ok("The grade has been deleted");
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

    amvonetroom_User::checkMuPrivileges($ACCESS);

    require_once("$CFG->libdir/gradelib.php");

    $grade_item = grade_item::fetch(array('id' => clean_param($grade_id, PARAM_INT)));
    if (!$grade_item)
        return amvonetroom_XmlResponse::error("Grade $grade_id not found");

    try {
        $grade_item->itemname = $grade_name;
        $grade_item->update();
    } catch(dml_exception $e) {
        return amvonetroom_XmlResponse::error("Can't update grade");
    }

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

    $grade_item = grade_item::fetch(array('id' => clean_param($grade_id, PARAM_INT)));
    if (!$grade_item)
        return amvonetroom_XmlResponse::error("Grade $grade_id not found");

    $resp_items = amvonetroom_XmlResponse::items();

    $users = amvonetroom_User::getGradees($ACCESS->room->course);
    if ($users) {
        $user_ids = array();
        foreach ($users as $user_id => $user) {
            $user_ids[] = $user_id;
        }

        $grade_grades = grade_grade::fetch_users_grades($grade_item, $user_ids);
        if ($grade_grades) {
            foreach ($grade_grades as $grade) {
                $user_id = $grade->userid;
                $user = $users[$user_id];

                $resp_item = $resp_items->element("item");

                $resp_item->text("user_id", $user_id);
                $resp_item->text("user_name", $user->firstname . ' ' . $user->lastname);
                $resp_item->text("user_grade_id", $grade->id);
                $resp_item->text("user_grade", $grade->finalgrade);
                $resp_item->cdata("user_grade_comment", $grade->feedback);
            }
        }
    }

    return $resp_items;
}

/**
 * Get files and folders list.
 *
 * @param  $path is the path in format compatible with the Moodle 2.0 file browser:
 *               '/contextid/component/filearea/itemid/some/other/path/filename'.
 *               Some parts can be missed, '/' is valid path, the filename '.' means a directory.
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

    $path = str_replace("\\", "/", clean_param($path, PARAM_PATH));

    $params = new stdClass();
    $params->contextid = null;
    $params->component = null;
    $params->filearea  = null;
    $params->itemid    = '0';
    $params->filepath  = '/';   // must start and end since '/'
    $params->filename  = '.';   // means a current dir

    // Extract params from the path argument.
    // In Moodle 1.x pathes starts since a course id,
    // so we ignore it and begin since the root of the filesystem.
    // In Moodle 2.0 we demand that a path starts since '/'.
    if (!empty($path) && $path[0] == '/' && strlen($path) > 1) {
        $parts = split('/', substr($path, 1));
        if (count($parts) > 0) {
            if (!is_numeric($parts[0])) return amvonetroom_XmlResponse::error("Context ID must be an integer.");
            $params->contextid = array_shift($parts);
        }
        if (count($parts) > 0) $params->component = array_shift($parts);
        if (count($parts) > 0) $params->filearea = array_shift($parts);
        if (count($parts) > 0) {
            if (!is_numeric($parts[0])) return amvonetroom_XmlResponse::error("Item ID must be an integer.");
            $params->itemid = array_shift($parts);
        }
        if (count($parts) > 0) $params->filename = array_pop($parts);
        if (count($parts) > 0) $params->filepath = '/'. join('/', $parts) . '/';
    }

    $items = amvonetroom_XmlResponse::items();

    $browser = get_file_browser();
    $context = is_null($params->contextid) ? get_system_context() : get_context_instance_by_id($params->contextid);
        
    if(!$context)      // cannot find a context by specified id
        return $items; // TODO: may be return a bad status?

    $info = $browser->get_file_info($context, $params->component, $params->filearea,
                                    $params->itemid, $params->filepath, $params->filename);

    if (!is_null($info) && $info->is_directory() && $info->is_readable())
        amvonetroom__list_dir($items, $info, $depth);

    return $items;
}

/**
 * Helper method to list directory.
 *
 * @param amvonetroom_XmlResponsePart $items
 * @param file_info $dir
 * @param int $depth
 * @return
 */
function amvonetroom__list_dir(amvonetroom_XmlResponsePart $items, file_info $dir, $depth = -1) {

    if ($depth == 0)
        return;

    foreach ($dir->get_children() as $child) {
        if (!$child->is_readable())
            continue;

        if (!$child->is_directory()) {
            $items->element("file", array(
                'id'   => amvonetroom__create_filesystem_path((object)$child->get_params()),
                'name' => $child->get_visible_name(),
                'size' => $child->get_filesize(),
                'time' => date("Y-m-d H:i:s", $child->get_timemodified()),
                'url'  => $child->get_url()
            ));
        } else if (!$child->is_empty_area()) {
            $item = $items->element("dir", array(
                'id'   => amvonetroom__create_filesystem_path((object)$child->get_params()),
                'name' => $child->get_visible_name()
            ));
            amvonetroom__list_dir($item, $child, $depth > 0 ? $depth-1 : -1);
        }
    }
}

/**
 * Helper method to create a filesystem identifier of Moodle 2.x.
 *
 * @param  $params is std object with fields contextid, component, filearea, itemid, filepath, filename.
 * @return string which has format '/contextid/component/filearea/itemid/some/other/path/filename'
 */
function amvonetroom__create_filesystem_path($params) {
    if (is_null($params->contextid))
        return '/';

    if (is_null($params->component) || is_null($params->filearea) || is_null($params->itemid))
        return "/$params->contextid";

    return "/$params->contextid/$params->component/$params->filearea/$params->itemid". $params->filepath . $params->filename;
}

/**
 * Get activities list.
 *
 * @param $course [deprecated] course associated with sessionId is used now
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <activity type="forum" name="News forum" url="http://test.amvonet.com/amvonet-moodle/mod/forum/view.php?id=6"/>
 * </items>
 *
 */
function amvonetservice_get_activities() {
    global $CFG, $DB, $ACCESS;

    $items = amvonetroom_XmlResponse::items();

    foreach (get_course_mods($ACCESS->room->course) as $mod) {
        $instance = $DB->get_record($mod->modname, array ('id' => $mod->instance));
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
 * @param string $path [deprecated since Moodle 2.0]
 * @param boolean $collision [deprecated since Moodle 2.0]
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
    global $CFG, $DB, $ACCESS;

    amvonetroom_User::ensureMuPrivileges($ACCESS);

    // initially, clean params and load course instance

    $courseId = $ACCESS->room->course;
    $course = $DB->get_record("course", array ("id" => $courseId));
    if (!$course)
        return amvonetroom_XmlResponse::error("Course not found.");

/*
    $collision = clean_param($collision, PARAM_BOOL);

    $path = clean_param($path, PARAM_PATH);
    if (empty($path)) {
        $path = '/';
    } else {
        if ($path[0] != '/')
            $path = '/' . $path;
        if ($path[strlen($path)-1] != '/')
            $path = $path . '/';
    }
*/

    if (empty($_FILES['package']))
        return amvonetroom_XmlResponse::error("Package is missed.");
    // w/a for wrong passing non-ASCII characters
    $filename = $_FILES['package']['name'] = urldecode($_FILES['package']['name']);

    $name = clean_param($name, PARAM_TEXT);
    if (empty($name))
        $name = $filename;

    // first, create scorm activity if it doesn't exists yet

    $scorm = $DB->get_record("scorm", array("course" => $courseId, "name" => $name));
    if (!$scorm) {
        $scorm = new stdClass();
        $scorm->instance     = '';
        $scorm->course       = $courseId;
        $scorm->name         = $name;
        $scorm->reference    = $filename;
        $scorm->component    = 'mode_scorm';
        $scorm->area         = 'package';
        $scorm->intro        = '';
        $scorm->width        = 100; // in percents
        $scorm->height       = 800; // in pixels (?)
        $scorm->maxgrade     = 100;
        $scorm->grademethod  = 1;
        $scorm->timemodified = time();

        $ret = amvonetroom__create_activity('scorm', $scorm);
        if (!$ret['result'])
            return amvonetroom_XmlResponse::error($ret['error']);

        $id = $ret['id'];

        $scorm = $DB->get_record("scorm", array("course" => $courseId, "name" => $name));
        if (!$scorm)
            return amvonetroom_XmlResponse::error('Canoot get created scorm activity.');

    } else {
        $scorm->name = $name;
        $scorm->reference = $filename;

        if (!$cm = get_coursemodule_from_instance('scorm', $scorm->id))
            return amvonetroom_XmlResponse::error("Course $scorm->name module not found.");
        $id = $cm->id;
    }

    // second, upload scorm package to course's file repository.

    $context = get_context_instance(CONTEXT_MODULE, $id);

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_scorm');

    $fileinfo = array(
        'contextid' => $context->id,
        'component' => 'mod_scorm',
        'filearea'  => 'package',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename);
    $fs->create_file_from_pathname($fileinfo, $_FILES['package']['tmp_name']);

    // third, update scorm activity to parse the package

    $ret = amvonetroom__update_activity('scorm', $scorm);
    if (!$ret['result'])
        return amvonetroom_XmlResponse::error($ret['error']);

    rebuild_course_cache($courseId);

    // finally, compose response

    $items = amvonetroom_XmlResponse::items();
    $items->element("activity", array(
        'type' => 'scorm',
        'name' => $scorm->name,
        'url'  => "$CFG->wwwroot/mod/scorm/view.php?id=$id"
    ));

    return $items;
}

function amvonetroom__create_activity($moduleName, $moduleData) {
    global $CFG, $DB;

    $ret = array('result' => false, 'id' => '', 'error' => '');

    require_once("$CFG->dirroot/course/lib.php");

    if (!course_allowed_module($moduleData->course, $moduleName)) {
        $ret['error'] = "Module $moduleName not allowed.";
        return $ret;
    }

    if (!$module = $DB->get_record('modules', array ('name' => $moduleName))) {
        $ret['error'] = "Module $moduleName not installed.";
        return $ret;
    }

    require_once("$CFG->dirroot/mod/$moduleName/lib.php");

    $cm = new stdClass();
    $cm->course = $moduleData->course;
    $cm->module = $module->id;
    $cm->instance = 0;
    $cm->added = time();

    try {
        $cmId = add_course_module($cm);
    } catch (dml_exception $e) {
        $ret['error'] = 'Could not add a new course module.';
        return $ret;
    }

    $moduleData->coursemodule = $cmId;
    try {
        $addFunc = "{$moduleName}_add_instance";
        $addFunc($moduleData);
    } catch (dml_exception $e) {
        $ret['error'] = "Add $moduleName instance failed.";
        return $ret;
    }

    $sec = new stdClass();
    $sec->course = $moduleData->course;
    $sec->section = amvonetroom__get_section();
    $sec->coursemodule = $cmId;

    try {
        $secId = add_mod_to_section($sec);
    } catch (dml_exception $e) {
        $ret['error'] = 'Could not add the new course module to section.';
        return $ret;
    }

    try {
        $DB->set_field("course_modules", "section", $secId, array("id" => $cmId));
    } catch (dml_exception $e) {
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

    require_once("$CFG->dirroot/lib/datalib.php");

    if (!$cm = get_coursemodule_from_instance($moduleName, $moduleData->id)) {
        $ret['error'] = "Course $moduleName module not found.";
        return $ret;
    }

    require_once("$CFG->dirroot/mod/$moduleName/lib.php");

    $moduleData->coursemodule = $cm->id;
    $moduleData->instance = $moduleData->id;
    try {
        $updateFunc = "{$moduleName}_update_instance";
        $updateFunc($moduleData);
    } catch (dml_exception $e) {
        $ret['error'] = "Update $moduleName instance failed.";
        return $ret;
    }

    $ret['result'] = true;
    $ret['id'] = $cm->id;

    return $ret;
}

function amvonetroom__get_section() {
    global $CFG, $ACCESS, $DB;

    require_once("$CFG->dirroot/lib/datalib.php");

    if (!$cm = get_coursemodule_from_instance('amvonetroom', $ACCESS->room->id))
        return 0;

    if (!$sec = $DB->get_record('course_sections', array('id' => $cm->section)))
        return 0;

    return $sec->section;
}

?>