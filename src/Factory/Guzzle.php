<?php

declare(strict_types=1);

namespace Conia\Core\Factory;

use Conia\Core\Exception\RuntimeException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/** @psalm-api */
class Guzzle extends AbstractFactory
{
    public function __construct()
    {
        try {
            $factory = new HttpFactory();
            $this->setRequestFactory($factory);
            $this->setResponseFactory($factory);
            $this->setServerRequestFactory($factory);
            $this->setStreamFactory($factory);
            $this->setUploadedFileFactory($factory);
            $this->setUriFactory($factory);
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            throw new RuntimeException('Install guzzlehttp/psr7');
            // @codeCoverageIgnoreEnd
        }
    }

    public function serverRequest(): ServerRequestInterface
    {
        return ServerRequest::fromGlobals();
    }
}