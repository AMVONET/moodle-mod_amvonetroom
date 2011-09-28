<?php

/**
 * The AMVONET room mod's exception.
 * It's processed like a moodle exception.
 */
class amvonetroom_Exception extends moodle_exception {
    private $status;

    function __construct($errorcode, $args = NULL, $status = 500) {
        parent::__construct($errorcode, 'amvonetroom', '', $args);

        $this->status = $status;
    }

    public function getStatus() {
        return $this->status;
    }
}

/**
 * Show a moodle's error page.
 *
 * @throws amvonetroom_Exception
 * @param  string $errorcode is a key of resource string in the amvonetroom boundle.
 * @param  $args - is a resource's parameter, witch contains a resource string,
 *              e.g. 'Count: $a' or 'Name: $args->first $args->last'.
 */
function amvonetroom_errorcode($errorcode, $args = NULL) {
    throw new amvonetroom_Exception($errorcode, $args);
}

/**
 * Show a moodle's error page.
 *
 * @throws amvonetroom_Exception
 * @param  string $message is a message to display.
 * @param  int $status is a http status code (500 by default)
 */
function amvonetroom_error($message, $status = 500) {
    throw new amvonetroom_Exception('error', $message, $status);
}

/**
 * Finish a request with specified HTTP status.
 *
 * @param int $status is a HTTP status of the response.
 * @param string $desc [optional] is a short description of the status. Used a standard if it's need.
 */
function amvonetroom_die($status, $desc='') {
    if (empty($desc) && defined("AMVONETROOM_HTTP_STATUS_$status"))
        $desc = constant("AMVONETROOM_HTTP_STATUS_$status");

	header("HTTP/1.1 $status $desc");
    die();
}

define ('AMVONETROOM_HTTP_STATUS_100', 'Continue');
define ('AMVONETROOM_HTTP_STATUS_101', 'Switching Protocols');
define ('AMVONETROOM_HTTP_STATUS_102', 'Processing');
define ('AMVONETROOM_HTTP_STATUS_200', 'OK');
define ('AMVONETROOM_HTTP_STATUS_201', 'Created');
define ('AMVONETROOM_HTTP_STATUS_202', 'Accepted');
define ('AMVONETROOM_HTTP_STATUS_203', 'Non-Authoritative Information');
define ('AMVONETROOM_HTTP_STATUS_204', 'No Content');
define ('AMVONETROOM_HTTP_STATUS_205', 'Reset Content');
define ('AMVONETROOM_HTTP_STATUS_206', 'Partial Content');
define ('AMVONETROOM_HTTP_STATUS_207', 'Multi-Status');
define ('AMVONETROOM_HTTP_STATUS_300', 'Multiple Choices');
define ('AMVONETROOM_HTTP_STATUS_301', 'Moved Permanently');
define ('AMVONETROOM_HTTP_STATUS_302', 'Found');
define ('AMVONETROOM_HTTP_STATUS_303', 'See Other');
define ('AMVONETROOM_HTTP_STATUS_304', 'Not Modified');
define ('AMVONETROOM_HTTP_STATUS_305', 'Use Proxy');
define ('AMVONETROOM_HTTP_STATUS_306', 'Switch Proxy');
define ('AMVONETROOM_HTTP_STATUS_307', 'Temporary Redirect');
define ('AMVONETROOM_HTTP_STATUS_400', 'Bad Request');
define ('AMVONETROOM_HTTP_STATUS_401', 'Unauthorized');
define ('AMVONETROOM_HTTP_STATUS_402', 'Payment Required');
define ('AMVONETROOM_HTTP_STATUS_403', 'Forbidden');
define ('AMVONETROOM_HTTP_STATUS_404', 'Not Found');
define ('AMVONETROOM_HTTP_STATUS_405', 'Method Not Allowed');
define ('AMVONETROOM_HTTP_STATUS_406', 'Not Acceptable');
define ('AMVONETROOM_HTTP_STATUS_407', 'Proxy Authentication Required');
define ('AMVONETROOM_HTTP_STATUS_408', 'Request Timeout');
define ('AMVONETROOM_HTTP_STATUS_409', 'Conflict');
define ('AMVONETROOM_HTTP_STATUS_410', 'Gone');
define ('AMVONETROOM_HTTP_STATUS_411', 'Length Required');
define ('AMVONETROOM_HTTP_STATUS_412', 'Precondition Failed');
define ('AMVONETROOM_HTTP_STATUS_413', 'Request Entity Too Large');
define ('AMVONETROOM_HTTP_STATUS_414', 'Request-URI Too Long');
define ('AMVONETROOM_HTTP_STATUS_415', 'Unsupported Media Type');
define ('AMVONETROOM_HTTP_STATUS_416', 'Requested Range Not Satisfiable');
define ('AMVONETROOM_HTTP_STATUS_417', 'Expectation Failed');
define ('AMVONETROOM_HTTP_STATUS_418', 'I\'m a teapot');
define ('AMVONETROOM_HTTP_STATUS_422', 'Unprocessable Entity');
define ('AMVONETROOM_HTTP_STATUS_423', 'Locked');
define ('AMVONETROOM_HTTP_STATUS_424', 'Failed Dependency');
define ('AMVONETROOM_HTTP_STATUS_425', 'Unordered Collection');
define ('AMVONETROOM_HTTP_STATUS_426', 'Upgrade Required');
define ('AMVONETROOM_HTTP_STATUS_449', 'Retry With');
define ('AMVONETROOM_HTTP_STATUS_450', 'Blocked by Windows Parental Controls');
define ('AMVONETROOM_HTTP_STATUS_460', 'Version Incompatible');
define ('AMVONETROOM_HTTP_STATUS_461', 'Version Too Old');
define ('AMVONETROOM_HTTP_STATUS_500', 'Internal Server Error');
define ('AMVONETROOM_HTTP_STATUS_501', 'Not Implemented');
define ('AMVONETROOM_HTTP_STATUS_502', 'Bad Gateway');
define ('AMVONETROOM_HTTP_STATUS_503', 'Service Unavailable');
define ('AMVONETROOM_HTTP_STATUS_504', 'Gateway Timeout');
define ('AMVONETROOM_HTTP_STATUS_505', 'HTTP Version Not Supported');
define ('AMVONETROOM_HTTP_STATUS_506', 'Variant Also Negotiates');
define ('AMVONETROOM_HTTP_STATUS_507', 'Insufficient Storage');
define ('AMVONETROOM_HTTP_STATUS_509', 'Bandwidth Limit Exceeded');
define ('AMVONETROOM_HTTP_STATUS_510', 'Not Extended');
