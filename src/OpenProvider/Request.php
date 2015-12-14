<?php

namespace OpenProvider;

use DOMDocument;

class Request
{
    /**
     * @var string
     */
    protected $cmd;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $raw;

    /**
     * @var string
     */
    protected $misc;

    /**
     * Create a new Request
     *
     * @param null $str
     */
    public function __construct($str = null) {
        if ($str) {
            $this->raw = $str;
            $this->parseRequest($str);
        }
    }

    /**
     * Parse request string to assign object properties with command name and
     * arguments structure
     *
     * @param string $str
     */
    protected function parseRequest($str = '')
    {
        $dom = new DOMDocument();
        $dom->loadXML($str, LIBXML_NOBLANKS);

        $arr = API::convertXmlToPhpObj($dom->documentElement);

        list($dummy, $credentials) = each($arr);
        list($this->cmd, $this->args) = each($arr);

        $this->username = $credentials['username'];
        $this->password = $credentials['password'];

        if (isset($credentials['hash'])) {
            $this->hash = $credentials['hash'];
        }

        if (isset($credentials['misc'])) {
            $this->misc = $credentials['misc'];
        }

        $this->token = isset($credentials['token']) ? $credentials['token'] : null;
        $this->ip = isset($credentials['ip']) ? $credentials['ip'] : null;

        if (isset($credentials['language'])) {
            $this->language = $credentials['language'];
        }
    }

    /**
     * Set the command
     *
     * @param $cmd
     * @return $this
     */
    public function setCommand($cmd)
    {
        $this->cmd = $cmd;
        return $this;
    }

    /**
     * Get the command
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->cmd;
    }

    /**
     * Set the language
     *
     * @param $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Get the language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the args
     *
     * @param array $args
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Get the args
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Set the misc
     *
     * @param $misc
     * @return $this
     */
    public function setMisc($misc)
    {
        $this->misc = $misc;
        return $this;
    }

    /**
     * Get the misc
     *
     * @return string
     */
    public function getMisc()
    {
        return $this->misc;
    }

    /**
     * Set the authentication parameters
     *
     * @param array $args
     * @return $this
     */
    public function setAuth(array $args)
    {
        $this->username = isset($args["username"]) ? $args["username"] : null;
        $this->password = isset($args["password"]) ? $args["password"] : null;
        $this->hash = isset($args["hash"]) ? $args["hash"] : null;
        $this->token = isset($args["token"]) ? $args["token"] : null;
        $this->ip = isset($args["ip"]) ? $args["ip"] : null;
        $this->misc = isset($args["misc"]) ? $args["misc"] : null;
        return $this;
    }

    /**
     * Get the auth parameters
     *
     * @return array
     */
    public function getAuth()
    {
        return array(
            "username" => $this->username,
            "password" => $this->password,
            "hash" => $this->hash,
            "token" => $this->token,
            "ip" => $this->ip,
            "misc" => $this->misc,
        );
    }

    /**
     * Get the raw output
     *
     * @return string
     */
    public function getRaw()
    {
        if (!$this->raw) {
            $this->raw .= $this->getRequest();
        }
        return $this->raw;
    }

    /**
     * Get the request
     *
     * @return string
     */
    public function getRequest()
    {
        $dom = new DOMDocument('1.0', API::$encoding);

        $credentialsElement = $dom->createElement('credentials');

        $usernameElement = $dom->createElement('username');
        $usernameElement->appendChild(
            $dom->createTextNode(API::encode($this->username))
        );

        $credentialsElement->appendChild($usernameElement);

        $passwordElement = $dom->createElement('password');
        $passwordElement->appendChild(
            $dom->createTextNode(API::encode($this->password))
        );

        $credentialsElement->appendChild($passwordElement);

        $hashElement = $dom->createElement('hash');
        $hashElement->appendChild(
            $dom->createTextNode(API::encode($this->hash))
        );

        $credentialsElement->appendChild($hashElement);

        if (isset($this->language)) {
            $languageElement = $dom->createElement('language');
            $languageElement->appendChild($dom->createTextNode($this->language));

            $credentialsElement->appendChild($languageElement);
        }

        if (isset($this->token)) {
            $tokenElement = $dom->createElement('token');
            $tokenElement->appendChild($dom->createTextNode($this->token));

            $credentialsElement->appendChild($tokenElement);
        }

        if (isset($this->ip)) {
            $ipElement = $dom->createElement('ip');
            $ipElement->appendChild($dom->createTextNode($this->ip));

            $credentialsElement->appendChild($ipElement);
        }

        if (isset($this->misc)) {
            $miscElement = $dom->createElement('misc');
            $credentialsElement->appendChild($miscElement);

            API::convertPhpObjToDom($this->misc, $miscElement, $dom);
        }

        $rootElement = $dom->createElement('openXML');
        $rootElement->appendChild($credentialsElement);

        $rootNode = $dom->appendChild($rootElement);

        $cmdNode = $rootNode->appendChild(
            $dom->createElement($this->getCommand())
        );

        API::convertPhpObjToDom($this->args, $cmdNode, $dom);

        return $dom->saveXML();
    }
}