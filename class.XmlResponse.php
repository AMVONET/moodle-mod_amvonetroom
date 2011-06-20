<?php
class amvonetroom_XmlResponsePart {
    private $node;

    protected function __construct($node) {
        $this->node = $node;
    }

    protected function document() {
        return $this->node->ownerDocument;
    }

    protected function node() {
        return $this->node;
    }

    /**
     * Creates element.
     *
     * @param  string $name
     * @param  array $attrs
     * @return XmlResponsePart
     */
    public function element($name, $attrs = null) {
        $doc = $this->document();

        $child = $doc->createElement($name);
        if ($attrs) {
            foreach ($attrs as $attrName => $attrValue) {
                $attrNode = $doc->createAttribute($attrName);
                $attrNode->value = $attrValue;
                $child->appendChild($attrNode);
            }
        }
        $this->node->appendChild($child);

        return new amvonetroom_XmlResponsePart($child);
    }

    /**
     * Creates element with text value.
     *
     * @param  string $name
     * @param  string $value
     * @return XmlResponsePart
     */
    public function text($name, $value) {
        $doc = $this->document();

        $child = $doc->createElement($name);
        $text = $doc->createTextNode($value);
        $child->appendChild($text);
        $this->node->appendChild($child);

        return $this;
    }

    /**
     * Creates element with CDATA value.
     *
     * @param  string $name
     * @param  string $value
     * @return XmlResponsePart
     */
    function cdata($name, $value) {
        $doc = $this->document();

        $child = $doc->createElement($name);
        $text = $doc->createCDATASection($value);
        $child->appendChild($text);
        $this->node->appendChild($child);

        return $this;
    }

    /**
     * Creates attribute.
     *
     * @param  string $name
     * @param  array $attrs
     * @return XmlResponsePart
     */
    public function attribute($name, $value) {
        $doc = $this->document();

        $attrNode = $doc->createAttribute($name);
        $attrNode->value = $value;
        $this->node->appendChild($attrNode);

        return $this;
    }
};

class amvonetroom_XmlResponse extends amvonetroom_XmlResponsePart {

    protected function __construct($node) {
        parent::__construct($node);
    }

    /**
     * Retruns string representation.
     *
     * @return string
     */
    public function __toString() {
        return $this->document()->saveXML();
    }

    public function send() {
        $body = $this->__toString();
        header("Content-Type: text/xml;charset=UTF-8");
        header("Content-Length: " . strlen($body));
        print($body);
    }

    /**
     * Creates default error response.
     *
     * @param string $msg error message
     * @param string $code symbolic error code which is used for localization
     * @return XmlResponse
     */
    public static function error($msg, $code = null) {
        $doc = new DOMDocument ('1.0', 'UTF-8');
        $node = $doc->createElement("error");
        $doc->appendChild($node);
        $cdata = $doc->createCDATASection($msg);
        $node->appendChild($cdata);

        if (!empty($code)) {
            $attrNode = $doc->createAttribute('code');
            $attrNode->value = $code;
            $node->appendChild($attrNode);
        }

        return new amvonetroom_XmlResponse($node);
    }

    /**
     * Creates default ok response.
     *
     * @param  string $msg success message
     * @return XmlResponse
     */
    public static function ok($msg) {
        $doc = new DOMDocument ('1.0', 'UTF-8');
        $node = $doc->createElement("ok");
        $doc->appendChild($node);
        $cdata = $doc->createCDATASection($msg);
        $node->appendChild($cdata);

        return new amvonetroom_XmlResponse($node);
    }

    /**
     * Creates complex response with <items> root node.
     *
     * @return XmlResponse
     */
    public static function items() {
        $doc = new DOMDocument ('1.0', 'UTF-8');
        $items_node = $doc->createElement("items");
        $doc->appendChild($items_node);

        return new amvonetroom_XmlResponse($items_node);
    }

    /**
     * Creates response with <user> root node.
     *
     * @static
     * @param bool $useNamespace
     * @return XmlResponse
     */
    public static function user($useNamespace = false) {
        $doc = new DOMDocument ('1.0', 'UTF-8');
        $user_node = $useNamespace ?
            $doc->createElementNS("http://www.amvonet.com/schema/user", "user") :
            $doc->createElement("user");

        $doc->appendChild($user_node);

        return new amvonetroom_XmlResponse($user_node);
    }
}
