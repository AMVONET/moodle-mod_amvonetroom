<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Version.php");

class amvonetroom_Server {
    private static $error = NULL;

    /**
     * Combines servlet URL in form <serverUrl>/balancer_proxy/<servlet>
     *
     * @param $serverUrl balancer's entry point
     * @param $servlet servlet path
     * @return URL for a specified servlet via a balancer
     */
    public static function balancerProxy ($serverUrl, $servlet) {
        if (substr($serverUrl, -1) != "/") {
            $serverUrl .= "/";
        }

        return $serverUrl . "balancer_proxy/" . $servlet .
            "?protoVersion=" . amvonetroom_ProtoVersion::getCurrent();
    }

    /**
     * Combines servlet URL in form <serverUrl>/balancer_redirect/<servlet>
     *
     * @param $serverUrl balancer's entry point
     * @param $servlet servlet path
     * @return URL for a specified servlet via a balancer
     */
    public static function balancerRedirect ($serverUrl, $servlet) {
        if (substr($serverUrl, -1) != "/") {
            $serverUrl .= "/";
        }

        return $serverUrl . "balancer_redirect/" . $servlet .
            "?protoVersion=" . amvonetroom_ProtoVersion::getCurrent();
    }

    /**
     * Returns URL for worker serves specified session.
     *
     * @static
     * @param  $serverUrl balancer's entry point
     * @param  $uid session id
     * @return worker URL
     */
    public static function getWorkerUrl ($serverUrl, $uid) {
        self::clearError();

        if (substr($serverUrl, -1) != "/") {
            $serverUrl .= "/";
        }
        $serverUrl .= "get_worker?sessionId=$uid";

        $url = FALSE;
        $req = new amvonetroom_HttpRequest("GET", $serverUrl);
        if (!$req->send(NULL)) {
            self::$error = $req->getError();
        } else {
            $url = $req->getResponse();
        }
        $req->close();

        return $url;
    }

    public static function getError() {
        return self::$error;
    }

    public static function clearError() {
        self::$error = NULL;
    }
}

?>