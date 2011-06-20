<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.HttpRequest.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Version.php");

define ("AMVONETROOM_PASSPORT_URL", "http://master.amvonet.com/amvonet/passport");
define ("AMVONETROOM_UPGRADE_URL", "http://master.amvonet.com/amvonet/upgrade");
define ("AMVONETROOM_PLUGIN_URL", "http://master.amvonet.com/amvonet/plugin");

define ("AMVONETROOM_STATUS_ACTIVE", "active");
define ("AMVONETROOM_STATUS_BLOCKED", "blocked");

define ("AMVONETROOM_TYPE_FREE", "free");
define ("AMVONETROOM_TYPE_TRIAL", "trial");
define ("AMVONETROOM_TYPE_COM", "com");

class amvonetroom_SchoolPassport {
    private static $instance = FALSE;
    private static $error = NULL;

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
        $k = get_record ('config_plugins', 'plugin', 'amvonetroom', 'name', 'school_key');
        return $k ? $k->value : '';
    }

    public static function setKey($key) {
        $k = get_record ('config_plugins', 'plugin', 'amvonetroom', 'name', 'school_key');
        if (!$k) {
            $k = new stdClass();
            $k->id = 0;
            $k->plugin = 'amvonetroom';
            $k->name = 'school_key';
            $k->value = $key;
            insert_record('config_plugins', $k);
        } else {
            $k->value = $key;
            update_record('config_plugins', $k);
        }
    }

    public static function get() {
        self::clearError();

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
                    if (!self::upgrade($key, $myProtoVersion, $myPluginVersion))
                        self::$instance = FALSE;
                }
            }
        }

        return self::$instance;
    }

    private static function loadFromDb() {
        $psp = get_record ("config_plugins", "plugin", "amvonetroom", "name", "school_passport");
        if (!$psp)
            return FALSE;

        return unserialize($psp->value);
    }

    private static function saveToDb($passport) {
        $psp = get_record ("config_plugins", "plugin", "amvonetroom", "name", "school_passport");
        if (!$psp) {
            $psp = new object();
            $psp->plugin = "amvonetroom";
            $psp->name = "school_passport";
            $psp->value = serialize($passport);

            insert_record("config_plugins", $psp);
        } else {
            $psp->value = serialize($passport);

            update_record("config_plugins", $psp);
        }
    }

    private static function loadFromXml($key) {
        if (empty($key)) {
            self::$error = get_string("error_key_not_defined", "amvonetroom");
            return FALSE;
        }

        $url = AMVONETROOM_PASSPORT_URL . '?key=' . urlencode($key) . '&protoVersion=' . amvonetroom_ProtoVersion::getCurrent();
        $req = new amvonetroom_HttpRequest("GET", $url);

        if ($req->send(NULL)) {
            $instance = new amvonetroom_SchoolPassport($req->getResponse());
            $instance->key = $key;
            if ($instance->expire) {
                // convert expiration time from relative to absolute
                $instance->expire = time() + $instance->expire;
                // put in db-cache
                self::saveToDb($instance);
            }
        } else {
            $instance = FALSE;
            self::parseError($req);
        }

        $req->close();

        return $instance;
    }

    private static function upgrade($key, $protoVersion, $pluginVersion) {
        $url = AMVONETROOM_UPGRADE_URL . '?key=' . urlencode($key) . '&protoVersion=' . $protoVersion . '&pluginVersion=' . $pluginVersion->toLongString();
        $req = new amvonetroom_HttpRequest("GET", $url);
        $req->send(NULL);
        $ret = self::parseError($req);
        $req->close();

        return $ret;
    }

    private static function parseError($req) {
        switch ($req->getStatusCode()) {
        case 200:
            self::clearError();
            return TRUE;
        case 400:
            self::$error = get_string("error_school_bad_request", "amvonetroom");
            return FALSE;
        case 403:
            self::$error = get_string("error_school_forbidden", "amvonetroom");
            return FALSE;
        case 404:
            self::$error = get_string("error_school_not_registered", "amvonetroom");
            return FALSE;
        case 460:
            self::$error = get_string("error_version_incompatible", "amvonetroom");
            return FALSE;
        case 461:
            self::$error = get_string("error_version_too_old", "amvonetroom");
            return FALSE;
        case 503:
            self::$error = get_string("error_unavailable", "amvonetroom");
            return FALSE;
        default:
            self::$error = $req->getError();
            return FALSE;
        }
    }

    public static function getError () {
        return self::$error;
    }

    public function clearError() {
        self::$error = NULL;
    }
}

?>