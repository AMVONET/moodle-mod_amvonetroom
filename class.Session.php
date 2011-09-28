<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.Exception.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.HttpRequest.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.SchoolPassport.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Server.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.User.php");

class amvonetroom_Session {
    private $uid;

    /**
     * Constructor
     * @param $uid - ID of session, MUST BE unique for all schools
     */
    public function __construct ($uid) {
        $this->uid = $uid;
    }

    public function getId() {
        return $this->uid;
    }

    /**
     * Creates new session on media-server.
     *
     * @param $server_url
     * @return boolean result
     */
    public function create ($course, $name, $info) {
        $passport = amvonetroom_SchoolPassport::get();

        $session = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><session/>');
        $session->addAttribute("id", $this->uid);
        $session->addAttribute("course", $course);
        $session->addAttribute("name", $name);
        $session->addAttribute("schoolKey", amvonetroom_SchoolPassport::getKey());
        $session->addChild("info", $info);

        $serverUrl = amvonetroom_Server::balancerProxy($passport->getEntryPoint(), "rpc/0/import");
        $token = amvonetroom_User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";
        
        $data = $session->asXML();

        $req = new amvonetroom_HttpRequest("PUT", $serverUrl);
        $req->setContentType("text/xml");
        try {
            $req->send($data);
            $this->uid = $req->getResponse();
        } catch (amvonetroom_Exception $e) {
            throw $e;
        }
        $req->close();
    }

    public function update($name, $info) {
        $passport = amvonetroom_SchoolPassport::get();

        $serverUrl = amvonetroom_Server::balancerProxy($passport->getEntryPoint(), "rpc/$this->uid/update");
        $serverUrl .= "&name=" . urlencode($name);
        $token = amvonetroom_User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";

        $req = new amvonetroom_HttpRequest("POST", $serverUrl);
        $req->setContentType("text/plain; charset=UTF-8");
        try {
            $req->send($info);
        } catch (amvonetroom_Exception $e) {
            throw $e;
        }
        $req->close();
    }

    /**
     * Deletes session from media-server.
     *
     * @return boolean result
     */
    public function delete() {
        $passport = amvonetroom_SchoolPassport::get();
        
        $serverUrl = amvonetroom_Server::balancerProxy($passport->getEntryPoint(), "rpc/$this->uid/delete");
        $token = amvonetroom_User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";
        
        $req = new amvonetroom_HttpRequest("GET", $serverUrl);
        try {
            $req->send();
        } catch (amvonetroom_Exception $e) {
            throw $e;
        }
        $req->close();
    }
}
?>