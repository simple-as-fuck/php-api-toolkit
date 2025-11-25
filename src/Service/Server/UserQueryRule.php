<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Data\Server\QueryRule;
use SimpleAsFuck\Validator\Rule\Custom\UserArrayRule;

/**
 * @template TClass of object
 * @extends UserArrayRule<QueryRule, TClass>
 */
interface UserQueryRule extends UserArrayRule
{
}
