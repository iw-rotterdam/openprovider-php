<?php

class OP_API
{
    protected $url = null;
    protected $error = null;
    protected $timeout = null;
    protected $debug = null;
    static public $encoding = 'UTF-8';
    public function __construct ($url, $timeout = 1000)
    {
        $this->url = $url;
        $this->timeout = $timeout;
    }
    public function setDebug ($v)
    {
        $this->debug = $v;
        return $this;
    }
    public function processRawReply (OP_Request $r) {
        if ($this->debug) {
            echo $r->getRaw() . "\n";
        }
        $msg = $r->getRaw();
        $str = $this->_send($msg);
        if (!$str) {
            throw new OP_API_Exception("Bad reply", 4004);
        }
        if ($this->debug) {
            echo $str . "\n";
        }
        return $str;
    }
    public function process (OP_Request $r) {
        if ($this->debug) {
            echo $r->getRaw() . "\n";
        }

        $msg = $r->getRaw();
        $str = $this->_send($msg);
        if (!$str) {
            throw new OP_API_Exception("Bad reply", 4004);
        }
        if ($this->debug) {
            echo $str . "\n";
        }
        return new OP_Reply($str);
    }
    static function encode ($str)
    {
        $ret = @htmlentities($str, null, OP_API::$encoding);
        // Ticket #18 "Encoding issues when parsing XML"
        // Some tables have data stored in two encodings
        if (strlen($str) && !strlen($ret)) {
            $str = iconv('ISO-8859-1', 'UTF-8', $str);
            $ret = htmlentities($str, null, OP_API::$encoding);
        }
        return $ret;
    }
    static function decode ($str)
    {
        return html_entity_decode($str, null, OP_API::$encoding);
    }
    static function createRequest ($xmlStr = null)
    {
        return new OP_Request ($xmlStr);
    }
    static function createReply ($xmlStr = null)
    {
        return new OP_Reply ($xmlStr);
    }
    protected function _send ($str)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $ret = curl_exec ($ch);
        $errno = curl_errno($ch);
        $this->error = $error = curl_error($ch);
        curl_close ($ch);

        if ($errno) {
            error_log("CURL error. Code: $errno, Message: $error");
            return false;
        } else {
            return $ret;
        }
    }
    // convert SimpleXML to PhpObj
    public static function convertXmlToPhpObj ($node)
    {
        $ret = array();

        if (is_object($node) && $node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $name = self::decode($child->nodeName);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $ret = self::decode($child->nodeValue);
                } else {
                    if ('array' === $name) {
                        return self::parseArray($child);
                    } else {
                        $ret[$name] = self::convertXmlToPhpObj($child);
                    }
                }
            }
        }
        return 0 < count($ret) ? $ret : null;
    }
    // parse array
    protected static function parseArray ($node)
    {
        $ret = array();
        foreach ($node->childNodes as $child) {
            $name = self::decode($child->nodeName);
            if ('item' !== $name) {
                throw new OP_API_Exception('Wrong message format', 4006);
            }
            $ret[] = self::convertXmlToPhpObj($child);
        }
        return $ret;
    }
    /**
     * converts php-structure to DOM-object.
     *
     * @param array $arr php-structure
     * @param SimpleXMLElement $node parent node where new element to attach
     * @param DOMDocument $dom DOMDocument object
     * @return SimpleXMLElement
     */
    public static function convertPhpObjToDom ($arr, $node, $dom)
    {
        if (is_array($arr)) {
            /**
             * If arr has integer keys, this php-array must be converted in
             * xml-array representation (<array><item>..</item>..</array>)
             */
            $arrayParam = array();
            foreach ($arr as $k => $v) {
                if (is_integer($k)) {
                    $arrayParam[] = $v;
                }
            }
            if (0 < count($arrayParam)) {
                $node->appendChild($arrayDom = $dom->createElement("array"));
                foreach ($arrayParam as $key => $val) {
                    $new = $arrayDom->appendChild($dom->createElement('item'));
                    self::convertPhpObjToDom($val, $new, $dom);
                }
            } else {
                foreach ($arr as $key => $val) {
                    $new = $node->appendChild(
                        $dom->createElement(self::encode($key))
                    );
                    self::convertPhpObjToDom($val, $new, $dom);
                }
            }
        } else {
            $node->appendChild($dom->createTextNode(self::encode($arr)));
        }
    }
}