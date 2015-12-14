PHP interface to OpenProvider API
=================================

This package contains a small PHP interface to make use of the OpenProvider API (https://doc.openprovider.eu/index.php/Main_Page).

**NOTE: It's very important to remember that this version is NOT identical to the API listed above. This version is compliant to PSR-2 and PSR-4, and is therefore namespaced accordingly.**

**Be watchful when following the API docs**


Example
-------

```php
require 'vendor/autoload.php';

use OpenProvider\API;
use OpenProvider\Request;

$api = new API('https://api.openprovider.eu');

$request = new Request();
$request
    ->setCommand('checkDomainRequest')
    ->setAuth([ 'username' => '[username]', 'password' => '[password]' ])
    ->setArgs([
        'domains' => [
            [
                'name' => 'openprovider',
                'extension' => 'nl',
            ],
            [
                'name' => 'jouwweb',
                'extension' => 'nl'
            ],
        )
    ]);

$reply = $api->setDebug(1)->process($request);
echo "Code: " . $reply->getFaultCode() . "\n";
echo "Error: " . $reply->getFaultString() . "\n";
echo "Value: " . print_r($reply->getValue(), true) . "\n";
echo "\n---------------------------------------\n";

echo "Finished example script\n\n";
```