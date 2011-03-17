<?php
    require_once("../../config.php");
    $url = $CFG->wwwroot . "/course/mod.php?id=" . $_REQUEST["course"] . "&section=0&add=quiz";
    header("Location: $url");
?>