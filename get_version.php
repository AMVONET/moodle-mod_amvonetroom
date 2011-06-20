<?php
require_once('../../config.php');
require_once('class.Version.php');

$v20 = new amvonetroom_Version(2,0);
$proto = amvonetroom_ProtoVersion::getFromRequest();

header("Content-Type: text/plain");
header("Cache-Control: no-cache");
if ($proto->compare($v20) <= 0) {
    echo amvonetroom_ProtoVersion::getCurrent();
} else {
    global $CFG;
    
    echo 'protoVersion=' . amvonetroom_ProtoVersion::getCurrent() . "\n";
    echo 'pluginVersion=' . amvonetroom_PluginVersion::getCurrent()->toLongString() . "\n";
    echo "lmsType=Moodle\n";
    echo 'lmsVersion=' . $CFG->release . "\n";
}
?>