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
Optionally, you can add deprecations logger for automated logging of `Deprecated` or [Sunset](https://datatracker.ietf.org/doc/html/rfc8594) response header.

Laravel config load automatically configuration from `services.php` config, with structure:

```php
    'some_api_name' => [ // this key is value of first parameter ApiClient::request method
        'base_url' => 'https://some-host/some-base-url', // required
        'default_options' => [ // required, guzzle options array https://docs.guzzlephp.org/en/stable/request-options.html
            'timeout' => null, // required, you MUST always configure some timeout https://docs.guzzlephp.org/en/stable/request-options.html#timeout
        ],
        'default_headers' => [ // optional default [], http headers send in every request
            'Authorization' => 'Bearer tokenexample', // https://swagger.io/docs/specification/authentication/bearer-authentication/
        ],
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
);

$client = new \SimpleAsFuck\ApiToolkit\Service\Client\ApiClient(
    $config,
    new \GuzzleHttp\Client(),
    new \GuzzleHttp\Psr7\HttpFactory(),
    $deprecationsLogger
);

/**
 * @var RequestDataClass $dataForRequestBody
 * @var \SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer<RequestDataClass> $transformerForRequestBody
 * @var \SimpleAsFuck\Validator\Rule\Custom\UserClassRule<ResponseDataClass> $classRuleForResponseBody
 */

try {
    $responseObject = $client->requestObject(
        'some_api_name',
        'POST',
        '/to-some-action',
        $dataForRequestBody,
        $transformerForRequestBody,
        options: [\GuzzleHttp\RequestOptions::TIMEOUT => 3600],
    );
    /*
     * response has getter for json decoded body which is validated after decoding by rule chain
     * request method return ObjectRule, so you can easily validate response json structure
     * is recommended use some you class rule documented here: https://github.com/simple-as-fuck/php-validator#user-class-rule
     * and convert api data structure into some your specific object instance
     */
    $objectFromResponseBody = $responseObject->class($classRuleForResponseBody)->notNull();

    $responseArray = $client->requestArray(
        'some_api_name',
        'GET',
        '/some-list-action',
        ['some-filter-parameter' => 15],
        options: [\GuzzleHttp\RequestOptions::TIMEOUT => 3600],
    );
    /*
     * for responses with json encoded array, method requestArray return ArrayRule
     * allowing validation and conversion every item of array into some your instance
     * all items of array are loaded in to RAM before validation and whole json is decoded at once
     */
    $arrayFromResponseBody = $responseArray->ofClass($classRuleForResponseBody)->notNull();

    $responseStream = $client->requestStream(
        'some_api_name',
        'GET',
        '/some-big-response-action',
        ['some-filter-parameter' => 15],
        options: [\GuzzleHttp\RequestOptions::TIMEOUT => 3600],
    );
    /*
     * stream is response with json encoded lines separated by \n character https://jsonlines.org/
     * allowing validation and conversion every item of stream into some your instance
     * requestStream method return StreamRules with object iterator inside
     * iterator holding one decoded line with small string buffer
     * all validation and conversion into your data instance is lazy executed while you iterating or fetching data
     * network data should be also lazy loaded, requestStream method set guzzle option to use real stream for response body
     * https://docs.guzzlephp.org/en/stable/request-options.html#stream
     * but if guzzle not capable of stream resource fetching, whole stream can be loaded to RAM,
     * depends on your PHP extensions, what guzzle can use
     * ! also beware real network stream can be loaded only once, so multiple iteration typically throw exception !
     */
    $streamFromResponseBody = $responseStream->ofClass($classRuleForResponseBody)->notNull();
}
catch (\SimpleAsFuck\ApiToolkit\Data\Client\ApiException $exception) {
    /*
     * if anything go wrong in request/response processing or response json parsing
     * \SimpleAsFuck\ApiToolkit\Model\Client\ApiException is thrown,
     * and you can handle any error from communication
     */
    // if exception contains http response, rfc9457 status or http status is here, otherwise zero is returned
    $exception->getCode();
    // exception message for logging or debugging is build from https://datatracker.ietf.org/doc/html/rfc9457
    // extended with optional message property, you SHOULD log this, so you know WTF is going wrong
    $logger->error($exception->getMessage()); 
    // short information for end user WTF just happened, if is not null you SHOULD show the tittle on your front end
    $exception->getProblemDetail()?->title;
    // information for end user with more detail, if is not null you SHOULD show the detail on your front end,
    // because detail can contain clue or information how user can solve error, mainly if error is his false :D
    $exception->getProblemDetail()?->detail;
    // parse from error response some extensions, is RECOMMENDED ignoring all errors from error response parsing
    // because you can lose another useful data from error response or if response si corrupted you can lose previous exception
    $exception->getProblemDetailExtensions()?->property('some_error_property')->string()->nullable(failAsNull: true);
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
    ['some_key' => '89'],
);

// you can save webhook identifier for future use
// deletion while listening is no longer needed, or some data loading in listening url
$webhook->id;

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
        $rules = \SimpleAsFuck\ApiToolkit\Factory\Server\Validator::make($request)->webhook();
        //$rules = \SimpleAsFuck\ApiToolkit\Factory\Symfony\Validator::make($request)->webhook();
        $webhook = $rules->notNull();
        $attribute = $rules->attributes()->key('some_attribute')->parseInt()->positive()->nullable();
        $attribute = $webhook->params->attributes['some_attribute'] ?? null;

        // run some you logic
        // you should expect than listening action can be called multiple times
        // because of some network error or another failure

        $response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeWebhookResult();
        //$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeWebhookResult();
        $response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeWebhookResult(
            // https://www.php-fig.org/psr/psr-14/#stoppable-events
            // you can inform server site application to stop
            // dispatching webhook for another listener with less priority
            // server services in this package support this functionality
            stopDispatching: true
        );
        return $response;
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
$someQueryValidValue = $rules->query()->key('someKey')->string()->parseInt()->positive()->notNull();

/** @var \SimpleAsFuck\ApiToolkit\Service\Server\UserQueryRule<YourClass> $yourQueryRule */
$yourObjectFromRequestQuery = $rules->query()->class($yourQueryRule)->notNull();

// validate something from request body with json format
$someJsonValidValue = $rules->json()->object()->property('someProperty')->string()->notEmpty()->maxChar(255)->notNull();

/** @var \SimpleAsFuck\Validator\Rule\Custom\UserClassRule<YourClass> $yourClassRule */
$yourObjectFromRequestBody = $rules->json()->object()->class($yourClassRule)->notNull();

// http error in your action
/** @var bool $shitHappens */
if ($shitHappens) {
    throw new \SimpleAsFuck\ApiToolkit\Data\Server\ApiException(
        'Shit Happens',
        new \SimpleAsFuck\ApiToolkit\Data\Common\ProblemDetail(
            'https://shit-happens.wtf/error',
            418,
            'Shit happens',
            'Developers are looking for some shit in their code.',
            '/error/418',
        ),
        (object) ['wtf' => 418],
        internalMessage: 'Shit Happens, enjoy looking for what happens in the code.'
    );
    //throw new \Symfony\Component\HttpKernel\Exception\HttpException(418, 'Shit Happens'),
}

// end of your action

/**
 * @var YourClass $yourDataForResponseBody
 * @var \SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer<YourClass> $transformer 
 */

// response with one object
$response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeObject($yourDataForResponseBody, $transformer, \Kayex\HttpCodes::HTTP_OK);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeObject($yourDataForResponseBody, $transformer, \Kayex\HttpCodes::HTTP_OK);

// response with some array or collection (avoiding out of memory problem recommended some lazy loading iterator)
$response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeArray(new \ArrayIterator([$yourDataForResponseBody]), $transformer);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeArray(new \ArrayIterator([$yourDataForResponseBody]), $transformer);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeArray([$yourDataForResponseBody], $transformer);

// response with json encoded lines separated by \n character https://jsonlines.org/
// avoiding out of memory problem must be used some lazy loading iterator
/** @var \Iterator<array-key, YourClass> $yourIteratorForResponseBody some iterator with big number of items */
$response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeStream($yourIteratorForResponseBody, $transformer);
//$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeStream($yourIteratorForResponseBody, $transformer);
```

### Api server middleware tools

If anything go wrong you can use Exception transformers in your exception catching middleware or in some exception handler.

For laravel is prepared Laravel config adapter which load automatically configuration for ExceptionTransformer,
you can easily get this transformer from DI, without any new configuration (standard configuration from Laravel is used).

```php

/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Config\Repository $configRepository
 * @var \Psr\Log\LoggerInterface $logger
 */

try {
    // some breakable logic
}
catch(\SimpleAsFuck\ApiToolkit\Data\Server\ApiException $exception) {
    // exception message for logging or debugging, you SHOULD log this, so you know WTF is going wrong
    $logger->error(implode(', ', [$exception->getMessage(), (string) $exception->getInternalMessage()]), [
        'type' => $exception->getProblemDetail()?->type,
        'status' => $exception->getCode(),
        'instance' => $exception->getProblemDetail()?->instance,
        'extensions' => $exception->getProblemDetailExtensions(),
    ]);

    $response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeObject(
    //$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeObject(
        $exception,
        // transformer will convert exception in to https://datatracker.ietf.org/doc/html/rfc9457 json object with message and all another extensions
        new \SimpleAsFuck\ApiToolkit\Service\Server\ApiExceptionTransformer(),
        $exception->getCode()
    );
}
// if you use Symfony Http Exceptions you can use HttpExceptionTransformer
catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
    $logger->error($exception->getMessage(), ['status' => $exception->getStatusCode()]);

    $response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeObject(
        $exception,
        // transformer will convert exception into json object
        // with message property contains message from http exception
        // and https://datatracker.ietf.org/doc/html/rfc9457#name-status property
        new \SimpleAsFuck\ApiToolkit\Service\Symfony\HttpExceptionTransformer(),
        $exception->getStatusCode()
    );
}
catch (\Throwable $exception) {
    $logger->error($exception->getMessage());

    $response = \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeObject(
    //$response = \SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory::makeObject(
        $exception,
        // transformer will convert exception in to json object
        // if application has turned off debug, message property contain only "Internal server error"
        // but with enabled debug message contains exception type, message, file and line where was exception thrown
        // with enabled debug json object also contains trace property with exception stacktrace
        // and json object contains https://datatracker.ietf.org/doc/html/rfc9457#name-status property always with 500 http code
        new \SimpleAsFuck\ApiToolkit\Service\Server\ExceptionTransformer($configRepository),
        \Kayex\HttpCodes::HTTP_INTERNAL_SERVER_ERROR
    );
}

```

### Api server webhook tools

For webhook dispatching from server site to a client is here prepared WebhookDispatcher.
WebhookDispatcher will find necessary webhooks for calling by using abstract webhook [Repository](../src/Service/Webhook/Repository.php)
and after then call them by abstract [WebhookClient](../src/Service/Webhook/WebhookClient.php).

You can implement webhook Repository, webhook Client and have prepared
some storage for persisting webhooks, also you need to prepare some queue
for webhook call retries.

For Laravel are prepared [LaravelMysqlRepository](../src/Service/Webhook/LaravelMysqlRepository.php) and
[LaravelClient](../src/Service/Webhook/LaravelClient.php) using Laravel [queues](https://laravel.com/docs/queues).

Laravel webhook implementation load automatically configuration from [webhook.php](../config/laravel/webhook.php) config,
you should publish configuration from this package and change for your needs.

```console
php artisan vendor:publish --tag=api-toolkit-config
```

You can store webhooks in MySql database tables, they are defined in Laravel migration publishable from this package.

```console
php artisan vendor:publish --tag=api-toolkit-migration
```

```php

/**
 * @var \SimpleAsFuck\ApiToolkit\Service\Webhook\Repository $webhookRepository
 * @var \SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookClient $webhookClient
 */

$dispatcher = new \SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookDispatcher($webhookRepository, $webhookClient);

// simplest dispatch, when something happened on the server side,
// webhooks calls are added into a queue
$dispatcher->dispatch('some_webhook_event_type', options: [\GuzzleHttp\RequestOptions::TIMEOUT => 20]);

// webhook call with some attribute,
// for example, you can dispatch an event type with some specific entity id
$dispatcher->dispatch('some_webhook_event_type', attributes: ['some_attribute' => '1256'], options: [\GuzzleHttp\RequestOptions::TIMEOUT => 20]);

// webhook call try immediately without adding call into queue
// only if the first call fails, webhook call is added into queue for retry
$dispatcher->call('some_webhook_event_type', options: [\GuzzleHttp\RequestOptions::TIMEOUT => 20]);

// simple dispatch when the first call will try after 1 minute
$dispatcher->dispatchWithDelay(60, 'some_webhook_event_type', options: [\GuzzleHttp\RequestOptions::TIMEOUT => 20]);

// you can build webhook sequence by your custom logic without using $webhookRepository
// beware you still need some queue for webhook retries if calls failed
/** @var iterable<\SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook> $webhooks */
$webhookClient->dispatchWebhooks($webhooks, options: [\GuzzleHttp\RequestOptions::TIMEOUT => 20]);
// first try without queue
$webhookClient->callWebhooks($webhooks, options: [\GuzzleHttp\RequestOptions::TIMEOUT => 20]);

```

For webhook listener registration on server site, you can use controllers [AddListener](../src/Controller/Webhook/AddListener.php),
[RemoveListener](../src/Controller/Webhook/RemoveListener.php), [Symfony](../src/Controller/Webhook/Symfony.php) equivalent
or just use webhook [Repository](../src/Service/Webhook/Repository.php) in any action and persist webhook with a processed data
and controllers from here use only as inspiration.

Controllers from this package do not have any publish functionality or not provide any auto-registration in your router,
because of security reasons. You should always have full control in your application, what will be listened to!

You can copy controllers by hand into your app and put them among your other controllers. 
You SHOULD add before webhook actions same authentication middleware as before other actions,
so can be same secure.

If you register `AddListener` on POST /webhook route and `RemoveListener` on DELETE /webhook,
your webhook actions will be compatible with [API client](#api-client-webhook-tools) helper methods for webhooks.