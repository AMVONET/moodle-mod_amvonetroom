<?php
/**
 * Represents HTTP request functionality on the basis of cURL lib.
 * Used to prevent additional dependencies on php_http extension,
 * which isn't required by Moodle. 
 */
class amvonetroom_HttpRequest {

    private $curlHandle = null;
    private $response = null;
    private $headers = array();

    public function __construct($method, $url) {
        $this->curlHandle = curl_init();
        $options = array
        (
            CURLOPT_URL		        =>	$url,
            CURLOPT_CUSTOMREQUEST   =>  $method,
            CURLOPT_HEADER			=>	false,
            CURLOPT_RETURNTRANSFER	=>	true,
            CURLOPT_FOLLOWLOCATION	=>	true,
        );
        curl_setopt_array($this->curlHandle, $options);            
    }

    public function setHeader($name, $value) {
        $this->headers[] = $name . ": " . $value;
    }

    public function setContentType($type) {
        $this->setHeader("Content-type", $type);
    }

    public function setContentLength($length) {
        $this->setHeader("Content-length", $length);
    }

    public function send($data) {
        $infile = null;
        if ($data !== null) {
            $infile = tmpfile();
            fwrite($infile, $data);
            $length = ftell($infile);
            fseek($infile, 0);

            curl_setopt($this->curlHandle, CURLOPT_PUT, true);
            curl_setopt($this->curlHandle, CURLOPT_INFILE, $infile);
            curl_setopt($this->curlHandle, CURLOPT_INFILESIZE, $length);
        }

        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = curl_exec($this->curlHandle);

        if ($infile !== null) {
            fclose($infile);
        }

        $errno = $this->getErrno();
        $status = $this->getStatusCode();
        return $errno == 0 && $status >= 200 && $status < 300;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getStatusCode() {
        return (int)curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    }

    public function getErrno() {
        return curl_errno($this->curlHandle);
    }

    public function close() {
        curl_close($this->curlHandle);
    }

    public function getError() {
        $errno = $this->getErrno();
        if ($errno != 0) {
            return "HTTP request failed. errno=$errno";
        }

        $status = $this->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return "HTTP request failed. status=$status";
        }
    }
}
?>