<?php

declare(strict_types=1);

namespace Conia\Cms\Middleware;

use Conia\Cms\Config;
use Conia\Cms\Users;
use Conia\Quma\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Session implements Middleware
{
    public function __construct(protected Config $config, protected Database $db)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $session = new \Conia\Cms\Session(
            $this->config->app(),
            $this->config->get('session.options'),
            $this->config->get('session.handler', null),
        );

        $session->start();
        $expires = $this->config->get('session.options')['gc_maxlifetime'];
        $lastActivity = $session->lastActivity();

        if ($lastActivity && (time() - $lastActivity > $expires)) {
            $session->forget();
            $session->start();
        }

        $session->signalActivity();
        $userId = $session->authenticatedUserId();

        if ($userId) {
            $user = (new Users($this->db))->byId($userId);
            $request = $request->withAttribute('user', $user);
        }

        $request = $request->withAttribute('session', $session);

        return $handler->handle($request);
    }
}
