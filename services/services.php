<?php
/**
 * Created by IntelliJ IDEA.
 * User: root
 * Date: Oct 21, 2010
 *
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
require_once($CFG->libdir . '/gradelib.php');
require_once("../class.Version.php");
require_once("../class.User.php");
require_once("../lib.php");

$args = array();
$token = null;
$protoVersion = null;
$service = @$_GET['service'];

if (!empty($service)) {
    // process as GET request
    foreach ($_GET as $key => $arg) {
        switch($key) {
        case 'service' :
            break;
        case 'token' :
            $token = $arg;
            break;
        case 'protoVersion' :
            $protoVersion = $arg;
            break;
        default :
            $args[] = $arg;
            break;
        }
    }
} else {
    $service = @$_POST['service'];
    if (empty($service)) {
        header("HTTP/1.1 400 Service not specified");
        die();
    }

    $xml = null;
    try {
        $xml = new SimpleXMLElement($service);
    } catch (Exception $e) {
    }
    if (!$xml) {
        header("HTTP/1.1 400 Input is invalid");
        die();
    }

    $token = (string)$xml->token;
    $protoVersion = (string)$xml->protoVersion;
    $service = (string)$xml->name;
    if (empty($service)) {
        header("HTTP/1.1 400 Service name not specified");
        die();
    }

    $xmlArg = $xml->arg;
    if ($xmlArg) {
        foreach ($xmlArg as $arg) {
            if (count($arg) == 0)
                $args[] = (string)$arg;
            else
                $args[] = $arg;
        }
    }
}

//Authentication
if (!$token || !User::getByToken($token)) {
    header("HTTP/1.1 401 Unauthorized");
    die();
}

//Version validation
if (empty($protoVersion))
    $protoVersion="2.0";
ProtoVersion::checkRequest($protoVersion);

if (!function_exists('amvonetservice_' . $service)) {
    header("HTTP/1.1 404 Service not found");
    die();
}

//Return XML to response and die process
header("Content-Type: text/xml;charset=UTF-8");
echo call_user_func_array('amvonetservice_' . $service, $args);
die();

/**
 * Create error XML
 * @param  $msg
 * @return XML string
 */
function __error($msg) {
    $doc = new DOMDocument ('1.0', 'UTF-8');
    $error_node = $doc->createElement("error");
    $doc->appendChild($error_node);
    $cdata_error = $doc->createCDATASection($msg);
    $error_node->appendChild($cdata_error);
    return $doc->saveXML();
}

/**
 * Create success XML
 * @param  $msg
 * @return XML string
 */
function __ok($msg) {
    $doc = new DOMDocument ('1.0', 'UTF-8');
    $error_node = $doc->createElement("ok");
    $doc->appendChild($error_node);
    $cdata_error = $doc->createCDATASection($msg);
    $error_node->appendChild($cdata_error);
    return $doc->saveXML();
}

/**
 * Create <items> root node
 * @return XMLNode
 */
function __items() {
    $doc = new DOMDocument ('1.0', 'UTF-8');
    $items_node = $doc->createElement("items");
    $doc->appendChild($items_node);
    return $items_node;
}

/**
 * Utility function for create element with text value.
 * @param  $parent
 * @param  $name
 * @param  array $attrs
 * @return $node
 */
function __element($parent, $name, $attrs) {
    $node = $parent->ownerDocument->createElement($name);
    foreach ($attrs as $attrName => $attrValue) {
        $attrNode = $parent->ownerDocument->createAttribute($attrName);
        $attrNode->value = $attrValue;
        $node->appendChild($attrNode);
    }
    $parent->appendChild($node);
    return $node;
}

/**
 * Utility function for create element with text value.
 * @param  $parent
 * @param  $name
 * @param  $value
 * @return $node
 */
function __text($parent, $name, $value) {
    $node = $parent->ownerDocument->createElement($name);
    $text = $parent->ownerDocument->createTextNode($value);
    $node->appendChild($text);
    $parent->appendChild($node);
    return $node;
}

/**
 * Utility function for create element with CDATA value.
 * @param  $parent
 * @param  $name
 * @param  $value
 * @return $node
 */
function __cdata($parent, $name, $value) {
    $node = $parent->ownerDocument->createElement($name);
    $text = $parent->ownerDocument->createCDATASection($value);
    $node->appendChild($text);
    $parent->appendChild($node);
    return $node;
}

/**
 * Converts DOM document to string XML.
 *  
 * @param  $node
 * @return string
 */
function __asXML($node) {
    return $node->ownerDocument->saveXML();
}

