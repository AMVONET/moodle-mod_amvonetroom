<?php

require_once("class.HttpRequest.php");
require_once("class.Version.php");

define ("PASSPORT_URL", "http://master.amvonet.com/amvonet/passport");
define ("UPGRADE_URL", "http://master.amvonet.com/amvonet/upgrade");
define ("PLUGIN_URL", "http://master.amvonet.com/amvonet/plugin");

define ("STATUS_ACTIVE", "active");
define ("STATUS_BLOCKED", "blocked");

define ("TYPE_FREE", "free");
define ("TYPE_TRIAL", "trial");
define ("TYPE_COM", "com");

class SchoolPassport {
    private static $instance = FALSE;
    private static $error = NULL;

    private $domain;
    private $expire;
    private $protoVersion;
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
        $this->protoVersion = (string)$passport->protoVersion;
        $this->entrypoint = (string)$passport->entrypoint;
        $this->status = (string)$passport->status;
        $this->type = (string)$passport->type;
        $this->version = (string)$passport->version;
        $this->expirationDate = (string)$passport->limits->expiration;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function getProtoVersion() {
        return $this->protoVersion;
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
        $k = get_record ("config_plugins", "plugin", "amvonetroom", "name", "school_key");
        return $k ? $k->value : "";
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
                $storedVersion = new Version(self::$instance->getProtoVersion());
                $myVersion = ProtoVersion::getCurrent();
                if ($myVersion->compare($storedVersion) != 0) {
                    if (!self::upgrade($key, $myVersion))
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

        $url = PASSPORT_URL . '?key=' . urlencode($key) . '&protoVersion=' . ProtoVersion::getCurrent();
        $req = new HttpRequest("GET", $url);

        if ($req->send(NULL)) {
            $instance = new SchoolPassport($req->getResponse());
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

    private static function upgrade($key, $version) {
        $url = UPGRADE_URL . '?key=' . urlencode($key) . '&protoVersion=' . $version;
        $req = new HttpRequest("GET", $url);
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