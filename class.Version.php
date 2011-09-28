<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Exception.php");

define ("AMVONETROOM_PROTOCOL_VERSION", "2.1");

class amvonetroom_Version {

    private $major = 0;
    private $minor = 0;
    private $build = 0;
    private $revision = 0;

    public function __construct ($major, $minor = 0) {
        if (!$major)
            return;
        if (is_string($major)) {
            $v = split('\.', $major);
            if (count($v) > 0)
                $this->major = intval($v[0]);
            if (count($v) > 1)
                $this->minor = intval($v[1]);
            if (count($v) > 2)
                $this->build = intval($v[2]);
            if (count($v) > 3)
                $this->revision = intval($v[3]);
        }
        else {
            $this->major = $major;
            $this->minor = $minor;
        }
    }

    public function getMajor() {
        return $this->major;
    }

    public function getMinor() {
        return $this->minor;
    }

    public function getBuild() {
        return $this->build;
    }

    public function getRevision() {
        return $this->revision;
    }

    public function __toString() {
        return $this->toShortString();
    }

    public function toShortString() {
        return $this->major . '.' . $this->minor;
    }

    public function toLongString() {
        return $this->major . '.' . $this->minor . '.' . $this->build . ($this->revision != 0 ? '.' . $this->revision : '');
    }

    public function compare($other) {
        $cmp = $this->major - $other->getMajor();
        if ($cmp != 0)
            return $cmp;

        $cmp = $this->minor - $other->getMinor();
        if ($cmp != 0)
            return $cmp;

        $cmp = $this->build - $other->getBuild();
        if ($cmp != 0)
            return $cmp;

        return $this->revision - $other->getRevision();
    }

    /**
     * Detect is a current version compatible with a given one.
     *
     * @param  $requestVersion is a version of a request from a client
     * @return int
     *    Let this == 'n1.m1' and other == 'n2.m2'
     *    Then returns
     *          1) true (compatible),      if n1 == n2 && m1 >= m2
     *          2) false (not compatible), if n1 != n2 || (n1 == n2 && m1 < m2)
     */
    public function compatible($other) {
        $cmp = $this->major - $other->getMajor();
        if ($cmp != 0)
            return FALSE;
        return $this->minor - $other->getMinor() >= 0;
    }
}

class amvonetroom_ProtoVersion {

    /**
     * Returns a current protocol version supported be plugin.
     */
    public static function getCurrent() {
        return new amvonetroom_Version(AMVONETROOM_PROTOCOL_VERSION);
    }

    public static function getDefault() {
        return new amvonetroom_Version(2, 0);
    }

    /**
     * Returns a protocol version of the current HTTP request.
     */
    public static function getFromRequest() {
        // there is no suitable PARAM_XXX flag in Moodle to clean this parameter but
        // don't worry it will be split and parsed as two int and it's never used in raw form
        $param = @$_GET['protoVersion'];
        if ($param && !empty($param))
            return new amvonetroom_Version($param);

        return amvonetroom_ProtoVersion::getDefault();
    }

    /**
     * Check a compatibility of a protocol version of the current request.
     */
    public static function checkRequest($ver = null) {
        $requestVersion = empty($ver) ? amvonetroom_ProtoVersion::getFromRequest() : new amvonetroom_Version($ver);
        $currentVersion = amvonetroom_ProtoVersion::getCurrent();

        $v20 = new amvonetroom_Version(2,0);
        if (!$currentVersion->compatible($requestVersion) || $requestVersion->compare($v20) <= 0) {
            if ($currentVersion->compare($requestVersion) < 0) {
                amvonetroom_die(460, 'Version Incompatible');
            } else {
                amvonetroom_die(461, 'Version Too Old');
            }
        }
    }
}

class amvonetroom_PluginVersion {

    /**
     * Returns a current version of the plugin.
     */
    public static function getCurrent() {
        global $CFG;

        if ($data = file_get_contents("$CFG->dirroot/mod/amvonetroom/revision"))
            return new amvonetroom_Version($data);
        else
            return new amvonetroom_Version(0);
    }
}
?>