<?php

require_once('class.Version.php');

header("Content-Type: text/plain");
header("Cache-Control: no-cache");
echo ProtoVersion::getCurrent();
?>