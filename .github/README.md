# Simple as fuck / Php api toolkit

Bunch of services for easies api implementations with standardized dependencies as possible.

## Installation

```console
composer require simple-as-fuck/php-api-toolkit
```

## Support

If any PHP platform requirements in [composer.json](../composer.json) ends with security support,
consider package version as unsupported except last version.

[PHP supported versions](https://www.php.net/supported-versions.php).

## Usage

### Api client service

Api client require guzzle client, psr client interface is not good enough because absence of async request.
Second main dependency is some config repository, you can implement yours configuration loading.

For laravel is prepared Laravel config adapter which load automatically configuration from services.php config,
with structure:

```php
    'some_api_name' => [ // this key is value of first parameter ApiClient::request method
        'base_url' => 'https://some-host/some-base-url',
        'token' => 'Bearer token', // optional default null
        'verify' => true, // optional default true, turn on/off certificates verification
    ],
```

```php
/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Config\Repository $configRepository
 */

$client = new \SimpleAsFuck\ApiToolkit\Service\Client\ApiClient($configRepository, new \GuzzleHttp\Client(), new \GuzzleHttp\Psr7\HttpFactory());

/**
 * with transformer, YourClass can be converted into different api structure
 *
 * @implements \SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer<YourClass>
 */
final class YourTransformer implements \SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer
{
    /**
     * @param YourClass $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $apiData = new \stdClass();
        $apiData->some_property = $transformed->someProperty;
        return $apiData;
    }
}

/**
 * @var YourClass $yourModelForRequestBody
 * @var \SimpleAsFuck\Validator\Rule\Custom\UserClassRule<YourOtherClass> $classRuleForResponseModel
 */

try {
    $response = $client->request('some_api_name', 'POST', '/to-some-action', $yourModelForRequestBody, new YourTransformer());
    /*
     * response has getter for json decoded body which is validated after decoding by rule chain
     * request method return object rule, so you can easily validate response json structure
     * is recommended use some you class rule documented here: https://github.com/simple-as-fuck/php-validator#user-class-rule
     * and convert api data structure into some your concrete object instance
     */
    $yourModelFromResponseBody = $response->class($classRuleForResponseModel)->notNull();
}
catch (\SimpleAsFuck\ApiToolkit\Model\Client\ApiException $exception) {
    /*
     * if anything go wrong in request/response processing or response json parsing
     * \SimpleAsFuck\ApiToolkit\Model\Client\ApiException is thrown,
     * and you can handle any error from communication
     */
    $exception->getCode(); // if exception contains http response, http status is here, otherwise zero is returned
    $exception->getMessage(); // if http response contains json object with message string property, json message overwrite exception message
}

```
