<?php

require_once("$CFG->dirroot/mod/amvonetroom/class.HttpRequest.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Version.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.SchoolPassport.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.Server.php");
require_once("$CFG->dirroot/mod/amvonetroom/class.User.php");

class amvonetroom_Session {
    private $uid;
    private $error;

    /**
     * Constructor
     * @param $uid - ID of session, MUST BE unique for all schools
     */
    public function __construct ($uid) {
        $this->uid = $uid;
        $this->error = NULL;
    }

    /**
     *
     * @return string error or NULL
     */
    public function getError () {
        return $this->error;
    }

    public function clearError() {
        $this->error = NULL;
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
        $this->clearError();

        $passport = amvonetroom_SchoolPassport::get();
        if (!$passport) {
            $this->error = amvonetroom_SchoolPassport::getError();
            return FALSE;
        }

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
        if (!$req->send($data)) {
            $this->error = $req->getError();
        } else {
            $this->uid = $req->getResponse();
        }
        $req->close();

        return $this->error === NULL;
    }

    public function update($name, $info) {
        $this->clearError();

        $passport = amvonetroom_SchoolPassport::get();
        if (!$passport) {
            $this->error = amvonetroom_SchoolPassport::getError();
            return FALSE;
        }

        $serverUrl = amvonetroom_Server::balancerProxy($passport->getEntryPoint(), "rpc/$this->uid/update");
        $serverUrl .= "&name=" . urlencode($name);
        $token = amvonetroom_User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";

        $req = new amvonetroom_HttpRequest("POST", $serverUrl);
        $req->setContentType("text/plain; charset=UTF-8");
        if (!$req->send($info)) {
            $this->error = $req->getError();
        }
        $req->close();

        return $this->error === NULL;
    }

    /**
     * Deletes session from media-server.
     *
     * @return boolean result
     */
    public function delete() {
        $this->clearError();

        $passport = amvonetroom_SchoolPassport::get();
        if (!$passport) {
            $this->error = amvonetroom_SchoolPassport::getError();
            return FALSE;
        }

        $serverUrl = amvonetroom_Server::balancerProxy($passport->getEntryPoint(), "rpc/$this->uid/delete");
        $token = amvonetroom_User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";
        
        $req = new amvonetroom_HttpRequest("GET", $serverUrl);
        if (!$req->send(NULL)) {
            $this->error = $req->getError();
        }
        $req->close();

        return $this->error === NULL;
    }
}
?>