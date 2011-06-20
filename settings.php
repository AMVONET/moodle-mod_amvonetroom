<?php
require_once ("class.SchoolPassport.php");

// try auto-register if key passed externally
$register = optional_param('register', null, PARAM_SAFEDIR);
if (!empty($register)) {
    amvonetroom_SchoolPassport::setKey($register);
}
/////////////////////////////////////////////

$link_url = AMVONETROOM_PLUGIN_URL . "?from=" . urlencode($CFG->wwwroot);
$status = "";
$version = "";
$domain = "";
$type = "";
$html_status_color = "";
$link = "";
$key = amvonetroom_SchoolPassport::getKey();

if (empty($key)) {
    $html_status_color = "#999999";
    $link = "<a href='$link_url'>" . get_string("link_register", "amvonetroom") . "</a>";
    $status = get_string("status_no_key", "amvonetroom");
} else {
    $passport = amvonetroom_SchoolPassport::get();
    if (!$passport) {
        $html_status_color = "#990000";
        $link = "<a href='$link_url'>" . get_string("link_account", "amvonetroom") . "</a>";
        $status = amvonetroom_SchoolPassport::getError();
    } else {
        $domain = $passport->getDomain();
        $version = $passport->getVersion();
        $type = $passport->getType();
        $html_status_color = "#009900";
        $link = "<a href='$link_url'>" . get_string("link_account", "amvonetroom") . "</a>";
        $status = get_string("status_" . $passport->getStatus(), "amvonetroom");
        if ($passport->getType() == AMVONETROOM_TYPE_TRIAL) {
            $link = "<a href=\"$link_url\">" . get_string("link_buy", "amvonetroom") . "</a>";
            if ($passport->getStatus() == AMVONETROOM_STATUS_ACTIVE) {
                $status .= get_string("status_active_till", "amvonetroom") . $passport->getExpirationDate();
            } elseif ($passport->getStatus() == AMVONETROOM_STATUS_BLOCKED) {
                $status .= get_string("status_blocked_since", "amvonetroom") . $passport->getExpirationDate();
                $html_status_color = "#990000";
            }    
        }
    }
}

$warn = "";

// check crossdomain.xml (not necessary now)
/*$crossdomain = "";

$req = new amvonetroom_HttpRequest("GET", $CFG->wwwroot . '/mod/amvonetroom/crossdomain.xml');
if ($req->send(NULL))
    $crossdomain = $req->getResponse();

if (strstr($crossdomain, 'allow-access-from domain="*.amvonet.com"') === false) {
    $warn = get_string("warn_no_crossdomain", "amvonetroom");
}*/
// ********************

$html = "<div><table cellspacing=0 cellpadding=2 border=0>";

if ($warn) {
    $html .= "<tr style='background-color:yellow'><td>" . get_string("label_warning", "amvonetroom") . ":</td><td style='padding-left:15px'>" . $warn . "</td></tr><tr style='height:15px'/>";
}

if ($domain) {
    $html .= "<tr><td><b>" . get_string("label_domain", "amvonetroom") . ": </b></td><td style='padding-left:15px'>" . $domain . "</td></tr>";
}

if ($version) {
    $html .= "<tr><td><b>" . get_string("label_version", "amvonetroom") . ": </b></td>" .
                 "<td style='padding-left:15px'>" . get_string("label_version_" . $version, "amvonetroom") . " / " . get_string("label_version_type_" . $type, "amvonetroom") . "</td>" .
             "</tr>";
}

$html .= "<tr><td><b>" . get_string("label_status", "amvonetroom") . ": </b></td>" .
             "<td style='padding-left:15px;'><b style='padding:2px;color:white;background-color:$html_status_color'>$status</b></td>" .
         "</tr>";
$html .= "</table></div>";

$hint = "<table><tr><td></td><td>| $link | <a href='http://support.amvonet.com'>" . get_string("link_support", "amvonetroom") . "</a> |</td></tr></table>";

$settings->add(new admin_setting_heading ("amvonet_header", "", $html));

$settings->add(new admin_setting_configtext('amvonetroom/school_key',
    get_string("key_caption", "amvonetroom"),
    $hint,
    '')
);
?>