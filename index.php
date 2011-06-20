<?php // $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page lists all the instances of vcrsession in a particular course
 *
 * @author 
 * @version $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
 **/

/// Replace vcrsession with the name of your module

    require_once("../../config.php");
    require_once("lib.php");
	$id = required_param('id', PARAM_INT);   // course
 

    if (! $course = get_record("course", "id", $id)) 
        error("Course ID is incorrect");
   

    require_course_login($course);

    add_to_log($course->id, "amvonetroom", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strrooms = get_string("modulenameplural", "amvonetroom");
    $strlive = get_string("modulename", "amvonetroom");


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strrooms, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation($navlinks);

    print_header_simple($strrooms, '', $navigation, '', '', true, '', navmenu($course)); 

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");
	
    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left");
    }

  	$currentsection = "";
	$modinfo = get_fast_modinfo($course);
	foreach ($modinfo->instances['amvonetroom'] as $cm) {
	    if (!$cm->uservisible) {
	        continue;
	    }
		//Show dimmed if the mod is hidden
	    $class = $cm->visible ? '' : 'class="dimmed"';

	    $link = "<a $class href=\"view.php?id=$cm->id\">".format_string($cm->name)."</a>";

		$printsection = "";
        if ($cm->sectionnum !== $currentsection) {
            if ($cm->sectionnum) {
                $printsection = $cm->sectionnum;
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $cm->sectionnum;
        }

        $cm = get_record("course_modules", "instance", $room->id);
        
        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($printsection, $link);
        } else {
        	$table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>