/**
 * Utility function for create node
 * @param  $doc
 * @param  $name
 * @param  $value
 * @param bool $cdata
 * @return $node
 */
function __add_node($doc, $name, $value, $cdata = false) {
    $node = $doc->createElement($name);
    if ($cdata) {
        $text_node = $doc->createCDATASection($value);
    } else {
        $text_node = $doc->createTextNode($value);
    }
    $node->appendChild($text_node);
    return $node;
}

/**
 * Services (should be stats with 'amvonetservice_')
 *
 */

/**
 * Get all quizes for course
 * @param  $course_id
 * @return xml
 * for example:
 *   <?xml version="1.0" encoding="UTF-8"?>
 *   <items>
 *       <item>
 *           <id>1</id>
 *           <name><![CDATA[test quiz]]></name>
 *           <intro><![CDATA[test quiz ]]></intro>
 *           <question_count>0</question_count>
 *           <url><![CDATA[http://demo.amvonet.com/amvonet-moodle/mod/quiz/attempt.php?id=167]]></url>
 *       </item>
 *   </items>
 */

function amvonetservice_get_quizes($course_id) {

    global $CFG;

    $quizes = get_records("quiz", "course", $course_id);

    $doc = new DOMDocument ('1.0', 'UTF-8');
    $items_node = $doc->createElement("items");
    $doc->appendChild($items_node);

    if ($quizes) {
        foreach ($quizes as $key => $quiz) {

            $item_node = $doc->createElement("item");
            $items_node->appendChild($item_node);

            $cm = get_coursemodule_from_instance('quiz', $quiz->id);

            $question_count = count_records("quiz_question_instances", "quiz", $quiz->id);

            $node = __add_node($doc, "id", $quiz->id);
            $item_node->appendChild($node);

            $node = __add_node($doc, "name", $quiz->name, true);
            $item_node->appendChild($node);

            $node = __add_node($doc, "intro", $quiz->intro, true);
            $item_node->appendChild($node);

            $node = __add_node($doc, "question_count", $question_count);
            $item_node->appendChild($node);

            $node = __add_node($doc, "url", $CFG->wwwroot . "/mod/quiz/attempt.php?id=" . $cm->id, true);
            $item_node->appendChild($node);

        }
    }

    return $doc->saveXML();
}


/**
 * Get all grades for the course
 * @param  $course_id
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
function amvonetservice_get_grades($course_id) {

    $grade_items = grade_item::fetch_all(array('courseid' => $course_id));

    $doc = new DOMDocument ('1.0', 'UTF-8');
    $items_node = $doc->createElement("items");
    $doc->appendChild($items_node);

    if ($grade_items) {
        foreach ($grade_items as $key => $item) {
            if ($item->itemnumber != null) {

                $item_node = $doc->createElement("item");
                $items_node->appendChild($item_node);

                $node = __add_node($doc, "id", $item->id);
                $item_node->appendChild($node);

                $node = __add_node($doc, "name", $item->itemname, true);
                $item_node->appendChild($node);

                $node = __add_node($doc, "number", $item->itemnumber);
                $item_node->appendChild($node);

            }
        }
    }

    return $doc->saveXML();
}

/**
 * Add grade
 * @param  $course_id
 * @param  $room_id
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
    global $CFG;

    if (!function_exists('grade_update'))
    { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $room = get_record('amvonetroom', 'uid', $room_id);

    if (!$room) {
        return __error("Amvonetroom $room_id not found");
    }

    if (0 == grade_update('mod/amvonetroom', $course_id, 'mod', 'amvonetroom', $room->id, $grade_number, NULL,
        array(100, 'itemname' => $grade_name))) {
        return __ok("The grade has been added");
    }
    else {
        return __error("Can't add grade");
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
    global $CFG;

    if (!function_exists('grade_update')) 
    { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $grade_item = grade_item::fetch(array('id' => $grade_id));

    if (!$grade_item) {
        return __error("Grade $grade_id not found");
    }

    if ($grade_item->delete()) {
        return __ok("The grade has been deleted");
    } else {
        return __error("Can't delete grade");
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

    $grade_item = grade_item::fetch(array('id' => $grade_id));

    if (!$grade_item) {
        return __error("Grade $grade_id not found");
    }

    $grade_item->itemname = $grade_name;
    $grade_item->update();
    $user_grades = (array) $user_grades;

    if ($grade_item) {
        foreach ($user_grades["item"] as $grade)
        {
            $grade = (array) $grade;
            
            $grade_item->update_final_grade($grade["user_id"], $grade["user_grade"] != ""
                    ?
                    (float) $grade["user_grade"]
                    :
                    null, null, $grade["user_grade_comment"]);
        }
    }
    return __ok("The user's grades has been saved");
}

/**
 * Get grades for users
 * @param  $room_id
 * @param  $grade_id
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <item>
 *         <user_id>4</user_id>
 *         <user_name><![CDATA[Vasya Pupkin]]></user_name>
 *         <user_grade_id></user_grade_id>
 *         <user_grade></user_grade>
 *         <user_grade_comment><![CDATA[]]></user_grade_comment>
 *     </item>
 *     <item>
 *         <user_id>3</user_id>
 *         <user_name><![CDATA[Alexey Batareykin]]></user_name>
 *         <user_grade_id></user_grade_id>
 *         <user_grade></user_grade>
 *         <user_grade_comment><![CDATA[]]></user_grade_comment>
 *     </item>
 * </items>
 *
 */
