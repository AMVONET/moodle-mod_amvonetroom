<?php
require_once('../../config.php');
require_once('class.Version.php');

header("Content-Type: text/plain");
header("Cache-Control: no-cache");

$v20 = new amvonetroom_Version(2,0);
$proto = amvonetroom_ProtoVersion::getFromRequest();

if ($proto->compare($v20) <= 0) {
    echo amvonetroom_ProtoVersion::getCurrent();
} else {
    global $CFG;

    $params = array(
        'protoVersion'  => amvonetroom_ProtoVersion::getCurrent(),
        'pluginVersion' => amvonetroom_PluginVersion::getCurrent()->toLongString(),
        'lmsType'       => 'Moodle',
        'lmsVersion'    => $CFG->release
    );

    foreach($params as $key => $value) {
        echo "$key=$value\n";
    }
}
?>