<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\General\Rules;

final class LaravelAdapter extends Repository
{
    public function __construct(
        private readonly \Illuminate\Contracts\Config\Repository $repository,
    ) {
    }

    public function isDebug(): bool
    {
        return $this->get('app.debug')->bool()->nullable() ?? false;
    }

    /**
     * @param non-empty-string $key
     */
    public function get(string $key): Rules
    {
        return Validator::make($this->repository->get($key), 'Config key '.$key);
    }
}
