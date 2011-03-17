<?php

define ("ROLE_STUDENT", "st");
define ("ROLE_MODERATOR", "mu");
define ("ROLE_GUEST_MODERATOR", "gmu");

define ("USER_TIME_CACHE", 60 * 60 * 24);
define ("AMVONET_DATA_DIR", "moddata/amvonetroom");

class User {
    private static $error = NULL;
    private static $token = NULL;

    /**
     * Registers Moodle's user as AMVONET user.
     * Creates temporary file with unique name with user information.
     *
     * @param $user (array) - Moodle's user
     * @param $role - user role
     * @return $token
     */
    public static function registerUser($user, $role) {
        global $CFG;
        self::clearError();

        // first, clean expired tokens
        self::clean();

        // second, check the token already presented in cookie
        $token = self::getToken();
        if ($token) {
            $info = self::getByToken($token);

            if ($info !== FALSE) {
                $xml = new SimpleXMLElement($info);

                // is it the same user with the same role?
                if ($xml['lmsId'] == $user->id && $xml['role'] == $role)
                    return $token;
            }
        }

        // third, if no token in cookie or its expired then generate new one
        $token = uniqid();

        make_upload_directory(AMVONET_DATA_DIR);  //Make sure the directory exists the first time we register for a site

        $file = $CFG->dataroot . '/' . AMVONET_DATA_DIR . '/' . $token . ".usr";
        $fp = fopen($file, "w+");
        if( !$fp ) {
            self::$error = "Can't create session token file: $file";
            return FALSE;
        } else {
            $info = self::getAsXml($user, $role);
            fwrite ($fp, $info);
            fclose($fp);
        }

        self::setToken($token);

        return $token;
    }

    /**
     * Reads info of user from file and return it as string and FALSE if not found.
     *
     * @param $token user's token
     * @return user's info
     */
    public static function getByToken ($token) {
        global $CFG;

        self::clearError();

        $file = $CFG->dataroot . '/' . AMVONET_DATA_DIR . '/'. $token . ".usr";

        if (!file_exists($file)) {
            return FALSE;
        }

        $fp = fopen ($file, "r+");
        $contents = fread($fp, filesize($file));
        fclose($fp);

        touch($file);

        return $contents;
    }

    private static function clean() {
        global $CFG;

        $directory = $CFG->dataroot . '/' . AMVONET_DATA_DIR;

        $handle = opendir($directory);
        while ($datei = readdir($handle))
        {
            if (($datei != '.') && ($datei != '..'))
            {
                $file = $directory . '/' . $datei;
                if (!is_dir($file) && strrpos($file, ".usr") === strlen($file)-strlen(".usr")) {
                    //cleaning
                    if (time() - filemtime($file) > USER_TIME_CACHE) {
                        unlink ($file);
                    }
                }
            }
        }
        closedir($handle);
    }

    /**
     *
     * @return string error or NULL
     */
    public static function getError () {
        return self::$error;
    }

    public static function clearError() {
        self::$error = NULL;
    }

    public static function getToken() {
        if (empty(self::$token)) {
            self::$token = @$_COOKIE["token"];
        }
        return self::$token;
    }

    public static function setToken($token) {
        setcookie("token", $token);
        self::$token = $token;
    }

    /**
     * Formats and returns user information for callback.
     * Used to return user info to media-server.
     *
     * @param $user - Moodle's user
     * @param $role - user role
     * @return formatted user info as xml
     */
    public static function getAsXml($user, $role = null, $useNamespace = false) {
        global $CFG;

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><user' . ($useNamespace ? ' xmlns="http://www.amvonet.com/schema/user"' : '') . '/>');
        if (!empty($role))
            $xml->addAttribute("role", $role);
        $xml->addAttribute("login", $user->username);
        $xml->addAttribute("firstName", $user->firstname);
        $xml->addAttribute("lastName", $user->lastname);
        $xml->addAttribute("lmsId", $user->id);
        $xml->addAttribute("email", $user->email);
        $urlInfo = parse_url($CFG->wwwroot);
        $xml->addAttribute("domain", $urlInfo['host']);

        return $xml->asXML();
    }
}
?>