<?php

define ("AMVONET_PROTOCOL_VERSION", "2.0");

class Version {

    private $major = 2;
    private $minor = 0;

    public function __construct ($major, $minor = 0) {
        if (!$major)
            return;
        if (is_string($major)) {
            $v = split("\\.", $major);
            if (count($v) > 0)
                $this->major = intval($v[0]);
            if (count($v) > 1)
                $this->minor = intval($v[1]);
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

    public function __toString() {
        return $this->major . "." . $this->minor;
    }

    public function compare($other) {
        $cmp = $this->major - $other->getMajor();
        if ($cmp != 0)
            return $cmp;
        return $this->minor - $other->getMinor();
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

class ProtoVersion {

    /**
     * Returns a current version of the plugin.
     */
    public static function getCurrent() {
        return new Version(AMVONET_PROTOCOL_VERSION);
    }

    public static function getDefault() {
        return new Version(2, 0);
    }

    /**
     * Returns a version of the current HTTP request.
     */
    public static function getFromRequest() {
        $param = @$_GET["protoVersion"];
        if ($param && !empty($param))
            return new Version($param);

        return ProtoVersion::getDefault();
    }

    /**
     * Check a compatibility of a protocol version of the current request.
     */
    public static function checkRequest($ver = null) {
        $requestVersion = empty($ver) ? ProtoVersion::getFromRequest() : new Version($ver);
        $currentVersion = ProtoVersion::getCurrent();

        if (!$currentVersion->compatible($requestVersion)) {
            if ($currentVersion->compare($requestVersion) < 0) {
                header ("HTTP/1.1 460 Version Incompatible");
                die("460 Version Incompatible");
            } else {
                header ("HTTP/1.1 461 Version Too Old");
                die("461 Version Too Old");
            }
        }
    }
}
?>