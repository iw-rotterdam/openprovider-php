<?php

namespace OpenProvider\Test;

use OpenProvider\API;
use OpenProvider\API\APIException;
use OpenProvider\Reply;
use OpenProvider\Request;

class InitializeTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialization()
    {
        $classes = [
            API::class,
            Reply::class,
            Request::class,
            APIException::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
        }
    }
}
