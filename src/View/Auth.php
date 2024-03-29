<?php

declare(strict_types=1);

namespace Conia\Cms\View;

use Conia\Cms\Middleware\Permission;
use Conia\Cms\Schema;
use Conia\Core\Factory;
use Conia\Core\Request;
use Conia\Core\Response;

class Auth
{
    public function __construct(
        protected readonly Factory $factory,
        protected readonly \Conia\Cms\Auth $auth,
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
        $response = Response::create($this->factory);

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
                    ['error' => _('Falscher Benutzername oder Passwort')],
                    $schema->pristineValues()
                ), 400);
            }

            return $response->json($user->array());
        }

        $response->json(
            array_merge(
                ['error' => _('Bitte Benutzernamen und Passwort eingeben')],
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
