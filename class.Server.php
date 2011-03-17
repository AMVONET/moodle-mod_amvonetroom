<?php

require_once("class.Version.php");

class Server {
    private static $error = NULL;

    public static function combineUrl($url1, $url2) {
        if (strpos($url2, "://") > 0)
            return $url2;

        if (substr($url1, -1) != "/") {
            $url1 .= "/";
        }

        return $url1 . $url2;
    }

    /**
     * Combines servlet URL in form <serverUrl>/balancer_redirect/<servlet>?sessionId=<uid> 
     *
     * @param $serverUrl balancer's entry point
     * @param $servlet servlet path
     * @param $uid session id
     * @return URL for a specified servlet via a balancer
     */
    public static function combineServletUrl ($serverUrl, $servlet,  $uid) {
        if (substr($serverUrl, -1) != "/") {
            $serverUrl .= "/";
        }

        if ($uid !== null) {
            return $serverUrl . "balancer_redirect/" . $servlet .
                    "?sessionId=" . $uid .
                    "&protoVersion=" . ProtoVersion::getCurrent()->__toString();
        }

        return $serverUrl . $servlet .
                "?protoVersion=" . ProtoVersion::getCurrent()->__toString();
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
        $req = new HttpRequest("GET", $serverUrl);
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