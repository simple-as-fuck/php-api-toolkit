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

- [Api client](#api-client-service)
- [Api server](#api-server-controller-tools)

### Api client service

Api client require guzzle client, psr client interface is not good enough because absence of async request.
Second main dependency is some config repository, you can implement yours configuration loading.

For laravel is prepared Laravel config adapter which load automatically configuration from services.php config,
with structure:

```php
    'some_api_name' => [ // this key is value of first parameter ApiClient::request method
        'base_url' => 'https://some-host/some-base-url',
        'token' => 'tokenexample', // optional default null, authentication token for https://swagger.io/docs/specification/authentication/bearer-authentication/
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

### Api server controller tools

For request handling is prepared Validator and Response factories.
More information about validation rules you can find in
[Simple as fuck / Php Validator](https://github.com/simple-as-fuck/php-validator) readme.

If you using symfony request and responses, you can use factories from different namespace, commented in example.

```php

// star of your action

$rules = \SimpleAsFuck\ApiToolkit\Factory\Server\Validator::make($request);
//$rules = \SimpleAsFuck\ApiToolkit\Factory\Symfony\Validator::make($request);

// validate some query parameter
$someQueryValidValue = $rules->query()->key('someKey')->string()->parseInt()->min(1)->notNull();

// validate something from request body with json format
$someJsonValidValue = $rules->json()->object()->property('someProperty')->string()->notEmpty()->max(255)->notNull();



// end fo your action

/**
 * @var YourClass $yourModelForResponseBody
 * @var \SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer<YourClass> $transformer 
 */

// response with one object
$response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeJson($yourModelForResponseBody, $transformer, \Kayex\HttpCodes::HTTP_OK);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeJson($yourModelForResponseBody, $transformer, \Kayex\HttpCodes::HTTP_OK);

// response with some array or collection (avoiding out of memory problem recommended some lazy loading iterator)
$response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeJsonStream(new \ArrayIterator([$yourModelForResponseBody]), $transformer);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeJsonStream(new \ArrayIterator([$yourModelForResponseBody]), $transformer);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeJsonStream([$yourModelForResponseBody], $transformer);

```

### Api server middleware tools

If anything go wrong you can use Exception transformers in your exception catching middleware or in some exception handler.

For laravel is prepared Laravel config adapter which load automatically configuration for ExceptionTransformer,
you can easily get this transformer from DI, without any new configuration (standard configuration from Laravel is used).

```php

/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Config\Repository $configRepository
 */

try {
    // some breakable logic
}
catch(\SimpleAsFuck\ApiToolkit\Model\Server\ApiException $exception) {
//catch(\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
    $response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeJson(
    //$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeJson(
        $exception,
        // transformer will convert exception in to json object with message property with original exception message
        new \SimpleAsFuck\ApiToolkit\Service\Server\ApiExceptionTransformer(),
        $exception->getStatusCode()
    );
}
catch (\Throwable $exception) {
    $response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeJson(
    //$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeJson(
        $exception,
        // transformer will convert exception in to json object with message property
        // if application has turned off debug, message property contain only "Internal server error"
        // but with enabled debug message contains exception type, message, file and line where was exception thrown
        new \SimpleAsFuck\ApiToolkit\Service\Server\ExceptionTransformer($configRepository),
        \Kayex\HttpCodes::HTTP_INTERNAL_SERVER_ERROR
    );
}

```
