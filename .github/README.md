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
- [Api webhook](#api-server-webhook-tools)

### Api client service

Api client requires guzzle client, psr client interface is not good enough because absence of async request.
Second main dependency is some config, you can implement yours configuration loading.
Optionally, you can add deprecations logger for automated logging of `Deprecated`
or [Sunset](https://datatracker.ietf.org/doc/html/rfc8594) response header.

Laravel config load automatically configuration from `services.php` config, with structure:

```php
    'some_api_name' => [ // this key is value of first parameter ApiClient::request method
        'base_url' => 'https://some-host/some-base-url',
        'token' => 'tokenexample', // optional default null, authentication token for https://swagger.io/docs/specification/authentication/bearer-authentication/
        'verify' => true, // optional default true, turn on/off certificates verification
        'deprecated_header' => 'Deprecated', // optional default 'Deprecated', define name of deprecated response header logged into deprecation log
    ],
```

If you have in Laravel defined config key [logging.deprecations](https://laravel.com/docs/logging#logging-deprecation-warnings),
Deprecated or Sunset headers will be logged into defined log channel.

```php
/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Client\Config $config
 * @var \Psr\Log\LoggerInterface $logger
 */

/** @var \SimpleAsFuck\ApiToolkit\Service\Client\DeprecationsLogger|null $deprecationsLogger */
$deprecationsLogger = new \SimpleAsFuck\ApiToolkit\Service\Client\DeprecationsLogger(
    $config,
    $logger,
    new \GuzzleHttp\Psr7\HttpFactory()
);

$client = new \SimpleAsFuck\ApiToolkit\Service\Client\ApiClient(
    $config,
    new \GuzzleHttp\Client(),
    new \GuzzleHttp\Psr7\HttpFactory(),
    $deprecationsLogger
);

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
    $responseObject = $client->requestObject('some_api_name', 'POST', '/to-some-action', $yourModelForRequestBody, new YourTransformer());
    /*
     * response has getter for json decoded body which is validated after decoding by rule chain
     * request method return object rule, so you can easily validate response json structure
     * is recommended use some you class rule documented here: https://github.com/simple-as-fuck/php-validator#user-class-rule
     * and convert api data structure into some your concrete object instance
     */
    $yourModelFromResponseBody = $responseObject->class($classRuleForResponseModel)->notNull();
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

### Api client webhook tools

Api client service has two helper methods for registering and unregistering webhook listening URL.
Helper methods calls HTTP requests with data structures compatible with these controllers
[AddListener](../src/Controller/Webhook/AddListener.php), [RemoveListener](../src/Controller/Webhook/RemoveListener.php).

```php

/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Client\ApiClient $client
 */

// method call POST /webhook request
$webhook = $client->addWebhookListener('some_api_name', 'some_webhook_event_type', 'https://some-client/listening-url');

// you can register listener URL with priority and required webhook attributes,
// it means than listener SHOULD be called only if server dispatch webhook type
// with attributes containing all specified attributes in webhook registration
// (webhook dispatch can contain more attributes than required)
// priority specified which webhook listener SHOULD be called first,
// if on server is more than one listener for same webhook type,
// this behaviours is implemented in server services in this package,
// but other server implementations can behave differently
// or webhook functionality may not be implemented, so always read specific API documentation!
$webhook = $client->addWebhookListener(
    'some_api_name',
    'some_webhook_event_type',
    'https://some-client/listening-url',
    \SimpleAsFuck\ApiToolkit\Model\Webhook\Priority::NORMAL,
    ['some_key' => '89']
);

// you can save webhook identifier for future use
// deletion while listening is no longer needed, or some data loading in listening url
$webhook->id();

```

```php

/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Client\ApiClient $client
 * @var non-empty-string $webhookId
 */

// method call DELETE /webhook request
$client->removeWebhookListener('some_api_name', $webhookId);

```

For listening dispatched webhooks, you need prepare some POST action on URL reachable from server site.
Action will receive dispatched webhook instance in json body.
Your registered URL SHOULD be unmodified by server, this is default behavior of server services in this package.

You can add before webhook listening actions some authentication middleware,
server services in this package allow dispatching with custom HTTP headers
and support automatically adding https://swagger.io/docs/specification/authentication/bearer-authentication/.

```php
class YourListeningController
{
    public function handle(
        \Psr\Http\Message\ServerRequestInterface $request
        //\Symfony\Component\HttpFoundation\Request $request
    ): \Psr\Http\Message\ResponseInterface {
    //): \Symfony\Component\HttpFoundation\Response {
        $webhook = \SimpleAsFuck\ApiToolkit\Factory\Server\Validator::make($request)
        //$webhook = \SimpleAsFuck\ApiToolkit\Factory\Symfony\Validator::make($request)
            ->json()
            ->object()
            ->class(new \SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookTransformer())
            ->notNull()
        ;

        // run some you logic
        // you should expect than listening action can be called multiple times
        // because of some network error or another failure

        $result = new \SimpleAsFuck\ApiToolkit\Model\Webhook\Result();
        $result = new \SimpleAsFuck\ApiToolkit\Model\Webhook\Result(
            // you can inform server site application to stop
            // dispatching webhook for another listener after current listener
            // which has less priority
            // server services in this package support this functionality
            stopDispatching: true
        )
        // you SHOULD return valid json object,
        // server services in this package expect to receive result object,
        // otherwise dispatch can be detected as failed because of some syntax error
        // and server can dispatch webhook agan
        return \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeJson(
        //return \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeJson(
            $result,
            new \SimpleAsFuck\ApiToolkit\Service\Webhook\ResultTransformer(),
            // you MUST return successful response, otherwise
            // server site application can send webhook agan
            // because error response will look like failed dispatch
            \Kayex\HttpCodes::HTTP_OK
        );
    }
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



// end of your action

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

### Api server webhook tools

For webhook dispatching from server site to a client is here prepared Dispatcher.
Dispatcher will find necessary webhooks for calling by using abstract webhook [Repository](../src/Service/Webhook/Repository.php)
and after then call them by abstract webhook [Client](../src/Service/Webhook/Client.php).

You need to implement webhook Repository, webhook Client and have prepared
some storage for persisting webhooks, also you need to prepare some queue
for webhook call retries.

For Laravel are prepared [LaravelMysqlRepository](../src/Service/Webhook/LaravelMysqlRepository.php) and
[LaravelClient](../src/Service/Webhook/LaravelClient.php) using Laravel [queues](https://laravel.com/docs/queues).

Laravel webhook implementation load automatically configuration from [webhook.php](../config/laravel/webhook.php) config,
which can be published from this package.

```console
php artisan vendor:publish --tag=api-toolkit-config
```

Webhooks are stored in MySql database tables, they are defined in Laravel migration publishable from this package.

```console
php artisan vendor:publish --tag=api-toolkit-migration
```

```php

/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Webhook\Repository $webhookRepository
 * @var \SimpleAsFuck\ApiToolkit\Service\Webhook\Client $webhookClient
 */

$dispatcher = new \SimpleAsFuck\ApiToolkit\Service\Webhook\Dispatcher($webhookRepository, $webhookClient);

// simplest dispatch, when something happened on the server side,
// webhooks calls are added into a queue
$dispatcher->dispatch('some_webhook_event_type');

// webhook call with some attribute,
// for example, you can dispatch an event type with some concrete entity id
$dispatcher->dispatch('some_webhook_event_type', ['some_attribute' => '1256']);

// webhook dispatch with third parameter $synchronouslyFirstTry as true
// will first webhook call try immediately without adding call into queue
// only if the first call fails, webhook call is added into queue for retry
$dispatcher->dispatch('some_webhook_event_type', [], synchronouslyFirstTry: true);

// simple dispatch when the first call will try after 1 minute
$dispatcher->dispatchWithDelay('some_webhook_event_type', [], 60);

```

For webhook listener registration on server site, you can use controllers [AddListener](../src/Controller/Webhook/AddListener.php),
[RemoveListener](../src/Controller/Webhook/RemoveListener.php), [Symfony](../src/Controller/Webhook/Symfony.php) equivalent
or just use webhook [Repository](../src/Service/Webhook/Repository.php) in any action and persist webhook with a processed model
and controllers from here use only as inspiration.

Controllers from this package do not have any publish functionality or not provide any auto-registration in your router,
because of security reasons. You should always have full control in your application, what will be listened to!

You can copy controllers by hand into your app and put them among your other controllers. 
You SHOULD add before webhook actions same authentication middleware as before other actions,
so can be same secure.

If you register `AddListener` on POST /webhook route and `RemoveListener` on DELETE /webhook,
your webhook actions will be compatible with [API client](#api-client-webhook-tools) helper methods for webhooks.