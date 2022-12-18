<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;
use SimpleAsFuck\Validator\Rule\String\StringRule;

class DeprecationsLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private Repository $configRepository
    ) {
    }

    /**
     * @param non-empty-string $apiName
     */
    public function logDeprecation(string $apiName, RequestInterface $request, ResponseInterface $response): void
    {
        $config = $this->configRepository->getClientConfig($apiName);

        $deprecatedContext = [];
        if ($response->hasHeader($config->deprecatedHeader())) {
            $deprecatedContext['Deprecated'] = $response->getHeaderLine($config->deprecatedHeader());
        }
        // https://datatracker.ietf.org/doc/html/rfc8594
        if ($response->hasHeader('Sunset')) {
            $sunset = $response->getHeaderLine('Sunset');
            $deprecatedContext['Sunset'] = StringRule::make($sunset)
                ->parseDateTime(\DateTimeInterface::RFC7231)
                ->nullable(true)
                ?->format(\DateTimeInterface::ATOM)
                ??
                $sunset
            ;
        }
        foreach ($response->getHeader('Link') as $link) {
            if (str_contains($link, 'sunset')) {
                $deprecatedContext['Link'] = StringRule::make($link)
                    ->parseRegex('/^<(?P<link>.+)>;rel="sunset".*$/')
                    ->match('link')
                    ->nullable(true)
                    ??
                    $link
                ;
                break;
            }
        }

        if (count($deprecatedContext) !== 0) {
            $this->logger->warning('Api: '.$apiName.' method: '.$request->getMethod().' uri: "'.$request->getUri()->__toString().'" call is deprecated', $deprecatedContext);
        }
    }
}
