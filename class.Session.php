<?php

require_once("class.HttpRequest.php");
require_once("class.Version.php");
require_once("class.SchoolPassport.php");
require_once("class.Server.php");
require_once("class.User.php");

class Session {
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

        $passport = SchoolPassport::get();
        if (!$passport) {
            $this->error = SchoolPassport::getError();
            return FALSE;
        }

        $session = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><session/>');
        // skip uid, it's ignored anyway
        $session->addAttribute("course", $course);
        $session->addAttribute("name", $name);
        $session->addAttribute("schoolKey", SchoolPassport::getKey());
        $session->addChild("info", $info);

        $serverUrl = Server::getWorkerUrl($passport->getEntryPoint(), "0");
        if (!$serverUrl) {
            $this->error = Server::getError();
            return FALSE;
        }

        $serverUrl = Server::combineUrl($serverUrl, "rest/session/0");
        $serverUrl .= "?protoVersion=" . ProtoVersion::getCurrent()->__toString();
        $token = User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";
        
        $data = $session->asXML();

        $req = new HttpRequest("PUT", $serverUrl);
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

        $passport = SchoolPassport::get();
        if (!$passport) {
            $this->error = SchoolPassport::getError();
            return FALSE;
        }

        $serverUrl = Server::getWorkerUrl($passport->getEntryPoint(), $this->uid);
        if (!$serverUrl) {
            $this->error = Server::getError();
            return FALSE;
        }

        $serverUrl = Server::combineUrl($serverUrl, "rpc/$this->uid/update");
        $serverUrl .= "?protoVersion=" . ProtoVersion::getCurrent()->__toString();
        $serverUrl .= "&name=" . urlencode($name);
        $token = User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";

        $req = new HttpRequest("POST", $serverUrl);
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

        $passport = SchoolPassport::get();
        if (!$passport) {
            $this->error = SchoolPassport::getError();
            return FALSE;
        }

        $serverUrl = Server::combineServletUrl($passport->getEntryPoint(), "rpc/$this->uid/delete", $this->uid);
        $token = User::getToken();
        if (!empty($token))
            $serverUrl .= "&token=$token";
        
        $req = new HttpRequest("GET", $serverUrl);
        if (!$req->send(NULL)) {
            $this->error = $req->getError();
        }
        $req->close();

        return $this->error === NULL;
    }
}
?>