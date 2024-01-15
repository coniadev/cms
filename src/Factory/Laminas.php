<?php

declare(strict_types=1);

namespace Conia\Core\Factory;

use Conia\Core\Exception\RuntimeException;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/** @psalm-api */
class Laminas extends AbstractFactory
{
    public function __construct()
    {
        try {
            $this->setRequestFactory(new RequestFactory());
            $this->setResponseFactory(new ResponseFactory());
            $this->setServerRequestFactory(new ServerRequestFactory());
            $this->setStreamFactory(new StreamFactory());
            $this->setUploadedFileFactory(new UploadedFileFactory());
            $this->setUriFactory(new UriFactory());
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            throw new RuntimeException('Install nyholm/psr7-server');
            // @codeCoverageIgnoreEnd
        }
    }

    public function serverRequest(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }
}