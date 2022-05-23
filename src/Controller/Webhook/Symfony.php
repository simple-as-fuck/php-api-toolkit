<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Controller\Webhook;

use SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory;
use SimpleAsFuck\ApiToolkit\Factory\Symfony\Validator;
use SimpleAsFuck\ApiToolkit\Service\Webhook\ParamsTransformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Repository;
use SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class Symfony
{
    private Repository $repository;

    public function __construct(
        Repository $repository
    ) {
        $this->repository = $repository;
    }

    public function addListener(Request $request): Response
    {
        $type = Validator::make($request)->query()->key('type')->string()->notEmpty()->notNull();
        $params = Validator::make($request)->json()->object()->class(new ParamsTransformer())->notNull();

        $webhook = $this->repository->save($type, $params);
        if ($webhook === null) {
            throw new ConflictHttpException('Webhook: \''.$params->listeningUrl().'\' with type: \''.$type.'\' and attribute array already exist');
        }

        return ResponseFactory::makeJson($webhook, new WebhookTransformer());
    }

    public function removeListener(Request $request): Response
    {
        $webhookId = Validator::make($request)->query()->key('webhookId')->string()->notEmpty()->notNull();

        $this->repository->delete($webhookId);

        return ResponseFactory::makeJson(null);
    }
}
