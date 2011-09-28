<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Exception.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.HttpRequest.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Version.php");

define ("AMVONETROOM_PASSPORT_URL_0", "http://master.amvonet.com/amvonet/passport");
define ("AMVONETROOM_PASSPORT_URL_1", "http://master2.amvonet.com/amvonet/passport");
define ("AMVONETROOM_UPGRADE_URL", "http://master.amvonet.com/amvonet/upgrade");
define ("AMVONETROOM_PLUGIN_URL", "http://master.amvonet.com/amvonet/plugin");

define ("AMVONETROOM_STATUS_ACTIVE", "active");
define ("AMVONETROOM_STATUS_BLOCKED", "blocked");

define ("AMVONETROOM_TYPE_FREE", "free");
define ("AMVONETROOM_TYPE_TRIAL", "trial");
define ("AMVONETROOM_TYPE_COM", "com");

class amvonetroom_SchoolPassport {
    private static $instance = FALSE;

    private $domain;
    private $expire;
    private $protoVersion;
    private $pluginVersion;
    private $entrypoint;
    private $status;
    private $type;
    private $version;
    private $key;
    private $expirationDate;

    private function __construct ($xml) {
        $passport = new SimpleXMLElement($xml);
        $this->domain = (string)$passport['domain'];
        $this->expire = (int)$passport['expire'];
        $this->entrypoint = (string)$passport->entrypoint;
        $this->status = (string)$passport->status;
        $this->type = (string)$passport->type;
        $this->version = (string)$passport->version;
        $this->protoVersion = (string)$passport->lms->protoVersion;
        $this->pluginVersion = (string)$passport->lms->pluginVersion;
        $this->expirationDate = (string)$passport->limits->expiration;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function getProtoVersion() {
        return $this->protoVersion;
    }

    public function getPluginVersion() {
        return $this->pluginVersion;
    }

    public function getEntryPoint() {
        return $this->entrypoint;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getType() {
        return $this->type;
    }

    public function getVersion() {
        return $this->version;
    }
    
    public function getExpirationDate() {
        return $this->expirationDate;
    }

    public static function getKey() {
        global $DB;

        $k = $DB->get_record ('config_plugins', array('plugin' => 'amvonetroom',
                                                      'name'   => 'school_key'));
        return $k ? $k->value : '';
    }

    public static function setKey($key) {
        global $DB;

        $k = $DB->get_record ('config_plugins', array('plugin' => 'amvonetroom',
                                                      'name'   => 'school_key'));
        if (!$k) {
            $k = new stdClass();
            $k->id = 0;
            $k->plugin = 'amvonetroom';
            $k->name = 'school_key';
            $k->value = $key;
            $DB->insert_record('config_plugins', $k);
        } else {
            $k->value = $key;
            $DB->update_record('config_plugins', $k);
        }
    }

    /**
     * @static
     * Get a cached passport or request it from a server.
     *
     * @return amvonetroom_SchoolPassport
     * @throws dml_exception, amvonetroom_Exception
     */
    public static function get() {

        // first, try to load from db-cache
        if (!self::$instance) {
            self::$instance = self::loadFromDb();
        }

        if (self::$instance) {
            $exp = self::$instance->expire;
            $key = self::$instance->key;
            if (($exp && (int)$exp < time()) || $key != self::getKey()) {
                self::$instance = FALSE;
            }
        }

        // then, try to get passport from master
        if (!self::$instance) {
            $key = self::getKey();
            self::$instance = self::loadFromXml($key);
            if (self::$instance) {
                // check version and upgrade if necessary
                $storedProtoVersion = new amvonetroom_Version(self::$instance->getProtoVersion());
                $storedPluginVersion = new amvonetroom_Version(self::$instance->getPluginVersion());

                $myProtoVersion = amvonetroom_ProtoVersion::getCurrent();
                $myPluginVersion = amvonetroom_PluginVersion::getCurrent();

                if (($myProtoVersion->compare($storedProtoVersion) != 0) ||
                    ($myPluginVersion->compare($storedPluginVersion) != 0)) {
                    try {
                        self::upgrade($key, $myProtoVersion, $myPluginVersion);
                    } catch (Exception $e) {
                        self::$instance = FALSE;
                        throw $e;
                    }
                }
            }
        }

        return self::$instance;
    }

    private static function loadFromDb() {
        global $DB;

        $psp = $DB->get_record("config_plugins", array("plugin" => "amvonetroom",
                                                       "name"   => "school_passport"));
        if (!$psp)
            return FALSE;

        return unserialize($psp->value);
    }

    private static function saveToDb($passport) {
        global $DB;

        $psp = $DB->get_record("config_plugins", array("plugin" => "amvonetroom",
                                                       "name"   => "school_passport"));
        if (!$psp) {
            $psp = new object();
            $psp->plugin = "amvonetroom";
            $psp->name = "school_passport";
            $psp->value = serialize($passport);

            $DB->insert_record("config_plugins", $psp);
        } else {
            $psp->value = serialize($passport);

            $DB->update_record("config_plugins", $psp);
        }
    }

    private static function loadFromXml($key) {
        if (empty($key))
            amvonetroom_errorcode("error_key_not_defined");

        $result = self::requestPassport($key);

        $instance = new amvonetroom_SchoolPassport($result);
        $instance->key = $key;
        if ($instance->expire) {
            // convert expiration time from relative to absolute
            $instance->expire = time() + $instance->expire;
            // put in db-cache
            self::saveToDb($instance);
        }
        
        return $instance;
    }

    private static function requestPassport($key) {
        $query = '?key=' . urlencode($key) . '&protoVersion=' . amvonetroom_ProtoVersion::getCurrent();
        $url = AMVONETROOM_PASSPORT_URL_0 . $query;

        for ($index = 1; ; $index++) {
            $req = new amvonetroom_HttpRequest("GET", $url);

            try {
                $req->send();
                $resp = $req->getResponse();
                $req->close();
                return $resp;
            }
            catch (amvonetroom_Exception $e) {
            }

            $error = self::getError($req);
            $errno = $req->getErrno();
            $status = $req->getStatusCode();
            $req->close();

            // break on illegal errno or status
            if (!(
                    // see errno values on http://php.net/manual/en/function.curl-errno.php
                    $errno == 6  /*CURLE_COULDNT_RESOLVE_HOST*/ ||
                    $errno == 7  /*CURLE_COULDNT_CONNECT*/ ||
                    $errno == 28 /*CURLE_OPERATION_TIMEDOUT*/ ||
                            
                    $status == 408 /*Request Timeout*/ ||
                    $status >= 500))
                amvonetroom_error($error);

            // break if there is no reserve passport url
            if (!defined("AMVONETROOM_PASSPORT_URL_" . $index))
                amvonetroom_error($error);

            $url = constant("AMVONETROOM_PASSPORT_URL_" . $index) . $query;
        }
    }

    private static function upgrade($key, $protoVersion, $pluginVersion) {
        $url = AMVONETROOM_UPGRADE_URL .
                '?key=' . urlencode($key) .
                '&protoVersion=' . $protoVersion .
                '&pluginVersion=' . $pluginVersion->toLongString();

        $req = new amvonetroom_HttpRequest("GET", $url);
        try {
            $req->send();
        } catch (amvonetroom_Exception $e) {
            amvonetroom_error(self::getError($req));
        }
        $req->close();
    }

    private static function getError($req) {
        switch ($req->getStatusCode()) {
        case 400:
            return get_string("error_school_bad_request", "amvonetroom");
        case 403:
            return get_string("error_school_forbidden", "amvonetroom");
        case 404:
            return get_string("error_school_not_registered", "amvonetroom");
        case 460:
            return get_string("error_version_incompatible", "amvonetroom");
        case 461:
            return get_string("error_version_too_old", "amvonetroom");
        case 503:
            return get_string("error_unavailable", "amvonetroom");
        default:
            return $req->getError();
        }
    }
}

?>