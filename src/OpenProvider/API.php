<?php

namespace OpenProvider;

use DOMDocument;
use DOMNode;
use OpenProvider\API\ApiException;
use SimpleXMLElement;

class API
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    public static $encoding = 'UTF-8';

    /**
     * Constructor
     *
     * @param string $url
     * @param int $timeout
     */
    public function __construct ($url, $timeout = 1000)
    {
        $this->url = $url;
        $this->timeout = $timeout;
    }

    /**
     * Enable or disable debugging
     *
     * @param bool $v
     * @return $this
     */
    public function setDebug($v)
    {
        $this->debug = $v;
        return $this;
    }

    /**
     * Process the raw reply
     *
     * @param Request $request
     * @return bool|mixed
     * @throws ApiException
     */
    public function processRawReply(Request $request)
    {
        if ($this->debug) {
            echo $request->getRaw() . "\n";
        }

        $msg = $request->getRaw();
        $str = $this->send($msg);

        if (!$str) {
            throw new ApiException('Bad reply', 4004);
        }

        if ($this->debug) {
            echo $str . "\n";
        }

        return $str;
    }

    /**
     * Process the request
     *
     * @param Request $request
     * @return Reply
     * @throws ApiException
     */
    public function process(Request $request)
    {
        if ($this->debug) {
            echo $request->getRaw() . "\n";
        }

        $msg = $request->getRaw();
        $str = $this->send($msg);

        if (!$str) {
            throw new ApiException('Bad reply', 4004);
        }

        if ($this->debug) {
            echo $str . "\n";
        }

        return new Reply($str);
    }

    /**
     * Encode an HTML string
     *
     * @param $str
     * @return string
     */
    public static function encode($str)
    {
        $ret = @htmlentities($str, null, API::$encoding);

        // Ticket #18 "Encoding issues when parsing XML"
        // Some tables have data stored in two encodings
        if (strlen($str) && !strlen($ret)) {
            $str = iconv('ISO-8859-1', 'UTF-8', $str);
            $ret = htmlentities($str, null, self::$encoding);
        }

        return $ret;
    }

    /**
     * Decode a string into HTML
     *
     * @param $str
     * @return string
     */
    public static function decode($str)
    {
        return html_entity_decode($str, null, API::$encoding);
    }

    /**
     * Create a request from a given XML string
     *
     * @param null $xmlStr
     * @return Request
     */
    public static function createRequest($xmlStr = null)
    {
        return new Request($xmlStr);
    }

    /**
     * Create a reply from a given XML string
     *
     * @param null $xmlStr
     * @return Reply
     */
    public static function createReply($xmlStr = null)
    {
        return new Reply($xmlStr);
    }

    /**
     * Send a request through cURL
     *
     * @param $str
     * @return bool|mixed
     */
    protected function send($str)
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

        $ret = curl_exec($ch);

        $errno = curl_errno($ch);
        $this->error = $error = curl_error($ch);

        curl_close($ch);

        if ($errno) {
            error_log("CURL error. Code: $errno, Message: $error");
            return false;
        } else {
            return $ret;
        }
    }

    /**
     * convert SimpleXML to PhpObj
     *
     * @param DOMNode $node
     * @return array|null|string
     * @throws ApiException
     */
    public static function convertXmlToPhpObj(DOMNode $node)
    {
        $ret = array();

        if ($node->hasChildNodes()) {
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

    /**
     * Parse an array into a DOMNode
     *
     * @param $node
     * @return array
     * @throws ApiException
     */
    protected static function parseArray($node)
    {
        $ret = array();

        foreach ($node->childNodes as $child) {
            $name = self::decode($child->nodeName);

            if ($name !== 'item') {
                throw new ApiException('Wrong message format', 4006);
            }

            $ret[] = self::convertXmlToPhpObj($child);
        }

        return $ret;
    }

    /**
     * converts php-structure to DOM-object.
     *
     * @param array|DOMNode $arr php-structure
     * @param DOMNode $node parent node where new element to attach
     * @param DOMDocument $dom DOMDocument object
     * @return SimpleXMLElement
     */
    public static function convertPhpObjToDom($arr, DOMNode $node, DOMDocument $dom)
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