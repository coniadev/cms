<?php

declare(strict_types=1);

namespace Conia\Core\View;

use Conia\Chuck\Factory;
use Conia\Chuck\Request;
use Conia\Chuck\Response;
use Conia\Core\Middleware\Permission;
use Conia\Core\Schema;

class Auth
{
    public function __construct(
        protected readonly Factory $factory,
        protected readonly \Conia\Core\Auth $auth,
    ) {
    }

    #[Permission('authenticated')]
    public function me()
    {
        return [
            'name' => 'User',
            'permissions' => [],
        ];
    }

    public function login(Request $request): Response
    {
        $schema = new Schema\Login();
        $response = Response::fromFactory($this->factory);

        if ($schema->validate($request->json())) {
            $values = $schema->values();
            $user = $this->auth->authenticate(
                $values['login'],
                $values['password'],
                $values['rememberme'],
                true,
            );

            if ($user === false) {
                return $response->json(array_merge(
                    ['error' => _('Incorrect username or password.')],
                    $schema->pristineValues()
                ), 401);
            }

            return $response->json($user);
        }
        $response->json(
            array_merge(
                ['error' => _('Please provide username and password.')],
                $schema->pristineValues()
            ),
            400
        );

        return $response;
    }

    public function logout(): array
    {
        $this->auth->logout();

        return ['ok' => true];
    }
}
