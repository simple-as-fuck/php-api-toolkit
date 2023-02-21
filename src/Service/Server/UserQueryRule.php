<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Model\Server\QueryRule;
use SimpleAsFuck\Validator\Rule\Custom\UserArrayRule;

/**
 * @template TClass
 * @extends UserArrayRule<QueryRule, TClass>
 */
interface UserQueryRule extends UserArrayRule
{
}
