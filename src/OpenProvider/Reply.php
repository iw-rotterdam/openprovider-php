<?php

namespace OpenProvider;

use DOMDocument;
use OpenProvider\API\ApiException;

class Reply
{
    /**
     * @var int
     */
    protected $faultCode = 0;

    /**
     * @var string
     */
    protected $faultString = null;

    /**
     * @var array
     */
    protected $value = [];

    /**
     * @var array
     */
    protected $warnings = [];

    /**
     * @var string
     */
    protected $raw;

    /**
     * @var mixed
     */
    protected $maintenance;

    /**
     * Create a reply instance
     *
     * @param null $str
     * @throws ApiException
     */
    public function __construct($str = null) {
        if ($str) {
            $this->raw = $str;
            $this->parseReply($str);
        }
    }

    /**
     * Parse the reply
     *
     * @param string $str
     * @throws ApiException
     */
    protected function parseReply($str = '')
    {
        $dom = new DOMDocument;
        $result = $dom->loadXML(trim($str));

        if (!$result) {
            error_log("Cannot parse xml: '$str'");
        }

        $arr = API::convertXmlToPhpObj($dom->documentElement);

        if ((!is_array($arr) && trim($arr) == "") || $arr['reply']['code'] == 4005)
        {
            throw new ApiException('API is temprorarily unavailable due to maintenance', 4005);
        }

        $this->faultCode = (int) $arr['reply']['code'];
        $this->faultString = $arr['reply']['desc'];
        $this->value = $arr['reply']['data'];

        if (isset($arr['reply']['warnings'])) {
            $this->warnings = $arr['reply']['warnings'];
        }

        if (isset($arr['reply']['maintenance'])) {
            $this->maintenance = $arr['reply']['maintenance'];
        }
    }

    /**
     * Set the fault code
     *
     * @param $faultCode
     * @return $this
     */
    public function setFaultCode($faultCode)
    {
        $this->faultCode = $faultCode;
        return $this;
    }

    /**
     * Set the fault string
     *
     * @param $faultString
     * @return $this
     */
    public function setFaultString($faultString)
    {
        $this->faultString = $faultString;
        return $this;
    }

    /**
     * Set the value
     *
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get the value
     *
     * @return array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the warnings
     *
     * @param $warnings
     * @return $this
     */
    public function setWarnings($warnings)
    {
        $this->warnings = $warnings;
        return $this;
    }

    /**
     * Get the warnings
     *
     * @return array
     */
    public function getWarnings ()
    {
        return $this->warnings;
    }

    /**
     * Get the maintenance
     *
     * @return mixed
     */
    public function getMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * Get the fault string
     *
     * @return string
     */
    public function getFaultString()
    {
        return $this->faultString;
    }

    /**
     * Get the fault code
     *
     * @return int
     */
    public function getFaultCode()
    {
        return $this->faultCode;
    }

    /**
     * Get the raw reply message
     *
     * @return string
     */
    public function getRaw ()
    {
        if (!$this->raw) {
            $this->raw .= $this->getReply ();
        }
        return $this->raw;
    }

    /**
     * Fetch the reply string
     *
     * @return string
     */
    public function getReply ()
    {
        $dom = new DOMDocument('1.0', API::$encoding);

        $rootNode = $dom->appendChild($dom->createElement('openXML'));

        $replyNode = $rootNode->appendChild($dom->createElement('reply'));

        $codeNode = $replyNode->appendChild($dom->createElement('code'));
        $codeNode->appendChild($dom->createTextNode($this->faultCode));

        $descNode = $replyNode->appendChild($dom->createElement('desc'));
        $descNode->appendChild(
            $dom->createTextNode(API::encode($this->faultString))
        );

        $dataNode = $replyNode->appendChild($dom->createElement('data'));

        API::convertPhpObjToDom($this->value, $dataNode, $dom);

        if (count($this->warnings) > 0) {
            $warningsNode = $replyNode->appendChild($dom->createElement('warnings'));
            API::convertPhpObjToDom($this->warnings, $warningsNode, $dom);
        }

        if (class_exists('sfConfig') && \sfConfig::get("app_system_maintenance")) {
            $maintenanceNode = $replyNode->appendChild($dom->createElement('maintenance'));
            $maintenanceNode->appendChild($dom->createTextNode(1));
        }

        return $dom->saveXML();
    }
}