function amvonetservice_get_grade_users($room_id, $grade_id) {

    $doc = new DOMDocument ('1.0', 'UTF-8');
    $items_node = $doc->createElement("items");
    $doc->appendChild($items_node);

    $room = get_record('amvonetroom', 'uid', $room_id);

    if (!$room) {
        return __error("Amvonetroom $room_id not found");
    }

    $course = get_record('course', 'id', $room->course);

    if (!$course) {
        return __error("Course has been deleted");
    }

    $cm = get_coursemodule_from_instance('amvonetroom', $room->id, $course->id);
    $users = amvonetroom_get_users($cm->id, array("student"));

    $grade_item = grade_item::fetch(array('id' => $grade_id));

    if (!$grade_item) {
        return __error("Grade $grade_id not found");
    }

    if ($users) {
        foreach ($users as $user_id => $user)
        {
            $grade_grades = grade_grade::fetch_users_grades($grade_item, array($user_id));

            if ($grade_grades) {
                foreach ($grade_grades as $grade)
                {

                    $item_node = $doc->createElement("item");
                    $items_node->appendChild($item_node);

                    $node = __add_node($doc, "user_id", $user_id);
                    $item_node->appendChild($node);

                    $node = __add_node($doc, "user_name", $user->firstname . ' ' . $user->lastname, true);
                    $item_node->appendChild($node);

                    $node = __add_node($doc, "user_grade_id", $grade->id);
                    $item_node->appendChild($node);

                    $node = __add_node($doc, "user_grade", $grade->finalgrade);
                    $item_node->appendChild($node);

                    $node = __add_node($doc, "user_grade_comment", $grade->feedback, true);
                    $item_node->appendChild($node);

                }
            }
        }
    }
    return $doc->saveXML();
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
    $path = str_replace("\\", "/", $path);
    
    $first = true;
    foreach (explode("/", $path) as $part) {
        if ($part == "." || $part == "..")
            return __error("Invalid path.");
        if (empty($part))
            continue;
        if ($first) {
            $first = false;
            if (!is_numeric($part))
                return __error("Course must be integer.");
        }
    }
    if ($first)
        return __error("Course must be specified.");

    $items = __items();
    __list_dir($items, $path, $depth);
    return __asXML($items);
}

/**
 * Helper method to list directory.
 *
 * @param  $parent
 * @param  $path
 * @return none
 */
function __list_dir($parent, $path, $depth = -1) {
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
            $node = __element($parent, "dir", array(
                'id' => $fullname,
                'name' => $file
            ));
            __list_dir($node, $fullname, $depth > 0 ? --$depth : -1);
        } else {
            $furl  = $CFG->wwwroot . '/file.php/' . $fullname;
            $fpath = $fullpath . '/' . $file;
	        $fsize = filesize($fpath);
	        $ftime = date ("Y-m-d H:i:s", filemtime($fpath));
            __element($parent, "file", array(
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
 * @param  $course
 * @return XML
 * for example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <items>
 *     <activity type="forum" name="News forum" url="http://test.amvonet.com/amvonet-moodle/mod/forum/view.php?id=6"/>
 * </items>
 *
 */
function amvonetservice_get_activities($course) {
    global $CFG;

    if (empty($course))
        return __error("Course not defined.");

    $items = __items();

    foreach (get_course_mods($course)  as $mod) {
        $instance = get_record($mod->modname, 'id', $mod->instance);
        if (!$instance)
            continue;
        
        $url = $CFG->wwwroot . '/mod/' . $mod->modname . '/view.php?id=' . $mod->id;
        __element($items, "activity", array(
            'type' => $mod->modname,
            'name' => $instance->name,
            'url' => $url
        ));
    }

    return __asXML($items);
}

?>