<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Controller\Webhook;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory;
use SimpleAsFuck\ApiToolkit\Factory\Server\Validator;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Repository;

final class RemoveListener implements RequestHandlerInterface
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $webhookId = Validator::make($request)->query()->key('webhookId')->string()->notEmpty()->notNull();

        $this->repository->delete($webhookId);

        return ResponseFactory::makeJson(null);
    }
}
