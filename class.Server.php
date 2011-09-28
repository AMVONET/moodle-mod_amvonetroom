<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Version.php");

class amvonetroom_Server {
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
     * @throws amvonetroom_Exception
     */
    public static function getWorkerUrl ($serverUrl, $uid) {

        if (substr($serverUrl, -1) != "/") {
            $serverUrl .= "/";
        }
        $serverUrl .= "get_worker?sessionId=$uid";

        $req = new amvonetroom_HttpRequest("GET", $serverUrl);
        try {
            $req->send();
            $url = $req->getResponse();
        } catch (amvonetroom_Exception $e) {
            throw $e;
        }
        $req->close();

        return $url;
    }
}

?>