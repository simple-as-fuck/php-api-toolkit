<?php

declare(strict_types=1);

return [
    /* configuration for webhook dispatcher */
    'dispatch' => [
        // authentication token for https://swagger.io/docs/specification/authentication/bearer-authentication/
        // if not null token is in every webhook call for authentication of application that serving event
        'token' => null,
        // connection name where are asynchronous webhook calls dispatched, if null default laravel queue connection is used
        'queue-connection' => null,
        // if connection support named queue, specific name for webhooks can be defined here, if null default queue name is used
        'queue-name' => null,
        // channel name where failed webhook calls are logged, if null default laravel channel is used
        'log-channel' => null,
        // define how many times will webhook call retry if failed
        'max-tries' => 24,
        // define delay between retries in seconds
        'tries-delay' => 3600,
    ],

    /* configuration for webhook repository */
    // config keys in the 'repository' array can be different and depends on the used repository, registered in service provider
    'repository' => [
        /*
         * configuration for \SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelMysqlRepository
         * registered by default in the "simple-as-fuck/php-api-toolkit" package
         */
        // string with connection name where are webhooks stored if null default laravel connection used
        'database-connection' => null,
    ],
];
