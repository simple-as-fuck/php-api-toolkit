<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;

abstract class Repository
{
    /**
     * method loads all webhooks that SHOULD be called while some webhook type dispatching,
     * saved webhook MUST be filtered by dispatched $attributes and $type,
     * if all saved webhook attributes (keys and values) for one unique webhook
     * are in dispatched $attributes or saved webhook not have any attributes
     * and webhook type match, the webhook MUST be returned in iterable
     *
     * iterable MUST be sorted by saved webhook priority in descendant order, priority is integer value
     * and a bigger value means the highest priority if priority for two webhooks is the same
     * webhook with more attribute matches MUST be in iterable before webhook with less attribute matches
     *
     * webhook ordering causes listeners who want more specific data,
     * and the data are more relevant for the listener will be called before
     * than listeners who accept anything
     *
     * @param non-empty-string $type
     * @param array<string, string> $attributes with webhook will be dispatched
     * @return iterable<Webhook>
     */
    abstract public function loadForDispatching(string $type, array $attributes): iterable;

    /**
     * the method saves one unique webhook if the duplicate webhook exists method MUST return null
     *
     * unique webhook means that a combination of webhook $type and $params->listeningUrl
     * can be saved only once
     *
     * the uniqueness of webhook causes than one listening url can listen to more webhook types
     * but solves redundant tries to notify the same webhook type for one listener
     *
     * @param non-empty-string $type
     * @return Webhook|null null if unique webhook already exists
     */
    abstract public function save(string $type, Params $params): ?Webhook;

    /**
     * method deletes unique webhook by its identifier
     *
     * it is UNRECOMMENDED using any soft-deleting for better performance,
     * you SHOULD think than webhooks are temporary instances, and if you do not need
     * to listen any more for some webhook mainly if you use required attributes, just delete webhook
     * and reduce the number of webhooks that will be searched for dispatching
     *
     * @param non-empty-string $webhookId
     */
    abstract public function delete(string $webhookId): void;
}
