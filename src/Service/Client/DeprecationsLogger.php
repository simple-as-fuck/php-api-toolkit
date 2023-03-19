<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\Validator\Rule\String\StringRule;

class DeprecationsLogger
{
    public function __construct(
        private Config $config,
        private LoggerInterface $logger,
        private RequestFactoryInterface $requestFactory
    ) {
    }

    /**
     * @param non-empty-string $apiName
     */
    public function logDeprecation(string $apiName, Request $request, ResponseInterface $response): void
    {
        $deprecatedHeader = $this->config->getDeprecatedHeader($apiName);

        $deprecatedContext = [];
        if ($response->hasHeader($deprecatedHeader)) {
            $deprecatedContext['Deprecated'] = $response->getHeaderLine($deprecatedHeader);
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
            $request = $request->createPsr($this->requestFactory);
            $this->logger->warning('Api: '.$apiName.' method: '.$request->getMethod().' uri: "'.$request->getUri()->__toString().'" call is deprecated', $deprecatedContext);
        }
    }
}
