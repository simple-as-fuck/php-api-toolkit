<?php

declare(strict_types=1);

return [
    /* configuration for webhook repository */
    // config keys in the 'repository' array can be different and depends on the used repository, registered in service provider
    'repository' => [
        /*
         * configuration for SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelMysqlRepository
         * registered by default in the "simple-as-fuck/php-api-toolkit" package
         */
        // string with connection name where are webhooks stored if null default laravel connection used
        'database-connection' => null,
    ],
];
