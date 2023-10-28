<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Controller\Webhook;

use Kayex\HttpCodes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory;
use SimpleAsFuck\ApiToolkit\Factory\Server\Validator;
use SimpleAsFuck\ApiToolkit\Model\Server\ApiException;
use SimpleAsFuck\ApiToolkit\Service\Webhook\ParamsTransformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Repository;
use SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookTransformer;

final class AddListener implements RequestHandlerInterface
{
    public function __construct(
        private Repository $repository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $type = Validator::make($request)->query()->key('type')->string()->notEmpty()->notNull();
        $params = Validator::make($request)->json()->object()->class(new ParamsTransformer())->notNull();

        $webhook = $this->repository->save($type, $params);
        if ($webhook === null) {
            throw new ApiException('Webhook: \''.$params->listeningUrl().'\' with type: \''.$type.'\' already exist', HttpCodes::HTTP_CONFLICT);
        }

        return ResponseFactory::makeJson($webhook, new WebhookTransformer());
    }
}
